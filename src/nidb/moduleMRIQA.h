/* ------------------------------------------------------------------------------
  NIDB moduleMRIQA.h
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

#ifndef MODULEMRIQA_H
#define MODULEMRIQA_H
#include "nidb.h"
#include "series.h"

class moduleMRIQA
{
public:
    moduleMRIQA();
    moduleMRIQA(nidb *n);
    ~moduleMRIQA();

    int Run();
    bool QA(qint64 seriesid);
    bool GetQAStats(QString f, double &pvsnr, double &iosnr, QString &msg);
    bool GetMovementStats(QString f, double &maxrx, double &maxry, double &maxrz, double &maxtx, double &maxty, double &maxtz, double &maxax, double &maxay, double &maxaz, double &minrx, double &minry, double &minrz, double &mintx, double &minty, double &mintz, double &minax, double &minay, double &minaz, QString &msg);
    void GetMinMax(QVector<double> a, double &min, double &max);
    QVector<double> Derivative(QVector<double> a);
    void WriteQALog(QString dir, QString log);

private:
    nidb *n;
};

#endif // MODULEMRIQA_H
