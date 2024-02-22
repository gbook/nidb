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
        objectID = q.value("AnalysisRowID").toLongLong();
        studyRowID = q.value("StudyRowID").toLongLong();
        pipelineRowID = q.value("PipelineRowID").toLongLong();

        pipelineName = q.value("PipelineName").toString();
        pipelineVersion = q.value("PipelineVersion").toInt();
        clusterStartDate = q.value("ClusterStartDate").toDateTime();
        clusterEndDate = q.value("ClusterEndDate").toDateTime();
        startDate = q.value("StartDate").toDateTime();
        endDate = q.value("EndDate").toDateTime();
        setupTime = q.value("SetupTime").toLongLong();
        runTime = q.value("RunTime").toLongLong();
        numSeries = q.value("NumSeries").toInt();
        successful = q.value("Successful").toBool();
        size = q.value("Size").toLongLong();
        hostname = q.value("Hostname").toString();
        status = q.value("Status").toString();
        lastMessage = q.value("StatusMessage").toString();
        //virtualPath = q.value("VirtualPath").toString();

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
        q.prepare("insert or ignore into Analysis (StudyRowID, PipelineRowID, PipelineVersion, ClusterStartDate, ClusterEndDate, StartDate, EndDate, SetupTime, RunTime, NumSeries, Status, Successful, Size, Hostname, StatusMessage, VirtualPath) values (:StudyRowID, :PipelineRowID, :PipelineVersion, :ClusterStartDate, :ClusterEndDate, :StartDate, :EndDate, :SetupTime, :RunTime, :NumSeries, :Status, :Successful, :Size, :Hostname, :StatusMessage, :VirtualPath)");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":PipelineRowID", pipelineRowID);
        q.bindValue(":PipelineVersion", pipelineVersion);
        q.bindValue(":ClusterStartDate", clusterStartDate);
        q.bindValue(":ClusterEndDate", clusterEndDate);
        q.bindValue(":StartDate", startDate);
        q.bindValue(":EndDate", endDate);
        q.bindValue(":SetupTime", setupTime);
        q.bindValue(":RunTime", runTime);
        q.bindValue(":NumSeries", numSeries);
        q.bindValue(":Status", status);
        q.bindValue(":Successful", successful);
        q.bindValue(":Size", size);
        q.bindValue(":Hostname", hostname);
        q.bindValue(":StatusMessage", lastMessage);
        q.bindValue(":VirtualPath", VirtualPath());

        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Analysis set StudyRowID = :StudyRowID, PipelineRowID = :PipelineRowID, PipelineVersion = :PipelineVersion, ClusterStartDate = :ClusterStartDate, ClusterEndDate = :ClusterEndDate, StartDate = :StartDate, EndDate = :EndDate, SetupTime = :SetupTime, RunTime = :RunTime, NumSeries = :NumSeries, Status = :Status, Successful = :Successful, Size = :Size, Hostname = :Hostname, StatusMessage = :StatusMessage, VirtualPath = :VirtualPath");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":PipelineRowID", pipelineRowID);
        q.bindValue(":PipelineVersion", pipelineVersion);
        q.bindValue(":ClusterStartDate", clusterStartDate);
        q.bindValue(":ClusterEndDate", clusterEndDate);
        q.bindValue(":StartDate", startDate);
        q.bindValue(":EndDate", endDate);
        q.bindValue(":SetupTime", setupTime);
        q.bindValue(":RunTime", runTime);
        q.bindValue(":NumSeries", numSeries);
        q.bindValue(":Status", status);
        q.bindValue(":Successful", successful);
        q.bindValue(":Size", size);
        q.bindValue(":Hostname", hostname);
        q.bindValue(":StatusMessage", lastMessage);
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

    json["ClusterEndDate"] = clusterEndDate.toString("yyyy-MM-dd HH:mm:ss");
    json["ClusterStartDate"] = clusterStartDate.toString("yyyy-MM-dd HH:mm:ss");
    json["EndDate"] = endDate.toString("yyyy-MM-dd HH:mm:ss");
    json["Hostname"] = hostname;
    json["LastMessage"] = lastMessage;
    json["NumberOfSeries"] = numSeries;
    json["PipelineName"] = pipelineName;
    json["PipelineVersion"] = pipelineVersion;
    json["RunTime"] = runTime;
    json["SetupTime"] = setupTime;
    json["Size"] = size;
    json["StartDate"] = startDate.toString("yyyy-MM-dd HH:mm:ss");
    json["Status"] = status;
    json["Successful"] = successful;
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
    utils::Print(QString("\t\t\t\tClusterEndDate: %1").arg(clusterEndDate.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tClusterStartDate: %1").arg(clusterStartDate.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tEndDate: %1").arg(endDate.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tHostname: %1").arg(hostname));
    utils::Print(QString("\t\t\t\tLastMessage: %1").arg(lastMessage));
    utils::Print(QString("\t\t\t\tNumSeries: %1").arg(numSeries));
    utils::Print(QString("\t\t\t\tPipelineName: %1").arg(pipelineName));
    utils::Print(QString("\t\t\t\tPipelineVersion: %1").arg(pipelineVersion));
    utils::Print(QString("\t\t\t\tRunTime: %1").arg(runTime));
    utils::Print(QString("\t\t\t\tSetupTime: %1").arg(setupTime));
    utils::Print(QString("\t\t\t\tSize: %1").arg(size));
    utils::Print(QString("\t\t\t\tStartDate: %1").arg(startDate.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tStatus: %1").arg(status));
    utils::Print(QString("\t\t\t\tStudyRowID: %1").arg(objectID));
    utils::Print(QString("\t\t\t\tSuccessful: %1").arg(successful));
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
    q.prepare("select SubjectRowID, StudyNumber, Sequence from Study where StudyRowID = :studyid");
    q.bindValue(":studyid", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        subjectRowID = q.value("SubjectRowID").toInt();
        if (studyDirFormat == "orig")
            studyDir = QString("%1").arg(q.value("StudyNumber").toInt());
        else
            studyDir = QString("%1").arg(q.value("Sequence").toInt());
    }

    /* get parent subject directory */
    q.prepare("select ID, Sequence from Subject where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        if (subjectDirFormat == "orig")
            subjectDir = utils::CleanString(q.value("ID").toString());
        else
            subjectDir = QString("%1").arg(q.value("Sequence").toInt());
    }

    vPath = QString("data/%1/%2/%3").arg(subjectDir).arg(studyDir).arg(pipelineName);

    return vPath;
}
