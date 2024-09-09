/* ------------------------------------------------------------------------------
  NIDB analysis.h
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

#ifndef ANALYSIS_H
#define ANALYSIS_H
#include <QString>
#include "nidb.h"
#include "squirrelAnalysis.h"

class analysis
{
public:
	analysis(qint64 id, nidb *a, bool c=false);
	analysis(int pipelineid, int studyid, nidb *a, bool c=false);
	nidb *n;

	void PrintAnalysisInfo();
    squirrelAnalysis GetSquirrelObject();

    QDateTime clusterEndDate; /*!< datetime the analysis finished running on the cluster */
    QDateTime clusterStartDate; /*!< datetime the analysis started running on the cluster */
    QDateTime endDate; /*!< datetime the analysis finished setup */
    QDateTime startDate; /*!< datetime the analysis started setup (copying data) */
    QString analysispath; /*!< disk path to the analysis */
    QString hostname;  /*!< hostname on which the analysis was run */
    QString notes; /*!< user-specified notes */
    QString status; /*!< the last status */
    QString statusmessage; /*!< the last status message */
    bool isBad; /*!< true if analysis is marked as bad */
    bool isComplete; /*!< true if analysis is marked as complete */
    int numSeries; /*!< number of series downloaded */
    qint64 analysisid = -1; /*!< analysis RowID */
    qint64 diskSize; /*!< size on disk, in bytes */

    /* subject/study information */
    QString studyDateTime; /*!< study datetime */
    QString uid; /*!< analysis UID */
    int studyid = -1; /*!< study RowID */
    int studynum = -1; /*!< study number */
    int subjectid = -1; /*!< subject RowID */

    /* pipeline information */
    QString msg;
    QString pipelinedirectory;
    QString pipelinedirstructure;
    QString pipelinename;
    bool exists = true;
    bool isValid = true;
    bool rerunResults = false;
    bool runSupplement = false;
    int jobid = -1;
    int pipelineid = -1;
    int pipelinelevel = -1;
    int pipelineversion = -1;

	/* export functions */
	QJsonObject GetJSONObject();

private:
	void LoadAnalysisInfo();
	bool useClusterPaths;
};

#endif // ANALYSIS_H
