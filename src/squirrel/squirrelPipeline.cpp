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
        ClusterEngine = q.value("ClusterEngine").toString();
        ClusterMaxWallTime = q.value("ClusterMaxWallTime").toInt();
        ClusterMemory = q.value("ClusterMemory").toInt();
        ClusterNumberConcurrentAnalyses = q.value("ClusterNumberConcurrentAnalyses").toInt();
        ClusterNumberCores = q.value("ClusterNumberCores").toInt();
        ClusterQueue = q.value("ClusterQueue").toString();
        ClusterSubmitDelay = q.value("ClusterSubmitDelay").toInt();
        ClusterSubmitHost = q.value("ClusterSubmitHost").toString();
        ClusterUser = q.value("ClusterUser").toString();
        PipelineAnalysisLevel = q.value("PipelineAnalysisLevel").toInt();
        PipelineCompleteFiles = q.value("PipelineCompleteFiles").toString().split(",");
        PipelineCreateDate = q.value("PipelineCreateDate").toDateTime();
        PipelineDescription = q.value("PipelineDescription").toString();
        PipelineDirectory = q.value("PipelineDirectory").toString();
        PipelineDirectoryStructure = q.value("PipelineDirectoryStructure").toString();
        PipelineName = q.value("PipelineName").toString();
        PipelineNotes = q.value("PipelineNotes").toString();
        PipelinePrimaryScript = q.value("PipelinePrimaryScript").toString();
        PipelineResultScript = q.value("PipelineResultScript").toString();
        PipelineSecondaryScript = q.value("PipelineSecondaryScript").toString();
        PipelineVersion = q.value("PipelineVersion").toInt();
        SearchDependencyLevel = q.value("SearchDependencyLevel").toString();
        SearchDependencyLinkType = q.value("SearchDependencyLinkType").toString();
        SearchGroup = q.value("SearchGroup").toString();
        SearchGroupType = q.value("SearchGroupType").toString();
        SearchParentPipelines = q.value("SearchParentPipelines").toString().split(",");
        SetupDataCopyMethod = q.value("SetupDataCopyMethod").toString();
        SetupDependencyDirectory = q.value("SetupDependencyDirectory").toString();
        SetupTempDirectory = q.value("SetupTempDirectory").toString();
        flags.SetupUseProfile = q.value("FlagSetupUseProfile").toBool();
        flags.SetupUseTempDirectory = q.value("FlagSetupUseTempDirectory").toBool();

        /* get the data steps */
        dataSteps.clear();
        QSqlQuery qd(QSqlDatabase::database(databaseUUID));
        qd.prepare("select * from PipelineDataStep where PipelineRowID = :id order by StepNumber asc");
        qd.bindValue(":id", objectID);
        utils::SQLQuery(qd, __FUNCTION__, __FILE__, __LINE__);
        while (qd.next()) {
            dataStep ds;
            ds.ExportBehavioralDirectoryFormat = qd.value("ExportBehavioralDirectoryFormat").toString();
            ds.ExportBehavioralDirectoryName = qd.value("ExportBehavioralDirectoryName").toString();
            ds.ExportDataFormat = qd.value("ExportDataFormat").toString();
            ds.ExportSubDirectoryName = qd.value("ExportSubDirectoryName").toString();
            ds.SearchAssociationType = qd.value("SearchAssociationType").toString();
            ds.SearchDataLevel = qd.value("SearchDataLevel").toString();
            ds.SearchImageType = qd.value("SearchImageType").toString();
            ds.SearchModality = qd.value("SearchModality").toString();
            ds.SearchNumberBOLDreps = qd.value("SearchNumberBOLDreps").toString();
            ds.SearchProtocol = qd.value("SearchProtocol").toString();
            ds.SearchSeriesCriteria = qd.value("SearchSeriesCriteria").toString();
            ds.StepNumber = qd.value("StepNumber").toInt();
            ds.flags.IsEnabled = qd.value("FlagIsEnabled").toBool();
            ds.flags.IsOptional = qd.value("FlagIsOptional").toBool();
            ds.flags.ExportGzip = qd.value("FlagExportGzip").toBool();
            ds.flags.ExportPreserveSeriesNumber = qd.value("FlagExportPreserveSeriesNumber").toBool();
            ds.flags.IsPrimaryProtocol = qd.value("FlagIsPrimaryProtocol").toBool();
            ds.flags.ExportWritePhaseDirectory = qd.value("FlagExportWritePhaseDirectory").toBool();
            ds.flags.ExportWriteSeriesDirectory = qd.value("FlagExportWriteSeriesDirectory").toBool();
            dataSteps.append(ds);
        }

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
    bool isNewObject = (objectID < 0);

    //utils::Print(QString("squirrelPipeline has been asked to Store(%1, %2). Current objectID [%3]").arg(PipelineName).arg(Version).arg(objectID));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Pipeline ("
                  "ClusterEngine, "
                  "ClusterMaxWallTime, "
                  "ClusterMemory, "
                  "ClusterNumberConcurrentAnalyses, "
                  "ClusterNumberCores, "
                  "ClusterQueue, "
                  "ClusterSubmitDelay, "
                  "ClusterSubmitHost, "
                  "ClusterUser, "
                  "FlagSetupUseProfile, "
                  "FlagSetupUseTempDirectory, "
                  "PipelineAnalysisLevel, "
                  "PipelineCompleteFiles, "
                  "PipelineCreateDate, "
                  "PipelineDescription, "
                  "PipelineDirectory, "
                  "PipelineDirectoryStructure, "
                  "PipelineName, "
                  "PipelineNotes, "
                  "PipelinePrimaryScript, "
                  "PipelineResultScript, "
                  "PipelineSecondaryScript, "
                  "PipelineVersion, "
                  "SearchDependencyLevel, "
                  "SearchDependencyLinkType, "
                  "SearchGroup, "
                  "SearchGroupType, "
                  "SearchParentPipelines, "
                  "SetupDataCopyMethod, "
                  "SetupDependencyDirectory, "
                  "SetupTempDirectory, "
                  "VirtualPath"
            ") values ("
                  ":ClusterEngine, "
                  ":ClusterMaxWallTime, "
                  ":ClusterMemory, "
                  ":ClusterNumberConcurrentAnalyses, "
                  ":ClusterNumberCores, "
                  ":ClusterQueue, "
                  ":ClusterSubmitDelay, "
                  ":ClusterSubmitHost, "
                  ":ClusterUser, "
                  ":FlagSetupUseProfile, "
                  ":FlagSetupUseTempDirectory, "
                  ":PipelineAnalysisLevel, "
                  ":PipelineCompleteFiles, "
                  ":PipelineCreateDate, "
                  ":PipelineDescription, "
                  ":PipelineDirectory, "
                  ":PipelineDirectoryStructure, "
                  ":PipelineName, "
                  ":PipelineNotes, "
                  ":PipelinePrimaryScript, "
                  ":PipelineResultScript, "
                  ":PipelineSecondaryScript, "
                  ":PipelineVersion, "
                  ":SearchDependencyLevel, "
                  ":SearchDependencyLinkType, "
                  ":SearchGroup, "
                  ":SearchGroupType, "
                  ":SearchParentPipelines, "
                  ":SetupDataCopyMethod, "
                  ":SetupDependencyDirectory, "
                  ":SetupTempDirectory, "
                  ":VirtualPath"
                  ")");
        q.bindValue(":ClusterEngine", ClusterEngine);
        q.bindValue(":ClusterMaxWallTime", ClusterMaxWallTime);
        q.bindValue(":ClusterMemory", ClusterMemory);
        q.bindValue(":ClusterNumberConcurrentAnalyses", ClusterNumberConcurrentAnalyses);
        q.bindValue(":ClusterNumberCores", ClusterNumberCores);
        q.bindValue(":ClusterQueue", ClusterQueue);
        q.bindValue(":ClusterSubmitDelay", ClusterSubmitDelay);
        q.bindValue(":ClusterSubmitHost", ClusterSubmitHost);
        q.bindValue(":ClusterUser", ClusterUser);
        q.bindValue(":FlagSetupUseProfile", flags.SetupUseProfile);
        q.bindValue(":FlagSetupUseTempDirectory", flags.SetupUseTempDirectory);
        q.bindValue(":PipelineAnalysisLevel", PipelineAnalysisLevel);
        q.bindValue(":PipelineCompleteFiles", PipelineCompleteFiles.join(","));
        q.bindValue(":PipelineCreateDate", PipelineCreateDate);
        q.bindValue(":PipelineDescription", PipelineDescription);
        q.bindValue(":PipelineDirectory", PipelineDirectory);
        q.bindValue(":PipelineDirectoryStructure", PipelineDirectoryStructure);
        q.bindValue(":PipelineName", PipelineName);
        q.bindValue(":PipelineNotes", PipelineNotes);
        q.bindValue(":PipelinePrimaryScript", PipelinePrimaryScript);
        q.bindValue(":PipelineResultScript", PipelineResultScript);
        q.bindValue(":PipelineSecondaryScript", PipelineSecondaryScript);
        q.bindValue(":PipelineVersion", PipelineVersion);
        q.bindValue(":SearchDependencyLevel", SearchDependencyLevel);
        q.bindValue(":SearchDependencyLinkType", SearchDependencyLinkType);
        q.bindValue(":SearchGroup", SearchGroup);
        q.bindValue(":SearchGroupType", SearchGroupType);
        q.bindValue(":SearchParentPipelines", SearchParentPipelines.join(","));
        q.bindValue(":SetupDataCopyMethod", SetupDataCopyMethod);
        q.bindValue(":SetupDependencyDirectory", SetupDependencyDirectory);
        q.bindValue(":SetupTempDirectory", SetupTempDirectory);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toLongLong();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Pipeline set "
                  "ClusterEngine = :ClusterEngine, "
                  "ClusterMaxWallTime = :ClusterMaxWallTime, "
                  "ClusterMemory = :ClusterMemory, "
                  "ClusterNumberConcurrentAnalyses = :ClusterNumberConcurrentAnalyses, "
                  "ClusterNumberCores = :ClusterNumberCores, "
                  "ClusterQueue = :ClusterQueue, "
                  "ClusterSubmitDelay = :ClusterSubmitDelay, "
                  "ClusterSubmitHost = :ClusterSubmitHost, "
                  "ClusterUser = :ClusterUser, "
                  "FlagSetupUseProfile = :FlagSetupUseProfile, "
                  "FlagSetupUseTempDirectory = :FlagSetupUseTempDirectory, "
                  "PipelineAnalysisLevel = :PipelineAnalysisLevel, "
                  "PipelineCompleteFiles = :PipelineCompleteFiles, "
                  "PipelineCreateDate = :PipelineCreateDate, "
                  "PipelineDescription = :PipelineDescription, "
                  "PipelineDirectory = :PipelineDirectory, "
                  "PipelineDirectoryStructure = :PipelineDirectoryStructure, "
                  "PipelineName = :PipelineName, "
                  "PipelineNotes = :PipelineNotes, "
                  "PipelinePrimaryScript = :PipelinePrimaryScript, "
                  "PipelineResultScript = :PipelineResultScript, "
                  "PipelineSecondaryScript = :PipelineSecondaryScript, "
                  "PipelineVersion = :PipelineVersion, "
                  "SearchDependencyLevel = :SearchDependencyLevel, "
                  "SearchDependencyLinkType = :SearchDependencyLinkType, "
                  "SearchGroup = :SearchGroup, "
                  "SearchGroupType = :SearchGroupType, "
                  "SearchParentPipelines = :SearchParentPipelines, "
                  "SetupDataCopyMethod = :SetupDataCopyMethod, "
                  "SetupDependencyDirectory = :SetupDependencyDirectory, "
                  "SetupTempDirectory = :SetupTempDirectory, "
                  "VirtualPath = :VirtualPath "
                  "where PipelineRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":ClusterEngine", ClusterEngine);
        q.bindValue(":ClusterMaxWallTime", ClusterMaxWallTime);
        q.bindValue(":ClusterMemory", ClusterMemory);
        q.bindValue(":ClusterNumberConcurrentAnalyses", ClusterNumberConcurrentAnalyses);
        q.bindValue(":ClusterNumberCores", ClusterNumberCores);
        q.bindValue(":ClusterQueue", ClusterQueue);
        q.bindValue(":ClusterSubmitDelay", ClusterSubmitDelay);
        q.bindValue(":ClusterSubmitHost", ClusterSubmitHost);
        q.bindValue(":ClusterUser", ClusterUser);
        q.bindValue(":FlagSetupUseProfile", flags.SetupUseProfile);
        q.bindValue(":FlagSetupUseTempDirectory", flags.SetupUseTempDirectory);
        q.bindValue(":PipelineAnalysisLevel", PipelineAnalysisLevel);
        q.bindValue(":PipelineCompleteFiles", PipelineCompleteFiles.join(","));
        q.bindValue(":PipelineCreateDate", PipelineCreateDate);
        q.bindValue(":PipelineDescription", PipelineDescription);
        q.bindValue(":PipelineDirectory", PipelineDirectory);
        q.bindValue(":PipelineDirectoryStructure", PipelineDirectoryStructure);
        q.bindValue(":PipelineName", PipelineName);
        q.bindValue(":PipelineNotes", PipelineNotes);
        q.bindValue(":PipelinePrimaryScript", PipelinePrimaryScript);
        q.bindValue(":PipelineResultScript", PipelineResultScript);
        q.bindValue(":PipelineSecondaryScript", PipelineSecondaryScript);
        q.bindValue(":PipelineVersion", PipelineVersion);
        q.bindValue(":SearchDependencyLevel", SearchDependencyLevel);
        q.bindValue(":SearchDependencyLinkType", SearchDependencyLinkType);
        q.bindValue(":SearchGroup", SearchGroup);
        q.bindValue(":SearchGroupType", SearchGroupType);
        q.bindValue(":SearchParentPipelines", SearchParentPipelines.join(","));
        q.bindValue(":SetupDataCopyMethod", SetupDataCopyMethod);
        q.bindValue(":SetupDependencyDirectory", SetupDependencyDirectory);
        q.bindValue(":SetupTempDirectory", SetupTempDirectory);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store the data steps, replacing any previously stored */
    QSqlQuery qd(QSqlDatabase::database(databaseUUID));
    qd.prepare("delete from PipelineDataStep where PipelineRowID = :id");
    qd.bindValue(":id", objectID);
    utils::SQLQuery(qd, __FUNCTION__, __FILE__, __LINE__);
    foreach (dataStep ds, dataSteps) {
        qd.prepare("insert into PipelineDataStep (PipelineRowID, ExportBehavioralDirectoryFormat, ExportBehavioralDirectoryName, ExportDataFormat, ExportSubDirectoryName, SearchAssociationType, SearchDataLevel, SearchImageType, SearchModality, SearchNumberBOLDreps, SearchProtocol, SearchSeriesCriteria, StepNumber, FlagIsEnabled, FlagIsOptional, FlagExportGzip, FlagExportPreserveSeriesNumber, FlagIsPrimaryProtocol, FlagExportWritePhaseDirectory, FlagExportWriteSeriesDirectory) values (:PipelineRowID, :ExportBehavioralDirectoryFormat, :ExportBehavioralDirectoryName, :ExportDataFormat, :ExportSubDirectoryName, :SearchAssociationType, :SearchDataLevel, :SearchImageType, :SearchModality, :SearchNumberBOLDreps, :SearchProtocol, :SearchSeriesCriteria, :StepNumber, :FlagIsEnabled, :FlagIsOptional, :FlagExportGzip, :FlagExportPreserveSeriesNumber, :FlagIsPrimaryProtocol, :FlagExportWritePhaseDirectory, :FlagExportWriteSeriesDirectory)");
        qd.bindValue(":PipelineRowID", objectID);
        qd.bindValue(":ExportBehavioralDirectoryFormat", ds.ExportBehavioralDirectoryFormat);
        qd.bindValue(":ExportBehavioralDirectoryName", ds.ExportBehavioralDirectoryName);
        qd.bindValue(":ExportDataFormat", ds.ExportDataFormat);
        qd.bindValue(":ExportSubDirectoryName", ds.ExportSubDirectoryName);
        qd.bindValue(":SearchAssociationType", ds.SearchAssociationType);
        qd.bindValue(":SearchDataLevel", ds.SearchDataLevel);
        qd.bindValue(":SearchImageType", ds.SearchImageType);
        qd.bindValue(":SearchModality", ds.SearchModality);
        qd.bindValue(":SearchNumberBOLDreps", ds.SearchNumberBOLDreps);
        qd.bindValue(":SearchProtocol", ds.SearchProtocol);
        qd.bindValue(":SearchSeriesCriteria", ds.SearchSeriesCriteria);
        qd.bindValue(":StepNumber", ds.StepNumber);
        qd.bindValue(":FlagIsEnabled", ds.flags.IsEnabled);
        qd.bindValue(":FlagIsOptional", ds.flags.IsOptional);
        qd.bindValue(":FlagExportGzip", ds.flags.ExportGzip);
        qd.bindValue(":FlagExportPreserveSeriesNumber", ds.flags.ExportPreserveSeriesNumber);
        qd.bindValue(":FlagIsPrimaryProtocol", ds.flags.IsPrimaryProtocol);
        qd.bindValue(":FlagExportWritePhaseDirectory", ds.flags.ExportWritePhaseDirectory);
        qd.bindValue(":FlagExportWriteSeriesDirectory", ds.flags.ExportWriteSeriesDirectory);
        utils::SQLQuery(qd, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    if (!isNewObject || !stagedFiles.isEmpty())
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

    json["ClusterEngine"] = ClusterEngine;
    json["ClusterMaxWallTime"] = ClusterMaxWallTime;
    json["ClusterMemory"] = ClusterMemory;
    json["ClusterNumberConcurrentAnalyses"] = ClusterNumberConcurrentAnalyses;
    json["ClusterNumberCores"] = ClusterNumberCores;
    json["ClusterQueue"] = ClusterQueue;
    json["ClusterSubmitDelay"] = ClusterSubmitDelay;
    json["ClusterSubmitHost"] = ClusterSubmitHost;
    json["ClusterUser"] = ClusterUser;
    json["FlagSetupUseProfile"] = flags.SetupUseProfile;
    json["FlagSetupUseTempDirectory"] = flags.SetupUseTempDirectory;
    json["PipelineAnalysisLevel"] = PipelineAnalysisLevel;
    json["PipelineCompleteFiles"] = QJsonArray::fromStringList(PipelineCompleteFiles);
    json["PipelineCreateDate"] = PipelineCreateDate.toString("yyyy-MM-dd HH:mm:ss");
    json["PipelineDescription"] = PipelineDescription;
    json["PipelineDirectory"] = PipelineDirectory;
    json["PipelineDirectoryStructure"] = PipelineDirectoryStructure;
    json["PipelineName"] = PipelineName;
    json["PipelineNotes"] = PipelineNotes;
    json["PipelineResultScript"] = PipelineResultScript;
    json["PipelineVersion"] = PipelineVersion;
    json["SearchDependencyLevel"] = SearchDependencyLevel;
    json["SearchDependencyLinkType"] = SearchDependencyLinkType;
    json["SearchGroup"] = SearchGroup;
    json["SearchGroupType"] = SearchGroupType;
    json["SearchParentPipelines"] = SearchParentPipelines.join(",");
    json["SetupDataCopyMethod"] = SetupDataCopyMethod;
    json["SetupDependencyDirectory"] = SetupDependencyDirectory;
    json["SetupTempDirectory"] = SetupTempDirectory;
    json["VirtualPath"] = VirtualPath();

    /* add the dataSteps */
    QJsonArray JSONdataSteps;
    for (int i=0; i < dataSteps.size(); i++) {
        QJsonObject dataStep;
        //dataStep["NumberImagesCriteria"] = dataSteps[i].SearchNumberImagesCriteria;
        dataStep["ExportBehavioralDirectoryFormat"] = dataSteps[i].ExportBehavioralDirectoryFormat;
        dataStep["ExportBehavioralDirectoryName"] = dataSteps[i].ExportBehavioralDirectoryName;
        dataStep["ExportDataFormat"] = dataSteps[i].ExportDataFormat;
        dataStep["ExportSubDirectoryName"] = dataSteps[i].ExportSubDirectoryName;
        dataStep["FlagExportGzip"] = dataSteps[i].flags.ExportGzip;
        dataStep["FlagExportPreserveSeriesNumber"] = dataSteps[i].flags.ExportPreserveSeriesNumber;
        dataStep["FlagExportWritePhaseDirectory"] = dataSteps[i].flags.ExportWritePhaseDirectory;
        dataStep["FlagExportWriteSeriesDirectory"] = dataSteps[i].flags.ExportWriteSeriesDirectory;
        dataStep["FlagIsEnabled"] = dataSteps[i].flags.IsEnabled;
        dataStep["FlagIsOptional"] = dataSteps[i].flags.IsOptional;
        dataStep["FlagIsPrimaryProtocol"] = dataSteps[i].flags.IsPrimaryProtocol;
        dataStep["SearchAssociationType"] = dataSteps[i].SearchAssociationType;
        dataStep["SearchDataLevel"] = dataSteps[i].SearchDataLevel;
        dataStep["SearchImageType"] = dataSteps[i].SearchImageType;
        dataStep["SearchModality"] = dataSteps[i].SearchModality;
        dataStep["SearchNumberBOLDreps"] = dataSteps[i].SearchNumberBOLDreps;
        dataStep["SearchProtocol"] = dataSteps[i].SearchProtocol;
        dataStep["SearchSeriesCriteria"] = dataSteps[i].SearchSeriesCriteria;
        dataStep["StepNumber"] = dataSteps[i].StepNumber;

        JSONdataSteps.append(dataStep);
    }
    json["DataStepCount"] = JSONdataSteps.size();
    json["data-steps"] = JSONdataSteps;

    /* write all pipeline info to path */
    QString m;
    QString pipelinepath = QString("%1/pipelines/%2").arg(path).arg(PipelineName);
    if (utils::MakePath(pipelinepath, m)) {
        /* write the scripts */
        if (!utils::WriteTextFile(QString(pipelinepath + "/primaryScript.sh"), PipelinePrimaryScript))
            utils::Print("Error writing primary script [" + pipelinepath + "/primaryScript.sh]");

        if (!utils::WriteTextFile(QString(pipelinepath + "/secondaryScript.sh"), PipelineSecondaryScript))
            utils::Print("Error writing secondary script [" + pipelinepath + "/secondaryScript.sh]");

    }
    else {
        utils::Print("Error creating path [" + pipelinepath + "] because of [" + m + "]");
    }

    /* return JSON object */
    return json;
}


/* ------------------------------------------------------------ */
/* ----- GetData ---------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the object data as a hash of 'Object.Variable' keys, for printing
 * @param d the dataset type (ID, basic, or full)
 * @return hash of the requested variables
 */
QHash<QString, QString> squirrelPipeline::GetData(DatasetType d) {
    QHash<QString, QString> data;

    data["Pipeline.PipelineName"] = PipelineName;
    if (d == DatasetBasic || d == DatasetFull) {
        data["Pipeline.PipelineVersion"] = QString("%1").arg(PipelineVersion);
        data["Pipeline.PipelineAnalysisLevel"] = QString("%1").arg(PipelineAnalysisLevel);
        data["Pipeline.PipelineCreateDate"] = PipelineCreateDate.toString("yyyy-MM-dd HH:mm:ss");
        data["Pipeline.PipelineDescription"] = PipelineDescription;
        data["Pipeline.DataStepCount"] = QString("%1").arg(dataSteps.size());
    }
    if (d == DatasetFull) {
        data["Pipeline.ClusterEngine"] = ClusterEngine;
        data["Pipeline.ClusterMaxWallTime"] = QString("%1").arg(ClusterMaxWallTime);
        data["Pipeline.ClusterMemory"] = QString("%1").arg(ClusterMemory);
        data["Pipeline.ClusterNumberConcurrentAnalyses"] = QString("%1").arg(ClusterNumberConcurrentAnalyses);
        data["Pipeline.ClusterNumberCores"] = QString("%1").arg(ClusterNumberCores);
        data["Pipeline.ClusterQueue"] = ClusterQueue;
        data["Pipeline.ClusterSubmitDelay"] = QString("%1").arg(ClusterSubmitDelay);
        data["Pipeline.ClusterSubmitHost"] = ClusterSubmitHost;
        data["Pipeline.ClusterUser"] = ClusterUser;
        data["Pipeline.FlagSetupUseProfile"] = flags.SetupUseProfile ? "true" : "false";
        data["Pipeline.FlagSetupUseTempDirectory"] = flags.SetupUseTempDirectory ? "true" : "false";
        data["Pipeline.PipelineCompleteFiles"] = PipelineCompleteFiles.join(",");
        data["Pipeline.PipelineDirectory"] = PipelineDirectory;
        data["Pipeline.PipelineDirectoryStructure"] = PipelineDirectoryStructure;
        data["Pipeline.PipelineNotes"] = PipelineNotes;
        data["Pipeline.PipelineResultScript"] = PipelineResultScript;
        data["Pipeline.SearchDependencyLevel"] = SearchDependencyLevel;
        data["Pipeline.SearchDependencyLinkType"] = SearchDependencyLinkType;
        data["Pipeline.SearchGroup"] = SearchGroup;
        data["Pipeline.SearchGroupType"] = SearchGroupType;
        data["Pipeline.SearchParentPipelines"] = SearchParentPipelines.join(",");
        data["Pipeline.SetupDataCopyMethod"] = SetupDataCopyMethod;
        data["Pipeline.SetupDependencyDirectory"] = SetupDependencyDirectory;
        data["Pipeline.SetupTempDirectory"] = SetupTempDirectory;
        data["Pipeline.VirtualPath"] = VirtualPath();
    }

    return data;
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

    str += utils::Print(QString("\tClusterEngine: %1").arg(ClusterEngine));
    str += utils::Print(QString("\tClusterMaxWallTime: %1").arg(ClusterMaxWallTime));
    str += utils::Print(QString("\tClusterMemory: %1").arg(ClusterMemory));
    str += utils::Print(QString("\tClusterNumberConcurrentAnalyses: %1").arg(ClusterNumberConcurrentAnalyses));
    str += utils::Print(QString("\tClusterNumberCores: %1").arg(ClusterNumberCores));
    str += utils::Print(QString("\tClusterQueue: %1").arg(ClusterQueue));
    str += utils::Print(QString("\tClusterSubmitDelay: %1").arg(ClusterSubmitDelay));
    str += utils::Print(QString("\tClusterSubmitHost: %1").arg(ClusterSubmitHost));
    str += utils::Print(QString("\tClusterUser: %1").arg(ClusterUser));
    str += utils::Print(QString("\tFlagSetupUseProfile: %1").arg(flags.SetupUseProfile));
    str += utils::Print(QString("\tFlagSetupUseTempDirectory: %1").arg(flags.SetupUseTempDirectory));
    str += utils::Print(QString("\tPipelineAnalysisLevel: %1").arg(PipelineAnalysisLevel));
    str += utils::Print(QString("\tPipelineCompleteFiles: %1").arg(PipelineCompleteFiles.join(",")));
    str += utils::Print(QString("\tPipelineCreateDate: %1").arg(PipelineCreateDate.toString()));
    str += utils::Print(QString("\tPipelineDescription: %1").arg(PipelineDescription));
    str += utils::Print(QString("\tPipelineDirectory: %1").arg(PipelineDirectory));
    str += utils::Print(QString("\tPipelineDirectoryStructure: %1").arg(PipelineDirectoryStructure));
    str += utils::Print(QString("\tPipelineName: %1").arg(PipelineName));
    str += utils::Print(QString("\tPipelineNotes: %1").arg(PipelineNotes));
    str += utils::Print(QString("\tPipelineResultScript: %1").arg(PipelineResultScript));
    str += utils::Print(QString("\tPipelineRowID: %1").arg(objectID));
    str += utils::Print(QString("\tSearchDependencyLevel: %1").arg(SearchDependencyLevel));
    str += utils::Print(QString("\tSearchDependencyLinkType: %1").arg(SearchDependencyLinkType));
    str += utils::Print(QString("\tSearchGroup: %1").arg(SearchGroup));
    str += utils::Print(QString("\tSearchGroupType: %1").arg(SearchGroupType));
    str += utils::Print(QString("\tSearchParentPipelines: %1").arg(SearchParentPipelines.join(",")));
    str += utils::Print(QString("\tSetupDataCopyMethod: %1").arg(SetupDataCopyMethod));
    str += utils::Print(QString("\tSetupDependencyDirectory: %1").arg(SetupDependencyDirectory));
    str += utils::Print(QString("\tSetupTempDirectory: %1").arg(SetupTempDirectory));
    str += utils::Print(QString("\tVersion: %1").arg(PipelineVersion));
    str += utils::Print(QString("\tVirtualPath: %1").arg(VirtualPath()));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Return the pipeline's virtual path within the squirrel package
 * @return virtual path string (e.g. "pipelines/PipelineName")
 */
QString squirrelPipeline::VirtualPath() {
    QString vPath = QString("pipelines/%1").arg(PipelineName);

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Return all staged files as physical path / virtual path pairs
 * @return list of pairs where first is the physical disk path and second is the virtual path in the package
 */
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
