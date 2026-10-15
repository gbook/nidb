/* ------------------------------------------------------------------------------
  NIDB modulePipelineContainer.cpp
  Copyright (C) 2004 - 2026
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

#include "modulePipelineContainer.h"
#include "modulePipeline.h"
#include "analysis.h"
#include <cmath>


/* ---------------------------------------------------------- */
/* --------- modulePipelineContainer ------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Constructor
 * @param a pointer to the nidb object
 */
modulePipelineContainer::modulePipelineContainer(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~modulePipelineContainer ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Destructor
 */
modulePipelineContainer::~modulePipelineContainer()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Entry point for this module. Moves each pipeline_containers row through submitted -> started -> building
 * (doc/pipeline-apptainer.md section 8). From then on the build job checks in through analysisapi.php, which sets
 * build_step, and complete or error at the end, so the module doesn't look at building rows
 * @param containerID If non-zero, only process this pipeline_containers row
 * @return The number of rows acted on (used to decide whether to keep the log)
 */
int modulePipelineContainer::Run(int containerID) {
    n->Log("Entering the pipelinecontainer module");

    int numDone = 0;

    /* 'started' means a previous run of this module stopped part way through PrepareExport(). The build
       directory may be incomplete, so don't try to resume it */
    for (containerRequest c : GetExportRequests("started", containerID)) {
        SetError(c.containerID, "The pipelinecontainer module stopped while preparing this build. Create the container again");
        numDone++;
    }

    /* new requests: prepare the build directory and submit the build job */
    for (containerRequest c : GetExportRequests("submitted", containerID)) {
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return numDone; }

        /* mark as started, unless another instance got to it first */
        if (!ClaimRequest(c, "submitted", "started"))
            continue;
        numDone++;

        QString msg;
        if (!PrepareExport(c, msg)) {
            SetError(c.containerID, msg);
            continue;
        }
        if (!SubmitBuildJob(c, msg)) {
            SetError(c.containerID, msg);
            continue;
        }
    }

    n->Log(QString("Leaving the pipelinecontainer module. Acted on [%1] container requests").arg(numDone));
    return numDone;
}


/* ---------------------------------------------------------- */
/* --------- GetExportRequests ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Get list of pipeline containers that have a specified status
 * @param status The build_status to select
 * @param containerID If non-zero, only this row
 * @return List of requests, oldest first
 */
QList<modulePipelineContainer::containerRequest> modulePipelineContainer::GetExportRequests(QString status, int containerID) {
    QList<containerRequest> requests;

    QSqlQuery q;
    if (containerID > 0) {
        q.prepare("select * from pipeline_containers where build_status = :status and pipelinecontainer_id = :id order by pipelinecontainer_id");
        q.bindValue(":id", containerID);
    }
    else
        q.prepare("select * from pipeline_containers where build_status = :status order by pipelinecontainer_id");
    q.bindValue(":status", status);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        containerRequest c;
        c.containerID = q.value("pipelinecontainer_id").toInt();
        c.pipelineID = q.value("pipeline_id").toInt();
        c.pipelineVersion = q.value("pipeline_version").toInt();
        c.referenceAnalysisID = q.value("reference_analysisid").toLongLong();
        c.runValidation = q.value("run_validation").toBool();
        c.status = q.value("build_status").toString();
        c.buildDir = GetBuildDir(c.containerID);
        requests.append(c);
    }

    return requests;
}


/* ---------------------------------------------------------- */
/* --------- ClaimRequest ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Change a row's status only if it still has the expected status, so two module instances can't process the same row
 * @param c The request
 * @param fromStatus The status the row must currently have
 * @param toStatus The new status
 * @return true if this instance changed the status
 */
bool modulePipelineContainer::ClaimRequest(containerRequest &c, QString fromStatus, QString toStatus) {
    QSqlQuery q;
    q.prepare("update pipeline_containers set build_status = :tostatus where pipelinecontainer_id = :id and build_status = :fromstatus");
    q.bindValue(":tostatus", toStatus);
    q.bindValue(":id", c.containerID);
    q.bindValue(":fromstatus", fromStatus);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.numRowsAffected() < 1)
        return false;

    c.status = toStatus;
    return true;
}


/* ---------------------------------------------------------- */
/* --------- PrepareExport ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Step 2. Check the pipeline is exportable, then write the build directory: run.sh, run.sh.template,
 * manifest-export.json, README.txt, requiredvars.txt, the staged reference input, export.log, build-container.sh and build.job.
 * build-container.sh is written before build.job, which calls it
 * @param c The request
 * @param msg Error message, if any
 * @return true if the build directory is ready to submit
 */
bool modulePipelineContainer::PrepareExport(containerRequest &c, QString &msg) {
    AppendBuildLog(c.containerID, QString("Preparing container for pipeline [%1] version [%2], reference analysis [%3], validation [%4]").arg(c.pipelineID).arg(c.pipelineVersion).arg(c.referenceAnalysisID).arg(c.runValidation ? "yes" : "no"));

    /* the requested version, since the pipeline may have been edited since the request was submitted */
    pipeline p(n);
    p.searchPipelineRowID = c.pipelineID;
    p.searchPipelineVersion = c.pipelineVersion;
    p.Load();
    if (!p.isValid()) {
        msg = QString("Unable to load pipeline [%1]: %2").arg(c.pipelineID).arg(p.msg);
        return false;
    }

    QStringList errors, warnings;
    if (!CheckIfExportable(p, c.pipelineVersion, errors, warnings)) {
        AppendBuildWarnings(c.containerID, warnings);
        msg = "Pipeline can't be exported:\n" + errors.join("\n");
        return false;
    }

    QString script;
    QStringList requiredVars;
    if (!RenderScriptTemplate(p, c.pipelineVersion, script, requiredVars, errors, warnings)) {
        AppendBuildWarnings(c.containerID, warnings);
        msg = "Unable to render the script template:\n" + errors.join("\n");
        return false;
    }
    AppendBuildWarnings(c.containerID, warnings);

    /* the build job reports back through analysisapi.php, so it needs the site's URL */
    if (n->cfg["siteurl"].trimmed() == "") {
        msg = "siteurl isn't set in the config, so the build job would have no way to report its progress";
        return false;
    }

    /* start from an empty build directory, so files from an earlier attempt aren't mixed with this build's */
    if (n->cfg["analysisdir"].trimmed() == "") {
        msg = "analysisdir isn't set in the config, so there's nowhere to put the build directory";
        return false;
    }
    QDir buildDir(c.buildDir);
    if (buildDir.exists() && !buildDir.removeRecursively()) {
        msg = "Unable to remove the build directory [" + c.buildDir + "] left from an earlier attempt";
        return false;
    }
    if (!MakePath(c.buildDir, msg))
        return false;

    /* the expected /input layout, for manifest.json, README.txt and the image's %help */
    QJsonArray inputLayout;
    QStringList inputLayoutText = GetInputDirStructure(p, c.pipelineVersion, inputLayout);

    QString manifest = CreateManifest(p, c.pipelineVersion, requiredVars, warnings, inputLayout);
    QString readme = CreateReadme(p, c.pipelineVersion, requiredVars, inputLayoutText);
    if (!WriteTextFile(c.buildDir + "/manifest-export.json", manifest, false) || !WriteTextFile(c.buildDir + "/README.txt", readme, false)) {
        msg = "Unable to write manifest-export.json and README.txt into [" + c.buildDir + "]";
        return false;
    }

    /* run.sh reads requiredvars.txt, one variable per line, since the image may not have jq */
    if (!WriteTextFile(c.buildDir + "/run.sh.template", script, false) || !WriteTextFile(c.buildDir + "/requiredvars.txt", requiredVars.isEmpty() ? "" : requiredVars.join("\n") + "\n", false)) {
        msg = "Unable to write run.sh.template and requiredvars.txt into [" + c.buildDir + "]";
        return false;
    }

    if (!WriteRunScript(c, msg))
        return false;

    /* export.log records the checks. The errors stopped the export above, so only the warnings are left */
    QStringList log;
    log << QString("Exported [%1] version [%2] on %3").arg(p.name).arg(c.pipelineVersion).arg(QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm:ss"));
    log << QString("Reference analysis [%1]").arg(c.referenceAnalysisID);
    log << "Required arguments: " + (requiredVars.isEmpty() ? QString("none") : "--" + requiredVars.join(", --"));
    log << QString("Export checks: %1 warning(s)").arg(warnings.size());
    for (QString w : warnings)
        log << "  " + w;
    if (!WriteTextFile(c.buildDir + "/export.log", log.join("\n") + "\n", false)) {
        msg = "Unable to write export.log into [" + c.buildDir + "]";
        return false;
    }

    if (!StageReferenceInput(c, p, msg))
        return false;

    if (!WriteContainerBuildScript(c, p, msg))
        return false;

    if (!WriteClusterJob(c, p, msg))
        return false;

    /* the build job runs as the pipeline's cluster user, which may not be the NiDB user, and writes into the build
       directory. Same permissions modulePipeline gives analysis directories */
    SystemCommand("chmod -Rf 777 " + ShellQuote(c.buildDir), true, true);

    AppendBuildLog(c.containerID, "Build directory [" + c.buildDir + "] is ready");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- SubmitBuildJob --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Step 3. Set build_status = building, then submit build.job with nidb::SubmitClusterJob(), using the
 * pipeline's cluster and queue settings. The job ID is written to build.jobid in the build directory.
 * build_startdate is set by the job's first check-in, since the job may wait in the queue
 * @param c The request
 * @param msg Error message, if any
 * @return true if the job was submitted
 */
bool modulePipelineContainer::SubmitBuildJob(containerRequest &c, QString &msg) {

    /* the cluster settings aren't versioned, so the current version is fine */
    pipeline p(n);
    p.searchPipelineRowID = c.pipelineID;
    if (!p.Load()) {
        msg = QString("Unable to load pipeline [%1]: %2").arg(c.pipelineID).arg(p.msg);
        return false;
    }
    if (p.clusterQueue == "") {
        msg = QString("Pipeline [%1] has no cluster queue").arg(p.name);
        return false;
    }
    if (p.clusterSubmitHost == "") {
        msg = QString("Pipeline [%1] has no submit host, and clustersubmithost isn't set in the config").arg(p.name);
        return false;
    }

    /* analysisapi.php only accepts check-ins for building rows, and the job can check in as soon as it's submitted */
    if (!ClaimRequest(c, "started", "building")) {
        msg = QString("Container [%1] is no longer 'started', so it wasn't submitted").arg(c.containerID);
        return false;
    }

    /* the scheduler reads build.job from the cluster's path to the build directory */
    QString jobFile = GetClusterBuildDir(c.containerID) + "/build.job";
    QString submitMsg, result;
    int jobID = -1;
    if (!n->SubmitClusterJob(jobFile, p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], p.clusterUser, p.clusterQueue, submitMsg, jobID, result)) {
        msg = QString("Unable to submit [%1] to the %2 cluster through [%3]: %4. Output [%5]").arg(jobFile).arg(p.clusterType).arg(p.clusterSubmitHost).arg(submitMsg).arg(result);
        return false;
    }

    /* pipeline_containers has no job ID column, so it goes in the build directory */
    WriteTextFile(c.buildDir + "/build.jobid", QString("%1\n").arg(jobID), false);

    AppendBuildLog(c.containerID, QString("Submitted build job [%1] to the %2 cluster, queue [%3], through [%4]. Output [%5]").arg(jobID).arg(p.clusterType).arg(p.clusterQueue).arg(p.clusterSubmitHost).arg(result));

    /* a cancel that came in after the claim above, but before build.jobid was written, couldn't find the job to stop it */
    QSqlQuery q;
    q.prepare("select build_status from pipeline_containers where pipelinecontainer_id = :id");
    q.bindValue(":id", c.containerID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first() && (q.value("build_status").toString() == "canceled")) {
        QString cancelMsg, cancelResult;
        n->CancelClusterJob(p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], jobID, cancelMsg, cancelResult);
        AppendBuildLog(c.containerID, "Canceled while the job was being submitted. " + cancelMsg);
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- CheckIfExportable ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Export checks (doc sections 1, 3, 5). The same rules as the pitfalls on pipelinecontainers.php, which are
 * a preview; these are authoritative. Only enabled main steps are checked, since only they run in the container.
 * Errors: not level 1, no enabled main steps, no completed analysis of this version, group, deprecated or
 * unrecognized variables, NiDB storage paths, running a container. Warnings: {analysisid}, {command}, a parent pipeline, FreeSurfer, MATLAB
 * @param p The pipeline
 * @param version Pipeline version
 * @param errors Problems that stop the export
 * @param warnings Problems that don't
 * @return true if there are no errors
 */
bool modulePipelineContainer::CheckIfExportable(pipeline &p, int version, QStringList &errors, QStringList &warnings) {

    if (p.level != 1)
        errors << QString("Only level 1 pipelines can be exported. This is a level [%1] pipeline").arg(p.level);

    /* main steps of this version */
    modulePipeline mp(n);
    QList<pipelineStep> steps;
    for (pipelineStep step : mp.GetPipelineSteps(p.GetPipelineRowID(), version)) {
        if (step.enabled && !step.supplement)
            steps.append(step);
    }
    if (steps.size() == 0)
        errors << QString("Version [%1] has no enabled main script steps").arg(version);

    /* the build re-runs a completed analysis of this version (the reference analysis) */
    QSqlQuery q;
    q.prepare("select count(*) 'num' from analysis where pipeline_id = :pipelineid and pipeline_version = :version and analysis_status = 'complete' and (analysis_isbad is null or analysis_isbad <> 1)");
    q.bindValue(":pipelineid", p.GetPipelineRowID());
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first() && (q.value("num").toInt() < 1))
        errors << QString("Version [%1] has no completed, not-bad analyses to use as the reference analysis").arg(version);

    /* variables and paths, in each step's command and working directory */
    QString allCommands;
    for (pipelineStep step : steps) {
        QString text = step.command + "\n" + step.workingDir;
        CheckVariables(text, step.order, errors, warnings);
        CheckPaths(text, step.order, errors);
        CheckContainers(step.command, step.order, errors);
        allCommands += step.command + "\n";
    }

    /* the caller has to supply the parent's output in /input */
    for (int parentID : p.parentIDs) {
        pipeline parent(n);
        parent.searchPipelineRowID = parentID;
        parent.Load();
        warnings << QString("Depends on parent pipeline [%1]. Its output must be supplied in /input").arg(parent.isValid() ? parent.name : QString::number(parentID));
    }

    /* software that needs a license, which is never copied into the image */
    if (allCommands.contains(QRegularExpression("freesurfer|recon-all|FREESURFER_HOME|SUBJECTS_DIR", QRegularExpression::CaseInsensitiveOption)))
        warnings << "Uses FreeSurfer. Its license.txt isn't copied into the image, so users must bind their own (--bind license.txt:/opt/freesurfer/license.txt)";
    if (allCommands.contains(QRegularExpression("\\bmatlab\\b", QRegularExpression::CaseInsensitiveOption)))
        warnings << "Uses MATLAB. Commercial software must not be in an image meant to be shared, and MATLAB license files aren't copied. Compiled MATLAB with the MATLAB Runtime (MCR) can be included";

    /* return false if any errors */
    return (errors.size() == 0);
}


/* ---------------------------------------------------------- */
/* --------- CheckVariables --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check the {variables} in one step (doc section 3). Group variables, {first_*} and unrecognized
 * tokens are errors; {analysisid} and {command} are warnings. Bash ${var} isn't a NiDB variable and is skipped
 * @param text The step command and working directory
 * @param step The step number, for messages
 * @param errors Problems that stop the export
 * @param warnings Problems that don't
 */
void modulePipelineContainer::CheckVariables(QString text, int step, QStringList &errors, QStringList &warnings) {
    static const QStringList known = { "analysisrootdir", "pipelinename", "workingdir", "description", "subjectuid", "studynum", "uidstudynum", "studydatetime", "analysisid", "nolog", "nocheckin", "profile", "command" };
    static const QStringList group = { "groups", "uidstudynums", "numsubjects" };

    QStringList found;
    QRegularExpressionMatchIterator i = QRegularExpression("(?<!\\$)\\{([A-Za-z0-9_]+)\\}").globalMatch(text);
    while (i.hasNext()) {
        QString var = i.next().captured(1).toLower();
        if (!found.contains(var))
            found << var;
    }

    for (QString var : found) {
        if (var == "analysisid")
            warnings << QString("Step %1: {analysisid} is replaced with 0, since there is no analysis ID outside NiDB").arg(step);
        else if (var == "command")
            warnings << QString("Step %1: the deprecated {command} variable is removed").arg(step);
        else if (known.contains(var))
            continue;
        else if (group.contains(var) || var.startsWith("uidstudynums_") || var.startsWith("numsubjects_"))
            errors << QString("Step %1: group variable {%2} can only be used in level 2 pipelines").arg(step).arg(var);
        else if (var.startsWith("first_"))
            errors << QString("Step %1: deprecated variable {%2} is no longer expanded by NiDB").arg(step).arg(var);
        else
            errors << QString("Step %1: {%2} isn't a NiDB variable. Fix the typo, or rewrite the text so it isn't a single word in braces").arg(step).arg(var);
    }
}


/* ---------------------------------------------------------- */
/* --------- CheckPaths ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check one step for absolute NiDB storage paths (doc section 5): the archive and analysis directories
 * in the config, and the analysisdirs table. The container can't see NiDB's storage
 * @param text The step command and working directory
 * @param step The step number, for messages
 * @param errors Problems that stop the export
 */
void modulePipelineContainer::CheckPaths(QString text, int step, QStringList &errors) {

    /* load the storage paths once */
    if (storagePaths.isEmpty()) {
        QStringList keys = { "archivedir", "archivedir1", "archivedir2", "archivedir3", "archivedir4", "analysisdir", "analysisdirb", "clusteranalysisdir", "clusteranalysisdirb", "groupanalysisdir" };
        for (QString key : keys)
            storagePaths << n->cfg.value(key);

        QSqlQuery q;
        q.prepare("select nidbpath, clusterpath from analysisdirs");
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next())
            storagePaths << q.value("nidbpath").toString() << q.value("clusterpath").toString();

        /* trim trailing slashes, and drop blanks and the root directory */
        QStringList paths;
        for (QString path : storagePaths) {
            path = path.trimmed();
            while (path.endsWith("/"))
                path.chop(1);
            if ((path != "") && !paths.contains(path))
                paths << path;
        }
        storagePaths = paths;
    }

    for (QString path : storagePaths) {
        if (text.contains(QRegularExpression(QRegularExpression::escape(path) + "(/|\\s|$|['\";])")))
            errors << QString("Step %1: NiDB storage path [%2] can't be used in the container. Use {analysisrootdir} for the analysis directory, and bring other data in through /input").arg(step).arg(path);
    }
}


