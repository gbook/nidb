/* ------------------------------------------------------------------------------
  Squirrel squirrel.sql.h
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
#include <QString>

/* Notes about this file:
 *
    SQLite does not support multiple statements for each query. So instead,
    each table must have it's own statement. This isn't too bad because we only
    have 12 tables to create.

    Also, SQLite's datatype and table creation syntax is much simpler than
    regular SQL
 */

QString tableStagedFiles = QString("CREATE TABLE IF NOT EXISTS StagedFiles ("
    "StagedFileRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ObjectType TEXT,"
    "ObjectRowID INTEGER,"
    "FileSize INTEGER,"
    "StagedPath TEXT,"
    "FinalDirectory TEXT,"
    "UNIQUE(ObjectRowID, ObjectType, StagedPath) )");

QString tableAnalysis = QString("CREATE TABLE IF NOT EXISTS Analysis ("
    "AnalysisRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "StudyRowID INTEGER,"
    "PipelineRowID INTEGER,"
    "PipelineVersion INTEGER,"
    "ClusterStartDate TEXT,"
    "ClusterEndDate TEXT,"
    "StartDate TEXT,"
    "EndDate TEXT,"
    "SetupTime INTEGER,"
    "RunTime INTEGER,"
    "NumSeries INTEGER,"
    "Status TEXT,"
    "Successful INTEGER,"
    "Size INTEGER,"
    "FileCount INTEGER,"
    "Hostname TEXT,"
    "StatusMessage TEXT,"
    "VirtualPath TEXT,"
    "UNIQUE(StudyRowID, PipelineRowID, PipelineVersion))");

QString tableDataDictionary = QString("CREATE TABLE IF NOT EXISTS DataDictionary ("
    "DataDictionaryRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "FileCount INTEGER,"
    "Size INTEGER,"
    "VirtualPath TEXT)");

QString tableDataDictionaryItems = QString("CREATE TABLE IF NOT EXISTS DataDictionaryItems ("
    "DataDictionaryItemRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "DataDictionaryRowID INTEGER,"
    "VariableType TEXT,"
    "VariableName TEXT,"
    "VariableDescription TEXT,"
    "KeyValue TEXT,"
    "ExpectedTimepoints INTEGER,"
    "RangeLow REAL,"
    "RangeHigh REAL)");

QString tableDrug = QString("CREATE TABLE IF NOT EXISTS Drug ("
    "DrugRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "SubjectRowID INTEGER,"
    "DrugName TEXT,"
    "DateStart TEXT,"
    "DateEnd TEXT,"
    "DateRecordCreate TEXT,"
    "DateRecordEntry TEXT,"
    "DateRecordModify TEXT,"
    "DoseString TEXT,"
    "DoseAmount TEXT,"
    "DoseFrequency TEXT,"
    "AdministrationRoute TEXT,"
    "DrugClass TEXT,"
    "DoseKey TEXT,"
    "DoseUnit TEXT,"
    "FrequencyModifer TEXT,"
    "FrequencyValue REAL,"
    "FrequencyUnit TEXT,"
    "Description TEXT,"
    "Rater TEXT,"
    "Notes TEXT)");

QString tableExperiment = QString("CREATE TABLE IF NOT EXISTS Experiment ("
    "ExperimentRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ExperimentName TEXT UNIQUE,"
    "Size INTEGER DEFAULT 0,"
    "FileCount INTEGER DEFAULT 0,"
    "VirtualPath TEXT)");

QString tableGroupAnalysis = QString("CREATE TABLE IF NOT EXISTS GroupAnalysis ("
    "GroupAnalysisRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "GroupAnalysisName TEXT UNIQUE,"
    "Description TEXT,"
    "Datetime TEXT,"
    "FileCount INTEGER,"
    "Size INTEGER,"
    "VirtualPath TEXT)");

QString tableMeasure = QString("CREATE TABLE IF NOT EXISTS Measure ("
    "MeasureRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "SubjectRowID INTEGER,"
    "MeasureName TEXT,"
    "DateStart TEXT,"
    "DateEnd TEXT,"
    "InstrumentName TEXT,"
    "Rater TEXT,"
    "Notes TEXT,"
    "Value TEXT,"
    "Duration INTEGER,"
    "DateRecordCreate TEXT,"
    "DateRecordEntry TEXT,"
    "DateRecordModify TEXT,"
    "Description TEXT)");

