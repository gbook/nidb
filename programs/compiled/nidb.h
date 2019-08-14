/* ------------------------------------------------------------------------------
  NIDB nidb.h
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

#ifndef NIDB_H
#define NIDB_H

#define __BUILD__ "2019a"

#include <QFile>
#include <QString>
#include <QHash>
#include <QDebug>
#include <QtSql>
#include <QHostInfo>
#include <QDirIterator>
#include "smtp/SmtpMime"
#include "gdcmReader.h"
#include "gdcmWriter.h"
#include "gdcmAttribute.h"
#include "gdcmStringFilter.h"
#include "gdcmAnonymizer.h"

class nidb
{
public:
    QHash<QString, QString> cfg;
    QSqlDatabase db;

	nidb();
	nidb(QString m);
    bool LoadConfig();
	bool DatabaseConnect(bool cluster=false);

	/* module housekeeping functions */
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
	QString CreateCurrentDateTime(int format=1);
	QString CreateLogDate();
	QString SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d=false, bool batch=false);
	QString WriteLog(QString msg);
	void AppendCustomLog(QString f, QString msg);
	QString SystemCommand(QString s, bool detail=true, bool truncate=false);
	QString GenerateRandomString(int n);
	QString CreateUID(QString prefix, int numletters=3);
	void SortQStringListNaturally(QStringList &s);
	bool SendEmail(QString to, QString subject, QString body);
	QString RemoveNonAlphaNumericChars(QString s);
	QString GetPrimaryAlternateUID(int subjectid, int enrollmentid);
	QString ParseDate(QString s);
	QString ParseTime(QString s);
	QString JoinIntArray(QList<int> a, QString glue);
	bool SubmitClusterJob(QString f, QString submithost, QString qsub, QString user, QString queue, QString &msg, int &jobid, QString &result);
	bool GetSQLComparison(QString c, QString &comp, int &num);
	QStringList ShellWords(QString s);

	/* file and directory operations */
	bool MakePath(QString p, QString &msg, bool perm777=true);
	bool RemoveDir(QString p, QString &msg);
	QStringList FindAllFiles(QString dir, QString pattern, bool recursive=false);
	QStringList FindAllDirs(QString dir, QString pattern, bool recursive=false, bool includepath=false);
	QString FindFirstFile(QString dir, QString pattern, bool recursive=false);
	bool MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg);
	bool RenameFile(QString filepathorig, QString filepathnew, bool force=true);
	bool MoveFile(QString f, QString dir);
	void GetDirSizeAndFileCount(QString dir, int &c, qint64 &b, bool recurse=false);
	QByteArray GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm);
	bool chmod(QString f, QString perm);

	/* DICOM functions */
	bool ConvertDicom(QString filetype, QString indir, QString outdir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg);
	bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
	bool IsDICOMFile(QString f);
	bool AnonymizeDir(QString dir, int anonlevel, QString randstr1, QString randstr2);
	bool AnonymizeDICOMFile(gdcm::Anonymizer &anon, QString infile, QString outfile, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags);
	bool ValidNiDBModality(QString m);

private:
    void FatalError(QString err);
	qint64 pid = 0;
	bool checkedin = false;
	bool configLoaded = false;
	QString module;
	QString logFilepath;
	QString lockFilepath;
	QFile log;
};

#endif // NIDB_H
