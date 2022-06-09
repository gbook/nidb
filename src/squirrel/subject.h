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

#ifndef SUBJECT_H
#define SUBJECT_H

#include <QString>
#include <QDate>
#include <QJsonObject>
#include <QJsonArray>
#include "study.h"
#include "measure.h"
#include "drug.h"

/**
 * @brief The subject class
 *
 * contains details of a subject
 */
class subject
{
public:
    subject();

    bool addStudy(study s);
    void PrintSubject();
    QJsonObject ToJSON();

    /* subject info */
    QString ID; /*!< Unique identifier. Must be unique within the squirrel package */
    QStringList altUIDs; /*!< List of alternate subject IDs */
    QString GUID;  /*!< globally unique identifier, from NIMH's NDA */
    QString sex; /*!< Sex at birth (biological sex) */
    QString gender; /*!< Gender identity */
    QDate birthdate; /*!< Date of birth. Not required, but can be useful to calculate age during studies. Can also contain only year, or only year and month */
    QString ethnicity1; /*!< Ethnicity: hispanic, non-hispanic */
    QString ethnicity2; /*!< Race: americanindian, asian, black, hispanic, islander, white */

    QString dirpath; /*!< Relative path to the subject data */
    QList<study> studyList; /*!< List of studies attached to this subject */
    QList<measure> measureList; /*!< List of measures (variables) attached to this subject */
    QList<drug> drugList; /*!< List of drugs attached to this subject */

private:
    QString virtualPath; /*!< path within the squirrel package, no leading slash */

};

#endif // SUBJECT_H
