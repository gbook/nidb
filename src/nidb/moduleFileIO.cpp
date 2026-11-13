/* ------------------------------------------------------------------------------
  NIDB moduleFileIO.cpp
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

#include "moduleFileIO.h"
#include <QSqlQuery>
#include <QSqlRecord>
#include <QLocale>
#include <QRegularExpression>
#include <QProcess>
#include <QSet>
#include <QMap>
#include <QDirIterator>
#include <QPair>

/* ---------------------------------------------------------- */
/* --------- RunProgram ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run a program with an argument list, without a shell, so paths with spaces or shell characters are passed
 * through literally and can't inject commands
 * @param program Program to run, such as 'rsync'
 * @param args Arguments
 * @param output [return] Combined stdout and stderr
 * @return The exit code, or -1 if the program didn't start or crashed
 */
static int RunProgram(const QString &program, const QStringList &args, QString &output) {
    QProcess p;
    p.setProcessChannelMode(QProcess::MergedChannels);
    p.start(program, args);
    if (!p.waitForStarted()) {
        output = QString("Unable to start [%1]: %2").arg(program).arg(p.errorString());
        return -1;
    }
    p.waitForFinished(-1);
    output = QString(p.readAll());
    if (p.exitStatus() != QProcess::NormalExit)
        return -1;
    return p.exitCode();
}


/* ---------------------------------------------------------- */
/* --------- ResolveDestination ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Validate a user-supplied destination and resolve it to an absolute path under the mount directory
 * @param mountdir The mount directory (cfg 'mountdir'), which the destination must stay inside. If empty, the
 * destination must be an absolute path other than '/'
 * @param destination Destination as entered by the user, such as '/home/user/output'
 * @param path [return] The resolved absolute path
 * @param msg [return] Reason if the destination is rejected
 * @return true if the destination is valid
 */
