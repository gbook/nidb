/* ------------------------------------------------------------------------------
  NIDB nidb.cpp
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

#include "nidb.h"

/* ---------------------------------------------------------- */
/* --------- nidb ------------------------------------------- */
/* ---------------------------------------------------------- */
nidb::nidb()
{
    pid = QCoreApplication::applicationPid();
}


/* ---------------------------------------------------------- */
/* --------- nidb ------------------------------------------- */
/* ---------------------------------------------------------- */
nidb::nidb(QString m, bool c)
{
    module = m;
    runningFromCluster = c;

    pid = QCoreApplication::applicationPid();

    LoadConfig();
}


/* ---------------------------------------------------------- */
/* --------- GetBuildString --------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GetBuildString() {
    return QString("   NiDB version %1.%2.%3\n   Build date [%4 %5]\n   C++ [%6]\n   Qt compiled [%7]\n   Qt runtime [%8]\n   Build system [%9]").arg(VERSION_MAJ).arg(VERSION_MIN).arg(BUILD_NUM).arg(__DATE__).arg(__TIME__).arg(__cplusplus).arg(QT_VERSION_STR).arg(qVersion()).arg(QSysInfo::buildAbi());
}


/* ---------------------------------------------------------- */
/* --------- Print ------------------------------------------ */
/* ---------------------------------------------------------- */
void nidb::Print(QString s, bool n, bool pad) {
    if (n)
        if (pad)
            printf("%-80s\n", s.toStdString().c_str());
        else
            printf("%s\n", s.toStdString().c_str());
    else
        if (pad)
            printf("%-80s", s.toStdString().c_str());
        else
            printf("%s", s.toStdString().c_str());
}


/* ---------------------------------------------------------- */
/* --------- LoadConfig ------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::LoadConfig() {

    if (configLoaded) return 1;

    /* get the directory in which this application binary lives */
    QString binpath = QCoreApplication::applicationDirPath();

    /* list of possible locations for the config file */
    QStringList files;
    if (runningFromCluster)
        files << binpath + "/nidb-cluster.cfg";
    else
        files << binpath + "/nidb.cfg"
            << binpath + "/../nidb.cfg"
            << binpath + "/../../nidb.cfg"
            << binpath + "/../../../nidb.cfg"
            << binpath + "/../../prod/programs/nidb.cfg"
            << binpath + "/../../../../prod/programs/nidb.cfg"
            << binpath + "/../programs/nidb.cfg"
            << "/nidb/nidb.cfg"
            << "/nidb/bin/nidb.cfg"
            << "/home/nidb/programs/nidb.cfg"
            << "/nidb/programs/nidb.cfg"
            << "M:/programs/nidb.cfg";

    QFile f;
    bool found = false;
    for (int i=0;i<files.size();i++) {
        QFileInfo finfo(files[i]);
        QString abspath = finfo.absoluteFilePath();
        if (f.exists(abspath)) {
            f.setFileName(abspath);
            found = true;
        }
    }

    if (!found) {
        Print("Config file not found");
        return false;
    }

    if (!runningFromCluster)
        Print("Loading config file " + f.fileName(), false, true);

    /* open and read the config file */
    if (f.open(QIODevice::ReadOnly | QIODevice::Text)) {

        QTextStream in(&f);
        int lineno = 0;
        while (!in.atEnd()) {
            QString line = in.readLine();
            lineno++;
            if ((line.trimmed().count() > 0) && (line.at(0) != '#')) {
                QStringList parts = line.split(" = ");
                if (parts.size() >= 2) {
                    QString var = parts[0].trimmed();
                    QString value = parts[1].trimmed();
                    var.remove('[').remove(']');
                    if (var != "")
                        cfg[var] = value;
                }
                else {
                    Print(QString("Weird config file entry [%1] on line [%2]").arg(line.trimmed()).arg(lineno));
                }
            }
        }
        f.close();
        configLoaded = true;

        if (!runningFromCluster)
            Print("[\033[0;32mOk\033[0m]");

        return true;
    }
    else {
        Print("[\033[0;31mError\033[0m]");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- DatabaseConnect -------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::DatabaseConnect(bool cluster) {

    if (!cluster) Print("Connecting to database", false, true);

    if (cfg["debug"].toInt())
        qDebug()<< QSqlDatabase::drivers();

    db = QSqlDatabase::addDatabase("QMYSQL");
    db.setHostName(cfg["mysqlhost"]);
    db.setDatabaseName(cfg["mysqldatabase"]);
    if (cluster) {
        db.setUserName(cfg["mysqlclusteruser"]);
        db.setPassword(cfg["mysqlclusterpassword"]);
    }
    else {
        db.setUserName(cfg["mysqluser"]);
        db.setPassword(cfg["mysqlpassword"]);
    }

    if (db.open()) {
        if (!cluster)
            Print("[\033[0;32mOk\033[0m]");
        return true;
    }
    else {
        QString err = "[\033[0;31mError\033[0m]\n\tUnable to connect to database. Error message [" + db.lastError().text() + "]";

        FatalError(err);
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- FatalError ------------------------------------- */
/* ---------------------------------------------------------- */
void nidb::FatalError(QString err) {
    Print(err);
    exit(0);
}


/* ---------------------------------------------------------- */
/* --------- ModuleGetNumThreads ---------------------------- */
/* ---------------------------------------------------------- */
int nidb::ModuleGetNumThreads() {
    int numThreads = 0;

    if (module == "fileio") {
        if (cfg["modulefileiothreads"] == "") numThreads = 1;
        else numThreads = cfg["modulefileiothreads"].toInt();
    }
    else if (module == "export") {
        if (cfg["moduleexportthreads"] == "") numThreads = 1;
        else numThreads = cfg["moduleexportthreads"].toInt();
    }
    else if ((module == "parsedicom") || (module == "import")) {
        numThreads = 1;
    }
    else if (module == "mriqa") {
        if (cfg["modulemriqathreads"] == "") numThreads = 1;
        else numThreads = cfg["modulemriqathreads"].toInt();
    }
    else if (module == "pipeline") {
        if (cfg["modulepipelinethreads"] == "") numThreads = 1;
        else numThreads = cfg["modulepipelinethreads"].toInt();
    }
    else if (module == "minipipeline") {
        if (cfg["moduleminipipelinethreads"] == "") numThreads = 1;
        else numThreads = cfg["moduleminipipelinethreads"].toInt();
    }
    else if (module == "importuploaded") {
        numThreads = 1;
    }
    else if (module == "qc") {
        if (cfg["moduleqcthreads"] == "") numThreads = 1;
        else numThreads = cfg["moduleqcthreads"].toInt();
    }
    else if (module == "upload") {
        if (cfg["moduleuploadthreads"] == "") numThreads = 1;
        else numThreads = cfg["moduleuploadthreads"].toInt();
    }
    else if (module == "backup") {
        numThreads = 1;
    }

    WriteLog(QString("ModuleGetNumThreads() returned [%1] threads for module [%2]").arg(numThreads).arg(module));
    return numThreads;
}


/* ---------------------------------------------------------- */
/* --------- ModuleGetNumLockFiles -------------------------- */
/* ---------------------------------------------------------- */
int nidb::ModuleGetNumLockFiles() {
    QDir dir;
    dir.setPath(cfg["lockdir"]);

    QString lockfileprefix = QString("%1.*").arg(module);
    QStringList filters;
    filters << lockfileprefix;

    QStringList files = dir.entryList(filters);
    int numlocks = files.size();

    Print(QString("Found [%1] lockfiles for module [%2]").arg(numlocks).arg(module));
    WriteLog(QString("ModuleGetNumLockFiles() found [%1] lockfiles for module [%2]").arg(numlocks).arg(module));

    return numlocks;
}


/* ---------------------------------------------------------- */
/* --------- ModuleCreateLockFile --------------------------- */
/* ---------------------------------------------------------- */
bool nidb::ModuleCreateLockFile() {
    qint64 pid = 0;
    pid = QCoreApplication::applicationPid();

    lockFilepath = QString("%1/%2.%3").arg(cfg["lockdir"]).arg(module).arg(pid);

    Print("Creating lock file [" + lockFilepath + "]",false, true);
    QFile f(lockFilepath);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QString d = CreateCurrentDateTime();
        QTextStream fs(&f);
        fs << d;
        f.close();
        Print("[\033[0;32mOk\033[0m]");
        return 1;
    }
    else {
        Print("[\033[0;31mError\033[0m]");
        return 0;
    }
}


/* ---------------------------------------------------------- */
/* --------- ModuleClearLockFiles --------------------------- */
/* ---------------------------------------------------------- */
bool nidb::ModuleClearLockFiles() {

    Print("Clearing lock files [" + lockFilepath + "]",false, true);
    QString s = QString("rm -v %1/%2*").arg(cfg["lockdir"]).arg(module);
    SystemCommand(s, true);
    Print("[\033[0;32mOk\033[0m]");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ModuleCreateLogFile ---------------------------- */
/* ---------------------------------------------------------- */
bool nidb::ModuleCreateLogFile () {
    logFilepath = QString("%1/%2%3.log").arg(cfg["logdir"]).arg(module).arg(CreateLogDate());
    log.setFileName(logFilepath);

    Print("Creating log file [" + logFilepath + "]",false, true);
    if (log.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Unbuffered)) {
        QString padding = "";
        if (pid < 1000) padding = "";
        else if (pid < 10000) padding = " ";
        else padding = "  ";
        log.write(GetBuildString().toLatin1());
        Print("[\033[0;32mOk\033[0m]");
        return 1;
    }
    else {
        Print("[\033[0;31mError\033[0m]");
        return 0;
    }
}


/* ---------------------------------------------------------- */
/* --------- ModuleDeleteLockFile --------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleDeleteLockFile() {

    Print("Deleting lock file [" + lockFilepath + "]",false, true);

    QFile f(lockFilepath);
    if (f.remove()) {
        Print("[\033[0;32mOk\033[0m]");
        WriteLog("Successfully removed lock file [" + lockFilepath + "]");
    }
    else {
        Print("[\033[0;31mError\033[0m]");
        WriteLog("Error removing lock file [" + lockFilepath + "]");
    }
}


/* ---------------------------------------------------------- */
/* --------- ModuleRemoveLogFile ---------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleRemoveLogFile(bool keepLog) {

    if (!keepLog) {
        Print("Deleting log file [" + logFilepath + "]",false, true);
        QFile f(logFilepath);
        if (f.remove())
            Print("[\033[0;32mOk\033[0m]");
        else
            Print("[\033[0;31mError\033[0m]");
    }
    else
        Print("Keeping log file [" + logFilepath + "]");
}


/* ---------------------------------------------------------- */
/* --------- CreateCurrentDate ------------------------------ */
/* ---------------------------------------------------------- */
QString nidb::CreateCurrentDateTime(int format) {
    QString date;

    QDateTime d = QDateTime::currentDateTime();
    switch (format) {
        case 1:
            date = d.toString("yyyy/MM/dd HH:mm:ss"); break;
        case 2:
            date = d.toString("yyyy-MM-dd HH:mm:ss"); break;
        case 3:
            date = d.toString("yyyy/MM/dd"); break;
        case 4:
            date = d.toString("yyyy-MM-dd"); break;
        case 5:
            date = d.toString("HH:mm:ss"); break;
        default:
            date = d.toString("yyyy/MM/dd HH:mm:ss");
    }

    return date;
}


/* ---------------------------------------------------------- */
/* --------- CreateLogDate ---------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::CreateLogDate() {
    QString date;

    QDateTime d = QDateTime::currentDateTime();
    date = d.toString("yyyyMMddHHmmss");

    return date;
}


/* ---------------------------------------------------------- */
/* --------- SQLQuery --------------------------------------- */
/* ---------------------------------------------------------- */
/* QSqlQuery object must already be prepared and bound before */
/* being passed in to this function                           */
QString nidb::SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d, bool batch) {

    /* get the SQL string that will be run */
    QString sql = q.lastQuery();
    QMapIterator<QString, QVariant> it(q.boundValues());
    while (it.hasNext()) {
        it.next();
        sql.replace(it.key(),it.value().toString());
    }

    /* debugging */
    if (cfg["debug"].toInt() || d) {
        WriteLog(sql);
    }

    /* run the query */
    if (batch)
        if (q.execBatch(QSqlQuery::ValuesAsRows))
            return sql;
    if (q.exec())
        return sql;

    /* if we get to this point, there is a SQL error */
    QString err = QString("SQL ERROR (Module: %1 Function: %2 File: %3 Line: %4)\n\nSQL (1) [%5]\n\nSQL (2) [%6]\n\nDatabase error [%7]\n\nDriver error [%8]").arg(module).arg(function).arg(file).arg(line).arg(sql).arg(q.executedQuery()).arg(q.lastError().databaseText()).arg(q.lastError().driverText());
    SendEmail(cfg["adminemail"], "SQL error", err);
    qDebug() << err;
    qDebug() << q.lastError();
    WriteLog(err);
    WriteLog("SQL error, exiting program");

    /* record error in error_log */
    QSqlQuery q2;
    q2.prepare("insert into error_log (error_hostname, error_type, error_source, error_module, error_date, error_message) values ('localhost', 'sql', 'backend', :module, now(), :msg)");
    q2.bindValue(":module", file);
    q2.bindValue(":msg", err);
    q2.exec();

    exit(0);
}


/* ---------------------------------------------------------- */
/* --------- ModuleCheckIfActive ---------------------------- */
/* ---------------------------------------------------------- */
bool nidb::ModuleCheckIfActive() {

    QSqlQuery q;
    q.prepare("select * from modules where module_name = :module and module_isactive = 1");
    q.bindValue(":module", module);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() < 1) {
        WriteLog("ModuleCheckIfActive() returned false");
        return false;
    }
    else {
        WriteLog("ModuleCheckIfActive() returned true");
        return true;
    }
}