/* ---------------------------------------------------------- */
/* --------- CheckContainers -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check one step for running a container (doc section 7, "Pipelines that run containers"): apptainer, singularity,
 * docker or podman with a run-type subcommand, docker-compose, run-singularity, or a .sif/.simg image, which can be run
 * directly. The image would need a container runtime inside a container, which doesn't work. Comment lines are skipped.
 * build-container.sh also checks the reference run's trace, which catches containers started from a script the step calls.
 * The same rule as GetContainerPitfalls() on pipelinecontainers.php
 * @param text The step command
 * @param step The step number, for messages
 * @param errors Problems that stop the export
 */
void modulePipelineContainer::CheckContainers(QString text, int step, QStringList &errors) {
    static const QRegularExpression containerRegex("(^|[^A-Za-z0-9_.-])(apptainer|singularity|docker|podman)\\s+(exec|run|shell|instance|test|start|create|compose|container)\\b|\\bdocker-compose\\b|\\brun-singularity\\b|[A-Za-z0-9_}-]\\.(?i:sif|simg)\\b");

    for (QString line : text.split("\n")) {
        if (line.trimmed().startsWith("#"))
            continue;
        QRegularExpressionMatch m = containerRegex.match(line);
        if (m.hasMatch()) {
            errors << QString("Step %1: runs a container [%2]. Pipelines that run containers can't be exported, since the image would need Apptainer inside Apptainer. If the pipeline already runs in a container, share that image instead").arg(step).arg(m.captured(0).trimmed());
            return;
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- RenderScriptTemplate --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Render run.sh.template from the main script steps (doc sections 2-4). Export-time variables are
 * replaced; {subjectuid}, {studynum}, {studydatetime} and {analysisrootdir} are left for run.sh, lowercased.
 * Each enabled step is a comment, an echo, the cd to its working directory, and the command. Disabled steps
 * are comments, and supplement steps are left out. requiredVars only counts the lines that run
 * @param p The pipeline
 * @param version Pipeline version
 * @param script The rendered template
 * @param requiredVars The runtime variables the script uses
 * @param errors Problems that stop the export
 * @param warnings Problems that don't
 * @return true if there are no errors
 */
bool modulePipelineContainer::RenderScriptTemplate(pipeline &p, int version, QString &script, QStringList &requiredVars, QStringList &errors, QStringList &warnings) {
    static const QStringList runtimeVars = { "subjectuid", "studynum", "studydatetime" };

    script = "#!/bin/bash\n";
    script += QString("# %1 version %2, exported from NiDB on %3\n").arg(p.name).arg(version).arg(QDateTime::currentDateTime().toString("yyyy-MM-dd hh:mm:ss"));
    script += "# Rendered and run by /pipeline/run.sh. Steps run in order and a failing step doesn't stop later steps, unless run.sh is given --stop-on-error\n";

    /* the lines that run, for the checks below. Comments and echo lines aren't checked */
    QString executable;

    modulePipeline mp(n);
    int numSteps = 0;
    for (pipelineStep step : mp.GetPipelineSteps(p.GetPipelineRowID(), version)) {
        /* supplement steps aren't exported (doc section 2) */
        if (step.supplement)
            continue;

        /* the description is written into comments and an echo, so remove the flags and flatten newlines */
        QString description = step.description;
        description.remove(QRegularExpression("\\{(nolog|nocheckin|profile)\\}", QRegularExpression::CaseInsensitiveOption));
        description.replace(QRegularExpression("[\\x00-\\x1F]"), " ");
        description = description.trimmed();

        /* {workingdir} is the step's working directory, rendered. NiDB doesn't render it (doc section 4) */
        QString workingDir = RenderVariables(step.workingDir, p.name, step.workingDir, description).trimmed();
        QString command = RenderVariables(step.command, p.name, workingDir, description).trimmed();

        /* as in FormatCommand(), remove a trailing semicolon */
        if (command.endsWith(";"))
            command = command.chopped(1).trimmed();

        script += "\n";
        if (step.enabled) {
            if ((step.command + step.description).contains("{PROFILE}", Qt::CaseInsensitive))
                warnings << QString("Step %1: {PROFILE} is removed. The container doesn't run steps with /usr/bin/time").arg(step.order);

            script += QString("# Step %1: %2\n").arg(step.order).arg(description);
            script += "echo " + ShellQuote(QString("== Step %1: %2").arg(step.order).arg(description)) + "\n";
            if (workingDir != "")
                script += "cd " + workingDir + "\n";
            if (command != "")
                script += command + "\n";

            executable += workingDir + "\n" + command + "\n";
            numSteps++;
        }
        else {
            /* disabled steps are kept as comments, so the step numbering matches NiDB (doc section 2) */
            script += QString("# Step %1 (disabled): %2\n").arg(step.order).arg(description);
            if (workingDir != "")
                script += "# cd " + workingDir + "\n";
            if (command != "")
                script += "# " + command + "\n";
        }
    }

    if (numSteps == 0)
        errors << QString("Version [%1] has no enabled main script steps").arg(version);

    /* only the runtime variables may be left. CheckVariables() already reported problems in the step text, so this
       catches anything that came in through {workingdir} or {description}. Bash ${var} is skipped */
    QStringList unknown;
    QRegularExpressionMatchIterator i = QRegularExpression("(?<!\\$)\\{([A-Za-z0-9_]+)\\}").globalMatch(executable);
    while (i.hasNext()) {
        QString var = i.next().captured(1);
        if ((var != "analysisrootdir") && !runtimeVars.contains(var) && !unknown.contains(var))
            unknown << var;
    }
    for (QString var : unknown)
        errors << QString("{%1} is left in the rendered script, after the step's working directory and description were inserted").arg(var);

    /* the runtime variables the steps use, which run.sh requires */
    requiredVars.clear();
    for (QString var : runtimeVars) {
        if (executable.contains("{" + var + "}"))
            requiredVars << var;
    }

    return (errors.size() == 0);
}


/* ---------------------------------------------------------- */
/* --------- RenderVariables -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Replace the pipeline variables in one command or working directory, as FormatCommand() does, but leave the
 * runtime variables for run.sh (doc section 3). The text is scanned once, so the inserted values aren't rescanned.
 * Variables are matched case-insensitively and the runtime variables are lowercased, so run.sh only needs exact matches.
 * {uidstudynum} becomes {subjectuid}{studynum}, {analysisid} becomes 0, {command} and the flags are removed, and
 * control characters are removed. Other {words} are left as they are
 * @param text The command or working directory
 * @param pipelineName Value of {pipelinename}
 * @param workingDir Value of {workingdir}
 * @param description Value of {description}
 * @return The rendered text
 */
QString modulePipelineContainer::RenderVariables(QString text, QString pipelineName, QString workingDir, QString description) {

    text.remove(QRegularExpression("\\{(nolog|nocheckin|profile)\\}", QRegularExpression::CaseInsensitiveOption));
    text.remove(QRegularExpression("[\\x00-\\x1F]"));

    QString rendered;
    int last = 0;
    QRegularExpressionMatchIterator i = QRegularExpression("\\{([A-Za-z0-9_]+)\\}").globalMatch(text);
    while (i.hasNext()) {
        QRegularExpressionMatch m = i.next();
        QString var = m.captured(1).toLower();
        QString value;
        if ((var == "analysisrootdir") || (var == "subjectuid") || (var == "studynum") || (var == "studydatetime"))
            value = "{" + var + "}";
        else if (var == "uidstudynum")
            value = "{subjectuid}{studynum}";
        else if (var == "pipelinename")
            value = pipelineName;
        else if (var == "workingdir")
            value = workingDir;
        else if (var == "description")
            value = description;
        else if (var == "analysisid")
            value = "0";
        else if (var == "command")
            value = "";
        else
            continue;

        rendered += text.mid(last, m.capturedStart() - last) + value;
        last = m.capturedEnd();
    }
    rendered += text.mid(last);

    return rendered;
}


/* ---------------------------------------------------------- */
/* --------- CreateManifest --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Create manifest-export.json, NiDB's part of manifest.json (doc section 6). The build job writes manifest.json
 * from it, adding a 'build' object with the base OS and the captured software
 * @param p The pipeline
 * @param version Pipeline version
 * @param requiredVars The runtime variables the script uses
 * @param warnings Export warnings
 * @param inputLayout The /input layout from GetInputDirStructure()
 * @return The JSON text
 */
QString modulePipelineContainer::CreateManifest(pipeline &p, int version, const QStringList &requiredVars, const QStringList &warnings, const QJsonArray &inputLayout) {
    /* TODO: the squirrel pipeline from p.GetSquirrelObject() */
    QJsonObject root;
    root["pipelineName"] = p.name;
    root["pipelineVersion"] = version;
    root["pipelineDescription"] = p.desc;
    root["exportDate"] = QDateTime::currentDateTime().toString(Qt::ISODate);
    root["requiredVariables"] = QJsonArray::fromStringList(requiredVars);
    root["exportWarnings"] = QJsonArray::fromStringList(warnings);
    root["inputLayout"] = inputLayout;

    return QJsonDocument(root).toJson();
}


/* ---------------------------------------------------------- */
/* --------- GetInputDirStructure --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief The /input layout the container expects: the layout GetData() and the dependency copy would have
 * staged into the analysis directory (doc section 6). The container doesn't check it; this is documentation,
 * for manifest.json, README.txt and the image's %help. Data steps that write to the same directory, with the same
 * modality, format and notes, are one entry, with their protocols combined
 * @param p The pipeline
 * @param version Pipeline version (the data steps are versioned)
 * @param items The layout, for manifest.json
 * @return The layout as readable text, one line per entry
 */
QStringList modulePipelineContainer::GetInputDirStructure(pipeline &p, int version, QJsonArray &items) {
    QStringList lines;

    if (p.outputBIDS) {
        /* GetData() writes all the matching series as one BIDS dataset (archiveIO::WriteBIDS(), BIDS_SUBJECTDIR_UID and BIDS_STUDYDIR_STUDYNUM) */
        QString dir = "/input";
        QString bidsDir = p.BIDSoutputDir.trimmed();
        while (bidsDir.startsWith("/")) bidsDir.remove(0, 1);
        if (bidsDir != "")
            dir += "/" + bidsDir;
        lines << dir;
        lines << "    A BIDS dataset. Subject directories are sub-<subjectuid> and session directories are ses-<studynum>";

        QJsonObject o;
        o["type"] = "bids";
        o["location"] = dir;
        items.append(o);
    }
    else {
        /* steps that write to the same directory, with the same modality, format and notes, are one input to
           the container. Their protocols are combined */
        QStringList keys;
        QList<QStringList> notes;
        QList<QStringList> protocols;
        QList<QJsonObject> entries;

        modulePipeline mp(n);
        for (dataDefinitionStep d : mp.GetPipelineDataDef(p.GetPipelineRowID(), version)) {
            if (!d.flags.isEnabled)
                continue;

            QString location = d.exportSubDirectoryName;
            while (location.startsWith("/")) location.remove(0, 1);
            QString root = (location == "") ? "/input" : "/input/" + location;

            /* the same directory structure as GetData() */
            QString dataDir = root;
            if (d.flags.exportWriteSeriesDirectory) dataDir += "/<seriesnum>";
            if (d.flags.exportWritePhaseDirectory) dataDir += "/<phasedir>";

            /* the same as modulePipeline::GetBehPath() */
            QString behFormat = d.exportBehavioralDirectoryFormat;
            QString behDir;
            if (behFormat == "behroot") behDir = root;
            else if (behFormat == "behrootdir") behDir = root + "/" + d.exportBehavioralDirectoryName;
            else if (behFormat == "behseries") behDir = root + "/<seriesnum>";
            else if (behFormat == "behseriesdir") behDir = root + "/<seriesnum>/" + d.exportBehavioralDirectoryName;

            QString modality = d.searchModality.toUpper();
            QString format = d.flags.behOnly ? ". Behavioral data only, no imaging data" : QString(", format [%1%2]").arg(d.exportDataFormat).arg(d.flags.exportGzip ? ", gzipped" : "");

            QStringList stepNotes;
            if (d.flags.exportWriteSeriesDirectory) {
                if (d.flags.exportPreserveSeriesNumber)
                    stepNotes << "<seriesnum> is the series' original series number";
                else
                    stepNotes << "<seriesnum> is 1, 2, 3, ... for each matching series, in order";
            }
            if (d.flags.exportWritePhaseDirectory)
                stepNotes << "<phasedir> is the phase encoding direction: AP, PA, RL, LR, COL, ROW, or unknownPE";
            if (behDir != "")
                stepNotes << "Behavioral data in " + behDir;
            if (d.flags.isOptional)
                stepNotes << "Optional";

            QString key = QStringList({ dataDir, modality, format, stepNotes.join("\n") }).join("\t");
            int i = keys.indexOf(key);
            if (i >= 0) {
                if (!protocols[i].contains(d.searchProtocol))
                    protocols[i] << d.searchProtocol;
                continue;
            }

            QJsonObject o;
            o["type"] = "data";
            o["location"] = dataDir;
            o["modality"] = d.searchModality;
            o["format"] = d.exportDataFormat;
            o["gzip"] = d.flags.exportGzip;
            o["seriesDirectories"] = d.flags.exportWriteSeriesDirectory;
            o["preserveSeriesNumbers"] = d.flags.exportPreserveSeriesNumber;
            o["phaseDirectories"] = d.flags.exportWritePhaseDirectory;
            o["behavioralLocation"] = behDir;
            o["behavioralOnly"] = d.flags.behOnly;
            o["optional"] = d.flags.isOptional;

            keys << key;
            notes << stepNotes;
            protocols << QStringList(d.searchProtocol);
            entries << o;
        }

        for (int i = 0; i < entries.size(); i++) {
            QJsonObject o = entries[i];
            o["protocols"] = QJsonArray::fromStringList(protocols[i]);
            items.append(o);

            QStringList key = keys[i].split("\t");
            lines << key[0];
            lines << QString("    %1, %2 [%3]%4").arg(key[1]).arg(protocols[i].size() > 1 ? "protocols" : "protocol").arg(protocols[i].join("], [")).arg(key[2]);
            for (QString note : notes[i])
                lines << "    " + note;
        }
        if (items.size() == 0)
            lines << "This pipeline has no enabled data steps, so /input can be empty";
    }

    /* the dependency copy puts the parent's analysis directory in a subdirectory, or its contents in the root */
    for (int parentID : p.parentIDs) {
        pipeline parent(n);
        parent.searchPipelineRowID = parentID;
        parent.Load();
        if (!parent.isValid())
            continue;

        QString dir = (p.depDir == "subdir") ? "/input/" + parent.name : "/input";
        lines << dir;
        lines << QString("    The output (analysis directory) of parent pipeline [%1] for the same study").arg(parent.name);

        QJsonObject o;
        o["type"] = "parent";
        o["location"] = dir;
        o["pipeline"] = parent.name;
        items.append(o);
    }

    return lines;
}


/* ---------------------------------------------------------- */
/* --------- CreateReadme ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Create README.txt: what the container is, how to run it, and the expected /input layout. It stays next to
 * the .sif in the build directory and goes into the image as /pipeline/README.txt and the %help text (apptainer run-help)
 * @param p The pipeline
 * @param version Pipeline version
 * @param requiredVars The runtime variables the script uses
 * @param inputLayout The /input layout from GetInputDirStructure()
 * @return The text
 */
QString modulePipelineContainer::CreateReadme(pipeline &p, int version, const QStringList &requiredVars, const QStringList &inputLayout) {
    QString sif = GetContainerFileName(p, version);

    QStringList args;
    if (requiredVars.contains("subjectuid")) args << "--subjectuid S1234ABC";
    if (requiredVars.contains("studynum")) args << "--studynum 1";
    if (requiredVars.contains("studydatetime")) args << "--studydatetime \"2024-03-01 10:15:00\"";

    QStringList r;
    r << QString("NiDB pipeline [%1], version %2").arg(p.name).arg(version);
    if (p.desc.trimmed() != "")
        r << p.desc.trimmed();
    r << QString("Exported from NiDB on %1").arg(QDateTime::currentDateTime().toString("yyyy-MM-dd"));
    r << "";
    r << "USAGE";
    r << "Run once per subject/study. Bind the input data to /input and a writable directory to /output. The input is";
    r << "copied into /output and the pipeline runs there, so /output ends up looking like a NiDB analysis directory.";
    r << "";
    r << "    apptainer run --bind /data/sub01:/input --bind /results/sub01:/output " + sif + (args.size() > 0 ? " " + args.join(" ") : "");
    r << "";
    r << "ARGUMENTS";
    if (requiredVars.contains("subjectuid")) r << "    --subjectuid UID           Subject ID. Letters, numbers, _ and - only";
    if (requiredVars.contains("studynum")) r << "    --studynum N               Study number (integer)";
    if (requiredVars.contains("studydatetime")) r << "    --studydatetime DATETIME   Study date/time, \"YYYY-MM-DD hh:mm:ss\"";
    r << "    --stop-on-error            Optional. Stop at the first failing step. By default, like NiDB, later steps still run";
    r << "";
    r << "INPUT LAYOUT (/input)";
    r << "Lay out /input as NiDB would have staged the data for this pipeline. The container doesn't check the layout.";
    r << "";
    for (QString line : inputLayout)
        r << "    " + line;
    r << "";
    r << "MORE";
    r << "    apptainer run-help " + sif + "    # this text";
    r << "    apptainer inspect " + sif + "     # labels: pipeline name, version, base OS";
    r << "    /pipeline/manifest.json in the image has the same information, plus the software captured into the image";

    return r.join("\n") + "\n";
}


/* ---------------------------------------------------------- */
/* --------- StageReferenceInput ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Stage the reference analysis' input into <builddir>/input, the way modulePipeline stages a new analysis
 * (doc section 7 step 1): the data steps through modulePipeline::GetData(), then the parent pipeline's output.
 * Nothing is logged against the reference analysis. The data log is written to input.log
 * @param c The request
 * @param p The pipeline, loaded at the requested version
 * @param msg Error message, if any
 * @return true if the input was staged
 */
bool modulePipelineContainer::StageReferenceInput(containerRequest &c, pipeline &p, QString &msg) {

    /* local paths, since the staging runs on the NiDB server */
    analysis a(c.referenceAnalysisID, n);
    if (!a.isValid) {
        msg = QString("Unable to load reference analysis [%1]: %2").arg(c.referenceAnalysisID).arg(a.msg);
        return false;
    }
    study s(a.studyid, n);
    if (!s.isValid()) {
        msg = QString("Unable to load study [%1] of reference analysis [%2]: %3").arg(a.studyid).arg(c.referenceAnalysisID).arg(s.msg);
        return false;
    }

    QString inputDir = c.buildDir + "/input";
    if (!MakePath(inputDir, msg))
        return false;

    /* modulePipeline only uses the first parent */
    int pipelineDep = p.parentIDs.isEmpty() ? -1 : p.parentIDs[0];

    /* the data steps. Analysis ID -1, so GetData() doesn't log events or set the status on any analysis. Copied with
       cp even if the pipeline uses scp, since the build directory is local */
    modulePipeline mp(n);
    QList<dataDefinitionStep> dataSteps = mp.GetPipelineDataDef(p.GetPipelineRowID(), c.pipelineVersion);
    int numDownloaded = 0;
    QString dataLog;
    mp.GetData(s.GetStudyRowID(), inputDir, s.GetUID(), -1, p.GetPipelineRowID(), pipelineDep, p.depLevel, dataSteps, numDownloaded, dataLog, true);
    WriteTextFile(c.buildDir + "/input.log", dataLog + "\n", false);

    /* the rule modulePipeline uses to decide whether an analysis runs: some data, or a study-level parent */
    if ((numDownloaded < 1) && !((pipelineDep != -1) && (p.depLevel == "study"))) {
        msg = QString("No data found for the reference study [%1%2] using the data steps of version [%3]. See input.log in the build directory").arg(s.GetUID()).arg(s.GetStudyNum()).arg(c.pipelineVersion);
        return false;
    }
    AppendBuildLog(c.containerID, QString("Staged [%1] series from study [%2%3] into [%4]").arg(numDownloaded).arg(s.GetUID()).arg(s.GetStudyNum()).arg(inputDir));

    if (pipelineDep == -1)
        return true;

    /* the parent's output. A study-level parent is the same study; a subject-level parent is the subject's completed
       analysis nearest in time, as in modulePipeline */
    pipeline parent(n);
    parent.searchPipelineRowID = pipelineDep;
    if (!parent.Load()) {
        msg = QString("Unable to load parent pipeline [%1]: %2").arg(pipelineDep).arg(parent.msg);
        return false;
    }

    int parentStudyNum = s.GetStudyNum();
    if (p.depLevel == "subject") {
        QSqlQuery q;
        q.prepare("select b.study_num from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.subject_id = :subjectid and a.pipeline_id = :pipelinedep and a.analysis_status = 'complete' and (a.analysis_isbad <> 1 or a.analysis_isbad is null) order by abs(timestampdiff(minute, b.study_datetime, :studydatetime)) limit 1");
        q.bindValue(":subjectid", s.GetSubjectRowID());
        q.bindValue(":pipelinedep", pipelineDep);
        q.bindValue(":studydatetime", s.datetime.toString("yyyy-MM-dd hh:mm:ss"));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (!q.first()) {
            msg = QString("Subject [%1] has no completed analysis of parent pipeline [%2]").arg(s.GetUID()).arg(parent.name);
            return false;
        }
        parentStudyNum = q.value("study_num").toInt();
    }

    QString parentDir = mp.GetAnalysisLocalPath(parent.dirStructure, parent.name, s.GetUID(), parentStudyNum);
    if (!QDir(parentDir).exists()) {
        msg = QString("Parent pipeline [%1] analysis directory [%2] not found").arg(parent.name).arg(parentDir);
        return false;
    }

    /* always a regular copy, so the build directory doesn't depend on links. -u, as modulePipeline does, so the
       parent's files don't replace newer files from the data steps */
    QString targetDir = (p.depDir == "subdir") ? inputDir + "/" + parent.name : inputDir;
    if (!MakePath(targetDir, msg))
        return false;
    QProcess cp;
    cp.setProcessChannelMode(QProcess::MergedChannels);
    cp.start("cp", { "-au", parentDir + "/.", targetDir + "/" });
    if (!cp.waitForFinished(-1) || (cp.exitStatus() != QProcess::NormalExit) || (cp.exitCode() != 0)) {
        msg = QString("Unable to copy parent pipeline [%1] output from [%2] to [%3]: %4").arg(parent.name).arg(parentDir).arg(targetDir).arg(QString(cp.readAll()).trimmed());
        return false;
    }

    /* remove the logs and job files that came with the parent, as modulePipeline does. pipeline/ is kept, empty */
    QDir(inputDir + "/pipeline").removeRecursively();
    MakePath(inputDir + "/pipeline", msg);
    for (QString f : { "origfiles.log", "sge.job", "slurm.job" })
        QFile::remove(inputDir + "/" + f);

    AppendBuildLog(c.containerID, QString("Copied parent pipeline [%1] output from [%2] into [%3]").arg(parent.name).arg(parentDir).arg(targetDir));
    return true;
}


/* ---------------------------------------------------------- */
/* --------- WriteRunScript --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Write run.sh, the image's entry point (doc section 4). It's the same for every pipeline: it checks the
 * arguments against requiredvars.txt, copies /input to /output, renders run.sh.template and runs it in /output.
 * Must work with bash 4.2 and coreutils, since the base image may be minimal
 * @param c The request
 * @param msg Error message, if any
 * @return true if run.sh was written
 */
bool modulePipelineContainer::WriteRunScript(containerRequest &c, QString &msg) {
    static const QString script = R"BASH(#!/bin/bash
# Runs an exported NiDB pipeline: checks the arguments, copies /input to /output, renders
# /pipeline/run.sh.template with the arguments, and runs it in /output. Written by NiDB, and the same for every pipeline.
# For the build's reference run outside a container, NIDB_PIPELINE_DIR, NIDB_INPUT_DIR and NIDB_OUTPUT_DIR
# are used in place of /pipeline, /input and /output. Needs bash 4.2 or later and coreutils

PIPELINE_DIR="${NIDB_PIPELINE_DIR:-/pipeline}"
INPUT_DIR="${NIDB_INPUT_DIR:-/input}"
OUTPUT_DIR="${NIDB_OUTPUT_DIR:-/output}"

# bash 5.2 expands & in the replacement of ${var//pattern/replacement}. The values are checked below, but turn it off anyway
shopt -u patsub_replacement 2>/dev/null

die() {
    echo "run.sh: $*" >&2
    exit 2
}

usage() {
    echo 'Usage: run.sh [--subjectuid UID] [--studynum N] [--studydatetime "YYYY-MM-DD hh:mm:ss"] [--stop-on-error]'
    echo 'Run with --help to see which arguments this pipeline requires'
}

SUBJECTUID=""
STUDYNUM=""
STUDYDATETIME=""
STOPONERROR=0
while [ $# -gt 0 ]; do
    case "$1" in
        --subjectuid|--studynum|--studydatetime)
            [ $# -ge 2 ] || die "$1 needs a value"
            case "$1" in
                --subjectuid) SUBJECTUID="$2" ;;
                --studynum) STUDYNUM="$2" ;;
                --studydatetime) STUDYDATETIME="$2" ;;
            esac
            shift 2 ;;
        --stop-on-error)
            STOPONERROR=1
            shift ;;
        -h|--help)
            usage
            if [ -f "$PIPELINE_DIR/README.txt" ]; then
                echo
                cat "$PIPELINE_DIR/README.txt"
            fi
            exit 0 ;;
        *)
            usage >&2
            die "unknown argument [$1]" ;;
    esac
