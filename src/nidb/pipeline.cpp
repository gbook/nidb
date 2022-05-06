/* ------------------------------------------------------------------------------
  NIDB pipeline.cpp
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

#include "pipeline.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- pipeline --------------------------------------- */
/* ---------------------------------------------------------- */
pipeline::pipeline(int id, nidb *a)
{
    n = a;
    pipelineid = id;
    LoadPipelineInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadPipelineInfo ------------------------------- */
/* ---------------------------------------------------------- */
void pipeline::LoadPipelineInfo() {

    if (pipelineid < 1) {
        msg = "Invalid pipeline ID";
        isValid = false;
        return;
    }

    /* get the path to the analysisroot */
    QSqlQuery q;
    q.prepare("select * from pipelines where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1) {
        msg = "Pipeline query returned no results. Possibly invalid pipeline ID or recently deleted?";
        isValid = false;
        return;
    }
    q.first();

    name = q.value("pipeline_name").toString().trimmed();
    desc = q.value("pipeline_desc").toString().trimmed();
    ownerID = q.value("pipeline_admin").toInt();
    createDate = q.value("pipeline_createdate").toDateTime();
    level = q.value("pipeline_level").toInt();
    group = q.value("pipeline_group").toString().trimmed();
    directory = q.value("pipeline_directory").toString().trimmed();
    dirStructure = q.value("pipeline_dirstructure").toString().trimmed();
    useTmpDir = q.value("pipeline_usetmpdir").toBool();
    tmpDir = q.value("pipeline_tmpdir").toString().trimmed();
    foreach (QString did, q.value("pipeline_dependency").toString().trimmed().split(",", Qt::SkipEmptyParts)) {
        parentDependencyIDs.append(did.toInt());
    }

    depLevel = q.value("pipeline_dependencylevel").toString().trimmed();
    depDir = q.value("pipeline_dependencydir").toString().trimmed();
    depLinkType = q.value("pipeline_deplinktype").toString().trimmed();
    foreach (QString gid, q.value("pipeline_groupid").toString().trimmed().split(",", Qt::SkipEmptyParts)) {
        groupIDs.append(gid.toInt());
    }

    groupType = q.value("pipeline_grouptype").toString().trimmed();
    groupBySubject = q.value("pipeline_groupbysubject").toBool();
    dynamicGroupID = q.value("pipeline_dynamicgroupid").toInt();
    status = q.value("pipeline_status").toString().trimmed();
    statusMessage = q.value("pipeline_statusmessage").toString().trimmed();
    lastStart = q.value("pipeline_laststart").toDateTime();
    lastFinish = q.value("pipeline_lastfinish").toDateTime();
    lastCheck = q.value("pipeline_lastcheck").toDateTime();
    completeFiles = q.value("pipeline_desc").toString().trimmed().split(",", Qt::SkipEmptyParts);
    numConcurrentAnalysis = q.value("pipeline_numproc").toInt();
    queue = q.value("pipeline_queue").toString().trimmed();
    submitHost = q.value("pipeline_submithost").toString().trimmed();
    clusterType = q.value("pipeline_clustertype").toString().trimmed();
    clusterUser = q.value("pipeline_clusteruser").toString().trimmed();
    maxWallTime = q.value("pipeline_maxwalltime").toInt();
    submitDelay = q.value("pipeline_submitdelay").toInt();
    dataCopyMethod = q.value("pipeline_datacopymethod").toString().trimmed();
    notes = q.value("pipeline_notes").toString().trimmed();
    useProfile = q.value("pipeline_useprofile").toBool();
    removeData = q.value("pipeline_removedata").toBool();
    resultScript = q.value("pipeline_resultsscript").toString().trimmed();
    enabled = q.value("pipeline_enabled").toBool();
    testing = q.value("pipeline_testing").toBool();
    isPrivate = q.value("pipeline_isprivate").toBool();
    isHidden = q.value("pipeline_ishidden").toBool();
    debug = q.value("pipeline_debug").toBool();
    version = q.value("pipeline_version").toInt();

    /* check if anything is missing */
    if (submitHost == "")
        submitHost = n->cfg["clustersubmithost"];

    if (dirStructure == "b")
        pipelineRootDir = n->cfg["analysisdirb"];
    else {
        pipelineRootDir = n->cfg["analysisdir"];
    }

    /* remove any whitespace from the queue... SGE hates whitespace */
    static const QRegularExpression re("\\s+");
    queue.replace(re,"");

    isValid = true;
    msg = "Loaded pipeline details";
}


/* ---------------------------------------------------------- */
/* --------- GetJSONObject ---------------------------------- */
/* ---------------------------------------------------------- */
/* if path is specified, write the full JSON object to that
 * path and return a small JSON object */
QJsonObject pipeline::GetJSONObject(QString path) {
    QJsonObject json, jsonSmall, jsonLarge;

    jsonSmall["name"] = name;
    jsonSmall["desc"] = desc;
    jsonSmall["createDate"] = createDate.toString();
    jsonSmall["level"] = level;

    jsonLarge["name"] = name;
    jsonLarge["desc"] = desc;
    jsonLarge["createDate"] = createDate.toString();
    jsonLarge["level"] = level;
    jsonLarge["group"] = group;
    jsonLarge["directory"] = directory;
    jsonLarge["dirStructure"] = dirStructure;
    jsonLarge["useTmpDir"] = useTmpDir;
    jsonLarge["tmpDir"] = tmpDir;
    jsonLarge["depLevel"] = depLevel;
    jsonLarge["depDir"] = depDir;
    jsonLarge["depLinkType"] = depLinkType;
    jsonLarge["groupType"] = groupType;
    jsonLarge["completeFiles"] = QJsonArray::fromStringList(completeFiles);
    jsonLarge["numConcurrentAnalysis"] = numConcurrentAnalysis;
    jsonLarge["queue"] = queue;
    jsonLarge["submitHost"] = submitHost;
    jsonLarge["clusterType"] = clusterType;
    jsonLarge["clusterUser"] = clusterUser;
    jsonLarge["maxWallTime"] = maxWallTime;
    jsonLarge["submitDelay"] = submitDelay;
    jsonLarge["dataCopyMethod"] = dataCopyMethod;
    jsonLarge["notes"] = notes;
    jsonLarge["useProfile"] = useProfile;
    jsonLarge["resultScript"] = resultScript;
    jsonLarge["version"] = version;

    AppendJSONParents(jsonLarge, parentDependencyIDs, path);
    AppendJSONDataSpec(jsonLarge, path);
    AppendJSONScripts(jsonLarge, path);

    if (path == "") {
        /* return full JSON object */
        /* append all pipeline info */
        return jsonLarge;
    }
    else {
        /* return small JSON object */

        /* write all pipeline info to path */
        QByteArray j = QJsonDocument(jsonLarge).toJson();
        QFile fout(QString("%1/pipelines/%2/pipeline.json").arg(path).arg(name));
        fout.open(QIODevice::WriteOnly);
        fout.write(j);

        return jsonSmall;
    }
}


/* ---------------------------------------------------------- */
/* --------- AppendJSONParents ------------------------------ */
/* ---------------------------------------------------------- */
void pipeline::AppendJSONParents(QJsonObject &obj, QList<int> parentIDs, QString path) {

    if (parentIDs.size() > 0) {
        QJsonArray JSONparents;
        for (int i=0; i< parentIDs.size(); i++) {
            pipeline p(parentIDs[i], n);
            JSONparents.append(p.GetJSONObject(path));
        }
        obj["parents"] = JSONparents;
    }

}


/* ---------------------------------------------------------- */
/* --------- AppendJSONDataSpec ----------------------------- */
/* ---------------------------------------------------------- */
void pipeline::AppendJSONDataSpec(QJsonObject &obj) {

    QSqlQuery q;
    q.prepare("select * from pipeline_data_def where pipeline_id = :pipelineid and pipeline_version = :version order by pdd_order + 0");
    q.bindValue(":pipelineid",pipelineid);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        QJsonArray JSONdata;
        while (q.next()) {
            QJsonObject JSONline;
            JSONline["order"] = q.value("pdd_order").toInt();
            JSONline["primaryProtocol"] = q.value("pdd_isprimaryprotocol").toBool();
            JSONline["seriesCriteria"] = q.value("pdd_seriescriteria").toString();
            JSONline["protocol"] = q.value("pdd_protocol").toString();
            JSONline["modality"] = q.value("pdd_modality").toString();
            JSONline["dataFormat"] = q.value("pdd_dataformat").toString();
            JSONline["imageType"] = q.value("pdd_imagetype").toString();
            JSONline["gzip"] = q.value("pdd_gzip").toBool();
            JSONline["location"] = q.value("pdd_location").toString();
            JSONline["useSeries"] = q.value("pdd_useseries").toBool();
            JSONline["preserveSeries"] = q.value("pdd_preserveseries").toBool();
            JSONline["usePhaseDir"] = q.value("pdd_usephasedir").toBool();
            JSONline["behFormat"] = q.value("pdd_behformat").toString();
            JSONline["behDir"] = q.value("pdd_behdir").toString();
            JSONline["numBOLDreps"] = q.value("pdd_numboldreps").toInt();
            JSONline["enabled"] = q.value("pdd_enabled").toBool();
            JSONline["associatonType"] = q.value("pdd_assoctype").toString();
            JSONline["optional"] = q.value("pdd_optional").toBool();
            JSONline["level"] = q.value("pdd_level").toString();
            JSONline["numImagesCriteria"] = q.value("pdd_numimagescriteria").toInt();
            JSONdata.append(JSONline);
        }
        obj["dataSpec"] = JSONdata;
    }
}


