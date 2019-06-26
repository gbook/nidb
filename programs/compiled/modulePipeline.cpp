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

	int numchecked = 0;
	bool jobsWereSubmitted = false;
	QSqlQuery q;

	/* update the start time */
	SetPipelineProcessStatus("started",0,0);

	// check if this module should be running now or not
	if (!n->ModuleCheckIfActive()) {
		n->WriteLog("Not supposed to be running right now. Exiting module");
		SetPipelineProcessStatus("complete",0,0);
		return 0;
	}

	/* get list of pipelines that are not currently running, sorted by the longest since last run
	   we're only going to run 1 pipeline per instance of this module */
	q.prepare("select pipeline_id from pipelines where pipeline_status <> 'running' and (pipeline_enabled = 1 or pipeline_testing = 1) order by pipeline_laststart asc");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1) {
		n->WriteLog("No pipelines need to be run. Exiting module");
		SetPipelineProcessStatus("complete",0,0);
		return false;
	}

	// create a list of pipelines to be run
	//int i = 0;
	while (q.next()) {
		int pipelineid = q.value("pipeline_id").toInt();
		n->ModuleRunningCheckIn();

		//my %row = %{$pipelinerows[$i]};
		pipeline p(pipelineid, n);
		if (!p.isValid) {
			n->WriteLog("Pipeline was not valid: [" + p.msg + "]");
			continue;
		}

		QString analysisdir;
		if (p.dirStructure == "b")
			analysisdir = n->cfg["analysisdirb"];
		else
			analysisdir = n->cfg["analysisdir"];

		n->WriteLog("Working on pipeline [$pid] - [$pipelinename] Submits to queue [$pipelinequeue] through host [$pipelinesubmithost]");

		SetPipelineProcessStatus("running",pipelineid,0);

		// mark the pipeline as having been checked
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
		int analysisRowID(0);
		//int analysisGroupRowID(0);

		// check if the pipeline is running, if so, go on to the next one
		QString status;
		q2.prepare("select pipeline_status from pipelines where pipeline_id = :$pid");
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

		// update the pipeline start time
		SetPipelineStatusMessage(pipelineid, "Submitting jobs");
		SetPipelineRunning(pipelineid);

		n->WriteLog("Running pipeline $pid");

		QList<dataDefinitionStep> dataSteps;
		//my @datadef = ();
		if (p.level != 0) {
			if ((p.level == 1) || ((p.level == 2) && (p.parentDependencyIDs.size() < 1))) {
				dataSteps = GetPipelineDataDef(pipelineid, p.version);

				// if there is no data definition and no dependency
				if ((dataSteps.size() < 1) && (p.parentDependencyIDs.size() < 1)) {
					n->WriteLog("Pipeline [$pipelinename - $pid] has no data definition. Skipping.");

					/* update the statuses, and stop the modules */
					SetPipelineStatusMessage(pipelineid, "Pipeline has no data definition. Skipping");
					SetPipelineStopped(pipelineid);
					continue;
				}
				//if (defined($dd)) {
				//	@datadef = @$dd;
				//}
			}
		}

		// get the pipeline steps (the script)
		QList<pipelineStep> steps = GetPipelineSteps(pipelineid, p.version);
		if (steps.size() < 1) {
			n->WriteLog("Pipeline [$pipelinename - $pid] has no steps. Skipping.");

			// update the statuses and stop the modules
			SetPipelineStatusMessage(pipelineid, "Pipeline has no steps. Skipping");
			SetPipelineStopped(pipelineid);
			continue;
		}
		//my @pipelinesteps = @$ps;

		// determine which analysis level this is
		/* ------------------------------ level 0 ----------------------------- */
		if (p.level == 0) {
			/* check if this module should be running now or not */
			if (!n->ModuleCheckIfActive()) {
				n->WriteLog("Not supposed to be running right now. Exiting module");
				SetPipelineProcessStatus("complete",0,0);
				return 0;
			}

			// only 1 analysis should ever be run with the oneshot level, so if 1 already exists, regardless of state or pipeline version, then
			// leave this function without running the analysis
			q2.prepare("select * from analysis where pipeline_id = :pid");
			q2.bindValue(":pid", pipelineid);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			if (q2.size() > 0) {
				n->WriteLog("An analysis already exists for this one-shot level pipeline, exiting");
				SetPipelineStatusMessage(pipelineid, "An analysis already exists for this one-shot pipeline. Skipping");
				SetPipelineStopped(pipelineid);
				continue;
			}
			// create the analysis path
			QString analysispath = "/mount"+p.directory+"/"+p.name;
			n->WriteLog("Creating path [$analysispath/pipeline]");
			QString m;
			if (!n->MakePath(analysispath + "/pipeline", m)) {
				n->WriteLog("Error: unable to create directory ["+analysispath+"]");
				continue;
			}
			//my $systemstring = "mkdir -p $analysispath/pipeline";
			//WriteLog("[$systemstring]: " . `$systemstring 2>&1`);
			//mkpath("$analysispath/pipeline", { verbose => 1, mode => 0777});
			//chmod(0777,"$analysispath/pipeline");

			//if (-d "$analysispath/pipeline") {
			//    n->WriteLog("Directory [$analysispath/pipeline] exists!");
			//}

			// this file will record any events during setup
			QString setupLogFile = "/mount" + analysispath + "/pipeline/analysisSetup.log";
			n->AppendCustomLog(setupLogFile, "Beginning recording");
			n->WriteLog("Should have created this analysis setup log [" + setupLogFile + "]");

			// insert a temporary row, to be updated later, in the analysis table as a placeholder
			// so that no other processes end up running it
			q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values (:pid, :version,'','','processing',now())");
			q2.bindValue(":pid", pipelineid);
			q2.bindValue(":version", p.version);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			int analysisRowID = q2.lastInsertId().toInt();

			/* create the cluster job file */
			QString sgefilename = analysispath + "/sge.job";
			if (CreateClusterJobFile(sgefilename, p.clusterType, analysisRowID, false, "UID", 0, analysispath, p.useTmpDir, p.tmpDir, "", p.name, pipelineid, p.resultScript, p.maxWallTime, steps, false, p.useProfile, p.removeData)) {
				n->WriteLog("Created sge job submit file ["+sgefilename+"]");
			}
			else {
				n->WriteLog("Error: unable to create sge job submit file ["+sgefilename+"]");
				continue;
			}

			n->SystemCommand("chmod -Rf 777 " + analysispath);

			/* submit the cluster job file */
			QString qm, qresult;
			int jobid;
			QString statusmsg;
			if (n->SubmitClusterJob(sgefilename, p.submitHost, n->cfg["qsubpath"], n->cfg["queueuser"], p.queue, qm, jobid, qresult)) {
				n->WriteLog("Successfully submitted job to cluster ["+qresult+"]");
				statusmsg = "Submitted to " + p.queue;
				q2.prepare("update analysis set analysis_qsubid = :jobid, analysis_status = 'submitted', analysis_statusmessage = :statusmsg, analysis_enddate = now() where analysis_id = :analysisid");
				q2.bindValue(":statusmsg", statusmsg);
				q2.bindValue(":jobid", jobid);
				q2.bindValue(":analysisid", analysisRowID);
			}
			else {
				n->WriteLog("Error submitting job to cluster [" + qresult + "]");
				statusmsg = "Submit error [" + qm + "]";
				q2.prepare("update analysis set analysis_qsubid = 0, analysis_status = 'error', analysis_statusmessage = :statusmsg, analysis_enddate = now() where analysis_id = :analysisid");
				q2.bindValue(":statusmsg", statusmsg);
				q2.bindValue(":analysisid", analysisRowID);
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

			// if there are multiple dependencies, we'll need to loop through all of them separately
			for(int i=0; i<p.parentDependencyIDs.size(); i++) {

				int pipelinedep = p.parentDependencyIDs[i];

				QString modality;

				//modality = $datadef[0]{'modality'};

				// get the list of studies which meet the criteria for being processed through the pipeline
				QList<int> studyids = GetStudyToDoList(pipelineid, modality, QString(pipelinedep), n->JoinIntArray(p.groupIDs, ","));

				int numsubmitted(0);
				foreach (int sid, studyids) {

					QStringList setuplog;
					QString setupLogFile;

					SetPipelineProcessStatus("running", pipelineid, sid);

					numchecked++;

					n->WriteLog("--------------------- Working on study [$sid] for [$pipelinename] --------------------");

					/* check if the number of concurrent jobs is reached. the function also checks if this pipeline module is enabled */
					n->WriteLog("Checking if we've reached the max number of concurrent analyses");
					int filled;
					do {
						filled = IsQueueFilled(pipelineid);

						/* check if this pipeline is enabled */
						if (!IsPipelineEnabled(pipelineid)) {
							SetPipelineStatusMessage(pipelineid, "Pipeline disabled while running. Normal stop");
							SetPipelineStopped(pipelineid);
							continue;
						}

						// otherwise check
						if (filled == 0)
							break;

						if (filled == 1) {
							// update the pipeline status message
							SetPipelineStatusMessage(pipelineid, "Process quota reached. Waiting 1 minute to resubmit");
							n->WriteLog("Concurrent analysis quota reached, waiting 1 minute");
							//print "Queue full, waiting 1 minute...";
							n->ModuleRunningCheckIn();
							QThread::sleep(60); // sleep for 1 minute
						}
						if (filled == 2) {
							return 1;
						}
					} while (filled == 1);

					// get the analysis info, if an analysis already exists for this study
					//my ($analysisRowID, $rerunresults, $runsupplement, $msg) = GetAnalysisInfo(pipelineid,sid);
					analysis a(pipelineid, sid, p.version, n);
					if (!p.isValid) {
						n->WriteLog("Pipeline was not valid: [" + p.msg + "]");
						continue;
					}

					setuplog << p.msg;

					// ********************
					// only continue through this section (and submit the analysis) if
					// a) there is no analysis
					// b) OR there is an existing analysis and it needs the results rerun
					// c) OR there is an existing analysis and it needs a supplement run
					// ********************
					if ((a.runSupplement) || (a.rerunResults) || (analysisRowID == 0)) {
						// if the analysis doesn't yet exist, insert a temporary row, to be updated later, in the analysis table as a placeholder so that no other pipeline processes try to run it
						if (analysisRowID == 0) {
							q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values (:pipelineid, :version, :pipelinedep, :studyid,'processing',now())");
							q2.bindValue(":pipelineid",pipelineid);
							q2.bindValue(":version",p.version);
							q2.bindValue(":pipelinedep",pipelinedep);
							q2.bindValue(":studyid",sid);
							n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
							analysisRowID = q2.lastInsertId().toInt();

							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysiscreated", "Analysis created");
						}

						/* get information about the study */
						study s(sid, n);
						if (!s.isValid) {
							n->WriteLog("Study was not valid: [" + s.msg + "]");
							continue;
						}

						int numseries(0);
						QString datalog;
						QString datareport;
						QString datatable;

						int dependencyanalysisid(0);
						bool submiterror = false;
						QString errormsg;

						n->WriteLog("StudyDateTime: [$studydatetime], Working on: [$uid$studynum]");
						//print "StudyDateTime: $studydatetime\n";

						QString analysispath = "";
						if (p.dirStructure == "b")
							analysispath = QString("%1/%2/%3/%4").arg(p.directory).arg(p.name).arg(s.uid).arg(s.studynum);
						else
							analysispath = QString("%1/%2/%3/%4").arg(p.directory).arg(s.uid).arg(s.studynum).arg(p.name);

						/* this file will record any events during setup */
						QString setupLogFile = analysispath + "/pipeline/analysisSetup.log";
						n->WriteLog("Should have created this analysis setup log [" + setupLogFile + "]");

						// get the nearest study for this subject that has the dependency
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
						// create the dependency path
						QString deppath;
						if (pipelinedep > 0) {
							if (p.depLevel == "subject") {
								setuplog << n->WriteLog("Dependency is a subject level (will match dep for same subject, any study)");
								//$deppath = "$pipelinedirectory/$uid/$studynum_nearest";
							}
							else {
								setuplog << n->WriteLog("Dependency is a study level (will match dep for same subject, same study)");
								//$deppath = "$pipelinedirectory/$uid/$studynum";

								// check the dependency and see if there's anything amiss about it
								QString depstatus = CheckDependency(sid, pipelinedep);
								if (depstatus != "") {
									QString datalog2 = setuplog.join("\n");
									QString datatable2 = datatable;
									q2.prepare("update analysis set analysis_datalog = :datalog2, analysis_datatable = :datatable2, analysis_status = :depstatus, analysis_startdate = null where analysis_id = :analysisid");
									q2.bindValue(":datalog2", datalog2);
									q2.bindValue(":datatable2", datatable2);
									q2.bindValue(":depstatus", depstatus);
									q2.bindValue(":analysisid", analysisRowID);
									n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
									continue;
								}
							}
						}
						setuplog << n->WriteLog("Dependency path is [$deppath] and analysis path is [$analysispath]");

						// get the data if we are not running a supplement, and not rerunning the results
						if ((!a.runSupplement) && (!a.rerunResults)) {
							//($numseries, $datalog, $datareport, $datatable) = GetData(sid, analysispath, s.uid, analysisRowID, p.version, pipelineid, pipelinedep, p.depLevel, @datadef);
						}
						QString datatable2 = datatable;
						q2.prepare("update analysis set analysis_datatable = :datatable2 where analysis_id = :analysisid");
						q2.bindValue(":datatable2", datatable2);
						q2.bindValue(":analysisid", analysisRowID);
						n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

						// again check if there are any series to actually run the pipeline on...
						// ... but its ok to run if any of the following are true
						//     a) rerunresults is true
						//     b) runsupplement is true
						//     c) this pipeline is dependent on another pipeline
						bool okToRun(false);
						if (numseries > 0)
							okToRun = 1; // there is data to download from this study
						if (a.rerunResults)
							okToRun = true;
						if (a.runSupplement)
							okToRun = true;
						if ((pipelinedep > 0) && (p.depLevel == "study"))
							okToRun = 1; // there is a parent pipeline and we're using the same study from the parent pipeline. may or may not have data to download

						// one of the above criteria has been satisfied, so its ok to run
						if (okToRun) {
							setuplog << n->WriteLog(" ----- Study [$sid] has [$numseries] matching series downloaded (or needs results rerun, or is a supplement, or is dependent on another pipeline). Beginning analysis ----- ");

							QString dependencyname;
							if ((!a.rerunResults) && (!a.runSupplement)) {
								if (pipelinedep != 0) {
									setuplog << n->WriteLog("There is a pipeline dependency [$pipelinedep]");
									q2.prepare("select pipeline_name from pipelines where pipeline_id = :pipelinedep");
									q2.bindValue(":pipelinedep", pipelinedep);
									n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
									if (q2.size() > 0) {
										q2.first();
										dependencyname = q2.value("pipeline_name").toString();
										setuplog << n->WriteLog(QString("Found [%1] rows for pipeline dependency [%2]").arg(q2.size()).arg(dependencyname));
									}
									else {
										setuplog << n->WriteLog("Pipeline dependency ($pipelinedep) does not exist!");
										SetPipelineStatusMessage(pipelineid, "Pipeline dependency ($pipelinedep) does not exist!");
										SetPipelineStopped(pipelineid);
										continue;
									}
									n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This pipeline is dependent on pipeline [$dependencyname]");
								}
								else {
									setuplog << n->WriteLog("No pipeline dependencies [$pipelinedep]");
								}

								QString analysispath = "/mount" + p.directory + "/" + p.name;
								QString m;
								if (!n->MakePath(analysispath + "/pipeline", m)) {
									n->WriteLog("Error: unable to create directory ["+analysispath+"/pipeline]");
									continue;
								}

								n->SystemCommand("chmod -R 777 " + analysispath + "/pipeline");
								if (pipelinedep != 0) {
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

									QString fulldeppath = deppath + "/" + dependencyname;
									QDir d(fulldeppath);
									if (d.exists())
										setuplog << n->WriteLog("Full dependency path ["+fulldeppath+"] exists");
									else
										setuplog << n->WriteLog("Full dependency path ["+fulldeppath+"] does NOT exist");

									setuplog << n->WriteLog("This is a level [$pipelinelevel] pipeline. deplinktype [$deplinktype] depdir [$depdir]");

									n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "Parent pipeline (dependency) will be copied to directory [$depdir] using method [$deplinktype]");

									/* copy any dependencies */
									QString systemstring;
									if (p.depDir == "subdir") {
										setuplog << n->WriteLog("Dependency will be copied to a subdir");
										if (p.depLinkType == "hardlink")
											systemstring = "cp -aul $deppath $analysispath/";
										else if (p.depLinkType == "softlink")
											systemstring = "cp -aus $deppath $analysispath/";
										else if (p.depLinkType == "regularcopy")
											systemstring = "cp -au $deppath $analysispath/";
									}
									else { // root dir
										setuplog << n->WriteLog("Dependency will be copied to the root dir");
										if (p.depLinkType == "hardlink")
											systemstring = "cp -aul $deppath/* $analysispath/";
										else if (p.depLinkType == "softlink")
											systemstring = "cp -aus $deppath/* $analysispath/";
										else if (p.depLinkType == "regularcopy")
											systemstring = "cp -au $deppath/* $analysispath/";
									}
									setuplog << n->WriteLog(n->SystemCommand(systemstring));

									n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "Copied dependency by running ["+systemstring+"]");
									n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysisdependencyid", QString(dependencyanalysisid));

									/* delete any log files and SGE files that came with the dependency */
									setuplog << n->WriteLog(n->SystemCommand(QString("rm --preserve-root %1/pipeline/* %1/origfiles.log %1/sge.job").arg(analysispath)));

									/* make sure the whole tree is writeable */
									setuplog << n->WriteLog(n->SystemCommand("chmod -R 777 " + analysispath));
								}
								else {
									setuplog << n->WriteLog("Pipelinedep was 0 [$pipelinedep]");
									n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "No dependencies specified for this pipeline");
								}

								/* now safe to write out the setuplog */
								n->AppendCustomLog(setupLogFile, setuplog.join("\n"));

								// die here if we can't write the log... because if its not writeable, then something is wrong and we could submit a few hundred jobs that will fail
								//open DATALOG, "> $analysispath/pipeline/data.log" or die("[Line: " . __LINE__ . "] Could not create $analysispath/pipeline/data.log");
								//print DATALOG $datalog;
								//close DATALOG;
								//n->SystemCommand("chmod -R 777 " + analysispath + "/pipeline/data.log");
							}
							/* "realanalysispath" is now --> "localanalysispath" */
							QString localanalysispath = analysispath;
							localanalysispath.replace("/mount","");

							// create the SGE job file
							//my $sgebatchfile = CreateClusterJobFile($clustertype, $analysisRowID, 0, $uid, $studynum, localanalysispath, $usetmpdir, $tmpdir, $pipelineuseprofile, $studydatetime, $pipelinename, pipelineid, $pipelineremovedata, $pipelineresultscript, $pipelinemaxwalltime, $runsupplement, steps);

							//`chmod -Rf 777 $analysispath`;
							// create the SGE job file
							QString sgefilename;
							QString sgeshortfilename;
							if (a.rerunResults) {
								sgefilename = "$analysispath/sgererunresults.job";
								sgeshortfilename = "sgererunresults.job";
							}
							else if (a.runSupplement) {
								sgefilename = "$analysispath/sge-supplement.job";
								sgeshortfilename = "sge-supplement.job";
							}
							else {
								sgefilename = "$analysispath/sge.job";
								sgeshortfilename = "sge.job";
							}


							if (CreateClusterJobFile(sgefilename, p.clusterType, analysisRowID, false, a.uid, a.studynum, localanalysispath, p.useTmpDir, p.tmpDir, a.studyDateTime, p.name, pipelineid, p.resultScript, p.maxWallTime, steps, false, p.useProfile, p.removeData)) {
								n->WriteLog("Created sge job submit file ["+sgefilename+"]");
							}
							else {
								n->WriteLog("Error: unable to create sge job submit file ["+sgefilename+"]");
								continue;
							}

							n->SystemCommand("chmod -Rf 777 " + analysispath);

							/* submit the cluster job file */
							QString qm, qresult;
							int jobid;
							QString statusmsg;
							if (n->SubmitClusterJob(sgefilename, p.submitHost, n->cfg["qsubpath"], n->cfg["queueuser"], p.queue, qm, jobid, qresult)) {
								n->WriteLog("Successfully submitted job to cluster ["+qresult+"]");
								statusmsg = "Submitted to " + p.queue;
								q2.prepare("update analysis set analysis_qsubid = :jobid, analysis_status = 'submitted', analysis_statusmessage = :statusmsg, analysis_enddate = now() where analysis_id = :analysisid");
								q2.bindValue(":statusmsg", statusmsg);
								q2.bindValue(":jobid", jobid);
								q2.bindValue(":analysisid", analysisRowID);
							}
							else {
								n->WriteLog("Error submitting job to cluster [" + qresult + "]");
								statusmsg = "Submit error [" + qm + "]";
								q2.prepare("update analysis set analysis_qsubid = 0, analysis_status = 'error', analysis_statusmessage = :statusmsg, analysis_enddate = now() where analysis_id = :analysisid");
								q2.bindValue(":statusmsg", statusmsg);
								q2.bindValue(":analysisid", analysisRowID);
							}
							n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmitted", qresult);

							numsubmitted++;
							jobsWereSubmitted = true;

							SetPipelineStatusMessage(pipelineid, "Submitted $uid$studynum");

							// check if this module should be running now or not
							if (!n->ModuleCheckIfActive()) {
								n->AppendCustomLog(setupLogFile, n->WriteLog("Not supposed to be running right now. Exiting module"));
								// update the stop time
								n->ModuleDBCheckOut();
								return 0;
							}
						}
						else {
							n->AppendCustomLog(setupLogFile, n->WriteLog("GetData() returned 0 series"));
							// update the analysis table with the datalog to people can check later on why something didn't process
							QString datalog2 = datalog;
							QString datatable2 = datatable;
							q2.prepare("update analysis set analysis_datalog = :datalog2, analysis_datatable = :datatable2 where analysis_id = :analysisid");
							q2.bindValue(":datalog2", datalog2);
							q2.bindValue(":datatable2", datatable2);
							q2.bindValue(":analysisid", analysisRowID);
							n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissetuperror", "No data found, 0 series returned from search");
						}
						n->AppendCustomLog(setupLogFile, n->WriteLog("Submitted $numsubmitted jobs so far"));
						//print "Submitted $numsubmitted jobs so far\n";

						// mark the study in the analysis table
						if ((numseries > 0) || ((pipelinedep != 0) && (p.depLevel == "study")) || (a.runSupplement) || (a.rerunResults)) {
							QString sqlstring;
							if (a.rerunResults || a.runSupplement) {
								q2.prepare("update analysis set analysis_status = 'pending' where analysis_id = :analysisid");
								q2.bindValue(":analysisid", analysisRowID);
							}
							else {
								q2.prepare("update analysis set analysis_status = 'pending', analysis_numseries = :numseries, analysis_enddate = now() where analysis_id = :analysisid");
								q2.bindValue(":numseries", numseries);
								q2.bindValue(":analysisid", analysisRowID);
							}
							n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
							if (submiterror)
								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmiterror", "Analysis submitted to cluster, but was rejected with errors");
							else
								n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysispending", "Analysis has been submitted to the cluster [" + p.queue + "] and is waiting to run");
						}
						else {
							// save some database space, since most entries will be blank
							q2.prepare("update analysis set analysis_status = 'NoMatchingSeries', analysis_startdate = null where analysis_id = :analysisid");
							q2.bindValue(":analysisid", analysisRowID);
							n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

							n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This study did not have any matching data");
						}
					}
					else {
						n->WriteLog("This analysis already has an entry in the analysis table");
						n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This analysis already has an entry in the analysis table");
					}
					if (!IsPipelineEnabled(pipelineid)) {
						SetPipelineStatusMessage(pipelineid, "Pipeline disabled while running. Normal stop.");
						SetPipelineStopped(pipelineid);
						continue;
					}

					if ((numchecked%1000) == 0) {
						n->WriteLog("$numchecked studies checked");
					}
				}
			}
		}
		// ======================= LEVEL 2 =======================
		else if (p.level == 2) {
			// --- process second level pipeline ---
			n->WriteLog("Level 2");

//			// only 1 analysis should ever be run with the second level, so if 1 already exists, regardless of state or pipeline version, then
//			// leave this function without running the analysis
//			my $sqlstring = "select * from analysis where pipeline_id = $pid";
//			my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
//			if ($result->numrows > 0) {
//				WriteLog("An analysis already exists for this second level pipeline, exiting pipeline");
//				SetPipelineStatusMessage(pipelineid, 'An analysis already exists for this second level pipeline. Delete the analysis if you want to re-run it');
//				SetPipelineStopped(pipelineid);
//				continue;
//			}

//            #my $analysispath;
//			my $analysisRowID;
//			my $groupanalysispath;
//			my $realpipelinedirectory;

//			if ($pipelinedependency == "") {
//				$pipelinedependency = "0";
//			}
//			// if there are multiple dependencies, we'll need to loop through all of them separately
//			my @deps = split(',', $pipelinedependency);
//			foreach my $pipelinedep(@deps) {

//				// get the dependency name
//				my $dependencyname;
//				my $dependencylevel;
//				if ($pipelinedep != 0) {
//					my $sqlstring = "select pipeline_name, pipeline_level from pipelines where pipeline_id = $pipelinedep";
//					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
//					if ($result->numrows > 0) {
//						WriteLog("Found " . $result->numrows . " pipelines for second level pipeline");
//						my %row = $result->fetchhash;
//						$dependencyname = $row{'pipeline_name'};
//						$dependencylevel = $row{'pipeline_level'};
//					}
//					else {
//						WriteLog("Pipeline dependency ($pipelinedep) does not exist!");
//						SetPipelineStopped(pipelineid);
//						continue;
//					}
//				}

//				// build the path
//				if ($pipelinedirectory == "") {
//					$pipelinedirectory = $cfg{'groupanalysisdir'};
//					$realpipelinedirectory = $pipelinedirectory;
//					$realpipelinedirectory =~ s/\/mount//;
//					WriteLog("1 [$pipelinedirectory] --> [$realpipelinedirectory]");
//				}
//				else {
//					$pipelinedirectory = $cfg{'mountdir'} . $pipelinedirectory;
//					$realpipelinedirectory = $pipelinedirectory;
//					WriteLog("2 [$pipelinedirectory] --> [$realpipelinedirectory]");
//				}

//				MakePath("$pipelinedirectory/$pipelinename/$dependencyname");
//				$groupanalysispath = "$pipelinedirectory/$pipelinename";
//				MakePath($groupanalysispath);
//				chmod(0777,$groupanalysispath);

//				MakePath("$groupanalysispath/pipeline");
//				chmod(0777,"$groupanalysispath/pipeline");

//				// check if the groupanalysis has an entry in the analysis_group table
//				my $sqlstring = "select * from analysis where pipeline_id = $pid and pipeline_version = p.version";
//				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
//				if ($result->numrows < 1) {
//					// insert a temporary row, to be updated later, in the analysis_group table as a placeholder
//					// so that no other processes end up running it
//					$sqlstring = "insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, analysis_status, analysis_startdate) values ($pid,$pipelineversion,$pipelinedep,'processing',now())";
//					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
//					$analysisRowID = $result->insertid;
//				}
//				else {
//					my %row = $result->fetchhash;
//					$analysisRowID = $row{'analysis_id'};
//				}

//				// loop through the groups
//				if ($pipelinegroupids == "") {
//					continue;
//				}
//				$sqlstring = "select * from groups where group_id in ($pipelinegroupids)";
//				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
//				if ($result->numrows > 0) {
//					while (my %row = $result->fetchhash) {
//						my $groupname = $row{'group_name'};
//						my $groupid = $row{'group_id'};
//						// get a list of studies in the group that have the dependency
//						my $sqlstringA = "select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id in (select study_id from analysis where pipeline_id in ($pipelinedependency) and analysis_status = 'complete' and analysis_isbad <> 1) and (a.study_datetime < date_sub(now(), interval $pipelinesubmitdelay hour)) and b.group_id in ($groupid) order by a.study_datetime desc";
//						my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
//						WriteLog($sqlstringA);
//						my @studyids = ();
//						if ($resultA->numrows > 0) {
//							while (my %rowA = $resultA->fetchhash) {
//								push(@studyids,$rowA{'study_id'});
//								//WriteLog("Found study " . $rowA{'study_id'});
//							}
//						}
//						else {
//							WriteLog("No studies found [$sqlstringA]");
//							SetPipelineStatusMessage(pipelineid, "No studies found (Maybe 1st/2nd level group mismatch?)");
//							SetPipelineStopped(pipelineid);
//							continue;
//						}

//						foreach my sid(@studyids) {
//							// get information about the study
//							my $sqlstringB = "select *, date_format(study_datetime,'%Y%m%d_%H%i%s') 'studydatetime' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $sid";
//							//WriteLog($sqlstringB);
//							my $resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
//							my %rowB = $resultB->fetchhash;
//							my $uid = $rowB{'uid'};
//							my $studynum = $rowB{'study_num'};
//							my $studydatetime = $rowB{'studydatetime'};
//							my $numseries = 0;
//							my $datalog;
//							if (defined($uid)) {
//								//WriteLog("StudyDateTime: $studydatetime");
//								//print "StudyDateTime: $studydatetime\n";

//								if ($pipelinedirectory == "") {
//									$pipelinedirectory = $analysisdir;
//								}

//								WriteLog("StudyDateTime [$studydatetime]. This is a level [$pipelinelevel] pipeline. dependencylevel [$dependencylevel] deplinktype [$deplinktype] groupbysubject [$pipelinegroupbysubject]");
//								my $studydir;
//								if ($pipelinegroupbysubject) {
//									$studydir = "$uid/$studydatetime";
//									MakePath("$pipelinedirectory/$pipelinename/$groupname/$dependencyname/$uid");
//								}
//								else {
//									$studydir = "$uid$studynum";
//									MakePath("$pipelinedirectory/$pipelinename/$groupname/$dependencyname");
//								}

//								// create hard link in the analysis directory
//								my $systemstring;
//								if ($dependencylevel == 1) {
//									switch ($deplinktype) {
//									    case "hardlink" { $systemstring = "cp -auL $analysisdir/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
//									    case "softlink" { $systemstring = "cp -aus $analysisdir/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
//									    case "regularcopy" { $systemstring = "cp -au $analysisdir/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
//									}
//									}
//									else {
//									switch ($deplinktype) {
//									    case "hardlink" { $systemstring = "cp -aul $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
//									    case "softlink" { $systemstring = "cp -aus $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
//									    case "regularcopy" { $systemstring = "cp -au $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
//									}
//									}
//									//WriteLog("Running copy command [$systemstring]");

//									my $cpresults = `$systemstring 2>&1`;
//									if (($cpresults =~ /cannot stat/) || ($cpresults =~ /No such file or/) || ($cpresults =~ /error/)) {
//									WriteLog($cpresults);
//									}
//									WriteLog("pwd: [" . getcwd . "], [$systemstring] :" . $cpresults);
//									}
//									}
//									}
//									}
//									}

//			$sqlstring = "select date_format(now(),'%Y%m%d_%H%i%s') 'studydatetime'";
//			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
//			%row = $result->fetchhash;
//			my $studydatetime = $row{'studydatetime'};

//			// create the SGE job file
//			my $sgebatchfile = CreateClusterJobFile($clustertype, $analysisRowID, 1, "GROUPLEVEL", 0, $groupanalysispath, $usetmpdir, $tmpdir, $pipelineuseprofile, $studydatetime, $pipelinename, pipelineid, $pipelineremovedata, $pipelineresultscript, $pipelinemaxwalltime, 0, steps);

//			`chmod -Rf 777 $groupanalysispath`;
//			WriteLog($sgebatchfile);

//			// submit the SGE job
//			open SGEFILE, "> $pipelinedirectory/$pipelinename/pipeline/sge.job";
//			print SGEFILE $sgebatchfile;
//			close SGEFILE;
//			chmod(0777,$sgebatchfile);
//			chmod(0777,"$pipelinedirectory/$pipelinename");

//			// submit the sucker to the cluster
//			my $systemstring = "ssh $pipelinesubmithost qsub -u onrc -q $pipelinequeue \"$realpipelinedirectory/$pipelinename/pipeline/sge.job\"";
//			//print "SGE submit string [$systemstring]\n";
//			WriteLog("SGE submit string [$systemstring]");
//			my $sgeresult = trim(`$systemstring 2>&1`);
//			//print "SGE submit result [$sgeresult]\n";
//			WriteLog("SGE submit result [$sgeresult]");
//			my @parts = split(' ', $sgeresult);
//			my $jobid = $parts[2];
//			WriteLog(join('|',@parts));
//			WriteLog("[$systemstring]: " . $sgeresult);

//			if ($sgeresult =~ /error/) {
//									$sqlstring = "update analysis set analysis_qsubid = '$jobid', analysis_status = 'error', analysis_statusmessage = 'Error submitting to $pipelinequeue', analysis_enddate = now() where analysis_id = $analysisRowID";
//									}
//			else {
//									$sqlstring = "update analysis set analysis_qsubid = '$jobid', analysis_status = 'submitted', analysis_statusmessage = 'Submitted to $pipelinequeue', analysis_enddate = now() where analysis_id = $analysisRowID";
//									}
//			jobsWereSubmitted = true;
//			$result = SQLQuery($sqlstring, __FILE__, __LINE__);

//			// check if this module should be running now or not
//			if (!ModuleCheckIfActive($scriptname, $db)) {
//									WriteLog("Not supposed to be running right now");
//									// update the stop time
//									ModuleDBCheckOut($scriptname, $db);
//									return 0;
//									}

//			// a second level pipeline should run once, so disable it after submitting
//			SetPipelineDisabled(pipelineid);
		}
		else {
			n->WriteLog("Pipeline level invalid");
		}

		n->WriteLog("Done with pipeline [$pid] - [$pipelinename]");
		n->WriteLog("Done");
		SetPipelineStatusMessage(pipelineid, "Finished submitting jobs");
		SetPipelineStopped(pipelineid);

		//if (jobsWereSubmitted) {
		//	SetPipelineProcessStatus("complete",0,0);
		//	return 1;
		//}
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
bool modulePipeline::GetData(int studyid, QString analysispath, QString uid, int analysisid, int pipelineversion, int pipelineid, int pipelinedep, QString deplevel, QList<dataDefinitionStep> datadef, int &numdownloaded, QString &datalog, QString &datareport, QString &datatable) {
	n->WriteLog("Inside GetData()");

	bool ret(false);
	numdownloaded = 0;
	datalog = "";
	QStringList dlog;
	datareport = "";
	datatable = "Empty data table";

	/* get pipeline information, for data copying preferences */
	pipeline p(pipelineid, n);
	if (!p.isValid) {
		n->WriteLog("Pipeline was not valid: [" + p.msg + "]");
		return false;
	}

	/* get information about the study */
	study s(studyid, n);
	if (!s.isValid) {
		n->WriteLog("Study was not valid: [" + s.msg + "]");
		return false;
	}

	QString submithost;
	if (p.submitHost == "")
		submithost = n->cfg["clustersubmithost"];
	else
		submithost = p.submitHost;

	QString clusteruser;
	if (p.clusterUser == "")
		clusteruser = n->cfg["clusteruser"];
	else
		clusteruser = n->cfg["pipeline_clusteruser"];

	//InsertAnalysisEvent($analysisid, $pid, $pipelineversion, $studyid, 'analysiscopydata', 'Checking for data');

	// get list of series for this study
	QString modality = s.modality;
	int studynum = s.studynum;
	dlog << QString("===== Working on [%1%2] studyid [%3] =====").arg(uid).arg(studynum).arg(studyid);

	dlog << n->WriteLog("Study modality is ["+modality+"]");
	dlog << "-----------------------------------------------";
	dlog << "---------- Checking data steps ----------------";
	dlog << "-----------------------------------------------";

	//my $datarows;
	//$datarows->[0]->[0] = 'step';
	//$datarows->[0]->[1] = 'enabled';
	//$datarows->[0]->[2] = 'optional';
	//$datarows->[0]->[3] = 'protocol';
	//$datarows->[0]->[4] = 'modality';
	//$datarows->[0]->[5] = 'imagetype';
	//$datarows->[0]->[6] = 'type';
	//$datarows->[0]->[7] = 'level';
	//$datarows->[0]->[8] = 'assoctype';
	//$datarows->[0]->[9] = 'numboldreps';
	//$datarows->[0]->[10] = 'FOUND';
	//$datarows->[0]->[11] = 'dir';
	//$datarows->[0]->[12] = 'explanation';

	// ------------------------------------------------------------------------
	// check all of the steps to see if this data spec is valid
	// ------------------------------------------------------------------------
	bool stepIsInvalid(false);
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

		/* SQL query object for use throughout this loop */
		QSqlQuery q;

		/* use the correct SQL schema column name for the series_desc... */
		QString seriesdescfield = "series_desc";
		if (modality != "mr")
			seriesdescfield = "series_protocol";

		dlog += QString("Step [%1], CHECKING:  protocol [%2]  modality [%3]  imagetype [%4]  enabled [%5]  type [%6]  level [%7]  assoctype [%8]  optional [%9]  numboldreps [%10]").arg(i).arg(protocol).arg(modality).arg(imagetype).arg(enabled).arg(type).arg(level).arg(assoctype).arg(optional).arg(numboldreps);

		//$datarows->[$i+1]->[0] = $i;
		//$datarows->[$i+1]->[1] = $enabled;
		//$datarows->[$i+1]->[2] = $optional;
		//$datarows->[$i+1]->[3] = $protocol;
		//$datarows->[$i+1]->[4] = $modality;
		//$datarows->[$i+1]->[5] = $imagetype;
		//$datarows->[$i+1]->[6] = $type;
		//$datarows->[$i+1]->[7] = $level;
		//$datarows->[$i+1]->[8] = $assoctype;
		//$datarows->[$i+1]->[9] = $numboldreps;
		//$datarows->[$i+1]->[10] = "";
		//$datarows->[$i+1]->[11] = "";
		//$datarows->[$i+1]->[12] = "";

		/* check if the step is enabled */
		if (!enabled) {
			//$datarows->[$i+1]->[12] = "This step is not enabled, skipping";
			dlog << QString("    Data specification step [%1] is NOT enabled. Skipping this download step").arg(i);
			continue;
		}

		/* check if the step is optional */
		if (optional) {
			//$datarows->[$i+1]->[12] = "This step is optional, skipping the checks";
			dlog << QString("    Data step [%1] is OPTIONAL. Ignoring the checks").arg(i);
			continue;
		}

		/* make sure the requested modality table exists */
		q.prepare(QString("show tables like '%1_series'").arg(modality.toLower()));
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() < 1) {
			dlog << "    ERROR: modality ["+modality+"] not found";
			//$datarows->[$i+1]->[10] = "no";
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
		if (!n->GetSQLComparison(numboldreps, comparison, num))
			n->WriteLog("Invalid comparison for boldreps ["+numboldreps+"]. Ignoring BOLD rep comparison");
		else
			validComparisonStr = true;

		/* if its a subject level, check the subject for the protocol(s) */
		int subjectid = s.subjectid;
		QString studytype = s.studytype;
		QString studydate = s.studydatetime.toString("yyyy-MM-dd hh:mm:ss");
		if (level == "subject") {
			datalog += "    This data step is subject level [$protocol], association type [$assoctype]\n";

			QString sqlstring;
			if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
				/* find the data from the same subject and modality that has the nearest (in time) matching scan */
				n->WriteLog("Searching for subject-level data nearest in time...");
				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				sqlstring += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '%2' ) ) LIMIT 1").arg(modality).arg(studydate);

				dlog << "    Searching for subject-level data nearest in time ["+sqlstring+"]";

				//$datarows->[$i+1]->[12] = "Searching for data from the same SUBJECT and modality that has the nearest (in time) matching scan";
				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
			}
			else if (assoctype == "all") {
				n->WriteLog("Searching for all subject-level data...");
				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				dlog << "    Searching for all subject-level data ["+sqlstring+"]";

				//$datarows->[$i+1]->[12] = "Searching for ALL data from the same SUBJECT and modality";
				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
			}
			else {
				/* find the data from the same subject and modality that has the same study_type */
				n->WriteLog("Searching for subject-level data with same study type...");
				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				sqlstring += " and `studies`.study_type = :studytype";
				dlog << "    Searching for subject-level data with same study type ["+sqlstring+"]";

				//$datarows->[$i+1]->[12] = "Searching for data from the same SUBJECT and modality, with the same study type (visit)";
				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
				q.bindValue(":studytype", studytype);
			}

			n->WriteLog(sqlstring);
			//int newseriesnum = 1;
			n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
			if (q.size() > 0) {
				dlog << QString("    Found [%1] matching subject-level series").arg(q.size());
				//$datarows->[$i+1]->[10] = "yes (".$resultC->numrows.")";
			}
			else {
				dlog << "    Found 0 rows matching the subject-level required protocol(s)";
				stepIsInvalid = true;
				//$datarows->[$i+1]->[10] = "no";
				break;
			}
		}
		/* otherwise, check the study for the protocol(s) */
		else {
			QString sqlstring;
			datalog += "    Checking the study [$studyid] for the protocol ($protocols)\n";
			/* get a list of series satisfying the search criteria, if it exists */
			if (validComparisonStr) {
				sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
				if (imagetypes == "''")
					sqlstring += " and image_type in ("+imagetypes+")";

				q.prepare(sqlstring);
				q.bindValue(":subjectid", subjectid);
			}
			else {
				sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
				if (imagetypes != "''") {
					sqlstring += " and image_type in (" + imagetypes + ")";
				}
				sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);
			}
			n->WriteLog(sqlstring);
			dlog << "    Checking if study contains data [" + sqlstring + "]";
			n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
			if (q.size() > 0) {
				dlog << QString("    This study contained step [%1]").arg(i);
			}
			else {
				dlog << QString("    This study did NOT contain this required step [%1]").arg(i);
				stepIsInvalid = true;
				break;
			}
		}
	}
	dlog << "---------- Done checking data steps ----------";

	/* if it's a subject level dependency, but there is no data found, we don't want to copy any dependencies */
	if ((stepIsInvalid) && (deplevel == "subject")) {
		//my $datatable = generate_table(rows => $datarows, header_row => 1);

		// bail out of this function, the data spec didn't work out for this subject
		datalog = dlog.join("\n");
		return false; //(0,$datalog, $datareport, $datatable);
	}

	/* if there is a dependency, don't worry about the previous checks */
	if (pipelinedep != 0) {
		stepIsInvalid = false;
	}

	// check for any bad data items
	if (stepIsInvalid) {
		//my $datatable = generate_table(rows => $datarows, header_row => 1);

		// bail out of this function, the data spec didn't work out for this subject
		datalog = dlog.join("\n");
		return false; //(0,$datalog, $datareport, $datatable);
	}


	// ------ end checking the data steps --------------------------------------
	// if we get to here, the data spec is valid for this study
	// so we can assume all of the data exists, and start copying it
	// -------------------------------------------------------------------------

	n->WriteLog("Modality: $modality");

	n->InsertAnalysisEvent(analysisid, pipelineid, p.version, studyid, "analysiscopydata", "Started copying data to [<tt>" + analysispath + "</tt>]");

	datalog += "\n\n------------------------------------------------------------------------------\n";
	datalog += "---------- Required data for this study exists, beginning data copy ----------\n";
	datalog += "------------------------------------------------------------------------------\n";
	/* go through list of data search criteria again to do the actual copying */
	for (int i = 0; i < datadef.size(); i++) {

		int id = datadef[i].id;
		int order = datadef[i].order;
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
		QString level = datadef[i].level;
		bool optional = datadef[i].optional;
		QString numboldreps = datadef[i].numboldreps;

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


		n->WriteLog("Working on step [$i] id [$id]");

		/* SQL comparison string */
		QString comparison;
		int num(0);
		bool validComparisonStr = false;
		if (!n->GetSQLComparison(numboldreps, comparison, num))
			n->WriteLog("Invalid comparison for boldreps ["+numboldreps+"]. Ignoring BOLD rep comparison");
		else
			validComparisonStr = true;

		datalog += "Copying data for step [$i] id [$id]\n";
		//datalog += "    bold reps comparison [$comparison] [$num]\n";

		/* check to see if we should even run this step */
		if (!enabled) {
			n->WriteLog("Data step [$i] not enabled");
			dlog << "    This data item [" + protocol + "] is not enabled";
			continue;
		}


		//WriteLog("---------- Checking for $protocol for $modality ----------");
		//datalog += "---------- Checking for protocol [$protocol] modality [$modality] imagetype [$imagetype] ----------\n";
		int neareststudynum(-1); // = "";
		//$modality = lc($modality);
		/* use the correct SQL schema column name for the series_desc... */
		QString seriesdescfield = "series_desc";
		if (modality != "mr")
			seriesdescfield = "series_protocol";

		/* make sure the requested modality table exists */
		q.prepare(QString("show tables like '%1_series'").arg(modality.toLower()));
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() < 1) {
			dlog << n->WriteLog("    ERROR: modality [" + modality + "] not found");
			continue;
		}

		QString sqlstring;
		/* get a list of series satisfying the search criteria, if it exists */
		if (level == "study") {
			//WriteLog("BOLD reps comparison [$comparison] [$num] inside StudyLevel");
			//datalog += "    BOLD reps check: comparison [$comparison] num [$num] inside StudyLevel\n";

			datalog += "    This data step is study-level\n        protocols [$protocols]\n        criteria [$criteria]\n        imagetype [$imagetype]\n";

			sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
			if (imagetypes != "''")
				sqlstring += " and image_type in (" + imagetypes + ")";

			if (criteria == "first")
				sqlstring += " order by series_num asc limit 1";
			if (criteria == "last")
				sqlstring += " order by series_num desc limit 1";
			if (criteria == "largestsize")
				sqlstring += " order by series_size desc, numfiles desc, img_slices desc limit 1";
			if (criteria == "smallestsize")
				sqlstring += " order by series_size asc, numfiles asc, img_slices asc limit 1";
			if (criteria == "usesizecriteria")
				sqlstring += QString(" and numfiles %1 %2 order by series_num asc").arg(comparison).arg(num);
			else
				sqlstring += " order by series_num asc";

			q.prepare(sqlstring);
			q.bindValue(":studyid", studyid);
			dlog << "    ... at the end of the study level section, SQL [$sqlstring]\n";
		}
		else {
			datalog += "    This data step is subject-level\n        protocols[$protocols]\n        association type [$assoctype]\n        imagetype [$imagetypes]\n";

			if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
				// find the data from the same subject and modality that has the nearest (in time) matching scan
				n->WriteLog("Searching for subject-level data nearest in time...");
				datalog += "    Searching for subject-level data nearest in time\nSearching for nearest study first...";
				// get the otherstudyid
				QSqlQuery q2;
				QString sqlstringA;
				sqlstringA = QString("SELECT `studies`.study_id, `studies`.study_num FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstringA += QString("and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				sqlstringA += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '$studydate' ) ) LIMIT 1").arg(modality);
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
					n->WriteLog("Could not find a matching row: [$sqlstring]");
					// this data item is probably optional, so go to the next item
					continue;
				}

				datalog += "    Still within the subject-level data search\n        protocols [$protocols]\n        criteria [$criteria]\n        imagetype [$imagetypes]\n";

				// base SQL string
				sqlstring = QString("select * from %1_series where study_id = $otherstudyid and trim(%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);
				if (imagetypes != "''")
					sqlstring += " and image_type in (" + imagetypes + ")";

				/* determine the ORDERing and LIMITs */
				if (criteria == "first")
					sqlstring += " order by series_num asc limit 1";
				if (criteria == "last")
					sqlstring += " order by series_num desc limit 1";
				if (criteria == "largestsize")
					sqlstring += " order by series_size desc, numfiles desc, img_slices desc limit 1";
				if (criteria == "smallestsize")
					sqlstring += " order by series_size asc, numfiles asc, img_slices asc limit 1";
				if (criteria == "usesizecriteria")
					sqlstring += QString(" and numfiles %1 %2 order by series_num asc").arg(comparison).arg(num);
				else
					sqlstring += " order by series_num asc";

				q.prepare(sqlstring);
				q.bindValue(":subjectid", s.subjectid);

				datalog += "    ... now searching for the data IN the nearest study\n";

			}
			else if (assoctype == "all") {
				n->WriteLog("Searching for all subject-level data...");
				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				if (validComparisonStr)
					sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);

				q.prepare(sqlstring);
				q.bindValue(":subjectid", s.subjectid);

				datalog += "    Searching for all subject-level data [$sqlstring]\n";
			}
			else {
				// find the data from the same subject and modality that has the same study_type
				n->WriteLog("Searching for subject-level data with same study type...");
				sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

				if (imagetypes != "''")
					sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

				if (validComparisonStr)
					sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);

				sqlstring += " and `studies`.study_type = :studytype";

				q.prepare(sqlstring);
				q.bindValue(":subjectid", s.subjectid);
				q.bindValue(":studytype", s.studytype);

				datalog += "    Searching for subject-level data with same study type [$sqlstring]\n";
			}
		}
		n->WriteLog(sqlstring);
		datalog += "    Resulting SQL string [$sqlstring]\n";
		int newseriesnum = 1;
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() > 0) {

			//$datarows->[$i+1]->[10] = "yes";

			dlog << n->WriteLog(QString("Found [%1] matching subject-level series").arg(q.size()));
			//datalog += "    type [$type] data found [" . $resultC->numrows . "] rows, creating [$analysispath]\n";
			// in theory, data for this analysis exists for this study, so lets now create the analysis directory
			QString m;
			if (!n->MakePath(analysispath, m)) {
				n->WriteLog("Error: unable to create directory ["+analysispath+"]");
				continue;
			}

			while (q.next()) {
				numdownloaded++;
				int localstudynum;
				n->WriteLog("NumDownloaded: $numdownloaded");
				int seriesid = q.value(modality+"series_id").toInt();
				int seriesnum = q.value("series_num").toInt();
				QString seriesdesc = q.value("series_desc").toString();
				QString seriesdatetime = q.value("series_datetime").toString();
				QString datatype = q.value("data_type").toString();
				qint64 seriessize = q.value("series_size").toLongLong();
				int numfiles = q.value("numfiles").toInt();
				QString phaseplane = q.value("phaseencodedir").toString();
				double phaseangle = q.value("phaseencodeangle").toDouble();
				int phasepositive;
				if (q.value("PhaseEncodingDirectionPositive").isNull())
					phasepositive = -1;
				else
					q.value("PhaseEncodingDirectionPositive").toInt();

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
				n->WriteLog("Processing $seriesdesc [$seriessize bytes] [$numfiles files]");
				datalog += "    Working on seriesdesc [$seriesdesc] seriesnum [$seriesnum] seriesdatetime [$seriesdatetime]...\n";
				QString behoutdir;
				QString indir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum).arg(datatype);
				QString behindir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum);

				/* start building the analysis path */
				QString newanalysispath = analysispath + "/" + location;

				/* check if the series numbers are used, and if so, are they preserved */
				if (useseries) {
					if (!preserveseries) {
						// renumber the series
						newanalysispath += QString("/%1").arg(newseriesnum);
						behoutdir = GetBehPath(behformat, analysispath, location, behdir, newseriesnum);
						//if ($behformat == "behroot") { $behoutdir = "$analysispath/$location"; }
						//if ($behformat == "behrootdir") { $behoutdir = "$analysispath/$location/$behdir"; }
						//if ($behformat == "behseries") { $behoutdir = "$analysispath/$location/$newseriesnum"; }
						//if ($behformat == "behseriesdir") { $behoutdir = "$analysispath/$location/$newseriesnum/$behdir"; }
						newseriesnum++;
					}
					else {
						newanalysispath += QString("/%1").arg(seriesnum);
						behoutdir = GetBehPath(behformat, analysispath, location, behdir, seriesnum);
						//if ($behformat == "behroot") { $behoutdir = "$analysispath/$location"; }
						//if ($behformat == "behrootdir") { $behoutdir = "$analysispath/$location/$behdir"; }
						//if ($behformat == "behseries") { $behoutdir = "$analysispath/$location/$seriesnum"; }
						//if ($behformat == "behseriesdir") { $behoutdir = "$analysispath/$location/$seriesnum/$behdir"; }
					}
				}
				else {
					behoutdir = GetBehPath(behformat, analysispath, location, behdir, seriesnum);
					//if ($behformat == "behroot") { $behoutdir = "$analysispath/$location"; }
					//if ($behformat == "behrootdir") { $behoutdir = "$analysispath/$location/$behdir"; }
					//if ($behformat == "behseries") { $behoutdir = "$analysispath/$location/$seriesnum"; }
					//if ($behformat == "behseriesdir") { $behoutdir = "$analysispath/$location/$seriesnum/$behdir"; }
				}

				datalog += "    behformat [$behformat] behoutdir [$behoutdir]\n";
				if (usephasedir) {
					QString phasedir = "unknownPE";

					datalog += "    PhasePlane [$phaseplane] PhasePositive [$phasepositive]\n";
					if ((phaseplane == "COL") && (phasepositive == 1)) phasedir = "AP";
					if ((phaseplane == "COL") && (phasepositive == 0)) phasedir = "PA";
					if ((phaseplane == "COL") && (phasepositive == -1)) phasedir = "COL";
					if ((phaseplane == "ROW") && (phasepositive == 1)) phasedir = "RL";
					if ((phaseplane == "ROW") && (phasepositive == 0)) phasedir = "LR";
					if ((phaseplane == "ROW") && (phasepositive == -1)) phasedir = "ROW";

					newanalysispath += "/" + phasedir;
				}

				datalog += "    Creating directory [$newanalysispath]\n";
				QString m;
				if (!n->MakePath(newanalysispath, m))
					n->WriteLog("Error creating directory [" + newanalysispath + "] message [" + m + "]");

				n->SystemCommand("chmod -Rf 777 " + newanalysispath);

				// output the correct file type
				if ((dataformat == "dicom") || ((datatype != "dicom") && (datatype != "parrec"))) {
					QString systemstring;
					if (p.dataCopyMethod == "scp")
						systemstring = "scp $tmpdir/* $cfg{'clusteruser'}\@$pipelinesubmithost:$newanalysispath";
					else
						systemstring = "cp -v $tmpdir/* $newanalysispath";
					dlog << n->WriteLog(n->SystemCommand(systemstring));
				}
				else {
					QString tmpdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
					QString m;
					if (!n->MakePath(tmpdir, m))
						n->WriteLog("Error creating directory [" + tmpdir + "] message [" + m + "]");

					datalog += "    Created temp directory [$tmpdir]\n";
					datalog += "    Calling ConvertDicom($dataformat, $indir, $tmpdir, $gzip, $uid, $localstudynum, $seriesnum, $datatype)\n";
					//n->ConvertDicom(dataformat, indir, tmpdir, gzip, uid, localstudynum, seriesnum, datatype);
					int numfilesconv(0);
					int numfilesrenamed(0);
					n->ConvertDicom(dataformat, indir, tmpdir, gzip, uid, QString(localstudynum), QString(seriesnum), datatype, numfilesconv, numfilesrenamed, m);

					QString systemstring;
					if (p.dataCopyMethod == "scp")
						systemstring = "scp $tmpdir/* $cfg{'clusteruser'}\@$pipelinesubmithost:$newanalysispath";
					else
						systemstring = "cp -v $tmpdir/* $newanalysispath";
					dlog << n->WriteLog(n->SystemCommand(systemstring));

					n->WriteLog("Copying data using command (ConvertToNifti) [$systemstring] output [$output]");

					n->WriteLog("Removing temp directory [$tmpdir]");
					datalog += "    Removing temp directory [$tmpdir]\n";
					if (!n->RemoveDir(tmpdir,m))
						n->WriteLog("Unable to remove directory [" + tmpdir + "] error [" + m + "]");
				}

				//$datarows->[$i+1]->[11] = $newanalysispath;

				// copy the beh data
				if (behformat != "behnone") {
					datalog += "    Copying behavioral data\n";
					datalog += "        - Creating directory [$behoutdir]\n";
					QString m;
					if (!n->MakePath(behoutdir, m))
						n->WriteLog("Error creating directory [" + behoutdir + "] message [" + m + "]");
					//mkpath($behoutdir, {mode => 0777});
					QString systemstring = "cp -Rv " + behindir + "/* " + behoutdir;
					dlog << n->WriteLog(n->SystemCommand("chmod -Rf 777 " + behoutdir));

					n->SystemCommand("chmod -Rf 777 " + behoutdir);
					datalog += "        Done copying behavioral data...\n";
				}

				// give full read/write permissions to everyone
				n->SystemCommand("chmod -Rf 777 " + newanalysispath);

				dlog << n->WriteLog("Done writing data to " + newanalysispath);
			}
		}
		else {
			dlog << n->WriteLog("Found no matching subject-level [" + protocol + "] series. SQL: [" + sqlstring + "]");
		}
	}
	//my $datatable = generate_table(rows => $datarows, header_row => 1);
	n->WriteLog("\n$datatable");
	datalog += "\n$datatable\nLeaving GetData(). Copied [$numdownloaded] series\n";
	n->WriteLog("Leaving GetData() successfully => ret($numdownloaded, datalog)");
	n->InsertAnalysisEvent(analysisid, pipelineid, p.version, studyid, "analysiscopydataend", QString("Finished copying data [%1] series downloaded").arg(numdownloaded));

	return true; //($numdownloaded, $datalog, $datareport, $datatable);
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
			//int id = steps[i].id;
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
	QFile f(jobfilename);
	if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
		QTextStream fs(&f);
		fs << jobfile;
		f.close();
		return true;
	}
	else {
		return false;
	}
}


