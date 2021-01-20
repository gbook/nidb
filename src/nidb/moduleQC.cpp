/* ------------------------------------------------------------------------------
  NIDB moduleQC.cpp
  Copyright (C) 2004 - 2021
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
	n->WriteLog("Entering the QC module");

	int ret(0);

	/* get list of active modules */
	QSqlQuery q;
	q.prepare("select * from qc_modules where qcm_isenabled = 1");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		int numdone = 0;
		while (q.next()) {
			int moduleid = q.value("qcmodule_id").toInt();
			QString modality = q.value("qcm_modality").toString().toLower();

			n->WriteLog(QString("*********************** Working on module [%1][%2] ***********************").arg(moduleid).arg(modality));

			/* look through DB for all series (of this modality) that don't have an associated QCdata row */
			QSqlQuery q2;
			q2.prepare(QString("SELECT %1series_id 'seriesid' FROM %1_series where %1series_id not in (select series_id from qc_moduleseries where qcmodule_id = :moduleid) order by series_datetime desc").arg(modality));
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
							n->WriteLog("Not supposed to be running right now");
							return 0;
						}

						/* give this thing a break every so often */
						if (numdone >= 100)
							break;

						QThread::sleep(1); // sleep for 1 sec
					}
					else {
						n->WriteLog(QString("Skipping this MR series [%1] because it does not have an mr_qa row yet... QC needs the 3D/4D information from the mr_qa script first").arg(seriesid));
					}
				}
				n->WriteLog("Finished checking for MR series that dont have a QC row");
			}
			else {
				n->WriteLog("Nothing to do");
			}

			n->WriteLog(QString("*********************** Finished module [%1][%2] ***********************").arg(moduleid).arg(modality));

		}
		n->WriteLog("Finished all modules");
	}
	else {
		n->WriteLog("No QC modules exist (in the database)!");
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
	q.prepare("select qcm_name from qc_modules where qcmodule_id = :moduleid");
	q.bindValue(":moduleid",moduleid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		modulename = q.value("qcm_name").toString();
	}
	else
		return false;

	/* get the series info */
	series s(seriesid, modality.toUpper(), n);
	if (!s.isValid) {
		n->WriteLog("Series was not valid: [" + s.msg + "]");
		return false;
	}

	int seriesnum = s.seriesnum;
	int studynum = s.studynum;
	QString uid = s.uid;
	QString datatype = s.datatype;

	n->WriteLog(QString("-------------- Running %1 on %2 series %3 --------------").arg(moduleid).arg(modality).arg(seriesid));

	int qcmoduleseriesid(0);

	n->WriteLog(QString("============== Working on [%1-%2-%3] ==============").arg(uid).arg(studynum).arg(seriesnum));
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
	if (!n->MakePath(qcpath, m)) {
		n->WriteLog("Unable to create directory ["+qcpath+"] because of error ["+m+"]");
		return false;
	}
	n->WriteLog("Working on ["+qcpath+"]");

	if (n->cfg["usecluster"].toInt()) {
		/* submit this module to the cluster. first create the SGE job file */
		n->WriteLog("About to create the SGE job file");
		QString sgebatchfile = CreateSGEJobFile(modulename, qcmoduleseriesid, qcpath);
		n->WriteLog("Created SGE job file");

		/* submit the SGE job */
		QString systemstring = QString("ssh %1 %2 -u %3 -q %4 \"%5\"").arg(n->cfg["clustersubmithost"]).arg(n->cfg["qsubpath"]).arg(n->cfg["queueuser"]).arg(n->cfg["queuename"]).arg(sgebatchfile);
		n->WriteLog("About to submit SGE job file");
		n->WriteLog(n->SystemCommand(systemstring));
		n->WriteLog("Submitted SGE job file");
	}
	else {
		n->WriteLog("About to run the QC module locally");
		QDir::setCurrent(n->cfg["qcmoduledir"] + "/" + modulename);
		QString systemstring = QString("%1/%2/./%2.sh %3").arg(n->cfg["qcmoduledir"]).arg(modulename).arg(qcmoduleseriesid);
		n->WriteLog(n->SystemCommand(systemstring));
		n->WriteLog("Finished running the QC module locally");
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
	n->WriteLog(QString("-------------- Finished %1 on %2 series %3 --------------").arg(moduleid).arg(modality).arg(seriesid));

	return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateSGEJobFile ------------------------------- */
/* ---------------------------------------------------------- */
QString moduleQC::CreateSGEJobFile(QString modulename, int qcmoduleseriesid, QString qcpath) {

	QString jobfilename;

	n->WriteLog("CreateSGEJobFile() - A");

	/* check if any of the variables might be blank */
	if ((modulename == "") || (qcmoduleseriesid < 1)) {
		n->WriteLog("CreateSGEJobFile() - B");
		return jobfilename;
	}

	QString jobfile;
	n->WriteLog("CreateSGEJobFile() - C");

	jobfile += "#!/bin/sh\n";
	jobfile += QString("#$ -N NIDB-QC-%1\n").arg(modulename);
	jobfile += "#$ -S /bin/sh\n";
	jobfile += "#$ -j y\n";
	jobfile += "#$ -V\n";
	jobfile += QString("#$ -o %1\n").arg(qcpath);
	jobfile += QString("#$ -u %1\n\n").arg(n->cfg["queueuser"]);
	jobfile += QString("cd %1/%2\n").arg(n->cfg["qcmoduledir"]).arg(modulename);
	jobfile += QString("%1/%2/./%2.sh %3\n").arg(n->cfg["qcmoduledir"]).arg(modulename).arg(qcmoduleseriesid);
	n->WriteLog("CreateSGEJobFile() - D");

	jobfilename = QString("%1/sge-%2.job").arg(qcpath).arg(n->GenerateRandomString(10));
	QFile f(jobfilename);
	if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
		QTextStream fs(&f);
		fs << jobfile;
		f.close();
	}
	n->WriteLog("CreateSGEJobFile() - E");
	n->WriteLog(n->SystemCommand("chmod 777 " + jobfilename));
	n->WriteLog("CreateSGEJobFile() - F");

	return jobfilename;
}
