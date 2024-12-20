/* ------------------------------------------------------------------------------
  NIDB minipipeline.h
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

#ifndef MINIPIPELINE_H
#define MINIPIPELINE_H
#include "nidb.h"

struct miniPipelineScript {
    int id;
    QString filename;
    int version;
    bool isExec;
    bool isEntryPoint;
    QByteArray file;
    qint64 filesize;
    QString parameterList;
    QDateTime mDate;
    QDateTime cDate;
};

class minipipeline
{
public:
    minipipeline();
    minipipeline(int id, nidb *a);
    nidb *n;

    bool WriteScripts(QString dir, QString &m);

    /* object variables */
    QString msg;
    bool isValid = true;

    /* minipipeline variables */
    QString name;
    int version;
    QDateTime createDate;
    QDateTime modifyDate;

    QList<miniPipelineScript> scripts;
    QString entrypoint;

    QJsonObject GetJSONObject(QString path);

private:
    void LoadMiniPipelineInfo();

    int minipipelineid;
};

#endif // MINIPIPELINE_H
