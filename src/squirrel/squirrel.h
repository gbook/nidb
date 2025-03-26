/* ------------------------------------------------------------------------------
  Squirrel squirrel.h
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

#ifndef SQUIRREL_H
#define SQUIRREL_H

#include <QString>
#include <QDate>
#include <QDateTime>
#include <QDebug>
#include <QtSql>
#include <QUuid>
#include "squirrelSubject.h"
#include "squirrelStudy.h"
#include "squirrelSeries.h"
#include "squirrelExperiment.h"
#include "squirrelPipeline.h"
#include "squirrelObservation.h"
#include "squirrelIntervention.h"
#include "squirrelGroupAnalysis.h"
#include "squirrelDataDictionary.h"
#include "squirrelTypes.h"


/**
 * @brief The squirrel class
 *
 * provides a complete class to read, write, modify, split, merge, and validate squirrel files
 */
class squirrel
{
public:
    /* constructors */
    squirrel(bool dbg=false, bool q=false);
    ~squirrel();

    /* user-facing package operations */
    QString Print(bool detail=false);
    bool Extract(QString destinationDir, QString &m);
    bool Read();
    bool Validate();
    bool Write();
    bool WriteUpdate();
    bool ExtractArchiveFilesToDirectory(QString archivePath, QString filePattern, QString outDir, QString &m);

    /* get/set options */
    QString GetDatabaseUUID() { return databaseUUID; } /*!< get the database UUID */
    QString GetPackagePath();
    QString GetSystemTempDir();
    bool GetDebug() { return debug; } /*!< true if debugging is enabled */
    bool GetDebugSQL() { return debugSQL; } /*!< true if SQL debugging is enabled */
    void SetDebug(bool d);
    void SetDebugSQL(bool d);
    void SetFileMode(FileMode m) { fileMode = m; } /*!< Set the file mode to either NewPackage or ExistingPackage */
    void SetOverwritePackage(bool o);
    void SetPackagePath(QString p) { packagePath = p; } /*!< Set the package path */
    void SetQuickRead(bool q);
    void SetSystemTempDir(QString tmpdir);
    void SetWriteLog(bool w) { writeLog = w; }

    /* package JSON elements */
    QDateTime Datetime;         /*!< datetime the package was created */
    QString Changes;            /*!< any changes since last package release */
    QString DataFormat;         /*!< orig, anon, anonfull, nift3d, nifti3dgz, nifti4d, nifti4dgz */
    QString Description;        /*!< detailed description of the package */
    QString License;            /*!< a data usage license */
    QString NiDBversion;        /*!< NiDB version that wrote this package */
    QString Notes;              /*!< JSON string of notes (may contain JSON sub-elements of 'import', 'merge', 'export') */
    QString PackageFormat;      /*!< 'squirrel' */
    QString PackageName;        /*!< name of the package */
    QString Readme;             /*!< a README */
    QString SeriesDirFormat;    /*!< orig, seq */
    QString SquirrelBuild;      /*!< squirrel build */
    QString SquirrelVersion;    /*!< squirrel version */
    QString StudyDirFormat;     /*!< orig, seq */
    QString SubjectDirFormat;   /*!< orig, seq */

    /* get list(s) of objects */
    QList<squirrelAnalysis> GetAnalysisList(qint64 studyRowID);
    QList<squirrelDataDictionary> GetDataDictionaryList();
    QList<squirrelExperiment> GetExperimentList();
    QList<squirrelGroupAnalysis> GetGroupAnalysisList();
    QList<squirrelIntervention> GetInterventionList(qint64 subjectRowID);
    QList<squirrelObservation> GetObservationList(qint64 subjectRowID);
    QList<squirrelPipeline> GetPipelineList();
    QList<squirrelSeries> GetSeriesList(qint64 studyRowID);
    QList<squirrelStudy> GetStudyList(qint64 subjectRowID);
    QList<squirrelSubject> GetSubjectList();

    /* get individual objects */
    squirrelAnalysis GetAnalysis(qint64 analysisRowID);
    squirrelDataDictionary GetDataDictionary(qint64 dataDictionaryRowID);
    squirrelExperiment GetExperiment(qint64 experimentRowID);
    squirrelGroupAnalysis GetGroupAnalysis(qint64 groupAnalysisRowID);
    squirrelIntervention GetIntervention(qint64 interventionRowID);
    squirrelObservation GetObservation(qint64 observationRowID);
    squirrelPipeline GetPipeline(qint64 pipelineRowID);
    squirrelSeries GetSeries(qint64 seriesRowID);
    squirrelStudy GetStudy(qint64 studyRowID);
    squirrelSubject GetSubject(qint64 subjectRowID);

