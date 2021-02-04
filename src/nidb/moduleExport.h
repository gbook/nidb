/* ------------------------------------------------------------------------------
  NIDB moduleExport.h
  Copyright (C) 2004 - 2021
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

#ifndef MODULEEXPORT_H
#define MODULEEXPORT_H
#include "nidb.h"
#include "archiveio.h"
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

    bool ExportLocal(int exportid, QString exporttype, QString nfsdir, int publicdownloadid, bool downloadimaging, bool downloadbeh, bool downloadqc, QString filetype, QString dirformat, int preserveseries, bool gzip, int anonymize, QString behformat, QString behdirrootname, QString behdirseriesname, QString bidsreadme, QString bidsflags, QString &status, QString &msg);
    bool ExportNDAR(int exportid, bool csvonly, QString &exportstatus, QString &msg);
    bool ExportBIDS(int exportid, QString bidsreadme, QString bidsflags, QString &outdir, QString &exportstatus, QString &msg);
    bool ExportToRemoteNiDB(int exportid, remoteNiDBConnection &conn, QString &exportstatus, QString &msg);
    bool ExportToRemoteFTP(int exportid, QString remoteftpusername, QString remoteftppassword, QString remoteftpserver, int remoteftpport, QString remoteftppath, QString &exportstatus, QString &msg);

    bool WriteNDARHeader(QString headerfile, QString modality, QStringList &log);
    bool WriteNDARSeries(QString file, QString imagefile, QString behfile, QString behdesc, int seriesid, QString modality, QString indir, QStringList &log);

    int StartRemoteNiDBTransaction(QString remotenidbserver, QString remotenidbusername, QString remotenidbpassword);
    void EndRemoteNiDBTransaction(int tid, QString remotenidbserver, QString remotenidbusername, QString remotenidbpassword);

    /* create a multilevel hash s[uid][study][series]['attribute'] to store the series */
    QMap<QString, QMap<int, QMap<int, QMap<QString, QString>>>> s;

private:
    nidb *n;
    archiveIO *io;
};

#endif // MODULEEXPORT_H
