/* ------------------------------------------------------------------------------
  Squirrel study.h
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

#ifndef STUDY_H
#define STUDY_H

#include <QString>
#include <QDateTime>
#include "series.h"

/**
 * @brief The study class
 *
 * provides details of a study
 */
class study
{
public:
	study();

	bool appendSeries(series s);

	/* subject info */
	QString studyNum; /*!< Unique study number. Must be unique within the subject */
	QString description; /*!< Description of the imaging study */
	QString visitType; /*!< Description of the visit, eg. pre, post */
	QString dayNum; /*!< Day number for repeated studies or clinical trials. eg. 6 for 'day 6' */
	QString timePoint; /*!< Ordinal time point for repeated studies. eg. 3 for the 3rd consecutive imaging study */
	QDateTime dateTime; /*!< start datetime of the study */
	QString modality; /*!< study modality */

	QString dirpath; /*!< Relative path to the subject data */
	QList<study> studies; /*!< List of studies attached to this subject */
};

#endif // STUDY_H
