/* ------------------------------------------------------------------------------
  NIDB moduleImport.cpp
  Copyright (C) 2004 - 2019
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

	/* ----- Step 1 - archive all files in the main directory ----- */
	if (ParseDirectory(n->cfg["incomingdir"], 0))
		ret = 1;

	/* ----- Step 2 - parse the sub directories -----
	 * if there's a sub directory, the directory name is a rowID from the import table,
	 * which contains additional information about the files being imported, such as project and site
	*/
	QStringList dirs = n->FindAllDirs(n->cfg["incomingdir"],"",false, false);
	n->WriteLog(QString("Found [%1] directories in [%2]").arg(dirs.size()).arg(n->cfg["incomingdir"]));
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

	n->WriteLog("Got import status of ["+status+"]");

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

		/* check the file size */
		qint64 fsize = QFileInfo(file).size();
		if (fsize < 1) {
			n->WriteLog(QString("File [%1] - size [%2] is 0 bytes!").arg(file).arg(fsize));
			SetImportStatus(importid, "error", "File has size of 0 bytes", QString("File [" + file + "] is empty"), true);
			if (!n->MoveFile(file, n->cfg["problemdir"]))
				n->WriteLog("Unable to move ["+file+"] to ["+n->cfg["problemdir"]+"]");
			continue;
		}

		/* check if the file has been modified in the past 2 minutes (-120 seconds)
		 * if so, the file may still be being copied, so skip it */
		QDateTime now = QDateTime::currentDateTime();
		qint64 fileAgeInSec = now.secsTo(QFileInfo(file).lastModified());
		if (fileAgeInSec > -120) {
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

				if (!InsertParRec(importid, file, archivereport)) {
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

				if (!InsertEEG(importid, file, archivereport)) {
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

					SetImportStatus(importid, "error", "Problem inserting " + importDatatype.toUpper() + ": " + m, archivereport, true);
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

				if (ParseDICOMFile(file, tags)) {
					dcmseries[tags["SeriesInstanceUID"]].append(file);
				}
				else {
					n->WriteLog(QString("File [%1] - size [%2] is not a dicom file. Moving to [%3]").arg(file).arg(fsize).arg(n->cfg["problemdir"]));

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

		n->WriteLog(QString("Getting list of files for seriesuid ["+seriesuid+"] - number of files is [%1]").arg(dcmseries[seriesuid].size()));
		QStringList files = dcmseries[seriesuid];

		if (InsertDICOMSeries(importid, files, archivereport))
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
		SetImportStatus(importid, "archived", "DICOM successfully archived", archivereport, true);
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


/* ---------------------------------------------------------- */
/* --------- ParseDICOMFile --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::ParseDICOMFile(QString file, QHash<QString, QString> &tags) {

	if (!QFile::exists(file)) {
		n->WriteLog(QString("File [%1] does not exist - check C!").arg(file));
		return false;
	}

	/* check if the file is readable */
	gdcm::Reader r;
	r.SetFileName(file.toStdString().c_str());
	if (!r.Read())
		return false;

	gdcm::StringFilter sf;
	sf = gdcm::StringFilter();
	sf.SetFile(r.GetFile());

	/* get all of the DICOM tags...
	 * we're not using an iterator because we want to know exactly what tags we have and dont have */

	tags["FileMetaInformationGroupLength"] =	QString(sf.ToString(gdcm::Tag(0x0002,0x0000)).c_str()).trimmed(); /* FileMetaInformationGroupLength */
	tags["FileMetaInformationVersion"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0001)).c_str()).trimmed(); /* FileMetaInformationVersion */
	tags["MediaStorageSOPClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0002)).c_str()).trimmed(); /* MediaStorageSOPClassUID */
	tags["MediaStorageSOPInstanceUID"] =		QString(sf.ToString(gdcm::Tag(0x0002,0x0003)).c_str()).trimmed(); /* MediaStorageSOPInstanceUID */
	tags["TransferSyntaxUID"] =					QString(sf.ToString(gdcm::Tag(0x0002,0x0010)).c_str()).trimmed(); /* TransferSyntaxUID */
	tags["ImplementationClassUID"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0012)).c_str()).trimmed(); /* ImplementationClassUID */
	tags["ImplementationVersionName"] =			QString(sf.ToString(gdcm::Tag(0x0002,0x0013)).c_str()).trimmed(); /* ImplementationVersionName */

	tags["SpecificCharacterSet"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0005)).c_str()).trimmed(); /* SpecificCharacterSet */
	tags["ImageType"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0008)).c_str()).trimmed(); /* ImageType */
	tags["InstanceCreationDate"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0012)).c_str()).trimmed(); /* InstanceCreationDate */
	tags["InstanceCreationTime"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0013)).c_str()).trimmed(); /* InstanceCreationTime */
	tags["SOPClassUID"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0016)).c_str()).trimmed(); /* SOPClassUID */
	tags["SOPInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0018)).c_str()).trimmed(); /* SOPInstanceUID */
	tags["StudyDate"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0020)).c_str()).trimmed(); /* StudyDate */
	tags["SeriesDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0021)).c_str()).trimmed(); /* SeriesDate */
	tags["AcquisitionDate"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0022)).c_str()).trimmed(); /* AcquisitionDate */
	tags["ContentDate"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0023)).c_str()).trimmed(); /* ContentDate */
	tags["StudyTime"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0030)).c_str()).trimmed(); /* StudyTime */
	tags["SeriesTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0031)).c_str()).trimmed(); /* SeriesTime */
	tags["AcquisitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0032)).c_str()).trimmed(); /* AcquisitionTime */
	tags["ContentTime"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0033)).c_str()).trimmed(); /* ContentTime */
	tags["AccessionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0050)).c_str()).trimmed(); /* AccessionNumber */
	tags["Modality"] =							QString(sf.ToString(gdcm::Tag(0x0008,0x0060)).c_str()).trimmed(); /* Modality */
	tags["Manufacturer"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x0070)).c_str()).trimmed(); /* Manufacturer */
	tags["InstitutionName"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x0080)).c_str()).trimmed(); /* InstitutionName */
	tags["InstitutionAddress"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x0081)).c_str()).trimmed(); /* InstitutionAddress */
	tags["ReferringPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x0090)).c_str()).trimmed(); /* ReferringPhysicianName */
	tags["StationName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1010)).c_str()).trimmed(); /* StationName */
	tags["StudyDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x1030)).c_str()).trimmed(); /* StudyDescription */
	tags["SeriesDescription"] =					QString(sf.ToString(gdcm::Tag(0x0008,0x103E)).c_str()).trimmed(); /* SeriesDescription */
	tags["InstitutionalDepartmentName"] =		QString(sf.ToString(gdcm::Tag(0x0008,0x1040)).c_str()).trimmed(); /* InstitutionalDepartmentName */
	tags["PerformingPhysicianName"] =			QString(sf.ToString(gdcm::Tag(0x0008,0x1050)).c_str()).trimmed(); /* PerformingPhysicianName */
	tags["OperatorsName"] =						QString(sf.ToString(gdcm::Tag(0x0008,0x1070)).c_str()).trimmed(); /* OperatorsName */
	tags["ManufacturerModelName"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x1090)).c_str()).trimmed(); /* ManufacturerModelName */
	tags["SourceImageSequence"] =				QString(sf.ToString(gdcm::Tag(0x0008,0x2112)).c_str()).trimmed(); /* SourceImageSequence */

	tags["PatientName"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0010)).c_str()).trimmed(); /* PatientName */
	tags["PatientID"] =							QString(sf.ToString(gdcm::Tag(0x0010,0x0020)).c_str()).trimmed(); /* PatientID */
	tags["PatientBirthDate"] =					QString(sf.ToString(gdcm::Tag(0x0010,0x0030)).c_str()).trimmed(); /* PatientBirthDate */
	tags["PatientSex"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0040)).c_str()).trimmed().left(1); /* PatientSex */
	tags["PatientAge"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1010)).c_str()).trimmed(); /* PatientAge */
	tags["PatientSize"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1020)).c_str()).trimmed(); /* PatientSize */
	tags["PatientWeight"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x1030)).c_str()).trimmed(); /* PatientWeight */

	tags["ContrastBolusAgent"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0010)).c_str()).trimmed(); /* ContrastBolusAgent */
	tags["KVP"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x0060)).c_str()).trimmed(); /* KVP */
	tags["DataCollectionDiameter"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0090)).c_str()).trimmed(); /* DataCollectionDiameter */
	tags["ContrastBolusRoute"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1040)).c_str()).trimmed(); /* ContrastBolusRoute */
	tags["RotationDirection"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1140)).c_str()).trimmed(); /* RotationDirection */
	tags["ExposureTime"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1150)).c_str()).trimmed(); /* ExposureTime */
	tags["XRayTubeCurrent"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1151)).c_str()).trimmed(); /* XRayTubeCurrent */
	tags["FilterType"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1160)).c_str()).trimmed(); /* FilterType */
	tags["GeneratorPower"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1170)).c_str()).trimmed(); /* GeneratorPower */
	tags["ConvolutionKernel"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1210)).c_str()).trimmed(); /* ConvolutionKernel */

	tags["BodyPartExamined"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0015)).c_str()).trimmed(); /* BodyPartExamined */
	tags["ScanningSequence"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0020)).c_str()).trimmed(); /* ScanningSequence */
	tags["SequenceVariant"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0021)).c_str()).trimmed(); /* SequenceVariant */
	tags["ScanOptions"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0022)).c_str()).trimmed(); /* ScanOptions */
	tags["MRAcquisitionType"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0023)).c_str()).trimmed(); /* MRAcquisitionType */
	tags["SequenceName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0024)).c_str()).trimmed(); /* SequenceName */
	tags["AngioFlag"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0025)).c_str()).trimmed(); /* AngioFlag */
	tags["SliceThickness"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0050)).c_str()).trimmed(); /* SliceThickness */
	tags["RepetitionTime"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0080)).c_str()).trimmed(); /* RepetitionTime */
	tags["EchoTime"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x0081)).c_str()).trimmed(); /* EchoTime */
	tags["NumberOfAverages"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0083)).c_str()).trimmed(); /* NumberOfAverages */
	tags["ImagingFrequency"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0084)).c_str()).trimmed(); /* ImagingFrequency */
	tags["ImagedNucleus"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0085)).c_str()).trimmed(); /* ImagedNucleus */
	tags["EchoNumbers"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x0086)).c_str()).trimmed(); /* EchoNumbers */
	tags["MagneticFieldStrength"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0087)).c_str()).trimmed(); /* MagneticFieldStrength */
	tags["SpacingBetweenSlices"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x0088)).c_str()).trimmed(); /* SpacingBetweenSlices */
	tags["NumberOfPhaseEncodingSteps"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x0089)).c_str()).trimmed(); /* NumberOfPhaseEncodingSteps */
	tags["EchoTrainLength"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0091)).c_str()).trimmed(); /* EchoTrainLength */
	tags["PercentSampling"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0093)).c_str()).trimmed(); /* PercentSampling */
	tags["PercentPhaseFieldOfView"] =			QString(sf.ToString(gdcm::Tag(0x0018,0x0094)).c_str()).trimmed(); /* PercentPhaseFieldOfView */
	tags["PixelBandwidth"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x0095)).c_str()).trimmed(); /* PixelBandwidth */
	tags["DeviceSerialNumber"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1000)).c_str()).trimmed(); /* DeviceSerialNumber */
	tags["SoftwareVersions"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1020)).c_str()).trimmed(); /* SoftwareVersions */
	tags["ProtocolName"] =						QString(sf.ToString(gdcm::Tag(0x0018,0x1030)).c_str()).trimmed(); /* ProtocolName */
	tags["TransmitCoilName"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1251)).c_str()).trimmed(); /* TransmitCoilName */
	tags["AcquisitionMatrix"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1310)).c_str()).trimmed().left(20); /* AcquisitionMatrix */
	tags["InPlanePhaseEncodingDirection"] =		QString(sf.ToString(gdcm::Tag(0x0018,0x1312)).c_str()).trimmed(); /* InPlanePhaseEncodingDirection */
	tags["FlipAngle"] =							QString(sf.ToString(gdcm::Tag(0x0018,0x1314)).c_str()).trimmed(); /* FlipAngle */
	tags["VariableFlipAngleFlag"] =				QString(sf.ToString(gdcm::Tag(0x0018,0x1315)).c_str()).trimmed(); /* VariableFlipAngleFlag */
	tags["SAR"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1316)).c_str()).trimmed(); /* SAR */
	tags["dBdt"] =								QString(sf.ToString(gdcm::Tag(0x0018,0x1318)).c_str()).trimmed(); /* dBdt */
	tags["PatientPosition"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x5100)).c_str()).trimmed(); /* PatientPosition */

	tags["Unknown Tag & Data"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1009)).c_str()).trimmed(); /* Unknown Tag & Data */
	tags["NumberOfImagesInMosaic"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100A)).c_str()).trimmed(); /* NumberOfImagesInMosaic*/
	tags["SliceMeasurementDuration"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100B)).c_str()).trimmed(); /* SliceMeasurementDuration*/
	tags["B_value"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x100C)).c_str()).trimmed(); /* B_value*/
	tags["DiffusionDirectionality"] =			QString(sf.ToString(gdcm::Tag(0x0019,0x100D)).c_str()).trimmed(); /* DiffusionDirectionality*/
	tags["DiffusionGradientDirection"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x100E)).c_str()).trimmed(); /* DiffusionGradientDirection*/
	tags["GradientMode"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x100F)).c_str()).trimmed(); /* GradientMode*/
	tags["FlowCompensation"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1011)).c_str()).trimmed(); /* FlowCompensation*/
	tags["TablePositionOrigin"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1012)).c_str()).trimmed(); /* TablePositionOrigin*/
	tags["ImaAbsTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1013)).c_str()).trimmed(); /* ImaAbsTablePosition*/
	tags["ImaRelTablePosition"] =				QString(sf.ToString(gdcm::Tag(0x0019,0x1014)).c_str()).trimmed(); /* ImaRelTablePosition*/
	tags["SlicePosition_PCS"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1015)).c_str()).trimmed(); /* SlicePosition_PCS*/
	tags["TimeAfterStart"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1016)).c_str()).trimmed(); /* TimeAfterStart*/
	tags["SliceResolution"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1017)).c_str()).trimmed(); /* SliceResolution*/
	tags["RealDwellTime"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1018)).c_str()).trimmed(); /* RealDwellTime*/
	tags["RBMoCoTrans"] =						QString(sf.ToString(gdcm::Tag(0x0019,0x1025)).c_str()).trimmed(); /* RBMoCoTrans*/
	tags["RBMoCoRot"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1026)).c_str()).trimmed(); /* RBMoCoRot*/
	tags["B_matrix"] =							QString(sf.ToString(gdcm::Tag(0x0019,0x1027)).c_str()).trimmed(); /* B_matrix*/
	tags["BandwidthPerPixelPhaseEncode"] =		QString(sf.ToString(gdcm::Tag(0x0019,0x1028)).c_str()).trimmed(); /* BandwidthPerPixelPhaseEncode*/
	tags["MosaicRefAcqTimes"] =					QString(sf.ToString(gdcm::Tag(0x0019,0x1029)).c_str()).trimmed(); /* MosaicRefAcqTimes*/

	tags["StudyInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000D)).c_str()).trimmed(); /* StudyInstanceUID */
	tags["SeriesInstanceUID"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x000E)).c_str()).trimmed(); /* SeriesInstanceUID */
	tags["StudyID"] =							QString(sf.ToString(gdcm::Tag(0x0020,0x0010)).c_str()).trimmed(); /* StudyID */
	tags["SeriesNumber"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x0011)).c_str()).trimmed(); /* SeriesNumber */
	tags["AcquisitionNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0012)).c_str()).trimmed(); /* AcquisitionNumber */
	tags["InstanceNumber"] =					QString(sf.ToString(gdcm::Tag(0x0020,0x0013)).c_str()).trimmed(); /* InstanceNumber */
	tags["ImagePositionPatient"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0032)).c_str()).trimmed(); /* ImagePositionPatient */
	tags["ImageOrientationPatient"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0037)).c_str()).trimmed(); /* ImageOrientationPatient */
	tags["FrameOfReferenceUID"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0052)).c_str()).trimmed(); /* FrameOfReferenceUID */
	tags["NumberOfTemporalPositions"] =			QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* NumberOfTemporalPositions */
	tags["ImagesInAcquisition"] =				QString(sf.ToString(gdcm::Tag(0x0020,0x0105)).c_str()).trimmed(); /* ImagesInAcquisition */
	tags["PositionReferenceIndicator"] =		QString(sf.ToString(gdcm::Tag(0x0020,0x1040)).c_str()).trimmed(); /* PositionReferenceIndicator */
	tags["SliceLocation"] =						QString(sf.ToString(gdcm::Tag(0x0020,0x1041)).c_str()).trimmed(); /* SliceLocation */

	tags["SamplesPerPixel"] =					QString(sf.ToString(gdcm::Tag(0x0028,0x0002)).c_str()).trimmed(); /* SamplesPerPixel */
	tags["PhotometricInterpretation"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0004)).c_str()).trimmed(); /* PhotometricInterpretation */
	tags["Rows"] =								QString(sf.ToString(gdcm::Tag(0x0028,0x0010)).c_str()).trimmed(); /* Rows */
	tags["Columns"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0011)).c_str()).trimmed(); /* Columns */
	tags["PixelSpacing"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0030)).c_str()).trimmed(); /* PixelSpacing */
	tags["BitsAllocated"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0100)).c_str()).trimmed(); /* BitsAllocated */
	tags["BitsStored"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x0101)).c_str()).trimmed(); /* BitsStored */
	tags["HighBit"] =							QString(sf.ToString(gdcm::Tag(0x0028,0x0102)).c_str()).trimmed(); /* HighBit */
	tags["PixelRepresentation"] =				QString(sf.ToString(gdcm::Tag(0x0028,0x0103)).c_str()).trimmed(); /* PixelRepresentation */
	tags["SmallestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0106)).c_str()).trimmed(); /* SmallestImagePixelValue */
	tags["LargestImagePixelValue"] =			QString(sf.ToString(gdcm::Tag(0x0028,0x0107)).c_str()).trimmed(); /* LargestImagePixelValue */
	tags["WindowCenter"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1050)).c_str()).trimmed(); /* WindowCenter */
	tags["WindowWidth"] =						QString(sf.ToString(gdcm::Tag(0x0028,0x1051)).c_str()).trimmed(); /* WindowWidth */
	tags["WindowCenterWidthExplanation"] =		QString(sf.ToString(gdcm::Tag(0x0028,0x1055)).c_str()).trimmed(); /* WindowCenterWidthExplanation */

	tags["RequestingPhysician"] =				QString(sf.ToString(gdcm::Tag(0x0032,0x1032)).c_str()).trimmed(); /* RequestingPhysician */
	tags["RequestedProcedureDescription"] =		QString(sf.ToString(gdcm::Tag(0x0032,0x1060)).c_str()).trimmed(); /* RequestedProcedureDescription */

	tags["PerformedProcedureStepStartDate"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0244)).c_str()).trimmed(); /* PerformedProcedureStepStartDate */
	tags["PerformedProcedureStepStartTime"] =	QString(sf.ToString(gdcm::Tag(0x0040,0x0245)).c_str()).trimmed(); /* PerformedProcedureStepStartTime */
	tags["PerformedProcedureStepID"] =			QString(sf.ToString(gdcm::Tag(0x0040,0x0253)).c_str()).trimmed(); /* PerformedProcedureStepID */
	tags["PerformedProcedureStepDescription"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0254)).c_str()).trimmed(); /* PerformedProcedureStepDescription */
	tags["CommentsOnThePerformedProcedureSte"] = QString(sf.ToString(gdcm::Tag(0x0040,0x0280)).c_str()).trimmed(); /* CommentsOnThePerformedProcedureSte */

	tags["TimeOfAcquisition"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100A)).c_str()).trimmed(); /* TimeOfAcquisition*/
	tags["AcquisitionMatrixText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x100B)).c_str()).trimmed(); /* AcquisitionMatrixText*/
	tags["FieldOfView"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100C)).c_str()).trimmed(); /* FieldOfView*/
	tags["SlicePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100D)).c_str()).trimmed(); /* SlicePositionText*/
	tags["ImageOrientation"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x100E)).c_str()).trimmed(); /* ImageOrientation*/
	tags["CoilString"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x100F)).c_str()).trimmed(); /* CoilString*/
	tags["ImaPATModeText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1011)).c_str()).trimmed(); /* ImaPATModeText*/
	tags["TablePositionText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1012)).c_str()).trimmed(); /* TablePositionText*/
	tags["PositivePCSDirections"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1013)).c_str()).trimmed(); /* PositivePCSDirections*/
	tags["ImageTypeText"] =						QString(sf.ToString(gdcm::Tag(0x0051,0x1016)).c_str()).trimmed(); /* ImageTypeText*/
	tags["SliceThicknessText"] =				QString(sf.ToString(gdcm::Tag(0x0051,0x1017)).c_str()).trimmed(); /* SliceThicknessText*/
	tags["ScanOptionsText"] =					QString(sf.ToString(gdcm::Tag(0x0051,0x1019)).c_str()).trimmed(); /* ScanOptionsText*/

	/* fix the study date */
	if (tags["StudyDate"] == "")
		tags["StudyDate"] = n->CreateCurrentDateTime(2);
	else {
		tags["StudyDate"].replace("/","-");
		if (tags["StudyDate"].size() == 8) {
			tags["StudyDate"].insert(6,'-');
			tags["StudyDate"].insert(4,'-');
		}
	}

	/* fix the series date */
	if (tags["SeriesDate"] == "")
		tags["SeriesDate"] = tags["StudyDate"];
	else {
		tags["SeriesDate"].replace("/","-");
		if (tags["SeriesDate"].size() == 8) {
			tags["SeriesDate"].insert(6,'-');
			tags["SeriesDate"].insert(4,'-');
		}
	}

	/* fix the study time */
	if (tags["StudyTime"].size() == 13)
		tags["StudyTime"] = tags["StudyTime"].left(6);

	if (tags["StudyTime"].size() == 6) {
		tags["StudyTime"].insert(4,':');
		tags["StudyTime"].insert(2,':');
	}

	/* some images may not have a series date/time, so substitute the studyDateTime for seriesDateTime */
	if (tags["SeriesTime"] == "")
		tags["SeriesTime"] = tags["StudyTime"];
	else {
		if (tags["SeriesTime"].size() == 13)
			tags["SeriesTime"] = tags["SeriesTime"].left(6);

		if (tags["SeriesTime"].size() == 6) {
			tags["SeriesTime"].insert(4,':');
			tags["SeriesTime"].insert(2,':');
		}
	}

	tags["StudyDateTime"] = tags["StudyDate"] + " " + tags["StudyTime"];
	tags["SeriesDateTime"] = tags["SeriesDate"] + " " + tags["SeriesTime"];

	/* fix the birthdate */
	if (tags["PatientBirthDate"] == "") tags["PatientBirthDate"] = "0001-01-01";
	tags["PatientBirthDate"].replace("/","-");
	if (tags["PatientBirthDate"].size() == 8) {
		tags["PatientBirthDate"].insert(6,'-');
		tags["PatientBirthDate"].insert(4,'-');
	}

	/* check for other undefined or blank fields */
	if (tags["PatientSex"] == "") tags["PatientSex"] = 'U';
	if (tags["StationName"] == "") tags["StationName"] = "Unknown";
	if (tags["InstitutionName"] == "") tags["InstitutionName"] = "Unknown";
	if (tags["SeriesNumber"] == "") {
		QString timestamp = tags["SeriesTime"];
		timestamp.remove(':').remove('-').remove(' ');
		tags["SeriesNumber"] = timestamp;
	}

	QString uniqueseries = tags["InstitutionName"] + tags["StationName"] + tags["Modality"] + tags["PatientName"] + tags["PatientBirthDate"] + tags["PatientSex"] + tags["StudyDateTime"] + tags["SeriesNumber"];
	tags["UniqueSeriesString"] = uniqueseries;
	//n->WriteLog("File ["+file+"]  SeriesInstanceUID ["+tags["SeriesInstanceUID"]+"]");
	//n->WriteLog("File ["+file+"]  UniqueSeriesString ["+tags["UniqueSeriesString"]+"]");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- GetCostCenter ---------------------------------- */
