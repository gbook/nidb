/* ------------------------------------------------------------------------------
  NIDB squirrelImageIO.cpp
  Copyright (C) 2004 - 2025
  Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
  Olin Neuropsychiatry Research Center, Hartford Hospital
  ------------------------------------------------------------------------------
  GPLv3 License:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  ------------------------------------------------------------------------------ */

#include "squirrelImageIO.h"
#include "parrec.h"

#ifdef USE_DCM2NIIX_LIB
/* dcm2niix in-process conversion API (compiled directly into squirrellib) */
#include "nii_dicom_batch.h"
#endif

using namespace std;

/* ---------------------------------------------------------- */
/* --------- align4 ----------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief align a number to 4
 * @param x
 * @return
 */
static int align4(int x)
{
    return (x + 3) & ~3;
}

/* ---------------------------------------------------------- */
/* --------- squirrelImageIO -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * squirrelImageIO constructor
*/
squirrelImageIO::squirrelImageIO()
{
}


/* ---------------------------------------------------------- */
/* --------- ~squirrelImageIO ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * squirrelImageIO destructor
*/
squirrelImageIO::~squirrelImageIO()
{
}


/* ---------------------------------------------------------- */
/* --------- Exiftool --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run exiftool to get DICOM tags. Output should be terminated with {ready}, otherwise it is incomplete
 * @param arg The DICOM (or other type) file
 * @return The full output from exiftool
 */
QString squirrelImageIO::Exiftool(QString arg) {
    QString str;

    QFileInfo fileInfo(arg);
    QString filename = fileInfo.fileName();

    QString systemstring = "exiftool " + arg;
    str = utils::SystemCommand(systemstring, false);

    /* check if the output is not truncated or cut off */
    if (str.size() < 50) {
        //Print(n->Log(QString("*** Exiftool output from file [%1] is ONLY [%2] bytes. str contains [%3] ***").arg(arg).arg(str.size()).arg(str)));
        str = "";
    }
    /* check if the output contains the filename passed to exiftool  */
    else if (!str.contains(filename.trimmed(), Qt::CaseInsensitive)) {
        //Print(n->Log(QString("*** Exiftool output from file [%1] does NOT contain the file name [%2]. size is [%3] bytes. str is [%4] ***").arg(arg).arg(filename).arg(str.size()).arg(str)));
        str = "";
    }
    /* check if the str is blank */
    else if (str == "") {
        //Print(n->Log(QString("*** Exiftool output from file [%1] is empty ***").arg(arg)));
        str = "";
    }

    return str;
}

/* ---------------------------------------------------------- */
/* --------- GetImageTagsDCMTK ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief squirrelImageIO::GetImageTagsDCMTK
 * @param f
 * @param tags
 * @param msg
 * @return
 */
