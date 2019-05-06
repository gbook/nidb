#include "moduleManager.h"
#include <QDebug>


/* ---------------------------------------------------------- */
/* --------- moduleManager ---------------------------------- */
/* ---------------------------------------------------------- */
moduleManager::moduleManager(nidb *a)
{
	n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleManager --------------------------------- */
/* ---------------------------------------------------------- */
moduleManager::~moduleManager()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleManager::Run() {
	qDebug() << "Entering the fileio module";

	/* get list of modules with a last checkin older than 1 hours */
	QSqlQuery q;
	q.prepare("select * from module_procs where last_checkin < date_sub(now(), interval 1 hour) or last_checkin is null");
	n->SQLQuery(q, "moduleManager->Run");

	if (q.size() > 0) {
		while (q.next()) {
			QString modulename = q.value("module_name").toString();
			int pid = q.value("process_id").toInt();
			QString lastcheckin = q.value("last_checkin").toString();

			QString lockfile = QString("%1/lock/%2.%3").arg(n->cfg["scriptdir"]).arg(modulename).arg(pid);

			qDebug() << "Deleting [" << lockfile << "] last checked in on [" << lastcheckin << "]";

			QFile f(lockfile);
			if (f.remove())
				n->WriteLog(QString("Lockfile [" + lockfile + "] deleted"));
			else
				n->WriteLog(QString("Lockfile [" + lockfile + "] NOT deleted"));

			QSqlQuery q2;
			q2.prepare("delete from module_procs where module_name = :modulename and process_id = :pid");
			q2.bindValue(":modulename", modulename);
			q2.bindValue(":pid", pid);
			n->SQLQuery(q2, "moduleManager->Run");
		}
	}
	else {
		n->WriteLog("Found no lock files to delete");
	}
	return 1;
}
