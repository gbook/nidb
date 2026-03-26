/* ------------------------------------------------------------------------------
  Squirrel squirrel.sql.h
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
#include <QString>

/* Notes about this file:
 *
    SQLite does not support multiple statements for each query. So instead,
    each table must have it's own statement. This isn't too bad because we only
    have 12 tables to create.

    Also, SQLite's datatype and table creation syntax is much simpler than
    regular SQL
 */

const QString tableStagedFiles = QString("CREATE TABLE IF NOT EXISTS StagedFiles ("
    "StagedFileRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ObjectType TEXT,"
    "ObjectRowID INTEGER,"
    "FileSize INTEGER,"
    "StagedPath TEXT,"
    "FinalDirectory TEXT,"
    "UNIQUE(ObjectRowID, ObjectType, StagedPath) )");

const QString tableAnalysis = QString("CREATE TABLE IF NOT EXISTS Analysis ("
    "AnalysisRowID INTEGER PRIMARY KEY AUTOINCREMENT, "
    "StudyRowID INTEGER, "
    "AnalysisName TEXT, "
    "ClusterEndDate TEXT, "
    "ClusterStartDate TEXT, "
    "EndDate TEXT, "
    "FileCount INTEGER, "
    "Hostname TEXT, "
    "NumSeries INTEGER, "
    "PipelineRowID INTEGER, "
    "PipelineVersion INTEGER, "
    "RunTime INTEGER, "
    "SetupTime INTEGER, "
    "Size INTEGER, "
    "StartDate TEXT, "
    "Status TEXT, "
    "StatusMessage TEXT, "
    "Successful INTEGER, "
    "VirtualPath TEXT, "
    "UNIQUE(StudyRowID, PipelineRowID, PipelineVersion))");

const QString tableDataDictionary = QString("CREATE TABLE IF NOT EXISTS DataDictionary ("
    "DataDictionaryRowID INTEGER PRIMARY KEY AUTOINCREMENT, "
    "DataDictionaryName TEXT, "
    "FileCount INTEGER, "
    "Size INTEGER, "
    "VirtualPath TEXT)");

const QString tableDataDictionaryItem = QString("CREATE TABLE IF NOT EXISTS DataDictionaryItem ("
    "DataDictionaryItemRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "DataDictionaryRowID INTEGER,"
    "VariableType TEXT,"
    "VariableName TEXT,"
    "VariableDescription TEXT,"
    "KeyValueMapping TEXT,"
    "ExpectedTimepoints INTEGER,"
    "RangeLow REAL,"
    "RangeHigh REAL)");

const QString tableExperiment = QString("CREATE TABLE IF NOT EXISTS Experiment ("
    "ExperimentRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ExperimentName TEXT UNIQUE,"
    "Size INTEGER DEFAULT 0,"
    "FileCount INTEGER DEFAULT 0,"
    "VirtualPath TEXT)");

const QString tableGroupAnalysis = QString("CREATE TABLE IF NOT EXISTS GroupAnalysis ("
    "GroupAnalysisRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "GroupAnalysisName TEXT UNIQUE,"
    "Description TEXT,"
    "Datetime TEXT,"
    "Notes TEXT,"
    "FileCount INTEGER,"
    "Size INTEGER,"
    "VirtualPath TEXT)");

const QString tableIntervention = QString("CREATE TABLE IF NOT EXISTS Intervention ("
    "InterventionRowID INTEGER PRIMARY KEY AUTOINCREMENT, "
    "SubjectRowID INTEGER, "
    "AdministrationRoute TEXT, "
    "DateEnd TEXT, "
    "DateRecordCreate TEXT, "
    "DateRecordEntry TEXT, "
    "DateRecordModify TEXT, "
    "DateStart TEXT, "
    "Description TEXT, "
    "DoseAmount REAL, "
    "DoseFrequency TEXT, "
    "DoseKey TEXT, "
    "DoseString TEXT, "
    "DoseUnit TEXT, "
    "FrequencyModifier TEXT, "
    "FrequencyUnit TEXT, "
    "FrequencyValue REAL, "
    "InterventionClass TEXT, "
    "InterventionName TEXT, "
    "Notes TEXT, "
    "Rater TEXT, "
    "UNIQUE(SubjectRowID, InterventionName, DateStart))");


const QString tableObservation = QString("CREATE TABLE IF NOT EXISTS Observation ("
    "ObservationRowID INTEGER PRIMARY KEY AUTOINCREMENT, "
    "SubjectRowID INTEGER, "
    "DateEnd TEXT, "
    "DateRecordCreate TEXT, "
    "DateRecordEntry TEXT, "
    "DateRecordModify TEXT, "
    "DateStart TEXT, "
    "Description TEXT, "
    "Duration INTEGER, "
    "InstrumentName TEXT, "
    "Notes TEXT, "
    "ObservationName TEXT, "
    "Rater TEXT, "
    "Value TEXT, "
    "UNIQUE(SubjectRowID, ObservationName, DateStart))");

const QString tablePackage = QString("CREATE TABLE IF NOT EXISTS Package ("
    "PackageRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "Name TEXT NOT NULL UNIQUE,"
    "Description TEXT,"
    "Datetime TEXT,"
    "SubjectDirFormat TEXT DEFAULT 'orig',"
    "StudyDirFormat TEXT DEFAULT 'orig',"
    "SeriesDirFormat TEXT DEFAULT 'orig',"
    "PackageDataFormat TEXT DEFAULT 'orig',"
    "License TEXT,"
    "Readme TEXT,"
    "Changes TEXT,"
    "Notes TEXT)");

