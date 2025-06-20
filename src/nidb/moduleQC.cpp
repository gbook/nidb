/* ------------------------------------------------------------------------------
  NIDB moduleQC.cpp
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

#include "moduleQC.h"
#include <QSqlQuery>

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
            QString modality = q.value("modality").toString().toLower();

            n->Log(QString("*********************** Working on module [%1][%2] ***********************").arg(moduleid).arg(modality));

            /* look through DB for all series (of this modality) that don't have an associated QCdata row */
            QSqlQuery q2;
            q2.prepare(QString("select %1series_id 'seriesid' from %1_series where %1series_id not in (select series_id from qc_moduleseries where qcmodule_id = :moduleid) order by series_datetime desc").arg(modality));
            q2.bindValue(":moduleid", moduleid);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__,true);
            if (q2.size() > 0) {
                while (q2.next()) {
                    ret = 1;
                    int seriesid = q2.value("seriesid").toInt();

                    n->ModuleRunningCheckIn();

                    /* check if this series has an mr_qa row */
                    QSqlQuery q3;
                    q3.prepare("select mrseries_id from mr_qa where mrseries_id = :seriesid");
                    q3.bindValue(":seriesid", seriesid);
                    n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                    if (q3.size() > 0) {
                        QC(moduleid, seriesid, modality);
                        numdone++;

                        /* check if this module should be running now or not */
                        if (!n->ModuleCheckIfActive()) {
                            n->Log("Not supposed to be running right now");
                            return 0;
                        }

                        /* give this thing a break every so often */
                        if (numdone >= 100)
                            break;

                        QThread::sleep(1); // sleep for 1 sec
                    }
                    else {
                        n->Log(QString("Skipping this MR series [%1] because it does not have an mr_qa row yet... QC needs the 3D/4D information from the mr_qa script first").arg(seriesid));
                    }
                }
                n->Log("Finished checking for MR series that dont have a QC row");
            }
            else {
                n->Log("Nothing to do");
            }

            n->Log(QString("*********************** Finished module [%1][%2] ***********************").arg(moduleid).arg(modality));

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

    QString modulename;

    QSqlQuery q;
    q.prepare("select module_name from qc_modules where qcmodule_id = :moduleid");
    q.bindValue(":moduleid",moduleid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
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

    n->Log(QString("-------------- Running %1 on %2 series %3 --------------").arg(moduleid).arg(modality).arg(seriesid));

    int qcmoduleseriesid(0);

    n->Log(QString("============== Working on [%1-%2-%3] ==============").arg(uid).arg(studynum).arg(seriesnum));
    // check if this qc_moduleseries row exists
    q.prepare("select * from qc_moduleseries where series_id = :seriesid and modality = :modality and qcmodule_id = :moduleid");
    q.bindValue(":seriesid",seriesid);
    q.bindValue(":modality",modality);
    q.bindValue(":moduleid",moduleid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0)
        /* another row exists */
        return false;
    else {
        /* insert a blank row for this qc_moduleseries and get the row ID */
        QSqlQuery q2;
        q2.prepare("insert ignore into qc_moduleseries (qcmodule_id, series_id, modality) values (:moduleid, :seriesid, :modality)");
        q2.bindValue(":seriesid",seriesid);
        q2.bindValue(":modality",modality);
        q2.bindValue(":moduleid",moduleid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        qcmoduleseriesid = q2.lastInsertId().toInt();
    }

    QString qcpath = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);
    QString m;
    if (!MakePath(qcpath, m)) {
        n->Log("Unable to create directory ["+qcpath+"] because of error ["+m+"]");
        return false;
    }
    n->Log("Working on ["+qcpath+"]");

    if (n->cfg["usecluster"].toInt()) {
        /* submit this module to the cluster. first create the SGE job file */
        n->Log("About to create the SGE job file");
        QString sgebatchfile = CreateSGEJobFile(modulename, qcmoduleseriesid, qcpath);
        n->Log("Created SGE job file");

        /* submit the SGE job */
        QString systemstring = QString("ssh %1 %2 -u %3 -q %4 \"%5\"").arg(n->cfg["clustersubmithost"]).arg(n->cfg["qsubpath"]).arg(n->cfg["queueuser"]).arg(n->cfg["queuename"]).arg(sgebatchfile);
        n->Log("About to submit SGE job file");
        n->Log(SystemCommand(systemstring));
        n->Log("Submitted SGE job file");
    }
    else {
        n->Log("About to run the QC module locally");
        QDir::setCurrent(n->cfg["qcmoduledir"] + "/" + modulename);
        QString systemstring = QString("%1/%2/./%2.sh %3").arg(n->cfg["qcmoduledir"]).arg(modulename).arg(qcmoduleseriesid);
        n->Log(SystemCommand(systemstring));
        n->Log("Finished running the QC module locally");
    }

    /* calculate the total time running */
    qint64 cputime = timer.elapsed();

    q.prepare("update qc_moduleseries set cpu_time = :cputime where qcmoduleseries_id = :qcmoduleseriesid");
    q.bindValue(":cputime",cputime);
    q.bindValue(":qcmoduleseriesid",qcmoduleseriesid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    // only process 10 before exiting the script. Since the script always starts with the newest when it first runs,
    // this will allow studies collected since the script started a chance to be QC'd
    //numProcessed++;

    //QThread::sleep(1);
    n->Log(QString("-------------- Finished %1 on %2 series %3 --------------").arg(moduleid).arg(modality).arg(seriesid));

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateSGEJobFile ------------------------------- */
/* ---------------------------------------------------------- */
QString moduleQC::CreateSGEJobFile(QString modulename, int qcmoduleseriesid, QString qcpath) {

    QString jobfilename;

    n->Log("CreateSGEJobFile() - A");

    /* check if any of the variables might be blank */
    if ((modulename == "") || (qcmoduleseriesid < 1)) {
        n->Log("CreateSGEJobFile() - B");
        return jobfilename;
    }

    QString jobfile;
    n->Log("CreateSGEJobFile() - C");

    jobfile += "#!/bin/sh\n";
    jobfile += QString("#$ -N NIDB-QC-%1\n").arg(modulename);
    jobfile += "#$ -S /bin/sh\n";
    jobfile += "#$ -j y\n";
    jobfile += "#$ -V\n";
    jobfile += QString("#$ -o %1\n").arg(qcpath);
    jobfile += QString("#$ -u %1\n\n").arg(n->cfg["queueuser"]);
    jobfile += QString("cd %1/%2\n").arg(n->cfg["qcmoduledir"]).arg(modulename);
    jobfile += QString("%1/%2/./%2.sh %3\n").arg(n->cfg["qcmoduledir"]).arg(modulename).arg(qcmoduleseriesid);
    n->Log("CreateSGEJobFile() - D");

    jobfilename = QString("%1/sge-%2.job").arg(qcpath).arg(GenerateRandomString(10));
    QFile f(jobfilename);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QTextStream fs(&f);
        fs << jobfile;
        f.close();
    }
    n->Log("CreateSGEJobFile() - E");
    n->Log(SystemCommand("chmod 777 " + jobfilename));
    n->Log("CreateSGEJobFile() - F");

    return jobfilename;
}


/* ---------------------------------------------------------- */
/* --------- WriteClusterJobFile ---------------------------- */
/* ---------------------------------------------------------- */
bool moduleQC::WriteClusterJobFile(QString jobfilename, int clusterid, QString datapath, QString entrypoint) {

    QString jobfile;
    QString clusteranalysispath = analysispath;
    QString localanalysispath = QString("%1/%2-%3").arg(tmpdir).arg(pipelinename).arg(analysisid);

    /* get cluster info */
    computeCluster cluster = GetClusterInfo(clusterid);

    n->Log("Cluster analysis path [" + analysispath + "]");
    n->Log("Local analysis path (temp directory) [" + localanalysispath + "]");

    /* check if any of the variables might be blank */
    if (analysispath == "") {
        n->Log("analysispath was blank", __FUNCTION__);
        return false;
    }
    if (localanalysispath == "") {
        n->Log("localanalysispath was blank", __FUNCTION__);
        return false;
    }

    /* different submission parameters for slurm */
    if (cluster.type == "slurm") {
        jobfile += "#!/bin/bash -l\n";
        jobfile += "#SBATCH -J " + pipelinename + "\n";
        jobfile += "#SBATCH --nodes=1\n";
        jobfile += "#SBATCH --partition=" + queue + "\n";
        jobfile += "#SBATCH -o " + analysispath + "/pipeline/%x.o%j\n";
        jobfile += "#SBATCH -e " + analysispath + "/pipeline/%x.e%j\n";
        jobfile += QString("#SBATCH --mem-per-cpu=%1G\n").arg(memory);
        jobfile += QString("#SBATCH --ntasks=1 --cpus-per-task=%1\n").arg(numcores);
        if (maxwalltime > 0) {
            int hours = int(floor(maxwalltime/60));
            int min = maxwalltime % 60;

            if (min < 10)
                jobfile += QString("#SBATCH -t %1:0%2:00\n").arg(hours).arg(min);
            else
                jobfile += QString("#SBATCH -t %1:%2:00\n").arg(hours).arg(min);
        }
    }
    else { /* assume SGE otherwise */
        jobfile += "#!/bin/sh\n";
        jobfile += "#$ -N "+pipelinename+"\n";
        jobfile += "#$ -S /bin/bash\n";
        jobfile += "#$ -j y\n";
        jobfile += "#$ -o "+analysispath+"/pipeline/\n";
        jobfile += "#$ -V\n";
        jobfile += "#$ -u " + n->cfg["queueuser"] + "\n";
        if (maxwalltime > 0) {
            int hours = int(floor(maxwalltime/60));
            int min = maxwalltime % 60;

            if (min < 10)
                jobfile += QString("#$ -l h_rt=%1:0%2:00\n").arg(hours).arg(min);
            else
                jobfile += QString("#$ -l h_rt=%1:%2:00\n").arg(hours).arg(min);
        }
        /* add the library path SO the cluster version of the nidb executable to run, and diagnostic echos */
        jobfile += "LD_LIBRARY_PATH=" + n->cfg["clusternidbpath"] + "/; export LD_LIBRARY_PATH;\n";
    }

    jobfile += "echo Hostname: `hostname`\n";
    jobfile += "echo Username: `whoami`\n\n";

    jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Beginning data copy to /tmp'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);
    jobfile += "mkdir -pv " + localanalysispath + "\n";
    jobfile += "cp -Rv " + analysispath + "/* " + localanalysispath + "/\n";
    jobfile += QString("%1/nidb cluster -u pipelinecheckin -a %2 -s started -m 'Done copying data to /tmp'\n").arg(n->cfg["clusternidbpath"]).arg(analysisid);

    QDir::setCurrent(clusteranalysispath);

    /* write out the file */
    QFile f(jobfilename);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QTextStream fs(&f);
        fs << jobfile;
        f.close();
        n->Log("Wrote job file [" + jobfilename + "]", __FUNCTION__);
        return true;
    }
    else {
        n->Log("Could not write the file [" + jobfilename + "]", __FUNCTION__);
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetClusterInfo --------------------------------- */
/* ---------------------------------------------------------- */
computeCluster moduleQC::GetClusterInfo(int clusterRowID) {

    computeCluster cluster;

    QSqlQuery q;
    q.prepare("select * from compute_cluster where computecluster_id = :clusterid");
    q.bindValue(":clusterid", clusterRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        cluster.name = q.value("cluster_name").toString();
        cluster.description = q.value("cluster_name").toString();
        cluster.type = q.value("cluster_name").toString();
        cluster.submitHostname = q.value("cluster_name").toString();
        cluster.submitHostUsername = q.value("cluster_name").toString();
        cluster.clusterUsername = q.value("cluster_name").toString();
        cluster.queue = q.value("cluster_name").toString();
    }

    return cluster;
}
