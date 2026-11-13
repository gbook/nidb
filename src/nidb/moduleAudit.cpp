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
#include <algorithm>
#include <cmath>
#include <QCryptographicHash>
#include <QRegularExpression>
#include "moduleAudit.h"
#include "imageio.h"


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
        QStringList options;
    };
    QList<auditRequest> requests;

    QSqlQuery q;
    q.prepare("select audit_id, project_id, audit_type, audit_fixerrors, audit_options from audits where audit_status = 'started' order by audit_id");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        auditRequest r;
        r.auditid = q.value("audit_id").toLongLong();
        r.projectid = q.value("project_id").toInt();
        r.type = q.value("audit_type").toString();
        r.fixErrors = q.value("audit_fixerrors").toBool();
        r.options = q.value("audit_options").toString().split(",", Qt::SkipEmptyParts);
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
        else if (r.type == "qualitycontrol") {
            ok = AuditQualityControl(r.auditid, r.projectid, r.options, m);
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
/* --------- AuditQualityControl ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run the selected quality control checks on one project. Issues are only reported, except
 * for the missing demographics fixes (fixsex, fixname, fixdob), which are only applied when selected
 * along with missingdemographics. The criteria for each check are listed in doc/audits.md
 * @param auditid The audit_id
 * @param projectid The project to audit. Must be a single project (not 0)
 * @param checks The checks to run (audits.audit_options): duplicatesubjectsproject, duplicatesubjects, duplicatestudies, empty, missingdemographics, invaliddob, dicommetadata. Also the missingdemographics fixes: fixsex, fixname, fixdob
 * @param m Summary message
 * @return true if the audit ran to completion
 */
bool moduleAudit::AuditQualityControl(qint64 auditid, int projectid, QStringList checks, QString &m) {

    /* the checks, in the order they are run */
    QStringList validChecks = {"duplicatesubjectsproject", "duplicatesubjects", "duplicatestudies", "empty", "missingdemographics", "invaliddob", "dicommetadata"};

    if (projectid < 1) {
        m = "Quality control audits can only be run on a single project";
        return false;
    }

    QStringList toRun;
    foreach (QString c, validChecks)
        if (checks.contains(c))
            toRun << c;
    if (toRun.isEmpty()) {
        m = "No quality control checks were selected";
        return false;
    }

    /* duplicatesubjects compares alternate IDs from every project, which includes the alternate IDs
       duplicatesubjectsproject compares, so running both would only report the same groups twice */
    if (toRun.contains("duplicatesubjects") && toRun.contains("duplicatesubjectsproject"))
        toRun.removeAll("duplicatesubjectsproject");

    /* fixes for the missing demographics check. These are options of that check, not checks of their own */
    bool fixSex = checks.contains("fixsex");
    bool fixName = checks.contains("fixname");
    bool fixDOB = checks.contains("fixdob");

    /* the DICOM check's series are listed up front, so the progress bar covers the whole audit.
       Each database check counts as one object, and each series in the DICOM check as one object */
    bool doDicom = toRun.contains("dicommetadata");
    QList<qcDicomSeries> seriesList;
    if (doDicom) {
        if (!QCLoadDicomSeries(projectid, seriesList)) {
            m = "SQL error getting the list of series. See the module log";
            return false;
        }
    }
    qint64 total = (toRun.size() - (doDicom ? 1 : 0)) + seriesList.size();
    qint64 progress = 0;
    SetAuditProgress(auditid, 0, total);

    QList<qcSubject> subjects;
    if (toRun.contains("duplicatesubjects") || toRun.contains("duplicatesubjectsproject") || toRun.contains("missingdemographics") || toRun.contains("invaliddob")) {
        if (!QCLoadSubjects(projectid, subjects)) {
            m = "SQL error getting the list of subjects. See the module log";
            return false;
        }
    }

    QStringList summary;
    qint64 numIssues = 0;
    QString stopReason;
    QSqlQuery q;

    /* only update the message while the audit is running, so a 'stopped' status set by the website isn't overwritten */
    auto setRunningMessage = [&](QString msg) {
        q.prepare("update audits set audit_message = :msg where audit_id = :auditid and audit_status = 'auditing'");
        q.bindValue(":msg", msg);
        q.bindValue(":auditid", auditid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    };

    /* ----- database checks ----- */
    foreach (QString c, toRun) {
        if (c == "dicommetadata")
            continue;

        if (!AuditCheckIn(auditid, progress, total, stopReason)) {
            m = QString("%1Stopped before the [%2] check. Found %3 issues").arg(stopReason).arg(c).arg(numIssues);
            if (!summary.isEmpty()) m += " (" + summary.join(", ") + ")";
            return false;
        }
        setRunningMessage(QString("Running check [%1]").arg(c));
        n->Log(QString("Running quality control check [%1] on project [%2]").arg(c).arg(projectid));

        bool ok = true;
        qint64 found = 0;
        if (c == "duplicatesubjectsproject") found = QCDuplicateSubjects(auditid, projectid, subjects, true, ok);
        else if (c == "duplicatesubjects") found = QCDuplicateSubjects(auditid, projectid, subjects, false, ok);
        else if (c == "duplicatestudies") found = QCDuplicateStudies(auditid, projectid, ok);
        else if (c == "empty") found = QCEmptyObjects(auditid, projectid, ok);
        else if (c == "missingdemographics") found = QCMissingDemographics(auditid, projectid, subjects, fixSex, fixName, fixDOB, ok);
        else if (c == "invaliddob") found = QCInvalidDOB(auditid, projectid, subjects, ok);

        if (!ok) {
            m = QString("SQL error during the [%1] check. See the module log. Found %2 issues before the error").arg(c).arg(numIssues + found);
            return false;
        }
        numIssues += found;
        summary << QString("%1: %2").arg(c).arg(found);
        progress++;
    }

    /* ----- DICOM metadata check ----- */
    if (doDicom) {
        setRunningMessage("Running check [dicommetadata]");
        n->Log(QString("Running quality control check [dicommetadata] on [%1] series in project [%2]").arg(seriesList.size()).arg(projectid));

        /* the IDs a DICOM PatientID may match for each subject: the UID and all alternate IDs */
        QHash<qint64, QSet<QString>> subjectIDs;
        q.prepare("select distinct d.subject_id, d.uid from subjects d inner join enrollment c on d.subject_id = c.subject_id where c.project_id = :projectid and d.isactive = 1");
        q.bindValue(":projectid", projectid);
        bool success = false;
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
        if (!success) { m = "SQL error getting the subject UIDs. See the module log"; return false; }
        while (q.next())
            subjectIDs[q.value("subject_id").toLongLong()].insert(q.value("uid").toString().replace('\u0000', "").trimmed().toUpper());

        q.prepare("select distinct a.subject_id, a.altuid from subject_altuid a inner join enrollment c on a.subject_id = c.subject_id inner join subjects d on a.subject_id = d.subject_id where c.project_id = :projectid and d.isactive = 1");
        q.bindValue(":projectid", projectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
        if (!success) { m = "SQL error getting the subject alternate IDs. See the module log"; return false; }
        while (q.next()) {
            QString altuid = q.value("altuid").toString().trimmed().toUpper();
            if (altuid != "")
                subjectIDs[q.value("subject_id").toLongLong()].insert(altuid);
        }

        qint64 numChecked = 0;
        qint64 dicomIssues = 0;
        QElapsedTimer timer;
        timer.start();
        for (int i = 0; i < seriesList.size(); i++) {
            if (timer.elapsed() > 2000) {
                if (!AuditCheckIn(auditid, progress + i, total, stopReason)) {
                    summary << QString("dicommetadata: %1 (checked %2 of %3 series)").arg(dicomIssues).arg(numChecked).arg(seriesList.size());
                    m = QString("%1Stopped during the [dicommetadata] check. Found %2 issues (%3)").arg(stopReason).arg(numIssues + dicomIssues).arg(summary.join(", "));
                    return false;
                }
                timer.restart();
            }

            bool checked = false;
            dicomIssues += QCDicomSeries(auditid, seriesList.at(i), subjectIDs.value(seriesList.at(i).subjectid), checked);
            if (checked)
                numChecked++;
        }
        progress += seriesList.size();
        numIssues += dicomIssues;
        summary << QString("dicommetadata: %1 (checked %2 of %3 series, the rest have no DICOM files)").arg(dicomIssues).arg(numChecked).arg(seriesList.size());
    }

    SetAuditProgress(auditid, total, total);
    m = QString("Ran %1 quality control checks. Found %2 issues (%3)").arg(toRun.size()).arg(numIssues).arg(summary.join(", "));

    return true;
}


/* ---------------------------------------------------------- */
/* --------- QCLoadSubjects --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get the active subjects enrolled in a project. Sex is stored in subjects.gender. The DOB is
 * read as a string, because a year-only or year-month DOB (YYYY-00-00, YYYY-MM-00) is not a valid date
 * @param projectid The project
 * @param subjects The subjects, ordered by subject_id
 * @return false on SQL error
 */
bool moduleAudit::QCLoadSubjects(int projectid, QList<qcSubject> &subjects) {
    QSqlQuery q;
    q.prepare("select distinct d.subject_id, d.uid, d.name, d.gender, d.sex, cast(d.birthdate as char) 'dob' from subjects d inner join enrollment c on d.subject_id = c.subject_id where c.project_id = :projectid and d.isactive = 1 order by d.subject_id");
    q.bindValue(":projectid", projectid);
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success)
        return false;

    while (q.next()) {
        qcSubject s;
        s.subjectid = q.value("subject_id").toLongLong();
        s.uid = q.value("uid").toString().replace('\u0000', "").trimmed();
        s.name = q.value("name").toString().trimmed();
        s.sex = q.value("gender").toString().trimmed().toUpper();
        s.sexcol = q.value("sex").toString().trimmed().toUpper();
        s.dob = q.value("dob").toString().trimmed();
        subjects.append(s);
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- QCDuplicateSubjects ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find groups of subjects within the project that may be the same person. Pairs of
 * subjects are matched by the rules below, then pairs that share a subject are merged into one
 * group (if A matches B, and B matches C, then A, B, and C are one group). Each group is one
 * issue, logged against the subject with the lowest subject_id. Every subject in the group is
 * recorded in audit_log_objects, with the subjects it matched and why. Names and DOBs are not
 * written to the log, only the UIDs and the rules that matched
 * @param auditid The audit_id
 * @param projectid The project
 * @param subjects The project's subjects, ordered by subject_id
 * @param projectAltUIDsOnly true to only compare alternate IDs from enrollments in this project.
 *   false to compare all of the subjects' alternate IDs, from any project
 * @param ok Set to false on SQL error
 * @return The number of issues (groups) found
 */
qint64 moduleAudit::QCDuplicateSubjects(qint64 auditid, int projectid, const QList<qcSubject> &subjects, bool projectAltUIDsOnly, bool &ok) {
    ok = true;
    qint64 numIssues = 0;

    /* when more subjects than this share a name or alternate ID, they are linked to the first
       subject only, rather than as every pair, which would be n^2 pairs */
    const int maxGroupPairs = 25;

    struct nameKeys {
        QString ordered;  /* name tokens in their stored order */
        QString sorted;   /* name tokens sorted, so a transposed first/last name has the same key */
        bool dobUsable;   /* present and valid, so it can be compared */
    };
    QList<nameKeys> keys;
    QHash<QString, QList<int>> bySortedName;
    QMap<QString, QList<int>> byFullDOB;
    QHash<qint64, int> indexOf;

    for (int i = 0; i < subjects.size(); i++) {
        const qcSubject &s = subjects.at(i);
        QStringList tokens = NameTokens(s.name);
        nameKeys k;
        k.ordered = tokens.join(" ");
        tokens.sort();
        k.sorted = tokens.join(" ");
        QString reason;
        k.dobUsable = !DOBIsMissing(s.dob) && DOBIsValid(s.dob, QDate(), reason);
        keys.append(k);
        indexOf[s.subjectid] = i;

        if (k.sorted != "")
            bySortedName[k.sorted].append(i);
        /* only a full DOB is specific enough to pair with a similar (not identical) name */
        if (k.dobUsable && !s.dob.endsWith("-00"))
            byFullDOB[s.dob].append(i);
    }

    /* matched pairs. Each pair keeps only its first (strongest) rule, because the rules are checked strongest first */
    struct pairMatch {
        int i;
        int j;
        QString reason;
        bool likely;
    };
    QList<pairMatch> pairs;
    QSet<QPair<int, int>> paired;
    auto addPair = [&](int i, int j, QString reason, bool likely) {
        if (i > j) std::swap(i, j);
        QPair<int, int> key(i, j);
        if (paired.contains(key))
            return;
        paired.insert(key);
        pairMatch pm;
        pm.i = i;
        pm.j = j;
        pm.reason = reason;
        pm.likely = likely;
        pairs.append(pm);
    };
    auto sexMatches = [&](int i, int j) {
        return (subjects.at(i).sex != "") && (subjects.at(i).sex == subjects.at(j).sex);
    };

    /* 1) same name (in either order) and same DOB. Pairs with the same name but a different or
       missing DOB are weaker, and are added last */
    QList<QPair<int, int>> weakPairs;
    for (auto it = bySortedName.cbegin(); it != bySortedName.cend(); ++it) {
        const QList<int> &idx = it.value();
        if (idx.size() < 2)
            continue;

        if (idx.size() > maxGroupPairs) {
            for (int a = 1; a < idx.size(); a++)
                addPair(idx[0], idx[a], QString("same name as %1 other subjects, which may be a placeholder name").arg(idx.size() - 1), false);
            continue;
        }

        for (int a = 0; a < idx.size(); a++) {
            for (int b = a + 1; b < idx.size(); b++) {
                int i = idx[a], j = idx[b];
                bool sameOrder = (keys[i].ordered == keys[j].ordered);
                bool dobMatch = keys[i].dobUsable && keys[j].dobUsable && (subjects.at(i).dob == subjects.at(j).dob);
                if (!dobMatch) {
                    weakPairs.append(qMakePair(i, j));
                    continue;
                }
                if (sameOrder && sexMatches(i, j))
                    addPair(i, j, "same name, DOB, and sex", true);
                else if (sameOrder)
                    addPair(i, j, "same name and DOB, sex differs or is missing", true);
                else if (sexMatches(i, j))
                    addPair(i, j, "same DOB and sex, first/last name transposed", true);
                else
                    addPair(i, j, "same DOB, first/last name transposed", true);
            }
        }
    }

    /* 2) the same alternate ID belongs to more than one subject. Either only the alternate IDs
       recorded for this project's enrollments, or all of the subjects' alternate IDs */
    QSqlQuery q;
    if (projectAltUIDsOnly)
        q.prepare("select distinct a.altuid, a.subject_id from subject_altuid a inner join enrollment c on a.enrollment_id = c.enrollment_id inner join subjects d on a.subject_id = d.subject_id where c.project_id = :projectid and d.isactive = 1 and trim(a.altuid) <> '' order by a.altuid, a.subject_id");
    else
        q.prepare("select distinct a.altuid, a.subject_id from subject_altuid a inner join enrollment c on a.subject_id = c.subject_id inner join subjects d on a.subject_id = d.subject_id where c.project_id = :projectid and d.isactive = 1 and trim(a.altuid) <> '' order by a.altuid, a.subject_id");
    q.bindValue(":projectid", projectid);
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) {
        ok = false;
        return numIssues;
    }
    QMap<QString, QList<int>> byAltUID;
    while (q.next()) {
        qint64 subjectid = q.value("subject_id").toLongLong();
        if (!indexOf.contains(subjectid))
            continue;
        QString altuid = q.value("altuid").toString().trimmed().toUpper();
        if (!byAltUID[altuid].contains(indexOf[subjectid]))
            byAltUID[altuid].append(indexOf[subjectid]);
    }
    for (auto it = byAltUID.cbegin(); it != byAltUID.cend(); ++it) {
        const QList<int> &idx = it.value();
        QString reason = QString("both have the alternate ID [%1]").arg(it.key());
        if (idx.size() > maxGroupPairs) {
            for (int a = 1; a < idx.size(); a++)
                addPair(idx[0], idx[a], QString("alternate ID [%1] is shared by %2 subjects").arg(it.key()).arg(idx.size()), true);
            continue;
        }
        for (int a = 0; a < idx.size(); a++)
            for (int b = a + 1; b < idx.size(); b++)
                addPair(idx[a], idx[b], reason, true);
    }

    /* 3) similar name (a typo, or a missing/extra letter) and the same full DOB */
    for (auto it = byFullDOB.cbegin(); it != byFullDOB.cend(); ++it) {
        const QList<int> &idx = it.value();
        for (int a = 0; a < idx.size(); a++) {
            for (int b = a + 1; b < idx.size(); b++) {
                int i = idx[a], j = idx[b];
                if ((keys[i].sorted == "") || (keys[j].sorted == "") || (keys[i].sorted == keys[j].sorted))
                    continue;
                int minLen = qMin(keys[i].sorted.size(), keys[j].sorted.size());
                int threshold = (minLen >= 10) ? 2 : ((minLen >= 4) ? 1 : 0);
                if (threshold == 0)
                    continue;
                int dist = qMin(Levenshtein(keys[i].sorted, keys[j].sorted), Levenshtein(keys[i].ordered, keys[j].ordered));
                if (dist <= threshold)
                    addPair(i, j, sexMatches(i, j) ? "similar name, same DOB and sex" : "similar name and same DOB", false);
            }
        }
    }

    /* 4) same name, but a different or missing DOB */
    foreach (auto p, weakPairs) {
        if (keys[p.first].ordered == keys[p.second].ordered)
            addPair(p.first, p.second, "same name, different or missing DOB", false);
        else
            addPair(p.first, p.second, "same name with first/last transposed, different or missing DOB", false);
    }

    /* ----- merge the pairs into groups (union-find) ----- */
    QVector<int> parent(subjects.size());
    for (int i = 0; i < parent.size(); i++)
        parent[i] = i;
    auto findRoot = [&](int x) {
        while (parent[x] != x) {
            parent[x] = parent[parent[x]];
            x = parent[x];
        }
        return x;
    };
    foreach (pairMatch p, pairs) {
        int ri = findRoot(p.i), rj = findRoot(p.j);
        if (ri != rj)
            parent[qMax(ri, rj)] = qMin(ri, rj);
    }

    QMap<int, QList<int>> groups; /* root => member indexes. The root is the lowest index, so groups are in subject_id order */
    QMap<int, QList<int>> pairsByGroup;
    for (int p = 0; p < pairs.size(); p++) {
        int root = findRoot(pairs[p].i);
        pairsByGroup[root].append(p);
        if (!groups[root].contains(pairs[p].i)) groups[root].append(pairs[p].i);
        if (!groups[root].contains(pairs[p].j)) groups[root].append(pairs[p].j);
    }

    /* ----- log one issue per group ----- */
    const int maxNoteMatches = 20;
    for (auto it = groups.cbegin(); it != groups.cend(); ++it) {
        QList<int> members = it.value();
        std::sort(members.begin(), members.end());

        bool likely = false;
        QStringList reasons;
        QMap<int, QStringList> notes; /* member index => what it matched */
        foreach (int p, pairsByGroup[it.key()]) {
            const pairMatch &pm = pairs.at(p);
            if (pm.likely) likely = true;
            if (!reasons.contains(pm.reason)) reasons << pm.reason;
            notes[pm.i] << QString("%1: %2").arg(subjects.at(pm.j).uid).arg(pm.reason);
            notes[pm.j] << QString("%1: %2").arg(subjects.at(pm.i).uid).arg(pm.reason);
        }

        QStringList uids;
        for (int m = 0; (m < members.size()) && (m < maxNoteMatches); m++)
            uids << subjects.at(members[m]).uid;
        if (members.size() > maxNoteMatches)
            uids << QString("and %1 more").arg(members.size() - maxNoteMatches);
        if (reasons.size() > 5) {
            int more = reasons.size() - 5;
            reasons = reasons.mid(0, 5);
            reasons << QString("and %1 more").arg(more);
        }

        QString msg = QString("%1 duplicates: %2 subjects (%3). Matched by: %4").arg(likely ? "Likely" : "Possible").arg(members.size()).arg(uids.join(", ")).arg(reasons.join("; "));
        qint64 auditlogid = LogAuditIssue(auditid, "duplicateSubject", "subject", subjects.at(members[0]).subjectid, msg, -1, "");
        numIssues++;

        foreach (int m, members) {
            QStringList note = notes[m];
            if (note.size() > maxNoteMatches) {
                int more = note.size() - maxNoteMatches;
                note = note.mid(0, maxNoteMatches);
                note << QString("and %1 more").arg(more);
            }
            LogAuditIssueObject(auditlogid, "subject", subjects.at(m).subjectid, note.join("\n"));
        }
    }

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- QCDuplicateStudies ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find studies in the project that also appear under a different subject, in this or any
 * other project. A study matches by StudyInstanceUID, or by the same modality and study date/time
 * (when the time is known). Each set of matching studies is reported once, against the first of
 * its studies that is in the project
 * @param auditid The audit_id
 * @param projectid The project
 * @param ok Set to false on SQL error
 * @return The number of issues found
 */
qint64 moduleAudit::QCDuplicateStudies(qint64 auditid, int projectid, bool &ok) {
    ok = true;
    qint64 numIssues = 0;
    QSet<QString> reportedSets;

    struct studyRow {
        qint64 studyid;
        int studynum;
        int projectid;
        qint64 subjectid;
        QString uid;
    };

    /* group the query's rows by 'groupkey', and log each group that spans more than one subject */
    auto checkGroups = [&](QSqlQuery &q, QString basis) {
        QMap<QString, QList<studyRow>> groups;
        while (q.next()) {
            studyRow r;
            r.studyid = q.value("study_id").toLongLong();
            r.studynum = q.value("study_num").toInt();
            r.projectid = q.value("project_id").toInt();
            r.subjectid = q.value("subject_id").toLongLong();
            r.uid = q.value("uid").toString().remove(QChar('\u0000')).trimmed();
            groups[q.value("groupkey").toString().trimmed()].append(r);
        }

        for (auto it = groups.cbegin(); it != groups.cend(); ++it) {
            const QList<studyRow> &rows = it.value();
            QSet<qint64> subjectids;
            QList<qint64> studyids;
            foreach (studyRow r, rows) {
                subjectids.insert(r.subjectid);
                studyids.append(r.studyid);
            }
            if (subjectids.size() < 2)
                continue;

            /* the same set of studies may match by both UID and date/time */
            std::sort(studyids.begin(), studyids.end());
            QStringList idstrs;
            foreach (qint64 id, studyids) idstrs << QString::number(id);
            if (reportedSets.contains(idstrs.join(",")))
                continue;
            reportedSets.insert(idstrs.join(","));

            qint64 objectid = -1;
            QStringList descs;
            foreach (studyRow r, rows) {
                if ((objectid < 0) && (r.projectid == projectid))
                    objectid = r.studyid;
                descs << QString("%1/%2").arg(r.uid).arg(r.studynum) + ((r.projectid == projectid) ? "" : " (other project)");
            }
            if (objectid < 0)
                continue;

            LogAuditIssue(auditid, "duplicateStudy", "study", objectid, QString("Possible duplicate study: same %1 [%2] under %3 different subjects: %4").arg(basis).arg(it.key()).arg(subjectids.size()).arg(descs.join(", ")), -1, "");
            numIssues++;
        }
    };

    /* by StudyInstanceUID */
    QSqlQuery q;
    q.prepare("select b.study_id, b.study_num, b.study_uid 'groupkey', c.project_id, d.subject_id, d.uid from studies b inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id where d.isactive = 1 and b.study_uid in (select b2.study_uid from studies b2 inner join enrollment c2 on b2.enrollment_id = c2.enrollment_id inner join subjects d2 on c2.subject_id = d2.subject_id where c2.project_id = :projectid and d2.isactive = 1 and trim(ifnull(b2.study_uid, '')) <> '') order by b.study_id");
    q.bindValue(":projectid", projectid);
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) {
        ok = false;
        return numIssues;
    }
    checkGroups(q, "StudyInstanceUID");

    /* by modality and study date/time. A time of 00:00:00 usually means only the date is known, which isn't specific enough */
    q.prepare("select b.study_id, b.study_num, concat(b.study_modality, ' ', b.study_datetime) 'groupkey', c.project_id, d.subject_id, d.uid from studies b inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id where d.isactive = 1 and (b.study_modality, b.study_datetime) in (select b2.study_modality, b2.study_datetime from studies b2 inner join enrollment c2 on b2.enrollment_id = c2.enrollment_id inner join subjects d2 on c2.subject_id = d2.subject_id where c2.project_id = :projectid and d2.isactive = 1 and b2.study_datetime > '1900-01-01' and time(b2.study_datetime) <> '00:00:00') order by b.study_id");
    q.bindValue(":projectid", projectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) {
        ok = false;
        return numIssues;
    }
    checkGroups(q, "modality and study date/time");

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- QCEmptyObjects --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find empty studies (no series), enrollments (no studies, observations, or interventions),
 * and subjects (every enrollment, in any project, is empty). An empty subject is reported instead
 * of its empty enrollment
 * @param auditid The audit_id
 * @param projectid The project
 * @param ok Set to false on SQL error
 * @return The number of issues found
 */
qint64 moduleAudit::QCEmptyObjects(qint64 auditid, int projectid, bool &ok) {
    ok = true;
    qint64 numIssues = 0;
    QSqlQuery q;
    bool success = false;

    /* ----- studies with at least one series, in any of the series tables ----- */
    QSet<qint64> studiesWithSeries;
    QMap<QString, QSet<QString>> tables = GetSeriesTables();
    for (auto it = tables.cbegin(); it != tables.cend(); ++it) {
        q.prepare(QString("select distinct a.study_id from `%1` a inner join studies b on a.study_id = b.study_id inner join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = :projectid").arg(it.key()));
        q.bindValue(":projectid", projectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
        if (!success) { ok = false; return numIssues; }
        while (q.next())
            studiesWithSeries.insert(q.value("study_id").toLongLong());
    }

    q.prepare("select b.study_id, b.study_num, b.study_modality, d.uid from studies b inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id where c.project_id = :projectid and d.isactive = 1 order by b.study_id");
    q.bindValue(":projectid", projectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) { ok = false; return numIssues; }
    while (q.next()) {
        qint64 studyid = q.value("study_id").toLongLong();
        if (studiesWithSeries.contains(studyid))
            continue;
        LogAuditIssue(auditid, "emptyStudy", "study", studyid, QString("Study %1/%2 (%3) has no series").arg(q.value("uid").toString().replace('\u0000', "").trimmed()).arg(q.value("study_num").toInt()).arg(q.value("study_modality").toString()), -1, "");
        numIssues++;
    }

    /* ----- enrollments and subjects. Every enrollment of the project's subjects is read, in any
       project, to tell an empty enrollment from an empty subject ----- */
    struct enrollmentRow {
        qint64 enrollmentid;
        int projectid;
        bool empty;
    };
    QMap<qint64, QList<enrollmentRow>> bySubject;
    QHash<qint64, QString> uids;

    q.prepare("select c.enrollment_id, c.project_id, c.subject_id, d.uid, exists(select 1 from studies b where b.enrollment_id = c.enrollment_id) 'hasstudies', exists(select 1 from observations o where o.enrollment_id = c.enrollment_id) 'hasobservations', exists(select 1 from interventions i where i.enrollment_id = c.enrollment_id) 'hasinterventions' from enrollment c inner join subjects d on c.subject_id = d.subject_id where d.isactive = 1 and c.subject_id in (select c2.subject_id from enrollment c2 where c2.project_id = :projectid) order by c.subject_id, c.enrollment_id");
    q.bindValue(":projectid", projectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) { ok = false; return numIssues; }
    while (q.next()) {
        qint64 subjectid = q.value("subject_id").toLongLong();
        enrollmentRow e;
        e.enrollmentid = q.value("enrollment_id").toLongLong();
        e.projectid = q.value("project_id").toInt();
        e.empty = !q.value("hasstudies").toBool() && !q.value("hasobservations").toBool() && !q.value("hasinterventions").toBool();
        bySubject[subjectid].append(e);
        uids[subjectid] = q.value("uid").toString().replace('\u0000', "").trimmed();
    }

    for (auto it = bySubject.cbegin(); it != bySubject.cend(); ++it) {
        const QList<enrollmentRow> &enrollments = it.value();
        bool allEmpty = true;
        foreach (enrollmentRow e, enrollments)
            if (!e.empty) allEmpty = false;

        if (allEmpty) {
            LogAuditIssue(auditid, "emptySubject", "subject", it.key(), QString("Subject %1 has no studies, observations, or interventions in any of its %2 enrollment(s)").arg(uids[it.key()]).arg(enrollments.size()), -1, "");
            numIssues++;
            continue;
        }

        foreach (enrollmentRow e, enrollments) {
            if (e.empty && (e.projectid == projectid)) {
                LogAuditIssue(auditid, "emptyEnrollment", "enrollment", e.enrollmentid, QString("Subject %1 is enrolled in this project, but the enrollment has no studies, observations, or interventions").arg(uids[it.key()]), -1, "");
                numIssues++;
            }
        }
    }

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- QCMissingDemographics -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find subjects with a missing name, sex, or DOB. An unrecognized sex is also reported.
 * Sex is checked in both subjects.sex and subjects.gender. Each missing item can optionally be fixed:
 *   fixSex - a blank sex or gender is filled in from the other, if the other is valid (M, F, O, U)
 *   fixName - a blank name is set to "<primary alternate ID for this project's enrollment>^", or "<UID>^"
 *   fixDOB - a missing DOB is estimated as YYYY-MM-00 from the subject's earliest study with an age (see EstimateDOB)
 * An issue that could be fixed but wasn't (ie, nothing to fill it in from) is logged with issue_fixed = 0
 * @param auditid The audit_id
 * @param projectid The project being audited
 * @param subjects The project's subjects
 * @param fixSex Fill in a blank sex/gender from the other
 * @param fixName Fill in a blank name
 * @param fixDOB Fill in a missing DOB with an estimate
 * @param ok Set to false on an SQL error
 * @return The number of issues found
 */
qint64 moduleAudit::QCMissingDemographics(qint64 auditid, int projectid, const QList<qcSubject> &subjects, bool fixSex, bool fixName, bool fixDOB, bool &ok) {
    qint64 numIssues = 0;
    QStringList validSexes = {"M", "F", "O", "U"};
    QSqlQuery q;
    bool success = false;

    foreach (qcSubject s, subjects) {

        /* ----- name ----- */
        QString name = s.name;
        name.remove('^');
        if (name.trimmed().isEmpty()) {
            QString msg = QString("Subject %1 has no name").arg(s.uid);
            if (!fixName) {
                LogAuditIssue(auditid, "missingName", "subject", s.subjectid, msg, -1, "");
            }
            else {
                /* the primary alternate ID of the subject's enrollment in this project, otherwise the UID */
                q.prepare("select a.altuid from subject_altuid a inner join enrollment e on a.enrollment_id = e.enrollment_id where a.subject_id = :subjectid and e.project_id = :projectid and a.isprimary = 1 and trim(a.altuid) <> '' order by a.subjectaltuid_id limit 1");
                q.bindValue(":subjectid", s.subjectid);
                q.bindValue(":projectid", projectid);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
                if (!success) { ok = false; return numIssues; }

                QString newName, source;
                if (q.next()) {
                    newName = q.value("altuid").toString().trimmed() + "^";
                    source = "primary alternate ID";
                }
                else if (s.uid != "") {
                    newName = s.uid + "^";
                    source = "UID";
                }

                if (newName == "") {
                    LogAuditIssue(auditid, "missingName", "subject", s.subjectid, msg, 0, "Not fixed: the subject has no primary alternate ID in this project and no UID");
                }
                else {
                    q.prepare("update subjects set name = :name where subject_id = :subjectid");
                    q.bindValue(":name", newName);
                    q.bindValue(":subjectid", s.subjectid);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
                    if (!success) { ok = false; return numIssues; }
                    LogAuditIssue(auditid, "missingName", "subject", s.subjectid, msg, 1, QString("Name changed from [%1] to [%2] (from the %3)").arg(s.name).arg(newName).arg(source));
                }
            }
            numIssues++;
        }

        /* ----- sex (subjects.sex) and gender (subjects.gender) ----- */
        QStringList problems;
        if (s.sexcol.isEmpty()) problems << "no sex";
        else if (!validSexes.contains(s.sexcol)) problems << QString("an unrecognized sex [%1]").arg(s.sexcol);
        if (s.sex.isEmpty()) problems << "no gender";
        else if (!validSexes.contains(s.sex)) problems << QString("an unrecognized gender [%1]").arg(s.sex);

        if (!problems.isEmpty()) {
            QString msg = QString("Subject %1 has %2").arg(s.uid).arg(problems.join(" and "));
            if (s.sexcol.isEmpty() != s.sex.isEmpty())
                msg += QString(" (%1 is [%2])").arg(s.sexcol.isEmpty() ? "gender" : "sex").arg(s.sexcol.isEmpty() ? s.sex : s.sexcol);
            if ((!validSexes.contains(s.sexcol) && !s.sexcol.isEmpty()) || (!validSexes.contains(s.sex) && !s.sex.isEmpty()))
                msg += ". Valid values are M, F, O, U";

            if (!fixSex) {
                LogAuditIssue(auditid, "missingSex", "subject", s.subjectid, msg, -1, "");
            }
            else {
                /* only a blank column is filled in, and only from a valid value. An unrecognized value is left for the user to fix */
                QString column, value;
                if (s.sexcol.isEmpty() && validSexes.contains(s.sex)) { column = "sex"; value = s.sex; }
                else if (s.sex.isEmpty() && validSexes.contains(s.sexcol)) { column = "gender"; value = s.sexcol; }

                if (column == "") {
                    QString reason;
                    if (s.sexcol.isEmpty() && s.sex.isEmpty()) reason = "sex and gender are both blank";
                    else if (s.sexcol.isEmpty() || s.sex.isEmpty()) reason = "the other value is not M, F, O, or U";
                    else reason = "neither value is blank. Unrecognized values must be fixed manually";
                    LogAuditIssue(auditid, "missingSex", "subject", s.subjectid, msg, 0, "Not fixed: " + reason);
                }
                else {
                    /* the column name is one of two fixed strings, not user input */
                    q.prepare(QString("update subjects set %1 = :value where subject_id = :subjectid").arg(column));
                    q.bindValue(":value", value);
                    q.bindValue(":subjectid", s.subjectid);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
                    if (!success) { ok = false; return numIssues; }

                    LogAuditIssue(auditid, "missingSex", "subject", s.subjectid, msg, 1, QString("Set %1 to [%2] (from %3)").arg(column).arg(value).arg(column == "sex" ? "gender" : "sex"));
                }
            }
            numIssues++;
        }

        /* ----- DOB ----- */
        if (DOBIsMissing(s.dob)) {
            QString msg = QString("Subject %1 has no DOB").arg(s.uid);
            if (!fixDOB) {
                LogAuditIssue(auditid, "missingDOB", "subject", s.subjectid, msg, -1, "");
            }
            else {
                QString basis;
                QString dob = EstimateDOB(s.subjectid, basis, success);
                if (!success) { ok = false; return numIssues; }

                if (dob == "") {
                    LogAuditIssue(auditid, "missingDOB", "subject", s.subjectid, msg, 0, "Not fixed: " + basis);
                }
                else {
                    q.prepare("update subjects set birthdate = :dob where subject_id = :subjectid");
                    q.bindValue(":dob", dob);
                    q.bindValue(":subjectid", s.subjectid);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
                    if (!success) { ok = false; return numIssues; }
                    LogAuditIssue(auditid, "missingDOB", "subject", s.subjectid, msg, 1, QString("DOB estimated as [%1] %2").arg(dob).arg(basis));
                }
            }
            numIssues++;
        }
    }

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- EstimateDOB ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Estimate a subject's DOB (year and month) from the earliest of the subject's studies, in any
 * project, that has both an age at scan (studies.study_ageatscan > 0) and a study date (after 1900).
 * The DOB is the study date minus the age. A whole-number age (ie, 45 from a DICOM PatientAge of
 * 045Y) means the subject was between 45 and 46, so the midpoint (45.5 years) is subtracted
 * @param subjectid The subject_id
 * @param basis Set to a description of how the DOB was estimated, or why it couldn't be
 * @param ok Set to false on an SQL error
 * @return The estimated DOB as YYYY-MM-00, or a blank string if it couldn't be estimated
 */
QString moduleAudit::EstimateDOB(qint64 subjectid, QString &basis, bool &ok) {
    QSqlQuery q;
    q.prepare("select a.study_id, cast(a.study_datetime as char) 'studydatetime', a.study_ageatscan from studies a inner join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid and a.study_ageatscan > 0 and a.study_ageatscan < 150 and a.study_datetime >= '1900-01-01' order by a.study_datetime, a.study_id");
    q.bindValue(":subjectid", subjectid);
    ok = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &ok);
    if (!ok)
        return "";

    /* the earliest study whose date parses. The query already excludes zero/placeholder dates, so this is normally the first row */
    while (q.next()) {
        QDate studyDate = QDate::fromString(q.value("studydatetime").toString().left(10), "yyyy-MM-dd");
        if (!studyDate.isValid())
            continue;

        double age = q.value("study_ageatscan").toDouble();
        double years = (age == std::floor(age)) ? age + 0.5 : age;
        QDate dob = studyDate.addDays(-qRound64(years * 365.25));
        if (dob.year() < 1900) {
            basis = QString("the earliest study with an age (study_id %1, %2, age %3) gives a DOB before 1900").arg(q.value("study_id").toLongLong()).arg(studyDate.toString("yyyy-MM-dd")).arg(age);
            return "";
        }

        basis = QString("from study_id %1 (study date %2, age at scan %3)").arg(q.value("study_id").toLongLong()).arg(studyDate.toString("yyyy-MM-dd")).arg(age);
        return dob.toString("yyyy-MM") + "-00";
    }

    basis = "the subject has no study with both an age at scan and a study date";
    return "";
}


/* ---------------------------------------------------------- */
/* --------- QCInvalidDOB ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find subjects whose DOB is present but invalid (see DOBIsValid). A missing DOB is
 * reported by the missing demographics check instead
 * @param auditid The audit_id
 * @param projectid The project
 * @param subjects The project's subjects
 * @param ok Set to false on SQL error
 * @return The number of issues found
 */
qint64 moduleAudit::QCInvalidDOB(qint64 auditid, int projectid, const QList<qcSubject> &subjects, bool &ok) {
    ok = true;
    qint64 numIssues = 0;

    /* each subject's first study in the project. Study dates before 1900 are themselves invalid, so aren't used */
    QHash<qint64, QDate> firstStudy;
    QSqlQuery q;
    q.prepare("select c.subject_id, min(b.study_datetime) 'firststudy' from studies b inner join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = :projectid and b.study_datetime > '1900-01-01' group by c.subject_id");
    q.bindValue(":projectid", projectid);
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success) {
        ok = false;
        return 0;
    }
    while (q.next())
        firstStudy[q.value("subject_id").toLongLong()] = q.value("firststudy").toDateTime().date();

    foreach (qcSubject s, subjects) {
        if (DOBIsMissing(s.dob))
            continue;
        QString reason;
        if (!DOBIsValid(s.dob, firstStudy.value(s.subjectid), reason)) {
            LogAuditIssue(auditid, "invalidDOB", "subject", s.subjectid, QString("Subject %1 DOB [%2] is invalid: %3").arg(s.uid).arg(s.dob).arg(reason), -1, "");
            numIssues++;
        }
    }

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- QCLoadDicomSeries ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Get every series in the project, from every series table, that could contain DICOM
 * files. MR series with a data_type other than dicom (ie, parrec, nifti) are skipped
 * @param projectid The project
 * @param seriesList The series
 * @return false on SQL error
 */
bool moduleAudit::QCLoadDicomSeries(int projectid, QList<qcDicomSeries> &seriesList) {
    QMap<QString, QSet<QString>> tables = GetSeriesTables();

    for (auto it = tables.cbegin(); it != tables.cend(); ++it) {
        QString table = it.key();
        if (!it.value().contains("series_num"))
            continue;
        QString modality = table.left(table.size() - QString("_series").size());
        QString pk = modality + "series_id";
        bool hasDataType = it.value().contains("data_type");

        QSqlQuery q;
        q.prepare(QString("select a.`%1` 'seriesid', a.series_num, %2 'datatype', b.study_num, b.study_datetime, b.storage_tier, d.subject_id, d.uid from `%3` a inner join studies b on a.study_id = b.study_id inner join enrollment c on b.enrollment_id = c.enrollment_id inner join subjects d on c.subject_id = d.subject_id where d.isactive = 1 and c.project_id = :projectid order by a.`%1`").arg(pk).arg(QString(hasDataType ? "a.data_type" : "''")).arg(table));
        q.bindValue(":projectid", projectid);
        bool success = false;
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
        if (!success)
            return false;

        while (q.next()) {
            qcDicomSeries s;
            s.table = table;
            s.modality = modality;
            s.seriesid = q.value("seriesid").toLongLong();
            s.seriesnum = q.value("series_num").toInt();
            s.datatype = q.value("datatype").toString().trimmed().toLower();
            s.subjectid = q.value("subject_id").toLongLong();
            s.uid = q.value("uid").toString().replace('\u0000', "").trimmed();
            s.studynum = q.value("study_num").toInt();
            s.studydatetime = q.value("study_datetime").toDateTime().toString("yyyy-MM-dd HH:mm:ss");
            s.storagetier = q.value("storage_tier").toString().trimmed();

            if ((s.datatype != "") && (s.datatype != "dicom"))
                continue;
            seriesList.append(s);
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- QCDicomSeries ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read the header of every .dcm file in a series' dicom/ directory and compare its
 * PatientID, StudyDate/StudyTime, and SeriesNumber against the series' subject, study, and series
 * in the database. One issue is logged per series per mismatched field, with the number of files
 * that don't match. File counts are not checked here (see the seriesfiles audit)
 * @param auditid The audit_id
 * @param s The series
 * @param subjectIDs The subject's UID and alternate IDs, uppercase
 * @param checked Set to true if the series had DICOM files to check
 * @return The number of issues found
 */
qint64 moduleAudit::QCDicomSeries(qint64 auditid, const qcDicomSeries &s, const QSet<QString> &subjectIDs, bool &checked) {
    checked = false;
    if ((s.uid == "") || (s.studynum < 1) || (s.seriesnum < 1))
        return 0;

    QString studypath = GetStudyPath(s.storagetier, s.uid, s.studynum);
    if (studypath == "")
        return 0;
    QString dicomdir = QString("%1/%2/dicom").arg(studypath).arg(s.seriesnum);
    QStringList files = FindAllFiles(dicomdir, "*.dcm");
    if (files.isEmpty())
        return 0;
    files.sort();
    checked = true;

    QString desc = QString("%1 series %2/%3/%4").arg(s.modality.toUpper()).arg(s.uid).arg(s.studynum).arg(s.seriesnum);

    imageIO img(n);
    QStringList unreadable;
    int numRead = 0;
    int numBlankPatientID = 0;
    QMap<QString, int> patientIDs, studyDateTimes, seriesNums;
    QStringList pidBad, dtBad, snBad; /* first few mismatched filenames, as examples */
    int numPidBad = 0, numDtBad = 0, numSnBad = 0;

    foreach (QString f, files) {
        QHash<QString, QString> tags;
        QString msg;
        img.GetImageFileTags(f, tags, msg);
        QString filename = QFileInfo(f).fileName();
        if (tags["FileType"] != "DICOM") {
            unreadable << filename;
            continue;
        }
        numRead++;

        QString pid = tags["PatientID"].trimmed().toUpper();
        QString dt = tags["StudyDateTime"].trimmed();
        QString sn = tags["SeriesNumber"].trimmed();
        patientIDs[pid]++;
        studyDateTimes[dt]++;
        seriesNums[sn]++;

        if (pid == "")
            numBlankPatientID++;
        /* an alternate ID may be stored as the SHA1 of the PatientID (see archiveIO) */
        QString pidsha1 = QString(QCryptographicHash::hash(pid.toUtf8(), QCryptographicHash::Sha1).toHex()).toUpper();
        if ((pid == "") || (!subjectIDs.contains(pid) && !subjectIDs.contains(pidsha1))) {
            numPidBad++;
            if (pidBad.size() < 3) pidBad << filename;
        }
        if (dt != s.studydatetime) {
            numDtBad++;
            if (dtBad.size() < 3) dtBad << filename;
        }
        bool snok = false;
        int snint = sn.toInt(&snok);
        if (!snok || (snint != s.seriesnum)) {
            numSnBad++;
            if (snBad.size() < 3) snBad << filename;
        }
    }

    qint64 numIssues = 0;
    int numFiles = files.size();

    if (!unreadable.isEmpty()) {
        LogAuditIssue(auditid, "dicomUnreadable", "series", s.seriesid, QString("%1: %2 of %3 .dcm files could not be read as DICOM. Example files: %4 [%5]").arg(desc).arg(unreadable.size()).arg(numFiles).arg(unreadable.mid(0, 3).join(", ")).arg(dicomdir), -1, "");
        numIssues++;
    }
    if (numRead == 0)
        return numIssues;

    /* a series whose files all have a blank PatientID was anonymized, so there is nothing to compare */
    if ((numPidBad > 0) && (numBlankPatientID < numRead)) {
        QStringList expected = subjectIDs.values();
        expected.sort();
        if (expected.size() > 10) {
            int more = expected.size() - 10;
            expected = expected.mid(0, 10);
            expected << QString("and %1 more").arg(more);
        }
        LogAuditIssue(auditid, "dicomPatientIDMismatch", "series", s.seriesid, QString("%1: %2 of %3 DICOM files have a PatientID that does not match the subject's UID or alternate IDs [%4]. Found %5. Example files: %6 [%7]").arg(desc).arg(numPidBad).arg(numRead).arg(expected.join(", ")).arg(FormatValueCounts(patientIDs)).arg(pidBad.join(", ")).arg(dicomdir), -1, "");
        numIssues++;
    }
    if (numDtBad > 0) {
        LogAuditIssue(auditid, "dicomStudyDateTimeMismatch", "series", s.seriesid, QString("%1: %2 of %3 DICOM files have a StudyDate/StudyTime that does not match the study date/time [%4]. Found %5. Example files: %6 [%7]").arg(desc).arg(numDtBad).arg(numRead).arg(s.studydatetime).arg(FormatValueCounts(studyDateTimes)).arg(dtBad.join(", ")).arg(dicomdir), -1, "");
        numIssues++;
    }
    if (numSnBad > 0) {
        LogAuditIssue(auditid, "dicomSeriesNumberMismatch", "series", s.seriesid, QString("%1: %2 of %3 DICOM files have a SeriesNumber that does not match the series number [%4]. Found %5. Example files: %6 [%7]").arg(desc).arg(numSnBad).arg(numRead).arg(s.seriesnum).arg(FormatValueCounts(seriesNums)).arg(snBad.join(", ")).arg(dicomdir), -1, "");
        numIssues++;
    }

    return numIssues;
}


/* ---------------------------------------------------------- */
/* --------- GetSeriesTables -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get the series tables (<modality>_series) that are linked to a study, with their columns.
 * Read from the database, so new modalities are included. Tables without a study_id column, or
 * a <modality>series_id primary key, are not included
 * @return table name => set of column names
 */
QMap<QString, QSet<QString>> moduleAudit::GetSeriesTables() {
    QMap<QString, QSet<QString>> columns;
    QSqlQuery q;
    q.prepare("select table_name 'tablename', column_name 'columnname' from information_schema.columns where table_schema = database() and table_name like '%series'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QRegularExpression re("^[a-z0-9]+_series$");
    while (q.next()) {
        QString table = q.value("tablename").toString().toLower();
        if (re.match(table).hasMatch())
            columns[table].insert(q.value("columnname").toString().toLower());
    }

    QMap<QString, QSet<QString>> tables;
    for (auto it = columns.cbegin(); it != columns.cend(); ++it) {
        QString modality = it.key().left(it.key().size() - QString("_series").size());
        if (it.value().contains("study_id") && it.value().contains(modality + "series_id"))
            tables[it.key()] = it.value();
    }
    return tables;
}


/* ---------------------------------------------------------- */
/* --------- GetStudyPath ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Path to a study in the archive, accounting for the study's storage tier. This must match study::LoadStudyInfo()
 * @param storageTier studies.storage_tier: blank for the default archivedir, otherwise archivedir1...4
 * @param uid Subject UID
 * @param studynum Study number
 * @return The path, or blank if the archive directory isn't configured
 */
QString moduleAudit::GetStudyPath(QString storageTier, QString uid, int studynum) {
    QString archivedir;
    if (QStringList({"archivedir1", "archivedir2", "archivedir3", "archivedir4"}).contains(storageTier))
        archivedir = n->cfg[storageTier];
    else
        archivedir = n->cfg["archivedir"];

    if ((archivedir == "") || (archivedir == "/"))
        return "";
    return QString("%1/%2/%3").arg(archivedir).arg(uid).arg(studynum);
}


/* ---------------------------------------------------------- */
/* --------- NameTokens ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Split a subject name (ie, Last^First^Middle) into lowercase words for comparison. Accents
 * are removed, any non-letter is a separator, and single letters (initials) are dropped
 * @param name The name
 * @return The words, in their original order
 */
QStringList moduleAudit::NameTokens(QString name) {
    QString decomposed = name.normalized(QString::NormalizationForm_KD);
    QString cleaned;
    foreach (QChar c, decomposed) {
        if (c.isMark())
            continue;
        cleaned += c.isLetter() ? c.toLower() : QChar(' ');
    }

    QStringList tokens;
    foreach (QString t, cleaned.split(' ', Qt::SkipEmptyParts))
        if (t.size() > 1)
            tokens << t;
    return tokens;
}


/* ---------------------------------------------------------- */
/* --------- Levenshtein ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Edit distance between two strings (insertions, deletions, substitutions)
 */
int moduleAudit::Levenshtein(const QString &a, const QString &b) {
    QVector<int> prev(b.size() + 1), cur(b.size() + 1);
    for (int j = 0; j <= b.size(); j++)
        prev[j] = j;
    for (int i = 1; i <= a.size(); i++) {
        cur[0] = i;
        for (int j = 1; j <= b.size(); j++) {
            int cost = (a[i - 1] == b[j - 1]) ? 0 : 1;
            cur[j] = qMin(qMin(prev[j] + 1, cur[j - 1] + 1), prev[j - 1] + cost);
        }
        std::swap(prev, cur);
    }
    return prev[b.size()];
}


/* ---------------------------------------------------------- */
/* --------- DOBIsMissing ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief true if a DOB (read as a string) is blank, NULL, or 0000-00-00
 */
bool moduleAudit::DOBIsMissing(QString dob) {
    dob = dob.trimmed();
    return (dob == "") || dob.startsWith("0000-00-00");
}


/* ---------------------------------------------------------- */
/* --------- DOBIsValid ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check a DOB that is present. YYYY-MM-DD, YYYY-MM-00 (year and month), and YYYY-00-00 (year
 * only) are valid formats. Partial dates are compared at the precision they have
 * @param dob The DOB, YYYY-MM-DD as stored
 * @param firstStudyDate The subject's first study date, or an invalid QDate to skip that check
 * @param reason Set to why the DOB is invalid
 * @return true if valid
 */
bool moduleAudit::DOBIsValid(QString dob, QDate firstStudyDate, QString &reason) {
    QRegularExpression re("^(\\d{4})-(\\d{2})-(\\d{2})$");
    QRegularExpressionMatch match = re.match(dob.trimmed().left(10));
    if (!match.hasMatch()) {
        reason = "not in YYYY-MM-DD format";
        return false;
    }
    int y = match.captured(1).toInt();
    int mo = match.captured(2).toInt();
    int d = match.captured(3).toInt();

    QStringList placeholders = {"0000-01-01", "0001-01-01", "1000-01-01", "1776-07-04", "1899-12-31", "1900-01-01"};
    if (placeholders.contains(dob.left(10))) { reason = "a common placeholder date"; return false; }
    if ((mo == 0) && (d != 0)) { reason = "has a day but no month"; return false; }
    if (mo > 12) { reason = "month is greater than 12"; return false; }
    if ((mo > 0) && (d > 0) && !QDate(y, mo, d).isValid()) { reason = "not a real calendar date"; return false; }
    if (y < 1900) { reason = "year is before 1900"; return false; }

    /* true if the DOB is after the reference date, at the precision of the DOB */
    auto isAfter = [&](QDate ref) {
        if (y != ref.year()) return y > ref.year();
        if (mo == 0) return false;
        if (mo != ref.month()) return mo > ref.month();
        if (d == 0) return false;
        return d > ref.day();
    };

    if (isAfter(QDate::currentDate())) { reason = "in the future"; return false; }
    if (firstStudyDate.isValid() && isAfter(firstStudyDate)) {
        reason = QString("after the subject's first study in this project (%1)").arg(firstStudyDate.toString("yyyy-MM-dd"));
        return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- FormatValueCounts ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Format the values found in a series' files, most common first, ie "[S1234ABC] (100 files), [S9999XYZ] (1 file)"
 */
QString moduleAudit::FormatValueCounts(const QMap<QString, int> &counts) {
    QList<QPair<int, QString>> sorted;
    for (auto it = counts.cbegin(); it != counts.cend(); ++it)
        sorted.append(qMakePair(it.value(), it.key()));
    std::sort(sorted.begin(), sorted.end(), [](const QPair<int, QString> &a, const QPair<int, QString> &b) { return a.first > b.first; });

    QStringList parts;
    for (int i = 0; (i < sorted.size()) && (i < 5); i++)
        parts << QString("[%1] (%2 %3)").arg(sorted[i].second == "" ? QString("blank") : sorted[i].second).arg(sorted[i].first).arg(QString(sorted[i].first == 1 ? "file" : "files"));
    if (sorted.size() > 5)
        parts << QString("and %1 more values").arg(sorted.size() - 5);
    return parts.join(", ");
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
 * @return The new auditlog_id, or -1 if the insert failed
 */
qint64 moduleAudit::LogAuditIssue(qint64 auditid, QString issueType, QString objectType, qint64 objectid, QString msg, int fixed, QString fixedMsg) {
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
    bool success = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &success);
    if (!success)
        return -1;
    return q.lastInsertId().toLongLong();
}


/* ---------------------------------------------------------- */
/* --------- LogAuditIssueObject ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Record one of the objects involved in an issue that has more than one object (ie, a
 * group of suspected duplicate subjects) in `audit_log_objects`
 * @param auditlogid The issue's auditlog_id. Nothing is recorded if this is less than 1
 * @param objectType One of the audit_log_objects.object_type enum values
 * @param objectid The database row ID of the object
 * @param note Why this object is part of the issue
 */
void moduleAudit::LogAuditIssueObject(qint64 auditlogid, QString objectType, qint64 objectid, QString note) {
    if (auditlogid < 1)
        return;
    QSqlQuery q;
    q.prepare("insert into audit_log_objects (auditlog_id, object_type, object_id, object_note) values (:auditlogid, :objecttype, :objectid, :note)");
    q.bindValue(":auditlogid", auditlogid);
    q.bindValue(":objecttype", objectType);
    q.bindValue(":objectid", objectid);
    q.bindValue(":note", note);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}
