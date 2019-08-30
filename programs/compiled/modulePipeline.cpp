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
#include "TextTable.h"


/* ---------------------------------------------------------- */
/* --------- modulePipeline --------------------------------- */
/* ---------------------------------------------------------- */
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
	n->WriteLog("Entering the pipeline module");

	int numchecked = 0;
	bool jobsWereSubmitted = false;
	QSqlQuery q;

	/* update the start time */
	SetPipelineProcessStatus("started",0,0);

	/* get list of pipelines that are not currently running, sorted by the longest since last run
	   we're only going to run 1 pipeline per instance of this module */
	q.prepare("select pipeline_id from pipelines where pipeline_status <> 'running' and (pipeline_enabled = 1 or pipeline_testing = 1) order by pipeline_laststart asc");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1) {
		n->WriteLog("No pipelines need to be run. Exiting module");
		SetPipelineProcessStatus("complete",0,0);
		return false;
	}

	/* get the list of pipelines to be run */
	while (q.next()) {
		n->ModuleRunningCheckIn();

		int pipelineid = q.value("pipeline_id").toInt();

		pipeline p(pipelineid, n);
		if (!p.isValid) {
			n->WriteLog("Pipeline was not valid [in Run()]: [" + p.msg + "]");
			continue;
		}

		QString analysisdir;
		if (p.dirStructure == "b")
			analysisdir = n->cfg["analysisdirb"];
		else
			analysisdir = n->cfg["analysisdir"];

		n->WriteLog(QString("Working on pipeline [%1] - [%2] Submits to queue [%3] through host [%4]").arg(pipelineid).arg(p.name).arg(p.queue).arg(p.submitHost));

		SetPipelineProcessStatus("running",pipelineid,0);

		/* mark the pipeline as having been checked */
		QSqlQuery q2;
		q2.prepare("update pipelines set pipeline_lastcheck = now() where pipeline_id = :pid");
		q2.bindValue(":pid", pipelineid);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

		if (p.queue == "") {
			n->WriteLog("No queue specified");
			SetPipelineStatusMessage(pipelineid, "No queue specified");
			SetPipelineStopped(pipelineid);
			continue;
		}

		/* check if the pipeline is running, if so, go on to the next one */
		QString status;
		q2.prepare("select pipeline_status from pipelines where pipeline_id = :pid");
		q2.bindValue(":pid", pipelineid);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			status = q2.value("pipeline_status").toString();
		}
		if (status == "running") {
			// another process has started running this pipeline, so go on to the next one
			n->WriteLog("This pipeline is already running");
			continue;
		}

		/* update the pipeline status */
		SetPipelineStatusMessage(pipelineid, "Submitting jobs");
		SetPipelineRunning(pipelineid);

		n->WriteLog(QString("Running pipeline id [%1] name [%2]").arg(pipelineid).arg(p.name));

		QList<dataDefinitionStep> dataSteps;
		if (p.level != 0) {
			if ((p.level == 1) || ((p.level == 2) && (p.parentDependencyIDs.size() < 1))) {
				dataSteps = GetPipelineDataDef(pipelineid, p.version);

				/* if there is no data definition and no dependency */
				if ((dataSteps.size() < 1) && (p.parentDependencyIDs.size() < 1)) {
					n->WriteLog(QString("Pipeline [%1 - %2] has no data definition. Skipping.").arg(p.name).arg(pipelineid));

					/* update the statuses, and stop the modules */
					SetPipelineStatusMessage(pipelineid, "Pipeline has no data definition. Skipping");
					SetPipelineStopped(pipelineid);
					continue;
				}
			}
		}

		/* get the pipeline steps (the script) */
		QList<pipelineStep> steps = GetPipelineSteps(pipelineid, p.version);
		if (steps.size() < 1) {
			n->WriteLog(QString("Pipeline [%1 - %2] has no steps. Skipping.").arg(p.name).arg(pipelineid));

			/* update the statuses and stop the modules */
			SetPipelineStatusMessage(pipelineid, "Pipeline has no steps. Skipping");
			SetPipelineStopped(pipelineid);
			continue;
		}

		/* ------------------------------ level 0 ----------------------------- */
		if (p.level == 0) {
			/* check if this module should be running now or not */
			if (!n->ModuleCheckIfActive()) {
				n->WriteLog("Module disabled. Exiting");
				SetPipelineStatusMessage(pipelineid, "Pipeline module disabled while running. Stopping.");
				SetPipelineStopped(pipelineid);
				SetPipelineProcessStatus("complete",0,0);
				return 1;
			}

			/* only 1 analysis should ever be run with the oneshot level, so if 1 already exists, regardless of state or pipeline version, then
			   leave this function without running the analysis */
			q2.prepare("select * from analysis where pipeline_id = :pid");
			q2.bindValue(":pid", pipelineid);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			if (q2.size() > 0) {
				n->WriteLog("An analysis already exists for this one-shot level pipeline, exiting");
				SetPipelineStatusMessage(pipelineid, "An analysis already exists for this one-shot pipeline. Skipping");
				SetPipelineStopped(pipelineid);
				continue;
			}

			/* create the analysis path */
			QString analysispath = QString("%1/%3").arg(p.pipelineRootDir).arg(p.name);
			n->WriteLog("Creating path [" + analysispath + "/pipeline]");
			QString m;
			if (!n->MakePath(analysispath + "/pipeline", m)) {
				n->WriteLog("Error: unable to create directory ["+analysispath+"] - A");
				continue;
			}
			else
				n->WriteLog("Created directory [" + analysispath + "/pipeline] - A");

			/* this file will record any events during setup */
			QString setupLogFile = "/mount" + analysispath + "/pipeline/analysisSetup.log";
			n->AppendCustomLog(setupLogFile, "Beginning recording");
			n->WriteLog("Should have created this analysis setup log [" + setupLogFile + "]");

			/* insert a temporary row, to be updated later, in the analysis table as a placeholder
			 * so that no other processes end up running it */
			q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values (:pid, :version,'','','processing',now())");
			q2.bindValue(":pid", pipelineid);
			q2.bindValue(":version", p.version);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			int analysisRowID = q2.lastInsertId().toInt();

			/* create the cluster job file */
			QString sgefilepath = analysispath + "/sge.job";
			if (CreateClusterJobFile(sgefilepath, p.clusterType, analysisRowID, "UID", 0, analysispath, p.useTmpDir, p.tmpDir, "", p.name, pipelineid, p.resultScript, p.maxWallTime, steps, false)) {
				n->WriteLog("Created sge job submit file [" + sgefilepath + "]");
			}
			else {
				n->WriteLog("Error: unable to create sge job submit file [" + sgefilepath + "]");
				continue;
			}

			n->SystemCommand("chmod -Rf 777 " + analysispath, true, true);

			/* submit the cluster job file */
			QString qm, qresult;
			int jobid;
			if (n->SubmitClusterJob(sgefilepath, p.submitHost, n->cfg["qsubpath"], n->cfg["queueuser"], p.queue, qm, jobid, qresult)) {
				n->WriteLog("Successfully submitted job to cluster [" + qresult + "]");
				UpdateAnalysisStatus(analysisRowID, "submitted", "Submitted to [" + p.queue + "]", jobid, -1, "", "", true, false);
			}
			else {
				n->WriteLog("Error submitting job to cluster [" + qresult + "]");
				UpdateAnalysisStatus(analysisRowID, "error", "Submit error [" + qm + "]", 0, -1, "", "", false, true);
			}
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

			jobsWereSubmitted = true;
		}
		// ================================= LEVEL 1 =================================
		else if (p.level == 1) {

			QString pipelinedirectory;
			QSqlQuery q2;

			/* fix the directory if its not the default or blank */
			if (p.directory == "")
				pipelinedirectory = analysisdir;
			else
				pipelinedirectory = n->cfg["mountdir"] + pipelinedirectory;

			/* if there are multiple dependencies, we'll need to loop through all of them separately
			 * NOPE... we don't allow multiple dependencies. Yet. */
			//for(int i=0; i<p.parentDependencyIDs.size(); i++) {

			int pipelinedep = -1;
			if (p.parentDependencyIDs.size() > 0)
				pipelinedep = p.parentDependencyIDs[0];

			QString modality;
			modality = dataSteps[0].modality;

			/* get the list of studies which meet the criteria for being processed through the pipeline */
			QList<int> studyids = GetStudyToDoList(pipelineid, modality, pipelinedep, n->JoinIntArray(p.groupIDs, ","));

			int numsubmitted = 0;
			foreach (int sid, studyids) {

				int analysisRowID = -1;
				QStringList setuplog;
				QString setupLogFile;

				SetPipelineProcessStatus("running", pipelineid, sid);

				numchecked++;

				/* get information about the study */
				study s(sid, n);
				if (!s.isValid) {
					n->WriteLog("Study was not valid: [" + s.msg + "]");
					continue;
				}

				n->WriteLog(QString("--------------------- Working on study [%1%2] for pipeline [%3] --------------------").arg(s.uid).arg(s.studynum).arg(p.name));

				/* check if the number of concurrent jobs is reached. the function also checks if this pipeline module is enabled */
				//n->WriteLog("Checking if we've reached the max number of concurrent analyses");
				int filled;
				do {
					filled = IsQueueFilled(pipelineid);

					/* check if this pipeline is enabled */
					if (!IsPipelineEnabled(pipelineid)) {
						SetPipelineStatusMessage(pipelineid, "Pipeline disabled while running. Stopping at next iteration.");
						SetPipelineStopped(pipelineid);
						break;
					}

					/* check if this module is still enabled */
					if (!n->ModuleCheckIfActive()) {
						n->WriteLog("Module disabled. Exiting");
						SetPipelineStatusMessage(pipelineid, "Pipeline module disabled while running. Stopping.");
						SetPipelineStopped(pipelineid);
						SetPipelineProcessStatus("complete",0,0);
						return 1;
					}

					/* otherwise check what to do, depending on how filled the queue is */
					if (filled == 0)
						break;

					if (filled == 1) {
						SetPipelineStatusMessage(pipelineid, "Process quota reached. Waiting 1 minute to resubmit");
						n->WriteLog("Concurrent analysis quota reached, waiting 15 seconds");
						n->ModuleRunningCheckIn();
						QThread::sleep(15); /* sleep for 15 seconds */
					}
					if (filled == 2) {
						return 1;
					}
				} while (filled == 1);

				if (!IsPipelineEnabled(pipelineid)) {
					SetPipelineStatusMessage(pipelineid, "Pipeline disabled while running. Stopping at this iteration.");
					SetPipelineStopped(pipelineid);
					break;
				}

				/* get the analysis info, if an analysis already exists for this study */
				n->WriteLog(QString("Getting analysis info for pipelineID [%1] studyID [%2] pipelineVersion [%3]").arg(pipelineid).arg(sid).arg(p.version));
				analysis a(pipelineid, sid, n);
				if (a.exists)
					analysisRowID = a.analysisid;
				else
					analysisRowID = -1;

				setuplog << a.msg;

				// ********************
				// only continue through this section (and submit the analysis) if
				// a) there is no existing analysis
				// b) -OR- there is an existing analysis and it needs the results rerun
				// c) -OR- there is an existing analysis and it needs a supplement run
				// ********************
				//n->WriteLog(QString("Checking if we need to submit this analysis to the cluster [%1] [%2] [%3]").arg(a.runSupplement).arg(a.rerunResults).arg(analysisRowID));
				if ((a.runSupplement) || (a.rerunResults) || (analysisRowID == -1)) {
					/* if the analysis doesn't yet exist, insert a temporary row, to be updated later, in the analysis table as a placeholder so that no other pipeline processes try to run it */
					if (analysisRowID == -1) {
						/* check if this analysis already exists (probably created by another instance of this program ) */
						q2.prepare("select analysis_id from analysis where pipeline_id = :pipelineid and pipeline_version = :version and study_id = :studyid");
						q2.bindValue(":pipelineid",pipelineid);
						q2.bindValue(":version",p.version);
						q2.bindValue(":studyid",sid);
						n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
						if (q2.size() > 0) {
							q2.first();
							analysisRowID = q2.value("analysis_id").toInt();
						}
						else {
							q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values (:pipelineid, :version, :pipelinedep, :studyid,'processing',now())");
							q2.bindValue(":pipelineid",pipelineid);
							q2.bindValue(":version",p.version);
							q2.bindValue(":pipelinedep",pipelinedep);
							q2.bindValue(":studyid",sid);
							n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
							analysisRowID = q2.lastInsertId().toInt();
						}

						n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysiscreated", "Analysis created");
					}

					QString datalog;

					int dependencyanalysisid(0);
					bool submiterror = false;

					n->WriteLog(QString("StudyDateTime: [%1], Working on: [%2%3]").arg(s.studydatetime.toString("yyyy-MM-dd hh:mm:ss")).arg(s.uid).arg(s.studynum));

					QString analysispath = "";
					if (p.dirStructure == "b")
						analysispath = QString("%1/%2/%3/%4").arg(p.pipelineRootDir).arg(p.name).arg(s.uid).arg(s.studynum);
					else
						analysispath = QString("%1/%2/%3/%4").arg(p.pipelineRootDir).arg(s.uid).arg(s.studynum).arg(p.name);
					n->WriteLog("analysispath is [" + analysispath + "]");

					/* this file will record any events during setup */
					QString setupLogFile = analysispath + "/pipeline/analysisSetup.log";
					n->WriteLog("Should have created this analysis setup log [" + setupLogFile + "]");

					/* get the nearest study for this subject that has the dependency */
					int studyNumNearest(0);
					q2.prepare("select analysis_id, study_num from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.subject_id = :subjectid and a.pipeline_id = :pipelinedep and a.analysis_status = 'complete' and a.analysis_isbad <> 1 order by abs(datediff(b.study_datetime, :studydatetime)) limit 1");
					q2.bindValue(":subjectid", s.subjectid);
					q2.bindValue(":pipelinedep", pipelinedep);
					q2.bindValue(":studydatetime", s.studydatetime.toString("yyyy-MM-dd hh:mm:ss"));
					n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
					if (q2.size() > 0) {
						q2.first();
						studyNumNearest = q2.value("study_num").toInt();
						dependencyanalysisid = q2.value("analysis_id").toInt();
					}
					/* determine the dependency path */
					QString deppath;
					if (pipelinedep != -1) {
						if (p.depLevel == "subject") {
							setuplog << n->WriteLog("Dependency is a subject level (will match dep for same subject, any study)");
						}
						else {
							setuplog << n->WriteLog("Dependency is a study level (will match dep for same subject, same study)");

							/* check the dependency and see if there's anything amiss about it */
							QString depstatus = CheckDependency(sid, pipelinedep);
							if (depstatus != "") {
								UpdateAnalysisStatus(analysisRowID, "OddDependencyStatus", depstatus, -1, -1, setuplog.join("\n"), setuplog.join("\n"), false, false);
								continue;
							}
						}
					}
					setuplog << n->WriteLog("This analysis path is [" + analysispath + "]");

					int numseriesdownloaded = 0;
					/* get the data if we are not running a supplement, and not rerunning the results */
					if ((!a.runSupplement) && (!a.rerunResults)) {
						if (!GetData(sid, analysispath, s.uid, analysisRowID, pipelineid, pipelinedep, p.depLevel, dataSteps, numseriesdownloaded, datalog)) {
							n->WriteLog("GetData() returned false");
						}
						else
							n->WriteLog(QString("GetData() downloaded [%1] series").arg(numseriesdownloaded));
					}
					UpdateAnalysisStatus(analysisRowID, "", "", -1, numseriesdownloaded, "", datalog, false, false);

					// again check if there are any series to actually run the pipeline on...
					// ... but its ok to run if any of the following are true
					//     a) rerunresults is true
					//     b) runsupplement is true
					//     c) this pipeline is dependent on another pipeline
					bool okToRun = false;

					if (numseriesdownloaded > 0)
						okToRun = true; // there is data to download from this study
					if (a.rerunResults)
						okToRun = true;
					if (a.runSupplement)
						okToRun = true;
					if ((pipelinedep != -1) && (p.depLevel == "study"))
						okToRun = true; // there is a parent pipeline and we're using the same study from the parent pipeline. may or may not have data to download

					/* one of the above criteria has been satisfied, so its ok to run the pipeline on this study and submit the cluster */
					if (okToRun) {
						setuplog << n->WriteLog(QString(" ----- Study [%1] has [%2] matching series downloaded (or needs results rerun, or is a supplement, or is dependent on another pipeline). Beginning analysis ----- ").arg(sid).arg(numseriesdownloaded));

						QString dependencyname;
						/* this analysis is new and has not been written to disk before, so
						 * the directory should not yet exist */
						if ((!a.rerunResults) && (!a.runSupplement)) {
							if (pipelinedep != -1) {
								setuplog << n->WriteLog(QString("This pipeline depends on [%1]").arg(pipelinedep));
								q2.prepare("select pipeline_name from pipelines where pipeline_id = :pipelinedep");
								q2.bindValue(":pipelinedep", pipelinedep);
								n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
								if (q2.size() > 0) {
									q2.first();
									dependencyname = q2.value("pipeline_name").toString();
									setuplog << n->WriteLog(QString("Found [%1] rows for parent pipeline [%2]").arg(q2.size()).arg(dependencyname));
								}
								else {
									setuplog << n->WriteLog(QString("Parent pipeline [%1] does not exist!").arg(pipelinedep));
									SetPipelineStatusMessage(pipelineid, QString("Parent pipeline [%1] does not exist!").arg(pipelinedep));
									SetPipelineStopped(pipelineid);
									continue;
								}
								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This pipeline is dependent on [" + dependencyname + "]");
							}
							else {
								setuplog << n->WriteLog(QString("This pipeline does not depend on any pipelines [%1]").arg(pipelinedep));
							}

							//QString analysispath = p.pipelineRootDir + "/" + p.name;
							QString m;
							if (!n->MakePath(analysispath + "/pipeline", m)) {
								n->WriteLog("Error: unable to create directory [" + analysispath + "/pipeline] - B");
								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysiserror", "Unable to create directory [" + analysispath + "/pipeline]");
								UpdateAnalysisStatus(analysisRowID, "error", "Unable to create directory [" + analysispath + "/pipeline]", 0, -1, "", "", false, true);
								continue;
							}
							else
								n->WriteLog("Created directory [" + analysispath + "/pipeline] - B");

							n->WriteLog(n->SystemCommand("chmod -R 777 " + analysispath + "/pipeline", true, true));
							if (pipelinedep != -1) {
								if (p.depLevel == "subject") {
									if (p.dirStructure == "b")
										deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(dependencyname).arg(s.uid).arg(studyNumNearest);
									else
										deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(s.uid).arg(studyNumNearest).arg(dependencyname);
								}
								else {
									if (p.dirStructure == "b")
										deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(dependencyname).arg(s.uid).arg(s.studynum);
									else
										deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(s.uid).arg(s.studynum).arg(dependencyname);
								}

								setuplog << n->WriteLog("Dependency path is [" + deppath + "]");

								QString fulldeppath = deppath + "/" + dependencyname;
								QDir d(fulldeppath);
								if (d.exists())
									setuplog << n->WriteLog("Full path to parent pipeline [" + fulldeppath + "] exists");
								else
									setuplog << n->WriteLog("Full path to parent pipeline [" + fulldeppath + "] does NOT exist");

								setuplog << n->WriteLog(QString("This is a level [%1] pipeline. deplinktype [%2] depdir [%3]").arg(p.level).arg(p.depLinkType).arg(p.depDir));

								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", QString("Parent pipeline (dependency) will be copied to directory [%1] using method [%2]").arg(p.depDir).arg(p.depLinkType));

								/* copy any parent pipelines */
								QString systemstring;
								if (p.depLinkType == "hardlink") systemstring = "cp -aul ";
								else if (p.depLinkType == "softlink") systemstring = "cp -aus ";
								else if (p.depLinkType == "regularcopy") systemstring = "cp -au ";
								if (p.depDir == "subdir") {
									setuplog << n->WriteLog("Parent pipeline will be copied to a subdir");
									systemstring += deppath + " " + analysispath + "/";
								}
								else {
									setuplog << n->WriteLog("Parent pipeline will be copied to the root dir");
									systemstring += deppath + "/* " + analysispath + "/";
								}
								setuplog << n->WriteLog(n->SystemCommand(systemstring));

								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "Parent pipeline copied by running [" + systemstring + "]");
								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysisdependencyid", QString(dependencyanalysisid));

								/* delete any log files and SGE files that came with the dependency */
								setuplog << n->WriteLog(n->SystemCommand(QString("rm --preserve-root %1/pipeline/* %1/origfiles.log %1/sge.job").arg(analysispath)));

								/* make sure the whole tree is writeable */
								setuplog << n->WriteLog(n->SystemCommand("chmod -R 777 " + analysispath, true, true));
							}
							else {
								setuplog << n->WriteLog("This pipeline is not dependent on another pipeline");
								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This pipeline does not depend on other pipelines");
							}

							/* now safe to write out the setuplog */
							n->AppendCustomLog(setupLogFile, setuplog.join("\n"));

							/* however, if the setupLogFile does not exist, something is not writeable, and that is not good */
							if (!QFile::exists(setupLogFile)) {
								setuplog << n->WriteLog("setupLogFile [" + setupLogFile + "] does not exist.");
							}
						}
						/* "realanalysispath" is now --> "clusteranalysispath" */
						QString clusteranalysispath = analysispath;
						clusteranalysispath.replace("/mount","");

						/* create the SGE job file */
						QString localsgefilepath;
						QString clustersgefilepath;
						QString sgefilename;
						if (a.rerunResults)
							sgefilename = "sgererunresults.job";
						else if (a.runSupplement)
							sgefilename = "sge-supplement.job";
						else
							sgefilename = "sge.job";
						localsgefilepath = analysispath + "/" + sgefilename;
						clustersgefilepath = clusteranalysispath + "/" + sgefilename;

						if (CreateClusterJobFile(localsgefilepath, p.clusterType, analysisRowID, s.uid, s.studynum, clusteranalysispath, p.useTmpDir, p.tmpDir, s.studydatetime.toString("yyyy-MM-dd hh:mm:ss"), p.name, pipelineid, p.resultScript, p.maxWallTime, steps, false)) {
							n->WriteLog("Created (local path) sge job submit file [" + localsgefilepath + "]");
						}
						else {
							UpdateAnalysisStatus(analysisRowID, "error", "Error creating cluster job file", 0, -1, "", "", false, true);
							continue;
						}

						n->SystemCommand("chmod -Rf 777 " + analysispath, true, true);

						/* submit the cluster job file */
						QString qm, qresult;
						int jobid;
						if (n->SubmitClusterJob(clustersgefilepath, p.submitHost, n->cfg["qsubpath"], n->cfg["queueuser"], p.queue, qm, jobid, qresult)) {
							n->WriteLog("Successfully submitted job to cluster ["+qresult+"]");
							UpdateAnalysisStatus(analysisRowID, "submitted", "Submitted to [" + p.queue + "]", jobid, numseriesdownloaded, "", "", false, true);
							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmitted", qresult);
						}
						else {
							n->WriteLog("Error submitting job to cluster [" + qresult + "]");
							UpdateAnalysisStatus(analysisRowID, "error", "Submit error [" + qm + "]", 0, numseriesdownloaded, "", "", false, true);
							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmiterror", "Analysis submitted to cluster, but was rejected with errors [" + qm + "]");
							submiterror = true;
						}

						numsubmitted++;
						jobsWereSubmitted = true;

						SetPipelineStatusMessage(pipelineid, QString("Submitted %1%2").arg(s.uid).arg(s.studynum));

						/* check if this module should be running now or not */
						if (!n->ModuleCheckIfActive()) {
							n->WriteLog("Module disabled. Exiting");
							SetPipelineStatusMessage(pipelineid, "Pipeline module disabled while running. Stopping.");
							SetPipelineStopped(pipelineid);
							SetPipelineProcessStatus("complete",0,0);
							return 1;
						}
						/* wait 10 seconds before submitting the next job */
						QThread::sleep(10);
					}
					else {
						n->WriteLog("Not Ok to submit job");
						/* update the analysis table with the datalog so people can check later on why something didn't process */
						UpdateAnalysisStatus(analysisRowID, "", "", -1, -1, datalog, datalog, false, false);
						n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissetuperror", "No data found, 0 series returned from search");
					}
					n->WriteLog(QString("Submitted [%1] jobs so far").arg(numsubmitted));

					/* mark the study in the analysis table */
					n->WriteLog(QString("numseriesdownloaded [%1]  pipelinedep [%2]  deplevel [%3]  runSupplement [%4]  rerunResults [%5]").arg(numseriesdownloaded).arg(pipelinedep).arg(p.depLevel).arg(a.runSupplement).arg(a.rerunResults));
					if (!submiterror) {
						if ((numseriesdownloaded > 0) || ((pipelinedep != -1) && (p.depLevel == "study")) || (a.runSupplement) || (a.rerunResults)) {
							/* do nothing right here... :) */
						}
						else {
							// save some database space, since most entries will be blank
							UpdateAnalysisStatus(analysisRowID, "NoMatchingSeries", "", -1, -1, "", "", false, false);
							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This study did not have any matching data");
						}
					}
				}
				else {
					n->WriteLog(QString("This analysis [%1] already has an entry in the analysis table").arg(analysisRowID));
					n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This analysis already has an entry in the analysis table");
				}

				if ((numchecked%1000) == 0)
					n->WriteLog(QString("[%1] studies checked").arg(numchecked));

				/* check if this pipeline is still enabled */
				if (!IsPipelineEnabled(pipelineid)) {
					SetPipelineStatusMessage(pipelineid, "Pipeline disabled while running. Normal stop.");
					SetPipelineStopped(pipelineid);
					break;
				}
			}
		}
		/* ======================= LEVEL 2 ======================= */
		/* level 2 was once implemented but never used, and fell out of maintenance in the Perl
		 * version, so it's been deprecated and removed in the C++ version of NiDB */
		else if (p.level == 2) {
			n->WriteLog("Level 2 (group) pipelines are not yet implemented in the compiled version of NiDB");
		}
		else {
			n->WriteLog(QString("Invalid pipeline level [%1]").arg(p.level));
		}

		n->WriteLog(QString("Done with pipeline [%1] - [%2]").arg(pipelineid).arg(p.name));
		SetPipelineStatusMessage(pipelineid, "Finished submitting jobs");
		SetPipelineStopped(pipelineid);
	}

	SetPipelineProcessStatus("complete",0,0);

	if (jobsWereSubmitted)
		return true;
	else
		return false;
}


