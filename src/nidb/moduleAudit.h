/* ------------------------------------------------------------------------------
  NIDB moduleAudit.h
  Copyright (C) 2004 - 2026
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
#ifndef MODULEAUDIT_H
#define MODULEAUDIT_H
#include "nidb.h"

class moduleAudit
{
public:
    moduleAudit(nidb *n);
    ~moduleAudit();

    int Run();

    /* individual audits. Each returns true if the audit ran to completion, and sets m to a summary message */
    bool AuditSeriesFiles(qint64 auditid, int projectid, bool fixErrors, QString &m);
    bool AuditAnalysisFiles(qint64 auditid, int projectid, bool fixErrors, QString &m);
    bool AuditQualityControl(qint64 auditid, int projectid, QStringList checks, QString &m);

private:
    /* a subject enrolled in the project being audited, used by the quality control checks */
    struct qcSubject {
        qint64 subjectid;
        QString uid;
        QString name;
        QString sex; /* subjects.gender */
        QString sexcol; /* subjects.sex */
        QString dob; /* YYYY-MM-DD as stored, which may be YYYY-00-00 or YYYY-MM-00 */
    };

    /* a series to check by the quality control DICOM metadata check */
    struct qcDicomSeries {
        QString table;
        QString modality;
        qint64 seriesid;
        int seriesnum;
        QString datatype;
        qint64 subjectid;
        QString uid;
        int studynum;
        QString studydatetime;
        QString storagetier;
    };

    /* quality control checks. Each returns the number of issues found, and sets ok to false on an SQL error */
    bool QCLoadSubjects(int projectid, QList<qcSubject> &subjects);
    qint64 QCDuplicateSubjects(qint64 auditid, int projectid, const QList<qcSubject> &subjects, bool projectAltUIDsOnly, bool &ok);
    qint64 QCDuplicateStudies(qint64 auditid, int projectid, bool &ok);
    qint64 QCEmptyObjects(qint64 auditid, int projectid, bool &ok);
    qint64 QCMissingDemographics(qint64 auditid, int projectid, const QList<qcSubject> &subjects, bool fixSex, bool fixName, bool fixDOB, bool &ok);
    qint64 QCInvalidDOB(qint64 auditid, int projectid, const QList<qcSubject> &subjects, bool &ok);
    bool QCLoadDicomSeries(int projectid, QList<qcDicomSeries> &seriesList);
    qint64 QCDicomSeries(qint64 auditid, const qcDicomSeries &s, const QSet<QString> &subjectIDs, bool &checked);

    /* quality control helpers */
    QMap<QString, QSet<QString>> GetSeriesTables();
    QString GetStudyPath(QString storageTier, QString uid, int studynum);
    static QStringList NameTokens(QString name);
    static int Levenshtein(const QString &a, const QString &b);
    static bool DOBIsMissing(QString dob);
    QString EstimateDOB(qint64 subjectid, QString &basis, bool &ok);
    static bool DOBIsValid(QString dob, QDate firstStudyDate, QString &reason);
    static QString FormatValueCounts(const QMap<QString, int> &counts);

    bool AuditCheckIn(qint64 auditid, qint64 progress, qint64 total, QString &stopReason);
    int CheckFileCountAndSize(qint64 auditid, bool fixErrors, QString objectType, qint64 objectid, QString objectDesc, QString path, QString table, QString idColumn, QString countColumn, QString sizeColumn, bool dbCountIsNull, qint64 dbCount, bool dbSizeIsNull, qint64 dbSize, qint64 diskCount, qint64 diskSize, qint64 &numFixed);
    QString GetAuditStatus(qint64 auditid);
    void SetAuditStatus(qint64 auditid, QString status, QString msg, bool ended=false);
    void SetAuditProgress(qint64 auditid, qint64 progress, qint64 total);
    qint64 LogAuditIssue(qint64 auditid, QString issueType, QString objectType, qint64 objectid, QString msg, int fixed, QString fixedMsg);
    void LogAuditIssueObject(qint64 auditlogid, QString objectType, qint64 objectid, QString note);

    nidb *n;
};

#endif // MODULEAUDIT_H
