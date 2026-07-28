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
    QStringList rootfiles = utils::FindAllFiles(dir, "*", false);
    sqrl->Debug(QString("Found [%1] root files matching '%2/*'").arg(rootfiles.size()).arg(dir), __FUNCTION__);
    LoadRootFiles(rootfiles, sqrl);

    /* get list of directories in the root named 'sub-*' */
    QStringList subjdirs = utils::FindAllDirs(dir, "sub-*", false);

    sqrl->Debug(QString("Found [%1] subject directories matching '%2/sub-*'").arg(subjdirs.size()).arg(dir), __FUNCTION__);
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

        /* get all the FILES inside of the subject directory */
        QStringList subjfiles = utils::FindAllFiles(subjpath, "*", false);
        sqrl->Debug(QString("Found [%1] subject root files matching '%2/*'").arg(subjfiles.size()).arg(subjdir), __FUNCTION__);

        LoadSubjectFiles(subjfiles, subjdir, sqrl);

        /* get a list of ses-* DIRS, if there are any */
        QStringList sesdirs = utils::FindAllDirs(subjpath, "ses-*", false);
        sqrl->Debug(QString("Found [%1] session directories matching '%2/ses-*'").arg(sesdirs.size()).arg(subjdir), __FUNCTION__);
        if (sesdirs.size() > 0) {
            int studyNum = 1;
            foreach (QString sesdir, sesdirs) {
                /* each session will become its own study */
                QString sespath = QString("%1/%2/%3").arg(dir).arg(subjdir).arg(sesdir);

                sqrl->Debug(QString("Loading session path [%1] into study [%2]").arg(sespath).arg(studyNum), __FUNCTION__);

                LoadSessionDir(sespath, subjectRowID, studyNum, sqrl);
                studyNum++;
            }
        }
        else {
            /* if there are no ses-* directories, then the session must be in the root subject directory */
            QString sespath = QString("%1/%2").arg(dir).arg(subjdir);
            LoadSessionDir(sespath, subjectRowID, -1, sqrl);
        }

        /* now that this subject's studies exist, apply the sub-*_sessions.tsv (session
         * acquisition times + session-level measures) */
        QString sessionsFile = QString("%1/%2_sessions.tsv").arg(subjpath).arg(subjdir);
        if (utils::FileExists(sessionsFile))
            LoadSessionsFile(sessionsFile, subjectRowID, sqrl);
    }

    /* check for a 'phenotype' directory, which become subject observations */
    QString phenotypeDir = QString("%1/phenotype").arg(dir);
    if (QDir(phenotypeDir).exists())
        LoadPhenotypeDir(phenotypeDir, sqrl);

    /* check for a 'derivatives' directory, which become pipelines + analyses */
    QString derivativesDir = QString("%1/derivatives").arg(dir);
    if (QDir(derivativesDir).exists())
        LoadDerivatives(derivativesDir, sqrl);

    /* TODO future passes:
     *   'code' directory     -> pipeline/code
     *   'stimuli' directory  -> experiments
     *   'phenotype' directory -> observations
     *   'logs' directory     -> logs
     */

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
/* ----- LoadSubjectFiles ----------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Load the files contained in the subject's directory in the BIDS object
 * @param subjfiles list of subject files
 * @param ID BIDS ID of this subject
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadSubjectFiles(QStringList subjfiles, QString ID, squirrel *sqrl) {
    sqrl->Log(QString("Loading subject [%1] files for ID [%2]").arg(subjfiles.size()).arg(ID));

    foreach (QString f, subjfiles) {
        QFileInfo fi(f);
        QString filename = fi.fileName();

        //sqrl->Log(QString("Found file [%1]").arg(filename), __FUNCTION__);

        /* possible files in the subject root dir
            sub-*-sessions.tsv

            There's not a lot whole lot of information in this file, but it seems to contain analysis
            or behavioral information... So let's put it in the analysis object
            But, we need to create an empty study and pipeline for these files, because the BIDS analysis files are
            not associated with a session/study or pipeline
        */
        if (filename.endsWith("_scans.tsv")) {

            /* get the subject */
            squirrelSubject sqrlSubject(sqrl->GetDatabaseUUID());
            qint64 subjectRowID = sqrl->FindSubject(ID);
            if (subjectRowID < 0) {
                sqrlSubject.ID = ID;
                sqrlSubject.Store();
                subjectRowID = sqrlSubject.GetObjectID();
            }

            /* create a session/study and add it to the subject */
            int studyRowID;
            squirrelStudy sqrlStudy(sqrl->GetDatabaseUUID());
            sqrlStudy.subjectRowID = subjectRowID;
            sqrlStudy.Store();
            studyRowID = sqrlStudy.GetObjectID();

            /* create an analysis */
            squirrelAnalysis sqrlAnalysis(sqrl->GetDatabaseUUID());
            sqrlAnalysis.PipelineName = "analysis";
            sqrlAnalysis.StatusMessage = "BIDS imported analysis file";
            sqrlAnalysis.studyRowID = studyRowID;
            sqrlAnalysis.Store();
            qint64 analysisRowID = sqrlAnalysis.GetObjectID();

            QStringList files;
            files.append(f);
            sqrl->AddStagedFiles(Analysis, analysisRowID, files);
            sqrl->Log(QString("Added [%1] files to analysis [%2]").arg(files.size()).arg("analysis"));

        }
        /* note: sub-*_sessions.tsv is handled separately in LoadSessionsFile, after
         * this subject's studies have been created */

    }

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadSessionDir ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Load the files from a BIDS session directory into a squirrel study
 * @param sesdir the session directory
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadSessionDir(QString sesdir, qint64 subjectRowID, int studyNum, squirrel *sqrl) {
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

    /* BIDS datatype (modality) directories, eg: anat func fmap dwi perf pet eeg ieeg meg micr motion beh nirs */

    /* derive the session/visit label from the session directory name (eg "ses-1" -> "1") */
    QString sesLabel;
    QString sesDirName = QFileInfo(sesdir).fileName();
    if (sesDirName.startsWith("ses-"))
        sesLabel = sesDirName.mid(4);

    /* track which modalities are present so we can set the study modality */
    QSet<QString> modalities;

    /* get list of all datatype dirs in this sesdir */
    QStringList datatypeDirs = utils::FindAllDirs(sesdir, "*", false);
    sqrl->Log(QString("Found [%1] datatype directories in [%2/*]").arg(datatypeDirs.size()).arg(sesdir));

    foreach (QString datatype, datatypeDirs) {
        /* 'figures' (derivatives artifact) and similar non-data dirs are skipped */
        if (datatype == "figures")
            continue;

        QString datadir = QString("%1/%2").arg(sesdir).arg(datatype);
        QStringList files = utils::FindAllFiles(datadir, "*", false);
        sqrl->Log(QString("Found [%1] files in '%2'").arg(files.size()).arg(datadir));

        QString modality = ModalityForDatatype(datatype);
        if (modality == "") {
            sqrl->Log(QString("Notice! BIDS datatype directory [%1] not recognized; importing its data files generically").arg(datatype));
        }

        /* identify the 'primary' data files in this datatype dir. Sidecars (.json,
         * events/channels/scans .tsv, .bval/.bvec) are attached to their primary,
         * not turned into their own series. */
        QStringList primaries;
        foreach (QString f, files) {
            QString fn = QFileInfo(f).fileName().toLower();
            if (fn.endsWith(".nii.gz") || fn.endsWith(".nii") ||
                fn.endsWith(".edf") || fn.endsWith(".bdf") || fn.endsWith(".set") ||
                fn.endsWith(".vhdr") || fn.endsWith(".fif") || fn.endsWith(".snirf") ||
                fn.endsWith(".tif") || fn.endsWith(".tiff"))
                primaries.append(f);
        }
        /* fallback for datatypes whose primary data is a .tsv (eg motion, beh): any
         * file that isn't an obvious sidecar becomes a primary */
        if (primaries.isEmpty()) {
            foreach (QString f, files) {
                QString fn = QFileInfo(f).fileName().toLower();
                if (fn.endsWith(".json") || fn.endsWith(".bval") || fn.endsWith(".bvec"))
                    continue;
                if (fn.endsWith("_events.tsv") || fn.endsWith("_channels.tsv") ||
                    fn.endsWith("_scans.tsv") || fn.endsWith("_electrodes.tsv") ||
                    fn.endsWith("_coordsystem.json"))
                    continue;
                primaries.append(f);
            }
        }

        foreach (QString primary, primaries) {
            AddSeriesFromBidsFile(primary, datatype, studyRowID, sqrl);
            if (modality != "")
                modalities.insert(modality);
        }
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
    if (!sesLabel.isEmpty())
        study.VisitType = sesLabel;
    study.Store(); /* objectID is set, so this updates the existing study row */

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
        ".nii.gz", ".tsv.gz", ".ome.tif", ".ome.tiff",
        ".nii", ".json", ".tsv", ".bval", ".bvec",
        ".edf", ".bdf", ".set", ".vhdr", ".vmrk", ".eeg", ".fif",
        ".snirf", ".tif", ".tiff", ".png", ".jpg", ".pos", ".mat", ".txt"
    };
    foreach (const QString &e, exts) {
        if (base.endsWith(e, Qt::CaseInsensitive)) {
            ext = base.right(e.size());
            base.chop(e.size());
            break;
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
    return "";
}


/* ---------------------------------------------------------------------------- */
/* ----- AddSeriesFromBidsFile ------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Create a squirrel series from a primary BIDS data file
 *
 * Parses the BIDS entities from the filename, populates the series' BIDS fields,
 * reads the matching .json sidecar into the series params, and stages the primary
 * file plus its associated sidecars (.json/.bval/.bvec, events/physio/channels).
 *
 * @param primaryFile full path to the primary data file
 * @param datatype the BIDS datatype directory name (anat, func, dwi, ...)
 * @param studyRowID the parent study row id
 * @param sqrl squirrel object
 * @return the new series row id, or -1 on failure
 */
qint64 bids::AddSeriesFromBidsFile(QString primaryFile, QString datatype, qint64 studyRowID, squirrel *sqrl) {
    QFileInfo fi(primaryFile);
    QString filename = fi.fileName();
    QString dirpath = fi.absolutePath();

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

    /* the file stem (filename without extension), used to find sidecars */
    QString stem = filename;
    stem.chop(ext.size());

    /* read the JSON sidecar (same stem) into the series params */
    QString jsonSidecar = QString("%1/%2.json").arg(dirpath).arg(stem);
    if (utils::FileExists(jsonSidecar))
        series.params = sqrl->ReadParamsFile(jsonSidecar);

    series.Store();
    qint64 seriesRowID = series.GetObjectID();

    /* gather the primary file plus its associated sidecars */
    QStringList files;
    files.append(primaryFile);

    /* same-stem sidecars (.json/.bval/.bvec) */
    foreach (const QString &se, QStringList({".json", ".bval", ".bvec"})) {
        QString p = QString("%1/%2%3").arg(dirpath).arg(stem).arg(se);
        if (utils::FileExists(p) && !files.contains(p))
            files.append(p);
    }

    /* entity-prefix sidecars (events/physio/channels/stim) that share the entities
     * but use a different suffix */
    QString entityPrefix = stem;
    if (!suffix.isEmpty() && entityPrefix.endsWith("_" + suffix))
        entityPrefix.chop(suffix.size() + 1);
    foreach (const QString &sc, QStringList({"events.tsv", "events.json", "physio.tsv.gz", "physio.json", "channels.tsv", "stim.tsv.gz"})) {
        QString p = QString("%1/%2_%3").arg(dirpath).arg(entityPrefix).arg(sc);
        if (utils::FileExists(p) && !files.contains(p))
            files.append(p);
    }

    sqrl->AddStagedFiles(Series, seriesRowID, files);
    sqrl->Log(QString("  Added %1 series [%2] suffix [%3] task [%4] run [%5] with [%6] file(s)")
                  .arg(datatype).arg(seriesNum).arg(suffix).arg(series.BidsTask).arg(series.BidsRun).arg(files.size()));

    return seriesRowID;
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadParticipantFile -------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Loads the participants.tsv file
 * @param f the path to the file
 * @param sqrl squirrel object
 * @return true if successful, false otherwise
 */
bool bids::LoadParticipantsFile(QString f, squirrel *sqrl) {
    /* do we need to read the .json file? There's not much in there that isn't already specified here */

    sqrl->Log(QString("Reading participants file [%1]").arg(f));

    QString file = utils::ReadTextFileToString(f);

    utils::indexedHash tsv;
    QStringList cols;
    QString m;

    if (utils::ParseTSV(file, tsv, cols, m)) {
        sqrl->Debug(QString("Successful read [%1] into [%2] rows").arg(f).arg(tsv.size()), __FUNCTION__);
        for (int i=0; i<tsv.size(); i++) {
            QString id = tsv[i]["participant_id"];
            QString age = tsv[i]["age"];
            QString sex = tsv[i]["sex"];
            QString hand = tsv[i]["handedness"];
            QString species = tsv[i]["species"];
            QString strain = tsv[i]["strain"];

            /* add a subject */
            squirrelSubject sqrlSubj(sqrl->GetDatabaseUUID());
            sqrlSubj.ID = id;
            sqrlSubj.Sex = sex;
            sqrlSubj.Gender = sex;
            sqrlSubj.Store();
            qint64 subjectRowID = sqrlSubj.GetObjectID();

            /* add handedness as a observation */
            squirrelObservation sqrlObs(sqrl->GetDatabaseUUID());
            sqrlObs.Description = "Handedness";
            sqrlObs.ObservationName = "Handedness";
            sqrlObs.Value = hand;
            sqrlObs.subjectRowID = subjectRowID;
            sqrlObs.Store();

            squirrelObservation sqrlObs2(sqrl->GetDatabaseUUID());
            sqrlObs2.Description = "Species";
            sqrlObs2.ObservationName = "Species";
            sqrlObs2.Value = species;
            sqrlObs2.subjectRowID = subjectRowID;
            sqrlObs2.Store();

            squirrelObservation sqrlObs3(sqrl->GetDatabaseUUID());
            sqrlObs3.Description = "Strain";
            sqrlObs3.ObservationName = "Strain";
            sqrlObs3.Value = strain;
            sqrlObs3.subjectRowID = subjectRowID;
            sqrlObs3.Store();

            squirrelObservation sqrlObs4(sqrl->GetDatabaseUUID());
            sqrlObs4.Description = "age";
            sqrlObs4.ObservationName = "age";
            sqrlObs4.Value = age;
            sqrlObs4.subjectRowID = subjectRowID;
            sqrlObs4.Store();

            sqrl->Debug(QString("Read subject ID [%1]  age [%2]  sex [%3]. Stored in squirrel with SubjectRowID [%4]").arg(id).arg(age).arg(sex).arg(subjectRowID), __FUNCTION__);
        }
    }
    else {
        sqrl->Log(QString("Error: Unable to read .tsv file [%1] message [%2]").arg(f).arg(m));
    }

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

    QFileInfo fi(f);
    QString filename = fi.fileName();
    filename.replace("task-", "");
    filename.replace(".json", "");
    filename.replace(".tsv", "");
    filename.replace("_events", "");
    filename.replace("_bold", "");
    QString str = utils::ReadTextFileToString(f);
    QJsonDocument d = QJsonDocument::fromJson(str.toUtf8());
    QJsonObject root = d.object();

    QString experimentName = root.value("TaskName").toString().toLower();
    if (experimentName == "")
        experimentName = filename;

    //double tr = root.value("RepetitionTime").toDouble();

    squirrelExperiment exp(sqrl->GetDatabaseUUID());
    exp.ExperimentName = experimentName;
    //exp.virtualPath = QString("experiments/%1").arg(experimentName);
    exp.Store();
    int expRowID = exp.GetObjectID();

    QStringList files;
    files.append(f);
    //squirrelExperiment sqrlExp;
    //sqrlExp.experimentName = experimentName;
    //sqrlExp.virtualPath = QString("%1/experiments/%2").arg(sqrl->GetTempDir()).arg(experimentName);
    //sqrl->experimentList.append(sqrlExp);
    sqrl->AddStagedFiles(Experiment, expRowID, files);
    sqrl->Debug(QString("Added [%1] files to experiment [%2] with path [%3]").arg(files.size()).arg(experimentName).arg(exp.VirtualPath()), __FUNCTION__);

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
/* ----- LoadDerivatives ------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Import a BIDS 'derivatives' directory as squirrel pipelines + analyses
 *
 * Each derivatives/<name>/ directory becomes a squirrel pipeline. Each sub-* (and
 * ses-*) directory inside it becomes an analysis attached to that subject's study.
 *
 * @param derivativesDir path to the derivatives directory
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadDerivatives(QString derivativesDir, squirrel *sqrl) {
    sqrl->Log(QString("Loading derivatives from [%1]").arg(derivativesDir));

    QStringList pipelineDirs = utils::FindAllDirs(derivativesDir, "*", false);
    foreach (QString pipelineName, pipelineDirs) {
        QString pdir = QString("%1/%2").arg(derivativesDir).arg(pipelineName);

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

        /* stage pipeline-level files (those sitting directly in the pipeline dir) */
        QStringList pipelineFiles = utils::FindAllFiles(pdir, "*", false);
        if (!pipelineFiles.isEmpty())
            sqrl->AddStagedFiles(Pipeline, pipelineRowID, pipelineFiles);

        /* per-subject outputs become analyses */
        QStringList subDirs = utils::FindAllDirs(pdir, "sub-*", false);
        foreach (QString subName, subDirs) {
            QString sdir = QString("%1/%2").arg(pdir).arg(subName);

            qint64 subjectRowID = sqrl->FindSubject(subName);
            if (subjectRowID < 0) {
                sqrl->Log(QString("  Derivative output for unknown subject [%1]; skipping").arg(subName));
                continue;
            }

            /* if the derivative subject has sessions, map each to the matching study */
            QStringList sesDirs = utils::FindAllDirs(sdir, "ses-*", false);
            if (!sesDirs.isEmpty()) {
                foreach (QString sesName, sesDirs) {
                    QString sesLabel = sesName.startsWith("ses-") ? sesName.mid(4) : sesName;
                    qint64 studyRowID = ResolveDerivativeStudy(subjectRowID, sesLabel, sqrl);
                    AddAnalysisFromDir(QString("%1/%2").arg(sdir).arg(sesName), pipelineName, pipelineRowID, studyRowID, sqrl);
                }
            }
            else {
                qint64 studyRowID = ResolveDerivativeStudy(subjectRowID, "", sqrl);
                AddAnalysisFromDir(sdir, pipelineName, pipelineRowID, studyRowID, sqrl);
            }
        }
    }

    return true;
}


/* ---------------------------------------------------------------------------- */
/* ----- ResolveDerivativeStudy ----------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Find the study a derivative analysis should attach to, creating one if needed
 * @param subjectRowID parent subject
 * @param sesLabel session/visit label to match (empty = no session)
 * @param sqrl squirrel object
 * @return the study row id
 */
qint64 bids::ResolveDerivativeStudy(qint64 subjectRowID, QString sesLabel, squirrel *sqrl) {
    QList<squirrelStudy> studies = sqrl->GetStudyList(subjectRowID);

    /* prefer a study whose visit type matches the session label */
    if (!sesLabel.isEmpty()) {
        foreach (squirrelStudy s, studies) {
            if (s.VisitType == sesLabel)
                return s.GetObjectID();
        }
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
    st.Store();
    sqrl->Log(QString("  Created derived study [%1] for subject [%2]").arg(st.StudyNumber).arg(subj.ID));
    return st.GetObjectID();
}


/* ---------------------------------------------------------------------------- */
/* ----- AddAnalysisFromDir --------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Create an analysis from a derivative directory and stage its files
 * @param dir directory containing the derivative output for one subject/session
 * @param pipelineName name of the pipeline this analysis belongs to
 * @param pipelineRowID parent pipeline row id
 * @param studyRowID parent study row id
 * @param sqrl squirrel object
 * @return the new analysis row id
 */
qint64 bids::AddAnalysisFromDir(QString dir, QString pipelineName, qint64 pipelineRowID, qint64 studyRowID, squirrel *sqrl) {
    squirrelAnalysis a(sqrl->GetDatabaseUUID());
    a.AnalysisName = pipelineName;
    a.PipelineName = pipelineName;
    a.PipelineVersion = 1;
    a.studyRowID = studyRowID;
    a.pipelineRowID = pipelineRowID;
    a.StatusMessage = "Imported from BIDS derivatives";
    a.Store();
    qint64 analysisRowID = a.GetObjectID();

    QStringList files = utils::FindAllFiles(dir, "*", true); /* recursive */
    if (!files.isEmpty())
        sqrl->AddStagedFiles(Analysis, analysisRowID, files);

    sqrl->Log(QString("  Added analysis [%1] -> studyRowID [%2] with [%3] file(s)").arg(pipelineName).arg(studyRowID).arg(files.size()));
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
 * @param f path to the sessions.tsv file
 * @param subjectRowID the parent subject
 * @param sqrl squirrel object
 * @return true if parsed
 */
bool bids::LoadSessionsFile(QString f, qint64 subjectRowID, squirrel *sqrl) {
    sqrl->Log(QString("Reading sessions file [%1]").arg(f));

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
            obs.Description = col;
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
 * per participant. Column descriptions come from the sibling .json. Repeated-measure
 * rows are disambiguated by an index column (day/session/visit/...) or row ordinal.
 *
 * @param phenotypeDir path to the phenotype directory
 * @param sqrl squirrel object
 * @return true
 */
bool bids::LoadPhenotypeDir(QString phenotypeDir, squirrel *sqrl) {
    sqrl->Log(QString("Loading phenotype from [%1]").arg(phenotypeDir));

    static const QStringList idxCols = {"session_id", "ses", "day", "visit", "timepoint", "run", "wave"};

    QStringList tsvFiles = utils::FindAllFiles(phenotypeDir, "*.tsv", false);
    foreach (QString tsvFile, tsvFiles) {
        QString instrument = QFileInfo(tsvFile).fileName();
        instrument.replace(".tsv", "");

        /* read the sibling .json for per-column descriptions */
        QHash<QString, QString> colDesc;
        QString jsonFile = tsvFile;
        jsonFile.replace(".tsv", ".json");
        if (utils::FileExists(jsonFile)) {
            QJsonObject root = QJsonDocument::fromJson(utils::ReadTextFileToString(jsonFile).toUtf8()).object();
            foreach (const QString &k, root.keys()) {
                if (root.value(k).isObject()) {
                    QString d = root.value(k).toObject().value("Description").toString();
                    if (!d.isEmpty())
                        colDesc.insert(k, d);
                }
            }
        }

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
                obs.Description = colDesc.value(col);
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
