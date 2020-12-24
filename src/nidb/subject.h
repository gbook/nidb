/* ------------------------------------------------------------------------------
  NIDB subject.h
  Copyright (C) 2004 - 2020
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


class subject
{
public:
    subject();
    subject(int id, nidb *a);
    subject(QString uid, nidb *a);
    subject(QString altuid, int projectid, nidb *a);
    subject(QString name, QString sex, QString dob, nidb *a);
    nidb *n;

    void PrintSubjectInfo();

    int subjectRowID() { return _subjectid; }
    QString UID() { return _uid; }
    QStringList altUIDs() { return _altuids; }
    QString path() { return _subjectpath; }
    bool dataPathExists() { return _dataPathExists; }
    bool valid() { return _isValid; }
    QString msg() { return _msg; }

private:
    void LoadSubjectInfo();

    int _subjectid = -1;
    QString _uid = "";
    QStringList _altuids;
    QString _subjectpath = "";
    bool _dataPathExists = false;
    bool _isValid = false;
    QString _msg = "";

};

#endif // SUBJECT_H
