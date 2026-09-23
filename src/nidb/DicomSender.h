#pragma once

#include <QObject>
#include <QString>
#include <QStringList>
#include <QList>
#include <atomic>

/*
 * DicomSender
 * -----------
 * Sends a list of DICOM files to a remote Storage SCP (C-STORE) using DCMTK's
 * DcmStorageSCU. DcmStorageSCU takes care of the tedious parts:
 *   - reading SOP Class / Instance / Transfer Syntax from each file's meta header
 *   - building the presentation contexts (and splitting the job across multiple
 *     associations if more than 128 contexts are needed)
 *   - picking a transfer syntax the peer accepted, decompressing if allowed
 *
 * sendFiles() is BLOCKING. Call it from a worker thread (QtConcurrent::run,
 * QThread, etc.) if you're in a GUI app. Signals are emitted from that thread;
 * connect with Qt::QueuedConnection (the default across threads) to update UI.
 */

struct DicomSendResult
{
    QString file;
    bool    sent       = false;   // C-STORE request actually went out
    quint16 dimseStatus = 0xFFFF; // 0x0000 success, 0xBxxx warning, else failure
    QString error;                // populated if the file was rejected or not sent

    bool success() const { return sent && (dimseStatus == 0x0000 || (dimseStatus & 0xF000) == 0xB000); }
};

class DicomSender : public QObject
{
    Q_OBJECT
public:
    explicit DicomSender(QObject *parent = nullptr);

    // Connection settings
    void setLocalAETitle(const QString &ae)  { m_localAE = ae; }
    void setRemoteAETitle(const QString &ae) { m_remoteAE = ae; }
    void setRemoteHost(const QString &host)  { m_host = host; }
    void setRemotePort(quint16 port)         { m_port = port; }
    void setTimeoutSeconds(int secs)         { m_timeout = secs; }
    void setMaxPDU(quint32 bytes)            { m_maxPDU = bytes; }

    // Blocking send. Returns one result per input file (same order).
    QList<DicomSendResult> sendFiles(const QStringList &files);

    // Optional C-ECHO to check connectivity before a big send.
    bool echo(QString *errorOut = nullptr);

    // Thread-safe: request the transfer to stop after the current instance.
    void cancel() { m_cancel = true; }

    QString lastError() const { return m_lastError; }

signals:
    void progress(int done, int total, const QString &currentFile);
    void fileSent(const QString &file, bool ok, quint16 dimseStatus);
    void logMessage(const QString &msg);

private:
    friend class QtStorageSCU;

    QString m_localAE  = QStringLiteral("QTSTORESCU");
    QString m_remoteAE = QStringLiteral("ANY-SCP");
    QString m_host     = QStringLiteral("localhost");
    quint16 m_port     = 104;
    int     m_timeout  = 30;
    quint32 m_maxPDU   = 16384;

    std::atomic_bool m_cancel { false };
    QString m_lastError;
};
