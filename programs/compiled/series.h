#ifndef SERIES_H
#define SERIES_H
#include <QString>
#include "nidb.h"


class series
{
public:
	series();
	series(int id, QString m, nidb *a);
	nidb *n;

	void PrintSeriesInfo();

	QString modality;
	QString uid;
	int studynum;
	int seriesnum;
	int subjectid;
	int studyid;
	int seriesid;
	QString seriespath;
	QString datapath;
	QString datatype;
	int enrollmentid;
	int projectid;

	bool isValid = true;
	QString msg;
private:
	void LoadSeriesInfo();
};

#endif // SERIES_H
