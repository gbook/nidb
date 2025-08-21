/* ------------------------------------------------------------------------------
  NIDB moduleImport.cpp
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

#include "moduleImport.h"
#include <QSqlQuery>

/* ---------------------------------------------------------- */
/* --------- moduleImport ----------------------------------- */
/* ---------------------------------------------------------- */
moduleImport::moduleImport(nidb *a)
{
    n = a;
    io = new archiveIO(n);
}


/* ---------------------------------------------------------- */
/* --------- ~moduleImport ---------------------------------- */
/* ---------------------------------------------------------- */
moduleImport::~moduleImport()
{
    delete io;
}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run the module
 * @return true if any data was processed, false otherwise
 */
bool moduleImport::Run() {
    n->Log("Entering the import module");

    bool ret(false);

    /* archive local data */
    ret |= ArchiveLocal();

    /* parse remotely imported data */
    ret |= ParseRemotelyImportedData();

    n->Log("Leaving the import module");
    return ret;

}


/* ---------------------------------------------------------- */
/* --------- ParseRemotelyImportedData ---------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parse any remotely imported data. This is data that would have been sent
 * to this server from a remote NiDB instance, and was received by the api.php
 * @return true if any data was found and processed, false otherwise
 */
bool moduleImport::ParseRemotelyImportedData() {

    bool ret(false);

    /* get list of pending uploads */
    QSqlQuery q;
    q.prepare("select * from import_requests where import_status = 'pending'");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            n->ModuleRunningCheckIn();

            int importrequestid = q.value("importrequest_id").toInt();
            int anonymize = q.value("import_anonymize").toInt();
            QString datatype = q.value("import_datatype").toString().trimmed();
            QString importstatus = q.value("import_status").toString().trimmed();

            /* We should not attempt to process this export if the status was changed elsewhere */
            if (importstatus != "pending")
                continue;

            QSqlQuery q2;
            q2.prepare("update import_requests set import_status = 'receiving', import_startdate = now() where importrequest_id = :importrequestid");
            q2.bindValue(":importrequestid",importrequestid);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

            QString uploaddir = QString("%1/%2").arg(n->cfg["uploadeddir"]).arg(importrequestid);
            QString outdir = QString("%1/%2").arg(n->cfg["incomingdir"]).arg(importrequestid);
            QString m;
            if (!MakePath(outdir, m)) {
                n->Log("Unable to create outdir [" + outdir + "] because of error [" + m + "]");
                continue;
            }

            if (datatype == "")
                datatype = "dicom";

            n->Log(QString("Datatype for %1 is [%2]").arg(importrequestid).arg(datatype));

            /* ----- get list of files in directory ----- */

            /* unzip the entire directory */
            io->AppendUploadLog(__FUNCTION__, "Unzipping files located in [" + uploaddir + "]");
            m = UnzipDirectory(uploaddir, true);
            io->AppendUploadLog(__FUNCTION__, "Unzip output" + m);

            QStringList files;
            files = FindAllFiles(uploaddir,"*");
            if (files.size() < 1) {
                SetImportRequestStatus(importrequestid, "error", "No files found in [" + uploaddir + "]");
                continue;
            }

            /* special procedures for each datatype */
            if ((datatype == "dicom") || (datatype == "parrec")) {
                n->Log("Working on [" + uploaddir + "]");

                /* go through the files */
                foreach (QString file, files) {

                    if (img->IsDICOMFile(file)) {
                        /* anonymize, replace project and site, rename, and dump to incoming */
                        n->Log("[" + file + "] is a DICOM file");
                        PrepareAndMoveDICOM(file, outdir, anonymize);
                    }
                    else if (file.endsWith(".par")) {
                        PrepareAndMovePARREC(file, outdir);
                    }
                    else if (file.endsWith(".rec")) {
                        /* .par/.rec are pairs, and only the .par contains meta-info, so leave the .rec alone */
                    }
                    else {
                        n->Log("[" + file + "] is NOT a DICOM file");
                    }
                }

                /* move the beh directory if it exists */
                QString behdir = uploaddir + "/beh";
                QDir bd(behdir);
                if (bd.exists()) {
                    QString systemstring = QString("mv -v %1 %2/").arg(behdir).arg(outdir);
                    SystemCommand(systemstring);
                }
            }
            else if ((datatype == "eeg") || (datatype == "et")) {
                n->Log("Encountered [" + datatype + "] import");

                /* move the files */
                QString systemstring = QString("touch %1/*; mv -v %1/* %2/").arg(uploaddir).arg(outdir);
                n->Log(SystemCommand(systemstring));

                n->Log("Finished moving the ET or EEG files");
            }
            else {
                n->Log("Datatype not recognized [" + datatype + "]");
            }

            SetImportRequestStatus(importrequestid, "received");

            /* delete the uploaded directory */
            n->Log("Attempting to remove [" + uploaddir + "]");
            if (!RemoveDir(uploaddir, m))
                n->Log("Unable to remove directory [" + uploaddir + "] because error [" + m + "]");
        }
        ret = true;
    }
    else {
        n->Log("No rows in import_requests found");
    }

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- PrepareAndMoveDICOM ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Used by the ParseRemotelyImportedData function to prepare (anonymize) and move the DICOM files to the
 * `incomingdir/<exportRowID>` directory
 * @param filepath Input DICOM file
 * @param outdir Output directory
 * @param anonymize true if the file should be anonymized
 * @return true if successful, false otherwise
 */
