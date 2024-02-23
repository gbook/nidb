/* ------------------------------------------------------------------------------
  Squirrel measure.cpp
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

#include "squirrelMeasure.h"
#include "utils.h"

squirrelMeasure::squirrelMeasure()
{

}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelMeasure::Get
 * @return true if successful
 *
 * This function will attempt to load the measure data from
 * the database. The measureRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelMeasure::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Measure where MeasureRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("MeasureRowID").toLongLong();
        subjectRowID = q.value("SubjectRowID").toLongLong();
        MeasureName = q.value("MeasureName").toString();
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
 * @brief squirrelMeasure::Store
 * @return true if successful
 *
 * This function will attempt to load the measure data from
 * the database. The measureRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelMeasure::Store() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Measure (SubjectRowID, MeasureName, DateStart, DateEnd, InstrumentName, Rater, Notes, Value, Duration, DateRecordEntry, Description) values (:SubjectRowID, :MeasureName, :DateStart, :DateEnd, :InstrumentName, :Rater, :Notes, :Value, :Duration, :DateRecordEntry, :Description)");
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":MeasureName", MeasureName);
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
        q.prepare("update Measure set SubjectRowID = :SubjectRowID, MeasureName = :MeasureName, DateStart = :DateStart, DateEnd = :DateEnd, InstrumentName = :InstrumentName, Rater = :Rater, Notes = :Notes, Value = :Value, Duration = :Duration, DateRecordEntry = :DateRecordEntry, Description = :Description where MeasureRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":MeasureName", MeasureName);
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
QJsonObject squirrelMeasure::ToJSON() {
    QJsonObject json;

    json["DateEnd"] = DateEnd.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordCreate"] = DateRecordCreate.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordEntry"] = DateRecordEntry.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordModify"] = DateRecordModify.toString("yyyy-MM-dd HH:mm:ss");
    json["DateStart"] = DateStart.toString("yyyy-MM-dd HH:mm:ss");
    json["Description"] = Description;
    json["Duration"] = Duration;
    json["InstrumentName"] = InstrumentName;
    json["MeasureName"] = MeasureName;
    json["Notes"] = Notes;
    json["Rater"] = Rater;
    json["Value"] = Value;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintMeasure ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelMeasure::PrintMeasure
 */
void squirrelMeasure::PrintMeasure() {

    utils::Print(QString("\t\t\tMEASURE\tName [%1]\tDateStart [%2]\tDateEnd [%3]\tInstrumentName [%4]\tRater [%5]\tNotes [%6]\tValue [%7]\tDescription [%8]").arg(MeasureName).arg(DateStart.toString()).arg(DateEnd.toString()).arg(InstrumentName).arg(Rater).arg(Notes).arg(Value).arg(Description));
}
