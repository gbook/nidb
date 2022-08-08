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
#include "utils.h"

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


/* ------------------------------------------------------------ */
/* ----- PrintDrug -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelDrug::PrintDrug
 */
void squirrelDrug::PrintDrug() {

	Print("-- DRUG----------");
	Print(QString("       DrugName: %1").arg(drugName));
	Print(QString("       DateStart: %1").arg(dateStart.toString("yyyy-MM-dd HH:mm:ss")));
	Print(QString("       DateEnd: %1").arg(dateEnd.toString("yyyy-MM-dd HH:mm:ss")));
	Print(QString("       DoseAmount: %1").arg(doseAmount));
	Print(QString("       DoseFrequency: %1").arg(doseFrequency));
	Print(QString("       Route: %1").arg(route));
	Print(QString("       Type: %1").arg(type));
	Print(QString("       DoseKey: %1").arg(doseKey));
	Print(QString("       DoseUnit: %1").arg(doseUnit));
	Print(QString("       FrequencyModifier: %1").arg(frequencyModifier));
	Print(QString("       FrequencyValue: %1").arg(frequencyValue));
	Print(QString("       FrequencyUnit: %1").arg(frequencyUnit));
	Print(QString("       Description: %1").arg(description));
	Print(QString("       Rater: %1").arg(rater));
	Print(QString("       Notes: %1").arg(notes));
	Print(QString("       DateEntry: %1").arg(dateEntry.toString("yyyy-MM-dd HH:mm:ss")));

}
