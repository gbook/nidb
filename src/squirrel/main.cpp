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
#include "dicom.h"
#include "bids.h"
#include "modify.h"
#include "squirrel.h"

void CommandLineError(QCommandLineParser &p, QString m) {
    std::cout << p.helpText().toStdString().c_str();
    std::cout << "\n**** ERROR " << m.toStdString().c_str() << " ****\n";
}

int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    QString bindir = QDir::currentPath();

    /* this whole section reads the command line parameters */
    a.setApplicationVersion(QString("Build %1.%2.%3  (squirrellib %4.%5)").arg(UTIL_VERSION_MAJ).arg(UTIL_VERSION_MIN).arg(UTIL_BUILD_NUM).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN));
    a.setApplicationName("Squirrel Utilities");

    /* setup the command line parser */
    QCommandLineParser p;
    p.setApplicationDescription("Tools to manage squirrel data packages");
    p.setSingleDashWordOptionMode(QCommandLineParser::ParseAsCompactedShortOptions);
    p.setOptionsAfterPositionalArgumentsMode(QCommandLineParser::ParseAsOptions);
    p.addHelpOption();
    p.addVersionOption();

    /* setup and obtain the tool we're supposed to run */
    p.addPositionalArgument("tool", "Available tools:\n   bids2squirrel\n   dicom2squirrel\n   list\n   modify\n   validate");
    p.parse(QCoreApplication::arguments());
    const QStringList args = p.positionalArguments();
    const QString command = args.isEmpty() ? QString() : args.first();

    /* check which tool to run */
    if (command == "dicom2squirrel") {
        p.clearPositionalArguments();
        p.addPositionalArgument("dicom2squirrel", "Convert DICOM directory to squirrel.", "dicom2squirrel [options]");

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Dont print headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "i" << "input", "Input path", "dir"));
        p.addOption(QCommandLineOption(QStringList() << "o" << "outout", "Output path", "zipfilename"));
        p.addOption(QCommandLineOption(QStringList() << "data-format", "Output data format if converted from DICOM:\n  anon - Anonymized DICOM\n  nifti4d - Nifti 4D\n  nifti4dgz - Nifti 4D gz (default)\n  nifti3d - Nifti 3D\n  nifti3dgz - Nifti 3D gz", "format"));
        p.addOption(QCommandLineOption(QStringList() << "dir-format", "Output directory structure\n  seq - Sequentially numbered\n  orig - Original ID (default)", "format"));
        //p.addOption(QCommandLineOption(QStringList() << "output-package-format", "Output package format\n  dir - Directory\n  zip - .zip file (default)", "packageformat"));

        p.process(a);

        bool debug = p.isSet("d");
        bool quiet = p.isSet("q");
        QString paramOutputFile = p.value("o").trimmed();
        QString paramInput = p.value("i").trimmed();
        QString paramOutputDataFormat = p.value("output-data-format").trimmed();
        QString paramOutputDirFormat = p.value("output-dir-format").trimmed();
        QString paramOutputPackageFormat = p.value("output-package-format").trimmed();

        if (paramInput == "") {
            std::cout << p.helpText().toStdString().c_str();
            std::cout << "\n**** ERROR - Missing input path. Use -i to specify an input directory. ****\n";
            return 0;
        }
        if (paramOutputFile == "") {
            std::cout << p.helpText().toStdString().c_str();
            std::cout << "\n**** ERROR - Missing output path. Use -o to specify an output path. ****\n";
            return 0;
        }

        /* finished with checks, now run the tool */
        if (!quiet)
            utils::PrintHeader();

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
            sqrl->Write(true);

            delete dcm;
            delete sqrl;
        }

    }
    else if (command == "bids2squirrel") {
        p.clearPositionalArguments();
        p.addPositionalArgument("dicom2squirrel", "Convert DICOM directory to squirrel.", "dicom2squirrel [options]");

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Dont print headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "i" << "input", "Input path", "dir"));
        p.addOption(QCommandLineOption(QStringList() << "o" << "outout", "Output path", "zipfilename"));

        p.process(a);

        bool debug = p.isSet("d");
        bool quiet = p.isSet("q");
        QString paramOutputFile = p.value("o").trimmed();
        QString paramInput = p.value("i").trimmed();

        if (paramInput == "") {
            std::cout << p.helpText().toStdString().c_str();
            std::cout << "\n**** Missing input parameter. Use -i to specify an input directory. ****\n";
            return 0;
        }
        if (paramOutputFile == "") {
            std::cout << p.helpText().toStdString().c_str();
            std::cout << "\n**** Missing output path. Use -o to specify an output path. ****\n";
            return 0;
        }

        /* everything is ok, so let's run the tool */
        if (!quiet)
            utils::PrintHeader();

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

            /* save the squirrel object */
            //sqrl->filePath = outputfile;
            sqrl->SetFilename(outputfile);
            sqrl->Write(true);
        }
    }
    else if (command == "list") {
        p.clearPositionalArguments();
        p.addPositionalArgument("list", "List all instances of an object within a squirrel package.", "list [options]");

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Dont print headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "i" << "input", "Input path", "dir"));
        p.addOption(QCommandLineOption(QStringList() << "object", "List an object [package  subject  study  series  experiment  pipeline  groupanalysis  datadictionary].", "object"));
        p.addOption(QCommandLineOption(QStringList() << "subject-id", "Subject ID.", "subjectid"));
        p.addOption(QCommandLineOption(QStringList() << "study-num", "Study Number\n  -subject-id must also be specified.", "studynum"));
        //p.addOption(QCommandLineOption(QStringList() << "series-num", "Series Number\n  -subject-id and -study-num must also be specified.", "seriesnum"));
        p.addOption(QCommandLineOption(QStringList() << "details", "Include details when printing lists."));
        p.process(a);

        bool debug = p.isSet("d");
        bool quiet = p.isSet("q");
        QString inputPath = p.value("i").trimmed();
        QString object = p.value("object").trimmed();
        QString subjectID = p.value("i").trimmed();
        int studyNum = p.value("i").toInt();
        //QString seriesNum = p.value("i").trimmed();
        bool details = p.isSet("details");

        /* check if the infile exists */
        QFile infile(inputPath);
        if (!infile.exists()) {
            std::cout << p.helpText().toStdString().c_str();
            std::cout << "\n**** ERROR - Missing input file path. Use -i to specify an input file. ****\n";
            return 0;
        }
        else {
            squirrel *sqrl = new squirrel(debug, quiet);
            sqrl->quiet = quiet;
            sqrl->Read(true);

            if (object == "package") {
                sqrl->PrintPackage();
            }
            else if (object == "subject") {
                sqrl->PrintSubjects(details);
            }
            else if (object == "study") {
                int subjectRowID = sqrl->FindSubject(subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject not found. Searched for subject [%1]").arg(subjectID));
                else
                    sqrl->PrintStudies(subjectRowID, details);
            }
            else if (object == "series") {
                int subjectRowID = sqrl->FindSubject(subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject not found. Searched for subject [%1]").arg(subjectID));
                else {
                    int studyRowID = sqrl->FindStudy(subjectID, studyNum);
                    if (studyRowID < 0)
                        utils::Print(QString("Study not found. Searched for subject [%1] study [%2]").arg(subjectID).arg(studyNum));
                    else
                        sqrl->PrintSeries(studyRowID, details);
                }
            }
            else if (object == "experiment") {
                sqrl->PrintExperiments(details);
            }
            else if (object == "pipeline") {
                sqrl->PrintPipelines(details);
            }
            else if (object == "groupanalysis") {
                sqrl->PrintGroupAnalyses(details);
            }
            else if (object == "datadictionary") {
                sqrl->PrintDataDictionary(details);
            }

            delete sqrl;
        }
    }
    else if (command == "modify") {
        p.clearPositionalArguments();
        p.addPositionalArgument("modify", "Modify squirrel package by adding/removing objects.", "modify");

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Quiet mode. No printing of headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "p" << "package", "Squirrel package path", "path"));
        p.addOption(QCommandLineOption(QStringList() << "add", "Add object to the package.", "object"));
        p.addOption(QCommandLineOption(QStringList() << "remove", "Remove object (and all dependent objects) from the package.", "object"));
        p.addOption(QCommandLineOption(QStringList() << "datapath", "Path to new object data. Can include wildcard: /path/*.dcm", "path"));
        p.addOption(QCommandLineOption(QStringList() << "recursive", "Search the data path recursively"));
        p.addOption(QCommandLineOption(QStringList() << "objectid", "Existing object ID, name, or number to remove.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "subjectid", "Parent subject ID. Used when adding a study, series, measure, drug, or analysis object.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "studynum", "Parent study number. Used when adding a series or analysis object (subjectid is also needed).", "num"));
        p.addOption(QCommandLineOption(QStringList() << "objectdata", "String specifying the new object meta-data.", "string"));

        p.process(a);

        bool debug = p.isSet("d");
        bool quiet = p.isSet("q");
        QString packagePath = p.value("p").trimmed();
        QString addObject = p.value("add").trimmed(); /* possible objects: subject study series measure drug analysis experiment pipeline groupanalysis datadictionary */
        QString removeObject = p.value("remove").trimmed();
        QString dataPath = p.value("datapath").trimmed();
        QString objectData = p.value("objectdata").trimmed();
        QString objectID = p.value("objectid").trimmed();
        QString subjectID = p.value("subjectid").trimmed();
        int studyNum = p.value("studynum").toInt();
        bool recursive = p.isSet("recursive");

        QString m;
        modify mod;
        if (!mod.DoModify(packagePath, addObject, removeObject, dataPath, recursive, objectData, objectID, subjectID, studyNum, m)) {
            CommandLineError(p,m);
        }
    }
    else if (command == "validate") {
        p.clearPositionalArguments();
        p.addPositionalArgument("validate", "Validate a squirrel package.", "validate [options]");

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "i" << "input", "Input path", "dir"));

        p.process(a);

        bool debug = p.isSet("d");
        QString paramInput = p.value("i").trimmed();

        if (paramInput == "") {
            std::cout << p.helpText().toStdString().c_str();
            std::cout << "\n**** ERROR - Missing input parameter. Use -i to specify an input directory. ****\n";
            return 0;
        }

        /* create squirrel object and validate */
        squirrel *sqrl = new squirrel(debug);
        if (sqrl->Read(true)) {
            sqrl->Log("Valid squirrel file", __FUNCTION__);
        }
        else {
            sqrl->Log("*** Invalid squirrel file ***", __FUNCTION__);
        }
        delete sqrl;
    }
    else {
        bool v = p.isSet("v");
        if (v)
            p.showVersion();
        else
            p.showHelp(0);
    }

    a.exit();
    return 0;
}
