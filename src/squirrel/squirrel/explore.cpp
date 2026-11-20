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

  Commands (see the CommandTable() below for the full list and options):
    ls   [subjectID [studyNum]]            list subjects / studies / series
    info [subjectID [studyNum [seriesNum]]] show package/subject/study/series detail
    info --object <type> [options]         same as 'squirrel info' on this package
    tree                                   interactive subject/study/series tree
    extract / modify / vars / validate     same as the matching CLI tools, run on
    merge / convert                        the open package (CLI option syntax)
    open / reload / clear / help / quit

  The CLI-tool commands take the same --options as the 'squirrel <tool>'
  command line, so anything learned in one place works in the other. Commands
  that can run for a long time (extract, modify, merge, convert, validate)
  temporarily leave the full-screen UI so their progress is shown live; their
  output is also captured into the transcript.
  ------------------------------------------------------------------------------ */

#include <cstdio>
#include <cstring>
#include <string>
#include <vector>
#include <functional>
#include <algorithm>
#include <memory>
#include <thread>
#ifdef _WIN32
/* MSVC has no unistd.h: dup, dup2, close, read, write and fileno are declared
   in io.h/stdio.h under their POSIX names; pipe, ssize_t and the descriptor
   constants are supplied here. */
#include <io.h>
#include <fcntl.h>
#include <BaseTsd.h>
typedef SSIZE_T ssize_t;
#ifndef STDOUT_FILENO
#define STDOUT_FILENO 1
#endif
#ifndef STDERR_FILENO
#define STDERR_FILENO 2
#endif
static int pipe(int fds[2]) { return _pipe(fds, 65536, _O_BINARY); }
#else
#include <unistd.h>
#endif

#include <QString>
#include <QStringList>
#include <QHash>
#include <QDir>
#include <QFileInfo>
#include <QRegularExpression>

#include "ftxui/component/component.hpp"
#include "ftxui/component/component_options.hpp"
#include "ftxui/component/screen_interactive.hpp"
#include "ftxui/dom/elements.hpp"

#include "explore.h"
#include "info.h"
#include "extract.h"
#include "modify.h"
#include "convert.h"
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

/* ----- command table -----------------------------------------------------------
   One entry per shell command. The option lists drive both 'help <command>' and
   argument validation, so an option only has to be described in one place. The
   option names deliberately match the 'squirrel <tool>' command line in main.cpp. */

struct OptSpec {
    const char *name;
    const char *valueName; /* nullptr = a flag that takes no value */
    const char *help;
};

struct CmdSpec {
    const char *name;
    std::vector<const char *> usage;   /* one or more usage lines */
    const char *summary;
    std::vector<OptSpec> opts;
    std::vector<const char *> examples;
};

