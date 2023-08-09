/* ------------------------------------------------------------------------------
  NIDB nidb.cpp
  Copyright (C) 2004 - 2023
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
    debug = false;
}


/* ---------------------------------------------------------- */
/* --------- nidb ------------------------------------------- */
/* ---------------------------------------------------------- */
nidb::nidb(QString m, bool c)
{
    module = m;
    runningFromCluster = c;
    debug = false;

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
/* --------- GetVersion ------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GetVersion() {
    return QString("version%1.%2.%3").arg(VERSION_MAJ).arg(VERSION_MIN).arg(BUILD_NUM);
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
            if ((line.trimmed().size() > 0) && (line.at(0) != '#')) {
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
    else if (module == "modulemanager") {
        numThreads = 1;
    }

    //WriteLog(QString("ModuleGetNumThreads() returned [%1] threads for module [%2]").arg(numThreads).arg(module));
    return numThreads;
}


/* ---------------------------------------------------------- */
/* --------- ModuleGetNumLockFiles -------------------------- */
/* ---------------------------------------------------------- */
qint64 nidb::ModuleGetNumLockFiles() {
    QDir dir;
    dir.setPath(cfg["lockdir"]);

    QString lockfileprefix = QString("%1.*").arg(module);
    QStringList filters;
    filters << lockfileprefix;

    QStringList files = dir.entryList(filters);
    qint64 numlocks = files.size();

    Print(QString("Found [%1] lockfiles for module [%2]").arg(numlocks).arg(module));
    //WriteLog(QString("ModuleGetNumLockFiles() found [%1] lockfiles for module [%2]").arg(numlocks).arg(module));

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
/* --------- SQLQuery --------------------------------------- */
/* ---------------------------------------------------------- */
/* QSqlQuery object must already be prepared and bound before */
/* being passed in to this function                           */
QString nidb::SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d, bool batch) {

    /* get the SQL string that will be run */
    QString sql = q.executedQuery();
    QVariantList list = q.boundValues();
    for (int i=0; i < list.size(); ++i) {
        sql += QString(" [" + list.at(i).toString() + "]");
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
        //WriteLog("ModuleCheckIfActive() returned false");
        return false;
    }
    else {
        //WriteLog("ModuleCheckIfActive() returned true");
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
/* --------- Debug ------------------------------------------ */
/* ---------------------------------------------------------- */
QString nidb::Debug(QString msg, int wrap, bool timeStamp) {
    if (debug) {
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
    }

    return msg;
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
/* --------- GetPrimaryAlternateUID ------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GetPrimaryAlternateUID(qint64 subjectid, qint64 enrollmentid) {

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
/* --------- CreateUID -------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::CreateUID(QString prefix, int numletters) {

    QString newID;
    QString letters("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
    QString numbers("0123456789");
    QChar C1, C2, C3, C4, C5, C6, C7, C8;

    QStringList badarray;
    badarray << "fuck" << "shit" << "piss" << "tits" << "dick" << "cunt" << "twat" << "jism" << "jizz" << "arse" << "damn" << "fart" << "hell" << "wang" << "wank" << "gook" << "kike" << "kyke" << "spic" << "arse" << "dyke" << "cock" << "muff" << "pusy" << "butt" << "crap" << "poop" << "slut" << "dumb" << "snot" << "boob" << "dead" << "anus" << "clit" << "homo" << "poon" << "tard" << "kunt" << "tity" << "tit" << "ass" << "dic" << "dik" << "fuk" << "kkk";
    bool done = false;

    C1 = numbers.at( QRandomGenerator::global()->bounded(10) );
    C2 = numbers.at( QRandomGenerator::global()->bounded(10) );
    C3 = numbers.at( QRandomGenerator::global()->bounded(10) );
    C4 = numbers.at( QRandomGenerator::global()->bounded(10) );

    do {
        C5 = letters.at( QRandomGenerator::global()->bounded(26) );
        C6 = letters.at( QRandomGenerator::global()->bounded(26) );
        C7 = letters.at( QRandomGenerator::global()->bounded(26) );

        if (numletters == 4)
            C8 = letters.at( QRandomGenerator::global()->bounded(26) );

        QString str;
        str = QString("%1%2%3%4").arg(C5).arg(C6).arg(C7).arg(C8);
        if (!badarray.contains(str,Qt::CaseInsensitive))
            done = true;
    }
    while (!done);

    if (numletters == 4)
        newID = QString("%1%2%3%4%5%6%7%8%9").arg(prefix).arg(C1).arg(C2).arg(C3).arg(C4).arg(C5).arg(C6).arg(C7).arg(C8);
    else
        newID = QString("%1%2%3%4%5%6%7%8").arg(prefix).arg(C1).arg(C2).arg(C3).arg(C4).arg(C5).arg(C6).arg(C7);

    return newID.trimmed();
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
//	static const QRegularExpression whiteSpaceRE("\\s*");
    c.remove(QRegularExpression("\\s+"));

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
                QString uid = q.value("uid").toString().replace('\u0000', "");
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
                QString uid = q.value("uid").toString().replace('\u0000', "");
                s += QString("%1%2, ").arg(uid).arg(studynum);
            }
        }
    }

    return s;
}


/* ---------------------------------------------------------- */
/* --------- SetExportSeriesStatus -------------------------- */
/* ---------------------------------------------------------- */
bool nidb::SetExportSeriesStatus(qint64 exportseriesid, qint64 exportid, qint64 seriesid, QString modality, QString status, QString msg) {

    /* get the export series ID by exportID and modality if the exportseriesid is blank */
    if (exportseriesid == -1) {
        QSqlQuery q;
        q.prepare("select exportseries_id from exportseries where export_id = :exportid and series_id = :seriesid and modality = :modality");
        q.bindValue(":exportid", exportid);
        q.bindValue(":seriesid", seriesid);
        q.bindValue(":modality", modality);
        SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            exportseriesid = q.value("exportseries_id").toLongLong();
        }
    }

    if (((status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (exportseriesid > 0)) {
        if (msg.trimmed() == "") {
            QSqlQuery q;
            q.prepare("update exportseries set status = :status where exportseries_id = :id");
            q.bindValue(":id", exportseriesid);
            q.bindValue(":status", status);
            SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        else {
            QSqlQuery q;
            q.prepare("update exportseries set status = :status, statusmessage = :msg where exportseries_id = :id");
            q.bindValue(":id", exportseriesid);
            q.bindValue(":msg", msg);
            q.bindValue(":status", status);
            SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
        return true;
    }
    else {
        return false;
    }
}
