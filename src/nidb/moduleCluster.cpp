/* ------------------------------------------------------------------------------
  NIDB moduleCluster.cpp
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

#include <QSqlQuery>
#include "moduleCluster.h"
#include "analysis.h"


/* ---------------------------------------------------------- */
/* --------- moduleCluster ---------------------------------- */
/* ---------------------------------------------------------- */
moduleCluster::moduleCluster(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleCluster --------------------------------- */
/* ---------------------------------------------------------- */
moduleCluster::~moduleCluster()
{

}


/* ---------------------------------------------------------- */
/* --------- PipelineCheckin -------------------------------- */
/* ---------------------------------------------------------- */
bool moduleCluster::PipelineCheckin(QString analysisid, QString status, QString message, QString command, QString &m) {

    m = "";
    QString hostname = QHostInfo::localHostName();
    QSqlQuery q;
    qint64 id;

    /* check if the analysis ID is valid */
    if (IsInt(analysisid)) {
        id = analysisid.toInt();
        q.prepare("select * from analysis where analysis_id = :analysisid");
        q.bindValue(":analysisid",id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() != 1) {
            m = QString("Analysis ID [%1] [%2] not found").arg(analysisid).arg(analysisid.toInt());
            return false;
        }
    }
    else {
        m = "analysisID is not an integer";
        return false;
    }

    if (status == "started") {
        q.prepare("update analysis set analysis_status = :status, analysis_statusmessage = :message, analysis_statusdatetime = now(), analysis_clusterstartdate = now(), analysis_hostname = :hostname where analysis_id = :analysisid");
        q.bindValue(":status", status);
        q.bindValue(":message", message);
        q.bindValue(":hostname", hostname);
        q.bindValue(":analysisid", id);
    }
    else if (status == "startedrerun") {
        q.prepare("update analysis set analysis_status = :status, analysis_statusmessage = :message, analysis_statusdatetime = now(), analysis_hostname = :hostname where analysis_id = :analysisid");
        q.bindValue(":status", status);
        q.bindValue(":message", message);
        q.bindValue(":hostname", hostname);
        q.bindValue(":analysisid", id);
    }
    else if (status == "startedsupplement") {
        q.prepare("update analysis set analysis_status = :status, analysis_statusmessage = :message, analysis_statusdatetime = now(), analysis_hostname = :hostname where analysis_id = :analysisid");
        q.bindValue(":status", status);
        q.bindValue(":message", message);
        q.bindValue(":hostname", hostname);
        q.bindValue(":analysisid", id);
    }
    else if (status == "complete") {
        q.prepare("update analysis set analysis_status = :status, analysis_statusmessage = :message, analysis_statusdatetime = now(), analysis_clusterenddate = now(), analysis_hostname = :hostname where analysis_id = :analysisid");
        q.bindValue(":status", status);
        q.bindValue(":message", message);
        q.bindValue(":hostname", hostname);
        q.bindValue(":analysisid", id);
    }
    else if (status == "completererun") {
        q.prepare("update analysis set analysis_status = 'complete', analysis_statusmessage = :message, analysis_rerunresults = 0 where analysis_id = :analysisid");
        q.bindValue(":message", message);
        q.bindValue(":analysisid", id);
    }
    else if (status == "completesupplement") {
        q.prepare("update analysis set analysis_status = 'complete', analysis_statusmessage = :message, analysis_rerunresults = 0, analysis_runsupplement = 0 where analysis_id = :analysisid");
        q.bindValue(":message", message);
        q.bindValue(":analysisid", id);
    }
    else {
        q.prepare("update analysis set analysis_status = :status, analysis_statusmessage = :message, analysis_rerunresults = 0, analysis_statusdatetime = now(), analysis_hostname = :hostname where analysis_id = :analysisid");
        q.bindValue(":status", status);
        q.bindValue(":message", message);
        q.bindValue(":hostname", hostname);
        q.bindValue(":analysisid", id);
    }

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    QString msg = message;
    if (command.trimmed() != "")
        msg += " [" + command + "]";

    q.prepare("insert into analysis_history (analysis_id, analysis_event, analysis_hostname, event_message) values (:analysisid, :status, :hostname, :msg)");
    q.bindValue(":analysisid", id);
    q.bindValue(":status", status);
    q.bindValue(":hostname", hostname);
    q.bindValue(":msg", msg);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ResultInsert ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleCluster::ResultInsert(QString paramAnalysisID, QString paramResultText, QString paramResultNumber, QString paramResultFile, QString paramResultImage, QString paramResultDesc, QString paramResultUnit, QString &m) {

    m = "";
    QSqlQuery q;
    qint64 id;

    /* check if the analysis ID is valid */
    if (IsInt(paramAnalysisID)) {
        id = paramAnalysisID.toInt();
        q.prepare("select * from analysis where analysis_id = :analysisid");
        q.bindValue(":analysisid",id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() != 1) {
            m = QString("analysisID string[%1]  int[%2] not found. q.size() [%3]").arg(paramAnalysisID).arg(paramAnalysisID.toInt()).arg(q.size());
            return false;
        }
    }
    else {
        m = "analysisID is not an integer";
        return false;
    }

    /* check if there is anything to insert, and if it's valid */
    if ((paramResultText == "") && (paramResultFile == "") && (paramResultNumber == "")) {
        m = "Text, File, and Number values are all blank. There is nothing to insert.";
        return false;
    }
    if ((paramResultText != "") && (paramResultFile != "") && (paramResultNumber != "")) {
        m = "Text, File, and Number results specified. Only one type of result can be specified at a time.";
        return false;
    }
    if ((paramResultText != "") && (paramResultFile != "")) {
        m = "Text and File results specified. Only one type of result can be specified at a time.";
        return false;
    }
    if ((paramResultNumber != "") && (paramResultFile != "")) {
        m = "Number and File results specified. Only one type of result can be specified at a time.";
        return false;
    }
    if ((paramResultText != "") && (paramResultNumber != "")) {
        m = "Text and Number results specified. Only one type of result can be specified at a time.";
        return false;
    }
    if (paramResultDesc == "") {
        m = "Description of the result is blank. You must include a description/label of this result.";
        return false;
    }
    if (paramResultNumber != "") {
        if (!IsNumber(paramResultNumber)) {
             m = QString("Number specified is not an integer or floating point value [%1]").arg(paramResultNumber);
             return false;
        }
    }

    /* insert the resultname, and/or get the resultname ID */
    qint64 resultnameid;
    q.prepare("select resultname_id from analysis_resultnames where result_name = :desc");
    q.bindValue(":desc",paramResultDesc);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        resultnameid = q.value("resultname_id").toInt();
    }
    else {
        q.prepare("insert ignore into analysis_resultnames (result_name) values (:desc)");
        q.bindValue(":desc",paramResultDesc);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        resultnameid = q.lastInsertId().toLongLong();
        m += "Inserted analysis_resultname [" + paramResultDesc + "]";
    }

    /* insert a text result */
    if (paramResultText != "") {
        q.prepare("insert ignore into analysis_results (analysis_id, result_type, result_nameid, result_text) values (:analysisid, 't', :resultnameid, :text) on duplicate key update result_count=result_count+1");
        q.bindValue(":analysisid", paramAnalysisID.toInt());
        q.bindValue(":resultnameid", resultnameid);
        q.bindValue(":text", paramResultText);
        QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        m += "Inserted analysis result [" + sql + "]";
    }

    /* insert a number result */
    if (paramResultNumber != "") {
        /* insert the units, and/or get the unit ID */
        qint64 resultunitid;
        q.prepare("select resultunit_id from analysis_resultunit where result_unit = :unit");
        q.bindValue(":unit",paramResultUnit);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            resultunitid = q.value("resultunit_id").toInt();
        }
        else {
            q.prepare("insert ignore into analysis_resultunit (result_unit) values (:unit)");
            q.bindValue(":unit",paramResultUnit);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            resultunitid = q.lastInsertId().toLongLong();
        }

        q.prepare("insert ignore into analysis_results (analysis_id, result_type, result_nameid, result_unitid, result_value) values (:analysisid, 'v', :resultnameid, :resultunitid, :value) on duplicate key update result_count=result_count+1");
        q.bindValue(":analysisid", paramAnalysisID.toInt());
        q.bindValue(":resultnameid", resultnameid);
        q.bindValue(":resultunitid", resultunitid);
        if (IsInt(paramResultNumber))
            q.bindValue(":value", static_cast<double>(paramResultNumber.toInt())); /* type casting... I know. But the user could have passed an int for a value, and the database only accepts double */
        else
            q.bindValue(":value", paramResultNumber.toDouble());
        QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        m += "Inserted analysis result [" + sql + "]";
    }

    /* insert a file result */
    if (paramResultFile != "") {
        q.prepare("insert ignore into analysis_results (analysis_id, result_type, result_nameid, result_filename) values (:analysisid, 'f', :resultnameid, :filename) on duplicate key update result_count=result_count+1");
        q.bindValue(":analysisid", paramAnalysisID.toInt());
        q.bindValue(":resultnameid", resultnameid);
        q.bindValue(":filename", paramResultFile);
        QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        m += "Inserted analysis result [" + sql + "]";
    }

    /* insert an image result */
    if (paramResultImage != "") {
        q.prepare("insert ignore into analysis_results (analysis_id, result_type, result_nameid, result_filename) values (:analysisid, 'i', :resultnameid, :filename) on duplicate key update result_count=result_count+1");
        q.bindValue(":analysisid", paramAnalysisID.toInt());
        q.bindValue(":resultnameid", resultnameid);
        q.bindValue(":filename", paramResultImage);
        QString sql = n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        m += "Inserted analysis result [" + sql + "]";
    }

    //Print(m);
    return true;
}


