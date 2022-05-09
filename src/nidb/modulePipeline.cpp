/* ------------------------------------------------------------------------------
  NIDB modulePipeline.cpp
  Copyright (C) 2004 - 2022
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
    int totalSubmitted = 0;
    QSqlQuery q;
    QString m; /* for creating messages for logging */

    /* update the start time */
    SetPipelineProcessStatus("started",0,0);

    /* clear old pipeline_history entries */
    ClearPipelineHistory();

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
		//bool debug(false);

        int pipelineid = q.value("pipeline_id").toInt();

        qint64 runnum = 0;
        /* check if the pipeline is valid */
        pipeline p(pipelineid, n);
        if (!p.isValid) {
            m = n->WriteLog("Error starting pipeline. Pipeline was not valid [" + p.msg + "]");
            InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_started", m);
            InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_finished", "Pipeline stopped prematurely due to error");
            continue;
        }
        else {
            InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_started", QString("Pipeline %1 started").arg(p.name));
        }
		//debug = p.debug;

        /* get analysis directory root */
        QString analysisdir;
        if (p.dirStructure == "b")
            analysisdir = n->cfg["analysisdirb"];
        else
            analysisdir = n->cfg["analysisdir"];

        n->WriteLog(QString("========== Working on pipeline [%1] - [%2] Submits to queue [%3] through host [%4] ==========").arg(pipelineid).arg(p.name).arg(p.queue).arg(p.submitHost));

        SetPipelineProcessStatus("running",pipelineid,0);

        /* mark the pipeline as having been checked */
        QSqlQuery q2;
        q2.prepare("update pipelines set pipeline_lastcheck = now() where pipeline_id = :pid");
        q2.bindValue(":pid", pipelineid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

        /* check if the pipeline's queue is valid */
        if (p.queue == "") {
            m = n->WriteLog("No queue specified");
            InsertPipelineEvent(pipelineid, runnum, -1, "error_noqueue", m);
            InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_finished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }
        /* check if the submit host is valid */
        if (p.submitHost == "") {
            m = n->WriteLog("No submit host specified");
            InsertPipelineEvent(pipelineid, runnum, -1, "error_nosubmithost", m);
            InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_finished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }

        /* check if the pipeline is running, if so, go on to the next one */
        QString status = GetPipelineStatus(pipelineid);
        if (status == "running") {
            n->WriteLog("This pipeline is already running");
            continue;
        }

        /* update the pipeline status */
        SetPipelineRunning(pipelineid, "Submitting jobs");

        /* get the data steps */
        QList<dataDefinitionStep> dataSteps;
        if (p.level != 0) {
            if ((p.level == 1) || ((p.level == 2) && (p.parentDependencyIDs.size() < 1))) {
                dataSteps = GetPipelineDataDef(pipelineid, p.version);

                /* if there is no data definition and no dependency */
                if ((dataSteps.size() < 1) && (p.parentDependencyIDs.size() < 1)) {
                    m = n->WriteLog("Pipeline has no data items. Skipping pipeline.");
                    InsertPipelineEvent(pipelineid, runnum, -1, "error_nodatasteps", m);
                    InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_finished", "Pipeline stopped prematurely due to error");
                    SetPipelineStopped(pipelineid, m);
                    continue;
                }
                else
                    InsertPipelineEvent(pipelineid, runnum, -1, "getdatasteps", QString("Retreived [%1] pipeline data items").arg(dataSteps.size()));
            }
        }

        /* get the pipeline steps (the script) */
        QList<pipelineStep> steps = GetPipelineSteps(pipelineid, p.version);
        if (steps.size() < 1) {
            m = n->WriteLog("Pipeline has no script commands. Skipping pipeline.");
            InsertPipelineEvent(pipelineid, runnum, -1, "error_nopipelinesteps", m);
            InsertPipelineEvent(pipelineid, runnum, -1, "pipeline_finished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }
        else
            InsertPipelineEvent(pipelineid, runnum, -1, "getpipelinesteps", QString("Retreived [%1] pipeline script commands").arg(steps.size()));

        /* ------------------------------ level 0 ----------------------------- */
        if (p.level == 0) {
            /* check if this module should be running now or not */
            if (!n->ModuleCheckIfActive()) {
                n->WriteLog("Module disabled. Exiting");
                SetPipelineStopped(pipelineid, "Pipeline module disabled while running. Stopping.");
                SetPipelineProcessStatus("complete",0,0);
                return 1;
            }

            /* only 1 analysis should ever be run with the oneshot level, so if 1 already exists, regardless of state or pipeline version, then
               leave this function without running the analysis */
            q2.prepare("select * from analysis where pipeline_id = :pid");
            q2.bindValue(":pid", pipelineid);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            if (q2.size() > 0) {
                m = n->WriteLog("An analysis already exists for this one-shot level pipeline, exiting");
                SetPipelineStopped(pipelineid, m);
                continue;
            }

            /* create the analysis path */
            QString analysispath = QString("%1/%3").arg(p.pipelineRootDir).arg(p.name);
            n->WriteLog("Creating path [" + analysispath + "/pipeline]");
            QString m;
            if (!n->MakePath(analysispath + "/pipeline", m)) {
                n->WriteLog("Error: unable to create directory [" + analysispath + "/pipeline] - A");
                //UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + analysispath + "/pipeline]", 0, -1, "", "", false, true, -1, -1);
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
            q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate, analysis_isbad) values (:pid, :version,'','','processing',now(),0)");
            q2.bindValue(":pid", pipelineid);
            q2.bindValue(":version", p.version);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            qint64 analysisRowID = q2.lastInsertId().toLongLong();

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
                UpdateAnalysisStatus(analysisRowID, "submitted", "Submitted to [" + p.queue + "]", jobid, -1, "", "", true, false, 0, 0);
            }
            else {
                n->WriteLog("Error submitting job to cluster [" + qresult + "]");
                UpdateAnalysisStatus(analysisRowID, "error", "Submit error [" + qm + "]", 0, -1, "", "", false, true, 0, 0);
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
            QList<int> studyids = GetStudyToDoList(pipelineid, modality, pipelinedep, n->JoinIntArray(p.groupIDs, ","), runnum);

            int i = 0;
            int numsubmitted = 0;
            foreach (int sid, studyids) {
                i++;
                qint64 analysisRowID = -1;
                QStringList setuplog;
                //QString setupLogFile;

                SetPipelineProcessStatus("running", pipelineid, sid);

                numchecked++;

                /* get information about the study */
                study s(sid, n);
                if (!s.valid()) {
                    n->WriteLog("Study was not valid: [" + s.msg() + "]");
                    continue;
                }

                n->WriteLog(QString("---------- Working on study [%1%2] (%3 of %4) for pipeline [%5] ----------").arg(s.UID()).arg(s.studyNum()).arg(i).arg(studyids.size()).arg(p.name));

                /* check if the number of concurrent jobs is reached. the function also checks if this pipeline module is enabled */
                int filled;
                do {
                    filled = IsQueueFilled(pipelineid);

                    /* check if this pipeline is enabled */
                    if (!IsPipelineEnabled(pipelineid)) {
                        SetPipelineStopped(pipelineid, "Pipeline disabled while running. Stopping at next iteration.");
                        break;
                    }

                    /* check if this module is still enabled */
                    if (!n->ModuleCheckIfActive()) {
                        n->WriteLog("Module disabled. Exiting");
                        SetPipelineStopped(pipelineid, "Pipeline module disabled while running. Stopping.");
                        SetPipelineProcessStatus("complete",0,0);
                        return 1;
                    }

                    /* otherwise check what to do, depending on how filled the queue is */
                    if (filled == 0)
                        break;

                    if (filled == 1) {
                        m = n->WriteLog("Concurrent analysis quota reached, waiting 15 seconds");
                        SetPipelineStatusMessage(pipelineid, m);
                        InsertPipelineEvent(pipelineid, runnum, -1, "maxjobs_reached", m);
                        n->ModuleRunningCheckIn();
                        QThread::sleep(15); /* sleep for 15 seconds */
                    }
                    if (filled == 2) {
                        return 1;
                    }
                } while (filled == 1);

                if (!IsPipelineEnabled(pipelineid)) {
                    SetPipelineStopped(pipelineid, "Pipeline disabled while running. Stopping at this iteration.");
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
                            q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate, analysis_isbad) values (:pipelineid, :version, :pipelinedep, :studyid,'processing',now(), 0)");
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

                    //n->WriteLog(QString("StudyDateTime: [%1], Working on: [%2%3]").arg(s.studydatetime.toString("yyyy-MM-dd hh:mm:ss")).arg(s.uid).arg(s.studynum));

                    QString analysispath = "";
                    if (p.dirStructure == "b")
                        analysispath = QString("%1/%2/%3/%4").arg(p.pipelineRootDir).arg(p.name).arg(s.UID()).arg(s.studyNum());
                    else
                        analysispath = QString("%1/%2/%3/%4").arg(p.pipelineRootDir).arg(s.UID()).arg(s.studyNum()).arg(p.name);
                    n->WriteLog("analysispath is [" + analysispath + "]");

                    /* get the nearest study for this subject that has the dependency */
                    int studyNumNearest(0);
					q2.prepare("select analysis_id, study_num from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.subject_id = :subjectid and a.pipeline_id = :pipelinedep and a.analysis_status = 'complete' and (a.analysis_isbad <> 1 or a.analysis_isbad is null) order by abs(timestampdiff(minute, b.study_datetime, :studydatetime)) limit 1");
                    q2.bindValue(":subjectid", s.subjectRowID());
                    q2.bindValue(":pipelinedep", pipelinedep);
                    q2.bindValue(":studydatetime", s.dateTime().toString("yyyy-MM-dd hh:mm:ss"));
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
                                UpdateAnalysisStatus(analysisRowID, "OddDependencyStatus", depstatus, -1, -1, setuplog.join("\n"), setuplog.join("\n"), false, false, -1, -1);
                                continue;
                            }
                        }
                    }
                    setuplog << n->WriteLog("This analysis path is [" + analysispath + "]");

                    int numseriesdownloaded = 0;
                    /* get the data if we are not running a supplement, and not rerunning the results */
                    if ((!a.runSupplement) && (!a.rerunResults)) {
                        if (!GetData(sid, analysispath, s.UID(), analysisRowID, pipelineid, pipelinedep, p.depLevel, dataSteps, numseriesdownloaded, datalog)) {
                            n->WriteLog("GetData() returned false");
                        }
                        else
                            n->WriteLog(QString("GetData() downloaded [%1] series").arg(numseriesdownloaded));
                    }
                    UpdateAnalysisStatus(analysisRowID, "", "", -1, numseriesdownloaded, "", datalog, false, false, -1, -1);

                    // again check if there are any series to actually run the pipeline on...
                    // ... but its ok to run if any of the following are true
                    //     a) rerunresults is true
                    //     b) runsupplement is true
                    //     c) this pipeline is dependent on another pipeline
                    bool okToRun = false;

                    if (numseriesdownloaded > 0) {
                        okToRun = true; // there is data to download from this study
                        setuplog << n->WriteLog(QString("Study [%1%2] has [%2] matching series downloaded. Beginning analysis.").arg(s.UID()).arg(s.studyNum()).arg(numseriesdownloaded));
                    }
                    if (a.rerunResults) {
                        okToRun = true;
                        setuplog << n->WriteLog(QString("Study [%1%2] set to have results rerun. Beginning analysis.").arg(s.UID()).arg(s.studyNum()));
                    }
                    if (a.runSupplement) {
                        okToRun = true;
                        setuplog << n->WriteLog(QString("Study [%1%2] set to have supplement run. Beginning analysis.").arg(s.UID()).arg(s.studyNum()));
                    }
                    if ((pipelinedep != -1) && (p.depLevel == "study")) {
                        okToRun = true; // there is a parent pipeline and we're using the same study from the parent pipeline. may or may not have data to download
                        setuplog << n->WriteLog(QString("Study [%1%2] has a study-level parent pipeline. Beginning analysis.").arg(s.UID()).arg(s.studyNum()));
                    }

                    /* one of the above criteria has been satisfied, so its ok to run the pipeline on this study and submit the cluster */
                    if (okToRun) {

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
                                    SetPipelineStopped(pipelineid, QString("Parent pipeline [%1] does not exist!").arg(pipelinedep));
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
                                UpdateAnalysisStatus(analysisRowID, "error", "Unable to create directory [" + analysispath + "/pipeline]", 0, -1, "", "", false, true, -1, -1);
                                continue;
                            }
                            else
                                n->WriteLog("Created directory [" + analysispath + "/pipeline] - B");

                            n->WriteLog(n->SystemCommand("chmod -Rf 777 " + analysispath + "/pipeline", true, true));
                            if (pipelinedep != -1) {
                                if (p.depLevel == "subject") {
                                    if (p.dirStructure == "b")
                                        deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(dependencyname).arg(s.UID()).arg(studyNumNearest);
                                    else
                                        deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(s.UID()).arg(studyNumNearest).arg(dependencyname);
                                }
                                else {
                                    if (p.dirStructure == "b")
                                        deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(dependencyname).arg(s.UID()).arg(s.studyNum());
                                    else
                                        deppath = QString("%1/%2/%3/%4").arg(pipelinedirectory).arg(s.UID()).arg(s.studyNum()).arg(dependencyname);
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
                                if (p.depLinkType == "hardlink") systemstring = "cp -aulL "; /* L added to allow copying of softlinks */
                                else if (p.depLinkType == "softlink") systemstring = "cp -aus ";
                                else if (p.depLinkType == "regularcopy") systemstring = "cp -au ";
                                //if (p.depLinkType == "hardlink") systemstring = "rsync -aH "; /* try rsync to overcome cp bug in CentOS8 Stream (update, rsync doesn't create hardlinks with -H) */
                                //else if (p.depLinkType == "softlink") systemstring = "cp -aus ";
                                //else if (p.depLinkType == "regularcopy") systemstring = "cp -au ";
                                if (p.depDir == "subdir") {
                                    systemstring += deppath + " " + analysispath + "/";
                                    setuplog << n->WriteLog("Parent pipeline will be copied to a subdir [" + systemstring + "]");
                                }
                                else {
                                    systemstring += deppath + "/* " + analysispath + "/";
                                    setuplog << n->WriteLog("Parent pipeline will be copied to the root dir [" + systemstring + "] ");
                                }
                                setuplog << n->WriteLog(n->SystemCommand(systemstring));
                                //setuplog << n->WriteLog(n->SystemCommand(systemstring, true, false, false));

                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "Parent pipeline copied by running [" + systemstring + "]");
                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysisdependencyid", QString("%1").arg(dependencyanalysisid));

                                /* delete any log files and SGE files that came with the dependency */
                                setuplog << n->WriteLog(n->SystemCommand(QString("rm --preserve-root %1/pipeline/* %1/origfiles.log %1/sge.job").arg(analysispath)));

                                /* make sure the whole tree is writeable */
                                setuplog << n->WriteLog(n->SystemCommand("chmod -Rf 777 " + analysispath, true, true));
                            }
                            else {
                                setuplog << n->WriteLog("This pipeline is not dependent on another pipeline");
                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This pipeline does not depend on other pipelines");
                            }

                            /* this file will record any events during setup */
                            QString setupLogFile = analysispath + "/pipeline/analysisSetup.log";

                            /* now safe to write out the setuplog */
                            n->AppendCustomLog(setupLogFile, setuplog.join("\n"));
                            n->WriteLog("Should have created this analysis setup log [" + setupLogFile + "]");

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

                        if (CreateClusterJobFile(localsgefilepath, p.clusterType, analysisRowID, s.UID(), s.studyNum(), clusteranalysispath, p.useTmpDir, p.tmpDir, s.dateTime().toString("yyyy-MM-dd hh:mm:ss"), p.name, pipelineid, p.resultScript, p.maxWallTime, steps, a.runSupplement)) {
                            n->WriteLog("Created (local path) sge job submit file [" + localsgefilepath + "]");
                        }
                        else {
                            UpdateAnalysisStatus(analysisRowID, "error", "Error creating cluster job file", 0, -1, "", "", false, true, -1, -1);
                            continue;
                        }

                        n->SystemCommand("chmod -Rf 777 " + analysispath, true, true);

                        /* submit the cluster job file */
                        QString qm, qresult;
                        int jobid;
                        if (n->SubmitClusterJob(clustersgefilepath, p.submitHost, n->cfg["qsubpath"], n->cfg["queueuser"], p.queue, qm, jobid, qresult)) {
                            n->WriteLog("Successfully submitted job to cluster ["+qresult+"]");
                            UpdateAnalysisStatus(analysisRowID, "submitted", "Submitted to [" + p.queue + "]", jobid, numseriesdownloaded, "", "", false, true, 0, 0);
                            n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmitted", qresult);
                        }
                        else {
                            n->WriteLog("Error submitting job to cluster [" + qresult + "]");
                            UpdateAnalysisStatus(analysisRowID, "error", "Submit error [" + qm + "]", 0, numseriesdownloaded, "", "", false, true, 0, 0);
                            n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmiterror", "Analysis submitted to cluster, but was rejected with errors [" + qm + "]");
                            submiterror = true;
                        }

                        numsubmitted++;
                        totalSubmitted++;
                        jobsWereSubmitted = true;

                        SetPipelineStatusMessage(pipelineid, QString("Submitted %1%2").arg(s.UID()).arg(s.studyNum()));

                        /* check if this module should be running now or not */
                        if (!n->ModuleCheckIfActive()) {
                            m = n->WriteLog("Pipeline module disabled while running. Exiting");
                            SetPipelineStopped(pipelineid, m);
                            SetPipelineProcessStatus("complete",0,0);
                            return 1;
                        }
                        /* wait 10 seconds before submitting the next job */
                        QThread::sleep(10);
                    }
                    else {
                        n->WriteLog("Not Ok to submit job");
                        /* update the analysis table with the datalog so people can check later on why something didn't process */
                        UpdateAnalysisStatus(analysisRowID, "", "", -1, -1, datalog, datalog, false, false, -1, -1);
                        n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissetuperror", "No data found, 0 series returned from search");
                    }
                    n->WriteLog(QString("Submitted [%1] jobs so far").arg(numsubmitted));

                    /* mark the study in the analysis table */
                    //n->WriteLog(QString("numseriesdownloaded [%1]  pipelinedep [%2]  deplevel [%3]  runSupplement [%4]  rerunResults [%5]").arg(numseriesdownloaded).arg(pipelinedep).arg(p.depLevel).arg(a.runSupplement).arg(a.rerunResults));
                    if (!submiterror) {
                        if ((numseriesdownloaded > 0) || ((pipelinedep != -1) && (p.depLevel == "study")) || (a.runSupplement) || (a.rerunResults)) {
                            /* do nothing right here... :) */
                        }
                        else {
                            /* save some database space, since most entries will be blank */
                            UpdateAnalysisStatus(analysisRowID, "NoMatchingSeries", "", -1, -1, "", "", false, false, -1, -1);
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
                    SetPipelineStopped(pipelineid, "Pipeline disabled while running. Normal stop.");
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
        //SetPipelineStatusMessage(pipelineid, "Finished submitting jobs");
        SetPipelineStopped(pipelineid, "Finished submitting jobs");
    }

    SetPipelineProcessStatus("complete",0,0);

    if (jobsWereSubmitted) {
        n->WriteLog(QString("Done with pipeline module. [%1] total jobs were submitted. jobsWereSubmitted [%2]").arg(totalSubmitted).arg(jobsWereSubmitted));
        return true;
    }
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- GetData ---------------------------------------- */
/* ---------------------------------------------------------- */
bool modulePipeline::GetData(int studyid, QString analysispath, QString uid, qint64 analysisid, int pipelineid, int pipelinedep, QString deplevel, QList<dataDefinitionStep> datadef, int &numdownloaded, QString &datalog) {

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
    if (!s.valid()) {
        n->WriteLog("Study was not valid: [" + s.msg() + "]");
        return false;
    }
    QString modality = s.modality();
    int studynum = s.studyNum();
    QString studytype = s.type();

    dlog << QString("Working on study [%1%2]\nstudyid [%3]\nModality [%4]\n").arg(uid).arg(studynum).arg(studyid).arg(modality);
    dlog << " ********** Checking if all required data exists **********";

    /* ------------------------------------------------------------------------
       check all of the steps to see if this data spec is valid
       ------------------------------------------------------------------------ */
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

        dlog << QString("Checking step [%1]").arg(i);

        /* check if the step is enabled */
        if (!enabled) {
            dlog << QString("   Step [%1] not enabled. Skipping").arg(i);
            RecordDataDownload(datadownloadid, analysisid, modality, 0, 0, -1, "", i, "Step not enabled, skipping step");
            continue;
        }

        /* check if the step is optional */
        if (optional) {
            dlog << QString("   Step [%1] optional. Skipping").arg(i);
            RecordDataDownload(datadownloadid, analysisid, modality, 0, 0, -1, "", i, "Step is optional, skipping step");
            continue;
        }

        dlog << QString("   Step [%1] Checking if the following data exist:   protocol [%2]  modality [%3]  imagetype [%4]  enabled [%5]  type [%6]  level [%7]  assoctype [%8]  optional [%9]  numboldreps [%10]").arg(i).arg(protocol).arg(modality).arg(imagetype).arg(enabled).arg(type).arg(level).arg(assoctype).arg(optional).arg(numboldreps);

        /* make sure the requested modality table exists */
        q.prepare(QString("show tables like '%1_series'").arg(modality.toLower()));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            dlog << QString("   Step [%1]. Modality [%2] is not valid").arg(i).arg(modality);
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
            for(int i=0; i<types.size(); i++)
                types[i] = types[i].replace("\\", "\\\\");
            imagetypes = "'" + types.join("','") + "'";
        }
        else
            imagetypes = "'" + imagetype.replace("\\", "\\\\") + "'";

        /* expand the comparison into SQL */
        QString comparison;
        int num(0);
        bool validComparisonStr = false;
        if (n->GetSQLComparison(numboldreps, comparison, num))
            validComparisonStr = true;

        /* if its a subject level, check the subject for the protocol(s) */
        int subjectid = s.subjectRowID();
        QString studydate = s.dateTime().toString("yyyy-MM-dd hh:mm:ss");
        if (level == "subject") {
            dlog << "   Note: this data step is subject level [" + protocol + "], association type [" + assoctype + "]";

            QString sqlstring;
            if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
                /* find the data from the same subject and modality that has the nearest (in time) matching scan */
                dlog << QString("   Searching for data from the same SUBJECT and modality that has the nearest (in time) matching scan");

                sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

                if (imagetypes != "''")
                    sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

                sqlstring += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '%2' ) ) LIMIT 1").arg(modality).arg(studydate);

                q.prepare(sqlstring);
                q.bindValue(":subjectid", subjectid);
            }
            else if (assoctype == "all") {
                dlog << QString("   Searching for ALL data from the same SUBJECT and modality");

                sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

                if (imagetypes != "''")
                    sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

                q.prepare(sqlstring);
                q.bindValue(":subjectid", subjectid);
            }
            else {
                /* find the data from the same subject and modality that has the same study_type */
                dlog << QString("   Searching for data from the same SUBJECT, Modality, and StudyType");

                sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

                if (imagetypes != "''")
                    sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

                sqlstring += " and `studies`.study_type = :studytype";

                q.prepare(sqlstring);
                q.bindValue(":subjectid", subjectid);
                q.bindValue(":studytype", studytype);
            }

            dlog << n->WriteLog("   SQL used for this search (for debugging) [" + n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__,true) + "]");
            if (q.size() > 0) {
                dlog << QString("   Data FOUND for step [%1] (subject level)").arg(i);
                RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, -1, "", i, "Data found for this step (subject level)");
            }
            else {
                dlog << QString("   Data NOT found for step [%1] (subject level)").arg(i);
                RecordDataDownload(datadownloadid, analysisid, modality, 1, 0, -1, "", i, "Data NOT found for this step (subject level). Stopping search");
                stepIsInvalid = true;
                break;
            }
        }
        /* otherwise, check the study for the protocol(s) */
        else {
            QString sqlstring;
            dlog << QString("   Checking the study [%1] for the protocol (%2)").arg(studyid).arg(protocols);
            /* get a list of series satisfying the search criteria, if it exists */
            sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
            if (imagetypes != "''") {
                sqlstring += " and image_type in (" + imagetypes + ")";
            }
            if (validComparisonStr)
                sqlstring += QString(" and ((numfiles %1 %2) or (dimT %1 %2))").arg(comparison).arg(num);

            q.prepare(sqlstring);
            q.bindValue(":studyid", studyid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                dlog << QString("   Data found for step [%1] - protocol [%2] (study level)").arg(i).arg(protocol);
                RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, -1, "", i, "Data found for this step (study level)");
            }
            else {
                dlog << QString("   Data NOT found for step [%1] - protocol [%2] (study level). Stopping search for this step").arg(i).arg(protocol);
                RecordDataDownload(datadownloadid, analysisid, modality, 1, 0, -1, "", i, "Data NOT found for this step (study level). Stopping search");
                stepIsInvalid = true;
                break;
            }
        }
    }
    dlog << "\n ********** Done checking data steps **********";

    /* if it's a subject level dependency, but there is no data found, we don't want to copy any dependencies */
    if ((stepIsInvalid) && (deplevel == "subject")) {
        dlog << " ********** One of the required steps was invalid because no data was found based on the search criteria. (This was a subject-level dependency) No data will be downloaded. **********";
        datalog = dlog.join("\n");
        return false;
    }

    /* if there is a dependency, don't worry about the previous checks */
    if (pipelinedep != -1)
        stepIsInvalid = false;

    /* any bad data items, then the data spec didn't work out for this subject */
    if (stepIsInvalid) {
        dlog << " ********** One of the required steps was invalid because no data was found for the search criteria. No data will be downloaded.";
        datalog = dlog.join("\n");
        return false;
    }

    /* ------ end checking the data steps --------------------------------------
        if we get to here, the data spec is valid for this study
        so we can assume all of the data exists, and start copying it
       ------------------------------------------------------------------------- */

    n->InsertAnalysisEvent(analysisid, pipelineid, p.version, studyid, "analysiscopydata", "Started copying data to [<tt>" + analysispath + "</tt>]");

    /* if global BIDS export, do that as one step */
    /* TBD */

    dlog << "\n ********** Required data for this study exists. Beginning data copy **********\n";
    /* go through list of data search criteria again to do the actual copying */
    for (int i = 0; i < datadef.size(); i++) {
        QString criteria = datadef[i].criteria;
		//QString type = datadef[i].type;
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
            for(int i=0; i<types.size(); i++)
                types[i] = types[i].replace("\\", "\\\\");
            imagetypes = "'" + types.join("','") + "'";
        }
        else
            imagetypes = "'" + imagetype.replace("\\", "\\\\") + "'";

        /* SQL comparison string */
        QString comparison;
        int num = 0;
        bool validComparisonStr = false;
        if (n->GetSQLComparison(numboldreps, comparison, num))
            validComparisonStr = true;

        dlog << QString("Downloading data for step [%1]  NumBoldReps [%2]  Comparison [%3]  num [%4]  valid [%5]").arg(i).arg(numboldreps).arg(comparison).arg(num).arg(validComparisonStr);

        /* check to see if we should even run this step */
        if (!enabled) {
            dlog << n->WriteLog("   This data step [" + protocol + "] is not enabled. Data step will not be downloaded.");
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
            dlog << n->WriteLog("   Error - Modality [" + modality + "] not found. Data step will not be downloaded.");
            RecordDataDownload(datadownloadid, analysisid, modality, 1, -1, -1, "", i, "Invalid modality. Not downloading.");
            continue;
        }

        QString sqlstring;
        /* get a list of series satisfying the search criteria, if it exists */
        if (level == "study") {
            dlog << "   Getting list of series that match this (STUDY-level) data step -  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetype + "]";

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
                sqlstring += QString(" and ((numfiles %1 %2) or (dimT %1 %2)) order by series_num asc").arg(comparison).arg(num);
            else
                sqlstring += " order by series_num asc";

            q.prepare(sqlstring);
            q.bindValue(":studyid", studyid);
        }
        else {
            dlog << "   Getting list of series matching this (SUBJECT-level) data step -  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetype + "]";

            if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
                /* find the data from the same subject and modality that has the nearest (in time) matching scan */
                dlog << n->WriteLog("   Searching for subject-level data nearest-in-time.");

                /* get the otherstudyid */
                QSqlQuery q2;
                QString sqlstringA;
                sqlstringA = QString("SELECT `studies`.study_id, `studies`.study_num FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

                if (imagetypes != "''")
                    sqlstringA += QString("and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

                sqlstringA += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '%2' ) ) LIMIT 1").arg(modality).arg(s.dateTime().toString("yyyy-MM-dd hh:mm:ss"));
                q2.prepare(sqlstringA);
                q2.bindValue(":subjectid", s.subjectRowID());

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

                dlog << n->WriteLog("   Preparing (subject-level) data search:  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetypes + "]");

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
                q.bindValue(":subjectid", s.subjectRowID());
                q.bindValue(":otherstudyid", otherstudyid);
            }
            else if (assoctype == "all") {
                dlog << n->WriteLog("   Searching for all subject-level data.");
                sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

                if (imagetypes != "''")
                    sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

                if (validComparisonStr)
                    sqlstring += QString(" and ((numfiles %1 %2) or (dimT %1 %2))").arg(comparison).arg(num);

                q.prepare(sqlstring);
                q.bindValue(":subjectid", s.subjectRowID());
            }
            else {
                /* find the data from the same subject and modality that has the same study_type */
                dlog << n->WriteLog("   Searching for subject-level data with same study type.");

                sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

                if (imagetypes != "''")
                    sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

                if (validComparisonStr)
                    sqlstring += QString(" and ((numfiles %1 %2) or (dimT %1 %2))").arg(comparison).arg(num);

                sqlstring += " and `studies`.study_type = :studytype";

                q.prepare(sqlstring);
                q.bindValue(":subjectid", s.subjectRowID());
                q.bindValue(":studytype", studytype);
            }
        }

        int newseriesnum = 1;
        QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {

            dlog << n->WriteLog(QString("   Found [%1] matching subject-level series").arg(q.size()));
            /* in theory, data for this analysis exists for this study, so lets now create the analysis directory */
            QString m;
            if (!n->MakePath(analysispath + "/pipeline", m)) {
                dlog << n->WriteLog("   Error: unable to create directory [" + analysispath + "/pipeline] - C");
                UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + analysispath + "/pipeline]", -1, -1, "", "", false, true, 0, 0);
                continue;
            }

            while (q.next()) {
                int localstudynum;
                n->WriteLog(QString("NumDownloaded [%1]").arg(numdownloaded));
                int seriesid = q.value(modality+"series_id").toInt();
                int seriesnum = q.value("series_num").toInt();
                QString seriesdesc = q.value("series_desc").toString();
                QString seriesprotocol = q.value("series_protocol").toString();
                QString seriesdatetime = q.value("series_datetime").toString();
                QString datatype = q.value("data_type").toString();
                QString phaseplane = q.value("phaseencodedir").toString();
                int phasepositive;
                if (q.value("PhaseEncodingDirectionPositive").isNull())
                    phasepositive = -1;
                else
                    phasepositive = q.value("PhaseEncodingDirectionPositive").toInt();

                if (seriesdesc == "")
                    seriesdesc = seriesprotocol;

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

                dlog << n->WriteLog(QString("   Beginning to copy data -  protocol [%1]  seriesnum [%2]  seriesdatetime [%3]").arg(seriesdesc).arg(seriesnum).arg(seriesdatetime));

                QString behoutdir;
                QString indir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum).arg(datatype);
                QString behindir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum);

                dlog << n->WriteLog("   Copying imaging data from [" + indir + "]");
                if (behformat != "none")
                    dlog << n->WriteLog("   Copying behavioral data from [" + behindir + "]");

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

                if (usephasedir) {
                    dlog << QString("   PhasePlane [" + phaseplane + "] PhasePositive [%1]").arg(phasepositive);

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
                if (!n->MakePath(newanalysispath, m)) {
                    dlog << n->WriteLog("   Error: unable to create directory [" + newanalysispath + "] message [" + m + "]");
                    UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + newanalysispath + "]", 0, -1, "", "", false, true, -1, -1);
                }
                else
                    dlog << n->WriteLog("   Created imaging data output directory [" + newanalysispath + "]");

                n->SystemCommand("chmod -Rf 777 " + newanalysispath, true, true);

                /* output the correct file type */
                if ((dataformat == "dicom") || ((datatype != "dicom") && (datatype != "parrec"))) {
                    QString systemstring;
                    if (p.dataCopyMethod == "scp")
                        systemstring = QString("scp %1/* %2\\@%3:%4").arg(indir).arg(n->cfg["clusteruser"]).arg(p.submitHost).arg(newanalysispath);
                    else
                        systemstring = QString("cp -v %1/* %2").arg(indir).arg(newanalysispath);
                    n->WriteLog(n->SystemCommand(systemstring, true, true));

                    dlog << QString("   Done copying imaging data from [%1] to [%2]").arg(indir).arg(newanalysispath);

					qint64 c;
					qint64 b;
                    n->GetDirSizeAndFileCount(newanalysispath, c, b, true);
                    dlog << n->WriteLog(QString("   Imaging data output directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(newanalysispath).arg(c).arg(b));
                }
                else {
                    QString tmpdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
                    QString m;
                    if (!n->MakePath(tmpdir, m)) {
                        dlog << n->WriteLog("   Error: unable to create temp directory [" + tmpdir + "] message [" + m + "] for DICOM conversion");
                        UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + newanalysispath + "]", 0, -1, "", "", false, true, -1, -1);
                    }
                    else
                        dlog << n->WriteLog("   Created temp directory [" + tmpdir + "] for DICOM conversion");
                    int numfilesconv(0);
                    int numfilesrenamed(0);
                    n->ConvertDicom(dataformat, indir, tmpdir, gzip, uid, QString("%1").arg(localstudynum), QString("%1").arg(seriesnum), datatype, numfilesconv, numfilesrenamed, m);

                    QString systemstring;
                    if (p.dataCopyMethod == "scp")
                        systemstring = QString("scp %1/* %2\\@%3:%4").arg(tmpdir).arg(n->cfg["clusteruser"]).arg(p.submitHost).arg(newanalysispath);
                    else
                        systemstring = QString("cp -v %1/* %2").arg(tmpdir).arg(newanalysispath);
                    n->WriteLog(n->SystemCommand(systemstring, true, true));

                    dlog << n->WriteLog("   Removing temp directory ["+tmpdir+"]");
                    if (!n->RemoveDir(tmpdir,m))
                        dlog << n->WriteLog("   Error: unable to remove temp directory [" + tmpdir + "] error [" + m + "]");

                    dlog << QString("   Done copying converted imaging data from [%1] via [%2] to [%3]").arg(indir).arg(tmpdir).arg(newanalysispath);

					qint64 c;
					qint64 b;
                    n->GetDirSizeAndFileCount(newanalysispath, c, b, true);
                    dlog << n->WriteLog(QString("   Imaging output directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(newanalysispath).arg(c).arg(b));
                }

                RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, seriesid, newanalysispath, i, "Data downloaded");
                dlog << QString("   Data for step [%1] downloaded to [%2]").arg(i).arg(newanalysispath);
                numdownloaded++;

                /* copy the beh data */
                if (behformat != "behnone") {
                    dlog << "   Copying behavioral data";
                    QString m;
                    if (!n->MakePath(behoutdir, m)) {
                        dlog << n->WriteLog("   Error: unable to create behavioral output directory [" + behoutdir + "] message [" + m + "] - F");
                        UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + newanalysispath + "]", 0, -1, "", "", false, true, -1, -1);
                    }
                    else
                        dlog << n->WriteLog("   Created behavioral output directory [" + behoutdir + "] - F");
                    QString systemstring = "cp -Rv " + behindir + "/* " + behoutdir;
                    n->WriteLog(n->SystemCommand(systemstring, true, true));

                    n->SystemCommand("chmod -Rf 777 " + behoutdir, true, true);
                    dlog << QString("   Done copying behavioral data from [%1] to [%2]").arg(behindir).arg(behoutdir);

					qint64 c;
					qint64 b;
                    n->GetDirSizeAndFileCount(behoutdir, c, b, true);
                    dlog << n->WriteLog(QString("   Behavioral output directory now contains [%1] files, and is [%2] bytes in size.").arg(c).arg(b));
                }

                /* give full read/write permissions to everyone */
                n->SystemCommand("chmod -Rf 777 " + newanalysispath, true, true);

                dlog << n->WriteLog("   Done writing data to [" + newanalysispath + "]");
            }
        }
        else {
            dlog << "   Error: found no matching subject-level [" + protocol + "] series. SQL: [" + sql + "]";
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
bool modulePipeline::UpdateAnalysisStatus(qint64 analysisid, QString status, QString statusmsg, int jobid, int numseries, QString datalog, QString datatable, bool currentStartDate, bool currentEndDate, int supplementFlag, int rerunFlag) {
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
    if (supplementFlag >= 0)
        varsToSet << "analysis_runsupplement = :supplementflag";
    if (rerunFlag >= 0)
        varsToSet << "analysis_rerunresults = :rerunflag";
    if (numseries >= 0)
        varsToSet << "analysis_numseries = :numseries";

    /* return false if there were no variables to update */
    if (varsToSet.size() < 1) {
        n->WriteLog("Unable to update analysis status. No variables set to update");
        return false;
    }
    //else {
    //	n->WriteLog("Updating analysis [" + varsToSet.join(" | ") + "]");
    //}

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
    if (supplementFlag >= 0)
        q.bindValue(":supplementflag", supplementFlag);
    if (rerunFlag >= 0)
        q.bindValue(":rerunflag", rerunFlag);

    q.bindValue(":analysisid", analysisid);

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

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
    q.prepare("select * from analysis where study_id = :sid and pipeline_id = :pipelinedep and (analysis_isbad <> 1 or analysis_isbad is null)");
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
void modulePipeline::SetPipelineStopped(int pid, QString msg) {
    QSqlQuery q;
    q.prepare("update pipelines set pipeline_status = 'stopped', pipeline_statusmessage = :msg, pipeline_lastfinish = now() where pipeline_id = :pid");
    q.bindValue(":pid", pid);
    q.bindValue(":msg", msg);
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
void modulePipeline::SetPipelineRunning(int pid, QString msg) {

    QSqlQuery q;
    q.prepare("update pipelines set pipeline_status = 'running', pipeline_statusmessage = :msg, pipeline_laststart = now() where pipeline_id = :pid");
    q.bindValue(":pid", pid);
    q.bindValue(":msg", msg);
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
/* --------- GetPipelineStatus ------------------------------ */
/* ---------------------------------------------------------- */
QString modulePipeline::GetPipelineStatus(int pipelineid) {
    QString status;
    QSqlQuery q;
    q.prepare("select pipeline_status from pipelines where pipeline_id = :pid");
    q.bindValue(":pid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        status = q.value("pipeline_status").toString();
    }
    return status;
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
            if (rec.logged) {
                if (rec.supplement)
                    rec.logfile = QString("SupplementStep%1").arg(rec.order);
                else
                    rec.logfile = QString("Step%1").arg(rec.order);
            }
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
QString modulePipeline::FormatCommand(int pipelineid, QString clusteranalysispath, QString command, QString analysispath, qint64 analysisid, QString uid, int studynum, QString studydatetime, QString pipelinename, QString workingdir, QString description) {

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
bool modulePipeline::CreateClusterJobFile(QString jobfilename, QString clustertype, qint64 analysisid, QString uid, int studynum, QString analysispath, bool usetmpdir, QString tmpdir, QString studydatetime, QString pipelinename, int pipelineid, QString resultscript, int maxwalltime,  QList<pipelineStep> steps, bool runsupplement) {

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

    //n->WriteLog(QString("ReRunResults: [%1]").arg(rerunresults));

    QString jobfile;
    QString clusteranalysispath = analysispath;
    QString localanalysispath = QString("%1/%2-%3").arg(tmpdir).arg(pipelinename).arg(analysisid);

    n->WriteLog("Cluster analysis path [" + analysispath + "]");
    n->WriteLog("Local analysis path (temp directory) [" + localanalysispath + "]");

    /* check if any of the variables might be blank */
    if (analysispath == "") {
        n->WriteLog("Within CreateClusterJobFile(), analysispath was blank");
        return false;
    }
    if (localanalysispath == "") {
        n->WriteLog("Within CreateClusterJobFile(), localanalysispath was blank");
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

    /* add the library path SO the cluster version of the nidb executable to run, and diagnostic echos */
    jobfile += "LD_LIBRARY_PATH=" + n->cfg["clusternidbpath"] + "/; export LD_LIBRARY_PATH;\n";
    jobfile += "echo Hostname: `hostname`\n";
    jobfile += "echo Username: `whoami`\n\n";

    /* do the first checkin from the cluster */
    if ((resultscript != "") && (rerunresults))
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s startedrerun -m 'Cluster processing started'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
    else if (runsupplement)
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s startedsupplement -m 'Supplement processing started'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
    else
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Cluster processing started'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);

    jobfile += "cd "+analysispath+";\n";
    if (usetmpdir) {
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Beginning data copy to /tmp'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
        jobfile += "mkdir -pv " + localanalysispath + "\n";
        jobfile += "cp -Rv " + analysispath + "/* " + localanalysispath + "/\n";
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Done copying data to /tmp'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
    }

    QDir::setCurrent(clusteranalysispath);
    if (!rerunresults) {

        /* get the number of regular vs supplement steps */
        int regSize(0), supSize(0);
        for (int i=0; i<steps.size(); i++) {
            if (steps[i].supplement)
                supSize++;
            else
                regSize++;
        }

        /* go through list of data search criteria */
        for (int i=0; i<steps.size(); i++) {
            int order = steps[i].order;
            bool issupplement = steps[i].supplement;
            QString command = steps[i].command;
            QString workingdir = steps[i].workingDir;
            QString description = steps[i].description;
            bool logged = steps[i].logged;
            bool enabled = steps[i].enabled;
            QString logfile = steps[i].logfile;
            bool checkedin = true;
            bool profile = false;

            /* only run supplement commands if we are running a supplement, and vice-versa */
            if ((runsupplement && !issupplement) || (!runsupplement && issupplement)) {
                //n->WriteLog(QString("runsupplement [%1]  issupplement [%2]").arg(runsupplement).arg(issupplement));
                continue;
            }

            QString supplement;
            int size(0);
            if (issupplement) {
                supplement = "supplement-";
                size = supSize;
            }
            else
                size = regSize;

            /* check for flags */
            if ((command.contains("{NOLOG}")) || (description.contains("{NOLOG}")))
                logged = false;
            if ((command.contains("{NOCHECKIN}")) || (description.contains("{NOCHECKIN}")))
                checkedin = false;
            if ((command.contains("{PROFILE}")) || (description.contains("{PROFILE}")))
                profile = true;

            /* format the command (replace pipeline variables, etc) */
            if (usetmpdir)
                command = FormatCommand(pipelineid, clusteranalysispath, command, localanalysispath, analysisid, uid, studynum, studydatetime, pipelinename, workingdir, description);
            else
                command = FormatCommand(pipelineid, clusteranalysispath, command, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, workingdir, description);

            /* add the step checkin */
            if (checkedin) {
                QString cleandesc = description;
                cleandesc.replace("'","").replace("\"","");
                jobfile += QString("\n%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'processing %3step %4 of %5'").arg(n->cfg["clusternidbpath"]).arg(analysisid).arg(supplement).arg(order).arg(size);
                //jobfile += QString("\n%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'processing %3step %4 of %5' '%6'").arg(n->cfg["clusternidbpath"]).arg(analysisid).arg(supplement).arg(order).arg(steps.size()).arg(cleandesc);
                jobfile += "\n# " + description + "\necho Running " + command + "\n";
            }

            /* prepend with 'time' if the neither NOLOG nor NOCHECKIN are specified */
            if (profile && logged && checkedin)
                command = "/usr/bin/time -v " + command;

            /* write to a log file if logging is requested */
            if (logged)
                command += QString(" >> " + analysispath + "/pipeline/" + logfile);
                //command += QString(" >> " + analysispath + "/pipeline/" + supplement + "step%1.log 2>&1").arg(order);

            if (workingdir != "")
                jobfile += "cd " + workingdir + ";\n";

            /* disable the step if necessary */
            if (!enabled)
                jobfile += "# ";

            jobfile += command + "\n";
        }
    }
    if (usetmpdir) {
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Copying data from temp dir'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
        jobfile += "cp -Ruv " + localanalysispath + "/* " + analysispath + "/\n";
        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Deleting temp dir'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
        jobfile += "rm --preserve-root -rv " + localanalysispath + "\n";
    }

    if ((resultscript != "") && (rerunresults)) {
        /* add on the result script command */
        QString resultcommand = FormatCommand(pipelineid, clusteranalysispath, resultscript, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, "", "");
        resultcommand += " > " + analysispath + "/pipeline/stepResults.log 2>&1";
        jobfile += QString("\n%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'Processing result script'\n# Running result script\necho Running %3\n").arg(n->cfg["clusternidbpath"]).arg(analysisid).arg(resultcommand);
        jobfile += resultcommand + "\n";

        jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s completererun -m 'Results re-run complete'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
        jobfile += "chmod -Rf 777 " + analysispath;
    }
    else {
        /* run the results import script */
        QString resultcommand = FormatCommand(pipelineid, clusteranalysispath, resultscript, analysispath, analysisid, uid, studynum, studydatetime, pipelinename, "", "");
        resultcommand += " > " + analysispath + "/pipeline/stepResults.log 2>&1";
        jobfile += QString("\n%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'Processing result script'\n# Running result script\necho Running %3\n").arg(n->cfg["clusternidbpath"]).arg(analysisid).arg(resultcommand);
        jobfile += resultcommand + "\n";

        /* clean up and log everything */
        jobfile += "chmod -Rf 777 " + analysispath + "\n";
        if (runsupplement) {
            jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'Updating analysis files'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u updateanalysis -a %2\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'Checking for completed files'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u checkcompleteanalysis -a %2\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s completesupplement -m 'Supplement processing complete'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
        }
        else {
            jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'Updating analysis files'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u updateanalysis -a %2\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s processing -m 'Checking for completed files'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u checkcompleteanalysis -a %2\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
            jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s complete -m 'Cluster processing complete'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
        }
        jobfile += "chmod -Rf 777 " + analysispath;
    }

    /* write out the file */
    QFile f(jobfilename);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QTextStream fs(&f);
        fs << jobfile;
        f.close();
        //n->WriteLog("Within CreateClusterJobFile() - wrote the file ["+jobfilename+"]");
        return true;
    }
    else {
        n->WriteLog("Within CreateClusterJobFile() - Could not write the file [" + jobfilename + "]");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetStudyToDoList ------------------------------- */
/* ---------------------------------------------------------- */
QList<int> modulePipeline::GetStudyToDoList(int pipelineid, QString modality, int depend, QString groupids, qint64 &runnum) {

    QSqlQuery q;

    int numInitial(0);
    int numRerun(0);
    int numSupplement(0);
    QList<int> list;
    QStringList rerunStudyList;
    QStringList supplementStudyList;
    QStringList normalStudyList;
    QString m;

    pipeline p(pipelineid, n);
    bool debug = p.debug;

    /* step 1 - get only the studies that need to have their results rerun */
    int addedStudies = 0;
    q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_rerunresults = 1 and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null)) order by study_datetime desc");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            int studyid = q.value("study_id").toInt();
            n->WriteLog(QString("Found study (results rerun) [%1]").arg(studyid));
            list.append(studyid);
            rerunStudyList << QString("%1%2").arg(q.value("uid").toString()).arg(q.value("study_num").toString());
            addedStudies++;
        }
        if (debug)
            InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", QString("Found %1 studies to have results rerun [" + rerunStudyList.join(", ") + "]").arg(rerunStudyList.size()));
    }
    numRerun = addedStudies;

    /* step 2 - get only the studies that need to have their supplements run */
    addedStudies = 0;
    q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_runsupplement = 1 and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null)) order by study_datetime desc");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            int studyid = q.value("study_id").toInt();
            n->WriteLog(QString("Found study (results rerun) [%1]").arg(studyid));
            list.append(studyid);
            supplementStudyList << QString("%1%2").arg(q.value("uid").toString()).arg(q.value("study_num").toString());
            addedStudies++;
        }
        if (debug)
            InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", QString("Found %1 studies to have supplement scripts run [" + supplementStudyList.join(", ") + "]").arg(supplementStudyList.size()));
    }
    numSupplement = addedStudies;

    /* step 3 - get list of studies which do not have an entry in the analysis table for this pipeline */
    if (depend >= 0) {
        /* there is a dependency. need to check if ANY of the subject's studies have the dependency... */
        n->WriteLog(QString("This pipeline [%1] depends on [%2]").arg(pipelineid).arg(depend));

        /* step 3a) get list of studies that have completed the dependency */
        QList<int> list;
        //QStringList studylist;
        QSqlQuery q2;
        q2.prepare("select a.study_id, c.uid, a.study_num from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.subject_id in (select a.subject_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_id in (select study_id from analysis where pipeline_id = :depend and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null)) and (a.isactive = 1 or a.isactive is null))");
        q2.bindValue(":depend", depend);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            while (q2.next()) {
                list.append(q2.value("study_id").toInt());
                //studylist << QString("%1%2").arg(q2.value("uid").toString()).arg(q2.value("study_num").toString());
                normalStudyList << QString("%1%2").arg(q2.value("uid").toString()).arg(q2.value("study_num").toString());
            }
        }
        QString studyidlist = n->JoinIntArray(list, ", ");
        //QString studyliststr = studylist.join(", ");

        if (studyidlist == "")
            studyidlist = "-1";

        if ((n->cfg["debug"].toInt()) || (debug)) {
            if (groupids != "") {
                QStringList gids = groupids.split(",");
                foreach (QString gid, gids) {
                    n->WriteLog(n->GetGroupListing(gid.toInt()), 250);
                }
            }
        }

        /* step 3b) then find all studies that have completed and have not already been processed by this pipeline */
        if (groupids == "") {
            /* NO groupids */
            q.prepare("select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and study_id in (" + studyidlist + ") and (study_datetime < date_sub(now(), interval 6 hour)) order by study_datetime desc");
            q.bindValue(":pipelineid", pipelineid);
            m = n->WriteLog(QString("Pipeline has a dependency [%1] and NO groups. Found %2 studies that have completed the dependency").arg(depend).arg(list.size()));
            InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", m);
        }
        else {
            /* with groupids */
            q.prepare("select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and a.study_id in (" + studyidlist + ") and (a.study_datetime < date_sub(now(), interval 6 hour)) and b.group_id in (" + groupids + ") order by a.study_datetime desc");
            q.bindValue(":pipelineid", pipelineid);
            m = n->WriteLog(QString("Pipeline HAS a dependency [%1] and group(s) [%2]. Found %3 studies that have completed the dependency and are within the group(s)").arg(depend).arg(groupids).arg(list.size()));
            InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", m);
        }
    }
    else {
        /* NO dependency */
        if (groupids == "") {
            /* NO groupids */
            q.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and (a.study_datetime < date_sub(now(), interval 6 hour)) and a.study_modality = :modality and c.isactive = 1 order by a.study_datetime desc");
            q.bindValue(":pipelineid", pipelineid);
            q.bindValue(":modality", modality);
            m = n->WriteLog("Pipeline has NO dependency and NO groups");
            InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", m);
        }
        else {
            /* WITH groupids */
            q.prepare("SELECT a.study_id FROM studies a left join group_data b on a.study_id = b.data_id left join enrollment c on a.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id WHERE a.study_id NOT IN (SELECT study_id FROM analysis WHERE pipeline_id = :pipelineid) AND ( a.study_datetime < DATE_SUB( NOW( ) , INTERVAL 6 hour )) AND a.study_modality = :modality and b.group_id in (" + groupids + ") and d.isactive = 1 ORDER BY a.study_datetime DESC");
            q.bindValue(":pipelineid", pipelineid);
            q.bindValue(":modality", modality);
            m = n->WriteLog(QString("Pipeline has NO dependency and HAS groups [%1]").arg(groupids));
            InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", m);
        }
    }

    /* run the query from the previous section to get the study details */
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
                //QString uidstudynum;
                //uidstudynum = QString("%1%2").arg(q2.value("uid").toString()).arg(q2.value("study_num").toInt());
                normalStudyList << QString("%1%2").arg(q2.value("uid").toString()).arg(q2.value("study_num").toString());
            }
            list.append(studyid);

            numInitial++;
        }
    }
    if (debug)
        InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", QString("Found %1 unprocessed studies that meet criteria [" + normalStudyList.join(", ") + "]").arg(normalStudyList.size()));

    m = n->WriteLog(QString("Found [%1] total studies that met criteria: [%2] initial match  [%3] rerun  [%4] supplement").arg(list.size()).arg(numInitial).arg(numRerun).arg(numSupplement));
    InsertPipelineEvent(pipelineid, runnum, -1, "getstudylist", m);

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

		if (modality == "") q.bindValue(":modality", QVariant(QMetaType::fromType<QString>())); else q.bindValue(":modality", analysisid);
		if (checked == -1) q.bindValue(":checked", QVariant(QMetaType::fromType<bool>())); else q.bindValue(":checked", checked);
		if (found == -1) q.bindValue(":found", QVariant(QMetaType::fromType<bool>())); else q.bindValue(":found", found);
		if (seriesid == -1) q.bindValue(":seriesid", QVariant(QMetaType::fromType<int>())); else q.bindValue(":seriesid", seriesid);
		if (downloadpath == "") q.bindValue(":downloadpath", QVariant(QMetaType::fromType<QString>())); else q.bindValue(":downloadpath", downloadpath);
		if (step == -1) q.bindValue(":step", QVariant(QMetaType::fromType<int>())); else q.bindValue(":step", step);
		if (msg.toInt() == -1) q.bindValue(":msg", QVariant(QMetaType::fromType<QString>())); else q.bindValue(":msg", msg);

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


