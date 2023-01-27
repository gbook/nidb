/* ------------------------------------------------------------------------------
  Squirrel study.cpp
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

#include "squirrelStudy.h"
#include "utils.h"
#include <iostream>
#include <exception>

/* ------------------------------------------------------------ */
/* ----- study ------------------------------------------------ */
/* ------------------------------------------------------------ */
squirrelStudy::squirrelStudy()
{
	number = 1;
    dateTime = QDateTime::currentDateTime();
    modality = "UNKNOWN";
    weight = 0.0;
    height = 0.0;
}


/* ------------------------------------------------------------ */
/* ----- squirrelStudy ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelStudy::squirrelStudy
 * @param s
 * Copy constructor
 */
squirrelStudy::squirrelStudy(const squirrelStudy& s)
{
	ageAtStudy = s.ageAtStudy;
	analysisList = s.analysisList;
	dateTime = s.dateTime;
	dayNumber = s.dayNumber;
	description = s.description;
	equipment = s.equipment;
	height = s.height;
	modality = s.modality;
	number = s.number;
	seriesList = s.seriesList;
	studyUID = s.studyUID;
	timePoint = s.timePoint;
	virtualPath = s.virtualPath;
	visitType = s.visitType;
	weight = s.weight;
}


/* ------------------------------------------------------------ */
/* ----- addSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief study::addSeries
 * @param s
 * @return true if series was added, false if not added
 */
bool squirrelStudy::addSeries(squirrelSeries s) {
	/* check size of the series list before and after adding */
	qint64 size(0);
	size = seriesList.size();

	/* create a copy of the object before appending */
	squirrelSeries *s2 = new squirrelSeries(s);
	seriesList.append(*s2);

    if (seriesList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- addAnalysis ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief study::addAnalysis
 * @param a
 * @return true if analysis was added, false if not added
 */
bool squirrelStudy::addAnalysis(squirrelAnalysis a) {

	/* check size of the series list before and after adding */
	qint64 size = analysisList.size();

	analysisList.append(a);

	if (analysisList.size() > size)
		return true;
	else
		return false;
}


/* ------------------------------------------------------------ */
/* ----- PrintStudy ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief study::PrintStudy
 */
void squirrelStudy::PrintStudy() {

	Print("------ STUDY ----------");
	Print(QString("       number: %1").arg(number));
	Print(QString("       dateTime: %1").arg(dateTime.toString()));
	Print(QString("       ageAtStudy: %1").arg(ageAtStudy));
	Print(QString("       Height: %1 m").arg(height));
	Print(QString("       Weight: %1 kg").arg(weight));
	Print(QString("       Modality: %1").arg(modality));
	Print(QString("       Description: %1").arg(description));
	Print(QString("       StudyUID: %1").arg(studyUID));
	Print(QString("       VisitType: %1").arg(visitType));
	Print(QString("       DayNumber: %1").arg(dayNumber));
	Print(QString("       TimePoint: %1").arg(timePoint));
	Print(QString("       Equipment: %1").arg(equipment));
	Print(QString("       Path: %1").arg(virtualPath));
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelStudy::ToJSON
 * @return QJsonObject
 */
QJsonObject squirrelStudy::ToJSON() {
	QJsonObject json;

	json["number"] = number;
	json["dateTime"] = dateTime.toString("yyyy-MM-dd HH:mm:ss");
	json["ageAtStudy"] = ageAtStudy;
	json["height"] = height;
	json["weight"] = weight;
	json["modality"] = modality;
	json["description"] = description;
	json["studyUID"] = studyUID;
	json["visitType"] = visitType;
	json["dayNumber"] = dayNumber;
	json["timePoint"] = timePoint;
	json["equipment"] = equipment;
	json["path"] = virtualPath;

    /* add all the series */
	QJsonArray JSONseries;
	for (int i=0; i<seriesList.size(); i++) {
		JSONseries.append(seriesList[i].ToJSON());
	}
	json["numSeries"] = JSONseries.size();
	json["series"] = JSONseries;

	return json;
}
