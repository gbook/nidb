/* ------------------------------------------------------------------------------
  NIDB pipeline.h
  Copyright (C) 2004 - 2024
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
    pipeline();
    pipeline(int id, nidb *a);
    nidb *n;

    /* object variables */
    QString msg;
    bool isValid = true;

    /* pipeline variables */
    QDateTime createDate;
    QDateTime lastCheck;
    QDateTime lastFinish;
    QDateTime lastStart;
    QList<int> groupIDs;
    QList<int> parentIDs;
    QString clusterType;
    QString clusterUser;
    QString dataCopyMethod;
    QString depDir;
    QString depLevel;
    QString depLinkType;
    QString desc;
    QString dirStructure;
    QString directory;
    QString group;
    QString groupType;
    QString name;
    QString notes;
    QString pipelineRootDir;
    QString queue;
    QString resultScript;
    QString status;
    QString statusMessage;
    QString submitHost;
    QString tmpDir;
    QStringList completeFiles;
    bool debug;
    bool enabled;
    bool groupBySubject;
    bool isHidden;
    bool isPrivate;
    bool removeData;
    bool testing;
    bool useProfile;
    bool useTmpDir;
    double memory;
    int dynamicGroupID;
    int level;
    int maxWallTime;
    int numConcurrentAnalysis;
    int numCores;
    int ownerID;
    int submitDelay;
    int version;

    //QJsonObject GetJSONObject(QString path);
    squirrelPipeline GetSquirrelObject();

private:
    void LoadPipelineInfo();
    //void AppendJSONParents(QJsonObject &obj, QList<int> parentIDs, QString path);
    //void AppendJSONDataSpec(QJsonObject &obj);
    //void AppendJSONScripts(QJsonObject &obj);
    QString GetPrimaryScript();
    QString GetSecondaryScript();
    QStringList GetParentList();

    int pipelineid;
};

#endif // PIPELINE_H