done

# the values are inserted into the script as text, so only these characters are accepted
re='^[A-Za-z0-9_-]+$'
if [ -n "$SUBJECTUID" ] && ! [[ $SUBJECTUID =~ $re ]]; then die "--subjectuid [$SUBJECTUID] can only contain letters, numbers, _ and -"; fi
re='^[0-9]+$'
if [ -n "$STUDYNUM" ] && ! [[ $STUDYNUM =~ $re ]]; then die "--studynum [$STUDYNUM] must be a number"; fi
re='^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$'
if [ -n "$STUDYDATETIME" ] && ! [[ $STUDYDATETIME =~ $re ]]; then die "--studydatetime [$STUDYDATETIME] must be in the format \"YYYY-MM-DD hh:mm:ss\""; fi

# the arguments this pipeline requires, one per line
[ -f "$PIPELINE_DIR/run.sh.template" ] || die "[$PIPELINE_DIR/run.sh.template] not found"
missing=""
if [ -f "$PIPELINE_DIR/requiredvars.txt" ]; then
    while IFS= read -r var || [ -n "$var" ]; do
        case "$var" in
            subjectuid) [ -n "$SUBJECTUID" ] || missing="$missing --subjectuid" ;;
            studynum) [ -n "$STUDYNUM" ] || missing="$missing --studynum" ;;
            studydatetime) [ -n "$STUDYDATETIME" ] || missing="$missing --studydatetime" ;;
            "") ;;
            *) die "unknown variable [$var] in [$PIPELINE_DIR/requiredvars.txt]" ;;
        esac
    done < "$PIPELINE_DIR/requiredvars.txt"
fi
if [ -n "$missing" ]; then
    usage >&2
    die "this pipeline requires$missing"
fi

# {analysisrootdir} is the output directory, and is also inserted as text
[ -d "$OUTPUT_DIR" ] || die "output directory [$OUTPUT_DIR] not found. Bind a directory to it, for example: apptainer run --bind <dir>:/output ..."
OUTPUT_DIR=$(cd "$OUTPUT_DIR" && pwd) || die "unable to cd into the output directory"
[ -w "$OUTPUT_DIR" ] || die "output directory [$OUTPUT_DIR] isn't writable"
re='^/[A-Za-z0-9_.+/-]*$'
[[ $OUTPUT_DIR =~ $re ]] || die "output directory path [$OUTPUT_DIR] can only contain letters, numbers, _ . + / and -"

# the pipeline runs in place, in a copy of the input, as it does in a NiDB analysis directory
if [ -d "$INPUT_DIR" ] && [ -n "$(ls -A "$INPUT_DIR" 2>/dev/null)" ]; then
    echo "== Copying [$INPUT_DIR] to [$OUTPUT_DIR]"
    cp -a "$INPUT_DIR/." "$OUTPUT_DIR/" || die "unable to copy [$INPUT_DIR] to [$OUTPUT_DIR]"
fi

