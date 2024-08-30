/* ------------------------------------------------------------------------------
  Squirrel subject.h
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

#ifndef SQUIRRELSUBJECT_H
#define SQUIRRELSUBJECT_H
#include <QtSql>
#include <QString>
#include <QDate>
#include <QJsonObject>
#include <QJsonArray>
#include "squirrelStudy.h"
#include "squirrelObservation.h"
#include "squirrelIntervention.h"

/**
 * @brief The subject class
 *
 * contains details of a subject
 */
class squirrelSubject
{
public:
    squirrelSubject();

    /* functions */
    QString PrintDetails();
    QString PrintTree(bool isLast);
    QString CSVLine();
    QJsonObject ToJSON();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool Remove();          /* remove the subject (and all child studies and series) from the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    void SetDirFormat(QString subject_DirFormat) {subjectDirFormat = subject_DirFormat; }
    QString VirtualPath();
    QList<QPair<QString,QString>> GetStagedFileList();
    int GetNextStudyNumber();

    /* JSON elements */
    QDate DateOfBirth;      /*!< Date of birth. Not required, but can be useful to calculate age during studies. Can also contain only year... or contain only year and month */
    QString Ethnicity1;     /*!< Ethnicity: hispanic, non-hispanic */
    QString Ethnicity2;     /*!< Race: americanindian, asian, black, hispanic, islander, white */
    QString GUID;           /*!< globally unique identifier, from NIMH's NDA */
    QString Gender;         /*!< Gender identity */
    QString ID;             /*!< --- Unique identifier --- Must be unique within the squirrel package */
    QString Sex;            /*!< Sex at birth (biological sex) */
    QStringList AlternateIDs;   /*!< List of alternate subject IDs */
    int SequenceNumber;

private:
    bool valid;
    QString err;
    qint64 objectID;
    QString subjectDirFormat;
};

#endif // SQUIRRELSUBJECT_H
