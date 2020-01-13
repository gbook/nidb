/* ------------------------------------------------------------------------------
  NIDB nidb.cpp
  Copyright (C) 2004 - 2019
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
	return QString("NiDB version %1.%2.%3   Build date [%4 %5]   C++ [%6]   Qt compiled [%7]   Qt runtime [%8]   Build system [%9]").arg(VERSION_MAJ).arg(VERSION_MIN).arg(BUILD_NUM).arg(__DATE__).arg(__TIME__).arg(__cplusplus).arg(QT_VERSION_STR).arg(qVersion()).arg(QSysInfo::buildAbi());
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
			Print("[Ok]");

        return true;
    }
    else {
		Print("[Error]");
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
		if (!cluster) Print("[Ok]");
		return true;
    }
    else {
		QString err = "[Error]\n\tUnable to connect to database. Error message [" + db.lastError().text() + "]";

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
/* --------- GetNumThreads ---------------------------------- */
/* ---------------------------------------------------------- */
int nidb::GetNumThreads() {

	if (module == "fileio") {
		if (cfg["modulefileiothreads"] == "") return 1;
		else return cfg["modulefileiothreads"].toInt();
	}
	else if (module == "export") {
		if (cfg["moduleexportthreads"] == "") return 1;
		else return cfg["moduleexportthreads"].toInt();
	}
	else if ((module == "parsedicom") || (module == "import")) {
		return 1;
	}
	else if (module == "mriqa") {
		if (cfg["modulemriqathreads"] == "") return 1;
		else return cfg["modulemriqathreads"].toInt();
	}
	else if (module == "pipeline") {
		if (cfg["modulepipelinethreads"] == "") return 1;
		else return cfg["modulepipelinethreads"].toInt();
	}
	else if (module == "importuploaded") {
		return 1;
	}
	else if (module == "qc") {
		if (cfg["moduleqcthreads"] == "") return 1;
		else return cfg["moduleqcthreads"].toInt();
	}

	return 1;
}


/* ---------------------------------------------------------- */
/* --------- CheckNumLockFiles ------------------------------ */
/* ---------------------------------------------------------- */
int nidb::CheckNumLockFiles() {
    QStringList lockfiles;

    QDir dir;
	dir.setPath(cfg["lockdir"]);

	QString lockfileprefix = QString("%1.*").arg(module);
    QStringList filters;
    filters << lockfileprefix;

    QStringList files = dir.entryList(filters);
	int numlocks = files.size();

    return numlocks;
}


/* ---------------------------------------------------------- */
/* --------- CreateLockFile --------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::CreateLockFile() {
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
		Print("[Ok]");
        return 1;
    }
    else {
		Print("[Error]");
		return 0;
    }
}


/* ---------------------------------------------------------- */
/* --------- CreateLogFile ---------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::CreateLogFile () {
	logFilepath = QString("%1/%2%3.log").arg(cfg["logdir"]).arg(module).arg(CreateLogDate());
	log.setFileName(logFilepath);

	Print("Creating log file [" + logFilepath + "]",false, true);
	if (log.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Unbuffered)) {
		QString padding = "";
		if (pid < 1000) padding = "";
		else if (pid < 10000) padding = " ";
		else padding = "  ";
		log.write(GetBuildString().toLatin1());
		Print("[Ok]");
		return 1;
	}
	else {
		Print("[Error]");
		return 0;
	}
}


/* ---------------------------------------------------------- */
/* --------- DeleteLockFile --------------------------------- */
/* ---------------------------------------------------------- */
void nidb::DeleteLockFile() {

	Print("Deleting lock file [" + lockFilepath + "]",false, true);

	QFile f(lockFilepath);
	if (f.remove())
		Print("[Ok]");
	else
		Print("[Error]");
}