# render the template. Variables that weren't given are left as they are; requiredvars.txt
# covers every line that runs, so those are only in comments and echo lines
RENDERED=$(mktemp "${TMPDIR:-/tmp}/run-rendered.XXXXXX") || die "unable to create a temporary file in [${TMPDIR:-/tmp}]"
trap 'rm -f "$RENDERED"' EXIT
while IFS= read -r line || [ -n "$line" ]; do
    line=${line//"{analysisrootdir}"/$OUTPUT_DIR}
    if [ -n "$SUBJECTUID" ]; then line=${line//"{subjectuid}"/$SUBJECTUID}; fi
    if [ -n "$STUDYNUM" ]; then line=${line//"{studynum}"/$STUDYNUM}; fi
    if [ -n "$STUDYDATETIME" ]; then line=${line//"{studydatetime}"/$STUDYDATETIME}; fi
    printf '%s\n' "$line"
done < "$PIPELINE_DIR/run.sh.template" > "$RENDERED" || die "unable to render [$PIPELINE_DIR/run.sh.template]"

# a failing step doesn't stop later steps, as in NiDB, unless --stop-on-error. The exit code is the last step's
cd "$OUTPUT_DIR" || die "unable to cd into [$OUTPUT_DIR]"
if [ $STOPONERROR -eq 1 ]; then
    bash -e "$RENDERED"
else
    bash "$RENDERED"
fi
rc=$?
echo "== Finished with exit code [$rc]"
exit $rc
)BASH";

    QString path = c.buildDir + "/run.sh";
    if (!WriteTextFile(path, script, false)) {
        msg = "Unable to write [" + path + "]";
        return false;
    }
    QFile::setPermissions(path, QFile::ReadOwner | QFile::WriteOwner | QFile::ExeOwner | QFile::ReadGroup | QFile::ExeGroup | QFile::ReadOther | QFile::ExeOther);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- WriteContainerBuildScript ---------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Write build-container.sh into the build directory. It's the only script that runs on the compute node
 * (doc section 8 step 4), and is bash so it runs on any node without Python or the nidb binary. It collects the
 * OS and software info into plain text files, runs the pipeline once under strace, builds the .sif, and optionally
 * runs the validation. It checks in to analysisapi.php at each major step (build_step), and its EXIT trap reports
 * the result: containercomplete with the .sif's path, size and sha256, or containererror. The .sif stays in the
 * build directory on the cluster. A new API token is created for each build; only its SHA-256 is stored, in
 * build_apitoken. The settings block at the top is generated for this build; the rest is the same for every build
 *
 * Files it writes into the build directory:
 *   build.log             everything the script prints
 *   build-warnings.txt    one warning per line, sent to build_warnings with the final report
 *   build-error.txt       the reason the build stopped, if it did
 *   manifest.json         manifest-export.json from NiDB, plus a 'build' object. Copied into the image
 *   sysinfo.txt           key=value: host, OS, base image, package manager, tool versions, cpus, memory, scratch space
 *   software.txt          key=value: interpreter paths and versions, package count
 *   env.txt, env.0        the login environment (env.0 is NUL separated, for capture_software)
 *   packages.txt          installed packages: name, version-release, arch (tab separated)
 *   modules.txt           'module list' output
 *   opt.txt               the entries in /opt
 *   trace.log             strace output from the reference run. reference.start marks when the run started
 *   capture/              capture_software's work files: the package file map, traced paths, copy list, tar and glibc logs
 *   captured-*.txt        what capture_software copied: packages, /opt directories, other paths
 *   capture-excluded.txt  deny-listed files that weren't copied
 *   environment.txt       the image's environment (.singularity.d/env/90-environment.sh)
 *   labels.txt            the image's labels, key and value tab separated (.singularity.d/labels.json)
 *   staging/              the captured software, laid over the base image
 *   reference-run.log     output of the reference run
 *   validation.txt        key=value summary, then the missing and extra file lists (validation only)
 *   <pipeline>-v<N>.sif   the container
 *
 * On success, the sandbox, staging tree, staged input and run outputs are removed, leaving the .sif, logs and reports
 *
 * @param c The request
 * @param p The pipeline
 * @param msg Error message, if any
 * @return true if written
 */
bool modulePipelineContainer::WriteContainerBuildScript(containerRequest &c, pipeline &p, QString &msg) {

    /* the reference analysis supplies the study values for the reference and validation runs */
    analysis a(c.referenceAnalysisID, n, true);
    if (!a.isValid) {
        msg = QString("Unable to load reference analysis [%1]").arg(c.referenceAnalysisID);
        return false;
    }
    if (a.pipelineid != c.pipelineID) {
        msg = QString("Reference analysis [%1] belongs to pipeline [%2], not this pipeline [%3]").arg(c.referenceAnalysisID).arg(a.pipelineid).arg(c.pipelineID);
        return false;
    }
    if ((a.status != "complete") || a.isBad) {
        msg = QString("Reference analysis [%1] must be complete and not marked bad. Its status is [%2]%3").arg(c.referenceAnalysisID).arg(a.status).arg(a.isBad ? " and it's marked bad" : "");
        return false;
    }

    /* these become arguments to run.sh, so they must pass run.sh's own checks (doc section 4) */
    QString studyDateTime = a.studyDateTime.left(19).replace('T', ' ');
    if (!QRegularExpression("^[A-Za-z0-9_-]+$").match(a.uid).hasMatch() || (a.studynum < 1) || !QRegularExpression("^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$").match(studyDateTime).hasMatch()) {
        msg = QString("Reference analysis [%1] has a subject UID [%2], study number [%3] or study date [%4] that run.sh won't accept").arg(c.referenceAnalysisID).arg(a.uid).arg(a.studynum).arg(studyDateTime);
        return false;
    }

    /* the reference analysis directory as the compute node sees it, for the validation comparison */
    modulePipeline mp(n);
    QString refAnalysisDir = mp.GetAnalysisClusterPath(p.dirStructure, p.name, a.uid, a.studynum);

    /* a new token for analysisapi.php. Only the SHA-256 is stored; the raw token is only in build-container.sh */
    QByteArray tokenBytes(32, 0);
    QRandomGenerator::system()->fillRange(reinterpret_cast<quint32*>(tokenBytes.data()), tokenBytes.size()/sizeof(quint32));
    QString apiToken = QString(tokenBytes.toHex());
    QSqlQuery q;
    q.prepare("update pipeline_containers set build_apitoken = :tokenhash where pipelinecontainer_id = :id");
    q.bindValue(":tokenhash", QString(QCryptographicHash::hash(apiToken.toUtf8(), QCryptographicHash::Sha256).toHex()));
    q.bindValue(":id", c.containerID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    QStringList settings;
    settings << "#!/bin/bash -l";
    settings << QString("# build-container.sh, generated by NiDB for pipeline_containers row [%1] on %2").arg(c.containerID).arg(QDateTime::currentDateTime().toString("yyyy-MM-dd HH:mm:ss"));
    settings << "# Runs on a compute node and builds an Apptainer container from a NiDB pipeline. See doc/pipeline-apptainer.md section 8";
    settings << "# Must work with bash 4.2 (CentOS 7) and only standard tools, plus strace, objdump and apptainer";
    settings << "";
    settings << "# ---------- settings for this build ----------";
    settings << "CONTAINER_ID=" + QString::number(c.containerID);
    settings << "PIPELINE_NAME=" + ShellQuote(p.name);
    settings << "PIPELINE_VERSION=" + QString::number(c.pipelineVersion);
    settings << "BUILDDIR=" + ShellQuote(GetClusterBuildDir(c.containerID));
    settings << "SIF_NAME=" + ShellQuote(GetContainerFileName(p, c.pipelineVersion));
    settings << "REF_ANALYSISDIR=" + ShellQuote(refAnalysisDir);
    settings << "REF_SUBJECTUID=" + ShellQuote(a.uid);
    settings << "REF_STUDYNUM=" + QString::number(a.studynum);
    settings << "REF_STUDYDATETIME=" + ShellQuote(studyDateTime);
    settings << QString("RUN_VALIDATION=%1").arg(c.runValidation ? 1 : 0);
    /* not exported, so the pipeline doesn't see them and they don't end up in env.txt or the image */
    settings << "NIDB_APIURL=" + ShellQuote(n->cfg["siteurl"].trimmed() + "/analysisapi.php");
    settings << "NIDB_APITOKEN=" + ShellQuote(apiToken);
    /* TODO: from the pipeline's container settings, once they exist. A docker:// URI or the path of a .sif */
    settings << "BASE_OVERRIDE=''";
    settings << "";

    /* the same for every build */
    static const char *body = R"BASH(# ---------- fixed part ----------
set -u

# bash 5.2 expands & in the replacement of ${var//pattern/replacement}. json_str quotes its replacements, but turn it off anyway
shopt -u patsub_replacement 2>/dev/null

APPTAINER=""
MISSING_TOOLS=()
INTERPRETERS="R Rscript perl python python3 java tcsh csh matlab octave"
CURRENT_STEP=""
SIF_SIZE=""; SIF_SHA256=""
BUILD_SIZE=""; BUILD_FILECOUNT=""
OS_ID=""; OS_VERSION_ID=""; OS_ID_LIKE=""; OS_PRETTY_NAME=""; OS_FAMILY="unknown"
PKG_MANAGER="none"
BASE_IMAGE=""

log()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }
warn() { log "WARNING: $*"; echo "$*" >> "$BUILDDIR/build-warnings.txt"; }
fail() { log "ERROR: $*"; echo "$*" >> "$BUILDDIR/build-error.txt"; exit 1; }

# safe_rm <path>...: rm -rf, but only for paths inside the build directory. Refuses if the build directory isn't an absolute
# path at least two levels deep, or a path has '..' in it. Read-only directories (sandboxes) are made writable first
safe_rm() {
    local p
    case "$BUILDDIR" in /?*/?*) ;; *) fail "Refusing to delete files: the build directory [$BUILDDIR] isn't a safe path" ;; esac
    case "/$BUILDDIR/" in */../*|*/./*) fail "Refusing to delete files: the build directory [$BUILDDIR] has . or .. in it" ;; esac
    for p in "$@"; do
        case "$p" in "$BUILDDIR"/?*) ;; *) fail "Refusing to delete [$p], which isn't inside the build directory [$BUILDDIR]" ;; esac
        case "/$p/" in */../*|*/./*) fail "Refusing to delete [$p], which has . or .. in it" ;; esac
        if [ -d "$p" ] && [ ! -L "$p" ]; then chmod -R u+w -- "$p" 2>/dev/null; fi
        rm -rf -- "$p"
    done
}

# kv <file> <key> <value>: one key=value line. Newlines in the value are flattened
kv() { printf '%s=%s\n' "$2" "$(printf '%s' "$3" | tr '\r\n' '  ')" >> "$1"; }

# kv_get <file> <key>: the value of a key=value line
kv_get() { awk -v k="$2" 'index($0, k "=") == 1 { print substr($0, length(k) + 2); exit }' "$1" 2>/dev/null; }

# json_str <text>: the text as a JSON string. Other control characters are removed
json_str() {
    local s="$1" bs='\' q='"'
    s=${s//"$bs"/"$bs$bs"}
    s=${s//"$q"/"$bs$q"}
    s=${s//$'\t'/'\t'}
    s=${s//$'\r'/'\r'}
    s=${s//$'\n'/'\n'}
    s=$(printf '%s' "$s" | LC_ALL=C tr -d '\000-\010\013\014\016-\037\177')
    printf '"%s"' "$s"
}

# json_lines <file> <indent>: the file's non-blank lines as a JSON array of strings. [] if there's no file
json_lines() {
    local line sep=""
    printf '['
    if [ -f "$1" ]; then
        while IFS= read -r line || [ -n "$line" ]; do
            if [ -z "$line" ]; then continue; fi
            printf '%s\n%s    %s' "$sep" "$2" "$(json_str "$line")"
            sep=","
        done < "$1"
    fi
    if [ -n "$sep" ]; then printf '\n%s]' "$2"; else printf ']'; fi
}

# first line of a command's output, with a time limit so a hung tool can't stall the build
firstline() { timeout 60 "$@" 2>&1 | head -n 1; }

# call analysisapi.php. Any arguments are passed to curl. Logs the response and returns non-zero if the call failed
nidbapi() {
    local response
    response=$(curl -sS --connect-timeout 30 --max-time 300 --retry 3 "$NIDB_APIURL" -d "containerid=$CONTAINER_ID" --data-urlencode "token=$NIDB_APITOKEN" --data-urlencode "hostname=$(hostname)" "$@" 2>&1)
    if printf '%s' "$response" | grep -q '"success":true'; then return 0; fi
    log "NiDB check-in failed: $response"
    return 1
}

# checkin <step> <message>: report a major build step (build_step). A failed check-in doesn't stop the build
checkin() {
    CURRENT_STEP="$1"
    log "Step [$1]: $2"
    nidbapi -d action=containercheckin -d "step=$1" --data-urlencode "message=$2"
}

# build_dir_stats: what's left in the build directory, as BUILD_SIZE (bytes on disk) and BUILD_FILECOUNT (everything but
# directories). Each is left empty if it fails or takes over 2 minutes, rather than reporting a partial count
build_dir_stats() {
    BUILD_SIZE=$(set -o pipefail; timeout 120 du -sB1 -- "$BUILDDIR" 2>/dev/null | awk '{ print $1 }') || BUILD_SIZE=""
    BUILD_FILECOUNT=$(set -o pipefail; timeout 120 find "$BUILDDIR" ! -type d -printf 'x' 2>/dev/null | wc -c) || BUILD_FILECOUNT=""
    log "Build directory [$BUILDDIR] has [${BUILD_FILECOUNT:-unknown}] files, [${BUILD_SIZE:-unknown}] bytes"
}

# the EXIT trap, so the result is reported even on failure or SIGTERM from the scheduler. This is the job's last API call,
# since it clears the token
finish() {
    local rc=$? msg
    log "build-container.sh finished with exit code [$rc]"
    # after finalize's cleanup on success. On error everything is kept, so this shows how much is left to clean up. Skipped
    # on SIGTERM, since the scheduler may follow with SIGKILL and the result has to be reported first
    if [ $rc -ne 143 ]; then build_dir_stats; fi
    if [ $rc -eq 0 ]; then
        nidbapi -d action=containercomplete --data-urlencode "path=$BUILDDIR/$SIF_NAME" -d "size=$SIF_SIZE" -d "sha256=$SIF_SHA256" \
            -d "buildsize=$BUILD_SIZE" -d "buildfilecount=$BUILD_FILECOUNT" \
            --data-urlencode "message=Container built on $(hostname)" --data-urlencode "warnings@$BUILDDIR/build-warnings.txt" \
            || log "ERROR: the container was built, but NiDB wasn't told. It's [$BUILDDIR/$SIF_NAME]"
    else
        msg="build-container.sh exited with code [$rc]"
        if [ -s "$BUILDDIR/build-error.txt" ]; then msg="$(tail -n 1 "$BUILDDIR/build-error.txt")"; fi
        nidbapi -d action=containererror -d "step=$CURRENT_STEP" --data-urlencode "message=$msg" --data-urlencode "warnings@$BUILDDIR/build-warnings.txt" \
            -d "buildsize=$BUILD_SIZE" -d "buildfilecount=$BUILD_FILECOUNT"
    fi
}

# find the tools. Missing ones stop the build in check_tools, after the system info is collected
find_tools() {
    local t
    for t in strace objdump ldd find timeout sha256sum stat tar awk readlink getconf; do
        command -v "$t" >/dev/null 2>&1 || MISSING_TOOLS+=("$t")
    done
    if command -v apptainer >/dev/null 2>&1; then APPTAINER="apptainer"
    elif command -v singularity >/dev/null 2>&1; then APPTAINER="singularity"
    else MISSING_TOOLS+=("apptainer (or singularity)")
    fi
}

check_tools() {
    if [ ${#MISSING_TOOLS[@]} -gt 0 ]; then
        fail "Required tools missing on $(hostname): ${MISSING_TOOLS[*]}"
    fi
}

# OS, family, package manager and base image (doc section 7, step 3 and 'Supported OSes')
detect_os() {
    if [ -r /etc/os-release ]; then
        eval "$(. /etc/os-release; printf 'OS_ID=%q; OS_VERSION_ID=%q; OS_ID_LIKE=%q; OS_PRETTY_NAME=%q' "${ID:-}" "${VERSION_ID:-}" "${ID_LIKE:-}" "${PRETTY_NAME:-}")"
    else
        warn "/etc/os-release not found, so the OS can't be detected"
    fi

    case " $OS_ID $OS_ID_LIKE " in
        *" rhel "*|*" centos "*|*" fedora "*) OS_FAMILY="rhel" ;;
        *" debian "*|*" ubuntu "*) OS_FAMILY="debian" ;;
        *" suse "*|*" sles "*|*" opensuse "*) OS_FAMILY="suse" ;;
    esac

    if command -v rpm >/dev/null 2>&1 && rpm -q coreutils >/dev/null 2>&1; then
        PKG_MANAGER="rpm"
    elif command -v dpkg-query >/dev/null 2>&1 && dpkg-query -W coreutils >/dev/null 2>&1; then
        PKG_MANAGER="dpkg"
    else
        warn "No usable package manager (rpm or dpkg) found. Software is captured from the trace and ldd only, which is less complete"
    fi

    local major="${OS_VERSION_ID%%.*}"
    if [ -n "$BASE_OVERRIDE" ]; then
        BASE_IMAGE="$BASE_OVERRIDE"
    elif [ -n "$major" ]; then
        case "$OS_ID" in
            centos)        if [ "$major" = "7" ]; then BASE_IMAGE="docker://centos:7.9.2009"; fi ;;
            rhel)          if [ "$major" = "7" ]; then BASE_IMAGE="docker://centos:7.9.2009"; else BASE_IMAGE="docker://rockylinux:$major"; fi ;;
            rocky)         BASE_IMAGE="docker://rockylinux:$major" ;;
            almalinux)     BASE_IMAGE="docker://almalinux:$major" ;;
            debian)        BASE_IMAGE="docker://debian:$major" ;;
            ubuntu)        BASE_IMAGE="docker://ubuntu:$OS_VERSION_ID" ;;
            opensuse-leap) BASE_IMAGE="docker://opensuse/leap:$OS_VERSION_ID" ;;
        esac
    fi
}

# end-of-life OSes (as of 2026) get no security updates, so the image carries unpatched CVEs (doc section 7)
check_eol() {
    local major="${OS_VERSION_ID%%.*}" eol=0
    case "$major" in ''|*[!0-9]*) major=0 ;; esac
    case "$OS_ID" in
        centos)                [ "$major" -le 8 ] && eol=1 ;;
        rhel|rocky|almalinux)  [ "$major" -le 7 ] && eol=1 ;;
        debian)                [ "$major" -le 11 ] && eol=1 ;;
        ubuntu)                case "$OS_VERSION_ID" in 1[0-9].*|20.*) eol=1 ;; esac ;;
        opensuse-leap)         case "$OS_VERSION_ID" in 42.*|15.*) eol=1 ;; esac ;;
    esac
    if [ $eol -eq 1 ]; then warn "[$OS_PRETTY_NAME] is end-of-life, so the image has no security updates. Some institutions' scanners or HPC centers may block it"; fi
    return 0
}

collect_sysinfo() {
    local f="$BUILDDIR/sysinfo.txt"
    local tmp="${APPTAINER_TMPDIR:-${TMPDIR:-/tmp}}"
    : > "$f"
    kv "$f" hostname "$(hostname)"
    kv "$f" date "$(date '+%Y-%m-%d %H:%M:%S')"
    kv "$f" user "$(id -un)"
    kv "$f" jobid "${SLURM_JOB_ID:-${JOB_ID:-}}"
    kv "$f" kernel "$(uname -r)"
    kv "$f" arch "$(uname -m)"
    kv "$f" os_id "$OS_ID"
    kv "$f" os_version_id "$OS_VERSION_ID"
    kv "$f" os_pretty_name "$OS_PRETTY_NAME"
    kv "$f" os_family "$OS_FAMILY"
    kv "$f" package_manager "$PKG_MANAGER"
    kv "$f" base_image "$BASE_IMAGE"
    kv "$f" glibc "$(firstline ldd --version)"
    kv "$f" bash "$BASH_VERSION"
    kv "$f" apptainer "$(if [ -n "$APPTAINER" ]; then firstline "$APPTAINER" --version; fi)"
    kv "$f" strace "$(if command -v strace >/dev/null 2>&1; then firstline strace -V; fi)"
    kv "$f" objdump "$(if command -v objdump >/dev/null 2>&1; then firstline objdump --version; fi)"
    kv "$f" cpus "$(nproc 2>/dev/null)"
    kv "$f" memory_kb "$(awk '/^MemTotal:/ {print $2}' /proc/meminfo 2>/dev/null)"
    kv "$f" ptrace_scope "$(cat /proc/sys/kernel/yama/ptrace_scope 2>/dev/null)"
    kv "$f" tmpdir "$tmp"
    kv "$f" tmpdir_free_kb "$(df -Pk "$tmp" 2>/dev/null | awk 'NR==2 {print $4}')"
    kv "$f" builddir_free_kb "$(df -Pk "$BUILDDIR" 2>/dev/null | awk 'NR==2 {print $4}')"
}

# an inventory of the node's software. This is a record for the manifest; the files themselves
# are captured from the trace in capture_software
collect_software() {
    local f="$BUILDDIR/software.txt" t path
    : > "$f"
    for t in $INTERPRETERS; do
        path="$(command -v "$t" 2>/dev/null)"
        if [ -z "$path" ]; then continue; fi
        kv "$f" "$t.path" "$path"
        case "$t" in
            R|Rscript|python|python3) kv "$f" "$t.version" "$(firstline "$t" --version)" ;;
            perl) kv "$f" "$t.version" "$(timeout 60 perl -e 'print $^V' 2>&1)" ;;
            java) kv "$f" "$t.version" "$(firstline java -version)" ;;
        esac
    done

    case "$PKG_MANAGER" in
        rpm)  rpm -qa --qf '%{NAME}\t%{VERSION}-%{RELEASE}\t%{ARCH}\n' 2>/dev/null | LC_ALL=C sort > "$BUILDDIR/packages.txt" ;;
        dpkg) dpkg-query -W -f '${Package}\t${Version}\t${Architecture}\n' 2>/dev/null | LC_ALL=C sort > "$BUILDDIR/packages.txt" ;;
        *)    : > "$BUILDDIR/packages.txt" ;;
    esac
    kv "$f" packages.count "$(wc -l < "$BUILDDIR/packages.txt")"

    # 'module' is a shell function, only defined in a login shell
    if type module >/dev/null 2>&1; then
        module list > "$BUILDDIR/modules.txt" 2>&1
    else
        : > "$BUILDDIR/modules.txt"
    fi

    if [ -d /opt ]; then
        find /opt -mindepth 1 -maxdepth 1 -printf '%f\n' 2>/dev/null | LC_ALL=C sort > "$BUILDDIR/opt.txt"
    else
        : > "$BUILDDIR/opt.txt"
    fi
}

# run the pipeline once under strace, outside a container (doc section 7, step 1). run.sh reads
# NIDB_PIPELINE_DIR, NIDB_INPUT_DIR and NIDB_OUTPUT_DIR in place of /pipeline, /input and /output. This script already runs in a
# login shell, so run.sh gets the login environment without being a login shell itself. Otherwise the trace would include
# every /etc/profile.d script and the commands they run, and capture_software would copy their packages
reference_run() {
    local scratch="$BUILDDIR/reference" rc
    if [ ! -f "$BUILDDIR/run.sh" ]; then fail "run.sh is missing from the build directory"; fi
    safe_rm "$scratch"
    mkdir -p "$scratch" || fail "Unable to create [$scratch]"

    # files changed after this are the run's output, not software (capture_software)
    touch "$BUILDDIR/reference.start" || fail "Unable to create [$BUILDDIR/reference.start]"
    sleep 1
    log "Reference run: subject [$REF_SUBJECTUID] study [$REF_STUDYNUM] [$REF_STUDYDATETIME]"
    NIDB_PIPELINE_DIR="$BUILDDIR" NIDB_INPUT_DIR="$BUILDDIR/input" NIDB_OUTPUT_DIR="$scratch" \
        strace -f -s 4096 -e trace=file,process -o "$BUILDDIR/trace.log" \
        bash "$BUILDDIR/run.sh" --subjectuid "$REF_SUBJECTUID" --studynum "$REF_STUDYNUM" --studydatetime "$REF_STUDYDATETIME" > "$BUILDDIR/reference-run.log" 2>&1
    rc=$?
    log "Reference run finished with exit code [$rc]"

    if [ ! -s "$BUILDDIR/trace.log" ]; then fail "The reference run produced no trace. Check that strace works on this node (ptrace_scope in sysinfo.txt)"; fi
    if [ $rc -ne 0 ]; then warn "The reference run exited with code [$rc], so the trace may not include everything the pipeline uses. See reference-run.log"; fi
}

# ---------- software capture (doc section 7, step 2) ----------
# Paths are kept as the pipeline saw them, except the merged-/usr links (/lib64 -> usr/lib64, ...), which are resolved so a
# file has one name and the staging tree never has a real directory where the base image has a link. Work files go in
# capture/. Outputs: staging/, captured-packages.txt, captured-opt.txt, captured-paths.txt, environment.txt, capture-excluded.txt

CAP=""; STAGING=""; REF_START=""; FILEMAP=""; HOME_DIR=""; CANON_RESULT=""; ROOT_RESULT=""
declare -A TOPLINK=()      # top-level link -> target, for example /lib64 -> /usr/lib64
declare -A SEEN=()         # paths already classified
declare -A PKG_SEEN=()     # name.arch of packages already captured
declare -A TREE_SEEN=()    # /opt directories, library roots and script directories already captured
declare -A ELF_SEEN=()     # files already checked for ELF and run through ldd
declare -A DIR_CHECKED=()  # is_script_dir() results
declare -A OPT_SPLIT=()    # /opt/<name> directories where only the install the trace used is captured (find_opt_installs)
EXCLUDE_PREFIXES=()
LIB_ROOTS=()

# canon <path>: resolve a leading merged-/usr link. Sets CANON_RESULT
canon() {
    local p="$1" top
    top="${1#/}"; top="/${top%%/*}"
    if [ "$top" != "$p" ] && [ -n "${TOPLINK[$top]+x}" ]; then
        CANON_RESULT="${TOPLINK[$top]}${p#"$top"}"
    else
        CANON_RESULT="$p"
    fi
}

# normalize <path>: remove . and .. and repeated slashes, without touching the filesystem. Sets CANON_RESULT
normalize() {
    local part out=""
    local -a parts
    IFS=/ read -r -a parts <<< "$1"
    for part in ${parts[@]+"${parts[@]}"}; do
        case "$part" in
            ''|.) ;;
            ..) out="${out%/*}" ;;
            *) out="$out/$part" ;;
        esac
    done
    CANON_RESULT="${out:-/}"
}

# link_target <symlink>: where a symlink points, followed one link at a time without resolving the directories it's in, so
# /opt/x/bin/tool -> ../lib/tool stays under /opt/x even if /opt is itself a link. Sets CANON_RESULT, empty if dangling
link_target() {
    local p="$1" t i
    for i in 1 2 3 4 5 6 7 8 9 10; do
        t="$(readlink -- "$p" 2>/dev/null)" || { CANON_RESULT=""; return; }
        case "$t" in /*) ;; *) t="${p%/*}/$t" ;; esac
        normalize "$t"; canon "$CANON_RESULT"; p="$CANON_RESULT"
        if [ ! -L "$p" ]; then
            if [ -e "$p" ]; then CANON_RESULT="$p"; else CANON_RESULT=""; fi
            return
        fi
    done
    CANON_RESULT=""
}

# never captured: kernel interfaces, scratch space, the build directory, and the dot files in the home directory
is_excluded() {
    local x
    case "$1" in
        /proc|/proc/*|/sys|/sys/*|/dev|/dev/*|/run|/run/*|/tmp|/tmp/*|/var/tmp|/var/tmp/*|/var/run/*|/var/lock/*) return 0 ;;
    esac
    for x in ${EXCLUDE_PREFIXES[@]+"${EXCLUDE_PREFIXES[@]}"}; do
        case "$1" in "$x"|"$x"/*) return 0 ;; esac
    done
    if [ -n "$HOME_DIR" ]; then
        case "$1" in
            "$HOME_DIR"/.local|"$HOME_DIR"/.local/*) return 1 ;;
            "$HOME_DIR"/.*) return 0 ;;
        esac
    fi
    return 1
}

# the deny list: never copied, even if the pipeline used them. Listed in capture-excluded.txt
is_denied() {
    case "$1" in
        /etc/shadow|/etc/shadow-|/etc/gshadow|/etc/gshadow-|/etc/munge|/etc/munge/*|/etc/sssd|/etc/sssd/*|/etc/ssh/*_key|*.keytab) return 0 ;;
        */.ssh|*/.ssh/*|*/.gnupg|*/.gnupg/*|*/.aws|*/.aws/*|*/.netrc|*/.pgpass|*/.my.cnf|*/nidb.cfg) return 0 ;;
        */[Ff]ree[Ss]urfer*/license.txt|*/[Ff]ree[Ss]urfer*/.license) return 0 ;;
        */[Mm][Aa][Tt][Ll][Aa][Bb]*/licenses/*) return 0 ;;
    esac
    return 1
}

# files Apptainer provides at run time or that describe this node rather than the software, the node's logs and caches, and
# documentation. Skipped silently
is_runtime_file() {
    case "$1" in
        /etc/passwd|/etc/passwd-|/etc/group|/etc/group-|/etc/hosts|/etc/resolv.conf|/etc/hostname|/etc/localtime|/etc/machine-id|/etc/fstab|/etc/mtab|/etc/nsswitch.conf) return 0 ;;
        /var/log|/var/log/*|/var/cache|/var/cache/*) return 0 ;;
        /usr/share/doc/*|/usr/share/man/*|/usr/share/info/*) return 0 ;;
    esac
    return 1
}

# the base image's own skeleton packages. Copying them would replace the image's /etc/passwd, top-level links, ...
is_skeleton_package() {
    case "$1" in
        filesystem|setup|basesystem|base-files|base-passwd|kernel|kernel-core|kernel-modules*|linux-image*) return 0 ;;
    esac
    return 1
}

# add_candidate <path>: print the path if it's new and should be classified
add_candidate() {
    local c="$1"
    if [ -z "$c" ] || [ -n "${SEEN[$c]+x}" ]; then return; fi
    SEEN[$c]=1
    if is_excluded "$c" || is_runtime_file "$c"; then return; fi
    if is_denied "$c"; then echo "$c" >> "$BUILDDIR/capture-excluded.txt"; return; fi
    if [ -d "$c" ] && [ ! -L "$c" ]; then
        # a directory under /opt means its /opt/<name> was used. Other directories are only parents
        case "$c" in /opt/?*) echo "$c" ;; esac
        return
    fi
    if [ ! -f "$c" ] && [ ! -L "$c" ]; then return; fi
    # written by the reference run: output, not software
    if [ "$c" -nt "$REF_START" ]; then return; fi
    echo "$c"
}

# prepare_paths <in> <out>: the new paths to classify, canonical and filtered. A symlink's target is added too
prepare_paths() {
    local p
    while IFS= read -r p || [ -n "$p" ]; do
        case "$p" in /*) ;; *) continue ;; esac
        normalize "$p"; canon "$CANON_RESULT"; p="$CANON_RESULT"
        add_candidate "$p"
        if [ -L "$p" ]; then
            link_target "$p"
            add_candidate "$CANON_RESULT"
        fi
    done < "$1" > "$2"
}

# every installed file and the package that owns it: path, name, version-release, arch (tab separated), with the merged-/usr
# links resolved the same way as the traced paths
build_file_map() {
    local links="" l
    FILEMAP="$CAP/filemap.txt"
    for l in "${!TOPLINK[@]}"; do links="$links$l=${TOPLINK[$l]};"; done
    case "$PKG_MANAGER" in
        rpm)  rpm -qa --qf '[%{FILENAMES}\t%{=NAME}\t%{=VERSION}-%{=RELEASE}\t%{=ARCH}\n]' 2>/dev/null ;;
        dpkg)
            dpkg-query -W -f '${binary:Package}\t${Version}\t${Architecture}\n' 2>/dev/null > "$CAP/dpkg-packages.txt"
            find /var/lib/dpkg/info -maxdepth 1 -name '*.list' -print0 2>/dev/null | xargs -0 -r awk -v pkgs="$CAP/dpkg-packages.txt" '
                BEGIN { FS = "\t"; while ((getline line < pkgs) > 0) { split(line, f, "\t"); v[f[1]] = f[2]; a[f[1]] = f[3] } }
                FNR == 1 { k = FILENAME; sub(/.*\//, "", k); sub(/\.list$/, "", k); n = k; sub(/:.*/, "", n) }
                (k in v) && /^\/./ { print $0 "\t" n "\t" v[k] "\t" a[k] }' ;;
        *)    : ;;
    esac | awk -v links="$links" '
        BEGIN { FS = OFS = "\t"; n = split(links, l, ";"); for (i = 1; i <= n; i++) if (l[i] != "") { split(l[i], kv, "="); from[i] = kv[1]; to[i] = kv[2] } }
        $1 ~ /^\// {
            for (i in from) if (index($1, from[i] "/") == 1) { $1 = to[i] substr($1, length(from[i]) + 1); break }
            print
        }' > "$FILEMAP"
    log "Package file map: $(wc -l < "$FILEMAP") files (package manager [$PKG_MANAGER])"
}

