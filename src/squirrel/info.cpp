/* ------------------------------------------------------------------------------
  Squirrel info.cpp
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

#include "info.h"
#include "utils.h"

info::info() {}

bool info::DisplayInfo(QString packagePath, bool debug, QString object, QString subjectID, int studyNum, PrintFormat printFormat, QString &m) {

    /* check if the infile exists */
    QFile infile(packagePath);
    if (!infile.exists()) {
        m = "Missing path to squirrel package";
        return false;
    }
    else {
        squirrel *sqrl = new squirrel(debug, true);
        sqrl->quiet = true;
        sqrl->SetPackagePath(packagePath);
        sqrl->SetFileMode(FileMode::ExistingPackage);
        sqrl->SetQuickRead(true);
        sqrl->Read();
        if (sqrl->IsValid()) {
            sqrl->Debug("Reading package...", __FUNCTION__);
            if (object == "package") {
                sqrl->PrintPackage();
            }
            else if (object == "subject") {
                sqrl->PrintSubjects(printFormat);
            }
            else if (object == "study") {
                qint64 subjectRowID = sqrl->FindSubject(subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject [%1] was not found in this package").arg(subjectID));
                else
                    sqrl->PrintStudies(subjectRowID, printFormat);
            }
            else if (object == "series") {
                qint64 subjectRowID = sqrl->FindSubject(subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject [%1] was not found in this package").arg(subjectID));
                else {
                    qint64 studyRowID = sqrl->FindStudy(subjectID, studyNum);
                    if (studyRowID < 0)
                        utils::Print(QString("Study not found. Searched for subject [%1] study [%2]").arg(subjectID).arg(studyNum));
                    else
                        sqrl->PrintSeries(studyRowID, printFormat);
                }
            }
            else if (object == "observation") {
                qint64 subjectRowID = sqrl->FindSubject(subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject [%1] was not found in this package").arg(subjectID));
                else
                    sqrl->PrintObservations(subjectRowID, printFormat);
            }
            else if (object == "intervention") {
                qint64 subjectRowID = sqrl->FindSubject(subjectID);
                if (subjectRowID < 0)
                    utils::Print(QString("Subject [%1] was not found in this package").arg(subjectID));
                else
                    sqrl->PrintInterventions(subjectRowID, printFormat);
            }
            else if (object == "experiment") {
                sqrl->PrintExperiments(printFormat);
            }
            else if (object == "analysis") {
                sqrl->PrintExperiments(printFormat);
            }
            else if (object == "pipeline") {
                sqrl->PrintPipelines(printFormat);
            }
            else if (object == "groupanalysis") {
                sqrl->PrintGroupAnalyses(printFormat);
            }
            else if (object == "datadictionary") {
                sqrl->PrintDataDictionary(printFormat);
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
