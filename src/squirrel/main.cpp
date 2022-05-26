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

int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    /* this whole section reads the command line parameters */
    a.setApplicationVersion(QString("%1.%2").arg(VERSION_MAJ).arg(VERSION_MIN));
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
    QCommandLineOption optOutputFile(QStringList() << "o" << "out", "Output squirrel file", "out");
    QCommandLineOption optInputFile(QStringList() << "i" << "in", "Input squirrel file", "in");
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

    if (tool == "validate") {
        /* create validate object */
        validate *v = new validate();

        delete v;
    }
    else if (tool == "dicom2squirrel") {

    }

    return a.exec();
}
