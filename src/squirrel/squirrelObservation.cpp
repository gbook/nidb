/* ------------------------------------------------------------------------------
  Squirrel observation.cpp
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

#include "squirrelObservation.h"
#include "utils.h"
#include "squirrel.h"

squirrelObservation::squirrelObservation(QString dbID)
{
    databaseUUID = dbID;
}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelObservation::Get
 * @return true if successful
 *
 * This function will attempt to load the observation data from
 * the database. The observationRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelObservation::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Observation where ObservationRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("ObservationRowID").toLongLong();
        subjectRowID = q.value("SubjectRowID").toLongLong();
        ObservationName = q.value("ObservationName").toString();
        DateStart = q.value("DateStart").toDateTime();
        DateEnd = q.value("DateEnd").toDateTime();
        InstrumentName = q.value("InstrumentName").toString();
        Rater = q.value("Rater").toString();
        Notes = q.value("Notes").toString();
        Value = q.value("Value").toString();
        Description = q.value("Description").toString();
        Duration = q.value("Duration").toLongLong();
        DateRecordCreate = q.value("DateRecordCreate").toDateTime();
        DateRecordEntry = q.value("DateRecordEntry").toDateTime();
        DateRecordModify = q.value("DateRecordModify").toDateTime();

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
 * @brief squirrelObservation::Store
 * @return true if successful
 *
 * This function will attempt to load the observation data from
 * the database. The observationRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelObservation::Store() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Observation (SubjectRowID, ObservationName, DateStart, DateEnd, InstrumentName, Rater, Notes, Value, Duration, DateRecordEntry, Description) values (:SubjectRowID, :ObservationName, :DateStart, :DateEnd, :InstrumentName, :Rater, :Notes, :Value, :Duration, :DateRecordEntry, :Description)");
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":ObservationName", ObservationName);
        q.bindValue(":DateStart", DateStart);
        q.bindValue(":DateEnd", DateEnd);
        q.bindValue(":InstrumentName", InstrumentName);
        q.bindValue(":Rater", Rater);
        q.bindValue(":Notes", Notes);
        q.bindValue(":Value", Value);
        q.bindValue(":Duration", Duration);
        q.bindValue(":DateRecordCreate", DateRecordCreate);
        q.bindValue(":DateRecordEntry", DateRecordEntry);
        q.bindValue(":DateRecordModify", DateRecordModify);
        q.bindValue(":Description", Description);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Observation set SubjectRowID = :SubjectRowID, ObservationName = :ObservationName, DateStart = :DateStart, DateEnd = :DateEnd, InstrumentName = :InstrumentName, Rater = :Rater, Notes = :Notes, Value = :Value, Duration = :Duration, DateRecordEntry = :DateRecordEntry, Description = :Description where ObservationRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":ObservationName", ObservationName);
        q.bindValue(":DateStart", DateStart);
        q.bindValue(":DateEnd", DateEnd);
        q.bindValue(":InstrumentName", InstrumentName);
        q.bindValue(":Rater", Rater);
        q.bindValue(":Notes", Notes);
        q.bindValue(":Value", Value);
        q.bindValue(":Duration", Duration);
        q.bindValue(":DateRecordCreate", DateRecordCreate);
        q.bindValue(":DateRecordEntry", DateRecordEntry);
        q.bindValue(":DateRecordModify", DateRecordModify);
        q.bindValue(":Description", Description);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelObservation::ToJSON() {
    QJsonObject json;

    json["DateEnd"] = DateEnd.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordCreate"] = DateRecordCreate.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordEntry"] = DateRecordEntry.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordModify"] = DateRecordModify.toString("yyyy-MM-dd HH:mm:ss");
    json["DateStart"] = DateStart.toString("yyyy-MM-dd HH:mm:ss");
    json["Description"] = Description;
    json["Duration"] = Duration;
    json["InstrumentName"] = InstrumentName;
    json["ObservationName"] = ObservationName;
    json["Notes"] = Notes;
    json["Rater"] = Rater;
    json["Value"] = Value;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintObservation ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelObservation::PrintObservation
 */
QString squirrelObservation::PrintObservation() {
    QString str;

    str += utils::Print(QString("\t\t\tMEASURE\tName [%1]\tDateStart [%2]\tDateEnd [%3]\tInstrumentName [%4]\tRater [%5]\tNotes [%6]\tValue [%7]\tDescription [%8]").arg(ObservationName).arg(DateStart.toString()).arg(DateEnd.toString()).arg(InstrumentName).arg(Rater).arg(Notes).arg(Value).arg(Description));

    return str;
}
