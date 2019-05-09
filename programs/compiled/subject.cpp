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
	n->SQLQuery(q, "subject->subject");
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
		n->SQLQuery(q, "subject->LoadSubjectInfo");
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
				msgs << QString("Invalid subject path [%1]").arg(subjectpath);
				isValid = false;
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
