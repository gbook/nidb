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
    quiet = q;

    DatabaseConnect();
    InitializeDatabase();

    Log(QString("Created squirrel object."), __FUNCTION__);
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
/* --------- DatabaseConnect -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Connect to SQLite memory database
 * @return true if successful, false otherwise
 */
bool squirrel::DatabaseConnect() {

    db = QSqlDatabase::addDatabase("QSQLITE", "squirrel");
    db.setDatabaseName(":memory:");
    //db.setDatabaseName("/tmp/sqlite.db");

    if (db.open()) {
        Log("Successfuly opened SQLite memory database", __FUNCTION__);
        return true;
    }
    else {
        Log(QString("Error opening SQLite memory database" + db.lastError().text()), __FUNCTION__);
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
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Analysis]", __FUNCTION__); return false; }

    q.prepare(tableDrug);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Drug]", __FUNCTION__); return false; }

    q.prepare(tableDataDictionary);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [DataDictionary]", __FUNCTION__); return false; }

    q.prepare(tableDataDictionaryItems);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [DataDictionaryItems]", __FUNCTION__); return false; }

    q.prepare(tableExperiment);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Experiment]", __FUNCTION__); return false; }

    q.prepare(tableGroupAnalysis);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [GroupAnalysis]", __FUNCTION__); return false; }

    q.prepare(tableMeasure);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Measure]", __FUNCTION__); return false; }

    q.prepare(tablePackage);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Package]", __FUNCTION__); return false; }

    q.prepare(tableParams);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Params]", __FUNCTION__); return false; }

    q.prepare(tablePipeline);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Pipeline]", __FUNCTION__); return false; }

    q.prepare(tablePipelineDataStep);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [PipelineDataStep]", __FUNCTION__); return false; }

    q.prepare(tableSeries);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Series]", __FUNCTION__); return false; }

    q.prepare(tableStudy);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Study]", __FUNCTION__); return false; }

    q.prepare(tableSubject);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [Subject]", __FUNCTION__); return false; }

    q.prepare(tableStagedFiles);
    if (!utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__)) { Log("Error creating table [StagedFiles]", __FUNCTION__); return false; }

    Log("Successfully initialized database", __FUNCTION__);
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

    /* check if file exists */
    if (!utils::FileExists(GetPackagePath())) {
        Log(QString("File %1 does not exist").arg(GetPackagePath()), __FUNCTION__);
        return false;
    }

    QString jsonstr;
    if (!ExtractFileFromArchive(GetPackagePath(), "squirrel.json", jsonstr)) {
        Log(QString("Error reading squirrel package. Unable to find squirrel.json"), __FUNCTION__);
        return false;
    }

    /* get the JSON document and root object */
    QJsonDocument d = QJsonDocument::fromJson(jsonstr.toUtf8());
    QJsonObject root = d.object();

    /* get the package info */
    QJsonObject pkgObj = root["package"].toObject();
    Changes = pkgObj["Changes"].toString();
    DataFormat = pkgObj["DataFormat"].toString();
    Datetime.fromString(pkgObj["Datetime"].toString());
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
    if (root.contains("data")) {
        QJsonValue dataVal = root.value("data");
        QJsonObject dataObj = dataVal.toObject();
        jsonSubjects = dataObj["subjects"].toArray();
        Log(QString("Found [%1] subjects").arg(jsonSubjects.size()), __FUNCTION__);
    }
    else if (root.contains("subjects")) {
        jsonSubjects = root["subjects"].toArray();
        Log(QString("NOTICE: Found [%1] subjects in the root of the JSON. (This is a slightly malformed squirrel file, but I'll accept it)").arg(jsonSubjects.size()), __FUNCTION__);
    }
    else {
        Log("root does not contain 'data' or 'subjects'", __FUNCTION__);
    }

    Log(QString("TotalFileCount: [%1]").arg(root["TotalFileCount"].toInt()), __FUNCTION__);
    Log(QString("TotalSize: [%1]").arg(root["TotalSize"].toInt()), __FUNCTION__);

    /* loop through and read any subjects */
    for (auto v : jsonSubjects) {
        QJsonObject jsonSubject = v.toObject();

        squirrelSubject sqrlSubject;

        sqrlSubject.ID = jsonSubject["SubjectID"].toString();
        sqrlSubject.AlternateIDs = jsonSubject["AlternateIDs"].toVariant().toStringList();
        sqrlSubject.GUID = jsonSubject["GUID"].toString();
        sqrlSubject.DateOfBirth.fromString(jsonSubject["DateOfBirth"].toString(), "yyyy-MM-dd");
        sqrlSubject.Sex = jsonSubject["Sex"].toString();
        sqrlSubject.Gender = jsonSubject["Gender"].toString();
        sqrlSubject.Ethnicity1 = jsonSubject["Ethnicity1"].toString();
        sqrlSubject.Ethnicity2 = jsonSubject["Ethnicity2"].toString();
        sqrlSubject.Store();
        qint64 subjectRowID = sqrlSubject.GetObjectID();

        Log(QString("Reading subject [%1]").arg(sqrlSubject.ID), __FUNCTION__);

        /* loop through and read all studies */
        QJsonArray jsonStudies = jsonSubject["studies"].toArray();
        for (auto v : jsonStudies) {
            QJsonObject jsonStudy = v.toObject();
            squirrelStudy sqrlStudy;

            sqrlStudy.StudyNumber = jsonStudy["StudyNumber"].toInt();
            sqrlStudy.DateTime.fromString(jsonStudy["StudyDatetime"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlStudy.AgeAtStudy = jsonStudy["AgeAtStudy"].toDouble();
            sqrlStudy.Height = jsonStudy["Height"].toDouble();
            sqrlStudy.Weight = jsonStudy["Weight"].toDouble();
            sqrlStudy.Modality = jsonStudy["Modality"].toString();
            sqrlStudy.Description = jsonStudy["Description"].toString();
            sqrlStudy.StudyUID = jsonStudy["StudyUID"].toString();
            sqrlStudy.VisitType = jsonStudy["VisitType"].toString();
            sqrlStudy.DayNumber = jsonStudy["DayNumber"].toInt();
            sqrlStudy.TimePoint = jsonStudy["TimePoint"].toInt();
            sqrlStudy.Equipment = jsonStudy["Equipment"].toString();
            sqrlStudy.subjectRowID = subjectRowID;
            sqrlStudy.Store();
            qint64 studyRowID = sqrlStudy.GetObjectID();

            /* loop through and read all series */
            QJsonArray jsonSeries = jsonStudy["series"].toArray();
            for (auto v : jsonSeries) {
                QJsonObject jsonSeries = v.toObject();
                squirrelSeries sqrlSeries;

                sqrlSeries.SeriesNumber = jsonSeries["SeriesNumber"].toInteger();
                sqrlSeries.DateTime.fromString(jsonSeries["SeriesDatetime"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlSeries.SeriesUID = jsonSeries["SeriesUID"].toString();
                sqrlSeries.Description = jsonSeries["Description"].toString();
                sqrlSeries.Protocol = jsonSeries["Protocol"].toString();
                //sqrlSeries.experimentNames = jsonSeries["ExperimentNames"].toString().split(",");
                sqrlSeries.Size = jsonSeries["Size"].toInteger();
                sqrlSeries.FileCount = jsonSeries["FileCount"].toInteger();
                sqrlSeries.BehavioralSize = jsonSeries["BehavioralSize"].toInteger();
                sqrlSeries.BehavioralFileCount = jsonSeries["BehavioralFileCount"].toInteger();
                sqrlSeries.studyRowID = studyRowID;

                /* read any params from the data/Subject/Study/Series/params.json file */
                QString parms;
                QString paramsfilepath = QString("data/%2/%3/%4/params.json").arg(sqrlSubject.ID).arg(sqrlStudy.StudyNumber).arg(sqrlSeries.SeriesNumber);
                if (ExtractFileFromArchive(GetPackagePath(), paramsfilepath, parms)) {
                    sqrlSeries.params = ReadParamsFile(parms);
                }

                sqrlSeries.Store();
            }

            /* loop through and read all analyses */
            QJsonArray jsonAnalyses = jsonStudy["analyses"].toArray();
            for (auto v : jsonAnalyses) {
                QJsonObject jsonAnalysis = v.toObject();
                squirrelAnalysis sqrlAnalysis;

                sqrlAnalysis.DateClusterEnd.fromString(jsonAnalysis["DateClusterEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.DateClusterStart.fromString(jsonAnalysis["DateClusterStart"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.DateStart.fromString(jsonAnalysis["DateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.DateStart.fromString(jsonAnalysis["DateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
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

                Log(QString("Added analysis [%1]").arg(sqrlAnalysis.PipelineName), __FUNCTION__);
            }
        }

        /* read all measures */
        QJsonArray jsonMeasures = jsonSubject["measures"].toArray();
        Log(QString("Reading [%1] measures").arg(jsonMeasures.size()), __FUNCTION__);
        for (auto v : jsonMeasures) {
            QJsonObject jsonMeasure = v.toObject();
            squirrelMeasure sqrlMeasure;
            sqrlMeasure.DateEnd.fromString(jsonMeasure["DateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.DateStart.fromString(jsonMeasure["DateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.DateRecordCreate.fromString(jsonMeasure["DateRecordCreate"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.DateRecordEntry.fromString(jsonMeasure["DateRecordEntry"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.DateRecordModify.fromString(jsonMeasure["DateRecordModify"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.Description = jsonMeasure["Description"].toString();
            sqrlMeasure.Duration = jsonMeasure["Duration"].toDouble();
            sqrlMeasure.InstrumentName = jsonMeasure["InstrumentName"].toString();
            sqrlMeasure.MeasureName = jsonMeasure["MeasureName"].toString();
            sqrlMeasure.Notes = jsonMeasure["Notes"].toString();
            sqrlMeasure.Rater = jsonMeasure["Rater"].toString();
            sqrlMeasure.Value = jsonMeasure["Value"].toString();
            sqrlMeasure.subjectRowID = subjectRowID;
            sqrlMeasure.Store();
        }

        /* read all drugs */
        QJsonArray jsonDrugs = jsonSubject["drugs"].toArray();
        Log(QString("Reading [%1] drugs").arg(jsonDrugs.size()), __FUNCTION__);
        for (auto v : jsonDrugs) {
            QJsonObject jsonDrug = v.toObject();
            squirrelDrug sqrlDrug;

            sqrlDrug.DateEnd.fromString(jsonDrug["DateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.DateRecordEntry.fromString(jsonDrug["DateRecordEntry"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.DateStart.fromString(jsonDrug["DateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.Description = jsonDrug["Description"].toString();
            sqrlDrug.DoseAmount = jsonDrug["DoseAmount"].toDouble();
            sqrlDrug.DoseFrequency = jsonDrug["DoseFrequency"].toString();
            sqrlDrug.DoseKey = jsonDrug["DoseKey"].toString();
            sqrlDrug.DoseString = jsonDrug["DoseString"].toString();
            sqrlDrug.DoseUnit = jsonDrug["DoseUnit"].toString();
            sqrlDrug.DrugClass = jsonDrug["DrugClass"].toString();
            sqrlDrug.DrugName = jsonDrug["DrugName"].toString();
            sqrlDrug.Notes = jsonDrug["Notes"].toString();
            sqrlDrug.Rater = jsonDrug["Rater"].toString();
            sqrlDrug.AdministrationRoute = jsonDrug["AdministrationRoute"].toString();
            sqrlDrug.subjectRowID = subjectRowID;
            sqrlDrug.Store();
        }
    }

    /* read all experiments */
    QJsonArray jsonExperiments;
    jsonExperiments = root["experiments"].toArray();
    Log(QString("Reading [%1] experiments").arg(jsonExperiments.size()), __FUNCTION__);
    for (auto v : jsonExperiments) {
        QJsonObject jsonExperiment = v.toObject();
        squirrelExperiment sqrlExperiment;

        sqrlExperiment.ExperimentName = jsonExperiment["ExperimentName"].toString();
        sqrlExperiment.FileCount = jsonExperiment["FileCount"].toInt();
        sqrlExperiment.Size = jsonExperiment["Size"].toInt();
        sqrlExperiment.Store();
    }

    /* read all pipelines */
    QJsonArray jsonPipelines;
    jsonPipelines = root["pipelines"].toArray();
    Log(QString("Reading [%1] pipelines").arg(jsonPipelines.size()), __FUNCTION__);
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
        sqrlPipeline.CreateDate.fromString(jsonPipeline["CreateDate"].toString(), "yyyy-MM-dd hh:mm:ss");
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
        Log(QString("Updating existing squirrel package [%1]").arg(workingDir).arg(GetPackagePath()), __FUNCTION__);
    }

    pairList stagedFiles;

    /* ----- 1) Write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetAllSubjects();
    foreach (squirrelSubject subject, subjects) {
        qint64 subjectRowID = subject.GetObjectID();
        Log(QString("Writing subject [%1] to virtualPath [%2]").arg(subject.ID).arg(subject.VirtualPath()), __FUNCTION__);

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudies(subjectRowID);
        foreach (squirrelStudy study, studies) {
            qint64 studyRowID = study.GetObjectID();
            Log(QString("Writing study [%1] to virtualPath [%2]").arg(study.StudyNumber).arg(study.VirtualPath()), __FUNCTION__);

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeries(studyRowID);
            foreach (squirrelSeries series, serieses) {
                QString m;
                QString seriesPath = QString("%1/%2").arg(workingDir).arg(series.VirtualPath());
                utils::MakePath(seriesPath,m);

                Log(QString("Writing series [%1] to [%2]. Data format [%3]").arg(series.SeriesNumber).arg(seriesPath).arg(DataFormat), __FUNCTION__);

                /* orig vs other formats */
                if (DataFormat == "orig") {
                    /* copy all of the series files to the temp directory */
                    foreach (QString f, series.stagedFiles) {
                        QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                        utils::SystemCommand(systemstring);
                    }
                }
                else if ((DataFormat == "anon") || (DataFormat == "anonfull")) {
                    /* create temp directory for the anonymization */
                    QString td;
                    MakeTempDir(td);

                    /* copy all files to temp directory */
                    QString systemstring;
                    foreach (QString f, series.stagedFiles) {
                        systemstring = QString("cp -uv %1 %2").arg(f).arg(td);
                        utils::SystemCommand(systemstring);
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
                    utils::SystemCommand(systemstring);

                    /* delete temp directory */
                    QString m2;
                    utils::RemoveDir(td, m2);
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
                        Log(QString("Converting [%1] files to nifti").arg(series.stagedFiles.size()), __FUNCTION__);

                        QFileInfo f(series.stagedFiles[0]);
                        QString origSeriesPath = f.absoluteDir().absolutePath();
                        squirrelImageIO io;
                        QString m3;
                        if (!io.ConvertDicom(DataFormat, origSeriesPath, seriesPath, QDir::currentPath(), gzip, utils::CleanString(subject.ID), QString("%1").arg(study.StudyNumber), QString("%1").arg(series.SeriesNumber), "dicom", numConv, numRename, m3)) {
                            Log(QString("ConvertDicom() failed. Returned [%1]").arg(m3), __FUNCTION__);
                        }
                        Log(QString("ConvertDicom() returned [%1]").arg(m3), __FUNCTION__);
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
    QList<squirrelSubject> subjectses = GetAllSubjects();
    foreach (squirrelSubject subject, subjectses) {
        JSONsubjects.append(subject.ToJSON());
    }

    /* add staged files to list */
    foreach (squirrelSubject subject, subjectses) {
        stagedFiles += subject.GetStagedFileList();
    }

    /* add group-analyses */
    QList <squirrelGroupAnalysis> groupAnalyses = GetAllGroupAnalyses();
    if (groupAnalyses.size() > 0) {
        Log(QString("Adding [%1] group-analyses...").arg(groupAnalyses.size()), __FUNCTION__);
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
    QList <squirrelPipeline> pipelines = GetAllPipelines();
    if (pipelines.size() > 0) {
        Log(QString("Adding [%1] pipelines...").arg(pipelines.size()), __FUNCTION__);
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
    QList <squirrelExperiment> exps = GetAllExperiments();
    if (exps.size() > 0) {
        Log(QString("Adding [%1] experiments...").arg(exps.size()), __FUNCTION__);
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
    QList <squirrelDataDictionary> dicts = GetAllDataDictionaries();
    if (dicts.size() > 0) {
        Log(QString("Adding [%1] data-dictionaries...").arg(dicts.size()), __FUNCTION__);
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
        for (int i=0; i<stagedFiles.size(); i++) {
            QStringPair file = stagedFiles.at(i);
            QString source = file.first;
            QFileInfo fi(file.first);
            QString fname = fi.fileName();
            QString dest = workingDir + "/" + fname;
            if (!QFile::copy(source, dest))
                Log(QString("Error copying [%1] to [%2]").arg(source).arg(dest), __FUNCTION__);
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

            /* delete the tmp dir, if it exists */
            if (utils::DirectoryExists(workingDir)) {
                Log("Temporary export dir [" + workingDir + "] exists and will be deleted", __FUNCTION__);
                QString m;
                if (!utils::RemoveDir(workingDir, m))
                    Log("Error [" + m + "] removing directory [" + workingDir + "]", __FUNCTION__);
            }
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
/* ----- Print ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print the details of a package, including all objects
 */
void squirrel::Print() {

    /* print package info */
    PrintPackage();

    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetAllSubjects();
    foreach (squirrelSubject sub, subjects) {
        qint64 subjectRowID = sub.GetObjectID();
        sub.PrintSubject();

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudies(subjectRowID);
        foreach (squirrelStudy study, studies) {
            qint64 studyRowID = study.GetObjectID();
            study.PrintStudy();

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeries(studyRowID);
            foreach (squirrelSeries series, serieses) {
                series.PrintSeries();
            }

            /* iterate through analyses */
            QList<squirrelAnalysis> analyses = GetAnalyses(studyRowID);
            foreach (squirrelAnalysis analysis, analyses) {
                analysis.PrintAnalysis();
            }
        }

        /* iterate through measures */
        QList<squirrelMeasure> measures = GetMeasures(subjectRowID);
        foreach (squirrelMeasure measure, measures) {
            measure.PrintMeasure();
        }

        /* iterate through drugs */
        QList<squirrelDrug> drugs = GetDrugs(subjectRowID);
        foreach (squirrelDrug drug, drugs) {
            drug.PrintDrug();
        }
    }

    /* iterate through pipelines */
    PrintPipelines();

    /* iterate through experiments */
    PrintExperiments();
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
    else if (object == "drug") table = "Drug";
    else if (object == "measure") table = "Measure";
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
void squirrel::PrintPackage() {

    qint64 numSubjects = GetObjectCount("subject");
    qint64 numStudies = GetObjectCount("study");
    qint64 numSeries = GetObjectCount("series");
    qint64 numMeasures = GetObjectCount("measure");
    qint64 numDrugs = GetObjectCount("drug");
    qint64 numAnalyses = GetObjectCount("analysis");
    qint64 numExperiments = GetObjectCount("experiment");
    qint64 numPipelines = GetObjectCount("pipeline");
    qint64 numGroupAnalyses = GetObjectCount("groupanalysis");
    qint64 numDataDictionaries = GetObjectCount("datadictionary");
    //qint64 numDataDictionaryItems = GetObjectCount("datadictionaryitem");

    QString fileModeStr = "UnknownFileMode";
    if (fileMode == FileMode::NewPackage) fileModeStr = "NewPackage";
    if (fileMode == FileMode::ExistingPackage) fileModeStr = "ExistingPackage";

    utils::Print("Squirrel Package: " + GetPackagePath());
    utils::Print(QString("  DataFormat: %1").arg(DataFormat));
    utils::Print(QString("  Date: %1").arg(Datetime.toString()));
    utils::Print(QString("  Description: %1").arg(Description));
    utils::Print(QString("  DirectoryFormat (subject, study, series): %1, %2, %3").arg(SubjectDirFormat).arg(StudyDirFormat).arg(SeriesDirFormat));
    utils::Print(QString("  FileMode: %1").arg(fileModeStr));
    utils::Print(QString("  Files:\n    %1 files\n    %2 bytes (unzipped)").arg(GetFileCount()).arg(GetUnzipSize()));
    utils::Print(QString("  PackageName: %1").arg(PackageName));
    utils::Print(QString("  SquirrelBuild: %1").arg(SquirrelBuild));
    utils::Print(QString("  SquirrelVersion: %1").arg(SquirrelVersion));
    utils::Print(QString("  Object count:\n    %1 subjects\n    +-- %4 measures\n    +-- %5 drugs\n    +-- %2 studies\n    +---- %3 series\n    +---- %6 analyses\n    %7 experiments\n    %8 pipelines\n    %9 group analyses\n    %10 data dictionary").arg(numSubjects).arg(numStudies).arg(numSeries).arg(numMeasures).arg(numDrugs).arg(numAnalyses).arg(numExperiments).arg(numPipelines).arg(numGroupAnalyses).arg(numDataDictionaries));
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

    if (objectType == "series") {
        squirrelSeries s;
        s.SetObjectID(rowid);
        if (s.Get()) {
            foreach (QString f, files)
                s.stagedFiles.append(f);
            if (s.Store())
                return true;
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
    if (!quiet) {
        if (s.trimmed() != "") {
            log.append(QString("squirrel::%1() %2\n").arg(func).arg(s));
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
void squirrel::PrintSubjects(bool details) {

    QList <squirrelSubject> subjects = GetAllSubjects();
    QStringList subjectIDs;
    foreach (squirrelSubject s, subjects) {
        if (s.Get()) {
            if (details)
                s.PrintSubject();
            else
                subjectIDs.append(s.ID);
        }
    }
    if (!details)
        utils::Print("Subjects: " + subjectIDs.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- PrintStudies ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintStudies print list of studies to the stdout
 * @param subjectID the subject ID to print studies for
 * @param details true to print details, false to print list of study numbers
 */
void squirrel::PrintStudies(qint64 subjectRowID, bool details) {
    QList <squirrelStudy> studies = GetStudies(subjectRowID);
    QStringList studyNumbers;
    foreach (squirrelStudy s, studies) {
        if (s.Get()) {
            if (details)
                s.PrintStudy();
            else
                studyNumbers.append(QString("%1").arg(s.StudyNumber));
        }
    }
    if (!details)
        utils::Print("Studies: " + studyNumbers.join(" "));
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
void squirrel::PrintSeries(qint64 studyRowID, bool details) {
    QList <squirrelSeries> series = GetSeries(studyRowID);
    QStringList seriesNumbers;
    foreach (squirrelSeries s, series) {
        if (s.Get()) {
            if (details)
                s.PrintSeries();
            else
                seriesNumbers.append(QString("%1").arg(s.SeriesNumber));
        }
    }
    if (!details)
        utils::Print("Series: " + seriesNumbers.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- PrintExperiments ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintExperiments
 * @param details true to print details, false to print list of pipeline names
 */
void squirrel::PrintExperiments(bool details) {
    QList <squirrelExperiment> exps = GetAllExperiments();
    QStringList experimentNames;
    foreach (squirrelExperiment e, exps) {
        if (e.Get()) {
            if (details)
                e.PrintExperiment();
            else
                experimentNames.append(e.ExperimentName);
        }
    }
    if (!details)
        utils::Print("Experiments: " + experimentNames.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- PrintPipelines --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintPipelines
 * @param details true to print details, false to print list of pipeline names
 */
void squirrel::PrintPipelines(bool details) {
    QList <squirrelPipeline> pipelines = GetAllPipelines();
    QStringList pipelineNames;
    foreach (squirrelPipeline p, pipelines) {
        if (p.Get()) {
            if (details)
                p.PrintPipeline();
            else
                pipelineNames.append(p.PipelineName);
        }
    }
    if (!details)
        utils::Print("Pipelines: " + pipelineNames.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- PrintGroupAnalyses ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintGroupAnalyses
 * @param details true to print details, false to print list of group analysis names
 */
void squirrel::PrintGroupAnalyses(bool details) {
    QList <squirrelGroupAnalysis> groupAnalyses = GetAllGroupAnalyses();
    QStringList groupAnalysisNames;
    foreach (squirrelGroupAnalysis g, groupAnalyses) {
        if (g.Get()) {
            if (details)
                g.PrintGroupAnalysis();
            else
                groupAnalysisNames.append(g.GroupAnalysisName);
        }
    }
    if (!details)
        utils::Print("GroupAnalysis: " + groupAnalysisNames.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- PrintDataDictionary ---------------------------------- */
/* ------------------------------------------------------------ */
void squirrel::PrintDataDictionary(bool details) {
    QList <squirrelDataDictionary> dataDictionaries = GetAllDataDictionaries();
    QStringList dataDictionaryNames;
    foreach (squirrelDataDictionary d, dataDictionaries) {
        if (d.Get()) {
            if (details)
                d.PrintDataDictionary();
            else
                dataDictionaryNames.append(d.DataDictionaryName);
        }
    }
    if (!details)
        utils::Print("DataDictionary: " + dataDictionaryNames.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- GetAllExperiments ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetAllExperiments
 * @return list of all experiments
 */
QList<squirrelExperiment> squirrel::GetAllExperiments() {
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
/* ----- GetAllPipelines -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetAllPipelines
 * @return list of all pipelines
 */
QList<squirrelPipeline> squirrel::GetAllPipelines() {
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
/* ----- GetAllSubjects --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetAllSubjects
 * @return list of all subjects
 */
QList<squirrelSubject> squirrel::GetAllSubjects() {
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
/* ----- GetStudies ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetStudies
 * @param subjectRowID database row ID of the subject
 * @return list of studies
 */
QList<squirrelStudy> squirrel::GetStudies(qint64 subjectRowID) {
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
/* ----- GetSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetSeries Get all series for a study
 * @param studyRowID database row ID of the study
 * @return list of series
 */
QList<squirrelSeries> squirrel::GetSeries(qint64 studyRowID) {
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
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalyses ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get list of all Analysis objects for the specified studyRowID
 * @param studyRowID of the parent study
 * @return QList of squirrelAnalysis objects
 */
QList<squirrelAnalysis> squirrel::GetAnalyses(qint64 studyRowID) {
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
/* ----- GetMeasures ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all Measure objects for a subject
 * @param subjectRowID of the parent subject
 * @return QList of squirrelMeasure objects
 */
QList<squirrelMeasure> squirrel::GetMeasures(qint64 subjectRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelMeasure> list;
    q.prepare("select MeasureRowID from Measure where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelMeasure m;
        m.SetObjectID(q.value("MeasureRowID").toLongLong());
        if (m.Get()) {
            list.append(m);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetDrugs --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all Drug objects
 * @param subjectRowID
 * @return list of all squirrelDrug objects
 */
QList<squirrelDrug> squirrel::GetDrugs(qint64 subjectRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelDrug> list;
    q.prepare("select DrugRowID from Drug where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelDrug d;
        d.SetObjectID(q.value("DrugRowID").toLongLong());
        if (d.Get()) {
            list.append(d);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetAllGroupAnalyses ---------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all GroupAnalysis objects
 * @return list of all groupAnalysis objects
 */
QList<squirrelGroupAnalysis> squirrel::GetAllGroupAnalyses() {
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
/* ----- GetAllDataDictionaries ------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all DataDictionary objects
 * @return list of all dataDictionary objects
 */
QList<squirrelDataDictionary> squirrel::GetAllDataDictionaries() {
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
    q.prepare("select a.SubjectRowID from Study a left join Subject b on a.SubjectRowID = b.SubjectRowID where a.StudyNumber = :studynum and b.ID = :id");
    q.bindValue(":studynum", studyNum);
    q.bindValue(":id", subjectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SubjectRowID").toLongLong();
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
        rowid = q.value("SubjectRowID").toLongLong();
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

    QList<squirrelSubject> subjects = GetAllSubjects();
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

    QList<squirrelStudy> studies = GetStudies(subjectRowID);
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

    QList<squirrelSeries> serieses = GetSeries(studyRowID);
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

    /* remove drugs associated with this subject */
    q.prepare("delete from Drug where SubjectRowID = :subjectRowID");
    q.bindValue(":subjectRowID", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* remove all measures associated with this subject */
    q.prepare("delete from Measure where SubjectRowID = :subjectRowID");
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
/* ----- RemoveDrug ------------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveDrug(qint64 drugRowID) {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Drug where DrugRowID = :drugRowID");
    q.bindValue(":drugRowID", drugRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    return true;
}


/* ------------------------------------------------------------ */
/* ----- RemoveMeasure ---------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrel::RemoveMeasure(qint64 measureRowID) {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("delete from Measure where MeasureRowID = :measureRowID");
    q.bindValue(":measureRowID", measureRowID);
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
    try {
        using namespace bit7z;
        std::vector<unsigned char> buffer;
        #ifdef Q_OS_WINDOWS
           Bit7zLibrary lib("C:/Program Files/7-Zip/7z.dll");
        #else
           Bit7zLibrary lib("/usr/libexec/p7zip/7z.so");
        #endif
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitFileExtractor extractor(lib, BitFormat::Zip);
            extractor.extractMatching(archivePath.toStdString(), filePath.toStdString(), buffer);
        }
        else {
            BitFileExtractor extractor(lib, BitFormat::SevenZip);
            extractor.extractMatching(archivePath.toStdString(), filePath.toStdString(), buffer);
        }
        std::string str{buffer.begin(), buffer.end()};
        fileContents = QString::fromStdString(str);
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
    utils::Print("Inside CompressDirectoryToArchive()");

    try {
        using namespace bit7z;
#ifdef Q_OS_WINDOWS
        Bit7zLibrary lib("C:/Program Files/7-Zip/7z.dll");
#else
        utils::Print("Checkpoint A");
        Bit7zLibrary lib("/usr/libexec/p7zip/7z.so");
        utils::Print("Checkpoint B");
#endif
        utils::Print("Checkpoint C");
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            BitArchiveWriter archive(lib, BitFormat::Zip);
            //archive.setOverwriteMode(OverwriteMode::Overwrite);
            archive.setUpdateMode(UpdateMode::Update);
            archive.addDirectory(dir.toStdString());
            archive.compressTo(archivePath.toStdString());
        }
        else {
            utils::Print("Checkpoint D");
            BitArchiveWriter archive(lib, BitFormat::SevenZip);
            utils::Print("Checkpoint E");
            //archive.setOverwriteMode(OverwriteMode::Overwrite);
            archive.setUpdateMode(UpdateMode::Update);
            utils::Print("Checkpoint F");
            archive.addDirectory(dir.toStdString());
            utils::Print("Checkpoint G (" + archivePath + ")");
            archive.compressTo(archivePath.toStdString());
            utils::Print("Checkpoint H");
        }
        m = "Successfully compressed directory [" + dir + "] to archive [" + archivePath + "]";
        return true;
    }
    catch ( const bit7z::BitException& ex ) {
        /* Do something with ex.what()...*/
        m = "Unable to compress directory into archive using bit7z library [" + QString(ex.what()) + "]";
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
#ifdef Q_OS_WINDOWS
        Bit7zLibrary lib("C:/Program Files/7-Zip/7z.dll");
#else
        Bit7zLibrary lib("/usr/libexec/p7zip/7z.so");
#endif
        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            editor.setUpdateMode(UpdateMode::Update);
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
#ifdef Q_OS_WINDOWS
        Bit7zLibrary lib("C:/Program Files/7-Zip/7z.dll");
#else
        Bit7zLibrary lib("/usr/libexec/p7zip/7z.so");
#endif

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
#ifdef Q_OS_WINDOWS
        Bit7zLibrary lib("C:/Program Files/7-Zip/7z.dll");
#else
        Bit7zLibrary lib("/usr/libexec/p7zip/7z.so");
#endif
        /* convert the QString to a istream */
        std::istringstream i(file.toStdString());

        if (archivePath.endsWith(".zip", Qt::CaseInsensitive)) {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::Zip);
            editor.updateItem(compressedFilePath.toStdString(), i);
            editor.applyChanges();
        }
        else {
            bit7z::BitArchiveEditor editor(lib, archivePath.toStdString(), bit7z::BitFormat::SevenZip);
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
