#include "DicomSender.h"

#include <QHash>

#include "dcmtk/config/osconfig.h"   // must come first in DCMTK includes
#include "dcmtk/dcmnet/dstorscu.h"
#include "dcmtk/dcmnet/scu.h"
#include "dcmtk/dcmdata/dcuid.h"

/*
 * Subclass DcmStorageSCU so we can hook its per-instance callbacks and turn
 * them into Qt signals / cancellation checks.
 */
class QtStorageSCU : public DcmStorageSCU
{
public:
    QtStorageSCU(DicomSender *owner, int total) : m_owner(owner), m_total(total) {}

    int done() const { return m_done; }

    struct Outcome { bool sent; quint16 status; };
    const QHash<QString, Outcome> &outcomes() const { return m_outcomes; }

protected:
    void notifySOPInstanceToBeSent(const TransferEntry &entry) override
    {
        emit m_owner->progress(m_done, m_total, QString::fromLocal8Bit(entry.Filename.getCharPointer()));
    }

    void notifySOPInstanceSent(const TransferEntry &entry) override
    {
        ++m_done;
        const QString file = QString::fromLocal8Bit(entry.Filename.getCharPointer());
        const bool ok = entry.RequestSent &&
                        (entry.ResponseStatusCode == 0x0000 || (entry.ResponseStatusCode & 0xF000) == 0xB000);
        m_outcomes.insert(file, Outcome{ entry.RequestSent == OFTrue, entry.ResponseStatusCode });
        emit m_owner->fileSent(file, ok, entry.ResponseStatusCode);
        emit m_owner->progress(m_done, m_total, file);
    }

    OFBool shouldStopAfterCurrentSOPInstance() override
    {
        return m_owner->m_cancel.load() ? OFTrue : OFFalse;
    }

private:
    DicomSender *m_owner;
    int m_total;
    int m_done = 0;
    QHash<QString, Outcome> m_outcomes;
};

DicomSender::DicomSender(QObject *parent) : QObject(parent) {}

static void configureSCU(DcmSCU &scu, const QString &localAE, const QString &remoteAE,
                         const QString &host, quint16 port, int timeout, quint32 maxPDU)
{
    scu.setAETitle(localAE.toStdString().c_str());
    scu.setPeerAETitle(remoteAE.toStdString().c_str());
    scu.setPeerHostName(host.toStdString().c_str());
    scu.setPeerPort(port);
    scu.setMaxReceivePDULength(maxPDU);
    scu.setACSETimeout(timeout);
    scu.setDIMSETimeout(timeout);
    scu.setDIMSEBlockingMode(DIMSE_NONBLOCKING); // so the DIMSE timeout is honored
    scu.setConnectionTimeout(timeout);
}

bool DicomSender::echo(QString *errorOut)
{
    DcmSCU scu;
    configureSCU(scu, m_localAE, m_remoteAE, m_host, m_port, m_timeout, m_maxPDU);

    OFList<OFString> ts;
    ts.push_back(UID_LittleEndianExplicitTransferSyntax);
    ts.push_back(UID_LittleEndianImplicitTransferSyntax);
    scu.addPresentationContext(UID_VerificationSOPClass, ts);

    OFCondition cond = scu.initNetwork();
    if (cond.good()) cond = scu.negotiateAssociation();
    if (cond.good()) cond = scu.sendECHORequest(0);
    if (cond.good()) scu.releaseAssociation();

    if (cond.bad() && errorOut)
        *errorOut = QString::fromLatin1(cond.text());
    return cond.good();
}

