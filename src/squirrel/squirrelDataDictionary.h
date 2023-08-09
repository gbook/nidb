/* ------------------------------------------------------------------------------
  Squirrel squirrelDataDictionary.h
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

#ifndef SQUIRRELDATADICTIONARY_H
#define SQUIRRELDATADICTIONARY_H

#include <QString>
#include <QJsonObject>
#include <QJsonArray>

struct dataDictionaryItem {
    QString type;
    QString variableName;
    QString desc;
    QString keyValue; /*!< 'key1=value2, key2=value2' ... example '1=Male, 2=Female' */
    int expectedTimepoints; /*!< expected number of timepoints */
    double rangeLow; /*!< for numeric values, the lower limit */
    double rangeHigh; /*!< for numeric values, the higher limit */
};

class squirrelDataDictionary
{
public:
    squirrelDataDictionary();

    QJsonObject ToJSON();
    void PrintDataDictionary();

    QList<dataDictionaryItem> dictItems; /*!< List of data dictionary items */

    qint64 numfiles;
    qint64 size; /*!< disk size in bytes of the data dictionary */

    QString virtualPath = "data-dictionary"; /*!< path within the squirrel package, no leading slash */
};

#endif // SQUIRRELDATADICTIONARY_H