/* ---------------------------------------------------------- */
/* --------- GetData ---------------------------------------- */
/* ---------------------------------------------------------- */
bool modulePipeline::GetData(int studyid, QString analysispath, QString uid, int analysisid, int pipelineid, int pipelinedep, QString deplevel, QList<dataDefinitionStep> datadef, int &numdownloaded, QString &datalog) {

	numdownloaded = 0;
	QStringList dlog;
	datalog = "";

	/* get pipeline information, for data copying preferences */
	pipeline p(pipelineid, n);
	if (!p.isValid) {
		n->WriteLog("Pipeline was not valid [ in GetData()]: [" + p.msg + "]");
		return false;
	}
	QString submithost;
	QString clusteruser;
	if (p.submitHost == "") submithost = n->cfg["clustersubmithost"];
	else submithost = p.submitHost;
	if (p.clusterUser == "") clusteruser = n->cfg["clusteruser"];
	else clusteruser = n->cfg["pipeline_clusteruser"];

	/* get information about the study */
	study s(studyid, n);
	if (!s.isValid) {
		n->WriteLog("Study was not valid: [" + s.msg + "]");
		return false;
	}
	QString modality = s.modality;
	int studynum = s.studynum;

	dlog << QString("---------- Working on [%1%2] studyid [%3]   modality [%4] ----------").arg(uid).arg(studynum).arg(studyid).arg(modality);
	dlog << "---------- Checking data steps -----------------------------------------------";

	// ------------------------------------------------------------------------
	// check all of the steps to see if this data spec is valid
	// ------------------------------------------------------------------------
	bool stepIsInvalid = false;
	for (int i = 0; i < datadef.size(); i++) {
		QString protocol = datadef[i].protocol;
		QString modality = datadef[i].modality.toLower();
		QString imagetype = datadef[i].imagetype;
		bool enabled = datadef[i].enabled;
		QString type = datadef[i].type;
		QString level = datadef[i].level;
		QString assoctype = datadef[i].assoctype;
		bool optional = datadef[i].optional;
		QString numboldreps = datadef[i].numboldreps;

		/* its not efficient to do an insert and then a series of updates. But it doesn't need to be very efficient, and it's much easier to program */
		datadef[i].datadownloadid = RecordDataDownload(-1, analysisid, modality, 0, 0, -1, "", i, "");
		qint64 datadownloadid = datadef[i].datadownloadid;

		/* SQL query object for use throughout this loop */
		QSqlQuery q;

		/* use the correct SQL schema column name for the series_desc... */
		QString seriesdescfield = "series_desc";
		if (modality != "mr")
			seriesdescfield = "series_protocol";

		dlog << QString("Step [%1], CHECKING:  protocol [%2]  modality [%3]  imagetype [%4]  enabled [%5]  type [%6]  level [%7]  assoctype [%8]  optional [%9]  numboldreps [%10]").arg(i).arg(protocol).arg(modality).arg(imagetype).arg(enabled).arg(type).arg(level).arg(assoctype).arg(optional).arg(numboldreps);

		/* check if the step is enabled */
		if (!enabled) {
			dlog << QString("Pre-checking step [%1]: step is not enabled. Skipping").arg(i);
			RecordDataDownload(datadownloadid, analysisid, modality, 0, 0, -1, "", i, "Step not enabled, skipping step");
			continue;
		}

		/* check if the step is optional */
		if (optional) {
			dlog << QString("Pre-checking step [%1]: step is optional. Skipping").arg(i);
			RecordDataDownload(datadownloadid, analysisid, modality, 0, 0, -1, "", i, "Step is optional, skipping step");
			continue;
		}

		/* make sure the requested modality table exists */
		q.prepare(QString("show tables like '%1_series'").arg(modality.toLower()));
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() < 1) {
			dlog << QString("Pre-checking step [%1]: Modality [%2] is not valid").arg(i).arg(modality);
			RecordDataDownload(datadownloadid, analysisid, modality, 0, 0, -1, "", i, "Invalid modality. Stopping search");
			stepIsInvalid = true;
			break;
		}

		/* seperate any protocols that have multiples */
		QString protocols;
		if (protocol.contains("\"")) {
			QStringList prots = n->ShellWords(protocol);
			protocols = "'" + prots.join("','") + "'";
		}
		else
			protocols = "'" + protocol + "'";

		/* separate image types */
		QString imagetypes;
		if (imagetype.contains(",")) {
			QStringList types = imagetype.split(QRegularExpression(",\\s*"));
			imagetypes = "'" + types.join("','") + "'";
		}
		else
			imagetypes = "'" + imagetype + "'";

		/* expand the comparison into SQL */
		QString comparison;
		int num(0);
		bool validComparisonStr = false;
		if (n->GetSQLComparison(numboldreps, comparison, num))
			validComparisonStr = true;

		/* if its a subject level, check the subject for the protocol(s) */
		int subjectid = s.subjectid;
		QString studytype = s.studytype;
		QString studydate = s.studydatetime.toString("yyyy-MM-dd hh:mm:ss");
		if (level == "subject") {
			dlog << "This data step is subject level [" + protocol + "], association type [" + assoctype + "]";

			QString sqlstring;
			if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
				/* find the data from the same subject and modality that has the nearest (in time) matching scan */
				dlog << QString("Pre-checking step [%1] Searching for data from the same SUBJECT and modality that has the nearest (in time) matching scan").arg(i);

				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				sqlstring += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '%2' ) ) LIMIT 1").arg(modality).arg(studydate);

				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
			}
			else if (assoctype == "all") {
				dlog << QString("Pre-checking step [%1]: Searching for ALL data from the same SUBJECT and modality").arg(i);

				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
			}
			else {
				/* find the data from the same subject and modality that has the same study_type */
				dlog << QString("Pre-checking step [%1]: Searching for data from the same SUBJECT, Modality, and StudyType").arg(i);

				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				sqlstring += " and `studies`.study_type = :studytype";

				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
				q.bindValue(":studytype", studytype);
			}

			dlog << n->WriteLog("SQL: [" + n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__,true) + "]");
			if (q.size() > 0) {
				dlog << QString("Pre-checking step [%1]: data FOUND for this step (subject level)").arg(i);
				RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, -1, "", i, "Data found for this step (subject level)");
			}
			else {
				dlog << QString("Pre-checking step [%1]: data NOT found for this step (subject level)").arg(i);
				RecordDataDownload(datadownloadid, analysisid, modality, 1, 0, -1, "", i, "Data NOT found for this step (subject level). Stopping search");
				stepIsInvalid = true;
				break;
			}
		}
		/* otherwise, check the study for the protocol(s) */
		else {
			QString sqlstring;
			dlog << QString("Checking the study [%1] for the protocol (%2)").arg(studyid).arg(protocols);
			/* get a list of series satisfying the search criteria, if it exists */
			sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
			if (imagetypes != "''") {
				sqlstring += " and image_type in (" + imagetypes + ")";
			}
			if (validComparisonStr)
				sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);

			q.prepare(sqlstring);
			q.bindValue(":studyid", studyid);
			dlog << n->WriteLog(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
			if (q.size() > 0) {
				dlog << QString("Pre-checking step [%1]  protocol [%2]: data found (study level)").arg(i).arg(protocol);
				RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, -1, "", i, "Data found for this step (study level)");
			}
			else {
				dlog << QString("Pre-checking step [%1]  protocol [%2]: data NOT found (study level)").arg(i).arg(protocol);
				RecordDataDownload(datadownloadid, analysisid, modality, 1, 0, -1, "", i, "Data NOT found for this step (study level). Stopping search");
				stepIsInvalid = true;
				break;
			}
		}
	}
	dlog << "Done checking data steps";

	/* if it's a subject level dependency, but there is no data found, we don't want to copy any dependencies */
	if ((stepIsInvalid) && (deplevel == "subject")) {
		dlog << "stepIsInvalid is true, or deplevel is subject";
		datalog = dlog.join("\n");
		return false;
	}

	/* if there is a dependency, don't worry about the previous checks */
	if (pipelinedep != -1) {
		stepIsInvalid = false;
	}

	/* any bad data items, then the data spec didn't work out for this subject */
	if (stepIsInvalid) {
		dlog << "stepIsInvalid is true";
		datalog = dlog.join("\n");
		return false;
	}

	// ------ end checking the data steps --------------------------------------
	// if we get to here, the data spec is valid for this study
	// so we can assume all of the data exists, and start copying it
	// -------------------------------------------------------------------------

	n->InsertAnalysisEvent(analysisid, pipelineid, p.version, studyid, "analysiscopydata", "Started copying data to [<tt>" + analysispath + "</tt>]");

	dlog << "---------- Required data for this study exists, beginning data copy ----------";
	/* go through list of data search criteria again to do the actual copying */
	for (int i = 0; i < datadef.size(); i++) {
		QString criteria = datadef[i].criteria;
		QString type = datadef[i].type;
		QString assoctype = datadef[i].assoctype;
		QString protocol = datadef[i].protocol;
		QString modality = datadef[i].modality.toLower();
		QString dataformat = datadef[i].dataformat;
		QString imagetype = datadef[i].imagetype;
		bool gzip = datadef[i].gzip;
		QString location = datadef[i].location;
		bool useseries = datadef[i].useseries;
		bool preserveseries = datadef[i].preserveseries;
		bool usephasedir = datadef[i].usephasedir;
		QString behformat = datadef[i].behformat;
		QString behdir = datadef[i].behdir;
		bool enabled = datadef[i].enabled;
		//bool optional = datadef[i].optional;
		QString level = datadef[i].level;
		QString numboldreps = datadef[i].numboldreps;
		qint64 datadownloadid = datadef[i].datadownloadid;

		QSqlQuery q;

		/* seperate any protocols that have multiples */
		QString protocols;
		if (protocol.contains("\"")) {
			QStringList prots = n->ShellWords(protocol);
			protocols = "'" + prots.join("','") + "'";
		}
		else
			protocols = "'" + protocol + "'";

		/* separate image types */
		QString imagetypes;
		if (imagetype.contains(",")) {
			QStringList types = imagetype.split(QRegularExpression(",\\s*"));
			imagetypes = "'" + types.join("','") + "'";
		}
		else
			imagetypes = "'" + imagetype + "'";

		/* SQL comparison string */
		QString comparison;
		int num = 0;
		bool validComparisonStr = false;
		if (n->GetSQLComparison(numboldreps, comparison, num))
			validComparisonStr = true;

		dlog << QString("Copying data for step [%1]").arg(i);

		/* check to see if we should even run this step */
		if (!enabled) {
			dlog << n->WriteLog("This data item [" + protocol + "] is not enabled");
			RecordDataDownload(datadownloadid, analysisid, modality, 1, -1, -1, "", i, "Step not enabled. Not downloading.");
			continue;
		}

		int neareststudynum = -1;

		/* use the correct SQL schema column name for the series_desc... */
		QString seriesdescfield = "series_desc";
		if (modality != "mr")
			seriesdescfield = "series_protocol";

		/* make sure the requested modality table exists */
		q.prepare(QString("show tables like '%1_series'").arg(modality.toLower()));
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() < 1) {
			dlog << n->WriteLog("    ERROR: modality [" + modality + "] not found");
			RecordDataDownload(datadownloadid, analysisid, modality, 1, -1, -1, "", i, "Invalid modality. Not downloading.");
			continue;
		}

		QString sqlstring;
		/* get a list of series satisfying the search criteria, if it exists */
		if (level == "study") {
			dlog << "This data step is STUDY-level:  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetype + "]";

			sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
			if (imagetypes != "''")
				sqlstring += " and image_type in (" + imagetypes + ")";

			if (criteria == "first")
				sqlstring += " order by series_num asc limit 1";
			else if (criteria == "last")
				sqlstring += " order by series_num desc limit 1";
			else if (criteria == "largestsize")
				sqlstring += " order by series_size desc, numfiles desc, img_slices desc limit 1";
			else if (criteria == "smallestsize")
				sqlstring += " order by series_size asc, numfiles asc, img_slices asc limit 1";
			else if (criteria == "usesizecriteria")
				sqlstring += QString(" and numfiles %1 %2 order by series_num asc").arg(comparison).arg(num);
			else
				sqlstring += " order by series_num asc";

			q.prepare(sqlstring);
			q.bindValue(":studyid", studyid);
		}
		else {
			dlog << "This data step is SUBJECT-level:  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetype + "]";

			if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
				/* find the data from the same subject and modality that has the nearest (in time) matching scan */
				dlog << n->WriteLog("Searching for subject-level data nearest in time...");

				/* get the otherstudyid */
				QSqlQuery q2;
				QString sqlstringA;
				sqlstringA = QString("SELECT `studies`.study_id, `studies`.study_num FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstringA += QString("and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				sqlstringA += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '%2' ) ) LIMIT 1").arg(modality).arg(s.studydatetime.toString("yyyy-MM-dd hh:mm:ss"));
				q2.prepare(sqlstringA);
				q2.bindValue(":subjectid", s.subjectid);

				n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
				int otherstudyid(0);
				if (q2.size() > 0) {
					q2.first();
					otherstudyid = q2.value("study_id").toInt();
					neareststudynum = q2.value("study_num").toInt();
				}
				else {
					// this data item is probably optional, so go to the next item
					continue;
				}

				dlog << n->WriteLog("Still within the subject-level data search:  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetypes + "]");

				/* base SQL string */
				sqlstring = QString("select * from %1_series where study_id = :otherstudyid and trim(%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);
				if (imagetypes != "''")
					sqlstring += " and image_type in (" + imagetypes + ")";

				/* determine the ORDERing and LIMITs */
				if (criteria == "first")
					sqlstring += " order by series_num asc limit 1";
				else if (criteria == "last")
					sqlstring += " order by series_num desc limit 1";
				else if (criteria == "largestsize")
					sqlstring += " order by series_size desc, numfiles desc, img_slices desc limit 1";
				else if (criteria == "smallestsize")
					sqlstring += " order by series_size asc, numfiles asc, img_slices asc limit 1";
				else if (criteria == "usesizecriteria")
					sqlstring += QString(" and numfiles %1 %2 order by series_num asc").arg(comparison).arg(num);
				else
					sqlstring += " order by series_num asc";

				q.prepare(sqlstring);
				q.bindValue(":subjectid", s.subjectid);
				q.bindValue(":otherstudyid", otherstudyid);
			}
			else if (assoctype == "all") {
				dlog << n->WriteLog("Searching for all subject-level data...");
				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				if (validComparisonStr)
					sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);

				q.prepare(sqlstring);
				q.bindValue(":subjectid", s.subjectid);
			}
			else {
				/* find the data from the same subject and modality that has the same study_type */
				dlog << n->WriteLog("Searching for subject-level data with same study type...");

				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				if (validComparisonStr)
					sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);

				sqlstring += " and `studies`.study_type = :studytype";

				q.prepare(sqlstring);
				q.bindValue(":subjectid", s.subjectid);
				q.bindValue(":studytype", s.studytype);
			}
		}

		int newseriesnum = 1;
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() > 0) {

			dlog << n->WriteLog(QString("Found [%1] matching subject-level series").arg(q.size()));
			/* in theory, data for this analysis exists for this study, so lets now create the analysis directory */
			QString m;
			if (!n->MakePath(analysispath + "/pipeline", m)) {
				dlog << n->WriteLog("Error: unable to create directory [" + analysispath + "/pipeline] - C");
				UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + analysispath + "/pipeline]", -1, -1, "", "", false, true);
				continue;
			}
			else
				dlog << n->WriteLog("Created directory [" + analysispath + "/pipeline] - C");

			while (q.next()) {
				int localstudynum;
				n->WriteLog(QString("NumDownloaded [%1]").arg(numdownloaded));
				int seriesid = q.value(modality+"series_id").toInt();
				int seriesnum = q.value("series_num").toInt();
				QString seriesdesc = q.value("series_desc").toString();
				QString seriesdatetime = q.value("series_datetime").toString();
				QString datatype = q.value("data_type").toString();
				//qint64 seriessize = q.value("series_size").toLongLong();
				//int numfiles = q.value("numfiles").toInt();
				QString phaseplane = q.value("phaseencodedir").toString();
				//double phaseangle = q.value("phaseencodeangle").toDouble();
				int phasepositive;
				if (q.value("PhaseEncodingDirectionPositive").isNull())
					phasepositive = -1;
				else
					phasepositive = q.value("PhaseEncodingDirectionPositive").toInt();

				if (datatype == "")
					datatype = modality;

				if (level == "study") {
					/* studynum is not returned as part of this current result set, so reuse the studynum from outside this resultset loop */
					if (neareststudynum == -1)
						localstudynum = studynum;
					else
						localstudynum = neareststudynum;
				}
				else {
					if (neareststudynum == -1)
						localstudynum = q.value("study_num").toInt();
					else
						localstudynum = neareststudynum;
				}

				dlog << n->WriteLog(QString("Working on copying:  protocol [%1]  seriesnum [%2]  seriesdatetime [%3]").arg(seriesdesc).arg(seriesnum).arg(seriesdatetime));

				QString behoutdir;
				QString indir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum).arg(datatype);
				QString behindir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum);

				/* start building the analysis path */
				QString newanalysispath = analysispath + "/" + location;

				/* check if the series numbers are used, and if so, are they preserved */
				if (useseries) {
					if (preserveseries) {
						newanalysispath += QString("/%1").arg(seriesnum);
						behoutdir = GetBehPath(behformat, analysispath, location, behdir, seriesnum);
					}
					else {
						/* renumber the series */
						newanalysispath += QString("/%1").arg(newseriesnum);
						behoutdir = GetBehPath(behformat, analysispath, location, behdir, newseriesnum);
						newseriesnum++;
					}
				}
				else {
					behoutdir = GetBehPath(behformat, analysispath, location, behdir, seriesnum);
				}

				if (behformat != "behnone")
					dlog << n->WriteLog("behformat [" + behformat + "] behoutdir [" + behoutdir + "]");

				if (usephasedir) {
					dlog << QString("PhasePlane [" + phaseplane + "] PhasePositive [%1]").arg(phasepositive);

					QString phasedir = "unknownPE";
					if ((phaseplane == "COL") && (phasepositive == 1)) phasedir = "AP";
					if ((phaseplane == "COL") && (phasepositive == 0)) phasedir = "PA";
					if ((phaseplane == "COL") && (phasepositive == -1)) phasedir = "COL";
					if ((phaseplane == "ROW") && (phasepositive == 1)) phasedir = "RL";
					if ((phaseplane == "ROW") && (phasepositive == 0)) phasedir = "LR";
					if ((phaseplane == "ROW") && (phasepositive == -1)) phasedir = "ROW";

					newanalysispath += "/" + phasedir;
				}

				QString m;
				if (!n->MakePath(newanalysispath, m))
					dlog << n->WriteLog("Error creating directory [" + newanalysispath + "] message [" + m + "] - D");
				else
					dlog << n->WriteLog("Created directory [" + newanalysispath + "] - D");

				n->SystemCommand("chmod -Rf 777 " + newanalysispath, true, true);

				// output the correct file type
				if ((dataformat == "dicom") || ((datatype != "dicom") && (datatype != "parrec"))) {
					QString systemstring;
					if (p.dataCopyMethod == "scp")
						systemstring = QString("scp %1/* %2\\@%3:%4").arg(indir).arg(n->cfg["clusteruser"]).arg(p.submitHost).arg(newanalysispath);
					else
						systemstring = QString("cp -v %1/* %2").arg(indir).arg(newanalysispath);
					dlog << n->WriteLog(n->SystemCommand(systemstring, true, true));
				}
				else {
					QString tmpdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
					QString m;
					if (!n->MakePath(tmpdir, m))
						dlog << n->WriteLog("Error creating directory [" + tmpdir + "] message [" + m + "] - E");
					else
						dlog << n->WriteLog("Created temp directory [" + tmpdir + "] - E");
					int numfilesconv(0);
					int numfilesrenamed(0);
					n->ConvertDicom(dataformat, indir, tmpdir, gzip, uid, QString("%1").arg(localstudynum), QString("%1").arg(seriesnum), datatype, numfilesconv, numfilesrenamed, m);

					QString systemstring;
					if (p.dataCopyMethod == "scp")
						systemstring = QString("scp %1/* %2\\@%3:%4").arg(tmpdir).arg(n->cfg["clusteruser"]).arg(p.submitHost).arg(newanalysispath);
					else
						systemstring = QString("cp -v %1/* %2").arg(tmpdir).arg(newanalysispath);
					dlog << n->WriteLog(n->SystemCommand(systemstring, true, true));

					dlog << n->WriteLog("Removing temp directory ["+tmpdir+"]");
					if (!n->RemoveDir(tmpdir,m))
						n->WriteLog("Unable to remove directory [" + tmpdir + "] error [" + m + "]");
				}

				RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, seriesid, newanalysispath, i, "Data downloaded");
				dlog << QString("Data download step [%1]: data downloaded to [%2]").arg(i).arg(newanalysispath);
				numdownloaded++;

				/* copy the beh data */
				if (behformat != "behnone") {
					dlog << "Copying behavioral data";
					QString m;
					if (!n->MakePath(behoutdir, m))
						n->WriteLog("Error creating directory [" + behoutdir + "] message [" + m + "] - F");
					else
						n->WriteLog("Created directory [" + behoutdir + "] - F");
					QString systemstring = "cp -Rv " + behindir + "/* " + behoutdir;
					dlog << n->WriteLog(n->SystemCommand("chmod -Rf 777 " + behoutdir, true, true));

					n->SystemCommand("chmod -Rf 777 " + behoutdir, true, true);
					dlog << "Done copying behavioral data...";
				}

				/* give full read/write permissions to everyone */
				n->SystemCommand("chmod -Rf 777 " + newanalysispath, true, true);

				dlog << n->WriteLog("Done writing data to " + newanalysispath);
			}
		}
		else {
			dlog << "Found no matching subject-level [" + protocol + "] series. SQL: [" + sqlstring + "]";
		}
	}
	n->WriteLog("Leaving GetData() successfully");
	n->InsertAnalysisEvent(analysisid, pipelineid, p.version, studyid, "analysiscopydataend", QString("Finished copying data [%1] series downloaded").arg(numdownloaded));

	datalog = dlog.join("\n");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- UpdateAnalysisStatus --------------------------- */
