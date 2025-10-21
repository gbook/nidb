/* ------------------------------------------------------------------------------
  NIDB archiveio.h
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

#ifndef ARCHIVEIO_H
#define ARCHIVEIO_H
#include "nidb.h"
#include "imageio.h"
#include "performanceMetric.h"
#include "series.h"
#include "subject.h"
#include "study.h"
#include "analysis.h"
#include "pipeline.h"
#include "minipipeline.h"
#include "experiment.h"
#include "observation.h"
#include "intervention.h"
#include "squirrel.h"

/**
 * @brief The archiveIO class is responsible for interacting with the archive, such as adding subjects and archiving data
 * It includes functions to: export BIDS/squirrel from the archive
 */
class archiveIO
{
public:
    archiveIO();
    archiveIO(nidb *n);
    ~archiveIO();

    /* archive functions */
    BIDSMapping GetBIDSMapping(int projectRowID, QString protocol, QString modality, QString imageType);
    bool ArchiveDICOMSeries(int importid, int existingSubjectID, int existingStudyID, int existingSeriesID, QString subjectMatchCriteria, QString studyMatchCriteria, QString seriesMatchCriteria, int destProjectID, QString specificPatientID, int destSiteID, QString altUIDs, QString seriesNotes, QStringList files, performanceMetric &perf);
    bool ArchiveEEGSeries(int importid, QString file);
    bool ArchiveNiftiSeries(int subjectRowID, int studyRowID, int seriesRowID, int seriesNumber, QHash<QString, QString> tags, QStringList files);
    bool ArchiveParRecSeries(int importid, QString file);
    bool ArchiveSquirrelPackage(UploadOptions options, QString file, QString &msg);
    bool CreateStudy(int subjectRowID, int enrollmentRowID, QString StudyDateTime, QString studyUID, QString Modality, QString PatientID, double PatientAge, double PatientSize, double PatientWeight, QString StudyDescription, QString OperatorsName, QString PerformingPhysiciansName, QString StationName, QString InstitutionName, QString InstitutionAddress, int &studyRowID, int &studyNum);
    bool CreateSubject(QString PatientID, QString PatientName, QString PatientBirthDate, QString PatientSex, double PatientWeight, double PatientSize, int &subjectRowID, QString &subjectRealUID);
    //bool CreateSubject(QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, double PatientWeight, double PatientSize, QString SQLIDs, int &subjectRowID, QString &subjectUID);
    bool GetSeriesListDetails(QList <qint64> seriesids, QStringList modalities, subjectStudySeriesContainer &s);
    bool GetStudy(QString studyMatchCriteria, int existingStudyID, int enrollmentRowID, QString StudyDateTime, QString Modality, QString StudyInstanceUID, int &studyRowID, int &studyNumber);
    bool GetSubject(QString subjectMatchCriteria, int existingSubjectID, int projectID, QString PatientID, QString PatientName, QString PatientSex, QString PatientBirthDate, int &subjectRowID, QString &subjectUID);
    bool WriteBIDS(QList<qint64> seriesids, QStringList modalities, QString odir, QString bidsreadme, QStringList bidsflags, QString &msg);
    bool WriteExportPackage(qint64 exportid, QString zipfilepath, QString &msg);
    bool WriteSquirrel(qint64 exportid, QString name, QString desc, QStringList downloadflags, QStringList squirrelflags, QList<qint64> seriesids, QStringList modalities, QString odir, QString &msg);

    /* archive helper functions */
    QString CreateIDSearchList(QString PatientID, QString altuids);
    QString GetCostCenter(QString studydesc);
    bool GetOrCreateEnrollment(int subjectRowID, int projectRowID, int &enrollmentRowID);
    bool GetFamily(int subjectRowID, QString subjectUID, int &familyRowID, QString &familyUID);
    bool GetProject(int destProjectID, QString StudyDescription, int &projectRowID);
    bool CreateThumbnail(QString f, QString outdir);
    void SetAlternateIDs(int subjectRowID, int enrollmentRowID, QStringList altuidlist);

    /* object helper functions */
    void SetUploadID(int upid);
    void AppendUploadLog(QString func, QString m);

private:
    nidb *n;
    imageIO *img;

    int uploadid;
};

#endif // ARCHIVEIO_H
