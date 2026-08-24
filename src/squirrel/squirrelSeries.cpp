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
/* ----- Populate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Populate object fields from a database query result row
 * @param q an executed QSqlQuery positioned at the row to read
 */
void squirrelSeries::Populate(const QSqlQuery &q) {
    objectID                   = q.value("SeriesRowID").toLongLong();
    studyRowID                 = q.value("StudyRowID").toLongLong();
    if (q.record().contains("SubjectRowID"))
        subjectRowID           = q.value("SubjectRowID").toLongLong();
    BidsEntity                 = q.value("BidsEntity").toString();
    BidsSuffix                 = q.value("BidsSuffix").toString();
    BidsTask                   = q.value("BidsTask").toString();
    BidsRun                    = q.value("BidsRun").toString();
    BidsPhaseEncodingDirection = q.value("BidsPhaseEncodingDirection").toString();
    BehavioralFileCount        = q.value("BehavioralFileCount").toLongLong();
    BehavioralSize             = q.value("BehavioralSize").toLongLong();
    DateTime                   = q.value("Datetime").toDateTime();
    Description                = q.value("Description").toString();
    files                      = q.value("Files").toString().split(",", Qt::SkipEmptyParts);
    FileCount                  = q.value("FileCount").toLongLong();
    Protocol                   = q.value("Protocol").toString();
    Run                        = q.value("Run").toInt();
    SequenceNumber             = q.value("SequenceNumber").toInt();
    SeriesNumber               = q.value("SeriesNumber").toLongLong();
    SeriesUID                  = q.value("SeriesUID").toString();
    Size                       = q.value("Size").toLongLong();
    experimentRowID            = q.value("ExperimentRowID").toInt();
    removed = false;
    Validate();
}


/* ------------------------------------------------------------ */
/* ----- Validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelSeries::Validate
 * @return true if this object is in a state that can be written to,
 * or was read from, a squirrel package
 *
 * Checks the object's fields for anything that would produce a corrupt
 * or unwritable series. Every problem found is appended to the public
 * 'msg' variable, which is blank if the object is valid. This performs
 * no database queries other than checking that the connection exists,
 * so it is safe to call in a loop.
 */
bool squirrelSeries::Validate() {

    QStringList m;

    /* the object must still exist */
    if (removed)
        m << "series has been removed from the database";

    /* there must be a usable database connection */
    if (databaseUUID.trimmed() == "")
        m << "databaseUUID is not set";
    else if (!QSqlDatabase::database(databaseUUID, false).isValid())
        m << QString("database connection [%1] does not exist").arg(databaseUUID);

    /* a series is meaningless without a parent study */
    if (studyRowID < 0)
        m << QString("studyRowID [%1] is invalid. A series must belong to a study").arg(studyRowID);

    /* SeriesNumber is half of the UNIQUE(StudyRowID, SeriesNumber) key, and is used
       as the directory name when SeriesDirFormat is 'orig' */
    if (SeriesNumber < 0)
        m << QString("SeriesNumber [%1] is invalid. Must be 0 or greater").arg(SeriesNumber);

    /* SequenceNumber is used as the directory name when SeriesDirFormat is 'seq'. It is 0 until
       the study is resequenced, which happens after the series is stored, so only a negative
       value is wrong here */
    if (SequenceNumber < 0)
        m << QString("SequenceNumber [%1] is invalid. Must be 0 or greater").arg(SequenceNumber);

    /* directory formats must be one of the two known values */
    if ((subjectDirFormat != "orig") && (subjectDirFormat != "seq"))
        m << QString("subjectDirFormat [%1] is invalid. Must be 'orig' or 'seq'").arg(subjectDirFormat);
    if ((studyDirFormat != "orig") && (studyDirFormat != "seq"))
        m << QString("studyDirFormat [%1] is invalid. Must be 'orig' or 'seq'").arg(studyDirFormat);
    if ((seriesDirFormat != "orig") && (seriesDirFormat != "seq"))
        m << QString("seriesDirFormat [%1] is invalid. Must be 'orig' or 'seq'").arg(seriesDirFormat);

    if (Run < 0)
        m << QString("Run [%1] is invalid. Must be 0 or greater").arg(Run);

    /* counts and sizes cannot be negative */
    if (Size < 0)
        m << QString("Size [%1] is invalid. Must be 0 or greater").arg(Size);
    if (FileCount < 0)
        m << QString("FileCount [%1] is invalid. Must be 0 or greater").arg(FileCount);
    if (BehavioralSize < 0)
        m << QString("BehavioralSize [%1] is invalid. Must be 0 or greater").arg(BehavioralSize);
    if (BehavioralFileCount < 0)
        m << QString("BehavioralFileCount [%1] is invalid. Must be 0 or greater").arg(BehavioralFileCount);

    /* a datetime holding a value that isn't a real date (a failed parse yields a null
       QDateTime, which is allowed here and simply means 'no datetime') */
    //if (!DateTime.isNull() && !DateTime.isValid())
    //    m << "Datetime is set, but is not a valid datetime";

    msg = m.join("; ");
    valid = m.isEmpty();

    return valid;
}