static const std::vector<CmdSpec> &CommandTable() {
    static const std::vector<CmdSpec> cmds = {
        { "ls", { "ls [subjectID [studyNum]]" },
          "List subjects, a subject's studies, or a study's series.", {}, {} },

        { "info", { "info [subjectID [studyNum [seriesNum]]]", "info --object <type> [options]" },
          "Show package/subject/study/series detail, or list any object type like 'squirrel info'.",
          { { "object", "type", "package subject study series params observation intervention experiment\n"
                                 "analysis pipeline groupanalysis datadictionary datadictionaryitem" },
            { "subjectid", "id", "Only list objects for this subject" },
            { "studynum", "num", "Only list objects for this study (needs --subjectid)" },
            { "seriesnum", "num", "Only list params for this series (needs --subjectid, --studynum)" },
            { "dataset", "type", "id  basic  full (default: full)" },
            { "format", "fmt", "list  csv (default: list)" } },
          { "info --object observation --subjectid S1234",
            "info --object series --subjectid S1234 --studynum 1 --dataset basic --format csv" } },

        { "tree", { "tree" },
          "Browse subjects/studies/series as an expandable tree. Up/Down move, Enter\n"
          "expands/collapses (or opens a series), i shows details, x/u fill in an\n"
          "extract/update command for the selected row, q returns to the shell.", {}, {} },

        { "extract", { "extract --object <type> --objectid <id> --outdir <dir> [options]" },
          "Extract an object from the package to a directory (like 'squirrel extract').",
          { { "object", "type", "subject study series analysis observation intervention\n"
                                 "experiment pipeline groupanalysis datadictionary" },
            { "objectid", "id", "ID, name, or number of the object to extract" },
            { "outdir", "dir", "Output directory (created if needed)" },
            { "subjectid", "id", "Parent subject (study, series, analysis, observation, intervention)" },
            { "studynum", "num", "Parent study number (series, analysis)" },
            { "objectdate", "datetime", "DateStart of the observation/intervention to extract" } },
          { "extract --object subject --objectid S1234 --outdir ~/out",
            "extract --object series --subjectid S1234 --studynum 1 --objectid 3 --outdir ~/out" } },

        { "modify", { "modify --operation <op> [options]" },
          "Add, remove, or update objects in the package (like 'squirrel modify').\n"
          "The package is reloaded afterwards. remove, removephi and renumber ask\n"
          "for confirmation unless --yes is given.",
          { { "operation", "op", "add  remove  update  splitbymodality  removephi  renumber  embeddb" },
            { "object", "type", "package subject study series analysis intervention observation\n"
                                 "experiment pipeline groupanalysis datadictionary datadictionaryitem datastep" },
            { "objectid", "id", "Existing object ID, name, or number. For datadictionaryitem and\n"
                                "datastep, the parent data dictionary or pipeline name" },
            { "objectdate", "datetime", "DateStart of an existing observation/intervention (with --objectid)" },
            { "itemid", "id", "VariableName of a data dictionary item, or StepNumber of a data step" },
            { "subjectid", "id", "Parent subject ID" },
            { "studynum", "num", "Parent study number" },
            { "seriesnum", "num", "Series number (update a series)" },
            { "objectdata", "string", "key=value pairs separated by &, e.g. 'Sex=F&Gender=F'. Quote it.\n"
                                      "Use 'vars <object>' to list the possible keys" },
            { "datapath", "path", "Files to add: a directory, a single file, or a glob (e.g. /p/*.dcm)" },
            { "recursive", nullptr, "Search --datapath recursively" },
            { "digits", "num", "(renumber) digits in new subject IDs" },
            { "startnum", "num", "(renumber) starting number (default: 1)" },
            { "prefix", "string", "(renumber) prefix for new subject IDs" },
            { "random", nullptr, "(renumber) assign new IDs in random order" },
            { "yes", nullptr, "Do not ask for confirmation (also -y)" } },
          { "modify --operation update --object subject --objectid S1234 --objectdata 'Sex=F'",
            "modify --operation add --object series --subjectid S1234 --studynum 1 --objectdata 'SeriesNumber=3' --datapath /dicom/",
            "modify --operation remove --object study --subjectid S1234 --studynum 2" } },

        { "vars", { "vars <object>" },
          "List the variables that --objectdata accepts for an object type.", {},
          { "vars subject", "vars series" } },

        { "validate", { "validate" },
          "Re-read the package from disk and report whether it is valid.", {}, {} },

        { "merge", { "merge <package2> [package3 ...] --output <path> [options]" },
          "Merge the open package with one or more other packages (like 'squirrel merge').",
          { { "output", "path", "Output package path (required unless --test)" },
            { "test", nullptr, "Detect and display collisions without writing output" },
            { "renumbersubjects", nullptr, "Assign new sequential subject IDs to all subjects" },
            { "digits", "num", "Digits in renumbered subject IDs" } },
          { "merge ~/other.sqrl --output ~/merged.sqrl" } },

        { "convert", { "convert <inputdir> <outputpackage> --inputformat <bids|dicom> [options]" },
          "Convert a DICOM or BIDS directory into a new package (like 'squirrel convert').\n"
          "Use 'open <outputpackage>' afterwards to explore the result.",
          { { "inputformat", "format", "bids  dicom" },
            { "outputformat", "format", "squirrel (default)" },
            { "dataformat", "format", "anon  nifti4d  nifti4dgz (default)  nifti3d  nifti3dgz (DICOM only)" },
            { "dirformat", "format", "seq  orig (default)" },
            { "overwrite", nullptr, "Overwrite an existing output package" } },
          { "convert ~/dicom ~/new.sqrl --inputformat dicom --dataformat nifti4dgz" } },

        { "open", { "open <package>" }, "Close the current package and open another one.", {}, {} },
        { "reload", { "reload" }, "Re-read the current package from disk.", {}, {} },
        { "clear", { "clear" }, "Clear the transcript.", {}, {} },
        { "help", { "help [command]" }, "List commands, or show the options for one command.", {}, {} },
        { "quit", { "quit | exit" }, "Leave the shell.", {}, {} },
    };
    return cmds;
}

static const CmdSpec *FindCommand(const QString &name) {
    for (const CmdSpec &c : CommandTable())
        if (name == c.name)
            return &c;
    return nullptr;
}

/* print a possibly multi-line description, indenting continuation lines */
static void PrintWrapped(const char *firstPrefix, const std::string &indent, const char *textBlock) {
    QStringList lines = QString(textBlock).split('\n');
    for (int i = 0; i < lines.size(); ++i)
        printf("%s%s\n", i == 0 ? firstPrefix : indent.c_str(), lines[i].toStdString().c_str());
}

static void PrintHelp() {
    printf("\n  Commands:\n");
    for (const CmdSpec &c : CommandTable()) {
        /* long usage lines get the summary on the next line instead of overflowing */
        std::string firstLine = QString(c.summary).section('\n', 0, 0).toStdString();
        for (size_t i = 0; i < c.usage.size(); ++i) {
            const char *desc = (i == 0) ? firstLine.c_str() : "";
            if (strlen(c.usage[i]) > 40 && *desc) {
                printf("    %s\n", c.usage[i]);
                printf("    %-40s %s\n", "", desc);
            }
            else
                printf("    %-40s %s\n", c.usage[i], desc);
        }
    }
    printf("\n  Type 'help <command>' for a command's options and examples.\n");
    printf("  Values containing spaces or & must be quoted: --objectdata 'Sex=F&Gender=F'\n\n");
}

