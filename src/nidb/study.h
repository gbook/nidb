/* ------------------------------------------------------------------------------
  NIDB study.h
  Copyright (C) 2004 - 2022
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

    int studyRowID() { return _studyid; }
    int subjectRowID() { return _subjectid; }
    int enrollmentRowID() { return _enrollmentid; }
    int projectRowID() { return _projectid; }
    QString UID() { return _uid; }
	QString desc() { return _desc; }
	int studyNum() { return _studynum; }
    QString type() { return _studytype; }
	QString daynum() { return _daynum; }
	QString timepoint() { return _timepoint; }
	QString path() { return _studypath; }
    bool pathExists() { return _studyPathExists; }
    QDateTime dateTime() { return _studydatetime; }
    QString modality() { return _modality; }
    QString msg() { return _msg; }

    bool valid() { return _isValid; }

private:
    criteria searchCriteria;
    void LoadStudyInfo();

    int _studyid = -1;
    int _studynum = -1;
    QString _uid = "";
	QString _desc = "";
	QString _studytype = "";
	QString _daynum = "";
	QString _timepoint = "";
	int _subjectid = -1;
    QString _studypath = "";
    bool _studyPathExists = false;
    int _enrollmentid = -1;
    int _projectid = -1;
    QDateTime _studydatetime;
    QString _modality = "";
    QString _studyuid = "";

    bool _isValid = false;
    QString _msg;

};

#endif // STUDY_H