QList<DicomSendResult> DicomSender::sendFiles(const QStringList &files)
{
    m_cancel = false;
    m_lastError.clear();

    // Pre-populate results so every input file gets an entry, in order.
    QList<DicomSendResult> results;
    QHash<QString, int> indexOf;
    results.reserve(files.size());
    for (const QString &f : files) {
        indexOf.insert(f, results.size());
        DicomSendResult r;
        r.file = f;
        results.append(r);
    }

    QtStorageSCU scu(this, files.size());
    configureSCU(scu, m_localAE, m_remoteAE, m_host, m_port, m_timeout, m_maxPDU);

    // If the peer doesn't accept a file's native (compressed) transfer syntax,
    // allow DCMTK to decompress lossless images and send uncompressed.
    // (Requires codecs to be registered - see main.cpp.)
    scu.setDecompressionMode(DcmStorageSCU::DM_losslessOnly);
    // Keep going if one instance fails instead of aborting the whole batch.
    scu.setHaltOnUnsuccessfulStoreMode(OFFalse);
    scu.setHaltOnInvalidFileMode(OFFalse);

    // 1. Add files. ERM_fileOnly = read only the meta header, which is fast;
    //    the dataset is loaded later when it's actually sent.
    for (const QString &f : files) {
        OFFilename ofname(f.toLocal8Bit().constData());
        OFCondition cond = scu.addDicomFile(ofname, ERM_fileOnly, OFTrue /* validate UIDs */);
        if (cond.bad()) {
            results[indexOf.value(f)].error = QStringLiteral("Not added: %1").arg(cond.text());
            emit logMessage(QStringLiteral("Skipping %1: %2").arg(f, cond.text()));
        }
    }

    if (scu.getNumberOfSOPInstances() == 0) {
        m_lastError = QStringLiteral("No valid DICOM files to send");
        return results;
    }

    OFCondition cond;

    // 2. One or more associations: addPresentationContexts() adds up to 128
    //    contexts for instances not yet sent, and returns
    //    NET_EC_NoPresentationContextsDefined once everything has been handled.
    while (!m_cancel) {
        cond = scu.addPresentationContexts();
        if (cond == NET_EC_NoPresentationContextsDefined)
            break; // all done
        if (cond.bad()) {
            m_lastError = QStringLiteral("addPresentationContexts failed: %1").arg(cond.text());
            break;
        }

        // DcmSCU needs the presentation contexts in place before initNetwork(),
        // so (re)initialize for each association batch.
        cond = scu.initNetwork();
        if (cond.bad()) {
            m_lastError = QStringLiteral("initNetwork failed: %1").arg(cond.text());
            break;
        }

        emit logMessage(QStringLiteral("Requesting association %1 -> %2@%3:%4")
                            .arg(m_localAE, m_remoteAE, m_host).arg(m_port));
        cond = scu.negotiateAssociation();
        if (cond.bad()) {
            m_lastError = QStringLiteral("Association failed: %1").arg(cond.text());
            // NoAcceptablePresentationContexts: peer rejected this batch's SOP
            // classes; the instances are marked as handled, so try the next batch.
            if (cond == NET_EC_NoAcceptablePresentationContexts)
                continue;
            break;
        }

        cond = scu.sendSOPInstances();
        if (cond.bad())
            m_lastError = QStringLiteral("sendSOPInstances: %1").arg(cond.text());

        scu.releaseAssociation();
    }

    // 3. Fill in per-file status from what the callbacks recorded.
    for (DicomSendResult &r : results) {
        if (!r.error.isEmpty())
            continue; // rejected by addDicomFile()
        auto it = scu.outcomes().find(r.file);
        if (it == scu.outcomes().end()) {
            r.error = m_cancel ? QStringLiteral("Cancelled")
                               : QStringLiteral("Not sent (SOP class/transfer syntax not accepted, or association failed)");
            continue;
        }
        r.sent = it->sent;
        r.dimseStatus = it->status;
        if (!r.sent)
            r.error = QStringLiteral("Not sent: peer did not accept this SOP class / transfer syntax");
        else if (!r.success())
            r.error = QStringLiteral("C-STORE failed, status 0x%1").arg(r.dimseStatus, 4, 16, QLatin1Char('0'));
    }

    OFString summary;
    scu.getStatusSummary(summary);
    emit logMessage(QString::fromLatin1(summary.c_str()));

    return results;
}
