#include "moduleImportUploaded.h"


/* ---------------------------------------------------------- */
/* --------- moduleImportUploaded --------------------------- */
/* ---------------------------------------------------------- */
moduleImportUploaded::moduleImportUploaded(nidb *a)
{
	n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleImportUploaded -------------------------- */
/* ---------------------------------------------------------- */
moduleImportUploaded::~moduleImportUploaded()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleImportUploaded::Run() {
	n->WriteLog("Entering the importuploaded module");

	int ret(0);

	/* get list of pending uploads */
	QSqlQuery q;
	q.prepare("select * from import_requests where import_status = 'pending'");
	n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
	if (q.size() > 0) {
		while (q.next()) {
			n->ModuleRunningCheckIn();

			int importrequestid = q.value("importrequest_id").toInt();
			//int siteid = q.value("import_siteid").toInt();
			//int projectid = q.value("import_projectid").toInt();
			int anonymize = q.value("import_anonymize").toInt();
			QString datatype = q.value("import_datatype").toString().trimmed();
			QString modality = q.value("import_modality").toString().trimmed();
			//int fileisseries = q.value("import_fileisseries").toInt();
			QString importstatus = q.value("import_status").toString().trimmed();

			/* if somehow the status was changed elsewhere, don't attempt to process these statuses */
			if (importstatus != "pending")
				continue;

			QSqlQuery q2;
			q2.prepare("update import_requests set import_status = 'receiving', import_startdate = now() where importrequest_id = :importrequestid");
			q2.bindValue(":importrequestid",importrequestid);
			n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);

			QString uploaddir = QString("%1/%2").arg(n->cfg["uploadeddir"]).arg(importrequestid);
			QString outdir = QString("%1/%2").arg(n->cfg["incomingdir"]).arg(importrequestid);
			QString m;
			if (!n->MakePath(outdir, m)) {
				n->WriteLog("Unable to create outdir [" + outdir + "] because of error [" + m + "]");
				continue;
			}

			if (datatype == "")
				datatype = "dicom";

			n->WriteLog(QString("Datatype for %1 is [%2]").arg(importrequestid).arg(datatype));

			/* ----- get list of files in directory ----- */
			QStringList files;
			files = n->FindAllFiles(uploaddir,"*");
			if (files.size() < 1) {
				SetImportRequestStatus(importrequestid, "error", "No files found in [" + uploaddir + "]");
				continue;
			}

			/* special procedures for each datatype */
			if ((datatype == "dicom") || (datatype == "parrec")) {
				n->WriteLog("Working on [" + uploaddir + "]");

				/* go through the files */
				foreach (QString file, files) {

					if (n->IsDICOMFile(file)) {
						/* anonymize, replace project and site, rename, and dump to incoming */
						n->WriteLog("[" + file + "] is a DICOM file");
						PrepareAndMoveDICOM(file, outdir, anonymize);
					}
					else if (file.endsWith(".par")) {
						PrepareAndMovePARREC(file, outdir);
					}
					else if (file.endsWith(".rec")) {
						/* .par/.rec are pairs, and only the .par contains meta-info, so leave the .rec alone */
					}
					else {
						n->WriteLog("[" + file + "] is NOT a DICOM file");

						QString tmppath = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
						QString systemstring;
						if (file.endsWith(".tar.gz")) systemstring = "tar -xzf '" + file + "' --warning=no-timestamp -C "+tmppath;
						else if (file.endsWith(".gz")) systemstring = "gunzip -c '" + file + "' -C "+tmppath;
						else if (file.endsWith(".z")) systemstring = "gunzip -c '" + file + "' -C "+tmppath;
						else if (file.endsWith(".Z")) systemstring = "gunzip -c '" + file + "' -C "+tmppath;
						else if (file.endsWith(".zip")) systemstring = "unzip '" + file + "' -d "+tmppath;
						else if (file.endsWith(".tar.bz2")) systemstring = "tar -xjf '" + file + "' --warning=no-timestamp -C "+tmppath;
						else if (file.endsWith(".tar")) systemstring = "tar -xf '" + file + "' -C "+tmppath;

						if (systemstring == "") {
							n->WriteLog("Did not know how to handle this file [" + file + "]");
						}
						else {
							QString m;
							if (!n->MakePath(tmppath, m)) {
								n->WriteLog("Unable to create tmpdir ["+tmppath+"] because of error ["+m+"]");
								continue;
							}

							/* find all files in the /tmp dir and (anonymize,replace fields, rename, and dump to incoming) */
							QStringList tmpfiles = n->FindAllFiles(tmppath, "*", true);

							foreach (QString tf, tmpfiles) {
								PrepareAndMoveDICOM(tf, outdir, anonymize);
							}

							/* delete the tmp directory */
							if (!n->RemoveDir(tmppath,m))
								n->WriteLog("Unable to remove tmpdir ["+tmppath+"] because of error ["+m+"]");
						}
					}
				}

				/* move the beh directory if it exists */
				QString behdir = uploaddir + "/beh";
				QDir bd(behdir);
				if (bd.exists()) {
					QString systemstring = QString("mv -v %1 %2/").arg(behdir).arg(outdir);
					n->SystemCommand(systemstring);
				}
			}
			else if ((datatype == "eeg") || (datatype == "et")) {
				n->WriteLog("Encountered "+datatype+" import");
				/* ----- get list of files in directory ----- */

				n->WriteLog("Should see me...");
				/* unzip anything in the directory before parsing it */
				QString systemstringbase = "cd " + uploaddir + "; ";
				n->SystemCommand(systemstringbase + "unzip *.zip; rm *.zip");
				n->SystemCommand(systemstringbase + "tar -xzf *.tar.gz --warning=no-timestamp; rm *.tar.gz");
				n->SystemCommand(systemstringbase + "gunzip *.gz; rm *.gz");
				n->SystemCommand(systemstringbase + "bunzip2 *.bz2; rm *.bz2");

				/* go through the files */
				foreach (QString file, files) {
					QString systemstring = QString("touch %1; mv %1 %2/%3/%4").arg(file).arg(n->cfg["incomingdir"]).arg(importrequestid).arg(file);
					n->SystemCommand(systemstring);
				}
				n->WriteLog("Finished iterating through the files");
			}
			else {
				n->WriteLog("Datatype not recognized ["+datatype+"]");
			}

			SetImportRequestStatus(importrequestid, "received");

			/* delete the uploaded directory */
			n->WriteLog("Attempting to remove ["+uploaddir+"]");
			if (!n->RemoveDir(uploaddir, m))
				n->WriteLog("Unable to remove directory [" + uploaddir + "] because error [" + m + "]");
		}
		ret = 1;
	}
	else {
		n->WriteLog("No rows in import_requests found");
		ret = 0;
	}

	return ret;
}


