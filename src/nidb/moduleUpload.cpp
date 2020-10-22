/* ------------------------------------------------------------------------------
  NIDB moduleUpload.cpp
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

#include "moduleUpload.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- moduleUpload ----------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::moduleUpload(nidb *a)
{
    n = a;
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
int moduleUpload::Run() {
    n->WriteLog("Entering the upload module");

    QSqlQuery q;
    int ret(0);

    /* get list of uploads that are marked as uploadcomplete, with the upload details */
    q.prepare("select * from uploads where upload_status = 'uploadcomplete'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            ret = 1;
            int upload_id = q.value("upload_id").toInt();
            QString upload_source = q.value("upload_source").toString();
            QString upload_datapath = q.value("upload_datapath").toString();
            //int upload_destprojectid = q.value("upload_destprojectid").toInt();
            QString upload_modality = q.value("upload_modality").toString();
            //bool upload_guessmodality = q.value("upload_guessmodality").toBool();
            QString upload_subjectcriteria = q.value("upload_subjectcriteria").toString();
            QString upload_studycriteria = q.value("upload_studycriteria").toString();
            QString upload_seriescriteria = q.value("upload_seriescriteria").toString();

            /* update the status */
            QSqlQuery q2;
            q2.prepare("update uploads set upload_status = 'parsing' where upload_id = :uploadid");
            q2.bindValue(":uploadid", upload_id);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

            /* create a multilevel hash [subject][study][series][files] */
            QMap<QString, QMap<QString, QMap<QString, QStringList> > > fs;

            /* create the path for the upload data */
            QString uploadstagingpath = QString("%1/%2").arg(n->cfg["uploadstagingdir"]).arg(upload_id);

            /* create temporary directory in uploadstagingdir */
            QString m;
            if (!n->MakePath(uploadstagingpath, m)) {
                n->WriteLog(AppendUploadLog(upload_id, "Error creating directory [" + uploadstagingpath + "]  with message [" + m + "]"));
                ret = 1;

                /* update the status */
                q2.prepare("update uploads set upload_status = 'parsingerror' where upload_id = :uploadid");
                q2.bindValue(":uploadid", upload_id);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

                continue;
            }

            if ((upload_datapath == "") || (upload_datapath == "/") || (upload_datapath == "/etc") || (upload_datapath == "/bin") || (upload_datapath == "/root")) {
                n->WriteLog(AppendUploadLog(upload_id, QString("upload_datapath is invalid [%1] ").arg(upload_datapath)));

                /* update the status */
                q2.prepare("update uploads set upload_status = 'parsingerror' where upload_id = :uploadid");
                q2.bindValue(":uploadid", upload_id);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

                continue;
            }

            /* if modality is blank */
            if (upload_modality == "") {
                n->WriteLog(AppendUploadLog(upload_id, "Error. Modality was blank [" + upload_modality + "]"));
                ret = 1;

                /* update the status */
                q2.prepare("update uploads set upload_status = 'parsingerror' where upload_id = :uploadid");
                q2.bindValue(":uploadid", upload_id);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

                continue;
            }

            /* copy in files from uploadtmp or nfs to the uploadstagingdir */
            QString systemstring = QString("cp -ruv %1/* %2/").arg(upload_datapath).arg(uploadstagingpath);
            n->SystemCommand(systemstring);

            /* get information about the uploaded data from the uploadstagingdir (before unzipping any zip files) */
            int c;
            qint64 b;
            n->GetDirSizeAndFileCount(uploadstagingpath, c, b, true);
            n->WriteLog(AppendUploadLog(upload_id, QString("Upload directory [%1] contains [%2] files, and is [%3] bytes in size.").arg(uploadstagingpath).arg(c).arg(b)));

            /* unzip any files in the uploadstagingdir */
            n->WriteLog("Preparing to unzip the files located in [" + uploadstagingpath + "]");
            n->WriteLog(AppendUploadLog(upload_id, n->UnzipDirectory(uploadstagingpath, true)));
            n->WriteLog("Finished unzipping the files located in [" + uploadstagingpath + "]");

            /* get information about the uploaded data from the uploadstagingdir (after unzipping any zip files) */
            c = 0;
            b = 0;
            n->GetDirSizeAndFileCount(uploadstagingpath, c, b, true);
            n->WriteLog(AppendUploadLog(upload_id, QString("After 3 passes of UNZIPPING files, upload directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(uploadstagingpath).arg(c).arg(b)));

            /* get list of all files, and iterate through all of the files */
            QStringList files = n->FindAllFiles(uploadstagingpath, "*", true);
            foreach (QString f, files) {
                QString subject, study, series;

                /* get the file info */
                QHash<QString, QString> tags;
                if (n->GetImageFileTags(f, tags)) {
                    if ((tags["Modality"].toLower() == upload_modality.toLower()) || (upload_modality.toLower() == "auto")) {

                        /* subject matching criteria */
                        if (upload_subjectcriteria == "patientid")
                            subject = tags["PatientID"];
                        else if (upload_subjectcriteria == "namesexdob")
                            subject = tags["PatientName"] + "|" + tags["PatientSex"] + "|" + tags["PatientBirthDate"];
                        else
                            n->WriteLog(AppendUploadLog(upload_id, "Unspecified subject criteria [" + upload_subjectcriteria + "]"));

                        /* study matching criteria */
                        if (upload_studycriteria == "modalitystudydate")
                            study = tags["Modality"] + "|" + tags["StudyDateTime"];
                        else if (upload_studycriteria == "studyuid")
                            study = tags["StudyInstanceUID"];
                        else
                            n->WriteLog(AppendUploadLog(upload_id, "Unspecified study criteria [" + upload_studycriteria + "]"));

                        /* series matching criteria */
                        if (upload_seriescriteria == "seriesnum")
                            series = tags["SeriesNumber"];
                        else if (upload_seriescriteria == "seriesdate")
                            series = tags["SeriesDateTime"];
                        else if (upload_seriescriteria == "seriesuid")
                            series = tags["SeriesInstanceUID"];
                        else
                            n->WriteLog(AppendUploadLog(upload_id, "Unspecified series criteria [" + upload_seriescriteria + "]"));

                        /* store the file in the appropriate group */
                        fs[subject][study][series].append(f);
                    }
                    else {
                        n->WriteLog(AppendUploadLog(upload_id, "Valid file [" + f + "] but not the modality [" + upload_modality + "] we're looking for [" + tags["Modality"] + "]"));
                        fs["nonmatch"]["nonmatch"]["nonmatch"].append(f);
                    }
                }
                else {
                    /* the file is not readable */
                    fs["unreadable"]["unreadable"]["unreadable"].append(f);
                    n->WriteLog(AppendUploadLog(upload_id, "Unable to read file [" + f + "]"));
                }
            }


            /* ---------- iterate through the subjects ---------- */
            for(QMap<QString, QMap<QString, QMap<QString, QStringList> > >::iterator a = fs.begin(); a != fs.end(); ++a) {
                QString subject = a.key();

                /* get the uploadsubject_id */
                int subjectid(0);

                if (upload_subjectcriteria == "patientid") {
                    /* get subjectid by PatientID field */
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
                        n->WriteLog(AppendUploadLog(upload_id, QString("Found subjectid [%1]").arg(subjectid)));
                    }
                    else {
                        /* ... otherwise create a new subject */
                        q3.prepare("insert into upload_subjects (upload_id, uploadsubject_patientid) values (:uploadid, :patientid)");
                        q3.bindValue(":uploadid", upload_id);
                        q3.bindValue(":patientid", PatientID);
                        n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                        subjectid = q3.lastInsertId().toInt();
                        n->WriteLog(AppendUploadLog(upload_id, QString("subjectid [%1] created").arg(subjectid)));
                    }
                }
                else if (upload_subjectcriteria == "namesexdob") {
                    /* get subjectid by PatientName/PatientSex/PatientBirthDate */
                    QStringList parts = subject.split("|");
                    QString PatientName = parts[0];
                    QString PatientSex = parts[1];
                    QString PatientBirthDate = parts[2];

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
                        n->WriteLog(AppendUploadLog(upload_id, QString("Found subjectid [%1]").arg(subjectid)));
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
                        n->WriteLog(AppendUploadLog(upload_id, QString("subjectid [%1] created").arg(subjectid)));
                    }
                }
                else
                    n->WriteLog("Unspecified subject criteria [" + upload_subjectcriteria + "]. That's weird it would show up here...");

                /* ---------- iterate through the studies ---------- */
                for(QMap<QString, QMap<QString, QStringList> >::iterator b = fs[subject].begin(); b != fs[subject].end(); ++b) {
                    QString study = b.key();

                    /* get the uploadstudy_id */
                    int studyid(0);

                    if (upload_studycriteria == "modalitystudydate") {
                        //n->WriteLog("Checkpoint A.2");
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
                            n->WriteLog(AppendUploadLog(upload_id, QString("Found studyid [%1]").arg(studyid)));
                        }
                        else {
                            /* ... otherwise create a new study */
                            q3.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_date, uploadstudy_modality) values (:subjectid, :studydatetime, :modality)");
                            q3.bindValue(":subjectid", subjectid);
                            q3.bindValue(":studydatetime", StudyDateTime);
                            q3.bindValue(":modality", Modality);
                            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            studyid = q3.lastInsertId().toInt();
                            n->WriteLog(AppendUploadLog(upload_id, QString("studyid [%1] created").arg(studyid)));
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
                            n->WriteLog(AppendUploadLog(upload_id, QString("Found studyid [%1]").arg(studyid)));
                        }
                        else {
                            /* ... otherwise create a new study */
                            q3.prepare("insert into upload_studies (uploadsubject_id, uploadstudy_instanceuid) values (:subjectid, :studyinstanceuid)");
                            q3.bindValue(":subjectid", subjectid);
                            q3.bindValue(":studyinstanceuid", StudyInstanceUID);
                            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            studyid = q3.lastInsertId().toInt();
                            n->WriteLog(AppendUploadLog(upload_id, QString("studyid [%1] created").arg(studyid)));
                        }
                    }
                    else
                        n->WriteLog(AppendUploadLog(upload_id, "Unspecified study criteria [" + upload_studycriteria + "]. That's weird it would show up here..."));

                    /* ---------- iterate through the series ---------- */
                    for(QMap<QString, QStringList>::iterator c = fs[subject][study].begin(); c != fs[subject][study].end(); ++c) {
                        QString series = c.key();

                        /* get uploadseries_id */
                        int seriesid(0);

                        if (upload_seriescriteria == "seriesnum") {
                            /* get seriesid from SeriesNumber field */
                            QString SeriesNumber = series;

                            QSqlQuery q3;
                            /* check if the studyid exists ... */
                            q3.prepare("select uploadseries_id from upload_series where uploadstudy_id = :studyid and uploadseries_num = :seriesnum");
                            q3.bindValue(":studyid", studyid);
                            q3.bindValue(":seriesnum", SeriesNumber);
                            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            if (q3.size() > 0) {
                                q3.first();
                                seriesid = q3.value("uploadseries_id").toInt();
                                n->WriteLog(AppendUploadLog(upload_id, QString("Found seriesid [%1]").arg(seriesid)));
                            }
                            else {
                                /* ... otherwise create a new series */
                                q3.prepare("insert into upload_series (uploadstudy_id, uploadseries_num) values (:studyid, :seriesnum)");
                                q3.bindValue(":studyid", studyid);
                                q3.bindValue(":seriesnum", SeriesNumber);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                                seriesid = q3.lastInsertId().toInt();
                                n->WriteLog(AppendUploadLog(upload_id, QString("seriesid [%1] created").arg(seriesid)));
                            }
                        }
                        else if (upload_seriescriteria == "seriesdate") {
                            /* get seriesid using SeriesDateTime field */
                            QString SeriesDateTime = series;

                            QSqlQuery q3;
                            /* check if the seriesid already exists ... */
                            q3.prepare("select uploadseries_id from upload_series where uploadstudy_id = :studyid and uploadseries_date = '" + SeriesDateTime + "'");
                            q3.bindValue(":studyid", studyid);
                            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            if (q3.size() > 0) {
                                q3.first();
                                seriesid = q3.value("uploadseries_id").toInt();
                                n->WriteLog(AppendUploadLog(upload_id, QString("Found seriesid [%1]").arg(seriesid)));
                            }
                            else {
                                /* ... otherwise create a new series */
                                q3.prepare("insert into upload_series (uploadstudy_id, uploadseries_date) values (:studyid, '" + SeriesDateTime + "')");
                                q3.bindValue(":studyid", studyid);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                                seriesid = q3.lastInsertId().toInt();
                                n->WriteLog(AppendUploadLog(upload_id, QString("seriesid [%1] created").arg(seriesid)));
                            }
                        }
                        else if (upload_seriescriteria == "seriesuid") {
                            /* get seriesid using SeriesInstanceUID field */
                            QString SeriesInstanceUID = series;

                            QSqlQuery q3;
                            /* check if the seriesid already exists ... */
                            q3.prepare("select uploadseries_id from upload_series where uploadstudy_id = :studyid and uploadseries_instanceuid = :seriesinstanceuid");
                            q3.bindValue(":studyid", studyid);
                            q3.bindValue(":seriesinstanceuid", SeriesInstanceUID);
                            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            if (q3.size() > 0) {
                                q3.first();
                                seriesid = q3.value("uploadseries_id").toInt();
                                n->WriteLog(AppendUploadLog(upload_id, QString("Found seriesid [%1]").arg(seriesid)));
                            }
                            else {
                                /* ... otherwise create a new series */
                                q3.prepare("insert into upload_series (uploadstudy_id, uploadseries_instanceuid) values (:studyid, :seriesinstanceuid)");
                                q3.bindValue(":studyid", studyid);
                                q3.bindValue(":seriesinstanceuid", SeriesInstanceUID);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                                seriesid = q3.lastInsertId().toInt();
                                n->WriteLog(AppendUploadLog(upload_id, QString("seriesid [%1] created").arg(seriesid)));
                            }
                        }
                        else {
                            n->WriteLog(AppendUploadLog(upload_id, "Unspecified seriesid criteria [" + upload_seriescriteria + "]. That's weird it would show up here..."));
                        }

                        QStringList files = fs[subject][study][series];
                        int numfiles = files.size();
                        //n->WriteLog(AppendUploadLog(upload_id, QString("numfiles [%1]   numfiles [%2]").arg(files.size()).arg(numfiles)));

                        /* remove the prefix for the files */
                        QStringList filesNoPrefix;
                        for (int ii=0; ii<files.size(); ii++) {
                            QString str = files[ii];
                            str.replace(str.indexOf(uploadstagingpath), uploadstagingpath.size(), "");
                            filesNoPrefix.append(str);
                        }

                        /* we've arrived at a series, so let's put it into the database */
                        /* get tags from first file in the list to populate the subject/study/series info not included in the criteria matching */
                        QHash<QString, QString> tags;
                        if (n->GetImageFileTags(files[0], tags)) {

                            QSqlQuery q3;

                            /* don't overwrite the tags in the databse that were used to group the subject/study/series */

                            /* update subject details */
                            if (upload_subjectcriteria == "patientid") {
                                /* update all subject details except PatientID */
                                q3.prepare("update ignore upload_subjects set uploadsubject_name = :name, uploadsubject_sex = :sex, uploadsubject_dob = :dob where uploadsubject_id = :subjectid");
                                q3.bindValue(":name", tags["PatientName"]);
                                q3.bindValue(":sex", tags["PatientSex"]);
                                q3.bindValue(":dob", tags["PatientBirthDate"]);
                                q3.bindValue(":subjectid", subjectid);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            }
                            else if (upload_subjectcriteria == "namesexdob") {
                                /* update all subject details except PatientName/Sex/BirthDate */
                                q3.prepare("update ignore upload_subjects set uploadsubject_patientid = :patientid where uploadsubject_id = :subjectid");
                                q3.bindValue(":name", tags["PatientID"]);
                                q3.bindValue(":subjectid", subjectid);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            }
                            else n->WriteLog(AppendUploadLog(upload_id, "Unspecified subject criteria [" + upload_subjectcriteria + "]"));


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
                            else n->WriteLog(AppendUploadLog(upload_id, "Unspecified study criteria [" + upload_studycriteria + "]"));


                            /* update series details */
                            if (upload_seriescriteria == "seriesnum") {
                                /* update all series details except SeriesNumber */
                                q3.prepare("update ignore upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_date = :date, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_instanceuid = :seriesinstanceuid, uploadseries_filelist = :files where uploadseries_id = :seriesid");
                                q3.bindValue(":desc", tags["SeriesDescription"]);
                                q3.bindValue(":protocol", tags["ProtocolName"]);
                                q3.bindValue(":date", tags["SeriesDateTime"]);
                                q3.bindValue(":NumberOfFiles", numfiles);
                                if (tags["RepetitionTime"] == "") q3.bindValue(":tr", QVariant(QVariant::Double)); else q3.bindValue(":tr", tags["RepetitionTime"]);
                                if (tags["EchoTime"] == "") q3.bindValue(":te", QVariant(QVariant::Double)); else q3.bindValue(":te", tags["EchoTime"]);
                                if (tags["SpacingBetweenSlices"] == "") q3.bindValue(":slicespacing", QVariant(QVariant::Double)); else q3.bindValue(":slicespacing", tags["SpacingBetweenSlices"]);
                                if (tags["SliceThickness"] == "") q3.bindValue(":slicethickness", QVariant(QVariant::Double)); else q3.bindValue(":slicethickness", tags["SliceThickness"]);
                                q3.bindValue(":rows", tags["Rows"]);
                                q3.bindValue(":cols", tags["Columns"]);
                                q3.bindValue(":seriesinstanceuid", tags["SeriesInstanceUID"]);
                                q3.bindValue(":files", filesNoPrefix.join(","));
                                q3.bindValue(":seriesid", seriesid);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            }
                            else if (upload_seriescriteria == "seriesdate") {
                                /* update all series details except SeriesDateTime */
                                q3.prepare("update ignore upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_num = :num, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_instanceuid = :seriesinstanceuid, uploadseries_filelist = :files where uploadseries_id = :seriesid");
                                q3.bindValue(":desc", tags["SeriesDescription"]);
                                q3.bindValue(":protocol", tags["ProtocolName"]);
                                q3.bindValue(":num", tags["SeriesNumber"]);
                                q3.bindValue(":NumberOfFiles", numfiles);
                                if (tags["RepetitionTime"] == "") q3.bindValue(":tr", QVariant(QVariant::Double)); else q3.bindValue(":tr", tags["RepetitionTime"]);
                                if (tags["EchoTime"] == "") q3.bindValue(":te", QVariant(QVariant::Double)); else q3.bindValue(":te", tags["EchoTime"]);
                                if (tags["SpacingBetweenSlices"] == "") q3.bindValue(":slicespacing", QVariant(QVariant::Double)); else q3.bindValue(":slicespacing", tags["SpacingBetweenSlices"]);
                                if (tags["SliceThickness"] == "") q3.bindValue(":slicethickness", QVariant(QVariant::Double)); else q3.bindValue(":slicethickness", tags["SliceThickness"]);
                                q3.bindValue(":rows", tags["Rows"]);
                                q3.bindValue(":cols", tags["Columns"]);
                                q3.bindValue(":seriesinstanceuid", tags["SeriesInstanceUID"]);
                                q3.bindValue(":files", filesNoPrefix.join(","));
                                q3.bindValue(":seriesid", seriesid);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            }
                            else if (upload_seriescriteria == "seriesuid") {
                                /* update all series details except SeriesInstanceUID */
                                q3.prepare("update ignore upload_series set uploadseries_desc = :desc, uploadseries_protocol = :protocol, uploadseries_num = :num, uploadseries_date = :date, uploadseries_numfiles = :NumberOfFiles, uploadseries_tr = :tr, uploadseries_te = :te, uploadseries_slicespacing = :slicespacing, uploadseries_slicethickness = :slicethickness, uploadseries_rows = :rows, uploadseries_cols = :cols, uploadseries_filelist = :files where uploadseries_id = :seriesid");
                                q3.bindValue(":desc", tags["SeriesDescription"]);
                                q3.bindValue(":protocol", tags["ProtocolName"]);
                                q3.bindValue(":date", tags["SeriesDateTime"]);
                                q3.bindValue(":num", tags["SeriesNumber"]);
                                q3.bindValue(":NumberOfFiles", numfiles);
                                if (tags["RepetitionTime"] == "") q3.bindValue(":tr", QVariant(QVariant::Double)); else q3.bindValue(":tr", tags["RepetitionTime"]);
                                if (tags["EchoTime"] == "") q3.bindValue(":te", QVariant(QVariant::Double)); else q3.bindValue(":te", tags["EchoTime"]);
                                if (tags["SpacingBetweenSlices"] == "") q3.bindValue(":slicespacing", QVariant(QVariant::Double)); else q3.bindValue(":slicespacing", tags["SpacingBetweenSlices"]);
                                if (tags["SliceThickness"] == "") q3.bindValue(":slicethickness", QVariant(QVariant::Double)); else q3.bindValue(":slicethickness", tags["SliceThickness"]);
                                q3.bindValue(":rows", tags["Rows"]);
                                q3.bindValue(":cols", tags["Columns"]);
                                q3.bindValue(":files", filesNoPrefix.join(","));
                                q3.bindValue(":seriesid", seriesid);
                                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                            }
                            else n->WriteLog(AppendUploadLog(upload_id, "Unspecified series criteria [" + upload_seriescriteria + "]"));

                        }
                        else {
                            n->WriteLog(AppendUploadLog(upload_id, "Error reading file [" + files[0] + "]. That's weird it would show up here..."));
                        }
                    }
                }
            }

            /* update the status */
            QSqlQuery q3;
            q3.prepare("update uploads set upload_status = 'parsingcomplete' where upload_id = :uploadid");
            q3.bindValue(":uploadid", upload_id);
            n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

        } /* end while */
    }

    n->WriteLog("Leaving the upload module");
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- AppendUploadLog -------------------------------- */
/* ---------------------------------------------------------- */
QString moduleUpload::AppendUploadLog(int uploadid, QString msg) {

    QSqlQuery q;

    q.prepare("update uploads set upload_log = concat(upload_log, :msg) where upload_id = :uploadid");
    q.bindValue(":msg", msg);
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);

    return msg;
}
