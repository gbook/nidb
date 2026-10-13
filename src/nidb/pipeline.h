/* ------------------------------------------------------------------------------
  NIDB pipeline.h
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

#ifndef PIPELINE_H
#define PIPELINE_H
#include "nidb.h"
#include "squirrelPipeline.h"

class pipeline
{
public:
    pipeline(int id, nidb *a);
    nidb *n = nullptr;

    /* object variables */
    QString msg;
    bool isValid = false;
    int pipelineid = 0;

    /* pipeline variables */
    QDateTime createDate;
    QDateTime lastCheck;
    QDateTime lastFinish;
    QDateTime lastStart;
    QList<int> groupIDs;
    QList<int> parentIDs;
    QList<int> projectIDs;
    QString BIDSoutputDir;
    QString dataCopyMethod;
    QString depDir;
    QString depLevel; /* study (default) or subject */
    QString depLinkType;
    QString desc;
    QString dirStructure;
    QString directory;
    QString group;
    //QString groupType;
    QString name;
    QString notes;
    QString pipelineRootDir;
    QString resultScript;
    QString status;
    QString statusMessage;
    QString tmpDir;
    QStringList completeFiles;
    bool debug = false;
    bool enabled = false;
    bool groupBySubject = false;
    bool isHidden = false;
    bool isPrivate = false;
    bool outputBIDS = false;
    bool removeData = false;
    bool testing = false;
    bool useProfile = false;
    bool useTmpDir = false;
    int dynamicGroupID = 0;
    int level = 0;
    int numConcurrentAnalysis = 0;
    int ownerID = 0;
    int submitDelay = 0;
    int version = 0;
    QString clusterQueue;
    QString clusterSubmitHost;
    QString clusterSubmitHostUser;
    QString clusterType;
    QString clusterUser;
    double clusterMemory = 0.0; /* GB per core. Fractional values are allowed */
    int clusterMaxWallTime = 0;
    int clusterNumCores = 0;

    QString PrintPipelineVariables() const;
    squirrelPipeline GetSquirrelObject(QString databaseUUID);

private:
    void LoadPipelineInfo();
    QString GetScript(bool supplement);
    QStringList GetParentList();

    QString clusterSubmitHostSetting; /* submit host as stored in the pipeline, before the cfg default is applied. Used for export */

};

#endif // PIPELINE_H
