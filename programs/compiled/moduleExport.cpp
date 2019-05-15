#include "moduleExport.h"
#include <QDebug>
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- moduleExport ----------------------------------- */
/* ---------------------------------------------------------- */
moduleExport::moduleExport(nidb *a)
{
	n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleFileIO ---------------------------------- */
/* ---------------------------------------------------------- */
moduleExport::~moduleExport()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleExport::Run() {
	qDebug() << "Entering the export module";

	/* get list of things to delete */
	QSqlQuery q("select * from exports where status = 'submitted'");
	n->SQLQuery(q, "Run", true);

	if (q.size() > 0) {
		int i = 0;
		while (q.next()) {
			n->ModuleRunningCheckIn();
			if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }
			bool found = false;
			QString msg;
			i++;

			int exportid = q.value("export_id").toInt();
			QString username = q.value("username").toString().trimmed();
			QString exporttype = q.value("destinationtype").toString().trimmed();
			bool downloadimaging = q.value("download_imaging").toBool();
			bool downloadbeh = q.value("download_beh").toBool();
			bool downloadqc = q.value("download_qc").toBool();
			QString nfsdir = q.value("nfsdir").toString().trimmed();
			QString filetype = q.value("filetype").toString().trimmed();
			QString dirformat = q.value("dirformat").toString().trimmed();
			int preserveseries = q.value("do_preserveseries").toInt();
			bool gzip = q.value("do_gzip").toBool();
			int anonymize = q.value("anonymize").toInt();
			QString behformat = q.value("beh_format").toString().trimmed();
			QString behdirrootname = q.value("beh_dirrootname").toString().trimmed();
			QString behdirseriesname = q.value("beh_dirseriesname").toString().trimmed();
			QString remoteftpusername = q.value("remoteftp_username").toString().trimmed();
			QString remoteftppassword = q.value("remoteftp_password").toString().trimmed();
			QString remoteftpserver = q.value("remoteftp_server").toString().trimmed();
			QString remoteftpport = q.value("remoteftp_port").toString().trimmed();
			QString remoteftppath = q.value("remoteftp_path").toString().trimmed();
			int remotenidbconnid = q.value("remotenidb_connectionid").toInt();
			int publicdownloadid = q.value("publicdownloadid").toInt();
			QString bidsreadme = q.value("bidsreadme").toString().trimmed();

			remoteNiDBConnection conn(remotenidbconnid, n);

			/* get the current status of this fileio request, make sure no one else is processing it, and mark it as being processed if not */
			QString status = GetExportStatus(exportid);
			if (status == "submitted") {
				/* set the status. if something is wrong, skip this request */
				if (!SetExportStatus(exportid, "processing")) {
					n->WriteLog(QString("Unable to set export status to [%1]").arg(status));
					continue;
				}
			}
			else {
				/* skip this IO request... the status was changed outside of this instance of the program */
				n->WriteLog(QString("The status for this export [%1] has been changed to [%2]. Skipping.").arg(exportid).arg(status));
				continue;
			}

			n->WriteLog(QString(" ----- Export operation (%1 of %2) ----- ").arg(i).arg(q.size()));
			QString log;

			if (exporttype == "web") {
				found = ExportLocal(exportid, exporttype, "", 0, downloadimaging, downloadbeh, downloadqc, filetype, dirformat, preserveseries, gzip, anonymize, behformat, behdirrootname, behdirseriesname, status, log);
			}
			else if (exporttype == "publicdownload") {
				found = ExportLocal(exportid, exporttype, "", publicdownloadid, downloadimaging, downloadbeh, downloadqc, filetype, dirformat, preserveseries, gzip, anonymize, behformat, behdirrootname, behdirseriesname, status, log);
			}
			else if (exporttype == "nfs") {
				found = ExportLocal(exportid, exporttype, nfsdir, 0, downloadimaging, downloadbeh, downloadqc, filetype, dirformat, preserveseries, gzip, anonymize, behformat, behdirrootname, behdirseriesname, status, log);
			}
			else if (exporttype == "localftp") {
				found = ExportLocal(exportid, exporttype, nfsdir, 0, downloadimaging, downloadbeh, downloadqc, filetype, dirformat, preserveseries, gzip, anonymize, behformat, behdirrootname, behdirseriesname, status, log);
			}
			else {
				n->WriteLog(QString("Unknown export type [%1]").arg(exporttype));
			}
		}
		n->WriteLog("Finished performing exports");
	}
	else {
		n->WriteLog("Nothing to do");
	}

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- GetExportStatus -------------------------------- */
/* ---------------------------------------------------------- */
QString moduleExport::GetExportStatus(int exportid) {
	QSqlQuery q;
	q.prepare("select status from exports where export_id = :id");
	q.bindValue(":id", exportid);
	n->SQLQuery(q, "GetExportStatus", true);
	q.first();
	QString status = q.value("status").toString();
	return status;
}


/* ---------------------------------------------------------- */
/* --------- SetExportStatus -------------------------------- */
/* ---------------------------------------------------------- */
bool moduleExport::SetExportStatus(int exportid, QString status, QString msg) {

	if (((status == "pending") || (status == "deleting") || (status == "complete") || (status == "error") || (status == "processing") || (status == "cancelled") || (status == "canceled")) && (exportid > 0)) {
		if (msg.trimmed() == "") {
			QSqlQuery q;
			q.prepare("update exports set status = :status where export_id = :id");
			q.bindValue(":id", exportid);
			q.bindValue(":status", status);
			n->SQLQuery(q, "SetExportStatus", true);
		}
		else {
			QSqlQuery q;
			q.prepare("update exports set status = :status, log = :msg where export_id = :id");
			q.bindValue(":id", exportid);
			q.bindValue(":msg", msg);
			q.bindValue(":status", status);
			n->SQLQuery(q, "SetExportStatus", true);
		}
		return true;
	}
	else {
		return false;
	}
}


/* ---------------------------------------------------------- */
/* --------- GetExportSeriesList ---------------------------- */
/* ---------------------------------------------------------- */
bool moduleExport::GetExportSeriesList(int exportid) {

	QSqlQuery q;
	q.prepare("select * from exportseries where export_id = :exportid");
	q.bindValue(":exportid",exportid);
	n->SQLQuery(q,"GetExportSeriesList",true);
	if (q.size() > 0) {
		while (q.next()) {
			QString modality = q.value("modality").toString().toLower();
			int seriesid = q.value("series_id").toInt();
			int exportseriesid = q.value("exportseries_id").toInt();
			QString status = q.value("status").toString();


			QSqlQuery q2;
			q2.prepare(QString("select a.*, b.*, c.enrollment_id, d.project_name, e.uid, e.subject_id from %1_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.%1series_id = :seriesid order by uid, study_num, series_num").arg(modality));
			q.bindValue(":seriesid",seriesid);
			n->SQLQuery(q2,"GetExportSeriesList",true);

			if (q2.size() > 0) {
				while (q2.next()) {
					QString uid = q2.value("uid").toString();
					int subjectid = q2.value("subject_id").toInt();
					int studynum = q2.value("study_num").toInt();
					int studyid = q2.value("study_id").toInt();
					QString studydatetime = q2.value("study_datetime").toDateTime().toString("yyyyMMdd_HHmmss");
					int seriesnum = q2.value("series_num").toInt();
					int seriessize = q2.value("series_size").toInt();
					QString seriesnotes = q2.value("series_notes").toString();
					QString seriesdesc = q2.value("series_desc").toString();
					QString seriesaltdesc = q2.value("series_altdesc").toString();
					QString projectname = q2.value("project_name").toString();
					QString studyaltid = q2.value("study_alternateid").toString();
					QString studytype = q2.value("study_type").toString();
					QString datatype = q2.value("data_type").toString();
					if (datatype == "") // if datatype (dicom, nifti, parrec) is blank because its not MR, then the datatype is the modality
						datatype = modality;
					int numfiles = q2.value("numfiles").toInt();
					if (modality != "mr")
						numfiles = q2.value("series_numfiles").toInt();
					int numfilesbeh = q2.value("numfiles_beh").toInt();
					int enrollmentid = q2.value("enrollment_id").toInt();

					QString datadir = QString("%1/%2/%3/%4/%5").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum).arg(datatype);
					QString behdir = QString("%1/%2/%3/%4/beh").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);
					QString qcdir = QString("%1/%2/%3/%4/qa").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);

					s[uid][studynum][seriesnum]["exportseriesid"] = QString("%1").arg(exportseriesid);
					s[uid][studynum][seriesnum]["seriesid"] = QString("%1").arg(seriesid);
					s[uid][studynum][seriesnum]["subjectid"] = QString("%1").arg(subjectid);
					s[uid][studynum][seriesnum]["studyid"] = QString("%1").arg(studyid);
					s[uid][studynum][seriesnum]["studydatetime"] = studydatetime;
					s[uid][studynum][seriesnum]["modality"] = modality;
					s[uid][studynum][seriesnum]["seriessize"] = QString("%1").arg(seriessize);
					s[uid][studynum][seriesnum]["seriesnotes"] = seriesnotes;
					s[uid][studynum][seriesnum]["seriesdesc"] = seriesdesc;
					s[uid][studynum][seriesnum]["seriesaltdesc"] = seriesaltdesc;
					s[uid][studynum][seriesnum]["numfilesbeh"] = QString("%1").arg(numfilesbeh);
					s[uid][studynum][seriesnum]["numfiles"] = QString("%1").arg(numfiles);
					s[uid][studynum][seriesnum]["projectname"] = projectname;
					s[uid][studynum][seriesnum]["studyaltid"] = studyaltid;
					s[uid][studynum][seriesnum]["studytype"] = studytype;
					s[uid][studynum][seriesnum]["datatype"] = datatype;
					s[uid][studynum][seriesnum]["datadir"] = datadir;
					s[uid][studynum][seriesnum]["behdir"] = behdir;
					s[uid][studynum][seriesnum]["qcdir"] = qcdir;

					/* Check if source data directories exist */
					if (QDir(datadir).exists()) {
						s[uid][studynum][seriesnum]["datadirexists"] = "1";

						if (QDir(datadir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0)
							s[uid][studynum][seriesnum]["datadirempty"] = "1";
						else
							s[uid][studynum][seriesnum]["datadirempty"] = "0";
					}
					else
						s[uid][studynum][seriesnum]["datadirexists"] = "0";

					if (QDir(behdir).exists()) {
						s[uid][studynum][seriesnum]["behdirexists"] = "1";

						if (QDir(behdir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0)
							s[uid][studynum][seriesnum]["behdirempty"] = "1";
						else
							s[uid][studynum][seriesnum]["behdirempty"] = "0";
					}
					else
						s[uid][studynum][seriesnum]["behdirexists"] = "0";

					if (QDir(qcdir).exists()) {
						s[uid][studynum][seriesnum]["qcdirexists"] = "1";

						if (QDir(qcdir).entryInfoList(QDir::NoDotAndDotDot|QDir::AllEntries).count() == 0)
							s[uid][studynum][seriesnum]["qcdirempty"] = "1";
						else
							s[uid][studynum][seriesnum]["qcdirempty"] = "0";
					}
					else
						s[uid][studynum][seriesnum]["qcdirexists"] = "0";

					// get any alternate IDs
					QStringList altuids;
					QString primaryaltuid;

					QSqlQuery q3;
					q3.prepare("select altuid, isprimary from subject_altuid where enrollment_id = :enrollmentid and subject_id = :subjectid");
					q3.bindValue(":enrollmentid",enrollmentid);
					q3.bindValue(":subjectid",subjectid);
					n->SQLQuery(q3,"GetExportSeriesList",true);
					if (q3.size() > 0) {
						while (q3.next()) {
							if (q3.value("isprimary").toBool())
								primaryaltuid = q3.value("altuid").toString();

							altuids << q3.value("altuid").toString();
						}
						s[uid][studynum][seriesnum]["primaryaltuid"] = primaryaltuid;
						s[uid][studynum][seriesnum]["altuids"] = altuids.join(",");
					}
				}
			}
			else {
				n->WriteLog(QString("No rows found for this seriesid [%1] and modality [%2]").arg(seriesid).arg(modality));
			}
		}
	}
	else {
		n->WriteLog(QString("No series rows found for this exportid [%1]").arg(exportid));
	}

	return true;
}


