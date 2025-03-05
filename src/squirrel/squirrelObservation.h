/* ------------------------------------------------------------------------------
  Squirrel observation.h
  Copyright (C) 2004 - 2025
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

#ifndef SQUIRRELOBSERVATION_H
#define SQUIRRELOBSERVATION_H
#include <QtSql>
#include <QString>
#include <QDateTime>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The observation class
 */
class squirrelObservation
{
public:
    squirrelObservation(QString dbID);
    QJsonObject ToJSON();
    QString PrintObservation();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    QString GetDatabaseUUID() { return databaseUUID; }
    void SetDatabaseUUID(QString dbID) { databaseUUID = dbID; }

    qint64 subjectRowID;

    /* JSON elements */
    QDateTime DateEnd;          /*!< end date of the observationment */
    QDateTime DateRecordCreate; /*!< date the record was created */
    QDateTime DateRecordEntry;  /*!< date the record was entered */
    QDateTime DateRecordModify; /*!< date the record was modified */
    QDateTime DateStart;        /*!< start date of the observationment (required) */
    QString Description;        /*!< extended observationment description */
    QString InstrumentName;     /*!< name of the instrument (test, assessment, etc) from which this observation came */
    QString ObservationName;    /*!< observation name (required) */
    QString Notes;              /*!< notes about the observation */
    QString Rater;              /*!< name or username of the person who rated the observation */
    QString Value;              /*!< value, in string or number stored as a string */
    double Duration;            /*!< duration of the observation, in seconds */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
    QString databaseUUID;
};

#endif // SQUIRRELOBSERVATION_H
