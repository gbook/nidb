/* ------------------------------------------------------------------------------
  NIDB modulePipeline.cpp
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

#include "modulePipeline.h"
#include <QSqlQuery>

modulePipeline::modulePipeline()
{

}

/* ---------------------------------------------------------- */
/* --------- modulePipeline --------------------------------- */
/* ---------------------------------------------------------- */
modulePipeline::modulePipeline(nidb *a)
{
	n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~modulePipeline -------------------------------- */
/* ---------------------------------------------------------- */
modulePipeline::~modulePipeline()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int modulePipeline::Run() {
	n->WriteLog("Entering the QC module");

	int ret(0);

	return ret;
}


/* ---------------------------------------------------------- */
/* --------- IsQueueFilled ---------------------------------- */
/* ---------------------------------------------------------- */
int modulePipeline::IsQueueFilled(int pid) {

	/* find out how many processes are allowed to run */
	int numprocallowed = 0;
	QSqlQuery q;
	q.prepare("select pipeline_enabled, pipeline_numproc from pipelines where pipeline_id = :pid");
	q.bindValue(":pid",pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		numprocallowed = q.value("pipeline_numproc").toInt();
	}

	/* if numprocallowed is 0, the pipeline may have disappeared, or someone set the concurrent limit to 0
	   in which case this check will never be valid, so exit the look with a return code of 2 */
	if (numprocallowed == 0)
		return 2;

	/* find out how many processes are actually running */
	int numprocrunning = 0;
	q.prepare("select count(*) 'count' from analysis where pipeline_id = :pid and (analysis_status = 'processing' or analysis_status = 'started' or analysis_status = 'submitted' or analysis_status = 'pending')");
	q.bindValue(":pid",pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		numprocrunning = q.value("count").toInt();
	}

	if (numprocrunning >= numprocallowed)
		return 1;
	else
		return 0;
}


/* ---------------------------------------------------------- */
/* --------- GetGroupList ----------------------------------- */
/* ---------------------------------------------------------- */
QStringList modulePipeline::GetGroupList(int pid) {

	QStringList grouplist;

	/* get list of groups associated with this pipeline */

	QSqlQuery q;
	q.prepare("select pipeline_groupid from pipelines where pipeline_id = :pid");
	q.bindValue(":pid",pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int groupid = q.value("pipeline_groupid").toInt();
			if (groupid != 0) {
				QSqlQuery q2;
				q2.prepare("select group_name from groups where group_id in (:groupid)");
				q2.bindValue(":groupid",groupid);
				n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
				if (q2.size() > 0) {
					QString groupname = q2.value("group_name").toString();
					grouplist.append(groupname);
				}
			}
		}
	}

	return grouplist;
}


/* ---------------------------------------------------------- */
/* --------- GetPipelineList -------------------------------- */
/* ---------------------------------------------------------- */
QVector<int> modulePipeline::GetPipelineList() {
	QVector<int> a;

	/* get list of enabled pipelines */
	QSqlQuery q("select * from pipelines where pipeline_enabled = 1 order by pipeline_createdate asc");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int pid = q.value("pipeline_id").toInt();
			a.append(pid);
		}
		n->WriteLog(QString("Found [%1] enabled pipelines").arg(a.size()));
	}
	else
		n->WriteLog("Found no enabled pipelines");

	return a;
}


/* ---------------------------------------------------------- */
/* --------- CheckDependency -------------------------------- */
/* ---------------------------------------------------------- */
QString modulePipeline::CheckDependency(int sid, int pipelinedep) {

	QString status;

	/* check if the dependency exists */
	QSqlQuery q;
	q.prepare("select * from analysis where study_id = :sid and pipeline_id = :pipelinedep");
	q.bindValue(":sid", sid);
	q.bindValue(":pipelinedep", pipelinedep);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1)
		return "NoMatchingStudyDependency";

	/* check if the dependency is complete */
	q.prepare("select * from analysis where study_id = :sid and pipeline_id = :pipelinedep and analysis_status = 'complete'");
	q.bindValue(":sid", sid);
	q.bindValue(":pipelinedep", pipelinedep);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1)
		return "IncompleteDependency";

	/* check if the dependency is marked as bad */
	q.prepare("select * from analysis where study_id = :sid and pipeline_id = :pipelinedep and analysis_isbad <> 1");
	q.bindValue(":sid", sid);
	q.bindValue(":pipelinedep", pipelinedep);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1)
		return "BadDependency";

	return "";
}


