/* ------------------------------------------------------------------------------
  NIDB pipeline.cpp
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

#include "pipeline.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- pipeline --------------------------------------- */
/* ---------------------------------------------------------- */
pipeline::pipeline(int id, nidb *a)
{
	n = a;
	pipelineid = id;
	LoadPipelineInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadPipelineInfo ------------------------------- */
/* ---------------------------------------------------------- */
void pipeline::LoadPipelineInfo() {

	if (pipelineid < 1) {
		msg = "Invalid pipeline ID";
		isValid = false;
		return;
	}

	/* get the path to the analysisroot */
	QSqlQuery q;
	q.prepare("select * from pipelines where pipeline_id = :pipelineid");
	q.bindValue(":pipelineid", pipelineid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1) {
		msg = "Pipeline query returned no results. Possibly invalid pipeline ID or recently deleted?";
		isValid = false;
		return;
	}
	q.first();

	name = q.value("pipeline_name").toString().trimmed();
	desc = q.value("pipeline_desc").toString().trimmed();
	ownerID = q.value("pipeline_admin").toInt();
	createDate = q.value("pipeline_createdate").toDateTime();
	level = q.value("pipeline_level").toInt();
	group = q.value("pipeline_group").toString().trimmed();
	directory = q.value("pipeline_directory").toString().trimmed();
	dirStructure = q.value("pipeline_dirstructure").toString().trimmed();
	useTmpDir = q.value("pipeline_usetmpdir").toBool();
	tmpDir = q.value("pipeline_tmpdir").toString().trimmed();
    foreach (QString did, q.value("pipeline_dependency").toString().trimmed().split(",", Qt::SkipEmptyParts)) {
		parentDependencyIDs.append(did.toInt());
	}

	depLevel = q.value("pipeline_dependencylevel").toString().trimmed();
	depDir = q.value("pipeline_dependencydir").toString().trimmed();
	depLinkType = q.value("pipeline_deplinktype").toString().trimmed();
    foreach (QString gid, q.value("pipeline_groupid").toString().trimmed().split(",", Qt::SkipEmptyParts)) {
		groupIDs.append(gid.toInt());
	}

	groupType = q.value("pipeline_grouptype").toString().trimmed();
	groupBySubject = q.value("pipeline_groupbysubject").toBool();
	dynamicGroupID = q.value("pipeline_dynamicgroupid").toInt();
	status = q.value("pipeline_status").toString().trimmed();
	statusMessage = q.value("pipeline_statusmessage").toString().trimmed();
	lastStart = q.value("pipeline_laststart").toDateTime();
	lastFinish = q.value("pipeline_lastfinish").toDateTime();
	lastCheck = q.value("pipeline_lastcheck").toDateTime();
    completeFiles = q.value("pipeline_desc").toString().trimmed().split(",", Qt::SkipEmptyParts);
	numConcurrentAnalysis = q.value("pipeline_numproc").toInt();
	queue = q.value("pipeline_queue").toString().trimmed();
	submitHost = q.value("pipeline_submithost").toString().trimmed();
	clusterType = q.value("pipeline_clustertype").toString().trimmed();
	clusterUser = q.value("pipeline_clusteruser").toString().trimmed();
	maxWallTime = q.value("pipeline_maxwalltime").toInt();
	submitDelay = q.value("pipeline_submitdelay").toInt();
	dataCopyMethod = q.value("pipeline_datacopymethod").toString().trimmed();
	notes = q.value("pipeline_notes").toString().trimmed();
	useProfile = q.value("pipeline_useprofile").toBool();
	removeData = q.value("pipeline_removedata").toBool();
	resultScript = q.value("pipeline_resultsscript").toString().trimmed();
	enabled = q.value("pipeline_enabled").toBool();
	testing = q.value("pipeline_testing").toBool();
	isPrivate = q.value("pipeline_isprivate").toBool();
	isHidden = q.value("pipeline_ishidden").toBool();
	version = q.value("pipeline_version").toInt();


	if (submitHost == "")
		submitHost = n->cfg["clustersubmithost"];

	//elsif (($pipelinesubmitdelay eq "") || ($pipelinesubmitdelay == 0)) {
	//	$pipelinesubmitdelay = 6;
	//}

	if (dirStructure == "b")
		pipelineRootDir = n->cfg["analysisdirb"];
	else
		pipelineRootDir = n->cfg["analysisdir"];

	/* remove any whitespace from the queue... SGE hates whitespace */
	queue.replace(QRegularExpression("\\s+"),"");

	isValid = true;
	msg = "Loaded pipeline details";
}
