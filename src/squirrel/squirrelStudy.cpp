/* ------------------------------------------------------------------------------
  Squirrel study.cpp
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
/* ----- addSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add series to this study
 * @param s squirrelSeries to be added
 * @return true if series was added, false otherwise
 */
bool squirrelStudy::addSeries(squirrelSeries s) {
	/* check size of the series list before and after adding */
	qint64 size(0);
	size = seriesList.size();

	/* create a copy of the object before appending */
    seriesList.append(s);

    if (seriesList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- addAnalysis ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Add an analysis to this study
 * @param a squirrelAnalysis to be added
 * @return true if analysis was added, false otherwise
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
/* ----- GetNextSeriesNumber ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the next series number for this study
 * @return the next series number
 */
qint64 squirrelStudy::GetNextSeriesNumber() {

    /* find the current highest series number */
    qint64 maxnum = 0;
    for (int i=0; i<seriesList.size(); i++)
        if (seriesList[i].number > maxnum)
            maxnum = seriesList[i].number;

    return maxnum+1;
}


/* ------------------------------------------------------------ */
/* ----- PrintStudy ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print study details
 */
void squirrelStudy::PrintStudy() {

    Print("\t\t\t----- STUDY -----");
    Print(QString("\t\t\tnumber: %1").arg(number));
    Print(QString("\t\t\tdateTime: %1").arg(dateTime.toString()));
    Print(QString("\t\t\tageAtStudy: %1").arg(ageAtStudy));
    Print(QString("\t\t\tHeight: %1 m").arg(height));
    Print(QString("\t\t\tWeight: %1 kg").arg(weight));
    Print(QString("\t\t\tModality: %1").arg(modality));
    Print(QString("\t\t\tDescription: %1").arg(description));
    Print(QString("\t\t\tStudyUID: %1").arg(studyUID));
    Print(QString("\t\t\tVisitType: %1").arg(visitType));
    Print(QString("\t\t\tDayNumber: %1").arg(dayNumber));
    Print(QString("\t\t\tTimePoint: %1").arg(timePoint));
    Print(QString("\t\t\tEquipment: %1").arg(equipment));
    Print(QString("\t\t\tPath: %1").arg(virtualPath));
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a JSON object for this study
 * @return JSON object containing the study
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
