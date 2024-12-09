/* ------------------------------------------------------------------------------
  Squirrel squirrel.h
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

#ifndef SQUIRREL_H
#define SQUIRREL_H

//#include <string>
#include <QString>
#include <QDate>
#include <QDateTime>
#include <QDebug>
#include <QtSql>
#include "squirrelSubject.h"
#include "squirrelStudy.h"
#include "squirrelSeries.h"
#include "squirrelExperiment.h"
#include "squirrelPipeline.h"
#include "squirrelObservation.h"
#include "squirrelIntervention.h"
#include "squirrelGroupAnalysis.h"
#include "squirrelDataDictionary.h"
#include "squirrelVersion.h"

enum FileMode { NewPackage, ExistingPackage };
enum PrintingType { List, Details, CSV, Tree };
typedef QPair<QString, QString> QStringPair;
typedef QList<QStringPair> pairList;

/**
 * @brief The squirrel class
 *
 * provides a complete class to read, write, and validate squirrel files
 */
class squirrel
{
public:
    squirrel(bool dbg=false, bool q=false);
    ~squirrel();

    bool Read();
    bool Write(bool writeLog);
    bool Validate();
    QString Print(bool detail=false);
    void SetPackagePath(QString p) { packagePath = p; }
    QString GetPackagePath();
    void SetFileMode(FileMode m) { fileMode = m; } /*!< Set the file mode to either NewPackage or ExistingPackage */
    void SetDebugSQL(bool d);
    void SetOverwritePackage(bool o);
    void SetQuickRead(bool q);
    bool Extract(QString destinationDir, QString &m);

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

    /* get numbers of objects */
    qint64 GetFileCount();
    qint64 GetObjectCount(QString object);

    /* find objects, return rowID */
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

    /* remove objects */
    bool RemoveAnalysis(qint64 analysisRowID);
    bool RemoveDataDictionary(qint64 dataDictionaryRowID);
    bool RemoveExperiment(qint64 experimentRowID);
    bool RemoveGroupAnalysis(qint64 groupAnalysisRowID);
    bool RemoveIntervention(qint64 InterventionRowID);
    bool RemoveObservation(qint64 observationRowID);
    bool RemovePipeline(qint64 pipelineRowID);
    bool RemoveSeries(qint64 seriesRowID);
    bool RemoveStudy(qint64 studyRowID);
    bool RemoveSubject(qint64 subjectRowID);

    bool AddStagedFiles(QString objectType, qint64 rowid, QStringList files);

    /* requence the subject data */
    void ResequenceSubjects();
    void ResequenceStudies(qint64 subjectRowID);
    void ResequenceSeries(qint64 studyRowID);

    /* package information */
    qint64 GetUnzipSize();

    /* validation functions */
    QString GetTempDir();
    bool IsValid() { return isValid; }
    bool OkToDelete() { return isOkToDelete; }

    /* functions to read special files */
    QHash<QString, QString> ReadParamsFile(QString f);

    /* logging */
    void Log(QString s, QString func);
    void Debug(QString s, QString func="");
    QString GetLog() { return log; }
    QString GetLogBuffer();
    bool GetDebug() { return debug; }
    bool GetDebugSQL() { return debugSQL; }
    bool quiet=false;

    /* printing of information to console */
    QString PrintDataDictionary(bool details=false);
    QString PrintExperiments(bool details=false);
    QString PrintGroupAnalyses(bool details=false);
    QString PrintInterventions(qint64 subjectRowID, PrintingType printType=PrintingType::List);
    QString PrintObservations(qint64 subjectRowID, PrintingType printType=PrintingType::List);
    QString PrintPackage();
    QString PrintPipelines(bool details=false);
    QString PrintSeries(qint64 studyRowID, bool details=false);
    QString PrintStudies(qint64 subjectRowID, bool details=false);
    QString PrintSubjects(PrintingType printType=PrintingType::List);

    QSqlDatabase db;

private:
    bool AddFilesToArchive(QStringList filePaths, QStringList compressedFilePaths, QString archivePath, QString &m);
    bool CompressDirectoryToArchive(QString dir, QString archivePath, QString &m);
    bool DatabaseConnect();
    bool DeleteTempDir(QString dir);
    bool ExtractArchiveToDirectory(QString archivePath, QString destinationPath, QString &m);
    bool ExtractFileFromArchive(QString archivePath, QString filePath, QString &fileContents);
    bool Get7zipLibPath();
    bool InitializeDatabase();
    bool MakeTempDir(QString &dir);
    bool RemoveDirectoryFromArchive(QString compressedDirPath, QString archivePath, QString &m);
    bool UpdateMemoryFileToArchive(QString file, QString compressedFilePath, QString archivePath, QString &m);
    bool GetFileListingFromArchive(QString archivePath, QString subDir, QStringList &files, QString &m);

    QString workingDir;
    QString logfile;
    QStringList msgs; /* squirrel messages to be passed back through the squirrel library */
    QString log;
    QString logBuffer;
    QString packagePath;
    QString p7zipLibPath;

    FileMode fileMode;

    bool debug;
    bool debugSQL;
    bool overwritePackage;
    bool isValid;
    bool isOkToDelete;
    bool quickRead; /* set true to skip reading of the params.json files */
};

#endif // SQUIRREL_H