/* ---------------------------------------------------------- */
/* --------- ModuleDBCheckIn -------------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleDBCheckIn() {
    Print("Checking module into database",false, true);
    QSqlQuery q;
    q.prepare("update modules set module_laststart = now(), module_status = 'running', module_numrunning = module_numrunning + 1 where module_name = :module");
    q.bindValue(":module", module);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.numRowsAffected() > 0) {
        Print("[\033[0;32mOk\033[0m]");
    }
    else {
        Print("[\033[0;31mError\033[0m]");
    }

    /* check if the module should be in a debug state */
    q.prepare("select module_debug from modules where module_name = :module");
    q.bindValue(":module", module);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        if (q.value("module_debug").toBool())
            cfg["debug"] = "1";
    }

}


/* ---------------------------------------------------------- */
/* --------- ModuleDBCheckOut ------------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleDBCheckOut() {
    QSqlQuery q;
    q.prepare("update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = :module");
    q.bindValue(":module", module);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from module_procs where module_name = :module and process_id = :pid");
    q.bindValue(":module", module);
    q.bindValue(":pid", pid);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    Print("Module checked out of database");
}


/* ---------------------------------------------------------- */
/* --------- ModuleRunningCheckIn --------------------------- */
/* ---------------------------------------------------------- */
/* this is a deadman's switch. if the module doesn't check in
   after a certain period of time, the module is assumed to
   be dead and is reset so it can start again
   ---------------------------------------------------------- */
