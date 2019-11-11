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

	int InsertMeasure(int enrollmentID, QString measureName, QString value, QString instrument, QDateTime startDate, QDateTime endDate, int duration);
	int InsertVital(int enrollmentID, QString vitalName, QString value, QString notes, QString vitalType, QDateTime vitalDate);
	int InsertDrug(int enrollmentID, QDateTime startDate, QDateTime endDate, QString doseAmount, QString doseFreq, QString route, QString drugName, QString drugType, QString doseUnit, QString doseFreqModifier, double doseFreqValue, QString doseFreqUnit);
};

#endif // MODULEMINIPIPELINE_H
