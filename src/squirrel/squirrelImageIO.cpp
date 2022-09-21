/* ------------------------------------------------------------------------------
  NIDB squirrelImageIO.cpp
  Copyright (C) 2004 - 2022
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
/* --------- ConvertDicom ----------------------------------- */
/* ---------------------------------------------------------- */
bool squirrelImageIO::ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg) {

    QStringList msgs;

    QString pwd = QDir::currentPath();

    //QString gzipstr;
    //if (gzip) gzipstr = "-z y";
    //else gzipstr = "-z n";

    numfilesconv = 0; /* need to fix this to be correct at some point */

    msgs << QString("Working on [" + indir + "] and filetype [" + filetype + "]");

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
    if (!MakePath(outdir, m)) {
        msgs << "Unable to create path [" + outdir + "] because of error [" + m + "]";
        msg = msgs.join("\n");
        return false;
    }

    /* delete any files that may already be in the output directory.. for example, an incomplete series was put in the output directory
     * remove any stuff and start from scratch to ensure proper file numbering */
    if ((outdir != "") && (outdir != "/") ) {
        QString systemstring2 = QString("rm -f %1/*.hdr %1/*.img %1/*.nii %1/*.gz").arg(outdir);
        msgs << SystemCommand(systemstring2, true, true);

        /* execute the command created above */
        msgs << SystemCommand(systemstring, true, true);
    }
    else {
        msg = msgs.join("\n");
        return false;
    }

    /* conversion should be done, so check if it actually gzipped the file */
    if ((gzip) && (filetype != "bids")) {
        systemstring = "cd " + outdir + "; gzip *";
        msgs << SystemCommand(systemstring, true);
    }

    /* rename the files into something meaningful */
    m = "";
    if (!BatchRenameFiles(outdir, seriesnum, studynum, uid, numfilesrenamed, m))
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
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (r.Read())
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDICOMFile ----------------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
bool squirrelImageIO::AnonymizeDicomFile(gdcm::Anonymizer &anon, QString infile, QString outfile, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags, QString &msg)
{

    gdcm::Reader reader;
    reader.SetFileName( infile.toStdString().c_str() );
    if( !reader.Read() ) {
        msg += QString("Could not read [%1]").arg(infile);
        //if( continuemode ) {
        //	WriteLog("Skipping from anonymization process (continue mode).");
        //	return true;
        //}
        //else
        //{
        //	WriteLog("Check [--continue] option for skipping files.");
        //	return false;
        //}
    }
    gdcm::File &file = reader.GetFile();

    anon.SetFile( file );

    if( empty_tags.empty() && replace_tags.empty() && remove_tags.empty() ) {
        msg += "AnonymizeDICOMFile() empty tags. No operation to be done.";
        return false;
    }

    std::vector<gdcm::Tag>::const_iterator it = empty_tags.begin();
    bool success = true;
    for(; it != empty_tags.end(); ++it) {
        success = success && anon.Empty( *it );
    }
    it = remove_tags.begin();
    for(; it != remove_tags.end(); ++it) {
        success = success && anon.Remove( *it );
    }

    std::vector< std::pair<gdcm::Tag, std::string> >::const_iterator it2 = replace_tags.begin();
    for(; it2 != replace_tags.end(); ++it2) {
        success = success && anon.Replace( it2->first, it2->second.c_str() );
    }

    gdcm::Writer writer;
    writer.SetFileName( outfile.toStdString().c_str() );
    writer.SetFile( file );
    if( !writer.Write() ) {
        msg += QString("Could not write [%1]").arg(outfile);
        if ((infile != infile) && (infile != "")) {
            gdcm::System::RemoveFile( infile.toStdString().c_str() );
        }
        else
        {
            msg += QString("gdcmanon just corrupted [%1] for you (data lost).").arg(infile);
        }
        return false;
    }
    return success;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDir ----------------------------------- */
/* ---------------------------------------------------------- */
bool squirrelImageIO::AnonymizeDir(QString dir,int anonlevel, QString randstr1, QString randstr2, QString &msg) {

    std::vector<gdcm::Tag> empty_tags;
    std::vector<gdcm::Tag> remove_tags;
    std::vector< std::pair<gdcm::Tag, std::string> > replace_tags;

    gdcm::Tag tag;

    switch (anonlevel) {
        case 0:
            msg += "No anonymization requested. Leaving files unchanged.";
            return 0;
        case 1:
        case 3:
            /* remove referring physician name */
            tag.ReadFromCommaSeparatedString("0008, 0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
            tag.ReadFromCommaSeparatedString("0008, 1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
            tag.ReadFromCommaSeparatedString("0008, 1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
            tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr1).toStdString().c_str()) );
            tag.ReadFromCommaSeparatedString("0010, 0030"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr2).toStdString().c_str()) );
            break;
        case 2:
            /* Full anonymization. remove all names, dates, locations. ANYTHING identifiable */
            tag.ReadFromCommaSeparatedString("0008,0012"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // InstanceCreationDate
            tag.ReadFromCommaSeparatedString("0008,0013"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // InstanceCreationTime
            tag.ReadFromCommaSeparatedString("0008,0020"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // StudyDate
            tag.ReadFromCommaSeparatedString("0008,0021"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // SeriesDate
            tag.ReadFromCommaSeparatedString("0008,0022"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // AcquisitionDate
            tag.ReadFromCommaSeparatedString("0008,0023"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // ContentDate
            tag.ReadFromCommaSeparatedString("0008,0030"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //StudyTime
            tag.ReadFromCommaSeparatedString("0008,0031"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //SeriesTime
            tag.ReadFromCommaSeparatedString("0008,0032"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //AcquisitionTime
            tag.ReadFromCommaSeparatedString("0008,0033"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //ContentTime
            tag.ReadFromCommaSeparatedString("0008,0080"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // InstitutionName
            tag.ReadFromCommaSeparatedString("0008,0081"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // InstitutionAddress
            tag.ReadFromCommaSeparatedString("0008,0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianName
            tag.ReadFromCommaSeparatedString("0008,0092"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianAddress
            tag.ReadFromCommaSeparatedString("0008,0094"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianTelephoneNumber
            tag.ReadFromCommaSeparatedString("0008,0096"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianIDSequence
            tag.ReadFromCommaSeparatedString("0008,1010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // StationName
            tag.ReadFromCommaSeparatedString("0008,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // StudyDescription
            tag.ReadFromCommaSeparatedString("0008,103E"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // SeriesDescription
            tag.ReadFromCommaSeparatedString("0008,1048"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PhysiciansOfRecord
            tag.ReadFromCommaSeparatedString("0008,1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformingPhysicianName
            tag.ReadFromCommaSeparatedString("0008,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // NameOfPhysicianReadingStudy
            tag.ReadFromCommaSeparatedString("0008,1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OperatorsName

            tag.ReadFromCommaSeparatedString("0010,0010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientName
            tag.ReadFromCommaSeparatedString("0010,0020"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientID
            tag.ReadFromCommaSeparatedString("0010,0021"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // IssuerOfPatientID
            tag.ReadFromCommaSeparatedString("0010,0030"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // PatientBirthDate
            tag.ReadFromCommaSeparatedString("0010,0032"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); // PatientBirthTime
            tag.ReadFromCommaSeparatedString("0010,0050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientInsurancePlanCodeSequence
            tag.ReadFromCommaSeparatedString("0010,1000"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OtherPatientIDs
            tag.ReadFromCommaSeparatedString("0010,1001"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OtherPatientNames
            tag.ReadFromCommaSeparatedString("0010,1005"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientBirthName
            tag.ReadFromCommaSeparatedString("0010,1010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientAge
            tag.ReadFromCommaSeparatedString("0010,1020"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientSize
            tag.ReadFromCommaSeparatedString("0010,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientWeight
            tag.ReadFromCommaSeparatedString("0010,1040"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientAddress
            tag.ReadFromCommaSeparatedString("0010,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientMotherBirthName
            tag.ReadFromCommaSeparatedString("0010,2154"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientTelephoneNumbers
            tag.ReadFromCommaSeparatedString("0010,21B0"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // AdditionalPatientHistory
            tag.ReadFromCommaSeparatedString("0010,21F0"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientReligiousPreference
            tag.ReadFromCommaSeparatedString("0010,4000"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientComments

            tag.ReadFromCommaSeparatedString("0018,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ProtocolName

            tag.ReadFromCommaSeparatedString("0032,1032"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // RequestingPhysician
            tag.ReadFromCommaSeparatedString("0032,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // RequestedProcedureDescription

            tag.ReadFromCommaSeparatedString("0040,0006"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ScheduledPerformingPhysiciansName
            tag.ReadFromCommaSeparatedString("0040,0244"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // PerformedProcedureStepStartDate
            tag.ReadFromCommaSeparatedString("0040,0245"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); // PerformedProcedureStepStartTime
            tag.ReadFromCommaSeparatedString("0040,0253"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformedProcedureStepID
            tag.ReadFromCommaSeparatedString("0040,0254"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformedProcedureStepDescription
            tag.ReadFromCommaSeparatedString("0040,4036"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // HumanPerformerOrganization
            tag.ReadFromCommaSeparatedString("0040,4037"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // HumanPerformerName
            tag.ReadFromCommaSeparatedString("0040,A123"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PersonName

            break;
        case 4:
            tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr1).toStdString().c_str()) );
            break;
    }

    /* recursively loop through the directory and anonymize the .dcm files */
    gdcm::Anonymizer anon;
    QDirIterator it(dir, QStringList() << "*.dcm", QDir::Files, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        QString dcmfile = it.next();
        AnonymizeDicomFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags, msg);
    }

    return true;
}


/* ------------------------------------------------- */
/* --------- GetDicomModality ---------------------- */
/* ------------------------------------------------- */
QString squirrelImageIO::GetDicomModality(QString f)
{
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (!r.CanRead()) {
        return "NOTDICOM";
    }
    gdcm::StringFilter sf;
    sf = gdcm::StringFilter();
    sf.SetFile(r.GetFile());
    std::string s = sf.ToString(gdcm::Tag(0x0008,0x0060));

    QString qs = s.c_str();

    return qs;
}


/* ---------------------------------------------------------- */
/* --------- GetImageFileTags ------------------------------- */
/* ---------------------------------------------------------- */
bool squirrelImageIO::GetImageFileTags(QString f, QString bindir, bool enablecsa, QHash<QString, QString> &tags, QString &msg) {

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
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (r.Read()) {
        /* ---------- it's a readable DICOM file ---------- */
        gdcm::StringFilter sf;
        sf = gdcm::StringFilter();
        sf.SetFile(r.GetFile());

        tags["FileType"] = "DICOM";

        /* get all of the DICOM tags...
         * we're not using an iterator because we want to know exactly what tags we have and dont have */

        tags["FileMetaInformationGroupLength"] =	QString(sf.ToString(gdcm::Tag(0x0002,0x0000)).c_str()).trimmed(); /* FileMetaInformationGroupLength */
        tags["FileMetaInformationVersion"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0001)).c_str()).trimmed(); /* FileMetaInformationVersion */
        tags["MediaStorageSOPClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0002)).c_str()).trimmed(); /* MediaStorageSOPClassUID */
        tags["MediaStorageSOPInstanceUID"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0003)).c_str()).trimmed(); /* MediaStorageSOPInstanceUID */
        tags["TransferSyntaxUID"] =					QString(sf.ToString(gdcm::Tag(0x0002,0x0010)).c_str()).trimmed(); /* TransferSyntaxUID */
        tags["ImplementationClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0012)).c_str()).trimmed(); /* ImplementationClassUID */
        tags["ImplementationVersionName"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0013)).c_str()).trimmed(); /* ImplementationVersionName */

        tags["SpecificCharacterSet"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0005)).c_str()).trimmed(); /* SpecificCharacterSet */
        tags["ImageType"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0008)).c_str()).trimmed(); /* ImageType */
        tags["InstanceCreationDate"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0012)).c_str()).trimmed(); /* InstanceCreationDate */
        tags["InstanceCreationTime"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0013)).c_str()).trimmed(); /* InstanceCreationTime */
        tags["SOPClassUID"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0016)).c_str()).trimmed(); /* SOPClassUID */
        tags["SOPInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0018)).c_str()).trimmed(); /* SOPInstanceUID */
        tags["StudyDate"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0020)).c_str()).trimmed(); /* StudyDate */
        tags["SeriesDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0021)).c_str()).trimmed(); /* SeriesDate */
        tags["AcquisitionDate"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0022)).c_str()).trimmed(); /* AcquisitionDate */
        tags["ContentDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0023)).c_str()).trimmed(); /* ContentDate */
        tags["StudyTime"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0030)).c_str()).trimmed(); /* StudyTime */
        tags["SeriesTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0031)).c_str()).trimmed(); /* SeriesTime */
        tags["AcquisitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0032)).c_str()).trimmed(); /* AcquisitionTime */
        tags["ContentTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0033)).c_str()).trimmed(); /* ContentTime */
        tags["AccessionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0050)).c_str()).trimmed(); /* AccessionNumber */
        tags["Modality"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0060)).c_str()).trimmed(); /* Modality */
        tags["Manufacturer"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0070)).c_str()).trimmed(); /* Manufacturer */
        tags["InstitutionName"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0080)).c_str()).trimmed(); /* InstitutionName */
        tags["InstitutionAddress"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0081)).c_str()).trimmed(); /* InstitutionAddress */
        tags["ReferringPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x0090)).c_str()).trimmed(); /* ReferringPhysicianName */
        tags["StationName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1010)).c_str()).trimmed(); /* StationName */
        tags["StudyDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x1030)).c_str()).trimmed(); /* StudyDescription */
        tags["SeriesDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x103E)).c_str()).trimmed(); /* SeriesDescription */
        tags["InstitutionalDepartmentName"] =		QString(sf.ToString(gdcm::Tag(0x0008,0x1040)).c_str()).trimmed(); /* InstitutionalDepartmentName */
        tags["PerformingPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x1050)).c_str()).trimmed(); /* PerformingPhysicianName */
        tags["OperatorsName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1070)).c_str()).trimmed(); /* OperatorsName */
        tags["ManufacturerModelName"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x1090)).c_str()).trimmed(); /* ManufacturerModelName */
        tags["SourceImageSequence"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x2112)).c_str()).trimmed(); /* SourceImageSequence */

        tags["PatientName"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0010)).c_str()).trimmed(); /* PatientName */
        tags["PatientID"] =							QString(sf.ToString(gdcm::Tag(0x0010,0x0020)).c_str()).trimmed(); /* PatientID */
        tags["PatientBirthDate"] =					QString(sf.ToString(gdcm::Tag(0x0010,0x0030)).c_str()).trimmed(); /* PatientBirthDate */
        tags["PatientSex"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0040)).c_str()).trimmed().left(1); /* PatientSex */
        tags["PatientAge"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1010)).c_str()).trimmed(); /* PatientAge */
        tags["PatientSize"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1020)).c_str()).trimmed(); /* PatientSize */
        tags["PatientWeight"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1030)).c_str()).trimmed(); /* PatientWeight */

        tags["ContrastBolusAgent"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0010)).c_str()).trimmed(); /* ContrastBolusAgent */
        tags["KVP"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x0060)).c_str()).trimmed(); /* KVP */
        tags["DataCollectionDiameter"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0090)).c_str()).trimmed(); /* DataCollectionDiameter */
        tags["ContrastBolusRoute"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1040)).c_str()).trimmed(); /* ContrastBolusRoute */
        tags["RotationDirection"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1140)).c_str()).trimmed(); /* RotationDirection */
        tags["ExposureTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1150)).c_str()).trimmed(); /* ExposureTime */
        tags["XRayTubeCurrent"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1151)).c_str()).trimmed(); /* XRayTubeCurrent */
        tags["FilterType"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1160)).c_str()).trimmed(); /* FilterType */
        tags["GeneratorPower"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1170)).c_str()).trimmed(); /* GeneratorPower */
        tags["ConvolutionKernel"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1210)).c_str()).trimmed(); /* ConvolutionKernel */

        tags["BodyPartExamined"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0015)).c_str()).trimmed(); /* BodyPartExamined */
        tags["ScanningSequence"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0020)).c_str()).trimmed(); /* ScanningSequence */
        tags["SequenceVariant"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0021)).c_str()).trimmed(); /* SequenceVariant */
        tags["ScanOptions"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0022)).c_str()).trimmed(); /* ScanOptions */
        tags["MRAcquisitionType"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0023)).c_str()).trimmed(); /* MRAcquisitionType */
        tags["SequenceName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0024)).c_str()).trimmed(); /* SequenceName */
        tags["AngioFlag"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0025)).c_str()).trimmed(); /* AngioFlag */
        tags["SliceThickness"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0050)).c_str()).trimmed(); /* SliceThickness */
        tags["RepetitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0080)).c_str()).trimmed(); /* RepetitionTime */
        tags["EchoTime"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0081)).c_str()).trimmed(); /* EchoTime */
        tags["InversionTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0082)).c_str()).trimmed(); /* InversionTime */
        tags["NumberOfAverages"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0083)).c_str()).trimmed(); /* NumberOfAverages */
        tags["ImagingFrequency"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0084)).c_str()).trimmed(); /* ImagingFrequency */
        tags["ImagedNucleus"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0085)).c_str()).trimmed(); /* ImagedNucleus */
        tags["EchoNumbers"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0086)).c_str()).trimmed(); /* EchoNumbers */
        tags["MagneticFieldStrength"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0087)).c_str()).trimmed(); /* MagneticFieldStrength */
        tags["SpacingBetweenSlices"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0088)).c_str()).trimmed(); /* SpacingBetweenSlices */
        tags["NumberOfPhaseEncodingSteps"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x0089)).c_str()).trimmed(); /* NumberOfPhaseEncodingSteps */
        tags["EchoTrainLength"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0091)).c_str()).trimmed(); /* EchoTrainLength */
        tags["PercentSampling"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0093)).c_str()).trimmed(); /* PercentSampling */
        tags["PercentPhaseFieldOfView"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0094)).c_str()).trimmed(); /* PercentPhaseFieldOfView */
        tags["PixelBandwidth"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0095)).c_str()).trimmed(); /* PixelBandwidth */
        tags["DeviceSerialNumber"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1000)).c_str()).trimmed(); /* DeviceSerialNumber */
        tags["SoftwareVersions"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1020)).c_str()).trimmed(); /* SoftwareVersions */
        tags["ProtocolName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1030)).c_str()).trimmed(); /* ProtocolName */
        tags["TransmitCoilName"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1251)).c_str()).trimmed(); /* TransmitCoilName */
        tags["AcquisitionMatrix"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1310)).c_str()).trimmed().left(20); /* AcquisitionMatrix */
        tags["InPlanePhaseEncodingDirection"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x1312)).c_str()).trimmed(); /* InPlanePhaseEncodingDirection */
        tags["FlipAngle"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x1314)).c_str()).trimmed(); /* FlipAngle */
        tags["VariableFlipAngleFlag"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1315)).c_str()).trimmed(); /* VariableFlipAngleFlag */
        tags["SAR"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1316)).c_str()).trimmed(); /* SAR */
        tags["dBdt"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1318)).c_str()).trimmed(); /* dBdt */
        tags["PatientPosition"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x5100)).c_str()).trimmed(); /* PatientPosition */

        tags["Unknown Tag & Data"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1009)).c_str()).trimmed(); /* Unknown Tag & Data */
        tags["NumberOfImagesInMosaic"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100A)).c_str()).trimmed(); /* NumberOfImagesInMosaic*/
        tags["SliceMeasurementDuration"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100B)).c_str()).trimmed(); /* SliceMeasurementDuration*/
        tags["B_value"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x100C)).c_str()).trimmed(); /* B_value*/
        tags["DiffusionDirectionality"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100D)).c_str()).trimmed(); /* DiffusionDirectionality*/
        tags["DiffusionGradientDirection"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x100E)).c_str()).trimmed(); /* DiffusionGradientDirection*/
        tags["GradientMode"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x100F)).c_str()).trimmed(); /* GradientMode*/
        tags["FlowCompensation"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1011)).c_str()).trimmed(); /* FlowCompensation*/
        tags["TablePositionOrigin"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1012)).c_str()).trimmed(); /* TablePositionOrigin*/
        tags["ImaAbsTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1013)).c_str()).trimmed(); /* ImaAbsTablePosition*/
        tags["ImaRelTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1014)).c_str()).trimmed(); /* ImaRelTablePosition*/
        tags["SlicePosition_PCS"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1015)).c_str()).trimmed(); /* SlicePosition_PCS*/
        tags["TimeAfterStart"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1016)).c_str()).trimmed(); /* TimeAfterStart*/
        tags["SliceResolution"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1017)).c_str()).trimmed(); /* SliceResolution*/
        tags["RealDwellTime"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1018)).c_str()).trimmed(); /* RealDwellTime*/
        tags["RBMoCoTrans"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1025)).c_str()).trimmed(); /* RBMoCoTrans*/
        tags["RBMoCoRot"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1026)).c_str()).trimmed(); /* RBMoCoRot*/
        tags["B_matrix"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1027)).c_str()).trimmed(); /* B_matrix*/
        tags["BandwidthPerPixelPhaseEncode"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x1028)).c_str()).trimmed(); /* BandwidthPerPixelPhaseEncode*/
        tags["MosaicRefAcqTimes"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1029)).c_str()).trimmed(); /* MosaicRefAcqTimes*/

        tags["StudyInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000D)).c_str()).trimmed(); /* StudyInstanceUID */
        tags["SeriesInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000E)).c_str()).trimmed(); /* SeriesInstanceUID */
        tags["StudyID"] =							QString(sf.ToString(gdcm::Tag(0x0020,0x0010)).c_str()).trimmed(); /* StudyID */
        tags["SeriesNumber"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x0011)).c_str()).trimmed(); /* SeriesNumber */
        tags["AcquisitionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0012)).c_str()).trimmed(); /* AcquisitionNumber */
        tags["InstanceNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0013)).c_str()).trimmed(); /* InstanceNumber */
        tags["ImagePositionPatient"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0032)).c_str()).trimmed(); /* ImagePositionPatient */
        tags["ImageOrientationPatient"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0037)).c_str()).trimmed(); /* ImageOrientationPatient */
        tags["FrameOfReferenceUID"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0052)).c_str()).trimmed(); /* FrameOfReferenceUID */
        tags["NumberOfTemporalPositions"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* NumberOfTemporalPositions */
        tags["ImagesInAcquisition"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* ImagesInAcquisition */
        tags["PositionReferenceIndicator"] =		QString(sf.ToString(gdcm::Tag(0x0020,0x1040)).c_str()).trimmed(); /* PositionReferenceIndicator */
        tags["SliceLocation"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x1041)).c_str()).trimmed(); /* SliceLocation */

        tags["SamplesPerPixel"] =					QString(sf.ToString(gdcm::Tag(0x0028,0x0002)).c_str()).trimmed(); /* SamplesPerPixel */
        tags["PhotometricInterpretation"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0004)).c_str()).trimmed(); /* PhotometricInterpretation */
        tags["Rows"] =								QString(sf.ToString(gdcm::Tag(0x0028,0x0010)).c_str()).trimmed(); /* Rows */
        tags["Columns"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0011)).c_str()).trimmed(); /* Columns */
        tags["PixelSpacing"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0030)).c_str()).trimmed(); /* PixelSpacing */
        tags["BitsAllocated"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0100)).c_str()).trimmed(); /* BitsAllocated */
        tags["BitsStored"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0101)).c_str()).trimmed(); /* BitsStored */
        tags["HighBit"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0102)).c_str()).trimmed(); /* HighBit */
        tags["PixelRepresentation"] =				QString(sf.ToString(gdcm::Tag(0x0028,0x0103)).c_str()).trimmed(); /* PixelRepresentation */
        tags["SmallestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0106)).c_str()).trimmed(); /* SmallestImagePixelValue */
        tags["LargestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0107)).c_str()).trimmed(); /* LargestImagePixelValue */
        tags["WindowCenter"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1050)).c_str()).trimmed(); /* WindowCenter */
        tags["WindowWidth"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1051)).c_str()).trimmed(); /* WindowWidth */
        tags["WindowCenterWidthExplanation"] =		QString(sf.ToString(gdcm::Tag(0x0028,0x1055)).c_str()).trimmed(); /* WindowCenterWidthExplanation */

        tags["RequestingPhysician"] =				QString(sf.ToString(gdcm::Tag(0x0032,0x1032)).c_str()).trimmed(); /* RequestingPhysician */
        tags["RequestedProcedureDescription"] =		QString(sf.ToString(gdcm::Tag(0x0032,0x1060)).c_str()).trimmed(); /* RequestedProcedureDescription */

        tags["PerformedProcedureStepStartDate"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0244)).c_str()).trimmed(); /* PerformedProcedureStepStartDate */
        tags["PerformedProcedureStepStartTime"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0245)).c_str()).trimmed(); /* PerformedProcedureStepStartTime */
        tags["PerformedProcedureStepID"] =			QString(sf.ToString(gdcm::Tag(0x0040,0x0253)).c_str()).trimmed(); /* PerformedProcedureStepID */
        tags["PerformedProcedureStepDescription"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0254)).c_str()).trimmed(); /* PerformedProcedureStepDescription */
        tags["CommentsOnThePerformedProcedureSte"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0280)).c_str()).trimmed(); /* CommentsOnThePerformedProcedureSte */

        tags["TimeOfAcquisition"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100A)).c_str()).trimmed(); /* TimeOfAcquisition*/
        tags["AcquisitionMatrixText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x100B)).c_str()).trimmed(); /* AcquisitionMatrixText*/
        tags["FieldOfView"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100C)).c_str()).trimmed(); /* FieldOfView*/
        tags["SlicePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100D)).c_str()).trimmed(); /* SlicePositionText*/
        tags["ImageOrientation"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100E)).c_str()).trimmed(); /* ImageOrientation*/
        tags["CoilString"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100F)).c_str()).trimmed(); /* CoilString*/
        tags["ImaPATModeText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1011)).c_str()).trimmed(); /* ImaPATModeText*/
        tags["TablePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1012)).c_str()).trimmed(); /* TablePositionText*/
        tags["PositivePCSDirections"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1013)).c_str()).trimmed(); /* PositivePCSDirections*/
        tags["ImageTypeText"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x1016)).c_str()).trimmed(); /* ImageTypeText*/
        tags["SliceThicknessText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1017)).c_str()).trimmed(); /* SliceThicknessText*/
        tags["ScanOptionsText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1019)).c_str()).trimmed(); /* ScanOptionsText*/

        /* fix the study date */
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
            if (tags["StudyTime"].size() == 13)
                tags["StudyTime"] = tags["StudyTime"].left(6);

            if (tags["StudyTime"].size() == 6) {
                tags["StudyTime"].insert(4,':');
                tags["StudyTime"].insert(2,':');
            }
        }

        /* some images may not have a series date/time, so substitute the studyDateTime for seriesDateTime */
        if (tags["SeriesTime"] == "")
            tags["SeriesTime"] = tags["StudyTime"];
        else {
            if (tags["SeriesTime"].size() == 13)
                tags["SeriesTime"] = tags["SeriesTime"].left(6);

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

        QString uniqueseries = tags["InstitutionName"] + tags["StationName"] + tags["Modality"] + tags["PatientName"] + tags["PatientBirthDate"] + tags["PatientSex"] + tags["StudyDateTime"] + tags["SeriesNumber"];
        tags["UniqueSeriesString"] = uniqueseries;

        /* attempt to get the Siemens CSA header info */
        tags["PhaseEncodeAngle"] = "";
        tags["PhaseEncodingDirectionPositive"] = "";
        if (enablecsa) {
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
            tags["PatientID"] = parts[0];
        }
        /* check if ET */
        else if (f.endsWith(".edf", Qt::CaseInsensitive)) {
            tags["FileType"] = "ET";
            tags["Modality"] = "ET";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            tags["PatientID"] = parts[0];
        }
        /* check if MR (Non-DICOM) analyze or nifti */
        else if ((f.endsWith(".nii", Qt::CaseInsensitive)) || (f.endsWith(".nii.gz", Qt::CaseInsensitive)) || (f.endsWith(".hdr", Qt::CaseInsensitive)) || (f.endsWith(".img", Qt::CaseInsensitive))) {
            tags["FileType"] = "NIFTI";
            tags["Modality"] = "NIFTI";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            tags["PatientID"] = parts[0];
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
                    if (line.contains("Patient name")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        tags["PatientID"] = parts[1].trimmed();
                    }
                    if (line.contains("Protocol name")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        tags["ProtocolName"] = parts[1].trimmed();
                    }
                    if (line.contains("MRSERIES", Qt::CaseInsensitive)) {
                        tags["Modality"] = "MR";
                    }
                }
                inputFile.close();
            }
        }
        else {
            /* unknown modality/filetype */
            return false;
        }
    }


    /* fix some of the fields to be amenable to the DB */
    if (tags["Modality"] == "")
        tags["Modality"] = "OT";
    QString StudyDate = ParseDate(tags["StudyDate"]);
    //QString StudyTime = ParseTime(tags["StudyTime"]);
    //QString SeriesDate = ParseDate(tags["SeriesDate"]);
    //QString SeriesTime = ParseTime(tags["SeriesTime"]);

    tags["StudyDateTime"] = tags["StudyDate"] + " " + tags["StudyTime"];
    tags["SeriesDateTime"] = tags["SeriesDate"] + " " + tags["SeriesTime"];
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

    return true;
}