/* ---------------------------------------------------------- */
/* --------- IsPipelineEnabled ------------------------------ */
/* ---------------------------------------------------------- */
bool modulePipeline::IsPipelineEnabled(int pid) {

	bool enabled = false;

	QSqlQuery q;
	q.prepare("select * from pipelines where pipeline_id = :pid");
	q.bindValue(":pid", pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		if (q.value("pipeline_enabled").toBool()) {
			enabled = true;
		}
	}

	return enabled;
}


/* ---------------------------------------------------------- */
/* --------- SetPipelineStopped ----------------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::SetPipelineStopped(int pid) {

	QSqlQuery q;
	q.prepare("update pipelines set pipeline_status = 'stopped', pipeline_lastfinish = now() where pipeline_id = :pid");
	q.bindValue(":pid", pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SetPipelineDisabled ---------------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::SetPipelineDisabled(int pid) {

	QSqlQuery q;
	q.prepare("update pipelines set pipeline_enabled = 0 where pipeline_id = :pid");
	q.bindValue(":pid", pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SetPipelineRunning ----------------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::SetPipelineRunning(int pid) {

	QSqlQuery q;
	q.prepare("update pipelines set pipeline_status = 'running', pipeline_laststart = now() where pipeline_id = :pid");
	q.bindValue(":pid", pid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SetPipelineStatusMessage ----------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::SetPipelineStatusMessage(int pid, QString msg) {

	QSqlQuery q;
	q.prepare("update pipelines set pipeline_statusmessage = :msg where pipeline_id = :pid");
	q.bindValue(":pid", pid);
	q.bindValue(":msg", msg);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SetPipelineProcessStatus ----------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::SetPipelineProcessStatus(QString status, int pipelineid, int studyid) {

	QSqlQuery q;

	if (status == "started") {
		q.prepare("insert ignore into pipeline_procs (pp_processid, pp_status, pp_startdate, pp_lastcheckin, pp_currentpipeline, pp_currentsubject, pp_currentstudy) values (:processid,'started',now(),now(),0,0,0)");
		q.bindValue(":processid", QCoreApplication::applicationPid());
	}
	else if (status == "complete") {
		q.prepare("delete from pipeline_procs where pp_processid = :processid");
		q.bindValue(":processid", QCoreApplication::applicationPid());
	}
	else {
		q.prepare("update pipeline_procs set pp_status = 'running', pp_lastcheckin = now(), pp_currentpipeline = :pipelineid, pp_currentstudy = :studyid where pp_processid = :processid");
		q.bindValue(":pipelineid", pipelineid);
		q.bindValue(":studyid", studyid);
		q.bindValue(":processid", QCoreApplication::applicationPid());
	}

	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- GetUIDStudyNumListByGroup ---------------------- */
/* ---------------------------------------------------------- */
QStringList modulePipeline::GetUIDStudyNumListByGroup(QString group) {

	QStringList uidlist;

	/* get list of groups associated with this pipeline */
	QSqlQuery q;
	q.prepare("select concat(uid,cast(study_num as char)) 'uidstudynum' from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = (select group_id from groups where group_name = :group) group by d.uid order by d.uid,b.study_num");
	q.bindValue(":group", group);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			QString uidstudynum = q.value("uidstudynum").toString().trimmed();
			uidlist.append(uidstudynum);
		}
	}

	return uidlist;
}


/* ---------------------------------------------------------- */
/* --------- GetPipelineSteps ------------------------------- */
/* ---------------------------------------------------------- */
QList<pipelineStep> modulePipeline::GetPipelineSteps(int pipelineid, int version) {

	QList<pipelineStep> steps;

	/* get data definition */
	QSqlQuery q;
	q.prepare("select * from pipeline_steps where pipeline_id = :pipelineid and pipeline_version = :version order by ps_order asc");
	q.bindValue(":pipelineid", pipelineid);
	q.bindValue(":version", version);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			pipelineStep rec;
			rec.id = q.value("pipelinestep_id").toInt();
			rec.command = q.value("ps_command").toString();
			rec.supplement = q.value("ps_supplement").toBool();
			rec.workingDir = q.value("ps_workingdir").toString();
			rec.order = q.value("ps_order").toInt();
			rec.description = q.value("ps_description").toString();
			rec.logged = q.value("ps_logged").toBool();
			rec.enabled = q.value("ps_enabled").toBool();
			steps.append(rec);
		}
	}
	return steps;
}


