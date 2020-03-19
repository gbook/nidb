/* ------------------------------------------------------------------------------
  NIDB analysis.h
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

#ifndef ANALYSIS_H
#define ANALYSIS_H
#include <QString>
#include "nidb.h"

class analysis
{
public:
	analysis(qint64 id, nidb *a, bool c=false);
	analysis(int pipelineid, int studyid, nidb *a, bool c=false);
	nidb *n;

	void PrintAnalysisInfo();

	QString analysispath;
	qint64 analysisid = -1;

	int studynum = -1;
	int studyid = -1;
	QString studyDateTime;
	QString uid;
	int subjectid = -1;

	QString pipelinename;
	int pipelineversion = -1;
	int pipelineid = -1;
	int pipelinelevel = -1;
	QString pipelinedirectory;
	QString pipelinedirstructure;
	int jobid = -1;
	bool exists = true;
	bool isValid = true;
	bool rerunResults = false;
	bool runSupplement = false;
	QString msg;

private:
	void LoadAnalysisInfo();
	bool useClusterPaths;
};

#endif // ANALYSIS_H
