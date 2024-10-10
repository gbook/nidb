/* ------------------------------------------------------------------------------
  Squirrel subject.cpp
  Copyright (C) 2004 - 2024
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

#include "squirrelSubject.h"
#include "utils.h"


/* ------------------------------------------------------------ */
/* ----- subject ---------------------------------------------- */
/* ------------------------------------------------------------ */
squirrelSubject::squirrelSubject()
{
    DateOfBirth = QDate(0,0,0);
    Gender = "U";
    Sex = "U";
    SequenceNumber = -1;

    valid = false;
    objectID = -1;
    subjectDirFormat = "orig";
}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelSubject::Get
 * @return true if successful
 *
 * Load the subject data from the database. The subjectRowID
 * must be set before calling this function.
 */
bool squirrelSubject::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Subject where SubjectRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("SubjectRowID").toLongLong();
        ID = q.value("ID").toString();
        AlternateIDs = q.value("AltIDs").toString().split(",");
        GUID = q.value("GUID").toString();
        DateOfBirth = q.value("DateOfBirth").toDate();
        Sex = q.value("Sex").toString();
        Gender = q.value("Gender").toString();
        Ethnicity1 = q.value("Ethnicity1").toString();
        Ethnicity2 = q.value("Ethnicity2").toString();
        SequenceNumber = q.value("SequenceNumber").toInt();

        valid = true;
        return true;
    }
    else {
        valid = false;
        err = "objectID not found in database";
        return false;
    }
}


/* ------------------------------------------------------------ */
/* ----- Store ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelSubject::Store
 * @return true if successful
 *
 * This function will attempt to load the subject data from
 * the database. The subjectRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelSubject::Store() {

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Subject (ID, AltIDs, GUID, DateOfBirth, Sex, Gender, Ethnicity1, Ethnicity2, SequenceNumber, VirtualPath) values (:ID, :AltIDs, :GUID, :DateOfBirth, :Sex, :Gender, :Ethnicity1, :Ethnicity2, :SequenceNumber, :VirtualPath)");
        q.bindValue(":ID", ID);
        q.bindValue(":AltIDs", AlternateIDs.join(","));
        q.bindValue(":GUID", GUID);
        q.bindValue(":DateOfBirth", DateOfBirth);
        q.bindValue(":Sex", Sex);
        q.bindValue(":Gender", Gender);
        q.bindValue(":Ethnicity1", Ethnicity1);
        q.bindValue(":Ethnicity2", Ethnicity2);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Subject set ID = :ID, AltIDs = :AltIDs, GUID = :GUID, DateOfBirth = :DateOfBirth, Sex = :Sex, Gender = :Gender, Ethnicity1 = :Ethnicity1, Ethnicity2 = :Ethnicity2, SequenceNumber = :SequenceNumber, VirtualPath = :VirtualPath where SubjectRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":ID", ID);
        q.bindValue(":AltIDs", AlternateIDs.join(","));
        q.bindValue(":GUID", GUID);
        q.bindValue(":DateOfBirth", DateOfBirth);
        q.bindValue(":Sex", Sex);
        q.bindValue(":Gender", Gender);
        q.bindValue(":Ethnicity1", Ethnicity1);
        q.bindValue(":Ethnicity2", Ethnicity2);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- PrintDetails ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print subject details
 */
