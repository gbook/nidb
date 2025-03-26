/* ------------------------------------------------------------------------------
  Squirrel series.cpp
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

#include "squirrelSeries.h"
#include "utils.h"
//#include "squirrel.h"

squirrelSeries::squirrelSeries(QString dbID)
{
    databaseUUID = dbID;
    debug = false;
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
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Series where SeriesRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        /* get the data */
        BIDSEntity = q.value("BIDSEntity").toString();
        BIDSSuffix = q.value("BIDSSuffix").toString();
        BIDSTask = q.value("BIDSTask").toString();
        BIDSRun = q.value("BIDSRun").toString();
        BIDSPhaseEncodingDirection = q.value("BIDSPhaseEncodingDirection").toString();
        BehavioralFileCount = q.value("BehavioralFileCount").toLongLong();
        BehavioralSize = q.value("BehavioralSize").toLongLong();
        DateTime = q.value("Datetime").toDateTime();
        Description = q.value("Description").toString();
        files = q.value("Files").toString().split(",");
        FileCount = q.value("FileCount").toLongLong();
        Protocol = q.value("Protocol").toString();
        Run = q.value("Run").toInt();
        SequenceNumber = q.value("SequenceNumber").toInt();
        SeriesNumber = q.value("SeriesNumber").toLongLong();
        SeriesUID = q.value("SeriesUID").toString();
        Size = q.value("Size").toLongLong();
        experimentRowID = q.value("ExperimentRowID").toInt();
        objectID = q.value("SeriesRowID").toLongLong();
        studyRowID = q.value("StudyRowID").toLongLong();

        /* get any params */
        params = utils::GetParams(databaseUUID, objectID);

        /* get any staged files */
        //utils::Print(QString("Series contains [%1] files before calling GetStagedFileList").arg(stagedFiles.size()));
        stagedFiles = utils::GetStagedFileList(databaseUUID, objectID, Series);
        stagedBehFiles = utils::GetStagedFileList(databaseUUID, objectID, BehSeries);
        //utils::Print(QString("Series contains [%1] files AFTER calling GetStagedFileList").arg(stagedFiles.size()));

        valid = true;
        return true;
    }
    else {
        valid = false;
        err = QString("objectID [%1] not found in database").arg(objectID);
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

    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Series (StudyRowID, SeriesNumber, Datetime, SeriesUID, Description, Protocol, BIDSEntity, BIDSSuffix, BIDSTask, BIDSRun, BIDSPhaseEncodingDirection, Run, ExperimentRowID, Size, Files, FileCount, BehavioralSize, BehavioralFileCount, SequenceNumber, VirtualPath) values (:StudyRowID, :SeriesNumber, :Datetime, :SeriesUID, :Description, :Protocol, :BIDSEntity, :BIDSSuffix, :BIDSTask, :BIDSRun, :BIDSPhaseEncodingDirection, :Run, :ExperimentRowID, :Size, :Files, :FileCount, :BehavioralSize, :BehavioralFileCount, :SequenceNumber, :VirtualPath)");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", SeriesNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":SeriesUID", SeriesUID);
        q.bindValue(":Description", Description);
        q.bindValue(":Protocol", Protocol);
        q.bindValue(":BIDSEntity", BIDSEntity);
        q.bindValue(":BIDSSuffix", BIDSSuffix);
        q.bindValue(":BIDSTask", BIDSTask);
        q.bindValue(":BIDSRun", BIDSRun);
        q.bindValue(":BIDSPhaseEncodingDirection", BIDSPhaseEncodingDirection);
        q.bindValue(":Run", Run);
        q.bindValue(":ExperimentRowID", experimentRowID);
        q.bindValue(":Size", Size);
        q.bindValue(":Files", files.join(","));
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":BehavioralSize", BehavioralSize);
        q.bindValue(":BehavioralFileCount", BehavioralFileCount);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
        //utils::Print(QString("Added series with seriesRowID [%1]").arg(objectID));
    }
    /* ... otherwise update */
    else {
        q.prepare("update Series set StudyRowID = :StudyRowID, SeriesNumber = :SeriesNumber, Datetime = :Datetime, SeriesUID = :SeriesUID, Description = :Description, Protocol = :Protocol, BIDSEntity = :BIDSEntity, BIDSSuffix = :BIDSSuffix, BIDSTask = :BIDSTask, BIDSRun = :BIDSRun, BIDSPhaseEncodingDirection = :BIDSPhaseEncodingDirection, Run = :Run, ExperimentRowID = :ExperimentRowID, Size = :Size, Files = :Files, FileCount = :FileCount, BehavioralSize = :BehavioralSize, BehavioralFileCount = :BehavioralFileCount, SequenceNumber = :SequenceNumber, VirtualPath = :VirtualPath where SeriesRowID = :id");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", SeriesNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":SeriesUID", SeriesUID);
        q.bindValue(":Description", Description);
        q.bindValue(":Protocol", Protocol);
        q.bindValue(":BIDSEntity", BIDSEntity);
        q.bindValue(":BIDSSuffix", BIDSSuffix);
        q.bindValue(":BIDSTask", BIDSTask);
        q.bindValue(":BIDSRun", BIDSRun);
        q.bindValue(":BIDSPhaseEncodingDirection", BIDSPhaseEncodingDirection);
        q.bindValue(":Run", Run);
        q.bindValue(":ExperimentRowID", experimentRowID);
        q.bindValue(":Size", Size);
        q.bindValue(":Files", files.join(","));
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":BehavioralSize", BehavioralSize);
        q.bindValue(":BehavioralFileCount", BehavioralFileCount);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        q.bindValue(":id", objectID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        //utils::Print(QString("Updated series with seriesRowID [%1]").arg(objectID));
    }

    /* store any params */
    utils::StoreParams(databaseUUID, objectID, params);

    /* store any staged filepaths */
    //utils::Print(QString("Series contains [%1] files before calling StoreStagedFileList").arg(stagedFiles.size()));
    utils::StoreStagedFileList(databaseUUID, objectID, Series, stagedFiles);
    utils::StoreStagedFileList(databaseUUID, objectID, BehSeries, stagedBehFiles);
    //utils::Print(QString("Series contains [%1] files AFTER calling StoreStagedFileList").arg(stagedFiles.size()));

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Remove ----------------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrelSeries::Remove() {

    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* ... delete any staged Study files */
    utils::RemoveStagedFileList(databaseUUID, objectID, Series);

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
QString squirrelSeries::PrintSeries(PrintFormat p) {
    QString str;

    if (p == BasicList) {
        QString s = QString("%1\t%2\t%3\t%4\t%5").arg(SeriesNumber).arg(Protocol).arg(Description).arg(DateTime.toString("yyyy-MM-dd HH:mm:ss").arg(FileCount));
        str += utils::Print(s);
    }
    else if (p == FullList) {
        QString s = QString("%1\t%2\t%3\t%4\t%5\t%6\t%7\t%8\t%9\t%10\t%11\t%12\t%13\t%14\t%15\t%16\t%17\t%18\t%19\t%20").arg(BIDSEntity).arg(BIDSSuffix).arg(BIDSTask).arg(BIDSRun).arg(BIDSPhaseEncodingDirection).arg(BehavioralFileCount).arg(BehavioralSize).arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")).arg(Description).arg(experimentRowID).arg(FileCount).arg(files.join(", ")).arg(Protocol).arg(Run).arg(SequenceNumber).arg(SeriesNumber).arg(objectID).arg(SeriesUID).arg(Size).arg(VirtualPath());
        str += utils::Print(s);
    }
    else {
        str += utils::Print("\t\t\t\t----- SERIES -----");
        str += utils::Print(QString("\t\t\t\tBIDSEntity: %1").arg(BIDSEntity));
        str += utils::Print(QString("\t\t\t\tBIDSSuffix: %1").arg(BIDSSuffix));
        str += utils::Print(QString("\t\t\t\tBIDSTask: %1").arg(BIDSTask));
        str += utils::Print(QString("\t\t\t\tBIDSRun: %1").arg(BIDSRun));
        str += utils::Print(QString("\t\t\t\tBIDSPhaseEncodingDirection: %1").arg(BIDSPhaseEncodingDirection));
        str += utils::Print(QString("\t\t\t\tBehavioralFileCount: %1").arg(BehavioralFileCount));
        str += utils::Print(QString("\t\t\t\tBehavioralSize: %1").arg(BehavioralSize));
        str += utils::Print(QString("\t\t\t\tDatetime: %1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")));
        str += utils::Print(QString("\t\t\t\tDescription: %1").arg(Description));
        str += utils::Print(QString("\t\t\t\tExperimentName: %1").arg(experimentRowID));
        str += utils::Print(QString("\t\t\t\tFileCount: %1").arg(FileCount));
        str += utils::Print(QString("\t\t\t\tFiles: %1").arg(files.join(", ")));
        str += utils::Print(QString("\t\t\t\tProtocol: %1").arg(Protocol));
        str += utils::Print(QString("\t\t\t\tRun: %1").arg(Run));
        str += utils::Print(QString("\t\t\t\tSequenceNumber: %1").arg(SequenceNumber));
        str += utils::Print(QString("\t\t\t\tSeriesNumber: %1").arg(SeriesNumber));
        str += utils::Print(QString("\t\t\t\tSeriesRowID: %1").arg(objectID));
        str += utils::Print(QString("\t\t\t\tSeriesUID: %1").arg(SeriesUID));
        str += utils::Print(QString("\t\t\t\tSize: %1").arg(Size));
        str += utils::Print(QString("\t\t\t\tVirtualPath: %1").arg(VirtualPath()));

        foreach (QString f, stagedFiles) {
            str += utils::Print(QString("\t\t\t\t\tFile: %1").arg(f));
        }
        foreach (QString f, stagedBehFiles) {
            str += utils::Print(QString("\t\t\t\t\tBehFile: %1").arg(f));
        }
    }

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintTree -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print series tree
 */
QString squirrelSeries::PrintTree(bool isLast) {
    QString str;

    QString dateTime = DateTime.toString("yyyy-MM-dd HH:mm:ss");
    QString protocol = Protocol.trimmed();
    QString seriesDesc = Description.trimmed();
    if (dateTime == "")
        dateTime = "(blankDateTime)";
    if (protocol == "")
        protocol = "(blankProtocol)";
    if (seriesDesc == "")
        seriesDesc = "(blankSeriesDesc)";

    if (isLast)
        str += utils::Print(QString("             +--- Series %1 - %2  %3  %4").arg(SeriesNumber).arg(dateTime).arg(protocol).arg(seriesDesc));
    else
        str += utils::Print(QString("   |    |    |--- Series %1 - %2  %3  %4").arg(SeriesNumber).arg(dateTime).arg(protocol).arg(seriesDesc));

    return str;
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

    json["BIDSEntity"] = BIDSEntity;
    json["BIDSSuffix"] = BIDSSuffix;
    json["BIDSTask"] = BIDSTask;
    json["BIDSRun"] = BIDSRun;
    json["BIDSPhaseEncodingDirection"] = BIDSPhaseEncodingDirection;
    json["BehavioralFileCount"] = BehavioralFileCount;
    json["BehavioralSize"] = BehavioralSize;
    json["Description"] = Description;
    json["FileCount"] = FileCount;
    if (Protocol == "")
        json["Protocol"] = Description;
    else
        json["Protocol"] = Protocol;
    json["Run"] = Run;
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
/**
 * @brief Get the virtual path for this series within the package
 * @return The virtual path
 */
QString squirrelSeries::VirtualPath() {

    QString vPath;
    QString subjectDir;
    QString studyDir;
    QString seriesDir;
    int subjectRowID = -1;

    /* get the parent study directory */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
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
/**
 * @brief Remove selected fields from the series params that may contain PHI
 */
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
/**
 * @brief Get a list of all staged files
 * The list is a list of pairs of physical disk path & virtual path
 * Example: "/path/to/file.txt" , "data/S1234/1/2/file.txt"
 * @return Hash of staged files
 */
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


/* ------------------------------------------------------------ */
/* ----- GetData ---------------------------------------------- */
/* ------------------------------------------------------------ */
QHash<QString, QString> squirrelSeries::GetData(DatasetType d) {

    QHash<QString, QString> data;


    switch (d) {
    case DatasetID:
        data["Series.Number"] = QString("%1").arg(SeriesNumber);
        break;
    case DatasetBasic:
        data["Series.Datetime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
        data["Series.Description"] = Description;
        data["Series.FileCount"] = QString("%1").arg(FileCount);
        data["Series.Number"] = QString("%1").arg(SeriesNumber);
        data["Series.Protocol"] = Protocol;
        data["Series.Size"] = QString("%1").arg(Size);
        break;
    case DatasetFull:
        data["Series.BIDSEntity"] = BIDSEntity;
        data["Series.BIDSPhaseEncodingDirection"] = BIDSPhaseEncodingDirection;
        data["Series.BIDSRun"] = BIDSRun;
        data["Series.BIDSSuffix"] = BIDSSuffix;
        data["Series.BIDSTask"] = BIDSTask;
        data["Series.BehavioralFileCount"] = QString("%1").arg(BehavioralFileCount);
        data["Series.BehavioralSize"] = QString("%1").arg(BehavioralSize);
        data["Series.Datetime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
        data["Series.Description"] = Description;
        data["Series.ExperimentName"] = QString("%1").arg(experimentRowID);
        data["Series.FileCount"] = QString("%1").arg(FileCount);
        data["Series.Files"] = files.join(", ");
        data["Series.Number"] = QString("%1").arg(SeriesNumber);
        data["Series.Protocol"] = Protocol;
        data["Series.RowID"] = QString("%1").arg(objectID);
        data["Series.Run"] = QString("%1").arg(Run);
        data["Series.SequenceNumber"] = QString("%1").arg(SequenceNumber);
        data["Series.SeriesUID"] = SeriesUID;
        data["Series.Size"] = QString("%1").arg(Size);
        data["Series.VirtualPath"] = VirtualPath();
        break;
    default:
        break;
    }

    return data;
}