/* ---------------------------------------------------------- */
bool modulePipeline::UpdateAnalysisStatus(int analysisid, QString status, QString statusmsg, int jobid, int numseries, QString datalog, QString datatable, bool currentStartDate, bool currentEndDate) {
	QSqlQuery q;
	QString sqlstring;
	QStringList varsToSet;

	status = status.trimmed();
	statusmsg = statusmsg.trimmed();
	datalog = datalog.trimmed();
	datatable = datatable.trimmed();

	/* concatenate the SQL statement */
	sqlstring = "update analysis set ";
	if (status != "")
		varsToSet << "analysis_status = :status";
	if (statusmsg != "")
		varsToSet << "analysis_statusmessage = :statusmsg";
	if (datalog != "")
		varsToSet << "analysis_datalog = :datalog";
	if (datatable != "")
		varsToSet << "analysis_datatable = :datatable";
	if (currentStartDate)
		varsToSet << "analysis_startdate = now()";
	if (currentEndDate)
		varsToSet << "analysis_enddate = now()";
	if (jobid >= 0)
		varsToSet << "analysis_qsubid = :jobid";
	if (numseries >= 0)
		varsToSet << "analysis_numfiles = :numseries";

	/* return false if there were no variables to update */
	if (varsToSet.size() < 1) {
		n->WriteLog("Unable to update analysis status");
		return false;
	}
	else {
		n->WriteLog("Updating analysis [" + varsToSet.join(" | ") + "]");
	}

	sqlstring += varsToSet.join(", ") + " where analysis_id = :analysisid";

	/* prepare the SQL statement and bind the values */
	q.prepare(sqlstring);
	if (status != "")
		q.bindValue(":status", status);
	if (statusmsg != "")
		q.bindValue(":statusmsg", statusmsg);
	if (datalog != "")
		q.bindValue(":datalog", datalog);
	if (datatable != "")
		q.bindValue(":datatable", datatable);
	if (jobid >= 0)
		q.bindValue(":jobid", jobid);
	if (numseries >= 0)
		q.bindValue(":numseries", numseries);

	q.bindValue(":analysisid", analysisid);

	n->WriteLog(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__,true));

	return true;
}


