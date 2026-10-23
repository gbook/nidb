/* ------------------------------------------------------------------------------
  NIDB moduleAudit.cpp
  Copyright (C) 2004 - 2026
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
#include <QElapsedTimer>
#include "moduleAudit.h"


/* ---------------------------------------------------------- */
/* --------- moduleAudit ------------------------------------ */
/* ---------------------------------------------------------- */
moduleAudit::moduleAudit(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleAudit ----------------------------------- */
/* ---------------------------------------------------------- */
moduleAudit::~moduleAudit()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run the audits requested from the website (adminaudits.php). Requested audits
 * have a row in `audits` with status 'started'. Each is run in the order requested.
 * @return 1 if any audit ran (keep the log), 0 otherwise
 */
int moduleAudit::Run() {
    n->Log("Entering the audit module");

    /* get the list of requested audits. Read them all first, so the query isn't
       held open while the (possibly long) audits run */
    struct auditRequest {
        qint64 auditid;
        int projectid;
        QString type;
        bool fixErrors;
    };
    QList<auditRequest> requests;

    QSqlQuery q;
    q.prepare("select audit_id, project_id, audit_type, audit_fixerrors from audits where audit_status = 'started' order by audit_id");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        auditRequest r;
        r.auditid = q.value("audit_id").toLongLong();
        r.projectid = q.value("project_id").toInt();
        r.type = q.value("audit_type").toString();
        r.fixErrors = q.value("audit_fixerrors").toBool();
        requests.append(r);
    }

    if (requests.isEmpty()) {
        n->Log("No audits requested");
        n->Log("Leaving the audit module");
        return 0;
    }

    n->Log(QString("Found [%1] requested audits").arg(requests.size()));

    foreach (auditRequest r, requests) {
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 1; }

        /* claim the audit. If another instance of this module got to it first, the status
           will no longer be 'started' and no row is updated */
        q.prepare("update audits set audit_status = 'auditing', audit_message = 'Auditing' where audit_id = :auditid and audit_status = 'started'");
        q.bindValue(":auditid", r.auditid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.numRowsAffected() < 1) {
            n->Log(QString("Audit [%1] was already claimed by another instance of this module, skipping").arg(r.auditid));
            continue;
        }

        n->Log(QString("Starting audit [%1] type [%2] projectid [%3] fixErrors [%4]").arg(r.auditid).arg(r.type).arg(r.projectid).arg(r.fixErrors));

        QString m;
        bool ok = false;
        if (r.type == "seriesfiles") {
            ok = AuditSeriesFiles(r.auditid, r.projectid, r.fixErrors, m);
        }
        else if (r.type == "analysisfiles") {
            ok = AuditAnalysisFiles(r.auditid, r.projectid, r.fixErrors, m);
        }
        else {
            m = QString("Audit type [%1] is not implemented yet").arg(r.type);
        }

        /* a stopped audit already has its status, message, and end date set by the website, so only add the summary */
        if (GetAuditStatus(r.auditid) == "stopped") {
            q.prepare("update audits set audit_message = concat(ifnull(audit_message, ''), '. ', :msg) where audit_id = :auditid");
            q.bindValue(":msg", m);
            q.bindValue(":auditid", r.auditid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            n->Log(QString("Audit [%1] stopped: %2").arg(r.auditid).arg(m));
            continue;
        }

        SetAuditStatus(r.auditid, ok ? "complete" : "error", m, true);
        n->Log(QString("Audit [%1] %2: %3").arg(r.auditid).arg(ok ? "completed" : "failed").arg(m));
    }

    n->Log("Leaving the audit module");
    return 1;
}


/* ---------------------------------------------------------- */
/* --------- AuditSeriesFiles ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Compare the file counts and sizes stored for each MR series against the files on disk.
 * Three sets of files are checked, each against its own columns:
 *   data - the series' <data_type>/ directory (mr_series.numfiles, series_size)
 *   beh  - the series' beh/ directory (mr_series.numfiles_beh, beh_size)
 *   QC   - the series' qa/ (mriqa module) and qc/ (qc module) directories (mr_series.qc_numfiles, qc_size)
 * Any difference is logged to `audit_log`, and if fixErrors is true, the database is updated to
 * match the disk. Stored values that are NULL, with nothing on disk, are not an issue.
 * @param auditid The audit_id
 * @param projectid The project to audit, or 0 for all projects
 * @param fixErrors true to update the database to match what is on disk
 * @param m Summary message
 * @return true if the audit ran to completion
 */