/* ---------------------------------------------------------- */
/* --------- PrepareAndMoveDICOM ---------------------------- */
/* ---------------------------------------------------------- */
bool moduleImportUploaded::PrepareAndMoveDICOM(QString filepath, QString outdir, bool anonymize) {

	n->WriteLog("PrepareAndMoveDICOM(" + filepath + "," + outdir + ")");

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

		n->AnonymizeDICOMFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags, false);
	}
	/* if the filename exists in the outgoing directory, prepend some junk to it, since the filename is unimportant
	   some directories have all their files named IM0001.dcm ..... so, inevitably, something will get overwrtten, which is bad */
	QString filename = QFileInfo(filepath).fileName();
	QString newfilename = n->GenerateRandomString(15) + filename;

	QString systemstring = QString("touch %1; mv %1 %2/%3").arg(filepath).arg(outdir).arg(newfilename);
	n->SystemCommand(systemstring, true);

	return true;
}


/* ---------------------------------------------------------- */
/* --------- PrepareAndMovePARREC --------------------------- */
/* ---------------------------------------------------------- */
bool moduleImportUploaded::PrepareAndMovePARREC(QString filepath, QString outdir) {

	n->WriteLog("PrepareAndMovePARREC(" + filepath + "," + outdir + ")");

	/* if the filename exists in the outgoing directory, prepend some junk to it, since the filename is unimportant
	   some directories have all their files named IM0001.dcm ..... so, inevitably, something will get overwrtten, which is bad */
	QString filename = QFileInfo(filepath).fileName();
	QString newparfilename = n->GenerateRandomString(15) + filename;
	QString newrecfilename = newparfilename.replace(".par", ".rec", Qt::CaseInsensitive);;
	QString oldparfilepath = filepath;
	QString oldrecfilepath = oldparfilepath.replace(".par", ".rec", Qt::CaseInsensitive);

	n->SystemCommand(QString("touch %1; mv %1 %2/%3").arg(oldparfilepath).arg(outdir).arg(newparfilename), true);
	n->SystemCommand(QString("touch %1; mv %1 %2/%3").arg(oldrecfilepath).arg(outdir).arg(newrecfilename), true);

	return true;
}


/* ---------------------------------------------------------- */
/* --------- SetImportRequestStatus ------------------------- */
/* ---------------------------------------------------------- */
bool moduleImportUploaded::SetImportRequestStatus(int importid, QString status, QString msg) {

	n->WriteLog("Setting status to ["+status+"]");

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
