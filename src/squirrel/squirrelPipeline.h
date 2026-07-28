/* ------------------------------------------------------------------------------
  Squirrel pipeline.h
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


#ifndef SQUIRRELPIPELINE_H
#define SQUIRRELPIPELINE_H
#include <QtSql>
#include <QString>
#include <QDateTime>
#include <QJsonObject>
#include <QJsonArray>
#include <QJsonDocument>

/**
 * @brief The dataStep struct
 *
 * This is primarily used by NiDB for inter-instance pipeline sharing but can also be used
 * for other sharing contexts
 */
struct dataStep {
    //QString NumberImagesCriteria;   /*!< not sure */
    QString ExportBehavioralDirectoryFormat;    /*!< nobeh, behroot, behseries, behseriesdir */
    QString ExportBehavioralDirectoryName;      /*!< if behFormat writes data to a sub-directory, this is the name of that sub-doirectory */
    QString ExportDataFormat;                   /*!< native, dicom, nifti3d, nift4d, analyze3d, analyze4d, bids */
    QString ExportSubDirectoryName;             /*!< directory relative to {analysisroot}, where this data will be written (formerly 'Location') */
    QString SearchAssociationType;              /*!< study or subject (required) */
    QString SearchDataLevel;                    /*!< nearestintime, samestudy */
    QString SearchImageType;                    /*!< comma separated list of image types, often derived from the DICOM ImageType tag (0008:0008) */
    QString SearchModality;                     /*!< modality of the data to search for */
    QString SearchNumberBOLDreps;               /*!< if seriesCriteria is 'usecriteria', then this is the number of bold reps to search for, ie '<=450' */
    QString SearchProtocol;                     /*!< protocol name(s) to search for */
    QString SearchSeriesCriteria;               /*!< criteria for downloading data from a study, if more than one series matches the protocol: all, first, last, largest, smallest, usecriteria */
    int StepNumber;                             /*!< order of this step */

    struct flag {
        bool ExportGzip;                    /*!< whether Nifti data should be zipped (.nii.gz) */
        bool ExportPreserveSeriesNumber;    /*!< whether to preserve the series number, if writing series directories. Otherwise the series directories are generated sequentially starting at 1 */
        bool ExportWritePhaseDirectory;     /*!< whether to place data into a sub-directory based on the phase-encoding direction */
        bool ExportWriteSeriesDirectory;    /*!< true to write each series to an individually numbered directory, otherwise write it to the directory specified in 'location' */
        bool IsEnabled;                     /*!< whether this step is enabled */
        bool IsOptional;                    /*!< if the step is optional */
        bool IsPrimaryProtocol;             /*!< true if this is the primary protocol. this determines if this study will be used as the parent for child pipelines */
    } flags;
};


/**
 * @brief The pipeline class
 *
 * pipelines contain 3 sections: pipeline info, dataspec, steps (the script)
 *
 * [NiDB] fields are used when sharing pipelines between NiDB instances.
 * These are optional in the squirrel format spec, but the fields
 * can be useful when sharing pipelines in other contexts
 */
class squirrelPipeline
{
public:
    squirrelPipeline(QString dbID);
    QJsonObject ToJSON(QString path);
    QString PrintPipeline();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    QString VirtualPath();
    QList<QPair<QString,QString>> GetStagedFileList();
    QString GetDatabaseUUID() { return databaseUUID; }
    void SetDatabaseUUID(QString dbID) { databaseUUID = dbID; }

    /* JSON elements */
    QDateTime PipelineCreateDate;           /*!< date the pipeline was created */
    QString ClusterEngine;            /*!< [NiDB] compute cluster engine (sge, slurm) */
    QString ClusterQueue;           /*!< [NiDB] compute cluster queue */
    QString ClusterSubmitHost;      /*!< [NiDB] hostname of the sge/slurm submit node */
    QString ClusterUser;            /*!< [NiDB] compute cluster user */
    QString PipelineDescription;            /*!< longer description */
    QString PipelineDirectory;              /*!< [NiDB] directory where this pipeline will live if not using the default pipeline directory */
    QString PipelineDirectoryStructure;     /*!<  */
    QString PipelineName;           /*!< pipeline name (required) */
    QString PipelineNotes;                  /*!< freeform area for notes */
    QString PipelinePrimaryScript;
    QString PipelineResultScript;           /*!< path to a script to run to get a results at the end */
    QString PipelineSecondaryScript;
    QString SearchDependencyLevel;        /*!<  */
    QString SearchDependencyLinkType;     /*!<  */
    QString SearchGroup;                  /*!< [NiDB] group on which the pipeline will be run */
    QString SearchGroupType;              /*!< [NiDB] subject, study */
    QString SetupBidsDirectory;
    QString SetupDataCopyMethod;         /*!< cp, hardlink, softlink  */
    QString SetupDependencyDirectory;    /*!< dependency directory - root or subdir */
    QString SetupTempDirectory;          /*!< name of temp dir, if one is to be used */
    QString SetupWriteBids;
    QStringList PipelineCompleteFiles;      /*!< list of files that must exists to indicate the analysis was complete */
    QStringList SearchParentPipelines;    /*!< list of pipelines on which this pipeline depends */
    int ClusterMaxWallTime;         /*!< [NiDB] maximum allowed clock (wall) time the analysis is allowed to run (seconds) */
    int ClusterMemory;              /*!< [NiDB] memory requested */
    int ClusterNumberConcurrentAnalyses;   /*!< [NiDB] max number of concurrent analyses allowed to run */
    int ClusterNumberCores;         /*!< [NiDB] number of cores requested */
    int ClusterSubmitDelay;                /*!< [NiDB] time in hours after the study datetime to delay before running this analysis */
    int PipelineAnalysisLevel;                      /*!< 1 (subject), or 2 (group) */
    int PipelineVersion;                    /*!< pipeline version (required) */
    struct flag {
        bool SetupUseProfile;            /*!< whether to use the profile command to see CPU and memory usage history for each analysis */
        bool SetupUseTempDirectory;      /*!< whether to use a temp directory or not */
    } flags;

    QList<dataStep> dataSteps;

    /* lib variables */
    QStringList stagedFiles;        /*!< staged file list: list of files in their own original directories which will be copied in before the package is zipped up */
    QStringList files;              /*!< files as they will appear in the package, including virtual paths */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
    QString databaseUUID;
};

#endif // SQUIRRELPIPELINE_H
