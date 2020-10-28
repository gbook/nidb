/* ------------------------------------------------------------------------------
  NIDB main.cpp
  Copyright (C) 2004 - 2020
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
#include <QDebug>
#include "nidb.h"
#include "moduleFileIO.h"
#include "moduleExport.h"
#include "moduleManager.h"
#include "moduleImport.h"
#include "moduleImportUploaded.h"
#include "moduleUpload.h"
#include "moduleMRIQA.h"
#include "moduleQC.h"
#include "modulePipeline.h"
#include "moduleCluster.h"
#include "moduleMiniPipeline.h"
#include <iostream>
#include <SmtpMime>

/* ---------------------------------------------------------- */
/* --------- main ------------------------------------------- */
/* ---------------------------------------------------------- */
int main(int argc, char *argv[])
{
	QCoreApplication a(argc, argv);

	/* this whole section reads the command line parameters */
	a.setApplicationVersion(QString("%1.%2.%3").arg(VERSION_MAJ).arg(VERSION_MIN).arg(BUILD_NUM));
	a.setApplicationName("Neuroinformatics Database (NiDB)");

	/* setup the command line parser */
	QCommandLineParser p;
	p.setApplicationDescription("Neuroinformatics Database (NiDB)");
	p.setSingleDashWordOptionMode(QCommandLineParser::ParseAsCompactedShortOptions);
	p.setOptionsAfterPositionalArgumentsMode(QCommandLineParser::ParseAsOptions);
	p.addHelpOption();
	p.addVersionOption();
    p.addPositionalArgument("module", "Available modules:  import  export  fileio  mriqa  qc  modulemanager  importuploaded  upload  pipeline  cluster  minipipeline");

	/* command line flag options */
	QCommandLineOption optDebug(QStringList() << "d" << "debug", "Enable debugging");
	QCommandLineOption optQuiet(QStringList() << "q" << "quiet", "Dont print headers and checks");
    QCommandLineOption optReset(QStringList() << "r" << "reset", "Reset, and then run, the specified module");
    p.addOption(optDebug);
	p.addOption(optQuiet);
    p.addOption(optReset);

	/* command line options that take values */
	QCommandLineOption optSubModule(QStringList() << "u" <<"submodule", "For running on cluster. Sub-modules [ resultinsert, pipelinecheckin, updateanalysis, checkcompleteanalysis ]", "submodule");
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
	p.addOption(optSubModule);
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
    bool debug, quiet, reset;

	const QStringList args = p.positionalArguments();
	if (args.size() > 0)
		module = args.at(0).trimmed();

	debug = p.isSet(optDebug);
	quiet = p.isSet(optQuiet);
    reset = p.isSet(optReset);
    QString paramSubModule = p.value(optSubModule).trimmed();
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

    QStringList modules = { "export", "fileio", "qc", "mriqa", "modulemanager", "import", "pipeline", "importuploaded", "upload", "cluster", "minipipeline" };
	QStringList submodules = { "pipelinecheckin", "resultinsert", "updateanalysis", "checkcompleteanalysis"};

	/* now check the command line parameters passed in, to see if they are calling a valid module */
	if (!modules.contains(module)) {
		std::cout << QString("Error: unrecognized module [%1]").arg(module).toStdString().c_str();
		std::cout << p.helpText().toStdString().c_str();
		return 0;
	}
	if (module == "cluster") {
		if (!submodules.contains(paramSubModule)) {
			std::cout << QString("Error: unrecognized cluster module [%1]").arg(paramSubModule).toStdString().c_str();
			std::cout << p.helpText().toStdString().c_str();
			return 0;
		}
	}

	/* we've gotten this far, so let's create the nidb object */
	nidb *n;

	/* check if this is being run from the cluster or locally */
	if (module == "cluster") {
		/* load the config file and connect to the database */
		n = new nidb(module, true);
		n->DatabaseConnect(true);
		moduleCluster *m = new moduleCluster(n);

		bool ret = false;
		QString msg;
		if (paramSubModule == "pipelinecheckin")
			ret = m->PipelineCheckin(paramAnalysisID, paramStatus, paramMessage, paramCommand, msg);
		else if (paramSubModule == "resultinsert")
			ret = m->ResultInsert(paramAnalysisID, paramResultText, paramResultNumber, paramResultFile, paramResultImage, paramResultDesc, paramResultUnit, msg);
		else if (paramSubModule == "updateanalysis")
			ret = m->UpdateAnalysis(paramAnalysisID, msg);
		else if (paramSubModule == "checkcompleteanalysis")
			ret = m->CheckCompleteAnalysis(paramAnalysisID, msg);

		/* if the operation failed, let the user know */
		if (!ret)
			std::cout << "Error: " << msg.toStdString().c_str() << std::endl;

		delete m;
	}
	else {
		/* a regular module is being called */
		if (!quiet) {
			printf("\n\n-------------------------------------------------------------\n");
            printf("----- \033[1mStarting Neuroinformatics Database (NiDB) backend\033[0m -----\n");
			printf("-------------------------------------------------------------\n");
		}

		/* load the config file and connect to the database */
		n = new nidb(module);
		if (n->DatabaseConnect()) {
			bool keepLog = false;
			if (debug)
				n->cfg["debug"] = "1";

			if (n->cfg["debug"].toInt())
				if (!quiet)
					printf("------------------------- DEBUG MODE ------------------------\n");

			if (!quiet)
				n->Print(QString(n->GetBuildString()));

            if (reset)
                n->ClearLockFiles();

			/* check if this module should be running now or not */
			if (n->ModuleCheckIfActive()) {
				int numlock = n->CheckNumLockFiles();
				if (numlock < n->GetNumThreads()) {
					if (n->CreateLockFile()) {

						n->CreateLogFile();

						/* let the database know this module is running, and if the DB says it should be in debug mode */
						n->ModuleDBCheckIn();

						/* run the module */
						if (module == "fileio") {
							moduleFileIO *m = new moduleFileIO(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "export") {
							moduleExport *m = new moduleExport(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "modulemanager") {
							moduleManager *m = new moduleManager(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "import") {
							moduleImport *m = new moduleImport(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "importuploaded") {
							moduleImportUploaded *m = new moduleImportUploaded(n);
							keepLog = m->Run();
							delete m;
						}
                        else if (module == "upload") {
                            moduleUpload *m = new moduleUpload(n);
                            keepLog = m->Run();
                            delete m;
                        }
                        else if (module == "mriqa") {
							moduleMRIQA *m = new moduleMRIQA(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "qc") {
							moduleQC *m = new moduleQC(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "pipeline") {
							modulePipeline *m = new modulePipeline(n);
							keepLog = m->Run();
							delete m;
						}
						else if (module == "minipipeline") {
							moduleMiniPipeline *m = new moduleMiniPipeline(n);
							keepLog = m->Run();
							delete m;
						}
						else
							n->Print("Unrecognized module [" + module + "]");

						/* always keep the logfile in debug mode */
						if ((n->cfg["debug"].toInt()) || (keepLog))
							keepLog = true;

						n->RemoveLogFile(keepLog);

						/* let the database know this module has stopped running */
						n->ModuleDBCheckOut();
					}

					/* delete the lock file */
					n->DeleteLockFile();
				}
				else
					n->Print(QString("Too many instances [%1] of this module [%2] running already").arg(numlock).arg(module));
			}
			else
				n->Print("This module [" + module + "] is disabled or does not exist");
		}
		else
			n->Print("Unable to connect to database");

		if (!quiet) {
			printf("-------------------------------------------------------------\n");
            printf("----- \033[1mTerminating (NiDB) backend\033[0m ----------------------------\n");
			printf("-------------------------------------------------------------\n");
		}
	}

	delete n;

	/* exit the event loop */
	a.exit();

	/* assume everything is happy! */
	return 0;
}
