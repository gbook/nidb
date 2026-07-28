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
#include "convert.h"
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
    printf("    squirrel convert <inputDir> <outputPackage> --inputformat dicom --outputformat squirrel --dataformat nifti4d --dirformat orig\n");
    printf("    squirrel convert <inputDir> <outputPackage> --inputformat bids --outputformat squirrel\n");
    printf("    squirrel modify <package> --operation update --object subject --objectdata 'SubjectID=S1234&DateOfBirth=1999-12-31&Sex=M&Gender=M'\n");
}

void PrintExampleUsageConvert() {
    printf("\nExample convert usage: \n");
    printf("    squirrel convert <inputDir> <outputPackage> --inputformat dicom --outputformat squirrel --dataformat nifti4d --dirformat orig\n");
    printf("    squirrel convert <inputDir> <outputPackage> --inputformat bids --outputformat squirrel\n");
}

void PrintExampleModifyUsage() {
    printf("\nExample modify usage: \n");
    printf("    squirrel modify <package> --operation update --object subject --objectdata 'SubjectID=S1234&DateOfBirth=1999-12-31&Sex=M&Gender=M'\n");
    printf("    squirrel modify <package> --operation add --object series --subjectid S1234 --studynum 1 --objectdata 'SeriesNumber=3' --datapath /path/to/dicom/\n");
    printf("    squirrel modify <package> --operation add --object series --subjectid S1234 --studynum 1 --seriesnum 3 --datapath /more/files/*.dcm\n");
    printf("    squirrel modify <package> --operation add --object experiment --objectid MyExperiment --datapath /path/to/files/ --recursive\n");
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
    p.addPositionalArgument("tool", "Available tools:\n   convert - Convert DICOM or BIDS data into a squirrel package\n   info - Display information about a package or its contents\n   merge - Merge two or more packages into one\n   modify - Add/remove objects from a package\n   extract - Extract data from a package\n   validate - Check if a package is valid");
    p.parse(QCoreApplication::arguments());
    const QStringList args = p.positionalArguments();
    const QString command = args.isEmpty() ? QString() : args.first();

    /* check which tool to run */
    if (command == "convert") {
        p.clearPositionalArguments();
        p.addPositionalArgument("convert", "Convert DICOM or BIDS data into a squirrel package.", "convert [options]");
        p.addPositionalArgument("inputdirectory", "The input data directory (DICOM or BIDS).", "inputdirectory");
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
        p.addOption(QCommandLineOption(QStringList() << "inputformat", "Input data format [bids  dicom]", "format"));
        p.addOption(QCommandLineOption(QStringList() << "outputformat", "Output data format [squirrel] (default: squirrel)", "format"));
        p.addOption(QCommandLineOption(QStringList() << "dataformat", "Output data format for DICOM input (ignored for BIDS):\n  anon - Anonymized DICOM\n  nifti4d - Nifti 4D\n  nifti4dgz - Nifti 4D gz (default)\n  nifti3d - Nifti 3D\n  nifti3dgz - Nifti 3D gz", "format"));
        p.addOption(QCommandLineOption(QStringList() << "dirformat", "Output directory structure\n  seq - Sequentially numbered\n  orig - Original ID (default)", "format"));
        p.addOption(QCommandLineOption(QStringList() << "overwrite", "Overwrite existing squirrel package if a package with same name exists"));
        p.addOption(QCommandLineOption(QStringList() << "debugsql", "Enable debugging of SQL statements"));

        p.process(a);

        bool debug = p.isSet("d");
        bool quiet = p.isSet("q");
        bool overwrite = p.isSet("overwrite");
        bool debugsql = p.isSet("debugsql");
        QString inputFormat = p.value("inputformat").trimmed();
        QString outputFormat = p.value("outputformat").trimmed();
        QString paramOutputDataFormat = p.value("dataformat").trimmed();
        QString paramOutputDirFormat = p.value("dirformat").trimmed();

        /* default the output format to squirrel */
        if (outputFormat == "")
            outputFormat = "squirrel";

        if (inputPath == "") {
            CommandLineError(p, "Missing input path.");
            PrintExampleUsageConvert();
            return 0;
        }
        if (outputPath == "") {
            CommandLineError(p, "Missing output path.");
            PrintExampleUsageConvert();
            return 0;
        }
        if (inputFormat == "") {
            CommandLineError(p, "Missing --inputformat. Valid values: bids, dicom");
            PrintExampleUsageConvert();
            return 0;
        }

        /* finished with checks, now run the tool */
        if (!quiet)
            utils::PrintHeader();

        QString m;
        convert converter;
        if (!converter.DoConvert(inputPath, outputPath, inputFormat, outputFormat, paramOutputDataFormat, paramOutputDirFormat, overwrite, debug, debugsql, quiet, m)) {
            CommandLineError(p, m);
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
            QString dataset = p.value("dataset").trimmed();
            QString format = p.value("format").trimmed();

            infoQuery query;
            query.debug = p.isSet("d");
            query.object = squirrel::ObjectTypeToEnum(p.value("object").trimmed());
            query.subjectID = p.value("subjectid").trimmed();
            query.studyNum = p.value("studynum").toInt();

            if (dataset == "id")
                query.dataset = DatasetID;
            else if (dataset == "basic")
                query.dataset = DatasetBasic;
            else
                query.dataset = DatasetFull;

            if (format == "csv")
                query.printFormat = CSV;
            else
                query.printFormat = List;

            if (query.object == UnknownObjectType)
                query.object = Package;

            QString m;
            info information;
            if (!information.DisplayInfo(inputPath, query, m)) {
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
        p.addOption(QCommandLineOption(QStringList() << "operation", "Operation to perform on the package [add  remove  update  splitbymodality  removephi  renumber].", "operation"));
        p.addOption(QCommandLineOption(QStringList() << "object", "Object type to perform operation on [package  subject  study  series  analysis  intervention  observation  experiment  pipeline  groupanalysis  datadictionary].", "object"));
        p.addOption(QCommandLineOption(QStringList() << "datapath", "Path to files to add. May be a directory, a single file, or a glob pattern (e.g. /path/*.dcm)", "path"));
        p.addOption(QCommandLineOption(QStringList() << "recursive", "Search the datapath directory recursively for files"));
        p.addOption(QCommandLineOption(QStringList() << "objectid", "Existing object ID, name, or number to modify.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "subjectid", "Parent subject ID. Used when adding a study, series, observation, intervention, or analysis object.", "id"));
        p.addOption(QCommandLineOption(QStringList() << "studynum", "Parent study number. Used when adding a series or analysis object (subjectid also required).", "num"));
        p.addOption(QCommandLineOption(QStringList() << "seriesnum", "Parent series number. Used when updating a series object (subjectid and studynum also required).", "num"));
        p.addOption(QCommandLineOption(QStringList() << "objectdata", "URL-style string specifying the new object meta-data.", "string"));
        p.addOption(QCommandLineOption(QStringList() << "variablelist", "List the possible variables for the specified object (subject, study, series, analysis ...)", "object"));
        p.addOption(QCommandLineOption(QStringList() << "digits", "Number of digits for renumbered subject IDs (e.g. 4 produces 0001...9999). Default: auto-sized.", "num"));
        p.addOption(QCommandLineOption(QStringList() << "startnum", "Starting number for renumbering (default: 1).", "num"));
        p.addOption(QCommandLineOption(QStringList() << "prefix", "Prefix string prepended to renumbered subject IDs (e.g. 'sub' produces sub0001, sub0002, ...).", "string"));
        p.addOption(QCommandLineOption(QStringList() << "random", "Randomly assign new subject IDs instead of sorting ascending."));

        p.process(a);

        QString operation = p.value("operation").trimmed();
        ObjectType object = squirrel::ObjectTypeToEnum(p.value("object").trimmed());
        QString dataPath = p.value("datapath").trimmed();
        QString objectData = p.value("objectdata").trimmed();
        QString objectID = p.value("objectid").trimmed();
        QString subjectID = p.value("subjectid").trimmed();
        ObjectType variableList = squirrel::ObjectTypeToEnum(p.value("variablelist").trimmed());
        int studyNum = p.value("studynum").toInt();
        int seriesNum = p.value("seriesnum").toInt();
        int digits = p.value("digits").toInt();
        int startNum = p.isSet("startnum") ? p.value("startnum").toInt() : 1;
        QString prefix = p.value("prefix").trimmed();
        bool randomize = p.isSet("random");
        bool recursive = p.isSet("recursive");

        modification mod;
        mod.operation = operation;
        mod.object = object;
        mod.dataPath = dataPath;
        mod.objectData = objectData;
        mod.objectID = objectID;
        mod.subjectID = subjectID;
        mod.studyNumber = studyNum;
        mod.seriesNumber = seriesNum;
        mod.renumberDigits = digits;
        mod.renumberStartNum = startNum;
        mod.renumberPrefix = prefix;
        mod.renumberRandomize = randomize;
        mod.recursive = recursive;

        QString m;
        modify modifier;
        if (variableList != UnknownObjectType) {
            modifier.PrintVariables(variableList);
        }
        else if (!modifier.DoModify(inputPath, mod, m)) {
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
        //p.addOption(QCommandLineOption(QStringList() << "recurse", "Include all child objects of the specified object"));

        p.process(a);

        //bool recurse = p.isSet("recurse");
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
    else if (command == "merge") {
        p.clearPositionalArguments();
        p.addPositionalArgument("merge", "Merge two or more squirrel packages into one.", "merge [options]");
        p.addPositionalArgument("packages", "Two or more input squirrel packages.", "package1 package2 [package3 ...]");
        p.parse(QCoreApplication::arguments());

        p.addOption(QCommandLineOption(QStringList() << "output", "Output squirrel package path (required).", "path"));
        p.addOption(QCommandLineOption(QStringList() << "test", "Detect and display collisions without writing output."));
        p.addOption(QCommandLineOption(QStringList() << "renumbersubjects", "Sort all subjects globally and assign new sequential IDs; append original ID to AlternateIDs."));
        p.addOption(QCommandLineOption(QStringList() << "digits", "Number of digits for renumbered subject IDs (e.g. 4 produces 0001...9999). Auto-sized if omitted or too small.", "n"));

        p.process(a);

        QStringList positional = p.positionalArguments();
        QStringList inputPaths;
        for (int i = 1; i < positional.size(); ++i)
            inputPaths.append(positional[i]);

        const QString outputPath    = p.value("output").trimmed();
        const bool testOnly         = p.isSet("test");
        const bool renumberSubjects = p.isSet("renumbersubjects");
        const int  digits           = p.value("digits").toInt();

        if (inputPaths.size() < 2) {
            CommandLineError(p, "At least two input packages are required.");
            return 1;
        }
        if (outputPath.isEmpty() && !testOnly) {
            CommandLineError(p, "Missing --output path. Specify an output package with --output <path>.");
            return 1;
        }

        utils::PrintHeader();
        QString mergeMsg;
        modify merger;
        if (!merger.MergePackages(inputPaths, outputPath, testOnly, renumberSubjects, digits, mergeMsg))
            CommandLineError(p, mergeMsg);
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
