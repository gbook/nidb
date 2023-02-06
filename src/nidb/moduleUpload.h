/* ------------------------------------------------------------------------------
  NIDB moduleUpload.h
  Copyright (C) 2004 - 2023
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

#ifndef MODULEUPLOAD_H
#define MODULEUPLOAD_H
#include "nidb.h"
#include "archiveio.h"
#include "imageio.h"

class moduleUpload
{
public:
    moduleUpload();
    moduleUpload(nidb *n);
    ~moduleUpload();

    int Run();
    //QString AppendUploadLog(int uploadid, QString msg);
    bool ParseUploads();
    bool ArchiveParsedUploads();
    void SetUploadStatus(int uploadid, QString status, double percent=-1.0);
    QString GetUploadStatus(int uploadid);
    bool UpdateParsedUploads(QMap<QString, QMap<QString, QMap<QString, QStringList> > > fs, QString upload_subjectcriteria, QString upload_studycriteria, QString upload_seriescriteria, QString uploadstagingpath, int upload_id);

private:
    nidb *n;
    archiveIO *io;
    imageIO *img;
};

#endif // MODULEUPLOAD_H
