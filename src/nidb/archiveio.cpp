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
#include "study.h"

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
/* --------- Setr  ------------------------------------ */
/* ---------------------------------------------------------- */
void archiveIO::SetUploadID(int u) {
    uploadid = u;
}


/* ---------------------------------------------------------- */
/* --------- ArchiveDICOMSeries ------------------------------ */
/* ---------------------------------------------------------- */
bool archiveIO::ArchiveDICOMSeries(int importid, int existingSubjectID, int existingStudyID, int existingSeriesID, QString subjectMatchCriteria, QString studyMatchCriteria, QString seriesMatchCriteria, int destProjectID, QString specificPatientID, int destSiteID, QString altUIDstr, QString seriesNotes, QStringList files) {

    AppendUploadLog(__FUNCTION__ , QString("Beginning to archive this DICOM series (%1, %2, %3, %4, %5, %6, %7, %8, %9, %10, %11, %12)").arg(importid).arg(existingSubjectID).arg(existingStudyID).arg(existingSeriesID).arg(subjectMatchCriteria).arg(studyMatchCriteria).arg(seriesMatchCriteria).arg(destProjectID).arg(specificPatientID).arg(destSiteID).arg(altUIDstr).arg(seriesNotes));

    /* in case this function is called with capitalized criteria */
    subjectMatchCriteria = subjectMatchCriteria.toLower();
    studyMatchCriteria = studyMatchCriteria.toLower();
    seriesMatchCriteria = seriesMatchCriteria.toLower();


    /* check if there are any files to archive */
    if (files.size() < 1) {
        AppendUploadLog(__FUNCTION__ , "No DICOM files to archive");
        return false;
    }

    /* check if the first file exists */
    if (!QFile::exists(files[0])) {
        AppendUploadLog(__FUNCTION__ , QString("File [%1] does not exist - check A!").arg(files[0]));
        return 0;
    }

    n->SortQStringListNaturally(files);

    /* check if the first file exists after sorting */
    if (!QFile::exists(files[0])) {
        AppendUploadLog(__FUNCTION__ , QString("File [%1] does not exist - check B!").arg(files[0]));
        return 0;
    }

    int subjectRowID(-1);
    QString subjectUID;
    QString familyUID;
    int familyRowID(-1);
    int projectRowID(-1);
    int enrollmentRowID(-1);
    int studyRowID(-1);
    int seriesRowID(-1);
    int studynum(-1);

    /* get all the DICOM tags */
    QHash<QString, QString> tags;
    QString f = files[0];

    if (!QFile::exists(f)) {
        AppendUploadLog(__FUNCTION__ , QString("File [%1] does not exist - check C!").arg(f));
        return 0;
    }

    if (n->GetImageFileTags(f, tags)) {
        if (!QFile::exists(f)) {
            AppendUploadLog(__FUNCTION__ , QString("File [%1] does not exist - check D!").arg(f));
            return 0;
        }
    }

    QString InstitutionName = tags["InstitutionName"];
    QString InstitutionAddress = tags["InstitutionAddress"];
    QString Modality = tags["Modality"];
    QString StationName = tags["StationName"];
    QString OperatorsName = tags["OperatorsName"];
    QString PatientID = tags["PatientID"].toUpper();
    QString ParentDirectory = tags["ParentDirectory"].toUpper();
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
    QString SeriesDateTime = tags["SeriesDateTime"];
    QString StudyDescription = tags["StudyDescription"];
    QString SeriesDescription = tags["SeriesDescription"];
    QString StudyTime = tags["StudyTime"];
    QString StudyDateTime = tags["StudyDateTime"];
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
    QString StudyInstanceUID = tags["StudyInstanceUID"];
    int mat1 = tags["mat1"].toInt();
    int mat4 = tags["mat4"].toInt();
    int pixelX = tags["pixelX"].toInt();
    int pixelY = tags["pixelY"].toInt();

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
    QString PhaseEncodeAngle = tags["PhaseEncodeAngle"];
    QString PhaseEncodingDirectionPositive = tags["PhaseEncodingDirectionPositive"];

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

    if (subjectMatchCriteria == "specificpatientid")
        PatientID = specificPatientID;
    else if (subjectMatchCriteria == "patientidfromdir")
        PatientID = ParentDirectory;


    /* get the ID search string */
    //QString SQLIDs = CreateIDSearchList(PatientID, altUIDstr);
    QStringList altuidlist;
    if (altUIDstr != "")
        altuidlist = altUIDstr.split(",");

    /* check the alternate UIDs */
    foreach (QString altuid, altuidlist) {
        if (altuid.trimmed().size() > 254)
            AppendUploadLog(__FUNCTION__ , "Alternate UID [" + altuid.left(255) + "...] is longer than 255 characters and will be truncated");
    }

    /* ----- get/set the subjectID */
    if (GetSubject(subjectMatchCriteria, existingSubjectID, PatientID, PatientName, PatientSex, PatientBirthDate, subjectRowID, subjectUID))
        AppendUploadLog(__FUNCTION__, QString("SubjectRowID [%1] found").arg(subjectRowID));
    else
        CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, subjectRowID, subjectUID);

    /* ----- get/set family ID ----- */
    if (GetFamily(subjectRowID, subjectUID, familyRowID, familyUID))
        AppendUploadLog(__FUNCTION__ , QString("GetFamily() returned familyID [%1]  familyUID [%2]").arg(familyRowID).arg(familyUID));
    else
        AppendUploadLog(__FUNCTION__ , QString("GetFamily() returned error: familyID [%1]  familyUID [%2]").arg(familyRowID).arg(familyUID));

    /* ----- get the project ID ----- */
    if (!GetProject(destProjectID, StudyDescription, projectRowID))
        AppendUploadLog(__FUNCTION__ , QString("GetProject() returned error: projectRowID [%1]").arg(projectRowID));

    /* ----- get/create enrollment ID ----- */
    if (GetEnrollment(subjectRowID, projectRowID, enrollmentRowID))
        AppendUploadLog(__FUNCTION__ , QString("GetEnrollment returned enrollmentRowID [%1]").arg(enrollmentRowID));
    else
        AppendUploadLog(__FUNCTION__ , QString("GetEnrollment returned error: enrollmentRowID [%1]").arg(enrollmentRowID));

    /* set the alternate IDs for this enrollment */
    SetAlternateIDs(subjectRowID, enrollmentRowID, altuidlist);

    /* ----- get/create studyID ----- */
    if (GetStudy(studyMatchCriteria, existingStudyID, enrollmentRowID, StudyDateTime, Modality, StudyInstanceUID, studyRowID))
        AppendUploadLog(__FUNCTION__, QString("StudyRowID [%1] found").arg(studyRowID));
    else
        CreateStudy(subjectRowID, enrollmentRowID, StudyDateTime, StudyInstanceUID, Modality, PatientID, PatientAge, PatientSize, PatientWeight, StudyDescription, OperatorsName, PerformingPhysiciansName, StationName, InstitutionName, InstitutionAddress, studyRowID, studynum);

    /* ----- if we couldn't find/create any of the: subjectRowID, projectRowID, enrollmentRowID, studyRowID, then there's nothing more to do in this function, and we have to exit ----- */
    if ((subjectRowID < 0) || (projectRowID < 0) || (enrollmentRowID < 0) || (studyRowID < 0)) {
        AppendUploadLog(__FUNCTION__ , QString("Error finding/creating one of the rowIDs:  subjectRowID [%1]  projectRowID [%2]  enrollmentRowID [%3]  studyRowID [%4]").arg(subjectRowID).arg(projectRowID).arg(enrollmentRowID).arg(studyRowID));
        return 0;
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
        QSqlQuery q2;
        dbModality = "mr";
        q2.prepare("select mrseries_id from mr_series where study_id = :studyid and series_num = :seriesnum");
        q2.bindValue(":studyid", studyRowID);
        q2.bindValue(":seriesnum", SeriesNumber);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            seriesRowID = q2.value("mrseries_id").toInt();

            AppendUploadLog(__FUNCTION__ , QString("This MR series [%1] exists, updating").arg(SeriesNumber));

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

            /* if the series is being updated, the QA information might be incorrect or be based on the wrong number of files, so delete the mr_qa row */
            q3.prepare("delete from mr_qa where mrseries_id = :seriesid");
            q3.bindValue(0,seriesRowID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

            AppendUploadLog(__FUNCTION__ , "Deleted from mr_qa table, now deleting from qc_results");

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

            AppendUploadLog(__FUNCTION__ , "Deleted from qc_results table, now deleting from qc_moduleseries");

            q3.prepare("delete from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
            q3.bindValue(0,seriesRowID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
        }
        else {
            /* create seriesRowID if it doesn't exist */
            AppendUploadLog(__FUNCTION__ , QString("MR series [%1] did not exist, creating").arg(SeriesNumber));
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
            AppendUploadLog(__FUNCTION__ , QString("This CT series [%1] exists, updating").arg(SeriesNumber));
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
            AppendUploadLog(__FUNCTION__ , QString("CT series [%1] did not exist, creating").arg(SeriesNumber));
        }
    }
    else {
        /* this is the catch all for modalities which don't have a table in the database */

        n->WriteLog(QString("Modality [%1] numfiles [%2] zsize [%3]").arg(Modality).arg(numfiles).arg(zsize));

        if (n->isValidNiDBModality(Modality))
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
            AppendUploadLog(__FUNCTION__ , QString("This %1 series [%2] exists, updating").arg(Modality).arg(SeriesNumber));
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
        }
        else {
            /* create seriesRowID if it doesn't exist */
            AppendUploadLog(__FUNCTION__ , QString(Modality + " series [%1] did not exist, creating").arg(SeriesNumber));
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
        }
    }

    /* copy the file to the archive, update db info */
    AppendUploadLog(__FUNCTION__ , QString("SeriesRowID: [%1]").arg(seriesRowID));

    study *s = NULL;
    s = new study(studyRowID, n);
    if ((s == NULL) || (!s->valid())) {
        AppendUploadLog(__FUNCTION__ , QString("Error getting study information. StudyRowID [%1] not valid").arg(studyRowID));
        return false;
    }
    studynum = s->studyNum();

    /* create data directory if it doesn't already exist */
    QString outdir = QString("%1/%2/dicom").arg(s->path()).arg(SeriesNumber);
    QString thumbdir = QString("%1/%4").arg(s->path()).arg(SeriesNumber);

    QString m;
    if (!n->MakePath(outdir, m))
        AppendUploadLog(__FUNCTION__ , "Unable to create output direcrory [" + outdir + "] because of error [" + m + "]");
    else
        AppendUploadLog(__FUNCTION__ , "Created outdir ["+outdir+"]");

    /* check if there are .dcm files already in the archive (outdir) */
    AppendUploadLog(__FUNCTION__ , "Checking for existing files in outdir [" + outdir + "]");
    QStringList existingdcms = n->FindAllFiles(outdir, "*.dcm");
    int numexistingdcms = existingdcms.size();

    /* rename **** EXISTING **** files in the output directory */
    if (numexistingdcms > 0) {
        /* check all files to see if its the same study datetime, patient name, dob, gender, series #
         * 1) if anything is different, move the file to a UID/Study/Series/dicom/existing directory
         * 2) if they're all the same, consolidate the files into one list of new and old, remove duplicates */

        QString logmsg = QString("There are [%1] existing files in [%2]. Beginning renaming of existing files [").arg(numexistingdcms).arg(outdir);

        int filecnt = 0;
        /* rename the existing files to make them unique */
        foreach (QString file, existingdcms) {
            QFileInfo f(file);

            /* check if its already in the intended filename format */
            QString fname = f.fileName();
            QStringList parts = fname.split("_");
            if (parts.size() == 8) {
                if ((subjectUID == parts[0]) && (studynum == parts[1]) && (SeriesNumber == parts[2])) {
                    logmsg += "-";
                    continue;
                }
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

            QString newfname = QString("%1_%2_%3_%4_%5_%6_%7_%8.dcm").arg(subjectUID).arg(studynum).arg(SeriesNumber).arg(SliceNumber, 5, 10, QChar('0')).arg(InstanceNumber, 5, 10, QChar('0')).arg(AcquisitionTime).arg(ContentTime).arg(SOPInstance);
            QString newfile = outdir + "/" + newfname;

            if (file == newfile)
                logmsg += "?";
            else
                n->RenameFile(file, newfile); /* don't care about return value here, because the old filename may have been the same as the new one */

            logmsg += ".";
            filecnt++;
        }
        AppendUploadLog(__FUNCTION__ , QString(logmsg + "]  Done renaming existings [%1] files").arg(filecnt));
    }

    /* create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size) */
    CreateThumbnail(files[files.size()/2], thumbdir);

    /* renumber the **** NEWLY **** added files to make them unique */
    QString logmsg = "Renaming new files [";
    int filecnt = 0;
    foreach (QString file, files) {
        /* need to rename it, get the DICOM tags */
        QHash<QString, QString> tags;
        if (!n->GetImageFileTags(file, tags)) {
            logmsg += "?";
            continue;
        }

        int SliceNumber = tags["AcquisitionNumber"].toInt();
        int InstanceNumber = tags["InstanceNumber"].toInt();
        QString AcquisitionTime = tags["AcquisitionTime"];
        QString ContentTime = tags["ContentTime"];
        //QString SliceLocation = tags["SliceLocation"];
        QString SOPInstance = tags["SOPInstanceUID"];
        AcquisitionTime.remove(":").remove(".");
        ContentTime.remove(":").remove(".");
        SOPInstance = QString(QCryptographicHash::hash(SOPInstance.toUtf8(),QCryptographicHash::Md5).toHex());

        QString newfname = QString("%1_%2_%3_%4_%5_%6_%7_%8.dcm").arg(subjectUID).arg(studynum).arg(SeriesNumber).arg(SliceNumber, 5, 10, QChar('0')).arg(InstanceNumber, 5, 10, QChar('0')).arg(AcquisitionTime).arg(ContentTime).arg(SOPInstance);
        QString newfile = outdir + "/" + newfname;

        /* check if a file with the same name already exists */
        if (QFile::exists(newfile)) {
            /* remove the existing file */
            QFile::remove(newfile);
        }
        else {
        }

        /* move & rename the file */
        if (!n->RenameFile(file, newfile))
            AppendUploadLog(__FUNCTION__ , "Unable to rename newly added file [" + file + "] to [" + newfile + "]");
        else {
            logmsg += ".";
            filecnt++;
        }
    }

    AppendUploadLog(__FUNCTION__ , QString(logmsg + "]  Done renaming [%1] new files").arg(filecnt));

    /* get the size of the dicom files and update the DB */
    qint64 dirsize = 0;
    int nfiles;
    n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);
    AppendUploadLog(__FUNCTION__ , QString("Archive directory [%1] is [%2] bytes in size and contains [%3] files").arg(outdir).arg(dirsize).arg(nfiles));

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
    QString outbehdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);

    if (importid > 0) {
        AppendUploadLog(__FUNCTION__ , "Checking for behavioral data in [" + inbehdir + "]");
        QDir bd(inbehdir);
        if (bd.exists()) {
            QString m;
            if (n->MakePath(outbehdir, m)) {
                QString systemstring = "mv -v " + inbehdir + "/* " + outbehdir + "/";
                AppendUploadLog(__FUNCTION__ , n->SystemCommand(systemstring));

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
                AppendUploadLog(__FUNCTION__ , "Unable to create outbehdir ["+outbehdir+"] because of error ["+m+"]");
        }
    }

    QString systemstring;

    /* change the permissions on the outdir to 777 so the webpage can read/write the directories */
    systemstring = "chmod -Rf 777 " + outdir;
    AppendUploadLog(__FUNCTION__ , n->SystemCommand(systemstring));

    /* copy everything to the backup directory */
    QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);
    QDir bda(backdir);
    if (!bda.exists()) {
        AppendUploadLog(__FUNCTION__ , "Backup directory [" + backdir + "] does not exist. About to create it...");
        QString m;
        if (!n->MakePath(backdir, m))
            AppendUploadLog(__FUNCTION__ , "Unable to create backdir [" + backdir + "] because of error [" + m + "]");
    }
    AppendUploadLog(__FUNCTION__ , "Starting copy to the backup directory");
    systemstring = QString("rsync -az %1/* %2").arg(outdir).arg(backdir);
    AppendUploadLog(__FUNCTION__ , n->SystemCommand(systemstring));
    AppendUploadLog(__FUNCTION__ , "Finished copying to the backup directory");

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- InsertParRec ----------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::InsertParRec(int importid, QString file) {

    AppendUploadLog(__FUNCTION__, QString("----- In InsertParRec(%1,%2) -----").arg(importid).arg(file));

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
        AppendUploadLog(__FUNCTION__, "Unable to read file [" + file + "]");
        return 0;
    }

    /* check if anything is funny, and not compatible with archiving this data */
    if (SeriesNumber == 0) {
        AppendUploadLog(__FUNCTION__, "Series number is 0");
        return 0;
    }
    if (PatientName == "") {
        AppendUploadLog(__FUNCTION__, "PatientName (ID) is blank");
        return 0;
    }

    /* ----- check if this subject/study/series/etc exists ----- */
    int projectRowID(0);
    QString subjectUID = PatientName;
    int subjectRowID(0);
    int enrollmentRowID;
    int studyRowID(0);
    int seriesRowID;
    QString costcenter;
    int studynum(0);

    /* get the costcenter */
    costcenter = GetCostCenter(StudyDescription);

    AppendUploadLog(__FUNCTION__, PatientID + " - " + StudyDescription);

    /* get the ID search string */
    QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
    QStringList altuidlist;
    if (importAltUIDs != "")
        altuidlist = importAltUIDs.split(",");

    /* check the alternate UIDs */
    foreach (QString altuid, altuidlist) {
        if (altuid.trimmed().size() > 254)
            AppendUploadLog(__FUNCTION__, "Alternate UID [" + altuid.left(255) + "...] is longer than 255 characters and will be truncated");
    }

    /* check if the project and subject exist */
    AppendUploadLog(__FUNCTION__, "Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]");
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
            AppendUploadLog(__FUNCTION__, "Subject not found by UID. Checking if the subject exists using PatientName [" + PatientName + "] PatientSex [" + PatientSex + "] PatientBirthDate [" + PatientBirthDate + "]");
            QSqlQuery q2;
            q2.prepare(sqlstring2);
            q2.bindValue(":dob", PatientBirthDate);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            if (q2.size() > 0) {
                q2.first();
                subjectUID = q2.value("uid").toString();
                subjectRowID = q2.value("subject_id").toInt();
                AppendUploadLog(__FUNCTION__, "This subject exists. UID [" + subjectUID + "]");
                subjectFoundByName = 1;
            }
        }
        /* if it couldn't be found, create a new subject */
        if (!subjectFoundByName) {
            CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, subjectRowID, subjectUID);
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
            subjectUID = q2.value("uid").toString().toUpper().trimmed();

            AppendUploadLog(__FUNCTION__, QString("Found subject [subjectid %1, UID " + subjectUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID));
        }
        else {
            AppendUploadLog(__FUNCTION__, "Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]");
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
    }

    n->WriteLog("subjectUID [" + subjectUID + "]");
    if (subjectUID == "") {
        AppendUploadLog(__FUNCTION__, "Error finding/creating subject. UID is blank");
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
        AppendUploadLog(__FUNCTION__, QString("This subject is part of a family [%1]").arg(familyRowID));
    }
    else {
        int count = 0;

        /* create family UID */
        AppendUploadLog(__FUNCTION__, "Subject is not part of family, creating a unique family UID");
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
        q2.bindValue(":familyname", "Proband-" + subjectUID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        familyRowID = q2.lastInsertId().toInt();

        q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
        q2.bindValue(":familyid", familyRowID);
        q2.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
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
        AppendUploadLog(__FUNCTION__, QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID));
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
        AppendUploadLog(__FUNCTION__, QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID));
    }
    else {
        /* create enrollmentRowID if it doesn't exist */
        q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
        q2.bindValue(":subjectid", subjectRowID);
        q2.bindValue(":projectid", projectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        enrollmentRowID = q2.lastInsertId().toInt();

        AppendUploadLog(__FUNCTION__, QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID));
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
    AppendUploadLog(__FUNCTION__, QString("Checking if this study exists: enrollmentID [%1] StudyDateTime [%2] Modality [%3] StationName [%4]").arg(enrollmentRowID).arg(StudyDateTime).arg(Modality).arg(StationName));

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
            AppendUploadLog(__FUNCTION__, QString("StudyID [%1] exists, updating").arg(study_id));
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

            break;
        }
    }
    if (!studyFound) {
        AppendUploadLog(__FUNCTION__, "Study did not exist, creating new study");

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
    }

    /* copy the file to the archive, update db info */
    AppendUploadLog(__FUNCTION__, QString("seriesRowID [%1]").arg(seriesRowID));

    /* create data directory if it doesn't already exist */
    QString outdir = QString("%1/%2/%3/%4/parrec").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);
    AppendUploadLog(__FUNCTION__, "Outdir [" + outdir + "]");
    QString m;
    if (!n->MakePath(outdir, m))
        AppendUploadLog(__FUNCTION__, "Error creating outdir ["+outdir+"] because of error ["+m+"]");

    /* move the files into the outdir */
    n->MoveFile(parfile, outdir);
    n->MoveFile(recfile, outdir);

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
    QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);
    n->SystemCommand(systemstring);

    /* copy everything to the backup directory */
    QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);
    QDir bda(backdir);
    if (!bda.exists()) {
        AppendUploadLog(__FUNCTION__, "Directory [" + backdir + "] does not exist. About to create it...");
        QString m;
        if (!n->MakePath(backdir, m))
            AppendUploadLog(__FUNCTION__, "Unable to create backdir [" + backdir + "] because of error [" + m + "]");
        else
            AppendUploadLog(__FUNCTION__, "Finished creating ["+backdir+"]");
    }
    AppendUploadLog(__FUNCTION__, "About to copy to the backup directory");
    systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
    QString output = n->SystemCommand(systemstring);
    AppendUploadLog(__FUNCTION__, output);
    AppendUploadLog(__FUNCTION__, "Finished copying to the backup directory");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertEEG -------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::InsertEEG(int importid, QString file) {

    AppendUploadLog(__FUNCTION__, QString("----- In InsertEEG(%1, %2) -----").arg(importid).arg(file));

    QString familyRealUID;

    int projectRowID(0);
    QString subjectUID;
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
        AppendUploadLog(__FUNCTION__, QString("ImportID [%1] not found. Using default import parameters").arg(importid));
    }

    AppendUploadLog(__FUNCTION__, file);
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

    AppendUploadLog(__FUNCTION__, QString("Before fixing: PatientID [%1], StudyDateTime [%2], SeriesDateTime [%3], SeriesDescription [%4], OperatorsName [%5], SeriesNumber [%6], FileNumber [%7]").arg(PatientID).arg(StudyDateTime).arg(SeriesDateTime).arg(SeriesDescription).arg(OperatorsName).arg(SeriesNumber).arg(FileNumber));

    /* check if anything is still funny */
    if (StudyDateTime == "") StudyDateTime = "0000-00-00 00:00:00";
    if (SeriesDateTime == "") SeriesDateTime = "0000-00-00 00:00:00";
    if (SeriesDescription == "") SeriesDescription = "Unknown";
    if (ProtocolName == "") ProtocolName = "Unknown";
    if (OperatorsName == "") OperatorsName = "Unknown";
    if (SeriesNumber < 1) SeriesNumber = 1;

    AppendUploadLog(__FUNCTION__, QString("Before fixing: PatientID [%1], StudyDateTime [%2], SeriesDateTime [%3], SeriesDescription [%4], OperatorsName [%5], SeriesNumber [%6], FileNumber [%7]").arg(PatientID).arg(StudyDateTime).arg(SeriesDateTime).arg(SeriesDescription).arg(OperatorsName).arg(SeriesNumber).arg(FileNumber));

    AppendUploadLog(__FUNCTION__, PatientID + " - " + StudyDescription);

    /* get the ID search string */
    QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
    QStringList altuidlist;
    if (importAltUIDs != "") {
        altuidlist = importAltUIDs.split(",");
        altuidlist.removeDuplicates();
    }

    // check if the project and subject exist
    AppendUploadLog(__FUNCTION__, "Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]");
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
        subjectUID = q.value("uid").toString().trimmed();
    }
    else {
        /* subject doesn't already exist. Not creating new subjects as part of EEG/ET/etc upload because we have no age, DOB, sex. So note this failure in the import_logs table */
        AppendUploadLog(__FUNCTION__, QString("Subject with ID [%1] does not exist. Subjects must exist prior to EEG/ET import").arg(PatientID));
        return false;
    }

    if (subjectUID == "") {
        AppendUploadLog(__FUNCTION__, "ERROR: UID blank");
        return false;
    }
    else
        AppendUploadLog(__FUNCTION__, "UID found [" + subjectUID + "]");

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
        AppendUploadLog(__FUNCTION__, QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID));
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
        AppendUploadLog(__FUNCTION__, QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID));
    }
    else {
        /* create enrollmentRowID if it doesn't exist */
        q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
        q2.bindValue(":subjectid", subjectRowID);
        q2.bindValue(":projectid", projectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        enrollmentRowID = q2.lastInsertId().toInt();

        AppendUploadLog(__FUNCTION__, QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID));
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
    }

    /* copy the file to the archive, update db info */
    AppendUploadLog(__FUNCTION__, QString("seriesRowID [%1]").arg(seriesRowID));

    /* create data directory if it doesn't already exist */
    QString outdir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber).arg(Modality.toLower());
    AppendUploadLog(__FUNCTION__, "Creating outdir ["+outdir+"]");
    QString m;
    if (!n->MakePath(outdir,m))
        AppendUploadLog(__FUNCTION__, "Unable to create directory ["+outdir+"] because of error ["+m+"]");

    /* move the files into the outdir */
    AppendUploadLog(__FUNCTION__, "Moving ["+file+"] -> ["+outdir+"]");
    if (!n->MoveFile(file, outdir))
        n->WriteLog("Unable to move ["+file+"] to ["+outdir+"]");

    /* get the size of the files and update the DB */
    qint64 dirsize(0);
    int nfiles(0);
    n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);
    q2.prepare(QString("update %1_series set series_size = :dirsize where %1series_id = :seriesRowID").arg(Modality.toLower()));
    q2.bindValue(":dirsize", dirsize);
    q2.bindValue(":seriesRowID", seriesRowID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

    /* change the permissions to 777 so the webpage can read/write the directories */
    QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);
    n->SystemCommand(systemstring);

    /* copy everything to the backup directory */
    QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectUID).arg(studynum).arg(SeriesNumber);
    QDir bda(backdir);
    if (!bda.exists()) {
        AppendUploadLog(__FUNCTION__, "Directory [" + backdir + "] does not exist. About to create it...");
        QString m;
        if (!n->MakePath(backdir, m))
            AppendUploadLog(__FUNCTION__, "Unable to create backdir [" + backdir + "] because of error [" + m + "]");
        else
            AppendUploadLog(__FUNCTION__, "Created backdir [" + backdir + "]");
    }
    AppendUploadLog(__FUNCTION__, "About to copy to the backup directory");
    systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
    QString output = n->SystemCommand(systemstring);
    AppendUploadLog(__FUNCTION__, output);
    AppendUploadLog(__FUNCTION__, "Finished copying to the backup directory");

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
        cc = "999999"; /* generic project */
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
    AppendUploadLog(__FUNCTION__, n->SystemCommand(systemstring));
}


