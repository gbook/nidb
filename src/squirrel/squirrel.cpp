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

/* ------------------------------------------------------------ */
/* ----- squirrel --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::squirrel constructor
 * @param dbg true for debug logging
 * @param q true to turn off all output
 */
squirrel::squirrel(bool dbg, bool q)
{
    datetime = QDateTime::currentDateTime();
    description = "Squirrel package";
    name = "Squirrel package";
    version = QString("%1.%2").arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN);
    format = "squirrel";
    subjectDirFormat = "orig";
    studyDirFormat = "orig";
    seriesDirFormat = "orig";
    dataFormat = "nifti4dgz";
    isOkToDelete = true;
    debug = dbg;
    quiet = q;

    MakeTempDir(workingDir);
    DatabaseConnect();
    InitializeDatabase();

    Log(QString("Created squirrel object. Working dir [%1]").arg(workingDir), __FUNCTION__);
}


/* ------------------------------------------------------------ */
/* ----- ~squirrel -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::~squirrel
 */
squirrel::~squirrel()
{
    if (isValid && (workingDir.size() > 20)) {
        QString m;
        if (!utils::RemoveDir(workingDir, m))
            Log(QString("Error removing working directory [%1]. Message [%2]").arg(workingDir).arg(m), __FUNCTION__);
    }
}


/* ---------------------------------------------------------- */
/* --------- DatabaseConnect -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief squirrel::DatabaseConnect - connect to SQLite memory
 * database
 * @return true if successful, false otherwise
 */
