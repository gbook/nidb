/* ------------------------------------------------------------------------------
  Squirrel bids.h
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


#ifndef BIDS_H
#define BIDS_H

#include "utils.h"
#include "squirrel.h"

class bids
{
public:
    bids();

    bool Read(QString dir, squirrel *sqrl);
    bool LoadToSquirrel(QString dir, squirrel *sqrl);

    bool LoadRootFiles(QStringList rootfiles, squirrel *sqrl);
    QStringList InheritableFiles(QStringList files);
    bool LoadSessionDir(QString sesdir, qint64 subjectRowID, int studyNum, QStringList inherited, QStringList subjectScans, squirrel *sqrl);
    QStringList OrderSessionsByAcqTime(QStringList sesdirs, QString sessionsFile, squirrel *sqrl);
    bool LoadScansFile(QString f, const QHash<QString, qint64> &seriesByPath, bool allowFallback, squirrel *sqrl);

    bool LoadParticipantsFile(QString f, squirrel *sqrl);
    QJsonObject ReadColumnSidecars(QStringList jsonFiles, squirrel *sqrl);
    void ApplyColumnMeta(squirrelObservation &obs, const QString &col, const QJsonObject &colMeta);
    void AddToPackageNotes(QString section, QString key, QJsonValue value, squirrel *sqrl);
    bool LoadTaskFile(QString f, squirrel *sqrl);
    bool LoadDatasetDescription(QString f, squirrel *sqrl);
    bool LoadSessionsFile(QString f, qint64 subjectRowID, squirrel *sqrl);
    bool LoadPhenotypeDir(QString phenotypeDir, squirrel *sqrl);

    /* derivatives -> pipelines + analyses */
    bool LoadDerivatives(QString derivativesDir, squirrel *sqrl);
    qint64 LoadDerivativePipeline(QString pdir, QString pipelineName, bool subjectsOnly, squirrel *sqrl, QStringList excludeDirs = QStringList());
    void ScanDerivativeDir(QString dir, int depth, QStringList &subjectDirs, QStringList &leftovers, squirrel *sqrl);
    bool ContainsSubjectDir(QString dir, int depth, squirrel *sqrl);
    qint64 ResolveDerivativeStudy(qint64 subjectRowID, QString sesLabel, squirrel *sqrl);
    qint64 StageAnalysisEntries(QStringList entries, QString pipelineName, qint64 pipelineRowID, qint64 studyRowID, squirrel *sqrl);

    /* top-level directories without a squirrel object of their own */
    bool LoadStimuliDir(QString stimuliDir, squirrel *sqrl);
    bool LoadCodeDir(QString codeDir, squirrel *sqrl);
    void StoreDatasetFilesInNotes(QStringList rootfiles, squirrel *sqrl);
    bool StageFiles(ObjectType object, qint64 rowid, QStringList files, squirrel *sqrl);
    void ReportMissingContent(squirrel *sqrl);

    /* BIDS filename/entity parsing helpers */
    void ParseBidsFilename(const QString &filename, QHash<QString, QString> &entities, QString &suffix, QString &ext);
    QString ModalityForDatatype(const QString &datatype);
    bool IsPrimaryDataFile(const QString &path);
    bool EntitiesSubset(const QHash<QString, QString> &sub, const QHash<QString, QString> &super, const QStringList &ignore = QStringList());
    QHash<QString, QString> ReadSidecarParams(QStringList jsonFiles);
    qint64 AddSeriesFromBidsFile(QString primaryFile, QString datatype, qint64 studyRowID, QStringList companionFiles, QHash<QString, QString> params, squirrel *sqrl);

private:
    QStringList rootInheritable;     /*!< dataset-root sidecars (eg T1w.json, dwi.bval) applied by the BIDS inheritance principle */
    QString rootDir;
    QString datasetType;             /*!< DatasetType from dataset_description.json (raw, derivative) */
    QString generatedByName;         /*!< GeneratedBy[0].Name from dataset_description.json */
    bool derivativeRootMode = false; /*!< importing a dataset whose root is a derivative */                 /*!< the BIDS dataset root directory */
    QSet<QString> usedRootFiles;
    QSet<QString> missingContent;    /*!< dataset-relative paths of files whose content is not present (DataLad/git-annex) */
    QHash<QPair<qint64, qint64>, qint64> derivAnalyses; /*!< (pipeline, study) -> analysis row id, for derivative imports */     /*!< root files that were applied to a series or staged in an experiment */
};

#endif // BIDS_H
