/* ------------------------------------------------------------------------------
  NIDB moduleUpload.cpp
  Copyright (C) 2004 - 2024
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

#include "moduleUpload.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- moduleUpload ----------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::moduleUpload(nidb *a)
{
    n = a;
    io = new archiveIO(n);
}


/* ---------------------------------------------------------- */
/* --------- ~moduleUpload ---------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::~moduleUpload()
{
    delete io;
}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run the module. Parse any uploads, and archive any uploads that the user has requested to be archived
 * @return The number of operations completed, 0 otherwise
 */
int moduleUpload::Run() {
    n->Log("Entering the upload module");

    bool ret(false);

    /* parse any uploads */
    ret |= ReadUploads();

    /* archive any uploads */
    ret |= ArchiveSelectedFiles();

    /* archive any squirrel */
    ret |= ArchiveSelectedSquirrel();

    n->Log("Leaving the upload module");
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- ReadUploads ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Begin exploring the files received from the Data --> Import Data webpage. This function will also unzip any compressed files.
 * After unzipping, it will call `ParseUploadedFiles()` in chunks of 5000 so the files can further parsed into subject/study/series which
 * will be displayed to the user through the website. The user can then choose which of the subjects/studies/series to archive.
 * @return true if uploads were parsed
 */
bool moduleUpload::ReadUploads() {
    QSqlQuery q;
    bool ret(false);

    /* get list of uploads that are marked as uploadcomplete, with the upload details */
    q.prepare("select * from uploads where upload_status = 'uploadcomplete'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

            ret = 1;
            int uploadRowID = q.value("upload_id").toInt();
            io->SetUploadID(uploadRowID);

            QString upload_source = q.value("upload_source").toString();
            QString upload_datapath = q.value("upload_datapath").toString();
            //int upload_destprojectid = q.value("upload_destprojectid").toInt();
            QString upload_patientid = q.value("upload_patientid").toString();
            QString upload_modality = q.value("upload_modality").toString();
            QString upload_type = q.value("upload_type").toString();
            QString upload_subjectcriteria = q.value("upload_subjectcriteria").toString();
            QString upload_studycriteria = q.value("upload_studycriteria").toString();
            QString upload_seriescriteria = q.value("upload_seriescriteria").toString();

            /* update the status */
            SetUploadStatus(uploadRowID, "parsing", 0.0);

            /* create the path for the upload data */
            QString uploadstagingpath = QString("%1/%2").arg(n->cfg["uploadstagingdir"]).arg(uploadRowID);

            /* create temporary directory in uploadstagingdir */
            QString m;
            if (!MakePath(uploadstagingpath, m)) {
                io->AppendUploadLog(__FUNCTION__, "Error creating directory [" + uploadstagingpath + "]  with message [" + m + "]");
                ret = 1;

                /* update the status */
                SetUploadStatus(uploadRowID, "parsingerror");

                continue;
            }
            /* update the upload_stagingpath */
            QSqlQuery q2;
            q2.prepare("update uploads set upload_stagingpath = :stagingpath where upload_id = :uploadid");
            q2.bindValue(":stagingpath", uploadstagingpath);
            q2.bindValue(":uploadid", uploadRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

            /* check if the upload path is valid */
            if ((upload_datapath == "") || (upload_datapath == "/") || (upload_datapath == "/etc") || (upload_datapath == "/bin") || (upload_datapath == "/root")) {
                io->AppendUploadLog(__FUNCTION__, QString("upload_datapath is invalid [%1] ").arg(upload_datapath));

                /* update the status */
                SetUploadStatus(uploadRowID, "parsingerror");

                continue;
            }

            /* if modality is blank */
            if (upload_modality == "") {
                io->AppendUploadLog(__FUNCTION__, "Error. Modality was blank [" + upload_modality + "]");
                ret = 1;

                /* update the status */
                SetUploadStatus(uploadRowID, "parsingerror");

                continue;
            }

            /* copy in files from uploadtmp or nfs to the uploadstagingdir */
            io->AppendUploadLog(__FUNCTION__, QString("Beginning copy of data from original path [%1] to upload staging path [%2]").arg(upload_datapath).arg(uploadstagingpath));
            QString systemstring = QString("rsync -a %1/ %2/").arg(upload_datapath).arg(uploadstagingpath);
            io->AppendUploadLog(__FUNCTION__, SystemCommand(systemstring, true, true));

            /* remove the uploadtmp directory, if it was uploaded from the web */
            if (upload_source == "web") {
                QString m;
                if (RemoveDir(upload_datapath, m)) {
                    io->AppendUploadLog(__FUNCTION__, "Removed upload_tmp directory [" + upload_datapath + "]");
                }
                else {
                    io->AppendUploadLog(__FUNCTION__, "Error: Unable to remove upload_tmp directory [" + upload_datapath + "]");
                }
            }

            /* unzip any files in the uploadstagingdir */
            io->AppendUploadLog(__FUNCTION__, "Unzipping files located in [" + uploadstagingpath + "]");
            QString unzipOutput = UnzipDirectory(uploadstagingpath, true);
            io->AppendUploadLog(__FUNCTION__, "Unzip output" + unzipOutput);

            /* get information about the uploaded data from the uploadstagingdir (after unzipping any zip files) */
            qint64 c = 0;
            qint64 b = 0;
            GetDirSizeAndFileCount(uploadstagingpath, c, b, true);
            io->AppendUploadLog(__FUNCTION__, QString("After unzipping, upload directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(uploadstagingpath).arg(c).arg(b));

            n->Log("upload_type is [" + upload_type + "]");
            /* handle the files differently if it's a squirrel upload */
            if (upload_type == "squirrel") {
                QStringList files = FindAllFiles(uploadstagingpath, "*", true);
                foreach (QString f, files) {
                    squirrel *sqrl = new squirrel();
                    sqrl->SetPackagePath(f);
                    if (sqrl->Read()) {
                        ParseUploadedSquirrel(sqrl, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, uploadstagingpath, uploadRowID);
                        n->Log("Successfully read squirrel file [" + f + "]");
                        n->Log(sqrl->GetLog());
                    }
                    else {
                        /* unable to read squirrel file */
                        n->Log("Error reading squirrel file [" + f + "]");
                        n->Log(sqrl->GetLog());
                    }

                    delete sqrl;
                }
            }
            else {

                /* get list of all files, and iterate through all of the files */
                int i = 0;
                int tfiles = 0;
                int validFiles(0);
                int nonMatchFiles(0);
                int unreadableFiles(0);
                /* create a multilevel hash [subject][study][series][files] */
                QMap<QString, QMap<QString, QMap<QString, QStringList> > > fs;
                QStringList files = FindAllFiles(uploadstagingpath, "*", true);
                foreach (QString f, files) {
                    QString subject, study, series;

                    /* get the file info */
                    QHash<QString, QString> tags;
                    QString m;
                    bool csa = false;
                    if (n->cfg["enablecsa"] == "1") csa = true;
                    QString binpath = n->cfg["nidbdir"] + "/bin";
                    if (img->GetImageFileTags(f, binpath, csa, tags, m)) {
                        if ((tags["Modality"].toLower() == upload_modality.toLower()) || (upload_modality.toLower() == "auto")) {

                            /* subject matching criteria */
                            if (upload_subjectcriteria == "patientid")
                                subject = tags["PatientID"];
                            else if (upload_subjectcriteria == "specificpatientid")
                                subject = upload_patientid;
                            else if (upload_subjectcriteria == "patientidfromdir")
                                subject = tags["ParentDirectory"];
                            else if (upload_subjectcriteria == "namesexdob")
                                subject = tags["PatientName"] + "|" + tags["PatientSex"] + "|" + tags["PatientBirthDate"];
                            else
                                io->AppendUploadLog(__FUNCTION__, "Unspecified subject criteria [" + upload_subjectcriteria + "]");

                            /* study matching criteria */
                            if (upload_studycriteria == "modalitystudydate")
                                study = tags["Modality"] + "|" + tags["StudyDateTime"];
                            else if (upload_studycriteria == "studyuid")
                                study = tags["StudyInstanceUID"];
                            else
                                io->AppendUploadLog(__FUNCTION__, "Unspecified study criteria [" + upload_studycriteria + "]");

                            /* series matching criteria */
                            if (upload_seriescriteria == "seriesnum")
                                series = tags["SeriesNumber"];
                            else if (upload_seriescriteria == "seriesdate")
                                series = tags["SeriesDateTime"];
                            else if (upload_seriescriteria == "seriesuid")
                                series = tags["SeriesInstanceUID"];
                            else
                                io->AppendUploadLog(__FUNCTION__, "Unspecified series criteria [" + upload_seriescriteria + "]");

                            /* store the file in the appropriate group */
                            fs[subject][study][series].append(f);
                            validFiles++;
                        }
                        else {
                            //io->AppendUploadLog(__FUNCTION__, "Valid file [" + f + "] but not the modality [" + upload_modality + "] we're looking for [" + tags["Modality"] + "]");
                            fs["nonmatch"]["nonmatch"]["nonmatch"].append(f);
                            nonMatchFiles++;
                        }
                    }
                    else {
                        /* the file is not readable */
                        fs["NiDBunreadable"]["NiDBunreadable"]["0"].append(f);
                        unreadableFiles++;
                        //io->AppendUploadLog(__FUNCTION__, "Unable to read file [" + f + "]");
                    }

                    i++;
                    tfiles++;

                    if (i >= 5000) {
                        double pct = (static_cast<double>(tfiles)/static_cast<double>(c)) * 100.0;
                        SetUploadStatus(uploadRowID, "parsing", pct);

                        /* check if this module should be running */
                        n->ModuleRunningCheckIn();
                        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

                        /* check if the upload status has changed */
                        QString status = GetUploadStatus(uploadRowID);
                        if (status != "parsing") {
                            return ret;
                        }

                        /* after 5000 files, put the found information into the database, then clear the fs list */
                        io->AppendUploadLog(__FUNCTION__, QString("Found [%1] total files: [%2] valid, [%3] nonmatch, [%4] unreadable").arg(tfiles).arg(validFiles).arg(nonMatchFiles).arg(unreadableFiles));
                        io->AppendUploadLog(__FUNCTION__, QString("fs.size() [%1] before being sent into UpdateParsedUploads()").arg(fs.size()));

                        ParseUploadedFiles(fs, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, uploadstagingpath, uploadRowID);
                        fs.clear();
                        i = 0;
                    }
                }

                double pct = (static_cast<double>(tfiles)/static_cast<double>(c)) * 100.0;
                SetUploadStatus(uploadRowID, "parsing", pct);

                /* check if this module should be running */
                n->ModuleRunningCheckIn();
                if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

                /* check if the upload status has changed */
                QString status = GetUploadStatus(uploadRowID);
                if (status != "parsing") {
                    return ret;
                }

                /* after 5000 files, put the found information into the database, then clear the fs list */
                io->AppendUploadLog(__FUNCTION__, QString("Found [%1] total files: [%2] valid, [%3] nonmatch, [%4] unreadable").arg(tfiles).arg(validFiles).arg(nonMatchFiles).arg(unreadableFiles));
                io->AppendUploadLog(__FUNCTION__, QString("fs.size() [%1] before being sent into UpdateParsedUploads()").arg(fs.size()));

                ParseUploadedFiles(fs, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, uploadstagingpath, uploadRowID);
                fs.clear();

                /* update the status */
                SetUploadStatus(uploadRowID, "parsingcomplete", 100);

            } /* end if squirrel */
        } /* end while */
    }

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- GetUploadOptions ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Poplate an UploadOptions structure
 * @param uploadRowID The uploadRowID
 * @return an UploadOptions structure
 */
UploadOptions moduleUpload::GetUploadOptions(int uploadRowID) {
    UploadOptions options;
    QSqlQuery q;

    q.prepare("select * from uploads where upload_id = :uploadid");
    q.bindValue(":uploadid", uploadRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        options.dataPath = q.value("upload_datapath").toString();
        options.modality = q.value("upload_modality").toString();
        options.patientID = q.value("upload_patientid").toString();
        options.projectRowID = q.value("upload_destprojectid").toInt();
        options.seriesMatchCriteria = q.value("upload_seriescriteria").toString();
        options.source = q.value("upload_source").toString();
        options.studyMatchCriteria = q.value("upload_studycriteria").toString();
        options.subjectMatchCriteria = q.value("upload_subjectcriteria").toString();
        options.type = q.value("upload_type").toString();
    }
    return options;
}


/* ---------------------------------------------------------- */
/* --------- ParseUploadedFiles ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief This function updates the upload* tables in the database, based on the previously parsed DICOM (or other) files
 * @param fs Multilevel hash containing [subject][study][series][files]. Some import methods will obtain the subjectID from the directory name for example
 * @param upload_subjectcriteria The subject parsing/archiving criteria. Possible values `patientid`, `specificpatientid`, `patientidfromdir`, `namesexdob`
 * @param upload_studycriteria The study parsing/archiving criteria. Possible values `modalitystudydate`, `studyuid`
 * @param upload_seriescriteria The series parsing/archiving criteria. Possible values `seriesnum`, `seriesdate`, `seriesuid`
 * @param uploadstagingpath Path to the location of this data
 * @param uploadRowID uploadRowID
 * @return true
 */
bool moduleUpload::ParseUploadedFiles(QMap<QString, QMap<QString, QMap<QString, QStringList> > > fs, QString upload_subjectcriteria, QString upload_studycriteria, QString upload_seriescriteria, QString uploadstagingpath, int uploadRowID) {

    io->AppendUploadLog(__FUNCTION__, QString("Processing [%1] subjects").arg(fs.size()));

    /* ---------- iterate through the subjects ---------- */
    for(QMap<QString, QMap<QString, QMap<QString, QStringList> > >::iterator a = fs.begin(); a != fs.end(); ++a) {
        QString subject = a.key();

        io->AppendUploadLog(__FUNCTION__, QString("Processing subject [%1]").arg(subject));

        QString PatientID, PatientName, PatientSex, PatientBirthDate;
        if ((upload_subjectcriteria == "patientid") || (upload_subjectcriteria == "specificpatientid") || (upload_subjectcriteria == "patientidfromdir")) {
            PatientID = subject; /* get subjectid by PatientID field (or the specific PatientID, or from the parent directory) */
        }
        else if (upload_subjectcriteria == "namesexdob") {
            /* get subjectid by PatientName/PatientSex/PatientBirthDate */
            QStringList parts = subject.split("|");
            if (parts.size() > 0)
                PatientName = parts[0];
            if (parts.size() > 1)
                PatientSex = parts[1];
            if (parts.size() > 2)
                PatientBirthDate = parts[2];
        }

        /* get the uploadsubject_id */
        int subjectid(0);
        QString m;
        subjectid = InsertOrUpdateParsedSubject(-1, upload_subjectcriteria, uploadRowID, PatientID, PatientName, PatientSex, PatientBirthDate, m);

        /* ---------- iterate through the studies ---------- */
        for(QMap<QString, QMap<QString, QStringList> >::iterator b = fs[subject].begin(); b != fs[subject].end(); ++b) {

            QString study = b.key();

            io->AppendUploadLog(__FUNCTION__, QString("Processing study [%1]").arg(study));

            QString Modality;
            QString StudyDateTime;
            QString StudyInstanceUID;
            if (upload_studycriteria == "modalitystudydate") {
                /* get studyid from Modality/StudyDateTime fields */
                QStringList parts = study.split("|");
                if (parts.size() > 0) {
                    Modality = parts[0];
                    if (parts.size() > 1)
                        StudyDateTime = parts[1];
                }
            }
            else if (upload_studycriteria == "studyuid") {
                /* get studyid using StudyInstanceUID field */
                StudyInstanceUID = study;
            }
            /* get the uploadstudy_id */
            int studyid(0);
            QString m;
            studyid = InsertOrUpdateParsedStudy(-1, upload_studycriteria, subjectid, -1, StudyDateTime, Modality, StudyInstanceUID, "", "", "", "", m);

            /* ---------- iterate through the series ---------- */
            for(QMap<QString, QStringList>::iterator c = fs[subject][study].begin(); c != fs[subject][study].end(); ++c) {

                QString series = c.key();
                io->AppendUploadLog(__FUNCTION__, QString("Processing series [%1]").arg(series));

                int SeriesNumber(0);
                QString SeriesDateTime;
                QString SeriesInstanceUID;

                if (upload_seriescriteria == "seriesnum") {
                    /* get seriesid from SeriesNumber field */
                    bool ok = false;
                    SeriesNumber = series.toInt(&ok);
                    if (!ok)
                        SeriesNumber = 0;
                }
                else if (upload_seriescriteria == "seriesdate") {
                    /* get seriesid using SeriesDateTime field */
                    SeriesDateTime = series;
                }
                else if (upload_seriescriteria == "seriesuid") {
                    /* get seriesid using SeriesInstanceUID field */
                    SeriesInstanceUID = series;
                }

                /* get uploadseries_id */
                int seriesid(0);

                QStringList files = fs[subject][study][series];
                int numfiles = files.size();
                //io->AppendUploadLog(__FUNCTION__, QString("numfiles [%1]   numfiles [%2]").arg(files.size()).arg(numfiles));
                seriesid = InsertOrUpdateParsedSeries(-1, upload_seriescriteria, studyid, SeriesDateTime, SeriesNumber, SeriesInstanceUID, files, numfiles, "", "", "", "", "", "", 0, 0, m);

                /* remove the prefix for the files */
                QStringList filesNoPrefix;
                for (int ii=0; ii<files.size(); ii++) {
                    QString str = files[ii];
                    str.replace(str.indexOf(uploadstagingpath), uploadstagingpath.size(), "");
                    filesNoPrefix.append(str);
                }

                /* we've arrived at a series, so let's put it into the database */
                /* get tags from first file in the list to populate the subject/study/series info not included in the criteria matching */

                /* if subject and study are unreadable, put those files into the appropriate bin */
                QHash<QString, QString> tags;
                QString m;
                bool csa = false;
                if (n->cfg["enablecsa"] == "1") csa = true;
				QString binpath = n->cfg["nidbdir"] + "/bin";
				img->GetImageFileTags(files[0], binpath, csa, tags, m);

                QSqlQuery q3;

                /* don't overwrite the tags in the databse that were used to group the subject/study/series */
                /* we run the InsertOrUpdateS*() functions again, because sometimes information is stored at the series level, which we first encounter here. The subject/study/series may already exist, but it should be updated with the new information */

                /* update subject details - depending on the original matching criteria, not all of these values may be in the database */
                InsertOrUpdateParsedSubject(subjectid, upload_subjectcriteria, uploadRowID, PatientID, PatientName, PatientSex, PatientBirthDate, m);

                /* update study details - we already know the studyRowID, so just update some of the other fields */
                InsertOrUpdateParsedStudy(studyid, upload_studycriteria, subjectid, -1, StudyDateTime, Modality, StudyInstanceUID, tags["StudyDescription"], tags["FileType"], tags["Manufacturer"] + " " + tags["ManufacturerModelName"], tags["OperatorsName"], m);

                /* update series details */
                InsertOrUpdateParsedSeries(seriesid, "seriesnum", studyid, SeriesDateTime, SeriesNumber, SeriesInstanceUID, files, numfiles, tags["SeriesDescription"], tags["ProtocolName"], tags["RepetitionTime"], tags["EchoTime"], tags["SpacingBetweenSlices"], tags["SliceThickness"], tags["Rows"].toInt(), tags["Columns"].toInt(), m);

            }
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ParseUploadedSquirrel -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief This function updates the upload* tables in the database from a squirrel file
 * @param sqrl squirrel object
 * @param upload_subjectcriteria The subject parsing/archiving criteria. Possible values `patientid`, `specificpatientid`, `patientidfromdir`, `namesexdob`
 * @param upload_studycriteria The study parsing/archiving criteria. Possible values `modalitystudydate`, `studyuid`
 * @param upload_seriescriteria The series parsing/archiving criteria. Possible values `seriesnum`, `seriesdate`, `seriesuid`
 * @param uploadstagingpath Path to the location of this data
 * @param uploadRowID uploadRowID
 * @return true
 */
bool moduleUpload::ParseUploadedSquirrel(squirrel *sqrl, QString upload_subjectcriteria, QString upload_studycriteria, QString upload_seriescriteria, QString uploadstagingpath, int uploadRowID) {
    n->Log(sqrl->Print());

    /* load subjects, studies, series into the upload_* tables */

    /* iterate through the subjects */
    QList<squirrelSubject> subjects = sqrl->GetSubjectList();
    foreach (squirrelSubject subject, subjects) {
        n->Log("Found subject [" + subject.ID + "]");

        QString m;
        int subjectRowID = InsertOrUpdateParsedSubject(-1, "patientid", uploadRowID, subject.ID, subject.ID, subject.Sex, subject.DateOfBirth.toString("yyyy-MM-dd"), m);
        InsertOrUpdateParsedSubject(subjectRowID, "patientid", uploadRowID, subject.ID, subject.ID, subject.Sex, subject.DateOfBirth.toString("yyyy-MM-dd"), m);

        /* get studies */
        QList<squirrelStudy> studies = sqrl->GetStudyList(subject.GetObjectID());
        foreach (squirrelStudy study, studies) {
            n->Log(QString("Found study [%1]").arg(study.StudyNumber));

            int studyRowID = InsertOrUpdateParsedStudy(-1, "modalitystudydate", subjectRowID, study.StudyNumber, study.DateTime.toString("yyyy-MM-dd hh:mm:ss"), study.Modality, study.StudyUID, study.Description, sqrl->DataFormat, study.Equipment, "", m);
            InsertOrUpdateParsedStudy(studyRowID, "modalitystudydate", subjectRowID, study.StudyNumber, study.DateTime.toString("yyyy-MM-dd hh:mm:ss"), study.Modality, study.StudyUID, study.Description, sqrl->DataFormat, study.Equipment, "", m);

            /* get series */
            QList<squirrelSeries> serieses = sqrl->GetSeriesList(study.GetObjectID());
            foreach (squirrelSeries series, serieses) {
                n->Log(QString("Found series [%1]").arg(series.SeriesNumber));

                int numfiles = series.files.size();
                int seriesRowID = InsertOrUpdateParsedSeries(-1, "seriesnum", studyRowID, series.DateTime.toString("yyyy-MM-dd hh:mm:ss"), series.SeriesNumber, series.SeriesUID, series.files, numfiles, series.Description, series.Protocol, "", "", "", "", 0, 0, m);
                InsertOrUpdateParsedSeries(seriesRowID, "seriesnum", studyRowID, series.DateTime.toString("yyyy-MM-dd hh:mm:ss"), series.SeriesNumber, series.SeriesUID, series.files, numfiles, series.Description, series.Protocol, "", "", "", "", 0, 0, m);
            }
        }
    }

    /* update the status */
    SetUploadStatus(uploadRowID, "parsingcomplete", 100);

    return true;
}

/* ---------------------------------------------------------- */
/* --------- ArchiveSelectedFiles --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Archive the series marked by the user based on specified upload criteria
 * @return `true` if successful, `false` otherwise
 */
bool moduleUpload::ArchiveSelectedFiles() {
    QSqlQuery q;
    bool ret(false);

    /* get list of uploads that are ready to be archived */
    q.prepare("select * from uploads where upload_status = 'queueforarchive' and upload_type <> 'squirrel'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {

            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return false; }

            ret = true;
            bool error = false;
            int uploadRowID = q.value("upload_id").toInt();
            io->SetUploadID(uploadRowID);

            //QString upload_status = q.value("upload_status").toString();
            int upload_destprojectid = q.value("upload_destprojectid").toInt();
            QString upload_patientid = q.value("upload_patientid").toString();
            QString upload_stagingpath = q.value("upload_stagingpath").toString();
            QString upload_subjectcriteria = q.value("upload_subjectcriteria").toString();
            QString upload_studycriteria = q.value("upload_studycriteria").toString();
            QString upload_seriescriteria = q.value("upload_seriescriteria").toString();

            io->AppendUploadLog(__FUNCTION__, QString("Beginning archiving of upload [%1] with upload_destprojectid of [%2]").arg(uploadRowID).arg(upload_destprojectid));

            /* set status to archiving */
            SetUploadStatus(uploadRowID, "archiving", 0.0);

            /* get list of series which should be archived from this upload */
            QSqlQuery q2;
            q2.prepare("select * from upload_series a left join upload_studies b on a.uploadstudy_id = b.uploadstudy_id left join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where a.uploadseries_status = 'import' and c.upload_id = :uploadid");
            q2.bindValue(":uploadid", uploadRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            int numSeries = q2.size();
            int i(0);
            if (numSeries > 0) {
                while (q2.next()) {

                    /* check if this module should be running */
                    n->ModuleRunningCheckIn();
                    if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return false; }

                    ret = true;
                    int uploadseries_id = q2.value("uploadseries_id").toInt();

                    n->Log(QString("Working on uploadSeriesRowID [%1]").arg(uploadseries_id));

                    /* get any matching subject/study/series */
                    int matchingsubjectid(-1), matchingstudyid(-1), matchingseriesid(-1);
                    if (!q2.value("matchingsubjectid").isNull())
                        matchingsubjectid = q2.value("matchingsubjectid").toInt();
                    if (!q2.value("matchingstudyid").isNull())
                        matchingstudyid = q2.value("matchingstudyid").toInt();
                    if (!q2.value("matchingseriesid").isNull())
                        matchingseriesid = q2.value("matchingseriesid").toInt();

                    /* get information about this series to be imported */
                    QStringList uploadseries_filelist = q2.value("uploadseries_filelist").toString().split(",");
                    //for(int i=0; i<uploadseries_filelist.size(); i++) {
                    //    if (uploadseries_filelist[i].trimmed() != "")
                    //        uploadseries_filelist[i] = upload_stagingpath + uploadseries_filelist[i];
                    //}

                    performanceMetric perf;
                    /* insert the series */
                    io->ArchiveDICOMSeries(-1, matchingsubjectid, matchingstudyid, matchingseriesid, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, upload_destprojectid, upload_patientid, -1, "", "Uploaded to NiDB", uploadseries_filelist, perf);

                    i++;
                    double pct = static_cast<double>(i)/static_cast<double>(numSeries) * 100.0;
                    SetUploadStatus(uploadRowID, "archiving", pct);
                }

                io->AppendUploadLog(__FUNCTION__, QString("Completed archiving of upload [%1]").arg(uploadRowID));
            }
            else {
                error = true;
                io->AppendUploadLog(__FUNCTION__, QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }

            if (error) {
                SetUploadStatus(uploadRowID, "archiveerror", -1.0);
            }
            else {
                SetUploadStatus(uploadRowID, "archivecomplete", 100.0);
                /* delete all of the source data and mark status as 'archivecomplete' */
                QString m;
                if (RemoveDir(upload_stagingpath, m))
                    io->AppendUploadLog(__FUNCTION__, QString("Removed upload staging directory [%1]").arg(upload_stagingpath));
                else
                    io->AppendUploadLog(__FUNCTION__, QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }
        }
    }
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- ArchiveSelectedSquirrel ------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Archive the series marked by the user for archiving, based on specified upload criteria
 * @return `true` if successful, `false` otherwise
 */
bool moduleUpload::ArchiveSelectedSquirrel() {
    QSqlQuery q;
    bool ret(false);

    /* get list of uploads that are ready to be archived */
    q.prepare("select * from uploads where upload_status = 'queueforarchive' and upload_type = 'squirrel'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {

            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return false; }

            ret = true;
            bool error = false;
            int uploadRowID = q.value("upload_id").toInt();
            io->SetUploadID(uploadRowID);

            //QString upload_status = q.value("upload_status").toString();
            int upload_destprojectid = q.value("upload_destprojectid").toInt();
            //QString upload_patientid = q.value("upload_patientid").toString();
            QString upload_stagingpath = q.value("upload_stagingpath").toString();
            //QString upload_subjectcriteria = q.value("upload_subjectcriteria").toString();
            //QString upload_studycriteria = q.value("upload_studycriteria").toString();
            //QString upload_seriescriteria = q.value("upload_seriescriteria").toString();

            io->AppendUploadLog(__FUNCTION__, QString("Beginning archiving of upload [%1] with upload_destprojectid of [%2]").arg(uploadRowID).arg(upload_destprojectid));

            /* set status to archiving */
            SetUploadStatus(uploadRowID, "archiving", 0.0);

            /* unzip the squirrel package */
            QString f;
            QString m;
            if (!FindFirstFile(upload_stagingpath, "*.sqrl", f, m)) {
                n->Log("Unable to find any squirrel files in path [" + upload_stagingpath + "]", __FUNCTION__);
                continue;
            }
            QString tmppath;
            squirrel *sqrl = new squirrel();
            sqrl->SetPackagePath(f);
            sqrl->SetQuickRead(false); /* it will take longer to read, but we will want the contents of all the params.json files */
            if (sqrl->Read()) {
                /* extract the file to a temp directory */
                tmppath = n->cfg["tmpdir"] + "/" + GenerateRandomString(20);
                if (!MakePath(tmppath,m)) {
                    n->Log("Error creating temp directory [" + tmppath + "] with error [" + m + "]", __FUNCTION__);
                    continue;
                }
                else {
                    n->Log("Created temp directory [" + tmppath + "]. Now extracting squirrel package...", __FUNCTION__);
                }

                if (sqrl->Extract(tmppath, m)) {
                    n->Log("Successfuly extract squirrel package [" + f + "] to directory [" + tmppath + "]", __FUNCTION__);
                }
                else {
                    n->Log("Error extracting squirrel package [" + f + "] to directory [" + tmppath + "] with message [" + m + "]", __FUNCTION__);
                }
            }

            /* get list of series which should be archived from this upload */
            QSqlQuery q2;
            q2.prepare("select * from upload_series a left join upload_studies b on a.uploadstudy_id = b.uploadstudy_id left join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where a.uploadseries_status = 'import' and c.upload_id = :uploadid");
            q2.bindValue(":uploadid", uploadRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            int numSeries = q2.size();
            int i(0);
            if (numSeries > 0) {
                while (q2.next()) {

                    /* check if this module should be running */
                    n->ModuleRunningCheckIn();
                    if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return false; }

                    ret = true;
                    //int uploadSeriesRowID = q2.value("uploadseries_id").toInt();
                    //int uploadSubjectRowID = q2.value("uploadsubject_id").toInt();
                    QString uploadSubjectID = q2.value("uploadsubject_patientid").toString();
                    QString uploadSubjectName = q2.value("uploadsubject_name").toString();
                    QString uploadSubjectSex = q2.value("uploadsubject_sex").toString();
                    QString uploadSubjectDOB = q2.value("uploadsubject_dob").toString();

                    int uploadStudyNumber = q2.value("uploadstudy_number").toInt();
                    //QString uploadStudyDesc = q2.value("uploadstudy_desc").toString();
                    QString uploadStudyDate = q2.value("uploadstudy_date").toString();
                    QString uploadStudyModality = q2.value("uploadstudy_modality").toString();

                    int uploadSeriesNumber = q2.value("uploadseries_num").toInt();
                    //QString uploadSeriesDesc = q2.value("uploadseries_desc").toString();
                    //QString uploadSeriesDate = q2.value("uploadseries_date").toString();

                    /* get squirrel objects */
                    squirrelSubject sqrlSubject = sqrl->GetSubject(sqrl->FindSubject(uploadSubjectID));
                    squirrelStudy sqrlStudy = sqrl->GetStudy(sqrl->FindStudy(uploadSubjectID, uploadStudyNumber));
                    squirrelSeries sqrlSeries = sqrl->GetSeries(sqrl->FindSeries(uploadSubjectID, uploadStudyNumber, uploadSeriesNumber));

                    /* get any matching subject/study/series */
                    int subjectRowID(-1), studyRowID(-1), seriesRowID(-1);
                    QString subjectUID;

                    /* get or create subject */
                    if (io->GetSubject("patientid", subjectRowID, upload_destprojectid, uploadSubjectID, uploadSubjectName, sqrlSubject.Sex, sqrlSubject.DateOfBirth.toString("yyyy-MM-dd"), subjectRowID, subjectUID)) {
                        n->Log(QString("Found existing subject UID [%1], subjectRowID [%2]").arg(subjectUID).arg(subjectRowID), __FUNCTION__);
                    }
                    else {
                        /* create subject */
                        if (io->CreateSubject(uploadSubjectID, uploadSubjectName, uploadSubjectDOB, uploadSubjectSex, sqrlStudy.Weight, sqrlStudy.Height, subjectRowID, subjectUID))
                            n->Log(QString("Successfully created subject with rowID [%1] and UID [%2]").arg(subjectRowID).arg(subjectUID), __FUNCTION__);
                        else
                            n->Log(QString("Error creating subject with uploadSubjectID [%1]").arg(uploadSubjectID), __FUNCTION__);
                    }

                    int enrollmentRowID;
                    io->GetOrCreateEnrollment(subjectRowID, upload_destprojectid, enrollmentRowID);

                    /* get or create study */
                    int localStudyNumber(-1);
                    if (io->GetStudy("modalitystudydate", studyRowID, enrollmentRowID, uploadStudyDate, uploadStudyModality, sqrlStudy.StudyUID, studyRowID)) {
                        n->Log(QString("Found existing study.  number [%1], studytRowID [%2]").arg(subjectUID).arg(subjectRowID), __FUNCTION__);
                    }
                    else {
                        /* create study */
                        if (io->CreateStudy(subjectRowID, enrollmentRowID, uploadStudyDate, sqrlStudy.StudyUID, uploadStudyModality, uploadSubjectID, sqrlStudy.AgeAtStudy, sqrlStudy.Height, sqrlStudy.Weight, sqrlStudy.Description, "operator", "", sqrlStudy.Equipment, "", "", studyRowID, localStudyNumber))
                            n->Log(QString("Successfully created study (date [%1]  modality [%2]) with studyRowID [%3] squirrelStudyNumber [%4] and localStudyNumber [%5]").arg(sqrlStudy.DateTime.toString("yyyy-MM-dd hh:mm:ss")).arg(sqrlStudy.Modality).arg(studyRowID).arg(sqrlStudy.StudyNumber).arg(localStudyNumber), __FUNCTION__);
                        else
                            n->Log(QString("Error creating study with uploadSubjectID [%1]").arg(uploadSubjectID), __FUNCTION__);
                    }

                    /* get the squirrel path to the series, then get the list of files */
                    QString squirrelSeriesPath = QString("%1/data/%2/%3/%4").arg(tmppath).arg(uploadSubjectID).arg(uploadStudyNumber).arg(uploadSeriesNumber);
                    QStringList files = FindAllFiles(squirrelSeriesPath, "*", false);
                    qint64 b(0),c(0);
                    GetDirSizeAndFileCount(squirrelSeriesPath, c, b);
                    n->Log(QString("squirrelSeriesPath is [%1]. It contains [%2] files with total size [%3] bytes").arg(squirrelSeriesPath).arg(c).arg(b));

                    //performanceMetric perf;
                    QHash<QString, QString> tags;
                    tags["SeriesDescription"] = sqrlSeries.Description;
                    tags["SeriesDateTime"] = sqrlSeries.DateTime.toString("yyyy-MM-dd hh:mm:ss");
                    tags["ProtocolName"] = sqrlSeries.params["ProtocolName"];
                    tags["SequenceName"] = sqrlSeries.params["SequenceName"];
                    tags["RepetitionTime"] = sqrlSeries.params["RepetitionTime"];
                    tags["EchoTime"] = sqrlSeries.params["EchoTime"];
                    tags["FlipAngle"] = sqrlSeries.params["FlipAngle"];
                    tags["InPlanePhaseEncodingDirection"] = sqrlSeries.params["InPlanePhaseEncodingDirection"];
                    tags["PhaseEncodeAngle"] = sqrlSeries.params["PhaseEncodeAngle"];
                    tags["PhaseEncodingDirectionPositive"] = sqrlSeries.params["PhaseEncodingDirectionPositive"];
                    tags["pixelX"] = sqrlSeries.params["pixelX"];
                    tags["pixelY"] = sqrlSeries.params["pixelY"];
                    tags["SliceThickness"] = sqrlSeries.params["SliceThickness"];
                    tags["MagneticFieldStrength"] = sqrlSeries.params["MagneticFieldStrength"];
                    tags["Rows"] = sqrlSeries.params["Rows"];
                    tags["Columns"] = sqrlSeries.params["Columns"];
                    tags["zsize"] = sqrlSeries.params["zsize"];
                    tags["InversionTime"] = sqrlSeries.params["InversionTime"];
                    tags["PercentSampling"] = sqrlSeries.params["PercentSampling"];
                    tags["PercentPhaseFieldOfView"] = sqrlSeries.params["PercentPhaseFieldOfView"];
                    tags["AcquisitionMatrix"] = sqrlSeries.params["AcquisitionMatrix"];
                    tags["SliceThickness"] = sqrlSeries.params["SliceThickness"];
                    tags["SpacingBetweenSlices"] = sqrlSeries.params["SpacingBetweenSlices"];
                    tags["PixelBandwidth"] = sqrlSeries.params["PixelBandwidth"];
                    tags["ImageType"] = sqrlSeries.params["ImageType"];
                    tags["ImageComments"] = sqrlSeries.params["ImageComments"];
                    tags["boldreps"] = QString("%1").arg(sqrlSeries.FileCount);

                    /* insert the series */
                    if (sqrl->DataFormat.startsWith("nifti", Qt::CaseInsensitive)) {
                        n->Debug("squirrel data format is [" + sqrl->DataFormat + "], calling ArchiveNiftiSeries()");
                        io->ArchiveNiftiSeries(subjectRowID, studyRowID, seriesRowID, uploadSeriesNumber, tags, files);
                    }
                    else if ((sqrl->DataFormat == "dicom") || (sqrl->DataFormat == "orig") || (sqrl->DataFormat == "anon") || (sqrl->DataFormat == "anonfull")) {
                        n->Debug("squirrel data format is [" + sqrl->DataFormat + "], calling ArchiveDICOMSeries()");
                        //io->ArchiveDICOMSeries();
                    }
                    else {
                        n->Debug("squirrel data format is [" + sqrl->DataFormat + "], unrecognized");
                    }

                    i++;
                    double pct = static_cast<double>(i)/static_cast<double>(numSeries) * 100.0;
                    SetUploadStatus(uploadRowID, "archiving", pct);
                }

                io->AppendUploadLog(__FUNCTION__, QString("Completed archiving of upload [%1]").arg(uploadRowID));
            }
            else {
                error = true;
                io->AppendUploadLog(__FUNCTION__, QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }

            if (error) {
                SetUploadStatus(uploadRowID, "archiveerror", -1.0);
            }
            else {
                SetUploadStatus(uploadRowID, "archivecomplete", 100.0);
                /* delete all of the source data and mark status as 'archivecomplete' */
                QString m;
                if (RemoveDir(upload_stagingpath, m))
                    io->AppendUploadLog(__FUNCTION__, QString("Removed upload staging directory [%1]").arg(upload_stagingpath));
                else
                    io->AppendUploadLog(__FUNCTION__, QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }
        }
    }
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- SetUploadStatus -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Update the upload status, with a string and percent complete
 * @param uploadid uploadRowID
 * @param status Status message
 * @param percent percent complete
 */
void moduleUpload::SetUploadStatus(int uploadid, QString status, double percent) {

    QSqlQuery q;

    q.prepare("update uploads set upload_status = :status, upload_statuspercent = :pct where upload_id = :uploadid");
    q.bindValue(":status", status);
    //if (percent < 0.0) q.bindValue(":pct", QVariant(QVariant::Double)); else q.bindValue(":pct", percent);
    if (percent < 0.0) q.bindValue(":pct", QVariant(QMetaType::fromType<double>())); else q.bindValue(":pct", percent);
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, false);
}


/* ---------------------------------------------------------- */
/* --------- GetUploadStatus -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get the upload status string
 * @param uploadid uploadRowID
 * @return The upload status
 */
QString moduleUpload::GetUploadStatus(int uploadid) {
    QString status = "";

    QSqlQuery q;
    q.prepare("select upload_status from uploads where upload_id = :uploadid");
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
    if (q.size() > 0) {
        q.first();
        status = q.value("upload_status").toString();
    }

    return status;
}


/* ---------------------------------------------------------- */
/* --------- InsertOrUpdateParsedSubject -------------------- */
/* ---------------------------------------------------------- */
int moduleUpload::InsertOrUpdateParsedSubject(int parsedSubjectRowID, QString upload_subjectcriteria, int uploadRowID, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, QString &m) {
    QSqlQuery q;

    if (parsedSubjectRowID >= 0) {
        if ( (upload_subjectcriteria == "patientid") || (upload_subjectcriteria == "specificpatientid") || (upload_subjectcriteria == "patientidfromdir") ) {
            /* update all subject details except PatientID */
            q.prepare("update upload_subjects set uploadsubject_name = :name, uploadsubject_sex = :sex, uploadsubject_dob = :dob where uploadsubject_id = :subjectid");
            q.bindValue(":name", PatientName);
            q.bindValue(":sex", PatientSex);
            q.bindValue(":dob", PatientBirthDate);
            q.bindValue(":subjectid", parsedSubjectRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else if (upload_subjectcriteria == "namesexdob") {
            /* update all subject details except PatientName/Sex/BirthDate */
            q.prepare("update upload_subjects set uploadsubject_patientid = :patientid where uploadsubject_id = :subjectid");
            q.bindValue(":name", PatientID);
            q.bindValue(":subjectid", parsedSubjectRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else
            m = "Unspecified subject criteria [" + upload_subjectcriteria + "]";
    }
    else {
        if ((upload_subjectcriteria == "patientid") || (upload_subjectcriteria == "specificpatientid") || (upload_subjectcriteria == "patientidfromdir")) {
            /* get parsedSubjectRowID by PatientID field (or the specific PatientID, or from the parent directory) */

            /* check if the parsedSubjectRowID exists ... */
            q.prepare("select uploadsubject_id from upload_subjects where upload_id = :uploadid and uploadsubject_patientid = :patientid");
            q.bindValue(":uploadid", uploadRowID);
            q.bindValue(":patientid", PatientID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSubjectRowID = q.value("uploadsubject_id").toInt();
            }
            else {
                /* ... otherwise create a new subject */
                q.prepare("insert into upload_subjects (upload_id, uploadsubject_patientid) values (:uploadid, :patientid)");
                q.bindValue(":uploadid", uploadRowID);
                q.bindValue(":patientid", PatientID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedSubjectRowID = q.lastInsertId().toInt();
            }
        }
        else if (upload_subjectcriteria == "namesexdob") {
            /* check if the parsedSubjectRowID already exists ... */
            q.prepare("select uploadsubject_id from upload_subjects where upload_id = :uploadid and uploadsubject_name = :patientname and uploadsubject_dob = :patientdob and uploadsubject_sex = :patientsex");
            q.bindValue(":uploadid", uploadRowID);
            q.bindValue(":patientname", PatientName);
            q.bindValue(":patientdob", PatientBirthDate);
            q.bindValue(":patientsex", PatientSex);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSubjectRowID = q.value("uploadsubject_id").toInt();
            }
            else {
                /* ... otherwise create a new subject */
                q.prepare("insert into upload_subjects (upload_id, uploadsubject_name, uploadsubject_dob, uploadsubject_sex) values (:uploadid, :patientname, :patientdob, :patientsex)");
                q.bindValue(":uploadid", uploadRowID);
                q.bindValue(":patientname", PatientName);
                q.bindValue(":patientdob", PatientBirthDate);
                q.bindValue(":patientsex", PatientSex);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedSubjectRowID = q.lastInsertId().toInt();
            }
        }
        else
            m = "Unspecified subject criteria [" + upload_subjectcriteria + "]. That's weird it would show up here...";
    }

    return parsedSubjectRowID;
}


/* ---------------------------------------------------------- */
/* --------- InsertOrUpdateParsedStudy ---------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Insert or update a parsed study. This function will update an existing study in the `upload_study` table if it is found by the criteria, otherwise it will insert a new study. This function is designed to be called multiple times because some imaging data do not contain all study information in a single file. Meta data from other files/locations may be needed, and necessitates calling this function more than once to update the new information.
 * @param parsedStudyRowID This should be `-1` the first time the function is called. If an existing upload_study rowID is known, pass it in. The function will not attempt to create a new study if a studyID is passed in
 * @param upload_studycriteria Criteria to match new study to existing study. Possible values `modalitystudydate`, `studyuid`
 * @param subjectRowID rowID for the parent subject in the upload_subject table
 * @param StudyDateTime Study datetime
 * @param Modality Modality
 * @param StudyInstanceUID Study Instance UID, from the DICOM field
 * @param StudyDescription Study description
 * @param FileType Filetype / datatype
 * @param Equipment Equipment description
 * @param Operator Operator during the study
 * @param msg Any messages generated by the function
 * @return
 */
int moduleUpload::InsertOrUpdateParsedStudy(int parsedStudyRowID, QString upload_studycriteria, int subjectRowID, int StudyNumber, QString StudyDateTime, QString Modality, QString StudyInstanceUID, QString StudyDescription, QString FileType, QString Equipment, QString Operator, QString &msg) {

    QSqlQuery q;

    if (parsedStudyRowID >= 0) {
        /* update study details */
        if (upload_studycriteria == "modalitystudydate") {
            /* update all study details except Modality/StudyDateTime */
            q.prepare("update upload_studies set uploadstudy_desc = :desc, uploadstudy_datatype = :datatype, uploadstudy_equipment = :equipment, uploadstudy_operator = :operator where uploadstudy_id = :studyid");
            q.bindValue(":desc", StudyDescription);
            q.bindValue(":datatype", FileType);
            q.bindValue(":equipment", Equipment);
            q.bindValue(":operator", Operator);
            q.bindValue(":studyinstanceuid", StudyInstanceUID);
            q.bindValue(":studyid", parsedStudyRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else if (upload_studycriteria == "studyuid") {
            /* update all study details except StudyInstanceUID */
            q.prepare("update upload_studies set uploadstudy_desc = :desc, uploadstudy_date = :datetime, uploadstudy_modality = :modality, uploadstudy_datatype = :datatype, uploadstudy_equipment = :equipment, uploadstudy_operator = :operator where uploadstudy_id = :studyid");
            q.bindValue(":desc", StudyDescription);
            q.bindValue(":datetime", StudyDateTime);
            q.bindValue(":modality", Modality);
            q.bindValue(":datatype", FileType);
            q.bindValue(":equipment", Equipment);
            q.bindValue(":operator", Operator);
            q.bindValue(":studyinstanceuid", StudyInstanceUID);
            q.bindValue(":studyid", parsedStudyRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else
            msg = "Unspecified study criteria [" + upload_studycriteria + "]";
    }
    else {
        if (upload_studycriteria == "modalitystudydate") {
            /* check if the parsedStudyRowID exists ... */
            q.prepare("select uploadstudy_id from upload_studies where uploadsubject_id = :subjectid and uploadstudy_date = :studydatetime and uploadstudy_modality = :modality");
            q.bindValue(":subjectid", subjectRowID);
            q.bindValue(":studydatetime", StudyDateTime);
            q.bindValue(":modality", Modality);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedStudyRowID = q.value("uploadstudy_id").toInt();
            }
            else {
                /* ... otherwise create a new study */
                q.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_number, uploadstudy_date, uploadstudy_modality) values (:subjectid, :studyNumber, :studydatetime, :modality)");
                q.bindValue(":subjectid", subjectRowID);
                q.bindValue(":studyNumber", StudyNumber);
                q.bindValue(":studydatetime", StudyDateTime);
                q.bindValue(":modality", Modality);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedStudyRowID = q.lastInsertId().toInt();
            }
        }
        else if (upload_studycriteria == "studyuid") {
            /* check if the parsedStudyRowID already exists ... */
            q.prepare("select uploadstudy_id from upload_studies where uploadsubject_id = :subjectid and uploadstudy_instanceuid = :studyinstanceuid");
            q.bindValue(":subjectid", subjectRowID);
            q.bindValue(":studyinstanceuid", StudyInstanceUID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedStudyRowID = q.value("uploadstudy_id").toInt();
            }
            else {
                /* ... otherwise create a new study */
                q.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_number, uploadstudy_instanceuid) values (:subjectid, :studyNumber, :studyinstanceuid)");
                q.bindValue(":subjectid", subjectRowID);
                q.bindValue(":studyNumber", StudyNumber);
                q.bindValue(":studyinstanceuid", StudyInstanceUID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedStudyRowID = q.lastInsertId().toInt();
            }
        }
        else
            msg = "Unspecified study criteria [" + upload_studycriteria + "]. That's weird it would show up here...";
    }

    return parsedStudyRowID;
}


/* ---------------------------------------------------------- */
/* --------- InsertOrUpdateParsedSeries --------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Insert or update a parsed series. This function will update an existing series in the upload_series table if it is found by the criteria, otherwise it will insert a new series. This function is designed to be called multiple times because some imaging data do not contain all series information in a single file. Meta data from other files/locations may be needed, and necessitates calling this function more than once to update the new information.
 * @param parsedSeriesRowID This should be `-1` the first time the function is called. If an existing upload_series rowID is known, pass it in. The function will not attempt to create a new series if a seriesID is passed in
 * @param upload_seriescriteria Criteria to match new series to existing series. Possible values `seriesnum`, `seriesdate`, `seriesuid`
 * @param studyRowID rowID for the parent study in the upload_study table
 * @param SeriesDateTime Series datetime
 * @param SeriesNumber Series number
 * @param SeriesInstanceUID Series Instance UID, from the DICOM field
 * @param files List of files, relative paths are preferable
 * @param numfiles Number of files
 * @param SeriesDescription Series description, often what the user sees at the operator console
 * @param ProtocolName Protocol name, which may be different than the series description. This may be the sequence name, like epifd64* for example
 * @param RepetitionTime Repetition time in ms. This is a string. If the string is blank, a null will be inserted into the database
 * @param EchoTime Echo time in ms. This is a string. If the string is blank, a null will be inserted into the database
 * @param SpacingBetweenSlices Spacing between slices in mm. This is a string. If the string is blank, a null will be inserted into the database
 * @param SliceThickness Slice thickness in mm. This is a string. If the string is blank, a null will be inserted into the database
 * @param Rows Number of rows in the image
 * @param Columns Number of columns in the image
 * @param msg Any messages generated
 * @return
 */
int moduleUpload::InsertOrUpdateParsedSeries(int parsedSeriesRowID, QString upload_seriescriteria, int studyRowID, QString SeriesDateTime, int SeriesNumber, QString SeriesInstanceUID, QStringList &files, int &numfiles, QString SeriesDescription, QString ProtocolName, QString RepetitionTime, QString EchoTime, QString SpacingBetweenSlices, QString SliceThickness, int Rows, int Columns, QString &msg) {
    QSqlQuery q;

    if (parsedSeriesRowID >= 0) {
        if (upload_seriescriteria == "seriesnum") {
            /* update all series details except SeriesNumber */
            q.prepare("update upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_date = :date, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_instanceuid = :seriesinstanceuid, uploadseries_filelist = :files where uploadseries_id = :seriesid");
            q.bindValue(":desc", SeriesDescription);
            q.bindValue(":protocol", ProtocolName);
            q.bindValue(":date", SeriesDateTime);
            q.bindValue(":NumberOfFiles", numfiles);
            if (RepetitionTime == "") q.bindValue(":tr", QVariant(QMetaType::fromType<double>())); else q.bindValue(":tr", RepetitionTime);
            if (EchoTime == "") q.bindValue(":te", QVariant(QMetaType::fromType<double>())); else q.bindValue(":te", EchoTime);
            if (SpacingBetweenSlices == "") q.bindValue(":slicespacing", QVariant(QMetaType::fromType<double>())); else q.bindValue(":slicespacing", SpacingBetweenSlices);
            if (SliceThickness == "") q.bindValue(":slicethickness", QVariant(QMetaType::fromType<double>())); else q.bindValue(":slicethickness", SliceThickness);
            q.bindValue(":rows", Rows);
            q.bindValue(":cols", Columns);
            q.bindValue(":seriesinstanceuid", SeriesInstanceUID);
            q.bindValue(":files", files.join(","));
            q.bindValue(":seriesid", parsedSeriesRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else if (upload_seriescriteria == "seriesdate") {
            /* update all series details except SeriesDateTime */
            q.prepare("update upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_num = :num, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_instanceuid = :seriesinstanceuid, uploadseries_filelist = :files where uploadseries_id = :seriesid");
            q.bindValue(":desc", SeriesDescription);
            q.bindValue(":protocol", ProtocolName);
            q.bindValue(":num", SeriesNumber);
            q.bindValue(":NumberOfFiles", numfiles);
            if (RepetitionTime == "") q.bindValue(":tr", QVariant(QMetaType::fromType<double>())); else q.bindValue(":tr", RepetitionTime);
            if (EchoTime == "") q.bindValue(":te", QVariant(QMetaType::fromType<double>())); else q.bindValue(":te", EchoTime);
            if (SpacingBetweenSlices == "") q.bindValue(":slicespacing", QVariant(QMetaType::fromType<double>())); else q.bindValue(":slicespacing", SpacingBetweenSlices);
            if (SliceThickness == "") q.bindValue(":slicethickness", QVariant(QMetaType::fromType<double>())); else q.bindValue(":slicethickness", SliceThickness);
            q.bindValue(":rows", Rows);
            q.bindValue(":cols", Columns);
            q.bindValue(":seriesinstanceuid", SeriesInstanceUID);
            q.bindValue(":files", files.join(","));
            q.bindValue(":seriesid", parsedSeriesRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else if (upload_seriescriteria == "seriesuid") {
            /* update all series details except SeriesInstanceUID */
            q.prepare("update upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_num = :num, uploadseries_date = :date, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_filelist = :files where uploadseries_id = :seriesid");
            q.bindValue(":desc", SeriesDescription);
            q.bindValue(":protocol", ProtocolName);
            q.bindValue(":date", SeriesDateTime);
            q.bindValue(":num", SeriesNumber);
            q.bindValue(":NumberOfFiles", numfiles);
            if (RepetitionTime == "") q.bindValue(":tr", QVariant(QMetaType::fromType<double>())); else q.bindValue(":tr", RepetitionTime);
            if (EchoTime == "") q.bindValue(":te", QVariant(QMetaType::fromType<double>())); else q.bindValue(":te", EchoTime);
            if (SpacingBetweenSlices == "") q.bindValue(":slicespacing", QVariant(QMetaType::fromType<double>())); else q.bindValue(":slicespacing", SpacingBetweenSlices);
            if (SliceThickness == "") q.bindValue(":slicethickness", QVariant(QMetaType::fromType<double>())); else q.bindValue(":slicethickness", SliceThickness);
            q.bindValue(":rows", Rows);
            q.bindValue(":cols", Columns);
            q.bindValue(":files", files.join(","));
            q.bindValue(":seriesid", parsedSeriesRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else
            msg = "Unspecified series criteria [" + upload_seriescriteria + "]";
    }
    else {
        if (upload_seriescriteria == "seriesnum") {
            /* check if the studyid exists ... */
            q.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_num = :seriesnum");
            q.bindValue(":studyid", studyRowID);
            q.bindValue(":seriesnum", SeriesNumber);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSeriesRowID = q.value("uploadseries_id").toInt();

                int databaseNumFiles = q.value("uploadseries_numfiles").toInt();
                io->AppendUploadLog(__FUNCTION__, QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                io->AppendUploadLog(__FUNCTION__, QString("2) numfiles, before appending [%1]").arg(numfiles));
                numfiles += databaseNumFiles;
                io->AppendUploadLog(__FUNCTION__, QString("3) numfiles, after appending [%1]").arg(numfiles));

                QStringList databaseFiles = q.value("uploadseries_filelist").toString().split(",");
                io->AppendUploadLog(__FUNCTION__, QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                io->AppendUploadLog(__FUNCTION__, QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                files.append(databaseFiles);
                io->AppendUploadLog(__FUNCTION__, QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
            }
            else {
                /* ... otherwise create a new series */
                q.prepare("insert into upload_series (uploadstudy_id, uploadseries_num) values (:studyid, :seriesnum)");
                q.bindValue(":studyid", studyRowID);
                q.bindValue(":seriesnum", SeriesNumber);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedSeriesRowID = q.lastInsertId().toInt();
            }
        }
        else if (upload_seriescriteria == "seriesdate") {
            /* check if the seriesid already exists ... */
            q.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_date = '" + SeriesDateTime + "'");
            q.bindValue(":studyid", studyRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSeriesRowID = q.value("uploadseries_id").toInt();
                int databaseNumFiles = q.value("uploadseries_numfiles").toInt();
                io->AppendUploadLog(__FUNCTION__, QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                io->AppendUploadLog(__FUNCTION__, QString("2) numfiles, before appending [%1]").arg(numfiles));
                numfiles += databaseNumFiles;
                io->AppendUploadLog(__FUNCTION__, QString("3) numfiles, after appending [%1]").arg(numfiles));

                QStringList databaseFiles = q.value("uploadseries_filelist").toString().split(",");
                io->AppendUploadLog(__FUNCTION__, QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                io->AppendUploadLog(__FUNCTION__, QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                files.append(databaseFiles);
                io->AppendUploadLog(__FUNCTION__, QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
            }
            else {
                /* ... otherwise create a new series */
                q.prepare("insert into upload_series (uploadstudy_id, uploadseries_date) values (:studyid, '" + SeriesDateTime + "')");
                q.bindValue(":studyid", studyRowID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedSeriesRowID = q.lastInsertId().toInt();
            }
        }
        else if (upload_seriescriteria == "seriesuid") {
            /* get seriesid using SeriesInstanceUID field */

            QSqlQuery q;
            /* check if the seriesid already exists ... */
            q.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_instanceuid = :seriesinstanceuid");
            q.bindValue(":studyid", studyRowID);
            q.bindValue(":seriesinstanceuid", SeriesInstanceUID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSeriesRowID = q.value("uploadseries_id").toInt();
                int databaseNumFiles = q.value("uploadseries_numfiles").toInt();
                io->AppendUploadLog(__FUNCTION__, QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                io->AppendUploadLog(__FUNCTION__, QString("2) numfiles, before appending [%1]").arg(numfiles));
                numfiles += databaseNumFiles;
                io->AppendUploadLog(__FUNCTION__, QString("3) numfiles, after appending [%1]").arg(numfiles));

                QStringList databaseFiles = q.value("uploadseries_filelist").toString().split(",");
                io->AppendUploadLog(__FUNCTION__, QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                io->AppendUploadLog(__FUNCTION__, QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                files.append(databaseFiles);
                io->AppendUploadLog(__FUNCTION__, QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
            }
            else {
                /* ... otherwise create a new series */
                q.prepare("insert into upload_series (uploadstudy_id, uploadseries_instanceuid) values (:studyid, :seriesinstanceuid)");
                q.bindValue(":studyid", studyRowID);
                q.bindValue(":seriesinstanceuid", SeriesInstanceUID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                parsedSeriesRowID = q.lastInsertId().toInt();
            }
        }
        else {
            msg = "Unspecified seriesid criteria [" + upload_seriescriteria + "]. That's weird it would show up here...";
        }
    }

    return parsedSeriesRowID;
}
