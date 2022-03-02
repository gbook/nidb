/* ------------------------------------------------------------------------------
  NIDB moduleFileIO.cpp
  Copyright (C) 2004 - 2021
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

#include "moduleFileIO.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- moduleFileIO ----------------------------------- */
/* ---------------------------------------------------------- */
moduleFileIO::moduleFileIO(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleFileIO ---------------------------------- */
/* ---------------------------------------------------------- */
moduleFileIO::~moduleFileIO()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleFileIO::Run() {
    n->WriteLog("Entering the fileio module");

    /* get list of things to delete */
    QSqlQuery q("select * from fileio_requests where request_status = 'pending'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() > 0) {
        int i = 0;
        while (q.next()) {
            n->ModuleRunningCheckIn();
            bool found = false;
            QString msg;
            i++;

            int requestid = q.value("fileiorequest_id").toInt();
            QString fileio_operation = q.value("fileio_operation").toString().trimmed();
            QString data_type = q.value("data_type").toString().trimmed();
            int data_id = q.value("data_id").toInt();
            QString modality = q.value("modality").toString().trimmed();
            QString data_destination = q.value("data_destination").toString().trimmed();
            int rearchiveprojectid = q.value("rearchiveprojectid").toInt();
            QString dicomtags = q.value("anonymize_fields").toString().trimmed();
            QString username = q.value("username").toString().trimmed();
            QString merge_ids = q.value("merge_ids").toString().trimmed();
            QString merge_method = q.value("merge_method").toString().trimmed();
            QString merge_name = q.value("merge_name").toString().trimmed();
            QString merge_dob = q.value("merge_dob").toString().trimmed();
            QString merge_sex = q.value("merge_sex").toString().trimmed();
            QString merge_ethnicity1 = q.value("merge_ethnicity1").toString().trimmed();
            QString merge_ethnicity2 = q.value("merge_ethnicity2").toString().trimmed();
            QString merge_guid = q.value("merge_guid").toString().trimmed();
            QString merge_altuids = q.value("merge_altuids").toString().trimmed();

            /* get the current status of this fileio request, make sure no one else is processing it, and mark it as being processed if not */
            QString status = GetIORequestStatus(requestid);
            n->WriteLog(QString("Status for request_id [%1] is [%2]").arg(requestid).arg(status));
            if (status == "pending") {
                /* set the status, if something is wrong, skip this request */
                if (!SetIORequestStatus(requestid, "pending")) {
                    n->WriteLog(QString("Unable to set fileiorequest_status to [%1]").arg(status));
                    continue;
                }
            }
            else {
                /* skip this IO request... the status was changed outside of this instance of the program */
                n->WriteLog(QString("The status for this fileiorequest [%1] has been changed to [%2]. Skipping.").arg(requestid).arg(status));
                continue;
            }

            n->WriteLog(QString(" ----- FileIO operation (%1 of %2) [%3] on datatype [%4] ----- ").arg(i).arg(q.size()).arg(fileio_operation).arg(data_type));

            if (fileio_operation == "rechecksuccess") {
                if (data_type == "analysis") {
                    found = RecheckSuccess(data_id, msg);
                }
            }
            else if (fileio_operation == "createlinks") {
                if (data_type == "analysis") {
                    found = CreateLinks(data_id, n->cfg["mountdir"] + data_destination, msg);
                }
            }
            else if (fileio_operation == "copy") {
                    if (data_type == "analysis") {
                        found = CopyAnalysis(data_id, n->cfg["mountdir"] + data_destination, msg);
                    }
                }
            else if (fileio_operation == "delete") {
                if (data_type == "pipeline") { found = DeletePipeline(data_id, msg); }
                else if (data_type == "analysis") { found = DeleteAnalysis(data_id, msg); }
                //else if (data_type == "groupanalysis") { found = DeleteGroupAnalysis(data_id); }
                else if (data_type == "subject") { found = DeleteSubject(data_id, username, msg); }
                else if (data_type == "study") { found = DeleteStudy(data_id, msg); }
                else if (data_type == "series") { found = DeleteSeries(data_id, modality, msg); }
            }
            else if (fileio_operation == "detach") {
            }
            else if (fileio_operation == "move") {
                if (data_type == "study") { found = MoveStudyToSubject(data_id, data_destination, -1, msg); }
            }
            else if (fileio_operation == "merge") {
                if (data_type == "subject") { found = MergeSubjects(data_id, merge_ids, merge_name, merge_dob, merge_sex, merge_ethnicity1, merge_ethnicity2, merge_guid, merge_altuids, msg); }
                if (data_type == "study") { found = MergeStudies(data_id, merge_ids, merge_method, msg); }
            }
            else if (fileio_operation == "anonymize") {
                //found = AnonymizeSeries(requestid, data_id, modality, dicomtags);
            }
            else if (fileio_operation == "rearchive") {
                if (data_type == "study") {
                    found = RearchiveStudy(data_id, false, msg);
                }
                else if (data_type == "subject") {
                    found = RearchiveSubject(data_id, false, rearchiveprojectid, msg);
                }
            }
            else if (fileio_operation == "rearchiveidonly") {
                if (data_type == "study") {
                    found = RearchiveStudy(data_id, true, msg);
                }
                if (data_type == "subject") {
                    found = RearchiveSubject(data_id, true, rearchiveprojectid, msg);
                }
            }
            else {
                /* unknown fileio_operation, so set it to 'error' */
                SetIORequestStatus(requestid, "error", msg);
            }

            /* all finished with the request, so set the status */
            if (found) {
                /* set the status of the delete_request to complete */
                SetIORequestStatus(requestid, "complete", msg);
            }
            else {
                /* some error occurred, so set it to 'error' */
                SetIORequestStatus(requestid, "error", msg);
            }
            n->WriteLog("File IO operation finished, with message from function [" + msg + "]");
        }
        n->WriteLog("Finished performing file IO");
    }
    else {
        n->WriteLog("Nothing to do");
        return 0;
    }

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- GetIORequestStatus ----------------------------- */
/* ---------------------------------------------------------- */
QString moduleFileIO::GetIORequestStatus(int requestid) {
    QSqlQuery q;
    q.prepare("select request_status from fileio_requests where fileiorequest_id = :id");
    q.bindValue(":id", requestid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    QString status = q.value("request_status").toString();
    return status;
}


/* ---------------------------------------------------------- */
/* --------- SetIORequestStatus ----------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::SetIORequestStatus(int requestid, QString status, QString msg) {

    if (((status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (requestid > 0)) {
        if (msg.trimmed() == "") {
            QSqlQuery q;
            q.prepare("update fileio_requests set request_status = :status where fileiorequest_id = :id");
            q.bindValue(":id", requestid);
            q.bindValue(":status", status);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else {
            QSqlQuery q;
            q.prepare("update fileio_requests set request_status = :status, request_message = :msg where fileiorequest_id = :id");
            q.bindValue(":id", requestid);
            q.bindValue(":msg", msg);
            q.bindValue(":status", status);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        return true;
    }
    else {
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- RecheckSuccess --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::RecheckSuccess(qint64 analysisid, QString &msg) {
    n->WriteLog(QString("In RecheckSuccess(%1)").arg(analysisid));

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

    /* check the analysispath to see if the required file(s) exist
     * get a list of expected files from the database */
    QSqlQuery q;
    q.prepare("select pipeline_completefiles from pipelines a left join analysis b on a.pipeline_id = b.pipeline_id where b.analysis_id = :analysisid");
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    QString completefiles = q.value("pipeline_completefiles").toString().trimmed();
    QStringList filelist = completefiles.split(',');

    int iscomplete = 1;
    for(int i=0; i<filelist.size(); i++) {
        QString filepath = a.analysispath + "/" + filelist[i];
        n->WriteLog(QString("Checking for [" + filepath + "]"));
        QFile f(filepath);
        if (f.exists()) {
            n->WriteLog(QString("File [" + filepath + "] exists"));
        }
        else {
            n->WriteLog(QString("File [" + filepath + "] does not exist"));
            iscomplete = 0;
            break;
        }
    }

    q.prepare("update analysis set analysis_iscomplete = :iscomplete where analysis_id = :analysisid");
    q.bindValue(":iscomplete", iscomplete);
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysisrecheck", "Analysis success recheck finished");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateLinks ------------------------------------ */
/* ---------------------------------------------------------- */
bool moduleFileIO::CreateLinks(qint64 analysisid, QString destination, QString &msg) {
    n->WriteLog(QString("In CreateLinks(%1, %2)").arg(analysisid).arg(destination));

    /* check if destination is somewhat valid */
    if ((destination == "") || (destination == ".") || (destination == "..") || (destination == "/") || (destination.contains("//"))) {
        msg = "Invalid destination [" + destination + "]";
        return false;
    }

    analysis a(analysisid, n, true); /* get the analysis info */
    if (!a.isValid) {
        msg = "analysis was not valid: [" + a.msg + "]";
        return false;
    }

    if (n->MakePath(destination, msg)) {
        QString systemstring = QString("cd %1; ln -s %2 %3%4; chmod 777 %5%6").arg(destination).arg(a.analysispath).arg(a.uid).arg(a.studynum).arg(a.uid).arg(a.studynum);
        n->WriteLog(n->SystemCommand(systemstring));
        n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysiscreatelink", "Analysis links created");
        return true;
    }
    else {
        msg += "Unable to create destination directory";
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- CopyAnalysis ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::CopyAnalysis(qint64 analysisid, QString destination, QString &msg) {
    n->WriteLog(QString("In CopyAnalysis(%1, %2)").arg(analysisid).arg(destination));

    /* check if destination is somewhat valid */
    if ((destination == "") || (destination == ".") || (destination == "..") || (destination == "/") || (destination.contains("//"))) {
        msg = "Invalid destination [" + destination + "]";
        return false;
    }

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

    destination = QString("%1/%2%3").arg(destination).arg(a.uid).arg(a.studynum);
    if (n->MakePath(destination, msg)) {
        QString systemstring = QString("rsync -az %1/* %2").arg(a.analysispath).arg(destination);
        n->WriteLog(QString("About to run the following command[" + systemstring + "]"));
        n->WriteLog(n->SystemCommand(systemstring));
        n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysiscopy", "Analysis copied");
        return true;
    }
    else {
        msg += "Unable to create destination directory";
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- DeleteAnalysis --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteAnalysis(qint64 analysisid, QString &msg) {
    n->WriteLog("In DeleteAnalysis()");

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "Analysis was not valid: [" + a.msg + "]"; return false; }

    /* attempt to kill the SGE job, if its running */
    if (a.jobid > 0) {
        QString systemstring = QString("/sge/sge-root/bin/lx24-amd64/./qdelete %1").arg(a.jobid);
        n->WriteLog(n->SystemCommand(systemstring, true));
    }
    else {
        n->WriteLog(QString("SGE job id [%1] is not valid. Not attempting to kill the job").arg(a.jobid));
    }

    n->WriteLog("Analysispath: [" + a.analysispath + "]");

    bool okToDeleteDBEntries = false;

    if (QDir(a.analysispath).exists()) {
		qint64 c;
        qint64 b;
        n->GetDirSizeAndFileCount(a.analysispath, c, b, true);

        n->WriteLog(QString("Going to remove [%1] files and directories from [%2]").arg(c).arg(a.analysispath));
        if (n->RemoveDir(a.analysispath, msg)) {
            /* QDir.remove worked */
            n->WriteLog("Analysispath removed");
            okToDeleteDBEntries = true;
        }
        else {
            QString systemstring = QString("sudo rm -rfv %1").arg(a.analysispath);
            n->WriteLog("Not all files deleted. Deleting remaining files using sudo.");
            n->WriteLog(n->SystemCommand(systemstring));

            QDir d(a.analysispath);
            if (d.exists()) {
                n->WriteLog("Datapath [" + a.analysispath + "] still exists, even after sudo rm -rf");

                QSqlQuery q;
                q.prepare("update analysis set analysis_statusmessage = 'Analysis directory not deleted. Manually delete the directory and then delete from this webpage again' where analysis_id = :analysisid");
                q.bindValue(":analysisid", analysisid);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

                n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysisdeleteerror", "Analysis directory not deleted. Probably because permissions have changed and NiDB does not have permission to delete the directory [" + a.analysispath + "]");
                return false;
            }
            else {
                okToDeleteDBEntries = true;
            }
        }
    }
    else {
        n->WriteLog("Path [" + a.analysispath + "] did not exist. Did not attempt to delete");
        okToDeleteDBEntries = true;
    }

    if (okToDeleteDBEntries) {
        /* remove the database entries */
        QSqlQuery q;
        q.prepare("delete from analysis_data where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from analysis_results where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from analysis_history where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from analysis where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeletePipeline --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeletePipeline(int pipelineid, QString &msg) {
    n->WriteLog("In DeletePipeline()");

    /* get list of analyses associated with this pipeline */
    QSqlQuery q;
    q.prepare("select analysis_id from analysis where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() > 0) {
        while (q.next()) {
            QString msg;
            int analysisid = q.value("analysis_id").toInt();
            if (!DeleteAnalysis(analysisid, msg))
                n->WriteLog(QString("Attempted to delete analysis [%1], but received error [%2]").arg(analysisid).arg(msg));
        }
    }
    else {
        msg = "No analyses to delete for this pipeline";
        n->WriteLog("No analyses to delete for this pipeline");
    }

    /* delete the actual pipeline entry */
    q.prepare("delete from pipelines where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- DeleteSubject ---------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteSubject(int subjectid, QString username, QString &msg) {
    QSqlQuery q;

    subject s(subjectid, n); /* get the subject info */
    if (!s.valid()) { msg = "Subject was not valid: [" + s.msg() + "]"; return false; }

    QString newpath = QString("%1/%2-%3").arg(n->cfg["deleteddir"]).arg(s.UID()).arg(n->GenerateRandomString(10));
    QDir d;

    if (s.path() != "") {
        if (d.exists(s.path())) {
            if (d.rename(s.path(), newpath)) {
                msg = n->WriteLog(QString("Moved [%1] to [%2]").arg(s.path()).arg(newpath));
            }
            else {
                msg = QString("Error in moving [%1] to [%2]").arg(s.path()).arg(newpath);
                n->WriteLog(msg);
                return false;
            }
        }
        else {
            n->WriteLog(QString("Subject path on disk [" + s.path() + "] does not exist"));
        }
    }
    else {
        n->WriteLog(QString("Subject has no path on disk"));
    }

    /* remove all database entries about this subject:
       TABLES: subjects, subject_altuid, subject_relation, studies, *_series, enrollment, family_members, mostrecent */
    q.prepare("delete from mostrecent where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from family_members where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subject_relation where subjectid1 = :subjectid or subjectid2 = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subject_altuid where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* get enrollment_ids for the subject */
    q.prepare("select enrollment_id from enrollment where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QStringList enrollmentIDs;
    if (q.size() > 0) {
        while (q.next()) {
            enrollmentIDs.append(QString("%1").arg(q.value("enrollment_id").toInt()));
        }

        /* delete all series */
        q.prepare("delete from mr_series where study_id in (select study_id from studies where enrollment_id in (" + enrollmentIDs.join(",") + "))");
        q.bindValue(":subjectid", subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from et_series where study_id in (select study_id from studies where enrollment_id in (" + enrollmentIDs.join(",") + "))");
        q.bindValue(":subjectid", subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from eeg_series where study_id in (select study_id from studies where enrollment_id in (" + enrollmentIDs.join(",") + "))");
        q.bindValue(":subjectid", subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* delete all studies */
        q.prepare("delete from studies where enrollment_id in (" + enrollmentIDs.join(",") + ")");
        q.bindValue(":subjectid", subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* delete all enrollments */
        q.prepare("delete from enrollment where subject_id = :subjectid");
        q.bindValue(":subjectid", subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }
    else {
        n->WriteLog("No enrollments found");
    }

    // delete the subject
    q.prepare("delete from subjects where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    n->InsertSubjectChangeLog(username, s.UID(), "", "obliterate", msg);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeleteStudy ------------------------------------ */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteStudy(int studyid, QString &msg) {
    n->WriteLog("In DeleteStudy()");

    QSqlQuery q;
    study s(studyid, n); /* get the study info */
    if (!s.valid()) { msg = "Study was not valid: [" + s.msg() + "]"; return false; }
    QString modality = s.modality().toLower();

    QString newpath = QString("%1/%2-%3-%4").arg(n->cfg["deleteddir"]).arg(s.UID()).arg(s.studyNum()).arg(n->GenerateRandomString(10));
    QDir d;
    if(d.rename(s.path(), newpath)) {
        n->WriteLog(QString("Moved [%1] to [%2]").arg(s.path()).arg(newpath));

        // move all archive data to the deleted directory
        // delete all series
        q.prepare(QString("delete from %1_series where study_id = :studyid").arg(modality));
        q.bindValue(":studyid", studyid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        // delete all studies
        q.prepare("delete from studies where study_id = :studyid");
        q.bindValue(":studyid", studyid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }
    else {
        msg = QString("Error in moving [%1] to [%2]").arg(s.path()).arg(newpath);
        n->WriteLog(msg);
        return false;
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeleteSeries ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteSeries(int seriesid, QString modality, QString &msg) {
    n->WriteLog("In DeleteSeries()");
    modality = modality.toLower();

    QSqlQuery q;
    series s(seriesid, modality, n); /* get the series info */
    if (!s.isValid) { msg = "Series was not valid: [" + s.msg + "]"; return false; }

    QString newpath = QString("%1/%2-%3-%4-%5").arg(n->cfg["deleteddir"]).arg(s.uid).arg(s.studynum).arg(s.seriesnum).arg(n->GenerateRandomString(10));
    QDir d;
    if(d.rename(s.seriespath, newpath)) {
        n->WriteLog(QString("Moved [%1] to [%2]").arg(s.seriespath).arg(newpath));

        QString sqlstring = QString("delete from %1_series where %1series_id = :seriesid").arg(modality);
        q.prepare(sqlstring);
        q.bindValue(":seriesid", seriesid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    }
    else {
        msg = QString("Error in moving series [%1] to [%2]").arg(s.seriespath).arg(newpath);
        n->WriteLog(msg);
        return false;
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- RearchiveStudy --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::RearchiveStudy(int studyid, bool matchidonly, QString &msg) {
    n->WriteLog("In RearchiveStudy()");

    QStringList msgs;
    QSqlQuery q;
    study s(studyid, n); /* get the series info */
    if (!s.valid()) { msg = "Study was not valid: [" + s.msg() + "]"; return false; }

    /* get instanceid */
    q.prepare("select instance_id from projects where project_id = :projectid");
    q.bindValue(":projectid", s.projectRowID());
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int instanceid;
    if (q.size() > 0) {
        q.first();
        instanceid = q.value("instance_id").toInt();
    }
    else {
        msg = "Invalid instance ID";
        return false;
    }

    /* create an import request, based on the current instance, project, and site & get next import ID */
    q.prepare("insert into import_requests (import_datatype, import_datetime, import_status, import_equipment, import_siteid, import_projectid, import_instanceid, import_uuid, import_anonymize, import_permanent, import_matchidonly) values ('dicom',now(),'uploading','',null,:projectid,:instanceid,'',null,null,:matchidonly)");
    q.bindValue(":projectid", s.projectRowID());
    q.bindValue(":instanceid", instanceid);
    q.bindValue(":matchidonly", matchidonly);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int uploadid = q.lastInsertId().toInt();

    /* create an import request dir */
    QString outpath = QString("%1/%2").arg(n->cfg["uploadeddir"]).arg(uploadid);
    QDir d;
    if (!d.mkpath(outpath)) {
        msg = "Unable to create outpath [" + outpath + "]";
        return false;
    }

    /* move all DICOMs to the incomingdir */
    QString m;
    if (!n->MoveAllFiles(s.path(),"*.dcm",outpath, m)) {
        msgs << n->WriteLog(QString("Error moving DICOM files from archivedir to incomingdir [%1]").arg(m));
    }
    else {
        n->WriteLog(QString("Moved all .dcm files from [%1] to [%2]").arg(s.path()).arg(outpath));
    }

    /* move the old study to the deleted directory */
    QString newpath = QString("%1/%2-%3-%4").arg(n->cfg["deleteddir"]).arg(s.UID()).arg(s.studyNum()).arg(n->GenerateRandomString(10));
    QDir d2;
    if(d2.rename(s.path(), newpath)) {
        n->WriteLog(QString("Moved [%1] to [%2]").arg(s.path()).arg(newpath));
    }
    else {
        n->WriteLog(QString("Unable to move [%1] to [%2]").arg(s.path()).arg(newpath));
    }

    /* update the import_requests table with the new uploadid */
    q.prepare("update import_requests set import_status = 'pending' where importrequest_id = :uploadid");
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove any reference to this study from the (enrollment, study) tables
     * delete all series */
    q.prepare("delete from mr_series where study_id = :studyid");
    q.bindValue(":studyid", studyid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* delete the study */
    q.prepare("delete from studies where study_id = :studyid");
    q.bindValue(":studyid", studyid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- RearchiveSubject ------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::RearchiveSubject(int subjectid, bool matchidonly, int projectid, QString &msg) {
    n->WriteLog("In RearchiveSubject()");

    QStringList msgs;
    QSqlQuery q;
    subject s(subjectid, n); /* get the series info */
    if (!s.valid()) { msg = "Subject was not valid: [" + s.msg() + "]"; return false; }

    /* get instanceid */
    q.prepare("select instance_id from projects where project_id = :projectid");
    q.bindValue(":projectid", projectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int instanceid;
    if (q.size() > 0) {
        q.first();
        instanceid = q.value("instance_id").toInt();
    }
    else {
        msg = "Invalid instance ID";
        return false;
    }

    /* create an import request, based on the current instance, project, and site & get next import ID */
    q.prepare("insert into import_requests (import_datatype, import_datetime, import_status, import_equipment, import_siteid, import_projectid, import_instanceid, import_uuid, import_anonymize, import_permanent, import_matchidonly) values ('dicom',now(),'uploading','',null,:projectid,:instanceid,'',null,null,:matchidonly)");
    q.bindValue(":projectid", projectid);
    q.bindValue(":instanceid", instanceid);
    q.bindValue(":matchidonly", matchidonly);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int uploadid = q.lastInsertId().toInt();

    /* create an import request dir */
    QString outpath = QString("%1/%2").arg(n->cfg["uploadeddir"]).arg(uploadid);
    QDir d;
    if (!d.mkpath(outpath)) {
        msg = "Unable to create outpath [" + outpath + "]";
        return false;
    }

    /* move all DICOMs to the incomingdir */
    QString m;
    if (!n->MoveAllFiles(s.path(),"*.dcm",outpath, m)) {
        msgs << QString("Error moving DICOM files from archivedir to incomingdir [%1]").arg(m);
    }
    else {
        msgs << QString("Moved all .dcm files from [%1] to  [%2]").arg(s.path()).arg(outpath);
    }

    /* move the remains of the subject directory to the deleted directory */
    QString newpath = QString("%1/%2-%3").arg(n->cfg["deleteddir"]).arg(s.UID()).arg(n->GenerateRandomString(10));
    QDir d2;
    if(d2.rename(s.path(), newpath)) {
        msgs << n->WriteLog(QString("Moved [%1] to [%2]").arg(s.path()).arg(newpath));
    }
    else {
        msgs << n->WriteLog(QString("Unable to move [%1] to [%2]").arg(s.path()).arg(newpath));
    }

    /* update the import_requests table with the new uploadid */
    q.prepare("update import_requests set import_status = 'pending' where importrequest_id = :uploadid");
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove all database entries about this subject:
     * TABLES: subjects, subject_altuid, subject_relation, studies, *_series, enrollment, family_members, mostrecent */

    q.prepare("delete from mostrecent where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from family_members where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subject_relation where subjectid1 = :subjectid or subjectid2 = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subject_altuid where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from mr_series where study_id in (select study_id from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid))");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid)");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from enrollment where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subjects where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveStudyToSubject ----------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::MoveStudyToSubject(int studyid, QString newuid, int newsubjectid, QString &msg) {
    QStringList msgs;

    study thestudy(studyid, n); /* get the original study info */
    if (!thestudy.valid()) {
        msg = n->WriteLog("Original study was not valid: [" + thestudy.msg() + "]");
        return false;
    }

    subject origsubject(thestudy.subjectRowID(), n); /* get the original subject info */
    if (!origsubject.valid()) {
        msg = n->WriteLog("Original subject was not valid: [" + origsubject.msg() + "]");
        return false;
    }

    /* check if this is looking for a UID or rowID */
    subject *newsubject;
    if ((newuid == "") && (newsubjectid > -1)) {
        newsubject = new subject(newsubjectid, n); /* get the new subject info, by subjectID */
        if (!newsubject->valid()) {
            msg = n->WriteLog("New subject was not valid: [" + newsubject->msg() + "]");
            delete newsubject;
            return false;
        }
    }
    else {
        newsubject = new subject(newuid, false, n); /* get the new subject info, by UID */
        if (!newsubject->valid()) {
            msg = n->WriteLog("New subject was not valid: [" + newsubject->msg() + "]");
            delete newsubject;
            return false;
        }
    }

    QDateTime now = QDateTime::currentDateTime();
    if (now < thestudy.dateTime().addDays(1)) {
        msg = n->WriteLog("This study was collected in the past 24 hours. The study may not be completely archived so no changes can be made until 1 day after the study's start time");
        delete newsubject;
        return false;
    }

    /* all of the checks are ok, so lets do the move */
    n->WriteLog(QString("Moving study [%1%2] to subject [%3]").arg(thestudy.UID()).arg(thestudy.studyNum()).arg(newuid));

    /* check if the new subject is enrolled in the old project, if not, enroll them */
    QSqlQuery q;
    q.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
    q.bindValue(":subjectid", newsubject->subjectRowID());
    q.bindValue(":projectid", thestudy.projectRowID());
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int newenrollmentid;
    if (q.size() > 0) {
        q.first();
        newenrollmentid = q.value("enrollment_id").toInt();
        n->WriteLog("Subject already enrolled in project");
    }
    else {
        q.prepare("insert into enrollment (subject_id, project_id, enroll_startdate) values (:subjectid, :projectid, now())");
        q.bindValue(":subjectid", newsubject->subjectRowID());
        q.bindValue(":projectid", thestudy.projectRowID());
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        newenrollmentid = q.lastInsertId().toInt();
        n->WriteLog(QString("Subject now enrolled in project. enrollmentid [%1]").arg(newenrollmentid));
    }

    /* get the next study number for the new subject */
    q.prepare("select max(a.study_num) 'maxstudynum' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
    q.bindValue(":subjectid", newsubject->subjectRowID());
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int newstudynum = 1;
    if (q.size() > 0) {
        q.first();
        newstudynum = q.value("maxstudynum").toInt() + 1;
        n->WriteLog(QString("Highest studynum for subject [%1], New studynum [%2]").arg(q.value("maxstudynum").toInt()).arg(newstudynum));
    }

    /* change the enrollment_id associated with the studyid */
    q.prepare("update studies set enrollment_id = :enrollmentid, study_num = :newstudynum where study_id = :studyid");
    q.bindValue(":enrollmentid", newenrollmentid);
    q.bindValue(":newstudynum", newstudynum);
    q.bindValue(":studyid", studyid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* copy the data, don't move in case there is a problem */
    QString oldpath = thestudy.path();
    QString newpath = QString("%1/%2").arg(newsubject->path()).arg(newstudynum);
    if (!newsubject->dataPathExists()) {
        QString m;
        if (!n->MakePath(newsubject->path(), m)) {
            n->WriteLog("Subject directory [" + newsubject->path() + "] did not exist on disk. Tried to create the directory path, but failed with error [" + m + "]");
        }
    }

    QDir d;
    d.mkpath(newpath);
    //d.mkpath(oldpath);
    if (!d.exists(newpath)) msgs << "Error creating newpath [" + newpath + "]";
    if (!d.exists(oldpath)) msgs << "Error: oldpath [" + oldpath + "] does not exist";

    n->WriteLog("Moving data within archive directory");
    QString systemstring = QString("rsync -rtu %1/* %2").arg(oldpath).arg(newpath);
    msgs << n->WriteLog(n->SystemCommand(systemstring));

    msg = msgs.join(" | ");
    q.prepare("insert into changelog (affected_projectid1, affected_projectid2, affected_subjectid1, affected_subjectid2, affected_enrollmentid1, affected_enrollmentid2, affected_studyid1, affected_studyid2, change_datetime, change_event, change_desc) values (:oldprojectid, :oldprojectid, :oldsubjectid, :newsubjectid, :oldenrollmentid, :newenrollmentid, :studyid, :studyid, now(), 'MoveStudyToSubject', :msg)");
    q.bindValue(":oldprojectid", thestudy.projectRowID());
    q.bindValue(":oldsubjectid", origsubject.subjectRowID());
    q.bindValue(":newsubjectid", newsubject->subjectRowID());
    q.bindValue(":oldenrollmentid", thestudy.enrollmentRowID());
    q.bindValue(":newenrollmentid", newenrollmentid);
    q.bindValue(":studyid", studyid);
    q.bindValue(":msg", msg);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    delete newsubject;

    return true;
}


/* ---------------------------------------------------------- */
/* --------- MergeSubjects ---------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::MergeSubjects(int subjectid, QString mergeIDs, QString mergeName, QString mergeDOB, QString mergeSex, QString mergeEthnicity1, QString mergeEthnicity2, QString mergeGUID, QString mergeAltUIDs, QString &msg) {

    QSqlQuery q;
    msg = "";

    QStringList ids = mergeIDs.split(",");
    if (ids.size() > 0) {
        /* moving all studies from each existing subject to the new subject */
        foreach (QString id, ids) {

            /* make sure the target subjectid is not in the list of merge ids */
            if (id.toInt() == subjectid)
                continue;

            /* get list of studies for this subject */
            q.prepare("select study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
            q.bindValue(":subjectid", id.toInt());
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
            if (q.size() > 0) {
                n->WriteLog(QString("Found [%1] studies for subject [%2]").arg(q.size()).arg(id.toInt()));
                while (q.next()) {
                    int studyid = q.value("study_id").toInt();
                    QString m;
                    if (!MoveStudyToSubject(studyid, "", subjectid, m)) {
                        n->WriteLog("Error moving study to subject [" + m + "]");
                        return false;
                    }
                }
            }
            n->WriteLog(QString("Found no studies for this subject [%1]").arg(id.toInt()));

            /* delete the subject that has just been merged */
            q.prepare("update subjects set isactive = 0 where subject_id = :subjectid");
            q.bindValue(":subjectid", id.toInt());
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
        }
    }

    /* update the final subject with the info */
    q.prepare("update subjects set name = :name, birthdate = :dob, gender = :sex, ethnicity1 = :ethnicity1, ethnicity2 = :ethnicity2, guid = :guid where subject_id = :subjectid");
    q.bindValue(":name", mergeName);
    q.bindValue(":dob", mergeDOB);
    q.bindValue(":sex", mergeSex);
    q.bindValue(":ethnicity1", mergeEthnicity1);
    q.bindValue(":ethnicity2", mergeEthnicity2);
    q.bindValue(":guid", mergeGUID);
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* update the Global (subject level) alternate UIDs */

    /* delete entries for this subject from the altuid table ... */
    q.prepare("delete from subject_altuid where subject_id = :subjectid");
    q.bindValue(":subjectid",subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* ... and insert the new rows into the altuids table */
    QStringList altuids = mergeAltUIDs.split(",");
    foreach (QString altuid, altuids) {
        altuid = altuid.trimmed();
        if (altuid != "") {
            if (altuid.contains("*")) {
                altuid.replace("*","");
                q.prepare("insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (:subjectid, :altuid, 1, -1)");
            }
            else {
                q.prepare("insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (:subjectid, :altuid, 0, -1)");
            }
            q.bindValue(":subjectid", subjectid);
            q.bindValue(":altuid", altuid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- MergeStudies ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::MergeStudies(int studyid, QString mergeIDs, QString mergeMethod, QString &msg) {
    /* all mergeIDs will be merged into the studyid */

    QSqlQuery q;
    msg = "";

    /* get info about first study */
    study s(studyid, n);
    QString modality = s.modality().toLower();
    int finalStudyNum = s.studyNum();

    /* get list of all studyids */
    QList<int> allStudyIDs;
    allStudyIDs.append(studyid);
    allStudyIDs.append(n->SplitStringToIntArray(mergeIDs));

    int destStudyID = studyid;
    /* merge by SERIES datetime */
    if (mergeMethod == "sortbyseriesdate") {
        QString mergeIDList = n->JoinIntArray(allStudyIDs, ",");

        int newSeriesNum = 1;
        int tmpSeriesNum = 100000;
        QMap<int, int> tmpSeriesNumMap;
        QMap<int, int> newSeriesNumMap;
        /* get list of all series sorted by series date */
        q.prepare(QString("select * from %1_series a left join studies b on a.study_id = b.study_id where b.study_id in (%2) order by a.series_datetime asc").arg(modality).arg(mergeIDList));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
        if (q.size() > 0) {
            while (q.next()) {
                /* renumber the series to tmp seriesNumbers, and move them to the new study */
                int sourceStudyID = q.value("study_id").toInt();
                int studyNum = q.value("study_num").toInt();
                int seriesID = q.value(modality + "series_id").toInt();
                int seriesNum = q.value("series_num").toInt();
                QString seriesDesc = q.value("series_desc").toString();
                QString seriesDateTime = q.value("series_datetime").toDateTime().toString();

                n->WriteLog(QString("Moving seriesNum [%1]  desc [%2]  datetime [%3]  from studynum [%4] (id [%5]) to studynum [%6] (id [%7]). New seriesnum [%8]").arg(seriesNum).arg(seriesDesc).arg(seriesDateTime).arg(studyNum).arg(sourceStudyID).arg(finalStudyNum).arg(destStudyID).arg(newSeriesNum));

                newSeriesNumMap[seriesID] = newSeriesNum;
                tmpSeriesNumMap[seriesID] = tmpSeriesNum;
                n->WriteLog(QString("Mapping seriesID [%1] to tmpSeriesNum [%2]  and newSeriesNum [%3]").arg(seriesID).arg(tmpSeriesNum).arg(newSeriesNum));

                /* FIRST - move the series directory */
                series s(seriesID, modality, n);
                s.ChangeSeriesPath(destStudyID, tmpSeriesNum);

                /* SECOND - change the studyid to the new study, and seriesnum to the tmpSeriesNum */
                QSqlQuery q2;
                q2.prepare(QString("update %1_series set study_id = :newstudyid, series_num = :tmpseriesnum where %1series_id = :seriesid").arg(modality));
                q2.bindValue(":newstudyid", destStudyID);
                q2.bindValue(":tmpseriesnum", tmpSeriesNum);
                q2.bindValue(":seriesid", seriesID);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);

                tmpSeriesNum++;
                newSeriesNum++;
            }

            /* renumber series to newSeriesNum */
            QMapIterator<int, int> it(newSeriesNumMap);
            while (it.hasNext()) {
                it.next();
                int seriesID = it.key();
                int newSeriesNum = it.value();

                /* FIRST - move the series path */
                series s(seriesID, modality, n);
                s.ChangeSeriesPath(destStudyID, newSeriesNum);

                /* SECOND - update the database with the new series number */
                q.prepare(QString("update %1_series set series_num = :seriesnum where %1series_id = :seriesid").arg(modality));
                q.bindValue(":seriesnum", newSeriesNum);
                q.bindValue(":seriesid", seriesID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
            }

            /* delete the, now, extraneous studies */
            QList<int> deleteStudyIDs;
            deleteStudyIDs.append(n->SplitStringToIntArray(mergeIDs));
            foreach (int delStudyID, deleteStudyIDs) {
                QString m;
                if (!DeleteStudy(delStudyID, m))
                    n->WriteLog("Error deleting study, with message [" + m + "]");
            }
        }
    }
    else if (mergeMethod == "sortbyseriesnum") {
        QString mergeIDList = n->JoinIntArray(allStudyIDs, ",");

        int newSeriesNum = 0;
        /* get list of all series sorted by series date */
        q.prepare(QString("select * from %1_series a left join studies b on a.study_id = b.study_id where b.study_id in (%2) order by a.series_num asc").arg(modality).arg(mergeIDList));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
        if (q.size() > 0) {
            while (q.next()) {

                int sourceStudyID = q.value("study_id").toInt();
                int studyNum = q.value("study_num").toInt();
                int seriesID = q.value(modality + "series_id").toInt();
                int seriesNum = q.value("series_num").toInt();
                QString seriesDesc = q.value("series_desc").toString();
                QString seriesDateTime = q.value("series_datetime").toDateTime().toString();

                /* check if this series number exists in the new study (on disk) */
                study destStudy(destStudyID, n);
                QString newPath(QString("%1/%2").arg(destStudy.path()).arg(seriesNum));
                QFileInfo fi(newPath);
                if (fi.exists()) {
                    int chkSeriesNum = 10000 + seriesNum;
                    n->WriteLog(QString("New series path [%1] already exists. Checking if [%2] exists").arg(newPath).arg(chkSeriesNum));
                    while (1) {
                        QString chkPath(QString("%1/%2").arg(destStudy.path()).arg(chkSeriesNum));
                        if (QDir(chkPath).exists()) {
                            n->WriteLog(QString("Path [%1] already exists. Will check next one").arg(chkPath));
                            chkSeriesNum++;
                        }
                        else {
                            n->WriteLog(QString("Path [%1] does not exist. This will be the new series number").arg(chkPath));
                            newSeriesNum = chkSeriesNum;
                            break;
                        }
                    }
                }
                else {
                    newSeriesNum = seriesNum;
                }

                n->WriteLog(QString("Moving seriesNum [%1]  desc [%2]  datetime [%3]  from studynum [%4] (id [%5]) to studynum [%6] (id [%7]). New seriesnum [%8]").arg(seriesNum).arg(seriesDesc).arg(seriesDateTime).arg(studyNum).arg(sourceStudyID).arg(finalStudyNum).arg(destStudyID).arg(newSeriesNum));

                /* FIRST - move the series directory */
                series s(seriesID, modality, n);
                s.ChangeSeriesPath(destStudyID, newSeriesNum);

                /* SECOND - change the studyid to the new study, and seriesnum to the tmpSeriesNum */
                QSqlQuery q2;
                q2.prepare(QString("update %1_series set study_id = :newstudyid, series_num = :newseriesnum where %1series_id = :seriesid").arg(modality));
                q2.bindValue(":newstudyid", destStudyID);
                q2.bindValue(":newseriesnum", newSeriesNum);
                q2.bindValue(":seriesid", seriesID);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
            }

            /* delete the, now, extraneous studies */
            QList<int> deleteStudyIDs;
            deleteStudyIDs.append(n->SplitStringToIntArray(mergeIDs));
            foreach (int delStudyID, deleteStudyIDs) {
                QString m;
                if (!DeleteStudy(delStudyID, m))
                    n->WriteLog("Error deleting study, with message [" + m + "]");
            }
        }
    }
    else if (mergeMethod == "concatbystudydateasc") {

    }
    else if (mergeMethod == "concatbystudydatedesc") {

    }
    else if (mergeMethod == "concatbystudynumasc") {

    }
    else if (mergeMethod == "concatbystudynumdesc") {

    }

    /* start a transaction */
    //QSqlDatabase::database().transaction();
    /* commit the transaction */
    //QSqlDatabase::database().commit();

    return true;
}
