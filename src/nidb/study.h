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

enum class StudySearchMethod { RowId, UidStudyNum, StudyDatetimeModality, StudyUid };

class study
{
public:
    study(nidb *a);
    study(int rowID, nidb *a);
    //study(int studyRowID, nidb *a); /* get study by studyID */
    //study(QString uidStudyNum, nidb *a);
    //study(int enrollmentRowID, QString studyDateTime, QString modality, nidb *a);
    //study(int enrollmentRowID, QString studyUID, nidb *a);

    void PrintStudyInfo();
    squirrelStudy GetSquirrelObject(QString databaseUUID);
    bool isValid() { return valid; }
    bool Load();

    /* immutable information (rowids, etc) */
    bool DataPathExists() { return studyPathExists; }
    int GetEnrollmentRowID() { return enrollmentRowID; }
    int GetProjectRowID() { return projectRowID; }
    int GetStudyNum() { return studyNum; }
    QString GetUID() { return uid; }
    int GetStudyRowID() { return studyRowID; }
    int GetSubjectRowID() { return subjectRowID; }
    QString GetPath() { return studypath; }

    /* study data */
    QDateTime datetime;
    QString daynum;
    QString desc;
    QString enrollmentGroup;
    QString enrollmentStatus;
    QString equipment;
    QString modality;
    QString timepoint;
    QString type;
    QString studyuid;
    double height;
    double weight;

    /* object info */
    StudySearchMethod searchMethod = StudySearchMethod::RowId;
    int searchStudyRowID = -1;
    int searchEnrollmentRowID = -1;
    QString searchUID;
    int searchStudyNum = -1;
    QDateTime searchDatetime;
    QString searchModality;
    QString searchStudyUID;
    QString msg;

private:

    nidb *n;

    QString studypath = "";
    bool studyPathExists = false;
    QString uid;
    int enrollmentRowID = -1;
    int projectRowID = -1;
    int studyRowID = -1;
    int studyNum = -1;
    int subjectRowID = -1;

    bool pathExists = false;
    bool valid = false;

};

#endif // STUDY_H