/* ---------------------------------------------------------- */
/* --------- GetBehPath ------------------------------------- */
/* ---------------------------------------------------------- */
QString modulePipeline::GetBehPath(QString behformat, QString analysispath, QString location, QString behdir, int newseriesnum) {
	QString behoutdir;
	if (behformat == "behroot")
		behoutdir = QString("%1/%2").arg(analysispath).arg(location);
	if (behformat == "behrootdir")
		behoutdir = QString("%1/%2/%3").arg(analysispath).arg(location).arg(behdir);
	if (behformat == "behseries")
		behoutdir = QString("%1/%2/%3").arg(analysispath).arg(location).arg(newseriesnum);
	if (behformat == "behseriesdir")
		behoutdir = QString("%1/%2/%3/%4").arg(analysispath).arg(location).arg(newseriesnum).arg(behdir);

	return behoutdir;
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
					q2.first();
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
QList<int> modulePipeline::GetPipelineList() {
	QList<int> a;

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
		status = "NoMatchingStudyDependency";

	/* check if the dependency is complete */
	q.prepare("select * from analysis where study_id = :sid and pipeline_id = :pipelinedep and analysis_status = 'complete'");
	q.bindValue(":sid", sid);
	q.bindValue(":pipelinedep", pipelinedep);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1)
		status = "IncompleteDependency";

	/* check if the dependency is marked as bad */
	q.prepare("select * from analysis where study_id = :sid and pipeline_id = :pipelinedep and analysis_isbad <> 1");
	q.bindValue(":sid", sid);
	q.bindValue(":pipelinedep", pipelinedep);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1)
		status = "BadDependency";

	return status;
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
			rec.numboldreps = q.value("pdd_numboldreps").toString().trimmed();
			rec.level = q.value("pdd_level").toString().trimmed();
			rec.datadownloadid = -1;
			datadef.append(rec);
		}
	}
	return datadef;
}


