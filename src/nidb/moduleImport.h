/* ------------------------------------------------------------------------------
  NIDB moduleImport.h
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

#ifndef MODULEIMPORT_H
#define MODULEIMPORT_H
#include "nidb.h"
#include "gdcmReader.h"
#include "gdcmWriter.h"
#include "gdcmAttribute.h"
#include "gdcmStringFilter.h"
#include "gdcmAnonymizer.h"
#include "series.h"


class moduleImport
{
public:
	moduleImport();
	moduleImport(nidb *n);
	~moduleImport();

	int Run();
	int ParseDirectory(QString dir, int importid);
	QString GetImportStatus(int importid);
	bool SetImportStatus(int importid, QString status, QString msg, QString report, bool enddate);
	bool ParseDICOMFile(QString file, QHash<QString, QString> &tags);
	bool InsertDICOMSeries(int importid, QStringList files, QString &msg);
	bool InsertParRec(int importid, QString file, QString &msg);
	bool InsertEEG(int importid, QString file, QString &msg);
	void CreateThumbnail(QString f, QString outdir);
	QString GetCostCenter(QString studydesc);
	QString CreateIDSearchList(QString PatientID, QString altuids);
	bool CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, QString importUUID, QStringList &msgs, int &subjectRowID, QString &subjectRealUID);

private:
	nidb *n;

	/* create a multilevel hash, for archiving data without a SeriesInstanceUID tag: dcms[institute][equip][modality][patient][dob][sex][date][series][files] */
	//QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QStringList>>>>>>>>> dcms;

	/* create a regular associated hash for dicoms with a SeriesInstanceUID tag */
	QMap<QString, QStringList> dcmseries;
};

#endif // MODULEIMPORT_H
