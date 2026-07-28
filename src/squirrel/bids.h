/* ------------------------------------------------------------------------------
  Squirrel bids.h
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


#ifndef BIDS_H
#define BIDS_H

#include "utils.h"
#include "squirrel.h"

class bids
{
public:
    bids();

    bool Read(QString dir, squirrel *sqrl);
    bool LoadToSquirrel(QString dir, squirrel *sqrl);

    bool LoadRootFiles(QStringList rootfiles, squirrel *sqrl);
    bool LoadSubjectFiles(QStringList subjfiles, QString ID, squirrel *sqrl);
    bool LoadSessionDir(QString sesdir, qint64 subjectRowID, int studyNum, squirrel *sqrl);

    bool LoadParticipantsFile(QString f, squirrel *sqrl);
    bool LoadTaskFile(QString f, squirrel *sqrl);
    bool LoadDatasetDescription(QString f, squirrel *sqrl);
    bool LoadSessionsFile(QString f, qint64 subjectRowID, squirrel *sqrl);
    bool LoadPhenotypeDir(QString phenotypeDir, squirrel *sqrl);

    /* derivatives -> pipelines + analyses */
    bool LoadDerivatives(QString derivativesDir, squirrel *sqrl);
    qint64 ResolveDerivativeStudy(qint64 subjectRowID, QString sesLabel, squirrel *sqrl);
    qint64 AddAnalysisFromDir(QString dir, QString pipelineName, qint64 pipelineRowID, qint64 studyRowID, squirrel *sqrl);

    /* BIDS filename/entity parsing helpers */
    void ParseBidsFilename(const QString &filename, QHash<QString, QString> &entities, QString &suffix, QString &ext);
    QString ModalityForDatatype(const QString &datatype);
    qint64 AddSeriesFromBidsFile(QString primaryFile, QString datatype, qint64 studyRowID, squirrel *sqrl);
};

#endif // BIDS_H
