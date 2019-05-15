#include "nidb.h"

nidb::nidb()
{
}


nidb::nidb(QString m)
{
	module = m;

	qDebug() << "Build date: " << builtDate << " C++ version " << __cplusplus;

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
					//qDebug() << var << ": " << value;
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
/* --------- GetNumThreads ---------------------------------- */
/* ---------------------------------------------------------- */
int nidb::GetNumThreads() {

	if (module == "fileio") {
		if (cfg["modulefileiothreads"] == "") return 1;
		else return cfg["modulefileiothreads"].toInt();
	}
	else if (module == "export") {
		if (cfg["modulefileiothreads"] == "") return 1;
		else return cfg["modulefileiothreads"].toInt();
	}
	else if (module == "parsedicom") {
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
	logFilepath = QString("%1/%2%3.log").arg(cfg["logdir"]).arg(module).arg(CreateLogDate());
	log.setFileName(logFilepath);

	if (log.open(QIODevice::WriteOnly | QIODevice::Text)) {
		WriteLog(QString("Created log file for the " + module + " module"));
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
	date = d.toString("yyyy/MM/dd HH:mm:ss");

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
int nidb::SQLQuery(QSqlQuery &q, QString function, bool d) {

	/* get the SQL string that will be run */
	QString sql = q.lastQuery();
	QMapIterator<QString, QVariant> it(q.boundValues());
	while (it.hasNext()) {
		it.next();
		sql.replace(it.key(),it.value().toString());
	}

	/* debugging */
	if (cfg["debug"].toInt() || d) {
		qDebug() << "Running SQL statement[" << sql <<"]";
		WriteLog(sql);
	}

	/* run the query */
    if (q.exec()) {
		return q.size();
    }
    else {
		QString err = QString("SQL ERROR (Module: %1 Function: %2) SQL [%3] Error(DB) [%4] Error(driver) [%5]").arg(module).arg(function).arg(sql).arg(q.lastError().databaseText()).arg(q.lastError().driverText());
		SendEmail(cfg["adminemail"], "SQL error", err);
		qDebug() << err;
		qDebug() << q.lastError();
		WriteLog(err);
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
		return false;
    else
		return true;
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
	q.prepare("update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = :module");
	q.bindValue(":module", module);
	SQLQuery(q, "ModuleDBCheckOut");

	q.prepare("delete from module_procs where module_name = :module and process_id = :pid");
	q.bindValue(":module", module);
	q.bindValue(":pid", QCoreApplication::applicationPid());
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
	qint64 pid = 0;
	pid = QCoreApplication::applicationPid();

	if (cfg["debug"].toInt()) {
		qDebug() << msg;
	}
	else {
		if (msg.trimmed() != "") {
			if (!log.write(QString("\n[%1][%2] %3").arg(CreateCurrentDate()).arg(pid).arg(msg).toLatin1()))
				qDebug() << "Unable to write to log file!";
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
/* --------- FindAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
QStringList nidb::FindAllFiles(QString dir, QString pattern) {
	QStringList files;
	QDirIterator it(dir, QStringList() << pattern, QDir::Files, QDirIterator::Subdirectories);
	while (it.hasNext())
		files << it.next();

	return files;
}


/* ---------------------------------------------------------- */
/* --------- MoveAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
bool nidb::MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg) {
	QStringList msgs;
	bool ret = true;
	QDirIterator it(indir, QStringList() << pattern, QDir::Files, QDirIterator::Subdirectories);
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
/* --------- GetDirSize ------------------------------------- */
/* ---------------------------------------------------------- */
void nidb::GetDirSize(QString dir, double &bytes, int &filecount) {
	filecount = 0;
	bytes = 0.0;
	QDirIterator it(dir, QDir::Files | QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
	while (it.hasNext()) {
		filecount++;
		it.next();
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
		qDebug() << "Failed to connect to host [" << cfg["emailserver"] << "]";
		smtp.quit();
		return false;
	}
	if (!smtp.login()) {
		qDebug() << "Failed to login using username [" << cfg["emailusername"] << "] and password [" << cfg["emailpassword"] << "]";
		smtp.quit();
		return false;
	}
	if (!smtp.sendMail(message)) {
		qDebug() << "Failed to send [" << body << "]";
		smtp.quit();
		return false;
	}
	else {
		qDebug() << "Sent email successfuly";
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
bool nidb::ConvertDicom(QString filetype, QString indir, QString outdir, bool gzip, QString uid, int studynum, int seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg) {

	QStringList msgs;

	QString pwd = QDir::currentPath();

	QString gzipstr;
	if (gzip) gzipstr = "-z y";
	else gzipstr = "-z n";

	numfilesconv = 0; /* need to fix this to be correct at some point */

	WriteLog("Working on [" + indir + "]");

	/* in case of par/rec, the argument list to dcm2niix is a file instead of a directory */
	QString fileext = "";
	if (datatype == "parrec")
		fileext = "/*.par";

	/* do the conversion */
	QString systemstring;
	QDir::setCurrent(indir);
	if (filetype == "nifti4d")
		systemstring = QString("%1/./dcm2niixme %2 -o '%3' %4").arg(cfg["scriptdir"]).arg(gzipstr).arg(outdir).arg(indir);
	else if (filetype == "nifti4dme")
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
		systemstring = QString("rm -f %1/*.hdr %1/*.img %1/*.nii %1/*.gz").arg(outdir);
		WriteLog(SystemCommand(systemstring, true));
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
bool nidb::BatchRenameFiles(QString dir, int seriesnum, int studynum, QString uid, int &numfilesrenamed, QString &msg) {

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
			QString newName = fi.path() + "/" + QString("%1_%2_%3_%4%5").arg(uid).arg(studynum).arg(seriesnum).arg(i).arg(ext);
			qDebug() << fname + " --> " + newName;
			if (f.rename(newName))
				numfilesrenamed++;
			else
				qDebug() << "Error renaming file [" + fname + "] to [" + newName + "]";
			i++;
		}
	}

	return true;
}
