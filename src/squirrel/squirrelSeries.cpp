/* ------------------------------------------------------------------------------
  Squirrel series.cpp
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

#include "squirrelSeries.h"
#include "utils.h"

squirrelSeries::squirrelSeries()
{

}

/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelSeries::Get
 * @return true if successful
 *
 * This function will attempt to load the series data from
 * the database. The seriesRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelSeries::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Series where SeriesRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("SeriesRowID").toLongLong();
        studyRowID = q.value("StudyRowID").toLongLong();
        number = q.value("SeriesNumber").toLongLong();
        dateTime = q.value("Datetime").toDateTime();
        seriesUID = q.value("SeriesUID").toString();
        description = q.value("Description").toString();
        protocol = q.value("Protocol").toString();
        experimentRowID = q.value("ExperimentRowID").toInt();
        numFiles = q.value("NumFiles").toLongLong();
        size = q.value("Size").toLongLong();
        numBehFiles = q.value("BehNumFiles").toLongLong();
        behSize = q.value("BehSize").toLongLong();
        sequence = q.value("Sequence").toInt();

        /* get any params */
        params = utils::GetParams(objectID);

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(objectID, "series");
        stagedBehFiles = utils::GetStagedFileList(objectID, "behseries");

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
 * @brief squirrelSeries::Store
 * @return true if successful
 *
 * This function will attempt to load the series data from
 * the database. The seriesRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelSeries::Store() {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Series (StudyRowID, SeriesNumber, Datetime, SeriesUID, Description, Protocol, ExperimentRowID, Size, NumFiles, BehSize, BehNumFiles, Sequence, VirtualPath) values (:StudyRowID, :SeriesNumber, :Datetime, :SeriesUID, :Description, :Protocol, :ExperimentRowID, :Size, :NumFiles, :BehSize, :BehNumFiles, :Sequence, :VirtualPath)");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", number);
        q.bindValue(":Datetime", dateTime);
        q.bindValue(":SeriesUID", seriesUID);
        q.bindValue(":Description", description);
        q.bindValue(":Protocol", protocol);
        q.bindValue(":ExperimentRowID", experimentRowID);
        q.bindValue(":Size", size);
        q.bindValue(":NumFiles", numFiles);
        q.bindValue(":BehSize", behSize);
        q.bindValue(":BehNumFiles", numBehFiles);
        q.bindValue(":Sequence", sequence);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Series set StudyRowID = :StudyRowID, SeriesNumber = :SeriesNumber, Datetime = :Datetime, SeriesUID = :SeriesUID, Description = :Description, Protocol = :Protocol, ExperimentRowID = :ExperimentRowID, Size = :Size, NumFiles = :NumFiles, BehSize = :BehSize, BehNumFiles = :BehNumFiles, Sequence = :Sequence, VirtualPath = :VirtualPath where SeriesRowID = :id");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", number);
        q.bindValue(":Datetime", dateTime);
        q.bindValue(":SeriesUID", seriesUID);
        q.bindValue(":Description", description);
        q.bindValue(":Protocol", protocol);
        q.bindValue(":ExperimentRowID", experimentRowID);
        q.bindValue(":Size", size);
        q.bindValue(":NumFiles", numFiles);
        q.bindValue(":BehSize", behSize);
        q.bindValue(":BehNumFiles", numBehFiles);
        q.bindValue(":Sequence", sequence);
        q.bindValue(":VirtualPath", VirtualPath());
        q.bindValue(":id", objectID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any params */
    utils::StoreParams(objectID, params);

    /* store any staged filepaths */
    utils::StoreStagedFileList(objectID, "series", stagedFiles);
    utils::StoreStagedFileList(objectID, "behseries", stagedBehFiles);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Remove ----------------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrelSeries::Remove() {

    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* ... delete any staged Study files */
    utils::RemoveStagedFileList(objectID, "series");

    /* delete the series */
    q.prepare("delete from Series where SeriesRowID = :seriesid");
    q.bindValue(":seriesid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* in case anyone tries to use this object again */
    objectID = -1;
    valid = false;

    return true;
}


/* ------------------------------------------------------------ */
/* ----- PrintSeries ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print the series details
 */
void squirrelSeries::PrintSeries() {
    utils::Print("\t\t\t\t----- SERIES -----");
    utils::Print(QString("\t\t\t\tBehSize: %1").arg(behSize));
    utils::Print(QString("\t\t\t\tDescription: %1").arg(description));
    utils::Print(QString("\t\t\t\tExperimentName: %1").arg(experimentRowID));
    utils::Print(QString("\t\t\t\tNumBehFiles: %1").arg(numBehFiles));
    utils::Print(QString("\t\t\t\tNumFiles: %1").arg(numFiles));
    utils::Print(QString("\t\t\t\tProtocol: %1").arg(protocol));
    utils::Print(QString("\t\t\t\tSequence: %1").arg(sequence));
    utils::Print(QString("\t\t\t\tSeriesDatetime: %1").arg(dateTime.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tSeriesNumber: %1").arg(number));
    utils::Print(QString("\t\t\t\tSeriesUID: %1").arg(seriesUID));
    utils::Print(QString("\t\t\t\tSize: %1").arg(size));
    utils::Print(QString("\t\t\t\tVirtualPath: %1").arg(VirtualPath()));

    foreach (QString f, stagedFiles) {
        utils::Print(QString("\t\t\t\t\tFile: %1").arg(f));
    }
    foreach (QString f, stagedBehFiles) {
        utils::Print(QString("\t\t\t\t\tBehFile: %1").arg(f));
    }
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a JSON object for the entire series
 * @return JSON object
 */
QJsonObject squirrelSeries::ToJSON() {
    QJsonObject json;

    json["BehSize"] = behSize;
    json["Description"] = description;
    json["NumBehFiles"] = numBehFiles;
    json["NumFiles"] = numFiles;
    json["Protocol"] = protocol;
    json["Sequence"] = sequence;
    json["SeriesDatetime"] = dateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["SeriesNumber"] = number;
    json["SeriesUID"] = seriesUID;
    json["Size"] = size;
    json["VirtualPath"] = VirtualPath();

    /* experiments */
    //QJsonArray JSONseriess;
    //for (int i=0; i<seriesNames.size(); i++) {
    //    JSONseriess.append(seriesNames[i]);
    //}
    //if (JSONseriess.size() > 0)
    //    json["SeriesNames"] = JSONseriess;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- ParamsToJSON ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get series params in JSON format, likely MRI sequence params
 * @return JSON object containing series params
 */
QJsonObject squirrelSeries::ParamsToJSON() {
	QJsonObject json;

	for(QHash<QString, QString>::iterator a = params.begin(); a != params.end(); ++a) {
		json[a.key()] = a.value();
	}

	return json;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelSeries::VirtualPath() {

    QString vPath;
    QString subjectDir;
    QString studyDir;
    QString seriesDir;
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

    /* get series directory */
    if (seriesDirFormat == "orig")
        seriesDir = QString("%1").arg(number);
    else
        seriesDir = QString("%1").arg(sequence);

    vPath = QString("data/%1/%2/%3").arg(subjectDir).arg(studyDir).arg(seriesDir);

    return vPath;
}
