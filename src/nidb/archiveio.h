/* ------------------------------------------------------------------------------
  NIDB archiveio.h
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

#ifndef ARCHIVEIO_H
#define ARCHIVEIO_H
#include "nidb.h"


class archiveIO
{
public:
    archiveIO();
    archiveIO(nidb *n);
    ~archiveIO();

    bool CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, QStringList &msgs, int &subjectRowID, QString &subjectRealUID);
    bool InsertDICOMSeries(int importid, int existingSubjectID, int existingStudyID, int existingSeriesID, QString subjectMatchCriteria, QString studyMatchCriteria, QString seriesMatchCriteria, int destProjectID, int destSiteID, QString altUIDs, QString seriesNotes, QStringList files, QString &msg);
    bool InsertParRec(int importid, QString file, QString &msg);
    bool InsertEEG(int importid, QString file, QString &msg);
    bool GetSubject(QString matchcriteria, int subjectid, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, double PatientWeight, double PatientSize, QString SQLIDs, int &subjectRowID, QString &subjectUID, bool &subjectCreated, QString &m);

    /* helper functions */
    QString GetCostCenter(QString studydesc);
    QString CreateIDSearchList(QString PatientID, QString altuids);
    void CreateThumbnail(QString f, QString outdir);
    double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);

private:
    nidb *n;

};

#endif // ARCHIVEIO_H
