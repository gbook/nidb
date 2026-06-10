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
    else {
        squirrel *sqrl = new squirrel(query.debug, true);
        sqrl->quiet = true;
        sqrl->SetPackagePath(packagePath);
        sqrl->SetFileMode(FileMode::ExistingPackage);
        sqrl->SetQuickRead(true);
        sqrl->Read();
        if (sqrl->IsValid()) {
            sqrl->Debug("Reading package...", __FUNCTION__);
            if (query.object == Package) {
                sqrl->PrintPackage();
            }
            else if (query.object == Subject) {
                sqrl->PrintSubjects(query.dataset, query.printFormat);
            }
            else if (query.object == Study) {
                /* if subjectID is blank, print all studies */
                if (query.subjectID == "") {
                    sqrl->PrintStudies(query.dataset, query.printFormat, -1);
                }
                else {
                    /* just print the studies for a specified subect */
                    qint64 subjectRowID = sqrl->FindSubject(query.subjectID);
                    if (subjectRowID < 0)
                        utils::Print(QString("Subject [%1] was not found in this package").arg(query.subjectID));
                    else
                        sqrl->PrintStudies(query.dataset, query.printFormat, subjectRowID);
                }
            }
            else if (query.object == Series) {
                if ((query.subjectID == "") && (query.studyNum < 1)) {
                    /* print all series */
                    sqrl->PrintSeries(query.dataset, query.printFormat, -1);
                }
                else {
                    qint64 subjectRowID = sqrl->FindSubject(query.subjectID);
                    if (subjectRowID < 0)
                        utils::Print(QString("Subject [%1] was not found in this package").arg(query.subjectID));
                    else {
                        qint64 studyRowID = sqrl->FindStudy(query.subjectID, query.studyNum);
                        if (studyRowID < 0)
                            utils::Print(QString("Study not found. Searched for subject [%1] study [%2]").arg(query.subjectID).arg(query.studyNum));
                        else
                            sqrl->PrintSeries(query.dataset, query.printFormat, studyRowID);
                    }
                }
            }
            else if (query.object == Observation) {
                if (query.subjectID == "") {
                    sqrl->PrintObservations(query.dataset, query.printFormat, -1);
                }
                else {
                    qint64 subjectRowID = sqrl->FindSubject(query.subjectID);
                    if (subjectRowID < 0)
                        utils::Print(QString("Subject [%1] was not found in this package").arg(query.subjectID));
                    else
                        sqrl->PrintObservations(query.dataset, query.printFormat, subjectRowID);
                }
            }
            else if (query.object == Intervention) {
                qint64 subjectRowID = sqrl->FindSubject(query.subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject [%1] was not found in this package").arg(query.subjectID));
                else
                    sqrl->PrintInterventions(query.dataset, query.printFormat, subjectRowID);
            }
            else if (query.object == Experiment) {
                sqrl->PrintExperiments(query.printFormat);
            }
            else if (query.object == Analysis) {
                sqrl->PrintAnalyses(query.dataset, query.printFormat);
            }
            else if (query.object == Pipeline) {
                sqrl->PrintPipelines(query.printFormat);
            }
            else if (query.object == GroupAnalysis) {
                sqrl->PrintGroupAnalyses(query.printFormat);
            }
            else if (query.object == DataDictionary) {
                sqrl->PrintDataDictionary(query.printFormat);
            }
            else {
                sqrl->PrintPackage();
            }
        }
        else {
            utils::Print("Squirrel library has not loaded correctly. See error messages above");
        }

        delete sqrl;
    }

    return true;
}
