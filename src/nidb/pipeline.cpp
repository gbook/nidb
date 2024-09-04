/* ------------------------------------------------------------------------------
  NIDB pipeline.cpp
  Copyright (C) 2004 - 2024
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

    clusterMaxWallTime = q.value("pipeline_maxwalltime").toInt();
    clusterMemory = q.value("pipeline_memory").toDouble();
    clusterNumCores = q.value("pipeline_numcores").toInt();
    clusterQueue = q.value("pipeline_queue").toString().trimmed();
    clusterSubmitHost = q.value("pipeline_submithost").toString().trimmed();
    clusterSubmitHostUser = q.value("pipeline_submithostuser").toString().trimmed();
    clusterType = q.value("pipeline_clustertype").toString().trimmed();
    clusterUser = q.value("pipeline_clusteruser").toString().trimmed();
    completeFiles = q.value("pipeline_desc").toString().trimmed().split(",", Qt::SkipEmptyParts);
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
    groupType = q.value("pipeline_grouptype").toString().trimmed();
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
    removeData = q.value("pipeline_removedata").toBool();
    resultScript = q.value("pipeline_resultsscript").toString().trimmed();
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

    /* split the 'list' variables */
    foreach (QString did, dependencyStr) {
        parentIDs.append(did.toInt());
    }
    foreach (QString gid, groupIDStr) {
        groupIDs.append(gid.toInt());
    }

    /* check if anything is missing */
    if (clusterSubmitHost == "")
        clusterSubmitHost = n->cfg["clustersubmithost"];

    if (dirStructure == "b")
        pipelineRootDir = n->cfg["analysisdirb"];
    else {
        pipelineRootDir = n->cfg["analysisdir"];
    }

    /* remove any whitespace from the queue... SGE hates whitespace */
    static const QRegularExpression re("\\s+");
    clusterQueue.replace(re,"");

    isValid = true;
    msg = "Loaded pipeline details";
}


/* ---------------------------------------------------------- */
/* --------- GetParentList ---------------------------------- */
/* ---------------------------------------------------------- */
QStringList pipeline::GetParentList() {

    QStringList l;

    if (parentIDs.size() > 0) {
        for (int i=0; i< parentIDs.size(); i++) {
            pipeline p(parentIDs[i], n);
            l.append(p.name);
        }
    }
    return l;
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelPipeline pipeline::GetSquirrelObject() {
    squirrelPipeline s;

    s.ClusterMaxWallTime = clusterMaxWallTime;
    s.ClusterMemory = clusterMemory;
    s.ClusterNumberCores = clusterNumCores;
    s.ClusterQueue = clusterQueue;
    s.ClusterSubmitHost = clusterSubmitHost;
    //s.ClusterSubmitHostUser = clusterSubmitHostUser;
    s.ClusterType = clusterType;
    s.ClusterUser = clusterUser;
    s.CompleteFiles = completeFiles;
    s.CreateDate = createDate;
    s.DataCopyMethod = dataCopyMethod;
    s.DependencyDirectory = depDir;
    s.DependencyLevel = depLevel;
    s.DependencyLinkType = depLinkType;
    s.Description = desc;
    s.Directory = directory;
    s.DirectoryStructure = dirStructure;
    s.Group = group;
    s.GroupType = groupType;
    s.Level = level;
    s.Notes = notes;
    s.NumberConcurrentAnalyses = numConcurrentAnalysis;
    s.ParentPipelines = GetParentList();
    s.PipelineName = name;
    s.ResultScript = resultScript;
    s.SubmitDelay = submitDelay;
    s.TempDirectory = tmpDir;
    s.Version = version;
    s.flags.UseProfile = useProfile;
    s.flags.UseTempDirectory = useTmpDir;

    /* dataSteps */
    QSqlQuery q;
    q.prepare("select * from pipeline_data_def where pipeline_id = :pipelineid and pipeline_version = :version order by pdd_order + 0");
    q.bindValue(":pipelineid",pipelineid);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            dataStep d;
            d.AssociationType = q.value("pdd_assoctype").toString();
            d.BehavioralDirectory = q.value("pdd_behdir").toString();
            d.BehavioralFormat = q.value("pdd_behformat").toString();
            d.DataFormat = q.value("pdd_dataformat").toString();
            d.ImageType = q.value("pdd_imagetype").toString();
            d.Location = q.value("pdd_location").toString();
            d.Modality = q.value("pdd_modality").toString();
            d.NumberBOLDreps = q.value("pdd_numboldreps").toString();
            d.Order = q.value("pdd_order").toInt();
            d.Protocol = q.value("pdd_protocol").toString();
            d.SeriesCriteria = q.value("pdd_seriescriteria").toString();
            d.flags.Enabled = q.value("pdd_enabled").toBool();
            d.flags.Gzip = q.value("pdd_gzip").toBool();
            d.flags.Optional = q.value("pdd_optional").toBool();
            d.flags.PreserveSeries = q.value("pdd_preserveseries").toBool();
            d.flags.PrimaryProtocol = q.value("pdd_isprimaryprotocol").toBool();
            d.flags.UsePhaseDirectory = q.value("pdd_usephasedir").toBool();
            d.flags.UseSeries = q.value("pdd_useseries").toBool();
            //d. = q.value("pdd_level").toString();
            //d.numImagesCriteria = q.value("pdd_numimagescriteria").toString();

            s.dataSteps.append(d);
        }
    }

    /* scripts (required) */
    s.PrimaryScript = GetPrimaryScript();
    s.SecondaryScript = GetSecondaryScript();

    return s;
}


/* ---------------------------------------------------------- */
/* --------- GetPrimaryScript ------------------------------- */
/* ---------------------------------------------------------- */
QString pipeline::GetPrimaryScript() {
    QString script;

    /* get primary script from the pipeline_steps */
    QSqlQuery q;
    q.prepare("select * from pipeline_steps where pipeline_id = :pipelineid and pipeline_version = :version and ps_supplement = 0 order by ps_order asc");
    q.bindValue(":pipelineid", pipelineid);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            QString str;
            QString command = q.value("ps_command").toString();
            QString description = q.value("ps_description").toString();
            bool enable = q.value("ps_enabled").toBool();
            str = QString(command + " #" + description);
            if (!enable)
                str = QString("#" + str);

            str += "\n";
            script.append(str);
        }
    }

    return script;
}


/* ---------------------------------------------------------- */
/* --------- GetSecondaryScript ----------------------------- */
/* ---------------------------------------------------------- */
QString pipeline::GetSecondaryScript() {
    QString script;

    /* get primary script from the pipeline_steps */
    QSqlQuery q;
    q.prepare("select * from pipeline_steps where pipeline_id = :pipelineid and pipeline_version = :version and ps_supplement = 1 order by ps_order asc");
    q.bindValue(":pipelineid", pipelineid);
    q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            QString str;
            QString command = q.value("ps_command").toString();
            QString description = q.value("ps_description").toString();
            bool enable = q.value("ps_enabled").toBool();
            str = QString(command + " #" + description);
            if (!enable)
                str = QString("#" + str);

            str += "\n";
            script.append(str);
        }
    }

    return script;
}
