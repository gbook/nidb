#include "study.h"
#include <QDebug>
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
		q.prepare("select c.uid, a.study_num, b.project_id, b.enrollment_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = :studyid");
		q.bindValue(":studyid", studyid);
		n->SQLQuery(q, "study->LoadStudyInfo");
		if (q.size() < 1) {
			msgs << "Query returned no results. Possibly invalid study ID or recently deleted?";
			isValid = false;
		}
		else {
			uid = q.value("uid").toString().trimmed();
			studynum = q.value("study_num").toInt();
			projectid = q.value("project_id").toInt();
			enrollmentid = q.value("enrollment_id").toInt();

			/* check to see if anything isn't valid or is blank */
			if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; isValid = false; }
			if (uid == "") { msgs << "uid was blank"; isValid = false; }
			if (studynum < 1) { msgs << "studynum is not valid"; isValid = false; }

			studypath = QString("%1/%2/%3").arg(n->cfg["archivedir"]).arg(uid).arg(studynum);

			QDir d(studypath);
			if (!d.exists()) {
				msgs << QString("Invalid study path [%1]").arg(studypath);
				isValid = false;
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
	output += QString("   projectid: [%1]\n").arg(projectid);
	output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
	output += QString("   isValid: [%1]\n").arg(isValid);
	output += QString("   msg: [%1]\n").arg(msg);
	output += QString("   studypath: [%1]\n").arg(studypath);

	n->WriteLog(output);
}
