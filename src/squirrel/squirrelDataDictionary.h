/* ------------------------------------------------------------------------------
  Squirrel squirrelDataDictionary.h
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

#ifndef SQUIRRELDATADICTIONARY_H
#define SQUIRRELDATADICTIONARY_H
#include <QtSql>
#include <QString>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The dataDictionaryItem structure
 */
struct dataDictionaryItem {
    QString VariableType;           /*!< the variable type */
    QString VariableName;   /*!< the variable name */
    QString Description;           /*!< longer variable description */
    QString KeyValueMapping;       /*!< 'key1=value2, key2=value2' ... example '1=Male, 2=Female' */
    int ExpectedTimepoints; /*!< expected number of timepoints */
    double RangeLow;        /*!< for numeric values, the lower limit */
    double RangeHigh;       /*!< for numeric values, the higher limit */
};


/**
 * @brief The squirrelDataDictionary class
 */
class squirrelDataDictionary
{
public:
    squirrelDataDictionary(QString dbID);

    QJsonObject ToJSON();
    QString PrintDataDictionary();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    QString VirtualPath();
    QList<QPair<QString,QString>> GetStagedFileList();
    QString GetDatabaseUUID() { return databaseUUID; }
    void SetDatabaseUUID(QString dbID) { databaseUUID = dbID; }

    /* JSON elements */
    QList<dataDictionaryItem> dictItems;    /*!< List of data dictionary items */
    QString DataDictionaryName;
    qint64 FileCount;                       /*!< total number of files */
    qint64 Size;                            /*!< disk size in bytes of the data dictionary */

    /* lib variables */
    QStringList stagedFiles;                /*!< staged file list: list of files in their own original paths which will be copied in before the package is zipped up */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
    QString databaseUUID;
};

#endif // SQUIRRELDATADICTIONARY_H
