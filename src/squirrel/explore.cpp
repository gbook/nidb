/* ------------------------------------------------------------------------------
  Squirrel explore.cpp
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

/* ------------------------------------------------------------------------------
  Interactive 'squirrel explore' shell.

  The package is opened once via squirrel::Read(), which loads only the
  metadata manifest (squirrel.json) into an in-memory database - the large data
  files are NOT extracted. Every command below therefore just walks the
  already-loaded object lists, so navigation is instant.

  Commands (prototype):
    ls   [subjectID [studyNum]]            list subjects / studies / series
    info [subjectID [studyNum [seriesNum]]] show package/subject/study/series detail
    tree                                   interactive subject/study/series tree
    help                                   list commands
    quit | exit                            leave (Ctrl-D also works)

  Line editing (history, arrow keys, in-line editing) is provided by replxx.
  ------------------------------------------------------------------------------ */

#include <cstdio>
#include <string>
#include <vector>
#include <functional>
#include <algorithm>
#include <unistd.h>

#include <QString>
#include <QStringList>
#include <QRegularExpression>

#include "ftxui/component/component.hpp"
#include "ftxui/component/component_options.hpp"
#include "ftxui/component/screen_interactive.hpp"
#include "ftxui/dom/elements.hpp"

#include "explore.h"
#include "squirrel.h"
#include "squirrelSubject.h"
#include "squirrelStudy.h"
#include "squirrelSeries.h"

/* ----- small local helpers -------------------------------------------------- */
/* squirrelSubject/Study/Series have no default constructor (they need a database
   UUID), so rather than copy found objects out we return the index of the match
   within a caller-held list and let the caller reference it in place. */

static int IndexOfSubject(const QList<squirrelSubject> &subjects, const QString &id) {
    for (int i = 0; i < subjects.size(); ++i)
        if (subjects[i].ID.compare(id, Qt::CaseInsensitive) == 0)
            return i;
    return -1;
}

static int IndexOfStudy(const QList<squirrelStudy> &studies, int studyNum) {
    for (int i = 0; i < studies.size(); ++i)
        if (studies[i].StudyNumber == studyNum)
            return i;
    return -1;
}

static int IndexOfSeries(const QList<squirrelSeries> &series, qint64 seriesNum) {
    for (int i = 0; i < series.size(); ++i)
        if (series[i].SeriesNumber == seriesNum)
            return i;
    return -1;
}

static void PrintHelp() {
    printf("\n");
    printf("  Commands:\n");
    printf("    ls                              list all subjects\n");
    printf("    ls <subjectID>                  list studies for a subject\n");
    printf("    ls <subjectID> <studyNum>       list series for a study\n");
    printf("\n");
    printf("    info                            show package summary\n");
    printf("    info <subjectID>                show subject detail\n");
    printf("    info <subjectID> <studyNum>     show study detail\n");
    printf("    info <subjectID> <studyNum> <seriesNum>   show series detail\n");
    printf("\n");
    printf("    tree                            browse subjects/studies/series as an\n");
    printf("                                    expandable tree; Up/Down move, Enter\n");
    printf("                                    expands/collapses (or opens a series),\n");
    printf("                                    'i' shows details in a popup, 'q' returns\n");
    printf("\n");
    printf("    help                            show this help\n");
    printf("    quit | exit                     leave the shell (Ctrl-D works too)\n");
    printf("\n");
}

/* ----- ls ------------------------------------------------------------------- */