bool moduleAudit::AuditSeriesFiles(qint64 auditid, int projectid, bool fixErrors, QString &m) {

    /* get the list of series to check. Series from deleted subjects are skipped, because their
       files are no longer in the archive. Read the whole list first, so other queries can be
       run while looping through it */
    struct seriesInfo {
        qint64 seriesid;
        QString uid;
        int studynum;
        int seriesnum;
        QString datatype;
        bool numFilesIsNull;
        bool sizeIsNull;
        qint64 numFiles;
        qint64 size;
        bool behNumFilesIsNull;
        bool behSizeIsNull;
        qint64 behNumFiles;
        qint64 behSize;
        bool qcNumFilesIsNull;
        bool qcSizeIsNull;
        qint64 qcNumFiles;
        qint64 qcSize;
    };
    QList<seriesInfo> seriesList;

    QSqlQuery q;
    QString sql = "select a.mrseries_id, a.series_num, a.data_type, a.numfiles, a.series_size, a.numfiles_beh, a.beh_size, a.qc_numfiles, a.qc_size, b.study_num, d.uid from mr_series a inner join studies b on a.study_id = b.study_id inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id where d.isactive = 1";
    if (projectid > 0)
        sql += " and c.project_id = :projectid";
    sql += " order by a.mrseries_id";
    q.prepare(sql);
    if (projectid > 0)
        q.bindValue(":projectid", projectid);
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) {
        m = "SQL error getting the list of series. See the module log";
        return false;
    }
    while (q.next()) {
        seriesInfo s;
        s.seriesid = q.value("mrseries_id").toLongLong();
        s.uid = q.value("uid").toString().trimmed();
        s.studynum = q.value("study_num").toInt();
        s.seriesnum = q.value("series_num").toInt();
        s.datatype = q.value("data_type").toString().trimmed();
        s.numFilesIsNull = q.value("numfiles").isNull();
        s.sizeIsNull = q.value("series_size").isNull();
        s.numFiles = q.value("numfiles").toLongLong();
        s.size = q.value("series_size").toLongLong();
        s.behNumFilesIsNull = q.value("numfiles_beh").isNull();
        s.behSizeIsNull = q.value("beh_size").isNull();
        s.behNumFiles = q.value("numfiles_beh").toLongLong();
        s.behSize = q.value("beh_size").toLongLong();
        s.qcNumFilesIsNull = q.value("qc_numfiles").isNull();
        s.qcSizeIsNull = q.value("qc_size").isNull();
        s.qcNumFiles = q.value("qc_numfiles").toLongLong();
        s.qcSize = q.value("qc_size").toLongLong();
        seriesList.append(s);
    }

    qint64 total = seriesList.size();
    SetAuditProgress(auditid, 0, total);
    n->Log(QString("Checking data, beh, and QC/QA files for [%1] MR series").arg(total));

    QString archivedir = n->cfg["archivedir"];
    qint64 numChecked = 0;
    qint64 numSkipped = 0;
    qint64 numNoDataType = 0;
    qint64 numIssues = 0;
    qint64 numFixed = 0;

    QElapsedTimer timer;
    timer.start();

    for (qint64 i = 0; i < total; i++) {
        const seriesInfo &s = seriesList.at(i);

        /* update the progress and check in periodically */
        if (timer.elapsed() > 2000) {
            QString stopReason;
            if (!AuditCheckIn(auditid, i, total, stopReason)) {
                m = QString("%1Checked %2 of %3 series. Found %4 issues, fixed %5").arg(stopReason).arg(i).arg(total).arg(numIssues).arg(numFixed);
                return false;
            }
            timer.restart();
        }

        /* a series without a valid path can't be checked */
        if ((s.uid == "") || (s.studynum < 1) || (s.seriesnum < 1)) {
            n->Log(QString("Skipping series [%1] because its path is invalid: uid [%2] studynum [%3] seriesnum [%4]").arg(s.seriesid).arg(s.uid).arg(s.studynum).arg(s.seriesnum));
            numSkipped++;
            continue;
        }

        QString seriespath = QString("%1/%2/%3/%4").arg(archivedir).arg(s.uid).arg(s.studynum).arg(s.seriesnum);
        QString seriesdesc = QString("MR series %1/%2/%3").arg(s.uid).arg(s.studynum).arg(s.seriesnum);
        numChecked++;

        /* data files are stored in the <data_type>/ directory, and are counted non-recursively, the
           same as when the series is archived. Without a valid data_type, the data directory is unknown */
        if ((s.datatype == "") || s.datatype.contains('/') || (s.datatype == "..") || (s.datatype == ".")) {
            n->Log(QString("Not checking data files for series [%1] because its data_type [%2] is invalid").arg(s.seriesid).arg(s.datatype));
            numNoDataType++;
        }
        else {
            QString datapath = seriespath + "/" + s.datatype;
            qint64 dataCount = 0, dataSize = 0;
            GetDirSizeAndFileCount(datapath, dataCount, dataSize);
            numIssues += CheckFileCountAndSize(auditid, fixErrors, "series", s.seriesid, seriesdesc + " data", datapath, "mr_series", "mrseries_id", "numfiles", "series_size", s.numFilesIsNull, s.numFiles, s.sizeIsNull, s.size, dataCount, dataSize, numFixed);
        }

        /* behavioral files are stored in the beh/ directory, also counted non-recursively */
        QString behpath = seriespath + "/beh";
        qint64 behCount = 0, behSize = 0;
        GetDirSizeAndFileCount(behpath, behCount, behSize);
        numIssues += CheckFileCountAndSize(auditid, fixErrors, "series", s.seriesid, seriesdesc + " beh", behpath, "mr_series", "mrseries_id", "numfiles_beh", "beh_size", s.behNumFilesIsNull, s.behNumFiles, s.behSizeIsNull, s.behSize, behCount, behSize, numFixed);

        /* QC/QA files are stored in the qa/ (mriqa module) and qc/ (qc module) directories */
        qint64 qaCount = 0, qaSize = 0, qcCount = 0, qcSize = 0;
        GetDirSizeAndFileCount(seriespath + "/qa", qaCount, qaSize, true);
        GetDirSizeAndFileCount(seriespath + "/qc", qcCount, qcSize, true);
        numIssues += CheckFileCountAndSize(auditid, fixErrors, "series", s.seriesid, seriesdesc + " QC/QA", seriespath + "/{qa,qc}", "mr_series", "mrseries_id", "qc_numfiles", "qc_size", s.qcNumFilesIsNull, s.qcNumFiles, s.qcSizeIsNull, s.qcSize, qaCount + qcCount, qaSize + qcSize, numFixed);
    }

    SetAuditProgress(auditid, total, total);

    m = QString("Checked %1 of %2 MR series. Found %3 issues, fixed %4").arg(numChecked).arg(total).arg(numIssues).arg(numFixed);
    if (numSkipped > 0)
        m += QString(". Skipped %1 series with an invalid path (see the module log)").arg(numSkipped);
    if (numNoDataType > 0)
        m += QString(". Did not check data files for %1 series with a blank or invalid data_type (see the module log)").arg(numNoDataType);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- AuditAnalysisFiles ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Compare the file count and size stored for each analysis (analysis.analysis_numfiles,
 * analysis_disksize) against the files on disk in the analysis directory. Any difference is
 * logged to `audit_log`, and if fixErrors is true, the database is updated to match the disk.
 * An analysis with no stored values (NULL) and no files on disk is not an issue. Analyses that
 * are still running are skipped, because their files are still changing.
 * @param auditid The audit_id
 * @param projectid The project to audit, or 0 for all projects
 * @param fixErrors true to update the database to match what is on disk
 * @param m Summary message
 * @return true if the audit ran to completion
 */
