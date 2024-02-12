/* ------------------------------------------------------------------------------
  Squirrel drug.cpp
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

#include "squirrelDrug.h"
#include "utils.h"

squirrelDrug::squirrelDrug()
{

}

/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelDrug::Get
 * @return true if successful
 *
 * This function will attempt to load the drug data from
 * the database. The drugRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelDrug::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Drug where DrugRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("DrugRowID").toLongLong();
        subjectRowID = q.value("SubjectRowID").toLongLong();
        drugName = q.value("DrugName").toString();
        dateStart = q.value("DateStart").toDateTime();
        dateEnd = q.value("DateEnd").toDateTime();
        doseString = q.value("DoseString").toString();
        doseAmount = q.value("DoseAmount").toDouble();
        doseFrequency = q.value("DoseFrequency").toString();
        route = q.value("Route").toString();
        drugClass = q.value("DrugClass").toString();
        doseKey = q.value("DoseKey").toString();
        doseUnit = q.value("DoseUnit").toString();
        frequencyModifier = q.value("FrequencyModifier").toString();
        frequencyValue = q.value("FrequencyValue").toDouble();
        frequencyUnit = q.value("FrequencyUnit").toString();
        description = q.value("Description").toString();
        rater = q.value("Rater").toString();
        notes = q.value("Notes").toString();
        dateRecordEntry = q.value("DateRecordEntry").toDateTime();

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
 * @brief squirrelDrug::Store
 * @return true if successful
 *
 * This function will attempt to load the drug data from
 * the database. The drugRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelDrug::Store() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into Drug (SubjectRowID, DrugName, DateStart, DateEnd, DateRecordEntry, DoseString, DoseAmount, DoseFrequency, AdministrationRoute, DrugClass, DoseKey, DoseUnit, FrequencyModifer, FrequencyValue, FrequencyUnit, Description, Rater, Notes) values (:SubjectRowID, :DrugName, :DateStart, :DateEnd, :DateRecordEntry, :DoseString, :DoseAmount, :DoseFrequency, :AdministrationRoute, :DrugClass, :DoseKey, :DoseUnit, :FrequencyModifer, :FrequencyValue, :FrequencyUnit, :Description, :Rater, :Notes)");

        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":DrugName", drugName);
        q.bindValue(":DateStart", dateStart);
        q.bindValue(":DateEnd", dateEnd);
        q.bindValue(":DateRecordEntry", dateRecordEntry);
        q.bindValue(":DoseString", doseString);
        q.bindValue(":DoseAmount", doseAmount);
        q.bindValue(":DoseFrequency", doseFrequency);
        q.bindValue(":AdministrationRoute", route);
        q.bindValue(":DrugClass", drugClass);
        q.bindValue(":DoseKey", doseKey);
        q.bindValue(":DoseUnit", doseUnit);
        q.bindValue(":FrequencyModifer", frequencyModifier);
        q.bindValue(":FrequencyValue", frequencyValue);
        q.bindValue(":FrequencyUnit", frequencyUnit);
        q.bindValue(":Description", description);
        q.bindValue(":Rater", rater);
        q.bindValue(":Notes", notes);

        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Drug set SubjectRowID = :SubjectRowID, DrugName = :DrugName, DateStart = :DateStart, DateEnd = :DateEnd, DateRecordEntry = :DateRecordEntry, DoseString = :DoseString, DoseAmount = :DoseAmount, DoseFrequency = :DoseFrequency, AdministrationRoute = :AdministrationRoute, DrugClass = :DrugClass, DoseKey = :DoseKey, DoseUnit = :DoseUnit, FrequencyModifer = :FrequencyModifer, FrequencyValue = :FrequencyValue, FrequencyUnit = :FrequencyUnit, Description = :Description, Rater = :Rater, Notes = :Notes where DrugRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":DrugName", drugName);
        q.bindValue(":DateStart", dateStart);
        q.bindValue(":DateEnd", dateEnd);
        q.bindValue(":DateRecordEntry", dateRecordEntry);
        q.bindValue(":DoseString", doseString);
        q.bindValue(":DoseAmount", doseAmount);
        q.bindValue(":DoseFrequency", doseFrequency);
        q.bindValue(":AdministrationRoute", route);
        q.bindValue(":DrugClass", drugClass);
        q.bindValue(":DoseKey", doseKey);
        q.bindValue(":DoseUnit", doseUnit);
        q.bindValue(":FrequencyModifer", frequencyModifier);
        q.bindValue(":FrequencyValue", frequencyValue);
        q.bindValue(":FrequencyUnit", frequencyUnit);
        q.bindValue(":Description", description);
        q.bindValue(":Rater", rater);
        q.bindValue(":Notes", notes);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelDrug::ToJSON() {
    QJsonObject json;

    json["DrugName"] = drugName;
    json["DateStart"] = dateStart.toString("yyyy-MM-dd HH:mm:ss");
    json["DateEnd"] = dateEnd.toString("yyyy-MM-dd HH:mm:ss");
    json["DoseAmount"] = doseAmount;
    json["DoseFrequency"] = doseFrequency;
    json["Route"] = route;
    json["DrugClass"] = drugClass;
    json["DoseKey"] = doseKey;
    json["DoseUnit"] = doseUnit;
    json["FrequencyModifier"] = frequencyModifier;
    json["FrequencyValue"] = frequencyValue;
    json["FrequencyUnit"] = frequencyUnit;
    json["Description"] = description;
    json["Rater"] = rater;
    json["Notes"] = notes;
    json["DateRecordEntry"] = dateRecordEntry.toString("yyyy-MM-dd HH:mm:ss");

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintDrug -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelDrug::PrintDrug
 */
void squirrelDrug::PrintDrug() {

    utils::Print("\t\t\t----- DRUG -----");
    utils::Print(QString("\t\t\tDrugName: %1").arg(drugName));
    utils::Print(QString("\t\t\tDateStart: %1").arg(dateStart.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\tDateEnd: %1").arg(dateEnd.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\tDoseAmount: %1").arg(doseAmount));
    utils::Print(QString("\t\t\tDoseFrequency: %1").arg(doseFrequency));
    utils::Print(QString("\t\t\tRoute: %1").arg(route));
    utils::Print(QString("\t\t\tDrugClass: %1").arg(drugClass));
    utils::Print(QString("\t\t\tDoseKey: %1").arg(doseKey));
    utils::Print(QString("\t\t\tDoseUnit: %1").arg(doseUnit));
    utils::Print(QString("\t\t\tFrequencyModifier: %1").arg(frequencyModifier));
    utils::Print(QString("\t\t\tFrequencyValue: %1").arg(frequencyValue));
    utils::Print(QString("\t\t\tFrequencyUnit: %1").arg(frequencyUnit));
    utils::Print(QString("\t\t\tDescription: %1").arg(description));
    utils::Print(QString("\t\t\tRater: %1").arg(rater));
    utils::Print(QString("\t\t\tNotes: %1").arg(notes));
    utils::Print(QString("\t\t\tDateRecordEntry: %1").arg(dateRecordEntry.toString("yyyy-MM-dd HH:mm:ss")));

}