static void HandleLs(squirrel &sqrl, const QStringList &args) {
    /* ls -> subjects */
    if (args.isEmpty()) {
        QList<squirrelSubject> subjects = sqrl.GetSubjectList();
        if (subjects.isEmpty()) {
            printf("  (no subjects in package)\n");
            return;
        }
        printf("  %-20s %-6s %-16s %8s\n", "SubjectID", "Sex", "Group", "#Studies");
        printf("  %-20s %-6s %-16s %8s\n", "---------", "---", "-----", "--------");
        for (squirrelSubject &s : subjects) {
            int nStudies = sqrl.GetStudyList(s.GetObjectID()).size();
            printf("  %-20s %-6s %-16s %8d\n",
                   s.ID.toStdString().c_str(),
                   s.Sex.toStdString().c_str(),
                   s.EnrollmentGroup.toStdString().c_str(),
                   nStudies);
        }
        printf("\n  %d subject(s)\n", int(subjects.size()));
        return;
    }

    /* ls <subjectID> -> studies */
    QList<squirrelSubject> subjects = sqrl.GetSubjectList();
    int si = IndexOfSubject(subjects, args[0]);
    if (si < 0) {
        printf("  Subject '%s' not found. Try 'ls' to list subjects.\n", args[0].toStdString().c_str());
        return;
    }
    squirrelSubject &subj = subjects[si];

    if (args.size() == 1) {
        QList<squirrelStudy> studies = sqrl.GetStudyList(subj.GetObjectID());
        if (studies.isEmpty()) {
            printf("  (no studies for subject %s)\n", subj.ID.toStdString().c_str());
            return;
        }
        printf("  Studies for subject %s:\n", subj.ID.toStdString().c_str());
        printf("  %-8s %-10s %8s  %s\n", "StudyNum", "Modality", "#Series", "Description");
        printf("  %-8s %-10s %8s  %s\n", "--------", "--------", "-------", "-----------");
        for (squirrelStudy &st : studies) {
            int nSeries = sqrl.GetSeriesList(st.GetObjectID()).size();
            printf("  %-8d %-10s %8d  %s\n",
                   st.StudyNumber,
                   st.Modality.toStdString().c_str(),
                   nSeries,
                   st.Description.toStdString().c_str());
        }
        printf("\n  %d study(ies)\n", int(studies.size()));
        return;
    }

    /* ls <subjectID> <studyNum> -> series */
    bool ok = false;
    int studyNum = args[1].toInt(&ok);
    if (!ok) {
        printf("  Invalid study number '%s'.\n", args[1].toStdString().c_str());
        return;
    }
    QList<squirrelStudy> studies = sqrl.GetStudyList(subj.GetObjectID());
    int sti = IndexOfStudy(studies, studyNum);
    if (sti < 0) {
        printf("  Study %d not found for subject %s.\n", studyNum, subj.ID.toStdString().c_str());
        return;
    }
    squirrelStudy &study = studies[sti];
    const QList<squirrelSeries> series = sqrl.GetSeriesList(study.GetObjectID());
    if (series.isEmpty()) {
        printf("  (no series for subject %s study %d)\n", subj.ID.toStdString().c_str(), studyNum);
        return;
    }
    printf("  Series for subject %s, study %d:\n", subj.ID.toStdString().c_str(), studyNum);
    printf("  %-9s %-16s  %s\n", "SeriesNum", "Protocol", "Description");
    printf("  %-9s %-16s  %s\n", "---------", "--------", "-----------");
    for (const squirrelSeries &se : series) {
        printf("  %-9lld %-16s  %s\n",
               (long long)se.SeriesNumber,
               se.Protocol.toStdString().c_str(),
               se.Description.toStdString().c_str());
    }
    printf("\n  %d series\n", int(series.size()));
}

/* ----- info ----------------------------------------------------------------- */

static void HandleInfo(squirrel &sqrl, const QStringList &args) {
    /* info -> package summary (PrintPackage writes to stdout itself) */
    if (args.isEmpty()) {
        sqrl.PrintPackage();
        return;
    }

    QList<squirrelSubject> subjects = sqrl.GetSubjectList();
    int si = IndexOfSubject(subjects, args[0]);
    if (si < 0) {
        printf("  Subject '%s' not found. Try 'ls' to list subjects.\n", args[0].toStdString().c_str());
        return;
    }
    squirrelSubject &subj = subjects[si];

    /* info <subjectID> -> subject detail (PrintDetails writes to stdout) */
    if (args.size() == 1) {
        subj.PrintDetails();
        return;
    }

    bool ok = false;
    int studyNum = args[1].toInt(&ok);
    if (!ok) {
        printf("  Invalid study number '%s'.\n", args[1].toStdString().c_str());
        return;
    }
    QList<squirrelStudy> studies = sqrl.GetStudyList(subj.GetObjectID());
    int sti = IndexOfStudy(studies, studyNum);
    if (sti < 0) {
        printf("  Study %d not found for subject %s.\n", studyNum, subj.ID.toStdString().c_str());
        return;
    }
    squirrelStudy &study = studies[sti];

    /* info <subjectID> <studyNum> -> study detail */
    if (args.size() == 2) {
        printf("\n  ----- STUDY -----\n");
        printf("  Subject:     %s\n", subj.ID.toStdString().c_str());
        printf("  StudyNumber: %d\n", study.StudyNumber);
        printf("  Modality:    %s\n", study.Modality.toStdString().c_str());
        printf("  Description: %s\n", study.Description.toStdString().c_str());
        printf("  VisitType:   %s\n", study.VisitType.toStdString().c_str());
        printf("  AgeAtStudy:  %g\n", study.AgeAtStudy);
        printf("  #Series:     %d\n\n", int(sqrl.GetSeriesList(study.GetObjectID()).size()));
        return;
    }

    /* info <subjectID> <studyNum> <seriesNum> -> series detail */
    qint64 seriesNum = args[2].toLongLong(&ok);
    if (!ok) {
        printf("  Invalid series number '%s'.\n", args[2].toStdString().c_str());
        return;
    }
    QList<squirrelSeries> serieslist = sqrl.GetSeriesList(study.GetObjectID());
    int sei = IndexOfSeries(serieslist, seriesNum);
    if (sei < 0) {
        printf("  Series %lld not found for subject %s study %d.\n",
               (long long)seriesNum, subj.ID.toStdString().c_str(), studyNum);
        return;
    }
    squirrelSeries &series = serieslist[sei];
    printf("\n  ----- SERIES -----\n");
    printf("  Subject:      %s\n", subj.ID.toStdString().c_str());
    printf("  StudyNumber:  %d\n", study.StudyNumber);
    printf("  SeriesNumber: %lld\n", (long long)series.SeriesNumber);
    printf("  Protocol:     %s\n", series.Protocol.toStdString().c_str());
    printf("  Description:  %s\n", series.Description.toStdString().c_str());
    printf("  SeriesUID:    %s\n\n", series.SeriesUID.toStdString().c_str());
}

