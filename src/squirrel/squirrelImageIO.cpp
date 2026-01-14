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


/* ---------------------------------------------------------- */
/* --------- squirrelImageIO -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * squirrelImageIO constructor
*/
squirrelImageIO::squirrelImageIO()
{
    /* always start exiftool daemon */
    StartExiftool();
}


/* ---------------------------------------------------------- */
/* --------- ~squirrelImageIO ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * squirrelImageIO destructor
*/
squirrelImageIO::~squirrelImageIO()
{
    /* always terminate the exiftool daemon */
    TerminateExiftool();
}


/* ---------------------------------------------------------- */
/* --------- StartExiftool ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Starts the exiftool 'deamon'
 * @return true if successful, false otherwise
 */
bool squirrelImageIO::StartExiftool() {

    /* create the process */
    exifProcess = new QProcess();

    /* start exiftool */
    QStringList args;
    args << "-stay_open" << "True" << "-@" << "-";
    exifProcess->start("exiftool", args);
    exifProcess->waitForStarted();

    if (exifProcess->processId() > 0) {
        //Print(QString("Started exiftool with pid [%1]").arg(exiftool->processId()));
        return true;
    }
    else {
        utils::Print(QString("Error starting exiftool [%1]").arg(exifProcess->errorString()));
        return false;
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- TerminateExiftool ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Terminate the exiftool 'daemon'
 * @return true
 */
bool squirrelImageIO::TerminateExiftool() {
    /* let exiftool terminate itself */
    exifProcess->write("-stay_open\nFalse\n");
    exifProcess->waitForBytesWritten();

    /* terminate the QProcess */
    exifProcess->close();
    delete exifProcess;

    return true;
}


/* ---------------------------------------------------------- */
/* --------- RunExiftool ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Run exiftool to get DICOM tags. Output should be terminated with {ready}, otherwise it is incomplete
 * @param arg The DICOM (or other type) file
 * @return The full output from exiftool
 */
QString squirrelImageIO::RunExiftool(QString arg) {
    QString str;

    QFileInfo fileInfo(arg);
    QString filename = fileInfo.fileName();

    /* try passing the command to exiftool three times, in case there is a problem reading the file using exiftool */
    for (int i=0; i<3; i++) {
        exifProcess->readAllStandardOutput(); /* clear buffer */

        exifProcess->write(arg.toUtf8() + '\n');
        exifProcess->waitForBytesWritten();
        exifProcess->write("-execute\n");
        exifProcess->waitForBytesWritten();

        exifProcess->waitForReadyRead();

        QByteArray output = exifProcess->readAllStandardOutput();
        str = QString::fromUtf8(output);

        /* check if the output contains {ready} */
        if (!str.contains("{ready}")) {
            utils::Print(QString("*** Exiftool output from file [%1] does NOT contain {ready}. String size is [%2] bytes (attempt %3 of 3) ***").arg(arg).arg(str.size()).arg(i));
            QThread::msleep(100);
        }
        /* check if the output is not truncated, or cut off */
        else if (str.size() < 50) {
            utils::Print(QString("*** Exiftool output from file [%1] is ONLY [%2] bytes (attempt %3 of 3) str contains [%4] ***").arg(arg).arg(str.size()).arg(i).arg(str));
            QThread::msleep(100);
        }
        /* check if the output contains the filename passed to exiftool. ie the  */
        else if (!str.contains(filename)) {
            utils::Print(QString("*** Exiftool output from file [%1] does NOT contain the file name (attempt %2 of 3) size is [%3] ***").arg(arg).arg(i).arg(str.size()));
            QThread::msleep(100);
        }
        /* check if the str is blank */
        else if (str == "") {
            utils::Print(QString("*** Exiftool output from file [%1] is empty (attempt %2 of 3) ***").arg(arg).arg(i));
            QThread::msleep(100);
        }
        /* otherwise, we've successfully read the file header and gotten a complete response */
        else {
            break;
        }
    }

    return str;
}


/* ---------------------------------------------------------- */
/* --------- ConvertDicom ----------------------------------- */
/* ---------------------------------------------------------- */
bool squirrelImageIO::ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg) {

    QStringList msgs;

    QString pwd = QDir::currentPath();

    numfilesconv = 0; /* need to fix this to be correct at some point */

    msgs << QString("Converting DICOM to Nifti.  indir [" + indir + "]  outdir [" + outdir + "]  outfiletype [" + filetype + "]");

    /* in case of par/rec, the argument list to dcm2niix is a file instead of a directory */
    QString fileext = "";
    if (datatype == "parrec")
        fileext = "/*.par";

    /* do the conversion */
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
    else {
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

    /* delete any files that may already be in the output directory.. for example, an incomplete series was put in the output directory
     * remove any stuff and start from scratch to ensure proper file numbering */
    if ((outdir != "") && (outdir != "/") ) {
        QString systemstring2 = QString("rm -f %1/*.hdr %1/*.img %1/*.nii %1/*.gz").arg(outdir);
        msgs << utils::SystemCommand(systemstring2, true, true);

        /* execute the command created above */
        msgs << utils::SystemCommand(systemstring, true, true);
    }
    else {
        msg = msgs.join("\n");
        return false;
    }

    /* conversion should be done, so check if it actually gzipped the file */
    if ((gzip) && (filetype != "bids")) {
        systemstring = "cd " + outdir + "; gzip *";
        msgs << utils::SystemCommand(systemstring, true);
    }

    /* rename the files into something meaningful */
    m = "";
    if (!utils::BatchRenameFiles(outdir, seriesnum, studynum, uid, numfilesrenamed, m))
        msgs << "Error renaming output files [" + m + "]";

    /* change back to original directory before leaving */
    QDir::setCurrent(pwd);

    msg = msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- IsDICOMFile ------------------------------------ */
/* ---------------------------------------------------------- */
bool squirrelImageIO::IsDICOMFile(QString f) {
    /* check if its really a dicom file... */
    /* try reading with exiftool */
    QHash<QString, QString> tags;
    QString exifoutput = RunExiftool(f);
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
        return false;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDicomFileInPlace ---------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
bool squirrelImageIO::AnonymizeDicomFileInPlace(QString file, QStringList tagsToChange, QString &msg)
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
            QString output = utils::SystemCommand(systemstring, false);
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
bool squirrelImageIO::AnonymizeDicomDirInPlace(QString dir, int anonlevel, QString &msg) {


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

    QStringList dcms = utils::FindAllFiles(dir, "*.dcm");
    foreach (const QString &f, dcms) {
        QString m;
        AnonymizeDicomFileInPlace(f, cmdArgs, m);
        msg += m + '\n';
    }

    //QString systemstring = QString("gdcmanon --dumb --continue %1 -i %2 -o %3").arg(cmdArgs.join(" ")).arg(indir).arg(outdir);
    //n->Log(systemstring);
    //QString output = SystemCommand(systemstring, true);
    //n->Log(output);
    //msg += output;

    return true;
}


/* ------------------------------------------------- */
/* --------- GetDicomModality ---------------------- */
/* ------------------------------------------------- */
QString squirrelImageIO::GetDicomModality(QString f)
{
    QString modality = "NOTDICOM";
    QString exifoutput = RunExiftool(f);
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
bool squirrelImageIO::GetImageFileTags(QString f, QString bindir, bool enablecsa, QHash<QString, QString> &tags, QString &msg) {

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

    /* read file with EXIF tool */
    QString exifoutput = RunExiftool(f);
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
    msg += "GetImageFileTags() checkpoint A\n";

    if (tags["FileType"] == "DICOM") {
        msg += "exiftool successfuly read file [" + f + "]\n";
        /* ---------- it's a readable DICOM file ---------- */

        /* attempt to get the Siemens CSA header info, but not if this is a Siemens enhanced DICOM */
        tags["PhaseEncodeAngle"] = "";
        tags["PhaseEncodingDirectionPositive"] = "";

        if ((enablecsa) && (tags["SOPClassUID"] != "Enhanced MR Image Storage")) {
            /* attempt to get the phase encode angle (In Plane Rotation) from the siemens CSA header */
            QFile df(f);

            /* open the dicom file as a text file, since part of the CSA header is stored as text, not binary */
            if (df.open(QIODevice::ReadOnly | QIODevice::Text)) {

                QTextStream in(&df);
                while (!in.atEnd()) {
                    QString line = in.readLine();
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
                //WriteLog(QString("Found PhaseEncodeAngle of [%1]").arg(tags["PhaseEncodeAngle"]));
                df.close();
            }

            /* get the other part of the CSA header, the PhaseEncodingDirectionPositive value */
            QString systemstring = QString("%1/./gdcmdump -C %2 | grep PhaseEncodingDirectionPositive").arg(bindir).arg(f);
            QString csaheader = utils::SystemCommand(systemstring, false);
            QStringList parts = csaheader.split(",");
            QString val;
            if (parts.size() == 5) {
                val = parts[4];
                val.replace("Data '","",Qt::CaseInsensitive);
                val.replace("'","");
                if (val.trimmed() == "Data")
                    val = "";
                tags["PhaseEncodingDirectionPositive"] = val.trimmed();
            }
            //WriteLog(QString("Found PhaseEncodingDirectionPositive of [%1]").arg(tags["PhaseEncodingDirectionPositive"]));
        }
        msg += "GetImageFileTags() checkpoint C\n";
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
            /* try one last time to read with EXIF tool */
            QString exifoutput = RunExiftool(f);
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
    QString StudyDate = utils::ParseDate(tags["StudyDate"]);
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
            utils::Print("StudyTime is not 12, 13 or 15 characters [" + tags["StudyTime"] + "]");
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
            utils::Print("SeriesTime is not 12, 13 or 15 characters [" + tags["SeriesTime"] + "]");
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

    //qDebug() << "Leaving GetImageFileTags()";
    //Print(QString("tags[] contains %1 elements").arg(tags.size()));

    return true;
}


/* ------------------------------------------------- */
/* --------- GetFileType --------------------------- */
/* ------------------------------------------------- */
void squirrelImageIO::GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol)
{
    fileModality = QString("");

    /* read file with EXIF tool */
    QString exifoutput = RunExiftool(f);
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
