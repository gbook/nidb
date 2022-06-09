/* ------------------------------------------------------------------------------
  NIDB utils.h
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


typedef QHash <int, QHash<QString, QString>> indexedHash;
static const QRegularExpression REwhiteSpace("\\s*");
static const QRegularExpression REnonAlphaNum("[^a-zA-Z0-9_-]");

/* generic functions */
void Print(QString s, bool n=true, bool pad=false);
QString CreateCurrentDateTime(int format=1);
QString CreateLogDate();
void AppendCustomLog(QString f, QString msg);
QString SystemCommand(QString s, bool detail=true, bool truncate=false, bool bufferOutput=true);
bool SandboxedSystemCommand(QString s, QString dir, QString &output, QString timeout="00:05:00", bool detail=true, bool truncate=false);
QString GenerateRandomString(int n);
void SortQStringListNaturally(QStringList &s);
QString RemoveNonAlphaNumericChars(QString s);
QString ParseDate(QString s);
QString ParseTime(QString s);
QString JoinIntArray(QList<int> a, QString glue);
QList<int> SplitStringArrayToInt(QStringList a);
QList<double> SplitStringArrayToDouble(QStringList a);
QList<int> SplitStringToIntArray(QString a);
QStringList ShellWords(QString s);
bool IsInt(QString s);
bool IsDouble(QString s);
bool IsNumber(QString s);
QString WrapText(QString s, int col);
bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg);

double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);

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
void GetDirSizeAndFileCount(QString dir, qint64 &c, qint64 &b, bool recurse=false);
//void GetDirectoryListing(QString dir, QStringList &files, QList<int> &sizes, bool recurse=false);
QByteArray GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm);
bool chmod(QString f, QString perm);
QString UnzipDirectory(QString dir, bool recurse=false);
bool WriteTextFile(QString filepath, QString str, bool append=true);
QStringList ReadTextFileIntoArray(QString filepath, bool ignoreEmptyLines=true);
bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
bool DirectoryExists(QString dir);
bool FileExists(QString f);
bool FileDirectoryExists(QString f);

#endif // UTILS_H