/* ----- stdout capture ------------------------------------------------------- */
/* The ls/info handlers above (and the library's PrintPackage/PrintDetails) write
   with printf. In the FTXUI full-screen UI we must not let that reach the real
   terminal, so we temporarily redirect fd 1 to a temp file, run the handler,
   then read the captured text back to append into the transcript pane. A temp
   file (not a pipe) avoids any buffer-full deadlock on large output. */
static std::string CaptureStdout(const std::function<void()> &fn) {
    fflush(stdout);
    FILE *tmp = tmpfile();
    if (!tmp) { fn(); return std::string(); }
    int saved = dup(STDOUT_FILENO);
    dup2(fileno(tmp), STDOUT_FILENO);

    fn();

    fflush(stdout);
    dup2(saved, STDOUT_FILENO);
    close(saved);

    fseek(tmp, 0, SEEK_END);
    long n = ftell(tmp);
    fseek(tmp, 0, SEEK_SET);
    std::string out;
    if (n > 0) {
        out.resize(size_t(n));
        size_t got = fread(&out[0], 1, size_t(n), tmp);
        out.resize(got);
    }
    fclose(tmp);
    return out;
}

/* ----- tree view -------------------------------------------------------------
   'tree' command data model: subjects/studies/series are read once into a
   local hierarchy (each node also carrying its own expand/collapse flag), then
   flattened into visible rows on every frame according to which nodes are
   currently expanded. ------------------------------------------------------- */

/* squirrelSubject/Study/Series have no default constructor (see the note at
   the top of this file), so these wrapper structs must always be built via
   the constructors below rather than default-constructed then assigned. */
/* Children (and the observation/intervention counts) are loaded lazily, on
   first expand/view, rather than all at once when 'tree' is typed. A package
   with hundreds of thousands of Observation rows makes fetching every
   subject's full child hierarchy up front far too slow; instead BuildTree()
   below only runs a single query (the subject list), and everything else is
   filled in - and cached - the first time a given row is actually rendered,
   expanded, or opened in the details popup. */
struct TreeStudyEntry {
    explicit TreeStudyEntry(const squirrelStudy &st) : study(st) {}
    squirrelStudy study;
    QList<squirrelSeries> seriesList;
    int numSeriesCached = -1; /* -1 = not yet known */
    bool seriesLoaded = false;
    bool expanded = false;
};
struct TreeSubjectEntry {
    explicit TreeSubjectEntry(const squirrelSubject &s) : subject(s) {}
    squirrelSubject subject;
    QList<TreeStudyEntry> studies;
    int numStudiesCached = -1;
    int numObservationsCached = -1;
    int numInterventionsCached = -1;
    bool studiesLoaded = false;
    bool expanded = false;
};

static std::vector<TreeSubjectEntry> BuildTree(squirrel &sqrl) {
    std::vector<TreeSubjectEntry> tree;
    QList<squirrelSubject> subjects = sqrl.GetSubjectList();
    tree.reserve(size_t(subjects.size()));
    for (squirrelSubject &s : subjects)
        tree.emplace_back(s);
    return tree;
}

/* Fetch a subject's studies (and cache the count) the first time they're
   needed; a no-op on every call after the first. */
static void EnsureStudiesLoaded(squirrel &sqrl, TreeSubjectEntry &subj) {
    if (subj.studiesLoaded) return;
    for (squirrelStudy &st : sqrl.GetStudyList(subj.subject.GetObjectID()))
        subj.studies.append(TreeStudyEntry(st));
    subj.studiesLoaded = true;
    subj.numStudiesCached = subj.studies.size();
}

/* Same idea for a study's series. */
static void EnsureSeriesLoaded(squirrel &sqrl, TreeStudyEntry &study) {
    if (study.seriesLoaded) return;
    study.seriesList = sqrl.GetSeriesList(study.study.GetObjectID());
    study.seriesLoaded = true;
    study.numSeriesCached = study.seriesList.size();
}

/* Child counts for a collapsed row's label: cheap "select count(*)" queries,
   cached after the first call, and skipped entirely once the real child list
   has been loaded (its size() is then already known for free). */
