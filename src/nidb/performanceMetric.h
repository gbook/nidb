#ifndef PERFORMANCEMETRIC_H
#define PERFORMANCEMETRIC_H
#include "nidb.h"

class performanceMetric
{
public:
    performanceMetric();

    void Start();
    QString End();

    int numSeries;
    int numStudies;
    int numSubjects;
    int numFilesRead;
    int numFilesArchived;
    int numFilesIgnored;
    int numFilesError;
    qint64 numBytesRead;
    qint64 numBytesArchived;
    qint64 numBytesIgnored;
    qint64 numBytesError;

    qint64 elapsedTime;

private:
    QDateTime start;
    QDateTime end;
};

#endif // PERFORMANCEMETRIC_H
