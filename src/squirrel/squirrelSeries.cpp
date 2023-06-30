/* ------------------------------------------------------------------------------
  Squirrel series.cpp
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

#include "squirrelSeries.h"
#include "utils.h"

squirrelSeries::squirrelSeries()
{
	number = 0;
	dateTime = QDateTime::currentDateTime();
    seriesUID = "";
    description = "";
    protocol = "";
	numFiles = 0;
	size = 0;
	numBehFiles = 0;
	behSize = 0;
	//QHash<QString, QString> params; /*!< Hash containing experimental parameters. eg MR params */
	//QStringList stagedFiles; /*!< staged file list: list of raw files in their own directories before the package is zipped up */
	//QStringList stagedBehFiles; /*!< staged beh file list: list of raw files in their own directories before the package is zipped up */
	//QStringList experimentList; /*!< List of experiment names attached to this series */
	virtualPath = "";

}


/* ------------------------------------------------------------ */
/* ----- PrintSeries ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print the series details
 */
void squirrelSeries::PrintSeries() {
    Print("\t\t\t\t----- SERIES -----");
    Print(QString("\t\t\t\tSeriesUID: %1").arg(seriesUID));
    Print(QString("\t\t\t\tSeriesNum: %1").arg(number));
    Print(QString("\t\t\t\tDescription: %1").arg(description));
    Print(QString("\t\t\t\tProtocol: %1").arg(protocol));
    Print(QString("\t\t\t\tNumFiles: %1").arg(numFiles));
    Print(QString("\t\t\t\tSize: %1").arg(size));
    Print(QString("\t\t\t\tNumBehFiles: %1").arg(numBehFiles));
    Print(QString("\t\t\t\tBehSize: %1").arg(behSize));
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a JSON object for the entire series
 * @return JSON object
 */
QJsonObject squirrelSeries::ToJSON() {
    QJsonObject json;

	json["number"] = number;
	json["seriesDateTime"] = dateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["description"] = description;
    json["protocol"] = protocol;
    json["numFiles"] = numFiles;
    json["size"] = size;
	json["numBehFiles"] = numBehFiles;
	json["behSize"] = behSize;
	json["path"] = virtualPath;

    QJsonArray JSONexperiments;
    for (int i=0; i<experimentList.size(); i++) {
		JSONexperiments.append(experimentList[i]);
    }
	if (JSONexperiments.size() > 0)
		json["Experiments"] = JSONexperiments;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- ParamsToJSON ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get series params in JSON format, likely MRI sequence params
 * @return JSON object containing series params
 */
QJsonObject squirrelSeries::ParamsToJSON() {
	QJsonObject json;

	for(QHash<QString, QString>::iterator a = params.begin(); a != params.end(); ++a) {
		json[a.key()] = a.value();
	}

	return json;
}
