#ifndef NIDB_H
#define NIDB_H

#include <QFile>
#include <QString>
#include <QHash>
#include <QDebug>
#include <QtSql>
#include <QHostInfo>

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

	void InsertAnalysisEvent(int analysisid, int pipelineid, int pipelineversion, int studyid, QString event, QString message);

	/* generic functions */
	QString CreateCurrentDate();
	QString CreateLogDate();
	int SQLQuery(QSqlQuery &q, QString function, bool d=false);
	QString WriteLog(QString msg);
	QString SystemCommand(QString s, bool detail=true);
	//QString runCommand(const QString& cmd);
	QString GetBuildDate();
	bool MakePath(QString p, QString &msg);
	bool RemoveDir(QString p, QString &msg);
	QString GenerateRandomString(int n);

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
