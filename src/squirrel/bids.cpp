/* ------------------------------------------------------------------------------
  Squirrel bids.cpp
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

#include "bids.h"
#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QCollator>
#include <QRegularExpression>

/* ---------------------------------------------------------------------------- */
/* ----- NaturalSort ---------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Directory listings come back in filesystem order, which is arbitrary. Sort them
 * so subject/session/series numbering is deterministic and follows the BIDS labels
 * (natural order, so ses-2 comes before ses-10).
 *
 * @brief Sort a list of names/paths in natural (numeric-aware) order
 * @param list the list to sort
 * @return the sorted list
 */
static QStringList NaturalSort(QStringList list) {
    QCollator collator;
    collator.setNumericMode(true);
    collator.setCaseSensitivity(Qt::CaseInsensitive);
    std::sort(list.begin(), list.end(), [&collator](const QString &a, const QString &b) {
        return collator.compare(a, b) < 0;
    });
    return list;
}

/* ---------------------------------------------------------------------------- */
/* ----- ListEntries ---------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Hidden files are included (eg .zgroup, .bidsignore, tool history files), except
 * operating-system clutter: .DS_Store, ._* (macOS AppleDouble), Thumbs.db, desktop.ini.
 *
 * @brief List the files and directories directly inside a directory, including hidden ones
 * @param dir the directory
 * @return full paths, files first then directories, each in natural order
 */
static QStringList ListEntries(QString dir) {
    QStringList files, dirs;
    QDir d(dir);
    /* no NoSymLinks: DataLad/git-annex datasets store data files as symlinks. System
     * is needed for Qt to list broken symlinks (content not yet fetched), which are
     * kept here so file names still drive series creation; StageFiles() drops them */
    foreach (QFileInfo fi, d.entryInfoList(QDir::Files | QDir::Dirs | QDir::NoDotAndDotDot | QDir::Hidden | QDir::System)) {
        if (utils::IsSkippedName(fi.fileName()))
            continue;
        if (fi.isDir())
            dirs.append(fi.absoluteFilePath());
        else if (fi.isFile() || fi.isSymLink())
            files.append(fi.absoluteFilePath());
    }
    return NaturalSort(files) + NaturalSort(dirs);
}


/* ---------------------------------------------------------------------------- */
/* ----- BidsFiles / BidsDirs ------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Like utils::FindAllFiles/FindAllDirs (non-recursive, no hidden entries), but
 * symlinks are followed and broken symlinks are listed as files, and VCS/DataLad
 * metadata and OS clutter are skipped.
 *
 * @brief List the (non-hidden) files in a directory matching a pattern
 * @param dir the directory
 * @param pattern wildcard pattern, eg "*" or "*.tsv"
 * @return full paths, in natural order
 */
static QStringList BidsFiles(QString dir, QString pattern) {
    QStringList files;
    QDir d(dir);
    foreach (QFileInfo fi, d.entryInfoList(QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::System)) {
        if (utils::IsSkippedName(fi.fileName()) || fi.isDir())
            continue;
        if (fi.isFile() || fi.isSymLink())
            files.append(fi.absoluteFilePath());
    }
    return NaturalSort(files);
}

/**
 * @brief List the (non-hidden) directories in a directory matching a pattern, following symlinks
 * @param dir the directory
 * @param pattern wildcard pattern, eg "sub-*"
 * @return directory names (not paths), in natural order
 */
static QStringList BidsDirs(QString dir, QString pattern) {
    QStringList dirs;
    QDir d(dir);
    foreach (QFileInfo fi, d.entryInfoList(QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot)) {
        if (!utils::IsSkippedName(fi.fileName()))
            dirs.append(fi.fileName());
    }
    return NaturalSort(dirs);
}


/* ---------------------------------------------------------------------------- */
/* ----- bids ----------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Constructor
 */
bids::bids()
{

}


/* ---------------------------------------------------------------------------- */
/* ----- Read ----------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * This function does its best to load an existing BIDS directory into a squirrel
 * package. Due to the complex nature of BIDS structures and addendums, exact
 * converstion of BIDS into squirrel may be incomplete
 *
 * @brief Load a BIDS directory into a squirrel object
 * @param dir Path to the BIDS directory
 * @param sqrl Squirrel object
 * @return true if loaded successfully, false otherwise
 */
