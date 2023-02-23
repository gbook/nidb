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
}


/* ------------------------------------------------------------ */
/* ----- read ------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::read Reads a squirrel package
 * @param filename Full filepath of the package to read
 * @return true if package was successfully read, false otherwise
 */
bool squirrel::read(QString filepath, QString &m, bool validateOnly) {

    QStringList msgs;

    if (validateOnly)
        msgs << QString("Validating " + filepath);
    else
        msgs << QString("Reading " + filepath);

    /* check if file exists */
    if (!FileExists(filepath)) {
        msgs << QString("File " + filepath + " does not exist");
        PrependQStringList(msgs, "read() ");
        m = msgs.join("\n");
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
    msgs << output;
    if (!output.contains("squirrel.json")) {
        msgs << QString("File " + filepath + " does not appear to be a squirrel package");
        PrependQStringList(msgs, "read() ");
        m = msgs.join("\n");
        return false;
    }

    /* create a working directory */
    MakeTempDir(workingDir);
    msgs << QString("Created temp directory [" + workingDir + "]");

    /* unzip the .zip to the working dir */
    #ifdef Q_OS_WINDOWS
        systemstring = QString("\"C:/Program Files/7-Zip/7z.exe\" x \"" + filepath + "\" -o\"" + workingDir + "\" -y");
    #else
        systemstring = QString("unzip " + filepath + " -d " + workingDir);
    #endif
    output = SystemCommand(systemstring, true);
    msgs << output;

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
    QJsonValue pkgVal = root.value("_package");
    QJsonObject pkgObj = pkgVal.toObject();
    description = pkgObj["description"].toString();
    datetime.fromString(pkgObj["datetime"].toString());
    name = pkgObj["name"].toString();
    qDebug() << pkgObj;

    /* get the data object, and check for any subjects */
    QJsonValue dataVal = root.value("data");
    QJsonObject dataObj = dataVal.toObject();
    QJsonArray subjects = dataObj["subjects"].toArray();
    qDebug() << subjects;

    /* in case of string value get value and convert into string*/
    //qWarning() << tr("QJsonObject[appName] of description: ") << item["description"];
    //QJsonValue subobj = item["description"];
    //qWarning() << subobj.toString();

    /* in case of array get array and convert into string*/
    //qWarning() << tr("QJsonObject[appName] of value: ") << item["imp"];
    //QJsonArray test = item["imp"].toArray();
    //qWarning() << test[1].toString();


    /* delete the tmp dir, if it exists */
    if (validateOnly) {
        if (DirectoryExists(workingDir)) {
            Print("Temporary export dir [" + workingDir + "] exists and will be deleted");
            QString m;
            if (!RemoveDir(workingDir, m))
                Print("Error [" + m + "] removing directory [" + workingDir + "]");
        }
    }

    PrependQStringList(msgs, "read() ");
    m = msgs.join("\n");

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
bool squirrel::write(QString outpath, QString &filepath, QString &m, bool debug) {

    /* create the log file */
    QFileInfo finfo(outpath);
    logfile = QString(finfo.absolutePath() + "/squirrel-" + CreateLogDate() + ".log");

    msgs << Log("Beginning writing of squirrel package", __FUNCTION__);

    /* create temp directory */
    MakeTempDir(workingDir);
    msgs << Log(QString("Created working directory [" + workingDir + "]"), __FUNCTION__);

    /* ----- 1) write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

        squirrelSubject sub = subjectList[i];

        QString subjDir;
        if (subjectDirFormat == "orig")
            subjDir = sub.ID;
        else
            subjDir = QString("%1").arg(i+1); /* start the numbering at 1 instead of 0 */

        subjDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
        QString vPath = QString("data/%1").arg(subjDir);
        subjectList[i].virtualPath = vPath;

        msgs << Log(QString("Working on subject [" + subjDir + "]"), __FUNCTION__);

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

            msgs << Log(QString("Working on study [" + studyDir + "]"), __FUNCTION__);

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

                msgs << Log(QString("Working on series [" + seriesDir + "]"), __FUNCTION__);
                msgs << Log(QString("Package data format [" + dataFormat + "]"), __FUNCTION__);

                /* orig vs other formats */
                if (dataFormat == "orig") {
                    msgs << Log(QString("Squirrel: dataformat original [%1]").arg(dataFormat), __FUNCTION__);

                    /* copy all of the series files to the temp directory */
                    foreach (QString f, ser.stagedFiles) {
                        QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                        SystemCommand(systemstring);
                    }
                }
                else if ((dataFormat == "anon") || (dataFormat == "anonfull")) {
                    msgs << Log(QString("Squirrel: dataformat anonymize [%1]").arg(dataFormat), __FUNCTION__);
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
                    msgs << Log(QString("dataformat nifti [%1]").arg(dataFormat), __FUNCTION__);
                    int numConv(0), numRename(0);
                    //QString format = dataFormat.left(5);
                    bool gzip;
                    if (dataFormat.contains("gz"))
                        gzip = true;
                    else
                        gzip = false;

                    /* get path of first file to be converted */
                    if (ser.stagedFiles.size() > 0) {
                        msgs << Log(QString("Converting [%1] files to nifti").arg(ser.stagedFiles.size()), __FUNCTION__);

                        QFileInfo f(ser.stagedFiles[0]);
                        QString origSeriesPath = f.absoluteDir().absolutePath();
                        squirrelImageIO io;
                        QString m3;
                        io.ConvertDicom(dataFormat, origSeriesPath, seriesPath, QDir::currentPath(), gzip, subjDir, studyDir, seriesDir, "dicom", numConv, numRename, m3);
                        msgs << Log(QString("ConvertDicom() returned [%1]").arg(m3), __FUNCTION__);
                    }
                    else {
                        msgs << Log(QString("Variable squirrelSeries.stagedFiles is empty. No files to convert to Nifti"), __FUNCTION__);
                    }
                }
                else
                    msgs << Log(QString("dataFormat not found [%1]").arg(dataFormat), __FUNCTION__);

                /* get the number of files and size of the series */
                qint64 c(0), b(0);
                msgs << Log(QString("Running GetDirSizeAndFileCount() on [%1]").arg(seriesPath), __FUNCTION__);
                GetDirSizeAndFileCount(seriesPath, c, b, false);
                msgs << Log(QString("GetDirSizeAndFileCount() found  [%1] files   [%2] bytes").arg(c).arg(b), __FUNCTION__);
                subjectList[i].studyList[j].seriesList[k].numFiles = c;
                subjectList[i].studyList[j].seriesList[k].size = b;

                /* write the series .json file, containing the dicom header params */
                QJsonObject params;
                params = ser.ParamsToJSON();
                QByteArray j = QJsonDocument(params).toJson();
                QFile fout(QString("%1/params.json").arg(seriesPath));
                if (fout.open(QIODevice::WriteOnly)) {
                    fout.write(j);
                    msgs << Log(QString("Wrote %1/params.json").arg(seriesPath), __FUNCTION__);
                }
                else {
                    msgs << Log(QString("Error writing [%1]").arg(fout.fileName()), __FUNCTION__);
                    msgs << Log(QString("Error writing %1/params.json").arg(seriesPath), __FUNCTION__);
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

    root["_package"] = pkgInfo;

    QJsonArray JSONsubjects;

    /* add subjects */
    for (int i=0; i < subjectList.size(); i++) {
        JSONsubjects.append(subjectList[i].ToJSON());
    }
    root["numSubjects"] = JSONsubjects.size();
    root["subjects"] = JSONsubjects;

    /* add pipelines */
    msgs << Log(QString("Adding [%1] pipelines to JSON file").arg(pipelineList.size()), __FUNCTION__);
    if (pipelineList.size() > 0) {
        msgs << Log(QString("Adding pipelines to JSON file"), __FUNCTION__);
        QJsonArray JSONpipelines;
        for (int i=0; i < pipelineList.size(); i++) {
            JSONpipelines.append(pipelineList[i].ToJSON(workingDir));
        }
        root["numPipelines"] = JSONpipelines.size();
        root["pipelines"] = JSONpipelines;
    }

    /* add experiments */
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

    msgs << Log(QString("Wrote %1/squirrel.json").arg(workingDir), __FUNCTION__);

    /* zip the temp directory into the output file */
    QString zipfile = outpath;
    if (!zipfile.endsWith(".zip"))
        zipfile += ".zip";

    QString systemstring = "cd " + workingDir + "; zip -1rv " + zipfile + " .";
    msgs << Log("Beginning zipping package...", __FUNCTION__);
    if (debug) {
        msgs << Log(SystemCommand(systemstring), __FUNCTION__);
    }
    else {
        SystemCommand(systemstring, false);
    }

    msgs << Log("Finished zipping package...", __FUNCTION__);

    if (FileExists(zipfile)) {
        msgs << Log("Created .zip file [" + zipfile + "]", __FUNCTION__);
        filepath = zipfile;

        /* delete the tmp dir, if it exists */
        if (DirectoryExists(workingDir)) {
            msgs << Log("Temporary export dir [" + workingDir + "] exists and will be deleted", __FUNCTION__);
            QString m;
            if (!RemoveDir(workingDir, m))
                msgs << Log("Error [" + m + "] removing directory [" + workingDir + "]", __FUNCTION__);
        }
    }
    else {
        msgs << Log("Error creating zip file [" + zipfile + "]", __FUNCTION__);
    }

    m = msgs.join("\n");
    return true;
}


/* ------------------------------------------------------------ */
/* ----- validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::validate
 * @return true if valid squirrel file, false otherwise
 */
bool squirrel::validate() {

    return true;
}


/* ------------------------------------------------------------ */
/* ----- print ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief print
 */
void squirrel::print() {

    /* print package info */
    PrintPackage();

    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

        squirrelSubject sub = subjectList[i];
        sub.PrintSubject();

        /* iterate through studies */
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
}


/* ------------------------------------------------------------ */
/* ----- GetUnzipSize ----------------------------------------- */
/* ------------------------------------------------------------ */
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
 * @brief squirrel::addSubject
 * @param subj
 * @return true if added, false if not added
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
 * @brief squirrel::addPipeline
 * @param pipe
 * @return true if added, false if not added
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
 * @brief squirrel::addExperiment
 * @param exp
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
 * @brief squirrel::removeSubject
 * @param ID
 * @return true if subject found and removed, false is subject not found
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
 * @brief squirrel::PrintPackage
 */
void squirrel::PrintPackage() {
    Print("-- SQUIRREL PACKAGE ----------");
    Print(QString("   Date: %1").arg(datetime.toString()));
    Print(QString("   Description: %1").arg(description));
    Print(QString("   Name: %1").arg(name));
    Print(QString("   Version: %1").arg(version));
    Print(QString("   subjectDirFormat: %1").arg(subjectDirFormat));
    Print(QString("   studyDirFormat: %1").arg(studyDirFormat));
    Print(QString("   seriesDirFormat: %1").arg(seriesDirFormat));
    Print(QString("   dataFormat: %1").arg(dataFormat));
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
/* ----- Log -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::Log
 * @return the log message for continued use
 */
QString squirrel::Log(QString m, QString f) {
    m = QString("\tsquirrel.%1() %2").arg(f).arg(m);
    Print(m);
    AppendCustomLog(logfile, m);
    return m;
}


/* ------------------------------------------------------------ */
/* ----- GetSubject ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::GetSubject - Finds a subject object by subjectID
 * @param ID - subjectID
 * @param sqrlSubject
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
 * @brief squirrel::GetStudy - Finds a study by subjectID and studyNumber
 * @param ID - subjectID
 * @param studyNum - studyNumber
 * @param sqrlStudy - squirrelStudy object if found
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
bool squirrel::GetSubjectList(QList<squirrelSubject> &subjects) {

    subjects = subjectList;

    return true;
}


/* ------------------------------------------------------------ */
/* ----- GetStudyList ----------------------------------------- */
/* ------------------------------------------------------------ */
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
QString squirrel::GetTempDir() {
    return workingDir;
}
