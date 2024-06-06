/* ------------------------------------------------------------------------------
  NIDB measure.cpp
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

#include "measure.h"
#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- measure ---------------------------------------- */
/* ---------------------------------------------------------- */
measure::measure(qint64 id, nidb *a)
{
    n = a;
    measureid = id;
    LoadMeasureInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadMeasureInfo -------------------------------- */
/* ---------------------------------------------------------- */
void measure::LoadMeasureInfo() {

    QStringList msgs;

    if (measureid < 1) {
        msgs << "Invalid measure ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        q.prepare("select * from measures a left join measurenames b on a.measurename_id = b.measurename_id left join measureinstruments c on a.instrumentname_id = c.measureinstrument_id left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.measure_id = :measureid");
        q.bindValue(":measureid", measureid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid measure ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();

            dateMeasureComplete = q.value("measure_datecomplete").toDateTime();
            dateMeasureEnd = q.value("measure_enddate").toDateTime();
            dateMeasureStart = q.value("measure_startdate").toDateTime();
            dateRecordCreate = q.value("measure_createdate").toDateTime();
            dateRecordEntry = q.value("measure_entrydate").toDateTime();
            dateRecordModify = q.value("measure_modifydate").toDateTime();
            desc = q.value("measure_desc").toString();
            duration = q.value("measure_duration").toInt();
            enrollmentid = q.value("enrollment_id").toInt();
            instrumentName = q.value("instrument_name").toString();
            instrumentNameID = q.value("instrumentname_id").toInt();
            measureName = q.value("measure_name").toString();
            measureNameID = q.value("measurename_id").toInt();
            measureType = q.value("measure_type").toChar().toLatin1();
            notes = q.value("measure_notes").toString();
            rater = q.value("measure_rater").toString();
            subjectid = q.value("subject_id").toInt();
            uid = q.value("UID").toString();
            value = q.value("measure_value").toString();
            valueNumber = q.value("measure_valuenum").toDouble();
            valueString = q.value("measure_valuestring").toString();

        }
        isValid = true;
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintMeasureInfo -------------------------------- */
/* ---------------------------------------------------------- */
void measure::PrintMeasureInfo() {
    QString	output = QString("***** Measure - [%1] *****\n").arg(measureid);

    output += QString("   dateMeasureComplete: [%1]\n").arg(dateMeasureComplete.toString());
    output += QString("   dateMeasureEnd: [%1]\n").arg(dateMeasureEnd.toString());
    output += QString("   dateMeasureStart: [%1]\n").arg(dateMeasureStart.toString());
    output += QString("   dateRecordCreate: [%1]\n").arg(dateRecordCreate.toString());
    output += QString("   dateRecordEntry: [%1]\n").arg(dateRecordEntry.toString());
    output += QString("   dateRecordModify: [%1]\n").arg(dateRecordModify.toString());
    output += QString("   desc: [%1]\n").arg(desc);
    output += QString("   duration: [%1]\n").arg(duration);
    output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
    output += QString("   instrumentName: [%1]\n").arg(instrumentName);
    output += QString("   instrumentNameID: [%1]\n").arg(instrumentNameID);
    output += QString("   measureName: [%1]\n").arg(measureName);
    output += QString("   measureNameID: [%1]\n").arg(measureNameID);
    output += QString("   measureType: [%1]\n").arg(measureType);
    output += QString("   measureid: [%1]\n").arg(measureid);
    output += QString("   notes: [%1]\n").arg(notes);
    output += QString("   rater: [%1]\n").arg(rater);
    output += QString("   subjectid: [%1]\n").arg(subjectid);
    output += QString("   uid: [%1]\n").arg(uid);
    output += QString("   value: [%1]\n").arg(value);
    output += QString("   valueNumber: [%1]\n").arg(valueNumber);
    output += QString("   valueString: [%1]\n").arg(valueString);

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelMeasure measure::GetSquirrelObject() {
    squirrelMeasure sqrl;

    sqrl.DateEnd = dateMeasureEnd;
    sqrl.DateRecordCreate = dateRecordCreate;
    sqrl.DateRecordEntry = dateRecordEntry;
    sqrl.DateRecordModify = dateRecordModify;
    sqrl.DateStart = dateMeasureStart;
    sqrl.Description = desc;
    sqrl.Duration = duration;
    sqrl.InstrumentName = instrumentName;
    sqrl.MeasureName = measureName;
    sqrl.Notes = notes;
    sqrl.Rater = rater;

    if (value != "")
        sqrl.Value = value;
    else if (measureType == 'n')
        sqrl.Value = QString("%1").arg(valueNumber);
    else
        sqrl.Value = valueString;

    return sqrl;
}