/* ---------------------------------------------------------- */
/* --------- FormatCommand ---------------------------------- */
/* ---------------------------------------------------------- */
QString modulePipeline::FormatCommand(int pipelineid, QString clusteranalysispath, QString command, QString analysispath, int analysisid, QString uid, int studynum, QString studydatetime, QString pipelinename, QString workingdir, QString description) {

	if (command.trimmed() == "")
		return "";

	command.replace("{NOLOG}",""); /* remove any {NOLOG} commands */
	command.replace("{NOCHECKIN}",""); /* remove any {NOCHECKIN} commands */
	command.replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),""); /* remove any non-printable ASCII control characters */
	command.replace("{analysisrootdir}", analysispath, Qt::CaseInsensitive);
	command.replace("{analysisid}", QString("%1").arg(analysisid), Qt::CaseInsensitive);
	command.replace("{subjectuid}", uid, Qt::CaseInsensitive);
	command.replace("{studynum}", QString("%1").arg(studynum), Qt::CaseInsensitive);
	command.replace("{uidstudynum}", QString("%1%2").arg(uid).arg(studynum), Qt::CaseInsensitive);
	command.replace("{studydatetime}", studydatetime, Qt::CaseInsensitive);
	command.replace("{pipelinename}", pipelinename, Qt::CaseInsensitive);
	command.replace("{workingdir}", workingdir, Qt::CaseInsensitive);
	command.replace("{description}", description, Qt::CaseInsensitive);

	/* expand {groups} */
	QStringList groups = GetGroupList(pipelineid);
	QString grouplist = groups.join(" ");
	command.replace("{groups}", grouplist, Qt::CaseInsensitive);

	QStringList alluidstudynums;
	foreach (QString group, groups) {
		QStringList uidStudyNums = GetUIDStudyNumListByGroup(group);
		alluidstudynums.append(uidStudyNums);
		QString uidlist = uidStudyNums.join(" ");
		command.replace("{uidstudynums_"+group+"}", uidlist, Qt::CaseInsensitive);
		command.replace("{numsubjects_"+group+"}", QString("%1").arg(uidStudyNums.size()), Qt::CaseInsensitive);
	}
	QString alluidlist = alluidstudynums.join(" ");
	command.replace("{uidstudynums}", alluidlist, Qt::CaseInsensitive);
	command.replace("{numsubjects}", QString("%1").arg(alluidstudynums.size()), Qt::CaseInsensitive);

	/* not really sure of the utility of these commands... doing this from bash may be more straightforward */
	QRegularExpression regex("\\s+(\\S*)\\{first_(.*)_file\\}", QRegularExpression::CaseInsensitiveOption);
	if (command.contains(regex)) {
		QRegularExpressionMatch match = regex.match(command);
		QString file = match.captured(0);
		QString ext = match.captured(1);
		QString searchpattern = QString("%2*.%3").arg(clusteranalysispath).arg(file).arg(ext);
		QStringList files = n->FindAllFiles(clusteranalysispath, searchpattern);
		QString replacement = files[0];
		replacement.replace(clusteranalysispath, analysispath, Qt::CaseInsensitive);
		command.replace(regex, replacement);
	}
	/* {first_n_ext_files} {last_ext_file} are not implemented in the compiled NiDB */
	command.replace("{command}", command, Qt::CaseInsensitive);

	/* if there is a semi-colon at the end of the line, remove it (it will prevent logging) */
	if (command.right(1) == ";")
		command.chop(1);

	return command;
}


