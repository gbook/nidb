/* ------------------------------------------------------------------------------
  Squirrel study.cpp
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

#include "squirrelStudy.h"
#include "utils.h"
#include <iostream>
#include <exception>

/* ------------------------------------------------------------ */
/* ----- study ------------------------------------------------ */
/* ------------------------------------------------------------ */
squirrelStudy::squirrelStudy()
{
    AgeAtStudy = 0.0;
    DateTime = QDateTime::currentDateTime();
    DayNumber = 0;
    Height = 0.0;
    Modality = "UNKNOWN";
    SequenceNumber = -1;
    StudyNumber = -1;
    TimePoint = 0;
    Weight = 0.0;

    objectID = -1;
    studyDirFormat = "orig";
    subjectDirFormat = "orig";
    valid = false;
}


/* ------------------------------------------------------------ */
/* ----- Get -------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrelStudy::Get
 * @return true if successful
 *
 * This function will attempt to load the study data from
 * the database. The studyRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelStudy::Get() {
    if (objectID < 0) {
        valid = false;
        err = "objectID is not set";
        return false;
    }

    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select * from Study where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("StudyRowID").toLongLong();
        subjectRowID = q.value("SubjectRowID").toLongLong();
        StudyNumber = q.value("StudyNumber").toLongLong();
        DateTime = q.value("Datetime").toDateTime();
        AgeAtStudy = q.value("Age").toDouble();
        Height = q.value("Height").toDouble();
        Weight = q.value("Weight").toDouble();
        Modality = q.value("Modality").toString();
        Description = q.value("Description").toString();
        StudyUID = q.value("StudyUID").toString();
        VisitType = q.value("VisitType").toString();
        DayNumber = q.value("DayNumber").toInt();
        TimePoint = q.value("TimePoint").toInt();
        Equipment = q.value("StudyRowID").toString();
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
 * @brief squirrelStudy::Store
 * @return true if successful
 *
 * This function will attempt to load the study data from
 * the database. The studyRowID must be set before calling
 * this function. If the object exists in the DB, it will return true.
 * Otherwise it will return false.
 */
bool squirrelStudy::Store() {

    QSqlQuery q(QSqlDatabase::database("squirrel"));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        /* get the next study number */
        q.prepare("select max(StudyNumber) 'Max' from Study where StudyRowID = :id");
        q.bindValue(":id", objectID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.next())
            StudyNumber = q.value("Max").toInt() + 1;

        q.prepare("insert or ignore into Study (SubjectRowID, StudyNumber, Datetime, Age, Height, Weight, Modality, Description, StudyUID, VisitType, DayNumber, Timepoint, Equipment, SequenceNumber, VirtualPath) values (:SubjectRowID, :StudyNumber, :Datetime, :Age, :Height, :Weight, :Modality, :Description, :StudyUID, :VisitType, :DayNumber, :Timepoint, :Equipment, :SequenceNumber, :VirtualPath)");
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":StudyNumber", StudyNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":Age", AgeAtStudy);
        q.bindValue(":Height", Height);
        q.bindValue(":Weight", Weight);
        q.bindValue(":Modality", Modality);
        q.bindValue(":Description", Description);
        q.bindValue(":StudyUID", StudyUID);
        q.bindValue(":VisitType", VisitType);
        q.bindValue(":DayNumber", DayNumber);
        q.bindValue(":Timepoint", TimePoint);
        q.bindValue(":Equipment", Equipment);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Study set SubjectRowID = :SubjectRowID, StudyNumber = :StudyNumber, Datetime = :Datetime, Age = :Age, Height = :Height, Weight = :Weight, Modality = :Modality, Description = :Description, StudyUID = :StudyUID, VisitType = :VisitType, DayNumber = :DayNumber, Timepoint = :Timepoint, Equipment = :Equipment, SequenceNumber = :SequenceNumber, VirtualPath = :VirtualPath where StudyRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":StudyNumber", StudyNumber);
        q.bindValue(":Datetime", DateTime);
        q.bindValue(":Age", AgeAtStudy);
        q.bindValue(":Height", Height);
        q.bindValue(":Weight", Weight);
        q.bindValue(":Modality", Modality);
        q.bindValue(":Description", Description);
        q.bindValue(":StudyUID", StudyUID);
        q.bindValue(":VisitType", VisitType);
        q.bindValue(":DayNumber", DayNumber);
        q.bindValue(":Timepoint", TimePoint);
        q.bindValue(":Equipment", Equipment);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Remove ----------------------------------------------- */
