/* ------------------------------------------------------------------------------
  Squirrel modify.cpp
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

#include "modify.h"
#include "utils.h"
#include <algorithm>
#include <iostream>
#include <vector>
#include <iomanip>
#include <QRandomGenerator>
#include <QSet>
#include <QUrl>

modify::modify() {
}


/* ---------------------------------------------------------------------------- */
/* ----- DoModify ------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Dispatch a modify operation (add, remove, update, splitbymodality, removephi, renumber) on a squirrel package
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::DoModify(QString packagePath, const modification &mod, QString &m) {

    if (mod.operation == "add")
        return AddObject(packagePath, mod, m);
    else if (mod.operation == "remove")
        return RemoveObject(packagePath, mod, m);
    else if (mod.operation == "update")
        return UpdateObject(packagePath, mod, m);
    else if (mod.operation == "splitbymodality")
        return SplitByModality(packagePath, mod, m);
    else if (mod.operation == "removephi")
        return RemovePHI(packagePath, mod, m);
    else if (mod.operation == "renumber")
        return RenumberSubjects(packagePath, mod, m);
    else if (mod.operation == "embeddb")
        return EmbedDatabase(packagePath, mod, m);
    else {
        m = "Invalid operation [" + mod.operation + "] specified";
        return false;
    }
}


/* ---------------------------------------------------------------------------- */
/* ----- CollectFiles --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Resolve mod.dataPath to a list of absolute file paths.
 *
 * mod.dataPath may be:
 *   - a single file              → returns that file
 *   - a directory                → returns all files in the directory (recursive if mod.recursive)
 *   - a glob pattern (/p/ *.dcm)  → returns matching files in the parent directory
 *
 * @param mod the modification struct
 * @return list of absolute file paths (may be empty if dataPath is empty or matches nothing)
 */
static QStringList CollectFiles(const modification &mod) {
    QStringList files;
    if (mod.dataPath.isEmpty())
        return files;

    QFileInfo fi(mod.dataPath);
    if (fi.isFile()) {
        files << fi.absoluteFilePath();
    } else if (fi.isDir()) {
        files = utils::FindAllFiles(mod.dataPath, "*", mod.recursive);
    } else {
        /* treat as a glob: parent dir + filename pattern */
        QFileInfo dirInfo(fi.absolutePath());
        if (dirInfo.isDir())
            files = utils::FindAllFiles(fi.absolutePath(), fi.fileName(), mod.recursive);
    }
    return files;
}


/* ---------------------------------------------------------------------------- */
/* ----- objectData ----------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Object meta-data given as --objectdata 'key=value&key=value', or as one row of a CSV/TSV file
 *
 * Keys are matched case-insensitively, and a variable can be accepted under more
 * than one name (for example SeriesDatetime or Datetime). Each Set() copies a
 * value into the object only if that key was given, so the same code fills in a
 * new object (add) or changes an existing one (update). An empty value clears the
 * variable. Check() reports keys that were never used and values that could not
 * be converted, so typos are reported instead of silently ignored.
 */
class objectData {
public:
    /* parse a URL-style string. Values may contain '=', and may be percent-encoded (%26 for '&') */
    bool Parse(QString s, QString &m) {
        foreach (QString pair, s.split("&", Qt::SkipEmptyParts)) {
            int i = pair.indexOf("=");
            if (i < 1) {
                m = QString("Malformed --objectdata [%1]. Expected key=value pairs separated by &").arg(pair);
                return false;
            }
            Insert(QUrl::fromPercentEncoding(pair.left(i).toUtf8()), QUrl::fromPercentEncoding(pair.mid(i + 1).toUtf8()));
        }
        return true;
    }

    void Insert(QString key, QString value) {
        key = key.trimmed();
        vars[key.toLower()] = value.trimmed();
        names[key.toLower()] = key;
    }

    bool Has(QStringList keys) const {
        foreach (QString k, keys)
            if (vars.contains(k.toLower()))
                return true;
        return false;
    }

    /* the value of the first of the keys that was given, or def. Marks the key as used */
    QString String(QStringList keys, QString def = "") {
        foreach (QString k, keys) {
            if (vars.contains(k.toLower())) {
                used.insert(k.toLower());
                return vars.value(k.toLower());
            }
        }
        return def;
    }

    void Set(QString &v, QStringList keys) { if (Has(keys)) v = String(keys); }

    void Set(QStringList &v, QStringList keys) {
        if (!Has(keys)) return;
        v.clear();
        foreach (QString s, String(keys).split(",", Qt::SkipEmptyParts))
            v.append(s.trimmed());
    }

    void Set(int &v, QStringList keys) {
        if (!Has(keys)) return;
        QString s = String(keys);
        bool ok = true;
        int x = s.isEmpty() ? 0 : s.toInt(&ok);
        if (ok) v = x; else Bad(keys, s, "a whole number");
    }

    void Set(qint64 &v, QStringList keys) {
        if (!Has(keys)) return;
        QString s = String(keys);
        bool ok = true;
        qint64 x = s.isEmpty() ? 0 : s.toLongLong(&ok);
        if (ok) v = x; else Bad(keys, s, "a whole number");
    }

    void Set(double &v, QStringList keys) {
        if (!Has(keys)) return;
        QString s = String(keys);
        bool ok = true;
        double x = s.isEmpty() ? 0.0 : s.toDouble(&ok);
        if (ok) v = x; else Bad(keys, s, "a number");
    }

    void Set(bool &v, QStringList keys) {
        if (!Has(keys)) return;
        QString s = String(keys).toLower();
        if ((s == "true") || (s == "1") || (s == "yes")) v = true;
        else if ((s == "false") || (s == "0") || (s == "no") || (s == "")) v = false;
        else Bad(keys, s, "true or false");
    }

    void Set(QDateTime &v, QStringList keys) {
        if (!Has(keys)) return;
        QString s = String(keys);
        QDateTime x = s.isEmpty() ? QDateTime() : utils::StringToDatetime(s);
        if (s.isEmpty() || x.isValid()) v = x; else Bad(keys, s, "a datetime, YYYY-MM-DD HH:MI:SS");
    }

    void Set(QDate &v, QStringList keys) {
        if (!Has(keys)) return;
        QString s = String(keys);
        QDate x = s.isEmpty() ? QDate() : utils::StringToDatetime(s).date();
        if (s.isEmpty() || x.isValid()) v = x; else Bad(keys, s, "a date, YYYY-MM-DD");
    }

    /* true if any keys have not been used yet */
    bool HasUnused() const {
        foreach (QString k, vars.keys())
            if (!used.contains(k))
                return true;
        return false;
    }

    /* report unknown keys and bad values */
    bool Check(QString objectName, QString &m) const {
        QStringList unused;
        foreach (QString k, vars.keys())
            if (!used.contains(k))
                unused << names.value(k);
        unused.sort();
        QStringList problems;
        if (!unused.isEmpty())
            problems << QString("Unknown variable(s) [%1] for object [%2]. To see the variables, run: squirrel modify --variablelist %2").arg(unused.join(", ")).arg(objectName);
        if (!bad.isEmpty())
            problems << QString("Invalid value(s): %1").arg(bad.join("; "));
        if (problems.isEmpty())
            return true;
        m = problems.join("\n");
        return false;
    }

private:
    QHash<QString, QString> vars;   /* lowercase key -> value */
    QHash<QString, QString> names;  /* lowercase key -> key as given, for messages */
    QSet<QString> used;
    QStringList bad;

    void Bad(QStringList keys, QString value, QString expected) {
        bad << QString("%1=%2 (expected %3)").arg(keys.first()).arg(value).arg(expected);
    }
};


/* ---------------------------------------------------------------------------- */
/* ----- Apply* --------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/* Copy any given --objectdata variables into an object. The keys are the names
   used in squirrel.json; the extra names are older or alternate spellings. */

static void ApplyPackage(objectData &d, squirrel *s) {
    d.Set(s->Changes, {"Changes"});
    d.Set(s->Description, {"Description"});
    d.Set(s->License, {"License"});
    d.Set(s->Notes, {"Notes"});
    d.Set(s->PackageName, {"PackageName"});
    d.Set(s->Readme, {"Readme"});
}

static void ApplySubject(objectData &d, squirrelSubject &s) {
    d.Set(s.ID, {"SubjectID", "ID"});
    d.Set(s.AlternateIDs, {"AlternateIDs"});
    d.Set(s.DateOfBirth, {"DateOfBirth"});
    d.Set(s.EnrollmentGroup, {"EnrollmentGroup"});
    d.Set(s.EnrollmentStatus, {"EnrollmentStatus"});
    d.Set(s.Ethnicity1, {"Ethnicity1"});
    d.Set(s.Ethnicity2, {"Ethnicity2"});
    d.Set(s.GUID, {"GUID"});
    d.Set(s.Gender, {"Gender"});
    d.Set(s.Notes, {"Notes"});
    d.Set(s.Sex, {"Sex"});
}

static void ApplyStudy(objectData &d, squirrelStudy &s) {
    d.Set(s.AgeAtStudy, {"AgeAtStudy"});
    d.Set(s.DateTime, {"StudyDatetime", "Datetime"});
    d.Set(s.DayNumber, {"DayNumber"});
    d.Set(s.Description, {"Description"});
    d.Set(s.Equipment, {"Equipment"});
    d.Set(s.Height, {"Height"});
    d.Set(s.Modality, {"Modality"});
    d.Set(s.Notes, {"Notes"});
    d.Set(s.StudyNumber, {"StudyNumber"});
    d.Set(s.StudyUID, {"StudyUID"});
    d.Set(s.TimePoint, {"TimePoint"});
    d.Set(s.VisitType, {"VisitType"});
    d.Set(s.Weight, {"Weight"});
}

static void ApplySeries(objectData &d, squirrelSeries &s) {
    d.Set(s.BidsEntity, {"BidsEntity"});
    d.Set(s.BidsPhaseEncodingDirection, {"BidsPhaseEncodingDirection"});
    d.Set(s.BidsRun, {"BidsRun"});
    d.Set(s.BidsSuffix, {"BidsSuffix"});
    d.Set(s.BidsTask, {"BidsTask"});
    d.Set(s.Description, {"Description"});
    d.Set(s.Protocol, {"Protocol"});
    d.Set(s.Run, {"Run"});
    d.Set(s.DateTime, {"SeriesDatetime", "Datetime"});
    d.Set(s.SeriesNumber, {"SeriesNumber"});
    d.Set(s.SeriesUID, {"SeriesUID"});
}

static void ApplyAnalysis(objectData &d, squirrelAnalysis &a) {
    d.Set(a.AnalysisName, {"AnalysisName"});
    d.Set(a.DateClusterEnd, {"DateClusterEnd"});
    d.Set(a.DateClusterStart, {"DateClusterStart"});
    d.Set(a.DateEnd, {"DateEnd"});
    d.Set(a.DateStart, {"DateStart"});
    d.Set(a.Hostname, {"Hostname"});
    d.Set(a.PipelineName, {"PipelineName"});
    d.Set(a.PipelineVersion, {"PipelineVersion"});
    d.Set(a.RunTime, {"RunTime"});
    d.Set(a.SeriesCount, {"SeriesCount"});
    d.Set(a.SetupTime, {"SetupTime"});
    d.Set(a.Status, {"Status"});
    d.Set(a.StatusMessage, {"StatusMessage"});
    d.Set(a.Successful, {"Successful"});
}

static void ApplyObservation(objectData &d, squirrelObservation &o) {
    d.Set(o.DateEnd, {"DateEnd"});
    d.Set(o.DateRecordCreate, {"DateRecordCreate"});
    d.Set(o.DateRecordEntry, {"DateRecordEntry"});
    d.Set(o.DateRecordModify, {"DateRecordModify"});
    d.Set(o.DateStart, {"DateStart"});
    d.Set(o.Description, {"Description"});
    d.Set(o.Duration, {"Duration"});
    d.Set(o.InstrumentName, {"InstrumentName"});
    d.Set(o.ObservationName, {"ObservationName"});
    d.Set(o.ObservationType, {"ObservationType"});
    d.Set(o.Notes, {"Notes"});
    d.Set(o.Rater, {"Rater"});
    d.Set(o.Value, {"Value"});
}

static void ApplyIntervention(objectData &d, squirrelIntervention &i) {
    d.Set(i.AdministrationRoute, {"AdministrationRoute"});
    d.Set(i.DateEnd, {"DateInterventionEnd", "DateEnd"});
    d.Set(i.DateRecordCreate, {"DateRecordCreate"});
    d.Set(i.DateRecordEntry, {"DateRecordEntry"});
    d.Set(i.DateRecordModify, {"DateRecordModify"});
    d.Set(i.DateStart, {"DateInterventionStart", "DateStart"});
    d.Set(i.Description, {"Description"});
    d.Set(i.DoseAmount, {"DoseAmount"});
    d.Set(i.DoseFrequency, {"DoseFrequency"});
    d.Set(i.DoseKey, {"DoseKey"});
    d.Set(i.DoseString, {"DoseString"});
    d.Set(i.DoseUnit, {"DoseUnit"});
    d.Set(i.InterventionClass, {"InterventionClass"});
    d.Set(i.InterventionName, {"InterventionName"});
    d.Set(i.Notes, {"Notes"});
    d.Set(i.Rater, {"Rater"});
}

