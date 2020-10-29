/* ------------------------------------------------------------------------------
  NIDB moduleImport.cpp
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

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleImport::Run() {
	n->WriteLog("Entering the import module");

	int ret(0);

	/* before archiving the directory, delete any rows older than 4 days from the importlogs table */
	QSqlQuery q("delete from importlogs where importstartdate < date_sub(now(), interval 4 day)");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    io->SetUploadID(0);
	/* ----- Step 1 - archive all files in the main directory ----- */
	if (ParseDirectory(n->cfg["incomingdir"], 0))
		ret = 1;

	/* ----- Step 2 - parse the sub directories -----
	 * if there's a sub directory, the directory name is a rowID from the import table,
	 * which contains additional information about the files being imported, such as project and site
	*/
	QStringList dirs = n->FindAllDirs(n->cfg["incomingdir"],"",false, false);
	n->WriteLog(QString("Found [%1] directories in [%2]").arg(dirs.size()).arg(n->cfg["incomingdir"]));
	n->WriteLog("Directories found: " + dirs.join("|"));
	foreach (QString dir, dirs) {
		n->WriteLog("Found dir ["+dir+"]");
		QString fulldir = QString("%1/%2").arg(n->cfg["incomingdir"]).arg(dir);
		if (ParseDirectory(fulldir, dir.toInt())) {
			/* check if the directrory is empty */
			if (QDir(fulldir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0) {
				QString m;
				if (n->RemoveDir(fulldir, m))
					n->WriteLog("Removed directory [" + fulldir + "]");
				else
					n->WriteLog("Error removing directory [" + fulldir + "] [" + m + "]");

				ret = ret | 1;
			}
		}
		else
			n->WriteLog(QString("ParseDirectory(%1,%2) returned false").arg(fulldir).arg(dir.toInt()));

		n->ModuleRunningCheckIn();
		if (!n->ModuleCheckIfActive()) {
			n->WriteLog("Module is now inactive, stopping the module");
			return ret;
		}
	}

	n->WriteLog("Leaving the import module");

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

	n->WriteLog("Got import status of [" + status + "]");

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

		n->WriteLog("Set import status to ["+status+"]");
		return true;
	}
	else {
		return false;
	}
}


