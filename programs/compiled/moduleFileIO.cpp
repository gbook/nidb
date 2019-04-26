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
						//found = CopyAnalysis(data_id, n->cfg["mountdir"] + data_destination);
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
				/* wrong data_type, so set it to 'error' */
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
	n->WriteLog(QString("In CopyAnalysis(%1, %2)").arg(analysisid).arg(destination));

	/* check if destination is somewhat valid */
	if ((destination == "") || (destination == ".") || (destination == "..") || (destination == "/") || (destination.contains("//"))) {
		msg = "Invalid destination [" + destination + "]";
		return false;
	}

	analysis a(analysisid, n); /* get the analysis info */
	if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

	//$destination = "$destination/$uid$studynum";
	QDir dest(destination);
	if (dest.exists()) {
		n->WriteLog(QString("Path [" + destination + "] exists"));
	}
	else {
		if (dest.mkpath(destination)) {
			n->WriteLog(QString("Destination path [" + destination + "] created"));
		}
		else {
			n->WriteLog(QString("Destination path [" + destination + "] not created"));
		}
	}

	QString systemstring = QString("cd %1; ln -s %2 %3%4; chmod 777 %5%6").arg(destination).arg(a.analysispath).arg(a.uid).arg(a.studynum).arg(a.uid).arg(a.studynum);
	n->WriteLog(n->SystemCommand(systemstring));
	n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysiscreatelink", "Analysis links created");

	return true;
}
