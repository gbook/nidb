/* ------------------------------------------------------------------------------
  NIDB moduleUpload.cpp
  Copyright (C) 2004 - 2020
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

#include "moduleUpload.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- moduleUpload ----------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::moduleUpload(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleUpload ---------------------------------- */
/* ---------------------------------------------------------- */
moduleUpload::~moduleUpload()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleUpload::Run() {
    n->WriteLog("Entering the upload module");

    QSqlQuery q;
    int ret(0);

    /* get list of uploads that are marked as uploadcomplete, with the upload details */
    q.prepare("select * from uploads where status = 'uploadcomplete'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            int upload_id = q.value("upload_id").toInt();
            QString upload_source = q.value("upload_source").toString();
            QString upload_datadir = q.value("upload_datadir").toString();
            int upload_destprojectid = q.value("upload_destprojectid").toInt();
            QString upload_modality = q.value("upload_modality").toString();
            bool upload_guessmodality = q.value("upload_guessmodality").toBool();

            QString uploadpath = QString("%1/%2").arg(n->cfg["uploadstagingdir"]).arg(upload_id);

            /* create temporary directory in uploadstagingdir */
            QString m;
            if (!n->MakePath(uploadpath, m)) {
                n->WriteLog("Error creating directory [" + uploadpath + "]  with message [" + m + "]");
                continue;
            }

            /* copy in files from uploadtmp or nfs to the uploadstagingdir */
            QString systemstring = QString("cp -ruv %1/* %2/").arg(upload_datadir).arg(uploadpath);
            n->SystemCommand(systemstring);

            /* get information about the uploaded data from the uploadstagingdir (before unzipping any zip files) */
            int c;
            qint64 b;
            n->GetDirSizeAndFileCount(uploadpath, c, b, true);
            n->WriteLog(QString("Upload directory [%1] contains [%2] files, and is [%3] bytes in size.").arg(uploadpath).arg(c).arg(b));

            /* unzip any files in the uploadstagingdir (3 passes) */
            n->WriteLog(n->UnzipDirectory(uploadpath, true));
            n->WriteLog(n->UnzipDirectory(uploadpath, true));
            n->WriteLog(n->UnzipDirectory(uploadpath, true));

            /* get information about the uploaded data from the uploadstagingdir (after unzipping any zip files) */
            int c;
            qint64 b;
            n->GetDirSizeAndFileCount(uploadpath, c, b, true);
            n->WriteLog(QString("After 3 passes of unzipping files, upload directory now [%1] contains [%2] files, and is [%3] bytes in size.").arg(uploadpath).arg(c).arg(b));

            /* if modality is known, find and parse all files matching that modality. --- Also keep list of files not matching that modality --- */
            QStringList DICOMmodalities;
            DICOMmodalities << "MR" << "CT" << "SR" << "CR" << "US" << "XA";
            if (DICOMmodalities.contains(upload_modality, Qt::CaseInsensitive)) {

            }

            /* else if modality is NOT known, search for DICOMs, ET, EEG files. ---- Also keep list of files that are still unknown ---- */

            /* organize by subject -> study -> series, add record(s) to appropriate tables */

            /* create a multilevel hash, for archiving data without a SeriesInstanceUID tag: dcms[institute][equip][modality][patient][dob][sex][date][series][files] */
            QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QStringList>>>>>>> files;

        }
    }

    n->WriteLog("Leaving the upload module");
    return ret;
}
