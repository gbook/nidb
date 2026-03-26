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
bool extract::DoExtract(QString packagePath, QString outputPath, QString objectType, QString objectIdentifier, QString subjectID, int studyNum, QString &m) {

    utils::Print(QString("Extracting subject [%1] from package [%2] to directory [%3]...").arg(objectIdentifier).arg(packagePath).arg(outputPath));

    /* read squirrel package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(true);
    sqrl->Read();

    /* create outputDir */
    QString m2;
    if (utils::MakePath(outputPath, m2)) {
        utils::Print("Created output path [" + outputPath + "]");
    }
    else {
        utils::Print("Error creating output path [" + outputPath + "]. Message [" + m2 + "]");
        delete sqrl;
        return false;
    }

    ObjectType object = sqrl->ObjectTypeToEnum(objectType);
    if (object == Subject) {
        /* find the subjectRowID */
        qint64 subjectRowID = sqrl->FindSubject(objectIdentifier);
        if (subjectRowID < 0) {
            utils::Print("Subject [" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* extract the subject */
        utils::Print("Extracting subject [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Subject, subjectRowID, outputPath);
    }
    else if (object == Study) {
        /* find the studyRowID */
        qint64 studyRowID = sqrl->FindStudy(subjectID, objectIdentifier.toInt());
        if (studyRowID < 0) {
            utils::Print("Study [" + subjectID + "-" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* extract the study */
        utils::Print("Extracting study [" + subjectID + "-" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Study, studyRowID, outputPath);
    }
    else if (object == Series) {
        /* find the seriesRowID */
        qint64 seriesRowID = sqrl->FindSeries(subjectID, studyNum, objectIdentifier.toInt());
        if (seriesRowID < 0) {
            utils::Print(QString("Series [%1-%2-%3] not found in this package").arg(subjectID).arg(studyNum).arg(objectIdentifier));
            delete sqrl;
            return false;
        }

        /* extract the series */
        utils::Print("Extracting series [" + subjectID + "-" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Series, seriesRowID, outputPath);
    }
    else if (object == Analysis) {
        /* find the analysisRowID */
        qint64 analysisRowID = sqrl->FindAnalysis(subjectID, studyNum, objectIdentifier);
        if (analysisRowID < 0) {
            utils::Print(QString("Analysis [%1-%2-%3] not found in this package").arg(subjectID).arg(studyNum).arg(objectIdentifier));
            delete sqrl;
            return false;
        }

        /* extract the analysis */
        utils::Print("Extracting analysis [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Analysis, analysisRowID, outputPath);
    }
    else if (object == Experiment) {
        /* find the experimentRowID */
        qint64 experimentRowID = sqrl->FindExperiment(objectIdentifier);
        if (experimentRowID < 0) {
            utils::Print("Experiment [" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* extract the experiment */
        utils::Print("Extracting experiment [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Experiment, experimentRowID, outputPath);
    }
    else if (object == Pipeline) {
        /* find the pipelineRowID */
        qint64 pipelineRowID = sqrl->FindPipeline(objectIdentifier);
        if (pipelineRowID < 0) {
            utils::Print("Pipeline [" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* extract the pipeline */
        utils::Print("Extracting pipeline [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Pipeline, pipelineRowID, outputPath);
    }
    else if (object == GroupAnalysis) {
        /* find the groupAnalysisRowID */
        qint64 groupAnalysisRowID = sqrl->FindGroupAnalysis(objectIdentifier);
        if (groupAnalysisRowID < 0) {
            utils::Print("GroupAnalysis [" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* extract the groupAnalysis */
        utils::Print("Extracting groupAnalysis [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(GroupAnalysis, groupAnalysisRowID, outputPath);
    }
    else if (object == DataDictionary) {
        /* find the dataDictionaryRowID */
        qint64 dataDictionaryRowID = sqrl->FindDataDictionary(objectIdentifier);
        if (dataDictionaryRowID < 0) {
            utils::Print("DataDictionary [" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* extract the dataDictionary */
        utils::Print("Extracting dataDictionary [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(DataDictionary, dataDictionaryRowID, outputPath);
    }
    else {
        m = "Invalid oject type [" + sqrl->ObjectTypeToString(object) + "] specified";
        delete sqrl;
        return false;
    }

    delete sqrl;
    return true;
}