/* ---------------------------------------------------------- */
QString moduleImport::GetCostCenter(QString studydesc) {
	QString cc;

	/* extract the costcenter */
	if (studydesc.contains("clinical",Qt::CaseInsensitive))
		cc = "888888";
	else if ( (studydesc.contains("(")) && (studydesc.contains(")")) ) /* if it contains an opening and closing parentheses */
	{
		int idx1 = studydesc.indexOf("(");
		int idx2 = studydesc.lastIndexOf(")");
		cc = studydesc.mid(idx1+1, idx2-idx1-1);
	}
	else {
		cc = studydesc;
	}

	return cc;
}


/* ---------------------------------------------------------- */
/* --------- CreateIDSearchList ----------------------------- */
/* ---------------------------------------------------------- */
QString moduleImport::CreateIDSearchList(QString PatientID, QString altuids) {
	/* create the possible ID search lists and arrays */
	QStringList altuidlist;
	QStringList idsearchlist;
	if (altuids != "")
		altuidlist = altuids.split(",");

	idsearchlist.append(PatientID);
	idsearchlist.append(altuidlist);
	idsearchlist.removeDuplicates();

	QString SQLIDs = "'" + idsearchlist.first() + "'";
	idsearchlist.removeFirst();
	foreach (QString tmpID, idsearchlist) {
		if ((tmpID != "") && (tmpID != "none") && (tmpID.toLower() != "na") && (tmpID != "0") && (tmpID.toLower() != "null"))
			SQLIDs += ",'" + tmpID + "'";
	}

	return SQLIDs;
}


