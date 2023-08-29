/* ------------------------------------------------------------------------------
  Squirrel subject.cpp
  Copyright (C) 2004 - 2023
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

#include "squirrelSubject.h"
#include "utils.h"


/* ------------------------------------------------------------ */
/* ----- subject ---------------------------------------------- */
/* ------------------------------------------------------------ */
squirrelSubject::squirrelSubject() {
    sex = 'U';
    gender = 'U';
	dateOfBirth = QDate::fromString("0000-00-00", "YYYY-MM-dd");
}


/* ------------------------------------------------------------ */
/* ----- addStudy --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add a study to this subject
 * @param s squirrelStudy to be added
 * @return true if added, false otherwise
 */
bool squirrelSubject::addStudy(squirrelStudy s) {

    /* check size of the study list before and after adding */
    qint64 size = studyList.size();

    /* check if this study already exists, by UID */
    bool exists = false;
    for (int i=0; i<studyList.size(); i++)
        if ((studyList[i].studyUID == s.studyUID) && (s.studyUID != ""))
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
/* ----- addMeasure ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add a measure to this subject
 * @param m squirrelMeasure to be added
 * @return true if added, false otherwise
 */
bool squirrelSubject::addMeasure(squirrelMeasure m) {

	/* check size of the measure list before and after adding */
	qint64 size = measureList.size();

	/* check if this measure already exists, by UID */
	bool exists = false;
	//for (int i=0; i<studyList.size(); i++)
	//	if (studyList.at(i).studyUID == s.studyUID)
	//        exists = true;

	/* if it doesn't exist, append it */
	if (!exists)
		measureList.append(m);

	if (measureList.size() > size)
		return true;
	else
		return false;
}


/* ------------------------------------------------------------ */
/* ----- addDrug ---------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Add a drug to this subject
 * @param d squirrelDrug to be added
 * @return true if added, false otherwise
 */
bool squirrelSubject::addDrug(squirrelDrug d) {

	/* check size of the drug list before and after adding */
	qint64 size = drugList.size();

	/* check if this drug already exists, by UID */
	bool exists = false;
	//for (int i=0; i<studyList.size(); i++)
	//	if (studyList.at(i).studyUID == s.studyUID)
	//        exists = true;

	/* if it doesn't exist, append it */
	if (!exists)
        drugList.append(d);

	if (drugList.size() > size)
		return true;
	else
		return false;
}


/* ------------------------------------------------------------ */
/* ----- GetNextStudyNumber ----------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Gets the next study number for this subject
 * @return the next study number
 */
qint64 squirrelSubject::GetNextStudyNumber() {

    /* find the current highest study number */
    qint64 maxnum = 0;
    for (int i=0; i<studyList.size(); i++)
        if (studyList[i].number > maxnum)
            maxnum = studyList[i].number;

    return maxnum+1;
}


/* ------------------------------------------------------------ */
/* ----- PrintSubject ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Print subject details
 */
void squirrelSubject::PrintSubject() {

    Print("\t\t----- SUBJECT -----");
    Print(QString("\t\tID: %1").arg(ID));
    Print(QString("\t\tAlternateIDs: %1").arg(alternateIDs.join(",")));
    Print(QString("\t\tGUID: %1").arg(GUID));
    Print(QString("\t\tSex: %1").arg(sex));
    Print(QString("\t\tGender: %1").arg(gender));
    Print(QString("\t\tdateOfBirth: %1").arg(dateOfBirth.toString()));
    Print(QString("\t\tEthnicity1: %1").arg(ethnicity1));
    Print(QString("\t\tEthnicity2: %1").arg(ethnicity2));
}


/* ------------------------------------------------------------ */
/* ----- ToJSON ----------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief Get JSON object for this subject
 * @return a JSON object containing the entire subject
 */
QJsonObject squirrelSubject::ToJSON() {
    QJsonObject json;

    json["ID"] = ID;
	json["alternateIDs"] = QJsonArray::fromStringList(alternateIDs);
    json["GUID"] = GUID;
	json["dateOfBirth"] = dateOfBirth.toString("yyyy-MM-dd");
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