const QString tableParams = QString("CREATE TABLE IF NOT EXISTS Params ("
    "ParamRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "SeriesRowID INTEGER,"
    "ParamKey TEXT,"
    "ParamValue TEXT,"
    "UNIQUE(SeriesRowID, ParamKey))");

const QString tablePipeline = QString("CREATE TABLE IF NOT EXISTS Pipeline ("
    "PipelineRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ClusterEngine TEXT,"
    "ClusterMaxWallTime INTEGER,"
    "ClusterMemory INTEGER,"
    "ClusterNumberConcurrentAnalyses INTEGER,"
    "ClusterNumberCores INTEGER,"
    "ClusterQueue TEXT,"
    "ClusterSubmitDelay INTEGER,"
    "ClusterSubmitHost TEXT,"
    "ClusterUser TEXT,"
    "FlagSetupUseProfile INTEGER,"
    "FlagSetupUseTempDirectory INTEGER,"
    "PipelineAnalysisLevel INTEGER,"
    "PipelineCompleteFiles TEXT,"
    "PipelineCreateDate TEXT,"
    "PipelineDescription TEXT,"
    "PipelineDirectory TEXT,"
    "PipelineDirectoryStructure TEXT,"
    "PipelineName TEXT,"
    "PipelineNotes TEXT,"
    "PipelinePrimaryScript TEXT,"
    "PipelineResultScript TEXT,"
    "PipelineSecondaryScript TEXT,"
    "PipelineVersion INTEGER ,"
    "SearchDependencyLevel TEXT,"
    "SearchDependencyLinkType TEXT,"
    "SearchGroup TEXT,"
    "SearchGroupType TEXT,"
    "SearchParentPipelines TEXT,"
    "SetupDataCopyMethod TEXT,"
    "SetupDependencyDirectory TEXT,"
    "SetupTempDirectory TEXT,"
    "VirtualPath TEXT,"
    "UNIQUE(PipelineName, PipelineVersion))");

const QString tablePipelineDataStep = QString("CREATE TABLE IF NOT EXISTS PipelineDataStep ("
    "DataStepRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "PipelineRowID INTEGER,"
    "ExportBehavioralDirectoryFormat TEXT,"
    "ExportBehavioralDirectoryName TEXT,"
    "ExportDataFormat TEXT,"
    "ExportSubDirectoryName TEXT,"
    "NumImagesCriteria TEXT,"
    "SearchAssociationType TEXT,"
    "SearchDataLevel TEXT,"
    "SearchImageType TEXT,"
    "SearchModality TEXT,"
    "SearchNumberBOLDreps TEXT,"
    "SearchProtocol TEXT,"
    "SearchSeriesCriteria TEXT,"
    "StepNumber INTEGER,"
    "FlagIsEnabled INTEGER,"
    "FlagIsOptional INTEGER,"
    "FlagExportGzip INTEGER,"
    "FlagExportPreserveSeriesNumber INTEGER,"
    "FlagIsPrimaryProtocol INTEGER,"
    "FlagExportWritePhaseDirectory INTEGER,"
    "FlagExportWriteSeriesDirectory INTEGER)");

const QString tableSeries = QString("CREATE TABLE IF NOT EXISTS Series ("
    "SeriesRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "StudyRowID INTEGER,"
    "SeriesNumber INTEGER,"
    "Datetime TEXT,"
    "SeriesUID TEXT,"
    "Description TEXT,"
    "Protocol TEXT,"
    "BidsEntity TEXT,"
    "BidsSuffix TEXT,"
    "BidsTask TEXT,"
    "BidsRun TEXT,"
    "BidsExtension TEXT,"
    "BidsPhaseEncodingDirection TEXT,"
    "Run INTEGER DEFAULT 1,"
    "ExperimentRowID INTEGER,"
    "Size INTEGER DEFAULT 0,"
    "Files TEXT,"
    "FileCount INTEGER DEFAULT 0,"
    "BehavioralSize INTEGER DEFAULT 0,"
    "BehavioralFileCount INTEGER DEFAULT 0,"
    "SequenceNumber INTEGER,"
    "VirtualPath TEXT,"
    "UNIQUE(StudyRowID, SeriesNumber))");

const QString tableStudy = QString("CREATE TABLE IF NOT EXISTS Study ("
    "StudyRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "SubjectRowID INTEGER,"
    "StudyNumber INTEGER ,"
    "Datetime TEXT,"
    "Age REAL DEFAULT 0.0,"
    "Height REAL DEFAULT 0.0,"
    "Weight REAL DEFAULT 0.0,"
    "Modality TEXT,"
    "Description TEXT,"
    "StudyUID TEXT,"
    "VisitType TEXT,"
    "DayNumber INTEGER DEFAULT 0,"
    "TimePoint INTEGER DEFAULT 0,"
    "Equipment TEXT,"
    "Notes TEXT,"
    "SequenceNumber INTEGER,"
    "VirtualPath TEXT,"
    "UNIQUE(SubjectRowID, StudyNumber))");

const QString tableSubject = QString("CREATE TABLE IF NOT EXISTS Subject ("
    "SubjectRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ID TEXT NOT NULL UNIQUE,"
    "AltIDs TEXT,"
    "GUID TEXT,"
    "DateOfBirth TEXT,"
    "Sex TEXT,"
    "Gender TEXT,"
    "EnrollmentGroup TEXT,"
    "EnrollmentStatus TEXT,"
    "Ethnicity1 TEXT,"
    "Ethnicity2 TEXT,"
    "Notes TEXT,"
    "SequenceNumber INTEGER,"
    "VirtualPath TEXT)");
