/* ------------------------------------------------------------------------------
  NIDB moduleMaintenance.cpp
  Copyright (C) 2004 - 2026
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

#include <QSqlQuery>
#include "moduleMaintenance.h"


/* ---------------------------------------------------------- */
/* --------- moduleMaintenance ------------------------------ */
/* ---------------------------------------------------------- */
moduleMaintenance::moduleMaintenance(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleMaintenance ----------------------------- */
/* ---------------------------------------------------------- */
moduleMaintenance::~moduleMaintenance()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Entry point for the maintenance module
 * @return 1 if the log file should be kept, 0 otherwise
 */
int moduleMaintenance::Run() {
    n->Log("Entering the maintenance module");

    n->ModuleRunningCheckIn();
    if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

    bool keepLog = false;

    /* maintenance tasks go here */

    n->Log("Leaving the maintenance module");
    return keepLog;
}
