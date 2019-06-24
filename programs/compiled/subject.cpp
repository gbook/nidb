/* ------------------------------------------------------------------------------
  NIDB subject.cpp
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

#include "subject.h"
#include <QDebug>
#include <QSqlQuery>

subject::subject(int id, nidb *a)
{
	n = a;
	subjectid = id;
	LoadSubjectInfo();
}


subject::subject(QString uid, nidb *a)
{
	n = a;

	QSqlQuery q;
	q.prepare("select subject_id from subjects where uid = :uid");
	q.bindValue(":uid", uid);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() < 1) {
		msg = "UID [" + uid + "] could not be found";
		isValid = false;
	}
	else {
		q.first();
		subjectid = q.value("subject_id").toInt();
	}

	LoadSubjectInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadSubjectInfo -------------------------------- */
/* ---------------------------------------------------------- */
void subject::LoadSubjectInfo() {

	QStringList msgs;

	if (subjectid < 1) {
		msgs << "Invalid subject ID";
		isValid = false;
	}
	else {
		/* get the path to the analysisroot */
		QSqlQuery q;
		q.prepare("select uid from subjects where subject_id = :subjectid");
		q.bindValue(":subjectid", subjectid);
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() < 1) {
			msgs << "Query returned no results. Possibly invalid subject ID or recently deleted?";
			isValid = false;
		}
		else {
			q.first();
			uid = q.value("uid").toString().trimmed();

			/* check to see if anything isn't valid or is blank */
			if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; isValid = false; }
			if (uid == "") { msgs << "uid was blank"; isValid = false; }

			subjectpath = QString("%1/%2").arg(n->cfg["archivedir"]).arg(uid);

			QDir d(subjectpath);
			if (!d.exists()) {
				msgs << QString("Subject path does not exist [%1]").arg(subjectpath);
				subjectpath = "";
			}
		}
	}
	msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintSubjectInfo ------------------------------- */
/* ---------------------------------------------------------- */
void subject::PrintSubjectInfo() {
	QString	output = QString("***** Subject - [%1] *****\n").arg(subjectid);

	output += QString("   uid: [%1]\n").arg(uid);
	output += QString("   subjectid: [%1]\n").arg(subjectid);
	output += QString("   isValid: [%1]\n").arg(isValid);
	output += QString("   msg: [%1]\n").arg(msg);
	output += QString("   analysispath: [%1]\n").arg(subjectpath);

	n->WriteLog(output);
}