/* ---------------------------------------------------------- */
/* --------- GetFamily -------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::GetFamily(int subjectRowID, QString subjectUID, int &familyRowID, QString &familyUID) {
    AppendUploadLog(__FUNCTION__, "Entering GetFamily()");

    /* check if the subject is part of a family, if not create a family for it */
    QSqlQuery q;

    q.prepare("select family_id from family_members where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        familyRowID = q.value("family_id").toInt();
    }
    else {
        int count = 0;

        /* create family UID */
        AppendUploadLog(__FUNCTION__, "Subject is not part of family, creating a unique family UID");
        do {
            familyUID = n->CreateUID("F");
            QSqlQuery q2;
            q2.prepare("SELECT * FROM `families` WHERE family_uid = :familyuid");
            q2.bindValue(":familyuid", familyUID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            count = q2.size();
        } while (count > 0);

        /* create familyRowID if it doesn't exist */
        QSqlQuery q2;
        q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyuid, now(), :familyname)");
        q2.bindValue(":familyuid", familyUID);
        q2.bindValue(":familyname", "Proband-" + subjectUID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        familyRowID = q2.lastInsertId().toInt();

        q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
        q2.bindValue(":familyid", familyRowID);
        q2.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

    }

    AppendUploadLog(__FUNCTION__, "Leaving GetFamily()");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetProject ------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::GetProject(int destProjectID, QString StudyDescription, int &projectRowID) {
    QSqlQuery q;


    /* get the projectRowID */
    if (destProjectID >= 0) {
        AppendUploadLog(__FUNCTION__, QString("Destination project [%1] specified").arg(destProjectID));
        projectRowID = destProjectID;
    }
    else {
        /* get the costcenter */
        QString costcenter = GetCostCenter(StudyDescription);

        q.prepare("select project_id from projects where project_costcenter = :costcenter");
        q.bindValue(":costcenter", costcenter);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            projectRowID = q.value("project_id").toInt();
            AppendUploadLog(__FUNCTION__, QString("Found project [" + costcenter + "] with id [%1]").arg(projectRowID));
        }
        else {
            AppendUploadLog(__FUNCTION__, QString("Project with cost center [" + costcenter + "] not found, using Generic Project instead"));
            q.prepare("select project_id from projects where project_costcenter = '999999'");
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                projectRowID = q.value("project_id").toInt();
            }
            else {
                AppendUploadLog(__FUNCTION__, QString("Project with cost center [999999] not found. That's trouble. Generic project does not exist."));
            }
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetEnrollment ---------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::GetEnrollment(int subjectRowID, int projectRowID, int &enrollmentRowID) {
    QSqlQuery q;

    /* check if the subject is enrolled in the project */
    q.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
    q.bindValue(":subjectid", subjectRowID);
    q.bindValue(":projectid", projectRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        enrollmentRowID = q.value("enrollment_id").toInt();
        AppendUploadLog(__FUNCTION__, QString("Subject is enrolled in this project [%1], with enrollmentRowID [%2]").arg(projectRowID).arg(enrollmentRowID));
    }
    else {
        /* create enrollmentRowID if it doesn't exist */
        q.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
        q.bindValue(":subjectid", subjectRowID);
        q.bindValue(":projectid", projectRowID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        enrollmentRowID = q.lastInsertId().toInt();

        AppendUploadLog(__FUNCTION__, QString("Created new enrollmentRowID [%1]").arg(enrollmentRowID));
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- SetAlternateIDs -------------------------------- */
/* ---------------------------------------------------------- */
void archiveIO::SetAlternateIDs(int subjectRowID, int enrollmentRowID, QStringList altuidlist) {
    /* update alternate IDs, if there are any */
    if (altuidlist.size() > 0) {
        foreach (QString altuid, altuidlist) {
            if (altuid.trimmed() != "") {
                QSqlQuery q;
                q.prepare("replace into subject_altuid (subject_id, altuid, enrollment_id) values (:subjectid, :altuid, :enrollmentid)");
                q.bindValue(":subjectid", subjectRowID);
                q.bindValue(":altuid", altuid);
                q.bindValue(":enrollmentid", enrollmentRowID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
            }
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- GetSubject ------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::GetSubject(QString subjectMatchCriteria, int existingSubjectID, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, int &subjectRowID, QString &subjectUID) {
    subjectMatchCriteria = subjectMatchCriteria.toLower();

    subject *s = NULL;

    if (existingSubjectID >= 0) {
        s = new subject(existingSubjectID, n);
    }
    else {
        if ( (subjectMatchCriteria == "") || (subjectMatchCriteria == "patientid") || (subjectMatchCriteria == "specificpatientid") || (subjectMatchCriteria == "patientidfromdir")) {
            s = new subject(PatientID, n);
        }
        else if (subjectMatchCriteria == "namesexdob") {
            s = new subject(PatientName, PatientSex, PatientBirthDate, n);
        }
        else {
            subjectRowID = -1;
            subjectUID = "";
            AppendUploadLog(__FUNCTION__, "Invalid subjectMatchCriteria [" + subjectMatchCriteria + "]");
            return false;
        }
    }

    //if (s->valid()) {
    //    subjectRowID = s->subjectRowID();
    //    subjectUID = s->UID();
    //    return true;
    //}
    //else {
    //    subjectRowID = -1;
    //    subjectUID = "";
    //    AppendUploadLog(__FUNCTION__, "Subject found, but not valid");
    //    return false;
    //}

    if (s) {
        if (s->valid()) {
            subjectRowID = s->subjectRowID();
            subjectUID = s->UID();
            AppendUploadLog(__FUNCTION__, QString("Subject [%1] with subjectRowID [%2] found by criteria [%3]").arg(s->UID()).arg(s->subjectRowID()).arg(subjectMatchCriteria));
            return true;
        }
        else {
            subjectRowID = -1;
            subjectUID = "";
            AppendUploadLog(__FUNCTION__, QString("Subject [%1] with subjectRowID [%2] found by criteria [%3], but is not valid").arg(s->UID()).arg(s->subjectRowID()).arg(subjectMatchCriteria));
            return false;
        }
    }
    else {
        subjectRowID = -1;
        AppendUploadLog(__FUNCTION__, "Study not found. Study object is still NULL");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- CreateSubject ---------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, int &subjectRowID, QString &subjectUID) {

    subjectRowID = -1;

    int count(0);
    //AppendUploadLog(__FUNCTION__, "Creating a new subject. Searching for an unused UID");
    /* create a new subjectUID */
    do {
        subjectUID = n->CreateUID("S",3);
        QSqlQuery q2;
        q2.prepare("select uid from subjects where uid = :uid");
        q2.bindValue(":uid", subjectUID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        count = q2.size();
    } while (count > 0);

    //AppendUploadLog(__FUNCTION__, "Found an unused UID [" + subjectUID + "]");

    QString sqlstring = "insert into subjects (name, birthdate, gender, weight, height, uid, uuid) values (:patientname, :patientdob, :patientsex, :weight, :size, :uid, ucase(md5(concat('" + n->RemoveNonAlphaNumericChars(PatientName) + "', '" + n->RemoveNonAlphaNumericChars(PatientBirthDate) + "','" + n->RemoveNonAlphaNumericChars(PatientSex) + "'))) )";
    QSqlQuery q2;
    q2.prepare(sqlstring);
    q2.bindValue(":patientname",PatientName);
    q2.bindValue(":patientdob",PatientBirthDate);
    q2.bindValue(":patientsex",PatientSex);
    q2.bindValue(":weight",PatientWeight);
    q2.bindValue(":size",PatientSize);
    q2.bindValue(":uid",subjectUID);
    n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    subjectRowID = q2.lastInsertId().toInt();

    /* insert the PatientID as an alternate UID */
    if (PatientID != "") {
        QSqlQuery q2;
        q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
        q2.bindValue(":subjectid",subjectRowID);
        q2.bindValue(":patientid",PatientID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        AppendUploadLog(__FUNCTION__, "Added alternate UID [" + PatientID + "]");
    }

    if (subjectRowID >= 0) {
        AppendUploadLog(__FUNCTION__, "Created new subject [" + subjectUID + "] with subjectRowID [" + subjectRowID + "]");
        return true;
    }
    else {
        AppendUploadLog(__FUNCTION__, "Error creating new subject [" + subjectUID + "] with subjectRowID [" + subjectRowID + "]");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetStudy --------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::GetStudy(QString studyMatchCriteria, int existingStudyID, int enrollmentRowID, QString StudyDateTime, QString Modality, QString StudyInstanceUID, int &studyRowID) {

    studyMatchCriteria = studyMatchCriteria.toLower();

    study *s = NULL;

    if (existingStudyID >= 0)
        s = new study(existingStudyID, n);
    else {
        if (studyMatchCriteria == "modalitystudydate") {
            if (StudyDateTime != "")
                s = new study(enrollmentRowID, StudyDateTime, Modality, n);
            else
                s = new study(StudyInstanceUID, n);
        }
        else if (studyMatchCriteria == "studyuid")
            s = new study(StudyInstanceUID, n);
        else {
            studyRowID = -1;
            AppendUploadLog(__FUNCTION__, "Study not found. Invalid match criteria [" + studyMatchCriteria + "]");
            return false;
        }
    }

    if (s) {
        if (s->valid()) {
            studyRowID = s->studyRowID();
            AppendUploadLog(__FUNCTION__, QString("Study [%1%2] with studyRowID [%3] found by criteria [%4]").arg(s->UID()).arg(s->studyNum()).arg(s->studyRowID()).arg(studyMatchCriteria));
            return true;
        }
        else {
            studyRowID = -1;
            AppendUploadLog(__FUNCTION__, QString("Study [%1%2] with studyRowID [%3] found by criteria [%4], but is not valid").arg(s->UID()).arg(s->studyNum()).arg(s->studyRowID()).arg(studyMatchCriteria));
            return false;
        }
    }
    else {
        studyRowID = -1;
        AppendUploadLog(__FUNCTION__, "Study not found. Study object is still NULL");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- CreateStudy ------------------------------------ */
/* ---------------------------------------------------------- */
bool archiveIO::CreateStudy(int subjectRowID, int enrollmentRowID, QString StudyDateTime, QString studyUID, QString Modality, QString PatientID, double PatientAge, double PatientSize, double PatientWeight, QString StudyDescription, QString OperatorsName, QString PerformingPhysiciansName, QString StationName, QString InstitutionName, QString InstitutionAddress, int &studyRowID, int &studyNum) {

    QSqlQuery q;

    q.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        studyNum = q.value("study_num").toInt() + 1;
    }
    else
        studyNum = 1;

    q.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_desc, study_operator, study_performingphysician, study_site, study_nidbsite, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentid, :studynum, :patientid, :modality, '" + StudyDateTime + "', :patientage, :height, :weight, :studydesc, :operator, :physician, :stationname, :importsiteid, :institution, 'complete', 'import', now()) on duplicate key update enrollment_id = :enrollmentid, study_num = :studynum, study_alternateid = :patientid, study_modality = :modality, study_datetime = '" + StudyDateTime + "', study_ageatscan = :patientage, study_height = :height, study_weight = :weight, study_desc = :studydesc, study_operator = :operator, study_performingphysician = :physician, study_site = :stationname, study_institution = :institution");
    q.bindValue(":enrollmentid", enrollmentRowID);
    q.bindValue(":studynum", studyNum);
    q.bindValue(":patientid", PatientID);
    q.bindValue(":modality", Modality);
    q.bindValue(":patientage", PatientAge);
    q.bindValue(":height", PatientSize);
    q.bindValue(":weight", PatientWeight);
    q.bindValue(":studydesc", StudyDescription);
    q.bindValue(":operator", OperatorsName);
    q.bindValue(":physician", PerformingPhysiciansName);
    q.bindValue(":stationname", StationName);
    q.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
    q.bindValue(":studyid", studyRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    studyRowID = q.lastInsertId().toInt();

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AppendUploadLog -------------------------------- */
/* ---------------------------------------------------------- */
void archiveIO::AppendUploadLog(QString func, QString m) {
    if ((uploadid >= 0) && (m.trimmed() != "")) {
        QString str = func + "() " + m;

        QSqlQuery q;
        q.prepare("insert ignore into upload_logs (upload_id, log_date, log_msg) values (:uploadid, now(), :msg)");
        q.bindValue(":uploadid", uploadid);
        q.bindValue(":msg", str);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        n->WriteLog(str);
    }
}


/* ---------------------------------------------------------- */
/* --------- WriteBIDS ------------------------------------- */
/* ---------------------------------------------------------- */
bool archiveIO::WriteBIDS(QList<int> seriesids, QStringList modalities, QString odir, QString bidsreadme, QString &msg) {
    n->WriteLog("Entering WriteBIDS()...");

    QString exportstatus = "complete";
    QString bidsver = "1.4.1";
    subjectStudySeriesContainer s;

    QStringList msgs;
    if (!GetSeriesListDetails(seriesids, modalities, s)) {
        msg = "Unable to get a series list";
        return false;
    }

    /* create the output directory */
    QString outdir = odir;
    QString m;
    if (n->MakePath(outdir, m)) {
        n->WriteLog("Created outdir [" + outdir + "]");
    }
    else {
        exportstatus = "error";
        msg = n->WriteLog("ERROR [" + m + "] unable to create outdir [" + outdir + "]");
        return false;
    }

    /* create dataset description */
    QJsonObject root;
    root["BIDSVersion"] = bidsver;
    root["License"] = "This dataset was written by the Neuroinformatics Database. Licensing should be included with this dataset";
    root["Date"] = n->CreateLogDate();
    root["README"] = bidsreadme;
    QByteArray j = QJsonDocument(root).toJson();
    QFile fout("dataset_description.json");
    fout.open(QIODevice::WriteOnly);
    fout.write(j);

    /* write the participants.csv file */
    QString pfile = outdir + "/participants.tsv";
    if (!n->WriteTextFile(pfile, "participant_id\tAge\tGender\n", false)) n->WriteLog("Error writing " + pfile);

    int i = 1; /* the subject counter */
    /* iterate through the UIDs */
    for(QMap<QString, QMap<int, QMap<int, QMap<QString, QString>>>>::iterator a = s.begin(); a != s.end(); ++a) {
        QString uid = a.key();
        int j = 1; /* the session (study) counter */

        n->WriteLog("Working on [" + uid + "]");
        QString subjectSex = s[uid][0][0]["subjectsex"];
        double subjectAge = s[uid][0][0]["subjectage"].toDouble();

        /* write subject to participants file */
        if (!n->WriteTextFile(pfile, QString("sub-%1\t%2\t%3\n").arg(i, 3, 10, QChar('0')).arg(subjectAge).arg(subjectSex), true)) n->WriteLog("Error writing " + pfile);

        /* iterate through the studynums */
        for(QMap<int, QMap<int, QMap<QString, QString>>>::iterator b = s[uid].begin(); b != s[uid].end(); ++b) {
            int studynum = b.key();

            if (studynum == 0)
                continue;

            n->WriteLog(QString("Working on [" + uid + "] and study [%1]").arg(studynum));

            /* iterate through the seriesnums */
            for(QMap<int, QMap<QString, QString>>::iterator c = s[uid][studynum].begin(); c != s[uid][studynum].end(); ++c) {
                int seriesnum = c.key();

                /* skip the series that contained only a placeholder for the subject/study info */
                if (seriesnum == 0)
                    continue;

                n->WriteLog(QString("Working on [" + uid + "] and study [%1] and series [%2]").arg(studynum).arg(seriesnum));

                //int exportseriesid = s[uid][studynum][seriesnum]["exportseriesid"].toInt();
                //SetExportSeriesStatus(exportseriesid, "processing");

                QString seriesstatus = "complete";
                QString statusmessage;

                int seriesid = s[uid][studynum][seriesnum]["seriesid"].toInt();
                //int subjectid = s[uid][studynum][seriesnum]["subjectid"].toInt();
                QString primaryaltuid = s[uid][studynum][seriesnum]["primaryaltuid"];
                QString altuids = s[uid][studynum][seriesnum]["altuids"];
                QString projectname = s[uid][studynum][seriesnum]["projectname"];
                //int studyid = s[uid][studynum][seriesnum]["studyid"].toInt();
                QString studytype = s[uid][studynum][seriesnum]["studytype"];
                QString studyaltid = s[uid][studynum][seriesnum]["studyaltid"];
                QString modality = s[uid][studynum][seriesnum]["modality"];
                //double seriessize = s[uid][studynum][seriesnum]["seriessize"].toDouble();
                QString seriesdesc = s[uid][studynum][seriesnum]["seriesdesc"];
                QString seriesaltdesc = s[uid][studynum][seriesnum]["seriesaltdesc"].trimmed();
                QString datatype = s[uid][studynum][seriesnum]["datatype"];
                QString datadir = s[uid][studynum][seriesnum]["datadir"];
                QString behindir = s[uid][studynum][seriesnum]["behdir"];
                QString qcindir = s[uid][studynum][seriesnum]["qcdir"];
                bool datadirexists = s[uid][studynum][seriesnum]["datadirexists"].toInt();
                bool behdirexists = s[uid][studynum][seriesnum]["behdirexists"].toInt();
                //bool qcdirexists = s[uid][studynum][seriesnum]["qcdirexists"].toInt();
                bool datadirempty = s[uid][studynum][seriesnum]["datadirempty"].toInt();
                //bool behdirempty = s[uid][studynum][seriesnum]["behdirempty"].toInt();
                //bool qcdirempty = s[uid][studynum][seriesnum]["qcdirempty"].toInt();

                /* create the subject identifier */
                QString subjectdir = QString("sub-%1").arg(i, 4, 10, QChar('0'));

                /* create the session (study) identifier */
                QString sessiondir = QString("ses-%1").arg(j, 4, 10, QChar('0'));

                /* determine the datatype (what BIDS calls the 'modality') */
                QString seriesdir;
                if (seriesaltdesc == "") {
                    seriesdir = seriesdesc;
                }
                else {
                    seriesdir = seriesaltdesc;
                }
                /* remove any non-alphanumeric characters */
                seriesdir.replace(QRegularExpression("[^a-zA-Z0-9_-]"),"_");

                QString seriesoutdir = QString("%1/%2/%3/%4").arg(outdir).arg(subjectdir).arg(sessiondir).arg(seriesdir);

                QString m;
                if (n->MakePath(seriesoutdir, m)) {
                    n->WriteLog("Created seriesoutdir [" + seriesoutdir + "]");
                }
                else {
                    exportstatus = "error";
                    n->WriteLog("ERROR [" + m + "] unable to create seriesoutdir [" + seriesoutdir + "]");
                    msg = "Unable to create output directory [" + seriesoutdir + "]";
                    return false;
                }

                if (datadirexists) {
                    if (!datadirempty) {
                        QString tmpdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
                        QString m;
                        if (n->MakePath(tmpdir, m)) {

                            int numfilesconv(0), numfilesrenamed(0);
                            if (!n->ConvertDicom("bids", datadir, tmpdir, 1, subjectdir, sessiondir, seriesdir, datatype, numfilesconv, numfilesrenamed, m))
                                msgs << "Error converting files [" + m + "]";

                            n->WriteLog("About to copy files from " + tmpdir + " to " + seriesoutdir);
                            QString systemstring = "rsync " + tmpdir + "/* " + seriesoutdir + "/";
                            n->WriteLog(n->SystemCommand(systemstring));
                            n->WriteLog("Done copying files...");
                            n->RemoveDir(tmpdir,m);
                        }
                        else {
                            n->WriteLog("Unable to create directory");
                        }
                    }
                    else {
                        seriesstatus = "error";
                        exportstatus = "error";
                        n->WriteLog("ERROR [" + datadir + "] is empty");
                        msgs << "Directory [" + datadir + "] is empty";
                    }
                }
                else {
                    seriesstatus = "error";
                    exportstatus = "error";
                    n->WriteLog("ERROR datadir [" + datadir + "] does not exist");
                    msgs << "Directory [" + datadir + "] does not exist";
                }

                /* copy the beh data */
                if (behdirexists) {
                    QString systemstring;
                    systemstring = "cp -R " + behindir + "/* " + seriesoutdir;
                    n->WriteLog(n->SystemCommand(systemstring, true));
                    systemstring = "chmod -Rf 777 " + seriesoutdir;
                    n->WriteLog(n->SystemCommand(systemstring, true));
                }

                n->WriteLog(QString("Checkpoint A [%1, %2, %3]").arg(seriesid).arg(seriesstatus).arg(statusmessage));

                n->SetExportSeriesStatus(seriesid,seriesstatus,statusmessage);
                msgs << QString("Series [%1%2-%3 (%4)] complete").arg(uid).arg(studynum).arg(seriesnum).arg(seriesdesc);
                //SetExportSeriesStatus(exportseriesid, seriesstatus);

                QSqlQuery q2;
                q2.prepare("update exportseries set status = :status where series_id = :id and modality = :modality");
                q2.bindValue(":id", seriesid);
                q2.bindValue(":status", seriesstatus);
                q2.bindValue(":modality", modality);
                n->WriteLog(n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__));

            }
            j++;
        }
        i++;
    }

    /* write the readme file */
    if (!n->WriteTextFile(outdir + "/README", bidsreadme, false)) n->WriteLog("Error writing README file [" + outdir + "/README]");

    msg = msgs.join("\n");
    n->WriteLog("Leaving WriteBIDS()...");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetSeriesListDetails --------------------------- */
/* ---------------------------------------------------------- */
/* create a multilevel hash s[uid][study][series]['attribute'] to store the series */
bool archiveIO::GetSeriesListDetails(QList <int> seriesids, QStringList modalities, subjectStudySeriesContainer &s) {

    QSqlQuery q;
    for (int i=0; i<seriesids.size(); i++) {
        int seriesid = seriesids[i];
        QString modality = modalities[i];

        q.prepare(QString("select a.*, b.*, c.enrollment_id, d.project_name, d.project_id, e.uid, e.gender, e.birthdate, e.subject_id from %1_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.%1series_id = :seriesid order by uid, study_num, series_num").arg(modality));
        q.bindValue(":seriesid",seriesid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        if (q.size() > 0) {
            n->WriteLog(QString("%1() Found [%2] rows").arg(__FUNCTION__).arg(q.size()));
            while (q.next()) {
                QString uid = q.value("uid").toString();
                int subjectid = q.value("subject_id").toInt();
                QString subjectsex = q.value("gender").toString();
                QString subjectdob = q.value("birthdate").toString();
                int studynum = q.value("study_num").toInt();
                int studyid = q.value("study_id").toInt();
                QString studydatetime = q.value("study_datetime").toDateTime().toString("yyyyMMdd_HHmmss");
                QString studydate = q.value("study_datetime").toString();
                int seriesnum = q.value("series_num").toInt();
                int seriessize = q.value("series_size").toInt();
                QString seriesnotes = q.value("series_notes").toString();
                QString seriesdesc = q.value("series_desc").toString();
                QString seriesaltdesc = q.value("series_altdesc").toString();
                QString projectname = q.value("project_name").toString();
                int projectid = q.value("project_id").toInt();
                QString studyaltid = q.value("study_alternateid").toString();
                QString studytype = q.value("study_type").toString();
                QString datatype = q.value("data_type").toString();
                if (datatype == "") /* If the modality is MR, the datatype will have a value (dicom, nifti, parrec), otherwise we will set the datatype to the modality */
                    datatype = modality;
                int numfiles = q.value("numfiles").toInt();
                if (modality != "mr")
                    numfiles = q.value("series_numfiles").toInt();
                int numfilesbeh = q.value("numfiles_beh").toInt();
                int enrollmentid = q.value("enrollment_id").toInt();

                double subjectAge = n->GetPatientAge("", studydate, subjectdob);

                QSqlQuery q2;
                q2.prepare("select * from bids_mapping where project_id = :projectid and protocolname = :protocol and modality = :modality");
                q2.bindValue(":projectid", projectid);
                q2.bindValue(":protocol", seriesdesc);
                q2.bindValue(":modality", modality);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

                QString bidsname = "";
                if (q2.size() > 0) {
                    q2.first();
                    bidsname = q.value("shortname").toString();
                }

                QString datadir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum).arg(datatype);
                QString behdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);
                QString qcdir = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);

                //s[uid][studynum][seriesnum]["exportseriesid"] = QString("%1").arg(exportseriesid);
                s[uid][studynum][seriesnum]["seriesid"] = QString("%1").arg(seriesid);
                s[uid][studynum][seriesnum]["subjectid"] = QString("%1").arg(subjectid);
                s[uid][studynum][seriesnum]["studyid"] = QString("%1").arg(studyid);
                s[uid][studynum][seriesnum]["projectid"] = QString("%1").arg(projectid);
                s[uid][studynum][seriesnum]["subjectsex"] = subjectsex;
                s[uid][studynum][seriesnum]["subjectage"] = subjectAge;
                s[uid][studynum][seriesnum]["studydatetime"] = studydatetime;
                s[uid][studynum][seriesnum]["modality"] = modality;
                s[uid][studynum][seriesnum]["seriessize"] = QString("%1").arg(seriessize);
                s[uid][studynum][seriesnum]["seriesnotes"] = seriesnotes;
                s[uid][studynum][seriesnum]["seriesdesc"] = seriesdesc;
                s[uid][studynum][seriesnum]["seriesaltdesc"] = seriesaltdesc;
                s[uid][studynum][seriesnum]["bidsname"] = bidsname;
                s[uid][studynum][seriesnum]["numfilesbeh"] = QString("%1").arg(numfilesbeh);
                s[uid][studynum][seriesnum]["numfiles"] = QString("%1").arg(numfiles);
                s[uid][studynum][seriesnum]["projectname"] = projectname;
                s[uid][studynum][seriesnum]["studyaltid"] = studyaltid;
                s[uid][studynum][seriesnum]["studytype"] = studytype;
                s[uid][studynum][seriesnum]["datatype"] = datatype;
                s[uid][studynum][seriesnum]["datadir"] = datadir;
                s[uid][studynum][seriesnum]["behdir"] = behdir;
                s[uid][studynum][seriesnum]["qcdir"] = qcdir;

                /* Check if source data directories exist */
                if (QDir(datadir).exists()) {
                    s[uid][studynum][seriesnum]["datadirexists"] = "1";

                    if (QDir(datadir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0)
                        s[uid][studynum][seriesnum]["datadirempty"] = "1";
                    else
                        s[uid][studynum][seriesnum]["datadirempty"] = "0";
                }
                else
                    s[uid][studynum][seriesnum]["datadirexists"] = "0";

                if (QDir(behdir).exists()) {
                    s[uid][studynum][seriesnum]["behdirexists"] = "1";

                    if (QDir(behdir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0)
                        s[uid][studynum][seriesnum]["behdirempty"] = "1";
                    else
                        s[uid][studynum][seriesnum]["behdirempty"] = "0";
                }
                else
                    s[uid][studynum][seriesnum]["behdirexists"] = "0";

                if (QDir(qcdir).exists()) {
                    s[uid][studynum][seriesnum]["qcdirexists"] = "1";

                    if (QDir(qcdir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0)
                        s[uid][studynum][seriesnum]["qcdirempty"] = "1";
                    else
                        s[uid][studynum][seriesnum]["qcdirempty"] = "0";
                }
                else
                    s[uid][studynum][seriesnum]["qcdirexists"] = "0";

                /* get any alternate IDs */
                QStringList altuids;
                QString primaryaltuid;

                //QSqlQuery q2;
                q2.prepare("select altuid, isprimary from subject_altuid where enrollment_id = :enrollmentid and subject_id = :subjectid");
                q2.bindValue(":enrollmentid",enrollmentid);
                q2.bindValue(":subjectid",subjectid);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
                if (q2.size() > 0) {
                    while (q2.next()) {
                        altuids << q2.value("altuid").toString();
                        if (q2.value("isprimary").toBool())
                            primaryaltuid = q2.value("altuid").toString();
                    }
                    s[uid][studynum][seriesnum]["primaryaltuid"] = primaryaltuid;
                    s[uid][studynum][seriesnum]["altuids"] = altuids.join(",");
                }
            }
        }
        else {
            n->WriteLog(QString("No rows found for this seriesid [%1] and modality [%2]").arg(seriesid).arg(modality));
        }
    }
    return true;
}