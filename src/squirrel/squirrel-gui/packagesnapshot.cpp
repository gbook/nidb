/* ------------------------------------------------------------------------------
  squirrel-gui packagesnapshot.cpp
  ------------------------------------------------------------------------------ */

#include "packagesnapshot.h"

#include <QDateTime>
#include <QFileInfo>
#include <QJsonArray>
#include <QJsonObject>
#include <QRegularExpression>
#include <algorithm>

#include "squirrel.h"

/* ------------------------------------------------------------ */
QString HumanSize(qint64 bytes) {
    const char *units[] = {"bytes", "KB", "MB", "GB", "TB"};
    double v = double(bytes);
    int u = 0;
    while (v >= 1024.0 && u < 4) {
        v /= 1024.0;
        u++;
    }
    if (u == 0)
        return QString("%1 bytes").arg(bytes);
    return QString("%1 %2").arg(v, 0, 'f', v < 10 ? 2 : (v < 100 ? 1 : 0)).arg(units[u]);
}

/* "DateOfBirth" -> "Date of birth", "SeriesUID" -> "Series UID" */
QString HumanizeKey(const QString &key) {
    QString s = key;
    s.replace(QRegularExpression("([a-z0-9])([A-Z])"), "\\1 \\2");
    s.replace(QRegularExpression("([A-Z]+)([A-Z][a-z])"), "\\1 \\2");
    s.replace('_', ' ');
    QStringList words = s.split(' ', Qt::SkipEmptyParts);
    for (int i = 1; i < words.size(); i++) {
        const QString &w = words[i];
        if (w.size() > 1 && w == w.toUpper())
            continue; /* acronym */
        words[i] = w.toLower();
    }
    if (!words.isEmpty() && words[0] == "Bids")
        words[0] = "BIDS";
    return words.join(' ');
}

QString PackageSnapshot::StatusSummary() const {
    return QString("%1 %2 · %3 %4 · %5 series")
        .arg(subjectCount).arg(subjectCount == 1 ? "subject" : "subjects")
        .arg(studyCount).arg(studyCount == 1 ? "study" : "studies")
        .arg(seriesCount);
}

namespace {

QString DateTimeText(const QDateTime &dt) {
    return dt.isValid() ? dt.toString("yyyy-MM-dd HH:mm:ss") : QString();
}

QString DateText(const QDateTime &dt) {
    return dt.isValid() ? dt.toString("yyyy-MM-dd") : QString();
}

QString CleanName(const QString &s) {
    QString out = s;
    out.replace(QRegularExpression("[^A-Za-z0-9._-]+"), "_");
    return out.isEmpty() ? QString("object") : out;
}

QString Plural(qint64 n, const QString &one, const QString &many) {
    return QString("%1 %2").arg(n).arg(n == 1 ? one : many);
}

/* scalar members of a ToJSON() object, in key order. Arrays of strings are
   joined; nested objects and arrays of objects (child lists) are skipped. */
PropList PropsFromJson(const QJsonObject &obj, const QStringList &skip = QStringList()) {
    PropList props;
    for (auto it = obj.begin(); it != obj.end(); ++it) {
        if (skip.contains(it.key()))
            continue;
        const QJsonValue v = it.value();
        QString text;
        if (v.isString())
            text = v.toString();
        else if (v.isBool())
            text = v.toBool() ? "yes" : "no";
        else if (v.isDouble()) {
            double d = v.toDouble();
            text = (d == qint64(d)) ? QString::number(qint64(d)) : QString::number(d, 'g', 6);
        }
        else if (v.isArray()) {
            QStringList parts;
            bool scalars = true;
            for (const QJsonValue &e : v.toArray()) {
                if (e.isObject() || e.isArray()) {
                    scalars = false;
                    break;
                }
                parts << e.toVariant().toString();
            }
            if (!scalars)
                continue;
            text = parts.join(", ");
        }
        else if (v.isNull())
            text = QString();
        else
            continue;
        props.append({HumanizeKey(it.key()), text});
    }
    return props;
}

/* put the most useful fields first, in this order; format byte counts */
PropList Arrange(PropList props, const QStringList &first) {
    PropList ordered;
    for (const QString &k : first)
        for (int i = 0; i < props.size(); i++)
            if (props[i].first == k) {
                ordered.append(props.takeAt(i));
                break;
            }
    ordered.append(props);
    for (auto &kv : ordered)
        if ((kv.first == "Size" || kv.first.endsWith(" size")) && !kv.second.isEmpty()) {
            bool ok = false;
            const qint64 n = kv.second.toLongLong(&ok);
            if (ok)
                kv.second = HumanSize(n);
        }
    return ordered;
}

struct Builder {
    squirrel *sqrl;
    PackageSnapshot *snap;

