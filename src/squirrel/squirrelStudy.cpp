/* ------------------------------------------------------------------------------
  Squirrel study.cpp
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

#include "squirrelStudy.h"
#include "utils.h"
//#include "squirrelSubject.h"

/* ------------------------------------------------------------ */
/* ----- study ------------------------------------------------ */
/* ------------------------------------------------------------ */
squirrelStudy::squirrelStudy(QString dbID)
{
    databaseUUID = dbID;

    debug = false;
    objectID = -1;
    studyDirFormat = "orig";
    subjectDirFormat = "orig";
    valid = false;

    AgeAtStudy = 0.0;
    DateTime = QDateTime::currentDateTime();
    DayNumber = 0;
    Height = 0.0;
    Modality = "UNKNOWN";
    SequenceNumber = -1;
    StudyNumber = -1;
    TimePoint = 0;
    Weight = 0.0;
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

    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select * from Study where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next()) {

        /* get the data */
        objectID = q.value("StudyRowID").toLongLong();
        subjectRowID = q.value("SubjectRowID").toLongLong();
        AgeAtStudy = q.value("Age").toDouble();
        DateTime = q.value("Datetime").toDateTime();
        DayNumber = q.value("DayNumber").toInt();
        Description = q.value("Description").toString();
        Equipment = q.value("StudyRowID").toString();
        Height = q.value("Height").toDouble();
        Modality = q.value("Modality").toString();
        Notes = q.value("Notes").toString();
        SequenceNumber = q.value("SequenceNumber").toInt();
        StudyNumber = q.value("StudyNumber").toInt();
        StudyUID = q.value("StudyUID").toString();
        TimePoint = q.value("TimePoint").toInt();
        VisitType = q.value("VisitType").toString();
        Weight = q.value("Weight").toDouble();

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

    QSqlQuery q(QSqlDatabase::database(databaseUUID));

    /* insert if the object doesn't exist ... */
    if (objectID < 0) {
        q.prepare("insert or ignore into Study (SubjectRowID, StudyNumber, Datetime, Age, Height, Weight, Modality, Description, StudyUID, VisitType, DayNumber, Timepoint, Equipment, Notes, SequenceNumber, VirtualPath) values (:SubjectRowID, :StudyNumber, :Datetime, :Age, :Height, :Weight, :Modality, :Description, :StudyUID, :VisitType, :DayNumber, :Timepoint, :Equipment, :Notes, :SequenceNumber, :VirtualPath)");
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
        q.bindValue(":Notes", Notes);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        objectID = q.lastInsertId().toInt();
    }
    /* ... otherwise update */
    else {
        q.prepare("update Study set SubjectRowID = :SubjectRowID, StudyNumber = :StudyNumber, Datetime = :Datetime, Age = :Age, Height = :Height, Weight = :Weight, Modality = :Modality, Description = :Description, StudyUID = :StudyUID, VisitType = :VisitType, DayNumber = :DayNumber, Timepoint = :Timepoint, Equipment = :Equipment, Notes = :Notes, SequenceNumber = :SequenceNumber, VirtualPath = :VirtualPath where StudyRowID = :id");
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
        q.bindValue(":Notes", Notes);
        q.bindValue(":SequenceNumber", SequenceNumber);
        q.bindValue(":VirtualPath", VirtualPath());
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return true;
}


/* ------------------------------------------------------------ */
/* ----- Remove ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Remove this subject from the squirrel object in memory
 * @return true if successful, false otherwise
 */
bool squirrelStudy::Remove() {
    /* ... delete any staged Study files */
    utils::RemoveStagedFileList(databaseUUID, objectID, Study);

    /* ... delete all staged Series files */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select SeriesRowID from Series where StudyRowID = :studyid");
    q.bindValue(":studyid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        /* ... delete any staged Series files */
        utils::RemoveStagedFileList(databaseUUID, q.value("SeriesRowID").toInt(), Series);
    }

    /* ... delete all series for those studies */
    q.prepare("delete from Series where StudyRowID = :studyid");
    q.bindValue(":studyid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* delete the study */
    q.prepare("delete from Study where SubjectRowID = :subjectid");
    q.bindValue(":subjectid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    utils::RemoveStagedFileList(databaseUUID, objectID, Subject);

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
QString squirrelStudy::PrintStudy(PrintFormat p) {
    QString str;

    if (p == BasicList) {
        QString s;

        s += QString("\t%1").arg(StudyNumber);
        s += QString("\t%1").arg(AgeAtStudy);
        s += QString("\t%1").arg(Description);
        s += QString("\t%1").arg(Modality);
        s += QString("\t%1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss"));
        str += utils::Print(s);
    }
    else if (p == FullList) {
        QString s;

        s += QString("%1").arg(AgeAtStudy);
        s += QString("\t%1").arg(DayNumber);
        s += QString("\t%1").arg(Description);
        s += QString("\t%1").arg(Equipment);
        s += QString("\t%1").arg(Height);
        s += QString("\t%1").arg(Modality);
        s += QString("\t%1").arg(Notes);
        s += QString("\t%1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss"));
        s += QString("\t%1").arg(StudyNumber);
        s += QString("\t%1").arg(StudyUID);
        s += QString("\t%1").arg(TimePoint);
        s += QString("\t%1").arg(VirtualPath());
        s += QString("\t%1").arg(VisitType);
        s += QString("\t%1").arg(Weight);
        if (debug) {
            s += QString("\t%1").arg(objectID);
            s += QString("\t%1").arg(subjectRowID);
        }
        str += utils::Print(s);
    }
    else {
        str += utils::Print("\t\t\t----- STUDY -----");
        str += utils::Print(QString("\t\t\tAgeAtStudy: %1").arg(AgeAtStudy));
        str += utils::Print(QString("\t\t\tDayNumber: %1").arg(DayNumber));
        str += utils::Print(QString("\t\t\tDescription: %1").arg(Description));
        str += utils::Print(QString("\t\t\tEquipment: %1").arg(Equipment));
        str += utils::Print(QString("\t\t\tHeight: %1 m").arg(Height));
        str += utils::Print(QString("\t\t\tModality: %1").arg(Modality));
        str += utils::Print(QString("\t\t\tNotes: %1").arg(Notes));
        str += utils::Print(QString("\t\t\tStudyDatetime: %1").arg(DateTime.toString("yyyy-MM-dd HH:mm:ss")));
        str += utils::Print(QString("\t\t\tStudyNumber: %1").arg(StudyNumber));
        str += utils::Print(QString("\t\t\tStudyRowID: %1").arg(objectID));
        str += utils::Print(QString("\t\t\tStudyUID: %1").arg(StudyUID));
        str += utils::Print(QString("\t\t\tSubjectRowID: %1").arg(subjectRowID));
        str += utils::Print(QString("\t\t\tTimePoint: %1").arg(TimePoint));
        str += utils::Print(QString("\t\t\tVirtualPath: %1").arg(VirtualPath()));
        str += utils::Print(QString("\t\t\tVisitType: %1").arg(VisitType));
        str += utils::Print(QString("\t\t\tWeight: %1 kg").arg(Weight));
    }

    return str;
}


/* ------------------------------------------------------------ */
/* ----- PrintTree -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print study tree items
 */
QString squirrelStudy::PrintTree(bool isLast) {
    QString str;

    QString dateTime = DateTime.toString("yyyy-MM-dd HH:mm:ss");
    if (dateTime == "")
        dateTime = "(blankDateTime)";

    if (isLast)
        str += utils::Print(QString("        +--- Study %1 (%2)  %3").arg(StudyNumber).arg(Modality).arg(dateTime));
    else
        str += utils::Print(QString("   |    |--- Study %1 (%2)  %3").arg(StudyNumber).arg(Modality).arg(dateTime));

    /* print all series for this study */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select SeriesRowID from Series where StudyRowID = :studyid");
    q.bindValue(":studyid", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        qint64 seriesRowID = q.value("SeriesRowID").toLongLong();
        squirrelSeries ser(databaseUUID);
        ser.SetObjectID(seriesRowID);
        if (ser.Get()) {
            str += ser.PrintTree(false);
        }
    }

    return str;
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get a JSON object for this study
 * @return JSON object
 */
QJsonObject squirrelStudy::ToJSON() {
	QJsonObject json;

    json["AgeAtStudy"] = AgeAtStudy;
    json["DayNumber"] = DayNumber;
    json["Description"] = Description;
    json["Equipment"] = Equipment;
    json["Height"] = Height;
    json["Modality"] = Modality;
    json["Notes"] = Notes;
    json["StudyDatetime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
    json["StudyNumber"] = StudyNumber;
    json["StudyUID"] = StudyUID;
    json["TimePoint"] = TimePoint;
    json["VirtualPath"] = VirtualPath();
    json["VisitType"] = VisitType;
    json["Weight"] = Weight;

    /* add all the series */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select SeriesRowID from Series where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    QJsonArray JSONseries;
    while (q.next()) {
        squirrelSeries s(databaseUUID);
        s.SetObjectID(q.value("SeriesRowID").toLongLong());
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
        squirrelAnalysis a(databaseUUID);
        a.SetObjectID(q.value("AnalysisRowID").toLongLong());
        if (a.Get()) {
            JSONanalysis.append(a.ToJSON());
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
/**
 * @brief Get the virtual path for this subject within the squirrel package
 * @return The virtual path
 */
QString squirrelStudy::VirtualPath() {

    QString vPath;
    QString subjectDir;
    QString studyDir;

    /* get parent subject directory */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
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


/* ------------------------------------------------------------ */
/* ----- GetStagedFileList ------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief Get a list of all staged files from all child series.
 * The list is a list of pairs of physical disk path & virtual path
 * Example: "/path/to/file.txt" , "data/S1234/1/2/file.txt"
 * @return Hash of staged files
 */
QList<QPair<QString,QString>> squirrelStudy::GetStagedFileList() {

    QList<QPair<QString,QString>> stagedList;

    /* add all the series staged files */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select SeriesRowID from Series where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelSeries s(databaseUUID);
        s.SetObjectID(q.value("SeriesRowID").toLongLong());
        if (s.Get()) {
            stagedList += s.GetStagedFileList();
        }
    }

    /* add all the analysis staged files */
    q.prepare("select AnalysisRowID from Analysis where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    while (q.next()) {
        squirrelAnalysis a(databaseUUID);
        a.SetObjectID(q.value("AnalysisRowID").toLongLong());
        if (a.Get()) {
            stagedList += a.GetStagedFileList();
        }
    }

    return stagedList;
}


/* ------------------------------------------------------------ */
/* ----- GetNextSeriesNumber ---------------------------------- */
/* ------------------------------------------------------------ */
int squirrelStudy::GetNextSeriesNumber() {
    int nextSeriesNum = 1;

    /* get the next series number for this study */
    QSqlQuery q(QSqlDatabase::database(databaseUUID));
    q.prepare("select max(SeriesNumber) 'Max' from Series where StudyRowID = :id");
    q.bindValue(":id", objectID);
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.next())
        nextSeriesNum = q.value("Max").toInt() + 1;

    return nextSeriesNum;
}


/* ------------------------------------------------------------ */
/* ----- GetData ---------------------------------------------- */
/* ------------------------------------------------------------ */
QHash<QString, QString> squirrelStudy::GetData(DatasetType d) {

    QHash<QString, QString> data;

    switch (d) {
        case DatasetID:
            data["Study.Number"] = QString("%1").arg(StudyNumber);
            break;
        case DatasetBasic:
            data["Study.AgeAtStudy"] = QString("%1").arg(AgeAtStudy);
            data["Study.Description"] = Description;
            data["Study.Modality"] = Modality;
            data["Study.DateTime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
            data["Study.Number"] = QString("%1").arg(StudyNumber);
            break;
        case DatasetFull:
            data["Study.AgeAtStudy"] = QString("%1").arg(AgeAtStudy);
            data["Study.DateTime"] = DateTime.toString("yyyy-MM-dd HH:mm:ss");
            data["Study.DayNumber"] = QString("%1").arg(DayNumber);
            data["Study.Description"] = Description;
            data["Study.Equipment"] = Equipment;
            data["Study.Height"] = QString("%1").arg(Height);
            data["Study.Modality"] = Modality;
            data["Study.Notes"] = Notes;
            data["Study.Number"] = QString("%1").arg(StudyNumber);
            data["Study.StudyUID"] = StudyUID;
            data["Study.TimePoint"] = QString("%1").arg(TimePoint);
            data["Study.VirtualPath"] = VirtualPath();
            data["Study.VisitType"] = VisitType;
            data["Study.Weight"] = QString("%1").arg(Weight);
            break;
        default:
            break;
    }

    return data;
}
