/* ------------------------------------------------------------------------------
  NIDB mainCluster.cpp
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

/* Entry point for the standalone `nidbcluster` executable. This is a trimmed-down
   counterpart to main.cpp that provides ONLY the cluster module - the small set of
   commands a compute node runs to check pipeline jobs in and out. It deliberately
   links neither dcmtk nor the squirrel library (see nidbcluster.pro), so it builds
   fast and light on cluster nodes that never touch imaging data directly. */

#include <QCoreApplication>
#include "nidb.h"
#include "utils.h"
#include "moduleCluster.h"
#include <iostream>

/* ---------------------------------------------------------- */
/* --------- main ------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief main Entry point into the nidbcluster executable. Runs a single cluster
 *        submodule and exits.
 */
int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    a.setApplicationVersion(QString("%1.%2.%3").arg(VERSION_MAJ).arg(VERSION_MIN).arg(BUILD_NUM));
    a.setApplicationName("Neuroinformatics Database (NiDB) - cluster");

    /* setup the command line parser */
    QCommandLineParser p;
    p.setApplicationDescription("Neuroinformatics Database (NiDB) cluster module");
    p.setSingleDashWordOptionMode(QCommandLineParser::ParseAsCompactedShortOptions);
    p.setOptionsAfterPositionalArgumentsMode(QCommandLineParser::ParseAsOptions);
    p.addHelpOption();
    p.addVersionOption();
    p.addPositionalArgument("module", "The only available module is: cluster");

    /* command line options used by the cluster submodules (mirrors main.cpp) */
    QCommandLineOption optAnalysisID(QStringList() << "a" << "analysisid", "resultinsert -or- pipelinecheckin submodules only", "analysisid");
    QCommandLineOption optCommand(QStringList() << "c" << "command", "pipelinecheckin submodule", "command");
    QCommandLineOption optMessage(QStringList() << "m" << "message", "pipelinecheckin submodule", "message");
    QCommandLineOption optResultDesc(QStringList() << "e" << "desc", "Result description (resultinsert submodule)", "desc");
    QCommandLineOption optResultFile(QStringList() << "f" << "file", "Insert file result (resultinsert submodule)", "filepath");
    QCommandLineOption optResultImage(QStringList() << "i" << "image", "Insert image result (resultinsert submodule)", "imagepath");
    QCommandLineOption optResultNumber(QStringList() << "n" << "number", "Insert numerical result (resultinsert submodule)", "number");
    QCommandLineOption optResultText(QStringList() << "t" << "text", "Insert text result (resultinsert submodule)", "text");
    QCommandLineOption optResultUnit(QStringList() << "unit", "Result unit (resultinsert submodule)", "unit");
    QCommandLineOption optStep(QStringList() << "step", "Pipeline checkin step number", "step");
    QCommandLineOption optStatus(QStringList() << "s" << "status", "pipelinecheckin submodule", "status");
    QCommandLineOption optSubModule(QStringList() << "u" << "submodule", "Cluster sub-modules [ resultinsert, pipelinecheckin, updateanalysis, checkcompleteanalysis ]", "submodule");
    p.addOption(optAnalysisID);
    p.addOption(optCommand);
    p.addOption(optMessage);
    p.addOption(optResultDesc);
    p.addOption(optResultFile);
    p.addOption(optResultImage);
    p.addOption(optResultNumber);
    p.addOption(optResultText);
    p.addOption(optResultUnit);
    p.addOption(optStatus);
    p.addOption(optStep);
    p.addOption(optSubModule);

    p.process(a);

    QString module;
    const QStringList args = p.positionalArguments();
    if (args.size() > 0)
        module = args.at(0).trimmed();

    QString paramAnalysisID  = p.value(optAnalysisID).trimmed();
    QString paramCommand     = p.value(optCommand).trimmed();
    QString paramMessage     = p.value(optMessage).trimmed();
    QString paramResultDesc  = p.value(optResultDesc).trimmed();
    QString paramResultFile  = p.value(optResultFile).trimmed();
    QString paramResultImage = p.value(optResultImage).trimmed();
    QString paramResultNumber= p.value(optResultNumber).trimmed();
    QString paramResultText  = p.value(optResultText).trimmed();
    QString paramResultUnit  = p.value(optResultUnit).trimmed();
    QString paramStatus      = p.value(optStatus).trimmed();
    QString paramStep        = p.value(optStep).trimmed();
    QString paramSubModule   = p.value(optSubModule).trimmed();

    QStringList submodules = {
        "checkcompleteanalysis",
        "pipelinecheckin",
        "resultinsert",
        "updateanalysis",
    };

    /* this executable only runs the cluster module */
    if (module != "cluster") {
        std::cout << QString("Error: this is the cluster-only executable. Run with the 'cluster' module.\n").toStdString().c_str();
        std::cout << p.helpText().toStdString().c_str();
        return 0;
    }
    if (!submodules.contains(paramSubModule)) {
        std::cout << QString("Error: unrecognized cluster submodule [%1]\n").arg(paramSubModule).toStdString().c_str();
        std::cout << p.helpText().toStdString().c_str();
        return 0;
    }

    /* load the config file and connect to the database in cluster mode */
    nidb *n = new nidb(module, true);
    n->DatabaseConnect(true);
    moduleCluster *m = new moduleCluster(n);

    bool ret = false;
    QString msg;
    if (paramSubModule == "pipelinecheckin")
        ret = m->PipelineCheckin(paramAnalysisID, paramStatus, paramStep, paramMessage, paramCommand, msg);
    else if (paramSubModule == "resultinsert")
        ret = m->ResultInsert(paramAnalysisID, paramResultText, paramResultNumber, paramResultFile, paramResultImage, paramResultDesc, paramResultUnit, msg);
    else if (paramSubModule == "updateanalysis")
        ret = m->UpdateAnalysis(paramAnalysisID, msg);
    else if (paramSubModule == "checkcompleteanalysis")
        ret = m->CheckCompleteAnalysis(paramAnalysisID, msg);

    Print(msg);
    if (!ret)
        std::cout << "Error: " << msg.toStdString().c_str() << std::endl;

    delete m;
    delete n;

    return 0;
}
