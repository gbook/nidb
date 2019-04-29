#include "analysis.h"
#include <QDebug>
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- analysis --------------------------------------- */
/* ---------------------------------------------------------- */
analysis::analysis(int id, nidb *a)
{
	n = a;
	analysisid = id;
	LoadAnalysisInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadAnalysisInfo ------------------------------- */
/* ---------------------------------------------------------- */
void analysis::LoadAnalysisInfo() {

	if (analysisid < 1) {
		msg = "Invalid analysis ID";
		isValid = false;
	}

	/* get the path to the analysisroot */
	QSqlQuery q;
	q.prepare("select a.analysis_qsubid, d.uid, b.study_num, b.study_id, e.* from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = :analysisid");
	q.bindValue(":analysisid", analysisid);
	n->SQLQuery(q, "Run");
	if (q.size() < 1) { msg = "Analysis query returned no results. Possibly invalid analysis ID or recently deleted?"; isValid = false; }
	q.first();
	QString uid = q.value("uid").toString().trimmed();
	studynum = q.value("study_num").toInt();
	studyid = q.value("study_id").toInt();
	jobid = q.value("analysis_qsubid").toInt();
	pipelinename = q.value("pipeline_name").toString().trimmed();
	pipelinelevel = q.value("pipeline_level").toInt();
	pipelinedirectory = q.value("pipeline_directory").toString().trimmed();
	pipelineid = q.value("pipeline_id").toInt();
	pipelineversion = q.value("pipeline_version").toInt();
	pipelinedirstructure = q.value("pipeline_dirstructure").toString().trimmed();

	/* check to see if anything isn't valid or is blank */
	if (n->cfg["analysisdir"] == "") { n->WriteLog("Something was wrong, cfg->analysisdir was not initialized"); msg = "cfg->analysisdir was not initialized"; isValid = false; }
	if (uid == "") { n->WriteLog("Something was wrong, uid was blank"); msg = "uid was not initialized"; isValid = false; }
	if (studynum == 0) { n->WriteLog("Something was wrong, studynum was blank"); msg = "studynum was not initialized"; isValid = false; }
	if (pipelinename == "") { n->WriteLog("Something was wrong, pipelinename was blank"); msg = "pipelinename was not initialized"; isValid = false; }

	if (pipelinelevel == 0) {
		analysispath = QString("%1/%2/%3/%4").arg(n->cfg["groupanalysisdir"]).arg(uid).arg(studynum).arg(pipelinename);
	}
	else {
		if (pipelinedirstructure == "b")
			analysispath = QString("%1/%2/%3/%4").arg(n->cfg["analysisdirb"]).arg(pipelinename).arg(uid).arg(studynum);
		else
			analysispath = QString("%1/%2/%3/%4").arg(n->cfg["analysisdir"]).arg(uid).arg(studynum).arg(pipelinename);
	}

	/* if there is a /mount prefix, remove it */
	if (analysispath.left(6) == "/mount") {
		analysispath.remove(0,6);
	}

	if ((analysispath == "") || (analysispath == ".") || (analysispath == "..") || (analysispath == "/") || analysispath.contains("//") || (analysispath == "/home") || (analysispath == "/root")) {
		msg = "Invalid data path";
		isValid = false;
	}
}