# env_get <name>: a variable's value in the login environment (env.0)
env_get() {
    local kv
    while IFS= read -r -d '' kv; do
        case "$kv" in "$1="*) printf '%s' "${kv#*=}"; return ;; esac
    done < "$BUILDDIR/env.0"
}

# language library roots: the OS family's, plus R_LIBS*, PERL5LIB and PYTHONPATH from the login environment. Longest first,
# so a path is matched to the most specific root
find_library_roots() {
    local r v
    local -a candidates=() parts
    case "$OS_FAMILY" in
        rhel|suse) candidates=(/usr/lib64/R/library /usr/local/lib64/R/library /usr/local/share/perl5 /usr/local/lib64/perl5 /usr/local/lib/python*/site-packages /usr/local/lib64/python*/site-packages) ;;
        debian)    candidates=(/usr/local/lib/R/site-library /usr/lib/R/site-library /usr/local/share/perl/* /usr/local/lib/x86_64-linux-gnu/perl/* /usr/local/lib/python*/dist-packages) ;;
    esac
    for v in R_LIBS R_LIBS_SITE R_LIBS_USER PERL5LIB PYTHONPATH; do
        IFS=: read -r -a parts <<< "$(env_get "$v")"
        candidates+=(${parts[@]+"${parts[@]}"})
    done
    for r in ${candidates[@]+"${candidates[@]}"}; do
        case "$r" in /?*) ;; *) continue ;; esac
        normalize "$r"; canon "$CANON_RESULT"; r="$CANON_RESULT"
        if [ -d "$r" ] && ! is_excluded "$r"; then printf '%s\t%s\n' "${#r}" "$r"; fi
    done | sort -t$'\t' -k1,1rn -k2 -u | cut -f2- > "$CAP/library-roots.txt"
    while IFS= read -r r; do LIB_ROOTS+=("$r"); done < "$CAP/library-roots.txt"
    log "Library roots: $(tr '\n' ' ' < "$CAP/library-roots.txt")"
}