static int SubjectStudyCount(squirrel &sqrl, TreeSubjectEntry &subj) {
    if (subj.studiesLoaded) return subj.studies.size();
    if (subj.numStudiesCached < 0)
        subj.numStudiesCached = int(sqrl.CountStudies(subj.subject.GetObjectID()));
    return subj.numStudiesCached;
}

static int StudySeriesCount(squirrel &sqrl, TreeStudyEntry &study) {
    if (study.seriesLoaded) return study.seriesList.size();
    if (study.numSeriesCached < 0)
        study.numSeriesCached = int(sqrl.CountSeries(study.study.GetObjectID()));
    return study.numSeriesCached;
}

/* Observation/intervention counts are only ever shown in the subject details
   popup, so they're fetched (and cached) only when that popup is opened. */
static void EnsureSubjectCounts(squirrel &sqrl, TreeSubjectEntry &subj) {
    if (subj.numObservationsCached < 0)
        subj.numObservationsCached = int(sqrl.CountObservations(subj.subject.GetObjectID()));
    if (subj.numInterventionsCached < 0)
        subj.numInterventionsCached = int(sqrl.CountInterventions(subj.subject.GetObjectID()));
}

enum class TreeRowType { Subject, Study, Series };
struct TreeRow {
    TreeRowType type;
    int subjectIdx;
    int studyIdx = -1;  /* index within subject.studies, valid for Study/Series rows */
    int seriesIdx = -1; /* index within study.seriesList, valid for Series rows */
    int depth;
};

static bool TreeRowIsExpanded(std::vector<TreeSubjectEntry> &tree, const TreeRow &row) {
    if (row.type == TreeRowType::Subject)
        return tree[size_t(row.subjectIdx)].expanded;
    if (row.type == TreeRowType::Study)
        return tree[size_t(row.subjectIdx)].studies[row.studyIdx].expanded;
    return false;
}

/* Toggle expand state, lazily loading children the first time a node is
   opened; returns true if the row was a collapsible node. */
static bool TreeRowToggle(squirrel &sqrl, std::vector<TreeSubjectEntry> &tree, const TreeRow &row) {
    if (row.type == TreeRowType::Subject) {
        TreeSubjectEntry &subj = tree[size_t(row.subjectIdx)];
        if (!subj.studiesLoaded) EnsureStudiesLoaded(sqrl, subj);
        if (subj.studies.isEmpty()) return false;
        subj.expanded = !subj.expanded;
        return true;
    }
    if (row.type == TreeRowType::Study) {
        TreeStudyEntry &study = tree[size_t(row.subjectIdx)].studies[row.studyIdx];
        if (!study.seriesLoaded) EnsureSeriesLoaded(sqrl, study);
        if (study.seriesList.isEmpty()) return false;
        study.expanded = !study.expanded;
        return true;
    }
    return false;
}

/* The row 'delta' positions before/after 'row' among its siblings of the same
   type under the same parent (e.g. the next series within the same study).
   Clamped at the first/last sibling rather than wrapping. */
static TreeRow TreeRowSibling(std::vector<TreeSubjectEntry> &tree, const TreeRow &row, int delta) {
    if (row.type == TreeRowType::Subject) {
        int idx = std::min(std::max(row.subjectIdx + delta, 0), int(tree.size()) - 1);
        return TreeRow{TreeRowType::Subject, idx, -1, -1, 0};
    }
    if (row.type == TreeRowType::Study) {
        int n = int(tree[size_t(row.subjectIdx)].studies.size());
        int idx = std::min(std::max(row.studyIdx + delta, 0), n - 1);
        return TreeRow{TreeRowType::Study, row.subjectIdx, idx, -1, 1};
    }
    int n = int(tree[size_t(row.subjectIdx)].studies[row.studyIdx].seriesList.size());
    int idx = std::min(std::max(row.seriesIdx + delta, 0), n - 1);
    return TreeRow{TreeRowType::Series, row.subjectIdx, row.studyIdx, idx, 2};
}

static void FlattenTree(std::vector<TreeSubjectEntry> &tree, std::vector<TreeRow> &out) {
    out.clear();
    for (int si = 0; si < int(tree.size()); ++si) {
        TreeSubjectEntry &subj = tree[size_t(si)];
        out.push_back(TreeRow{TreeRowType::Subject, si, -1, -1, 0});
        if (!subj.expanded) continue;
        for (int sti = 0; sti < int(subj.studies.size()); ++sti) {
            TreeStudyEntry &study = subj.studies[sti];
            out.push_back(TreeRow{TreeRowType::Study, si, sti, -1, 1});
            if (!study.expanded) continue;
            for (int sei = 0; sei < int(study.seriesList.size()); ++sei)
                out.push_back(TreeRow{TreeRowType::Series, si, sti, sei, 2});
        }
    }
}