/* ---------------------------------------------------------- */
/* --------- CreateClusterJobFile --------------------------- */
/* ---------------------------------------------------------- */
bool modulePipeline::CreateClusterJobFile(QString jobfilename, QString clustertype, int analysisid, QString uid, int studynum, QString analysispath, bool usetmpdir, QString tmpdir, QString studydatetime, QString pipelinename, int pipelineid, QString resultscript, int maxwalltime,  QList<pipelineStep> steps, bool runsupplement) {

	bool rerunresults(false);

	/* check if this analysis only needs part of it rerun, and not the whole thing */
	QSqlQuery q;
	q.prepare("select * from analysis where analysis_id = :analysisid");
	q.bindValue(":analysisid", analysisid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		rerunresults = q.value("analysis_rerunresults").toBool();
	}

	n->WriteLog(QString("ReRunResults: [%1]").arg(rerunresults));

	QString checkinscript = "analysischeckin.pl";
	QString jobfile;
	QString clusteranalysispath = analysispath;
	QString workinganalysispath = QString("%1/%2-%3").arg(tmpdir).arg(pipelinename).arg(analysisid);

	n->WriteLog("Cluster analysis path [" + analysispath + "]");
	n->WriteLog("Working Analysis path (temp directory) [" + workinganalysispath + "]");

	/* check if any of the variables might be blank */
	if (analysispath == "") {
		n->WriteLog("Within CreateClusterJobFile(), analysispath was blank");
		return false;
	}
	if (workinganalysispath == "") {
		n->WriteLog("Within CreateClusterJobFile(), workinganalysispath was blank");
		return false;
	}
	if (analysisid < 0) {
		n->WriteLog(QString("Within CreateClusterJobFile(), analysisid was [%1]").arg(analysisid));
		return false;
	}
	if (uid == "") {
		n->WriteLog("Within CreateClusterJobFile(), uid was blank");
		return false;
	}
	if (studynum < 0) {
		n->WriteLog(QString("Within CreateClusterJobFile(), studynum was [%1]").arg(studynum));
		return false;
	}
	if (studydatetime == "") {
		n->WriteLog("Within CreateClusterJobFile(), studydatetime was blank");
		return false;
	}

	/* different submission parameters for slurm */
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
	else { /* assuming SGE or derivative if not slurm */
		jobfile += "#!/bin/sh\n";
		if (runsupplement)
			jobfile += "#$ -N "+pipelinename+"-supplement\n";
		else
			jobfile += "#$ -N "+pipelinename+"\n";

		jobfile += "#$ -S /bin/bash\n";
		jobfile += "#$ -j y\n";
		jobfile += "#$ -o "+analysispath+"/pipeline/\n";
		jobfile += "#$ -V\n";
		jobfile += "#$ -u " + n->cfg["queueuser"] + "\n";
		if (maxwalltime > 0) {
			int hours = int(floor(maxwalltime/60));
			int min = maxwalltime % 60;

			if (min < 10)
				jobfile += QString("#$ -l h_rt=%1:0%2:00\n").arg(hours).arg(min);
			else
				jobfile += QString("#$ -l h_rt=%1:%2:00\n").arg(hours).arg(min);
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

	QDir::setCurrent(clusteranalysispath);
	if (!rerunresults) {
		/* go through list of data search criteria */
		for (int i=0; i<steps.size(); i++) {
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

			/* only run supplement commands if we are running a supplement, and vice-versa */
			if ((runsupplement && !issupplement) || (!runsupplement && issupplement)) {
				n->WriteLog(QString("runsupplement [%1]  issupplement [%2]").arg(runsupplement).arg(issupplement));
				continue;
			}

			/* check for flags */
			if ((command.contains("{NOLOG}")) || (description.contains("{NOLOG}")))
				logged = false;
			if ((command.contains("{NOCHECKIN}")) || (description.contains("{NOCHECKIN}")))
				checkedin = false;
			if ((command.contains("{PROFILE}")) || (description.contains("{PROFILE}")))
				profile = true;

			/* format the command (replace pipeline variables, etc) */
			if (usetmpdir)
				command = FormatCommand(pipelineid, clusteranalysispath, command, workinganalysispath, analysisid, uid, studynum, studydatetime, pipelinename, workingdir, description);
			else
				command = FormatCommand(pipelineid, clusteranalysispath, command, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, workingdir, description);

			/* add the step checkin */
			if (checkedin) {
				QString cleandesc = description;
				cleandesc.replace("'","").replace("\"","");
				jobfile += QString("\nperl /opt/pipeline/%1 %2 processing 'processing %3step %4 of %5' '%6'").arg(checkinscript).arg(analysisid).arg(supplement).arg(order).arg(steps.size()).arg(cleandesc);
				jobfile += "\n# " + description + "\necho Running " + command + "\n";
			}

			/* prepend with 'time' if the neither NOLOG nor NOCHECKIN are specified */
			if (profile && logged && checkedin)
				command = "/usr/bin/time -v " + command;

			/* write to a log file if logging is requested */
			if (logged)
				command += QString(" >> " + analysispath + "/pipeline/" + supplement + "step%1.log 2>&1").arg(order);

			if (workingdir != "")
				jobfile += "cd " + workingdir + ";\n";

			/* disable the step if necessary */
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
		/* add on the result script command */
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

		/* clean up and log everything */
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
	QFile f(jobfilename);
	if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
		QTextStream fs(&f);
		fs << jobfile;
		f.close();
		n->WriteLog("Within CreateClusterJobFile() - wrote the file ["+jobfilename+"]");
		return true;
	}
	else {
		n->WriteLog("Within CreateClusterJobFile() - Could not write the file ["+jobfilename+"]");
		return false;
	}
}


/* ---------------------------------------------------------- */
/* --------- GetStudyToDoList ------------------------------- */
/* ---------------------------------------------------------- */
QList<int> modulePipeline::GetStudyToDoList(int pipelineid, QString modality, int depend, QString groupids) {

	QSqlQuery q;

	int numInitial(0);
	int numRerun(0);
	int numSupplement(0);

	/* step 1 - get list of studies which do not have an entry in the analysis table for this pipeline */
	if (depend >= 0) {
		// there is a dependency
		// need to check if ANY of the subject's studies have the dependency...

		/* step 1) get list of SUBJECTs who have completed the dependency */
		QList<int> list;
		QSqlQuery q2;
		q2.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id in (select a.subject_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_id in (select study_id from analysis where pipeline_id = :depend and analysis_status = 'complete' and analysis_isbad <> 1) and a.isactive = 1)");
		q2.bindValue(":depend", depend);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
		if (q2.size() > 0) {
			while (q2.next())
				list.append(q2.value("study_id").toInt());
		}
		QString studyidlist = n->JoinIntArray(list, ",");
		//n->WriteLog(QString("Found studyid list [%1]").arg(studyidlist));

		if (studyidlist == "")
			studyidlist = "-1";

		/* step 2) then find all STUDIES that those subjects have completed */
		if (groupids == "") {
			/* NO groupids */
			q.prepare("select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and study_id in (" + studyidlist + ") and (study_datetime < date_sub(now(), interval 6 hour)) order by study_datetime desc");
			q.bindValue(":pipelineid", pipelineid);
			n->WriteLog(QString("GetStudyToDoList(): HAS dependency [%1]. NO groupids [%2]").arg(depend).arg(groupids));
		}
		else {
			/* with groupids */
			q.prepare("select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and a.study_id in (" + studyidlist + ") and (a.study_datetime < date_sub(now(), interval 6 hour)) and b.group_id in (" + groupids + ") order by a.study_datetime desc");
			q.bindValue(":pipelineid", pipelineid);
			n->WriteLog(QString("GetStudyToDoList(): HAS dependency [%1]. HAS groupids [%2]").arg(depend).arg(groupids));
		}
	}
	else {
		/* NO dependency */
		if (groupids == "") {
			/* NO groupids */
			q.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and (a.study_datetime < date_sub(now(), interval 6 hour)) and a.study_modality = :modality and c.isactive = 1 order by a.study_datetime desc");
			q.bindValue(":pipelineid", pipelineid);
			q.bindValue(":modality", modality);
			n->WriteLog(QString("GetStudyToDoList(): NO dependency [%1]. NO groupids [%2]").arg(depend).arg(groupids));
		}
		else {
			/* WITH groupids */
			q.prepare("SELECT a.study_id FROM studies a left join group_data b on a.study_id = b.data_id left join enrollment c on a.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id WHERE a.study_id NOT IN (SELECT study_id FROM analysis WHERE pipeline_id = :pipelineid) AND ( a.study_datetime < DATE_SUB( NOW( ) , INTERVAL 6 hour )) AND a.study_modality = :modality and b.group_id in (" + groupids + ") and d.isactive = 1 ORDER BY a.study_datetime DESC");
			q.bindValue(":pipelineid", pipelineid);
			q.bindValue(":modality", modality);
			n->WriteLog(QString("GetStudyToDoList(): NO dependency [%1]. HAS groupids [%2]").arg(depend).arg(groupids));
		}
	}

	QList<int> list;

	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int studyid = q.value("study_id").toInt();

			QSqlQuery q2;
			q2.prepare("select b.study_num, c.uid from enrollment a left join studies b on a.enrollment_id = b.enrollment_id left join subjects c on a.subject_id = c.subject_id where b.study_id = :studyid and c.isactive = 1");
			q2.bindValue(":studyid", studyid);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			if (q2.size() > 0) {
				q2.first();
				QString uidstudynum;
				uidstudynum = QString("%1%2").arg(q2.value("uid").toString()).arg(q2.value("study_num").toInt());
				//n->WriteLog(QString("Found study id [%1]  UIDStudyNum [%2]").arg(studyid).arg(uidstudynum));
			}
			list.append(studyid);
		}
	}
	numInitial = list.size();
	//n->WriteLog(QString("Found [%1] initial studies that met criteria.").arg(list.size()));

	/* step 2 - get only the studies that need to have their results rerun */
	int addedStudies = 0;
	q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_rerunresults = 1 and analysis_status = 'complete' and analysis_isbad <> 1) order by study_datetime desc");
	q.bindValue(":pipelineid", pipelineid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int studyid = q.value("study_id").toInt();
			n->WriteLog(QString("Found study (results rerun) [%1]").arg(studyid));
			list.append(studyid);
			addedStudies++;
		}
	}
	//n->WriteLog(QString("Found [%1] additional studies marked to be rerun.").arg(addedStudies));
	numRerun = addedStudies;

	/* step 3 - get only the studies that need to have their supplements run */
	addedStudies = 0;
	q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_runsupplement = 1 and analysis_status = 'complete' and analysis_isbad <> 1) order by study_datetime desc");
	q.bindValue(":pipelineid", pipelineid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int studyid = q.value("study_id").toInt();
			n->WriteLog(QString("Found study (results rerun) [%1]").arg(studyid));
			list.append(studyid);
			addedStudies++;
		}
	}
	//n->WriteLog(QString("Found [%1] additional studies marked for supplement run.").arg(addedStudies));
	numSupplement = addedStudies;

	n->WriteLog(QString("Found [%1] total studies that met criteria: [%2] initial match  [%3] rerun  [%4] supplement").arg(list.size()).arg(numInitial).arg(numRerun).arg(numSupplement));

	return list;
}