/* ------------------------------------------------- */
/* --------- GetFileType --------------------------- */
/* ------------------------------------------------- */
void squirrelImageIO::GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol)
{
    fileModality = QString("");
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (r.CanRead()) {
        r.Read();
        fileType = QString("DICOM");
        gdcm::StringFilter sf;
        sf = gdcm::StringFilter();
        sf.SetFile(r.GetFile());
        std::string s;

        /* get modality */
        s = sf.ToString(gdcm::Tag(0x0008,0x0060));
        fileModality = QString(s.c_str());

        /* get patientID */
        s = sf.ToString(gdcm::Tag(0x0010,0x0020));
        filePatientID = QString(s.c_str());

        /* get protocol (seriesDesc) */
        s = sf.ToString(gdcm::Tag(0x0008,0x103E));
        fileProtocol = QString(s.c_str());
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
        }
        /* check if ET */
        else if (f.endsWith(".edf", Qt::CaseInsensitive)) {
            //WriteLog("Found an ET file [" + f + "]");
            fileType = "ET";
            fileModality = "ET";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
        }
        /* check if MR (Non-DICOM) analyze or nifti */
        else if ((f.endsWith(".nii", Qt::CaseInsensitive)) || (f.endsWith(".nii.gz", Qt::CaseInsensitive)) || (f.endsWith(".hdr", Qt::CaseInsensitive)) || (f.endsWith(".img", Qt::CaseInsensitive))) {
            //WriteLog("Found an analyze or Nifti image [" + f + "]");
            fileType = "NIFTI";
            fileModality = "NIFTI";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
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
