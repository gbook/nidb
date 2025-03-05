/* ------------------------------------------------------------------------------
  Squirrel Intervention.cpp
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

#include "squirrelIntervention.h"
#include "utils.h"
#include "squirrel.h"

squirrelIntervention::squirrelIntervention(QString dbID)
{
    databaseUUID = dbID;
}

/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelIntervention::Get
 * @return true if successful
 *
 * This function will attempt to load the Intervention data from
 * the database. The InterventionRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelIntervention::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Intervention where InterventionRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        AdministrationRoute = q.value("AdministrationRoute").toString();
        DateEnd = q.value("DateEnd").toDateTime();
        DateRecordCreate = q.value("DateRecordCreate").toDateTime();
        DateRecordEntry = q.value("DateRecordEntry").toDateTime();
        DateRecordModify = q.value("DateRecordModify").toDateTime();
        DateStart = q.value("DateStart").toDateTime();
        Description = q.value("Description").toString();
        DoseAmount = q.value("DoseAmount").toDouble();
        DoseFrequency = q.value("DoseFrequency").toString();
        DoseKey = q.value("DoseKey").toString();
        DoseString = q.value("DoseString").toString();
        DoseUnit = q.value("DoseUnit").toString();
        InterventionClass = q.value("InterventionClass").toString();
        InterventionName = q.value("InterventionName").toString();
        Notes = q.value("Notes").toString();
        Rater = q.value("Rater").toString();
        objectID = q.value("InterventionRowID").toLongLong();
        subjectRowID = q.value("SubjectRowID").toLongLong();

        //FrequencyModifier = q.value("FrequencyModifier").toString();
        //FrequencyUnit = q.value("FrequencyUnit").toString();
        //FrequencyValue = q.value("FrequencyValue").toDouble();

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
 * @brief squirrelIntervention::Store
 * @return true if successful
 *
 * This function will attempt to load the Intervention data from
 * the database. The InterventionRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelIntervention::Store() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Intervention (SubjectRowID, InterventionName, DateStart, DateEnd, DateRecordEntry, DoseString, DoseAmount, DoseFrequency, AdministrationRoute, InterventionClass, DoseKey, DoseUnit, FrequencyModifer, FrequencyValue, FrequencyUnit, Description, Rater, Notes) values (:SubjectRowID, :InterventionName, :DateStart, :DateEnd, :DateRecordEntry, :DoseString, :DoseAmount, :DoseFrequency, :AdministrationRoute, :InterventionClass, :DoseKey, :DoseUnit, :FrequencyModifer, :FrequencyValue, :FrequencyUnit, :Description, :Rater, :Notes)");

        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":InterventionName", InterventionName);
        q.bindValue(":DateStart", DateStart);
        q.bindValue(":DateEnd", DateEnd);
        q.bindValue(":DateRecordCreate", DateRecordCreate);
        q.bindValue(":DateRecordEntry", DateRecordEntry);
        q.bindValue(":DateRecordModify", DateRecordModify);
        q.bindValue(":DoseString", DoseString);
        q.bindValue(":DoseAmount", DoseAmount);
        q.bindValue(":DoseFrequency", DoseFrequency);
        q.bindValue(":AdministrationRoute", AdministrationRoute);
        q.bindValue(":InterventionClass", InterventionClass);
        q.bindValue(":DoseKey", DoseKey);
        q.bindValue(":DoseUnit", DoseUnit);
        //q.bindValue(":FrequencyModifer", frequencyModifier);
        //q.bindValue(":FrequencyValue", frequencyValue);
        //q.bindValue(":FrequencyUnit", frequencyUnit);
        q.bindValue(":Description", Description);
        q.bindValue(":Rater", Rater);
        q.bindValue(":Notes", Notes);

        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Intervention set SubjectRowID = :SubjectRowID, InterventionName = :InterventionName, DateStart = :DateStart, DateEnd = :DateEnd, DateRecordEntry = :DateRecordEntry, DoseString = :DoseString, DoseAmount = :DoseAmount, DoseFrequency = :DoseFrequency, AdministrationRoute = :AdministrationRoute, InterventionClass = :InterventionClass, DoseKey = :DoseKey, DoseUnit = :DoseUnit, FrequencyModifer = :FrequencyModifer, FrequencyValue = :FrequencyValue, FrequencyUnit = :FrequencyUnit, Description = :Description, Rater = :Rater, Notes = :Notes where InterventionRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":InterventionName", InterventionName);
        q.bindValue(":DateStart", DateStart);
        q.bindValue(":DateEnd", DateEnd);
        q.bindValue(":DateRecordCreate", DateRecordCreate);
        q.bindValue(":DateRecordEntry", DateRecordEntry);
        q.bindValue(":DateRecordModify", DateRecordModify);
        q.bindValue(":DoseString", DoseString);
        q.bindValue(":DoseAmount", DoseAmount);
        q.bindValue(":DoseFrequency", DoseFrequency);
        q.bindValue(":AdministrationRoute", AdministrationRoute);
        q.bindValue(":InterventionClass", InterventionClass);
        q.bindValue(":DoseKey", DoseKey);
        q.bindValue(":DoseUnit", DoseUnit);
        //q.bindValue(":FrequencyModifer", frequencyModifier);
        //q.bindValue(":FrequencyValue", frequencyValue);
        //q.bindValue(":FrequencyUnit", frequencyUnit);
        q.bindValue(":Description", Description);
        q.bindValue(":Rater", Rater);
        q.bindValue(":Notes", Notes);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelIntervention::ToJSON() {
    QJsonObject json;

    json["AdministrationRoute"] = AdministrationRoute;
    json["DateInterventionEnd"] = DateEnd.toString("yyyy-MM-dd HH:mm:ss");
    json["DateInterventionStart"] = DateStart.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordCreate"] = DateRecordCreate.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordEntry"] = DateRecordEntry.toString("yyyy-MM-dd HH:mm:ss");
    json["DateRecordModify"] = DateRecordModify.toString("yyyy-MM-dd HH:mm:ss");
    json["DoseAmount"] = DoseAmount;
    json["DoseFrequency"] = DoseFrequency;
    json["DoseKey"] = DoseKey;
    json["DoseString"] = DoseString;
    json["DoseUnit"] = DoseUnit;
    json["InterventionClass"] = InterventionClass;
    json["InterventionDescription"] = Description;
    json["InterventionName"] = InterventionName;
    //json["FrequencyModifier"] = frequencyModifier;
    //json["FrequencyUnit"] = frequencyUnit;
    //json["FrequencyValue"] = frequencyValue;
    json["Notes"] = Notes;
    json["Rater"] = Rater;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintIntervention ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelIntervention::PrintIntervention
 */
