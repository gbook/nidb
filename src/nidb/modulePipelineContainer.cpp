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
 * @brief Entry point for this module. Moves each pipeline_containers row through
 * submitted -> started -> building -> complete/error (doc/pipeline-apptainer.md section 8)
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

    /* running builds: finish any that have written build.exitcode */
    for (containerRequest c : GetExportRequests("building", containerID)) {
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return numDone; }

        if (!IsBuildFinished(c))
            continue;
        numDone++;

        QString msg;
        if (!Finish(c, msg))
            SetError(c.containerID, msg);
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
 * manifest.json, README.txt, requiredvars.txt, the staged reference input, export.log, build-container.sh and build.job.
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

    /* start from an empty build directory. A build.exitcode left from an earlier attempt would make
       IsBuildFinished() true before the new job starts */
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
    if (!WriteTextFile(c.buildDir + "/manifest.json", manifest, false) || !WriteTextFile(c.buildDir + "/README.txt", readme, false)) {
        msg = "Unable to write manifest.json and README.txt into [" + c.buildDir + "]";
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
 * @brief Step 3. Submit build.job with nidb::SubmitClusterJob(), using the pipeline's cluster and queue settings,
 * then set build_status = building and build_startdate
 * @param c The request
 * @param msg Error message, if any
 * @return true if the job was submitted
 */
bool modulePipelineContainer::SubmitBuildJob(containerRequest &c, QString &msg) {
    /* TODO */
    Q_UNUSED(c);
    msg = "SubmitBuildJob() not implemented yet";
    return false;
}


/* ---------------------------------------------------------- */
/* --------- IsBuildFinished -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Step 5. The build job writes build.exitcode as its last action. Polling for it means the job
 * needs no connection to NiDB
 * @param c The request
 * @return true if build.exitcode exists
 */
bool modulePipelineContainer::IsBuildFinished(containerRequest &c) {
    /* TODO: also catch jobs that died without writing build.exitcode (scheduler job state, or a timeout) */
    return QFile::exists(c.buildDir + "/build.exitcode");
}


/* ---------------------------------------------------------- */
/* --------- Finish ----------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Step 5. On success, copy the .sif, README.txt, manifest.json and validation.txt to the export directory,
 * and record container_path, container_size, container_sha256, container_date, build_enddate and status complete.
 * On failure, record the error. Either way, save the logs to the row and delete the build directory
 * @param c The request
 * @param msg Error message, if any
 * @return true if the container is complete
 */
bool modulePipelineContainer::Finish(containerRequest &c, QString &msg) {
    /* TODO */
    Q_UNUSED(c);
    msg = "Finish() not implemented yet";
    return false;
}


/* ---------------------------------------------------------- */
/* --------- CheckIfExportable ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Export checks (doc sections 1, 3, 5). The same rules as the pitfalls on pipelinecontainers.php, which are
 * a preview; these are authoritative. Only enabled main steps are checked, since only they run in the container.
 * Errors: not level 1, no enabled main steps, no completed analysis of this version, group, deprecated or
 * unrecognized variables, NiDB storage paths. Warnings: {analysisid}, {command}, a parent pipeline, FreeSurfer, MATLAB
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
 * @brief Create manifest.json (doc section 6). The build job adds the base OS and captured software
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
 * for manifest.json, README.txt and the image's %help
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

            QString format = d.exportDataFormat + (d.flags.exportGzip ? ", gzipped" : "");

            lines << dataDir;
            if (d.flags.behOnly)
                lines << QString("    %1, protocol [%2]. Behavioral data only, no imaging data").arg(d.searchModality.toUpper()).arg(d.searchProtocol);
            else
                lines << QString("    %1, protocol [%2], format [%3]").arg(d.searchModality.toUpper()).arg(d.searchProtocol).arg(format);
            if (d.flags.exportWriteSeriesDirectory) {
                if (d.flags.exportPreserveSeriesNumber)
                    lines << "    <seriesnum> is the series' original series number";
                else
                    lines << "    <seriesnum> is 1, 2, 3, ... for each matching series, in order";
            }
            if (d.flags.exportWritePhaseDirectory)
                lines << "    <phasedir> is the phase encoding direction: AP, PA, RL, LR, COL, ROW, or unknownPE";
            if (behDir != "")
                lines << "    Behavioral data in " + behDir;
            if (d.flags.isOptional)
                lines << "    Optional";

            QJsonObject o;
            o["type"] = "data";
            o["location"] = dataDir;
            o["modality"] = d.searchModality;
            o["protocol"] = d.searchProtocol;
            o["format"] = d.exportDataFormat;
            o["gzip"] = d.flags.exportGzip;
            o["seriesDirectories"] = d.flags.exportWriteSeriesDirectory;
            o["preserveSeriesNumbers"] = d.flags.exportPreserveSeriesNumber;
            o["phaseDirectories"] = d.flags.exportWritePhaseDirectory;
            o["behavioralLocation"] = behDir;
            o["behavioralOnly"] = d.flags.behOnly;
            o["optional"] = d.flags.isOptional;
            items.append(o);
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
 * @brief Create README.txt: what the container is, how to run it, and the expected /input layout. It goes next to
 * the .sif in the export directory and into the image as /pipeline/README.txt and the %help text (apptainer run-help)
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
 * runs the validation. Finish() does all the parsing of its output. It always writes build.exitcode last.
 * The settings block at the top is generated for this build; the rest is the same for every build
 *
 * Files it writes into the build directory:
 *   build.log             everything the script prints
 *   build-warnings.txt    one warning per line, added to build_warnings by Finish()
 *   build-error.txt       the reason the build stopped, if it did
 *   sysinfo.txt           key=value: host, OS, base image, package manager, tool versions, cpus, memory, scratch space
 *   software.txt          key=value: interpreter paths and versions, package count
 *   env.txt               the login environment
 *   packages.txt          installed packages: name, version-release, arch (tab separated)
 *   modules.txt           'module list' output
 *   opt.txt               the entries in /opt
 *   trace.log             strace output from the reference run
 *   reference-run.log     output of the reference run
 *   validation.txt        key=value summary, then the missing and extra file lists (validation only)
 *   <pipeline>-v<N>.sif   the container
 *   build.exitcode        0 on success. Written last, atomically
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
    /* TODO: from the pipeline's container settings, once they exist. A docker:// URI or the path of a .sif */
    settings << "BASE_OVERRIDE=''";
    settings << "";

    /* the same for every build */
    static const char *body = R"BASH(# ---------- fixed part ----------
set -u

APPTAINER=""
MISSING_TOOLS=()
OS_ID=""; OS_VERSION_ID=""; OS_ID_LIKE=""; OS_PRETTY_NAME=""; OS_FAMILY="unknown"
PKG_MANAGER="none"
BASE_IMAGE=""

log()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }
warn() { log "WARNING: $*"; echo "$*" >> "$BUILDDIR/build-warnings.txt"; }
fail() { log "ERROR: $*"; echo "$*" >> "$BUILDDIR/build-error.txt"; exit 1; }

