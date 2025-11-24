/* ------------------------------------------------------------------------------
  NIDB modulePipeline.cpp
  Copyright (C) 2004 - 2025
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
    n->Log("Starting the pipeline module...");

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
    int numPipelines = q.size();
    if (numPipelines < 1) {
        n->Log("No pipelines need to be run. Exiting module.");
        SetPipelineProcessStatus("complete",0,0);
        return false;
    }
    else {
        n->Log(QString("Found %1 active pipelines").arg(numPipelines));
    }

    /* get the list of pipelines to be run */
    int i = 0;
    while (q.next()) {

//pipeline_loop: /* first time ever using goto */

        i++;
        double percent = (static_cast<double>(i)/static_cast<double>(numPipelines))*100.0;
        //QString percentStr = QString::number(percent, 'f', 1);

        n->ModuleRunningCheckIn();

        int pipelineid = q.value("pipeline_id").toInt();

        qint64 runnum = 0;
        /* check if the pipeline is valid */
        pipeline p(pipelineid, n);
        if (!p.isValid) {
            m = n->Log(QString("Error starting pipeline [%1]. Pipeline was not valid [" + p.msg + "]").arg(p.name), __FUNCTION__);
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineStarted", m);
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline stopped prematurely due to error");
            continue;
        }
        else {
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineStarted", QString("Pipeline '%1' started").arg(p.name));
        }

        /* get analysis directory root */
        QString analysisdir;
        analysisdir = GetAnalysisLocalPath(p.dirStructure);

        n->Log(QString("===== [%1%] Working on %2 (Submits to cluster %3@%4 queue %5 through host %6@%7 =====").arg(percent, 0, 'f', 1).arg(p.name).arg(p.clusterUser).arg(p.clusterType).arg(p.clusterQueue).arg(p.clusterSubmitHostUser).arg(p.clusterSubmitHost));

        SetPipelineProcessStatus("running",pipelineid,0);

        /* mark the pipeline as having been checked */
        QSqlQuery q2;
        q2.prepare("update pipelines set pipeline_lastcheck = now() where pipeline_id = :pid");
        q2.bindValue(":pid", pipelineid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

        /* check if the pipeline's queue is valid */
        if (p.clusterQueue == "") {
            m = n->Log(QString("[%1] No queue specified").arg(p.name), __FUNCTION__);
            RecordPipelineEvent(pipelineid, runnum, -1, "errorNoQueue", m);
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }

        /* check if the submit host is valid */
        if (p.clusterSubmitHost == "") {
            m = n->Log(QString("[%1] No submit host specified").arg(p.name), __FUNCTION__);
            RecordPipelineEvent(pipelineid, runnum, -1, "errorNoSubmitHost", m);
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }

        /* check if there is enough space on the target drive */
        QStorageInfo storage = QStorageInfo(analysisdir);
        double percentFree = (static_cast<double>(storage.bytesAvailable())/static_cast<double>(storage.bytesTotal()))*100.0;
        if (percentFree < 1.0) {
            m = n->Log(QString("[%1] Less than 1% free space on target disk").arg(p.name), __FUNCTION__);
            RecordPipelineEvent(pipelineid, runnum, -1, "errorNotEnoughSpace", m);
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }

        /* check if the pipeline is running, if so, go on to the next one */
        QString status = GetPipelineStatus(pipelineid);
        if (status == "running") {
            n->Log(QString("[%1] pipeline is already running").arg(p.name), __FUNCTION__);
            continue;
        }

        /* update the pipeline status */
        SetPipelineRunning(pipelineid, "Submitting jobs");

        /* get the data steps */
        QList<dataDefinitionStep> dataSteps;
        if (p.level != 0) {
            if ((p.level == 1) || ((p.level == 2) && (p.parentIDs.size() < 1))) {
                dataSteps = GetPipelineDataDef(pipelineid, p.version);

                /* if there is no data definition and no dependency */
                if ((dataSteps.size() < 1) && (p.parentIDs.size() < 1)) {
                    m = n->Log(QString("[%1] pipeline has no data items. Skipping pipeline.").arg(p.name), __FUNCTION__);
                    RecordPipelineEvent(pipelineid, runnum, -1, "errorNoDataSteps", m);
                    RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline stopped prematurely due to error");
                    SetPipelineStopped(pipelineid, m);
                    continue;
                }
                else
                    RecordPipelineEvent(pipelineid, runnum, -1, "getDataSteps", QString("Pipeline contains %1 data items").arg(dataSteps.size()));
            }
        }

        /* get the pipeline steps (the script) */
        QList<pipelineStep> steps = GetPipelineSteps(pipelineid, p.version);
        if (steps.size() < 1) {
            m = n->Log(QString("[%1] Pipeline has no script commands. Skipping pipeline.").arg(p.name), __FUNCTION__);
            RecordPipelineEvent(pipelineid, runnum, -1, "errorNoPipelineSteps", m);
            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline stopped prematurely due to error");
            SetPipelineStopped(pipelineid, m);
            continue;
        }
        else
            RecordPipelineEvent(pipelineid, runnum, -1, "getPipelineSteps", QString("Pipeline contains %1 script commands").arg(steps.size()));

        /* ------------------------------ level 0 ----------------------------- */
        /* avoid using this, Level 0 is not maintained */
        /* -------------------------------------------------------------------- */
        if (p.level == 0) {
            /* check if this module should be running now or not */
            if (!n->ModuleCheckIfActive()) {
                n->Log(QString("[%1] Module disabled. Exiting").arg(p.name), __FUNCTION__);
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
                m = n->Log(QString("[%1] An analysis already exists for this one-shot level pipeline, exiting").arg(p.name), __FUNCTION__);
                SetPipelineStopped(pipelineid, m);
                continue;
            }

            /* create the analysis path */
            QString analysispath = QString("%1/%3").arg(p.pipelineRootDir).arg(p.name);
            n->Log(QString("[%1] Creating path [" + analysispath + "/pipeline]").arg(p.name), __FUNCTION__);
            //QString m;
            if (!MakePath(analysispath + "/pipeline", m)) {
                n->Log(QString("[%1] Error: unable to create directory [" + analysispath + "/pipeline] - A").arg(p.name), __FUNCTION__);
                //UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + analysispath + "/pipeline]", 0, -1, "", "", false, true, -1, -1);
                continue;
            }
            else
                n->Debug(QString("[%1] Created directory [" + analysispath + "/pipeline] - A").arg(p.name));

            /* this file will record any events during setup */
            QString setupLogFile = "/mount" + analysispath + "/pipeline/analysisSetup.log";
            AppendCustomLog(setupLogFile, "Beginning recording");
            n->Log(QString("[%1] Should have created this analysis setup log [" + setupLogFile + "]").arg(p.name), __FUNCTION__);

            /* insert a temporary row, to be updated later, in the analysis table as a placeholder
             * so that no other processes end up running it */
            q2.prepare("insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate, analysis_isbad) values (:pid, :version,'','','processing',now(),0)");
            q2.bindValue(":pid", pipelineid);
            q2.bindValue(":version", p.version);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            qint64 analysisRowID = q2.lastInsertId().toLongLong();

            /* create the cluster job file */
            QString jobFilePath = analysispath + "/sge.job";
            if (CreateClusterJobFile(jobFilePath, p.clusterType, p.clusterQueue, analysisRowID, "UID", 0, analysispath, p.useTmpDir, p.tmpDir, "", p.name, pipelineid, p.resultScript, p.clusterMaxWallTime, p.clusterNumCores, p.clusterMemory, steps, false)) {
                n->Log(QString("[%1] Created sge job submit file [" + jobFilePath + "]").arg(p.name), __FUNCTION__);
            }
            else {
                n->Log(QString("[%1] Error: unable to create sge job submit file [" + jobFilePath + "]").arg(p.name), __FUNCTION__);
                continue;
            }

            SystemCommand("chmod -Rf 777 " + analysispath, false);

            /* submit the cluster job file */
            QString qm, qresult;
            int jobid;
            if (n->SubmitClusterJob(jobFilePath, p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], p.clusterUser, p.clusterQueue, qm, jobid, qresult)) {
                n->Log(QString("[%1] Successfully submitted job to cluster [" + qresult + "]").arg(p.name), __FUNCTION__);
                UpdateAnalysisStatus(analysisRowID, "submitted", "Submitted to [" + p.clusterQueue + "]", jobid, -1, "", "", true, false, 0, 0);
            }
            else {
                n->Log(QString("[%1] Error submitting job to cluster [" + qresult + "]").arg(p.name), __FUNCTION__);
                UpdateAnalysisStatus(analysisRowID, "error", "Submit error [" + qm + "]", 0, -1, "", "", false, true, 0, 0);
            }
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

            jobsWereSubmitted = true;
        }
        // ================================= LEVEL 1 =================================
        else if (p.level == 1) {

            QString pipelinedirectory;
            //QSqlQuery q2;

            /* fix the directory if its not the default or blank */
            if (p.directory == "")
                pipelinedirectory = analysisdir;
            else
                pipelinedirectory = n->cfg["mountdir"] + pipelinedirectory;

            /* if there are multiple dependencies, we'll need to loop through all of them separately
             * NOPE... we don't allow multiple dependencies. Yet. */
            //for(int i=0; i<p.parentDependencyIDs.size(); i++) {

            int pipelinedep = -1;
            if (p.parentIDs.size() > 0)
                pipelinedep = p.parentIDs[0];

            QString modality;
            if (dataSteps.size() > 0)
                modality = dataSteps[0].modality;

            for (int i = 0; i < dataSteps.size(); i++) {
                if (dataSteps[i].flags.primaryProtocol) {
                    modality = dataSteps[i].modality;
                    break;
                }
            }

            /* get the list of studies which meet the criteria for being processed through the pipeline */
            QList<int> studyids = GetStudyToDoList(pipelineid, modality, pipelinedep, JoinIntArray(p.groupIDs, ","), runnum);

            int ii = 0;
            int numsubmitted = 0;
            foreach (int sid, studyids) {
                ii++;
                qint64 analysisRowID = -1;
                QStringList setuplog;

                SetPipelineProcessStatus("running", pipelineid, sid);

                numchecked++;

                /* get information about the study */
                study s(sid, n);
                if (!s.valid()) {
                    n->Log(QString("Study was not valid [" + s.msg() + "]. Maybe this study was deleted after this pipeline started?").arg(p.name), __FUNCTION__);
                    continue;
                }

                n->Log(QString(" ---------- Working on study [%2%3] (%4 of %5) for pipeline [%1] ----------").arg(p.name).arg(s.UID()).arg(s.studyNum()).arg(ii).arg(studyids.size()));

                /* check if the number of concurrent jobs is reached. the function also checks if this pipeline module is enabled */
                int filled;
                do {
                    filled = IsQueueFilled(pipelineid);

                    /* check if this pipeline is enabled */
                    if (!IsPipelineEnabled(pipelineid)) {
                        n->Log("Pipeline disabled. Stopping this pipeline at next iteration.", __FUNCTION__);
                        RecordPipelineEvent(pipelineid, runnum, -1, "pipelineDisabled", "Pipeline disabled");
                        SetPipelineStopped(pipelineid, "Pipeline disabled while running. Stopping at next iteration.");
                        break;
                    }

                    /* check if this module is still enabled */
                    if (!n->ModuleCheckIfActive()) {
                        n->Log("Module disabled. Exiting", __FUNCTION__);
                        RecordPipelineEvent(pipelineid, runnum, -1, "pipelineModuleDisabled", m);
                        SetPipelineStopped(pipelineid, "Pipeline module disabled while running. Stopping.");
                        SetPipelineProcessStatus("complete",0,0);
                        return 1;
                    }

                    /* otherwise check what to do, depending on how filled the queue is */
                    if (filled == 0)
                        break;

                    if (filled == 1) {
                        //m = n->WriteLog(QString("Concurrent analysis quota reached, waiting 15 seconds").arg(p.name), __FUNCTION__);
                        m = n->Log("Concurrent number of running jobs reached. Waiting 60 seconds to try again.", __FUNCTION__);
                        SetPipelineStatusMessage(pipelineid, m);
                        RecordPipelineEvent(pipelineid, runnum, -1, "maxJobsReached", m);
                        n->ModuleRunningCheckIn();
                        QThread::sleep(60); /* sleep for 15 seconds */
                        //SetPipelineStopped(pipelineid, "Pipeline max jobs reached. Normal stop.");
                        //goto pipeline_loop; /* first time ever using goto in 25 years of programming... */
                    }
                    if (filled == 2) {
                        return 1;
                    }
                } while (filled == 1);

                if (!IsPipelineEnabled(pipelineid)) {
                    RecordPipelineEvent(pipelineid, runnum, -1, "pipelineDisabled", "Pipeline disabled");
                    SetPipelineStopped(pipelineid, "Pipeline disabled while running. Stopping at this iteration.");
                    break;
                }

                /* get the analysis info, if an analysis already exists for this study */
                n->Log(QString("[%1] Getting analysis info for pipelineID [%2] studyID [%3] pipelineVersion [%4]").arg(p.name).arg(pipelineid).arg(sid).arg(p.version), __FUNCTION__);
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
                    analysispath = GetAnalysisLocalPath(p.dirStructure, p.name, s.UID(), s.studyNum());
                    n->Debug(QString("[%1] analysispath is [" + analysispath + "]").arg(p.name));

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
                            setuplog << n->Debug(QString("[%1] Dependency is a subject level (will match dep for same subject, any study)").arg(p.name));
                        }
                        else {
                            setuplog << n->Debug(QString("[%1] Dependency is a study level (will match dep for same subject, same study)").arg(p.name));

                            /* check the dependency and see if there's anything amiss about it */
                            QString depstatus = CheckDependency(sid, pipelinedep);
                            if (depstatus != "") {
                                UpdateAnalysisStatus(analysisRowID, "OddDependencyStatus", depstatus, -1, -1, setuplog.join("\n"), setuplog.join("\n"), false, false, -1, -1);
                                continue;
                            }
                        }
                    }
                    setuplog << n->Debug(QString("[%1] This analysis path is [" + analysispath + "]").arg(p.name), __FUNCTION__);

                    int numseriesdownloaded = 0;
                    /* get the data if we are not running a supplement, and not rerunning the results */
                    if ((!a.runSupplement) && (!a.rerunResults)) {
                        if (!GetData(sid, analysispath, s.UID(), analysisRowID, pipelineid, pipelinedep, p.depLevel, dataSteps, numseriesdownloaded, datalog)) {
                            n->Log(QString("[%1] GetData() returned false").arg(p.name), __FUNCTION__);
                        }
                        else {
                            m = QString("Downloaded %1 series for %2%3").arg(numseriesdownloaded).arg(s.UID()).arg(s.studyNum());
                            n->Debug(m, __FUNCTION__);
                            RecordPipelineEvent(pipelineid, runnum, -1, "analysisGetData", m);
                        }
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
                        setuplog << n->Log(QString("[%1] Study [%2%3] has [%4] matching series downloaded. Beginning analysis.").arg(p.name).arg(s.UID()).arg(s.studyNum()).arg(numseriesdownloaded), __FUNCTION__);
                    }
                    if (a.rerunResults) {
                        okToRun = true;
                        setuplog << n->Log(QString("[%1] Study [%2%3] set to have results rerun. Beginning analysis.").arg(p.name).arg(s.UID()).arg(s.studyNum()), __FUNCTION__);
                    }
                    if (a.runSupplement) {
                        okToRun = true;
                        setuplog << n->Log(QString("[%1] Study [%2%3] set to have supplement run. Beginning analysis.").arg(p.name).arg(s.UID()).arg(s.studyNum()), __FUNCTION__);
                    }
                    if ((pipelinedep != -1) && (p.depLevel == "study")) {
                        okToRun = true; // there is a parent pipeline and we're using the same study from the parent pipeline. may or may not have data to download
                        setuplog << n->Log(QString("[%1] Study [%2%3] has a study-level parent pipeline. Beginning analysis.").arg(p.name).arg(s.UID()).arg(s.studyNum()), __FUNCTION__);
                    }

                    /* one of the above criteria has been satisfied, so its ok to run the pipeline on this study and submit the cluster */
                    if (okToRun) {

                        QString dependencyname;
                        /* this analysis is new and has not been written to disk before, so
                         * the directory should not yet exist */
                        if ((!a.rerunResults) && (!a.runSupplement)) {
                            if (pipelinedep != -1) {
                                setuplog << n->Log(QString("[%1] This pipeline depends on [%1]").arg(p.name).arg(pipelinedep), __FUNCTION__);
                                q2.prepare("select pipeline_name from pipelines where pipeline_id = :pipelinedep");
                                q2.bindValue(":pipelinedep", pipelinedep);
                                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
                                if (q2.size() > 0) {
                                    q2.first();
                                    dependencyname = q2.value("pipeline_name").toString();
                                    setuplog << n->Debug(QString("[%1] Found [%2] rows for parent pipeline [%3]").arg(p.name).arg(q2.size()).arg(dependencyname));
                                }
                                else {
                                    m = QString("[%1] Parent pipeline [%2] does not exist!").arg(p.name).arg(pipelinedep);
                                    setuplog << n->Log(m, __FUNCTION__);
                                    RecordPipelineEvent(pipelineid, runnum, -1, "pipelineModuleDisabled", m);
                                    SetPipelineStopped(pipelineid, m);
                                    continue;
                                }
                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This pipeline is dependent on [" + dependencyname + "]");
                            }
                            else {
                                setuplog << n->Debug(QString("This pipeline does not depend on any pipelines [%1]").arg(pipelinedep));
                            }

                            //QString analysispath = p.pipelineRootDir + "/" + p.name;
                            //QString m;
                            if (!MakePath(analysispath + "/pipeline", m)) {
                                n->Log("Error: unable to create directory [" + analysispath + "/pipeline] - B", __FUNCTION__);
                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysiserror", "Unable to create directory [" + analysispath + "/pipeline]");
                                UpdateAnalysisStatus(analysisRowID, "error", "Unable to create directory [" + analysispath + "/pipeline]", 0, -1, "", "", false, true, -1, -1);
                                continue;
                            }
                            else
                                n->Log("Created directory [" + analysispath + "/pipeline] - B", __FUNCTION__);

                            n->Log(SystemCommand("chmod -Rf 777 " + analysispath + "/pipeline", false, true), __FUNCTION__);
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

                                setuplog << n->Log("Dependency path is [" + deppath + "]", __FUNCTION__);

                                QString fulldeppath = deppath;
                                QDir d(fulldeppath);
                                if (d.exists())
                                    setuplog << n->Debug("Full path to parent pipeline [" + fulldeppath + "] exists");
                                else
                                    setuplog << n->Log("Full path to parent pipeline [" + fulldeppath + "] does NOT exist", __FUNCTION__);

                                setuplog << n->Log(QString("This is a level [%1] pipeline. deplinktype [%2] depdir [%3]").arg(p.level).arg(p.depLinkType).arg(p.depDir), __FUNCTION__);

                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", QString("Parent pipeline (dependency) will be copied to directory [%1] using method [%2]").arg(p.depDir).arg(p.depLinkType));

                                /* copy any parent pipelines */
                                QString systemstring;
                                if (p.depLinkType == "hardlink") systemstring = "time cp -aulL "; /* L added to allow copying of softlinks */
                                else if (p.depLinkType == "softlink") systemstring = "time cp -aus ";
                                else if (p.depLinkType == "regularcopy") systemstring = "time cp -au ";
                                //if (p.depLinkType == "hardlink") systemstring = "rsync -aH "; /* try rsync to overcome cp bug in CentOS8 Stream (update, rsync doesn't create hardlinks with -H) */
                                //else if (p.depLinkType == "softlink") systemstring = "cp -aus ";
                                //else if (p.depLinkType == "regularcopy") systemstring = "cp -au ";
                                if (p.depDir == "subdir") {
                                    systemstring += deppath + " " + analysispath + "/";
                                    setuplog << n->Log("Parent pipeline will be copied to a subdir [" + systemstring + "]", __FUNCTION__);
                                }
                                else {
                                    systemstring += deppath + "/* " + analysispath + "/";
                                    setuplog << n->Log("Parent pipeline will be copied to the root dir [" + systemstring + "] ", __FUNCTION__);
                                }
                                setuplog << n->Log(SystemCommand(systemstring), __FUNCTION__);
                                //setuplog << n->WriteLog(SystemCommand(systemstring, true, false, false));

                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "Parent pipeline copied by running [" + systemstring + "]");
                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysisdependencyid", QString("%1").arg(dependencyanalysisid));

                                /* delete any log files and job files that came with the dependency */
                                setuplog << n->Log(SystemCommand(QString("rm --preserve-root %1/pipeline/* %1/origfiles.log %1/sge.job %1/slurm.job").arg(analysispath)), __FUNCTION__);

                                /* make sure the whole tree is writeable */
                                setuplog << n->Log(SystemCommand("chmod -Rf 777 " + analysispath, true, true), __FUNCTION__);
                            }
                            else {
                                setuplog << n->Log("This pipeline is not dependent on another pipeline", __FUNCTION__);
                                n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This pipeline does not depend on other pipelines");
                            }

                            /* this file will record any events during setup */
                            QString setupLogFile = analysispath + "/pipeline/analysisSetup.log";

                            /* now safe to write out the setuplog */
                            AppendCustomLog(setupLogFile, setuplog.join("\n"));
                            n->Debug("Should have created this analysis setup log [" + setupLogFile + "]");

                            /* however, if the setupLogFile does not exist, something is not writeable, and that is not good */
                            if (!QFile::exists(setupLogFile)) {
                                setuplog << n->Log("setupLogFile [" + setupLogFile + "] does not exist.", __FUNCTION__);
                            }
                        }
                        /* "realanalysispath" is now --> "clusteranalysispath" */
                        QString clusteranalysispath = analysispath;
                        clusteranalysispath.replace("/mount","");

                        /* create the cluster job file */
                        QString localJobFilePath;
                        QString clusterJobFilePath;
                        QString jobFilename;
                        if (a.rerunResults)
                            jobFilename = p.clusterType + "rerunresults.job";
                        else if (a.runSupplement)
                            jobFilename = p.clusterType + "-supplement.job";
                        else
                            jobFilename = p.clusterType + ".job";
                        localJobFilePath = analysispath + "/" + jobFilename;
                        clusterJobFilePath = clusteranalysispath + "/" + jobFilename;

                        if (CreateClusterJobFile(localJobFilePath, p.clusterType, p.clusterQueue, analysisRowID, s.UID(), s.studyNum(), clusteranalysispath, p.useTmpDir, p.tmpDir, s.dateTime().toString("yyyy-MM-dd hh:mm:ss"), p.name, pipelineid, p.resultScript, p.clusterMaxWallTime, p.clusterNumCores, p.clusterMemory, steps, a.runSupplement)) {
                            n->Debug("Created (local path) " + p.clusterType + " job submit file [" + localJobFilePath + "]");
                        }
                        else {
                            UpdateAnalysisStatus(analysisRowID, "error", "Error creating cluster job file", 0, -1, "", "", false, true, -1, -1);
                            continue;
                        }

                        SystemCommand("chmod -Rf 777 " + analysispath, true, true);

                        /* submit the cluster job file */
                        QString qm, qresult;
                        int jobid;
                        if (n->SubmitClusterJob(clusterJobFilePath, p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], p.clusterUser, p.clusterQueue, qm, jobid, qresult)) {
                            m = QString("Successfully submitted job %1 to %2 cluster. analysisRowID %3").arg(jobid).arg(p.clusterType).arg(analysisRowID);
                            n->Log(m, __FUNCTION__);
                            n->Debug("Job submission result [" + qresult + "]", __FUNCTION__);
                            UpdateAnalysisStatus(analysisRowID, "submitted", m, jobid, numseriesdownloaded, "", "", false, true, 0, 0);
                            n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmitted", qresult);
                            RecordPipelineEvent(pipelineid, runnum, -1, "submitAnalysis", m);
                        }
                        else {
                            m = QString("Error submitting job to %1 cluster. analysisRowID %2. Job submission message [%3]").arg(p.clusterType).arg(analysisRowID).arg(qresult);
                            n->Log(m, __FUNCTION__);
                            UpdateAnalysisStatus(analysisRowID, "error", "Submit error [" + qm + "]", 0, numseriesdownloaded, "", "", false, true, 0, 0);
                            n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissubmiterror", "Analysis submitted to cluster, but was rejected with errors [" + qm + "]");
                            RecordPipelineEvent(pipelineid, runnum, -1, "errorSubmitAnalysis", m);

                            submiterror = true;
                        }

                        numsubmitted++;
                        totalSubmitted++;
                        jobsWereSubmitted = true;

                        SetPipelineStatusMessage(pipelineid, QString("Submitted %1%2").arg(s.UID()).arg(s.studyNum()));

                        /* check if this module should be running now or not */
                        if (!n->ModuleCheckIfActive()) {
                            m = n->Log("Pipeline module disabled while running. Exiting", __FUNCTION__);
                            RecordPipelineEvent(pipelineid, runnum, -1, "pipelineModuleDisabled", m);
                            SetPipelineStopped(pipelineid, m);
                            SetPipelineProcessStatus("complete",0,0);
                            return 1;
                        }
                        /* wait 10 seconds before submitting the next job */
                        QThread::sleep(10);
                    }
                    else {
                        n->Log("Not Ok to submit job - No data found", __FUNCTION__);
                        /* update the analysis table with the datalog so people can check later on why something didn't process */
                        UpdateAnalysisStatus(analysisRowID, "", "", -1, -1, datalog, datalog, false, false, -1, -1);
                        n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysissetuperror", "No data found, 0 series returned from search");
                    }
                    n->Log(QString("Submitted [%1] jobs so far").arg(numsubmitted));

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
                    n->Log(QString("This analysis [%1] already has an entry in the analysis table").arg(analysisRowID), __FUNCTION__);
                    n->InsertAnalysisEvent(analysisRowID, pipelineid, p.version, sid, "analysismessage", "This analysis already has an entry in the analysis table");
                }

                if ((numchecked%1000) == 0)
                    n->Log(QString("[%1] studies checked").arg(numchecked), __FUNCTION__);

                /* check if this pipeline is still enabled */
                if (!IsPipelineEnabled(pipelineid)) {
                    RecordPipelineEvent(pipelineid, runnum, -1, "pipelineDisabled", "Pipeline disabled");
                    SetPipelineStopped(pipelineid, "Pipeline disabled while running. Normal stop.");
                    break;
                }
            }
        }
        /* ======================= LEVEL 2 ======================= */
        /* level 2 was once implemented but never used, and fell out of maintenance in the Perl
         * version, so it's been deprecated and removed in the C++ version of NiDB */
        else if (p.level == 2) {
            n->Log("Level 2 (group) pipelines are not yet implemented in the compiled version of NiDB", __FUNCTION__);
        }
        else {
            n->Log(QString("Invalid pipeline level [%1]").arg(p.level), __FUNCTION__);
        }

        n->Log(QString("Done with %1").arg(p.name));
        RecordPipelineEvent(pipelineid, runnum, -1, "pipelineFinished", "Pipeline finished submitting jobs");
        SetPipelineStopped(pipelineid, "Finished submitting jobs");

        /* check if there have been any completed analyses in the last 60 days */
        q2.prepare("select count(*) 'count' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = :pid and analysis_status in ('complete','error','pending','processing','submitted','notcompleted','') and abs(timestampdiff(day, now(), analysis_startdate)) < 60");
        q2.bindValue(":pid", pipelineid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.size() > 0) {
            q2.first();
            int count = q2.value("count").toInt();
            /* disable the pipeline if there are no analyses completed in the last 60 days */
            if (count < 1) {
                n->Log(QString("Disabling pipeline [%1]. No analyses completed within the past 60 days").arg(p.name));
                DisablePipeline(pipelineid);
            }
        }

    }

    SetPipelineProcessStatus("complete",0,0);

    if (jobsWereSubmitted) {
        n->Log(QString("Done with pipeline module. [%1] total jobs were submitted. jobsWereSubmitted [%2]").arg(totalSubmitted).arg(jobsWereSubmitted));
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
    bool exportBIDS(false);
    QString BIDSExportDir;
    QList<qint64> BIDSseriesids;
    QStringList BIDSmodalities;

    /* get pipeline information, for data copying preferences */
    pipeline p(pipelineid, n);
    if (!p.isValid) {
        n->Log("Pipeline was not valid: [" + p.msg + "]", __FUNCTION__);
        return false;
    }
    QString submithost;
    QString clusteruser;
    if (p.clusterSubmitHost == "") submithost = n->cfg["clustersubmithost"];
    else submithost = p.clusterSubmitHost;
    if (p.clusterUser == "") clusteruser = n->cfg["clusteruser"];
    else clusteruser = n->cfg["pipeline_clusteruser"];
    exportBIDS = p.outputBIDS;
    BIDSExportDir = p.BIDSoutputDir;

    /* get information about the study */
    study s(studyid, n);
    if (!s.valid()) {
        n->Log("Study was not valid: [" + s.msg() + "]", __FUNCTION__);
        return false;
    }
    QString firstModality = s.modality();
    int studynum = s.studyNum();
    QString studytype = s.type();

    dlog << QString("Working on study [%1%2]\nstudyid [%3]\nModality [%4]\n").arg(uid).arg(studynum).arg(studyid).arg(firstModality);
    dlog << QString("********** Checking if all required data exists **********");

    /* ------------------------------------------------------------------------
       check all of the steps to see if this data spec is valid
       ------------------------------------------------------------------------ */
    bool stepIsInvalid = false;
    for (int i = 0; i < datadef.size(); i++) {
        QString protocol = datadef[i].protocol;
        QString modality = datadef[i].modality.toLower();
        QString imagetype = datadef[i].imagetype;
        bool enabled = datadef[i].flags.enabled;
        QString type = datadef[i].type;
        QString level = datadef[i].level;
        QString assoctype = datadef[i].assoctype;
        bool optional = datadef[i].flags.optional;
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

        dlog << QString("%1() Checking step [%2]").arg(__FUNCTION__).arg(i);

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
            QStringList prots = ShellWords(protocol);
            protocols = "'" + prots.join("','") + "'";
        }
        else
            protocols = "'" + protocol + "'";

        /* separate image types */
        QString imagetypes;
        if (imagetype.contains(",")) {
            QStringList types = imagetype.split(QRegularExpression(",\\s*"));
            for(int ii=0; ii<types.size(); ii++)
                types[ii] = types[ii].replace("\\", "\\\\");
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
            else if ((assoctype == "all") || (assoctype == "entiresubject")) {
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

            dlog << n->Debug("SQL used for this search (for debugging) [" + n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__,true) + "]", __FUNCTION__);
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
    /* get list of seriesids/modalities, pass to archiveio::WriteBIDS() */

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
        bool gzip = datadef[i].flags.gzip;
        QString location = datadef[i].location;
        bool useseries = datadef[i].flags.useSeries;
        bool preserveseries = datadef[i].flags.preserveSeries;
        bool usephasedir = datadef[i].flags.usePhaseDir;
        bool behonly = datadef[i].flags.behOnly;
        QString behformat = datadef[i].behformat;
        QString behdir = datadef[i].behdir;
        bool enabled = datadef[i].flags.enabled;
        QString level = datadef[i].level;
        QString numboldreps = datadef[i].numboldreps;
        qint64 datadownloadid = datadef[i].datadownloadid;

        QSqlQuery q;

        /* seperate any protocols that have multiples */
        QString protocols;
        if (protocol.contains("\"")) {
            QStringList prots = ShellWords(protocol);
            protocols = "'" + prots.join("','") + "'";
        }
        else
            protocols = "'" + protocol + "'";

        /* separate image types */
        QString imagetypes;
        if (imagetype.contains(",")) {
            QStringList types = imagetype.split(QRegularExpression(",\\s*"));
            for(int ii=0; ii<types.size(); ii++)
                types[ii] = types[ii].replace("\\", "\\\\");
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

        dlog << QString("Downloading data for Step %1  -   NumBoldReps [%2]  Comparison [%3]  num [%4]  valid [%5]").arg(i).arg(numboldreps).arg(comparison).arg(num).arg(validComparisonStr);

        /* check to see if we should even run this step */
        if (!enabled) {
            dlog << n->Debug(QString("This data step [" + protocol + "] is not enabled. Data step will not be downloaded."),__FUNCTION__);
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
            dlog << n->Log(QString("Error - Modality [" + modality + "] not found. Data step will not be downloaded.").arg(p.name), __FUNCTION__);
            RecordDataDownload(datadownloadid, analysisid, modality, 1, -1, -1, "", i, "Invalid modality. Not downloading.");
            continue;
        }

        QString sqlstring;
        /* get a list of series satisfying the search criteria, if it exists */
        if (level == "study") {
            dlog << "\tGetting list of series that match this (STUDY-level) data step -  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetype + "]";

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
            dlog << "\tGetting list of series matching this (SUBJECT-level) data step -  protocols [" + protocols + "]  criteria [" + criteria + "]  imagetype [" + imagetype + "]";

            if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
                /* find the data from the same subject and modality that has the nearest (in time) matching scan */
                dlog << n->Log("Searching for subject-level data nearest-in-time.", __FUNCTION__);

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

                dlog << n->Log(QString("Preparing (subject-level) data search:  protocols [%1]  criteria [%2]  imagetype [%3]").arg(protocols).arg(criteria).arg(imagetypes), __FUNCTION__);

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
            else if ((assoctype == "all") || (assoctype == "entiresubject")) {
                dlog << n->Log("Searching for all subject-level data", __FUNCTION__);
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
                dlog << n->Log(QString("Searching for subject-level data with same study type. assoctype [%1]").arg(assoctype), __FUNCTION__);

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

        if (exportBIDS) {
            /* consolidate the found series */
            QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            while (q.next()) {
                int seriesid = q.value(modality+"series_id").toInt();
                BIDSseriesids.append(seriesid);
                BIDSmodalities.append(modality);
            }
            if (q.size() > 0)
                dlog << n->Log(QString("Exporting in BIDS format. Added [%1] series to export. Total series is [%2]").arg(q.size()).arg(BIDSseriesids.size()), __FUNCTION__);
        }
        else {
            int newseriesnum = 1;
            QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {

                dlog << n->Debug(QString("Found [%1] matching subject-level series").arg(q.size()), __FUNCTION__);
                /* in theory, data for this analysis exists for this study, so lets now create the analysis directory */
                QString m;
                if (!MakePath(analysispath + "/pipeline", m)) {
                    dlog << n->Log("Error: unable to create directory [" + analysispath + "/pipeline] - C", __FUNCTION__);
                    UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + analysispath + "/pipeline]", -1, -1, "", "", false, true, 0, 0);
                    continue;
                }

                while (q.next()) {
                    int localstudynum;
                    n->Debug(QString("NumDownloaded [%1]").arg(numdownloaded), __FUNCTION__);
                    int seriesid = q.value(modality+"series_id").toInt();
                    int seriesnum = q.value("series_num").toInt();
                    QString seriesdesc = q.value("series_desc").toString();
                    QString seriesprotocol = q.value("series_protocol").toString();
                    QString seriesdatetime = q.value("series_datetime").toString();
                    QString datatype;
                    if (q.value("data_type").isValid())
                        datatype = q.value("data_type").toString().trimmed();
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

                    dlog << QString("Beginning to copy data -  protocol [%1]  seriesnum [%2]  seriesdatetime [%3]").arg(seriesdesc).arg(seriesnum).arg(seriesdatetime);

                    QString behoutdir;
                    QString indir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum).arg(datatype);
                    QString behindir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum);

                    if (!QFile::exists(indir)) {
                        dlog << QString("indir [%1] does NOT exist...").arg(indir);
                        indir = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(uid).arg(localstudynum).arg(seriesnum);
                        dlog << QString("... using this indir [%1] instead").arg(indir);
                    }
                    else {
                        dlog << QString("indir [%1] exists").arg(indir);
                    }

                    /* start building the analysis path */
                    QString newanalysispath = analysispath + "/" + location;

                    if (behformat == "none")
                        dlog << n->Log(QString("Copying imaging data [%1] and behavioral data [%2] into [%3]").arg(indir).arg(behindir).arg(newanalysispath), __FUNCTION__);
                    else
                        dlog << n->Log("Copying imaging data from [" + indir + "] to [" + newanalysispath + "]", __FUNCTION__);

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
                        dlog << QString("\tPhasePlane [" + phaseplane + "] PhasePositive [%1]").arg(phasepositive);

                        QString phasedir = "unknownPE";
                        if ((phaseplane == "COL") && (phasepositive == 1)) phasedir = "AP";
                        if ((phaseplane == "COL") && (phasepositive == 0)) phasedir = "PA";
                        if ((phaseplane == "COL") && (phasepositive == -1)) phasedir = "COL";
                        if ((phaseplane == "ROW") && (phasepositive == 1)) phasedir = "RL";
                        if ((phaseplane == "ROW") && (phasepositive == 0)) phasedir = "LR";
                        if ((phaseplane == "ROW") && (phasepositive == -1)) phasedir = "ROW";

                        newanalysispath += "/" + phasedir;
                    }

                    //QString m;
                    if (!MakePath(newanalysispath, m)) {
                        dlog << n->Log("Error: unable to create directory [" + newanalysispath + "] message [" + m + "]", __FUNCTION__);
                        UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + newanalysispath + "]", 0, -1, "", "", false, true, -1, -1);
                    }
                    else
                        dlog << n->Debug("Created imaging data output directory [" + newanalysispath + "]", __FUNCTION__);

                    SystemCommand("chmod -Rf 777 " + newanalysispath, true, true);

                    if (!behonly) {
                        /* output the correct file type */
                        if ((dataformat == "dicom") || ((datatype != "dicom") && (datatype != "parrec"))) {
                            QString systemstring;
                            if (p.dataCopyMethod == "scp")
                                systemstring = QString("scp %1/* %2\\@%3:%4").arg(indir).arg(n->cfg["clusteruser"]).arg(p.clusterSubmitHost).arg(newanalysispath);
                            else
                                systemstring = QString("time cp -v %1/* %2").arg(indir).arg(newanalysispath);
                            n->Debug(SystemCommand(systemstring, true, true));

                            dlog << n->Log(QString("Finished copying imaging data from [%1] to [%2]").arg(indir).arg(newanalysispath), __FUNCTION__);

                            qint64 c;
                            qint64 b;
                            GetDirSizeAndFileCount(newanalysispath, c, b, true);
                            dlog << n->Log(QString("Imaging data output directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(newanalysispath).arg(c).arg(b), __FUNCTION__);
                        }
                        else {
                            QString tmpdir = n->cfg["tmpdir"] + "/" + GenerateRandomString(10);
                            m = "";
                            if (!MakePath(tmpdir, m)) {
                                dlog << n->Log("Error: unable to create temp directory [" + tmpdir + "] message [" + m + "] for DICOM conversion", __FUNCTION__);
                                UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + newanalysispath + "]", 0, -1, "", "", false, true, -1, -1);
                            }
                            else
                                dlog << n->Debug("Created temp directory [" + tmpdir + "] for DICOM conversion", __FUNCTION__);
                            int numfilesconv(0);
                            int numfilesrenamed(0);
                            QString binpath = n->cfg["nidbdir"] + "/bin";
                            BIDSMapping mapping;
                            QString localStudyNumStr = QString("%1").arg(localstudynum);
                            QString seriesNumStr = QString("%1").arg(seriesnum);
                            img->ConvertDicom(dataformat, indir, tmpdir, binpath, gzip, false, uid, localStudyNumStr, seriesNumStr, "", "", mapping, datatype, numfilesconv, numfilesrenamed, m);

                            QString systemstring;
                            if (p.dataCopyMethod == "scp")
                                systemstring = QString("scp %1/* %2\\@%3:%4").arg(tmpdir).arg(n->cfg["clusteruser"]).arg(p.clusterSubmitHost).arg(newanalysispath);
                            else
                                systemstring = QString("cp -v %1/* %2").arg(tmpdir).arg(newanalysispath);
                            n->Log(SystemCommand(systemstring, true, true));

                            dlog << "Removing temp directory [" + tmpdir + "]";
                            if (!RemoveDir(tmpdir,m))
                                dlog << n->Log("Error: unable to remove temp directory [" + tmpdir + "] error [" + m + "]", __FUNCTION__);

                            dlog << QString("\tDone copying converted imaging data from [%1] via [%2] to [%3]").arg(indir).arg(tmpdir).arg(newanalysispath);

                            qint64 c;
                            qint64 b;
                            GetDirSizeAndFileCount(newanalysispath, c, b, true);
                            dlog << n->Debug(QString("Imaging output directory [%1] now contains [%2] files, and is [%3] bytes in size.").arg(newanalysispath).arg(c).arg(b), __FUNCTION__);
                        }
                    }

                    RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, seriesid, newanalysispath, i, "Data downloaded");
                    dlog << QString("\tData for step [%1] downloaded to [%2]").arg(i).arg(newanalysispath);
                    numdownloaded++;

                    /* copy the beh data */
                    if (behformat != "behnone") {
                        dlog << "\tCopying behavioral data";
                        //QString m;
                        if (!MakePath(behoutdir, m)) {
                            dlog << n->Log("Error: unable to create behavioral output directory [" + behoutdir + "] message [" + m + "] - F", __FUNCTION__);
                            UpdateAnalysisStatus(analysisid, "error", "Unable to create directory [" + newanalysispath + "]", 0, -1, "", "", false, true, -1, -1);
                        }
                        else
                            dlog << n->Debug("Created behavioral output directory [" + behoutdir + "] - F", __FUNCTION__);
                        QString systemstring = "cp -Rv " + behindir + "/* " + behoutdir;
                        n->Debug(SystemCommand(systemstring, true, true));
                        n->Debug(SystemCommand("chmod -Rf 777 " + behoutdir, true, true));
                        dlog << QString("Done copying behavioral data from [%1] to [%2]").arg(behindir).arg(behoutdir);

                        qint64 c;
                        qint64 b;
                        GetDirSizeAndFileCount(behoutdir, c, b, true);
                        dlog << n->Debug(QString("Behavioral output directory now contains [%1] files, and is [%2] bytes in size.").arg(c).arg(b), __FUNCTION__);
                    }

                    /* give full read/write permissions to everyone */
                    SystemCommand("chmod -Rf 777 " + newanalysispath, true, true);
                    dlog << n->Debug("Done writing data to [" + newanalysispath + "]", __FUNCTION__);
                }
            }
            else {
                dlog << "\tError: found no matching subject-level [" + protocol + "] series. SQL: [" + sql + "]";
            }
        }
    }
    if (exportBIDS) {
        if (BIDSseriesids.size() > 0) {
            archiveIO *io = new archiveIO(n);
            QStringList bidsflags = { "BIDS_SUBJECTDIR_UID", "BIDS_STUDYDIR_STUDYNUM" };
            QString m2;
            QString BIDSpath;
            if (BIDSExportDir == "")
                BIDSpath = analysispath;
            else
                BIDSpath = analysispath + "/" + BIDSExportDir;
            io->WriteBIDS(BIDSseriesids, BIDSmodalities, BIDSpath, "BIDS Readme", bidsflags, m2);
            dlog << n->Log(QString("Exporting in BIDS format. Message from WriteBIDS [%1]").arg(m2), __FUNCTION__);
            numdownloaded = BIDSseriesids.size();
        }
        else {
            dlog << n->Log("Exporting in BIDS format, but no series found to export", __FUNCTION__);
        }
    }
    n->Debug("Leaving GetData() successfully", __FUNCTION__);
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
        n->Log("Unable to update analysis status. No variables set to update");
        return false;
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
        n->Log(QString("Found [%1] enabled pipelines").arg(a.size()));
    }
    else
        n->Log("Found 0 enabled pipelines");

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
/* --------- GetUIDStudyNumListByStudyIDs ------------------- */
/* ---------------------------------------------------------- */
QStringList modulePipeline::GetUIDStudyNumListByStudyIDs(QList<int> studyRowIDs) {
    QStringList uidlist;

    if (studyRowIDs.size() > 0) {
        /* get list of groups associated with this pipeline */
        QSqlQuery q;
        q.prepare("select concat(c.uid,cast(a.study_num as char)) 'uidstudynum' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id in (" + JoinIntArray(studyRowIDs, ",") + ") order by uidstudynum");
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            while (q.next()) {
                QString uidstudynum = q.value("uidstudynum").toString().trimmed();
                uidlist.append(uidstudynum);
            }
        }
    }

    return uidlist;
}


