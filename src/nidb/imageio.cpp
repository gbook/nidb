/* ------------------------------------------------------------------------------
  NIDB imageio.cpp
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

#include "imageio.h"
#include <QRegularExpression>


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
/* --------- imageIO ---------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * imageIO constructor
*/
imageIO::imageIO(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~imageIO --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * imageIO destructor
*/
imageIO::~imageIO()
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
QString imageIO::Exiftool(QString arg) {
    QString str;

    QFileInfo fileInfo(arg);
    QString filename = fileInfo.fileName();

    /* ----- check one more time to see if everything was ok ----- */
    QString systemstring = "exiftool " + arg;
    str = SystemCommand(systemstring, false);

    /* check if the output is not truncated or cut off */
    if (str.size() < 50) {
        Print(n->Log(QString("*** Exiftool output from file [%1] is ONLY [%2] bytes. str contains [%3] ***").arg(arg).arg(str.size()).arg(str)));
        str = "";
    }
    /* check if the output contains the filename passed to exiftool  */
    else if (!str.contains(filename.trimmed(), Qt::CaseInsensitive)) {
        Print(n->Log(QString("*** Exiftool output from file [%1] does NOT contain the file name [%2]. size is [%3] bytes. str is [%4] ***").arg(arg).arg(filename).arg(str.size()).arg(str)));
        str = "";
    }
    /* check if the str is blank */
    else if (str == "") {
        Print(n->Log(QString("*** Exiftool output from file [%1] is empty ***").arg(arg)));
        str = "";
    }

    return str;
}


/* ---------------------------------------------------------- */
/* --------- GetImageTagsDCMTK ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief imageIO::GetImageTagsDCMTK
 * @param f
 * @param tags
 * @param msg
 * @return
 */
bool imageIO::GetImageTagsDCMTK(QString f, QHash<QString, QString> &tags) {

    QStringList msgs;

    tags["FilePath"] = f;

    const char* filename = f.toLatin1();

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
                            //n->Log(QString("CSA %1 = [%2]").arg(name).arg(val));

                            /* only add the tag if it does not already exist */
                            if (!tags.contains(name))
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
                        //std::string cstr = strValue.c_str();
                        //QString val = QString(cstr.c_str());
                        //n->Log(QString("%1 = [%2] [%3] [%4]").arg(tagName).arg(strValue.c_str()).arg(cstr).arg(val));
                        //qDebug() << tagName << " = [" << strValue.c_str() << "] [" << val << "]";
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
double imageIO::csaToDouble(const QByteArray& v)
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
int imageIO::csaToInteger(const QByteArray& v)
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
QString imageIO::csaToString(const QByteArray& v)
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
QMap<QString, CsaElement> imageIO::ParseSiemensCSA(const QByteArray& csa)
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

    for (int t = 0; t < nTags; ++t)
    {
        char nameBuf[64];
        stream.readRawData(nameBuf, 64);
        QString name = QString(QByteArray(nameBuf));

        qint32 vm;
        stream >> vm;

        char vrBuf[4];
        stream.readRawData(vrBuf, 4);
        QString vr = QString::fromLatin1(vrBuf).trimmed();

        qint32 syngo_dt;
        qint32 nItems;
        qint32 unused;

        stream >> syngo_dt >> nItems >> unused;

        CsaElement element;
        element.name = name;
        element.vr = vr;

        for (int i = 0; i < nItems; ++i)
        {
            qint32 itemLen[4];
            stream.readRawData(reinterpret_cast<char*>(itemLen), 16);

            int len = itemLen[1];   // actual length

            QByteArray value(len, Qt::Uninitialized);
            if (len > 0)
                stream.readRawData(value.data(), len);

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
/**
 * @brief This function acts as a wrapper for dcm2niix to convert DICOM to Nifti. The handles temporary directories and also renames files into NiDB format filenames
 * @param filetype Output file type: `niftime`, `nifti3d`, `nifti4d`, `bids`
 * @param indir Input directory
 * @param outdir Output directory
 * @param bindir Directory containing the dcm2niix binary
 * @param gzip true to gzip the files, false otherwise
 * @param json true to output JSON sidecar, false otherwise
 * @param uid UID of the images (used in renaming the files)
 * @param studynum Study number of the images (used in renaming the files)
 * @param seriesnum Series number of the images (used in renaming the files)
 * @param bidsSubject **BIDS** BIDS subject label
 * @param bidsSession **BIDS** BIDS session label
 * @param bidsMapping **BIDS** structure containing mapping of NiDB series desc to BIDS format
 * @param datatype if 'parrec', this function will handle conversion slightly differently
 * @param numfilesconv Number of files converted (doesn't work correctly)
 * @param numfilesrenamed Number of files renamed
 * @param msg Any messages generated during converstion
 * @return `true` if successful, `false` otherwise
 */
bool imageIO::ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, bool json, QString uid, QString studynum, QString seriesnum, QString bidsSubject, QString bidsSession, BIDSMapping bidsMapping, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg) {

    QStringList msgs;

    QString pwd = QDir::currentPath();

    QString gzipstr;
    if (gzip) gzipstr = "-z y";
    else gzipstr = "-z n";

    QString jsonstr;
    if (json) jsonstr = "y";
    else jsonstr = "n";

    numfilesconv = 0; /* need to fix this to be correct at some point */

    msgs << QString("Working on [" + indir + "] and filetype [" + filetype + "]");

    /* in case of par/rec, the argument list to dcm2niix is a file instead of a directory */
    QString fileext = "";
    if (datatype == "parrec")
        fileext = "/*.par";

    /* do the conversion */
    QString systemstring;
    QDir::setCurrent(indir);
    if (filetype == "nifti4dme") {
        systemstring = QString("%1/./dcm2niixme %2 -o '%3' %4").arg(bindir).arg(gzipstr).arg(outdir).arg(indir);
    }
    else if (filetype == "nifti4d") {
        systemstring = QString("%1/./dcm2niix -1 -b %6 %2 -o '%3' %4%5").arg(bindir).arg(gzipstr).arg(outdir).arg(indir).arg(fileext).arg(jsonstr);
    }
    else if (filetype == "nifti3d") {
        //Print("Checkpoing D-3");
        systemstring = QString("%1/./dcm2niix -1 -b %5 -z 3 -o '%2' %3%4").arg(bindir).arg(outdir).arg(indir).arg(fileext).arg(jsonstr);
    }
    else if (filetype == "bids") {
        //Print("Checkpoing D-4");
        systemstring = QString("%1/./dcm2niix -1 -b y -z y -o '%2' %3%4").arg(bindir).arg(outdir).arg(indir).arg(fileext);
    }
    else {
        msgs << "Invalid export filetype [" + filetype + "]";
        msg = msgs.join("\n");
        return false;
    }

    /* create the output directory */
    QString m;
    if (!MakePath(outdir, m)) {
        msgs << "Unable to create path [" + outdir + "] because of error [" + m + "]";
        msg = msgs.join("\n");
        return false;
    }

    /* remove any files that may already be in the output directory.. for example, an incomplete series was put in the output directory */
    if ((outdir != "") && (outdir != "/") ) {
        QString systemstring2 = QString("rm -f %1/*.hdr %1/*.img %1/*.nii %1/*.gz").arg(outdir);
        msgs << SystemCommand(systemstring2, true, true);

        /* execute the command created above */
        msgs << SystemCommand(systemstring, true, true);
    }
    else {
        msgs << "Invalid output directory [" + outdir + "]";
        msg = msgs.join("\n");
        return false;
    }

    /* conversion should be done, so check if it actually gzipped the file */
    if ((gzip) && (filetype != "bids")) {
        systemstring = "cd " + outdir + "; gzip *.nii";
        msgs << SystemCommand(systemstring, true);
    }

    /* rename the files into something meaningful */
    m = "";
    if (filetype == "bids") {
        BatchRenameBIDSFiles(outdir, bidsSubject, bidsSession, bidsMapping, numfilesrenamed, m);
    }
    else {
        BatchRenameFiles(outdir, seriesnum, studynum, uid, numfilesrenamed, m);
    }
    msgs << "Renamed output files [" + m + "]";

    /* change back to original directory before leaving */
    QDir::setCurrent(pwd);

    msg = msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- IsDICOMFile ------------------------------------ */
/* ---------------------------------------------------------- */
bool imageIO::IsDICOMFile(QString f) {
    /* check if its really a dicom file... */
    /* try reading with exiftool */
    QHash<QString, QString> tags;
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
    else
        return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDICOMFile ----------------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
 bool imageIO::AnonymizeDicomFile(QString infile, QString outfile, QString &msg) {

    const char *anonStr = "Anon";
    const char *anonDate = "10000101";
    //const char *anonTime = "000000.000000";

    DcmFileFormat fileformat;

    OFFilename ifile = infile.toStdString().c_str();
    OFFilename ofile = outfile.toStdString().c_str();
    OFCondition status = fileformat.loadFile(ifile);

    if (status.good()) {
        DcmDataset *dataset = fileformat.getDataset();

        /* partial anonmymization - remove the obvious stuff like name and DOB */
        if (dataset->tagExists(DCM_ReferringPhysicianName)) { dataset->putAndInsertString(DCM_ReferringPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ReferringPhysicianName]\n"; }
        if (dataset->tagExists(DCM_PerformingPhysicianName)) { dataset->putAndInsertString(DCM_PerformingPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PerformingPhysicianName]\n"; }
        if (dataset->tagExists(DCM_OperatorsName)) { dataset->putAndInsertString(DCM_OperatorsName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_OperatorsName]\n"; }
        if (dataset->tagExists(DCM_PatientName)) { dataset->putAndInsertString(DCM_PatientName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientName]\n"; }
        if (dataset->tagExists(DCM_PatientBirthDate)) { dataset->putAndInsertString(DCM_PatientBirthDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_PatientBirthDate]\n"; }

        status = fileformat.saveFile(ofile, dataset->getOriginalXfer());
        if (status.good())
            std::cout << "Tag modified successfully." << std::endl;
        else
            std::cerr << "Error: Cannot save file (" << status.text() << ")" << std::endl;
    }
    else {
        std::cerr << "Error: Cannot load DICOM file (" << status.text() << ")" << std::endl;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDicomFileInPlace ---------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
bool imageIO::AnonymizeDicomFileInPlace(QString file, QStringList tagsToChange, QString &msg)
{
    if( tagsToChange.isEmpty() ) {
        msg += "AnonymizeDICOMFile() called with no tags to change. No operation to be done.";
        return false;
    }

    /* do the command line anonymization */
    int i(0);
    QStringList subsetTags;
    foreach (const QString &str, tagsToChange) {
        if (i > 10) {
            QString systemstring = QString("gdcmanon --dumb -i %1 -o %2 %3").arg(file).arg(subsetTags.join(" "));
            QString output = SystemCommand(systemstring, false);
            msg += output;
            i=0;
            subsetTags.clear();
        }
        subsetTags.append(str);
        i++;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDir ----------------------------------- */
/* ---------------------------------------------------------- */
bool imageIO::AnonymizeDir(QString indir, QString outdir, int anonlevel, QString &msg) {


    // gdcmanon --dumb -i /path/to/dicom --replace 10,10=Anonymous -o /path/to/output <-- output will be created if it doesn't exist

    QString anonStr = "Anon";
    QString anonDate = "19000101";
    QString anonTime = "000000.000000";

    QStringList cmdArgs;

    switch (anonlevel) {
        case 0: {
            msg += "No anonymization requested. Leaving files unchanged.";
            return 0;
        }
        case 1:
        case 3: {
            /* partial anonmymization - remove the obvious stuff like name and DOB */
            cmdArgs.append(QString("--replace 8,90='%1'").arg(anonStr)); // ReferringPhysicianName
            cmdArgs.append(QString("--replace 8,1050='%1'").arg(anonStr)); // PerformingPhysicianName
            cmdArgs.append(QString("--replace 8,1070='%1'").arg(anonStr)); // OperatorsName
            cmdArgs.append(QString("--replace 10,10='%1'").arg(anonStr)); // PatientName
            cmdArgs.append(QString("--replace 10,30='%1'").arg(anonStr)); // PatientBirthDate

            break;
        }
        case 2: {
            /* Full anonymization. remove all names, dates, locations. ANYTHING identifiable */
            cmdArgs.append(QString("--replace 8,12='%1'").arg(anonDate)); // InstanceCreationDate
            cmdArgs.append(QString("--replace 8,13='%1'").arg(anonDate)); // InstanceCreationTime
            cmdArgs.append(QString("--replace 8,20='%1'").arg(anonDate)); // StudyDate
            cmdArgs.append(QString("--replace 8,21='%1'").arg(anonDate)); // SeriesDate
            cmdArgs.append(QString("--replace 8,22='%1'").arg(anonDate)); // AcquisitionDate
            cmdArgs.append(QString("--replace 8,23='%1'").arg(anonDate)); // ContentDate
            cmdArgs.append(QString("--replace 8,30='%1'").arg(anonTime)); //StudyTime
            cmdArgs.append(QString("--replace 8,31='%1'").arg(anonTime)); //SeriesTime
            cmdArgs.append(QString("--replace 8,32='%1'").arg(anonTime)); //AcquisitionTime
            cmdArgs.append(QString("--replace 8,33='%1'").arg(anonTime)); //ContentTime
            cmdArgs.append(QString("--replace 8,80='%1'").arg(anonStr)); // InstitutionName
            cmdArgs.append(QString("--replace 8,81='%1'").arg(anonStr)); // InstitutionAddress
            cmdArgs.append(QString("--replace 8,90='%1'").arg(anonStr)); // ReferringPhysicianName
            cmdArgs.append(QString("--replace 8,92='%1'").arg(anonStr)); // ReferringPhysicianAddress
            cmdArgs.append(QString("--replace 8,94='%1'").arg(anonStr)); // ReferringPhysicianTelephoneNumber
            cmdArgs.append(QString("--replace 8,96='%1'").arg(anonStr)); // ReferringPhysicianIDSequence
            cmdArgs.append(QString("--replace 8,1010='%1'").arg(anonStr)); // StationName
            cmdArgs.append(QString("--replace 8,1030='%1'").arg(anonStr)); // StudyDescription
            cmdArgs.append(QString("--replace 8,103E='%1'").arg(anonStr)); // SeriesDescription
            cmdArgs.append(QString("--replace 8,1048='%1'").arg(anonStr)); // PhysiciansOfRecord
            cmdArgs.append(QString("--replace 8,1050='%1'").arg(anonStr)); // PerformingPhysicianName
            cmdArgs.append(QString("--replace 8,1060='%1'").arg(anonStr)); // NameOfPhysicianReadingStudy
            cmdArgs.append(QString("--replace 8,1070='%1'").arg(anonStr)); // OperatorsName

            cmdArgs.append(QString("--replace 10,10='%1'").arg(anonStr)); // PatientName
            cmdArgs.append(QString("--replace 10,20='%1'").arg(anonStr)); // PatientID
            cmdArgs.append(QString("--replace 10,21='%1'").arg(anonStr)); // IssuerOfPatientID
            cmdArgs.append(QString("--replace 10,30='%1'").arg(anonDate)); // PatientBirthDate
            cmdArgs.append(QString("--replace 10,32='%1'").arg(anonTime)); // PatientBirthTime
            cmdArgs.append(QString("--replace 10,50='%1'").arg(anonStr)); // PatientInsurancePlanCodeSequence
            cmdArgs.append(QString("--replace 10,1000='%1'").arg(anonStr)); // OtherPatientIDs
            cmdArgs.append(QString("--replace 10,1001='%1'").arg(anonStr)); // OtherPatientNames
            cmdArgs.append(QString("--replace 10,1005='%1'").arg(anonStr)); // PatientBirthName
            cmdArgs.append(QString("--replace 10,1010='%1'").arg(anonStr)); // PatientAge
            cmdArgs.append(QString("--replace 10,1020='%1'").arg(anonStr)); // PatientSize
            cmdArgs.append(QString("--replace 10,1030='%1'").arg(anonStr)); // PatientWeight
            cmdArgs.append(QString("--replace 10,1040='%1'").arg(anonStr)); // PatientAddress
            cmdArgs.append(QString("--replace 10,1060='%1'").arg(anonStr)); // PatientMotherBirthName
            cmdArgs.append(QString("--replace 10,2154='%1'").arg(anonStr)); // PatientTelephoneNumbers
            cmdArgs.append(QString("--replace 10,21B0='%1'").arg(anonStr)); // AdditionalPatientHistory
            cmdArgs.append(QString("--replace 10,21F0='%1'").arg(anonStr)); // PatientReligiousPreference
            cmdArgs.append(QString("--replace 10,4000='%1'").arg(anonStr)); // PatientComments

            cmdArgs.append(QString("--replace 18,1030='%1'").arg(anonStr)); // ProtocolName

            cmdArgs.append(QString("--replace 32,1032='%1'").arg(anonStr)); // RequestingPhysician
            cmdArgs.append(QString("--replace 32,1060='%1'").arg(anonStr)); // RequestedProcedureDescription

            cmdArgs.append(QString("--replace 40,6='%1'").arg(anonStr)); // ScheduledPerformingPhysiciansName
            cmdArgs.append(QString("--replace 40,244='%1'").arg(anonDate)); // PerformedProcedureStepStartDate
            cmdArgs.append(QString("--replace 40,245='%1'").arg(anonTime)); // PerformedProcedureStepStartTime
            cmdArgs.append(QString("--replace 40,253='%1'").arg(anonStr)); // PerformedProcedureStepID
            cmdArgs.append(QString("--replace 40,254='%1'").arg(anonStr)); // PerformedProcedureStepDescription
            cmdArgs.append(QString("--replace 40,4036='%1'").arg(anonStr)); // HumanPerformerOrganization
            cmdArgs.append(QString("--replace 40,4037='%1'").arg(anonStr)); // HumanPerformerName
            cmdArgs.append(QString("--replace 40,A123='%1'").arg(anonStr)); // PersonName

            break;
        }
        case 4: {
            cmdArgs.append(QString("--replace 10,10='%1'").arg(anonStr));
            break;
        }
        default: {
            break;
        }
    }

    /* do the command line anonymization */
    QString systemstring = QString("gdcmanon --dumb --continue %1 -i %2 -o %3").arg(cmdArgs.join(" ")).arg(indir).arg(outdir);
    n->Log(systemstring);
    QString output = SystemCommand(systemstring, true);
    n->Log(output);
    msg += output;

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDicomDirInPlace ----------------------- */
/* ---------------------------------------------------------- */
bool imageIO::AnonymizeDicomDir(QString indir, QString outdir, int anonlevel, QString &msg) {

    const char *anonStr = "Anon";
    const char *anonDate = "10000101";
    const char *anonTime = "000000.000000";


    QStringList files = FindAllFiles(indir, "*");

    foreach (QString inFilePath, files) {
        QFileInfo fi(inFilePath);
        QString filename = fi.fileName();
        if (outdir.endsWith("/")) { outdir.chop(1); }
        QString outFilePath = outdir + "/" + filename;

        /* convert from QString to dcmtk string format */
        OFFilename dcmInFile = inFilePath.toStdString().c_str();
        OFFilename dcmOutFile = outFilePath.toStdString().c_str();
        DcmFileFormat fileformat;
        OFCondition status = fileformat.loadFile(dcmInFile);
        fileformat.loadAllDataIntoMemory();

        if (status.good()) {
            DcmDataset *dataset = fileformat.getDataset();
            //qDebug() << "Original TS:" << DcmXfer(dataset->getOriginalXfer()).getXferName();
            switch (anonlevel) {
                case 0: {
                    msg += "No anonymization requested. Leaving files unchanged.";
                    return 0;
                }
                case 1:
                case 3: {
                    /* partial anonmymization - remove the obvious stuff like name and DOB */
                    if (dataset->tagExists(DCM_ReferringPhysicianName)) { dataset->putAndInsertString(DCM_ReferringPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ReferringPhysicianName]\n"; }
                    if (dataset->tagExists(DCM_PerformingPhysicianName)) { dataset->putAndInsertString(DCM_PerformingPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PerformingPhysicianName]\n"; }
                    if (dataset->tagExists(DCM_OperatorsName)) { dataset->putAndInsertString(DCM_OperatorsName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_OperatorsName]\n"; }
                    if (dataset->tagExists(DCM_PatientName)) { dataset->putAndInsertString(DCM_PatientName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientName]\n"; }
                    if (dataset->tagExists(DCM_PatientBirthDate)) { dataset->putAndInsertString(DCM_PatientBirthDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_PatientBirthDate]\n"; }
                    break;
                }
                case 2: {
                    /* Full anonymization. remove all names, dates, locations. ANYTHING identifiable */
                    if (dataset->tagExists(DCM_InstanceCreationDate)) { dataset->putAndInsertString(DCM_InstanceCreationDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_InstanceCreationDate]\n"; }
                    if (dataset->tagExists(DCM_InstanceCreationTime)) { dataset->putAndInsertString(DCM_InstanceCreationTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_InstanceCreationTime]\n"; }
                    if (dataset->tagExists(DCM_StudyDate)) { dataset->putAndInsertString(DCM_StudyDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_StudyDate]\n"; }
                    if (dataset->tagExists(DCM_SeriesDate)) { dataset->putAndInsertString(DCM_SeriesDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_SeriesDate]\n"; }
                    if (dataset->tagExists(DCM_AcquisitionDate)) { dataset->putAndInsertString(DCM_AcquisitionDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_AcquisitionDate]\n"; }
                    if (dataset->tagExists(DCM_ContentDate)) { dataset->putAndInsertString(DCM_ContentDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_ContentDate]\n"; }
                    if (dataset->tagExists(DCM_StudyTime)) { dataset->putAndInsertString(DCM_StudyTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_StudyTime]\n"; }
                    if (dataset->tagExists(DCM_SeriesTime)) { dataset->putAndInsertString(DCM_SeriesTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_SeriesTime]\n"; }
                    if (dataset->tagExists(DCM_AcquisitionTime)) { dataset->putAndInsertString(DCM_AcquisitionTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_AcquisitionTime]\n"; }
                    if (dataset->tagExists(DCM_ContentTime)) { dataset->putAndInsertString(DCM_ContentTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_ContentTime]\n"; }
                    if (dataset->tagExists(DCM_InstitutionName)) { dataset->putAndInsertString(DCM_InstitutionName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_InstitutionName]\n"; }
                    if (dataset->tagExists(DCM_InstitutionAddress)) { dataset->putAndInsertString(DCM_InstitutionAddress, anonStr); if (status.bad()) msg += "Error changing tag [DCM_InstitutionAddress]\n"; }
                    if (dataset->tagExists(DCM_ReferringPhysicianName)) { dataset->putAndInsertString(DCM_ReferringPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ReferringPhysicianName]\n"; }
                    if (dataset->tagExists(DCM_ReferringPhysicianAddress)) { dataset->putAndInsertString(DCM_ReferringPhysicianAddress, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ReferringPhysicianAddress]\n"; }
                    if (dataset->tagExists(DCM_ReferringPhysicianTelephoneNumbers)) { dataset->putAndInsertString(DCM_ReferringPhysicianTelephoneNumbers, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ReferringPhysicianTelephoneNumbers]\n"; }
                    if (dataset->tagExists(DCM_ReferringPhysicianIdentificationSequence)) { dataset->putAndInsertString(DCM_ReferringPhysicianIdentificationSequence, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ReferringPhysicianIdentificationSequence]\n"; }
                    if (dataset->tagExists(DCM_StationName)) { dataset->putAndInsertString(DCM_StationName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_StationName]\n"; }
                    if (dataset->tagExists(DCM_StudyDescription)) { dataset->putAndInsertString(DCM_StudyDescription, anonStr); if (status.bad()) msg += "Error changing tag [DCM_StudyDescription]\n"; }
                    if (dataset->tagExists(DCM_SeriesDescription)) { dataset->putAndInsertString(DCM_SeriesDescription, anonStr); if (status.bad()) msg += "Error changing tag [DCM_SeriesDescription]\n"; }
                    if (dataset->tagExists(DCM_PhysiciansOfRecord)) { dataset->putAndInsertString(DCM_PhysiciansOfRecord, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PhysiciansOfRecord]\n"; }
                    if (dataset->tagExists(DCM_PerformingPhysicianName)) { dataset->putAndInsertString(DCM_PerformingPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PerformingPhysicianName]\n"; }
                    if (dataset->tagExists(DCM_NameOfPhysiciansReadingStudy)) { dataset->putAndInsertString(DCM_NameOfPhysiciansReadingStudy, anonStr); if (status.bad()) msg += "Error changing tag [DCM_NameOfPhysiciansReadingStudy]\n"; }
                    if (dataset->tagExists(DCM_OperatorsName)) { dataset->putAndInsertString(DCM_OperatorsName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_OperatorsName]\n"; }

                    if (dataset->tagExists(DCM_PatientName)) { dataset->putAndInsertString(DCM_PatientName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientName]\n"; }
                    if (dataset->tagExists(DCM_PatientID)) { dataset->putAndInsertString(DCM_PatientID, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientID]\n"; }
                    if (dataset->tagExists(DCM_IssuerOfPatientID)) { dataset->putAndInsertString(DCM_IssuerOfPatientID, anonStr); if (status.bad()) msg += "Error changing tag [DCM_IssuerOfPatientID]\n"; }
                    if (dataset->tagExists(DCM_PatientBirthDate)) { dataset->putAndInsertString(DCM_PatientBirthDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_PatientBirthDate]\n"; }
                    if (dataset->tagExists(DCM_PatientBirthTime)) { dataset->putAndInsertString(DCM_PatientBirthTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_PatientBirthTime]\n"; }
                    if (dataset->tagExists(DCM_PatientInsurancePlanCodeSequence)) { dataset->putAndInsertString(DCM_PatientInsurancePlanCodeSequence, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientInsurancePlanCodeSequence]\n"; }
                    if (dataset->tagExists(DCM_OtherPatientIDsSequence)) { dataset->putAndInsertString(DCM_OtherPatientIDsSequence, anonStr); if (status.bad()) msg += "Error changing tag [DCM_OtherPatientIDsSequence]\n"; }
                    if (dataset->tagExists(DCM_OtherPatientNames)) { dataset->putAndInsertString(DCM_OtherPatientNames, anonStr); if (status.bad()) msg += "Error changing tag [DCM_OtherPatientNames]\n"; }
                    if (dataset->tagExists(DCM_PatientBirthName)) { dataset->putAndInsertString(DCM_PatientBirthName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientBirthName]\n"; }
                    if (dataset->tagExists(DCM_PatientAge)) { dataset->putAndInsertString(DCM_PatientAge, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientAge]\n"; }
                    if (dataset->tagExists(DCM_PatientSize)) { dataset->putAndInsertString(DCM_PatientSize, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientSize]\n"; }
                    if (dataset->tagExists(DCM_PatientWeight)) { dataset->putAndInsertString(DCM_PatientWeight, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientWeight]\n"; }
                    if (dataset->tagExists(DCM_PatientAddress)) { dataset->putAndInsertString(DCM_PatientAddress, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientAddress]\n"; }
                    if (dataset->tagExists(DCM_PatientMotherBirthName)) { dataset->putAndInsertString(DCM_PatientMotherBirthName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientMotherBirthName]\n"; }
                    if (dataset->tagExists(DCM_PatientTelephoneNumbers)) { dataset->putAndInsertString(DCM_PatientTelephoneNumbers, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientTelephoneNumbers]\n"; }
                    if (dataset->tagExists(DCM_AdditionalPatientHistory)) { dataset->putAndInsertString(DCM_AdditionalPatientHistory, anonStr); if (status.bad()) msg += "Error changing tag [DCM_AdditionalPatientHistory]\n"; }
                    if (dataset->tagExists(DCM_PatientReligiousPreference)) { dataset->putAndInsertString(DCM_PatientReligiousPreference, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientReligiousPreference]\n"; }
                    if (dataset->tagExists(DCM_PatientComments)) { dataset->putAndInsertString(DCM_PatientComments, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientComments]\n"; }

                    if (dataset->tagExists(DCM_ProtocolName)) { dataset->putAndInsertString(DCM_ProtocolName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ProtocolName]\n"; }

                    if (dataset->tagExists(DCM_RequestingPhysician)) { dataset->putAndInsertString(DCM_RequestingPhysician, anonStr); if (status.bad()) msg += "Error changing tag [DCM_RequestingPhysician]\n"; }
                    if (dataset->tagExists(DCM_RequestedProcedureDescription)) { dataset->putAndInsertString(DCM_RequestedProcedureDescription, anonStr); if (status.bad()) msg += "Error changing tag [DCM_RequestedProcedureDescription]\n"; }

                    if (dataset->tagExists(DCM_ScheduledPerformingPhysicianName)) { dataset->putAndInsertString(DCM_ScheduledPerformingPhysicianName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_ScheduledPerformingPhysicianName]\n"; }
                    if (dataset->tagExists(DCM_PerformedProcedureStepStartDate)) { dataset->putAndInsertString(DCM_PerformedProcedureStepStartDate, anonDate); if (status.bad()) msg += "Error changing tag [DCM_PerformedProcedureStepStartDate]\n"; }
                    if (dataset->tagExists(DCM_PerformedProcedureStepStartTime)) { dataset->putAndInsertString(DCM_PerformedProcedureStepStartTime, anonTime); if (status.bad()) msg += "Error changing tag [DCM_PerformedProcedureStepStartTime]\n"; }
                    if (dataset->tagExists(DCM_PerformedProcedureStepID)) { dataset->putAndInsertString(DCM_PerformedProcedureStepID, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PerformedProcedureStepID]\n"; }
                    if (dataset->tagExists(DCM_PerformedProcedureStepDescription)) { dataset->putAndInsertString(DCM_PerformedProcedureStepDescription, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PerformedProcedureStepDescription]\n"; }
                    if (dataset->tagExists(DCM_HumanPerformerOrganization)) { dataset->putAndInsertString(DCM_HumanPerformerOrganization, anonStr); if (status.bad()) msg += "Error changing tag [DCM_HumanPerformerOrganization]\n"; }
                    if (dataset->tagExists(DCM_HumanPerformerName)) { dataset->putAndInsertString(DCM_HumanPerformerName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_HumanPerformerName]\n"; }
                    if (dataset->tagExists(DCM_PersonName)) { dataset->putAndInsertString(DCM_PersonName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PersonName]\n"; }

                    break;
                }
                case 4: {
                    if (dataset->tagExists(DCM_PatientName)) { dataset->putAndInsertString(DCM_PatientName, anonStr); if (status.bad()) msg += "Error changing tag [DCM_PatientName]\n"; }
                    break;
                }
                default: {
                    break;
                }
            }

                // Generate new UIDs (important!)
            //char newUID[100];
            //dcmGenerateUniqueIdentifier(newUID, SITE_INSTANCE_UID_ROOT);
            //dataset->putAndInsertString(DCM_StudyInstanceUID, newUID);

            //dcmGenerateUniqueIdentifier(newUID, SITE_INSTANCE_UID_ROOT);
            //dataset->putAndInsertString(DCM_SeriesInstanceUID, newUID);

            //dcmGenerateUniqueIdentifier(newUID, SITE_INSTANCE_UID_ROOT);
            //dataset->putAndInsertString(DCM_SOPInstanceUID, newUID);

            status = fileformat.saveFile(dcmOutFile, dataset->getOriginalXfer(), EET_ExplicitLength, EGL_recalcGL, EPD_noChange);
            if (status.good()) {
                //std::cout << "Tags modified successfully." << std::endl;
            }
            else {
                msg += QString("Error: Cannot save file (%1)\n").arg(status.text());
            }
        }
        else {
            msg += QString("Error: Cannot load DICOM file (%1)\n").arg(status.text());
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetImageFileTags ------------------------------- */
/* ---------------------------------------------------------- */
bool imageIO::GetImageFileTags(QString f, QHash<QString, QString> &tags, QString &msg) {

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

            /* if its a .rec file, there must be a corresponding .par file with the same name */

            QFile inputFile(f);
            if (inputFile.open(QIODevice::ReadOnly)) {
                QTextStream in(&inputFile);
                while ( !in.atEnd() ) {
                    QString line = in.readLine();
                    if (line.startsWith(".")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        if (parts.size() >= 2) {
                            QString label = parts[0].trimmed();
                            QString value = parts[1].trimmed();

                            if (label == "Patient name") tags["PatientID"] = value;
                            if (label == "Protocol name") tags["SeriesDescription"] = tags["ProtocolName"] = value;
                            if (label == "Examination name") tags["ExaminationName"] = value;
                            if (label == "Technique") tags["Sequence"] = value;
                            if (label == "Acquisition nr") tags["AcquisitionNR"] = value;
                            if (label == "Reconstruction nr") tags["ReconstructionNR"] = value;
                            if (label == "Scan Duration [sec]") tags["ScanDurationSeconds"] = value;
                            if (label == "Max. number of cardiac phases") tags["MaxNumberCardiacPhases"] = value;
                            if (label == "Max. number of echoes") tags["MaxNumberEchos"] = value;
                            if (label == "Max. number of slices/locations") tags["MaxNumberSlices"] = value;
                            if (label == "Max. number of dynamics") tags["MaxNumberDynamics"] = value;
                            if (label == "Max. number of mixes") tags["MaxNumberMixes"] = value;
                            if (label == "Patient position") tags["PatientPosition"] = value;
                            if (label == "Preparation direction") tags["PreparationDirection"] = value;
                            if (label == "Scan resolution (x, y)") {
                                parts = line.split(" ",Qt::SkipEmptyParts);
                                if (parts.size() >= 2) {
                                    tags["Cols"] = parts[0].trimmed();
                                    tags["Rows"] = parts[1].trimmed();
                                }
                            }
                            if (label == "Repetition time [ms]") tags["RepetitionTime"] = value;
                            if (label == "Scan mode") tags["ScanMode"] = value;
                            if (label == "FOV (ap,fh,rl) [mm]") tags["FieldOfView"] = value;
                            if (label == "Water Fat shift [pixels]") tags["WaterFatShift"] = value;
                            if (label == "Angulation midslice(ap,fh,rl)[degr]") tags["AngulationMidslice"] = value;
                            if (label == "Examination date/time") {
                                parts = line.split("/",Qt::SkipEmptyParts);
                                if (parts.size() >= 2) {
                                    tags["ExaminationDate"] = parts[0].trimmed().replace(".", "-");
                                    tags["ExaminationTime"] = parts[1].trimmed();
                                }
                            }
                            if (label == "Off Centre midslice(ap,fh,rl) [mm]") tags["OffCenterMidslice"] = value;
                            if (label == "Flow compensation <0=no 1=yes> ?") tags["FlowCompensation"] = value;
                            if (label == "Presaturation     <0=no 1=yes> ?") tags["Presaturation"] = value;
                            if (label == "Phase encoding velocity [cm/sec]") tags["PhaseEncodingVelocity"] = value;
                            if (label == "MTC               <0=no 1=yes> ?") tags["MTC"] = value;
                            if (label == "SPIR              <0=no 1=yes> ?") tags["SPIR"] = value;
                            if (label == "EPI factor        <0,1=no EPI>") tags["EPIFactor"] = value;
                            if (label == "Dynamic scan      <0=no 1=yes> ?") tags["DynamicScan"] = value;
                            if (label == "Diffusion         <0=no 1=yes> ?") tags["Diffusion"] = value;
                            if (label == "Diffusion echo time [ms]") tags["DiffusionEchoTime"] = value;
                            if (label == "Max. number of diffusion values") tags["MaxNumberDiffusionValues"] = value;
                            if (label == "Max. number of gradient orients") tags["MaxNumberGradientOrients"] = value;
                            if (label == "Number of label types   <0=no ASL>") tags["NumberLabelTypes"] = value;
                            if (line.contains("MRSERIES", Qt::CaseInsensitive)) tags["Modality"] = "MR";
                        }
                    }
                }
                inputFile.close();
            }
        }
        else {
            msg += "GetImageFileTags() checkpoint D\n";
            /* unknown modality/filetype */
            /* try one last time to read with just the non-interactive command line EXIF tool */
            QString systemstring = "exiftool " + f;
            QString exifoutput = SystemCommand(systemstring, false);
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
            msg += "GetImageFileTags() checkpoint E\n";

            if (tags["FileType"] != "DICOM")
                return false;
        }
    }

    msg += "GetImageFileTags() checkpoint F\n";

    /* fix some of the fields to be amenable to the DB */
    if (tags["Modality"] == "")
        tags["Modality"] = "OT";
    QString StudyDate = ParseDate(tags["StudyDate"]);
    //QString StudyTime = ParseTime(tags["StudyTime"]);
    //QString SeriesDate = ParseDate(tags["SeriesDate"]);
    //QString SeriesTime = ParseTime(tags["SeriesTime"]);

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
        else if (((tags["StudyTime"].size() == 12) || (tags["StudyTime"].size() == 13)) && (tags["StudyTime"].contains("."))) {
            //Print("StudyTime before [" + tags["StudyTime"] + "]");
            tags["StudyTime"] = tags["StudyTime"].left(6);
            //Print("StudyTime after [" + tags["StudyTime"] + "]");
        }
        else {
            Print("StudyTime is not 12, 13 or 15 characters [" + tags["StudyTime"] + "]");
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
        //Print("SeriesTime before [" + tags["SeriesTime"] + "]");

        if (tags["SeriesTime"].contains(":")) {
            tags["SeriesTime"] = tags["SeriesTime"].left(8);
        }
        else if (((tags["SeriesTime"].size() == 12) || (tags["SeriesTime"].size() == 13)) && (tags["SeriesTime"].contains("."))) {
            //Print("SeriesTime before [" + tags["SeriesTime"] + "]");
            tags["SeriesTime"] = tags["SeriesTime"].left(6);
            //Print("SeriesTime after [" + tags["SeriesTime"] + "]");
        }
        else if (((tags["SeriesTime"].size() == 14) || (tags["SeriesTime"].size() == 15)) && (tags["SeriesTime"].contains(":"))) {
            tags["SeriesTime"] = tags["SeriesTime"].left(8);
        }
        else {
            Print("SeriesTime is not 12, 13 or 15 characters [" + tags["SeriesTime"] + "]");
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

    //tags["StudyDateTime"] = tags["StudyDate"] + " " + tags["StudyTime"];
    //tags["SeriesDateTime"] = tags["SeriesDate"] + " " + tags["SeriesTime"];
    //QStringList pix = tags["PixelSpacing"].split("\\");
    //int pixelX(0);
    //int pixelY(0);
    //if (pix.size() == 2) {
    //    pixelX = pix[0].toInt();
    //    pixelY = pix[1].toInt();
    //}
    QStringList amat = tags["AcquisitionMatrix"].split(" ");
    //int mat1(0);
    //int mat2(0);
    //int mat3(0);
    //int mat4(0);
    if (amat.size() == 4) {
        tags["mat1"] = amat[0];
        //mat2 = amat[1].toInt();
        //mat3 = amat[2].toInt();
        tags["mat4"] = amat[3];
    }
    //if (SeriesNumber == 0) {
    //    QString timestamp = SeriesTime;
    //    timestamp.replace(":","").replace("-","").replace(" ","");
    //    tags["SeriesNumber"] = timestamp.toInt();
    //}

    /* fix patient birthdate */
    QString PatientBirthDate = ParseDate(tags["PatientBirthDate"]);

    /* get patient age */
    tags["PatientAge"] = QString("%1").arg(GetPatientAge(tags["PatientAge"], StudyDate, PatientBirthDate));

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

    //qDebug() << "Leaving GetImageFileTags()";
    //n->Log(QString("tags[] contains %1 elements").arg(tags.size()));
    //QString tagString = "";
    //foreach (const QString &key, tags.keys()) {
    //    tagString += QString("tags[%1] = %2\n").arg(key).arg(tags.value(key));
    //}
    //n->Log(tagString);

    return true;
}


/* ------------------------------------------------- */
/* --------- GetFileType --------------------------- */
/* ------------------------------------------------- */
void imageIO::GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol)
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
        else if (f.endsWith(".par")) {
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
                      filePatientID = parts[1].trimmed();
                  }
                  if (line.contains("Protocol name")) {
                      QStringList parts = line.split(":",Qt::SkipEmptyParts);
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
