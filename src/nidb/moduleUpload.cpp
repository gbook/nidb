/* ------------------------------------------------------------------------------
  NIDB moduleUpload.cpp
  Copyright (C) 2004 - 2023
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
int moduleUpload::Run() {
    n->WriteLog("Entering the upload module");

    //QSqlQuery q;
    bool ret(false);

    /* parse any uploads */
    ret |= ParseUploads();

    /* archive any uploads */
    ret |= ArchiveParsedUploads();

    n->WriteLog("Leaving the upload module");
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- ParseUploads ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleUpload::ParseUploads() {
    QSqlQuery q;
    bool ret(false);

    /* get list of uploads that are marked as uploadcomplete, with the upload details */
    q.prepare("select * from uploads where upload_status = 'uploadcomplete'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }

            ret = 1;
            int upload_id = q.value("upload_id").toInt();
            io->SetUploadID(upload_id);

            QString upload_source = q.value("upload_source").toString();
            QString upload_datapath = q.value("upload_datapath").toString();
            //int upload_destprojectid = q.value("upload_destprojectid").toInt();
            QString upload_patientid = q.value("upload_patientid").toString();
            QString upload_modality = q.value("upload_modality").toString();
            //bool upload_guessmodality = q.value("upload_guessmodality").toBool();
            QString upload_subjectcriteria = q.value("upload_subjectcriteria").toString();
            QString upload_studycriteria = q.value("upload_studycriteria").toString();
            QString upload_seriescriteria = q.value("upload_seriescriteria").toString();

            /* update the status */
            SetUploadStatus(upload_id, "parsing", 0.0);

            /* create the path for the upload data */
            QString uploadstagingpath = QString("%1/%2").arg(n->cfg["uploadstagingdir"]).arg(upload_id);

            /* create temporary directory in uploadstagingdir */
            QString m;
            if (!MakePath(uploadstagingpath, m)) {
                io->AppendUploadLog(__FUNCTION__, "Error creating directory [" + uploadstagingpath + "]  with message [" + m + "]");
                ret = 1;

                /* update the status */
                SetUploadStatus(upload_id, "parsingerror");

                continue;
            }
            /* update the upload_stagingpath */
            QSqlQuery q2;
            q2.prepare("update uploads set upload_stagingpath = :stagingpath where upload_id = :uploadid");
            q2.bindValue(":stagingpath", uploadstagingpath);
            q2.bindValue(":uploadid", upload_id);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

            /* check if the upload path is valid */
            if ((upload_datapath == "") || (upload_datapath == "/") || (upload_datapath == "/etc") || (upload_datapath == "/bin") || (upload_datapath == "/root")) {
                io->AppendUploadLog(__FUNCTION__, QString("upload_datapath is invalid [%1] ").arg(upload_datapath));

                /* update the status */
                SetUploadStatus(upload_id, "parsingerror");

                continue;
            }

            /* if modality is blank */
            if (upload_modality == "") {
                io->AppendUploadLog(__FUNCTION__, "Error. Modality was blank [" + upload_modality + "]");
                ret = 1;

                /* update the status */
                SetUploadStatus(upload_id, "parsingerror");

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

            /* get information about the uploaded data from the uploadstagingdir (before unzipping any zip files) */
            qint64 c;
            qint64 b;
            //n->GetDirSizeAndFileCount(uploadstagingpath, c, b, true);
            //io->AppendUploadLog(__FUNCTION__, QString("(BEFORE UNZIPPING) Upload directory [%1] contains [%2] files, and is [%3] bytes in size.").arg(uploadstagingpath).arg(c).arg(b));

            /* unzip any files in the uploadstagingdir */
            io->AppendUploadLog(__FUNCTION__, "Unzipping files located in [" + uploadstagingpath + "]");
            QString unzipOutput = UnzipDirectory(uploadstagingpath, true);
            io->AppendUploadLog(__FUNCTION__, "Unzip output" + unzipOutput);

            /* get information about the uploaded data from the uploadstagingdir (after unzipping any zip files) */
            c = 0;
            b = 0;
            GetDirSizeAndFileCount(uploadstagingpath, c, b, true);
            io->AppendUploadLog(__FUNCTION__, QString("AFTER 3 passes of UNZIPPING, upload directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(uploadstagingpath).arg(c).arg(b));

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
                    SetUploadStatus(upload_id, "parsing", pct);

                    /* check if this module should be running */
                    n->ModuleRunningCheckIn();
                    if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }

                    /* check if the upload status has changed */
                    QString status = GetUploadStatus(upload_id);
                    if (status != "parsing") {
                        return ret;
                    }

                    /* after 5000 files, put the found information into the database, then clear the fs list */
                    io->AppendUploadLog(__FUNCTION__, QString("Found [%1] total files: [%2] valid, [%3] nonmatch, [%4] unreadable").arg(tfiles).arg(validFiles).arg(nonMatchFiles).arg(unreadableFiles));

                    io->AppendUploadLog(__FUNCTION__, QString("fs.size() [%1] before being sent into UpdateParsedUploads()").arg(fs.size()));

                    UpdateParsedUploads(fs, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, uploadstagingpath, upload_id);
                    fs.clear();
                    i = 0;
                }
            }

            double pct = (static_cast<double>(tfiles)/static_cast<double>(c)) * 100.0;
            SetUploadStatus(upload_id, "parsing", pct);

            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }

            /* check if the upload status has changed */
            QString status = GetUploadStatus(upload_id);
            if (status != "parsing") {
                return ret;
            }

            /* after 5000 files, put the found information into the database, then clear the fs list */
            io->AppendUploadLog(__FUNCTION__, QString("Found [%1] total files: [%2] valid, [%3] nonmatch, [%4] unreadable").arg(tfiles).arg(validFiles).arg(nonMatchFiles).arg(unreadableFiles));

            io->AppendUploadLog(__FUNCTION__, QString("fs.size() [%1] before being sent into UpdateParsedUploads()").arg(fs.size()));

            UpdateParsedUploads(fs, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, uploadstagingpath, upload_id);
            fs.clear();

            /* update the status */
            SetUploadStatus(upload_id, "parsingcomplete", 100);

        } /* end while */
    }

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- UpdateParsedUploads ---------------------------- */
/* ---------------------------------------------------------- */
bool moduleUpload::UpdateParsedUploads(QMap<QString, QMap<QString, QMap<QString, QStringList> > > fs, QString upload_subjectcriteria, QString upload_studycriteria, QString upload_seriescriteria, QString uploadstagingpath, int upload_id) {

    io->AppendUploadLog(__FUNCTION__, QString("Processing [%1] subjects").arg(fs.size()));

    /* ---------- iterate through the subjects ---------- */
    for(QMap<QString, QMap<QString, QMap<QString, QStringList> > >::iterator a = fs.begin(); a != fs.end(); ++a) {
        QString subject = a.key();

        io->AppendUploadLog(__FUNCTION__, QString("Processing subject [%1]").arg(subject));

        /* get the uploadsubject_id */
        int subjectid(0);

        if ((upload_subjectcriteria == "patientid") || (upload_subjectcriteria == "specificpatientid") || (upload_subjectcriteria == "patientidfromdir")) {
            /* get subjectid by PatientID field (or the specific PatientID, or from the parent directory) */
            QString PatientID = subject;

            QSqlQuery q3;
            /* check if the subjectid exists ... */
            q3.prepare("select uploadsubject_id from upload_subjects where upload_id = :uploadid and uploadsubject_patientid = :patientid");
            q3.bindValue(":uploadid", upload_id);
            q3.bindValue(":patientid", PatientID);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
            if (q3.size() > 0) {
                q3.first();
                subjectid = q3.value("uploadsubject_id").toInt();
                //io->AppendUploadLog(__FUNCTION__, QString("Found subjectid [%1]").arg(subjectid));
            }
            else {
                /* ... otherwise create a new subject */
                q3.prepare("insert into upload_subjects (upload_id, uploadsubject_patientid) values (:uploadid, :patientid)");
                q3.bindValue(":uploadid", upload_id);
                q3.bindValue(":patientid", PatientID);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                subjectid = q3.lastInsertId().toInt();
                //io->AppendUploadLog(__FUNCTION__, QString("subjectid [%1] created").arg(subjectid));
            }
        }
        else if (upload_subjectcriteria == "namesexdob") {
            /* get subjectid by PatientName/PatientSex/PatientBirthDate */
			QString PatientName, PatientSex, PatientBirthDate;
            QStringList parts = subject.split("|");
			if (parts.size() > 0)
				PatientName = parts[0];
			if (parts.size() > 1)
				PatientSex = parts[1];
			if (parts.size() > 2)
				PatientBirthDate = parts[2];

            QSqlQuery q3;
            /* check if the subjectid already exists ... */
            q3.prepare("select uploadsubject_id from upload_subjects where upload_id = :uploadid and uploadsubject_name = :patientname and uploadsubject_dob = :patientdob and uploadsubject_sex = :patientsex");
            q3.bindValue(":uploadid", upload_id);
            q3.bindValue(":patientname", PatientName);
            q3.bindValue(":patientdob", PatientBirthDate);
            q3.bindValue(":patientsex", PatientSex);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
            if (q3.size() > 0) {
                q3.first();
                subjectid = q3.value("uploadsubject_id").toInt();
                //io->AppendUploadLog(__FUNCTION__, QString("Found subjectid [%1]").arg(subjectid));
            }
            else {
                /* ... otherwise create a new subject */
                q3.prepare("insert into upload_subjects (upload_id, uploadsubject_name, uploadsubject_dob, uploadsubject_sex) values (:uploadid, :patientname, :patientdob, :patientsex)");
                q3.bindValue(":uploadid", upload_id);
                q3.bindValue(":patientname", PatientName);
                q3.bindValue(":patientdob", PatientBirthDate);
                q3.bindValue(":patientsex", PatientSex);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                subjectid = q3.lastInsertId().toInt();
                //io->AppendUploadLog(__FUNCTION__, QString("subjectid [%1] created").arg(subjectid));
            }
        }
        else
            n->WriteLog("Unspecified subject criteria [" + upload_subjectcriteria + "]. That's weird it would show up here...");

        /* ---------- iterate through the studies ---------- */
        for(QMap<QString, QMap<QString, QStringList> >::iterator b = fs[subject].begin(); b != fs[subject].end(); ++b) {

            QString study = b.key();

            io->AppendUploadLog(__FUNCTION__, QString("Processing study [%1]").arg(study));

            /* get the uploadstudy_id */
            int studyid(0);

            if (upload_studycriteria == "modalitystudydate") {
                /* get studyid from Modality/StudyDateTime fields */
                QStringList parts = study.split("|");
                QString Modality;
                QString StudyDateTime;
                if (parts.size() > 0) {
                    Modality = parts[0];
                    if (parts.size() > 1)
                        StudyDateTime = parts[1];
                }

                QSqlQuery q3;
                /* check if the studyid exists ... */
                q3.prepare("select uploadstudy_id from upload_studies where uploadsubject_id = :subjectid and uploadstudy_date = :studydatetime and uploadstudy_modality = :modality");
                q3.bindValue(":subjectid", subjectid);
                q3.bindValue(":studydatetime", StudyDateTime);
                q3.bindValue(":modality", Modality);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                if (q3.size() > 0) {
                    q3.first();
                    studyid = q3.value("uploadstudy_id").toInt();
                    //io->AppendUploadLog(__FUNCTION__, QString("Found studyid [%1]").arg(studyid));
                }
                else {
                    /* ... otherwise create a new study */
                    q3.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_date, uploadstudy_modality) values (:subjectid, :studydatetime, :modality)");
                    q3.bindValue(":subjectid", subjectid);
                    q3.bindValue(":studydatetime", StudyDateTime);
                    q3.bindValue(":modality", Modality);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    studyid = q3.lastInsertId().toInt();
                    //io->AppendUploadLog(__FUNCTION__, QString("studyid [%1] created").arg(studyid));
                }
            }
            else if (upload_studycriteria == "studyuid") {
                /* get studyid using StudyInstanceUID field */
                QString StudyInstanceUID = study;

                QSqlQuery q3;
                /* check if the studyid already exists ... */
                q3.prepare("select uploadstudy_id from upload_studies where uploadsubject_id = :subjectid and uploadstudy_instanceuid = :studyinstanceuid");
                q3.bindValue(":subjectid", subjectid);
                q3.bindValue(":studyinstanceuid", StudyInstanceUID);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                if (q3.size() > 0) {
                    q3.first();
                    studyid = q3.value("uploadstudy_id").toInt();
                    //io->AppendUploadLog(__FUNCTION__, QString("Found studyid [%1]").arg(studyid));
                }
                else {
                    /* ... otherwise create a new study */
                    q3.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_instanceuid) values (:subjectid, :studyinstanceuid)");
                    q3.bindValue(":subjectid", subjectid);
                    q3.bindValue(":studyinstanceuid", StudyInstanceUID);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    studyid = q3.lastInsertId().toInt();
                    //io->AppendUploadLog(__FUNCTION__, QString("studyid [%1] created").arg(studyid));
                }
            }
            else
                io->AppendUploadLog(__FUNCTION__, "Unspecified study criteria [" + upload_studycriteria + "]. That's weird it would show up here...");

            /* ---------- iterate through the series ---------- */
            for(QMap<QString, QStringList>::iterator c = fs[subject][study].begin(); c != fs[subject][study].end(); ++c) {

                QString series = c.key();
                io->AppendUploadLog(__FUNCTION__, QString("Processing series [%1]").arg(series));

                /* get uploadseries_id */
                int seriesid(0);

                QStringList files = fs[subject][study][series];
                qint64 numfiles = files.size();
                //io->AppendUploadLog(__FUNCTION__, QString("numfiles [%1]   numfiles [%2]").arg(files.size()).arg(numfiles));

                if (upload_seriescriteria == "seriesnum") {
                    /* get seriesid from SeriesNumber field */
                    bool ok = false;
                    int SeriesNumber = series.toInt(&ok);
                    if (!ok)
                        SeriesNumber = 0;

                    QSqlQuery q3;
                    /* check if the studyid exists ... */
                    q3.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_num = :seriesnum");
                    q3.bindValue(":studyid", studyid);
                    q3.bindValue(":seriesnum", SeriesNumber);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    if (q3.size() > 0) {
                        q3.first();
                        seriesid = q3.value("uploadseries_id").toInt();

                        int databaseNumFiles = q3.value("uploadseries_numfiles").toInt();
                        io->AppendUploadLog(__FUNCTION__, QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                        io->AppendUploadLog(__FUNCTION__, QString("2) numfiles, before appending [%1]").arg(numfiles));
                        numfiles += databaseNumFiles;
                        io->AppendUploadLog(__FUNCTION__, QString("3) numfiles, after appending [%1]").arg(numfiles));

                        QStringList databaseFiles = q3.value("uploadseries_filelist").toString().split(",");
                        io->AppendUploadLog(__FUNCTION__, QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                        io->AppendUploadLog(__FUNCTION__, QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                        files.append(databaseFiles);
                        io->AppendUploadLog(__FUNCTION__, QString("3) Files list, after appending, contains [%1] files").arg(files.size()));

                        //io->AppendUploadLog(__FUNCTION__, QString("Found seriesid [%1]").arg(seriesid));
                    }
                    else {
                        /* ... otherwise create a new series */
                        q3.prepare("insert into upload_series (uploadstudy_id, uploadseries_num) values (:studyid, :seriesnum)");
                        q3.bindValue(":studyid", studyid);
                        q3.bindValue(":seriesnum", SeriesNumber);
                        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                        seriesid = q3.lastInsertId().toInt();
                        //io->AppendUploadLog(__FUNCTION__, QString("seriesid [%1] created").arg(seriesid));
                    }
                }
                else if (upload_seriescriteria == "seriesdate") {
                    /* get seriesid using SeriesDateTime field */
                    QString SeriesDateTime = series;

                    QSqlQuery q3;
                    /* check if the seriesid already exists ... */
                    q3.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_date = '" + SeriesDateTime + "'");
                    q3.bindValue(":studyid", studyid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    if (q3.size() > 0) {
                        q3.first();
                        seriesid = q3.value("uploadseries_id").toInt();
                        int databaseNumFiles = q3.value("uploadseries_numfiles").toInt();
                        io->AppendUploadLog(__FUNCTION__, QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                        io->AppendUploadLog(__FUNCTION__, QString("2) numfiles, before appending [%1]").arg(numfiles));
                        numfiles += databaseNumFiles;
                        io->AppendUploadLog(__FUNCTION__, QString("3) numfiles, after appending [%1]").arg(numfiles));

                        QStringList databaseFiles = q3.value("uploadseries_filelist").toString().split(",");
                        io->AppendUploadLog(__FUNCTION__, QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                        io->AppendUploadLog(__FUNCTION__, QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                        files.append(databaseFiles);
                        io->AppendUploadLog(__FUNCTION__, QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
                        //io->AppendUploadLog(__FUNCTION__, QString("Found seriesid [%1]").arg(seriesid));
                    }
                    else {
                        /* ... otherwise create a new series */
                        q3.prepare("insert into upload_series (uploadstudy_id, uploadseries_date) values (:studyid, '" + SeriesDateTime + "')");
                        q3.bindValue(":studyid", studyid);
                        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                        seriesid = q3.lastInsertId().toInt();
                        //io->AppendUploadLog(__FUNCTION__, QString("seriesid [%1] created").arg(seriesid));
                    }
                }
                else if (upload_seriescriteria == "seriesuid") {
                    /* get seriesid using SeriesInstanceUID field */
                    QString SeriesInstanceUID = series;

                    QSqlQuery q3;
                    /* check if the seriesid already exists ... */
                    q3.prepare("select uploadseries_id, uploadseries_numfiles, uploadseries_filelist from upload_series where uploadstudy_id = :studyid and uploadseries_instanceuid = :seriesinstanceuid");
                    q3.bindValue(":studyid", studyid);
                    q3.bindValue(":seriesinstanceuid", SeriesInstanceUID);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    if (q3.size() > 0) {
                        q3.first();
                        seriesid = q3.value("uploadseries_id").toInt();
                        int databaseNumFiles = q3.value("uploadseries_numfiles").toInt();
                        io->AppendUploadLog(__FUNCTION__, QString("1) Database numfiles [%1]").arg(databaseNumFiles));
                        io->AppendUploadLog(__FUNCTION__, QString("2) numfiles, before appending [%1]").arg(numfiles));
                        numfiles += databaseNumFiles;
                        io->AppendUploadLog(__FUNCTION__, QString("3) numfiles, after appending [%1]").arg(numfiles));

                        QStringList databaseFiles = q3.value("uploadseries_filelist").toString().split(",");
                        io->AppendUploadLog(__FUNCTION__, QString("1) Database contains list of [%1] files").arg(databaseFiles.size()));
                        io->AppendUploadLog(__FUNCTION__, QString("2) Files list, before appending, contains [%1] files").arg(files.size()));
                        files.append(databaseFiles);
                        io->AppendUploadLog(__FUNCTION__, QString("3) Files list, after appending, contains [%1] files").arg(files.size()));
                        //io->AppendUploadLog(__FUNCTION__, QString("Found seriesid [%1]").arg(seriesid));
                    }
                    else {
                        /* ... otherwise create a new series */
                        q3.prepare("insert into upload_series (uploadstudy_id, uploadseries_instanceuid) values (:studyid, :seriesinstanceuid)");
                        q3.bindValue(":studyid", studyid);
                        q3.bindValue(":seriesinstanceuid", SeriesInstanceUID);
                        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                        seriesid = q3.lastInsertId().toInt();
                        //io->AppendUploadLog(__FUNCTION__, QString("seriesid [%1] created").arg(seriesid));
                    }
                }
                else {
                    io->AppendUploadLog(__FUNCTION__, "Unspecified seriesid criteria [" + upload_seriescriteria + "]. That's weird it would show up here...");
                }

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

                /* update subject details */
                if ( (upload_subjectcriteria == "patientid") || (upload_subjectcriteria == "specificpatientid") || (upload_subjectcriteria == "patientidfromdir") ) {
                    /* update all subject details except PatientID */
                    q3.prepare("update upload_subjects set uploadsubject_name = :name, uploadsubject_sex = :sex, uploadsubject_dob = :dob where uploadsubject_id = :subjectid");
                    q3.bindValue(":name", tags["PatientName"]);
                    q3.bindValue(":sex", tags["PatientSex"]);
                    q3.bindValue(":dob", tags["PatientBirthDate"]);
                    q3.bindValue(":subjectid", subjectid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else if (upload_subjectcriteria == "namesexdob") {
                    /* update all subject details except PatientName/Sex/BirthDate */
                    q3.prepare("update upload_subjects set uploadsubject_patientid = :patientid where uploadsubject_id = :subjectid");
                    q3.bindValue(":name", tags["PatientID"]);
                    q3.bindValue(":subjectid", subjectid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else io->AppendUploadLog(__FUNCTION__, "Unspecified subject criteria [" + upload_subjectcriteria + "]");


                /* update study details */
                if (upload_studycriteria == "modalitystudydate") {
                    /* update all study details except Modality/StudyDateTime */
                    q3.prepare("update upload_studies set uploadstudy_desc = :desc, uploadstudy_datatype = :datatype, uploadstudy_equipment = :equipment, uploadstudy_operator = :operator where uploadstudy_id = :studyid");
                    q3.bindValue(":desc", tags["StudyDescription"]);
                    q3.bindValue(":datatype", tags["FileType"]);
                    q3.bindValue(":equipment", tags["Manufacturer"] + " " + tags["ManufacturerModelName"]);
                    q3.bindValue(":operator", tags["OperatorsName"]);
                    q3.bindValue(":studyinstanceuid", tags["StudyInstanceUID"]);
                    q3.bindValue(":studyid", studyid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else if (upload_studycriteria == "studyuid") {
                    /* update all study details except StudyInstanceUID */
                    q3.prepare("update upload_studies set uploadstudy_desc = :desc, uploadstudy_date = :datetime, uploadstudy_modality = :modality, uploadstudy_datatype = :datatype, uploadstudy_equipment = :equipment, uploadstudy_operator = :operator where uploadstudy_id = :studyid");
                    q3.bindValue(":desc", tags["StudyDescription"]);
                    q3.bindValue(":datetime", tags["StudyDateTime"]);
                    q3.bindValue(":modality", tags["Modality"]);
                    q3.bindValue(":datatype", tags["FileType"]);
                    q3.bindValue(":equipment", tags["Manufacturer"] + " " + tags["ManufacturerModelName"]);
                    q3.bindValue(":operator", tags["OperatorsName"]);
                    q3.bindValue(":studyinstanceuid", tags["StudyInstanceUID"]);
                    q3.bindValue(":studyid", studyid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else io->AppendUploadLog(__FUNCTION__, "Unspecified study criteria [" + upload_studycriteria + "]");


                /* update series details */
                if (upload_seriescriteria == "seriesnum") {
                    /* update all series details except SeriesNumber */
                    q3.prepare("update upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_date = :date, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_instanceuid = :seriesinstanceuid, uploadseries_filelist = :files where uploadseries_id = :seriesid");
                    q3.bindValue(":desc", tags["SeriesDescription"]);
                    q3.bindValue(":protocol", tags["ProtocolName"]);
                    q3.bindValue(":date", tags["SeriesDateTime"]);
                    q3.bindValue(":NumberOfFiles", numfiles);
                    if (tags["RepetitionTime"] == "") q3.bindValue(":tr", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":tr", tags["RepetitionTime"]);
                    if (tags["EchoTime"] == "") q3.bindValue(":te", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":te", tags["EchoTime"]);
                    if (tags["SpacingBetweenSlices"] == "") q3.bindValue(":slicespacing", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":slicespacing", tags["SpacingBetweenSlices"]);
                    if (tags["SliceThickness"] == "") q3.bindValue(":slicethickness", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":slicethickness", tags["SliceThickness"]);
                    q3.bindValue(":rows", tags["Rows"]);
                    q3.bindValue(":cols", tags["Columns"]);
                    q3.bindValue(":seriesinstanceuid", tags["SeriesInstanceUID"]);
                    q3.bindValue(":files", filesNoPrefix.join(","));
                    q3.bindValue(":seriesid", seriesid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else if (upload_seriescriteria == "seriesdate") {
                    /* update all series details except SeriesDateTime */
                    q3.prepare("update upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_num = :num, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_instanceuid = :seriesinstanceuid, uploadseries_filelist = :files where uploadseries_id = :seriesid");
                    q3.bindValue(":desc", tags["SeriesDescription"]);
                    q3.bindValue(":protocol", tags["ProtocolName"]);
                    q3.bindValue(":num", tags["SeriesNumber"]);
                    q3.bindValue(":NumberOfFiles", numfiles);
                    if (tags["RepetitionTime"] == "") q3.bindValue(":tr", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":tr", tags["RepetitionTime"]);
                    if (tags["EchoTime"] == "") q3.bindValue(":te", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":te", tags["EchoTime"]);
                    if (tags["SpacingBetweenSlices"] == "") q3.bindValue(":slicespacing", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":slicespacing", tags["SpacingBetweenSlices"]);
                    if (tags["SliceThickness"] == "") q3.bindValue(":slicethickness", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":slicethickness", tags["SliceThickness"]);
                    q3.bindValue(":rows", tags["Rows"]);
                    q3.bindValue(":cols", tags["Columns"]);
                    q3.bindValue(":seriesinstanceuid", tags["SeriesInstanceUID"]);
                    q3.bindValue(":files", filesNoPrefix.join(","));
                    q3.bindValue(":seriesid", seriesid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else if (upload_seriescriteria == "seriesuid") {
                    /* update all series details except SeriesInstanceUID */
                    q3.prepare("update upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_num = :num, uploadseries_date = :date, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_filelist = :files where uploadseries_id = :seriesid");
                    q3.bindValue(":desc", tags["SeriesDescription"]);
                    q3.bindValue(":protocol", tags["ProtocolName"]);
                    q3.bindValue(":date", tags["SeriesDateTime"]);
                    q3.bindValue(":num", tags["SeriesNumber"]);
                    q3.bindValue(":NumberOfFiles", numfiles);
                    if (tags["RepetitionTime"] == "") q3.bindValue(":tr", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":tr", tags["RepetitionTime"]);
                    if (tags["EchoTime"] == "") q3.bindValue(":te", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":te", tags["EchoTime"]);
                    if (tags["SpacingBetweenSlices"] == "") q3.bindValue(":slicespacing", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":slicespacing", tags["SpacingBetweenSlices"]);
                    if (tags["SliceThickness"] == "") q3.bindValue(":slicethickness", QVariant(QMetaType::fromType<double>())); else q3.bindValue(":slicethickness", tags["SliceThickness"]);
                    q3.bindValue(":rows", tags["Rows"]);
                    q3.bindValue(":cols", tags["Columns"]);
                    q3.bindValue(":files", filesNoPrefix.join(","));
                    q3.bindValue(":seriesid", seriesid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                }
                else io->AppendUploadLog(__FUNCTION__, "Unspecified series criteria [" + upload_seriescriteria + "]");

            //}
            //else {
            //    io->AppendUploadLog(__FUNCTION__, "Error reading file [" + files[0] + "]. That's weird it would show up here...");
            //}
            }
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AchiveParsedUploads ---------------------------- */
/* ---------------------------------------------------------- */
bool moduleUpload::ArchiveParsedUploads() {
    QSqlQuery q;
    bool ret(false);

    /* get list of uploads that are ready to be archived */
    q.prepare("select * from uploads where upload_status = 'queueforarchive'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {

            /* check if this module should be running */
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }

            ret = 1;
            bool error = false;
            int upload_id = q.value("upload_id").toInt();
            io->SetUploadID(upload_id);

            //QString upload_status = q.value("upload_status").toString();
            int upload_destprojectid = q.value("upload_destprojectid").toInt();
            QString upload_patientid = q.value("upload_patientid").toString();
            QString upload_stagingpath = q.value("upload_stagingpath").toString();
            QString upload_subjectcriteria = q.value("upload_subjectcriteria").toString();
            QString upload_studycriteria = q.value("upload_studycriteria").toString();
            QString upload_seriescriteria = q.value("upload_seriescriteria").toString();

            io->AppendUploadLog(__FUNCTION__, QString("Beginning archiving of upload [%1] with upload_destprojectid of [%2]").arg(upload_id).arg(upload_destprojectid));

            /* set status to archiving */
            SetUploadStatus(upload_id, "archiving", 0.0);

            /* get list of series which should be archived from this upload */
            QSqlQuery q2;
            q2.prepare("select * from upload_series a left join upload_studies b on a.uploadstudy_id = b.uploadstudy_id left join upload_subjects c on b.uploadsubject_id = c.uploadsubject_id where a.uploadseries_status = 'import' and c.upload_id = :uploadid");
            q2.bindValue(":uploadid", upload_id);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            int numSeries = q2.size();
            int i(0);
            if (numSeries > 0) {
                while (q2.next()) {

                    /* check if this module should be running */
                    n->ModuleRunningCheckIn();
                    if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }

                    ret = 1;
                    int uploadseries_id = q2.value("uploadseries_id").toInt();

                    n->WriteLog(QString("Working on series [%1]").arg(uploadseries_id));

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
                    for(int i=0; i<uploadseries_filelist.size(); i++) {
                        if (uploadseries_filelist[i].trimmed() != "")
                            uploadseries_filelist[i] = upload_stagingpath + uploadseries_filelist[i];
                    }

                    performanceMetric perf;
                    /* insert the series */
                    io->ArchiveDICOMSeries(-1, matchingsubjectid, matchingstudyid, matchingseriesid, upload_subjectcriteria, upload_studycriteria, upload_seriescriteria, upload_destprojectid, upload_patientid, -1, "", "Uploaded to NiDB", uploadseries_filelist, perf);

                    i++;
                    double pct = static_cast<double>(i)/static_cast<double>(numSeries) * 100.0;
                    SetUploadStatus(upload_id, "archiving", pct);
                }

                io->AppendUploadLog(__FUNCTION__, QString("Completed archiving of upload [%1]").arg(upload_id));
            }
            else {
                error = true;
                io->AppendUploadLog(__FUNCTION__, QString("Error: No series found for upload [%1]").arg(upload_id));
            }

            if (error) {
                SetUploadStatus(upload_id, "archiveerror", -1.0);
            }
            else {
                SetUploadStatus(upload_id, "archivecomplete", 100.0);
                /* delete all of the source data and mark status as 'archivecomplete' */
                QString m;
                if (RemoveDir(upload_stagingpath, m))
                    io->AppendUploadLog(__FUNCTION__, QString("Removed upload staging directory [%1]").arg(upload_stagingpath));
                else
                    io->AppendUploadLog(__FUNCTION__, QString("Error: No series found for upload [%1]").arg(upload_id));
            }
        }
    }
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- SetUploadStatus -------------------------------- */
/* ---------------------------------------------------------- */
void moduleUpload::SetUploadStatus(int uploadid, QString status, double percent) {

    QSqlQuery q;

    q.prepare("update uploads set upload_status = :status, upload_statuspercent = :pct where upload_id = :uploadid");
    q.bindValue(":status", status);
    //if (percent < 0.0) q.bindValue(":pct", QVariant(QVariant::Double)); else q.bindValue(":pct", percent);
    if (percent < 0.0) q.bindValue(":pct", QVariant(QMetaType::fromType<double>())); else q.bindValue(":pct", percent);
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
}


/* ---------------------------------------------------------- */
/* --------- GetUploadStatus -------------------------------- */
/* ---------------------------------------------------------- */
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
