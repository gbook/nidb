#ifndef MODULEFILEIO_H
#define MODULEFILEIO_H
#include "nidb.h"
#include "analysis.h"
#include "subject.h"
#include "study.h"
#include "series.h"

class moduleFileIO
{
public:
	moduleFileIO(nidb *n);
    ~moduleFileIO();
	int Run();
	bool RecheckSuccess(int analysisid, QString &msg);
	bool CreateLinks(int analysisid, QString destination, QString &msg);
	QString GetAnalyisRootPath(int analysisid, QString &msg);
	bool CopyAnalysis(int analysisid, QString destination, QString &msg);
	bool DeleteAnalysis(int analysisid, QString &msg);
	bool DeletePipeline(int pipelineid, QString &msg);
	bool DeleteSubject(int subjectid, QString &msg);
	bool DeleteStudy(int subjectid, QString &msg);
	bool DeleteSeries(int seriesid, QString modality, QString &msg);
	bool RearchiveStudy(int studyid, bool matchidonly, QString &msg);
	bool RearchiveSubject(int studyid, bool matchidonly, int projectid, QString &msg);

private:
	nidb *n;
};

#endif // MODULEFILEIO_H
