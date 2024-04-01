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
        SeriesNumber = q.value("SeriesNumber").toLongLong();
        DateTime = q.value("Datetime").toDateTime();
        SeriesUID = q.value("SeriesUID").toString();
        Description = q.value("Description").toString();
        Protocol = q.value("Protocol").toString();
        experimentRowID = q.value("ExperimentRowID").toInt();
        FileCount = q.value("FileCount").toLongLong();
        Size = q.value("Size").toLongLong();
        BehavioralFileCount = q.value("BehavioralFileCount").toLongLong();
        BehavioralSize = q.value("BehavioralSize").toLongLong();
        SequenceNumber = q.value("SequenceNumber").toInt();

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
        q.prepare("insert or ignore into Series (StudyRowID, SeriesNumber, Datetime, SeriesUID, Description, Protocol, ExperimentRowID, Size, FileCount, BehavioralSize, BehavioralFileCount, SequenceNumber, VirtualPath) values (:StudyRowID, :SeriesNumber, :Datetime, :SeriesUID, :Description, :Protocol, :ExperimentRowID, :Size, :FileCount, :BehavioralSize, :BehavioralFileCount, :SequenceNumber, :VirtualPath)");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", SeriesNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":SeriesUID", SeriesUID);
        q.bindValue(":Description", Description);
        q.bindValue(":Protocol", Protocol);
        q.bindValue(":ExperimentRowID", experimentRowID);
        q.bindValue(":Size", Size);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":BehavioralSize", BehavioralSize);
        q.bindValue(":BehavioralFileCount", BehavioralFileCount);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Series set StudyRowID = :StudyRowID, SeriesNumber = :SeriesNumber, Datetime = :Datetime, SeriesUID = :SeriesUID, Description = :Description, Protocol = :Protocol, ExperimentRowID = :ExperimentRowID, Size = :Size, FileCount = :FileCount, BehavioralSize = :BehavioralSize, BehavioralFileCount = :BehavioralFileCount, SequenceNumber = :SequenceNumber, VirtualPath = :VirtualPath where SeriesRowID = :id");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", SeriesNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":SeriesUID", SeriesUID);
        q.bindValue(":Description", Description);
        q.bindValue(":Protocol", Protocol);
        q.bindValue(":ExperimentRowID", experimentRowID);
        q.bindValue(":Size", Size);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":BehavioralSize", BehavioralSize);
        q.bindValue(":BehavioralFileCount", BehavioralFileCount);
        q.bindValue(":SequenceNumber", SequenceNumber);
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
    utils::Print(QString("\t\t\t\tBehavioralFileCount: %1").arg(BehavioralFileCount));
    utils::Print(QString("\t\t\t\tBehavioralSize: %1").arg(BehavioralSize));
    utils::Print(QString("\t\t\t\tDatetime: %1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\t\tDescription: %1").arg(Description));
    utils::Print(QString("\t\t\t\tExperimentName: %1").arg(experimentRowID));
    utils::Print(QString("\t\t\t\tFileCount: %1").arg(FileCount));
    utils::Print(QString("\t\t\t\tProtocol: %1").arg(Protocol));
    utils::Print(QString("\t\t\t\tSequenceNumber: %1").arg(SequenceNumber));
    utils::Print(QString("\t\t\t\tSeriesNumber: %1").arg(SeriesNumber));
    utils::Print(QString("\t\t\t\tSeriesRowID: %1").arg(objectID));
    utils::Print(QString("\t\t\t\tSeriesUID: %1").arg(SeriesUID));
    utils::Print(QString("\t\t\t\tSize: %1").arg(Size));
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

    json["BehavioralSize"] = BehavioralSize;
    json["Description"] = Description;
    json["BehavioralFileCount"] = BehavioralFileCount;
    json["FileCount"] = FileCount;
    json["Protocol"] = Protocol;
    json["SequenceNumber"] = SequenceNumber;
    json["SeriesDatetime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["SeriesNumber"] = SeriesNumber;
    json["SeriesUID"] = SeriesUID;
    json["Size"] = Size;
    json["VirtualPath"] = VirtualPath();

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

    AnonymizeParams();

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

    /* get series directory */
    if (seriesDirFormat == "orig")
        seriesDir = QString("%1").arg(SeriesNumber);
    else
        seriesDir = QString("%1").arg(SequenceNumber);

    vPath = QString("data/%1/%2/%3").arg(subjectDir).arg(studyDir).arg(seriesDir);

    return vPath;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeParams -------------------------------- */
/* ---------------------------------------------------------- */
void squirrelSeries::AnonymizeParams() {

    QHash<QString, QString> p;
    QStringList anonFields;
    anonFields << "AcquisitionDate";
    anonFields << "AcquisitionTime";
    anonFields << "CommentsOnThePerformedProcedureSte";
    anonFields << "ContentDate";
    anonFields << "ContentTime";
    anonFields << "Filename";
    anonFields << "InstanceCreationDate";
    anonFields << "InstanceCreationTime";
    anonFields << "InstitutionAddress";
    anonFields << "InstitutionName";
    anonFields << "InstitutionalDepartmentName";
    anonFields << "OperatorsName";
    anonFields << "ParentDirectory";
    anonFields << "PatientBirthDate";
    anonFields << "PatientID";
    anonFields << "PatientName";
    anonFields << "PerformedProcedureStepDescription";
    anonFields << "PerformedProcedureStepID";
    anonFields << "PerformedProcedureStepStartDate";
    anonFields << "PerformedProcedureStepStartTime";
    anonFields << "PerformingPhysicianName";
    anonFields << "ReferringPhysicianName";
    anonFields << "RequestedProcedureDescription";
    anonFields << "RequestingPhysician";
    anonFields << "SeriesDate";
    anonFields << "SeriesDateTime";
    anonFields << "SeriesTime";
    anonFields << "StationName";
    anonFields << "StudyDate";
    anonFields << "StudyDateTime";
    anonFields << "StudyDescription";
    anonFields << "StudyTime";
    anonFields << "UniqueSeriesString";

    for(QHash<QString, QString>::iterator a = params.begin(); a != params.end(); ++a) {
        if (!anonFields.contains(a.key()))
            p[a.key()] = a.value();
    }

    params = p;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelSeries::GetStagedFileList() {

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
