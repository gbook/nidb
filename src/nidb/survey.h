/* ------------------------------------------------------------------------------
  NIDB survey.h
  Copyright (C) 2004 - 2026
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

#ifndef SURVEY_H
#define SURVEY_H
#include <QString>
#include "nidb.h"

class survey
{
public:
    survey();
    survey(qint64 id, nidb *a);
    ~survey();
    nidb *n;

    void PrintSurveyInfo();
    bool AddToDatabase();

    /* data from 'observation_surveys' table */
    QDateTime dateEnd;
    QDateTime dateEntry;
    QDateTime dateStart;
    QString experimenter;
    QString notes;
    QString rater;
    QString visit;
    int instrumentRowID = -1;
    qint64 surveyRowID = -1;

    bool isValid = false;
    QString msg;

private:
    void LoadSurveyInfo();
};

#endif // SURVEY_H
