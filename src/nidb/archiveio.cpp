/* ------------------------------------------------------------------------------
  NIDB archiveio.h
  Copyright (C) 2004 - 2020
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

#include "archiveio.h"
#include "subject.h"

/* ---------------------------------------------------------- */
/* --------- archiveIO -------------------------------------- */
/* ---------------------------------------------------------- */
archiveIO::archiveIO(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~archiveIO ------------------------------------- */
/* ---------------------------------------------------------- */
archiveIO::~archiveIO()
{

}


/* ---------------------------------------------------------- */
/* --------- CreateSubject ---------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, QStringList &msgs, int &subjectRowID, QString &subjectRealUID) {
    int count(0);

    msgs << n->WriteLog("Creating a new subject. Searching for an unused UID");
    /* create a new subjectRealUID */
    do {
        subjectRealUID = n->CreateUID("S",3);
        QSqlQuery q2;
        q2.prepare("select uid from subjects where uid = :uid");
        q2.bindValue(":uid", subjectRealUID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        count = q2.size();
    } while (count > 0);

    msgs << n->WriteLog("Found an unused UID [" + subjectRealUID + "]");

    QString sqlstring = "insert into subjects (name, birthdate, gender, weight, height, uid, uuid) values (:patientname, :patientdob, :patientsex, :weight, :size, :uid, ucase(md5(concat('" + n->RemoveNonAlphaNumericChars(PatientName) + "', '" + n->RemoveNonAlphaNumericChars(PatientBirthDate) + "','" + n->RemoveNonAlphaNumericChars(PatientSex) + "'))) )";
    QSqlQuery q2;
    q2.prepare(sqlstring);
    q2.bindValue(":patientname",PatientName);
    q2.bindValue(":patientdob",PatientBirthDate);
    q2.bindValue(":patientsex",PatientSex);
    q2.bindValue(":weight",PatientWeight);
    q2.bindValue(":size",PatientSize);
    q2.bindValue(":uid",subjectRealUID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    subjectRowID = q2.lastInsertId().toInt();

    msgs << n->WriteLog("Added new subject [" + subjectRealUID + "]");

    /* insert the PatientID as an alternate UID */
    if (PatientID != "") {
        QSqlQuery q2;
        q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
        q2.bindValue(":subjectid",subjectRowID);
        q2.bindValue(":patientid",PatientID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        msgs << n->WriteLog("Added alternate UID [" + PatientID + "]");
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertDICOMSeries ------------------------------ */
/* ---------------------------------------------------------- */
bool archiveIO::InsertDICOMSeries(int importid, int existingSubjectID, int existingStudyID, int existingSeriesID, QString subjectMatchCriteria, QString studyMatchCriteria, QString seriesMatchCriteria, int destProjectID, int destSiteID, QString altUIDstr, QString seriesNotes, QStringList files, QString &msg) {

    QStringList msgs;
    //msgs << n->WriteLog(QString("----- Inside InsertDICOMSeries(importID %1  uploadID %2, <array of size[%3]>) with [%3] files -----").arg(importid).arg(uploadid).arg(files.size()));
    //msgs << n->WriteLog(QString("----- Inside InsertDICOMSeries(importID %1  <array of size[%2]>) with [%2] files -----").arg(importid).arg(files.size()));

    if (files.size() < 1) {
        msgs << n->WriteLog("This DICOM series has no files");
        msg += msgs.join("\n");
        return false;
    }

    if (!QFile::exists(files[0])) {
        msgs << n->WriteLog(QString("File [%1] does not exist - check 0!").arg(files[0]));
        msg += msgs.join("\n");
        return 0;
    }

    n->SortQStringListNaturally(files);

    if (!QFile::exists(files[0])) {
        msgs << n->WriteLog(QString("File [%1] does not exist - check 1!").arg(files[0]));
        msg += msgs.join("\n");
        return 0;
    }

    /* import log variables */
    QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
    //double IL_patientage_orig(0.0);
    int IL_seriesnumber_orig(0);
    QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
    QString IL_subject_uid;
    QString IL_project_number;
    int IL_seriescreated(0), IL_studycreated(0), IL_subjectcreated(0), IL_familycreated(0), IL_enrollmentcreated(0), IL_overwrote_existing(0);

    int subjectRowID(0);
    QString subjectRealUID;
    QString familyRealUID;
    int familyRowID(0);
    int projectRowID(0);
    int enrollmentRowID(0);
    int studyRowID(0);
    int seriesRowID(0);
    QString costcenter;
    int studynum(0);

    //int importInstanceID(0);
    //int importSiteID(0);
    //int importProjectID(0);
    //int importPermanent(0);
    //int importAnonymize(0);
    //int importMatchIDOnly(1); /* match by ID first, by default */
    //QString importUUID;
    //QString importSeriesNotes;
    //QString importAltUIDs;

    /* if there is an importid, check to see how that thing is doing */
//    if (importid > 0) {
//        QSqlQuery q;
//        q.prepare("select * from import_requests where importrequest_id = :importid");
//        q.bindValue(":importid", importid);
//        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
//        if (q.size() > 0) {
//            q.first();
//            QString status = q.value("import_status").toString();
//            //importInstanceID = q.value("import_instanceid").toInt();
//            importSiteID = q.value("import_siteid").toInt();
//            importProjectID = q.value("import_projectid").toInt();
//            //importPermanent = q.value("import_permanent").toInt();
//            //importAnonymize = q.value("import_anonymize").toInt();
//            importMatchIDOnly = q.value("import_matchidonly").toInt();
//            //importUUID = q.value("import_uuid").toString();
//            importSeriesNotes = q.value("import_seriesnotes").toString();
//            importAltUIDs = q.value("import_altuids").toString();
//        }
//    }

    /* get all the DICOM tags */
    QHash<QString, QString> tags;
    QString filetype;
    QString f = files[0];

    if (!QFile::exists(f)) {
        msgs << n->WriteLog(QString("File [%1] does not exist - check A!").arg(f));
        msg += msgs.join("\n");
        return 0;
    }

    if (n->GetImageFileTags(f, tags)) {
        if (!QFile::exists(f)) {
            msgs << n->WriteLog(QString("File [%1] does not exist - check B!").arg(f));
            msg += msgs.join("\n");
            return 0;
        }
    }

    QString InstitutionName = tags["InstitutionName"];
    QString InstitutionAddress = tags["InstitutionAddress"];
    QString Modality = tags["Modality"];
    QString StationName = tags["StationName"];
    QString Manufacturer = tags["Manufacturer"];
    QString ManufacturersModelName = tags["ManufacturersModelName"];
    QString OperatorsName = tags["OperatorsName"];
    QString PatientID = tags["PatientID"].toUpper();
    QString PatientBirthDate = tags["PatientBirthDate"];
    QString PatientName = tags["PatientName"];
    QString PatientSex = tags["PatientSex"];
    double PatientWeight = tags["PatientWeight"].toDouble();
    double PatientSize = tags["PatientSize"].toDouble();
    QString PatientAgeStr = tags["PatientAge"];
    double PatientAge(0.0);
    QString PerformingPhysiciansName = tags["PerformingPhysicianName"];
    QString ProtocolName = tags["ProtocolName"];
    QString SeriesDate = tags["SeriesDate"];
    int SeriesNumber = tags["SeriesNumber"].toInt();
    QString SeriesTime = tags["SeriesTime"];
    QString StudyDate = tags["StudyDate"];
    QString StudyDescription = tags["StudyDescription"];
    QString SeriesDescription = tags["SeriesDescription"];
    QString StudyTime = tags["StudyTime"];
    int Rows = tags["Rows"].toInt();
    int Columns = tags["Columns"].toInt();
    //int AccessionNumber = tags["AccessionNumber"].toInt();
    double SliceThickness = tags["SliceThickness"].toDouble();
    QString PixelSpacing = tags["PixelSpacing"];
    int NumberOfTemporalPositions = tags["NumberOfTemporalPositions"].toInt();
    int ImagesInAcquisition = tags["ImagesInAcquisition"].toInt();
    QString SequenceName = tags["SequenceName"];
    QString ImageType = tags["ImageType"];
    QString ImageComments = tags["ImageComments"];

    /* MR specific tags */
    double MagneticFieldStrength = tags["MagneticFieldStrength"].toDouble();
    int RepetitionTime = tags["RepetitionTime"].toInt();
    double FlipAngle = tags["FlipAngle"].toDouble();
    double EchoTime = tags["EchoTime"].toDouble();
    QString AcquisitionMatrix = tags["AcquisitionMatrix"];
    QString InPlanePhaseEncodingDirection = tags["InPlanePhaseEncodingDirection"];
    double InversionTime = tags["InversionTime"].toDouble();
    double PercentSampling = tags["PercentSampling"].toDouble();
    double PercentPhaseFieldOfView = tags["PercentPhaseFieldOfView"].toDouble();
    double PixelBandwidth = tags["PixelBandwidth"].toDouble();
    double SpacingBetweenSlices = tags["SpacingBetweenSlices"].toDouble();
    QString PhaseEncodeAngle;
    QString PhaseEncodingDirectionPositive;

    /* attempt to get the phase encode angle (In Plane Rotation) from the siemens CSA header */
    QFile df(files[0]);

    /* open the dicom file as a text file, since part of the CSA header is stored as text, not binary */
    if (df.open(QIODevice::ReadOnly | QIODevice::Text)) {

        QTextStream in(&df);
        while (!in.atEnd()) {
            QString line = in.readLine();
            if (line.startsWith("sSliceArray.asSlice[0].dInPlaneRot") && (line.size() < 70)) {
                /* make sure the line does not contain any non-printable ASCII control characters */
                if (!line.contains(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")))) {
                    int idx = line.indexOf(".dInPlaneRot");
                    line = line.mid(idx,23);
                    QStringList vals = line.split(QRegExp("\\s+"));
                    if (vals.size() > 0)
                        PhaseEncodeAngle = vals.last().trimmed();
                    break;
                }
            }
        }
        msgs << n->WriteLog(QString("Found PhaseEncodeAngle of [%1]").arg(PhaseEncodeAngle));
        df.close();
    }

    /* get the other part of the CSA header, the PhaseEncodingDirectionPositive value */
    QString systemstring = QString("%1/bin/./gdcmdump -C %2 | grep PhaseEncodingDirectionPositive").arg(n->cfg["nidbdir"]).arg(f);
    QString csaheader = n->SystemCommand(systemstring, false);
    QStringList parts = csaheader.split(",");
    QString val;
    if (parts.size() == 5) {
        val = parts[4];
        val.replace("Data '","",Qt::CaseInsensitive);
        val.replace("'","");
        if (val.trimmed() == "Data")
            val = "";
        PhaseEncodingDirectionPositive = val.trimmed();
    }
    n->WriteLog(QString("Found PhaseEncodingDirectionPositive of [%1]").arg(PhaseEncodingDirectionPositive));

    /* CT specific tags */
    QString ContrastBolusAgent = tags["ContrastBolusAgent"];
    QString BodyPartExamined = tags["BodyPartExamined"];
    QString ScanOptions = tags["ScanOptions"];
    double KVP = tags["KVP"].toDouble();
    double DataCollectionDiameter = tags["DataCollectionDiameter"].toDouble();
    QString ContrastBolusRoute = tags["ContrastBolusRoute"];
    QString RotationDirection = tags["RotationDirection"];
    double ExposureTime = tags["ExposureTime"].toDouble();
    double XRayTubeCurrent = tags["XRayTubeCurrent"].toDouble();
    QString FilterType = tags["FilterType"];
    double GeneratorPower = tags["GeneratorPower"].toDouble();
    QString ConvolutionKernel = tags["ConvolutionKernel"];

    /* fix some of the fields to be amenable to the DB */
    if (Modality == "")
        Modality = "OT";
    StudyDate = n->ParseDate(StudyDate);
    StudyTime = n->ParseTime(StudyTime);
    SeriesDate = n->ParseDate(SeriesDate);
    SeriesTime = n->ParseTime(SeriesTime);

    QString StudyDateTime = tags["StudyDateTime"] = StudyDate + " " + StudyTime;
    QString SeriesDateTime = tags["SeriesDateTime"] = SeriesDate + " " + SeriesTime;
    QStringList pix = PixelSpacing.split("\\");
    int pixelX(0);
    int pixelY(0);
    if (pix.size() == 2) {
        pixelX = pix[0].toInt();
        pixelY = pix[1].toInt();
    }
    QStringList amat = AcquisitionMatrix.split(" ");
    int mat1(0);
    //int mat2(0);
    //int mat3(0);
    int mat4(0);
    if (amat.size() == 4) {
        mat1 = amat[0].toInt();
        //mat2 = amat[1].toInt();
        //mat3 = amat[2].toInt();
        mat4 = amat[3].toInt();
    }
    if (SeriesNumber == 0) {
        QString timestamp = SeriesTime;
        timestamp.replace(":","").replace("-","").replace(" ","");
        SeriesNumber = timestamp.toInt();
    }

    /* fix patient birthdate */
    PatientBirthDate = n->ParseDate(PatientBirthDate);

    /* get patient age */
    PatientAge = GetPatientAge(PatientAgeStr, StudyDate, PatientBirthDate);

    /* remove any non-printable ASCII control characters */
    PatientName.replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"");
    PatientSex.replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"");

    if (PatientID == "") {
        PatientID = "(empty)";
        n->WriteLog(n->SystemCommand("exiftool " + f));
    }

    if (PatientName == "")
        PatientName = "(empty)";

    if (StudyDescription == "")
        StudyDescription = "(empty)";

    if (PatientSex == "")
        PatientName = "U";

    /* get the costcenter */
    costcenter = GetCostCenter(StudyDescription);

    msgs << n->WriteLog(PatientID + " - " + StudyDescription);

    /* set the import log variables */
    IL_modality_orig = Modality;
    IL_patientname_orig = PatientName;
    IL_patientdob_orig = PatientBirthDate;
    IL_patientsex_orig = PatientSex;
    IL_stationname_orig = StationName;
    IL_institution_orig = InstitutionName + "-" + InstitutionAddress;
    IL_studydatetime_orig = StudyDate + " " + StudyTime;
    IL_seriesdatetime_orig = SeriesDate + " " + SeriesTime;
    IL_seriesnumber_orig = SeriesNumber;
    IL_studydesc_orig = StudyDescription;
    IL_seriesdesc_orig = SeriesDescription;
    IL_protocolname_orig = ProtocolName;
    //IL_patientage_orig = PatientAge;

    /* get the ID search string */
    QString SQLIDs = CreateIDSearchList(PatientID, altUIDstr);
    QStringList altuidlist;
    if (altUIDstr != "")
        altuidlist = altUIDstr.split(",");

    /* check the alternate UIDs */
    foreach (QString altuid, altuidlist) {
        if (altuid.trimmed().size() > 254)
            msgs << "Alternate UID [" + altuid.left(255) + "...] is longer than 255 characters and will be truncated";
    }

    /* get the subject (create it if it wasn't found) */
    //QString subjectCriteria("PatientID");
    //int existingSubjectID(-1);
    bool subjectCreated(false);
    QString m;
    GetSubject(subjectMatchCriteria, existingSubjectID, PatientID, PatientName, PatientSex, PatientBirthDate, PatientWeight, PatientSize, SQLIDs, subjectRowID, subjectRealUID, subjectCreated, m);

    /* check if the project and subject exist */
    msgs << n->WriteLog("Checking if the project exists");
    int projectcount(0);
    QSqlQuery q;
    q.prepare("select count(*) from `projects` where project_costcenter = :costcenter");
    q.bindValue(":costcenter", costcenter);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        projectcount = q.value("projectcount").toInt();
    }
    else {
        costcenter = "999999";
    }

    n->WriteLog("subjectRealUID ["+subjectRealUID+"]");
    if (subjectRealUID == "") {
        msgs << n->WriteLog("Error finding/creating subject. UID is blank");
        msg += msgs.join("\n");
        return 0;
    }

    /* check if the subject is part of a family, if not create a family for it */
    QSqlQuery q2;
    q2.prepare("select family_id from family_members where subject_id = :subjectid");
    q2.bindValue(":subjectid", subjectRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        familyRowID = q2.value("family_id").toInt();
        IL_familycreated = 0;
    }
    else {
        int count = 0;

        /* create family UID */
        msgs << n->WriteLog("Subject is not part of family, creating a unique family UID");
        do {
            familyRealUID = n->CreateUID("F");
            QSqlQuery q2;
            q2.prepare("SELECT * FROM `families` WHERE family_uid = :familyuid");
            q2.bindValue(":familyuid", familyRealUID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            count = q2.size();
        } while (count > 0);

        /* create familyRowID if it doesn't exist */
        QSqlQuery q2;
        q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyRealUID, now(), :familyname)");
        q2.bindValue(":familyuid", familyRealUID);
        q2.bindValue(":familyname", "Proband-" + subjectRealUID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        familyRowID = q2.lastInsertId().toInt();

        q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
        q2.bindValue(":familyid", familyRowID);
        q2.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

        IL_familycreated = 1;
    }

    /* get the projectRowID */
    if (destProjectID == 0) {
        q2.prepare("select project_id from projects where project_costcenter = :costcenter");
        q2.bindValue(":costcenter", costcenter);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            projectRowID = q2.value("project_id").toInt();
        }
    }
    else {
        /* need to create the project if it doesn't exist */
        msgs << n->WriteLog(QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(destProjectID));
        projectRowID = destProjectID;
    }

    /* check if the subject is enrolled in the project */
    q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
    q2.bindValue(":subjectid", subjectRowID);
    q2.bindValue(":projectid", projectRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        enrollmentRowID = q2.value("enrollment_id").toInt();
        msgs << n->WriteLog(QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID));
        IL_enrollmentcreated = 0;
    }
    else {
        /* create enrollmentRowID if it doesn't exist */
        q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
        q2.bindValue(":subjectid", subjectRowID);
        q2.bindValue(":projectid", projectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        enrollmentRowID = q2.lastInsertId().toInt();

        msgs << n->WriteLog(QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID));
        IL_enrollmentcreated = 1;
    }

    /* update alternate IDs, if there are any */
    if (altuidlist.size() > 0) {
        foreach (QString altuid, altuidlist) {
            if (altuid.trimmed() != "") {
                q2.prepare("replace into subject_altuid (subject_id, altuid, enrollment_id) values (:subjectid, :altuid, :enrollmentid)");
                q2.bindValue(":subjectid", subjectRowID);
                q2.bindValue(":altuid", altuid);
                q2.bindValue(":enrollmentid", enrollmentRowID);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            }
        }
    }

    /* now determine if this study exists or not...
     * basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
     * also checks the accession number against the study_num to see if this study was pre-registered
     * HOWEVER, if there is an instanceID specified, we should only match a study that's part of an enrollment in the same instance */
    bool studyFound = false;
    msgs << n->WriteLog(QString("Checking if this study exists: enrollmentID [%1]  StudyDateTime [%2]  Modality [%3] StationName [%4]").arg(enrollmentRowID).arg(StudyDateTime).arg(Modality).arg(StationName));

    q2.prepare("select study_id, study_num from studies where enrollment_id = :enrollmentid and (((study_datetime between date_sub('" + StudyDateTime + "', interval 30 second) and date_add('" + StudyDateTime + "', interval 30 second)) and study_modality = :modality and study_site = :stationname))");
    q2.bindValue(":enrollmentid", enrollmentRowID);
    //q2.bindValue(":accessionnum", AccessionNumber);
    q2.bindValue(":modality", Modality);
    q2.bindValue(":stationname", StationName);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        while (q2.next()) {
            int study_id = q2.value("study_id").toInt();
            studynum = q2.value("study_num").toInt();
            studyFound = true;
            studyRowID = study_id;

            QSqlQuery q4;
            msgs << n->WriteLog(QString("StudyID [%1] exists, updating").arg(study_id));
            q4.prepare("update studies set study_modality = :modality, study_datetime = '" + StudyDateTime + "', study_ageatscan = :patientage, study_height = :height, study_weight = :weight, study_desc = :studydesc, study_operator = :operator, study_performingphysician = :physician, study_site = :stationname, study_nidbsite = :importsiteid, study_institution = :institution, study_status = 'complete' where study_id = :studyid");
            q4.bindValue(":modality", Modality);
            q4.bindValue(":patientage", PatientAge);
            q4.bindValue(":height", PatientSize);
            q4.bindValue(":weight", PatientWeight);
            q4.bindValue(":studydesc", StudyDescription);
            q4.bindValue(":operator", OperatorsName);
            q4.bindValue(":physician", PerformingPhysiciansName);
            q4.bindValue(":stationname", StationName);
            q4.bindValue(":importsiteid", destSiteID);
            q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
            q4.bindValue(":studyid", studyRowID);
            n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

            IL_studycreated = 0;
            break;
        }
    }
    if (!studyFound) {
        msgs << n->WriteLog("Study did not exist, creating new study");

        /* create studyRowID if it doesn't exist */
        q2.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
        q2.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            studynum = q2.value("study_num").toInt() + 1;
        }
        else
            studynum = 1;

        QSqlQuery q4;
        q4.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_desc, study_operator, study_performingphysician, study_site, study_nidbsite, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentid, :studynum, :patientid, :modality, '" + StudyDateTime + "', :patientage, :height, :weight, :studydesc, :operator, :physician, :stationname, :importsiteid, :institution, 'complete', 'import', now())");
        q4.bindValue(":enrollmentid", enrollmentRowID);
        q4.bindValue(":studynum", studynum);
        q4.bindValue(":patientid", PatientID);
        q4.bindValue(":modality", Modality);
        //q4.bindValue(":studydatetime", StudyDateTime);
        q4.bindValue(":patientage", PatientAge);
        q4.bindValue(":height", PatientSize);
        q4.bindValue(":weight", PatientWeight);
        q4.bindValue(":studydesc", StudyDescription);
        q4.bindValue(":operator", OperatorsName);
        q4.bindValue(":physician", PerformingPhysiciansName);
        q4.bindValue(":stationname", StationName);
        q4.bindValue(":importsiteid", destSiteID);
        q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
        q4.bindValue(":studyid", studyRowID);
        n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
        studyRowID = q4.lastInsertId().toInt();

        IL_studycreated = 1;
    }

    /* gather series information */
    int boldreps(1);
    int numfiles(1);
    numfiles = files.size();
    int zsize(1);
    QString mrtype = "structural";

    /* check if its an EPI sequence, but not a perfusion sequence */
    if (SequenceName.contains("epfid2d1_")) {
        if (ProtocolName.contains("perfusion", Qt::CaseInsensitive) || SequenceName.contains("ep2d_perf_tra")) { }
        else {
            mrtype = "epi";
            /* get the bold reps and attempt to get the z size */
            boldreps = numfiles;

            /* this method works ... sometimes */
            if ((mat1 > 0) && (mat4 > 0))
                zsize = (Rows/mat1)*(Columns/mat4); /* example (384/64)*(384/64) = 6*6 = 36 possible slices in a mosaic */
            else
                zsize = numfiles;
        }
    }
    else {
        zsize = numfiles;
    }
    /* if any of the DICOM fields were populated, use those instead */
    if (ImagesInAcquisition > 0)
        zsize = ImagesInAcquisition;
    if (NumberOfTemporalPositions > 0)
        boldreps = NumberOfTemporalPositions;

    /* insert or update the series based on modality */
    QString dbModality;
    if (Modality.toUpper() == "MR") {
        dbModality = "mr";
        q2.prepare("select mrseries_id from mr_series where study_id = :studyid and series_num = :seriesnum");
        q2.bindValue(":studyid", studyRowID);
        q2.bindValue(":seriesnum", SeriesNumber);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            seriesRowID = q2.value("mrseries_id").toInt();

            msgs << n->WriteLog(QString("This MR series [%1] exists, updating").arg(SeriesNumber));

            //int dimN(0), dimT(0), dimZ(0);
            //if (NumberOfTemporalPositions > 0) {
            //	dimN = 4;
            //	dimT = NumberOfTemporalPositions;
            //}
            //if (ImagesInAcquisition > 0)
            //	dimZ = ImagesInAcquisition;

            QString sqlstring = "update mr_series set series_datetime = '" + SeriesDateTime + "', series_desc = :SeriesDescription, series_protocol = :ProtocolName, series_sequencename = :SequenceName, series_tr = :RepetitionTime, series_te = :EchoTime,series_flip = :FlipAngle, phaseencodedir = :InPlanePhaseEncodingDirection, phaseencodeangle = :PhaseEncodeAngle, PhaseEncodingDirectionPositive = :PhaseEncodingDirectionPositive, series_spacingx = :pixelX,series_spacingy = :pixelY, series_spacingz = :SliceThickness, series_fieldstrength = :MagneticFieldStrength, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, series_ti = :InversionTime, percent_sampling = :PercentSampling, percent_phasefov = :PercentPhaseFieldOfView, acq_matrix = :AcquisitionMatrix, slicethickness = :SliceThickness, slicespacing = :SpacingBetweenSlices, bandwidth = :PixelBandwidth, image_type = :ImageType, image_comments = :ImageComments, bold_reps = :boldreps, numfiles = :numfiles, series_notes = :importSeriesNotes, series_status = 'complete' where mrseries_id = :seriesRowID";
            QSqlQuery q3;
            q3.prepare(sqlstring);
            q3.bindValue(":SeriesDescription", SeriesDescription);
            q3.bindValue(":ProtocolName", ProtocolName);
            q3.bindValue(":SequenceName", SequenceName);
            q3.bindValue(":RepetitionTime", RepetitionTime);
            q3.bindValue(":EchoTime", EchoTime);
            q3.bindValue(":FlipAngle", FlipAngle);
            q3.bindValue(":InPlanePhaseEncodingDirection", InPlanePhaseEncodingDirection);

            if (PhaseEncodeAngle == "") q3.bindValue(":PhaseEncodeAngle", QVariant(QVariant::Double)); /* for null values */
            else q3.bindValue(":PhaseEncodeAngle", PhaseEncodeAngle);

            if (PhaseEncodingDirectionPositive == "") q3.bindValue(":PhaseEncodingDirectionPositive", QVariant(QVariant::Int)); /* for null values */
            else q3.bindValue(":PhaseEncodingDirectionPositive", PhaseEncodingDirectionPositive);

            q3.bindValue(":pixelX", pixelX);
            q3.bindValue(":pixelY", pixelY);
            q3.bindValue(":SliceThickness", SliceThickness);
            q3.bindValue(":MagneticFieldStrength", MagneticFieldStrength);
            q3.bindValue(":Rows", Rows);
            q3.bindValue(":Columns", Columns);
            q3.bindValue(":zsize", zsize);
            q3.bindValue(":InversionTime", InversionTime);
            q3.bindValue(":PercentSampling", PercentSampling);
            q3.bindValue(":PercentPhaseFieldOfView", PercentPhaseFieldOfView);
            q3.bindValue(":AcquisitionMatrix", AcquisitionMatrix);
            q3.bindValue(":SliceThickness", SliceThickness);
            q3.bindValue(":SpacingBetweenSlices", SpacingBetweenSlices);
            q3.bindValue(":PixelBandwidth", PixelBandwidth);
            q3.bindValue(":ImageType", ImageType);
            q3.bindValue(":ImageComments", ImageComments);
            q3.bindValue(":boldreps", boldreps);
            q3.bindValue(":numfiles", numfiles);
            q3.bindValue(":importSeriesNotes", seriesNotes);
            q3.bindValue(":seriesRowID", seriesRowID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

            IL_seriescreated = 0;

            /* if the series is being updated, the QA information might be incorrect or be based on the wrong number of files, so delete the mr_qa row */
            q3.prepare("delete from mr_qa where mrseries_id = :seriesid");
            q3.bindValue(0,seriesRowID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

            msgs << n->WriteLog("Deleted from mr_qa table, now deleting from qc_results");

            /* delete the qc module rows */
            q3.prepare("select qcmoduleseries_id from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
            q3.bindValue(0,seriesRowID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

            QStringList qcidlist;
            if (q3.size() > 0) {
                while (q3.next())
                    qcidlist << q3.value("qcmoduleseries_id").toString();

                QString sqlstring = "delete from qc_results where qcmoduleseries_id in (" + qcidlist.join(",") + ")";
                q3.prepare(sqlstring);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
            }

            msgs << n->WriteLog("Deleted from qc_results table, now deleting from qc_moduleseries");

            q3.prepare("delete from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
            q3.bindValue(0,seriesRowID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        }
        else {
            /* create seriesRowID if it doesn't exist */
            msgs << n->WriteLog(QString("MR series [%1] did not exist, creating").arg(SeriesNumber));
            QString sqlstring = "insert ignore into mr_series (study_id, series_datetime, series_desc, series_protocol, series_sequencename, series_num, series_tr, series_te, series_flip, phaseencodedir, phaseencodeangle, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, series_ti, percent_sampling, percent_phasefov, acq_matrix, slicethickness, slicespacing, bandwidth, image_type, image_comments, bold_reps, numfiles, series_notes, data_type, series_status, series_createdby, series_createdate) values (:studyRowID, :SeriesDateTime, :SeriesDescription, :ProtocolName, :SequenceName, :SeriesNumber, :RepetitionTime, :EchoTime, :FlipAngle, :InPlanePhaseEncodingDirection, :PhaseEncodeAngle, :PhaseEncodingDirectionPositive, :pixelX, :pixelY, :SliceThickness, :MagneticFieldStrength, :Rows, :Columns, :zsize, :InversionTime, :PercentSampling, :PercentPhaseFieldOfView, :AcquisitionMatrix, :SliceThickness, :SpacingBetweenSlices, :PixelBandwidth, :ImageType, :ImageComments, :boldreps, :numfiles, :importSeriesNotes, 'dicom', 'complete', 'import', now())";
            QSqlQuery q3;
            q3.prepare(sqlstring);
            q3.bindValue(":studyRowID", studyRowID);
            q3.bindValue(":SeriesDateTime", SeriesDateTime);
            q3.bindValue(":SeriesDescription", SeriesDescription);
            q3.bindValue(":ProtocolName", ProtocolName);
            q3.bindValue(":SequenceName", SequenceName);
            q3.bindValue(":SeriesNumber", SeriesNumber);
            q3.bindValue(":RepetitionTime", RepetitionTime);
            q3.bindValue(":EchoTime", EchoTime);
            q3.bindValue(":FlipAngle", FlipAngle);
            q3.bindValue(":InPlanePhaseEncodingDirection", InPlanePhaseEncodingDirection);

            if (PhaseEncodeAngle == "") q3.bindValue(":PhaseEncodeAngle", QVariant(QVariant::Double)); /* for null values */
            else q3.bindValue(":PhaseEncodeAngle", PhaseEncodeAngle);

            if (PhaseEncodingDirectionPositive == "") q3.bindValue(":PhaseEncodingDirectionPositive", QVariant(QVariant::Int)); /* for null values */
            else q3.bindValue(":PhaseEncodingDirectionPositive", PhaseEncodingDirectionPositive);

            q3.bindValue(":pixelX", pixelX);
            q3.bindValue(":pixelY", pixelY);
            q3.bindValue(":SliceThickness", SliceThickness);
            q3.bindValue(":MagneticFieldStrength", MagneticFieldStrength);
            q3.bindValue(":Rows", Rows);
            q3.bindValue(":Columns", Columns);
            q3.bindValue(":zsize", zsize);
            q3.bindValue(":InversionTime", InversionTime);
            q3.bindValue(":PercentSampling", PercentSampling);
            q3.bindValue(":PercentPhaseFieldOfView", PercentPhaseFieldOfView);
            q3.bindValue(":AcquisitionMatrix", AcquisitionMatrix);
            q3.bindValue(":SliceThickness", SliceThickness);
            q3.bindValue(":SpacingBetweenSlices", SpacingBetweenSlices);
            q3.bindValue(":PixelBandwidth", PixelBandwidth);
            q3.bindValue(":ImageType", ImageType);
            q3.bindValue(":ImageComments", ImageComments);
            q3.bindValue(":boldreps", boldreps);
            q3.bindValue(":numfiles", numfiles);
            q3.bindValue(":importSeriesNotes", seriesNotes);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
            seriesRowID = q3.lastInsertId().toInt();

            IL_seriescreated = 1;
        }
    }
    else if (Modality.toUpper() == "CT") {
        dbModality = "ct";
        QSqlQuery q4;
        q4.prepare("select ctseries_id from ct_series where study_id = :studyRowID and series_num = :SeriesNumber");
        q4.bindValue(":studyRowID",studyRowID);
        q4.bindValue(":SeriesNumber",SeriesNumber);
        n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
        if (q4.size() > 0) {
            q4.first();
            seriesRowID = q4.value("ctseries_id").toInt();

            QSqlQuery q5;
            q5.prepare("update ct_series set series_datetime = :SeriesDateTime, series_desc = :SeriesDescription, series_protocol = :ProtocolName, series_spacingx = :pixelX, series_spacingy = :pixelY, series_spacingz = :SliceThickness, series_imgrows = :Rows, series_imgcols = :Columns, series_imgslices = :zsize, series_numfiles = :numfiles, series_contrastbolusagent = :ContrastBolusAgent, series_bodypartexamined = :BodyPartExamined, series_scanoptions = :ScanOptions, series_kvp = :KVP, series_datacollectiondiameter = :DataCollectionDiameter, series_contrastbolusroute = :ContrastBolusRoute, series_rotationdirection = :RotationDirection, series_exposuretime = :ExposureTime, series_xraytubecurrent = :XRayTubeCurrent, series_filtertype = :FilterType, series_generatorpower = :GeneratorPower, series_convolutionkernel = :ConvolutionKernel, series_status = 'complete' where ctseries_id = :seriesRowID");
            q5.bindValue(":SeriesDateTime", SeriesDateTime);
            q5.bindValue(":SeriesDescription", SeriesDescription);
            q5.bindValue(":ProtocolName", ProtocolName);
            q5.bindValue(":pixelX", pixelX);
            q5.bindValue(":pixelY", pixelY);
            q5.bindValue(":SliceThickness", SliceThickness);
            q5.bindValue(":Rows", Rows);
            q5.bindValue(":Columns", Columns);
            q5.bindValue(":zsize", zsize);
            q5.bindValue(":numfiles", numfiles);
            q5.bindValue(":ContrastBolusAgent", ContrastBolusAgent);
            q5.bindValue(":BodyPartExamined", BodyPartExamined);
            q5.bindValue(":ScanOptions", ScanOptions);
            q5.bindValue(":KVP", KVP);
            q5.bindValue(":DataCollectionDiameter", DataCollectionDiameter);
            q5.bindValue(":ContrastBolusRoute", ContrastBolusRoute);
            q5.bindValue(":RotationDirection", RotationDirection);
            q5.bindValue(":ExposureTime", ExposureTime);
            q5.bindValue(":XRayTubeCurrent", XRayTubeCurrent);
            q5.bindValue(":FilterType", FilterType);
            q5.bindValue(":GeneratorPower", GeneratorPower);
            q5.bindValue(":ConvolutionKernel", ConvolutionKernel);
            q5.bindValue(":seriesRowID", seriesRowID);
            n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);
            msgs << n->WriteLog(QString("This CT series [%1] exists, updating").arg(SeriesNumber));
            IL_seriescreated = 0;
        }
        else {
            /* create seriesRowID if it doesn't exist */
            QSqlQuery q5;
            q5.prepare("insert into ct_series ( study_id, series_datetime, series_desc, series_protocol, series_num, series_contrastbolusagent, series_bodypartexamined, series_scanoptions, series_kvp, series_datacollectiondiameter, series_contrastbolusroute, series_rotationdirection, series_exposuretime, series_xraytubecurrent, series_filtertype,series_generatorpower, series_convolutionkernel, series_spacingx, series_spacingy, series_spacingz, series_imgrows, series_imgcols, series_imgslices, numfiles, series_datatype, series_status, series_createdby ) values ( :studyRowID, :SeriesDateTime, :SeriesDescription, :ProtocolName, :SeriesNumber, :ContrastBolusAgent, :BodyPartExamined, :ScanOptions, :KVP, :DataCollectionDiameter, :ContrastBolusRoute, :RotationDirection, :ExposureTime, :XRayTubeCurrent, :FilterType, :GeneratorPower, :ConvolutionKernel, :pixelX, :pixelY, :SliceThickness, :Rows, :Columns, :zsize, :numfiles, 'dicom', 'complete', 'import')");
            q5.bindValue(":studyRowID", studyRowID);
            q5.bindValue(":SeriesDateTime", SeriesDateTime);
            q5.bindValue(":SeriesDescription", SeriesDescription);
            q5.bindValue(":ProtocolName", ProtocolName);
            q5.bindValue(":pixelX", pixelX);
            q5.bindValue(":pixelY", pixelY);
            q5.bindValue(":SliceThickness", SliceThickness);
            q5.bindValue(":Rows", Rows);
            q5.bindValue(":Columns", Columns);
            q5.bindValue(":zsize", zsize);
            q5.bindValue(":numfiles", numfiles);
            q5.bindValue(":ContrastBolusAgent", ContrastBolusAgent);
            q5.bindValue(":BodyPartExamined", BodyPartExamined);
            q5.bindValue(":ScanOptions", ScanOptions);
            q5.bindValue(":KVP", KVP);
            q5.bindValue(":DataCollectionDiameter", DataCollectionDiameter);
            q5.bindValue(":ContrastBolusRoute", ContrastBolusRoute);
            q5.bindValue(":RotationDirection", RotationDirection);
            q5.bindValue(":ExposureTime", ExposureTime);
            q5.bindValue(":XRayTubeCurrent", XRayTubeCurrent);
            q5.bindValue(":FilterType", FilterType);
            q5.bindValue(":GeneratorPower", GeneratorPower);
            q5.bindValue(":ConvolutionKernel", ConvolutionKernel);
            n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);
            seriesRowID = q5.lastInsertId().toInt();
            msgs << n->WriteLog(QString("CT series [%1] did not exist, creating").arg(SeriesNumber));
            IL_seriescreated = 1;
        }
    }
    else {
        /* this is the catch all for modalities which don't have a table in the database */

        n->WriteLog(QString("Modality [%1] numfiles [%2] zsize [%3]").arg(Modality).arg(numfiles).arg(zsize));

        if (n->ValidNiDBModality(Modality))
            dbModality = Modality.toLower();
        else
            dbModality = "ot";

        QSqlQuery q3;
        q3.prepare(QString("select %1series_id from %1_series where study_id = :studyid and series_num = :seriesnum").arg(dbModality));
        q3.bindValue(":studyid", studyRowID);
        q3.bindValue(":seriesnum", SeriesNumber);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        if (q3.size() > 0) {
            q3.first();
            msgs << n->WriteLog(QString("This %1 series [%2] exists, updating").arg(Modality).arg(SeriesNumber));
            seriesRowID = q3.value(dbModality + "series_id").toInt();

            QSqlQuery q4;
            if (dbModality == "ot")
                q4.prepare(QString("update %1_series set series_datetime = '" + SeriesDateTime + "', series_desc = :ProtocolName, series_spacingx = :pixelX, series_spacingy = :pixelY, series_spacingz = :SliceThickness, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, numfiles = :numfiles, series_status = 'complete' where %1series_id = :seriesRowID").arg(dbModality));
            else
                q4.prepare(QString("update %1_series set series_datetime = '" + SeriesDateTime + "', series_desc = :ProtocolName where %1series_id = :seriesRowID").arg(dbModality));
            q4.bindValue(":ProtocolName", ProtocolName);
            q4.bindValue(":pixelX", pixelX);
            q4.bindValue(":pixelY", pixelY);
            q4.bindValue(":SliceThickness", SliceThickness);
            q4.bindValue(":Rows", Rows);
            q4.bindValue(":Columns", Columns);
            q4.bindValue(":zsize", zsize);
            q4.bindValue(":numfiles", numfiles);
            q4.bindValue(":seriesRowID", seriesRowID);
            n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

            IL_seriescreated = 0;
        }
        else {
            /* create seriesRowID if it doesn't exist */
            msgs << n->WriteLog(QString(Modality + " series [%1] did not exist, creating").arg(SeriesNumber));
            QSqlQuery q4;
            if (dbModality == "ot") {
                q4.prepare(QString("insert into %1_series (study_id, series_datetime, series_desc, series_num, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, img_slices, numfiles, modality, data_type, series_status, series_createdby) values (:studyid, :SeriesDateTime, :ProtocolName, :SeriesNumber, :pixelX, :pixelY, :SliceThickness, :Rows, :Columns, :zsize, :numfiles, :Modality, 'dicom', 'complete', 'import')").arg(dbModality));
            }
            else {
                q4.prepare(QString("insert into %1_series (study_id, series_datetime, series_desc, series_num, series_createdby) values (:studyid, :SeriesDateTime, :ProtocolName, :SeriesNumber, 'import')").arg(dbModality));
            }
            q4.bindValue(":studyid", studyRowID);
            q4.bindValue(":SeriesDateTime", SeriesDateTime);
            q4.bindValue(":ProtocolName", ProtocolName);
            q4.bindValue(":SeriesNumber", SeriesNumber);
            q4.bindValue(":pixelX", pixelX);
            q4.bindValue(":pixelY", pixelY);
            q4.bindValue(":SliceThickness", SliceThickness);
            q4.bindValue(":Rows", Rows);
            q4.bindValue(":Columns", Columns);
            q4.bindValue(":zsize", zsize);
            q4.bindValue(":numfiles", numfiles);
            q4.bindValue(":Modality", Modality);
            n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
            seriesRowID = q4.lastInsertId().toInt();

            IL_seriescreated = 1;
        }
    }

    /* copy the file to the archive, update db info */
    msgs << n->WriteLog(QString("SeriesRowID: [%1]").arg(seriesRowID));

    /* create data directory if it doesn't already exist */
    QString outdir = QString("%1/%2/%3/%4/dicom").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    QString thumbdir = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    //msgs << n->WriteLog("outdir [" + outdir + "]");
    m = "";
    if (!n->MakePath(outdir, m))
        msgs << n->WriteLog("Unable to create output direcrory [" + outdir + "] because of error [" + m + "]");
    else
        msgs << n->WriteLog("Created outdir ["+outdir+"]");

    /* check if there are .dcm files already in the archive (outdir) */
    msgs << n->WriteLog("Checking for existing files in outdir [" + outdir + "]");
    QStringList existingdcms = n->FindAllFiles(outdir, "*.dcm");
    int numexistingdcms = existingdcms.size();

    /* rename **** EXISTING **** files in the output directory */
    if (numexistingdcms > 0) {
        //n->SortQStringListNaturally(existingdcms);

        /* check all files to see if its the same study datetime, patient name, dob, gender, series #
         * if anything is different, move the file to a UID/Study/Series/dicom/existing directory
         *
         * if they're all the same, consolidate the files into one list of new and old, remove duplicates
         */

        msgs << n->WriteLog(QString("There are [%1] existing files in [%2]. Beginning renaming...").arg(numexistingdcms).arg(outdir));

        int filecnt = 0;
        /* rename the existing files to make them unique */
        foreach (QString file, existingdcms) {
            QFileInfo f(file);

            /* check if its already in the intended filename format */
            QString fname = f.fileName();
            QStringList parts = fname.split("_");
            if (parts.size() == 8) {
                if ((subjectRealUID == parts[0]) && (studynum == parts[1]) && (SeriesNumber == parts[2]))
                    continue;
            }

            /* need to rename it, get the DICOM tags */
            QHash<QString, QString> tags;
            if (!n->GetImageFileTags(file, tags))
                continue;

            int SliceNumber = tags["AcquisitionNumber"].toInt();
            int InstanceNumber = tags["InstanceNumber"].toInt();
            QString AcquisitionTime = tags["AcquisitionTime"];
            QString ContentTime = tags["ContentTime"];
            QString SOPInstance = tags["SOPInstanceUID"];
            AcquisitionTime.remove(":").remove(".");
            ContentTime.remove(":").remove(".");
            SOPInstance = QString(QCryptographicHash::hash(SOPInstance.toUtf8(),QCryptographicHash::Md5).toHex());

            QString newfname = QString("%1_%2_%3_%4_%5_%6_%7_%8.dcm").arg(subjectRealUID).arg(studynum).arg(SeriesNumber).arg(SliceNumber, 5, 10, QChar('0')).arg(InstanceNumber, 5, 10, QChar('0')).arg(AcquisitionTime).arg(ContentTime).arg(SOPInstance);
            QString newfile = outdir + "/" + newfname;

            n->RenameFile(file, newfile); /* don't care about return value here, because the old filename may have been the same as the new one */

            filecnt++;
        }
        msgs << n->WriteLog(QString("Done renaming [%1] files").arg(filecnt));
    }

    /* create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size) */
    CreateThumbnail(files[files.size()/2], thumbdir);

    /* renumber the **** NEWLY **** added files to make them unique */
    msgs << n->WriteLog("Renaming new files");
    foreach (QString file, files) {
        /* need to rename it, get the DICOM tags */
        QHash<QString, QString> tags;
        if (!n->GetImageFileTags(file, tags))
            continue;

        int SliceNumber = tags["AcquisitionNumber"].toInt();
        int InstanceNumber = tags["InstanceNumber"].toInt();
        QString AcquisitionTime = tags["AcquisitionTime"];
        QString ContentTime = tags["ContentTime"];
        QString SliceLocation = tags["SliceLocation"];
        QString SOPInstance = tags["SOPInstanceUID"];
        AcquisitionTime.remove(":").remove(".");
        ContentTime.remove(":").remove(".");
        SOPInstance = QString(QCryptographicHash::hash(SOPInstance.toUtf8(),QCryptographicHash::Md5).toHex());

        QString newfname = QString("%1_%2_%3_%4_%5_%6_%7_%8.dcm").arg(subjectRealUID).arg(studynum).arg(SeriesNumber).arg(SliceNumber, 5, 10, QChar('0')).arg(InstanceNumber, 5, 10, QChar('0')).arg(AcquisitionTime).arg(ContentTime).arg(SOPInstance);
        QString newfile = outdir + "/" + newfname;

        /* check if a file with the same name already exists */
        if (QFile::exists(newfile)) {
            /* remove the existing file */
            QFile::remove(newfile);
            IL_overwrote_existing = true;
        }
        else {
            IL_overwrote_existing = false;
        }

        /* move & rename the file */
        QFile fr(file);
        if (!n->RenameFile(file, newfile))
            msgs << n->WriteLog("Unable to rename newly added file [" + file + "] to [" + newfile + "]");

        /* insert an import log record */
        QSqlQuery q5;
        q5.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, modality_orig, patientid_orig, patientname_orig, stationname_orig, institution_orig, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values (:file, :newfile, 'DICOM', now(), 'successful', :importid, :importid, :importSiteID, :importProjectID, :IL_modality_orig, :PatientID, :IL_patientname_orig, :IL_stationname_orig, :IL_institution_orig, :subjectRealUID, :studynum, :subjectRowID, :studyRowID, :seriesRowID, :enrollmentRowID, :IL_seriescreated, :IL_studycreated, :IL_subjectcreated, :IL_familycreated, :IL_enrollmentcreated, :IL_overwrote_existing)");
        q5.bindValue(":file", file);
        q5.bindValue(":newfile", outdir+"/"+newfname);
        q5.bindValue(":importid", importid);
        q5.bindValue(":importSiteID", destSiteID);
        q5.bindValue(":importProjectID", destProjectID);
        q5.bindValue(":IL_modality_orig", IL_modality_orig);
        q5.bindValue(":PatientID", PatientID);
        q5.bindValue(":IL_patientname_orig", IL_patientname_orig);
        q5.bindValue(":IL_stationname_orig", IL_stationname_orig);
        q5.bindValue(":IL_institution_orig", IL_institution_orig);
        q5.bindValue(":IL_seriesnumber_orig", IL_seriesnumber_orig);
        q5.bindValue(":subjectRealUID", subjectRealUID);
        q5.bindValue(":studynum", studynum);
        q5.bindValue(":subjectRowID", subjectRowID);
        q5.bindValue(":studyRowID", studyRowID);
        q5.bindValue(":seriesRowID", seriesRowID);
        q5.bindValue(":enrollmentRowID", enrollmentRowID);
        q5.bindValue(":IL_seriescreated", IL_seriescreated);
        q5.bindValue(":IL_studycreated", IL_studycreated);
        q5.bindValue(":IL_subjectcreated", IL_subjectcreated);
        q5.bindValue(":IL_familycreated", IL_familycreated);
        q5.bindValue(":IL_enrollmentcreated", IL_enrollmentcreated);
        q5.bindValue(":IL_overwrote_existing", IL_overwrote_existing);
        n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);
    }

    /* get the size of the dicom files and update the DB */
    qint64 dirsize = 0;
    int nfiles;
    n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);
    msgs << n->WriteLog(QString("output directory [%1] is size [%2] and contains nfiles [%3]").arg(outdir).arg(dirsize).arg(nfiles));

    /* check if its an EPI sequence, but not a perfusion sequence */
    if (SequenceName.contains("epfid2d1_")) {
        if (ProtocolName.contains("perfusion", Qt::CaseInsensitive) || SequenceName.contains("ep2d_perf_tra")) { }
        else {
            mrtype = "epi";
            /* get the bold reps and attempt to get the z size */
            boldreps = nfiles;

            /* this method works ... sometimes */
            if ((mat1 > 0) && (mat4 > 0))
                zsize = (Rows/mat1)*(Columns/mat4); /* example (384/64)*(384/64) = 6*6 = 36 possible slices in a mosaic */
            else
                zsize = nfiles;
        }
    }
    else {
        zsize = nfiles;
    }

    /* update the database with the correct number of files/BOLD reps */
    if (dbModality == "mr") {
        QString sqlstring = QString("update %1_series set series_size = :dirsize, numfiles = :numfiles, bold_reps = :boldreps where %1series_id = :seriesid").arg(dbModality.toLower());
        QSqlQuery q2;
        q2.prepare(sqlstring);
        q2.bindValue(":dirsize", dirsize);
        q2.bindValue(":numfiles", nfiles);
        q2.bindValue(":boldreps", boldreps);
        q2.bindValue(":seriesid", seriesRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    }
    else {
        QString sqlstring = QString("update %1_series set series_size = :dirsize, series_numfiles = :numfiles where %1series_id = :seriesid").arg(dbModality.toLower());
        QSqlQuery q2;
        q2.prepare(sqlstring);
        q2.bindValue(":dirsize", dirsize);
        q2.bindValue(":numfiles", nfiles);
        q2.bindValue(":seriesid", seriesRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    }

    /* if a beh directory exists for this series from an import, move it to the final series directory */
    QString inbehdir = QString("%1/%2/beh").arg(n->cfg["incomingdir"]).arg(importid);
    QString outbehdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);

    if (importid > 0) {
        msgs << n->WriteLog("Checking for behavioral data in [" + inbehdir + "]");
        QDir bd(inbehdir);
        if (bd.exists()) {
            QString m;
            if (n->MakePath(outbehdir, m)) {
                QString systemstring = "mv -v " + inbehdir + "/* " + outbehdir + "/";
                msgs << n->WriteLog(n->SystemCommand(systemstring));

                qint64 behdirsize(0);
                int behnumfiles(0);
                n->GetDirSizeAndFileCount(outdir, behnumfiles, behdirsize);
                QString sqlstring = QString("update %1_series set beh_size = :behdirsize, numfiles_beh = :behnumfiles where %1series_id = :seriesRowID").arg(dbModality.toLower());
                QSqlQuery q3;
                q3.prepare(sqlstring);
                q3.bindValue(":behdirsize", behdirsize);
                q3.bindValue(":behnumfiles", behnumfiles);
                q3.bindValue(":seriesRowID", seriesRowID);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
            }
            else
                msgs << n->WriteLog("Unable to create outbehdir ["+outbehdir+"] because of error ["+m+"]");
        }
    }

    /* change the permissions on the outdir to 777 so the webpage can read/write the directories */
    systemstring = "chmod -Rf 777 " + outdir;
    msgs << n->WriteLog(n->SystemCommand(systemstring));

    /* copy everything to the backup directory */
    QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    QDir bda(backdir);
    if (!bda.exists()) {
        msgs << n->WriteLog("Backup directory [" + backdir + "] does not exist. About to create it...");
        QString m;
        if (!n->MakePath(backdir, m))
            msgs << n->WriteLog("Unable to create backdir [" + backdir + "] because of error [" + m + "]");
    }
    msgs << n->WriteLog("Starting copy to the backup directory");
    systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
    msgs << n->WriteLog(n->SystemCommand(systemstring));
    msgs << n->WriteLog("Finished copying to the backup directory");

    msg += msgs.join("\n");
    return 1;
}


/* ---------------------------------------------------------- */
/* --------- InsertParRec ----------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::InsertParRec(int importid, QString file, QString &msg) {

    QStringList msgs;

    msgs << n->WriteLog(QString("----- In InsertParRec(%1,%2) -----").arg(importid).arg(file));

    /* import log variables */
    QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
    //double IL_patientage_orig;
    int IL_seriesnumber_orig;
    QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
    QString IL_subject_uid;
    QString IL_project_number;
    int IL_seriescreated(0), IL_studycreated(0), IL_subjectcreated(0), IL_familycreated(0), IL_enrollmentcreated(0), IL_overwrote_existing(0);

    QString familyRealUID;
    int familyRowID(0);

    QString parfile = file;
    QString recfile = file;
    recfile.replace(".par", ".rec");

    QString PatientName;
    QString PatientBirthDate = "0001-01-01";
    QString PatientID = "NotSpecified";
    QString PatientSex = "U";
    double PatientWeight(0.0);
    double PatientSize(0.0);
    double PatientAge(0.0);
    QString StudyDescription;
    QString SeriesDescription;
    QString StationName = "PAR/REC";
    QString OperatorsName = "NotSpecified";
    QString PerformingPhysiciansName = "NotSpecified";
    QString InstitutionName = "NotSpecified";
    QString InstitutionAddress = "NotSpecified";
    //int AccessionNumber(0);
    QString SequenceName;
    double MagneticFieldStrength(0.0);
    QString ProtocolName;
    QString StudyDateTime;
    QString SeriesDateTime;
    QString Modality;
    int SeriesNumber(0);
    int zsize(0);
    int boldreps(0);
    int numfiles(2); /* should always be 2 for .par/.rec */
    QString seriessequencename;
    int RepetitionTime(0);
    int Columns(0), Rows(0);
    int pixelX(0), pixelY(0);
    double SliceThickness(0.0);
    //double xspacing(0.0);
    //double yspacing(0.0);
    double EchoTime(0.0);
    double FlipAngle(0.0);

    //int importInstanceID(0);
    int importSiteID(0);
    int importProjectID(0);
    //int importPermanent(0);
    //int importAnonymize(0);
    int importMatchIDOnly(0);
    //QString importUUID;
    QString importSeriesNotes;
    QString importAltUIDs;

    /* if there is an importid, check to see how that thing is doing */
    if (importid > 0) {
        QSqlQuery q;
        q.prepare("select * from import_requests where importrequest_id = :importid");
        q.bindValue(":importid", importid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            QString status = q.value("import_status").toString();
            //importInstanceID = q.value("import_instanceid").toInt();
            importSiteID = q.value("import_siteid").toInt();
            importProjectID = q.value("import_projectid").toInt();
            //importPermanent = q.value("import_permanent").toInt();
            //importAnonymize = q.value("import_anonymize").toInt();
            importMatchIDOnly = q.value("import_matchidonly").toInt();
            //importUUID = q.value("import_uuid").toString();
            importSeriesNotes = q.value("import_seriesnotes").toString();
            importAltUIDs = q.value("import_altuids").toString();
        }
    }

    QFile prf(file);

    /* read the .par file into an array, get all the useful info out of it */
    if (prf.open(QIODevice::ReadOnly | QIODevice::Text)) {

        QTextStream in(&prf);
        while (!in.atEnd()) {
            QString line = in.readLine().trimmed();

            if (line.contains("Patient name")) {
                QStringList p = line.split(":");
                if (p.size() > 1) {
                    PatientName = p[1].trimmed();
                    PatientID = PatientName;
                }
            }
            if (line.contains("Examination name")) {
                QStringList p = line.split(":");
                if (p.size() > 1)
                    StudyDescription = p[1].trimmed();
            }
            if (line.contains("Protocol name")) {
                QStringList p = line.split(":");
                if (p.size() > 1) {
                    ProtocolName = p[1].trimmed();
                    SeriesDescription = ProtocolName;
                }
            }
            if (line.contains("Examination date/time")) {
                QString datetime = line;
                datetime.replace(QRegExp("\\.\\s+Examination date/time\\s+:"),"");
                QStringList p = datetime.split("/");
                if (p.size() > 1) {
                    QString date = p[0].trimmed();
                    QString time = p[1].trimmed();
                    date = date.replace(".","-");
                    StudyDateTime = date + " " + time;
                    SeriesDateTime = date + " " + time;
                }
            }
            if (line.contains("Series Type")) {
                QStringList p = line.split(":");
                if (p.size() > 1) {
                    Modality = p[1].trimmed();
                    Modality = Modality.replace("Image", "");
                    Modality = Modality.replace("series", "", Qt::CaseInsensitive);
                    Modality = Modality.trimmed().toUpper();
                }
            }
            if (line.contains("Acquisition nr")) {
                QStringList p = line.split(":");
                if (p.size() > 1) {
                    p[1] = p[1].trimmed();
                    SeriesNumber = p[1].toInt();
                }
            }
            if (line.contains("Max. number of slices/locations")) {
                QStringList p = line.split(":");
                if (p.size() > 1)
                    zsize = p[1].trimmed().toInt();
            }
            if (line.contains("Max. number of dynamics")) {
                QStringList p = line.split(":");
                if (p.size() > 1)
                    boldreps = p[1].trimmed().toInt();
            }
            if (line.contains("Technique")) {
                QStringList p = line.split(":");
                if (p.size() > 1)
                    SequenceName = p[1].trimmed();
            }
            if (line.contains("Scan resolution")) {
                QStringList p = line.split(":");
                if (p.size() > 1) {
                    QString resolution = p[1].trimmed();
                    QStringList p2 = resolution.split(QRegExp("\\s+"));
                    if (p.size() > 1) {
                        Columns = p2[0].trimmed().toInt();
                        Rows = p2[1].trimmed().toInt();
                    }
                }
            }
            if (line.contains("Repetition time")) {
                QStringList p = line.split(":");
                if (p.size() > 1)
                    RepetitionTime = p[1].trimmed().toInt();
            }
            /* get the first line of the image list... it should contain the flip angle */
            if (!line.startsWith(".") && !line.startsWith("#") && (line != "")) {
                QStringList p = line.split(QRegExp("\\s+"));

                if (p.size() > 9) pixelX = p[9].trimmed().toInt(); /* 10 - xsize */
                if (p.size() > 10) pixelY = p[10].trimmed().toInt(); /* 11 - ysize */
                if (p.size() > 22) SliceThickness = p[22].trimmed().toDouble(); /* 23 - slice thickness */
                //if (p.size() > 28) xspacing = p[28].trimmed().toDouble(); /* 29 - xspacing */
                //if (p.size() > 29) yspacing = p[29].trimmed().toDouble(); /* 30 - yspacing */
                if (p.size() > 30) EchoTime = p[30].trimmed().toDouble(); /* 31 - TE */
                if (p.size() > 35) FlipAngle = p[35].trimmed().toInt(); /* 36 - flip */

                break;
            }
        }
    }
    else {
        msgs << "Unable to read file [" + file + "]";
        msg += msgs.join("\n");
        return 0;
    }

    /* check if anything is funny, and not compatible with archiving this data */
    if (SeriesNumber == 0) {
        msgs << "Series number is 0";
        msg += msgs.join("\n");
        return 0;
    }
    if (PatientName == "") {
        msgs << "PatientName (ID) is blank";
        msg += msgs.join("\n");
        return 0;
    }

    /* set the import log variables */
    IL_modality_orig = Modality;
    IL_patientname_orig = PatientName;
    IL_patientdob_orig = PatientBirthDate;
    IL_patientsex_orig = PatientSex;
    IL_stationname_orig = StationName;
    IL_institution_orig = InstitutionName + "-" + InstitutionAddress;
    IL_studydatetime_orig = StudyDateTime;
    IL_seriesdatetime_orig = SeriesDateTime;
    IL_seriesnumber_orig = SeriesNumber;
    IL_studydesc_orig = StudyDescription;
    IL_seriesdesc_orig = SeriesDescription;
    IL_protocolname_orig = ProtocolName;
    //IL_patientage_orig = PatientAge;

    /* ----- check if this subject/study/series/etc exists ----- */
    int projectRowID(0);
    QString subjectRealUID = PatientName;
    int subjectRowID(0);
    int enrollmentRowID;
    int studyRowID(0);
    int seriesRowID;
    QString costcenter;
    int studynum(0);

    /* get the costcenter */
    costcenter = GetCostCenter(StudyDescription);

    msgs << PatientID + " - " + StudyDescription;

    /* get the ID search string */
    QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
    QStringList altuidlist;
    if (importAltUIDs != "")
        altuidlist = importAltUIDs.split(",");

    /* check the alternate UIDs */
    foreach (QString altuid, altuidlist) {
        if (altuid.trimmed().size() > 254)
            msgs << "Alternate UID [" + altuid.left(255) + "...] is longer than 255 characters and will be truncated";
    }

    /* check if the project and subject exist */
    msgs << "Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]";
    int projectcount(0);
    int subjectcount(0);
    QString sqlstring = QString("select (SELECT count(*) FROM `projects` WHERE project_costcenter = :costcenter) 'projectcount', (SELECT count(*) FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (%1) or a.uid = SHA1(:patientid) or b.altuid in (%1) or b.altuid = SHA1(:patientid)) 'subjectcount'").arg(SQLIDs);
    QSqlQuery q;
    q.prepare(sqlstring);
    q.bindValue(":patientid", PatientID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
    if (q.size() > 0) {
        q.first();
        projectcount = q.value("projectcount").toInt();
        subjectcount = q.value("subjectcount").toInt();
    }

    /* if subject can't be found by UID, check by name/dob/sex (except if importMatchIDOnly is set), or create the subject */
    if (subjectcount < 1) {

        bool subjectFoundByName = false;
        /* search for an existing subject by name, dob, gender */
        if (!importMatchIDOnly) {
            QString sqlstring2 = "select subject_id, uid from subjects where name like '%" + PatientName + "%' and gender = left('" + PatientSex + "',1) and birthdate = :dob and isactive = 1";
            msgs << "Subject not found by UID. Checking if the subject exists using PatientName [" + PatientName + "] PatientSex [" + PatientSex + "] PatientBirthDate [" + PatientBirthDate + "]";
            QSqlQuery q2;
            q2.prepare(sqlstring2);
            q2.bindValue(":dob", PatientBirthDate);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            if (q2.size() > 0) {
                q2.first();
                subjectRealUID = q2.value("uid").toString();
                subjectRowID = q2.value("subject_id").toInt();
                msgs << "This subject exists. UID [" + subjectRealUID + "]";
                IL_subjectcreated = 0;
                subjectFoundByName = 1;
            }
        }
        /* if it couldn't be found, create a new subject */
        if (!subjectFoundByName) {
            CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, msgs, subjectRowID, subjectRealUID);
            IL_subjectcreated = 1;
        }
    }
    else {
        /* get the existing subject ID, and UID! (the PatientID may be an alternate UID) */
        QString sqlstring = "SELECT a.subject_id, a.uid FROM subjects a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (" + SQLIDs + ") or a.uid = SHA1(:patientid) or b.altuid in (" + SQLIDs + ") or b.altuid = SHA1(:patientid)";
        QSqlQuery q2;
        q2.prepare(sqlstring);
        q2.bindValue(":patientid", PatientID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__,true);
        if (q2.size() > 0) {
            q2.first();
            subjectRowID = q2.value("subject_id").toInt();
            subjectRealUID = q2.value("uid").toString().toUpper().trimmed();

            msgs << QString("Found subject [subjectid %1, UID " + subjectRealUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID);
        }
        else {
            msgs << "Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]";
            msg += msgs.join("\n");
            return 0;
        }
        /* insert the PatientID as an alternate UID */
        if (PatientID != "") {
            QSqlQuery q2;
            q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
            q2.bindValue(":subjectid", subjectRowID);
            q2.bindValue(":patientid", PatientID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        }
        IL_subjectcreated = 0;
    }

    n->WriteLog("subjectRealUID [" + subjectRealUID + "]");
    if (subjectRealUID == "") {
        msgs << "Error finding/creating subject. UID is blank";
        msg += msgs.join("\n");
        return 0;
    }

    /* check if the subject is part of a family, if not create a family for it */
    QSqlQuery q2;
    q2.prepare("select family_id from family_members where subject_id = :subjectid");
    q2.bindValue(":subjectid", subjectRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        familyRowID = q2.value("family_id").toInt();
        msgs << QString("This subject is part of a family [%1]").arg(familyRowID);
        IL_familycreated = 0;
    }
    else {
        int count = 0;

        /* create family UID */
        msgs << "Subject is not part of family, creating a unique family UID";
        do {
            familyRealUID = n->CreateUID("F");
            QSqlQuery q2;
            q2.prepare("SELECT * FROM `families` WHERE family_uid = :familyuid");
            q2.bindValue(":familyuid", familyRealUID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            count = q2.size();
        } while (count > 0);

        /* create familyRowID if it doesn't exist */
        QSqlQuery q2;
        q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyRealUID, now(), :familyname)");
        q2.bindValue(":familyuid", familyRealUID);
        q2.bindValue(":familyname", "Proband-" + subjectRealUID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        familyRowID = q2.lastInsertId().toInt();

        q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
        q2.bindValue(":familyid", familyRowID);
        q2.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

        IL_familycreated = 1;
    }

    /* if project doesn't exist, use the generic project */
    if (projectcount < 1) {
        costcenter = "999999";
    }

    /* get the projectRowID */
    if (importProjectID == 0) {
        q2.prepare("select project_id from projects where project_costcenter = :costcenter");
        q2.bindValue(":costcenter", costcenter);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            projectRowID = q2.value("project_id").toInt();
        }
    }
    else {
        /* need to create the project if it doesn't exist */
        msgs << QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID);
        projectRowID = importProjectID;
    }

    /* check if the subject is enrolled in the project */
    q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
    q2.bindValue(":subjectid", subjectRowID);
    q2.bindValue(":projectid", projectRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        enrollmentRowID = q2.value("enrollment_id").toInt();
        msgs << QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID);
        IL_enrollmentcreated = 0;
    }
    else {
        /* create enrollmentRowID if it doesn't exist */
        q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
        q2.bindValue(":subjectid", subjectRowID);
        q2.bindValue(":projectid", projectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        enrollmentRowID = q2.lastInsertId().toInt();

        msgs << QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID);
        IL_enrollmentcreated = 1;
    }

    /* update alternate IDs, if there are any */
    if (altuidlist.size() > 0) {
        n->WriteLog(QString("altuidlist is of size [%1]").arg(altuidlist.size()));
        foreach (QString altuid, altuidlist) {
            if (altuid.trimmed() != "") {
                n->WriteLog("Updating/inserting an altuid ["+altuid+"]");
                q2.prepare("replace into subject_altuid (subject_id, altuid, enrollment_id) values (:subjectid, :altuid, :enrollmentid)");
                q2.bindValue(":subjectid", subjectRowID);
                q2.bindValue(":altuid", altuid);
                q2.bindValue(":enrollmentid", enrollmentRowID);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            }
        }
    }

    /* now determine if this study exists or not...
     * basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
     * also checks the accession number against the study_num to see if this study was pre-registered
     * HOWEVER, if there is an instanceID specified, we should only match a study that's part of an enrollment in the same instance */
    bool studyFound = false;
    msgs << QString("Checking if this study exists: enrollmentID [%1] StudyDateTime [%2] Modality [%3] StationName [%4]").arg(enrollmentRowID).arg(StudyDateTime).arg(Modality).arg(StationName);

    q2.prepare("select study_id, study_num from studies where enrollment_id = :enrollmentid and (((study_datetime between date_sub('" + StudyDateTime + "', interval 30 second) and date_add('" + StudyDateTime + "', interval 30 second)) and study_modality = :modality and study_site = :stationname))");
    q2.bindValue(":enrollmentid", enrollmentRowID);
    //q2.bindValue(":accessionnum", AccessionNumber);
    q2.bindValue(":modality", Modality);
    q2.bindValue(":stationname", StationName);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        while (q2.next()) {
            int study_id = q2.value("study_id").toInt();
            studynum = q2.value("study_num").toInt();
            studyFound = true;
            studyRowID = study_id;

            QSqlQuery q4;
            msgs << QString("StudyID [%1] exists, updating").arg(study_id);
            q4.prepare("update studies set study_modality = :modality, study_datetime = '" + StudyDateTime + "', study_ageatscan = :patientage, study_height = :height, study_weight = :weight, study_desc = :studydesc, study_operator = :operator, study_performingphysician = :physician, study_site = :stationname, study_nidbsite = :importsiteid, study_institution = :institution, study_status = 'complete' where study_id = :studyid");
            q4.bindValue(":modality", Modality);
            q4.bindValue(":patientage", PatientAge);
            q4.bindValue(":height", PatientSize);
            q4.bindValue(":weight", PatientWeight);
            q4.bindValue(":studydesc", StudyDescription);
            q4.bindValue(":operator", OperatorsName);
            q4.bindValue(":physician", PerformingPhysiciansName);
            q4.bindValue(":stationname", StationName);
            q4.bindValue(":importsiteid", importSiteID);
            q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
            q4.bindValue(":studyid", studyRowID);
            n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

            IL_studycreated = 0;
            break;
        }
    }
    if (!studyFound) {
        msgs << "Study did not exist, creating new study";

        /* create studyRowID if it doesn't exist */
        q2.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
        q2.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            studynum = q2.value("study_num").toInt() + 1;
        }
        else
            studynum = 1;

        QSqlQuery q4;
        q4.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_desc, study_operator, study_performingphysician, study_site, study_nidbsite, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentid, :studynum, :patientid, :modality, '"+StudyDateTime+"', :patientage, :height, :weight, :studydesc, :operator, :physician, :stationname, :importsiteid, :institution, 'complete', 'import', now())");
        q4.bindValue(":enrollmentid", enrollmentRowID);
        q4.bindValue(":studynum", studynum);
        q4.bindValue(":patientid", PatientID);
        q4.bindValue(":modality", Modality);
        //q4.bindValue(":studydatetime", StudyDateTime);
        q4.bindValue(":patientage", PatientAge);
        q4.bindValue(":height", PatientSize);
        q4.bindValue(":weight", PatientWeight);
        q4.bindValue(":studydesc", StudyDescription);
        q4.bindValue(":operator", OperatorsName);
        q4.bindValue(":physician", PerformingPhysiciansName);
        q4.bindValue(":stationname", StationName);
        q4.bindValue(":importsiteid", importSiteID);
        q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
        n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
        studyRowID = q4.lastInsertId().toInt();

        IL_studycreated = 1;
    }

    n->WriteLog(QString("Going forward using the following: SubjectRowID [%1] ProjectRowID [%2] EnrollmentRowID [%3] StudyRowID [%4]").arg(subjectRowID).arg(projectRowID).arg(enrollmentRowID).arg(studyRowID));

    // ----- insert or update the series -----
    q2.prepare("select mrseries_id from mr_series where study_id = :studyid and series_num = :SeriesNumber");
    q2.bindValue(":studyid",studyRowID);
    q2.bindValue(":SeriesNumber",SeriesNumber);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        seriesRowID = q2.value("mrseries_id").toInt();

        QSqlQuery q3;
        q3.prepare("update mr_series set series_datetime = :SeriesDateTime,series_desc = :ProtocolName, series_sequencename = :SequenceName,series_tr = :RepetitionTime, series_te = :EchoTime,series_flip = :FlipAngle, series_spacingx = :pixelX,series_spacingy = :pixelY, series_spacingz = :SliceThickness, series_fieldstrength = :MagneticFieldStrength, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, bold_reps = :boldreps, numfiles = :numfiles, series_status = 'complete' where mrseries_id = :seriesRowID");

        q3.bindValue(":SeriesDateTime",SeriesDateTime);
        q3.bindValue(":ProtocolName",ProtocolName);
        q3.bindValue(":SequenceName",SequenceName);
        q3.bindValue(":RepetitionTime",RepetitionTime);
        q3.bindValue(":EchoTime",EchoTime);
        q3.bindValue(":FlipAngle",FlipAngle);
        q3.bindValue(":pixelX",pixelX);
        q3.bindValue(":pixelY",pixelY);
        q3.bindValue(":SliceThickness",SliceThickness);
        q3.bindValue(":MagneticFieldStrength",MagneticFieldStrength);
        q3.bindValue(":Rows",Rows);
        q3.bindValue(":Columns",Columns);
        q3.bindValue(":zsize",zsize);
        q3.bindValue(":boldreps",boldreps);
        q3.bindValue(":numfiles",numfiles);
        q3.bindValue(":seriesRowID",seriesRowID);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

        IL_seriescreated = 0;
    }
    else {
        // create seriesRowID if it doesn't exist
        QSqlQuery q3;
        q3.prepare("insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, numfiles, data_type, series_status, series_createdby, series_createdate) values (:studyRowID, :SeriesDateTime, :ProtocolName, :SequenceName, :SeriesNumber, :RepetitionTime, :EchoTime, :FlipAngle, :pixelX, :pixelY, :SliceThickness, :MagneticFieldStrength, :Rows, :Columns, :zsize, :boldreps, :numfiles, 'parrec', 'complete', 'import', now())");
        q3.bindValue(":studyRowID",studyRowID);
        q3.bindValue(":SeriesDateTime",SeriesDateTime);
        q3.bindValue(":ProtocolName",ProtocolName);
        q3.bindValue(":SequenceName",SequenceName);
        q3.bindValue(":SeriesNumber",SeriesNumber);
        q3.bindValue(":RepetitionTime",RepetitionTime);
        q3.bindValue(":EchoTime",EchoTime);
        q3.bindValue(":FlipAngle",FlipAngle);
        q3.bindValue(":pixelX",pixelX);
        q3.bindValue(":pixelY",pixelY);
        q3.bindValue(":SliceThickness",SliceThickness);
        q3.bindValue(":MagneticFieldStrength",MagneticFieldStrength);
        q3.bindValue(":Rows",Rows);
        q3.bindValue(":Columns",Columns);
        q3.bindValue(":zsize",zsize);
        q3.bindValue(":boldreps",boldreps);
        q3.bindValue(":numfiles",numfiles);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        seriesRowID = q3.lastInsertId().toInt();
        IL_seriescreated = 0;
    }

    /* copy the file to the archive, update db info */
    msgs << n->WriteLog(QString("seriesRowID [%1]").arg(seriesRowID));

    /* create data directory if it doesn't already exist */
    QString outdir = QString("%1/%2/%3/%4/parrec").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    msgs << n->WriteLog("Outdir [" + outdir + "]");
    QString m;
    if (!n->MakePath(outdir, m))
        msgs << "Error creating outdir ["+outdir+"] because of error ["+m+"]";

    /* move the files into the outdir */
    n->MoveFile(parfile, outdir);
    n->MoveFile(recfile, outdir);

    /* insert an import log record (.par file) */
    QSqlQuery q5;
    q5.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, modality_orig, patientid_orig, patientname_orig, stationname_orig, institution_orig, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values (:file, :newfile, 'PARREC', now(), 'successful', :importid, :importid, :importSiteID, :importProjectID, :IL_modality_orig, :PatientID, :IL_patientname_orig, :IL_stationname_orig, :IL_institution_orig, :subjectRealUID, :studynum, :subjectRowID, :studyRowID, :seriesRowID, :enrollmentRowID, :IL_seriescreated, :IL_studycreated, :IL_subjectcreated, :IL_familycreated, :IL_enrollmentcreated, :IL_overwrote_existing)");
    q5.bindValue(":file", file);
    q5.bindValue(":newfile", outdir+"/"+parfile);
    q5.bindValue(":importid", importid);
    q5.bindValue(":importSiteID", importSiteID);
    q5.bindValue(":importProjectID", importProjectID);
    q5.bindValue(":IL_modality_orig", IL_modality_orig);
    q5.bindValue(":PatientID", PatientID);
    q5.bindValue(":IL_patientname_orig", IL_patientname_orig);
    q5.bindValue(":IL_stationname_orig", IL_stationname_orig);
    q5.bindValue(":IL_institution_orig", IL_institution_orig);
    q5.bindValue(":IL_seriesnumber_orig", IL_seriesnumber_orig);
    q5.bindValue(":subjectRealUID", subjectRealUID);
    q5.bindValue(":studynum", studynum);
    q5.bindValue(":subjectRowID", subjectRowID);
    q5.bindValue(":studyRowID", studyRowID);
    q5.bindValue(":seriesRowID", seriesRowID);
    q5.bindValue(":enrollmentRowID", enrollmentRowID);
    q5.bindValue(":IL_seriescreated", IL_seriescreated);
    q5.bindValue(":IL_studycreated", IL_studycreated);
    q5.bindValue(":IL_subjectcreated", IL_subjectcreated);
    q5.bindValue(":IL_familycreated", IL_familycreated);
    q5.bindValue(":IL_enrollmentcreated", IL_enrollmentcreated);
    q5.bindValue(":IL_overwrote_existing", IL_overwrote_existing);
    n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);

    q5.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, modality_orig, patientid_orig, patientname_orig, stationname_orig, institution_orig, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values (:file, :newfile, 'PARREC', now(), 'successful', :importid, :importid, :importSiteID, :importProjectID, :IL_modality_orig, :PatientID, :IL_patientname_orig, :IL_stationname_orig, :IL_institution_orig, :subjectRealUID, :studynum, :subjectRowID, :studyRowID, :seriesRowID, :enrollmentRowID, :IL_seriescreated, :IL_studycreated, :IL_subjectcreated, :IL_familycreated, :IL_enrollmentcreated, :IL_overwrote_existing)");
    q5.bindValue(":file", file);
    q5.bindValue(":newfile", outdir+"/"+parfile);
    q5.bindValue(":importid", importid);
    q5.bindValue(":importSiteID", importSiteID);
    q5.bindValue(":importProjectID", importProjectID);
    q5.bindValue(":IL_modality_orig", IL_modality_orig);
    q5.bindValue(":PatientID", PatientID);
    q5.bindValue(":IL_patientname_orig", IL_patientname_orig);
    q5.bindValue(":IL_stationname_orig", IL_stationname_orig);
    q5.bindValue(":IL_institution_orig", IL_institution_orig);
    q5.bindValue(":IL_seriesnumber_orig", IL_seriesnumber_orig);
    q5.bindValue(":subjectRealUID", subjectRealUID);
    q5.bindValue(":studynum", studynum);
    q5.bindValue(":subjectRowID", subjectRowID);
    q5.bindValue(":studyRowID", studyRowID);
    q5.bindValue(":seriesRowID", seriesRowID);
    q5.bindValue(":enrollmentRowID", enrollmentRowID);
    q5.bindValue(":IL_seriescreated", IL_seriescreated);
    q5.bindValue(":IL_studycreated", IL_studycreated);
    q5.bindValue(":IL_subjectcreated", IL_subjectcreated);
    q5.bindValue(":IL_familycreated", IL_familycreated);
    q5.bindValue(":IL_enrollmentcreated", IL_enrollmentcreated);
    q5.bindValue(":IL_overwrote_existing", IL_overwrote_existing);
    n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);

    /* get the size of the dicom files and update the DB */
    qint64 dirsize(0);
    int nfiles(0);
    n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);

    /* update the database with the correct number of files/BOLD reps */
    if (Modality == "mr") {
        QString sqlstring = QString("update %1_series set series_size = :dirsize where %1series_id = :seriesid").arg(Modality.toLower());
        QSqlQuery q2;
        q2.prepare(sqlstring);
        q2.bindValue(":dirsize", dirsize);
        q2.bindValue(":seriesid", seriesRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    }


    /* change the permissions to 777 so the webpage can read/write the directories */
    QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    n->SystemCommand(systemstring);

    /* copy everything to the backup directory */
    QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    QDir bda(backdir);
    if (!bda.exists()) {
        msgs << "Directory [" + backdir + "] does not exist. About to create it...";
        QString m;
        if (!n->MakePath(backdir, m))
            msgs << "Unable to create backdir [" + backdir + "] because of error [" + m + "]";
        else
            msgs << "Finished creating ["+backdir+"]";
    }
    msgs << "About to copy to the backup directory";
    systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
    msgs << n->SystemCommand(systemstring);
    msgs << "Finished copying to the backup directory";

    msg += msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertEEG -------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::InsertEEG(int importid, QString file, QString &msg) {

    QStringList msgs;

    msgs << n->WriteLog(QString("----- In InsertEEG(%1, %2) -----").arg(importid).arg(file));

    /* import log variables */
    QString IL_modality_orig;
    QString IL_patientname_orig;
    QString IL_patientdob_orig;
    QString IL_patientsex_orig;
    QString IL_stationname_orig;
    QString IL_institution_orig;
    QString IL_studydatetime_orig;
    QString IL_seriesdatetime_orig;
    QString IL_studydesc_orig;
    //double IL_patientage_orig;
    //int IL_seriesnumber_orig;
    QString IL_modality_new;
    QString IL_patientname_new;
    QString IL_patientdob_new;
    QString IL_patientsex_new;
    QString IL_stationname_new;
    QString IL_institution_new;
    QString IL_studydatetime_new;
    QString IL_seriesdatetime_new;
    QString IL_studydesc_new;
    QString IL_seriesdesc_orig;
    QString IL_protocolname_orig;
    QString IL_subject_uid;
    QString IL_project_number;
    //int IL_seriescreated(0);
    //int IL_studycreated(0);
    //int IL_enrollmentcreated(0);
    //int IL_subjectcreated(0);
    //int IL_familycreated(0);
    //int IL_overwrote_existing(0);

    QString familyRealUID;

    int projectRowID(0);
    QString subjectRealUID;
    int subjectRowID(0);
    int enrollmentRowID;
    int studyRowID(0);
    int seriesRowID(0);
    QString costcenter;
    int studynum(0);

    QString PatientName = "NotSpecified";
    QString PatientBirthDate = "0001-01-01";
    QString PatientID = "NotSpecified";
    QString PatientSex = "U";
    QString StudyDescription = "NotSpecified";
    QString SeriesDescription;
    QString StationName = "";
    QString OperatorsName = "NotSpecified";
    QString PerformingPhysiciansName = "NotSpecified";
    QString InstitutionName = "NotSpecified";
    QString InstitutionAddress = "NotSpecified";
    QString SequenceName;
    QString ProtocolName;
    QString StudyDateTime;
    QString SeriesDateTime;
    QString Modality = "EEG";
    int SeriesNumber(0);
    int FileNumber(0);
    int numfiles = 1;

    //int importInstanceID(0);
    //int importSiteID(0);
    int importProjectID(0);
    //int importPermanent(0);
    //int importAnonymize(0);
    //int importMatchIDOnly(0);
    //QString importUUID;
    QString importSeriesNotes;
    QString importAltUIDs;

    /* if there is an importid, check to see how that thing is doing */
    if (importid > 0) {
        QSqlQuery q;
        q.prepare("select * from import_requests where importrequest_id = :importid");
        q.bindValue(":importid", importid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            QString status = q.value("import_status").toString();
            //importInstanceID = q.value("import_instanceid").toInt();
            //importSiteID = q.value("import_siteid").toInt();
            importProjectID = q.value("import_projectid").toInt();
            //importPermanent = q.value("import_permanent").toInt();
            //importAnonymize = q.value("import_anonymize").toInt();
            //importMatchIDOnly = q.value("import_matchidonly").toInt();
            //importUUID = q.value("import_uuid").toString();
            importSeriesNotes = q.value("import_seriesnotes").toString();
            importAltUIDs = q.value("import_altuids").toString();
        }
    }
    else {
        msgs << n->WriteLog(QString("ImportID [%1] not found. Using default import parameters").arg(importid));
    }

    msgs << n->WriteLog(file);
    /* split the filename into the appropriate fields */
    /* AltUID_Date_task_operator_series.* */
    QString FileName = QFileInfo(file).baseName();
    //FileName.replace(QRegExp("\\..*+$",Qt::CaseInsensitive),""); // remove everything after the first dot
    QStringList parts = FileName.split("_");

    /* get the values as they should be ... */
    if (parts.size() > 0)
        PatientID = parts[0].trimmed();

    if (parts.size() > 1) {
        if (parts[1].size() == 6) {
            StudyDateTime = SeriesDateTime = parts[1].mid(4,2) + "-" + parts[1].mid(0,2) + "-" + parts[1].mid(2,2) + " 00:00:00";
        }
        else if (parts[1].size() == 8) {
            StudyDateTime = SeriesDateTime = parts[1].mid(0,4) + "-" + parts[1].mid(4,2) + "-" + parts[1].mid(6,2) + " 00:00:00";
        }
        else if (parts[1].size() == 12) {
            StudyDateTime = SeriesDateTime = parts[1].mid(0,4) + "-" + parts[1].mid(4,2) + "-" + parts[1].mid(6,2) + " " + parts[1].mid(8,2) + ":" + parts[1].mid(10,2) + ":00";
        }
        else if (parts[1].size() >= 14) {
            StudyDateTime = SeriesDateTime = parts[1].mid(0,4) + "-" + parts[1].mid(4,2) + "-" + parts[1].mid(6,2) + " " + parts[1].mid(8,2) + ":" + parts[1].mid(10,2) + ":" + parts[1].mid(12,2);
        }
    }

    if (parts.size() > 2)
        SeriesDescription = ProtocolName = parts[2].trimmed();

    if (parts.size() > 3)
        OperatorsName = parts[3].trimmed();

    if (parts.size() > 4)
        SeriesNumber = parts[4].trimmed().toInt();

    if (parts.size() > 5)
        FileNumber = parts[5].trimmed().toInt();

    msgs << n->WriteLog(QString("Before fixing: PatientID [%1], StudyDateTime [%2], SeriesDateTime [%3], SeriesDescription [%4], OperatorsName [%5], SeriesNumber [%6], FileNumber [%7]").arg(PatientID).arg(StudyDateTime).arg(SeriesDateTime).arg(SeriesDescription).arg(OperatorsName).arg(SeriesNumber).arg(FileNumber));

    /* check if anything is still funny */
    if (StudyDateTime == "") StudyDateTime = "0000-00-00 00:00:00";
    if (SeriesDateTime == "") SeriesDateTime = "0000-00-00 00:00:00";
    if (SeriesDescription == "") SeriesDescription = "Unknown";
    if (ProtocolName == "") ProtocolName = "Unknown";
    if (OperatorsName == "") OperatorsName = "Unknown";
    if (SeriesNumber < 1) SeriesNumber = 1;

    msgs << n->WriteLog(QString("Before fixing: PatientID [%1], StudyDateTime [%2], SeriesDateTime [%3], SeriesDescription [%4], OperatorsName [%5], SeriesNumber [%6], FileNumber [%7]").arg(PatientID).arg(StudyDateTime).arg(SeriesDateTime).arg(SeriesDescription).arg(OperatorsName).arg(SeriesNumber).arg(FileNumber));

    /* set the import log variables */
    IL_modality_orig = Modality;
    IL_patientname_orig = PatientName;
    IL_patientdob_orig = PatientBirthDate;
    IL_patientsex_orig = PatientSex;
    IL_stationname_orig = StationName;
    IL_institution_orig = InstitutionName + " - " + InstitutionAddress;
    IL_studydatetime_orig = StudyDateTime;
    IL_seriesdatetime_orig = SeriesDateTime;
    //IL_seriesnumber_orig = SeriesNumber;
    IL_studydesc_orig = StudyDescription;
    IL_seriesdesc_orig = ProtocolName;
    IL_protocolname_orig = ProtocolName;
    //IL_patientage_orig = 0;

    msgs << n->WriteLog(PatientID + " - " + StudyDescription);

    /* get the ID search string */
    QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
    QStringList altuidlist;
    if (importAltUIDs != "") {
        altuidlist = importAltUIDs.split(",");
        altuidlist.removeDuplicates();
    }

    // check if the project and subject exist
    msgs << "Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]";
    //int projectcount(0);
    //int subjectcount(0);
    QString sqlstring = QString("SELECT a.subject_id, a.uid FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (%1) or a.uid = SHA1(:PatientID) or b.altuid in (%1) or b.altuid = SHA1(:PatientID)").arg(SQLIDs);
    QSqlQuery q;
    q.prepare(sqlstring);
    q.bindValue(":PatientID", PatientID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, false);
    if (q.size() > 0) {
        q.first();
        subjectRowID = q.value("subject_id").toInt();
        subjectRealUID = q.value("uid").toString().trimmed();
    }
    else {
        /* subject doesn't already exist. Not creating new subjects as part of EEG/ET/etc upload because we have no age, DOB, sex. So note this failure in the import_logs table */
        msgs << n->WriteLog(QString("Subject with ID [%1] does not exist. Subjects must exist prior to EEG/ET import").arg(PatientID));
        msg += msgs.join("\n");
        return false;
    }

    if (subjectRealUID == "") {
        msgs << n->WriteLog("ERROR: UID blank");
        msg += msgs.join("\n");
        return false;
    }
    else
        msgs << n->WriteLog("UID found [" + subjectRealUID + "]");

    /* get the projectRowID */
    if (importProjectID == 0) {
        QSqlQuery q2;
        q2.prepare("select project_id from projects where project_costcenter = :costcenter");
        q2.bindValue(":costcenter", costcenter);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            projectRowID = q2.value("project_id").toInt();
        }
    }
    else {
        /* need to create the project if it doesn't exist */
        msgs << QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID);
        projectRowID = importProjectID;
    }

    /* check if the subject is enrolled in the project */
    QSqlQuery q2;
    q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
    q2.bindValue(":subjectid", subjectRowID);
    q2.bindValue(":projectid", projectRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        enrollmentRowID = q2.value("enrollment_id").toInt();
        msgs << QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID);
        //IL_enrollmentcreated = 0;
    }
    else {
        /* create enrollmentRowID if it doesn't exist */
        q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
        q2.bindValue(":subjectid", subjectRowID);
        q2.bindValue(":projectid", projectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        enrollmentRowID = q2.lastInsertId().toInt();

        msgs << QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID);
        //IL_enrollmentcreated = 1;
    }

    // now determine if this study exists or not...
    // basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
    // also checks the accession number against the study_num to see if this study was pre-registered
    q2.prepare("select study_id, study_num from studies where enrollment_id = :enrollmentRowID and (study_datetime = '" + StudyDateTime + "' and study_modality = :Modality and study_site = :StationName)");
    q2.bindValue(":enrollmentRowID", enrollmentRowID);
    q2.bindValue(":Modality", Modality);
    q2.bindValue(":StationName", StationName);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        studyRowID = q2.value("study_id").toInt();
        studynum =  q2.value("study_num").toInt();

        QSqlQuery q3;
        q3.prepare("update studies set study_modality = :Modality, study_datetime = '" + StudyDateTime + "', study_desc = :StudyDescription, study_operator = :OperatorsName, study_performingphysician = :PerformingPhysiciansName, study_site = :StationName, study_institution = :Institution, study_status = 'complete' where study_id = :studyRowID");
        q3.bindValue(":Modality", Modality);
        q3.bindValue(":StudyDescription", StudyDescription);
        q3.bindValue(":OperatorsName", OperatorsName);
        q3.bindValue(":PerformingPhysiciansName", PerformingPhysiciansName);
        q3.bindValue(":StationName", StationName);
        q3.bindValue(":Institution", InstitutionName + " - " + InstitutionAddress);
        q3.bindValue(":studyRowID", studyRowID);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        //IL_studycreated = 0;
    }
    else {
        /* create studyRowID if it doesn't exist */
        QSqlQuery q3;
        q3.prepare("SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = :subjectRowID");
        q3.bindValue(":subjectRowID", subjectRowID);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        if (q3.size() > 0) {
            q3.first();
            studynum = q3.value("study_num").toInt() + 1;
        }

        q3.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentRowID, :studynum, :PatientID, :Modality, '"+StudyDateTime+"', :StudyDescription, :OperatorsName, :PerformingPhysiciansName, :StationName, :Institution, 'complete', 'import', now())");
        q3.bindValue(":enrollmentRowID", enrollmentRowID);
        q3.bindValue(":studynum", studynum);
        q3.bindValue(":PatientID", PatientID);
        q3.bindValue(":Modality", Modality);
        q3.bindValue(":StudyDescription", StudyDescription);
        q3.bindValue(":OperatorsName", OperatorsName);
        q3.bindValue(":PerformingPhysiciansName", PerformingPhysiciansName);
        q3.bindValue(":StationName", StationName);
        q3.bindValue(":Institution", InstitutionName + " - " + InstitutionAddress);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        studyRowID = q3.lastInsertId().toInt();
        //IL_studycreated = 1;
    }

    /* ----- insert or update the series ----- */
    q2.prepare(QString("select %1series_id from %1_series where study_id = :studyRowID and series_num = :SeriesNumber").arg(Modality.toLower()));
    q2.bindValue(":studyRowID", studyRowID);
    q2.bindValue(":SeriesNumber", SeriesNumber);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    if (q2.size() > 0) {
        q2.first();
        seriesRowID = q2.value(Modality.toLower() + "series_id").toInt();

        QSqlQuery q3;
        q3.prepare(QString("update %1_series set series_datetime = :SeriesDateTime, series_desc = :ProtocolName, series_protocol = :ProtocolName, series_numfiles = :numfiles, series_notes = :importSeriesNotes where %1series_id = :seriesRowID").arg(Modality.toLower()));
        q3.bindValue(":SeriesDateTime", SeriesDateTime);
        q3.bindValue(":ProtocolName", ProtocolName);
        q3.bindValue(":numfiles", numfiles);
        q3.bindValue(":importSeriesNotes", importSeriesNotes);
        q3.bindValue(":seriesRowID", seriesRowID);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        //IL_seriescreated = 0;
    }
    else {
        /* create seriesRowID if it doesn't exist */
        QSqlQuery q3;
        q3.prepare(QString("insert into %1_series (study_id, series_datetime, series_desc, series_protocol, series_num, series_numfiles, series_notes, series_createdby) values (:studyRowID, :SeriesDateTime, :ProtocolName, :ProtocolName, :SeriesNumber, :numfiles, :importSeriesNotes, 'import')").arg(Modality.toLower()));
        q3.bindValue(":studyRowID", studyRowID);
        q3.bindValue(":SeriesDateTime", SeriesDateTime);
        q3.bindValue(":ProtocolName", ProtocolName);
        q3.bindValue(":SeriesNumber", SeriesNumber);
        q3.bindValue(":numfiles", numfiles);
        q3.bindValue(":importSeriesNotes", importSeriesNotes);
        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        seriesRowID = q3.lastInsertId().toInt();
        //IL_seriescreated = 1;
    }

    /* copy the file to the archive, update db info */
    msgs << n->WriteLog(QString("seriesRowID [%1]").arg(seriesRowID));

    /* create data directory if it doesn't already exist */
    QString outdir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber).arg(Modality.toLower());
    msgs << n->WriteLog("Creating outdir ["+outdir+"]");
    QString m;
    if (!n->MakePath(outdir,m))
        msgs << n->WriteLog("Unable to create directory ["+outdir+"] because of error ["+m+"]");

    /* move the files into the outdir */
    msgs << n->WriteLog("Moving ["+file+"] -> ["+outdir+"]");
    if (!n->MoveFile(file, outdir))
        n->WriteLog("Unable to move ["+file+"] to ["+outdir+"]");

    // insert an import log record
    //$sqlstring = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$file', '" . uc($Modality) . "', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')";
    //$report .= WriteLog("Inside InsertEEG() [$sqlstring]") . "\n";
    //$result = SQLQuery($sqlstring, __FILE__, __LINE__);

    /* get the size of the files and update the DB */
    qint64 dirsize(0);
    int nfiles(0);
    n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);
    q2.prepare(QString("update %1_series set series_size = :dirsize where %1series_id = :seriesRowID").arg(Modality.toLower()));
    q2.bindValue(":dirsize", dirsize);
    q2.bindValue(":seriesRowID", seriesRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

    /* change the permissions to 777 so the webpage can read/write the directories */
    QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    n->SystemCommand(systemstring);

    /* copy everything to the backup directory */
    QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
    QDir bda(backdir);
    if (!bda.exists()) {
        msgs << "Directory [" + backdir + "] does not exist. About to create it...";
        QString m;
        if (!n->MakePath(backdir, m))
            msgs << "Unable to create backdir [" + backdir + "] because of error [" + m + "]";
        else
            msgs << "Created backdir [" + backdir + "]";
    }
    msgs << "About to copy to the backup directory";
    systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
    msgs << n->SystemCommand(systemstring);
    msgs << "Finished copying to the backup directory";

    msg += msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetCostCenter ---------------------------------- */
/* ---------------------------------------------------------- */
QString archiveIO::GetCostCenter(QString studydesc) {
    QString cc;

    /* extract the costcenter */
    if (studydesc.contains("clinical",Qt::CaseInsensitive))
        cc = "888888";
    else if ( (studydesc.contains("(")) && (studydesc.contains(")")) ) /* if it contains an opening and closing parentheses */
    {
        int idx1 = studydesc.indexOf("(");
        int idx2 = studydesc.lastIndexOf(")");
        cc = studydesc.mid(idx1+1, idx2-idx1-1);
    }
    else {
        cc = studydesc;
    }

    return cc;
}


/* ---------------------------------------------------------- */
/* --------- CreateIDSearchList ----------------------------- */
/* ---------------------------------------------------------- */
QString archiveIO::CreateIDSearchList(QString PatientID, QString altuids) {
    /* create the possible ID search lists and arrays */
    QStringList altuidlist;
    QStringList idsearchlist;
    if (altuids != "")
        altuidlist = altuids.split(",");

    idsearchlist.append(PatientID);
    idsearchlist.append(altuidlist);
    idsearchlist.removeDuplicates();

    QString SQLIDs = "'" + idsearchlist.first() + "'";
    idsearchlist.removeFirst();
    foreach (QString tmpID, idsearchlist) {
        if ((tmpID != "") && (tmpID != "none") && (tmpID.toLower() != "na") && (tmpID != "0") && (tmpID.toLower() != "null"))
            SQLIDs += ",'" + tmpID + "'";
    }

    return SQLIDs;
}


/* ---------------------------------------------------------- */
/* --------- CreateThumbnail -------------------------------- */
/* ---------------------------------------------------------- */
void archiveIO::CreateThumbnail(QString f, QString outdir) {

    QString outfile = outdir + "/thumb.png";

    QString systemstring = "convert -normalize " + f + " " + outfile;
    n->WriteLog(n->SystemCommand(systemstring));
}


/* ---------------------------------------------------------- */
/* --------- GetPatientAge ---------------------------------- */
/* ---------------------------------------------------------- */
double archiveIO::GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate) {
    double PatientAge;

    /* check if the patient age contains any characters */
    if (PatientAgeStr.contains('Y')) PatientAge = PatientAgeStr.replace("Y","").toDouble();
    if (PatientAgeStr.contains('M')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/12.0;
    if (PatientAgeStr.contains('W')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/52.0;
    if (PatientAgeStr.contains('D')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/365.25;

    /* fix patient age */
    if (PatientAge < 0.001) {
        QDate studydate;
        QDate dob;
        studydate.fromString(StudyDate);
        dob.fromString(PatientBirthDate);

        PatientAge = dob.daysTo(studydate)/365.25;
    }

    return PatientAge;
}


/* ---------------------------------------------------------- */
/* --------- GetSubject ------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::GetSubject(QString matchcriteria, int existingSubjectID, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, double PatientWeight, double PatientSize, QString SQLIDs, int &subjectRowID, QString &subjectUID, bool &subjectCreated, QString &m) {
    bool ret = true;
    QStringList msgs;
    subjectRowID = -1;
    subjectUID = "";
    subjectCreated = false;

    /* if we already know the subjectID for this subject, get the UID */
    if (existingSubjectID >= 0) {
        subject s(existingSubjectID, n);
        if (s.isValid) {
            subjectUID = s.uid;
            subjectRowID = existingSubjectID;
        }
        else {
            msgs << n->WriteLog("Existing subjectID was not valid: [" + s.msg + "]");
            return false;
        }
    }

    /* otherwise, try to find the subject, and create subject if necessary */
    if (matchcriteria == "PatientID") {
        msgs << "Matching subject by PatientID";

        /* get the existing subject ID, and UID! (the PatientID may be an alternate UID) */
        QString sqlstring = "SELECT a.subject_id, a.uid FROM subjects a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (" + SQLIDs + ") or a.uid = SHA1(:patientid) or b.altuid in (" + SQLIDs + ") or b.altuid = SHA1(:patientid)";
        QSqlQuery q;
        q.prepare(sqlstring);
        q.bindValue(":patientid", PatientID);
        n->SQLQuery(q,__FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            subjectRowID = q.value("subject_id").toInt();
            subjectUID = q.value("uid").toString().toUpper().trimmed();

            msgs << n->WriteLog(QString("Found subject [%1, " + subjectUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID));
        }
        else {
            msgs << n->WriteLog("Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]");
            CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, msgs, subjectRowID, subjectUID);
            subjectCreated = true;
        }
    }
    else if (matchcriteria == "NameSexDOB") {
        msgs << "Matching subject by NameSexDOB";
        QString sqlstring = "select subject_id, uid from subjects where name like '%" + PatientName + "%' and gender = left('" + PatientSex + "',1) and birthdate = :dob and isactive = 1";
        QSqlQuery q;
        q.prepare(sqlstring);
        q.bindValue(":dob", PatientBirthDate);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            subjectUID = q.value("uid").toString();
            subjectRowID = q.value("subject_id").toInt();
            msgs << n->WriteLog("This subject exists. UID [" + subjectUID + "]");
        }
        else {
            msgs << n->WriteLog("Could not the find this subject. Searched for PatientName [" + PatientName + "]  PatientSex [" + PatientSex + "]  PatientBirthDate [" + PatientBirthDate + "]");
            CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, msgs, subjectRowID, subjectUID);
            subjectCreated = true;
        }
    }
    else {
        msgs << QString("Unknown subject match criteria [%1]").arg(matchcriteria);
        ret = false;
    }

    m = msgs.join("\n");
    return ret;
}
