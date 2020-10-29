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

    bool CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, int &subjectRowID, QString &subjectRealUID);
    bool ArchiveDICOMSeries(int importid, int existingSubjectID, int existingStudyID, int existingSeriesID, QString subjectMatchCriteria, QString studyMatchCriteria, QString seriesMatchCriteria, int destProjectID, int destSiteID, QString altUIDs, QString seriesNotes, QStringList files);
    bool InsertParRec(int importid, QString file);
    bool InsertEEG(int importid, QString file);
    bool GetSubject(QString matchcriteria, int subjectid, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, double PatientWeight, double PatientSize, QString SQLIDs, int &subjectRowID, QString &subjectUID);
    bool GetStudy(QString matchcriteria, int existingStudyID, int subjectRowID, int enrollmentRowID, QString StudyDateTime, QString studyUID, QString Modality, QString PatientID, double PatientAge, double PatientSize, double PatientWeight, QString StudyDescription, QString OperatorsName, QString PerformingPhysiciansName, QString StationName, QString InstitutionName, QString InstitutionAddress, int &studyRowID, int &studyNum);

    /* helper functions */
    QString GetCostCenter(QString studydesc);
    QString CreateIDSearchList(QString PatientID, QString altuids);
    void CreateThumbnail(QString f, QString outdir);
    double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate);
    void SetAlternateIDs(int subjectRowID, int enrollmentRowID, QStringList altuidlist);
    bool GetFamily(int subjectRowID, QString subjectUID, int &familyRowID, QString &familyUID);
    bool GetProject(int destProjectID, QString costcenter, int &projectRowID);
    bool GetEnrollment(int subjectRowID, int projectRowID, int &enrollmentRowID);

    void SetUploadID(int u);
    void AppendUploadLog(QString func, QString m);

private:
    nidb *n;

    int uploadid;
};

#endif // ARCHIVEIO_H