bool bids::Read(QString dir, squirrel *sqrl) {
    Q_UNUSED(dir);
    Q_UNUSED(sqrl);

    /* 1) Read participants.tsv to get list of expected subjects
     * 2) Read sub- directories
     * 3) Read ses- directories
     * 4) Read Entity directories (anat, func, etc)
     * 5) Within the entity directories, read the files
     * 6) Parse the filenames into known parts (-acq, -run, _suffix, etc)
    */

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadToSquirrel ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * This function does its best to load an existing BIDS directory into a squirrel
 * package. Due to the complex nature of BIDS structures and addendums, exact
 * converstion of BIDS into squirrel may be incomplete
 *
 * @brief Load a BIDS directory into a squirrel object
 * @param dir path to the BIDS directory
 * @param sqrl squirrel object
 * @return true if loaded successfully, false otherwise
 */
bool bids::LoadToSquirrel(QString dir, squirrel *sqrl) {

    sqrl->Log(QString("Loading BIDS from [%1]").arg(dir));

    /* check if BIDS directory exists */
    QDir d(dir);
    if (!d.exists()) {
        sqrl->Log(QString("Error. Directory [%1] does not exist").arg(dir));
        return false;
    }

    QSqlDatabase bidsDbconn = QSqlDatabase::database(sqrl->GetDatabaseUUID());
    if (!bidsDbconn.transaction())
        sqrl->Log(QString("Warning: could not start BIDS import transaction: %1").arg(bidsDbconn.lastError().text()));

    /* check for all .json files in the root directory */
    QStringList rootfiles = NaturalSort(BidsFiles(dir, "*"));
    sqrl->Debug(QString("Found [%1] root files matching '%2/*'").arg(rootfiles.size()).arg(dir), __FUNCTION__);
    usedRootFiles.clear();
    rootDir = dir;
    missingContent.clear();
    LoadRootFiles(rootfiles, sqrl);

    /* root-level sidecars apply to every matching data file in the dataset (BIDS inheritance principle) */
    rootInheritable = InheritableFiles(rootfiles);
    sqrl->Debug(QString("Found [%1] inheritable root sidecar files").arg(rootInheritable.size()), __FUNCTION__);

    /* get list of directories in the root named 'sub-*' */
    QStringList subjdirs = NaturalSort(BidsDirs(dir, "sub-*"));

    sqrl->Debug(QString("Found [%1] subject directories matching '%2/sub-*'").arg(subjdirs.size()).arg(dir), __FUNCTION__);

    /* a dataset whose root is itself a derivative (DatasetType: derivative, eg fMRIPrep
     * output, or an atlas) holds analysis outputs, not raw data. It is imported as one
     * pipeline with analyses (and group-level outputs), rather than as series */
    bool derivativeDataset = (datasetType.toLower() == "derivative");
    if (derivativeDataset)
        sqrl->Log(QString("Dataset is a BIDS derivative (generated by [%1]); importing it as a pipeline with analyses").arg(generatedByName));

    /* sub-*_sessions.tsv (session acquisition times + session-level measures) and its
     * .json column descriptions, applied once the subject's studies exist */
    auto loadSessions = [&](QString subjdir, qint64 subjectRowID) {
        QString subjpath = QString("%1/%2").arg(dir).arg(subjdir);
        QString sessionsFile = QString("%1/%2_sessions.tsv").arg(subjpath).arg(subjdir);
        QString sessionsJson = QString("%1/%2_sessions.json").arg(subjpath).arg(subjdir);
        if (utils::FileExists(sessionsFile) || utils::FileExists(sessionsJson) || utils::FileExists(rootDir + "/sessions.json"))
            LoadSessionsFile(sessionsFile, subjectRowID, sqrl);
    };

    foreach (QString subjdir, subjdirs) {

        QString subjpath = QString("%1/%2").arg(dir).arg(subjdir);

        QString ID = subjdir;
        /* load the subject */
        squirrelSubject sqrlSubject(sqrl->GetDatabaseUUID());
        qint64 subjectRowID = sqrl->FindSubject(ID);
        if (subjectRowID < 0) {
            sqrlSubject.ID = ID;
            sqrlSubject.Store();
            subjectRowID = sqrlSubject.GetObjectID();
            sqrl->Log(QString("Added subject [%1]").arg(ID));
        }
        sqrl->Log(QString("Reading BIDS subject [%1] into squirrel subject [%2] with rowID [%3]").arg(ID).arg(sqrlSubject.ID).arg(subjectRowID));

        /* derivative dataset: the subject's outputs are imported as analyses below */
        if (derivativeDataset)
            continue;

        /* get a list of ses-* DIRS, if there are any */
        QStringList sesdirs = OrderSessionsByAcqTime(NaturalSort(BidsDirs(subjpath, "ses-*")), QString("%1/%2_sessions.tsv").arg(subjpath).arg(subjdir), sqrl);
        sqrl->Debug(QString("Found [%1] session directories matching '%2/ses-*'").arg(sesdirs.size()).arg(subjdir), __FUNCTION__);
        if (sesdirs.size() > 0) {
            /* sidecars in the subject directory apply to all of this subject's sessions */
            QStringList subjfiles = NaturalSort(BidsFiles(subjpath, "*"));
            QStringList inherited = rootInheritable + InheritableFiles(subjfiles);
            QStringList subjectScans;
            foreach (QString f, subjfiles) {
                if (f.endsWith("_scans.tsv"))
                    subjectScans.append(f);
            }

            int studyNum = 1;
            foreach (QString sesdir, sesdirs) {
                /* each session will become its own study */
                QString sespath = QString("%1/%2/%3").arg(dir).arg(subjdir).arg(sesdir);

                sqrl->Debug(QString("Loading session path [%1] into study [%2]").arg(sespath).arg(studyNum), __FUNCTION__);

                LoadSessionDir(sespath, subjectRowID, studyNum, inherited, subjectScans, sqrl);
                studyNum++;
            }
        }
        else {
            /* if there are no ses-* directories, then the session must be in the root subject
             * directory. Its files (scans.tsv, sidecars) are read as session-level files */
            QString sespath = QString("%1/%2").arg(dir).arg(subjdir);
            LoadSessionDir(sespath, subjectRowID, -1, rootInheritable, QStringList(), sqrl);
        }

        /* now that this subject's studies exist, apply the sessions file */
        loadSessions(subjdir, subjectRowID);
    }

    if (derivativeDataset) {
        /* the whole dataset is one pipeline. Directories with their own handling are
         * excluded; everything else that isn't a subject (tpl-* templates, logs/) is
         * group-level output */
        QString pipelineName = generatedByName.isEmpty() ? QString("derivative") : generatedByName;
        derivAnalyses.clear();
        derivativeRootMode = true;
        LoadDerivativePipeline(dir, pipelineName, false, sqrl, QStringList({"derivatives", "phenotype", "code", "stimuli", "sourcedata"}));
        derivativeRootMode = false;

        /* the root files were staged with the pipeline; don't also copy them into Notes */
        foreach (QString f, rootfiles)
            usedRootFiles.insert(f);

        foreach (QString subjdir, subjdirs)
            loadSessions(subjdir, sqrl->FindSubject(subjdir));
    }

    /* check for a 'phenotype' directory, which become subject observations */
    QString phenotypeDir = QString("%1/phenotype").arg(dir);
    if (QDir(phenotypeDir).exists())
        LoadPhenotypeDir(phenotypeDir, sqrl);

    /* check for a 'derivatives' directory, which become pipelines + analyses */
    QString derivativesDir = QString("%1/derivatives").arg(dir);
    if (QDir(derivativesDir).exists())
        LoadDerivatives(derivativesDir, sqrl);

    /* 'stimuli' directory -> an experiment */
    QString stimuliDir = QString("%1/stimuli").arg(dir);
    if (QDir(stimuliDir).exists())
        LoadStimuliDir(stimuliDir, sqrl);

    /* 'code' directory -> a pipeline */
    QString codeDir = QString("%1/code").arg(dir);
    if (QDir(codeDir).exists())
        LoadCodeDir(codeDir, sqrl);

    /* dataset-level files with no squirrel object (LICENSE, participants.json, ...) -> package Notes */
    StoreDatasetFilesInNotes(rootfiles, sqrl);

    /* files whose content isn't present (DataLad/git-annex, not yet fetched) */
    ReportMissingContent(sqrl);

    /* not imported: 'sourcedata' (usually raw DICOM, better imported with the DICOM converter) */
    if (QDir(QString("%1/sourcedata").arg(dir)).exists())
        sqrl->Log("Notice: BIDS 'sourcedata' directory is not imported");

    if (!bidsDbconn.commit())
        sqrl->Log(QString("Warning: could not commit BIDS import transaction: %1").arg(bidsDbconn.lastError().text()));

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadRootFiles -------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Load the files contained in the root of the BIDS directory
 * @param rootfiles list of files
 * @param sqrl squirrel object
 * @return true if successful, false if any errors
 */
bool bids::LoadRootFiles(QStringList rootfiles, squirrel *sqrl) {
    sqrl->Log(QString("Loading [%1] files from the BIDS root directory").arg(rootfiles.size()));

    foreach (QString filePath, rootfiles) {
        QFileInfo fi(filePath);
        QString filename = fi.fileName();

        sqrl->Debug(QString("Found file [%1]").arg(filename), __FUNCTION__);
        /* possible files in the root dir
            dataset_description.json
            participants.json
            participants.tsv
            README
            README.md
            CHANGES
            task-*_bold.json
            task-*_events.tsv
            task-*_physio.json
            task-*_stim.json
            desc-aparcaseg_dseg.tsv
            acq-*_<modality>.json (modality is something like T1w, dwi, and will match a directory inside each sub-* directory)
        */

        if (filename == "dataset_description.json") {
            sqrl->Debug(QString("Parsing dataset_description.json from %1").arg(filePath), __FUNCTION__);
            LoadDatasetDescription(filePath, sqrl);
        }
        else if ((filename == "README") || (filename == "README.md")) {
            sqrl->Debug(QString("Read squirrel->Readme from %1").arg(filePath), __FUNCTION__);
            sqrl->Readme = utils::ReadTextFileToString(filePath);
        }
        else if (filename == "CHANGES") {
            sqrl->Debug(QString("Read squirrel->Changes from %1").arg(filePath), __FUNCTION__);
            sqrl->Changes = utils::ReadTextFileToString(filePath);
        }
        else if (filename.startsWith("task-")) {
            sqrl->Debug(QString("Reading squirrel->experiment from from %1 ...").arg(filePath), __FUNCTION__);
            /* this goes into the squirrel experiments object */
            LoadTaskFile(filePath, sqrl);
        }
        else if (filename == "participants.tsv") {
            sqrl->Debug(QString("Reading squirrel->subject (demographics) from from %1 ...").arg(filePath), __FUNCTION__);
            /* this goes into the subjects object */
            if (!LoadParticipantsFile(filePath, sqrl)) {
                sqrl->Log("Error loading participants.tsv");
            }
        }
    }

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- InheritableFiles ----------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Under the BIDS inheritance principle, a sidecar placed higher in the tree (the
 * dataset root, a subject directory, or a session directory) applies to every data
 * file below it whose entities include the sidecar's entities and whose suffix
 * matches (eg a root 'task-rest_bold.json' applies to every *_task-rest_*_bold.nii.gz).
 *
 * @brief Select the files from a directory listing that can be inherited by data files
 * @param files full paths of the files in one directory
 * @return the inheritable sidecar files (.json, .bval, .bvec, .tsv)
 */
QStringList bids::InheritableFiles(QStringList files) {
    static const QStringList notInheritable = {
        "dataset_description.json", "participants.json", "participants.tsv",
        "samples.json", "samples.tsv", "genetic_info.json"
    };

    QStringList list;
    foreach (QString f, files) {
        QString fn = QFileInfo(f).fileName();
        if (notInheritable.contains(fn))
            continue;
        if (fn.endsWith("_sessions.tsv") || fn.endsWith("_sessions.json") || fn.endsWith("_scans.tsv") || fn.endsWith("_scans.json"))
            continue;

        QHash<QString, QString> ents;
        QString suffix, ext;
        ParseBidsFilename(fn, ents, suffix, ext);
        if (suffix.isEmpty() || (suffix == "sessions"))  /* sessions.json is read by LoadSessionsFile */
            continue;
        if ((ext == ".json") || (ext == ".bval") || (ext == ".bvec") || (ext == ".tsv") || (ext == ".tsv.gz"))
            list.append(f);
    }
    return list;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadSessionDir ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Every datatype directory (anat, func, eeg, meg, ...) is split into 'primary' data
 * files/directories, each of which becomes a series, and everything else, which is
 * attached to the series it belongs to:
 *   - a .json/.bval/.bvec whose suffix matches a primary (eg *_bold.json) attaches to
 *     the primaries with that suffix whose entities include the sidecar's entities
 *   - any other file (events, channels, electrodes, coordsystem, physio, blood, the
 *     .eeg/.vmrk/.fdt halves of a recording, ...) attaches to the primaries whose
 *     entities include its entities, ignoring space- and recording-
 *   - a file that matches no primary (eg sub-01_acq-NAS_photo.jpg) attaches to all of
 *     the primaries in that directory, so nothing is dropped
 * Sidecars inherited from higher levels are staged with the series as well, and all
 * matching .json sidecars are merged (least to most specific) into the series params.
 *
 * @brief Load the files from a BIDS session directory into a squirrel study
 * @param sesdir the session directory (or the subject directory, if there are no sessions)
 * @param subjectRowID parent subject
 * @param studyNum study number to use/look up
 * @param inherited sidecars inherited from the dataset root and subject directory, least specific first
 * @param subjectScans subject-level scans.tsv files (datasets with sessions), whose paths start with ses-X/
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadSessionDir(QString sesdir, qint64 subjectRowID, int studyNum, QStringList inherited, QStringList subjectScans, squirrel *sqrl) {
    sqrl->Log(QString("Reading BIDS session directory [%1] --> into squirrel study [%2]").arg(sesdir).arg(studyNum));

    /* load the subject */
    squirrelSubject subject(sqrl->GetDatabaseUUID());
    subject.SetObjectID(subjectRowID);
    subject.Get();

    /* create a new study... */
    qint64 studyRowID = sqrl->FindStudy(subject.ID, studyNum);
    squirrelStudy study(sqrl->GetDatabaseUUID());
    if (studyRowID < 0) {
        studyNum = subject.GetNextStudyNumber();
        study.StudyNumber = studyNum;
        study.DateTime = QDateTime(); /* not the conversion time; set from the data below */
        study.subjectRowID = subjectRowID;
        study.Modality = "MR";
        study.Store();
        studyRowID = study.GetObjectID();
        sqrl->Log(QString(" Added study [%1]").arg(studyNum));
    }
    /* ... or load an existing study */
    else {
        study.SetObjectID(studyRowID);
        study.Get();
    }

    /* derive the session/visit label from the session directory name (eg "ses-1" -> "1") */
    QString sesLabel;
    QString sesDirName = QFileInfo(sesdir).fileName();
    if (sesDirName.startsWith("ses-"))
        sesLabel = sesDirName.mid(4);

    /* files directly in the session directory: scans.tsv, plus session-level sidecars */
    QStringList sesFiles = NaturalSort(BidsFiles(sesdir, "*"));
    QStringList scansFiles;
    foreach (QString f, sesFiles) {
        if (f.endsWith("_scans.tsv"))
            scansFiles.append(f);
    }
    QStringList sesInheritable = InheritableFiles(sesFiles);
    inherited += sesInheritable;

    /* parsed form of the inherited files */
    struct ParsedFile { QString path; QString name; QHash<QString, QString> ents; QString suffix; QString ext; };
    QList<ParsedFile> inheritedParsed;
    foreach (QString f, inherited) {
        ParsedFile p;
        p.path = f;
        p.name = QFileInfo(f).fileName();
        ParseBidsFilename(p.name, p.ents, p.suffix, p.ext);
        inheritedParsed.append(p);
    }

    /* track which modalities are present so we can set the study modality */
    QSet<QString> modalities;

    /* BIDS-relative path (eg "func/sub-01_task-rest_bold.nii.gz") -> series row id, for scans.tsv */
    QHash<QString, qint64> seriesByPath;

    static const QStringList sidecarSuffixes = {
        "events", "channels", "electrodes", "coordsystem", "scans", "physio", "physioevents",
        "stim", "optodes", "photo", "headshape", "markers", "blood", "aslcontext", "asllabeling"
    };
    /* sidecars that may sit above the datatype directory and apply to any datatype... */
    static const QStringList genericSidecars = {"events", "physio", "physioevents", "stim", "blood", "aslcontext", "asllabeling"};
    /* ...and those that only describe electrophysiology/sensor recordings */
    static const QStringList electrophysSidecars = {"channels", "electrodes", "coordsystem", "optodes", "headshape", "photo", "markers"};
    static const QStringList electrophysDatatypes = {"eeg", "ieeg", "meg", "nirs", "fnirs", "emg", "motion"};
    static const QStringList ignoreEnts = {"space", "recording"};

    /* subject/session-level sidecars that were attached to at least one series */
    QSet<QString> usedInherited;

    /* get list of all datatype dirs in this sesdir */
    QStringList datatypeDirs = NaturalSort(BidsDirs(sesdir, "*"));
    sqrl->Log(QString("Found [%1] datatype directories in [%2/*]").arg(datatypeDirs.size()).arg(sesdir));

    foreach (QString datatype, datatypeDirs) {
        /* 'figures' (derivatives artifact) and similar non-data dirs are skipped */
        if (datatype == "figures")
            continue;

        QString datadir = QString("%1/%2").arg(sesdir).arg(datatype);

        /* files and directories (directory-format recordings such as MEG .ds or .zarr) */
        QStringList entries = ListEntries(datadir);
        sqrl->Log(QString("Found [%1] files/directories in '%2'").arg(entries.size()).arg(datadir));

        QString modality = ModalityForDatatype(datatype);
        if (modality == "") {
            sqrl->Log(QString("Notice! BIDS datatype directory [%1] not recognized; importing its data files generically").arg(datatype));
        }

        QList<ParsedFile> parsed;
        foreach (QString e, entries) {
            ParsedFile p;
            p.path = e;
            p.name = QFileInfo(e).fileName();
            ParseBidsFilename(p.name, p.ents, p.suffix, p.ext);
            parsed.append(p);
        }

        /* identify the 'primary' data files/directories; each becomes a series */
        QList<int> primaries;
        for (int i = 0; i < parsed.size(); i++) {
            if (IsPrimaryDataFile(parsed[i].path) && !sidecarSuffixes.contains(parsed[i].suffix))
                primaries.append(i);
        }
        /* fallback for datatypes whose primary data is a .tsv (eg motion, beh) */
        if (primaries.isEmpty()) {
            for (int i = 0; i < parsed.size(); i++) {
                if (((parsed[i].ext == ".tsv") || (parsed[i].ext == ".tsv.gz")) && !sidecarSuffixes.contains(parsed[i].suffix))
                    primaries.append(i);
            }
        }
        /* last resort: every non-.json file is its own series, so nothing is dropped */
        if (primaries.isEmpty()) {
            for (int i = 0; i < parsed.size(); i++) {
                if (parsed[i].ext != ".json")
                    primaries.append(i);
            }
        }
        if (primaries.isEmpty()) {
            if (!parsed.isEmpty())
                sqrl->Log(QString("Warning: no data files found in [%1]; [%2] sidecar file(s) not imported").arg(datadir).arg(parsed.size()));
            continue;
        }

        QSet<QString> primarySuffixes;
        foreach (int pi, primaries)
            primarySuffixes.insert(parsed[pi].suffix);

        /* attach every non-primary entry to the primaries it belongs to */
        QHash<int, QStringList> companions;
        QHash<int, QStringList> paramJsons; /* in-directory .json sidecars of each primary */
        QHash<int, QSet<QString>> attachedSuffixes;
        for (int i = 0; i < parsed.size(); i++) {
            if (primaries.contains(i))
                continue;
            const ParsedFile &f = parsed[i];
            bool dataSidecar = ((f.ext == ".json") || (f.ext == ".bval") || (f.ext == ".bvec")) && primarySuffixes.contains(f.suffix);

            QList<int> matched;
            foreach (int pi, primaries) {
                const ParsedFile &p = parsed[pi];
                if (dataSidecar) {
                    if ((f.suffix == p.suffix) && EntitiesSubset(f.ents, p.ents))
                        matched.append(pi);
                }
                else if (EntitiesSubset(f.ents, p.ents, ignoreEnts))
                    matched.append(pi);
            }
            if (matched.isEmpty()) {
                sqrl->Debug(QString("File [%1] matches no data file in [%2]; attaching it to all of them").arg(f.name).arg(datatype), __FUNCTION__);
                matched = primaries;
            }
            foreach (int pi, matched) {
                companions[pi].append(f.path);
                attachedSuffixes[pi].insert(f.suffix);
                if (dataSidecar && (f.ext == ".json"))
                    paramJsons[pi].append(f.path);
            }
        }

        foreach (int pi, primaries) {
            const ParsedFile &p = parsed[pi];
            QStringList files = companions.value(pi);

            /* sidecars inherited from the root, subject and session levels */
            QStringList jsons;
            foreach (const ParsedFile &inh, inheritedParsed) {
                bool isRoot = rootInheritable.contains(inh.path);
                bool match = false;
                bool forPrimary = (inh.suffix == p.suffix);
                if (forPrimary)
                    match = EntitiesSubset(inh.ents, p.ents);
                else if (attachedSuffixes.value(pi).contains(inh.suffix))
                    match = EntitiesSubset(inh.ents, p.ents, ignoreEnts);
                /* subject/session-level recording sidecars (eg ses-meg/*_channels.tsv) apply
                 * even if the datatype directory has none of its own. Root-level files are
                 * not matched this way, so a bare root 'physio.json' doesn't attach to every
                 * series; unmatched root files are kept in the package Notes instead */
                else if (!isRoot && (genericSidecars.contains(inh.suffix) || (electrophysSidecars.contains(inh.suffix) && electrophysDatatypes.contains(datatype))))
                    match = EntitiesSubset(inh.ents, p.ents, ignoreEnts);

                if (match) {
                    if (!files.contains(inh.path))
                        files.append(inh.path);
                    if (forPrimary && (inh.ext == ".json"))
                        jsons.append(inh.path);
                    if (isRoot)
                        usedRootFiles.insert(inh.path);
                    else
                        usedInherited.insert(inh.path);
                }
            }

            /* in-directory sidecars, least specific (fewest entities) first */
            QStringList local = paramJsons.value(pi);
            std::stable_sort(local.begin(), local.end(), [this](const QString &a, const QString &b) {
                QHash<QString, QString> ea, eb; QString s, x;
                ParseBidsFilename(QFileInfo(a).fileName(), ea, s, x);
                ParseBidsFilename(QFileInfo(b).fileName(), eb, s, x);
                return ea.size() < eb.size();
            });
            jsons += local;

            QHash<QString, QString> params = ReadSidecarParams(jsons);
            qint64 seriesRowID = AddSeriesFromBidsFile(p.path, datatype, studyRowID, files, params, sqrl);
            seriesByPath.insert(QString("%1/%2").arg(datatype).arg(p.name), seriesRowID);
            if (modality != "")
                modalities.insert(modality);
        }
    }

    /* subject/session-level sidecars that matched no series are attached to all of
     * this session's series, so nothing is dropped */
    if (!seriesByPath.isEmpty()) {
        foreach (const ParsedFile &inh, inheritedParsed) {
            /* only session-level files: a subject-level file may belong to another session */
            if (!sesInheritable.contains(inh.path) || usedInherited.contains(inh.path))
                continue;
            sqrl->Debug(QString("Sidecar [%1] matches no series in [%2]; attaching it to all of them").arg(inh.name).arg(sesdir), __FUNCTION__);
            foreach (qint64 id, seriesByPath.values())
                StageFiles(Series, id, QStringList() << inh.path, sqrl);
        }
    }

    /* scans.tsv: per-file acquisition times */
    foreach (QString scansFile, scansFiles)
        LoadScansFile(scansFile, seriesByPath, true, sqrl);

    /* a subject-level scans.tsv in a dataset with sessions lists paths as
     * 'ses-X/<datatype>/<file>'; only rows for this session are applied */
    if (!subjectScans.isEmpty()) {
        QHash<QString, qint64> withSes;
        for (auto it = seriesByPath.constBegin(); it != seriesByPath.constEnd(); ++it)
            withSes.insert(QString("%1/%2").arg(sesDirName).arg(it.key()), it.value());
        foreach (QString scansFile, subjectScans)
            LoadScansFile(scansFile, withSes, false, sqrl);
    }

    /* persist study-level fields determined from the session contents */
    if (modalities.contains("MR"))
        study.Modality = "MR";
    else if (modalities.size() == 1)
        study.Modality = *modalities.begin();
    else if (modalities.size() > 1) {
        QStringList ml(modalities.begin(), modalities.end());
        ml.sort();
        study.Modality = ml.join("/");
    }
    else
        study.Modality = "UNKNOWN"; /* only unrecognized datatypes (or none); squirrel's default */
    if (!sesLabel.isEmpty())
        study.VisitType = sesLabel;

    /* study datetime: the earliest series acquisition time (from scans.tsv), if the
     * study has none yet. A sessions.tsv acq_time, applied later, takes precedence */
    if (!study.DateTime.isValid()) {
        QSqlQuery q(QSqlDatabase::database(sqrl->GetDatabaseUUID()));
        q.prepare("select Datetime from Series where StudyRowID = :id and Datetime is not null and Datetime != ''");
        q.bindValue(":id", studyRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        QDateTime earliest;
        while (q.next()) {
            QDateTime dt = q.value("Datetime").toDateTime();
            if (dt.isValid() && (!earliest.isValid() || (dt < earliest)))
                earliest = dt;
        }
        study.DateTime = earliest; /* stays null (no datetime) if no series has one */
    }
    study.Store(); /* objectID is set, so this updates the existing study row */

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadScansFile -------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Sets each listed series' acquisition datetime from the 'acq_time' column, and
 * stages the scans.tsv (and its .json) with every series it lists. If no row matches
 * a series, the file is staged with all of the session's series so it is not lost.
 *
 * @brief Apply a sub-*[_ses-*]_scans.tsv file to the series of one session
 * @param f path to the scans.tsv file
 * @param seriesByPath path as listed in the 'filename' column -> series row id, for this session
 * @param allowFallback if no row matches, stage the file with all of the session's series
 * @param sqrl squirrel object
 * @return true if parsed
 */
bool bids::LoadScansFile(QString f, const QHash<QString, qint64> &seriesByPath, bool allowFallback, squirrel *sqrl) {
    sqrl->Log(QString("Reading scans file [%1]").arg(f));

    QStringList files;
    files.append(f);
    QString jsonFile = f;
    jsonFile.replace(QRegularExpression("\\.tsv$"), ".json");
    if (utils::FileExists(jsonFile))
        files.append(jsonFile);

    utils::indexedHash tsv;
    QStringList cols;
    QString m;
    QSet<qint64> targets;
    if (utils::ParseTSV(utils::ReadTextFileToString(f), tsv, cols, m)) {
        for (int i = 0; i < tsv.size(); i++) {
            QString fn = tsv[i].value("filename").trimmed();
            if (fn.startsWith("./"))
                fn = fn.mid(2);
            if (!seriesByPath.contains(fn))
                continue;
            qint64 seriesRowID = seriesByPath.value(fn);
            targets.insert(seriesRowID);

            QString acqTime = tsv[i].value("acq_time").trimmed();
            if (acqTime.isEmpty() || (acqTime.toLower() == "n/a"))
                continue;
            QDateTime dt = QDateTime::fromString(acqTime, Qt::ISODate);
            if (!dt.isValid())
                continue;
            QSqlQuery q(QSqlDatabase::database(sqrl->GetDatabaseUUID()));
            q.prepare("update Series set Datetime = :dt where SeriesRowID = :id");
            q.bindValue(":dt", dt);
            q.bindValue(":id", seriesRowID);
            utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
    }
    else
        sqrl->Log(QString("Error reading scans.tsv [%1]: %2").arg(f).arg(m));

    if (targets.isEmpty() && allowFallback) {
        foreach (qint64 id, seriesByPath.values())
            targets.insert(id);
    }
    foreach (qint64 id, targets)
        StageFiles(Series, id, files, sqrl);
    sqrl->Debug(QString("Staged [%1] with [%2] series").arg(QFileInfo(f).fileName()).arg(targets.size()), __FUNCTION__);

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- ParseBidsFilename ---------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Parse a BIDS filename into entity key/value pairs, a suffix, and an extension
 *
 * eg "sub-01_ses-1_task-rest_run-2_bold.nii.gz" yields
 *    entities = {sub:01, ses:1, task:rest, run:2}, suffix = "bold", ext = ".nii.gz"
 *
 * @param filename the bare filename (no directory)
 * @param entities [out] hash of entity key -> value
 * @param suffix [out] the trailing suffix (the token with no '-')
 * @param ext [out] the file extension (including leading dot)
 */
void bids::ParseBidsFilename(const QString &filename, QHash<QString, QString> &entities, QString &suffix, QString &ext) {
    entities.clear();
    suffix.clear();
    ext.clear();

    QString base = filename;

    /* strip the extension (check multi-part extensions before single-part) */
    static const QStringList exts = {
        ".nii.gz", ".tsv.gz", ".ome.tif", ".ome.tiff", ".ome.btf", ".ome.zarr",
        ".nii", ".json", ".tsv", ".bval", ".bvec",
        ".edf", ".bdf", ".set", ".fdt", ".vhdr", ".vmrk", ".eeg", ".fif", ".nwb",
        ".ds", ".mefd", ".zarr", ".sqd", ".con", ".mrk", ".elp", ".hsp", ".dat",
        ".snirf", ".tif", ".tiff", ".png", ".jpg", ".jpeg", ".pos", ".mat", ".txt"
    };
    foreach (const QString &e, exts) {
        if (base.endsWith(e, Qt::CaseInsensitive)) {
            ext = base.right(e.size());
            base.chop(e.size());
            break;
        }
    }
    /* unknown extension: strip whatever follows the last dot */
    if (ext.isEmpty()) {
        int dot = base.lastIndexOf('.');
        if (dot > 0) {
            ext = base.mid(dot);
            base.truncate(dot);
        }
    }

    /* split the remaining base into underscore-separated tokens. A 'key-value'
     * token is an entity; a token with no '-' is the suffix. */
    foreach (const QString &token, base.split("_", Qt::SkipEmptyParts)) {
        int dash = token.indexOf('-');
        if (dash > 0)
            entities.insert(token.left(dash), token.mid(dash + 1));
        else
            suffix = token;
    }
}


/* ---------------------------------------------------------------------------- */
/* ----- IsPrimaryDataFile ---------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Is this file (or directory) a BIDS data recording, as opposed to a sidecar?
 *
 * Companion files that make up part of a recording (.eeg/.vmrk for BrainVision .vhdr,
 * .fdt for EEGLAB .set) are not primaries; they are attached to their header file.
 *
 * @param path full path to the file or directory
 * @return true if it is a primary data file/directory
 */
bool bids::IsPrimaryDataFile(const QString &path) {
    QFileInfo fi(path);
    QString fn = fi.fileName().toLower();

    /* directory-format recordings: CTF MEG, MEF3 iEEG, OME-Zarr microscopy */
    if (fi.isDir())
        return fn.endsWith(".ds") || fn.endsWith(".mefd") || fn.endsWith(".zarr");

    static const QStringList dataExts = {
        ".nii.gz", ".nii", ".edf", ".bdf", ".set", ".vhdr", ".fif", ".nwb",
        ".sqd", ".con", ".snirf", ".tif", ".tiff", ".ome.btf", ".png"
    };
    foreach (const QString &e, dataExts) {
        if (fn.endsWith(e))
            return true;
    }
    return false;
}


/* ---------------------------------------------------------------------------- */
/* ----- EntitiesSubset ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Check whether every entity in 'sub' appears in 'super' with the same value
 * @param sub entities of the sidecar
 * @param super entities of the data file
 * @param ignore entity keys in 'sub' that describe the sidecar itself (eg space, recording)
 * @return true if sub is a subset of super
 */
bool bids::EntitiesSubset(const QHash<QString, QString> &sub, const QHash<QString, QString> &super, const QStringList &ignore) {
    for (auto it = sub.constBegin(); it != sub.constEnd(); ++it) {
        if (ignore.contains(it.key()))
            continue;
        if (!super.contains(it.key()) || (super.value(it.key()) != it.value()))
            return false;
    }
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- ReadSidecarParams ---------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Later files override earlier ones, so pass them least specific first. Values are
 * stored as strings (the squirrel params format): numbers and booleans as their text,
 * arrays and objects as compact JSON.
 *
 * @brief Merge BIDS .json sidecars into a series params hash
 * @param jsonFiles paths of .json sidecars, least specific first
 * @return merged key/value params
 */
QHash<QString, QString> bids::ReadSidecarParams(QStringList jsonFiles) {
    QHash<QString, QString> params;
    foreach (QString f, jsonFiles) {
        QJsonDocument doc = QJsonDocument::fromJson(utils::ReadTextFileToString(f).toUtf8());
        if (!doc.isObject())
            continue;
        QJsonObject obj = doc.object();
        for (auto it = obj.constBegin(); it != obj.constEnd(); ++it) {
            QJsonValue v = it.value();
            QString s;
            if (v.isString())
                s = v.toString();
            else if (v.isBool())
                s = v.toBool() ? "true" : "false";
            else if (v.isDouble())
                s = QString::number(v.toDouble(), 'g', 15);
            else if (v.isArray())
                s = QString::fromUtf8(QJsonDocument(v.toArray()).toJson(QJsonDocument::Compact));
            else if (v.isObject())
                s = QString::fromUtf8(QJsonDocument(v.toObject()).toJson(QJsonDocument::Compact));
            params.insert(it.key(), s);
        }
    }
    return params;
}


/* ---------------------------------------------------------------------------- */
/* ----- ModalityForDatatype -------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Map a BIDS datatype directory name to a squirrel study modality
 * @param datatype the datatype directory name (anat, func, eeg, ...)
 * @return the squirrel modality string, or "" if unrecognized
 */
QString bids::ModalityForDatatype(const QString &datatype) {
    if ((datatype == "anat") || (datatype == "func") || (datatype == "fmap") || (datatype == "dwi") || (datatype == "perf"))
        return "MR";
    if (datatype == "pet")   return "PET";
    if (datatype == "eeg")   return "EEG";
    if (datatype == "ieeg")  return "iEEG";
    if (datatype == "meg")   return "MEG";
    if (datatype == "micr")  return "Microscopy";
    if (datatype == "motion") return "Motion";
    if (datatype == "beh")   return "Behavioral";
    if ((datatype == "nirs") || (datatype == "fnirs")) return "NIRS";
    if (datatype == "emg")   return "EMG";
    if (datatype == "mrs")   return "MR";   /* MR spectroscopy, acquired on an MR scanner */
    return "";
}


/* ---------------------------------------------------------------------------- */
/* ----- AddSeriesFromBidsFile ------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Create a squirrel series from a primary BIDS data file
 *
 * Parses the BIDS entities from the filename, populates the series' BIDS fields and
 * params, and stages the primary file plus its companion files.
 *
 * @param primaryFile full path to the primary data file (or directory-format recording)
 * @param datatype the BIDS datatype directory name (anat, func, dwi, ...)
 * @param studyRowID the parent study row id
 * @param companionFiles sidecars and companion files to stage with the series
 * @param params merged .json sidecar params
 * @param sqrl squirrel object
 * @return the new series row id, or -1 on failure
 */
qint64 bids::AddSeriesFromBidsFile(QString primaryFile, QString datatype, qint64 studyRowID, QStringList companionFiles, QHash<QString, QString> params, squirrel *sqrl) {
    QFileInfo fi(primaryFile);
    QString filename = fi.fileName();

    QHash<QString, QString> entities;
    QString suffix, ext;
    ParseBidsFilename(filename, entities, suffix, ext);

    /* next series number for this study */
    squirrelStudy study(sqrl->GetDatabaseUUID());
    study.SetObjectID(studyRowID);
    qint64 seriesNum = study.GetNextSeriesNumber();

    /* build the series and populate its BIDS fields */
    squirrelSeries series(sqrl->GetDatabaseUUID());
    series.SeriesNumber = seriesNum;
    series.studyRowID = studyRowID;
    series.BidsEntity = datatype;
    series.BidsSuffix = suffix;
    series.BidsTask = entities.value("task");
    series.BidsRun = entities.value("run");
    series.BidsPhaseEncodingDirection = entities.value("dir");
    bool runOk = false;
    int runNum = entities.value("run").toInt(&runOk);
    if (runOk)
        series.Run = runNum;

    /* a human-ish protocol/description from the entities */
    QString protocol = suffix;
    if (entities.contains("task"))
        protocol = entities.value("task") + "_" + protocol;
    if (entities.contains("acq"))
        protocol = entities.value("acq") + "_" + protocol;
    series.Protocol = protocol;
    series.Description = suffix;
    series.params = params;

    series.Store();
    qint64 seriesRowID = series.GetObjectID();

    /* the primary file plus its companions */
    QStringList files;
    files.append(primaryFile);
    foreach (QString f, companionFiles) {
        if (!files.contains(f))
            files.append(f);
    }

    StageFiles(Series, seriesRowID, files, sqrl);
    sqrl->Log(QString("  Added %1 series [%2] suffix [%3] task [%4] run [%5] with [%6] file(s)")
                  .arg(datatype).arg(seriesNum).arg(suffix).arg(series.BidsTask).arg(series.BidsRun).arg(files.size()));

    return seriesRowID;
}


/* ---------------------------------------------------------------------------- */
/* ----- ReadColumnSidecars --------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * BIDS .tsv files (participants, sessions, phenotype) describe their columns in a
 * sibling .json: {"column": {"Description": ..., "Units": ..., "Levels": {...}}}.
 * Later files override earlier ones key-by-key within each column, so a more
 * specific sidecar can change just one field.
 *
 * @brief Read and merge .tsv column-description sidecars
 * @param jsonFiles sidecar paths, least specific first (missing files are skipped)
 * @param sqrl squirrel object
 * @return merged {column: metadata} object
 */
QJsonObject bids::ReadColumnSidecars(QStringList jsonFiles, squirrel *sqrl) {
    QJsonObject colMeta;
    foreach (QString jf, jsonFiles) {
        if (!utils::FileExists(jf))
            continue;
        QJsonDocument doc = QJsonDocument::fromJson(utils::ReadTextFileToString(jf).toUtf8());
        if (!doc.isObject()) {
            sqrl->Log(QString("Warning: could not parse [%1] as JSON").arg(jf));
            continue;
        }
        QJsonObject obj = doc.object();
        for (auto it = obj.constBegin(); it != obj.constEnd(); ++it) {
            if (it.value().isObject() && colMeta.value(it.key()).isObject()) {
                QJsonObject merged = colMeta.value(it.key()).toObject();
                QJsonObject over = it.value().toObject();
                for (auto o = over.constBegin(); o != over.constEnd(); ++o)
                    merged.insert(o.key(), o.value());
                colMeta.insert(it.key(), merged);
            }
            else
                colMeta.insert(it.key(), it.value());
        }
    }
    return colMeta;
}


/* ---------------------------------------------------------------------------- */
/* ----- ApplyColumnMeta ------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * The observation Description is the column's 'Description' (or the column name if
 * there is none). Any other column metadata (Units, Levels, LongName, TermURL, ...)
 * is stored in the observation Notes as compact JSON.
 *
 * @brief Apply a .tsv column's sidecar metadata to an observation
 * @param obs the observation
 * @param col the column name
 * @param colMeta merged column metadata from ReadColumnSidecars()
 */
void bids::ApplyColumnMeta(squirrelObservation &obs, const QString &col, const QJsonObject &colMeta) {
    obs.Description = col;
    if (!colMeta.value(col).isObject())
        return;
    QJsonObject meta = colMeta.value(col).toObject();
    QString desc = meta.value("Description").toString().trimmed();
    if (!desc.isEmpty())
        obs.Description = desc;
    meta.remove("Description");
    if (!meta.isEmpty())
        obs.Notes = QString::fromUtf8(QJsonDocument(meta).toJson(QJsonDocument::Compact));
}


/* ---------------------------------------------------------------------------- */
/* ----- NormalizeSex --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Normalize a BIDS sex/gender value to squirrel's M/F/O/U convention
 * @param v the value from participants.tsv
 * @return M, F, O, U (unknown, for empty or n/a), or the original value if it is
 *         none of the standard spellings (eg a dataset-defined level)
 */
static QString NormalizeSex(QString v) {
    QString l = v.trimmed().toLower();
    if (l.isEmpty() || (l == "n/a") || (l == "na") || (l == "unknown") || (l == "u"))
        return "U";
    if ((l == "m") || (l == "male"))
        return "M";
    if ((l == "f") || (l == "female"))
        return "F";
    if ((l == "o") || (l == "other"))
        return "O";
    return v.trimmed();
}


/* ---------------------------------------------------------------------------- */
/* ----- AddToPackageNotes ---------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Store a value in the package Notes JSON, under bids.<section>.<key>
 * @param section eg "phenotype"
 * @param key eg the instrument name
 * @param value the JSON value to store
 * @param sqrl squirrel object
 */
void bids::AddToPackageNotes(QString section, QString key, QJsonValue value, squirrel *sqrl) {
    QJsonObject notes;
    QString existing = sqrl->Notes.trimmed();
    if (!existing.isEmpty()) {
        QJsonDocument doc = QJsonDocument::fromJson(existing.toUtf8());
        if (doc.isObject())
            notes = doc.object();
        else
            notes.insert("text", existing);
    }
    QJsonObject bidsMeta = notes.value("bids").toObject();
    QJsonObject sec = bidsMeta.value(section).toObject();
    sec.insert(key, value);
    bidsMeta.insert(section, sec);
    notes.insert("bids", bidsMeta);
    sqrl->Notes = QString::fromUtf8(QJsonDocument(notes).toJson(QJsonDocument::Compact));
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadParticipantFile -------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * 'sex' and 'gender' set the subject's Sex and Gender (normalized to M/F/O/U; Gender
 * is only set from a 'gender' column). Every other column becomes an observation
 * (InstrumentName 'participants'), described by participants.json. Empty and 'n/a'
 * values are not stored. Column names are matched case-insensitively. The age,
 * handedness, species and strain columns keep their established observation names
 * (age, Handedness, Species, Strain); other columns use the column name.
 *
 * @brief Loads the participants.tsv file
 * @param f the path to the file
 * @param sqrl squirrel object
 * @return true if successful, false otherwise
 */
bool bids::LoadParticipantsFile(QString f, squirrel *sqrl) {
    sqrl->Log(QString("Reading participants file [%1]").arg(f));

    utils::indexedHash tsv;
    QStringList cols;
    QString m;
    if (!utils::ParseTSV(utils::ReadTextFileToString(f), tsv, cols, m)) {
        sqrl->Log(QString("Error: Unable to read .tsv file [%1] message [%2]").arg(f).arg(m));
        return false;
    }

    QString jsonFile = f;
    jsonFile.replace(QRegularExpression("\\.tsv$"), ".json");
    QJsonObject colMeta = ReadColumnSidecars(QStringList() << jsonFile, sqrl);

    /* find columns case-insensitively (some datasets use 'Age', 'Gender', or a BOM) */
    auto findCol = [&cols](const QString &name) -> QString {
        foreach (QString c, cols) {
            QString clean = c;
            clean.remove(QChar(0xFEFF));
            if (clean.trimmed().toLower() == name)
                return c;
        }
        return QString();
    };
    QString idCol = findCol("participant_id");
    QString sexCol = findCol("sex");
    QString genderCol = findCol("gender");
    if (idCol.isEmpty()) {
        sqrl->Log(QString("Error: [%1] has no participant_id column").arg(f));
        return false;
    }

    static const QHash<QString, QString> legacyNames = {
        {"age", "age"}, {"handedness", "Handedness"}, {"species", "Species"}, {"strain", "Strain"}
    };

    int obsCount = 0;
    for (int i = 0; i < tsv.size(); i++) {
        QString id = tsv[i].value(idCol).trimmed();
        if (id.isEmpty())
            continue;

        /* add (or update) the subject */
        squirrelSubject sqrlSubj(sqrl->GetDatabaseUUID());
        qint64 subjectRowID = sqrl->FindSubject(id);
        if (subjectRowID >= 0) {
            sqrlSubj.SetObjectID(subjectRowID);
            sqrlSubj.Get();
        }
        sqrlSubj.ID = id;
        if (!sexCol.isEmpty())
            sqrlSubj.Sex = NormalizeSex(tsv[i].value(sexCol));
        if (!genderCol.isEmpty())
            sqrlSubj.Gender = NormalizeSex(tsv[i].value(genderCol));
        sqrlSubj.Store();
        subjectRowID = sqrlSubj.GetObjectID();

        /* every other column becomes an observation */
        foreach (QString col, cols) {
            if ((col == idCol) || (col == sexCol) || (col == genderCol))
                continue;
            QString val = tsv[i].value(col).trimmed();
            if (val.isEmpty() || (val.toLower() == "n/a"))
                continue;

            QString key = col;
            key.remove(QChar(0xFEFF));
            key = key.trimmed();

            squirrelObservation obs(sqrl->GetDatabaseUUID());
            obs.subjectRowID = subjectRowID;
            obs.InstrumentName = "participants";
            obs.ObservationName = legacyNames.value(key.toLower(), key);
            ApplyColumnMeta(obs, col, colMeta);
            obs.Value = val;
            obs.Store();
            obsCount++;
        }

        sqrl->Debug(QString("Read subject ID [%1] sex [%2]. Stored in squirrel with SubjectRowID [%3]").arg(id).arg(sqrlSubj.Sex).arg(subjectRowID), __FUNCTION__);
    }
    sqrl->Log(QString("  Read [%1] participants, [%2] observations").arg(tsv.size()).arg(obsCount));

    return true;
}



/* ---------------------------------------------------------------------------- */
/* ----- LoadTaskFile --------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Load a file containing task information
 * @param f the file path
 * @param sqrl squirrel object
 * @return true if successful, false otherwise
 */
bool bids::LoadTaskFile(QString f, squirrel *sqrl) {

    sqrl->Log(QString("Reading task file [%1]").arg(f));

    /* all root files for one task (task-rest_bold.json, task-rest_acq-x_bold.json,
     * task-rest_events.json, ...) go into one experiment, named by the BIDS task label
     * (which is also each series' BidsTask) */
    QHash<QString, QString> ents;
    QString suffix, ext;
    ParseBidsFilename(QFileInfo(f).fileName(), ents, suffix, ext);
    QString experimentName = ents.value("task").trimmed();
    if (experimentName.isEmpty()) {
        sqrl->Log(QString("Warning: [%1] has no task- label; not added to an experiment").arg(f));
        return false;
    }

    squirrelExperiment exp(sqrl->GetDatabaseUUID());
    qint64 expRowID = sqrl->FindExperiment(experimentName);
    if (expRowID >= 0) {
        exp.SetObjectID(expRowID);
        exp.Get();
    }
    exp.ExperimentName = experimentName;
    exp.FileCount += 1;
    exp.Size += QFileInfo(f).size();
    exp.Store();
    expRowID = exp.GetObjectID();

    if (StageFiles(Experiment, expRowID, QStringList() << f, sqrl) || utils::IsMissingContent(f))
        usedRootFiles.insert(f);
    sqrl->Debug(QString("Added [%1] to experiment [%2] (now %3 file(s))").arg(QFileInfo(f).fileName()).arg(experimentName).arg(exp.FileCount), __FUNCTION__);

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadDatasetDescription ----------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Parse dataset_description.json into squirrel package fields
 *
 * Maps Name -> PackageName, License -> License, and preserves the remaining BIDS
 * dataset metadata (Authors, BIDSVersion, DatasetDOI, Funding, etc) as a JSON
 * object under the 'bids' key in the package Notes field.
 *
 * @param f path to dataset_description.json
 * @param sqrl squirrel object
 * @return true if parsed as JSON, false if it fell back to raw text
 */
bool bids::LoadDatasetDescription(QString f, squirrel *sqrl) {
    sqrl->Log(QString("Reading dataset_description.json [%1]").arg(f));

    QString str = utils::ReadTextFileToString(f);
    QJsonDocument doc = QJsonDocument::fromJson(str.toUtf8());
    if (!doc.isObject()) {
        sqrl->Log(QString("Could not parse dataset_description.json as JSON; storing raw text in Description"));
        sqrl->Description = utils::CleanJSON(str);
        return false;
    }
    QJsonObject root = doc.object();

    QString name = root.value("Name").toString().trimmed();
    if (!name.isEmpty()) {
        sqrl->PackageName = name;
        /* fill Description from the dataset Name if it is still empty or the default
         * placeholder (BIDS has no separate dataset-level description field) */
        QString desc = sqrl->Description.trimmed();
        if (desc.isEmpty() || (desc == "Squirrel package"))
            sqrl->Description = name;
    }

    datasetType = root.value("DatasetType").toString().trimmed();
    generatedByName.clear();
    QJsonArray generatedBy = root.value("GeneratedBy").toArray();
    if (!generatedBy.isEmpty())
        generatedByName = generatedBy.first().toObject().value("Name").toString().trimmed();

    QString license = root.value("License").toString().trimmed();
    if (!license.isEmpty())
        sqrl->License = license;

    /* preserve the remaining dataset metadata as a JSON object in Notes */
    QJsonObject bidsMeta;
    foreach (const QString &key, QStringList({"BIDSVersion", "DatasetType", "DatasetDOI",
                                              "Acknowledgements", "HowToAcknowledge",
                                              "Authors", "Funding", "ReferencesAndLinks",
                                              "EthicsApprovals", "GeneratedBy", "SourceDatasets"})) {
        if (root.contains(key))
            bidsMeta.insert(key, root.value(key));
    }
    if (!bidsMeta.isEmpty()) {
        QJsonObject notes;
        notes.insert("bids", bidsMeta);
        sqrl->Notes = QString::fromUtf8(QJsonDocument(notes).toJson(QJsonDocument::Compact));
    }

    sqrl->Log(QString("Parsed dataset_description.json: Name [%1] License [%2]").arg(name).arg(license.left(40)));
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- ParseSubjectDirName -------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Derivatives name subject directories either 'sub-01' (sessions as ses-* children)
 * or 'sub-01_ses-pre' (subject and session joined, eg BrainVisa/FreeSurfer outputs).
 *
 * @brief Parse a derivative subject directory name
 * @param name directory name
 * @param subjectID [out] eg 'sub-01'
 * @param sesLabel [out] eg 'pre', or empty
 * @return true if the name is a subject directory name
 */
static bool ParseSubjectDirName(const QString &name, QString &subjectID, QString &sesLabel) {
    static const QRegularExpression rx("^(sub-[^_]+)(?:_ses-([^_]+))?$");
    QRegularExpressionMatch m = rx.match(name);
    if (!m.hasMatch())
        return false;
    subjectID = m.captured(1);
    sesLabel = m.captured(2);
    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadDerivatives ------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Import a BIDS 'derivatives' directory as squirrel pipelines + analyses
 *
 * Each derivatives/<name>/ directory becomes a squirrel pipeline. Within it, every
 * directory for a known subject (sub-01, or sub-01_ses-pre), found up to a few levels
 * deep (eg freesurfer/subjects/sub-01), becomes an analysis attached to that subject's
 * study. Outputs that belong to no subject (fsaverage, template subjects, group
 * statistics) become a group-analysis of the same name. If derivatives/ holds sub-*
 * directories itself, it is treated as a pipeline named 'derivatives'.
 *
 * @param derivativesDir path to the derivatives directory
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadDerivatives(QString derivativesDir, squirrel *sqrl) {
    sqrl->Log(QString("Loading derivatives from [%1]").arg(derivativesDir));
    derivAnalyses.clear();

    QStringList children = NaturalSort(BidsDirs(derivativesDir, "*"));

    /* derivatives/sub-* (no pipeline directory): derivatives/ is itself the pipeline */
    bool hasSubjects = false;
    foreach (QString c, children) {
        if (c.startsWith("sub-"))
            hasSubjects = true;
    }
    if (hasSubjects)
        LoadDerivativePipeline(derivativesDir, "derivatives", true, sqrl);

    foreach (QString pipelineName, children) {
        if (pipelineName.startsWith("sub-"))
            continue;
        LoadDerivativePipeline(QString("%1/%2").arg(derivativesDir).arg(pipelineName), pipelineName, false, sqrl);
    }

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadDerivativePipeline ----------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Import one derivative pipeline directory
 * @param pdir the pipeline directory
 * @param pipelineName the pipeline name
 * @param subjectsOnly only import sub-* children and files (pdir is the derivatives/
 *        directory itself, whose other children are separate pipelines)
 * @param sqrl squirrel object
 * @param excludeDirs top-level directories of pdir to skip (imported elsewhere)
 * @return the pipeline row id
 */
qint64 bids::LoadDerivativePipeline(QString pdir, QString pipelineName, bool subjectsOnly, squirrel *sqrl, QStringList excludeDirs) {

    /* create the pipeline if it doesn't already exist */
    qint64 pipelineRowID = sqrl->FindPipeline(pipelineName);
    if (pipelineRowID < 0) {
        squirrelPipeline p(sqrl->GetDatabaseUUID());
        p.PipelineName = pipelineName;
        p.PipelineVersion = 1;

        /* a derivative may describe itself in its own dataset_description.json */
        QString dd = QString("%1/dataset_description.json").arg(pdir);
        if (utils::FileExists(dd)) {
            QJsonObject root = QJsonDocument::fromJson(utils::ReadTextFileToString(dd).toUtf8()).object();
            p.PipelineDescription = root.value("Name").toString();
            QJsonArray gb = root.value("GeneratedBy").toArray();
            if (!gb.isEmpty()) {
                QJsonObject g0 = gb.first().toObject();
                QString gname = g0.value("Name").toString();
                QString gver = g0.value("Version").toString();
                if (!gname.isEmpty())
                    p.PipelineDescription = gname + (gver.isEmpty() ? "" : (" " + gver));
            }
        }
        if (p.PipelineDescription.trimmed().isEmpty())
            p.PipelineDescription = "BIDS derivative: " + pipelineName;

        p.Store();
        pipelineRowID = p.GetObjectID();
        sqrl->Log(QString("  Created pipeline [%1]").arg(pipelineName));
    }

    /* find the subject directories, and everything else */
    QStringList subjectDirs, leftovers;
    if (subjectsOnly) {
        foreach (QString e, ListEntries(pdir)) {
            QFileInfo fi(e);
            QString subjectID, sesLabel;
            if (fi.isDir() && ParseSubjectDirName(fi.fileName(), subjectID, sesLabel) && (sqrl->FindSubject(subjectID) >= 0))
                subjectDirs.append(e);
            else if (fi.isDir() && fi.fileName().startsWith("sub-"))
                leftovers.append(e);
            else if (!fi.isDir())
                leftovers.append(e);
        }
    }
    else
        ScanDerivativeDir(pdir, 0, subjectDirs, leftovers, sqrl);

    /* skip top-level directories that are imported elsewhere */
    if (!excludeDirs.isEmpty()) {
        QStringList kept;
        foreach (QString e, leftovers) {
            QFileInfo fi(e);
            if (fi.isDir() && (fi.absolutePath() == QFileInfo(pdir).absoluteFilePath()) && excludeDirs.contains(fi.fileName()))
                continue;
            kept.append(e);
        }
        leftovers = kept;
    }

    /* files directly in the pipeline directory (dataset_description.json, README, ...)
     * stay with the pipeline; other non-subject outputs become a group-analysis */
    QStringList pipelineFiles, groupEntries;
    foreach (QString e, leftovers) {
        if ((QFileInfo(e).absolutePath() == QFileInfo(pdir).absoluteFilePath()) && QFileInfo(e).isFile())
            pipelineFiles.append(e);
        else
            groupEntries.append(e);
    }
    if (!pipelineFiles.isEmpty())
        StageFiles(Pipeline, pipelineRowID, pipelineFiles, sqrl);

    if (!groupEntries.isEmpty()) {
        qint64 groupRowID = sqrl->FindGroupAnalysis(pipelineName);
        squirrelGroupAnalysis g(sqrl->GetDatabaseUUID());
        if (groupRowID >= 0) {
            g.SetObjectID(groupRowID);
            g.Get();
        }
        g.GroupAnalysisName = pipelineName;
        g.Description = QString("Group-level outputs of BIDS derivative [%1]").arg(pipelineName);
        foreach (QString e, groupEntries) {
            qint64 c(0), b(0);
            utils::GetPathSizeAndFileCount(e, c, b);
            g.FileCount += c;
            g.Size += b;
        }
        g.Store();
        groupRowID = g.GetObjectID();
        StageFiles(GroupAnalysis, groupRowID, groupEntries, sqrl);

        /* keep each entry's location within the pipeline directory, so eg
         * freesurfer/subjects/fsaverage is stored as group-analysis/freesurfer/subjects/fsaverage */
        foreach (QString e, groupEntries) {
            QString parentRel = QDir(pdir).relativeFilePath(QFileInfo(e).absolutePath());
            if (!parentRel.isEmpty() && (parentRel != "."))
                utils::SetStagedFileSubdir(sqrl->GetDatabaseUUID(), groupRowID, GroupAnalysis, e, parentRel);
        }

        QStringList names;
        foreach (QString e, groupEntries)
            names.append(QDir(pdir).relativeFilePath(e));
        sqrl->Log(QString("  Added group-analysis [%1] with [%2] file(s): %3").arg(pipelineName).arg(g.FileCount).arg(names.join(", ")));
    }

    /* per-subject outputs become analyses */
    foreach (QString sdir, subjectDirs) {
        QString subjectID, sesLabel;
        ParseSubjectDirName(QFileInfo(sdir).fileName(), subjectID, sesLabel);
        qint64 subjectRowID = sqrl->FindSubject(subjectID);

        /* sub-01_ses-pre: the session is in the directory name */
        if (!sesLabel.isEmpty()) {
            qint64 studyRowID = ResolveDerivativeStudy(subjectRowID, sesLabel, sqrl);
            StageAnalysisEntries(ListEntries(sdir), pipelineName, pipelineRowID, studyRowID, sqrl);
            continue;
        }

        /* sub-01/ses-*: each session maps to its study. Anything at the subject level
         * (eg a longitudinal anat/) goes with the first session's analysis */
        QStringList sesDirs, subjectLevel;
        foreach (QString e, ListEntries(sdir)) {
            if (QFileInfo(e).isDir() && QFileInfo(e).fileName().startsWith("ses-"))
                sesDirs.append(e);
            else
                subjectLevel.append(e);
        }
        if (!sesDirs.isEmpty()) {
            qint64 firstStudyRowID = -1;
            foreach (QString sesPath, sesDirs) {
                QString sesName = QFileInfo(sesPath).fileName();
                qint64 studyRowID = ResolveDerivativeStudy(subjectRowID, sesName.mid(4), sqrl);
                if (firstStudyRowID < 0)
                    firstStudyRowID = studyRowID;
                StageAnalysisEntries(ListEntries(sesPath), pipelineName, pipelineRowID, studyRowID, sqrl);
            }
            if (!subjectLevel.isEmpty())
                StageAnalysisEntries(subjectLevel, pipelineName, pipelineRowID, firstStudyRowID, sqrl);
        }
        else {
            qint64 studyRowID = ResolveDerivativeStudy(subjectRowID, "", sqrl);
            StageAnalysisEntries(subjectLevel, pipelineName, pipelineRowID, studyRowID, sqrl);
        }
    }

    return pipelineRowID;
}


/* ---------------------------------------------------------------------------- */
/* ----- ScanDerivativeDir ---------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Descends into directories that contain subject directories (eg freesurfer/subjects/)
 * up to a fixed depth. Directories for subjects not in the dataset (sub-Talairach,
 * fsaverage) are leftovers, as is anything else.
 *
 * @brief Split a derivative directory into known-subject directories and everything else
 * @param dir directory to scan
 * @param depth current depth below the pipeline directory
 * @param subjectDirs [out] directories of known subjects
 * @param leftovers [out] all other files/directories (not descended into)
 * @param sqrl squirrel object
 */
void bids::ScanDerivativeDir(QString dir, int depth, QStringList &subjectDirs, QStringList &leftovers, squirrel *sqrl) {
    const int maxDepth = 3;

    foreach (QString e, ListEntries(dir)) {
        QFileInfo fi(e);
        if (!fi.isDir()) {
            leftovers.append(e);
            continue;
        }
        QString subjectID, sesLabel;
        if (ParseSubjectDirName(fi.fileName(), subjectID, sesLabel)) {
            if (sqrl->FindSubject(subjectID) >= 0)
                subjectDirs.append(e);
            else {
                sqrl->Log(QString("  Derivative directory [%1] is not a subject in this dataset; storing it as group-level output").arg(fi.fileName()));
                leftovers.append(e);
            }
            continue;
        }
        if ((depth < maxDepth) && ContainsSubjectDir(e, depth + 1, sqrl))
            ScanDerivativeDir(e, depth + 1, subjectDirs, leftovers, sqrl);
        else
            leftovers.append(e);
    }
}


/* ---------------------------------------------------------------------------- */
/* ----- ContainsSubjectDir --------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Does this directory contain (within the depth limit) a known subject's directory?
 * @param dir directory to check
 * @param depth depth of dir below the pipeline directory
 * @param sqrl squirrel object
 * @return true if a known subject directory was found
 */
bool bids::ContainsSubjectDir(QString dir, int depth, squirrel *sqrl) {
    const int maxDepth = 3;

    foreach (QString d, BidsDirs(dir, "*")) {
        QString subjectID, sesLabel;
        if (ParseSubjectDirName(d, subjectID, sesLabel) && (sqrl->FindSubject(subjectID) >= 0))
            return true;
        if ((depth < maxDepth) && ContainsSubjectDir(QString("%1/%2").arg(dir).arg(d), depth + 1, sqrl))
            return true;
    }
    return false;
}


/* ---------------------------------------------------------------------------- */
/* ----- ResolveDerivativeStudy ----------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Find the study a derivative analysis should attach to, creating one if needed
 *
 * Matches the session label exactly, then as an unambiguous prefix (derivatives
 * often abbreviate, eg 'ses-pre' for 'ses-preimp'), then falls back to the subject's
 * first study.
 *
 * @param subjectRowID parent subject
 * @param sesLabel session/visit label to match (empty = no session)
 * @param sqrl squirrel object
 * @return the study row id
 */
qint64 bids::ResolveDerivativeStudy(qint64 subjectRowID, QString sesLabel, squirrel *sqrl) {
    QList<squirrelStudy> studies = sqrl->GetStudyList(subjectRowID);

    if (!sesLabel.isEmpty()) {
        /* exact match on the visit type */
        foreach (squirrelStudy s, studies) {
            if (s.VisitType == sesLabel)
                return s.GetObjectID();
        }
        /* unambiguous prefix match (not for a derivative dataset, whose studies are
         * created from its own session labels, so 'ses-1' must not match 'ses-10') */
        QList<qint64> prefixMatches;
        foreach (squirrelStudy s, studies) {
            if (!derivativeRootMode && s.VisitType.startsWith(sesLabel))
                prefixMatches.append(s.GetObjectID());
        }
        if (prefixMatches.size() == 1)
            return prefixMatches.first();
        /* a derivative dataset has no raw studies: every session gets its own study */
        if (derivativeRootMode)
            studies.clear();
        else
            sqrl->Log(QString("  Derivative session [ses-%1] matches no study; using the subject's first study").arg(sesLabel));
    }
    /* otherwise use the subject's first study */
    if (!studies.isEmpty())
        return studies.first().GetObjectID();

    /* no study exists (derivative-only subject); create one */
    squirrelSubject subj(sqrl->GetDatabaseUUID());
    subj.SetObjectID(subjectRowID);
    subj.Get();

    squirrelStudy st(sqrl->GetDatabaseUUID());
    st.subjectRowID = subjectRowID;
    st.StudyNumber = subj.GetNextStudyNumber();
    st.Modality = "Derived";
    st.VisitType = sesLabel;
    st.DateTime = QDateTime(); /* unknown; not the conversion time */
    st.Store();
    sqrl->Log(QString("  Created derived study [%1] for subject [%2]").arg(st.StudyNumber).arg(subj.ID));
    return st.GetObjectID();
}


/* ---------------------------------------------------------------------------- */
/* ----- StageAnalysisEntries ------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * There is one analysis per (pipeline, study); entries from several derivative
 * directories that resolve to the same study are added to the same analysis.
 * Directories are staged whole and copied with their structure intact.
 *
 * @brief Stage derivative files/directories into the analysis for a pipeline + study
 * @param entries files and directories to stage
 * @param pipelineName name of the pipeline
 * @param pipelineRowID parent pipeline row id
 * @param studyRowID parent study row id
 * @param sqrl squirrel object
 * @return the analysis row id
 */
qint64 bids::StageAnalysisEntries(QStringList entries, QString pipelineName, qint64 pipelineRowID, qint64 studyRowID, squirrel *sqrl) {
    QPair<qint64, qint64> key(pipelineRowID, studyRowID);
    qint64 analysisRowID = derivAnalyses.value(key, -1);
    if (analysisRowID < 0) {
        squirrelAnalysis a(sqrl->GetDatabaseUUID());
        a.AnalysisName = pipelineName;
        a.PipelineName = pipelineName;
        a.PipelineVersion = 1;
        a.studyRowID = studyRowID;
        a.pipelineRowID = pipelineRowID;
        a.StatusMessage = "Imported from BIDS derivatives";
        a.Store();
        analysisRowID = a.GetObjectID();
        derivAnalyses.insert(key, analysisRowID);
    }

    if (!entries.isEmpty())
        StageFiles(Analysis, analysisRowID, entries, sqrl);

    qint64 fileCount(0);
    foreach (QString e, entries) {
        qint64 c(0), b(0);
        utils::GetPathSizeAndFileCount(e, c, b);
        fileCount += c;
    }
    sqrl->Log(QString("  Added [%1] file(s) to analysis [%2] -> studyRowID [%3]").arg(fileCount).arg(pipelineName).arg(studyRowID));
    return analysisRowID;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadSessionsFile ----------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Apply a sub-*_sessions.tsv file to a subject's studies
 *
 * Sets each study's acquisition datetime from the 'acq_time' column, and stores any
 * remaining (session-level) columns as subject observations, prefixed with the
 * session label so repeated measures across sessions stay unique.
 *
 * Column metadata comes from the sessions.json sidecar: the dataset-root sessions.json
 * (applies to every subject) overridden by the subject's own sub-*_sessions.json. Each
 * observation's Description is the column's 'Description', and any other column
 * metadata (Units, Levels, LongName, TermURL, ...) is stored in the observation Notes
 * as JSON. The merged sidecar itself is kept in the subject Notes (bids.sessions.json),
 * so descriptions of columns that are not observations (session_id, acq_time) are kept.
 *
 * @param f path to the sessions.tsv file (may not exist if only a sessions.json does)
 * @param subjectRowID the parent subject
 * @param sqrl squirrel object
 * @return true if parsed
 */
bool bids::LoadSessionsFile(QString f, qint64 subjectRowID, squirrel *sqrl) {
    sqrl->Log(QString("Reading sessions file [%1]").arg(f));

    /* column metadata: root sessions.json, then the subject's sidecar (more specific wins) */
    QString subjectJson = f;
    subjectJson.replace(QRegularExpression("\\.tsv$"), ".json");
    QJsonObject colMeta = ReadColumnSidecars(QStringList({rootDir + "/sessions.json", subjectJson}), sqrl);
    if (utils::FileExists(rootDir + "/sessions.json"))
        usedRootFiles.insert(rootDir + "/sessions.json");

    /* keep the merged sidecar in the subject's Notes, so nothing in it is lost */
    if (!colMeta.isEmpty()) {
        squirrelSubject subj(sqrl->GetDatabaseUUID());
        subj.SetObjectID(subjectRowID);
        if (subj.Get()) {
            QJsonObject notes;
            QString existing = subj.Notes.trimmed();
            if (!existing.isEmpty()) {
                QJsonDocument doc = QJsonDocument::fromJson(existing.toUtf8());
                if (doc.isObject())
                    notes = doc.object();
                else
                    notes.insert("text", existing);
            }
            QJsonObject bidsMeta = notes.value("bids").toObject();
            bidsMeta.insert("sessions.json", colMeta);
            notes.insert("bids", bidsMeta);
            subj.Notes = QString::fromUtf8(QJsonDocument(notes).toJson(QJsonDocument::Compact));
            subj.Store();
        }
    }

    if (!utils::FileExists(f))
        return true;

    utils::indexedHash tsv;
    QStringList cols;
    QString m;
    if (!utils::ParseTSV(utils::ReadTextFileToString(f), tsv, cols, m)) {
        sqrl->Log(QString("Error reading sessions.tsv [%1]: %2").arg(f).arg(m));
        return false;
    }

    QList<squirrelStudy> studies = sqrl->GetStudyList(subjectRowID);

    for (int i = 0; i < tsv.size(); i++) {
        QString sesid = tsv[i].value("session_id").trimmed();
        QString sesLabel = sesid.startsWith("ses-") ? sesid.mid(4) : sesid;
        QString acqTime = tsv[i].value("acq_time").trimmed();

        /* find the study whose visit type matches this session */
        qint64 studyRowID = -1;
        foreach (squirrelStudy s, studies) {
            if (s.VisitType == sesLabel) {
                studyRowID = s.GetObjectID();
                break;
            }
        }

        /* set the study acquisition datetime from acq_time */
        QDateTime dt;
        if (!acqTime.isEmpty() && (acqTime.toLower() != "n/a")) {
            dt = QDateTime::fromString(acqTime, Qt::ISODate);
            if (dt.isValid() && (studyRowID >= 0)) {
                QSqlQuery q(QSqlDatabase::database(sqrl->GetDatabaseUUID()));
                q.prepare("update Study set Datetime = :dt where StudyRowID = :id");
                q.bindValue(":dt", dt);
                q.bindValue(":id", studyRowID);
                utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                sqrl->Log(QString("  Set study [%1] datetime to [%2]").arg(sesLabel).arg(dt.toString(Qt::ISODate)));
            }
        }

        /* remaining columns become subject observations */
        foreach (QString col, cols) {
            if ((col == "session_id") || (col == "acq_time") || (col == "subject_id"))
                continue;
            QString val = tsv[i].value(col).trimmed();
            if (val.isEmpty() || (val.toLower() == "n/a"))
                continue;

            squirrelObservation obs(sqrl->GetDatabaseUUID());
            obs.subjectRowID = subjectRowID;
            obs.InstrumentName = "sessions";
            obs.ObservationName = sesLabel.isEmpty() ? col : QString("%1_%2").arg(sesLabel).arg(col);
            ApplyColumnMeta(obs, col, colMeta);
            obs.Value = val;
            if (dt.isValid())
                obs.DateStart = dt;
            obs.Store();
        }
    }

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadPhenotypeDir ----------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Import a BIDS 'phenotype' directory as subject observations
 *
 * Each phenotype/<instrument>.tsv contributes one observation per measure column
 * per participant. Column metadata comes from the sibling .json (Description ->
 * observation Description; Units, Levels, ... -> observation Notes), and the whole
 * .json (including MeasurementToolMetadata) is kept in the package Notes under
 * bids.phenotype.<instrument>. Repeated-measure rows are disambiguated by an index
 * column (day/session/visit/...) or row ordinal.
 *
 * @param phenotypeDir path to the phenotype directory
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadPhenotypeDir(QString phenotypeDir, squirrel *sqrl) {
    sqrl->Log(QString("Loading phenotype from [%1]").arg(phenotypeDir));

    static const QStringList idxCols = {"session_id", "ses", "day", "visit", "timepoint", "run", "wave"};

    QStringList tsvFiles = BidsFiles(phenotypeDir, "*.tsv");
    foreach (QString tsvFile, tsvFiles) {
        QString instrument = QFileInfo(tsvFile).fileName();
        instrument.replace(".tsv", "");

        /* read the sibling .json for per-column metadata, and keep the whole file */
        QString jsonFile = tsvFile;
        jsonFile.replace(QRegularExpression("\\.tsv$"), ".json");
        QJsonObject colMeta = ReadColumnSidecars(QStringList() << jsonFile, sqrl);
        if (!colMeta.isEmpty())
            AddToPackageNotes("phenotype", instrument, colMeta, sqrl);

        utils::indexedHash tsv;
        QStringList cols;
        QString m;
        if (!utils::ParseTSV(utils::ReadTextFileToString(tsvFile), tsv, cols, m)) {
            sqrl->Log(QString("Error reading phenotype tsv [%1]: %2").arg(tsvFile).arg(m));
            continue;
        }

        /* count rows per participant so repeated measures can be disambiguated */
        QHash<QString, int> rowCount, rowSeen;
        for (int i = 0; i < tsv.size(); i++)
            rowCount[tsv[i].value("participant_id")]++;

        for (int i = 0; i < tsv.size(); i++) {
            QString pid = tsv[i].value("participant_id").trimmed();
            qint64 subjectRowID = sqrl->FindSubject(pid);
            if (subjectRowID < 0)
                continue;

            /* build a discriminator from an index column, or a row ordinal */
            QString disc;
            foreach (const QString &ic, idxCols) {
                if (cols.contains(ic)) {
                    QString v = tsv[i].value(ic).trimmed();
                    if (!v.isEmpty() && (v.toLower() != "n/a")) {
                        disc = QString("%1-%2").arg(ic).arg(v);
                        break;
                    }
                }
            }
            if (disc.isEmpty() && (rowCount.value(pid) > 1))
                disc = QString("row-%1").arg(++rowSeen[pid]);

            /* optional acquisition date for the observation */
            QDateTime dt;
            if (cols.contains("acq_time")) {
                QString at = tsv[i].value("acq_time").trimmed();
                if (!at.isEmpty() && (at.toLower() != "n/a"))
                    dt = QDateTime::fromString(at, Qt::ISODate);
            }

            foreach (QString col, cols) {
                if (col == "participant_id")
                    continue;
                QString val = tsv[i].value(col).trimmed();
                if (val.isEmpty() || (val.toLower() == "n/a"))
                    continue;

                squirrelObservation obs(sqrl->GetDatabaseUUID());
                obs.subjectRowID = subjectRowID;
                obs.InstrumentName = instrument;
                /* prefix with the instrument and suffix with the discriminator so the
                 * (ObservationName, DateStart) uniqueness holds across instruments and rows */
                obs.ObservationName = QString("%1_%2%3").arg(instrument).arg(col).arg(disc.isEmpty() ? "" : ("_" + disc));
                ApplyColumnMeta(obs, col, colMeta);
                obs.Value = val;
                if (dt.isValid())
                    obs.DateStart = dt;
                obs.Store();
            }
        }
        sqrl->Log(QString("  Imported phenotype instrument [%1] (%2 rows)").arg(instrument).arg(tsv.size()));
    }

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadStimuliDir ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Import the BIDS 'stimuli' directory as a squirrel experiment named 'stimuli'
 * @param stimuliDir path to the stimuli directory
 * @param sqrl squirrel object
 * @return true if staged
 */
bool bids::LoadStimuliDir(QString stimuliDir, squirrel *sqrl) {
    sqrl->Log(QString("Loading stimuli from [%1]").arg(stimuliDir));

    /* top-level files and directories; directories keep their structure */
    QStringList entries = ListEntries(stimuliDir);
    if (entries.isEmpty())
        return false;

    qint64 fileCount(0), size(0);
    foreach (QString e, entries) {
        qint64 c(0), b(0);
        utils::GetPathSizeAndFileCount(e, c, b);
        fileCount += c;
        size += b;
    }

    squirrelExperiment exp(sqrl->GetDatabaseUUID());
    qint64 expRowID = sqrl->FindExperiment("stimuli");
    if (expRowID >= 0) {
        exp.SetObjectID(expRowID);
        exp.Get();
    }
    exp.ExperimentName = "stimuli";
    exp.FileCount += fileCount;
    exp.Size += size;
    exp.Store();
    expRowID = exp.GetObjectID();

    bool ok = StageFiles(Experiment, expRowID, entries, sqrl);
    sqrl->Log(QString("  Added experiment [stimuli] with [%1] file(s)").arg(fileCount));
    return ok;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadCodeDir ---------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Import the BIDS 'code' directory as a squirrel pipeline named 'bids-code'
 * @param codeDir path to the code directory
 * @param sqrl squirrel object
 * @return true if staged
 */
bool bids::LoadCodeDir(QString codeDir, squirrel *sqrl) {
    sqrl->Log(QString("Loading code from [%1]").arg(codeDir));

    QStringList entries = ListEntries(codeDir);
    if (entries.isEmpty())
        return false;

    qint64 pipelineRowID = sqrl->FindPipeline("bids-code");
    if (pipelineRowID < 0) {
        squirrelPipeline p(sqrl->GetDatabaseUUID());
        p.PipelineName = "bids-code";
        p.PipelineVersion = 1;
        p.PipelineDescription = "Contents of the BIDS 'code' directory";
        p.Store();
        pipelineRowID = p.GetObjectID();
    }

    bool ok = StageFiles(Pipeline, pipelineRowID, entries, sqrl);
    sqrl->Log(QString("  Added pipeline [bids-code] with [%1] top-level file(s)/directories").arg(entries.size()));
    return ok;
}


/* ---------------------------------------------------------------------------- */
/* ----- StoreDatasetFilesInNotes --------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Dataset-level files that have no squirrel object of their own (LICENSE,
 * CITATION.cff, participants.json, samples.tsv, unused root sidecars, ...) are kept
 * in the package Notes, under bids.files, as {filename: contents}. JSON files are
 * stored as parsed JSON, other text files as strings. Binary or very large files
 * are listed with their size but not stored.
 *
 * @brief Store leftover dataset-root files in the package Notes
 * @param rootfiles all files in the BIDS root directory
 * @param sqrl squirrel object
 */
void bids::StoreDatasetFilesInNotes(QStringList rootfiles, squirrel *sqrl) {
    /* parsed into package/subject fields by LoadRootFiles */
    static const QStringList parsed = {"dataset_description.json", "participants.tsv", "README", "README.md", "CHANGES"};
    const qint64 maxSize = 256 * 1024;

    QJsonObject files;
    foreach (QString f, rootfiles) {
        QFileInfo fi(f);
        QString fn = fi.fileName();
        if (parsed.contains(fn) || usedRootFiles.contains(f))
            continue;

        QFile file(f);
        if (!file.open(QIODevice::ReadOnly))
            continue;
        QByteArray data = (fi.size() <= maxSize) ? file.readAll() : QByteArray();
        file.close();

        bool binary = data.left(8192).contains('\0');
        if ((fi.size() > maxSize) || binary) {
            QJsonObject info;
            info.insert("size", fi.size());
            info.insert("stored", false);
            info.insert("reason", binary ? "binary file" : "larger than 256KB");
            files.insert(fn, info);
            continue;
        }

        if (fn.endsWith(".json")) {
            QJsonDocument doc = QJsonDocument::fromJson(data);
            if (doc.isObject()) { files.insert(fn, doc.object()); continue; }
            if (doc.isArray()) { files.insert(fn, doc.array()); continue; }
        }
        files.insert(fn, QString::fromUtf8(data));
    }

    if (files.isEmpty())
        return;

    /* merge into the existing Notes JSON ({"bids": {...}}) written by LoadDatasetDescription */
    QJsonObject notes;
    QString existing = sqrl->Notes.trimmed();
    if (!existing.isEmpty()) {
        QJsonDocument doc = QJsonDocument::fromJson(existing.toUtf8());
        if (doc.isObject())
            notes = doc.object();
        else
            notes.insert("text", existing);
    }
    QJsonObject bidsMeta = notes.value("bids").toObject();
    bidsMeta.insert("files", files);
    notes.insert("bids", bidsMeta);
    sqrl->Notes = QString::fromUtf8(QJsonDocument(notes).toJson(QJsonDocument::Compact));

    sqrl->Log(QString("Stored [%1] dataset-level file(s) in package Notes: %2").arg(files.size()).arg(QStringList(files.keys()).join(", ")));
}


/* ---------------------------------------------------------------------------- */
/* ----- OrderSessionsByAcqTime ----------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Session labels don't always sort into acquisition order (eg 'pre'/'post', or
 * 'baseline'/'followup'). If the subject's sessions.tsv gives a valid acq_time for
 * every session, the sessions are ordered by it (so study 1 is the first session
 * acquired); otherwise the given (natural label) order is kept.
 *
 * @brief Order a subject's ses-* directories by their sessions.tsv acq_time
 * @param sesdirs ses-* directory names, in natural order
 * @param sessionsFile path to the subject's sessions.tsv (may not exist)
 * @param sqrl squirrel object
 * @return the ordered directory names
 */
QStringList bids::OrderSessionsByAcqTime(QStringList sesdirs, QString sessionsFile, squirrel *sqrl) {
    if ((sesdirs.size() < 2) || !utils::FileExists(sessionsFile))
        return sesdirs;

    utils::indexedHash tsv;
    QStringList cols;
    QString m;
    if (!utils::ParseTSV(utils::ReadTextFileToString(sessionsFile), tsv, cols, m) || !cols.contains("acq_time"))
        return sesdirs;

    QHash<QString, QDateTime> acq;
    for (int i = 0; i < tsv.size(); i++) {
        QString sesid = tsv[i].value("session_id").trimmed();
        if (!sesid.startsWith("ses-"))
            sesid = "ses-" + sesid;
        QDateTime dt = QDateTime::fromString(tsv[i].value("acq_time").trimmed(), Qt::ISODate);
        if (dt.isValid())
            acq.insert(sesid, dt);
    }
    foreach (QString s, sesdirs) {
        if (!acq.contains(s))
            return sesdirs; /* not every session has a time: keep label order */
    }

    QStringList ordered = sesdirs;
    std::stable_sort(ordered.begin(), ordered.end(), [&acq](const QString &a, const QString &b) {
        return acq.value(a) < acq.value(b);
    });
    if (ordered != sesdirs)
        sqrl->Log(QString("  Ordering sessions by sessions.tsv acq_time: %1").arg(ordered.join(", ")));
    return ordered;
}


/* ---------------------------------------------------------------------------- */
/* ----- FindMissingContent --------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Recursively collect files under a directory whose content is not present
 * @param dir directory to scan (symlinks followed, loops guarded)
 * @param visited canonical paths of directories already visited
 * @param missing [out] paths of broken symlinks and git-annex pointer files
 */
static void FindMissingContent(const QString &dir, QSet<QString> &visited, QStringList &missing) {
    QString canon = QFileInfo(dir).canonicalFilePath();
    if (canon.isEmpty() || visited.contains(canon))
        return;
    visited.insert(canon);
    foreach (QFileInfo fi, QDir(dir).entryInfoList(QDir::Files | QDir::Dirs | QDir::NoDotAndDotDot | QDir::Hidden | QDir::System)) {
        if (utils::IsSkippedName(fi.fileName()))
            continue;
        if (fi.isDir()) {
            /* a symlink to this directory or one of its ancestors is a cycle */
            if (fi.isSymLink() && ((fi.canonicalFilePath() == canon) || canon.startsWith(fi.canonicalFilePath() + "/")))
                continue;
            FindMissingContent(fi.absoluteFilePath(), visited, missing);
        }
        else if (utils::IsMissingContent(fi.absoluteFilePath()))
            missing.append(fi.absoluteFilePath());
    }
}


/* ---------------------------------------------------------------------------- */
/* ----- StageFiles ----------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * Every BIDS file goes through here on its way into the package. Files whose content
 * is not present (DataLad/git-annex: broken symlink or pointer file, ie not yet
 * fetched with 'datalad get') are left out and recorded, so the import can report
 * them, rather than silently writing a package with missing data. Inside staged
 * directories they are skipped by the writer (utils::CopyPathToDir) and recorded here.
 *
 * @brief Stage files/directories with a squirrel object, leaving out missing content
 * @param object object type
 * @param rowid object row id
 * @param files files and directories to stage
 * @param sqrl squirrel object
 * @return true if anything was staged
 */
bool bids::StageFiles(ObjectType object, qint64 rowid, QStringList files, squirrel *sqrl) {
    QStringList kept;
    foreach (QString f, files) {
        QFileInfo fi(f);
        if (fi.isDir()) {
            QSet<QString> visited;
            QStringList missing;
            FindMissingContent(f, visited, missing);
            foreach (QString m, missing)
                missingContent.insert(QDir(rootDir).relativeFilePath(m));
            kept.append(f);
        }
        else if (utils::IsMissingContent(f))
            missingContent.insert(QDir(rootDir).relativeFilePath(f));
        else
            kept.append(f);
    }
    if (kept.isEmpty())
        return false;
    return sqrl->AddStagedFiles(object, rowid, kept);
}


/* ---------------------------------------------------------------------------- */
/* ----- ReportMissingContent ------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Warn about, and record in the package Notes, files whose content was not present
 * @param sqrl squirrel object
 */
void bids::ReportMissingContent(squirrel *sqrl) {
    if (missingContent.isEmpty())
        return;

    QStringList list(missingContent.begin(), missingContent.end());
    list = NaturalSort(list);

    sqrl->Log(QString("WARNING: [%1] file(s) in this dataset have no content (broken symlinks or git-annex pointer files) and were NOT included in the package.").arg(list.size()));
    sqrl->Log("WARNING: if this is a DataLad dataset, fetch the data first with 'datalad get .' (or 'datalad get <path>') and convert again.");
    for (int i = 0; (i < list.size()) && (i < 10); i++)
        sqrl->Log(QString("WARNING:   missing: %1").arg(list[i]));
    if (list.size() > 10)
        sqrl->Log(QString("WARNING:   ... and %1 more (all are listed in the package Notes under bids.import.missingContent)").arg(list.size() - 10));

    /* the package records its own incompleteness */
    AddToPackageNotes("import", "missingContent", QJsonArray::fromStringList(list), sqrl);
    AddToPackageNotes("import", "missingContentCount", list.size(), sqrl);
}
