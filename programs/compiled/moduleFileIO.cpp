#include "moduleFileIO.h"
#include <QDebug>
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
	n->SQLQuery(q, "Run", true);

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

			qDebug() << "requestid [" << requestid << "]";
			/* get the current status of this fileio request, make sure no one else is processing it, and mark it as being processed if not */
			QString status = GetIORequestStatus(requestid);
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
				if (data_type == "study") { found = MoveStudyToSubject(data_id, data_destination, username, msg); }
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

			if (found) {
				/* set the status of the delete_request to complete */
				SetIORequestStatus(requestid, "complete", msg);
			}
			else {
				/* some error occurred, so set it to 'error' */
				SetIORequestStatus(requestid, "error", msg);
			}
		}
		n->WriteLog("Finished performing file IO");
	}
	else {
		n->WriteLog("Nothing to do");
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
	n->SQLQuery(q, "GetIORequestStatus", true);
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
			n->SQLQuery(q, "SetIORequestStatus", true);
		}
		else {
			QSqlQuery q;
			q.prepare("update fileio_requests set request_status = :status, request_message = :msg where fileiorequest_id = :id");
			q.bindValue(":id", requestid);
			q.bindValue(":msg", msg);
			q.bindValue(":status", status);
			n->SQLQuery(q, "SetIORequestStatus", true);
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
bool moduleFileIO::RecheckSuccess(int analysisid, QString &msg) {
	n->WriteLog(QString("In RecheckSuccess(%1)").arg(analysisid));

	analysis a(analysisid, n); /* get the analysis info */
	if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

	/* check the analysispath to see if the required file(s) exist
	 * get a list of expected files from the database */
	QSqlQuery q;
	q.prepare("select pipeline_completefiles from pipelines a left join analysis b on a.pipeline_id = b.pipeline_id where b.analysis_id = :analysisid");
	q.bindValue(":analysisid", analysisid);
	n->SQLQuery(q, "RecheckSuccess");
	q.first();
	QString completefiles = q.value("uid").toString().trimmed();
	QStringList filelist = completefiles.split(',');

	int iscomplete = 1;
	for(int i=0; i<filelist.size(); i++) {
		QString filepath = a.analysispath + "/" + filelist[i];
		n->WriteLog(QString("Checking for [" + filepath + "]"));
		QFile f(filepath);
		if (!f.exists()) {
			n->WriteLog(QString("File [" + filepath + "] does not exist"));
			iscomplete = 0;
			break;
		}
	}

	q.prepare("update analysis set analysis_iscomplete = :iscomplete where analysis_id = :analysisid");
	q.bindValue(":iscomplete", iscomplete);
	q.bindValue(":analysisid", analysisid);
	n->SQLQuery(q, "Run");

	n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysisrecheck", "Analysis success recheck finished");

	return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateLinks ------------------------------------ */
/* ---------------------------------------------------------- */
bool moduleFileIO::CreateLinks(int analysisid, QString destination, QString &msg) {
	n->WriteLog(QString("In CreateLinks(%1, %2)").arg(analysisid).arg(destination));

	/* check if destination is somewhat valid */
	if ((destination == "") || (destination == ".") || (destination == "..") || (destination == "/") || (destination.contains("//"))) {
		msg = "Invalid destination [" + destination + "]";
		return false;
	}

	analysis a(analysisid, n); /* get the analysis info */
	if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

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
bool moduleFileIO::CopyAnalysis(int analysisid, QString destination, QString &msg) {
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
		QString systemstring = QString("cp -ruv %1 %2").arg(a.analysispath).arg(destination);
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
bool moduleFileIO::DeleteAnalysis(int analysisid, QString &msg) {
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
		int c;
		double b;
		n->GetDirSize(a.analysispath, b, c);
		n->WriteLog(QString("Going to remove [%1] files and directories from [%2]").arg(c).arg(a.analysispath));
		if (n->RemoveDir(a.analysispath, msg)) {
			/* QDir.remove worked */
			n->WriteLog("Analysispath removed");
			okToDeleteDBEntries = true;
		}
		else {
			QString systemstring = QString("sudo rm -rfv %1").arg(a.analysispath);
			n->WriteLog("Running " + systemstring);
			n->WriteLog(n->SystemCommand(systemstring));

			QDir d(a.analysispath);
			if (d.exists()) {
				n->WriteLog("Datapath [" + a.analysispath + "] still exists, even after sudo rm -rf");

				QSqlQuery q;
				q.prepare("update analysis set analysis_statusmessage = 'Analysis directory not deleted. Manually delete the directory and then delete from this webpage again' where analysis_id = :analysisid");
				q.bindValue(":analysisid", analysisid);
				n->SQLQuery(q, "DeleteAnalysis");

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
		n->SQLQuery(q, "DeleteAnalysis");

		q.prepare("delete from analysis_results where analysis_id = :analysisid");
		q.bindValue(":analysisid", analysisid);
		n->SQLQuery(q, "DeleteAnalysis");

		q.prepare("delete from analysis_history where analysis_id = :analysisid");
		q.bindValue(":analysisid", analysisid);
		n->SQLQuery(q, "DeleteAnalysis");

		q.prepare("delete from analysis where analysis_id = :analysisid");
		q.bindValue(":analysisid", analysisid);
		n->SQLQuery(q, "DeleteAnalysis");
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
	n->SQLQuery(q, "DeletePipeline");

	if (q.size() > 0) {
		while (q.next()) {
			QString msg;
			int analysisid = q.value("anlaysis_id").toInt();
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
	n->SQLQuery(q, "DeletePipeline");

	return 1;
}


/* ---------------------------------------------------------- */
/* --------- DeleteSubject ---------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteSubject(int subjectid, QString username, QString &msg) {
	n->WriteLog("In DeleteSubject()");

	QSqlQuery q;

	subject s(subjectid, n); /* get the subject info */
	if (!s.isValid) { msg = "Subject was not valid: [" + s.msg + "]"; return false; }

	QString newpath = QString("%1/%2-%3").arg(n->cfg["deleteddir"]).arg(s.uid).arg(n->GenerateRandomString(10));
	QDir d;

	if (s.subjectpath != "") {
		if (d.exists(s.subjectpath)) {
			if (d.rename(s.subjectpath, newpath)) {
				msg = n->WriteLog(QString("Moved [%1] to [%2]").arg(s.subjectpath).arg(newpath));
			}
			else {
				msg = QString("Error in moving [%1] to [%2]").arg(s.subjectpath).arg(newpath);
				n->WriteLog(msg);
				return false;
			}
		}
		else {
			n->WriteLog(QString("Subject path on disk [" + s.subjectpath + "] does not exist"));
		}
	}
	else {
		n->WriteLog(QString("Subject has no path on disk"));
	}

	/* remove all database entries about this subject:
	   TABLES: subjects, subject_altuid, subject_relation, studies, *_series, enrollment, family_members, mostrecent */
	q.prepare("delete from mostrecent where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject", true);
	//q.numRowsAffected();

	q.prepare("delete from mostrecent where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject", true);

	q.prepare("delete from family_members where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject", true);

	q.prepare("delete from subject_relation where subjectid1 = :subjectid or subjectid2 = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");

	q.prepare("delete from subject_altuid where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");

	// delete all series
	q.prepare("delete from mr_series where study_id in (select study_id from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid))");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");
	q.prepare("delete from et_series where study_id in (select study_id from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid))");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");
	q.prepare("delete from eeg_series where study_id in (select study_id from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid))");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");

	// delete all studies
	q.prepare("delete from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid)");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");

	// delete all enrollments
	q.prepare("delete from enrollment where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");

	// delete the subject
	q.prepare("delete from subjects where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "DeleteSubject");

	n->InsertSubjectChangeLog(username, s.uid, "", "obliterate", msg);
	return true;
}


/* ---------------------------------------------------------- */
/* --------- DeleteStudy ------------------------------------ */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteStudy(int studyid, QString &msg) {
	n->WriteLog("In DeleteStudy()");

	QSqlQuery q;
	study s(studyid, n); /* get the study info */
	if (!s.isValid) { msg = "Study was not valid: [" + s.msg + "]"; return false; }

	QString newpath = QString("%1/%2-%3-%4").arg(n->cfg["deleteddir"]).arg(s.uid).arg(s.studynum).arg(n->GenerateRandomString(10));
	QDir d;
	if(d.rename(s.studypath, newpath)) {
		n->WriteLog(QString("Moved [%1] to [%2]").arg(s.studypath).arg(newpath));

		// move all archive data to the deleted directory
		// delete all series
		q.prepare("delete from mr_series where study_id = :studyid");
		q.bindValue(":studyid", studyid);
		n->SQLQuery(q, "DeleteStudy");

		// delete all studies
		q.prepare("delete from studies where study_id = :studyid");
		q.bindValue(":studyid", studyid);
		n->SQLQuery(q, "DeleteStudy");
	}
	else {
		msg = QString("Error in moving [%1] to [%2]").arg(s.studypath).arg(newpath);
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
		n->SQLQuery(q, "DeleteSeries", true);

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
	n->WriteLog("In DeleteSeries()");

	QStringList msgs;
	QSqlQuery q;
	study s(studyid, n); /* get the series info */
	if (!s.isValid) { msg = "Study was not valid: [" + s.msg + "]"; return false; }

	/* get instanceid */
	q.prepare("select instance_id from projects where project_id = :projectid");
	q.bindValue(":projectid", s.projectid);
	n->SQLQuery(q, "RearchiveStudy", true);
	int instanceid;
	if (q.size() > 0) {
		q.first();
		instanceid = q.value("anlaysis_id").toInt();
	}
	else {
		msg = "Invalid instance ID";
		return false;
	}

	/* create an import request, based on the current instance, project, and site & get next import ID */
	q.prepare("insert into import_requests (import_datatype, import_datetime, import_status, import_equipment, import_siteid, import_projectid, import_instanceid, import_uuid, import_anonymize, import_permanent, import_matchidonly) values ('dicom',now(),'uploading','',null,:projectid,:instanceid,'',null,null,:matchidonly)");
	q.bindValue(":projectid", s.projectid);
	q.bindValue(":instanceid", instanceid);
	q.bindValue(":matchidonly", matchidonly);
	n->SQLQuery(q, "RearchiveStudy", true);
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
	if (!n->MoveAllFiles(s.studypath,"*.dcm",outpath, m)) {
		msgs << QString("Error moving DICOM files from archivedir to incomingdir [%1]").arg(m);
	}

	/* move the old study to the deleted directory */
	QString newpath = QString("%1/%2-%3-%4").arg(n->cfg["deleteddir"]).arg(s.uid).arg(s.studynum).arg(n->GenerateRandomString(10));
	QDir d2;
	if(d2.rename(s.studypath, newpath)) {
		n->WriteLog(QString("Moved [%1] to [%2]").arg(s.studypath).arg(newpath));
	}

	/* update the import_requests table with the new uploadid */
	q.prepare("update import_requests set import_status = 'pending' where importrequest_id = :uploadid");
	q.bindValue(":uploadid", uploadid);
	n->SQLQuery(q, "RearchiveStudy", true);

	/* remove any reference to this study from the (enrollment, study) tables
	 * delete all series */
	q.prepare("delete from mr_series where study_id = :studyid");
	q.bindValue(":studyid", studyid);
	n->SQLQuery(q, "RearchiveStudy", true);

	/* delete the study */
	q.prepare("delete from studies where study_id = :studyid");
	q.bindValue(":studyid", studyid);
	n->SQLQuery(q, "RearchiveStudy", true);

	msg = msgs.join(" | ");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- RearchiveSubject ------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::RearchiveSubject(int subjectid, bool matchidonly, int projectid, QString &msg) {
	n->WriteLog("In DeleteSeries()");

	QStringList msgs;
	QSqlQuery q;
	subject s(subjectid, n); /* get the series info */
	if (!s.isValid) { msg = "Subject was not valid: [" + s.msg + "]"; return false; }

	/* get instanceid */
	q.prepare("select instance_id from projects where project_id = :projectid");
	q.bindValue(":projectid", projectid);
	n->SQLQuery(q, "RearchiveSubject", true);
	int instanceid;
	if (q.size() > 0) {
		q.first();
		instanceid = q.value("anlaysis_id").toInt();
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
	n->SQLQuery(q, "RearchiveSubject", true);
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
	if (!n->MoveAllFiles(s.subjectpath,"*.dcm",outpath, m)) {
		msgs << QString("Error moving DICOM files from archivedir to incomingdir [%1]").arg(m);
	}

	/* move the remains of the subject directory to the deleted directory */
	QString newpath = QString("%1/%2-%3").arg(n->cfg["deleteddir"]).arg(s.uid).arg(n->GenerateRandomString(10));
	QDir d2;
	if(d2.rename(s.subjectpath, newpath)) {
		n->WriteLog(QString("Moved [%1] to [%2]").arg(s.subjectpath).arg(newpath));
	}

	/* update the import_requests table with the new uploadid */
	q.prepare("update import_requests set import_status = 'pending' where importrequest_id = :uploadid");
	q.bindValue(":uploadid", uploadid);
	n->SQLQuery(q, "RearchiveSubject", true);

	/* remove all database entries about this subject:
	 * TABLES: subjects, subject_altuid, subject_relation, studies, *_series, enrollment, family_members, mostrecent */

	q.prepare("delete from mostrecent where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from family_members where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from subject_relation where subjectid1 = :subjectid or subjectid2 = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from subject_altuid where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from mr_series where study_id in (select study_id from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid))");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid)");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from enrollment where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	q.prepare("delete from subjects where subject_id = :subjectid");
	q.bindValue(":subjectid", subjectid);
	n->SQLQuery(q, "RearchiveSubject", true);

	msg = msgs.join(" | ");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveStudyToSubject ----------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::MoveStudyToSubject(int studyid, QString newuid, QString username, QString &msg) {
	QStringList msgs;

	study thestudy(studyid, n); /* get the original study info */
	if (!thestudy.isValid) { msg = "Original study was not valid: [" + thestudy.msg + "]"; return false; }

	subject origsubject(thestudy.subjectid, n); /* get the original subject info */
	if (!origsubject.isValid) { msg = "Original subject was not valid: [" + origsubject.msg + "]"; return false; }

	subject newsubject(newuid, n); /* get the new subject info */
	if (!newsubject.isValid) { msg = "New subject was not valid: [" + newsubject.msg + "]"; return false; }

	QDateTime now = QDateTime::currentDateTime();
	if (now < thestudy.studydatetime.addDays(1)) {
		msg = "This study was collected in the past 24 hours. The study may not be completely archived so no changes can be made until 1 day after the study's start time";
		return false;
	}

	/* check if the new subject is enrolled in the old project, if not, enroll them */
	QSqlQuery q;
	q.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
	q.bindValue(":subjectid", newsubject.subjectid);
	q.bindValue(":projectid", thestudy.projectid);
	n->SQLQuery(q, "MoveStudyToSubject", true);
	int newenrollmentid;
	if (q.size() > 0) {
		q.first();
		newenrollmentid = q.value("anlaysis_id").toInt();
	}
	else {
		q.prepare("insert into enrollment (subject_id, project_id, enroll_startdate) values (:subjectid, :projectid, now())");
		q.bindValue(":subjectid", newsubject.subjectid);
		q.bindValue(":projectid", thestudy.projectid);
		n->SQLQuery(q, "MoveStudyToSubject", true);
		newenrollmentid = q.lastInsertId().toInt();
	}

	/* get the next study number for the new subject */
	q.prepare("select max(a.study_num) 'maxstudynum' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
	q.bindValue(":subjectid", newsubject.subjectid);
	n->SQLQuery(q, "MoveStudyToSubject", true);
	int newstudynum = 1;
	if (q.size() > 0) {
		q.first();
		newstudynum = q.value("maxstudynum").toInt() + 1;
	}

	/* change the enrollment_id associated with the studyid */
	q.prepare("update studies set enrollment_id = :enrollmentid, study_num = :newstudynum where study_id = :studyid");
	q.bindValue(":enrollmentid", newenrollmentid);
	q.bindValue(":newstudynum", newstudynum);
	q.bindValue(":studyid", studyid);
	n->SQLQuery(q, "MoveStudyToSubject", true);

	/* copy the data, don't move in case there is a problem */
	QString oldpath = thestudy.studypath;
	QString newpath = QString("%1/%2").arg(newsubject.subjectpath).arg(newstudynum);

	QDir d;
	d.mkpath(newpath);
	d.mkpath(oldpath);
	if (!d.exists(newpath)) msgs << "Error creating newpath [" + newpath + "]";
	if (!d.exists(oldpath)) msgs << "Error creating oldpath [" + oldpath + "]";

	n->WriteLog("Moving data within archive directory");
	QString systemstring = QString("rsync -rtuv %1/* %2 2>&1").arg(oldpath).arg(newpath);
	msgs << n->SystemCommand(systemstring);

	msg = msgs.join(" | ");
	q.prepare("insert into changelog (affected_projectid1, affected_projectid2, affected_subjectid1, affected_subjectid2, affected_enrollmentid1, affected_enrollmentid2, affected_studyid1, affected_studyid2, change_datetime, change_event, change_desc) values (:oldprojectid, :oldprojectid, :oldsubjectid, :newsubjectid, :oldenrollmentid, :newenrollmentid, :studyid, :studyid, now(), 'MoveStudyToSubject', :msg)");
	q.bindValue(":oldprojectid", thestudy.projectid);
	q.bindValue(":oldsubjectid", origsubject.subjectid);
	q.bindValue(":newsubjectid", newsubject.subjectid);
	q.bindValue(":oldenrollmentid", thestudy.enrollmentid);
	q.bindValue(":newenrollmentid", newenrollmentid);
	q.bindValue(":studyid", studyid);
	q.bindValue(":msg", msg);
	n->SQLQuery(q, "MoveStudyToSubject", true);

	return true;
}
