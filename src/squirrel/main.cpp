/* ------------------------------------------------------------------------------
  Squirrel main.cpp
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

#include <QCoreApplication>
#include <QCommandLineParser>
#include <iostream>
#include "squirrelVersion.h"
#include "dicom.h"
#include "bids.h"
#include "modify.h"
#include "extract.h"
#include "info.h"
#include "squirrel.h"
#include "squirrelTypes.h"

void CommandLineError(QCommandLineParser &p, QString m) {
    std::cout << p.helpText().toStdString().c_str();
    std::cout << "\n----- ERROR ----->> " << m.toStdString().c_str() << "\n\n";
}

void PrintExampleUsage() {
    printf("\nExample usage: \n");
    printf("    squirrel dicom2squirrel <inputDir> <outputPackage> --dataformat nift4d --dirformat orig\n");
    printf("    squirrel bids2squirrel <inputFile> <outputPackage>\n");
    printf("    squirrel modify <package> --operation update --object subject --objectdata 'SubjectID=S1234&DateOfBirth=1999-12-31&Sex=M&Gender=M'\n");
}

void PrintExampleModifyUsage() {
    printf("\nExample modify usage: \n");
    printf("    squirrel modify <package> --operation update --object subject --objectdata 'SubjectID=S1234&DateOfBirth=1999-12-31&Sex=M&Gender=M'\n");
}

void PrintExampleUsageInfo() {
    printf("\nExample info usage: \n");
    printf("    squirrel info <package> --object subject --dataset full --format csv\n");
    printf("    squirrel info <package> --object study --subjectid S1234 \n");
}

void PrintExampleUsageExtract() {
    printf("\nExample extract usage: \n");
    printf("    squirrel extract <package> --object subject --objectid S1234ABC\n");
    printf("    squirrel extract <package> --object series --objectid 1 --subjectid S1234ABC --studynum 1\n");
    printf("    squirrel extract <package> --object experiment --objectid 'MyExperiment'\n");
}

int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    QString bindir = QDir::currentPath();

    /* the entire section below reads the command line parameters */
    a.setApplicationVersion(QString("Build %1.%2.%3  (squirrellib %4.%5)  Build date %6 %7").arg(UTIL_VERSION_MAJ).arg(UTIL_VERSION_MIN).arg(UTIL_BUILD_NUM).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN).arg(__DATE__).arg(__TIME__));
    a.setApplicationName("Squirrel Utilities");

    /* setup the command line parser */
    QCommandLineParser p;
    p.setApplicationDescription("Command line tools to manage squirrel data packages");
    p.setSingleDashWordOptionMode(QCommandLineParser::ParseAsCompactedShortOptions);
    p.setOptionsAfterPositionalArgumentsMode(QCommandLineParser::ParseAsOptions);
    p.addHelpOption();
    p.addVersionOption();

    /* setup and obtain the tool we're supposed to run */
    p.addPositionalArgument("tool", "Available tools:\n   bids2squirrel - Converts BIDS to squirrel\n   dicom2squirrel - Convert DICOM to squirrel\n   info - Display information about a package or its contents\n   modify - Add/remove objects from a package\n   extract - Extract data from a package\n   validate - Check if a package is valid");
    p.parse(QCoreApplication::arguments());
    const QStringList args = p.positionalArguments();
    const QString command = args.isEmpty() ? QString() : args.first();

    /* check which tool to run */
    if (command == "dicom2squirrel") {
        p.clearPositionalArguments();
        p.addPositionalArgument("dicom2squirrel", "Convert DICOM directory to squirrel.", "dicom2squirrel [options]");
        p.addPositionalArgument("dicomdirectory", "The input DICOM directory.", "dicomdirectory");
        p.addPositionalArgument("package", "The output squirrel package.", "package");
        p.parse(QCoreApplication::arguments());
        QStringList args = p.positionalArguments();
        QString inputPath, outputPath;
        if (args.size() > 2) {
            inputPath = args[1];
            outputPath = args[2];
        }

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Dont print headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "dataformat", "Output data format if converted from DICOM:\n  anon - Anonymized DICOM\n  nifti4d - Nifti 4D\n  nifti4dgz - Nifti 4D gz (default)\n  nifti3d - Nifti 3D\n  nifti3dgz - Nifti 3D gz", "format"));
        p.addOption(QCommandLineOption(QStringList() << "dirformat", "Output directory structure\n  seq - Sequentially numbered\n  orig - Original ID (default)", "format"));

        p.process(a);

        bool debug = p.isSet("d");
        bool quiet = p.isSet("q");
        QString paramOutputDataFormat = p.value("dataformat").trimmed();
        QString paramOutputDirFormat = p.value("dirformat").trimmed();

        if (inputPath == "") {
            CommandLineError(p,"Missing input path. Use -i to specify an input directory.");
            PrintExampleUsage();
            return 0;
        }
        if (outputPath == "") {
            CommandLineError(p, "Missing output path. Use -o to specify an output path.");
            PrintExampleUsage();
            return 0;
        }

        /* finished with checks, now run the tool */
        if (!quiet)
            utils::PrintHeader();

        /* check if the outfile's parent directory exists */
        QFileInfo outinfo(outputPath);
        QDir outdir = outinfo.absolutePath();
        if (!outdir.exists()) {
            utils::Print(QString("Output directory [%1] does not exist").arg(outdir.absolutePath()));
        }
        else {
            dicom *dcm = new dicom();
            squirrel *sqrl = new squirrel(debug, quiet);

            if (paramOutputDataFormat != "")
                sqrl->DataFormat = paramOutputDataFormat;

            if (paramOutputDirFormat != "") {
                sqrl->SubjectDirFormat = paramOutputDirFormat;
                sqrl->StudyDirFormat = paramOutputDirFormat;
                sqrl->SeriesDirFormat = paramOutputDirFormat;
            }

            /* 1) load the DICOM data to a squirrel object */
            dcm->LoadToSquirrel(inputPath, bindir, sqrl);

            /* 2) write the squirrel file */
            sqrl->SetPackagePath(outputPath);
            sqrl->SetWriteLog(true);
            sqrl->Write();

            delete dcm;
            delete sqrl;
        }

    }
    else if (command == "bids2squirrel") {
        p.clearPositionalArguments();
        p.addPositionalArgument("bids2squirrel", "Convert BIDS directory to squirrel.", "bids2squirrel [options]");
        p.addPositionalArgument("bidsdirectory", "The BIDS directory.", "bidsdirectory");
        p.addPositionalArgument("package", "The squirrel package.", "package");
        p.parse(QCoreApplication::arguments());
        QStringList args = p.positionalArguments();
        QString inputPath, outputPath;
        if (args.size() > 2) {
            inputPath = args[1];
            outputPath = args[2];
        }

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Dont print headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "overwrite", "Overwrite existing squirrel package if a package with same name exists"));
        p.addOption(QCommandLineOption(QStringList() << "debugsql", "Enable debugging of SQL statements"));

        p.process(a);

        bool debug = p.isSet("d");
        bool debugsql = p.isSet("debugsql");
        bool overwrite = p.isSet("overwrite");
        bool quiet = p.isSet("q");

        if (inputPath == "") {
            CommandLineError(p, "Missing input parameter. Use -i to specify an input directory.");
            PrintExampleUsage();
            return 0;
        }
        if (outputPath == "") {
            CommandLineError(p, "Missing output path. Use -o to specify an output path.");
            PrintExampleUsage();
            return 0;
        }

        /* everything is ok, so let's run the tool */
        if (!quiet)
            utils::PrintHeader();

        /* check if the infile directory exists */
        QDir indir(inputPath);
        if (!indir.exists()) {
            utils::Print(QString("Input directory [%1] does not exist").arg(indir.absolutePath()));
        }
        else if (inputPath == "") {
            utils::Print("Input directory not specified. Use the -i <indir> option to specify the input directory");
        }
        else {
            QString outputfile = outputPath;

            if (outputPath == "") {
                outputfile = QString(inputPath + "/squirrel.sqrl");
                utils::Print(QString("Output package path not specified. Creating squirrel package in input directory [%1]").arg(outputfile));
            }

            /* create a squirrel object */
            squirrel *sqrl = new squirrel(debug);
            sqrl->SetDebugSQL(debugsql);
            sqrl->SetOverwritePackage(overwrite);
            sqrl->DataFormat = "orig";

            /* create a BIDS object, and start reading the directory */
            bids *bds = new bids();

            bds->LoadToSquirrel(indir.path(), sqrl);

            /* save the squirrel object */
            sqrl->SetPackagePath(outputfile);
            sqrl->SetWriteLog(true);
            sqrl->Write();
        }
    }
    else if (command == "info") {
        p.clearPositionalArguments();
        p.addPositionalArgument("info", "Display instances of an object within a squirrel package.", "info");
        p.addPositionalArgument("package", "The squirrel package.", "package");
        p.parse(QCoreApplication::arguments());
        QStringList args = p.positionalArguments();
        QString inputPath;
        if (args.size() > 1)
            inputPath = args[1];

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "object", "List items for object [all  package  subject  study  series  observation  intervention  experiment  pipeline  groupanalysis  datadictionary].", "object"));
        p.addOption(QCommandLineOption(QStringList() << "subjectid", "Subject ID.", "subjectid"));
        p.addOption(QCommandLineOption(QStringList() << "studynum", "Study Number\n  --subjectid must also be specified.", "studynum"));
        p.addOption(QCommandLineOption(QStringList() << "dataset", "Dataset type [id  basic  full]", "dataset"));
        p.addOption(QCommandLineOption(QStringList() << "format", "Printing format [list  csv]", "format"));
        p.process(a);

        if (inputPath == "") {
            std::cout << p.helpText().toStdString().c_str();
            PrintExampleUsageInfo();
        }
        else {
            bool debug = p.isSet("d");
            ObjectType object = squirrel::ObjectTypeToEnum(p.value("object").trimmed());
            QString subjectID = p.value("subjectid").trimmed();
            int studyNum = p.value("studynum").toInt();
            QString dataset = p.value("dataset").trimmed();
            QString format = p.value("format").trimmed();

            DatasetType datasetType;
            PrintFormat printType;
            if (dataset == "id")
                datasetType = DatasetID;
            else if (dataset == "basic")
                datasetType = DatasetBasic;
            else
                datasetType = DatasetFull;

            if (format == "list")
                printType = List;
            else
                printType = CSV;

            if (object == UnknownObjectType)
                object = Package;

            QString m;
            info information;
            if (!information.DisplayInfo(inputPath, debug, object, subjectID, studyNum, datasetType, printType, m)) {
                CommandLineError(p,m);
            }
        }
    }
    else if (command == "modify") {
        p.clearPositionalArguments();
        p.addPositionalArgument("modify", "Modify squirrel package by adding/removing objects.", "modify");
        p.addPositionalArgument("package", "The squirrel package.", "package");
        p.parse(QCoreApplication::arguments());
        QStringList args = p.positionalArguments();
        QString inputPath;
        if (args.size() > 1)
            inputPath = args[1];

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Quiet mode. No printing of headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "operation", "Operation to perform on the package [add  remove  update  splitbymodality  removephi].", "operation"));
        p.addOption(QCommandLineOption(QStringList() << "object", "Object type to perform operation on [package  subject  study  series  analysis  intervention  observation  experiment  pipeline  groupanalysis  datadictionary].", "object"));
        p.addOption(QCommandLineOption(QStringList() << "datapath", "Path to new object data. Can include wildcard: /path/*.dcm", "path"));
        //p.addOption(QCommandLineOption(QStringList() << "recursive", "Search the data path recursively"));
        p.addOption(QCommandLineOption(QStringList() << "objectid", "Existing object ID, name, or number to modify.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "subjectid", "Parent subject ID. Used when adding a study, series, observation, intervention, or analysis object.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "studynum", "Parent study number. Used when adding a series or analysis object (subjectid is also needed).", "num"));
        p.addOption(QCommandLineOption(QStringList() << "objectdata", "URL-style string specifying the new object meta-data.", "string"));
        p.addOption(QCommandLineOption(QStringList() << "variablelist", "List the possible variables for the specified object (subject, study, series, analysis ...)", "object"));

        p.process(a);

        QString operation = p.value("operation").trimmed();
        //QString object = p.value("object").trimmed(); /* possible objects: subject study series observation intervention analysis experiment pipeline groupanalysis datadictionary */
        ObjectType object = squirrel::ObjectTypeToEnum(p.value("object").trimmed());
        QString dataPath = p.value("datapath").trimmed();
        QString objectData = p.value("objectdata").trimmed();
        QString objectID = p.value("objectid").trimmed();
        QString subjectID = p.value("subjectid").trimmed();
        //QString variablelist = p.value("variablelist").trimmed();
        ObjectType variableList = squirrel::ObjectTypeToEnum(p.value("variablelist").trimmed());
        int studyNum = p.value("studynum").toInt();
        //bool recursive = p.isSet("recursive");

        QString m;
        modify mod;
        if (variableList != UnknownObjectType) {
            mod.PrintVariables(variableList);
        }
        else if (!mod.DoModify(inputPath, operation, object, dataPath, objectData, objectID, subjectID, studyNum, m)) {
            CommandLineError(p,m);
        }
    }
    else if (command == "extract") {
        p.clearPositionalArguments();
        p.addPositionalArgument("extract", "Extract objects to disk from a squirrel package.", "extract");
        p.addPositionalArgument("package", "The squirrel package.", "package");
        p.parse(QCoreApplication::arguments());
        QStringList args = p.positionalArguments();
        QString inputPath;
        if (args.size() > 1)
            inputPath = args[1];

        /* command line flag options */
        p.addOption(QCommandLineOption(QStringList() << "d" << "debug", "Enable debugging"));
        p.addOption(QCommandLineOption(QStringList() << "q" << "quiet", "Quiet mode. No printing of headers and checks"));
        p.addOption(QCommandLineOption(QStringList() << "object", "Object type to perform operation on [package  subject  study  series  analysis  intervention  observation  experiment  pipeline  groupanalysis  datadictionary].", "object"));
        p.addOption(QCommandLineOption(QStringList() << "outdir", "Path to output directory", "outdir"));
        p.addOption(QCommandLineOption(QStringList() << "objectid", "Existing object ID, name, or number to modify.", "identifer"));
        p.addOption(QCommandLineOption(QStringList() << "subjectid", "Parent subject ID. Used when extracting a study, series, observation, intervention, or analysis object.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "studynum", "Parent study number. Used when extracting a series or analysis object (subjectid is also needed).", "num"));
        p.addOption(QCommandLineOption(QStringList() << "recurse", "Include all child objects of the specified object"));

        p.process(a);

        bool recurse = p.isSet("recurse");
        QString object = p.value("object").trimmed(); /* possible objects: subject study series observation intervention analysis experiment pipeline groupanalysis datadictionary */
        QString outputPath = p.value("outdir").trimmed();
        QString objectID = p.value("objectid").trimmed();
        QString subjectID = p.value("subjectid").trimmed();
        int studyNum = p.value("studynum").toInt();

        /* validate the input */
        if (inputPath == "") {
            std::cout << p.helpText().toStdString().c_str();
            PrintExampleUsageExtract();
        }
        else {
            QString m;
            extract ext;
            if (!ext.DoExtract(inputPath, outputPath, object, objectID, subjectID, studyNum, m)) {
                CommandLineError(p,m);
            }
        }
    }
    else if (command == "validate") {
        p.clearPositionalArguments();
        p.addPositionalArgument("validate", "Validate a squirrel package.", "validate [options]");
        p.addPositionalArgument("package", "The squirrel package.", "package");
        p.parse(QCoreApplication::arguments());
        QStringList args = p.positionalArguments();
        QString inputPath;
        if (args.size() > 1)
            inputPath = args[1];

        p.process(a);

        bool debug = false;

        if (inputPath == "") {
            CommandLineError(p, "Missing input parameter. Use -i to specify an input directory.");
            return 0;
        }

        /* create squirrel object and validate */
        squirrel *sqrl = new squirrel(debug);
        sqrl->SetPackagePath(inputPath);
        sqrl->SetFileMode(FileMode::ExistingPackage);
        if (sqrl->Read()) {
            sqrl->Log("Valid squirrel file");
        }
        else {
            sqrl->Log("*** Invalid squirrel file ***");
        }
        delete sqrl;
    }
    else {
        bool v = p.isSet("v");
        if (v)
            p.showVersion();
        else {
            PrintExampleUsage();
            p.showHelp(0);
        }
    }

    a.exit();
    return 0;
}