bool moduleAudit::AuditAnalysisFiles(qint64 auditid, int projectid, bool fixErrors, QString &m) {

    /* get the list of analyses to check. Analyses of deleted subjects are skipped. Read the whole
       list first, so other queries can be run while looping through it. Only the columns needed
       to build the path are selected, rather than loading each analysis object, because the
       analysis table includes large text columns */
    struct analysisInfo {
        qint64 analysisid;
        QString status;
        QString uid;
        int studynum;
        QString pipelinename;
        int pipelinelevel;
        QString pipelinedirstructure;
        bool numFilesIsNull;
        bool diskSizeIsNull;
        qint64 numFiles;
        qint64 diskSize;
    };
    QList<analysisInfo> analysisList;

    QSqlQuery q;
    QString sql = "select a.analysis_id, a.analysis_status, a.analysis_numfiles, a.analysis_disksize, b.study_num, d.uid, e.pipeline_name, e.pipeline_level, e.pipeline_dirstructure from analysis a inner join studies b on a.study_id = b.study_id inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id inner join pipelines e on a.pipeline_id = e.pipeline_id where d.isactive = 1";
    if (projectid > 0)
        sql += " and c.project_id = :projectid";
    sql += " order by a.analysis_id";
    q.prepare(sql);
    if (projectid > 0)
        q.bindValue(":projectid", projectid);
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) {
        m = "SQL error getting the list of analyses. See the module log";
        return false;
    }
    while (q.next()) {
        analysisInfo a;
        a.analysisid = q.value("analysis_id").toLongLong();
        a.status = q.value("analysis_status").toString();
        a.uid = q.value("uid").toString().trimmed();
        a.uid.replace('\u0000', "");
        a.studynum = q.value("study_num").toInt();
        a.pipelinename = q.value("pipeline_name").toString().trimmed();
        a.pipelinelevel = q.value("pipeline_level").toInt();
        a.pipelinedirstructure = q.value("pipeline_dirstructure").toString().trimmed();
        a.numFilesIsNull = q.value("analysis_numfiles").isNull();
        a.diskSizeIsNull = q.value("analysis_disksize").isNull();
        a.numFiles = q.value("analysis_numfiles").toLongLong();
        a.diskSize = q.value("analysis_disksize").toLongLong();
        analysisList.append(a);
    }

    qint64 total = analysisList.size();
    SetAuditProgress(auditid, 0, total);
    n->Log(QString("Checking files for [%1] analyses").arg(total));

    /* analyses in these states are still being set up or run, so their files are still changing */
    QStringList runningStatuses = {"pending", "processing", "submitted", "started", "rerunresults"};

    qint64 numChecked = 0;
    qint64 numSkippedInvalid = 0;
    qint64 numSkippedRunning = 0;
    qint64 numIssues = 0;
    qint64 numFixed = 0;

    QElapsedTimer timer;
    timer.start();

    for (qint64 i = 0; i < total; i++) {
        const analysisInfo &a = analysisList.at(i);

        /* update the progress and check in periodically */
        if (timer.elapsed() > 2000) {
            QString stopReason;
            if (!AuditCheckIn(auditid, i, total, stopReason)) {
                m = QString("%1Checked %2 of %3 analyses. Found %4 issues, fixed %5").arg(stopReason).arg(i).arg(total).arg(numIssues).arg(numFixed);
                return false;
            }
            timer.restart();
        }

        if (runningStatuses.contains(a.status)) {
            numSkippedRunning++;
            continue;
        }

        /* build the analysis path. This must match analysis::LoadAnalysisInfo() */
        QString analysispath;
        if (a.pipelinelevel == 2)
            analysispath = QString("%1/%2/%3/%4").arg(n->cfg["groupanalysisdir"]).arg(a.uid).arg(a.studynum).arg(a.pipelinename);
        else if (a.pipelinedirstructure == "b")
            analysispath = QString("%1/%2/%3/%4").arg(n->cfg["analysisdirb"]).arg(a.pipelinename).arg(a.uid).arg(a.studynum);
        else
            analysispath = QString("%1/%2/%3/%4").arg(n->cfg["analysisdir"]).arg(a.uid).arg(a.studynum).arg(a.pipelinename);

        /* an analysis without a valid path can't be checked */
        if ((a.uid == "") || (a.studynum < 1) || (a.pipelinename == "") || analysispath.contains("//")) {
            n->Log(QString("Skipping analysis [%1] because its path is invalid [%2]").arg(a.analysisid).arg(analysispath));
            numSkippedInvalid++;
            continue;
        }

        qint64 diskCount = 0, diskSize = 0;
        GetDirSizeAndFileCount(analysispath, diskCount, diskSize, true);
        numChecked++;

        numIssues += CheckFileCountAndSize(auditid, fixErrors, "analysis", a.analysisid, QString("Analysis %1/%2/%3").arg(a.uid).arg(a.studynum).arg(a.pipelinename), analysispath, "analysis", "analysis_id", "analysis_numfiles", "analysis_disksize", a.numFilesIsNull, a.numFiles, a.diskSizeIsNull, a.diskSize, diskCount, diskSize, numFixed);
    }

    SetAuditProgress(auditid, total, total);

    m = QString("Checked %1 of %2 analyses. Found %3 issues, fixed %4").arg(numChecked).arg(total).arg(numIssues).arg(numFixed);
    if (numSkippedRunning > 0)
        m += QString(". Skipped %1 analyses that are still running").arg(numSkippedRunning);
    if (numSkippedInvalid > 0)
        m += QString(". Skipped %1 analyses with an invalid path (see the module log)").arg(numSkippedInvalid);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CheckFileCountAndSize -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Compare an object's stored file count and size against what is on disk, log any
 * differences to `audit_log`, and if fixErrors is true, update the database to match the disk.
 * Stored values that are NULL, with nothing on disk, are not an issue.
 * @param auditid The audit_id
 * @param fixErrors true to update the database to match what is on disk
 * @param objectType The audit_log.object_type
 * @param objectid The database row ID of the object
 * @param objectDesc Human readable description of the object, used in the log messages
 * @param path Path on disk that was checked
 * @param table Table containing the stored values (not user input)
 * @param idColumn Primary key column of the table (not user input)
 * @param countColumn Column storing the file count (not user input)
 * @param sizeColumn Column storing the size (not user input)
 * @param dbCountIsNull true if the stored count is NULL
 * @param dbCount Stored count
 * @param dbSizeIsNull true if the stored size is NULL
 * @param dbSize Stored size, in bytes
 * @param diskCount Count on disk
 * @param diskSize Size on disk, in bytes
 * @param numFixed Incremented for each issue fixed
 * @return The number of issues found (0, 1, or 2)
 */
