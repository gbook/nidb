/* ------------------------------------------------------------------------------
  Squirrel subject.cpp
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

#include "subject.h"
#include "../nidb/utils.h"


/* ------------------------------------------------------------ */
/* ----- subject ---------------------------------------------- */
/* ------------------------------------------------------------ */
subject::subject() {
    //ID;
    //altUIDs;
    sex = 'U';
    gender = 'U';
    birthdate = QDate::fromString("0000-00-00", "YYYY-MM-dd");
    //ethnicity1;
    //ethnicity2;
}


/* ------------------------------------------------------------ */
/* ----- addStudy --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief subject::addStudy
 * @param s
 * @return
 */
bool subject::addStudy(study s) {

    /* check size of the study list before and after adding */
    qint64 size = studyList.size();

    /* check if this study already exists, by UID */

    /* if it doesn't exist, append it */
    studyList.append(s);

    if (studyList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- PrintSubject ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief subject::PrintSubject
 */
void subject::PrintSubject() {

    Print("---- SUBJECT ----------");
    Print(QString("     ID: %1").arg(ID));
    Print(QString("     AltIDs: %1").arg(altUIDs.join(",")));
    Print(QString("     Sex: %1").arg(sex));
    Print(QString("     Gender: %1").arg(gender));
    Print(QString("     DOB: %1").arg(birthdate.toString()));
    Print(QString("     Ethnicity1: %1").arg(ethnicity1));
    Print(QString("     Ethnicity2: %1").arg(ethnicity2));
}