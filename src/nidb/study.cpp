/* ------------------------------------------------------------------------------
  NIDB study.cpp
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

#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
study::study(nidb *a)
{
    n = a;
    //searchCriteria = rowid;

    //_studyid = id;

    //LoadStudyInfo();
}


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief overloaded constructor - assumes a search criteria of
 * the subjectRowID
 * @param a
 */
study::study(int rowID, nidb *a)
{
    searchMethod = StudySearchMethod::RowId;
    searchStudyRowID = rowID;
    n = a;

    Load();
}


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
/*    find study by UIDStudyNum (S1234ABC1)                   */
/* ---------------------------------------------------------- */
// study::study(QString uidStudyNum, nidb *a) {
//     n = a;
//     searchCriteria = uidstudynum;

//     _uid = uidStudyNum.left(8);
//     _studynum = uidStudyNum.mid(8).toInt();

//     LoadStudyInfo();
// }

/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
// study::study(int enrollmentRowID, QString studyDateTime, QString modality, nidb *a) {
//     n = a;
//     searchCriteria = studydatetimemodality;

//     studyDateTime = studyDateTime.replace("T", " ");
//     if (studyDateTime.contains(".")) /* if it ends with a .millisecond */
//         studyDateTime.chop(4); /* remove last 4 characters */

//     _enrollmentid = enrollmentRowID;
//     _studydatetime = QDateTime::fromString(studyDateTime, "yyyy-MM-dd hh:mm:ss");
//     _modality = modality;

//     //n->WriteLog("studyDateTime [" + studyDateTime + "]");
//     //n->WriteLog("_studyDateTime.toLocalTime().toString() [" + _studydatetime.toLocalTime().toString("yyyy-MM-dd hh:mm:ss") + "]");
//     //n->WriteLog("_studyDateTime.toString() [" + _studydatetime.toString("yyyy-MM-dd hh:mm:ss") + "]");
//     //PrintStudyInfo();
//     LoadStudyInfo();
// }


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
// study::study(int enrollmentRowID, QString studyUID, nidb *a) {
//     n = a;
//     _enrollmentid = enrollmentRowID;
//     _studyuid = studyUID;
//     searchMethod = StudyUid;

//     LoadStudyInfo();
// }