# lib_root_for <path>: the library root the path is in. Sets ROOT_RESULT, empty if none
lib_root_for() {
    local r
    ROOT_RESULT=""
    for r in ${LIB_ROOTS[@]+"${LIB_ROOTS[@]}"}; do
        case "$1" in "$r"/*) ROOT_RESULT="$r"; return ;; esac
    done
}

# is_script_dir <dir>: a directory the step commands name, other than system directories and the home directory itself
is_script_dir() {
    case "$1" in
        ''|/|/usr|/usr/*|/bin|/sbin|/lib*|/etc|/etc/*|/var|/var/*|/opt|/home) return 1 ;;
    esac
    if [ "$1" = "$HOME_DIR" ]; then return 1; fi
    if [ -z "${DIR_CHECKED[$1]+x}" ]; then
        if grep -qF -- "$1/" "$BUILDDIR/run.sh.template"; then DIR_CHECKED[$1]=1; else DIR_CHECKED[$1]=0; fi
    fi
    [ "${DIR_CHECKED[$1]}" = "1" ]
}

# add_tree <dir> <list>: copy a whole directory, and record it in <list>. Its files go through ldd and the glibc check
add_tree() {
    if [ -n "${TREE_SEEN[$1]+x}" ]; then return; fi
    TREE_SEEN[$1]=1
    echo "$1" >> "$2"
    log "Capturing directory [$1] ($(du -sh "$1" 2>/dev/null | cut -f1))"
    find -H "$1" \( -type f -o -type l -o -type d \) -print 2>/dev/null >> "$CAP/copy.txt"
    find -H "$1" -type f \( -perm -u+x -o -name '*.so' -o -name '*.so.*' \) -print 2>/dev/null >> "$CAP/elf-other.txt"
}

# opt_child <path>: for a path under /opt, sets OPT_NAME to <name> and OPT_CHILD to /opt/<name>/<child>, the entry in
# /opt/<name> the path is in. OPT_CHILD is empty for /opt/<name> itself. If the entry is a link to a directory in
# /opt/<name>, OPT_CHILD is the directory and OPT_LINK the link; OPT_LINK is empty otherwise
opt_child() {
    local rest
    rest="${1#/opt/}"; OPT_NAME="${rest%%/*}"; OPT_CHILD=""; OPT_LINK=""
    if [ "$rest" = "$OPT_NAME" ]; then return; fi
    rest="${rest#*/}"
    OPT_CHILD="/opt/$OPT_NAME/${rest%%/*}"
    if [ -L "$OPT_CHILD" ]; then
        link_target "$OPT_CHILD"
        case "$CANON_RESULT" in
            /opt/"$OPT_NAME"/?*)
                rest="${CANON_RESULT#/opt/"$OPT_NAME"/}"
                if [ -d "/opt/$OPT_NAME/${rest%%/*}" ]; then OPT_LINK="$OPT_CHILD"; OPT_CHILD="/opt/$OPT_NAME/${rest%%/*}"; fi ;;
        esac
    fi
}

# find_opt_installs <paths>: /opt/<name> directories that hold several installs side by side, like /opt/fsl/fsl-5.0.11 and
# /opt/fsl/fsl-6.0.3. If every traced path under /opt/<name> is in one subdirectory (directly or through a link in
# /opt/<name>), and that subdirectory is an install root itself (it has a bin or lib directory), only it is captured.
# Otherwise the whole /opt/<name> is, since a program's own bin, lib, share, ... are siblings. Sets OPT_SPLIT
find_opt_installs() {
    local p n
    local -A first=() blocked=()
    while IFS= read -r p || [ -n "$p" ]; do
        case "$p" in /opt/?*) ;; *) continue ;; esac
        opt_child "$p"
        n="$OPT_NAME"
        if [ -z "$OPT_CHILD" ] || [ -n "${blocked[$n]+x}" ]; then continue; fi
        if [ ! -d "$OPT_CHILD" ] || [ -L "$OPT_CHILD" ]; then blocked[$n]=1; continue; fi
        if [ -z "${first[$n]+x}" ]; then first[$n]="$OPT_CHILD"; elif [ "${first[$n]}" != "$OPT_CHILD" ]; then blocked[$n]=1; fi
    done < "$1"
    for n in ${first[@]+"${!first[@]}"}; do
        if [ -n "${blocked[$n]+x}" ]; then continue; fi
        p="${first[$n]}"
        if [ -d "$p/bin" ] || [ -d "$p/lib" ] || [ -d "$p/lib64" ]; then
            OPT_SPLIT[$n]=1
            log "[/opt/$n] has more than one directory, and the reference run only used [${p##*/}], so only [$p] is captured"
        fi
    done
}

# classify <paths>: sort each path into a package, an /opt directory, a library root, a script directory or a single file
classify() {
    local p n v a d
    : > "$CAP/owned.txt"; : > "$CAP/unowned.txt"
    awk -v owned="$CAP/owned.txt" -v unowned="$CAP/unowned.txt" '
        BEGIN { FS = "\t" }
        FILENAME == ARGV[1] { owner[$1] = $2 "\t" $3 "\t" $4; next }
        { if ($0 in owner) print $0 "\t" owner[$0] > owned; else print $0 > unowned }' "$FILEMAP" "$1"

    # packages are copied whole, at the node's installed version
    : > "$CAP/new-packages.txt"
    while IFS=$'\t' read -r p n v a; do
        if [ -n "${PKG_SEEN[$n.$a]+x}" ]; then continue; fi
        PKG_SEEN[$n.$a]=1
        if is_skeleton_package "$n"; then continue; fi
        printf '%s\t%s\t%s\n' "$n" "$v" "$a" >> "$CAP/new-packages.txt"
        printf '%s.%s\t%s\n' "$n" "$a" "$p" >> "$CAP/package-reasons.txt"
    done < "$CAP/owned.txt"
    if [ -s "$CAP/new-packages.txt" ]; then
        cat "$CAP/new-packages.txt" >> "$BUILDDIR/captured-packages.txt"
        awk -v pkgs="$CAP/new-packages.txt" -v elf="$CAP/elf-package.txt" '
            BEGIN { FS = "\t"; while ((getline line < pkgs) > 0) want[line] = 1 }
            ($2 "\t" $3 "\t" $4) in want { print $1; print $1 > elf }' "$FILEMAP" >> "$CAP/copy.txt"
    fi

    # everything else
    while IFS= read -r p || [ -n "$p" ]; do
        case "$p" in
            /opt/?*)
                opt_child "$p"
                if [ -z "${OPT_SPLIT[$OPT_NAME]+x}" ]; then add_tree "/opt/$OPT_NAME" "$BUILDDIR/captured-opt.txt"; continue; fi
                # only the install(s) used. A link in /opt/<name> is copied as a link, and the directory it points to whole
                if [ -z "$OPT_CHILD" ]; then continue; fi
                if [ -n "$OPT_LINK" ]; then echo "$OPT_LINK" >> "$BUILDDIR/captured-paths.txt"; echo "$OPT_LINK" >> "$CAP/copy.txt"; fi
                if [ -d "$OPT_CHILD" ] && [ ! -L "$OPT_CHILD" ]; then add_tree "$OPT_CHILD" "$BUILDDIR/captured-opt.txt"; continue; fi
                ;;
        esac
        lib_root_for "$p"
        if [ -n "$ROOT_RESULT" ]; then add_tree "$ROOT_RESULT" "$BUILDDIR/captured-paths.txt"; continue; fi
        d="${p%/*}"
        if is_script_dir "$d"; then
            if [ -z "$(du -s --threshold=2G "$d" 2>/dev/null)" ]; then add_tree "$d" "$BUILDDIR/captured-paths.txt"; continue; fi
            if [ -z "${TREE_SEEN[$d]+x}" ]; then
                TREE_SEEN[$d]=1
                warn "[$d] is named in the step commands, but is over 2 GB, so only the files the reference run used were copied"
            fi
        fi
        echo "$p" >> "$BUILDDIR/captured-paths.txt"
        echo "$p" >> "$CAP/copy.txt"
        echo "$p" >> "$CAP/elf-other.txt"
    done < "$CAP/unowned.txt"
}

# library_pass <out>: run ldd on the new ELF files, and write the libraries they need to <out>. ELF files that aren't from a
# package also go in nonpackage-elf.txt, for the glibc check
library_pass() {
    local list f m LC_ALL=C
    : > "$1"
    for list in elf-package elf-other; do
        if [ ! -f "$CAP/$list.txt" ]; then continue; fi
        while IFS= read -r f || [ -n "$f" ]; do
            if [ -z "$f" ] || [ -n "${ELF_SEEN[$f]+x}" ]; then continue; fi
            ELF_SEEN[$f]=1
            if [ -L "$f" ] || [ ! -f "$f" ]; then continue; fi
            m=""
            IFS= read -r -n 4 -d '' m 2>/dev/null < "$f"
            if [ "$m" != $'\x7fELF' ]; then continue; fi
            if [ "$list" = "elf-other" ]; then echo "$f" >> "$CAP/nonpackage-elf.txt"; fi
            timeout 60 ldd -- "$f" 2>/dev/null | awk '$2 == "=>" && $3 ~ /^\// { print $3; next } $1 ~ /^\// { print $1 }' >> "$1"
        done < "$CAP/$list.txt"
        : > "$CAP/$list.txt"
    done
}

# copy_to_staging: copy everything in copy.txt into staging/ with tar, keeping links and permissions. Directories the pipeline
# reached through a link become real directories
copy_to_staging() {
    local p n t
    LC_ALL=C sort -u "$CAP/copy.txt" > "$CAP/copy-sorted.txt"
    : > "$CAP/copy-final.txt"; : > "$CAP/dirlinks.txt"; : > "$CAP/through.txt"
    while IFS= read -r p || [ -n "$p" ]; do
        case "$p" in /?*) ;; *) continue ;; esac
        case "/$p/" in */../*|*/./*) continue ;; esac
        if is_denied "$p"; then echo "$p" >> "$BUILDDIR/capture-excluded.txt"; continue; fi
        if is_runtime_file "$p" || is_excluded "$p"; then continue; fi
        if [ ! -e "$p" ] && [ ! -L "$p" ]; then continue; fi
        if [ -L "$p" ] && [ -d "$p" ]; then
            # top-level links (/bin -> usr/bin) are the base image's own
            if [ -z "${p%/*}" ]; then continue; fi
            echo "$p" >> "$CAP/dirlinks.txt"
        fi
        echo "$p" >> "$CAP/copy-final.txt"
    done < "$CAP/copy-sorted.txt"

    # tar would extract an entry under a copied directory link through the link, which could point outside the staging tree.
    # So those entries are copied at their real path instead
    if [ -s "$CAP/dirlinks.txt" ]; then
        awk -v links="$CAP/dirlinks.txt" -v through="$CAP/through.txt" '
            BEGIN { while ((getline l < links) > 0) link[l] = 1 }
            {
                n = split($0, part, "/"); prefix = ""; inside = 0
                for (i = 2; i < n; i++) { prefix = prefix "/" part[i]; if (prefix in link) { inside = 1; break } }
                if (inside) print > through; else print
            }' "$CAP/copy-final.txt" > "$CAP/copy-direct.txt"
        mv -f "$CAP/copy-direct.txt" "$CAP/copy-final.txt"
        while IFS= read -r p; do
            t="$(readlink -f -- "$p" 2>/dev/null)"
            if [ -n "$t" ] && [ "$t" != "/" ] && ! is_excluded "$t" && ! is_denied "$t" && ! is_runtime_file "$t"; then echo "$t"; fi
        done < "$CAP/through.txt" >> "$CAP/copy-final.txt"
    fi

    LC_ALL=C sort -u "$CAP/copy-final.txt" > "$CAP/copy-list.txt"
    log "Copying $(wc -l < "$CAP/copy-list.txt") files and directories into the staging tree"
    sed 's|^/||' "$CAP/copy-list.txt" | tr '\n' '\0' > "$CAP/copy-list.0"
    (cd / && tar -cf - --no-recursion --null -T "$CAP/copy-list.0" 2> "$CAP/tar-read.log") | (cd "$STAGING" && tar -xpf - 2> "$CAP/tar-write.log")
    n=$(cat "$CAP/tar-read.log" "$CAP/tar-write.log" | grep -c -i 'cannot\|denied')
    if [ "$n" -gt 0 ]; then warn "$n files couldn't be copied (unreadable by $(id -un), or a conflict in the staging tree), so they aren't in the image. See capture/tar-read.log and capture/tar-write.log"; fi
    if [ -z "$(ls -A "$STAGING" 2>/dev/null)" ]; then fail "Nothing was copied into the staging tree"; fi
    log "Staging tree is $(du -sh "$STAGING" 2>/dev/null | cut -f1)"
}

# in_image <path>: the path is in the staging tree, or is a standard system directory the base image has
in_image() {
    case "$1" in
        /usr/local/bin|/usr/local/sbin|/usr/bin|/usr/sbin|/bin|/sbin|/usr/lib|/usr/lib64|/lib|/lib64|/usr/local/lib|/usr/local/lib64|/usr/share/man|/usr/local/share/man) return 0 ;;
    esac
    canon "$1"
    [ -e "$STAGING$CANON_RESULT" ] || [ -L "$STAGING$CANON_RESULT" ]
}

# environment.txt, the image's %environment, from the login environment: variables that point into the image (path lists are
# cut down to the entries in the image), and known tool settings. Scheduler, user, session and NiDB variables are left out
capture_environment() {
    local kv name value kept e sq="'"
    local -a parts
    : > "$BUILDDIR/environment.txt"
    while IFS= read -r -d '' kv; do
        name="${kv%%=*}"; value="${kv#*=}"
        case "$name" in ''|[0-9]*|*[!A-Za-z0-9_]*) continue ;; esac
        case "$name" in
            HOME|USER|LOGNAME|USERNAME|SHELL|PWD|OLDPWD|SHLVL|_|HOSTNAME|HOST|TERM|MAIL|DISPLAY|TMPDIR|TMP|TEMP|HISTFILE|BASH_ENV|ENV|KRB5CCNAME) continue ;;
            LANG|LANGUAGE|LC_*|XDG_*|SSH_*|SLURM_*|SBATCH_*|SRUN_*|SGE_*|PBS_*|PE_HOSTFILE|JOB_ID|NIDB_*|APPTAINER*|SINGULARITY*) continue ;;
            LOADEDMODULES|_LMFILES_|MODULEPATH|MODULESHOME|MODULES_*|LMOD*|__LMOD*|DBUS_*|SELINUX_*) continue ;;
        esac
        case "$value" in
            /*|*:/*)
                kept=""
                IFS=: read -r -a parts <<< "$value"
                for e in ${parts[@]+"${parts[@]}"}; do
                    case "$e" in /*) ;; *) continue ;; esac
                    if in_image "$e"; then kept="${kept:+$kept:}$e"; fi
                done
                value="$kept" ;;
            *)
                case "$name" in FSL*|FS_*|FREESURFER*|ANTS*|AFNI*|MNI_*|ITK_*) ;; *) value="" ;; esac ;;
        esac
        if [ -n "$value" ]; then printf "export %s='%s'\n" "$name" "${value//"$sq"/"$sq\\$sq$sq"}" >> "$BUILDDIR/environment.txt"; fi
    done < "$BUILDDIR/env.0"
    if ! grep -q '^export PATH=' "$BUILDDIR/environment.txt"; then
        echo "export PATH='/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin'" >> "$BUILDDIR/environment.txt"
    fi
    log "Image environment: $(sed 's/=.*//; s/^export //' "$BUILDDIR/environment.txt" | tr '\n' ' ')"
}

