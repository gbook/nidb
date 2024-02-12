/* ------------------------------------------------------------------------------
  Squirrel pipeline.cpp
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

#include "squirrelPipeline.h"
#include <QFile>
#include <QTextStream>
#include "utils.h"


/* ---------------------------------------------------------- */
/* --------- pipeline --------------------------------------- */
/* ---------------------------------------------------------- */
squirrelPipeline::squirrelPipeline()
{

}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelPipeline::Get
 * @return true if successful
 *
 * This function will attempt to load the pipeline data from
 * the database. The pipelineRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelPipeline::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Pipeline where PipelineRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("PipelineRowID").toLongLong();
        pipelineName = q.value("PipelineName").toString();
        description = q.value("Description").toString();
        createDate = q.value("Datetime").toDateTime();
        level = q.value("Level").toInt();
        primaryScript = q.value("PrimaryScript").toString();
        secondaryScript = q.value("SecondaryScript").toString();
        version = q.value("Version").toInt();
        //parentPipelines = q.value("CompleteFiles").toString();
        completeFiles = q.value("CompleteFiles").toString().split(",");
        dataCopyMethod = q.value("DataCopyMethod").toString();
        depDir = q.value("DependencyDirectory").toString();
        depLevel = q.value("DependencyLevel").toString();
        depLinkType = q.value("DependencyLinkType").toString();
        dirStructure = q.value("DirStructure").toString();
        directory = q.value("Directory").toString();
        group = q.value("GroupName").toString();
        groupType = q.value("GroupType").toString();
        notes = q.value("Notes").toString();
        resultScript = q.value("ResultScript").toString();
        tmpDir = q.value("TempDir").toString();
        flags.useProfile = q.value("FlagUseProfile").toBool();
        flags.useTmpDir = q.value("FlagUseTempDir").toBool();
        clusterType = q.value("ClusterType").toString();
        clusterUser = q.value("ClusterUser").toString();
        clusterQueue = q.value("ClusterQueue").toString();
        clusterSubmitHost = q.value("ClusterSubmitHost").toString();
        numConcurrentAnalyses = q.value("NumConcurrentAnalysis").toInt();
        maxWallTime = q.value("MaxWallTime").toInt();
        submitDelay = q.value("SubmitDelay").toInt();
        virtualPath = q.value("VirtualPath").toString();

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(objectID, "pipeline");

        valid = true;
        return true;
    }
    else {
        valid = false;
        err = "objectID not found in database";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- Store ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelPipeline::Store
 * @return true if successful
 *
 * This function will attempt to load the pipeline data from
 * the database. The pipelineRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelPipeline::Store() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Pipeline (PipelineName, Description, Datetime, Level, PrimaryScript, SecondaryScript, Version, CompleteFiles, DataCopyMethod, DependencyDirectory, DependencyLevel, DependencyLinkType, DirStructure, Directory, GroupName, GroupType, Notes, ResultScript, TempDir, FlagUseProfile, FlagUseTempDir, ClusterType, ClusterUser, ClusterQueue, ClusterSubmitHost, NumConcurrentAnalysis, MaxWallTime, SubmitDelay, VirtualPath) values (:PipelineName, :Description, :Datetime, :Level, :PrimaryScript, :SecondaryScript, :Version, :CompleteFiles, :DataCopyMethod, :DependencyDirectory, :DependencyLevel, :DependencyLinkType, :DirStructure, :Directory, :GroupName, :GroupType, :Notes, :ResultScript, :TempDir, :FlagUseProfile, :FlagUseTempDir, :ClusterType, :ClusterUser, :ClusterQueue, :ClusterSubmitHost, :NumConcurrentAnalysis, :MaxWallTime, :SubmitDelay, :VirtualPath)");
        q.bindValue(":PipelineName", pipelineName);
        q.bindValue(":Description", description);
        q.bindValue(":Datetime", createDate);
        q.bindValue(":Level", level);
        q.bindValue(":PrimaryScript", primaryScript);
        q.bindValue(":SecondaryScript", secondaryScript);
        q.bindValue(":Version", version);
        q.bindValue(":CompleteFiles", completeFiles.join(","));
        q.bindValue(":DataCopyMethod", dataCopyMethod);
        q.bindValue(":DependencyDirectory", depDir);
        q.bindValue(":DependencyLevel", depLevel);
        q.bindValue(":DependencyLinkType", depLinkType);
        q.bindValue(":DirStructure", dirStructure);
        q.bindValue(":Directory", directory);
        q.bindValue(":GroupName", group);
        q.bindValue(":GroupType", groupType);
        q.bindValue(":Notes", notes);
        q.bindValue(":ResultScript", resultScript);
        q.bindValue(":TempDir", tmpDir);
        q.bindValue(":FlagUseProfile", flags.useProfile);
        q.bindValue(":FlagUseTempDir", flags.useTmpDir);
        q.bindValue(":ClusterType", clusterType);
        q.bindValue(":ClusterUser", clusterUser);
        q.bindValue(":ClusterQueue", clusterQueue);
        q.bindValue(":ClusterSubmitHost", clusterSubmitHost);
        q.bindValue(":NumConcurrentAnalysis", numConcurrentAnalyses);
        q.bindValue(":MaxWallTime", maxWallTime);
        q.bindValue(":SubmitDelay", submitDelay);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Pipeline set PipelineName = :PipelineName, Description = :Description, Datetime = :Datetime, Level = :Level, PrimaryScript = :PrimaryScript, SecondaryScript = :SecondaryScript, Version = :Version, CompleteFiles = :CompleteFiles, DataCopyMethod = :DataCopyMethod, DependencyDirectory = :DependencyDirectory, DependencyLevel = :DependencyLevel, DependencyLinkType = :DependencyLinkType, DirStructure = :DirStructure, Directory = :Directory, GroupName = :GroupName, GroupType = :GroupType, Notes = :Notes, ResultScript = :ResultScript, TempDir = :TempDir, FlagUseProfile = :FlagUseProfile, FlagUseTempDir = :FlagUseTempDir, ClusterType = :ClusterType, ClusterUser = :ClusterUser, ClusterQueue = :ClusterQueue, ClusterSubmitHost = :ClusterSubmitHost, NumConcurrentAnalysis = :NumConcurrentAnalysis, MaxWallTime = :MaxWallTime, SubmitDelay = :SubmitDelay, VirtualPath = :VirtualPath where PipelineRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":PipelineName", pipelineName);
        q.bindValue(":Description", description);
        q.bindValue(":Datetime", createDate);
        q.bindValue(":Level", level);
        q.bindValue(":PrimaryScript", primaryScript);
        q.bindValue(":SecondaryScript", secondaryScript);
        q.bindValue(":Version", version);
        q.bindValue(":CompleteFiles", completeFiles.join(","));
        q.bindValue(":DataCopyMethod", dataCopyMethod);
        q.bindValue(":DependencyDirectory", depDir);
        q.bindValue(":DependencyLevel", depLevel);
        q.bindValue(":DependencyLinkType", depLinkType);
        q.bindValue(":DirStructure", dirStructure);
        q.bindValue(":Directory", directory);
        q.bindValue(":GroupName", group);
        q.bindValue(":GroupType", groupType);
        q.bindValue(":Notes", notes);
        q.bindValue(":ResultScript", resultScript);
        q.bindValue(":TempDir", tmpDir);
        q.bindValue(":FlagUseProfile", flags.useProfile);
        q.bindValue(":FlagUseTempDir", flags.useTmpDir);
        q.bindValue(":ClusterType", clusterType);
        q.bindValue(":ClusterUser", clusterUser);
        q.bindValue(":ClusterQueue", clusterQueue);
        q.bindValue(":ClusterSubmitHost", clusterSubmitHost);
        q.bindValue(":NumConcurrentAnalysis", numConcurrentAnalyses);
        q.bindValue(":MaxWallTime", maxWallTime);
        q.bindValue(":SubmitDelay", submitDelay);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(objectID, "pipeline", stagedFiles);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ToJSON ----------------------------------------- */
