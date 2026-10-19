/* ------------------------------------------------------------------------------
  Squirrel squirrelDataDictionary.cpp
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

#include "squirrelDataDictionary.h"
#include "utils.h"
#include "squirrel.h"

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
        DataDictionaryName = q.value("DataDictionaryName").toString();
        FileCount = q.value("FileCount").toLongLong();
        Size = q.value("Size").toLongLong();

        /* get the DataDictionaryItems */
        dictItems.clear();
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select * from DataDictionaryItem where DataDictionaryRowID = :id");
        q.bindValue(":id", objectID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            dataDictionaryItem d;
            d.VariableType = q.value("VariableType").toString();
            d.VariableName = q.value("VariableName").toString();
            d.VariableDescription = q.value("VariableDescription").toString();
            d.KeyValueMapping = q.value("KeyValueMapping").toString();
            d.ExpectedTimepoints = q.value("ExpectedTimepoints").toInt();
            d.RangeLow = q.value("RangeLow").toDouble();
            d.RangeHigh = q.value("RangeHigh").toDouble();
            dictItems.append(d);
        }

        /* get any staged files */
        stagedFiles = utils::GetStagedFileList(databaseUUID, objectID, DataDictionary);

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
    bool isNewObject = (objectID < 0);
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert into DataDictionary (DataDictionaryName, FileCount, Size, VirtualPath) values (:DataDictionaryName, :FileCount, :Size, :VirtualPath)");
        q.bindValue(":DataDictionaryName", DataDictionaryName);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":Size", Size);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toLongLong();
    }
    /* ... otherwise update */
    else {
        q.prepare("update DataDictionary set DataDictionaryName = :DataDictionaryName, FileCount = :FileCount, Size = :Size, VirtualPath = :VirtualPath where DataDictionaryRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":DataDictionaryName", DataDictionaryName);
        q.bindValue(":FileCount", FileCount);
        q.bindValue(":Size", Size);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store the DataDictionaryItems */
    q.prepare("delete from DataDictionaryItem where DataDictionaryRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    foreach (dataDictionaryItem dic, dictItems) {
        q.prepare("insert into DataDictionaryItem (DataDictionaryRowID, VariableType, VariableName, VariableDescription, KeyValueMapping, ExpectedTimepoints, RangeLow, RangeHigh) values (:DataDictionaryRowID, :VariableType, :VariableName, :VariableDescription, :KeyValueMapping, :ExpectedTimepoints, :RangeLow, :RangeHigh)");
        q.bindValue(":DataDictionaryRowID", objectID);
        q.bindValue(":VariableType", dic.VariableType);
        q.bindValue(":VariableName", dic.VariableName);
        q.bindValue(":VariableDescription", dic.VariableDescription);
        q.bindValue(":KeyValueMapping", dic.KeyValueMapping);
        q.bindValue(":ExpectedTimepoints", dic.ExpectedTimepoints);
        q.bindValue(":RangeLow", dic.RangeLow);
        q.bindValue(":RangeHigh", dic.RangeHigh);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    /* store any staged filepaths */
    if (!isNewObject || !stagedFiles.isEmpty())
        utils::StoreStagedFileList(databaseUUID, objectID, DataDictionary, stagedFiles);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Return a JSON object representing this data dictionary and all its items
 * @return QJsonObject containing all data dictionary fields and item array
 */
QJsonObject squirrelDataDictionary::ToJSON() {

    QJsonObject json;

    QJsonArray jsonItems;
    foreach (dataDictionaryItem item, dictItems) {
        QJsonObject jsonItem;
        jsonItem["VariableType"] = item.VariableType;
        jsonItem["VariableName"] = item.VariableName;
        jsonItem["VariableDescription"] = item.VariableDescription;
        jsonItem["KeyValueMapping"] = item.KeyValueMapping;
        jsonItem["ExpectedTimepoints"] = item.ExpectedTimepoints;
        jsonItem["RangeLow"] = item.RangeLow;
        jsonItem["RangeHigh"] = item.RangeHigh;
        jsonItems.append(jsonItem);
    }

    json["DataDictionaryName"] = DataDictionaryName;
    json["FileCount"] = FileCount;
    json["Size"] = Size;
    json["VirtualPath"] = VirtualPath();
    json["data-dictionary-items"] = jsonItems;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- GetData ---------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the object data as a hash of 'Object.Variable' keys, for printing
 * @param d the dataset type (ID, basic, or full)
 * @return hash of the requested variables
 */
QHash<QString, QString> squirrelDataDictionary::GetData(DatasetType d) {
    QHash<QString, QString> data;

    data["DataDictionary.DataDictionaryName"] = DataDictionaryName;
    if (d == DatasetBasic || d == DatasetFull) {
        data["DataDictionary.ItemCount"] = QString("%1").arg(dictItems.size());
        data["DataDictionary.FileCount"] = QString("%1").arg(FileCount);
        data["DataDictionary.Size"] = QString("%1").arg(Size);
    }
    if (d == DatasetFull)
        data["DataDictionary.VirtualPath"] = VirtualPath();

    return data;
}


/* ------------------------------------------------------------ */
/* ----- GetItemData ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get one data dictionary item as a hash of 'DataDictionaryItem.Variable' keys, for printing
 * @param item the data dictionary item
 * @param d the dataset type (ID, basic, or full)
 * @return hash of the requested variables, including the parent data dictionary name
 */
QHash<QString, QString> squirrelDataDictionary::GetItemData(const dataDictionaryItem &item, DatasetType d) {
    QHash<QString, QString> data;

    data["DataDictionary.DataDictionaryName"] = DataDictionaryName;
    data["DataDictionaryItem.VariableName"] = item.VariableName;
    if (d == DatasetBasic || d == DatasetFull) {
        data["DataDictionaryItem.VariableType"] = item.VariableType;
        data["DataDictionaryItem.VariableDescription"] = item.VariableDescription;
    }
    if (d == DatasetFull) {
        data["DataDictionaryItem.KeyValueMapping"] = item.KeyValueMapping;
        data["DataDictionaryItem.ExpectedTimepoints"] = QString("%1").arg(item.ExpectedTimepoints);
        data["DataDictionaryItem.RangeLow"] = QString("%1").arg(item.RangeLow);
        data["DataDictionaryItem.RangeHigh"] = QString("%1").arg(item.RangeHigh);
    }

    return data;
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
    str += utils::Print(QString("\tName: %1").arg(DataDictionaryName));
    str += utils::Print(QString("\tFileCount: %1").arg(FileCount));
    str += utils::Print(QString("\tSize: %1").arg(Size));
    str += utils::Print(QString("\tVirtualPath: %1").arg(VirtualPath()));
    str += utils::Print(QString("\tItemCount: %1").arg(dictItems.size()));

    int i = 0;
    foreach (dataDictionaryItem item, dictItems) {
        str += utils::Print(QString("\tItem [%1]\tvariableName [%2]\ttype [%3]\tdescription [%4]\tkeyValueMapping [%5]\texpectedTimepoints [%6]\trangeLow [%7]\trangeHigh [%8]").arg(i).arg(item.VariableName).arg(item.VariableType).arg(item.VariableDescription).arg(item.KeyValueMapping).arg(item.ExpectedTimepoints).arg(item.RangeLow).arg(item.RangeHigh));
        i++;
    }

    return str;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Return the data dictionary's virtual path within the squirrel package
 * @return virtual path string (e.g. "data-dictionary/DataDictionaryName")
 */
QString squirrelDataDictionary::VirtualPath() {
    QString vPath = QString("data-dictionary/%1").arg(utils::CleanString(DataDictionaryName));

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Return all staged files as physical path / virtual path pairs
 * @return list of pairs where first is the physical disk path and second is the virtual path in the package
 */
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