int moduleAudit::CheckFileCountAndSize(qint64 auditid, bool fixErrors, QString objectType, qint64 objectid, QString objectDesc, QString path, QString table, QString idColumn, QString countColumn, QString sizeColumn, bool dbCountIsNull, qint64 dbCount, bool dbSizeIsNull, qint64 dbSize, qint64 diskCount, qint64 diskSize, qint64 &numFixed) {

    /* never recorded, and nothing on disk */
    if (dbCountIsNull && dbSizeIsNull && (diskCount == 0) && (diskSize == 0))
        return 0;

    bool countMismatch = dbCountIsNull || (dbCount != diskCount);
    bool sizeMismatch = dbSizeIsNull || (dbSize != diskSize);
    if (!countMismatch && !sizeMismatch)
        return 0;

    QString dbCountStr = dbCountIsNull ? "NULL" : QString::number(dbCount);
    QString dbSizeStr = dbSizeIsNull ? "NULL" : QString::number(dbSize);

    /* fix the issue first, so the log records the outcome. fixed: -1 not attempted, 0 failed, 1 fixed */
    int fixed = -1;
    if (fixErrors) {
        QSqlQuery q;
        q.prepare(QString("update %1 set %2 = :numfiles, %3 = :size where %4 = :id").arg(table).arg(countColumn).arg(sizeColumn).arg(idColumn));
        q.bindValue(":numfiles", diskCount);
        q.bindValue(":size", diskSize);
        q.bindValue(":id", objectid);
        bool fixSuccess = false;
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &fixSuccess);
        fixed = fixSuccess ? 1 : 0;
    }

    int numIssues = 0;
    if (countMismatch) {
        QString msg = QString("%1 file count: database [%2], disk [%3] (%4)").arg(objectDesc).arg(dbCountStr).arg(diskCount).arg(path);
        QString fixedMsg = (fixed == 1) ? QString("Set %1 from [%2] to [%3]").arg(countColumn).arg(dbCountStr).arg(diskCount) : (fixed == 0 ? QString("SQL error updating %1. See the module log").arg(countColumn) : "");
        LogAuditIssue(auditid, "fileCountMismatch", objectType, objectid, msg, fixed, fixedMsg);
        numIssues++;
        if (fixed == 1) numFixed++;
    }
    if (sizeMismatch) {
        QString msg = QString("%1 file size: database [%2], disk [%3] bytes (%4)").arg(objectDesc).arg(dbSizeStr).arg(diskSize).arg(path);
        QString fixedMsg = (fixed == 1) ? QString("Set %1 from [%2] to [%3]").arg(sizeColumn).arg(dbSizeStr).arg(diskSize) : (fixed == 0 ? QString("SQL error updating %1. See the module log").arg(sizeColumn) : "");
        LogAuditIssue(auditid, "fileSizeMismatch", objectType, objectid, msg, fixed, fixedMsg);
        numIssues++;
        if (fixed == 1) numFixed++;
    }

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- AuditCheckIn ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Periodic check-in while an audit is running: update the progress, check in the module,
 * and check whether the audit should stop
 * @param auditid The audit_id
 * @param progress Number of objects checked so far
 * @param total Total number of objects to check
 * @param stopReason Set to a message prefix if the audit should stop because the module was disabled. Blank if stopped by the user
 * @return false if the audit should stop
 */