/* ---------------------------------------------------------- */
/* --------- RemoveLogFile ---------------------------------- */
/* ---------------------------------------------------------- */
void nidb::RemoveLogFile(bool keepLog) {

	if (!keepLog) {
		Print("Deleting log file [" + logFilepath + "]",false, true);
		QFile f(logFilepath);
		if (f.remove())
			Print("[Ok]");
		else
			Print("[Error]");
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
	QString err = QString("SQL ERROR (Module: %1 Function: %2 File: %3 Line: %4)\n\nSQL [%5]\n\nDatabase error [%6]\n\nDriver error [%7]").arg(module).arg(function).arg(file).arg(line).arg(sql).arg(q.lastError().databaseText()).arg(q.lastError().driverText());
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

    if (q.size() < 1)
		return false;
    else
		return true;
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

	if (q.numRowsAffected() > 0)
		Print("[Ok]");
	else
		Print("[Error]");

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
	QString hostname = QHostInfo::localHostName();

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
QString nidb::SystemCommand(QString s, bool detail, bool truncate) {

	double starttime = QDateTime::currentMSecsSinceEpoch();
	QString ret;
	QString output;
	QProcess process;

	process.setProcessChannelMode(QProcess::MergedChannels);
	process.start("sh", QStringList() << "-c" << s);

	/* Get the output */
	if (process.waitForStarted(-1)) {
		while(process.waitForReadyRead(-1)) {
			output += process.readAll();
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
QString nidb::WriteLog(QString msg, int wrap) {
	if (msg.trimmed() != "") {
		if (wrap > 0)
			msg = WrapText(msg, wrap);
		if (log.isWritable()) {
			if (!log.write(QString("\n[%1][%2] %3").arg(CreateCurrentDateTime()).arg(pid).arg(msg).toLatin1()))
				Print("Unable to write to log file!");
		}
		else {
			Print("Log file is not writeable! Tried to write [" + msg + "] to [" + log.fileName() + "]");
		}
	}

	return msg;
}


/* ---------------------------------------------------------- */
/* --------- MakePath --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MakePath(QString p, QString &msg, bool perm777) {

	if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p == "/root") || (p == "/home")) {
		msg = "Path is not valid [" + p + "]";
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
		SystemCommand("chmod -R 777 " + p);

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
bool nidb::MoveFile(QString f, QString dir) {

	QDir d;
	if (d.exists(dir)) {
		QString systemstring;
		systemstring = QString("mv %1 %2/").arg(f).arg(dir);

		if (SystemCommand(systemstring, false).trimmed() != "")
			return false;
	}
	else
		return false;

	return true;
}


/* ---------------------------------------------------------- */
/* --------- RenameFile ------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::RenameFile(QString filepathorig, QString filepathnew, bool force) {

	QString systemstring;
	if (force)
		systemstring = QString("mv -f %1 %2").arg(filepathorig).arg(filepathnew);
	else
		systemstring = QString("mv %1 %2").arg(filepathorig).arg(filepathnew);


	if (SystemCommand(systemstring, false).trimmed() == "")
		return true;
	else
		return false;
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
		//WriteLog("Checkpoint A");
		QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
		//WriteLog("Checkpoint B");
		if (it.hasNext()) {
			//WriteLog("Checkpoint C");
			f = it.next();
		}
	}
	else {
		//WriteLog("Checkpoint D");
		QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks);
		//WriteLog("Checkpoint E");
		if (it.hasNext()) {
			//WriteLog("Checkpoint F");
			f = it.next();
		}
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
	QFileInfoList fl = d.entryInfoList(QDir::NoDotAndDotDot | QDir::Files);
	c = fl.size();
	for (int i=0; i < fl.size(); i++) {
		const QFileInfo finfo = fl.at(i);
		b += finfo.size();
	}

	if (recurse) {
		QDirIterator it(dir, QStringList() << "*", QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
		while (it.hasNext()) {
			it.next();
			c++;
			b += it.fileInfo().size();
		}
	}
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
		systemstring = QString("%1/./dcm2niixme %2 -o '%3' %4").arg(cfg["scriptdir"]).arg(gzipstr).arg(outdir).arg(indir);
	else if (filetype == "nifti4d")
		systemstring = QString("%1/./dcm2niix -1 -b n %2 -o '%3' %4%5").arg(cfg["scriptdir"]).arg(gzipstr).arg(outdir).arg(indir).arg(fileext);
	else if (filetype == "nifti3d")
		systemstring = QString("%1/./dcm2niix -1 -b n -z 3 -o '%2' %3%4").arg(cfg["scriptdir"]).arg(outdir).arg(indir).arg(fileext);
	else if (filetype == "bids")
		systemstring = QString("%1/./dcm2niix -1 -b y -z y -o '%2' %3%4").arg(cfg["scriptdir"]).arg(outdir).arg(indir).arg(fileext);
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
bool nidb::AnonymizeDICOMFile(gdcm::Anonymizer &anon, QString infile, QString outfile, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags)
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
		AnonymizeDICOMFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags);
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

	time = QTime::fromString(s, "h:m:s"); /* unlikely */
	if (time.isValid()) return time.toString("hh:mm:ss");

	time = QTime::fromString(s, "hh:mm");
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
bool nidb::ValidNiDBModality(QString m) {
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

	if (a.size() > 0)
		foreach (QString v, a)
			i.append(v.trimmed().toInt());

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
	result = SystemCommand(systemstring,false).trimmed();

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
bool nidb::ParseCSV(QString csv, indexedHash &table, QString &m) {

	m = "";
	bool ret(true);

	/* get header row */
	QStringList lines = csv.trimmed().split(QRegularExpression("[\\n\\r]"));

	if (lines.size() > 1) {
		QString header = lines.takeFirst();
		QStringList cols = header.trimmed().toLower().split(QRegularExpression("\\s*,\\s*"));

		m += QString("Found [%1] columns. ").arg(cols.size());
		/* remove the last column if it was blank, because the file contained an extra trailing comma */
		if (cols.last() == "") {
			cols.removeLast();
			m += QString("Last column was blank, removing. ").arg(cols.size());
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
					table[row][cols[col]] = buffer;

					buffer = "";
					col++;
				}
				else {
					buffer = QString("%1%2").arg(buffer).arg(c); /* make sure no null terminators end up in the string */
				}
			}
			if (col != numcols) {
				m += QString("Error: row [%1] has [%2] columns, but expecting [%3] columns").arg(row).arg(col).arg(numcols);
				ret = false;
			}

			row++;
		}
		m += QString("Processed [%1] data rows. ").arg(row);
	}
	else {
		ret = false;
		m += ".csv file contained only one row. csv must contain at least one header row and one data row. ";
	}

	return ret;
}
