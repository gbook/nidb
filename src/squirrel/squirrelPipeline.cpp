/* ------------------------------------------------------------------------------
  Squirrel pipeline.cpp
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

#include "squirrelPipeline.h"
#include <QFile>
#include <QTextStream>
#include "utils.h"
#include "squirrel.h"


/* ---------------------------------------------------------- */
/* --------- pipeline --------------------------------------- */
/* ---------------------------------------------------------- */
squirrelPipeline::squirrelPipeline(QString dbID)
{
    databaseUUID = dbID;
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
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Pipeline where PipelineRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("PipelineRowID").toLongLong();
        //parentPipelines = q.value("CompleteFiles").toString();
        ClusterMaxWallTime = q.value("ClusterMaxWallTime").toInt();
        ClusterMemory = q.value("ClusterMemory").toInt();
        ClusterNumberCores = q.value("ClusterNumberCores").toInt();
        ClusterQueue = q.value("ClusterQueue").toString();
        ClusterSubmitHost = q.value("ClusterSubmitHost").toString();
        ClusterType = q.value("ClusterType").toString();
        ClusterUser = q.value("ClusterUser").toString();
        CompleteFiles = q.value("CompleteFiles").toString().split(",");
        CreateDate = q.value("Datetime").toDateTime();
        DataCopyMethod = q.value("DataCopyMethod").toString();
        DependencyDirectory = q.value("DependencyDirectory").toString();
        DependencyLevel = q.value("DependencyLevel").toString();
        DependencyLinkType = q.value("DependencyLinkType").toString();
        Description = q.value("Description").toString();
        Directory = q.value("Directory").toString();
        DirectoryStructure = q.value("DirectoryStructure").toString();
        Group = q.value("GroupName").toString();
        GroupType = q.value("GroupType").toString();
        Level = q.value("Level").toInt();
        Notes = q.value("Notes").toString();
        NumberConcurrentAnalyses = q.value("NumConcurrentAnalysis").toInt();
        PipelineName = q.value("PipelineName").toString();
        PrimaryScript = q.value("PrimaryScript").toString();
        ResultScript = q.value("ResultScript").toString();
        SecondaryScript = q.value("SecondaryScript").toString();
        SubmitDelay = q.value("SubmitDelay").toInt();
        TempDirectory = q.value("TempDirectory").toString();
        Version = q.value("Version").toInt();
        flags.UseProfile = q.value("FlagUseProfile").toBool();
        flags.UseTempDirectory = q.value("FlagUseTempDirectory").toBool();

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(databaseUUID, objectID, Pipeline);

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
    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    //utils::Print(QString("squirrelPipeline has been asked to Store(%1, %2). Current objectID [%3]").arg(PipelineName).arg(Version).arg(objectID));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Pipeline (PipelineName, Description, Datetime, Level, PrimaryScript, SecondaryScript, Version, CompleteFiles, DataCopyMethod, DependencyDirectory, DependencyLevel, DependencyLinkType, DirectoryStructure, Directory, GroupName, GroupType, Notes, ResultScript, TempDirectory, FlagUseProfile, FlagUseTempDirectory, ClusterType, ClusterUser, ClusterQueue, ClusterSubmitHost, NumConcurrentAnalysis, ClusterMaxWallTime, ClusterNumberCores, ClusterMemory, SubmitDelay, VirtualPath) values (:PipelineName, :Description, :Datetime, :Level, :PrimaryScript, :SecondaryScript, :Version, :CompleteFiles, :DataCopyMethod, :DependencyDirectory, :DependencyLevel, :DependencyLinkType, :DirectoryStructure, :Directory, :GroupName, :GroupType, :Notes, :ResultScript, :TempDirectory, :FlagUseProfile, :FlagUseTempDirectory, :ClusterType, :ClusterUser, :ClusterQueue, :ClusterSubmitHost, :NumConcurrentAnalysis, :ClusterMaxWallTime, :ClusterNumberCores, :ClusterMemory, :SubmitDelay, :VirtualPath)");
        q.bindValue(":PipelineName", PipelineName);
        q.bindValue(":Description", Description);
        q.bindValue(":Datetime", CreateDate);
        q.bindValue(":Level", Level);
        q.bindValue(":PrimaryScript", PrimaryScript);
        q.bindValue(":SecondaryScript", SecondaryScript);
        q.bindValue(":Version", Version);
        q.bindValue(":CompleteFiles", CompleteFiles.join(","));
        q.bindValue(":DataCopyMethod", DataCopyMethod);
        q.bindValue(":DependencyDirectory", DependencyDirectory);
        q.bindValue(":DependencyLevel", DependencyLevel);
        q.bindValue(":DependencyLinkType", DependencyLinkType);
        q.bindValue(":DirectoryStructure", DirectoryStructure);
        q.bindValue(":Directory", Directory);
        q.bindValue(":GroupName", Group);
        q.bindValue(":GroupType", GroupType);
        q.bindValue(":Notes", Notes);
        q.bindValue(":ResultScript", ResultScript);
        q.bindValue(":TempDirectory", TempDirectory);
        q.bindValue(":FlagUseProfile", flags.UseProfile);
        q.bindValue(":FlagUseTempDirectory", flags.UseTempDirectory);
        q.bindValue(":ClusterType", ClusterType);
        q.bindValue(":ClusterUser", ClusterUser);
        q.bindValue(":ClusterQueue", ClusterQueue);
        q.bindValue(":ClusterSubmitHost", ClusterSubmitHost);
        q.bindValue(":NumConcurrentAnalysis", NumberConcurrentAnalyses);
        q.bindValue(":ClusterMaxWallTime", ClusterMaxWallTime);
        q.bindValue(":ClusterNumberCores", ClusterNumberCores);
        q.bindValue(":ClusterMemory", ClusterMemory);
        q.bindValue(":SubmitDelay", SubmitDelay);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Pipeline set PipelineName = :PipelineName, Description = :Description, Datetime = :Datetime, Level = :Level, PrimaryScript = :PrimaryScript, SecondaryScript = :SecondaryScript, Version = :Version, CompleteFiles = :CompleteFiles, DataCopyMethod = :DataCopyMethod, DependencyDirectory = :DependencyDirectory, DependencyLevel = :DependencyLevel, DependencyLinkType = :DependencyLinkType, DirectoryStructure = :DirectoryStructure, Directory = :Directory, GroupName = :GroupName, GroupType = :GroupType, Notes = :Notes, ResultScript = :ResultScript, TempDirectory = :TempDirectory, FlagUseProfile = :FlagUseProfile, FlagUseTempDirectory = :FlagUseTempDirectory, ClusterType = :ClusterType, ClusterUser = :ClusterUser, ClusterQueue = :ClusterQueue, ClusterSubmitHost = :ClusterSubmitHost, NumConcurrentAnalysis = :NumConcurrentAnalysis, ClusterMaxWallTime = :ClusterMaxWallTime, ClusterNumberCores = :ClusterNumberCores, ClusterMemory = :ClusterMemory, SubmitDelay = :SubmitDelay, VirtualPath = :VirtualPath where PipelineRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":PipelineName", PipelineName);
        q.bindValue(":Description", Description);
        q.bindValue(":Datetime", CreateDate);
        q.bindValue(":Level", Level);
        q.bindValue(":PrimaryScript", PrimaryScript);
        q.bindValue(":SecondaryScript", SecondaryScript);
        q.bindValue(":Version", Version);
        q.bindValue(":CompleteFiles", CompleteFiles.join(","));
        q.bindValue(":DataCopyMethod", DataCopyMethod);
        q.bindValue(":DependencyDirectory", DependencyDirectory);
        q.bindValue(":DependencyLevel", DependencyLevel);
        q.bindValue(":DependencyLinkType", DependencyLinkType);
        q.bindValue(":DirStructure", DirectoryStructure);
        q.bindValue(":Directory", Directory);
        q.bindValue(":GroupName", Group);
        q.bindValue(":GroupType", GroupType);
        q.bindValue(":Notes", Notes);
        q.bindValue(":ResultScript", ResultScript);
        q.bindValue(":TempDirectory", TempDirectory);
        q.bindValue(":FlagUseProfile", flags.UseProfile);
        q.bindValue(":FlagUseTempDirectory", flags.UseTempDirectory);
        q.bindValue(":ClusterType", ClusterType);
        q.bindValue(":ClusterUser", ClusterUser);
        q.bindValue(":ClusterQueue", ClusterQueue);
        q.bindValue(":ClusterSubmitHost", ClusterSubmitHost);
        q.bindValue(":NumConcurrentAnalysis", NumberConcurrentAnalyses);
        q.bindValue(":ClusterMaxWallTime", ClusterMaxWallTime);
        q.bindValue(":ClusterNumberCores", ClusterNumberCores);
        q.bindValue(":ClusterMemory", ClusterMemory);
        q.bindValue(":SubmitDelay", SubmitDelay);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(databaseUUID, objectID, Pipeline, stagedFiles);

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

    json["ClusterMaxWallTime"] = ClusterMaxWallTime;
    json["ClusterMemory"] = ClusterMemory;
    json["ClusterNumberCores"] = ClusterNumberCores;
    json["ClusterQueue"] = ClusterQueue;
    json["ClusterSubmitHost"] = ClusterSubmitHost;
    json["ClusterType"] = ClusterType;
    json["ClusterUser"] = ClusterUser;
    json["CompleteFiles"] = QJsonArray::fromStringList(CompleteFiles);
    json["CreateDate"] = CreateDate.toString("yyyy-MM-dd HH:mm:ss");
    json["DataCopyMethod"] = DataCopyMethod;
    json["DependencyDirectory"] = DependencyDirectory;
    json["DependencyLevel"] = DependencyLevel;
    json["DependencyLinkType"] = DependencyLinkType;
    json["Description"] = Description;
    json["Directory"] = Directory;
    json["DirectoryStructure"] = DirectoryStructure;
    json["Group"] = Group;
    json["GroupType"] = GroupType;
    json["Level"] = Level;
    json["Notes"] = Notes;
    json["NumberConcurrentAnalyses"] = NumberConcurrentAnalyses;
    json["ParentPipelines"] = ParentPipelines.join(",");
    json["PipelineName"] = PipelineName;
    json["ResultScript"] = ResultScript;
    json["SubmitDelay"] = SubmitDelay;
    json["TempDirectory"] = TempDirectory;
    json["UseProfile"] = flags.UseProfile;
    json["UseTempDirectory"] = flags.UseTempDirectory;
    json["Version"] = Version;
    json["VirtualPath"] = VirtualPath();

    /* add the dataSteps */
    QJsonArray JSONdataSteps;
    for (int i=0; i < dataSteps.size(); i++) {
        QJsonObject dataStep;
        dataStep["AssociationType"] = dataSteps[i].AssociationType;
        dataStep["BehavioralDirectory"] = dataSteps[i].BehavioralDirectory;
        dataStep["BehavioralDirectoryFormat"] = dataSteps[i].BehavioralFormat;
        dataStep["DataFormat"] = dataSteps[i].DataFormat;
        dataStep["DataLevel"] = dataSteps[i].Datalevel;
        dataStep["Enabled"] = dataSteps[i].flags.Enabled;
        dataStep["Gzip"] = dataSteps[i].flags.Gzip;
        dataStep["ImageType"] = dataSteps[i].ImageType;
        dataStep["Location"] = dataSteps[i].Location;
        dataStep["Modality"] = dataSteps[i].Modality;
        dataStep["NumberBOLDreps"] = dataSteps[i].NumberBOLDreps;
        dataStep["NumberImagesCriteria"] = dataSteps[i].NumberImagesCriteria;
        dataStep["Optional"] = dataSteps[i].flags.Optional;
        dataStep["Order"] = dataSteps[i].Order;
        dataStep["PreserveSeries"] = dataSteps[i].flags.PreserveSeries;
        dataStep["PrimaryProtocol"] = dataSteps[i].flags.PrimaryProtocol;
        dataStep["Protocol"] = dataSteps[i].Protocol;
        dataStep["SeriesCriteria"] = dataSteps[i].SeriesCriteria;
        dataStep["UsePhaseDirectory"] = dataSteps[i].flags.UsePhaseDirectory;
        dataStep["UseSeriesDirectory"] = dataSteps[i].flags.UseSeries;

        JSONdataSteps.append(dataStep);
    }
    json["DataStepCount"] = JSONdataSteps.size();
    json["data-steps"] = JSONdataSteps;

    /* write all pipeline info to path */
    QString m;
    QString pipelinepath = QString("%1/pipelines/%2").arg(path).arg(PipelineName);
    if (utils::MakePath(pipelinepath, m)) {
        /* write the scripts */
        if (!utils::WriteTextFile(QString(pipelinepath + "/primaryScript.sh"), PrimaryScript))
            utils::Print("Error writing primary script [" + pipelinepath + "/primaryScript.sh]");

        if (!utils::WriteTextFile(QString(pipelinepath + "/secondaryScript.sh"), SecondaryScript))
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
QString squirrelPipeline::PrintPipeline() {
    QString str;

    str += utils::Print("\t----- PIPELINE -----");

    str += utils::Print(QString("\tClusterMaxWallTime: %1").arg(ClusterMaxWallTime));
    str += utils::Print(QString("\tClusterMemory: %1").arg(ClusterMemory));
    str += utils::Print(QString("\tClusterNumberCores: %1").arg(ClusterNumberCores));
    str += utils::Print(QString("\tClusterQueue: %1").arg(ClusterQueue));
    str += utils::Print(QString("\tClusterSubmitHost: %1").arg(ClusterSubmitHost));
    str += utils::Print(QString("\tClusterType: %1").arg(ClusterType));
    str += utils::Print(QString("\tClusterUser: %1").arg(ClusterUser));
    str += utils::Print(QString("\tCompleteFiles: %1").arg(CompleteFiles.join(",")));
    str += utils::Print(QString("\tCreateDate: %1").arg(CreateDate.toString()));
    str += utils::Print(QString("\tDataCopyMethod: %1").arg(DataCopyMethod));
    str += utils::Print(QString("\tDepDirectory: %1").arg(DependencyDirectory));
    str += utils::Print(QString("\tDepLevel: %1").arg(DependencyLevel));
    str += utils::Print(QString("\tDepLinkType: %1").arg(DependencyLinkType));
    str += utils::Print(QString("\tDescription: %1").arg(Description));
    str += utils::Print(QString("\tDirectory: %1").arg(Directory));
    str += utils::Print(QString("\tDirectoryStructure: %1").arg(DirectoryStructure));
    str += utils::Print(QString("\tGroup: %1").arg(Group));
    str += utils::Print(QString("\tGroupType: %1").arg(GroupType));
    str += utils::Print(QString("\tLevel: %1").arg(Level));
    str += utils::Print(QString("\tNotes: %1").arg(Notes));
    str += utils::Print(QString("\tNumConcurrentAnalyses: %1").arg(NumberConcurrentAnalyses));
    str += utils::Print(QString("\tParentPipelines: %1").arg(ParentPipelines.join(",")));
    str += utils::Print(QString("\tPipelineName: %1").arg(PipelineName));
    str += utils::Print(QString("\tPipelineRowID: %1").arg(objectID));
    str += utils::Print(QString("\tResultScript: %1").arg(ResultScript));
    str += utils::Print(QString("\tSubmitDelay: %1").arg(SubmitDelay));
    str += utils::Print(QString("\tTempDirectory: %1").arg(TempDirectory));
    str += utils::Print(QString("\tUseProfile: %1").arg(flags.UseProfile));
    str += utils::Print(QString("\tUseTempDirectory: %1").arg(flags.UseTempDirectory));
    str += utils::Print(QString("\tVersion: %1").arg(Version));
    str += utils::Print(QString("\tVirtualPath: %1").arg(VirtualPath()));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelPipeline::VirtualPath() {
    QString vPath = QString("pipelines/%1").arg(PipelineName);

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelPipeline::GetStagedFileList() {

    QList<QPair<QString,QString>> stagedList;
    QString virtualPath = VirtualPath();

    QString path;
    foreach (path, stagedFiles) {
        QPair<QString, QString> pair;
        pair.first = path;
        pair.second = virtualPath;
        stagedList.append(pair);
    }

    return stagedList;
}
