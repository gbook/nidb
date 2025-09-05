/* ------------------------------------------------------------------------------
  NIDB intervention.h
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

#ifndef INTERVENTION_H
#define INTERVENTION_H
#include <QString>
#include "nidb.h"
#include "squirrelIntervention.h"

/**
 * @brief The intervention class
 */
class intervention
{
public:
    intervention();
    intervention(qint64 id, nidb *a);
    nidb *n;

    void PrintInterventionInfo();
    squirrelIntervention GetSquirrelObject(QString databaseUUID);

    QDateTime dateEnd;
    QDateTime dateStart;
    QDateTime dateRecordCreate;
    QDateTime dateRecordEntry;
    QDateTime dateRecordModify;
    QString doseAmount;
    QString doseDesc;
    QString doseFrequency;
    QString doseKey;
    QString doseUnit;
    QString interventionName;
    QString interventionType;
    QString frequencyModifier;
    QString frequencyUnit;
    QString frequencyValue;
    QString notes;
    QString rater;
    QString route;
    QString uid;
    int interventionRowID;
    int enrollmentRowID;
    int subjectRowID;

    bool isValid = true;
    QString msg;

private:
    void LoadInterventionInfo();
};

#endif // INTERVENTION_H
