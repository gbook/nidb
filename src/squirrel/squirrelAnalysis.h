/* ------------------------------------------------------------------------------
  Squirrel analysis.h
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


#ifndef SQUIRRELANALYSIS_H
#define SQUIRRELANALYSIS_H
#include <QtSql>
#include <QString>
#include <QDateTime>
#include <QJsonObject>

/**
 * @brief The analysis class
 */
class squirrelAnalysis
{
public:
    squirrelAnalysis(QString dbID);
    QJsonObject ToJSON();
    QString PrintAnalysis();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    void SetDirFormat(QString subject_DirFormat, QString study_DirFormat) {subjectDirFormat = subject_DirFormat; studyDirFormat = study_DirFormat; }
    QString VirtualPath();
    QList<QPair<QString,QString>> GetStagedFileList();
    QString GetDatabaseUUID() { return databaseUUID; }
    void SetDatabaseUUID(QString dbID) { databaseUUID = dbID; }

    /* squirrel database variables */
    qint64 studyRowID;          /*!< database row id of the parent study */
    qint64 pipelineRowID;       /*!< database row id of the parent pipeline */

    /* JSON variables */
    QDateTime DateClusterEnd;   /*!< datetime the analysis finished running on the cluster */
    QDateTime DateClusterStart; /*!< datetime the analysis started running on the cluster */
    QDateTime DateEnd;          /*!< datetime the analysis ended */
    QDateTime DateStart;        /*!< datetime the analysis was started, includes the setup time */
    QString AnalysisName;       /*!< name of this analysis, usually same as the pipeline name */
    QString Hostname;           /*!< hostname on which the analysis was run */
    QString LastMessage;        /*!< if the analysis had a status message, the last would be stored here */
    QString PipelineName;       /*!< name of the pipeline */
    QString Status;             /*!< status of the analysis. eg running, complete, pending */
    bool Successful;            /*!< true if the analysis completed successfully */
    int PipelineVersion;        /*!< pipeline version */
    int SeriesCount;            /*!< number of series downloaded into the analysis */
    qint64 RunTime;             /*!< total run time in minutes (elapsed wall time) of the analysis after analysis was submitted to the cluster */
    qint64 SetupTime;           /*!< total time in minutes (elapsed wall time) to setup the analysis, most time will be spent copying data into the analysis directories */
    qint64 Size;                /*!< disk size in bytes of the analysis */

    /* lib variables */
    QStringList stagedFiles;    /*!< staged file list: list of files in their own original paths which will be copied in before the package is zipped up */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
    QString subjectDirFormat = "orig";
    QString studyDirFormat = "orig";
    QString databaseUUID;
};

#endif // SQUIRRELANALYSIS_H