QString squirrelSubject::PrintDetails() {
    QString str;

    str += utils::Print("\t\t----- SUBJECT -----");
    str += utils::Print(QString("\t\tAlternateIDs: %1").arg(AlternateIDs.join(",")));
    str += utils::Print(QString("\t\tDateOfBirth: %1").arg(DateOfBirth.toString("yyyy-MM-dd")));
    str += utils::Print(QString("\t\tEthnicity1: %1").arg(Ethnicity1));
    str += utils::Print(QString("\t\tEthnicity2: %1").arg(Ethnicity2));
    str += utils::Print(QString("\t\tGUID: %1").arg(GUID));
    str += utils::Print(QString("\t\tGender: %1").arg(Gender));
    str += utils::Print(QString("\t\tSex: %1").arg(Sex));
    str += utils::Print(QString("\t\tSubjectID: %1").arg(ID));
    str += utils::Print(QString("\t\tSubjectRowID: %1").arg(objectID));
    str += utils::Print(QString("\t\tVirtualPath: %1").arg(VirtualPath()));

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintTree -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print subject tree
 */
QString squirrelSubject::PrintTree(bool isLast) {
    QString str;

    if (isLast)
        str += utils::Print(QString("   └─── ID %1  AltIDs %2  DOB %3  Sex %4").arg(ID).arg(AlternateIDs.join(",")).arg(DateOfBirth.toString("yyyy-MM-dd")).arg(Sex));
    else
        str += utils::Print(QString("   ├─── ID %1  AltIDs %2  DOB %3  Sex %4").arg(ID).arg(AlternateIDs.join(",")).arg(DateOfBirth.toString("yyyy-MM-dd")).arg(Sex));

    /* find all studies associated with this subject ... */
    QSqlQuery q(QSqlDatabase::database("squirrel"));

    int count(0);
    q.prepare("select count(*) 'count' from Study where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.first())
        count = q.value("count").toInt();

    q.prepare("select StudyRowID from Study where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    int i(0);
    while (q.next()) {
        qint64 studyRowID = q.value("StudyRowID").toLongLong();
        squirrelStudy stud;
        stud.SetObjectID(studyRowID);
        if (stud.Get()) {
            i++;
            if (count == i)
                str += stud.PrintTree(true);
            else
                str += stud.PrintTree(false);
        }
    }

    return str;
}


/* ------------------------------------------------------------ */
/* ----- CSVLine ---------------------------------------------- */
/* ------------------------------------------------------------ */
QString squirrelSubject::CSVLine() {
    QStringList data;

    data.append(ID);
    data.append(AlternateIDs.join(","));
    data.append(DateOfBirth.toString("yyyy-MM-dd"));
    data.append(Ethnicity1);
    data.append(Ethnicity2);
    data.append(GUID);
    data.append(Gender);
    data.append(Sex);

    QString line = "\"" + data.join("\",\"") + "\"";

    return line;
}


/* ------------------------------------------------------------ */
/* ----- Remove ----------------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrelSubject::Remove() {

    /* find all studies associated with this subject ... */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select StudyRowID from Study where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        int studyRowID = q.value("StudyRowID").toInt();

        /* ... delete any staged Study files */
        utils::RemoveStagedFileList(studyRowID, "study");

        /* ... delete all staged Series files */
        QSqlQuery q2(QSqlDatabase::database("squirrel"));
        q2.prepare("select SeriesRowID from Series where StudyRowID = :studyid");
        q2.bindValue(":studyid", studyRowID);
        utils::SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
        while (q2.next()) {
            int seriesRowID = q2.value("SeriesRowID").toInt();

            /* ... delete any staged Series files */
            utils::RemoveStagedFileList(seriesRowID, "series");
        }

        /* ... delete all series for those studies */
        q2.prepare("delete from Series where StudyRowID = :studyid");
        q2.bindValue(":studyid", studyRowID);
        utils::SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
    }

    /* delete the studies */
    q.prepare("delete from Study where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* delete the subject and any staged files */
    q.prepare("delete from Subject where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    utils::RemoveStagedFileList(objectID, "subject");

    /* in case anyone tries to use this object again */
    objectID = -1;
    valid = false;

    return true;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get JSON object for this subject
 * @return a JSON object containing the entire subject
 */
QJsonObject squirrelSubject::ToJSON() {
    QJsonObject json;

    json["SubjectID"] = ID;
    json["AlternateIDs"] = QJsonArray::fromStringList(AlternateIDs);
    json["GUID"] = GUID;
    json["DateOfBirth"] = DateOfBirth.toString("yyyy-MM-dd");
    json["Sex"] = Sex;
    json["Gender"] = Gender;
    json["Ethnicity1"] = Ethnicity1;
    json["Ethnicity2"] = Ethnicity2;
    json["VirtualPath"] = VirtualPath();

    /* add studies */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select StudyRowID from Study where SubjectRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QJsonArray JSONstudies;
    while (q.next()) {
        squirrelStudy s;
        s.SetObjectID(q.value("StudyRowID").toLongLong());
        if (s.Get()) {
            JSONstudies.append(s.ToJSON());
        }
    }
    if (JSONstudies.size() > 0) {
        json["StudyCount"] = JSONstudies.size();
        json["studies"] = JSONstudies;
    }

    /* add observations */
    q.prepare("select ObservationRowID from Observation where SubjectRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QJsonArray JSONobservations;
    while (q.next()) {
        squirrelObservation m;
        m.SetObjectID(q.value("ObservationRowID").toLongLong());
        if (m.Get()) {
            JSONobservations.append(m.ToJSON());
        }
    }
    if (JSONobservations.size() > 0) {
        json["ObservationCount"] = JSONobservations.size();
        json["observations"] = JSONobservations;
    }

    /* add interventions */
    q.prepare("select InterventionRowID from Intervention where SubjectRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QJsonArray JSONinterventions;
    while (q.next()) {
        squirrelIntervention d;
        d.SetObjectID(q.value("InterventionRowID").toLongLong());
        if (d.Get()) {
            JSONinterventions.append(d.ToJSON());
        }
    }
    if (JSONinterventions.size() > 0) {
        json["InterventionCount"] = JSONinterventions.size();
        json["interventions"] = JSONinterventions;
    }

    return json;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelSubject::VirtualPath() {

    QString vPath;
    QString subjectDir;

    /* get subject directory */
    if (subjectDirFormat == "orig")
        subjectDir = utils::CleanString(ID);
    else
        subjectDir = QString("%1").arg(SequenceNumber);

    vPath = QString("data/%1").arg(subjectDir);

    return vPath;
}


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
QList<QPair<QString,QString>> squirrelSubject::GetStagedFileList() {

    QList<QPair<QString,QString>> stagedList;

    /* add all studies staged files */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select StudyRowID from Study where SubjectRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelStudy s;
        s.SetObjectID(q.value("StudyRowID").toLongLong());
        if (s.Get()) {
            stagedList += s.GetStagedFileList();
        }
    }

    return stagedList;
}


/* ------------------------------------------------------------ */
/* ----- GetNextStudyNumber ----------------------------------- */
/* ------------------------------------------------------------ */
int squirrelSubject::GetNextStudyNumber() {
    int nextStudyNum = 1;

    /* get the next study number for this subject */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select max(StudyNumber) 'Max' from Study where SubjectRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next())
        nextStudyNum = q.value("Max").toInt() + 1;

    return nextStudyNum;
}
