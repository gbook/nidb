/* ------------------------------------------------------------------------------
  NIDB nidb.h
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

#ifndef NIDB_H
#define NIDB_H

#include <QFile>
#include <QString>
#include <QHash>
#include <QDebug>
#include <QtSql>
#include <QHostInfo>
#include <QDirIterator>
#include <QMetaType>
#include <QVariant>
#include "version.h"
#include "SmtpMime"
#include "utils.h"

typedef QMap<QString, QMap<int, QMap<int, QMap<QString, QString>>>> subjectStudySeriesContainer;

#ifdef Q_OS_WINDOWS
#pragma warning(disable : 5045)
#endif

/**
 * @brief The nidb class
 *
 * The nidb class is the root class for creating, running, and managing nidb modules
 */
class nidb
{
public:
    QHash<QString, QString> cfg;    /*!< configuration variables */
    QSqlDatabase db;                /*!< database connection */
    bool debug;                     /*!< true if module is in debugging mode */

    nidb();
    nidb(QString m, bool c=false);
    bool LoadConfig();
    bool DatabaseConnect(bool cluster=false);
    QString GetBuildString();
    QString GetVersion();

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
    bool SetExportSeriesStatus(qint64 exportseriesid, qint64 exportid, qint64 seriesid, QString modality, QString status, QString msg = "");

    /* generic nidb functions */
    QString CreateUID(QString prefix, int numletters=3);
    QString GetPrimaryAlternateUID(qint64 subjectid, qint64 enrollmentid);
    QString GetGroupListing(int groupid);
    bool isValidNiDBModality(QString m);
    //bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg);
    //double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);
    QString SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d=false, bool batch=false);
    QString Log(QString msg, QString func="", int wrap=0, bool timeStamp=true);
    QString Debug(QString msg, QString func="", int wrap=0, bool timeStamp=true);
    bool SendEmail(QString to, QString subject, QString body);
    bool GetSQLComparison(QString c, QString &comp, int &num);
    //bool SubmitClusterJob(QString f, QString submithost, QString qsub, QString user, QString queue, QString &msg, int &jobid, QString &result);
    bool SubmitClusterJob(QString jobFilePath, QString clusterType, QString submitHost, QString submitUser, QString qsub, QString clusterUser, QString clusterQueue, QString &msg, int &jobid, QString &result);

private:
    void FatalError(QString err);
    qint64 pid = 0;                 /*!< Currently running process id */
    bool checkedin = false;         /*!< true if this module has been checked in */
    bool configLoaded = false;      /*!< true if the config has been loaded */
    QString module;                 /*!< current module name */
    QString logFilepath;            /*!< LOG file path */
    QString lockFilepath;           /*!< LOCK file path */
    QFile log;                      /*!< the contents of the logfile. This is available as a buffer */
    bool runningFromCluster;        /*!< This nidb executable is being run from a cluster or location other than the main NiDB instance */
};

#endif // NIDB_H