static void ApplyExperiment(objectData &d, squirrelExperiment &e) {
    d.Set(e.ExperimentName, {"ExperimentName"});
}

static void ApplyPipeline(objectData &d, squirrelPipeline &p) {
    d.Set(p.ClusterEngine, {"ClusterEngine"});
    d.Set(p.ClusterMaxWallTime, {"ClusterMaxWallTime"});
    d.Set(p.ClusterMemory, {"ClusterMemory"});
    d.Set(p.ClusterNumberConcurrentAnalyses, {"ClusterNumberConcurrentAnalyses"});
    d.Set(p.ClusterNumberCores, {"ClusterNumberCores"});
    d.Set(p.ClusterQueue, {"ClusterQueue"});
    d.Set(p.ClusterSubmitDelay, {"ClusterSubmitDelay"});
    d.Set(p.ClusterSubmitHost, {"ClusterSubmitHost"});
    d.Set(p.ClusterUser, {"ClusterUser"});
    d.Set(p.flags.SetupUseProfile, {"FlagSetupUseProfile", "SetupUseProfile"});
    d.Set(p.flags.SetupUseTempDirectory, {"FlagSetupUseTempDirectory", "SetupUseTempDirectory"});
    d.Set(p.PipelineAnalysisLevel, {"PipelineAnalysisLevel"});
    d.Set(p.PipelineCompleteFiles, {"PipelineCompleteFiles"});
    d.Set(p.PipelineCreateDate, {"PipelineCreateDate"});
    d.Set(p.PipelineDescription, {"PipelineDescription"});
    d.Set(p.PipelineDirectory, {"PipelineDirectory"});
    d.Set(p.PipelineDirectoryStructure, {"PipelineDirectoryStructure"});
    d.Set(p.PipelineName, {"PipelineName"});
    d.Set(p.PipelineNotes, {"PipelineNotes"});
    d.Set(p.PipelinePrimaryScript, {"PipelinePrimaryScript"});
    d.Set(p.PipelineResultScript, {"PipelineResultScript"});
    d.Set(p.PipelineSecondaryScript, {"PipelineSecondaryScript"});
    d.Set(p.PipelineVersion, {"PipelineVersion"});
    d.Set(p.SearchDependencyLevel, {"SearchDependencyLevel"});
    d.Set(p.SearchDependencyLinkType, {"SearchDependencyLinkType"});
    d.Set(p.SearchGroup, {"SearchGroup"});
    d.Set(p.SearchGroupType, {"SearchGroupType"});
    d.Set(p.SearchParentPipelines, {"SearchParentPipelines"});
    d.Set(p.SetupDataCopyMethod, {"SetupDataCopyMethod"});
    d.Set(p.SetupDependencyDirectory, {"SetupDependencyDirectory"});
    d.Set(p.SetupTempDirectory, {"SetupTempDirectory"});
}

static void ApplyGroupAnalysis(objectData &d, squirrelGroupAnalysis &g) {
    d.Set(g.GroupAnalysisName, {"GroupAnalysisName"});
    d.Set(g.DateTime, {"Datetime", "DateTime"});
    d.Set(g.Description, {"Description"});
    d.Set(g.Notes, {"Notes"});
}

static void ApplyDataDictionary(objectData &d, squirrelDataDictionary &dd) {
    d.Set(dd.DataDictionaryName, {"DataDictionaryName"});
}

static void ApplyDataDictionaryItem(objectData &d, dataDictionaryItem &item) {
    d.Set(item.VariableType, {"VariableType"});
    d.Set(item.VariableName, {"VariableName"});
    d.Set(item.VariableDescription, {"VariableDescription"});
    d.Set(item.KeyValueMapping, {"KeyValueMapping"});
    d.Set(item.ExpectedTimepoints, {"ExpectedTimepoints"});
    d.Set(item.RangeLow, {"RangeLow"});
    d.Set(item.RangeHigh, {"RangeHigh"});
}

static void ApplyDataStep(objectData &d, dataStep &ds) {
    d.Set(ds.StepNumber, {"StepNumber"});
    d.Set(ds.flags.IsEnabled, {"FlagIsEnabled"});
    d.Set(ds.flags.IsOptional, {"FlagIsOptional"});
    d.Set(ds.flags.IsPrimaryProtocol, {"FlagIsPrimaryProtocol"});
    d.Set(ds.SearchAssociationType, {"SearchAssociationType"});
    d.Set(ds.SearchDataLevel, {"SearchDataLevel"});
    d.Set(ds.SearchImageType, {"SearchImageType"});
    d.Set(ds.SearchModality, {"SearchModality"});
    d.Set(ds.SearchNumberBOLDreps, {"SearchNumberBOLDreps"});
    d.Set(ds.SearchProtocol, {"SearchProtocol"});
    d.Set(ds.SearchSeriesCriteria, {"SearchSeriesCriteria"});
    d.Set(ds.ExportBehavioralDirectoryFormat, {"ExportBehavioralDirectoryFormat"});
    d.Set(ds.ExportBehavioralDirectoryName, {"ExportBehavioralDirectoryName"});
    d.Set(ds.ExportDataFormat, {"ExportDataFormat"});
    d.Set(ds.ExportSubDirectoryName, {"ExportSubDirectoryName"});
    d.Set(ds.flags.ExportGzip, {"FlagExportGzip"});
    d.Set(ds.flags.ExportPreserveSeriesNumber, {"FlagExportPreserveSeriesNumber"});
    d.Set(ds.flags.ExportWritePhaseDirectory, {"FlagExportWritePhaseDirectory"});
    d.Set(ds.flags.ExportWriteSeriesDirectory, {"FlagExportWriteSeriesDirectory"});
}


/* ---------------------------------------------------------------------------- */
/* ----- object lookup helpers ------------------------------------------------ */
/* ---------------------------------------------------------------------------- */

/* find the --subjectid subject */
static qint64 RequireSubject(squirrel *sqrl, const modification &mod, QString &m) {
    if (mod.subjectID.isEmpty()) {
        m = "--subjectid is required for this object";
        return -1;
    }
    qint64 rowID = sqrl->FindSubject(mod.subjectID);
    if (rowID < 0)
        m = QString("Subject [%1] not found in package").arg(mod.subjectID);
    return rowID;
}

/* find a study of the --subjectid subject */
static qint64 RequireStudy(squirrel *sqrl, const modification &mod, int studyNum, QString &m) {
    if (RequireSubject(sqrl, mod, m) < 0)
        return -1;
    if (studyNum < 1) {
        m = "--studynum is required for this object";
        return -1;
    }
    qint64 rowID = sqrl->FindStudy(mod.subjectID, studyNum);
    if (rowID < 0)
        m = QString("Study [%1] not found for subject [%2]").arg(studyNum).arg(mod.subjectID);
    return rowID;
}

/* find an existing observation or intervention, by --objectid (name) and --objectdate
   (DateStart), or by the name and DateStart in --objectdata */
static qint64 FindObservationOrIntervention(squirrel *sqrl, const modification &mod, objectData &d, QString &m) {
    if (RequireSubject(sqrl, mod, m) < 0)
        return -1;

    bool isObservation = (mod.object == Observation);
    QStringList nameKeys = isObservation ? QStringList{"ObservationName"} : QStringList{"InterventionName"};
    QStringList dateKeys = isObservation ? QStringList{"DateStart"} : QStringList{"DateInterventionStart", "DateStart"};
    QString what = isObservation ? "Observation" : "Intervention";

    QString name, date;
    if (!mod.objectID.isEmpty()) {
        name = mod.objectID;
        date = mod.objectDate;
    }
    else {
        /* these keys identify the object, and are also (unchanged) values to apply */
        name = d.Has(nameKeys) ? d.String(nameKeys) : "";
        date = d.Has(dateKeys) ? d.String(dateKeys) : "";
    }
    if (name.isEmpty() || date.isEmpty()) {
        m = QString("%1 not specified. Use --objectid <%2> and --objectdate <DateStart>").arg(what).arg(nameKeys.first());
        return -1;
    }
    QDateTime dateStart = utils::StringToDatetime(date);
    qint64 rowID = isObservation ? sqrl->FindObservation(mod.subjectID, name, dateStart) : sqrl->FindIntervention(mod.subjectID, name, dateStart);
    if (rowID < 0)
        m = QString("%1 [%2] with DateStart [%3] not found for subject [%4]").arg(what).arg(name).arg(dateStart.toString("yyyy-MM-dd HH:mm:ss")).arg(mod.subjectID);
    return rowID;
}

/* the name of a package-level object (experiment, pipeline, group analysis, data dictionary), from --objectid or --objectdata */
static QString ObjectName(const modification &mod, objectData &d, QString key) {
    return mod.objectID.isEmpty() ? d.String({key}) : mod.objectID;
}

/* find a package-level object by name */
static qint64 FindByName(squirrel *sqrl, ObjectType object, QString name) {
    switch (object) {
        case Experiment: return sqrl->FindExperiment(name);
        case Pipeline: return sqrl->FindPipeline(name);
        case GroupAnalysis: return sqrl->FindGroupAnalysis(name);
        case DataDictionary: return sqrl->FindDataDictionary(name);
        default: return -1;
    }
}

