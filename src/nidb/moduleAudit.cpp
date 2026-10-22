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
 * @brief Compare the QC/QA file count and size stored for each MR series (mr_series.qc_numfiles,
 * qc_size) against the files on disk in the series' qa/ and qc/ directories. Any difference is
 * logged to `audit_log`, and if fixErrors is true, the database is updated to match the disk.
 * A series with no stored values (NULL) and no QC/QA files on disk is not an issue.
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
        bool qcNumFilesIsNull;
        bool qcSizeIsNull;
        qint64 qcNumFiles;
        qint64 qcSize;
    };
    QList<seriesInfo> seriesList;

    QSqlQuery q;
    QString sql = "select a.mrseries_id, a.series_num, a.qc_numfiles, a.qc_size, b.study_num, d.uid from mr_series a inner join studies b on a.study_id = b.study_id inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id where d.isactive = 1";
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
        s.qcNumFilesIsNull = q.value("qc_numfiles").isNull();
        s.qcSizeIsNull = q.value("qc_size").isNull();
        s.qcNumFiles = q.value("qc_numfiles").toLongLong();
        s.qcSize = q.value("qc_size").toLongLong();
        seriesList.append(s);
    }

    qint64 total = seriesList.size();
    SetAuditProgress(auditid, 0, total);
    n->Log(QString("Checking QC/QA files for [%1] MR series").arg(total));

    QString archivedir = n->cfg["archivedir"];
    qint64 numChecked = 0;
    qint64 numSkipped = 0;
    qint64 numIssues = 0;
    qint64 numFixed = 0;

    QElapsedTimer timer;
    timer.start();

    for (qint64 i = 0; i < total; i++) {
        const seriesInfo &s = seriesList.at(i);

        /* update the progress and check in periodically */
        if (timer.elapsed() > 2000) {
            SetAuditProgress(auditid, i, total);
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) {
                m = QString("Audit module was disabled. Stopped after checking %1 of %2 series. Found %3 issues, fixed %4").arg(i).arg(total).arg(numIssues).arg(numFixed);
                return false;
            }
            if (GetAuditStatus(auditid) == "stopped") {
                m = QString("Checked %1 of %2 series. Found %3 issues, fixed %4").arg(i).arg(total).arg(numIssues).arg(numFixed);
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

        /* QC/QA files are stored in the series' qa/ (mriqa module) and qc/ (qc module) directories */
        QString seriespath = QString("%1/%2/%3/%4").arg(archivedir).arg(s.uid).arg(s.studynum).arg(s.seriesnum);
        qint64 qaCount = 0, qaSize = 0, qcCount = 0, qcSize = 0;
        GetDirSizeAndFileCount(seriespath + "/qa", qaCount, qaSize, true);
        GetDirSizeAndFileCount(seriespath + "/qc", qcCount, qcSize, true);
        qint64 diskCount = qaCount + qcCount;
        qint64 diskSize = qaSize + qcSize;
        numChecked++;

        /* never recorded, and nothing on disk */
        if (s.qcNumFilesIsNull && s.qcSizeIsNull && (diskCount == 0) && (diskSize == 0))
            continue;

        bool countMismatch = s.qcNumFilesIsNull || (s.qcNumFiles != diskCount);
        bool sizeMismatch = s.qcSizeIsNull || (s.qcSize != diskSize);
        if (!countMismatch && !sizeMismatch)
            continue;

        QString dbCountStr = s.qcNumFilesIsNull ? "NULL" : QString::number(s.qcNumFiles);
        QString dbSizeStr = s.qcSizeIsNull ? "NULL" : QString::number(s.qcSize);

        /* fix the issue first, so the log records the outcome. fixed: -1 not attempted, 0 failed, 1 fixed */
        int fixed = -1;
        if (fixErrors) {
            QSqlQuery q2;
            q2.prepare("update mr_series set qc_numfiles = :numfiles, qc_size = :size where mrseries_id = :seriesid");
            q2.bindValue(":numfiles", diskCount);
            q2.bindValue(":size", diskSize);
            q2.bindValue(":seriesid", s.seriesid);
            bool fixSuccess = false;
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, &fixSuccess);
            fixed = fixSuccess ? 1 : 0;
        }

        if (countMismatch) {
            QString msg = QString("MR series %1/%2/%3 QC/QA file count: database [%4], disk [%5] (%6)").arg(s.uid).arg(s.studynum).arg(s.seriesnum).arg(dbCountStr).arg(diskCount).arg(seriespath);
            QString fixedMsg = (fixed == 1) ? QString("Set qc_numfiles from [%1] to [%2]").arg(dbCountStr).arg(diskCount) : (fixed == 0 ? "SQL error updating qc_numfiles. See the module log" : "");
            LogAuditIssue(auditid, "fileCountMismatch", "series", s.seriesid, msg, fixed, fixedMsg);
            numIssues++;
            if (fixed == 1) numFixed++;
        }
        if (sizeMismatch) {
            QString msg = QString("MR series %1/%2/%3 QC/QA file size: database [%4], disk [%5] bytes (%6)").arg(s.uid).arg(s.studynum).arg(s.seriesnum).arg(dbSizeStr).arg(diskSize).arg(seriespath);
            QString fixedMsg = (fixed == 1) ? QString("Set qc_size from [%1] to [%2]").arg(dbSizeStr).arg(diskSize) : (fixed == 0 ? "SQL error updating qc_size. See the module log" : "");
            LogAuditIssue(auditid, "fileSizeMismatch", "series", s.seriesid, msg, fixed, fixedMsg);
            numIssues++;
            if (fixed == 1) numFixed++;
        }
    }

    SetAuditProgress(auditid, total, total);

    m = QString("Checked %1 of %2 MR series. Found %3 issues, fixed %4").arg(numChecked).arg(total).arg(numIssues).arg(numFixed);
    if (numSkipped > 0)
        m += QString(". Skipped %1 series with an invalid path (see the module log)").arg(numSkipped);

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
