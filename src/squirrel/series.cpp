/* ------------------------------------------------------------------------------
  Squirrel series.cpp
  Copyright (C) 2004 - 2022
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

#include "series.h"
#include "../nidb/utils.h"

series::series()
{
	numFiles = 0;
	size = 0;
}


/* ------------------------------------------------------------ */
/* ----- AddExperiment ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief series::AddExperiment
 * @param s
 * @return
 */
bool series::AddExperiment(experiment *e) {

    /* check size of the study list before and after adding */
    qint64 size = experimentList.size();

    /* check if this study already exists, by UID */

    /* if it doesn't exist, append it */
    experimentList.append(e);

    if (experimentList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- PrintSeries ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief series::PrintSeries
 */
void series::PrintSeries() {
	Print("-------- SERIES ----------");
	Print(QString("         SeriesUID: %1").arg(seriesUID));
	Print(QString("         SeriesNum: %1").arg(seriesNum));
	Print(QString("         Description: %1").arg(description));
	Print(QString("         Protocol: %1").arg(protocol));
	Print(QString("         NumFiles: %1").arg(numFiles));
	Print(QString("         Size: %1").arg(size));
}