/* ---------------------------------------------------------- */
/* --------- ExportLocal ------------------------------------ */
/* ---------------------------------------------------------- */
bool moduleExport::ExportLocal(int exportid, QString exporttype, QString nfsdir, int publicdownloadid, bool downloadimaging, bool downloadbeh, bool downloadqc, QString filetype, QString dirformat, int preserveseries, bool gzip, int anonlevel, QString behformat, QString behdirrootname, QString behdirseriesname, QString &status, QString &msg) {

	QStringList msgs;
	if (!GetExportSeriesList(exportid)) {
		msg = "Unable to get a series list";
		return false;
	}

	QString tmpexportdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(20);

	QString exportstatus = "complete";
	int laststudynum = 0;
	QString newseriesnum = "1";

	/* iterate through the UIDs */
	for(QMap<QString, QMap<int, QMap<int, QMap<QString, QString>>>>::iterator a = s.begin(); a != s.end(); ++a) {
		QString uid = a.key();

		/* iterate through the studynums */
		for(QMap<int, QMap<int, QMap<QString, QString>>>::iterator b = s[uid].begin(); b != s[uid].end(); ++b) {
			int studynum = b.key();

			/* iterate through the seriesnums */
			for(QMap<int, QMap<QString, QString>>::iterator c = s[uid][studynum].begin(); c != s[uid][studynum].end(); ++c) {
				int seriesnum = c.key();

				int exportseriesid = s[uid][studynum][seriesnum]["exportseriesid"].toInt();
				QSqlQuery q;
				q.prepare("update exportseries set status = 'processing' where exportseries_id = :exportseriesid");
				q.bindValue(":exportseriesid",exportseriesid);
				n->SQLQuery(q,"ExportLocal",true);

				QString seriesstatus = "complete";
				QString statusmessage;

				//int subjectid = s[uid][studynum][seriesnum]["subjectid"].toInt();
				int seriesid = s[uid][studynum][seriesnum]["seriesid"].toInt();
				QString primaryaltuid = s[uid][studynum][seriesnum]["primaryaltuid"];
				QString altuids = s[uid][studynum][seriesnum]["altuids"];
				QString projectname = s[uid][studynum][seriesnum]["projectname"];
				//int studyid = s[uid][studynum][seriesnum]["studyid"].toInt();
				QString studytype = s[uid][studynum][seriesnum]["studytype"];
				QString studyaltid = s[uid][studynum][seriesnum]["studyaltid"];
				QString modality = s[uid][studynum][seriesnum]["modality"];
				//int seriessize = s[uid][studynum][seriesnum]["seriessize"].toInt();
				QString seriesdesc = s[uid][studynum][seriesnum]["seriesdesc"];
				QString datatype = s[uid][studynum][seriesnum]["datatype"];
				QString indir = s[uid][studynum][seriesnum]["datadir"];
				QString behindir = s[uid][studynum][seriesnum]["behdir"];
				QString qcindir = s[uid][studynum][seriesnum]["qcdir"];
				int numfiles = s[uid][studynum][seriesnum]["numfiles"].toInt();
				bool datadirexists = s[uid][studynum][seriesnum]["datadirexists"].toInt();
				bool behdirexists = s[uid][studynum][seriesnum]["behdirexists"].toInt();
				bool qcdirexists = s[uid][studynum][seriesnum]["qcdirexists"].toInt();
				bool datadirempty = s[uid][studynum][seriesnum]["datadirempty"].toInt();
				bool behdirempty = s[uid][studynum][seriesnum]["behdirempty"].toInt();
				bool qcdirempty = s[uid][studynum][seriesnum]["qcdirempty"].toInt();

				QString subjectdir;
				if (dirformat == "shortid")
					subjectdir = QString("%1%2").arg(uid).arg(studynum);
				else if (dirformat == "shortstudyid")
					subjectdir = QString("%1/%2").arg(uid).arg(studynum);
				else if (dirformat == "altuid")
					if (primaryaltuid == "")
						subjectdir = uid;
					else
						subjectdir = primaryaltuid;
				else
					subjectdir = QString("%1%2").arg(uid).arg(studynum);

				/* renumber series if necessary */
				switch (preserveseries) {
				case 0:
					    if (laststudynum != studynum)
							newseriesnum = "1";
						else
							newseriesnum = QString("%1").arg(newseriesnum.toInt() + 1);
					break;
				case 1:
					    newseriesnum = QString("%1").arg(seriesnum);
					break;
				case 2:
					QString seriesdir = seriesdesc;
					seriesdir.replace(QRegularExpression("[^a-zA-Z0-9_-]"),"_");
					newseriesnum = QString("%1_%2").arg(seriesnum).arg(seriesdir);
				}

				/* determine the base directory structure format */
				n->WriteLog("Series number [$seriesnum] --> [$newseriesnum]");
				msgs << QString("%1 - Series number [%2] --> [%3]").arg(subjectdir).arg(seriesnum).arg(newseriesnum);
				QString rootoutdir;
				if (exporttype == "nfs")
					rootoutdir = QString("%1%2/%3").arg(n->cfg["mountdir"]).arg(nfsdir).arg(subjectdir);
				else if ((exporttype == "web") || (exporttype == "publicdownload"))
					rootoutdir = QString("%1/%2").arg(tmpexportdir).arg(subjectdir);
				else if (exporttype == "localftp")
					rootoutdir = QString("%1/NiDB-%2/%3").arg(n->cfg["ftpdir"]).arg(exportid).arg(subjectdir);
				else
					rootoutdir = QString("%1/%2").arg(tmpexportdir).arg(subjectdir);

				/* make the output directory */
				QDir d;
				if (d.mkpath(rootoutdir)) {
					n->WriteLog(QString("Created rootoutdir [%1]").arg(rootoutdir));
					msgs << "Created rootoutdir [" + rootoutdir + "]. Writing data to directory";
				}
				else {
					seriesstatus = "error";
					exportstatus = "error";
					n->WriteLog("ERROR unable to create rootoutdir [" + rootoutdir + "]");
					msgs << "Unable to create output directory [" + rootoutdir + "]";
					statusmessage = "Unable to create rootoutdir [" + rootoutdir + "]";
				}

				QString outdir = QString("%1/%2").arg(rootoutdir).arg(newseriesnum);
				QString qcoutdir = QString("%1/qa").arg(outdir);
				QString behoutdir;
				if (behformat == "behroot")
					behoutdir = rootoutdir;
				else if (behformat == "behrootdir")
					behoutdir = rootoutdir + "/" + behdirrootname;
				else if (behformat == "behseries")
					behoutdir = outdir;
				else if (behformat == "behseriesdir")
					behoutdir = outdir + "/" + behdirseriesname;
				else
					behoutdir = rootoutdir;

				n->WriteLog("Destination is '$exporttype'. rootoutdir [$rootoutdir], outdir [$outdir], qcoutdir [$qcoutdir], behoutdir [$behoutdir]");

				if (downloadimaging) {
					if (numfiles > 0) {
						if (datadirexists) {
							if (!datadirempty) {
								// output the correct file type
								if ((modality != "mr") || (filetype == "dicom") || ((datatype != "dicom") && (datatype != "parrec"))) {
									// use rsync instead of cp because of the number of files limit
									QString systemstring = QString("rsync %1/* %2/").arg(indir).arg(outdir);
									n->WriteLog(n->SystemCommand(systemstring, true));
									msgs << "Copying raw data from [" + indir + "] to [" + outdir + "]";
								}
								else if (filetype == "qc") {
									/* copy only the qc data */
									QString systemstring = QString("cp -R %1/qa %2").arg(indir).arg(qcoutdir);
									n->WriteLog(n->SystemCommand(systemstring, true));
									msgs << "Copying QC data from [" + indir + "/qa] to [" + qcoutdir + "]";

									/* write the series info to a text file */
									QString seriesfile = outdir + "seriesinfo.txt";
									QFile f(seriesfile);
									if (f.open(QIODevice::WriteOnly | QIODevice::Text)) {
										QTextStream fs(&f);
										QSqlQuery q;
										q.prepare("select * from mr_series where mrseries_id = :seriesid");
										q.bindValue(":seriesid",seriesid);
										n->SQLQuery(q,"ExportLocal",true);
										if (q.size() > 0) {
											QSqlRecord r(q.record());
											QStringList fields;
											for (int v = 0; v < r.count(); ++v)
												fields << r.fieldName(v);

											q.first();
											foreach (QString field, fields) {
												fs << QString("%1: %2").arg(field).arg(q.value(field).toString());
											}
										}
										f.close();
									}
									else {
										msgs << "Unable to create series info file [" + seriesfile + "]";
									}
								}
								else {
									QString tmpdir = n->cfg["tmpdir"] + "/" + n->GenerateRandomString(10);
									QString m1;
									if (n->MakePath(tmpdir, m1)) {
										QString m2;
										int numfilesconv(0), numfilesrenamed(0);
										if (!n->ConvertDicom(filetype, indir, tmpdir, gzip, uid, studynum, seriesnum, datatype, numfilesconv, numfilesrenamed, m2))
											msgs << "Error converting files [" + m2 + "]";
										n->WriteLog("About to copy files from " + tmpdir + " to " + outdir);
										QString systemstring = "rsync " + tmpdir + "/* " + outdir + "/";
										n->WriteLog(n->SystemCommand(systemstring, true));
										n->WriteLog("Done copying files...");
										QString m3;
										if (!n->RemoveDir(tmpdir, m3))
											msgs << "Error [" + m3 + "] while removing path [" + tmpdir + "]";
										msgs << "Converted DICOM/parrec data into " + filetype + " using tmpdir [" + tmpdir + "]. Final directory [" + outdir + "]";
									}
									else
										msgs << "Error [" + m1 + "] while creating path [" + tmpdir + "]";
								}
							}
							else {
								seriesstatus = "error";
								exportstatus = "error";
								n->WriteLog("ERROR [" + indir + "] is empty");
								msgs << "Directory [" + indir + "] is empty";
								statusmessage = "Directory [" + indir + "] is empty. Data missing from disk";
							}
						}
						else {
							seriesstatus = "error";
							exportstatus = "error";
							n->WriteLog("ERROR indir [" + indir + "] does not exist");
							msgs << "Directory [" + indir + "] does not exist";
							statusmessage = "Directory [" + indir + "] does not exist. Data missing from disk";
						}
					}
					else {
						n->WriteLog("numfiles is 0");
						msgs << "Series contains 0 files";
					}
				}

				/* copy the beh data */
				if (downloadbeh) {
					if (behdirexists) {
						QString m;
						if (n->MakePath(behoutdir, m)) {
							QString systemstring = "cp -R " + behindir + "/* " + behoutdir;
							n->WriteLog(n->SystemCommand(systemstring, true));
							systemstring = "chmod -Rf 777 " + behoutdir;
							n->WriteLog(n->SystemCommand(systemstring, true));
							msgs << "Copying behavioral data from [$behindir] to [$behoutdir]\n";
						}
						else
							msgs << "Error [" + m + "] while creating path [" + behoutdir + "]";
					}
					else {
						n->WriteLog("WARNING behindir [" + behindir + "] does not exist");
						msgs << "Directory [" + behindir + "] does not exist";
					}
				}
				else {
					n->WriteLog("Not downloading beh data");
					msgs << "Not downloading beh data\n";
				}

				/* copy the QC data */
				if (downloadqc) {
					if (qcdirexists) {
						QString m;
						if (n->MakePath(qcoutdir, m)) {
							QString systemstring = "cp -R " + qcindir + "/* " + qcoutdir;
							n->WriteLog(n->SystemCommand(systemstring, true));
							systemstring = "chmod -Rf 777 " + qcoutdir;
							n->WriteLog(n->SystemCommand(systemstring, true));
							msgs << "Copying QC data from [$qcindir] to [$qcoutdir]";
						}
						else
							msgs << "Error [" + m + "] while creating path [" + behoutdir + "]";
					}
					else {
						seriesstatus = "error";
						exportstatus = "error";
						n->WriteLog("ERROR qcindir [" + qcindir + "] does not exist");
						msgs << "Directory [" + qcindir + "] does not exist";
						statusmessage = "Directory [" + qcindir + "] does not exist";
					}
				}

				/* give full permissions to the files that were downloaded */
				if (exporttype == "nfs") {
					QString systemstring = "chmod -Rf 777 " + outdir;
					n->WriteLog(n->SystemCommand(systemstring, true));

					// change the modification/access timestamp to the current date/time
					//find sub { #print $File::Find::name;
					//	utime(time,time,$File::Find::name) }, "$outdir";
				}

				if (filetype == "dicom") {
					AnonymizeDir(outdir,anonlevel,"Anonymous","Anonymous");
				}

				SetExportStatus(exportseriesid,seriesstatus,statusmessage);
				msgs << QString("Series [%1%2-%3 (%4)] complete").arg(uid).arg(studynum).arg(seriesnum).arg(seriesdesc);

				laststudynum = studynum;
			}
		}
	}

	/* extra steps for web download */
	if (exporttype == "web") {
		QString zipfile = QString("%1/NIDB-%2.zip").arg(n->cfg["webdownloaddir"]).arg(exportid);
		QString outdir;
		n->WriteLog("Final zip file will be [" + zipfile + "]");
		n->WriteLog("tmpexportdir: [" + tmpexportdir + "]");
		outdir = tmpexportdir;

		QString pwd = QDir::currentPath();
		n->WriteLog("Current directory is [" + pwd + "], changing directory to [" + outdir + "]");
		QDir d;
		if (d.exists(outdir)) {
			QString systemstring;
			QDir::setCurrent(outdir);
			if (QFile::exists(zipfile))
				systemstring = "zip -1grv " + zipfile + " .";
			else
				systemstring = "zip -1rv " + zipfile + " .";
			n->WriteLog("Beginning zipping...");
			n->WriteLog(n->SystemCommand(systemstring, true));
			n->WriteLog("Finished zipping...");
			n->WriteLog("Changing directory to [" + pwd + "]");
			QDir::setCurrent(pwd);
		}
		else {
			n->WriteLog("outdir [" + outdir + "] does not exist");
		}

		/* delete the tmp dir, if it exists */
		if (d.exists(tmpexportdir)) {
			n->WriteLog("Temporary export dir [" + tmpexportdir + "] exists and will be deleted");
			QString m;
			if (!n->RemoveDir(tmpexportdir, m))
				msgs << "Error [" + m + "] removing directory [" + tmpexportdir + "]";
		}
		QFile file;
		if (file.exists(zipfile))
			msgs << "Created .zip file [" + zipfile + "]";
		else
			msgs << "Unable to create [" + zipfile + "]";
	}

	/* extra steps for publicdownload */
	if (exporttype == "publicdownload") {

		QSqlQuery q;
		q.prepare("select * from public_downloads where pd_id = :publicdownloadid");
		q.bindValue(":publicdownloadid",publicdownloadid);
		n->SQLQuery(q,"ExportLocal",true);
		if (q.size() > 0) {
			q.first();
			int expiredays = q.value("pd_expiredays").toInt();

			QString filename = QString("NiDB-%1.zip").arg(exportid);
			QString zipfile = n->cfg["webdownloaddir"] + "/" + filename;
			QString outdir = tmpexportdir;

			QString pwd = QDir::currentPath();
			n->WriteLog("Current directory is [" + pwd + "], changing directory to [" + outdir + "]");
			QDir d;
			if (d.exists(outdir)) {
				QString systemstring;
				QDir::setCurrent(outdir);
				if (QFile::exists(zipfile))
					systemstring = "zip -1grq " + zipfile + " .";
				else
					systemstring = "zip -1rq " + zipfile + " .";
				n->WriteLog(n->SystemCommand(systemstring, true));
				n->WriteLog("Changing directory to [" + pwd + "]");
				QDir::setCurrent(pwd);
				systemstring = "unzip -vl " + zipfile;
				QString filecontents = n->SystemCommand(systemstring, true);
				QStringList lines = filecontents.split("\n");
				QString lastline = lines.last().trimmed();
				QStringList parts = lastline.split(QRegExp("\\s+"), QString::SkipEmptyParts); /* split on whitespace */
				int unzippedsize = parts[0].toInt();
				int zippedsize = parts[1].toInt();

				QSqlQuery q2;
				q2.prepare("update public_downloads set pd_createdate = now(), pd_expiredate = date_add(now(), interval :expiredays day), pd_zippedsize = ':zippedsize', pd_unzippedsize = ':unzippedsize', pd_filename = ':filename', pd_filecontents = ':filecontents', pd_key = upper(sha1(now())), pd_status = 'preparing' where pd_id = :publicdownloadid");
				q2.bindValue(":expiredays",expiredays);
				q2.bindValue(":zippedsize",zippedsize);
				q2.bindValue(":unzippedsize",unzippedsize);
				q2.bindValue(":filename",filename);
				q2.bindValue(":filecontents",filecontents);
				q2.bindValue(":publicdownloadid",publicdownloadid);
				n->SQLQuery(q2,"ExportLocal",true);
			}
			else {
				exportstatus = "error";
				n->WriteLog("ERROR directory [" + outdir + "] does not exist");
				msgs << "Outdir [" + zipfile + "] does not exist";
			}

			if (QFile::exists(zipfile)) {
				msgs << "Created .zip file [" + zipfile + "]";
			}
			else {
				exportstatus = "error";
				n->WriteLog("ERROR unable to create zip file [" + zipfile + "]");
				msgs << "Unable to create [" + zipfile + "]";
			}
		}
		else {
			/* public downloadid not found */
		}
	}

	n->WriteLog("Leaving ExportLocal()...");

	msg = msgs.join("\n");

	return 1;
}


