/* ------------------------------------------------------------------------------
  NIDB moduleMiniPipeline.cpp
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

#include "moduleMiniPipeline.h"
#include "minipipeline.h"
#include "series.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- moduleMiniPipeline ----------------------------- */
/* ---------------------------------------------------------- */
moduleMiniPipeline::moduleMiniPipeline()
{

}


/* ---------------------------------------------------------- */
/* --------- moduleMiniPipeline ----------------------------- */
/* ---------------------------------------------------------- */
moduleMiniPipeline::moduleMiniPipeline(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleMiniPipeline ---------------------------- */
/* ---------------------------------------------------------- */
moduleMiniPipeline::~moduleMiniPipeline()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleMiniPipeline::Run() {
    n->WriteLog("Entering the pipeline module");

    n->ModuleRunningCheckIn();

    int numJobsRun = 0;
    QSqlQuery q;
    QList<int> mpjobs = GetMPJobList();
    if (mpjobs.size() > 0) {
        int i = 0;
        foreach(int mpjobid, mpjobs) {
            i++;
            n->WriteLog(QString("Working on mini-pipeline job [%1] of [%2]").arg(i).arg(mpjobs.size()));
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) {
                n->WriteLog("Module is now inactive, stopping the module");
                if (numJobsRun > 0)
                    return 1;
                else
                    return 0;
            }

            /* check if the minipipeline job is still pending */
            q.prepare("select minipipelinejob_id from minipipeline_jobs where mp_status = 'pending' and minipipelinejob_id = :mpjobid");
            q.bindValue(":mpjobid", mpjobid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() < 1) {
                n->WriteLog("This job's status is no longer 'pending'");
                continue;
            }

            /* set it to processing to prevent another instance from working on it too */
            q.prepare("update minipipeline_jobs set mp_status = 'running', mp_startdate = now() where minipipelinejob_id = :mpjobid");
            q.bindValue(":mpjobid", mpjobid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

            numJobsRun++;
			//QStringList logs;
            int enrollmentID = -1;
            int numInserts = 0;

            /* get the minipipeline details */
            q.prepare("select * from minipipeline_jobs where minipipelinejob_id = :mpjobid");
            q.bindValue(":mpjobid", mpjobid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                int mpid = q.value("minipipeline_id").toInt();
                QString modality = q.value("mp_modality").toString();
                int seriesid = q.value("mp_seriesid").toInt();

                minipipeline mp(mpid, n); /* get the analysis info */
                if (!mp.isValid) {
                    AppendMiniPipelineLog(n->WriteLog("mini-pipeline was not valid: [" + mp.msg + "]"), mpjobid);
                    continue;
                }

                series s(seriesid, modality, n); /* get the series info */
                if (!s.isValid) {
                    AppendMiniPipelineLog(n->WriteLog("Series was not valid: [" + s.msg + "]"), mpjobid);
                    return 0;
                }
                enrollmentID = s.enrollmentid;

                AppendMiniPipelineLog(n->WriteLog("Running mini-pipeline [" + mp.name + "] on [" + s.datapath + "]"), mpjobid);

                /* (1) create a temp space */
                QString m;
                QString tmpdir = "/tmp/" + n->GenerateRandomString(10);
                if (!n->MakePath(tmpdir, m))
                    AppendMiniPipelineLog(n->WriteLog("Error creating directory [" + tmpdir + "] error message [" + m + "]"), mpjobid);
                else {
                    /* (2) write the script files */
                    if (mp.WriteScripts(tmpdir, m)) {

                        /* (3) copy in all of the behavioral data */
                        QString m;
						qint64 c = CopyAllSeriesData(modality, seriesid, tmpdir, m);
                        if (c > 0)
                            AppendMiniPipelineLog(n->WriteLog(QString("Copied [%1] files to [%2]").arg(c).arg(tmpdir)), mpjobid);
                        else
                            AppendMiniPipelineLog(n->WriteLog("Did not copy any series from data directory. Message from CopyAllSeriesData() [" + m + "]"), mpjobid);

                        /* (4) execute the script (sandboxed to the tmp directory), and limit execution time to 5 minutes */
                        QString systemstring = mp.entrypoint;
                        QString output = "";
                        n->SandboxedSystemCommand(systemstring, tmpdir, output, "00:05:00", true, false);
                        AppendMiniPipelineLog(n->WriteLog(output), mpjobid);

                        /* (5) parse the output */
                        QString outfilename = tmpdir + "/output.csv";
                        QFile f(outfilename);
                        if (f.exists())
                            AppendMiniPipelineLog(n->WriteLog("[" + outfilename + "] exists"), mpjobid);
                        else
                            AppendMiniPipelineLog(n->WriteLog("[" + outfilename + "] does not exist"), mpjobid);

                        if (f.open(QFile::ReadOnly | QFile::Text)) {
                            QTextStream in(&f);
                            QString csvText = in.readAll();

                            AppendMiniPipelineLog("\nContents of .csv file:\n ----------\n" + csvText + "\n ----------\n", mpjobid);

                            indexedHash csv;
                            QStringList cols;
                            if (n->ParseCSV(csvText, csv, cols, m)) {
                                AppendMiniPipelineLog("\nParsed .csv file. Message(s) from parser [" + m + "]", mpjobid);
                                if (!cols.contains("type"))
                                    AppendMiniPipelineLog("\nError - csv header did not contain the [type] column header. This column is required", mpjobid);
                                if (!cols.contains("variablename"))
                                    AppendMiniPipelineLog("\nError - csv header did not contain the [variablename] column header. This column is required", mpjobid);
                                if ((!cols.contains("startdate")) && (!cols.contains("startdatetime")))
                                    AppendMiniPipelineLog("\nError - csv header did not contain the [startdate] column header. This column is required", mpjobid);
                                if ((!cols.contains("enddate")) && (!cols.contains("enddatetime")))
                                    AppendMiniPipelineLog("\ncsv header did not contain the [enddate] column header. The header is required, though the column values are optional", mpjobid);
                                if (!cols.contains("duration"))
                                    AppendMiniPipelineLog("\ncsv header did not contain the [duration] column header. The header is required, though the column values are optional", mpjobid);
                                if (!cols.contains("value"))
                                    AppendMiniPipelineLog("\nError - csv header did not contain the [value] column header. This column is required", mpjobid);
                                if (!cols.contains("units"))
                                    AppendMiniPipelineLog("\ncsv header did not contain the [units] column header. The header is required, though the column values are optional", mpjobid);
                                if (!cols.contains("notes"))
                                    AppendMiniPipelineLog("\ncsv header did not contain the [notes] column header. The header is required, though the column values are optional", mpjobid);
                                if (!cols.contains("instrument"))
                                    AppendMiniPipelineLog("\ncsv header did not contain the [instrument] column header. The header is required, though the column values are optional", mpjobid);

                                /* go through all the rows from the csv */
                                for (int i=0; i<csv.size();i++) {
                                    QString csvType = csv[i]["type"];
                                    QString csvVariableName = csv[i]["variablename"];
                                    QString csvStartDate;
                                    if (csv[i]["startdate"] == "") csvStartDate = csv[i]["startdatetime"];
                                    else csvStartDate = csv[i]["startdate"];
                                    QString csvEndDate = csv[i]["enddate"];
                                    if (csv[i]["enddate"] == "") csvEndDate = csv[i]["enddatetime"];
                                    else csvEndDate = csv[i]["enddate"];
                                    QString csvDuration = csv[i]["duration"];
                                    QString csvValue = csv[i]["value"];
									//QString csvUnits = csv[i]["units"];
                                    QString csvNotes = csv[i]["notes"];
                                    QString csvInstrument = csv[i]["instrument"];

                                    /* check the variable name */
                                    if (csvVariableName == "")
                                        AppendMiniPipelineLog(n->WriteLog(QString("variablename was blank for line %1").arg(i)), mpjobid);
                                    /* check the value name */
                                    if (csvValue == "")
                                        AppendMiniPipelineLog(n->WriteLog(QString("value was blank for line %1").arg(i)), mpjobid);

                                    /* check and reformat the startDate */
                                    QDateTime startDate;
                                    if (csvStartDate == "")
                                        AppendMiniPipelineLog(n->WriteLog(QString("startdate was blank for line %1").arg(i)), mpjobid);
                                    else {
                                        QStringList sdparts = csvStartDate.split(" ");
                                        if (sdparts.size() == 1)
                                            if (sdparts[0].contains("T"))
                                                startDate = QDateTime::fromString(sdparts[0],"yyyy-MM-ddThh:mm:ss");
                                            else
                                                startDate = QDateTime::fromString(sdparts[0],"yyyy-MM-dd");
                                        else {
                                            sdparts[1] = n->ParseTime(sdparts[1]); /* attempt to fix the time if its different than expected */
                                            if (sdparts[1].size() == 5)
                                                startDate = QDateTime::fromString(sdparts[0] + " " + sdparts[1],"yyyy-MM-dd hh:mm");
                                            else
                                                startDate = QDateTime::fromString(sdparts[0] + " " + sdparts[1],"yyyy-MM-dd hh:mm:ss");
                                        }
                                    }

                                    /* check and reformat the endDate */
                                    QDateTime endDate;
                                    if (csvEndDate != "") {
                                        QStringList edparts = csvEndDate.split(" ");
                                        if (edparts.size() == 1)
                                            if (edparts[0].contains("T"))
                                                endDate = QDateTime::fromString(edparts[0],"yyyy-MM-ddThh:mm:ss");
                                            else
                                                endDate = QDateTime::fromString(edparts[0],"yyyy-MM-dd");
                                        else
                                            if (edparts[1].size() == 5)
                                                endDate = QDateTime::fromString(edparts[0] + " " + edparts[1],"yyyy-MM-dd hh:mm");
                                            else
                                                endDate = QDateTime::fromString(edparts[0] + " " + edparts[1],"yyyy-MM-dd hh:mm:ss");
                                    }

                                    /* check for valid dates */
                                    if (!startDate.isValid())
                                        AppendMiniPipelineLog(n->WriteLog("Error. Invalid start date [" + csvStartDate + "]"), mpjobid);
                                    if (!endDate.isValid())
                                        AppendMiniPipelineLog(n->WriteLog("Invalid end date [" + csvEndDate + "]"), mpjobid);

                                    /* insert the value */
                                    //QSqlQuery q2;
                                    if (csvType == "measure") {
                                        int n=0;
                                        QString m;
                                        if (!InsertMeasure(enrollmentID, s.studyid, s.seriesid, csvVariableName, csvValue, csvInstrument, startDate, endDate, csvDuration.toInt(), "minipipeline-" + mp.name, n, m))
                                            AppendMiniPipelineLog(m, mpjobid);
                                        else
                                            numInserts += n;
                                    }
                                    else if (csvType == "vital") {
                                        numInserts += InsertVital(enrollmentID, csvVariableName, csvValue, csvNotes, csvInstrument, startDate, endDate, csvDuration.toInt());
                                    }
                                    else if (csvType == "drug") {
                                        numInserts += InsertDrug(enrollmentID, startDate, endDate, csvValue, "", "", "", "", "", "", 0.0, "");
                                    }
                                    else {
                                        AppendMiniPipelineLog(n->WriteLog("Error. Invalid value type [" + csvType + "]"), mpjobid);
                                    }
                                }
                            }
                            else {
                                AppendMiniPipelineLog(n->WriteLog("Error. Unable to parse csv output file. Message(s) from parser [" + m + "]"), mpjobid);
                            }
                        }
                        else {
                            AppendMiniPipelineLog(n->WriteLog("Error. Unable to read .csv output file [" + outfilename + "]"), mpjobid);
                        }
                    }
                    else
                        AppendMiniPipelineLog(n->WriteLog("Error. Unable to write scripts to [" + tmpdir + "] error message [" + m + "]"), mpjobid);

                    /* (6) cleanup */
                    if (!n->RemoveDir(tmpdir, m))
                        AppendMiniPipelineLog(n->WriteLog("Error deleting directory [" + tmpdir + "] error message [" + m + "]"), mpjobid);
                    else
                        AppendMiniPipelineLog(n->WriteLog("Deleted temp directory [" + tmpdir + "]"), mpjobid);
                }
            }
            else {
                /* the minipipeline specified by this job was not found, set an error */
                AppendMiniPipelineLog(n->WriteLog("The pipeline specified was not found. Maybe it was deleted since this job was submitted?"), mpjobid);
            }

            /* done running the job - update the status and log */
            q.prepare("update minipipeline_jobs set mp_status = 'complete', mp_numinserts = :numinserts, mp_enddate = now() where minipipelinejob_id = :mpjobid");
            q.bindValue(":mpjobid", mpjobid);
            q.bindValue(":numinserts", numInserts);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
    }
    else {
        n->WriteLog("Nothing to run");
    }

    n->WriteLog("Leaving the minipipeline module");

    if (numJobsRun > 0)
        return 1;
    else
        return 0;
}


/* ---------------------------------------------------------- */
/* --------- GetMPJobList ----------------------------------- */
/* ---------------------------------------------------------- */
QList<int> moduleMiniPipeline::GetMPJobList() {
    QList<int> list;

    QSqlQuery q;
    q.prepare("select minipipelinejob_id from minipipeline_jobs where mp_status = 'pending'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next())
            list.append(q.value("minipipelinejob_id").toInt());
    }

    return list;
}


/*************************************************************
* @brief CopyAllSeriesData
*
* Copies all series data to a destination path
* @param modality Modality of the series
* @param seriesid ID of the series
* @param destination directory to which the data will be copied
* @param msg logs or messages returned from the function
* @param createDestDir true to create the destination directory if it doesn't exist
* @param rwPerms copy the data with read-write permissions
**************************************************************/
qint64 moduleMiniPipeline::CopyAllSeriesData(QString modality, qint64 seriesid, QString destination, QString &msg, bool createDestDir, bool rwPerms) {
	qint64 numFilesCopied = 0;
    msg = "";

    series s(seriesid, modality, n); /* get the series info */
    if (!s.isValid) {
        msg = "Series was not valid: [" + s.msg + "]";
        return 0;
    }

    QString m;
    if (createDestDir)
        if (!n->MakePath(destination,m)) {
            msg = "Unable to create output path [" + destination + "] because of error [" + m + "]";
            return 0;
        }

    /* MR modality: copy thebeh data. Otherwise copy the raw data */
    QString systemstring;
    if (modality.toLower() == "mr")
        systemstring = "cp -uvf " + s.behpath + "/* " + destination + "/";
    else
        systemstring = "cp -uvf " + s.datapath + "/* " + destination + "/";

    msg += n->SystemCommand(systemstring, true, false);

    if (rwPerms) {
        systemstring = "chmod -R 777 " + destination;
        msg += n->SystemCommand(systemstring, true, false);
    }

	qint64 c;
	qint64 b;
    n->GetDirSizeAndFileCount(destination,c,b);

    numFilesCopied = c;

    return numFilesCopied;
}


/* ---------------------------------------------------------- */
/* --------- InsertMeasure ---------------------------------- */
/* ---------------------------------------------------------- */
bool moduleMiniPipeline::InsertMeasure(qint64 enrollmentid, qint64 studyid, qint64 seriesid, QString measureName, QString value, QString instrument, QDateTime startDate, QDateTime endDate, int duration, QString rater, int &numInserts, QString &msg) {

    QSqlQuery q;
    numInserts = 0;
    msg = "";

    if (enrollmentid < 0) {
        msg = QString("Invalid enrollmentID [%1]").arg(enrollmentid);
        return false;
    }

    /* get the measure name ID */
    int measureNameID;
    q.prepare("select measurename_id from measurenames where measure_name = :measurename");
    q.bindValue(":measurename", measureName);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        measureNameID = q.value("measurename_id").toInt();
    }
    else {
        q.prepare("insert into measurenames (measure_name) values (:measurename)");
        q.bindValue(":measurename", measureName);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        measureNameID = q.lastInsertId().toInt();
    }

    /* get the instrument name ID */
    int instrumentNameID;
    q.prepare("select measureinstrument_id from measureinstruments where instrument_name = :instrument");
    q.bindValue(":instrument", instrument);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        instrumentNameID = q.value("measureinstrument_id").toInt();
    }
    else {
        q.prepare("insert into measureinstruments (instrument_name) values (:instrument)");
        q.bindValue(":instrument", instrument);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        instrumentNameID = q.lastInsertId().toInt();
    }

    q.prepare("insert ignore into measures (enrollment_id, study_id, series_id, instrumentname_id, measurename_id, measure_value, measure_rater, measure_startdate, measure_enddate, measure_duration, measure_entrydate, measure_createdate, measure_modifydate) values (:enrollmentid, :studyid, :seriesid, :instrumentnameid, :measurenameid, :value, :measurerater, :startdate, :enddate, :duration, now(), now(), now()) on duplicate key update study_id = :studyid, series_id = :seriesid, measurename_id = :measurenameid, measure_value = :value, instrumentname_id = :instrumentnameid, measure_startdate = :startdate, measure_enddate = :enddate, measure_modifydate = now()");
    q.bindValue(":enrollmentid", enrollmentid);
    q.bindValue(":studyid", studyid);
    q.bindValue(":seriesid", seriesid);
    q.bindValue(":measurenameid", measureNameID);
    q.bindValue(":value", value);
    q.bindValue(":measurerater", rater);
    q.bindValue(":instrumentnameid", instrumentNameID);
    q.bindValue(":startdate", startDate.toString(Qt::ISODate));
    q.bindValue(":enddate", endDate.toString(Qt::ISODate));
    q.bindValue(":duration", duration);

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
    numInserts = 1;

    return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertVital ------------------------------------ */
/* ---------------------------------------------------------- */
int moduleMiniPipeline::InsertVital(qint64 enrollmentID, QString vitalName, QString value, QString notes, QString vitalType, QDateTime vitalStartDate, QDateTime vitalEndDate, int duration) {

    QSqlQuery q;

    /* get the vital name ID */
    int vitalNameID;
    q.prepare("select vitalname_id from vitalnames where vital_name = :vitalname");
    q.bindValue(":vitalname", vitalName);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        vitalNameID = q.value("vitalname_id").toInt();
    }
    else {
        q.prepare("insert into vitalnames (vital_name) values (:vitalname)");
        q.bindValue(":vitalname", vitalName);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        vitalNameID = q.lastInsertId().toInt();
    }

    q.prepare("insert ignore into vitals (enrollment_id, vitalname_id, vital_value, vital_notes, vital_date, vital_startdate, vital_enddate, vital_duration, vital_type, vital_createdate, vital_modifydate) values (:enrollmentid, :vitalnameid, :value, :notes, :vitaldate, :startdate, :enddate, :duration, :vitaltype, now(), now()) on duplicate key update vitalname_id = :vitalnameid, vital_value = :value, vital_type = :vitaltype, vital_notes = :notes, vital_date = :vitaldate, vital_modifydate = now()");
    q.bindValue(":enrollmentid", enrollmentID);
    q.bindValue(":vitalnameid", vitalNameID);
    q.bindValue(":value", value);
    q.bindValue(":vitaltype", vitalType);
    q.bindValue(":notes", notes);
    q.bindValue(":vitaldate", vitalStartDate.toString(Qt::ISODate));
    q.bindValue(":startdate", vitalStartDate.toString(Qt::ISODate));
    q.bindValue(":enddate", vitalEndDate);
    q.bindValue(":duration", duration);

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- InsertDrug ------------------------------------- */
/* ---------------------------------------------------------- */
int moduleMiniPipeline::InsertDrug(qint64 enrollmentID, QDateTime startDate, QDateTime endDate, QString doseAmount, QString doseFreq, QString route, QString drugName, QString drugType, QString doseUnit, QString doseFreqModifier, double doseFreqValue, QString doseFreqUnit) {

    doseFreq = "";

    QSqlQuery q;

    /* get the drug name ID */
    int drugNameID;
    q.prepare("select vitalname_id from vitalnames where vital_name = :vitalname");
    q.bindValue(":vitalname", drugName);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        drugNameID = q.value("vitalname_id").toInt();
    }
    else {
        q.prepare("insert into vitalnames (vital_name) values (:vitalname)");
        q.bindValue(":vitalname", drugName);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
        drugNameID = q.lastInsertId().toInt();
    }

    q.prepare("insert ignore into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drug_route, drugname_id, drug_type, drug_doseunit, drug_frequencymodifier, drug_frequencyvalue, drug_frequencyunit, vital_createdate, vital_modifydate) values (:enrollmentid, :startdate, :enddate, :doseamount, :route, :drugnameid, :drugtype, :doseunit, :freqmodifier, :freqvalue, :frequnit, now(), now()) on duplicate key update drugname_id = :drugnameid, drug_startdate = :startdate, drug_enddate = :enddate, drug_doseamount = :doseamount, drug_route = :route, drug_type = :drugtype, drug_doseunit = :doseunit, drug_frequencymodifier = :freqmodifier, drug_frequencyvalue = :freqvalue, drug_frequencyunit = :frequnit, drug_modifydate = now()");
    q.bindValue(":enrollmentid", enrollmentID);
    q.bindValue(":startdate", startDate);
    q.bindValue(":enddate", endDate);
    q.bindValue(":doseamount", doseAmount);
    q.bindValue(":route", route);
    q.bindValue(":drugnameid", drugNameID);
    q.bindValue(":drugtype", drugType);
    q.bindValue(":doseunit", doseUnit);
    q.bindValue(":freqmodifier", doseFreqModifier);
    q.bindValue(":freqvalue", doseFreqValue);
    q.bindValue(":frequnit", doseFreqUnit);

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- AppendMiniPipelineLog -------------------------- */
/* ---------------------------------------------------------- */
void moduleMiniPipeline::AppendMiniPipelineLog(QString log, int jobid) {
    QSqlQuery q;

    log = "[" + n->CreateCurrentDateTime() + "] " + log + "\n";

    q.prepare("update minipipeline_jobs set mp_log = concat(mp_log, :log) where minipipelinejob_id = :jobid");
    q.bindValue(":jobid", jobid);
    q.bindValue(":log", log);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

}