# copied binaries (not from a package) that need a newer glibc than the node's. They couldn't run on this node either, so
# they're usually unused files in a copied /opt directory that was built on a newer system. The node's glibc is the newest
# GLIBC_ version its libc.so.6 provides, since distributions backport versions (EL9's glibc 2.34 provides GLIBC_2.35)
check_glibc() {
    local node libc f need n
    if [ ! -s "$CAP/nonpackage-elf.txt" ]; then return 0; fi
    libc="$(ldd "$(command -v bash)" 2>/dev/null | awk '$1 == "libc.so.6" { print $3 }')"
    node="$(objdump -T -- "$libc" 2>/dev/null | grep -o 'GLIBC_[0-9][0-9.]*' | sed 's/^GLIBC_//' | sort -V | tail -n 1)"
    if [ -z "$node" ]; then warn "Unable to find this node's glibc version, so the glibc check was skipped"; return 0; fi
    : > "$CAP/glibc-newer.txt"
    LC_ALL=C sort -u "$CAP/nonpackage-elf.txt" | while IFS= read -r f; do
        need="$(objdump -T -- "$f" 2>/dev/null | grep -o 'GLIBC_[0-9][0-9.]*' | sed 's/^GLIBC_//' | sort -V | tail -n 1)"
        if [ -n "$need" ] && [ "$(printf '%s\n%s\n' "$node" "$need" | sort -V | tail -n 1)" != "$node" ]; then
            printf '%s\tGLIBC_%s\n' "$f" "$need" >> "$CAP/glibc-newer.txt"
        fi
    done
    n=$(wc -l < "$CAP/glibc-newer.txt")
    if [ "$n" -gt 0 ]; then warn "$n copied binaries need a newer glibc than this node's [$node], so they won't run in the image. See capture/glibc-newer.txt"; fi
}

# warnings about what was captured: commercial software, the home directory, deny-listed files
capture_warnings() {
    local p n
    cat "$BUILDDIR/captured-opt.txt" "$BUILDDIR/captured-paths.txt" | grep -i 'matlab' | grep -v -i 'mcr\|runtime' | sed 's|^\(/opt/[^/]*\).*|\1|' | LC_ALL=C sort -u > "$CAP/commercial.txt"
    while IFS= read -r p; do
        warn "Commercial software [$p] was copied into the image. Don't share the image, or use the MATLAB Runtime (MCR) instead"
    done < "$CAP/commercial.txt"

    if [ -n "$HOME_DIR" ] && grep -qF -- "$HOME_DIR/" "$BUILDDIR/captured-paths.txt"; then
        warn "Files from the home directory [$HOME_DIR] were copied into the image. Apptainer mounts the user's home directory over it, so if [$(id -un)] runs the image, run it with --no-home"
    fi

    if [ -s "$BUILDDIR/capture-excluded.txt" ]; then
        LC_ALL=C sort -u -o "$BUILDDIR/capture-excluded.txt" "$BUILDDIR/capture-excluded.txt"
        n=$(wc -l < "$BUILDDIR/capture-excluded.txt")
        warn "$n files on the deny list (credentials, keys, license files) were not copied. See capture-excluded.txt"
        if grep -qi 'freesurfer.*license' "$BUILDDIR/capture-excluded.txt"; then
            warn "FreeSurfer's license wasn't copied. Users must bind their own: --bind license.txt:<freesurfer dir>/license.txt"
        fi
    fi
}