/* ------------------------------------------------- */
/* --------- AnonymizeDICOMFile -------------------- */
/* ------------------------------------------------- */
/* borrowed in its entirety from gdcmanon.cxx        */
bool moduleExport::AnonymizeDICOMFile(gdcm::Anonymizer &anon, const char *filename, const char *outfilename, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags, bool continuemode)
{
	gdcm::Reader reader;
	reader.SetFileName( filename );
	if( !reader.Read() ) {
		std::cerr << "Could not read : " << filename << std::endl;
		if( continuemode ) {
			std::cerr << "Skipping from anonymization process (continue mode)." << std::endl;
			return true;
		}
		else
		{
			std::cerr << "Check [--continue] option for skipping files." << std::endl;
			return false;
		}
	}
	gdcm::File &file = reader.GetFile();

	anon.SetFile( file );

	if( empty_tags.empty() && replace_tags.empty() && remove_tags.empty() ) {
		std::cerr << "No operation to be done." << std::endl;
		return false;
	}

	std::vector<gdcm::Tag>::const_iterator it = empty_tags.begin();
	bool success = true;
	for(; it != empty_tags.end(); ++it) {
		success = success && anon.Empty( *it );
	}
	it = remove_tags.begin();
	for(; it != remove_tags.end(); ++it) {
		success = success && anon.Remove( *it );
	}

	std::vector< std::pair<gdcm::Tag, std::string> >::const_iterator it2 = replace_tags.begin();
	for(; it2 != replace_tags.end(); ++it2) {
		success = success && anon.Replace( it2->first, it2->second.c_str() );
	}

	gdcm::Writer writer;
	writer.SetFileName( outfilename );
	writer.SetFile( file );
	if( !writer.Write() ) {
		std::cerr << "Could not Write : " << outfilename << std::endl;
		if( strcmp(filename,outfilename) != 0 ) {
			gdcm::System::RemoveFile( outfilename );
		}
		else
		{
			std::cerr << "gdcmanon just corrupted: " << filename << " for you (data lost)." << std::endl;
		}
		return false;
	}
	return success;
}