bool moduleImport::PrepareAndMoveDICOM(QString filepath, QString outdir, bool anonymize) {

    if (anonymize) {
        gdcm::Anonymizer anon;
        std::vector<gdcm::Tag> empty_tags;
        std::vector<gdcm::Tag> remove_tags;
        std::vector< std::pair<gdcm::Tag, std::string> > replace_tags;
        gdcm::Tag tag;
        const char *dcmfile = filepath.toStdString().c_str();

        tag.ReadFromCommaSeparatedString("0008, 0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
        tag.ReadFromCommaSeparatedString("0008, 1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
        tag.ReadFromCommaSeparatedString("0008, 1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
        tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
        tag.ReadFromCommaSeparatedString("0010, 0030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );

        QString m;
        img->AnonymizeDicomFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags, m);
    }
    /* if the filename exists in the outgoing directory, prepend some junk to it, since the filename is unimportant
       some directories have all their files named IM0001.dcm ..... so, inevitably, something will get overwrtten, which is bad */
    QString newfilename = QFileInfo(filepath).baseName() + GenerateRandomString(15) + "." + QFileInfo(filepath).completeSuffix();

    QString systemstring = QString("touch %1; mv %1 %2/%3").arg(filepath).arg(outdir).arg(newfilename);
    SystemCommand(systemstring, false);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- PrepareAndMovePARREC --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Used by the ParseRemotelyImportedData function to move par/rec files to the
 * `incomingdir/<exportRowID>` directory
 * @param parfilepath Path to the .par file
 * @param outdir Output directory
 * @return true if successful, false otherwise
 */
bool moduleImport::PrepareAndMovePARREC(QString parfilepath, QString outdir) {

    n->Log("PrepareAndMovePARREC(" + parfilepath + "," + outdir + ")");

    /* if the filename exists in the outgoing directory, prepend some junk to it, since the filename is unimportant
       some directories have all their files named IM0001.dcm ..... so something will get overwrtten unless the files are renamed */

    QString padding = GenerateRandomString(15);
    QString oldpath = QFileInfo(parfilepath).path();
    QString parfilename = QFileInfo(parfilepath).fileName();
    QString newparfilename = padding + parfilename;
    QString newparfilepath = outdir + "/" + newparfilename;

    n->Log(SystemCommand(QString("touch %1; mv -v %1 %2").arg(parfilepath).arg(newparfilepath)));

    QString recfilename = parfilename.replace(".par", ".rec", Qt::CaseInsensitive);
    QString newrecfilename = newparfilename.replace(".par", ".rec", Qt::CaseInsensitive);
    QString recfilepath = oldpath + "/" + recfilename;
    QString newrecfilepath = outdir + "/" + newrecfilename;

    n->Log(SystemCommand(QString("touch %1; mv -v %1 %2").arg(recfilepath).arg(newrecfilepath)));

    return true;
}


/* ---------------------------------------------------------- */
/* --------- SetImportRequestStatus ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Set the status for an import request (remote receipt of data)
 * @param importid ImportRowID
 * @param status status can be `pending`, `deleting`, `receiving`, `received`, `complete`, `error`, `processing`, `cancelled`, `canceled`
 * @param msg A string message
 * @return true if successful, false otherwise
 */
bool moduleImport::SetImportRequestStatus(int importid, QString status, QString msg) {

    n->Log("Setting status to ["+status+"]");

    if (((status == "pending") || (status == "deleting") || (status == "receiving")|| (status == "received") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (importid > 0)) {
        QSqlQuery q;
        if (msg.trimmed() == "") {
            q.prepare("update import_requests set import_status = :status where importrequest_id = :importid");
            q.bindValue(":importid", importid);
            q.bindValue(":status", status);
        }
        else {
            q.prepare("update import_requests set import_status = :status, import_message = :msg where importrequest_id = :importid");
            q.bindValue(":importid", importid);
            q.bindValue(":msg", msg);
            q.bindValue(":status", status);
        }
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        return true;
    }
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- ArchiveLocal ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Archive data from the local disk, from dcmrcv and remote imports.
 * @return true if any data processed, false otherwise
 */
bool moduleImport::ArchiveLocal() {
    //n->Log("Entering the import module");

    bool ret(false);

    /* before archiving the directory, delete any rows older than 14 days from the importlogs table */
    QSqlQuery q("delete from importlogs where importstartdate < date_sub(now(), interval 14 day)");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    q.prepare("delete from import_file_log where importfile_datetime < date_sub(now(), interval 14 day)");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    io->SetUploadID(0);
    /* ----- Step 1 - archive all files in the main directory ----- */
    if (ParseDirectory(n->cfg["incomingdir"], 0))
        ret = true;

    /* ----- Step 2 - parse the sub directories -----
     * if there's a sub directory, the directory name is a importRowID from the import table,
     * which contains additional information about the files being imported, such as project and site
    */
    QStringList dirs = FindAllDirs(n->cfg["incomingdir"],"",false, false);
    if (dirs.size() > 0) {
        n->Log(QString("incomingdir [%2] contains [%1] sub-directories").arg(dirs.size()).arg(n->cfg["incomingdir"]));
        n->Log("Directories found: " + dirs.join("|"));
        foreach (QString dir, dirs) {
            n->Log("Found dir ["+dir+"]");
            QString fulldir = QString("%1/%2").arg(n->cfg["incomingdir"]).arg(dir);
            if (ParseDirectory(fulldir, dir.toInt())) {
                /* check if the directrory is empty */
                if (QDir(fulldir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0) {
                    QString m;
                    if (RemoveDir(fulldir, m))
                        n->Log("Removed directory [" + fulldir + "]");
                    else
                        n->Log("Error removing directory [" + fulldir + "] [" + m + "]");

                    ret = ret | true;
                }
            }
            else
                n->Log(QString("ParseDirectory(%1,%2) returned false").arg(fulldir).arg(dir.toInt()));

            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) {
                n->Log("Module disabled. Stopping module.");
                return ret;
            }
        }
    }

    //n->Log("Leaving the import module");

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- GetImportStatus -------------------------------- */
/* ---------------------------------------------------------- */
QString moduleImport::GetImportStatus(int importid) {
    QSqlQuery q;
    QString status;
    q.prepare("select import_status from import_requests where importrequest_id = :id");
    q.bindValue(":id", importid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        q.first();
        status = q.value("import_status").toString();
    }

    n->Log("Got import status of [" + status + "]");

    return status;
}


/* ---------------------------------------------------------- */
/* --------- SetImportStatus -------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::SetImportStatus(int importid, QString status, QString msg, QString report, bool enddate) {

    QString sql;

    if (((status == "") || (status == "archiving") || (status == "archived") || (status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (importid > 0)) {
        sql = "update import_requests set import_status = :status";

        if (msg.trimmed() != "")
            sql += ", import_message = :msg";
        if (report.trimmed() != "")
            sql += ", archivereport = :report";
        if (enddate)
            sql += ", import_enddate = now()";
        sql += " where importrequest_id = :importid";

        QSqlQuery q;
        q.prepare(sql);
        q.bindValue(":status", status);
        q.bindValue(":msg", msg);
        q.bindValue(":report", report);
        q.bindValue(":importid", importid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

        n->Log("Set import status to ["+status+"]");
        return true;
    }
    else {
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- ParseDirectory --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::ParseDirectory(QString dir, int importid) {

    n->Log(QString("********** Working on directory [" + dir + "] with importRowID [%1] **********").arg(importid));
    n->ModuleRunningCheckIn();

    dcmseries.clear();
    QString archivereport;
    //QString importStatus;
    QString importModality;
    QString importDatatype;
    int importSiteID(-1);
    int importProjectID(-1);
    //int importMatchIDOnly(-1);
    QString importSeriesNotes;
    QString importAltUIDs;

    performanceMetric perf;
    perf.Start();

    QString subjectMatchCriteria("uid");
    QString studyMatchCriteria("ModalityStudyDate");
    QString seriesMatchCriteria("SeriesNum");

    /* if there is an importRowID, check to see how the import is doing */
    if (importid > 0) {
        subjectMatchCriteria = "uidOrAltUID";
        studyMatchCriteria = "ModalityStudyDate";
        seriesMatchCriteria = "SeriesNum";

        QSqlQuery q;
        q.prepare("select * from import_requests where importrequest_id = :importid");
        q.bindValue(":importid",importid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            QString importStatus = q.value("import_status").toString();
            //QString importModality = q.value("import_modality").toString();
            //QString importDatatype = q.value("import_datatype").toString();
            importSiteID = q.value("import_siteid").toInt();
            importProjectID = q.value("import_projectid").toInt();
            //importMatchIDOnly = q.value("import_matchidonly").toInt();
            importSeriesNotes = q.value("import_seriesnotes").toString();
            importAltUIDs = q.value("import_altuids").toString();

            if ((importStatus == "complete") || (importStatus == "") || (importStatus == "received") || (importStatus == "error")) { }
            else {
                n->Log("This import is not complete. Status is [" + importStatus + "]. Skipping.");
                /* cleanup so this import can continue at another time */
                SetImportStatus(importid, "", "", "", false);

                n->Log(perf.End());
                return false;
            }
        }
    }
    else {
        subjectMatchCriteria = "uidOrAltUID";
        studyMatchCriteria = "ModalityStudyDate";
        seriesMatchCriteria = "SeriesNum";
    }

    SetImportStatus(importid, "archiving", "", "", false);

    int ret(false);
    int i(0);
    bool iscomplete = false;
    bool okToDeleteDir = true;

    /* ----- parse all files in the directory ----- */
    QStringList files = FindAllFiles(dir, "*");
    qint64 numfiles = files.size();
    n->Log(QString("Found %1 total files in path [%2]").arg(numfiles).arg(dir));
    int processedFileCount(0);
    int numFilesTooYoung(0);
    foreach (QString file, files) {

        perf.numFilesRead++;
        /* check if the file exists. par/rec files may be moved from previous steps, so check if they still exist */
        if (QFile::exists(file)) {
            /* check the file size */
            qint64 fsize = QFileInfo(file).size();
            perf.numBytesRead += fsize;
            if (fsize < 1) {
                n->Log(QString("File [%1] - size [%2] is 0 bytes!").arg(file).arg(fsize));
                SetImportStatus(importid, "error", "File has size of 0 bytes", QString("File [" + file + "] is empty"), true);
                perf.numFilesError++;
                QString m;
                if (!MoveFile(file, n->cfg["problemdir"], m))
                    n->Log(QString("Unable to move [%1] to [%2], with error [%3]").arg(file).arg(n->cfg["problemdir"]).arg(m));
                continue;
            }
        }
        else {
            n->Log("File [" + file + "] no longer exists. That's ok if it's a .rec file");
            continue;
        }

        /* check if the file has been modified in the past 1 minutes (-60 seconds)
         * if so, the file may still be being copied, so skip it */
        QDateTime now = QDateTime::currentDateTime();
        qint64 fileAgeInSec = now.secsTo(QFileInfo(file).lastModified());
        if (fileAgeInSec > -60) {
            n->Debug(QString("File [%1] has an age of [%2] sec").arg(file).arg(fileAgeInSec));
            numFilesTooYoung++;
            perf.numFilesIgnored++;
            okToDeleteDir = false;
            continue;
        }

        /* display how many files have been checked so far, and start archiving them if we've reached the chunk size */
        processedFileCount++;

        if (processedFileCount%1000 == 0) {
            n->Log(QString("Processed %1 files...").arg(processedFileCount));
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) {
                n->Log("Module disabled. Stopping module.");
                //okToDeleteDir = false;
                n->Log(perf.End());
                return true;
            }
        }

		int chunksize(5000);
        if (n->cfg["importchunksize"].toInt() > 0)
            chunksize = n->cfg["importchunksize"].toInt();

        if (processedFileCount >= chunksize) {
            n->Log(QString("Checked [%1] files, going to archive them now").arg(processedFileCount));
            break;
        }

        /* make sure this file still exists... another instance of the program may have altered it */
        if (QFile::exists(file)) {

            QString ext = QFileInfo(file).completeSuffix().toLower();
            if (ext == "par") {
                n->Log("Filetype is .par");

                QString m;

                if (!io->ArchiveParRecSeries(importid, file)) {
                    n->Log(QString("InsertParRec(%1, %2) failed: [%3]").arg(file).arg(importid).arg(m));

                    QSqlQuery q;
                    q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, 'PARREC', :importid, now(), :msg)");
                    q.bindValue(":file",file);
                    q.bindValue(":importid",importid);
                    q.bindValue(":msg",m);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

                    m="";
                    if (!MoveFile(file, n->cfg["problemdir"], m))
                        n->Log(QString("Unable to move [%1] to [%2]").arg(file).arg(n->cfg["problemdir"]).arg(m));

                    SetImportStatus(importid, "error", "Problem inserting PAR/REC: " + m, archivereport, true);
                }
                else {
                    iscomplete = true;
                }
                i++;
            }
            else if (ext == "rec") {
                n->Log("Filetype is a .rec");
            }
            else if (ext == "sqrl") {
                n->Log("Filetype is a .sqrl package");

                QString m;
                UploadOptions options;
                options.projectRowID = importProjectID;
                options.subjectMatchCriteria = "patientid";
                options.studyMatchCriteria = "modalitystudydate";
                options.seriesMatchCriteria = "seriesnum";
                io->ArchiveSquirrelPackage(options, file, m);
            }
            else if ((ext == "cnt") || (ext == "3dd") || (ext == "dat") || (ext == "edf") || (importModality == "eeg") || (importDatatype == "eeg") || (importModality == "et") || (ext == "et") ) {
                n->Log("Filetype is an EEG or ET file");

                QString m;

                if (!io->ArchiveEEGSeries(importid, file)) {
                    n->Log(QString("InsertEEG(%1, %2) failed: [%3]").arg(file).arg(importid).arg(m));
                    QSqlQuery q;
                    q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, :datatype, :importid, now(), :msg)");
                    q.bindValue(":file", file);
                    q.bindValue(":datatype", importDatatype.toUpper());
                    q.bindValue(":id", importid);
                    q.bindValue(":msg", m + " - moving to problem directory");
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                    if (!MoveFile(file, n->cfg["problemdir"], m))
                        n->Log(QString("Unable to move [%1] to [%2], with error [%3]").arg(file).arg(n->cfg["problemdir"]).arg(m));

                    SetImportStatus(importid, "error", "Problem inserting " + importDatatype.toUpper() + " - subject ID did not exist", archivereport, true);
                }
                else {
                    iscomplete = true;
                }
                i++;
            }
            else {
                /* check if this is a DICOM file */
                QHash<QString, QString> tags;
                i++;

                QString m;
                bool csa = false;
                if (n->cfg["enablecsa"] == "1") csa = true;
                QString binpath = n->cfg["nidbdir"] + "/bin";
                if (img->GetImageFileTags(file, binpath, csa, tags, m)) {

                    dcmseries[tags["SeriesInstanceUID"]].append(file);

                    QFileInfo fi(file);
                    fi.lastModified();

                    QSqlQuery q;
                    q.prepare("insert into import_file_log (filename, importfile_datetime, file_datetime, file_size, file_type, Modality, PatientID, StudyUID, SeriesUID, StudyDescription, SeriesDescription, SeriesNumber, AcquisitionNumber, InstanceNumber) values (:fileName, now(), :fileDatetime, :fileSize, :fileType, :Modality, :PatientID, :StudyUID, :SeriesUID, :StudyDescription, :SeriesDescription, :SeriesNumber, :AcquisitionNumber, :InstanceNumber)");
                    q.bindValue(":fileName", file);
                    q.bindValue(":fileDatetime", fi.lastModified());
                    q.bindValue(":fileSize", fi.size());
                    q.bindValue(":fileType", "DICOM");
                    q.bindValue(":Modality", tags["Modality"]);
                    q.bindValue(":PatientID", tags["PatientID"]);
                    q.bindValue(":StudyUID", tags["StudyUID"]);
                    q.bindValue(":SeriesUID", tags["SeriesUID"]);
                    q.bindValue(":StudyDescription", tags["StudyDescription"]);
                    q.bindValue(":SeriesDescription", tags["SeriesDescription"]);
                    q.bindValue(":SeriesNumber", tags["SeriesNumber"]);
                    q.bindValue(":AcquisitionNumber", tags["AcquisitionNumber"]);
                    q.bindValue(":InstanceNumber", tags["InstanceNumber"]);

                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

                }
                else {
                    qint64 fsize = QFileInfo(file).size();
                    n->Log(QString("Unable to parse file [%1] (size [%2]) as a DICOM file. Moving to [%3]").arg(file).arg(fsize).arg(n->cfg["problemdir"]));

                    n->Log(m);

                    QSqlQuery q;
                    m = "Not a DICOM file, moving to the problem directory";
                    q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, :datatype, :importid, now(), :msg)");
                    q.bindValue(":file", file);
                    q.bindValue(":datatype", importDatatype.toUpper());
                    q.bindValue(":id", importid);
                    q.bindValue(":msg", m);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                    QString m2;
                    if (!MoveFile(file, n->cfg["problemdir"], m2))
                        n->Log(QString("Unable to move [%1] to [%2], with error [%3]").arg(file).arg(n->cfg["problemdir"]).arg(m2));

                    /* change the import status to reflect the error */
                    if (importid > 0)
                        SetImportStatus(importid, "error", "Problem inserting " + importDatatype.toUpper() + ": " + m, archivereport, true);
                }
            }
        }
        else {
            n->Log(file + " does not exist");
        }
    }

    n->Log(QString("Ignoring %1 files that are less than 60 seconds old").arg(numFilesTooYoung));

    //n->Log(QString("dcmseries contains [%1] entries").arg(dcmseries.size()));
    /* done reading all of the files in the directory (more may show up, but we'll get to those later)
     * now archive them */
    for(QMap<QString, QStringList>::iterator a = dcmseries.begin(); a != dcmseries.end(); ++a) {
        QString seriesuid = a.key();

        n->Log(QString("Archiving %1 files for SeriesUID [" + seriesuid + "]").arg(dcmseries[seriesuid].size()));
        QStringList files2 = dcmseries[seriesuid];

        performanceMetric perf2;
        perf2.Start();
        if (io->ArchiveDICOMSeries(importid, -1, -1, -1, subjectMatchCriteria, studyMatchCriteria, seriesMatchCriteria, importProjectID, "", importSiteID, importSeriesNotes, importAltUIDs, files2, perf2))
            iscomplete = true;
        else
            iscomplete = false;
        n->Log(perf2.End());

        n->ModuleRunningCheckIn();
        /* check if this module should be running now or not */
        if (!n->ModuleCheckIfActive()) {
            n->Log("Module disabled. Stopping module.");
            /* cleanup so this import can continue another time */
            QSqlQuery q;
            q.prepare("update import_requests set import_status = '', import_enddate = now(), archivereport = :archivereport where importrequest_id = :importid");
            q.bindValue(":archivereport", archivereport);
            q.bindValue(":importid", importid);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

            n->Log(perf.End());

            return true;
        }
    }

    if (importid > 0 && iscomplete && okToDeleteDir) {
        QDir d(dir);
        if (d.exists()) {
            /* delete the uploaded directory */
            n->Log("Attempting to remove [" + dir + "]");
            QString m;
            if (!RemoveDir(dir, m))
                n->Log("Unable to delete directory [" + dir + "] because of error [" + m + "]");
        }
        SetImportStatus(importid, "archived", importDatatype.toUpper() + " successfully archived", archivereport, true);
    }
    else
        SetImportStatus(importid, "checked", "Files less than 2 minutes old in directory", "", false);

    if (i > 0) {
        n->Log("Finished archiving data for [" + dir + "]");
        ret = true;
    }
    else {
        n->Log("Nothing to do for [" + dir + "]");
        ret = false;
    }

    n->Log(perf.End());
    return ret;
}