/* ---------------------------------------------------------- */
/* --------- GetStudyToDoList ------------------------------- */
/* ---------------------------------------------------------- */
QList<int> modulePipeline::GetStudyToDoList(int pipelineid, QString modality, QString depend, QString groupids) {

	QSqlQuery q;

	// get list of studies which do not have an entry in the analysis table for this pipeline
	if (depend != "") {
		// there is a dependency
		// need to check if ANY of the subject's studies have the dependency...

		/* step 1) get list of SUBJECTs who have completed the dependency */
		QList<int> list;
		QSqlQuery q2;
		q2.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id in (select a.subject_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_id in (select study_id from analysis where pipeline_id in (" + depend + ") and analysis_status = 'complete' and analysis_isbad <> 1) and a.isactive = 1)");
		n->WriteLog("StudyIDList SQL [$sqlstring]");
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			while (q2.next())
				                    list.append(q2.value("study_id").toInt());
		}
		QString studyidlist = n->JoinIntArray(list, ",");

		if (studyidlist == "")
			studyidlist = "0";

		/* step 2) then find all STUDIES that those subjects have completed */
		if (groupids == "") {
			// with no groupids
			q.prepare("select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and study_id in (" + studyidlist + ") and (study_datetime < date_sub(now(), interval 6 hour)) order by study_datetime desc");
			q.bindValue(":pipelineid", pipelineid);
			n->WriteLog("GetStudyToDoList(): dependency and no groupids");
		}
		else {
			// with groupids
			q.prepare("select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and a.study_id in (" + studyidlist + ") and (a.study_datetime < date_sub(now(), interval 6 hour)) and b.group_id in (" + groupids + ") order by a.study_datetime desc");
			q.bindValue(":pipelineid", pipelineid);
			n->WriteLog("GetStudyToDoList(): dependency and groupids");
		}
	}
	else {
		// no dependency
		if (groupids == "") {
			// with no groupids
			q.prepare("select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and (study_datetime < date_sub(now(), interval 6 hour)) and study_modality = :modality order by study_datetime desc");
			q.bindValue(":pipelineid", pipelineid);
			q.bindValue(":modality", modality);
			n->WriteLog("GetStudyToDoList(): No dependency and no groupids");
		}
		else {
			/* with groupids */
			q.prepare("SELECT a.study_id FROM studies a left join group_data b on a.study_id = b.data_id WHERE a.study_id NOT IN (SELECT study_id FROM analysis WHERE pipeline_id = :pipelineid) AND ( a.study_datetime < DATE_SUB( NOW( ) , INTERVAL 6 hour )) AND a.study_modality = :modality and b.group_id in (" + groupids + ") ORDER BY a.study_datetime DESC");
			q.bindValue(":pipelineid", pipelineid);
			q.bindValue(":modality", modality);
			n->WriteLog("GetStudyToDoList(): No dependency and groupids");
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
				QString uidstudynum;
				uidstudynum = QString("%1%2").arg(q2.value("uid").toString()).arg(q2.value("study_num").toInt());
				n->WriteLog(QString("Found study [%1] [%2]").arg(studyid).arg(uidstudynum));
			}
			list.append(studyid);
		}
	}

	/* now get only the studies that need to have their results rerun */
	q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_rerunresults = 1 and analysis_status = 'complete' and analysis_isbad <> 1) order by study_datetime desc");
	q.bindValue(":pipelineid", pipelineid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int studyid = q.value("study_id").toInt();
			n->WriteLog(QString("Found study (results rerun) [%1]").arg(studyid));
			list.append(studyid);
		}
	}

	/* now get only the studies that need to have their supplements run */
	q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_runsupplement = 1 and analysis_status = 'complete' and analysis_isbad <> 1) order by study_datetime desc");
	q.bindValue(":pipelineid", pipelineid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			int studyid = q.value("study_id").toInt();
			n->WriteLog(QString("Found study (results rerun) [%1]").arg(studyid));
			list.append(studyid);
		}
	}

	n->WriteLog(QString("Found %1 studies that met criteria").arg(list.size()));

	return list;
}