#ifndef NIDB_H
#define NIDB_H

#include <QFile>
#include <QString>
#include <QHash>
#include <QDebug>
#include <QtSql>
#include <QHostInfo>
#include <QDirIterator>
#include "smtp/SmtpMime"

class nidb
{
public:
    QHash<QString, QString> cfg;
    QSqlDatabase db;

	nidb();
	nidb(QString m);
    bool LoadConfig();
    bool DatabaseConnect();

	/* module housekeeping */
	int CheckNumLockFiles();
	bool CreateLockFile();
	bool CreateLogFile();
	void DeleteLockFile();
	void RemoveLogFile(bool keepLog);
	bool ModuleCheckIfActive();
	void ModuleDBCheckIn();
	void ModuleDBCheckOut();
	void ModuleRunningCheckIn();
	int GetNumThreads();

	/* logging */
	void InsertAnalysisEvent(int analysisid, int pipelineid, int pipelineversion, int studyid, QString event, QString message);
	void InsertSubjectChangeLog(QString username, QString uid, QString newuid, QString changetype, QString log);

	/* generic functions */
	void Print(QString s, bool n=true, bool pad=false);
	QString CreateCurrentDate();
	QString CreateLogDate();
	int SQLQuery(QSqlQuery &q, QString function, bool d=false);
	QString WriteLog(QString msg);
	QString SystemCommand(QString s, bool detail=true);
	QString GetBuildDate();
	bool MakePath(QString p, QString &msg);
	bool RemoveDir(QString p, QString &msg);
	QString GenerateRandomString(int n);
	QStringList FindAllFiles(QString dir, QString pattern);
	QString FindFirstFile(QString dir, QString pattern, bool recursive=false);
	bool MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg);
	void GetDirSize(QString dir, double &bytes, int &filecount);
	bool SendEmail(QString to, QString subject, QString body);
	bool ConvertDicom(QString filetype, QString indir, QString outdir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg);
	bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
	QString GetPrimaryAlternateUID(int subjectid, int enrollmentid);
	QByteArray GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm);

private:
    void FatalError(QString err);

	QString builtDate = QString::fromLocal8Bit(__DATE__); // set text for the label

	bool configLoaded = false;
	QString module;
	QString logFilepath;
	QString lockFilepath;
	QFile log;
};

#endif // NIDB_H