/* ---------------------------------------------------------- */
/* --------- GetPipelineDataDef ----------------------------- */
/* ---------------------------------------------------------- */
QList<dataDefinitionStep> modulePipeline::GetPipelineDataDef(int pipelineid, int version) {

	QList<dataDefinitionStep> datadef;

	/* get data definition */
	QSqlQuery q;
	q.prepare("select * from pipeline_data_def where pipeline_id = :pipelineid and pipeline_version = :version order by pdd_type, pdd_order asc");
	q.bindValue(":pipelineid", pipelineid);
	q.bindValue(":version", version);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			dataDefinitionStep rec;
			rec.id = q.value("pipelinedatadef_id").toInt();
			rec.order = q.value("pdd_order").toInt();
			rec.type = q.value("pdd_type").toString().trimmed();
			rec.criteria = q.value("pdd_seriescriteria").toString().trimmed();
			rec.assoctype = q.value("pdd_assoctype").toString().trimmed();
			rec.protocol = q.value("pdd_protocol").toString().trimmed();
			rec.modality = q.value("pdd_modality").toString().trimmed();
			rec.dataformat = q.value("pdd_dataformat").toString().trimmed();
			rec.imagetype = q.value("pdd_imagetype").toString().trimmed();
			rec.gzip = q.value("pdd_gzip").toBool();
			rec.location = q.value("pdd_location").toString().trimmed();
			rec.useseries = q.value("pdd_useseries").toBool();
			rec.preserveseries = q.value("pdd_preserveseries").toBool();
			rec.usephasedir = q.value("pdd_usephasedir").toBool();
			rec.behformat = q.value("pdd_behformat").toString().trimmed();
			rec.behdir = q.value("pdd_behdir").toString().trimmed();
			rec.enabled = q.value("pdd_enabled").toBool();
			rec.optional = q.value("pdd_optional").toBool();
			rec.numboldreps = q.value("pdd_numboldreps").toInt();
			rec.level = q.value("pdd_level").toString().trimmed();
			datadef.append(rec);
		}
	}
	return datadef;
}


