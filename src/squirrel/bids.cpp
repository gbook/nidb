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

/* ---------------------------------------------------------------------------- */
/* ----- bids ----------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
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

    }

    /* check for a 'derivatives' directory, which are analyses */

    /* check for a 'logs' directory, which are logs */

    /* check for a 'code' directory, which are pipeline/code */

    /* check for a 'stimuli' directory, which are experiments */

    /* check for a 'phenotype' directory, which are ... subject demographics, or observations? */

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
            sqrl->Debug(QString("Read squirrel->Description from %1").arg(filePath), __FUNCTION__);
            QString desc = utils::CleanJSON(utils::ReadTextFileToString(filePath));
            sqrl->Description = desc;
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
            sqrlAnalysis.LastMessage = "BIDS imported analysis file";
            sqrlAnalysis.studyRowID = studyRowID;
            sqrlAnalysis.Store();
            qint64 analysisRowID = sqrlAnalysis.GetObjectID();

            QStringList files;
            files.append(f);
            sqrl->AddStagedFiles(Analysis, analysisRowID, files);
            sqrl->Log(QString("Added [%1] files to analysis [%2]").arg(files.size()).arg("analysis"));

        }
        else if (f.endsWith("_sessions.tsv")) {

            /* load this information into a hash. the second column is likely the session date
             * ses-01 value
             * ses-02 value
             */
            QString filestr = utils::ReadTextFileToString(f);

            utils::indexedHash tsv;
            QStringList cols;
            QString m;

            if (utils::ParseTSV(filestr, tsv, cols, m)) {
                //sqrl->Log(QString("Successfuly read [%1] into [%2] rows").arg(f).arg(tsv.size()), __FUNCTION__);
                for (int i=0; i<tsv.size(); i++) {
                    QString sesid = tsv[i]["session_id"];
                    QString datetime = tsv[i]["acq_time"];
                }
            }
        }

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

    /* possible directories:
        anat
        func
        figures
        fmap
        ieeg
        perf
        eeg
        micr
        motion
    */

    /* for loading the JSON files containing paramaters */
    QHash<QString, QString> params;

    /* get list of all dirs in this sesdir */
    QStringList sesdirs = utils::FindAllDirs(sesdir, "*", false);
    sqrl->Log(QString("Found [%1] directories in [%2/*]").arg(sesdirs.size()).arg(sesdir));
    if (sesdirs.size() > 0) {
        foreach (QString dir, sesdirs) {
            QString datadir = QString("%1/%2").arg(sesdir).arg(dir);
            QStringList files = utils::FindAllFiles(datadir, "*", false);
            sqrl->Log(QString("Found [%1] files in '%2'").arg(files.size()).arg(datadir));

            /* now do something with the files, depending on what they are */
            if ((dir == "anat") || (dir == "fmap") || (dir == "perf")) {
                foreach (QString f, files) {

                    sqrl->Debug(QString("Found file [%1] of type [%2]").arg(f).arg(dir), __FUNCTION__);

                    QString filename = QFileInfo(f).fileName();
                    filename.replace(".nii.gz", "");
                    filename.replace(".nii", "");
                    QString protocol = filename.section("_", -1);
                    study.VisitType = filename.section("_", 1,1);
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    sqrl->Debug(QString("Checkpoint 1 - SubjectID [%1]  protocol [%2]  seriesNum [%3]").arg(subject.ID).arg(protocol).arg(seriesNum), __FUNCTION__);

                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    files2.append(f);
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else if (dir == "func") {
                foreach (QString f, files) {

                    /* ignore the *events.tsv files, they'll be handled with the .nii.gz */
                    if (f.endsWith("events.tsv")) {
                        continue;
                    }

                    QString filename = QFileInfo(f).fileName();
                    filename.replace(".nii.gz", "");
                    filename.replace(".nii", "");
                    filename.replace(".tsv.gz", "");
                    //QString ID = filename.section("_", 0,0);
                    QString protocol = filename.section("_", 2, 2);
                    QString run = filename.section("_", -1);
                    study.VisitType = filename.section("_", 1, 1);
                    protocol += run;
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    files2.append(f);
                    if (f.endsWith("bold.nii.gz", Qt::CaseInsensitive)) {
                        QString tf = f;
                        tf.replace("bold.nii.gz", "events.tsv");
                        files2.append(tf);
                    }
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else if (dir == "pet") {
                foreach (QString f, files) {

                    if (f.endsWith("pet.json"))
                        params = sqrl->ReadParamsFile(f);

                    /* ignore the *.json and event.tsv files, they'll be handled with the .nii.gz later */
                    if ((f.endsWith("pet.json")) || (f.endsWith("events.tsv")) || (f.endsWith("events.json"))) {
                        continue;
                    }

                    QString filename = QFileInfo(f).fileName();
                    filename.replace(".nii.gz", "", Qt::CaseInsensitive);
                    filename.replace(".nii", "", Qt::CaseInsensitive);
                    QString protocol = filename.section("_", -1);
                    study.VisitType = filename.section("_", 1, 1);
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    /* create a seriesRowID */
                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    files2.append(f);
                    if (f.endsWith("pet.nii.gz", Qt::CaseInsensitive)) {
                        /* there could be several files that are associated with the .nii.gz file, so lets add all of those */
                        QString tf = f;
                        tf.replace("pet.nii.gz", "pet.json", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("pet.json", "events.json", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("events.json", "events.tsv", Qt::CaseInsensitive);
                        files2.append(tf);
                    }
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else if (dir == "micr") {
                foreach (QString f, files) {

                    if (f.endsWith("SEM.json", Qt::CaseInsensitive))
                        params = sqrl->ReadParamsFile(f);

                    /* ignore the *.json and event.tsv files, they'll be handled with the .nii.gz later */
                    if ((f.endsWith("micr.json")) || (f.endsWith("events.tsv")) || (f.endsWith("events.json"))) {
                        continue;
                    }

                    QString filename = QFileInfo(f).fileName();
                    QString protocol = filename.section("_", -1);
                    study.VisitType = filename.section("_", 1, 1);
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    /* create a seriesRowID */
                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    files2.append(f);
                    if (f.endsWith("pet.nii.gz"), Qt::CaseInsensitive) {
                        /* there could be several files that are associated with the .nii.gz file, so lets add all of those */
                        QString tf = f;
                        tf.replace("pet.nii.gz", "pet.json", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("pet.json", "events.json", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("events.json", "events.tsv", Qt::CaseInsensitive);
                        files2.append(tf);
                    }
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else if (dir == "motion") {
                /* just copy all the files into the study */
                foreach (QString f, files) {

                    if (f.endsWith("motion.json"))
                        params = sqrl->ReadParamsFile(f);

                    /* ignore some files here, they'll be handled with the _motion.tsv file later on */
                    if ( (f.endsWith("channels.tsv")) || (f.endsWith("motion.json")) ) {
                        continue;
                    }

                    QString filename = QFileInfo(f).fileName();
                    filename.replace("_motion.tsv", "");
                    QString protocol = filename.section("_", 2, 2); /* second part after splitting by _ */
                    study.VisitType = filename.section("_", 1, 1);
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    /* create a subjectRowID if it doesn't exist */
                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    if (f.endsWith("motion.tsv", Qt::CaseInsensitive)) {
                        /* there could be several files that are associated with the .nii.gz file, so lets add all of those */
                        QString tf = f;
                        tf.replace("motion.tsv", "channels.tsv", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("channels.tsv", "motion.json", Qt::CaseInsensitive);
                        files2.append(tf);
                    }
                    files2.append(f);
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else if (dir == "eeg") {
                /* just copy all the files into the study */
                foreach (QString f, files) {

                    if (f.endsWith("eeg.json"))
                        params = sqrl->ReadParamsFile(f);

                    /* the only file we care about is the *.edf. All other files will be handled later on */
                    if ( (f.endsWith("channels.tsv")) || (f.endsWith("eeg.json")) || (f.endsWith("events.tsv")) ) {
                        continue;
                    }

                    QString filename = QFileInfo(f).fileName();
                    filename.replace("_eeg.edf", "");
                    QString protocol = filename.section("_", 2, 2); /* second part after splitting by _ */
                    study.VisitType = filename.section("_", 1, 1);
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    /* create a subjectRowID if it doesn't exist */
                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    if (f.endsWith("eeg.edf", Qt::CaseInsensitive)) {
                        /* there could be several files that are associated with the .nii.gz file, so lets add all of those */
                        QString tf = f;
                        tf.replace("eeg.edf", "channels.tsv", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("channels.tsv", "eeg.json", Qt::CaseInsensitive);
                        files2.append(tf);
                        tf.replace("eeg.json", "events.tsv", Qt::CaseInsensitive);
                        files2.append(tf);
                    }
                    files2.append(f);
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else if (dir == "beh") {
                /* just copy all the files into the study */
                foreach (QString f, files) {

                    QString filename = QFileInfo(f).fileName();
                    filename.replace("_eeg.edf", "");
                    QString protocol = filename.section("_", 2, 2); /* second part after splitting by _ */
                    study.VisitType = filename.section("_", 1, 1);
                    qint64 seriesNum = study.GetNextSeriesNumber();

                    /* create a subjectRowID if it doesn't exist */
                    squirrelSeries series(sqrl->GetDatabaseUUID());
                    series.SeriesNumber = seriesNum;
                    series.studyRowID = studyRowID;
                    series.Protocol = protocol;
                    series.Store();
                    qint64 seriesRowID = series.GetObjectID();
                    sqrl->Log(QString("  Added series [%1] with seriesRowID [%2]").arg(seriesNum).arg(seriesRowID));

                    /* now that the subject/study/series exist, add the file(s) */
                    QStringList files2;
                    files2.append(f);
                    sqrl->AddStagedFiles(Series, seriesRowID, files2);
                }
            }
            else {
                sqrl->Log(QString("Notice! modality directory [%1] not handled yet").arg(dir));
            }
        }
    }

    return true;
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
