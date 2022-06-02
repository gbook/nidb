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
bool dicom::ReadDirectory(QString dir, QString binpath, QString &msg) {

    numFiles = 0;

    /* check if the directory exists */
    QDir d(dir);
    if (!d.exists()) {
        msg = "directory does not exist";
        return false;
    }

    imageIO *img = new imageIO();

    /* find all files in the directory. DICOM files can have any extension, not just .dcm
     * so we need to check if all files to see if they are readable by gdcm */
    qint64 processedFileCount(0);
    QString m;
    QStringList files = FindAllFiles(dir, "*", true);
    numFiles = files.size();
    foreach (QString f, files) {
        processedFileCount++;

        if (processedFileCount%1000 == 0) {
            double percent = static_cast<double>(processedFileCount)/static_cast<double>(numFiles) * 100.0;
            Print(QString("Processed %1 of %2 files [%3%%]").arg(processedFileCount).arg(numFiles).arg(percent));
        }

        QHash<QString, QString> tags;
        if (img->GetImageFileTags(f, binpath, false, tags, m)) {
            //dcmseries[tags["SeriesInstanceUID"]].append(file);
            dcms[tags["PatientID"]][tags["StudyInstanceUID"]][tags["SeriesInstanceUID"]].append(f);
        }
    }
    Print(QString("Found %1 series").arg(dcms.size()));

    /* ---------- iterate through the subjects ---------- */
    for(QMap<QString, QMap<QString, QMap<QString, QStringList> > >::iterator a = dcms.begin(); a != dcms.end(); ++a) {
        QString subjectID = a.key();

        /* create a subject */
        subject currSubject;
        /* ---------- iterate through the studies ---------- */
        for(QMap<QString, QMap<QString, QStringList> >::iterator b = dcms[subjectID].begin(); b != dcms[subjectID].end(); ++b) {
            QString studyID = b.key();

            study currStudy;
            /* ---------- iterate through the series ---------- */
            for(QMap<QString, QStringList>::iterator c = dcms[subjectID][studyID].begin(); c != dcms[subjectID][studyID].end(); ++c) {
                QString seriesID = c.key();
                QStringList files = dcms[subjectID][studyID][seriesID];
                qint64 numfiles = files.size();

                QHash<QString, QString> tags;
                QString m;
                img->GetImageFileTags(files[0], binpath, true, tags, m);

                /* setup the series object */
                series currSeries;
                currSeries.description = tags["SeriesDescription"];
                currSeries.numFiles = numfiles;
                currSeries.params = tags;
                currSeries.seriesUID = tags["SeriesInstanceUID"];
                currSeries.files = files;

                /* setup/update the study object */
                currStudy.dateTime = QDateTime::fromString(tags["StudyDateTime"], "YYYY-MM-dd HH:mm:ss");
                currStudy.description = tags["StudyDescription"];
                currStudy.modality = tags["Modality"];
                currStudy.studyUID = tags["StudyInstanceUID"];
                currStudy.height = tags["PatientSize"].toDouble();
                currStudy.weight = tags["PatientWeight"].toDouble();

                /* setup/update the subject object */
                currSubject.birthdate = QDate::fromString(tags["PatientBirthDate"], "YYYY-MM-dd");
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

    delete img;

    return true;
}
