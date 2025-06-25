/* ------------------------------------------------------------------------------
  Squirrel modify.cpp
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

#include "modify.h"
#include "utils.h"
#include <iostream>
#include <vector>
#include <iomanip>

modify::modify() {
}


/* ---------------------------------------------------------------------------- */
/* ----- DoModify ------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
bool modify::DoModify(QString packagePath, QString operation, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m) {

    //ObjectType object = squirrel::ObjectTypeToEnum(objectType);

    if (operation == "add") {
        if (AddObject(packagePath, object, dataPath, objectData, objectID, subjectID, studyNum, m))
            return true;
        else
            return false;
    }
    else if (operation == "remove") {
        if (RemoveObject(packagePath, object, dataPath, objectData, objectID, subjectID, studyNum, m))
            return true;
        else
            return false;
    }
    else if (operation == "update") {
        if (UpdateObject(packagePath, object, dataPath, objectData, objectID, subjectID, studyNum, m))
            return true;
        else
            return false;
    }
    else if (operation == "splitbymodality") {
        if (SplitByModality(packagePath, dataPath, objectData, objectID, m))
            return true;
        else
            return false;
    }
    else if (operation == "removephi") {
        if (RemovePHI(packagePath, dataPath, m))
            return true;
        else
            return false;
    }
    else {
        m = "Invalid operation [" + operation + "] specified";
        return false;
    }
}


/* ---------------------------------------------------------------------------- */
/* ----- AddObject ------------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
bool modify::AddObject(QString packagePath, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m) {

    /* check if the user should have specified a path */
    if ((object == Series) || (object == Analysis) || (object == Experiment) || (object == Pipeline) || (object == GroupAnalysis)) {
    }
    else {

        /* check if that path is specified */
        if (dataPath == "") {
            m = "No datapath specified for this object type. A datapath must be specified.";
            return false;
        }

        /* check if the specified path exists */
        if (!utils::DirectoryExists(dataPath)) {
            m = QString("Specified datapath [%1] does not exist").arg(dataPath);
            return false;
        }
    }

    /* get the object data */
    QHash<QString, QString> vars;
    QStringList metadata = objectData.split("&");
    foreach (QString keyvalue, metadata) {
        QStringList keyVal = keyvalue.split("=");
        if (keyVal.count() == 2)
            vars[keyVal[0]] = keyVal[1];
        else {
            m = QString("Malformed subject metadata string [%1]. Inconsistent key/value pair count").arg(objectData);
            return false;
        }
    }

    /* load the package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetWriteLog(true);
    if (!sqrl->Read()) {
        m = QString("Package unreadable [%1] already exists in package").arg(vars["ID"]);
        delete sqrl;
        return false;
    }

    /* ----- subject ----- */
    if (object == Subject) {
        qint64 subjectRowID;
        subjectRowID = sqrl->FindSubject(vars["ID"]);
        if (subjectRowID < 0) {
            squirrelSubject subject(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel Subject [%1]").arg(vars["ID"]));
            subject.ID = vars["ID"];
            subject.AlternateIDs = vars["AlternateIDs"].split(",");
            subject.GUID = vars["GUID"];
            subject.DateOfBirth = QDate::fromString(vars["DateOfBirth"], "yyyy-MM-dd");
            subject.Sex = vars["Gender"];
            subject.Gender = vars["Gender"];
            subject.Ethnicity1 = vars["Ethnicity1"];
            subject.Ethnicity2 = vars["Ethnicity2"];
            subject.Notes = vars["Notes"];
            subject.Store();
            /* resequence the newly added subject */
            sqrl->ResequenceSubjects();
        }
        else {
            m = QString("Subject with ID [%1] already exists in package").arg(vars["ID"]);
            delete sqrl;
            return false;
        }
    }
    /* ----- study ----- */
    else if (object == Study) {
        qint64 subjectRowID = sqrl->FindSubject(subjectID);
        qint64 studyRowID = sqrl->FindStudy(subjectID, vars["StudyNumber"].toInt());
        if (studyRowID < 0) {
            squirrelStudy study(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel Subject [%1]").arg(vars["ID"]));
            study.StudyNumber = vars["StudyNumber"].toInt();
            study.DateTime = QDateTime::fromString(vars["Datetime"], "yyyy-MM-dd HH:mm:ss");
            study.AgeAtStudy = vars["AgeAtStudy"].toDouble();
            study.Modality = vars["Modality"];
            study.Height = vars["Height"].toDouble();
            study.Weight = vars["Weight"].toDouble();
            study.Description = vars["Description"];
            study.StudyUID = vars["StudyUID"];
            study.VisitType = vars["VisitType"];
            study.DayNumber = vars["DayNumber"].toInt();
            study.TimePoint = vars["TimePoint"].toInt();
            study.Equipment = vars["Equipment"];
            study.Notes = vars["Notes"];
            study.subjectRowID = subjectRowID;
            study.Store();
            //studyRowID = study.GetObjectID();
            /* resequence the newly added subject */
            sqrl->ResequenceStudies(subjectRowID);
        }
        else {
            m = QString("Study with StudyNumber [%1] already exists for subject [%2] in package").arg(vars["StudyNumber"]).arg(subjectID);
            delete sqrl;
            return false;
        }
    }
    /* ----- series ----- */
    else if (object == Series) {
        qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
        qint64 seriesRowID = sqrl->FindSeries(subjectID, studyNum, vars["SeriesNumber"].toInt());
        if (seriesRowID < 0) {
            squirrelSeries series(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel Series [%1]").arg(vars["SeriesNumber"]));
            series.SeriesNumber = vars["StudyNumber"].toInt();
            series.DateTime = QDateTime::fromString(vars["Datetime"], "yyyy-MM-dd HH:mm:ss");
            series.Description = vars["Description"];
            series.Protocol = vars["Protocol"];
            series.SeriesUID = vars["SeriesUID"];
            series.stagedBehFiles = vars["StagedBehFiles"].split(",");
            series.stagedFiles = vars["StagedFiles"].split(",");
            series.studyRowID = studyRowID;
            series.Store();
            /* resequence the newly added subject */
            sqrl->ResequenceSeries(studyRowID);
        }
        else {
            m = QString("Series with SeriesNumber [%1] already exists for study [%2] and subject [%3] in package").arg(vars["SeriesNumber"]).arg(studyNum).arg(subjectID);
            delete sqrl;
            return false;
        }
    }
    /* ----- observation ----- */
    else if (object == Observation) {
        qint64 subjectRowID = sqrl->FindSubject(subjectID);
        if (subjectRowID < 0) {
            m = QString("Subject [%1] not found in package").arg(subjectID);
            delete sqrl;
            return false;
        }
        else {
            if (dataPath == "") {
                squirrelObservation observation(sqrl->GetDatabaseUUID());
                sqrl->Log(QString("Creating squirrel Observation [%1]").arg(vars["ObservationName"]));
                observation.DateEnd = QDateTime::fromString(vars["DateEnd"], "yyyy-MM-dd HH:mm:ss");
                observation.DateRecordCreate = QDateTime::fromString(vars["DateRecordCreate"], "yyyy-MM-dd HH:mm:ss");
                observation.DateRecordEntry = QDateTime::fromString(vars["DateRecordEntry"], "yyyy-MM-dd HH:mm:ss");
                observation.DateRecordModify = QDateTime::fromString(vars["DateRecordModify"], "yyyy-MM-dd HH:mm:ss");
                observation.DateStart = QDateTime::fromString(vars["DateStart"], "yyyy-MM-dd HH:mm:ss");
                observation.Description = vars["Description"];
                observation.Duration = vars["Duration"].toDouble();
                observation.InstrumentName = vars["InstrumentName"];
                observation.ObservationName = vars["ObservationName"];
                observation.Notes = vars["Notes"];
                observation.Rater = vars["Rater"];
                observation.Value = vars["Value"];
                observation.subjectRowID = subjectRowID;
                observation.Store();
            }
            else {
                /* load the observations from a CSV or TSV file */
                if (utils::FileExists(dataPath)) {

                    utils::indexedHash csv;
                    QStringList cols;
                    QString m;

                    /* if csv, read csv */
                    if (dataPath.endsWith(".csv", Qt::CaseInsensitive)) {
                        if (utils::ParseCSV(dataPath, csv, cols, m)) {
                        }
                    }
                    else if (dataPath.endsWith(".tsv", Qt::CaseInsensitive)) {
                        if (utils::ParseTSV(dataPath, csv, cols, m)) {
                            //sqrl->Log(QString("Successfuly read [%1] into [%2] rows").arg(f).arg(tsv.size()));
                        }
                        else {
                            m = QString("File containing observations [%1] not found").arg(dataPath);
                            delete sqrl;
                            return false;
                        }
                    }
                    else {
                        // unrecognized file extension
                        return false;
                    }

                    /* load all the observations into squirrel */
                    for (int i=0; i<csv.size(); i++) {
                        QString sesid = csv[i]["session_id"];
                        QString datetime = csv[i]["acq_time"];
                    }

                }
                else {
                    m = QString("File containing observations [%1] not found").arg(dataPath);
                    delete sqrl;
                    return false;
                }
            }
        }
    }
    /* ----- intervention ----- */
    else if (object == Intervention) {
        qint64 subjectRowID = sqrl->FindSubject(subjectID);
        if (subjectRowID < 0) {
            m = QString("Subject [%1] not found in package").arg(subjectID);
            delete sqrl;
            return false;
        }
        else {
            squirrelIntervention intervention(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel intervention [%1]").arg(vars["InterventionName"]));
            intervention.DateEnd = QDateTime::fromString(vars["DateEnd"], "yyyy-MM-dd HH:mm:ss");
            intervention.DateRecordCreate = QDateTime::fromString(vars["DateRecordCreate"], "yyyy-MM-dd HH:mm:ss");
            intervention.DateRecordEntry = QDateTime::fromString(vars["DateRecordEntry"], "yyyy-MM-dd HH:mm:ss");
            intervention.DateRecordModify = QDateTime::fromString(vars["DateRecordModify"], "yyyy-MM-dd HH:mm:ss");
            intervention.DateStart = QDateTime::fromString(vars["DateStart"], "yyyy-MM-dd HH:mm:ss");
            intervention.Description = vars["Description"];
            intervention.DoseAmount = vars["DoseAmount"].toDouble();
            intervention.DoseFrequency = vars["DoseFrequency"];
            intervention.DoseKey = vars["DoseKey"];
            intervention.DoseString = vars["DoseString"];
            intervention.DoseUnit = vars["DoseUnit"];
            intervention.InterventionClass = vars["InterventionClass"];
            intervention.InterventionName = vars["InterventionName"];
            intervention.Notes = vars["Notes"];
            intervention.Rater = vars["Rater"];
            intervention.subjectRowID = subjectRowID;
            intervention.Store();
        }
    }
    /* ----- analysis ----- */
    else if (object == Analysis) {
        qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
        qint64 analysisRowID = sqrl->FindAnalysis(subjectID, studyNum, vars["AnalysisName"]);
        if (analysisRowID < 0) {
            squirrelAnalysis analysis(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel Analysis [%1]").arg(vars["AnalysisName"]));
            analysis.AnalysisName = vars["AnalysisName"];
            analysis.DateClusterEnd = QDateTime::fromString(vars["DateClusterEnd"], "yyyy-MM-dd HH:mm:ss");
            analysis.DateClusterStart = QDateTime::fromString(vars["DateClusterStart"], "yyyy-MM-dd HH:mm:ss");
            analysis.DateEnd = QDateTime::fromString(vars["DateEnd"], "yyyy-MM-dd HH:mm:ss");
            analysis.DateStart = QDateTime::fromString(vars["DateStart"], "yyyy-MM-dd HH:mm:ss");
            analysis.Hostname = vars["Hostname"];
            analysis.LastMessage = vars["LastMessage"];
            analysis.PipelineName = vars["PipelineName"];
            analysis.PipelineVersion = vars["PipelineVersion"].toInt();
            analysis.RunTime = vars["RunTime"].toInt();
            analysis.SeriesCount = vars["SeriesCount"].toInt();
            analysis.SetupTime = vars["SetupTime"].toInt();
            analysis.Size = vars["Size"].toInt();
            analysis.Status = vars["Status"];
            analysis.Successful = vars["Successful"].toInt();
            analysis.stagedFiles = vars["StagedFiles"].split(",");
            analysis.studyRowID = studyRowID;
            analysis.Store();
        }
        else {
            m = QString("Series with SeriesNumber [%1] already exists for study [%2] and subject [%3] in package").arg(vars["SeriesNumber"]).arg(studyNum).arg(subjectID);
            delete sqrl;
            return false;
        }
    }
    /* ----- experiment ----- */
    else if (object == Experiment) {
        qint64 experimentRowID = sqrl->FindExperiment(vars["ExperimentName"]);
        if (experimentRowID < 0) {
            squirrelExperiment experiment(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel Experiment [%1]").arg(vars["ExperimentName"]));
            experiment.ExperimentName = vars["ExperimentName"];
            experiment.FileCount = vars["FileCount"].toLongLong();
            experiment.Size = vars["Size"].toLongLong();
            experiment.stagedFiles = vars["StagedFiles"].split(",");
            experiment.Store();
        }
        else {
            m = QString("Experiment [%1] already exists in this package").arg(vars["ExperimentName"]);
            delete sqrl;
            return false;
        }
    }
    /* ----- pipeline ----- */
    else if (object == Pipeline) {
        qint64 pipelineRowID = sqrl->FindPipeline(vars["PipelineName"]);
        if (pipelineRowID < 0) {
            squirrelPipeline pipeline(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel Pipeline [%1]").arg(vars["PipelineName"]));
            pipeline.ClusterMaxWallTime = vars["ClusterMaxWallTime"].toInt();
            pipeline.ClusterMemory = vars["ClusterMemory"].toInt();
            pipeline.ClusterNumberCores = vars["ClusterNumberCores"].toInt();
            pipeline.ClusterQueue = vars["ClusterQueue"];
            pipeline.ClusterSubmitHost = vars["ClusterSubmitHost"];
            pipeline.ClusterType = vars["ClusterType"];
            pipeline.ClusterUser = vars["ClusterUser"];
            pipeline.CompleteFiles = vars["CompleteFiles"].split(",");
            pipeline.CreateDate = QDateTime::fromString(vars["CreateDate"], "yyyy-MM-dd HH:mm:ss");
            pipeline.DataCopyMethod = vars["DataCopyMethod"];
            //pipeline.dataSteps = vars["PipelineName"];
            pipeline.DependencyDirectory = vars["DependencyDirectory"];
            pipeline.DependencyLevel = vars["DependencyLevel"];
            pipeline.DependencyLinkType = vars["DependencyLinkType"];
            pipeline.Description = vars["Description"];
            pipeline.Directory = vars["Directory"];
            pipeline.DirectoryStructure = vars["DirectoryStructure"];
            pipeline.Group = vars["Group"];
            pipeline.GroupType = vars["GroupType"];
            pipeline.Level = vars["Level"].toInt();
            pipeline.Notes = vars["Notes"];
            pipeline.NumberConcurrentAnalyses = vars["NumberConcurrentAnalyses"].toInt();
            pipeline.ParentPipelines = vars["ParentPipelines"].split(",");
            pipeline.PipelineName = vars["PipelineName"];
            pipeline.PrimaryScript = vars["PrimaryScript"];
            pipeline.ResultScript = vars["ResultScript"];
            pipeline.SecondaryScript = vars["SecondaryScript"];
            pipeline.SubmitDelay = vars["SubmitDelay"].toInt();
            pipeline.TempDirectory = vars["TempDirectory"];
            pipeline.Version = vars["Version"].toInt();
            pipeline.stagedFiles = vars["StagedFiles"].split(",");
            pipeline.Store();
        }
        else {
            m = QString("Pipeline [%1] already exists in this package").arg(vars["PipelineName"]);
            delete sqrl;
            return false;
        }
    }
    /* ----- groupanalysis ----- */
    else if (object == GroupAnalysis) {
        qint64 groupAnalysisRowID = sqrl->FindGroupAnalysis(vars["GroupAnalysisName"]);
        if (groupAnalysisRowID < 0) {
            squirrelGroupAnalysis groupAnalysis(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel GroupAnalysis [%1]").arg(vars["GroupAnalysisName"]));
            groupAnalysis.DateTime = QDateTime::fromString(vars["DateTime"], "yyyy-MM-dd HH:mm:ss");
            groupAnalysis.Description = vars["Description"];
            groupAnalysis.Notes = vars["Notes"];
            groupAnalysis.GroupAnalysisName = vars["GroupAnalysisName"];
            groupAnalysis.FileCount = vars["FileCount"].toLongLong();
            groupAnalysis.Size = vars["Size"].toLongLong();
            groupAnalysis.stagedFiles = vars["StagedFiles"].split(",");
            groupAnalysis.Store();
        }
        else {
            m = QString("GroupAnalysis [%1] already exists in this package").arg(vars["GroupAnalysisName"]);
            delete sqrl;
            return false;
        }
    }
    /* ----- datadictionary ----- */
    else if (object == DataDictionary) {
        qint64 dataDictionaryRowID = sqrl->FindDataDictionary(vars["DataDictionaryName"]);
        if (dataDictionaryRowID < 0) {
            squirrelDataDictionary dataDictionary(sqrl->GetDatabaseUUID());
            sqrl->Log(QString("Creating squirrel DataDictionary [%1]").arg(vars["DataDictionaryName"]));
            dataDictionary.DataDictionaryName = vars["DataDictionaryName"];
            dataDictionary.FileCount = vars["FileCount"].toLongLong();
            dataDictionary.Size = vars["Size"].toLongLong();
            dataDictionary.stagedFiles = vars["StagedFiles"].split(",");
            dataDictionary.Store();
        }
        else {
            m = QString("DataDictionary [%1] already exists in this package").arg(vars["DataDictionaryName"]);
            delete sqrl;
            return false;
        }
    }
    /* ----- unknown ----- */
    else {
        m = "Unknown object type";
        delete sqrl;
        return false;
    }

    /* write the squirrel object */
    sqrl->Write();

    delete sqrl;
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- RemoveObject --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
bool modify::RemoveObject(QString packagePath, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m) {

    /* load the package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetWriteLog(false);
    if (!sqrl->Read()) {
        m = QString("Package unreadable");
        delete sqrl;
        return false;
    }

    /* ----- subject ----- */
    if (object == Subject) {
        qint64 subjectRowID = sqrl->FindSubject(objectID);
        if (subjectRowID < 0) {
            m = QString("Subject with ID [%1] not found in package").arg(objectID);
            delete sqrl;
            return false;
        }
        else {
            sqrl->RemoveObject(Subject, subjectRowID);
            sqrl->ResequenceSubjects();
        }
    }
    else if (object == Study) {
        qint64 studyRowID = sqrl->FindStudy(subjectID, objectID.toInt());
        qint64 subjectRowID = sqrl->FindSubject(objectID);
        if (studyRowID < 0) {
            sqrl->RemoveObject(Study, studyRowID);
            sqrl->ResequenceStudies(subjectRowID);
        }
        else {
            m = QString("Study with SubjectID [%1], StudyNum [%2] not found in package").arg(subjectID).arg(objectID);
            delete sqrl;
            return false;
        }
    }
    else if (object == Series) {
        qint64 seriesRowID = sqrl->FindSeries(subjectID, studyNum, objectID.toInt());
        qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
        if (seriesRowID < 0) {
            sqrl->RemoveObject(Series, seriesRowID);
            sqrl->ResequenceSeries(studyRowID);
        }
        else {
            m = QString("Series with SubjectID [%1], StudyNum [%2], SeriesNum [%3] not found in package").arg(subjectID).arg(studyNum).arg(objectID);
            delete sqrl;
            return false;
        }
    }
    else if (object == Experiment) {
        qint64 experimentRowID = sqrl->FindExperiment(objectID);
        if (experimentRowID < 0) {
            sqrl->RemoveObject(Experiment, experimentRowID);
        }
        else {
            m = QString("Experiment with ExperimentName [%1] not found in package").arg(objectID);
            delete sqrl;
            return false;
        }
    }
    else if (object == Pipeline) {
        qint64 pipelineRowID = sqrl->FindPipeline(objectID);
        if (pipelineRowID < 0) {
            sqrl->RemoveObject(Pipeline, pipelineRowID);
        }
        else {
            m = QString("Pipeline with PipelineName [%1] not found in package").arg(objectID);
            delete sqrl;
            return false;
        }
    }
    else if (object == GroupAnalysis) {
        qint64 groupAnalysisRowID = sqrl->FindGroupAnalysis(objectID);
        if (groupAnalysisRowID < 0) {
            sqrl->RemoveObject(GroupAnalysis, groupAnalysisRowID);
        }
        else {
            m = QString("GroupAnalysis with GroupAnalysisName [%1] not found in package").arg(objectID);
            delete sqrl;
            return false;
        }
    }
    else if (object == DataDictionary) {
        qint64 dataDictionaryRowID = sqrl->FindDataDictionary(objectID);
        if (dataDictionaryRowID < 0) {
            sqrl->RemoveObject(DataDictionary, dataDictionaryRowID);
        }
        else {
            m = QString("DataDictionary with DataDictionaryName [%1] not found in package").arg(objectID);
            delete sqrl;
            return false;
        }
    }
    else {
        m = "Unknown object type";
        return false;
    }

    if (sqrl->Write()) {
        m = "Successfully removed object and wrote squirrel package";
        return true;
    }
    else {
        m = "Unable to write squirrel package";
        return false;
    }

}


/* ---------------------------------------------------------------------------- */
/* ----- UpdateObject --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
bool modify::UpdateObject(QString packagePath, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m) {

    //if (objectType != "") {
        /* load the package */
        squirrel *sqrl = new squirrel();
        sqrl->SetFileMode(FileMode::ExistingPackage);
        sqrl->SetPackagePath(packagePath);
        if (sqrl->Read()) {
            utils::Print("Read package");
        }
        else {
            m = QString("Package unreadable");
            delete sqrl;
            return false;
        }

        //utils::Print("objectData [" + objectData + "]");
        QUrlQuery queryObject(objectData);
        //utils::Print(QString("queryObject [%1]").arg(queryObject.toString()));

        /* ----- package ----- */
        if (object == Package) {
            //utils::Print("Checkpoint B - objectType is package");

            /* read the JSON file from the package --"QJsonObject squirrel::ReadSquirrelHeader()" */
            QJsonDocument d;
            sqrl->GetJsonHeader(d);

            /* modify the JSON file */
            QJsonObject root = d.object();
            QJsonObject packageInfo = root["package"].toObject();

            /* update any package values */
            if (queryObject.queryItemValue("Changes").trimmed() != "") packageInfo["Changes"] = queryObject.queryItemValue("Changes").trimmed();
            if (queryObject.queryItemValue("Datetime").trimmed() != "") packageInfo["Datetime"] = queryObject.queryItemValue("Datetime").trimmed();
            if (queryObject.queryItemValue("Description").trimmed() != "") packageInfo["Description"] = queryObject.queryItemValue("Description").trimmed();
            if (queryObject.queryItemValue("License").trimmed() != "") packageInfo["License"] = queryObject.queryItemValue("License").trimmed();
            if (queryObject.queryItemValue("Notes").trimmed() != "") packageInfo["Notes"] = queryObject.queryItemValue("Notes").trimmed();
            if (queryObject.queryItemValue("PackageName").trimmed() != "") packageInfo["PackageName"] = queryObject.queryItemValue("PackageName").trimmed();
            if (queryObject.queryItemValue("Readme").trimmed() != "") packageInfo["Readme"] = queryObject.queryItemValue("Readme").trimmed();

            /* overwrite the existing JSON file in the package */
            root["package"] = packageInfo;
            QString j = QJsonDocument(root).toJson();
            if (sqrl->UpdateJsonHeader(j)) {
                utils::Print("Updated json header in package");
            }
            else {
                utils::Print("Error updating json header in package");
            }
        }
        else {
            m = "Unknown object type";
            return false;
        }

        /* delete the object when done */
        delete sqrl;

        return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- SplitByModality ------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Split a package by modality. This will create new packages with only the imaging data (and associated analyses/experiments/pipelines) and a separate package with only interventions/observations
 * @param packagePath
 * @param objectType
 * @param dataPath
 * @param recursive
 * @param objectData
 * @param objectID
 * @param subjectID
 * @param studyNum
 * @param m
 * @return
 */
bool modify::SplitByModality(QString packagePath, QString dataPath, QString objectData, QString objectID, QString &m) {
    /* Note: the data is COPIED, not moved, from the original package to the new packages.
     * So an example package of 100MB with 2 modalities will write out 2 packages, with each being 50MB
     * and the original package will remain on disk. After the split operation there will be three image packages
     * with a total of 200MB on disk, plus one package with only interventions/observations. */

    utils::Print("Splitting package [" + packagePath + "] by modality...");
    /* read squirrel package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(false);
    qint64 unzippedSize = sqrl->GetUnzipSize();
    qint64 totalSpaceRequired = unzippedSize * 3; /* 3x the unzipped size needed */
    if (totalSpaceRequired > sqrl->GetFreeDiskSpace()) {
        m = QString("Not enough free space on disk to perform this operation. %1 bytes free space needed, but only %2 bytes free").arg(totalSpaceRequired).arg(sqrl->GetFreeDiskSpace());
        return false;
    }

    if (sqrl->Read()) {
        utils::Print("Read package [" + packagePath + "] successfully");
    }
    else {
        m = QString("Package unreadable [" + packagePath + "]");
        m += QString("Log [" + sqrl->GetLog() + "]");
        delete sqrl;
        return false;
    }

    /* get list of unique modalities */
    utils::Print("Getting list of modalities in package...");
    QSet<QString> modalities;
    QList <squirrelSubject> subjects = sqrl->GetSubjectList();
    int count = subjects.size();
    if (count > 0) {
        foreach (squirrelSubject subject, subjects) {
            if (subject.Get()) {
                QList <squirrelStudy> studies = sqrl->GetStudyList(subject.GetObjectID());
                int count = subjects.size();
                if (count > 0) {
                    foreach (squirrelStudy study, studies) {
                        if (study.Get()) {
                            modalities.insert(study.Modality);
                        }
                        else { utils::Print("Error getting study object..."); }
                    }
                }
                else {
                    utils::Print("No studies found for subject [" + subject.ID + "]...");
                }
            }
            else { utils::Print("Error getting subject object..."); }
        }
    }
    else {
        utils::Print("Package contains no subjects. Nothing to split by modality...");
        return true;
    }

    /* get unique list of modalities */
    QStringList mods(modalities.begin(), modalities.end());
    if (mods.size() == 0) {
        sqrl->Log("Package contains no modalities (no studies). Nothing to do.");
        return true;
    }
    else if (mods.size() == 1) {
        sqrl->Log("Package contains one modality. Nothing to do.");
    }

    sqrl->Log(QString("Package contains %1 modalities: ").arg(mods.size()) + mods.join(", "));

    /* extract the original package to disk */
    QString tmpDir = sqrl->GetSystemTempDir() + "/squirrel-" + utils::GenerateRandomString(10);
    QString m2;
    utils::MakePath(tmpDir, m2);

    /* create N packages, one for each modality */
    foreach (QString modality, mods) {
        QFileInfo finfo(packagePath);
        QString newPackagePath = finfo.absolutePath() + "/" + finfo.baseName() + "-" + modality + "." + finfo.completeSuffix();
        sqrl->Log(QString("Creating new package %1 for %2 modality").arg(newPackagePath).arg(modality));

        squirrel *sqrl2 = new squirrel();
        //sqrl2->SetDebugSQL(true);
        //sqrl2->SetDebug(true);
        sqrl2->SetFileMode(FileMode::NewPackage);
        sqrl2->SetPackagePath(newPackagePath);
        sqrl2->SetOverwritePackage(true);
        sqrl2->DataFormat = "orig";
        sqrl2->SetWriteLog(true);
        QString newDbID = sqrl2->GetDatabaseUUID();
        //sqrl2->SetSystemTempDir();

        QList <squirrelSubject> subjects = sqrl->GetSubjectList();
        if (subjects.size() > 0) {
            foreach (squirrelSubject subject, subjects) {
                if (subject.Get()) {
                    QList <squirrelStudy> studies = sqrl->GetStudyList(subject.GetObjectID());
                    if (studies.size() > 0) {
                        foreach (squirrelStudy study, studies) {
                            if (study.Get()) {
                                /* extract the files for the matching modality */
                                if (study.Modality == modality) {

                                    /* find existing subject in sqrl2 */
                                    qint64 newSubjectRowID = sqrl2->FindSubject(subject.ID);
                                    squirrelSubject newSubject = subject;
                                    if (newSubjectRowID < 0) {
                                        /* create this subject if they don't already exist, and copy original subject to new subject */
                                        newSubject.SetDatabaseUUID(newDbID);
                                        newSubject.SetObjectID(-1);
                                        newSubject.Store();
                                        newSubjectRowID = newSubject.GetObjectID();
                                    }
                                    else {
                                        /* get the sqrl2 subject that already exists */
                                        newSubject = sqrl2->GetSubject(newSubjectRowID);
                                    }

                                    /* copy original study to new study */
                                    squirrelStudy newStudy = study;
                                    newStudy.SetDatabaseUUID(newDbID);
                                    newStudy.SetObjectID(-1); /* reset the rowID */
                                    newStudy.subjectRowID = newSubjectRowID;
                                    newStudy.Store();
                                    qint64 newStudyRowID = newStudy.GetObjectID();
                                    sqrl2->Debug(QString("Subject %1 & study %2 contain %3 modality data... extracting the series to disk").arg(newSubject.ID).arg(newStudy.StudyNumber).arg(newStudy.Modality));

                                    /* get series */
                                    QList <squirrelSeries> serieses = sqrl->GetSeriesList(study.GetObjectID());
                                    if (serieses.size() > 0) {
                                        foreach (squirrelSeries series, serieses) {
                                            if (series.Get()) {

                                                /* copy original series to new series */
                                                squirrelSeries newSeries = series;
                                                newSeries.SetDatabaseUUID(newDbID);
                                                newSeries.SetObjectID(-1); /* reset the rowID */
                                                newSeries.studyRowID = newStudyRowID;
                                                newSeries.Store();
                                                qint64 newSeriesRowID = newSeries.GetObjectID();
                                                //sqrl2->Log(QString("Copying original seriesRowID [%1] to new sqrl2 seriesRowID [%2]").arg(series.GetObjectID()).arg(newSeriesRowID), __FUNCTION__);

                                                /* extract files from original package and add them to the new package */
                                                QString m3;
                                                QString archiveSeriesPath;
                                                #ifdef Q_OS_WINDOWS
                                                    archiveSeriesPath = QString("data\\%1\\%2\\%3\\*").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber);
                                                #else
                                                    archiveSeriesPath = QString("data/%1/%2/%3/*").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber);
                                                #endif

                                                QString newSeriesPath = QString("%1/data/%2/%3/%4").arg(tmpDir).arg(newSubject.ID).arg(newStudy.StudyNumber).arg(newSeries.SeriesNumber);
                                                //QString newSeriesPath = tmpDir;
                                                utils::MakePath(newSeriesPath, m3);

                                                sqrl->ExtractArchiveFilesToDirectory(sqrl->GetPackagePath(), archiveSeriesPath, tmpDir, m3);
                                                qint64 c(0), b(0);
                                                utils::GetDirSizeAndFileCount(newSeriesPath, c, b, true);
                                                sqrl->Log(QString("Extracted files from original squirrel packge [%1 :: %2] to directory [%3]. Directory now contains [%4] files of size [%5] bytes").arg(sqrl->GetPackagePath()).arg(archiveSeriesPath).arg(newSeriesPath).arg(c).arg(b));

                                                utils::Print(QString("Searching %1 for files matching '*'").arg(newSeriesPath));
                                                QStringList allFiles = utils::FindAllFiles(newSeriesPath, "*");
                                                utils::Print(QString("Found %1 files [%2] in path [%3]").arg(allFiles.size()).arg(allFiles.join(", ")).arg(newSeriesPath));
                                                sqrl2->AddStagedFiles(Series, newSeriesRowID, allFiles);
                                                sqrl2->Debug(QString("Added staged series files from directory [%1] to seriesRowID [%2]").arg(newSeriesPath).arg(newSeriesRowID), __FUNCTION__);
                                            }
                                        }
                                    }
                                }
                            }
                            else { utils::Print("Error getting study object..."); }
                        }
                    }
                }
                else { utils::Print("Error getting subject object..."); }
            }
        }
        //sqrl2->Print();
        sqrl2->Write();
        delete sqrl2;
    }

    /* delete squirrel object(s) */
    delete sqrl;

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- RemovePHI ------------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
bool modify::RemovePHI(QString packagePath, QString dataPath, QString &m) {

    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(true);

    if (sqrl->Read()) {
        utils::Print("Read package [" + packagePath + "] successfully");
    }
    else {
        m = QString("Package unreadable [" + packagePath + "]");
        m += QString("Log [" + sqrl->GetLog() + "]");
        delete sqrl;
        return false;
    }

    QSqlQuery q(QSqlDatabase::database(sqrl->GetDatabaseUUID()));

    /* alphabetically by table ... */

    /* remove intervention dates */
    q.prepare("update Intervention set DateStart = '0000-00-00 00:00:00', DateEnd = '0000-00-00 00:00:00', DateRecordCreate = '0000-00-00 00:00:00', DateRecordEntry = '0000-00-00 00:00:00', DateRecordModify = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed intervention dates");

    /* remove observation dates */
    q.prepare("update Intervention set DateStart = '0000-00-00 00:00:00', DateEnd = '0000-00-00 00:00:00', DateRecordCreate = '0000-00-00 00:00:00', DateRecordEntry = '0000-00-00 00:00:00', DateRecordModify = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed observation dates");

    /* remove series datetime */
    q.prepare("update Series set Datetime = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed series dates");

    /* remove study datetime */
    q.prepare("update Study set Datetime = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed study dates");

    /* remove subject dateOfBirth */
    q.prepare("update Subject set DateOfBirth = '0000-00-00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed subject birthdates");

    sqrl->WriteUpdate();

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- PrintVariables ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
void modify::PrintVariables(ObjectType object) {
    using namespace std;
    vector<vector<string> > data;

    if (object == Package) {
        data = {
            {"Variable","Type","Default","Description"},
            {"Changes","string","","Any CHANGE files"},
            {"Datetime","datetime","*","Datetime the package was created"},
            {"Description","string","","Longer description of the package"},
            {"License","string","","Any sharing or license notes, or LICENSE files"},
            {"Notes","JSON object","","See details below"},
            {"PackageName","string","*","Short name of the package"},
            {"Readme","string","","Any README files"}
        };
    }

    if (object == Subject) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AlternateIDs","JSON array","","List of alternate IDs. Comma separated"},
            {"DateOfBirth","date","*","Subject’s date of birth. Used to calculate age-at-server. Can be YYYY-00-00 to store year only, or YYYY-MM-00 to store year and month only"},
            {"Gender","char","","Gender"},
            {"GUID","string","","Globally unique identifier, from NDA"},
            {"Ethnicity1","string","","NIH defined ethnicity: Usually hispanic, non-hispanic"},
            {"Ethnicity2","string","","NIH defined race: americanindian, asian, black, hispanic, islander, white"},
            {"Notes","string","","Any notes about the subject"},
            {"Sex","char","*","Sex at birth (F,M,O,U)"},
            {"SubjectID","string","*","Unique ID of this subject. Each subject ID must be unique within the package"}
        };
    }

    if (object == Study) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AgeAtStudy","number","*","Subject's age in years at the time of the study"},
            {"Datetime","datetime","*","Date of the study"},
            {"DayNumber","number","","For repeated studies and clinical trials, this indicates the day number of this study in relation to time 0"},
            {"Description","string","*","Study description"},
            {"Equipment","string","","Equipment name, on which the imaging session was collected"},
            {"Height","number","","Height in m of the subject at the time of the study"},
            {"Modality","string","*","Defines the type of data. See table of supported modalities"},
            {"Notes","string","","Any notes about the study"},
            {"StudyNumber","number","*","Study number. May be sequential or correspond to NiDB assigned study number"},
            {"StudyUID","string","","DICOM field StudyUID"},
            {"TimePoint","number","","Similar to day number, but this should be an ordinal number"},
            {"VisitType","string","","Type of visit. ex: Pre, Post"},
            {"Weight","number","","Weight in kg of the subject at the time of the study"}
        };
    }

    if (object == Series) {
        data = {
            {"Variable","Type","Required","Description"},
            {"BidsEntity","string","","BIDS entity (anat, fmri, dwi, etc)"},
            {"BidsSuffix","string","","BIDS suffix"},
            {"BIDSTask","string","","BIDS Task name"},
            {"BIDSRun","number","","BIDS run number"},
            {"BIDSPhaseEncodingDirection","string","","BIDS PE direction"},
            {"Description","string","","Description of the series"},
            {"ExperimentName","string","","Experiment name associated with this series. Experiments link to the experiments section of the squirrel package"},
            {"Protocol","string","*","Protocol name"},
            {"Run","number","","The run identifies order of acquisition in cases of multiple identical series"},
            {"SeriesDatetime","date","*","Date of the series, usually taken from the DICOM header"},
            {"SeriesNumber","number","*","Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header"},
            {"SeriesUID","string","","From the SeriesUID DICOM tag"}
        };
    }

    if (object == Analysis) {
        data = {
            {"Variable","Type","Required","Description"},
            {"DateStart","date","*","Datetime of the start of the analysis"},
            {"DateEnd","date","","Datetime of the end of the analysis"},
            {"DateClusterStart","date","","Datetime the job began running on the cluster"},
            {"DateClusterEnd","date","","Datetime the job finished running on the cluster"},
            {"Hostname","string","","If run on a cluster, the hostname of the node on which the analysis run"},
            {"PipelineName","string","*","Name of the pipeline used to generate these results"},
            {"PipelineVersion","number","","Version of the pipeline used"},
            {"RunTime","number","","Elapsed wall time, in seconds, to run the analysis after setup"},
            {"SeriesCount","number","","Number of series downloaded/used to perform analysis"},
            {"SetupTime","number","","Elapsed wall time, in seconds, to copy data and set up analysis"},
            {"Status","string","","Status, should always be 'complete'"},
            {"StatusMessage","string","","Last running status message"},
            {"Successful","bool","","Analysis ran to completion without error and expected files were created"}
        };
    }

    if (object == Observation) {
        data = {
            {"Variable","Type","Required","Description"},
            {"DateEnd","datetime","","End datetime of the observation"},
            {"DateRecordCreate","datetime","","Date the record was created in the current database. The original record may have been imported from another database"},
            {"DateRecordEntry","datetime","","Date the record was first entered into a database"},
            {"DateRecordModify","datetime","","Date the record was modified in the current database"},
            {"DateStart","datetime","*","Start datetime of the observation"},
            {"Description","string","","Longer description of the measure"},
            {"Duration","number","","Duration of the measure in seconds, if known"},
            {"InstrumentName","string","","Name of the instrument associated with this measure"},
            {"ObservationName","string","*","Name of the observation"},
            {"Notes","string","","Detailed notes"},
            {"Rater","string","","Name of the rater"},
            {"Value","string","*","Value (string or number)"}
        };
    }

    if (object == Intervention) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AdministrationRoute","string","","Drug entry route (oral, IV, unknown, etc)"},
            {"DateRecordCreate","string","","Date the record was created in the current database. The original record may have been imported from another database"},
            {"DateRecordEntry","string","","Date the record was first entered into a database"},
            {"DateRecordModify","string","","Date the record was modified in the current database"},
            {"DateEnd","datetime","","Datetime the intervention was stopped"},
            {"DateStart","datetime","*","Datetime the intervention was started"},
            {"Description","string","","Longer description"},
            {"DoseString","string","*","Full dosing string. Examples tylenol 325mg twice daily by mouth, or 5g marijuana inhaled by volcano"},
            {"DoseAmount","number","","In combination with other dose variables, the quantity of the drug"},
            {"DoseFrequency","string","","Description of the frequency of administration"},
            {"DoseKey","string","","For clinical trials, the dose key"},
            {"DoseUnit","string","","mg, g, ml, tablets, capsules, etc"},
            {"InterventionClass","string","","Drug class"},
            {"InterventionName","string","*","Name of the intervention"},
            {"Notes","string","","Notes about drug"},
            {"Rater","string","","Rater/experimenter name"}
        };
    }

    if (object == Pipeline) {
        data = {
            {"Variable","Type","Required","Description"},
            {"ClusterType","string","","Compute cluster engine (sge or slurm)"},
            {"ClusterUser","string","","Submit username"},
            {"ClusterQueue","string","","Queue to submit jobs"},
            {"ClusterSubmitHost","string","","Hostname to submit jobs"},
            {"CompleteFiles","JSON array","","JSON array of complete files, with relative paths to analysisroot"},
            {"CreateDate","datetime","*","Date the pipeline was created"},
            {"DataCopyMethod","string","","How the data is copied to the analysis directory: cp, softlink, hardlink"},
            {"DependencyDirectory","string","",""},
            {"DependencyLevel","string","",""},
            {"DependencyLinkType","string","",""},
            {"Description","string","","Longer pipeline description"},
            {"DirectoryStructure","string","",""},
            {"Directory","string","","Directory where the analyses for this pipeline will be stored. Leave blank to use the default location"},
            {"Group","string","","ID or name of a group on which this pipeline will run"},
            {"GroupType","string","","Either subject or study"},
            {"Level","number","*","subject-level analysis (1) or group-level analysis (2)"},
            {"MaxWallTime","number","","Maximum allowed clock (wall) time in minutes for the analysis to run"},
            {"ClusterMemory","number","","Amount of memory in GB requested for a running job"},
            {"PipelineName","string","*","Pipeline name"},
            {"Notes","string","","Extended notes about the pipeline"},
            {"NumberConcurrentAnalyses","number","1","Number of analyses allowed to run at the same time. This number if managed by NiDB and is different than grid engine queue size"},
            {"ClusterNumberCores","number","1","Number of CPU cores requested for a running job"},
            {"ParentPipelines","string","","Comma separated list of parent pipelines"},
            {"ResultScript","string","","Executable script to be run at completion of the analysis to find and insert results back into NiDB"},
            {"SubmitDelay","number","","Delay in hours, after the study datetime, to submit to the cluster. Allows time to upload behavioral data"},
            {"TempDirectory","string","","The path to a temporary directory if it is used, on a compute node"},
            {"UseProfile","bool","","true if using the profile option, false otherwise"},
            {"UseTempDirectory","bool","","true if using a temporary directory, false otherwise"},
            {"Version","number","1","Version of the pipeline"},
            {"PrimaryScript","string","*","See details of pipeline scripts"},
            {"SecondaryScript","string","","See details of pipeline scripts"}
        };
    }

    if (object == Experiment) {
        data = {
            {"Variable","Type","Required","Description"},
            {"ExperimentName","string","*","Unique name of the experiment"}
        };
    }

    if (object == DataDictionary) {
        data = {
            {"Variable","Type","Required","Description"},
            {"DataDictionaryName","string","*","Name of this data dictionary"}
        };
    }

    if (object == DataDictionaryItem) {
        data = {
            {"Variable","Type","Required","Description"},
            {"VariableType","string","*","Type of variable"},
            {"VariableName","string","*","Name of the variable"},
            {"Description","string","","Description of the variable"},
            {"KeyValueMapping","string","","List of possible key/value mappings in the format key1=value1, key2=value2. Example 1=Female, 2=Male"},
            {"ExpectedTimepoints","number","","Number of expected timepoints. Example, the study is expected to have 5 records of a variable"},
            {"RangeLow","number","","For numeric values, the lower limit"},
            {"RangeHigh","number","","For numeric values, the upper limit"}
        };
    }

    if (object == GroupAnalysis) {
        data = {
            {"Variable","Type","Required","Description"},
            {"Datetime","datetime","","Datetime of the group analysis"},
            {"Description","string","","Description"},
            {"GroupAnalysisName","string","*","Name of this group analysis"},
            {"Notes","string","","Notes about the group analysis"}
        };
    }

    /* Find the maximum width of each column */
    vector<int> columnWidths(data[0].size());
    for (const auto &row : data) {
        for (size_t i = 0; i < row.size(); ++i) {
            columnWidths[i] = max(columnWidths[i], (int)row[i].size());
        }
    }

    /* Print the table */
    for (const auto &row : data) {
        for (size_t i = 0; i < row.size(); ++i) {
            cout << setw(columnWidths[i] + 2) << left << row[i] << " ";
        }
        cout << endl;
    }

}
