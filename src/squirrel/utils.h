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
#include <QtSql>
#include <QProcess>
#include <QDirIterator>
#include <QCryptographicHash>
#include <QRandomGenerator>
#include <QCollator>
#include <QRegularExpression>
#include <QCoreApplication>


#define PBSTR "------------------------------------------------------------"
#define PBWIDTH 60

namespace utils {
    typedef QHash <int, QHash<QString, QString>> indexedHash;

    /* generic functions */
    QString Print(QString s, bool n=true, bool pad=false);
    void PrintHeader();
    QString CreateCurrentDateTime(int format=1);
    QString CreateLogDate();
    QString SystemCommand(QString s, bool detail=true, bool truncate=false);
    QString GenerateRandomString(int n);
    QString ParseDate(QString s);
    QString ParseTime(QString s);
    bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg);
    bool ParseTSV(QString tsv, indexedHash &table, QStringList &columns, QString &msg);
    QString CleanJSON(QString s);
    double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);
    QString CleanString(QString s);
    QString HumanReadableSize(qint64 bytes);
    void PrintProgress(double percentage);

    /* file and directory operations */
    bool CopyFile(QString f, QString dir);
    bool MakePath(QString p, QString &msg, bool perm777=true);
    bool RemoveDir(QString p, QString &msg);
    QStringList FindAllFiles(QString dir, QString pattern, bool recursive=false);
    QStringList FindAllDirs(QString dir, QString pattern, bool recursive=false, bool includepath=false);
    bool RenameFile(QString filepathorig, QString filepathnew, bool force=true);
    bool MoveFile(QString f, QString dir, QString &m);
    void GetDirSizeAndFileCount(QString dir, qint64 &c, qint64 &b, bool recurse=false);
    bool WriteTextFile(QString filepath, QString str, bool append=true);
    QString ReadTextFileToString(QString filepath);
    bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
    bool DirectoryExists(QString dir);
    bool FileExists(QString f);

    /* database functions */
    bool SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d=false);
    QStringList GetStagedFileList(qint64 objectID, QString objectType);
    void StoreStagedFileList(qint64 objectID, QString objectType, QStringList paths);
    void RemoveStagedFileList(qint64 objectID, QString objectType);
    QHash<QString, QString> GetParams(qint64 seriesRowID);
    void StoreParams(qint64 seriesRowID, QHash<QString, QString> params);
    QHash<QString, QString> AnonymizeParams(QHash<QString, QString> params);

}
#endif // UTILS_H