/* ------------------------------------------------------------ */
/* ----- LogInvalid ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Record, and optionally print, the reason this series is invalid
 * @param func the calling function name
 */
void squirrelSeries::LogInvalid(QString func) {

    err = msg;
    if (debug)
        utils::Print(QString("[%1] Invalid series (SeriesNumber [%2] StudyRowID [%3] SeriesRowID [%4]): %5").arg(func).arg(SeriesNumber).arg(studyRowID).arg(objectID).arg(msg));
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
        msg = "objectID is not set";
        err = msg;
        return false;
    }
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Series left join Study on Series.StudyRowID = Study.StudyRowID where Series.SeriesRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        Populate(q);

        /* get any params */
        params = utils::GetParams(databaseUUID, objectID);

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(databaseUUID, objectID, Series);
        stagedBehFiles = utils::GetStagedFileList(databaseUUID, objectID, BehSeries);

        /* the row loaded, but it may still contain values that can't be written back out */
        if (!Validate())
            LogInvalid(__FUNCTION__);

        return true;
    }
    else {
        valid = false;
        msg = QString("objectID [%1] not found in database").arg(objectID);
        err = msg;
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

    /* refuse to write an object that would corrupt the package */
    if (!Validate()) {
        LogInvalid(__FUNCTION__);
        return false;
    }

    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    bool isNewObject = (objectID < 0);
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Series (StudyRowID, SeriesNumber, Datetime, SeriesUID, Description, Protocol, BidsEntity, BidsSuffix, BidsTask, BidsRun, BidsPhaseEncodingDirection, Run, ExperimentRowID, Size, Files, FileCount, BehavioralSize, BehavioralFileCount, SequenceNumber, VirtualPath) values (:StudyRowID, :SeriesNumber, :Datetime, :SeriesUID, :Description, :Protocol, :BidsEntity, :BidsSuffix, :BidsTask, :BidsRun, :BidsPhaseEncodingDirection, :Run, :ExperimentRowID, :Size, :Files, :FileCount, :BehavioralSize, :BehavioralFileCount, :SequenceNumber, :VirtualPath)");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", SeriesNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":SeriesUID", SeriesUID);
        q.bindValue(":Description", Description);
        q.bindValue(":Protocol", Protocol);
        q.bindValue(":BidsEntity", BidsEntity);
        q.bindValue(":BidsSuffix", BidsSuffix);
        q.bindValue(":BidsTask", BidsTask);
        q.bindValue(":BidsRun", BidsRun);
        q.bindValue(":BidsPhaseEncodingDirection", BidsPhaseEncodingDirection);
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
        if (q.numRowsAffected() > 0) {
            objectID = q.lastInsertId().toLongLong();
        }
        else {
            /* the insert was ignored, meaning a series with this StudyRowID/SeriesNumber
               already exists. lastInsertId() would return an unrelated rowID here, so look
               up the existing row instead */
            QSqlQuery q2(QSqlDatabase::database(databaseUUID));
            q2.prepare("select SeriesRowID from Series where StudyRowID = :StudyRowID and SeriesNumber = :SeriesNumber");
            q2.bindValue(":StudyRowID", studyRowID);
            q2.bindValue(":SeriesNumber", SeriesNumber);
            utils::SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            if (q2.next()) {
                objectID = q2.value("SeriesRowID").toLongLong();
                msg = QString("Series [%1] already exists in study [%2]").arg(SeriesNumber).arg(studyRowID);
                err = msg;
            }
            else {
                valid = false;
                msg = QString("Unable to insert or find series [%1] in study [%2]").arg(SeriesNumber).arg(studyRowID);
                err = msg;
                return false;
            }
        }
        //utils::Print(QString("Added series with seriesRowID [%1]").arg(objectID));
    }
    /* ... otherwise update */
    else {
        q.prepare("update Series set StudyRowID = :StudyRowID, SeriesNumber = :SeriesNumber, Datetime = :Datetime, SeriesUID = :SeriesUID, Description = :Description, Protocol = :Protocol, BidsEntity = :BidsEntity, BidsSuffix = :BidsSuffix, BidsTask = :BidsTask, BidsRun = :BidsRun, BidsPhaseEncodingDirection = :BidsPhaseEncodingDirection, Run = :Run, ExperimentRowID = :ExperimentRowID, Size = :Size, Files = :Files, FileCount = :FileCount, BehavioralSize = :BehavioralSize, BehavioralFileCount = :BehavioralFileCount, SequenceNumber = :SequenceNumber, VirtualPath = :VirtualPath where SeriesRowID = :id");
        q.bindValue(":StudyRowID", studyRowID);
        q.bindValue(":SeriesNumber", SeriesNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":SeriesUID", SeriesUID);
        q.bindValue(":Description", Description);
        q.bindValue(":Protocol", Protocol);
        q.bindValue(":BidsEntity", BidsEntity);
        q.bindValue(":BidsSuffix", BidsSuffix);
        q.bindValue(":BidsTask", BidsTask);
        q.bindValue(":BidsRun", BidsRun);
        q.bindValue(":BidsPhaseEncodingDirection", BidsPhaseEncodingDirection);
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
    if (!isNewObject || !params.isEmpty())
        utils::StoreParams(databaseUUID, objectID, params);

    /* store any staged filepaths */
    //utils::Print(QString("Series contains [%1] files before calling StoreStagedFileList").arg(stagedFiles.size()));
    if (!isNewObject || !stagedFiles.isEmpty())
        utils::StoreStagedFileList(databaseUUID, objectID, Series, stagedFiles);
    if (!isNewObject || !stagedBehFiles.isEmpty())
        utils::StoreStagedFileList(databaseUUID, objectID, BehSeries, stagedBehFiles);
    //utils::Print(QString("Series contains [%1] files AFTER calling StoreStagedFileList").arg(stagedFiles.size()));

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Store (bulk insert) ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Bind this series' values to a pre-prepared bulk-insert query and execute it
 * @param q a QSqlQuery prepared with the appropriate INSERT statement
 * @return true if successful
 */
bool squirrelSeries::Store(QSqlQuery &q) {

    /* refuse to write an object that would corrupt the package */
    if (!Validate()) {
        LogInvalid(__FUNCTION__);
        return false;
    }

    q.bindValue(":StudyRowID", studyRowID);
    q.bindValue(":SeriesNumber", SeriesNumber);
    q.bindValue(":Datetime", DateTime);
    q.bindValue(":SeriesUID", SeriesUID);
    q.bindValue(":Description", Description);
    q.bindValue(":Protocol", Protocol);
    q.bindValue(":BidsEntity", BidsEntity);
    q.bindValue(":BidsSuffix", BidsSuffix);
    q.bindValue(":BidsTask", BidsTask);
    q.bindValue(":BidsRun", BidsRun);
    q.bindValue(":BidsPhaseEncodingDirection", BidsPhaseEncodingDirection);
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
    objectID = q.lastInsertId().toLongLong();

    if (!params.isEmpty())
        utils::StoreParams(databaseUUID, objectID, params);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Remove ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Remove this series and its staged files from the database
 * @return true if successful
 */
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
    removed = true;
    valid = false;
    msg = "series has been removed from the database";

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
        QString s = QString("%1\t%2\t%3\t%4\t%5").arg(SeriesNumber).arg(Protocol).arg(Description).arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")).arg(FileCount);
        str += utils::Print(s);
    }
    else if (p == FullList) {
        QString s = QString("%1\t%2\t%3\t%4\t%5\t%6\t%7\t%8\t%9\t%10\t%11\t%12\t%13\t%14\t%15\t%16\t%17\t%18\t%19\t%20").arg(BidsEntity).arg(BidsSuffix).arg(BidsTask).arg(BidsRun).arg(BidsPhaseEncodingDirection).arg(BehavioralFileCount).arg(BehavioralSize).arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")).arg(Description).arg(experimentRowID).arg(FileCount).arg(files.join(", ")).arg(Protocol).arg(Run).arg(SequenceNumber).arg(SeriesNumber).arg(objectID).arg(SeriesUID).arg(Size).arg(VirtualPath());
        str += utils::Print(s);
    }
    else {
        str += utils::Print("\t\t\t\t----- SERIES -----");
        str += utils::Print(QString("\t\t\t\tBidsEntity: %1").arg(BidsEntity));
        str += utils::Print(QString("\t\t\t\tBidsSuffix: %1").arg(BidsSuffix));
        str += utils::Print(QString("\t\t\t\tBidsTask: %1").arg(BidsTask));
        str += utils::Print(QString("\t\t\t\tBidsRun: %1").arg(BidsRun));
        str += utils::Print(QString("\t\t\t\tBidsPhaseEncodingDirection: %1").arg(BidsPhaseEncodingDirection));
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

    json["BidsEntity"] = BidsEntity;
    json["BidsSuffix"] = BidsSuffix;
    json["BidsTask"] = BidsTask;
    json["BidsRun"] = BidsRun;
    json["BidsPhaseEncodingDirection"] = BidsPhaseEncodingDirection;
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

    QString subjectDir;
    QString studyDir;
    if (parentSubjectSeqNum >= 0) {
        subjectDir = (subjectDirFormat == "orig") ? utils::CleanString(parentSubjectID) : QString::number(parentSubjectSeqNum);
        studyDir   = (studyDirFormat   == "orig") ? QString::number(parentStudyNumber)  : QString::number(parentStudySeqNum);
    } else {
        int parentSubjectRowID = -1;
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select SubjectRowID, StudyNumber, SequenceNumber from Study where StudyRowID = :studyid");
        q.bindValue(":studyid", studyRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.next()) {
            parentSubjectRowID = q.value("SubjectRowID").toInt();
            studyDir = (studyDirFormat == "orig") ? QString::number(q.value("StudyNumber").toInt()) : QString::number(q.value("SequenceNumber").toInt());
        }
        q.prepare("select ID, SequenceNumber from Subject where SubjectRowID = :subjectid");
        q.bindValue(":subjectid", parentSubjectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.next()) {
            subjectDir = (subjectDirFormat == "orig") ? utils::CleanString(q.value("ID").toString()) : QString::number(q.value("SequenceNumber").toInt());
        }
    }

    QString seriesDir = (seriesDirFormat == "orig") ? QString::number(SeriesNumber) : QString::number(SequenceNumber);

    return QString("data/%1/%2/%3").arg(subjectDir).arg(studyDir).arg(seriesDir);
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
/**
 * @brief Return a key/value hash of series fields for the requested dataset level
 * @param d the dataset detail level (DatasetID, DatasetBasic, or DatasetFull)
 * @return hash of field names to string values
 */
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
        data["Series.BidsEntity"] = BidsEntity;
        data["Series.BidsPhaseEncodingDirection"] = BidsPhaseEncodingDirection;
        data["Series.BidsRun"] = BidsRun;
        data["Series.BidsSuffix"] = BidsSuffix;
        data["Series.BidsTask"] = BidsTask;
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
