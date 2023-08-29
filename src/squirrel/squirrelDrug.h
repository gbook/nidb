/* ------------------------------------------------------------------------------
  Squirrel drug.h
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

#ifndef SQUIRRELDRUG_H
#define SQUIRRELDRUG_H
#include <QDateTime>
#include <QString>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The drug class
 *
 * Most string fields are freeform, and not all are required
 */
class squirrelDrug
{
public:
	squirrelDrug();
    QJsonObject ToJSON();
	void PrintDrug();

    QString drugName; /*!< drug name (required) */
    QDateTime dateStart; /*!< drug start date (required) */
    QDateTime dateEnd; /*!< drug end date */
    double doseAmount; /*!< dose amount (required) */
    QString doseFrequency; /*!< string representation of dose frequency, ie '2 tablets daily' */
    QString route; /*!< drug delivery route (oral, IV, IM, etc) */
    QString type; /*!< drug class */
    QString doseKey; /*!< for clinical trials, the dose key */
    QString doseUnit; /*!< mg, g, ml, tablets, etc */
    QString frequencyModifier; /*!< 'every' or 'times' */
    double frequencyValue; /*!< the frequency as a number */
    QString frequencyUnit; /*!< the time of the frequency: bolus, dose, second, minute, hour, day, week, month, year */
    QString description; /*!< longer description of the drug and dosing */
    QString rater; /*!< rater/experimenter/prescriber */
    QString notes; /*!< freeform field for notes */
    QDateTime dateEntry; /*!< date of the data entry */
	QDateTime dateRecordEntry;
	QDateTime dateRecordCreate;
	QDateTime dateRecordModify;

};

#endif // SQUIRRELDRUG_H
