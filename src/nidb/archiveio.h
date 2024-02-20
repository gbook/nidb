/* ------------------------------------------------------------------------------
  NIDB archiveio.h
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

#ifndef ARCHIVEIO_H
#define ARCHIVEIO_H
#include "nidb.h"
#include "imageio.h"
#include "performanceMetric.h"
#include "squirrel.h"
#include "series.h"
#include "subject.h"
#include "study.h"
#include "analysis.h"
#include "pipeline.h"
#include "minipipeline.h"
#include "experiment.h"

class archiveIO
{
public:
    archiveIO();
    archiveIO(nidb *n);
    ~archiveIO();

    /* archive functions */
    bool CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, int &subjectRowID, QString &subjectRealUID);
    bool ArchiveDICOMSeries(int importid, int existingSubjectID, int existingStudyID, int existingSeriesID, QString subjectMatchCriteria, QString studyMatchCriteria, QString seriesMatchCriteria, int destProjectID, QString specificPatientID, int destSiteID, QString altUIDs, QString seriesNotes, QStringList files, performanceMetric &perf);
    bool InsertParRec(int importid, QString file);
    bool InsertEEG(int importid, QString file);

    bool GetSubject(QString subjectMatchCriteria, int existingSubjectID, int projectID, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, int &subjectRowID, QString &subjectUID);
    bool CreateSubject(QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, double PatientWeight, double PatientSize, QString SQLIDs, int &subjectRowID, QString &subjectUID);

    bool GetStudy(QString studyMatchCriteria, int existingStudyID, int enrollmentRowID, QString StudyDateTime, QString Modality, QString StudyInstanceUID, int &studyRowID);
    bool CreateStudy(int subjectRowID, int enrollmentRowID, QString StudyDateTime, QString studyUID, QString Modality, QString PatientID, double PatientAge, double PatientSize, double PatientWeight, QString StudyDescription, QString OperatorsName, QString PerformingPhysiciansName, QString StationName, QString InstitutionName, QString InstitutionAddress, int &studyRowID, int &studyNum);

    bool WriteBIDS(QList<qint64> seriesids, QStringList modalities, QString odir, QString bidsreadme, QStringList bidsflags, QString &msg);
    bool WriteSquirrel(qint64 exportid, QString name, QString desc, QStringList downloadflags, QStringList squirrelflags, QList<qint64> seriesids, QStringList modalities, QString odir, QString &msg);
    bool WritePackage(qint64 exportid, QString zipfilepath, QString &msg);
    bool GetSeriesListDetails(QList <qint64> seriesids, QStringList modalities, subjectStudySeriesContainer &s);

    /* archive helper functions */
    QString GetCostCenter(QString studydesc);
    QString CreateIDSearchList(QString PatientID, QString altuids);
    void CreateThumbnail(QString f, QString outdir);
    void SetAlternateIDs(int subjectRowID, int enrollmentRowID, QStringList altuidlist);
    bool GetFamily(int subjectRowID, QString subjectUID, int &familyRowID, QString &familyUID);
    bool GetProject(int destProjectID, QString StudyDescription, int &projectRowID);
    bool GetEnrollment(int subjectRowID, int projectRowID, int &enrollmentRowID);

    bool AppendJSONMeasures(QJsonObject &jsonObj, QList <int> enrollmentIDs);
    bool AppendJSONDrugs(QJsonObject &jsonObj, QList <int> enrollmentIDs);

    /* class helper functions */
    void SetUploadID(int upid);
    void AppendUploadLog(QString func, QString m);

private:
    nidb *n;
    imageIO *img;

    int uploadid;
};

#endif // ARCHIVEIO_H
