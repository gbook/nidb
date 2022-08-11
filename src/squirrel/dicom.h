/* ------------------------------------------------------------------------------
  Squirrel dicom.h
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


#ifndef DICOM_H
#define DICOM_H

#include "utils.h"
#include "squirrelImageIO.h"
#include "squirrel.h"

class dicom
{
public:
    dicom();

	bool LoadToSquirrel(QString dir, QString binpath, squirrel *sqrl, QString &msg);

    qint64 NumFiles() { return numFiles; }

	//squirrel *sqrl;

private:
    qint64 numFiles;
    QMap<QString, QMap<QString, QMap<QString, QStringList> > > dcms;

};

#endif // DICOM_H
