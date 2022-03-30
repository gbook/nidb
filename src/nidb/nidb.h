/* ------------------------------------------------------------------------------
  NIDB nidb.h
  Copyright (C) 2004 - 2022
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

#define VERSION_MAJ "2022"
#define VERSION_MIN "3"
#define BUILD_NUM "771"

#include <QFile>
#include <QString>
#include <QHash>
#include <QDebug>
#include <QtSql>
#include <QHostInfo>
#include <QDirIterator>
#include <QMetaType>
#include <QVariant>
#include "SmtpMime"
#include "gdcmReader.h"
#include "gdcmWriter.h"
#include "gdcmAttribute.h"
#include "gdcmStringFilter.h"
#include "gdcmAnonymizer.h"

typedef QHash <int, QHash<QString, QString>> indexedHash;
typedef QMap<QString, QMap<int, QMap<int, QMap<QString, QString>>>> subjectStudySeriesContainer;

class nidb
{
public:
    QHash<QString, QString> cfg;
    QSqlDatabase db;

    nidb();
    nidb(QString m, bool c=false);
    bool LoadConfig();
    bool DatabaseConnect(bool cluster=false);
    QString GetBuildString();

    /* module housekeeping functions */
    qint64 ModuleGetNumLockFiles();
    bool ModuleCreateLockFile();
    bool ModuleCreateLogFile();
    bool ModuleClearLockFiles();
    void ModuleDeleteLockFile();
    void ModuleRemoveLogFile(bool keepLog);
    bool ModuleCheckIfActive();
    void ModuleDBCheckIn();
    void ModuleDBCheckOut();
    void ModuleRunningCheckIn();
    int ModuleGetNumThreads();
    bool IsRunningFromCluster();

    /* logging */
    void InsertAnalysisEvent(qint64 analysisid, int pipelineid, int pipelineversion, int studyid, QString event, QString message);
    void InsertSubjectChangeLog(QString username, QString uid, QString newuid, QString changetype, QString log);

    /* generic nidb functions */
    QString CreateUID(QString prefix, int numletters=3);
    QString GetPrimaryAlternateUID(int subjectid, int enrollmentid);
    QString GetGroupListing(int groupid);

    /* generic functions */
    void Print(QString s, bool n=true, bool pad=false);
    QString CreateCurrentDateTime(int format=1);
    QString CreateLogDate();
    QString SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d=false, bool batch=false);
    QString WriteLog(QString msg, int wrap=0, bool timeStamp=true);
    void AppendCustomLog(QString f, QString msg);
    QString SystemCommand(QString s, bool detail=true, bool truncate=false, bool bufferOutput=true);
    bool SandboxedSystemCommand(QString s, QString dir, QString &output, QString timeout="00:05:00", bool detail=true, bool truncate=false);
    QString GenerateRandomString(int n);
    void SortQStringListNaturally(QStringList &s);
    bool SendEmail(QString to, QString subject, QString body);
    QString RemoveNonAlphaNumericChars(QString s);
    QString ParseDate(QString s);
    QString ParseTime(QString s);
    QString JoinIntArray(QList<int> a, QString glue);
    QList<int> SplitStringArrayToInt(QStringList a);
    QList<double> SplitStringArrayToDouble(QStringList a);
    QList<int> SplitStringToIntArray(QString a);
    bool SubmitClusterJob(QString f, QString submithost, QString qsub, QString user, QString queue, QString &msg, int &jobid, QString &result);
    bool GetSQLComparison(QString c, QString &comp, int &num);
    QStringList ShellWords(QString s);
    bool IsInt(QString s);
    bool IsDouble(QString s);
    bool IsNumber(QString s);
    QString WrapText(QString s, int col);
    bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg);

    /* math */
    double Mean(QList<double> a);
    double Variance(QList<double> a);
    double StdDev(QList<double> a);

    /* file and directory operations */
    bool MakePath(QString p, QString &msg, bool perm777=true);
    bool RemoveDir(QString p, QString &msg);
    QStringList FindAllFiles(QString dir, QString pattern, bool recursive=false);
    QStringList FindAllDirs(QString dir, QString pattern, bool recursive=false, bool includepath=false);
    bool FindFirstFile(QString dir, QString pattern, QString &f, QString &msg, bool recursive=false);
    bool MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg);
    bool RenameFile(QString filepathorig, QString filepathnew, bool force=true);
    bool MoveFile(QString f, QString dir, QString &m);
    void GetDirSizeAndFileCount(QString dir, quint64 &c, quint64 &b, bool recurse=false);
    //void GetDirectoryListing(QString dir, QStringList &files, QList<int> &sizes, bool recurse=false);
    QByteArray GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm);
    bool chmod(QString f, QString perm);
    QString UnzipDirectory(QString dir, bool recurse=false);
    bool WriteTextFile(QString filepath, QString str, bool append=true);
    QStringList ReadTextFileIntoArray(QString filepath, bool ignoreEmptyLines=true);

    /* DICOM functions */
    bool ConvertDicom(QString filetype, QString indir, QString outdir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg);
    bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
    bool IsDICOMFile(QString f);
    bool AnonymizeDir(QString dir, int anonlevel, QString randstr1, QString randstr2);
    bool AnonymizeDicomFile(gdcm::Anonymizer &anon, QString infile, QString outfile, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags);
    bool isValidNiDBModality(QString m);
    QString GetDicomModality(QString f);
    void GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol);
    bool GetImageFileTags(QString f, QHash<QString, QString> &tags);
    double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);

    /* other */
    bool SetExportSeriesStatus(int exportseriesid, QString status, QString msg = "");

private:
    void FatalError(QString err);
    qint64 pid = 0;                 /*!< Currently running process id */
    bool checkedin = false;         /*!< process id */
    bool configLoaded = false;
    QString module;                 /*!< module name */
    QString logFilepath;
    QString lockFilepath;
    QFile log;
    bool runningFromCluster;        /*!< This nidb executable is being run from a cluster or location other than the main NiDB instance */
};

#endif // NIDB_H
