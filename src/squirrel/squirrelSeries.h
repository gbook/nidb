/* ------------------------------------------------------------------------------
  Squirrel series.h
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

#ifndef SQUIRRELSERIES_H
#define SQUIRRELSERIES_H
#include <QtSql>
#include <QString>
#include <QHash>
#include <QList>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The series class
 *
 * provides details of a series
 */
class squirrelSeries
{
public:
    squirrelSeries(QString dbID);
    QString PrintSeries();
    QString PrintTree(bool isLast);
    QJsonObject ToJSON();
    QJsonObject ParamsToJSON();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool Remove();
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    void SetDirFormat(QString subject_DirFormat, QString study_DirFormat, QString series_DirFormat) {subjectDirFormat = subject_DirFormat; studyDirFormat = study_DirFormat; seriesDirFormat = series_DirFormat; }
    QString VirtualPath();
    void AnonymizeParams();
    QList<QPair<QString,QString>> GetStagedFileList();
    QString GetDatabaseUUID() { return databaseUUID; }
    void SetDatabaseUUID(QString dbID) { databaseUUID = dbID; }

    qint64 studyRowID;
    qint64 experimentRowID = -1;

    /* JSON elements */
    QDateTime DateTime;             /*!< Series datetime */
    QHash<QString, QString> params; /*!< Hash containing experimental parameters. eg MR params */
    QString BIDSEntity;             /*!< BIDS entity (anat, func, etc) */
    QString BIDSSuffix;             /*!< BIDS suffix (T1w, T2w, etc) */
    QString BIDSTask;               /*!< BIDS task */
    QString BIDSRun;                /*!< BIDS run number */
    QString BIDSPhaseEncodingDirection; /*!< BIDS phase encoding direction */
    QString Description;            /*!< Description of the series */
    QString Protocol;               /*!< Protocol (may differ from description) */
    QString SeriesUID;              /*!< SeriesInstanceUID */
    int SequenceNumber = 0;
    int Run = 1;                    /*!< Run number, if multiple identical series */
    qint64 BehavioralFileCount = 0; /*!< Number of files associated with the behavioral data */
    qint64 BehavioralSize = 0;      /*!< total size in bytes of the beh data */
    qint64 FileCount = 0;           /*!< Number of files associated with the series */
    qint64 SeriesNumber = -1;       /*!< Series number. must be unique to the study */
    qint64 Size = 0;                /*!< total size in bytes of the series */

    /* lib variables */
    QStringList stagedFiles;        /*!< staged file list: list of raw files in their own directories before the package is zipped up */
    QStringList stagedBehFiles;     /*!< staged beh file list: list of raw files in their own directories before the package is zipped up */

    QStringList files;              /* actual files in the package, not staged files */
    QStringList behFiles;

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
    QString subjectDirFormat = "orig";
    QString studyDirFormat = "orig";
    QString seriesDirFormat = "orig";
    QString databaseUUID;
};

#endif // SQUIRRELSERIES_H
