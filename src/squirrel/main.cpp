/* ------------------------------------------------------------------------------
  Squirrel main.cpp
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

#include <QCoreApplication>
#include <QCommandLineParser>
#include <iostream>
#include "../nidb/version.h"
#include "validate.h"
#include "dicom.h"

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
    p.addPositionalArgument("tool", "Available tools:  dicom2squirrel  validate");

    /* command line flag options */
    QCommandLineOption optDebug(QStringList() << "debug", "Enable debugging");
    QCommandLineOption optQuiet(QStringList() << "q" << "quiet", "Dont print headers and checks");
    p.addOption(optDebug);
    p.addOption(optQuiet);

    /* command line options that take values */
    QCommandLineOption optDicomDir(QStringList() << "d" << "dicomdir", "Path to directory containing DICOM files", "dicomdir");
	QCommandLineOption optOutputFile(QStringList() << "o" << "out", "Output file", "out");
	QCommandLineOption optInputFile(QStringList() << "i" << "in", "Input file", "in");
    p.addOption(optDicomDir);
    p.addOption(optOutputFile);
    p.addOption(optInputFile);

    /* Process the actual command line arguments given by the user */
    p.process(a);

    QString tool;
    bool debug, quiet;

    const QStringList args = p.positionalArguments();
    if (args.size() > 0)
        tool = args.at(0).trimmed();

    debug = p.isSet(optDebug);
    quiet = p.isSet(optQuiet);
    QString paramDicomDir = p.value(optDicomDir).trimmed();
    QString paramOutputFile = p.value(optOutputFile).trimmed();
    QString paramInputFile = p.value(optInputFile).trimmed();

    QStringList tools = { "dicom2squirrel", "validate" };

    /* now check the command line parameters passed in, to see if they are calling a valid module */
    if (!tools.contains(tool)) {
        if (tool != "")
            std::cout << QString("Error: unrecognized option [%1]\n").arg(tool).toStdString().c_str();

        std::cout << p.helpText().toStdString().c_str();
        return 0;
    }

	QString bindir = QDir::currentPath();

    Print("+----------------------------------------------------+");
    Print(QString("|  Squirrel utils version %1.%2\n|\n|  Build date [%3 %4]\n|  C++ [%5]\n|  Qt compiled [%6]\n|  Qt runtime [%7]\n|  Build system [%8]" ).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN).arg(__DATE__).arg(__TIME__).arg(__cplusplus).arg(QT_VERSION_STR).arg(qVersion()).arg(QSysInfo::buildAbi()));
    Print(QString("|\n|  Current working directory is %1").arg(bindir));
    Print("+----------------------------------------------------+\n");

	/* ----- check the tool to run ----- */
    if (tool == "validate") {
        if (paramInputFile.trimmed() == "") {
            Print("*** Input file blank ***");
        }
        else if (!QFile::exists(paramInputFile)) {
            Print(QString("*** Input file [%1] does not exist ***").arg(paramInputFile));
        }
        else {
            /* create validate object */
            validate *v = new validate();
            QString m;
            if (v->LoadSquirrel(paramInputFile, m)) {
                Print("Successfully loaded squirrel file");
            }
            else {
                Print(QString("*** Unable to load squirrel file [%1] ***").arg(m));
            }

            delete v;
        }
    }
    else if (tool == "dicom2squirrel") {
        /* 1) load DICOM directory into subject/study/series objects
         * based on PatientID (subject) StudyUID (study) SeriesUID (series)
         *
         * 2) write squirrel using the loaded information
         */
        dicom *dcm = new dicom();
		QString m;
		dcm->ReadDirectory(paramDicomDir, bindir, m);

        delete dcm;
    }
    else if (tool == "bids2squirrel") {

    }
    else if (tool == "squirrel2bids") {

    }

    Print("\n\nExiting squirrel utils");
    a.exit();
    return 0;
}
