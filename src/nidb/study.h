/* ------------------------------------------------------------------------------
  NIDB study.h
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

#ifndef STUDY_H
#define STUDY_H
#include <QString>
#include "nidb.h"
#include "squirrelStudy.h"

enum criteria {rowid, uidstudynum, studydatetimemodality, studyuid};

class study
{
public:
    study();
    study(int studyRowID, nidb *a); /* get study by studyID */
    study(QString uidStudyNum, nidb *a);
    study(int enrollmentRowID, QString studyDateTime, QString modality, nidb *a);
    study(int enrollmentRowID, QString studyUID, nidb *a);

    nidb *n;

    void PrintStudyInfo();
    squirrelStudy GetSquirrelObject(QString databaseUUID);

    QDateTime dateTime() { return _studydatetime; }
    QString UID() { return _uid; }
    QString daynum() { return _daynum; }
    QString desc() { return _desc; }
    QString enrollmentGroup() { return _enrollmentgroup; }
    QString enrollmentStatus() { return _enrollmentstatus; }
    QString equipment() { return _equipment; }
    QString modality() { return _modality; }
    QString msg() { return _msg; }
    QString path() { return _studypath; }
    QString timepoint() { return _timepoint; }
    QString type() { return _studytype; }
    bool pathExists() { return _studyPathExists; }
    double height() { return _height; }
    double weight() { return _weight; }
    int enrollmentRowID() { return _enrollmentid; }
    int projectRowID() { return _projectid; }
    int studyNum() { return _studynum; }
    int studyRowID() { return _studyid; }
    int subjectRowID() { return _subjectid; }

    bool valid() { return _isValid; }

private:
    criteria searchCriteria;
    void LoadStudyInfo();

    QDateTime _studydatetime;
    QString _daynum = "";
    QString _desc = "";
    QString _enrollmentgroup = "";
    QString _enrollmentstatus = "";
    QString _equipment = "";
    QString _modality = "";
    QString _studypath = "";
    QString _studytype = "";
    QString _studyuid = "";
    QString _timepoint = "";
    QString _uid = "";
    bool _studyPathExists = false;
    double _height = 0.0;
    double _weight = 0.0;
    int _enrollmentid = -1;
    int _projectid = -1;
    int _studyid = -1;
    int _studynum = -1;
    int _subjectid = -1;

    bool _isValid = false;
    QString _msg;

};

#endif // STUDY_H
