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

    /* 'started' means a previous run of this module stopped part way through Prepare(). The build
       directory may be incomplete, so don't try to resume it */
    for (containerRequest c : GetRequests("started", containerID)) {
        SetError(c.containerID, "The pipelinecontainer module stopped while preparing this build. Create the container again");
        numDone++;
    }

    /* new requests: prepare the build directory and submit the build job */
    for (containerRequest c : GetRequests("submitted", containerID)) {
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return numDone; }

        /* mark as started, unless another instance got to it first */
        if (!ClaimRequest(c, "submitted", "started"))
            continue;
        numDone++;

        QString msg;
        if (!Prepare(c, msg)) {
            SetError(c.containerID, msg);
            continue;
        }
        if (!SubmitBuildJob(c, msg)) {
            SetError(c.containerID, msg);
            continue;
        }
    }

    /* running builds: finish any that have written build.exitcode */
    for (containerRequest c : GetRequests("building", containerID)) {
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
/* --------- GetRequests ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Get the pipeline_containers rows with a given status
 * @param status The build_status to select
 * @param containerID If non-zero, only this row
 * @return List of requests, oldest first
 */
QList<modulePipelineContainer::containerRequest> modulePipelineContainer::GetRequests(QString status, int containerID) {
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
/* --------- Prepare ---------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Step 2. Check the pipeline is exportable, then write the build directory: run.sh, run.sh.template,
 * manifest.json, requiredvars.txt, the staged reference input, export.log, build-container.sh and build.job
 * @param c The request
 * @param msg Error message, if any
 * @return true if the build directory is ready to submit
 */
bool modulePipelineContainer::Prepare(containerRequest &c, QString &msg) {
    AppendBuildLog(c.containerID, QString("Preparing container for pipeline [%1] version [%2], reference analysis [%3], validation [%4]").arg(c.pipelineID).arg(c.pipelineVersion).arg(c.referenceAnalysisID).arg(c.runValidation ? "yes" : "no"));

    /* TODO: the pipeline object loads the current version. Load c.pipelineVersion instead, since the pipeline
       may have been edited since the request was submitted */
    pipeline p(c.pipelineID, n);
    if (!p.isValid) {
        msg = QString("Unable to load pipeline [%1]: %2").arg(c.pipelineID).arg(p.msg);
        return false;
    }

    QStringList errors, warnings;
    if (!CheckExportable(p, c.pipelineVersion, errors, warnings)) {
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

    if (!MakePath(c.buildDir, msg))
        return false;

    QString manifest = CreateManifest(p, c.pipelineVersion, requiredVars, warnings);

    /* TODO: write run.sh (copied from src/setup/apptainer), run.sh.template, manifest.json, requiredvars.txt and export.log into c.buildDir */
    Q_UNUSED(manifest);

    if (!StageReferenceInput(c, msg))
        return false;

    if (!WriteBuildJob(c, p, msg))
        return false;

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
/* --------- CheckExportable -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Export checks (doc sections 1, 3, 5): level 1 only, at least one enabled main step, a valid
 * reference analysis, no group or deprecated variables, and no NiDB storage paths in the steps
 * @param p The pipeline
 * @param version Pipeline version
 * @param errors Problems that stop the export
 * @param warnings Problems that don't
 * @return true if there are no errors
 */
bool modulePipelineContainer::CheckExportable(pipeline &p, int version, QStringList &errors, QStringList &warnings) {
    Q_UNUSED(version);

    if (p.level != 1)
        errors << QString("Only level 1 pipelines can be exported. This is a level [%1] pipeline").arg(p.level);

    /* TODO: enabled main steps, reference analysis, CheckVariables() and CheckPaths() on each step's command and working directory */
    Q_UNUSED(warnings);

    return (errors.size() == 0);
}


/* ---------------------------------------------------------- */
/* --------- CheckVariables --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check the {variables} in one step (doc section 3). Group variables, {first_*} and unrecognized
 * tokens are errors; {analysisid} and {command} are warnings
 * @param text The step command (or working directory)
 * @param step The step number, for messages
 * @param errors Problems that stop the export
 * @param warnings Problems that don't
 */
void modulePipelineContainer::CheckVariables(QString text, int step, QStringList &errors, QStringList &warnings) {
    /* TODO */
    Q_UNUSED(text);
    Q_UNUSED(step);
    Q_UNUSED(errors);
    Q_UNUSED(warnings);
}


/* ---------------------------------------------------------- */
/* --------- CheckPaths ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check one step for absolute NiDB storage paths (doc section 5): cfg archivedir, analysisdir,
 * analysisdirb, and the analysisdirs table
 * @param text The step command (or working directory)
 * @param step The step number, for messages
 * @param errors Problems that stop the export
 */
void modulePipelineContainer::CheckPaths(QString text, int step, QStringList &errors) {
    /* TODO */
    Q_UNUSED(text);
    Q_UNUSED(step);
    Q_UNUSED(errors);
}


/* ---------------------------------------------------------- */
/* --------- RenderScriptTemplate --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Render run.sh.template from the main script steps (doc sections 2-4). Export-time variables are
 * replaced; {subjectuid}, {studynum}, {studydatetime} and {analysisrootdir} are left for run.sh, lowercased
 * @param p The pipeline
 * @param version Pipeline version
 * @param script The rendered template
 * @param requiredVars The runtime variables the script uses
 * @param errors Problems that stop the export
 * @param warnings Problems that don't
 * @return true if there are no errors
 */
bool modulePipelineContainer::RenderScriptTemplate(pipeline &p, int version, QString &script, QStringList &requiredVars, QStringList &errors, QStringList &warnings) {
    /* TODO: share the step rendering with modulePipeline::CreateClusterJobFile() (doc section 9) */
    Q_UNUSED(p);
    Q_UNUSED(version);
    Q_UNUSED(script);
    Q_UNUSED(requiredVars);
    Q_UNUSED(warnings);
    errors << "RenderScriptTemplate() not implemented yet";
    return false;
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
 * @return The JSON text
 */
QString modulePipelineContainer::CreateManifest(pipeline &p, int version, const QStringList &requiredVars, const QStringList &warnings) {
    /* TODO: input layout, and the squirrel pipeline from p.GetSquirrelObject() */
    QJsonObject root;
    root["pipelineName"] = p.name;
    root["pipelineVersion"] = version;
    root["pipelineDescription"] = p.desc;
    root["exportDate"] = QDateTime::currentDateTime().toString(Qt::ISODate);
    root["requiredVariables"] = QJsonArray::fromStringList(requiredVars);
    root["exportWarnings"] = QJsonArray::fromStringList(warnings);

    return QJsonDocument(root).toJson();
}


/* ---------------------------------------------------------- */
/* --------- StageReferenceInput ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Stage the reference analysis' study input into <builddir>/input, as GetData() and the dependency
 * copy would (doc section 7 step 1). Needs those refactored to stage into an arbitrary directory
 * @param c The request
 * @param msg Error message, if any
 * @return true if the input was staged
 */
bool modulePipelineContainer::StageReferenceInput(containerRequest &c, QString &msg) {
    /* TODO */
    Q_UNUSED(c);
    msg = "StageReferenceInput() not implemented yet";
    return false;
}


/* ---------------------------------------------------------- */
/* --------- WriteBuildJob ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Copy build-container.sh into the build directory and write build.job: the scheduler header
 * (from the pipeline's cluster settings) plus a call to build-container.sh with the reference study's values
 * @param c The request
 * @param p The pipeline
 * @param msg Error message, if any
 * @return true if written
 */
bool modulePipelineContainer::WriteBuildJob(containerRequest &c, pipeline &p, QString &msg) {
    /* TODO */
    Q_UNUSED(c);
    Q_UNUSED(p);
    msg = "WriteBuildJob() not implemented yet";
    return false;
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