# kv <file> <key> <value>: one key=value line. Newlines in the value are flattened
kv() { printf '%s=%s\n' "$2" "$(printf '%s' "$3" | tr '\r\n' '  ')" >> "$1"; }

# first line of a command's output, with a time limit so a hung tool can't stall the build
firstline() { timeout 60 "$@" 2>&1 | head -n 1; }

# always write build.exitcode, even on failure, so the NiDB module stops polling. Write it atomically
finish() {
    local rc=$?
    log "build-container.sh finished with exit code [$rc]"
    echo "$rc" > "$BUILDDIR/build.exitcode.tmp" && mv -f "$BUILDDIR/build.exitcode.tmp" "$BUILDDIR/build.exitcode"
}

# find the tools. Missing ones stop the build in check_tools, after the system info is collected
find_tools() {
    local t
    for t in strace objdump ldd find timeout sha256sum; do
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
    for t in R Rscript perl python python3 java tcsh csh matlab octave; do
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
# NIDB_PIPELINE_DIR, NIDB_INPUT_DIR and NIDB_OUTPUT_DIR in place of /pipeline, /input and /output
reference_run() {
    local scratch="$BUILDDIR/reference" rc
    if [ ! -f "$BUILDDIR/run.sh" ]; then fail "run.sh is missing from the build directory"; fi
    rm -rf "$scratch"
    mkdir -p "$scratch" || fail "Unable to create [$scratch]"

    log "Reference run: subject [$REF_SUBJECTUID] study [$REF_STUDYNUM] [$REF_STUDYDATETIME]"
    NIDB_PIPELINE_DIR="$BUILDDIR" NIDB_INPUT_DIR="$BUILDDIR/input" NIDB_OUTPUT_DIR="$scratch" \
        strace -f -s 4096 -e trace=file,process -o "$BUILDDIR/trace.log" \
        bash -l "$BUILDDIR/run.sh" --subjectuid "$REF_SUBJECTUID" --studynum "$REF_STUDYNUM" --studydatetime "$REF_STUDYDATETIME" > "$BUILDDIR/reference-run.log" 2>&1
    rc=$?
    log "Reference run finished with exit code [$rc]"

    if [ ! -s "$BUILDDIR/trace.log" ]; then fail "The reference run produced no trace. Check that strace works on this node (ptrace_scope in sysinfo.txt)"; fi
    if [ $rc -ne 0 ]; then warn "The reference run exited with code [$rc], so the trace may not include everything the pipeline uses. See reference-run.log"; fi
}

# sort the traced files and copy them into staging/, and write environment.txt (doc section 7, step 2)
capture_software() {
    # TODO: package/opt/library-root/unowned sorting, ldd pass, deny list, glibc check, environment
    fail "Software capture is not implemented yet"
}

write_definition() {
    echo "Bootstrap: localimage"
    echo "From: $BUILDDIR/base"
    echo ""
    echo "%environment"
    if [ -f "$BUILDDIR/environment.txt" ]; then sed 's/^/    /' "$BUILDDIR/environment.txt"; fi
    echo ""
    echo "%runscript"
    echo '    exec /pipeline/run.sh "$@"'
    echo ""
    echo "%labels"
    echo "    org.nidb.pipeline.name $PIPELINE_NAME"
    echo "    org.nidb.pipeline.version $PIPELINE_VERSION"
    echo "    org.nidb.base $BASE_IMAGE"
    echo "    org.nidb.buildos $OS_PRETTY_NAME"
    echo "    org.nidb.exported $(date '+%Y-%m-%d')"
    echo ""
    # README.txt is written by NiDB: usage, arguments and the expected /input layout
    echo "%help"
    sed 's/^/    /' "$BUILDDIR/README.txt"
}

# sandbox from the base image, add the captured software and /pipeline, then build the .sif (doc section 8, steps 4.4-4.5)
build_image() {
    export APPTAINER_TMPDIR="${APPTAINER_TMPDIR:-${TMPDIR:-/tmp}}"
    cd "$BUILDDIR" || fail "Unable to cd into [$BUILDDIR]"

    # sandboxes can contain read-only directories
    if [ -d base ]; then chmod -R u+w base 2>/dev/null; rm -rf base; fi

    log "Creating a sandbox from base image [$BASE_IMAGE]"
    "$APPTAINER" build --sandbox base "$BASE_IMAGE" || fail "Unable to create a sandbox from base image [$BASE_IMAGE]"

    log "Copying the captured software and the pipeline into the sandbox"
    cp -a staging/. base/ || fail "Unable to copy the captured software into the sandbox"
    mkdir -p base/pipeline base/input base/output || fail "Unable to create /pipeline, /input and /output in the sandbox"
    cp -f run.sh run.sh.template manifest.json requiredvars.txt README.txt base/pipeline/ || fail "Unable to copy the pipeline files into the sandbox"
    chmod 755 base/pipeline/run.sh

    write_definition > container.def

    log "Building [$SIF_NAME]"
    rm -f "$SIF_NAME"
    "$APPTAINER" build "$SIF_NAME" container.def || fail "Unable to build [$SIF_NAME]. If the build needs root, this node needs fakeroot (see 'Cluster requirements' in the NiDB container documentation)"
}

# run the new image on the reference input and compare the output file list with the reference analysis (doc section 8, step 4.6)
validate() {
    local out="$BUILDDIR/validation-output" f="$BUILDDIR/validation.txt" rc nmissing nextra
    rm -rf "$out"
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

# ---------- main ----------
cd "$BUILDDIR" || { echo "Unable to cd into the build directory [$BUILDDIR]"; exit 1; }
exec >> "$BUILDDIR/build.log" 2>&1
rm -f build.exitcode build.exitcode.tmp build-error.txt build-warnings.txt
trap finish EXIT
trap 'exit 143' TERM
trap 'exit 130' INT

log "Building container [$CONTAINER_ID] for pipeline [$PIPELINE_NAME] version [$PIPELINE_VERSION] on $(hostname)"

# the login environment, before this script changes anything
env | LC_ALL=C sort > "$BUILDDIR/env.txt"

find_tools
detect_os
collect_sysinfo
collect_software
check_tools
if [ -z "$BASE_IMAGE" ]; then fail "No base image for this OS [$OS_PRETTY_NAME] (ID [$OS_ID], VERSION_ID [$OS_VERSION_ID])"; fi
log "OS [$OS_PRETTY_NAME], family [$OS_FAMILY], package manager [$PKG_MANAGER], base image [$BASE_IMAGE]"

reference_run
capture_software
build_image
if [ "$RUN_VALIDATION" = "1" ]; then validate; else log "Validation run skipped"; fi

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
    if (p.clusterType == "slurm") {
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
       writes build.exitcode, which the module polls for */
    jobfile += "exec /bin/bash -l " + ShellQuote(clusterBuildDir + "/build-container.sh") + "\n";

    QString path = c.buildDir + "/build.job";
    if (!WriteTextFile(path, jobfile, false)) {
        msg = "Unable to write [" + path + "]";
        return false;
    }
    QFile::setPermissions(path, QFileDevice::ReadOwner | QFileDevice::WriteOwner | QFileDevice::ExeOwner | QFileDevice::ReadGroup | QFileDevice::ExeGroup | QFileDevice::ReadOther | QFileDevice::ExeOther);

    AppendBuildLog(c.containerID, QString("Wrote build.job for [%1] queue [%2]. Wall time [%3]").arg(p.clusterType == "slurm" ? "slurm" : "SGE").arg(p.clusterQueue).arg(wallTime > 0 ? wallTimeStr : "none"));
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
/* --------- GetExportDir ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Where the finished container goes
 * @param p The pipeline
 * @param version Pipeline version
 * @return <exportdir>/NiDB-Apptainer-<pipeline>-v<version>
 */
QString modulePipelineContainer::GetExportDir(pipeline &p, int version) {
    return QString("%1/NiDB-Apptainer-%2-v%3").arg(n->cfg["exportdir"]).arg(p.name).arg(version);
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

    QSqlQuery q;
    q.prepare("update pipeline_containers set build_status = 'error', build_enddate = now() where pipelinecontainer_id = :id");
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
