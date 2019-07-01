/* ------------------------------------------------------------------------------
  NIDB moduleManager.cpp
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

#include "moduleManager.h"


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
	n->WriteLog("Entering the fileio module");

	/* get list of modules with a last checkin older than 1 hours */
	QSqlQuery q;
	q.prepare("select * from module_procs where last_checkin < date_sub(now(), interval 1 hour) or last_checkin is null");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

	if (q.size() > 0) {
		while (q.next()) {
			QString modulename = q.value("module_name").toString();
			int pid = q.value("process_id").toInt();
			QString lastcheckin = q.value("last_checkin").toString();

			QString lockfile = QString("%1/lock/%2.%3").arg(n->cfg["scriptdir"]).arg(modulename).arg(pid);

			n->WriteLog("Deleting [" + lockfile + "] last checked in on [" + lastcheckin + "]");

			QFile f(lockfile);
			if (f.remove())
				n->WriteLog(QString("Lockfile [" + lockfile + "] deleted"));
			else
				n->WriteLog(QString("Lockfile [" + lockfile + "] NOT deleted"));

			QSqlQuery q2;
			q2.prepare("delete from module_procs where module_name = :modulename and process_id = :pid");
			q2.bindValue(":modulename", modulename);
			q2.bindValue(":pid", pid);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		}
	}
	else {
		n->WriteLog("Found no lock files to delete");
	}
	return 1;
}