    int Add(PackageNode n, int parent) {
        n.parent = parent;
        if (parent >= 0)
            n.crumbs = snap->nodes[parent].crumbs;
        n.crumbs << n.label;
        snap->nodes.append(n);
        int idx = snap->nodes.size() - 1;
        if (parent >= 0)
            snap->nodes[parent].children.append(idx);
        return idx;
    }

    PackageNode Group(const QString &id, const QString &label, int count) {
        PackageNode g;
        g.kind = NodeKind::Group;
        g.groupId = id;
        g.label = label;
        g.meta = QString::number(count);
        g.title = label;
        g.subtitle = Plural(count, "item", "items");
        return g;
    }
};

} // namespace

/* ------------------------------------------------------------ */
SnapshotPtr BuildSnapshot(squirrel *sqrl) {
    auto *snap = new PackageSnapshot;
    Builder b{sqrl, snap};

    snap->path = sqrl->GetPackagePath();
    snap->name = sqrl->PackageName.isEmpty() ? QFileInfo(snap->path).completeBaseName() : sqrl->PackageName;
    snap->dataFormat = sqrl->DataFormat;
    snap->unzippedSize = sqrl->GetUnzipSize();

    /* ----- package root ----- */
    PackageNode root;
    root.kind = NodeKind::Package;
    root.label = snap->name;
    root.title = snap->name;
    root.props = {
        {"Package name", sqrl->PackageName},
        {"Description", sqrl->Description},
        {"Data format", sqrl->DataFormat},
        {"Created", DateTimeText(sqrl->Datetime)},
        {"Squirrel version", sqrl->SquirrelVersion},
        {"Squirrel build", sqrl->SquirrelBuild},
        {"Directory format", QString("%1, %2, %3").arg(sqrl->SubjectDirFormat, sqrl->StudyDirFormat, sqrl->SeriesDirFormat)},
        {"License", sqrl->License},
        {"NiDB version", sqrl->NiDBversion},
        {"File", snap->path},
        {"Files", QString::number(sqrl->GetFileCount())},
        {"Unzipped size", HumanSize(snap->unzippedSize)},
    };
    root.readme = sqrl->Readme;
    root.changes = sqrl->Changes;
    int rootIdx = b.Add(root, -1);

    /* ----- subjects / studies / series ----- */
    QList<squirrelSubject> subjects = sqrl->GetSubjectList();
    int subjectsIdx = b.Add(b.Group("subjects", "Subjects", subjects.size()), rootIdx);

    qint64 allSeries = 0, allStudies = 0, allObservations = 0, allInterventions = 0;
    QList<qint64> subjectRowIDs;
    QStringList subjectDirs;
    NodeTable subjectsTable;
    subjectsTable.title = "Subjects";
    subjectsTable.columns = QStringList{"Subject", "Sex", "Group", "Studies"};

    for (squirrelSubject &subj : subjects) {
        const qint64 subjRow = subj.GetObjectID();
        QList<squirrelStudy> studies = sqrl->GetStudyList(subjRow);
        std::sort(studies.begin(), studies.end(), [](const squirrelStudy &a, const squirrelStudy &b) { return a.StudyNumber < b.StudyNumber; });

        PackageNode sn;
        sn.kind = NodeKind::Subject;
        sn.rowID = subjRow;
        sn.label = subj.ID;
        sn.meta = QString("%1 · %2").arg(subj.Sex.isEmpty() ? "?" : subj.Sex, Plural(studies.size(), "study", "studies"));
        sn.title = subj.ID;
        sn.virtualPath = subj.VirtualPath();
        sn.props = Arrange(PropsFromJson(subj.ToJSON(), {"VirtualPath", "StudyCount"}),
                           {"Subject ID", "Alternate IDs", "Sex", "Gender", "Date of birth", "Enrollment group", "Enrollment status", "GUID"});
        int subjIdx = b.Add(sn, subjectsIdx);

        NodeTable studyTable;
        studyTable.title = "Studies";
        studyTable.columns = QStringList{"#", "Date", "Modality", "Description", "Series"};
        studyTable.emptyText = "This subject has no studies.";

        qint64 subjSeries = 0, subjSize = 0;
        for (squirrelStudy &study : studies) {
            const qint64 studyRow = study.GetObjectID();
            QList<squirrelSeries> seriesList = sqrl->GetSeriesList(studyRow);
            std::sort(seriesList.begin(), seriesList.end(), [](const squirrelSeries &a, const squirrelSeries &b) { return a.SeriesNumber < b.SeriesNumber; });

            PackageNode st;
            st.kind = NodeKind::Study;
            st.rowID = studyRow;
            st.label = QString("Study %1").arg(study.StudyNumber);
            st.meta = QString("%1 · %2").arg(study.Modality, DateText(study.DateTime));
            st.title = st.label;
            st.virtualPath = study.VirtualPath();
            st.props = Arrange(PropsFromJson(study.ToJSON(), {"VirtualPath", "SeriesCount"}),
                               {"Study number", "Description", "Modality", "Study datetime", "Equipment", "Visit type", "Age at study", "Height", "Weight", "Day number", "Time point", "Study UID"});
            int studyIdx = b.Add(st, subjIdx);

            NodeTable seriesTable;
            seriesTable.title = "Series";
            seriesTable.columns = QStringList{"#", "Description", "Files", "Size"};
            seriesTable.emptyText = "This study has no series.";

            qint64 studyFiles = 0, studySize = 0;
            for (squirrelSeries &ser : seriesList) {
                PackageNode se;
                se.kind = NodeKind::Series;
                se.rowID = ser.GetObjectID();
                se.label = QString("%1  %2").arg(ser.SeriesNumber).arg(ser.Description);
                se.meta = Plural(ser.FileCount, "file", "files");
                se.title = QString("Series %1  %2").arg(ser.SeriesNumber).arg(ser.Description);
                se.subtitle = QString("%1 · %2").arg(Plural(ser.FileCount, "file", "files"), HumanSize(ser.Size));
                se.virtualPath = ser.VirtualPath();
                se.props = Arrange(PropsFromJson(ser.ToJSON(), {"VirtualPath"}),
                                   {"Series number", "Description", "Protocol", "Series datetime", "Run", "File count", "Size", "Series UID"});
                QStringList keys = ser.params.keys();
                std::sort(keys.begin(), keys.end(), [](const QString &a, const QString &b) { return a.compare(b, Qt::CaseInsensitive) < 0; });
                for (const QString &k : keys)
                    se.params.append({k, ser.params.value(k)});
                se.fileCount = ser.FileCount;
                se.size = ser.Size;
                se.studyNode = studyIdx;
                se.extract.possible = true;
                se.extract.objectType = Series;
                se.extract.rowIDs = {se.rowID};
                se.extract.title = se.title;
                se.extract.detail = QString("%1 (%2) and series.json").arg(Plural(ser.FileCount, "file", "files"), HumanSize(ser.Size));
                se.extract.folderName = CleanName(QString("%1_%2_%3").arg(subj.ID).arg(study.StudyNumber).arg(ser.SeriesNumber));
                int seIdx = b.Add(se, studyIdx);

                seriesTable.rows.append({QString::number(ser.SeriesNumber), ser.Description, QString::number(ser.FileCount), HumanSize(ser.Size)});
                seriesTable.rowNodes.append(seIdx);
                studyFiles += ser.FileCount;
                studySize += ser.Size;
            }

            /* analyses */
            NodeTable analyses;
            QList<squirrelAnalysis> analysisList = sqrl->GetAnalysisList(studyRow);
            analyses.title = QString("Analyses (%1)").arg(analysisList.size());
            analyses.columns = QStringList{"Name", "Pipeline", "Status", "Started", "Files", "Size"};
            analyses.emptyText = "No analyses for this study.";
            for (squirrelAnalysis &an : analysisList) {
                analyses.rows.append({an.AnalysisName, QString("%1 v%2").arg(an.PipelineName).arg(an.PipelineVersion), an.Status,
                                      DateTimeText(an.DateStart), QString::number(an.FileCount), HumanSize(an.Size)});
                analyses.rowNodes.append(-1);
            }

            PackageNode &stRef = snap->nodes[studyIdx];
            stRef.subtitle = QString("%1 · %2 · %3").arg(study.Modality, DateText(study.DateTime), Plural(seriesList.size(), "series", "series"));
            stRef.childTable = seriesTable;
            stRef.extraTabs = {analyses};
            stRef.extract.possible = true;
            stRef.extract.objectType = Study;
            stRef.extract.rowIDs = {studyRow};
            stRef.extract.title = QString("Study %1 of %2").arg(study.StudyNumber).arg(subj.ID);
            stRef.extract.detail = QString("%1, %2 (%3) and study.json").arg(Plural(seriesList.size(), "series", "series"), Plural(studyFiles, "file", "files"), HumanSize(studySize));
            stRef.extract.folderName = CleanName(QString("%1_%2").arg(subj.ID).arg(study.StudyNumber));

            studyTable.rows.append({QString::number(study.StudyNumber), DateText(study.DateTime), study.Modality, study.Description, QString::number(seriesList.size())});
            studyTable.rowNodes.append(studyIdx);
            subjSeries += seriesList.size();
            subjSize += studySize;
            allSeries += seriesList.size();
        }
        allStudies += studies.size();

        /* observations and interventions */
        QList<squirrelObservation> obs = sqrl->GetObservationList(subjRow);
        NodeTable obsTable;
        obsTable.title = QString("Observations (%1)").arg(obs.size());
        obsTable.columns = QStringList{"Name", "Value", "Start", "Instrument", "Rater"};
        obsTable.emptyText = "No observations for this subject.";
        for (squirrelObservation &o : obs) {
            obsTable.rows.append({o.ObservationName, o.Value, DateTimeText(o.DateStart), o.InstrumentName, o.Rater});
            obsTable.rowNodes.append(-1);
        }
        QList<squirrelIntervention> ints = sqrl->GetInterventionList(subjRow);
        NodeTable intTable;
        intTable.title = QString("Interventions (%1)").arg(ints.size());
        intTable.columns = QStringList{"Name", "Start", "End", "Route", "Frequency"};
        intTable.emptyText = "No interventions for this subject.";
        for (squirrelIntervention &i : ints) {
            intTable.rows.append({i.InterventionName, DateTimeText(i.DateStart), DateTimeText(i.DateEnd), i.AdministrationRoute, i.DoseFrequency});
            intTable.rowNodes.append(-1);
        }
        allObservations += obs.size();
        allInterventions += ints.size();

        PackageNode &snRef = snap->nodes[subjIdx];
        snRef.subtitle = QString("%1 · %2 · %3").arg(subj.Sex, subj.EnrollmentGroup, Plural(studies.size(), "study", "studies"));
        snRef.childTable = studyTable;
        snRef.extraTabs = {obsTable, intTable};
        snRef.extract.possible = true;
        snRef.extract.objectType = Subject;
        snRef.extract.rowIDs = {subjRow};
        snRef.extract.title = QString("subject %1").arg(subj.ID);
        snRef.extract.detail = QString("%1, %2 (%3) and subject.json").arg(Plural(studies.size(), "study", "studies"), Plural(subjSeries, "series", "series"), HumanSize(subjSize));
        snRef.extract.folderName = CleanName(subj.ID);

        subjectsTable.rows.append({subj.ID, subj.Sex, subj.EnrollmentGroup, QString::number(studies.size())});
        subjectsTable.rowNodes.append(subjIdx);
        subjectRowIDs.append(subjRow);
        subjectDirs.append(CleanName(subj.ID));
    }
    snap->subjectCount = subjects.size();
    snap->studyCount = int(allStudies);
    snap->seriesCount = int(allSeries);
    {
        PackageNode &g = snap->nodes[subjectsIdx];
        g.childTable = subjectsTable;
        g.childTable.emptyText = "This package has no subjects.";
        g.extract.possible = !subjectRowIDs.isEmpty();
        g.extract.objectType = Subject;
        g.extract.rowIDs = subjectRowIDs;
        g.extract.subdirs = subjectDirs;
        g.extract.title = "all subjects";
        g.extract.detail = QString("%1, %2; one folder per subject, each with subject.json").arg(Plural(subjects.size(), "subject", "subjects"), Plural(allSeries, "series", "series"));
        g.extract.folderName = "subjects";
    }

    /* ----- experiments ----- */
    QList<squirrelExperiment> experiments = sqrl->GetExperimentList();
    int expIdx = b.Add(b.Group("experiments", "Experiments", experiments.size()), rootIdx);
    {
        NodeTable t;
        t.title = "Experiments";
        t.columns = QStringList{"Name", "Files", "Size"};
        t.emptyText = "This package has no experiments.";
        ExtractPlan plan;
        plan.objectType = Experiment;
        qint64 files = 0, size = 0;
        for (squirrelExperiment &e : experiments) {
            PackageNode n;
            n.kind = NodeKind::Experiment;
            n.rowID = e.GetObjectID();
            n.label = e.ExperimentName;
            n.meta = Plural(e.FileCount, "file", "files");
            n.title = e.ExperimentName;
            n.subtitle = QString("Experiment · %1 · %2").arg(Plural(e.FileCount, "file", "files"), HumanSize(e.Size));
            n.virtualPath = e.VirtualPath();
            n.props = Arrange(PropsFromJson(e.ToJSON(), {"VirtualPath"}), {"Experiment name"});
            n.extract.possible = true;
            n.extract.objectType = Experiment;
            n.extract.rowIDs = {n.rowID};
            n.extract.title = QString("experiment %1").arg(e.ExperimentName);
            n.extract.detail = QString("%1 (%2) and experiment.json").arg(Plural(e.FileCount, "file", "files"), HumanSize(e.Size));
            n.extract.folderName = CleanName(e.ExperimentName);
            int idx = b.Add(n, expIdx);
            t.rows.append({e.ExperimentName, QString::number(e.FileCount), HumanSize(e.Size)});
            t.rowNodes.append(idx);
            plan.rowIDs.append(n.rowID);
            plan.subdirs.append(CleanName(e.ExperimentName));
            files += e.FileCount;
            size += e.Size;
        }
        plan.possible = !plan.rowIDs.isEmpty();
        plan.title = "all experiments";
        plan.detail = QString("%1 (%2, %3), each with experiment.json").arg(Plural(experiments.size(), "experiment", "experiments"), Plural(files, "file", "files"), HumanSize(size));
        plan.folderName = "experiments";
        snap->nodes[expIdx].childTable = t;
        snap->nodes[expIdx].extract = plan;
    }

    /* ----- pipelines (squirrelPipeline::ToJSON() writes script files, so the
             properties are copied from the members directly) ----- */
    QList<squirrelPipeline> pipelines = sqrl->GetPipelineList();
    int pipeIdx = b.Add(b.Group("pipelines", "Pipelines", pipelines.size()), rootIdx);
    {
        NodeTable t;
        t.title = "Pipelines";
        t.columns = QStringList{"Name", "Version", "Description"};
        t.emptyText = "This package has no pipelines.";
        ExtractPlan plan;
        plan.objectType = Pipeline;
        for (squirrelPipeline &p : pipelines) {
            PackageNode n;
            n.kind = NodeKind::Pipeline;
            n.rowID = p.GetObjectID();
            n.label = p.PipelineName;
            n.meta = QString("v%1").arg(p.PipelineVersion);
            n.title = p.PipelineName;
            n.subtitle = QString("Pipeline · version %1").arg(p.PipelineVersion);
            n.virtualPath = p.VirtualPath();
            n.props = {
                {"Name", p.PipelineName}, {"Version", QString::number(p.PipelineVersion)},
                {"Description", p.PipelineDescription}, {"Created", DateTimeText(p.PipelineCreateDate)},
                {"Directory", p.PipelineDirectory}, {"Directory structure", p.PipelineDirectoryStructure},
                {"Cluster engine", p.ClusterEngine}, {"Cluster queue", p.ClusterQueue},
                {"Cluster submit host", p.ClusterSubmitHost}, {"Cluster user", p.ClusterUser},
                {"Notes", p.PipelineNotes},
            };
            n.extract.possible = true;
            n.extract.objectType = Pipeline;
            n.extract.rowIDs = {n.rowID};
            n.extract.title = QString("pipeline %1").arg(p.PipelineName);
            n.extract.detail = "the pipeline files and pipeline.json";
            n.extract.folderName = CleanName(p.PipelineName);
            int idx = b.Add(n, pipeIdx);
            t.rows.append({p.PipelineName, QString::number(p.PipelineVersion), p.PipelineDescription});
            t.rowNodes.append(idx);
            plan.rowIDs.append(n.rowID);
            plan.subdirs.append(CleanName(p.PipelineName));
        }
        plan.possible = !plan.rowIDs.isEmpty();
        plan.title = "all pipelines";
        plan.detail = QString("%1, each with pipeline.json").arg(Plural(pipelines.size(), "pipeline", "pipelines"));
        plan.folderName = "pipelines";
        snap->nodes[pipeIdx].childTable = t;
        snap->nodes[pipeIdx].extract = plan;
    }

    /* ----- group analyses ----- */
    QList<squirrelGroupAnalysis> groupAnalyses = sqrl->GetGroupAnalysisList();
    int gaIdx = b.Add(b.Group("groupanalyses", "Group analyses", groupAnalyses.size()), rootIdx);
    {
        NodeTable t;
        t.title = "Group analyses";
        t.columns = QStringList{"Name", "Date", "Files", "Size"};
        t.emptyText = "This package has no group analyses.";
        ExtractPlan plan;
        plan.objectType = GroupAnalysis;
        for (squirrelGroupAnalysis &g : groupAnalyses) {
            PackageNode n;
            n.kind = NodeKind::GroupAnalysis;
            n.rowID = g.GetObjectID();
            n.label = g.GroupAnalysisName;
            n.meta = Plural(g.FileCount, "file", "files");
            n.title = g.GroupAnalysisName;
            n.subtitle = QString("Group analysis · %1 · %2").arg(Plural(g.FileCount, "file", "files"), HumanSize(g.Size));
            n.virtualPath = g.VirtualPath();
            n.props = Arrange(PropsFromJson(g.ToJSON(), {"VirtualPath"}), {"Group analysis name", "Description"});
            n.extract.possible = true;
            n.extract.objectType = GroupAnalysis;
            n.extract.rowIDs = {n.rowID};
            n.extract.title = QString("group analysis %1").arg(g.GroupAnalysisName);
            n.extract.detail = QString("%1 (%2) and groupanalysis.json").arg(Plural(g.FileCount, "file", "files"), HumanSize(g.Size));
            n.extract.folderName = CleanName(g.GroupAnalysisName);
            int idx = b.Add(n, gaIdx);
            t.rows.append({g.GroupAnalysisName, DateTimeText(g.DateTime), QString::number(g.FileCount), HumanSize(g.Size)});
            t.rowNodes.append(idx);
            plan.rowIDs.append(n.rowID);
            plan.subdirs.append(CleanName(g.GroupAnalysisName));
        }
        plan.possible = !plan.rowIDs.isEmpty();
        plan.title = "all group analyses";
        plan.detail = QString("%1, each with groupanalysis.json").arg(Plural(groupAnalyses.size(), "group analysis", "group analyses"));
        plan.folderName = "groupanalyses";
        snap->nodes[gaIdx].childTable = t;
        snap->nodes[gaIdx].extract = plan;
    }

    /* ----- data dictionaries ----- */
    QList<squirrelDataDictionary> dicts = sqrl->GetDataDictionaryList();
    int ddIdx = b.Add(b.Group("datadictionaries", "Data dictionary", dicts.size()), rootIdx);
    {
        NodeTable t;
        t.title = "Data dictionaries";
        t.columns = QStringList{"Name", "Items", "Files", "Size"};
        t.emptyText = "This package has no data dictionary.";
        ExtractPlan plan;
        plan.objectType = DataDictionary;
        for (squirrelDataDictionary &d : dicts) {
            PackageNode n;
            n.kind = NodeKind::DataDictionary;
            n.rowID = d.GetObjectID();
            n.label = d.DataDictionaryName;
            n.meta = Plural(d.dictItems.size(), "item", "items");
            n.title = d.DataDictionaryName;
            n.subtitle = QString("Data dictionary · %1").arg(Plural(d.dictItems.size(), "item", "items"));
            n.virtualPath = d.VirtualPath();
            n.props = Arrange(PropsFromJson(d.ToJSON(), {"VirtualPath"}), {"Data dictionary name"});
            NodeTable items;
            items.title = "Variables";
            items.columns = QStringList{"Name", "Type", "Description", "Values"};
            items.emptyText = "No variables defined.";
            for (const auto &item : d.dictItems) {
                items.rows.append({item.VariableName, item.VariableType, item.VariableDescription, item.KeyValueMapping});
                items.rowNodes.append(-1);
            }
            n.childTable = items;
            n.extract.possible = true;
            n.extract.objectType = DataDictionary;
            n.extract.rowIDs = {n.rowID};
            n.extract.title = QString("data dictionary %1").arg(d.DataDictionaryName);
            n.extract.detail = QString("%1 and datadictionary.json").arg(Plural(d.FileCount, "file", "files"));
            n.extract.folderName = CleanName(d.DataDictionaryName);
            int idx = b.Add(n, ddIdx);
            t.rows.append({d.DataDictionaryName, QString::number(d.dictItems.size()), QString::number(d.FileCount), HumanSize(d.Size)});
            t.rowNodes.append(idx);
            plan.rowIDs.append(n.rowID);
            plan.subdirs.append(CleanName(d.DataDictionaryName));
        }
        plan.possible = !plan.rowIDs.isEmpty();
        plan.title = "the data dictionary";
        plan.detail = QString("%1, each with datadictionary.json").arg(Plural(dicts.size(), "data dictionary", "data dictionaries"));
        plan.folderName = "datadictionary";
        snap->nodes[ddIdx].childTable = t;
        snap->nodes[ddIdx].extract = plan;
    }

    /* ----- finish the root: contents table and whole-package extract ----- */
    {
        PackageNode &r = snap->nodes[rootIdx];
        r.subtitle = QString("squirrel package · %1 · %2").arg(sqrl->DataFormat, HumanSize(snap->unzippedSize));
        NodeTable t;
        t.title = "Contents";
        t.columns = QStringList{"Object", "Count"};
        auto row = [&](const QString &name, qint64 count, int node) {
            t.rows.append({name, QString::number(count)});
            t.rowNodes.append(node);
        };
        row("Subjects", subjects.size(), subjectsIdx);
        row("Studies", allStudies, subjectsIdx);
        row("Series", allSeries, subjectsIdx);
        row("Observations", allObservations, subjectsIdx);
        row("Interventions", allInterventions, subjectsIdx);
        row("Experiments", experiments.size(), expIdx);
        row("Pipelines", pipelines.size(), pipeIdx);
        row("Group analyses", groupAnalyses.size(), gaIdx);
        row("Data dictionaries", dicts.size(), ddIdx);
        r.childTable = t;
        r.extract.possible = true;
        r.extract.wholePackage = true;
        r.extract.title = "the whole package";
        r.extract.detail = QString("%1, %2 (%3) and squirrel.json").arg(Plural(subjects.size(), "subject", "subjects"), Plural(allSeries, "series", "series"), HumanSize(snap->unzippedSize));
        r.extract.folderName = CleanName(QFileInfo(snap->path).completeBaseName());
    }

    return SnapshotPtr(snap);
}