/* add observations or interventions from a CSV/TSV file, one per row. Columns are the --objectdata variable names, plus an optional SubjectID */
static bool ImportRows(squirrel *sqrl, const modification &mod, QString &done, QString &m) {
    QString text = utils::ReadTextFileToString(mod.dataPath);
    utils::indexedHash table;
    QStringList columns;
    QString msg;
    bool parsed;
    if (mod.dataPath.endsWith(".csv", Qt::CaseInsensitive))
        parsed = utils::ParseCSV(text, table, columns, msg);
    else if (mod.dataPath.endsWith(".tsv", Qt::CaseInsensitive))
        parsed = utils::ParseTSV(text, table, columns, msg);
    else {
        m = QString("File [%1] has an unrecognized extension. Expected .csv or .tsv").arg(mod.dataPath);
        return false;
    }
    if (!parsed) {
        m = QString("Unable to read [%1]. %2").arg(mod.dataPath).arg(msg);
        return false;
    }

    QString what = (mod.object == Observation) ? "observation" : "intervention";
    QList<int> rows = table.keys();
    std::sort(rows.begin(), rows.end());
    int count = 0;
    foreach (int r, rows) {
        objectData rd;
        for (auto it = table[r].constBegin(); it != table[r].constEnd(); ++it)
            rd.Insert(it.key(), it.value());

        /* each row can name its subject, otherwise --subjectid is used */
        QString subjectID = rd.String({"SubjectID"}, mod.subjectID);
        qint64 subjectRowID = sqrl->FindSubject(subjectID);
        if (subjectRowID < 0) {
            m = QString("Row %1: subject [%2] not found in package. Use a SubjectID column, or --subjectid").arg(r + 2).arg(subjectID);
            return false;
        }

        if (mod.object == Observation) {
            squirrelObservation o(sqrl->GetDatabaseUUID());
            ApplyObservation(rd, o);
            if (o.ObservationName.isEmpty() || !o.DateStart.isValid()) {
                m = QString("Row %1: ObservationName and DateStart are required").arg(r + 2);
                return false;
            }
            o.subjectRowID = subjectRowID;
            if (!rd.Check(what, m)) { m = QString("Row %1: %2").arg(r + 2).arg(m); return false; }
            o.Store();
        }
        else {
            squirrelIntervention i(sqrl->GetDatabaseUUID());
            ApplyIntervention(rd, i);
            if (i.InterventionName.isEmpty() || !i.DateStart.isValid()) {
                m = QString("Row %1: InterventionName and DateStart are required").arg(r + 2);
                return false;
            }
            i.subjectRowID = subjectRowID;
            if (!rd.Check(what, m)) { m = QString("Row %1: %2").arg(r + 2).arg(m); return false; }
            i.Store();
        }
        count++;
    }
    done = QString("Added %1 %2(s) from [%3]").arg(count).arg(what).arg(mod.dataPath);
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- AddObject ------------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/* The object-specific part of AddObject(). On success, done describes what was added */
static bool AddToPackage(squirrel *sqrl, const modification &mod, objectData &d, const QStringList &dataFiles, QString &done, QString &m) {
    QString db = sqrl->GetDatabaseUUID();
    bool hasFiles = !dataFiles.isEmpty();

    switch (mod.object) {
    case Subject: {
        QString id = d.Has({"SubjectID", "ID"}) ? d.String({"SubjectID", "ID"}) : mod.objectID;
        if (id.isEmpty()) { m = "No subject ID specified. Use SubjectID=<id> in --objectdata, or --objectid"; return false; }
        if (sqrl->FindSubject(id) >= 0) { m = QString("Subject [%1] already exists in package").arg(id); return false; }
        squirrelSubject s(db);
        ApplySubject(d, s);
        s.ID = id;
        s.Store();
        sqrl->ResequenceSubjects();
        done = QString("Added subject [%1]").arg(id);
        return true;
    }
    case Study: {
        qint64 subjectRowID = RequireSubject(sqrl, mod, m);
        if (subjectRowID < 0) return false;
        squirrelStudy st(db);
        ApplyStudy(d, st);
        /* the study number comes from StudyNumber, --studynum, --objectid, or is the next available number */
        if (!d.Has({"StudyNumber"})) {
            if (mod.studyNumber > 0) st.StudyNumber = mod.studyNumber;
            else if (mod.objectID.toInt() > 0) st.StudyNumber = mod.objectID.toInt();
            else {
                squirrelSubject subj(db);
                subj.SetObjectID(subjectRowID);
                subj.Get();
                st.StudyNumber = subj.GetNextStudyNumber();
            }
        }
        if (st.StudyNumber < 1) { m = "StudyNumber must be 1 or greater"; return false; }
        if (sqrl->FindStudy(mod.subjectID, st.StudyNumber) >= 0) { m = QString("Study [%1] already exists for subject [%2]").arg(st.StudyNumber).arg(mod.subjectID); return false; }
        st.subjectRowID = subjectRowID;
        st.Store();
        sqrl->ResequenceStudies(subjectRowID);
        done = QString("Added study [%1] to subject [%2]").arg(st.StudyNumber).arg(mod.subjectID);
        return true;
    }
    case Series: {
        qint64 studyRowID = RequireStudy(sqrl, mod, mod.studyNumber, m);
        if (studyRowID < 0) return false;
        /* the series number comes from --seriesnum, --objectid, SeriesNumber, or is the next available number */
        int seriesNum = (mod.seriesNumber > 0) ? mod.seriesNumber : (mod.objectID.toInt() > 0) ? mod.objectID.toInt() : 0;
        qint64 seriesRowID = (seriesNum > 0) ? sqrl->FindSeries(mod.subjectID, mod.studyNumber, seriesNum) : -1;
        if (seriesRowID >= 0) {
            if (d.HasUnused()) { m = QString("Series [%1] already exists. Use --operation update to change its variables").arg(seriesNum); return false; }
            if (!hasFiles) { m = QString("Series [%1] already exists. Specify --datapath to add files to it").arg(seriesNum); return false; }
            sqrl->AddStagedFiles(Series, seriesRowID, dataFiles);
            done = QString("Added %1 file(s) to series [%2]").arg(dataFiles.size()).arg(seriesNum);
            return true;
        }
        if (!hasFiles) { m = "No --datapath specified. A datapath is required when adding a series"; return false; }
        squirrelSeries se(db);
        ApplySeries(d, se);
        if (seriesNum > 0) se.SeriesNumber = seriesNum;
        else if (se.SeriesNumber < 1) {
            squirrelStudy st(db);
            st.SetObjectID(studyRowID);
            st.Get();
            se.SeriesNumber = st.GetNextSeriesNumber();
        }
        if (sqrl->FindSeries(mod.subjectID, mod.studyNumber, se.SeriesNumber) >= 0) { m = QString("Series [%1] already exists").arg(se.SeriesNumber); return false; }
        if (d.Has({"ExperimentName"})) {
            QString exp = d.String({"ExperimentName"});
            se.experimentRowID = exp.isEmpty() ? -1 : sqrl->FindExperiment(exp);
            if (!exp.isEmpty() && (se.experimentRowID < 0)) { m = QString("Experiment [%1] not found in package").arg(exp); return false; }
        }
        se.stagedFiles = dataFiles;
        se.studyRowID = studyRowID;
        se.Store();
        sqrl->ResequenceSeries(studyRowID);
        done = QString("Added series [%1] with %2 file(s)").arg(se.SeriesNumber).arg(dataFiles.size());
        return true;
    }
    case Observation:
    case Intervention: {
        if (!mod.dataPath.isEmpty())
            return ImportRows(sqrl, mod, done, m);
        qint64 subjectRowID = RequireSubject(sqrl, mod, m);
        if (subjectRowID < 0) return false;
        if (mod.object == Observation) {
            squirrelObservation o(db);
            ApplyObservation(d, o);
            if (o.ObservationName.isEmpty() || !o.DateStart.isValid()) { m = "ObservationName and DateStart are required"; return false; }
            if (sqrl->FindObservation(mod.subjectID, o.ObservationName, o.DateStart) >= 0) { m = QString("Observation [%1] with DateStart [%2] already exists for subject [%3]").arg(o.ObservationName).arg(o.DateStart.toString("yyyy-MM-dd HH:mm:ss")).arg(mod.subjectID); return false; }
            o.subjectRowID = subjectRowID;
            o.Store();
            done = QString("Added observation [%1] to subject [%2]").arg(o.ObservationName).arg(mod.subjectID);
        }
        else {
            squirrelIntervention i(db);
            ApplyIntervention(d, i);
            if (i.InterventionName.isEmpty() || !i.DateStart.isValid()) { m = "InterventionName and DateStart are required"; return false; }
            if (sqrl->FindIntervention(mod.subjectID, i.InterventionName, i.DateStart) >= 0) { m = QString("Intervention [%1] with DateStart [%2] already exists for subject [%3]").arg(i.InterventionName).arg(i.DateStart.toString("yyyy-MM-dd HH:mm:ss")).arg(mod.subjectID); return false; }
            i.subjectRowID = subjectRowID;
            i.Store();
            done = QString("Added intervention [%1] to subject [%2]").arg(i.InterventionName).arg(mod.subjectID);
        }
        return true;
    }
    case Analysis: {
        qint64 studyRowID = RequireStudy(sqrl, mod, mod.studyNumber, m);
        if (studyRowID < 0) return false;
        /* AnalysisName and PipelineName default to each other; the analysis directory is named after the pipeline */
        QString name = d.Has({"AnalysisName"}) ? d.String({"AnalysisName"}) : mod.objectID;
        QString pipelineName = d.Has({"PipelineName"}) ? d.String({"PipelineName"}) : name;
        if (name.isEmpty()) name = pipelineName;
        if (name.isEmpty()) { m = "No analysis name specified. Use AnalysisName or PipelineName in --objectdata, or --objectid"; return false; }
        qint64 analysisRowID = sqrl->FindAnalysis(mod.subjectID, mod.studyNumber, name);
        if (analysisRowID >= 0) {
            if (d.HasUnused()) { m = QString("Analysis [%1] already exists. Use --operation update to change its variables").arg(name); return false; }
            if (!hasFiles) { m = QString("Analysis [%1] already exists. Specify --datapath to add files to it").arg(name); return false; }
            sqrl->AddStagedFiles(Analysis, analysisRowID, dataFiles);
            done = QString("Added %1 file(s) to analysis [%2]").arg(dataFiles.size()).arg(name);
            return true;
        }
        if (!hasFiles) { m = "No --datapath specified. A datapath is required when adding an analysis"; return false; }
        squirrelAnalysis a(db);
        ApplyAnalysis(d, a);
        a.AnalysisName = name;
        a.PipelineName = pipelineName;
        a.stagedFiles = dataFiles;
        a.studyRowID = studyRowID;
        a.Store();
        done = QString("Added analysis [%1] with %2 file(s)").arg(name).arg(dataFiles.size());
        return true;
    }
    case Experiment:
    case Pipeline:
    case GroupAnalysis:
    case DataDictionary: {
        QString key = (mod.object == Experiment) ? "ExperimentName" : (mod.object == Pipeline) ? "PipelineName" : (mod.object == GroupAnalysis) ? "GroupAnalysisName" : "DataDictionaryName";
        QString what = squirrel::ObjectTypeToString(mod.object).toLower();
        QString name = ObjectName(mod, d, key);
        if (name.isEmpty()) { m = QString("No name specified. Use %1=<name> in --objectdata, or --objectid").arg(key); return false; }
        qint64 rowID = FindByName(sqrl, mod.object, name);
        if (rowID >= 0) {
            if (d.HasUnused()) { m = QString("%1 [%2] already exists. Use --operation update to change its variables").arg(what).arg(name); return false; }
            if (!hasFiles) { m = QString("%1 [%2] already exists. Specify --datapath to add files to it").arg(what).arg(name); return false; }
            sqrl->AddStagedFiles(mod.object, rowID, dataFiles);
            done = QString("Added %1 file(s) to %2 [%3]").arg(dataFiles.size()).arg(what).arg(name);
            return true;
        }
        if (!hasFiles) { m = QString("No --datapath specified. A datapath is required when adding a %1").arg(what); return false; }
        if (mod.object == Experiment) {
            squirrelExperiment e(db); ApplyExperiment(d, e); e.ExperimentName = name; e.stagedFiles = dataFiles; e.Store();
        }
        else if (mod.object == Pipeline) {
            squirrelPipeline p(db); ApplyPipeline(d, p); p.PipelineName = name; p.stagedFiles = dataFiles; p.Store();
        }
        else if (mod.object == GroupAnalysis) {
            squirrelGroupAnalysis g(db); ApplyGroupAnalysis(d, g); g.GroupAnalysisName = name; g.stagedFiles = dataFiles; g.Store();
        }
        else {
            squirrelDataDictionary dd(db); ApplyDataDictionary(d, dd); dd.DataDictionaryName = name; dd.stagedFiles = dataFiles; dd.Store();
        }
        done = QString("Added %1 [%2] with %3 file(s)").arg(what).arg(name).arg(dataFiles.size());
        return true;
    }
    case DataDictionaryItem: {
        qint64 rowID = sqrl->FindDataDictionary(mod.objectID);
        if (rowID < 0) { m = QString("Data dictionary [%1] not found in package. Specify it with --objectid").arg(mod.objectID); return false; }
        squirrelDataDictionary dd(db);
        dd.SetObjectID(rowID);
        dd.Get();
        dataDictionaryItem item{};
        ApplyDataDictionaryItem(d, item);
        if (item.VariableName.isEmpty()) item.VariableName = mod.itemID;
        if (item.VariableName.isEmpty()) { m = "VariableName is required"; return false; }
        foreach (dataDictionaryItem existing, dd.dictItems)
            if (existing.VariableName == item.VariableName) { m = QString("Variable [%1] already exists in data dictionary [%2]").arg(item.VariableName).arg(mod.objectID); return false; }
        dd.dictItems.append(item);
        dd.Store();
        done = QString("Added variable [%1] to data dictionary [%2]").arg(item.VariableName).arg(mod.objectID);
        return true;
    }
    case DataStep: {
        qint64 rowID = sqrl->FindPipeline(mod.objectID);
        if (rowID < 0) { m = QString("Pipeline [%1] not found in package. Specify it with --objectid").arg(mod.objectID); return false; }
        squirrelPipeline p(db);
        p.SetObjectID(rowID);
        p.Get();
        dataStep ds{};
        ApplyDataStep(d, ds);
        /* the step number comes from StepNumber, --itemid, or is the next available number */
        if (ds.StepNumber < 1) ds.StepNumber = mod.itemID.toInt();
        if (ds.StepNumber < 1) {
            ds.StepNumber = 1;
            foreach (dataStep existing, p.dataSteps)
                ds.StepNumber = std::max(ds.StepNumber, existing.StepNumber + 1);
        }
        foreach (dataStep existing, p.dataSteps)
            if (existing.StepNumber == ds.StepNumber) { m = QString("Data step [%1] already exists in pipeline [%2]").arg(ds.StepNumber).arg(mod.objectID); return false; }
        p.dataSteps.append(ds);
        std::sort(p.dataSteps.begin(), p.dataSteps.end(), [](const dataStep &a, const dataStep &b) { return a.StepNumber < b.StepNumber; });
        p.Store();
        done = QString("Added data step [%1] to pipeline [%2]").arg(ds.StepNumber).arg(mod.objectID);
        return true;
    }
    default:
        m = "This object type cannot be added";
        return false;
    }
}


/**
 * @brief Add a new object (or files to an existing object) in a squirrel package.
 *
 * For file-bearing object types (Series, Analysis, Experiment, Pipeline,
 * GroupAnalysis, DataDictionary) the --datapath is resolved to a file list and
 * staged onto the object.  If the target object already exists the files are
 * appended to it rather than returning an error. For observations and
 * interventions, --datapath may instead be a .csv or .tsv file with one object per row.
 *
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::AddObject(QString packagePath, const modification &mod, QString &m) {

    objectData d;
    if (!d.Parse(mod.objectData, m))
        return false;

    /* observations and interventions read --datapath as a CSV/TSV file; everything else stages files from it */
    bool isTable = ((mod.object == Observation) || (mod.object == Intervention));
    QStringList dataFiles;
    if (!isTable) {
        dataFiles = CollectFiles(mod);
        if (!mod.dataPath.isEmpty() && dataFiles.isEmpty()) {
            m = QString("No files found at --datapath [%1]").arg(mod.dataPath);
            return false;
        }
    }
    else if (!mod.dataPath.isEmpty() && !utils::FileExists(mod.dataPath)) {
        m = QString("File [%1] not found").arg(mod.dataPath);
        return false;
    }

    /* load the package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetWriteLog(true);
    if (!sqrl->Read()) {
        m = QString("Package unreadable [%1]").arg(packagePath);
        delete sqrl;
        return false;
    }
    /* adding a subject, study, or series resequences its siblings, which moves them when the directory format is 'seq' */
    QHash<QString, QString> dataPaths = sqrl->GetDataPaths();

    QString done;
    bool ok = AddToPackage(sqrl, mod, d, dataFiles, done, m);
    if (ok)
        ok = d.Check(squirrel::ObjectTypeToString(mod.object).toLower(), m);
    if (ok)
        ok = sqrl->MoveDataPaths(dataPaths, m);
    if (ok && !sqrl->Write()) {
        m = "Failed to write squirrel package after add operation";
        ok = false;
    }
    delete sqrl;

    if (ok)
        utils::Print(done);
    return ok;
}


