/* ------------------------------------------------------------------------------
  NIDB study.cpp
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

#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
study::study(int id, nidb *a)
{
	n = a;
	studyid = id;
	LoadStudyInfo();
	//PrintStudyInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadStudyInfo ---------------------------------- */
/* ---------------------------------------------------------- */
void study::LoadStudyInfo() {

	QStringList msgs;

	if (studyid < 1) {
		msgs << "Invalid study ID";
		isValid = false;
	}
	else {
		QSqlQuery q;
		q.prepare("select c.uid, a.study_num, b.project_id, b.enrollment_id, a.study_datetime, a.study_modality, a.study_type from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = :studyid");
		q.bindValue(":studyid", studyid);
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() < 1) {
			msgs << "Query returned no results. Possibly invalid study ID or recently deleted?";
			isValid = false;
		}
		else {
			q.first();
			uid = q.value("uid").toString().trimmed();
			studynum = q.value("study_num").toInt();
			projectid = q.value("project_id").toInt();
			enrollmentid = q.value("enrollment_id").toInt();
			studydatetime = q.value("study_datetime").toDateTime();
			modality = q.value("study_modality").toString().trimmed();
			studytype = q.value("study_type").toString().trimmed();

			/* check to see if anything isn't valid or is blank */
			if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; isValid = false; }
			if (uid == "") { msgs << "uid was blank"; isValid = false; }
			if (studynum < 1) { msgs << "studynum is not valid"; isValid = false; }

			studypath = QString("%1/%2/%3").arg(n->cfg["archivedir"]).arg(uid).arg(studynum);

			QDir d(studypath);
			if (d.exists()) {
				msgs << QString("Study path [%1] exists").arg(studypath);
				studyPathExists = true;
			}
			else {
				msgs << QString("Study path [%1] does not exist").arg(studypath);
				studyPathExists = false;
			}
		}
	}
	msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintSubjectInfo ------------------------------- */
/* ---------------------------------------------------------- */
void study::PrintStudyInfo() {
	QString	output = QString("***** Subject - [%1] *****\n").arg(studyid);

	output += QString("   uid: [%1]\n").arg(uid);
	output += QString("   subjectid: [%1]\n").arg(subjectid);
	output += QString("   studyid: [%1]\n").arg(studyid);
	output += QString("   studynum: [%1]\n").arg(studynum);
	output += QString("   studytype: [%1]\n").arg(studytype);
	output += QString("   modality: [%1]\n").arg(modality);
	output += QString("   projectid: [%1]\n").arg(projectid);
	output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
	output += QString("   isValid: [%1]\n").arg(isValid);
	output += QString("   msg: [%1]\n").arg(msg);
	output += QString("   studypath: [%1]\n").arg(studypath);
	output += QString("   studydatetime: [%1]\n").arg(studydatetime.toString("yyyy-MM-dd HH:mm:ss"));

	n->WriteLog(output);
}
