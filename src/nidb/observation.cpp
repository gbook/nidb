/* ------------------------------------------------------------------------------
  NIDB observation.cpp
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

#include "observation.h"
#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- observation ------------------------------------ */
/* ---------------------------------------------------------- */
observation::observation(qint64 id, nidb *a)
{
    n = a;
    observationid = id;
    LoadObservationInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadObservationInfo ---------------------------- */
/* ---------------------------------------------------------- */
void observation::LoadObservationInfo() {

    QStringList msgs;

    if (observationid < 1) {
        msgs << "Invalid observation ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        q.prepare("select * from observations a left join observationnames b on a.observationname_id = b.observationname_id left join observationinstruments c on a.instrumentname_id = c.observationinstrument_id left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.observation_id = :observationid");
        q.bindValue(":observationid", observationid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid observation ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();

            //dateObservationComplete = q.value("observation_datecomplete").toDateTime();
            dateObservationEnd = q.value("observation_enddate").toDateTime();
            dateObservationStart = q.value("observation_startdate").toDateTime();
            dateRecordCreate = q.value("observation_createdate").toDateTime();
            dateRecordEntry = q.value("observation_entrydate").toDateTime();
            dateRecordModify = q.value("observation_modifydate").toDateTime();
            desc = q.value("observation_desc").toString();
            duration = q.value("observation_duration").toInt();
            enrollmentid = q.value("enrollment_id").toInt();
            instrumentName = q.value("instrument_name").toString();
            //instrumentNameID = q.value("instrumentname_id").toInt();
            observationName = q.value("observation_name").toString();
            //observationNameID = q.value("observationname_id").toInt();
            //observationType = q.value("observation_type").toChar().toLatin1();
            notes = q.value("observation_notes").toString();
            rater = q.value("observation_rater").toString();
            subjectid = q.value("subject_id").toInt();
            uid = q.value("UID").toString();
            value = q.value("observation_value").toString();
            //valueNumber = q.value("observation_valuenum").toDouble();
            //valueString = q.value("observation_valuestring").toString();

        }
        isValid = true;
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintObservationInfo --------------------------- */
/* ---------------------------------------------------------- */
void observation::PrintObservationInfo() {
    QString	output = QString("***** Observation - [%1] *****\n").arg(observationid);

    //output += QString("   dateObservationComplete: [%1]\n").arg(dateObservationComplete.toString());
    output += QString("   dateObservationEnd: [%1]\n").arg(dateObservationEnd.toString());
    output += QString("   dateObservationStart: [%1]\n").arg(dateObservationStart.toString());
    output += QString("   dateRecordCreate: [%1]\n").arg(dateRecordCreate.toString());
    output += QString("   dateRecordEntry: [%1]\n").arg(dateRecordEntry.toString());
    output += QString("   dateRecordModify: [%1]\n").arg(dateRecordModify.toString());
    output += QString("   desc: [%1]\n").arg(desc);
    output += QString("   duration: [%1]\n").arg(duration);
    output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
    output += QString("   instrumentName: [%1]\n").arg(instrumentName);
    //output += QString("   instrumentNameID: [%1]\n").arg(instrumentNameID);
    output += QString("   observationName: [%1]\n").arg(observationName);
    //output += QString("   observationNameID: [%1]\n").arg(observationNameID);
    //output += QString("   observationType: [%1]\n").arg(observationType);
    output += QString("   observationid: [%1]\n").arg(observationid);
    output += QString("   notes: [%1]\n").arg(notes);
    output += QString("   rater: [%1]\n").arg(rater);
    output += QString("   subjectid: [%1]\n").arg(subjectid);
    output += QString("   uid: [%1]\n").arg(uid);
    output += QString("   value: [%1]\n").arg(value);
    //output += QString("   valueNumber: [%1]\n").arg(valueNumber);
    //output += QString("   valueString: [%1]\n").arg(valueString);

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelObservation observation::GetSquirrelObject(QString databaseUUID) {
    squirrelObservation sqrl(databaseUUID);

    sqrl.DateEnd = dateObservationEnd;
    sqrl.DateRecordCreate = dateRecordCreate;
    sqrl.DateRecordEntry = dateRecordEntry;
    sqrl.DateRecordModify = dateRecordModify;
    sqrl.DateStart = dateObservationStart;
    sqrl.Description = desc;
    sqrl.Duration = duration;
    sqrl.InstrumentName = instrumentName;
    sqrl.ObservationName = observationName;
    sqrl.Notes = notes;
    sqrl.Rater = rater;

    //if (value != "")
        sqrl.Value = value;
    //else if (observationType == 'n')
    //    sqrl.Value = QString("%1").arg(valueNumber);
    //else
    //    sqrl.Value = valueString;

    return sqrl;
}
