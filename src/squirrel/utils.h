/* ------------------------------------------------------------------------------
  NIDB utils.h
  Copyright (C) 2004 - 2025
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
#include "squirrelTypes.h"

#define PBSTR "------------------------------------------------------------"
#define PBWIDTH 60

namespace utils {
    typedef QHash <int, QHash<QString, QString>> indexedHash;

    /* generic functions */
    QDateTime StringToDatetime(QString datetime);
    QString CleanJSON(QString s);
    QString CleanString(QString s);
    QString CreateCurrentDateTime(int format=1);
    QString CreateLogDate();
    QString GenerateRandomString(int n);
    QString HumanReadableSize(qint64 bytes);
    QString ParseDate(QString s);
    QString ParseTime(QString s);
    QString Print(QString s, bool n=true, bool pad=false);
    QString PrintData(PrintFormat p, QStringList keys, QList <QStringHash> rows);
    QString SystemCommand(QString s, bool detail=true, bool truncate=false);
    bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg);
    bool ParseTSV(QString tsv, indexedHash &table, QStringList &columns, QString &msg);
    double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);
    void PrintHeader();
    void PrintProgress(double percentage);
    QStringHash MergeStringHash(QStringHash hash1, QStringHash hash2);

    /* file and directory operations */
    bool CopyFileToDir(QString f, QString dir);
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

    bool SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d=false);
    QHash<QString, QString> AnonymizeParams(QHash<QString, QString> params);
    QStringList GetStagedFileList(QString databaseUUID, qint64 objectID, ObjectType object);
    void StoreStagedFileList(QString databaseUUID, qint64 objectID, ObjectType object, QStringList paths);
    void RemoveStagedFileList(QString databaseUUID, qint64 objectID, ObjectType object);
    QHash<QString, QString> GetParams(QString databaseUUID, qint64 seriesRowID);
    void StoreParams(QString databaseUUID, qint64 seriesRowID, QHash<QString, QString> params);

}
#endif // UTILS_H