/* ---------------------------------------------------------- */
/* --------- InsertPipelineEvent ---------------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::InsertPipelineEvent(int pipelineid, qint64 &runnum, qint64 analysisid, QString event, QString message) {

    /* possible events:
     *
        pipeline_started
        error_noqueue
        error_nosubmithost
        getdatasteps
        getpipelinesteps
        getstudylist
        maxjobs_reached
        analysis_exists
        analysis_runsupplement
        analysis_rerunresults
        analysis_checkdependency
        analysis_getdata
        analysis_createdir
        analysis_oktosubmit
        analysis_copyparent
        analysis_errorcreatepath
        submit_analysis
        error_submitanalysis
        pipeline_disabled
        pipeline_finished
    */

    QSqlQuery q;
    pipeline p(pipelineid, n);

    if (event == "pipeline_started") {
        q.prepare("select max(run_num) 'max' from pipeline_history where pipeline_id = :pipelineid");
        q.bindValue(":pipelineid", pipelineid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            runnum = q.value("max").toInt() + 1;
        }
        else {
            runnum = 0;
        }
    }

    /* do an insert */
	if (analysisid > 0) {
		q.prepare("insert into pipeline_history (run_num, pipeline_id, pipeline_version, analysis_id, pipeline_event, event_message) values (:runnum, :pipelineid, :version, :analysid, :event, :msg)");
		q.bindValue(":analysisid", analysisid);
	}
	else {
		q.prepare("insert into pipeline_history (run_num, pipeline_id, pipeline_version, pipeline_event, event_message) values (:runnum, :pipelineid, :version, :event, :msg)");
	}
	q.bindValue(":runnum", runnum);
	q.bindValue(":pipelineid", pipelineid);
	q.bindValue(":version", p.version);
	q.bindValue(":event", event);
    q.bindValue(":msg", message);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- ClearPipelineHistory --------------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::ClearPipelineHistory() {
    QSqlQuery q;

    /* delete all pipeline_history events older than 2 days */
    q.prepare("delete from pipeline_history where event_datetime < date_add(now(), interval -2 day)");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    n->WriteLog(QString("Deleted %1 rows older than two days").arg(q.numRowsAffected()));
}
