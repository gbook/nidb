/* ------------------------------------------------------------------------------
  NIDB subject.cpp
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

#include "subject.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- subject ---------------------------------------- */
/* ---------------------------------------------------------- */
subject::subject(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- Load ------------------------------------------- */
/* ---------------------------------------------------------- */
bool subject::Load() {
    QSqlQuery q;

    /* ----- search by subjectRowID ----- */
    if (searchMethod == RowId) {
        if (searchSubjectRowID > 0) {
            q.prepare("select subject_id from subjects where subject_id = :subjectid");
            q.bindValue(":subjectid", searchSubjectRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                subjectRowID = q.value("subject_id").toInt();
                valid = true;
            }
            else {
                msgs << QString("searchSubjectRowID [%1] not found").arg(searchSubjectRowID);
                valid = false;
            }
        }
        else {
            msgs << "Searching by subjectRowID, but subjectRowID is not set";
            valid = false;
        }
    }
    /* ----- search by UID ----- */
    else if (searchMethod == Uid) {
        if (searchUID == "") {
            msgs << "Searching by UID, but searchUID is not set";
            valid = false;
        }
        else {
            q.prepare("select subject_id from subjects where uid = :uid");
            q.bindValue(":uid", searchUID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                subjectRowID = q.value("subject_id").toInt();
                valid = true;
            }
            else {
                msgs << QString("Searched by searchUID [%1]; subject not found").arg(searchUID);
                valid = false;
            }
        }
    }
    /* ----- search by AltUID, projectRowID ----- */
    else if (searchMethod == AltUid) {
        if (searchAltUID == "") {
            msgs << "Searching by AltUID, but searchAltUID is not set";
            valid = false;
        }
        else if (searchProjectRowID < 0) {
            msgs << "Searching by AltUID, but searchProjectRowID is not set";
            valid = false;
        }
        else {
            q.prepare("select * from subjects a left join subject_altuid b on a.subject_id = b.subject_id left join enrollment c on a.subject_id = c.subject_id WHERE (a.uid = :altuid or b.altuid = :altuid) and c.project_id = :projectid");
            q.bindValue(":altuid", searchAltUID);
            q.bindValue(":projectid", searchProjectRowID);

            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                subjectRowID = q.value("subject_id").toInt();
                valid = true;

                n->Log(QString("Subject with subjectRowID [%1] found by:  altuid [%2]  searchProjectRowID [%3]").arg(subjectRowID).arg(searchAltUID).arg(searchProjectRowID));
            }
            else {
                n->Log(QString("Subject not found by:  altuid [%1]  searchProjectRowID [%2]").arg(searchAltUID).arg(searchProjectRowID));
                msgs << QString("Subject not found by searchAltUID [" + searchAltUID + "] within searchProjectRowID [%1]").arg(searchProjectRowID);
                valid = false;
            }
        }
    }
    /* ----- search by AltUID, UID ----- */
    else if (searchMethod == UidOrAltUid) {
        if ((searchAltUID == "") && (searchUID == "")) {
            msgs << "Searching by AltUID or UID, but neither is not set";
            valid = false;
        }
        else {
            q.prepare("select * from subjects a left join subject_altuid b on a.subject_id = b.subject_id left join enrollment c on a.subject_id = c.subject_id WHERE (a.uid = :uid or b.altuid = :altuid)");
            q.bindValue(":uid", searchUID);
            q.bindValue(":altuid", searchAltUID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            if (q.size() > 0) {
                q.first();
                subjectRowID = q.value("subject_id").toInt();
                valid = true;

                n->Log(QString("Subject with subjectRowID [%1] found by:  altuid [%2]  searchProjectRowID [%3]").arg(subjectRowID).arg(searchAltUID).arg(searchProjectRowID));
            }
            else {
                n->Log(QString("Subject not found by:  altuid [%1]  searchProjectRowID [%2]").arg(searchAltUID).arg(searchProjectRowID));
                msgs << QString("Subject not found by searchAltUID [" + searchAltUID + "] within searchProjectRowID [%1]").arg(searchProjectRowID);
                valid = false;
            }
        }
    }
    /* ----- search by Name, Sex, DOB ----- */
    else if (searchMethod == NameSexDob) {
        q.prepare("select subject_id from subjects where name = :name and birthdate = :dob and gender = :sex");
        q.bindValue(":name", searchName);
        q.bindValue(":sex", searchSex);
        q.bindValue(":dob", searchDOB);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            subjectRowID = q.value("subject_id").toInt();
            valid = true;
        }
        else {
            msgs << "Subject not found by  searchName [" + searchName + "], searchSex [" + searchSex + "], searchDOB [" + searchDOB + "]  could not be found";
            valid = false;
        }
    }

    if (valid) {
        if (LoadSubjectInfo())
            return true;
        else
            return false;
    }
    else {
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- LoadSubjectInfo -------------------------------- */
/* ---------------------------------------------------------- */
bool subject::LoadSubjectInfo() {

    if (subjectRowID < 1) {
        msgs << "Subject not found by subjectRowID";
        valid = false;
    }
    else {
        /* get the path to the analysisroot */
        QSqlQuery q;
        q.prepare("select * from subjects where subject_id = :subjectid");
        q.bindValue(":subjectid", subjectRowID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid subject ID or recently deleted?";
            valid = false;
        }
        else {
            q.first();
            /* assume valid unless one of the checks below fails */
            valid = true;
            /* off chance there is a null-terminator in the UID from database */
            uid = q.value("uid").toString().trimmed().replace('\u0000', "");
            dob = q.value("birthdate").toDate();
            sex = q.value("gender").toString().trimmed();
            ethnicity1 = q.value("ethnicity1").toString().trimmed();
            ethnicity2 = q.value("ethnicity2").toString().trimmed();
            handedness = q.value("handedness").toString().trimmed();

            /* check to see if anything isn't valid or is blank */
            if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; valid = false; }
            if (uid == "") { msgs << "uid was blank"; valid = false; }

            subjectDataPath = QString("%1/%2").arg(n->cfg["archivedir"]).arg(uid);

            QDir d(subjectDataPath);
            if (!d.exists()) {
                msgs << QString("Subject path does not exist [%1]").arg(subjectDataPath);
                dataPathExists = false;
            }
            else {
                dataPathExists = true;
            }
        }
    }
    msg = msgs.join("\n");

    return valid;
}


/* ---------------------------------------------------------- */
/* --------- GetAllAlternateIDs ----------------------------- */
/* ---------------------------------------------------------- */
QStringList subject::GetAllAlternateIDs() {

    QStringList altIDs;

    QSqlQuery q;
    q.prepare("select * from subject_altuid where subject_id = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        altIDs.append(q.value("altuid").toString());
    }

    return altIDs;
}


/* ---------------------------------------------------------- */
/* --------- GetPrimaryAlternateID -------------------------- */
/* ---------------------------------------------------------- */
QString subject::GetPrimaryAlternateID(int projectRowID) {
    QString primaryAltID;

    QSqlQuery q;
    q.prepare("select * from subject_altuid a left join enrollment b on a.enrollment_id = b.enrollment_id where a.subject_id = :subjectid and b.project_id = :projectid and a.isprimary = 1");
    q.bindValue(":subjectid", subjectRowID);
    q.bindValue(":projectid", projectRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        primaryAltID = q.value("altuid").toString();
        n->Debug(QString("Found primary alternate ID [%1] for subjectRowID [%2] and projectRowID [%3]").arg(primaryAltID).arg(subjectRowID).arg(projectRowID), __FUNCTION__);
    }
    else
        n->Debug(QString("Unable to find primary alternate ID for subjectRowID [%1] and projectRowID [%2]").arg(subjectRowID).arg(projectRowID), __FUNCTION__);


    return primaryAltID;
}


/* ---------------------------------------------------------- */
/* --------- PrintSubjectInfo ------------------------------- */
/* ---------------------------------------------------------- */
void subject::PrintSubjectInfo() {
    QString	output = QString("***** Subject - [%1] *****\n").arg(subjectRowID);

    output += QString("   uid: [%1]\n").arg(uid);
    output += QString("   subjectRowID: [%1]\n").arg(subjectRowID);
    output += QString("   valid: [%1]\n").arg(valid);
    output += QString("   msg: [%1]\n").arg(msg);
    output += QString("   analysispath: [%1]\n").arg(subjectDataPath);

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelSubject subject::GetSquirrelObject(QString databaseUUID) {
    squirrelSubject sqrl(databaseUUID);

    sqrl.AlternateIDs = altuids;
    sqrl.DateOfBirth = dob;
    sqrl.Ethnicity1 = ethnicity1;
    sqrl.Ethnicity2 = ethnicity2;
    sqrl.Gender = gender;
    sqrl.Sex = sex;
    sqrl.GUID = guid;
    sqrl.ID = uid;

    return sqrl;
}
