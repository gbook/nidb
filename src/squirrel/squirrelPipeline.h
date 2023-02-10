/* ------------------------------------------------------------------------------
  Squirrel pipeline.h
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


#ifndef SQUIRRELPIPELINE_H
#define SQUIRRELPIPELINE_H
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
    QString associationType; /*!< study or subject (required) */
    QString behDir; /*!< if behFormat writes data to a sub-directory, this is the name of that sub-doirectory */
    QString behFormat; /*!< nobeh, behroot, behseries, behseriesdir */
    QString dataFormat; /*!< native, dicom, nifti3d, nift4d, analyze3d, analyze4d, bids */
    QString imageType; /*!< comma separated list of image types, often derived from the DICOM ImageType tag (0008:0008) */
    QString datalevel; /*!< nearestintime, samestudy */
    QString location; /*!< directory relative to {analysisroot}, where this data will be written */
    QString modality; /*!< modality of the data to search for */
    QString numBOLDreps; /*!< if seriesCriteria is 'usecriteria', then this is the number of bold reps to search for, ie '<=450' */
    QString numImagesCriteria; /*!< note sure */
    int order; /*!< order of this step */
    QString protocol; /*!< protocol name(s) to search for */
    QString seriesCriteria; /*!< criteria for downloading data from a study, if more than one series matches the protocol: all, first, last, largest, smallest, usecriteria */

    struct flag {
        bool enabled; /*!< whether this step is enabled */
        bool optional; /*!< if the step is optional */
        bool gzip; /*!< whether Nifti data should be zipped (.nii.gz) */
        bool preserveSeries; /*!< whether to preserve the series number, if writing series directories. Otherwise the series directories are generated sequentially starting at 1 */
        bool primaryProtocol; /*!< true if this is the primary protocol. this determines if this study will be used as the parent for child pipelines */
        bool usePhaseDir; /*!< whether to place data into a sub-directory based on the phase-encoding direction */
        bool useSeries; /*!< true to write each series to an individually numbered directory, otherwise write it to the directory specified in 'location' */
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
    squirrelPipeline();
    QJsonObject ToJSON(QString path);
    void PrintPipeline();

    /* pipeline information (required fields) */
    QString pipelineName; /*!< pipeline name (required) */
    QString description; /*!< longer description */
    QDateTime createDate; /*!< date the pipeline was created */
    int version; /*!< pipeline version (required) */
    QString level; /*!< 1 (subject), or 2 (group) */

    /* pipeline options */
    QStringList parentPipelines; /*!< list of pipelines on which this pipeline depends */
    QString completeFiles; /*!< list of files that must exists to indicate the analysis was complete */
    QString dataCopyMethod; /*!< cp, hardlink, softlink  */
    QString depDir; /*!< dependency directory */
    QString depLevel; /*!<  */
    QString depLinkType; /*!<  */
    QString dirStructure; /*!<  */
    QString directory; /*!< [NiDB] directory where this pipeline will live if not using the default pipeline directory */
    QString group; /*!< [NiDB] group on which the pipeline will be run */
    QString groupType; /*!< [NiDB] subject, study */
    QString notes; /*!< freeform area for notes */
    QString resultScript; /*!< path to a script to run to get a results at the end */
    QString tmpDir; /*!< name of temp dir, if one is to be used */
    struct flag {
        bool useProfile; /*!< whether to use the profile command to see CPU and memory usage history for each analysis */
        bool useTmpDir; /*!< whether to use a temp directory or not */
    } flags;

    /* cluster information */
    QString clusterType; /*!< [NiDB] compute cluster engine (sge, slurm) */
    QString clusterUser; /*!< [NiDB] compute cluster user */
    QString clusterQueue; /*!< [NiDB] compute cluster queue */
    QString clusterSubmitHost; /*!< [NiDB] hostname of the sge/slurm submit node */
    int numConcurrentAnalyses; /*!< [NiDB] max number of concurrent analyses allowed to run */
    int maxWallTime; /*!< [NiDB] maximum allowed clock (wall) time the analysis is allowed to run (seconds) */
    int submitDelay; /*!< [NiDB] time in hours after the study datetime to delay before running this analysis */

    /* data */
    QList<dataStep> dataSteps;

    /* scripts (required) */
    QString primaryScript;
    QString secondaryScript;

private:
    QString virtualPath; /*!< path within the squirrel package, no leading slash */

};

#endif // SQUIRRELPIPELINE_H
