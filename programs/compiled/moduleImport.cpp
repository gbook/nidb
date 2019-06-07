#include "moduleImport.h"
#include <QDebug>
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

	int ret = 0;

	/* before archiving the directory, delete any rows older than 4 days from the importlogs table */
	QSqlQuery q("delete from importlogs where importstartdate < date_sub(now(), interval 4 day)");
	n->SQLQuery(q, "Run", true);

	// ----- Step 1 - archive all files in the main directory -----
	if (ParseDirectory(n->cfg["incomingdir"], 0)) {
		ret = 1;
	}

	// ----- parse the sub directories -----
	// if there's a sub directory, the directory name is a rowID from the import table,
	// which contains additional information about the files being imported, such as project and site
	QStringList dirs = n->FindAllDirs(n->cfg["incomingdir"],"",false, false);
	foreach (QString dir, dirs) {
		QString fulldir = QString("%1/%2").arg(n->cfg["incomingdir"]).arg(dir);
		if (ParseDirectory(fulldir, dir.toInt())) {
			/* check if the directrory is empty */
			if (QDir(fulldir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0) {
				QString m;
				if (n->RemoveDir(fulldir, m))
					n->WriteLog("Removed directory [" + fulldir + "]");
				else
					n->WriteLog("Error removing directory [" + fulldir + "] [" + m + "]");
				ret = 1;
			}
		}
		n->ModuleRunningCheckIn();
		if (!n->ModuleCheckIfActive()) {
			n->WriteLog("Module is now inactive, stopping the module");
			return 0;
		}
	}
	return ret;
}


/* ---------------------------------------------------------- */
/* --------- GetImportStatus -------------------------------- */
/* ---------------------------------------------------------- */
QString moduleImport::GetImportStatus(int importid) {
	QSqlQuery q;
	q.prepare("select status from import_requests where importrequest_id = :id");
	q.bindValue(":id", importid);
	n->SQLQuery(q, "GetImportStatus", true);
	q.first();
	QString status = q.value("status").toString();
	return status;
}


