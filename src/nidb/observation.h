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
    observation(qint64 id, nidb *a);
    nidb *n;

    void PrintObservationInfo();
    squirrelObservation GetSquirrelObject(QString databaseUUID);

    QDateTime dateObservationComplete;
    QDateTime dateObservationEnd;
    QDateTime dateObservationStart;
    QDateTime dateRecordCreate;
    QDateTime dateRecordEntry;
    QDateTime dateRecordModify;
    QString desc;
    QString instrumentName;
    QString observationName;
    QString notes;
    QString rater;
    QString uid;
    QString value;
    //QString valueString;
    //char observationType;
    //double valueNumber;
    int duration;
    int enrollmentid;
    //int instrumentNameID;
    //int observationNameID;
    int observationid;
    int subjectid;

    bool isValid = true;
    QString msg;

private:
    void LoadObservationInfo();
};

#endif // OBSERVATION_H