/* ---------------------------------------------------------- */
/* --------- RecordDataDownload ----------------------------- */
/* ---------------------------------------------------------- */
qint64 modulePipeline::RecordDataDownload(qint64 id, qint64 analysisid, QString modality, int checked, int found, int seriesid, QString downloadpath, int step, QString msg) {

	/* check if there are any variables to insert/update */
	if ((modality == "") && (checked == -1) && (found == -1) && (seriesid == -1) && (downloadpath == "") && (step == -1) && (msg == ""))
		return -1;

	QSqlQuery q;
	QString sql;
	if (id < 0) {
		/* do an insert */
		q.prepare("insert into pipeline_data (analysis_id, pd_modality, pd_checked, pd_found, pd_seriesid, pd_downloadpath, pd_step, pd_msg) values (:analysisid, :modality, :checked, :found, :seriesid, :downloadpath, :step, :msg)");
		q.bindValue(":analysisid",analysisid);

		if (modality == "") q.bindValue(":modality", QVariant(QVariant::String)); else q.bindValue(":modality", analysisid);
		if (checked == -1) q.bindValue(":checked", QVariant(QVariant::Bool)); else q.bindValue(":checked", checked);
		if (found == -1) q.bindValue(":found", QVariant(QVariant::Bool)); else q.bindValue(":found", found);
		if (seriesid == -1) q.bindValue(":seriesid", QVariant(QVariant::Int)); else q.bindValue(":seriesid", seriesid);
		if (downloadpath == "") q.bindValue(":downloadpath", QVariant(QVariant::String)); else q.bindValue(":downloadpath", downloadpath);
		if (step == -1) q.bindValue(":step", QVariant(QVariant::Int)); else q.bindValue(":step", step);
		if (msg == -1) q.bindValue(":msg", QVariant(QVariant::String)); else q.bindValue(":msg", msg);

		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		return q.lastInsertId().toInt();
	}
	else {
		/* do an update */
		QStringList sqlsets;
		if (modality != "") sqlsets.append("pd_modality = :modality");
		if (checked > -1) sqlsets.append("pd_checked = :checked");
		if (found > -1) sqlsets.append("pd_found = :found");
		if (seriesid > -1) sqlsets.append("pd_seriesid = :seriesid");
		if (downloadpath != "") sqlsets.append("pd_downloadpath = :downloadpath");
		if (step > -1) sqlsets.append("pd_step = :step");
		if (msg != "") sqlsets.append("pd_msg = :msg");

		sql = "update pipeline_data set " + sqlsets.join(", ") + " where pipelinedata_id = :pipelinedataid";

		q.prepare(sql);

		if (modality != "") q.bindValue(":modality", modality);
		if (checked > -1) q.bindValue(":checked", checked);
		if (found > -1) q.bindValue(":found", found);
		if (seriesid > -1) q.bindValue(":seriesid", seriesid);
		if (downloadpath != "") q.bindValue(":downloadpath", downloadpath);
		if (step > -1) q.bindValue(":step", step);
		if (msg != "") q.bindValue(":msg", msg);

		q.bindValue(":pipelinedataid",id);

		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		return id;
	}

}