/* ---------------------------------------------------------- */
/* --------- GetPipelineSteps ------------------------------- */
/* ---------------------------------------------------------- */
QList<pipelineStep> modulePipeline::GetPipelineSteps(int pipelineid, int version) {

    QList<pipelineStep> steps;

    /* get steps */
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
            rec.assoctype = q.value("pdd_assoctype").toString().trimmed();
            rec.behdir = q.value("pdd_behdir").toString().trimmed();
            rec.behformat = q.value("pdd_behformat").toString().trimmed();
            rec.criteria = q.value("pdd_seriescriteria").toString().trimmed();
            rec.dataformat = q.value("pdd_dataformat").toString().trimmed();
            rec.flags.behOnly = q.value("pdd_behonly").toBool();
            rec.flags.enabled = q.value("pdd_enabled").toBool();
            rec.flags.gzip = q.value("pdd_gzip").toBool();
            rec.flags.optional = q.value("pdd_optional").toBool();
            rec.flags.preserveSeries = q.value("pdd_preserveseries").toBool();
            rec.flags.usePhaseDir = q.value("pdd_usephasedir").toBool();
            rec.flags.behOnly = q.value("pdd_behonly").toBool();
            rec.flags.useSeries = q.value("pdd_useseries").toBool();
            rec.id = q.value("pipelinedatadef_id").toInt();
            rec.imagetype = q.value("pdd_imagetype").toString().trimmed();
            rec.level = q.value("pdd_level").toString().trimmed();
            rec.location = q.value("pdd_location").toString().trimmed();
            rec.modality = q.value("pdd_modality").toString().trimmed();
            rec.numboldreps = q.value("pdd_numboldreps").toString().trimmed();
            rec.order = q.value("pdd_order").toInt();
            rec.protocol = q.value("pdd_protocol").toString().trimmed();
            rec.type = q.value("pdd_type").toString().trimmed();
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
        QStringList files = FindAllFiles(clusteranalysispath, searchpattern);
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
bool modulePipeline::CreateClusterJobFile(QString jobfilename, QString clustertype, QString queue, qint64 analysisid, QString uid, int studynum, QString analysispath, bool usetmpdir, QString tmpdir, QString studydatetime, QString pipelinename, int pipelineid, QString resultscript, int maxwalltime, int numcores, double memory,  QList<pipelineStep> steps, bool runsupplement) {

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

    QString jobfile;
    QString clusteranalysispath = analysispath;
    QString localanalysispath = QString("%1/%2-%3").arg(tmpdir).arg(pipelinename).arg(analysisid);

    n->Log("Cluster analysis path [" + analysispath + "]");
    n->Log("Local analysis path (temp directory) [" + localanalysispath + "]");

    /* check if any of the variables might be blank */
    if (analysispath == "") {
        n->Log("analysispath was blank", __FUNCTION__);
        return false;
    }
    if (localanalysispath == "") {
        n->Log("localanalysispath was blank", __FUNCTION__);
        return false;
    }
    if (analysisid < 0) {
        n->Log(QString("analysisid was [%1]").arg(analysisid), __FUNCTION__);
        return false;
    }
    if (uid == "") {
        n->Log("uid was blank", __FUNCTION__);
        return false;
    }
    if (studynum < 0) {
        n->Log(QString("studynum was [%1]").arg(studynum), __FUNCTION__);
        return false;
    }
    if (studydatetime == "") {
        n->Log("studydatetime was blank", __FUNCTION__);
        return false;
    }

    /* different submission parameters for slurm */
    if (clustertype == "slurm") {
        jobfile += "#!/bin/bash -l\n";
        if (runsupplement)
            jobfile += "#SBATCH -J " + pipelinename + "-supplement\n";
        else
            jobfile += "#SBATCH -J " + pipelinename + "\n";

        jobfile += "#SBATCH --nodes=1\n";
        jobfile += "#SBATCH --partition=" + queue + "\n";
        jobfile += "#SBATCH -o " + analysispath + "/pipeline/%x.o%j\n";
        jobfile += "#SBATCH -e " + analysispath + "/pipeline/%x.e%j\n";
        jobfile += QString("#SBATCH --mem-per-cpu=%1G\n").arg(memory);
        jobfile += QString("#SBATCH --ntasks=1 --cpus-per-task=%1\n").arg(numcores);
        if (maxwalltime > 0) {
            int hours = int(floor(maxwalltime/60));
            int min = maxwalltime % 60;

            if (min < 10)
                jobfile += QString("#SBATCH -t %1:0%2:00\n").arg(hours).arg(min);
            else
                jobfile += QString("#SBATCH -t %1:%2:00\n").arg(hours).arg(min);
        }
    }
    else { /* assume SGE otherwise */
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
        /* add the library path SO the cluster version of the nidb executable to run, and diagnostic echos */
        jobfile += "LD_LIBRARY_PATH=" + n->cfg["clusternidbpath"] + "/; export LD_LIBRARY_PATH;\n";
    }

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
                n->Debug(QString("runsupplement [%1]  issupplement [%2]").arg(runsupplement).arg(issupplement));
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
        n->Log("Wrote job file [" + jobfilename + "]", __FUNCTION__);
        return true;
    }
    else {
        n->Log("Could not write the file [" + jobfilename + "]", __FUNCTION__);
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetStudyToDoList ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get a list of studyRowIDs that should be processed
 * @param pipelineid the pipelineRowID
 * @param modality the primary modality of the study
 * @param depend
 * @param groupids QString containing a comma separated list of groups for this pipeline
 * @param runnum
 * @return
 */
QList<int> modulePipeline::GetStudyToDoList(int pipelineid, QString modality, int depend, QString groupids, qint64 &runnum) {

    QSqlQuery q;

    qint64 numInitial(0);
    int numRerun(0);
    int numSupplement(0);
    QList<int> studyIDToDoList;
    QStringList rerunStudyList;
    QStringList supplementStudyList;
    QStringList normalStudyList;
    QString m;

    pipeline p(pipelineid, n);
    //bool debug = p.debug;

    /* run some checks first */
    if (modality.trimmed() == "") {
        m = QString("Pipeline modality was blank. Cannot run pipeline without a primary modality in the data specification");
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        return studyIDToDoList;
    }

    /*
     * Subtractive search (list is narrowed down at each step, only remaining studies are analyzed)
     * A1) All studies that do not have an entry in the analysis table
     * A2) Studies with a parent dependency
     * A3) Studies in the group(s)

     * Additive search (analysis will always run if found):
     * B1) Studies that need results re-run
     * B2) Studies that need supplement run
     *
     * This could all be done in SQL with joins, but this method is easier to debug and
     * gives the row counts at each step. It's easier for the user to interpret
     */

    /* A1 - all studies that do not have an entry in the analysis table */
    q.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id not in (select study_id from analysis where pipeline_id = :pipelineid) and (a.study_datetime < date_sub(now(), interval 6 hour)) and a.study_modality = :modality and c.isactive = 1 order by a.study_datetime desc");
    q.bindValue(":pipelineid", pipelineid);
    q.bindValue(":modality", modality);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    m = QString("Step A1 - SQL returned %1 studies").arg(q.size());
    RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

    if (q.size() > 0) {
        while (q.next()) {
            int studyRowID = q.value("study_id").toInt();
            studyIDToDoList.append(studyRowID);
        }
    }
    m = QString("Step A1 - Found %1 global studies (valid study *and* older than 6 hours *and* does not have an existing analysis for this pipeline)").arg(studyIDToDoList.size());
    RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

    if (studyIDToDoList.size() > 100) {
        m = QString("Step A1 - Study list [greater than 100, not displaying full list of studies]");
    }
    else {
        m = QString("Step A1 - Study list [" + GetUIDStudyNumListByStudyIDs(studyIDToDoList).join(", ") + "]");
    }
    RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

    /* A2 - find studies in the specified parent dependency */
    if (depend >= 0) {
        QList<int> dependencyStudyRowIDs;
        if (p.depLevel == "subject") {

            /* need list of all studies that have a SUBJECT who has completed at least one analysis for the parent pipeline... */
            /* *** this option must be used with a group *** to further narrow down the number of analyzed studies */

            /* this function just needs to determine "Is there is a completed parent pipeline within this subject?", if yes, then this study is valid to analyze
             *  and the actual parent study to be copied will be determined later */

            /* find all SUBJECTS that have completed the dependency ... */
            QList<int> tmpSubjectRowIDs;
            q.prepare("select distinct(c.subject_id) from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where (a.pipeline_id = :depend and a.analysis_status = 'complete' and (a.analysis_isbad <> 1 or a.analysis_isbad is null))");
            q.bindValue(":depend", depend);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                while (q.next()) {
                    tmpSubjectRowIDs.append(q.value("subject_id").toInt());
                }
            }

            /* ... find all studies for those subjects, because now all of these studies are valid because the SUBJECT has at least one study that has run through a parent pipeline */
            q.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id in (" + JoinIntArray(tmpSubjectRowIDs, ", ") + ")");
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                while (q.next()) {
                    dependencyStudyRowIDs.append(q.value("study_id").toInt());
                }
            }

        }
        else {
            /* find all studies that have a parent STUDY analysis completed */
            q.prepare("select a.study_id from analysis a left join studies b on a.study_id = b.study_id where (a.pipeline_id = :depend and a.analysis_status = 'complete' and (a.analysis_isbad <> 1 or a.analysis_isbad is null))");
            q.bindValue(":depend", depend);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                while (q.next()) {
                    int studyRowID = q.value("study_id").toInt();
                    dependencyStudyRowIDs.append(studyRowID);
                }
            }
        }

        m = QString("Step A2 - Found %1 studies in the specified dependency (matching on the %2 level)").arg(dependencyStudyRowIDs.size()).arg(p.depLevel);
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        if (dependencyStudyRowIDs.size() > 100) {
            m = QString("Studies at the beginning of step A2 - [study list greater than 100, not displaying full list of studies]");
        }
        else {
            m = QString("Studies at the beginning of step A2 [" + GetUIDStudyNumListByStudyIDs(dependencyStudyRowIDs).join(", ") + "]");
        }

        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        /* find an intersection between the lists */
        QSet<int> set1(studyIDToDoList.begin(), studyIDToDoList.end());
        QSet<int> set2(dependencyStudyRowIDs.begin(), dependencyStudyRowIDs.end());
        QSet<int> intersection = set1.intersect(set2);

        m = QString("Global studyID list (step A1) contains %1 studies, and dependency list (step A2) contains %2 studies. The intersection of these sets yields %3 studies to be analyzed").arg(studyIDToDoList.size()).arg(dependencyStudyRowIDs.size()).arg(intersection.size());
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        studyIDToDoList = QList<int>(intersection.begin(), intersection.end());

        if (studyIDToDoList.size() > 100) {
            m = QString("Studies at the end of step A2 - [study list greater than 100, not displaying full list of studies]");
        }
        else {
            m = QString("Studies at the end of step A2 [" + GetUIDStudyNumListByStudyIDs(studyIDToDoList).join(", ") + "]");
        }

        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));
    }
    else {
        m = "Step A2 - No parent pipelines (dependencies) defined";
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));
    }

    /* A3 - find studies from above that match the specified group(s) */
    if (groupids != "") {
        QList<int> groupStudyRowIDs;
        q.prepare("select data_id from group_data where group_id in (" + groupids + ")");
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            while (q.next()) {
                int studyRowID = q.value("data_id").toInt();
                groupStudyRowIDs.append(studyRowID);
            }
        }
        m = QString("Step A3 - Found %1 studies from the specified group(s)").arg(groupStudyRowIDs.size());
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        m = QString("Studies at the beginning of step A3 [" + GetUIDStudyNumListByStudyIDs(groupStudyRowIDs).join(", ") + "]");
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        /* find an intersection between the lists */
        QSet<int> set1(studyIDToDoList.begin(), studyIDToDoList.end());
        QSet<int> set2(groupStudyRowIDs.begin(), groupStudyRowIDs.end());
        QSet<int> intersection = set1.intersect(set2);

        m = QString("Global studyID list (step A1 and A2) contains %1 studies, and group(s) list (step A3) contains %2 studies. Intersection of these sets yields %3 studies to be analyzed").arg(studyIDToDoList.size()).arg(groupStudyRowIDs.size()).arg(intersection.size());
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));

        studyIDToDoList = QList<int>(intersection.begin(), intersection.end());

        m = QString("Studies at the end of step A3 [" + GetUIDStudyNumListByStudyIDs(studyIDToDoList).join(", ") + "]");
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));
    }
    else {
        m = "Step A3 - No groups defined";
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));
    }

    numInitial = studyIDToDoList.size();

    /* step B1 - get only the studies that need to have their results rerun */
    q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_rerunresults = 1 and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null)) order by study_datetime desc");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            int studyid = q.value("study_id").toInt();
            studyIDToDoList.append(studyid);
            rerunStudyList << QString("%1%2").arg(q.value("uid").toString().replace('\u0000', "")).arg(q.value("study_num").toString());
            numRerun++;
        }
        m = n->Log(QString("Step B1 - Found %1 studies marked to have results re-run").arg(numRerun), __FUNCTION__);
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", QString("Found %1 studies to have results rerun [" + rerunStudyList.join(", ") + "]").arg(rerunStudyList.size()));
    }
    else
        n->Log("Step B1 - No studies marked to have results rerun", __FUNCTION__);

    /* step B2 - get only the studies that need to have their supplements run */
    q.prepare("select study_id from studies where study_id in (select study_id from analysis where pipeline_id = :pipelineid and analysis_runsupplement = 1 and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null)) order by study_datetime desc");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            int studyid = q.value("study_id").toInt();
            studyIDToDoList.append(studyid);
            supplementStudyList << QString("%1%2").arg(q.value("uid").toString().replace('\u0000', "")).arg(q.value("study_num").toString());
            numSupplement++;
        }
        m = QString("Step B2 - Found %1 studies marked to have supplement commands run. Studies [%2]").arg(numSupplement).arg(supplementStudyList.join(", "));
        //QString("Found %1 studies to have supplement scripts run [" + supplementStudyList.join(", ") + "]").arg(supplementStudyList.size());
        RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__));
    }
    else
        n->Log("Step B2 - No studies marked to have supplment run", __FUNCTION__);

    /* step C - end result */
    m = QString("Step C - Found %1 studies that met criteria to be analyzed (%2 not yet analyzed,   %3 flagged to be rerun,   %4 flagged for supplement run)").arg(studyIDToDoList.size()).arg(numInitial).arg(numRerun).arg(numSupplement);
    RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__) );

    m = "Studies after selection [" + GetUIDStudyNumListByStudyIDs(studyIDToDoList).join(", ") + "]";
    RecordPipelineEvent(pipelineid, runnum, -1, "getStudyToDoList", n->Log(m, __FUNCTION__) );

    return studyIDToDoList;
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
/* --------- RecordPipelineEvent ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Record a pipeline event, also add the message to the logfile
 * @param pipelineid The pipelineRowID
 * @param runnum The run number
 * @param analysisid The analysisRowID
 * @param event One of the possible events
 * @param message The message accompanying the event
 */