/* ---------------------------------------------------------- */
/* --------- UpdateAnalysis --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleCluster::UpdateAnalysis(QString analysisid, QString &m) {

    m = "";

    QSqlQuery q;
    qint64 id;

    /* check if the analysis ID is valid */
    if (IsInt(analysisid)) {
        id = analysisid.toInt();
        q.prepare("select * from analysis where analysis_id = :analysisid");
        q.bindValue(":analysisid",id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() != 1) {
            m = QString("analysisID string[%1]  int[%2] not found. q.size() [%3]").arg(analysisid).arg(analysisid.toInt()).arg(q.size());
            return false;
        }
    }
    else {
        m = "analysisID is not an integer";
        return false;
    }

    /* get the analysis info. Also checks if the analysis directory exists, and returns it if it does */
    analysis a(id, n);
    if (!a.isValid) {
        m = "Analysis was not valid: [" + a.msg + "]";
        return false;
    }

    m = "Getting directory size for [" + a.analysispath + "]";
    qint64 c;
    qint64 b;
    GetDirSizeAndFileCount(a.analysispath, c, b, true);

    q.prepare("update analysis set analysis_disksize = :disksize, analysis_numfiles = :numfiles where analysis_id = :analysisid");
    q.bindValue(":disksize", b);
    q.bindValue(":numfiles", c);
    q.bindValue(":analysisid", id);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CheckCompleteAnalysis -------------------------- */
