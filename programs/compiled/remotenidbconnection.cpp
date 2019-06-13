/* ------------------------------------------------------------------------------
  NIDB remotenidbconnection.cpp
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

#include "remotenidbconnection.h"
#include <QDebug>
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- remoteNiDBConnection --------------------------- */
/* ---------------------------------------------------------- */
remoteNiDBConnection::remoteNiDBConnection(int id, nidb *a)
{
	n = a;
	connid = id;
	LoadConnection();

}


/* ---------------------------------------------------------- */
/* --------- LoadConnection --------------------------------- */
/* ---------------------------------------------------------- */
void remoteNiDBConnection::LoadConnection() {

	QStringList msgs;

	if (connid < 1) {
		msgs << "Invalid connection ID";
		isValid = false;
	}
	else {
		QSqlQuery q;
		q.prepare("select * from remote_connections where remoteconn_id = :connid");
		q.bindValue(":connid", connid);
		n->SQLQuery(q, "study->LoadConnection", true);
		if (q.size() < 1) {
			msgs << "Query returned no results. Possibly invalid connection ID?";
			isValid = false;
		}
		else {
			q.first();

			QString server = q.value("remote_server").toString().trimmed();
			QString username = q.value("remote_username").toString().trimmed();
			QString password = q.value("remote_password").toString().trimmed();
			int instanceid = q.value("remote_instanceid").toInt();
			int projectid = q.value("remote_projectid").toInt();
			int siteid = q.value("remote_siteid").toInt();

			/* check to see if anything isn't valid or is blank */
			if (server == "") { msgs << "server was blank"; isValid = false; }
			if (username == "") { msgs << "username was blank"; isValid = false; }
			if (password == "") { msgs << "password was blank"; isValid = false; }
			if (instanceid < 1) { msgs << "instance id was blank"; isValid = false; }
			if (projectid < 1) { msgs << "project id was blank"; isValid = false; }
			if (siteid < 1) { msgs << "site id was blank"; isValid = false; }
		}
	}
	msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintConnectionInfo ---------------------------- */
/* ---------------------------------------------------------- */
void remoteNiDBConnection::PrintConnectionInfo() {
	QString	output = QString("***** NiDB Remote Connection - [%1] *****\n").arg(connid);

	output += QString("   server: [%1]\n").arg(server);
	output += QString("   username: [%1]\n").arg(username);
	output += QString("   password: [%1]\n").arg(password);
	output += QString("   instanceid: [%1]\n").arg(instanceid);
	output += QString("   projectid: [%1]\n").arg(projectid);
	output += QString("   siteid: [%1]\n").arg(siteid);

	n->WriteLog(output);
}
