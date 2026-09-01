/* ------------------------------------------------------------------------------
  NIDB subject.h
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

#ifndef SUBJECT_H
#define SUBJECT_H
#include <QString>
#include "nidb.h"
#include "squirrelSubject.h"

/**
 * @brief The subject class - databaseRowID, valid, datapath are private, all other subject info is public
 */
class subject
{
public:
    subject(nidb *a);

    bool Load();
    bool Update();

    void PrintSubjectInfo();
    squirrelSubject GetSquirrelObject(QString databaseUUID);

    QString GetSubjectDataPath() { return subjectDataPath; }
    QStringList GetAllAlternateIDs();
    QString GetPrimaryAlternateID(int projectRowID);
    bool DataPathExists() { return dataPathExists; }
    bool isValid() { return valid; }
    int GetSubjectRowID() { return subjectRowID; }

    /* subject data */
    QDate dob = QDate(0,0,0);
    QString ethnicity1;
    QString ethnicity2;
    QString gender = "U";
    QString guid;
    QString handedness = "U";
    QString sex = "U";
    QString uid;
    QStringList altuids;
    int searchProjectRowID = -1;

    /* object information */
    SubjectSearchMethod searchMethod = SubjectSearchMethod::RowId;
    QString searchAltUID;
    QString searchDOB; /* YYYY-MM-DD format */
    QString searchName;
    QString searchSex;
    QString searchUID;
    int searchSubjectRowID = -1;
    QString msg;
    QStringList msgs;

private:
    bool LoadSubjectInfo();

    nidb *n;
    int subjectRowID = -1;
    QString subjectDataPath;
    bool dataPathExists = false;
    bool valid = false;

};

#endif // SUBJECT_H
