/* ------------------------------------------------------------------------------
  Squirrel main.cpp
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

#include <QCoreApplication>
#include <QCommandLineParser>
#include <iostream>
#include "squirrelVersion.h"
#include "validate.h"
#include "dicom.h"
#include "squirrel.h"

int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    /* this whole section reads the command line parameters */
    a.setApplicationVersion(QString("%1.%2").arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN));
    a.setApplicationName("Squirrel");

    /* setup the command line parser */
    QCommandLineParser p;
    p.setApplicationDescription("Squirrel data format tools");
    p.setSingleDashWordOptionMode(QCommandLineParser::ParseAsCompactedShortOptions);
    p.setOptionsAfterPositionalArgumentsMode(QCommandLineParser::ParseAsOptions);
    p.addHelpOption();
    p.addVersionOption();
    p.addPositionalArgument("tool", "Available tools:  validate  dicom2squirrel  bids2squirrel  squirrel2bids");

    /* command line flag options */
    QCommandLineOption optDebug(QStringList() << "d" << "debug", "Enable debugging");
    QCommandLineOption optQuiet(QStringList() << "q" << "quiet", "Dont print headers and checks");
    p.addOption(optDebug);
    p.addOption(optQuiet);

    /* command line options that take values */
    QCommandLineOption optInputFile(QStringList() << "i" << "in", "Input file/directory", "in");
    QCommandLineOption optOutputFile(QStringList() << "o" << "out", "Output file", "out");
    QCommandLineOption optOutputDataFormat(QStringList() << "output-data-format", "Output data format, if converted from DICOM:\n  anon - Anonymized DICOM\n  nifti4d - Nifti 4D\n  nifti4dgz - Nifti 4D gz (default)\n  nifti3d - Nifti 3D\n  nifti3dgz - Nifti 3D gz", "outputdataformat");
    QCommandLineOption optOutputDirFormat(QStringList() << "output-dir-format", "Output directory structure\n  seq - Sequentially numbered\n  orig - Original ID (default)", "outputdirformat");
    p.addOption(optOutputFile);
    p.addOption(optInputFile);
    p.addOption(optOutputDataFormat);
    p.addOption(optOutputDirFormat);

    /* Process the actual command line arguments given by the user */
    p.process(a);

    QString tool;
    bool debug, quiet;

    const QStringList args = p.positionalArguments();
    if (args.size() > 0)
        tool = args.at(0).trimmed();

    debug = p.isSet(optDebug);
    quiet = p.isSet(optQuiet);
    QString paramOutputFile = p.value(optOutputFile).trimmed();
    QString paramInputFile = p.value(optInputFile).trimmed();
    QString paramOutputDataFormat = p.value(optOutputDataFormat).trimmed();
    QString paramOutputDirFormat = p.value(optOutputDirFormat).trimmed();

    QStringList tools = { "dicom2squirrel", "validate", "bids2squirrel", "squirrel2bids" };

    /* now check the command line parameters passed in, to see if they are calling a valid module */
    if (!tools.contains(tool)) {
        if (tool != "")
            std::cout << QString("Error: unrecognized option [%1]\n").arg(tool).toStdString().c_str();

        std::cout << p.helpText().toStdString().c_str();
        return 0;
    }

    QString bindir = QDir::currentPath();

    if (!quiet) {
        Print("+----------------------------------------------------+");
        Print(QString("|  Squirrel utils version %1.%2\n|\n|  Build date [%3 %4]\n|  C++ [%5]\n|  Qt compiled [%6]\n|  Qt runtime [%7]\n|  Build system [%8]" ).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN).arg(__DATE__).arg(__TIME__).arg(__cplusplus).arg(QT_VERSION_STR).arg(qVersion()).arg(QSysInfo::buildAbi()));
        Print(QString("|\n|  Current working directory is %1").arg(bindir));
        Print("+----------------------------------------------------+\n");
    }

    /* ---------- Run the validate tool ---------- */
    if (tool == "validate") {
        if (paramInputFile.trimmed() == "") {
            Print("*** Input file blank ***");
        }
        else if (!QFile::exists(paramInputFile)) {
            Print(QString("*** Input file [%1] does not exist ***").arg(paramInputFile));
        }
        else {
            /* create squirrel object and validate */
            squirrel *sqrl = new squirrel();
            QString m;
            if (sqrl->read(paramInputFile, true)) {
                Print("Valid squirrel file");
            }
            else {
                Print(QString("*** Invalid squirrel file [%1] ***").arg(m));
            }

            //delete v;
        }
    }
    /* ---------- Run the dicom2squirrel tool ---------- */
    else if (tool == "dicom2squirrel") {

        /* check if the outfile's parent directory exists */
        QFileInfo outinfo(paramOutputFile);
        QDir outdir = outinfo.absolutePath();
        if (!outdir.exists()) {
            Print(QString("Output directory [%1] does not exist").arg(outdir.absolutePath()));
        }
        else {
            dicom *dcm = new dicom();
            squirrel *sqrl = new squirrel();
            sqrl->dataFormat = paramOutputDataFormat;
            sqrl->subjectDirFormat = paramOutputDirFormat;
            sqrl->studyDirFormat = paramOutputDirFormat;
            sqrl->seriesDirFormat = paramOutputDirFormat;

            /* 1) load the DICOM data to a squirrel object */
            QString m;
            dcm->LoadToSquirrel(paramInputFile, bindir, sqrl, m);

            /* 2) write the squirrel file */
            QString m2;
            QString filepath;
            sqrl->write(paramOutputFile, filepath, m2, debug);

            delete dcm;
            delete sqrl;
        }

    }
    /* ---------- Run the bids2squirrel tool ---------- */
    else if (tool == "bids2squirrel") {

    }
    /* ---------- Run the squirrel2bids tool ---------- */
    else if (tool == "squirrel2bids") {

    }

    Print("\n\nExiting squirrel utils");
    a.exit();
    return 0;
}