static void PrintCommandHelp(const CmdSpec &c) {
    printf("\n");
    for (const char *u : c.usage)
        printf("  %s\n", u);
    printf("\n");
    PrintWrapped("  ", "  ", c.summary);
    if (!c.opts.empty()) {
        printf("\n  Options:\n");
        for (const OptSpec &o : c.opts) {
            std::string lhs = std::string("--") + o.name + (o.valueName ? std::string(" <") + o.valueName + ">" : "");
            char prefix[64];
            snprintf(prefix, sizeof(prefix), "    %-24s ", lhs.c_str());
            PrintWrapped(prefix, std::string(29, ' '), o.help);
        }
    }
    if (!c.examples.empty()) {
        printf("\n  Examples:\n");
        for (const char *e : c.examples)
            printf("    %s\n", e);
    }
    printf("\n");
}

/* ----- command line parsing ---------------------------------------------------
   The shell has no real shell underneath it, so quoting is handled here:
   whitespace separates words, '...' is taken literally, "..." allows \" and \\
   escapes, and a backslash outside quotes escapes the next character. A quoted
   empty string ('') yields an empty word (needed for --objectdata ''). */

static bool Tokenize(const QString &line, QStringList &out, QString &err) {
    out.clear();
    QString cur;
    bool inWord = false;
    for (int i = 0; i < line.size(); ++i) {
        QChar ch = line[i];
        if (ch.isSpace()) {
            if (inWord) { out << cur; cur.clear(); inWord = false; }
        }
        else if (ch == '\'') {
            inWord = true;
            int end = line.indexOf('\'', i + 1);
            if (end < 0) { err = "Unterminated ' quote"; return false; }
            cur += line.mid(i + 1, end - i - 1);
            i = end;
        }
        else if (ch == '"') {
            inWord = true;
            int j = i + 1;
            for (; j < line.size() && line[j] != '"'; ++j) {
                if (line[j] == '\\' && j + 1 < line.size() && (line[j + 1] == '"' || line[j + 1] == '\\'))
                    ++j;
                cur += line[j];
            }
            if (j >= line.size()) { err = "Unterminated \" quote"; return false; }
            i = j;
        }
        else if (ch == '\\' && i + 1 < line.size()) {
            inWord = true;
            cur += line[++i];
        }
        else {
            inWord = true;
            cur += ch;
        }
    }
    if (inWord) out << cur;
    return true;
}

/* Quote a value so Tokenize() gives it back unchanged (used when the tree fills
   in a command line from the selected row). */
static QString QuoteArg(const QString &s) {
    static const QRegularExpression needsQuoting("[\\s'\"\\\\&]");
    if (!s.isEmpty() && !s.contains(needsQuoting))
        return s;
    QString q = s;
    q.replace("'", "'\\''");
    return "'" + q + "'";
}

struct ParsedArgs {
    QStringList pos;
    QHash<QString, QString> opts; /* flags are stored with the value "1" */
    bool has(const QString &k) const { return opts.contains(k); }
    QString value(const QString &k) const { return opts.value(k).trimmed(); }
};

/* Split the words after the command name into positional arguments and
   options, accepting --name value, --name=value and (for convenience) a single
   leading dash. Unknown options are an error rather than silently ignored. */
static bool ParseArgs(const CmdSpec &spec, const QStringList &words, ParsedArgs &out, QString &err) {
    bool optionsDone = false;
    for (int i = 0; i < words.size(); ++i) {
        const QString &w = words[i];
        if (!optionsDone && w == "--") { optionsDone = true; continue; }
        if (optionsDone || !w.startsWith('-') || w.size() < 2) {
            out.pos << w;
            continue;
        }
        QString name = w.mid(w.startsWith("--") ? 2 : 1);
        QString val;
        bool inlineVal = false;
        int eq = name.indexOf('=');
        if (eq >= 0) { val = name.mid(eq + 1); name = name.left(eq); inlineVal = true; }
        name = name.toLower();
        if (name == "y") name = "yes";

        const OptSpec *opt = nullptr;
        for (const OptSpec &o : spec.opts)
            if (name == o.name) { opt = &o; break; }
        if (!opt) {
            err = QString("Unknown option '--%1' for '%2'. Type 'help %2' for its options.").arg(name, spec.name);
            return false;
        }
        if (!opt->valueName) {
            if (inlineVal) { err = QString("Option '--%1' does not take a value").arg(name); return false; }
            out.opts[name] = "1";
            continue;
        }
        if (!inlineVal) {
            if (i + 1 >= words.size()) {
                err = QString("Option '--%1' needs a value <%2>").arg(name, opt->valueName);
                return false;
            }
            val = words[++i];
        }
        out.opts[name] = val;
    }
    return true;
}

