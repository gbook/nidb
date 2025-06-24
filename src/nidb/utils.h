/* ------------------------------------------------------------------------------
  NIDB utils.h
  Copyright (C) 2004 - 2024
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

#ifndef UTILS_H
#define UTILS_H

#include <QString>
#include <QFile>
#include <QDir>
#include <QList>
#include <QHash>
#include <QDate>
#include <QProcess>
#include <QDirIterator>
#include <QCryptographicHash>
#include <QRandomGenerator>
#include <QCollator>
#include <QRegularExpression>
#include <QCoreApplication>
#include <QJsonObject>
#include <QJsonArray>
#include <QJsonDocument>

typedef QHash <int, QHash<QString, QString> > indexedHash;
static const QRegularExpression REwhiteSpace("\\s*");
static const QRegularExpression REnonAlphaNum("[^a-zA-Z0-9_-]");

struct BIDSMapping {
    QString bidsEntity;
    QString bidsIntendedForEntity;
    QString bidsIntendedForFileExtension;
    QString bidsIntendedForRun;
    QString bidsIntendedForSuffix;
    QString bidsIntendedForTask;
    QString bidsPEDirection;
    QString bidsSuffix;
    QString bidsTask;
    QString imageType;
    QString phaseDir;
    QString protocol;
    bool bidsAutoNumberRuns;
    bool bidsIncludeAcquisition;
    int bidsRun;
    int run;
};

struct UploadOptions {
    QDateTime uploadStartDate;
    QDateTime uploadEndDate;
    QString status;
    double statusPercent;
    QString log;
    QString originalFileList;
    QString source;
    QString type;
    QString dataPath;
    QString stagingPath;
    int projectRowID;
    QString patientID;
    QString modality;
    bool guessModality;
    QString subjectMatchCriteria;
    QString studyMatchCriteria;
    QString seriesMatchCriteria;
};

struct computeCluster {
    QString name;
    QString description;
    QString type;
    QString submitHostname;
    QString submitHostUsername;
    QString clusterUsername;
    QString queue;
    qint64 maxWallTime = -1;
    qint64 memory = 1;
    qint64 numCores = 1;
};


/* generic functions */
QList<double> SplitStringArrayToDouble(QStringList a);
QList<int> SplitStringArrayToInt(QStringList a);
QList<int> SplitStringToIntArray(QString a);
QString CreateCurrentDateTime(int format=1);
QString CreateLogDate();
QString GenerateRandomString(int n);
QString JoinIntArray(QList<int> a, QString glue);
QString ParseDate(QString s);
QString ParseTime(QString s);
QString RemoveNonAlphaNumericChars(QString s);
QString SystemCommand(QString s, bool detail=true, bool truncate=false);
QString WrapText(QString s, int col);
QStringList ShellWords(QString s);
bool IsDouble(QString s);
bool IsInt(QString s);
bool IsNumber(QString s);
bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg);
bool SandboxedSystemCommand(QString s, QString dir, QString &output, QString timeout="00:05:00", bool detail=true, bool truncate=false);
void AppendCustomLog(QString f, QString msg);
void Print(QString s, bool n=true, bool pad=false);
void SortQStringListNaturally(QStringList &s);

double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);

/* math */
double Mean(QList<double> a);
double Variance(QList<double> a);
double StdDev(QList<double> a);

/* file and directory operations */
//void GetDirectoryListing(QString dir, QStringList &files, QList<int> &sizes, bool recurse=false);
QByteArray GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm);
QString UnzipDirectory(QString dir, bool recurse=false);
QStringList FindAllDirs(QString dir, QString pattern, bool recursive=false, bool includepath=false);
QStringList FindAllFiles(QString dir, QString pattern, bool recursive=false);
QStringList ReadTextFileIntoArray(QString filepath, bool ignoreEmptyLines=true);
bool BatchRenameBIDSFiles(QString dir, QString bidsSubject, QString bidsSession, BIDSMapping mapping, int &numfilesrenamed, QString &msg);
bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
bool CopyFile(QString f, QString dir, QString &m);
bool DirectoryExists(QString dir);
bool FileDirectoryExists(QString f);
bool FileExists(QString f);
bool FindFirstFile(QString dir, QString pattern, QString &f, QString &msg, bool recursive=false);
bool GetZipFileDetails(QString zippath, qint64 &unzipsize, qint64 &zipsize, QString &compression, qint64 &numfiles, QString &filelisting);
bool MakePath(QString p, QString &msg, bool perm777=true);
bool MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg);
bool MoveFile(QString f, QString dir, QString &m);
bool RemoveDir(QString p, QString &msg);
bool RenameFile(QString filepathorig, QString filepathnew, bool force=true);
bool WriteTextFile(QString filepath, QString str, bool append=true);
bool chmod(QString f, QString perm);
void GetDirSizeAndFileCount(QString dir, qint64 &c, qint64 &b, bool recurse=false);

#endif // UTILS_H