/* ---------------------------------------------------------- */
/* --------- CreateSubject ---------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, QString importUUID, QStringList &msgs, int &subjectRowID, QString &subjectRealUID) {
	int count(0);

	msgs << n->WriteLog("Searching for an unused UID");
	/* create a new subjectRealUID */
	do {
		subjectRealUID = n->CreateUID("S",3);
		//msgs << n->WriteLog("Checking [" + subjectRealUID + "]");
		QSqlQuery q2;
		q2.prepare("select uid from subjects where uid = :uid");
		q2.bindValue(":uid", subjectRealUID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__, true);
		count = q2.size();
		//msgs << n->WriteLog(QString("UID [" + subjectRealUID + "] found [%1] rows").arg(count));
	} while (count > 0);

	msgs << n->WriteLog("This subject did not exist. Created a new UID [" + subjectRealUID + "]");

	QString sqlstring = "insert into subjects (name, birthdate, gender, weight, height, uid, uuid, uuid2) values (:patientname, :patientdob, :patientsex, :weight, :size, :uid, ucase(md5(concat('" + n->RemoveNonAlphaNumericChars(PatientName) + "', '" + n->RemoveNonAlphaNumericChars(PatientBirthDate) + "','" + n->RemoveNonAlphaNumericChars(PatientSex) + "'))), ucase(:uuid) )";
	QSqlQuery q2;
	q2.prepare(sqlstring);
	q2.bindValue(":patientname",PatientName);
	q2.bindValue(":patientdob",PatientBirthDate);
	q2.bindValue(":patientsex",PatientSex);
	q2.bindValue(":weight",PatientWeight);
	q2.bindValue(":size",PatientSize);
	q2.bindValue(":uid",subjectRealUID);
	q2.bindValue(":uuid",importUUID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	subjectRowID = q2.lastInsertId().toInt();

	msgs << n->WriteLog("Added new subject [" + subjectRealUID + "]");

	/* insert the PatientID as an alternate UID */
	if (PatientID != "") {
		QSqlQuery q2;
		q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectrowid, :patientid)");
		q2.bindValue(":subjectid",subjectRowID);
		q2.bindValue(":patientid",PatientID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		msgs << n->WriteLog("Added alternate UID [" + PatientID + "]");
	}
	return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertDICOMSeries ------------------------------ */
/* ---------------------------------------------------------- */
bool moduleImport::InsertDICOMSeries(int importid, QStringList files, QString &msg) {

	QStringList msgs;
	msgs << n->WriteLog(QString("----- Inside InsertDICOMSeries(%1, <array of size[%2]>) with [%2] files -----").arg(importid).arg(files.size()));

	if (files.size() < 1) {
		msgs << n->WriteLog("This DICOM series has no files");
		msg += msgs.join("\n");
		return false;
	}

	if (!QFile::exists(files[0])) {
		msgs << n->WriteLog(QString("File [%1] does not exist - check 0!").arg(files[0]));
		msg += msgs.join("\n");
		return 0;
	}

	//msgs << n->WriteLog(QString("First file (before sorting) is [" + files[0] + "]  array size [%1]").arg(files.size()));
	n->SortQStringListNaturally(files);
	//msgs << n->WriteLog(QString("First file (after sorting) is [" + files[0] + "] array size [%1]").arg(files.size()));

	if (!QFile::exists(files[0])) {
		msgs << n->WriteLog(QString("File [%1] does not exist - check 1!").arg(files[0]));
		msg += msgs.join("\n");
		return 0;
	}

	/* import log variables */
	QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
	double IL_patientage_orig(0.0);
	int IL_seriesnumber_orig(0);
	QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
	QString IL_subject_uid;
	QString IL_project_number;
	int IL_seriescreated(0), IL_studycreated(0), IL_subjectcreated(0), IL_familycreated(0), IL_enrollmentcreated(0), IL_overwrote_existing(0);

	int subjectRowID(0);
	QString subjectRealUID;
	QString familyRealUID;
	int familyRowID(0);
	int projectRowID(0);
	int enrollmentRowID(0);
	int studyRowID(0);
	int seriesRowID(0);
	QString costcenter;
	int studynum(0);

	int importInstanceID(0);
	int importSiteID(0);
	int importProjectID(0);
	int importPermanent(0);
	int importAnonymize(0);
	int importMatchIDOnly(0);
	QString importUUID;
	QString importSeriesNotes;
	QString importAltUIDs;

	/* if there is an importid, check to see how that thing is doing */
	if (importid > 0) {
		QSqlQuery q;
		q.prepare("select * from import_requests where importrequest_id = :importid");
		q.bindValue(":importid", importid);
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() > 0) {
			q.first();
			QString status = q.value("import_status").toString();
			importInstanceID = q.value("import_instanceid").toInt();
			importSiteID = q.value("import_siteid").toInt();
			importProjectID = q.value("import_projectid").toInt();
			importPermanent = q.value("import_permanent").toInt();
			importAnonymize = q.value("import_anonymize").toInt();
			importMatchIDOnly = q.value("import_matchidonly").toInt();
			importUUID = q.value("import_uuid").toString();
			importSeriesNotes = q.value("import_seriesnotes").toString();
			importAltUIDs = q.value("import_altuids").toString();
		}
	}

	/* get all the DICOM tags */
	QHash<QString, QString> tags;
	QString filetype;
	QString f = files[0];

	if (!QFile::exists(f)) {
		msgs << n->WriteLog(QString("File [%1] does not exist - check A!").arg(f));
		msg += msgs.join("\n");
		return 0;
	}

	if (ParseDICOMFile(f, tags)) {
		if (!QFile::exists(f)) {
			msgs << n->WriteLog(QString("File [%1] does not exist - check B!").arg(f));
			msg += msgs.join("\n");
			return 0;
		}
	}

	QString InstitutionName = tags["InstitutionName"];
	QString InstitutionAddress = tags["InstitutionAddress"];
	QString Modality = tags["Modality"];
	QString StationName = tags["StationName"];
	QString Manufacturer = tags["Manufacturer"];
	QString ManufacturersModelName = tags["ManufacturersModelName"];
	QString OperatorsName = tags["OperatorsName"];
	QString PatientID = tags["PatientID"].toUpper();
	QString PatientBirthDate = tags["PatientBirthDate"];
	QString PatientName = tags["PatientName"];
	QString PatientSex = tags["PatientSex"];
	double PatientWeight = tags["PatientWeight"].toDouble();
	double PatientSize = tags["PatientSize"].toDouble();
	QString PatientAgeStr = tags["PatientAge"];
	double PatientAge(0.0);
	QString PerformingPhysiciansName = tags["PerformingPhysicianName"];
	QString ProtocolName = tags["ProtocolName"];
	QString SeriesDate = tags["SeriesDate"];
	int SeriesNumber = tags["SeriesNumber"].toInt();
	QString SeriesTime = tags["SeriesTime"];
	QString StudyDate = tags["StudyDate"];
	QString StudyDescription = tags["StudyDescription"];
	QString SeriesDescription = tags["SeriesDescription"];
	QString StudyTime = tags["StudyTime"];
	int Rows = tags["Rows"].toInt();
	int Columns = tags["Columns"].toInt();
	int AccessionNumber = tags["AccessionNumber"].toInt();
	double SliceThickness = tags["SliceThickness"].toDouble();
	QString PixelSpacing = tags["PixelSpacing"];
	int NumberOfTemporalPositions = tags["NumberOfTemporalPositions"].toInt();
	int ImagesInAcquisition = tags["ImagesInAcquisition"].toInt();
	QString SequenceName = tags["SequenceName"];
	QString ImageType = tags["ImageType"];
	QString ImageComments = tags["ImageComments"];

	/* MR specific tags */
	double MagneticFieldStrength = tags["MagneticFieldStrength"].toDouble();
	int RepetitionTime = tags["RepetitionTime"].toInt();
	double FlipAngle = tags["FlipAngle"].toDouble();
	double EchoTime = tags["EchoTime"].toDouble();
	QString AcquisitionMatrix = tags["AcquisitionMatrix"];
	QString InPlanePhaseEncodingDirection = tags["InPlanePhaseEncodingDirection"];
	double InversionTime = tags["InversionTime"].toDouble();
	double PercentSampling = tags["PercentSampling"].toDouble();
	double PercentPhaseFieldOfView = tags["PercentPhaseFieldOfView"].toDouble();
	double PixelBandwidth = tags["PixelBandwidth"].toDouble();
	double SpacingBetweenSlices = tags["SpacingBetweenSlices"].toDouble();
	QString PhaseEncodeAngle;
	QString PhaseEncodingDirectionPositive;

	/* attempt to get the phase encode angle (In Plane Rotation) from the siemens CSA header */
	QFile df(files[0]);

	/* open the dicom file as a text file, since part of the CSA header is stored as text, not binary */
	if (df.open(QIODevice::ReadOnly | QIODevice::Text)) {

		QTextStream in(&df);
		while (!in.atEnd()) {
			QString line = in.readLine();
			if (line.startsWith("sSliceArray.asSlice[0].dInPlaneRot") && (line.size() < 70)) {
				/* make sure the line does not contain any non-printable ASCII control characters */
				if (!line.contains(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")))) {
					int idx = line.indexOf(".dInPlaneRot");
					line = line.mid(idx,23);
					QStringList vals = line.split(QRegExp("\\s+"));
					PhaseEncodeAngle = vals.last().trimmed();
					break;
				}
			}
		}
		msgs << n->WriteLog(QString("Found PhaseEncodeAngle of [%1]").arg(PhaseEncodeAngle));
		df.close();
	}

	/* get the other part of the CSA header, the PhaseEncodingDirectionPositive value */
	QString systemstring = QString("%1/./gdcmdump -C %2 | grep PhaseEncodingDirectionPositive").arg(n->cfg["scriptdir"]).arg(f);
	QString csaheader = n->SystemCommand(systemstring, false);
	QStringList parts = csaheader.split(",");
	QString val;
	if (parts.size() == 5) {
		val = parts[4];
		val.replace("Data '","",Qt::CaseInsensitive);
		val.replace("'","").trimmed();
		if (val.trimmed() == "Data")
			val = "";
		PhaseEncodingDirectionPositive = val.trimmed();
	}
	n->WriteLog(QString("Found PhaseEncodingDirectionPositive of [%1]").arg(PhaseEncodingDirectionPositive));

	/* CT specific tags */
	QString ContrastBolusAgent = tags["ContrastBolusAgent"];
	QString BodyPartExamined = tags["BodyPartExamined"];
	QString ScanOptions = tags["ScanOptions"];
	double KVP = tags["KVP"].toDouble();
	double DataCollectionDiameter = tags["DataCollectionDiameter"].toDouble();
	QString ContrastBolusRoute = tags["ContrastBolusRoute"];
	QString RotationDirection = tags["RotationDirection"];
	double ExposureTime = tags["ExposureTime"].toDouble();
	double XRayTubeCurrent = tags["XRayTubeCurrent"].toDouble();
	QString FilterType = tags["FilterType"];
	double GeneratorPower = tags["GeneratorPower"].toDouble();
	QString ConvolutionKernel = tags["ConvolutionKernel"];

	/* fix some of the fields to be amenable to the DB */
	if (Modality == "")
		Modality = "OT";
	StudyDate = n->ParseDate(StudyDate);
	StudyTime = n->ParseTime(StudyTime);
	SeriesDate = n->ParseDate(SeriesDate);
	SeriesTime = n->ParseTime(SeriesTime);

	QString StudyDateTime = tags["StudyDateTime"] = StudyDate + " " + StudyTime;
	QString SeriesDateTime = tags["SeriesDateTime"] = SeriesDate + " " + SeriesTime;
	QStringList pix = PixelSpacing.split("\\");
	int pixelX(0);
	int pixelY(0);
	if (pix.size() == 2) {
		pixelX = pix[0].toInt();
		pixelY = pix[1].toInt();
	}
	QStringList amat = AcquisitionMatrix.split(" ");
	int mat1(0), mat2(0), mat3(0), mat4(0);
	if (amat.size() == 4) {
		mat1 = amat[0].toInt();
		mat2 = amat[1].toInt();
		mat3 = amat[2].toInt();
		mat4 = amat[3].toInt();
	}
	if (SeriesNumber == 0) {
		QString timestamp = SeriesTime;
		timestamp.replace(":","").replace("-","").replace(" ","");
		SeriesNumber = timestamp.toInt();
	}

	/* fix patient birthdate */
	PatientBirthDate = n->ParseDate(PatientBirthDate);

	/* check if the patient age contains any characters */
	if (PatientAgeStr.contains('Y')) PatientAge = PatientAgeStr.replace("Y","").toDouble();
	if (PatientAgeStr.contains('M')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/12.0;
	if (PatientAgeStr.contains('W')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/52.0;
	if (PatientAgeStr.contains('D')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/365.25;

	/* fix patient age */
	if (PatientAge < 0.001) {
		QDate studydate;
		QDate dob;
		studydate.fromString(StudyDate);
		dob.fromString(PatientBirthDate);

		PatientAge = dob.daysTo(studydate)/365.25;
	}

	/* remove any non-printable ASCII control characters */
	PatientName.replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"");
	PatientSex.replace(QRegularExpression(QStringLiteral("[\\x00-\\x1F]")),"");

	if (PatientID == "") {
		PatientID = "(empty)";
		n->WriteLog(n->SystemCommand("exiftool " + f));
	}

	if (PatientName == "")
		PatientName = "(empty)";

	if (StudyDescription == "")
		StudyDescription = "(empty)";

	if (PatientSex == "")
		PatientName = "U";

	/* get the costcenter */
	costcenter = GetCostCenter(StudyDescription);

	msgs << n->WriteLog(PatientID + " - " + StudyDescription);

	/* set the import log variables */
	IL_modality_orig = Modality;
	IL_patientname_orig = PatientName;
	IL_patientdob_orig = PatientBirthDate;
	IL_patientsex_orig = PatientSex;
	IL_stationname_orig = StationName;
	IL_institution_orig = InstitutionName + "-" + InstitutionAddress;
	IL_studydatetime_orig = StudyDate + " " + StudyTime;
	IL_seriesdatetime_orig = SeriesDate + " " + SeriesTime;
	IL_seriesnumber_orig = SeriesNumber;
	IL_studydesc_orig = StudyDescription;
	IL_seriesdesc_orig = SeriesDescription;
	IL_protocolname_orig = ProtocolName;
	IL_patientage_orig = PatientAge;

	/* get the ID search string */
	QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
	QStringList altuidlist;
	if (importAltUIDs != "")
		altuidlist = importAltUIDs.split(",");

	/* check if the project and subject exist */
	msgs << n->WriteLog("Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]");
	int projectcount(0);
	int subjectcount(0);
	QString sqlstring = QString("select (SELECT count(*) FROM `projects` WHERE project_costcenter = :costcenter) 'projectcount', (SELECT count(*) FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (%1) or a.uid = SHA1(:patientid) or b.altuid in (%1) or b.altuid = SHA1(:patientid)) 'subjectcount'").arg(SQLIDs);
	QSqlQuery q;
	q.prepare(sqlstring);
	q.bindValue(":patientid", PatientID);
	q.bindValue(":costcenter", costcenter);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		projectcount = q.value("projectcount").toInt();
		subjectcount = q.value("subjectcount").toInt();
	}

	/* if subject can't be found by UID, check by name/dob/sex (except if importMatchIDOnly is set), or create the subject */
	if (subjectcount < 1) {

		bool subjectFoundByName = false;
		/* search for an existing subject by name, dob, gender */
		if (!importMatchIDOnly) {
			QString sqlstring2 = "select subject_id, uid from subjects where name like '%" + PatientName + "%' and gender = left('" + PatientSex + "',1) and birthdate = :dob and isactive = 1";
			msgs << n->WriteLog("Subject not found by UID. Checking if the subject exists using PatientName [" + PatientName + "] PatientSex [" + PatientSex + "] PatientBirthDate [" + PatientBirthDate + "]");
			QSqlQuery q2;
			q2.prepare(sqlstring2);
			q2.bindValue(":dob", PatientBirthDate);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			if (q2.size() > 0) {
				q2.first();
				subjectRealUID = q2.value("uid").toString();
				subjectRowID = q2.value("subject_id").toInt();
				msgs << n->WriteLog("This subject exists. UID [" + subjectRealUID + "]");
				IL_subjectcreated = 0;
				subjectFoundByName = 1;
			}
		}
		/* if it couldn't be found, create a new subject */
		if (!subjectFoundByName) {
			CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, importUUID, msgs, subjectRowID, subjectRealUID);
			IL_subjectcreated = 1;
		}
	}
	else {
		/* get the existing subject ID, and UID! (the PatientID may be an alternate UID) */
		QString sqlstring = "SELECT a.subject_id, a.uid FROM subjects a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (" + SQLIDs + ") or a.uid = SHA1(:patientid) or b.altuid in (" + SQLIDs + ") or b.altuid = SHA1(:patientid)";
		QSqlQuery q2;
		q2.prepare(sqlstring);
		q2.bindValue(":patientid", PatientID);
		n->SQLQuery(q2,__FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			subjectRowID = q2.value("subject_id").toInt();
			subjectRealUID = q2.value("uid").toString().toUpper().trimmed();

			msgs << n->WriteLog(QString("Found subject [%1, " + subjectRealUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID));
		}
		else {
			msgs << n->WriteLog("Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]");
			msg += msgs.join("\n");
			return 0;
		}
		/* insert the PatientID as an alternate UID */
		if (PatientID != "") {
			QSqlQuery q2;
			q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
			q2.bindValue(":subjectid", subjectRowID);
			q2.bindValue(":patientid", PatientID);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		}
		IL_subjectcreated = 0;
	}

	n->WriteLog("subjectRealUID ["+subjectRealUID+"]");
	if (subjectRealUID == "") {
		msgs << n->WriteLog("Error finding/creating subject. UID is blank");
		msg += msgs.join("\n");
		return 0;
	}

	/* check if the subject is part of a family, if not create a family for it */
	QSqlQuery q2;
	q2.prepare("select family_id from family_members where subject_id = :subjectid");
	q2.bindValue(":subjectid", subjectRowID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		familyRowID = q2.value("family_id").toInt();
		//msgs << n->WriteLog(QString("This subject is part of a family [%1]").arg(familyRowID));
		IL_familycreated = 0;
	}
	else {
		int count = 0;

		/* create family UID */
		msgs << n->WriteLog("Subject is not part of family, creating a unique family UID");
		do {
			familyRealUID = n->CreateUID("F");
			QSqlQuery q2;
			q2.prepare("SELECT * FROM `families` WHERE family_uid = :familyuid");
			q2.bindValue(":familyuid", familyRealUID);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			count = q2.size();
		} while (count > 0);

		/* create familyRowID if it doesn't exist */
		QSqlQuery q2;
		q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyRealUID, now(), :familyname)");
		q2.bindValue(":familyuid", familyRealUID);
		q2.bindValue(":familyname", "Proband-" + subjectRealUID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		familyRowID = q2.lastInsertId().toInt();

		q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
		q2.bindValue(":familyid", familyRowID);
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

		IL_familycreated = 1;
	}

	/* if project doesn't exist, use the generic project */
	if (projectcount < 1) {
		costcenter = "999999";
	}

	/* get the projectRowID */
	if (importProjectID == 0) {
		q2.prepare("select project_id from projects where project_costcenter = :costcenter");
		q2.bindValue(":costcenter", costcenter);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			projectRowID = q2.value("project_id").toInt();
		}
	}
	else {
		/* need to create the project if it doesn't exist */
		msgs << n->WriteLog(QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID));
		projectRowID = importProjectID;
	}

	/* check if the subject is enrolled in the project */
	q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
	q2.bindValue(":subjectid", subjectRowID);
	q2.bindValue(":projectid", projectRowID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		enrollmentRowID = q2.value("enrollment_id").toInt();
		msgs << n->WriteLog(QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID));
		IL_enrollmentcreated = 0;
	}
	else {
		/* create enrollmentRowID if it doesn't exist */
		q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
		q2.bindValue(":subjectid", subjectRowID);
		q2.bindValue(":projectid", projectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		enrollmentRowID = q2.lastInsertId().toInt();

		msgs << n->WriteLog(QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID));
		IL_enrollmentcreated = 1;
	}

	/* update alternate IDs, if there are any */
	if (altuidlist.size() > 0) {
		foreach (QString altuid, altuidlist) {
			if (altuid.trimmed() == "") {
				q2.prepare("insert ignore into subject_altuid (subject_id, altuid, enrollment_id) values (:subjectid, :altuid, :enrollmentid)");
				q2.bindValue(":subjectid", subjectRowID);
				q2.bindValue(":altuid", altuid);
				q2.bindValue(":enrollmentid", enrollmentRowID);
				n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			}
		}
	}

	/* now determine if this study exists or not...
	 * basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	 * also checks the accession number against the study_num to see if this study was pre-registered
	 * HOWEVER, if there is an instanceID specified, we should only match a study that's part of an enrollment in the same instance */
	bool studyFound = false;
	msgs << n->WriteLog(QString("Checking if this study exists: enrollmentID [%1] study(accession)Number [%2] StudyDateTime [%3] Modality [%4] StationName [%5]").arg(enrollmentRowID).arg(AccessionNumber).arg(StudyDateTime).arg(Modality).arg(StationName));

	q2.prepare("select study_id, study_num from studies where enrollment_id = :enrollmentid and (study_num = :accessionnum or ((study_datetime between date_sub('" + StudyDateTime + "', interval 30 second) and date_add('" + StudyDateTime + "', interval 30 second)) and study_modality = :modality and study_site = :stationname))");
	q2.bindValue(":enrollmentid", enrollmentRowID);
	q2.bindValue(":accessionnum", AccessionNumber);
	q2.bindValue(":modality", Modality);
	q2.bindValue(":stationname", StationName);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		while (q2.next()) {
			int study_id = q2.value("study_id").toInt();
			studynum = q2.value("study_num").toInt();
			//int foundInstanceRowID = -1;

			/* check which instance this study is enrolled in */
			//QSqlQuery q3;
			//q3.prepare("select instance_id from projects where project_id = (select project_id from enrollment where enrollment_id = (select enrollment_id from studies where study_id = :studyid))");
			//q3.bindValue(":studyid",study_id);
			//n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
			//if (q3.size() > 0) {
			//	q3.first();
			    //foundInstanceRowID = q3.value("instance_id").toInt();
			    //msgs << n->WriteLog(QString("Found instance ID [%1] comparing to import instance ID [%2]").arg(foundInstanceRowID).arg(importInstanceID));

				/* if the study already exists within the instance specified in the project, then update the existing study, otherwise create a new one */
			    //if (importInstanceID == 0) {
					studyFound = true;
					studyRowID = study_id;

					QSqlQuery q4;
					msgs << n->WriteLog(QString("StudyID [%1] exists, updating").arg(study_id));
					q4.prepare("update studies set study_modality = :modality, study_datetime = '" + StudyDateTime + "', study_ageatscan = :patientage, study_height = :height, study_weight = :weight, study_desc = :studydesc, study_operator = :operator, study_performingphysician = :physician, study_site = :stationname, study_nidbsite = :importsiteid, study_institution = :institution, study_status = 'complete' where study_id = :studyid");
					q4.bindValue(":modality", Modality);
					q4.bindValue(":patientage", PatientAge);
					q4.bindValue(":height", PatientSize);
					q4.bindValue(":weight", PatientWeight);
					q4.bindValue(":studydesc", StudyDescription);
					q4.bindValue(":operator", OperatorsName);
					q4.bindValue(":physician", PerformingPhysiciansName);
					q4.bindValue(":stationname", StationName);
					q4.bindValue(":importsiteid", importSiteID);
					q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
					q4.bindValue(":studyid", studyRowID);
					n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

					IL_studycreated = 0;
					break;
				//}
			//}
		}
	}
	if (!studyFound) {
		msgs << n->WriteLog("Study did not exist, creating new study");

		/* create studyRowID if it doesn't exist */
		q2.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			studynum = q2.value("study_num").toInt() + 1;
		}
		else
			studynum = 1;

		QSqlQuery q4;
		q4.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_desc, study_operator, study_performingphysician, study_site, study_nidbsite, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentid, :studynum, :patientid, :modality, :studydatetime, :patientage, :height, :weight, :studydesc, :operator, :physician, :stationname, :importsiteid, :institution, 'complete', 'import', now())");
		q4.bindValue(":enrollmentid", enrollmentRowID);
		q4.bindValue(":studynum", studynum);
		q4.bindValue(":patientid", PatientID);
		q4.bindValue(":modality", Modality);
		q4.bindValue(":studydatetime", StudyDateTime);
		q4.bindValue(":patientage", PatientAge);
		q4.bindValue(":height", PatientSize);
		q4.bindValue(":weight", PatientWeight);
		q4.bindValue(":studydesc", StudyDescription);
		q4.bindValue(":operator", OperatorsName);
		q4.bindValue(":physician", PerformingPhysiciansName);
		q4.bindValue(":stationname", StationName);
		q4.bindValue(":importsiteid", importSiteID);
		q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
		q4.bindValue(":studyid", studyRowID);
		n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
		studyRowID = q4.lastInsertId().toInt();

		IL_studycreated = 1;
	}

	/* gather series information */
	int boldreps(1);
	int numfiles(1);
	numfiles = files.size();
	int zsize(1);
	QString mrtype = "structural";

	/* check if its an EPI sequence, but not a perfusion sequence */
	if (SequenceName.contains("epfid2d1_")) {
		if (ProtocolName.contains("perfusion", Qt::CaseInsensitive) || SequenceName.contains("ep2d_perf_tra")) { }
		else {
			mrtype = "epi";
			/* get the bold reps and attempt to get the z size */
			boldreps = numfiles;

			/* this method works ... sometimes */
			if ((mat1 > 0) && (mat4 > 0))
				zsize = (Rows/mat1)*(Columns/mat4); /* example (384/64)*(384/64) = 6*6 = 36 possible slices in a mosaic */
			else
				zsize = numfiles;
		}
	}
	else {
		zsize = numfiles;
	}
	/* if any of the DICOM fields were populated, use those instead */
	if (ImagesInAcquisition > 0)
		zsize = ImagesInAcquisition;
	if (NumberOfTemporalPositions > 0)
		boldreps = NumberOfTemporalPositions;

	/* insert or update the series based on modality */
	QString dbModality;
	if (Modality.toUpper() == "MR") {
		dbModality = "mr";
		q2.prepare("select mrseries_id from mr_series where study_id = :studyid and series_num = :seriesnum");
		q2.bindValue(":studyid", studyRowID);
		q2.bindValue(":seriesnum", SeriesNumber);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			seriesRowID = q2.value("mrseries_id").toInt();

			msgs << n->WriteLog(QString("This MR series [%1] exists, updating").arg(SeriesNumber));

			int dimN(0), dimT(0), dimZ(0);
			if (NumberOfTemporalPositions > 0) {
				dimN = 4;
				dimT = NumberOfTemporalPositions;
			}
			if (ImagesInAcquisition > 0)
				dimZ = ImagesInAcquisition;

			QString sqlstring = "update mr_series set series_datetime = '" + SeriesDateTime + "', series_desc = :SeriesDescription, series_protocol = :ProtocolName, series_sequencename = :SequenceName, series_tr = :RepetitionTime, series_te = :EchoTime,series_flip = :FlipAngle, phaseencodedir = :InPlanePhaseEncodingDirection, phaseencodeangle = :PhaseEncodeAngle, PhaseEncodingDirectionPositive = :PhaseEncodingDirectionPositive, series_spacingx = :pixelX,series_spacingy = :pixelY, series_spacingz = :SliceThickness, series_fieldstrength = :MagneticFieldStrength, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, series_ti = :InversionTime, percent_sampling = :PercentSampling, percent_phasefov = :PercentPhaseFieldOfView, acq_matrix = :AcquisitionMatrix, slicethickness = :SliceThickness, slicespacing = :SpacingBetweenSlices, bandwidth = :PixelBandwidth, image_type = :ImageType, image_comments = :ImageComments, bold_reps = :boldreps, numfiles = :numfiles, series_notes = :importSeriesNotes, series_status = 'complete' where mrseries_id = :seriesRowID";
			QSqlQuery q3;
			q3.prepare(sqlstring);
			q3.bindValue(":SeriesDescription", SeriesDescription);
			q3.bindValue(":ProtocolName", ProtocolName);
			q3.bindValue(":SequenceName", SequenceName);
			q3.bindValue(":RepetitionTime", RepetitionTime);
			q3.bindValue(":EchoTime", EchoTime);
			q3.bindValue(":FlipAngle", FlipAngle);
			q3.bindValue(":InPlanePhaseEncodingDirection", InPlanePhaseEncodingDirection);

			if (PhaseEncodeAngle == "") q3.bindValue(":PhaseEncodeAngle", QVariant(QVariant::Double)); /* for null values */
			else q3.bindValue(":PhaseEncodeAngle", PhaseEncodeAngle);

			if (PhaseEncodingDirectionPositive == "") q3.bindValue(":PhaseEncodingDirectionPositive", QVariant(QVariant::Int)); /* for null values */
			else q3.bindValue(":PhaseEncodingDirectionPositive", PhaseEncodingDirectionPositive);

			q3.bindValue(":pixelX", pixelX);
			q3.bindValue(":pixelY", pixelY);
			q3.bindValue(":SliceThickness", SliceThickness);
			q3.bindValue(":MagneticFieldStrength", MagneticFieldStrength);
			q3.bindValue(":Rows", Rows);
			q3.bindValue(":Columns", Columns);
			q3.bindValue(":zsize", zsize);
			q3.bindValue(":InversionTime", InversionTime);
			q3.bindValue(":PercentSampling", PercentSampling);
			q3.bindValue(":PercentPhaseFieldOfView", PercentPhaseFieldOfView);
			q3.bindValue(":AcquisitionMatrix", AcquisitionMatrix);
			q3.bindValue(":SliceThickness", SliceThickness);
			q3.bindValue(":SpacingBetweenSlices", SpacingBetweenSlices);
			q3.bindValue(":PixelBandwidth", PixelBandwidth);
			q3.bindValue(":ImageType", ImageType);
			q3.bindValue(":ImageComments", ImageComments);
			q3.bindValue(":boldreps", boldreps);
			q3.bindValue(":numfiles", numfiles);
			q3.bindValue(":importSeriesNotes", importSeriesNotes);
			q3.bindValue(":seriesRowID", seriesRowID);
			n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

			IL_seriescreated = 0;

			/* if the series is being updated, the QA information might be incorrect or be based on the wrong number of files, so delete the mr_qa row */
			q3.prepare("delete from mr_qa where mrseries_id = :seriesid");
			q3.bindValue(0,seriesRowID);
			n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

			msgs << n->WriteLog("Deleted from mr_qa table, now deleting from qc_results");

			/* delete the qc module rows */
			q3.prepare("select qcmoduleseries_id from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
			q3.bindValue(0,seriesRowID);
			n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

			QStringList qcidlist;
			if (q3.size() > 0) {
				while (q3.next())
					qcidlist << q3.value("qcmoduleseries_id").toString();

				QString sqlstring = "delete from qc_results where qcmoduleseries_id in (" + qcidlist.join(",") + ")";
				q3.prepare(sqlstring);
				n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
			}

			msgs << n->WriteLog("Deleted from qc_results table, now deleting from qc_moduleseries");

			q3.prepare("delete from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
			q3.bindValue(0,seriesRowID);
			n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		}
		else {
			/* create seriesRowID if it doesn't exist */
			msgs << n->WriteLog(QString("MR series [%1] did not exist, creating").arg(SeriesNumber));
			QString sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_protocol, series_sequencename, series_num, series_tr, series_te, series_flip, phaseencodedir, phaseencodeangle, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, series_ti, percent_sampling, percent_phasefov, acq_matrix, slicethickness, slicespacing, bandwidth, image_type, image_comments, bold_reps, numfiles, series_notes, data_type, series_status, series_createdby, series_createdate) values (:studyRowID, :SeriesDateTime, :SeriesDescription, :ProtocolName, :SequenceName, :SeriesNumber, :RepetitionTime, :EchoTime, :FlipAngle, :InPlanePhaseEncodingDirection, :PhaseEncodeAngle, :PhaseEncodingDirectionPositive, :pixelX, :pixelY, :SliceThickness, :MagneticFieldStrength, :Rows, :Columns, :zsize, :InversionTime, :PercentSampling, :PercentPhaseFieldOfView, :AcquisitionMatrix, :SliceThickness, :SpacingBetweenSlices, :PixelBandwidth, :ImageType, :ImageComments, :boldreps, :numfiles, :importSeriesNotes, 'dicom', 'complete', 'import', now())";
			QSqlQuery q3;
			q3.prepare(sqlstring);
			q3.bindValue(":studyRowID", studyRowID);
			q3.bindValue(":SeriesDateTime", SeriesDateTime);
			q3.bindValue(":SeriesDescription", SeriesDescription);
			q3.bindValue(":ProtocolName", ProtocolName);
			q3.bindValue(":SequenceName", SequenceName);
			q3.bindValue(":SeriesNumber", SeriesNumber);
			q3.bindValue(":RepetitionTime", RepetitionTime);
			q3.bindValue(":EchoTime", EchoTime);
			q3.bindValue(":FlipAngle", FlipAngle);
			q3.bindValue(":InPlanePhaseEncodingDirection", InPlanePhaseEncodingDirection);

			if (PhaseEncodeAngle == "") q3.bindValue(":PhaseEncodeAngle", QVariant(QVariant::Double)); /* for null values */
			else q3.bindValue(":PhaseEncodeAngle", PhaseEncodeAngle);

			if (PhaseEncodingDirectionPositive == "") q3.bindValue(":PhaseEncodingDirectionPositive", QVariant(QVariant::Int)); /* for null values */
			else q3.bindValue(":PhaseEncodingDirectionPositive", PhaseEncodingDirectionPositive);

			q3.bindValue(":pixelX", pixelX);
			q3.bindValue(":pixelY", pixelY);
			q3.bindValue(":SliceThickness", SliceThickness);
			q3.bindValue(":MagneticFieldStrength", MagneticFieldStrength);
			q3.bindValue(":Rows", Rows);
			q3.bindValue(":Columns", Columns);
			q3.bindValue(":zsize", zsize);
			q3.bindValue(":InversionTime", InversionTime);
			q3.bindValue(":PercentSampling", PercentSampling);
			q3.bindValue(":PercentPhaseFieldOfView", PercentPhaseFieldOfView);
			q3.bindValue(":AcquisitionMatrix", AcquisitionMatrix);
			q3.bindValue(":SliceThickness", SliceThickness);
			q3.bindValue(":SpacingBetweenSlices", SpacingBetweenSlices);
			q3.bindValue(":PixelBandwidth", PixelBandwidth);
			q3.bindValue(":ImageType", ImageType);
			q3.bindValue(":ImageComments", ImageComments);
			q3.bindValue(":boldreps", boldreps);
			q3.bindValue(":numfiles", numfiles);
			q3.bindValue(":importSeriesNotes", importSeriesNotes);
			n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
			seriesRowID = q3.lastInsertId().toInt();

			IL_seriescreated = 1;
		}
	}
	else if (Modality.toUpper() == "CT") {
		dbModality = "ct";
		QSqlQuery q4;
		q4.prepare("select ctseries_id from ct_series where study_id = :studyRowID and series_num = :SeriesNumber");
		q4.bindValue(":studyRowID",studyRowID);
		q4.bindValue(":SeriesNumber",SeriesNumber);
		n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
		if (q4.size() > 0) {
			q4.first();
			seriesRowID = q4.value("ctseries_id").toInt();

			QSqlQuery q5;
			q5.prepare("update ct_series set series_datetime = :SeriesDateTime, series_desc = :SeriesDescription, series_protocol = :ProtocolName, series_spacingx = :pixelX, series_spacingy = :pixelY, series_spacingz = :SliceThickness, series_imgrows = :Rows, series_imgcols = :Columns, series_imgslices = :zsize, series_numfiles = :numfiles, series_contrastbolusagent = :ContrastBolusAgent, series_bodypartexamined = :BodyPartExamined, series_scanoptions = :ScanOptions, series_kvp = :KVP, series_datacollectiondiameter = :DataCollectionDiameter, series_contrastbolusroute = :ContrastBolusRoute, series_rotationdirection = :RotationDirection, series_exposuretime = :ExposureTime, series_xraytubecurrent = :XRayTubeCurrent, series_filtertype = :FilterType, series_generatorpower = :GeneratorPower, series_convolutionkernel = :ConvolutionKernel, series_status = 'complete' where ctseries_id = :seriesRowID");
			q5.bindValue(":SeriesDateTime", SeriesDateTime);
			q5.bindValue(":SeriesDescription", SeriesDescription);
			q5.bindValue(":ProtocolName", ProtocolName);
			q5.bindValue(":pixelX", pixelX);
			q5.bindValue(":pixelY", pixelY);
			q5.bindValue(":SliceThickness", SliceThickness);
			q5.bindValue(":Rows", Rows);
			q5.bindValue(":Columns", Columns);
			q5.bindValue(":zsize", zsize);
			q5.bindValue(":numfiles", numfiles);
			q5.bindValue(":ContrastBolusAgent", ContrastBolusAgent);
			q5.bindValue(":BodyPartExamined", BodyPartExamined);
			q5.bindValue(":ScanOptions", ScanOptions);
			q5.bindValue(":KVP", KVP);
			q5.bindValue(":DataCollectionDiameter", DataCollectionDiameter);
			q5.bindValue(":ContrastBolusRoute", ContrastBolusRoute);
			q5.bindValue(":RotationDirection", RotationDirection);
			q5.bindValue(":ExposureTime", ExposureTime);
			q5.bindValue(":XRayTubeCurrent", XRayTubeCurrent);
			q5.bindValue(":FilterType", FilterType);
			q5.bindValue(":GeneratorPower", GeneratorPower);
			q5.bindValue(":ConvolutionKernel", ConvolutionKernel);
			q5.bindValue(":seriesRowID", seriesRowID);
			n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);
			msgs << n->WriteLog(QString("This CT series [%1] exists, updating").arg(SeriesNumber));
			IL_seriescreated = 0;
		}
		else {
			/* create seriesRowID if it doesn't exist */
			QSqlQuery q5;
			q5.prepare("insert into ct_series ( study_id, series_datetime, series_desc, series_protocol, series_num, series_contrastbolusagent, series_bodypartexamined, series_scanoptions, series_kvp, series_datacollectiondiameter, series_contrastbolusroute, series_rotationdirection, series_exposuretime, series_xraytubecurrent, series_filtertype,series_generatorpower, series_convolutionkernel, series_spacingx, series_spacingy, series_spacingz, series_imgrows, series_imgcols, series_imgslices, numfiles, series_datatype, series_status, series_createdby ) values ( :studyRowID, :SeriesDateTime, :SeriesDescription, :ProtocolName, :SeriesNumber, :ContrastBolusAgent, :BodyPartExamined, :ScanOptions, :KVP, :DataCollectionDiameter, :ContrastBolusRoute, :RotationDirection, :ExposureTime, :XRayTubeCurrent, :FilterType, :GeneratorPower, :ConvolutionKernel, :pixelX, :pixelY, :SliceThickness, :Rows, :Columns, :zsize, :numfiles, 'dicom', 'complete', 'import')");
			q5.bindValue(":studyRowID", studyRowID);
			q5.bindValue(":SeriesDateTime", SeriesDateTime);
			q5.bindValue(":SeriesDescription", SeriesDescription);
			q5.bindValue(":ProtocolName", ProtocolName);
			q5.bindValue(":pixelX", pixelX);
			q5.bindValue(":pixelY", pixelY);
			q5.bindValue(":SliceThickness", SliceThickness);
			q5.bindValue(":Rows", Rows);
			q5.bindValue(":Columns", Columns);
			q5.bindValue(":zsize", zsize);
			q5.bindValue(":numfiles", numfiles);
			q5.bindValue(":ContrastBolusAgent", ContrastBolusAgent);
			q5.bindValue(":BodyPartExamined", BodyPartExamined);
			q5.bindValue(":ScanOptions", ScanOptions);
			q5.bindValue(":KVP", KVP);
			q5.bindValue(":DataCollectionDiameter", DataCollectionDiameter);
			q5.bindValue(":ContrastBolusRoute", ContrastBolusRoute);
			q5.bindValue(":RotationDirection", RotationDirection);
			q5.bindValue(":ExposureTime", ExposureTime);
			q5.bindValue(":XRayTubeCurrent", XRayTubeCurrent);
			q5.bindValue(":FilterType", FilterType);
			q5.bindValue(":GeneratorPower", GeneratorPower);
			q5.bindValue(":ConvolutionKernel", ConvolutionKernel);
			n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);
			seriesRowID = q5.lastInsertId().toInt();
			msgs << n->WriteLog(QString("CT series [%1] did not exist, creating").arg(SeriesNumber));
			IL_seriescreated = 1;
		}
	}
	else {
		/* this is the catch all for modalities which don't have a table in the database */

		n->WriteLog(QString("Modality [%1] numfiles [%2] zsize [%3]").arg(Modality).arg(numfiles).arg(zsize));

		if (n->ValidNiDBModality(Modality))
			dbModality = Modality.toLower();
		else
			dbModality = "ot";

		QSqlQuery q3;
		q3.prepare(QString("select %1series_id from %1_series where study_id = :studyid and series_num = :seriesnum").arg(dbModality));
		q3.bindValue(":studyid", studyRowID);
		q3.bindValue(":seriesnum", SeriesNumber);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		if (q3.size() > 0) {
			q3.first();
			msgs << n->WriteLog(QString("This %1 series [%2] exists, updating").arg(Modality).arg(SeriesNumber));
			seriesRowID = q3.value(dbModality + "series_id").toInt();

			QSqlQuery q4;
			if (dbModality == "ot")
				q4.prepare(QString("update %1_series set series_datetime = '" + SeriesDateTime + "', series_desc = :ProtocolName, series_spacingx = :pixelX, series_spacingy = :pixelY, series_spacingz = :SliceThickness, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, numfiles = :numfiles, series_status = 'complete' where %1series_id = :seriesRowID").arg(dbModality));
			else
				q4.prepare(QString("update %1_series set series_datetime = '" + SeriesDateTime + "', series_desc = :ProtocolName where %1series_id = :seriesRowID").arg(dbModality));
			q4.bindValue(":ProtocolName", ProtocolName);
			q4.bindValue(":pixelX", pixelX);
			q4.bindValue(":pixelY", pixelY);
			q4.bindValue(":SliceThickness", SliceThickness);
			q4.bindValue(":Rows", Rows);
			q4.bindValue(":Columns", Columns);
			q4.bindValue(":zsize", zsize);
			q4.bindValue(":numfiles", numfiles);
			q4.bindValue(":seriesRowID", seriesRowID);
			n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

			IL_seriescreated = 0;
		}
		else {
			/* create seriesRowID if it doesn't exist */
			msgs << n->WriteLog(QString(Modality + " series [%1] did not exist, creating").arg(SeriesNumber));
			QSqlQuery q4;
			if (dbModality == "ot") {
				q4.prepare(QString("insert into %1_series (study_id, series_datetime, series_desc, series_num, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, img_slices, numfiles, modality, data_type, series_status, series_createdby) values (:studyid, :SeriesDateTime, :ProtocolName, :SeriesNumber, :pixelX, :pixelY, :SliceThickness, :Rows, :Columns, :zsize, :numfiles, :Modality, 'dicom', 'complete', 'import')").arg(dbModality));
			}
			else {
				q4.prepare(QString("insert into %1_series (study_id, series_datetime, series_desc, series_num, series_createdby) values (:studyid, :SeriesDateTime, :ProtocolName, :SeriesNumber, 'import')").arg(dbModality));
			}
			q4.bindValue(":studyid", studyRowID);
			q4.bindValue(":SeriesDateTime", SeriesDateTime);
			q4.bindValue(":ProtocolName", ProtocolName);
			q4.bindValue(":SeriesNumber", SeriesNumber);
			q4.bindValue(":pixelX", pixelX);
			q4.bindValue(":pixelY", pixelY);
			q4.bindValue(":SliceThickness", SliceThickness);
			q4.bindValue(":Rows", Rows);
			q4.bindValue(":Columns", Columns);
			q4.bindValue(":zsize", zsize);
			q4.bindValue(":numfiles", numfiles);
			q4.bindValue(":Modality", Modality);
			n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
			seriesRowID = q4.lastInsertId().toInt();

			IL_seriescreated = 1;
		}
	}

	/* copy the file to the archive, update db info */
	msgs << n->WriteLog(QString("SeriesRowID: [%1]").arg(seriesRowID));

	/* create data directory if it doesn't already exist */
	QString outdir = QString("%1/%2/%3/%4/dicom").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	QString thumbdir = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	//msgs << n->WriteLog("outdir [" + outdir + "]");
	QString m;
	if (!n->MakePath(outdir, m))
		msgs << n->WriteLog("Unable to create output direcrory [" + outdir + "] because of error [" + m + "]");
	else
		msgs << n->WriteLog("Created outdir ["+outdir+"]");

	/* check if there are .dcm files already in the archive (outdir) */
	msgs << n->WriteLog("Checking for existing files in outdir [" + outdir + "]");
	QStringList existingdcms = n->FindAllFiles(outdir, "*.dcm");
	int numexistingdcms = existingdcms.size();

	/* rename **** EXISTING **** files in the output directory */
	if (numexistingdcms > 0) {
		//n->SortQStringListNaturally(existingdcms);

		/* check all files to see if its the same study datetime, patient name, dob, gender, series #
		 * if anything is different, move the file to a UID/Study/Series/dicom/existing directory
		 *
		 * if they're all the same, consolidate the files into one list of new and old, remove duplicates
		 */

		msgs << n->WriteLog(QString("There are [%1] existing files in [%2]. Beginning renaming...").arg(numexistingdcms).arg(outdir));

		int filecnt = 0;
		/* rename the existing files to make them unique */
		foreach (QString file, existingdcms) {
			QFileInfo f(file);

			/* check if its already in the intended filename format */
			QString fname = f.fileName();
			QStringList parts = fname.split("_");
			if (parts.size() == 8) {
				if ((subjectRealUID == parts[0]) && (studynum == parts[1]) && (SeriesNumber == parts[2]))
					continue;
			}

			/* need to rename it, get the DICOM tags */
			QHash<QString, QString> tags;
			if (!ParseDICOMFile(file, tags))
				continue;

			int SliceNumber = tags["AcquisitionNumber"].toInt();
			int InstanceNumber = tags["InstanceNumber"].toInt();
			QString AcquisitionTime = tags["AcquisitionTime"];
			QString ContentTime = tags["ContentTime"];
			QString SOPInstance = tags["SOPInstanceUID"];
			AcquisitionTime.remove(":").remove(".");
			ContentTime.remove(":").remove(".");
			SOPInstance = QString(QCryptographicHash::hash(SOPInstance.toUtf8(),QCryptographicHash::Md5).toHex());

			QString newfname = QString("%1_%2_%3_%4_%5_%6_%7_%8.dcm").arg(subjectRealUID).arg(studynum).arg(SeriesNumber).arg(SliceNumber, 5, 10, QChar('0')).arg(InstanceNumber, 5, 10, QChar('0')).arg(AcquisitionTime).arg(ContentTime).arg(SOPInstance);
			QString newfile = outdir + "/" + newfname;

			n->RenameFile(file, newfile); /* don't care about return value here, because the old filename may have been the same as the new one */

			filecnt++;
		}
		msgs << n->WriteLog(QString("Done renaming [%1] files").arg(filecnt));
	}

	/* create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size) */
	CreateThumbnail(files[files.size()/2], thumbdir);

	/* renumber the **** NEWLY **** added files to make them unique */
	msgs << n->WriteLog("Renaming new files");
	foreach (QString file, files) {
		/* need to rename it, get the DICOM tags */
		QHash<QString, QString> tags;
		if (!ParseDICOMFile(file, tags))
			continue;

		int SliceNumber = tags["AcquisitionNumber"].toInt();
		int InstanceNumber = tags["InstanceNumber"].toInt();
		QString AcquisitionTime = tags["AcquisitionTime"];
		QString ContentTime = tags["ContentTime"];
		QString SliceLocation = tags["SliceLocation"];
		QString SOPInstance = tags["SOPInstanceUID"];
		AcquisitionTime.remove(":").remove(".");
		ContentTime.remove(":").remove(".");
		SOPInstance = QString(QCryptographicHash::hash(SOPInstance.toUtf8(),QCryptographicHash::Md5).toHex());

		QString newfname = QString("%1_%2_%3_%4_%5_%6_%7_%8.dcm").arg(subjectRealUID).arg(studynum).arg(SeriesNumber).arg(SliceNumber, 5, 10, QChar('0')).arg(InstanceNumber, 5, 10, QChar('0')).arg(AcquisitionTime).arg(ContentTime).arg(SOPInstance);
		QString newfile = outdir + "/" + newfname;

		/* check if a file with the same name already exists */
		if (QFile::exists(newfile)) {
			/* remove the existing file */
			QFile::remove(newfile);
			IL_overwrote_existing = true;
		}
		else {
			IL_overwrote_existing = false;
		}

		/* move & rename the file */
		QFile fr(file);
		if (!n->RenameFile(file, newfile))
			msgs << n->WriteLog("Unable to rename newly added file [" + file + "] to [" + newfile + "]");

		/* insert an import log record */
		QSqlQuery q5;
		q5.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, modality_orig, patientid_orig, patientname_orig, stationname_orig, institution_orig, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values (:file, :newfile, 'DICOM', now(), 'successful', :importid, :importid, :importSiteID, :importProjectID, :IL_modality_orig, :PatientID, :IL_patientname_orig, :IL_stationname_orig, :IL_institution_orig, :subjectRealUID, :studynum, :subjectRowID, :studyRowID, :seriesRowID, :enrollmentRowID, :IL_seriescreated, :IL_studycreated, :IL_subjectcreated, :IL_familycreated, :IL_enrollmentcreated, :IL_overwrote_existing)");
		q5.bindValue(":file", file);
		q5.bindValue(":newfile", outdir+"/"+newfname);
		q5.bindValue(":importid", importid);
		q5.bindValue(":importSiteID", importSiteID);
		q5.bindValue(":importProjectID", importProjectID);
		q5.bindValue(":IL_modality_orig", IL_modality_orig);
		q5.bindValue(":PatientID", PatientID);
		q5.bindValue(":IL_patientname_orig", IL_patientname_orig);
		q5.bindValue(":IL_stationname_orig", IL_stationname_orig);
		q5.bindValue(":IL_institution_orig", IL_institution_orig);
		q5.bindValue(":IL_seriesnumber_orig", IL_seriesnumber_orig);
		q5.bindValue(":subjectRealUID", subjectRealUID);
		q5.bindValue(":studynum", studynum);
		q5.bindValue(":subjectRowID", subjectRowID);
		q5.bindValue(":studyRowID", studyRowID);
		q5.bindValue(":seriesRowID", seriesRowID);
		q5.bindValue(":enrollmentRowID", enrollmentRowID);
		q5.bindValue(":IL_seriescreated", IL_seriescreated);
		q5.bindValue(":IL_studycreated", IL_studycreated);
		q5.bindValue(":IL_subjectcreated", IL_subjectcreated);
		q5.bindValue(":IL_familycreated", IL_familycreated);
		q5.bindValue(":IL_enrollmentcreated", IL_enrollmentcreated);
		q5.bindValue(":IL_overwrote_existing", IL_overwrote_existing);
		n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);
	}

	/* get the size of the dicom files and update the DB */
	qint64 dirsize = 0;
	int nfiles;
	n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);
	msgs << n->WriteLog(QString("output directory [%1] is size [%2] and contains numfiles [%3]").arg(outdir).arg(dirsize).arg(numfiles));

	/* check if its an EPI sequence, but not a perfusion sequence */
	if (SequenceName.contains("epfid2d1_")) {
		if (ProtocolName.contains("perfusion", Qt::CaseInsensitive) || SequenceName.contains("ep2d_perf_tra")) { }
		else {
			mrtype = "epi";
			/* get the bold reps and attempt to get the z size */
			boldreps = numfiles;

			/* this method works ... sometimes */
			if ((mat1 > 0) && (mat4 > 0))
				zsize = (Rows/mat1)*(Columns/mat4); /* example (384/64)*(384/64) = 6*6 = 36 possible slices in a mosaic */
			else
				zsize = numfiles;
		}
	}
	else {
		zsize = numfiles;
	}

	/* update the database with the correct number of files/BOLD reps */
	if (dbModality == "mr") {
		QString sqlstring = QString("update %1_series set series_size = :dirsize, numfiles = :numfiles, bold_reps = :boldreps where %1series_id = :seriesid").arg(dbModality.toLower());
		QSqlQuery q2;
		q2.prepare(sqlstring);
		q2.bindValue(":dirsize", dirsize);
		q2.bindValue(":numfiles", numfiles);
		q2.bindValue(":boldreps", boldreps);
		q2.bindValue(":seriesid", seriesRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	}
	else {
		QString sqlstring = QString("update %1_series set series_size = :dirsize, series_numfiles = :numfiles where %1series_id = :seriesid").arg(dbModality.toLower());
		QSqlQuery q2;
		q2.prepare(sqlstring);
		q2.bindValue(":dirsize", dirsize);
		q2.bindValue(":numfiles", numfiles);
		q2.bindValue(":seriesid", seriesRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	}

	/* if a beh directory exists for this series from an import, move it to the final series directory */
	QString inbehdir = QString("%1/%2/beh").arg(n->cfg["incomingdir"]).arg(importid);
	QString outbehdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);

	if (importid > 0) {
		msgs << n->WriteLog("Checking for behavioral data in [" + inbehdir + "]");
		QDir bd(inbehdir);
		if (bd.exists()) {
			QString m;
			if (n->MakePath(outbehdir, m)) {
				QString systemstring = "mv -v " + inbehdir + "/* " + outbehdir + "/";
				msgs << n->WriteLog(n->SystemCommand(systemstring));

				qint64 behdirsize(0);
				int behnumfiles(0);
				n->GetDirSizeAndFileCount(outdir, behnumfiles, behdirsize);
				QString sqlstring = QString("update %1_series set beh_size = :behdirsize, numfiles_beh = :behnumfiles where %1series_id = :seriesRowID").arg(dbModality.toLower());
				QSqlQuery q3;
				q3.prepare(sqlstring);
				q3.bindValue(":behdirsize", behdirsize);
				q3.bindValue(":behnumfiles", behnumfiles);
				q3.bindValue(":seriesRowID", seriesRowID);
				n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
			}
			else
				msgs << n->WriteLog("Unable to create outbehdir ["+outbehdir+"] because of error ["+m+"]");
		}
	}

	/* change the permissions on the outdir to 777 so the webpage can read/write the directories */
	systemstring = "chmod -Rf 777 " + outdir;
	msgs << n->WriteLog(n->SystemCommand(systemstring));

	/* copy everything to the backup directory */
	QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	QDir bda(backdir);
	if (!bda.exists()) {
		msgs << n->WriteLog("Backup directory [" + backdir + "] does not exist. About to create it...");
		QString m;
		if (!n->MakePath(backdir, m))
			msgs << n->WriteLog("Unable to create backdir [" + backdir + "] because of error [" + m + "]");
	}
	msgs << n->WriteLog("Starting copy to the backup directory");
	systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
	msgs << n->WriteLog(n->SystemCommand(systemstring));
	msgs << n->WriteLog("Finished copying to the backup directory");

	msg += msgs.join("\n");
	return 1;
}


