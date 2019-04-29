#ifndef ANALYSIS_H
#define ANALYSIS_H
#include <QString>
#include "nidb.h"

class analysis
{
public:
	analysis(int id, nidb *a);
	nidb *n;

	QString analysispath;
	int analysisid;

	int studynum;
	int studyid;
	QString uid;
	int subjectid;

	QString pipelinename;
	int pipelineversion;
	int pipelineid;
	int pipelinelevel;
	QString pipelinedirectory;
	QString pipelinedirstructure;
	int jobid;
	bool isValid = true;
	QString msg;
private:
	void LoadAnalysisInfo();
};

#endif // ANALYSIS_H