/* ---------------------------------------------------------- */
/* --------- SetImportStatus -------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::SetImportStatus(int importid, QString status, QString msg, QString report, bool enddate) {

	QString sql;

	if (((status == "") || (status == "archiving") || (status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (importid > 0)) {
		sql = "update import_requests set import_status = :status";

		if (msg.trimmed() != "")
			sql += ", import_message = :msg";
		if (report.trimmed() != "")
			sql += ", archive_report = :report";
		if (enddate)
			sql += ", import_enddate = now()";
		sql += " where importrequest_id = :importid";

		QSqlQuery q;
		q.prepare(sql);
		q.bindValue(":status", status);
		q.bindValue(":msg", msg);
		q.bindValue(":report", report);
		q.bindValue(":importid", importid);
		n->SQLQuery(q, "SetImportStatus", true);
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

	QString archivereport;
	//bool useImportFields = false;
	QString importStatus;
	QString importModality;
	QString importDatatype;

	/* if there is an importRowID, check to see how that thing is doing */
	if (importid > 0) {
		QSqlQuery q;
		q.prepare("select * from import_requests where importrequest_id = :importid");
		q.bindValue(":importid",importid);
		n->SQLQuery(q,"Run",true);
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

	int ret = 0;
	int i = 0;
	bool iscomplete = false;

	/* ----- parse all files in /incoming ----- */
	QStringList files = n->FindAllFiles(dir, "*");
	int numfiles = files.size();
	n->WriteLog(QString("Found %1 files in %2").arg(numfiles).arg(dir));
	int processedFileCount = 0;
	foreach (QString file, files) {

		/* check the file size */
		qint64 fsize = QFileInfo(file).size();
		if (fsize < 1) {
			n->WriteLog(QString("File [%1] - size [%2] is 0 bytes!").arg(file).arg(fsize));
			SetImportStatus(importid, "error", "File has size of 0 bytes", QString("File [" + file + "] is empty"), true);
			n->MoveFile(file, n->cfg["problemdir"]);
			continue;
		}

		/* check if the file has been modified in the past 2 minutes (-120 seconds)
		 * if so, the file may still be being copied, so skip it */
		QDateTime now = QDateTime::currentDateTime();
		qint64 fileAgeInSec = now.secsTo(QFileInfo(file).lastModified());
		//n->WriteLog(QString("fileAgeInSec [%1] now[%2] fileLastModified [%3]").arg(fileAgeInSec).arg(now.toString()).arg(QFileInfo(file).lastModified().toString()));
		if (fileAgeInSec > -120) {
			continue;
		}

		processedFileCount++;

		if (processedFileCount%1000 == 0) {
			n->WriteLog(QString("Processed %1 files...").arg(processedFileCount));
			n->ModuleRunningCheckIn();
			if (!n->ModuleCheckIfActive()) {
				n->WriteLog("Module is now inactive, stopping the module");
				return 1;
			}
		}
		if (processedFileCount >= 5000) {
			n->WriteLog(QString("Reached [%1] files, going to archive them now").arg(processedFileCount));
			break;
		}

		/* make sure this file still exists... another instance of the program may have altered it */
		if (QFile::exists(file)) {

			QString dir = QFileInfo(file).path();
			QString fname = QFileInfo(file).fileName();
			QString ext = QFileInfo(file).completeSuffix().toLower();
			//chdir(dir);
			if (ext == "par") {
				n->WriteLog("Filetype is .par");

				QString m;
				QString report;

				QString archivereport;
				if (!InsertParRec(importid, file, archivereport)) {
					n->WriteLog(QString("InsertParRec(%1, %2) failed: [%3]").arg(file).arg(importid).arg(m));

					QSqlQuery q;
					q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, 'PARREC', :importid, now(), :msg)");
					q.bindValue(":file",file);
					q.bindValue(":importid",importid);
					q.bindValue(":msg",m);
					n->SQLQuery(q,"Run",true);

					n->MoveFile(file,n->cfg["problemdir"]);
					// change the import status to reflect the error
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

				bool ret;
				QString report;
				QString m;

				//my ($ret,$report) = InsertEEG($file, $importRowID, uc($importDatatype));

				archivereport += report;
				if (!ret) {
					n->WriteLog("InsertEEG($file, $importRowID) failed: [$ret]");
					QSqlQuery q;
					q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, :datatype, :importid, now(), :msg)");
					q.bindValue(":file", file);
					q.bindValue(":datatype", importDatatype.toUpper());
					q.bindValue(":id", importid);
					q.bindValue(":msg", m + " - moving to problem directory");
					n->SQLQuery(q, "ParseDirectory");
					n->MoveFile(file,n->cfg["problemdir"]);
					/* change the import status to reflect the error */
					SetImportStatus(importid, "error", "Problem inserting " + importDatatype.toUpper() + ": " + m, archivereport, true);
				}
				else {
					iscomplete = true;
				}
				i++;
			}
			else {
				//n->WriteLog("It's a dicom file!");
				/* check if this is a DICOM file */
				QHash<QString, QString> tags;
				QString filetype;

				if (ParseDICOMFile(file, tags)) {
					if (tags["SeriesInstanceUID"] != "") {
						dcmseries[tags["SeriesInstanceUID"]].append(file);
					}
					else {
						dcms[tags["InstitutionName"]][tags["StationName"]][tags["Modality"]][tags["PatientName"]][tags["PatientBirthDate"]][tags["PatientSex"]][tags["StudyDateTime"]][tags["SeriesNumber"]]["files"].append(file);
					}
				}
				else {
					n->WriteLog(QString("File [%1] - size [%2] is most likely not a dicom file").arg(file).arg(fsize));

					QSqlQuery q;
					QString m = "Not a DICOM file, moving to the problem directory";
					q.prepare("insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values (:file, :datatype, :importid, now(), :msg)");
					q.bindValue(":file", file);
					q.bindValue(":datatype", importDatatype.toUpper());
					q.bindValue(":id", importid);
					q.bindValue(":msg", m);
					n->SQLQuery(q, "ParseDirectory");
					n->MoveFile(file,n->cfg["problemdir"]);

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

	/* done reading all of the files in the directory (more may show up, but we'll get to those later)
	 * now archive them */
	for(QMap<QString, QStringList>::iterator a = dcmseries.begin(); a != dcmseries.end(); ++a) {
		QString seriesuid = a.key();

		QStringList files = dcmseries[seriesuid];

		QString archivereport;
		if (InsertDICOMSeries(importid, files, archivereport))
			iscomplete = true;
		else
			iscomplete = false;

		i++;
		n->ModuleRunningCheckIn();
		/* check if this module should be running now or not */
		if (!n->ModuleCheckIfActive()) {
			n->WriteLog("Not supposed to be running right now");
			/* cleanup so this import can continue another time */
			QSqlQuery q;
			q.prepare("update import_requests set import_status = '', import_enddate = now(), archivereport = :archivereport where importrequest_id = :importid");
			q.bindValue(":archivereport", archivereport);
			q.bindValue(":importid", importid);
			n->SQLQuery(q, "ParseDirectory");
			return 1;
		}
	}

	if (importid > 0 && iscomplete) {
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

	if (i > 0) {
		n->WriteLog("Finished extracting data for [" + dir + "]");
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

	/* check if the file is readable */
	gdcm::Reader r;
	r.SetFileName(file.toStdString().c_str());
	if (!r.CanRead()) {
		/* could not read the DICOM file... */
		return false;
	}

	if (!r.Read())
		return false;

	gdcm::StringFilter sf;
	sf = gdcm::StringFilter();
	sf.SetFile(r.GetFile());

	/* get all of the DICOM tags... we're not using an iterator because we went to know exactly what tags we have */
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
	tags["PatientSex"] =						QString(sf.ToString(gdcm::Tag(0x0010,0x0040)).c_str()).trimmed(); /* PatientSex */
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
	tags["AcquisitionMatrix"] =					QString(sf.ToString(gdcm::Tag(0x0018,0x1310)).c_str()).trimmed(); /* AcquisitionMatrix */
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
	else if (studydesc.contains(QRegularExpression("(?<=\\()[^)]*(?=\\))"))) /* look for anything between parentheses */
	{
		QRegularExpression regex("(?<=\\()[^)]*(?=\\))");
		QRegularExpressionMatch match = regex.match(studydesc);
		cc = match.captured(0);
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

	QString SQLIDs = "'" + PatientID + "'";
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
	int count = 0;

	msgs << "Searching for an unused UID";
	/* create a new subjectRealUID */
	do {
		subjectRealUID = n->CreateUID("S",3);
		QSqlQuery q2;
		q2.prepare("SELECT * FROM `subjects` WHERE uid = :uid");
		q2.bindValue(":uid",subjectRealUID);
		n->SQLQuery(q2, "InsertDICOMSeries");
		count = q2.size();
	} while (count > 0);

	msgs << "This subject did not exist. Create new UID [" + subjectRealUID + "]";

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
	n->SQLQuery(q2, "InsertDICOMSeries", true);
	subjectRowID = q2.lastInsertId().toInt();

	msgs << "Added new subject [" + subjectRealUID + "]";

	/* insert the PatientID as an alternate UID */
	if (PatientID != "") {
		QSqlQuery q2;
		q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectrowid, :patientid)");
		q2.bindValue(":subjectid",subjectRowID);
		q2.bindValue(":patientid",PatientID);
		n->SQLQuery(q2, "InsertDICOMSeries", true);
		msgs << "Added alternate UID [" + PatientID + "]";
	}
	return true;
}


/* ---------------------------------------------------------- */
/* --------- InsertDICOMSeries ------------------------------ */
/* ---------------------------------------------------------- */
bool moduleImport::InsertDICOMSeries(int importid, QStringList files, QString &msg) {

	n->SortQStringListNaturally(files);

	QStringList msgs;

	msgs << QString("----- Inside InsertDICOM() with [%1] files -----").arg(files.size());

	/* import log variables */
	QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
	double IL_patientage_orig;
	int IL_seriesnumber_orig;
	QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
	//double IL_patientage_new;
	//int IL_seriesnumber_new;
	QString IL_subject_uid;
	//int IL_study_num, IL_enrollmentid;
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
		n->SQLQuery(q, "InsertDICOMSeries", true);
		if (q.size() > 0) {
			q.first();
			QString status = q.value("status").toString();
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
	QFile zf(f);
	double fsize = zf.size();

	if (ParseDICOMFile(f, tags)) {
		if (QFile::exists(f)) {
			msgs << QString("File [%1] exists, size is [%2] bytes").arg(f).arg(fsize);
		}
		else {
			msgs << QString("File [%1] does not exist!").arg(f);
			msg = msgs.join("\n");
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
	//int EchoTrainLength = tags["EchoTrainLength"].toInt();
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
				if (!line.contains(QRegularExpression(QStringLiteral("[^\\x{0000}-\\x{007F}]")))) {
					n->Print(n->WriteLog("PhaseEncodeAngle line ["+line+"]"));
					int idx = line.indexOf(".dInPlaneRot");
					line = line.mid(idx,23);
					QStringList vals = line.split(QRegExp("\\s+"));
					PhaseEncodeAngle = vals.last().trimmed();
					break;
				}
			}
		}
		n->WriteLog(QString("Found PhaseEncodeAngle of [%1]").arg(PhaseEncodeAngle));
		msgs << QString("Found PhaseEncodeAngle of [%1]").arg(PhaseEncodeAngle);
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
	//double KVP = tags["KVP"].toDouble();
	//double DataCollectionDiameter = tags["DataCollectionDiameter"].toDouble();
	QString ContrastBolusRoute = tags["ContrastBolusRoute"];
	QString RotationDirection = tags["RotationDirection"];
	//double ExposureTime = tags["ExposureTime"].toDouble();
	//double XRayTubeCurrent = tags["XRayTubeCurrent"].toDouble();
	QString FilterType = tags["FilterType"];
	//double GeneratorPower = tags["GeneratorPower"].toDouble();
	QString ConvolutionKernel = tags["ConvolutionKernel"];

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

	/* fix some of the fields to be amenable to the DB */
	if (Modality == "")
		Modality = "OT";
	StudyDate.replace(":","-");
	SeriesDate.replace(":","-");
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

	/* check if the patient age contains any characters */
	if (PatientAgeStr.contains('Y')) PatientAge = PatientAgeStr.replace("Y","").toDouble();
	if (PatientAgeStr.contains('M')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/12.0;
	if (PatientAgeStr.contains('W')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/52.0;
	if (PatientAgeStr.contains('D')) PatientAge = PatientAgeStr.replace("Y","").toDouble()/365.25;

	/* fix studydatetime */
	if ((StudyDateTime == "") || (StudyDateTime.contains(QRegExp("[a-z]",Qt::CaseInsensitive))) || (StudyDateTime == "0-00-00") || (StudyDateTime == "00-00-00") || (StudyDateTime == "0000-00-00")) {
		StudyDateTime = "0000-01-01";
		msgs << "StudyDateTime changed to " + StudyDateTime;
	}

	/* fix seriesdate */
	if ((SeriesDateTime == "") || (SeriesDateTime.contains(QRegExp("[a-z]",Qt::CaseInsensitive))) || (SeriesDateTime == "0-00-00") || (SeriesDateTime == "00-00-00") || (SeriesDateTime == "0000-00-00")) {
		SeriesDateTime = "0000-01-01";
		msgs << "SeriesDateTime changed to " + SeriesDateTime;
	}

	/* fix patient birthdate */
	if ((PatientBirthDate == "") || (PatientBirthDate.contains(QRegExp("[a-z]",Qt::CaseInsensitive))) || (PatientBirthDate == "0-00-00") || (PatientBirthDate == "00-00-00") || (PatientBirthDate == "0000-00-00")) {
		PatientBirthDate = "0000-01-01";
		msgs << "PatientBirthDate changed to " + PatientBirthDate;
	}

	/* fix patient age */
	if (PatientAge < 0.001) {
		QDate studydate;
		QDate dob;
		studydate.fromString(StudyDate);
		dob.fromString(PatientBirthDate);

		PatientAge = dob.daysTo(studydate)/365.25;
	}

	/* normalize the strings to remove non-ascii or non-printable characters */
	PatientName = PatientName.normalized(QString::NormalizationForm_C);
	PatientSex = PatientSex.normalized(QString::NormalizationForm_C);

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
	n->SQLQuery(q, "InsertDICOMSeries", true);
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
			n->SQLQuery(q2, "InsertDICOMSeries", true);
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
		n->SQLQuery(q2, "InsertDICOMSeries", true);
		if (q2.size() > 0) {
			q2.first();
			subjectRowID = q2.value("subject_id").toInt();
			subjectRealUID = q2.value("uid").toString().toUpper().trimmed();

			msgs << QString("Found subject [%1, " + subjectRealUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID);
		}
		else {
			msgs << "Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]";
			msg = msgs.join("\n");
			return 0;
		}
		/* insert the PatientID as an alternate UID */
		if (PatientID != "") {
			QSqlQuery q2;
			q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
			q2.bindValue(":subjectid", subjectRowID);
			q2.bindValue(":patientid", PatientID);
			n->SQLQuery(q2, "InsertDICOMSeries", true);
		}
		IL_subjectcreated = 0;
	}

	n->WriteLog("subjectRealUID ["+subjectRealUID+"]");
	if (subjectRealUID == "") {
		msgs << "Error finding/creating subject. UID is blank";
		msg = msgs.join("\n");
		return 0;
	}

	/* check if the subject is part of a family, if not create a family for it */
	QSqlQuery q2;
	q2.prepare("select family_id from family_members where subject_id = :subjectid");
	q2.bindValue(":subjectid", subjectRowID);
	n->SQLQuery(q2, "InsertDICOMSeries", true);
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
			n->SQLQuery(q2, "InsertDICOMSeries", true);
			count = q2.size();
		} while (count > 0);

		/* create familyRowID if it doesn't exist */
		QSqlQuery q2;
		q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyRealUID, now(), :familyname)");
		q2.bindValue(":familyuid", familyRealUID);
		q2.bindValue(":familyname", "Proband-" + subjectRealUID);
		n->SQLQuery(q2, "InsertDICOMSeries", true);
		familyRowID = q2.lastInsertId().toInt();

		q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
		q2.bindValue(":familyid", familyRowID);
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, "InsertDICOMSeries", true);

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
		n->SQLQuery(q2, "InsertDICOMSeries", true);
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
	n->SQLQuery(q2, "InsertDICOMSeries", true);
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
		n->SQLQuery(q2, "InsertDICOMSeries", true);
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
				n->SQLQuery(q2, "InsertDICOMSeries", true);
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
	n->SQLQuery(q2, "InsertDICOMSeries", true);
	if (q2.size() > 0) {
		while (q2.next()) {
			int study_id = q2.value("study_id").toInt();
			studynum = q2.value("study_num").toInt();
			int foundInstanceRowID = -1;

			/* check which instance this study is enrolled in */
			QSqlQuery q3;
			q3.prepare("select instance_id from projects where project_id = (select project_id from enrollment where enrollment_id = (select enrollment_id from studies where study_id = :studyid))");
			q3.bindValue(":studyid",study_id);
			n->SQLQuery(q3, "InsertDICOMSeries", true);
			if (q3.size() > 0) {
				q3.first();
				foundInstanceRowID = q3.value("instance_id").toInt();
				msgs << QString("Found instance ID [%1] comparing to import instance ID [%2]").arg(foundInstanceRowID).arg(importInstanceID);

				/* if the study already exists within the instance specified in the project, then update the existing study, otherwise create a new one */
				if (importInstanceID == 0) {
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
					n->SQLQuery(q4, "InsertDICOMSeries", true);

					IL_studycreated = 0;
					break;
				}
			}
		}
	}
	if (!studyFound) {
		msgs << "Study did not exist, creating new study";

		/* create studyRowID if it doesn't exist */
		q2.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, "InsertDICOMSeries", true);
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
		n->SQLQuery(q4, "InsertDICOMSeries", true);
		studyRowID = q4.lastInsertId().toInt();

		IL_studycreated = 1;
	}

	/* gather series information */
	int boldreps(1);
	int numfiles = files.size();
	int zsize(0);
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
		n->SQLQuery(q2, "InsertDICOMSeries", true);
		if (q2.size() > 0) {
			q2.first();
			seriesRowID = q2.value("mrseries_id").toInt();

			msgs << QString("This MR series [%1] exists, updating").arg(SeriesNumber);

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

			IL_seriescreated = 0;

			/* if the series is being updated, the QA information might be incorrect or be based on the wrong number of files, so delete the mr_qa row */
			q3.prepare("delete from mr_qa where mrseries_id = :seriesid");
			q3.bindValue(0,seriesRowID);
			n->SQLQuery(q3, "InsertDICOMSeries", true);

			msgs << "Deleted from mr_qa. Now deleting from qc_results";

			/* delete the qc module rows */
			q3.prepare("select qcmoduleseries_id from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
			q3.bindValue(0,seriesRowID);
			n->SQLQuery(q3, "InsertDICOMSeries", true);

			QStringList qcidlist;
			if (q3.size() > 0) {
				while (q3.next())
					qcidlist << q3.value("qcmoduleseries_id").toString();

				QString sqlstring = "delete from qc_results where qcmoduleseries_id in (" + qcidlist.join(",") + ")";
				q3.prepare(sqlstring);
				n->SQLQuery(q3, "InsertDICOMSeries", true);
			}

			msgs << "Deleted from qc_results... about to delete from qc_moduleseries";

			q3.prepare("delete from qc_moduleseries where series_id = :seriesid and modality = 'mr'");
			q3.bindValue(0,seriesRowID);
			n->SQLQuery(q3, "InsertDICOMSeries", true);
		}
		else {
			/* create seriesRowID if it doesn't exist */
			msgs << QString("MR series [%1] did not exist, creating").arg(SeriesNumber);
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
			n->SQLQuery(q3, "InsertDICOMSeries", true);
			seriesRowID = q3.lastInsertId().toInt();

			IL_seriescreated = 1;
		}
	}
	/*
	elsif (uc($Modality) eq "CT") {
		$dbModality = "ct";
		$sqlstring = "select ctseries_id from ct_series where study_id = $studyRowID and series_num = $SeriesNumber";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'ctseries_id'};

			$sqlstring = "update ct_series set series_datetime = '$SeriesDateTime', series_desc = '$SeriesDescription', series_protocol = '$ProtocolName', series_spacingx = '$pixelX', series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_imgrows = '$Rows', series_imgcols = '$Columns', series_imgslices = '$zsize', series_numfiles = '$numfiles', series_contrastbolusagent = '$ContrastBolusAgent', series_bodypartexamined = '$BodyPartExamined', series_scanoptions = '$ScanOptions', series_kvp = '$KVP', series_datacollectiondiameter = '$DataCollectionDiameter', series_contrastbolusroute = '$ContrastBolusRoute', series_rotationdirection = '$RotationDirection', series_exposuretime = '$ExposureTime', series_xraytubecurrent = '$XRayTubeCurrent', series_filtertype = '$FilterType', series_generatorpower = '$GeneratorPower', series_convolutionkernel = '$ConvolutionKernel', series_status = 'complete' where ctseries_id = $seriesRowID";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$report .= WriteLog("This CT series [$SeriesNumber] exists, updating") . "\n";
			$IL_seriescreated = 0;
		}
		else {
            # create seriesRowID if it doesn't exist
			$sqlstring = "insert into ct_series ( study_id, series_datetime, series_desc, series_protocol, series_num, series_contrastbolusagent, series_bodypartexamined, series_scanoptions, series_kvp, series_datacollectiondiameter, series_contrastbolusroute, series_rotationdirection, series_exposuretime, series_xraytubecurrent, series_filtertype,series_generatorpower, series_convolutionkernel, series_spacingx, series_spacingy, series_spacingz, series_imgrows, series_imgcols, series_imgslices, numfiles, series_datatype, series_status, series_createdby
			) values (
			$studyRowID, '$SeriesDateTime', '$SeriesDescription', '$ProtocolName', '$SeriesNumber', '$ContrastBolusAgent', '$BodyPartExamined', '$ScanOptions', '$KVP', '$DataCollectionDiameter', '$ContrastBolusRoute', '$RotationDirection', '$ExposureTime', '$XRayTubeCurrent', '$FilterType', '$GeneratorPower', '$ConvolutionKernel', '$pixelX', '$pixelY', '$SliceThickness', '$Rows', '$Columns', '$zsize', '$numfiles', 'dicom', 'complete', '$scriptname')";
            #print "[$sqlstring]\n";
			my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
			$seriesRowID = $result2->insertid;
			$report .= WriteLog("CT series [$SeriesNumber] did not exist, creating") . "\n";
			$IL_seriescreated = 1;
		}
	}
	*/
	else {
		/* this is the catch all for modalities which don't have a table in the database */
		dbModality = "ot";
		QSqlQuery q3;
		q3.prepare("select otseries_id from ot_series where study_id = :studyid and series_num = :seriesnum");
		q3.bindValue(":studyid", studyRowID);
		q3.bindValue(":seriesnum", SeriesNumber);
		n->SQLQuery(q3, "InsertDICOMSeries", true);
		if (q3.size() > 0) {
			q3.first();
			msgs << QString("This OT series [%1] exists, updating").arg(SeriesNumber);
			seriesRowID = q3.value("otseries_id").toInt();

			QSqlQuery q4;
			q4.prepare("update ot_series set series_datetime = '" + SeriesDateTime + "', series_desc = :ProtocolName, series_sequencename = :SequenceName, series_spacingx = :pixelX, series_spacingy = :pixelY, series_spacingz = :SliceThickness, img_rows = :Rows, img_cols = :Columns, img_slices = :zsize, numfiles = :numfiles, series_status = 'complete' where otseries_id = :seriesRowID");
			q4.bindValue(":ProtocolName", ProtocolName);
			q4.bindValue(":SequenceName", SequenceName);
			q4.bindValue(":pixelX", pixelX);
			q4.bindValue(":pixelY", pixelY);
			q4.bindValue(":SliceThickness", SliceThickness);
			q4.bindValue(":Rows", Rows);
			q4.bindValue(":Columns", Columns);
			q4.bindValue(":zsize", zsize);
			q4.bindValue(":numfiles", numfiles);
			q4.bindValue(":seriesRowID", seriesRowID);
			n->SQLQuery(q4, "InsertDICOMSeries", true);

			IL_seriescreated = 0;
		}
		else {
			/* create seriesRowID if it doesn't exist */
			msgs << QString("OT series [%1] did not exist, creating").arg(SeriesNumber);
			QSqlQuery q4;
			q4.prepare("insert into ot_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, img_slices, numfiles, modality, data_type, series_status, series_createdby) values (:studyid, :SeriesDateTime, :ProtocolName, :SequenceName, :SeriesNumber, :pixelX, :pixelY, :SliceThickness, :Rows, :Columns, :zsize, :numfiles, :Modality, 'dicom', 'complete', 'import')");
			q4.bindValue(":studyid", studyRowID);
			q4.bindValue(":SeriesDateTime", SeriesDateTime);
			q4.bindValue(":ProtocolName", ProtocolName);
			q4.bindValue(":SequenceName", SequenceName);
			q4.bindValue(":SeriesNumber", SeriesNumber);
			q4.bindValue(":pixelX", pixelX);
			q4.bindValue(":pixelY", pixelY);
			q4.bindValue(":SliceThickness", SliceThickness);
			q4.bindValue(":Rows", Rows);
			q4.bindValue(":Columns", Columns);
			q4.bindValue(":zsize", zsize);
			q4.bindValue(":numfiles", numfiles);
			q4.bindValue(":Modality", Modality);
			n->SQLQuery(q4, "InsertDICOMSeries", true);
			seriesRowID = q4.lastInsertId().toInt();

			IL_seriescreated = 1;
		}
	}

	/* copy the file to the archive, update db info */
	msgs << QString("SeriesRowID: [%1]").arg(seriesRowID);
	n->WriteLog(QString("SeriesRowID: [%1]").arg(seriesRowID));

	/* create data directory if it doesn't already exist */
	QString outdir = QString("%1/%2/%3/%4/dicom").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	QString thumbdir = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	msgs << "outdir: " + outdir;
	n->WriteLog("outdir: " + outdir);
	QString m;
	if (!n->MakePath(outdir, m)) {
		msgs << "Unable to create output direcrory [" + outdir + "] because of error [" + m + "]";
		n->WriteLog("Unable to create output direcrory [" + outdir + "] because of error [" + m + "]");
	}
	else {
		msgs << "Created outdir ["+outdir+"] msg [" + m + "]";
		n->WriteLog("Created outdir ["+outdir+"] msg [" + m + "]");
	}

	/* check if there are .dcm files already in the archive (outdir) */
	msgs << "Checking for existing files in the outputdir [" + outdir + "]";
	n->WriteLog("Checking for existing files in the outputdir [" + outdir + "]");
	QStringList existingdcms = n->FindAllFiles(outdir, "*.dcm");
	int numexistingdcms = existingdcms.size();

	/* rename **** EXISTING **** files in the output directory */
	if (numexistingdcms > 0) {
		n->SortQStringListNaturally(existingdcms);

		/* check all files to see if its the same study datetime, patient name, dob, gender, series #
		 * if anything is different, move the file to a UID/Study/Series/dicom/existing directory
		 *
		 * if they're all the same, consolidate the files into one list of new and old, remove duplicates
		 */

		msgs << QString("There are [%1] existing files in [%2]. Beginning renaming...").arg(numexistingdcms).arg(outdir);

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

			QFile dfile(file);
			if (!dfile.rename(newfname)) {
				msgs << "Unable to rename [" + fname + "] to [" + newfname + "]";
				n->WriteLog("Unable to rename [" + fname + "] to [" + newfname + "]");
			}
			filecnt++;
		}
		msgs << QString("Done renaming [%1] files").arg(filecnt);
	}

	msgs << "Beginning renumbering of new files";
	n->WriteLog("Beginning renumbering of new files");

	/* create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size) */
	CreateThumbnail(files[files.size()/2], thumbdir);

	/* renumber the **** NEWLY **** added files to make them unique */
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
			n->WriteLog("Unable to rename file [" + file + "] to [" + newfile + "]");

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
		n->SQLQuery(q5, "InsertDICOMSeries");
	}

	/* get the size of the dicom files and update the DB */
	qint64 dirsize = 0;
	uint nfiles;
	dirsize = n->GetDirByteSize(outdir);
	nfiles = n->GetDirFileCount(outdir);
	msgs << QString("output directory [%1] is size [%2] and numfiles [%3] for directory").arg(outdir).arg(dirsize).arg(numfiles);
	n->WriteLog(QString("output directory [%1] is size [%2] and numfiles [%3] for directory").arg(outdir).arg(dirsize).arg(numfiles));

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
		n->SQLQuery(q2, "InsertDICOMSeries", true);
	}

	/* if a beh directory exists for this series from an import, move it to the final series directory */
	QString inbehdir = QString("%1/%2/beh").arg(n->cfg["incomingdir"]).arg(importid);
	QString outbehdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);

	msgs << "Checking for [" + inbehdir + "]";
	QDir bd(inbehdir);
	if (bd.exists()) {
		QString m;
		if (n->MakePath(outbehdir, m)) {
			QString systemstring = "mv -v " + inbehdir + "/* " + outbehdir + "/";
			msgs << n->SystemCommand(systemstring);

			qint64 behdirsize(0);
			uint behnumfiles(0);
			behdirsize = dirsize = n->GetDirByteSize(outbehdir);
			behnumfiles = nfiles = n->GetDirFileCount(outbehdir);
			QString sqlstring = QString("update %1_series set beh_size = :behdirsize, numfiles_beh = :behnumfiles where %1series_id = :seriesRowID").arg(dbModality.toLower());
			QSqlQuery q3;
			q3.prepare(sqlstring);
			q3.bindValue(":behdirsize", behdirsize);
			q3.bindValue(":behnumfiles", behnumfiles);
			q3.bindValue(":seriesRowID", seriesRowID);
			n->SQLQuery(q3, "InsertDICOMSeries", true);
		}
		else
			msgs << "Unable to create outbehdir ["+outbehdir+"] because of error ["+m+"]";
	}

	/* change the permissions to 777 so the webpage can read/write the directories */
	msgs << "About to change permissions on " + outdir;
	systemstring = "chmod -Rf 777 " + outdir;
	msgs << n->SystemCommand(systemstring);
	msgs << "Finished changing permissions on " + outdir;

	/* copy everything to the backup directory */
	QString backdir = QString("%1/%2/%3/%4").arg(n->cfg["backupdir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	QDir bda(backdir);
	if (!bda.exists()) {
		msgs << "Directory [" + backdir + "] does not exist. About to create it...";
		QString m;
		if (!n->MakePath(backdir, m))
			msgs << "Unable to create backdir [" + backdir + "] because of error [" + m + "]";
		else
			msgs << "Finished creating [$backdir]";
	}
	msgs << "About to copy to the backup directory";
	systemstring = QString("cp -R %1/* %5").arg(outdir).arg(backdir);
	msgs << n->SystemCommand(systemstring);
	msgs << "Finished copying to the backup directory";

	return 1;
}


/* ---------------------------------------------------------- */
/* --------- InsertParRec ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleImport::InsertParRec(int importid, QString file, QString &msg) {

	QStringList msgs;

	msgs << n->WriteLog("----- In InsertParRec($file, $importRowID) -----");

	/* import log variables */
	QString IL_modality_orig, IL_patientname_orig, IL_patientdob_orig, IL_patientsex_orig, IL_stationname_orig, IL_institution_orig, IL_studydatetime_orig, IL_seriesdatetime_orig, IL_studydesc_orig;
	double IL_patientage_orig;
	int IL_seriesnumber_orig;
	QString IL_modality_new, IL_patientname_new, IL_patientdob_new, IL_patientsex_new, IL_stationname_new, IL_institution_new, IL_studydatetime_new, IL_seriesdatetime_new, IL_studydesc_new, IL_seriesdesc_orig, IL_protocolname_orig;
	//double IL_patientage_new;
	//int IL_seriesnumber_new;
	QString IL_subject_uid;
	//int IL_study_num, IL_enrollmentid;
	QString IL_project_number;
	int IL_seriescreated(0), IL_studycreated(0), IL_subjectcreated(0), IL_familycreated(0), IL_enrollmentcreated(0), IL_overwrote_existing(0);

	QString familyRealUID;
	int familyRowID;

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
	//my $costcenter = "999999";
	QString StudyDescription;
	QString SeriesDescription;
	QString StationName = "PAR/REC";
	QString OperatorsName = "NotSpecified";
	QString PerformingPhysiciansName = "NotSpecified";
	QString InstitutionName = "NotSpecified";
	QString InstitutionAddress = "NotSpecified";
	//my $studydatetime;
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
	//int resolutionX(0), resolutionY(0);
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
		n->SQLQuery(q, "InsertParRec");
		if (q.size() > 0) {
			q.first();
			QString status = q.value("status").toString();
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
				//print "[$line]\n";
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
		msg = msgs.join("\n");
		return 0;
	}
	if (PatientName == "") {
		msgs << "PatientName (ID) is blank";
		msg = msgs.join("\n");
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
	n->SQLQuery(q, "InsertParRec");
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
			n->SQLQuery(q2, "InsertParRec");
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
		n->SQLQuery(q2, "InsertParRec");
		if (q2.size() > 0) {
			q2.first();
			subjectRowID = q2.value("subject_id").toInt();
			subjectRealUID = q2.value("uid").toString().toUpper().trimmed();

			msgs << QString("Found subject [%1, " + subjectRealUID + "] by searching for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]").arg(subjectRowID);
		}
		else {
			msgs << "Could not the find this subject. Searched for PatientID [" + PatientID + "] and alternate IDs [" + SQLIDs + "]";
			msg = msgs.join("\n");
			return 0;
		}
		/* insert the PatientID as an alternate UID */
		if (PatientID != "") {
			QSqlQuery q2;
			q2.prepare("insert ignore into subject_altuid (subject_id, altuid) values (:subjectid, :patientid)");
			q2.bindValue(":subjectid", subjectRowID);
			q2.bindValue(":patientid", PatientID);
			n->SQLQuery(q2, "InsertParRec");
		}
		IL_subjectcreated = 0;
	}

	n->WriteLog("subjectRealUID ["+subjectRealUID+"]");
	if (subjectRealUID == "") {
		msgs << "Error finding/creating subject. UID is blank";
		msg = msgs.join("\n");
		return 0;
	}

	/* check if the subject is part of a family, if not create a family for it */
	QSqlQuery q2;
	q2.prepare("select family_id from family_members where subject_id = :subjectid");
	q2.bindValue(":subjectid", subjectRowID);
	n->SQLQuery(q2, "InsertParRec");
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
			n->SQLQuery(q2, "InsertParRec", true);
			count = q2.size();
		} while (count > 0);

		/* create familyRowID if it doesn't exist */
		QSqlQuery q2;
		q2.prepare("insert into families (family_uid, family_createdate, family_name) values (:familyRealUID, now(), :familyname)");
		q2.bindValue(":familyuid", familyRealUID);
		q2.bindValue(":familyname", "Proband-" + subjectRealUID);
		n->SQLQuery(q2, "InsertParRec", true);
		familyRowID = q2.lastInsertId().toInt();

		q2.prepare("insert into family_members (family_id, subject_id, fm_createdate) values (:familyid, :subjectid, now())");
		q2.bindValue(":familyid", familyRowID);
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, "InsertParRec", true);

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
		n->SQLQuery(q2, "InsertParRec", true);
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
	n->SQLQuery(q2, "InsertParRec", true);
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
		n->SQLQuery(q2, "InsertParRec", true);
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
				n->SQLQuery(q2, "InsertParRec", true);
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
	n->SQLQuery(q2, "InsertParRec", true);
	if (q2.size() > 0) {
		while (q2.next()) {
			int study_id = q2.value("study_id").toInt();
			studynum = q2.value("study_num").toInt();
			int foundInstanceRowID = -1;

			/* check which instance this study is enrolled in */
			QSqlQuery q3;
			q3.prepare("select instance_id from projects where project_id = (select project_id from enrollment where enrollment_id = (select enrollment_id from studies where study_id = :studyid))");
			q3.bindValue(":studyid",study_id);
			n->SQLQuery(q3, "InsertParRec", true);
			if (q3.size() > 0) {
				q3.first();
				foundInstanceRowID = q3.value("instance_id").toInt();
				msgs << QString("Found instance ID [%1] comparing to import instance ID [%2]").arg(foundInstanceRowID).arg(importInstanceID);

				/* if the study already exists within the instance specified in the project, then update the existing study, otherwise create a new one */
				if (importInstanceID == 0) {
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
					n->SQLQuery(q4, "InsertParRec", true);

					IL_studycreated = 0;
					break;
				}
			}
		}
	}
	if (!studyFound) {
		msgs << "Study did not exist, creating new study";

		/* create studyRowID if it doesn't exist */
		q2.prepare("select max(a.study_num) 'study_num' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = :subjectid");
		q2.bindValue(":subjectid", subjectRowID);
		n->SQLQuery(q2, "InsertParRec", true);
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
		n->SQLQuery(q4, "InsertParRec", true);
		studyRowID = q4.lastInsertId().toInt();

		IL_studycreated = 1;
	}

	n->WriteLog("Going forward using the following: SubjectRowID [$subjectRowID] ProjectRowID [$projectRowID] EnrollmentRowID [$enrollmentRowID] StudyRowID [$studyRowID]");

	// ----- insert or update the series -----
	q2.prepare("select mrseries_id from mr_series where study_id = :studyid and series_num = :SeriesNumber");
	q2.bindValue(":studyid",studyRowID);
	q2.bindValue(":SeriesNumber",SeriesNumber);
	n->SQLQuery(q2, "InsertParRec", true);
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
		n->SQLQuery(q3, "InsertParRec", true);

		IL_seriescreated = 0;
	}
	else {
		// create seriesRowID if it doesn't exist
		QSqlQuery q3;
		q3.prepare("insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, numfiles, data_type, series_status, series_createdby, series_createdate) values (:studyRowID, :SeriesDateTime, :ProtocolName, :SequenceName, :SeriesNumber, :RepetitionTime, :EchoTime, :FlipAngle, :pixelX, :pixelY, :SliceThickness, :MagneticFieldStrength, :Rows, :Columns, :zsize, $boldreps, :numfiles, 'parrec', 'complete', 'import', now())");
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
		n->SQLQuery(q3, "InsertParRec", true);
		seriesRowID = q3.lastInsertId().toInt();
		IL_seriescreated = 0;
	}

	/* get the series info */
	series s(seriesRowID, "mr", n);

	msgs << n->WriteLog(QString("Values from GetDataPathFromSeriesID(%1, 'mr'): Path [%2] UID [%3] StudyNum [%4] SeriesNum [%5] StudyID [%6] SubjectID [%7]").arg(seriesRowID).arg(s.datapath).arg(s.uid).arg(s.studynum).arg(s.seriesnum).arg(s.studyid).arg(s.subjectid));

	// copy the file to the archive, update db info
	msgs << n->WriteLog(QString("seriesRowID [%1]").arg(seriesRowID));

	// create data directory if it doesn't already exist
	QString outdir = QString("%1/%2/%3/%4/parrec").arg(n->cfg["archivedir"]).arg(subjectRealUID).arg(studynum).arg(SeriesNumber);
	msgs << n->WriteLog("Outdir [" + outdir + "]");
	QString m;
	if (!n->MakePath(outdir, m))
		msgs << "Error creating outdir ["+outdir+"] because of error ["+m+"]";

	// move the files into the outdir
	//$report .= WriteLog("Moving " . $cfg{'incomingdir'} . "/$importID/$parfile -> $outdir/$parfile") . "\n";
	//$report .= WriteLog("Moving " . $cfg{'incomingdir'} . "/$importID/$recfile -> $outdir/$recfile") . "\n";
	n->MoveFile(parfile, outdir);
	n->MoveFile(recfile, outdir);
	//move($cfg{'incomingdir'} . "/$importID/$recfile","$outdir/$recfile");

	/* insert an import log record (.par file) */
	q2.prepare("insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$parfile', 'PARREC', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')");

	//$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	// insert an import log record (.rec file)
	//$sqlstring = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$recfile', 'PARREC', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')";
	//$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	// delete any rows older than 10 days from the import log
	//#$sqlstring = "delete from importlogs where importstartdate < date_sub(now(), interval 10 day)";
	//#$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	// get the size of the files and update the DB
	/* get the size of the dicom files and update the DB */
	qint64 dirsize = 0;
	dirsize = n->GetDirByteSize(outdir);

	/* update the database with the correct number of files/BOLD reps */
	if (Modality == "mr") {
		QString sqlstring = QString("update %1_series set series_size = :dirsize where %1series_id = :seriesid").arg(Modality.toLower());
		QSqlQuery q2;
		q2.prepare(sqlstring);
		q2.bindValue(":dirsize", dirsize);
		q2.bindValue(":seriesid", seriesRowID);
		n->SQLQuery(q2, "InsertDICOMSeries", true);
	}


	// change the permissions to 777 so the webpage can read/write the directories
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
			msgs << "Finished creating [$backdir]";
	}
	msgs << "About to copy to the backup directory";
	systemstring = QString("cp -R %1/* %5").arg(outdir).arg(backdir);
	msgs << n->SystemCommand(systemstring);
	msgs << "Finished copying to the backup directory";

	return true;
}


/* ---------------------------------------------------------- */
/* --------- CreateThumbnail -------------------------------- */
/* ---------------------------------------------------------- */
void moduleImport::CreateThumbnail(QString f, QString outdir) {

	QString outfile = outdir + "/thumb.png";

	QString systemstring = "convert -normalize " + f + " " + outfile;
	n->SystemCommand(systemstring);
	n->WriteLog("Ran ["+systemstring+"]");
}
