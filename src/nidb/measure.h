/* ------------------------------------------------------------------------------
  NIDB measure.h
  Copyright (C) 2004 - 2024
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

#ifndef MEASURE_H
#define MEASURE_H
#include <QString>
#include "nidb.h"
#include "squirrelObservation.h"

class measure
{
public:
    measure();
    measure(qint64 id, nidb *a);
    nidb *n;

    void PrintMeasureInfo();
    squirrelObservation GetSquirrelObject();

    QDateTime dateMeasureComplete;
    QDateTime dateMeasureEnd;
    QDateTime dateMeasureStart;
    QDateTime dateRecordCreate;
    QDateTime dateRecordEntry;
    QDateTime dateRecordModify;
    QString desc;
    QString instrumentName;
    QString measureName;
    QString notes;
    QString rater;
    QString uid;
    QString value;
    QString valueString;
    char measureType;
    double valueNumber;
    int duration;
    int enrollmentid;
    int instrumentNameID;
    int measureNameID;
    int measureid;
    int subjectid;

    bool isValid = true;
    QString msg;

private:
    void LoadMeasureInfo();
};

#endif // MEASURE_H
