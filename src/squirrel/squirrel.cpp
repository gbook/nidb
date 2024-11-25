/* ------------------------------------------------------------------------------
  Squirrel squirrel.cpp
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

#include "squirrel.h"
#include "squirrelImageIO.h"
#include "utils.h"
#include "squirrel.sql.h"
#include "bit7z.hpp"
#include "bitarchivewriter.hpp"
#include "bitarchiveeditor.hpp"
#include "bitfileextractor.hpp"

qint64 totalbytes(0);
double blocksize(0.0);
qint64 lastupdate(0);

bool totalArchiveSizeCallback(qint64 val) {
    //utils::Print(QString("Total package size in bytes [%1]").arg(val));
    //utils::Print("Total package size is [" + utils::HumanReadableSize(val) + "]");
    totalbytes = val;
    blocksize = (double)totalbytes/100.0;
    return true;
}

bool progressCallback(qint64 val) {
    if (val > (lastupdate+blocksize)) {
        double percent = ((double)val/(double)totalbytes)*100.0;
        //printf("%.0f%% (%lld of %lld bytes)\n", percent, val, totalbytes);
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

    if (!DatabaseConnect()) {
        Log(QString("Error connecting to database. Unable to initilize squirrel library"), __FUNCTION__);
        isValid = false;
    }

    if (!InitializeDatabase()) {
        Log(QString("Error connecting to database. Unable to initilize squirrel library"), __FUNCTION__);
        isValid = false;
    }

    if (!Get7zipLibPath()) {
        Log(QString("7-zip library not found. Unable to initilize squirrel library"), __FUNCTION__);
        utils::Print(QString("7-zip library not found. Unable to initilize squirrel library"));
        isValid = false;
    }

    Log(QString("Created squirrel object."), __FUNCTION__);
    if (debug)
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
            Log(QString("Error removing working directory [%1]. Message [%2]").arg(workingDir).arg(m), __FUNCTION__);
    }
}


/* ---------------------------------------------------------- */
/* --------- Get7zipLibPath --------------------------------- */
/* ---------------------------------------------------------- */
bool squirrel::Get7zipLibPath() {
#ifdef Q_OS_WINDOWS
    if (QFile::exists("C:/Program Files/7-Zip/7z.dll")) {
        p7zipLibPath = "C:/Program Files/7-Zip/7z.dll";
        Log("Found 7zip path C:/Program Files/7-Zip/7z.dll", __FUNCTION__);
        return true;
    }
#else
    if (QFile::exists("/usr/libexec/p7zip/7z.so")) {
        p7zipLibPath = "/usr/libexec/p7zip/7z.so";
        Log("Found 7zip path /usr/libexec/p7zip/7z.so", __FUNCTION__);
        return true;
    }
    else if (QFile::exists("/usr/libexec/p7zip/7za.so")) {
        p7zipLibPath = "/usr/libexec/p7zip/7za.so";
        Log("Found 7zip path /usr/libexec/p7zip/7za.so", __FUNCTION__);
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

    db = QSqlDatabase::addDatabase("QSQLITE", "squirrel");
    if (!db.isValid()) {
        Log(QString("Error initializing SQLite database (likely driver related) [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()), __FUNCTION__);
        utils::Print(QString("Error initializing SQLite database (likely driver related) [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()));
        return false;
    }

    if (debug) {
        QFile::remove("/tmp/sqlite.db");
        db.setDatabaseName("/tmp/sqlite.db");
    }
    else
        db.setDatabaseName(":memory:");

    if (db.open()) {
        Debug(QString("Successfuly opened SQLite database [%1]").arg(db.databaseName()), __FUNCTION__);
        return true;
    }
    else {
        Log(QString("Error opening SQLite database [%1]. Error [%2]").arg(db.databaseName()).arg(db.lastError().text()), __FUNCTION__);
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

    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* NOTE - SQLite does not support multiple statements, so each table needs to be created individualy */

    q.prepare(tableAnalysis);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Analysis]", __FUNCTION__); utils::Print("Error creating table [Analysis]"); return false; }

    q.prepare(tableIntervention);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Intervention]", __FUNCTION__); utils::Print("Error creating table [Intervention]"); return false; }

    q.prepare(tableDataDictionary);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [DataDictionary]", __FUNCTION__); utils::Print("Error creating table [DataDictionary]"); return false; }

    q.prepare(tableDataDictionaryItems);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [DataDictionaryItems]", __FUNCTION__); utils::Print("Error creating table [DataDictionaryItems]"); return false; }

    q.prepare(tableExperiment);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Experiment]", __FUNCTION__); utils::Print("Error creating table [Experiment]"); return false; }

    q.prepare(tableGroupAnalysis);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [GroupAnalysis]", __FUNCTION__); utils::Print("Error creating table [GroupAnalysis]"); return false; }

    q.prepare(tableObservation);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Observation]", __FUNCTION__); utils::Print("Error creating table [Observation]"); return false; }

    q.prepare(tablePackage);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Package]", __FUNCTION__); utils::Print("Error creating table [Package]"); return false; }

    q.prepare(tableParams);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Params]", __FUNCTION__); utils::Print("Error creating table [Params]"); return false; }

    q.prepare(tablePipeline);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Pipeline]", __FUNCTION__); utils::Print("Error creating table [Pipeline]"); return false; }

    q.prepare(tablePipelineDataStep);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [PipelineDataStep]", __FUNCTION__); utils::Print("Error creating table [PipelineDataStep]"); return false; }

    q.prepare(tableSeries);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Series]", __FUNCTION__); utils::Print("Error creating table [Series]"); return false; }

    q.prepare(tableStudy);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Study]", __FUNCTION__); utils::Print("Error creating table [Study]"); return false; }

    q.prepare(tableSubject);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Subject]", __FUNCTION__); utils::Print("Error creating table [Subject]"); return false; }

    q.prepare(tableStagedFiles);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [StagedFiles]", __FUNCTION__); utils::Print("Error creating table [StagedFiles]"); return false; }

    q.prepare("PRAGMA journal_mode=WAL");
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error setting journal_mode=WAL", __FUNCTION__); utils::Print("Error setting journal_mode=WAL"); return false; }

    q.prepare("PRAGMA synchronous=NORMAL");
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error setting synchronous=NORMAL", __FUNCTION__); utils::Print("Error setting synchronous=NORMAL"); return false; }

    Log("Successfully initialized database tables", __FUNCTION__);
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

    Log(QString("Reading squirrel file [%1]").arg(GetPackagePath()), __FUNCTION__);
    utils::Print(QString("Reading squirrel file [%1]").arg(GetPackagePath()), __FUNCTION__);

    /* check if file exists */
    if (!utils::FileExists(GetPackagePath())) {
        Log(QString("File %1 does not exist").arg(GetPackagePath()), __FUNCTION__);
        utils::Print(QString("File %1 does not exist").arg(GetPackagePath()));
        return false;
    }

    QString jsonstr;
    if (!ExtractFileFromArchive(GetPackagePath(), "squirrel.json", jsonstr)) {
        Log(QString("Error reading squirrel package. Unable to find squirrel.json"), __FUNCTION__);
        utils::Print(QString("Error reading squirrel package. Unable to find squirrel.json"));
        return false;
    }
    else {
        Log(QString("Extracted package header [%1]").arg(utils::HumanReadableSize(jsonstr.size())), __FUNCTION__);
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
        Log(QString("Found [%1] subjects").arg(numSubjects), __FUNCTION__);
    }
    else if (root.contains("subjects")) {
        jsonSubjects = root["subjects"].toArray();
        Log(QString("NOTICE: Found [%1] subjects in the root of the JSON. (This is a slightly malformed squirrel file, but I'll accept it)").arg(jsonSubjects.size()), __FUNCTION__);
    }
    else {
        Log("root JSON object does not contain 'data' or 'subjects'", __FUNCTION__);
    }

    Debug(QString("TotalFileCount: [%1]").arg(root["TotalFileCount"].toInt()), __FUNCTION__);
    Debug(QString("TotalSize: [%1]").arg(root["TotalSize"].toInt()), __FUNCTION__);

    /* loop through and read any subjects */
    utils::Print(QString("Reading %1 subjects...").arg(jsonSubjects.size()));
    qint64 i(0);
    for (auto a : jsonSubjects) {
        i++;
        Log(QString("Reading subject %1 of %2 - %3").arg(i).arg(jsonSubjects.size()).arg(QDateTime::currentDateTime().toString("yyyy/MM/dd hh:mm:ss.zzz")), __FUNCTION__);
        //utils::Print(QString("Reading subject %1 of %2 - %3").arg(i).arg(jsonSubjects.size()).arg(QDateTime::currentDateTime().toString("yyyy/MM/dd hh:mm:ss.zzz")), __FUNCTION__);
        utils::PrintProgress((double)i/(double)jsonSubjects.size());

        QJsonObject jsonSubject = a.toObject();

        squirrelSubject sqrlSubject;
        sqrlSubject.ID = jsonSubject["SubjectID"].toString();
        sqrlSubject.AlternateIDs = jsonSubject["AlternateIDs"].toVariant().toStringList();
        sqrlSubject.GUID = jsonSubject["GUID"].toString();
        sqrlSubject.DateOfBirth = QDate::fromString(jsonSubject["DateOfBirth"].toString(), "yyyy-MM-dd");
        sqrlSubject.Sex = jsonSubject["Sex"].toString();
        sqrlSubject.Gender = jsonSubject["Gender"].toString();
        sqrlSubject.Ethnicity1 = jsonSubject["Ethnicity1"].toString();
        sqrlSubject.Ethnicity2 = jsonSubject["Ethnicity2"].toString();
        sqrlSubject.Store();
        qint64 subjectRowID = sqrlSubject.GetObjectID();

        //Log(QString("Reading subject [%1]").arg(sqrlSubject.ID), __FUNCTION__);

        /* loop through and read all studies */
        QJsonArray jsonStudies = jsonSubject["studies"].toArray();
        for (auto b : jsonStudies) {
            QJsonObject jsonStudy = b.toObject();
            squirrelStudy sqrlStudy;

            sqrlStudy.AgeAtStudy = jsonStudy["AgeAtStudy"].toDouble();
            sqrlStudy.DateTime = utils::StringToDatetime(jsonStudy["StudyDatetime"].toString());
            sqrlStudy.DayNumber = jsonStudy["DayNumber"].toInt();
            sqrlStudy.Description = jsonStudy["Description"].toString();
            sqrlStudy.Equipment = jsonStudy["Equipment"].toString();
            sqrlStudy.Height = jsonStudy["Height"].toDouble();
            sqrlStudy.Modality = jsonStudy["Modality"].toString();
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
                squirrelSeries sqrlSeries;

                sqrlSeries.BIDSEntity = jsonSeries["BIDSEntity"].toString();
                sqrlSeries.BIDSPhaseEncodingDirection = jsonSeries["BIDSPhaseEncodingDirection"].toString();
                sqrlSeries.BIDSRun = jsonSeries["BIDSRun"].toString();
                sqrlSeries.BIDSSuffix = jsonSeries["BIDSSuffix"].toString();
                sqrlSeries.BIDSTask = jsonSeries["BIDSTask"].toString();
                sqrlSeries.BehavioralFileCount = jsonSeries["BehavioralFileCount"].toInteger();
                sqrlSeries.BehavioralSize = jsonSeries["BehavioralSize"].toInteger();
                sqrlSeries.DateTime = utils::StringToDatetime(jsonStudy["SeriesDatetime"].toString());
                sqrlSeries.Description = jsonSeries["Description"].toString();
                sqrlSeries.FileCount = jsonSeries["FileCount"].toInteger();
                sqrlSeries.Protocol = jsonSeries["Protocol"].toString();
                sqrlSeries.SeriesNumber = jsonSeries["SeriesNumber"].toInteger();
                sqrlSeries.SeriesUID = jsonSeries["SeriesUID"].toString();
                sqrlSeries.Size = jsonSeries["Size"].toInteger();
                sqrlSeries.studyRowID = studyRowID;

                Debug(QString("Reading series [%1][%2][%3]").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber), __FUNCTION__);

                if (!quickRead) {
                    /* read any params from the data/Subject/Study/Series/params.json file */
                    QString parms;
                    QString paramsfilepath = QString("data/%2/%3/%4/params.json").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber);
                    if (ExtractFileFromArchive(GetPackagePath(), paramsfilepath, parms)) {
                        sqrlSeries.params = ReadParamsFile(parms);
                    }
                    else {
                        Log("Unable to read params file [" + paramsfilepath + "]", __FUNCTION__);
                    }
                }

                sqrlSeries.Store();
            }

            /* loop through and read all analyses */
            QJsonArray jsonAnalyses = jsonStudy["analyses"].toArray();
            for (auto d : jsonAnalyses) {
                QJsonObject jsonAnalysis = d.toObject();
                squirrelAnalysis sqrlAnalysis;
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
        QSqlQuery q1(QSqlDatabase::database("squirrel")); /* start a transaction to slightly improve SQL insert performance */
        q1.prepare("begin transaction");
        utils::SQLQuery(q1, __FUNCTION__, __FILE__, __LINE__);

        QJsonArray jsonObservations = jsonSubject["observations"].toArray();
        Debug(QString("Reading [%1] observations").arg(jsonObservations.size()), __FUNCTION__);
        for (auto e : jsonObservations) {

            QJsonObject jsonObservation = e.toObject();
            squirrelObservation sqrlObservation;
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
            squirrelIntervention sqrlIntervention;
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
        squirrelExperiment sqrlExperiment;

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
        squirrelPipeline sqrlPipeline;

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
bool squirrel::Write(bool writeLog) {

    /* create the log file */
    QFileInfo finfo(GetPackagePath());
    logfile = QString(finfo.absolutePath() + "/squirrel-" + utils::CreateLogDate() + ".log");

    PrintPackage();

    if (fileMode == NewPackage) {
        MakeTempDir(workingDir);
        Log(QString("Writing NEW squirrel package: workingdir [%1]  packagePath [%2]").arg(workingDir).arg(GetPackagePath()), __FUNCTION__);
    }
    else {
        Log(QString("Updating existing squirrel package [%1]").arg(GetPackagePath()), __FUNCTION__);
    }

    pairList stagedFiles;

    /* ----- 1) Write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetSubjectList();
    foreach (squirrelSubject subject, subjects) {
        qint64 subjectRowID = subject.GetObjectID();
        Log(QString("Writing subject [%1] to virtualPath [%2]").arg(subject.ID).arg(subject.VirtualPath()), __FUNCTION__);

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudyList(subjectRowID);
        foreach (squirrelStudy study, studies) {
            qint64 studyRowID = study.GetObjectID();
            Log(QString("Writing study [%1] to virtualPath [%2]").arg(study.StudyNumber).arg(study.VirtualPath()), __FUNCTION__);

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeriesList(studyRowID);
            Log(QString("Writing [%1] series for [%2][%3]").arg(serieses.size()).arg(subject.ID).arg(study.StudyNumber), __FUNCTION__);
            foreach (squirrelSeries series, serieses) {
                QString m;
                QString seriesPath = QString("%1/%2").arg(workingDir).arg(series.VirtualPath());

                if (fileMode == FileMode::NewPackage) {
                    utils::MakePath(seriesPath,m);
                    Log(QString("Writing Subject-Study-Series [%1-%2-%3] to tmpdir [%4]. Data format [%5]").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber).arg(seriesPath).arg(DataFormat), __FUNCTION__);

                    /* orig vs other formats */
                    if (DataFormat == "orig") {
                        Debug(QString("Export data format is 'orig'. Copying [%1] files...").arg(series.stagedFiles.size()), __FUNCTION__);
                        /* copy all of the series files to the temp directory */
                        foreach (QString f, series.stagedFiles) {
                            QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                            Log(QString("  ... copying original files from %1 to %2").arg(f).arg(seriesPath), __FUNCTION__);
                            Debug(utils::SystemCommand(systemstring), __FUNCTION__);
                        }
                    }
                    else if (study.Modality.toUpper() != "MR") {
                        Debug(QString("Study modality is [%1]. Copying files...").arg(study.Modality.toUpper()), __FUNCTION__);
                        /* copy all of the series files to the temp directory */
                        foreach (QString f, series.stagedFiles) {
                            QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                            Log(QString("  ... copying files from %1 to %2").arg(f).arg(seriesPath), __FUNCTION__);
                            Debug(utils::SystemCommand(systemstring), __FUNCTION__);
                        }
                    }
                    else if ((DataFormat == "anon") || (DataFormat == "anonfull")) {
                        /* create temp directory for the anonymization */
                        QString td;
                        if (MakeTempDir(td)) {
                            /* copy all files to temp directory */
                            QString systemstring;
                            foreach (QString f, series.stagedFiles) {
                                systemstring = QString("cp -uv %1 %2").arg(f).arg(td);
                                Debug(utils::SystemCommand(systemstring), __FUNCTION__);
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
                            Log(QString("  ... anonymizing DICOM files from %1 to %2").arg(td).arg(seriesPath), __FUNCTION__);
                            Debug(utils::SystemCommand(systemstring), __FUNCTION__);

                            /* delete temp directory */
                            DeleteTempDir(td);
                        }
                        else
                            Log("Error creating temp directory for DICOM anonymization", __FUNCTION__);
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
                            Log(QString(" ... converting %1 files to nifti").arg(series.stagedFiles.size()), __FUNCTION__);

                            QFileInfo f(series.stagedFiles[0]);
                            QString origSeriesPath = f.absoluteDir().absolutePath();
                            squirrelImageIO io;
                            QString m3;
                            if (io.ConvertDicom(DataFormat, origSeriesPath, seriesPath, QDir::currentPath(), gzip, utils::CleanString(subject.ID), QString("%1").arg(study.StudyNumber), QString("%1").arg(series.SeriesNumber), "dicom", numConv, numRename, m3))
                                Debug(QString("ConvertDicom() returned [%1]").arg(m3), __FUNCTION__);
                            else
                                Log(QString("ConvertDicom() failed. Returned [%1]").arg(m3), __FUNCTION__);
                        }
                        else {
                            Log(QString("Variable squirrelSeries.stagedFiles is empty. No files to convert to Nifti"), __FUNCTION__);
                        }
                    }
                    else
                        Log(QString("DataFormat [%1] not recognized").arg(DataFormat), __FUNCTION__);

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
                        Log("Error writing [" + paramFilePath + "]", __FUNCTION__);
                }
            }
        }
    }

    /* ----- 2) write .json file ----- */
    Log("Creating JSON file...", __FUNCTION__);
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
    foreach (squirrelSubject subject, subjectses) {
        JSONsubjects.append(subject.ToJSON());
    }

    /* add group-analyses */
    QList <squirrelGroupAnalysis> groupAnalyses = GetGroupAnalysisList();
    if (groupAnalyses.size() > 0) {
        Log(QString("Adding %1 group-analyses...").arg(groupAnalyses.size()), __FUNCTION__);
        QJsonArray JSONgroupanalyses;
        foreach (squirrelGroupAnalysis g, groupAnalyses) {
            if (g.Get()) {
                JSONgroupanalyses.append(g.ToJSON());
                stagedFiles += g.GetStagedFileList();
                Log(QString("Added group-analysis [%1]").arg(g.GroupAnalysisName), __FUNCTION__);
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
        Log(QString("Adding %1 pipelines...").arg(pipelines.size()), __FUNCTION__);
        QJsonArray JSONpipelines;
        foreach (squirrelPipeline p, pipelines) {
            if (p.Get()) {
                JSONpipelines.append(p.ToJSON(workingDir));
                stagedFiles += p.GetStagedFileList();
                Log(QString("Added pipeline [%1]").arg(p.PipelineName), __FUNCTION__);
            }
        }
        root["PipelineCount"] = JSONpipelines.size();
        root["pipelines"] = JSONpipelines;
    }

    /* add experiments */
    QList <squirrelExperiment> exps = GetExperimentList();
    if (exps.size() > 0) {
        Log(QString("Adding %1 experiments...").arg(exps.size()), __FUNCTION__);
        QJsonArray JSONexperiments;
        foreach (squirrelExperiment e, exps) {
            if (e.Get()) {
                JSONexperiments.append(e.ToJSON());
                stagedFiles += e.GetStagedFileList();
                Log(QString("Added experiment [%1]").arg(e.ExperimentName), __FUNCTION__);
            }
        }
        root["ExperimentCount"] = JSONexperiments.size();
        root["experiments"] = JSONexperiments;
    }

    /* add data-dictionary */
    QList <squirrelDataDictionary> dicts = GetDataDictionaryList();
    if (dicts.size() > 0) {
        Log(QString("Adding %1 data-dictionaries...").arg(dicts.size()), __FUNCTION__);
        QJsonArray JSONdataDictionaries;
        foreach (squirrelDataDictionary d, dicts) {
            if (d.Get()) {
                JSONdataDictionaries.append(d.ToJSON());
                stagedFiles += d.GetStagedFileList();
                Log("Added data-dictionary", __FUNCTION__);
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
        Debug(QString("stagedFiles size is [%1]").arg(stagedFiles.size()), __FUNCTION__);
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
                Log(QString("Error creating directory [%1] - message [%2]").arg(destDir).arg(m), __FUNCTION__);
            else
                Debug(QString("Successfully created directory [%1] - message [%2]").arg(destDir).arg(m), __FUNCTION__);

            Debug(QString("Copying [%1] to [%2]").arg(sourcePath).arg(destPath), __FUNCTION__);
            if (!QFile::copy(sourcePath, destPath))
                Log(QString("Error copying [%1] to [%2]").arg(sourcePath).arg(destPath), __FUNCTION__);
        }

        Log("Zipping the archive from a temp directory", __FUNCTION__);

        /* write the .json file to the temp dir */
        QString jsonFilePath = workingDir + "/squirrel.json";
        if (!utils::WriteTextFile(jsonFilePath, j))
            Log("Error writing [" + jsonFilePath + "]", __FUNCTION__);

        QString m;
        if (CompressDirectoryToArchive(workingDir, GetPackagePath(), m)) {
            QFileInfo fi(GetPackagePath());
            qint64 zipSize = fi.size();
            Log(QString("Finished zipping package [%1]. Size is [%2] bytes").arg(GetPackagePath()).arg(zipSize), __FUNCTION__);

            /* delete the squirrel temp dir */
            DeleteTempDir(workingDir);
        }
        else {
            Log("Error creating zip file [" + GetPackagePath() + "]  message [" + m + "]", __FUNCTION__);
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
        Log("Adding/updating files in existing package", __FUNCTION__);
        QString m;
        if (!AddFilesToArchive(diskPaths, archivePaths, GetPackagePath(), m))
            Log("Error [" + m + "] adding file(s) to archive", __FUNCTION__);

        /* update the package in place with the new .json file */
        Log("Updating existing package", __FUNCTION__);
        if (!UpdateMemoryFileToArchive(j, "squirrel.json", GetPackagePath(), m)) {
            Log("Error [" + m + "] compressing memory file to archive", __FUNCTION__);
        }
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
            str += study.PrintStudy();

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeriesList(studyRowID);
            foreach (squirrelSeries series, serieses) {
                str += series.PrintSeries();
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
        QList<squirrelIntervention> Interventions = GetInterventionList(subjectRowID);
        if (detail)
            foreach (squirrelIntervention Intervention, Interventions)
                str += Intervention.PrintIntervention();
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

    QSqlQuery q(QSqlDatabase::database("squirrel"));

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

    QSqlQuery q(QSqlDatabase::database("squirrel"));

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
 * @brief squirrel::GetObjectCount
 * @param object the object to get a count of
 * @return the number of objects
 */
qint64 squirrel::GetObjectCount(QString object) {
    qint64 count(0);
    QString table;

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    if (object == "subject") table = "Subject";
    else if (object == "study") table = "Study";
    else if (object == "series") table = "Series";
    else if (object == "analysis") table = "Analysis";
    else if (object == "datadictionary") table = "DataDictionary";
    else if (object == "datadictionaryitem") table = "DataDictionaryItems";
    else if (object == "Intervention") table = "Intervention";
    else if (object == "observation") table = "Observation";
    else if (object == "pipeline") table = "Pipeline";
    else if (object == "groupanalysis") table = "GroupAnalysis";
    else if (object == "experiment") table = "Experiment";
    else { return -1; }

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

    qint64 numSubjects = GetObjectCount("subject");
    qint64 numStudies = GetObjectCount("study");
    qint64 numSeries = GetObjectCount("series");
    qint64 numObservations = GetObjectCount("observation");
    qint64 numInterventions = GetObjectCount("Intervention");
    qint64 numAnalyses = GetObjectCount("analysis");
    qint64 numExperiments = GetObjectCount("experiment");
    qint64 numPipelines = GetObjectCount("pipeline");
    qint64 numGroupAnalyses = GetObjectCount("groupanalysis");
    qint64 numDataDictionaries = GetObjectCount("datadictionary");
    //qint64 numDataDictionaryItems = GetObjectCount("datadictionaryitem");

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
    str += utils::Print(QString("  Objects:\n    ├── %1 subjects\n    │  ├── %4 observations\n    │  ├── %5 Interventions\n    │  ├── %2 studies\n    │  ├──── %3 series\n    │  └──── %6 analyses\n    ├── %7 experiments\n    ├── %8 pipelines\n    ├── %9 group analyses\n    └── %10 data dictionary").arg(numSubjects).arg(numStudies).arg(numSeries).arg(numObservations).arg(numInterventions).arg(numAnalyses).arg(numExperiments).arg(numPipelines).arg(numGroupAnalyses).arg(numDataDictionaries));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- MakeTempDir ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::MakeTempDir
 * @return true if created/exists, false otherwise
 */
bool squirrel::MakeTempDir(QString &dir) {

    QString d;
    #ifdef Q_OS_WINDOWS
        d = QString("C:/tmp/%1").arg(utils::GenerateRandomString(20));
    #else
    d = QString("/tmp/%1").arg(utils::GenerateRandomString(20));
    #endif

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
 * @brief squirrel::DeleteTempDir
 * @return true if created/exists, false otherwise
 */
bool squirrel::DeleteTempDir(QString dir) {
    /* delete the tmp dir, if it exists */
    if (utils::FileExists(dir)) {
        Debug("Temporary directory [" + dir + "] exists and will be deleted", __FUNCTION__);
        QString m;
        if (!utils::RemoveDir(dir, m)) {
            Log("Error [" + m + "] removing directory [" + dir + "]", __FUNCTION__);
            return false;
        }
    }
    return true;
}


/* ------------------------------------------------------------ */
/* ----- AddStagedFiles --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::AddStagedFiles - add staged files to the database.
 * These are files which will not be copied until the package is written
 * @param objectType one of the object types
 * @param rowid the database row ID of the object
 * @param files the list of files to be staged
 * @return
 */
bool squirrel::AddStagedFiles(QString objectType, qint64 rowid, QStringList files) {

    if (rowid < 0) return false;
    if (files.size() <= 0) return false;

    Debug(QString("Adding [%1] files of type [%2] to rowID [%3]").arg(files.size()).arg(objectType).arg(rowid), __FUNCTION__);

    if (objectType == "series") {
        squirrelSeries s;
        s.SetObjectID(rowid);
        if (s.Get()) {
            foreach (QString f, files) {
                s.stagedFiles.append(f);
                Debug(QString("Appended file [%1] of type [%2] to rowID [%3]. Size is now [%4]").arg(files.size()).arg(objectType).arg(rowid).arg(s.stagedFiles.size()), __FUNCTION__);
            }
            if (s.Store())
                return true;
        }
        else {
            Debug("Unable to get series object. Error [" + s.Error() + "]", __FUNCTION__);
        }
    }

    if (objectType == "experiment") {
        squirrelExperiment e;
        e.SetObjectID(rowid);
        if (e.Get()) {
            foreach (QString f, files)
                e.stagedFiles.append(f);
            if (e.Store())
                return true;
        }
    }

    if (objectType == "pipeline") {
        squirrelPipeline p;
        p.SetObjectID(rowid);
        if (p.Get()) {
            foreach (QString f, files)
                p.stagedFiles.append(f);
            if (p.Store())
                return true;
        }
    }

    if (objectType == "analysis") {
        squirrelAnalysis a;
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
void squirrel::Log(QString s, QString func) {
    if (s.trimmed() != "") {
        log.append(QString("squirrel::%1() %2\n").arg(func).arg(s));
        logBuffer.append(QString("squirrel::%1() %2\n").arg(func).arg(s));
        if (!quiet) {
            utils::Print(QString("squirrel::%1() %2").arg(func).arg(s));
        }
    }
}


/* ------------------------------------------------------------ */
/* ----- Debug ------------------------------------------------ */
/* ------------------------------------------------------------ */
void squirrel::Debug(QString s, QString func) {
    if (debug) {
        if (s.trimmed() != "") {
            log.append(QString("Debug squirrel::%1() %2\n").arg(func).arg(s));
            logBuffer.append(QString("Debug squirrel::%1() %2\n").arg(func).arg(s));
            utils::Print(QString("Debug squirrel::%1() %2").arg(func).arg(s));
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
 * @brief squirrel::PrintSubjects print list of subjects to stdout
 * @param details true to print details, false to print list of subject IDs
 */
QString squirrel::PrintSubjects(PrintingType printType) {
    QString str;

    QList <squirrelSubject> subjects = GetSubjectList();
    int count = subjects.size();
    if (count > 0) {
        if (printType == PrintingType::Details) {
            foreach (squirrelSubject s, subjects) {
                if (s.Get())
                    str += s.PrintDetails();
            }
        }
        else if (printType == PrintingType::CSV) {
            QStringList csvLines;
            foreach (squirrelSubject s, subjects) {
                if (s.Get())
                    csvLines.append(s.CSVLine());
            }
            str += utils::Print("ID, AlternateIDs, DateOfBirth, Ethnicity1, Ethnicity2, GUID, Gender, Sex");
            str += utils::Print(csvLines.join("\n"));
        }
        else if (printType == PrintingType::Tree) {
            str += utils::Print("Subjects");
            int i = 0;
            foreach (squirrelSubject s, subjects) {
                if (s.Get()) {
                    i++;
                    if (count == i)
                        str += s.PrintTree(true);
                    else
                        str += s.PrintTree(false);
                }
            }
        }
        else {
            QStringList subjectIDs;
            foreach (squirrelSubject s, subjects) {
                if (s.Get())
                    subjectIDs.append(s.ID);
            }
            str += utils::Print("Subjects: " + subjectIDs.join(" "));
        }
    }
    else
        str += utils::Print("No subjects in this package");

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintStudies ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintStudies print list of studies to the stdout
 * @param subjectID the subject ID to print studies for
 * @param details true to print details, false to print list of study numbers
 */
QString squirrel::PrintStudies(qint64 subjectRowID, bool details) {
    QString str;

    QList <squirrelStudy> studies = GetStudyList(subjectRowID);
    QStringList studyNumbers;
    foreach (squirrelStudy s, studies) {
        if (s.Get()) {
            if (details)
                str += s.PrintStudy();
            else
                studyNumbers.append(QString("%1").arg(s.StudyNumber));
        }
    }
    if (!details)
        str += utils::Print("Studies: " + studyNumbers.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintSeries ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintSeries print list of series to stdout
 * @param subjectID the subject ID
 * @param studyNum the study number
 * @param details true to print details, false to print list of series numbers
 */
QString squirrel::PrintSeries(qint64 studyRowID, bool details) {
    QString str;

    QList <squirrelSeries> series = GetSeriesList(studyRowID);
    QStringList seriesNumbers;
    foreach (squirrelSeries s, series) {
        if (s.Get()) {
            if (details)
                str += s.PrintSeries();
            else
                seriesNumbers.append(QString("%1").arg(s.SeriesNumber));
        }
    }
    if (!details)
        str += utils::Print("Series: " + seriesNumbers.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintObservations ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print all of the observations for a given subjectRowID
 * @param subjectRowID The subjectRowID
 * @param printType One of the PrintingType enumerations
 */
QString squirrel::PrintObservations(qint64 subjectRowID, PrintingType printType) {
    QString str;

    QList <squirrelObservation> observations = GetObservationList(subjectRowID);
    QStringList observationNames;
    foreach (squirrelObservation o, observations) {
        if (o.Get()) {
            if (printType == PrintingType::Details)
                str += o.PrintObservation();
            else
                observationNames.append(o.ObservationName);
        }
    }
    if (printType == PrintingType::Details)
        str += utils::Print("Observations: " + observationNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintInterventions ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print all of the interventions for a given subjectRowID
 * @param subjectRowID The subjectRowID
 * @param printType One of the PrintingType enumerations
 */
QString squirrel::PrintInterventions(qint64 subjectRowID, PrintingType printType) {
    QString str;

    QList <squirrelIntervention> interventions = GetInterventionList(subjectRowID);
    QStringList interventionNames;
    foreach (squirrelIntervention o, interventions) {
        if (o.Get()) {
            if (printType == PrintingType::Details)
                str += o.PrintIntervention();
            else
                interventionNames.append(o.InterventionName);
        }
    }
    if (printType == PrintingType::Details)
        str += utils::Print("Interventions: " + interventionNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintExperiments ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintExperiments
 * @param details true to print details, false to print list of pipeline names
 */
QString squirrel::PrintExperiments(bool details) {
    QString str;

    QList <squirrelExperiment> exps = GetExperimentList();
    QStringList experimentNames;
    foreach (squirrelExperiment e, exps) {
        if (e.Get()) {
            if (details)
                str += e.PrintExperiment();
            else
                experimentNames.append(e.ExperimentName);
        }
    }
    if (!details)
        str += utils::Print("Experiments: " + experimentNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintPipelines --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintPipelines
 * @param details true to print details, false to print list of pipeline names
 */
QString squirrel::PrintPipelines(bool details) {
    QString str;

    QList <squirrelPipeline> pipelines = GetPipelineList();
    QStringList pipelineNames;
    foreach (squirrelPipeline p, pipelines) {
        if (p.Get()) {
            if (details)
                str += p.PrintPipeline();
            else
                pipelineNames.append(p.PipelineName);
        }
    }
    if (!details)
        str += utils::Print("Pipelines: " + pipelineNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintGroupAnalyses ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintGroupAnalyses
 * @param details true to print details, false to print list of group analysis names
 */
QString squirrel::PrintGroupAnalyses(bool details) {
    QString str;

    QList <squirrelGroupAnalysis> groupAnalyses = GetGroupAnalysisList();
    QStringList groupAnalysisNames;
    foreach (squirrelGroupAnalysis g, groupAnalyses) {
        if (g.Get()) {
            if (details)
                str += g.PrintGroupAnalysis();
            else
                groupAnalysisNames.append(g.GroupAnalysisName);
        }
    }
    if (!details)
        str += utils::Print("GroupAnalysis: " + groupAnalysisNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintDataDictionary ---------------------------------- */
/* ------------------------------------------------------------ */
QString squirrel::PrintDataDictionary(bool details) {
    QString str;

    QList <squirrelDataDictionary> dataDictionaries = GetDataDictionaryList();
    QStringList dataDictionaryNames;
    foreach (squirrelDataDictionary d, dataDictionaries) {
        if (d.Get()) {
            if (details)
                str += d.PrintDataDictionary();
            else
                dataDictionaryNames.append(d.DataDictionaryName);
        }
    }
    if (!details)
        str += utils::Print("DataDictionary: " + dataDictionaryNames.join(" "));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- GetExperimentList ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetExperimentList
 * @return list of all experiments
 */
QList<squirrelExperiment> squirrel::GetExperimentList() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelExperiment> list;
    q.prepare("select ExperimentRowID from Experiment");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelExperiment e;
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelPipeline> list;
    q.prepare("select PipelineRowID from Pipeline");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelPipeline p;
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
 * @brief Get a list of all subjects in the package
 * @return list of all subjects
 */
QList<squirrelSubject> squirrel::GetSubjectList() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelSubject> list;
    q.prepare("select SubjectRowID from Subject order by ID asc, SequenceNumber asc");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSubject s;
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
 * @brief Get a list of all studies for a specified subject
 * @param subjectRowID database row ID of the subject
 * @return list of studies
 */
QList<squirrelStudy> squirrel::GetStudyList(qint64 subjectRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelStudy> list;
    q.prepare("select StudyRowID from Study where SubjectRowID = :id order by StudyNumber asc, SequenceNumber asc");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelStudy s;
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
 * @brief squirrel::GetSeriesList Get all series for a study
 * @param studyRowID database row ID of the study
 * @return list of series
 */
QList<squirrelSeries> squirrel::GetSeriesList(qint64 studyRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelSeries> list;
    q.prepare("select SeriesRowID from Series where StudyRowID = :id order by SeriesNumber asc, SequenceNumber");
    q.bindValue(":id", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSeries s;
        s.SetObjectID(q.value("SeriesRowID").toLongLong());
        if (s.Get()) {
            s.SetDirFormat(SubjectDirFormat, StudyDirFormat, SeriesDirFormat);
            list.append(s);
            Debug(QString("Found SeriesNumber [%1]").arg(s.SeriesNumber), __FUNCTION__);
        }
        else {
            Log(QString("Unable to load SeriesRowID [%1]").arg(s.GetObjectID()), __FUNCTION__);
        }
    }
    Debug(QString("Found [%1] series for StudyRowID [%2]").arg(list.size()).arg(studyRowID), __FUNCTION__);
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalysisList -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get list of all Analysis objects for the specified studyRowID
 * @param studyRowID of the parent study
 * @return QList of squirrelAnalysis objects
 */
QList<squirrelAnalysis> squirrel::GetAnalysisList(qint64 studyRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelAnalysis> list;
    q.prepare("select AnalysisRowID from Analysis where StudyRowID = :id");
    q.bindValue(":id", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelAnalysis a;
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelObservation> list;
    q.prepare("select ObservationRowID from Observation where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelObservation m;
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelIntervention> list;
    q.prepare("select InterventionRowID from Intervention where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelIntervention d;
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelGroupAnalysis> list;
    q.prepare("select GroupAnalysisRowID from GroupAnalysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelGroupAnalysis g;
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelDataDictionary> list;
    q.prepare("select DataDictionaryRowID from DataDictionary");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelDataDictionary s;
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
 * @brief squirrel::GetSubject
 * @param subjectRowID
 * @return
 */
squirrelSubject squirrel::GetSubject(qint64 subjectRowID) {
    squirrelSubject s;
    s.SetObjectID(subjectRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetStudy --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetStudy
 * @param studyRowID
 * @return
 */
squirrelStudy squirrel::GetStudy(qint64 studyRowID) {
    squirrelStudy s;
    s.SetObjectID(studyRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetSeries
 * @param seriesRowID
 * @return
 */
squirrelSeries squirrel::GetSeries(qint64 seriesRowID) {
    squirrelSeries s;
    s.SetObjectID(seriesRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalysis ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetAnalysis
 * @param analysisRowID
 * @return
 */
squirrelAnalysis squirrel::GetAnalysis(qint64 analysisRowID) {
    squirrelAnalysis s;
    s.SetObjectID(analysisRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetIntervention -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetIntervention
 * @param interventionRowID
 * @return
 */
squirrelIntervention squirrel::GetIntervention(qint64 interventionRowID) {
    squirrelIntervention s;
    s.SetObjectID(interventionRowID);
    s.Get();

    return s;
}


/* ------------------------------------------------------------ */
/* ----- GetObservation --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetObservation
 * @param observationRowID
 * @return
 */
squirrelObservation squirrel::GetObservation(qint64 observationRowID) {
    squirrelObservation s;
    s.SetObjectID(observationRowID);
    s.Get();

    return s;
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
    squirrelGroupAnalysis s;
    s.SetObjectID(groupAnalysisRowID);
    s.Get();

    return s;
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
    squirrelDataDictionary s;
    s.SetObjectID(dataDictionaryRowID);
    s.Get();

    return s;
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
    squirrelExperiment s;
    s.SetObjectID(experimentRowID);
    s.Get();

    return s;
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
    squirrelPipeline s;
    s.SetObjectID(pipelineRowID);
    s.Get();

    return s;
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select SubjectRowID from Subject where ID = :id");
    q.bindValue(":id", id);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SubjectRowID").toLongLong();
        Debug(QString("Searched for SubjectID [%1] and found SubjectRowID [%2]").arg(id).arg(rowid), __FUNCTION__);
    }
    else {
        Debug(QString("Could not find SubjectID [%1]").arg(id), __FUNCTION__);
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
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
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select DataDictionaryRowID from DataDictionary where DataDictionaryName = :dataDictionaryName");
    q.bindValue(":dataDictionaryName", dataDictionaryName);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("DataDictionaryRowID").toLongLong();
    }
    return rowid;
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
/* ----- RemoveSubject ---------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveSubject(qint64 subjectRowID) {

    /* get list of studies associated with this subject, and delete them */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select StudyRowID from Study where SubjectRowID = :subjectRowID");
    q.bindValue(":subjectRowID", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        if (!RemoveStudy(q.value("StudyRowID").toLongLong()))
            return false;
    }

    /* remove Interventions associated with this subject */
    q.prepare("delete from Intervention where SubjectRowID = :subjectRowID");
    q.bindValue(":subjectRowID", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove all observations associated with this subject */
    q.prepare("delete from Observation where SubjectRowID = :subjectRowID");
    q.bindValue(":subjectRowID", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
\
    /* remove the files, if any from the archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelSubject sqrlSubject;
        sqrlSubject.SetObjectID(subjectRowID);
        sqrlSubject.Get();
        QString subjectArchivePath = sqrlSubject.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(subjectArchivePath, packagePath, m))
            return false;
    }

    /* remove the subject */
    q.prepare("delete from Subject where SubjectRowID = :subjectRowID");
    q.bindValue(":subjectRowID", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveStudy ------------------------------------------ */
/* ------------------------------------------------------------ */
bool squirrel::RemoveStudy(qint64 studyRowID) {

    /* get list of studies associated with this subject, and delete them */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select SeriesRowID from Series where StudyRowID = :studyRowID");
    q.bindValue(":studyRowID", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        if (!RemoveSeries(q.value("SeriesRowID").toLongLong()))
            return false;
    }

    /* remove analyses associated with this subject */
    q.prepare("select AnalysisRowID from Analysis where StudyRowID = :studyRowID");
    q.bindValue(":studyRowID", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        if (!RemoveAnalysis(q.value("AnalysisRowID").toLongLong()))
            return false;
    }

    /* remove files from the archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelSubject sqrlStudy;
        sqrlStudy.SetObjectID(studyRowID);
        sqrlStudy.Get();
        QString studyArchivePath = sqrlStudy.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(studyArchivePath, packagePath, m))
            return false;
    }

    /* remove the study */
    q.prepare("delete from Study where StudyRowID = :studyRowID");
    q.bindValue(":studyRowID", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveSeries ----------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveSeries(qint64 seriesRowID) {

    /* delete from database */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Series where SeriesRowID = :seriesRowID");
    q.bindValue(":seriesRowID", seriesRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove files from archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelSeries sqrlSeries;
        sqrlSeries.SetObjectID(seriesRowID);
        sqrlSeries.Get();
        QString seriesArchivePath = sqrlSeries.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(seriesArchivePath, packagePath, m))
            return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveAnalysis --------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveAnalysis(qint64 analysisRowID) {

    /* delete from database */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Analysis where AnalysisRowID = :analysisRowID");
    q.bindValue(":analysisRowID", analysisRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove files from archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelAnalysis sqrlAnalysis;
        sqrlAnalysis.SetObjectID(analysisRowID);
        sqrlAnalysis.Get();
        QString analysisArchivePath = sqrlAnalysis.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(analysisArchivePath, packagePath, m))
            return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveExperiment ------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveExperiment(qint64 experimentRowID) {

    /* delete from database */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Experiment where ExperimentRowID = :experimentRowID");
    q.bindValue(":experimentRowID", experimentRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove files from archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelExperiment sqrlExperiment;
        sqrlExperiment.SetObjectID(experimentRowID);
        sqrlExperiment.Get();
        QString experimentArchivePath = sqrlExperiment.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(experimentArchivePath, packagePath, m))
            return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemovePipeline --------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemovePipeline(qint64 pipelineRowID) {

    /* delete from database */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Pipeline where PipelineRowID = :pipelineRowID");
    q.bindValue(":pipelineRowID", pipelineRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from PipelineDataStep where PipelineRowID = :pipelineRowID");
    q.bindValue(":pipelineRowID", pipelineRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove files from archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelPipeline sqrlPipeline;
        sqrlPipeline.SetObjectID(pipelineRowID);
        sqrlPipeline.Get();
        QString pipelineArchivePath = sqrlPipeline.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(pipelineArchivePath, packagePath, m))
            return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveGroupAnalysis ---------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveGroupAnalysis(qint64 groupAnalysisRowID) {

    /* delete from database */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from GroupAnalysis where GroupAnalysisRowID = :groupAnalysisRowID");
    q.bindValue(":groupAnalysisRowID", groupAnalysisRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove files from archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelGroupAnalysis sqrlGroupAnalysis;
        sqrlGroupAnalysis.SetObjectID(groupAnalysisRowID);
        sqrlGroupAnalysis.Get();
        QString groupAnalysisArchivePath = sqrlGroupAnalysis.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(groupAnalysisArchivePath, packagePath, m))
            return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveDataDictionary --------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveDataDictionary(qint64 dataDictionaryRowID) {

    /* delete from database */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from DataDictionary where DataDictionaryRowID = :dataDictionaryRowID");
    q.bindValue(":dataDictionaryRowID", dataDictionaryRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove files from archive */
    if (fileMode == FileMode::ExistingPackage) {
        squirrelDataDictionary sqrlDataDictionary;
        sqrlDataDictionary.SetObjectID(dataDictionaryRowID);
        sqrlDataDictionary.Get();
        QString dataDictionaryArchivePath = sqrlDataDictionary.VirtualPath();
        QString m;
        if (!RemoveDirectoryFromArchive(dataDictionaryArchivePath, packagePath, m))
            return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveIntervention ----------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveIntervention(qint64 InterventionRowID) {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Intervention where InterventionRowID = :InterventionRowID");
    q.bindValue(":InterventionRowID", InterventionRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveObservation ------------------------------------ */
/* ------------------------------------------------------------ */
bool squirrel::RemoveObservation(qint64 observationRowID) {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Observation where ObservationRowID = :observationRowID");
    q.bindValue(":observationRowID", observationRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ExtractFileFromArchive ------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Extract a single file from an existing archive and return it as a string
 * @param archivePath Path to the archive
 * @param filePath File path within the archive
 * @param fileContents File contents as a QString
 * @return true if successful, false otherwise
 */
bool squirrel::ExtractFileFromArchive(QString archivePath, QString filePath, QString &fileContents) {
    Debug(QString("Reading file [%1] from archive [%2]...").arg(filePath).arg(archivePath), __FUNCTION__);
    try {
        using namespace bit7z;
        std::vector<unsigned char> buffer;
        Bit7zLibrary lib(p7zipLibPath.toStdString());
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitFileExtractor extractor(lib, BitFormat::Zip);
            //extractor.setProgressCallback(progressCallback);
            //extractor.setTotalCallback(totalArchiveSizeCallback);
            extractor.extractMatching(archivePath.toStdString(), filePath.toStdString(), buffer);
        }
        else {
            BitFileExtractor extractor(lib, BitFormat::SevenZip);
            //extractor.setProgressCallback(progressCallback);
            //extractor.setTotalCallback(totalArchiveSizeCallback);
            extractor.extractMatching(archivePath.toStdString(), filePath.toStdString(), buffer);
        }
        std::string str{buffer.begin(), buffer.end()};
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
    Log(QString("Compressing directory [%1] to archive [%2]...").arg(dir).arg(archivePath), __FUNCTION__);

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
    try {
        using namespace bit7z;
        Bit7zLibrary lib(p7zipLibPath.toStdString());

        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            //bit7z::BitArchiveReader archive(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            //archive.setProgressCallback(progressCallback);
            //archive.setTotalCallback(totalArchiveSizeCallback);
            //archive.extractTo(destinationPath.toStdString());

            bit7z::BitFileExtractor extractor(lib, bit7z::BitFormat::Zip);
            extractor.setProgressCallback(progressCallback);
            extractor.setTotalCallback(totalArchiveSizeCallback);
            extractor.extract(archivePath.toStdString(), destinationPath.toStdString());
        }
        else {
            //bit7z::BitArchiveReader archive(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            //archive.setProgressCallback(progressCallback);
            //archive.setTotalCallback(totalArchiveSizeCallback);
            //archive.extractTo(destinationPath.toStdString());

            bit7z::BitFileExtractor extractor(lib, bit7z::BitFormat::SevenZip);
            extractor.setProgressCallback(progressCallback);
            extractor.setTotalCallback(totalArchiveSizeCallback);
            extractor.extract(archivePath.toStdString(), destinationPath.toStdString());
        }
        m = "Successfully extracted archive [" + archivePath + "] to directory [" + destinationPath + "]";
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to extract archive to directory using bit7z library [" + QString(ex.what()) + "]";
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
        Log("DebugSQL set to ON", __FUNCTION__);
    else
        Log("DebugSQL set to OFF", __FUNCTION__);
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
        Log("OverwritePackage set to ON", __FUNCTION__);
    else
        Log("OverwritePackage set to OFF", __FUNCTION__);
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
        Log("QuickRead set to ON (params.json files will be read)", __FUNCTION__);
    else
        Log("QuickRead set to OFF (params.json files will NOT be read)", __FUNCTION__);
}
