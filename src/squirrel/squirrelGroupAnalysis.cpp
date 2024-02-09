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

squirrelGroupAnalysis::squirrelGroupAnalysis()
{

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
    QSqlQuery q;
    q.prepare("select * from GroupAnalysis where GroupAnalysisRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("GroupAnalysisRowID").toLongLong();
        groupAnalysisName = q.value("GroupAnalysisName").toString();
        numFiles = q.value("NumFiles").toLongLong();
        size = q.value("Size").toLongLong();
        description = q.value("Description").toString();
        notes = q.value("Notes").toString();
        dateTime = q.value("Datetime").toDateTime();
        virtualPath = q.value("VirtualPath").toString();

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(objectID, "groupanalysis");

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

    QSqlQuery q;

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into GroupAnalysis (GroupAnalysisName, Description, Datetime, NumFiles, Size, VirtualPath) values (:GroupAnalysisName, :Description, :Datetime, :NumFiles, :Size, :VirtualPath)");
        q.bindValue(":GroupAnalysisName", groupAnalysisName);
        q.bindValue(":Description", description);
        q.bindValue(":Datetime", dateTime);
        q.bindValue(":NumFiles", numFiles);
        q.bindValue(":Size", size);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update GroupAnalysis set GroupAnalysisName = :GroupAnalysisName, Description = :Description, Datetime = :Datetime, NumFiles = :NumFiles, Size = :Size, VirtualPath = :Virtualpath where GroupAnalysisRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":GroupAnalysisName", groupAnalysisName);
        q.bindValue(":Description", description);
        q.bindValue(":Datetime", dateTime);
        q.bindValue(":NumFiles", numFiles);
        q.bindValue(":Size", size);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(objectID, "groupanalysis", stagedFiles);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelGroupAnalysis::ToJSON() {
    QJsonObject json;

    json["GroupAnalysisName"] = groupAnalysisName;
    json["Datetime"] = dateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["Description"] = description;
    json["Notes"] = notes;
    json["NumFiles"] = numFiles;
    json["Size"] = size;
    json["VirtualPath"] = virtualPath;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintGroupAnalysis ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelGroupAnalysis::PrintGroupAnalysis
 */
void squirrelGroupAnalysis::PrintGroupAnalysis() {

    utils::Print("\t----- GROUPANALYSIS ------");
    utils::Print(QString("\tGroupAnalysisName: %1").arg(groupAnalysisName));
    utils::Print(QString("\tDatetime: %1").arg(dateTime.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\tDescription: %1").arg(description));
    utils::Print(QString("\tNotes: %1").arg(notes));
    utils::Print(QString("\tNumfiles: %1").arg(numFiles));
    utils::Print(QString("\tSize: %1").arg(size));
    utils::Print(QString("\tVirtualPath: %1").arg(virtualPath));

}
