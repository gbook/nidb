#ifndef SUBJECT_H
#define SUBJECT_H
#include <QString>
#include "nidb.h"


class subject
{
public:
	subject();
	subject(int id, nidb *a);
	nidb *n;

	void PrintSubjectInfo();

	int subjectid;
	QString uid;
	QStringList altuids;
	QString subjectpath;

	bool isValid = true;
	QString msg;
private:
	void LoadSubjectInfo();
};

#endif // SUBJECT_H
