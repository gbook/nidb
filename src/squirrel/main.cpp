/* ------------------------------------------------------------------------------
  NIDB main.cpp
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
#include "version.h"

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
    QCommandLineOption optDebug(QStringList() << "d" << "debug", "Enable debugging");
    QCommandLineOption optQuiet(QStringList() << "q" << "quiet", "Dont print headers and checks");
    p.addOption(optDebug);
    p.addOption(optQuiet);

    /* command line options that take values */
    QCommandLineOption optAnalysisID(QStringList() << "a" << "analysisid", "resultinsert -or- pipelinecheckin submodules only", "analysisid");
    QCommandLineOption optStatus(QStringList() << "s" << "status", "pipelinecheckin submodule", "status");
    QCommandLineOption optMessage(QStringList() << "m" << "message", "pipelinecheckin submodule", "message");
    QCommandLineOption optCommand(QStringList() << "c" << "command", "pipelinecheckin submodule", "command");
    QCommandLineOption optResultText(QStringList() << "t" << "text", "Insert text result (resultinsert submodule)", "text");
    QCommandLineOption optResultNumber(QStringList() << "n" << "number", "Insert numerical result (resultinsert submodule)", "number");
    QCommandLineOption optResultFile(QStringList() << "f" << "file", "Insert file result (resultinsert submodule)", "filepath");
    QCommandLineOption optResultImage(QStringList() << "i" << "image", "Insert image result (resultinsert submodule)", "imagepath");
    QCommandLineOption optResultDesc(QStringList() << "e" <<"desc", "Result description (resultinsert submodule)", "desc");
    QCommandLineOption optResultUnit(QStringList() << "unit", "Result unit (resultinsert submodule)", "unit");
    p.addOption(optAnalysisID);
    p.addOption(optStatus);
    p.addOption(optMessage);
    p.addOption(optCommand);
    p.addOption(optResultText);
    p.addOption(optResultNumber);
    p.addOption(optResultFile);
    p.addOption(optResultImage);
    p.addOption(optResultDesc);
    p.addOption(optResultUnit);

    /* Process the actual command line arguments given by the user */
    p.process(a);

    QString module;
    bool debug, quiet;

    const QStringList args = p.positionalArguments();
    if (args.size() > 0)
        module = args.at(0).trimmed();

    debug = p.isSet(optDebug);
    quiet = p.isSet(optQuiet);
    QString paramAnalysisID = p.value(optAnalysisID).trimmed();
    QString paramStatus = p.value(optStatus).trimmed();
    QString paramMessage = p.value(optMessage).trimmed();
    QString paramCommand = p.value(optCommand).trimmed();
    QString paramResultText = p.value(optResultText).trimmed();
    QString paramResultNumber = p.value(optResultNumber).trimmed();
    QString paramResultFile = p.value(optResultFile).trimmed();
    QString paramResultImage = p.value(optResultImage).trimmed();
    QString paramResultDesc = p.value(optResultDesc).trimmed();
    QString paramResultUnit = p.value(optResultUnit).trimmed();

    QStringList tools = { "dicom2squirrel", "validate" };

    /* now check the command line parameters passed in, to see if they are calling a valid module */
    if (!tools.contains(module)) {
        std::cout << QString("Error: unrecognized module [%1]").arg(module).toStdString().c_str();
        std::cout << p.helpText().toStdString().c_str();
        return 0;
    }

    return a.exec();
}
