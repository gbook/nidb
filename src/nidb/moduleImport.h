/* ------------------------------------------------------------------------------
  NIDB moduleImport.h
  Copyright (C) 2004 - 2025
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
#include "archiveio.h"
#include "imageio.h"
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

    bool Run();

private:
    nidb *n;
    archiveIO *io;
    imageIO *img;

    QMap<QString, QStringList> dcmseries; /* QMap (associated hash) to store dicoms by the SeriesInstanceUID DICOM tag */

    /* functions */
    QString GetImportStatus(int importid);
    bool ArchiveLocal();
    bool ParseDirectory(QString dir, int importid);
    bool ParseRemotelyImportedData();
    bool PrepareAndMoveDICOM(QString file, QString outdir, bool anonymize);
    bool PrepareAndMovePARREC(QString file, QString outdir);
    bool SetImportRequestStatus(int importid, QString status, QString msg = "");
    bool SetImportStatus(int importid, QString status, QString msg, QString report, bool enddate);
};

#endif // MODULEIMPORT_H
