/* ------------------------------------------------------------------------------
  Squirrel squirrelDataDictionary.cpp
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

#include "squirrelDataDictionary.h"
#include "utils.h"

squirrelDataDictionary::squirrelDataDictionary()
{

}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelDataDictionary::Get
 * @return true if successful
 *
 * This function will attempt to load the dataDictionary data from
 * the database. The dataDictionaryRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelDataDictionary::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from DataDictionary where DataDictionaryRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        q.first();

        /* get the data */
        objectID = q.value("DataDictionaryRowID").toLongLong();
        numfiles = q.value("NumFiles").toLongLong();
        size = q.value("Size").toLongLong();
        virtualPath = q.value("VirtualPath").toString();

        /* get the DataDictionaryItems */
        dictItems.clear();
        QSqlQuery q(QSqlDatabase::database("squirrel"));
        q.prepare("select * from DataDictionaryItems where DataDictionaryRowID = :id");
        q.bindValue(":id", objectID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            dataDictionaryItem d;
            d.type = q.value("VariableType").toString();
            d.variableName = q.value("VariableName").toString();
            d.desc = q.value("VariableDescription").toString();
            d.keyValue = q.value("KeyValue").toString();
            d.expectedTimepoints = q.value("ExpectedTimepoints").toInt();
            d.rangeLow = q.value("RangeLow").toDouble();
            d.rangeHigh = q.value("RangeHigh").toDouble();
            dictItems.append(d);
        }

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(objectID, "datadictionary");

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
 * @brief squirrelDataDictionary::Store
 * @return true if successful
 *
 * This function will attempt to load the dataDictionary data from
 * the database. The dataDictionaryRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelDataDictionary::Store() {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into DataDictionary (NumFiles, Size, VirtualPath) values (:NumFiles, :Size, :VirtualPath)");
        q.bindValue(":NumFiles", numfiles);
        q.bindValue(":Size", size);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update DataDictionary set NumFiles = :NumFiles, Size = :Size, VirtualPath = :VirtualPath where DataDictionaryRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":NumFiles", numfiles);
        q.bindValue(":Size", size);
        q.bindValue(":VirtualPath", virtualPath);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store the DataDictionaryItems */
    q.prepare("delete from DataDictionaryItems where DataDictionaryRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    foreach (dataDictionaryItem dic, dictItems) {
        q.prepare("insert into DataDictionaryItems (DataDictionaryRowID, VariableType, VariableName, VariableDescription, KeyValue, ExpectedTimepoints, RangeLow, RangeHigh) values (:DataDictionaryRowID, :VariableType, :VariableName, :VariableDescription, :KeyValue, :ExpectedTimepoints, :RangeLow, :RangeHigh)");
        q.bindValue(":DataDictionaryRowID", objectID);
        q.bindValue(":VariableType", dic.type);
        q.bindValue(":VariableName", dic.variableName);
        q.bindValue(":VariableDescription", dic.desc);
        q.bindValue(":KeyValue", dic.keyValue);
        q.bindValue(":ExpectedTimepoints", dic.expectedTimepoints);
        q.bindValue(":RangeLow", dic.rangeLow);
        q.bindValue(":RangeHigh", dic.rangeHigh);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(objectID, "datadictionary", stagedFiles);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelDataDictionary::ToJSON() {

    QJsonObject json;

    QJsonArray jsonItems;
    foreach (dataDictionaryItem item, dictItems) {
        QJsonObject jsonItem;
        jsonItem["type"] = item.type;
        jsonItem["variableName"] = item.variableName;
        jsonItem["desc"] = item.desc;
        jsonItem["keyValue"] = item.keyValue;
        jsonItem["expectedTimepoints"] = item.expectedTimepoints;
        jsonItem["rangeLow"] = item.rangeLow;
        jsonItem["rangeHigh"] = item.rangeHigh;
        jsonItems.append(jsonItem);
    }

    json["numfiles"] = numfiles;
    json["size"] = size;
    json["virtualPath"] = virtualPath;
    json["data-dictionary-items"] = jsonItems;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintDataDictionary ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelDataDictionary::PrintDataDictionary
 */
void squirrelDataDictionary::PrintDataDictionary() {

    utils::Print("\t----- DATADICTIONARY ------");
    utils::Print(QString("\tNumFiles: %1").arg(numfiles));
    utils::Print(QString("\tSize: %1").arg(size));
    utils::Print(QString("\tVirtualPath: %1").arg(virtualPath));

    int i = 0;
    foreach (dataDictionaryItem item, dictItems) {
        utils::Print(QString("\tItem [%1]\ttype [%2]\tvariableName [%3]\ttype [%4]\ttype [%5]\ttype [%6]\ttype [%7]\ttype [%8]").arg(i).arg(item.type).arg(item.variableName).arg(item.desc).arg(item.keyValue).arg(item.expectedTimepoints).arg(item.rangeLow).arg(item.rangeHigh));
        i++;
    }
}
