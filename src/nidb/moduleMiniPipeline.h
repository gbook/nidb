#ifndef MODULEMINIPIPELINE_H
#define MODULEMINIPIPELINE_H

#include "nidb.h"


class moduleMiniPipeline
{
public:
    moduleMiniPipeline();
    moduleMiniPipeline(nidb *n);
    ~moduleMiniPipeline();

    int Run();
    QList<int> GetMPJobList();

private:
    nidb *n;

	qint64 CopyAllSeriesData(QString modality, qint64 seriesid, QString destination, QString &msg, bool createDestDir=true, bool rwPerms=true);
	bool InsertMeasure(qint64 enrollmentid, qint64 studyid, qint64 seriesid, QString measureName, QString value, QString instrument, QDateTime startDate, QDateTime endDate, int duration, QString rater, int &numInserts, QString &msg);
	int InsertVital(qint64 enrollmentID, QString vitalName, QString value, QString notes, QString vitalType, QDateTime vitalStartDate, QDateTime vitalEndDate, int duration);
	int InsertDrug(qint64 enrollmentID, QDateTime startDate, QDateTime endDate, QString doseAmount, QString doseFreq, QString route, QString drugName, QString drugType, QString doseUnit, QString doseFreqModifier, double doseFreqValue, QString doseFreqUnit);
    void AppendMiniPipelineLog(QString log, int jobid);
};

#endif // MODULEMINIPIPELINE_H