static bool ResolveDestination(const QString &mountdir, const QString &destination, QString &path, QString &msg) {
    QString d = destination.trimmed();

    static const QRegularExpression allowed("^[A-Za-z0-9_./ +=@,-]+$");
    if (d.isEmpty() || !allowed.match(d).hasMatch()) {
        msg = "Invalid destination [" + destination + "]. Only letters, numbers, spaces, and _ . / + = @ , - are allowed";
        return false;
    }
    if (d.split('/', Qt::SkipEmptyParts).contains("..")) {
        msg = "Invalid destination [" + destination + "]. '..' is not allowed";
        return false;
    }

    QString root = mountdir.trimmed().isEmpty() ? QString("") : QDir::cleanPath(mountdir.trimmed());
    path = QDir::cleanPath(root + "/" + d);

    if (root.isEmpty()) {
        if (path == "/") {
            msg = "Invalid destination [" + destination + "]";
            return false;
        }
    }
    else {
        QString prefix = root.endsWith('/') ? root : root + "/";
        if (!path.startsWith(prefix) || (path.size() <= prefix.size())) {
            msg = QString("Invalid destination [%1]. It must be a directory inside [%2]").arg(destination).arg(root);
            return false;
        }
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- moduleFileIO ----------------------------------- */
/* ---------------------------------------------------------- */
moduleFileIO::moduleFileIO(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleFileIO ---------------------------------- */
/* ---------------------------------------------------------- */
moduleFileIO::~moduleFileIO()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleFileIO::Run() {
    n->Log("Entering the fileio module");

    /* get list of things to delete */
    QSqlQuery q("select * from fileio_requests where request_status = 'pending'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() > 0) {
        int i = 0;
        while (q.next()) {
            n->ModuleRunningCheckIn();
            bool found = false;
            QString msg;
            i++;

            int requestid = q.value("fileiorequest_id").toInt();
            QString fileio_operation = q.value("fileio_operation").toString().trimmed();
            QString data_type;
            if (q.value("data_type").isValid())
                data_type = q.value("data_type").toString().trimmed();
            int data_id = q.value("data_id").toInt();
            QString modality = q.value("modality").toString().trimmed();
            QString data_destination = q.value("data_destination").toString().trimmed();
            int rearchiveprojectid = q.value("rearchiveprojectid").toInt();
            //QString dicomtags = q.value("anonymize_fields").toString().trimmed();
            QString username = q.value("username").toString().trimmed();
            QString merge_ids = q.value("merge_ids").toString().trimmed();
            QString merge_method = q.value("merge_method").toString().trimmed();
            QString merge_name = q.value("merge_name").toString().trimmed();
            QString merge_dob = q.value("merge_dob").toString().trimmed();
            QString merge_sex = q.value("merge_sex").toString().trimmed();
            QString merge_ethnicity1 = q.value("merge_ethnicity1").toString().trimmed();
            QString merge_ethnicity2 = q.value("merge_ethnicity2").toString().trimmed();
            QString merge_guid = q.value("merge_guid").toString().trimmed();
            QString merge_altuids = q.value("merge_altuids").toString().trimmed();

            /* claim the request: change it from pending to processing in one statement, so if another fileio thread
               (or a cancel from the website) got to it first, this one skips it */
            if (!ClaimIORequest(requestid)) {
                n->Log(QString("fileio request [%1] is no longer pending, it was claimed or cancelled elsewhere. Skipping").arg(requestid));
                continue;
            }

            n->Log(QString(" ----- FileIO operation (%1 of %2) [%3] on datatype [%4] ----- ").arg(i).arg(q.size()).arg(fileio_operation).arg(data_type));

            if (fileio_operation == "rechecksuccess") {
                if (data_type == "analysis") {
                    found = RecheckSuccess(data_id, msg);
                }
            }
            else if (fileio_operation == "createlinks") {
                if (data_type == "analysis") {
                    found = CreateLinks(data_id, data_destination, msg);
                }
            }
            else if (fileio_operation == "copy") {
                if (data_type == "analysis") {
                    found = CopyAnalysis(data_id, data_destination, msg);
                }
            }
            else if (fileio_operation == "delete") {
                if (data_type == "pipeline") { found = DeletePipeline(data_id, msg); }
                else if (data_type == "analysis") { found = DeleteAnalysis(data_id, msg); }
                //else if (data_type == "groupanalysis") { found = DeleteGroupAnalysis(data_id); }
                else if (data_type == "subject") { found = DeleteSubject(data_id, username, msg); }
                else if (data_type == "study") { found = DeleteStudy(data_id, msg); }
                else if (data_type == "series") { found = DeleteSeries(data_id, modality, msg); }
            }
            else if (fileio_operation == "cancel") {
                if (data_type == "pipelinecontainer") { found = CancelPipelineContainer(data_id, username, msg); }
                else if (data_type == "analysis") { found = CancelAnalysis(data_id, msg); }
            }
            else if (fileio_operation == "detach") {
            }
            else if (fileio_operation == "move") {
                if (data_type == "study") { found = MoveStudyToSubject(data_id, data_destination, -1, msg); }
            }
            else if (fileio_operation == "merge") {
                if (data_type == "subject") { found = MergeSubjects(data_id, merge_ids, merge_name, merge_dob, merge_sex, merge_ethnicity1, merge_ethnicity2, merge_guid, merge_altuids, msg); }
                if (data_type == "study") { found = MergeStudies(data_id, merge_ids, merge_method, msg); }
            }
            else if (fileio_operation == "anonymize") {
                //found = AnonymizeSeries(requestid, data_id, modality, dicomtags);
            }
            else if (fileio_operation == "rearchive") {
                if (data_type == "study") {
                    found = RearchiveStudy(data_id, false, msg);
                }
                else if (data_type == "subject") {
                    found = RearchiveSubject(data_id, false, rearchiveprojectid, msg);
                }
            }
            else if (fileio_operation == "rearchiveidonly") {
                if (data_type == "study") {
                    found = RearchiveStudy(data_id, true, msg);
                }
                if (data_type == "subject") {
                    found = RearchiveSubject(data_id, true, rearchiveprojectid, msg);
                }
            }
            else {
                /* unknown fileio_operation, so set it to 'error' */
                SetIORequestStatus(requestid, "error", msg);
            }

            /* all finished with the request, so set the status */
            if (found) {
                /* set the status of the delete_request to complete */
                SetIORequestStatus(requestid, "complete", msg);
            }
            else {
                /* some error occurred, so set it to 'error' */
                SetIORequestStatus(requestid, "error", msg);
            }
            n->Log("File IO operation finished, with message from function [" + msg + "]");
        }
        n->Log("Finished performing file IO");
    }
    else {
        n->Log("Nothing to do");
        return 0;
    }

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- GetIORequestStatus ----------------------------- */
/* ---------------------------------------------------------- */
QString moduleFileIO::GetIORequestStatus(int requestid) {
    QSqlQuery q;
    q.prepare("select request_status from fileio_requests where fileiorequest_id = :id");
    q.bindValue(":id", requestid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    QString status = q.value("request_status").toString();
    return status;
}


/* ---------------------------------------------------------- */
/* --------- ClaimIORequest --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Claim a pending fileio request for this thread, by changing its status from 'pending' to 'processing'. The
 * check and the change are one statement, so only one thread can claim a request
 * @param requestid fileiorequest_id
 * @return true if this thread claimed the request, false if it wasn't pending anymore
 */
bool moduleFileIO::ClaimIORequest(int requestid) {
    if (requestid < 1)
        return false;

    QSqlQuery q;
    q.prepare("update fileio_requests set request_status = 'processing', startdate = now() where fileiorequest_id = :id and request_status = 'pending'");
    q.bindValue(":id", requestid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    return (q.numRowsAffected() == 1);
}


/* ---------------------------------------------------------- */
/* --------- SetIORequestStatus ----------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::SetIORequestStatus(int requestid, QString status, QString msg) {

    if (requestid < 1)
        return false;

    if ((status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) {

        if (status == "pending") {
            QSqlQuery q;
            q.prepare("update fileio_requests set startdate = now() where fileiorequest_id = :id");
            q.bindValue(":id", requestid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            //n->Log("Updated startdate to now()");
        }
        if ((status == "complete") || (status == "error") || (status.startsWith("cancel"))) {
            QSqlQuery q;
            q.prepare("update fileio_requests set enddate = now() where fileiorequest_id = :id");
            q.bindValue(":id", requestid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            //n->Log("Updated enddate to now()");
        }

        if (msg.trimmed() == "") {
            QSqlQuery q;
            q.prepare("update fileio_requests set request_status = :status where fileiorequest_id = :id");
            q.bindValue(":id", requestid);
            q.bindValue(":status", status);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else {
            QSqlQuery q;
            q.prepare("update fileio_requests set request_status = :status, request_message = :msg where fileiorequest_id = :id");
            q.bindValue(":id", requestid);
            q.bindValue(":msg", msg);
            q.bindValue(":status", status);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        return true;
    }
    else {
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetAnalysisStoredSize -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get the number of files and disk size stored in the database for an analysis
 * @param analysisid analysis RowID
 * @param numfiles [return] number of files
 * @param bytes [return] disk size in bytes
 */
void moduleFileIO::GetAnalysisStoredSize(qint64 analysisid, qint64 &numfiles, qint64 &bytes) {
    numfiles = 0;
    bytes = 0;

    QSqlQuery q;
    q.prepare("select analysis_numfiles, analysis_disksize from analysis where analysis_id = :analysisid");
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first()) {
        numfiles = q.value("analysis_numfiles").toLongLong();
        bytes = q.value("analysis_disksize").toLongLong();
    }
}


/* ---------------------------------------------------------- */
/* --------- GetSeriesStoredSize ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Sum the number of files and disk size stored in the database for the series matching an ID
 * @param modality Modality, which selects the <modality>_series table
 * @param idcolumn Column to match, such as 'study_id' or '<modality>series_id'
 * @param id Value to match in idcolumn
 * @param numfiles [return] total number of files
 * @param bytes [return] total size in bytes
 */
void moduleFileIO::GetSeriesStoredSize(QString modality, QString idcolumn, qint64 id, qint64 &numfiles, qint64 &bytes) {
    numfiles = 0;
    bytes = 0;

    /* the modality and column become part of the SQL, so allow only plain identifiers */
    modality = modality.toLower();
    static const QRegularExpression identifier("^[a-z0-9_]+$");
    if (!identifier.match(modality).hasMatch() || !identifier.match(idcolumn).hasMatch())
        return;

    /* the <modality>_series tables don't all have the same columns, so select everything and
       use whichever count/size columns exist. A missing table is not fatal */
    QSqlQuery q;
    q.prepare(QString("select * from %1_series where %2 = :id").arg(modality).arg(idcolumn));
    q.bindValue(":id", id);
    bool ok = false;
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &ok);
    if (!ok)
        return;

    while (q.next()) {
        QSqlRecord r = q.record();

        /* some tables have 'numfiles', some 'series_numfiles', and some both */
        qint64 files = 0;
        if (r.indexOf("numfiles") >= 0)
            files = q.value("numfiles").toLongLong();
        if ((files == 0) && (r.indexOf("series_numfiles") >= 0))
            files = q.value("series_numfiles").toLongLong();
        if (r.indexOf("numfiles_beh") >= 0)
            files += q.value("numfiles_beh").toLongLong();
        numfiles += files;

        if (r.indexOf("series_size") >= 0)
            bytes += q.value("series_size").toLongLong();
        if (r.indexOf("beh_size") >= 0)
            bytes += q.value("beh_size").toLongLong();
    }
}


/* ---------------------------------------------------------- */
/* --------- StoredSizeString ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Format a file count and size for a fileio message, such as ' (1,234 files, 2.30 GB)'
 * @param numfiles Number of files
 * @param bytes Size in bytes
 * @return The formatted string with a leading space, or an empty string if neither is known
 */
QString moduleFileIO::StoredSizeString(qint64 numfiles, qint64 bytes) {
    if ((numfiles <= 0) && (bytes <= 0))
        return "";

    QLocale locale(QLocale::English, QLocale::UnitedStates);
    return QString(" (%1 files, %2)").arg(locale.toString(numfiles)).arg(locale.formattedDataSize(bytes, 2, QLocale::DataSizeTraditionalFormat));
}


/* ---------------------------------------------------------- */
/* --------- GetSeriesTables -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get the series tables (<modality>_series) that are linked to a study. Read from the database, so new
 * modalities are included. Tables without a study_id column and a <modality>series_id column, such as package_series
 * and upload_series, are not included
 * @return List of table names
 */
QStringList moduleFileIO::GetSeriesTables() {
    QMap<QString, QSet<QString>> columns;
    QSqlQuery q;
    q.prepare("select table_name 'tablename', column_name 'columnname' from information_schema.columns where table_schema = database() and table_name like '%series'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    static const QRegularExpression re("^[a-z0-9]+_series$");
    while (q.next()) {
        QString table = q.value("tablename").toString().toLower();
        if (re.match(table).hasMatch())
            columns[table].insert(q.value("columnname").toString().toLower());
    }

    QStringList tables;
    for (auto it = columns.cbegin(); it != columns.cend(); ++it) {
        QString modality = it.key().left(it.key().size() - QString("_series").size());
        if (it.value().contains("study_id") && it.value().contains(modality + "series_id"))
            tables << it.key();
    }
    return tables;
}


/* ---------------------------------------------------------- */
/* --------- DeleteSeriesRows ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Delete the series rows for a list of studies from every <modality>_series table. Only database rows are
 * deleted, not files
 * @param studyIDs study RowIDs
 * @param msg [return] Errors, if any
 * @return true if every table was cleared without an error
 */
bool moduleFileIO::DeleteSeriesRows(QList<int> studyIDs, QString &msg) {
    if (studyIDs.isEmpty())
        return true;

    QStringList tables = GetSeriesTables();
    if (tables.isEmpty()) {
        msg = "Unable to get the list of series tables";
        return false;
    }

    QStringList ids;
    for (int id : studyIDs)
        ids << QString::number(id);

    QStringList errors;
    for (const QString &table : tables) {
        /* the table names come from information_schema and match ^[a-z0-9]+_series$, and the IDs are integers */
        QSqlQuery q;
        q.prepare(QString("delete from %1 where study_id in (%2)").arg(table).arg(ids.join(",")));
        bool ok = false;
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, &ok);
        if (!ok)
            errors << table;
        else if (q.numRowsAffected() > 0)
            n->Log(QString("Deleted [%1] rows from %2").arg(q.numRowsAffected()).arg(table));
    }

    if (!errors.isEmpty()) {
        msg = "Error deleting from " + errors.join(", ");
        return false;
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- RecheckSuccess --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::RecheckSuccess(qint64 analysisid, QString &msg) {
    n->Log(QString("In RecheckSuccess(%1)").arg(analysisid));

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

    /* check the analysispath to see if the required file(s) exist
     * get a list of expected files from the database */
    QSqlQuery q;
    q.prepare("select pipeline_completefiles from pipelines a left join analysis b on a.pipeline_id = b.pipeline_id where b.analysis_id = :analysisid");
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    QString completefiles = q.value("pipeline_completefiles").toString().trimmed();
    QStringList filelist = completefiles.split(',');

    int iscomplete = 1;
    for(int i=0; i<filelist.size(); i++) {
        QString filepath = a.analysispath + "/" + filelist[i];
        n->Log(QString("Checking for [" + filepath + "]"));
        QFile f(filepath);
        if (f.exists()) {
            n->Log(QString("File [" + filepath + "] exists"));
        }
        else {
            n->Log(QString("File [" + filepath + "] does not exist"));
            iscomplete = 0;
            break;
        }
    }

    qint64 c;
    qint64 b;
    GetDirSizeAndFileCount(a.analysispath, c, b, true);

    q.prepare("update analysis set analysis_disksize = :disksize, analysis_numfiles = :numfiles, analysis_iscomplete = :iscomplete where analysis_id = :analysisid");
    q.bindValue(":disksize", b);
    q.bindValue(":numfiles", c);
    q.bindValue(":iscomplete", iscomplete);
    q.bindValue(":analysisid", analysisid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    //n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysisrecheck", "success", "Analysis success recheck finished");
    n->LogAnalysisEvent(analysisid, AnalysisEvent::StatusRecheckComplete, LogStatus::success,0,"","");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateLinks ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Create a symlink to an analysis directory, named <UID><studynum>, inside a user-supplied destination
 * @param analysisid analysis RowID
 * @param destination Destination directory as entered by the user. It's relative to the mount directory, and is
 * validated by ResolveDestination()
 * @param msg Result message
 * @return true if the link was created, or already pointed to the analysis
 */
bool moduleFileIO::CreateLinks(qint64 analysisid, QString destination, QString &msg) {
    n->Log(QString("In CreateLinks(%1, %2)").arg(analysisid).arg(destination));

    QString destdir;
    if (!ResolveDestination(n->cfg["mountdir"], destination, destdir, msg))
        return false;

    analysis a(analysisid, n, true); /* get the analysis info */
    if (!a.isValid) {
        msg = "analysis was not valid: [" + a.msg + "]";
        return false;
    }

    if (!MakePath(destdir, msg)) {
        msg += " Unable to create destination directory [" + destdir + "]";
        return false;
    }

    /* create the link directly, not through a shell. No chmod: permissions on a symlink are ignored, and chmod
       would follow the link and change the analysis directory itself */
    QString linkpath = QString("%1/%2%3").arg(destdir).arg(a.uid).arg(a.studynum);
    QFileInfo li(linkpath);
    if (li.isSymLink() && (li.symLinkTarget() == QFileInfo(a.analysispath).absoluteFilePath())) {
        msg = n->Log(QString("Link [%1] already points to [%2]").arg(linkpath).arg(a.analysispath));
    }
    else if (li.exists() || li.isSymLink()) {
        msg = n->Log(QString("Unable to create link [%1]. A file with that name already exists").arg(linkpath));
        return false;
    }
    else if (!QFile::link(a.analysispath, linkpath)) {
        msg = n->Log(QString("Unable to create link [%1] to [%2]").arg(linkpath).arg(a.analysispath));
        return false;
    }
    else {
        msg = n->Log(QString("Created link [%1] to [%2]").arg(linkpath).arg(a.analysispath));
    }

    n->LogAnalysisEvent(analysisid, AnalysisEvent::ManageCreateLink, LogStatus::success, 0, "", "");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- CopyAnalysis ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Copy an analysis directory into <destination>/<UID><studynum>
 * @param analysisid analysis RowID
 * @param destination Destination directory as entered by the user. It's relative to the mount directory, and is
 * validated by ResolveDestination()
 * @param msg Result message
 * @return true if rsync succeeded
 */
bool moduleFileIO::CopyAnalysis(qint64 analysisid, QString destination, QString &msg) {
    n->Log(QString("In CopyAnalysis(%1, %2)").arg(analysisid).arg(destination));

    QString destdir;
    if (!ResolveDestination(n->cfg["mountdir"], destination, destdir, msg))
        return false;

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "analysis was not valid: [" + a.msg + "]"; return false; }

    if (!QDir(a.analysispath).exists()) {
        msg = "Analysis directory [" + a.analysispath + "] does not exist";
        return false;
    }

    destdir = QString("%1/%2%3").arg(destdir).arg(a.uid).arg(a.studynum);
    if (!MakePath(destdir, msg)) {
        msg += " Unable to create destination directory [" + destdir + "]";
        return false;
    }

    /* run rsync directly, not through a shell. The trailing slashes copy the directory's contents, including dot files */
    QStringList args = { "-az", "--stats", "--", a.analysispath + "/", destdir + "/" };
    n->Log("Running [rsync " + args.join(" ") + "]");
    QString output;
    int ret = RunProgram("rsync", args, output);
    n->Log(output);
    if (ret != 0) {
        msg = QString("Copying [%1] to [%2] failed. rsync returned [%3]").arg(a.analysispath).arg(destdir).arg(ret);
        n->LogAnalysisEvent(analysisid, AnalysisEvent::ManageCopy, LogStatus::error, 0, "", "");
        return false;
    }

    msg = QString("Copied [%1] to [%2]").arg(a.analysispath).arg(destdir);
    n->LogAnalysisEvent(analysisid, AnalysisEvent::ManageCopy, LogStatus::success, 0, "", "");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- CancelPipelineContainer ------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Cancel a container build (pipeline_containers row) that hasn't finished. The row is set to 'canceled' first,
 * so analysisapi.php rejects the build job's later check-ins and final report, then the cluster job is stopped if
 * it was submitted. Nothing is deleted: the build directory stays, for the logs
 * @param containerid pipelinecontainer_id
 * @param username Who asked, for the build log
 * @param msg Result message
 * @return true if the build was canceled and, if it had a cluster job, the job was stopped
 */
bool moduleFileIO::CancelPipelineContainer(int containerid, QString username, QString &msg) {
    n->Log(QString("In CancelPipelineContainer(%1)").arg(containerid));

    modulePipelineContainer mpc(n);
    QString who = (username == "") ? QString("a user") : username;

    /* mark it canceled only if the status is still the one read, since the pipelinecontainer module and the build job
       also change it. Retry if it changed in between */
    QString status;
    int pipelineid = 0;
    bool canceled = false;
    for (int attempt = 0; (attempt < 3) && !canceled; attempt++) {
        QSqlQuery q;
        q.prepare("select build_status, pipeline_id from pipeline_containers where pipelinecontainer_id = :id");
        q.bindValue(":id", containerid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (!q.first()) {
            msg = QString("Container [%1] not found").arg(containerid);
            return false;
        }
        status = q.value("build_status").toString();
        pipelineid = q.value("pipeline_id").toInt();
        if ((status != "submitted") && (status != "started") && (status != "building")) {
            msg = QString("Container [%1] is [%2], so there's nothing to cancel").arg(containerid).arg(status);
            return false;
        }

        QSqlQuery q2;
        q2.prepare("update pipeline_containers set build_status = 'canceled', build_enddate = now(), build_apitoken = null, build_step_message = :message where pipelinecontainer_id = :id and build_status = :status");
        q2.bindValue(":message", "Canceled by " + who);
        q2.bindValue(":id", containerid);
        q2.bindValue(":status", status);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        canceled = (q2.numRowsAffected() > 0);
    }
    if (!canceled) {
        msg = QString("Container [%1]'s status kept changing, so it wasn't canceled. Try again").arg(containerid);
        return false;
    }
    mpc.AppendBuildLog(containerid, QString("Canceled by %1, while [%2]").arg(who).arg(status));

    /* submitted: the module hasn't picked it up. started: the module is preparing it, and won't submit it, since its
       claim from started to building fails */
    if (status != "building") {
        msg = QString("Canceled container [%1], which was [%2]. No cluster job had been submitted").arg(containerid).arg(status);
        mpc.AppendBuildLog(containerid, msg);
        return true;
    }

    /* building: stop the cluster job. Its ID is in build.jobid. If it isn't there yet, the job is still being
       submitted, and modulePipelineContainer::SubmitBuildJob() cancels it once it has the ID */
    QString buildDir = mpc.GetBuildDir(containerid);
    int jobid = 0;
    QFile f(buildDir + "/build.jobid");
    if (f.open(QIODevice::ReadOnly | QIODevice::Text)) {
        jobid = QString(f.readAll()).trimmed().toInt();
        f.close();
    }
    if (jobid < 1) {
        msg = QString("Canceled container [%1]. No job ID in [%2/build.jobid] yet, so the job is canceled when its submission finishes").arg(containerid).arg(buildDir);
        mpc.AppendBuildLog(containerid, msg);
        return true;
    }

    /* the cluster settings aren't versioned, so the current version is fine (same as SubmitBuildJob) */
    pipeline p(n);
    p.searchPipelineRowID = pipelineid;
    if (!p.Load()) {
        msg = QString("Canceled container [%1], but couldn't load pipeline [%2] to stop cluster job [%3]: %4").arg(containerid).arg(pipelineid).arg(jobid).arg(p.msg);
        mpc.AppendBuildLog(containerid, "ERROR: " + msg);
        return false;
    }

    QString cancelMsg, result;
    bool ok = n->CancelClusterJob(p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], jobid, cancelMsg, result);
    if (ok)
        msg = QString("Canceled container [%1]. %2").arg(containerid).arg(cancelMsg);
    else
        msg = QString("Canceled container [%1], but its cluster job may still be running, and needs to be stopped by hand. %2").arg(containerid).arg(cancelMsg);
    mpc.AppendBuildLog(containerid, (ok ? QString("") : QString("ERROR: ")) + msg);
    return ok;
}


/* ---------------------------------------------------------- */
/* --------- DeleteAnalysis --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteAnalysis(qint64 analysisid, QString &msg) {
    n->Log("In DeleteAnalysis()");

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "Analysis was not valid: [" + a.msg + "]"; return false; }

    /* stop the cluster job, but only if the analysis is still active. A finished analysis's job ID may since have been
       reused by the scheduler for someone else's job. The pipeline is only needed (for its cluster settings) here */
    QString status = a.status.trimmed().toLower();
    bool active = ((status == "pending") || (status == "submitted") || (status == "started") || (status == "processing"));
    if (active && (a.jobid > 0)) {
        pipeline p(n);
        p.searchPipelineRowID = a.pipelineid;
        if (!p.Load()) {
            msg = QString("Did not delete the analysis. It is [%1] with cluster job [%2], and pipeline [%3] couldn't be loaded to stop the job [%4]").arg(status).arg(a.jobid).arg(a.pipelineid).arg(p.msg);
            return false;
        }

        QString cancelMsg, result;
        if (!n->CancelClusterJob(p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], a.jobid, cancelMsg, result)) {
            /* don't delete the directory out from under a job that may still be running */
            msg = n->Log(QString("Did not delete the analysis. Its cluster job [%1] may still be running, stop it and try again. %2").arg(a.jobid).arg(cancelMsg));
            return false;
        }
        n->Log(cancelMsg);
    }
    else {
        n->Log(QString("Analysis is [%1] with job ID [%2], so there's no cluster job to stop").arg(status).arg(a.jobid));
    }

    n->Log("Analysispath: [" + a.analysispath + "]");

    /* describe the analysis now, because after it's deleted the fileio Details can no longer be looked up */
    QString what = QString("analysis for %1%2 for pipeline [%3]").arg(a.uid).arg(a.studynum).arg(a.pipelinename);
    qint64 numfiles, bytes;
    GetAnalysisStoredSize(analysisid, numfiles, bytes);

    bool okToDeleteDBEntries = false;

    if (QDir(a.analysispath).exists()) {
        qint64 c;
        qint64 b;
        GetDirSizeAndFileCount(a.analysispath, c, b, true);
        n->Log(QString("Going to remove [%1] files and directories from [%2]").arg(c).arg(a.analysispath));

        /* the analysis root depends on the pipeline level and directory structure, the same way analysis.cpp builds the path */
        QString analysisroot;
        if (a.pipelinelevel == 2)
            analysisroot = n->cfg["groupanalysisdir"];
        else if (a.pipelinedirstructure == "b")
            analysisroot = n->cfg["analysisdirb"];
        else
            analysisroot = n->cfg["analysisdir"];

        n->Log(QString("Going to recursively remove [%1], which must be inside [%2]").arg(a.analysispath).arg(analysisroot));
        QString m;
        bool deleted = SafeDeletePath(a.analysispath, analysisroot, m);
        if (!deleted || QDir(a.analysispath).exists()) {
            n->Log(QString("Analysis directory [%1] was not deleted [%2]").arg(a.analysispath).arg(m));

            QSqlQuery q;
            q.prepare("update analysis set analysis_statusmessage = 'Analysis directory not deleted. Manually delete the directory and then delete from this webpage again' where analysis_id = :analysisid");
            q.bindValue(":analysisid", analysisid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

            n->InsertAnalysisEvent(analysisid, a.pipelineid, a.pipelineversion, a.studyid, "analysisdeleteerror", "error", "Analysis directory [" + a.analysispath + "] not deleted [" + m + "]");
            n->LogAnalysisEvent(analysisid, AnalysisEvent::ManageDelete, LogStatus::error, 0, "", "");
            msg = QString("Did not delete %1. Directory [%2] could not be removed [%3]").arg(what).arg(a.analysispath).arg(m);
            return false;
        }
        okToDeleteDBEntries = true;
    }
    else {
        n->Log("Path [" + a.analysispath + "] did not exist. Did not attempt to delete");
        okToDeleteDBEntries = true;
    }

    if (okToDeleteDBEntries) {
        /* remove the database entries */
        QSqlQuery q;
        q.prepare("delete from analysis_data where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from analysis_results where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from analysis_history where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from analysis where analysis_id = :analysisid");
        q.bindValue(":analysisid", analysisid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    msg = QString("Analysis deleted for %1%2 for pipeline [%3]").arg(a.uid).arg(a.studynum).arg(a.pipelinename) + StoredSizeString(numfiles, bytes);
    return true;
}


/* ---------------------------------------------------------- */
/* --------- CancelAnalysis --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::CancelAnalysis(qint64 analysisid, QString &msg) {
    n->Log("In CancelAnalysis()");

    analysis a(analysisid, n); /* get the analysis info */
    if (!a.isValid) { msg = "Analysis was not valid: [" + a.msg + "]"; return false; }

    /* stop the cluster job, but only if the analysis is still active. A finished analysis's job ID may since have been
       reused by the scheduler for someone else's job. The pipeline is only needed here (for its cluster settings) */
    QString status = a.status.trimmed().toLower();
    bool active = ((status == "pending") || (status == "submitted") || (status == "started") || (status == "processing"));
    if (active && (a.jobid > 0)) {
        pipeline p(n);
        p.searchPipelineRowID = a.pipelineid;
        if (!p.Load()) {
            msg = QString("Did not delete the analysis. It is [%1] with cluster job [%2], and pipeline [%3] couldn't be loaded to stop the job [%4]").arg(status).arg(a.jobid).arg(a.pipelineid).arg(p.msg);
            return false;
        }

        QString cancelMsg, result;
        if (!n->CancelClusterJob(p.clusterType, p.clusterSubmitHost, p.clusterSubmitHostUser, n->cfg["qsubpath"], a.jobid, cancelMsg, result)) {
            /* don't delete the directory out from under a job that may still be running */
            msg = n->Log(QString("Did not delete the analysis. Its cluster job [%1] may still be running, stop it and try again. %2").arg(a.jobid).arg(cancelMsg));
            return false;
        }
        n->Log(cancelMsg);
    }
    else {
        n->Log(QString("Analysis is [%1] with job ID [%2], so there's no cluster job to stop").arg(status).arg(a.jobid));
    }

    msg = QString("Analysis cancelled for %1%2 for pipeline [%3]").arg(a.uid).arg(a.studynum).arg(a.pipelinename);
    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeletePipeline --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeletePipeline(int pipelineid, QString &msg) {
    n->Log("In DeletePipeline()");

    /* get the pipeline name now, because after it's deleted the fileio Details can no longer be looked up */
    QSqlQuery q;
    q.prepare("select pipeline_name from pipelines where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (!q.first()) {
        msg = QString("Pipeline [%1] not found. It may have already been deleted").arg(pipelineid);
        return false;
    }
    QString pipelinename = q.value("pipeline_name").toString().trimmed();

    /* make sure the pipeline module doesn't start new analyses while they are being deleted. The website already
       disables the pipeline when it queues the delete, but it may have been re-enabled (or put in testing) since */
    q.prepare("update pipelines set pipeline_enabled = 0, pipeline_testing = 0, pipeline_statusmessage = 'Deleting' where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* get list of analyses associated with this pipeline */
    q.prepare("select analysis_id from analysis where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    int numDeleted = 0;
    int numFailed = 0;
    qint64 totalFiles = 0;
    qint64 totalBytes = 0;
    QStringList failures;
    while (q.next()) {
        QString m;
        qint64 analysisid = q.value("analysis_id").toLongLong();
        qint64 numfiles, bytes;
        GetAnalysisStoredSize(analysisid, numfiles, bytes);
        if (DeleteAnalysis(analysisid, m)) {
            numDeleted++;
            totalFiles += numfiles;
            totalBytes += bytes;
        }
        else {
            numFailed++;
            n->Log(QString("Attempted to delete analysis [%1], but received error [%2]").arg(analysisid).arg(m));
            if (failures.size() < 5)
                failures.append(QString("analysis [%1]: %2").arg(analysisid).arg(m));
        }
    }

    /* only delete the pipeline row once no analyses refer to it. An analysis whose pipeline row is gone can't be loaded,
       so it could never be deleted afterwards. This also catches analyses created by a pipeline run that was already
       in progress when the delete started */
    q.prepare("select count(*) 'count' from analysis where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int numRemaining = 0;
    if (q.first())
        numRemaining = q.value("count").toInt();

    QString deletedStr = QString("%1 analyses deleted").arg(numDeleted) + StoredSizeString(totalFiles, totalBytes);

    if (numRemaining > 0) {
        msg = QString("Pipeline [%1] was not deleted, because %2 analyses remain (%3 could not be deleted). %4. The pipeline has been disabled. Fix the problem and delete the pipeline again").arg(pipelinename).arg(numRemaining).arg(numFailed).arg(deletedStr);
        if (failures.size() > 0)
            msg += ". Errors: " + failures.join("; ");
        if (numFailed > failures.size())
            msg += QString("; and %1 more, see the log").arg(numFailed - failures.size());

        q.prepare("update pipelines set pipeline_statusmessage = 'Delete failed, some analyses remain' where pipeline_id = :pipelineid");
        q.bindValue(":pipelineid", pipelineid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        return false;
    }

    /* delete the pipeline's definition rows (all versions), its status and history, and any tags or most-recent
       entries that point to it. Not deleted: pipeline_containers (built images have their own directories and are
       managed on the containers page), export and package records (historical, packages prune missing objects), and
       pipeline_dependencies rows where *other* pipelines list this one as a parent */
    QStringList childTables = { "pipeline_steps", "pipeline_data_def", "pipeline_options", "pipeline_version", "pipeline_dependencies", "pipeline_status", "pipeline_history", "tags", "mostrecent" };
    QStringList childErrors;
    for (const QString &table : childTables) {
        bool ok = true;
        QSqlQuery q2;
        q2.prepare(QString("delete from %1 where pipeline_id = :pipelineid").arg(table));
        q2.bindValue(":pipelineid", pipelineid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, &ok);
        if (ok)
            n->Log(QString("Deleted [%1] rows from [%2]").arg(q2.numRowsAffected()).arg(table));
        else
            childErrors.append(table);
    }

    /* delete the actual pipeline entry */
    q.prepare("delete from pipelines where pipeline_id = :pipelineid");
    q.bindValue(":pipelineid", pipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = QString("Pipeline [%1] deleted, along with %2").arg(pipelinename).arg(deletedStr);
    if (childErrors.size() > 0)
        msg += QString(". Rows for this pipeline could not be deleted from [%1], see the log").arg(childErrors.join(", "));

    /* other pipelines that list this one as a dependency now point to a pipeline that doesn't exist */
    q.prepare("select pipeline_name from pipelines where find_in_set(:pipelineid, replace(pipeline_dependency, ' ', '')) > 0");
    q.bindValue(":pipelineid", QString::number(pipelineid));
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QStringList dependents;
    while (q.next())
        dependents.append(q.value("pipeline_name").toString().trimmed());
    if (dependents.size() > 0)
        msg += QString(". These pipelines depend on it and need a new dependency: [%1]").arg(dependents.join(", "));

    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeleteSubject ---------------------------------- */
/* ---------------------------------------------------------- */
bool moduleFileIO::DeleteSubject(int subjectid, QString username, QString &msg) {
    QSqlQuery q;

    subject s(n); /* get the subject info */
    s.searchMethod = SubjectSearchMethod::RowId;
    s.searchSubjectRowID = subjectid;
    if (!s.Load()) { msg = "Subject was not valid: [" + s.msg + "]"; return false; }

    /* total the stored file counts and sizes of all the subject's series, before they're deleted */
    qint64 totalFiles = 0;
    qint64 totalBytes = 0;
    q.prepare("select a.study_id, a.study_modality from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        qint64 numfiles, bytes;
        GetSeriesStoredSize(q.value("study_modality").toString(), "study_id", q.value("study_id").toLongLong(), numfiles, bytes);
        totalFiles += numfiles;
        totalBytes += bytes;
    }

    QString newpath = QString("%1/%2-%3").arg(n->cfg["deleteddir"]).arg(s.uid).arg(GenerateRandomString(10));
    QDir d;

    if (s.GetSubjectDataPath() != "") {
        if (d.exists(s.GetSubjectDataPath())) {
            /* mv, rather than QDir::rename(), because deleteddir may be on a different filesystem. Run directly, not
               through a shell. If the move fails, stop before deleting the database rows */
            QString output;
            int ret = RunProgram("mv", { "-v", "--", s.GetSubjectDataPath(), newpath }, output);
            n->Log(output);
            if (ret != 0) {
                msg = n->Log(QString("Did not delete subject %1. Unable to move [%2] to [%3]. mv returned [%4]").arg(s.uid).arg(s.GetSubjectDataPath()).arg(newpath).arg(ret));
                return false;
            }
        }
        else {
            n->Log(QString("Subject path on disk [" + s.GetSubjectDataPath() + "] does not exist"));
        }
    }
    else {
        n->Log(QString("Subject has no path on disk"));
    }

    /* remove all database entries about this subject:
       TABLES: subjects, subject_altuid, subject_relation, studies, *_series, enrollment, family_members, mostrecent */
    q.prepare("delete from mostrecent where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    n->Log(QString("Deleted [%1] rows from mostrecent table").arg(q.numRowsAffected()));

    q.prepare("delete from family_members where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    n->Log(QString("Deleted [%1] rows from family_members table").arg(q.numRowsAffected()));

    q.prepare("delete from subject_relation where subjectid1 = :subjectid or subjectid2 = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    n->Log(QString("Deleted [%1] rows from subject_relation table").arg(q.numRowsAffected()));

    q.prepare("delete from subject_altuid where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    n->Log(QString("Deleted [%1] rows from subject_altuid table").arg(q.numRowsAffected()));

    /* get enrollment_ids for the subject */
    q.prepare("select enrollment_id from enrollment where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QStringList enrollmentIDs;
    QString seriesError;
    if (q.size() > 0) {
        while (q.next()) {
            enrollmentIDs.append(QString("%1").arg(q.value("enrollment_id").toInt()));
        }

        /* delete all series, from every <modality>_series table */
        QList<int> studyIDs;
        q.prepare("select study_id from studies where enrollment_id in (" + enrollmentIDs.join(",") + ")");
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next())
            studyIDs.append(q.value("study_id").toInt());
        if (!DeleteSeriesRows(studyIDs, seriesError))
            n->Log("Error deleting series rows [" + seriesError + "]");

        /* delete all studies */
        q.prepare("delete from studies where enrollment_id in (" + enrollmentIDs.join(",") + ")");
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        n->Log(QString("Deleted [%1] rows from studies table").arg(q.numRowsAffected()));

        /* delete all enrollments */
        q.prepare("delete from enrollment where subject_id = :subjectid");
        q.bindValue(":subjectid", subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        n->Log(QString("Deleted [%1] rows from enrollment table").arg(q.numRowsAffected()));
    }
    else {
        n->Log("No enrollments found");
    }

    // delete the subject
    q.prepare("delete from subjects where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    n->Log(QString("Deleted [%1] rows from subjects table").arg(q.numRowsAffected()));

    msg = QString("Subject %1 deleted").arg(s.uid) + StoredSizeString(totalFiles, totalBytes);
    if (seriesError != "")
        msg += ". Some series rows could not be deleted [" + seriesError + "]";
    n->InsertSubjectChangeLog(username, s.uid, "", "obliterate", msg);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveToDeleted ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Move a directory to the deleted directory. A path that doesn't exist isn't an error, there's just nothing to move
 * @param path Directory to move
 * @param name Name for it in the deleted directory. A random suffix is added
 * @param moved [return] true if the directory existed and was moved
 * @param newpath [return] Where it was moved to
 * @param msg [return] Error, if any
 * @return false only if the directory exists and couldn't be moved
 */
bool moduleFileIO::MoveToDeleted(QString path, QString name, bool &moved, QString &newpath, QString &msg) {
    moved = false;
    newpath = "";

    QFileInfo fi(path);
    if ((path.trimmed() == "") || (!fi.exists() && !fi.isSymLink())) {
        n->Log(QString("[%1] does not exist, so there's nothing to move to the deleted directory").arg(path));
        return true;
    }

    QString deleteddir = n->cfg["deleteddir"].trimmed();
    if ((deleteddir == "") || (deleteddir == "/")) {
        msg = "deleteddir is not set in the config file";
        return false;
    }
    if (!QDir(deleteddir).exists() && !QDir().mkpath(deleteddir)) {
        msg = "Unable to create the deleted directory [" + deleteddir + "]";
        return false;
    }

    newpath = QString("%1/%2-%3").arg(deleteddir).arg(name).arg(GenerateRandomString(10));

    /* mv, rather than QDir::rename(), because deleteddir may be on a different filesystem. Run directly, not through a shell */
    QString output;
    int ret = RunProgram("mv", { "--", path, newpath }, output);
    if ((ret != 0) || QFileInfo::exists(path)) {
        msg = QString("Unable to move [%1] to [%2]. mv returned [%3] %4").arg(path).arg(newpath).arg(ret).arg(output.trimmed());
        return false;
    }
    moved = true;
    n->Log(QString("Moved [%1] to [%2]").arg(path).arg(newpath));
    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeleteStudy ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Delete a study: move its directory, if it has one, to the deleted directory, then delete its series rows (from
 * every <modality>_series table) and the study row
 * @param studyid study RowID
 * @param msg Result message
 * @return true if the study was deleted
 */
bool moduleFileIO::DeleteStudy(int studyid, QString &msg) {
    n->Log(QString("In DeleteStudy(%1)").arg(studyid));

    study s(studyid, n); /* get the study info */
    if (!s.isValid()) { msg = "Study was not valid: [" + s.msg + "]"; return false; }
    QString name = QString("%1 study %2%3").arg(s.modality.toUpper()).arg(s.GetUID()).arg(s.GetStudyNum());
    qint64 numfiles, bytes;
    GetSeriesStoredSize(s.modality, "study_id", studyid, numfiles, bytes);

    /* move the directory first, so the database isn't changed if it can't be moved */
    bool moved;
    QString newpath, m;
    if (!MoveToDeleted(s.GetPath(), QString("%1-%2").arg(s.GetUID()).arg(s.GetStudyNum()), moved, newpath, m)) {
        msg = n->Log(QString("Did not delete %1. %2").arg(name).arg(m));
        return false;
    }

    QString seriesError;
    DeleteSeriesRows(QList<int>() << studyid, seriesError);

    QSqlQuery q;
    q.prepare("delete from studies where study_id = :studyid");
    q.bindValue(":studyid", studyid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = QString("%1 deleted").arg(name) + StoredSizeString(numfiles, bytes);
    if (!moved)
        msg += ". It had no directory on disk";
    if (seriesError != "")
        msg += ". Some series rows could not be deleted [" + seriesError + "]";
    return true;
}


/* ---------------------------------------------------------- */
/* --------- DeleteSeries ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Delete a series: move its directory, if it has one, to the deleted directory, then delete the series row
 * @param seriesid <modality>series_id
 * @param modality Modality, which selects the <modality>_series table. Must be an existing series table
 * @param msg Result message
 * @return true if the series was deleted
 */
bool moduleFileIO::DeleteSeries(int seriesid, QString modality, QString &msg) {
    n->Log(QString("In DeleteSeries(%1, %2)").arg(seriesid).arg(modality));

    /* the modality becomes part of the table and column names, so it must be a known series table */
    modality = modality.trimmed().toLower();
    static const QRegularExpression identifier("^[a-z0-9]+$");
    if (!identifier.match(modality).hasMatch() || !GetSeriesTables().contains(modality + "_series")) {
        msg = n->Log(QString("Invalid modality [%1]. Nothing was changed").arg(modality));
        return false;
    }

    series s(seriesid, modality, n); /* get the series info */
    if (!s.isValid) { msg = "Series was not valid: [" + s.msg + "]"; return false; }
    QString name = QString("%1 series %2%3-%4 [%5]").arg(modality.toUpper()).arg(s.uid).arg(s.studynum).arg(s.seriesnum).arg(s.protocol);

    qint64 numfiles, bytes;
    GetSeriesStoredSize(modality, modality + "series_id", seriesid, numfiles, bytes);

    /* move the directory first, so the database isn't changed if it can't be moved */
    bool moved;
    QString newpath, m;
    if (!MoveToDeleted(s.seriespath, QString("%1-%2-%3").arg(s.uid).arg(s.studynum).arg(s.seriesnum), moved, newpath, m)) {
        msg = n->Log(QString("Did not delete %1. %2").arg(name).arg(m));
        return false;
    }

    QSqlQuery q;
    q.prepare(QString("delete from %1_series where %1series_id = :seriesid").arg(modality));
    q.bindValue(":seriesid", seriesid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = QString("%1 deleted").arg(name) + StoredSizeString(numfiles, bytes);
    if (!moved)
        msg += ". It had no directory on disk";
    return true;
}


/* ---------------------------------------------------------- */
/* --------- QueueRearchive --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Queue the DICOM files in a study or subject directory for re-import, then move the directory to the deleted
 * directory. The DICOMs are copied, not moved, so a failure loses nothing: the copies are removed, the import request
 * is marked 'error', and the directory stays where it was
 * @param datapath Study or subject directory in the archive
 * @param projectid Project to import into
 * @param matchidonly Value for import_requests.import_matchidonly
 * @param deletedname Name for the directory in the deleted directory, such as 'S1234ABC-2-<random>'
 * @param msg Result message
 * @return true if the DICOMs were queued and the directory was moved. The caller then deletes the database rows
 */
bool moduleFileIO::QueueRearchive(QString datapath, int projectid, bool matchidonly, QString deletedname, QString &msg) {

    if ((datapath.trimmed() == "") || !QDir(datapath).exists()) {
        msg = "Data directory [" + datapath + "] does not exist";
        return false;
    }
    if ((n->cfg["deleteddir"].trimmed() == "") || (n->cfg["uploadeddir"].trimmed() == "")) {
        msg = "deleteddir or uploadeddir is not set in the config file";
        return false;
    }

    /* list the DICOMs first, so a directory with nothing to re-import isn't removed */
    QStringList dicoms;
    QDirIterator it(datapath, QStringList() << "*.dcm", QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
    while (it.hasNext())
        dicoms << it.next();
    if (dicoms.isEmpty()) {
        msg = "No .dcm files found in [" + datapath + "], so there is nothing to re-import";
        return false;
    }

    /* get instanceid */
    QSqlQuery q;
    q.prepare("select instance_id from projects where project_id = :projectid");
    q.bindValue(":projectid", projectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (!q.first()) {
        msg = QString("Project [%1] not found, so the instance ID is unknown").arg(projectid);
        return false;
    }
    int instanceid = q.value("instance_id").toInt();

    /* create an import request. It stays 'uploading' until the files are in place */
    q.prepare("insert into import_requests (import_datatype, import_datetime, import_status, import_equipment, import_siteid, import_projectid, import_instanceid, import_uuid, import_anonymize, import_permanent, import_matchidonly) values ('dicom',now(),'uploading','',null,:projectid,:instanceid,'',null,null,:matchidonly)");
    q.bindValue(":projectid", projectid);
    q.bindValue(":instanceid", instanceid);
    q.bindValue(":matchidonly", matchidonly);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int uploadid = q.lastInsertId().toInt();
    if (uploadid < 1) {
        msg = "Unable to create an import request";
        return false;
    }
    QString outpath = QString("%1/%2").arg(n->cfg["uploadeddir"]).arg(uploadid);

    /* on failure, remove the copies and mark the import request as an error. The original directory is untouched */
    auto fail = [&](QString reason) -> bool {
        if (QDir(outpath).exists()) {
            QString m;
            if (!SafeDeletePath(outpath, n->cfg["uploadeddir"], m))
                reason += QString(". Unable to remove the copies in [%1] [%2]").arg(outpath).arg(m);
        }
        QSqlQuery q2;
        q2.prepare("update import_requests set import_status = 'error', import_message = :msg where importrequest_id = :uploadid");
        q2.bindValue(":msg", reason.left(255));
        q2.bindValue(":uploadid", uploadid);
        n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        msg = n->Log(reason);
        return false;
    };

    if (!QDir().mkpath(outpath))
        return fail("Unable to create the import directory [" + outpath + "]");

    /* copy the DICOMs to the import directory */
    for (int i = 0; i < dicoms.size(); i++) {
        if ((i % 1000) == 0)
            n->ModuleRunningCheckIn();
        const QString &f = dicoms[i];
        QString newfile = QString("%1/%2.dcm").arg(outpath).arg(GenerateRandomString(20));
        if (!QFile::copy(f, newfile))
            return fail(QString("Unable to copy [%1] to [%2]. Nothing was changed").arg(f).arg(newfile));
    }
    n->Log(QString("Copied [%1] .dcm files from [%2] to [%3]").arg(dicoms.size()).arg(datapath).arg(outpath));

    /* move the original directory to the deleted directory. mv, rather than QDir::rename(), because deleteddir may be
       on a different filesystem. Run directly, not through a shell */
    QString newpath = QString("%1/%2").arg(n->cfg["deleteddir"]).arg(deletedname);
    QString output;
    int ret = RunProgram("mv", { "--", datapath, newpath }, output);
    n->Log(output);
    if ((ret != 0) || QDir(datapath).exists())
        return fail(QString("Unable to move [%1] to [%2]. mv returned [%3]. Check both directories, a partial move may have happened").arg(datapath).arg(newpath).arg(ret));

    /* everything is in place, so let the import module pick it up */
    q.prepare("update import_requests set import_status = 'pending' where importrequest_id = :uploadid");
    q.bindValue(":uploadid", uploadid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = QString("Queued %1 .dcm files for re-import (import request %2). Moved [%3] to [%4]").arg(dicoms.size()).arg(uploadid).arg(datapath).arg(newpath);
    return true;
}


/* ---------------------------------------------------------- */
/* --------- RearchiveStudy --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Re-import a study's DICOM files, and remove the study from the archive and the database. Only .dcm files are
 * re-imported, other files go to the deleted directory with the rest of the study
 * @param studyid study RowID
 * @param matchidonly Match the subject by ID only during the import
 * @param msg Result message
 * @return true if the files were queued for import and the study was removed
 */
bool moduleFileIO::RearchiveStudy(int studyid, bool matchidonly, QString &msg) {
    n->Log("In RearchiveStudy()");

    study s(studyid, n); /* get the study info */
    if (!s.isValid()) { msg = "Study was not valid: [" + s.msg + "]"; return false; }

    QString deletedname = QString("%1-%2-%3").arg(s.GetUID()).arg(s.GetStudyNum()).arg(GenerateRandomString(10));
    QString m;
    if (!QueueRearchive(s.GetPath(), s.GetProjectRowID(), matchidonly, deletedname, m)) {
        msg = QString("Did not rearchive study %1%2. %3").arg(s.GetUID()).arg(s.GetStudyNum()).arg(m);
        return false;
    }

    /* remove the study and its series from the database. The import creates them again */
    QString seriesError;
    DeleteSeriesRows(QList<int>() << studyid, seriesError);

    QSqlQuery q;
    q.prepare("delete from studies where study_id = :studyid");
    q.bindValue(":studyid", studyid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = QString("Rearchiving study %1%2. %3").arg(s.GetUID()).arg(s.GetStudyNum()).arg(m);
    if (seriesError != "")
        msg += ". Some series rows could not be deleted [" + seriesError + "]";
    return true;
}


/* ---------------------------------------------------------- */
/* --------- RearchiveSubject ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Re-import a subject's DICOM files into a project, and remove the subject from the archive and the database.
 * Only .dcm files are re-imported, other files go to the deleted directory with the rest of the subject
 * @param subjectid subject RowID
 * @param matchidonly Match the subject by ID only during the import
 * @param projectid Project to import into
 * @param msg Result message
 * @return true if the files were queued for import and the subject was removed
 */
bool moduleFileIO::RearchiveSubject(int subjectid, bool matchidonly, int projectid, QString &msg) {
    n->Log("In RearchiveSubject()");

    subject s(n);
    s.searchMethod = SubjectSearchMethod::RowId;
    s.searchSubjectRowID = subjectid;
    if (!s.Load()) { msg = "Subject was not valid: [" + s.msg + "]"; return false; }

    QString deletedname = QString("%1-%2").arg(s.uid).arg(GenerateRandomString(10));
    QString m;
    if (!QueueRearchive(s.GetSubjectDataPath(), projectid, matchidonly, deletedname, m)) {
        msg = QString("Did not rearchive subject %1. %2").arg(s.uid).arg(m);
        return false;
    }

    /* remove all database entries about this subject. The import creates them again
     * TABLES: subjects, subject_altuid, subject_relation, studies, *_series, enrollment, family_members, mostrecent */
    QSqlQuery q;
    q.prepare("delete from mostrecent where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from family_members where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subject_relation where subjectid1 = :subjectid or subjectid2 = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subject_altuid where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* series, from every <modality>_series table */
    QList<int> studyIDs;
    q.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next())
        studyIDs.append(q.value("study_id").toInt());
    QString seriesError;
    DeleteSeriesRows(studyIDs, seriesError);

    q.prepare("delete from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = :subjectid)");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from enrollment where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from subjects where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    msg = QString("Rearchiving subject %1. %2").arg(s.uid).arg(m);
    if (seriesError != "")
        msg += ". Some series rows could not be deleted [" + seriesError + "]";
    return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveStudyToSubject ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Move a study to another subject. The study gets the new subject's next study number, and its directory is
 * moved to <archive root>/<new UID>/<new study number>, in the same archive root (storage tier) it's in now. The
 * directory is moved before the database is changed, so if the move fails nothing is changed
 * @param studyid study RowID
 * @param newuid UID of the new subject. If blank, newsubjectid is used
 * @param newsubjectid RowID of the new subject, used when newuid is blank
 * @param msg Result message
 * @return true if the study was moved
 */
bool moduleFileIO::MoveStudyToSubject(int studyid, QString newuid, int newsubjectid, QString &msg) {
    n->Log(QString("In MoveStudyToSubject(%1, [%2], %3)").arg(studyid).arg(newuid).arg(newsubjectid));

    /* ---------- check everything before changing anything ---------- */
    study thestudy(studyid, n);
    if (!thestudy.isValid()) {
        msg = n->Log(QString("Study [%1] was not valid: [%2]. Nothing was changed").arg(studyid).arg(thestudy.msg));
        return false;
    }
    QString oldName = QString("%1%2").arg(thestudy.GetUID()).arg(thestudy.GetStudyNum());

    subject newsubject(n);
    if (newuid.trimmed() != "") {
        newsubject.searchMethod = SubjectSearchMethod::Uid;
        newsubject.searchUID = newuid.trimmed();
    }
    else if (newsubjectid > 0) {
        newsubject.searchMethod = SubjectSearchMethod::RowId;
        newsubject.searchSubjectRowID = newsubjectid;
    }
    else {
        msg = n->Log(QString("No subject was given to move study %1 to. Nothing was changed").arg(oldName));
        return false;
    }
    if (!newsubject.Load()) {
        msg = n->Log(QString("New subject [%1] was not valid: [%2]. Nothing was changed").arg(newuid.trimmed() != "" ? newuid : QString::number(newsubjectid)).arg(newsubject.msg));
        return false;
    }
    if (newsubject.GetSubjectRowID() == thestudy.GetSubjectRowID()) {
        msg = n->Log(QString("Study %1 already belongs to subject %2. Nothing was changed").arg(oldName).arg(newsubject.uid));
        return false;
    }

    if (QDateTime::currentDateTime() < thestudy.datetime.addDays(1)) {
        msg = n->Log(QString("Study %1 was collected in the past 24 hours, and may still be archiving. Move it after [%2]. Nothing was changed").arg(oldName).arg(thestudy.datetime.addDays(1).toString("yyyy-MM-dd HH:mm")));
        return false;
    }

    /* the next study number for the new subject */
    QSqlQuery q;
    q.prepare("select max(a.study_num) 'maxstudynum' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
    q.bindValue(":subjectid", newsubject.GetSubjectRowID());
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int newstudynum = 1;
    if (q.first())
        newstudynum = q.value("maxstudynum").toInt() + 1;
    QString newName = QString("%1%2").arg(newsubject.uid).arg(newstudynum);

    /* the new path is in the same archive root (storage tier) as the current path, which is <root>/<UID>/<studynum> */
    QString oldpath = QDir::cleanPath(thestudy.GetPath());
    QString root = QFileInfo(QFileInfo(oldpath).absolutePath()).absolutePath();
    if (QDir::cleanPath(QString("%1/%2/%3").arg(root).arg(thestudy.GetUID()).arg(thestudy.GetStudyNum())) != oldpath) {
        msg = n->Log(QString("Study %1's path [%2] isn't in the form <archive>/<UID>/<study number>. Nothing was changed").arg(oldName).arg(oldpath));
        return false;
    }
    QString newSubjectDir = QString("%1/%2").arg(root).arg(newsubject.uid);
    QString newpath = QString("%1/%2").arg(newSubjectDir).arg(newstudynum);
    QFileInfo newfi(newpath);
    if (newfi.exists() || newfi.isSymLink()) {
        msg = n->Log(QString("[%1] already exists, possibly left over from an earlier move. Move or remove it, then try again. Nothing was changed").arg(newpath));
        return false;
    }

    /* ---------- move the directory ---------- */
    bool hasDir = QFileInfo(oldpath).isDir();
    if (hasDir) {
        QString m;
        if (!QDir(newSubjectDir).exists() && !MakePath(newSubjectDir, m)) {
            msg = n->Log(QString("Unable to create [%1] [%2]. Nothing was changed").arg(newSubjectDir).arg(m));
            return false;
        }

        /* same archive root, so this is normally a rename. Run directly, not through a shell */
        QString output;
        int ret = RunProgram("mv", { "--", oldpath, newpath }, output);
        if ((ret != 0) || QFileInfo::exists(oldpath) || !QFileInfo(newpath).isDir()) {
            msg = n->Log(QString("Unable to move [%1] to [%2]. mv returned [%3] %4. Check both directories, a partial move may have happened. The database was not changed").arg(oldpath).arg(newpath).arg(ret).arg(output.trimmed()));
            return false;
        }
        n->Log(QString("Moved [%1] to [%2]").arg(oldpath).arg(newpath));
    }
    else {
        n->Log(QString("Study %1 has no directory [%2], so only the database is changed").arg(oldName).arg(oldpath));
    }

    /* ---------- update the database ---------- */
    /* enroll the new subject in the study's project, if they aren't already */
    int newenrollmentid;
    q.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
    q.bindValue(":subjectid", newsubject.GetSubjectRowID());
    q.bindValue(":projectid", thestudy.GetProjectRowID());
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first()) {
        newenrollmentid = q.value("enrollment_id").toInt();
    }
    else {
        q.prepare("insert into enrollment (subject_id, project_id, enroll_startdate) values (:subjectid, :projectid, now())");
        q.bindValue(":subjectid", newsubject.GetSubjectRowID());
        q.bindValue(":projectid", thestudy.GetProjectRowID());
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        newenrollmentid = q.lastInsertId().toInt();
        n->Log(QString("Enrolled subject %1 in project [%2], enrollment [%3]").arg(newsubject.uid).arg(thestudy.GetProjectRowID()).arg(newenrollmentid));
    }

    q.prepare("update studies set enrollment_id = :enrollmentid, study_num = :newstudynum where study_id = :studyid");
    q.bindValue(":enrollmentid", newenrollmentid);
    q.bindValue(":newstudynum", newstudynum);
    q.bindValue(":studyid", studyid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (hasDir)
        msg = QString("Moved study %1 to %2, and its directory [%3] to [%4]").arg(oldName).arg(newName).arg(oldpath).arg(newpath);
    else
        msg = QString("Moved study %1 to %2. It had no directory on disk").arg(oldName).arg(newName);
    n->Log(msg);

    q.prepare("insert into changelog (affected_projectid1, affected_projectid2, affected_subjectid1, affected_subjectid2, affected_enrollmentid1, affected_enrollmentid2, affected_studyid1, affected_studyid2, change_datetime, change_event, change_desc) values (:oldprojectid, :oldprojectid, :oldsubjectid, :newsubjectid, :oldenrollmentid, :newenrollmentid, :studyid, :studyid, now(), 'MoveStudyToSubject', :msg)");
    q.bindValue(":oldprojectid", thestudy.GetProjectRowID());
    q.bindValue(":oldsubjectid", thestudy.GetSubjectRowID());
    q.bindValue(":newsubjectid", newsubject.GetSubjectRowID());
    q.bindValue(":oldenrollmentid", thestudy.GetEnrollmentRowID());
    q.bindValue(":newenrollmentid", newenrollmentid);
    q.bindValue(":studyid", studyid);
    q.bindValue(":msg", msg);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- MergeSubjects ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Merge subjects into a target subject. Each source subject's studies, observations, and interventions are
 * moved to the target, and the source subject is deactivated. The target's demographics and alternate UIDs are then
 * replaced with the merged values. Everything that could stop the merge is checked before anything is changed
 * @param targetSubjectID Target subject RowID
 * @param mergeIDs Comma separated list of subject RowIDs to merge. The target's own ID may be in the list, and is skipped
 * @param mergeName Name for the target subject
 * @param mergeDOB Birthdate for the target subject, as YYYY-MM-DD. Blank (or 0000-00-00) sets it to NULL
 * @param mergeSex Sex for the target subject
 * @param mergeEthnicity1 Ethnicity1 for the target subject
 * @param mergeEthnicity2 Ethnicity2 for the target subject
 * @param mergeGUID GUID for the target subject
 * @param mergeAltUIDs Comma separated alternate UIDs for the target subject. A '*' marks the primary alternate UID
 * @param msg Result message
 * @return true if the subjects were merged
 */
bool moduleFileIO::MergeSubjects(int targetSubjectID, QString mergeIDs, QString mergeName, QString mergeDOB, QString mergeSex, QString mergeEthnicity1, QString mergeEthnicity2, QString mergeGUID, QString mergeAltUIDs, QString &msg) {
    n->Log(QString("In MergeSubjects(%1, [%2])").arg(targetSubjectID).arg(mergeIDs));
    msg = "";

    /* ---------- check everything before changing anything ---------- */
    subject targetSubject(n);
    targetSubject.searchMethod = SubjectSearchMethod::RowId;
    targetSubject.searchSubjectRowID = targetSubjectID;
    if (!targetSubject.Load()) {
        msg = n->Log(QString("Target subject [%1] was not valid: [%2]. Nothing was changed").arg(targetSubjectID).arg(targetSubject.msg));
        return false;
    }

    /* birthdate: blank means unknown (NULL). Anything else must be a valid date, or the update would fail */
    QVariant dob;
    QString dobString = mergeDOB.trimmed().left(10);
    if ((dobString != "") && (dobString != "0000-00-00")) {
        QDate d = QDate::fromString(dobString, "yyyy-MM-dd");
        if (!d.isValid()) {
            msg = n->Log(QString("Invalid birthdate [%1], expected YYYY-MM-DD. Nothing was changed").arg(mergeDOB));
            return false;
        }
        dob = d;
    }

    /* the subjects to merge. Blank entries (such as from a trailing comma) are ignored, and so is the target */
    QList<int> sourceIDs;
    for (const QString &s : mergeIDs.split(",", Qt::SkipEmptyParts)) {
        if (s.trimmed() == "")
            continue;
        bool ok = false;
        int id = s.trimmed().toInt(&ok);
        if (!ok || (id < 1)) {
            msg = n->Log(QString("Invalid subject ID [%1] in the merge list [%2]. Nothing was changed").arg(s).arg(mergeIDs));
            return false;
        }
        if ((id != targetSubjectID) && !sourceIDs.contains(id))
            sourceIDs.append(id);
    }
    if (sourceIDs.isEmpty()) {
        msg = n->Log(QString("No subjects to merge into %1. Nothing was changed").arg(targetSubject.uid));
        return false;
    }

    /* load each source subject and its studies, and check that every study can be moved (see MoveStudyToSubject) */
    struct SourceSubject {
        int id;
        QString uid;
        QList<int> studyIDs;
    };
    QList<SourceSubject> sources;
    QStringList sourceUIDs;
    QDateTime now = QDateTime::currentDateTime();
    for (int id : sourceIDs) {
        subject s(n);
        s.searchMethod = SubjectSearchMethod::RowId;
        s.searchSubjectRowID = id;
        if (!s.Load()) {
            msg = n->Log(QString("Subject [%1] was not valid: [%2]. Nothing was changed").arg(id).arg(s.msg));
            return false;
        }

        SourceSubject src;
        src.id = id;
        src.uid = s.uid;

        QSqlQuery q;
        q.prepare("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = :subjectid");
        q.bindValue(":subjectid", id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            int studyid = q.value("study_id").toInt();
            study st(studyid, n);
            if (!st.isValid()) {
                msg = n->Log(QString("Study [%1] of subject %2 was not valid: [%3]. Nothing was changed").arg(studyid).arg(s.uid).arg(st.msg));
                return false;
            }
            if (now < st.datetime.addDays(1)) {
                msg = n->Log(QString("Study %1%2 was collected in the past 24 hours, and may still be archiving. Merge after [%3]. Nothing was changed").arg(st.GetUID()).arg(st.GetStudyNum()).arg(st.datetime.addDays(1).toString("yyyy-MM-dd HH:mm")));
                return false;
            }
            src.studyIDs.append(studyid);
        }

        sources.append(src);
        sourceUIDs << s.uid;
    }

    /* ---------- merge each source subject ---------- */
    QStringList done;
    for (const SourceSubject &src : sources) {

        /* studies */
        int numStudies = 0;
        for (int studyid : src.studyIDs) {
            QString m;
            if (!MoveStudyToSubject(studyid, "", targetSubjectID, m)) {
                QString completed = done.isEmpty() ? QString("nothing") : done.join("; ");
                msg = n->Log(QString("Merge into %1 stopped. Unable to move study [%2] from %3 [%4]. %5 of %6 studies from %3 had been moved. Completed before this: %7").arg(targetSubject.uid).arg(studyid).arg(src.uid).arg(m).arg(numStudies).arg(src.studyIDs.size()).arg(completed));
                return false;
            }
            numStudies++;
        }

        /* observations and interventions, enrollment by enrollment */
        int numObservations = 0;
        int numInterventions = 0;
        QSqlQuery q;
        q.prepare("select enrollment_id, project_id from enrollment where subject_id = :subjectid");
        q.bindValue(":subjectid", src.id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            int sourceProjectID = q.value("project_id").toInt();
            int sourceEnrollmentID = q.value("enrollment_id").toInt();

            /* get, or create, the target subject's enrollment in the same project */
            int targetEnrollmentID;
            QSqlQuery q2;
            q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
            q2.bindValue(":subjectid", targetSubjectID);
            q2.bindValue(":projectid", sourceProjectID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            if (q2.first()) {
                targetEnrollmentID = q2.value("enrollment_id").toInt();
            }
            else {
                q2.prepare("insert into enrollment (subject_id, project_id) values (:subjectid, :projectid)");
                q2.bindValue(":subjectid", targetSubjectID);
                q2.bindValue(":projectid", sourceProjectID);
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
                targetEnrollmentID = q2.lastInsertId().toInt();
                n->Log(QString("Created enrollment [%1] for subject %2 in project [%3]").arg(targetEnrollmentID).arg(targetSubject.uid).arg(sourceProjectID));
            }

            q2.prepare("update observations set enrollment_id = :targetenrollmentid where enrollment_id = :sourceenrollmentid");
            q2.bindValue(":targetenrollmentid", targetEnrollmentID);
            q2.bindValue(":sourceenrollmentid", sourceEnrollmentID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            numObservations += q2.numRowsAffected();

            q2.prepare("update interventions set enrollment_id = :targetenrollmentid where enrollment_id = :sourceenrollmentid");
            q2.bindValue(":targetenrollmentid", targetEnrollmentID);
            q2.bindValue(":sourceenrollmentid", sourceEnrollmentID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            numInterventions += q2.numRowsAffected();
        }

        /* deactivate the merged subject. It isn't deleted, so it can still be looked up */
        q.prepare("update subjects set isactive = 0 where subject_id = :sourcesubjectid");
        q.bindValue(":sourcesubjectid", src.id);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        done << n->Log(QString("%1: %2 studies, %3 observations, %4 interventions moved, and %1 deactivated").arg(src.uid).arg(numStudies).arg(numObservations).arg(numInterventions));
    }

    /* ---------- update the target subject ---------- */
    QSqlQuery q;
    q.prepare("update subjects set name = :name, birthdate = :dob, gender = :sex, ethnicity1 = :ethnicity1, ethnicity2 = :ethnicity2, guid = :guid where subject_id = :targetsubjectid");
    q.bindValue(":name", mergeName);
    q.bindValue(":dob", dob);
    q.bindValue(":sex", mergeSex);
    q.bindValue(":ethnicity1", mergeEthnicity1);
    q.bindValue(":ethnicity2", mergeEthnicity2);
    q.bindValue(":guid", mergeGUID);
    q.bindValue(":targetsubjectid", targetSubjectID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* replace the subject-level alternate UIDs (enrollment_id 0, as the website stores them; -1 from older merges).
       Enrollment-level alternate UIDs are kept */
    q.prepare("delete from subject_altuid where subject_id = :targetsubjectid and enrollment_id in (0, -1)");
    q.bindValue(":targetsubjectid", targetSubjectID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    int numAltUIDs = 0;
    for (QString altuid : mergeAltUIDs.split(",", Qt::SkipEmptyParts)) {
        altuid = altuid.trimmed();
        int isprimary = 0;
        if (altuid.contains("*")) {
            altuid.replace("*", "");
            altuid = altuid.trimmed();
            isprimary = 1;
        }
        if (altuid == "")
            continue;
        q.prepare("insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (:targetsubjectid, :altuid, :isprimary, 0)");
        q.bindValue(":targetsubjectid", targetSubjectID);
        q.bindValue(":altuid", altuid);
        q.bindValue(":isprimary", isprimary);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        numAltUIDs++;
    }

    msg = n->Log(QString("Merged %1 into %2. %3. Updated %2's demographics and %4 alternate UIDs").arg(sourceUIDs.join(", ")).arg(targetSubject.uid).arg(done.join("; ")).arg(numAltUIDs));
    return true;
}


/* ---------------------------------------------------------- */
/* --------- MergeStudies ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Merge studies into a target study. The series of all the studies are ordered and numbered by the merge
 * method, the other studies' series are moved into the target study, and the other studies are deleted. All studies
 * must belong to the same subject and have the same modality. The series directories are moved first, through a
 * staging directory, and moved back if any move fails. The database is only changed once every directory is in place
 * @param studyid Target study RowID
 * @param mergeIDs Comma separated list of the study RowIDs to merge into the target
 * @param mergeMethod How to order and number the series:
 *   sortbyseriesdate - by series datetime, numbered 1...N
 *   sortbyseriesnum - by existing series number, which is kept. A duplicate number becomes 10000 + the number
 *   concatbystudydateasc, concatbystudydatedesc - study by study, ordered by study datetime, numbered 1...N
 *   concatbystudynumasc, concatbystudynumdesc - study by study, ordered by study number, numbered 1...N
 * @param msg Result message
 * @return true if the series were merged. Failing to delete an emptied study afterwards is reported in msg
 */
bool moduleFileIO::MergeStudies(int studyid, QString mergeIDs, QString mergeMethod, QString &msg) {
    n->Log(QString("In MergeStudies(%1, [%2], %3)").arg(studyid).arg(mergeIDs).arg(mergeMethod));

    /* ---------- check everything before changing anything ---------- */
    study target(studyid, n);
    if (!target.isValid()) { msg = "Target study was not valid: [" + target.msg + "]"; return false; }
    QString targetName = QString("%1%2").arg(target.GetUID()).arg(target.GetStudyNum());

    QString modality = target.modality.toLower();
    static const QRegularExpression identifier("^[a-z0-9]+$");
    QStringList seriesTables = GetSeriesTables();
    QString table = modality + "_series";
    QString idcol = modality + "series_id";
    if (!identifier.match(modality).hasMatch() || !seriesTables.contains(table)) {
        msg = QString("Target study %1 has modality [%2], which has no series table. Nothing was changed").arg(targetName).arg(target.modality);
        return false;
    }

    /* order of the series for each method. Ties put the target study's series first */
    QString orderby;
    if (mergeMethod == "sortbyseriesdate") orderby = "a.series_datetime asc, a.study_id = :targetid desc, a.series_num asc";
    else if (mergeMethod == "sortbyseriesnum") orderby = "a.series_num asc, a.study_id = :targetid desc, a.series_datetime asc";
    else if (mergeMethod == "concatbystudydateasc") orderby = "b.study_datetime asc, a.study_id = :targetid desc, b.study_id asc, a.series_num asc";
    else if (mergeMethod == "concatbystudydatedesc") orderby = "b.study_datetime desc, a.study_id = :targetid desc, b.study_id asc, a.series_num asc";
    else if (mergeMethod == "concatbystudynumasc") orderby = "b.study_num asc, a.study_id = :targetid desc, a.series_num asc";
    else if (mergeMethod == "concatbystudynumdesc") orderby = "b.study_num desc, a.study_id = :targetid desc, a.series_num asc";
    else {
        msg = "Unknown merge method [" + mergeMethod + "]. Nothing was changed";
        return false;
    }
    orderby += QString(", a.%1 asc").arg(idcol);

    /* the studies to merge into the target. The target itself is never in this list, so it's never deleted */
    QList<int> mergeStudyIDs;
    for (int id : SplitStringToIntArray(mergeIDs)) {
        if ((id > 0) && (id != studyid) && !mergeStudyIDs.contains(id))
            mergeStudyIDs.append(id);
    }
    if (mergeStudyIDs.isEmpty()) {
        msg = "No studies to merge into " + targetName + ". Nothing was changed";
        return false;
    }

    QMap<int, QString> studyPaths;
    studyPaths[studyid] = target.GetPath();
    QStringList mergeNames;
    for (int id : mergeStudyIDs) {
        study s(id, n);
        if (!s.isValid()) {
            msg = QString("Study [%1] was not valid: [%2]. Nothing was changed").arg(id).arg(s.msg);
            return false;
        }
        QString name = QString("%1%2").arg(s.GetUID()).arg(s.GetStudyNum());
        if (s.GetSubjectRowID() != target.GetSubjectRowID()) {
            msg = QString("Study %1 belongs to a different subject than %2. Nothing was changed").arg(name).arg(targetName);
            return false;
        }
        if (s.modality.toLower() != modality) {
            msg = QString("Study %1 is [%2] and %3 is [%4]. Only studies with the same modality can be merged. Nothing was changed").arg(name).arg(s.modality.toUpper()).arg(targetName).arg(modality.toUpper());
            return false;
        }
        studyPaths[id] = s.GetPath();
        mergeNames << name;
    }

    /* only <modality>_series is moved, so series in any other table would be lost when the merged studies are deleted */
    QString mergeIDList = JoinIntArray(mergeStudyIDs, ",");
    for (const QString &t : seriesTables) {
        if (t == table)
            continue;
        QSqlQuery q;
        q.prepare(QString("select count(*) 'count' from %1 where study_id in (%2)").arg(t).arg(mergeIDList));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.first() && (q.value("count").toInt() > 0)) {
            msg = QString("The studies being merged have %1 series in [%2], but only %3 series can be merged into %4. Nothing was changed").arg(q.value("count").toInt()).arg(t).arg(modality.toUpper()).arg(targetName);
            return false;
        }
    }

    /* get all of the series, in merge order */
    struct MergeSeries {
        int seriesid;
        int studyid;
        int oldnum;
        int newnum;
        QString oldpath;
        bool move;
    };
    QList<MergeSeries> list;
    QList<int> allStudyIDs = mergeStudyIDs;
    allStudyIDs.prepend(studyid);
    {
        QSqlQuery q;
        q.prepare(QString("select a.%1 'seriesid', a.study_id, a.series_num from %2 a left join studies b on a.study_id = b.study_id where a.study_id in (%3) order by %4").arg(idcol).arg(table).arg(JoinIntArray(allStudyIDs, ",")).arg(orderby));
        q.bindValue(":targetid", studyid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            MergeSeries ms;
            ms.seriesid = q.value("seriesid").toInt();
            ms.studyid = q.value("study_id").toInt();
            ms.oldnum = q.value("series_num").toInt();
            ms.newnum = 0;
            ms.move = false;
            list.append(ms);
        }
    }

    /* assign the new series numbers */
    QSet<int> used;
    int next = 1;
    for (MergeSeries &ms : list) {
        if (mergeMethod == "sortbyseriesnum") {
            int num = ms.oldnum;
            if ((num < 1) || used.contains(num)) {
                num = 10000 + qMax(ms.oldnum, 0);
                while (used.contains(num))
                    num++;
            }
            ms.newnum = num;
        }
        else {
            ms.newnum = next++;
        }
        used.insert(ms.newnum);

        /* a series directory only needs to move if it exists, and it's in another study or is being renumbered */
        ms.oldpath = QString("%1/%2").arg(studyPaths[ms.studyid]).arg(ms.oldnum);
        ms.move = QFileInfo(ms.oldpath).isDir() && ((ms.studyid != studyid) || (ms.oldnum != ms.newnum));
    }

    /* every new series path must be free, or be a directory that is about to be moved out of the way */
    QString targetPath = studyPaths[studyid];
    QSet<QString> vacated;
    for (const MergeSeries &ms : list)
        if (ms.move)
            vacated.insert(ms.oldpath);
    for (const MergeSeries &ms : list) {
        if ((ms.studyid == studyid) && (ms.oldnum == ms.newnum))
            continue; /* stays where it is */
        QString newpath = QString("%1/%2").arg(targetPath).arg(ms.newnum);
        QFileInfo fi(newpath);
        if ((fi.exists() || fi.isSymLink()) && !vacated.contains(newpath)) {
            msg = QString("[%1] already exists, and isn't a series being merged. Nothing was changed").arg(newpath);
            return false;
        }
    }

    /* ---------- move the series directories ---------- */
    QString m;
    if (!QDir(targetPath).exists() && !MakePath(targetPath, m)) {
        msg = QString("Unable to create the target study directory [%1] [%2]. Nothing was changed").arg(targetPath).arg(m);
        return false;
    }
    QString staging = QString("%1/.merge-%2").arg(targetPath).arg(GenerateRandomString(10));
    if (!QDir().mkpath(staging)) {
        msg = QString("Unable to create the staging directory [%1]. Nothing was changed").arg(staging);
        return false;
    }

    /* each completed move is recorded, so they can be undone if a later move fails */
    QList<QPair<QString, QString>> moves;
    auto moveDir = [&](const QString &from, const QString &to, QString &err) -> bool {
        n->ModuleRunningCheckIn();
        QFileInfo fi(to);
        if (fi.exists() || fi.isSymLink()) { /* mv would put 'from' inside 'to' */
            err = "[" + to + "] already exists";
            return false;
        }
        QString output;
        int ret = RunProgram("mv", { "--", from, to }, output);
        if (ret != 0) {
            err = QString("Moving [%1] to [%2] failed. mv returned [%3] %4").arg(from).arg(to).arg(ret).arg(output.trimmed());
            return false;
        }
        moves.append(qMakePair(from, to));
        return true;
    };
    auto rollback = [&](const QString &reason) -> bool {
        QStringList failed;
        for (int i = moves.size() - 1; i >= 0; i--) {
            QString output;
            if (RunProgram("mv", { "--", moves[i].second, moves[i].first }, output) != 0)
                failed << QString("[%1] -> [%2]").arg(moves[i].second).arg(moves[i].first);
        }
        QDir().rmdir(staging);
        if (failed.isEmpty())
            msg = n->Log(QString("Merge into %1 failed. %2. The directories were moved back. Nothing was changed").arg(targetName).arg(reason));
        else
            msg = n->Log(QString("Merge into %1 failed. %2. These directories could not be moved back, and need to be moved by hand: %3. The database was not changed").arg(targetName).arg(reason).arg(failed.join(", ")));
        return false;
    };

    /* first into the staging directory, so no series is moved onto a path another series still has */
    QString err;
    for (const MergeSeries &ms : list) {
        if (ms.move && !moveDir(ms.oldpath, QString("%1/%2").arg(staging).arg(ms.seriesid), err))
            return rollback(err);
    }
    /* then to the new series numbers */
    for (const MergeSeries &ms : list) {
        if (ms.move && !moveDir(QString("%1/%2").arg(staging).arg(ms.seriesid), QString("%1/%2").arg(targetPath).arg(ms.newnum), err))
            return rollback(err);
    }
    QDir().rmdir(staging);

    /* ---------- update the database ---------- */
    /* (study_id, series_num) is unique, so give every series a temporary number first */
    QSqlQuery q;
    int tmpnum = 1000000;
    for (const MergeSeries &ms : list) {
        q.prepare(QString("update %1 set study_id = :studyid, series_num = :seriesnum where %2 = :seriesid").arg(table).arg(idcol));
        q.bindValue(":studyid", studyid);
        q.bindValue(":seriesnum", tmpnum++);
        q.bindValue(":seriesid", ms.seriesid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }
    for (const MergeSeries &ms : list) {
        q.prepare(QString("update %1 set series_num = :seriesnum where %2 = :seriesid").arg(table).arg(idcol));
        q.bindValue(":seriesnum", ms.newnum);
        q.bindValue(":seriesid", ms.seriesid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if ((ms.studyid != studyid) || (ms.oldnum != ms.newnum))
            n->Log(QString("Series [%1] study [%2] series [%3] -> study [%4] series [%5]").arg(ms.seriesid).arg(ms.studyid).arg(ms.oldnum).arg(studyid).arg(ms.newnum));
    }

    /* ---------- delete the now-empty studies ---------- */
    QStringList deleteErrors;
    for (int id : mergeStudyIDs) {
        QString dm;
        if (!DeleteStudy(id, dm))
            deleteErrors << dm;
    }

    msg = QString("Merged %1 series from %2 into %3, using [%4]").arg(list.size()).arg(mergeNames.join(", ")).arg(targetName).arg(mergeMethod);
    if (!deleteErrors.isEmpty())
        msg += ". The series were merged, but these studies could not be deleted: " + deleteErrors.join(" | ");
    n->Log(msg);
    return true;
}
