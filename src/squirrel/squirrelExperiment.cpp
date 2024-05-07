/* ------------------------------------------------------------------------------
  Squirrel experiment.cpp
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

#include "squirrelExperiment.h"
#include "utils.h"

squirrelExperiment::squirrelExperiment()
{

}

/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelExperiment::Get
 * @return true if successful
 *
 * This function will attempt to load the experiment data from
 * the database. The experimentRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelExperiment::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Experiment where ExperimentRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("ExperimentRowID").toLongLong();
        ExperimentName = q.value("ExperimentName").toString();
        FileCount = q.value("FileCount").toLongLong();
        Size = q.value("Size").toLongLong();

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(objectID, "experiment");

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
 * @brief squirrelExperiment::Store
 * @return true if successful
 *
 * This function will attempt to load the experiment data from
 * the database. The experimentRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelExperiment::Store() {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Experiment (ExperimentName, Size, FileCount, VirtualPath) values (:name, :size, :FileCount, :virtualpath)");
        q.bindValue(":name", ExperimentName);
        q.bindValue(":size", Size);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":virtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Experiment set ExperimentName = :name, Size = :size, FileCount = :FileCount, VirtualPath = :virtualpath where ExperimentRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":name", ExperimentName);
        q.bindValue(":size", Size);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":virtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(objectID, "experiment", stagedFiles);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelExperiment::ToJSON() {
    QJsonObject json;

    json["ExperimentName"] = ExperimentName;
    json["FileCount"] = FileCount;
    json["Size"] = Size;
    json["VirtualPath"] = VirtualPath();

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintExperiment -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelExperiment::PrintExperiment
 */
void squirrelExperiment::PrintExperiment() {

    utils::Print("\t----- EXPERIMENT -----");
    utils::Print(QString("\tExperimentName: %1").arg(ExperimentName));
    utils::Print(QString("\tFileCount: %1").arg(FileCount));
    utils::Print(QString("\tSize: %1").arg(Size));
    utils::Print(QString("\tExperimentRowID: %1").arg(objectID));
    utils::Print(QString("\tVirtualPath: %1").arg(VirtualPath()));
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelExperiment::VirtualPath() {
    QString vPath = QString("experiments/%1").arg(utils::CleanString(ExperimentName));

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelExperiment::GetStagedFileList() {

    QList<QPair<QString,QString>> stagedList;

    QString path;
    foreach (path, stagedFiles) {
        QPair<QString, QString> pair;
        pair.first = path;
        pair.second = VirtualPath();
        stagedList.append(pair);
        utils::Print(QString("Inside GetStagedFileList() - stagedList [%1] -- [%2]").arg(pair.first).arg(pair.second));
    }

    return stagedList;
}
