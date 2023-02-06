/* ------------------------------------------------------------------------------
  Squirrel study.h
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

#ifndef SQUIRRELSTUDY_H
#define SQUIRRELSTUDY_H

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
    squirrelStudy(const squirrelStudy& s);

    bool addSeries(squirrelSeries s);
    bool addAnalysis(squirrelAnalysis a);
    void PrintStudy();
    QJsonObject ToJSON();

    /* study info */
    int number; /*!< Unique study number. Must be unique within the subject */
    QDateTime dateTime; /*!< start datetime of the study */
    double ageAtStudy;
    double height; /*!< height in meters */
    double weight; /*!< weight in kg */
    QString modality; /*!< study modality */
    QString description; /*!< Description of the imaging study */
    QString studyUID; /*!< StudyInstanceUID */
    QString visitType; /*!< Description of the visit, eg. pre, post */
    QString dayNumber; /*!< Day number for repeated studies or clinical trials. eg. 6 for 'day 6' */
    QString timePoint; /*!< Ordinal time point for repeated studies. eg. 3 for the 3rd consecutive imaging study */
    QString equipment;

    QList<squirrelSeries> seriesList; /*!< List of series attached to this study */
    QList<squirrelAnalysis> analysisList; /*!< List of analyses attached to this study */

    QString virtualPath; /*!< path within the squirrel package, no leading slash */
};

#endif // SQUIRRELSTUDY_H
