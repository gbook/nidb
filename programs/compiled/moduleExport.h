#ifndef MODULEEXPORT_H
#define MODULEEXPORT_H
#include "nidb.h"
#include "remotenidbconnection.h"
#include "gdcmReader.h"
#include "gdcmWriter.h"
#include "gdcmAttribute.h"
#include "gdcmStringFilter.h"
#include "gdcmAnonymizer.h"


class moduleExport
{
public:
    moduleExport(nidb *a);
    ~moduleExport();
    int Run();

	QString GetExportStatus(int exportid);
	bool SetExportStatus(int exportid, QString status, QString msg = "");

	bool GetExportSeriesList(int exportid);

	bool ExportLocal(int exportid, QString exporttype, QString nfsdir, int publicdownloadid, bool downloadimaging, bool downloadbeh, bool downloadqc, QString filetype, QString dirformat, int preserveseries, bool gzip, int anonymize, QString behformat, QString behdirrootname, QString behdirseriesname, QString &status, QString &msg);

	bool AnonymizeDir(QString dir,int anonlevel, QString randstr1, QString randstr2);
	bool AnonymizeDICOMFile(gdcm::Anonymizer &anon, const char *filename, const char *outfilename, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags, bool continuemode);

	/* create a multilevel hash s[uid][study][series]['attribute'] */
	QMap<QString, QMap<int, QMap<int, QMap<QString, QString>>>> s;

private:
    nidb *n;
};

#endif // MODULEEXPORT_H
