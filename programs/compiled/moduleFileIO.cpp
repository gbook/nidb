#include "moduleFileIO.h"
#include <QDebug>
#include <QSqlQuery>

moduleFileIO::moduleFileIO(nidb *a)
{
	n = a;
}

moduleFileIO::~moduleFileIO()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleFileIO::Run() {
    qDebug() << "Entering the fileio module";

	/* get list of things to delete */
	QSqlQuery q;
	q.prepare("select * from fileio_requests where request_status != 'complete' and request_status != 'deleting' and request_status != 'error'");
	n->SQLQuery(q, "Run");

	if (q.size() > 0) {
		while (q.next()) {
			int fileiorequest_id = q.value("fileiorequest_id").toInt();
			QString fileio_operation = q.value("fileio_operation").toString();
			QString data_type = q.value("data_type").toString();
			int data_id = q.value("data_id").toInt();
			QString modality = q.value("modality").toString();
			QString data_destination = q.value("data_destination").toString();
			QString dicomtags = q.value("anonymize_fields").toString();

			n->ModuleRunningCheckIn();

			n->WriteLog(QString("Performing the following fileio operation [%1] on the datatype [%2]").arg(fileio_operation).arg(data_type));
			bool found = false;
			QString msg;

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
				//if (data_type == "pipeline") { found = DeletePipeline(data_id); }
				//else if (data_type == "analysis") { found = DeleteAnalysis(data_id); }
				//else if (data_type == "groupanalysis") { found = DeleteGroupAnalysis(data_id); }
				//else if (data_type == "subject") { found = DeleteSubject(data_id); }
				//else if (data_type == "study") { found = DeleteStudy(data_id); }
				//else if (data_type == "series") { found = DeleteSeries(data_id, modality); }
			}
			else if (fileio_operation == "detach") {
			}
			else if (fileio_operation == "move") {
			}
			else if (fileio_operation == "anonymize") {
				//found = AnonymizeSeries(fileiorequest_id, data_id, modality, dicomtags);
			}
			else if (fileio_operation == "rearchive") {
				if (data_type == "study") {
					//found = RearchiveStudy(data_id,0);
				}
				else if (data_type == "subject") {
					//found = RearchiveSubject(data_id,0);
				}
			}
			else if (fileio_operation == "rearchiveidonly") {
				if (data_type == "study") {
					//found = RearchiveStudy(data_id,1);
				}
				if (data_type == "subject") {
					//found = RearchiveSubject(data_id,1);
				}
			}
			else {
				/* unknown fileio_operation, so set it to 'error' */
				QSqlQuery q;
				q.prepare("update fileio_requests set request_status = 'error' where fileiorequest_id = :id");
				q.bindValue(":id", fileiorequest_id);
				n->SQLQuery(q, "Run");
			}

			if (found) {
				/* set the status of the delete_request to complete */
				QSqlQuery q;
				q.prepare("update fileio_requests set request_status = 'complete' where fileiorequest_id = :id");
				q.bindValue(":id", fileiorequest_id);
				n->SQLQuery(q, "Run");
			}
			else {
				/* some error occurred, so set it to 'error' */
				QSqlQuery q;
				q.prepare("update fileio_requests set request_status = 'complete', request_message = ':msg' where fileiorequest_id = :id");
				q.bindValue(":msg", msg);
				q.bindValue(":id", fileiorequest_id);
				n->SQLQuery(q, "Run");
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
	n->SQLQuery(q, "Run");
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
		n->WriteLog(n->SystemCommand(systemstring));
	}
	else {
		n->WriteLog(QString("SGE job id [%1] is not valid. Not attempting to kill the job").arg(a.jobid));
	}

	n->WriteLog("Analysispath: [" + a.analysispath + "]");

	bool okToDeleteDBEntries = false;
	if (n->RemoveDir(a.analysispath, msg)) {
		/* QDir.remove worked */
		okToDeleteDBEntries = true;
	}
	else {
		QString systemstring = QString("sudo rm -rf %1").arg(a.analysispath);
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

		q.prepare("delete from analysis_group where analysis_id = :analysisid");
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
