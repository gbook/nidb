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


class subject
{
public:
    subject();
    subject(int id, nidb *a);
    subject(QString uid, bool checkAltUID, nidb *a);
    subject(QString altuid, int projectid, nidb *a);
    subject(QString name, QString sex, QString dob, nidb *a);
    nidb *n;

    void PrintSubjectInfo();
    squirrelSubject GetSquirrelObject(QString databaseUUID);

    QDate dob() { return _dob; }
    QString GUID() { return _guid; }
    QString ethnicity1() { return _ethnicity1; }
    QString ethnicity2() { return _ethnicity2; }
    QString handedness() { return _handedness; }
    QString sex() { return _sex; }
    QString UID() { return _uid; }
    QString msg() { return _msg; }
    QString path() { return _subjectpath; }
    QStringList GetAllAlternateIDs();
    QString GetPrimaryAlternateID(int projectRowID);
    bool dataPathExists() { return _dataPathExists; }
    bool valid() { return _isValid; }
    int subjectRowID() { return _subjectid; }

private:
    void LoadSubjectInfo();

    int _subjectid = -1;
    QString _uid = "";
	QString _guid = "";
	QStringList _altuids;
	QDate _dob = QDate(0,0,0);
	QString _sex = "U";
	QString _ethnicity1 = "";
	QString _ethnicity2 = "";
	QString _handedness = "U";
    QString _subjectpath = "";
    bool _dataPathExists = false;
    bool _isValid = false;
    QString _msg = "";
    QStringList msgs;

};

#endif // SUBJECT_H
