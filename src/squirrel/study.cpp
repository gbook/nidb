/* ------------------------------------------------------------------------------
  Squirrel study.cpp
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

#include "study.h"
#include "../nidb/utils.h"

/* ------------------------------------------------------------ */
/* ----- study ------------------------------------------------ */
/* ------------------------------------------------------------ */
study::study()
{
    studyNum = 1;
    dateTime = QDateTime::currentDateTime();
    modality = "UNKNOWN";
    weight = 0.0;
    height = 0.0;
}


/* ------------------------------------------------------------ */
/* ----- addSeries -------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief study::addSeries
 * @param s
 * @return true if series was added, false if not added
 */
bool study::addSeries(series s) {

    /* check size of the series list before and after adding */
    qint64 size = seriesList.size();

    seriesList.append(s);

    if (seriesList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- PrintStudy ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief study::PrintStudy
 */
void study::PrintStudy() {

    Print("---- STUDY ----------");
    Print(QString("     StudyUID: %1").arg(studyUID));
    Print(QString("     StudyNum: %1").arg(studyNum));
    Print(QString("     Description: %1").arg(description));
    Print(QString("     VisitType: %1").arg(visitType));
    Print(QString("     DayNum: %1").arg(dayNum));
    Print(QString("     TimePoint: %1").arg(timePoint));
    Print(QString("     Date: %1").arg(dateTime.toString()));
    Print(QString("     Modality: %1").arg(modality));
    Print(QString("     Weight: %1 kg").arg(weight));
    Print(QString("     Height: %1 m").arg(height));
}
