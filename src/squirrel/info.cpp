/* ------------------------------------------------------------------------------
  Squirrel info.cpp
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

#include "info.h"
#include "utils.h"

info::info() {}

bool info::DisplayInfo(QString packagePath, const infoQuery &query, QString &m) {

    /* check if the infile exists */
    QFile infile(packagePath);
    if (!infile.exists()) {
        m = "Missing path to squirrel package";
        return false;
    }

    /* --studynum and --seriesnum narrow down a parent, so they need the parents above them */
    if ((query.studyNum > 0) && (query.subjectID == "")) {
        m = "--studynum requires --subjectid";
        return false;
    }
    if ((query.seriesNum > 0) && (query.studyNum < 1)) {
        m = "--seriesnum requires --subjectid and --studynum";
        return false;
    }

    squirrel *sqrl = new squirrel(query.debug, true);
    sqrl->quiet = true;
    sqrl->SetPackagePath(packagePath);
    sqrl->SetFileMode(FileMode::ExistingPackage);
    /* params are only loaded by a full read */
    sqrl->SetQuickRead(query.object != Params);
    sqrl->Read();
    if (!sqrl->IsValid()) {
        utils::Print("Squirrel library has not loaded correctly. See error messages above");
        delete sqrl;
        return true;
    }
    sqrl->Debug("Reading package...", __FUNCTION__);

    /* resolve the optional subject and study filters. -1 means all */
    qint64 subjectRowID = -1;
    qint64 studyRowID = -1;
    if (query.subjectID != "") {
        subjectRowID = sqrl->FindSubject(query.subjectID);
        if (subjectRowID < 0) {
            m = QString("Subject [%1] was not found in this package").arg(query.subjectID);
            delete sqrl;
            return false;
        }
    }
    if (query.studyNum > 0) {
        studyRowID = sqrl->FindStudy(query.subjectID, query.studyNum);
        if (studyRowID < 0) {
            m = QString("Study [%1] was not found for subject [%2]").arg(query.studyNum).arg(query.subjectID);
            delete sqrl;
            return false;
        }
    }

    switch (query.object) {
        case Subject: sqrl->PrintSubjects(query.dataset, query.printFormat); break;
        case Study: sqrl->PrintStudies(query.dataset, query.printFormat, subjectRowID); break;
        case Series: sqrl->PrintSeries(query.dataset, query.printFormat, studyRowID, subjectRowID); break;
        case Params: sqrl->PrintParams(query.printFormat, studyRowID, subjectRowID, query.seriesNum); break;
        case Analysis: sqrl->PrintAnalyses(query.dataset, query.printFormat, studyRowID, subjectRowID); break;
        case Observation: sqrl->PrintObservations(query.dataset, query.printFormat, subjectRowID); break;
        case Intervention: sqrl->PrintInterventions(query.dataset, query.printFormat, subjectRowID); break;
        case Experiment: sqrl->PrintExperiments(query.dataset, query.printFormat); break;
        case Pipeline: sqrl->PrintPipelines(query.dataset, query.printFormat); break;
        case GroupAnalysis: sqrl->PrintGroupAnalyses(query.dataset, query.printFormat); break;
        case DataDictionary: sqrl->PrintDataDictionary(query.dataset, query.printFormat); break;
        case DataDictionaryItem: sqrl->PrintDataDictionaryItems(query.dataset, query.printFormat); break;
        default: sqrl->PrintPackage(); break;
    }

    delete sqrl;
    return true;
}
