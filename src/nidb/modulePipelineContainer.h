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

    /* also used by moduleFileIO::CancelPipelineContainer() */
    QString GetBuildDir(int containerID);
    void AppendBuildLog(int containerID, QString log);

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

    /* a pipeline whose steps run their own containers is exported as a launcher that runs the steps in segments:
       native steps in the pipeline's own image, and each container step from the host (doc section 11) */
    struct containerImage {
        QString name;           /* {image:<name>} */
        QString source;         /* the image as written in the step: an absolute path or a docker://, oras:// or library:// URI */
        QList<int> steps;
    };
    struct externalPath {
        QString name;           /* {path:<name>}, given to run-pipeline.sh as --path <name>=<host path> */
        QString source;         /* the host path as written in the step */
        QList<int> steps;
    };
    struct scriptSegment {
        int number = 0;
        bool container = false; /* a container step, run from the host. Otherwise native steps, run in pipeline.sif */
        QStringList images;     /* container segments: the images the step runs */
        QList<int> steps;
        QString script;         /* the segment's template */
        QString executable;     /* the lines that run, for the checks */
    };
    struct segmentedScript {
        QList<scriptSegment> segments;  /* empty if no step runs a container, which is a single-image export */
        QList<containerImage> images;
        QList<externalPath> paths;
    };

    /* one word or operator of a step command, from TokenizeCommand() */
    struct shellToken {
        int start = 0;          /* position in the command */
        int end = 0;            /* one past the last character */
        QString text;           /* the word with quotes and escapes removed, or the operator */
        bool isOperator = false;/* ; && || | & ( ) or a redirection */
        bool isRedirect = false;
        bool expands = false;   /* has an unquoted or double-quoted $ or backtick */
        bool quoted = false;
    };

    /* a change to a step command: the characters from start to end are replaced with text, which is shell text */
    struct textReplacement {
        int start;
        int end;
        QString text;
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

    /* container steps (doc section 11) */
    QList<shellToken> TokenizeCommand(const QString &command);
    bool RewriteContainerStep(QString &command, int step, QList<containerImage> &images, QList<externalPath> &paths, QStringList &errors, QStringList &warnings);
    void TranslateDockerRun(const QString &command, const QList<shellToken> &words, int k, int step, QList<textReplacement> &replacements, QList<containerImage> &images, QList<externalPath> &paths, QStringList &errors, QStringList &warnings);
    QString RewriteBinds(const shellToken &token, QString prefix, QString value, int step, QList<externalPath> &paths, QStringList &errors, QStringList &warnings);
    QString AddImage(QString source, int step, QList<containerImage> &images);
    QString AddExternalPath(QString source, int step, QList<externalPath> &paths);
    void CheckSegments(const segmentedScript &segmented, QStringList &warnings);

    /* build directory contents (doc sections 4, 6, 8, 11) */
    bool RenderScriptTemplate(pipeline &p, int version, QString &script, QStringList &requiredVars, segmentedScript &segmented, QStringList &errors, QStringList &warnings);
    QString RenderVariables(QString text, QString pipelineName, QString workingDir, QString description);
    QStringList GetInputDirStructure(pipeline &p, int version, QJsonArray &items);
    QString CreateManifest(pipeline &p, int version, const QStringList &requiredVars, const QStringList &warnings, const QJsonArray &inputLayout, const segmentedScript &segmented);
    QString CreateReadme(pipeline &p, int version, const QStringList &requiredVars, const QStringList &inputLayout);
    QString CreateLauncherReadme(pipeline &p, int version, const QStringList &requiredVars, const QStringList &inputLayout, const segmentedScript &segmented);
    bool WriteSegmentFiles(containerRequest &c, const segmentedScript &segmented, QString &msg);
    bool StageReferenceInput(containerRequest &c, pipeline &p, QString &msg);
    bool WriteRunScript(containerRequest &c, QString &msg);
    bool WriteLauncherScript(containerRequest &c, QString &msg);
    bool WriteContainerBuildScript(containerRequest &c, pipeline &p, QString &msg);
    bool WriteClusterJob(containerRequest &c, pipeline &p, QString &msg);

    /* paths */
    QString GetClusterBuildDir(int containerID);
    QString GetContainerFileName(pipeline &p, int version);
    QString GetExportDirName(pipeline &p, int version);

    /* pipeline_containers updates */
    void SetStatus(int containerID, QString status);
    void SaveExportType(int containerID, const segmentedScript &segmented);
    void SetError(int containerID, QString msg);
    void AppendBuildWarnings(int containerID, QStringList warnings);
};

#endif // MODULEPIPELINECONTAINER_H
