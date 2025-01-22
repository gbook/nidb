/* ------------------------------------------------------------------------------
  Squirrel squirrelGroupAnalysis.cpp
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

#include "squirrelGroupAnalysis.h"
#include "utils.h"

squirrelGroupAnalysis::squirrelGroupAnalysis(QString dbID)
{
    databaseUUID = dbID;
}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelGroupAnalysis::Get
 * @return true if successful
 *
 * This function will attempt to load the groupanalysis data from
 * the database. The groupanalysisRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelGroupAnalysis::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from GroupAnalysis where GroupAnalysisRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("GroupAnalysisRowID").toLongLong();
        GroupAnalysisName = q.value("GroupAnalysisName").toString();
        FileCount = q.value("FileCount").toLongLong();
        Size = q.value("Size").toLongLong();
        Description = q.value("Description").toString();
        Notes = q.value("Notes").toString();
        DateTime = q.value("Datetime").toDateTime();
        virtualPath = q.value("VirtualPath").toString();

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(databaseUUID, objectID, "groupanalysis");

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
 * @brief squirrelGroupAnalysis::Store
 * @return true if successful
 *
 * This function will attempt to load the groupanalysis data from
 * the database. The groupanalysisRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelGroupAnalysis::Store() {

    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into GroupAnalysis (GroupAnalysisName, Description, Datetime, FileCount, Size, VirtualPath) values (:GroupAnalysisName, :Description, :Datetime, :FileCount, :Size, :VirtualPath)");
        q.bindValue(":GroupAnalysisName", GroupAnalysisName);
        q.bindValue(":Description", Description);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":Size", Size);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update GroupAnalysis set GroupAnalysisName = :GroupAnalysisName, Description = :Description, Datetime = :Datetime, FileCount = :FileCount, Size = :Size, VirtualPath = :Virtualpath where GroupAnalysisRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":GroupAnalysisName", GroupAnalysisName);
        q.bindValue(":Description", Description);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":Size", Size);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(databaseUUID, objectID, "groupanalysis", stagedFiles);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelGroupAnalysis::ToJSON() {
    QJsonObject json;

    json["GroupAnalysisName"] = GroupAnalysisName;
    json["Datetime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["Description"] = Description;
    json["Notes"] = Notes;
    json["FileCount"] = FileCount;
    json["Size"] = Size;
    json["VirtualPath"] = virtualPath;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintGroupAnalysis ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelGroupAnalysis::PrintGroupAnalysis
 */
QString squirrelGroupAnalysis::PrintGroupAnalysis() {
    QString str;

    str += utils::Print("\t----- GROUPANALYSIS ------");
    str += utils::Print(QString("\tGroupAnalysisName: %1").arg(GroupAnalysisName));
    str += utils::Print(QString("\tDatetime: %1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")));
    str += utils::Print(QString("\tDescription: %1").arg(Description));
    str += utils::Print(QString("\tNotes: %1").arg(Notes));
    str += utils::Print(QString("\tFileCount: %1").arg(FileCount));
    str += utils::Print(QString("\tSize: %1").arg(Size));
    str += utils::Print(QString("\tVirtualPath: %1").arg(virtualPath));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelGroupAnalysis::VirtualPath() {
    QString vPath = QString("group-analysis/%1").arg(utils::CleanString(GroupAnalysisName));

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelGroupAnalysis::GetStagedFileList() {

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
