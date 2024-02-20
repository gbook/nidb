/* ------------------------------------------------------------------------------
  NIDB measure.cpp
  Copyright (C) 2004 - 2023
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
        QString sqlstring = QString("select *, b.study_num, d.uid, d.subject_id, c.enrollment_id, b.study_id from measure a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.measure_id = :measureid");
        q.prepare(sqlstring);
        q.bindValue(":measureid", measureid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid measure ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();
            uid = q.value("uid").toString().trimmed().replace('\u0000', "");
            studynum = q.value("study_num").toInt();
            measurenum = q.value("measure_num").toInt();
            desc = q.value("measure_desc").toString().trimmed();
            protocol = q.value("measure_protocol").toString().trimmed();
            datetime = q.value("measure_datetime").toDateTime();
            subjectid = q.value("subject_id").toInt();
            studyid = q.value("study_id").toInt();
            projectid = q.value("project_id").toInt();
            enrollmentid = q.value("enrollment_id").toInt();
            datatype = q.value("data_type").toString().trimmed();
            isderived = q.value("is_derived").toBool();

            /* check to see if anything isn't valid or is blank */
            if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; isValid = false; }
            if (uid == "") { msgs << "uid was blank"; isValid = false; }
            if (studynum < 1) { msgs << "studynum is not valid"; isValid = false; }
            if (measurenum < 0) { msgs << "measurenum is not valid"; isValid = false; }

            measurepath = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(measurenum);
            if (modality == "mr") {
                datapath = measurepath + "/" + datatype;
                behpath = measurepath + "/beh";
            }
            else {
                datapath = measurepath + "/" + modality;
            }

            QDir d(measurepath);
            if (!d.exists()) {
                msgs << QString("Invalid measure path [%1]").arg(measurepath);
                isValid = false;
            }
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

    output += QString("   uid: [%1]\n").arg(uid);
    output += QString("   subjectid: [%1]\n").arg(subjectid);
    output += QString("   studyid: [%1]\n").arg(studyid);
    output += QString("   studynum: [%1]\n").arg(studynum);
    output += QString("   measureid: [%1]\n").arg(measureid);
    output += QString("   measurenum: [%1]\n").arg(measurenum);
    output += QString("   projectid: [%1]\n").arg(projectid);
    output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
    output += QString("   datatype: [%1]\n").arg(datatype);
    output += QString("   modality: [%1]\n").arg(modality);
    output += QString("   isDerived: [%1]\n").arg(isderived);
    output += QString("   isValid: [%1]\n").arg(isValid);
    output += QString("   msg: [%1]\n").arg(msg);
    output += QString("   measurepath: [%1]\n").arg(measurepath);
    output += QString("   datapath: [%1]\n").arg(datapath);

    n->WriteLog(output);
}


/* ---------------------------------------------------------- */
/* --------- ChangeMeasurePath ------------------------------- */
/* ---------------------------------------------------------- */
bool measure::ChangeMeasurePath(int studyid, int newMeasureNum) {
    study s(studyid, n);
    QString newMeasurePath = QString("%1/%2").arg(s.path()).arg(newMeasureNum);

    n->WriteLog("Changing measure path from [" + measurepath + "] to [" + newMeasurePath + "]");

    if (RenameFile(measurepath, newMeasurePath))
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelMeasure measure::GetSquirrelObject() {
    squirrelMeasure sqrl;

    sqrl.dateTime = datetime;
    sqrl.description = desc;
    sqrl.number = measurenum;
    sqrl.protocol = protocol;
    //sqrl.experimentList;
    //sqrl.files;
    //sqrl.numFiles = ;
    //sqrl.size = ;

    return sqrl;
}
