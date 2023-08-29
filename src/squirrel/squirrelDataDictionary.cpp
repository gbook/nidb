/* ------------------------------------------------------------------------------
  Squirrel squirrelDataDictionary.cpp
  Copyright (C) 2004 - 2023
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

    Print("\t----- DATADICTIONARY ------");
    Print(QString("\tNumFiles: %1").arg(numfiles));
    Print(QString("\tSize: %1").arg(size));
    Print(QString("\tVirtualPath: %1").arg(virtualPath));

    int i = 0;
    foreach (dataDictionaryItem item, dictItems) {
        Print(QString("\tItem [%1]\ttype [%2]\tvariableName [%3]\ttype [%4]\ttype [%5]\ttype [%6]\ttype [%7]\ttype [%8]").arg(i).arg(item.type).arg(item.variableName).arg(item.desc).arg(item.keyValue).arg(item.expectedTimepoints).arg(item.rangeLow).arg(item.rangeHigh));
        i++;
    }
}