/* ---------------------------------------------------------------------------- */
/* ----- RemoveObject --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/* The object-specific part of RemoveObject(). On success, done describes what was removed */
static bool RemoveFromPackage(squirrel *sqrl, const modification &mod, objectData &d, QString &done, QString &m) {
    QString db = sqrl->GetDatabaseUUID();

    switch (mod.object) {
    case Subject: {
        QString id = mod.objectID.isEmpty() ? mod.subjectID : mod.objectID;
        qint64 rowID = sqrl->FindSubject(id);
        if (rowID < 0) { m = QString("Subject [%1] not found in package").arg(id); return false; }
        if (!sqrl->RemoveObject(Subject, rowID)) { m = "Unable to remove subject"; return false; }
        sqrl->ResequenceSubjects();
        done = QString("Removed subject [%1]").arg(id);
        return true;
    }
    case Study: {
        int studyNum = (mod.studyNumber > 0) ? mod.studyNumber : mod.objectID.toInt();
        qint64 rowID = RequireStudy(sqrl, mod, studyNum, m);
        if (rowID < 0) return false;
        if (!sqrl->RemoveObject(Study, rowID)) { m = "Unable to remove study"; return false; }
        sqrl->ResequenceStudies(sqrl->FindSubject(mod.subjectID));
        done = QString("Removed study [%1] from subject [%2]").arg(studyNum).arg(mod.subjectID);
        return true;
    }
    case Series: {
        qint64 studyRowID = RequireStudy(sqrl, mod, mod.studyNumber, m);
        if (studyRowID < 0) return false;
        int seriesNum = (mod.seriesNumber > 0) ? mod.seriesNumber : mod.objectID.toInt();
        qint64 rowID = sqrl->FindSeries(mod.subjectID, mod.studyNumber, seriesNum);
        if (rowID < 0) { m = QString("Series [%1] not found in study [%2] of subject [%3]").arg(seriesNum).arg(mod.studyNumber).arg(mod.subjectID); return false; }
        if (!sqrl->RemoveObject(Series, rowID)) { m = "Unable to remove series"; return false; }
        sqrl->ResequenceSeries(studyRowID);
        done = QString("Removed series [%1]").arg(seriesNum);
        return true;
    }
    case Analysis: {
        if (RequireStudy(sqrl, mod, mod.studyNumber, m) < 0) return false;
        qint64 rowID = sqrl->FindAnalysis(mod.subjectID, mod.studyNumber, mod.objectID);
        if (rowID < 0) { m = QString("Analysis [%1] not found in study [%2] of subject [%3]").arg(mod.objectID).arg(mod.studyNumber).arg(mod.subjectID); return false; }
        if (!sqrl->RemoveObject(Analysis, rowID)) { m = "Unable to remove analysis"; return false; }
        done = QString("Removed analysis [%1]").arg(mod.objectID);
        return true;
    }
    case Observation:
    case Intervention: {
        qint64 rowID = FindObservationOrIntervention(sqrl, mod, d, m);
        if (rowID < 0) return false;
        if (!sqrl->RemoveObject(mod.object, rowID)) { m = "Unable to remove object"; return false; }
        done = QString("Removed %1").arg(squirrel::ObjectTypeToString(mod.object).toLower());
        return true;
    }
    case Experiment:
    case Pipeline:
    case GroupAnalysis:
    case DataDictionary: {
        QString what = squirrel::ObjectTypeToString(mod.object).toLower();
        qint64 rowID = FindByName(sqrl, mod.object, mod.objectID);
        if (rowID < 0) { m = QString("%1 [%2] not found in package. Specify it with --objectid").arg(what).arg(mod.objectID); return false; }
        if (!sqrl->RemoveObject(mod.object, rowID)) { m = QString("Unable to remove %1").arg(what); return false; }
        done = QString("Removed %1 [%2]").arg(what).arg(mod.objectID);
        return true;
    }
    case DataDictionaryItem: {
        qint64 rowID = sqrl->FindDataDictionary(mod.objectID);
        if (rowID < 0) { m = QString("Data dictionary [%1] not found in package. Specify it with --objectid").arg(mod.objectID); return false; }
        QString variable = mod.itemID.isEmpty() ? d.String({"VariableName"}) : mod.itemID;
        squirrelDataDictionary dd(db);
        dd.SetObjectID(rowID);
        dd.Get();
        int before = dd.dictItems.size();
        dd.dictItems.erase(std::remove_if(dd.dictItems.begin(), dd.dictItems.end(), [&](const dataDictionaryItem &i) { return i.VariableName == variable; }), dd.dictItems.end());
        if (dd.dictItems.size() == before) { m = QString("Variable [%1] not found in data dictionary [%2]. Specify it with --itemid").arg(variable).arg(mod.objectID); return false; }
        dd.Store();
        done = QString("Removed variable [%1] from data dictionary [%2]").arg(variable).arg(mod.objectID);
        return true;
    }
    case DataStep: {
        qint64 rowID = sqrl->FindPipeline(mod.objectID);
        if (rowID < 0) { m = QString("Pipeline [%1] not found in package. Specify it with --objectid").arg(mod.objectID); return false; }
        int step = mod.itemID.isEmpty() ? d.String({"StepNumber"}).toInt() : mod.itemID.toInt();
        squirrelPipeline p(db);
        p.SetObjectID(rowID);
        p.Get();
        int before = p.dataSteps.size();
        p.dataSteps.erase(std::remove_if(p.dataSteps.begin(), p.dataSteps.end(), [&](const dataStep &s) { return s.StepNumber == step; }), p.dataSteps.end());
        if (p.dataSteps.size() == before) { m = QString("Data step [%1] not found in pipeline [%2]. Specify it with --itemid").arg(step).arg(mod.objectID); return false; }
        p.Store();
        done = QString("Removed data step [%1] from pipeline [%2]").arg(step).arg(mod.objectID);
        return true;
    }
    default:
        m = "This object type cannot be removed";
        return false;
    }
}


/**
 * @brief Remove an object from an existing squirrel package
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::RemoveObject(QString packagePath, const modification &mod, QString &m) {

    objectData d;
    if (!d.Parse(mod.objectData, m))
        return false;

    /* load the package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetWriteLog(false);
    if (!sqrl->Read()) {
        m = QString("Package unreadable");
        delete sqrl;
        return false;
    }
    /* removing an object resequences its siblings, which moves them when the directory format is 'seq' */
    QHash<QString, QString> dataPaths = sqrl->GetDataPaths();

    QString done;
    bool ok = RemoveFromPackage(sqrl, mod, d, done, m);
    if (ok)
        ok = d.Check(squirrel::ObjectTypeToString(mod.object).toLower(), m);
    if (ok)
        ok = sqrl->MoveDataPaths(dataPaths, m);
    if (ok && !sqrl->Write()) {
        m = "Unable to write squirrel package";
        ok = false;
    }
    delete sqrl;

    if (ok)
        utils::Print(done);
    return ok;
}


/* ---------------------------------------------------------------------------- */
/* ----- UpdateObject --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/* The object-specific part of UpdateObject(). On success, done describes what was updated */
static bool UpdateInPackage(squirrel *sqrl, const modification &mod, objectData &d, QString &done, QString &m) {
    QString db = sqrl->GetDatabaseUUID();

    switch (mod.object) {
    case Package: {
        ApplyPackage(d, sqrl);
        done = "Updated package";
        return true;
    }
    case Subject: {
        QString id = mod.objectID.isEmpty() ? mod.subjectID : mod.objectID;
        qint64 rowID = sqrl->FindSubject(id);
        if (rowID < 0) { m = QString("Subject [%1] not found in package").arg(id); return false; }
        squirrelSubject s(db);
        s.SetObjectID(rowID);
        if (!s.Get()) { m = QString("Unable to load subject [%1]").arg(id); return false; }
        ApplySubject(d, s);
        if (s.ID.isEmpty()) { m = "SubjectID cannot be blank"; return false; }
        if ((s.ID != id) && (sqrl->FindSubject(s.ID) >= 0)) { m = QString("Subject [%1] already exists in package").arg(s.ID); return false; }
        s.Store();
        done = QString("Updated subject [%1]").arg(s.ID);
        return true;
    }
    case Study: {
        int studyNum = (mod.studyNumber > 0) ? mod.studyNumber : mod.objectID.toInt();
        qint64 rowID = RequireStudy(sqrl, mod, studyNum, m);
        if (rowID < 0) return false;
        squirrelStudy st(db);
        st.SetObjectID(rowID);
        if (!st.Get()) { m = "Unable to load study"; return false; }
        ApplyStudy(d, st);
        if (st.StudyNumber < 1) { m = "StudyNumber must be 1 or greater"; return false; }
        if ((st.StudyNumber != studyNum) && (sqrl->FindStudy(mod.subjectID, st.StudyNumber) >= 0)) { m = QString("Study [%1] already exists for subject [%2]").arg(st.StudyNumber).arg(mod.subjectID); return false; }
        st.Store();
        done = QString("Updated study [%1] of subject [%2]").arg(st.StudyNumber).arg(mod.subjectID);
        return true;
    }
    case Series: {
        if (RequireStudy(sqrl, mod, mod.studyNumber, m) < 0) return false;
        int seriesNum = (mod.seriesNumber > 0) ? mod.seriesNumber : mod.objectID.toInt();
        qint64 rowID = sqrl->FindSeries(mod.subjectID, mod.studyNumber, seriesNum);
        if (rowID < 0) { m = QString("Series [%1] not found in study [%2] of subject [%3]").arg(seriesNum).arg(mod.studyNumber).arg(mod.subjectID); return false; }
        squirrelSeries se(db);
        se.SetObjectID(rowID);
        if (!se.Get()) { m = "Unable to load series"; return false; }
        ApplySeries(d, se);
        if (d.Has({"ExperimentName"})) {
            QString exp = d.String({"ExperimentName"});
            se.experimentRowID = exp.isEmpty() ? -1 : sqrl->FindExperiment(exp);
            if (!exp.isEmpty() && (se.experimentRowID < 0)) { m = QString("Experiment [%1] not found in package").arg(exp); return false; }
        }
        if (se.SeriesNumber < 1) { m = "SeriesNumber must be 1 or greater"; return false; }
        if ((se.SeriesNumber != seriesNum) && (sqrl->FindSeries(mod.subjectID, mod.studyNumber, se.SeriesNumber) >= 0)) { m = QString("Series [%1] already exists").arg(se.SeriesNumber); return false; }
        se.Store();
        done = QString("Updated series [%1]").arg(se.SeriesNumber);
        return true;
    }
    case Analysis: {
        if (RequireStudy(sqrl, mod, mod.studyNumber, m) < 0) return false;
        qint64 rowID = sqrl->FindAnalysis(mod.subjectID, mod.studyNumber, mod.objectID);
        if (rowID < 0) { m = QString("Analysis [%1] not found in study [%2] of subject [%3]").arg(mod.objectID).arg(mod.studyNumber).arg(mod.subjectID); return false; }
        squirrelAnalysis a(db);
        a.SetObjectID(rowID);
        if (!a.Get()) { m = "Unable to load analysis"; return false; }
        if (d.Has({"PipelineName"})) {
            QString pipelineName = d.String({"PipelineName"});
            a.pipelineRowID = sqrl->FindPipeline(pipelineName);
            if (a.pipelineRowID < 0) { m = QString("Pipeline [%1] not found in package").arg(pipelineName); return false; }
            a.PipelineName = pipelineName;
        }
        ApplyAnalysis(d, a);
        a.Store();
        done = QString("Updated analysis [%1]").arg(a.AnalysisName);
        return true;
    }
    case Observation: {
        qint64 rowID = FindObservationOrIntervention(sqrl, mod, d, m);
        if (rowID < 0) return false;
        squirrelObservation o(db);
        o.SetObjectID(rowID);
        if (!o.Get()) { m = "Unable to load observation"; return false; }
        ApplyObservation(d, o);
        if (o.ObservationName.isEmpty() || !o.DateStart.isValid()) { m = "ObservationName and DateStart cannot be blank"; return false; }
        o.Store();
        done = QString("Updated observation [%1]").arg(o.ObservationName);
        return true;
    }
    case Intervention: {
        qint64 rowID = FindObservationOrIntervention(sqrl, mod, d, m);
        if (rowID < 0) return false;
        squirrelIntervention i(db);
        i.SetObjectID(rowID);
        if (!i.Get()) { m = "Unable to load intervention"; return false; }
        ApplyIntervention(d, i);
        if (i.InterventionName.isEmpty() || !i.DateStart.isValid()) { m = "InterventionName and DateStart cannot be blank"; return false; }
        i.Store();
        done = QString("Updated intervention [%1]").arg(i.InterventionName);
        return true;
    }
    case Experiment:
    case Pipeline:
    case GroupAnalysis:
    case DataDictionary: {
        QString what = squirrel::ObjectTypeToString(mod.object).toLower();
        qint64 rowID = FindByName(sqrl, mod.object, mod.objectID);
        if (rowID < 0) { m = QString("%1 [%2] not found in package. Specify it with --objectid").arg(what).arg(mod.objectID); return false; }
        /* a new name must not be blank or already used */
        auto nameOk = [&](QString newName) -> bool {
            if (newName.isEmpty()) { m = QString("The %1 name cannot be blank").arg(what); return false; }
            if ((newName != mod.objectID) && (FindByName(sqrl, mod.object, newName) >= 0)) { m = QString("%1 [%2] already exists in package").arg(what).arg(newName); return false; }
            done = QString("Updated %1 [%2]").arg(what).arg(newName);
            return true;
        };
        if (mod.object == Experiment) {
            squirrelExperiment e(db); e.SetObjectID(rowID); e.Get(); ApplyExperiment(d, e);
            if (!nameOk(e.ExperimentName)) return false;
            e.Store();
        }
        else if (mod.object == Pipeline) {
            squirrelPipeline p(db); p.SetObjectID(rowID); p.Get(); ApplyPipeline(d, p);
            if (!nameOk(p.PipelineName)) return false;
            p.Store();
        }
        else if (mod.object == GroupAnalysis) {
            squirrelGroupAnalysis g(db); g.SetObjectID(rowID); g.Get(); ApplyGroupAnalysis(d, g);
            if (!nameOk(g.GroupAnalysisName)) return false;
            g.Store();
        }
        else {
            squirrelDataDictionary dd(db); dd.SetObjectID(rowID); dd.Get(); ApplyDataDictionary(d, dd);
            if (!nameOk(dd.DataDictionaryName)) return false;
            dd.Store();
        }
        return true;
    }
    case DataDictionaryItem: {
        qint64 rowID = sqrl->FindDataDictionary(mod.objectID);
        if (rowID < 0) { m = QString("Data dictionary [%1] not found in package. Specify it with --objectid").arg(mod.objectID); return false; }
        squirrelDataDictionary dd(db);
        dd.SetObjectID(rowID);
        dd.Get();
        for (int i = 0; i < dd.dictItems.size(); i++) {
            if (dd.dictItems[i].VariableName == mod.itemID) {
                ApplyDataDictionaryItem(d, dd.dictItems[i]);
                if (dd.dictItems[i].VariableName.isEmpty()) { m = "VariableName cannot be blank"; return false; }
                dd.Store();
                done = QString("Updated variable [%1] in data dictionary [%2]").arg(dd.dictItems[i].VariableName).arg(mod.objectID);
                return true;
            }
        }
        m = QString("Variable [%1] not found in data dictionary [%2]. Specify it with --itemid").arg(mod.itemID).arg(mod.objectID);
        return false;
    }
    case DataStep: {
        qint64 rowID = sqrl->FindPipeline(mod.objectID);
        if (rowID < 0) { m = QString("Pipeline [%1] not found in package. Specify it with --objectid").arg(mod.objectID); return false; }
        squirrelPipeline p(db);
        p.SetObjectID(rowID);
        p.Get();
        for (int i = 0; i < p.dataSteps.size(); i++) {
            if (p.dataSteps[i].StepNumber == mod.itemID.toInt()) {
                ApplyDataStep(d, p.dataSteps[i]);
                std::sort(p.dataSteps.begin(), p.dataSteps.end(), [](const dataStep &a, const dataStep &b) { return a.StepNumber < b.StepNumber; });
                p.Store();
                done = QString("Updated data step [%1] in pipeline [%2]").arg(mod.itemID).arg(mod.objectID);
                return true;
            }
        }
        m = QString("Data step [%1] not found in pipeline [%2]. Specify it with --itemid").arg(mod.itemID).arg(mod.objectID);
        return false;
    }
    default:
        m = "This object type cannot be updated";
        return false;
    }
}


