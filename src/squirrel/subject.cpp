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
    sex = 'U';
    gender = 'U';
    birthdate = QDate::fromString("0000-00-00", "YYYY-MM-dd");
}


/* ------------------------------------------------------------ */
/* ----- addStudy --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief subject::addStudy
 * @param s
 * @return true if added, false otherwise
 */
bool subject::addStudy(study s) {

    /* check size of the study list before and after adding */
    qint64 size = studyList.size();

    /* check if this study already exists, by UID */
    bool exists = false;
    for (int i=0; i<studyList.size(); i++)
        if (studyList[i].studyUID == s.studyUID)
            exists = true;

    /* if it doesn't exist, append it */
    if (!exists)
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
    Print(QString("     GUID: %1").arg(GUID));
    Print(QString("     Sex: %1").arg(sex));
    Print(QString("     Gender: %1").arg(gender));
    Print(QString("     DOB: %1").arg(birthdate.toString()));
    Print(QString("     Ethnicity1: %1").arg(ethnicity1));
    Print(QString("     Ethnicity2: %1").arg(ethnicity2));
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
QJsonObject subject::ToJSON() {
    QJsonObject json;

    json["ID"] = ID;
    json["alternateIDs"] = QJsonArray::fromStringList(altUIDs);
    json["GUID"] = GUID;
	json["dateOfBirth"] = birthdate.toString("yyyy-MM-dd");
    json["sex"] = sex;
    json["gender"] = gender;
    json["ethnicity1"] = ethnicity1;
    json["ethnicity2"] = ethnicity2;
	json["path"] = virtualPath;

    QJsonArray JSONstudies;
    for (int i=0; i<studyList.size(); i++) {
        JSONstudies.append(studyList[i].ToJSON());
    }
    json["numStudies"] = JSONstudies.size();
    json["studies"] = JSONstudies;

    /* add measures */
    if (measureList.size() > 0) {
        QJsonArray JSONmeasures;
        for (int i=0; i < measureList.size(); i++) {
            JSONmeasures.append(measureList[i].ToJSON());
        }
		json["numMeasures"] = JSONmeasures.size();
		json["measures"] = JSONmeasures;
    }

    /* add drugs */
    if (drugList.size() > 0) {
        QJsonArray JSONdrugs;
        for (int i=0; i < drugList.size(); i++) {
            JSONdrugs.append(drugList[i].ToJSON());
        }
		json["numDrugs"] = JSONdrugs.size();
		json["drugs"] = JSONdrugs;
    }

    return json;
}
