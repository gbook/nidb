/* ------------------------------------------------------------------------------
  NIDB enrollment.cpp
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

#include "enrollment.h"
#include <QSqlQuery>

enrollment::enrollment() {}

/* ---------------------------------------------------------- */
/* --------- enrollment ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief create an enrollment by enrollmentRowID
 * @param id The enrollmentRowID
 * @param a pointer to nidb object
 */
enrollment::enrollment(int id, nidb *a)
{
    n = a;

    _enrollmentid = id;
    searchCriteria = rowID;
    LoadEnrollmentInfo();
}


/* ---------------------------------------------------------- */
/* --------- study ------------------------------------------ */
/* ---------------------------------------------------------- */
enrollment::enrollment(int subjectRowID, int projectRowID, nidb *a) {
    n = a;
    _subjectid = subjectRowID;
    _projectid = projectRowID;
    searchCriteria = subjectAndProjectRowID;
    LoadEnrollmentInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadEnrollmentInfo ----------------------------- */
/* ---------------------------------------------------------- */
void enrollment::LoadEnrollmentInfo() {

    QStringList msgs;

    QSqlQuery q;
    switch (searchCriteria) {
    case rowID:
        q.prepare("select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = :enrollmentid");
        q.bindValue(":enrollmentid", _enrollmentid);
        break;
    case subjectAndProjectRowID:
        q.prepare("select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.subject_id = :subject_id and a.project_id = :projectid");
        q.bindValue(":subjectid", _subjectid);
        q.bindValue(":projectid", _projectid);
        break;
    }

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() > 0) {
        q.first();
        _isValid = true;
        _uid = q.value("uid").toString().trimmed().replace('\u0000', "");
        _projectid = q.value("project_id").toInt();
        _subjectid = q.value("subject_id").toInt();
        _enrollmentid = q.value("enrollment_id").toInt();
        _enrollmentgroup = q.value("enroll_subgroup").toString().trimmed();
        _enrollmentstatus = q.value("enroll_status").toString().trimmed();

        /* check to see if anything isn't valid or is blank */
        if (_uid == "") { msgs << "uid was blank"; _isValid = false; }
    }
    else {
        msgs << "Query returned no results. Invalid enrollmentID or recently deleted?";
        _isValid = false;
    }


    _msg = msgs.join("\n");
}


/* ---------------------------------------------------------- */
/* --------- PrintEnrollmentInfo ---------------------------- */
/* ---------------------------------------------------------- */
void enrollment::PrintEnrollmentInfo() {
    QString output;
    output += QString("   enrollmentgroup: [%1]\n").arg(_enrollmentgroup);
    output += QString("   enrollmentid: [%1]\n").arg(_enrollmentid);
    output += QString("   enrollmentstatus: [%1]\n").arg(_enrollmentstatus);
    output += QString("   isValid: [%1]\n").arg(_isValid);
    output += QString("   msg: [%1]\n").arg(_msg);
    output += QString("   projectid: [%1]\n").arg(_projectid);
    output += QString("   subjectid: [%1]\n").arg(_subjectid);
    output += QString("   uid: [%1]\n").arg(_uid);

    n->Log(output);
}
