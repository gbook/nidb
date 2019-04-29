#include <QCoreApplication>
#include <QDebug>
#include "nidb.h"
#include "moduleFileIO.h"
#include "moduleExport.h"

int main(int argc, char *argv[])
{
    QCoreApplication a(argc, argv);

    QString module = argv[1];

	qDebug() << "Initializating Neuroinformatics Database (NiDB) backend, with module [" << module << "]";

	nidb *n = new nidb(module);

	n->DatabaseConnect();

	int numlock = n->CheckNumLockFiles();

    if (numlock < 3) {
		if (n->CreateLockFile()) {
			/* check if this module should be running now or not */
			if (n->ModuleCheckIfActive()) {

				/* let the database know this module is running */
				n->ModuleDBCheckIn();

				bool keepLog = false;
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

	exit(0);

	//return a.exec();
}