/* ---------------------------------------------------------- */
/* --------- FormatCommand ---------------------------------- */
/* ---------------------------------------------------------- */
QString modulePipeline::FormatCommand(int pipelineid, QString clusteranalysispath, QString command, QString analysispath, int analysisid, QString uid, int studynum, QString studydatetime, QString pipelinename, QString workingdir, QString description) {

	    command.replace("{NOLOG}",""); // remove any {NOLOG} commands
		command.replace("{NOCHECKIN}",""); // remove any {NOCHECKIN} commands
		command.replace("x0D",""); // remove any ^M characters
		command.replace("{analysisrootdir}", analysispath, Qt::CaseInsensitive);
		command.replace("{analysisid}", QString("%1").arg(analysisid), Qt::CaseInsensitive);
		command.replace("{subjectuid}", uid, Qt::CaseInsensitive);
		command.replace("{studynum}", QString("%1").arg(studynum), Qt::CaseInsensitive);
		command.replace("{uidstudynum}", QString("%1%2").arg(uid).arg(studynum), Qt::CaseInsensitive);
		command.replace("{studydatetime}", studydatetime, Qt::CaseInsensitive);
		command.replace("{pipelinename}", pipelinename, Qt::CaseInsensitive);
		command.replace("{workingdir}", workingdir, Qt::CaseInsensitive);
		command.replace("{description}", description, Qt::CaseInsensitive);

		// expand {groups}
		QStringList groups = GetGroupList(pipelineid);
		//#WriteLog("@groups");
		QString grouplist = groups.join(" ");
		//#WriteLog("Group list: $grouplist");
		//#WriteLog("Replacing '{groups}' with '$grouplist'");
		command.replace("{groups}", grouplist, Qt::CaseInsensitive);

		QStringList alluidstudynums;
		foreach (QString group, groups) {
			// {numsubjects_groupname}
			// {uidstudynums_groupname}
			QStringList uidStudyNums = GetUIDStudyNumListByGroup(group);
			alluidstudynums.append(uidStudyNums);
			QString uidlist = uidStudyNums.join(" ");
			//numuids = $#uidStudyNums+1;
			//#WriteLog("Replacing '{uidstudynums_$group}' with '$uidlist'");
			command.replace("{uidstudynums_"+group+"}", uidlist, Qt::CaseInsensitive);
			//#WriteLog("Replacing '{numsubjects_$group}' with '$numuids'");
			command.replace("{numsubjects_"+group+"}", QString("%1").arg(uidStudyNums.size()), Qt::CaseInsensitive);
		}
		QString alluidlist = alluidstudynums.join(" ");
		//my $numsubjects = $#alluidstudynums+1;
		//#WriteLog("Replacing '{uidstudynums}' with '$alluidlist'");
		command.replace("{uidstudynums}", alluidlist, Qt::CaseInsensitive);
		//#WriteLog("Replacing '{numsubjects}' with '$numsubjects'");
		command.replace("{numsubjects}", QString("%1").arg(alluidstudynums.size()), Qt::CaseInsensitive);

		/* not really sure of the utility of these commands... doing this from bash may be more straightforward */
		//#WriteLog("Command (check0): [$command]");
		QRegularExpression regex("\\s+(\\S*)\\{first_(.*)_file\\}", QRegularExpression::CaseInsensitiveOption);
		if (command.contains(regex)) {
			//#WriteLog("Command (check1): [$command]");
			QRegularExpressionMatch match = regex.match(command);
			QString file = match.captured(0);
			QString ext = match.captured(1);
			QString searchpattern = QString("%2*.%3").arg(clusteranalysispath).arg(file).arg(ext);
			//WriteLog("Searchpath: [$searchpath]");
			QStringList files = n->FindAllFiles(clusteranalysispath, searchpattern);
			QString replacement = files[0];
			replacement.replace(clusteranalysispath, analysispath, Qt::CaseInsensitive);
			command.replace(regex, replacement);
		}
		//if ($command =~ m/\s+(\S*)\{first_(\d+)_(.*)_files\}/) {
		//	//#WriteLog("Command (check2): [$command]");
		//	my $path = $1;
		//	my $numfiles = $2;
		//	my $ext = $3;
		//	my $searchpath = "$realanalysispath/$path*.$ext";
		//	my @files = glob $searchpath;
		//	my $replacement = "";
		//	foreach my $j (0..$numfiles - 1) {
		//		$replacement .= " ".$files[$j];
		//	}
		//	$command = s/\s+(\S*)\{first_(\d+)_(.*)_file\}/ $replacement/g;
		//}
		//if ($command =~ m/ (.*)\{last_(.*)_file\}/) {
		    //#WriteLog("Command (check3): [$command]");
		//	my $path = $1;
		//	my $ext = $2;
		//	my $searchpath = "$realanalysispath/$path*.$ext";
		//	my @files = glob $searchpath;
		//	my $replacement = $files[-1];
		//	$command = s/\s+(\S*)\{last_(.*)_file\}/ $replacement/g;
		//}
		//#WriteLog("Command (check4): [$command]");
		command.replace("{command}", command, Qt::CaseInsensitive);
		//#WriteLog("Command (check5): [$command]");

		// remove semi-colon from the end of the line in case its there (it will prevent logging)
		if (command.right(1) == ";")
			command.chop(1);
		//#WriteLog("Command (check6): [$command]");

		return command;
}


