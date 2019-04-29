#include "nidb.h"

nidb::nidb()
{
}


nidb::nidb(QString m)
{
	module = m;

	qDebug() << "Build date: " << builtDate;

	LoadConfig();
}


/* ---------------------------------------------------------- */
/* --------- LoadConfig ------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::LoadConfig() {

	if (configLoaded) return 1;

	/* list of possible locations for the config file */
	QStringList files;
	files << "nidb.cfg"
	      << "../nidb.cfg"
	      << "../../nidb.cfg"
	      << "../../../nidb.cfg"
	      << "../../prod/programs/nidb.cfg"
	      << "../../../../prod/programs/nidb.cfg"
	      << "../programs/nidb.cfg"
	      << "/home/nidb/programs/nidb.cfg"
	      << "/nidb/programs/nidb.cfg"
	      << "M:/programs/nidb.cfg"
	         ;

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
        qDebug() << "Config file not found";
        return false;
    }

    qDebug() << "Using config file [" << f.fileName() << "]";

    /* open and read the config file */
    if (f.open(QIODevice::ReadOnly | QIODevice::Text)) {

        QTextStream in(&f);
        while (!in.atEnd()) {
            QString line = in.readLine();
            if ((line.trimmed().count() > 0) && (line.at(0) != '#')) {
                QStringList parts = line.split(" = ");
                QString var = parts[0].trimmed();
                QString value = parts[1].trimmed();
                var.remove('[').remove(']');
                if (var != "") {
                    cfg[var] = value;
                }
            }
        }
        f.close();
		configLoaded = true;
        return true;
    }
    else {
        qDebug() << "Unable to open config file [" << f.fileName() << "]";
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- DatabaseConnect -------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::DatabaseConnect() {

    cfg["mysqlhost"] = "10.35.10.65";

    db = QSqlDatabase::addDatabase("QMYSQL");
    db.setHostName(cfg["mysqlhost"]);
    db.setDatabaseName(cfg["mysqldatabase"]);
    db.setUserName(cfg["mysqluser"]);
    db.setPassword(cfg["mysqlpassword"]);

	qDebug() << "Attempting to connect to database [" << cfg["mysqldatabase"] << "] on host [" << cfg["mysqlhost"] << "]...";
    if (db.open()) {
		qDebug() << "Connected to database";
        return true;
    }
    else {
		QString err = "Unable to connect to database. Error message [" + db.lastError().text() + "]";

        FatalError(err);
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- FatalError ------------------------------------- */
/* ---------------------------------------------------------- */
void nidb::FatalError(QString err) {
    qDebug() << err;
    exit(0);
}


/* ---------------------------------------------------------- */
/* --------- CheckNumLockFiles ------------------------------ */
/* ---------------------------------------------------------- */
int nidb::CheckNumLockFiles() {
    QStringList lockfiles;

    QDir dir;
	dir.setPath(cfg["lockdir"]);

	QString lockfileprefix = module;
    lockfileprefix += ".*";
    QStringList filters;
    filters << lockfileprefix;

	qDebug() << "Checking for existing lock files [" << lockfileprefix << "]";

    QStringList files = dir.entryList(filters);
    int numlocks = files.count();

	qDebug() << "Found [" << numlocks << "] existing lock files";
    foreach (const QString &f, files) {
        qDebug() << f;
    }

    return numlocks;
}


/* ---------------------------------------------------------- */
/* --------- CreateLockFile --------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::CreateLockFile() {
	qint64 pid = 0;
    pid = QCoreApplication::applicationPid();
    
	lockFilepath = QString("%1/%2.%3").arg(cfg["lockdir"]).arg(module).arg(pid);
	QFile f(lockFilepath);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
        QString d = CreateCurrentDate();
        QTextStream fs(&f);
        fs << d;
        f.close();
        return 1;
    }
    else {
		qDebug() << "Unable to create lock file [" << lockFilepath << "]";
		return 0;
    }
}


/* ---------------------------------------------------------- */
/* --------- CreateLogFile ---------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::CreateLogFile () {
	logFilepath = QString("%1/%2.log").arg(cfg["logdir"]).arg(module);
	QFile f(logFilepath);
	if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
		QString d = CreateCurrentDate() + " - Created log file for module";
		QTextStream fs(&f);
		fs << d;
		f.close();
		return 1;
	}
	else {
		qDebug() << "Unable to create log file [" << logFilepath << "]";
		return 0;
	}
}


/* ---------------------------------------------------------- */
/* --------- DeleteLockFile --------------------------------- */
/* ---------------------------------------------------------- */
void nidb::DeleteLockFile() {
	QFile f(lockFilepath);
	if (f.remove())
		qDebug() << "Deleted lock file [" << lockFilepath << "]";
	else
		qDebug() << "Unable to delete lock file [" << lockFilepath << "]";
}


/* ---------------------------------------------------------- */
/* --------- RemoveLogFile ---------------------------------- */
/* ---------------------------------------------------------- */
void nidb::RemoveLogFile(bool keepLog) {
	if (!keepLog) {
		QFile f(lockFilepath);
		if (f.remove())
			qDebug() << "Deleted log file [" << logFilepath << "]";
		else
			qDebug() << "Unable to delete log file [" << logFilepath << "]";
	}
}


/* ---------------------------------------------------------- */
/* --------- CreateCurrentDate ------------------------------ */
/* ---------------------------------------------------------- */
QString nidb::CreateCurrentDate() {
    QString date;

    QDateTime d = QDateTime::currentDateTime();
    date = d.toString("YYYY/MM/dd HH:mm:ss");

    return date;
}


/* ---------------------------------------------------------- */
/* --------- CreateLogDate ---------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::CreateLogDate() {
	QString date;

	QDateTime d = QDateTime::currentDateTime();
	date = d.toString("YYYYMMddHHmmss");

	return date;
}


/* ---------------------------------------------------------- */
/* --------- SQLQuery --------------------------------------- */
/* ---------------------------------------------------------- */
/* QSqlQuery object must already be prepared and bound before */
/* being passed in to this function                           */
int nidb::SQLQuery(QSqlQuery &q, QString function, bool d) {

	QString sql = q.lastQuery();

	if (cfg["debug"].toInt() || d) qDebug() << "Running SQL statement[" << sql <<"]";

    if (q.exec()) {
        return q.size();
    }
    else {
        qDebug() << "SQL ERROR (Module: " << module << "  Function: " << function << ") SQL [" << sql << "]" << q.lastError().text();
        return -1;
    }
}


/* ---------------------------------------------------------- */
/* --------- ModuleCheckIfActive ---------------------------- */
/* ---------------------------------------------------------- */
bool nidb::ModuleCheckIfActive() {

	QSqlQuery q;
	q.prepare("select * from modules where module_name = :module and module_isactive = 1");
	q.bindValue(":module", module);
	SQLQuery(q, "ModuleCheckIfActive");

    if (q.size() < 1)
        return 0;
    else
        return 1;
}


/* ---------------------------------------------------------- */
/* --------- ModuleDBCheckIn -------------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleDBCheckIn() {
	QSqlQuery q;
	q.prepare("update modules set module_laststart = now(), module_status = 'running', module_numrunning = module_numrunning + 1 where module_name = :module");
	q.bindValue(":module", module);
	SQLQuery(q, "ModuleDBCheckIn");

	qDebug() << "Module checked in to database";
}


/* ---------------------------------------------------------- */
/* --------- ModuleDBCheckOut ------------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleDBCheckOut() {
	QSqlQuery q;
	q.prepare("update modules set module_laststart = now(), module_status = 'stopped', module_numrunning = module_numrunning + 1 where module_name = :module");
	q.bindValue(":module", module);
	SQLQuery(q, "ModuleDBCheckOut");

	qDebug() << "Module checked out of database";
}


/* ---------------------------------------------------------- */
/* --------- ModuleRunningCheckIn --------------------------- */
/* ---------------------------------------------------------- */
/* this is a deadman's switch. if the module doesn't check in
   after a certain period of time, the module is assumed to
   be dead and is reset so it can start again */
void nidb::ModuleRunningCheckIn() {
	QSqlQuery q;
	q.prepare("insert ignore into module_procs (module_name, process_id) values (:module, :pid)");
	q.bindValue(":module", module);
	q.bindValue(":pid", QCoreApplication::applicationPid());
	SQLQuery(q, "ModuleRunningCheckIn");

	/* update the checkin time */
	q.prepare("update module_procs set last_checkin = now() where module_name = :module and process_id = :pid");
	q.bindValue(":module", module);
	q.bindValue(":pid", QCoreApplication::applicationPid());
	SQLQuery(q, "ModuleRunningCheckIn");
}


/* ---------------------------------------------------------- */
/* --------- InsertAnalysisEvent ---------------------------- */
/* ---------------------------------------------------------- */
void nidb::InsertAnalysisEvent(int analysisid, int pipelineid, int pipelineversion, int studyid, QString event, QString message) {
	QString hostname = QHostInfo::localHostName();

	QSqlQuery q;
	q.prepare("insert into analysis_history (analysis_id, pipeline_id, pipeline_version, study_id, analysis_event, analysis_hostname, event_message) values (:analysisid, :pipelineid, :pipelineversion, :studyid, :event, :hostname, :message)");
	q.bindValue(":analysisid", analysisid);
	q.bindValue(":pipelineid", pipelineid);
	q.bindValue(":pipelineversion", pipelineversion);
	q.bindValue(":studyid", studyid);
	q.bindValue(":event", event);
	q.bindValue(":message", message);
	SQLQuery(q, "ModuleRunningCheckIn");
}


/* ---------------------------------------------------------- */
/* --------- SystemCommand ---------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::SystemCommand(QString s, bool detail) {
	QProcess process;
	process.start(/* command line stuff */);
	process.waitForFinished(-1); // will wait forever until finished

	QString ret;
	QString stdOut = process.readAllStandardOutput();
	QString stdErr = process.readAllStandardError();

	if (detail)
		ret = QString("Command [%1] stdout [%2] stderr [%3]").arg(s).arg(stdOut).arg(stdErr);
	else
		ret = stdOut + stdErr;

	return ret;
}


/* ---------------------------------------------------------- */
/* --------- WriteLog --------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::WriteLog(QString msg) {
	qint64 pid = 0;
	pid = QCoreApplication::applicationPid();

	if (cfg["debug"].toInt()) {
		qDebug() << msg;
	}
	else {
		if (msg.trimmed() != "") {
			log.write(QString("[%1][%2] %3").arg(CreateCurrentDate()).arg(pid).arg(msg).toLatin1());
		}
	}

	return msg;
}


/* ---------------------------------------------------------- */
/* --------- GetBuildDate ----------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::GetBuildDate() {
	return builtDate;
}


/* ---------------------------------------------------------- */
/* --------- MakePath --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MakePath(QString p, QString &msg) {

	if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p == "/root") || (p == "/home")) {
		msg = "Path is not valid [" + p + "]";
		return false;
	}

	QDir path(p);
	if (path.exists()) {
		msg = QString("Path [" + p + "] exists");
	}
	else {
		if (path.mkpath(p)) {
			msg = QString("Destination path [" + p + "] created");
		}
		else {
			msg = QString("Destination path [" + p + "] not created");
			return false;
		}
	}

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

	return true;
}
