/* ------------------------------------------------------------------------------
  Squirrel pipeline.h
  Copyright (C) 2004 - 2022
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


#ifndef PIPELINE_H
#define PIPELINE_H
#include <QString>
#include <QDateTime>

/**
 * @brief The dataStep struct
 */
struct dataStep {
	QString associationType; /*!< study or subject (required) */
	QString behDir; /*!< if behFormat writes data to a sub-directory, this is the name of that sub-doirectory */
	QString behFormat; /*!< nobeh, behroot, behseries, behseriesdir */
	QString dataFormat; /*!< native, dicom, nifti3d, nift4d, analyze3d, analyze4d, bids */
	bool enabled; /*!< whether this step is enabled */
	bool gzip; /*!< whether Nifti data should be zipped (.nii.gz) */
	QString imageType; /*!< comma separated list of image types, often derived from the DICOM ImageType tag (0008:0008) */
	QString datalevel; /*!< nearestintime, samestudy */
	QString location; /*!< directory relative to {analysisroot}, where this data will be written */
	QString modality; /*!< modality of the data to search for */
	QString numBOLDreps; /*!< if seriesCriteria is 'usecriteria', then this is the number of bold reps to search for, ie '<=450' */
	QString numImagesCriteria; /*!< note sure */
	bool optional; /*!< if the step is optional */
	int order; /*!< order of this step */
	bool preserveSeries; /*!< whether to preserve the series number, if writing series directories. Otherwise the series directories are generated sequentially starting at 1 */
	bool primaryProtocol; /*!< true if this is the primary protocol. this determines if this study will be used as the parent for child pipelines */
	QString protocol; /*!< protocol name(s) to search for */
	QString seriesCriteria; /*!< criteria for downloading data from a study, if more than one series matches the protocol: all, first, last, largest, smallest, usecriteria */
	bool usePhaseDir; /*!< whether to place data into a sub-directory based on the phase-encoding direction */
	bool useSeries; /*!< true to write each series to an individually numbered directory, otherwise write it to the directory specified in 'location' */
};


/**
 * @brief The pipelineStep struct
 */
struct pipelineStep {
	QString command; /*!< the bash command */
	QString description; /*!< description of the command (#comment) */
	bool enabled; /*!< if the step is enabled */
	bool logged; /*!< if the step should be logged. use {NOLOG} in the description/comment to prevent logging */
	int order; /*!< the order of the step */
	QString workingDir; /*!< unused */
};


/**
 * @brief The pipeline class
 *
 * pipelines contain 3 sections: pipeline info, dataspec, steps (the script)
 */
class pipeline
{
public:
	pipeline();

	QString pipelineName; /*!< pipeline name (required) */
	QString clusterType; /*!< compute cluster engine (sge, slurm) */
	QString completeFiles; /*!< list of files that must exists to indicate the analysis was complete */
	QDateTime createDate; /*!< date the pipeline was created */
	QString dataCopyMethod; /*!<  */
	QString depDir; /*!<  */
	QString depLevel; /*!<  */
	QString depLinkType; /*!<  */
	QString description; /*!< longer description */
	QString dirStructure; /*!<  */
	QString directory; /*!<  */
	QString group; /*!< NiDB group on which the pipeline will be run */
	QString groupType; /*!< subject, study */
	QString level; /*!< 1 (subject), or 2 (group) */
	QString maxWallTime; /*!< maximum allowed clock (wall) time the analysis is allowed to run */
	QString notes; /*!< freeform area for notes */
	int numConcurrentAnalyses; /*!< max number of concurrent analyses allowed to run */
	QString resultScript; /*!< path to a script to run to get a results at the end */
	int submitDelay; /*!< time in hours after the study datetime to delay before running this analysis */
	QString submitHost; /*!< hostname of the sge/slurm submit node */
	QString tmpDir; /*!< name of temp dir, if one is to be used */
	bool useProfile; /*!< whether to use the profile command to see CPU and memory usage history for each analysis */
	bool useTmpDir; /*!< whether to use a temp directory or not */
	int version; /*!< pipeline version (required) */

	QList<dataStep> data;
	QList<pipelineStep> primaryScript;
	QList<pipelineStep> secondaryScript;

};

#endif // PIPELINE_H
