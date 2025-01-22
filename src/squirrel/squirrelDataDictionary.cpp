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

squirrelDataDictionary::squirrelDataDictionary(QString dbID)
{
    databaseUUID = dbID;
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

    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from DataDictionary where DataDictionaryRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        q.first();

        /* get the data */
        objectID = q.value("DataDictionaryRowID").toLongLong();
        FileCount = q.value("FileCount").toLongLong();
        Size = q.value("Size").toLongLong();
        //virtualPath = q.value("VirtualPath").toString();

        /* get the DataDictionaryItems */
        dictItems.clear();
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select * from DataDictionaryItems where DataDictionaryRowID = :id");
        q.bindValue(":id", objectID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            dataDictionaryItem d;
            d.VariableType = q.value("VariableType").toString();
            d.VariableName = q.value("VariableName").toString();
            d.Description = q.value("VariableDescription").toString();
            d.KeyValueMapping = q.value("KeyValueMapping").toString();
            d.ExpectedTimepoints = q.value("ExpectedTimepoints").toInt();
            d.RangeLow = q.value("RangeLow").toDouble();
            d.RangeHigh = q.value("RangeHigh").toDouble();
            dictItems.append(d);
        }

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(databaseUUID, objectID, "datadictionary");

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

    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into DataDictionary (FileCount, Size, VirtualPath) values (:FileCount, :Size, :VirtualPath)");
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":Size", Size);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update DataDictionary set FileCount = :FileCount, Size = :Size, VirtualPath = :VirtualPath where DataDictionaryRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":Size", Size);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store the DataDictionaryItems */
    q.prepare("delete from DataDictionaryItems where DataDictionaryRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    foreach (dataDictionaryItem dic, dictItems) {
        q.prepare("insert into DataDictionaryItems (DataDictionaryRowID, VariableType, VariableName, VariableDescription, KeyValue, ExpectedTimepoints, RangeLow, RangeHigh) values (:DataDictionaryRowID, :VariableType, :VariableName, :VariableDescription, :KeyValue, :ExpectedTimepoints, :RangeLow, :RangeHigh)");
        q.bindValue(":DataDictionaryRowID", objectID);
        q.bindValue(":VariableType", dic.VariableType);
        q.bindValue(":VariableName", dic.VariableName);
        q.bindValue(":VariableDescription", dic.Description);
        q.bindValue(":KeyValue", dic.KeyValueMapping);
        q.bindValue(":ExpectedTimepoints", dic.ExpectedTimepoints);
        q.bindValue(":RangeLow", dic.RangeLow);
        q.bindValue(":RangeHigh", dic.RangeHigh);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    utils::StoreStagedFileList(databaseUUID, objectID, "datadictionary", stagedFiles);

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
        jsonItem["VariableType"] = item.VariableType;
        jsonItem["variableName"] = item.VariableName;
        jsonItem["Description"] = item.Description;
        jsonItem["KeyValueMapping"] = item.KeyValueMapping;
        jsonItem["ExpectedTimepoints"] = item.ExpectedTimepoints;
        jsonItem["RangeLow"] = item.RangeLow;
        jsonItem["RangeHigh"] = item.RangeHigh;
        jsonItems.append(jsonItem);
    }

    json["FileCount"] = FileCount;
    json["Size"] = Size;
    json["VirtualPath"] = VirtualPath();
    json["data-dictionary-items"] = jsonItems;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintDataDictionary ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelDataDictionary::PrintDataDictionary
 */
QString squirrelDataDictionary::PrintDataDictionary() {
    QString str;

    str += utils::Print("\t----- DATADICTIONARY ------");
    str += utils::Print(QString("\tFileCount: %1").arg(FileCount));
    str += utils::Print(QString("\tSize: %1").arg(Size));
    str += utils::Print(QString("\tVirtualPath: %1").arg(VirtualPath()));

    int i = 0;
    foreach (dataDictionaryItem item, dictItems) {
        str += utils::Print(QString("\tItem [%1]\ttype [%2]\tvariableName [%3]\ttype [%4]\ttype [%5]\ttype [%6]\ttype [%7]\ttype [%8]").arg(i).arg(item.VariableType).arg(item.VariableName).arg(item.Description).arg(item.KeyValueMapping).arg(item.ExpectedTimepoints).arg(item.RangeLow).arg(item.RangeHigh));
        i++;
    }

    return str;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelDataDictionary::VirtualPath() {
    QString vPath = QString("data-dictionary/%1").arg(utils::CleanString(DataDictionaryName));

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelDataDictionary::GetStagedFileList() {

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