/* ---------------------------------------------------------- */
/* --------- AnonymizeDir ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleExport::AnonymizeDir(QString dir,int anonlevel, QString randstr1, QString randstr2) {

	std::vector<gdcm::Tag> empty_tags;
	std::vector<gdcm::Tag> remove_tags;
	std::vector< std::pair<gdcm::Tag, std::string> > replace_tags;

	gdcm::Tag tag;

	switch (anonlevel) {
	    case 0:
		    qDebug() << "No anonymization requested. Leaving files unchanged.";
		    return 0;
	    case 1:
	    case 3:
		    /* remove referring physician name */
		    tag.ReadFromCommaSeparatedString("0008, 0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
			tag.ReadFromCommaSeparatedString("0008, 1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
			tag.ReadFromCommaSeparatedString("0008, 1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") );
			tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr1).toStdString().c_str()) );
			tag.ReadFromCommaSeparatedString("0010, 0030"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr2).toStdString().c_str()) );
		    break;
	    case 2:
		    /* Full anonymization. remove all names, dates, locations. ANYTHING identifiable */
		    tag.ReadFromCommaSeparatedString("0008,0012"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // InstanceCreationDate
			tag.ReadFromCommaSeparatedString("0008,0013"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // InstanceCreationTime
			tag.ReadFromCommaSeparatedString("0008,0020"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // StudyDate
			tag.ReadFromCommaSeparatedString("0008,0021"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // SeriesDate
			tag.ReadFromCommaSeparatedString("0008,0022"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // AcquisitionDate
			tag.ReadFromCommaSeparatedString("0008,0023"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // ContentDate
			tag.ReadFromCommaSeparatedString("0008,0030"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //StudyTime
			tag.ReadFromCommaSeparatedString("0008,0031"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //SeriesTime
			tag.ReadFromCommaSeparatedString("0008,0032"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //AcquisitionTime
			tag.ReadFromCommaSeparatedString("0008,0033"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); //ContentTime
			tag.ReadFromCommaSeparatedString("0008,0080"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // InstitutionName
			tag.ReadFromCommaSeparatedString("0008,0081"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // InstitutionAddress
			tag.ReadFromCommaSeparatedString("0008,0090"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianName
			tag.ReadFromCommaSeparatedString("0008,0092"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianAddress
			tag.ReadFromCommaSeparatedString("0008,0094"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianTelephoneNumber
			tag.ReadFromCommaSeparatedString("0008,0096"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ReferringPhysicianIDSequence
			tag.ReadFromCommaSeparatedString("0008,1010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // StationName
			tag.ReadFromCommaSeparatedString("0008,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // StudyDescription
			tag.ReadFromCommaSeparatedString("0008,103E"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // SeriesDescription
			tag.ReadFromCommaSeparatedString("0008,1048"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PhysiciansOfRecord
			tag.ReadFromCommaSeparatedString("0008,1050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformingPhysicianName
			tag.ReadFromCommaSeparatedString("0008,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // NameOfPhysicianReadingStudy
			tag.ReadFromCommaSeparatedString("0008,1070"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OperatorsName

			tag.ReadFromCommaSeparatedString("0010,0010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientName
			tag.ReadFromCommaSeparatedString("0010,0020"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientID
			tag.ReadFromCommaSeparatedString("0010,0021"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // IssuerOfPatientID
			tag.ReadFromCommaSeparatedString("0010,0030"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // PatientBirthDate
			tag.ReadFromCommaSeparatedString("0010,0032"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); // PatientBirthTime
			tag.ReadFromCommaSeparatedString("0010,0050"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientInsurancePlanCodeSequence
			tag.ReadFromCommaSeparatedString("0010,1000"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OtherPatientIDs
			tag.ReadFromCommaSeparatedString("0010,1001"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // OtherPatientNames
			tag.ReadFromCommaSeparatedString("0010,1005"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientBirthName
			tag.ReadFromCommaSeparatedString("0010,1010"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientAge
			tag.ReadFromCommaSeparatedString("0010,1020"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientSize
			tag.ReadFromCommaSeparatedString("0010,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientWeight
			tag.ReadFromCommaSeparatedString("0010,1040"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientAddress
			tag.ReadFromCommaSeparatedString("0010,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientMotherBirthName
			tag.ReadFromCommaSeparatedString("0010,2154"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientTelephoneNumbers
			tag.ReadFromCommaSeparatedString("0010,21B0"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // AdditionalPatientHistory
			tag.ReadFromCommaSeparatedString("0010,21F0"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientReligiousPreference
			tag.ReadFromCommaSeparatedString("0010,4000"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PatientComments

			tag.ReadFromCommaSeparatedString("0018,1030"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ProtocolName

			tag.ReadFromCommaSeparatedString("0032,1032"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // RequestingPhysician
			tag.ReadFromCommaSeparatedString("0032,1060"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // RequestedProcedureDescription

			tag.ReadFromCommaSeparatedString("0040,0006"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // ScheduledPerformingPhysiciansName
			tag.ReadFromCommaSeparatedString("0040,0244"); replace_tags.push_back( std::make_pair(tag, "19000101") ); // PerformedProcedureStepStartDate
			tag.ReadFromCommaSeparatedString("0040,0245"); replace_tags.push_back( std::make_pair(tag, "000000.000000") ); // PerformedProcedureStepStartTime
			tag.ReadFromCommaSeparatedString("0040,0253"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformedProcedureStepID
			tag.ReadFromCommaSeparatedString("0040,0254"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PerformedProcedureStepDescription
			tag.ReadFromCommaSeparatedString("0040,4036"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // HumanPerformerOrganization
			tag.ReadFromCommaSeparatedString("0040,4037"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // HumanPerformerName
			tag.ReadFromCommaSeparatedString("0040,A123"); replace_tags.push_back( std::make_pair(tag, "Anonymous") ); // PersonName

			break;
	    case 4:
		    tag.ReadFromCommaSeparatedString("0010, 0010"); replace_tags.push_back( std::make_pair(tag, QString("Anonymous" + randstr1).toStdString().c_str()) );
		    break;
	}

	/* recursively loop through the directory and anonymize the .dcm files */
	gdcm::Anonymizer anon;
	QDirIterator it(dir, QStringList() << "*.dcm", QDir::Files, QDirIterator::Subdirectories);
	while (it.hasNext()) {
		const char *dcmfile = it.next().toStdString().c_str();
		AnonymizeDICOMFile(anon, dcmfile, dcmfile, empty_tags, remove_tags, replace_tags, false);
	}

	//# remove an txt files, which may contain PHI
	//if ($File::Find::name =~ /\.gif/) { unlink($File::Find::name); }
	//if ($File::Find::name =~ /\.txt/) { unlink($File::Find::name); }

	return true;
}