void nidb::ModuleRunningCheckIn() {

    Print(".",false);

    QSqlQuery q;
    if (!checkedin) {
        q.prepare("insert ignore into module_procs (module_name, process_id) values (:module, :pid)");
        q.bindValue(":module", module);
        q.bindValue(":pid", pid);
        SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        checkedin = true;
    }

    /* update the checkin time */
    q.prepare("update module_procs set last_checkin = now() where module_name = :module and process_id = :pid");
    q.bindValue(":module", module);
    q.bindValue(":pid", pid);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- InsertAnalysisEvent ---------------------------- */
/* ---------------------------------------------------------- */
void nidb::InsertAnalysisEvent(qint64 analysisid, int pipelineid, int pipelineversion, int studyid, QString event, QString message) {
    QSqlQuery q;
    q.prepare("insert into analysis_history (analysis_id, pipeline_id, pipeline_version, study_id, analysis_event, analysis_hostname, event_message) values (:analysisid, :pipelineid, :pipelineversion, :studyid, :event, :hostname, :message)");
    q.bindValue(":analysisid", analysisid);
    q.bindValue(":pipelineid", pipelineid);
    q.bindValue(":pipelineversion", pipelineversion);
    q.bindValue(":studyid", studyid);
    q.bindValue(":event", event);
    q.bindValue(":hostname", QHostInfo::localHostName());
    q.bindValue(":message", message);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- SystemCommand ---------------------------------- */
/* ---------------------------------------------------------- */
/* this function does not work in Windows                     */
/* ---------------------------------------------------------- */
QString nidb::SystemCommand(QString s, bool detail, bool truncate, bool progress) {

    double starttime = QDateTime::currentMSecsSinceEpoch();
    QString ret;
    QString output;
    QProcess process;

    process.setProcessChannelMode(QProcess::MergedChannels);
    process.start("sh", QStringList() << "-c" << s);

    /* Get the output */
    if (process.waitForStarted(-1)) {
        while(process.waitForReadyRead(-1)) {
            QString buffer = process.readAll();
            output += buffer;
            if (progress)
                WriteLog(buffer,0,false);
        }
    }
    process.waitForFinished();

    double elapsedtime = (QDateTime::currentMSecsSinceEpoch() - starttime + 0.000001)/1000.0; /* add tiny decimal to avoid a divide by zero */

    output = output.trimmed();
    output.replace("’", "'");
    output.replace("‘", "'");

    if (truncate)
        if (output.size() > 20000)
            output = output.left(10000) + "\n\n     ...\n\n     OUTPUT TRUNCATED. Displaying only first and last 10,000 characters\n\n     ...\n\n" + output.right(10000);

    if (detail)
        ret = QString("Executed command [%1], Output [%2], elapsed time [%3 sec]").arg(s).arg(output).arg(elapsedtime, 0, 'f', 3);
    else
        ret = output;

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- SandboxedSystemCommand ------------------------- */
/* ---------------------------------------------------------- */
/* this function does not work in Windows                     */
/* ---------------------------------------------------------- */
bool nidb::SandboxedSystemCommand(QString s, QString dir, QString &output, QString timeout, bool detail, bool truncate) {

    double starttime = QDateTime::currentMSecsSinceEpoch();
    bool ret = true;
    QString outStr;
    QProcess process;
    double elapsedtime(0.0);

    /* check if the temp directory exists */
    QDir d(dir);
    if (!d.exists()) {
        output = "Error, sandbox dir [" + dir + "] does not exist";
        return false;
    }

    /* change to the home directory, which is where the jailed files will appear after running "firejail --private" */
    QDir::setCurrent("~");
    process.setProcessChannelMode(QProcess::MergedChannels);
    /* start the process */
    process.start("sh", QStringList() << "-c" << "firejail --timeout=" + timeout + " --quiet --private-cwd --private=" + dir + " ./" + s);
    QString command = "sh -cl 'firejail --timeout=" + timeout + " --quiet --private-cwd --private=" + dir + " ./" + s + "'";

    /* get the output, and wait for it to finish */
    if (process.waitForStarted(-1)) {
        while(process.waitForReadyRead(-1)) {
            outStr += process.readAll();
        }
    }
    process.waitForFinished();

    /* process should be done by now, check if there was an error */
    if ((process.errorString().trimmed() != "") && (process.errorString().trimmed() != "Unknown error")) {
        outStr += QString("Error [%1]. Exit status [%2]").arg(process.errorString()).arg(process.exitStatus());
        switch (process.error()) {
            case QProcess::FailedToStart: outStr += "Program failed to start. Executable not found?"; break;
            case QProcess::Crashed: outStr += "Program crashed"; break;
            case QProcess::Timedout: outStr += "Program timed out"; break;
            case QProcess::WriteError: outStr += "Program encountered a write error"; break;
            case QProcess::ReadError: outStr += "Program encountered a write error"; break;
            case QProcess::UnknownError: outStr += "Program encountered unknown error"; break;
        }
        ret = false;
    }
    else {
        elapsedtime = (QDateTime::currentMSecsSinceEpoch() - starttime + 0.000001)/1000.0; /* add tiny decimal to avoid a divide by zero */

        outStr = outStr.trimmed();
        outStr.replace("’", "'");
        outStr.replace("‘", "'");

        /* truncate only if there was no error */
        if (truncate)
            if (outStr.size() > 10000)
                outStr = outStr.left(5000) + "\n\n     ...\n\n     OUTPUT TRUNCATED. Displaying only first and last 5,000 characters\n\n     ...\n\n" + outStr.right(5000);
    }

    /* format the final output */
    if (detail)
        output = QString("Executed command [%1], Output [%2], elapsed time [%3 sec]").arg(command).arg(outStr).arg(elapsedtime, 0, 'f', 3);
    else
        output = outStr;

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- WriteLog --------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::WriteLog(QString msg, int wrap, bool timeStamp) {
    if (msg.trimmed() != "") {
        if (wrap > 0)
            msg = WrapText(msg, wrap);
        if (log.isWritable()) {
            bool success;
            if (timeStamp)
                success = log.write(QString("\n[%1][%2] %3").arg(CreateCurrentDateTime()).arg(pid).arg(msg).toLatin1());
            else
                success = log.write(QString("%3").toLatin1());

            if (!success)
                Print("Unable to write to log file!");
        }
        else {
            Print("Unable to write to log file. Maybe the logfile hasn't been created yet? Tried to write [" + msg + "] to [" + log.fileName() + "]");
        }
    }

    return msg;
}


/* ---------------------------------------------------------- */
/* --------- MakePath --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MakePath(QString p, QString &msg, bool perm777) {

    if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p == "/root") || (p == "/home")) {
        msg = "Path [" + p + "] is not valid";
        return false;
    }

    QDir d(p);

    if(!d.exists() && !d.mkpath(p)) {
        msg = "MakePath() Error creating path [" + p + "]";
        return false;
    }
    else
        msg = "MakePath() Path already exists or was created successfuly [" + p + "]";

    if (perm777)
        SystemCommand("chmod 777 " + p);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- RemoveDir -------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::RemoveDir(QString p, QString &msg) {

    if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p.startsWith("/root")) || (p == "/home")) {
        msg = "Path is not valid [" + p + "]";
        return false;
    }

    QDir path(p);
    if (path.removeRecursively()) {
        return true;
    }
    else {
        msg = "Unable to delete directory";
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- GenerateRandomString --------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GenerateRandomString(int n) {

   const QString chars("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789");
   QString randomString;
   for(int i=0; i<n; ++i)
   {
       QChar nextChar = chars.at(QRandomGenerator::global()->bounded(chars.length()-1));
       randomString.append(nextChar);
   }
   return randomString;
}


/* ---------------------------------------------------------- */
/* --------- MoveFile --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MoveFile(QString f, QString dir, QString &m) {

    QDir d;
    if (d.exists(dir)) {
        QString systemstring;
        systemstring = QString("mv %1 %2/").arg(f).arg(dir);

        QString output = SystemCommand(systemstring, false).trimmed();
        if (output != "") {
            m = output;
            return false;
        }
    }
    else {
        m = QString("Directory [%1] does not exist").arg(dir);
        return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- RenameFile ------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::RenameFile(QString filepathorig, QString filepathnew, bool force) {

    if (filepathorig == filepathnew) {
        //WriteLog("RenameFile - old and new filename are the same");
        return true;
    }

    QString systemstring;
    if (force)
        systemstring = QString("mv -f %1 %2").arg(filepathorig).arg(filepathnew);
    else
        systemstring = QString("mv %1 %2").arg(filepathorig).arg(filepathnew);

    QString output = SystemCommand(systemstring, false).trimmed();
    /* check if there's an error message from mv */
    if (output == "")
        return true;
    else {
        WriteLog("RenameFile() error. Running [" + systemstring + "] produced output [" + output + "]");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- FindAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
QStringList nidb::FindAllFiles(QString dir, QString pattern, bool recursive) {
    //if (cfg["debug"] == "1") WriteLog("Finding all files in ["+dir+"] with pattern ["+pattern+"]");

    QStringList files;
    if (recursive) {
        QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
        while (it.hasNext())
            files << it.next();
    }
    else {
        QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::NoIteratorFlags);
        while (it.hasNext())
            files << it.next();
    }

    return files;
}


/* ---------------------------------------------------------- */
/* --------- FindFirstFile ---------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::FindFirstFile(QString dir, QString pattern, QString &f, QString &msg, bool recursive) {

    QDir d = QDir(dir);
    if (!d.exists()) {
        msg = "Directory [" + dir + "] does not exist";
        return false;
    }

    f = "";

    if (recursive) {
        QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
        if (it.hasNext())
            f = it.next();
    }
    else {
        QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks);
        if (it.hasNext())
            f = it.next();
    }

    if (f.size() == 0)
        return false;
    else
        return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg) {
    QStringList msgs;
    bool ret = true;
    QDirIterator it(indir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        QFile f(it.next());
        QString newfile = QString("%1/%2.dcm").arg(outdir).arg(GenerateRandomString(20));
        if (!f.rename(newfile)) {
            msgs << QString("Error moving [%1] to [%2]").arg(QFileInfo(f).filePath()).arg(newfile);
            ret = false;
        }
    }

    msg = msgs.join(" | ");
    return ret;
}

/* ---------------------------------------------------------- */
/* --------- FindAllDirs ------------------------------------ */
/* ---------------------------------------------------------- */
QStringList nidb::FindAllDirs(QString dir, QString pattern, bool recursive, bool includepath) {

    if (pattern.trimmed() == "")
        pattern = "*";

    QStringList dirs;

    if (recursive) {
        QDirIterator it(dir, QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
        while (it.hasNext()) {
            if (includepath)
                dirs << it.next();
            else {
                it.next();
                dirs << it.fileName();
            }
        }
    }
    else {
        QDirIterator it(dir, QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks);
        while (it.hasNext()) {
            if (includepath)
                dirs << it.next();
            else {
                it.next();
                dirs << it.fileName();
            }
        }
    }

    return dirs;
}


/* ---------------------------------------------------------- */
/* --------- GetDirSizeAndFileCount ------------------------- */
/* ---------------------------------------------------------- */
void nidb::GetDirSizeAndFileCount(QString dir, int &c, qint64 &b, bool recurse) {
    c = 0;
    b = 0;

    QDir d(dir);

    if (recurse) {
        QDirIterator it(dir, QStringList() << "*", QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
        while (it.hasNext()) {
            it.next();
            c++;
            b += it.fileInfo().size();
        }
    }
    else {
        QFileInfoList fl = d.entryInfoList(QDir::NoDotAndDotDot | QDir::Files);
        c = fl.size();
        for (int i=0; i < fl.size(); i++) {
            const QFileInfo finfo = fl.at(i);
            b += finfo.size();
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- UnzipDirectory --------------------------------- */
/* ---------------------------------------------------------- */
/* perform one pass through a directory and attempt to unzip
 * any zipped files in it */
QString nidb::UnzipDirectory(QString dir, bool recurse) {

    QStringList msgs;

    if (dir.trimmed() == "") {
        msgs << "Empty directory specified. Not attempting to unzip";
    }
    else {
        //msgs << "Directory before unzipping [" + dir + "] contains " + SystemCommand("ls " + dir, false);
        for (int i=0; i<3; i++) {
            QString prefix = QString("Unzipping pass [%1]: ").arg(i);
            QString maxdepth;
            if (recurse)
                maxdepth = "";
            else
                maxdepth = "-maxdepth 0";

            QStringList cmds;
            cmds << QString("cd %1; find . %2 -name '*.tar.gz' -exec tar -zxf {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.gz' -exec gunzip {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.z' -exec gunzip {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -iname '*.zip' -exec sh -c 'unzip -o -q -d \"${0%.*}\" \"$0\" && rm -v {}' '{}' ';'").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.tar.bz2' -exec tar -xjf {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.bz2' -exec bunzip {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.tar' -exec tar -xf {} \\;").arg(dir).arg(maxdepth);

            foreach (QString cmd, cmds) {
                QString output;
                output = SystemCommand(cmd,false);
                if (output != "")
                    msgs << prefix + output;
            }
        }
        //msgs << "Directory after unzipping [" + dir + "] contains " + SystemCommand("ls " + dir, false);
    }

    return msgs.join('\n');
}


/* ---------------------------------------------------------- */
/* --------- SendEmail -------------------------------------- */
/* ---------------------------------------------------------- */
/* OpenSSL 1.0.x required for Qt compatibility                */
/* ---------------------------------------------------------- */
bool nidb::SendEmail(QString to, QString subject, QString body) {

    SmtpClient smtp(cfg["emailserver"].replace("tls://",""), cfg["emailport"].toInt(), SmtpClient::TlsConnection);
    smtp.setUser(cfg["emailusername"]);
    smtp.setPassword(cfg["emailpassword"]);

    /* create a MimeMessage object. This will be the email. */
    MimeMessage message;
    message.setSender(new EmailAddress(cfg["emailusername"], "NiDB"));
    message.addRecipient(new EmailAddress(to, ""));
    message.setSubject(subject);

    /* add the body to the email */
    MimeText text;
    text.setText(body);
    message.addPart(&text);

    /* Now we can send the mail */
    if (!smtp.connectToHost()) {
        Print("Failed to connect to host [" + cfg["emailserver"] + "]");
        smtp.quit();
        return false;
    }
    if (!smtp.login()) {
        Print("Failed to login using username [" + cfg["emailusername"] + "] and password [" + cfg["emailpassword"] + "]");
        smtp.quit();
        return false;
    }
    if (!smtp.sendMail(message)) {
        Print("Failed to send [" + body + "]");
        smtp.quit();
        return false;
    }
    else {
        Print("Sent email successfuly");
    }
    smtp.quit();

    return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertSubjectChangeLog ------------------------- */
/* ---------------------------------------------------------- */
void nidb::InsertSubjectChangeLog(QString username, QString uid, QString newuid, QString changetype, QString log) {
    QSqlQuery q;
    q.prepare("insert ignore into changelog_subject (username, change_date, changetype, uid, newuid, log) values (:username, now(), :changetype, :uid, :newuid, :log)");
    q.bindValue(":username", username);
    q.bindValue(":changetype", changetype);
    q.bindValue(":uid", uid);
    q.bindValue(":newuid", newuid);
    q.bindValue(":log", log);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
}


/* ---------------------------------------------------------- */
/* --------- ConvertDicom ----------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::ConvertDicom(QString filetype, QString indir, QString outdir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg) {

    QStringList msgs;

    QString pwd = QDir::currentPath();

    QString gzipstr;
    if (gzip) gzipstr = "-z y";
    else gzipstr = "-z n";

    numfilesconv = 0; /* need to fix this to be correct at some point */

    WriteLog("Working on [" + indir + "] and filetype [" + filetype + "]");

    /* in case of par/rec, the argument list to dcm2niix is a file instead of a directory */
    QString fileext = "";
    if (datatype == "parrec")
        fileext = "/*.par";

    /* do the conversion */
    QString systemstring;
    QDir::setCurrent(indir);
    if (filetype == "nifti4dme")
        systemstring = QString("%1/bin/./dcm2niixme %2 -o '%3' %4").arg(cfg["nidbdir"]).arg(gzipstr).arg(outdir).arg(indir);
    else if (filetype == "nifti4d")
        systemstring = QString("%1/bin/./dcm2niix -1 -b n %2 -o '%3' %4%5").arg(cfg["nidbdir"]).arg(gzipstr).arg(outdir).arg(indir).arg(fileext);
    else if (filetype == "nifti3d")
        systemstring = QString("%1/bin/./dcm2niix -1 -b n -z 3 -o '%2' %3%4").arg(cfg["nidbdir"]).arg(outdir).arg(indir).arg(fileext);
    else if (filetype == "bids")
        systemstring = QString("%1/bin/./dcm2niix -1 -b y -z y -o '%2' %3%4").arg(cfg["nidbdir"]).arg(outdir).arg(indir).arg(fileext);
    else
        return false;

    /* create the output directory */
    QString m;
    if (!MakePath(outdir, m)) {
        msgs << "Unable to create path [" + outdir + "] because of error [" + m + "]";
        return false;
    }

    /* delete any files that may already be in the output directory.. for example, an incomplete series was put in the output directory
     * remove any stuff and start from scratch to ensure proper file numbering */
    if ((outdir != "") && (outdir != "/") ) {
        QString systemstring2 = QString("rm -f %1/*.hdr %1/*.img %1/*.nii %1/*.gz").arg(outdir);
        WriteLog(SystemCommand(systemstring2, true, true));

        /* execute the command created above */
        WriteLog(SystemCommand(systemstring, true, true));
    }
    else {
        return false;
    }

    /* conversion should be done, so check if it actually gzipped the file */
    if ((gzip) && (filetype != "bids")) {
        systemstring = "cd " + outdir + "; gzip *";
        WriteLog(SystemCommand(systemstring, true));
    }

    /* rename the files into something meaningful */
    m = "";
    if (!BatchRenameFiles(outdir, seriesnum, studynum, uid, numfilesrenamed, m))
        msgs << "Error renaming output files [" + m + "]";

    /* change back to original directory before leaving */
    QDir::setCurrent(pwd);

    msg = msgs.join("\n");
    return true;
}


/* ---------------------------------------------------------- */
/* --------- BatchRenameFiles ------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg) {

    QDir d;
    if (!d.exists(dir)) {
        msg = "directory [" + dir + "] does not exist";
        return false;
    }

    numfilesrenamed = 0;
    QStringList exts;
    exts << "*.img" << "*.hdr" << "*.nii" << "*.nii.gz" << "*.json" << "*.bvec" << "*.bval";
    /* loop through all the extensions we want to rename/renumber */
    foreach (QString ext, exts) {
        int i = 1;
        QFile f;
        QDirIterator it(dir, QStringList() << ext, QDir::Files);
        while (it.hasNext()) {
            QString fname = it.next();
            f.setFileName(fname);
            QFileInfo fi(f);
            QString newName = fi.path() + "/" + QString("%1_%2_%3_%4%5").arg(uid).arg(studynum).arg(seriesnum).arg(i,5,10,QChar('0')).arg(ext.replace("*",""));
            WriteLog( fname + " --> " + newName);
            if (f.rename(newName))
                numfilesrenamed++;
            else
                WriteLog("Error renaming file [" + fname + "] to [" + newName + "]");
            i++;
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetPrimaryAlternateUID ------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GetPrimaryAlternateUID(int subjectid, int enrollmentid) {

    if ((subjectid < 1) || (enrollmentid < 1))
        return "";

    QSqlQuery q;
    q.prepare("select * from subject_altuid where subject_id = :subjectid and enrollment_id = :enrollmentid order by isprimary limit 1");
    q.bindValue(":subjectid", subjectid);
    q.bindValue(":enrollmentid", enrollmentid);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        QString altuid = q.value("altuid").toString();
        bool isprimary = q.value("isprimary").toBool();
        if (isprimary) {
            WriteLog("Found primary alternate ID [" + altuid + "]");
            return altuid;
        }
    }

    return "";
}


/* ---------------------------------------------------------- */
/* --------- GetFileChecksum -------------------------------- */
/* ---------------------------------------------------------- */
QByteArray nidb::GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm) {
    QFile f(fileName);
    if (f.open(QFile::ReadOnly)) {
        QCryptographicHash hash(hashAlgorithm);
        if (hash.addData(&f)) {
            return hash.result();
        }
    }
    return QByteArray();
}


/* ---------------------------------------------------------- */
/* --------- CreateUID -------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::CreateUID(QString prefix, int numletters) {

    QString newID;
    QString letters("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
    QString numbers("0123456789");
    QString C1, C2, C3, C4, C5, C6, C7, C8;

    C1 = numbers.at( QRandomGenerator::global()->bounded(numbers.length()-1) );
    C2 = numbers.at( QRandomGenerator::global()->bounded(numbers.length()-1) );
    C3 = numbers.at( QRandomGenerator::global()->bounded(numbers.length()-1) );
    C4 = numbers.at( QRandomGenerator::global()->bounded(numbers.length()-1) );

    QStringList badarray;
    badarray << "fuck" << "shit" << "piss" << "tits" << "dick" << "cunt" << "twat" << "jism" << "jizz" << "arse" << "damn" << "fart" << "hell" << "wang" << "wank" << "gook" << "kike" << "kyke" << "spic" << "arse" << "dyke" << "cock" << "muff" << "pusy" << "butt" << "crap" << "poop" << "slut" << "dumb" << "snot" << "boob" << "dead" << "anus" << "clit" << "homo" << "poon" << "tard" << "kunt" << "tity" << "tit" << "ass" << "dic" << "dik" << "fuk" << "kkk";
    bool done = false;

    do {
        C5 = letters.at( QRandomGenerator::global()->bounded(letters.length()-1) );
        C6 = letters.at( QRandomGenerator::global()->bounded(letters.length()-1) );
        C7 = letters.at( QRandomGenerator::global()->bounded(letters.length()-1) );

        if (numletters == 4)
            C8 = letters.at( QRandomGenerator::global()->bounded(letters.length()-1) );

        QString str;
        str = QString("%1%2%3%4").arg(C5).arg(C6).arg(C7).arg(C8);
        if (!badarray.contains(str,Qt::CaseInsensitive))
            done = true;
    }
    while (!done);

    newID = prefix+C1+C2+C3+C4+C5+C6+C7+C8;
    return newID.trimmed();
}


/* ---------------------------------------------------------- */
/* --------- RemoveNonAlphaNumericChars --------------------- */
/* ---------------------------------------------------------- */
QString nidb::RemoveNonAlphaNumericChars(QString s) {
    return s.remove(QRegExp("[^a-zA-Z\\d\\s]"));
}


/* ---------------------------------------------------------- */
/* --------- SortQStringListNaturally ----------------------- */
/* ---------------------------------------------------------- */
void nidb::SortQStringListNaturally(QStringList &s) {

    if (s.size() < 2)
        return;

    QCollator coll;
    coll.setNumericMode(true);
    std::sort(s.begin(), s.end(), [&](const QString& s1, const QString& s2){ return coll.compare(s1, s2) < 0; });
}


/* ---------------------------------------------------------- */
/* --------- IsDICOMFile ------------------------------------ */
/* ---------------------------------------------------------- */
bool nidb::IsDICOMFile(QString f) {
    /* check if its really a dicom file... */
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (r.Read())
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDICOMFile ----------------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
bool nidb::AnonymizeDicomFile(gdcm::Anonymizer &anon, QString infile, QString outfile, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags)
{
    //WriteLog(QString("AnonymizeDICOMFile(infile [%1]   outfile [%2])").arg(infile).arg(outfile));

    gdcm::Reader reader;
    reader.SetFileName( infile.toStdString().c_str() );
    if( !reader.Read() ) {
        WriteLog(QString("Could not read [%1]").arg(infile));
        //if( continuemode ) {
        //	WriteLog("Skipping from anonymization process (continue mode).");
        //	return true;
        //}
        //else
        //{
        //	WriteLog("Check [--continue] option for skipping files.");
        //	return false;
        //}
    }
    gdcm::File &file = reader.GetFile();

    anon.SetFile( file );

    if( empty_tags.empty() && replace_tags.empty() && remove_tags.empty() ) {
        WriteLog("AnonymizeDICOMFile() empty tags. No operation to be done.");
        return false;
    }

    std::vector<gdcm::Tag>::const_iterator it = empty_tags.begin();
    bool success = true;
    for(; it != empty_tags.end(); ++it) {
        success = success && anon.Empty( *it );
    }
    it = remove_tags.begin();
    for(; it != remove_tags.end(); ++it) {
        success = success && anon.Remove( *it );
    }

    std::vector< std::pair<gdcm::Tag, std::string> >::const_iterator it2 = replace_tags.begin();
    for(; it2 != replace_tags.end(); ++it2) {
        success = success && anon.Replace( it2->first, it2->second.c_str() );
    }

    gdcm::Writer writer;
    writer.SetFileName( outfile.toStdString().c_str() );
    writer.SetFile( file );
    if( !writer.Write() ) {
        WriteLog(QString("Could not write [%1]").arg(outfile));
        if ((infile != infile) && (infile != "")) {
            gdcm::System::RemoveFile( infile.toStdString().c_str() );
        }
        else
        {
            WriteLog(QString("gdcmanon just corrupted [%1] for you (data lost).").arg(infile));
        }
        return false;
    }
    return success;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDir ----------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::AnonymizeDir(QString dir,int anonlevel, QString randstr1, QString randstr2) {

    std::vector<gdcm::Tag> empty_tags;
    std::vector<gdcm::Tag> remove_tags;
    std::vector< std::pair<gdcm::Tag, std::string> > replace_tags;

    gdcm::Tag tag;

    switch (anonlevel) {
        case 0:
            WriteLog("No anonymization requested. Leaving files unchanged.");
            return 0;
        case 1:
        case 3:
            /* remove referring physician name */
            tag.ReadFromCommaSeparatedString("0008, 0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
            tag.ReadFromCommaSeparatedString("0008, 1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
            tag.ReadFromCommaSeparatedString("0008, 1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
            tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr1).toStdString().c_str()) );
            tag.ReadFromCommaSeparatedString("0010, 0030"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr2).toStdString().c_str()) );
            break;
        case 2:
            /* Full anonymization. remove all names, dates, locations. ANYTHING identifiable */
            tag.ReadFromCommaSeparatedString("0008,0012"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // InstanceCreationDate
            tag.ReadFromCommaSeparatedString("0008,0013"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // InstanceCreationTime
            tag.ReadFromCommaSeparatedString("0008,0020"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // StudyDate
            tag.ReadFromCommaSeparatedString("0008,0021"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // SeriesDate
            tag.ReadFromCommaSeparatedString("0008,0022"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // AcquisitionDate
            tag.ReadFromCommaSeparatedString("0008,0023"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // ContentDate
            tag.ReadFromCommaSeparatedString("0008,0030"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //StudyTime
            tag.ReadFromCommaSeparatedString("0008,0031"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //SeriesTime
            tag.ReadFromCommaSeparatedString("0008,0032"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //AcquisitionTime
            tag.ReadFromCommaSeparatedString("0008,0033"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //ContentTime
            tag.ReadFromCommaSeparatedString("0008,0080"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // InstitutionName
            tag.ReadFromCommaSeparatedString("0008,0081"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // InstitutionAddress
            tag.ReadFromCommaSeparatedString("0008,0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianName
            tag.ReadFromCommaSeparatedString("0008,0092"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianAddress
            tag.ReadFromCommaSeparatedString("0008,0094"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianTelephoneNumber
            tag.ReadFromCommaSeparatedString("0008,0096"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianIDSequence
            tag.ReadFromCommaSeparatedString("0008,1010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // StationName
            tag.ReadFromCommaSeparatedString("0008,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // StudyDescription
            tag.ReadFromCommaSeparatedString("0008,103E"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // SeriesDescription
            tag.ReadFromCommaSeparatedString("0008,1048"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PhysiciansOfRecord
            tag.ReadFromCommaSeparatedString("0008,1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformingPhysicianName
            tag.ReadFromCommaSeparatedString("0008,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // NameOfPhysicianReadingStudy
            tag.ReadFromCommaSeparatedString("0008,1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OperatorsName

            tag.ReadFromCommaSeparatedString("0010,0010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientName
            tag.ReadFromCommaSeparatedString("0010,0020"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientID
            tag.ReadFromCommaSeparatedString("0010,0021"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // IssuerOfPatientID
            tag.ReadFromCommaSeparatedString("0010,0030"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // PatientBirthDate
            tag.ReadFromCommaSeparatedString("0010,0032"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); // PatientBirthTime
            tag.ReadFromCommaSeparatedString("0010,0050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientInsurancePlanCodeSequence
            tag.ReadFromCommaSeparatedString("0010,1000"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OtherPatientIDs
            tag.ReadFromCommaSeparatedString("0010,1001"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OtherPatientNames
            tag.ReadFromCommaSeparatedString("0010,1005"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientBirthName
            tag.ReadFromCommaSeparatedString("0010,1010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientAge
            tag.ReadFromCommaSeparatedString("0010,1020"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientSize
            tag.ReadFromCommaSeparatedString("0010,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientWeight
            tag.ReadFromCommaSeparatedString("0010,1040"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientAddress
            tag.ReadFromCommaSeparatedString("0010,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientMotherBirthName
            tag.ReadFromCommaSeparatedString("0010,2154"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientTelephoneNumbers
            tag.ReadFromCommaSeparatedString("0010,21B0"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // AdditionalPatientHistory
            tag.ReadFromCommaSeparatedString("0010,21F0"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientReligiousPreference
            tag.ReadFromCommaSeparatedString("0010,4000"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientComments

            tag.ReadFromCommaSeparatedString("0018,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ProtocolName

            tag.ReadFromCommaSeparatedString("0032,1032"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // RequestingPhysician
            tag.ReadFromCommaSeparatedString("0032,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // RequestedProcedureDescription

            tag.ReadFromCommaSeparatedString("0040,0006"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ScheduledPerformingPhysiciansName
            tag.ReadFromCommaSeparatedString("0040,0244"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // PerformedProcedureStepStartDate
            tag.ReadFromCommaSeparatedString("0040,0245"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); // PerformedProcedureStepStartTime
            tag.ReadFromCommaSeparatedString("0040,0253"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformedProcedureStepID
            tag.ReadFromCommaSeparatedString("0040,0254"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformedProcedureStepDescription
            tag.ReadFromCommaSeparatedString("0040,4036"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // HumanPerformerOrganization
            tag.ReadFromCommaSeparatedString("0040,4037"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // HumanPerformerName
            tag.ReadFromCommaSeparatedString("0040,A123"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PersonName

            break;
        case 4:
            tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr1).toStdString().c_str()) );
            break;
    }

    /* recursively loop through the directory and anonymize the .dcm files */
    gdcm::Anonymizer anon;
    QDirIterator it(dir, QStringList() << "*.dcm", QDir::Files, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        QString dcmfile = it.next();
        AnonymizeDicomFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags);
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ParseDate -------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::ParseDate(QString s) {
    QString d = "0000-01-01";
    QDate date;

    s.replace(":","-").replace(".","-").replace("/","-").replace("|","-").replace(",","-").replace("\\","-");

    date = QDate::fromString(s, "yyyy-MM-dd");
    if (date.isValid()) return date.toString("yyyy-MM-dd");

    date = QDate::fromString(s, "yyyy-M-d");
    if (date.isValid()) return date.toString("yyyy-MM-dd");

    date = QDate::fromString(s, "dd-MM-yy");
    if (date.isValid()) return date.toString("yyyy-MM-dd");

    date = QDate::fromString(s, "MM-yyyy");
    if (date.isValid()) return date.toString("yyyy-MM-dd");

    return d;
}


/* ---------------------------------------------------------- */
/* --------- ParseTime -------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::ParseTime(QString s) {
    QString t = "00:00:00";
    QTime time;

    s.replace("-",":").replace("/",":").replace("|",":").replace(",",":").replace("\\",":");

    time = QTime::fromString(s, "hh:mm:ss");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "h:m:s");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:m:s");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:mm:s");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "h:mm:ss");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "h:m:ss");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:m:ss");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "h:mm:s");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:mm");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:m");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "h:mm");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "h:m");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:mm:ss.zzz");
    if (time.isValid()) return time.toString("hh:mm:ss");

    time = QTime::fromString(s, "hh:mm:ss.z");
    if (time.isValid()) return time.toString("hh:mm:ss");

    return t;
}


/* ---------------------------------------------------------- */
/* --------- ValidNiDBModality ------------------------------ */
/* ---------------------------------------------------------- */
bool nidb::isValidNiDBModality(QString m) {
    QSqlQuery q;
    QString sqlstring = QString("show tables like '%1_series'").arg(m.toLower());
    q.prepare(sqlstring);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0)
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- chmod ------------------------------------------ */
/* ---------------------------------------------------------- */
bool nidb::chmod(QString f, QString perm) {
    if (perm.size() != 3)
        return false;

    int owner = QString(perm[0]).toInt();
    int group = QString(perm[1]).toInt();
    int everyone = QString(perm[2]).toInt();

    switch (owner) {
        case 1: if (!QFile::setPermissions(f, QFileDevice::ExeOwner)) return false; break;
        case 2: if (!QFile::setPermissions(f, QFileDevice::WriteOwner)) return false; break;
        case 3: if (!QFile::setPermissions(f, QFileDevice::ExeOwner | QFileDevice::WriteOwner)) return false; break;
        case 4: if (!QFile::setPermissions(f, QFileDevice::ReadOwner)) return false; break;
        case 5: if (!QFile::setPermissions(f, QFileDevice::ExeOwner | QFileDevice::ReadOwner)) return false; break;
        case 6: if (!QFile::setPermissions(f, QFileDevice::ReadOwner | QFileDevice::WriteOwner)) return false; break;
        case 7: if (!QFile::setPermissions(f, QFileDevice::ExeOwner | QFileDevice::WriteOwner | QFileDevice::ReadOwner)) return false; break;
    }

    switch (group) {
        case 1: if (!QFile::setPermissions(f, QFileDevice::ExeGroup)) return false; break;
        case 2: if (!QFile::setPermissions(f, QFileDevice::WriteGroup)) return false; break;
        case 3: if (!QFile::setPermissions(f, QFileDevice::ExeGroup | QFileDevice::WriteGroup)) return false; break;
        case 4: if (!QFile::setPermissions(f, QFileDevice::ReadGroup)) return false; break;
        case 5: if (!QFile::setPermissions(f, QFileDevice::ExeGroup | QFileDevice::ReadGroup)) return false; break;
        case 6: if (!QFile::setPermissions(f, QFileDevice::ReadGroup | QFileDevice::WriteGroup)) return false; break;
        case 7: if (!QFile::setPermissions(f, QFileDevice::ExeGroup | QFileDevice::WriteGroup | QFileDevice::ReadGroup)) return false; break;
    }

    switch (everyone) {
        case 1: if (!QFile::setPermissions(f, QFileDevice::ExeOther)) return false; break;
        case 2: if (!QFile::setPermissions(f, QFileDevice::WriteOther)) return false; break;
        case 3: if (!QFile::setPermissions(f, QFileDevice::ExeOther | QFileDevice::WriteOther)) return false; break;
        case 4: if (!QFile::setPermissions(f, QFileDevice::ReadOther)) return false; break;
        case 5: if (!QFile::setPermissions(f, QFileDevice::ExeOther | QFileDevice::ReadOther)) return false; break;
        case 6: if (!QFile::setPermissions(f, QFileDevice::ReadOther | QFileDevice::WriteOther)) return false; break;
        case 7: if (!QFile::setPermissions(f, QFileDevice::ExeOther | QFileDevice::WriteOther | QFileDevice::ReadOther)) return false; break;
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- JoinIntArray ----------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::JoinIntArray(QList<int> a, QString glue) {
    if (a.size() == 0)
        return "";
    else if (a.size() == 1)
        return QString("%1").arg(a[0]);
    else {
        QStringList sa;
        for (int i=0; i<a.size();i++)
            sa << QString("%1").arg(a[i]);
        return sa.join(glue);
    }
}


/* ---------------------------------------------------------- */
/* --------- SplitStringArrayToInt -------------------------- */
/* ---------------------------------------------------------- */
QList<int> nidb::SplitStringArrayToInt(QStringList a) {
    QList<int> i;

    if (a.size() > 0) {
        foreach (QString v, a) {
            i.append(v.trimmed().toInt());
        }
    }

    return i;
}


/* ---------------------------------------------------------- */
/* --------- SplitStringArrayToDouble ----------------------- */
/* ---------------------------------------------------------- */
QList<double> nidb::SplitStringArrayToDouble(QStringList a) {
    QList<double> i;

    if (a.size() > 0) {
        foreach (QString v, a) {
            i.append(v.trimmed().toDouble());
        }
    }

    return i;
}


/* ---------------------------------------------------------- */
/* --------- SplitStringToIntArray -------------------------- */
/* ---------------------------------------------------------- */
QList<int> nidb::SplitStringToIntArray(QString a) {
    QList<int> i;

    if (a.size() > 0) {
        QStringList sl = a.split(',');
        i = SplitStringArrayToInt(sl);
    }

    return i;
}


/* ---------------------------------------------------------- */
/* --------- AppendCustomLog -------------------------------- */
/* ---------------------------------------------------------- */
void nidb::AppendCustomLog(QString file, QString msg) {
    QFile f(file);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Append)) {
        QTextStream fs(&f);
        fs << QString("[%1][%2] %3\n").arg(CreateCurrentDateTime()).arg(pid).arg(msg);
        f.close();
    }
    else {
        WriteLog("Error writing to file ["+file+"]");
    }
}


/* ---------------------------------------------------------- */
/* --------- SubmitClusterJob ------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::SubmitClusterJob(QString f, QString submithost, QString qsub, QString user, QString queue, QString &msg, int &jobid, QString &result) {

    /* submit the job to the cluster */
    QString systemstring = QString("ssh %1 %2 -u %3 -q %4 \"%5\"").arg(submithost).arg(qsub).arg(user).arg(queue).arg(f);
    result = SystemCommand(systemstring,true).trimmed();

    /* get the jobid */
    jobid = -1;
    QStringList parts = result.split(" ");
    if (parts.size() >= 3)
        jobid = parts[2].toInt();

    /* check the return message from qsub */
    if (result.contains("invalid option", Qt::CaseInsensitive)) {
        msg = "Invalid qsub option";
        return false;
    }
    else if (result.contains("permission denied, please try again", Qt::CaseInsensitive)) {
        msg = "SSH passwordless submission failure";
        return false;
    }
    else if (result.contains("directive error", Qt::CaseInsensitive)) {
        msg = "Invalid qsub directive";
        return false;
    }
    else if (result.contains("cannot connect to server", Qt::CaseInsensitive)) {
        msg = "Invalid qsub hostname (" + submithost + ")";
        return false;
    }
    else if (result.contains("unknown queue", Qt::CaseInsensitive)) {
        msg = "Invalid queue (" + queue + ")";
        return false;
    }
    else if (result.contains("queue is not enabled", Qt::CaseInsensitive)) {
        msg = "Queue (" + queue + ") is not enabled";
        return false;
    }
    else if (result.contains("job exceeds queue resource limits", Qt::CaseInsensitive)) {
        msg = "Job exceeds resource limits";
        return false;
    }
    else if (result.contains("unable to read script file", Qt::CaseInsensitive)) {
        msg = "Error reading job submission file";
        return false;
    }

    msg = "Cluster job submitted successfully";

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetSQLComparison ------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::GetSQLComparison(QString c, QString &comp, int &num) {

    /* remove whitespace */
    c.remove(QRegularExpression("\\s*"));

    /* check if there is anything to format */
    if (c == "")
        return false;

    comp = "";
    num = 0;
    bool ok;
    if (c.left(2) == "<=") {
        comp = "<=";
        num = c.mid(2).toInt(&ok);
        if (!ok) return false;
    }
    else if (c.left(2) == ">=") {
        comp = ">=";
        num = c.mid(2).toInt(&ok);
        if (!ok) return false;
    }
    else if (c.left(1) == "<") {
        comp = "<";
        num = c.mid(1).toInt(&ok);
        if (!ok) return false;
    }
    else if (c.left(1) == ">") {
        comp = ">";
        num = c.mid(1).toInt(&ok);
        if (!ok) return false;
    }
    else if (c.left(1) == "~") {
        comp = "<>";
        num = c.mid(1).toInt(&ok);
        if (!ok) return false;
    }
    else if (c.left(1) == "=") { /* someone will inevitably put unspecified format in there */
        comp = "=";
        num = c.mid(1).toInt(&ok);
        if (!ok) return false;
    }
    else {
        num = c.toInt(&ok);
        if (ok)
            comp = "=";
        else
            return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- ShellWords ------------------------------------- */
/* ---------------------------------------------------------- */
QStringList nidb::ShellWords(QString s) {

    QStringList words;
    QRegularExpression regex("\".*?\"", QRegularExpression::CaseInsensitiveOption);
    if (s.contains(regex)) {
        QRegularExpressionMatchIterator iterator = regex.globalMatch(s);
        while (iterator.hasNext()) {
            QRegularExpressionMatch match = iterator.next();
            QString matched = match.captured(0);
            matched.remove("\"");

            if (matched.length() > 0)
                words << matched;
        }
    }
    return words;
}


/* ---------------------------------------------------------- */
/* --------- IsInt ------------------------------------------ */
/* ---------------------------------------------------------- */
bool nidb::IsInt(QString s) {
    bool is = false;

    s.toInt(&is);

    if (is)
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- IsDouble --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::IsDouble(QString s) {
    bool is = false;

    s.toDouble(&is);

    if (is)
        return true;
    else
        return false;
}

/* ---------------------------------------------------------- */
/* --------- IsNumber --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::IsNumber(QString s) {
    if (IsInt(s) || IsDouble(s))
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- IsRunningFromCluster --------------------------- */
/* ---------------------------------------------------------- */
bool nidb::IsRunningFromCluster() {
    return runningFromCluster;
}


/* ---------------------------------------------------------- */
/* --------- GetGroupListing -------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GetGroupListing(int groupid) {
    QString s;

    QSqlQuery q;
    QString groupType;
    QString groupName;
    q.prepare("select * from groups where group_id = :groupid");
    q.bindValue(":groupid", groupid);
    SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        groupType = q.value("group_type").toString();
        groupName = q.value("group_name").toString();
    }

    s = "Group [" + groupName + "]  type [" + groupType + "] contains [";

    if (groupType == "subject") {
        q.prepare("select b.uid, b.subject_id from group_data a left join subjects b on a.data_id = b.subject_id where a.group_id = :groupid");
        q.bindValue(":groupid", groupid);
        SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            while (q.next()) {
                int subjectid = q.value("subject_id").toInt();
                QString uid = q.value("uid").toString();
                s += QString("%1, ").arg(uid).arg(subjectid);
            }
        }
    }

    if (groupType == "study") {
        q.prepare("select b.study_id, b.study_num, d.uid, d.subject_id from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = :groupid group by d.uid order by d.uid,b.study_num");
        q.bindValue(":groupid", groupid);
        SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            while (q.next()) {
                int studynum = q.value("study_num").toInt();
                QString uid = q.value("uid").toString();
                s += QString("%1%2, ").arg(uid).arg(studynum);
            }
        }
    }

    return s;
}


/* ---------------------------------------------------------- */
/* --------- WrapText --------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::WrapText(QString s, int col) {
    for (int i = col; i <= s.size(); i+=col+1)
        s.insert(i, "\n");

    return s;
}


/* ---------------------------------------------------------- */
/* --------- ParseCSV --------------------------------------- */
/* ---------------------------------------------------------- */
/* this function handles most Excel compatible .csv formats
 * but it does not handle nested quotes, and must have a header
 * row */
bool nidb::ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg) {

    QStringList m;
    bool ret(true);

    /* get header row */
    QStringList lines = csv.trimmed().split(QRegularExpression("[\\n\\r]"));

    if (lines.size() > 1) {
        QString header = lines.takeFirst();
        QStringList cols = header.trimmed().toLower().split(QRegularExpression("\\s*,\\s*"));
        columns = cols;

        m << QString("Found [%1] columns [%2]").arg(cols.size()).arg(cols.join(","));
        /* remove the last column if it was blank, because the file contained an extra trailing comma */
        if (cols.last() == "") {
            cols.removeLast();
            m << QString("Last column was blank, removing").arg(cols.size());
        }

        int numcols = cols.size();

        int row = 0;
        foreach (QString line, lines) {
            QString buffer = "";
            int col = 0;
            bool inQuotes = false;
            for (int i=0; i<line.size(); i++) {
                QChar c = line.at(i);

                /* determine if we're in quotes or not */
                if (c == """") {
                    if (inQuotes)
                        inQuotes = false;
                    else
                        inQuotes = true;
                }

                /* check if we've hit the next comma, and therefor should end the previous variable */
                if ((c == ",") && (!inQuotes)) {
                    table[row][cols[col]] = buffer.trimmed();

                    buffer = "";
                    col++;
                }
                else {
                    buffer = QString("%1%2").arg(buffer).arg(c); /* make sure no null terminators end up in the string */
                }
            }
            /* acquire the last column */
            table[row][cols[col]] = buffer.trimmed();
            buffer = "";

            if ((col+1) != numcols) {
                m << QString("Error: row [%1] has [%2] columns, but expecting [%3] columns").arg(row+1).arg(col+1).arg(numcols);
                ret = false;
            }

            row++;
        }
        m << QString("Processed [%1] data rows").arg(row);
    }
    else {
        ret = false;
        m << ".csv file contained only one row. The csv must contain at least one header row and one data row";
    }

    msg = m.join("  \n");

    return ret;
}


/* ------------------------------------------------- */
/* --------- GetFileType --------------------------- */
/* ------------------------------------------------- */
void nidb::GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol)
{
    WriteLog("In GetFileType(" + f + ")");
    fileModality = QString("");
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (r.CanRead()) {
        r.Read();
        fileType = QString("DICOM");
        gdcm::StringFilter sf;
        sf = gdcm::StringFilter();
        sf.SetFile(r.GetFile());
        std::string s;

        /* get modality */
        s = sf.ToString(gdcm::Tag(0x0008,0x0060));
        fileModality = QString(s.c_str());

        /* get patientID */
        s = sf.ToString(gdcm::Tag(0x0010,0x0020));
        filePatientID = QString(s.c_str());

        /* get protocol (seriesDesc) */
        s = sf.ToString(gdcm::Tag(0x0008,0x103E));
        fileProtocol = QString(s.c_str());
    }
    else {
        WriteLog("[" + f + "] is not a DICOM file");
        /* check if EEG, and Polhemus */
        if ((f.toLower().endsWith(".cnt")) || (f.toLower().endsWith(".dat")) || (f.toLower().endsWith(".3dd")) || (f.toLower().endsWith(".eeg"))) {
            WriteLog("Found an EEG file [" + f + "]");
            fileType = "EEG";
            fileModality = "EEG";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
        }
        /* check if ET */
        else if (f.toLower().endsWith(".edf")) {
            WriteLog("Found an ET file [" + f + "]");
            fileType = "ET";
            fileModality = "ET";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
        }
        /* check if MR (Non-DICOM) analyze or nifti */
        else if ((f.toLower().endsWith(".nii")) || (f.toLower().endsWith(".nii.gz")) || (f.toLower().endsWith(".hdr")) || (f.toLower().endsWith(".img"))) {
            WriteLog("Found an analyze or Nifti image [" + f + "]");
            fileType = "NIFTI";
            fileModality = "NIFTI";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            filePatientID = parts[0];
        }
        /* check if par/rec */
        else if (f.endsWith(".par")) {
            WriteLog("Found a PARREC image [" + f + "]");
            fileType = "PARREC";
            fileModality = "PARREC";

            QFile inputFile(f);
            if (inputFile.open(QIODevice::ReadOnly))
            {
               QTextStream in(&inputFile);
               while ( !in.atEnd() )
               {
                  QString line = in.readLine();
                  if (line.contains("Patient name")) {
                      QStringList parts = line.split(":",Qt::SkipEmptyParts);
                      filePatientID = parts[1].trimmed();
                  }
                  if (line.contains("Protocol name")) {
                      QStringList parts = line.split(":",Qt::SkipEmptyParts);
                      fileProtocol = parts[1].trimmed();
                  }
                  if (line.toUpper().contains("MRSERIES")) {
                      fileModality = "MR";
                  }
               }
               inputFile.close();
            }
        }
        else {
            WriteLog("Filetype is unknown [" + f + "]");
            fileType = "Unknown";
        }
    }
}


/* ------------------------------------------------- */
/* --------- GetDicomModality ---------------------- */
/* ------------------------------------------------- */
QString nidb::GetDicomModality(QString f)
{
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (!r.CanRead()) {
        return "NOTDICOM";
    }
    gdcm::StringFilter sf;
    sf = gdcm::StringFilter();
    sf.SetFile(r.GetFile());
    std::string s = sf.ToString(gdcm::Tag(0x0008,0x0060));

    QString qs = s.c_str();

    return qs;
}


/* ---------------------------------------------------------- */
/* --------- GetImageFileTags ------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::GetImageFileTags(QString f, QHash<QString, QString> &tags) {

    /* check if the file exists and has read permissions */
    QFileInfo fi(f);
    if (!fi.exists()) {
        tags["FileExists"] = "false";
        WriteLog(QString("File [%1] does not exist").arg(f));
        return false;
    }
    tags["FileExists"] = "true";
    if (!fi.isReadable()) {
        WriteLog(QString("File [%1] does not have read permissions").arg(f));
        tags["FileHasReadPermissions"] = "false";
        return false;
    }

    tags["Filename"] = f;
    tags["Modality"] = "Unknown";
    tags["FileType"] = "Unknown";

    /* check if the file is readable by GDCM, and therefore a DICOM file */
    gdcm::Reader r;
    r.SetFileName(f.toStdString().c_str());
    if (r.Read()) {
        /* ---------- it's a readable DICOM file ---------- */
        gdcm::StringFilter sf;
        sf = gdcm::StringFilter();
        sf.SetFile(r.GetFile());

        tags["FileType"] = "DICOM";

        /* get all of the DICOM tags...
         * we're not using an iterator because we want to know exactly what tags we have and dont have */

        tags["FileMetaInformationGroupLength"] =	QString(sf.ToString(gdcm::Tag(0x0002,0x0000)).c_str()).trimmed(); /* FileMetaInformationGroupLength */
        tags["FileMetaInformationVersion"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0001)).c_str()).trimmed(); /* FileMetaInformationVersion */
        tags["MediaStorageSOPClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0002)).c_str()).trimmed(); /* MediaStorageSOPClassUID */
        tags["MediaStorageSOPInstanceUID"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0003)).c_str()).trimmed(); /* MediaStorageSOPInstanceUID */
        tags["TransferSyntaxUID"] =					QString(sf.ToString(gdcm::Tag(0x0002,0x0010)).c_str()).trimmed(); /* TransferSyntaxUID */
        tags["ImplementationClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0012)).c_str()).trimmed(); /* ImplementationClassUID */
        tags["ImplementationVersionName"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0013)).c_str()).trimmed(); /* ImplementationVersionName */

        tags["SpecificCharacterSet"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0005)).c_str()).trimmed(); /* SpecificCharacterSet */
        tags["ImageType"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0008)).c_str()).trimmed(); /* ImageType */
        tags["InstanceCreationDate"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0012)).c_str()).trimmed(); /* InstanceCreationDate */
        tags["InstanceCreationTime"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0013)).c_str()).trimmed(); /* InstanceCreationTime */
        tags["SOPClassUID"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0016)).c_str()).trimmed(); /* SOPClassUID */
        tags["SOPInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0018)).c_str()).trimmed(); /* SOPInstanceUID */
        tags["StudyDate"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0020)).c_str()).trimmed(); /* StudyDate */
        tags["SeriesDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0021)).c_str()).trimmed(); /* SeriesDate */
        tags["AcquisitionDate"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0022)).c_str()).trimmed(); /* AcquisitionDate */
        tags["ContentDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0023)).c_str()).trimmed(); /* ContentDate */
        tags["StudyTime"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0030)).c_str()).trimmed(); /* StudyTime */
        tags["SeriesTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0031)).c_str()).trimmed(); /* SeriesTime */
        tags["AcquisitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0032)).c_str()).trimmed(); /* AcquisitionTime */
        tags["ContentTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0033)).c_str()).trimmed(); /* ContentTime */
        tags["AccessionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0050)).c_str()).trimmed(); /* AccessionNumber */
        tags["Modality"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0060)).c_str()).trimmed(); /* Modality */
        tags["Manufacturer"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0070)).c_str()).trimmed(); /* Manufacturer */
        tags["InstitutionName"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0080)).c_str()).trimmed(); /* InstitutionName */
        tags["InstitutionAddress"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0081)).c_str()).trimmed(); /* InstitutionAddress */
        tags["ReferringPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x0090)).c_str()).trimmed(); /* ReferringPhysicianName */
        tags["StationName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1010)).c_str()).trimmed(); /* StationName */
        tags["StudyDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x1030)).c_str()).trimmed(); /* StudyDescription */
        tags["SeriesDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x103E)).c_str()).trimmed(); /* SeriesDescription */
        tags["InstitutionalDepartmentName"] =		QString(sf.ToString(gdcm::Tag(0x0008,0x1040)).c_str()).trimmed(); /* InstitutionalDepartmentName */
        tags["PerformingPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x1050)).c_str()).trimmed(); /* PerformingPhysicianName */
        tags["OperatorsName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1070)).c_str()).trimmed(); /* OperatorsName */
        tags["ManufacturerModelName"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x1090)).c_str()).trimmed(); /* ManufacturerModelName */
        tags["SourceImageSequence"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x2112)).c_str()).trimmed(); /* SourceImageSequence */

        tags["PatientName"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0010)).c_str()).trimmed(); /* PatientName */
        tags["PatientID"] =							QString(sf.ToString(gdcm::Tag(0x0010,0x0020)).c_str()).trimmed(); /* PatientID */
        tags["PatientBirthDate"] =					QString(sf.ToString(gdcm::Tag(0x0010,0x0030)).c_str()).trimmed(); /* PatientBirthDate */
        tags["PatientSex"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0040)).c_str()).trimmed().left(1); /* PatientSex */
        tags["PatientAge"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1010)).c_str()).trimmed(); /* PatientAge */
        tags["PatientSize"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1020)).c_str()).trimmed(); /* PatientSize */
        tags["PatientWeight"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1030)).c_str()).trimmed(); /* PatientWeight */

        tags["ContrastBolusAgent"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0010)).c_str()).trimmed(); /* ContrastBolusAgent */
        tags["KVP"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x0060)).c_str()).trimmed(); /* KVP */
        tags["DataCollectionDiameter"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0090)).c_str()).trimmed(); /* DataCollectionDiameter */
        tags["ContrastBolusRoute"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1040)).c_str()).trimmed(); /* ContrastBolusRoute */
        tags["RotationDirection"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1140)).c_str()).trimmed(); /* RotationDirection */
        tags["ExposureTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1150)).c_str()).trimmed(); /* ExposureTime */
        tags["XRayTubeCurrent"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1151)).c_str()).trimmed(); /* XRayTubeCurrent */
        tags["FilterType"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1160)).c_str()).trimmed(); /* FilterType */
        tags["GeneratorPower"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1170)).c_str()).trimmed(); /* GeneratorPower */
        tags["ConvolutionKernel"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1210)).c_str()).trimmed(); /* ConvolutionKernel */

        tags["BodyPartExamined"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0015)).c_str()).trimmed(); /* BodyPartExamined */
        tags["ScanningSequence"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0020)).c_str()).trimmed(); /* ScanningSequence */
        tags["SequenceVariant"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0021)).c_str()).trimmed(); /* SequenceVariant */
        tags["ScanOptions"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0022)).c_str()).trimmed(); /* ScanOptions */
        tags["MRAcquisitionType"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0023)).c_str()).trimmed(); /* MRAcquisitionType */
        tags["SequenceName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0024)).c_str()).trimmed(); /* SequenceName */
        tags["AngioFlag"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0025)).c_str()).trimmed(); /* AngioFlag */
        tags["SliceThickness"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0050)).c_str()).trimmed(); /* SliceThickness */
        tags["RepetitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0080)).c_str()).trimmed(); /* RepetitionTime */
        tags["EchoTime"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0081)).c_str()).trimmed(); /* EchoTime */
        tags["InversionTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0082)).c_str()).trimmed(); /* InversionTime */
        tags["NumberOfAverages"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0083)).c_str()).trimmed(); /* NumberOfAverages */
        tags["ImagingFrequency"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0084)).c_str()).trimmed(); /* ImagingFrequency */
        tags["ImagedNucleus"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0085)).c_str()).trimmed(); /* ImagedNucleus */
        tags["EchoNumbers"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0086)).c_str()).trimmed(); /* EchoNumbers */
        tags["MagneticFieldStrength"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0087)).c_str()).trimmed(); /* MagneticFieldStrength */
        tags["SpacingBetweenSlices"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0088)).c_str()).trimmed(); /* SpacingBetweenSlices */
        tags["NumberOfPhaseEncodingSteps"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x0089)).c_str()).trimmed(); /* NumberOfPhaseEncodingSteps */
        tags["EchoTrainLength"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0091)).c_str()).trimmed(); /* EchoTrainLength */
        tags["PercentSampling"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0093)).c_str()).trimmed(); /* PercentSampling */
        tags["PercentPhaseFieldOfView"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0094)).c_str()).trimmed(); /* PercentPhaseFieldOfView */
        tags["PixelBandwidth"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0095)).c_str()).trimmed(); /* PixelBandwidth */
        tags["DeviceSerialNumber"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1000)).c_str()).trimmed(); /* DeviceSerialNumber */
        tags["SoftwareVersions"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1020)).c_str()).trimmed(); /* SoftwareVersions */
        tags["ProtocolName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1030)).c_str()).trimmed(); /* ProtocolName */
        tags["TransmitCoilName"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1251)).c_str()).trimmed(); /* TransmitCoilName */
        tags["AcquisitionMatrix"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1310)).c_str()).trimmed().left(20); /* AcquisitionMatrix */
        tags["InPlanePhaseEncodingDirection"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x1312)).c_str()).trimmed(); /* InPlanePhaseEncodingDirection */
        tags["FlipAngle"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x1314)).c_str()).trimmed(); /* FlipAngle */
        tags["VariableFlipAngleFlag"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1315)).c_str()).trimmed(); /* VariableFlipAngleFlag */
        tags["SAR"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1316)).c_str()).trimmed(); /* SAR */
        tags["dBdt"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1318)).c_str()).trimmed(); /* dBdt */
        tags["PatientPosition"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x5100)).c_str()).trimmed(); /* PatientPosition */

        tags["Unknown Tag & Data"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1009)).c_str()).trimmed(); /* Unknown Tag & Data */
        tags["NumberOfImagesInMosaic"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100A)).c_str()).trimmed(); /* NumberOfImagesInMosaic*/
        tags["SliceMeasurementDuration"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100B)).c_str()).trimmed(); /* SliceMeasurementDuration*/
        tags["B_value"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x100C)).c_str()).trimmed(); /* B_value*/
        tags["DiffusionDirectionality"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100D)).c_str()).trimmed(); /* DiffusionDirectionality*/
        tags["DiffusionGradientDirection"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x100E)).c_str()).trimmed(); /* DiffusionGradientDirection*/
        tags["GradientMode"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x100F)).c_str()).trimmed(); /* GradientMode*/
        tags["FlowCompensation"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1011)).c_str()).trimmed(); /* FlowCompensation*/
        tags["TablePositionOrigin"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1012)).c_str()).trimmed(); /* TablePositionOrigin*/
        tags["ImaAbsTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1013)).c_str()).trimmed(); /* ImaAbsTablePosition*/
        tags["ImaRelTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1014)).c_str()).trimmed(); /* ImaRelTablePosition*/
        tags["SlicePosition_PCS"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1015)).c_str()).trimmed(); /* SlicePosition_PCS*/
        tags["TimeAfterStart"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1016)).c_str()).trimmed(); /* TimeAfterStart*/
        tags["SliceResolution"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1017)).c_str()).trimmed(); /* SliceResolution*/
        tags["RealDwellTime"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1018)).c_str()).trimmed(); /* RealDwellTime*/
        tags["RBMoCoTrans"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1025)).c_str()).trimmed(); /* RBMoCoTrans*/
        tags["RBMoCoRot"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1026)).c_str()).trimmed(); /* RBMoCoRot*/
        tags["B_matrix"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1027)).c_str()).trimmed(); /* B_matrix*/
        tags["BandwidthPerPixelPhaseEncode"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x1028)).c_str()).trimmed(); /* BandwidthPerPixelPhaseEncode*/
        tags["MosaicRefAcqTimes"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1029)).c_str()).trimmed(); /* MosaicRefAcqTimes*/

        tags["StudyInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000D)).c_str()).trimmed(); /* StudyInstanceUID */
        tags["SeriesInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000E)).c_str()).trimmed(); /* SeriesInstanceUID */
        tags["StudyID"] =							QString(sf.ToString(gdcm::Tag(0x0020,0x0010)).c_str()).trimmed(); /* StudyID */
        tags["SeriesNumber"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x0011)).c_str()).trimmed(); /* SeriesNumber */
        tags["AcquisitionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0012)).c_str()).trimmed(); /* AcquisitionNumber */
        tags["InstanceNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0013)).c_str()).trimmed(); /* InstanceNumber */
        tags["ImagePositionPatient"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0032)).c_str()).trimmed(); /* ImagePositionPatient */
        tags["ImageOrientationPatient"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0037)).c_str()).trimmed(); /* ImageOrientationPatient */
        tags["FrameOfReferenceUID"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0052)).c_str()).trimmed(); /* FrameOfReferenceUID */
        tags["NumberOfTemporalPositions"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* NumberOfTemporalPositions */
        tags["ImagesInAcquisition"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* ImagesInAcquisition */
        tags["PositionReferenceIndicator"] =		QString(sf.ToString(gdcm::Tag(0x0020,0x1040)).c_str()).trimmed(); /* PositionReferenceIndicator */
        tags["SliceLocation"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x1041)).c_str()).trimmed(); /* SliceLocation */

        tags["SamplesPerPixel"] =					QString(sf.ToString(gdcm::Tag(0x0028,0x0002)).c_str()).trimmed(); /* SamplesPerPixel */
        tags["PhotometricInterpretation"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0004)).c_str()).trimmed(); /* PhotometricInterpretation */
        tags["Rows"] =								QString(sf.ToString(gdcm::Tag(0x0028,0x0010)).c_str()).trimmed(); /* Rows */
        tags["Columns"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0011)).c_str()).trimmed(); /* Columns */
        tags["PixelSpacing"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0030)).c_str()).trimmed(); /* PixelSpacing */
        tags["BitsAllocated"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0100)).c_str()).trimmed(); /* BitsAllocated */
        tags["BitsStored"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0101)).c_str()).trimmed(); /* BitsStored */
        tags["HighBit"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0102)).c_str()).trimmed(); /* HighBit */
        tags["PixelRepresentation"] =				QString(sf.ToString(gdcm::Tag(0x0028,0x0103)).c_str()).trimmed(); /* PixelRepresentation */
        tags["SmallestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0106)).c_str()).trimmed(); /* SmallestImagePixelValue */
        tags["LargestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0107)).c_str()).trimmed(); /* LargestImagePixelValue */
        tags["WindowCenter"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1050)).c_str()).trimmed(); /* WindowCenter */
        tags["WindowWidth"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1051)).c_str()).trimmed(); /* WindowWidth */
        tags["WindowCenterWidthExplanation"] =		QString(sf.ToString(gdcm::Tag(0x0028,0x1055)).c_str()).trimmed(); /* WindowCenterWidthExplanation */

        tags["RequestingPhysician"] =				QString(sf.ToString(gdcm::Tag(0x0032,0x1032)).c_str()).trimmed(); /* RequestingPhysician */
        tags["RequestedProcedureDescription"] =		QString(sf.ToString(gdcm::Tag(0x0032,0x1060)).c_str()).trimmed(); /* RequestedProcedureDescription */

        tags["PerformedProcedureStepStartDate"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0244)).c_str()).trimmed(); /* PerformedProcedureStepStartDate */
        tags["PerformedProcedureStepStartTime"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0245)).c_str()).trimmed(); /* PerformedProcedureStepStartTime */
        tags["PerformedProcedureStepID"] =			QString(sf.ToString(gdcm::Tag(0x0040,0x0253)).c_str()).trimmed(); /* PerformedProcedureStepID */
        tags["PerformedProcedureStepDescription"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0254)).c_str()).trimmed(); /* PerformedProcedureStepDescription */
        tags["CommentsOnThePerformedProcedureSte"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0280)).c_str()).trimmed(); /* CommentsOnThePerformedProcedureSte */

        tags["TimeOfAcquisition"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100A)).c_str()).trimmed(); /* TimeOfAcquisition*/
        tags["AcquisitionMatrixText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x100B)).c_str()).trimmed(); /* AcquisitionMatrixText*/
        tags["FieldOfView"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100C)).c_str()).trimmed(); /* FieldOfView*/
        tags["SlicePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100D)).c_str()).trimmed(); /* SlicePositionText*/
        tags["ImageOrientation"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100E)).c_str()).trimmed(); /* ImageOrientation*/
        tags["CoilString"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100F)).c_str()).trimmed(); /* CoilString*/
        tags["ImaPATModeText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1011)).c_str()).trimmed(); /* ImaPATModeText*/
        tags["TablePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1012)).c_str()).trimmed(); /* TablePositionText*/
        tags["PositivePCSDirections"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1013)).c_str()).trimmed(); /* PositivePCSDirections*/
        tags["ImageTypeText"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x1016)).c_str()).trimmed(); /* ImageTypeText*/
        tags["SliceThicknessText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1017)).c_str()).trimmed(); /* SliceThicknessText*/
        tags["ScanOptionsText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1019)).c_str()).trimmed(); /* ScanOptionsText*/

        /* fix the study date */
        if (tags["StudyDate"] == "") {
            tags["StudyDate"] = "0000-00-00";
        }
        else {
            tags["StudyDate"].replace("/","-");
            if (tags["StudyDate"].size() == 8) {
                tags["StudyDate"].insert(6,'-');
                tags["StudyDate"].insert(4,'-');
            }
        }

        /* fix the series date */
        if (tags["SeriesDate"] == "")
            tags["SeriesDate"] = tags["StudyDate"];
        else {
            tags["SeriesDate"].replace("/","-");
            if (tags["SeriesDate"].size() == 8) {
                tags["SeriesDate"].insert(6,'-');
                tags["SeriesDate"].insert(4,'-');
            }
        }

        /* fix the study time */
        if (tags["StudyTime"] == "") {
            tags["StudyTime"] = "00:00:00";
        }
        else {
            if (tags["StudyTime"].size() == 13)
                tags["StudyTime"] = tags["StudyTime"].left(6);

            if (tags["StudyTime"].size() == 6) {
                tags["StudyTime"].insert(4,':');
                tags["StudyTime"].insert(2,':');
            }
        }

        /* some images may not have a series date/time, so substitute the studyDateTime for seriesDateTime */
        if (tags["SeriesTime"] == "")
            tags["SeriesTime"] = tags["StudyTime"];
        else {
            if (tags["SeriesTime"].size() == 13)
                tags["SeriesTime"] = tags["SeriesTime"].left(6);

            if (tags["SeriesTime"].size() == 6) {
                tags["SeriesTime"].insert(4,':');
                tags["SeriesTime"].insert(2,':');
            }
        }

        tags["StudyDateTime"] = tags["StudyDate"] + " " + tags["StudyTime"];
        tags["SeriesDateTime"] = tags["SeriesDate"] + " " + tags["SeriesTime"];

        /* fix the birthdate */
        if (tags["PatientBirthDate"] == "") tags["PatientBirthDate"] = "0001-01-01";
        tags["PatientBirthDate"].replace("/","-");
        if (tags["PatientBirthDate"].size() == 8) {
            tags["PatientBirthDate"].insert(6,'-');
            tags["PatientBirthDate"].insert(4,'-');
        }

        /* check for other undefined or blank fields */
        if (tags["PatientSex"] == "") tags["PatientSex"] = 'U';
        if (tags["StationName"] == "") tags["StationName"] = "Unknown";
        if (tags["InstitutionName"] == "") tags["InstitutionName"] = "Unknown";
        if (tags["SeriesNumber"] == "") {
            QString timestamp = tags["SeriesTime"];
            timestamp.remove(':').remove('-').remove(' ');
            tags["SeriesNumber"] = timestamp;
        }

        QString uniqueseries = tags["InstitutionName"] + tags["StationName"] + tags["Modality"] + tags["PatientName"] + tags["PatientBirthDate"] + tags["PatientSex"] + tags["StudyDateTime"] + tags["SeriesNumber"];
        tags["UniqueSeriesString"] = uniqueseries;

        /* attempt to get the Siemens CSA header info */
        tags["PhaseEncodeAngle"] = "";
        tags["PhaseEncodingDirectionPositive"] = "";
        if (cfg["enablecsa"] == "1") {
            /* attempt to get the phase encode angle (In Plane Rotation) from the siemens CSA header */
            QFile df(f);

            /* open the dicom file as a text file, since part of the CSA header is stored as text, not binary */
            if (df.open(QIODevice::ReadOnly | QIODevice::Text)) {

                QTextStream in(&df);
                while (!in.atEnd()) {
                    QString line = in.readLine();
                    if (line.startsWith("sSliceArray.asSlice[0].dInPlaneRot") && (line.size() < 70)) {
                        /* make sure the line does not contain any non-printable ASCII control characters */
                        if (!line.contains(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")))) {
                            int idx = line.indexOf(".dInPlaneRot");
                            line = line.mid(idx,23);
                            QStringList vals = line.split(QRegExp("\\s+"));
                            if (vals.size() > 0)
                                tags["PhaseEncodeAngle"] = vals.last().trimmed();
                            break;
                        }
                    }
                }
                //WriteLog(QString("Found PhaseEncodeAngle of [%1]").arg(tags["PhaseEncodeAngle"]));
                df.close();
            }

            /* get the other part of the CSA header, the PhaseEncodingDirectionPositive value */
            QString systemstring = QString("%1/bin/./gdcmdump -C %2 | grep PhaseEncodingDirectionPositive").arg(cfg["nidbdir"]).arg(f);
            QString csaheader = SystemCommand(systemstring, false);
            QStringList parts = csaheader.split(",");
            QString val;
            if (parts.size() == 5) {
                val = parts[4];
                val.replace("Data '","",Qt::CaseInsensitive);
                val.replace("'","");
                if (val.trimmed() == "Data")
                    val = "";
                tags["PhaseEncodingDirectionPositive"] = val.trimmed();
            }
            //WriteLog(QString("Found PhaseEncodingDirectionPositive of [%1]").arg(tags["PhaseEncodingDirectionPositive"]));
        }
    }
    else {
        /* ---------- not a DICOM file, so see what other type of file it may be ---------- */
        WriteLog(QString("File [%1] is not a DICOM file").arg(f));

        /* check if EEG, and Polhemus */
        if ((f.endsWith(".cnt", Qt::CaseInsensitive)) || (f.endsWith(".dat"), Qt::CaseInsensitive) || (f.endsWith(".3dd"), Qt::CaseInsensitive) || (f.endsWith(".eeg", Qt::CaseInsensitive))) {
            tags["FileType"] = "EEG";
            tags["Modality"] = "EEG";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            tags["PatientID"] = parts[0];
        }
        /* check if ET */
        else if (f.endsWith(".edf", Qt::CaseInsensitive)) {
            tags["FileType"] = "ET";
            tags["Modality"] = "ET";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            tags["PatientID"] = parts[0];
        }
        /* check if MR (Non-DICOM) analyze or nifti */
        else if ((f.endsWith(".nii", Qt::CaseInsensitive)) || (f.endsWith(".nii.gz", Qt::CaseInsensitive)) || (f.endsWith(".hdr", Qt::CaseInsensitive)) || (f.endsWith(".img", Qt::CaseInsensitive))) {
            tags["FileType"] = "NIFTI";
            tags["Modality"] = "NIFTI";
            QFileInfo fn = QFileInfo(f);
            QStringList parts = fn.baseName().split("_");
            tags["PatientID"] = parts[0];
        }
        /* check if par/rec */
        else if (f.endsWith(".par", Qt::CaseInsensitive) || f.endsWith(".rec", Qt::CaseInsensitive)) {
            tags["FileType"] = "PARREC";
            tags["Modality"] = "PARREC";

            /* if its a .rec file, there must be a corresponding .par file with the same name */

            QFile inputFile(f);
            if (inputFile.open(QIODevice::ReadOnly)) {
                QTextStream in(&inputFile);
                while ( !in.atEnd() ) {
                    QString line = in.readLine();
                    if (line.contains("Patient name")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        tags["PatientID"] = parts[1].trimmed();
                    }
                    if (line.contains("Protocol name")) {
                        QStringList parts = line.split(":",Qt::SkipEmptyParts);
                        tags["ProtocolName"] = parts[1].trimmed();
                    }
                    if (line.toUpper().contains("MRSERIES")) {
                        tags["Modality"] = "MR";
                    }
                }
                inputFile.close();
            }
        }
        else {
            /* unknown modality/filetype */
            return false;
        }
    }


    /* fix some of the fields to be amenable to the DB */
    if (tags["Modality"] == "")
        tags["Modality"] = "OT";
    QString StudyDate = ParseDate(tags["StudyDate"]);
    //QString StudyTime = ParseTime(tags["StudyTime"]);
    //QString SeriesDate = ParseDate(tags["SeriesDate"]);
    //QString SeriesTime = ParseTime(tags["SeriesTime"]);

    tags["StudyDateTime"] = tags["StudyDate"] + " " + tags["StudyTime"];
    tags["SeriesDateTime"] = tags["SeriesDate"] + " " + tags["SeriesTime"];
    QStringList pix = tags["PixelSpacing"].split("\\");
    //int pixelX(0);
    //int pixelY(0);
    //if (pix.size() == 2) {
    //    pixelX = pix[0].toInt();
    //    pixelY = pix[1].toInt();
    //}
    QStringList amat = tags["AcquisitionMatrix"].split(" ");
    //int mat1(0);
    //int mat2(0);
    //int mat3(0);
    //int mat4(0);
    if (amat.size() == 4) {
        tags["mat1"] = amat[0].toInt();
        //mat2 = amat[1].toInt();
        //mat3 = amat[2].toInt();
        tags["mat4"] = amat[3].toInt();
    }
    //if (SeriesNumber == 0) {
    //    QString timestamp = SeriesTime;
    //    timestamp.replace(":","").replace("-","").replace(" ","");
    //    tags["SeriesNumber"] = timestamp.toInt();
    //}

    /* fix patient birthdate */
    QString PatientBirthDate = ParseDate(tags["PatientBirthDate"]);

    /* get patient age */
    tags["PatientAge"] = QString("%1").arg(GetPatientAge(tags["PatientAge"], StudyDate, PatientBirthDate));

    /* remove any non-printable ASCII control characters */
    tags["PatientName"].replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"").replace("\\xFFFD","");
    tags["PatientSex"].replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"").replace("\\xFFFD","");

    if (tags["PatientID"] == "")
        tags["PatientID"] = "(empty)";

    /* get parent directory of this file */
    QFileInfo finfo(f);
    QDir d = finfo.dir();
    QString dirname = d.dirName().trimmed();
    tags["ParentDirectory"] = dirname;

    if (tags["PatientName"] == "")
        tags["PatientName"] = tags["PatientID"];

    if (tags["StudyDescription"] == "")
        tags["StudyDescription"] = "(empty)";

    if (tags["PatientSex"] == "")
        tags["PatientName"] = "U";

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetPatientAge ---------------------------------- */
/* ---------------------------------------------------------- */
double nidb::GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate) {
    double PatientAge(0.0);

    /* check if the patient age contains any characters */
    if (PatientAgeStr.contains('Y')) PatientAge = PatientAgeStr.replace("Y","").toDouble();
    if (PatientAgeStr.contains('M')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/12.0;
    if (PatientAgeStr.contains('W')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/52.0;
    if (PatientAgeStr.contains('D')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/365.25;

    /* fix patient age */
    if (PatientAge < 0.001) {
        QDate studydate;
        QDate dob;
        studydate.fromString(StudyDate);
        dob.fromString(PatientBirthDate);

        PatientAge = dob.daysTo(studydate)/365.25;
    }

    return PatientAge;
}


/* ---------------------------------------------------------- */
/* --------- WriteTextFile ---------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::WriteTextFile(QString filepath, QString str, bool append) {

    QFile f(filepath);
    if (append)
        f.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Append);
    else
        f.open(QIODevice::WriteOnly | QIODevice::Text);

    if (f.isOpen()) {
        QTextStream fs(&f);
        fs << str;
        f.close();
        return true;
    }
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- SetExportSeriesStatus -------------------------- */
/* ---------------------------------------------------------- */
bool nidb::SetExportSeriesStatus(int exportseriesid, QString status, QString msg) {

    if (((status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (exportseriesid > 0)) {
        if (msg.trimmed() == "") {
            QSqlQuery q;
            q.prepare("update exportseries set status = :status where exportseries_id = :id");
            q.bindValue(":id", exportseriesid);
            q.bindValue(":status", status);
            WriteLog(SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
        }
        else {
            QSqlQuery q;
            q.prepare("update exportseries set status = :status, statusmessage = :msg where exportseries_id = :id");
            q.bindValue(":id", exportseriesid);
            q.bindValue(":msg", msg);
            q.bindValue(":status", status);
            WriteLog(SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
        }
        return true;
    }
    else {
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- ReadTextFileIntoArray -------------------------- */
/* ---------------------------------------------------------- */
QStringList nidb::ReadTextFileIntoArray(QString filepath, bool ignoreEmptyLines) {
    QStringList a;

    QFile inputFile(filepath);
    inputFile.open(QIODevice::ReadOnly);
    if (inputFile.isOpen()) {
        QTextStream in(&inputFile);

        QString line;
        while (in.readLineInto(&line)) {
            line = line.trimmed();
            if (ignoreEmptyLines && (line.size() == 0)) {}
            else
                a.append(line);
        }
    }

    return a;
}


/* ---------------------------------------------------------- */
/* --------- Mean ------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * Calculates the mean value from a list of doubles
 * @param a array of doubles
*/
double nidb::Mean(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    double sum = 0.0;
    foreach( double n, a )
        sum += n;

    return sum/a.size();
}


/* ---------------------------------------------------------- */
/* --------- Variance --------------------------------------- */
/* ---------------------------------------------------------- */
double nidb::Variance(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    double mean = Mean(a);
    double temp = 0.0;

    foreach (double d, a)
        temp += (d-mean)*(d-mean);

    return temp/(a.size()-1);
}


/* ---------------------------------------------------------- */
/* --------- StdDev ----------------------------------------- */
/* ---------------------------------------------------------- */
double nidb::StdDev(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    return sqrt(Variance(a));
}
