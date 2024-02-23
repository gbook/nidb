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
        PipelineName = q.value("PipelineName").toString();
        Description = q.value("Description").toString();
        CreateDate = q.value("Datetime").toDateTime();
        Level = q.value("Level").toInt();
        PrimaryScript = q.value("PrimaryScript").toString();
        SecondaryScript = q.value("SecondaryScript").toString();
        Version = q.value("Version").toInt();
        //parentPipelines = q.value("CompleteFiles").toString();
        CompleteFiles = q.value("CompleteFiles").toString().split(",");
        DataCopyMethod = q.value("DataCopyMethod").toString();
        DependencyDirectory = q.value("DependencyDirectory").toString();
        DependencyLevel = q.value("DependencyLevel").toString();
        DependencyLinkType = q.value("DependencyLinkType").toString();
        DirectoryStructure = q.value("DirStructure").toString();
        Directory = q.value("Directory").toString();
        Group = q.value("GroupName").toString();
        GroupType = q.value("GroupType").toString();
        Notes = q.value("Notes").toString();
        ResultScript = q.value("ResultScript").toString();
        TempDirectory = q.value("TempDir").toString();
        flags.UseProfile = q.value("FlagUseProfile").toBool();
        flags.UseTempDirectory = q.value("FlagUseTempDir").toBool();
        ClusterType = q.value("ClusterType").toString();
        ClusterUser = q.value("ClusterUser").toString();
        ClusterQueue = q.value("ClusterQueue").toString();
        ClusterSubmitHost = q.value("ClusterSubmitHost").toString();
        NumberConcurrentAnalyses = q.value("NumConcurrentAnalysis").toInt();
        MaxWallTime = q.value("MaxWallTime").toInt();
        SubmitDelay = q.value("SubmitDelay").toInt();

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
        q.bindValue(":TempDir", TempDirectory);
        q.bindValue(":FlagUseProfile", flags.UseProfile);
        q.bindValue(":FlagUseTempDir", flags.UseTempDirectory);
        q.bindValue(":ClusterType", ClusterType);
        q.bindValue(":ClusterUser", ClusterUser);
        q.bindValue(":ClusterQueue", ClusterQueue);
        q.bindValue(":ClusterSubmitHost", ClusterSubmitHost);
        q.bindValue(":NumConcurrentAnalysis", NumberConcurrentAnalyses);
        q.bindValue(":MaxWallTime", MaxWallTime);
        q.bindValue(":SubmitDelay", SubmitDelay);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Pipeline set PipelineName = :PipelineName, Description = :Description, Datetime = :Datetime, Level = :Level, PrimaryScript = :PrimaryScript, SecondaryScript = :SecondaryScript, Version = :Version, CompleteFiles = :CompleteFiles, DataCopyMethod = :DataCopyMethod, DependencyDirectory = :DependencyDirectory, DependencyLevel = :DependencyLevel, DependencyLinkType = :DependencyLinkType, DirStructure = :DirStructure, Directory = :Directory, GroupName = :GroupName, GroupType = :GroupType, Notes = :Notes, ResultScript = :ResultScript, TempDir = :TempDir, FlagUseProfile = :FlagUseProfile, FlagUseTempDir = :FlagUseTempDir, ClusterType = :ClusterType, ClusterUser = :ClusterUser, ClusterQueue = :ClusterQueue, ClusterSubmitHost = :ClusterSubmitHost, NumConcurrentAnalysis = :NumConcurrentAnalysis, MaxWallTime = :MaxWallTime, SubmitDelay = :SubmitDelay, VirtualPath = :VirtualPath where PipelineRowID = :id");
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
        q.bindValue(":TempDir", TempDirectory);
        q.bindValue(":FlagUseProfile", flags.UseProfile);
        q.bindValue(":FlagUseTempDir", flags.UseTempDirectory);
        q.bindValue(":ClusterType", ClusterType);
        q.bindValue(":ClusterUser", ClusterUser);
        q.bindValue(":ClusterQueue", ClusterQueue);
        q.bindValue(":ClusterSubmitHost", ClusterSubmitHost);
        q.bindValue(":NumConcurrentAnalysis", NumberConcurrentAnalyses);
        q.bindValue(":MaxWallTime", MaxWallTime);
        q.bindValue(":SubmitDelay", SubmitDelay);
        q.bindValue(":VirtualPath", VirtualPath());
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
    json["MaxWallTime"] = MaxWallTime;
    json["Notes"] = Notes;
    json["NumberConcurrentAnalyses"] = NumberConcurrentAnalyses;
    json["ParentPipelines"] = ParentPipelines.join(",");
    json["PipelineName"] = PipelineName;
    json["Version"] = Version;
    json["ResultScript"] = ResultScript;
    json["SubmitDelay"] = SubmitDelay;
    json["TempDirectory"] = TempDirectory;
    json["UseProfile"] = flags.UseProfile;
    json["UseTempDirectory"] = flags.UseTempDirectory;
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
void squirrelPipeline::PrintPipeline() {

    utils::Print("\t----- PIPELINE -----");

    utils::Print(QString("\tClusterQueue: %1").arg(ClusterQueue));
    utils::Print(QString("\tClusterSubmitHost: %1").arg(ClusterSubmitHost));
    utils::Print(QString("\tClusterType: %1").arg(ClusterType));
    utils::Print(QString("\tClusterUser: %1").arg(ClusterUser));
    utils::Print(QString("\tCompleteFiles: %1").arg(CompleteFiles.join(",")));
    utils::Print(QString("\tCreateDate: %1").arg(CreateDate.toString()));
    utils::Print(QString("\tDataCopyMethod: %1").arg(DataCopyMethod));
    utils::Print(QString("\tDepDirectory: %1").arg(DependencyDirectory));
    utils::Print(QString("\tDepLevel: %1").arg(DependencyLevel));
    utils::Print(QString("\tDepLinkType: %1").arg(DependencyLinkType));
    utils::Print(QString("\tDescription: %1").arg(Description));
    utils::Print(QString("\tDirStructure: %1").arg(DirectoryStructure));
    utils::Print(QString("\tDirectory: %1").arg(Directory));
    utils::Print(QString("\tGroup: %1").arg(Group));
    utils::Print(QString("\tGroupType: %1").arg(GroupType));
    utils::Print(QString("\tLevel: %1").arg(Level));
    utils::Print(QString("\tMaxWallTime: %1").arg(MaxWallTime));
    utils::Print(QString("\tNotes: %1").arg(Notes));
    utils::Print(QString("\tNumConcurrentAnalyses: %1").arg(NumberConcurrentAnalyses));
    utils::Print(QString("\tParentPipelines: %1").arg(ParentPipelines.join(",")));
    utils::Print(QString("\tPipelineName: %1").arg(PipelineName));
    utils::Print(QString("\tPipelineRowID: %1").arg(objectID));
    utils::Print(QString("\tResultScript: %1").arg(ResultScript));
    utils::Print(QString("\tSubmitDelay: %1").arg(SubmitDelay));
    utils::Print(QString("\tTempDir: %1").arg(TempDirectory));
    utils::Print(QString("\tUseProfile: %1").arg(flags.UseProfile));
    utils::Print(QString("\tUseTempDir: %1").arg(flags.UseTempDirectory));
    utils::Print(QString("\tVersion: %1").arg(Version));
    utils::Print(QString("\tVirtualPath: %1").arg(VirtualPath()));

}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelPipeline::VirtualPath() {
    QString vPath = QString("pipeline/%1").arg(PipelineName);

    return vPath;
}
