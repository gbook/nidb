#include "performanceMetric.h"


performanceMetric::performanceMetric()
{
    numSeries = 0;
    numStudies = 0;
    numSubjects = 0;

    numFilesRead = 0;
    numFilesArchived = 0;
    numFilesIgnored = 0;
    numFilesError = 0;

    numBytesRead = 0;
    numBytesArchived = 0;
    numBytesIgnored = 0;
    numBytesError = 0;

    elapsedTime = 0;
}


/* ---------------------------------------------------------- */
/* --------- Start ------------------------------------------ */
/* ---------------------------------------------------------- */
void performanceMetric::Start() {
    start = QDateTime().currentDateTime();
}


/* ---------------------------------------------------------- */
/* --------- End -------------------------------------------- */
/* ---------------------------------------------------------- */
QString performanceMetric::End() {
    end = QDateTime().currentDateTime();

    elapsedTime = start.secsTo(end);
	double n = static_cast<double>(numBytesRead) + 0.0000001;
	double t = static_cast<double>(elapsedTime) + 0.0000001;
    double bytesReadPerSec = n/t;

    QString str = QString("Performance metrics\nElapsed time: %1s").arg(elapsedTime);

    str += QString("\nSubjects [%1]  Studies [%2]  Series [%3]").arg(numSubjects).arg(numStudies).arg(numSeries);
    str += QString("\nFilesRead [%1]  FilesArchived [%2]  FilesIgnored [%3]  FilesError [%4]").arg(numFilesRead).arg(numFilesArchived).arg(numFilesIgnored).arg(numFilesError);
    str += QString("\nRead rate: Bytes/Sec [%1]").arg(bytesReadPerSec);

    return str;
}
