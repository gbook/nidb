/* ------------------------------------------------------------------------------
  NIDB observation.h
  Copyright (C) 2004 - 2025
  Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
  Olin Neuropsychiatry Research Center, Hartford Hospital
  ------------------------------------------------------------------------------
  GPLv3 License:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  ------------------------------------------------------------------------------ */

#ifndef OBSERVATION_H
#define OBSERVATION_H
#include <QString>
#include "nidb.h"
#include "squirrelObservation.h"

class observation
{
public:
    observation();
    observation(qint64 id, nidb *a, bool loadLinked = false);
    nidb *n;

    void PrintObservationInfo();
    squirrelObservation GetSquirrelObject(QString databaseUUID);
    bool AddToDatabase(); /* store the observation to the database */

    /* data from 'observations' table */
    QDateTime dateObservationEnd;
    QDateTime dateObservationStart;
    QDateTime dateRecordCreate;
    QDateTime dateRecordEntry;
    QDateTime dateRecordModify;
    QString observationDescription;
    QString observationInstrument;
    QString observationName;
    QString observationNotes;
    QString observationRater;
    QString observationValue;
    QString subjectUID;
    int enrollmentRowID = -1;
    int instrumentItemRowID = -1;
    int observationDuration = 0;
    int observationRowID = -1;
    int projectRowID = -1;
    int remoteBatchRowID = -1;
    int subjectRowID = -1;
    int surveyRowID = -1;

    bool hasLinkedInstrument = false;
    bool hasLinkedInstrumentItem = false;
    bool hasMetadata = false;
    bool hasSurvey = false;

    /* instruments */
    QString linkedInstrumentName;
    QString linkedInstrumentNotes;

    /* instrument item */
    QString linkedInstrumentItemName;
    QString linkedInstrumentItemType; /* enum, int, double, string, timeseries */
    QString linkedInstrumentItemNotes;
    int linkedInstrumentItemOrder;
    QMap<QString, QString> metadata;
    QMap<int, QString> valueMap;

    /* survey information */
    QDateTime linkedSurveyStartDate;
    QDateTime linkedSurveyEndDate;
    QString linkedSurveyNotes;
    QString linkedSurveyVisit;
    QString linkedSurveyExperimenter;
    QString linkedSurveyRater;
    QDateTime linkedSurveyEntryDate;

    /* timeseries containers */
    QMap<QDateTime, int> timeseriesInt;
    QMap<QDateTime, double> timeseriesDouble;
    QMap<QDateTime, QString> timeseriesString;

    bool isValid = true;
    QString msg;

private:
    void LoadObservationInfo();
    bool loadLinkedData = false;
};

#endif // OBSERVATION_H
