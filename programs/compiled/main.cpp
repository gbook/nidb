/* ------------------------------------------------------------------------------
  NIDB main.cpp
  Copyright (C) 2004 - 2019
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
#include "moduleMRIQA.h"
#include "moduleQC.h"
#include "modulePipeline.h"
#include <iostream>
#include <smtp/SmtpMime>


/* ---------------------------------------------------------- */
/* --------- main ------------------------------------------- */
/* ---------------------------------------------------------- */
int main(int argc, char *argv[])
{
	QCoreApplication a(argc, argv);

    QString module = argv[1];

	module = module.trimmed();
	if ((module != "export") && (module != "fileio") && (module != "qc") && (module != "mriqa") && (module != "modulemanager") && (module != "import") && (module != "pipeline") && (module != "importuploaded")) {
		printf("Module parameter missing or incorrect\n\nUsage:\n  nidb <module> <debug>\n\nAvailable modules:  import  export  fileio  mriqa  qc  modulemanager  importuploaded  pipeline\nAdd second parameter of 'debug' to display verbose information and retain the log file\n\n");
		return 0;
	}

	printf("\n\n-------------------------------------------------------------\n");
	printf("----- Starting Neuroinformatics Database (NiDB) backend -----\n");
	printf("-------------------------------------------------------------\n");

	/* load the config file and connect to the database */
	nidb *n = new nidb(module);
	n->DatabaseConnect();

	bool keepLog = false;
	if (argc == 3)
		if (QString(argv[2]) == "debug")
			n->cfg["debug"] = "1";

	if (n->cfg["debug"].toInt())
		printf("------------------------- DEBUG MODE ------------------------\n");

	/* check if this module should be running now or not */
	if (n->ModuleCheckIfActive()) {
		int numlock = n->CheckNumLockFiles();
		if (numlock < n->GetNumThreads()) {
			if (n->CreateLockFile()) {

				n->CreateLogFile();

				/* let the database know this module is running */
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
				else
					n->Print("Unrecognized module [" + module + "]");

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

	delete n;

	printf("-------------------------------------------------------------\n");
	printf("----- Terminating (NiDB) backend ----------------------------\n");
	printf("-------------------------------------------------------------\n");

	/* exit the event loop */
	a.exit();

	/* assume everything is happy! */
	return 0;
}