bool squirrel::DatabaseConnect() {

    db = QSqlDatabase::addDatabase("QSQLITE", "squirrel");
    db.setDatabaseName(":memory:");

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
 * @brief squirrel::InitializeDatabase - Create SQLite tables
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
/* ----- Read ------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Reads a squirrel package from disk
 * @param filepath Full filepath of the package
 * @param headerOnly true if only reading the header
 * @param validateOnly true if validating the package
 * @return
 */
bool squirrel::Read(QString filepath, bool headerOnly, bool validateOnly) {

    /* set the package path */
    filePath = filepath;

    if (validateOnly)
        Log(QString("Validating [%1]").arg(filepath), __FUNCTION__);
    else
        Log(QString("Reading squirrel file [%1]. Using working directory [%2]").arg(filepath).arg(workingDir), __FUNCTION__);

    /* check if file exists */
    if (!utils::FileExists(filepath)) {
        Log(QString("File %1 does not exist").arg(filepath), __FUNCTION__);
        return false;
    }

    /* get listing of the zip the file, check if the squirrel.json exists in the root */
    QString systemstring;
    #ifdef Q_OS_WINDOWS
        systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" l \"" + filepath + "\"");
    #else
        systemstring = "unzip -l " + filepath;
    #endif
    QString output = utils::SystemCommand(systemstring, true);
    Log(output, __FUNCTION__);
    if (!output.contains("squirrel.json")) {
        Log(QString("File " + filepath + " does not appear to be a squirrel package"), __FUNCTION__);
        return false;
    }

    /* get the header .json file (either by unzipping or extracting only the file) */
    QString jsonStr;
    if (headerOnly) {
        #ifdef Q_OS_WINDOWS
            systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" x \"" + filepath + "\" -o\"" + workingDir + "\" squirrel.json -y");
            Log(systemstring, __FUNCTION__, true);
            output = utils::SystemCommand(systemstring, true);
            /* read from .json file */
            jsonStr = utils::ReadTextFileToString(workingDir + "/squirrel.json");
        #else
            systemstring = QString("unzip -p " + filepath + " squirrel.json");
        output = utils::SystemCommand(systemstring, true);
        #endif
    }
    else {
        /* unzip the .zip to the working dir */
        #ifdef Q_OS_WINDOWS
            systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" x \"" + filepath + "\" -o\"" + workingDir + "\" -y");
        #else
            systemstring = QString("unzip " + filepath + " -d " + workingDir);
        #endif
        output = utils::SystemCommand(systemstring, true);
        Log(output, __FUNCTION__, true);

        /* read from .json file */
        jsonStr = utils::ReadTextFileToString(workingDir + "/squirrel.json");
    }

    /* get the JSON document and root object */
    QJsonDocument d = QJsonDocument::fromJson(jsonStr.toUtf8());
    QJsonObject root = d.object();

    /* get the package info */
    QJsonValue pkgVal = root.value("package");
    QJsonObject pkgObj = pkgVal.toObject();
    description = pkgObj["description"].toString();
    datetime.fromString(pkgObj["datetime"].toString());
    name = pkgObj["name"].toString();

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

    /* loop through and read any subjects */
    for (auto v : jsonSubjects) {
        QJsonObject jsonSubject = v.toObject();

        squirrelSubject sqrlSubject;

        sqrlSubject.ID = jsonSubject["SubjectID"].toString();
        sqrlSubject.alternateIDs = jsonSubject["AlternateIDs"].toVariant().toStringList();
        sqrlSubject.GUID = jsonSubject["GUID"].toString();
        sqrlSubject.dateOfBirth.fromString(jsonSubject["DateOfBirth"].toString(), "yyyy-MM-dd");
        sqrlSubject.sex = jsonSubject["Sex"].toString();
        sqrlSubject.gender = jsonSubject["Gender"].toString();
        sqrlSubject.ethnicity1 = jsonSubject["Ethnicity1"].toString();
        sqrlSubject.ethnicity2 = jsonSubject["Ethnicity2"].toString();
        //sqrlSubject.virtualPath = jsonSubject["VirtualPath"].toString();
        sqrlSubject.Store();
        int subjectRowID = sqrlSubject.GetObjectID();

        Log(QString("Reading subject [%1]").arg(sqrlSubject.ID), __FUNCTION__);

        /* loop through and read all studies */
        QJsonArray jsonStudies = jsonSubject["studies"].toArray();
        for (auto v : jsonStudies) {
            QJsonObject jsonStudy = v.toObject();
            squirrelStudy sqrlStudy;

            sqrlStudy.number = jsonStudy["StudyNumber"].toInt();
            sqrlStudy.dateTime.fromString(jsonStudy["StudyDatetime"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlStudy.ageAtStudy = jsonStudy["AgeAtStudy"].toDouble();
            sqrlStudy.height = jsonStudy["Height"].toDouble();
            sqrlStudy.weight = jsonStudy["Weight"].toDouble();
            sqrlStudy.modality = jsonStudy["Modality"].toString();
            sqrlStudy.description = jsonStudy["Description"].toString();
            sqrlStudy.studyUID = jsonStudy["StudyUID"].toString();
            sqrlStudy.visitType = jsonStudy["VisitType"].toString();
            sqrlStudy.dayNumber = jsonStudy["DayNumber"].toInt();
            sqrlStudy.timePoint = jsonStudy["TimePoint"].toInt();
            sqrlStudy.equipment = jsonStudy["Equipment"].toString();
            //sqrlStudy.virtualPath = jsonStudy["VirtualPath"].toString();
            sqrlStudy.subjectRowID = subjectRowID;
            sqrlStudy.Store();
            int studyRowID = sqrlStudy.GetObjectID();

            /* loop through and read all series */
            QJsonArray jsonSeries = jsonStudy["series"].toArray();
            for (auto v : jsonSeries) {
                QJsonObject jsonSeries = v.toObject();
                squirrelSeries sqrlSeries;

                sqrlSeries.number = jsonSeries["SeriesNumber"].toInteger();
                sqrlSeries.dateTime.fromString(jsonSeries["SeriesDatetime"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlSeries.seriesUID = jsonSeries["SeriesUID"].toString();
                sqrlSeries.description = jsonSeries["Description"].toString();
                sqrlSeries.protocol = jsonSeries["Protocol"].toString();
                //sqrlSeries.experimentNames = jsonSeries["ExperimentNames"].toString().split(",");
                sqrlSeries.size = jsonSeries["Size"].toInteger();
                sqrlSeries.numFiles = jsonSeries["NumFiles"].toInteger();
                sqrlSeries.behSize = jsonSeries["BehSize"].toInteger();
                sqrlSeries.numBehFiles = jsonSeries["BehNumFiles"].toInteger();
                //sqrlSeries.virtualPath = jsonSeries["VirtualPath"].toString();
                sqrlSeries.studyRowID = studyRowID;

                /* read any params from the data/Subject/Study/Series/params.json file */
                if (!headerOnly)
                    sqrlSeries.params = ReadParamsFile(QString("%1/data/%2/%3/%4/params.json").arg(workingDir).arg(sqrlSubject.ID).arg(sqrlStudy.number).arg(sqrlSeries.number));

                sqrlSeries.Store();
            }

            /* loop through and read all analyses */
            QJsonArray jsonAnalyses = jsonStudy["analyses"].toArray();
            for (auto v : jsonAnalyses) {
                QJsonObject jsonAnalyses = v.toObject();
                squirrelAnalysis sqrlAnalysis;

                sqrlAnalysis.clusterEndDate.fromString(jsonAnalyses["ClusterEndDate"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.clusterStartDate.fromString(jsonAnalyses["ClusterStartDate"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.endDate.fromString(jsonAnalyses["EndDate"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.hostname = jsonAnalyses["Hostname"].toString();
                sqrlAnalysis.lastMessage = jsonAnalyses["StatusMessage"].toString();
                sqrlAnalysis.numSeries = jsonAnalyses["NumSeries"].toInt();
                sqrlAnalysis.pipelineName = jsonAnalyses["PipelineName"].toString();
                sqrlAnalysis.pipelineVersion = jsonAnalyses["PipelineVersion"].toInt();
                sqrlAnalysis.runTime = jsonAnalyses["RunTime"].toInteger();
                sqrlAnalysis.setupTime = jsonAnalyses["RunTime"].toInteger();
                sqrlAnalysis.size = jsonAnalyses["Size"].toInteger();
                sqrlAnalysis.startDate.fromString(jsonAnalyses["StartDate"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlAnalysis.status = jsonAnalyses["Status"].toString();
                sqrlAnalysis.status = jsonAnalyses["Status"].toString();
                sqrlAnalysis.successful = jsonAnalyses["Successful"].toBool();
                //sqrlAnalysis.virtualPath = jsonAnalyses["VirtualPath"].toString();
                sqrlAnalysis.studyRowID = studyRowID;
                sqrlAnalysis.Store();

                Log(QString("Added analysis [%1]").arg(sqrlAnalysis.pipelineName), __FUNCTION__);
            }
        }

        /* read all measures */
        QJsonArray jsonMeasures = jsonSubject["measures"].toArray();
        Log(QString("Reading [%1] measures").arg(jsonMeasures.size()), __FUNCTION__);
        for (auto v : jsonMeasures) {
            QJsonObject jsonMeasure = v.toObject();
            squirrelMeasure sqrlMeasure;
            sqrlMeasure.dateEnd.fromString(jsonMeasure["DateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.dateStart.fromString(jsonMeasure["DateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.description = jsonMeasure["Description"].toString();
            sqrlMeasure.duration = jsonMeasure["Duration"].toDouble();
            sqrlMeasure.instrumentName = jsonMeasure["InstrumentName"].toString();
            sqrlMeasure.measureName = jsonMeasure["MeasureName"].toString();
            sqrlMeasure.notes = jsonMeasure["Notes"].toString();
            sqrlMeasure.rater = jsonMeasure["Rater"].toString();
            sqrlMeasure.value = jsonMeasure["Value"].toString();
            sqrlMeasure.subjectRowID = subjectRowID;
            sqrlMeasure.Store();
        }

        /* read all drugs */
        QJsonArray jsonDrugs = jsonSubject["drugs"].toArray();
        Log(QString("Reading [%1] drugs").arg(jsonDrugs.size()), __FUNCTION__);
        for (auto v : jsonDrugs) {
            QJsonObject jsonDrug = v.toObject();
            squirrelDrug sqrlDrug;

            sqrlDrug.dateEnd.fromString(jsonDrug["DateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.dateRecordEntry.fromString(jsonDrug["DateRecordEntry"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.dateStart.fromString(jsonDrug["DateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.description = jsonDrug["Description"].toString();
            sqrlDrug.doseAmount = jsonDrug["DoseAmount"].toDouble();
            sqrlDrug.doseFrequency = jsonDrug["DoseFrequency"].toString();
            sqrlDrug.doseKey = jsonDrug["DoseKey"].toString();
            sqrlDrug.doseString = jsonDrug["DoseString"].toString();
            sqrlDrug.doseUnit = jsonDrug["DoseUnit"].toString();
            sqrlDrug.drugClass = jsonDrug["DrugClass"].toString();
            sqrlDrug.drugName = jsonDrug["DrugName"].toString();
            sqrlDrug.frequencyModifier = jsonDrug["FrequencyModifier"].toString();
            sqrlDrug.frequencyUnit = jsonDrug["FrequencyUnit"].toString();
            sqrlDrug.frequencyValue = jsonDrug["FrequencyValue"].toDouble();
            sqrlDrug.notes = jsonDrug["Notes"].toString();
            sqrlDrug.rater = jsonDrug["Rater"].toString();
            sqrlDrug.route = jsonDrug["AdministrationRoute"].toString();
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

        sqrlExperiment.experimentName = jsonExperiment["ExperimentName"].toString();
        sqrlExperiment.numFiles = jsonExperiment["NumFiles"].toInt();
        sqrlExperiment.size = jsonExperiment["Size"].toInt();
        sqrlExperiment.virtualPath = jsonExperiment["VirtualPath"].toString();

        sqrlExperiment.Store();
    }

    /* read all pipelines */
    QJsonArray jsonPipelines;
    jsonPipelines = root["pipelines"].toArray();
    Log(QString("Reading [%1] pipelines").arg(jsonPipelines.size()), __FUNCTION__);
    for (auto v : jsonPipelines) {
        QJsonObject jsonPipeline = v.toObject();
        squirrelPipeline sqrlPipeline;

        sqrlPipeline.clusterQueue = jsonPipeline["ClusterQueue"].toString();
        sqrlPipeline.clusterSubmitHost = jsonPipeline["ClusterSubmitHost"].toString();
        sqrlPipeline.clusterType = jsonPipeline["ClusterType"].toString();
        sqrlPipeline.clusterUser = jsonPipeline["ClusterUser"].toString();
        sqrlPipeline.createDate.fromString(jsonPipeline["CreateDate"].toString(), "yyyy-MM-dd hh:mm:ss");
        sqrlPipeline.dataCopyMethod = jsonPipeline["DataCopyMethod"].toString();
        sqrlPipeline.depDir = jsonPipeline["DepDir"].toString();
        sqrlPipeline.depLevel = jsonPipeline["DepLevel"].toString();
        sqrlPipeline.depLinkType = jsonPipeline["DepLinkType"].toString();
        sqrlPipeline.description = jsonPipeline["Description"].toString();
        sqrlPipeline.dirStructure = jsonPipeline["DirStructure"].toString();
        sqrlPipeline.directory = jsonPipeline["Directory"].toString();
        sqrlPipeline.flags.useProfile = jsonPipeline["UseProfile"].toBool();
        sqrlPipeline.flags.useTmpDir = jsonPipeline["UseTempDir"].toBool();
        sqrlPipeline.group = jsonPipeline["Group"].toString();
        sqrlPipeline.groupType = jsonPipeline["GroupType"].toString();
        sqrlPipeline.level = jsonPipeline["Level"].toInt();
        sqrlPipeline.maxWallTime = jsonPipeline["MaxWallTime"].toInt();
        sqrlPipeline.notes = jsonPipeline["Notes"].toString();
        sqrlPipeline.numConcurrentAnalyses = jsonPipeline["NumConcurrentAnalyses"].toInt();
        sqrlPipeline.parentPipelines = jsonPipeline["ParentPipelines"].toString().split(",");
        sqrlPipeline.pipelineName = jsonPipeline["PipelineName"].toString();
        sqrlPipeline.primaryScript = jsonPipeline["PrimaryScript"].toString();
        sqrlPipeline.resultScript = jsonPipeline["ResultScript"].toString();
        sqrlPipeline.secondaryScript = jsonPipeline["SecondaryScript"].toString();
        sqrlPipeline.submitDelay = jsonPipeline["SubmitDelay"].toInt();
        sqrlPipeline.tmpDir = jsonPipeline["TempDir"].toString();
        sqrlPipeline.version = jsonPipeline["Version"].toInt();
        sqrlPipeline.virtualPath = jsonPipeline["VirtualPath"].toString();

        QJsonArray jsonCompleteFiles;
        jsonCompleteFiles = jsonPipeline["CompleteFiles"].toArray();
        for (auto v : jsonCompleteFiles) {
            sqrlPipeline.completeFiles.append(v.toString());
        }

        /* read the pipeline data steps */
        QJsonArray jsonDataSteps;
        jsonDataSteps = jsonPipeline["dataSteps"].toArray();
        for (auto v : jsonDataSteps) {
            QJsonObject jsonDataStep = v.toObject();
            dataStep ds;
            ds.associationType = jsonDataStep["AssociationType"].toString();
            ds.behDir = jsonDataStep["BehDir"].toString();
            ds.behFormat = jsonDataStep["BehFormat"].toString();
            ds.dataFormat = jsonDataStep["DataFormat"].toString();
            ds.imageType = jsonDataStep["ImageType"].toString();
            ds.datalevel = jsonDataStep["DataLevel"].toString();
            ds.location = jsonDataStep["Location"].toString();
            ds.modality = jsonDataStep["Modality"].toString();
            ds.numBOLDreps = jsonDataStep["NumBOLDreps"].toString();
            ds.numImagesCriteria = jsonDataStep["NumImagesCriteria"].toString();
            ds.order = jsonDataStep["Order"].toInt();
            ds.protocol = jsonDataStep["Protocol"].toString();
            ds.seriesCriteria = jsonDataStep["SeriesCriteria"].toString();
            ds.protocol = jsonDataStep["Protocol"].toString();
            ds.flags.enabled = jsonDataStep["Enabled"].toBool();
            ds.flags.optional = jsonDataStep["Optional"].toBool();
            ds.flags.gzip = jsonDataStep["Gzip"].toBool();
            ds.flags.usePhaseDir = jsonDataStep["UsePhaseDir"].toBool();
            ds.flags.useSeries = jsonDataStep["UseSeries"].toBool();
            ds.flags.preserveSeries = jsonDataStep["PreserveSeries"].toBool();
            ds.flags.primaryProtocol = jsonDataStep["PrimaryProtocol"].toBool();
            sqrlPipeline.dataSteps.append(ds);
        }
        sqrlPipeline.Store();
    }

    /* If we're only validating: delete the tmpdir if it exists */
    if (validateOnly) {
        if (utils::DirectoryExists(workingDir)) {
            Log(QString("Temporary export dir [" + workingDir + "] exists and will be deleted"), __FUNCTION__);
            QString m;
            if (!utils::RemoveDir(workingDir, m))
                Log(QString("Error [" + m + "] removing directory [" + workingDir + "]"), __FUNCTION__);
        }
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Write ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::Write
 * @param writeLog true if logfile should be written
 * @return true if successfuly written, false otherwise
 */
bool squirrel::Write(bool writeLog) {

    /* create the log file */
    QFileInfo finfo(zipPath);
    logfile = QString(finfo.absolutePath() + "/squirrel-" + utils::CreateLogDate() + ".log");

    Log(QString("Writing squirrel package: workingdir [%1]  zippath [%2]").arg(workingDir).arg(zipPath), __FUNCTION__);

    /* ----- 1) Write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    QList<squirrelSubject> subjects = GetAllSubjects();
    foreach (squirrelSubject subject, subjects) {
        int subjectRowID = subject.GetObjectID();
        Log(QString("Writing subject [%1] to virtualPath [%2]").arg(subject.ID).arg(subject.VirtualPath()), __FUNCTION__);

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudies(subjectRowID);
        foreach (squirrelStudy study, studies) {
            int studyRowID = study.GetObjectID();
            Log(QString("Writing study [%1] to virtualPath [%2]").arg(study.number).arg(study.VirtualPath()), __FUNCTION__);

            /* iterate through series */
            QList<squirrelSeries> serieses = GetSeries(studyRowID);
            foreach (squirrelSeries series, serieses) {
                QString m;
                QString seriesPath = QString("%1/%2").arg(workingDir).arg(series.VirtualPath());
                utils::MakePath(seriesPath,m);

                Log(QString("Writing series [%1] to [%2]. Data format [%3]").arg(series.number).arg(seriesPath).arg(dataFormat), __FUNCTION__);

                /* orig vs other formats */
                if (dataFormat == "orig") {
                    /* copy all of the series files to the temp directory */
                    foreach (QString f, series.stagedFiles) {
                        QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                        utils::SystemCommand(systemstring);
                    }
                }
                else if ((dataFormat == "anon") || (dataFormat == "anonfull")) {
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
                    if (dataFormat == "anon")
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
                else if (dataFormat.contains("nifti")) {
                    int numConv(0), numRename(0);
                    bool gzip;
                    if (dataFormat.contains("gz"))
                        gzip = true;
                    else
                        gzip = false;

                    /* get path of first file to be converted */
                    if (series.stagedFiles.size() > 0) {
                        Log(QString("Converting [%1] files to nifti").arg(series.stagedFiles.size()), __FUNCTION__, true);

                        QFileInfo f(series.stagedFiles[0]);
                        QString origSeriesPath = f.absoluteDir().absolutePath();
                        squirrelImageIO io;
                        QString m3;
                        if (!io.ConvertDicom(dataFormat, origSeriesPath, seriesPath, QDir::currentPath(), gzip, utils::CleanString(subject.ID), QString("%1").arg(study.number), QString("%1").arg(series.number), "dicom", numConv, numRename, m3)) {
                            Log(QString("ConvertDicom() failed. Returned [%1]").arg(m3), __FUNCTION__);
                        }
                    }
                    else {
                        Log(QString("Variable squirrelSeries.stagedFiles is empty. No files to convert to Nifti"), __FUNCTION__, true);
                    }
                }
                else
                    Log(QString("dataFormat [%1] not recognized").arg(dataFormat), __FUNCTION__);

                /* get the number of files and size of the series */
                qint64 c(0), b(0);
                utils::GetDirSizeAndFileCount(seriesPath, c, b, false);
                series.numFiles = c;
                series.size = b;
                series.Store();

                /* write the series .json file, containing the dicom header params */
                QString paramFilePath = QString("%1/params.json").arg(seriesPath);
                QByteArray j = QJsonDocument(series.ParamsToJSON()).toJson();
                if (!utils::WriteTextFile(paramFilePath, j))
                    Log("Error writing [" + paramFilePath + "]", __FUNCTION__, true);
            }
        }
    }

    /* ----- 2) write .json file ----- */
    Log("Creating JSON file...", __FUNCTION__);
    /* create JSON object */
    QJsonObject root;

    QJsonObject pkgInfo;
    pkgInfo["PackageName"] = name;
    pkgInfo["Description"] = description;
    pkgInfo["Datetime"] = utils::CreateCurrentDateTime(2);
    pkgInfo["PackageFormat"] = format;
    pkgInfo["SquirrelVersion"] = version;

    root["package"] = pkgInfo;

    QJsonObject data;
    QJsonArray JSONsubjects;

    /* add subjects */
    QList<squirrelSubject> subjectses = GetAllSubjects();
    foreach (squirrelSubject subject, subjectses) {
        JSONsubjects.append(subject.ToJSON());
    }

    /* add group-analyses */
    QList <squirrelGroupAnalysis> groupAnalyses = GetAllGroupAnalyses();
    if (groupAnalyses.size() > 0) {
        Log(QString("Adding [%1] group-analyses...").arg(groupAnalyses.size()), __FUNCTION__);
        QJsonArray JSONgroupanalyses;
        foreach (squirrelGroupAnalysis g, groupAnalyses) {
            if (g.Get()) {
                JSONgroupanalyses.append(g.ToJSON());
                Log(QString("Added group-analysis [%1]").arg(g.groupAnalysisName), __FUNCTION__, true);
            }
        }
        data["NumGroupAnalyses"] = JSONgroupanalyses.size();
        data["group-analysis"] = JSONgroupanalyses;
    }

    data["NumSubjects"] = JSONsubjects.size();
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
                Log(QString("Added pipeline [%1]").arg(p.pipelineName), __FUNCTION__, true);
            }
        }
        root["NumPipelines"] = JSONpipelines.size();
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
                Log(QString("Added experiment [%1]").arg(e.experimentName), __FUNCTION__, true);
            }
        }
        root["NumExperiments"] = JSONexperiments.size();
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
                Log("Added data-dictionary", __FUNCTION__, true);
            }
        }
        root["NumDataDictionaries"] = JSONdataDictionaries.size();
        root["data-dictionaries"] = JSONdataDictionaries;
    }
    root["TotalSize"] = GetUnzipSize();
    root["TotalNumFiles"] = GetNumFiles();

    /* write the final .json file */
    QString jsonFilePath = workingDir + "/squirrel.json";
    QByteArray j = QJsonDocument(root).toJson();
    if (!utils::WriteTextFile(jsonFilePath, j))
        Log("Error writing [" + jsonFilePath + "]", __FUNCTION__, true);

    QString systemstring;
    #ifdef Q_OS_WINDOWS
        systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" a \"" + zipPath + "\" \"" + workingDir + "/*\"");
    #else
        systemstring = "cd " + workingDir + "; zip -1rv " + zipPath + " .";
    #endif

    Log("Beginning zipping package", __FUNCTION__);
    utils::SystemCommand(systemstring);

    if (utils::FileExists(zipPath)) {
        QFileInfo fi(zipPath);
        qint64 zipSize = fi.size();
        Log(QString("Finished zipping package [%1]. Size is [%2] bytes").arg(zipPath).arg(zipSize), __FUNCTION__);

        /* delete the tmp dir, if it exists */
        if (utils::DirectoryExists(workingDir)) {
            Log("Temporary export dir [" + workingDir + "] exists and will be deleted", __FUNCTION__);
            QString m;
            if (!utils::RemoveDir(workingDir, m))
                Log("Error [" + m + "] removing directory [" + workingDir + "]", __FUNCTION__);
        }
    }
    else {
        Log("Error creating zip file [" + zipPath + "]", __FUNCTION__);
        return false;
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

    return true;
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
        int subjectRowID = sub.GetObjectID();
        sub.PrintSubject();

        /* iterate through studies */
        QList<squirrelStudy> studies = GetStudies(subjectRowID);
        foreach (squirrelStudy study, studies) {
            int studyRowID = study.GetObjectID();
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
    q.prepare("select sum(Size) 'Size', sum(BehSize) 'BehSize' from Series");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    unzipSize += q.value("Size").toLongLong();
    unzipSize += q.value("BehSize").toLongLong();

    return unzipSize;
}


/* ------------------------------------------------------------ */
/* ----- GetNumFiles ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get the number of files in the squirrel package
 * @return total number of files in the package
 */
qint64 squirrel::GetNumFiles() {
    qint64 total(0);

    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* Analysis */
    q.prepare("select sum(NumFiles) 'NumFiles' from Analysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("NumFiles").toLongLong();

    /* DataDictionary */
    q.prepare("select sum(NumFiles) 'NumFiles' from DataDictionary");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("NumFiles").toLongLong();

    /* Experiment */
    q.prepare("select sum(NumFiles) 'NumFiles' from Experiment");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("NumFiles").toLongLong();

    /* GroupAnalysis */
    q.prepare("select sum(NumFiles) 'NumFiles' from GroupAnalysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("NumFiles").toLongLong();

    /* Series */
    q.prepare("select sum(NumFiles) 'NumFiles', sum(BehNumFiles) 'BehNumFiles' from Series");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    q.first();
    total += q.value("NumFiles").toLongLong();
    total += q.value("BehNumFiles").toLongLong();

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
int squirrel::GetObjectCount(QString object) {
    int count(0);
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

    utils::Print("Squirrel Package: " + filePath);
    utils::Print(QString("  Date: %1").arg(datetime.toString()));
    utils::Print(QString("  Description: %1").arg(description));
    utils::Print(QString("  Name: %1").arg(name));
    utils::Print(QString("  Version: %1").arg(version));
    utils::Print(QString("  Directory Format (subject, study, series): %1, %2, %3").arg(subjectDirFormat).arg(studyDirFormat).arg(seriesDirFormat));
    utils::Print(QString("  Data Format: %1").arg(dataFormat));
    utils::Print(QString("  Files:\n    %1 files\n    %2 bytes (unzipped)").arg(GetNumFiles()).arg(GetUnzipSize()));
    utils::Print(QString("  Object count:\n    %1 subjects\n    %2 studies\n    %3 series\n    %4 measures\n    %5 drugs\n    %6 analyses\n    %7 experiments\n    %8 pipelines\n    %9 group analyses\n    %10 data dictionary").arg(numSubjects).arg(numStudies).arg(numSeries).arg(numMeasures).arg(numDrugs).arg(numAnalyses).arg(numExperiments).arg(numPipelines).arg(numGroupAnalyses).arg(numDataDictionaries));
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
 * @param destDir subdirectory in the package the files should be staged
 * @return
 */
bool squirrel::AddStagedFiles(QString objectType, int rowid, QStringList files, QString destDir) {

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
 * @brief Record a log - prints to screen and stores in log string
 * @param s log message
 * @param func function that called this function
 * @param dbg is this is a debug message, to be displayed only if debug is enabled at the command line
 */
void squirrel::Log(QString s, QString func, bool dbg) {
    if (!quiet) {
        if ((!dbg) || (debug && dbg)) {
            if (s.trimmed() != "") {
                log.append(QString("%1() %2\n").arg(func).arg(s));
                utils::Print(QString("%1() %2").arg(func).arg(s));
            }
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

    QString jsonStr;
    QFile file;
    file.setFileName(f);
    file.open(QIODevice::ReadOnly | QIODevice::Text);
    jsonStr = file.readAll();
    file.close();

    /* get the JSON document and root object */
    QJsonDocument d = QJsonDocument::fromJson(jsonStr.toUtf8());

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
    if (details)
        utils::Print(subjectIDs.join(" "));
}


/* ------------------------------------------------------------ */
/* ----- PrintStudies ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::PrintStudies print list of studies to the stdout
 * @param subjectID the subject ID to print studies for
 * @param details true to print details, false to print list of study numbers
 */
void squirrel::PrintStudies(int subjectRowID, bool details) {
    QList <squirrelStudy> studies = GetStudies(subjectRowID);
    QStringList studyNumbers;
    foreach (squirrelStudy s, studies) {
        if (s.Get()) {
            if (details)
                s.PrintStudy();
            else
                studyNumbers.append(QString("%1").arg(s.number));
        }
    }
    if (details)
        utils::Print(studyNumbers.join(" "));
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
void squirrel::PrintSeries(int studyRowID, bool details) {
    QList <squirrelSeries> series = GetSeries(studyRowID);
    QStringList seriesNumbers;
    foreach (squirrelSeries s, series) {
        if (s.Get()) {
            if (details)
                s.PrintSeries();
            else
                seriesNumbers.append(QString("%1").arg(s.number));
        }
    }
    if (details)
        utils::Print(seriesNumbers.join(" "));
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
    foreach (squirrelExperiment e, exps) {
        if (e.Get()) {
            if (details)
                e.PrintExperiment();
            else
                utils::Print(e.experimentName);
        }
    }
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
                pipelineNames.append(p.pipelineName);
        }
    }
    if (details)
        utils::Print(pipelineNames.join(" "));
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
                groupAnalysisNames.append(g.groupAnalysisName);
        }
    }
    if (details)
        utils::Print(groupAnalysisNames.join(" "));
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
                dataDictionaryNames.append(d.dataDictionaryName);
        }
    }
    if (details)
        utils::Print(dataDictionaryNames.join(" "));
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
        e.SetObjectID(q.value("ExperimentRowID").toInt());
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
        p.SetObjectID(q.value("PipelineRowID").toInt());
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
    q.prepare("select SubjectRowID from Subject order by ID asc, Sequence");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSubject s;
        s.SetObjectID(q.value("SubjectRowID").toInt());
        if (s.Get()) {
            s.SetDirFormat(subjectDirFormat);
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
QList<squirrelStudy> squirrel::GetStudies(int subjectRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelStudy> list;
    q.prepare("select StudyRowID from Study where SubjectRowID = :id order by StudyNumber asc, Sequence");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelStudy s;
        s.SetObjectID(q.value("StudyRowID").toInt());
        if (s.Get()) {
            s.SetDirFormat(subjectDirFormat, studyDirFormat);
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
QList<squirrelSeries> squirrel::GetSeries(int studyRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelSeries> list;
    q.prepare("select SeriesRowID from Series where StudyRowID = :id order by SeriesNumber asc, Sequence");
    q.bindValue(":id", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSeries s;
        s.SetObjectID(q.value("SeriesRowID").toInt());
        if (s.Get()) {
            s.SetDirFormat(subjectDirFormat, studyDirFormat, seriesDirFormat);
            list.append(s);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalyses ------------------------------------------ */
/* ------------------------------------------------------------ */
QList<squirrelAnalysis> squirrel::GetAnalyses(int studyRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelAnalysis> list;
    q.prepare("select AnalysisRowID from Analysis where StudyRowID = :id");
    q.bindValue(":id", studyRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelAnalysis a;
        a.SetObjectID(q.value("AnalysisRowID").toInt());
        if (a.Get()) {
            list.append(a);
        }
    }

    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetMeasures ------------------------------------------ */
/* ------------------------------------------------------------ */
QList<squirrelMeasure> squirrel::GetMeasures(int subjectRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelMeasure> list;
    q.prepare("select MeasureRowID from Measure where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelMeasure m;
        m.SetObjectID(q.value("MeasureRowID").toInt());
        if (m.Get()) {
            list.append(m);
        }
    }
    return list;
}


/* ------------------------------------------------------------ */
/* ----- GetDrugs --------------------------------------------- */
/* ------------------------------------------------------------ */
QList<squirrelDrug> squirrel::GetDrugs(int subjectRowID) {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelDrug> list;
    q.prepare("select DrugRowID from Drug where SubjectRowID = :id");
    q.bindValue(":id", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelDrug d;
        d.SetObjectID(q.value("DrugRowID").toInt());
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
 * @brief squirrel::GetAllGroupAnalyses
 * @return list of all groupAnalysis objects
 */
QList<squirrelGroupAnalysis> squirrel::GetAllGroupAnalyses() {
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    QList<squirrelGroupAnalysis> list;
    q.prepare("select GroupAnalysisRowID from GroupAnalysis");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelGroupAnalysis g;
        g.SetObjectID(q.value("GroupAnalysisRowID").toInt());
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
 * @brief squirrel::GetAllDataDictionaries
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
 * @brief squirrel::FindSubject
 * @param id the PatientID field
 * @return the database rowID of the subject if found, -1 otherwise
 */
int squirrel::FindSubject(QString id) {
    int rowid(-1);
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Subject where ID = :id");
    q.bindValue(":id", id);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SubjectRowID").toInt();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindStudy -------------------------------------------- */
/* ------------------------------------------------------------ */
int squirrel::FindStudy(QString subjectID, int studyNum) {
    int rowid(-1);
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Study a left join Subject b on a.SubjectRowID = b.SubjectRowID where a.StudyNumber = :studynum and b.ID = :id");
    q.bindValue(":studynum", studyNum);
    q.bindValue(":id", subjectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SubjectRowID").toInt();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindStudyByUID --------------------------------------- */
/* ------------------------------------------------------------ */
int squirrel::FindStudyByUID(QString studyUID) {
    int rowid(-1);
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Study where StudyUID = :studyuid");
    q.bindValue(":studyuid", studyUID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        q.first();
        rowid = q.value("StudyRowID").toInt();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindSeries ------------------------------------------- */
/* ------------------------------------------------------------ */
int squirrel::FindSeries(QString subjectID, int studyNum, int seriesNum) {
    int rowid(-1);
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Series a left join Study b on a.StudyRowID = b.StudyRowID left join Subject c on b.SubjectRowID = b.SubjectRowID where a.SeriesNumber = :seriesnum and b.StudyNumber = :studynum and c.ID = :id");
    q.bindValue(":seriesnum", seriesNum);
    q.bindValue(":studynum", studyNum);
    q.bindValue(":id", subjectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        rowid = q.value("SubjectRowID").toInt();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- FindSeriesByUID -------------------------------------- */
/* ------------------------------------------------------------ */
int squirrel::FindSeriesByUID(QString seriesUID) {
    int rowid(-1);
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Series where SeriesUID = :seriesuid");
    q.bindValue(":seriesuid", seriesUID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        //q.first();
        rowid = q.value("SeriesRowID").toInt();
    }
    return rowid;
}


/* ------------------------------------------------------------ */
/* ----- ResequenceSubjects ----------------------------------- */
/* ------------------------------------------------------------ */
void squirrel::ResequenceSubjects() {

    QList<squirrelSubject> subjects = GetAllSubjects();
    int i = 1;
    foreach (squirrelSubject subject, subjects) {
        subject.sequence = i;
        subject.Store();
        i++;
    }
}


/* ------------------------------------------------------------ */
/* ----- ResequenceStudies ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::ResequenceStudies
 * @param subjectRowID
 * Renumber the sequence field for the studies associated with
 * this subject
 */
void squirrel::ResequenceStudies(int subjectRowID) {

    QList<squirrelStudy> studies = GetStudies(subjectRowID);
    int i = 1;
    foreach (squirrelStudy study, studies) {
        study.sequence = i;
        study.Store();
        i++;
    }
}


/* ------------------------------------------------------------ */
/* ----- ResequenceSeries ------------------------------------- */
/* ------------------------------------------------------------ */
void squirrel::ResequenceSeries(int studyRowID) {

    QList<squirrelSeries> serieses = GetSeries(studyRowID);
    int i = 1;
    foreach (squirrelSeries series, serieses) {
        series.sequence = i;
        series.Store();
        i++;
    }
}
