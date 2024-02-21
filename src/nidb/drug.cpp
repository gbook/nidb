/* ------------------------------------------------------------------------------
  NIDB drug.cpp
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

#include "drug.h"
#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- drug ---------------------------------------- */
/* ---------------------------------------------------------- */
drug::drug(qint64 id, nidb *a)
{
    n = a;
    drugid = id;
    LoadDrugInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadDrugInfo -------------------------------- */
/* ---------------------------------------------------------- */
void drug::LoadDrugInfo() {

    QStringList msgs;

    if (drugid < 1) {
        msgs << "Invalid drug ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        q.prepare("select * from drugs a left join drugnames b on a.drugname_id = b.drugname_id left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.drug_id = :drugid");
        q.bindValue(":drugid", drugid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid drug ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();

            dateDrugEnd = q.value("drug_enddate").toDateTime();
            dateDrugStart = q.value("drug_startdate").toDateTime();
            dateRecordCreate = q.value("drug_createdate").toDateTime();
            dateRecordEntry = q.value("drug_entrydate").toDateTime();
            dateRecordModify = q.value("drug_modifydate").toDateTime();
            doseAmount = q.value("drug_doseamount").toString();
            doseDesc = q.value("drug_dosedesc").toString();
            doseFrequency = q.value("drug_dosefrequency").toString();
            doseKey = q.value("drug_dosekey").toString();
            doseUnit = q.value("drug_doseunit").toString();
            drugName = q.value("drug_name").toString();
            drugNameID = q.value("drugname_id").toInt();
            drugType = q.value("drug_type").toChar().toLatin1();
            drugid = q.value("drug_id").toInt();
            enrollmentid = q.value("enrollment_id").toInt();
            frequencyModifier = q.value("drug_frequencymodifier").toString();
            frequencyUnit = q.value("drug_frequencyunit").toString();
            frequencyValue = q.value("drug_frequencyvalue").toString();
            notes = q.value("drug_notes").toString();
            rater = q.value("drug_rater").toString();
            route = q.value("drug_route").toString();
            subjectid = q.value("subject_id").toInt();
            uid = q.value("UID").toString();

        }
        isValid = true;
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintDrugInfo -------------------------------- */
/* ---------------------------------------------------------- */
void drug::PrintDrugInfo() {
    QString	output = QString("***** Drug - [%1] *****\n").arg(drugid);

    output += QString("   dateDrugEnd: [%1]\n").arg(dateDrugEnd.toString());
    output += QString("   dateDrugStart: [%1]\n").arg(dateDrugStart.toString());
    output += QString("   dateRecordCreate: [%1]\n").arg(dateRecordCreate.toString());
    output += QString("   dateRecordEntry: [%1]\n").arg(dateRecordEntry.toString());
    output += QString("   dateRecordModify: [%1]\n").arg(dateRecordModify.toString());
    output += QString("   doseAmount: [%1]\n").arg(doseAmount);
    output += QString("   doseDesc: [%1]\n").arg(doseDesc);
    output += QString("   doseFrequency: [%1]\n").arg(doseFrequency);
    output += QString("   doseKey: [%1]\n").arg(doseKey);
    output += QString("   doseUnit: [%1]\n").arg(doseUnit);
    output += QString("   drugName: [%1]\n").arg(drugName);
    output += QString("   drugNameID: [%1]\n").arg(drugNameID);
    output += QString("   drugType: [%1]\n").arg(drugType);
    output += QString("   drugid: [%1]\n").arg(drugid);
    output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
    output += QString("   frequencyModifier: [%1]\n").arg(frequencyModifier);
    output += QString("   frequencyUnit: [%1]\n").arg(frequencyUnit);
    output += QString("   frequencyValue: [%1]\n").arg(frequencyValue);
    output += QString("   notes: [%1]\n").arg(notes);
    output += QString("   rater: [%1]\n").arg(rater);
    output += QString("   route: [%1]\n").arg(route);
    output += QString("   subjectid: [%1]\n").arg(subjectid);
    output += QString("   uid: [%1]\n").arg(uid);

    n->WriteLog(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelDrug drug::GetSquirrelObject() {
    squirrelDrug sqrl;

    sqrl.dateEnd = dateDrugEnd;
    sqrl.dateRecordEntry = dateRecordEntry;
    sqrl.dateStart = dateDrugStart;
    sqrl.description = doseDesc;
    sqrl.doseAmount = doseAmount.toDouble();
    sqrl.doseFrequency = doseFrequency;
    sqrl.doseKey = doseKey;
    sqrl.doseString = doseDesc;
    sqrl.doseUnit = doseUnit;
    sqrl.drugClass = drugType;
    sqrl.drugName = drugName;
    sqrl.frequencyModifier = frequencyModifier;
    sqrl.frequencyUnit = frequencyUnit;
    sqrl.frequencyValue = frequencyValue.toDouble();
    sqrl.notes = notes;
    sqrl.rater = rater;
    sqrl.route = route;

    return sqrl;
}
