/* ------------------------------------------------------------------------------
  Squirrel analysis.h
  Copyright (C) 2004 - 2023
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


#ifndef SQUIRRELANALYSIS_H
#define SQUIRRELANALYSIS_H
#include <QString>
#include <QDateTime>
#include <QJsonObject>

/**
 * @brief The analysis class
 */
class squirrelAnalysis
{
public:
    squirrelAnalysis();
    QJsonObject ToJSON();
    void PrintAnalysis();

    QString pipelineName; /*!< name of the pipeline */
    int pipelineVersion; /*!< pipeline version */
    //int analysisNumber; /*!< studies can have multiple analyses this should be unique for this study */
    QDateTime clusterStartDate; /*!< datetime the analysis started running on the cluster */
    QDateTime clusterEndDate; /*!< datetime the analysis finished running on the cluster */
    QDateTime startDate; /*!< datetime the analysis was started, includes the setup time */
    QDateTime endDate; /*!< datetime the analysis ended */
    qint64 setupTime; /*!< total time (wall time) to setup the analysis, most time will be spent copying data into the analysis directories */
    qint64 runTime; /*!< total run time (wall time) of the analysis after analysis was submitted to the cluster */
    int numSeries; /*!< number of series downloaded into the analysis */
    bool successful; /*!< true if the analysis completed successfully */
    qint64 size; /*!< disk size in bytes of the analysis */
    QString hostname; /*!< hostname on which the analysis was run */
    QString status; /*!< status of the analysis. eg running, complete, pending */
    QString lastMessage; /*!< if the analysis had a status message, the last would be stored here */

private:
    QString virtualPath; /*!< path within the squirrel package, no leading slash */

};

#endif // SQUIRRELANALYSIS_H
