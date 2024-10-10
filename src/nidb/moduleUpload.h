/* ------------------------------------------------------------------------------
  NIDB moduleUpload.h
  Copyright (C) 2004 - 2024
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

private:
    nidb *n;
    archiveIO *io;
    imageIO *img;

    QString GetUploadStatus(int uploadid);
    bool ArchiveSelectedFiles();
    bool ParseUploadedFiles(QMap<QString, QMap<QString, QMap<QString, QStringList> > > fs, QString upload_subjectcriteria, QString upload_studycriteria, QString upload_seriescriteria, QString uploadstagingpath, int upload_id);
    bool ParseUploadedSquirrel(squirrel *sqrl, QString upload_subjectcriteria, QString upload_studycriteria, QString upload_seriescriteria, QString uploadstagingpath, int uploadRowID);
    bool ReadUploads();
    void SetUploadStatus(int uploadid, QString status, double percent=-1.0);
    int InsertOrUpdateParsedSubject(int parsedSubjectRowID, QString upload_subjectcriteria, int uploadRowID, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, QString &m);
    int InsertOrUpdateParsedStudy(int parsedStudyRowID, QString upload_studycriteria, int subjectRowID, QString StudyDateTime, QString Modality, QString StudyInstanceUID, QString StudyDescription, QString FileType, QString Equipment, QString Operator, QString &msg);
    int InsertOrUpdateParsedSeries(int parsedSeriesRowID, QString upload_seriescriteria, int studyRowID, QString SeriesDateTime, int SeriesNumber, QString SeriesInstanceUID, QStringList &files, int &numfiles, QString SeriesDescription, QString ProtocolName, QString RepetitionTime, QString EchoTime, QString SpacingBetweenSlices, QString SliceThickness, int Rows, int Columns, QString &msg);
};

#endif // MODULEUPLOAD_H
