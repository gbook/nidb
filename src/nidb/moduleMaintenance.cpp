/* ------------------------------------------------------------------------------
  NIDB moduleMaintenance.cpp
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
#include <QProcess>
#include <QTemporaryFile>
#include <QRegularExpression>
#include "moduleMaintenance.h"


/* ---------------------------------------------------------- */
/* --------- moduleMaintenance ------------------------------ */
/* ---------------------------------------------------------- */
moduleMaintenance::moduleMaintenance(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleMaintenance ----------------------------- */
/* ---------------------------------------------------------- */
moduleMaintenance::~moduleMaintenance()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Entry point for the maintenance module
 * @return 1 if the log file should be kept, 0 otherwise
 */
int moduleMaintenance::Run() {
    n->Log("Entering the maintenance module");

    n->ModuleRunningCheckIn();
    if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

    bool keepLog = false;

    /* ----- Directory cleaning ----- */

    /* clean cfg[exportdir] for 30 days old */
    QString m;
    qint64 numFiles(0), numBytes(0);
    if (CleanPath(n->cfg["exportdir"], 30, numFiles, numBytes, m)) {
        n->Log(QString("Cleaned exportdir [%1]. Cleaned %2 files and %3 bytes.").arg(n->cfg["exportdir"]).arg(numFiles).arg(numBytes));
    }
    else {
        keepLog = true;
        n->Log(QString("Error while cleaning exportdir [%1]. Cleaned %2 files and %3 bytes. Message [%4].").arg(n->cfg["exportdir"]).arg(numFiles).arg(numBytes).arg(m));
    }

    /* clean cfg[tmpdir] for 7 days old */
    m.clear();
    numFiles = 0;
    numBytes = 0;
    if (CleanPath(n->cfg["tmpdir"], 7, numFiles, numBytes, m)) {
        n->Log(QString("Cleaned tmpdir [%1]. Cleaned %2 files and %3 bytes.").arg(n->cfg["tmpdir"]).arg(numFiles).arg(numBytes));
    }
    else {
        keepLog = true;
        n->Log(QString("Error while cleaning tmpdir [%1]. Cleaned %2 files and %3 bytes. Message [%4].").arg(n->cfg["tmpdir"]).arg(numFiles).arg(numBytes).arg(m));
    }

    /* clean cfg[logdir] for 4 days old */
    m.clear();
    numFiles = 0;
    numBytes = 0;
    if (CleanPath(n->cfg["logdir"], 4, numFiles, numBytes, m)) {
        n->Log(QString("Cleaned logdir [%1]. Cleaned %2 files and %3 bytes.").arg(n->cfg["logdir"]).arg(numFiles).arg(numBytes));
    }
    else {
        keepLog = true;
        n->Log(QString("Error while cleaning logdir [%1]. Cleaned %2 files and %3 bytes. Message [%4].").arg(n->cfg["logdir"]).arg(numFiles).arg(numBytes).arg(m));
    }

    /* ----- database backup ----- */
    m.clear();
    QString sqlFilename;
    qint64 size(0);
    bool skipped(false);
    if (DumpSQLDatabase(sqlFilename, size, skipped, m)) {
        if (skipped) {
            n->Log(QString("Database dump for today already exists [%1] - %2 bytes. Skipping dump.").arg(sqlFilename).arg(size));
        }
        else {
            keepLog = true; /* keep a record of each successful backup */
            n->Log(QString("Dumped database [%1] - %2 bytes.").arg(sqlFilename).arg(size));
        }
    }
    else {
        keepLog = true;
        n->Log(QString("Error dumping database [%1] - %2 bytes. Message [%3]").arg(sqlFilename).arg(size).arg(m));
    }

    /* ----- delete SQL dumps in cfg[backupdir] older than 14 days ----- */
    m.clear();
    numFiles = 0;
    numBytes = 0;
    if (CleanOldSQLDumps(14, numFiles, numBytes, m)) {
        n->Log(QString("Cleaned old SQL dumps from backupdir [%1]. Deleted %2 files and %3 bytes.").arg(n->cfg["backupdir"]).arg(numFiles).arg(numBytes));
    }
    else {
        keepLog = true;
        n->Log(QString("Error while cleaning old SQL dumps from backupdir [%1]. Deleted %2 files and %3 bytes. Message [%4].").arg(n->cfg["backupdir"]).arg(numFiles).arg(numBytes).arg(m));
    }

    n->Log("Leaving the maintenance module");
    return keepLog;
}


/* ---------------------------------------------------------- */
/* --------- DumpSQLDatabase -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief moduleMaintenance::DumpSQLDatabase Dump the NiDB database to a gzipped .sql.gz file in cfg[backupdir] using
 * mysqldump piped into gzip.
 * Connection settings come from cfg[mysqlhost], cfg[mysqluser], cfg[mysqlpassword], cfg[mysqldatabase]. The
 * credentials are passed to mysqldump through a private (0600) temporary option file so the password never
 * appears on the command line (visible in `ps`). The dump is written to a .partial file and only renamed to
 * .sql.gz once both mysqldump and gzip succeed, so a failed or interrupted dump never leaves a truncated file that
 * looks valid. Only one dump is made per day: if a NiDB-<database>-<yyyyMMdd>-*.sql.gz dump for today already exists,
 * no new dump is made and skipped is set.
 *
 * Note: --single-transaction only gives a point-in-time snapshot of InnoDB tables. NiDB also has Aria tables, which
 * are dumped without locking, so rows written to Aria tables while the dump runs may be inconsistent with the
 * InnoDB data. Locking all tables would stall NiDB for the length of the dump, so this is accepted.
 * @param filename [return] Path to .sql.gz file on disk (today's existing dump if skipped)
 * @param size [return] Size of the (compressed) .sql.gz file on disk
 * @param skipped [return] true if a dump for today already existed and no new dump was made
 * @param m [return] Error message if unsuccessful
 * @return true if successful (or skipped), false otherwise
 */
bool moduleMaintenance::DumpSQLDatabase(QString &filename, qint64 &size, bool &skipped, QString &m) {

    filename.clear();
    size = 0;
    skipped = false;
    m.clear();

    QString backupDir = n->cfg["backupdir"].trimmed();
    QString host = n->cfg["mysqlhost"].trimmed();
    QString user = n->cfg["mysqluser"].trimmed();
    QString pass = n->cfg["mysqlpassword"];
    QString database = n->cfg["mysqldatabase"].trimmed();

    /* ----- validate the config ----- */
    if (backupDir == "") { m = "cfg[backupdir] is blank"; return false; }
    if (!QDir::isAbsolutePath(backupDir)) { m = QString("cfg[backupdir] [%1] must be an absolute path").arg(backupDir); return false; }
    if (database == "") { m = "cfg[mysqldatabase] is blank"; return false; }
    if (user == "") { m = "cfg[mysqluser] is blank"; return false; }
    if (host == "") host = "localhost";

    /* create the backup directory if needed */
    if (!QFileInfo(backupDir).isDir()) {
        QString mpMsg;
        if (!MakePath(backupDir, mpMsg, false)) {
            m = QString("Unable to create backupdir [%1]: %2").arg(backupDir).arg(mpMsg);
            return false;
        }
    }

    /* ----- only one dump per day: check for an existing (completed) dump from today ----- */
    QDateTime now = QDateTime::currentDateTime();
    QDir dir(backupDir);
    QFileInfoList todays = dir.entryInfoList(QStringList() << QString("NiDB-%1-%2-*.sql.gz").arg(database).arg(now.toString("yyyyMMdd")), QDir::Files | QDir::NoSymLinks, QDir::Name);
    for (const QFileInfo &f : todays) {
        if (f.size() > 0) {
            filename = f.absoluteFilePath();
            size = f.size();
            skipped = true;
            return true;
        }
    }

    /* ----- build the output filename ----- */
    QString datetime = now.toString("yyyyMMdd-HHmmss");
    filename = QString("%1/NiDB-%2-%3.sql.gz").arg(backupDir).arg(database).arg(datetime);
    QString partialFilename = filename + ".partial";
    QFile::remove(partialFilename);

    /* ----- write credentials to a private option file -----
       QTemporaryFile creates the file with 0600 permissions and deletes it when it goes out of scope. Option file
       values are quoted so special characters in the password (#, ;, spaces) are not misparsed */
    auto quoteOpt = [](QString v) { return "\"" + v.replace("\\", "\\\\").replace("\"", "\\\"") + "\""; };
    QString tmpDir = n->cfg["tmpdir"].trimmed();
    if ((tmpDir == "") || !QFileInfo(tmpDir).isDir())
        tmpDir = QDir::tempPath();
    QTemporaryFile optFile(tmpDir + "/nidb-mysqldump-XXXXXX.cnf");
    if (!optFile.open()) {
        m = QString("Unable to create temporary mysqldump option file in [%1]: %2").arg(tmpDir).arg(optFile.errorString());
        return false;
    }
    QString opts = QString("[client]\nhost=%1\nuser=%2\npassword=%3\n").arg(quoteOpt(host)).arg(quoteOpt(user)).arg(quoteOpt(pass));
    QByteArray optBytes = opts.toUtf8();
    if ((optFile.write(optBytes) != optBytes.size()) || !optFile.flush()) {
        m = QString("Unable to write temporary mysqldump option file [%1]: %2").arg(optFile.fileName()).arg(optFile.errorString());
        return false;
    }

    /* ----- run mysqldump | gzip -----
       --defaults-extra-file must be the first argument. --single-transaction gives a consistent snapshot of InnoDB
       tables without locking them, --quick streams rows instead of buffering whole tables in memory, --hex-blob
       keeps binary columns intact, and --max-allowed-packet=1G allows rows larger than the small client default. mysqldump's stdout is piped directly into gzip, which writes the .partial file */
    QStringList dumpArgs;
    dumpArgs << QString("--defaults-extra-file=%1").arg(optFile.fileName())
             << "--single-transaction"
             << "--quick"
             << "--hex-blob"
             << "--max-allowed-packet=1G"
             << "--triggers"
             << database;

    n->Log(QString("Running mysqldump of database [%1] on host [%2] | gzip to [%3]").arg(database).arg(host).arg(partialFilename));

    QProcess dump;
    QProcess gz;
    dump.setStandardOutputProcess(&gz);
    gz.setStandardOutputFile(partialFilename);

    /* start the reader first, then the writer */
    gz.start("gzip", QStringList() << "-c");
    if (!gz.waitForStarted()) {
        m = QString("Unable to start gzip: %1").arg(gz.errorString());
        QFile::remove(partialFilename);
        return false;
    }
    dump.start("mysqldump", dumpArgs);
    if (!dump.waitForStarted()) {
        m = QString("Unable to start mysqldump: %1").arg(dump.errorString());
        gz.closeWriteChannel();
        gz.waitForFinished(30000);
        QFile::remove(partialFilename);
        return false;
    }

    /* a large database can take a long time to dump, so check in periodically so the module isn't considered dead */
    QDateTime startTime = QDateTime::currentDateTime();
    while (!dump.waitForFinished(60000)) {
        if (dump.state() == QProcess::NotRunning)
            break;
        n->ModuleRunningCheckIn();
    }
    /* gzip exits once mysqldump closes the pipe; give it time to flush the remaining compressed data */
    while (!gz.waitForFinished(60000)) {
        if (gz.state() == QProcess::NotRunning)
            break;
        n->ModuleRunningCheckIn();
    }
    QString dumpOutput = QString(dump.readAllStandardError()).trimmed();
    QString gzOutput = QString(gz.readAllStandardError()).trimmed();
    qint64 elapsed = startTime.secsTo(QDateTime::currentDateTime());

    /* check gzip first: if gzip dies (e.g. disk full), mysqldump then fails writing to the broken pipe, and the
       mysqldump error alone would hide the real cause. Report both outputs whenever either fails */
    bool gzFailed = (gz.exitStatus() != QProcess::NormalExit) || (gz.exitCode() != 0);
    bool dumpFailed = (dump.exitStatus() != QProcess::NormalExit) || (dump.exitCode() != 0);
    if (gzFailed || dumpFailed) {
        m = QString("%1 failed after %2 seconds. gzip exit code [%3] output [%4]. mysqldump exit code [%5] output [%6]")
                .arg(gzFailed ? "gzip" : "mysqldump").arg(elapsed)
                .arg(gz.exitCode()).arg(gzOutput)
                .arg(dump.exitCode()).arg(dumpOutput);
        QFile::remove(partialFilename);
        return false;
    }

    /* ----- verify the dump and move it into place ----- */
    QFileInfo fi(partialFilename);
    if (!fi.exists() || (fi.size() <= 0)) {
        m = QString("mysqldump and gzip reported success but [%1] is missing or empty. Output [%2]").arg(partialFilename).arg(dumpOutput);
        QFile::remove(partialFilename);
        return false;
    }

    QFile::remove(filename);
    if (!QFile::rename(partialFilename, filename)) {
        m = QString("Unable to rename [%1] to [%2]").arg(partialFilename).arg(filename);
        QFile::remove(partialFilename);
        return false;
    }

    size = QFileInfo(filename).size();
    if (dumpOutput != "")
        n->Log(QString("mysqldump output [%1]").arg(dumpOutput));
    if (gzOutput != "")
        n->Log(QString("gzip output [%1]").arg(gzOutput));
    n->Log(QString("mysqldump | gzip completed in %1 seconds").arg(elapsed));

    return true;
}


/* ---------------------------------------------------------- */
/* --------- CleanOldSQLDumps ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief moduleMaintenance::CleanOldSQLDumps Delete SQL dumps in cfg[backupdir] older than X days. Only regular files
 * directly in backupdir (not in subdirectories) whose names end in .sql, .sql.gz, or .sql.gz.partial are considered,
 * so the archive backup copies stored in backupdir subdirectories are never touched. Symlinks are skipped.
 * @param days Delete dumps last modified more than this many days ago
 * @param numFiles [return] Number of files deleted
 * @param numBytes [return] Number of bytes deleted
 * @param m [return] Error message if unsuccessful
 * @return true if successful, false if the config is invalid or any file could not be deleted
 */
bool moduleMaintenance::CleanOldSQLDumps(int days, qint64 &numFiles, qint64 &numBytes, QString &m) {

    numFiles = 0;
    numBytes = 0;
    m.clear();

    QString backupDir = n->cfg["backupdir"].trimmed();
    if (days < 1) { m = QString("days [%1] is less than 1").arg(days); return false; }
    if (backupDir == "") { m = "cfg[backupdir] is blank"; return false; }
    if (!QDir::isAbsolutePath(backupDir)) { m = QString("cfg[backupdir] [%1] must be an absolute path").arg(backupDir); return false; }
    if (!QFileInfo(backupDir).isDir()) { m = QString("backupdir [%1] does not exist or is not a directory").arg(backupDir); return false; }

    QDateTime cutoffDate = QDateTime::currentDateTime().addDays(-days);

    /* top level only, regular files only (QDir::NoSymLinks excludes symlinks, QDir::Files excludes directories) */
    QDir dir(backupDir);
    QStringList filters;
    filters << "*.sql" << "*.sql.gz" << "*.sql.gz.partial";
    QFileInfoList files = dir.entryInfoList(filters, QDir::Files | QDir::NoSymLinks | QDir::Hidden, QDir::Name);

    QStringList errors;
    for (const QFileInfo &f : files) {
        if (f.lastModified() >= cutoffDate)
            continue;

        qint64 fileSize = f.size();
        if (QFile::remove(f.absoluteFilePath())) {
            numFiles++;
            numBytes += fileSize;
            n->Log(QString("Deleted old SQL dump [%1] (%2 bytes, last modified %3)").arg(f.absoluteFilePath()).arg(fileSize).arg(f.lastModified().toString("yyyy-MM-dd HH:mm:ss")));
        }
        else
            errors << f.absoluteFilePath();
    }

    if (!errors.isEmpty()) {
        m = QString("Unable to delete %1 file(s): %2").arg(errors.size()).arg(errors.join(", "));
        return false;
    }

    return true;
}