void modulePipeline::RecordPipelineEvent(int pipelineid, qint64 &runnum, qint64 analysisid, QString event, QString message) {

    /* possible events:

        analysisCheckDependency
        analysisCopyParent
        analysisCreateDir
        analysisErrorCreatePath
        analysisExists
        analysisGetData
        analysisOkToSubmit
        analysisReRunResults
        analysisRunSupplement
        errorNoDataSteps
        errorNoPipelineSteps
        errorNoQueue
        errorNoSubmitHost
        errorSubmitAnalysis
        getDataSteps
        getPipelineSteps
        getStudyToDoList
        maxJobsReached
        pipelineDisabled
        pipelineFinished
        pipelineModuleDisabled
        pipelineStarted
        submitAnalysis

    */

    QSqlQuery q;
    pipeline p(pipelineid, n);

    if (event == "pipelineStarted") {
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

    n->Debug(QString("Deleted %1 pipeline_history rows older than two days").arg(q.numRowsAffected()), __FUNCTION__);
}


/* ---------------------------------------------------------- */
/* --------- GetAnalysisLocalPath --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief modulePipeline::GetAnalysisLocalPath - get the analysis path as seen by NiDB
 * @param dirStructureCode
 * @return the path
 */
QString modulePipeline::GetAnalysisLocalPath(QString dirStructureCode, QString pipelineName, QString UID, int studyNum) {
    QSqlQuery q;
    QString path;
    QChar dirtype;

    if (dirStructureCode == "b") {
        path = n->cfg["analysisdirb"];
        dirtype = 'b';
    }
    else if (IsInt(dirStructureCode)) {
        q.prepare("select * from analysisdirs where analysisdir_id = :dir");
        q.bindValue(":dir", dirStructureCode);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            path = q.value("nidbpath").toString();
            QString dirformat = q.value("dirformat").toString();
            if (dirformat == "uidfirst")
                dirtype = 'a';
            else
                dirtype = 'b';
        }
    }
    else {
        path = n->cfg["analysisdir"];
        dirtype = 'a';
    }

    if ((pipelineName != "") && (UID != "") && (studyNum > -1)) {
        if (dirtype == 'a') {
            path = QString("%1/%2/%3/%4").arg(path).arg(UID).arg(studyNum).arg(pipelineName);
        }
        else {
            path = QString("%1/%2/%3/%4").arg(path).arg(pipelineName).arg(UID).arg(studyNum);
        }
    }

    return path;
}


