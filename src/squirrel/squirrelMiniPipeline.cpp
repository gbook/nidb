/* ------------------------------------------------------------------------------
  Squirrel measure.cpp
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

#include "squirrelMiniPipeline.h"
#include "utils.h"

squirrelMiniPipeline::squirrelMiniPipeline()
{

}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelMiniPipeline::ToJSON() {
    QJsonObject json;

	//json["measureName"] = measureName;
	//json["dateStart"] = dateStart.toString("yyyy-MM-dd HH:mm:ss");
	//json["dateEnd"] = dateEnd.toString("yyyy-MM-dd HH:mm:ss");
	//json["instrumentName"] = instrumentName;
	//json["rater"] = rater;
	//json["notes"] = notes;
	//json["value"] = value;
	//json["description"] = description;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintMinipipeline ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelMinipipeline::PrintMinipipeline
 */
void squirrelMiniPipeline::PrintMinipipeline() {

	Print("-- MEASURE ----------");
//	Print(QString("   Name: %1").arg(measureName));
//	Print(QString("   DateStart: %1").arg(dateStart.toString()));
//	Print(QString("   DateEnd: %1").arg(dateEnd.toString()));
//	Print(QString("   InstrumentName: %1").arg(instrumentName));
//	Print(QString("   Rater: %1").arg(rater));
//	Print(QString("   Notes: %1").arg(notes));
//	Print(QString("   Value: %1").arg(value));
//	Print(QString("   Description: %1").arg(description));

}
