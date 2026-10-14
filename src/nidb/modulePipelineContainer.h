/* ------------------------------------------------------------------------------
  NIDB modulePipelineContainer.h
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

#ifndef MODULEPIPELINECONTAINER_H
#define MODULEPIPELINECONTAINER_H
#include "nidb.h"
#include "pipeline.h"

/* builds Apptainer containers from pipelines. Requests are rows in pipeline_containers, created by
   pipelinecontainers.php. Design: doc/pipeline-apptainer.md */
class modulePipelineContainer
{
public:
    modulePipelineContainer(nidb *a);
    ~modulePipelineContainer();

    int Run(int containerID = 0);

private:
    nidb *n;
    QStringList storagePaths; /* NiDB archive and analysis paths, loaded by CheckPaths() */

    /* one pipeline_containers row */
    struct containerRequest {
        int containerID = 0;
        int pipelineID = 0;
        int pipelineVersion = 0;
        qint64 referenceAnalysisID = 0;
        bool runValidation = true;
        QString status;
        QString buildDir;   /* GetBuildDir(containerID) */
    };

    QList<containerRequest> GetExportRequests(QString status, int containerID);
    bool ClaimRequest(containerRequest &c, QString fromStatus, QString toStatus);

    /* build stages (doc section 8). After step 3 the build job reports its progress and result through analysisapi.php */
    bool PrepareExport(containerRequest &c, QString &msg);            /* step 2: checks, build directory */
    bool SubmitBuildJob(containerRequest &c, QString &msg);     /* step 3: submit build.job to the cluster */

    /* export checks (doc sections 1, 3, 5) */
    bool CheckIfExportable(pipeline &p, int version, QStringList &errors, QStringList &warnings);
    void CheckVariables(QString text, int step, QStringList &errors, QStringList &warnings);
    void CheckPaths(QString text, int step, QStringList &errors);
    void CheckContainers(QString text, int step, QStringList &errors);

    /* build directory contents (doc sections 4, 6, 8) */
    bool RenderScriptTemplate(pipeline &p, int version, QString &script, QStringList &requiredVars, QStringList &errors, QStringList &warnings);
    QString RenderVariables(QString text, QString pipelineName, QString workingDir, QString description);
    QStringList GetInputDirStructure(pipeline &p, int version, QJsonArray &items);
    QString CreateManifest(pipeline &p, int version, const QStringList &requiredVars, const QStringList &warnings, const QJsonArray &inputLayout);
    QString CreateReadme(pipeline &p, int version, const QStringList &requiredVars, const QStringList &inputLayout);
    bool StageReferenceInput(containerRequest &c, pipeline &p, QString &msg);
    bool WriteRunScript(containerRequest &c, QString &msg);
    bool WriteContainerBuildScript(containerRequest &c, pipeline &p, QString &msg);
    bool WriteClusterJob(containerRequest &c, pipeline &p, QString &msg);

    /* paths */
    QString GetBuildDir(int containerID);
    QString GetClusterBuildDir(int containerID);
    QString GetContainerFileName(pipeline &p, int version);

    /* pipeline_containers updates */
    void SetStatus(int containerID, QString status);
    void SetError(int containerID, QString msg);
    void AppendBuildLog(int containerID, QString log);
    void AppendBuildWarnings(int containerID, QStringList warnings);
};

#endif // MODULEPIPELINECONTAINER_H