QString squirrelIntervention::PrintIntervention() {
    QString str;

    str += utils::Print("\t\t\t----- Intervention -----");
    str += utils::Print(QString("\t\t\tAdministrationRoute: %1").arg(AdministrationRoute));
    str += utils::Print(QString("\t\t\tDateEnd: %1").arg(DateEnd.toString("yyyy-MM-dd HH:mm:ss")));
    str += utils::Print(QString("\t\t\tDateRecordCreate: %1").arg(DateRecordCreate.toString("yyyy-MM-dd HH:mm:ss")));
    str += utils::Print(QString("\t\t\tDateRecordEntry: %1").arg(DateRecordEntry.toString("yyyy-MM-dd HH:mm:ss")));
    str += utils::Print(QString("\t\t\tDateRecordModify: %1").arg(DateRecordModify.toString("yyyy-MM-dd HH:mm:ss")));
    str += utils::Print(QString("\t\t\tDateStart: %1").arg(DateStart.toString("yyyy-MM-dd HH:mm:ss")));
    str += utils::Print(QString("\t\t\tDescription: %1").arg(Description));
    str += utils::Print(QString("\t\t\tDoseAmount: %1").arg(DoseAmount));
    str += utils::Print(QString("\t\t\tDoseFrequency: %1").arg(DoseFrequency));
    str += utils::Print(QString("\t\t\tDoseKey: %1").arg(DoseKey));
    str += utils::Print(QString("\t\t\tDoseUnit: %1").arg(DoseUnit));
    str += utils::Print(QString("\t\t\tInterventionClass: %1").arg(InterventionClass));
    str += utils::Print(QString("\t\t\tInterventionName: %1").arg(InterventionName));
    //utils::Print(QString("\t\t\tFrequencyModifier: %1").arg(frequencyModifier));
    //utils::Print(QString("\t\t\tFrequencyUnit: %1").arg(frequencyUnit));
    //utils::Print(QString("\t\t\tFrequencyValue: %1").arg(frequencyValue));
    str += utils::Print(QString("\t\t\tNotes: %1").arg(Notes));
    str += utils::Print(QString("\t\t\tRater: %1").arg(Rater));

    return str;
}
