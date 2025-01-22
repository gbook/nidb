/* ------------------------------------------------------------------------------
  NIDB study.cpp
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

#include "study.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
study::study(int id, nidb *a)
{
    n = a;
    searchCriteria = rowid;

    _studyid = id;

    LoadStudyInfo();
}


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
/*    find study by UIDStudyNum (S1234ABC1)                   */
/* ---------------------------------------------------------- */
study::study(QString uidStudyNum, nidb *a) {
    n = a;
    searchCriteria = uidstudynum;

    _uid = uidStudyNum.left(8);
    _studynum = uidStudyNum.mid(8).toInt();

    LoadStudyInfo();
}

/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
study::study(int enrollmentRowID, QString studyDateTime, QString modality, nidb *a) {
    n = a;
    searchCriteria = studydatetimemodality;

    studyDateTime = studyDateTime.replace("T", " ");
    if (studyDateTime.contains(".")) /* if it ends with a .millisecond */
        studyDateTime.chop(4); /* remove last 4 characters */

    _enrollmentid = enrollmentRowID;
    _studydatetime = QDateTime::fromString(studyDateTime, "yyyy-MM-dd hh:mm:ss");
    _modality = modality;

    //n->WriteLog("studyDateTime [" + studyDateTime + "]");
    //n->WriteLog("_studyDateTime.toLocalTime().toString() [" + _studydatetime.toLocalTime().toString("yyyy-MM-dd hh:mm:ss") + "]");
    //n->WriteLog("_studyDateTime.toString() [" + _studydatetime.toString("yyyy-MM-dd hh:mm:ss") + "]");
    //PrintStudyInfo();
    LoadStudyInfo();
}


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
study::study(int enrollmentRowID, QString studyUID, nidb *a) {
    n = a;
    _enrollmentid = enrollmentRowID;
    _studyuid = studyUID;
    searchCriteria = studyuid;

    LoadStudyInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadStudyInfo ---------------------------------- */
/* ---------------------------------------------------------- */
void study::LoadStudyInfo() {

    QStringList msgs;

    QSqlQuery q;
    switch (searchCriteria) {
        case rowid:
		    q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = :studyid");
            q.bindValue(":studyid", _studyid);
            break;
        case uidstudynum:
		    q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where c.uid = :uid and a.study_num = :studynum");
            q.bindValue(":uid", _uid);
            q.bindValue(":studynum", _studynum);
            break;
        case studydatetimemodality:
		    q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.enrollment_id = :enrollmentid and a.study_datetime > '" + _studydatetime.addSecs(-31).toString("yyyy-MM-dd hh:mm:ss") + "' and a.study_datetime < '" + _studydatetime.addSecs(30).toString("yyyy-MM-dd hh:mm:ss") + "' and a.study_modality = :modality");
            q.bindValue(":enrollmentid", _enrollmentid);
            //q.bindValue(":studydatelow", _studydatetime.addSecs(-30).toString("yyyy-MM-dd hh:mm:ss"));
            //q.bindValue(":studydatehigh", _studydatetime.addSecs(30).toString("yyyy-MM-dd hh:mm:ss"));
            q.bindValue(":modality", _modality);
            break;
        case studyuid:
		    q.prepare("select a.study_id, c.uid, c.subject_id, a.study_num, b.project_id, b.enrollment_id, a.study_datetime, a.study_modality, a.study_type, a.study_height, a.study_weight, a.study_site, a.study_daynum, a.study_timepoint, a.study_desc from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_uid = :studyuid");
            q.bindValue(":studyuid", _studyuid);
            break;
    }

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    //n->WriteLog(QString("%1() Found [%2] rows").arg(__FUNCTION__).arg(q.size()));

    if (q.size() > 0) {
        q.first();
        _isValid = true;
        _studyid = q.value("study_id").toInt();
        _uid = q.value("uid").toString().trimmed().replace('\u0000', "");
		_desc = q.value("study_desc").toString().trimmed();
		_studynum = q.value("study_num").toInt();
        _projectid = q.value("project_id").toInt();
        _subjectid = q.value("subject_id").toInt();
        _enrollmentid = q.value("enrollment_id").toInt();
        _studydatetime = q.value("study_datetime").toDateTime();
        _modality = q.value("study_modality").toString().trimmed();
        _studytype = q.value("study_type").toString().trimmed();
		_daynum = q.value("study_daynum").toString().trimmed();
		_timepoint = q.value("study_timepoint").toString().trimmed();
		_equipment = q.value("study_site").toString().trimmed();
		_height = q.value("study_height").toDouble();
		_weight = q.value("study_weight").toDouble();

        /* check to see if anything isn't valid or is blank */
        if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; _isValid = false; }
        if (_uid == "") { msgs << "uid was blank"; _isValid = false; }
        if (_studynum < 1) { msgs << "studynum is not valid"; _isValid = false; }

        _studypath = QString("%1/%2/%3").arg(n->cfg["archivedir"]).arg(_uid).arg(_studynum);

        QDir d(_studypath);
        if (d.exists()) {
            msgs << QString("Study path [%1] exists").arg(_studypath);
            _studyPathExists = true;
        }
        else {
            msgs << QString("Study path [%1] does not exist").arg(_studypath);
            _studyPathExists = false;
        }
    }
    else {
        msgs << "Query returned no results. Possibly invalid study ID or recently deleted?";
        _isValid = false;
    }


    _msg = msgs.join("\n");
    //PrintStudyInfo();
}


/* ---------------------------------------------------------- */
/* --------- PrintStudyInfo --------------------------------- */
/* ---------------------------------------------------------- */
void study::PrintStudyInfo() {
    QString	output = QString("***** Study - rowID [%1] *****\n").arg(_studyid);

    output += QString("   uid: [%1]\n").arg(_uid);
    output += QString("   subjectid: [%1]\n").arg(_subjectid);
    output += QString("   studyid: [%1]\n").arg(_studyid);
    output += QString("   studynum: [%1]\n").arg(_studynum);
    output += QString("   studytype: [%1]\n").arg(_studytype);
    output += QString("   modality: [%1]\n").arg(_modality);
    output += QString("   projectid: [%1]\n").arg(_projectid);
    output += QString("   enrollmentid: [%1]\n").arg(_enrollmentid);
    output += QString("   isValid: [%1]\n").arg(_isValid);
    output += QString("   msg: [%1]\n").arg(_msg);
    output += QString("   studypath: [%1]\n").arg(_studypath);
    output += QString("   studydatetime: [%1]\n").arg(_studydatetime.toString("yyyy-MM-dd HH:mm:ss"));

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelStudy study::GetSquirrelObject(QString databaseUUID) {
    squirrelStudy s(databaseUUID);

    s.DateTime = _studydatetime;
    s.DayNumber = _daynum.toInt();
    s.Description = _desc;
    s.Equipment = _equipment;
    s.Height = _height;
    s.Modality = _modality;
    s.StudyNumber = _studynum;
    s.TimePoint = _timepoint.toInt();
    s.VisitType = _studytype;
    s.Weight = _weight;

    return s;
}
