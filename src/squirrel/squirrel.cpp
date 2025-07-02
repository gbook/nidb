/* ------------------------------------------------------------------------------
  Squirrel squirrel.cpp
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

#include "squirrel.h"
#include "squirrelImageIO.h"
#include "utils.h"
#include "squirrel.sql.h"
#include "bit7z.hpp"
#include "bitarchivewriter.hpp"
#include "bitarchiveeditor.hpp"
#include "bitfileextractor.hpp"
#include "squirrelVersion.h"
#include "squirrelTypes.h"

/* ----- bit7z progress callbacks ----- */
qint64 totalbytes(0);
double blocksize(0.0);
qint64 lastupdate(0);

bool totalArchiveSizeCallback(qint64 val) {
    totalbytes = val;
    blocksize = (double)totalbytes/100.0;
    return true;
}

bool progressCallback(qint64 val) {
    if (val > (lastupdate+blocksize)) {
        double percent = ((double)val/(double)totalbytes)*100.0;
        utils::PrintProgress(percent/100.0);
        lastupdate = val;
    }
    return true;
}


/* ------------------------------------------------------------ */
/* ----- squirrel --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Constructor
 * @param dbg true for debug logging
 * @param q true to turn off all output
 */
squirrel::squirrel(bool dbg, bool q)
{
    DataFormat = "nifti4dgz";
    Datetime = QDateTime::currentDateTime();
    Description = "Squirrel package";
    PackageFormat = "squirrel";
    PackageName = "Squirrel package";
    SeriesDirFormat = "orig";
    SquirrelBuild = QString("%1.%2.%3").arg(UTIL_VERSION_MAJ).arg(UTIL_VERSION_MIN).arg(UTIL_BUILD_NUM);
    SquirrelVersion = QString("%1.%2").arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN);
    StudyDirFormat = "orig";
    SubjectDirFormat = "orig";

    debug = dbg;
    fileMode = FileMode::NewPackage;
    isOkToDelete = true;
    isValid = true;
    quickRead = true;
    quiet = q;
    writeLog = false;
    databaseUUID = QUuid::createUuid().toString(QUuid::WithoutBraces);
    Log(QString("Generated UUID [%1]").arg(databaseUUID));

    if (!DatabaseConnect()) {
        Log("Error connecting to database. Unable to initilize squirrel library");
        isValid = false;
    }

    if (!InitializeDatabase()) {
        Log("Error initializing database");
        isValid = false;
    }

    if (!Get7zipLibPath()) {
        Log("7-zip library not found. Unable to initilize squirrel library");
        utils::Print(QString("7-zip library not found. Unable to initilize squirrel library"));
        isValid = false;
    }

    Debug(QString("Created squirrel object."), __FUNCTION__);
    Debug("Squirrel is running in debug mode", __FUNCTION__);
}


/* ------------------------------------------------------------ */
/* ----- ~squirrel -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Destructor
 */
squirrel::~squirrel()
{
    if ((fileMode == NewPackage) && isValid && (workingDir.size() > 22)) {
        QString m;
        if (!utils::RemoveDir(workingDir, m))
            Log(QString("Error removing working directory [%1]. Message [%2]").arg(workingDir).arg(m));
    }
}


/* ---------------------------------------------------------- */
/* --------- Get7zipLibPath --------------------------------- */
/* ---------------------------------------------------------- */
bool squirrel::Get7zipLibPath() {
#ifdef Q_OS_WINDOWS
    if (QFile::exists("C:/Program Files/7-Zip/7z.dll")) {
        p7zipLibPath = "C:/Program Files/7-Zip/7z.dll";
        Debug("Found 7zip path C:/Program Files/7-Zip/7z.dll", __FUNCTION__);
        return true;
    }
#else
    if (QFile::exists("/usr/libexec/p7zip/7z.so")) {
        p7zipLibPath = "/usr/libexec/p7zip/7z.so";
        Debug("Found 7zip library path /usr/libexec/p7zip/7z.so", __FUNCTION__);
        return true;
    }
    else if (QFile::exists("/usr/libexec/p7zip/7za.so")) {
        p7zipLibPath = "/usr/libexec/p7zip/7za.so";
        Debug("Found 7zip library path /usr/libexec/p7zip/7za.so", __FUNCTION__);
        return true;
    }
    else if (QFile::exists("/usr/lib/7zip/7z.so")) {
        p7zipLibPath = "/usr/lib/7zip/7z.so";
        Debug("Found 7zip library path /usr/lib/7zip/7z.so", __FUNCTION__);
        return true;
    }
#endif

    return false;
}


/* ---------------------------------------------------------- */
/* --------- DatabaseConnect -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Connect to SQLite memory database
 * @return true if successful, false otherwise
 */
bool squirrel::DatabaseConnect() {

    db = QSqlDatabase::addDatabase("QSQLITE", databaseUUID);
    if (db.isValid()) {
        Log("Successfully initialized QSQLITE database");
    }
    else {
        Log(QString("Error initializing SQLite database (likely driver related) [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()));
        utils::Print(QString("Error initializing SQLite database (likely driver related) [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()));
        return false;
    }

    if (debug) {
        QFile::remove(QDir::tempPath() + "/" + databaseUUID + "-sqlite.db");
        db.setDatabaseName(QDir::tempPath() + "/" + databaseUUID + "-sqlite.db");
    }
    else {
        db.setDatabaseName(":memory:");
        Log("Set database name to :memory:");
    }

    if (db.open()) {
        Log(QString("Successfuly opened SQLite database [%1]").arg(db.databaseName()));
        return true;
    }
    else {
        Log(QString("Error opening SQLite database [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()));
        utils::Print(QString("Error opening SQLite database [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()));
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- InitializeDatabase ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Initialize the SQLite database by creating necessary tables
 * @return true if successul, false otherwise
 */
bool squirrel::InitializeDatabase() {

    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* NOTE - SQLite does not support multiple statements, so each table needs to be created individualy */

    q.prepare(tableAnalysis);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Analysis]"); utils::Print("Error creating table [Analysis]"); return false; }

    q.prepare(tableIntervention);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Intervention]"); utils::Print("Error creating table [Intervention]"); return false; }

    q.prepare(tableDataDictionary);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [DataDictionary]"); utils::Print("Error creating table [DataDictionary]"); return false; }

    q.prepare(tableDataDictionaryItem);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [DataDictionaryItem]"); utils::Print("Error creating table [DataDictionaryItem]"); return false; }

    q.prepare(tableExperiment);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Experiment]"); utils::Print("Error creating table [Experiment]"); return false; }

    q.prepare(tableGroupAnalysis);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [GroupAnalysis]"); utils::Print("Error creating table [GroupAnalysis]"); return false; }

    q.prepare(tableObservation);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Observation]"); utils::Print("Error creating table [Observation]"); return false; }

    q.prepare(tablePackage);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Package]"); utils::Print("Error creating table [Package]"); return false; }

    q.prepare(tableParams);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Params]"); utils::Print("Error creating table [Params]"); return false; }

    q.prepare(tablePipeline);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Pipeline]"); utils::Print("Error creating table [Pipeline]"); return false; }

    q.prepare(tablePipelineDataStep);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [PipelineDataStep]"); utils::Print("Error creating table [PipelineDataStep]"); return false; }

    q.prepare(tableSeries);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Series]"); utils::Print("Error creating table [Series]"); return false; }

    q.prepare(tableStudy);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Study]"); utils::Print("Error creating table [Study]"); return false; }

    q.prepare(tableSubject);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Subject]"); utils::Print("Error creating table [Subject]"); return false; }

    q.prepare(tableStagedFiles);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [StagedFiles]"); utils::Print("Error creating table [StagedFiles]"); return false; }

    q.prepare("PRAGMA journal_mode=WAL");
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error setting journal_mode=WAL"); utils::Print("Error setting journal_mode=WAL"); return false; }

    q.prepare("PRAGMA synchronous=NORMAL");
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error setting synchronous=NORMAL"); utils::Print("Error setting synchronous=NORMAL"); return false; }

    Debug("Successfully initialized database tables", __FUNCTION__);
    return true;
}


/* ------------------------------------------------------------ */
/* ----- GetPackagePath --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the package path, with extension
 * @return the full path to the package
 */
QString squirrel::GetPackagePath() {

    if (fileMode == NewPackage) {
        /* if it's a new package, make sure it has a .sqrl extension */
        if (!packagePath.endsWith(".sqrl", Qt::CaseInsensitive))
            packagePath += ".sqrl";
    }

    return packagePath;
}


/* ------------------------------------------------------------ */
/* ----- Read ------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Read a squirrel package. All parameters must be set first.
 * @return true if read succesful
 */
