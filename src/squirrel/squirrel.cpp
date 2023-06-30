/* ------------------------------------------------------------------------------
  Squirrel squirrel.cpp
  Copyright (C) 2004 - 2023
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

/* ------------------------------------------------------------ */
/* ----- squirrel --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::squirrel
 */
squirrel::squirrel()
{
    datetime = QDateTime::currentDateTime();
    description = "Uninitialized squirrel package";
    name = "Squirrel package";
    version = QString("%1.%2").arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN);
    format = "squirrel";
    subjectDirFormat = "orig";
    studyDirFormat = "orig";
    seriesDirFormat = "orig";
    dataFormat = "nifti4dgz";
    isOkToDelete = true;

    MakeTempDir(workingDir);
    Log("Created squirrel object", __FUNCTION__);
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
        RemoveDir(workingDir, m);
        Log(QString("Removed directory [%1] [%2]").arg(workingDir).arg(m), __FUNCTION__);
    }
    Log("Deleting squirrel object", __FUNCTION__);
}


/* ------------------------------------------------------------ */
/* ----- read ------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Reads a squirrel package into memory from disk
 * @param filename Full filepath of the package to read
 * @return true if package was successfully read, false otherwise
 */
bool squirrel::read(QString filepath, bool validateOnly) {

    if (validateOnly)
        Log(QString("Validating " + filepath), __FUNCTION__);
    else
        Log(QString("Reading " + filepath), __FUNCTION__);

    /* check if file exists */
    if (!FileExists(filepath)) {
        Log(QString("File " + filepath + " does not exist"), __FUNCTION__);
        return false;
    }

    /* get listing of the zip the file, check if the squirrel.json exists in the root */
    QString systemstring;
    #ifdef Q_OS_WINDOWS
        systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" l \"" + filepath + "\"");
    #else
        systemstring = "unzip -l " + filepath;
    #endif
    QString output = SystemCommand(systemstring, true);
    Log(output, __FUNCTION__);
    if (!output.contains("squirrel.json")) {
        Log(QString("File " + filepath + " does not appear to be a squirrel package"), __FUNCTION__);
        return false;
    }

    /* create a working directory */
    MakeTempDir(workingDir);
    Log(QString("Created temp directory [" + workingDir + "]"), __FUNCTION__);

    /* unzip the .zip to the working dir */
    #ifdef Q_OS_WINDOWS
        systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" x \"" + filepath + "\" -o\"" + workingDir + "\" -y");
    #else
        systemstring = QString("unzip " + filepath + " -d " + workingDir);
    #endif
    output = SystemCommand(systemstring, true);
    Log(output, __FUNCTION__);

    /* read from .json file */
    QString jsonStr;
    QFile file;
    file.setFileName(workingDir + "/squirrel.json");
    file.open(QIODevice::ReadOnly | QIODevice::Text);
    jsonStr = file.readAll();
    file.close();

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

        sqrlSubject.ID = jsonSubject["ID"].toString();
        sqrlSubject.alternateIDs = jsonSubject["ID"].toVariant().toStringList();
        sqrlSubject.GUID = jsonSubject["GUID"].toString();
        sqrlSubject.dateOfBirth.fromString(jsonSubject["dateOfBirth"].toString(), "yyyy-MM-dd");
        sqrlSubject.sex = jsonSubject["sex"].toString();
        sqrlSubject.gender = jsonSubject["gender"].toString();
        sqrlSubject.ethnicity1 = jsonSubject["ethnicity1"].toString();
        sqrlSubject.ethnicity2 = jsonSubject["ethnicity2"].toString();
        sqrlSubject.virtualPath = jsonSubject["path"].toString();

        Log(QString("Found subject [" + sqrlSubject.ID + "]"), __FUNCTION__);

        /* loop through and read all studies */
        QJsonArray jsonStudies = jsonSubject["studies"].toArray();
        for (auto v : jsonStudies) {
            QJsonObject jsonStudy = v.toObject();
            squirrelStudy sqrlStudy;

            sqrlStudy.number = jsonStudy["number"].toInteger();
            sqrlStudy.dateTime.fromString(jsonStudy["studyDateTime"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlStudy.ageAtStudy = jsonStudy["ageAtStudy"].toDouble();
            sqrlStudy.height = jsonStudy["height"].toDouble();
            sqrlStudy.weight = jsonStudy["weight"].toDouble();
            sqrlStudy.modality = jsonStudy["modality"].toString();
            sqrlStudy.description = jsonStudy["description"].toString();
            sqrlStudy.studyUID = jsonStudy["studyUID"].toString();
            sqrlStudy.visitType = jsonStudy["visitType"].toString();
            sqrlStudy.dayNumber = jsonStudy["dayNumber"].toString();
            sqrlStudy.timePoint = jsonStudy["timePoint"].toString();
            sqrlStudy.equipment = jsonStudy["equipment"].toString();
            sqrlStudy.virtualPath = jsonStudy["virtualPath"].toString();

            /* loop through and read all series */
            QJsonArray jsonSeries = jsonStudy["series"].toArray();
            for (auto v : jsonSeries) {
                QJsonObject jsonSeries = v.toObject();
                squirrelSeries sqrlSeries;

                sqrlSeries.number = jsonSeries["number"].toInteger();
                sqrlSeries.dateTime.fromString(jsonSeries["dateTime"].toString(), "yyyy-MM-dd hh:mm:ss");
                sqrlSeries.seriesUID = jsonSeries["seriesUID"].toString();
                sqrlSeries.description = jsonSeries["number"].toString();
                sqrlSeries.protocol = jsonSeries["number"].toString();
                sqrlSeries.experimentList.append(jsonSeries["experiment"].toString());
                sqrlSeries.size = jsonSeries["size"].toInteger();
                sqrlSeries.numFiles = jsonSeries["numFiles"].toInteger();
                sqrlSeries.behSize = jsonSeries["behSize"].toInteger();
                sqrlSeries.numBehFiles = jsonSeries["behNumFiles"].toInteger();

                /* read any params from the data/Subject/Study/Series/params.json file */
                //QString jsonStr2;
                //QFile file;
                //file.setFileName(QString("%1/data/%2/%3/%4/params.json").arg(workingDir).arg(sqrlSubject.ID).arg(sqrlStudy.number).arg(sqrlSeries.number));
                //file.open(QIODevice::ReadOnly | QIODevice::Text);
                //jsonStr2 = file.readAll();
                //file.close();

                /* get the JSON document and root object */
                //QJsonDocument d = QJsonDocument::fromJson(jsonStr2.toUtf8());

                //QHash<QString, QString> tags;

                //QJsonObject json = d.object();
                //foreach(const QString& key, json.keys()) {
                //    tags[key] = json.value(key).toString();
                //}

                sqrlSeries.params = ReadParamsFile(QString("%1/data/%2/%3/%4/params.json").arg(workingDir).arg(sqrlSubject.ID).arg(sqrlStudy.number).arg(sqrlSeries.number));

                /* add this series to the study */
                sqrlStudy.addSeries(sqrlSeries);
            }

            /* add this study to the subject */
            if (sqrlSubject.addStudy(sqrlStudy)) {
                Log(QString("Added study [%1]").arg(sqrlStudy.number), __FUNCTION__);
            }
        }

        /* read all measures */
        QJsonArray jsonMeasures = jsonSubject["measures"].toArray();
        Log(QString("Found [%1] measures").arg(jsonMeasures.size()), __FUNCTION__);
        for (auto v : jsonMeasures) {
            QJsonObject jsonMeasure = v.toObject();
            squirrelMeasure sqrlMeasure;

            sqrlMeasure.dateEnd.fromString(jsonMeasure["dateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.dateStart.fromString(jsonMeasure["dateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlMeasure.measureName = jsonMeasure["measureName"].toString();
            sqrlMeasure.instrumentName = jsonMeasure["instrumentName"].toString();
            sqrlMeasure.rater = jsonMeasure["rater"].toString();
            sqrlMeasure.notes = jsonMeasure["notes"].toString();
            sqrlMeasure.value = jsonMeasure["value"].toString();
            sqrlMeasure.description = jsonMeasure["description"].toString();

            sqrlSubject.addMeasure(sqrlMeasure);
        }

        /* read all drugs */
        QJsonArray jsonDrugs = jsonSubject["drugs"].toArray();
        Log(QString("Found [%1] drugs").arg(jsonDrugs.size()), __FUNCTION__);
        for (auto v : jsonDrugs) {
            QJsonObject jsonDrug = v.toObject();
            squirrelDrug sqrlDrug;

            sqrlDrug.dateEnd.fromString(jsonDrug["dateEnd"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.dateStart.fromString(jsonDrug["dateStart"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.dateEntry.fromString(jsonDrug["dateEntry"].toString(), "yyyy-MM-dd hh:mm:ss");
            sqrlDrug.drugName = jsonDrug["drugName"].toString();
            sqrlDrug.doseAmount = jsonDrug["doseAmount"].toDouble();
            sqrlDrug.doseFrequency = jsonDrug["doseFrequency"].toString();
            sqrlDrug.route = jsonDrug["route"].toString();
            sqrlDrug.type = jsonDrug["type"].toString();
            sqrlDrug.doseKey = jsonDrug["doseKey"].toString();
            sqrlDrug.doseUnit = jsonDrug["doseUnit"].toString();
            sqrlDrug.frequencyModifier = jsonDrug["frequencyModifier"].toString();
            sqrlDrug.frequencyValue = jsonDrug["frequencyValue"].toDouble();
            sqrlDrug.frequencyUnit = jsonDrug["frequencyUnit"].toString();
            sqrlDrug.description = jsonDrug["description"].toString();
            sqrlDrug.rater = jsonDrug["rater"].toString();
            sqrlDrug.notes = jsonDrug["notes"].toString();

            sqrlSubject.addDrug(sqrlDrug);
        }

        /* add the subject */
        if (addSubject(sqrlSubject)) {
            Log(QString("Added subject [" + sqrlSubject.ID + "]"), __FUNCTION__);
        }
    }

    /* read all experiments */
    QJsonArray jsonExperiments;
    jsonExperiments = root["experiments"].toArray();
    Log(QString("Found [%1] experiments").arg(jsonExperiments.size()), __FUNCTION__);
    for (auto v : jsonExperiments) {
        QJsonObject jsonExperiment = v.toObject();
        squirrelExperiment sqrlExperiment;

        sqrlExperiment.experimentName = jsonExperiment["experimentName"].toString();
        sqrlExperiment.numFiles = jsonExperiment["numFiles"].toInt();
        sqrlExperiment.size = jsonExperiment["size"].toInt();

        experimentList.append(sqrlExperiment);
    }

    /* read all pipelines */
    QJsonArray jsonPipelines;
    jsonPipelines = root["pipelines"].toArray();
    Log(QString("Found [%1] pipelines").arg(jsonPipelines.size()), __FUNCTION__);
    for (auto v : jsonPipelines) {
        QJsonObject jsonPipeline = v.toObject();
        squirrelPipeline sqrlPipeline;

        sqrlPipeline.clusterQueue = jsonPipeline["clusterQueue"].toString();
        sqrlPipeline.clusterSubmitHost = jsonPipeline["clusterSubmitHost"].toString();
        sqrlPipeline.clusterUser = jsonPipeline["clusterUser"].toString();
        sqrlPipeline.createDate.fromString(jsonPipeline["createDate"].toString(), "yyyy-MM-dd hh:mm:ss");
        sqrlPipeline.dataCopyMethod = jsonPipeline["dataCopyMethod"].toString();
        sqrlPipeline.depDir = jsonPipeline["depDir"].toString();
        sqrlPipeline.depLevel = jsonPipeline["depLevel"].toString();
        sqrlPipeline.depLinkType = jsonPipeline["depLinkType"].toString();
        sqrlPipeline.description = jsonPipeline["description"].toString();
        sqrlPipeline.dirStructure = jsonPipeline["dirStructure"].toString();
        sqrlPipeline.directory = jsonPipeline["directory"].toString();
        sqrlPipeline.group = jsonPipeline["group"].toString();
        sqrlPipeline.groupType = jsonPipeline["groupType"].toString();
        sqrlPipeline.level = jsonPipeline["level"].toString();
        sqrlPipeline.pipelineName = jsonPipeline["name"].toString();
        sqrlPipeline.notes = jsonPipeline["notes"].toString();
        sqrlPipeline.numConcurrentAnalyses = jsonPipeline["numConcurrentAnalyses"].toInt();
        sqrlPipeline.parentPipelines = jsonPipeline["parentPipelines"].toString().split(",");
        sqrlPipeline.resultScript = jsonPipeline["resultScript"].toString();
        sqrlPipeline.submitDelay = jsonPipeline["submitDelay"].toInt();
        sqrlPipeline.tmpDir = jsonPipeline["tmpDir"].toString();
        sqrlPipeline.version = jsonPipeline["version"].toInt();
        sqrlPipeline.flags.useProfile = jsonPipeline["useProfile"].toBool();
        sqrlPipeline.flags.useTmpDir = jsonPipeline["useTmpDir"].toBool();

        QJsonArray jsonCompleteFiles;
        jsonCompleteFiles = jsonPipeline["completeFiles"].toArray();
        for (auto v : jsonCompleteFiles) {
            sqrlPipeline.completeFiles.append(v.toString());
        }

        /* read the pipeline data steps */
        QJsonArray jsonDataSteps;
        jsonDataSteps = jsonPipeline["dataSteps"].toArray();
        for (auto v : jsonDataSteps) {
            QJsonObject jsonDataStep = v.toObject();
            dataStep ds;
            ds.associationType = jsonDataStep["associationType"].toString();
            ds.behDir = jsonDataStep["behDir"].toString();
            ds.behFormat = jsonDataStep["behFormat"].toString();
            ds.dataFormat = jsonDataStep["dataFormat"].toString();
            ds.imageType = jsonDataStep["imageType"].toString();
            ds.datalevel = jsonDataStep["dataLevel"].toString();
            ds.location = jsonDataStep["location"].toString();
            ds.modality = jsonDataStep["modality"].toString();
            ds.numBOLDreps = jsonDataStep["numBOLDreps"].toString();
            ds.numImagesCriteria = jsonDataStep["numImagesCriteria"].toString();
            ds.order = jsonDataStep["order"].toInt();
            ds.protocol = jsonDataStep["protocol"].toString();
            ds.seriesCriteria = jsonDataStep["seriesCriteria"].toString();
            ds.protocol = jsonDataStep["protocol"].toString();
            ds.flags.enabled = jsonDataStep["enabled"].toBool();
            ds.flags.optional = jsonDataStep["optional"].toBool();
            ds.flags.gzip = jsonDataStep["gzip"].toBool();
            ds.flags.usePhaseDir = jsonDataStep["usePhaseDir"].toBool();
            ds.flags.useSeries = jsonDataStep["useSeries"].toBool();
            ds.flags.preserveSeries = jsonDataStep["preserveSeries"].toBool();
            ds.flags.primaryProtocol = jsonDataStep["primaryProtocol"].toBool();
            sqrlPipeline.dataSteps.append(ds);
        }
        pipelineList.append(sqrlPipeline);
    }

    /* If we're only validating: delete the tmpdir if it exists */
    if (validateOnly) {
        if (DirectoryExists(workingDir)) {
            Log(QString("Temporary export dir [" + workingDir + "] exists and will be deleted"), __FUNCTION__);
            QString m;
            if (!RemoveDir(workingDir, m))
                Log(QString("Error [" + m + "] removing directory [" + workingDir + "]"), __FUNCTION__);
        }
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- write ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::write Writes a squirrel package using stored information
 * @param outpath full path to the output squirrel .zip file
 * @param dataFormat if converting from DICOM, write the data in the specified format
 *                   - 'orig' - Perform no conversion of DICOM images (not recommended as it retains PHI)
 *                   - 'anon' - Anonymize DICOM files (light anonymization: remove PHI, but not ID or dates)
 *                   - 'anonfull' - Anonymize DICOM files (full anonymization)
 *                   - 'nifti4d' - Attempt to convert any convertable images to Nifti 4D
 *                   - 'nifti4dgz' - Attempt to convert any convertable images to Nifti 4D gzip [DEFAULT]
 *                   - 'nidti3d' - Attempt to convert any convertable images to Nifti 3D
 *                   - 'nifti3dgz' - Attempt to convert any convertable images to Nifti 3D gzip
 * @param subjectDirFormat directory structure of the subject data
 *                  - 'orig' - Use the subjectID for subject directories [DEFAULT]
 *                  - 'seq' - Use sequentially generated numbers for subject directories
 * @param studyDirFormat directory structure of the subject data
 *                  - 'orig' - Use the studyNum for study directories [DEFAULT]
 *                  - 'seq' - Use sequentially generated numbers for study directories
 * @param seriesDirFormat directory structure of the subject data
 *                  - 'orig' - Use the seriesNum for series directories [DEFAULT]
 *                  - 'seq' - Use sequentially generated numbers for series directories
 * @return true if package was successfully written, false otherwise
 */
bool squirrel::write(QString outpath, QString &filepath) {

    /* create the log file */
    QFileInfo finfo(outpath);
    logfile = QString(finfo.absolutePath() + "/squirrel-" + CreateLogDate() + ".log");

    Log("Beginning writing of squirrel package", __FUNCTION__);

    /* create temp directory */
    //MakeTempDir(workingDir);
    Log(QString("Created working directory [" + workingDir + "]"), __FUNCTION__);

    /* ----- 1) write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

        squirrelSubject sub = subjectList[i];

        QString subjDir;
        if (subjectDirFormat == "orig") {
            subjDir = sub.ID;
            Log(QString("sub.ID [" + sub.ID + "]"), __FUNCTION__);
        }
        else {
            subjDir = QString("%1").arg(i+1); /* start the numbering at 1 instead of 0 */
        }

        subjDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
        QString vPath = QString("data/%1").arg(subjDir);
        subjectList[i].virtualPath = vPath;

        Log(QString("Working on subject [" + subjDir + "]"), __FUNCTION__);

        /* iterate through studies */
        for (int j=0; j < sub.studyList.size(); j++) {

            squirrelStudy stud = sub.studyList[j];

            QString studyDir;
            if (studyDirFormat == "orig")
                studyDir = QString("%1").arg(stud.number);
            else
                studyDir = QString("%1").arg(j+1); /* start the numbering at 1 instead of 0 */

            studyDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
            QString vPath = QString("data/%1/%2").arg(subjDir).arg(studyDir);
            subjectList[i].studyList[j].virtualPath = vPath;

            Log(QString("Working on study [" + studyDir + "]"), __FUNCTION__);

            /* iterate through series */
            for (int k=0; k < stud.seriesList.size(); k++) {

                squirrelSeries ser = stud.seriesList[k];

                QString seriesDir;
                if (seriesDirFormat == "orig")
                    seriesDir = QString("%1").arg(ser.number);
                else
                    seriesDir = QString("%1").arg(k+1); /* start the numbering at 1 instead of 0 */

                seriesDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
                QString vPath = QString("data/%1/%2/%3").arg(subjDir).arg(studyDir).arg(seriesDir);
                subjectList[i].studyList[j].seriesList[k].virtualPath = vPath;

                QString m;
                QString seriesPath = QString("%1/%2").arg(workingDir).arg(subjectList[i].studyList[j].seriesList[k].virtualPath);
                MakePath(seriesPath,m);

                Log(QString("Working on series [" + seriesDir + "]"), __FUNCTION__);
                Log(QString("Package data format [" + dataFormat + "]"), __FUNCTION__);

                /* orig vs other formats */
                if (dataFormat == "orig") {
                    Log(QString("Squirrel: dataformat original [%1]").arg(dataFormat), __FUNCTION__);

                    /* copy all of the series files to the temp directory */
                    foreach (QString f, ser.stagedFiles) {
                        QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                        SystemCommand(systemstring);
                    }
                }
                else if ((dataFormat == "anon") || (dataFormat == "anonfull")) {
                    Log(QString("Squirrel: dataformat anonymize [%1]").arg(dataFormat), __FUNCTION__);
                    /* create temp directory */
                    QString td;
                    MakeTempDir(td);

                    /* copy all files to temp directory */
                    QString systemstring;
                    foreach (QString f, ser.stagedFiles) {
                        systemstring = QString("cp -uv %1 %2").arg(f).arg(td);
                        SystemCommand(systemstring);
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
                    SystemCommand(systemstring);

                    /* delete temp directory */
                    QString m2;
                    RemoveDir(td, m2);
                }
                else if (dataFormat.contains("nifti")) {
                    Log(QString("dataformat nifti [%1]").arg(dataFormat), __FUNCTION__);
                    int numConv(0), numRename(0);
                    //QString format = dataFormat.left(5);
                    bool gzip;
                    if (dataFormat.contains("gz"))
                        gzip = true;
                    else
                        gzip = false;

                    /* get path of first file to be converted */
                    if (ser.stagedFiles.size() > 0) {
                        Log(QString("Converting [%1] files to nifti").arg(ser.stagedFiles.size()), __FUNCTION__);

                        QFileInfo f(ser.stagedFiles[0]);
                        QString origSeriesPath = f.absoluteDir().absolutePath();
                        squirrelImageIO io;
                        QString m3;
                        io.ConvertDicom(dataFormat, origSeriesPath, seriesPath, QDir::currentPath(), gzip, subjDir, studyDir, seriesDir, "dicom", numConv, numRename, m3);
                        Log(QString("ConvertDicom() returned [%1]").arg(m3), __FUNCTION__);
                    }
                    else {
                        Log(QString("Variable squirrelSeries.stagedFiles is empty. No files to convert to Nifti"), __FUNCTION__);
                    }
                }
                else
                    Log(QString("dataFormat not found [%1]").arg(dataFormat), __FUNCTION__);

                /* get the number of files and size of the series */
                qint64 c(0), b(0);
                //Log(QString("Running GetDirSizeAndFileCount() on [%1]").arg(seriesPath), __FUNCTION__);
                GetDirSizeAndFileCount(seriesPath, c, b, false);
                //Log(QString("GetDirSizeAndFileCount() found  [%1] files   [%2] bytes").arg(c).arg(b), __FUNCTION__);
                subjectList[i].studyList[j].seriesList[k].numFiles = c;
                subjectList[i].studyList[j].seriesList[k].size = b;

                /* write the series .json file, containing the dicom header params */
                QJsonObject params;
                params = ser.ParamsToJSON();
                QByteArray j = QJsonDocument(params).toJson();
                QFile fout(QString("%1/params.json").arg(seriesPath));
                if (fout.open(QIODevice::WriteOnly)) {
                    fout.write(j);
                    Log(QString("Wrote %1/params.json").arg(seriesPath), __FUNCTION__);
                }
                else {
                    Log(QString("Error writing [%1]").arg(fout.fileName()), __FUNCTION__);
                    Log(QString("Error writing %1/params.json").arg(seriesPath), __FUNCTION__);
                }
            }
        }
    }

    /* ----- 2) write .json file ----- */
    /* create JSON object */
    QJsonObject root;

    QJsonObject pkgInfo;
    pkgInfo["name"] = name;
    pkgInfo["description"] = description;
    pkgInfo["datetime"] = CreateCurrentDateTime(2);
    pkgInfo["format"] = format;
    pkgInfo["version"] = version;

    root["package"] = pkgInfo;

    QJsonObject data;
    QJsonArray JSONsubjects;
    /* add subjects */
    for (int i=0; i < subjectList.size(); i++) {
        JSONsubjects.append(subjectList[i].ToJSON());
    }
    data["numSubjects"] = JSONsubjects.size();
    data["subjects"] = JSONsubjects;
    root["data"] = data;

    /* add pipelines */
    Log(QString("Adding [%1] pipelines to JSON file").arg(pipelineList.size()), __FUNCTION__);
    if (pipelineList.size() > 0) {
        Log(QString("Adding pipelines to JSON file"), __FUNCTION__);
        QJsonArray JSONpipelines;
        for (int i=0; i < pipelineList.size(); i++) {
            JSONpipelines.append(pipelineList[i].ToJSON(workingDir));
        }
        root["numPipelines"] = JSONpipelines.size();
        root["pipelines"] = JSONpipelines;
    }

    /* add experiments */
    Log(QString("Adding [%1] experiments to JSON file").arg(experimentList.size()), __FUNCTION__);
    if (experimentList.size() > 0) {
        QJsonArray JSONexperiments;
        for (int i=0; i < experimentList.size(); i++) {
            JSONexperiments.append(experimentList[i].ToJSON());
        }
        root["numExperiments"] = JSONexperiments.size();
        root["experiments"] = JSONexperiments;
    }

    /* write the final .json file */
    QByteArray j = QJsonDocument(root).toJson();
    QFile fout(QString("%1/squirrel.json").arg(workingDir));
    fout.open(QIODevice::WriteOnly);
    fout.write(j);
    fout.close();

    Log(QString("Wrote %1/squirrel.json").arg(workingDir), __FUNCTION__);

    /* zip the temp directory into the output file */
    QString zipfile = outpath;
    if (!zipfile.endsWith(".zip"))
        zipfile += ".zip";

    QString systemstring;
    #ifdef Q_OS_WINDOWS
        systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" a \"" + zipfile + "\" \"" + workingDir + "/*\"");
    #else
        systemstring = "zip -1rv " + zipfile + ".";
    #endif

    Log("Beginning zipping package...", __FUNCTION__);
    SystemCommand(systemstring, false);
    Log("Finished zipping package...", __FUNCTION__);

    if (FileExists(zipfile)) {
        Log("Created .zip file [" + zipfile + "]", __FUNCTION__);
        filepath = zipfile;

        /* delete the tmp dir, if it exists */
        if (DirectoryExists(workingDir)) {
            Log("Temporary export dir [" + workingDir + "] exists and will be deleted", __FUNCTION__);
            QString m;
            if (!RemoveDir(workingDir, m))
                Log("Error [" + m + "] removing directory [" + workingDir + "]", __FUNCTION__);
        }
    }
    else {
        Log("Error creating zip file [" + zipfile + "]", __FUNCTION__);
        return false;
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Validate if a squirrel package is readable
 * @return true if valid squirrel file, false otherwise
 */
bool squirrel::validate() {

    return true;
}


/* ------------------------------------------------------------ */
/* ----- print ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Print the details of a package, including all objects
 */
void squirrel::print() {

    /* print package info */
    PrintPackage();

    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

        squirrelSubject sub = subjectList[i];
        sub.PrintSubject();

        /* iterate through studies */
        //Log(QString("Iterating through [%1] studies").arg(sub.studyList.size()), __FUNCTION__);
        for (int j=0; j < sub.studyList.size(); j++) {

            squirrelStudy stud = sub.studyList[j];
            stud.PrintStudy();

            /* iterate through series */
            for (int k=0; k < stud.seriesList.size(); k++) {

                squirrelSeries ser = stud.seriesList[k];
                ser.PrintSeries();
            }

            /* iterate through analyses */
            for (int k=0; k < stud.analysisList.size(); k++) {

                squirrelAnalysis an = stud.analysisList[k];
                an.PrintAnalysis();
            }
        }

        /* iterate through measures */
        for (int j=0; j < sub.measureList.size(); j++) {

            squirrelMeasure meas = sub.measureList[j];
            meas.PrintMeasure();
        }

        /* iterate through drugs */
        for (int j=0; j < sub.drugList.size(); j++) {

            squirrelDrug drug = sub.drugList[j];
            drug.PrintDrug();
        }
    }

    /* iterate through pipelines */
    for (int i=0; i < pipelineList.size(); i++) {
        squirrelPipeline pipe = pipelineList[i];
        pipe.PrintPipeline();
    }

    /* iterate through experiments */
    for (int i=0; i < experimentList.size(); i++) {
        squirrelExperiment exp = experimentList[i];
        exp.PrintExperiment();
    }
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

    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {
        squirrelSubject sub = subjectList[i];
        /* iterate through studies */
        for (int j=0; j < sub.studyList.size(); j++) {
            squirrelStudy stud = sub.studyList[j];
            /* iterate through series */
            for (int k=0; k < stud.seriesList.size(); k++) {
                squirrelSeries ser = stud.seriesList[k];
                unzipSize += ser.size;
            }
            /* iterate through analyses */
            for (int k=0; k < stud.analysisList.size(); k++) {
                squirrelAnalysis an = stud.analysisList[k];
                unzipSize += an.size;
            }
        }
    }
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

    qint64 numFiles(0);

    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {
        squirrelSubject sub = subjectList[i];
        /* iterate through studies */
        for (int j=0; j < sub.studyList.size(); j++) {
            squirrelStudy stud = sub.studyList[j];
            /* iterate through series */
            for (int k=0; k < stud.seriesList.size(); k++) {
                squirrelSeries ser = stud.seriesList[k];
                numFiles += ser.numFiles;
            }
        }
    }
    return numFiles;
}


/* ------------------------------------------------------------ */
/* ----- addSubject ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add a subject to the package
 * @param subj squirrelSubject to be added
 * @return true if added, false otherwise
 */
bool squirrel::addSubject(squirrelSubject subj) {

    /* check size of the subject list before and after adding */
    qint64 size = subjectList.size();

    subjectList.append(subj);

    if (subjectList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- addPipeline ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Add a pipeline to the package
 * @param pipe squirrelPipeline to be added
 * @return true if added, false otherwise
 */
bool squirrel::addPipeline(squirrelPipeline pipe) {

    /* check size of the pipeline list before and after adding */
    qint64 size = pipelineList.size();

    pipelineList.append(pipe);

    if (pipelineList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- addExperiment ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add an experiment to the package
 * @param exp a squirrelExperiment to be added
 * @return true if added, false if not added
 */
bool squirrel::addExperiment(squirrelExperiment exp) {

    /* check size of the pipeline list before and after adding */
    qint64 size = experimentList.size();

    experimentList.append(exp);

    if (experimentList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- removeSubject ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Removed a subject, by ID, from the package
 * @param ID subject ID to be removed
 * @return true if subject found and removed, false otherwise
 */
bool squirrel::removeSubject(QString ID) {

    for(int i=0; i < subjectList.count(); ++i) {
        if (subjectList[i].ID == ID) {
            subjectList.remove(i);
            return true;
        }
    }
    return false;
}


/* ------------------------------------------------------------ */
/* ----- PrintPackage ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print package details
 */
void squirrel::PrintPackage() {
    Print("-- SQUIRREL PACKAGE ----------");
    Print(QString("Date: %1").arg(datetime.toString()));
    Print(QString("Description: %1").arg(description));
    Print(QString("Name: %1").arg(name));
    Print(QString("Version: %1").arg(version));
    Print(QString("subjectDirFormat: %1").arg(subjectDirFormat));
    Print(QString("studyDirFormat: %1").arg(studyDirFormat));
    Print(QString("seriesDirFormat: %1").arg(seriesDirFormat));
    Print(QString("dataFormat: %1").arg(dataFormat));
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
        d = QString("C:/tmp/%1").arg(GenerateRandomString(20));
    #else
        d = QString("/tmp/%1").arg(GenerateRandomString(20));
    #endif

    QString m;
    if (MakePath(d, m)) {
        dir = d;
        return true;
    }
    else {
        dir = "";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- GetSubjectIndex -------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for index of a subject
 * @param ID subject ID
 * @return index of the subject, if found. -1 if not found
 */
int squirrel::GetSubjectIndex(QString ID) {

    /* find subject by ID */
    for (int i=0; i < subjectList.size(); i++) {
        if (subjectList[i].ID == ID) {
            return i;
        }
    }

    return -1;
}


/* ------------------------------------------------------------ */
/* ----- GetStudyIndex ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for index of a study
 * @param ID subject ID
 * @param studyNum study number
 * @return index of the study if found, -1 otherwise
 */
int squirrel::GetStudyIndex(QString ID, int studyNum) {

    /* first, find subject by ID */
    for (int i=0; i < subjectList.size(); i++) {
        if (subjectList[i].ID == ID) {
            /* next, find study by number */
            for (int j=0; j < subjectList[i].studyList.size(); j++) {
                if (subjectList[i].studyList[j].number == studyNum) {
                    return j;
                }
            }
        }
    }

    return -1;
}


/* ------------------------------------------------------------ */
/* ----- GetSeriesIndex --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for index of a series
 * @param ID subject ID
 * @param studyNum study number
 * @param seriesNum series number
 * @return index of the series if found, -1 otherwise
 */
int squirrel::GetSeriesIndex(QString ID, int studyNum, int seriesNum) {
    squirrelSubject sqrlSubject;
    squirrelStudy sqrlStudy;
    bool subjectFound = false;
    bool studyFound = false;

    /* first, find subject by ID */
    for (int i=0; i < subjectList.size(); i++) {
        if (subjectList[i].ID == ID) {
            sqrlSubject = subjectList[i];
            subjectFound = true;
            break;
        }
    }

    /* next, find study by number */
    if (subjectFound) {
        for (int j=0; j < sqrlSubject.studyList.size(); j++) {
            if (sqrlSubject.studyList[j].number == studyNum) {
                sqrlStudy = sqrlSubject.studyList[j];
                studyFound = true;
                break;
            }
        }
    }

    /* then, find series by number */
    if (studyFound) {
        for (int k=0; k < sqrlStudy.seriesList.size(); k++) {
            if (sqrlStudy.seriesList[k].number == seriesNum) {
                return k;
            }
        }
    }

    return -1;
}


/* ------------------------------------------------------------ */
/* ----- GetPipelineIndex ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for index of a pipeline
 * @param pipelineName pipeline name
 * @return index of pipeline if found, -1 otherwise
 */
int squirrel::GetPipelineIndex(QString pipelineName) {

    /* find pipeline by name */
    for (int i=0; i < pipelineList.size(); i++) {
        if (pipelineList[i].pipelineName == pipelineName) {
            return i;
        }
    }

    return -1;
}


/* ------------------------------------------------------------ */
/* ----- GetExperimentIndex ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for index of an experiment
 * @param experimentName experiment name
 * @return index of experiment if found, -1 otherwise
 */
int squirrel::GetExperimentIndex(QString experimentName) {

    /* find experiment by name */
    for (int i=0; i < experimentList.size(); i++) {
        if (experimentList[i].experimentName == experimentName) {
            return i;
        }
    }

    return -1;
}


/* ------------------------------------------------------------ */
/* ----- GetSubject ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of a subject
 * @param ID subject ID
 * @param sqrlSubject copy of squirrelSubject object
 * @return true if found, false otherwise
 */
bool squirrel::GetSubject(QString ID, squirrelSubject &sqrlSubject) {

    /* find subject by ID */
    for (int i=0; i < subjectList.size(); i++) {
        if (subjectList[i].ID == ID) {
            sqrlSubject = subjectList[i];
            return true;
        }
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetStudy --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of a study
 * @param ID subjectID
 * @param studyNum studyNumber
 * @param sqrlStudy copy of squirrelStudy object
 * @return true if found, false otherwise
 */
bool squirrel::GetStudy(QString ID, int studyNum, squirrelStudy &sqrlStudy) {

    squirrelSubject sqrlSubject;
    bool subjectFound = false;

    /* first, find subject by ID */
    for (int i=0; i < subjectList.size(); i++) {
        if (subjectList[i].ID == ID) {
            sqrlSubject = subjectList[i];
            subjectFound = true;
            break;
        }
    }

    /* next, find study by number */
    if (subjectFound) {
        for (int i=0; i < sqrlSubject.studyList.size(); i++) {
            if (sqrlSubject.studyList[i].number == studyNum) {
                sqrlStudy = sqrlSubject.studyList[i];
                return true;
            }
        }
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of a series
 * @param ID subject ID
 * @param studyNum study number
 * @param seriesNum series number
 * @param sqrlSeries copy of squirrelSeries object
 * @return true if found, false otherwise
 */
bool squirrel::GetSeries(QString ID, int studyNum, int seriesNum, squirrelSeries &sqrlSeries) {
    squirrelSubject sqrlSubject;
    squirrelStudy sqrlStudy;
    bool subjectFound = false;
    bool studyFound = false;

    /* first, find subject by ID */
    for (int i=0; i < subjectList.size(); i++) {
        if (subjectList[i].ID == ID) {
            sqrlSubject = subjectList[i];
            subjectFound = true;
            break;
        }
    }

    /* next, find study by number */
    if (subjectFound) {
        for (int i=0; i < sqrlSubject.studyList.size(); i++) {
            if (sqrlSubject.studyList[i].number == studyNum) {
                sqrlStudy = sqrlSubject.studyList[i];
                studyFound = true;
                break;
            }
        }
    }

    /* then, find series by number */
    if (studyFound) {
        for (int i=0; i < sqrlStudy.seriesList.size(); i++) {
            if (sqrlStudy.seriesList[i].number == seriesNum) {
                sqrlSeries = sqrlStudy.seriesList[i];
                return true;
            }
        }
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetSubjectList --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a copy of the list of subjects
 * @param subjects QList of squirrelSubject objects
 * @return always true, even if empty
 */
bool squirrel::GetSubjectList(QList<squirrelSubject> &subjects) {

    subjects = subjectList;

    return true;
}


/* ------------------------------------------------------------ */
/* ----- GetStudyList ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of a list of studies
 * @param ID subject ID
 * @param studies QList of squirrelStudy objects
 * @return true if found, false otherwise
 */
bool squirrel::GetStudyList(QString ID, QList<squirrelStudy> &studies) {
    squirrelSubject sqrlSubj;

    if (GetSubject(ID, sqrlSubj)) {
        studies = sqrlSubj.studyList;
        return true;
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetSeriesList ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for a get a copy of a list of series
 * @param ID subject ID
 * @param studyNum study number
 * @param series QList of squirrelSeries objects
 * @return true if found, false otherwise
 */
bool squirrel::GetSeriesList(QString ID, int studyNum, QList<squirrelSeries> &series) {
    squirrelStudy sqrlStudy;
    if (GetStudy(ID, studyNum, sqrlStudy)) {
        series = sqrlStudy.seriesList;
        return true;
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetDrugList ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Search for an get a copy of a list of drugs
 * @param ID subject ID
 * @param drugs QList of squirrelDrug objects
 * @return true if found, false otherwise
 */
bool squirrel::GetDrugList(QString ID, QList<squirrelDrug> &drugs) {
    squirrelSubject sqrlSubj;

    if (GetSubject(ID, sqrlSubj)) {
        drugs = sqrlSubj.drugList;
        return true;
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetMeasureList --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of a list of measure objects
 * @param ID subject ID
 * @param measures QList of squirrelMeasure objects
 * @return true if found, false otherwise
 */
bool squirrel::GetMeasureList(QString ID, QList<squirrelMeasure> &measures) {
    squirrelSubject sqrlSubj;

    if (GetSubject(ID, sqrlSubj)) {
        measures = sqrlSubj.measureList;
        return true;
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetAnalysis ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of an analysis
 * @param ID subject ID
 * @param studyNum study number
 * @param pipelineName pipeline name
 * @param sqrlAnalysis copy of a squirrelAnalysis object
 * @return true if found, false otherwise
 */
bool squirrel::GetAnalysis(QString ID, int studyNum, QString pipelineName, squirrelAnalysis &sqrlAnalysis) {
    squirrelStudy sqrlStudy;
    if (GetStudy(ID, studyNum, sqrlStudy)) {

        /* find analysis by pipelineName */
        for (int i=0; i < sqrlStudy.analysisList.size(); i++) {
            if (sqrlStudy.analysisList[i].pipelineName == pipelineName) {
                sqrlAnalysis = sqrlStudy.analysisList[i];
                return true;
            }
        }
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetPipeline ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Search for and get a copy of a pipeline
 * @param pipelineName pipeline name
 * @param sqrlPipeline copy of a squirrelPipeline object
 * @return true if found, false otherwise
 */
bool squirrel::GetPipeline(QString pipelineName, squirrelPipeline &sqrlPipeline) {

    /* find pipeline by name */
    for (int i=0; i < pipelineList.size(); i++) {
        if (pipelineList[i].pipelineName == pipelineName) {
            sqrlPipeline = pipelineList[i];
            return true;
        }
    }

    return false;
}


/* ------------------------------------------------------------ */
/* ----- GetExperiment ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Search for a get a copy of an experiment
 * @param experimentName experiment name
 * @param sqrlExperiment copy of a squirrelExperiment object
 * @return true if found, false otherwise
 */
bool squirrel::GetExperiment(QString experimentName, squirrelExperiment &sqrlExperiment) {

    /* find experiment by name */
    for (int i=0; i < experimentList.size(); i++) {
        if (experimentList[i].experimentName == experimentName) {
            sqrlExperiment = experimentList[i];
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
 */
void squirrel::Log(QString s, QString func) {
    if (s.trimmed() != "") {
        log.append(QString("%1() %2\n").arg(func).arg(s));
        Print(QString("%1() %2").arg(func).arg(s));
    }
}


/* ------------------------------------------------------------ */
/* ----- AddSeriesFiles --------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add files to a series. This function also copies the files to the staging/temp directory
 * @param ID subject ID
 * @param studyNum study number
 * @param seriesNum series number
 * @param files list of files to be added
 * @param destDir sub-directory within the series staging directory
 * @return true if successful, false otherwise
 */
bool squirrel::AddSeriesFiles(QString ID, int studyNum, int seriesNum, QStringList files, QString destDir) {

    /* make sure the subject info is not blank */
    if (ID == "") return false;
    if (studyNum < 1) return false;
    if (seriesNum < 1) return false;

    /* create the experiment path on disk and set the experiment path in  */
    QString dir = QString("%1/data/%2/%3/%4").arg(workingDir).arg(ID).arg(studyNum).arg(seriesNum);
    QString m;
    MakePath(dir, m);
    foreach (QString f, files) {
        /* copy this to the packageRoot/destDir directory */
        Log(QString("Copying file [%1] to [%2]").arg(f).arg(dir), __FUNCTION__);
        CopyFile(f, dir);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- AddAnalysisFiles ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add files to an analysis. This function also copies the files to the staging/temp directory
 * @param ID subject ID
 * @param studyNum study number
 * @param pipelineName pipeline name
 * @param files list of files to be added
 * @param destDir sub-directory within the analysis staging directory
 * @return true if successful, false otherwise
 */
bool squirrel::AddAnalysisFiles(QString ID, int studyNum, QString pipelineName, QStringList files, QString destDir) {

    /* make sure the subject info is not blank */
    if (ID == "") return false;
    if (studyNum < 1) return false;
    if (pipelineName == "") return false;

    /* create the experiment path on disk and set the experiment path in  */
    QString dir = QString("%1/data/%2/%3/%4").arg(workingDir).arg(ID).arg(studyNum).arg(pipelineName);
    QString m;
    MakePath(dir, m);
    foreach (QString f, files) {
        /* copy this to the packageRoot/destDir directory */
        CopyFile(f, dir);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- AddPipelineFiles ------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add files to a pipeline. This function also copies the files to the staging/temp directory
 * @param pipelineName pipeline name
 * @param files list of files to be added
 * @param destDir sub-directory within the analysis staging directory
 * @return true if successful, false otherwise
 */
bool squirrel::AddPipelineFiles(QString pipelineName, QStringList files, QString destDir) {

    /* make sure the experiment name is not blank */
    if (pipelineName == "") {
        return false;
    }
    /* create the experiment path on disk and set the experiment path in  */
    QString dir = QString("%1/experiments/%2/%3").arg(workingDir).arg(pipelineName).arg(destDir);
    QString m;
    MakePath(dir, m);
    foreach (QString f, files) {
        /* copy this to the packageRoot/destDir directory */
        CopyFile(f, dir);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- AddExperimentFiles ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add files to an experiment. This function also copies the files to the staging/temp directory
 * @param experimentName experiment name
 * @param files list of files to be added
 * @param destDir sub-directory within the analysis staging directory
 * @return true if successful, false otherwise
 */
bool squirrel::AddExperimentFiles(QString experimentName, QStringList files, QString destDir) {

    /* make sure the experiment name is not blank */
    if (experimentName == "") {
        return false;
    }
    /* create the experiment path on disk and set the experiment path in  */
    QString dir = QString("%1/experiments/%2/%3").arg(workingDir).arg(experimentName).arg(destDir);
    QString m;
    MakePath(dir, m);
    foreach (QString f, files) {
        /* copy this to the packageRoot/destDir directory */
        CopyFile(f, dir);
    }

    return true;
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