/* ---------------------------------------------------------- */
/* if path is specified, write the full JSON object to that
 * path and return a small JSON object */

/**
 * @brief Get JSON object describing the pipeline
 * @param path if a path is specified, the full JSON object is written
 * to that path and a smaller JSON object is returned
 * @return JSON object
 */

QJsonObject squirrelPipeline::ToJSON(QString path) {
    QJsonObject json;

    json["ClusterQueue"] = clusterQueue;
    json["ClusterSubmitHost"] = clusterSubmitHost;
    json["ClusterType"] = clusterType;
    json["ClusterUser"] = clusterUser;
    json["CompleteFiles"] = QJsonArray::fromStringList(completeFiles);
    json["CreateDate"] = createDate.toString();
    json["DataCopyMethod"] = dataCopyMethod;
    json["DepDir"] = depDir;
    json["DepLevel"] = depLevel;
    json["DepLinkType"] = depLinkType;
    json["Description"] = description;
    json["DirStructure"] = dirStructure;
    json["Directory"] = directory;
    json["Group"] = group;
    json["GroupType"] = groupType;
    json["Level"] = level;
    json["MaxWallTime"] = maxWallTime;
    json["Notes"] = notes;
    json["NumConcurrentAnalyses"] = numConcurrentAnalyses;
    json["ParentPipelines"] = parentPipelines.join(",");
    json["PipelineName"] = pipelineName;
    json["PipelineVersion"] = version;
    json["ResultScript"] = resultScript;
    json["SubmitDelay"] = submitDelay;
    json["TempDir"] = tmpDir;
    json["UseProfile"] = flags.useProfile;
    json["UseTempDir"] = flags.useTmpDir;
    json["VirtualPath"] = virtualPath;

    /* add the dataSteps */
    QJsonArray JSONdataSteps;
    for (int i=0; i < dataSteps.size(); i++) {
        QJsonObject dataStep;
        dataStep["AssociationType"] = dataSteps[i].associationType;
        dataStep["BehDir"] = dataSteps[i].behDir;
        dataStep["BehFormat"] = dataSteps[i].behFormat;
        dataStep["DataFormat"] = dataSteps[i].dataFormat;
        dataStep["DataLevel"] = dataSteps[i].datalevel;
        dataStep["Enabled"] = dataSteps[i].flags.enabled;
        dataStep["Gzip"] = dataSteps[i].flags.gzip;
        dataStep["ImageType"] = dataSteps[i].imageType;
        dataStep["Location"] = dataSteps[i].location;
        dataStep["Modality"] = dataSteps[i].modality;
        dataStep["NumBOLDreps"] = dataSteps[i].numBOLDreps;
        dataStep["NumImagesCriteria"] = dataSteps[i].numImagesCriteria;
        dataStep["Optional"] = dataSteps[i].flags.optional;
        dataStep["Order"] = dataSteps[i].order;
        dataStep["PreserveSeries"] = dataSteps[i].flags.preserveSeries;
        dataStep["PrimaryProtocol"] = dataSteps[i].flags.primaryProtocol;
        dataStep["Protocol"] = dataSteps[i].protocol;
        dataStep["SeriesCriteria"] = dataSteps[i].seriesCriteria;
        dataStep["UsePhaseDir"] = dataSteps[i].flags.usePhaseDir;
        dataStep["UseSeries"] = dataSteps[i].flags.useSeries;

        JSONdataSteps.append(dataStep);
    }
    json["NumDataSteps"] = JSONdataSteps.size();
    json["dataSteps"] = JSONdataSteps;

    /* write all pipeline info to path */
    QString m;
    QString pipelinepath = QString("%1/pipelines/%2").arg(path).arg(pipelineName);
    if (utils::MakePath(pipelinepath, m)) {
        //QByteArray j = QJsonDocument(json).toJson();
        //QFile fout(QString(pipelinepath + "/pipeline.json"));
        //if (fout.open(QIODevice::WriteOnly))
        //    fout.write(j);
        //else
        //    Print("Error writing file [" + pipelinepath + "/pipeline.json]");

        /* write the scripts */
        if (!utils::WriteTextFile(QString(pipelinepath + "/primaryScript.sh"), primaryScript))
            utils::Print("Error writing primary script [" + pipelinepath + "/primaryScript.sh]");

        if (!utils::WriteTextFile(QString(pipelinepath + "/secondaryScript.sh"), secondaryScript))
            utils::Print("Error writing secondary script [" + pipelinepath + "/secondaryScript.sh]");

    }
    else {
        utils::Print("Error creating path [" + pipelinepath + "] because of [" + m + "]");
    }

    /* return JSON object */
    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintPipeline ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print pipeline details
 */
void squirrelPipeline::PrintPipeline() {

    utils::Print("\t----- PIPELINE -----");

    utils::Print(QString("\tClusterQueue: %1").arg(clusterQueue));
    utils::Print(QString("\tClusterSubmitHost: %1").arg(clusterSubmitHost));
    utils::Print(QString("\tClusterType: %1").arg(clusterType));
    utils::Print(QString("\tClusterUser: %1").arg(clusterUser));
    utils::Print(QString("\tCompleteFiles: %1").arg(completeFiles.join(",")));
    utils::Print(QString("\tCreateDate: %1").arg(createDate.toString()));
    utils::Print(QString("\tDataCopyMethod: %1").arg(dataCopyMethod));
    utils::Print(QString("\tDepDirectory: %1").arg(depDir));
    utils::Print(QString("\tDepLevel: %1").arg(depLevel));
    utils::Print(QString("\tDepLinkType: %1").arg(depLinkType));
    utils::Print(QString("\tDescription: %1").arg(description));
    utils::Print(QString("\tDirStructure: %1").arg(dirStructure));
    utils::Print(QString("\tDirectory: %1").arg(directory));
    utils::Print(QString("\tGroup: %1").arg(group));
    utils::Print(QString("\tGroupType: %1").arg(groupType));
    utils::Print(QString("\tLevel: %1").arg(level));
    utils::Print(QString("\tMaxWallTime: %1").arg(maxWallTime));
    utils::Print(QString("\tNotes: %1").arg(notes));
    utils::Print(QString("\tNumConcurrentAnalyses: %1").arg(numConcurrentAnalyses));
    utils::Print(QString("\tParentPipelines: %1").arg(parentPipelines.join(",")));
    utils::Print(QString("\tPipelineName: %1").arg(pipelineName));
    utils::Print(QString("\tResultScript: %1").arg(resultScript));
    utils::Print(QString("\tSubmitDelay: %1").arg(submitDelay));
    utils::Print(QString("\tTempDir: %1").arg(tmpDir));
    utils::Print(QString("\tUseProfile: %1").arg(flags.useProfile));
    utils::Print(QString("\tUseTempDir: %1").arg(flags.useTmpDir));
    utils::Print(QString("\tVersion: %1").arg(version));

}
