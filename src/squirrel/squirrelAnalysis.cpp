/* ------------------------------------------------------------------------------
  Squirrel squirrelAnalysis.cpp
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


#include "squirrelAnalysis.h"
#include "utils.h"

squirrelAnalysis::squirrelAnalysis()
{

}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelAnalysis::Get
 * @return true if successful
 *
 * This function will attempt to load the analysis data from
 * the database. The analysisRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelAnalysis::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Analysis a left join Pipeline b on a.PipelineRowID = b.PipelineRowID where a.AnalysisRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        q.first();

        /* get the data */
        AnalysisName = q.value("AnalysisName").toString();
        DateClusterEnd = q.value("ClusterEndDate").toDateTime();
        DateClusterStart = q.value("ClusterStartDate").toDateTime();
        DateEnd = q.value("EndDate").toDateTime();
        DateStart = q.value("StartDate").toDateTime();
        Hostname = q.value("Hostname").toString();
        LastMessage = q.value("StatusMessage").toString();
        PipelineName = q.value("PipelineName").toString();
        PipelineVersion = q.value("PipelineVersion").toInt();
        RunTime = q.value("RunTime").toLongLong();
        SeriesCount = q.value("NumSeries").toInt();
        SetupTime = q.value("SetupTime").toLongLong();
        Size = q.value("Size").toLongLong();
        Status = q.value("Status").toString();
        Successful = q.value("Successful").toBool();
        objectID = q.value("AnalysisRowID").toLongLong();
        pipelineRowID = q.value("PipelineRowID").toLongLong();
        studyRowID = q.value("StudyRowID").toLongLong();

        /* get any staged files */
        stagedFiles.clear();
        q.prepare("select * from StagedFiles where ObjectRowID = :id and ObjectType = :type");
        q.bindValue(":id", objectID);
        q.bindValue(":type", "analysis");
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            stagedFiles.append(q.value("StagedPath").toString());
        }

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
 * @brief squirrelAnalysis::Store
 * @return true if successful
 *
 * This function will attempt to load the experiment data from
 * the database. The experimentRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelAnalysis::Store() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Analysis (StudyRowID, PipelineRowID, PipelineVersion, AnalysisName, ClusterStartDate, ClusterEndDate, StartDate, EndDate, SetupTime, RunTime, NumSeries, Status, Successful, Size, Hostname, StatusMessage, VirtualPath) values (:StudyRowID, :PipelineRowID, :PipelineVersion, :AnalysisName, :ClusterStartDate, :ClusterEndDate, :StartDate, :EndDate, :SetupTime, :RunTime, :NumSeries, :Status, :Successful, :Size, :Hostname, :StatusMessage, :VirtualPath)");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":PipelineRowID", pipelineRowID);
        q.bindValue(":PipelineVersion", PipelineVersion);
        q.bindValue(":ClusterStartDate", DateClusterStart);
        q.bindValue(":ClusterEndDate", DateClusterEnd);
        q.bindValue(":StartDate", DateStart);
        q.bindValue(":EndDate", DateEnd);
        q.bindValue(":SetupTime", SetupTime);
        q.bindValue(":RunTime", RunTime);
        q.bindValue(":NumSeries", SeriesCount);
        q.bindValue(":Status", Status);
        q.bindValue(":Successful", Successful);
        q.bindValue(":Size", Size);
        q.bindValue(":AnalysisName", AnalysisName);
        q.bindValue(":Hostname", Hostname);
        q.bindValue(":StatusMessage", LastMessage);
        q.bindValue(":VirtualPath", VirtualPath());

        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Analysis set StudyRowID = :StudyRowID, PipelineRowID = :PipelineRowID, PipelineVersion = :PipelineVersion, AnalysisName = :AnalysisName, ClusterStartDate = :ClusterStartDate, ClusterEndDate = :ClusterEndDate, StartDate = :StartDate, EndDate = :EndDate, SetupTime = :SetupTime, RunTime = :RunTime, NumSeries = :NumSeries, Status = :Status, Successful = :Successful, Size = :Size, Hostname = :Hostname, StatusMessage = :StatusMessage, VirtualPath = :VirtualPath");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":PipelineRowID", pipelineRowID);
        q.bindValue(":PipelineVersion", PipelineVersion);
        q.bindValue(":ClusterStartDate", DateClusterStart);
        q.bindValue(":ClusterEndDate", DateClusterEnd);
        q.bindValue(":StartDate", DateStart);
        q.bindValue(":EndDate", DateEnd);
        q.bindValue(":SetupTime", SetupTime);
        q.bindValue(":RunTime", RunTime);
        q.bindValue(":NumSeries", SeriesCount);
        q.bindValue(":Status", Status);
        q.bindValue(":Successful", Successful);
        q.bindValue(":Size", Size);
        q.bindValue(":AnalysisName", AnalysisName);
        q.bindValue(":Hostname", Hostname);
        q.bindValue(":StatusMessage", LastMessage);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged files */
    if (objectID >= 0) {
        /* delete previously staged files from the database */
        q.prepare("delete from StagedFiles where ObjectRowID = :id and ObjectType = :type");
        q.bindValue(":id", objectID);
        q.bindValue(":type", "analysis");
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        QString path;
        foreach (path, stagedFiles) {
            q.prepare("insert into StagedFiles (ObjectRowID, ObjectType) values (:packageid, :id, :type)");
            q.bindValue(":id", objectID);
            q.bindValue(":type", "analysis");
            utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
    }
    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelAnalysis::ToJSON() {
    QJsonObject json;

    json["AnalysisName"] = AnalysisName;
    json["DateClusterEnd"] = DateClusterEnd.toString("yyyy-MM-dd HH:mm:ss");
    json["DateClusterStart"] = DateClusterStart.toString("yyyy-MM-dd HH:mm:ss");
    json["DateEnd"] = DateEnd.toString("yyyy-MM-dd HH:mm:ss");
    json["DateStart"] = DateStart.toString("yyyy-MM-dd HH:mm:ss");
    json["Hostname"] = Hostname;
    json["LastMessage"] = LastMessage;
    json["PipelineName"] = PipelineName;
    json["PipelineVersion"] = PipelineVersion;
    json["RunTime"] = RunTime;
    json["SeriesCount"] = SeriesCount;
    json["SetupTime"] = SetupTime;
    json["Size"] = Size;
    json["Status"] = Status;
    json["Successful"] = Successful;
    json["VirtualPath"] = VirtualPath();

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintAnalysis ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print the analysis details
 */
void squirrelAnalysis::PrintAnalysis() {

    utils::Print("\t\t\t\t----- ANALYSIS -----");
    utils::Print(QString("\t\t\t\tAnalysisName: %1").arg(AnalysisName));
    utils::Print(QString("\t\t\t\tDateClusterEnd: %1").arg(DateClusterEnd.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tDateClusterStart: %1").arg(DateClusterStart.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tDateEnd: %1").arg(DateEnd.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tDateStart: %1").arg(DateStart.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tHostname: %1").arg(Hostname));
    utils::Print(QString("\t\t\t\tLastMessage: %1").arg(LastMessage));
    utils::Print(QString("\t\t\t\tPipelineName: %1").arg(PipelineName));
    utils::Print(QString("\t\t\t\tPipelineVersion: %1").arg(PipelineVersion));
    utils::Print(QString("\t\t\t\tRunTime: %1").arg(RunTime));
    utils::Print(QString("\t\t\t\tSeriesCount: %1").arg(SeriesCount));
    utils::Print(QString("\t\t\t\tSetupTime: %1").arg(SetupTime));
    utils::Print(QString("\t\t\t\tSize: %1").arg(Size));
    utils::Print(QString("\t\t\t\tStatus: %1").arg(Status));
    utils::Print(QString("\t\t\t\tStudyRowID: %1").arg(objectID));
    utils::Print(QString("\t\t\t\tSuccessful: %1").arg(Successful));
    utils::Print(QString("\t\t\t\tVirtualPath: %1").arg(VirtualPath()));
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelAnalysis::VirtualPath() {

    QString vPath;
    QString subjectDir;
    QString studyDir;
    int subjectRowID = -1;

    /* get the parent study directory */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select SubjectRowID, StudyNumber, SequenceNumber from Study where StudyRowID = :studyid");
    q.bindValue(":studyid", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        subjectRowID = q.value("SubjectRowID").toInt();
        if (studyDirFormat == "orig")
            studyDir = QString("%1").arg(q.value("StudyNumber").toInt());
        else
            studyDir = QString("%1").arg(q.value("SequenceNumber").toInt());
    }

    /* get parent subject directory */
    q.prepare("select ID, SequenceNumber from Subject where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        if (subjectDirFormat == "orig")
            subjectDir = utils::CleanString(q.value("ID").toString());
        else
            subjectDir = QString("%1").arg(q.value("SequenceNumber").toInt());
    }

    vPath = QString("data/%1/%2/%3").arg(subjectDir).arg(studyDir).arg(PipelineName);

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelAnalysis::GetStagedFileList() {

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