/* Read an integer option. Absent -> 'def'. Present but not a number -> error. */
static bool IntOpt(const ParsedArgs &a, const QString &name, int def, int &out, QString &err) {
    out = def;
    if (!a.has(name)) return true;
    bool ok = false;
    out = a.value(name).toInt(&ok);
    if (!ok) { err = QString("--%1 must be a number, not '%2'").arg(name, a.value(name)); return false; }
    return true;
}

/* There is no shell to expand '~', so do the common case here */
static QString ExpandPath(const QString &p) {
    if (p == "~") return QDir::homePath();
    if (p.startsWith("~/")) return QDir::homePath() + p.mid(1);
    return p;
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
   terminal, so we temporarily redirect fd 1 (and fd 2, so Qt/library warnings
   can't scribble over the UI either) to a temp file, run the handler, then read
   the captured text back to append into the transcript pane. A temp file (not
   a pipe) avoids any buffer-full deadlock on large output. */
static std::string CaptureStdout(const std::function<void()> &fn) {
    fflush(stdout);
    fflush(stderr);
    FILE *tmp = tmpfile();
    if (!tmp) { fn(); return std::string(); }
    int savedOut = dup(STDOUT_FILENO);
    int savedErr = dup(STDERR_FILENO);
    dup2(fileno(tmp), STDOUT_FILENO);
    dup2(fileno(tmp), STDERR_FILENO);

    fn();

    fflush(stdout);
    fflush(stderr);
    dup2(savedOut, STDOUT_FILENO);
    dup2(savedErr, STDERR_FILENO);
    close(savedOut);
    close(savedErr);

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

/* Like CaptureStdout(), but for long-running work done while the full-screen UI
   is suspended (see RunLive in explore::Run): stdout/stderr are routed through a
   pipe, and a reader thread copies everything both to the real terminal (so
   progress is visible as it happens) and into the returned string (so it also
   lands in the transcript). */
static std::string TeeOutput(const std::function<void()> &fn) {
    fflush(stdout);
    fflush(stderr);
    int pfd[2];
    if (pipe(pfd) != 0) { fn(); return std::string(); }
    int savedOut = dup(STDOUT_FILENO);
    int savedErr = dup(STDERR_FILENO);
    dup2(pfd[1], STDOUT_FILENO);
    dup2(pfd[1], STDERR_FILENO);
    close(pfd[1]); /* fds 1 and 2 now hold the only write ends */

    std::string out;
    std::thread reader([&] {
        char buf[4096];
        ssize_t n;
        while ((n = read(pfd[0], buf, sizeof(buf))) > 0) {
            ssize_t off = 0;
            while (off < n) {
                ssize_t w = write(savedOut, buf + off, size_t(n - off));
                if (w <= 0) break;
                off += w;
            }
            out.append(buf, size_t(n));
        }
    });

    fn();

    fflush(stdout);
    fflush(stderr);
    /* restoring fds 1/2 closes the last write ends, so the reader sees EOF */
    dup2(savedOut, STDOUT_FILENO);
    dup2(savedErr, STDERR_FILENO);
    reader.join();
    close(pfd[0]);
    close(savedOut);
    close(savedErr);
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

    /* The open package. Held by pointer so 'open', 'reload' and a successful
       'modify' can swap in a freshly read instance. */
    std::unique_ptr<squirrel> sqrl;
    QString pkgPath;   /* absolute path of the open package */
    QString pkgName;
    int nSubjects = 0;

    /* 'tree' command state. The hierarchy is built lazily (on first use) and
       kept around for the life of the session so expand/collapse state and
       scroll position survive switching back and forth to the command shell.
       Opening/reloading a package resets it. */
    enum class ViewMode { Command, Tree };
    ViewMode mode = ViewMode::Command;
    std::vector<TreeSubjectEntry> treeData;
    bool treeBuilt = false;
    std::vector<TreeRow> treeRows;
    int treeSelected = 0;
    int treeScroll = 0;
    int treeViewH = 0;
    Box treePaneBox;

    /* Read a package (metadata only; quiet so Read() does not spew the normal
       library log) and, on success, make it the current one. On failure the
       current package, if any, stays open. Library output is returned in log. */
    auto openPackage = [&](const QString &path, QString &err, std::string &log) -> bool {
        const QString abs = QFileInfo(path).absoluteFilePath();
        if (!QFileInfo::exists(abs)) {
            err = QString("Package '%1' does not exist").arg(path);
            return false;
        }
        auto s = std::make_unique<squirrel>(false, true);
        s->SetPackagePath(abs);
        s->SetFileMode(FileMode::ExistingPackage);
        bool ok = false;
        log = CaptureStdout([&] { ok = s->Read(); });
        if (!ok) {
            err = QString("Could not open squirrel package '%1'. Is it a valid package?").arg(path);
            return false;
        }
        sqrl = std::move(s);
        pkgPath = abs;
        pkgName = sqrl->PackageName.isEmpty() ? QString("(unnamed)") : sqrl->PackageName;
        nSubjects = int(sqrl->GetSubjectList().size());
        treeData.clear();
        treeRows.clear();
        treeBuilt = false;
        treeSelected = 0;
        treeScroll = 0;
        return true;
    };

    {
        QString err;
        std::string log;
        if (!openPackage(packagePath, err, log)) {
            if (!log.empty())
                fprintf(stderr, "%s", log.c_str());
            fprintf(stderr, "%s\n", err.toStdString().c_str());
            return 1;
        }
    }

    /* transcript: the scrolling history of commands and their output */
    std::vector<std::string> transcript;
    auto addLine = [&](const std::string &block) {
        /* split into individual lines so the scroll pane measures height right.
           A bare '\r' (progress bars redrawing in place) restarts the line, as
           it would on a terminal, so only the final state is kept. */
        std::string cur;
        for (size_t i = 0; i < block.size(); ++i) {
            char ch = block[i];
            if (ch == '\n') { transcript.push_back(cur); cur.clear(); }
            else if (ch == '\r') { if (i + 1 < block.size() && block[i + 1] != '\n') cur.clear(); }
            else cur.push_back(ch);
        }
        if (!cur.empty()) transcript.push_back(cur);
    };
    auto addMsg = [&](const QString &m) { addLine("  " + m.toStdString()); };
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

    /* details popup, opened from the tree with 'i' (or Enter on a series) */
    bool showModal = false;
    std::string modalTitle;
    std::string modalBody;

    /* A destructive modify waiting for the user to type 'yes'. Only names that
       live as long as Run() itself may be captured by reference in it. */
    std::function<void()> pendingAction;

    /* Run long work with the full-screen UI suspended, so progress is printed
       live on the normal terminal; the output is also captured into the
       transcript. Returns to the full-screen UI when fn finishes. */
    auto runLive = [&](const std::string &banner, const std::function<void()> &fn) {
        std::string out;
        screen.WithRestoredIO([&] {
            printf("\n----- squirrel explore: %s -----\n", banner.c_str());
            fflush(stdout);
            out = TeeOutput(fn);
        })();
        addLine(out);
    };

    /* Re-read the current package after a command changed it on disk. Called
       from inside runLive(), so it reports by printing. */
    auto reloadCurrent = [&] {
        printf("Reloading package...\n");
        const QString path = pkgPath;
        QString err;
        std::string log;
        if (openPackage(path, err, log))
            printf("Reloaded package (%d subjects)\n", nSubjects);
        else
            printf("%s%s The previously loaded copy is still open and may be out of date.\n",
                   log.c_str(), err.toStdString().c_str());
    };

    /* ----- command dispatch --------------------------------------------------- */
    auto runCommand = [&](const QString &line) {
        QStringList words;
        QString err;
        if (!Tokenize(line, words, err)) { addMsg(err); return; }
        if (words.isEmpty()) return;
        QString cmd = words.takeFirst().toLower();
        if (cmd == "exit" || cmd == "q") cmd = "quit";
        if (cmd == "?") cmd = "help";

        const CmdSpec *spec = FindCommand(cmd);
        if (!spec) {
            addMsg("Unknown command '" + cmd + "'. Type 'help' for the list of commands.");
            return;
        }

        /* commands with their own positional syntax */
        if (cmd == "quit") { screen.Exit(); return; }
        if (cmd == "help") {
            if (words.isEmpty()) { addLine(CaptureStdout([&] { PrintHelp(); })); return; }
            const CmdSpec *c = FindCommand(words[0].toLower());
            if (c) addLine(CaptureStdout([&] { PrintCommandHelp(*c); }));
            else addMsg("Unknown command '" + words[0] + "'. Type 'help' for the list of commands.");
            return;
        }
        if (cmd == "ls") { addLine(CaptureStdout([&] { HandleLs(*sqrl, words); })); return; }
        if (cmd == "info" && std::none_of(words.begin(), words.end(), [](const QString &w) { return w.startsWith('-'); })) {
            addLine(CaptureStdout([&] { HandleInfo(*sqrl, words); }));
            return;
        }
        if (cmd == "tree") {
            if (!treeBuilt) {
                treeData = BuildTree(*sqrl);
                treeBuilt = true;
            }
            mode = ViewMode::Tree;
            return;
        }

        /* everything else takes CLI-style --options */
        ParsedArgs a;
        if (!ParseArgs(*spec, words, a, err)) { addMsg(err); return; }

        if (cmd == "info") {
            if (!a.pos.isEmpty()) {
                addMsg("Use either 'info <subjectID> ...' or 'info --object ...', not both.");
                return;
            }
            infoQuery q;
            q.debug = false;
            const QString object = a.value("object");
            q.object = object.isEmpty() ? Package : squirrel::ObjectTypeToEnum(object);
            if ((q.object == UnknownObjectType) || (q.object == BehSeries)) {
                addMsg(QString("Unknown --object [%1]. Type 'help info' for the valid types.").arg(object));
                return;
            }
            q.subjectID = a.value("subjectid");
            if (!IntOpt(a, "studynum", 0, q.studyNum, err) || !IntOpt(a, "seriesnum", 0, q.seriesNum, err)) { addMsg(err); return; }

            const QString dataset = a.value("dataset").toLower();
            if ((dataset == "") || (dataset == "full")) q.dataset = DatasetFull;
            else if (dataset == "id") q.dataset = DatasetID;
            else if (dataset == "basic") q.dataset = DatasetBasic;
            else { addMsg(QString("Unknown --dataset [%1]. Valid values: id, basic, full").arg(dataset)); return; }

            const QString format = a.value("format").toLower();
            if ((format == "") || (format == "list")) q.printFormat = List;
            else if (format == "csv") q.printFormat = CSV;
            else { addMsg(QString("Unknown --format [%1]. Valid values: list, csv").arg(format)); return; }

            bool ok = false;
            QString m;
            if (q.object == Params) {
                /* params are skipped by the quick read the shell uses, so this
                   one re-reads the whole package from disk, which can be slow */
                runLive("info --object params", [&] { info inf; ok = inf.DisplayInfo(pkgPath, q, m); });
            }
            else {
                addLine(CaptureStdout([&] { info inf; ok = inf.DisplayInfo(sqrl.get(), q, m); }));
            }
            if (!ok) addMsg(m);
        }
        else if (cmd == "extract") {
            for (const char *req : { "object", "objectid", "outdir" })
                if (a.value(req).isEmpty()) { addMsg(QString("Missing --%1. Type 'help extract' for usage.").arg(req)); return; }
            int studyNum;
            if (!IntOpt(a, "studynum", 0, studyNum, err)) { addMsg(err); return; }
            const QString outdir = ExpandPath(a.value("outdir"));
            bool ok = false;
            QString m;
            runLive("extract", [&] {
                /* the shell's package is opened quiet; let the extract report progress */
                bool wasQuiet = sqrl->quiet;
                sqrl->quiet = false;
                extract ext;
                ok = ext.DoExtract(sqrl.get(), outdir, a.value("object"), a.value("objectid"), a.value("subjectid"), studyNum, a.value("objectdate"), false, m);
                sqrl->quiet = wasQuiet;
            });
            if (!ok) addMsg("extract failed: " + m);
        }
        else if (cmd == "modify") {
            static const QStringList ops = { "add", "remove", "update", "splitbymodality", "removephi", "renumber", "embeddb" };
            const QString op = a.value("operation").toLower();
            if (op.isEmpty()) { addMsg("Missing --operation. Type 'help modify' for usage."); return; }
            if (!ops.contains(op)) { addMsg(QString("Unknown --operation [%1]. Valid values: %2").arg(op, ops.join(", "))); return; }

            modification mod;
            mod.operation = op;
            const QString objStr = a.value("object");
            mod.object = squirrel::ObjectTypeToEnum(objStr);
            if (!objStr.isEmpty() && (mod.object == UnknownObjectType)) {
                addMsg(QString("Unknown --object [%1]. Type 'help modify' for the valid types.").arg(objStr));
                return;
            }
            if (objStr.isEmpty() && (op == "add" || op == "remove" || op == "update")) {
                addMsg(QString("--operation %1 needs --object. Type 'help modify' for usage.").arg(op));
                return;
            }
            mod.dataPath = ExpandPath(a.value("datapath"));
            mod.objectData = a.value("objectdata");
            mod.objectID = a.value("objectid");
            mod.subjectID = a.value("subjectid");
            mod.objectDate = a.value("objectdate");
            mod.itemID = a.value("itemid");
            if (!IntOpt(a, "studynum", 0, mod.studyNumber, err) || !IntOpt(a, "seriesnum", 0, mod.seriesNumber, err) ||
                !IntOpt(a, "digits", 0, mod.renumberDigits, err) || !IntOpt(a, "startnum", 1, mod.renumberStartNum, err)) {
                addMsg(err);
                return;
            }
            mod.renumberPrefix = a.value("prefix");
            mod.renumberRandomize = a.has("random");
            mod.recursive = a.has("recursive");

            /* captures only Run()-lifetime names by reference: this may run
               later, from the confirmation prompt */
            std::function<void()> action = [&, mod, op] {
                bool ok = false;
                QString m;
                runLive("modify --operation " + op.toStdString(), [&] {
                    modify modifier;
                    ok = modifier.DoModify(pkgPath, mod, m);
                    /* splitbymodality writes new packages and leaves this one as is */
                    if (ok && (op != "splitbymodality"))
                        reloadCurrent();
                });
                if (ok) addMsg("modify " + op + ": done");
                else addMsg("modify failed: " + m);
            };

            if ((op == "remove" || op == "removephi" || op == "renumber") && !a.has("yes")) {
                /* name the object being removed using whichever identifiers were given */
                QStringList ids;
                for (const char *k : { "subjectid", "studynum", "seriesnum", "objectid", "objectdate", "itemid" })
                    if (!a.value(k).isEmpty())
                        ids << QString("%1 %2").arg(k, a.value(k));
                QString what = (op == "remove")    ? QString("permanently remove the %1 [%2] and everything under it").arg(objStr.toLower(), ids.join(", "))
                             : (op == "removephi") ? QString("remove PHI from the package")
                                                   : QString("renumber every subject ID");
                addMsg(QString("This will %1 in %2.").arg(what, pkgPath));
                addMsg("Type 'yes' to continue, anything else to cancel.");
                pendingAction = action;
                return;
            }
            action();
        }
        else if (cmd == "vars") {
            if (a.pos.size() != 1) { addMsg("Usage: vars <object>   e.g. vars subject"); return; }
            ObjectType t = squirrel::ObjectTypeToEnum(a.pos[0]);
            if (t == UnknownObjectType) { addMsg(QString("Unknown object type [%1]").arg(a.pos[0])); return; }
            addLine(CaptureStdout([&] { modify mdf; mdf.PrintVariables(t); }));
        }
        else if (cmd == "validate") {
            bool valid = false;
            runLive("validate", [&] {
                squirrel v(false, false);
                v.SetPackagePath(pkgPath);
                v.SetFileMode(FileMode::ExistingPackage);
                valid = v.Read();
            });
            addMsg(valid ? "Valid squirrel file" : "*** Invalid squirrel file ***");
        }
        else if (cmd == "merge") {
            if (a.pos.isEmpty()) { addMsg("merge needs at least one other package. Type 'help merge' for usage."); return; }
            QStringList inputs;
            inputs << pkgPath;
            for (const QString &p : a.pos)
                inputs << ExpandPath(p);
            const QString output = ExpandPath(a.value("output"));
            const bool testOnly = a.has("test");
            if (output.isEmpty() && !testOnly) { addMsg("Missing --output path. Specify an output package with --output <path>."); return; }
            int digits;
            if (!IntOpt(a, "digits", 0, digits, err)) { addMsg(err); return; }
            const bool outIsCurrent = !output.isEmpty() && (QFileInfo(output).absoluteFilePath() == pkgPath);
            bool ok = false;
            QString m;
            runLive("merge", [&] {
                modify merger;
                ok = merger.MergePackages(inputs, output, testOnly, a.has("renumbersubjects"), digits, m);
                if (ok && !testOnly && outIsCurrent)
                    reloadCurrent();
            });
            if (!ok) addMsg("merge failed: " + m);
            else if (testOnly) addMsg("merge test finished (nothing written)");
            else if (!outIsCurrent) addMsg("Merged into " + output + ". Type 'open " + QuoteArg(output) + "' to explore it.");
        }
        else if (cmd == "convert") {
            if (a.pos.size() != 2) { addMsg("Usage: convert <inputdir> <outputpackage> --inputformat <bids|dicom>"); return; }
            const QString inPath = ExpandPath(a.pos[0]);
            const QString outPath = ExpandPath(a.pos[1]);
            const QString inFmt = a.value("inputformat");
            if (inFmt.isEmpty()) { addMsg("Missing --inputformat. Valid values: bids, dicom"); return; }
            const QString outFmt = a.value("outputformat").isEmpty() ? QString("squirrel") : a.value("outputformat");
            const bool outIsCurrent = (QFileInfo(outPath).absoluteFilePath() == pkgPath);
            bool ok = false;
            QString m;
            runLive("convert", [&] {
                convert converter;
                ok = converter.DoConvert(inPath, outPath, inFmt, outFmt, a.value("dataformat"), a.value("dirformat"), a.has("overwrite"), false, false, false, m);
                if (ok && outIsCurrent)
                    reloadCurrent();
            });
            if (!ok) addMsg("convert failed: " + m);
            else if (!outIsCurrent) addMsg("Created " + outPath + ". Type 'open " + QuoteArg(outPath) + "' to explore it.");
        }
        else if (cmd == "open" || cmd == "reload") {
            QString path;
            if (cmd == "open") {
                if (a.pos.size() != 1) { addMsg("Usage: open <package>"); return; }
                path = ExpandPath(a.pos[0]);
            }
            else
                path = pkgPath;
            std::string log;
            if (openPackage(path, err, log))
                addMsg(QString("Opened %1  (%2 subjects)").arg(pkgPath).arg(nSubjects));
            else {
                addLine(log);
                addMsg(err);
            }
        }
    };

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
    opt.placeholder = "type a command  (help, ls, info, tree, extract, modify, quit)";
    opt.multiline = false;
    opt.on_enter = [&] {
        QString line = QString::fromUtf8(command.c_str()).trimmed();
        command.clear();
        cursorPos = 0;
        scroll = 0; /* entering anything snaps the view back to the bottom */

        /* answer to a pending confirmation prompt */
        if (pendingAction) {
            std::function<void()> act = std::move(pendingAction);
            pendingAction = nullptr;
            addLine("> " + line.toStdString());
            if (line.compare("yes", Qt::CaseInsensitive) == 0 || line.compare("y", Qt::CaseInsensitive) == 0)
                act();
            else
                addMsg("Cancelled.");
            addLine("");
            return;
        }

        if (line.isEmpty())
            return;

        /* record in history (skip consecutive duplicates); reset the cursor */
        std::string cmdStr = line.toStdString();
        if (cmdHistory.empty() || cmdHistory.back() != cmdStr)
            cmdHistory.push_back(cmdStr);
        histPos = int(cmdHistory.size());
        draft.clear();

        if (line.compare("clear", Qt::CaseInsensitive) == 0) {
            transcript.clear();
            return;
        }

        addLine("> " + cmdStr);
        runCommand(line);
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
            Element hint;
            if (pendingAction)
                hint = text("  Confirm: type 'yes' to continue, anything else cancels") | color(Color::YellowLight);
            else if (scroll > 0)
                hint = text("  [scrolled up " + std::to_string(scroll) + "/" + std::to_string(maxScroll) +
                            " - wheel / Ctrl+Down / PageDown to return to bottom]") | dim;
            else
                hint = text("");

            body = vbox({
                       text(title) | bold | color(Color::CyanLight),
                       separator(),
                       pane,
                       separator(),
                       hbox({ text("> ") | color(Color::GreenLight), input->Render() | flex }) | border,
                       hint,
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
                Element line = text(TreeRowLabel(*sqrl, treeData, treeRows[size_t(i)]));
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
            std::string hint = " Up/Down move   Enter expand/collapse (series: details)   i details"
                                "   x extract   u update   q back to shell ";

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

    /* Fill the command bar with an extract / modify-update command for the
       selected tree row and switch to the shell, so the user only has to add
       the missing part (--outdir, or the --objectdata key=value pairs). */
    auto prefillFromTree = [&](const TreeRow &row, bool isExtract) {
        TreeSubjectEntry &subj = treeData[size_t(row.subjectIdx)];
        const QString sid = QuoteArg(subj.subject.ID);
        QString ids;
        if (row.type == TreeRowType::Subject) {
            ids = "--object subject --objectid " + sid;
        }
        else {
            const int studyNum = subj.studies[row.studyIdx].study.StudyNumber;
            if (row.type == TreeRowType::Study) {
                ids = isExtract ? QString("--object study --subjectid %1 --objectid %2").arg(sid).arg(studyNum)
                                : QString("--object study --subjectid %1 --studynum %2").arg(sid).arg(studyNum);
            }
            else {
                const qint64 seriesNum = subj.studies[row.studyIdx].seriesList[row.seriesIdx].SeriesNumber;
                ids = isExtract ? QString("--object series --subjectid %1 --studynum %2 --objectid %3").arg(sid).arg(studyNum).arg(seriesNum)
                                : QString("--object series --subjectid %1 --studynum %2 --seriesnum %3").arg(sid).arg(studyNum).arg(seriesNum);
            }
        }
        if (isExtract) {
            command = ("extract " + ids + " --outdir ").toStdString();
            cursorPos = int(command.size());
        }
        else {
            command = ("modify --operation update " + ids + " --objectdata ''").toStdString();
            cursorPos = int(command.size()) - 1; /* between the quotes */
        }
        histPos = int(cmdHistory.size());
        draft.clear();
        mode = ViewMode::Command;
    };

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
                        modalBody = TreeRowDetails(*sqrl, treeData, sib);
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
                        modalBody = TreeRowDetails(*sqrl, treeData, row);
                        showModal = true;
                    }
                }
                else if (e == Event::ArrowLeft) {
                    if (TreeRowIsExpanded(treeData, row))
                        TreeRowToggle(*sqrl, treeData, row);
                }
                else {
                    TreeRowToggle(*sqrl, treeData, row);
                }
                return true;
            }
            if (!treeRows.empty() && (e.is_character() && (e.character() == "i" || e.character() == "I"))) {
                const TreeRow &row = treeRows[size_t(treeSelected)];
                modalTitle = row.type == TreeRowType::Subject ? "Subject detail"
                           : row.type == TreeRowType::Study   ? "Study detail"
                                                               : "Series detail";
                modalBody = TreeRowDetails(*sqrl, treeData, row);
                showModal = true;
                return true;
            }
            if (!treeRows.empty() && e.is_character() &&
                (e.character() == "x" || e.character() == "X" || e.character() == "u" || e.character() == "U")) {
                prefillFromTree(treeRows[size_t(treeSelected)], e.character() == "x" || e.character() == "X");
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
