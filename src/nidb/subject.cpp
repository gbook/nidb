/* ------------------------------------------------------------------------------
  NIDB subject.cpp
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

#include "subject.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- subject ---------------------------------------- */
/* ---------------------------------------------------------- */
/*     find subject by rowID                                  */
/* ---------------------------------------------------------- */
subject::subject(int id, nidb *a)
{
    n = a;
    _subjectid = id;

    LoadSubjectInfo();
}


/* ---------------------------------------------------------- */
/* --------- subject ---------------------------------------- */
/* ---------------------------------------------------------- */
/*     find subject by UID, or AltUID                         */
/* ---------------------------------------------------------- */
subject::subject(QString uid, bool checkAltUID, nidb *a)
{
    n = a;

    QSqlQuery q;
    q.prepare("select subject_id from subjects where uid = :uid");
    q.bindValue(":uid", uid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1) {

        if (checkAltUID) {
            msgs << "Subject not found by UID [" + uid + "]. Checking for alternate UID";
            /* check for alternate UID */
            q.prepare("select * from subjects a left join subject_altuid b on a.subject_id = b.subject_id left join enrollment c on a.subject_id = c.subject_id WHERE (a.uid = :altuid or a.uid = SHA1(:altuid) or b.altuid = :altuid or b.altuid = SHA1(:altuid))");
            q.bindValue(":altuid", uid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
            if (q.size() < 1) {
                msgs << "Subject not found by AltUID [" + uid + "]. Subject could not be found";

                _isValid = false;
            }
            else {
                q.first();
                _subjectid = q.value("subject_id").toInt();
            }
        }
        else {
            msgs << "Subject not found by UID [" + uid + "]. Subject could not be found";
            _isValid = false;
        }
    }
    else {
        q.first();
        _subjectid = q.value("subject_id").toInt();
    }

    LoadSubjectInfo();
}


/* ---------------------------------------------------------- */
/* --------- subject ---------------------------------------- */
/* ---------------------------------------------------------- */
/*     find subject by alternate UID and projectRowID         */
/* ---------------------------------------------------------- */
subject::subject(QString altuid, int projectid, nidb *a)
{
    n = a;

    QSqlQuery q;
    q.prepare("select * from subjects a left join subject_altuid b on a.subject_id = b.subject_id left join enrollment c on a.subject_id = c.subject_id WHERE (a.uid = :altuid or a.uid = SHA1(:altuid) or b.altuid = :altuid or b.altuid = SHA1(:altuid)) and c.project_id = :projectid");
    q.bindValue(":altuid", altuid);
    q.bindValue(":projectid", projectid);

    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        _subjectid = q.value("subject_id").toInt();
        _isValid = true;

        n->WriteLog(QString("Subject with subjectRowID [%1] found by:  altuid [%2]  projectid [%3]").arg(_subjectid).arg(altuid).arg(projectid));
    }
    else {
        n->WriteLog(QString("Subject not found by:  altuid [%2]  projectid [%3]").arg(altuid).arg(projectid));
        msgs << QString("Subject not found by altUID [" + altuid + "] within project [%1]").arg(projectid);
        _isValid = false;
    }

    LoadSubjectInfo();
}


/* ---------------------------------------------------------- */
/* --------- subject ---------------------------------------- */
/* ---------------------------------------------------------- */
/*     find subject by name, sex, dob                         */
/* ---------------------------------------------------------- */
subject::subject(QString name, QString sex, QString dob, nidb *a)
{
    n = a;

    //n->WriteLog("Constructor C");

    QSqlQuery q;
    q.prepare("select subject_id from subjects where name = :name and birthdate = :dob and gender = :sex");
    q.bindValue(":name", name);
    q.bindValue(":sex", sex);
    q.bindValue(":dob", dob);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        _subjectid = q.value("subject_id").toInt();

        //n->WriteLog(QString("Constructor C - found subjectRowID [%1]").arg(_subjectid));
    }
    else {
        msgs << "Subject not found by  Name [" + name + "], Sex [" + sex + "], DOB [" + dob + "]  could not be found";
        _isValid = false;
    }

    LoadSubjectInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadSubjectInfo -------------------------------- */
/* ---------------------------------------------------------- */
void subject::LoadSubjectInfo() {

    if (_subjectid < 1) {
        msgs << "Subject not found by subjectRowID";
        _isValid = false;
    }
    else {
        /* get the path to the analysisroot */
        QSqlQuery q;
        q.prepare("select * from subjects where subject_id = :subjectid");
        q.bindValue(":subjectid", _subjectid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid subject ID or recently deleted?";
            _isValid = false;
        }
        else {
            q.first();
            _uid = q.value("uid").toString().trimmed();
            _dob = q.value("birthdate").toDate();
            _sex = q.value("gender").toString().trimmed();
            _ethnicity1 = q.value("ethnicity1").toString().trimmed();
            _ethnicity2 = q.value("ethnicity2").toString().trimmed();
            _handedness = q.value("handedness").toString().trimmed();

            /* check to see if anything isn't valid or is blank */
            if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; _isValid = false; }
            if (_uid == "") { msgs << "uid was blank"; _isValid = false; }

            _subjectpath = QString("%1/%2").arg(n->cfg["archivedir"]).arg(_uid);

            QDir d(_subjectpath);
            if (!d.exists()) {
                msgs << QString("Subject path does not exist [%1]").arg(_subjectpath);
                _dataPathExists = false;
            }
            else {
                _dataPathExists = true;
            }
        }
        _isValid = true;
    }
    _msg = msgs.join("\n");
}


/* ---------------------------------------------------------- */
/* --------- PrintSubjectInfo ------------------------------- */
/* ---------------------------------------------------------- */
void subject::PrintSubjectInfo() {
    QString	output = QString("***** Subject - [%1] *****\n").arg(_subjectid);

    output += QString("   uid: [%1]\n").arg(_uid);
    output += QString("   subjectid: [%1]\n").arg(_subjectid);
    output += QString("   isValid: [%1]\n").arg(_isValid);
    output += QString("   msg: [%1]\n").arg(_msg);
    output += QString("   analysispath: [%1]\n").arg(_subjectpath);

    n->WriteLog(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelSubject subject::GetSquirrelObject() {
    squirrelSubject sqrl;

    sqrl.alternateIDs = _altuids;
    sqrl.dateOfBirth = _dob;
    sqrl.ethnicity1 = _ethnicity1;
    sqrl.ethnicity2 = _ethnicity2;
    sqrl.gender = _sex;
    sqrl.sex = _sex;
    sqrl.GUID = _guid;
    sqrl.ID = _uid;

    return sqrl;
}
