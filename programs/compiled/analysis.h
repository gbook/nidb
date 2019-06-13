/* ------------------------------------------------------------------------------
  NIDB analysis.h
  Copyright (C) 2004 - 2019
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

#ifndef ANALYSIS_H
#define ANALYSIS_H
#include <QString>
#include "nidb.h"

class analysis
{
public:
	analysis(int id, nidb *a);
	nidb *n;

	void PrintAnalysisInfo();

	QString analysispath;
	int analysisid;

	int studynum;
	int studyid;
	QString uid;
	int subjectid;

	QString pipelinename;
	int pipelineversion;
	int pipelineid;
	int pipelinelevel;
	QString pipelinedirectory;
	QString pipelinedirstructure;
	int jobid;
	bool isValid = true;
	QString msg;
private:
	void LoadAnalysisInfo();
};

#endif // ANALYSIS_H
