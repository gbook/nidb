/* ------------------------------------------------------------------------------
  Squirrel measure.h
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

#ifndef SQUIRRELMEASURE_H
#define SQUIRRELMEASURE_H
#include <QtSql>
#include <QString>
#include <QDateTime>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The measure class
 */
class squirrelMeasure
{
public:
    squirrelMeasure();
    QJsonObject ToJSON();
	void PrintMeasure();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(int id) { objectID = id; }

    /* JSON elements */
    qint64 subjectRowID;
    QString measureName;        /*!< measure name (required) */
    QDateTime dateStart;        /*!< start date of the measurement (required) */
    QDateTime dateEnd;          /*!< end date of the measurement */
    QString instrumentName;     /*!< name of the instrument (test, assessment, etc) from which this measure came */
    QString rater;              /*!< name or username of the person who rated the measure */
    QString notes;              /*!< notes about the measure */
    QString value;              /*!< value, in string or number stored as a string */
    QString description;        /*!< extended measurement description */
    double duration;            /*!< duration of the measure, in seconds */
    QDateTime dateRecordEntry;  /*!< date the record was entered */
    QDateTime dateRecordCreate;  /*!< date the record was created */
    QDateTime dateRecordModify;  /*!< date the record was modified */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
};

#endif // SQUIRRELMEASURE_H
