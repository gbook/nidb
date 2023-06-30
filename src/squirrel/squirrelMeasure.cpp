/* ------------------------------------------------------------------------------
  Squirrel measure.cpp
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

#include "squirrelMeasure.h"
#include "utils.h"

squirrelMeasure::squirrelMeasure()
{

}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelMeasure::ToJSON() {
    QJsonObject json;

	json["measureName"] = measureName;
	json["dateStart"] = dateStart.toString("yyyy-MM-dd HH:mm:ss");
	json["dateEnd"] = dateEnd.toString("yyyy-MM-dd HH:mm:ss");
	json["instrumentName"] = instrumentName;
	json["rater"] = rater;
	json["notes"] = notes;
	json["value"] = value;
	json["description"] = description;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintMeasure ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelMeasure::PrintMeasure
 */
void squirrelMeasure::PrintMeasure() {

    //Print("-- MEASURE ----------");
    //Print(QString("\t\t\tName: %1").arg(measureName));
    //Print(QString("\t\t\tDateStart: %1").arg(dateStart.toString()));
    //Print(QString("\t\t\tDateEnd: %1").arg(dateEnd.toString()));
    //Print(QString("\t\t\tInstrumentName: %1").arg(instrumentName));
    //Print(QString("\t\t\tRater: %1").arg(rater));
    //Print(QString("\t\t\tNotes: %1").arg(notes));
    //Print(QString("\t\t\tValue: %1").arg(value));
    //Print(QString("\t\t\tDescription: %1").arg(description));

    Print(QString("\t\t\tMEASURE\tName [%1]\tDateStart [%2]\tDateEnd [%3]\tInstrumentName [%4]\tRater [%5]\tNotes [%6]\tValue [%7]\tDescription [%8]").arg(measureName).arg(dateStart.toString()).arg(dateEnd.toString()).arg(instrumentName).arg(rater).arg(notes).arg(value).arg(description));
}
