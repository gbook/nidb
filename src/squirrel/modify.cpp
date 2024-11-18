/* ------------------------------------------------------------------------------
  Squirrel modify.cpp
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

#include "modify.h"
#include "utils.h"

modify::modify() {
}


/* ---------------------------------------------------------------------------- */
/* ----- DoModify ------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
bool modify::DoModify(QString packagePath, QString addObject, QString removeObject, QString dataPath, bool recursive, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m) {

    /* check if any operation was specified */
    if ((addObject == "") && (removeObject == "")) {
        m = "No object specified to add or remove";
        return false;
    }
    /* check if at most one operation was specified */
    if ((addObject != "") && (removeObject != "")) {
        m = "Both add and remove operations were specified. Only one add/remove operation allowed at a time";
        return false;
    }

    /* perform the ADD object */
    if (addObject != "") {
        QStringList objectsWithPaths = {"series", "analysis", "experiment", "pipeline", "groupanalysis"};

        /* check if the user should have specified a path */
        if (objectsWithPaths.contains(addObject)) {

            /* check if that path is specified */
            if (dataPath == "") {
                m = QString("No datapath specified for this object. A datapath must be specified for %1 objects.").arg(addObject);
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
        if (!sqrl->Read()) {
            m = QString("Package unreadable [%1] already exists in package").arg(vars["ID"]);
            delete sqrl;
            return false;
        }

        /* ----- subject ----- */
        if (addObject == "subject") {
            qint64 subjectRowID;
            subjectRowID = sqrl->FindSubject(vars["ID"]);
            if (subjectRowID < 0) {
                squirrelSubject subject;
                sqrl->Log(QString("Creating squirrel Subject [%1]").arg(vars["ID"]), __FUNCTION__);
                subject.ID = vars["ID"];
                subject.AlternateIDs = vars["AlternateIDs"].split(",");
                subject.GUID = vars["GUID"];
                subject.DateOfBirth = QDate::fromString(vars["DateOfBirth"], "yyyy-MM-dd");
                subject.Sex = vars["Gender"];
                subject.Gender = vars["Gender"];
                subject.Ethnicity1 = vars["Ethnicity1"];
                subject.Ethnicity2 = vars["Ethnicity2"];
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
        else if (addObject == "study") {
            qint64 subjectRowID = sqrl->FindSubject(subjectID);
            qint64 studyRowID = sqrl->FindStudy(subjectID, vars["StudyNumber"].toInt());
            if (studyRowID < 0) {
                squirrelStudy study;
                sqrl->Log(QString("Creating squirrel Subject [%1]").arg(vars["ID"]), __FUNCTION__);
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
        else if (addObject == "series") {
            qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
            qint64 seriesRowID = sqrl->FindSeries(subjectID, studyNum, vars["SeriesNumber"].toInt());
            if (seriesRowID < 0) {
                squirrelSeries series;
                sqrl->Log(QString("Creating squirrel Series [%1]").arg(vars["SeriesNumber"]), __FUNCTION__);
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
        else if (addObject == "observation") {
            qint64 subjectRowID = sqrl->FindSubject(subjectID);
            if (subjectRowID < 0) {
                m = QString("Subject [%3] not found in package").arg(subjectID);
                delete sqrl;
                return false;
            }
            else {
                if (dataPath == "") {
                    squirrelObservation observation;
                    sqrl->Log(QString("Creating squirrel Observation [%1]").arg(vars["ObservationName"]), __FUNCTION__);
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
                            if (utils::ParseTSV(dataPath, csv, cols, m)) {
                            }
                        }
                        else if (dataPath.endsWith(".tsv", Qt::CaseInsensitive)) {
                            if (utils::ParseTSV(dataPath, csv, cols, m)) {
                                //sqrl->Log(QString("Successfuly read [%1] into [%2] rows").arg(f).arg(tsv.size()), __FUNCTION__);
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
        else if (addObject == "intervention") {
            qint64 subjectRowID = sqrl->FindSubject(subjectID);
            if (subjectRowID < 0) {
                m = QString("Subject [%3] not found in package").arg(subjectID);
                delete sqrl;
                return false;
            }
            else {
                squirrelIntervention intervention;
                sqrl->Log(QString("Creating squirrel intervention [%1]").arg(vars["InterventionName"]), __FUNCTION__);
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
        else if (addObject == "analysis") {
            qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
            qint64 analysisRowID = sqrl->FindAnalysis(subjectID, studyNum, vars["AnalysisName"]);
            if (analysisRowID < 0) {
                squirrelAnalysis analysis;
                sqrl->Log(QString("Creating squirrel Analysis [%1]").arg(vars["AnalysisName"]), __FUNCTION__);
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
        else if (addObject == "experiment") {
            qint64 experimentRowID = sqrl->FindExperiment(vars["ExperimentName"]);
            if (experimentRowID < 0) {
                squirrelExperiment experiment;
                sqrl->Log(QString("Creating squirrel Experiment [%1]").arg(vars["ExperimentName"]), __FUNCTION__);
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
        else if (addObject == "pipeline") {
            qint64 pipelineRowID = sqrl->FindPipeline(vars["PipelineName"]);
            if (pipelineRowID < 0) {
                squirrelPipeline pipeline;
                sqrl->Log(QString("Creating squirrel Pipeline [%1]").arg(vars["PipelineName"]), __FUNCTION__);
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
        else if (addObject == "groupanalysis") {
            qint64 groupAnalysisRowID = sqrl->FindGroupAnalysis(vars["GroupAnalysisName"]);
            if (groupAnalysisRowID < 0) {
                squirrelGroupAnalysis groupAnalysis;
                sqrl->Log(QString("Creating squirrel GroupAnalysis [%1]").arg(vars["GroupAnalysisName"]), __FUNCTION__);
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
        else if (addObject == "datadictionary") {
            qint64 dataDictionaryRowID = sqrl->FindDataDictionary(vars["DataDictionaryName"]);
            if (dataDictionaryRowID < 0) {
                squirrelDataDictionary dataDictionary;
                sqrl->Log(QString("Creating squirrel DataDictionary [%1]").arg(vars["DataDictionaryName"]), __FUNCTION__);
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
            m = QString("Unrecognized object type [%1]").arg(addObject);
            delete sqrl;
            return false;
        }

        /* write the squirrel object */
        sqrl->Write(true);

        delete sqrl;
    } /* end add objects */

    /* perform the REMOVE object */
    if (removeObject != "") {
        /* load the package */
        squirrel *sqrl = new squirrel();
        sqrl->SetFileMode(FileMode::ExistingPackage);
        sqrl->SetPackagePath(packagePath);
        if (!sqrl->Read()) {
            m = QString("Package unreadable");
            delete sqrl;
            return false;
        }

        /* ----- subject ----- */
        if (removeObject == "subject") {
            qint64 subjectRowID = sqrl->FindSubject(objectID);
            if (subjectRowID < 0) {
                sqrl->RemoveSubject(subjectRowID);
                sqrl->ResequenceSubjects();
            }
            else {
                m = QString("Subject with ID [%1] not found in package").arg(objectID);
                delete sqrl;
                return false;
            }
        }
        else if (removeObject == "study") {
            qint64 studyRowID = sqrl->FindStudy(subjectID, objectID.toInt());
            qint64 subjectRowID = sqrl->FindSubject(objectID);
            if (studyRowID < 0) {
                sqrl->RemoveStudy(studyRowID);
                sqrl->ResequenceStudies(subjectRowID);
            }
            else {
                m = QString("Study with SubjectID [%1], StudyNum [%2] not found in package").arg(subjectID).arg(objectID);
                delete sqrl;
                return false;
            }
        }
        else if (removeObject == "series") {
            qint64 seriesRowID = sqrl->FindSeries(subjectID, studyNum, objectID.toInt());
            qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
            if (seriesRowID < 0) {
                sqrl->RemoveSeries(seriesRowID);
                sqrl->ResequenceSeries(studyRowID);
            }
            else {
                m = QString("Series with SubjectID [%1], StudyNum [%2], SeriesNum [%3] not found in package").arg(subjectID).arg(studyNum).arg(objectID);
                delete sqrl;
                return false;
            }
        }
        else if (removeObject == "experiment") {
            qint64 experimentRowID = sqrl->FindExperiment(objectID);
            if (experimentRowID < 0) {
                sqrl->RemoveExperiment(experimentRowID);
            }
            else {
                m = QString("Experiment with ExperimentName [%1] not found in package").arg(objectID);
                delete sqrl;
                return false;
            }
        }
        else if (removeObject == "pipeline") {
            qint64 pipelineRowID = sqrl->FindPipeline(objectID);
            if (pipelineRowID < 0) {
                sqrl->RemovePipeline(pipelineRowID);
            }
            else {
                m = QString("Pipeline with PipelineName [%1] not found in package").arg(objectID);
                delete sqrl;
                return false;
            }
        }
        else if (removeObject == "groupanalysis") {
            qint64 groupAnalysisRowID = sqrl->FindGroupAnalysis(objectID);
            if (groupAnalysisRowID < 0) {
                sqrl->RemoveGroupAnalysis(groupAnalysisRowID);
            }
            else {
                m = QString("GroupAnalysis with GroupAnalysisName [%1] not found in package").arg(objectID);
                delete sqrl;
                return false;
            }
        }
        else if (removeObject == "datadictionary") {
            qint64 dataDictionaryRowID = sqrl->FindDataDictionary(objectID);
            if (dataDictionaryRowID < 0) {
                sqrl->RemoveDataDictionary(dataDictionaryRowID);
            }
            else {
                m = QString("DataDictionary with DataDictionaryName [%1] not found in package").arg(objectID);
                delete sqrl;
                return false;
            }
        }
    }
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- PrintVariables ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
void modify::PrintVariables(QString object) {

    if (object == "subject")
        utils::Print("ID\nAltIDs\nGUID\nDateOfBirth\nSex\nGender\nEthnicity1\nEthnicity2");

    if (object == "study")
        utils::Print("StudyNumber\nDatetime\nAge\nHeight\nWeight\nModality\nDescription\nStudyUID\nVisitType\nDayNumber\nTimepoint\nEquipment");

}
