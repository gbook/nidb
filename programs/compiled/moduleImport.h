#ifndef MODULEIMPORT_H
#define MODULEIMPORT_H
#include "nidb.h"
#include "gdcmReader.h"
#include "gdcmWriter.h"
#include "gdcmAttribute.h"
#include "gdcmStringFilter.h"
#include "gdcmAnonymizer.h"


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
	void CreateThumbnail(QString f, QString outdir);

private:
	nidb *n;

	/* create a multilevel hash, for archiving data without a SeriesInstanceUID tag: dcms[institute][equip][modality][patient][dob][sex][date][series][files] */
	QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QMap<QString, QStringList>>>>>>>>> dcms;

	/* create a regular associated hash for dicoms with a SeriesInstanceUID tag */
	QMap<QString, QStringList> dcmseries;

};

#endif // MODULEIMPORT_H
