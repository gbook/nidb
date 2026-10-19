/* ------------------------------------------------------------------------------
  Squirrel extract.cpp
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

#include "extract.h"
#include "utils.h"

extract::extract() {}

/* ---------------------------------------------------------------------------- */
/* ----- DoExtract ------------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Extract an object from a squirrel package to a directory on disk
 * @param packagePath path to the squirrel package file
 * @param outputPath directory where extracted files will be written
 * @param objectType string name of the object type to extract
 * @param objectIdentifier identifier (ID, name, or number) of the object to extract
 * @param subjectID subject ID for study/series/analysis/observation/intervention scoped extractions
 * @param studyNum study number for series/analysis scoped extractions
 * @param objectDate DateStart of the observation or intervention to extract
 * @param debug enable debug logging
 * @param quiet suppress progress output
 * @param m output message describing success or failure
 * @return true if successful
 */
bool extract::DoExtract(QString packagePath, QString outputPath, QString objectType, QString objectIdentifier, QString subjectID, int studyNum, QString objectDate, bool debug, bool quiet, QString &m) {

    ObjectType object = squirrel::ObjectTypeToEnum(objectType);
    switch (object) {
        case Subject: case Study: case Series: case Analysis: case Observation: case Intervention:
        case Experiment: case Pipeline: case GroupAnalysis: case DataDictionary: break;
        default:
            m = "Invalid object type [" + objectType + "] specified. Valid types: subject study series analysis observation intervention experiment pipeline groupanalysis datadictionary";
            return false;
    }

    /* check the required parameters for this object type */
    if (outputPath.isEmpty()) {
        m = "Missing --outdir";
        return false;
    }
    if (objectIdentifier.isEmpty()) {
        m = "Missing --objectid";
        return false;
    }
    bool needsSubject = (object == Study || object == Series || object == Analysis || object == Observation || object == Intervention);
    bool needsStudy = (object == Series || object == Analysis);
    if (needsSubject && subjectID.isEmpty()) {
        m = QString("Missing --subjectid, which is required for object type [%1]").arg(objectType);
        return false;
    }
    if (needsStudy && studyNum <= 0) {
        m = QString("Missing or invalid --studynum, which is required for object type [%1]").arg(objectType);
        return false;
    }
    if ((object == Observation || object == Intervention) && objectDate.isEmpty()) {
        m = QString("Missing --objectdate, which is required for object type [%1]").arg(objectType);
        return false;
    }
    int objectNum = 0;
    if (object == Study || object == Series) {
        bool ok;
        objectNum = objectIdentifier.toInt(&ok);
        if (!ok || objectNum <= 0) {
            m = QString("Invalid --objectid [%1]. A %2 is identified by its number").arg(objectIdentifier).arg(objectType);
            return false;
        }
    }

    /* read squirrel package */
    squirrel *sqrl = new squirrel(debug, quiet);
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(true);
    if (!sqrl->Read()) {
        m = "Unable to read squirrel package [" + packagePath + "]";
        delete sqrl;
        return false;
    }

    /* find the object */
    qint64 rowID = -1;
    QString desc;
    switch (object) {
        case Subject:
            rowID = sqrl->FindSubject(objectIdentifier);
            desc = QString("subject [%1]").arg(objectIdentifier);
            break;
        case Study:
            rowID = sqrl->FindStudy(subjectID, objectNum);
            desc = QString("study [%1-%2]").arg(subjectID).arg(objectNum);
            break;
        case Series:
            rowID = sqrl->FindSeries(subjectID, studyNum, objectNum);
            desc = QString("series [%1-%2-%3]").arg(subjectID).arg(studyNum).arg(objectNum);
            break;
        case Analysis:
            rowID = sqrl->FindAnalysis(subjectID, studyNum, objectIdentifier);
            desc = QString("analysis [%1-%2-%3]").arg(subjectID).arg(studyNum).arg(objectIdentifier);
            break;
        case Observation:
            rowID = sqrl->FindObservation(subjectID, objectIdentifier, utils::StringToDatetime(objectDate));
            desc = QString("observation [%1] with DateStart [%2] for subject [%3]").arg(objectIdentifier).arg(objectDate).arg(subjectID);
            break;
        case Intervention:
            rowID = sqrl->FindIntervention(subjectID, objectIdentifier, utils::StringToDatetime(objectDate));
            desc = QString("intervention [%1] with DateStart [%2] for subject [%3]").arg(objectIdentifier).arg(objectDate).arg(subjectID);
            break;
        case Experiment:
            rowID = sqrl->FindExperiment(objectIdentifier);
            desc = QString("experiment [%1]").arg(objectIdentifier);
            break;
        case Pipeline:
            rowID = sqrl->FindPipeline(objectIdentifier);
            desc = QString("pipeline [%1]").arg(objectIdentifier);
            break;
        case GroupAnalysis:
            rowID = sqrl->FindGroupAnalysis(objectIdentifier);
            desc = QString("groupanalysis [%1]").arg(objectIdentifier);
            break;
        case DataDictionary:
            rowID = sqrl->FindDataDictionary(objectIdentifier);
            desc = QString("datadictionary [%1]").arg(objectIdentifier);
            break;
        default: break;
    }
    if (rowID < 0) {
        m = desc.left(1).toUpper() + desc.mid(1) + " not found in package [" + packagePath + "]";
        delete sqrl;
        return false;
    }

    /* create outputDir */
    QString m2;
    if (!utils::MakePath(outputPath, m2)) {
        m = "Error creating output path [" + outputPath + "]. Message [" + m2 + "]";
        delete sqrl;
        return false;
    }

    /* extract the object */
    if (!quiet)
        utils::Print("Extracting " + desc + " from package [" + packagePath + "] to directory [" + outputPath + "]");
    if (!sqrl->ExtractObject(object, rowID, outputPath, m2)) {
        m = "Error extracting " + desc + ". Message [" + m2 + "]";
        delete sqrl;
        return false;
    }

    if (!quiet)
        utils::Print("Extracted " + desc + " to [" + outputPath + "]");

    delete sqrl;
    return true;
}
