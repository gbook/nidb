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
        number = q.value("StudyNumber").toLongLong();
        dateTime = q.value("Datetime").toDateTime();
        ageAtStudy = q.value("Age").toDouble();
        height = q.value("Height").toDouble();
        weight = q.value("Weight").toDouble();
        modality = q.value("Modality").toString();
        description = q.value("Description").toString();
        studyUID = q.value("StudyUID").toString();
        visitType = q.value("VisitType").toString();
        dayNumber = q.value("DayNumber").toInt();
        timePoint = q.value("TimePoint").toInt();
        equipment = q.value("StudyRowID").toString();
        sequence = q.value("Sequence").toInt();
        //virtualPath = q.value("VirtualPath").toString();

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
            number = q.value("Max").toInt() + 1;

        q.prepare("insert or ignore into Study (SubjectRowID, StudyNumber, Datetime, Age, Height, Weight, Modality, Description, StudyUID, VisitType, DayNumber, Timepoint, Equipment, Sequence, VirtualPath) values (:SubjectRowID, :StudyNumber, :Datetime, :Age, :Height, :Weight, :Modality, :Description, :StudyUID, :VisitType, :DayNumber, :Timepoint, :Equipment, :Sequence, :VirtualPath)");
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":StudyNumber", number);
        q.bindValue(":Datetime", dateTime);
        q.bindValue(":Age", ageAtStudy);
        q.bindValue(":Height", height);
        q.bindValue(":Weight", weight);
        q.bindValue(":Modality", modality);
        q.bindValue(":Description", description);
        q.bindValue(":StudyUID", studyUID);
        q.bindValue(":VisitType", visitType);
        q.bindValue(":DayNumber", dayNumber);
        q.bindValue(":Timepoint", timePoint);
        q.bindValue(":Equipment", equipment);
        q.bindValue(":Sequence", sequence);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Study set SubjectRowID = :SubjectRowID, StudyNumber = :StudyNumber, Datetime = :Datetime, Age = :Age, Height = :Height, Weight = :Weight, Modality = :Modality, Description = :Description, StudyUID = :StudyUID, VisitType = :VisitType, DayNumber = :DayNumber, Timepoint = :Timepoint, Equipment = :Equipment, Sequence = :Sequence, VirtualPath = :VirtualPath where StudyRowID = :id");
        q.bindValue(":id", objectID);
        q.bindValue(":SubjectRowID", subjectRowID);
        q.bindValue(":StudyNumber", number);
        q.bindValue(":Datetime", dateTime);
        q.bindValue(":Age", ageAtStudy);
        q.bindValue(":Height", height);
        q.bindValue(":Weight", weight);
        q.bindValue(":Modality", modality);
        q.bindValue(":Description", description);
        q.bindValue(":StudyUID", studyUID);
        q.bindValue(":VisitType", visitType);
        q.bindValue(":DayNumber", dayNumber);
        q.bindValue(":Timepoint", timePoint);
        q.bindValue(":Equipment", equipment);
        q.bindValue(":Sequence", sequence);
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
    utils::Print(QString("\t\t\tAgeAtStudy: %1").arg(ageAtStudy));
    utils::Print(QString("\t\t\tDatetime: %1").arg(dateTime.toString("yyyy-MM-dd HH:mm:ss")));
    utils::Print(QString("\t\t\tDayNumber: %1").arg(dayNumber));
    utils::Print(QString("\t\t\tDescription: %1").arg(description));
    utils::Print(QString("\t\t\tEquipment: %1").arg(equipment));
    utils::Print(QString("\t\t\tHeight: %1 m").arg(height));
    utils::Print(QString("\t\t\tModality: %1").arg(modality));
    utils::Print(QString("\t\t\tStudyNumber: %1").arg(number));
    utils::Print(QString("\t\t\tStudyRowID: %1").arg(objectID));
    utils::Print(QString("\t\t\tStudyUID: %1").arg(studyUID));
    utils::Print(QString("\t\t\tSubjectRowID: %1").arg(subjectRowID));
    utils::Print(QString("\t\t\tTimePoint: %1").arg(timePoint));
    utils::Print(QString("\t\t\tVirtualPath: %1").arg(VirtualPath()));
    utils::Print(QString("\t\t\tVisitType: %1").arg(visitType));
    utils::Print(QString("\t\t\tWeight: %1 kg").arg(weight));
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

    json["AgeAtStudy"] = ageAtStudy;
    json["Datetime"] = dateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["DayNumber"] = dayNumber;
    json["Description"] = description;
    json["Equipment"] = equipment;
    json["Height"] = height;
    json["Modality"] = modality;
    json["StudyNumber"] = number;
    json["StudyUID"] = studyUID;
    json["TimePoint"] = timePoint;
    json["VirtualPath"] = VirtualPath();
    json["VisitType"] = visitType;
    json["Weight"] = weight;

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
        json["NumSeries"] = JSONseries.size();
        json["series"] = JSONseries;
    }

    // QJsonArray JSONseries;
    // for (int i=0; i<seriesList.size(); i++) {
    // 	JSONseries.append(seriesList[i].ToJSON());
    // }
 //    json["NumSeries"] = JSONseries.size();
    // json["series"] = JSONseries;

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
        json["NumberAnalyses"] = JSONanalysis.size();
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
    q.prepare("select ID, Sequence from Subject where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", subjectRowID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {
        if (subjectDirFormat == "orig")
            subjectDir = utils::CleanString(q.value("ID").toString());
        else
            subjectDir = QString("%1").arg(q.value("Sequence").toInt());
    }

    /* get study directory */
    if (studyDirFormat == "orig")
        studyDir = QString("%1").arg(number);
    else
        studyDir = QString("%1").arg(sequence);

    vPath = QString("data/%1/%2").arg(subjectDir).arg(studyDir);

    return vPath;
}