/* ---------------------------------------------------------- */
/* --------- InsertParRec ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::InsertParRec(int importid, QString file, QString &msg) {

	QStringList msgs;

	msgs << n->WriteLog(QString("----- In InsertParRec(%1,%2) -----").arg(importid).arg(file));

	/* import log variables */
	QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
	double IL_patientage_orig;
	int IL_seriesnumber_orig;
	QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
	QString IL_subject_uid;
	QString IL_project_number;
	int IL_seriescreated(0), IL_studycreated(0), IL_subjectcreated(0), IL_familycreated(0), IL_enrollmentcreated(0), IL_overwrote_existing(0);

	QString familyRealUID;
	int familyRowID(0);

	QString parfile = file;
	QString recfile = file;
	recfile.replace(".par", ".rec");

	QString PatientName;
	QString PatientBirthDate = "0001-01-01";
	QString PatientID = "NotSpecified";
	QString PatientSex = "U";
	double PatientWeight(0.0);
	double PatientSize(0.0);
	double PatientAge(0.0);
	QString StudyDescription;
	QString SeriesDescription;
	QString StationName = "PAR/REC";
	QString OperatorsName = "NotSpecified";
	QString PerformingPhysiciansName = "NotSpecified";
	QString InstitutionName = "NotSpecified";
	QString InstitutionAddress = "NotSpecified";
	int AccessionNumber(0);
	QString SequenceName;
	double MagneticFieldStrength(0.0);
	QString ProtocolName;
	QString StudyDateTime;
	QString SeriesDateTime;
	QString Modality;
	int SeriesNumber(0);
	int zsize(0);
	int boldreps(0);
	int numfiles(2); /* should always be 2 for .par/.rec */
	QString seriessequencename;
	int RepetitionTime(0);
	int Columns(0), Rows(0);
	int pixelX(0), pixelY(0);
	double SliceThickness(0.0), xspacing(0.0), yspacing(0.0), EchoTime(0.0), FlipAngle(0.0);

	int importInstanceID(0);
	int importSiteID(0);
	int importProjectID(0);
	int importPermanent(0);
	int importAnonymize(0);
	int importMatchIDOnly(0);
	QString importUUID;
	QString importSeriesNotes;
	QString importAltUIDs;

	/* if there is an importid, check to see how that thing is doing */
	if (importid > 0) {
		QSqlQuery q;
		q.prepare("select * from import_requests where importrequest_id = :importid");
		q.bindValue(":importid", importid);
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() > 0) {
			q.first();
			QString status = q.value("import_status").toString();
			importInstanceID = q.value("import_instanceid").toInt();
			importSiteID = q.value("import_siteid").toInt();
			importProjectID = q.value("import_projectid").toInt();
			importPermanent = q.value("import_permanent").toInt();
			importAnonymize = q.value("import_anonymize").toInt();
			importMatchIDOnly = q.value("import_matchidonly").toInt();
			importUUID = q.value("import_uuid").toString();
			importSeriesNotes = q.value("import_seriesnotes").toString();
			importAltUIDs = q.value("import_altuids").toString();
		}
	}

	QFile prf(file);

	/* read the .par file into an array, get all the useful info out of it */
	if (prf.open(QIODevice::ReadOnly | QIODevice::Text)) {

		QTextStream in(&prf);
		while (!in.atEnd()) {
			QString line = in.readLine().trimmed();

			if (line.contains("Patient name")) {
				QStringList p = line.split(":");
				if (p.size() > 1) {
					PatientName = p[1];
					PatientID = PatientName;
				}
			}
			if (line.contains("Examination name")) {
				QStringList p = line.split(":");
				if (p.size() > 1)
					StudyDescription = p[1];
			}
			if (line.contains("Protocol name")) {
				QStringList p = line.split(":");
				if (p.size() > 1) {
					ProtocolName = p[1];
					SeriesDescription = ProtocolName;
				}
			}
			if (line.contains("Examination date/time")) {
				QString datetime = line;
				datetime.replace(QRegExp("\\s+Examination date/time\\s+:"),"");
				QStringList p = datetime.split("/");
				if (p.size() > 1) {
					QString date = p[0];
					QString time = p[1];
					date.replace(".","-");
					StudyDateTime = date + " " + time;
					SeriesDateTime = date + " " + time;
				}
			}
			if (line.contains("Series Type")) {
				QStringList p = line.split(":");
				if (p.size() > 1) {
					Modality = p[1];
					Modality.replace("Image", "");
					Modality.replace("series", "", Qt::CaseInsensitive);
					Modality.toUpper();
				}
			}
			if (line.contains("Acquisition nr")) {
				QStringList p = line.split(":");
				if (p.size() > 1)
					SeriesNumber = p[1].toInt();
			}
			if (line.contains("Max. number of slices/locations")) {
				QStringList p = line.split(":");
				if (p.size() > 1)
					zsize = p[1].toInt();
			}
			if (line.contains("Max. number of dynamics")) {
				QStringList p = line.split(":");
				if (p.size() > 1)
					boldreps = p[1].toInt();
			}
			if (line.contains("Technique")) {
				QStringList p = line.split(":");
				if (p.size() > 1)
					SequenceName = p[1];
			}
			if (line.contains("Scan resolution")) {
				QStringList p = line.split(":");
				if (p.size() > 1) {
					QString resolution = p[1];
					QStringList p2 = resolution.split(QRegExp("\\s+"));
					if (p.size() > 1) {
						Columns = p2[0].toInt();
						Rows = p2[1].toInt();
					}
				}
			}
			if (line.contains("Repetition time")) {
				QStringList p = line.split(":");
				if (p.size() > 1)
					RepetitionTime = p[1].toInt();
			}
			/* get the first line of the image list... it should contain the flip angle */
			if (!line.startsWith(".") && !line.startsWith("#") && (line != "")) {
				QStringList p = line.split(QRegExp("\\s+"));

				if (p.size() > 9) pixelX = p[9].toInt(); /* 10 - xsize */
				if (p.size() > 10) pixelY = p[10].toInt(); /* 11 - ysize */
				if (p.size() > 22) SliceThickness = p[22].toDouble(); /* 23 - slice thickness */
				if (p.size() > 28) xspacing = p[28].toDouble(); /* 29 - xspacing */
				if (p.size() > 29) yspacing = p[29].toDouble(); /* 30 - yspacing */
				if (p.size() > 30) EchoTime = p[30].toDouble(); /* 31 - TE */
				if (p.size() > 35) FlipAngle = p[35].toInt(); /* 36 - flip */

				break;
			}
		}
	}

	    /* check if anything is funny, and not compatible with archiving this data */
	if (SeriesNumber == 0) {
		msgs << "Series number is 0";
		msg += msgs.join("\n");
		return 0;
	}
	if (PatientName == "") {
		msgs << "PatientName (ID) is blank";
		msg += msgs.join("\n");
		return 0;
	}

	/* set the import log variables */
	IL_modality_orig = Modality;
	IL_patientname_orig = PatientName;
	IL_patientdob_orig = PatientBirthDate;
	IL_patientsex_orig = PatientSex;
	IL_stationname_orig = StationName;
	IL_institution_orig = InstitutionName + "-" + InstitutionAddress;
	IL_studydatetime_orig = StudyDateTime;
	IL_seriesdatetime_orig = SeriesDateTime;
	IL_seriesnumber_orig = SeriesNumber;
	IL_studydesc_orig = StudyDescription;
	IL_seriesdesc_orig = SeriesDescription;
	IL_protocolname_orig = ProtocolName;
	IL_patientage_orig = PatientAge;

	/* ----- check if this subject/study/series/etc exists ----- */
	int projectRowID(0);
	QString subjectRealUID = PatientName;
	int subjectRowID(0);
	int enrollmentRowID;
	int studyRowID(0);
	int seriesRowID;
	QString costcenter;
	int studynum(0);

	/* get the costcenter */
	costcenter = GetCostCenter(StudyDescription);

	msgs << PatientID + " - " + StudyDescription;

	/* get the ID search string */
	QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
	QStringList altuidlist;
	if (importAltUIDs != "")
		altuidlist = importAltUIDs.split(",");

	/* check if the project and subject exist */
	msgs << "Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]";
	int projectcount(0);
	int subjectcount(0);
	QString sqlstring = QString("select (SELECT count(*) FROM `projects` WHERE project_costcenter = :costcenter) 'projectcount', (SELECT count(*) FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (%1) or a.uid = SHA1(:patientid) or b.altuid in (%1) or b.altuid = SHA1(:patientid)) 'subjectcount'").arg(SQLIDs);
	QSqlQuery q;
	q.prepare(sqlstring);
	q.bindValue(":patientid", PatientID);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		projectcount = q.value("projectcount").toInt();
		subjectcount = q.value("subjectcount").toInt();
	}

	/* if subject can't be found by UID, check by name/dob/sex (except if importMatchIDOnly is set), or create the subject */
	if (subjectcount < 1) {

		bool subjectFoundByName = false;
		/* search for an existing subject by name, dob, gender */
		if (!importMatchIDOnly) {
			QString sqlstring2 = "select subject_id, uid from subjects where name like '%" + PatientName + "%' and gender = left('" + PatientSex + "',1) and birthdate = :dob and isactive = 1";
			msgs << "Subject not found by UID. Checking if the subject exists using PatientName [" + PatientName + "] PatientSex [" + PatientSex + "] PatientBirthDate [" + PatientBirthDate + "]";
			QSqlQuery q2;
			q2.prepare(sqlstring2);
			q2.bindValue(":dob", PatientBirthDate);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			if (q2.size() > 0) {
				q2.first();
				subjectRealUID = q2.value("uid").toString();
				subjectRowID = q2.value("subject_id").toInt();
				msgs << "This subject exists. UID [" + subjectRealUID + "]";
				IL_subjectcreated = 0;
				subjectFoundByName = 1;
			}
		}
		/* if it couldn't be found, create a new subject */
		if (!subjectFoundByName) {
			CreateSubject(PatientID, PatientName, PatientBirthDate, PatientSex, PatientWeight, PatientSize, importUUID, msgs, subjectRowID, subjectRealUID);
			IL_subjectcreated = 1;
		}
	}
	else {
		/* get the existing subject ID, and UID! (the PatientID may be an alternate UID) */
		QString sqlstring = "SELECT a.subject_id, a.uid FROM subjects a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (" + SQLIDs + ") or a.uid = SHA1(:patientid) or b.altuid in (" + SQLIDs + ") or b.altuid = SHA1(:patientid)";
		QSqlQuery q2;
		q2.prepare(sqlstring);
		q2.bindValue(":patientid", PatientID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			subjectRowID = q2.value("subject_id").toInt();
			subjectRealUID = q2.value("uid").toString().toUpper().trimmed();

			msgs << QString("Found subject [subjectid %1, UID " + subjectRealUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID);
		}
		else {
			msgs << "Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]";
			msg += msgs.join("\n");
			return 0;
		}
		/* insert the PatientID as an alternate UID */
		if (PatientID != "") {
			QSqlQuery q2;
			q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
			q2.bindValue(":subjectid", subjectRowID);
			q2.bindValue(":patientid", PatientID);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		}
		IL_subjectcreated = 0;
	}

	n->WriteLog("subjectRealUID ["+subjectRealUID+"]");
	if (subjectRealUID == "") {
		msgs << "Error finding/creating subject. UID is blank";
		msg += msgs.join("\n");
		return 0;
	}

	/* check if the subject is part of a family, if not create a family for it */
	QSqlQuery q2;
	q2.prepare("select family_id from family_members where subject_id = :subjectid");
	q2.bindValue(":subjectid", subjectRowID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		familyRowID = q2.value("family_id").toInt();
		msgs << QString("This subject is part of a family [%1]").arg(familyRowID);
		IL_familycreated = 0;
	}
	else {
		int count = 0;

		/* create family UID */
		msgs << "Subject is not part of family, creating a unique family UID";
		do {
			familyRealUID = n->CreateUID("F");
			QSqlQuery q2;
			q2.prepare("SELECT * FROM `families` WHERE family_uid = :familyuid");
			q2.bindValue(":familyuid", familyRealUID);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			count = q2.size();
		} while (count > 0);

		/* create familyRowID if it doesn't exist */
		QSqlQuery q2;
		q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyRealUID, now(), :familyname)");
		q2.bindValue(":familyuid", familyRealUID);
		q2.bindValue(":familyname", "Proband-" + subjectRealUID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		familyRowID = q2.lastInsertId().toInt();

		q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
		q2.bindValue(":familyid", familyRowID);
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

		IL_familycreated = 1;
	}

	/* if project doesn't exist, use the generic project */
	if (projectcount < 1) {
		costcenter = "999999";
	}

	/* get the projectRowID */
	if (importProjectID == 0) {
		q2.prepare("select project_id from projects where project_costcenter = :costcenter");
		q2.bindValue(":costcenter", costcenter);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			projectRowID = q2.value("project_id").toInt();
		}
	}
	else {
		/* need to create the project if it doesn't exist */
		msgs << QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID);
		projectRowID = importProjectID;
	}

	/* check if the subject is enrolled in the project */
	q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
	q2.bindValue(":subjectid", subjectRowID);
	q2.bindValue(":projectid", projectRowID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		enrollmentRowID = q2.value("enrollment_id").toInt();
		msgs << QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID);
		IL_enrollmentcreated = 0;
	}
	else {
		/* create enrollmentRowID if it doesn't exist */
		q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
		q2.bindValue(":subjectid", subjectRowID);
		q2.bindValue(":projectid", projectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		enrollmentRowID = q2.lastInsertId().toInt();

		msgs << QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID);
		IL_enrollmentcreated = 1;
	}

	/* update alternate IDs, if there are any */
	if (altuidlist.size() > 0) {
		foreach (QString altuid, altuidlist) {
			if (altuid.trimmed() == "") {
				q2.prepare("insert ignore into subject_altuid (subject_id, altuid, enrollment_id) values (:subjectid, :altuid, :enrollmentid)");
				q2.bindValue(":subjectid", subjectRowID);
				q2.bindValue(":altuid", altuid);
				q2.bindValue(":enrollmentid", enrollmentRowID);
				n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
			}
		}
	}

	/* now determine if this study exists or not...
	 * basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	 * also checks the accession number against the study_num to see if this study was pre-registered
	 * HOWEVER, if there is an instanceID specified, we should only match a study that's part of an enrollment in the same instance */
	bool studyFound = false;
	msgs << QString("Checking if this study exists: enrollmentID [%1] study(accession)Number [%2] StudyDateTime [%3] Modality [%4] StationName [%5]").arg(enrollmentRowID).arg(AccessionNumber).arg(StudyDateTime).arg(Modality).arg(StationName);

	q2.prepare("select study_id, study_num from studies where enrollment_id = :enrollmentid and (study_num = :accessionnum or ((study_datetime between date_sub('" + StudyDateTime + "', interval 30 second) and date_add('" + StudyDateTime + "', interval 30 second)) and study_modality = :modality and study_site = :stationname))");
	q2.bindValue(":enrollmentid", enrollmentRowID);
	q2.bindValue(":accessionnum", AccessionNumber);
	q2.bindValue(":modality", Modality);
	q2.bindValue(":stationname", StationName);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		while (q2.next()) {
			int study_id = q2.value("study_id").toInt();
			studynum = q2.value("study_num").toInt();
			//int foundInstanceRowID = -1;

			/* check which instance this study is enrolled in */
			//QSqlQuery q3;
			//q3.prepare("select instance_id from projects where project_id = (select project_id from enrollment where enrollment_id = (select enrollment_id from studies where study_id = :studyid))");
			//q3.bindValue(":studyid",study_id);
			//n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
			//if (q3.size() > 0) {
			//	q3.first();
			    //foundInstanceRowID = q3.value("instance_id").toInt();
			    //msgs << QString("Found instance ID [%1] comparing to import instance ID [%2]").arg(foundInstanceRowID).arg(importInstanceID);

				/* if the study already exists within the instance specified in the project, then update the existing study, otherwise create a new one */
			    //if (importInstanceID == 0) {
					studyFound = true;
					studyRowID = study_id;

					QSqlQuery q4;
					msgs << QString("StudyID [%1] exists, updating").arg(study_id);
					q4.prepare("update studies set study_modality = :modality, study_datetime = '" + StudyDateTime + "', study_ageatscan = :patientage, study_height = :height, study_weight = :weight, study_desc = :studydesc, study_operator = :operator, study_performingphysician = :physician, study_site = :stationname, study_nidbsite = :importsiteid, study_institution = :institution, study_status = 'complete' where study_id = :studyid");
					q4.bindValue(":modality", Modality);
					q4.bindValue(":patientage", PatientAge);
					q4.bindValue(":height", PatientSize);
					q4.bindValue(":weight", PatientWeight);
					q4.bindValue(":studydesc", StudyDescription);
					q4.bindValue(":operator", OperatorsName);
					q4.bindValue(":physician", PerformingPhysiciansName);
					q4.bindValue(":stationname", StationName);
					q4.bindValue(":importsiteid", importSiteID);
					q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
					q4.bindValue(":studyid", studyRowID);
					n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);

					IL_studycreated = 0;
					break;
				//}
			//}
		}
	}
	if (!studyFound) {
		msgs << "Study did not exist, creating new study";

		/* create studyRowID if it doesn't exist */
		q2.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			studynum = q2.value("study_num").toInt() + 1;
		}
		else
			studynum = 1;

		QSqlQuery q4;
		q4.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_desc, study_operator, study_performingphysician, study_site, study_nidbsite, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentid, :studynum, :patientid, :modality, :studydatetime, :patientage, :height, :weight, :studydesc, :operator, :physician, :stationname, :importsiteid, :institution, 'complete', 'import', now())");
		q4.bindValue(":enrollmentid", enrollmentRowID);
		q4.bindValue(":studynum", studynum);
		q4.bindValue(":patientid", PatientID);
		q4.bindValue(":modality", Modality);
		q4.bindValue(":studydatetime", StudyDateTime);
		q4.bindValue(":patientage", PatientAge);
		q4.bindValue(":height", PatientSize);
		q4.bindValue(":weight", PatientWeight);
		q4.bindValue(":studydesc", StudyDescription);
		q4.bindValue(":operator", OperatorsName);
		q4.bindValue(":physician", PerformingPhysiciansName);
		q4.bindValue(":stationname", StationName);
		q4.bindValue(":importsiteid", importSiteID);
		q4.bindValue(":institution", InstitutionName + " - " + InstitutionAddress);
		n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
		studyRowID = q4.lastInsertId().toInt();

		IL_studycreated = 1;
	}

	n->WriteLog(QString("Going forward using the following: SubjectRowID [%1] ProjectRowID [%2] EnrollmentRowID [%3] StudyRowID [%4]").arg(subjectRowID).arg(projectRowID).arg(enrollmentRowID).arg(studyRowID));

	// ----- insert or update the series -----
	q2.prepare("select mrseries_id from mr_series where study_id = :studyid and series_num = :SeriesNumber");
	q2.bindValue(":studyid",studyRowID);
	q2.bindValue(":SeriesNumber",SeriesNumber);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		seriesRowID = q2.value("mrseries_id").toInt();

		QSqlQuery q3;
		q3.prepare("update mr_series set series_datetime = :SeriesDateTime,series_desc = :ProtocolName, series_sequencename = :SequenceName,series_tr = :RepetitionTime, series_te = :EchoTime,series_flip = :FlipAngle, series_spacingx = :pixelX,series_spacingy = :pixelY, series_spacingz = :SliceThickness, series_fieldstrength = :MagneticFieldStrength, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, bold_reps = :boldreps, numfiles = :numfiles, series_status = 'complete' where mrseries_id = :seriesRowID");

		q3.bindValue(":SeriesDateTime",SeriesDateTime);
		q3.bindValue(":ProtocolName",ProtocolName);
		q3.bindValue(":SequenceName",SequenceName);
		q3.bindValue(":RepetitionTime",RepetitionTime);
		q3.bindValue(":EchoTime",EchoTime);
		q3.bindValue(":FlipAngle",FlipAngle);
		q3.bindValue(":pixelX",pixelX);
		q3.bindValue(":pixelY",pixelY);
		q3.bindValue(":SliceThickness",SliceThickness);
		q3.bindValue(":MagneticFieldStrength",MagneticFieldStrength);
		q3.bindValue(":Rows",Rows);
		q3.bindValue(":Columns",Columns);
		q3.bindValue(":zsize",zsize);
		q3.bindValue(":boldreps",boldreps);
		q3.bindValue(":numfiles",numfiles);
		q3.bindValue(":seriesRowID",seriesRowID);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);

		IL_seriescreated = 0;
	}
	else {
		// create seriesRowID if it doesn't exist
		QSqlQuery q3;
		q3.prepare("insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, numfiles, data_type, series_status, series_createdby, series_createdate) values (:studyRowID, :SeriesDateTime, :ProtocolName, :SequenceName, :SeriesNumber, :RepetitionTime, :EchoTime, :FlipAngle, :pixelX, :pixelY, :SliceThickness, :MagneticFieldStrength, :Rows, :Columns, :zsize, :boldreps, :numfiles, 'parrec', 'complete', 'import', now())");
		q3.bindValue(":studyRowID",studyRowID);
		q3.bindValue(":SeriesDateTime",SeriesDateTime);
		q3.bindValue(":ProtocolName",ProtocolName);
		q3.bindValue(":SequenceName",SequenceName);
		q3.bindValue(":RepetitionTime",RepetitionTime);
		q3.bindValue(":EchoTime",EchoTime);
		q3.bindValue(":FlipAngle",FlipAngle);
		q3.bindValue(":pixelX",pixelX);
		q3.bindValue(":pixelY",pixelY);
		q3.bindValue(":SliceThickness",SliceThickness);
		q3.bindValue(":MagneticFieldStrength",MagneticFieldStrength);
		q3.bindValue(":Rows",Rows);
		q3.bindValue(":Columns",Columns);
		q3.bindValue(":zsize",zsize);
		q3.bindValue(":boldreps",boldreps);
		q3.bindValue(":numfiles",numfiles);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		seriesRowID = q3.lastInsertId().toInt();
		IL_seriescreated = 0;
	}

	/* get the series info */
	series s(seriesRowID, "mr", n);

	msgs << n->WriteLog(QString("Values from GetDataPathFromSeriesID(%1, 'mr'): Path [%2] UID [%3] StudyNum [%4] SeriesNum [%5] StudyID [%6] SubjectID [%7]").arg(seriesRowID).arg(s.datapath).arg(s.uid).arg(s.studynum).arg(s.seriesnum).arg(s.studyid).arg(s.subjectid));

	/* copy the file to the archive, update db info */
	msgs << n->WriteLog(QString("seriesRowID [%1]").arg(seriesRowID));

	/* create data directory if it doesn't already exist */
	QString outdir = QString("%1/%2/%3/%4/parrec").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	msgs << n->WriteLog("Outdir [" + outdir + "]");
	QString m;
	if (!n->MakePath(outdir, m))
		msgs << "Error creating outdir ["+outdir+"] because of error ["+m+"]";

	/* move the files into the outdir */
	n->MoveFile(parfile, outdir);
	n->MoveFile(recfile, outdir);

	/* insert an import log record (.par file) */
	QSqlQuery q5;
	q5.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, modality_orig, patientid_orig, patientname_orig, stationname_orig, institution_orig, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values (:file, :newfile, 'PARREC', now(), 'successful', :importid, :importid, :importSiteID, :importProjectID, :IL_modality_orig, :PatientID, :IL_patientname_orig, :IL_stationname_orig, :IL_institution_orig, :subjectRealUID, :studynum, :subjectRowID, :studyRowID, :seriesRowID, :enrollmentRowID, :IL_seriescreated, :IL_studycreated, :IL_subjectcreated, :IL_familycreated, :IL_enrollmentcreated, :IL_overwrote_existing)");
	q5.bindValue(":file", file);
	q5.bindValue(":newfile", outdir+"/"+parfile);
	q5.bindValue(":importid", importid);
	q5.bindValue(":importSiteID", importSiteID);
	q5.bindValue(":importProjectID", importProjectID);
	q5.bindValue(":IL_modality_orig", IL_modality_orig);
	q5.bindValue(":PatientID", PatientID);
	q5.bindValue(":IL_patientname_orig", IL_patientname_orig);
	q5.bindValue(":IL_stationname_orig", IL_stationname_orig);
	q5.bindValue(":IL_institution_orig", IL_institution_orig);
	q5.bindValue(":IL_seriesnumber_orig", IL_seriesnumber_orig);
	q5.bindValue(":subjectRealUID", subjectRealUID);
	q5.bindValue(":studynum", studynum);
	q5.bindValue(":subjectRowID", subjectRowID);
	q5.bindValue(":studyRowID", studyRowID);
	q5.bindValue(":seriesRowID", seriesRowID);
	q5.bindValue(":enrollmentRowID", enrollmentRowID);
	q5.bindValue(":IL_seriescreated", IL_seriescreated);
	q5.bindValue(":IL_studycreated", IL_studycreated);
	q5.bindValue(":IL_subjectcreated", IL_subjectcreated);
	q5.bindValue(":IL_familycreated", IL_familycreated);
	q5.bindValue(":IL_enrollmentcreated", IL_enrollmentcreated);
	q5.bindValue(":IL_overwrote_existing", IL_overwrote_existing);
	n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);

	q5.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, modality_orig, patientid_orig, patientname_orig, stationname_orig, institution_orig, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values (:file, :newfile, 'PARREC', now(), 'successful', :importid, :importid, :importSiteID, :importProjectID, :IL_modality_orig, :PatientID, :IL_patientname_orig, :IL_stationname_orig, :IL_institution_orig, :subjectRealUID, :studynum, :subjectRowID, :studyRowID, :seriesRowID, :enrollmentRowID, :IL_seriescreated, :IL_studycreated, :IL_subjectcreated, :IL_familycreated, :IL_enrollmentcreated, :IL_overwrote_existing)");
	q5.bindValue(":file", file);
	q5.bindValue(":newfile", outdir+"/"+parfile);
	q5.bindValue(":importid", importid);
	q5.bindValue(":importSiteID", importSiteID);
	q5.bindValue(":importProjectID", importProjectID);
	q5.bindValue(":IL_modality_orig", IL_modality_orig);
	q5.bindValue(":PatientID", PatientID);
	q5.bindValue(":IL_patientname_orig", IL_patientname_orig);
	q5.bindValue(":IL_stationname_orig", IL_stationname_orig);
	q5.bindValue(":IL_institution_orig", IL_institution_orig);
	q5.bindValue(":IL_seriesnumber_orig", IL_seriesnumber_orig);
	q5.bindValue(":subjectRealUID", subjectRealUID);
	q5.bindValue(":studynum", studynum);
	q5.bindValue(":subjectRowID", subjectRowID);
	q5.bindValue(":studyRowID", studyRowID);
	q5.bindValue(":seriesRowID", seriesRowID);
	q5.bindValue(":enrollmentRowID", enrollmentRowID);
	q5.bindValue(":IL_seriescreated", IL_seriescreated);
	q5.bindValue(":IL_studycreated", IL_studycreated);
	q5.bindValue(":IL_subjectcreated", IL_subjectcreated);
	q5.bindValue(":IL_familycreated", IL_familycreated);
	q5.bindValue(":IL_enrollmentcreated", IL_enrollmentcreated);
	q5.bindValue(":IL_overwrote_existing", IL_overwrote_existing);
	n->SQLQuery(q5, __FUNCTION__, __FILE__, __LINE__);

	/* get the size of the dicom files and update the DB */
	qint64 dirsize(0);
	int nfiles(0);
	n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);

	/* update the database with the correct number of files/BOLD reps */
	if (Modality == "mr") {
		QString sqlstring = QString("update %1_series set series_size = :dirsize where %1series_id = :seriesid").arg(Modality.toLower());
		QSqlQuery q2;
		q2.prepare(sqlstring);
		q2.bindValue(":dirsize", dirsize);
		q2.bindValue(":seriesid", seriesRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	}


	/* change the permissions to 777 so the webpage can read/write the directories */
	QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	n->SystemCommand(systemstring);

	/* copy everything to the backup directory */
	QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	QDir bda(backdir);
	if (!bda.exists()) {
		msgs << "Directory [" + backdir + "] does not exist. About to create it...";
		QString m;
		if (!n->MakePath(backdir, m))
			msgs << "Unable to create backdir [" + backdir + "] because of error [" + m + "]";
		else
			msgs << "Finished creating ["+backdir+"]";
	}
	msgs << "About to copy to the backup directory";
	systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
	msgs << n->SystemCommand(systemstring);
	msgs << "Finished copying to the backup directory";

	msg += msgs.join("\n");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertEEG -------------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::InsertEEG(int importid, QString file, QString &msg) {

	QStringList msgs;

	msgs << n->WriteLog(QString("----- In InsertEEG(%1, %2) -----").arg(importid).arg(file));

	/* import log variables */
	QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
	double IL_patientage_orig;
	int IL_seriesnumber_orig;
	QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
	QString IL_subject_uid;
	QString IL_project_number;
	int IL_seriescreated(0), IL_studycreated(0), IL_enrollmentcreated(0); // IL_subjectcreated(0), IL_familycreated(0), IL_overwrote_existing(0);

	QString familyRealUID;

	int projectRowID(0);
	QString subjectRealUID;
	int subjectRowID(0);
	int enrollmentRowID;
	int studyRowID(0);
	int seriesRowID(0);
	QString costcenter;
	int studynum(0);

	QString PatientName = "NotSpecified";
	QString PatientBirthDate = "0001-01-01";
	QString PatientID = "NotSpecified";
	QString PatientSex = "U";
	QString StudyDescription = "NotSpecified";
	QString SeriesDescription;
	QString StationName = "";
	QString OperatorsName = "NotSpecified";
	QString PerformingPhysiciansName = "NotSpecified";
	QString InstitutionName = "NotSpecified";
	QString InstitutionAddress = "NotSpecified";
	QString SequenceName;
	QString ProtocolName;
	QString StudyDateTime;
	QString SeriesDateTime;
	QString Modality = "EEG";
	int SeriesNumber;
	int FileNumber;
	int numfiles = 1;

	int importInstanceID(0);
	int importSiteID(0);
	int importProjectID(0);
	int importPermanent(0);
	int importAnonymize(0);
	int importMatchIDOnly(0);
	QString importUUID;
	QString importSeriesNotes;
	QString importAltUIDs;

	/* if there is an importid, check to see how that thing is doing */
	if (importid > 0) {
		QSqlQuery q;
		q.prepare("select * from import_requests where importrequest_id = :importid");
		q.bindValue(":importid", importid);
		n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
		if (q.size() > 0) {
			q.first();
			QString status = q.value("import_status").toString();
			importInstanceID = q.value("import_instanceid").toInt();
			importSiteID = q.value("import_siteid").toInt();
			importProjectID = q.value("import_projectid").toInt();
			importPermanent = q.value("import_permanent").toInt();
			importAnonymize = q.value("import_anonymize").toInt();
			importMatchIDOnly = q.value("import_matchidonly").toInt();
			importUUID = q.value("import_uuid").toString();
			importSeriesNotes = q.value("import_seriesnotes").toString();
			importAltUIDs = q.value("import_altuids").toString();
		}
	}
	else {
		msgs << n->WriteLog(QString("ImportID [%1] not found. Using default import parameters").arg(importid));
	}

	msgs << n->WriteLog(file);
	/* split the filename into the appropriate fields */
	/* AltUID_Date_task_operator_series.* */
	QString FileName = file;
	FileName.replace(QRegExp("\\..*+$",Qt::CaseInsensitive),""); // remove everything after the first dot
	QStringList parts = FileName.split("_");
	PatientID = parts[0].trimmed();
	if (parts[1].size() == 6) {
		StudyDateTime = SeriesDateTime = parts[1].mid(4,2) + "-" + parts[1].mid(0,2) + "-" + parts[1].mid(2,2) + " 00:00:00";
	}
	else if (parts[1].size() == 8) {
		StudyDateTime = SeriesDateTime = parts[1].mid(0,4) + "-" + parts[1].mid(4,2) + "-" + parts[1].mid(6,2) + " 00:00:00";
	}
	else if (parts[1].size() == 12) {
		StudyDateTime = SeriesDateTime = parts[1].mid(0,4) + "-" + parts[1].mid(4,2) + "-" + parts[1].mid(6,2) + " " + parts[1].mid(8,2) + ":" + parts[1].mid(10,2) + ":00";
	}
	else if (parts[1].size() == 14) {
		StudyDateTime = SeriesDateTime = parts[1].mid(0,4) + "-" + parts[1].mid(4,2) + "-" + parts[1].mid(6,2) + " " + parts[1].mid(8,2) + ":" + parts[1].mid(10,2) + ":" + parts[1].mid(12,2);
	}

	SeriesDescription = ProtocolName = parts[2].trimmed();
	OperatorsName = parts[3].trimmed();
	SeriesNumber = parts[4].trimmed().toInt();
	FileNumber = parts[5].trimmed().toInt();

	msgs << n->WriteLog(QString("Before fixing: PatientID [%1], StudyDateTime [%2], SeriesDateTime [%3], SeriesDescription [%4], OperatorsName [%5], SeriesNumber [%6], FileNumber [%7]").arg(PatientID).arg(StudyDateTime).arg(SeriesDateTime).arg(SeriesDescription).arg(OperatorsName).arg(SeriesNumber).arg(FileNumber));

	/* check if anything is funny */
	if (StudyDateTime == "") StudyDateTime = "0000-00-00 00:00:00";
	if (SeriesDateTime == "") SeriesDateTime = "0000-00-00 00:00:00";
	if (SeriesDescription == "") SeriesDescription = "Unknown";
	if (ProtocolName == "") ProtocolName = "Unknown";
	if (OperatorsName == "") OperatorsName = "Unknown";
	if (SeriesNumber < 1) SeriesNumber = 1;

	msgs << n->WriteLog(QString("Before fixing: PatientID [%1], StudyDateTime [%2], SeriesDateTime [%3], SeriesDescription [%4], OperatorsName [%5], SeriesNumber [%6], FileNumber [%7]").arg(PatientID).arg(StudyDateTime).arg(SeriesDateTime).arg(SeriesDescription).arg(OperatorsName).arg(SeriesNumber).arg(FileNumber));

	/* set the import log variables */
	IL_modality_orig = Modality;
	IL_patientname_orig = PatientName;
	IL_patientdob_orig = PatientBirthDate;
	IL_patientsex_orig = PatientSex;
	IL_stationname_orig = StationName;
	IL_institution_orig = InstitutionName + " - " + InstitutionAddress;
	IL_studydatetime_orig = StudyDateTime;
	IL_seriesdatetime_orig = SeriesDateTime;
	IL_seriesnumber_orig = SeriesNumber;
	IL_studydesc_orig = StudyDescription;
	IL_seriesdesc_orig = ProtocolName;
	IL_protocolname_orig = ProtocolName;
	IL_patientage_orig = 0;

	// ----- check if this subject/study/series/etc exists -----
	msgs << n->WriteLog(PatientID + " - " + StudyDescription);

	/* get the ID search string */
	QString SQLIDs = CreateIDSearchList(PatientID, importAltUIDs);
	QStringList altuidlist;
	if (importAltUIDs != "") {
		altuidlist = importAltUIDs.split(",");
		altuidlist.removeDuplicates();
	}

	// check if the project and subject exist
	msgs << "Checking if the subject exists by UID [" + PatientID + "] or AltUIDs [" + SQLIDs + "]";
	//int projectcount(0);
	//int subjectcount(0);
	QString sqlstring = QString("SELECT a.subject_id, a.uid FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in (%1) or a.uid = SHA1(:PatientID) or b.altuid in (%1) or b.altuid = SHA1(:PatientID)").arg(SQLIDs);
	QSqlQuery q;
	q.prepare(sqlstring);
	q.bindValue(":PatientID", PatientID);
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		q.first();
		subjectRowID = q.value("subject_id").toInt();
		subjectRealUID = q.value("uid").toString().trimmed();
	}
	else {
		/* subject doesn't already exist. Not creating new subjects as part of EEG/ET/etc upload because we have no age, DOB, sex. So note this failure in the import_logs table */
		msgs << n->WriteLog(QString("Subject with ID [%1] does not exist. Subjects must exist prior to EEG/ET import").arg(PatientID));
		msg += msgs.join("\n");
		return false;
	}

	if (subjectRealUID == "") {
		msgs << n->WriteLog("ERROR: UID blank");
		msg += msgs.join("\n");
		return false;
	}
	else
		msgs << n->WriteLog("UID found [" + subjectRealUID + "]");

	/* get the projectRowID */
	if (importProjectID == 0) {
		QSqlQuery q2;
		q2.prepare("select project_id from projects where project_costcenter = :costcenter");
		q2.bindValue(":costcenter", costcenter);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		if (q2.size() > 0) {
			q2.first();
			projectRowID = q2.value("project_id").toInt();
		}
	}
	else {
		/* need to create the project if it doesn't exist */
		msgs << QString("Project [" + costcenter + "] does not exist, assigning import project id [%1]").arg(importProjectID);
		projectRowID = importProjectID;
	}

	/* check if the subject is enrolled in the project */
	QSqlQuery q2;
	q2.prepare("select enrollment_id from enrollment where subject_id = :subjectid and project_id = :projectid");
	q2.bindValue(":subjectid", subjectRowID);
	q2.bindValue(":projectid", projectRowID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		enrollmentRowID = q2.value("enrollment_id").toInt();
		msgs << QString("Subject is enrolled in this project [%1]: enrollment [%2]").arg(projectRowID).arg(enrollmentRowID);
		IL_enrollmentcreated = 0;
	}
	else {
		/* create enrollmentRowID if it doesn't exist */
		q2.prepare("insert into enrollment (project_id, subject_id, enroll_startdate) values (:projectid, :subjectid, now())");
		q2.bindValue(":subjectid", subjectRowID);
		q2.bindValue(":projectid", projectRowID);
		n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
		enrollmentRowID = q2.lastInsertId().toInt();

		msgs << QString("Subject was not enrolled in this project. New enrollment [%1]").arg(enrollmentRowID);
		IL_enrollmentcreated = 1;
	}

	// now determine if this study exists or not...
	// basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	// also checks the accession number against the study_num to see if this study was pre-registered
	q2.prepare("select study_id, study_num from studies where enrollment_id = :enrollmentRowID and (study_datetime = :StudyDateTime and study_modality = :Modality and study_site = StationName)");
	q2.bindValue(":enrollmentRowID", enrollmentRowID);
	q2.bindValue(":StudyDateTime", StudyDateTime);
	q2.bindValue(":Modality", Modality);
	q2.bindValue(":StationName", StationName);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		studyRowID = q2.value("study_id").toInt();
		studynum =  q2.value("study_num").toInt();

		QSqlQuery q3;
		q3.prepare("update studies set study_modality = :Modality, study_datetime = :StudyDateTime, study_desc = :StudyDescription, study_operator = :OperatorsName, study_performingphysician = :PerformingPhysiciansName, study_site = :StationName, study_institution = :Institution, study_status = 'complete' where study_id = :studyRowID");
		q3.bindValue(":Modality", Modality);
		q3.bindValue(":StudyDateTime", StudyDateTime);
		q3.bindValue(":StudyDescription", StudyDescription);
		q3.bindValue(":OperatorsName", OperatorsName);
		q3.bindValue(":PerformingPhysiciansName", PerformingPhysiciansName);
		q3.bindValue(":StationName", StationName);
		q3.bindValue(":Institution", InstitutionName + " - " + InstitutionAddress);
		q3.bindValue(":studyRowID", studyRowID);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		IL_studycreated = 0;
	}
	else {
		/* create studyRowID if it doesn't exist */
		QSqlQuery q3;
		q3.prepare("SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = :subjectRowID");
		q3.bindValue(":subjectRowID", subjectRowID);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		if (q3.size() > 0) {
			q3.first();
			studynum = q3.value("study_num").toInt() + 1;
		}

		q3.prepare("insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_createdby, study_createdate) values (:enrollmentRowID, :studynum, :PatientID, :Modality, :StudyDateTime, :StudyDescription, :OperatorsName, :PerformingPhysiciansName, :StationName, :Institution, 'complete', 'import', now())");
		q3.bindValue(":enrollmentRowID", enrollmentRowID);
		q3.bindValue(":studynum", studynum);
		q3.bindValue(":PatientID", PatientID);
		q3.bindValue(":Modality", Modality);
		q3.bindValue(":StudyDateTime", StudyDateTime);
		q3.bindValue(":StudyDescription", StudyDescription);
		q3.bindValue(":OperatorsName", OperatorsName);
		q3.bindValue(":PerformingPhysiciansName", PerformingPhysiciansName);
		q3.bindValue(":StationName", StationName);
		q3.bindValue(":Institution", InstitutionName + " - " + InstitutionAddress);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		studyRowID = q3.lastInsertId().toInt();
		IL_studycreated = 1;
	}

	// ----- insert or update the series -----
	q2.prepare(QString("select %1series_id from %1_series where study_id = :studyRowID and series_num = :SeriesNumber").arg(Modality.toLower()));
	q2.bindValue(":studyRowID", studyRowID);
	q2.bindValue(":SeriesNumber", SeriesNumber);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
	if (q2.size() > 0) {
		q2.first();
		seriesRowID = q2.value(Modality.toLower() + "series_id").toInt();

		QSqlQuery q3;
		q3.prepare(QString("update %1_series set series_datetime = :SeriesDateTime, series_desc = :ProtocolName, series_protocol = :ProtocolName, series_numfiles = :numfiles, series_notes = :importSeriesNotes where %1series_id = :seriesRowID").arg(Modality.toLower()));
		q3.bindValue(":SeriesDateTime", SeriesDateTime);
		q3.bindValue(":ProtocolName", ProtocolName);
		q3.bindValue(":numfiles", numfiles);
		q3.bindValue(":importSeriesNotes", importSeriesNotes);
		q3.bindValue(":seriesRowID", seriesRowID);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		IL_seriescreated = 0;
	}
	else {
		// create seriesRowID if it doesn't exist
		QSqlQuery q3;
		q3.prepare(QString("insert into %1_series (study_id, series_datetime, series_desc, series_protocol, series_num, series_numfiles, series_notes, series_createdby) values (:studyRowID, :SeriesDateTime, :ProtocolName, :ProtocolName, :SeriesNumber, :numfiles, :importSeriesNotes, 'import')").arg(Modality.toLower()));
		q3.bindValue(":studyRowID", studyRowID);
		q3.bindValue(":SeriesDateTime", SeriesDateTime);
		q3.bindValue(":ProtocolName", ProtocolName);
		q3.bindValue(":SeriesNumber", SeriesNumber);
		q3.bindValue(":numfiles", numfiles);
		q3.bindValue(":importSeriesNotes", importSeriesNotes);
		n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
		studyRowID = q3.lastInsertId().toInt();
		IL_seriescreated = 1;
	}

	/* copy the file to the archive, update db info */
	msgs << n->WriteLog(QString("seriesRowID [%1]").arg(seriesRowID));

	/* create data directory if it doesn't already exist */
	QString outdir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber).arg(Modality.toLower());
	msgs << n->WriteLog("Creating outdir ["+outdir+"]");
	QString m;
	if (!n->MakePath(outdir,m))
		msgs << n->WriteLog("Unable to create directory ["+outdir+"] because of error ["+m+"]");

	/* move the files into the outdir */
	msgs << n->WriteLog("Moving ["+file+"] -> ["+outdir+"]");
	if (!n->MoveFile(file, outdir))
		n->WriteLog("Unable to move ["+file+"] to ["+outdir+"]");

	// insert an import log record
	//$sqlstring = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$file', '" . uc($Modality) . "', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')";
	//$report .= WriteLog("Inside InsertEEG() [$sqlstring]") . "\n";
	//$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	/* get the size of the files and update the DB */
	qint64 dirsize(0);
	int nfiles(0);
	n->GetDirSizeAndFileCount(outdir, nfiles, dirsize);
	q2.prepare(QString("update %1_series set series_size = :dirsize where %1series_id = :seriesRowID").arg(Modality.toLower()));
	q2.bindValue(":dirsize", dirsize);
	q2.bindValue(":seriesRowID", seriesRowID);
	n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

	/* change the permissions to 777 so the webpage can read/write the directories */
	QString systemstring = QString("chmod -Rf 777 %1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	n->SystemCommand(systemstring);

	/* copy everything to the backup directory */
	QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	QDir bda(backdir);
	if (!bda.exists()) {
		msgs << "Directory [" + backdir + "] does not exist. About to create it...";
		QString m;
		if (!n->MakePath(backdir, m))
			msgs << "Unable to create backdir [" + backdir + "] because of error [" + m + "]";
		else
			msgs << "Created backdir [" + backdir + "]";
	}
	msgs << "About to copy to the backup directory";
	systemstring = QString("rsync -az %1/* %5").arg(outdir).arg(backdir);
	msgs << n->SystemCommand(systemstring);
	msgs << "Finished copying to the backup directory";

	msg += msgs.join("\n");
	return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateThumbnail -------------------------------- */
/* ---------------------------------------------------------- */
void moduleImport::CreateThumbnail(QString f, QString outdir) {

	QString outfile = outdir + "/thumb.png";

	QString systemstring = "convert -normalize " + f + " " + outfile;
	n->WriteLog(n->SystemCommand(systemstring));
}