/* ---------------------------------------------------------- */
/* --------- ParseDirectory --------------------------------- */
/* ---------------------------------------------------------- */
int moduleImport::ParseDirectory(QString dir, int importid) {

	n->WriteLog(QString("********** Working on directory [" + dir + "] with importRowID [%1] **********").arg(importid));
	n->ModuleRunningCheckIn();

	dcmseries.clear();
	QString archivereport;
	QString importStatus;
	QString importModality;
	QString importDatatype;
    int importSiteID;
    int importProjectID;
    int importMatchIDOnly;
    QString importSeriesNotes;
    QString importAltUIDs;

	/* if there is an importRowID, check to see how that thing is doing */
	if (importid > 0) {
		QSqlQuery q;
		q.prepare("select * from import_requests where importrequest_id = :importid");
		q.bindValue(":importid",importid);
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() > 0) {
			q.first();
			QString importStatus = q.value("import_status").toString();
			QString importModality = q.value("import_modality").toString();
			QString importDatatype = q.value("import_datatype").toString();
            importSiteID = q.value("import_siteid").toInt();
            importProjectID = q.value("import_projectid").toInt();
            importMatchIDOnly = q.value("import_matchidonly").toInt();
            importSeriesNotes = q.value("import_seriesnotes").toString();
            importAltUIDs = q.value("import_altuids").toString();

			if ((importStatus == "complete") || (importStatus == "") || (importStatus == "received") || (importStatus == "error")) { }
			else {
				n->WriteLog("This import is not complete. Status is [" + importStatus + "]. Skipping.");
				/* cleanup so this import can continue another time */
				SetImportStatus(importid, "", "", "", false);

				return 0;
			}
		}
	}

	SetImportStatus(importid, "archiving", "", "", false);

	int ret(0);
	int i(0);
	bool iscomplete = false;
	bool okToDeleteDir = true;

	/* ----- parse all files in /incoming ----- */
	QStringList files = n->FindAllFiles(dir, "*");
	int numfiles = files.size();
	n->WriteLog(QString("Found [%1] files in [%2]").arg(numfiles).arg(dir));
	int processedFileCount(0);
	foreach (QString file, files) {

		/* check if the file exists. par/rec files may be moved from previous steps, so check if they still exist */
		if (QFile::exists(file)) {
			/* check the file size */
			qint64 fsize = QFileInfo(file).size();
			if (fsize < 1) {
				n->WriteLog(QString("File [%1] - size [%2] is 0 bytes!").arg(file).arg(fsize));
				SetImportStatus(importid, "error", "File has size of 0 bytes", QString("File [" + file + "] is empty"), true);
				if (!n->MoveFile(file, n->cfg["problemdir"]))
					n->WriteLog("Unable to move ["+file+"] to ["+n->cfg["problemdir"]+"]");
				continue;
			}
		}
		else {
			n->WriteLog("File [" + file + "] no longer exists. That's ok if it's a .rec file");
			continue;
		}

		/* check if the file has been modified in the past 1 minutes (-60 seconds)
		 * if so, the file may still be being copied, so skip it */
		QDateTime now = QDateTime::currentDateTime();
		qint64 fileAgeInSec = now.secsTo(QFileInfo(file).lastModified());
		if (fileAgeInSec > -60) {
			n->WriteLog(QString("File [%1] has an age of [%2] sec").arg(file).arg(fileAgeInSec));
			okToDeleteDir = false;
			continue;
		}

		/* display how many files have been checked so far, and start archiving them if we've reached the chunk size */
		processedFileCount++;

		if (processedFileCount%1000 == 0) {
			n->WriteLog(QString("Processed %1 files...").arg(processedFileCount));
			n->ModuleRunningCheckIn();
			if (!n->ModuleCheckIfActive()) {
				n->WriteLog("Module is now inactive, stopping the module");
				okToDeleteDir = false;
				return 1;
			}
		}
		int chunksize(5000);
		if (n->cfg["importchunksize"].toInt() > 0)
			chunksize = n->cfg["importchunksize"].toInt();

		if (processedFileCount >= chunksize) {
			n->WriteLog(QString("Checked [%1] files, going to archive them now").arg(processedFileCount));
			break;
		}

		/* make sure this file still exists... another instance of the program may have altered it */
		if (QFile::exists(file)) {

			QString dir = QFileInfo(file).path();
			QString fname = QFileInfo(file).fileName();
			QString ext = QFileInfo(file).completeSuffix().toLower();
			if (ext == "par") {
				n->WriteLog("Filetype is .par");

				QString m;
				QString report;

                if (!io->InsertParRec(importid, file)) {
					n->WriteLog(QString("InsertParRec(%1, %2) failed: [%3]").arg(file).arg(importid).arg(m));

					QSqlQuery q;
					q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, 'PARREC', :importid, now(), :msg)");
					q.bindValue(":file",file);
					q.bindValue(":importid",importid);
					q.bindValue(":msg",m);
					n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

					if (!n->MoveFile(file, n->cfg["problemdir"]))
						n->WriteLog("Unable to move ["+file+"] to ["+n->cfg["problemdir"]+"]");

					SetImportStatus(importid, "error", "Problem inserting PAR/REC: " + m, archivereport, true);
				}
				else {
					iscomplete = true;
				}
				i++;
			}
			else if (ext == "rec")
				n->WriteLog("Filetype is a .rec");
			else if ((ext == "cnt") || (ext == "3dd") || (ext == "dat") || (ext == "edf") || (importModality == "eeg") || (importDatatype == "eeg") || (importModality == "et") || (ext == "et") ) {
				n->WriteLog("Filetype is an EEG or ET file");

				QString report;
				QString m;

                if (!io->InsertEEG(importid, file)) {
					n->WriteLog(QString("InsertEEG(%1, %2) failed: [%3]").arg(file).arg(importid).arg(m));
					QSqlQuery q;
					q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, :datatype, :importid, now(), :msg)");
					q.bindValue(":file", file);
					q.bindValue(":datatype", importDatatype.toUpper());
					q.bindValue(":id", importid);
					q.bindValue(":msg", m + " - moving to problem directory");
					n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
					if (!n->MoveFile(file, n->cfg["problemdir"]))
						n->WriteLog("Unable to move ["+file+"] to ["+n->cfg["problemdir"]+"]");

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
				QString filetype;
				i++;

                if (n->GetImageFileTags(file, tags)) {
					dcmseries[tags["SeriesInstanceUID"]].append(file);
				}
				else {
					qint64 fsize = QFileInfo(file).size();
                    n->WriteLog(QString("Unable to parse file [%1] (size [%2]) as a DICOM file. Moving to [%3]").arg(file).arg(fsize).arg(n->cfg["problemdir"]));

					QSqlQuery q;
					QString m = "Not a DICOM file, moving to the problem directory";
					q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, :datatype, :importid, now(), :msg)");
					q.bindValue(":file", file);
					q.bindValue(":datatype", importDatatype.toUpper());
					q.bindValue(":id", importid);
					q.bindValue(":msg", m);
					n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
					if (!n->MoveFile(file, n->cfg["problemdir"]))
						n->WriteLog("Unable to move ["+file+"] to ["+n->cfg["problemdir"]+"]");

					/* change the import status to reflect the error */
					if (importid > 0)
						SetImportStatus(importid, "error", "Problem inserting " + importDatatype.toUpper() + ": " + m, archivereport, true);
				}
			}
		}
		else {
			n->WriteLog(file + " does not exist");
		}
	}

	n->WriteLog(QString("dcmseries contains [%1] entries").arg(dcmseries.size()));
	/* done reading all of the files in the directory (more may show up, but we'll get to those later)
	 * now archive them */
	for(QMap<QString, QStringList>::iterator a = dcmseries.begin(); a != dcmseries.end(); ++a) {
		QString seriesuid = a.key();

        n->WriteLog(QString("Getting list of files for seriesuid [" + seriesuid + "] - number of files is [%1]").arg(dcmseries[seriesuid].size()));
		QStringList files = dcmseries[seriesuid];

        QString subjectMatchCriteria("PatientID");
        QString studyMatchCriteria("ModalityStudyDate");
        QString seriesMatchCriteria("SeriesNum");

        if (io->ArchiveDICOMSeries(importid, -1, -1, -1, subjectMatchCriteria, studyMatchCriteria, seriesMatchCriteria, importProjectID, importSiteID, importSeriesNotes, importAltUIDs, files))
			iscomplete = true;
		else
			iscomplete = false;

		n->ModuleRunningCheckIn();
		/* check if this module should be running now or not */
		if (!n->ModuleCheckIfActive()) {
			n->WriteLog("Not supposed to be running right now");
			/* cleanup so this import can continue another time */
			QSqlQuery q;
			q.prepare("update import_requests set import_status = '', import_enddate = now(), archivereport = :archivereport where importrequest_id = :importid");
			q.bindValue(":archivereport", archivereport);
			q.bindValue(":importid", importid);
			n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
			return 1;
		}
	}

	if (importid > 0 && iscomplete && okToDeleteDir) {
		QDir d(dir);
		if (d.exists()) {
			/* delete the uploaded directory */
			n->WriteLog("Attempting to remove [" + dir + "]");
			QString m;
			if (!n->RemoveDir(dir, m))
				n->WriteLog("Unable to delete directory [" + dir + "] because of error [" + m + "]");
		}
		SetImportStatus(importid, "archived", importDatatype.toUpper() + " successfully archived", archivereport, true);
	}
	else
		SetImportStatus(importid, "checked", "Files less than 2 minutes old in directory", "", false);

	if (i > 0) {
		n->WriteLog("Finished archiving data for [" + dir + "]");
		ret = 1;
	}
	else {
		n->WriteLog("Nothing to do for [" + dir + "]");
		ret = 0;
	}

	return ret;
}
