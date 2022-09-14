/* ------------------------------------------------------------------------------
  Squirrel dicom.cpp
  Copyright (C) 2004 - 2022
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
/* ----- ReadDirectory -------------------------------------------------------- */
/* ---------------------------------------------------------------------------- */
/**
 * @brief dicom::ReadDirectory
 * @param dir
 * @param nFiles
 * @param m
 * @return
 */
//bool dicom::ReadDirectory(QString dir, QString binpath, QString &msg) {
bool dicom::LoadToSquirrel(QString dir, QString binpath, squirrel *sqrl, QString &msg) {

    numFiles = 0;
    msg += "";

    /* check if the directory exists */
    QDir d(dir);
    if (!d.exists()) {
        Print(QString("Directory [%1] does not exist").arg(dir));
        return false;
    }

    squirrelImageIO *img = new squirrelImageIO();

    /* find all files in the directory. DICOM files can have any extension, not just .dcm
     * so we need to check if all files to see if they are readable by gdcm */
    qint64 processedFileCount(0);
    qint64 foundFileCount(0);
    QString m;
    QStringList files = FindAllFiles(dir, "*", true);
    //numFiles = files.size();
    foreach (QString f, files) {
        processedFileCount++;

        if (processedFileCount%1000 == 0) {
            double percent = static_cast<double>(processedFileCount)/static_cast<double>(numFiles) * 100.0;
            Print(QString("Processed %1 of %2 files [%3%%]").arg(processedFileCount).arg(numFiles).arg(percent));
        }

        QHash<QString, QString> tags;
        if (img->GetImageFileTags(f, binpath, false, tags, m)) {
            if (tags["FileType"] == "DICOM") {
                foundFileCount++;
                dcms[tags["PatientID"]][tags["StudyInstanceUID"]][tags["SeriesInstanceUID"]].append(f);
            }
        }
    }
    Print(QString("Found %1 subjects in %2 files").arg(dcms.size()).arg(foundFileCount));

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

                    /* setup the series object */
                    squirrelSeries currSeries;

                    currSeries.description = tags["SeriesDescription"];
                    currSeries.protocol = tags["Protocol"];
                    currSeries.number = tags["SeriesNumber"].toLongLong();
                    currSeries.dateTime = QDateTime::fromString(tags["SeriesDateTime"], "yyyy-MM-dd HH:mm:ss");
                    currSeries.numFiles = numfiles;
                    currSeries.params = tags;
                    currSeries.seriesUID = tags["SeriesInstanceUID"];
                    currSeries.stagedFiles = files;

                    qint64 totalSize(0);
                    foreach (QString f, files) {
                        QFileInfo fi(f);
                        totalSize += fi.size();
                    }
                    currSeries.size = totalSize;

                    /* setup/update the study object */
                    currStudy.dateTime = QDateTime::fromString(tags["StudyDateTime"], "yyyy-MM-dd HH:mm:ss");
                    currStudy.description = tags["StudyDescription"];
                    currStudy.modality = tags["Modality"];
                    currStudy.studyUID = tags["StudyInstanceUID"];
                    currStudy.height = tags["PatientSize"].toDouble();
                    currStudy.weight = tags["PatientWeight"].toDouble();

                    /* setup/update the subject object */
                    currSubject.dateOfBirth = QDate::fromString(tags["PatientBirthDate"], "yyyy-MM-dd");
                    currSubject.gender = tags["PatientSex"][0];
                    currSubject.ID = tags["PatientID"];
                    currSubject.sex = tags["PatientSex"][0];

                    currStudy.addSeries(currSeries);

                }
                currSubject.addStudy(currStudy);
            }
            /* add subject to list */
            sqrl->addSubject(currSubject);
        }

        sqrl->print();
    }

    delete img;

    return true;
}
