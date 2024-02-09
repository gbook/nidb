/* ------------------------------------------------------------------------------
  Squirrel dicom.cpp
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

#include "dicom.h"


/* ---------------------------------------------------------------------------- */
/* ----- dicom ---------------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
dicom::dicom()
{
}


/* ---------------------------------------------------------------------------- */
/* ----- LoadToSquirrel ------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Recursively load DICOM files found in a directory
 * @param dir the directory to load
 * @param binpath path to the dcm2nii executable
 * @param sqrl squirrel object
 * @return true if successful, false otherwise
 */
bool dicom::LoadToSquirrel(QString dir, QString binpath, squirrel *sqrl) {

    numFiles = 0;

    /* check if the directory exists */
    QDir d(dir);
    if (!d.exists()) {
        sqrl->Log(QString("Directory [%1] does not exist").arg(dir), __FUNCTION__);
        return false;
    }

    squirrelImageIO *img = new squirrelImageIO();

    /* find all files in the directory. DICOM files can have any extension, not just .dcm
     * so we need to check if all files to see if they are readable by gdcm */
    qint64 processedFileCount(0);
    qint64 foundFileCount(0);
    QString m;
    QStringList files = utils::FindAllFiles(dir, "*", true);
    //numFiles = files.size();
    foreach (QString f, files) {
        processedFileCount++;

        if (processedFileCount%1000 == 0) {
            double percent = static_cast<double>(processedFileCount)/static_cast<double>(numFiles) * 100.0;
            sqrl->Log(QString("Processed %1 of %2 files [%3%%]").arg(processedFileCount).arg(numFiles).arg(percent), __FUNCTION__);
        }

        QHash<QString, QString> tags;
        if (img->GetImageFileTags(f, binpath, false, tags, m)) {
            if (tags["FileType"] == "DICOM") {
                foundFileCount++;
                dcms[tags["PatientID"]][tags["StudyInstanceUID"]][tags["SeriesInstanceUID"]].append(f);
            }
        }
    }
    sqrl->Log(QString("Found %1 subjects in %2 files").arg(dcms.size()).arg(foundFileCount), __FUNCTION__);

    if (foundFileCount > 0) {
        /* ---------- iterate through the subjects ---------- */
        for(QMap<QString, QMap<QString, QMap<QString, QStringList> > >::iterator a = dcms.begin(); a != dcms.end(); ++a) {
            QString subjectID = a.key();

            /* create a subject */
            squirrelSubject currSubject;
            /* ---------- iterate through the studies ---------- */
            for(QMap<QString, QMap<QString, QStringList> >::iterator b = dcms[subjectID].begin(); b != dcms[subjectID].end(); ++b) {
                QString studyID = b.key();

                /* create a study */
                squirrelStudy currStudy;
                /* ---------- iterate through the series ---------- */
                for(QMap<QString, QStringList>::iterator c = dcms[subjectID][studyID].begin(); c != dcms[subjectID][studyID].end(); ++c) {
                    QString seriesID = c.key();

                    QStringList files = dcms[subjectID][studyID][seriesID];
                    qint64 numfiles = files.size();

                    QHash<QString, QString> tags;
                    QString m;
                    img->GetImageFileTags(files[0], binpath, true, tags, m);

                    /* create/update the subject */
                    int subjectRowID;
                    subjectRowID = sqrl->FindSubject(tags["PatientID"]);
                    if (subjectRowID < 0) {
                        sqrl->Log(QString("Creating squirrel Subject [%1]").arg(tags["PatientID"]), __FUNCTION__);
                        currSubject.dateOfBirth = QDate::fromString(tags["PatientBirthDate"], "yyyy-MM-dd");
                        currSubject.gender = tags["PatientSex"][0];
                        currSubject.ID = tags["PatientID"];
                        currSubject.sex = tags["PatientSex"][0];
                        currSubject.Store();
                        subjectRowID = currSubject.GetObjectID();
                        /* resequence the newly added subject */
                        sqrl->ResequenceSubjects();
                    }

                    /* create/update the study */
                    int studyRowID;
                    studyRowID = sqrl->FindStudyByUID(tags["StudyInstanceUID"]);
                    if (studyRowID < 0) {
                        sqrl->Log(QString("Creating squirrel Study [%1]").arg(tags["StudyInstanceUID"]), __FUNCTION__);
                        currStudy.dateTime = QDateTime::fromString(tags["StudyDateTime"], "yyyy-MM-dd HH:mm:ss");
                        currStudy.description = tags["StudyDescription"];
                        currStudy.modality = tags["Modality"];
                        currStudy.studyUID = tags["StudyInstanceUID"];
                        currStudy.height = tags["PatientSize"].toDouble();
                        currStudy.weight = tags["PatientWeight"].toDouble();
                        currStudy.subjectRowID = subjectRowID;
                        currStudy.Store();
                        studyRowID = currStudy.GetObjectID();
                        /* resequence the newly added studies */
                        sqrl->ResequenceStudies(subjectRowID);
                    }

                    /* create the series object */
                    squirrelSeries currSeries;
                    currSeries.description = tags["SeriesDescription"];
                    currSeries.protocol = tags["Protocol"];
                    currSeries.number = tags["SeriesNumber"].toLongLong();
                    currSeries.dateTime = QDateTime::fromString(tags["SeriesDateTime"], "yyyy-MM-dd HH:mm:ss");
                    currSeries.numFiles = numfiles;
                    currSeries.params = utils::AnonymizeParams(tags);
                    currSeries.seriesUID = tags["SeriesInstanceUID"];
                    currSeries.stagedFiles = files;

                    qint64 totalSize(0);
                    foreach (QString f, files) {
                        QFileInfo fi(f);
                        totalSize += fi.size();
                    }
                    currSeries.size = totalSize;
                    currSeries.studyRowID = studyRowID;
                    currSeries.Store();
                    /* resequence the newly added series */
                    sqrl->ResequenceSeries(studyRowID);

                    sqrl->Log(QString("Created squirrel Series [%1]").arg(currSeries.number), __FUNCTION__);
                }
            }
        }
        //sqrl->print();
    }

    delete img;

    return true;
}