/**
 * @brief Update fields of an existing object in a squirrel package. Only the
 * variables given in --objectdata are changed; a variable given with an empty
 * value is cleared
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::UpdateObject(QString packagePath, const modification &mod, QString &m) {

    objectData d;
    if (!d.Parse(mod.objectData, m))
        return false;

    /* load the package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    if (!sqrl->Read()) {
        m = QString("Package unreadable");
        delete sqrl;
        return false;
    }
    /* changing an ID, number, or name changes the object's directory */
    QHash<QString, QString> dataPaths = sqrl->GetDataPaths();

    QString done;
    bool ok = UpdateInPackage(sqrl, mod, d, done, m);
    if (ok)
        ok = d.Check(squirrel::ObjectTypeToString(mod.object).toLower(), m);
    if (ok) {
        /* a changed ID or number can change the sequence order */
        sqrl->ResequenceAll();
        ok = sqrl->MoveDataPaths(dataPaths, m);
    }
    if (ok && !sqrl->WriteUpdate()) {
        m = "Unable to write updated squirrel package";
        ok = false;
    }
    delete sqrl;

    if (ok)
        utils::Print(done);
    return ok;
}


/* ---------------------------------------------------------------------------- */
/* ----- SplitByModality ------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Split a package by modality, creating one new package per modality
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::SplitByModality(QString packagePath, const modification &mod, QString &m) {
    /* Note: the data is COPIED, not moved, from the original package to the new packages.
     * So an example package of 100MB with 2 modalities will write out 2 packages, with each being 50MB
     * and the original package will remain on disk. After the split operation there will be three image packages
     * with a total of 200MB on disk, plus one package with only interventions/observations. */

    Q_UNUSED(mod);

    utils::Print("Splitting package [" + packagePath + "] by modality...");
    /* read squirrel package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(false);
    qint64 unzippedSize = sqrl->GetUnzipSize();
    qint64 totalSpaceRequired = unzippedSize * 3; /* 3x the unzipped size needed */
    if (totalSpaceRequired > sqrl->GetFreeDiskSpace()) {
        m = QString("Not enough free space on disk to perform this operation. %1 bytes free space needed, but only %2 bytes free").arg(totalSpaceRequired).arg(sqrl->GetFreeDiskSpace());
        delete sqrl;
        return false;
    }

    if (sqrl->Read()) {
        utils::Print("Read package [" + packagePath + "] successfully");
    }
    else {
        m = QString("Package unreadable [" + packagePath + "]");
        m += QString("Log [" + sqrl->GetLog() + "]");
        delete sqrl;
        return false;
    }

    /* get list of unique modalities */
    utils::Print("Getting list of modalities in package...");
    QSet<QString> modalities;
    QList <squirrelSubject> subjects = sqrl->GetSubjectList();
    int count = subjects.size();
    if (count > 0) {
        foreach (squirrelSubject subject, subjects) {
            if (subject.Get()) {
                QList <squirrelStudy> studies = sqrl->GetStudyList(subject.GetObjectID());
                int count = studies.size();
                if (count > 0) {
                    foreach (squirrelStudy study, studies) {
                        if (study.Get()) {
                            modalities.insert(study.Modality);
                        }
                        else { utils::Print("Error getting study object..."); }
                    }
                }
                else {
                    utils::Print("No studies found for subject [" + subject.ID + "]...");
                }
            }
            else { utils::Print("Error getting subject object..."); }
        }
    }
    else {
        utils::Print("Package contains no subjects. Nothing to split by modality...");
        delete sqrl;
        return true;
    }

    /* get unique list of modalities */
    QStringList mods(modalities.begin(), modalities.end());
    if (mods.size() == 0) {
        sqrl->Log("Package contains no modalities (no studies). Nothing to do.");
        delete sqrl;
        return true;
    }
    else if (mods.size() == 1) {
        sqrl->Log("Package contains one modality. Nothing to do.");
    }

    sqrl->Log(QString("Package contains %1 modalities: ").arg(mods.size()) + mods.join(", "));

    /* extract the original package to disk */
    QString tmpDir = sqrl->GetSystemTempDir() + "/squirrel-" + utils::GenerateRandomString(10);
    QString m2;
    utils::MakePath(tmpDir, m2);

    /* create N packages, one for each modality */
    foreach (QString modality, mods) {
        QFileInfo finfo(packagePath);
        QString newPackagePath = finfo.absolutePath() + "/" + finfo.baseName() + "-" + modality + "." + finfo.completeSuffix();
        sqrl->Log(QString("Creating new package %1 for %2 modality").arg(newPackagePath).arg(modality));

        squirrel *sqrl2 = new squirrel();
        //sqrl2->SetDebugSQL(true);
        //sqrl2->SetDebug(true);
        sqrl2->SetFileMode(FileMode::NewPackage);
        sqrl2->SetPackagePath(newPackagePath);
        sqrl2->SetOverwritePackage(true);
        sqrl2->DataFormat = "orig";
        sqrl2->SetWriteLog(true);
        QString newDbID = sqrl2->GetDatabaseUUID();
        //sqrl2->SetSystemTempDir();

        QList <squirrelSubject> subjects = sqrl->GetSubjectList();
        if (subjects.size() > 0) {
            foreach (squirrelSubject subject, subjects) {
                if (subject.Get()) {
                    QList <squirrelStudy> studies = sqrl->GetStudyList(subject.GetObjectID());
                    if (studies.size() > 0) {
                        foreach (squirrelStudy study, studies) {
                            if (study.Get()) {
                                /* extract the files for the matching modality */
                                if (study.Modality == modality) {

                                    /* find existing subject in sqrl2 */
                                    qint64 newSubjectRowID = sqrl2->FindSubject(subject.ID);
                                    squirrelSubject newSubject = subject;
                                    if (newSubjectRowID < 0) {
                                        /* create this subject if they don't already exist, and copy original subject to new subject */
                                        newSubject.SetDatabaseUUID(newDbID);
                                        newSubject.SetObjectID(-1);
                                        newSubject.Store();
                                        newSubjectRowID = newSubject.GetObjectID();
                                    }
                                    else {
                                        /* get the sqrl2 subject that already exists */
                                        newSubject = sqrl2->GetSubject(newSubjectRowID);
                                    }

                                    /* copy original study to new study */
                                    squirrelStudy newStudy = study;
                                    newStudy.SetDatabaseUUID(newDbID);
                                    newStudy.SetObjectID(-1); /* reset the rowID */
                                    newStudy.subjectRowID = newSubjectRowID;
                                    newStudy.Store();
                                    qint64 newStudyRowID = newStudy.GetObjectID();
                                    sqrl2->Debug(QString("Subject %1 & study %2 contain %3 modality data... extracting the series to disk").arg(newSubject.ID).arg(newStudy.StudyNumber).arg(newStudy.Modality));

                                    /* get series */
                                    QList <squirrelSeries> serieses = sqrl->GetSeriesList(study.GetObjectID());
                                    if (serieses.size() > 0) {
                                        foreach (squirrelSeries series, serieses) {
                                            if (series.Get()) {

                                                /* copy original series to new series */
                                                squirrelSeries newSeries = series;
                                                newSeries.SetDatabaseUUID(newDbID);
                                                newSeries.SetObjectID(-1); /* reset the rowID */
                                                newSeries.studyRowID = newStudyRowID;
                                                newSeries.Store();
                                                qint64 newSeriesRowID = newSeries.GetObjectID();
                                                //sqrl2->Log(QString("Copying original seriesRowID [%1] to new sqrl2 seriesRowID [%2]").arg(series.GetObjectID()).arg(newSeriesRowID), __FUNCTION__);

                                                /* extract files from original package and add them to the new package */
                                                QString m3;
                                                QString archiveSeriesPath;
                                                #ifdef Q_OS_WINDOWS
                                                    archiveSeriesPath = QString("data\\%1\\%2\\%3\\*").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber);
                                                #else
                                                    archiveSeriesPath = QString("data/%1/%2/%3/*").arg(subject.ID).arg(study.StudyNumber).arg(series.SeriesNumber);
                                                #endif

                                                QString newSeriesPath = QString("%1/data/%2/%3/%4").arg(tmpDir).arg(newSubject.ID).arg(newStudy.StudyNumber).arg(newSeries.SeriesNumber);
                                                //QString newSeriesPath = tmpDir;
                                                utils::MakePath(newSeriesPath, m3);

                                                sqrl->ExtractArchiveFilesToDirectory(sqrl->GetPackagePath(), archiveSeriesPath, tmpDir, m3);
                                                qint64 c(0), b(0);
                                                utils::GetDirSizeAndFileCount(newSeriesPath, c, b, true);
                                                sqrl->Log(QString("Extracted files from original squirrel packge [%1 :: %2] to directory [%3]. Directory now contains [%4] files of size [%5] bytes").arg(sqrl->GetPackagePath()).arg(archiveSeriesPath).arg(newSeriesPath).arg(c).arg(b));

                                                utils::Print(QString("Searching %1 for files matching '*'").arg(newSeriesPath));
                                                QStringList allFiles = utils::FindAllFiles(newSeriesPath, "*");
                                                utils::Print(QString("Found %1 files [%2] in path [%3]").arg(allFiles.size()).arg(allFiles.join(", ")).arg(newSeriesPath));
                                                sqrl2->AddStagedFiles(Series, newSeriesRowID, allFiles);
                                                sqrl2->Debug(QString("Added staged series files from directory [%1] to seriesRowID [%2]").arg(newSeriesPath).arg(newSeriesRowID), __FUNCTION__);
                                            }
                                        }
                                    }
                                }
                            }
                            else { utils::Print("Error getting study object..."); }
                        }
                    }
                }
                else { utils::Print("Error getting subject object..."); }
            }
        }
        //sqrl2->Print();
        sqrl2->Write();
        delete sqrl2;
    }

    /* remove the temporary extraction directory */
    if (!utils::RemoveDir(tmpDir, m2))
        utils::Print(QString("Error removing temporary directory [%1]. Message [%2]").arg(tmpDir).arg(m2));

    /* delete squirrel object(s) */
    delete sqrl;
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- EmbedDatabase ---------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Rebuild "squirrel.db" from a full read of squirrel.json (see
 * squirrel::WriteEmbeddedDatabase()). Backfills packages that have no
 * squirrel.db, or whose squirrel.db is stale or came from a quick read. Any
 * existing squirrel.db is ignored and replaced.
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters (unused)
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::EmbedDatabase(QString packagePath, const modification &mod, QString &m) {

    Q_UNUSED(mod);

    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    /* must be a full read (not quick), so Params and file lists - which a
       quick read skips - end up in the embedded database, marked as complete */
    sqrl->SetQuickRead(false);
    /* always rebuild from squirrel.json, never from an existing squirrel.db */
    sqrl->SetUseEmbeddedDatabase(false);

    if (!sqrl->Read()) {
        m = QString("Package unreadable [%1]").arg(packagePath);
        delete sqrl;
        return false;
    }

    bool ok = sqrl->WriteEmbeddedDatabase(m);
    if (ok)
        m = "Embedded squirrel.db in [" + packagePath + "]";
    delete sqrl;
    return ok;
}


