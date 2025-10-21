/* ------------------------------------------------------------------------------
  NIDB modulePipeline.h
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

#ifndef MODULEPIPELINE_H
#define MODULEPIPELINE_H
#include "nidb.h"
#include "study.h"
#include "series.h"
#include "analysis.h"
#include "pipeline.h"
#include "imageio.h"
#include "archiveio.h"

/* data structures used in this class */
struct pipelineStep {
    int id;
    QString command;
    bool supplement;
    QString workingDir;
    int order;
    QString description; /* the comment */
    bool logged;
    bool enabled;
    QString logfile;
};

struct dataDefinitionStep {
    int id;
    int order;
    QString type;
    QString criteria;
    QString assoctype;
    QString protocol;
    QString modality;
    QString dataformat;
    QString imagetype;
    QString location;
    QString behformat;
    QString behdir;
    QString numboldreps; /* this is stored as a string comparison */
    QString level;
    qint64 datadownloadid;
	struct flag {
		bool enabled; /*!< whether this step is enabled */
		bool optional; /*!< if the step is optional */
		bool gzip; /*!< whether Nifti data should be zipped (.nii.gz) */
		bool preserveSeries; /*!< whether to preserve the series number, if writing series directories. Otherwise the series directories are generated sequentially starting at 1 */
		bool primaryProtocol; /*!< true if this is the primary protocol. this determines if this study will be used as the parent for child pipelines */
		bool usePhaseDir; /*!< whether to place data into a sub-directory based on the phase-encoding direction */
		bool useSeries; /*!< true to write each series to an individually numbered directory, otherwise write it to the directory specified in 'location' */
        bool behOnly; // download behavioral data only, no imaging data
	} flags;
};

class modulePipeline
{
public:
    modulePipeline();
    modulePipeline(nidb *n);
    ~modulePipeline();

    int Run();

    QList<dataDefinitionStep> GetPipelineDataDef(int pipelineid, int version);
    QList<int> GetPipelineList();
    QList<int> GetStudyToDoList(int pipelineid, QString modality, int depend, QString groupids, qint64 &runnum);
    QList<pipelineStep> GetPipelineSteps(int pipelineid, int version);
    QString CheckDependency(int sid, int pipelinedep);
    QString FormatCommand(int pipelineid, QString clusteranalysispath, QString command, QString analysispath, qint64 analysisid, QString uid, int studynum, QString studydatetime, QString pipelinename, QString workingdir, QString description);
    bool CreateClusterJobFile(QString jobfilename, QString clustertype, QString queue, qint64 analysisid, QString uid, int studynum, QString analysispath, bool usetmpdir, QString tmpdir, QString studydatetime, QString pipelinename, int pipelineid, QString resultscript, int maxwalltime, int numcores, double memory, QList<pipelineStep> steps, bool runsupplement = false);
    bool GetData(int studyid, QString analysispath, QString uid, qint64 analysisid, int pipelineid, int pipelinedep, QString deplevel, QList<dataDefinitionStep> datadef, int &numdownloaded, QString &datalog);

    /* logging and record-keeping */
    bool UpdateAnalysisStatus(qint64 analysisid, QString status, QString statusmsg, int jobid, int numseries, QString datalog, QString datatable, bool currentStartDate, bool currentEndDate, int supplementFlag, int rerunFlag);
    qint64 RecordDataDownload(qint64 id, qint64 analysisid, QString modality, int checked, int found, int seriesid, QString downloadpath, int step, QString msg);
    void ClearPipelineHistory();
    void RecordPipelineEvent(int pipelineid, qint64 &runnum, qint64 analysisid, QString event, QString message);

    /* information */
    QString GetAnalysisClusterPath(QString dirStructureCode, QString pipelineName="", QString UID="", int studyNum=-1);
    QString GetAnalysisLocalPath(QString dirStructureCode, QString pipelineName="", QString UID="", int studyNum=-1);
    QString GetBehPath(QString behformat, QString analysispath, QString location, QString behdir, int newseriesnum);
    QString GetPipelineStatus(int pipelineid);
    QStringList GetGroupList(int pid);
    QStringList GetUIDStudyNumListByGroup(QString group);
    QStringList GetUIDStudyNumListByStudyIDs(QList<int> studyRowIDs);
    bool IsPipelineEnabled(int pid);
    int IsQueueFilled(int pid);

    /* simple actions */
    void DisablePipeline(int pipelineid);
    void SetPipelineDisabled(int pid);
    void SetPipelineProcessStatus(QString status, int pipelineid, int studyid);
    void SetPipelineRunning(int pid, QString msg);
    void SetPipelineStatusMessage(int pid, QString msg);
    void SetPipelineStopped(int pid, QString msg);

private:
    nidb *n;
    imageIO *img;
};

#endif // MODULEPIPELINE_H