# check_container_use: stop if the reference run started a container runtime. The traced paths would be the inner container's,
# looked up on this node, and the image would need a container runtime inside a container. The export checks catch step
# commands that run containers; this catches containers started from a script a step calls. Reports the command and the
# program that started it, from the trace's fork and execve calls
check_container_use() {
    local found
    found="$(awk '
        / = -1 / { next }
        $2 ~ /^(clone|clone3|fork|vfork)\(/ || ($2 == "<..." && $3 ~ /^(clone|clone3|fork|vfork)$/) {
            if (match($0, /= [0-9]+$/)) { child = substr($0, RSTART + 2); parent[child] = $1; prog[child] = prog[$1] }
            next
        }
        $2 ~ /^execve\(/ {
            path = $0; sub(/^[0-9]+ +execve\("/, "", path); sub(/".*/, "", path)
            argv = $0; i = index(argv, "["); j = index(argv, "]")
            argv = (i > 0 && j > i) ? substr(argv, i + 1, j - i - 1) : path
            gsub(/", "/, " ", argv); gsub(/"/, "", argv)
            if (length(argv) > 300) argv = substr(argv, 1, 300) "..."
            name = path; sub(/.*\//, "", name)
            if (name ~ /^(apptainer|singularity|run-singularity|starter|starter-suid|docker|docker-compose|podman)$/) {
                by = (parent[$1] != "") ? prog[parent[$1]] : ""
                if (by == "") by = prog[$1]
                print "[" argv "], started by [" by "]"
                exit
            }
            prog[$1] = argv
        }' "$BUILDDIR/trace.log")"
    if [ -n "$found" ]; then
        fail "The reference run started a container: $found. Pipelines that run containers can't be exported, since the image would need Apptainer inside Apptainer. If the pipeline already runs in a container, share that image instead"
    fi
}

# sort the traced files and copy them into staging/, and write environment.txt (doc section 7, step 2)
capture_software() {
    local d t f round=0
    CAP="$BUILDDIR/capture"; STAGING="$BUILDDIR/staging"; REF_START="$BUILDDIR/reference.start"
    if [ ! -f "$REF_START" ]; then fail "reference.start is missing, so the reference run's output can't be told apart from the software"; fi
    check_container_use
    safe_rm "$CAP" "$STAGING"
    mkdir -p "$CAP" "$STAGING" || fail "Unable to create [$CAP] and [$STAGING]"
    : > "$CAP/copy.txt"; : > "$CAP/nonpackage-elf.txt"
    for f in captured-packages captured-opt captured-paths capture-excluded; do : > "$BUILDDIR/$f.txt"; done

    # merged /usr: /bin, /lib64, ... are links into /usr
    for d in /bin /sbin /lib /lib64 /lib32 /libx32; do
        if [ -L "$d" ] && [ -d "$d" ]; then
            t="$(readlink -f -- "$d")"
            if [ -n "$t" ] && [ "$t" != "/" ]; then TOPLINK[$d]="$t"; fi
        fi
    done

    # the build directory and scratch space, by the path given and the real path
    HOME_DIR="$(env_get HOME)"; HOME_DIR="${HOME_DIR%/}"
    for d in "$BUILDDIR" "${TMPDIR:-}" "${APPTAINER_TMPDIR:-}"; do
        case "$d" in /?*) ;; *) continue ;; esac
        EXCLUDE_PREFIXES+=("${d%/}")
        t="$(readlink -f -- "$d" 2>/dev/null)"
        if [ -n "$t" ] && [ "$t" != "/" ]; then EXCLUDE_PREFIXES+=("$t"); fi
    done

    build_file_map
    find_library_roots

    # the first quoted absolute path of each call that didn't fail. Relative paths are relative to a working directory the
    # trace doesn't record, so they're skipped, as are paths strace had to escape (trace-escaped.txt)
    awk -v escaped="$CAP/trace-escaped.txt" '
        / = -1 / { next }
        {
            i = index($0, "\"/"); if (i == 0) next
            s = substr($0, i + 1); match(s, /^([^"\\]|\\.)*/); p = substr(s, 1, RLENGTH)
            if (index(p, "\\") > 0) { print p > escaped; next }
            print p
        }' "$BUILDDIR/trace.log" | LC_ALL=C sort -u > "$CAP/traced.txt"
    log "Trace: $(wc -l < "$CAP/traced.txt") distinct paths"
    prepare_paths "$CAP/traced.txt" "$CAP/round.txt"
    find_opt_installs "$CAP/round.txt"

    # classify, then add the libraries the copied ELF files need, until nothing new turns up
    while [ -s "$CAP/round.txt" ] && [ $round -lt 8 ]; do
        round=$((round + 1))
        log "Capture round $round: $(wc -l < "$CAP/round.txt") new paths"
        classify "$CAP/round.txt"
        library_pass "$CAP/libraries.txt"
        prepare_paths "$CAP/libraries.txt" "$CAP/round.txt"
    done
    if [ -s "$CAP/round.txt" ]; then warn "Library dependencies were still being found after $round rounds. Some libraries may be missing"; fi

    for f in captured-packages captured-opt captured-paths; do LC_ALL=C sort -u -o "$BUILDDIR/$f.txt" "$BUILDDIR/$f.txt"; done
    log "Captured $(wc -l < "$BUILDDIR/captured-packages.txt") packages, $(wc -l < "$BUILDDIR/captured-opt.txt") /opt directories and $(wc -l < "$BUILDDIR/captured-paths.txt") other paths"
    if [ "$PKG_MANAGER" = "none" ]; then warn "No package manager, so the software was captured from the trace and ldd only. The image may be missing files the reference run didn't use"; fi

    copy_to_staging
    capture_environment
    check_glibc
    capture_warnings
}

# manifest.json: NiDB's manifest-export.json (pipeline, input layout, required variables, export warnings) plus a 'build'
# object with the build node, base OS and the captured software (doc section 6). Written before build_image, which copies
# it into the image. capture_software writes the captured-*.txt and environment.txt files. Validation results aren't in
# it, since the image is already built by then; they're in validation.txt
write_manifest() {
    local base s="$BUILDDIR/sysinfo.txt" sw="$BUILDDIR/software.txt" m="$BUILDDIR/manifest.json" t path sep name version arch
    if [ ! -s "$BUILDDIR/manifest-export.json" ]; then fail "manifest-export.json is missing from the build directory"; fi

    # remove the closing brace of NiDB's object, so the build object can be added to it
    base="$(cat "$BUILDDIR/manifest-export.json")"
    base="${base%"${base##*[![:space:]]}"}"
    if [ "${base: -1}" != "}" ]; then fail "manifest-export.json isn't a JSON object"; fi
    base="${base%?}"
    base="${base%"${base##*[![:space:]]}"}"

    {
        printf '%s,\n    "build": {\n' "$base"
        printf '        "containerId": %s,\n' "$CONTAINER_ID"
        printf '        "date": %s,\n' "$(json_str "$(date '+%Y-%m-%dT%H:%M:%S')")"
        printf '        "hostname": %s,\n' "$(json_str "$(kv_get "$s" hostname)")"
        printf '        "jobId": %s,\n' "$(json_str "$(kv_get "$s" jobid)")"
        printf '        "os": {\n'
        printf '            "id": %s,\n' "$(json_str "$OS_ID")"
        printf '            "versionId": %s,\n' "$(json_str "$OS_VERSION_ID")"
        printf '            "prettyName": %s,\n' "$(json_str "$OS_PRETTY_NAME")"
        printf '            "family": %s,\n' "$(json_str "$OS_FAMILY")"
        printf '            "kernel": %s,\n' "$(json_str "$(kv_get "$s" kernel)")"
        printf '            "arch": %s,\n' "$(json_str "$(kv_get "$s" arch)")"
        printf '            "glibc": %s\n' "$(json_str "$(kv_get "$s" glibc)")"
        printf '        },\n'
        printf '        "packageManager": %s,\n' "$(json_str "$PKG_MANAGER")"
        printf '        "baseImage": %s,\n' "$(json_str "$BASE_IMAGE")"
        printf '        "apptainer": %s,\n' "$(json_str "$(kv_get "$s" apptainer)")"

        # the interpreters found on the build node
        printf '        "interpreters": ['
        sep=""
        for t in $INTERPRETERS; do
            path="$(kv_get "$sw" "$t.path")"
            if [ -z "$path" ]; then continue; fi
            printf '%s\n            { "name": %s, "path": %s, "version": %s }' "$sep" "$(json_str "$t")" "$(json_str "$path")" "$(json_str "$(kv_get "$sw" "$t.version")")"
            sep=","
        done
        if [ -n "$sep" ]; then printf '\n        ],\n'; else printf '],\n'; fi

        # the system packages copied into the image: name, version-release, arch (tab separated)
        printf '        "capturedPackages": ['
        sep=""
        if [ -f "$BUILDDIR/captured-packages.txt" ]; then
            while IFS=$'\t' read -r name version arch || [ -n "$name" ]; do
                if [ -z "$name" ]; then continue; fi
                printf '%s\n            { "name": %s, "version": %s, "arch": %s }' "$sep" "$(json_str "$name")" "$(json_str "$version")" "$(json_str "$arch")"
                sep=","
            done < "$BUILDDIR/captured-packages.txt"
        fi
        if [ -n "$sep" ]; then printf '\n        ],\n'; else printf '],\n'; fi

        printf '        "capturedOptDirectories": %s,\n' "$(json_lines "$BUILDDIR/captured-opt.txt" '        ')"
        printf '        "capturedPaths": %s,\n' "$(json_lines "$BUILDDIR/captured-paths.txt" '        ')"
        printf '        "environment": %s,\n' "$(json_lines "$BUILDDIR/environment.txt" '        ')"
        printf '        "buildWarnings": %s\n' "$(json_lines "$BUILDDIR/build-warnings.txt" '        ')"
        printf '    }\n}\n'
    } > "$m.tmp" || fail "Unable to write [$m.tmp]"
    mv -f "$m.tmp" "$m" || fail "Unable to write [$m]"
    log "Wrote manifest.json"
}

# write_metadata <sandbox>: the image's environment, run script, labels and help, written straight into the sandbox's
# /.singularity.d, where a definition file's %environment, %runscript, %labels and %help would put them. Building the .sif
# from the sandbox then needs no root or fakeroot, unlike building from a definition file
write_metadata() {
    local d="$1/.singularity.d" key value sep="" line
    mkdir -p "$d/env" || fail "Unable to create [$d/env]"

    # sourced after the base image's own env/*.sh (like 10-docker2singularity.sh), so these win
    {
        echo "#!/bin/sh"
        echo "# written by NiDB from the build node's login environment"
        if [ -f "$BUILDDIR/environment.txt" ]; then cat "$BUILDDIR/environment.txt"; fi
    } > "$d/env/90-environment.sh" || fail "Unable to write [$d/env/90-environment.sh]"
    chmod 755 "$d/env/90-environment.sh"

    printf '#!/bin/sh\n\nexec /pipeline/run.sh "$@"\n' > "$d/runscript" || fail "Unable to write [$d/runscript]"
    chmod 755 "$d/runscript"

    # README.txt is written by NiDB: usage, arguments and the expected /input layout
    cp -f "$BUILDDIR/README.txt" "$d/runscript.help" || fail "Unable to write [$d/runscript.help]"

    # NiDB's labels, plus the base image's own (simple "key": "value" pairs) that NiDB doesn't set
    {
        printf 'org.nidb.pipeline.name\t%s\n' "$PIPELINE_NAME"
        printf 'org.nidb.pipeline.version\t%s\n' "$PIPELINE_VERSION"
        printf 'org.nidb.base\t%s\n' "$BASE_IMAGE"
        printf 'org.nidb.buildos\t%s\n' "$OS_PRETTY_NAME"
        printf 'org.nidb.exported\t%s\n' "$(date '+%Y-%m-%d')"
        if [ -f "$d/labels.json" ]; then
            grep -o '"[^"\\]*"[[:space:]]*:[[:space:]]*"[^"\\]*"' "$d/labels.json" | sed 's/^"\([^"]*\)"[[:space:]]*:[[:space:]]*"\(.*\)"$/\1\t\2/' | grep -v '^org\.nidb\.'
        fi
    } > "$BUILDDIR/labels.txt"
    {
        printf '{'
        while IFS=$'\t' read -r key value; do
            if [ -z "$key" ]; then continue; fi
            printf '%s\n    %s: %s' "$sep" "$(json_str "$key")" "$(json_str "$value")"
            sep=","
        done < "$BUILDDIR/labels.txt"
        printf '\n}\n'
    } > "$d/labels.json" || fail "Unable to write [$d/labels.json]"
}

# sandbox from the base image, add the captured software and /pipeline, then build the .sif (doc section 8, steps 4.4-4.5)
build_image() {
    export APPTAINER_TMPDIR="${APPTAINER_TMPDIR:-${TMPDIR:-/tmp}}"
    cd "$BUILDDIR" || fail "Unable to cd into [$BUILDDIR]"

    # the job can inherit another user's XDG_RUNTIME_DIR (like root's /run/user/0) from the submit environment. Pulling an
    # image reads registry logins from $XDG_RUNTIME_DIR/containers/auth.json, and stops if that's unreadable
    if [ -n "${XDG_RUNTIME_DIR:-}" ] && { [ ! -d "$XDG_RUNTIME_DIR" ] || [ ! -O "$XDG_RUNTIME_DIR" ] || [ ! -w "$XDG_RUNTIME_DIR" ]; }; then
        log "XDG_RUNTIME_DIR [$XDG_RUNTIME_DIR] isn't a directory owned by and writable by $(whoami), so it's unset for $APPTAINER"
        unset XDG_RUNTIME_DIR
    fi

    # sandboxes can contain read-only directories, which safe_rm handles
    safe_rm "$BUILDDIR/base"

    log "Creating a sandbox from base image [$BASE_IMAGE]"
    "$APPTAINER" build --sandbox base "$BASE_IMAGE" || fail "Unable to create a sandbox from base image [$BASE_IMAGE]"

    # an unprivileged sandbox keeps the image's modes, so directories like usr/bin aren't writable by us and the copy below
    # can't add or replace files in them. Same as 'build --fix-perms', which older versions don't have
    chmod -R u+rwX base || fail "Unable to make the sandbox writable"

    log "Copying the captured software and the pipeline into the sandbox"
    cp -a staging/. base/ || fail "Unable to copy the captured software into the sandbox"
    mkdir -p base/pipeline base/input base/output || fail "Unable to create /pipeline, /input and /output in the sandbox"
    cp -f run.sh run.sh.template manifest.json requiredvars.txt README.txt base/pipeline/ || fail "Unable to copy the pipeline files into the sandbox"
    chmod 755 base/pipeline/run.sh

    write_metadata base

    # sandbox to .sif only packs the directory into squashfs, so it doesn't need root or fakeroot
    log "Building [$SIF_NAME]"
    safe_rm "$BUILDDIR/$SIF_NAME"
    "$APPTAINER" build "$SIF_NAME" base || fail "Unable to build [$SIF_NAME] from the sandbox"
}

# run the new image on the reference input and compare the output file list with the reference analysis (doc section 8, step 4.6)
validate() {
    local out="$BUILDDIR/validation-output" f="$BUILDDIR/validation.txt" rc nmissing nextra
    safe_rm "$out"
    mkdir -p "$out" || fail "Unable to create [$out]"

    log "Validation run"
    "$APPTAINER" run --bind "$BUILDDIR/input:/input:ro" --bind "$out:/output" "$BUILDDIR/$SIF_NAME" --subjectuid "$REF_SUBJECTUID" --studynum "$REF_STUDYNUM" --studydatetime "$REF_STUDYDATETIME" > "$BUILDDIR/validation-run.log" 2>&1
    rc=$?
    log "Validation run finished with exit code [$rc]"

    : > "$f"
    kv "$f" exitcode "$rc"
    if [ $rc -ne 0 ]; then warn "The validation run exited with code [$rc]. See validation-run.log"; fi

    if [ ! -d "$REF_ANALYSISDIR" ]; then
        warn "Reference analysis directory [$REF_ANALYSISDIR] not found on $(hostname), so the validation output was not compared"
        return 0
    fi

    # NiDB's pipeline/ directory (logs, job files) isn't created by the container
    (cd "$REF_ANALYSISDIR" && find . -path ./pipeline -prune -o -type f -print) | LC_ALL=C sort > "$BUILDDIR/validation-reference-files.txt"
    (cd "$out" && find . -path ./pipeline -prune -o -type f -print) | LC_ALL=C sort > "$BUILDDIR/validation-container-files.txt"
    LC_ALL=C comm -23 "$BUILDDIR/validation-reference-files.txt" "$BUILDDIR/validation-container-files.txt" > "$BUILDDIR/validation-missing.txt"
    LC_ALL=C comm -13 "$BUILDDIR/validation-reference-files.txt" "$BUILDDIR/validation-container-files.txt" > "$BUILDDIR/validation-extra.txt"
    nmissing=$(wc -l < "$BUILDDIR/validation-missing.txt")
    nextra=$(wc -l < "$BUILDDIR/validation-extra.txt")

    kv "$f" reference_files "$(wc -l < "$BUILDDIR/validation-reference-files.txt")"
    kv "$f" container_files "$(wc -l < "$BUILDDIR/validation-container-files.txt")"
    kv "$f" missing_files "$nmissing"
    kv "$f" extra_files "$nextra"
    { echo ""; echo "[missing]"; cat "$BUILDDIR/validation-missing.txt"; echo ""; echo "[extra]"; cat "$BUILDDIR/validation-extra.txt"; } >> "$f"

    if [ "$nmissing" -gt 0 ]; then warn "$nmissing files in the reference analysis were not created by the container. See validation.txt"; fi
}

# the .sif's size and checksum for the final report, then remove what's no longer needed. The .sif stays here, with the
# logs and reports. The staged input is the reference subject's data, and the sandbox is as big as the image
finalize() {
    local sif="$BUILDDIR/$SIF_NAME"
    if [ ! -s "$sif" ]; then fail "[$sif] is missing or empty"; fi
    SIF_SIZE=$(stat -c %s "$sif") || fail "Unable to get the size of [$sif]"
    SIF_SHA256=$(sha256sum "$sif" | awk '{print $1}')
    if [ -z "$SIF_SHA256" ]; then fail "Unable to get the sha256 of [$sif]"; fi
    log "[$sif] size [$SIF_SIZE] sha256 [$SIF_SHA256]"

    safe_rm "$BUILDDIR/base" "$BUILDDIR/staging" "$BUILDDIR/input" "$BUILDDIR/reference" "$BUILDDIR/validation-output"
}

# ---------- main ----------
cd "$BUILDDIR" || { echo "Unable to cd into the build directory [$BUILDDIR]"; exit 1; }
exec >> "$BUILDDIR/build.log" 2>&1
rm -f build-error.txt
: > build-warnings.txt
if ! command -v curl >/dev/null 2>&1; then log "ERROR: curl not found on $(hostname), so the build can't report to NiDB. Stopping"; exit 1; fi
trap finish EXIT
trap 'exit 143' TERM
trap 'exit 130' INT

log "Building container [$CONTAINER_ID] for pipeline [$PIPELINE_NAME] version [$PIPELINE_VERSION] on $(hostname)"

# the login environment, before this script changes anything
env | LC_ALL=C sort > "$BUILDDIR/env.txt"
env -0 > "$BUILDDIR/env.0"

checkin preReferenceAnalysisChecks "Checking tools and collecting system info on $(hostname)"
find_tools
detect_os
collect_sysinfo
collect_software
check_tools
if [ -z "$BASE_IMAGE" ]; then fail "No base image for this OS [$OS_PRETTY_NAME] (ID [$OS_ID], VERSION_ID [$OS_VERSION_ID])"; fi
log "OS [$OS_PRETTY_NAME], family [$OS_FAMILY], package manager [$PKG_MANAGER], base image [$BASE_IMAGE]"
check_eol

checkin runningReferenceAnalysis "Reference run of subject [$REF_SUBJECTUID] study [$REF_STUDYNUM] under strace"
reference_run
checkin postReferenceAnalysisChecks "Capturing the software used by the reference run"
capture_software
checkin buildingContainer "Building [$SIF_NAME] from base image [$BASE_IMAGE]"
write_manifest
build_image
if [ "$RUN_VALIDATION" = "1" ]; then
    checkin runningValidationAnalysis "Validation run of [$SIF_NAME]"
    validate
else
    log "Validation run skipped"
fi
finalize

log "Build complete"
exit 0
)BASH";

    QString path = c.buildDir + "/build-container.sh";
    if (!WriteTextFile(path, settings.join("\n") + "\n" + body, false)) {
        msg = "Unable to write [" + path + "]";
        return false;
    }
    QFile::setPermissions(path, QFileDevice::ReadOwner | QFileDevice::WriteOwner | QFileDevice::ExeOwner | QFileDevice::ReadGroup | QFileDevice::ExeGroup | QFileDevice::ReadOther | QFileDevice::ExeOther);

    AppendBuildLog(c.containerID, QString("Wrote build-container.sh. Reference study [%1/%2], reference analysis directory [%3]").arg(a.uid).arg(a.studynum).arg(refAnalysisDir));
    return true;
}


/* ---------------------------------------------------------- */
/* --------- WriteClusterJob -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Write build.job: the scheduler header (from the pipeline's cluster settings) plus a call to
 * build-container.sh, which WriteContainerBuildScript() has already written. The reference study's values
 * are in build-container.sh, so build.job passes no arguments. The scheduler output goes to the build directory.
 * The wall time is the pipeline's, times the number of pipeline runs (reference, plus validation), plus 4 hours
 * @param c The request
 * @param p The pipeline
 * @param msg Error message, if any
 * @return true if written
 */
bool modulePipelineContainer::WriteClusterJob(containerRequest &c, pipeline &p, QString &msg) {

    /* paths as the compute node sees them */
    QString clusterBuildDir = GetClusterBuildDir(c.containerID);
    QString jobName = RemoveNonAlphaNumericChars(p.name) + "-container";

    /* the build runs the pipeline once (reference run), and again if validating, plus the capture and the image
       build. So allow the pipeline's wall time for each run, plus 4 hours */
    int wallTime = 0;
    if (p.clusterMaxWallTime > 0)
        wallTime = p.clusterMaxWallTime * (c.runValidation ? 2 : 1) + 240;
    QString wallTimeStr = QString("%1:%2:00").arg(wallTime/60).arg(wallTime % 60, 2, 10, QChar('0'));

    QString jobfile;
    if (p.clusterType.toLower() == "slurm") {
        jobfile += "#!/bin/bash -l\n";
        jobfile += "#SBATCH -J " + jobName + "\n";
        jobfile += "#SBATCH --nodes=1\n";
        jobfile += "#SBATCH --partition=" + p.clusterQueue + "\n";
        jobfile += "#SBATCH -o " + clusterBuildDir + "/%x.o%j\n";
        jobfile += "#SBATCH -e " + clusterBuildDir + "/%x.e%j\n";
        if (p.clusterMemory > 0)
            jobfile += QString("#SBATCH --mem-per-cpu=%1G\n").arg(int(ceil(p.clusterMemory)));
        jobfile += QString("#SBATCH --ntasks=1 --cpus-per-task=%1\n").arg(qMax(1, p.clusterNumCores));
        if (wallTime > 0)
            jobfile += "#SBATCH -t " + wallTimeStr + "\n";
    }
    else { /* assume SGE otherwise, as modulePipeline does */
        jobfile += "#!/bin/bash\n";
        jobfile += "#$ -N " + jobName + "\n";
        jobfile += "#$ -S /bin/bash\n";
        jobfile += "#$ -j y\n";
        jobfile += "#$ -o " + clusterBuildDir + "/\n";
        /* no '-V'. The submit host's environment would end up in env.txt and the image's environment instead of the node's login environment */
        jobfile += "#$ -u " + n->cfg["queueuser"] + "\n";
        if (wallTime > 0)
            jobfile += "#$ -l h_rt=" + wallTimeStr + "\n";
    }

    jobfile += "\n";
    jobfile += "echo Hostname: `hostname`\n";
    jobfile += "echo Username: `whoami`\n\n";

    /* build-container.sh has all the settings for this build, so it takes no arguments. Run it in a login shell,
       like the pipeline's own jobs, so the reference run sees the same environment. It logs to build.log and
       reports its progress and result through analysisapi.php */
    jobfile += "exec /bin/bash -l " + ShellQuote(clusterBuildDir + "/build-container.sh") + "\n";

    QString path = c.buildDir + "/build.job";
    if (!WriteTextFile(path, jobfile, false)) {
        msg = "Unable to write [" + path + "]";
        return false;
    }
    QFile::setPermissions(path, QFileDevice::ReadOwner | QFileDevice::WriteOwner | QFileDevice::ExeOwner | QFileDevice::ReadGroup | QFileDevice::ExeGroup | QFileDevice::ReadOther | QFileDevice::ExeOther);

    AppendBuildLog(c.containerID, QString("Wrote build.job for [%1] queue [%2]. Wall time [%3]").arg(p.clusterType.toLower() == "slurm" ? "slurm" : "SGE").arg(p.clusterQueue).arg(wallTime > 0 ? wallTimeStr : "none"));
    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetBuildDir ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief The build directory, on storage both the NiDB server and the compute nodes can reach. Named by
 * container ID only, so it can be found from the row alone and doesn't change if the pipeline is renamed
 * @param containerID pipelinecontainer_id
 * @return <analysisdir>/_apptainer/<containerid>
 */
QString modulePipelineContainer::GetBuildDir(int containerID) {
    return QString("%1/_apptainer/%2").arg(n->cfg["analysisdir"]).arg(containerID);
}


/* ---------------------------------------------------------- */
/* --------- GetClusterBuildDir ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief The build directory as the compute nodes see it. Used inside build-container.sh
 * @param containerID pipelinecontainer_id
 * @return <clusteranalysisdir>/_apptainer/<containerid>, or GetBuildDir() if clusteranalysisdir isn't set
 */
QString modulePipelineContainer::GetClusterBuildDir(int containerID) {
    if (n->cfg["clusteranalysisdir"].trimmed() == "")
        return GetBuildDir(containerID);

    return QString("%1/_apptainer/%2").arg(n->cfg["clusteranalysisdir"]).arg(containerID);
}


/* ---------------------------------------------------------- */
/* --------- GetContainerFileName --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief The .sif filename. The same as the example on pipelinecontainers.php
 * @param p The pipeline
 * @param version Pipeline version
 * @return <pipeline>-v<version>.sif, with characters other than letters, numbers, _ . - replaced with _
 */
QString modulePipelineContainer::GetContainerFileName(pipeline &p, int version) {
    QString name = p.name;
    name.replace(QRegularExpression("[^A-Za-z0-9_.-]"), "_");
    return QString("%1-v%2.sif").arg(name).arg(version);
}


/* ---------------------------------------------------------- */
/* --------- SetStatus -------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Set build_status for a row
 * @param containerID pipelinecontainer_id
 * @param status New status
 */
void modulePipelineContainer::SetStatus(int containerID, QString status) {
    QSqlQuery q;
    q.prepare("update pipeline_containers set build_status = :status where pipelinecontainer_id = :id");
    q.bindValue(":status", status);
    q.bindValue(":id", containerID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SetError --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Mark a row as error, set build_enddate, and add the message to the build log
 * @param containerID pipelinecontainer_id
 * @param msg Error message
 */
void modulePipelineContainer::SetError(int containerID, QString msg) {
    n->Log(QString("Container [%1] error: %2").arg(containerID).arg(msg));
    AppendBuildLog(containerID, "ERROR: " + msg);

    /* a canceled build stays canceled. Canceling while the export is being prepared makes SubmitBuildJob()'s claim fail */
    QSqlQuery q;
    q.prepare("update pipeline_containers set build_status = 'error', build_enddate = now() where pipelinecontainer_id = :id and build_status <> 'canceled'");
    q.bindValue(":id", containerID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- AppendBuildLog --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Append a timestamped line to build_log
 * @param containerID pipelinecontainer_id
 * @param log The text
 */
void modulePipelineContainer::AppendBuildLog(int containerID, QString log) {
    QString line = QString("[%1] %2\n").arg(QDateTime::currentDateTime().toString("yyyy-MM-dd HH:mm:ss")).arg(log);

    QSqlQuery q;
    q.prepare("update pipeline_containers set build_log = concat(coalesce(build_log, ''), :log) where pipelinecontainer_id = :id");
    q.bindValue(":log", line);
    q.bindValue(":id", containerID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- AppendBuildWarnings ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Append warnings to build_warnings, one per line
 * @param containerID pipelinecontainer_id
 * @param warnings The warnings
 */
void modulePipelineContainer::AppendBuildWarnings(int containerID, QStringList warnings) {
    if (warnings.size() == 0)
        return;

    QSqlQuery q;
    q.prepare("update pipeline_containers set build_warnings = concat(coalesce(build_warnings, ''), :warnings) where pipelinecontainer_id = :id");
    q.bindValue(":warnings", warnings.join("\n") + "\n");
    q.bindValue(":id", containerID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}