bool squirrel::Read() {

    Log(QString("Reading squirrel file [%1]").arg(GetPackagePath()));
    //utils::Print(QString("Reading squirrel file [%1]").arg(GetPackagePath()), __FUNCTION__);

    /* check if file exists */
    if (!utils::FileExists(GetPackagePath())) {
        Log(QString("File %1 does not exist").arg(GetPackagePath()));
        utils::Print(QString("File %1 does not exist").arg(GetPackagePath()));
        return false;
    }

    QString jsonstr;
    if (!ExtractArchiveFileToMemory(GetPackagePath(), "squirrel.json", jsonstr)) {
        Log(QString("Error reading squirrel package. Unable to find squirrel.json"));
        utils::Print(QString("Error reading squirrel package. Unable to find squirrel.json"));
        return false;
    }
    else {
        Debug(QString("Extracted package header [%1]").arg(utils::HumanReadableSize(jsonstr.size())), __FUNCTION__);
    }

    /* get the JSON document and root object */
    QJsonDocument d = QJsonDocument::fromJson(jsonstr.toUtf8());
    QJsonObject root = d.object();

    /* get the package info */
    QJsonObject pkgObj = root["package"].toObject();
    Changes = pkgObj["Changes"].toString();
    DataFormat = pkgObj["DataFormat"].toString();
    Datetime = utils::StringToDatetime(pkgObj["Datetime"].toString());
    Description = pkgObj["Description"].toString();
    License = pkgObj["License"].toString();
    Notes = pkgObj["Notes"].toString();
    PackageFormat = pkgObj["PackageFormat"].toString();
    PackageName = pkgObj["PackageName"].toString();
    PackageName = pkgObj["PackageName"].toString();
    Readme = pkgObj["Readme"].toString();
    SeriesDirFormat = pkgObj["SeriesDirectoryFormat"].toString();
    SquirrelBuild = pkgObj["SquirrelBuild"].toString();
    SquirrelVersion = pkgObj["SquirrelVersion"].toString();
    StudyDirFormat = pkgObj["StudyDirectoryFormat"].toString();
    SubjectDirFormat = pkgObj["SubjectDirectoryFormat"].toString();

    /* get the data object, and check for any subjects */
    QJsonArray jsonSubjects;
    qint64 numSubjects;
    if (root.contains("data")) {
        QJsonValue dataVal = root.value("data");
        QJsonObject dataObj = dataVal.toObject();
        jsonSubjects = dataObj["subjects"].toArray();
        numSubjects = jsonSubjects.size();
        Debug(QString("Found [%1] subjects").arg(numSubjects), __FUNCTION__);
    }
    else if (root.contains("subjects")) {
        jsonSubjects = root["subjects"].toArray();
        Log(QString("NOTICE: Found [%1] subjects in the root of the JSON. (This is a slightly malformed squirrel file, but I'll accept it)").arg(jsonSubjects.size()));
    }
    else {
        Log("root JSON object does not contain 'data' or 'subjects'");
    }

    Debug(QString("TotalFileCount: [%1]").arg(root["TotalFileCount"].toInt()), __FUNCTION__);
    Debug(QString("TotalSize: [%1]").arg(root["TotalSize"].toInt()), __FUNCTION__);

    /* loop through and read any subjects */
    utils::Print(QString("Reading %1 subjects...").arg(jsonSubjects.size()));
    qint64 i(0);
    for (auto a : jsonSubjects) {
        i++;
        Debug(QString("Reading subject %1 of %2 - %3").arg(i).arg(jsonSubjects.size()).arg(QDateTime::currentDateTime().toString("yyyy/MM/dd hh:mm:ss.zzz")));
        //utils::Print(QString("Reading subject %1 of %2 - %3").arg(i).arg(jsonSubjects.size()).arg(QDateTime::currentDateTime().toString("yyyy/MM/dd hh:mm:ss.zzz")), __FUNCTION__);
        utils::PrintProgress((double)i/(double)jsonSubjects.size());

        QJsonObject jsonSubject = a.toObject();

        squirrelSubject sqrlSubject(databaseUUID);
        sqrlSubject.ID = jsonSubject["SubjectID"].toString();
        sqrlSubject.AlternateIDs = jsonSubject["AlternateIDs"].toVariant().toStringList();
        sqrlSubject.GUID = jsonSubject["GUID"].toString();
        sqrlSubject.DateOfBirth = QDate::fromString(jsonSubject["DateOfBirth"].toString(), "yyyy-MM-dd");
        sqrlSubject.Sex = jsonSubject["Sex"].toString();
        sqrlSubject.Gender = jsonSubject["Gender"].toString();
        sqrlSubject.Ethnicity1 = jsonSubject["Ethnicity1"].toString();
        sqrlSubject.Ethnicity2 = jsonSubject["Ethnicity2"].toString();
        sqrlSubject.Notes = jsonSubject["Notes"].toString();
        sqrlSubject.Store();
        qint64 subjectRowID = sqrlSubject.GetObjectID();

        //Log(QString("Reading subject [%1]").arg(sqrlSubject.ID), __FUNCTION__);

        /* loop through and read all studies */
        QJsonArray jsonStudies = jsonSubject["studies"].toArray();
        for (auto b : jsonStudies) {
            QJsonObject jsonStudy = b.toObject();
            squirrelStudy sqrlStudy(databaseUUID);

            sqrlStudy.AgeAtStudy = jsonStudy["AgeAtStudy"].toDouble();
            sqrlStudy.DateTime = utils::StringToDatetime(jsonStudy["StudyDatetime"].toString());
            sqrlStudy.DayNumber = jsonStudy["DayNumber"].toInt();
            sqrlStudy.Description = jsonStudy["Description"].toString();
            sqrlStudy.Equipment = jsonStudy["Equipment"].toString();
            sqrlStudy.Height = jsonStudy["Height"].toDouble();
            sqrlStudy.Modality = jsonStudy["Modality"].toString();
            sqrlStudy.Notes = jsonStudy["Notes"].toString();
            sqrlStudy.StudyNumber = jsonStudy["StudyNumber"].toInt();
            sqrlStudy.StudyUID = jsonStudy["StudyUID"].toString();
            sqrlStudy.TimePoint = jsonStudy["TimePoint"].toInt();
            sqrlStudy.VisitType = jsonStudy["VisitType"].toString();
            sqrlStudy.Weight = jsonStudy["Weight"].toDouble();
            sqrlStudy.subjectRowID = subjectRowID;
            sqrlStudy.Store();
            qint64 studyRowID = sqrlStudy.GetObjectID();

            Debug(QString("Reading study [%1][%2]").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber), __FUNCTION__);

            /* loop through and read all series */
            QJsonArray jsonSeries = jsonStudy["series"].toArray();
            for (auto c : jsonSeries) {
                QJsonObject jsonSeries = c.toObject();
                squirrelSeries sqrlSeries(databaseUUID);

                sqrlSeries.BIDSEntity = jsonSeries["BIDSEntity"].toString();
                sqrlSeries.BIDSPhaseEncodingDirection = jsonSeries["BIDSPhaseEncodingDirection"].toString();
                sqrlSeries.BIDSRun = jsonSeries["BIDSRun"].toString();
                sqrlSeries.BIDSSuffix = jsonSeries["BIDSSuffix"].toString();
                sqrlSeries.BIDSTask = jsonSeries["BIDSTask"].toString();
                sqrlSeries.BehavioralFileCount = jsonSeries["BehavioralFileCount"].toInteger();
                sqrlSeries.BehavioralSize = jsonSeries["BehavioralSize"].toInteger();
                //sqrlSeries.DateTime = utils::StringToDatetime(jsonSeries["SeriesDatetime"].toString());
                sqrlSeries.DateTime = QDateTime::fromString(jsonSeries["SeriesDatetime"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlSeries.Description = jsonSeries["Description"].toString();
                //sqrlSeries.FileCount = jsonSeries["FileCount"].toInteger();
                sqrlSeries.Protocol = jsonSeries["Protocol"].toString();
                sqrlSeries.SeriesNumber = jsonSeries["SeriesNumber"].toInteger();
                sqrlSeries.SeriesUID = jsonSeries["SeriesUID"].toString();
                sqrlSeries.Size = jsonSeries["Size"].toInteger();
                sqrlSeries.studyRowID = studyRowID;

                Debug(QString("Reading series [%1][%2][%3]").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber), __FUNCTION__);

                if (!quickRead) {
                    /* read any params from the data/Subject/Study/Series/params.json file */
                    QString parms;
                    QString paramsfilepath;
                    #ifdef Q_OS_WINDOWS
                        paramsfilepath = QString("data\\%1\\%2\\%3\\params.json").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber);
                    #else
                        paramsfilepath = QString("data/%1/%2/%3/params.json").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber);
                    #endif
                    if (ExtractArchiveFileToMemory(GetPackagePath(), paramsfilepath, parms)) {
                        sqrlSeries.params = ReadParamsFile(parms);
                        Debug(QString("Read params file [%1]. series.params contains [%2] items").arg(paramsfilepath).arg(sqrlSeries.params.size()));
                    }
                    else {
                        Log("Unable to read params file [" + paramsfilepath + "]");
                    }

                    /* get file listing */
                    QString seriesPath;
                    #ifdef Q_OS_WINDOWS
                        seriesPath = QString("data\\%1\\%2\\%3").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber);
                    #else
                        seriesPath = QString("data/%1/%2/%3").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber);
                    #endif

                    QStringList files;
                    QString m;
                    GetArchiveFileListing(GetPackagePath(), seriesPath, files, m);
                    Debug(QString("archiveSeriesPath [%1] found [%2] files [%3]").arg(seriesPath).arg(files.size()).arg(files.join(",")));
                    sqrlSeries.files = files;
                    sqrlSeries.FileCount = files.size();
                }

                sqrlSeries.Store();
            }

            /* loop through and read all analyses */
            QJsonArray jsonAnalyses = jsonStudy["analyses"].toArray();
            for (auto d : jsonAnalyses) {
                QJsonObject jsonAnalysis = d.toObject();
                squirrelAnalysis sqrlAnalysis(databaseUUID);
                sqrlAnalysis.DateClusterEnd = utils::StringToDatetime(jsonAnalysis["DateClusterEnd"].toString());
                sqrlAnalysis.DateClusterStart = utils::StringToDatetime(jsonAnalysis["DateClusterStart"].toString());
                sqrlAnalysis.DateStart = utils::StringToDatetime(jsonAnalysis["DateEnd"].toString());
                sqrlAnalysis.DateStart = utils::StringToDatetime(jsonAnalysis["DateStart"].toString());
                sqrlAnalysis.Hostname = jsonAnalysis["Hostname"].toString();
                sqrlAnalysis.LastMessage = jsonAnalysis["StatusMessage"].toString();
                sqrlAnalysis.PipelineName = jsonAnalysis["PipelineName"].toString();
                sqrlAnalysis.PipelineVersion = jsonAnalysis["PipelineVersion"].toInt();
                sqrlAnalysis.RunTime = jsonAnalysis["RunTime"].toInteger();
                sqrlAnalysis.SeriesCount = jsonAnalysis["SeriesCount"].toInt();
                sqrlAnalysis.SetupTime = jsonAnalysis["RunTime"].toInteger();
                sqrlAnalysis.Size = jsonAnalysis["Size"].toInteger();
                sqrlAnalysis.Status = jsonAnalysis["Status"].toString();
                sqrlAnalysis.Successful = jsonAnalysis["Successful"].toBool();
                sqrlAnalysis.studyRowID = studyRowID;
                sqrlAnalysis.Store();

                Debug(QString("Added analysis [%1]").arg(sqrlAnalysis.PipelineName), __FUNCTION__);
            }
        }

        /* read all observations */
        QSqlQuery q1(QSqlDatabase::database(databaseUUID)); /* start a transaction to slightly improve SQL insert performance */
        q1.prepare("begin transaction");
        utils::SQLQuery(q1, __FUNCTION__, __FILE__, __LINE__);

        QJsonArray jsonObservations = jsonSubject["observations"].toArray();
        Debug(QString("Reading [%1] observations").arg(jsonObservations.size()), __FUNCTION__);
        for (auto e : jsonObservations) {

            QJsonObject jsonObservation = e.toObject();
            squirrelObservation sqrlObservation(databaseUUID);
            sqrlObservation.DateEnd = utils::StringToDatetime(jsonObservation["DateEnd"].toString());
            sqrlObservation.DateStart = utils::StringToDatetime(jsonObservation["DateStart"].toString());
            sqrlObservation.DateRecordCreate = utils::StringToDatetime(jsonObservation["DateRecordCreate"].toString());
            sqrlObservation.DateRecordEntry = utils::StringToDatetime(jsonObservation["DateRecordEntry"].toString());
            sqrlObservation.DateRecordModify = utils::StringToDatetime(jsonObservation["DateRecordModify"].toString());
            sqrlObservation.Description = jsonObservation["Description"].toString();
            sqrlObservation.Duration = jsonObservation["Duration"].toDouble();
            sqrlObservation.InstrumentName = jsonObservation["InstrumentName"].toString();
            sqrlObservation.ObservationName = jsonObservation["ObservationName"].toString();
            sqrlObservation.Notes = jsonObservation["Notes"].toString();
            sqrlObservation.Rater = jsonObservation["Rater"].toString();
            sqrlObservation.Value = jsonObservation["Value"].toString();
            sqrlObservation.subjectRowID = subjectRowID;
            sqrlObservation.Store();
        }

        q1.prepare("commit");
        utils::SQLQuery(q1, __FUNCTION__, __FILE__, __LINE__);

        /* read all Interventions */
        QJsonArray jsonInterventions = jsonSubject["Interventions"].toArray();
        Debug(QString("Reading [%1] Interventions").arg(jsonInterventions.size()), __FUNCTION__);
        for (auto f : jsonInterventions) {
            QJsonObject jsonIntervention = f.toObject();
            squirrelIntervention sqrlIntervention(databaseUUID);
            sqrlIntervention.DateEnd = utils::StringToDatetime(jsonIntervention["DateEnd"].toString());
            sqrlIntervention.DateRecordEntry = utils::StringToDatetime(jsonIntervention["DateRecordEntry"].toString());
            sqrlIntervention.DateStart = utils::StringToDatetime(jsonIntervention["DateStart"].toString());
            sqrlIntervention.DoseAmount = jsonIntervention["DoseAmount"].toDouble();
            sqrlIntervention.DoseFrequency = jsonIntervention["DoseFrequency"].toString();
            sqrlIntervention.DoseKey = jsonIntervention["DoseKey"].toString();
            sqrlIntervention.DoseString = jsonIntervention["DoseString"].toString();
            sqrlIntervention.DoseUnit = jsonIntervention["DoseUnit"].toString();
            sqrlIntervention.InterventionClass = jsonIntervention["InterventionClass"].toString();
            sqrlIntervention.InterventionName = jsonIntervention["InterventionName"].toString();
            sqrlIntervention.Notes = jsonIntervention["Notes"].toString();
            sqrlIntervention.Rater = jsonIntervention["Rater"].toString();
            sqrlIntervention.AdministrationRoute = jsonIntervention["AdministrationRoute"].toString();
            sqrlIntervention.subjectRowID = subjectRowID;
            sqrlIntervention.Store();
        }
    }

    /* read all experiments */
    QJsonArray jsonExperiments;
    jsonExperiments = root["experiments"].toArray();
    Debug(QString("Reading [%1] experiments").arg(jsonExperiments.size()), __FUNCTION__);
    for (auto g : jsonExperiments) {
        QJsonObject jsonExperiment = g.toObject();
        squirrelExperiment sqrlExperiment(databaseUUID);

        sqrlExperiment.ExperimentName = jsonExperiment["ExperimentName"].toString();
        sqrlExperiment.FileCount = jsonExperiment["FileCount"].toInt();
        sqrlExperiment.Size = jsonExperiment["Size"].toInt();
        sqrlExperiment.Store();
    }

    /* read all pipelines */
    QJsonArray jsonPipelines;
    jsonPipelines = root["pipelines"].toArray();
    Debug(QString("Reading [%1] pipelines").arg(jsonPipelines.size()), __FUNCTION__);
    for (auto v : jsonPipelines) {
        QJsonObject jsonPipeline = v.toObject();
        squirrelPipeline sqrlPipeline(databaseUUID);

        sqrlPipeline.ClusterMaxWallTime = jsonPipeline["ClusterMaxWallTime"].toInt();
        sqrlPipeline.ClusterMemory = jsonPipeline["ClusterMemory"].toInt();
        sqrlPipeline.ClusterNumberCores = jsonPipeline["ClusterNumberCores"].toInt();
        sqrlPipeline.ClusterQueue = jsonPipeline["ClusterQueue"].toString();
        sqrlPipeline.ClusterSubmitHost = jsonPipeline["ClusterSubmitHost"].toString();
        sqrlPipeline.ClusterType = jsonPipeline["ClusterType"].toString();
        sqrlPipeline.ClusterUser = jsonPipeline["ClusterUser"].toString();
        sqrlPipeline.CreateDate = utils::StringToDatetime(jsonPipeline["CreateDate"].toString());
        sqrlPipeline.DataCopyMethod = jsonPipeline["DataCopyMethod"].toString();
        sqrlPipeline.DependencyDirectory = jsonPipeline["DependencyDirectory"].toString();
        sqrlPipeline.DependencyLevel = jsonPipeline["DependencyLevel"].toString();
        sqrlPipeline.DependencyLinkType = jsonPipeline["DependencyLinkType"].toString();
        sqrlPipeline.Description = jsonPipeline["Description"].toString();
        sqrlPipeline.Directory = jsonPipeline["Directory"].toString();
        sqrlPipeline.DirectoryStructure = jsonPipeline["DirectoryStructure"].toString();
        sqrlPipeline.Group = jsonPipeline["Group"].toString();
        sqrlPipeline.GroupType = jsonPipeline["GroupType"].toString();
        sqrlPipeline.Level = jsonPipeline["Level"].toInt();
        sqrlPipeline.Notes = jsonPipeline["Notes"].toString();
        sqrlPipeline.NumberConcurrentAnalyses = jsonPipeline["NumberConcurrentAnalyses"].toInt();
        sqrlPipeline.ParentPipelines = jsonPipeline["ParentPipelines"].toString().split(",");
        sqrlPipeline.PipelineName = jsonPipeline["PipelineName"].toString();
        sqrlPipeline.PrimaryScript = jsonPipeline["PrimaryScript"].toString();
        sqrlPipeline.ResultScript = jsonPipeline["ResultScript"].toString();
        sqrlPipeline.SecondaryScript = jsonPipeline["SecondaryScript"].toString();
        sqrlPipeline.SubmitDelay = jsonPipeline["SubmitDelay"].toInt();
        sqrlPipeline.TempDirectory = jsonPipeline["TempDir"].toString();
        sqrlPipeline.Version = jsonPipeline["Version"].toInt();
        sqrlPipeline.flags.UseProfile = jsonPipeline["UseProfile"].toBool();
        sqrlPipeline.flags.UseTempDirectory = jsonPipeline["UseTempDirectory"].toBool();

        QJsonArray jsonCompleteFiles;
        jsonCompleteFiles = jsonPipeline["CompleteFiles"].toArray();
        for (auto v : jsonCompleteFiles) {
            sqrlPipeline.CompleteFiles.append(v.toString());
        }

        /* read the pipeline data steps */
        QJsonArray jsonDataSteps;
        jsonDataSteps = jsonPipeline["dataSteps"].toArray();
        for (auto v : jsonDataSteps) {
            QJsonObject jsonDataStep = v.toObject();
            dataStep ds;
            ds.AssociationType = jsonDataStep["AssociationType"].toString();
            ds.BehavioralDirectory = jsonDataStep["BehavioralDirectory"].toString();
            ds.BehavioralFormat = jsonDataStep["BehavioralFormat"].toString();
            ds.DataFormat = jsonDataStep["DataFormat"].toString();
            ds.ImageType = jsonDataStep["ImageType"].toString();
            ds.Datalevel = jsonDataStep["DataLevel"].toString();
            ds.Location = jsonDataStep["Location"].toString();
            ds.Modality = jsonDataStep["Modality"].toString();
            ds.NumberBOLDreps = jsonDataStep["NumberBOLDreps"].toString();
            ds.NumberImagesCriteria = jsonDataStep["NumberImagesCriteria"].toString();
            ds.Order = jsonDataStep["Order"].toInt();
            ds.Protocol = jsonDataStep["Protocol"].toString();
            ds.SeriesCriteria = jsonDataStep["SeriesCriteria"].toString();
            ds.Protocol = jsonDataStep["Protocol"].toString();
            ds.flags.Enabled = jsonDataStep["Enabled"].toBool();
            ds.flags.Optional = jsonDataStep["Optional"].toBool();
            ds.flags.Gzip = jsonDataStep["Gzip"].toBool();
            ds.flags.UsePhaseDirectory = jsonDataStep["UsePhaseDirectory"].toBool();
            ds.flags.UseSeries = jsonDataStep["UseSeries"].toBool();
            ds.flags.PreserveSeries = jsonDataStep["PreserveSeries"].toBool();
            ds.flags.PrimaryProtocol = jsonDataStep["PrimaryProtocol"].toBool();
            sqrlPipeline.dataSteps.append(ds);
        }
        sqrlPipeline.Store();
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Write ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Write a squirrel package. All parameters should be set first
 * @param writeLog true if logfile should be written
 * @return true if successfuly written, false otherwise
 */
bool squirrel::Write() {

    /* create the log file */
    QFileInfo finfo(GetPackagePath());
    logfile = QString(finfo.absolutePath() + "/squirrel-" + utils::CreateLogDate() + ".log");

    //PrintPackage();

    if (fileMode == NewPackage) {
        MakeTempDir(workingDir);
        Log(QString("Writing NEW squirrel package [%1]").arg(GetPackagePath()));
        Debug(QString("Writing NEW squirrel package. Working directory [%1]  packagePath [%2]").arg(workingDir).arg(GetPackagePath()));
    }
    else {
        Log(QString("Updating existing squirrel package [%1]").arg(GetPackagePath()));
    }

    pairList stagedFiles;

    /* ----- 1) Write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetSubjectList();
    foreach (squirrelSubject subject, subjects) {
        qint64 subjectRowID = subject.GetObjectID();
        Debug(QString("Writing subject [%1] to virtualPath [%2]").arg(subject.ID).arg(subject.VirtualPath()));

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudyList(subjectRowID);
        foreach (squirrelStudy study, studies) {
            qint64 studyRowID = study.GetObjectID();
            Debug(QString("Writing study [%1] to virtualPath [%2]").arg(study.StudyNumber).arg(study.VirtualPath()));

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeriesList(studyRowID);
            Debug(QString("Writing [%1] series for [%2][%3]").arg(serieses.size()).arg(subject.ID).arg(study.StudyNumber));
            foreach (squirrelSeries series, serieses) {
                QString m;
                QString seriesPath;
                #ifdef Q_OS_WINDOWS
                    seriesPath = QString("%1\\%2").arg(workingDir).arg(series.VirtualPath());
                #else
                    seriesPath = QString("%1/%2").arg(workingDir).arg(series.VirtualPath());
                #endif

                if (fileMode == FileMode::NewPackage) {
                    utils::MakePath(seriesPath,m);
                    Log(QString("Preparing series [%1-%2-%3]...").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber));
                    Debug(QString("Staging [%1-%2-%3] to tmpdir [%4]. Data format [%5]").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber).arg(seriesPath).arg(DataFormat));

                    /* orig vs other formats */
                    if (DataFormat == "orig") {
                        Debug(QString("Export data format is 'orig'. Copying [%1] files...").arg(series.stagedFiles.size()), __FUNCTION__);
                        /* copy all of the series files to the temp directory */
                        foreach (QString f, series.stagedFiles) {
                            if (utils::CopyFileToDir(f, seriesPath))
                                Debug(QString("  ... copying original files from %1 to %2").arg(f).arg(seriesPath));
                            else
                                Log(QString("  ERROR copying original files from %1 to %2").arg(f).arg(seriesPath));
                        }
                    }
                    else if (study.Modality.toUpper() != "MR") {
                        Debug(QString("Study modality is [%1]. Copying files...").arg(study.Modality.toUpper()), __FUNCTION__);
                        /* copy all of the series files to the temp directory */
                        foreach (QString f, series.stagedFiles) {
                            if (utils::CopyFileToDir(f, seriesPath))
                                Debug(QString("  ... copying original files from %1 to %2").arg(f).arg(seriesPath));
                            else
                                Log(QString("  ERROR copying original files from %1 to %2").arg(f).arg(seriesPath));
                        }
                    }
                    else if ((DataFormat == "anon") || (DataFormat == "anonfull")) {
                        /* create temp directory for the anonymization */
                        QString td;
                        if (MakeTempDir(td)) {
                            /* copy all files to temp directory */
                            QString systemstring;
                            foreach (QString f, series.stagedFiles) {
                                if (utils::CopyFileToDir(f, td))
                                    Debug(QString("  ... copying original files from %1 to %2").arg(f).arg(td));
                                else
                                    Log(QString("  ERROR copying original files from %1 to %2").arg(f).arg(td));
                            }

                            /* anonymize the directory */
                            squirrelImageIO io;
                            QString m;
                            if (DataFormat == "anon")
                                io.AnonymizeDir(td,1,"Anonymized","Anonymized",m);
                            else
                                io.AnonymizeDir(td,2,"Anonymized","Anonymized",m);

                            /* move the anonymized files to the staging area */
                            systemstring = QString("mv %1/* %2/").arg(td).arg(seriesPath);
                            Log(QString("  ... anonymizing DICOM files from %1 to %2").arg(td).arg(seriesPath));
                            Debug(utils::SystemCommand(systemstring), __FUNCTION__);

                            /* delete temp directory */
                            DeleteTempDir(td);
                        }
                        else
                            Log("Error creating temp directory for DICOM anonymization");
                    }
                    else if (DataFormat.contains("nifti")) {
                        int numConv(0), numRename(0);
                        bool gzip;
                        if (DataFormat.contains("gz"))
                            gzip = true;
                        else
                            gzip = false;

                        /* get path of first file to be converted */
                        if (series.stagedFiles.size() > 0) {
                            Log(QString(" ... converting %1 files to nifti").arg(series.stagedFiles.size()));

                            QFileInfo f(series.stagedFiles[0]);
                            QString origSeriesPath = f.absoluteDir().absolutePath();
                            squirrelImageIO io;
                            QString m3;
                            if (io.ConvertDicom(DataFormat, origSeriesPath, seriesPath, QDir::currentPath(), gzip, utils::CleanString(subject.ID), QString("%1").arg(study.StudyNumber), QString("%1").arg(series.SeriesNumber), "dicom", numConv, numRename, m3))
                                Debug(QString("ConvertDicom() returned [%1]").arg(m3), __FUNCTION__);
                            else
                                Log(QString("ConvertDicom() failed. Returned [%1]").arg(m3));
                        }
                        else {
                            Debug(QString("Variable squirrelSeries.stagedFiles is empty. No files to convert to Nifti"));
                        }
                    }
                    else
                        Log(QString("DataFormat [%1] not recognized").arg(DataFormat));

                    /* get the number of files and size of the series */
                    qint64 c(0), b(0);
                    utils::GetDirSizeAndFileCount(seriesPath, c, b, false);
                    series.FileCount = c;
                    series.Size = b;
                    series.Store();

                    /* write the series .json file, containing the dicom header params */
                    QString paramFilePath = QString("%1/params.json").arg(seriesPath);
                    QByteArray j = QJsonDocument(series.ParamsToJSON()).toJson();
                    if (!utils::WriteTextFile(paramFilePath, j))
                        Log("Error writing [" + paramFilePath + "]");
                }
            }
        }
    }

    /* ----- 2) write .json file ----- */
    Debug("Creating header file...");
    /* create JSON object */
    QJsonObject root;

    QJsonObject pkgInfo;
    pkgInfo["Changes"] = Changes;
    pkgInfo["DataFormat"] = DataFormat;
    pkgInfo["Datetime"] = utils::CreateCurrentDateTime(2);
    pkgInfo["Description"] = Description;
    pkgInfo["License"] = License;
    pkgInfo["Notes"] = Notes;
    pkgInfo["PackageFormat"] = DataFormat;
    pkgInfo["PackageName"] = PackageName;
    pkgInfo["Readme"] = Readme;
    pkgInfo["SeriesDirectoryFormat"] = SeriesDirFormat;
    pkgInfo["SquirrelBuild"] = SquirrelBuild;
    pkgInfo["SquirrelVersion"] = SquirrelVersion;
    pkgInfo["StudyDirectoryFormat"] = StudyDirFormat;
    pkgInfo["SubjectDirectoryFormat"] = SubjectDirFormat;

    root["package"] = pkgInfo;

    QJsonObject data;
    QJsonArray JSONsubjects;

    /* add subjects to JSON */
    QList<squirrelSubject> subjectses = GetSubjectList();
    Log(QString("Adding %1 subjects").arg(subjectses.size()));
    foreach (squirrelSubject subject, subjectses) {
        JSONsubjects.append(subject.ToJSON());
    }

    /* add group-analyses */
    QList <squirrelGroupAnalysis> groupAnalyses = GetGroupAnalysisList();
    if (groupAnalyses.size() > 0) {
        Log(QString("Adding %1 group-analyses").arg(groupAnalyses.size()));
        QJsonArray JSONgroupanalyses;
        foreach (squirrelGroupAnalysis g, groupAnalyses) {
            if (g.Get()) {
                JSONgroupanalyses.append(g.ToJSON());
                stagedFiles += g.GetStagedFileList();
                Log(QString("Added group-analysis [%1]").arg(g.GroupAnalysisName));
            }
        }
        data["GroupAnalysisCount"] = JSONgroupanalyses.size();
        data["group-analysis"] = JSONgroupanalyses;
    }

    data["SubjectCount"] = JSONsubjects.size();
    data["subjects"] = JSONsubjects;
    root["data"] = data;

    /* add pipelines */
    QList <squirrelPipeline> pipelines = GetPipelineList();
    if (pipelines.size() > 0) {
        Log(QString("Adding %1 pipelines").arg(pipelines.size()));
        QJsonArray JSONpipelines;
        foreach (squirrelPipeline p, pipelines) {
            if (p.Get()) {
                JSONpipelines.append(p.ToJSON(workingDir));
                stagedFiles += p.GetStagedFileList();
                Log(QString("Added pipeline [%1]").arg(p.PipelineName));
            }
        }
        root["PipelineCount"] = JSONpipelines.size();
        root["pipelines"] = JSONpipelines;
    }

    /* add experiments */
    QList <squirrelExperiment> exps = GetExperimentList();
    if (exps.size() > 0) {
        Log(QString("Adding %1 experiments").arg(exps.size()));
        QJsonArray JSONexperiments;
        foreach (squirrelExperiment e, exps) {
            if (e.Get()) {
                JSONexperiments.append(e.ToJSON());
                stagedFiles += e.GetStagedFileList();
                Log(QString("Added experiment [%1]").arg(e.ExperimentName));
            }
        }
        root["ExperimentCount"] = JSONexperiments.size();
        root["experiments"] = JSONexperiments;
    }

    /* add data-dictionary */
    QList <squirrelDataDictionary> dicts = GetDataDictionaryList();
    if (dicts.size() > 0) {
        Log(QString("Adding %1 data-dictionaries").arg(dicts.size()));
        QJsonArray JSONdataDictionaries;
        foreach (squirrelDataDictionary d, dicts) {
            if (d.Get()) {
                JSONdataDictionaries.append(d.ToJSON());
                stagedFiles += d.GetStagedFileList();
                Log("Added data-dictionary");
            }
        }
        root["DataDictionaryCount"] = JSONdataDictionaries.size();
        root["data-dictionaries"] = JSONdataDictionaries;
    }
    root["TotalSize"] = GetUnzipSize();
    root["TotalFileCount"] = GetFileCount();

    QString j = QJsonDocument(root).toJson();

    /* write the final .json file */
    if (fileMode == NewPackage) {

        /* copy in all files from the staged files list */
        Debug(QString("stagedFiles size is [%1]").arg(stagedFiles.size()));
        for (int i=0; i<stagedFiles.size(); i++) {
            Debug(QString("[%1] , [%2]").arg(stagedFiles.at(i).first).arg(stagedFiles.at(i).second), __FUNCTION__);
        }

        for (int i=0; i<stagedFiles.size(); i++) {
            QStringPair file = stagedFiles.at(i);

            QString sourcePath = file.first;
            QFileInfo fi(file.first);
            QString fname = fi.fileName();

            QString destPath = workingDir + "/" + file.second + "/" + fname;
            QString destDir = workingDir + "/" + file.second;
            QString m;
            if (!utils::MakePath(destDir,m))
                Log(QString("Error creating directory [%1] - message [%2]").arg(destDir).arg(m));
            else
                Debug(QString("Successfully created directory [%1] - message [%2]").arg(destDir).arg(m), __FUNCTION__);

            Debug(QString("Copying [%1] to [%2]").arg(sourcePath).arg(destPath), __FUNCTION__);
            if (!QFile::copy(sourcePath, destPath))
                Log(QString("Error copying [%1] to [%2]").arg(sourcePath).arg(destPath));
        }

        //Log("Zipping the archive from a temp directory");

        /* write the .json file to the temp dir */
        QString jsonFilePath = workingDir + "/squirrel.json";
        if (!utils::WriteTextFile(jsonFilePath, j))
            Log("Error writing [" + jsonFilePath + "]");

        QString m;
        Log("Writing package...");
        if (CompressDirectoryToArchive(workingDir, GetPackagePath(), m)) {
            QFileInfo fi(GetPackagePath());
            qint64 zipSize = fi.size();
            Log(QString("Finished writing package [%1]. Size is [%2] bytes").arg(GetPackagePath()).arg(zipSize));

            /* delete the squirrel temp dir */
            DeleteTempDir(workingDir);
        }
        else {
            Log("Error creating zip file [" + GetPackagePath() + "]  message [" + m + "]");
            return false;
        }
    }
    else {

        /* update all files from the staged files list */
        QStringList diskPaths, archivePaths;
        for (int i=0; i<stagedFiles.size(); i++) {
            QStringPair file = stagedFiles.at(i);
            QString source = file.first;
            QString dest = workingDir + "/" + file.first;
            diskPaths.append(source);
            archivePaths.append(dest);
        }
        Log("Adding/updating files in existing package");
        QString m;
        if (!AddFilesToArchive(diskPaths, archivePaths, GetPackagePath(), m))
            Log("Error [" + m + "] adding file(s) to archive");

        /* update the package in place with the new .json file */
        Log("Updating existing package");
        if (!UpdateMemoryFileToArchive(j, "squirrel.json", GetPackagePath(), m)) {
            Log("Error [" + m + "] compressing memory file to archive");
        }
    }

    /* write the log file */
    if (writeLog)
        utils::WriteTextFile(logfile, log);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- WriteUpdate ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Update the squirrel package *header only*, in place. All parameters should be set first
 * @return true if successfuly written, false otherwise
 */