QString tablePackage = QString("CREATE TABLE IF NOT EXISTS Package ("
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

QString tableParams = QString("CREATE TABLE IF NOT EXISTS Params ("
    "ParamRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "SeriesRowID INTEGER,"
    "ParamKey TEXT,"
    "ParamValue TEXT,"
    "UNIQUE(SeriesRowID, ParamKey))");

QString tablePipeline = QString("CREATE TABLE IF NOT EXISTS Pipeline ("
    "PipelineRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "PipelineName TEXT,"
    "Description TEXT,"
    "Datetime TEXT,"
    "Level INTEGER,"
    "PrimaryScript TEXT,"
    "SecondaryScript TEXT,"
    "Version INTEGER ,"
    "CompleteFiles TEXT,"
    "DataCopyMethod TEXT,"
    "DependencyDirectory TEXT,"
    "DependencyLevel TEXT,"
    "DependencyLinkType TEXT,"
    "DirStructure TEXT,"
    "Directory TEXT,"
    "GroupName TEXT,"
    "GroupType TEXT,"
    "Notes TEXT,"
    "ResultScript TEXT,"
    "TempDir TEXT,"
    "FlagUseProfile INTEGER,"
    "FlagUseTempDir INTEGER,"
    "ClusterType TEXT,"
    "ClusterUser TEXT,"
    "ClusterQueue TEXT,"
    "ClusterSubmitHost TEXT,"
    "NumConcurrentAnalysis INTEGER,"
    "MaxWallTime INTEGER,"
    "SubmitDelay INTEGER,"
    "VirtualPath TEXT,"
    "UNIQUE(PipelineName, Version))");

QString tablePipelineDataStep = QString("CREATE TABLE IF NOT EXISTS PipeplineDataStep ("
    "DataStepRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "PipelineRowID INTEGER,"
    "AssociationType TEXT,"
    "BehavioralDirectory TEXT,"
    "BehavioralFormat TEXT,"
    "DataFormat TEXT,"
    "ImageType TEXT,"
    "DataLevel TEXT,"
    "Location TEXT,"
    "Modality TEXT,"
    "NumBOLDReps TEXT,"
    "NumImagesCriteria TEXT,"
    "StepOrder INTEGER,"
    "Protocol TEXT,"
    "SeriesCriteria TEXT,"
    "FlagEnabled INTEGER,"
    "FlagOptional INTEGER,"
    "FlagGzip INTEGER,"
    "FlagPreserveSeriesNum INTEGER,"
    "FlagPrimaryProtocol INTEGER,"
    "FlagUsePhaseDir INTEGER,"
    "FlagUseSeries INTEGER)");

QString tableSeries = QString("CREATE TABLE IF NOT EXISTS Series ("
    "SeriesRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "StudyRowID INTEGER,"
    "SeriesNumber INTEGER,"
    "Datetime TEXT,"
    "SeriesUID TEXT,"
    "Description TEXT,"
    "Protocol TEXT,"
    "ExperimentRowID INTEGER,"
    "Size INTEGER DEFAULT 0,"
    "FileCount INTEGER DEFAULT 0,"
    "BehavioralSize INTEGER DEFAULT 0,"
    "BehavioralFileCount INTEGER DEFAULT 0,"
    "SequenceNumber INTEGER,"
    "VirtualPath TEXT,"
    "UNIQUE(StudyRowID, SeriesNumber))");

QString tableStudy = QString("CREATE TABLE IF NOT EXISTS Study ("
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
    "Timepoint INTEGER DEFAULT 0,"
    "Equipment TEXT,"
    "SequenceNumber INTEGER,"
    "VirtualPath TEXT,"
    "UNIQUE(SubjectRowID, StudyNumber))");

QString tableSubject = QString("CREATE TABLE IF NOT EXISTS Subject ("
    "SubjectRowID INTEGER PRIMARY KEY AUTOINCREMENT,"
    "ID TEXT NOT NULL UNIQUE,"
    "AltIDs TEXT,"
    "GUID TEXT,"
    "DateOfBirth TEXT,"
    "Sex TEXT,"
    "Gender TEXT,"
    "Ethnicity1 TEXT,"
    "Ethnicity2 TEXT,"
    "SequenceNumber INTEGER,"
    "VirtualPath TEXT)");
