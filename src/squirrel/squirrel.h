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
#include "squirrelMeasure.h"
#include "squirrelDrug.h"
#include "squirrelGroupAnalysis.h"
#include "squirrelDataDictionary.h"
#include "squirrelVersion.h"

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

    bool Read(QString filename, bool headerOnly, bool validateOnly=false);
    bool Write(bool writeLog);
    bool Validate();
    void Print();
    void SetFilename(QString p) { zipPath = p; }

    /* package JSON elements */
    QDateTime datetime;         /*!< datetime the package was created */
    QString description;        /*!< detailed description of the package */
    QString name;               /*!< name of the package */
    QString NiDBversion;        /*!< NiDB version that wrote this package */
    QString version;            /*!< squirrel version */
    QString format;             /*!< 'dir' or 'zip' */
    QString subjectDirFormat;   /*!< orig, seq */
    QString studyDirFormat;     /*!< orig, seq */
    QString seriesDirFormat;    /*!< orig, seq */
    QString dataFormat;         /*!< orig, anon, anonfull, nift3d, nifti3dgz, nifti4d, nifti4dgz */
    QString license;            /*!< a data usage license */
    QString readme;             /*!< a README */
    QString changes;            /*!< any changes since last package release */
    QString notes;              /*!< JSON string of notes (may contain JSON sub-elements of 'import', 'merge', 'export') */

    /* lib variables */
    QString filePath;           /*!< full path to the zip file */

    /* new, SQLite based functions */
    QList<squirrelExperiment> GetAllExperiments();
    QList<squirrelPipeline> GetAllPipelines();
    QList<squirrelSubject> GetAllSubjects();
    QList<squirrelStudy> GetStudies(int subjectRowID);
    QList<squirrelSeries> GetSeries(int studyRowID);
    QList<squirrelAnalysis> GetAnalyses(int studyRowID);
    QList<squirrelMeasure> GetMeasures(int subjectRowID);
    QList<squirrelDrug> GetDrugs(int subjectRowID);
    QList<squirrelGroupAnalysis> GetAllGroupAnalyses();
    QList<squirrelDataDictionary> GetAllDataDictionaries();

    /* get numbers of objects */
    qint64 GetNumFiles();
    int GetObjectCount(QString object);

    /* find objects */
    int FindSubject(QString id);
    int FindStudy(QString subjectID, int studyNum);
    int FindStudyByUID(QString studyUID);
    int FindSeries(QString subjectID, int studyNum, int seriesNum);
    int FindSeriesByUID(QString seriesUID);

    bool AddStagedFiles(QString objectType, int rowid, QStringList files, QString destDir="");

    /* requence the subject data */
    void ResequenceSubjects();
    void ResequenceStudies(int subjectRowID);
    void ResequenceSeries(int studyRowID);

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
    bool GetDebug() { return debug; }
    bool quiet=false;

    /* printing of information to console */
    void PrintPackage();
    void PrintSubjects(bool details=false);
    void PrintStudies(int subjectRowID, bool details=false);
    void PrintSeries(int studyRowID, bool details=false);
    void PrintExperiments(bool details=false);
    void PrintPipelines(bool details=false);
    void PrintGroupAnalyses(bool details=false);
    void PrintDataDictionary(bool details=false);

    QSqlDatabase db;

private:
    bool MakeTempDir(QString &dir);
    bool DatabaseConnect();
    bool InitializeDatabase();

    QString workingDir;
    QString logfile;
    QStringList msgs; /* squirrel messages to be passed back through the squirrel library */
    QString log;
    QString zipPath;

    bool debug;
    bool isValid;
    bool isOkToDelete;
};

#endif // SQUIRREL_H
