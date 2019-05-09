#ifndef STUDY_H
#define STUDY_H
#include <QString>
#include "nidb.h"


class study
{
public:
	study();
	study(int id, nidb *a);
	nidb *n;

	void PrintStudyInfo();

	int studynum;
	QString uid;
	int studyid;
	int subjectid;
	QString studypath;
	int enrollmentid;
	int projectid;
	QDateTime studydatetime;

	bool isValid = true;
	QString msg;
private:
	void LoadStudyInfo();
};

#endif // STUDY_H