bool squirrelImageIO::GetImageTagsDCMTK(QString f, QHash<QString, QString> &tags) {

    QStringList msgs;

    tags["FilePath"] = f;

    QByteArray filenameBA = f.toLatin1();
    const char* filename = filenameBA.constData();

    DcmFileFormat fileformat;
    OFCondition status = fileformat.loadFileUntilTag(filename, EXS_Unknown, EGL_noChange, DCM_MaxReadLength, ERM_autoDetect, DcmTagKey(0x7FE0, 0x0010));
    if (status.good()) {
        tags["FileType"] = "DICOM";
        DcmStack stack;
        DcmDataset *dataset = fileformat.getDataset();
        while (dataset->nextObject(stack, OFTrue /*intoSub*/).good())
        {
            DcmObject *object = stack.top();
            QString tagName = DcmTag(object->getTag()).getTagName();
            if (tagName.startsWith("Unknown")) {
                int group = DcmTag(object->getTag()).getGroup();
                int element = DcmTag(object->getTag()).getElement();
                tagName = QString("Unknown_%1x%2").arg(group, 4, 10, QChar('0')).arg(element, 4, 10, QChar('0'));
            }

            if (object->isElement()) {
                OFString strValue;
                DcmElement *element = OFstatic_cast(DcmElement *, object);
                if (element->getOFStringArray(strValue).good()) {
                    /* read the Siemens binary encoded CSA header */
                    if ((object->getGTag() == 0x0029) && (object->getETag() == 0x1010)) {
                        QString hexstr = strValue.c_str();
                        hexstr.remove('\\');
                        QByteArray bytes = QByteArray::fromHex(hexstr.toLatin1());

                        QMap<QString, CsaElement> csaTags = ParseSiemensCSA(bytes);
                        for (auto i = csaTags.cbegin(), end = csaTags.cend(); i != end; ++i) {
                            CsaElement elem = i.value();
                            QString name = i.key();
                            QString vr = i.value().vr;
                            QString val;
                            if (elem.values.size() > 0) {
                                if (vr == "LO" || vr == "SH" || vr == "ST" || vr == "LT" || vr == "AE" || vr == "CS" || vr == "UT" || vr == "DS" || vr == "IS") {
                                    val = csaToString(elem.values.first());
                                }
                                else if (vr == "FD" || vr == "FL") {
                                    val = QString("%1").arg(csaToDouble(elem.values.first()));
                                }
                                else if (vr == "SL" || vr == "UL" || vr == "SS" || vr == "US") {
                                    val = QString("%1").arg(csaToInteger(elem.values.first()));
                                }
                            }
                            else {
                                //printf("Value appears to be empty\n");
                            }
                            val.remove(QChar('\0'));
                            tags[name] = val.trimmed();
                        }
                    }
                    /* read the Siemens MrPhoenixProtocol header */
                    else if ((object->getGTag() == 0x0029) && (object->getETag() == 0x1020)) {
                        QString hexstr = strValue.c_str();
                        hexstr.remove('\\');
                        QByteArray bytes = QByteArray::fromHex(hexstr.toLatin1());
                        QString text = QString::fromLatin1(bytes);
                        QStringList lines = text.split("\n");
                        foreach (QString line, lines) {
                            if (line.startsWith("sSliceArray.asSlice[0].dInPlaneRot") && (line.size() < 70)) {
                                /* make sure the line does not contain any non-printable ASCII control characters */
                                if (!line.contains(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")))) {
                                    qint64 idx = line.indexOf(".dInPlaneRot");
                                    line = line.mid(idx,23);
                                    QStringList vals = line.split(QRegularExpression("\\s+"));
                                    if (vals.size() > 0)
                                        tags["PhaseEncodeAngle"] = vals.last().trimmed();
                                    break;
                                }
                            }
                        }
                    }
                    /* read all other tags */
                    else {
                        tags[tagName] = strValue.c_str();
                    }
                }
                else if (element->isLeaf()) {
                    tags[tagName] = "";
                }
            }
        }
    }
    else {
        tags["Valid"] = "0";
        tags["ParseMessages"] = msgs.join("\n");
        return false;
    }

    tags["ParseMessages"] = msgs.join("\n");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- csaToDouble ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Convert a value stored in a CSA byte array into a double
 * @param v The bytearray
 * @return The value
 */
double squirrelImageIO::csaToDouble(const QByteArray& v)
{
    QDataStream s(v);
    s.setByteOrder(QDataStream::LittleEndian);

    double d;
    s >> d;
    return d;
}


/* ---------------------------------------------------------- */
/* --------- csaToInteger ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Convert a value stored in a CSA byte array into an integer
 * @param v The bytearray
 * @return The value
 */
int squirrelImageIO::csaToInteger(const QByteArray& v)
{
    QDataStream s(v);
    s.setByteOrder(QDataStream::LittleEndian);

    int d;
    s >> d;
    return d;
}


/* ---------------------------------------------------------- */
/* --------- csaToString ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Convert a value stored in a CSA byte array into a QString
 * @param v The bytearray
 * @return The value
 */
QString squirrelImageIO::csaToString(const QByteArray& v)
{
    return QString::fromLatin1(v).trimmed();
}


/* ---------------------------------------------------------- */
/* --------- ParseSiemensCSA -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parse an older style Siemens CSA header. This function was generated by ChatGPT...
 * @param csa - The byte array extracted from the DICOM header
 * @return A list of found elements
 */
QMap<QString, CsaElement> squirrelImageIO::ParseSiemensCSA(const QByteArray& csa)
{
    QMap<QString, CsaElement> result;

    QDataStream stream(csa);
    stream.setByteOrder(QDataStream::LittleEndian);

    char header[4];
    stream.readRawData(header, 4);

    /* check for SV10 or SV12 */
    if (memcmp(header, "SV1", 3) != 0)
        return result;

    stream.skipRawData(4); // unused

    qint32 nTags;
    stream >> nTags;

    stream.skipRawData(4); // unused

    /* nTags, nItems and len below all come straight from the (untrusted) DICOM
       blob. Every one of them is bounded against the size of the blob before it
       is used as a loop count or an allocation size: a corrupt or truncated CSA
       header must never drive a huge allocation or an out-of-bounds read. The
       whole header cannot describe more entries than it has bytes, so csa.size()
       is a safe (loose) upper bound. stream.status() is checked after each read
       so a read past the end of the blob stops parsing instead of continuing
       with garbage. */
    const int maxBytes = csa.size();
    if ((nTags < 0) || (nTags > maxBytes))
        return result;

    for (int t = 0; t < nTags; ++t)
    {
        if (stream.status() != QDataStream::Ok)
            break;

        char nameBuf[64];
        if (stream.readRawData(nameBuf, 64) != 64)
            break;
        /* nameBuf is not guaranteed to contain a NUL within 64 bytes, so bound
           the scan explicitly rather than letting QByteArray run off the end */
        QString name = QString::fromLatin1(nameBuf, qstrnlen(nameBuf, 64));

        qint32 vm;
        stream >> vm;

        char vrBuf[4];
        if (stream.readRawData(vrBuf, 4) != 4)
            break;
        QString vr = QString::fromLatin1(vrBuf, qstrnlen(vrBuf, 4)).trimmed();

        qint32 syngo_dt;
        qint32 nItems;
        qint32 unused;

        stream >> syngo_dt >> nItems >> unused;
        if (stream.status() != QDataStream::Ok)
            break;

        if ((nItems < 0) || (nItems > maxBytes))
            break;

        CsaElement element;
        element.name = name;
        element.vr = vr;

        for (int i = 0; i < nItems; ++i)
        {
            qint32 itemLen[4];
            if (stream.readRawData(reinterpret_cast<char*>(itemLen), 16) != 16)
                break;

            int len = itemLen[1];   // actual length

            /* refuse a length that is negative or larger than the bytes still
               left in the blob - either indicates a corrupt/truncated header */
            qint64 avail = stream.device() ? stream.device()->bytesAvailable() : 0;
            if ((len < 0) || (len > avail))
                break;

            QByteArray value(len, Qt::Uninitialized);
            if (len > 0) {
                if (stream.readRawData(value.data(), len) != len)
                    break;
            }

            // Skip padding
            int pad = align4(len) - len;
            if (pad > 0)
                stream.skipRawData(pad);

            element.values.append(value);
        }

        result.insert(name, element);
    }

    return result;
}

/* ---------------------------------------------------------- */
/* --------- ConvertDicom ----------------------------------- */
/* ---------------------------------------------------------- */
bool squirrelImageIO::ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg) {

    QStringList msgs;

    QString pwd = QDir::currentPath();

    numfilesconv = 0; /* need to fix this to be correct at some point */

    msgs << QString("Converting DICOM to Nifti.  indir [" + indir + "]  outdir [" + outdir + "]  outfiletype [" + filetype + "]");

    /* validate the requested output filetype up front */
    if ((filetype != "nifti4d") && (filetype != "nifti4dgz") && (filetype != "nifti3d") && (filetype != "nifti3dgz")) {
        msg = msgs.join("\n");
        return false;
    }

    /* create the output directory */
    QString m;
    if (!utils::MakePath(outdir, m)) {
        msgs << "Unable to create path [" + outdir + "] because of error [" + m + "]";
        msg = msgs.join("\n");
        return false;
    }

    if ((outdir == "") || (outdir == "/")) {
        msg = msgs.join("\n");
        return false;
    }

    /* delete any files that may already be in the output directory.. for example, an incomplete series was put in the output directory
     * remove any stuff and start from scratch to ensure proper file numbering */
    for (const QString &stale : QDir(outdir).entryList(QStringList() << "*.hdr" << "*.img" << "*.nii" << "*.gz", QDir::Files))
        QFile::remove(QDir(outdir).absoluteFilePath(stale));

#ifdef USE_DCM2NIIX_LIB
    /* ----- in-process conversion using the embedded dcm2niix library ----- */
    Q_UNUSED(bindir);
    Q_UNUSED(pwd);
    Q_UNUSED(gzip);

    /* in case of par/rec, the input to dcm2niix is a file instead of a directory */
    QString inputArg = indir;
    if (datatype == "parrec") {
        QStringList parFiles = QDir(indir).entryList(QStringList() << "*.par" << "*.PAR", QDir::Files);
        if (!parFiles.isEmpty())
            inputArg = QDir(indir).absoluteFilePath(parFiles.first());
    }

    struct TDCMopts opts;
    setDefaultOpts(&opts, NULL);
    opts.isCreateBIDS = false;                          /* matches the old '-b n' */
    opts.isGz = (filetype == "nifti4dgz") || (filetype == "nifti3dgz");
    opts.isSave3D = (filetype == "nifti3d") || (filetype == "nifti3dgz");
    opts.isVerbose = 0;
    opts.isProgress = 0;
    snprintf(opts.indir, sizeof(opts.indir), "%s", inputArg.toUtf8().constData());
    snprintf(opts.outdir, sizeof(opts.outdir), "%s", outdir.toUtf8().constData());

    int rc = nii_loadDir(&opts);
    if (rc != 0)
        msgs << QString("dcm2niix (in-process) returned non-zero exit code [%1]").arg(rc);

    /* dcm2niix gzips internally when isGz is set; no manual gzip step needed */
#else
    /* ----- fallback: shell out to an external dcm2niix executable ----- */
    /* in case of par/rec, the argument list to dcm2niix is a file instead of a directory */
    QString fileext = "";
    if (datatype == "parrec")
        fileext = "/*.par";

    QString systemstring;
    QDir::setCurrent(indir);
    if (filetype == "nifti4d")
        systemstring = QString("%1/./dcm2niix -1 -b n -o '%2' %3%4").arg(bindir).arg(outdir).arg(indir).arg(fileext);
    else if (filetype == "nifti4dgz")
        systemstring = QString("%1/./dcm2niix -1 -b n -z y -o '%2' %3%4").arg(bindir).arg(outdir).arg(indir).arg(fileext);
    else if (filetype == "nifti3d")
        systemstring = QString("%1/./dcm2niix -1 -b n -z 3 -o '%2' %3%4").arg(bindir).arg(outdir).arg(indir).arg(fileext);
    else if (filetype == "nifti3dgz")
        systemstring = QString("%1/./dcm2niix -1 -b n -z 3 -o '%2' %3%4").arg(bindir).arg(outdir).arg(indir).arg(fileext);

    msgs << utils::SystemCommand(systemstring, true, true);

    /* conversion should be done, so check if it actually gzipped the file */
    if ((gzip) && (filetype != "bids")) {
        systemstring = "cd " + outdir + "; gzip *";
        msgs << utils::SystemCommand(systemstring, true);
    }

    /* change back to original directory before leaving */
    QDir::setCurrent(pwd);
#endif

    /* rename the files into something meaningful */
    m = "";
    if (!utils::BatchRenameFiles(outdir, seriesnum, studynum, uid, numfilesrenamed, m))
        msgs << "Error renaming output files [" + m + "]";

    msg = msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDicomFileInPlace ---------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Anonymize a single DICOM file in place using DCMTK
 *
 * Each listed tag that exists in the top-level dataset is overwritten with a
 * value appropriate for its VR: dates become 19000101, times 000000.000000,
 * person names and text become "Anon", numeric/age values are emptied, and
 * sequences are removed. Tags not present in the file are not added.
 *
 * The file is written to a temporary file in the same directory, which then
 * replaces the original, so a failure never leaves a half-written file.
 *
 * @param file the DICOM file
 * @param tagsToChange list of tags to anonymize
 * @param msg error/status messages
 * @return true if the file was successfully anonymized and saved
 */
bool squirrelImageIO::AnonymizeDicomFileInPlace(QString file, QList<DcmTagKey> tagsToChange, QString &msg)
{
    if (tagsToChange.isEmpty()) {
        msg += QString("AnonymizeDicomFileInPlace() called with no tags to change for [%1]. No operation to be done.").arg(file);
        return false;
    }

    const QByteArray fileBA = QFile::encodeName(file);

    DcmFileFormat fileformat;
    OFCondition status = fileformat.loadFile(fileBA.constData());
    if (status.bad()) {
        msg += QString("Not a readable DICOM file [%1]: %2").arg(file).arg(status.text());
        return false;
    }

    /* pixel data and other large elements are read lazily from the original file
       by default. Load everything now, because the original is about to be replaced */
    status = fileformat.loadAllDataIntoMemory();
    if (status.bad()) {
        msg += QString("Unable to load all data from [%1]: %2").arg(file).arg(status.text());
        return false;
    }

    const char *anonStr = "Anon";
    const char *anonDate = "19000101";
    const char *anonTime = "000000.000000";
    const char *anonDateTime = "19000101000000.000000";

    DcmDataset *dataset = fileformat.getDataset();
    QStringList errors;
    foreach (const DcmTagKey &key, tagsToChange) {
        DcmElement *elem = nullptr;
        if (dataset->findAndGetElement(key, elem, OFFalse /*searchIntoSub*/).bad() || (elem == nullptr))
            continue; /* tag not present - nothing to anonymize */

        OFCondition cond;
        switch (elem->ident()) {
            case EVR_SQ:
                cond = dataset->findAndDeleteElement(key);
                break;
            case EVR_DA:
                cond = dataset->putAndInsertString(key, anonDate);
                break;
            case EVR_TM:
                cond = dataset->putAndInsertString(key, anonTime);
                break;
            case EVR_DT:
                cond = dataset->putAndInsertString(key, anonDateTime);
                break;
            case EVR_PN:
            case EVR_LO:
            case EVR_SH:
            case EVR_ST:
            case EVR_LT:
            case EVR_UT:
            case EVR_UC:
            case EVR_CS:
                cond = dataset->putAndInsertString(key, anonStr);
                break;
            default:
                /* AS, DS, IS, binary, etc - "Anon" is not a valid value, so empty it */
                cond = dataset->putAndInsertString(key, "");
                break;
        }
        if (cond.bad())
            errors << QString("(%1,%2): %3").arg(key.getGroup(), 4, 16, QChar('0')).arg(key.getElement(), 4, 16, QChar('0')).arg(cond.text());
    }

    if (!errors.isEmpty()) {
        msg += QString("Unable to anonymize tags in [%1]: %2").arg(file).arg(errors.join("; "));
        return false;
    }

    /* save to a temp file, keeping the original transfer syntax, then replace the original */
    const QString tmpFile = file + ".anon.tmp";
    const QByteArray tmpBA = QFile::encodeName(tmpFile);
    status = fileformat.saveFile(tmpBA.constData(), EXS_Unknown);
    if (status.bad()) {
        QFile::remove(tmpFile);
        msg += QString("Unable to save anonymized file [%1]: %2").arg(tmpFile).arg(status.text());
        return false;
    }
    if (!QFile::remove(file) || !QFile::rename(tmpFile, file)) {
        QFile::remove(tmpFile);
        msg += QString("Unable to replace [%1] with anonymized file").arg(file);
        return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDicomDirInPlace ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Anonymize all DICOM files in a directory, in place
 * @param dir directory containing the DICOM files (files without a .dcm extension are included)
 * @param anonlevel 0 = none, 1 or 3 = partial (names and birthdate), 2 = full, 4 = patient name only
 * @param msg error/status messages
 * @return true if every DICOM file found was anonymized, false if any failed
 */
bool squirrelImageIO::AnonymizeDicomDirInPlace(QString dir, int anonlevel, QString &msg) {

    QList<DcmTagKey> tags;

    switch (anonlevel) {
    case 0: {
        msg += "No anonymization requested. Leaving files unchanged.";
        return true;
    }
    case 1:
    case 3: {
        /* partial anonymization - remove the obvious stuff like name and DOB */
        tags << DcmTagKey(0x0008, 0x0090)  // ReferringPhysicianName
             << DcmTagKey(0x0008, 0x1050)  // PerformingPhysicianName
             << DcmTagKey(0x0008, 0x1070)  // OperatorsName
             << DcmTagKey(0x0010, 0x0010)  // PatientName
             << DcmTagKey(0x0010, 0x0030); // PatientBirthDate
        break;
    }
    case 2: {
        /* Full anonymization. remove all names, dates, locations. ANYTHING identifiable */
        tags << DcmTagKey(0x0008, 0x0012)  // InstanceCreationDate
             << DcmTagKey(0x0008, 0x0013)  // InstanceCreationTime
             << DcmTagKey(0x0008, 0x0020)  // StudyDate
             << DcmTagKey(0x0008, 0x0021)  // SeriesDate
             << DcmTagKey(0x0008, 0x0022)  // AcquisitionDate
             << DcmTagKey(0x0008, 0x0023)  // ContentDate
             << DcmTagKey(0x0008, 0x0030)  // StudyTime
             << DcmTagKey(0x0008, 0x0031)  // SeriesTime
             << DcmTagKey(0x0008, 0x0032)  // AcquisitionTime
             << DcmTagKey(0x0008, 0x0033)  // ContentTime
             << DcmTagKey(0x0008, 0x0080)  // InstitutionName
             << DcmTagKey(0x0008, 0x0081)  // InstitutionAddress
             << DcmTagKey(0x0008, 0x0090)  // ReferringPhysicianName
             << DcmTagKey(0x0008, 0x0092)  // ReferringPhysicianAddress
             << DcmTagKey(0x0008, 0x0094)  // ReferringPhysicianTelephoneNumbers
             << DcmTagKey(0x0008, 0x0096)  // ReferringPhysicianIdentificationSequence
             << DcmTagKey(0x0008, 0x1010)  // StationName
             << DcmTagKey(0x0008, 0x1030)  // StudyDescription
             << DcmTagKey(0x0008, 0x103E)  // SeriesDescription
             << DcmTagKey(0x0008, 0x1048)  // PhysiciansOfRecord
             << DcmTagKey(0x0008, 0x1050)  // PerformingPhysicianName
             << DcmTagKey(0x0008, 0x1060)  // NameOfPhysiciansReadingStudy
             << DcmTagKey(0x0008, 0x1070)  // OperatorsName
             << DcmTagKey(0x0010, 0x0010)  // PatientName
             << DcmTagKey(0x0010, 0x0020)  // PatientID
             << DcmTagKey(0x0010, 0x0021)  // IssuerOfPatientID
             << DcmTagKey(0x0010, 0x0030)  // PatientBirthDate
             << DcmTagKey(0x0010, 0x0032)  // PatientBirthTime
             << DcmTagKey(0x0010, 0x0050)  // PatientInsurancePlanCodeSequence
             << DcmTagKey(0x0010, 0x1000)  // OtherPatientIDs
             << DcmTagKey(0x0010, 0x1001)  // OtherPatientNames
             << DcmTagKey(0x0010, 0x1005)  // PatientBirthName
             << DcmTagKey(0x0010, 0x1010)  // PatientAge
             << DcmTagKey(0x0010, 0x1020)  // PatientSize
             << DcmTagKey(0x0010, 0x1030)  // PatientWeight
             << DcmTagKey(0x0010, 0x1040)  // PatientAddress
             << DcmTagKey(0x0010, 0x1060)  // PatientMotherBirthName
             << DcmTagKey(0x0010, 0x2154)  // PatientTelephoneNumbers
             << DcmTagKey(0x0010, 0x21B0)  // AdditionalPatientHistory
             << DcmTagKey(0x0010, 0x21F0)  // PatientReligiousPreference
             << DcmTagKey(0x0010, 0x4000)  // PatientComments
             << DcmTagKey(0x0018, 0x1030)  // ProtocolName
             << DcmTagKey(0x0032, 0x1032)  // RequestingPhysician
             << DcmTagKey(0x0032, 0x1060)  // RequestedProcedureDescription
             << DcmTagKey(0x0040, 0x0006)  // ScheduledPerformingPhysicianName
             << DcmTagKey(0x0040, 0x0244)  // PerformedProcedureStepStartDate
             << DcmTagKey(0x0040, 0x0245)  // PerformedProcedureStepStartTime
             << DcmTagKey(0x0040, 0x0253)  // PerformedProcedureStepID
             << DcmTagKey(0x0040, 0x0254)  // PerformedProcedureStepDescription
             << DcmTagKey(0x0040, 0x4036)  // HumanPerformerOrganization
             << DcmTagKey(0x0040, 0x4037)  // HumanPerformerName
             << DcmTagKey(0x0040, 0xA123); // PersonName
        break;
    }
    case 4: {
        tags << DcmTagKey(0x0010, 0x0010); // PatientName
        break;
    }
    default: {
        msg += QString("Unknown anonymization level [%1]. Leaving files unchanged.").arg(anonlevel);
        return false;
    }
    }

    /* check every file - DICOM files frequently have no .dcm extension. Files
       that DCMTK can't read as DICOM are skipped */
    QStringList files = utils::FindAllFiles(dir, "*");
    int numAnon(0), numFailed(0), numSkipped(0);
    foreach (const QString &f, files) {
        if (f.endsWith(".anon.tmp"))
            continue;

        DcmFileFormat probe;
        if (probe.loadFileUntilTag(QFile::encodeName(f).constData(), EXS_Unknown, EGL_noChange, DCM_MaxReadLength, ERM_autoDetect, DCM_PixelData).bad()) {
            numSkipped++;
            continue;
        }
        /* require a SOP Class UID so non-DICOM files that happen to parse aren't rewritten */
        if (!probe.getDataset()->tagExists(DCM_SOPClassUID) && !probe.getMetaInfo()->tagExists(DCM_MediaStorageSOPClassUID)) {
            numSkipped++;
            continue;
        }

        QString m;
        if (AnonymizeDicomFileInPlace(f, tags, m))
            numAnon++;
        else {
            numFailed++;
            msg += m + '\n';
        }
    }

    msg += QString("Anonymized %1 DICOM files in [%2]. %3 failed, %4 non-DICOM files skipped").arg(numAnon).arg(dir).arg(numFailed).arg(numSkipped);

    return (numFailed == 0);
}


/* ------------------------------------------------- */
/* --------- GetDicomModality ---------------------- */
/* ------------------------------------------------- */
QString squirrelImageIO::GetDicomModality(QString f)
{
    QString modality = "NOTDICOM";
    QString exifoutput = Exiftool(f);
    QStringList lines = exifoutput.split(QRegularExpression("(\\n|\\r\\n|\\r)"), Qt::SkipEmptyParts);

    QHash<QString, QString> tags;
    foreach (QString line, lines) {
        QString delimiter = ":";
        qint64 index = line.indexOf(delimiter);

        if (index != -1) {
            QString firstPart = line.mid(0, index).replace(" ", "");
            QString secondPart = line.mid(index + delimiter.length());
            tags[firstPart.trimmed()] = secondPart.trimmed();
        }
    }
    if (tags.contains("Modality"))
        modality = tags["Modality"];

    return modality;
}


/* ---------------------------------------------------------- */
/* --------- GetImageFileTags ------------------------------- */
/* ---------------------------------------------------------- */
bool squirrelImageIO::GetImageFileTags(QString f, QHash<QString, QString> &tags, QString &msg) {

    tags.clear();

    /* check if the file exists and has read permissions */
    QFileInfo fi(f);
    if (!fi.exists()) {
        tags["FileExists"] = "false";
        msg += QString("File [%1] does not exist").arg(f);
        return false;
    }
    tags["FileExists"] = "true";
    if (!fi.isReadable()) {
        msg += QString("File [%1] does not have read permissions").arg(f);
        tags["FileHasReadPermissions"] = "false";
        return false;
    }

    tags["Filename"] = f;
    tags["Modality"] = "Unknown";
    tags["FileType"] = "Unknown";

    GetImageTagsDCMTK(f, tags);

    if (tags["FileType"] == "DICOM") {
        /* ---------- it's a readable DICOM file ---------- */
        msg += "dcmtk successfuly read file [" + f + "]\n";
    }
    else {
        /* ---------- not a DICOM file, so see what other type of file it may be ---------- */
        msg += QString("File [%1] is not a DICOM file").arg(f);

        /* check if EEG, and Polhemus */
        if ((f.endsWith(".cnt", Qt::CaseInsensitive)) || (f.endsWith(".dat", Qt::CaseInsensitive)) || (f.endsWith(".3dd", Qt::CaseInsensitive)) || (f.endsWith(".eeg", Qt::CaseInsensitive))) {
            tags["FileType"] = "EEG";
            tags["Modality"] = "EEG";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            if (parts.size() > 0)
                tags["PatientID"] = parts[0];
            else
                tags["PatientID"] = "Empty";
        }
        /* check if ET */
        else if (f.endsWith(".edf", Qt::CaseInsensitive)) {
            tags["FileType"] = "ET";
            tags["Modality"] = "ET";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            if (parts.size() > 0)
                tags["PatientID"] = parts[0];
            else
                tags["PatientID"] = "Empty";
        }
        /* check if MR (Non-DICOM) analyze or nifti */
        else if ((f.endsWith(".nii", Qt::CaseInsensitive)) || (f.endsWith(".nii.gz", Qt::CaseInsensitive)) || (f.endsWith(".hdr", Qt::CaseInsensitive)) || (f.endsWith(".img", Qt::CaseInsensitive))) {
            tags["FileType"] = "NIFTI";
            tags["Modality"] = "NIFTI";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            if (parts.size() > 0)
                tags["PatientID"] = parts[0];
            else
                tags["PatientID"] = "Empty";
        }
        /* check if par/rec */
        else if (f.endsWith(".par", Qt::CaseInsensitive) || f.endsWith(".rec", Qt::CaseInsensitive)) {
            tags["FileType"] = "PARREC";
            tags["Modality"] = "PARREC";

            /* read the header from the .par file (a .rec file's sibling .par is used) */
            parrec::ReadParFileTags(f, tags, msg);
        }
        else {
            /* unknown modality/filetype... so we'll try one last time to read with EXIF tool */
            QString exifoutput = Exiftool(f);
            QStringList lines = exifoutput.split(QRegularExpression("(\\n|\\r\\n|\\r)"), Qt::SkipEmptyParts);

            foreach (QString line, lines) {
                QString delimiter = ":";
                qint64 index = line.indexOf(delimiter);

                if (index != -1) {
                    QString firstPart = line.mid(0, index).replace(" ", "");
                    QString secondPart = line.mid(index + delimiter.length());
                    tags[firstPart.trimmed()] = secondPart.trimmed();
                }
            }

            if (tags["FileType"] != "DICOM")
                return false;
        }
    }

    /* fix some of the fields to be amenable to the DB */
    if (tags["Modality"] == "")
        tags["Modality"] = "OT";
    QString StudyDate = utils::ParseDate(tags["StudyDate"]);

    /* fix the study date */
    tags["StudyDate"].replace(":", "-");
    if (tags["StudyDate"] == "") {
        tags["StudyDate"] = "0000-00-00";
    }
    else {
        tags["StudyDate"].replace("/","-");
        if (tags["StudyDate"].size() == 8) {
            tags["StudyDate"].insert(6,'-');
            tags["StudyDate"].insert(4,'-');
        }
    }

    /* fix the series date */
    tags["SeriesDate"].replace(":", "-");
    if (tags["SeriesDate"] == "")
        tags["SeriesDate"] = tags["StudyDate"];
    else {
        tags["SeriesDate"].replace("/","-");
        if (tags["SeriesDate"].size() == 8) {
            tags["SeriesDate"].insert(6,'-');
            tags["SeriesDate"].insert(4,'-');
        }
    }

    /* fix the study time */
    if (tags["StudyTime"] == "") {
        tags["StudyTime"] = "00:00:00";
    }
    else {
        if (tags["StudyTime"].contains(":")) {
            tags["StudyTime"] = tags["StudyTime"].left(8);
        }
        else if (tags["StudyTime"].contains(".")) {
            tags["StudyTime"] = tags["StudyTime"].left(6);
        }
        else {
            utils::Print("StudyTime is not a recognized format [" + tags["StudyTime"] + "]");
        }

        if (tags["StudyTime"].size() == 6) {
            tags["StudyTime"].insert(4,':');
            tags["StudyTime"].insert(2,':');
        }
    }

    /* some images may not have a series date/time, so substitute the studyDateTime for seriesDateTime */
    if (tags["SeriesTime"] == "")
        tags["SeriesTime"] = tags["StudyTime"];
    else {
        if (tags["SeriesTime"].contains(":")) {
            tags["SeriesTime"] = tags["SeriesTime"].left(8);
        }
        else if (tags["SeriesTime"].contains(".")) {
            tags["SeriesTime"] = tags["SeriesTime"].left(6);
        }
        else {
            utils::Print("SeriesTime is not a recognized format [" + tags["SeriesTime"] + "]");
        }

        if (tags["SeriesTime"].size() == 6) {
            tags["SeriesTime"].insert(4,':');
            tags["SeriesTime"].insert(2,':');
        }
    }

    tags["StudyDateTime"] = tags["StudyDate"] + " " + tags["StudyTime"];
    tags["SeriesDateTime"] = tags["SeriesDate"] + " " + tags["SeriesTime"];

    /* fix the birthdate */
    if (tags["PatientBirthDate"] == "") tags["PatientBirthDate"] = "0001-01-01";
    tags["PatientBirthDate"].replace("/","-");
    if (tags["PatientBirthDate"].size() == 8) {
        tags["PatientBirthDate"].insert(6,'-');
        tags["PatientBirthDate"].insert(4,'-');
    }

    /* check for other undefined or blank fields */
    if (tags["PatientSex"] == "") tags["PatientSex"] = 'U';
    if (tags["StationName"] == "") tags["StationName"] = "Unknown";
    if (tags["InstitutionName"] == "") tags["InstitutionName"] = "Unknown";
    if (tags["SeriesNumber"] == "") {
        QString timestamp = tags["SeriesTime"];
        timestamp.remove(':').remove('-').remove(' ');
        tags["SeriesNumber"] = timestamp;
    }

    QStringList amat = tags["AcquisitionMatrix"].split(" ");
    if (amat.size() == 4) {
        tags["mat1"] = amat[0];
        tags["mat4"] = amat[3];
    }

    /* fix patient birthdate */
    QString PatientBirthDate = utils::ParseDate(tags["PatientBirthDate"]);

    /* get patient age */
    tags["PatientAge"] = QString("%1").arg(utils::GetPatientAge(tags["PatientAge"], StudyDate, PatientBirthDate));

    /* remove any non-printable ASCII control characters */
    tags["PatientName"].replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"").replace("\\xFFFD","");
    tags["PatientSex"].replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"").replace("\\xFFFD","");

    if (tags["PatientID"] == "")
        tags["PatientID"] = "(empty)";

    msg += "GetImageFileTags() checkpoint G\n";

    /* get parent directory of this file */
    QFileInfo finfo(f);
    QDir d = finfo.dir();
    QString dirname = d.dirName().trimmed();
    tags["ParentDirectory"] = dirname;

    if (tags["PatientName"] == "")
        tags["PatientName"] = tags["PatientID"];

    if (tags["StudyDescription"] == "")
        tags["StudyDescription"] = "(empty)";

    if (tags["PatientSex"] == "")
        tags["PatientName"] = "U";

    QString uniqueseries = tags["InstitutionName"] + tags["StationName"] + tags["Modality"] + tags["PatientName"] + tags["PatientBirthDate"] + tags["PatientSex"] + tags["StudyDateTime"] + tags["SeriesNumber"];
    tags["UniqueSeriesString"] = uniqueseries;

    msg += uniqueseries + "\n";

    return true;
}


/* ------------------------------------------------- */
/* --------- GetFileType --------------------------- */
/* ------------------------------------------------- */
void squirrelImageIO::GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol)
{
    fileModality = QString("");

    /* read file with EXIF tool */
    QString exifoutput = Exiftool(f);
    QStringList lines = exifoutput.split(QRegularExpression("(\\n|\\r\\n|\\r)"), Qt::SkipEmptyParts);
    QHash<QString, QString> tags;
    foreach (QString line, lines) {
        QString delimiter = ":";
        qint64 index = line.indexOf(delimiter);

        if (index != -1) {
            QString firstPart = line.mid(0, index).replace(" ", "");
            QString secondPart = line.mid(index + delimiter.length());
            tags[firstPart.trimmed()] = secondPart.trimmed();
        }
    }

    if (tags["FileType"] == "DICOM") {

        if (tags.contains("Modality"))
            fileModality = tags["Modality"];

        if (tags.contains("PatientID"))
            filePatientID = tags["PatientID"];

        if (tags.contains("SeriesDescription"))
            fileProtocol = tags["SeriesDescription"];
    }
    else {
        //WriteLog("[" + f + "] is not a DICOM file");
        /* check if EEG, and Polhemus */
        if ((f.endsWith(".cnt", Qt::CaseInsensitive)) || (f.endsWith(".dat", Qt::CaseInsensitive)) || (f.endsWith(".3dd", Qt::CaseInsensitive)) || (f.endsWith(".eeg", Qt::CaseInsensitive))) {
            //WriteLog("Found an EEG file [" + f + "]");
            fileType = "EEG";
            fileModality = "EEG";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
            if (parts.size() > 0)
                filePatientID = parts[0];
            else
                filePatientID = "Empty";
        }
        /* check if ET */
        else if (f.endsWith(".edf", Qt::CaseInsensitive)) {
            //WriteLog("Found an ET file [" + f + "]");
            fileType = "ET";
            fileModality = "ET";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
            if (parts.size() > 0)
                filePatientID = parts[0];
            else
                filePatientID = "Empty";
        }
        /* check if MR (Non-DICOM) analyze or nifti */
        else if ((f.endsWith(".nii", Qt::CaseInsensitive)) || (f.endsWith(".nii.gz", Qt::CaseInsensitive)) || (f.endsWith(".hdr", Qt::CaseInsensitive)) || (f.endsWith(".img", Qt::CaseInsensitive))) {
            //WriteLog("Found an analyze or Nifti image [" + f + "]");
            fileType = "NIFTI";
            fileModality = "NIFTI";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
            if (parts.size() > 0)
                filePatientID = parts[0];
            else
                filePatientID = "Empty";
        }
        /* check if par/rec */
        else if (f.endsWith(".par", Qt::CaseInsensitive)) {
            //WriteLog("Found a PARREC image [" + f + "]");
            fileType = "PARREC";
            fileModality = "PARREC";

            QFile inputFile(f);
            if (inputFile.open(QIODevice::ReadOnly))
            {
                QTextStream in(&inputFile);
                while ( !in.atEnd() )
                {
                    QString line = in.readLine();
                    if (line.contains("Patient name")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        if (parts.size() > 1)
                            filePatientID = parts[1].trimmed();
                    }
                    if (line.contains("Protocol name")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        if (parts.size() > 1)
                            fileProtocol = parts[1].trimmed();
                    }
                    if (line.contains("MRSERIES", Qt::CaseInsensitive)) {
                        fileModality = "MR";
                    }
                }
                inputFile.close();
            }
        }
        else {
            //WriteLog("Filetype is unknown [" + f + "]");
            fileType = "Unknown";
        }
    }
}