    /* find objects, and return rowID */
    qint64 FindAnalysis(QString subjectID, int studyNum, QString analysisName);
    qint64 FindDataDictionary(QString dataDictionaryName);
    qint64 FindExperiment(QString experimentName);
    qint64 FindGroupAnalysis(QString groupAnalysisName);
    qint64 FindPipeline(QString pipelineName);
    qint64 FindSeries(QString subjectID, int studyNum, int seriesNum);
    qint64 FindSeriesByUID(QString seriesUID);
    qint64 FindStudy(QString subjectID, int studyNum);
    qint64 FindStudyByUID(QString studyUID);
    qint64 FindSubject(QString id);

    /* extract objects */
    bool ExtractObject(ObjectType object, qint64 subjectRowID, QString outDir, bool recursive=false);

    /* remove objects */
    bool RemoveObject(ObjectType object, qint64 objectRowID);

    bool AddStagedFiles(ObjectType object, qint64 rowid, QStringList files);

    /* requence the subject data */
    void ResequenceSubjects();
    void ResequenceStudies(qint64 subjectRowID);
    void ResequenceSeries(qint64 studyRowID);

    /* package information */
    bool GetJsonHeader(QJsonDocument &jdoc);
    bool UpdateJsonHeader(QString json);
    qint64 GetFileCount();
    qint64 GetFreeDiskSpace(); /* this is not named GetDiskFreeSpace() because of collision with Windows API */
    qint64 GetObjectCount(ObjectType object);
    qint64 GetUnzipSize();

    /* validation functions */
    QString GetTempDir();
    bool IsValid() { return isValid; }
    /*!< true if ok to delete the object */
    bool OkToDelete() { return isOkToDelete; }

    /* functions to read special files */
    QHash<QString, QString> ReadParamsFile(QString f);

    /* logging */
    void Log(QString s);
    void Debug(QString s, QString func="");
    QString GetLog() { return log; } /*!< Get the entire log */
    QString GetLogBuffer();
    bool quiet=false;

    /* printing of information to console */
    QString PrintAnalyses(qint64 studyRowID, PrintFormat printFormat=List);
    QString PrintDataDictionary(PrintFormat printFormat=List);
    QString PrintExperiments(PrintFormat printFormat=List);
    QString PrintGroupAnalyses(PrintFormat printFormat=List);
    QString PrintInterventions(qint64 subjectRowID, PrintFormat printFormat=List);
    QString PrintObservations(qint64 subjectRowID, PrintFormat printFormat=List);
    QString PrintPackage();
    QString PrintPipelines(PrintFormat printFormat=List);
    QString PrintSeries(qint64 studyRowID, PrintFormat printFormat=List);
    QString PrintStudies(DatasetType dataType, PrintFormat printFormat, qint64 subjectRowID);
    QString PrintSubjects(DatasetType dataType, PrintFormat printFormat);
    QString PrintTree();

    /* database and utility functions */

    /* static functions */
    static QString ObjectTypeToString(ObjectType object);
    static ObjectType ObjectTypeToEnum(QString object);

private:
    bool DatabaseConnect();
    bool DeleteTempDir(QString dir);
    bool InitializeDatabase();
    bool MakeTempDir(QString &dir);

    /* 7zip archive functions */
    bool AddFilesToArchive(QStringList filePaths, QStringList compressedFilePaths, QString archivePath, QString &m);
    bool CompressDirectoryToArchive(QString dir, QString archivePath, QString &m);
    bool ExtractArchiveToDirectory(QString archivePath, QString destinationPath, QString &m);
    bool ExtractArchiveFileToMemory(QString archivePath, QString filePath, QString &fileContents);
    bool Get7zipLibPath();
    bool GetArchiveFileListing(QString archivePath, QString subDir, QStringList &files, QString &m);
    bool RemoveDirectoryFromArchive(QString compressedDirPath, QString archivePath, QString &m);
    bool UpdateMemoryFileToArchive(QString file, QString compressedFilePath, QString archivePath, QString &m);

    QString log;
    QString logBuffer;
    QString logfile;
    QString p7zipLibPath;
    QString packagePath;
    QString systemTempDir;
    QString workingDir;
    QStringList msgs; /* squirrel messages to be passed back through the squirrel library */

    FileMode fileMode;

    /* database */
    QSqlDatabase db;
    QString databaseUUID; /* necessary to create unique DB connections if more than one squirrel package is opened at a time */

    /* flags */
    bool debug;
    bool debugSQL;
    bool isOkToDelete;
    bool isValid;
    bool overwritePackage;
    bool quickRead; /* set true to skip reading of the params.json files */
    bool writeLog;
};

#endif // SQUIRREL_H
