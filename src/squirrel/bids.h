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
};

#endif // BIDS_H
