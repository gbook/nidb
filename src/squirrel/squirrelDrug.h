/* ------------------------------------------------------------------------------
  Squirrel drug.h
  Copyright (C) 2004 - 2024
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
#include <QtSql>
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
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(int id) { objectID = id; }

    /* squirrel database variables */
    qint64 subjectRowID;        /*!< database row ID of the parent object */

    /* JSON elements */
    QDateTime DateEnd;           /*!< drug end date */
    QDateTime DateRecordCreate;  /*!< date the record was created */
    QDateTime DateRecordEntry;   /*!< date the record was entered */
    QDateTime DateRecordModify;  /*!< date the record was modified */
    QDateTime DateStart;         /*!< drug start date (required) */
    QString AdministrationRoute; /*!< drug delivery route (oral, IV, IM, etc) */
    QString Description;         /*!< longer description of the drug and dosing */
    QString DoseFrequency;       /*!< string representation of dose frequency, ie '2 tablets daily' */
    QString DoseKey;             /*!< for clinical trials, the dose key */
    QString DoseString;          /*!< full dose string (example "tylenol 325mg twice daily by mouth") */
    QString DoseUnit;            /*!< mg, g, ml, tablets, etc */
    QString DrugClass;           /*!< drug class */
    QString DrugName;            /*!< drug name (required) */
    QString Notes;               /*!< freeform field for notes */
    QString Rater;               /*!< rater/experimenter/prescriber */
    double DoseAmount;           /*!< dose amount (required) */
    //QString frequencyModifier;  /*!< 'every' or 'times' */
    //QString frequencyUnit;      /*!< the time of the frequency: bolus, dose, second, minute, hour, day, week, month, year */
    //double frequencyValue;      /*!< the frequency as a number */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
};

#endif // SQUIRRELDRUG_H