/* ---------------------------------------------------------- */
/* --------- Load ------------------------------------------- */
/* ---------------------------------------------------------- */
bool study::Load() {

    QStringList msgs;

    QSqlQuery q;
    switch (searchMethod) {
        case StudySearchMethod::RowId:
            q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, b.enroll_subgroup, b.enroll_status, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = :studyid");
            q.bindValue(":studyid", searchStudyRowID);
            break;
        case StudySearchMethod::UidStudyNum:
            q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, b.enroll_subgroup, b.enroll_status, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where c.uid = :uid and a.study_num = :studynum");
            q.bindValue(":uid", searchUID);
            q.bindValue(":studynum", searchStudyNum);
            break;
        case StudySearchMethod::StudyDatetimeModality:
            q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, b.enroll_subgroup, b.enroll_status, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.enrollment_id = :enrollmentid and a.study_datetime > '" + searchDatetime.addSecs(-31).toString("yyyy-MM-dd hh:mm:ss") + "' and a.study_datetime < '" + searchDatetime.addSecs(30).toString("yyyy-MM-dd hh:mm:ss") + "' and a.study_modality = :modality");
            q.bindValue(":enrollmentid", searchEnrollmentRowID);
            //q.bindValue(":studydatelow", _studydatetime.addSecs(-30).toString("yyyy-MM-dd hh:mm:ss"));
            //q.bindValue(":studydatehigh", _studydatetime.addSecs(30).toString("yyyy-MM-dd hh:mm:ss"));
            q.bindValue(":modality", searchModality);
            break;
        case StudySearchMethod::StudyUid:
            q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, b.enroll_subgroup, b.enroll_status, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_uid = :studyuid");
            q.bindValue(":studyuid", searchStudyUID);
            break;
    }

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    //n->WriteLog(QString("%1() Found [%2] rows").arg(__FUNCTION__).arg(q.size()));

    if (q.size() > 0) {
        q.first();
        valid = true;
        studyRowID = q.value("study_id").toInt();
        uid = q.value("uid").toString().trimmed().replace('\u0000', "");
        desc = q.value("study_desc").toString().trimmed();
        studyNum = q.value("study_num").toInt();
        projectRowID = q.value("project_id").toInt();
        subjectRowID = q.value("subject_id").toInt();
        enrollmentRowID = q.value("enrollment_id").toInt();
        enrollmentGroup = q.value("enroll_subgroup").toString().trimmed();
        enrollmentStatus = q.value("enroll_status").toString().trimmed();
        datetime = q.value("study_datetime").toDateTime();
        modality = q.value("study_modality").toString().trimmed();
        type = q.value("study_type").toString().trimmed();
        daynum = q.value("study_daynum").toString().trimmed();
        timepoint = q.value("study_timepoint").toString().trimmed();
        equipment = q.value("study_site").toString().trimmed();
        height = q.value("study_height").toDouble();
        weight = q.value("study_weight").toDouble();

        /* check to see if anything isn't valid or is blank */
        if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; valid = false; }
        if (uid == "") { msgs << "uid was blank"; valid = false; }
        if (studyNum < 1) { msgs << "studynum is not valid"; valid = false; }

        studypath = QString("%1/%2/%3").arg(n->cfg["archivedir"]).arg(uid).arg(studyNum);

        QDir d(studypath);
        if (d.exists()) {
            msgs << QString("Study path [%1] exists").arg(studypath);
            studyPathExists = true;
        }
        else {
            msgs << QString("Study path [%1] does not exist").arg(studypath);
            studyPathExists = false;
        }
    }
    else {
        msgs << "Query returned no results. Possibly invalid study ID or recently deleted?";
        valid = false;
    }


    msg = msgs.join("\n");
    //PrintStudyInfo();

    return valid;
}


/* ---------------------------------------------------------- */
/* --------- PrintStudyInfo --------------------------------- */
/* ---------------------------------------------------------- */
void study::PrintStudyInfo() {
    QString	output = QString("***** Study - rowID [%1] *****\n").arg(studyRowID);

    output += QString("   enrollmentgroup: [%1]\n").arg(enrollmentGroup);
    output += QString("   enrollmentid: [%1]\n").arg(enrollmentRowID);
    output += QString("   enrollmentstatus: [%1]\n").arg(enrollmentStatus);
    output += QString("   isValid: [%1]\n").arg(valid);
    output += QString("   modality: [%1]\n").arg(modality);
    output += QString("   msg: [%1]\n").arg(msg);
    output += QString("   projectid: [%1]\n").arg(projectRowID);
    output += QString("   studydatetime: [%1]\n").arg(datetime.toString("yyyy-MM-dd HH:mm:ss"));
    output += QString("   studyid: [%1]\n").arg(studyRowID);
    output += QString("   studynum: [%1]\n").arg(studyNum);
    output += QString("   studypath: [%1]\n").arg(studypath);
    output += QString("   studytype: [%1]\n").arg(type);
    output += QString("   subjectid: [%1]\n").arg(subjectRowID);
    output += QString("   uid: [%1]\n").arg(uid);

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelStudy study::GetSquirrelObject(QString databaseUUID) {
    squirrelStudy s(databaseUUID);

    s.DateTime = datetime;
    s.DayNumber = daynum.toInt();
    s.Description = desc;
    s.Equipment = equipment;
    s.Height = height;
    s.Modality = modality;
    s.StudyNumber = studyNum;
    s.TimePoint = timepoint.toInt();
    s.VisitType = type;
    s.Weight = weight;

    return s;
}
