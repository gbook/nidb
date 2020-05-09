/* ------------------------------------------------------------------------------
  NIDB study.h
  Copyright (C) 2004 - 2020
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
#include "nidb.h"


class study
{
public:
	study();
	study(int id, nidb *a);
	nidb *n;

	void PrintStudyInfo();

	int studynum;
	QString uid;
	int studyid;
	QString studytype;
	int subjectid;
	QString studypath;
	bool studyPathExists;
	int enrollmentid;
	int projectid;
	QDateTime studydatetime;
	QString modality;

	bool isValid = true;
	QString msg;

private:
	void LoadStudyInfo();
};

#endif // STUDY_H