/* ---------------------------------------------------------- */
/* --------- CreateClusterJobFile --------------------------- */
/* ---------------------------------------------------------- */
bool modulePipeline::CreateClusterJobFile(QString jobfilename, QString clustertype, int analysisid, bool isgroup, QString uid, int studynum, QString analysispath, bool usetmpdir, QString tmpdir, QString studydatetime, QString pipelinename, int pipelineid, QString resultscript, int maxwalltime,  QList<pipelineStep> steps, bool runsupplement, bool pipelineuseprofile, bool removedata) {

	bool rerunresults(false);

	/* check if this analysis only needs part of it rerun, and not the whole thing */
	QSqlQuery q;
	q.prepare("select * from analysis where analysis_id = :analysisid");
	q.bindValue(":analysisid", analysisid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0)
		rerunresults = q.value("analysis_rerunresults").toBool();

	n->WriteLog(QString("ReRunResults: [%1]").arg(rerunresults));

	QString checkinscript = "analysischeckin.pl";
	QString jobfile;
	QString clusteranalysispath = analysispath;

	QString workinganalysispath = QString("%1/%2-%3").arg(tmpdir).arg(pipelinename).arg(analysisid);

	n->WriteLog("Analysis path [" + analysispath + "]");
	n->WriteLog("Working Analysis path (temp directory) [" + workinganalysispath + "]");

	// different submission parameters for slurm
	if (clustertype == "slurm") {
		jobfile += "#!/bin/sh\n";
		if (runsupplement)
			jobfile += "#$ -J "+pipelinename+"-supplement\n";
		else
			jobfile += "#$ -J "+pipelinename+"\n";

		jobfile += "#$ -o "+analysispath+"/pipeline/\n";
		jobfile += "#$ --export=ALL\n";
		jobfile += "#$ --uid=" + n->cfg["queueuser"] + "\n\n";
	}
	else { // assuming SGE or derivative if not slurm
		jobfile += "#!/bin/sh\n";
		if (runsupplement)
			jobfile += "#$ -N "+pipelinename+"-supplement\n";
		else
			jobfile += "#$ -N "+pipelinename+"\n";

		jobfile += "#$ -S /bin/bash\n";
		jobfile += "#$ -j y\n";
		jobfile += "#$ -o "+analysispath+"/pipeline\n";
		jobfile += "#$ -V\n";
		jobfile += "#$ -u " + n->cfg["queueuser"] + "\n";
		if (maxwalltime > 0) {
			int hours = int(floor(maxwalltime/60));
			int min = maxwalltime % 60;

			jobfile += QString("#$ -l h_rt=%1:%2:00\n").arg(hours, 'f', 2).arg(min, 'f', 2);
		}
	}

	jobfile += "echo Hostname: `hostname`\n";
	jobfile += "echo Hostname: `whoami`\n\n";
	if ((resultscript != "") && (rerunresults))
		jobfile += QString("perl /opt/pipeline/%1 %2 startedrerun 'Cluster processing started'\n").arg(checkinscript).arg(analysisid);
	else if (runsupplement)
		jobfile += QString("perl /opt/pipeline/%1 %2 startedsupplement 'Supplement processing started'\n").arg(checkinscript).arg(analysisid);
	else
		jobfile += QString("perl /opt/pipeline/%1 %2 started 'Cluster processing started'\n").arg(checkinscript).arg(analysisid);

	jobfile += "cd "+analysispath+";\n";
	if (usetmpdir) {
		jobfile += QString("perl /opt/pipeline/%1 %2 started 'Beginning data copy to /tmp'\n").arg(checkinscript).arg(analysisid);
		jobfile += "mkdir -pv " + workinganalysispath + "\n";
		jobfile += "cp -Rv " + analysispath + "/* " + workinganalysispath + "/\n";
		jobfile += QString("perl /opt/pipeline/%1 %2 started 'Done copying data to /tmp'\n").arg(checkinscript).arg(analysisid);
	}

	// check if any of the variables might be blank
	if ((analysispath == "") || (workinganalysispath == "") || (analysisid == 0) || (uid == "") || (studynum == 0) || (studydatetime == ""))
		return false;

	QDir::setCurrent(clusteranalysispath);
	if (!rerunresults) {
		/* go through list of data search criteria */
		for (int i=0; i<steps.size(); i++) {
			int id = steps[i].id;
			int order = steps[i].order;
			bool issupplement = steps[i].supplement;
			QString command = steps[i].command;
			QString workingdir = steps[i].workingDir;
			QString description = steps[i].description;
			bool logged = steps[i].logged;
			bool enabled = steps[i].enabled;
			bool checkedin = true;
			bool profile = false;

			QString supplement;
			if (issupplement)
				supplement = "supplement-";

			// check if we are operating on regular commands or supplement commands
			// n->WriteLog("PRE: runsupplement [$runsupplement] issupplement [$issupplement] - $command");

			if (runsupplement && !issupplement)
				continue;

			if (!runsupplement && issupplement)
				continue;
			// n->WriteLog("POST: runsupplement [$runsupplement] issupplement [$issupplement] - $command");

			if ((command.contains("{NOLOG}")) || (description.contains("{NOLOG}")))
				logged = false;
			if ((command.contains("{NOCHECKIN}")) || (description.contains("{NOCHECKIN}")))
				checkedin = false;
			if ((command.contains("{PROFILE}")) || (description.contains("{PROFILE}")))
				profile = true;

			// format the command (replace pipeline variables, etc)
			if (usetmpdir)
				command = FormatCommand(pipelineid, clusteranalysispath, command, workinganalysispath, analysisid, uid, studynum, studydatetime, pipelinename, workingdir, description);
			else
				command = FormatCommand(pipelineid, clusteranalysispath, command, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, workingdir, description);

			if (checkedin) {
				QString cleandesc = description;
				cleandesc.replace("'","").replace("\"","");
				jobfile += QString("\nperl /opt/pipeline/%1 %2 processing 'processing %3step %4 of %5' '%6'").arg(checkinscript).arg(analysisid).arg(supplement).arg(order).arg(steps.size()).arg(cleandesc);

				jobfile += "\n# " + description + "\necho Running " + command + "\n";
			}

			// prepend with 'time' if the neither NOLOG nor NOCHECKIN are specified
			if (profile && logged && checkedin)
				command = "/usr/bin/time -v " + command;

			// write to a log file if logging is requested
			if (logged)
				command += QString(" >> " + analysispath + "/pipeline/" + supplement + "step%1.log 2>&1").arg(order);

			if (workingdir != "")
				jobfile += "cd " + workingdir + ";\n";

			if (!enabled)
				jobfile += "# ";

			jobfile += command + "\n";
		}
	}
	if (usetmpdir) {
		jobfile += QString("perl /opt/pipeline/%1 %2 started 'Copying data from temp dir'\n").arg(checkinscript).arg(analysisid);
		jobfile += "cp -Ruv " + workinganalysispath + "/* " + analysispath + "/\n";
		jobfile += QString("perl /opt/pipeline/%1 %2 started 'Deleting temp dir'\n").arg(checkinscript).arg(analysisid);
		jobfile += "rm --preserve-root -rv " + workinganalysispath + "\n";
	}

	if ((resultscript != "") && (rerunresults)) {
		//jobfile += "env\n";
		// tack on the result script command
		QString resultcommand = FormatCommand(pipelineid, clusteranalysispath, resultscript, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, "", "");
		resultcommand += " > " + analysispath + "/pipeline/stepResultScript.log 2>&1";
		jobfile += QString("\nperl /opt/pipeline/%1 %2 processing 'Processing result script'\n# Running result script\necho Running %3\n").arg(checkinscript).arg(analysisid).arg(resultcommand);
		jobfile += resultcommand + "\n";

		jobfile += QString("perl /opt/pipeline/%1 %2 completererun 'Results re-run complete'\n").arg(checkinscript).arg(analysisid);
		jobfile += "chmod -Rf 777 " + analysispath;
	}
	else {
		// run the results import script
		QString resultcommand = FormatCommand(pipelineid, clusteranalysispath, resultscript, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, "", "");
		resultcommand += " > " + analysispath + "/pipeline/stepResultScript.log 2>&1";
		jobfile += QString("\nperl /opt/pipeline/%1 %2 processing 'Processing result script'\n# Running result script\necho Running %3\n").arg(checkinscript).arg(analysisid).arg(resultcommand);
		jobfile += resultcommand + "\n";

		// clean up and log everything
		jobfile += "chmod -Rf 777 " + analysispath + "\n";
		if (runsupplement) {
			jobfile += QString("perl /opt/pipeline/%1 %2 processing 'Updating analysis files'\n").arg(checkinscript).arg(analysisid);
			jobfile += QString("perl /opt/pipeline/UpdateAnalysisFiles.pl -a %1 -d %2\n").arg(analysisid).arg(analysispath);
			jobfile += QString("perl /opt/pipeline/%1 %2 processing 'Checking for completed files'\n").arg(checkinscript).arg(analysisid);
			jobfile += QString("perl /opt/pipeline/CheckCompleteResults.pl -a %1 -d %2\n").arg(analysisid).arg(analysispath);
			jobfile += QString("perl /opt/pipeline/%1 %2 completesupplement 'Supplement processing complete'\n").arg(checkinscript).arg(analysisid);
		}
		else {
			jobfile += QString("perl /opt/pipeline/%1 %2 processing 'Updating analysis files'\n").arg(checkinscript).arg(analysisid);;
			jobfile += QString("perl /opt/pipeline/UpdateAnalysisFiles.pl -a %1 -d %2\n").arg(analysisid).arg(analysispath);
			jobfile += QString("perl /opt/pipeline/%1 %2 processing 'Checking for completed files'\n").arg(checkinscript).arg(analysisid);
			jobfile += QString("perl /opt/pipeline/CheckCompleteResults.pl -a %1 -d %2\n").arg(analysisid).arg(analysispath);
			jobfile += QString("perl /opt/pipeline/%1 %2 complete 'Cluster processing complete'\n").arg(checkinscript).arg(analysisid);
		}
		jobfile += "chmod -Rf 777 " + analysispath;
	}

	/* write out the file */

	return true;
}
