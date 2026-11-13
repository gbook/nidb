/* ------------------------------------------------------------------------------
  NIDB moduleQC.cpp
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

#include "moduleQC.h"
#include <QSqlQuery>
#include "archiveio.h"

moduleQC::moduleQC()
{

}

/* ---------------------------------------------------------- */
/* --------- moduleQC --------------------------------------- */
/* ---------------------------------------------------------- */
moduleQC::moduleQC(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleQC -------------------------------------- */
/* ---------------------------------------------------------- */
moduleQC::~moduleQC()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleQC::Run() {
    n->Log("Entering the QC module");

    int ret(0);

    /* get list of active modules */
    QSqlQuery q;
    q.prepare("select * from qc_modules where isenabled = 1");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        int numdone = 0;
        while (q.next()) {
            int moduleid = q.value("qcmodule_id").toInt();
            QString qcmodulename = q.value("module_name").toString();
            QString modality = q.value("modality").toString().toLower();

            n->Log(QString("*** Finding all series for QC module [%1][%2] ***").arg(qcmodulename).arg(modality));

            /* look through DB for all series (of this modality) that don't have an associated QCdata row */
            QSqlQuery q2;
            q2.prepare(QString("select %1series_id 'seriesid' from %1_series where %1series_id not in (select series_id from qc_moduleseries where qcmodule_id = :moduleid) order by series_datetime desc").arg(modality));
            q2.bindValue(":moduleid", moduleid);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            if (q2.size() > 0) {
                while (q2.next()) {
                    ret = 1;
                    int seriesid = q2.value("seriesid").toInt();

                    n->ModuleRunningCheckIn();

                    /* check if this series has an mr_qa row */
                    //QSqlQuery q3;
                    //q3.prepare("select mrseries_id from mr_qa where mrseries_id = :seriesid");
                    //q3.bindValue(":seriesid", seriesid);
                    //n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    //if (q3.size() > 0) {
                        QC(moduleid, seriesid, modality);
                        numdone++;

                        /* check if this module should be running now or not */
                        if (!n->ModuleCheckIfActive()) {
                            n->Log("Not supposed to be running right now");
                            return 0;
                        }

                        /* give this thing a break every so often */
                        if (numdone >= 5)
                            break;

                        QThread::sleep(1); // sleep for 1 sec
                    //}
                    //else {
                    //    n->Log(QString("Skipping this MR series [%1] because it does not have an mr_qa row yet... QC needs the 3D/4D information from the mr_qa script first").arg(seriesid));
                    //}
                }
                n->Log("Finished checking for MR series that dont have a QC row");
            }
            else {
                n->Log("Nothing to do");
            }

            n->Log(QString("*** Finished module [%1][%2] ***").arg(qcmodulename).arg(modality));

        }
        n->Log("Finished all modules");
    }
    else {
        n->Log("No QC modules exist (in the database)!");
    }

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- QC --------------------------------------------- */
/* ---------------------------------------------------------- */
bool moduleQC::QC(int moduleid, int seriesid, QString modality) {

    QElapsedTimer timer;
    timer.start();

    QString datatype;
    QString entryPoint;
    QString modulename;
    qint64 clusterRowID(0);

    QSqlQuery q;
    q.prepare("select * from qc_modules where qcmodule_id = :moduleid");
    q.bindValue(":moduleid",moduleid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        clusterRowID = q.value("cluster_id").toLongLong();
        datatype = q.value("datatype").toString();
        entryPoint = q.value("entrypoint").toString();
        modulename = q.value("module_name").toString();
    }
    else
        return false;

    /* get the series info */
    series s(seriesid, modality.toUpper(), n);
    if (!s.isValid) {
        n->Log("Series was not valid: [" + s.msg + "]");
        return false;
    }

    int seriesnum = s.seriesnum;
    int studynum = s.studynum;
    QString uid = s.uid;
    //QString datatype = s.datatype;

    int qcmoduleseriesid(0);

    n->Log(QString("-------------- Working on series [%1-%2-%3] for QC module [%4] --------------").arg(uid).arg(studynum).arg(seriesnum).arg(modulename));
    // check if this qc_moduleseries row exists
    q.prepare("select * from qc_moduleseries where series_id = :seriesid and modality = :modality and qcmodule_id = :moduleid");
    q.bindValue(":seriesid", seriesid);
    q.bindValue(":modality", modality);
    q.bindValue(":moduleid", moduleid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        /* another row exists */
        n->Log(QString("This series already has a row in the qc_moduleseries table [%1, %2]").arg(moduleid).arg(seriesid));
        return false;
    }
    else {
        /* insert a blank row for this qc_moduleseries and get the row ID */
        QSqlQuery q2;
        q2.prepare("insert ignore into qc_moduleseries (qcmodule_id, series_id, modality) values (:moduleid, :seriesid, :modality)");
        q2.bindValue(":seriesid", seriesid);
        q2.bindValue(":modality", modality);
        q2.bindValue(":moduleid", moduleid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        qcmoduleseriesid = q2.lastInsertId().toInt();

        n->Log(QString("This series did not have a row in the module/series table [%1, %2]. New moduleseries row created [%3]").arg(moduleid).arg(seriesid).arg(qcmoduleseriesid));
    }

    QString qcpath = QString("%1/%2/%3/%4/qc/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum).arg(modulename);
    QString outputPath = qcpath + "/output";
    QString m;
    if (!MakePath(qcpath, m)) {
        n->Log("Unable to create directory [" + qcpath + "] because of error [" + m + "]");
        return false;
    }
    n->Log("Created qc path [" + qcpath + "]");

    /* Cluster mode is disabled for QC modules: SubmitToCluster() can write and submit a job, but nothing
       exports the data beforehand or reads the results back afterwards. Until that exists, always run locally.
       (clusterRowID is kept for when SubmitToCluster() is re-enabled) */
    Q_UNUSED(clusterRowID);
    if (n->cfg["usecluster"].toInt())
        n->Log(QString("[%1] Cluster mode is not supported for QC modules yet. Running locally instead").arg(modulename), __FUNCTION__);

    n->Log("Running QC module locally (not on cluster)");

    if ((s.bidsMapping.bidsEntity == "unknown") || (s.bidsMapping.bidsSuffix == "unknown")) {
        n->Log(QString("BIDS mapping not specified, not running QC module %1 on series %2-%3-%4").arg(modulename).arg(uid).arg(studynum).arg(seriesnum));
    }
    else {

        /* example path: /nidb/data/archive/S5479BEK/12/18/qc/mriqc_docker/output/sub-S5479BEK/ses-12/anat/sub-S5479BEK_ses-12_T1w.json */
        QString resultsJsonPath;
        if (s.bidsMapping.bidsTask == "") {
            resultsJsonPath = QString("%1/output/sub-%2/ses-%3/%4/sub-%2_ses-%3_%5.json").arg(qcpath).arg(uid).arg(studynum).arg(s.bidsMapping.bidsEntity).arg(s.bidsMapping.bidsSuffix);
        }
        else {
            resultsJsonPath = QString("%1/output/sub-%2/ses-%3/%4/sub-%2_ses-%3_task-%6_%5.json").arg(qcpath).arg(uid).arg(studynum).arg(s.bidsMapping.bidsEntity).arg(s.bidsMapping.bidsSuffix).arg(s.bidsMapping.bidsTask);
        }

        /* check if the JSON file already exists */
        n->Log("Checking for existing JSON file [" + resultsJsonPath + "]");
        if (QFile::exists(resultsJsonPath)) {
            n->Log(QString("JSON file [%1] already exists, not running mriqc again").arg(resultsJsonPath));
        }
        else {
            n->Log(QString("JSON file [%1] does not exist. Running mriqc").arg(resultsJsonPath));
            /* download the data to qcpath */
            ExportSeries(seriesid, modality, ExportFormat::BIDS, qcpath + "/data");
            MakePath(outputPath, m);
            QString systemstring = QString("%1 %2/data %3/output %4").arg(entryPoint).arg(qcpath).arg(qcpath).arg(uid);
            n->Log("Running the QC module [" + systemstring + "]");
            n->Log(SystemCommand(systemstring));
            n->Log("Finished running the QC module locally");
        }

        /* parse the mriqc results */
        QFile file(resultsJsonPath);
        if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
            n->Log("Could not open file [" + resultsJsonPath + "]. Error: " + file.errorString());
        }
        else {
            n->Log("Reading output file [" + resultsJsonPath + "]");
        }
        QString jsonStr = file.readAll();
        file.close();

        QJsonDocument d = QJsonDocument::fromJson(jsonStr.toUtf8());

        QJsonObject json = d.object();
        n->Log(QString("JSON file contains %1 keys").arg(json.keys().size()));
        int numMetricsInserted(0);
        foreach(const QString& key, json.keys()) {
            QJsonValue value = json.value(key);

            QString valType;
            if (value.isDouble()) {
                valType = "number";
                n->Debug(QString("%1 -> %2").arg(key).arg(value.toDouble()));
            }
            else if (value.isString()) {
                valType = "text";
                n->Debug(QString("%1 -> %2").arg(key).arg(value.toString()));
            }
            else {
                n->Debug(QString("Unrecognized value type for %1").arg(key));
                continue;
            }

            /* insert qc name */
            int qcNameRowID(-1);
            q.prepare("select qcresultname_id from qc_resultnames where qcresult_name = :name");
            q.bindValue(":name", key);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                qcNameRowID = q.value("qcresultname_id").toInt();
            }
            else {
                q.finish();
                q.prepare("insert ignore into qc_resultnames (qcresult_name, qcresult_type) values (:name, :type)");
                q.bindValue(":name", key);
                q.bindValue(":type", valType);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                qcNameRowID = q.lastInsertId().toInt();
            }

            if (value.isDouble()) {
                q.finish();
                q.prepare("insert ignore into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuenumber) values (:qcmoduleseriesid, :resultnameid, :value)");
                q.bindValue(":qcmoduleseriesid", qcmoduleseriesid);
                q.bindValue(":resultnameid", qcNameRowID);
                q.bindValue(":value", value.toDouble());
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                numMetricsInserted++;
            }
            else {
                q.finish();
                q.prepare("insert ignore into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext) values (:qcmoduleseriesid, :resultnameid, :value)");
                q.bindValue(":qcmoduleseriesid", qcmoduleseriesid);
                q.bindValue(":resultnameid", qcNameRowID);
                q.bindValue(":value", value.toString());
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                numMetricsInserted++;
            }
        }
        n->Log(QString("%1 metrics from JSON file inserted into database").arg(numMetricsInserted));

        /* remove /data directory */
        //if (!SafeDeletePath(qcpath + "/data", n->cfg[""], m)) {
        //    n->Log("Error removing directory [" + qcpath + "/data]  error message [" + m + "]");
        //}
    }

    /* calculate the total time running */
    qint64 cputime = timer.elapsed();

    q.prepare("update qc_moduleseries set cpu_time = :cputime where qcmoduleseries_id = :qcmoduleseriesid");
    q.bindValue(":cputime",cputime);
    q.bindValue(":qcmoduleseriesid", qcmoduleseriesid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    // only process 10 before exiting the script. Since the script always starts with the newest when it first runs,
    // this will allow studies collected since the script started a chance to be QC'd
    //numProcessed++;

    //QThread::sleep(1);
    n->Log(QString("Finished %1 on %2 series %3").arg(modulename).arg(modality).arg(seriesid));

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateSGEJobFile ------------------------------- */
/* ---------------------------------------------------------- */
// QString moduleQC::CreateSGEJobFile(QString modulename, int qcmoduleseriesid, QString qcpath) {

//     QString jobfilename;

//     n->Log("CreateSGEJobFile() - A");

//     /* check if any of the variables might be blank */
//     if ((modulename == "") || (qcmoduleseriesid < 1)) {
//         n->Log("CreateSGEJobFile() - B");
//         return jobfilename;
//     }

//     QString jobfile;
//     n->Log("CreateSGEJobFile() - C");

//     jobfile += "#!/bin/sh\n";
//     jobfile += QString("#$ -N NIDB-QC-%1\n").arg(modulename);
//     jobfile += "#$ -S /bin/sh\n";
//     jobfile += "#$ -j y\n";
//     jobfile += "#$ -V\n";
//     jobfile += QString("#$ -o %1\n").arg(qcpath);
//     jobfile += QString("#$ -u %1\n\n").arg(n->cfg["queueuser"]);
//     jobfile += QString("cd %1/%2\n").arg(n->cfg["qcmoduledir"]).arg(modulename);
//     jobfile += QString("%1/%2/./%2.sh %3\n").arg(n->cfg["qcmoduledir"]).arg(modulename).arg(qcmoduleseriesid);
//     n->Log("CreateSGEJobFile() - D");

//     jobfilename = QString("%1/sge-%2.job").arg(qcpath).arg(GenerateRandomString(10));
//     QFile f(jobfilename);
//     if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
//         QTextStream fs(&f);
//         fs << jobfile;
//         f.close();
//     }
//     n->Log("CreateSGEJobFile() - E");
//     n->Log(SystemCommand("chmod 777 " + jobfilename));
//     n->Log("CreateSGEJobFile() - F");

//     return jobfilename;
// }


/* ---------------------------------------------------------- */
/* --------- SubmitToCluster -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Write a cluster job file for a QC module and submit it. NOT CURRENTLY CALLED: cluster mode is
 * disabled for QC modules because nothing exports the series data before submission or reads the results
 * back afterwards. The archive is assumed to be mounted at the same path on the cluster nodes.
 * @param moduleName QC module name, used as the job name
 * @param clusterRowID Database row ID of the compute_cluster configuration
 * @param qcPath QC directory for this series/module (job file, logs, data/ and output/ live here)
 * @param entryPoint Absolute path of the QC module's entrypoint script
 * @param uid Subject UID, passed to the entrypoint
 * @param msg Error message, if any
 * @return `true` if the job was submitted, `false` otherwise
 */
bool moduleQC::SubmitToCluster(QString moduleName, qint64 clusterRowID, QString qcPath, QString entryPoint, QString uid, QString &msg) {

    computeCluster cluster;
    if (!GetClusterInfo(clusterRowID, cluster)) {
        msg = QString("Compute cluster [%1] not found").arg(clusterRowID);
        n->Log(msg, __FUNCTION__);
        return false;
    }
    if (cluster.submitHostname == "") {
        msg = QString("Compute cluster [%1] has no submit hostname").arg(cluster.name);
        n->Log(msg, __FUNCTION__);
        return false;
    }

    QString jobFilePath = QString("%1/%2.job").arg(qcPath).arg(moduleName);
    if (!WriteClusterJobFile(jobFilePath, moduleName, cluster, qcPath, entryPoint, uid)) {
        msg = "Unable to write job file [" + jobFilePath + "]";
        return false;
    }

    int jobID(0);
    QString qResult;
    if (n->SubmitClusterJob(jobFilePath, cluster.type, cluster.submitHostname, cluster.submitHostUsername, n->cfg["qsubpath"], cluster.clusterUsername, cluster.queue, msg, jobID, qResult)) {
        n->Log(QString("[%1] Successfully submitted QC job [%2] to cluster [%3]").arg(moduleName).arg(jobID).arg(qResult), __FUNCTION__);
        return true;
    }

    n->Log(QString("[%1] Error submitting QC job to cluster [%2] message [%3]").arg(moduleName).arg(qResult).arg(msg), __FUNCTION__);
    return false;
}


/* ---------------------------------------------------------- */
/* --------- WriteClusterJobFile ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Build, and write to disk, the cluster job file, prior to submission to the cluster
 * @param jobFilePath The job filename
 * @param jobName The job name
 * @param cluster Compute cluster configuration
 * @param qcPath QC directory. The job cd's here, writes its logs here, and passes qcPath/data and qcPath/output to the entrypoint
 * @param entryPoint Absolute path of the entrypoint script
 * @param uid Subject UID, passed to the entrypoint
 * @return `true` if the file was written, `false` otherwise
 */
bool moduleQC::WriteClusterJobFile(QString jobFilePath, QString jobName, const computeCluster &cluster, QString qcPath, QString entryPoint, QString uid) {

    QString jobfile;

    n->Log("QC path [" + qcPath + "]", __FUNCTION__);

    /* check if any of the variables might be blank */
    if (qcPath == "") {
        n->Log("qcPath was blank", __FUNCTION__);
        return false;
    }
    if (entryPoint == "") {
        n->Log("entryPoint was blank", __FUNCTION__);
        return false;
    }

    QString wallTime;
    if (cluster.maxWallTime > 0)
        wallTime = QString("%1:%2:00").arg(cluster.maxWallTime/60).arg(cluster.maxWallTime % 60, 2, 10, QChar('0'));

    /* different submission parameters for slurm */
    if (cluster.type.toLower() == "slurm") {
        jobfile += "#!/bin/bash -l\n";
        jobfile += "#SBATCH -J " + jobName + "\n";
        jobfile += "#SBATCH --nodes=1\n";
        if (cluster.queue != "")
            jobfile += "#SBATCH --partition=" + cluster.queue + "\n";
        jobfile += "#SBATCH -o " + qcPath + "/%x.o%j\n";
        jobfile += "#SBATCH -e " + qcPath + "/%x.e%j\n";
        jobfile += QString("#SBATCH --mem-per-cpu=%1G\n").arg(cluster.memory);
        jobfile += QString("#SBATCH --ntasks=1 --cpus-per-task=%1\n").arg(cluster.numCores);
        if (wallTime != "")
            jobfile += "#SBATCH -t " + wallTime + "\n";
    }
    else { /* assume SGE otherwise */
        jobfile += "#!/bin/sh\n";
        jobfile += "#$ -N " + jobName + "\n";
        jobfile += "#$ -S /bin/bash\n";
        jobfile += "#$ -j y\n";
        jobfile += "#$ -o " + qcPath + "/\n";
        jobfile += "#$ -V\n";
        if (cluster.clusterUsername != "")
            jobfile += "#$ -u " + cluster.clusterUsername + "\n";
        if (wallTime != "")
            jobfile += "#$ -l h_rt=" + wallTime + "\n";
    }

    jobfile += "echo Hostname: `hostname`\n";
    jobfile += "echo Username: `whoami`\n\n";

    /* same command line as a local run: <entrypoint> <qcpath>/data <qcpath>/output <uid> */
    jobfile += "cd \"" + qcPath + "\" || exit 1\n";
    jobfile += QString("\"%1\" \"%2/data\" \"%2/output\" \"%3\"\n").arg(entryPoint).arg(qcPath).arg(uid);

    /* write out the file */
    QFile f(jobFilePath);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QTextStream fs(&f);
        fs << jobfile;
        f.close();
        n->Log("Wrote job file [" + jobFilePath + "]", __FUNCTION__);
        return true;
    }
    else {
        n->Log("Could not write the file [" + jobFilePath + "] error [" + f.errorString() + "]", __FUNCTION__);
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetClusterInfo --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Load a compute_cluster row
 * @param clusterRowID Database row ID of the compute cluster
 * @param cluster The cluster configuration. `queue` is the first entry of the `queues` list
 * @return `true` if the cluster was found, `false` otherwise
 */
bool moduleQC::GetClusterInfo(qint64 clusterRowID, computeCluster &cluster) {

    cluster = computeCluster();
    if (clusterRowID < 1)
        return false;

    QSqlQuery q;
    q.prepare("select * from compute_cluster where computecluster_id = :clusterid");
    q.bindValue(":clusterid", clusterRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1)
        return false;

    q.first();
    cluster.name = q.value("cluster_name").toString();
    cluster.description = q.value("cluster_desc").toString();
    cluster.type = q.value("cluster_type").toString();
    cluster.submitHostname = q.value("submit_hostname").toString();
    cluster.submitHostUsername = q.value("submithost_username").toString();
    cluster.clusterUsername = q.value("cluster_username").toString();
    /* queues is a free-text list (queues on SGE, partitions on slurm). Use the first one */
    QStringList queues = q.value("queues").toString().split(QRegularExpression("[,;\\s]+"), Qt::SkipEmptyParts);
    cluster.queue = queues.isEmpty() ? "" : queues.first();
    cluster.maxWallTime = q.value("cluster_maxwalltime").toLongLong();
    cluster.memory = q.value("cluster_memory").toLongLong();
    cluster.numCores = q.value("cluster_numcores").toLongLong();

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ExportSeries ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleQC::ExportSeries(qint64 seriesRowID, QString modality, ExportFormat format, QString outputDir) {

    /* get series details */
    series s(seriesRowID, modality.toUpper(), n);
    if (!s.isValid) {
        n->Log("Series was not valid: [" + s.msg + "]");
        return false;
    }

    QString tmpdir = n->cfg["tmpdir"] + "/" + GenerateRandomString(10);
    QString m;
    int numFilesConverted(0), numFilesRenamed(0);
    QString binpath = n->cfg["nidbdir"] + "/bin";
    QString studyNumStr = QString("%1").arg(s.studynum);
    QString seriesNumStr = QString("%1").arg(s.seriesnum);
    QList<qint64> seriesRowIDs;
    QStringList seriesModalities;

    seriesRowIDs.append(seriesRowID);
    seriesModalities.append(modality);

    n->Log(QString("Exporting series [%1] [%2] [%3] to [%4]").arg(seriesRowID).arg(modality).arg(static_cast<int>(format)).arg(outputDir));

    switch (format) {
        case ExportFormat::Original:
        case ExportFormat::Dicom: {
            /* copy the data */
            break;
        }
        case ExportFormat::DicomLite: {
            /* copy the data */
            break;
        }
        case ExportFormat::DicomFull: {
            /* copy the data */
            break;
        }
        case ExportFormat::Nifti3d: {
            if (MakePath(tmpdir, m)) {
                imageIO img(n);
                if (!img.ConvertDicom("nifti3d", s.datapath, tmpdir, binpath, false, false, s.uid, studyNumStr, seriesNumStr, "", "", s.bidsMapping, s.datatype, QJsonObject(), numFilesConverted, numFilesRenamed, m)) {
                    n->Log("Error exporting series. Message [" + m + "]");
                }
            }
            break;
        }
        case ExportFormat::Nifti3dgz: {
            if (MakePath(tmpdir, m)) {
                imageIO img(n);
                if (!img.ConvertDicom("nifti3d", s.datapath, tmpdir, binpath, true, false, s.uid, studyNumStr, seriesNumStr, "", "", s.bidsMapping, s.datatype, QJsonObject(), numFilesConverted, numFilesRenamed, m)) {
                    n->Log("Error exporting series. Message [" + m + "]");
                }
            }
            break;
        }
        case ExportFormat::Nifti4d: {
            if (MakePath(tmpdir, m)) {
                imageIO img(n);
                if (!img.ConvertDicom("nifti4d", s.datapath, tmpdir, binpath, false, false, s.uid, studyNumStr, seriesNumStr, "", "", s.bidsMapping, s.datatype, QJsonObject(), numFilesConverted, numFilesRenamed, m)) {
                    n->Log("Error exporting series. Message [" + m + "]");
                }
            }
            break;
        }
        case ExportFormat::Nifti4dgz: {
            if (MakePath(tmpdir, m)) {
                imageIO img(n);
                if (!img.ConvertDicom("nifti4d", s.datapath, tmpdir, binpath, true, false, s.uid, studyNumStr, seriesNumStr, "", "", s.bidsMapping, s.datatype, QJsonObject(), numFilesConverted, numFilesRenamed, m)) {
                    n->Log("Error exporting series. Message [" + m + "]");
                }
            }
            break;
        }
        case ExportFormat::NiftiMe: {
            if (MakePath(tmpdir, m)) {
                imageIO img(n);
                if (!img.ConvertDicom("niftime", s.datapath, tmpdir, binpath, true, false, s.uid, studyNumStr, seriesNumStr, "", "", s.bidsMapping, s.datatype, QJsonObject(), numFilesConverted, numFilesRenamed, m)) {
                    n->Log("Error exporting series. Message [" + m + "]");
                }
            }
            break;
        }
        case ExportFormat::BIDS: {
            if (MakePath(tmpdir, m)) {
                archiveIO *io = new archiveIO(n);
                QStringList bidsFlags = { "BIDS_SUBJECTDIR_UID", "BIDS_STUDYDIR_STUDYNUM"};
                io->WriteBIDS(seriesRowIDs, seriesModalities, outputDir, "Readme", bidsFlags, m);
                delete io;
            }
            break;
        }
        case ExportFormat::Squirrel: {
            break;
        }
    }

    return true;
}
