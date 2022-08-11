/* ------------------------------------------------------------------------------
  Squirrel pipeline.cpp
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
    json["completeFiles"] = QJsonArray::fromStringList(completeFiles.split(","));

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

	//AppendJSONParents(json, parentDependencyIDs, path);
	//AppendJSONDataSpec(json);
//	AppendJSONScripts(json);

    /* write all pipeline info to path */
    QString m;
    QString pipelinepath = QString("%1/%2").arg(path).arg(pipelineName);
    if (!MakePath(pipelinepath, m))
    //	n->WriteLog("Created path [" + pipelinepath + "]");
    //else
        Print("Error creating path [" + pipelinepath + "] because of [" + m + "]");

    QByteArray j = QJsonDocument(json).toJson();
    QFile fout(QString("%1/%2/pipeline.json").arg(path).arg(pipelineName));
    if (fout.open(QIODevice::WriteOnly))
        fout.write(j);
    else
        Print("Error writing file [" + QString("%1/%2/pipeline.json").arg(path).arg(pipelineName) + "]");

    /* return small JSON object */
    return json;
}


/* ---------------------------------------------------------- */
/* --------- GetFormattedScripts ---------------------------- */
/* ---------------------------------------------------------- */
//bool pipeline::GetFormattedScripts(QString &primaryFile, QString &secondaryFile) {
//	//QString primaryFile, secondaryFile;

//    /* write the primary script file */
//    for(int i=0; i<primaryScript.size(); i++) {
//        QString line = QString("%1 #%2").arg(primaryScript[i].command).arg(primaryScript[i].description);

//        if (!primaryScript[i].flags.checkin) line = line + "{NOCHECKIN}";
//        if (!primaryScript[i].flags.logged) line = line + "{NOLOG}";
//        if (!primaryScript[i].flags.enabled) line = "#" + line;

//        primaryFile += line + "\n";
//    }
//    QString pfFilepath = path + "/primaryScript.sh";
//    QFile pf(pfFilepath);
//    if (pf.open(QIODevice::WriteOnly)) {

//        QTextStream stream(&pf);
//        stream << primaryFile;

//        /* write the secondary script file */
//        for(int i=0; i<secondaryFile.size(); i++) {
//            QString line = QString("%1 #%2").arg(secondaryScript[i].command).arg(secondaryScript[i].description);

//            if (!secondaryScript[i].flags.checkin) line = line + "{NOCHECKIN}";
//            if (!secondaryScript[i].flags.logged) line = line + "{NOLOG}";
//            if (!secondaryScript[i].flags.enabled) line = "#" + line;

//            secondaryFile += line + "\n";
//        }

//        QString sfFilepath = path + "/secondaryScript.sh";
//        QFile sf(sfFilepath);
//        if (sf.open(QIODevice::WriteOnly)) {
//            QTextStream stream(&sf);
//            stream << secondaryFile;
//            return true;
//        }
//        else {
//            Print("Unable to write secondary script [" + sfFilepath + "]");
//            return false;
//        }
//    }
//    else {
//        Print("Unable to write primary script [" + pfFilepath + "]");
//        return false;
//    }
//}


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
	Print(QString("       PipelineName: %1").arg(completeFiles));

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
