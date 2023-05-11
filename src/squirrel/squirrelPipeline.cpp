/* ------------------------------------------------------------------------------
  Squirrel pipeline.cpp
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

#include "squirrelPipeline.h"
#include <QFile>
#include <QTextStream>
#include "utils.h"


/* ---------------------------------------------------------- */
/* --------- pipeline --------------------------------------- */
/* ---------------------------------------------------------- */
squirrelPipeline::squirrelPipeline()
{

}


/* ---------------------------------------------------------- */
/* --------- ToJSON ----------------------------------------- */
/* ---------------------------------------------------------- */
/* if path is specified, write the full JSON object to that
 * path and return a small JSON object */
QJsonObject squirrelPipeline::ToJSON(QString path) {
    QJsonObject json;

    json["name"] = pipelineName;
    json["description"] = description;
    json["createDate"] = createDate.toString();
    json["version"] = version;
    json["level"] = level;

    json["parentPipelines"] = parentPipelines.join(",");
    json["completeFiles"] = QJsonArray::fromStringList(completeFiles);

    json["dataCopyMethod"] = dataCopyMethod;
    json["directory"] = directory;
    json["dirStructure"] = dirStructure;
    json["depLevel"] = depLevel;
    json["depDir"] = depDir;
    json["depLinkType"] = depLinkType;
    json["group"] = group;
    json["groupType"] = groupType;
    json["notes"] = notes;
    json["resultScript"] = resultScript;
    json["tmpDir"] = tmpDir;
    json["useTmpDir"] = flags.useTmpDir;
    json["useProfile"] = flags.useProfile;

    json["clusterType"] = clusterType;
    json["clusterUser"] = clusterUser;
    json["clusterQueue"] = clusterQueue;
    json["clusterSubmitHost"] = clusterSubmitHost;
    json["maxWallTime"] = maxWallTime;
    json["submitDelay"] = submitDelay;
    json["numConcurrentAnalyses"] = numConcurrentAnalyses;

    /* add the dataSteps */
    QJsonArray JSONdataSteps;
    for (int i=0; i < dataSteps.size(); i++) {
        QJsonObject dataStep;
        dataStep["associationType"] = dataSteps[i].associationType;
        dataStep["behDir"] = dataSteps[i].behDir;
        dataStep["behFormat"] = dataSteps[i].behFormat;
        dataStep["dataFormat"] = dataSteps[i].dataFormat;
        dataStep["imageType"] = dataSteps[i].imageType;
        dataStep["datalevel"] = dataSteps[i].datalevel;
        dataStep["location"] = dataSteps[i].location;
        dataStep["modality"] = dataSteps[i].modality;
        dataStep["numBOLDreps"] = dataSteps[i].numBOLDreps;
        dataStep["numImagesCriteria"] = dataSteps[i].numImagesCriteria;
        dataStep["order"] = dataSteps[i].order;
        dataStep["protocol"] = dataSteps[i].protocol;
        dataStep["seriesCriteria"] = dataSteps[i].seriesCriteria;
        dataStep["enabled"] = dataSteps[i].flags.enabled;
        dataStep["optional"] = dataSteps[i].flags.optional;
        dataStep["gzip"] = dataSteps[i].flags.gzip;
        dataStep["preserveSeries"] = dataSteps[i].flags.preserveSeries;
        dataStep["primaryProtocol"] = dataSteps[i].flags.primaryProtocol;
        dataStep["usePhaseDir"] = dataSteps[i].flags.usePhaseDir;
        dataStep["useSeries"] = dataSteps[i].flags.useSeries;

        JSONdataSteps.append(dataStep);
    }
    json["numSubjects"] = JSONdataSteps.size();
    json["dataSteps"] = JSONdataSteps;

    /* write all pipeline info to path */
    QString m;
    QString pipelinepath = QString("%1/pipelines/%2").arg(path).arg(pipelineName);
    if (MakePath(pipelinepath, m)) {
        //QByteArray j = QJsonDocument(json).toJson();
        //QFile fout(QString(pipelinepath + "/pipeline.json"));
        //if (fout.open(QIODevice::WriteOnly))
        //    fout.write(j);
        //else
        //    Print("Error writing file [" + pipelinepath + "/pipeline.json]");

        /* write the scripts */
        if (!WriteTextFile(QString(pipelinepath + "/primaryScript.sh"), primaryScript))
            Print("Error writing primary script [" + pipelinepath + "/primaryScript.sh]");

        if (!WriteTextFile(QString(pipelinepath + "/secondaryScript.sh"), secondaryScript))
            Print("Error writing secondary script [" + pipelinepath + "/secondaryScript.sh]");

    }
    else {
        Print("Error creating path [" + pipelinepath + "] because of [" + m + "]");
    }

    /* return JSON object */
    return json;
}


/* ------------------------------------------------------------ */
/* ----- PrintPipeline ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelPipeline::PrintPipeline
 */
void squirrelPipeline::PrintPipeline() {

    Print("-- PIPELINE ----------");

    Print(QString("       PipelineName: %1").arg(pipelineName));
    Print(QString("       PipelineName: %1").arg(description));
    Print(QString("       PipelineName: %1").arg(createDate.toString()));
    Print(QString("       PipelineName: %1").arg(version));
    Print(QString("       PipelineName: %1").arg(level));

    Print(QString("       PipelineName: %1").arg(parentPipelines.join(",")));
    Print(QString("       PipelineName: %1").arg(completeFiles.join(",")));

    Print(QString("       PipelineName: %1").arg(dataCopyMethod));
    Print(QString("       PipelineName: %1").arg(directory));
    Print(QString("       PipelineName: %1").arg(dirStructure));
    Print(QString("       PipelineName: %1").arg(depLevel));
    Print(QString("       PipelineName: %1").arg(depDir));
    Print(QString("       PipelineName: %1").arg(depLinkType));
    Print(QString("       PipelineName: %1").arg(group));
    Print(QString("       PipelineName: %1").arg(groupType));
    Print(QString("       PipelineName: %1").arg(notes));
    Print(QString("       PipelineName: %1").arg(resultScript));
    Print(QString("       PipelineName: %1").arg(tmpDir));
    Print(QString("       PipelineName: %1").arg(flags.useTmpDir));
    Print(QString("       PipelineName: %1").arg(flags.useProfile));

    Print(QString("       PipelineName: %1").arg(clusterType));
    Print(QString("       PipelineName: %1").arg(clusterUser));
    Print(QString("       PipelineName: %1").arg(clusterQueue));
    Print(QString("       PipelineName: %1").arg(clusterSubmitHost));
    Print(QString("       PipelineName: %1").arg(maxWallTime));
    Print(QString("       PipelineName: %1").arg(submitDelay));
    Print(QString("       PipelineName: %1").arg(numConcurrentAnalyses));

}
