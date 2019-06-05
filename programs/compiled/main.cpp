#include <QCoreApplication>
#include <QDebug>
#include "nidb.h"
#include "moduleFileIO.h"
#include "moduleExport.h"
#include "moduleManager.h"
#include "moduleImport.h"
#include <iostream>
#include <smtp/SmtpMime>


/* ---------------------------------------------------------- */
/* --------- main ------------------------------------------- */
/* ---------------------------------------------------------- */
int main(int argc, char *argv[])
{
	QCoreApplication a(argc, argv);

    QString module = argv[1];
	bool keepLog = false;
	if (argc == 3)
		if (strcmp(argv[2], "debug"))
			keepLog = true;

	module = module.trimmed();
	if ((module == "") || ((module != "export") && (module != "fileio") && (module != "mriqa") && (module != "export") && (module != "modulemanager") && (module != "import") && (module != "pipeline") && (module != "importuploaded"))) {
		printf("Module parameter missing or incorrect\n\nUsage:\n  nidb <module> <debug>\n\nAvailable modules: export fileio mriqa modulemanager import pipeline\nAdd second parameter of 'debug' to display verbose information and retain the log file\n\n");
		return 0;
	}

	printf("-------------------------------------------------------------\n");
	printf("----- Starting Neuroinformatics Database (NiDB) backend -----\n");
	printf("-------------------------------------------------------------\n");

	nidb *n = new nidb(module);

	n->DatabaseConnect();

	/* check if this module should be running now or not */
	if (n->ModuleCheckIfActive()) {
		int numlock = n->CheckNumLockFiles();
		if (numlock <= n->GetNumThreads()) {
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
				else {
					n->Print("Unrecognized module [" + module + "]");
				}

				n->RemoveLogFile(keepLog);

				/* let the database know this module has stopped running */
				n->ModuleDBCheckOut();
			}
			else {
				//n->Print("Unable to create lock file!");
			}

			/* delete the lock file */
			n->DeleteLockFile();
		}
		else {
			n->Print(QString("Too many instances [%1] of this module [%2] running already").arg(numlock).arg(module));
		}
    }
	else {
		n->Print("This module [" + module + "] is disabled or does not exist");
	}

	delete n;

	printf("-------------------------------------------------------------\n");
	printf("----- Terminating (NiDB) backend ----------------------------\n");
	printf("-------------------------------------------------------------\n");

	/* exit the event loop */
	a.exit();

	/* assume everything is happy! */
	return 0;
}
