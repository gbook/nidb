/* ------------------------------------------------------------------------------
  Squirrel drug.cpp
  Copyright (C) 2004 - 2022
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

squirrelDrug::squirrelDrug()
{

}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelDrug::ToJSON() {
    QJsonObject json;

	json["drugName"] = drugName;
	json["dateStart"] = dateStart.toString("yyyy-MM-dd HH:mm:ss");
	json["dateEnd"] = dateEnd.toString("yyyy-MM-dd HH:mm:ss");
	json["doseAmount"] = doseAmount;
	json["doseFrequency"] = doseFrequency;
	json["route"] = route;
	json["type"] = type;
	json["doseKey"] = doseKey;
	json["doseUnit"] = doseUnit;
	json["frequencyModifier"] = frequencyModifier;
	json["frequencyValue"] = frequencyValue;
	json["frequencyUnit"] = frequencyUnit;
	json["description"] = description;
	json["rater"] = rater;
	json["notes"] = notes;
	json["dateEntry"] = dateEntry.toString("yyyy-MM-dd HH:mm:ss");

    return json;
}
