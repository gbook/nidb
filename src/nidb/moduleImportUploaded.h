#ifndef MODULEIMPORTUPLOADED_H
#define MODULEIMPORTUPLOADED_H

#include "nidb.h"
#include "gdcmAnonymizer.h"

class moduleImportUploaded
{
public:
	moduleImportUploaded();
	moduleImportUploaded(nidb *n);
	~moduleImportUploaded();

	int Run();
	bool PrepareAndMoveDICOM(QString file, QString outdir, bool anonymize);
	bool PrepareAndMovePARREC(QString file, QString outdir);
	bool SetImportRequestStatus(int importid, QString status, QString msg = "");

private:
	nidb *n;
};

#endif // MODULEIMPORTUPLOADED_H
