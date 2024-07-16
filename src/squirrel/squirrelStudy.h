/* ------------------------------------------------------------------------------
  Squirrel study.h
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

#ifndef SQUIRRELSTUDY_H
#define SQUIRRELSTUDY_H
#include <QtSql>
#include <QString>
#include <QDateTime>
#include <QJsonObject>
#include <QJsonArray>
#include "squirrelSeries.h"
#include "squirrelAnalysis.h"

/**
 * @brief The study class
 *
 * provides details of a study
 */
class squirrelStudy
{
public:
    squirrelStudy();
    QString PrintStudy();
    QString PrintTree(bool isLast);
    QJsonObject ToJSON();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool Remove();
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    void SetDirFormat(QString subject_DirFormat, QString study_DirFormat) {subjectDirFormat = subject_DirFormat; studyDirFormat = study_DirFormat; }
    QString VirtualPath();
    QList<QPair<QString,QString>> GetStagedFileList();
    int GetNextSeriesNumber();

    qint64 subjectRowID;

    /* JSON elements */
    QDateTime DateTime;         /*!< start datetime of the study */
    QString Description;            /*!< Description of the imaging study */
    QString Equipment;              /*!< Equipment the study was run on */
    QString Modality;   /*!< study modality */
    QString StudyUID;               /*!< DICOM StudyInstanceUID */
    QString VisitType;              /*!< Description of the visit, eg. pre, post */
    double AgeAtStudy;        /*!< age in years at the time of the study */
    double Height;            /*!< height in meters */
    double Weight;            /*!< weight in kg */
    int DayNumber;              /*!< Day number for repeated studies or clinical trials. eg. 6 for 'day 6' */
    int SequenceNumber;
    int StudyNumber;             /*!< Unique study number. Must be unique within the subject */
    int TimePoint;              /*!< Ordinal time point for repeated studies. eg. 3 for the 3rd consecutive imaging study */

    /* lib variables */
    QList<squirrelSeries> seriesList; /*!< List of series attached to this study */
    QList<squirrelAnalysis> analysisList; /*!< List of analyses attached to this study */

private:
    bool valid;
    QString err;
    qint64 objectID;
    QString subjectDirFormat;
    QString studyDirFormat;
};

#endif // SQUIRRELSTUDY_H
