/* ------------------------------------------------------------------------------
  NIDB moduleUpload.cpp
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

#include "moduleUpload.h"
#include "bids.h"
#include <QSqlQuery>
#include <QSet>
#include <QTextStream>

/* ---------------------------------------------------------- */
/* --------- moduleUpload ----------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::moduleUpload(nidb *a)
{
    n = a;

    io = std::make_unique<archiveIO>(n);
    img = std::make_unique<imageIO>(n);
}


/* ---------------------------------------------------------- */
/* --------- ~moduleUpload ---------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::~moduleUpload()
{
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
    n->Log(QString("Finished ReadUploads() ret [%1]").arg(ret));

    /* archive any uploads */
    ret |= ArchiveSelectedFiles();
    n->Log(QString("Finished ArchiveSelectedFiles() ret [%1]").arg(ret));

    /* archive any squirrel */
    ret |= ArchiveSelectedSquirrel();
    n->Log(QString("Finished ArchiveSelectedSquirrel() ret [%1]").arg(ret));

    /* archive any import wizard uploads */
    ret |= ArchiveSelectedUniversal();
    n->Log(QString("Finished ArchiveSelectedUniversal() ret [%1]").arg(ret));

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

    //n->Log("Checkpoint A");

    /* get list of uploads that are marked as uploadcomplete, with the upload details */
    q.prepare("select * from uploads where upload_status = 'uploadcomplete'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }
            //n->Log("Checkpoint B");

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
            //n->Log("Checkpoint C");

            /* create the path for the upload data */
            QString uploadstagingpath = QString("%1/%2").arg(n->cfg["uploadstagingdir"]).arg(uploadRowID);

            /* create temporary directory in uploadstagingdir */
            QString m;
            if (!MakePath(uploadstagingpath, m)) {
                io->AppendUploadLog("Error creating directory [" + uploadstagingpath + "]  with message [" + m + "]");
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
                io->AppendUploadLog(QString("upload_datapath is invalid [%1] ").arg(upload_datapath));

                /* update the status */
                SetUploadStatus(uploadRowID, "parsingerror");

                continue;
            }

            /* if modality is blank. BIDS and squirrel packages carry the modality of each study themselves, and import wizard uploads of each series */
            if ((upload_modality == "") && (upload_type != "bids") && (upload_type != "squirrel") && (upload_type != "universal")) {
                io->AppendUploadLog("Error. Modality was blank [" + upload_modality + "]");
                ret = 1;

                /* update the status */
                SetUploadStatus(uploadRowID, "parsingerror");

                continue;
            }
            //n->Log("Checkpoint D");

            /* copy in files from uploadtmp or nfs to the uploadstagingdir */
            io->AppendUploadLog(QString("Beginning copy of data from original path [%1] to upload staging path [%2]").arg(upload_datapath).arg(uploadstagingpath));
            QString systemstring = QString("rsync -a --stats %1/ %2/").arg(upload_datapath).arg(uploadstagingpath);
            if ((upload_type == "universal") && (upload_source == "nfs")) {
                /* only the files in the import wizard's manifest. Symlinks that point outside the folder are skipped (D12) */
                QString listfile = uploadstagingpath + ".files";
                QString m;
                if (!WriteManifestFileList(q.value("upload_manifestpath").toString(), listfile, m)) {
                    io->AppendUploadLog("Error: " + m);
                    SetUploadStatus(uploadRowID, "parsingerror");
                    continue;
                }
                systemstring = QString("rsync -a -r --safe-links --stats --files-from=%1 %2/ %3/").arg(ShellQuote(listfile)).arg(ShellQuote(upload_datapath)).arg(ShellQuote(uploadstagingpath));
                io->AppendUploadLog(SystemCommand(systemstring, true, true));
                QFile::remove(listfile);
            }
            else
                io->AppendUploadLog(SystemCommand(systemstring, true, true));
            //n->Log("Checkpoint E");

            /* remove the uploadtmp directory, if it was uploaded from the web */
            if (upload_source == "web") {
                QString m;
                if (SafeDeletePath(upload_datapath, n->cfg["uploaddir"], m)) {
                    io->AppendUploadLog("Removed upload_tmp directory [" + upload_datapath + "]");
                }
                else {
                    io->AppendUploadLog("Error: Unable to remove upload_tmp directory [" + upload_datapath + "]");
                }
            }

            /* unzip any files in the uploadstagingdir (do not recurse into a BIDS directory). Import wizard uploads
               hold only the files the user mapped, and archives aren't among them */
            if (upload_type != "universal") {
                io->AppendUploadLog("Unzipping files located in [" + uploadstagingpath + "]");
                QString unzipOutput;
                if (upload_type == "bids")
                    unzipOutput = UnzipDirectory(uploadstagingpath, false);
                else
                    unzipOutput = UnzipDirectory(uploadstagingpath, true);
                io->AppendUploadLog("Unzip output" + unzipOutput);
            }

            /* get information about the uploaded data from the uploadstagingdir (after unzipping any zip files) */
            qint64 c = 0;
            qint64 b = 0;
            GetDirSizeAndFileCount(uploadstagingpath, c, b, true);
            io->AppendUploadLog(QString("After unzipping, upload directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(uploadstagingpath).arg(c).arg(b));

            n->Log("upload_type is [" + upload_type + "]");
            /* ----- determine whether DICOM, squirrel, or BIDS ----- */
            if (upload_type == "squirrel") {
                QStringList files = FindAllFiles(uploadstagingpath, "*", true);
                foreach (QString f, files) {
                    squirrel *sqrl = new squirrel();
                    sqrl->SetCommandLineExecution(false);
                    sqrl->SetSystemTempDir(n->cfg["tmpdir"]);
                    sqrl->SetPackagePath(f);
                    sqrl->SetQuickRead(false); /* it will take longer to read, but we will want the contents of all the params.json files */
                    if (sqrl->Read()) {
                        n->Log("Successfully read squirrel file [" + f + "]");
                        n->Log(sqrl->GetLogBuffer());
                        ParseUploadedSquirrel(sqrl, uploadRowID);
                    }
                    else {
                        /* unable to read squirrel file */
                        n->Log("Error reading squirrel file [" + f + "]");
                        n->Log(sqrl->GetLogBuffer());
                    }

                    delete sqrl;
                }
            }
            else if (upload_type == "universal") {
                /* the import wizard already mapped every file to its subject, study, and series */
                ParseUniversalUpload(uploadRowID, uploadstagingpath, q.value("upload_manifestpath").toString(), q.value("upload_destprojectid").toInt(), q.value("upload_autoarchive").toBool());
            }
            else if (upload_type == "bids") {
                /* there should only be one BIDS dataset in this directory */
                bids::Reader reader;
                bids::BidsDataset dataset;
                QString err;
                if (!reader.readDataset(uploadstagingpath, dataset, err)) {
                    n->Log(QString("Error reading BIDS directory [%1].  Error message [%2]").arg(uploadstagingpath).arg(err));
                }
                else {
                    //n->Log(bids::PrintDataset(dataset));
                    if (ArchiveUploadedBIDS(dataset, uploadRowID)) {
                        n->Log(QString("Successful archived BIDS directory [%1]").arg(uploadstagingpath));
                        /* remove the staging directory now that the data has been archived (left in place on error for debugging) */
                        QString delmsg;
                        if (SafeDeletePath(uploadstagingpath, n->cfg["uploadstagingdir"], delmsg))
                            n->Log(QString("Removed BIDS staging directory [%1]").arg(uploadstagingpath));
                        else
                            n->Log(QString("Error removing BIDS staging directory [%1]: %2").arg(uploadstagingpath).arg(delmsg));
                    }
                    else {
                        n->Log(QString("Error archiving BIDS directory [%1]").arg(uploadstagingpath));
                    }
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
                    //bool csa = false;
                    //if (n->cfg["enablecsa"] == "1") csa = true;
                    //QString binpath = n->cfg["nidbdir"] + "/bin";
                    if (img->GetImageFileTags(f, tags, m)) {
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
                                io->AppendUploadLog("Unspecified subject criteria [" + upload_subjectcriteria + "]");

                            /* study matching criteria */
                            if (upload_studycriteria == "modalitystudydate")
                                study = tags["Modality"] + "|" + tags["StudyDateTime"];
                            else if (upload_studycriteria == "studyuid")
                                study = tags["StudyInstanceUID"];
                            else
                                io->AppendUploadLog("Unspecified study criteria [" + upload_studycriteria + "]");

                            /* series matching criteria */
                            if (upload_seriescriteria == "seriesnum")
                                series = tags["SeriesNumber"];
                            else if (upload_seriescriteria == "seriesdate")
                                series = tags["SeriesDateTime"];
                            else if (upload_seriescriteria == "seriesuid")
                                series = tags["SeriesInstanceUID"];
                            else
                                io->AppendUploadLog("Unspecified series criteria [" + upload_seriescriteria + "]");

                            /* store the file in the appropriate group */
                            fs[subject][study][series].append(f);
                            validFiles++;
                        }
                        else {
                            //io->AppendUploadLog("Valid file [" + f + "] but not the modality [" + upload_modality + "] we're looking for [" + tags["Modality"] + "]");
                            fs["nonmatch"]["nonmatch"]["nonmatch"].append(f);
                            nonMatchFiles++;
                        }
                    }
                    else {
                        /* the file is not readable */
                        fs["NiDBunreadable"]["NiDBunreadable"]["0"].append(f);
                        unreadableFiles++;
                        //io->AppendUploadLog("Unable to read file [" + f + "]");
                    }

                    i++;
                    tfiles++;

                    if (i >= 5000) {
                        double pct = (c > 0) ? (static_cast<double>(tfiles)/static_cast<double>(c)) * 100.0 : 0.0;
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
                        io->AppendUploadLog(QString("Found [%1] total files: [%2] valid, [%3] nonmatch, [%4] unreadable").arg(tfiles).arg(validFiles).arg(nonMatchFiles).arg(unreadableFiles));
                        io->AppendUploadLog(QString("fs.size() [%1] before being sent into UpdateParsedUploads()").arg(fs.size()));

                        ParseUploadedFiles(fs, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, uploadstagingpath, uploadRowID);
                        fs.clear();
                        i = 0;
                    }
                }

                double pct = (c > 0) ? (static_cast<double>(tfiles)/static_cast<double>(c)) * 100.0 : 0.0;
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
                io->AppendUploadLog(QString("Found [%1] total files: [%2] valid, [%3] nonmatch, [%4] unreadable").arg(tfiles).arg(validFiles).arg(nonMatchFiles).arg(unreadableFiles));
                io->AppendUploadLog(QString("fs.size() [%1] before being sent into UpdateParsedUploads()").arg(fs.size()));

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
        q.first();
        options.dataPath = q.value("upload_datapath").toString();
        options.modality = q.value("upload_modality").toString();
        options.patientID = q.value("upload_patientid").toString();
        options.projectRowID = q.value("upload_destprojectid").toInt();
        options.seriesMatchCriteria = q.value("upload_seriescriteria").toString();
        options.source = q.value("upload_source").toString();
        options.studyMatchCriteria = q.value("upload_studycriteria").toString();
        options.subjectMatchCriteria = q.value("upload_subjectcriteria").toString();
        options.type = q.value("upload_type").toString();
        options.isValid = true;
    }
    else {
        options.isValid = false;
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

    Q_UNUSED(uploadstagingpath)

    io->AppendUploadLog(QString("Processing [%1] subjects").arg(fs.size()));

    /* ---------- iterate through the subjects ---------- */
    for(QMap<QString, QMap<QString, QMap<QString, QStringList> > >::iterator a = fs.begin(); a != fs.end(); ++a) {
        QString subject = a.key();

        io->AppendUploadLog(QString("Processing subject [%1]").arg(subject));

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

            io->AppendUploadLog(QString("Processing study [%1]").arg(study));

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
                io->AppendUploadLog(QString("Processing series [%1]").arg(series));

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
                qint64 numfiles = files.size();
                //io->AppendUploadLog(QString("numfiles [%1]   numfiles [%2]").arg(files.size()).arg(numfiles));
                seriesid = InsertOrUpdateParsedSeries(-1, upload_seriescriteria, studyid, SeriesDateTime, SeriesNumber, SeriesInstanceUID, files, numfiles, "", "", "", "", "", "", 0, 0, m);

                /* remove the prefix for the files */
                //QStringList filesNoPrefix;
                //for (int ii=0; ii<files.size(); ii++) {
                //    QString str = files[ii];
                //    str.replace(str.indexOf(uploadstagingpath), uploadstagingpath.size(), "");
                //    filesNoPrefix.append(str);
                //}

                /* we've arrived at a series, so let's put it into the database */
                /* get tags from first file in the list to populate the subject/study/series info not included in the criteria matching */

                /* if subject and study are unreadable, put those files into the appropriate bin */
                QHash<QString, QString> tags;
                QString m;
                //bool csa = false;
                //if (n->cfg["enablecsa"] == "1") csa = true;
                //QString binpath = n->cfg["nidbdir"] + "/bin";
                img->GetImageFileTags(files[0], tags, m);

                //QSqlQuery q3;

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
bool moduleUpload::ParseUploadedSquirrel(squirrel *sqrl, int uploadRowID) {
    n->Log(sqrl->PrintPackage());

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
                //n->Log(series.PrintSeries());
                n->Log(QString("Found series [%1]").arg(series.SeriesNumber));
                n->Log(QString("squirrelSeries fileCount [%1] files [%2]").arg(series.FileCount).arg(series.files.join(", ")));

                qint64 numfiles = series.FileCount;
                qint64 seriesRowID = InsertOrUpdateParsedSeries(-1, "seriesnum", studyRowID, series.DateTime.toString("yyyy-MM-dd hh:mm:ss"), series.SeriesNumber, series.SeriesUID, series.files, numfiles, series.Description, series.Protocol, "", "", "", "", 0, 0, m);
                InsertOrUpdateParsedSeries(seriesRowID, "seriesnum", studyRowID, series.DateTime.toString("yyyy-MM-dd hh:mm:ss"), series.SeriesNumber, series.SeriesUID, series.files, numfiles, series.Description, series.Protocol, "", "", "", "", 0, 0, m);
            }
        }
    }

    /* update the status */
    SetUploadStatus(uploadRowID, "parsingcomplete", 100);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ArchiveUploadedBIDS ---------------------------- */
/* ---------------------------------------------------------- */
bool moduleUpload::ArchiveUploadedBIDS(bids::BidsDataset &dataset, int uploadRowID) {

    /* get the upload info */
    UploadOptions upload = GetUploadOptions(uploadRowID);

    /* get BIDS package information */
    QString bidsRootPath = dataset.rootPath;
    QString bidsName = dataset.name;
    QString bidsVersion = dataset.bidsVersion;
    //QString bidsDesc = dataset->datasetDescription; /* full QJsonObject */

    /* files in the root BIDS directory */
    for (const bids::FileRecord &f : dataset.topLevelFiles) {
        QString file = f.absolutePath;
    }

    /* iterate over participants.tsv */
    //QStringList participantColumns = dataset.participantColumns;
    //for (const QVariantMap &row : dataset.participantRows) {
    //    QString bidsSubjectID = row.value("participant_id").toString();
    //}

    /* iterate through the BIDS subject data --> map to the NiDB subject */
    int i=0;
    int subjectCount = dataset.subjects.count();
    for (auto it = dataset.subjects.constBegin(); it != dataset.subjects.constEnd(); ++it) {

        const bids::SubjectRecord &bidsSubj = it.value();

        /* subject ID */
        const QString &bidsSubjectID = bidsSubj.id; /* base subject ID, without 'sub-' */
        n->Log(QString("Working on BIDS subject %1 of %2: id [%3]").arg(i+1).arg(subjectCount).arg(bidsSubjectID));

        /* demographics */
        double subjectAge = 0.0;
        QString subjectSex = "U";
        if (!bidsSubj.participantRow.isEmpty()) {
            subjectAge = bidsSubj.participantRow.value("age").toDouble();
            subjectSex = bids::NormalizeBidsSex(bidsSubj.participantRow.value("sex").toString());
        }

        /* handle loose files in sub-XX/ without acquisitions */
        for (const bids::FileRecord &f : bidsSubj.looseFiles) {
            QString filePath = f.absolutePath;
        }

        int subjectRowID;
        QString subjectUID;
        int enrollmentRowID;

        /* find existing subject that has this bidsSubjectID AND is enrolled in this project */
        /* Note: BIDS subject IDs will be extremely common, 01, 02, etc
         * so we must only match a subject that is already enrolled in this project */
        subject nidbSubj(n);
        nidbSubj.searchMethod = SubjectSearchMethod::AltUid;
        nidbSubj.searchAltUID = bidsSubjectID;
        nidbSubj.searchProjectRowID = upload.projectRowID;
        if (nidbSubj.Load()) {
            /* subject was found */
            subjectRowID = nidbSubj.GetSubjectRowID();
            subjectUID = nidbSubj.uid;
        }
        else {
            n->Log(QString("Subject not found by AltUID [%1] within this project [%2]. Subject could not be found").arg(bidsSubjectID).arg(upload.projectRowID));

            /* create the subject and enrollment */
            if (io->CreateSubject(bidsSubjectID, bidsSubjectID, "0000-00-00", subjectSex, 0.0, 0.0, subjectRowID, subjectUID)) {
                n->Log(QString("Created subject with UID [%1] and subjectRowID [%2]").arg(subjectUID).arg(subjectRowID));
            }
            else {
                n->Log(QString("Error creating subject. Skipping this subject [%1]").arg(bidsSubjectID));
                continue;
            }
        }

        /* enroll the subject in the project */
        if (io->GetOrCreateEnrollment(subjectRowID, upload.projectRowID, enrollmentRowID)) {
            n->Log(QString("Created enrollment [subjectRowID %1,  projectRowID %2,  enrollmentRowID %3]").arg(subjectRowID).arg(upload.projectRowID).arg(enrollmentRowID));
        }
        else {
            n->Log(QString("Error creating enrollment [%1, %2, %3]").arg(subjectRowID).arg(upload.projectRowID).arg(enrollmentRowID));
            continue;
        }

        /* update the altUIDs */
        io->SetAlternateIDs(subjectRowID, enrollmentRowID, QStringList(bidsSubjectID));

        /* ----- map the BIDS session(s) --> NiDB study(s) ----- */

        /* session acquisitions - has 'ses-XX' directory */
        for (auto sit = bidsSubj.sessions.constBegin(); sit != bidsSubj.sessions.constEnd(); ++sit) {
            const bids::SessionRecord &sess = sit.value();
            QString sessionLabel = sess.id;

            /* determine the acquisition datatypes */
            QStringList bidsDatatypes;
            for (const bids::Acquisition &acq : sess.acquisitions) {
                bidsDatatypes.append(acq.datatype);
            }
            QStringList modalities = bids::MapBidsDatatypeToModality(bidsDatatypes);

            if (modalities.size() > 1) {
                n->Log(QString("session contains more than one modality [%1]. Skipping import of this session.").arg(bidsDatatypes.join(",")));
                continue;
            }
            else if (modalities.size() == 0) {
                n->Log(QString("session contained no matching modality [%1]. Skipping import of this session.").arg(bidsDatatypes.join(",")));
                continue;
            }

            QString modality = modalities[0];
            /* create study */
            int studyRowID;
            int studyNum;
            if (io->CreateStudy(subjectRowID, enrollmentRowID, CreateCurrentDateTime(), "",modality, bidsSubjectID, subjectAge, 0.0, 0.0, sessionLabel, "BIDS", "", "BIDS", "BIDS", "BIDS", studyRowID, studyNum )) {
                //n->Log("Checkpoint A");
                QSqlQuery q;
                q.prepare("update studies set study_type = :visit where study_id = :studyid");
                q.bindValue(":visit", sessionLabel);
                q.bindValue(":studyid", studyRowID);
                //n->Log("Checkpoint B");
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                //n->Log("Checkpoint C");
                n->Log(QString("Created study [bids %1] for subject UID [%2]").arg(sessionLabel).arg(subjectUID));
            }
            else {
                n->Log(QString("Error creating study [bids %1] for subject UID [%2]").arg(sessionLabel).arg(subjectUID));
                continue;
            }

            //QList scans = sess.scansTable.rows; /* parsed scans.tsv */
            int nextSeries = 1;
            for (const bids::Acquisition &acq : sess.acquisitions) {
                QString err;
                if (ImportBidsAcquisition(subjectRowID, studyRowID, nextSeries, upload.projectRowID, acq, err)) {
                    n->Log(QString("Inserted series [%1]").arg(nextSeries));
                    nextSeries++;
                }
                else {
                    n->Log(QString("Error inserting series [%1]").arg(nextSeries));
                }
            }
        }

        /* --- store all sessionless acquisitions in 1 study --- */

        if (bidsSubj.acquisitionsWithoutSession.size() > 0) {
            /* determine the acquisition datatypes */
            QStringList bidsDatatypes;
            for (const bids::Acquisition &acq : bidsSubj.acquisitionsWithoutSession) {
                bidsDatatypes.append(acq.datatype);
            }
            QStringList modalities = bids::MapBidsDatatypeToModality(bidsDatatypes);

            if (modalities.size() > 1) {
                n->Log(QString("sessionless acquisitions contains more than one modality [%1]. Skipping import of this acquisition.").arg(bidsDatatypes.join(",")));
                continue;
            }
            else if (modalities.size() == 0) {
                n->Log(QString("sessionless acquisitions contained no matching modality [%1]. Skipping import of this acquisition.").arg(bidsDatatypes.join(",")));
                continue;
            }

            QString modality = modalities[0];
            /* create study */
            int studyRowID;
            int studyNum;
            if (io->CreateStudy(subjectRowID, enrollmentRowID, CreateCurrentDateTime(), "",modality, bidsSubjectID, subjectAge, 0.0, 0.0, "BIDS sessionless acquisitions", "BIDS", "", "BIDS", "BIDS", "BIDS", studyRowID, studyNum )) {
                QSqlQuery q;
                q.prepare("update studies set study_type = :visit where study_id = :studyid");
                q.bindValue(":visit", "bids no-ses");
                q.bindValue(":studyid", studyRowID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

                n->Log(QString("Created sessionless study [%1] for subject UID [%2]").arg(studyNum).arg(subjectUID));
            }
            else {
                n->Log(QString("Error creating sessionless study for subject UID [%1]").arg(subjectUID));
                continue;
            }

            /* sessionless acquisitions - no 'ses-XX' directories */
            int nextSeries = 1;
            for (const bids::Acquisition &acq : bidsSubj.acquisitionsWithoutSession) {
                QString err;
                if (ImportBidsAcquisition(subjectRowID, studyRowID, nextSeries, upload.projectRowID, acq, err)) {
                    n->Log(QString("Inserted series [%1]").arg(nextSeries));
                    nextSeries++;
                }
                else {
                    n->Log(QString("Error inserting series [%1]").arg(nextSeries));
                }
            }
        }

        i++;

        double pct = ((double)i/(double)subjectCount)*100.0;
        SetUploadStatus(uploadRowID, "archiving", pct);
    }

    /* update the status */
    SetUploadStatus(uploadRowID, "archivecomplete", 100);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ImportBidsAcquisition -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief This function imports a BIDS acquisition into an NiDB series
 * @param subjectRowID NiDB database subjectRowID
 * @param studyRowID NiDB database studyRowID
 * @param acq BIDS acquisition object
 * @param err Any errors reported during import
 * @return true if successful, false otherwise
 */
bool moduleUpload::ImportBidsAcquisition(int subjectRowID, int studyRowID, int seriesNumber, int projectRowID, const bids::Acquisition &acq, QString &err) {
    //QString key = acq.key; /* 'sub-01_ses-1_task-rest_run-1_bold' */
    QString datatype = acq.datatype; /* anat, func, dwi, eeg ... */
    QString suffix = acq.suffix; /* T1w, bold, dwi ... */
    //QMap<QString, QString> = acq.entities;
    QString taskName = acq.entities.value("task");
    QString run = acq.entities.value("run");
    //QString acqName = acq.entities.value("acq");

    /* get full paths to the files */
    QStringList files;
    for (const bids::FileRecord &f : acq.files) {
        files.append(f.absolutePath);
    }

    /* get the DICOM-like tags from the JSON file - and fix missing tags */
    QHash<QString, QString> tags = acq.ResolvedMetadataAsMap();

    /* BIDS stores these timing fields in seconds, but NiDB's mr_series columns
       (series_tr/series_te/series_ti) are milliseconds (DICOM convention). Convert
       seconds -> ms here so the DB stays consistent with DICOM-imported series and
       exports round-trip correctly. */
    foreach (const QString &k, QStringList({ "RepetitionTime", "EchoTime", "InversionTime" })) {
        if (tags.contains(k) && (tags.value(k) != "")) {
            bool ok = false;
            double secs = tags.value(k).toDouble(&ok);
            if (ok)
                tags[k] = QString::number(secs * 1000.0);
        }
    }

    if (!tags.contains("SeriesDateTime")) { tags["SeriesDateTime"] = CreateCurrentDateTime(2); }
    if (!tags.contains("boldreps")) { tags["boldreps"] = "0"; }
    if (!tags.contains("numfiles")) { tags["numfiles"] = QString("%1").arg(files.size()); }
    if (!tags.contains("PhaseEncodingDirectionPositive")) {
        /* check if the phase encoding moves in the positive 0->1 direction */
        if (tags.value("PhaseEncodingDirection").endsWith("-"))
            tags["PhaseEncodingDirectionPositive"] = "0";
        else
            tags["PhaseEncodingDirectionPositive"] = "1";
    }
    /* NiDB stores phaseencodedir from the tag InPlanePhaseEncodingDirection; BIDS provides it as InPlanePhaseEncodingDirectionDICOM, so copy it over when present */
    if (tags.contains("InPlanePhaseEncodingDirectionDICOM")) { tags["InPlanePhaseEncodingDirection"] = tags.value("InPlanePhaseEncodingDirectionDICOM"); }
    /* ArchiveNiftiSeries reads the series protocol from ProtocolName - default it to the series description if missing */
    if (!tags.contains("ProtocolName")) { tags["ProtocolName"] = tags.value("SeriesDescription"); }
    if ((tags.value("ProtocolName") == tags.value("SeriesDescription")) && (tags.contains("TaskName"))) { tags["SeriesDescription"] = tags.value("TaskName"); }

    /* attempt to get some image dimensions - it is possible for multiple .nii files to be in this directory so just get the first file */
    foreach (QString f, files) {
        if ((f.endsWith(".nii")) || (f.endsWith(".nii.gz"))) {
            QString fsl = QString("export FSLDIR=%1; source ${FSLDIR}/etc/fslconf/fsl.sh; export PATH=$PATH:%1/bin; ").arg(n->cfg["fsldir"]);
            QHash<QString,QString> hdr = NiftiInfo(f, fsl);
            if (!hdr.isEmpty()) {
                //qDebug() << hdr;
                /* NIfTI dim1 = X (columns), dim2 = Y (rows), matching DICOM Rows/Columns */
                tags["Rows"] = hdr.value("dim2");
                tags["Columns"] = hdr.value("dim1");
                tags["zsize"] = hdr.value("dim3");
                tags["boldreps"] = hdr.value("dim4");
                tags["pixelX"] = hdr.value("pixdim1");
                tags["pixelY"] = hdr.value("pixdim2");
                break;
            }
        }
    }

    if (io->ArchiveNiftiSeries(subjectRowID, studyRowID, -1, seriesNumber, tags, files)) {
        /* add the reverse BIDS mapping */
        QSqlQuery q;
        q.prepare("insert ignore into bids_mapping (project_id, protocolname, imagetype, modality, bidsentity, bidssuffix, bidsrun, bidsAutoNumberRuns, bidsIncludeAcquisition, bidstask, bidspedirection) values (:projectid, :seriesdesc, :imagetype, :modality, :bidsentity, :bidssuffix, :bidsrun, :bidsautonumberruns, :bidsincludeacquisition, :bidstask, :bidspedirection) on duplicate key update bidsentity = :bidsentity, bidssuffix = :bidssuffix, bidsrun = :bidsrun, bidsAutoNumberRuns = :bidsautonumberruns, bidsIncludeAcquisition = :bidsincludeacquisition, bidstask = :bidstask, bidspedirection = :bidspedirection");
        q.bindValue(":projectid", projectRowID);
        q.bindValue(":seriesdesc", tags.value("SeriesDescription"));
        q.bindValue(":imagetype", tags.value("ImageType"));
        /* the BIDS JSON may not carry a Modality field, so derive it from the datatype
           (falling back to the tag if present) - this must match the modality used by
           GetBIDSMapping() on export or the reverse mapping won't be found */
        QStringList mods = bids::MapBidsDatatypeToModality(QStringList() << datatype);
        q.bindValue(":modality", mods.isEmpty() ? tags.value("Modality") : mods.first());
        q.bindValue(":bidsentity", datatype);
        q.bindValue(":bidssuffix", suffix);
        q.bindValue(":bidsrun", run.toInt());
        /* the exact run is stored in bidsrun, so no auto-numbering is needed on export */
        q.bindValue(":bidsautonumberruns", 0);
        q.bindValue(":bidsincludeacquisition", acq.entities.value("acq").isEmpty() ? 0 : 1);
        q.bindValue(":bidstask", taskName);
        /* PE direction comes from the BIDS 'dir' entity (eg dir-AP); empty for non-fmap */
        q.bindValue(":bidspedirection", acq.entities.value("dir"));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        return true;
    }
    else {
        err = "ArchiveNiftiSeries() failed. Check logs";
        return false;
    }
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
    q.prepare("select * from uploads where upload_status = 'queueforarchive' and upload_type <> 'squirrel' and upload_type <> 'universal'");
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

            io->AppendUploadLog(QString("Beginning archiving of upload [%1] with upload_destprojectid of [%2]").arg(uploadRowID).arg(upload_destprojectid));

            /* set status to archiving */
            SetUploadStatus(uploadRowID, "archiving", 0.0);

            /* get list of series which should be archived from this upload */
            QSqlQuery q2;
            q2.prepare("select * from upload_series a left join upload_studies b on a.uploadstudy_id = b.uploadstudy_id left join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where a.uploadseries_status = 'import' and c.upload_id = :uploadid");
            q2.bindValue(":uploadid", uploadRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
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

                    //performanceMetric perf;
                    /* insert the series */
                    io->ArchiveDICOMSeries(-1, matchingsubjectid, matchingstudyid, matchingseriesid, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, upload_destprojectid, upload_patientid, -1, "", "Uploaded to NiDB", uploadseries_filelist);

                    i++;
                    double pct = static_cast<double>(i)/static_cast<double>(numSeries) * 100.0;
                    SetUploadStatus(uploadRowID, "archiving", pct);
                }

                io->AppendUploadLog(QString("Completed archiving of upload [%1]").arg(uploadRowID));
            }
            else {
                error = true;
                io->AppendUploadLog(QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }

            if (error) {
                SetUploadStatus(uploadRowID, "archiveerror", -1.0);
            }
            else {
                SetUploadStatus(uploadRowID, "archivecomplete", 100.0);
                /* delete all of the source data and mark status as 'archivecomplete' */
                QString m;
                if (SafeDeletePath(upload_stagingpath, n->cfg["uploadstagingdir"], m))
                    io->AppendUploadLog(QString("Removed upload staging directory [%1]").arg(upload_stagingpath));
                else
                    io->AppendUploadLog(QString("Error: No series found for upload [%1]").arg(uploadRowID));
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

            io->AppendUploadLog(QString("Beginning archiving of upload [%1] with upload_destprojectid of [%2]").arg(uploadRowID).arg(upload_destprojectid));

            /* set status to archiving */
            SetUploadStatus(uploadRowID, "archiving", 0.0);

            /* unzip the squirrel package */
            QString f;
            QString m;
            if (!NiDBFindFirstFile(upload_stagingpath, "*.sqrl", f, m)) {
                n->Log("Unable to find any squirrel files in path [" + upload_stagingpath + "]", __FUNCTION__);
                continue;
            }
            QString tmppath;
            auto sqrl = std::make_unique<squirrel>();
            //squirrel *sqrl = new squirrel();
            sqrl->SetCommandLineExecution(false);
            sqrl->SetSystemTempDir(n->cfg["tmpdir"]);
            sqrl->SetPackagePath(f);
            sqrl->SetQuickRead(false); /* it will take longer to read, but we will want the contents of all the params.json files */
            if (sqrl->Read()) {

                n->Log("Successfully read squirrel package [" + f + "]", __FUNCTION__);
                sqrl->PrintPackage();

                n->Log(sqrl->GetLogBuffer());

                /* extract the file to a temp directory */
                tmppath = n->cfg["tmpdir"] + "/" + GenerateRandomString(20);
                if (!MakePath(tmppath,m)) {
                    n->Log("Error creating temp directory [" + tmppath + "] with error [" + m + "]", __FUNCTION__);
                    //delete sqrl;
                    continue;
                }
                else {
                    n->Log("Created temp directory [" + tmppath + "]. Now extracting squirrel package...", __FUNCTION__);
                }

                m = "";
                if (sqrl->Extract(tmppath, m)) {
                    n->Log("Successfuly extracted squirrel package [" + f + "] to directory [" + tmppath + "] with message [" + m + "]", __FUNCTION__);
                    qint64 c(0), b(0);
                    GetDirSizeAndFileCount(tmppath, c, b);
                    n->Log(QString("squirrelPath is [%1]. It contains [%2] files with total size [%3] bytes").arg(tmppath).arg(c).arg(b));
                    QString systemstring = "ls " + tmppath;
                    n->Log(SystemCommand(systemstring));
                    systemstring = "ls -l " + tmppath;
                    n->Log(SystemCommand(systemstring));
                    systemstring = "cd " + tmppath + "/data; ls -l */*/*";
                    n->Log(SystemCommand(systemstring));
                }
                else {
                    n->Log("Error extracting squirrel package [" + f + "] to directory [" + tmppath + "] with message [" + m + "]", __FUNCTION__);
                    //delete sqrl;
                    continue;
                }
            }
            else {
                //delete sqrl;
                continue;
            }

            n->Log(sqrl->GetLogBuffer());

            /* get list of series which should be archived from this upload */
            QSqlQuery q2;
            q2.prepare("select * from upload_series a left join upload_studies b on a.uploadstudy_id = b.uploadstudy_id left join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where a.uploadseries_status = 'import' and c.upload_id = :uploadid");
            q2.bindValue(":uploadid", uploadRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            int numSeries = q2.size();
            int i(0);
            if (numSeries > 0) {
                while (q2.next()) {

                    /* check if this module should be running */
                    n->ModuleRunningCheckIn();
                    if (!n->ModuleCheckIfActive()) {
                        n->Log("Module is now inactive, stopping the module");
                        //delete sqrl;
                        return false;
                    }

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
                    if (io->GetStudy("modalitystudydate", studyRowID, enrollmentRowID, uploadStudyDate, uploadStudyModality, sqrlStudy.StudyUID, studyRowID, localStudyNumber)) {
                        n->Log(QString("Found existing study.  number [%1], studyRowID [%2]").arg(subjectUID).arg(subjectRowID), __FUNCTION__);
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
                    n->Log(QString("series.params.size [%1]").arg(sqrlSeries.params.size()), __FUNCTION__);
                    tags["AcquisitionMatrix"] = sqrlSeries.params["AcquisitionMatrix"];
                    tags["Columns"] = sqrlSeries.params["Columns"];
                    tags["EchoTime"] = sqrlSeries.params["EchoTime"];
                    tags["FlipAngle"] = sqrlSeries.params["FlipAngle"];
                    tags["ImageComments"] = sqrlSeries.params["ImageComments"];
                    tags["ImageType"] = sqrlSeries.params["ImageType"];
                    tags["InPlanePhaseEncodingDirection"] = sqrlSeries.params["InPlanePhaseEncodingDirection"];
                    tags["InversionTime"] = sqrlSeries.params["InversionTime"];
                    tags["MagneticFieldStrength"] = sqrlSeries.params["MagneticFieldStrength"];
                    tags["PercentPhaseFieldOfView"] = sqrlSeries.params["PercentPhaseFieldOfView"];
                    tags["PercentSampling"] = sqrlSeries.params["PercentSampling"];
                    tags["PhaseEncodeAngle"] = sqrlSeries.params["PhaseEncodeAngle"];
                    tags["PhaseEncodingDirectionPositive"] = sqrlSeries.params["PhaseEncodingDirectionPositive"];
                    tags["PixelBandwidth"] = sqrlSeries.params["PixelBandwidth"];
                    tags["ProtocolName"] = sqrlSeries.params["ProtocolName"];
                    tags["RepetitionTime"] = sqrlSeries.params["RepetitionTime"];
                    tags["Rows"] = sqrlSeries.params["Rows"];
                    tags["SequenceName"] = sqrlSeries.params["SequenceName"];
                    tags["SeriesDateTime"] = sqrlSeries.DateTime.toString("yyyy-MM-dd hh:mm:ss");
                    tags["SeriesDescription"] = sqrlSeries.Description;
                    tags["SliceThickness"] = sqrlSeries.params["SliceThickness"];
                    tags["SliceThickness"] = sqrlSeries.params["SliceThickness"];
                    tags["SpacingBetweenSlices"] = sqrlSeries.params["SpacingBetweenSlices"];
                    tags["boldreps"] = QString("%1").arg(sqrlSeries.FileCount);
                    tags["pixelX"] = sqrlSeries.params["pixelX"];
                    tags["pixelY"] = sqrlSeries.params["pixelY"];
                    tags["zsize"] = sqrlSeries.params["zsize"];

                    /* insert the series */
                    if ((sqrl->DataFormat.startsWith("nifti", Qt::CaseInsensitive)) && (sqrlStudy.Modality.toLower() == "mr")) {
                        n->Log("squirrel data format is [" + sqrl->DataFormat + "], calling ArchiveNiftiSeries()");
                        io->ArchiveNiftiSeries(subjectRowID, studyRowID, seriesRowID, uploadSeriesNumber, tags, files);
                    }
                    else if ((sqrl->DataFormat == "dicom") || (sqrl->DataFormat == "orig") || (sqrl->DataFormat == "anon") || (sqrl->DataFormat == "anonfull")) {
                        n->Log("squirrel data format is [" + sqrl->DataFormat + "], calling ArchiveDICOMSeries()");
                        /* TODO - this section is not implemented yet */
                        //io->ArchiveDICOMSeries();
                    }
                    else {
                        n->Log("Currently unsopported: squirrel data format is [" + sqrl->DataFormat + "] and modality is [" + sqrlStudy.Modality + "]");
                    }

                    i++;
                    double pct = static_cast<double>(i)/static_cast<double>(numSeries) * 100.0;
                    SetUploadStatus(uploadRowID, "archiving", pct);

                    n->Log(sqrl->GetLogBuffer());
                }

                io->AppendUploadLog(QString("Completed archiving of upload [%1]").arg(uploadRowID));
            }
            else {
                error = true;
                io->AppendUploadLog(QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }

            if (error) {
                SetUploadStatus(uploadRowID, "archiveerror", -1.0);
            }
            else {
                SetUploadStatus(uploadRowID, "archivecomplete", 100.0);
                /* delete all of the source data and mark status as 'archivecomplete' */
                QString m;
                if (SafeDeletePath(upload_stagingpath, n->cfg["uploadstagingdir"], m))
                    io->AppendUploadLog(QString("Removed upload staging directory [%1]").arg(upload_stagingpath));
                else
                    io->AppendUploadLog(QString("Error: No series found for upload [%1]").arg(uploadRowID));
            }

            n->Log(sqrl->GetLogBuffer());
            //delete sqrl;
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
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
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
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
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
            q.bindValue(":patientid", PatientID);
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
int moduleUpload::InsertOrUpdateParsedSeries(qint64 parsedSeriesRowID, QString upload_seriescriteria, qint64 studyRowID, QString SeriesDateTime, qint64 SeriesNumber, QString SeriesInstanceUID, QStringList &files, qint64 &numfiles, QString SeriesDescription, QString ProtocolName, QString RepetitionTime, QString EchoTime, QString SpacingBetweenSlices, QString SliceThickness, int Rows, int Columns, QString &msg) {
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
            /* check if the seriesid exists ... */
            q.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_num = :seriesnum");
            q.bindValue(":studyid", studyRowID);
            q.bindValue(":seriesnum", SeriesNumber);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSeriesRowID = q.value("uploadseries_id").toInt();

                int databaseNumFiles = q.value("uploadseries_numfiles").toInt();
                io->AppendUploadLog(QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                io->AppendUploadLog(QString("2) numfiles, before appending [%1]").arg(numfiles));
                numfiles += databaseNumFiles;
                io->AppendUploadLog(QString("3) numfiles, after appending [%1]").arg(numfiles));

                QStringList databaseFiles = q.value("uploadseries_filelist").toString().split(",");
                io->AppendUploadLog(QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                io->AppendUploadLog(QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                files.append(databaseFiles);
                io->AppendUploadLog(QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
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
                io->AppendUploadLog(QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                io->AppendUploadLog(QString("2) numfiles, before appending [%1]").arg(numfiles));
                numfiles += databaseNumFiles;
                io->AppendUploadLog(QString("3) numfiles, after appending [%1]").arg(numfiles));

                QStringList databaseFiles = q.value("uploadseries_filelist").toString().split(",");
                io->AppendUploadLog(QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                io->AppendUploadLog(QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                files.append(databaseFiles);
                io->AppendUploadLog(QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
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

            //QSqlQuery q;
            /* check if the seriesid already exists ... */
            q.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_instanceuid = :seriesinstanceuid");
            q.bindValue(":studyid", studyRowID);
            q.bindValue(":seriesinstanceuid", SeriesInstanceUID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                parsedSeriesRowID = q.value("uploadseries_id").toInt();
                int databaseNumFiles = q.value("uploadseries_numfiles").toInt();
                io->AppendUploadLog(QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                io->AppendUploadLog(QString("2) numfiles, before appending [%1]").arg(numfiles));
                numfiles += databaseNumFiles;
                io->AppendUploadLog(QString("3) numfiles, after appending [%1]").arg(numfiles));

                QStringList databaseFiles = q.value("uploadseries_filelist").toString().split(",");
                io->AppendUploadLog(QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                io->AppendUploadLog(QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                files.append(databaseFiles);
                io->AppendUploadLog(QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
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


/* ---------------------------------------------------------- */
/* --------- WriteManifestFileList -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Write the paths in an import wizard manifest to a file, one per line, for `rsync --files-from`
 * @param manifestpath The manifest JSON file
 * @param listfile The file to write
 * @param msg [returned] What went wrong
 * @return true if written
 */
bool moduleUpload::WriteManifestFileList(QString manifestpath, QString listfile, QString &msg) {
    QFile mf(manifestpath);
    if (!mf.open(QIODevice::ReadOnly)) { msg = QString("Unable to read the manifest [%1]").arg(manifestpath); return false; }
    QJsonObject manifest = QJsonDocument::fromJson(mf.readAll()).object();
    mf.close();

    QFile lf(listfile);
    if (!lf.open(QIODevice::WriteOnly | QIODevice::Truncate)) { msg = QString("Unable to write the file list [%1]").arg(listfile); return false; }
    QTextStream out(&lf);
    int count(0);
    for (const QJsonValue &subj : manifest.value("subjects").toArray())
        for (const QJsonValue &st : subj.toObject().value("studies").toArray())
            for (const QJsonValue &se : st.toObject().value("series").toArray())
                for (const QJsonValue &p : se.toObject().value("paths").toArray()) {
                    out << p.toArray()[0].toString() << "\n";
                    count++;
                }
    lf.close();
    if (count == 0) { msg = QString("The manifest [%1] lists no files").arg(manifestpath); return false; }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- SeriesTableColumns ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief The columns of a modality's series table, which differ between modalities (e.g. mr_series has `numfiles`
 * and `data_type`, several tables have no `series_status`). Read from information_schema and cached
 * @param modality Modality code, e.g. `EEG`
 * @return Column names, or an empty list if the modality has no `<modality>_series` table
 */
QStringList moduleUpload::SeriesTableColumns(QString modality) {
    QString table = modality.toLower() + "_series";
    if (seriesColumns.contains(table))
        return seriesColumns[table];

    QStringList cols;
    QSqlQuery q;
    q.prepare("select column_name from information_schema.columns where table_schema = database() and table_name = :table");
    q.bindValue(":table", table);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next())
        cols.append(q.value(0).toString().toLower());
    seriesColumns[table] = cols;
    return cols;
}


/* ---------------------------------------------------------- */
/* --------- ParseUniversalUpload --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Load an import wizard upload into upload_subjects/upload_studies/upload_series from its manifest (the mapping the
 * user previewed, resolved to file paths; see importupload_functions.php). Each subject and study is matched against NiDB,
 * and a series that can't be archived as it is gets an `uploadseries_problem`. Then the upload is queued for archiving if
 * `upload_autoarchive` is set and nothing has a problem, otherwise it waits for the user's review
 * @param uploadRowID uploadRowID
 * @param stagingpath The upload's staging directory, holding the files at their manifest paths
 * @param manifestpath The manifest JSON file
 * @param projectRowID The destination project
 * @param autoarchive Queue for archiving straight away if nothing needs review
 * @return true if the manifest was loaded
 */
bool moduleUpload::ParseUniversalUpload(int uploadRowID, QString stagingpath, QString manifestpath, int projectRowID, bool autoarchive) {
    QSqlQuery q;

    QFile mf(manifestpath);
    if (!mf.open(QIODevice::ReadOnly)) {
        io->AppendUploadLog(QString("Error: unable to read the manifest [%1]").arg(manifestpath));
        SetUploadStatus(uploadRowID, "parsingerror");
        return false;
    }
    QJsonParseError jerr;
    QJsonDocument doc = QJsonDocument::fromJson(mf.readAll(), &jerr);
    mf.close();
    QJsonObject manifest = doc.object();
    if ((jerr.error != QJsonParseError::NoError) || (manifest.value("upload_id").toInt() != uploadRowID)) {
        io->AppendUploadLog(QString("Error: the manifest [%1] is not valid for this upload [%2]").arg(manifestpath).arg(jerr.errorString()));
        SetUploadStatus(uploadRowID, "parsingerror");
        return false;
    }

    /* start from nothing, so loading the same upload again can't duplicate it */
    q.prepare("delete c from upload_series c join upload_studies b on c.uploadstudy_id = b.uploadstudy_id join upload_subjects a on b.uploadsubject_id = a.uploadsubject_id where a.upload_id = :uploadid");
    q.bindValue(":uploadid", uploadRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.prepare("delete b from upload_studies b join upload_subjects a on b.uploadsubject_id = a.uploadsubject_id where a.upload_id = :uploadid");
    q.bindValue(":uploadid", uploadRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.prepare("delete from upload_subjects where upload_id = :uploadid");
    q.bindValue(":uploadid", uploadRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    QJsonArray subjects = manifest.value("subjects").toArray();
    int numSeries(0), numProblems(0), numFiles(0);
    for (int si = 0; si < subjects.size(); si++) {
        QJsonObject subj = subjects[si].toObject();
        QString patientID = subj.value("id").toString().trimmed();

        /* check if this module should be running, and if the upload was changed by someone else */
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return false; }
        if (GetUploadStatus(uploadRowID) != "parsing") return false;

        /* the subject: by UID or alternate UID, like the DICOM import */
        QList<int> matches;
        q.prepare("select distinct a.subject_id from subjects a left join subject_altuid b on a.subject_id = b.subject_id where a.isactive = 1 and (a.uid = :id or b.altuid = :id)");
        q.bindValue(":id", patientID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next())
            matches.append(q.value(0).toInt());
        QString subjectProblem;
        if (matches.size() > 1)
            subjectProblem = QString("The subject ID matches %1 subjects in NiDB").arg(matches.size());
        int matchingSubjectRowID = (matches.size() == 1) ? matches[0] : -1;

        int enrollmentRowID(-1);
        if (matchingSubjectRowID > 0) {
            q.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
            q.bindValue(":subjectid", matchingSubjectRowID);
            q.bindValue(":projectid", projectRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.first())
                enrollmentRowID = q.value(0).toInt();
        }

        q.prepare("insert into upload_subjects (upload_id, uploadsubject_patientid, matchingsubjectid) values (:uploadid, :patientid, :matchingsubjectid)");
        q.bindValue(":uploadid", uploadRowID);
        q.bindValue(":patientid", patientID);
        q.bindValue(":matchingsubjectid", (matchingSubjectRowID > 0) ? QVariant(matchingSubjectRowID) : QVariant(QMetaType::fromType<int>()));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        int uploadSubjectRowID = q.lastInsertId().toInt();

        QJsonArray studies = subj.value("studies").toArray();
        for (int sti = 0; sti < studies.size(); sti++) {
            QJsonObject st = studies[sti].toObject();
            QString modality = st.value("modality").toString();
            QString visit = st.value("visit").toString().trimmed();
            QString datesource = st.value("datesource").toString();
            QJsonArray seriesList = st.value("series").toArray();
            QString studyProblem = subjectProblem;

            QStringList cols = SeriesTableColumns(modality);
            if ((studyProblem == "") && cols.isEmpty())
                studyProblem = QString("NiDB has no series table for modality [%1]").arg(modality);

            /* the study date: from the manifest, or from the first file's headers */
            QString studyDateTime;
            if (datesource == "header") {
                QString first;
                if (!seriesList.isEmpty()) {
                    QJsonArray p0 = seriesList[0].toObject().value("paths").toArray();
                    if (!p0.isEmpty()) first = stagingpath + "/" + p0[0].toArray()[0].toString();
                }
                QHash<QString, QString> tags;
                QString m;
                QDateTime dt;
                if ((first != "") && QFileInfo(first).isFile() && img->GetImageFileTags(first, tags, m)) {
                    dt = QDateTime::fromString(tags["StudyDateTime"].trimmed(), "yyyy-MM-dd HH:mm:ss");
                    if (!dt.isValid())
                        dt = QDateTime(QDate::fromString(tags["StudyDate"].trimmed().left(10), "yyyy-MM-dd"), QTime(0, 0));
                }
                if (dt.isValid())
                    studyDateTime = dt.toString("yyyy-MM-dd HH:mm:ss");
                else if (studyProblem == "")
                    studyProblem = "No study date in the file headers";
            }
            else {
                QDate d = QDate::fromString(st.value("date").toString(), "yyyy-MM-dd");
                if (d.isValid())
                    studyDateTime = d.toString("yyyy-MM-dd") + " 00:00:00";
                else if (studyProblem == "")
                    studyProblem = "The study date is not valid";
            }

            /* an existing study of the enrolled subject: same modality and visit label, or with no label the same date */
            int matchingStudyRowID(-1);
            if ((enrollmentRowID > 0) && (studyDateTime != "")) {
                if (visit != "") {
                    q.prepare("select study_id from studies where enrollment_id = :enrollmentid and study_modality = :modality and study_type = :visit");
                    q.bindValue(":visit", visit);
                }
                else {
                    q.prepare("select study_id from studies where enrollment_id = :enrollmentid and study_modality = :modality and date(study_datetime) = :date");
                    q.bindValue(":date", studyDateTime.left(10));
                }
                q.bindValue(":enrollmentid", enrollmentRowID);
                q.bindValue(":modality", modality);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                if (q.size() == 1) {
                    q.first();
                    matchingStudyRowID = q.value(0).toInt();
                }
                else if ((q.size() > 1) && (studyProblem == ""))
                    studyProblem = QString("Matches %1 existing studies in NiDB").arg(q.size());
            }

            /* series numbers already used in that study */
            QSet<int> usedNumbers;
            if ((matchingStudyRowID > 0) && !cols.isEmpty()) {
                q.prepare(QString("select series_num from %1_series where study_id = :studyid").arg(modality.toLower()));
                q.bindValue(":studyid", matchingStudyRowID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                while (q.next())
                    usedNumbers.insert(q.value(0).toInt());
            }

            QString firstFiletype = seriesList.isEmpty() ? "" : seriesList[0].toObject().value("filetype").toString();
            q.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_desc, uploadstudy_date, uploadstudy_modality, uploadstudy_datatype, uploadstudy_visit, uploadstudy_datesource, matchingstudyid) values (:subjectid, :desc, :date, :modality, :datatype, :visit, :datesource, :matchingstudyid)");
            q.bindValue(":subjectid", uploadSubjectRowID);
            q.bindValue(":desc", visit);
            q.bindValue(":date", (studyDateTime != "") ? QVariant(studyDateTime) : QVariant(QMetaType::fromType<QString>()));
            q.bindValue(":modality", modality);
            q.bindValue(":datatype", firstFiletype);
            q.bindValue(":visit", visit);
            q.bindValue(":datesource", datesource);
            q.bindValue(":matchingstudyid", (matchingStudyRowID > 0) ? QVariant(matchingStudyRowID) : QVariant(QMetaType::fromType<int>()));
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            int uploadStudyRowID = q.lastInsertId().toInt();

            QSet<int> pathNumbers;
            for (int sei = 0; sei < seriesList.size(); sei++) {
                QJsonObject se = seriesList[sei].toObject();
                int seriesNum = se.value("num").toInt();
                QString numsource = se.value("numsource").toString();
                QString problem = studyProblem;

                /* the files: each path is a file, or a folder imported whole (e.g. .mff). They are copied into the
                   series by name, so two with the same name can't share a series */
                QJsonArray paths = se.value("paths").toArray();
                QJsonArray units;
                QSet<QString> names;
                int missing(0), links(0), clashes(0);
                qint64 seriesFiles(0), seriesSize(0);
                for (int pi = 0; pi < paths.size(); pi++) {
                    QJsonArray p = paths[pi].toArray();
                    QString rel = p[0].toString();
                    bool bundle = (p[3].toInt() == 1);
                    QString full = stagingpath + "/" + rel;
                    QFileInfo fi(full);
                    if (fi.isSymLink()) { links++; continue; }
                    if (bundle ? !fi.isDir() : !fi.isFile()) { missing++; continue; }
                    if (bundle) {
                        qint64 c(0), b(0);
                        GetDirSizeAndFileCount(full, c, b, true);
                        seriesFiles += c;
                        seriesSize += b;
                    }
                    else {
                        seriesFiles++;
                        seriesSize += fi.size();
                    }
                    if (names.contains(fi.fileName())) clashes++;
                    names.insert(fi.fileName());
                    units.append(QJsonArray({ rel, bundle ? 1 : 0 }));
                }
                if (problem == "") {
                    if (missing > 0) problem = QString("%1 of %2 files are missing on the server").arg(missing).arg(paths.size());
                    else if (links > 0) problem = QString("%1 files are symbolic links, which aren't imported").arg(links);
                    else if (clashes > 0) problem = "Two files in this series have the same name";
                    else if ((numsource == "path") && usedNumbers.contains(seriesNum)) problem = QString("Series %1 already exists in the NiDB study").arg(seriesNum);
                    else if ((numsource == "path") && pathNumbers.contains(seriesNum)) problem = QString("Series number %1 is used twice in this study").arg(seriesNum);
                }
                if (numsource == "path") pathNumbers.insert(seriesNum);

                QJsonObject filelist;
                filelist["numsource"] = numsource;
                filelist["units"] = units;

                QString desc = se.value("desc").toString().left(250);
                q.prepare("insert into upload_series (uploadstudy_id, uploadseries_status, uploadseries_desc, uploadseries_protocol, uploadseries_num, uploadseries_date, uploadseries_numfiles, uploadseries_filelist, uploadseries_modality, uploadseries_filetype, uploadseries_size, uploadseries_problem) values (:studyid, :status, :desc, :desc, :num, :date, :numfiles, :filelist, :modality, :filetype, :size, :problem)");
                q.bindValue(":studyid", uploadStudyRowID);
                q.bindValue(":status", (problem == "") ? "import" : "");
                q.bindValue(":desc", desc);
                q.bindValue(":num", seriesNum);
                q.bindValue(":date", (studyDateTime != "") ? QVariant(studyDateTime) : QVariant(QMetaType::fromType<QString>()));
                q.bindValue(":numfiles", seriesFiles);
                q.bindValue(":filelist", QString(QJsonDocument(filelist).toJson(QJsonDocument::Compact)));
                q.bindValue(":modality", modality);
                q.bindValue(":filetype", se.value("filetype").toString().left(255));
                q.bindValue(":size", seriesSize);
                q.bindValue(":problem", (problem == "") ? QVariant(QMetaType::fromType<QString>()) : QVariant(problem.left(255)));
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

                numSeries++;
                numFiles += seriesFiles;
                if (problem != "") numProblems++;
            }
        }

        SetUploadStatus(uploadRowID, "parsing", 100.0 * (si + 1) / subjects.size());
    }

    /* the manifest is in the database now */
    if (!QFile::remove(manifestpath))
        io->AppendUploadLog(QString("Unable to remove the manifest [%1]").arg(manifestpath));

    io->AppendUploadLog(QString("Loaded [%1] subjects, [%2] series, [%3] files from the import wizard manifest. [%4] series need review").arg(subjects.size()).arg(numSeries).arg(numFiles).arg(numProblems));
    AddImportDecision(uploadRowID, "parse", "series", "", QString::number(numSeries), QString("{\"files\": %1, \"problems\": %2}").arg(numFiles).arg(numProblems));

    if (autoarchive && (numProblems == 0) && (numSeries > 0)) {
        io->AppendUploadLog("Nothing needs review, so the upload is queued for archiving");
        SetUploadStatus(uploadRowID, "queueforarchive", 0.0);
    }
    else
        SetUploadStatus(uploadRowID, "parsingcomplete", 100.0);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AddImportDecision ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Append an event by NiDB to an upload's decision log (import_decisions, doc/universal-import.md §6.5)
 * @param uploadRowID uploadRowID
 * @param step The step, e.g. `parse`, `archive`, `outcome`
 * @param target What it applies to
 * @param oldValue Previous value
 * @param newValue New value
 * @param detailJson Details as JSON, or empty
 */
void moduleUpload::AddImportDecision(int uploadRowID, QString step, QString target, QString oldValue, QString newValue, QString detailJson) {
    QSqlQuery q;
    q.prepare("insert into import_decisions (upload_id, recipe_id, user_id, decision_date, decision_step, decision_source, decision_target, decision_old, decision_new, decision_detail) select upload_id, upload_recipeid, null, now(), :step, 'nidb', :target, :old, :new, :detail from uploads where upload_id = :uploadid");
    q.bindValue(":step", step);
    q.bindValue(":target", target);
    q.bindValue(":old", oldValue);
    q.bindValue(":new", newValue);
    q.bindValue(":detail", (detailJson == "") ? QVariant(QMetaType::fromType<QString>()) : QVariant(detailJson));
    q.bindValue(":uploadid", uploadRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- ArchiveSelectedUniversal ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Archive the series of import wizard uploads that are queued for archiving: create or find the subject, enrollment,
 * and study, then copy each series' files (as they are, D3) into `<archivedir>/<UID>/<studynum>/<seriesnum>/<modality>`
 * @return `true` if any upload was archived
 */
bool moduleUpload::ArchiveSelectedUniversal() {
    QSqlQuery q;
    bool ret(false);

    q.prepare("select upload_id, upload_destprojectid, upload_stagingpath from uploads where upload_status = 'queueforarchive' and upload_type = 'universal'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return ret; }

        ret = true;
        int uploadRowID = q.value("upload_id").toInt();
        int projectRowID = q.value("upload_destprojectid").toInt();
        QString stagingpath = q.value("upload_stagingpath").toString();
        io->SetUploadID(uploadRowID);
        io->AppendUploadLog(QString("Beginning archiving of import wizard upload [%1] into project [%2]").arg(uploadRowID).arg(projectRowID));
        SetUploadStatus(uploadRowID, "archiving", 0.0);

        QSqlQuery q2;
        q2.prepare("select a.uploadsubject_id, a.uploadsubject_patientid, a.matchingsubjectid, b.uploadstudy_id, b.uploadstudy_date, b.uploadstudy_modality, b.uploadstudy_visit, b.matchingstudyid, c.uploadseries_id, c.uploadseries_num, c.uploadseries_desc, c.uploadseries_filetype, c.uploadseries_filelist from upload_series c join upload_studies b on c.uploadstudy_id = b.uploadstudy_id join upload_subjects a on b.uploadsubject_id = a.uploadsubject_id where a.upload_id = :uploadid and c.uploadseries_status = 'import' order by a.uploadsubject_id, b.uploadstudy_id, c.uploadseries_num");
        q2.bindValue(":uploadid", uploadRowID);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        int numSeries = q2.size();
        int done(0), failed(0), newSubjects(0), newStudies(0);

        /* uploadsubject_id -> [subjectRowID, enrollmentRowID], uploadstudy_id -> studyRowID, studyRowID -> series numbers in use */
        QHash<int, QPair<int, int>> subjectRows;
        QHash<int, QString> subjectUIDs;
        QHash<int, int> studyRows;
        QHash<int, QSet<int>> studyNumbers;

        while (q2.next()) {
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return ret; }

            int uploadSubjectRowID = q2.value("uploadsubject_id").toInt();
            int uploadStudyRowID = q2.value("uploadstudy_id").toInt();
            int uploadSeriesRowID = q2.value("uploadseries_id").toInt();
            QString patientID = q2.value("uploadsubject_patientid").toString();
            QString modality = q2.value("uploadstudy_modality").toString();
            QString visit = q2.value("uploadstudy_visit").toString();
            QString studyDateTime = q2.value("uploadstudy_date").toDateTime().toString("yyyy-MM-dd HH:mm:ss");
            QString err;

            /* the subject and its enrollment */
            if (!subjectRows.contains(uploadSubjectRowID)) {
                int subjectRowID(-1), enrollmentRowID(-1);
                QString subjectUID;
                if (!q2.value("matchingsubjectid").isNull()) {
                    subjectRowID = q2.value("matchingsubjectid").toInt();
                    subject s(n);
                    s.searchMethod = SubjectSearchMethod::RowId;
                    s.searchSubjectRowID = subjectRowID;
                    if (s.Load()) subjectUID = s.uid;
                    else subjectRowID = -1;
                }
                else if (io->CreateSubject(patientID, patientID, "0000-00-00", "U", 0.0, 0.0, subjectRowID, subjectUID))
                    newSubjects++;
                if ((subjectRowID > 0) && io->GetOrCreateEnrollment(subjectRowID, projectRowID, enrollmentRowID)) {
                    if (patientID.compare(subjectUID, Qt::CaseInsensitive) != 0)
                        io->SetAlternateIDs(subjectRowID, enrollmentRowID, QStringList(patientID));
                }
                else
                    enrollmentRowID = -1;
                subjectRows[uploadSubjectRowID] = qMakePair(subjectRowID, enrollmentRowID);
                subjectUIDs[uploadSubjectRowID] = subjectUID;
            }
            int subjectRowID = subjectRows[uploadSubjectRowID].first;
            int enrollmentRowID = subjectRows[uploadSubjectRowID].second;
            if ((subjectRowID < 1) || (enrollmentRowID < 1))
                err = QString("Unable to find or create subject [%1] in project [%2]").arg(patientID).arg(projectRowID);

            /* the study */
            QStringList cols = SeriesTableColumns(modality);
            if ((err == "") && cols.isEmpty())
                err = QString("NiDB has no series table for modality [%1]").arg(modality);
            if ((err == "") && !QDateTime::fromString(studyDateTime, "yyyy-MM-dd HH:mm:ss").isValid())
                err = "The study has no valid date";
            if ((err == "") && !studyRows.contains(uploadStudyRowID)) {
                int studyRowID(-1), studyNum(-1);
                if (!q2.value("matchingstudyid").isNull())
                    studyRowID = q2.value("matchingstudyid").toInt();
                else if (io->CreateStudy(subjectRowID, enrollmentRowID, studyDateTime, "", modality, patientID, 0.0, 0.0, 0.0, visit, "", "", "", "", "", studyRowID, studyNum) && (studyRowID > 0)) {
                    newStudies++;
                    QSqlQuery q3;
                    q3.prepare("update studies set study_type = :visit where study_id = :studyid");
                    q3.bindValue(":visit", visit);
                    q3.bindValue(":studyid", studyRowID);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                studyRows[uploadStudyRowID] = studyRowID;
                if (studyRowID > 0) {
                    QSet<int> used;
                    QSqlQuery q3;
                    q3.prepare(QString("select series_num from %1_series where study_id = :studyid").arg(modality.toLower()));
                    q3.bindValue(":studyid", studyRowID);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    while (q3.next()) used.insert(q3.value(0).toInt());
                    studyNumbers[studyRowID] = used;
                }
            }
            int studyRowID = (err == "") ? studyRows.value(uploadStudyRowID, -1) : -1;
            if ((err == "") && (studyRowID < 1))
                err = "Unable to find or create the study";

            /* the series number: from the path as given, otherwise the next free number in the study */
            QJsonObject filelist = QJsonDocument::fromJson(q2.value("uploadseries_filelist").toByteArray()).object();
            int seriesNum = q2.value("uploadseries_num").toInt();
            if (err == "") {
                QSet<int> &used = studyNumbers[studyRowID];
                if (used.contains(seriesNum)) {
                    if (filelist.value("numsource").toString() == "path")
                        err = QString("Series %1 already exists in the study").arg(seriesNum);
                    else {
                        int maxNum = 0;
                        for (int x : used) maxNum = qMax(maxNum, x);
                        seriesNum = maxNum + 1;
                    }
                }
                if (err == "") used.insert(seriesNum);
            }

            /* the series row, then its files */
            if (err == "") {
                study stud(studyRowID, n);
                QString mod = modality.toLower();
                QString datatype = q2.value("uploadseries_filetype").toString().toLower().remove(QRegularExpression("[^a-z0-9]"));
                if (datatype == "") datatype = "other";
                QString seriesPath = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectUIDs[uploadSubjectRowID]).arg(stud.GetStudyNum()).arg(seriesNum);
                QString dataPath = seriesPath + "/" + ((mod == "mr") ? datatype : mod);
                QString desc = q2.value("uploadseries_desc").toString();

                QSqlQuery q3;
                q3.prepare(QString("insert into %1_series (study_id, series_num, series_datetime, series_desc) values (:studyid, :num, :datetime, :desc)").arg(mod));
                q3.bindValue(":studyid", studyRowID);
                q3.bindValue(":num", seriesNum);
                q3.bindValue(":datetime", studyDateTime);
                q3.bindValue(":desc", desc);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                qint64 seriesRowID = q3.lastInsertId().toLongLong();
                QString pk = mod + "series_id"; /* every series table's key is <modality>series_id */

                QString m;
                if ((seriesRowID < 1) || !MakePath(dataPath, m))
                    err = QString("Unable to create the series [%1] %2").arg(dataPath).arg(m);
                else {
                    QJsonArray units = filelist.value("units").toArray();
                    for (int ui = 0; (ui < units.size()) && (err == ""); ui++) {
                        QJsonArray u = units[ui].toArray();
                        QString src = stagingpath + "/" + u[0].toString();
                        if (u[1].toInt() == 1) {
                            QString out = SystemCommand(QString("cp -a %1 %2/").arg(ShellQuote(src)).arg(ShellQuote(dataPath)), false).trimmed();
                            if (out != "") err = QString("Error copying [%1]: %2").arg(src).arg(out);
                        }
                        else if (!NiDBCopyFile(src, dataPath, m))
                            err = QString("Error copying [%1]: %2").arg(src).arg(m);
                    }
                    SystemCommand(QString("chmod -Rf 777 %1").arg(ShellQuote(seriesPath)), false);

                    qint64 c(0), b(0);
                    GetDirSizeAndFileCount(dataPath, c, b, true);
                    QStringList sets = { "series_size = :size" };
                    if (cols.contains("series_numfiles")) sets << "series_numfiles = :numfiles";
                    if (cols.contains("numfiles")) sets << "numfiles = :numfiles";
                    if (cols.contains("series_protocol")) sets << "series_protocol = :desc";
                    if (cols.contains("series_notes")) sets << "series_notes = :notes";
                    if (cols.contains("series_createdby")) sets << "series_createdby = 'import'";
                    if (cols.contains("series_status")) sets << "series_status = :status";
                    if (cols.contains("data_type")) sets << "data_type = :datatype";
                    q3.prepare(QString("update %1_series set %2 where %3 = :seriesid").arg(mod).arg(sets.join(", ")).arg(pk));
                    q3.bindValue(":size", b);
                    if (cols.contains("series_numfiles") || cols.contains("numfiles")) q3.bindValue(":numfiles", c);
                    if (cols.contains("series_protocol")) q3.bindValue(":desc", desc);
                    if (cols.contains("series_notes")) q3.bindValue(":notes", QString("Imported by the import wizard, upload %1").arg(uploadRowID));
                    if (cols.contains("series_status")) q3.bindValue(":status", (err == "") ? "complete" : "error");
                    if (cols.contains("data_type")) q3.bindValue(":datatype", datatype);
                    q3.bindValue(":seriesid", seriesRowID);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
            }

            QSqlQuery q4;
            if (err == "") {
                q4.prepare("update upload_series set uploadseries_status = 'archived' where uploadseries_id = :id");
                io->AppendUploadLog(QString("Archived series [%1] of subject [%2] study [%3]").arg(seriesNum).arg(patientID).arg(studyRowID));
            }
            else {
                failed++;
                q4.prepare("update upload_series set uploadseries_problem = :problem where uploadseries_id = :id");
                q4.bindValue(":problem", err.left(255));
                io->AppendUploadLog(QString("Error archiving upload series [%1]: %2").arg(uploadSeriesRowID).arg(err));
            }
            q4.bindValue(":id", uploadSeriesRowID);
            n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

            done++;
            SetUploadStatus(uploadRowID, "archiving", 100.0 * done / qMax(1, numSeries));
        }

        QString outcome = QString("{\"series\": %1, \"failed\": %2, \"newsubjects\": %3, \"newstudies\": %4}").arg(done - failed).arg(failed).arg(newSubjects).arg(newStudies);
        AddImportDecision(uploadRowID, "outcome", "archive", "", (failed == 0) ? "success" : "partial", outcome);
        QSqlQuery q5;
        q5.prepare(QString("update import_recipes a join uploads b on a.recipe_id = b.upload_recipeid set a.%1 = a.%1 + 1 where b.upload_id = :uploadid").arg((failed == 0) ? "recipe_successcount" : "recipe_correctioncount"));
        q5.bindValue(":uploadid", uploadRowID);
        n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);

        if ((failed > 0) || (numSeries == 0)) {
            io->AppendUploadLog(QString("Archived [%1] of [%2] series. [%3] failed").arg(done - failed).arg(numSeries).arg(failed));
            SetUploadStatus(uploadRowID, "archiveerror", -1.0);
        }
        else {
            io->AppendUploadLog(QString("Completed archiving of upload [%1]: [%2] series, [%3] new subjects, [%4] new studies").arg(uploadRowID).arg(done).arg(newSubjects).arg(newStudies));
            SetUploadStatus(uploadRowID, "archivecomplete", 100.0);
            QString m;
            if (SafeDeletePath(stagingpath, n->cfg["uploadstagingdir"], m))
                io->AppendUploadLog(QString("Removed upload staging directory [%1]").arg(stagingpath));
            else
                io->AppendUploadLog(QString("Error removing upload staging directory [%1]: %2").arg(stagingpath).arg(m));
        }
    }
    return ret;
}