/* ---------------------------------------------------------------------------- */
/* ----- RemovePHI -------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Remove all protected health information (dates, birthdates) from a squirrel package
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::RemovePHI(QString packagePath, const modification &mod, QString &m) {

    Q_UNUSED(mod);

    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(true);

    if (sqrl->Read()) {
        utils::Print("Read package [" + packagePath + "] successfully");
    }
    else {
        m = QString("Package unreadable [" + packagePath + "]");
        m += QString("Log [" + sqrl->GetLog() + "]");
        delete sqrl;
        return false;
    }

    QSqlQuery q(QSqlDatabase::database(sqrl->GetDatabaseUUID()));

    /* alphabetically by table ... */

    /* remove intervention dates */
    q.prepare("update Intervention set DateStart = '0000-00-00 00:00:00', DateEnd = '0000-00-00 00:00:00', DateRecordCreate = '0000-00-00 00:00:00', DateRecordEntry = '0000-00-00 00:00:00', DateRecordModify = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed intervention dates");

    /* remove observation dates */
    q.prepare("update Observation set DateStart = '0000-00-00 00:00:00', DateEnd = '0000-00-00 00:00:00', DateRecordCreate = '0000-00-00 00:00:00', DateRecordEntry = '0000-00-00 00:00:00', DateRecordModify = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed observation dates");

    /* remove series datetime */
    q.prepare("update Series set Datetime = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed series dates");

    /* remove study datetime */
    q.prepare("update Study set Datetime = '0000-00-00 00:00:00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed study dates");

    /* remove subject dateOfBirth */
    q.prepare("update Subject set DateOfBirth = '0000-00-00'");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    sqrl->Log("Removed subject birthdates");

    sqrl->WriteUpdate();

    delete sqrl;
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- RenumberSubjects ----------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Assign new sequential IDs to all subjects, preserving original IDs as alternate IDs
 * @param packagePath path to the squirrel package file
 * @param mod struct containing all operation parameters
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::RenumberSubjects(QString packagePath, const modification &mod, QString &m) {

    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(true);

    if (!sqrl->Read()) {
        m = QString("Package unreadable [%1]").arg(packagePath);
        delete sqrl;
        return false;
    }

    /* new subject IDs change the subject directories when the directory format is 'orig' */
    QHash<QString, QString> dataPaths = sqrl->GetDataPaths();

    /* collect subject rowIDs and current IDs, sorted ascending by ID */
    QSqlQuery q(QSqlDatabase::database(sqrl->GetDatabaseUUID()));
    q.prepare("select SubjectRowID, ID from Subject order by ID asc");
    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    QList<QPair<qint64, QString>> subjects;
    while (q.next()) {
        subjects.append(qMakePair(q.value("SubjectRowID").toLongLong(), q.value("ID").toString()));
    }

    int n = subjects.size();
    if (n == 0) {
        m = "Package contains no subjects";
        delete sqrl;
        return true;
    }

    /* optionally shuffle for random assignment */
    if (mod.renumberRandomize) {
        for (int i = n - 1; i > 0; --i) {
            int j = QRandomGenerator::global()->bounded(i + 1);
            subjects.swapItemsAt(i, j);
        }
    }

    /* auto-size digits if not specified */
    int digits = mod.renumberDigits;
    if (digits <= 0) {
        int maxNum = mod.renumberStartNum + n - 1;
        digits = QString::number(maxNum).length();
    }

    /* subject IDs must be unique, and the new IDs can overlap the current ones (for
       example when renumbering an already renumbered package), so first move every
       subject to a temporary ID that cannot collide */
    for (int i = 0; i < n; ++i) {
        QSqlQuery qt(QSqlDatabase::database(sqrl->GetDatabaseUUID()));
        qt.prepare("update Subject set ID = :id where SubjectRowID = :rowid");
        qt.bindValue(":id", QString("__renumber_%1").arg(subjects[i].first));
        qt.bindValue(":rowid", subjects[i].first);
        if (!utils::SQLQuery(qt, __FUNCTION__, __FILE__, __LINE__)) {
            m = QString("Unable to renumber subject [%1]").arg(subjects[i].second);
            delete sqrl;
            return false;
        }
    }

    /* assign new IDs */
    for (int i = 0; i < n; ++i) {
        qint64 rowID = subjects[i].first;
        QString oldID = subjects[i].second;
        QString newID = mod.renumberPrefix + QString("%1").arg(mod.renumberStartNum + i, digits, 10, QChar('0'));

        /* load the subject, prepend old ID to AlternateIDs, update ID */
        squirrelSubject subj(sqrl->GetDatabaseUUID());
        subj.SetObjectID(rowID);
        if (!subj.Get()) {
            m = QString("Unable to load subject rowID [%1]").arg(rowID);
            delete sqrl;
            return false;
        }

        if ((oldID != newID) && !subj.AlternateIDs.contains(oldID))
            subj.AlternateIDs.prepend(oldID);
        subj.ID = newID;

        if (!subj.Store()) {
            m = QString("Unable to store updated subject [%1] -> [%2]").arg(oldID).arg(newID);
            delete sqrl;
            return false;
        }
    }

    /* subjects are sequenced by ID, so new IDs can change the sequence order */
    sqrl->ResequenceSubjects();

    /* move any data files whose directory changed */
    if (!sqrl->MoveDataPaths(dataPaths, m)) {
        delete sqrl;
        return false;
    }

    sqrl->WriteUpdate();

    delete sqrl;
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- PrintVariables ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Print a table of available metadata variables and their types for the given object type
 * @param object the object type whose variables should be printed
 */
void modify::PrintVariables(ObjectType object) {
    using namespace std;
    vector<vector<string> > data;

    if (object == Package) {
        data = {
            {"Variable","Type","Required","Description"},
            {"Changes","string","","Any CHANGE files"},
            {"Description","string","","Longer description of the package"},
            {"License","string","","Any sharing or license notes, or LICENSE files"},
            {"Notes","JSON object","","See details below"},
            {"PackageName","string","*","Short name of the package"},
            {"Readme","string","","Any README files"}
        };
    }

    if (object == Subject) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AlternateIDs","JSON array","","List of alternate IDs. Comma separated"},
            {"DateOfBirth","date","*","Subject's date of birth. Used to calculate age-at-server. Can be YYYY-00-00 to store year only, or YYYY-MM-00 to store year and month only"},
            {"EnrollmentGroup","string","","Enrollment group within the project"},
            {"EnrollmentStatus","string","","Enrollment status within the project"},
            {"Ethnicity1","string","","NIH defined ethnicity: Usually hispanic, non-hispanic"},
            {"Ethnicity2","string","","NIH defined race: americanindian, asian, black, hispanic, islander, white"},
            {"GUID","string","","Globally unique identifier, from NDA"},
            {"Gender","char","","Gender"},
            {"Notes","string","","Any notes about the subject"},
            {"Sex","char","*","Sex at birth (F,M,O,U)"},
            {"SubjectID","string","*","Unique ID of this subject. Each subject ID must be unique within the package"}
        };
    }

    if (object == Study) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AgeAtStudy","number","*","Subject's age in years at the time of the study"},
            {"StudyDatetime","datetime","*","Date of the study. Datetime is also accepted"},
            {"DayNumber","number","","For repeated studies and clinical trials, this indicates the day number of this study in relation to time 0"},
            {"Description","string","*","Study description"},
            {"Equipment","string","","Equipment name, on which the imaging session was collected"},
            {"Height","number","","Height in m of the subject at the time of the study"},
            {"Modality","string","*","Defines the type of data. See table of supported modalities"},
            {"Notes","string","","Any notes about the study"},
            {"StudyNumber","number","*","Study number. May be sequential or correspond to NiDB assigned study number"},
            {"StudyUID","string","","DICOM field StudyUID"},
            {"TimePoint","number","","Similar to day number, but this should be an ordinal number"},
            {"VisitType","string","","Type of visit. ex: Pre, Post"},
            {"Weight","number","","Weight in kg of the subject at the time of the study"}
        };
    }

    if (object == Series) {
        data = {
            {"Variable","Type","Required","Description"},
            {"BidsEntity","string","","BIDS entity (anat, fmri, dwi, etc)"},
            {"BidsSuffix","string","","BIDS suffix"},
            {"BidsTask","string","","BIDS Task name"},
            {"BidsRun","number","","BIDS run number"},
            {"BidsPhaseEncodingDirection","string","","BIDS PE direction"},
            {"Description","string","","Description of the series"},
            {"ExperimentName","string","","Experiment name associated with this series. Experiments link to the experiments section of the squirrel package"},
            {"Protocol","string","*","Protocol name"},
            {"Run","number","","The run identifies order of acquisition in cases of multiple identical series"},
            {"SeriesDatetime","datetime","*","Date of the series, usually taken from the DICOM header. Datetime is also accepted"},
            {"SeriesNumber","number","*","Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header"},
            {"SeriesUID","string","","From the SeriesUID DICOM tag"}
        };
    }

    if (object == Analysis) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AnalysisName","string","*","Name of the analysis, usually the same as the pipeline name. Defaults to PipelineName"},
            {"DateStart","date","*","Datetime of the start of the analysis"},
            {"DateEnd","date","","Datetime of the end of the analysis"},
            {"DateClusterStart","date","","Datetime the job began running on the cluster"},
            {"DateClusterEnd","date","","Datetime the job finished running on the cluster"},
            {"Hostname","string","","If run on a cluster, the hostname of the node on which the analysis run"},
            {"PipelineName","string","*","Name of the pipeline used to generate these results"},
            {"PipelineVersion","number","","Version of the pipeline used"},
            {"RunTime","number","","Elapsed wall time, in minutes, to run the analysis after setup"},
            {"SeriesCount","number","","Number of series downloaded/used to perform analysis"},
            {"SetupTime","number","","Elapsed wall time, in minutes, to copy data and set up analysis"},
            {"Status","string","","Status, should always be 'complete'"},
            {"StatusMessage","string","","Last running status message"},
            {"Successful","bool","","Analysis ran to completion without error and expected files were created"}
        };
    }

    if (object == Observation) {
        data = {
            {"Variable","Type","Required","Description"},
            {"DateEnd","datetime","","End datetime of the observation"},
            {"DateRecordCreate","datetime","","Date the record was created in the current database. The original record may have been imported from another database"},
            {"DateRecordEntry","datetime","","Date the record was first entered into a database"},
            {"DateRecordModify","datetime","","Date the record was modified in the current database"},
            {"DateStart","datetime","*","Start datetime of the observation"},
            {"Description","string","","Longer description of the measure"},
            {"Duration","number","","Duration of the measure in seconds, if known"},
            {"InstrumentName","string","","Name of the instrument associated with this measure"},
            {"ObservationName","string","*","Name of the observation"},
            {"ObservationType","string","","Type hint for the value: 'int', 'double', 'string', or 'timeseries'"},
            {"Notes","string","","Detailed notes"},
            {"Rater","string","","Name of the rater"},
            {"Value","string","*","Value (string or number)"}
        };
    }

    if (object == Intervention) {
        data = {
            {"Variable","Type","Required","Description"},
            {"AdministrationRoute","string","","Drug entry route (oral, IV, unknown, etc)"},
            {"DateRecordCreate","datetime","","Date the record was created in the current database. The original record may have been imported from another database"},
            {"DateRecordEntry","datetime","","Date the record was first entered into a database"},
            {"DateRecordModify","datetime","","Date the record was modified in the current database"},
            {"DateInterventionEnd","datetime","","Datetime the intervention was stopped. DateEnd is also accepted"},
            {"DateInterventionStart","datetime","*","Datetime the intervention was started. DateStart is also accepted"},
            {"Description","string","","Longer description"},
            {"DoseString","string","*","Full dosing string. Examples tylenol 325mg twice daily by mouth, or 5g marijuana inhaled by volcano"},
            {"DoseAmount","number","","In combination with other dose variables, the quantity of the drug"},
            {"DoseFrequency","string","","Description of the frequency of administration"},
            {"DoseKey","string","","For clinical trials, the dose key"},
            {"DoseUnit","string","","mg, g, ml, tablets, capsules, etc"},
            {"InterventionClass","string","","Drug class"},
            {"InterventionName","string","*","Name of the intervention"},
            {"Notes","string","","Notes about drug"},
            {"Rater","string","","Rater/experimenter name"}
        };
    }

    if (object == Pipeline) {
        data = {
            {"Variable","Type","Required","Description"},
            {"ClusterEngine","string","","Compute cluster engine (sge or slurm)"},
            {"ClusterMaxWallTime","number","","Maximum allowed clock (wall) time in minutes for the analysis to run"},
            {"ClusterMemory","number","","Amount of memory in GB requested for a running job"},
            {"ClusterNumberConcurrentAnalyses","number","1","Number of analyses allowed to run at the same time. This number if managed by NiDB and is different than grid engine queue size"},
            {"ClusterNumberCores","number","1","Number of CPU cores requested for a running job"},
            {"ClusterQueue","string","","Queue to submit jobs"},
            {"ClusterSubmitDelay","number","","Delay in hours, after the study datetime, to submit to the cluster. Allows time to upload behavioral data"},
            {"ClusterSubmitHost","string","","Hostname to submit jobs"},
            {"ClusterUser","string","","Submit username"},
            {"FlagSetupUseProfile","bool","","true if using the profile option, false otherwise"},
            {"FlagSetupUseTempDirectory","bool","","true if using a temporary directory, false otherwise"},
            {"PipelineAnalysisLevel","number","*","subject-level analysis (1) or group-level analysis (2)"},
            {"PipelineCompleteFiles","JSON array","","JSON array of complete files, with relative paths to analysisroot"},
            {"PipelineCreateDate","datetime","*","Date the pipeline was created"},
            {"PipelineDescription","string","","Longer pipeline description"},
            {"PipelineDirectory","string","","Directory where the analyses for this pipeline will be stored. Leave blank to use the default location"},
            {"PipelineDirectoryStructure","string","",""},
            {"PipelineName","string","*","Pipeline name"},
            {"PipelineNotes","string","","Extended notes about the pipeline"},
            {"PipelinePrimaryScript","string","*","See details of pipeline scripts"},
            {"PipelineResultScript","string","","Executable script to be run at completion of the analysis to find and insert results back into NiDB"},
            {"PipelineSecondaryScript","string","","See details of pipeline scripts"},
            {"PipelineVersion","number","1","Version of the pipeline"},
            {"SearchDependencyLevel","string","",""},
            {"SearchDependencyLinkType","string","",""},
            {"SearchGroup","string","","ID or name of a group on which this pipeline will run"},
            {"SearchGroupType","string","","Either subject or study"},
            {"SearchParentPipelines","string","","Comma separated list of parent pipelines"},
            {"SetupDataCopyMethod","string","","How the data is copied to the analysis directory: cp, softlink, hardlink"},
            {"SetupDependencyDirectory","string","",""},
            {"SetupTempDirectory","string","","The path to a temporary directory if it is used, on a compute node"}
        };
    }

    if (object == Experiment) {
        data = {
            {"Variable","Type","Required","Description"},
            {"ExperimentName","string","*","Unique name of the experiment"}
        };
    }

    if (object == DataDictionary) {
        data = {
            {"Variable","Type","Required","Description"},
            {"DataDictionaryName","string","*","Name of this data dictionary"}
        };
    }

    if (object == DataDictionaryItem) {
        data = {
            {"Variable","Type","Required","Description"},
            {"VariableType","string","*","Type of variable"},
            {"VariableName","string","*","Name of the variable"},
            {"VariableDescription","string","","Description of the variable"},
            {"KeyValueMapping","string","","List of possible key/value mappings in the format key1=value1, key2=value2. Example 1=Female, 2=Male"},
            {"ExpectedTimepoints","number","","Number of expected timepoints. Example, the study is expected to have 5 records of a variable"},
            {"RangeLow","number","","For numeric values, the lower limit"},
            {"RangeHigh","number","","For numeric values, the upper limit"}
        };
    }

    if (object == DataStep) {
        data = {
            {"Variable","Type","Required","Description"},
            {"StepNumber","number","*","The numerical order of this data step. Defaults to the next number"},
            {"FlagIsEnabled","bool","*","true if the step is enabled, false otherwise"},
            {"FlagIsOptional","bool","*","true if this data step is optional"},
            {"FlagIsPrimaryProtocol","bool","","true if this data step determines the primary study"},
            {"SearchAssociationType","string","*","study, or subject"},
            {"SearchDataLevel","string","*","nearestintime, samestudy"},
            {"SearchImageType","string","","Comma separated list of image types"},
            {"SearchModality","string","*","Modality to search for"},
            {"SearchNumberBOLDreps","string","","If SearchSeriesCriteria is usecriteria, then search based on this option"},
            {"SearchProtocol","string","*","Comma separated list of protocol name(s)"},
            {"SearchSeriesCriteria","string","*","all, first, last, largest, smallest, usecriteria"},
            {"ExportBehavioralDirectoryFormat","string","","nobeh, behroot, behseries, behseriesdir"},
            {"ExportBehavioralDirectoryName","string","","Name of the behavioral sub-directory"},
            {"ExportDataFormat","string","*","native, dicom, nifti3d, nifti4d, analyze3d, analyze4d, bids"},
            {"ExportSubDirectoryName","string","","Directory, relative to the analysisroot, where this data will be written"},
            {"FlagExportGzip","bool","","true if Nifti data should be gzipped"},
            {"FlagExportPreserveSeriesNumber","bool","","true to preserve series numbers in directory names"},
            {"FlagExportWritePhaseDirectory","bool","","true to write data to a sub directory based on the phase encoding direction"},
            {"FlagExportWriteSeriesDirectory","bool","","true to write each series to its own directory"}
        };
    }

    if (object == GroupAnalysis) {
        data = {
            {"Variable","Type","Required","Description"},
            {"Datetime","datetime","","Datetime of the group analysis"},
            {"Description","string","","Description"},
            {"GroupAnalysisName","string","*","Name of this group analysis"},
            {"Notes","string","","Notes about the group analysis"}
        };
    }

    if (data.empty()) {
        cout << "No variables are available for this object" << endl;
        return;
    }

    /* Find the maximum width of each column */
    vector<int> columnWidths(data[0].size());
    for (const auto &row : data) {
        for (size_t i = 0; i < row.size(); ++i) {
            columnWidths[i] = max(columnWidths[i], (int)row[i].size());
        }
    }

    /* Print the table */
    for (const auto &row : data) {
        for (size_t i = 0; i < row.size(); ++i) {
            cout << setw(columnWidths[i] + 2) << left << row[i] << " ";
        }
        cout << endl;
    }

}