static std::string TreeRowLabel(squirrel &sqrl, std::vector<TreeSubjectEntry> &tree, const TreeRow &row) {
    char buf[256];
    TreeSubjectEntry &subj = tree[size_t(row.subjectIdx)];
    if (row.type == TreeRowType::Subject) {
        int nStudies = SubjectStudyCount(sqrl, subj);
        const char *mark = nStudies == 0 ? " " : (subj.expanded ? "-" : "+");
        snprintf(buf, sizeof(buf), "[%s] %s   (%s, %s, %d studies)", mark,
                 subj.subject.ID.toStdString().c_str(),
                 subj.subject.Sex.toStdString().c_str(),
                 subj.subject.EnrollmentGroup.toStdString().c_str(),
                 nStudies);
        return buf;
    }
    TreeStudyEntry &study = subj.studies[row.studyIdx];
    if (row.type == TreeRowType::Study) {
        int nSeries = StudySeriesCount(sqrl, study);
        const char *mark = nSeries == 0 ? " " : (study.expanded ? "-" : "+");
        snprintf(buf, sizeof(buf), "    [%s] Study %d   %s   %s   (%d series)", mark,
                 study.study.StudyNumber,
                 study.study.Modality.toStdString().c_str(),
                 study.study.Description.toStdString().c_str(),
                 nSeries);
        return buf;
    }
    const squirrelSeries &series = study.seriesList[row.seriesIdx];
    snprintf(buf, sizeof(buf), "        %lld   %s   %s", (long long)series.SeriesNumber,
             series.Protocol.toStdString().c_str(),
             series.Description.toStdString().c_str());
    return buf;
}

/* Strip a leading "----- WHATEVER -----" header line, if present: the modal
   already shows the object type in its own title bar, so the header inside
   squirrelSubject::PrintDetails()'s output (kept as-is for 'info') would just
   be redundant clutter here. */
static std::string StripHeaderLine(std::string s) {
    size_t nl = s.find('\n');
    if (nl != std::string::npos && s.substr(0, nl).find("-----") != std::string::npos)
        s.erase(0, nl + 1);
    return s;
}

/* Detail text for the popup, in the same shape as 'info's subject/study/series
   output (built directly here rather than via HandleInfo, since the tree keeps
   its own copies of the subject/study/series objects) but without the
   "----- OBJECT -----" header that 'info' prints - the popup's title bar
   already names the object type. */
static std::string TreeRowDetails(squirrel &sqrl, std::vector<TreeSubjectEntry> &tree, const TreeRow &row) {
    TreeSubjectEntry &subj = tree[size_t(row.subjectIdx)];
    if (row.type == TreeRowType::Subject) {
        EnsureSubjectCounts(sqrl, subj);
        std::string s = StripHeaderLine(CaptureStdout([&] { subj.subject.PrintDetails(); }));
        s += "Observations: " + std::to_string(subj.numObservationsCached) + "\n";
        s += "Interventions: " + std::to_string(subj.numInterventionsCached) + "\n";
        return s;
    }

    TreeStudyEntry &study = subj.studies[row.studyIdx];
    if (row.type == TreeRowType::Study) {
        std::string s;
        s += "Subject:     " + subj.subject.ID.toStdString() + "\n";
        s += "StudyNumber: " + std::to_string(study.study.StudyNumber) + "\n";
        s += "Modality:    " + study.study.Modality.toStdString() + "\n";
        s += "Description: " + study.study.Description.toStdString() + "\n";
        s += "VisitType:   " + study.study.VisitType.toStdString() + "\n";
        s += "AgeAtStudy:  " + std::to_string(study.study.AgeAtStudy) + "\n";
        s += "#Series:     " + std::to_string(StudySeriesCount(sqrl, study)) + "\n";
        return s;
    }

    const squirrelSeries &series = study.seriesList[row.seriesIdx];
    std::string s;
    s += "Subject:      " + subj.subject.ID.toStdString() + "\n";
    s += "StudyNumber:  " + std::to_string(study.study.StudyNumber) + "\n";
    s += "SeriesNumber: " + std::to_string(series.SeriesNumber) + "\n";
    s += "Protocol:     " + series.Protocol.toStdString() + "\n";
    s += "Description:  " + series.Description.toStdString() + "\n";
    s += "SeriesUID:    " + series.SeriesUID.toStdString() + "\n";
    return s;
}

/* ----- main loop (FTXUI full-screen UI) ------------------------------------- */

