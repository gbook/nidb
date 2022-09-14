/* ------------------------------------------------------------------------------
  Squirrel squirrel.cpp
  Copyright (C) 2004 - 2022
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
    description = "Package created by squirrelutils";
    name = "Squirrel package";
    version = QString("%1.%2").arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN);
    format = "squirrel";
}


/* ------------------------------------------------------------ */
/* ----- read ------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::read Reads a squirrel package
 * @param filename Full filepath of the package to read
 * @return true if package was successfully read, false otherwise
 */
bool squirrel::read(QString filepath, bool validateOnly) {

    if (validateOnly)
        Print("Validating " + filepath);
    else
        Print("Reading " + filepath);

    /* check if file exists */
    if (!FileExists(filepath)) {
        Print("File " + filepath + " does not exist");
        return false;
    }

    /* get listing of the zip the file, check if the squirrel.json exists in the root */
    QString systemstring;
    systemstring = "unzip -l " + filepath;
    QString output = SystemCommand(systemstring, false);
    if (!output.contains("squirrel.json")) {
        Print("File " + filepath + " does not appear to be a squirrel package");
        return false;
    }

    /* create a working directory */
    MakeTempDir(workingDir);

    /* unzip the .zip to the working dir */
    systemstring = QString("unzip " + filepath + " -d " + workingDir);
    Print(SystemCommand(systemstring, false));

    /* perform all checks */

    /* delete the tmp dir, if it exists */
    if (validateOnly) {
        if (DirectoryExists(workingDir)) {
            Print("Temporary export dir [" + workingDir + "] exists and will be deleted");
            QString m;
            if (!RemoveDir(workingDir, m))
                Print("Error [" + m + "] removing directory [" + workingDir + "]");
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
 *                   - 'fullanon' - Anonymize DICOM files (full anonymization)
 *                   - 'nifti4d' - Nifti 4D
 *                   - 'nifti4dgz' - Nifti 4D gzip [default]
 *                   - 'nidti3d' - Nifti 3D
 *                   - 'nifti3dgz' - Nifti 3D gzip
 * @param subjectDirFormat directory structure of the subject data
 *                  - 'orig' - Use the subjectID for subject directories
 *                  - 'seq' - Use sequentially generated numbers for subject directories
 * @param studyDirFormat directory structure of the subject data
 *                  - 'orig' - Use the studyNum for study directories
 *                  - 'seq' - Use sequentially generated numbers for study directories
 * @param seriesDirFormat directory structure of the subject data
 *                  - 'orig' - Use the seriesNum for series directories
 *                  - 'seq' - Use sequentially generated numbers for series directories
 * @return true if package was successfully written, false otherwise
 */
bool squirrel::write(QString outpath, QString dataFormat, QString subjectDirFormat, QString studyDirFormat, QString seriesDirFormat, bool debug) {

    if (subjectDirFormat == "")
        subjectDirFormat = "orig";
    if (studyDirFormat == "")
        studyDirFormat = "orig";
    if (seriesDirFormat == "")
        seriesDirFormat = "orig";
    if (dataFormat == "")
        dataFormat = "nifti4dgz";

    /* create temp directory */
    MakeTempDir(workingDir);

    /* ----- 1) write data. And set the relative paths in the objects ----- */
    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

        squirrelSubject sub = subjectList[i];

        QString subjDir;
        if (dirFormat == "orig")
            subjDir = sub.ID;
        else
            subjDir = QString("%1").arg(i);

        subjDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
        QString vPath = QString("data/%1").arg(subjDir);
        subjectList[i].virtualPath = vPath;

        /* iterate through studies */
        for (int j=0; j < sub.studyList.size(); j++) {

            squirrelStudy stud = sub.studyList[j];

            QString studyDir;
            if (dirFormat == "orig")
                studyDir = QString("%1").arg(stud.number);
            else
                studyDir = QString("%1").arg(j);

            studyDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
            QString vPath = QString("data/%1/%2").arg(subjDir).arg(studyDir);
            subjectList[i].studyList[j].virtualPath = vPath;

            /* iterate through series */
            for (int k=0; k < stud.seriesList.size(); k++) {

                squirrelSeries ser = stud.seriesList[k];

                QString seriesDir;
                if (dirFormat == "orig")
                    seriesDir = QString("%1").arg(ser.number);
                else
                    seriesDir = QString("%1").arg(k);

                seriesDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
                QString vPath = QString("data/%1/%2/%3").arg(subjDir).arg(studyDir).arg(seriesDir);
                subjectList[i].studyList[j].seriesList[k].virtualPath = vPath;

                QString m;
                QString seriesPath = QString("%1/%2").arg(workingDir).arg(subjectList[i].studyList[j].seriesList[k].virtualPath);
                MakePath(seriesPath,m);

                /* orig vs other formats */
                if (dataFormat == "orig") {
                    /* copy all of the series files to the temp directory */
                    foreach (QString f, ser.stagedFiles) {
                        QString systemstring = QString("cp -uv %1 %2").arg(f).arg(seriesPath);
                        SystemCommand(systemstring);
                    }
                }
                else if ((dataFormat == "anon") || (dataFormat == "anonfull")) {
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
                    int numConv(0), numRename(0);
                    QString format = dataFormat.left(5);
                    bool gzip;
                    if (dataFormat.contains("gz"))
                        gzip = true;
                    else
                        gzip = false;

                    /* get path of first file to be converted */
                    QFileInfo f(ser.stagedFiles[0]);
                    QString origSeriesPath = f.absoluteDir().absolutePath();
                    squirrelImageIO io;
                    io.ConvertDicom(format, origSeriesPath, workingDir, QDir::currentPath(), gzip, "", "", "", "dicom" ,numConv ,numRename ,m);
                }

                /* get the number of files and size of the series */
                qint64 c(0), b(0);
                GetDirSizeAndFileCount(seriesPath, c, b, false);
                stud.seriesList[k].numFiles = c;
                stud.seriesList[k].size = b;

                /* write the series .json file, containing the dicom header params */
                QJsonObject params;
                params = ser.ParamsToJSON();
                QByteArray j = QJsonDocument(params).toJson();
                QFile fout(QString("%1/params.json").arg(seriesPath));
                fout.open(QIODevice::WriteOnly);
                fout.write(j);
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
    if (pipelineList.size() > 0) {
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

    /* zip the temp directory into the output file */
    QString zipfile = outpath;
    if (!zipfile.endsWith(".zip"))
        zipfile += ".zip";

    QString systemstring = "cd " + workingDir + "; zip -1rv " + zipfile + " .";
    Print("Beginning zipping package...");
    if (debug)
        Print(SystemCommand(systemstring));
    else
        SystemCommand(systemstring, false);

    Print("Finished zipping package...");

    if (FileExists(zipfile)) {
        Print("Created .zip file [" + zipfile + "]");

        /* delete the tmp dir, if it exists */
        if (DirectoryExists(workingDir)) {
            Print("Temporary export dir [" + workingDir + "] exists and will be deleted");
            QString m;
            if (!RemoveDir(workingDir, m))
                Print("Error [" + m + "] removing directory [" + workingDir + "]");
        }
    }
    else {
        Print("Error creating zip file [" + zipfile + "]");
    }

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
    Print(QString("   Format: %1").arg(format));

}


/* ------------------------------------------------------------ */
/* ----- MakeTempDir ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::MakeTempDir
 * @return true if created/exists, false otherwise
 */
bool squirrel::MakeTempDir(QString &dir) {
    QString d = QString("/tmp/%1").arg(GenerateRandomString(20));
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
