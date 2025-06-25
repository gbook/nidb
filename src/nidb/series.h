/* ------------------------------------------------------------------------------
  NIDB series.h
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

#ifndef SERIES_H
#define SERIES_H
#include <QString>
#include "nidb.h"
#include "squirrelSeries.h"

class series
{
public:
    series();
    series(qint64 id, QString m, nidb *a);
    nidb *n;

    void PrintSeriesInfo();
    squirrelSeries GetSquirrelObject(QString databaseUUID);

    QDateTime datetime;
    QString desc;
    QString protocol;
    BIDSMapping bidsMapping;
    QString behpath;
    QString datapath;
    QString datatype;
    QString imagetype;
    QString modality;
    QString seriespath;
    QString uid;
    bool isderived;
    int enrollmentid;
    int projectid;
    int seriesnum;
    int studyid;
    int studynum;
    int subjectid;
    qint64 seriesid;

    bool isValid = true;
    QString msg;

    bool ChangeSeriesPath(int studyid, int newSeriesNum);

private:
    void LoadSeriesInfo();
};

#endif // SERIES_H
