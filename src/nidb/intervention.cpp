/* ------------------------------------------------------------------------------
  NIDB intervention.cpp
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

#include "intervention.h"
#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- intervention ----------------------------------- */
/* ---------------------------------------------------------- */
intervention::intervention(qint64 id, nidb *a)
{
    n = a;
    interventionRowID = id;
    LoadInterventionInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadInterventionInfo --------------------------- */
/* ---------------------------------------------------------- */
void intervention::LoadInterventionInfo() {

    QStringList msgs;

    if (interventionRowID < 1) {
        msgs << "Invalid intervention ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        q.prepare("select * from interventions a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.intervention_id = :interventionid");
        q.bindValue(":interventionid", interventionRowID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid intervention ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();

            dateEnd = q.value("enddate").toDateTime();
            dateStart = q.value("startdate").toDateTime();
            dateRecordCreate = q.value("createdate").toDateTime();
            dateRecordEntry = q.value("entrydate").toDateTime();
            dateRecordModify = q.value("modifydate").toDateTime();
            doseAmount = q.value("doseamount").toString();
            doseDesc = q.value("dosedesc").toString();
            doseFrequency = q.value("dosefrequency").toString();
            doseKey = q.value("dosekey").toString();
            doseUnit = q.value("doseunit").toString();
            interventionName = q.value("intervention_name").toString();
            interventionType = q.value("intervention_type").toChar().toLatin1();
            interventionRowID = q.value("intervention_id").toInt();
            enrollmentRowID = q.value("enrollment_id").toInt();
            frequencyModifier = q.value("frequencymodifier").toString();
            frequencyUnit = q.value("frequencyunit").toString();
            frequencyValue = q.value("frequencyvalue").toString();
            notes = q.value("notes").toString();
            rater = q.value("rater").toString();
            route = q.value("administration_route").toString();
            subjectRowID = q.value("subject_id").toInt();
            uid = q.value("UID").toString();

        }
        isValid = true;
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintInterventionInfo -------------------------- */
/* ---------------------------------------------------------- */
void intervention::PrintInterventionInfo() {
    QString	output = QString("***** Intervention - [%1] *****\n").arg(interventionRowID);

    output += QString("   dateEnd: [%1]\n").arg(dateEnd.toString());
    output += QString("   dateStart: [%1]\n").arg(dateStart.toString());
    output += QString("   dateRecordCreate: [%1]\n").arg(dateRecordCreate.toString());
    output += QString("   dateRecordEntry: [%1]\n").arg(dateRecordEntry.toString());
    output += QString("   dateRecordModify: [%1]\n").arg(dateRecordModify.toString());
    output += QString("   doseAmount: [%1]\n").arg(doseAmount);
    output += QString("   doseDesc: [%1]\n").arg(doseDesc);
    output += QString("   doseFrequency: [%1]\n").arg(doseFrequency);
    output += QString("   doseKey: [%1]\n").arg(doseKey);
    output += QString("   doseUnit: [%1]\n").arg(doseUnit);
    output += QString("   interventionName: [%1]\n").arg(interventionName);
    output += QString("   interventionType: [%1]\n").arg(interventionType);
    output += QString("   interventionRowID: [%1]\n").arg(interventionRowID);
    output += QString("   enrollmentRowID: [%1]\n").arg(enrollmentRowID);
    output += QString("   frequencyModifier: [%1]\n").arg(frequencyModifier);
    output += QString("   frequencyUnit: [%1]\n").arg(frequencyUnit);
    output += QString("   frequencyValue: [%1]\n").arg(frequencyValue);
    output += QString("   notes: [%1]\n").arg(notes);
    output += QString("   rater: [%1]\n").arg(rater);
    output += QString("   route: [%1]\n").arg(route);
    output += QString("   subjectRowID: [%1]\n").arg(subjectRowID);
    output += QString("   uid: [%1]\n").arg(uid);

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelIntervention intervention::GetSquirrelObject(QString databaseUUID) {
    squirrelIntervention sqrl(databaseUUID);

    sqrl.DateEnd = dateEnd;
    sqrl.DateRecordEntry = dateRecordEntry;
    sqrl.DateStart = dateStart;
    sqrl.Description = doseDesc;
    sqrl.DoseAmount = doseAmount.toDouble();
    sqrl.DoseFrequency = doseFrequency;
    sqrl.DoseKey = doseKey;
    sqrl.DoseString = doseDesc;
    sqrl.DoseUnit = doseUnit;
    sqrl.InterventionClass = interventionType;
    sqrl.InterventionName = interventionName;
    sqrl.Notes = notes;
    sqrl.Rater = rater;
    sqrl.AdministrationRoute = route;

    return sqrl;
}