/* ------------------------------------------------------------ */
bool squirrelStudy::Remove() {


    /* ... delete any staged Study files */
    utils::RemoveStagedFileList(objectID, "study");

    /* ... delete all staged Series files */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select SeriesRowID from Series where StudyRowID = :studyid");
    q.bindValue(":studyid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        /* ... delete any staged Series files */
        utils::RemoveStagedFileList(q.value("SeriesRowID").toInt(), "series");
    }

    /* ... delete all series for those studies */
    q.prepare("delete from Series where StudyRowID = :studyid");
    q.bindValue(":studyid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* delete the study */
    q.prepare("delete from Study where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    utils::RemoveStagedFileList(objectID, "subject");

    /* in case anyone tries to use this object again */
    objectID = -1;
    valid = false;

    return true;
}


/* ------------------------------------------------------------ */
/* ----- PrintStudy ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print study details
 */
void squirrelStudy::PrintStudy() {

    utils::Print("\t\t\t----- STUDY -----");
    utils::Print(QString("\t\t\tAgeAtStudy: %1").arg(AgeAtStudy));
    utils::Print(QString("\t\t\tDayNumber: %1").arg(DayNumber));
    utils::Print(QString("\t\t\tDescription: %1").arg(Description));
    utils::Print(QString("\t\t\tEquipment: %1").arg(Equipment));
    utils::Print(QString("\t\t\tHeight: %1 m").arg(Height));
    utils::Print(QString("\t\t\tModality: %1").arg(Modality));
    utils::Print(QString("\t\t\tStudyDatetime: %1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\tStudyNumber: %1").arg(StudyNumber));
    utils::Print(QString("\t\t\tStudyRowID: %1").arg(objectID));
    utils::Print(QString("\t\t\tStudyUID: %1").arg(StudyUID));
    utils::Print(QString("\t\t\tSubjectRowID: %1").arg(subjectRowID));
    utils::Print(QString("\t\t\tTimePoint: %1").arg(TimePoint));
    utils::Print(QString("\t\t\tVirtualPath: %1").arg(VirtualPath()));
    utils::Print(QString("\t\t\tVisitType: %1").arg(VisitType));
    utils::Print(QString("\t\t\tWeight: %1 kg").arg(Weight));
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a JSON object for this study
 * @return JSON object containing the study
 */
QJsonObject squirrelStudy::ToJSON() {
	QJsonObject json;

    json["AgeAtStudy"] = AgeAtStudy;
    json["StudyDatetime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["DayNumber"] = DayNumber;
    json["Description"] = Description;
    json["Equipment"] = Equipment;
    json["Height"] = Height;
    json["Modality"] = Modality;
    json["StudyNumber"] = StudyNumber;
    json["StudyUID"] = StudyUID;
    json["TimePoint"] = TimePoint;
    json["VirtualPath"] = VirtualPath();
    json["VisitType"] = VisitType;
    json["Weight"] = Weight;

    /* add all the series */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select SeriesRowID from Series where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QJsonArray JSONseries;
    while (q.next()) {
        squirrelSeries s;
        s.SetObjectID(q.value("SeriesRowID").toInt());
        if (s.Get()) {
            JSONseries.append(s.ToJSON());
        }
    }
    if (JSONseries.size() > 0) {
        json["SeriesCount"] = JSONseries.size();
        json["series"] = JSONseries;
    }

    /* add all the analyses */
    q.prepare("select AnalysisRowID from Analysis where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QJsonArray JSONanalysis;
    while (q.next()) {
        squirrelSeries s;
        s.SetObjectID(q.value("AnalysisRowID").toInt());
        if (s.Get()) {
            JSONanalysis.append(s.ToJSON());
        }
    }
    if (JSONanalysis.size() > 0) {
        json["AnalysisCount"] = JSONanalysis.size();
        json["analyses"] = JSONanalysis;
    }

	return json;
}


/* ------------------------------------------------------------ */
/* ----- VirtualPath ------------------------------------------ */
/* ------------------------------------------------------------ */
QString squirrelStudy::VirtualPath() {

    QString vPath;
    QString subjectDir;
    QString studyDir;

    /* get parent subject directory */
    QSqlQuery q(QSqlDatabase::database("squirrel"));
    q.prepare("select ID, SequenceNumber from Subject where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        if (subjectDirFormat == "orig")
            subjectDir = utils::CleanString(q.value("ID").toString());
        else
            subjectDir = QString("%1").arg(q.value("SequenceNumber").toInt());
    }

    /* get study directory */
    if (studyDirFormat == "orig")
        studyDir = QString("%1").arg(StudyNumber);
    else
        studyDir = QString("%1").arg(SequenceNumber);

    vPath = QString("data/%1/%2").arg(subjectDir).arg(studyDir);

    return vPath;
}