/* ---------------------------------------------------------- */
/* --------- GetAnalysisClusterPath ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief modulePipeline::GetAnalysisClusterPath - get the analysis base path as see by the cluster
 * @param dirStructure
 * @return the path
 */
QString modulePipeline::GetAnalysisClusterPath(QString dirStructureCode, QString pipelineName, QString UID, int studyNum) {
    QSqlQuery q;
    QString path;
    QChar dirtype;

    if (dirStructureCode == "b") {
        path = n->cfg["clusteranalysisdirb"];
        dirtype = 'b';
    }
    else if (IsInt(dirStructureCode)) {
        q.prepare("select * from analysisdirs where analysisdir_id = :dir");
        q.bindValue(":dir", dirStructureCode);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            path = q.value("clusterpath").toString();
            QString dirformat = q.value("dirformat").toString();
            if (dirformat == "uidfirst")
                dirtype = 'a';
            else
                dirtype = 'b';
        }
    }
    else {
        path = n->cfg["clusteranalysisdir"];
        dirtype = 'a';
    }

    if ((pipelineName != "") && (UID != "") && (studyNum > -1)) {
        if (dirtype == 'a') {
            path = QString("%1/%2/%3/%4").arg(path).arg(UID).arg(studyNum).arg(pipelineName);
        }
        else {
            path = QString("%1/%2/%3/%4").arg(path).arg(pipelineName).arg(UID).arg(studyNum);
        }
    }

    return path;
}


/* ---------------------------------------------------------- */
/* --------- DisablePipeline -------------------------------- */
/* ---------------------------------------------------------- */
void modulePipeline::DisablePipeline(int pipelineid) {
    QSqlQuery q;
    q.prepare("update pipelines set pipeline_enabled = 0 where pipeline_id = :pid");
    q.bindValue(":pid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}
