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
/* --------- imageIO ---------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * imageIO constructor
*/
imageIO::imageIO()
{

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

    //Print("Checkpoing A");
    QStringList msgs;

    QString pwd = QDir::currentPath();

    QString gzipstr;
    if (gzip) gzipstr = "-z y";
    else gzipstr = "-z n";

    //Print("Checkpoing B");

    QString jsonstr;
    if (json) jsonstr = "y";
    else jsonstr = "n";

    numfilesconv = 0; /* need to fix this to be correct at some point */

    msgs << QString("Working on [" + indir + "] and filetype [" + filetype + "]");

    //Print("Checkpoing C");

    /* in case of par/rec, the argument list to dcm2niix is a file instead of a directory */
    QString fileext = "";
    if (datatype == "parrec")
        fileext = "/*.par";

    /* do the conversion */
    QString systemstring;
    QDir::setCurrent(indir);
    if (filetype == "nifti4dme") {
        //Print("Checkpoing D-1");
        systemstring = QString("%1/./dcm2niixme %2 -o '%3' %4").arg(bindir).arg(gzipstr).arg(outdir).arg(indir);
    }
    else if (filetype == "nifti4d") {
        //Print("Checkpoing D-2");
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

    //Print("Checkpoing E");

    /* create the output directory */
    QString m;
    if (!MakePath(outdir, m)) {
        msgs << "Unable to create path [" + outdir + "] because of error [" + m + "]";
        msg = msgs.join("\n");
        return false;
    }

    //Print("Checkpoing F");

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

    //Print("Checkpoing G");

    /* conversion should be done, so check if it actually gzipped the file */
    if ((gzip) && (filetype != "bids")) {
        systemstring = "cd " + outdir + "; gzip *.nii";
        msgs << SystemCommand(systemstring, true);
    }

    /* rename the files into something meaningful */
    m = "";
    if (filetype == "bids") {
        //Print("Checkpoing H");
        BatchRenameBIDSFiles(outdir, bidsSubject, bidsSession, bidsMapping, numfilesrenamed, m);
    }
    else {
        //Print("Checkpoing I");
        BatchRenameFiles(outdir, seriesnum, studynum, uid, numfilesrenamed, m);
    }
    msgs << "Renamed output files [" + m + "]";
    //Print("Checkpoing J");

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
    //gdcm::Reader r;
    //r.SetFileName(f.toStdString().c_str());
    //if (r.Read())
    //    return true;
    //else {
        /* try reading with exiftool */
        QHash<QString, QString> tags;
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

        if (tags["FileType"] != "DICOM")
            return false;
        else
            return false;
    //}
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDICOMFile ----------------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
 bool imageIO::AnonymizeDicomFile(QString infile, QString outfile, QStringList tagsToChange, QString &msg)
{

//     gdcm::Reader reader;
//     reader.SetFileName( infile.toStdString().c_str() );
//     if( !reader.Read() ) {
//         msg += QString("Could not read [%1]").arg(infile);
//     }
//     gdcm::File &file = reader.GetFile();

//     anon.SetFile( file );

    if( tagsToChange.isEmpty() ) {
        msg += "AnonymizeDICOMFile() called with no tags to change. No operation to be done.";
        return false;
    }

    /* do the command line anonymization */
    QString systemstring = QString("gdcmanon --dumb -i %1 -o %2/ %3").arg(infile).arg(outfile).arg(tagsToChange.join(" "));
    QString output = SystemCommand(systemstring, false);
    msg += output;

//     std::vector<gdcm::Tag>::const_iterator it = empty_tags.begin();
//     bool success = true;
//     for(; it != empty_tags.end(); ++it) {
//         success = success && anon.Empty( *it );
//     }
//     it = remove_tags.begin();
//     for(; it != remove_tags.end(); ++it) {
//         success = success && anon.Remove( *it );
//     }

//     std::vector< std::pair<gdcm::Tag, std::string> >::const_iterator it2 = replace_tags.begin();
//     for(; it2 != replace_tags.end(); ++it2) {
//         success = success && anon.Replace( it2->first, it2->second.c_str() );
//     }

//     gdcm::Writer writer;
//     writer.SetFileName( outfile.toStdString().c_str() );
//     writer.SetFile( file );
//     if( !writer.Write() ) {
//         msg += QString("Could not write [%1]").arg(outfile);
//         if ((infile != infile) && (infile != "")) {
//             gdcm::System::RemoveFile( infile.toStdString().c_str() );
//         }
//         else
//         {
//             msg += QString("gdcmanon just corrupted [%1] for you (data lost).").arg(infile);
//         }
//         return false;
//     }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDir ----------------------------------- */
/* ---------------------------------------------------------- */
bool imageIO::AnonymizeDir(QString indir, QString outdir, int anonlevel, QString &msg) {


    // gdcmanon --dumb -i /path/to/dicom --replace 10,10=Anonymous -o /path/to/output/ <-- output will be created if it doesn't exist

    QString anonStr = "Anon";
    QString anonDate = "19000101";
    QString anonTime = "000000.000000";

    //std::vector<gdcm::Tag> empty_tags;
    //std::vector<gdcm::Tag> remove_tags;
    //std::vector< std::pair<gdcm::Tag, std::string> > replace_tags;

    //gdcm::Tag tag;
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
    QString systemstring = QString("gdcmanon --dumb -i %1 -o %2/ %3").arg(indir).arg(outdir).arg(cmdArgs.join(" "));
    QString output = SystemCommand(systemstring, false);
    msg += output;

    /* recursively loop through the directory and anonymize the .dcm files */
    //gdcm::Anonymizer anon;
    //QDirIterator it(dir, QStringList() << "*.dcm", QDir::Files, QDirIterator::Subdirectories);
    //while (it.hasNext()) {
    //    QString dcmfile = it.next();
    //    AnonymizeDicomFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags, msg);
    //}

    return true;
}


/* ------------------------------------------------- */
/* --------- GetDicomModality ---------------------- */
/* ------------------------------------------------- */
QString imageIO::GetDicomModality(QString f)
{
    QString modality = "NOTDICOM";

    QString systemstring = "exiftool " + f;
    QString exifoutput = SystemCommand(systemstring, false);
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

    //gdcm::Reader r;
    //r.SetFileName(f.toStdString().c_str());
    //if (!r.CanRead()) {
    //    return "NOTDICOM";
    //}
    //gdcm::StringFilter sf;
    //sf = gdcm::StringFilter();
    //sf.SetFile(r.GetFile());
    //std::string s = sf.ToString(gdcm::Tag(0x0008,0x0060));

    //QString qs = s.c_str();

    return modality;
}


/* ---------------------------------------------------------- */
/* --------- GetImageFileTags ------------------------------- */
/* ---------------------------------------------------------- */
bool imageIO::GetImageFileTags(QString f, QString bindir, bool enablecsa, QHash<QString, QString> &tags, QString &msg) {

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

    /* check if the file is readable by GDCM, and therefore a DICOM file */
    //gdcm::Reader r;
    //r.SetFileName(f.toStdString().c_str());

    /* read file with EXIF tool */
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

    if (tags["FileType"] == "DICOM") {
        msg += "exiftool successfuly read file [" + f + "]\n";
        /* ---------- it's a readable DICOM file ---------- */
        // gdcm::StringFilter sf;
        // sf = gdcm::StringFilter();
        // sf.SetFile(r.GetFile());

        msg += "GetImageFileTags() checkpoint A\n";

        // tags["FileType"] = "DICOM";

        /* get all of the DICOM tags...
         * we're not using an iterator because we want to know exactly what tags we have, or don't have */

        // tags["FileMetaInformationGroupLength"] =	QString(sf.ToString(gdcm::Tag(0x0002,0x0000)).c_str()).trimmed(); /* FileMetaInformationGroupLength */
        // tags["FileMetaInformationVersion"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0001)).c_str()).trimmed(); /* FileMetaInformationVersion */
        // tags["MediaStorageSOPClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0002)).c_str()).trimmed(); /* MediaStorageSOPClassUID */
        // tags["MediaStorageSOPInstanceUID"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0003)).c_str()).trimmed(); /* MediaStorageSOPInstanceUID */
        // tags["TransferSyntaxUID"] =					QString(sf.ToString(gdcm::Tag(0x0002,0x0010)).c_str()).trimmed(); /* TransferSyntaxUID */
        // tags["ImplementationClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0012)).c_str()).trimmed(); /* ImplementationClassUID */
        // tags["ImplementationVersionName"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0013)).c_str()).trimmed(); /* ImplementationVersionName */

        // msg += "GetImageFileTags() checkpoint A.1\n";

        // tags["SpecificCharacterSet"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0005)).c_str()).trimmed(); /* SpecificCharacterSet */
        // tags["ImageType"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0008)).c_str()).trimmed(); /* ImageType */
        // tags["InstanceCreationDate"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0012)).c_str()).trimmed(); /* InstanceCreationDate */
        // tags["InstanceCreationTime"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0013)).c_str()).trimmed(); /* InstanceCreationTime */
        // tags["SOPClassUID"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0016)).c_str()).trimmed(); /* SOPClassUID */
        // tags["SOPInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0018)).c_str()).trimmed(); /* SOPInstanceUID */
        // tags["StudyDate"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0020)).c_str()).trimmed(); /* StudyDate */
        // tags["SeriesDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0021)).c_str()).trimmed(); /* SeriesDate */
        // tags["AcquisitionDate"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0022)).c_str()).trimmed(); /* AcquisitionDate */
        // tags["ContentDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0023)).c_str()).trimmed(); /* ContentDate */
        // tags["StudyTime"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0030)).c_str()).trimmed(); /* StudyTime */
        // tags["SeriesTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0031)).c_str()).trimmed(); /* SeriesTime */
        // tags["AcquisitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0032)).c_str()).trimmed(); /* AcquisitionTime */
        // tags["ContentTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0033)).c_str()).trimmed(); /* ContentTime */
        // tags["AccessionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0050)).c_str()).trimmed(); /* AccessionNumber */
        // tags["Modality"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0060)).c_str()).trimmed(); /* Modality */
        // tags["Manufacturer"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0070)).c_str()).trimmed(); /* Manufacturer */
        // tags["InstitutionName"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0080)).c_str()).trimmed(); /* InstitutionName */
        // tags["InstitutionAddress"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0081)).c_str()).trimmed(); /* InstitutionAddress */
        // tags["ReferringPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x0090)).c_str()).trimmed(); /* ReferringPhysicianName */
        // tags["StationName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1010)).c_str()).trimmed(); /* StationName */
        // tags["StudyDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x1030)).c_str()).trimmed(); /* StudyDescription */
        // tags["SeriesDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x103E)).c_str()).trimmed(); /* SeriesDescription */
        // tags["InstitutionalDepartmentName"] =		QString(sf.ToString(gdcm::Tag(0x0008,0x1040)).c_str()).trimmed(); /* InstitutionalDepartmentName */
        // tags["PerformingPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x1050)).c_str()).trimmed(); /* PerformingPhysicianName */
        // tags["OperatorsName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1070)).c_str()).trimmed(); /* OperatorsName */
        // tags["ManufacturerModelName"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x1090)).c_str()).trimmed(); /* ManufacturerModelName */
        // tags["SourceImageSequence"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x2112)).c_str()).trimmed(); /* SourceImageSequence */

        // msg += "GetImageFileTags() checkpoint A.2\n";

        // tags["PatientName"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0010)).c_str()).trimmed(); /* PatientName */
        // tags["PatientID"] =							QString(sf.ToString(gdcm::Tag(0x0010,0x0020)).c_str()).trimmed(); /* PatientID */
        // tags["PatientBirthDate"] =					QString(sf.ToString(gdcm::Tag(0x0010,0x0030)).c_str()).trimmed(); /* PatientBirthDate */
        // tags["PatientSex"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0040)).c_str()).trimmed().left(1); /* PatientSex */
        // tags["PatientAge"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1010)).c_str()).trimmed(); /* PatientAge */
        // tags["PatientSize"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1020)).c_str()).trimmed(); /* PatientSize */
        // tags["PatientWeight"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1030)).c_str()).trimmed(); /* PatientWeight */

        // msg += "GetImageFileTags() checkpoint A.3\n";

        // tags["ContrastBolusAgent"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0010)).c_str()).trimmed(); /* ContrastBolusAgent */
        // tags["KVP"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x0060)).c_str()).trimmed(); /* KVP */
        // tags["DataCollectionDiameter"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0090)).c_str()).trimmed(); /* DataCollectionDiameter */
        // tags["ContrastBolusRoute"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1040)).c_str()).trimmed(); /* ContrastBolusRoute */
        // tags["RotationDirection"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1140)).c_str()).trimmed(); /* RotationDirection */
        // tags["ExposureTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1150)).c_str()).trimmed(); /* ExposureTime */
        // tags["XRayTubeCurrent"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1151)).c_str()).trimmed(); /* XRayTubeCurrent */
        // tags["FilterType"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1160)).c_str()).trimmed(); /* FilterType */
        // tags["GeneratorPower"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1170)).c_str()).trimmed(); /* GeneratorPower */
        // tags["ConvolutionKernel"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1210)).c_str()).trimmed(); /* ConvolutionKernel */

        // msg += "GetImageFileTags() checkpoint A.4\n";

        // tags["BodyPartExamined"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0015)).c_str()).trimmed(); /* BodyPartExamined */
        // tags["ScanningSequence"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0020)).c_str()).trimmed(); /* ScanningSequence */
        // tags["SequenceVariant"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0021)).c_str()).trimmed(); /* SequenceVariant */
        // tags["ScanOptions"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0022)).c_str()).trimmed(); /* ScanOptions */
        // tags["MRAcquisitionType"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0023)).c_str()).trimmed(); /* MRAcquisitionType */
        // tags["SequenceName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0024)).c_str()).trimmed(); /* SequenceName */
        // tags["AngioFlag"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0025)).c_str()).trimmed(); /* AngioFlag */
        // tags["SliceThickness"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0050)).c_str()).trimmed(); /* SliceThickness */
        // tags["RepetitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0080)).c_str()).trimmed(); /* RepetitionTime */
        // tags["EchoTime"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0081)).c_str()).trimmed(); /* EchoTime */
        // tags["InversionTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0082)).c_str()).trimmed(); /* InversionTime */
        // tags["NumberOfAverages"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0083)).c_str()).trimmed(); /* NumberOfAverages */
        // tags["ImagingFrequency"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0084)).c_str()).trimmed(); /* ImagingFrequency */
        // tags["ImagedNucleus"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0085)).c_str()).trimmed(); /* ImagedNucleus */
        // tags["EchoNumbers"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0086)).c_str()).trimmed(); /* EchoNumbers */
        // tags["MagneticFieldStrength"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0087)).c_str()).trimmed(); /* MagneticFieldStrength */
        // tags["SpacingBetweenSlices"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0088)).c_str()).trimmed(); /* SpacingBetweenSlices */
        // tags["NumberOfPhaseEncodingSteps"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x0089)).c_str()).trimmed(); /* NumberOfPhaseEncodingSteps */
        // tags["EchoTrainLength"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0091)).c_str()).trimmed(); /* EchoTrainLength */
        // tags["PercentSampling"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0093)).c_str()).trimmed(); /* PercentSampling */
        // tags["PercentPhaseFieldOfView"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0094)).c_str()).trimmed(); /* PercentPhaseFieldOfView */
        // tags["PixelBandwidth"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0095)).c_str()).trimmed(); /* PixelBandwidth */
        // tags["DeviceSerialNumber"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1000)).c_str()).trimmed(); /* DeviceSerialNumber */
        // tags["SoftwareVersions"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1020)).c_str()).trimmed(); /* SoftwareVersions */
        // tags["ProtocolName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1030)).c_str()).trimmed(); /* ProtocolName */
        // tags["TransmitCoilName"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1251)).c_str()).trimmed(); /* TransmitCoilName */
        // tags["AcquisitionMatrix"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1310)).c_str()).trimmed().left(20); /* AcquisitionMatrix */
        // tags["InPlanePhaseEncodingDirection"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x1312)).c_str()).trimmed(); /* InPlanePhaseEncodingDirection */
        // tags["FlipAngle"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x1314)).c_str()).trimmed(); /* FlipAngle */
        // tags["VariableFlipAngleFlag"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1315)).c_str()).trimmed(); /* VariableFlipAngleFlag */
        // tags["SAR"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1316)).c_str()).trimmed(); /* SAR */
        // tags["dBdt"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1318)).c_str()).trimmed(); /* dBdt */
        // tags["PatientPosition"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x5100)).c_str()).trimmed(); /* PatientPosition */

        // msg += "GetImageFileTags() checkpoint A.5\n";

        // tags["Unknown Tag & Data"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1009)).c_str()).trimmed(); /* Unknown Tag & Data */
        // tags["NumberOfImagesInMosaic"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100A)).c_str()).trimmed(); /* NumberOfImagesInMosaic*/
        // tags["SliceObservationmentDuration"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100B)).c_str()).trimmed(); /* SliceObservationmentDuration*/
        // tags["B_value"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x100C)).c_str()).trimmed(); /* B_value*/
        // tags["DiffusionDirectionality"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100D)).c_str()).trimmed(); /* DiffusionDirectionality*/
        // tags["DiffusionGradientDirection"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x100E)).c_str()).trimmed(); /* DiffusionGradientDirection*/
        // tags["GradientMode"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x100F)).c_str()).trimmed(); /* GradientMode*/
        // tags["FlowCompensation"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1011)).c_str()).trimmed(); /* FlowCompensation*/
        // tags["TablePositionOrigin"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1012)).c_str()).trimmed(); /* TablePositionOrigin*/
        // tags["ImaAbsTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1013)).c_str()).trimmed(); /* ImaAbsTablePosition*/
        // tags["ImaRelTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1014)).c_str()).trimmed(); /* ImaRelTablePosition*/
        // tags["SlicePosition_PCS"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1015)).c_str()).trimmed(); /* SlicePosition_PCS*/
        // tags["TimeAfterStart"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1016)).c_str()).trimmed(); /* TimeAfterStart*/
        // tags["SliceResolution"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1017)).c_str()).trimmed(); /* SliceResolution*/
        // tags["RealDwellTime"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1018)).c_str()).trimmed(); /* RealDwellTime*/
        // tags["RBMoCoTrans"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1025)).c_str()).trimmed(); /* RBMoCoTrans*/
        // tags["RBMoCoRot"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1026)).c_str()).trimmed(); /* RBMoCoRot*/
        // tags["B_matrix"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1027)).c_str()).trimmed(); /* B_matrix*/
        // tags["BandwidthPerPixelPhaseEncode"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x1028)).c_str()).trimmed(); /* BandwidthPerPixelPhaseEncode*/
        // tags["MosaicRefAcqTimes"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1029)).c_str()).trimmed(); /* MosaicRefAcqTimes*/

        // msg += "GetImageFileTags() checkpoint A.6\n";

        // tags["StudyInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000D)).c_str()).trimmed(); /* StudyInstanceUID */
        // tags["SeriesInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000E)).c_str()).trimmed(); /* SeriesInstanceUID */
        // tags["StudyID"] =							QString(sf.ToString(gdcm::Tag(0x0020,0x0010)).c_str()).trimmed(); /* StudyID */
        // tags["SeriesNumber"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x0011)).c_str()).trimmed(); /* SeriesNumber */
        // tags["AcquisitionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0012)).c_str()).trimmed(); /* AcquisitionNumber */
        // tags["InstanceNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0013)).c_str()).trimmed(); /* InstanceNumber */
        // tags["ImagePositionPatient"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0032)).c_str()).trimmed(); /* ImagePositionPatient */
        // tags["ImageOrientationPatient"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0037)).c_str()).trimmed(); /* ImageOrientationPatient */
        // tags["FrameOfReferenceUID"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0052)).c_str()).trimmed(); /* FrameOfReferenceUID */
        // tags["NumberOfTemporalPositions"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* NumberOfTemporalPositions */
        // tags["ImagesInAcquisition"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* ImagesInAcquisition */
        // tags["PositionReferenceIndicator"] =		QString(sf.ToString(gdcm::Tag(0x0020,0x1040)).c_str()).trimmed(); /* PositionReferenceIndicator */
        // tags["SliceLocation"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x1041)).c_str()).trimmed(); /* SliceLocation */

        // msg += "GetImageFileTags() checkpoint A.7\n";

        // tags["SamplesPerPixel"] =					QString(sf.ToString(gdcm::Tag(0x0028,0x0002)).c_str()).trimmed(); /* SamplesPerPixel */
        // tags["PhotometricInterpretation"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0004)).c_str()).trimmed(); /* PhotometricInterpretation */
        // tags["Rows"] =								QString(sf.ToString(gdcm::Tag(0x0028,0x0010)).c_str()).trimmed(); /* Rows */
        // tags["Columns"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0011)).c_str()).trimmed(); /* Columns */
        // tags["PixelSpacing"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0030)).c_str()).trimmed(); /* PixelSpacing */
        // tags["BitsAllocated"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0100)).c_str()).trimmed(); /* BitsAllocated */
        // tags["BitsStored"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0101)).c_str()).trimmed(); /* BitsStored */
        // tags["HighBit"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0102)).c_str()).trimmed(); /* HighBit */
        // tags["PixelRepresentation"] =				QString(sf.ToString(gdcm::Tag(0x0028,0x0103)).c_str()).trimmed(); /* PixelRepresentation */
        // tags["SmallestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0106)).c_str()).trimmed(); /* SmallestImagePixelValue */
        // tags["LargestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0107)).c_str()).trimmed(); /* LargestImagePixelValue */
        // tags["WindowCenter"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1050)).c_str()).trimmed(); /* WindowCenter */
        // tags["WindowWidth"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1051)).c_str()).trimmed(); /* WindowWidth */
        // tags["WindowCenterWidthExplanation"] =		QString(sf.ToString(gdcm::Tag(0x0028,0x1055)).c_str()).trimmed(); /* WindowCenterWidthExplanation */

        // msg += "GetImageFileTags() checkpoint A.8\n";

        // tags["RequestingPhysician"] =				QString(sf.ToString(gdcm::Tag(0x0032,0x1032)).c_str()).trimmed(); /* RequestingPhysician */
        // tags["RequestedProcedureDescription"] =		QString(sf.ToString(gdcm::Tag(0x0032,0x1060)).c_str()).trimmed(); /* RequestedProcedureDescription */

        // msg += "GetImageFileTags() checkpoint A.9\n";

        // tags["PerformedProcedureStepStartDate"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0244)).c_str()).trimmed(); /* PerformedProcedureStepStartDate */
        // tags["PerformedProcedureStepStartTime"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0245)).c_str()).trimmed(); /* PerformedProcedureStepStartTime */
        // tags["PerformedProcedureStepID"] =			QString(sf.ToString(gdcm::Tag(0x0040,0x0253)).c_str()).trimmed(); /* PerformedProcedureStepID */
        // tags["PerformedProcedureStepDescription"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0254)).c_str()).trimmed(); /* PerformedProcedureStepDescription */
        // tags["CommentsOnThePerformedProcedureSte"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0280)).c_str()).trimmed(); /* CommentsOnThePerformedProcedureSte */

        // msg += "GetImageFileTags() checkpoint A.10\n";

        // tags["TimeOfAcquisition"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100A)).c_str()).trimmed(); /* TimeOfAcquisition*/
        // tags["AcquisitionMatrixText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x100B)).c_str()).trimmed(); /* AcquisitionMatrixText*/
        // tags["FieldOfView"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100C)).c_str()).trimmed(); /* FieldOfView*/
        // tags["SlicePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100D)).c_str()).trimmed(); /* SlicePositionText*/
        // tags["ImageOrientation"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100E)).c_str()).trimmed(); /* ImageOrientation*/
        // tags["CoilString"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100F)).c_str()).trimmed(); /* CoilString*/
        // tags["ImaPATModeText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1011)).c_str()).trimmed(); /* ImaPATModeText*/
        // tags["TablePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1012)).c_str()).trimmed(); /* TablePositionText*/
        // tags["PositivePCSDirections"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1013)).c_str()).trimmed(); /* PositivePCSDirections*/
        // tags["ImageTypeText"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x1016)).c_str()).trimmed(); /* ImageTypeText*/
        // tags["SliceThicknessText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1017)).c_str()).trimmed(); /* SliceThicknessText*/
        // tags["ScanOptionsText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1019)).c_str()).trimmed(); /* ScanOptionsText*/

        // msg += "GetImageFileTags() checkpoint B\n";

        QString uniqueseries = tags["InstitutionName"] + tags["StationName"] + tags["Modality"] + tags["PatientName"] + tags["PatientBirthDate"] + tags["PatientSex"] + tags["StudyDateTime"] + tags["SeriesNumber"];
        tags["UniqueSeriesString"] = uniqueseries;

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
            QString csaheader = SystemCommand(systemstring, false);
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
        Print("SeriesTime before [" + tags["SeriesTime"] + "]");

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

    qDebug() << "Leaving GetImageFileTags()";

    return true;
}


/* ------------------------------------------------- */
/* --------- GetFileType --------------------------- */
/* ------------------------------------------------- */
void imageIO::GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol)
{
    fileModality = QString("");

    /* read file with EXIF tool */
    QString systemstring = "exiftool " + f;
    QString exifoutput = SystemCommand(systemstring, false);
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

    //gdcm::Reader r;
    //r.SetFileName(f.toStdString().c_str());
    if (tags["FileType"] == "DICOM") {

        if (tags.contains("Modality"))
            fileModality = tags["Modality"];

        if (tags.contains("PatientID"))
            filePatientID = tags["PatientID"];

        if (tags.contains("SeriesDescription"))
            fileProtocol = tags["SeriesDescription"];

        //if (r.CanRead()) {
        //r.Read();
        //fileType = QString("DICOM");
        //gdcm::StringFilter sf;
        //sf = gdcm::StringFilter();
        //sf.SetFile(r.GetFile());
        //std::string s;

        /* get modality */
        //s = sf.ToString(gdcm::Tag(0x0008,0x0060));
        //fileModality = QString(s.c_str());

        /* get patientID */
        //s = sf.ToString(gdcm::Tag(0x0010,0x0020));
        //filePatientID = QString(s.c_str());

        /* get protocol (seriesDesc) */
        //s = sf.ToString(gdcm::Tag(0x0008,0x103E));
        //fileProtocol = QString(s.c_str());
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
