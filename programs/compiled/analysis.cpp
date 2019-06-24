/* ------------------------------------------------------------------------------
  NIDB analysis.cpp
  Copyright (C) 2004 - 2019
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
	q.prepare("select a.*, d.uid, b.study_num, b.study_id, e.* from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = :analysisid");
	q.bindValue(":analysisid", analysisid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
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
	flagRerunResults = q.value("analysis_rerunresults").toBool();
	flagRunSupplement = q.value("analysis_runsupplement").toBool();

	/* check to see if anything isn't valid or is blank */
	if (n->cfg["analysisdir"] == "") { n->WriteLog("Something was wrong, cfg->analysisdir was not initialized"); msg = "cfg->analysisdir was not initialized"; isValid = false; }
	if (uid == "") { n->WriteLog("Something was wrong, uid was blank"); msg = "uid was not initialized"; isValid = false; }
	if (studynum == 0) { n->WriteLog("Something was wrong, studynum was blank"); msg = "studynum was not initialized"; isValid = false; }
	if (pipelinename == "") { n->WriteLog("Something was wrong, pipelinename was blank"); msg = "pipelinename was not initialized"; isValid = false; }

	if (pipelinelevel == 2) {
		analysispath = QString("%1/%2/%3/%4").arg(n->cfg["groupanalysisdir"]).arg(uid).arg(studynum).arg(pipelinename);
	}
	else {
		if (pipelinedirstructure == "b")
			analysispath = QString("%1/%2/%3/%4").arg(n->cfg["analysisdirb"]).arg(pipelinename).arg(uid).arg(studynum);
		else
			analysispath = QString("%1/%2/%3/%4").arg(n->cfg["analysisdir"]).arg(uid).arg(studynum).arg(pipelinename);
	}

	if ((analysispath == "") || (analysispath == ".") || (analysispath == "..") || (analysispath == "/") || analysispath.contains("//") || (analysispath == "/home") || (analysispath == "/root")) {
		msg = "Invalid data path";
		isValid = false;
	}
}


/* ---------------------------------------------------------- */
/* --------- PrintAnalysisInfo ------------------------------ */
/* ---------------------------------------------------------- */
void analysis::PrintAnalysisInfo() {
	QString	output = QString("***** Analysis - [%1] *****\n").arg(analysisid);

	output += QString("   uid: [%1]\n").arg(uid);
	output += QString("   studynum: [%1]\n").arg(studynum);
	output += QString("   subjectid: [%1]\n").arg(subjectid);
	output += QString("   studyid: [%1]\n").arg(studyid);
	output += QString("   pipelinename: [%1]\n").arg(pipelinename);
	output += QString("   pipelineversion: [%1]\n").arg(pipelineversion);
	output += QString("   pipelineid: [%1]\n").arg(pipelineid);
	output += QString("   pipelinelevel: [%1]\n").arg(pipelinelevel);
	output += QString("   pipelinedirectory: [%1]\n").arg(pipelinedirectory);
	output += QString("   pipelinedirstructure: [%1]\n").arg(pipelinedirstructure);
	output += QString("   jobid: [%1]\n").arg(jobid);
	output += QString("   isValid: [%1]\n").arg(isValid);
	output += QString("   jobid: [%1]\n").arg(jobid);
	output += QString("   flagRerunResults: [%1]\n").arg(flagRerunResults);
	output += QString("   flagRunSupplement: [%1]\n").arg(flagRunSupplement);
	output += QString("   msg: [%1]\n").arg(msg);
	output += QString("   analysispath: [%1]\n").arg(analysispath);

	n->WriteLog(output);
}
