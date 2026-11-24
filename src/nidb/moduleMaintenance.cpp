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
        n->Log(QString("Error while cleaning logdir [%1]. Cleaned %2 files and %3 bytes. Message [%4].").arg(n->cfg["logdir"]).arg(numFiles).arg(numBytes).arg(m));
    }

    /* ----- database backup ----- */
    m.clear();
    QString sqlFilename;
    qint64 size(0);
    if (DumpSQLDatabase(sqlFilename, size, m)) {
        n->Log(QString("Dumped database [%1] - %2 bytes.").arg(sqlFilename).arg(size));
    }
    else {
        n->Log(QString("Error dumping database [%1] - %2 bytes. Message [%3]").arg(sqlFilename).arg(size).arg(m));
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
 * looks valid.
 * @param filename [return] Path to .sql.gz file on disk
 * @param size [return] Size of the (compressed) .sql.gz file on disk
 * @param m [return] Error message if unsuccessful
 * @return true if successful, false otherwise
 */
bool moduleMaintenance::DumpSQLDatabase(QString &filename, qint64 &size, QString &m) {

    filename.clear();
    size = 0;
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

    /* ----- build the output filename ----- */
    QString datetime = QDateTime::currentDateTime().toString("yyyyMMdd-HHmmss");
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
    optFile.write(opts.toUtf8());
    optFile.flush();

    /* ----- run mysqldump | gzip -----
       --defaults-extra-file must be the first argument. --single-transaction gives a consistent snapshot of InnoDB
       tables without locking them, --quick streams rows instead of buffering whole tables in memory, and --hex-blob
       keeps binary columns intact. mysqldump's stdout is piped directly into gzip, which writes the .partial file */
    QStringList dumpArgs;
    dumpArgs << QString("--defaults-extra-file=%1").arg(optFile.fileName())
             << "--single-transaction"
             << "--quick"
             << "--hex-blob"
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

    if ((dump.exitStatus() != QProcess::NormalExit) || (dump.exitCode() != 0)) {
        m = QString("mysqldump failed after %1 seconds with exit code [%2]. Output [%3]").arg(elapsed).arg(dump.exitCode()).arg(dumpOutput);
        QFile::remove(partialFilename);
        return false;
    }
    if ((gz.exitStatus() != QProcess::NormalExit) || (gz.exitCode() != 0)) {
        m = QString("gzip failed after %1 seconds with exit code [%2]. Output [%3]").arg(elapsed).arg(gz.exitCode()).arg(gzOutput);
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