bool squirrel::WriteUpdate() {
    QString m;

    /* create the log file */
    QFileInfo finfo(GetPackagePath());
    logfile = QString(finfo.absolutePath() + "/squirrel-" + utils::CreateLogDate() + ".log");

    /* create JSON object */
    QJsonObject root;

    QJsonObject pkgInfo;
    pkgInfo["Changes"] = Changes;
    pkgInfo["DataFormat"] = DataFormat;
    pkgInfo["Datetime"] = utils::CreateCurrentDateTime(2);
    pkgInfo["Description"] = Description;
    pkgInfo["License"] = License;
    pkgInfo["Notes"] = Notes;
    pkgInfo["PackageFormat"] = DataFormat;
    pkgInfo["PackageName"] = PackageName;
    pkgInfo["Readme"] = Readme;
    pkgInfo["SeriesDirectoryFormat"] = SeriesDirFormat;
    pkgInfo["SquirrelBuild"] = SquirrelBuild;
    pkgInfo["SquirrelVersion"] = SquirrelVersion;
    pkgInfo["StudyDirectoryFormat"] = StudyDirFormat;
    pkgInfo["SubjectDirectoryFormat"] = SubjectDirFormat;

    root["package"] = pkgInfo;

    QJsonObject data;
    QJsonArray JSONsubjects;

    /* add subjects to JSON */
    QList<squirrelSubject> subjectses = GetSubjectList();
    Log(QString("Adding %1 subjects").arg(subjectses.size()));
    foreach (squirrelSubject subject, subjectses) {
        JSONsubjects.append(subject.ToJSON());
        Log("Added subject [" + subject.ID + "]");
    }

    /* add group-analyses */
    QList <squirrelGroupAnalysis> groupAnalyses = GetGroupAnalysisList();
    if (groupAnalyses.size() > 0) {
        Log(QString("Adding %1 group-analyses").arg(groupAnalyses.size()));
        QJsonArray JSONgroupanalyses;
        foreach (squirrelGroupAnalysis g, groupAnalyses) {
            if (g.Get()) {
                JSONgroupanalyses.append(g.ToJSON());
                Log(QString("Added group-analysis [%1]").arg(g.GroupAnalysisName));
            }
        }
        data["GroupAnalysisCount"] = JSONgroupanalyses.size();
        data["group-analysis"] = JSONgroupanalyses;
    }

    data["SubjectCount"] = JSONsubjects.size();
    data["subjects"] = JSONsubjects;
    root["data"] = data;

    /* add pipelines */
    QList <squirrelPipeline> pipelines = GetPipelineList();
    if (pipelines.size() > 0) {
        Log(QString("Adding %1 pipelines").arg(pipelines.size()));
        QJsonArray JSONpipelines;
        foreach (squirrelPipeline p, pipelines) {
            if (p.Get()) {
                JSONpipelines.append(p.ToJSON(workingDir));
                Log(QString("Added pipeline [%1]").arg(p.PipelineName));
            }
        }
        root["PipelineCount"] = JSONpipelines.size();
        root["pipelines"] = JSONpipelines;
    }

    /* add experiments */
    QList <squirrelExperiment> exps = GetExperimentList();
    if (exps.size() > 0) {
        Log(QString("Adding %1 experiments").arg(exps.size()));
        QJsonArray JSONexperiments;
        foreach (squirrelExperiment e, exps) {
            if (e.Get()) {
                JSONexperiments.append(e.ToJSON());
                Log(QString("Added experiment [%1]").arg(e.ExperimentName));
            }
        }
        root["ExperimentCount"] = JSONexperiments.size();
        root["experiments"] = JSONexperiments;
    }

    /* add data-dictionary */
    QList <squirrelDataDictionary> dicts = GetDataDictionaryList();
    if (dicts.size() > 0) {
        Log(QString("Adding %1 data-dictionaries").arg(dicts.size()));
        QJsonArray JSONdataDictionaries;
        foreach (squirrelDataDictionary d, dicts) {
            if (d.Get()) {
                JSONdataDictionaries.append(d.ToJSON());
                Log("Added data-dictionary");
            }
        }
        root["DataDictionaryCount"] = JSONdataDictionaries.size();
        root["data-dictionaries"] = JSONdataDictionaries;
    }
    root["TotalSize"] = GetUnzipSize();
    root["TotalFileCount"] = GetFileCount();

    QString j = QJsonDocument(root).toJson();

    /* update the package in place with the new .json file */
    Log("Updating existing package");
    if (!UpdateMemoryFileToArchive(j, "squirrel.json", GetPackagePath(), m)) {
        Log("Error [" + m + "] compressing memory file to archive");
    }

    /* write the log file */
    if (writeLog)
        utils::WriteTextFile(logfile, log);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Extract ---------------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::Extract(QString destinationDir, QString &m) {
    m = "Extracting [" + packagePath + "] to [" + destinationDir + "]";
    if (ExtractArchiveToDirectory(packagePath, destinationDir, m))
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- Validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Validate if a squirrel package is readable
 * @return true if valid squirrel file, false otherwise
 */
bool squirrel::Validate() {

    if (Read())
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- GetLogBuffer ----------------------------------------- */
/* ------------------------------------------------------------ */
QString squirrel::GetLogBuffer() {

    QString ret = logBuffer;
    logBuffer = "";

    return ret;
}


/* ------------------------------------------------------------ */
/* ----- PrintTree -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print the details of a package, including all objects
 */
QString squirrel::PrintTree() {
    QString str;

    /* print package info */
    str += PrintPackage();

    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetSubjectList();
    foreach (squirrelSubject sub, subjects) {
        //qint64 subjectRowID = sub.GetObjectID();
        str += sub.PrintTree(false);

        /* get intervention/observation list */
        QList<squirrelObservation> observations = GetObservationList(sub.GetObjectID());
        QList<squirrelIntervention> interventions = GetInterventionList(sub.GetObjectID());
        str += utils::Print(QString("        %1 interventions   %2 observations").arg(observations.size()).arg(interventions.size()));
    }

    /* iterate through pipelines */
    str += PrintPipelines();

    /* iterate through experiments */
    str += PrintExperiments();

    return str;
}


/* ------------------------------------------------------------ */
/* ----- Print ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print the details of a package, including all objects
 */
QString squirrel::Print(bool detail) {
    QString str;

    /* print package info */
    str += PrintPackage();

    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetSubjectList();
    foreach (squirrelSubject sub, subjects) {
        qint64 subjectRowID = sub.GetObjectID();
        str += sub.PrintDetails();

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudyList(subjectRowID);
        foreach (squirrelStudy study, studies) {
            qint64 studyRowID = study.GetObjectID();
            str += study.PrintStudy(List);

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeriesList(studyRowID);
            foreach (squirrelSeries series, serieses) {
                str += series.PrintSeries(Details);
            }

            /* iterate through analyses */
            QList<squirrelAnalysis> analyses = GetAnalysisList(studyRowID);
            foreach (squirrelAnalysis analysis, analyses) {
                str += analysis.PrintAnalysis();
            }
        }

        /* iterate through observations */
        QList<squirrelObservation> observations = GetObservationList(subjectRowID);
        if (detail)
            foreach (squirrelObservation observation, observations)
                str += observation.PrintObservation();
        else
            str += QString("[%1 observations]").arg(observations.size());

        /* iterate through Interventions */
        QList<squirrelIntervention> interventions = GetInterventionList(subjectRowID);
        if (detail)
            foreach (squirrelIntervention intervention, interventions)
                str += intervention.PrintIntervention();
        else
            str += QString("[%1 interventions]").arg(observations.size());

    }

    /* iterate through pipelines */
    str += PrintPipelines();

    /* iterate through experiments */
    str += PrintExperiments();

    return str;
}


/* ------------------------------------------------------------ */
/* ----- GetUnzipSize ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the unzipped size of the squirrel package in bytes
 * @return unzipped size of the squirrel package in bytes
 */
qint64 squirrel::GetUnzipSize() {

    qint64 unzipSize(0);

    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* Analysis */
    q.prepare("select sum(Size) 'Size' from Analysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    unzipSize += q.value("Size").toLongLong();

    /* DataDictionary */
    q.prepare("select sum(Size) 'Size' from DataDictionary");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    unzipSize += q.value("Size").toLongLong();

    /* Experiment */
    q.prepare("select sum(Size) 'Size' from Experiment");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    unzipSize += q.value("Size").toLongLong();

    /* GroupAnalysis */
    q.prepare("select sum(Size) 'Size' from GroupAnalysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    unzipSize += q.value("Size").toLongLong();

    /* Series */
    q.prepare("select sum(Size) 'Size', sum(BehavioralSize) 'BehavioralSize' from Series");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    unzipSize += q.value("Size").toLongLong();
    unzipSize += q.value("BehavioralSize").toLongLong();

    return unzipSize;
}


/* ------------------------------------------------------------ */
/* ----- GetFileCount ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get the number of files in the squirrel package
 * @return total number of files in the package
 */
qint64 squirrel::GetFileCount() {
    qint64 total(0);

    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* Analysis */
    q.prepare("select sum(FileCount) 'FileCount' from Analysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("FileCount").toLongLong();

    /* DataDictionary */
    q.prepare("select sum(FileCount) 'FileCount' from DataDictionary");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("FileCount").toLongLong();

    /* Experiment */
    q.prepare("select sum(FileCount) 'FileCount' from Experiment");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("FileCount").toLongLong();

    /* GroupAnalysis */
    q.prepare("select sum(FileCount) 'FileCount' from GroupAnalysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("FileCount").toLongLong();

    /* Series */
    q.prepare("select sum(FileCount) 'FileCount', sum(BehavioralFileCount) 'BehavioralFileCount' from Series");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("FileCount").toLongLong();
    total += q.value("BehavioralFileCount").toLongLong();

    return total;
}


/* ------------------------------------------------------------ */
/* ----- GetObjectCount --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the count for a specified object
 * @param object the object to get a count of
 * @return the number of objects
 */
qint64 squirrel::GetObjectCount(ObjectType object) {
    qint64 count(0);
    QString table;

    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    switch (object) {
        case Analysis: table = "Analysis"; break;
        case DataDictionary: table = "DataDictionary"; break;
        case DataDictionaryItem: table = "DataDictionaryItem"; break;
        case Experiment: table = "Experiment"; break;
        case GroupAnalysis: table = "GroupAnalysis"; break;
        case Intervention: table = "Intervention"; break;
        case Observation: table = "Observation"; break;
        case Pipeline: table = "Pipeline"; break;
        case Series: table = "Series"; break;
        case Study: table = "Study"; break;
        case Subject: table = "Subject"; break;
        default: return -1;
    }

    q.prepare("select count(*) 'count' from " + table);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first())
        count = q.value("count").toInt();

    return count;
}


/* ------------------------------------------------------------ */
/* ----- PrintPackage ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print package details
 */
QString squirrel::PrintPackage() {
    QString str;

    qint64 numAnalyses = GetObjectCount(Analysis);
    qint64 numDataDictionaries = GetObjectCount(DataDictionary);
    qint64 numDataDictionaryItem = GetObjectCount(DataDictionaryItem);
    qint64 numExperiments = GetObjectCount(Experiment);
    qint64 numGroupAnalyses = GetObjectCount(GroupAnalysis);
    qint64 numInterventions = GetObjectCount(Intervention);
    qint64 numObservations = GetObjectCount(Observation);
    qint64 numPipelines = GetObjectCount(Pipeline);
    qint64 numSeries = GetObjectCount(Series);
    qint64 numStudies = GetObjectCount(Study);
    qint64 numSubjects = GetObjectCount(Subject);

    QString fileModeStr = "UnknownFileMode";
    if (fileMode == FileMode::NewPackage) fileModeStr = "NewPackage";
    if (fileMode == FileMode::ExistingPackage) fileModeStr = "ExistingPackage";

    str += utils::Print("Squirrel Package: " + GetPackagePath());
    str += utils::Print(QString("  DataFormat: %1").arg(DataFormat));
    str += utils::Print(QString("  Date: %1").arg(Datetime.toString()));
    str += utils::Print(QString("  Description: %1").arg(Description));
    str += utils::Print(QString("  DirectoryFormat (subject, study, series): %1, %2, %3").arg(SubjectDirFormat).arg(StudyDirFormat).arg(SeriesDirFormat));
    str += utils::Print(QString("  FileMode: %1").arg(fileModeStr));
    str += utils::Print(QString("  Files:\n    %1 files\n    %2 bytes (unzipped)").arg(GetFileCount()).arg(GetUnzipSize()));
    str += utils::Print(QString("  PackageName: %1").arg(PackageName));
    str += utils::Print(QString("  SquirrelBuild: %1").arg(SquirrelBuild));
    str += utils::Print(QString("  SquirrelVersion: %1").arg(SquirrelVersion));
    str += utils::Print(QString("  Objects:\n    +-- %1 subjects\n    |  +-- %4 observations\n    |  +-- %5 Interventions\n    |  +-- %2 studies\n    |  +---- %3 series\n    |  +---- %6 analyses\n    +-- %7 experiments\n    +-- %8 pipelines\n    +-- %9 group analyses\n    +-- %10 data dictionary").arg(numSubjects).arg(numStudies).arg(numSeries).arg(numObservations).arg(numInterventions).arg(numAnalyses).arg(numExperiments).arg(numPipelines).arg(numGroupAnalyses).arg(numDataDictionaries));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- MakeTempDir ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Make a temporary directory
 * @return true if created/exists, false otherwise
 */
bool squirrel::MakeTempDir(QString &dir) {

    QString d;
    //#ifdef Q_OS_WINDOWS
    //    d = QString("C:/tmp/%1").arg(utils::GenerateRandomString(20));
    //#else
    d = QString(QDir::tempPath() + "/%1").arg(utils::GenerateRandomString(20));
    //#endif

    QString m;
    if (utils::MakePath(d, m)) {
        dir = d;
        return true;
    }
    else {
        dir = "";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- DeleteTempDir ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Delete a temporary directory
 * @return true if created/exists, false otherwise
 */
bool squirrel::DeleteTempDir(QString dir) {
    /* delete the tmp dir, if it exists */
    if (utils::FileExists(dir)) {
        Debug("Temporary directory [" + dir + "] exists and will be deleted", __FUNCTION__);
        QString m;
        if (!utils::RemoveDir(dir, m)) {
            Log("Error [" + m + "] removing directory [" + dir + "]");
            return false;
        }
    }
    return true;
}


/* ------------------------------------------------------------ */
/* ----- AddStagedFiles --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add the paths of staged files to a specified object
 * These files will be copied when the package is written
 * @param object The object type to add
 * @param rowid the database row ID of the object
 * @param files the list of files to be staged
 * @return
 */
bool squirrel::AddStagedFiles(ObjectType object, qint64 rowid, QStringList files) {

    if (rowid < 0) return false;
    if (files.size() <= 0) return false;

    utils::Print(QString("Adding [%1] files of type [%2] to rowID [%3]").arg(files.size()).arg(ObjectTypeToString(object)).arg(rowid));

    if (object == Series) {
        squirrelSeries s(databaseUUID);
        s.SetObjectID(rowid);
        if (s.Get()) {
            foreach (QString f, files) {
                s.stagedFiles.append(f);
                Debug(QString("Appended file [%1] of type [%2] to rowID [%3]. Size is now [%4]").arg(files.size()).arg(ObjectTypeToString(object)).arg(rowid).arg(s.stagedFiles.size()), __FUNCTION__);
            }
            if (s.Store())
                return true;
        }
        else {
            Debug("Unable to get series object. Error [" + s.Error() + "]", __FUNCTION__);
        }
    }

    if (object == Experiment) {
        squirrelExperiment e(databaseUUID);
        e.SetObjectID(rowid);
        if (e.Get()) {
            foreach (QString f, files)
                e.stagedFiles.append(f);
            if (e.Store())
                return true;
        }
    }

    if (object == Pipeline) {
        squirrelPipeline p(databaseUUID);
        p.SetObjectID(rowid);
        if (p.Get()) {
            foreach (QString f, files)
                p.stagedFiles.append(f);
            if (p.Store())
                return true;
        }
    }

    if (object == Analysis) {
        squirrelAnalysis a(databaseUUID);
        a.SetObjectID(rowid);
        if (a.Get()) {
            foreach (QString f, files)
                a.stagedFiles.append(f);
            if (a.Store())
                return true;
        }
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetTempDir ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the automatically created temp directory
 * @return the temp directory
 */
QString squirrel::GetTempDir() {
    return workingDir;
}


/* ------------------------------------------------------------ */
/* ----- Log -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Record a log to a string and print to the screen
 * @param s The log message
 * @param func The function that called this function
 */
void squirrel::Log(QString s) {
    if (s.trimmed() != "") {
        log.append(QString("%1\n").arg(s));
        logBuffer.append(QString("%1\n").arg(s));
        if (!quiet) {
            utils::Print(s);
        }
    }
}


/* ------------------------------------------------------------ */
/* ----- Debug ------------------------------------------------ */
/* ------------------------------------------------------------ */
void squirrel::Debug(QString s, QString func) {
    if (debug) {
        if (s.trimmed() != "") {
            log.append(QString("Debug %1() %2\n").arg(func).arg(s));
            logBuffer.append(QString("Debug %1() %2\n").arg(func).arg(s));
            utils::Print(QString("Debug %1() %2").arg(func).arg(s));
        }
    }
}


/* ------------------------------------------------------------ */
/* ----- ReadParamsFile --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Reads a JSON key/value pair file into a hash
 * @param f JSON file
 * @return list of key/value pairs
 */
QHash<QString, QString> squirrel::ReadParamsFile(QString f) {

    /* get the JSON document and root object */
    QJsonDocument d = QJsonDocument::fromJson(f.toUtf8());

    QHash<QString, QString> tags;

    QJsonObject json = d.object();
    foreach(const QString& key, json.keys()) {
        tags[key] = json.value(key).toString();
    }
    return tags;

}


/* ------------------------------------------------------------ */
/* ----- PrintSubjects ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of subjects to stdout
 * @param details true to print details, false to print list of subject IDs
 */
QString squirrel::PrintSubjects(DatasetType dataType, PrintFormat printFormat) {
    QString str;

    QList <squirrelSubject> subjects = GetSubjectList();
    int count = subjects.size();
    if (count > 0) {

        /* get the data */
        QList <QStringHash> rows;
        QStringList keys;
        foreach (squirrelSubject s, subjects) {
            if (s.Get()) {
                QStringHash row = s.GetData(dataType);
                rows.append(row);

                /* get the keys */
                keys.append(row.keys());
            }
        }
        keys.removeDuplicates();
        keys.sort();

        str = utils::PrintData(printFormat, keys, rows);
    }
    else
        str += utils::Print("No subjects in this package");

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintStudies ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of studies to the stdout
 * @param subjectID the subject ID to print studies for
 * @param details true to print details, false to print list of study numbers
 */
QString squirrel::PrintStudies(DatasetType dataType, PrintFormat printFormat, qint64 subjectRowID) {
    QString str;

    QList <squirrelStudy> studies = GetStudyList(subjectRowID);
    int count = studies.size();
    if (count > 0) {

        /* get the data */
        QList <QStringHash> rows;
        QStringList keys;
        foreach (squirrelStudy s, studies) {
            if (s.Get()) {
                QStringHash row = s.GetData(dataType);

                /* add in subject information */
                squirrelSubject subj = GetSubject(s.subjectRowID);
                subj.Get();
                QStringHash row2 = subj.GetData(DatasetBasic);
                row = utils::MergeStringHash(row, row2);

                rows.append(row);

                /* get the keys */
                keys.append(row.keys());
            }
        }
        keys.removeDuplicates();
        keys.sort();

        str = utils::PrintData(printFormat, keys, rows);
    }
    else
        str = utils::Print("No studies found");

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintSeries ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of series to stdout
 * @param subjectID the subject ID
 * @param studyNum the study number
 * @param details true to print details, false to print list of series numbers
 */
QString squirrel::PrintSeries(DatasetType dataType, PrintFormat printFormat, qint64 seriesRowID) {
    QString str;

    QList <squirrelSeries> series = GetSeriesList(seriesRowID);
    int count = series.size();
    if (count > 0) {

        /* get the data */
        QList <QStringHash> rows;
        QStringList keys;
        foreach (squirrelSeries s, series) {
            if (s.Get()) {
                QStringHash row = s.GetData(dataType);

                /* add in subject information */
                squirrelStudy stud = GetStudy(s.studyRowID);
                stud.Get();
                QStringHash row2 = stud.GetData(DatasetBasic);
                row = utils::MergeStringHash(row, row2);

                /* add in subject information */
                squirrelSubject subj = GetSubject(s.subjectRowID);
                subj.Get();
                QStringHash row3 = subj.GetData(DatasetBasic);
                row = utils::MergeStringHash(row, row3);

                rows.append(row);

                /* get the keys */
                keys.append(row.keys());
            }
        }
        keys.removeDuplicates();
        keys.sort();

        str = utils::PrintData(printFormat, keys, rows);
    }
    else
        str = utils::Print("No studies found");

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintObservations ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print all of the observations for a given subjectRowID
 * @param subjectRowID The subjectRowID
 * @param printFormat One of the PrintFormat enumerations
 */
QString squirrel::PrintObservations(DatasetType dataType, PrintFormat printFormat, qint64 subjectRowID) {
    QString str;

    QList <squirrelObservation> observations = GetObservationList(subjectRowID);
    int count = observations.size();
    if (count > 0) {

        /* get the data */
        QList <QStringHash> rows;
        QStringList keys;
        foreach (squirrelObservation o, observations) {
            if (o.Get()) {
                QStringHash row = o.GetData(dataType);

                /* add in subject information */
                squirrelSubject subj = GetSubject(o.subjectRowID);
                subj.Get();
                QStringHash row2 = subj.GetData(DatasetBasic);
                row = utils::MergeStringHash(row, row2);

                rows.append(row);

                /* get the keys */
                keys.append(row.keys());
            }
        }
        keys.removeDuplicates();
        keys.sort();

        str = utils::PrintData(printFormat, keys, rows);
    }
    else
        str += utils::Print("No subjects in this package");

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintInterventions ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print all of the interventions for a given subjectRowID
 * @param subjectRowID The subjectRowID
 * @param printFormat One of the PrintFormat enumerations
 */
QString squirrel::PrintInterventions(DatasetType dataType, PrintFormat printFormat, qint64 subjectRowID) {
    QString str;

    QList <squirrelIntervention> interventions = GetInterventionList(subjectRowID);
    int count = interventions.size();
    if (count > 0) {

        /* get the data */
        QList <QStringHash> rows;
        QStringList keys;
        foreach (squirrelIntervention i, interventions) {
            if (i.Get()) {
                QStringHash row = i.GetData(dataType);

                /* add in subject information */
                squirrelSubject subj = GetSubject(i.subjectRowID);
                subj.Get();
                QStringHash row2 = subj.GetData(DatasetBasic);
                row = utils::MergeStringHash(row, row2);

                rows.append(row);

                /* get the keys */
                keys.append(row.keys());
            }
        }
        keys.removeDuplicates();
        keys.sort();

        str = utils::PrintData(printFormat, keys, rows);
    }
    else
        str += utils::Print("No subjects in this package");

    return str;


    // QString str;

    // QList <squirrelIntervention> interventions = GetInterventionList(subjectRowID);
    // QStringList interventionNames;
    // foreach (squirrelIntervention o, interventions) {
    //     if (o.Get()) {
    //         if (printFormat == PrintFormat::Details)
    //             str += o.PrintIntervention();
    //         else
    //             interventionNames.append(o.InterventionName);
    //     }
    // }
    // if (printFormat == PrintFormat::Details)
    //     str += utils::Print("Interventions: " + interventionNames.join(" "));

    // return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintExperiments ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of experiments to stdout
 * @param details true to print details, false to print list of pipeline names
 */
QString squirrel::PrintExperiments(PrintFormat printFormat) {
    QString str;

    QList <squirrelExperiment> exps = GetExperimentList();
    QStringList experimentNames;
    foreach (squirrelExperiment e, exps) {
        if (e.Get()) {
            if (printFormat == PrintFormat::Details)
                str += e.PrintExperiment();
            else if (printFormat == PrintFormat::List)
                experimentNames.append(e.ExperimentName);
        }
    }
    if (printFormat == PrintFormat::List)
        str += utils::Print("Experiments: " + experimentNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintPipelines --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of pipelines to stdout
 * @param details true to print details, false to print list of pipeline names
 */
QString squirrel::PrintPipelines(PrintFormat printFormat) {
    QString str;

    QList <squirrelPipeline> pipelines = GetPipelineList();
    QStringList pipelineNames;
    foreach (squirrelPipeline p, pipelines) {
        if (p.Get()) {
            if (printFormat == PrintFormat::Details)
                str += p.PrintPipeline();
            else if (printFormat == PrintFormat::List)
                pipelineNames.append(p.PipelineName);
        }
    }
    if (printFormat == PrintFormat::List)
        str += utils::Print("Pipelines: " + pipelineNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintGroupAnalyses ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of group analyses to stdout
 * @param details true to print details, false to print list of group analysis names
 */
QString squirrel::PrintGroupAnalyses(PrintFormat printFormat) {
    QString str;

    QList <squirrelGroupAnalysis> groupAnalyses = GetGroupAnalysisList();
    QStringList groupAnalysisNames;
    foreach (squirrelGroupAnalysis g, groupAnalyses) {
        if (g.Get()) {
            if (printFormat == PrintFormat::Details)
                str += g.PrintGroupAnalysis();
            else if (printFormat == PrintFormat::List)
                groupAnalysisNames.append(g.GroupAnalysisName);
        }
    }
    if (printFormat == PrintFormat::List)
        str += utils::Print("GroupAnalysis: " + groupAnalysisNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintDataDictionary ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of data dictionaries to stdout
 * @param details `true` to print details, `false` otherwise
 * @return The string that was printed to stdout
 */
QString squirrel::PrintDataDictionary(PrintFormat printFormat) {
    QString str;

    QList <squirrelDataDictionary> dataDictionaries = GetDataDictionaryList();
    QStringList dataDictionaryNames;
    foreach (squirrelDataDictionary d, dataDictionaries) {
        if (d.Get()) {
            if (printFormat == PrintFormat::Details)
                str += d.PrintDataDictionary();
            else if (printFormat == PrintFormat::List)
                dataDictionaryNames.append(d.DataDictionaryName);
        }
    }
    if (printFormat == PrintFormat::List)
        str += utils::Print("DataDictionary: " + dataDictionaryNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintAnalyses ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print a list of studies to the stdout
 * @param subjectID the subject ID to print studies for
 * @param details true to print details, false to print list of study numbers
 */
QString squirrel::PrintAnalyses(qint64 studyRowID, PrintFormat printFormat) {
    QString str;

    QList <squirrelAnalysis> analyses = GetAnalysisList(studyRowID);
    QStringList analysisDates;
    foreach (squirrelAnalysis a, analyses) {
        if (a.Get()) {
            if (printFormat == PrintFormat::Details)
                str += a.PrintAnalysis();
            else if (printFormat == PrintFormat::List)
                analysisDates.append(QString("%1").arg(a.DateStart.toString()));
        }
    }
    if (printFormat == PrintFormat::List)
        str += utils::Print("Analyses: " + analysisDates.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- GetExperimentList ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of experiment objects
 * @return list of all experiments
 */
QList<squirrelExperiment> squirrel::GetExperimentList() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelExperiment> list;
    q.prepare("select ExperimentRowID from Experiment");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelExperiment e(databaseUUID);
        e.SetObjectID(q.value("ExperimentRowID").toLongLong());
        if (e.Get()) {
            list.append(e);
        }
    }

    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetPipelineList -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetPipelineList
 * @return list of all pipelines
 */
QList<squirrelPipeline> squirrel::GetPipelineList() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelPipeline> list;
    q.prepare("select PipelineRowID from Pipeline");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelPipeline p(databaseUUID);
        p.SetObjectID(q.value("PipelineRowID").toLongLong());
        if (p.Get()) {
            list.append(p);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetSubjectList --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all subject objects in the package
 * @return list of all subjects
 */
QList<squirrelSubject> squirrel::GetSubjectList() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelSubject> list;
    q.prepare("select SubjectRowID from Subject order by ID asc, SequenceNumber asc");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSubject s(databaseUUID);
        s.SetObjectID(q.value("SubjectRowID").toLongLong());
        if (s.Get()) {
            s.SetDirFormat(SubjectDirFormat);
            list.append(s);
        }
    }

    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetStudyList ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get list of all study objects for a specified subject
 * @param subjectRowID database row ID of the subject
 * @return list of studies
 */
QList<squirrelStudy> squirrel::GetStudyList(qint64 subjectRowID) {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelStudy> list;
    if (subjectRowID < 0) {
        q.prepare("select StudyRowID from Study order by StudyNumber asc, SequenceNumber asc");
    }
    else {
        q.prepare("select StudyRowID from Study where SubjectRowID = :id order by StudyNumber asc, SequenceNumber asc");
        q.bindValue(":id", subjectRowID);
    }
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelStudy s(databaseUUID);
        s.SetObjectID(q.value("StudyRowID").toLongLong());
        if (s.Get()) {
            s.SetDirFormat(SubjectDirFormat, StudyDirFormat);
            list.append(s);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetSeriesList ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get list of all series objects for a specified study
 * @param studyRowID database row ID of the study
 * @return list of series
 */
QList<squirrelSeries> squirrel::GetSeriesList(qint64 studyRowID) {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelSeries> list;
    if (studyRowID < 0) {
        q.prepare("select SeriesRowID from Series order by SeriesNumber asc, SequenceNumber");
    }
    else {
        q.prepare("select SeriesRowID from Series where StudyRowID = :id order by SeriesNumber asc, SequenceNumber");
        q.bindValue(":id", studyRowID);
    }
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSeries s(databaseUUID);
        s.SetObjectID(q.value("SeriesRowID").toLongLong());
        if (s.Get()) {
            s.SetDirFormat(SubjectDirFormat, StudyDirFormat, SeriesDirFormat);
            list.append(s);
            Debug(QString("Found SeriesNumber [%1]").arg(s.SeriesNumber), __FUNCTION__);
        }
        else {
            Log(QString("Unable to load SeriesRowID [%1]").arg(s.GetObjectID()));
        }
    }
    Debug(QString("Found [%1] series for StudyRowID [%2]").arg(list.size()).arg(studyRowID), __FUNCTION__);
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalysisList -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get list of all Analysis objects for the specified study
 * @param studyRowID of the parent study
 * @return QList of squirrelAnalysis objects
 */
QList<squirrelAnalysis> squirrel::GetAnalysisList(qint64 studyRowID) {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelAnalysis> list;
    q.prepare("select AnalysisRowID from Analysis where StudyRowID = :id");
    q.bindValue(":id", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelAnalysis a(databaseUUID);
        a.SetObjectID(q.value("AnalysisRowID").toLongLong());
        if (a.Get()) {
            list.append(a);
        }
    }

    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetObservationList ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all Observation objects for a subject
 * @param subjectRowID of the parent subject
 * @return QList of squirrelObservation objects
 */
QList<squirrelObservation> squirrel::GetObservationList(qint64 subjectRowID) {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelObservation> list;
    if (subjectRowID < 0) {
        q.prepare("select ObservationRowID from Observation order by InstrumentName asc, ObservationName asc");
    }
    else {
        q.prepare("select ObservationRowID from Observation where SubjectRowID = :id");
        q.bindValue(":id", subjectRowID);
    }
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelObservation m(databaseUUID);
        m.SetObjectID(q.value("ObservationRowID").toLongLong());
        if (m.Get()) {
            list.append(m);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetInterventionList ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all Intervention objects
 * @param subjectRowID
 * @return list of all squirrelIntervention objects
 */
QList<squirrelIntervention> squirrel::GetInterventionList(qint64 subjectRowID) {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelIntervention> list;
    q.prepare("select InterventionRowID from Intervention where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelIntervention d(databaseUUID);
        d.SetObjectID(q.value("InterventionRowID").toLongLong());
        if (d.Get()) {
            list.append(d);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetGroupAnalysisList --------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all GroupAnalysis objects
 * @return list of all groupAnalysis objects
 */
QList<squirrelGroupAnalysis> squirrel::GetGroupAnalysisList() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelGroupAnalysis> list;
    q.prepare("select GroupAnalysisRowID from GroupAnalysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelGroupAnalysis g(databaseUUID);
        g.SetObjectID(q.value("GroupAnalysisRowID").toLongLong());
        if (g.Get()) {
            list.append(g);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetDataDictionaryList -------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all DataDictionary objects
 * @return list of all dataDictionary objects
 */
QList<squirrelDataDictionary> squirrel::GetDataDictionaryList() {
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    QList<squirrelDataDictionary> list;
    q.prepare("select DataDictionaryRowID from DataDictionary");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelDataDictionary s(databaseUUID);
        s.SetObjectID(q.value("DataDictionaryRowID").toInt());
        if (s.Get()) {
            list.append(s);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetSubject ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a single subject object by `subjectRowID`
 * @param subjectRowID
 * @return The `squirrelSubject` object
 */
squirrelSubject squirrel::GetSubject(qint64 subjectRowID) {
    squirrelSubject s(databaseUUID);
    s.SetObjectID(subjectRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetStudy --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a single study object by `studyRowID`
 * @param studyRowID
 * @return The `squirrelStudy` object
 */
squirrelStudy squirrel::GetStudy(qint64 studyRowID) {
    squirrelStudy s(databaseUUID);
    s.SetObjectID(studyRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a single series object by `seriesRowID`
 * @param seriesRowID
 * @return The `squirrelSeries` object
 */
squirrelSeries squirrel::GetSeries(qint64 seriesRowID) {
    squirrelSeries s(databaseUUID);
    s.SetObjectID(seriesRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalysis ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get a single analysis object by `analysisRowID`
 * @param analysisRowID
 * @return The `squirrelAnalysis` object
 */
squirrelAnalysis squirrel::GetAnalysis(qint64 analysisRowID) {
    squirrelAnalysis a(databaseUUID);
    a.SetObjectID(analysisRowID);
    a.Get();

    return a;
}


/* ------------------------------------------------------------ */
/* ----- GetIntervention -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a single intervention object by `interventionRowID`
 * @param interventionRowID
 * @return The `squirrelIntervention` object
 */
squirrelIntervention squirrel::GetIntervention(qint64 interventionRowID) {
    squirrelIntervention i(databaseUUID);
    i.SetObjectID(interventionRowID);
    i.Get();

    return i;
}


/* ------------------------------------------------------------ */
/* ----- GetObservation --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a single observation object by `observationRowID`
 * @param observationRowID
 * @return The `squirrelObservation` object
 */
squirrelObservation squirrel::GetObservation(qint64 observationRowID) {
    squirrelObservation o(databaseUUID);
    o.SetObjectID(observationRowID);
    o.Get();

    return o;
}


/* ------------------------------------------------------------ */
/* ----- GetGroupAnalysis ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetGroupAnalysis
 * @param groupAnalysisRowID
 * @return
 */
squirrelGroupAnalysis squirrel::GetGroupAnalysis(qint64 groupAnalysisRowID) {
    squirrelGroupAnalysis g(databaseUUID);
    g.SetObjectID(groupAnalysisRowID);
    g.Get();

    return g;
}


/* ------------------------------------------------------------ */
/* ----- GetDataDictionary ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetDataDictionary
 * @param dataDictionaryRowID
 * @return
 */
squirrelDataDictionary squirrel::GetDataDictionary(qint64 dataDictionaryRowID) {
    squirrelDataDictionary d(databaseUUID);
    d.SetObjectID(dataDictionaryRowID);
    d.Get();

    return d;
}


/* ------------------------------------------------------------ */
/* ----- GetExperiment ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetExperiment
 * @param experimentRowID
 * @return
 */
squirrelExperiment squirrel::GetExperiment(qint64 experimentRowID) {
    squirrelExperiment e(databaseUUID);
    e.SetObjectID(experimentRowID);
    e.Get();

    return e;
}


/* ------------------------------------------------------------ */
/* ----- GetPipeline ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetPipeline
 * @param pipelineRowID
 * @return
 */
squirrelPipeline squirrel::GetPipeline(qint64 pipelineRowID) {
    squirrelPipeline p(databaseUUID);
    p.SetObjectID(pipelineRowID);
    p.Get();

    return p;
}


/* ------------------------------------------------------------ */
/* ----- FindSubject ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Find a Subject by subject ID
 * @param id the SubjectID field
 * @return the database rowID of the subject if found, -1 otherwise
 */
qint64 squirrel::FindSubject(QString id) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select SubjectRowID from Subject where ID = :id");
    q.bindValue(":id", id);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SubjectRowID").toLongLong();
        Log(QString("Searched for SubjectID [%1] and found SubjectRowID [%2]").arg(id).arg(rowid));
    }
    else {
        Log(QString("Could not find SubjectID [%1]").arg(id));
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindStudy -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find a Study by subject ID and study number
 * @param subjectID Subject ID to search for
 * @param studyNum Study number to search for
 * @return the database row ID
 */
qint64 squirrel::FindStudy(QString subjectID, int studyNum) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select a.StudyRowID from Study a left join Subject b on a.SubjectRowID = b.SubjectRowID where a.StudyNumber = :studynum and b.ID = :id");
    q.bindValue(":studynum", studyNum);
    q.bindValue(":id", subjectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("StudyRowID").toLongLong();
        Debug(QString("Searched for SubjectID, StudyNumber [%1], [%2] and found StudyRowID [%3]").arg(subjectID).arg(studyNum).arg(rowid), __FUNCTION__);
    }
    else {
        Debug(QString("Could not find SubjectID, StudyNumber [%1], [%2]").arg(subjectID).arg(studyNum), __FUNCTION__);
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindStudyByUID --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find a Study by the DICOM StudyUID field
 * @param studyUID The DICOM StudyUID field to search for
 * @return the database row ID
 */
qint64 squirrel::FindStudyByUID(QString studyUID) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select StudyRowID from Study where StudyUID = :studyuid");
    q.bindValue(":studyuid", studyUID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        q.first();
        rowid = q.value("StudyRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindSeries ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find a Series by subject ID, study number, and series number
 * @param subjectID Subject ID to search for
 * @param studyNum Study number to search for
 * @param seriesNum Series number to search for
 * @return The database rowid
 */
qint64 squirrel::FindSeries(QString subjectID, int studyNum, int seriesNum) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Series a left join Study b on a.StudyRowID = b.StudyRowID left join Subject c on b.SubjectRowID = b.SubjectRowID where a.SeriesNumber = :seriesnum and b.StudyNumber = :studynum and c.ID = :id");
    q.bindValue(":seriesnum", seriesNum);
    q.bindValue(":studynum", studyNum);
    q.bindValue(":id", subjectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SeriesRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindSeriesByUID -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find a Series by the DICOM SeriesUID field
 * @param seriesUID DICOM SeriesUID field to search by
 * @return The database rowid
 */
qint64 squirrel::FindSeriesByUID(QString seriesUID) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select SeriesRowID from Series where SeriesUID = :seriesuid");
    q.bindValue(":seriesuid", seriesUID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SeriesRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindAnalysis ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find an Anlaysis using subject ID, study number, and analysis name
 * @param subjectID Subject ID to search by
 * @param studyNum Study number to search by
 * @param analysisName Analysis name to search
 * @return The database rowid
 */
qint64 squirrel::FindAnalysis(QString subjectID, int studyNum, QString analysisName) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select AnalysisRowID from Analysis a left join Study b on a.StudyRowID = b.StudyRowID left join Subject c on b.SubjectRowID = b.SubjectRowID where a.AnalyisName = :analysisname and b.StudyNumber = :studynum and c.ID = :id");
    q.bindValue(":analysisname", analysisName);
    q.bindValue(":studynum", studyNum);
    q.bindValue(":id", subjectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("AnalysisRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindExperiment --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find an Experiment by name
 * @param experimentName Experiment name to search for
 * @return The database rowid
 */
qint64 squirrel::FindExperiment(QString experimentName) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select ExperimentRowID from Experiment where ExperimentName = :experimentName");
    q.bindValue(":experimentName", experimentName);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("ExperimentRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindPipeline ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find a Pipeline by name
 * @param pipelineName Pipeline name to search for
 * @return The database rowid
 */
qint64 squirrel::FindPipeline(QString pipelineName) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select PipelineRowID from Pipeline where PipelineName = :pipelineName");
    q.bindValue(":pipelineName", pipelineName);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("PipelineRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindGroupAnalysis ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Find a GroupAnalysis by group analysis name
 * @param groupAnalysisName GroupAnalysis name to search for
 * @return The database rowid
 */
qint64 squirrel::FindGroupAnalysis(QString groupAnalysisName) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select GroupAnalysisRowID from GroupAnalysis where GroupAnalysisName = :groupAnalysisName");
    q.bindValue(":groupAnalysisName", groupAnalysisName);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("GroupAnalysisRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindDataDictionary ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Find a DataDictionary by data dictonary name
 * @param dataDictionaryName DataDictionary name to search for
 * @return The database rowid
 */
qint64 squirrel::FindDataDictionary(QString dataDictionaryName) {
    qint64 rowid(-1);
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select DataDictionaryRowID from DataDictionary where DataDictionaryName = :dataDictionaryName");
    q.bindValue(":dataDictionaryName", dataDictionaryName);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("DataDictionaryRowID").toLongLong();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- ExtractSubject --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Extract an object from the archive into a directory on disk
 * @param object The object type
 * @param subjectRowID The object rowID
 * @param outDir output directory
 * @param recursive `true` if all data should be extracted, recursively (studies and series). This is the default
 * @return `true` if successful, `false` otherwise
 */
bool squirrel::ExtractObject(ObjectType object, qint64 objectRowID, QString outDir, bool recursive) {
    if (objectRowID < 0) {
        Log("Invalid object ID []");
        return false;
    }

    QString virtualPath;
    QString jsonPath;
    QString j;
    if (object == Subject) {
        squirrelSubject s = GetSubject(objectRowID);
        virtualPath = s.VirtualPath();
        jsonPath = outDir + "/subject.json";
        QJsonDocument doc(s.ToJSON());
        j = doc.toJson();
    }
    else if (object == Study) {
        squirrelStudy s = GetStudy(objectRowID);
        virtualPath = s.VirtualPath();
        jsonPath = outDir + "/study.json";
        QJsonDocument doc(s.ToJSON());
        j = doc.toJson();
    }
    else if (object == Series) {
        squirrelSeries s = GetSeries(objectRowID);
        virtualPath = s.VirtualPath();
        jsonPath = outDir + "/series.json";
        QJsonDocument doc(s.ToJSON());
        j = doc.toJson();
    }
    else if (object == Analysis) {
        squirrelAnalysis a = GetAnalysis(objectRowID);
        virtualPath = a.VirtualPath();
        jsonPath = outDir + "/analysis.json";
        QJsonDocument doc(a.ToJSON());
        j = doc.toJson();
    }
    else if (object == Experiment) {
        squirrelExperiment e = GetExperiment(objectRowID);
        virtualPath = e.VirtualPath();
        jsonPath = outDir + "/experiment.json";
        QJsonDocument doc(e.ToJSON());
        j = doc.toJson();
    }
    else if (object == Pipeline) {
        squirrelPipeline p = GetPipeline(objectRowID);
        virtualPath = p.VirtualPath();
        jsonPath = outDir + "/pipeline.json";
        QJsonDocument doc(p.ToJSON(jsonPath));
        j = doc.toJson();
    }
    else if (object == GroupAnalysis) {
        squirrelGroupAnalysis g = GetGroupAnalysis(objectRowID);
        virtualPath = g.VirtualPath();
        jsonPath = outDir + "/groupanalysis.json";
        QJsonDocument doc(g.ToJSON());
        j = doc.toJson();
    }
    else if (object == DataDictionary) {
        squirrelDataDictionary d = GetDataDictionary(objectRowID);
        virtualPath = d.VirtualPath();
        jsonPath = outDir + "/datadictionary.json";
        QJsonDocument doc(d.ToJSON());
        j = doc.toJson();
    }
    else {
        Log("Unknown object type [" + ObjectTypeToString(object) + "]");
        return false;
    }

    /* do the extraction to disk */
    #ifdef Q_OS_WINDOWS
        virtualPath += "\\*";
    #else
        virtualPath += "/*";
    #endif

    QString m;
    if (ExtractArchiveFilesToDirectory(packagePath, virtualPath, outDir, m)) {
        /* write out the JSON to disk for this subject as well */
        if (!utils::WriteTextFile(jsonPath, j))
            Log("Error writing " + jsonPath);

        return true;
    }
    else {
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- ResequenceSubjects ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Resequences (renumbers) the subjects. Used when writing
 * package with sequential directory names instead of subject IDs.
 */
void squirrel::ResequenceSubjects() {

    QList<squirrelSubject> subjects = GetSubjectList();
    int i = 1;
    foreach (squirrelSubject subject, subjects) {
        subject.SequenceNumber = i;
        subject.Store();
        i++;
    }
}


/* ------------------------------------------------------------ */
/* ----- ResequenceStudies ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Renumber the sequence field for the studies associated with the subject
 * @param subjectRowID The subjectRowID of the parent
 */
void squirrel::ResequenceStudies(qint64 subjectRowID) {

    QList<squirrelStudy> studies = GetStudyList(subjectRowID);
    int i = 1;
    foreach (squirrelStudy study, studies) {
        study.SequenceNumber = i;
        study.Store();
        i++;
    }
}


/* ------------------------------------------------------------ */
/* ----- ResequenceSeries ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Resequence the series numbers for a specified study
 * @param studyRowID The studyRowID to resequence
 */
void squirrel::ResequenceSeries(qint64 studyRowID) {

    QList<squirrelSeries> serieses = GetSeriesList(studyRowID);
    int i = 1;
    foreach (squirrelSeries series, serieses) {
        series.SequenceNumber = i;
        series.Store();
        i++;
    }
}


/* ------------------------------------------------------------ */
/* ----- RemoveObject ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Remove an object and child objects from the package
 * @param object Possible object types `subject`, `study`, `series`, `analysis`, `experiment`, `intervention`, `observation`, `pipeline`, `datadictionary`, `groupanalysis`
 * @param objectRowID object database rowID
 * @return true if successful, false otherwise
 */
bool squirrel::RemoveObject(ObjectType object, qint64 objectRowID) {
    if (object == Subject) {
        /* get list of studies associated with this subject, and delete them */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select StudyRowID from Study where SubjectRowID = :subjectRowID");
        q.bindValue(":subjectRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            if (!RemoveObject(Study, q.value("StudyRowID").toLongLong()))
                return false;
        }

        /* remove Interventions associated with this subject */
        q.prepare("delete from Intervention where SubjectRowID = :subjectRowID");
        q.bindValue(":subjectRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove all observations associated with this subject */
        q.prepare("delete from Observation where SubjectRowID = :subjectRowID");
        q.bindValue(":subjectRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove the files, if any from the archive */
        if (fileMode == ExistingPackage) {
            squirrelSubject sqrlSubject(databaseUUID);
            sqrlSubject.SetObjectID(objectRowID);
            sqrlSubject.Get();
            QString subjectArchivePath = sqrlSubject.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(subjectArchivePath, packagePath, m))
                return false;
        }

        /* remove the subject */
        q.prepare("delete from Subject where SubjectRowID = :subjectRowID");
        q.bindValue(":subjectRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        return true;
    }
    else if (object == Study) {
        /* get list of studies associated with this subject, and delete them */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select SeriesRowID from Series where StudyRowID = :studyRowID");
        q.bindValue(":studyRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            if (!RemoveObject(Series, q.value("SeriesRowID").toLongLong()))
                return false;
        }

        /* remove analyses associated with this subject */
        q.prepare("select AnalysisRowID from Analysis where StudyRowID = :studyRowID");
        q.bindValue(":studyRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            if (!RemoveObject(Analysis, q.value("AnalysisRowID").toLongLong()))
                return false;
        }

        /* remove files from the archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelSubject sqrlStudy(databaseUUID);
            sqrlStudy.SetObjectID(objectRowID);
            sqrlStudy.Get();
            QString studyArchivePath = sqrlStudy.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(studyArchivePath, packagePath, m))
                return false;
        }

        /* remove the study */
        q.prepare("delete from Study where StudyRowID = :studyRowID");
        q.bindValue(":studyRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        return true;
    }
    else if (object == Series) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Series where SeriesRowID = :seriesRowID");
        q.bindValue(":seriesRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelSeries sqrlSeries(databaseUUID);
            sqrlSeries.SetObjectID(objectRowID);
            sqrlSeries.Get();
            QString seriesArchivePath = sqrlSeries.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(seriesArchivePath, packagePath, m))
                return false;
        }

        return true;
    }
    else if (object == Analysis) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Analysis where AnalysisRowID = :analysisRowID");
        q.bindValue(":analysisRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelAnalysis sqrlAnalysis(databaseUUID);
            sqrlAnalysis.SetObjectID(objectRowID);
            sqrlAnalysis.Get();
            QString analysisArchivePath = sqrlAnalysis.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(analysisArchivePath, packagePath, m))
                return false;
        }

        return true;
    }
    else if (object == Intervention) {
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Intervention where InterventionRowID = :InterventionRowID");
        q.bindValue(":InterventionRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        return true;
    }
    else if (object == Observation) {
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Observation where ObservationRowID = :observationRowID");
        q.bindValue(":observationRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        return true;
    }
    else if (object == Analysis) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Analysis where AnalysisRowID = :analysisRowID");
        q.bindValue(":analysisRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelAnalysis sqrlAnalysis(databaseUUID);
            sqrlAnalysis.SetObjectID(objectRowID);
            sqrlAnalysis.Get();
            QString analysisArchivePath = sqrlAnalysis.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(analysisArchivePath, packagePath, m))
                return false;
        }

        return true;
    }
    else if (object == Experiment) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Experiment where ExperimentRowID = :experimentRowID");
        q.bindValue(":experimentRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelExperiment sqrlExperiment(databaseUUID);
            sqrlExperiment.SetObjectID(objectRowID);
            sqrlExperiment.Get();
            QString experimentArchivePath = sqrlExperiment.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(experimentArchivePath, packagePath, m))
                return false;
        }

        return true;
    }
    else if (object == Pipeline) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from Pipeline where PipelineRowID = :pipelineRowID");
        q.bindValue(":pipelineRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        q.prepare("delete from PipelineDataStep where PipelineRowID = :pipelineRowID");
        q.bindValue(":pipelineRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelPipeline sqrlPipeline(databaseUUID);
            sqrlPipeline.SetObjectID(objectRowID);
            sqrlPipeline.Get();
            QString pipelineArchivePath = sqrlPipeline.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(pipelineArchivePath, packagePath, m))
                return false;
        }

        return true;
    }
    else if (object == GroupAnalysis) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from GroupAnalysis where GroupAnalysisRowID = :groupAnalysisRowID");
        q.bindValue(":groupAnalysisRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelGroupAnalysis sqrlGroupAnalysis(databaseUUID);
            sqrlGroupAnalysis.SetObjectID(objectRowID);
            sqrlGroupAnalysis.Get();
            QString groupAnalysisArchivePath = sqrlGroupAnalysis.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(groupAnalysisArchivePath, packagePath, m))
                return false;
        }

        return true;
    }
    else if (object == DataDictionary) {
        /* delete from database */
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from DataDictionary where DataDictionaryRowID = :dataDictionaryRowID");
        q.bindValue(":dataDictionaryRowID", objectRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        /* remove files from archive */
        if (fileMode == FileMode::ExistingPackage) {
            squirrelDataDictionary sqrlDataDictionary(databaseUUID);
            sqrlDataDictionary.SetObjectID(objectRowID);
            sqrlDataDictionary.Get();
            QString dataDictionaryArchivePath = sqrlDataDictionary.VirtualPath();
            QString m;
            if (!RemoveDirectoryFromArchive(dataDictionaryArchivePath, packagePath, m))
                return false;
        }
        return true;
    }
    else {
        Debug("Unknown object type [" + ObjectTypeToString(object) + "]");
        return false;
    }
}

/* ------------------------------------------------------------ */
/* ----- ExtractArchiveFileToMemory --------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Extract a single file from an existing archive and return it as a string
 * @param archivePath Path to the archive
 * @param filePath File path within the archive
 * @param fileContents File contents as a QString
 * @return true if successful, false otherwise
 */
bool squirrel::ExtractArchiveFileToMemory(QString archivePath, QString filePath, QString &fileContents) {
    Debug(QString("Reading file [%1] from archive [%2]...").arg(filePath).arg(archivePath), __FUNCTION__);
    try {
        using namespace bit7z;
        std::vector<unsigned char> buffer;
        Bit7zLibrary lib(p7zipLibPath.toStdString());
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitFileExtractor extractor(lib, BitFormat::Zip);
            extractor.setProgressCallback(progressCallback);
            extractor.setTotalCallback(totalArchiveSizeCallback);
            extractor.extractMatching(archivePath.toStdString(), filePath.toStdString(), buffer);
        }
        else {
            BitFileExtractor extractor(lib, BitFormat::SevenZip);
            extractor.setProgressCallback(progressCallback);
            extractor.setTotalCallback(totalArchiveSizeCallback);
            Debug("Before calling extractMatching()", __FUNCTION__);
            extractor.extractMatching(archivePath.toStdString(), filePath.toStdString(), buffer);
            Debug(QString("After calling extractMatching() buffer size [%1] bytes").arg(buffer.size()), __FUNCTION__);
        }
        Debug(QString("Copying buffer to std::string. Buffer size [%1] bytes").arg(buffer.size()), __FUNCTION__);
        std::string str{buffer.begin(), buffer.end()};
        Debug(QString("Copying std::string to QString. string size [%1] bytes").arg(str.size()), __FUNCTION__);
        fileContents = QString::fromStdString(str);
        Debug(QString("Extracted file [%1]. File is [%2] bytes in length").arg(filePath).arg(fileContents.size()), __FUNCTION__);
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        fileContents = "Unable to extract file from archive using bit7z library [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- CompressDirectoryToArchive --------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Compress an existing directory to a new archive
 * @param dir Directory containing the files to compress
 * @param archivePath Path to the archive
 * @param m Any messages generated during the operation
 * @return true if successful, false otherwise
 */
bool squirrel::CompressDirectoryToArchive(QString dir, QString archivePath, QString &m) {
    Debug(QString("Compressing directory [%1] to archive [%2]...").arg(dir).arg(archivePath));

    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());

        if (overwritePackage) {
            if (QFile::exists(archivePath) && (archivePath != "")) {
                Debug("Overwrite option specified. Deleting existing package [" + archivePath + "]", __FUNCTION__);
                QFile::remove(archivePath);
            }
        }

        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitArchiveWriter archive(lib, BitFormat::Zip);
            archive.setUpdateMode(UpdateMode::Update);
            archive.setCompressionLevel(BitCompressionLevel::Fastest);
            archive.setRetainDirectories(true);
            archive.setProgressCallback(progressCallback);
            archive.setTotalCallback(totalArchiveSizeCallback);
            archive.addFiles(dir.toStdString(), "*", true); // instead of addDirectory
            archive.compressTo(archivePath.toStdString());
        }
        else {
            BitArchiveWriter archive(lib, BitFormat::SevenZip);
            archive.setUpdateMode(UpdateMode::Update);
            archive.setCompressionLevel(BitCompressionLevel::Fastest);
            archive.setRetainDirectories(true);
            archive.setSolidMode(false);
            archive.setProgressCallback(progressCallback);
            archive.setTotalCallback(totalArchiveSizeCallback);
            archive.addFiles(dir.toStdString(), "*", true); // instead of addDirectory
            archive.compressTo(archivePath.toStdString());
        }
        m = "Successfully compressed directory [" + dir + "] to archive [" + archivePath + "]";
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to compress directory into archive using bit7z library. Error [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- AddFilesToArchive ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Add/update files to an existing archive
 * @param filePaths File paths to add
 * @param compressedFilePaths Paths to the file paths within the archive
 * @param archivePath Path to the archive
 * @param m Any messages generated during the operation
 * @return true if successful, false otherwise
 */
bool squirrel::AddFilesToArchive(QStringList filePaths, QStringList compressedFilePaths, QString archivePath, QString &m) {
    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            editor.setUpdateMode(UpdateMode::Update);
            editor.setProgressCallback(progressCallback);
            editor.setTotalCallback(totalArchiveSizeCallback);
            for (int i=0; i<filePaths.size(); i++) {
                std::string filePath = filePaths.at(i).toStdString();
                std::string compressedPath = compressedFilePaths.at(i).toStdString();
                editor.addFile(filePath, compressedPath);
            }
            editor.applyChanges();
        }
        else {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::SevenZip);
            editor.setUpdateMode(UpdateMode::Update);
            editor.setSolidMode(false);
            editor.setProgressCallback(progressCallback);
            editor.setTotalCallback(totalArchiveSizeCallback);
            for (int i=0; i<filePaths.size(); i++) {
                std::string filePath = filePaths.at(i).toStdString();
                std::string compressedPath = compressedFilePaths.at(i).toStdString();
                editor.addFile(filePath, compressedPath);
            }
            editor.applyChanges();
        }
        m = "Successfully added/updated file(s) to archive [" + archivePath + "]";
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to add/update files into archive using bit7z library [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- RemoveDirectoryFromArchive --------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Recursively remove a directory from an archive
 * @param compressedDirPath The path to delete
 * @param archivePath Path to the archive
 * @param m Any messages generated during he operation
 * @return true if successful, false otherwise
 */
bool squirrel::RemoveDirectoryFromArchive(QString compressedDirPath, QString archivePath, QString &m) {
    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());

        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            /* first, get the index of the directory to remove */
            std::vector<uint32_t> indexes;
            BitArchiveReader reader(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            for (const auto& item : reader) {
                QString archivedPath = QString::fromStdString(item.path());
                if (archivedPath.startsWith(compressedDirPath)) {
                    indexes.push_back(item.index());
                }
            }

            /* next, remove the item from the archive and apply changes */
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            editor.setUpdateMode(UpdateMode::Update);
            for (uint32_t index : indexes) {
                editor.deleteItem(index);
            }
            editor.applyChanges();
        }
        else {
            /* first, get the index of the directory to remove */
            std::vector<uint32_t> indexes;
            BitArchiveReader reader(lib, archivePath.toStdString(), bit7z::BitFormat::SevenZip);
            for (const auto& item : reader) {
                QString archivedPath = QString::fromStdString(item.path());
                if (archivedPath.startsWith(compressedDirPath)) {
                    indexes.push_back(item.index());
                }
            }

            /* next, remove the item from the archive and apply changes */
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::SevenZip);
            editor.setUpdateMode(UpdateMode::Update);
            for (uint32_t index : indexes) {
                editor.deleteItem(index);
            }
            editor.applyChanges();
        }
        m = "Successfully removed file(s) from archive [" + archivePath + "]";
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to remove files from archive using bit7z library [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- UpdateMemoryFileToArchive ---------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Update a single file, from memory, into an existing archive
 * @param file File contents as a QString
 * @param compressedFilePath File path within the archive
 * @param archivePath Path to the archive
 * @param m Any messages generated during the operation
 * @return true if successful, false otherwise
 */
bool squirrel::UpdateMemoryFileToArchive(QString file, QString compressedFilePath, QString archivePath, QString &m) {
    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());
        /* convert the QString to a istream */
        std::istringstream i(file.toStdString());

        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            editor.setUpdateMode(UpdateMode::Update);
            editor.setProgressCallback(progressCallback);
            editor.setTotalCallback(totalArchiveSizeCallback);
            editor.updateItem(compressedFilePath.toStdString(), i);
            editor.applyChanges();
        }
        else {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::SevenZip);
            editor.setUpdateMode(UpdateMode::Update);
            editor.setSolidMode(false);
            editor.setProgressCallback(progressCallback);
            editor.setTotalCallback(totalArchiveSizeCallback);
            editor.updateItem(compressedFilePath.toStdString(), i);
            editor.applyChanges();
        }
        m = "Successfully compressed memory file to archive [" + archivePath + "]";
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to compress directory into archive using bit7z library [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- ExtractArchiveToDirectory ---------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Extract an entire archive to a specified directory
 * @param archivePath Path to the archive
 * @return true if successful, false otherwise
 */
bool squirrel::ExtractArchiveToDirectory(QString archivePath, QString destinationPath, QString &m) {

    QString systemstring = QString("7za x -y %1 -o%2").arg(archivePath).arg(destinationPath);
    m += systemstring + "\n";
    Log(QString("Extracting %1 to %2").arg(archivePath).arg(destinationPath));
    Log(utils::SystemCommand(systemstring));
    if (utils::FileExists(destinationPath)) {
        m += destinationPath + " exists\n";
        return true;
    }
    else {
        m += destinationPath + " does not exist\n";
        return false;
    }

    // try {
    //     using namespace bit7z;
    //     Bit7zLibrary lib(p7zipLibPath.toStdString());

    //     if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
    //         //bit7z::BitArchiveReader archive(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
    //         //archive.setProgressCallback(progressCallback);
    //         //archive.setTotalCallback(totalArchiveSizeCallback);
    //         //archive.extractTo(destinationPath.toStdString());

    //         bit7z::BitFileExtractor extractor(lib, bit7z::BitFormat::Zip);
    //         extractor.setProgressCallback(progressCallback);
    //         extractor.setTotalCallback(totalArchiveSizeCallback);
    //         extractor.extract(archivePath.toStdString(), destinationPath.toStdString());
    //     }
    //     else {
    //         //bit7z::BitArchiveReader archive(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
    //         //archive.setProgressCallback(progressCallback);
    //         //archive.setTotalCallback(totalArchiveSizeCallback);
    //         //archive.extractTo(destinationPath.toStdString());

    //         bit7z::BitFileExtractor extractor(lib, bit7z::BitFormat::SevenZip);
    //         extractor.setProgressCallback(progressCallback);
    //         extractor.setTotalCallback(totalArchiveSizeCallback);
    //         extractor.extract(archivePath.toStdString(), destinationPath.toStdString());
    //    }
    //    m = "Successfully extracted archive [" + archivePath + "] to directory [" + destinationPath + "]";
    //    return true;
    //}
    //catch ( const bit7z::BitException& ex ) {
    //    /* Do something with ex.what()...*/
    //    m = "Unable to extract archive to directory using bit7z library [" + QString(ex.what()) + "]";
    //    return false;
    //}
}


/* ------------------------------------------------------------ */
/* ----- GetArchiveFileListing -------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::GetArchiveFileListing(QString archivePath, QString subDir, QStringList &files, QString &m) {
    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());

        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitArchiveReader reader(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            for (const auto& item : reader) {
                QString archivedPath = QString::fromStdString(item.path());
                if (archivedPath.startsWith(subDir)) {
                    files.append(QString::fromStdString(item.path()));
                }
            }
        }
        else {
            /* first, get the index of the directory to remove */
            BitArchiveReader reader(lib, archivePath.toStdString(), bit7z::BitFormat::SevenZip);
            for (const auto& item : reader) {
                QString archivedPath = QString::fromStdString(item.path());
                if (archivedPath.startsWith(subDir)) {
                    files.append(QString::fromStdString(item.path()));
                }
            }
        }
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to get subdirectory listing using bit7z library [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- SetDebugSQL ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Set the option to print SQL debugging statements
 * @param d `true` to print debug statements, `false` otherwise
 */
void squirrel::SetDebugSQL(bool d) {
    debugSQL = d;

    if (debugSQL)
        Log("DebugSQL set to ON");
    else
        Log("DebugSQL set to OFF");
}


/* ------------------------------------------------------------ */
/* ----- SetDebug --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Set the option to print debugging information
 * @param d `true` to print debug information, `false` otherwise
 */
void squirrel::SetDebug(bool d) {
    debug = d;

    if (debug)
        Log("Debug set to ON");
    else
        Log("Debug set to OFF");
}


/* ------------------------------------------------------------ */
/* ----- SetOverwritePackage ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Set the option to overwrite an existing package
 * @param o `true` to overwrite an existing package, `false` otherwise
 */
void squirrel::SetOverwritePackage(bool o) {
    overwritePackage = o;

    if (overwritePackage)
        Log("OverwritePackage set to ON");
    else
        Log("OverwritePackage set to OFF");
}


/* ------------------------------------------------------------ */
/* ----- SetQuickRead ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Set the option to do a quick read (*not* reading the params.json files) or not
 * @param q `true` to perform a quick read, `false` otherwise
 */
void squirrel::SetQuickRead(bool q) {
    quickRead = q;

    if (quickRead)
        Log("QuickRead set to ON (params.json files will NOT be read)");
    else
        Log("QuickRead set to OFF (params.json files will be read)");
}


/* ------------------------------------------------------------ */
/* ----- SetSystemTempDir ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Allow a different temp directory to be used. Sometimes /tmp
 * is on a small partition, and creating a squirrel file of several hundred
 * GB may fill up the root partition
 * @param tmpdir The system temp directory to use
 */
void squirrel::SetSystemTempDir(QString tmpdir) {
    tmpdir = tmpdir.trimmed();

    if (tmpdir == "") {
        systemTempDir = QDir::tempPath();
        Log("Specified systemTempDir is blank, using default of " + QDir::tempPath());
    }
    else {
        if (QFile::exists(tmpdir)) {
            systemTempDir = tmpdir;
            Log("Using systemTempDir [" + tmpdir + "]");
        }
        else {
            systemTempDir = QDir::tempPath();
            Log("Specified systemTempDir [" + tmpdir + "] does not exist, using default of " + QDir::tempPath());
        }
    }
}


/* ------------------------------------------------------------ */
/* ----- GetSystemTempDir ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the previously specified system temp dir [using SetSystemTempDir()], or the default temp dir if blank
 * @return the temp dir
 */
QString squirrel::GetSystemTempDir() {

    if (systemTempDir == "")
        return QDir::tempPath();
    else
        return systemTempDir;
}


/* ------------------------------------------------------------ */
/* ----- GetJsonHeader ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the contents of the JSON header
 * @param jdoc a QJsonDocument object containing the header
 * @return `true` if successful, `false` otherwise
 */
bool squirrel::GetJsonHeader(QJsonDocument &jdoc) {

    QString jsonstr;
    if (!ExtractArchiveFileToMemory(GetPackagePath(), "squirrel.json", jsonstr)) {
        Log("Error reading squirrel package. Unable to find squirrel.json");
        utils::Print("Error reading squirrel package. Unable to find squirrel.json");
        return false;
    }
    else {
        Log(QString("Extracted package header [%1]").arg(utils::HumanReadableSize(jsonstr.size())));
    }

    jdoc = QJsonDocument::fromJson(jsonstr.toUtf8());
    return true;
}


/* ------------------------------------------------------------ */
/* ----- UpdateJsonHeader ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Update the JSON package header
 * @param json QString with the contents of the entire JSON formatted file to be written
 * @return `true` if successful, `false` otherwise
 */
bool squirrel::UpdateJsonHeader(QString json) {
    QString m;
    if (!UpdateMemoryFileToArchive(json, "squirrel.json", GetPackagePath(), m))
        Log("Error [" + m + "] compressing memory file to archive");

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ExtractArchiveFilesToDirectory ----------------------- */
/* ------------------------------------------------------------ */
bool squirrel::ExtractArchiveFilesToDirectory(QString archivePath, QString filePattern, QString outDir, QString &m) {
    utils::Print(QString("Attempting to extract files [%1] from archive [%2] to path [%3]").arg(filePattern).arg(archivePath).arg(outDir));
    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitFileExtractor extractor(lib, BitFormat::Zip);
            //extractor.setProgressCallback(progressCallback);
            //extractor.setTotalCallback(totalArchiveSizeCallback);
            Debug(QString("Attempting to extract files [%1] from archive [%2] to path [%3]").arg(filePattern).arg(archivePath).arg(outDir));
            extractor.extractMatching(archivePath.toStdString(), filePattern.toStdString(), outDir.toStdString());
        }
        else {
            BitFileExtractor extractor(lib, BitFormat::SevenZip);
            //extractor.setProgressCallback(progressCallback);
            //extractor.setTotalCallback(totalArchiveSizeCallback);
            Debug(QString("Attempting to extract files [%1] from archive [%2] to path [%3]").arg(filePattern).arg(archivePath).arg(outDir));
            extractor.extractMatching(archivePath.toStdString(), filePattern.toStdString(), outDir.toStdString());
        }
        m = QString("Extracted files [%1] from archive [%2] to directory [%3]...").arg(filePattern).arg(archivePath).arg(outDir);
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to extract files from archive using bit7z library [" + QString(ex.what()) + "]";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- GetFreeDiskSpace ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Gets the free disk space where the current package lives on disk
 * @return disk free space in bytes
 */
qint64 squirrel::GetFreeDiskSpace() {
    QStorageInfo storage = QStorageInfo::root();
    storage.setPath(systemTempDir);

    if (storage.isReadOnly())
        return 0;
    else
        return storage.bytesAvailable();
}


/* ------------------------------------------------------------ */
/* ----- ObjectTypeToString ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the string object type from an enum
 * @param object enum representation of the object type
 * @return the `QString` object type
 */
QString squirrel::ObjectTypeToString(ObjectType object) {
    switch (object) {
        case Analysis: return "Analysis"; break;
        case BehSeries: return "BehSeries"; break;
        case DataDictionary: return "DataDictionary"; break;
        case DataDictionaryItem: return "DataDictionaryItem"; break;
        case Experiment: return "Experiment"; break;
        case GroupAnalysis: return "GroupAnalysis"; break;
        case Intervention: return "Intervention"; break;
        case Observation: return "Observation"; break;
        case Pipeline: return "Pipeline"; break;
        case Series: return "Series"; break;
        case Study: return "Study"; break;
        case Subject: return "Subject"; break;
        default: return "UnknownObjectType";
    }
}


/* ------------------------------------------------------------ */
/* ----- ObjectTypeToEnum ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get the enum object type from a string
 * @param object QString representation of the object type
 * @return the `enum` object type
 */
ObjectType squirrel::ObjectTypeToEnum(QString object) {
    object.replace("-", "");
    if (object.toLower() == "analysis") { return Analysis; }
    if (object.toLower() == "behseries") { return BehSeries; }
    if (object.toLower() == "datadictionary") { return DataDictionary; }
    if (object.toLower() == "datadictionaryitem") { return DataDictionaryItem; }
    if (object.toLower() == "experiment") { return Experiment; }
    if (object.toLower() == "groupanalysis") { return GroupAnalysis; }
    if (object.toLower() == "intervention") { return Intervention; }
    if (object.toLower() == "observation") { return Observation; }
    if (object.toLower() == "pipeline") { return Pipeline; }
    if (object.toLower() == "series") { return Series; }
    if (object.toLower() == "study") { return Study; }
    if (object.toLower() == "subject") { return Subject; }

    return UnknownObjectType;
}
