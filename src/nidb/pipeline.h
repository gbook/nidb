/* ------------------------------------------------------------------------------
  NIDB pipeline.h
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

#ifndef PIPELINE_H
#define PIPELINE_H
#include "nidb.h"

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
    QString name;
    QString desc;
    int ownerID;
    QDateTime createDate;
    int level;
    QString group;
    QString directory;
    QString dirStructure;
    QString pipelineRootDir;
    bool useTmpDir;
    QString tmpDir;
    QList<int> parentDependencyIDs;
    QString depLevel;
    QString depDir;
    QString depLinkType;
    QList<int> groupIDs;
    QString groupType;
    bool groupBySubject;
    int dynamicGroupID;
    QString status;
    QString statusMessage;
    QDateTime lastStart;
    QDateTime lastFinish;
    QDateTime lastCheck;
    QStringList completeFiles;
    int numConcurrentAnalysis;
    QString queue;
    QString submitHost;
    QString clusterType;
    QString clusterUser;
    int maxWallTime;
    int submitDelay;
    QString dataCopyMethod;
    QString notes;
    bool useProfile;
    bool removeData;
    QString resultScript;
    bool enabled;
    bool testing;
    bool isPrivate;
    bool isHidden;
    bool debug;
    int version;

    QJsonObject GetJSONObject(QString path);

private:
    void LoadPipelineInfo();
    void AppendJSONParents(QJsonObject &obj, QList<int> parentIDs, QString path);
    void AppendJSONDataSpec(QJsonObject &obj);
    void AppendJSONScripts(QJsonObject &obj);

    int pipelineid;
};

#endif // PIPELINE_H
