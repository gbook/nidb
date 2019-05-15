#include <QCoreApplication>
#include <QDebug>
#include "nidb.h"
#include "moduleFileIO.h"
#include "moduleExport.h"
#include "moduleManager.h"
#include <iostream>
#include <smtp/SmtpMime>

int main(int argc, char *argv[])
{
	QCoreApplication a(argc, argv);

    QString module = argv[1];
	bool keepLog = false;
	if (argc == 3)
		if (strcmp(argv[2], "debug"))
			keepLog = true;

	if (module.trimmed() == "") {
		printf("Module parameter missing or incorrect\n\nUsage:\n  nidb <module> <debug>\n\nAvailable modules: export fileio mriqa modulemanager import pipeline\nAdd second parameter of 'debug' to display verbose information and retain the log file\n\n");
		return 0;
	}

	qDebug() << "Initializating Neuroinformatics Database (NiDB) backend, with module [" << module << "]";
	nidb *n = new nidb(module);

	n->DatabaseConnect();

	int numlock = n->CheckNumLockFiles();

	if (numlock <= n->GetNumThreads()) {
		if (n->CreateLockFile()) {
			/* check if this module should be running now or not */
			if (n->ModuleCheckIfActive()) {

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
				else {
					qDebug() << "Unrecognized module [" << module << "]";
				}

				n->RemoveLogFile(keepLog);

				/* let the database know this module has stopped running */
				n->ModuleDBCheckOut();
			}
			else {
				qDebug() << "This module is disabled and should not be running";
			}

			/* delete the lock file */
			n->DeleteLockFile();
		}
    }
    else {
		qDebug() << "Too many instances [" << numlock << "] of this module [" << module << "] running already";
    }

	delete n;

    qDebug() << "Terminating NiDB with the [" << module << "] module";

	/* exit the event loop */
	a.exit();

	/* assume everything is happy! */
	return 0;
}
