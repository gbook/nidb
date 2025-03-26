/* ------------------------------------------------------------------------------
  Squirrel modify.h
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

#ifndef MODIFY_H
#define MODIFY_H
#include "squirrel.h"

class modify
{
public:
    modify();

    bool DoModify(QString packagePath, QString operation, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m);
    void PrintVariables(ObjectType object);
    bool AddObject(QString packagePath, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m);
    bool RemoveObject(QString packagePath, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m);
    bool UpdateObject(QString packagePath, ObjectType object, QString dataPath, QString objectData, QString objectID, QString subjectID, int studyNum, QString &m);
    bool SplitByModality(QString packagePath, QString dataPath, QString objectData, QString objectID, QString &m);
    bool RemovePHI(QString packagePath, QString dataPath, QString &m);
};

#endif // MODIFY_H
