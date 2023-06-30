/* ------------------------------------------------------------------------------
  Squirrel squirrelAnalysis.cpp
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


#include "squirrelAnalysis.h"
#include "utils.h"

squirrelAnalysis::squirrelAnalysis()
{

}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelAnalysis::ToJSON() {
	QJsonObject json;

	json["pipelineName"] = pipelineName;
	json["pipelineVersion"] = pipelineVersion;
	json["clusterStartDate"] = clusterStartDate.toString("yyyy-MM-dd HH:mm:ss");
	json["clusterEndDate"] = clusterEndDate.toString("yyyy-MM-dd HH:mm:ss");
	json["startDate"] = startDate.toString("yyyy-MM-dd HH:mm:ss");
	json["endDate"] = endDate.toString("yyyy-MM-dd HH:mm:ss");
	json["setupTime"] = setupTime;
	json["runTime"] = runTime;
	json["numSeries"] = numSeries;
	json["successful"] = successful;
	json["size"] = size;
	json["hostname"] = hostname;
	json["status"] = status;
	json["lastMessage"] = lastMessage;
	json["virtualPath"] = virtualPath;

	return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintAnalysis ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print the analysis details
 */
void squirrelAnalysis::PrintAnalysis() {

    Print("\t\t\t\t----- ANALYSIS -----");
    Print(QString("\t\t\t\tPipelineName: %1").arg(pipelineName));
    Print(QString("\t\t\t\tPipelineVersion: %1").arg(pipelineVersion));
    Print(QString("\t\t\t\tClusterStartDate: %1").arg(clusterStartDate.toString("yyyy-MM-dd HH:mm:ss")));
    Print(QString("\t\t\t\tClusterEndDate: %1").arg(clusterEndDate.toString("yyyy-MM-dd HH:mm:ss")));
    Print(QString("\t\t\t\tStartDate: %1").arg(startDate.toString("yyyy-MM-dd HH:mm:ss")));
    Print(QString("\t\t\t\tEndDate: %1").arg(endDate.toString("yyyy-MM-dd HH:mm:ss")));
    Print(QString("\t\t\t\tSetupTime: %1").arg(setupTime));
    Print(QString("\t\t\t\tRunTime: %1").arg(runTime));
    Print(QString("\t\t\t\tNumSeries: %1").arg(numSeries));
    Print(QString("\t\t\t\tSuccessful: %1").arg(successful));
    Print(QString("\t\t\t\tSize: %1").arg(size));
    Print(QString("\t\t\t\tHostname: %1").arg(hostname));
    Print(QString("\t\t\t\tStatus: %1").arg(status));
    Print(QString("\t\t\t\tLastMessage: %1").arg(lastMessage));
    Print(QString("\t\t\t\tVirtualPath: %1").arg(virtualPath));

}