/* ---------------------------------------------------------- */
/* --------- AppendJSONScripts ------------------------------ */
/* ---------------------------------------------------------- */
void pipeline::AppendJSONScripts(QJsonObject &obj) {

    QSqlQuery q;

    q.prepare("select * from pipeline_steps where pipeline_id = :pipelineid and pipeline_version = :version and ps_supplement <> 1 order by ps_order + 0");
    q.bindValue(":pipelineid",pipelineid);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        QJsonArray JSONdata;
        while (q.next()) {
            QJsonObject JSONline;
            JSONline["order"] = q.value("ps_order").toInt();
            JSONline["desc"] = q.value("ps_description").toString();
            JSONline["command"] = q.value("ps_command").toString();
            JSONline["workingdir"] = q.value("ps_workingdir").toString();
            JSONline["enabled"] = q.value("ps_enabled").toBool();
            JSONline["logged"] = q.value("ps_logged").toBool();
            JSONdata.append(JSONline);
        }
        obj["primaryScript"] = JSONdata;
    }

    q.prepare("select * from pipeline_steps where pipeline_id = :pipelineid and pipeline_version = :version and ps_supplement = 1 order by ps_order + 0");
    q.bindValue(":pipelineid",pipelineid);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        QJsonArray JSONdata;
        while (q.next()) {
            QJsonObject JSONline;
            JSONline["order"] = q.value("ps_order").toInt();
            JSONline["desc"] = q.value("ps_description").toString();
            JSONline["command"] = q.value("ps_command").toString();
            JSONline["workingdir"] = q.value("ps_workingdir").toString();
            JSONline["enabled"] = q.value("ps_enabled").toBool();
            JSONline["logged"] = q.value("ps_logged").toBool();
            JSONdata.append(JSONline);
        }
        obj["supplementScript"] = JSONdata;
    }
}