int explore::Run(const QString &packagePath) {
    using namespace ftxui;

    /* Open the package (metadata only). quiet=true so Read() does not spew the
       normal library log into the interactive session. */
    squirrel sqrl(false, true);
    sqrl.SetPackagePath(packagePath);
    sqrl.SetFileMode(FileMode::ExistingPackage);
    if (!sqrl.Read()) {
        fprintf(stderr, "Could not open squirrel package '%s'. Is it a valid package?\n",
                packagePath.toStdString().c_str());
        return 1;
    }

    const QString pkgName = sqrl.PackageName.isEmpty() ? QString("(unnamed)") : sqrl.PackageName;
    const int nSubjects = int(sqrl.GetSubjectList().size());

    /* transcript: the scrolling history of commands and their output */
    std::vector<std::string> transcript;
    auto addLine = [&](const std::string &block) {
        /* split into individual lines so the scroll pane measures height right */
        std::string cur;
        for (char ch : block) {
            if (ch == '\n') { transcript.push_back(cur); cur.clear(); }
            else if (ch != '\r') cur.push_back(ch);
        }
        if (!cur.empty()) transcript.push_back(cur);
    };
    addLine("Type 'help' for commands, 'quit' to exit.");
    addLine("");

    auto screen = ScreenInteractive::Fullscreen();

    /* Scrollback state. 'scroll' is how many lines we are scrolled UP from the
       bottom (0 == pinned to the newest line). 'paneBox'/'contentHeight' are
       filled in during render so the event handler can clamp and page. Any new
       output snaps back to the bottom (scroll = 0), like a normal shell. */
    int scroll = 0;
    int contentHeight = 0;
    int viewH = 0;       /* visible rows in the transcript pane (measured) */
    Box paneBox;

    /* 'tree' command state. The hierarchy is built lazily (on first use) and
       kept around for the life of the session so expand/collapse state and
       scroll position survive switching back and forth to the command shell. */
    enum class ViewMode { Command, Tree };
    ViewMode mode = ViewMode::Command;
    std::vector<TreeSubjectEntry> treeData;
    bool treeBuilt = false;
    std::vector<TreeRow> treeRows;
    int treeSelected = 0;
    int treeScroll = 0;
    int treeViewH = 0;
    Box treePaneBox;

    /* details popup, opened from the tree with 'i' (or Enter on a series) */
    bool showModal = false;
    std::string modalTitle;
    std::string modalBody;

    /* command history (recalled with plain Up/Down). histPos indexes cmdHistory;
       histPos == cmdHistory.size() means "editing a fresh line", whose partial
       text is stashed in 'draft' so Down can bring it back. */
    std::vector<std::string> cmdHistory;
    int histPos = 0;
    std::string draft;

    /* the command bar: a single-line Input at the bottom. cursorPos is bound to
       the Input so we can move the caret to end-of-line when recalling history. */
    std::string command;
    int cursorPos = 0;
    InputOption opt = InputOption::Default();
    opt.content = &command;
    opt.cursor_position = &cursorPos;
    opt.placeholder = "type a command  (help, ls, info, tree, quit)";
    opt.multiline = false;
    opt.on_enter = [&] {
        QString line = QString::fromUtf8(command.c_str()).trimmed();
        command.clear();
        cursorPos = 0;
        if (line.isEmpty())
            return;

        /* record in history (skip consecutive duplicates); reset the cursor */
        std::string cmdStr = line.toStdString();
        if (cmdHistory.empty() || cmdHistory.back() != cmdStr)
            cmdHistory.push_back(cmdStr);
        histPos = int(cmdHistory.size());
        draft.clear();

        scroll = 0; /* running a command snaps the view back to the bottom */
        addLine("> " + line.toStdString());

        QStringList tok = line.split(QRegularExpression("\\s+"), Qt::SkipEmptyParts);
        const QString cmd = tok.takeFirst().toLower();

        if (cmd == "quit" || cmd == "exit" || cmd == "q") {
            screen.Exit();
            return;
        }
        else if (cmd == "help" || cmd == "?") {
            addLine(CaptureStdout([&] { PrintHelp(); }));
        }
        else if (cmd == "ls") {
            addLine(CaptureStdout([&] { HandleLs(sqrl, tok); }));
        }
        else if (cmd == "info") {
            addLine(CaptureStdout([&] { HandleInfo(sqrl, tok); }));
        }
        else if (cmd == "tree") {
            if (!treeBuilt) {
                treeData = BuildTree(sqrl);
                treeBuilt = true;
            }
            mode = ViewMode::Tree;
        }
        else {
            addLine("  Unknown command '" + cmd.toStdString() +
                 "'. Type 'help' for the list of commands.");
        }
        addLine("");
    };
    auto input = Input(opt);

    /* compose: title / transcript (scrollable) / command box.

       The transcript is scrolled by explicit windowing: we measure the pane
       height (viewH) via reflect(), then render only the slice of lines the
       window should show. 'scroll' is the number of lines above the bottom;
       scroll == 0 shows the newest lines (pinned to bottom). This gives exact
       1:1 wheel/key scrolling and clamps cleanly at the first line. */
    auto renderer = Renderer(input, [&] {
        Element body;

        if (mode == ViewMode::Command) {
            contentHeight = int(transcript.size());
            if (viewH <= 0) viewH = 10;                 /* until first measured    */
            int maxScroll = std::max(0, contentHeight - viewH);
            if (scroll > maxScroll) scroll = maxScroll;
            if (scroll < 0) scroll = 0;

            int start = std::max(0, contentHeight - viewH - scroll);
            int end   = std::min(contentHeight, start + viewH);
            Elements lines;
            lines.reserve(size_t(end - start));
            for (int i = start; i < end; ++i)
                lines.push_back(text(transcript[size_t(i)]));

            Element pane = vbox(std::move(lines)) | flex | reflect(paneBox);
            viewH = paneBox.y_max - paneBox.y_min + 1;  /* for the next frame      */

            std::string title = " squirrel explore   " + pkgName.toStdString() +
                                "   (" + std::to_string(nSubjects) + " subjects) ";
            std::string hint = scroll > 0
                ? "  [scrolled up " + std::to_string(scroll) + "/" + std::to_string(maxScroll) +
                      " - wheel / Ctrl+Down / PageDown to return to bottom]"
                : "";

            body = vbox({
                       text(title) | bold | color(Color::CyanLight),
                       separator(),
                       pane,
                       separator(),
                       hbox({ text("> ") | color(Color::GreenLight), input->Render() | flex }) | border,
                       text(hint) | dim,
                   }) |
                   flex;
        }
        else {
            /* Tree mode: same windowed-scroll approach as the transcript pane
               above, but the window follows the selection instead of always
               pinning to the bottom. */
            FlattenTree(treeData, treeRows);
            if (treeRows.empty())
                treeSelected = 0;
            else
                treeSelected = std::min(std::max(treeSelected, 0), int(treeRows.size()) - 1);

            if (treeViewH <= 0) treeViewH = 10;
            int maxScroll = std::max(0, int(treeRows.size()) - treeViewH);
            if (treeSelected < treeScroll) treeScroll = treeSelected;
            if (treeSelected >= treeScroll + treeViewH) treeScroll = treeSelected - treeViewH + 1;
            treeScroll = std::min(std::max(treeScroll, 0), maxScroll);

            int start = treeScroll;
            int end   = std::min(int(treeRows.size()), start + treeViewH);
            Elements lines;
            lines.reserve(size_t(end - start));
            for (int i = start; i < end; ++i) {
                Element line = text(TreeRowLabel(sqrl, treeData, treeRows[size_t(i)]));
                if (i == treeSelected)
                    line = line | inverted;
                lines.push_back(line);
            }
            if (treeRows.empty())
                lines.push_back(text("  (no subjects in package)") | dim);

            Element pane = vbox(std::move(lines)) | flex | reflect(treePaneBox);
            treeViewH = treePaneBox.y_max - treePaneBox.y_min + 1;

            std::string title = " squirrel explore [tree]   " + pkgName.toStdString() +
                                "   (" + std::to_string(nSubjects) + " subjects) ";
            std::string hint = " Up/Down move   Enter expand/collapse (series: view details)"
                                "   i details   q back to shell ";

            body = vbox({
                       text(title) | bold | color(Color::CyanLight),
                       separator(),
                       pane,
                       separator(),
                       text(hint) | dim,
                   }) |
                   flex;
        }

        if (showModal) {
            Elements bodyLines;
            std::string cur;
            for (char ch : modalBody) {
                if (ch == '\n') { bodyLines.push_back(text(cur)); cur.clear(); }
                else if (ch != '\r') cur.push_back(ch);
            }
            if (!cur.empty()) bodyLines.push_back(text(cur));

            Element modal = vbox({
                                text(modalTitle) | bold | color(Color::YellowLight),
                                separator(),
                                vbox(std::move(bodyLines)),
                                separator(),
                                text("Press Enter / Esc / q to close") | dim,
                            }) |
                            border | bgcolor(Color::Black) | size(WIDTH, LESS_THAN, 90) | clear_under | center;
            return dbox({ body, modal });
        }

        return body;
    });

    /* Scroll handling wraps the whole component. Mouse wheel and Ctrl+Up/Down
       scroll a line (well, a few) at a time; PageUp/PageDown move by a visible
       page. Everything else falls through to the Input so typing still works. */
    auto root = CatchEvent(renderer, [&](Event e) {
        /* the details popup is modal: swallow every key until it's dismissed.
           Up/Down step to the previous/next sibling of the same type under the
           same parent (e.g. the next series in the same study) and refresh the
           popup in place; treeSelected moves with it, so the tree view lands on
           whichever row was last viewed once the popup closes. */
        if (showModal) {
            if (e == Event::Return || e == Event::Escape ||
                (e.is_character() && (e.character() == "q" || e.character() == "Q"))) {
                showModal = false;
            }
            else if ((e == Event::ArrowUp || e == Event::ArrowDown) && !treeRows.empty()) {
                const TreeRow &cur = treeRows[size_t(treeSelected)];
                TreeRow sib = TreeRowSibling(treeData, cur, e == Event::ArrowUp ? -1 : 1);
                for (int i = 0; i < int(treeRows.size()); ++i) {
                    const TreeRow &r = treeRows[size_t(i)];
                    if (r.type == sib.type && r.subjectIdx == sib.subjectIdx &&
                        r.studyIdx == sib.studyIdx && r.seriesIdx == sib.seriesIdx) {
                        treeSelected = i;
                        modalBody = TreeRowDetails(sqrl, treeData, sib);
                        break;
                    }
                }
            }
            return true;
        }

        if (mode == ViewMode::Tree) {
            const int page = treeViewH > 1 ? treeViewH - 1 : 1;

            if (e == Event::ArrowUp)   { if (treeSelected > 0) treeSelected--; return true; }
            if (e == Event::ArrowDown) { if (treeSelected + 1 < int(treeRows.size())) treeSelected++; return true; }
            if (e == Event::PageUp)    { treeSelected = std::max(0, treeSelected - page); return true; }
            if (e == Event::PageDown)  { treeSelected = std::min(int(treeRows.size()) - 1, treeSelected + page); return true; }
            if (e.is_mouse()) {
                /* the wheel moves the selection, not just the view: the renderer
                   scrolls to keep the selected row visible, so scrolling the view
                   alone would be undone on the next frame */
                if (e.mouse().button == Mouse::WheelUp)   { treeSelected = std::max(0, treeSelected - 3); return true; }
                if (e.mouse().button == Mouse::WheelDown) { treeSelected = std::min(int(treeRows.size()) - 1, treeSelected + 3); return true; }
                return true;
            }

            if (!treeRows.empty() && (e == Event::Return || e == Event::ArrowRight || e == Event::ArrowLeft)) {
                const TreeRow &row = treeRows[size_t(treeSelected)];
                if (row.type == TreeRowType::Series) {
                    /* leaves have nothing to expand: Enter/Right always show details */
                    if (e != Event::ArrowLeft) {
                        modalTitle = "Series detail";
                        modalBody = TreeRowDetails(sqrl, treeData, row);
                        showModal = true;
                    }
                }
                else if (e == Event::ArrowLeft) {
                    if (TreeRowIsExpanded(treeData, row))
                        TreeRowToggle(sqrl, treeData, row);
                }
                else {
                    TreeRowToggle(sqrl, treeData, row);
                }
                return true;
            }
            if (!treeRows.empty() && (e.is_character() && (e.character() == "i" || e.character() == "I"))) {
                const TreeRow &row = treeRows[size_t(treeSelected)];
                modalTitle = row.type == TreeRowType::Subject ? "Subject detail"
                           : row.type == TreeRowType::Study   ? "Study detail"
                                                               : "Series detail";
                modalBody = TreeRowDetails(sqrl, treeData, row);
                showModal = true;
                return true;
            }
            if (e.is_character() && (e.character() == "q" || e.character() == "Q")) {
                mode = ViewMode::Command;
                return true;
            }
            if (e == Event::Escape) {
                mode = ViewMode::Command;
                return true;
            }
            /* swallow everything else so keystrokes never leak into the (hidden)
               command input while browsing the tree */
            return true;
        }

        const int maxScroll = std::max(0, contentHeight - viewH);
        const int page = viewH > 1 ? viewH - 1 : 1;

        /* plain Up/Down: walk the command history (Ctrl+Up/Down scroll instead) */
        if (e == Event::ArrowUp) {
            if (histPos > 0) {
                if (histPos == int(cmdHistory.size()))
                    draft = command;            /* stash the in-progress line   */
                histPos--;
                command = cmdHistory[size_t(histPos)];
                cursorPos = int(command.size());
            }
            return true;
        }
        if (e == Event::ArrowDown) {
            if (histPos < int(cmdHistory.size())) {
                histPos++;
                command = (histPos == int(cmdHistory.size()))
                              ? draft
                              : cmdHistory[size_t(histPos)];
                cursorPos = int(command.size());
                return true;
            }
            return false;
        }

        if (e.is_mouse()) {
            if (e.mouse().button == Mouse::WheelUp)   { scroll = std::min(maxScroll, scroll + 3); return true; }
            if (e.mouse().button == Mouse::WheelDown) { scroll = std::max(0, scroll - 3);          return true; }
            return false;
        }
        if (e == Event::ArrowUpCtrl)   { scroll = std::min(maxScroll, scroll + 1);    return true; }
        if (e == Event::ArrowDownCtrl) { scroll = std::max(0, scroll - 1);            return true; }
        if (e == Event::PageUp)        { scroll = std::min(maxScroll, scroll + page); return true; }
        if (e == Event::PageDown)      { scroll = std::max(0, scroll - page);         return true; }
        return false;
    });

    screen.Loop(root);
    return 0;
}
