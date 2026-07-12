/* ------------------------------------------------------------------------------
  NIDB enrollment.h
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

#ifndef ENROLLMENT_H
#define ENROLLMENT_H

#include <QString>
#include "nidb.h"

enum criteriaEnrollment { rowID, subjectAndProjectRowID };

class enrollment
{
public:
    enrollment();
    enrollment(int enrollmentRowID, nidb *a); /* get study by studyID */
    enrollment(int subjectRowID, int projectRowID, nidb *a);

    nidb *n;

    QString UID() { return _uid; }
    QString enrollmentGroup() { return _enrollmentgroup; }
    QString enrollmentStatus() { return _enrollmentstatus; }
    QString msg() { return _msg; }
    int enrollmentRowID() { return _enrollmentid; }
    int projectRowID() { return _projectid; }
    int subjectRowID() { return _subjectid; }

    bool valid() { return _isValid; }
    void PrintEnrollmentInfo();

private:
    void LoadEnrollmentInfo();
    criteriaEnrollment searchCriteria;

    QString _enrollmentgroup = "";
    QString _enrollmentstatus = "";
    QString _uid = "";
    int _enrollmentid = -1;
    int _projectid = -1;
    int _subjectid = -1;

    bool _isValid = false;
    QString _msg;

};

#endif // ENROLLMENT_H