/* ---------------------------------------------------------- */
bool moduleCluster::CheckCompleteAnalysis(QString analysisid, QString &m) {

    m = "";

    QSqlQuery q;
    qint64 id;

    /* check if the analysis ID is valid */
    if (IsInt(analysisid)) {
        id = analysisid.toInt();
        q.prepare("select * from analysis where analysis_id = :analysisid");
        q.bindValue(":analysisid",id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() != 1) {
            m = QString("analysisID string[%1]  int[%2] not found. q.size() [%3]").arg(analysisid).arg(analysisid.toInt()).arg(q.size());
            return false;
        }
    }
    else {
        m = "analysisID is not an integer";
        return false;
    }

    /* get the analysis info. Also checks if the analysis directory exists, and returns it if it does */
    analysis a(id, n);
    if (!a.isValid) {
        m = "Analysis was not valid: [" + a.msg + "]";
        return false;
    }

    /* Check the analysispath to see if the required file(s) exist.
     * Get a list of expected files from the database */
    q.prepare("select pipeline_completefiles from pipelines a left join analysis b on a.pipeline_id = b.pipeline_id where b.analysis_id = :analysisid");
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    QString completefiles = q.value("pipeline_completefiles").toString().trimmed();
    QStringList filelist = completefiles.split(',');

    Print("Checking if analysis should be marked successful, based on the successful file list");
    int iscomplete = 1;
    for(int i=0; i<filelist.size(); i++) {
        QString filepath = a.analysispath + "/" + filelist[i];
        QFile f(filepath);
        if (!f.exists()) {
            Print("[" + filepath + "] exists");
            iscomplete = 0;
            break;
        }
        else {
            Print("[" + filepath + "] does not exist");
        }
    }

    q.prepare("update analysis set analysis_iscomplete = :iscomplete where analysis_id = :analysisid");
    q.bindValue(":iscomplete", iscomplete);
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}
