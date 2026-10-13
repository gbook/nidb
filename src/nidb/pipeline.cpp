/* ------------------------------------------------------------------------------
  NIDB pipeline.cpp
  Copyright (C) 2004 - 2025
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
#include <QTextStream>
#include <cmath>

namespace {
    QString FormatBool(bool value) { return value ? "true" : "false"; }

    QString FormatDateTime(const QDateTime &value) {
        return value.isValid() ? value.toString(Qt::ISODate) : "<invalid>";
    }

    QString FormatString(const QString &value) {
        return value.isEmpty() ? "<empty>" : value;
    }

    QString FormatIntList(const QList<int> &values) {
        if (values.isEmpty())
            return "[]";

        QStringList strValues;
        for (int value : values)
            strValues.append(QString::number(value));

        return QString("[%1]").arg(strValues.join(", "));
    }

    QString FormatStringList(const QStringList &values) {
        if (values.isEmpty())
            return "[]";

        return QString("[%1]").arg(values.join(", "));
    }
}

/* ---------------------------------------------------------- */
/* --------- pipeline --------------------------------------- */
/* ---------------------------------------------------------- */
pipeline::pipeline(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- Load ------------------------------------------- */
/* ---------------------------------------------------------- */
bool pipeline::Load() {

    /* check if searchPipelineRowID is valid, if so load it */
    if (searchPipelineRowID < 1) {
        /* no valid pipeline IDs */
        msg = "Missing search pipelineID";
        valid = false;
        return false;
    }

    pipelineRowID = searchPipelineRowID;
    LoadPipelineInfo();

    if (!valid) {
        //msg = "Pipeline not valid [" + msg + "]";
        return false;
    }

    /* check if searchVersion is valid, if so load it */
    if (searchPipelineVersion > -1) {
        QSqlQuery q;
        q.prepare("select * from pipeline_options where pipeline_id = :pipelineid and pipeline_version = :version");
        q.bindValue(":pipelineid", pipelineRowID);
        q.bindValue(":version", searchPipelineVersion);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msg = QString("Pipeline *version* [%1] not found").arg(searchPipelineVersion);
            valid = false;
            return false;
        }
        q.first();

        version = searchPipelineVersion;

        /* versioned variables */
        depDir = q.value("pipeline_dependencydir").toString().trimmed();
        depLevel = q.value("pipeline_dependencylevel").toString().trimmed();
        depLinkType = q.value("pipeline_deplinktype").toString().trimmed();
        //group = q.value("pipeline_group").toString().trimmed();
        groupBySubject = q.value("pipeline_groupbysubject").toBool();
        //groupType = q.value("pipeline_grouptype").toString().trimmed();
        dynamicGroupID = q.value("pipeline_dynamicgroupid").toInt();
        outputBIDS = q.value("pipeline_outputbids").toInt();
        BIDSoutputDir = q.value("pipeline_bidsoutputdir").toString();
        completeFiles = q.value("pipeline_completefiles").toString().trimmed().split(",", Qt::SkipEmptyParts);
        resultScript = q.value("pipeline_resultsscript").toString().trimmed();
        scriptComment = q.value("pipeline_scriptcomment").toString().trimmed();
        QStringList dependencyStr = q.value("pipeline_dependency").toString().trimmed().split(",", Qt::SkipEmptyParts);
        QStringList groupIDStr = q.value("pipeline_groupid").toString().trimmed().split(",", Qt::SkipEmptyParts);
        QStringList projectIDStr = q.value("pipeline_projectid").toString().trimmed().split(",", Qt::SkipEmptyParts);

        /* split the 'list' variables */
        parentIDs.clear();
        foreach (QString did, dependencyStr) {
            parentIDs.append(did.toInt());
        }
        groupIDs.clear();
        foreach (QString gid, groupIDStr) {
            groupIDs.append(gid.toInt());
        }
        projectIDs.clear();
        foreach (QString pid, projectIDStr) {
            projectIDs.append(pid.toInt());
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- LoadPipelineInfo ------------------------------- */
/* ---------------------------------------------------------- */
void pipeline::LoadPipelineInfo() {

    if (pipelineRowID < 1) {
        msg = "Invalid pipeline ID";
        valid = false;
        return;
    }

    /* get the path to the analysisroot */
    QSqlQuery q;
    q.prepare("select * from pipelines where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1) {
        msg = "Pipeline query returned no results. Possibly invalid pipeline ID or recently deleted?";
        valid = false;
        return;
    }
    q.first();

    clusterMaxWallTime = q.value("pipeline_maxwalltime").toInt();
    clusterMemory = q.value("pipeline_memory").toDouble();
    clusterNumCores = q.value("pipeline_numcores").toInt();
    clusterQueue = q.value("pipeline_queue").toString().trimmed();
    clusterSubmitHost = q.value("pipeline_submithost").toString().trimmed();
    clusterSubmitHostUser = q.value("pipeline_submithostuser").toString().trimmed();
    clusterType = q.value("pipeline_clustertype").toString().trimmed();
    clusterUser = q.value("pipeline_clusteruser").toString().trimmed();
    completeFiles = q.value("pipeline_completefiles").toString().trimmed().split(",", Qt::SkipEmptyParts);
    createDate = q.value("pipeline_createdate").toDateTime();
    dataCopyMethod = q.value("pipeline_datacopymethod").toString().trimmed();
    debug = q.value("pipeline_debug").toBool();
    depDir = q.value("pipeline_dependencydir").toString().trimmed();
    depLevel = q.value("pipeline_dependencylevel").toString().trimmed();
    depLinkType = q.value("pipeline_deplinktype").toString().trimmed();
    desc = q.value("pipeline_desc").toString().trimmed();
    dirStructure = q.value("pipeline_dirstructure").toString().trimmed();
    directory = q.value("pipeline_directory").toString().trimmed();
    dynamicGroupID = q.value("pipeline_dynamicgroupid").toInt();
    enabled = q.value("pipeline_enabled").toBool();
    group = q.value("pipeline_group").toString().trimmed();
    groupBySubject = q.value("pipeline_groupbysubject").toBool();
    //groupType = q.value("pipeline_grouptype").toString().trimmed();
    isHidden = q.value("pipeline_ishidden").toBool();
    isPrivate = q.value("pipeline_isprivate").toBool();
    lastCheck = q.value("pipeline_lastcheck").toDateTime();
    lastFinish = q.value("pipeline_lastfinish").toDateTime();
    lastStart = q.value("pipeline_laststart").toDateTime();
    level = q.value("pipeline_level").toInt();
    name = q.value("pipeline_name").toString().trimmed();
    notes = q.value("pipeline_notes").toString().trimmed();
    numConcurrentAnalysis = q.value("pipeline_numproc").toInt();
    ownerID = q.value("pipeline_admin").toInt();
    outputBIDS = q.value("pipeline_outputbids").toInt();
    BIDSoutputDir = q.value("pipeline_bidsoutputdir").toString();
    removeData = q.value("pipeline_removedata").toBool();
    resultScript = q.value("pipeline_resultsscript").toString().trimmed();
    scriptComment = q.value("pipeline_scriptcomment").toString().trimmed();
    status = q.value("pipeline_status").toString().trimmed();
    statusMessage = q.value("pipeline_statusmessage").toString().trimmed();
    submitDelay = q.value("pipeline_submitdelay").toInt();
    testing = q.value("pipeline_testing").toBool();
    tmpDir = q.value("pipeline_tmpdir").toString().trimmed();
    useProfile = q.value("pipeline_useprofile").toBool();
    useTmpDir = q.value("pipeline_usetmpdir").toBool();
    version = q.value("pipeline_version").toInt();
    QStringList dependencyStr = q.value("pipeline_dependency").toString().trimmed().split(",", Qt::SkipEmptyParts);
    QStringList groupIDStr = q.value("pipeline_groupid").toString().trimmed().split(",", Qt::SkipEmptyParts);
    QStringList projectIDStr = q.value("pipeline_projectid").toString().trimmed().split(",", Qt::SkipEmptyParts);

    /* split the 'list' variables */
    parentIDs.clear();
    foreach (QString did, dependencyStr) {
        parentIDs.append(did.toInt());
    }
    groupIDs.clear();
    foreach (QString gid, groupIDStr) {
        groupIDs.append(gid.toInt());
    }
    projectIDs.clear();
    foreach (QString pid, projectIDStr) {
        projectIDs.append(pid.toInt());
    }

    /* check if anything is missing */
    clusterSubmitHostSetting = clusterSubmitHost;
    if (clusterSubmitHost == "")
        clusterSubmitHost = n->cfg["clustersubmithost"];

    /* root analysis directory. Same logic as modulePipeline::GetAnalysisLocalPath(): 'b', an analysisdirs row ID, or the default */
    if (dirStructure == "b")
        pipelineRootDir = n->cfg["analysisdirb"];
    else if (IsInt(dirStructure)) {
        QSqlQuery q2;
        q2.prepare("select nidbpath from analysisdirs where analysisdir_id = :dir");
        q2.bindValue(":dir", dirStructure.toInt());
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        if (q2.first())
            pipelineRootDir = q2.value("nidbpath").toString().trimmed();
        else {
            pipelineRootDir = n->cfg["analysisdir"];
            n->Log(QString("analysisdir [%1] for pipeline [%2] not found in database. Using default path instead [%3]").arg(dirStructure).arg(pipelineRowID).arg(pipelineRootDir));
        }
    }
    else
        pipelineRootDir = n->cfg["analysisdir"];

    /* remove any whitespace from the queue... SGE hates whitespace */
    static const QRegularExpression re("\\s+");
    clusterQueue.replace(re,"");

    valid = true;
    msg = "Loaded pipeline details";
}


/* ---------------------------------------------------------- */
/* --------- GetParentList ---------------------------------- */
/* ---------------------------------------------------------- */
QStringList pipeline::GetParentList() {

    QStringList l;

    if (parentIDs.size() > 0) {
        for (int i=0; i< parentIDs.size(); i++) {
            pipeline p(n);
            p.searchPipelineRowID = parentIDs[i];
            p.Load();
            if (p.isValid())
                l.append(p.name);
            else
                n->Log(QString("Parent pipeline [%1] of pipeline [%2] not found: %3").arg(parentIDs[i]).arg(pipelineRowID).arg(p.msg));
        }
    }
    return l;
}


/* ---------------------------------------------------------- */
/* --------- PrintPipelineVariables ------------------------- */
/* ---------------------------------------------------------- */
QString pipeline::PrintPipelineVariables() const {
    QString output;
    QTextStream stream(&output);

    auto writeLine = [&stream](const QString &label, const QString &value) {
        stream << label << ": " << value << Qt::endl;
    };

    writeLine("BIDSoutputDir", FormatString(BIDSoutputDir));
    writeLine("clusterMaxWallTime", QString::number(clusterMaxWallTime));
    writeLine("clusterMemory", QString::number(clusterMemory));
    writeLine("clusterNumCores", QString::number(clusterNumCores));
    writeLine("clusterQueue", FormatString(clusterQueue));
    writeLine("clusterSubmitHost", FormatString(clusterSubmitHost));
    writeLine("clusterSubmitHostUser", FormatString(clusterSubmitHostUser));
    writeLine("clusterType", FormatString(clusterType));
    writeLine("clusterUser", FormatString(clusterUser));
    writeLine("completeFiles", FormatStringList(completeFiles));
    writeLine("createDate", FormatDateTime(createDate));
    writeLine("dataCopyMethod", FormatString(dataCopyMethod));
    writeLine("debug", FormatBool(debug));
    writeLine("depDir", FormatString(depDir));
    writeLine("depLevel", FormatString(depLevel));
    writeLine("depLinkType", FormatString(depLinkType));
    writeLine("desc", FormatString(desc));
    writeLine("dirStructure", FormatString(dirStructure));
    writeLine("directory", FormatString(directory));
    writeLine("dynamicGroupID", QString::number(dynamicGroupID));
    writeLine("enabled", FormatBool(enabled));
    writeLine("group", FormatString(group));
    writeLine("groupBySubject", FormatBool(groupBySubject));
    writeLine("groupIDs", FormatIntList(groupIDs));
    writeLine("isHidden", FormatBool(isHidden));
    writeLine("isPrivate", FormatBool(isPrivate));
    writeLine("lastCheck", FormatDateTime(lastCheck));
    writeLine("lastFinish", FormatDateTime(lastFinish));
    writeLine("lastStart", FormatDateTime(lastStart));
    writeLine("level", QString::number(level));
    writeLine("name", FormatString(name));
    writeLine("notes", FormatString(notes));
    writeLine("numConcurrentAnalysis", QString::number(numConcurrentAnalysis));
    writeLine("outputBIDS", FormatBool(outputBIDS));
    writeLine("ownerID", QString::number(ownerID));
    writeLine("parentIDs", FormatIntList(parentIDs));
    writeLine("pipelineRootDir", FormatString(pipelineRootDir));
    writeLine("projectIDs", FormatIntList(projectIDs));
    writeLine("removeData", FormatBool(removeData));
    writeLine("resultScript", FormatString(resultScript));
    writeLine("scriptComment", FormatString(scriptComment));
    writeLine("status", FormatString(status));
    writeLine("statusMessage", FormatString(statusMessage));
    writeLine("submitDelay", QString::number(submitDelay));
    writeLine("testing", FormatBool(testing));
    writeLine("tmpDir", FormatString(tmpDir));
    writeLine("useProfile", FormatBool(useProfile));
    writeLine("useTmpDir", FormatBool(useTmpDir));
    writeLine("version", QString::number(version));

    return output;
}

/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelPipeline pipeline::GetSquirrelObject(QString databaseUUID) {
    squirrelPipeline s(databaseUUID);

    //s.ClusterSubmitHostUser = clusterSubmitHostUser;
    s.ClusterEngine = clusterType;
    s.ClusterMaxWallTime = clusterMaxWallTime;
    s.ClusterMemory = int(ceil(clusterMemory)); /* squirrel stores whole GB. Round up, as the slurm job file does */
    s.ClusterNumberConcurrentAnalyses = numConcurrentAnalysis;
    s.ClusterNumberCores = clusterNumCores;
    s.ClusterQueue = clusterQueue;
    s.ClusterSubmitDelay = submitDelay;
    s.ClusterSubmitHost = clusterSubmitHostSetting; /* not this server's default, which may be wrong for the importing instance */
    s.ClusterUser = clusterUser;
    s.PipelineAnalysisLevel = level;
    s.PipelineCompleteFiles = completeFiles;
    s.PipelineCreateDate = createDate;
    s.PipelineDescription = desc;
    s.PipelineDirectory = directory;
    s.PipelineDirectoryStructure = dirStructure;
    s.PipelineName = name;
    s.PipelineNotes = notes;
    s.PipelineResultScript = resultScript;
    s.PipelineVersion = version;
    s.SearchDependencyLevel = depLevel;
    s.SearchDependencyLinkType = depLinkType;
    s.SearchGroup = group;
    //s.SearchGroupType = groupType;
    s.SearchParentPipelines = GetParentList();
    s.SetupBidsDirectory = BIDSoutputDir;
    s.SetupWriteBids = outputBIDS ? "1" : "0";
    s.SetupDataCopyMethod = dataCopyMethod;
    s.SetupDependencyDirectory = depDir;
    s.SetupTempDirectory = tmpDir;
    s.flags.SetupUseProfile = useProfile;
    s.flags.SetupUseTempDirectory = useTmpDir;

    /* dataSteps */
    QSqlQuery q;
    q.prepare("select * from pipeline_data_def where pipeline_id = :pipelineid and pipeline_version = :version order by pdd_order + 0");
    q.bindValue(":pipelineid", pipelineRowID);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            dataStep d;
            d.ExportBehavioralDirectoryFormat = q.value("pdd_behformat").toString();
            d.ExportBehavioralDirectoryName = q.value("pdd_behdir").toString();
            d.ExportDataFormat = q.value("pdd_dataformat").toString();
            d.ExportSubDirectoryName = q.value("pdd_location").toString();
            d.SearchAssociationType = q.value("pdd_assoctype").toString();
            d.SearchDataLevel = q.value("pdd_level").toString();
            d.SearchImageType = q.value("pdd_imagetype").toString();
            d.SearchModality = q.value("pdd_modality").toString();
            d.SearchNumberBOLDreps = q.value("pdd_numboldreps").toString();
            d.SearchProtocol = q.value("pdd_protocol").toString();
            d.SearchSeriesCriteria = q.value("pdd_seriescriteria").toString();
            d.StepNumber = q.value("pdd_order").toInt();
            d.flags.ExportGzip = q.value("pdd_gzip").toBool();
            d.flags.ExportPreserveSeriesNumber = q.value("pdd_preserveseries").toBool();
            d.flags.ExportWritePhaseDirectory = q.value("pdd_usephasedir").toBool();
            d.flags.ExportWriteSeriesDirectory = q.value("pdd_useseries").toBool();
            d.flags.IsEnabled = q.value("pdd_enabled").toBool();
            d.flags.IsOptional = q.value("pdd_optional").toBool();
            d.flags.IsPrimaryProtocol = q.value("pdd_isprimaryprotocol").toBool();
            //d.numImagesCriteria = q.value("pdd_numimagescriteria").toString();

            s.dataSteps.append(d);
        }
    }

    /* scripts (required) */
    s.PipelinePrimaryScript = GetScript(false, version);
    s.PipelineSecondaryScript = GetScript(true, version);

    return s;
}


/* ---------------------------------------------------------- */
/* --------- GetScript -------------------------------------- */
/* ---------------------------------------------------------- */
/* the main (supplement = false) or supplement script, one step per line, in the same format as
   RenderStepLine() in pipelines.php, so the script can be pasted back into the pipeline editor:
   disabled steps start with '#', and {NOLOG} and the description go in a trailing comment */
QString pipeline::GetScript(bool supplement, int v) {
    QString script;

    QSqlQuery q;
    q.prepare("select * from pipeline_steps where pipeline_id = :pipelineid and pipeline_version = :version and ps_supplement = :supplement order by ps_order asc");
    q.bindValue(":pipelineid", pipelineRowID);
    q.bindValue(":version", v);
    q.bindValue(":supplement", supplement ? 1 : 0);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        QString command = q.value("ps_command").toString();
        QString description = q.value("ps_description").toString();
        bool enabled = q.value("ps_enabled").toBool();
        bool logged = q.value("ps_logged").toBool();

        /* disabled steps are stored with their leading '#', but older rows may not have it */
        QString line = command;
        if (!enabled && !command.trimmed().startsWith("#"))
            line = "#" + command;

        QString comment = QString("%1 %2").arg(logged ? "" : "{NOLOG}").arg(description).trimmed();
        if (comment != "")
            line += "     # " + comment;

        script.append(line + "\n");
    }

    return script;
}
