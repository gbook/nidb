/* ------------------------------------------------------------------------------
  Squirrel subject.h
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

#ifndef SQUIRRELSUBJECT_H
#define SQUIRRELSUBJECT_H

#include <QString>
#include <QDate>
#include <QJsonObject>
#include <QJsonArray>
#include "squirrelStudy.h"
#include "squirrelMeasure.h"
#include "squirrelDrug.h"

/**
 * @brief The subject class
 *
 * contains details of a subject
 */
class squirrelSubject
{
public:
	squirrelSubject();

	bool addStudy(squirrelStudy s);
	bool addMeasure(squirrelMeasure m);
	bool addDrug(squirrelDrug d);
	void PrintSubject();
	QJsonObject ToJSON();

    /* subject info */
    QString ID; /*!< Unique identifier. Must be unique within the squirrel package */
	QStringList alternateIDs; /*!< List of alternate subject IDs */
    QString GUID;  /*!< globally unique identifier, from NIMH's NDA */
	QDate dateOfBirth; /*!< Date of birth. Not required, but can be useful to calculate age during studies. Can also contain only year, or only year and month */
	QString sex; /*!< Sex at birth (biological sex) */
    QString gender; /*!< Gender identity */
    QString ethnicity1; /*!< Ethnicity: hispanic, non-hispanic */
    QString ethnicity2; /*!< Race: americanindian, asian, black, hispanic, islander, white */

    QString dirpath; /*!< Relative path to the subject data */
	QList<squirrelStudy> studyList; /*!< List of studies attached to this subject */
	QList<squirrelMeasure> measureList; /*!< List of measures (variables) attached to this subject */
	QList<squirrelDrug> drugList; /*!< List of drugs attached to this subject */

	QString virtualPath; /*!< path within the squirrel package, no leading slash */
};

#endif // SQUIRRELSUBJECT_H