/* ---------------------------------------------------------------------------- */
/* ----- MergePackages -------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Merge N squirrel packages into one output package
 * @param inputPaths list of input package paths
 * @param outputPath path for the merged output package
 * @param testOnly if true, detect collisions and print a plan without writing output
 * @param renumberSubjects if true, sort all subjects globally and assign new sequential IDs
 * @param digits number of digits for renumbered IDs (0 = auto-size)
 * @param m output message describing success or failure
 * @return true if successful
 */
bool modify::MergePackages(QStringList inputPaths, QString outputPath, bool testOnly, bool renumberSubjects, int digits, QString &m) {

    struct SubjectEntry {
        squirrelSubject subject;
        QString originalID;
        int srcIdx;
        qint64 srcSubjectRowID;
    };

    /* ---- 1. Open all input packages ---- */
    QList<squirrel *> inputs;
    for (const QString &path : inputPaths) {
        squirrel *sq = new squirrel();
        sq->SetFileMode(FileMode::ExistingPackage);
        sq->SetPackagePath(path);
        sq->SetQuickRead(false);
        sq->SetCommandLineExecution(true);
        if (!sq->Read()) {
            m = QString("Unable to read input package [%1]. Log: %2").arg(path).arg(sq->GetLog());
            qDeleteAll(inputs);
            delete sq;
            return false;
        }
        inputs.append(sq);
        utils::Print(QString("Opened package [%1]").arg(path));
    }

    if (inputs.isEmpty()) {
        m = "No input packages specified";
        return false;
    }

    /* ---- 2. Collect all subjects, detect collisions ---- */
    QList<SubjectEntry> allSubjects;
    QMap<QString, int> seenIDs;
    QStringList subjectCollisions;

    for (int i = 0; i < inputs.size(); ++i) {
        for (squirrelSubject subj : inputs[i]->GetSubjectList()) {
            const QString id = subj.ID;
            if (seenIDs.contains(id)) {
                subjectCollisions.append(
                    QString("  Subject [%1] exists in [%2] and [%3]")
                    .arg(id).arg(inputPaths[seenIDs[id]]).arg(inputPaths[i]));
            } else {
                seenIDs[id] = i;
            }
            allSubjects.append({subj, id, i, subj.GetObjectID()});
        }
    }

    if (!subjectCollisions.isEmpty() && !renumberSubjects) {
        m = QString("Subject ID collision(s) detected. Use --renumbersubjects to resolve:\n")
            + subjectCollisions.join("\n");
        qDeleteAll(inputs);
        return false;
    }

    /* ---- 3. Sort globally by original ID, then optionally renumber ---- */
    std::sort(allSubjects.begin(), allSubjects.end(), [](const SubjectEntry &a, const SubjectEntry &b) {
        return a.originalID < b.originalID;
    });

    if (renumberSubjects) {
        int n = allSubjects.size();
        int autoDigits = QString::number(n).length();
        if (digits <= 0 || digits < autoDigits)
            digits = autoDigits;
        for (int i = 0; i < n; ++i) {
            const QString oldID = allSubjects[i].originalID;
            const QString newID = QString("%1").arg(i + 1, digits, 10, QChar('0'));
            if (!allSubjects[i].subject.AlternateIDs.contains(oldID))
                allSubjects[i].subject.AlternateIDs.prepend(oldID);
            allSubjects[i].subject.ID = newID;
        }
    }

    /* ---- 4. Test mode: report plan and exit ---- */
    if (testOnly) {
        utils::Print("=== TEST MODE: no output will be written ===\n");
        if (!subjectCollisions.isEmpty()) {
            utils::Print(QString("Subject collisions (%1):").arg(subjectCollisions.size()));
            for (const QString &c : subjectCollisions)
                utils::Print(c);
        }
        utils::Print(QString("\nSubjects in merged output (%1):").arg(allSubjects.size()));
        for (const SubjectEntry &e : allSubjects)
            utils::Print(QString("  [%1]  (from %2, original ID [%3])").arg(e.subject.ID).arg(inputPaths[e.srcIdx]).arg(e.originalID));
        utils::Print("\nInput package summary:");
        for (int i = 0; i < inputs.size(); ++i) {
            utils::Print(QString("  [%1]").arg(inputPaths[i]));
            utils::Print(QString("    Subjects:%1  Studies:%2  Series:%3  Observations:%4  Interventions:%5")
                .arg(inputs[i]->GetObjectCount(Subject))
                .arg(inputs[i]->GetObjectCount(Study))
                .arg(inputs[i]->GetObjectCount(Series))
                .arg(inputs[i]->GetObjectCount(Observation))
                .arg(inputs[i]->GetObjectCount(Intervention)));
        }
        qDeleteAll(inputs);
        return true;
    }

    /* ---- 5. Create output package ---- */
    squirrel *out = new squirrel();
    out->SetFileMode(FileMode::NewPackage);
    out->SetPackagePath(outputPath);
    out->SetOverwritePackage(true);
    out->SetQuickRead(false);
    out->SetWriteLog(true);
    out->SetCommandLineExecution(true);
    out->DataFormat      = inputs[0]->DataFormat;
    out->SubjectDirFormat = inputs[0]->SubjectDirFormat;
    out->StudyDirFormat   = inputs[0]->StudyDirFormat;
    out->SeriesDirFormat  = inputs[0]->SeriesDirFormat;
    const QString outDbID = out->GetDatabaseUUID();

    QString tmpDir = QDir::tempPath() + "/squirrel-merge-" + utils::GenerateRandomString(10);
    QString tmpMsg;
    utils::MakePath(tmpDir, tmpMsg);

    QStringList expCollisions, pipeCollisions, gaCollisions, ddCollisions;
    QStringList studyCollisions, seriesCollisions, analysisCollisions, obsCollisions, intvCollisions;
    QStringList errors;

    /* ---- 6. Copy package-level objects from all inputs (first-wins) ---- */
    for (squirrel *src : inputs) {
        /* experiments */
        for (const squirrelExperiment &exp : src->GetExperimentList()) {
            if (out->FindExperiment(exp.ExperimentName) >= 0) {
                expCollisions.append(QString("  Experiment [%1] (first-package wins)").arg(exp.ExperimentName));
                continue;
            }
            squirrelExperiment newExp = exp;
            newExp.SetDatabaseUUID(outDbID);
            newExp.SetObjectID(-1);
            newExp.Store();
        }
        /* pipelines */
        for (const squirrelPipeline &pipe : src->GetPipelineList()) {
            if (out->FindPipeline(pipe.PipelineName, pipe.PipelineVersion) >= 0) {
                pipeCollisions.append(QString("  Pipeline [%1] v%2 (first-package wins)").arg(pipe.PipelineName).arg(pipe.PipelineVersion));
                continue;
            }
            squirrelPipeline newPipe = pipe;
            newPipe.SetDatabaseUUID(outDbID);
            newPipe.SetObjectID(-1);
            newPipe.Store();
        }
        /* group analyses */
        for (const squirrelGroupAnalysis &ga : src->GetGroupAnalysisList()) {
            if (out->FindGroupAnalysis(ga.GroupAnalysisName) >= 0) {
                gaCollisions.append(QString("  GroupAnalysis [%1] (first-package wins)").arg(ga.GroupAnalysisName));
                continue;
            }
            squirrelGroupAnalysis newGa = ga;
            newGa.SetDatabaseUUID(outDbID);
            newGa.SetObjectID(-1);
            newGa.Store();
        }
        /* data dictionaries */
        for (const squirrelDataDictionary &dd : src->GetDataDictionaryList()) {
            if (out->FindDataDictionary(dd.DataDictionaryName) >= 0) {
                ddCollisions.append(QString("  DataDictionary [%1] (first-package wins)").arg(dd.DataDictionaryName));
                continue;
            }
            squirrelDataDictionary newDd = dd;
            newDd.SetDatabaseUUID(outDbID);
            newDd.SetObjectID(-1);
            newDd.Store();
        }
    }

    /* ---- 7. Copy subjects and their children ---- */
    for (const SubjectEntry &entry : allSubjects) {
        squirrel *src = inputs[entry.srcIdx];

        squirrelSubject newSubj = entry.subject;
        newSubj.SetDatabaseUUID(outDbID);
        newSubj.SetObjectID(-1);
        newSubj.SetDirFormat(out->SubjectDirFormat);
        if (!newSubj.Store()) {
            errors.append(QString("Error storing subject [%1]").arg(newSubj.ID));
            continue;
        }
        const qint64 outSubjectRowID = newSubj.GetObjectID();

        /* studies */
        for (squirrelStudy study : src->GetStudyList(entry.srcSubjectRowID)) {
            if (out->FindStudy(newSubj.ID, study.StudyNumber) >= 0) {
                studyCollisions.append(QString("  Subject [%1] Study [%2] (first-package wins)").arg(newSubj.ID).arg(study.StudyNumber));
                continue;
            }
            squirrelStudy newStudy = study;
            newStudy.SetDatabaseUUID(outDbID);
            newStudy.SetObjectID(-1);
            newStudy.subjectRowID = outSubjectRowID;
            newStudy.SetDirFormat(out->SubjectDirFormat, out->StudyDirFormat);
            if (!newStudy.Store()) {
                errors.append(QString("Error storing Subject [%1] Study [%2]").arg(newSubj.ID).arg(study.StudyNumber));
                continue;
            }
            const qint64 outStudyRowID = newStudy.GetObjectID();

            /* series */
            for (squirrelSeries series : src->GetSeriesList(study.GetObjectID())) {
                if (out->FindSeries(newSubj.ID, study.StudyNumber, series.SeriesNumber) >= 0) {
                    seriesCollisions.append(QString("  Subject [%1] Study [%2] Series [%3] (first-package wins)").arg(newSubj.ID).arg(study.StudyNumber).arg(series.SeriesNumber));
                    continue;
                }
                squirrelSeries newSeries = series;
                newSeries.SetDatabaseUUID(outDbID);
                newSeries.SetObjectID(-1);
                newSeries.studyRowID = outStudyRowID;
                /* remap the experiment link by name (source rowID is meaningless in the output package) */
                newSeries.experimentRowID = -1;
                if (series.experimentRowID > 0) {
                    squirrelExperiment srcExp = src->GetExperiment(series.experimentRowID);
                    newSeries.experimentRowID = out->FindExperiment(srcExp.ExperimentName);
                }
                newSeries.stagedFiles.clear();
                newSeries.stagedBehFiles.clear();
                newSeries.SetDirFormat(out->SubjectDirFormat, out->StudyDirFormat, out->SeriesDirFormat);
                if (!newSeries.Store()) {
                    errors.append(QString("Error storing Subject [%1] Study [%2] Series [%3]").arg(newSubj.ID).arg(study.StudyNumber).arg(series.SeriesNumber));
                    continue;
                }
                const qint64 outSeriesRowID = newSeries.GetObjectID();

                /* extract files from source archive using the original subject ID */
                #ifdef Q_OS_WINDOWS
                    const QString archivePattern = QString("data\\%1\\%2\\%3\\*").arg(entry.originalID).arg(study.StudyNumber).arg(series.SeriesNumber);
                #else
                    const QString archivePattern = QString("data/%1/%2/%3/*").arg(entry.originalID).arg(study.StudyNumber).arg(series.SeriesNumber);
                #endif
                const QString extractDir = QString("%1/data/%2/%3/%4").arg(tmpDir).arg(entry.originalID).arg(study.StudyNumber).arg(series.SeriesNumber);
                QString extractMsg;
                utils::MakePath(extractDir, extractMsg);
                src->ExtractArchiveFilesToDirectory(src->GetPackagePath(), archivePattern, tmpDir, extractMsg);
                const QStringList extractedFiles = utils::FindAllFiles(extractDir, "*");
                if (!extractedFiles.isEmpty())
                    out->AddStagedFiles(Series, outSeriesRowID, extractedFiles);
            }

            /* analyses */
            for (squirrelAnalysis analysis : src->GetAnalysisList(study.GetObjectID())) {
                if (out->FindAnalysis(newSubj.ID, study.StudyNumber, analysis.AnalysisName) >= 0) {
                    analysisCollisions.append(QString("  Subject [%1] Study [%2] Analysis [%3] (first-package wins)").arg(newSubj.ID).arg(study.StudyNumber).arg(analysis.AnalysisName));
                    continue;
                }
                squirrelAnalysis newAnalysis = analysis;
                newAnalysis.SetDatabaseUUID(outDbID);
                newAnalysis.SetObjectID(-1);
                newAnalysis.studyRowID = outStudyRowID;
                newAnalysis.pipelineRowID = out->FindPipeline(analysis.PipelineName, analysis.PipelineVersion);
                newAnalysis.SetDirFormat(out->SubjectDirFormat, out->StudyDirFormat);
                newAnalysis.Store();
            }
        }

        /* observations */
        for (const squirrelObservation &obs : src->GetObservationList(entry.srcSubjectRowID)) {
            if (out->FindObservation(newSubj.ID, obs.ObservationName, obs.DateStart) >= 0) {
                obsCollisions.append(QString("  Subject [%1] Observation [%2] (first-package wins)").arg(newSubj.ID).arg(obs.ObservationName));
                continue;
            }
            squirrelObservation newObs = obs;
            newObs.SetDatabaseUUID(outDbID);
            newObs.SetObjectID(-1);
            newObs.subjectRowID = outSubjectRowID;
            newObs.Store();
        }

        /* interventions */
        for (const squirrelIntervention &intv : src->GetInterventionList(entry.srcSubjectRowID)) {
            if (out->FindIntervention(newSubj.ID, intv.InterventionName, intv.DateStart) >= 0) {
                intvCollisions.append(QString("  Subject [%1] Intervention [%2] (first-package wins)").arg(newSubj.ID).arg(intv.InterventionName));
                continue;
            }
            squirrelIntervention newIntv = intv;
            newIntv.SetDatabaseUUID(outDbID);
            newIntv.SetObjectID(-1);
            newIntv.subjectRowID = outSubjectRowID;
            newIntv.Store();
        }
    }

    /* ---- 8. Write output and cleanup ---- */
    bool writeOK = out->Write();
    utils::RemoveDir(tmpDir, tmpMsg);
    if (!writeOK) {
        m = QString("Failed to write output package [%1]. Log: %2").arg(outputPath).arg(out->GetLog());
        delete out;
        qDeleteAll(inputs);
        return false;
    }

    /* ---- 9. Print summary ---- */
    utils::Print("\n=== MERGE SUMMARY ===");
    utils::Print(QString("\nInput packages (%1):").arg(inputs.size()));
    for (int i = 0; i < inputs.size(); ++i) {
        utils::Print(QString("  [%1]").arg(inputPaths[i]));
        utils::Print(QString("    Subjects:%1  Studies:%2  Series:%3  Observations:%4  Interventions:%5  Experiments:%6  Pipelines:%7  GroupAnalyses:%8")
            .arg(inputs[i]->GetObjectCount(Subject))
            .arg(inputs[i]->GetObjectCount(Study))
            .arg(inputs[i]->GetObjectCount(Series))
            .arg(inputs[i]->GetObjectCount(Observation))
            .arg(inputs[i]->GetObjectCount(Intervention))
            .arg(inputs[i]->GetObjectCount(Experiment))
            .arg(inputs[i]->GetObjectCount(Pipeline))
            .arg(inputs[i]->GetObjectCount(GroupAnalysis)));
    }
    utils::Print(QString("\nOutput package [%1]:").arg(outputPath));
    utils::Print(QString("    Subjects:%1  Studies:%2  Series:%3  Observations:%4  Interventions:%5  Experiments:%6  Pipelines:%7  GroupAnalyses:%8")
        .arg(out->GetObjectCount(Subject))
        .arg(out->GetObjectCount(Study))
        .arg(out->GetObjectCount(Series))
        .arg(out->GetObjectCount(Observation))
        .arg(out->GetObjectCount(Intervention))
        .arg(out->GetObjectCount(Experiment))
        .arg(out->GetObjectCount(Pipeline))
        .arg(out->GetObjectCount(GroupAnalysis)));

    auto printList = [](const QString &label, const QStringList &lst) {
        if (!lst.isEmpty()) {
            utils::Print(QString("\n%1 (%2):").arg(label).arg(lst.size()));
            for (const QString &s : lst)
                utils::Print(s);
        }
    };
    printList("Subject collisions (resolved by renumbering)", subjectCollisions);
    printList("Study collisions", studyCollisions);
    printList("Series collisions", seriesCollisions);
    printList("Analysis collisions", analysisCollisions);
    printList("Observation collisions", obsCollisions);
    printList("Intervention collisions", intvCollisions);
    printList("Experiment collisions", expCollisions);
    printList("Pipeline collisions", pipeCollisions);
    printList("GroupAnalysis collisions", gaCollisions);
    printList("DataDictionary collisions", ddCollisions);
    printList("Errors", errors);

    qint64 peakMem = utils::GetPeakMemoryBytes();
    utils::Print(QString("\nPeak memory usage: %1").arg(peakMem > 0 ? utils::HumanReadableSize(peakMem) : QString("N/A")));

    delete out;
    qDeleteAll(inputs);
    return true;
}
