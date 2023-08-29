/* ------------------------------------------------------------------------------
  Squirrel squirrelGroupAnalysis.cpp
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

#include "squirrelGroupAnalysis.h"
#include "utils.h"

squirrelGroupAnalysis::squirrelGroupAnalysis()
{

}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject squirrelGroupAnalysis::ToJSON() {
    QJsonObject json;

    json["pipelineName"] = pipelineName;
    json["pipelineVersion"] = pipelineVersion;
    json["startDate"] = startDate.toString("yyyy-MM-dd HH:mm:ss");
    json["endDate"] = endDate.toString("yyyy-MM-dd HH:mm:ss");
    json["numfiles"] = numfiles;
    json["size"] = size;
    json["virtualPath"] = virtualPath;

    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintGroupAnalysis ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelGroupAnalysis::PrintGroupAnalysis
 */
void squirrelGroupAnalysis::PrintGroupAnalysis() {

    Print("\t----- GROUPANALYSIS ------");
    Print(QString("\tPipelineName: %1").arg(pipelineName));
    Print(QString("\tPipelineVersion: %1").arg(pipelineVersion));
    Print(QString("\tStartDate: %1").arg(startDate.toString("yyyy-MM-dd HH:mm:ss")));
    Print(QString("\tEndDate: %1").arg(endDate.toString("yyyy-MM-dd HH:mm:ss")));
    Print(QString("\tNumfiles: %1").arg(numfiles));
    Print(QString("\tSize: %1").arg(size));
    Print(QString("\tVirtualPath: %1").arg(virtualPath));

}
