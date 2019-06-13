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
nidb::nidb(QString m)
{
	module = m;

	pid = QCoreApplication::applicationPid();

	Print(QString("Build date [%1 %2]    C++ version [%3]").arg(__DATE__).arg(__TIME__).arg(__cplusplus));

	LoadConfig();
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
		Print("Config file not found");
        return false;
    }

	Print("Loading config file " + f.fileName(), false, true);

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
					//qDebug() << var << ": " << value;
                }
            }
        }
        f.close();
		configLoaded = true;

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
bool nidb::DatabaseConnect() {

    db = QSqlDatabase::addDatabase("QMYSQL");
    db.setHostName(cfg["mysqlhost"]);
    db.setDatabaseName(cfg["mysqldatabase"]);
    db.setUserName(cfg["mysqluser"]);
    db.setPassword(cfg["mysqlpassword"]);

	Print("Connecting to database [" + cfg["mysqldatabase"] + "] on [" + cfg["mysqlhost"] + "]... ", false, true);
    if (db.open()) {
		Print("[Ok]");
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
    int numlocks = files.count();

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
int nidb::SQLQuery(QSqlQuery &q, QString function, bool d, bool batch) {

	/* get the SQL string that will be run */
	QString sql = q.lastQuery();
	QMapIterator<QString, QVariant> it(q.boundValues());
	while (it.hasNext()) {
		it.next();
		sql.replace(it.key(),it.value().toString());
	}

	/* debugging */
	if (cfg["debug"].toInt() || d) {
		//Print("Running SQL statement[" + sql + "]");
		WriteLog(sql);
	}

	/* run the query */
	if (batch)
		if (q.execBatch(QSqlQuery::ValuesAsRows))
			return q.size();
	if (q.exec())
		return q.size();

	QString err = QString("SQL ERROR (Module: %1 Function: %2) SQL [%3] Error(DB) [%4] Error(driver) [%5]").arg(module).arg(function).arg(sql).arg(q.lastError().databaseText()).arg(q.lastError().driverText());
	SendEmail(cfg["adminemail"], "SQL error", err);
	qDebug() << err;
	qDebug() << q.lastError();
	WriteLog(err);
	WriteLog("SQL error, exiting program");
	exit(0);
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
	SQLQuery(q, "ModuleDBCheckIn");

	if (q.numRowsAffected() > 0)
		Print("[Ok]");
	else
		Print("[Error]");
}


/* ---------------------------------------------------------- */
/* --------- ModuleDBCheckOut ------------------------------- */
/* ---------------------------------------------------------- */
void nidb::ModuleDBCheckOut() {
	QSqlQuery q;
	q.prepare("update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = :module");
	q.bindValue(":module", module);
	SQLQuery(q, "ModuleDBCheckOut");

	q.prepare("delete from module_procs where module_name = :module and process_id = :pid");
	q.bindValue(":module", module);
	q.bindValue(":pid", pid);
	SQLQuery(q, "ModuleDBCheckOut");

	Print("Module checked out of database");
}


/* ---------------------------------------------------------- */
/* --------- ModuleRunningCheckIn --------------------------- */
/* ---------------------------------------------------------- */
/* this is a deadman's switch. if the module doesn't check in
   after a certain period of time, the module is assumed to
   be dead and is reset so it can start again */
void nidb::ModuleRunningCheckIn() {
	QSqlQuery q;
	if (!checkedin) {
		q.prepare("insert ignore into module_procs (module_name, process_id) values (:module, :pid)");
		q.bindValue(":module", module);
		q.bindValue(":pid", pid);
		SQLQuery(q, "ModuleRunningCheckIn");
		checkedin = true;
	}

	/* update the checkin time */
	q.prepare("update module_procs set last_checkin = now() where module_name = :module and process_id = :pid");
	q.bindValue(":module", module);
	q.bindValue(":pid", pid);
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
/* this function does not work in Windows                     */
/* ---------------------------------------------------------- */
QString nidb::SystemCommand(QString s, bool detail) {
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

	output = output.trimmed();
	if (detail)
		ret = QString("Running command [%1], Output [%2]").arg(s).arg(output);
	else
		ret = output;

	return ret;
}


/* ---------------------------------------------------------- */
/* --------- WriteLog --------------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::WriteLog(QString msg) {
	if (msg.trimmed() != "") {
		if (cfg["debug"].toInt())
			Print(msg);

		if (!log.write(QString("\n[%1][%2] %3").arg(CreateCurrentDateTime()).arg(pid).arg(msg).toLatin1()))
			Print("Unable to write to log file!");
	}

	return msg;
}


/* ---------------------------------------------------------- */
/* --------- MakePath --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MakePath(QString p, QString &msg) {

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

   const QString possibleCharacters("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789");
   qsrand(QTime::currentTime().msec());

   QString randomString;
   for(int i=0; i<n; ++i)
   {
	   int index = qrand() % possibleCharacters.length();
	   QChar nextChar = possibleCharacters.at(index);
	   randomString.append(nextChar);
   }
   return randomString;
}


/* ---------------------------------------------------------- */
/* --------- MoveFile --------------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MoveFile(QString f, QString dir) {

	QFile file(f);
	QDir d;
	if (d.exists(dir))
		file.rename(dir + "/" + QFileInfo(f).fileName());
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
	if (cfg["debug"] == "1") WriteLog("Finding all files in ["+dir+"] with pattern ["+pattern+"]");

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

	if (cfg["debug"] == "1") WriteLog(QString("Finished searching for files. Found [%1] files").arg(files.size()));

	return files;
}


/* ---------------------------------------------------------- */
/* --------- FindFirstFile ---------------------------------- */
/* ---------------------------------------------------------- */
QString nidb::FindFirstFile(QString dir, QString pattern, bool recursive) {
	QString f;
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

	return f;
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
		dir = "*";

	QStringList dirs;

	if (recursive) {
		QDirIterator it(dir, QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
		while (it.hasNext()) {
			if (includepath)
				dirs << it.next();
			else {
				dirs << it.fileName();
				it.next();
			}
		}
	}
	else {
		QDirIterator it(dir, QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks);
		while (it.hasNext()) {
			if (includepath)
				dirs << it.next();
			else {
				dirs << it.fileName();
				it.next();
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

	WriteLog("Entering GetDirSizeAndFileCount()");

	QDir d(dir);
	QFileInfoList fl = d.entryInfoList(QDir::NoDotAndDotDot | QDir::Files);
	WriteLog("got QFileInfoList");
	c = fl.size();
	for (int i=0; i < fl.size(); i++) {
		const QFileInfo finfo = fl.at(i);
		b += finfo.size();
	}
	WriteLog("Finished getting file count and size");

	if (recurse) {
		QDirIterator it(dir, QStringList() << "*", QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
		while (it.hasNext()) {
			it.next();
			c++;
			b += it.fileInfo().size();
		}
	}

	WriteLog("Leaving GetDirSizeAndFileCount()");
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
	SQLQuery(q, "InsertSubjectChangeLog");
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
		systemstring = QString("%1/./dcm2niix -1 -b n -z y -o '%2' %3%4").arg(cfg["scriptdir"]).arg(outdir).arg(indir).arg(fileext);
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
		WriteLog(SystemCommand(systemstring2, true));

		/* execute the command created above */
		WriteLog(SystemCommand(systemstring, true));
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
	SQLQuery(q, "GetPrimaryAlternateUID");
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

	/* seed the random number generator */
	qsrand(QTime::currentTime().msec());

	C1 = numbers.at( qrand() % numbers.length() );
	C2 = numbers.at( qrand() % numbers.length() );
	C3 = numbers.at( qrand() % numbers.length() );
	C4 = numbers.at( qrand() % numbers.length() );

	QStringList badarray;
	badarray << "fuck" << "shit" << "piss" << "tits" << "dick" << "cunt" << "twat" << "jism" << "jizz" << "arse" << "damn" << "fart" << "hell" << "wang" << "wank" << "gook" << "kike" << "kyke" << "spic" << "arse" << "dyke" << "cock" << "muff" << "pusy" << "butt" << "crap" << "poop" << "slut" << "dumb" << "snot" << "boob" << "dead" << "anus" << "clit" << "homo" << "poon" << "tard" << "kunt" << "tity" << "tit" << "ass" << "dic" << "dik" << "fuk";
	bool safe = false;

	while (!safe) {
		C5 = letters.at( qrand() % letters.length() );
		C6 = letters.at( qrand() % letters.length() );
		C7 = letters.at( qrand() % letters.length() );

		if (numletters == 4)
			C8 = letters.at( qrand() % letters.length() );

		QString str;
		str = QString("%1%2%3%4").arg(C5).arg(C6).arg(C7).arg(C8);
		if (!badarray.contains(str)) {
			safe = true;
		}
	}

	//newID = QString("%1%2%3%4%5%6%7%8%9").arg(prefix).arg(C1).arg(C2).arg(C3).arg(C4).arg(C5).arg(C6).arg(C7).arg(C8);
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
	if (r.CanRead())
		return true;
	else
		return false;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDICOMFile ----------------------------- */
/* ---------------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx                 */
bool nidb::AnonymizeDICOMFile(gdcm::Anonymizer &anon, const char *filename, const char *outfilename, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags, bool continuemode)
{
	gdcm::Reader reader;
	reader.SetFileName( filename );
	if( !reader.Read() ) {
		std::cerr << "Could not read : " << filename << std::endl;
		if( continuemode ) {
			std::cerr << "Skipping from anonymization process (continue mode)." << std::endl;
			return true;
		}
		else
		{
			std::cerr << "Check [--continue] option for skipping files." << std::endl;
			return false;
		}
	}
	gdcm::File &file = reader.GetFile();

	anon.SetFile( file );

	if( empty_tags.empty() && replace_tags.empty() && remove_tags.empty() ) {
		std::cerr << "No operation to be done." << std::endl;
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
	writer.SetFileName( outfilename );
	writer.SetFile( file );
	if( !writer.Write() ) {
		std::cerr << "Could not Write : " << outfilename << std::endl;
		if( strcmp(filename,outfilename) != 0 ) {
			gdcm::System::RemoveFile( outfilename );
		}
		else
		{
			std::cerr << "gdcmanon just corrupted: " << filename << " for you (data lost)." << std::endl;
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
		    qDebug() << "No anonymization requested. Leaving files unchanged.";
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
		const char *dcmfile = it.next().toStdString().c_str();
		AnonymizeDICOMFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags, false);
	}

	return true;
}
