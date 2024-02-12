/* ------------------------------------------------------------------------------
  Squirrel main.cpp
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

#include <QCoreApplication>
#include <QCommandLineParser>
#include <iostream>
#include "squirrelVersion.h"
#include "validate.h"
#include "dicom.h"
#include "bids.h"
#include "squirrel.h"

int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    /* this whole section reads the command line parameters */
    a.setApplicationVersion(QString("Build %1.%2.%3  (squirrellib %4.%5)").arg(UTIL_VERSION_MAJ).arg(UTIL_VERSION_MIN).arg(UTIL_BUILD_NUM).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN));
    a.setApplicationName("Squirrel Utilities");

    /* setup the command line parser */
    QCommandLineParser p;
    p.setApplicationDescription("Squirrel data format tools");
    p.setSingleDashWordOptionMode(QCommandLineParser::ParseAsCompactedShortOptions);
    p.setOptionsAfterPositionalArgumentsMode(QCommandLineParser::ParseAsOptions);
    p.addHelpOption();
    p.addVersionOption();
    p.addPositionalArgument("tool", "Available tools:  validate  dicom2squirrel  bids2squirrel  squirrel2bids  modify  list");

    /* command line flag options */
    QCommandLineOption optDebug(QStringList() << "d" << "debug", "Enable debugging");
    QCommandLineOption optQuiet(QStringList() << "q" << "quiet", "Dont print headers and checks");
    p.addOption(optDebug);
    p.addOption(optQuiet);

    /* command line options that take values */
    QCommandLineOption optInputFile(QStringList() << "i" << "in", "Input file/directory", "input file/dir");
    QCommandLineOption optOutputFile(QStringList() << "o" << "out", "Output file/directory", "output file/dir");
    QCommandLineOption optOutputDataFormat(QStringList() << "output-data-format", "Output data format if converted from DICOM:\n  anon - Anonymized DICOM\n  nifti4d - Nifti 4D\n  nifti4dgz - Nifti 4D gz (default)\n  nifti3d - Nifti 3D\n  nifti3dgz - Nifti 3D gz", "dataformat");
    QCommandLineOption optOutputDirFormat(QStringList() << "output-dir-format", "Output directory structure\n  seq - Sequentially numbered\n  orig - Original ID (default)", "dirformat");
    QCommandLineOption optOutputPackageFormat(QStringList() << "output-package-format", "Output package format\n  dir - Directory\n  zip - .zip file (default)", "packageformat");
    //QCommandLineOption optRenumberIDs(QStringList() << "renumber-ids", "Renumber IDs in zero-padded format #####. Existing IDs are moved to subject alt-IDs field");
    //QCommandLineOption optDicomDir(QStringList() << "add-dicom-dir", "Modify an existing squirrel package by adding in this DICOM directory. IDs will be automatically renumbered.", "dicomdir");
    QCommandLineOption optAddObject(QStringList() << "add", "Add an object [dicomdir  subject  study  series  experiment  analysis  pipeline].", "object");
    QCommandLineOption optRemoveObject(QStringList() << "remove", "Remove an object [subject  study  series  experiment  analysis  pipeline].", "object");
    QCommandLineOption optListObject(QStringList() << "list", "List an object [package  subjects  studies  series  experiments  pipelines  groupanalyses  datadictionary].", "object");
    QCommandLineOption optSubjectID(QStringList() << "subject-id", "Subject ID.", "subjectid");
    QCommandLineOption optStudyNum(QStringList() << "study-num", "Study Number.", "studynum");
    QCommandLineOption optSeriesNum(QStringList() << "series-num", "Series Number.", "seriesnum");

    QCommandLineOption optListDetails(QStringList() << "list-details", "Include details when printing lists.");
    p.addOption(optOutputFile);
    p.addOption(optInputFile);
    p.addOption(optOutputDataFormat);
    p.addOption(optOutputDirFormat);
    p.addOption(optOutputPackageFormat);
    //p.addOption(optRenumberIDs);
    //p.addOption(optDicomDir);
    p.addOption(optAddObject);
    p.addOption(optRemoveObject);
    p.addOption(optListObject);
    p.addOption(optSubjectID);
    p.addOption(optStudyNum);
    p.addOption(optSeriesNum);
    p.addOption(optListDetails);

    /* Process the actual command line arguments given by the user */
    p.process(a);

    QString tool;
    bool debug, quiet;
    //bool renumberIDs;
    bool listDetails;

    const QStringList args = p.positionalArguments();
    if (args.size() > 0)
        tool = args.at(0).trimmed();

    debug = p.isSet(optDebug);
    quiet = p.isSet(optQuiet);
    QString paramOutputFile = p.value(optOutputFile).trimmed();
    QString paramInput = p.value(optInputFile).trimmed();
    QString paramOutputDataFormat = p.value(optOutputDataFormat).trimmed();
    QString paramOutputDirFormat = p.value(optOutputDirFormat).trimmed();
    QString paramOutputPackageFormat = p.value(optOutputPackageFormat).trimmed();
    QString paramListObject = p.value(optListObject);
    QString paramSubjectID = p.value(optSubjectID).trimmed();
    int paramStudyNum = p.value(optStudyNum).trimmed().toInt();
    int paramSeriesNum = p.value(optSeriesNum).trimmed().toInt();
    //renumberIDs = p.isSet(optRenumberIDs);
    listDetails = p.isSet(optListDetails);

    if (quiet)
        debug = false;

    QStringList tools = { "dicom2squirrel", "validate", "bids2squirrel", "squirrel2bids", "modify", "list" };

    /* now check the command line parameters passed in, to see if they are calling a valid module */
    if (!tools.contains(tool)) {
        if (tool != "")
            std::cout << QString("***** Error: unrecognized option [%1] *****\n").arg(tool).toStdString().c_str();

        std::cout << p.helpText().toStdString().c_str();
        return 0;
    }

    QString bindir = QDir::currentPath();

    if (!quiet) {
        utils::Print("+----------------------------------------------------+");
        utils::Print(QString("|  Squirrel utils version %1.%2\n|\n|  Build date [%3 %4]\n|  C++ [%5]\n|  Qt compiled [%6]\n|  Qt runtime [%7]\n|  Build system [%8]" ).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN).arg(__DATE__).arg(__TIME__).arg(__cplusplus).arg(QT_VERSION_STR).arg(qVersion()).arg(QSysInfo::buildAbi()));
        utils::Print(QString("|\n|  Current working directory is %1").arg(bindir));
        utils::Print("+----------------------------------------------------+\n");
    }

    /* ---------- Run the validate tool ---------- */
    if (tool == "validate") {
        if (paramInput.trimmed() == "") {
            utils::Print("*** Input file blank ***");
        }
        else if (!QFile::exists(paramInput)) {
            utils::Print(QString("*** Input file [%1] does not exist ***").arg(paramInput));
        }
        else {
            /* create squirrel object and validate */
            squirrel *sqrl = new squirrel(debug);
            if (sqrl->Read(paramInput, true, true)) {
                sqrl->Log("Valid squirrel file", __FUNCTION__);
            }
            else {
                sqrl->Log("*** Invalid squirrel file ***", __FUNCTION__);
            }
        }
    }
    /* ---------- Run the dicom2squirrel tool ---------- */
    else if (tool == "dicom2squirrel") {

        /* check if the outfile's parent directory exists */
        QFileInfo outinfo(paramOutputFile);
        QDir outdir = outinfo.absolutePath();
        if (!outdir.exists()) {
            utils::Print(QString("Output directory [%1] does not exist").arg(outdir.absolutePath()));
        }
        else {
            dicom *dcm = new dicom();
            squirrel *sqrl = new squirrel(debug, quiet);

            if (paramOutputDataFormat != "")
                sqrl->dataFormat = paramOutputDataFormat;

            if (paramOutputDirFormat != "") {
                sqrl->subjectDirFormat = paramOutputDirFormat;
                sqrl->studyDirFormat = paramOutputDirFormat;
                sqrl->seriesDirFormat = paramOutputDirFormat;
            }

            if (paramOutputPackageFormat != "")
                sqrl->format = paramOutputPackageFormat;

            /* 1) load the DICOM data to a squirrel object */
            dcm->LoadToSquirrel(paramInput, bindir, sqrl);

            /* 2) write the squirrel file */
            QString filepath;
            sqrl->SetFilename(filepath);
            sqrl->Write();

            delete dcm;
            delete sqrl;
        }

    }
    /* ---------- Run the bids2squirrel tool ---------- */
    else if (tool == "bids2squirrel") {

        utils::Print(QString("Running bids2squirrel on input directory [%1]").arg(paramInput));
        utils::Print(QString("Output file [%1]").arg(paramOutputFile));

        /* check if the infile directory exists */
        QDir indir(paramInput);
        if (!indir.exists()) {
            utils::Print(QString("Input directory [%1] does not exist").arg(indir.absolutePath()));
        }
        else if (paramInput == "") {
            utils::Print("Input directory not specified. Use the -i <indir> option to specify the input directory");
        }
        else {
            QString outputfile = paramOutputFile;

            if (paramOutputFile == "") {
                outputfile = QString(paramInput + "/squirrel.zip");
                utils::Print(QString("Output file not specified. Creating squirrel file in input directory [%1]").arg(outputfile));
            }

            /* create a squirrel object */
            squirrel *sqrl = new squirrel(debug);

            /* create a BIDS object, and start reading the directory */
            bids *bds = new bids();

            bds->LoadToSquirrel(indir.path(), sqrl);

            /* display progress or messages */

            /* save the squirrel object */
            QString outpath;
            sqrl->filePath = outputfile;
            sqrl->SetFilename(outputfile);
            sqrl->Write();
        }
    }
    /* ---------- Run the squirrel2bids tool ---------- */
    else if (tool == "squirrel2bids") {

    }
    /* ---------- Run the list tool ---------- */
    else if (tool == "list") {

        //Print("--subject-id " + paramSubjectID);
        //Print("--study-num " + paramStudyNum);
        //if (quiet)
        //    Print("-q option is set");
        //else
        //    Print("-q option is not set");

        /* check if the infile exists */
        QFile infile(paramInput);
        if (!infile.exists()) {
            utils::Print(QString("Input file [%1] does not exist").arg(paramInput));
        }
        else {
            squirrel *sqrl = new squirrel(debug, quiet);
            sqrl->quiet = quiet;
            sqrl->Read(paramInput, true);

            //possible objects: subjects  studies  series  experiments  pipelines  groupanalyses  datadictionary

            if (paramListObject == "package") {
                sqrl->PrintPackage();
            }
            else if (paramListObject == "subjects") {
                sqrl->PrintSubjects(listDetails);
            }
            else if (paramListObject == "studies") {
                int studyRowID = sqrl->FindStudy(paramSubjectID, paramStudyNum);
                sqrl->PrintStudies(studyRowID, listDetails);
            }
            else if (paramListObject == "series") {
                int seriesRowID = sqrl->FindSeries(paramSubjectID, paramStudyNum, paramSeriesNum);
                sqrl->PrintSeries(seriesRowID, listDetails);
            }
            else if (paramListObject == "experiments") {
                sqrl->PrintExperiments(listDetails);
            }
            else if (paramListObject == "pipelines") {
                sqrl->PrintPipelines(listDetails);
            }
            else if (paramListObject == "groupanalyses") {
                sqrl->PrintGroupAnalyses(listDetails);
            }
            else if (paramListObject == "datadictionary") {
                sqrl->PrintDataDictionary(listDetails);
            }

            delete sqrl;
        }
    }

    if (!quiet)
        utils::Print("\nExiting squirrel utils");

    a.exit();
    return 0;
}