bool moduleAudit::AuditCheckIn(qint64 auditid, qint64 progress, qint64 total, QString &stopReason) {
    stopReason = "";
    SetAuditProgress(auditid, progress, total);
    n->ModuleRunningCheckIn();
    if (!n->ModuleCheckIfActive()) {
        stopReason = "Audit module was disabled. ";
        return false;
    }
    if (GetAuditStatus(auditid) == "stopped")
        return false;

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetAuditStatus --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get an audit's current status. The website can change it while the audit is running (ie, to 'stopped')
 * @param auditid The audit_id
 * @return The audit_status, or blank if the audit no longer exists
 */
QString moduleAudit::GetAuditStatus(qint64 auditid) {
    QSqlQuery q;
    q.prepare("select audit_status from audits where audit_id = :auditid");
    q.bindValue(":auditid", auditid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first())
        return q.value("audit_status").toString();
    return "";
}


/* ---------------------------------------------------------- */
/* --------- SetAuditStatus --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Set an audit's status and message
 * @param auditid The audit_id
 * @param status One of the audits.audit_status enum values
 * @param msg Message associated with the status
 * @param ended true to also set the audit's end date
 */
void moduleAudit::SetAuditStatus(qint64 auditid, QString status, QString msg, bool ended) {
    QSqlQuery q;
    if (ended)
        q.prepare("update audits set audit_status = :status, audit_message = :msg, audit_enddate = now() where audit_id = :auditid");
    else
        q.prepare("update audits set audit_status = :status, audit_message = :msg where audit_id = :auditid");
    q.bindValue(":status", status);
    q.bindValue(":msg", msg);
    q.bindValue(":auditid", auditid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SetAuditProgress ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Set the number of objects checked so far, and the total to check, for the progress display. Also records the check-in time
 * @param auditid The audit_id
 * @param progress Number of objects checked so far
 * @param total Total number of objects to check
 */
void moduleAudit::SetAuditProgress(qint64 auditid, qint64 progress, qint64 total) {
    QSqlQuery q;
    q.prepare("update audits set audit_objectprogress = :progress, audit_totalobjects = :total, audit_lastcheckin = now() where audit_id = :auditid");
    q.bindValue(":progress", progress);
    q.bindValue(":total", total);
    q.bindValue(":auditid", auditid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- LogAuditIssue ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Record an issue found by an audit in `audit_log`
 * @param auditid The audit_id
 * @param issueType One of the audit_log.issue_type enum values
 * @param objectType One of the audit_log.object_type enum values
 * @param objectid The database row ID of the object
 * @param msg Description of the issue
 * @param fixed -1 if no fix was attempted, 0 if the fix failed, 1 if fixed
 * @param fixedMsg Description of what was fixed
 */
void moduleAudit::LogAuditIssue(qint64 auditid, QString issueType, QString objectType, qint64 objectid, QString msg, int fixed, QString fixedMsg) {
    QSqlQuery q;
    if (fixed < 0) {
        q.prepare("insert into audit_log (audit_id, issue_type, object_type, object_id, issue_message, issue_fixed, issue_fixedmessage) values (:auditid, :issuetype, :objecttype, :objectid, :msg, null, null)");
    }
    else {
        q.prepare("insert into audit_log (audit_id, issue_type, object_type, object_id, issue_message, issue_fixed, issue_fixedmessage) values (:auditid, :issuetype, :objecttype, :objectid, :msg, :fixed, :fixedmsg)");
        q.bindValue(":fixed", fixed);
        q.bindValue(":fixedmsg", fixedMsg);
    }
    q.bindValue(":auditid", auditid);
    q.bindValue(":issuetype", issueType);
    q.bindValue(":objecttype", objectType);
    q.bindValue(":objectid", objectid);
    q.bindValue(":msg", msg);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}
