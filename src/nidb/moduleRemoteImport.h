/* ------------------------------------------------------------------------------
  NIDB moduleRemoteImport.h
  Copyright (C) 2004 - 2026
  Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
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

#ifndef MODULEREMOTEIMPORT_H
#define MODULEREMOTEIMPORT_H

#include "nidb.h"
#include "observation.h"
#include "survey.h"

/* enums for logging */
enum RemoteImportLogEvent {
    ConnectionEnd,
    ConnectionStart,
    FileEvent,
    ImportAnalysis,
    ImportEnd,
    ImportIntervention,
    ImportObservation,
    ImportPipeline,
    ImportSeries,
    ImportStart,
    ImportStudy,
    ImportSubject,
};

enum EventResult {
    Success,
    Error,
    Warning,
    Neutral
};

/* remote import mapping record */
struct RemoteImportMapping {
    QString sourceType;
    struct Avicenna {
        int survey;
        int question;
        QString datasource;
        QString variable;
        QString datatype;
        QString variableCount;
    } avicenna;
    struct Redcap {
        QString arm;
        QString event;
        QString form;
        QString field;
        QString datatype;
        QString dateField;
    } redcap;
    struct flags {
        bool importMeta;
    } flag;
    int instrumentRowID;
    int instrumentItemRowID;
};

/* import mapping class */
class ImportMapping
{
public:
    ImportMapping(nidb *n, int projectRowID);

    bool LookupAvicennaMapping(int survey, int question, QString variable, int &instrumentRowID, int &instrumentItemRowID, bool &importMeta) const;
    bool LookupAvicennaMapping(QString datasource, QString variable, int &instrumentRowID, int &instrumentItemRowID, bool &importMeta) const;
    bool LookupRedcapMapping(QString arm, QString event, QString form, QString field, int &instrumentRowID, int &instrumentItemRowID, bool &importMeta) const;

    int size() const;

    QList<RemoteImportMapping> mappings;

private:
    nidb *n;
};



/* ----- moduleRemoteImport ----- */
class moduleRemoteImport
{
public:
    moduleRemoteImport(nidb *n);
    ~moduleRemoteImport();

    bool Run();

    bool IsDateInSchedule(QDateTime date, QString scheduleType, int hourOfDay, int dayOfMonth, QStringList daysOfWeek);

private:
    bool ImportAvicennaSurveyAPI(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QString remoteUsername, int remoteProjectID, int remoteSurveyID, const ImportMapping &mapping);
    bool ImportAvicennaApiDataSource(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QString remoteUsername, int remoteProjectID, int remoteSurveyID, const ImportMapping &mapping);
    bool ImportRedCap(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, const ImportMapping &mapping);
    bool ImportURL(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, const ImportMapping &mapping);
    //bool ImportCSV(int remoteImportBatchRowID, int remoteSurveyID, QString csvFormat, const ImportMapping &mapping, bool importUnmapped);

    QString RemoteImportLogEventToString(RemoteImportLogEvent event);
    QString EventResultToString(EventResult result);
    void RemoteImportLog(qint64 batchRowID, RemoteImportLogEvent event, QString message, EventResult result);

    void SetBatchStatus(qint64 batchRowID, QString status, int remoteExportID = -1);

    int GetAvicennaExportID(QString jsonStr);
    QString GetAvicennaExportStatus(int remoteProjectID, int exportID, QString remoteUsername, QString remoteToken, QString &exportURL);
    bool DownloadAvicennaExport(int remoteProjectID, QString remoteUsername, QString remoteToken, QString url, QString path);
    QList<int> GetAvicennaSubjectsFromCSV(QString csv);
    qint64 ImportAvicennaSurveyCSV(qint64 remoteImportBatchRowID, int remoteSurveyID, const ImportMapping &mapping, bool importUnmapped);
    qint64 ImportAvicennaDataSourceCSV(qint64 remoteImportBatchRowID, QString remoteDatasource, const ImportMapping &mapping, bool importUnmapped);

    //InsertObservation(int subjectRowID, int enrollmentRowID, int remoteBatchRowID, QString observation, QDateTime startDate, QDateTime endDate, int duration, QDateTime entryDate, QDateTime createDate, QDateTime modifyDate);

    nidb *n;


    /* ----- Avicenna - global date handling functions and consts ----- */

    /* Avicenna timestamps are ISO 8601 with a space separator and microseconds, e.g.
           "2025-12-14 15:07:05.385000+00:00". Qt::ISODateWithMs handles the timezone
           offset but requires a T separator and milliseconds (3 digits), so preprocess first. */
    static inline auto parseAvicennaDT = [](const QString &raw) -> QDateTime {
        QString s = raw;
        s.replace(' ', 'T');
        s.replace(QRegularExpression("(\\.\\d{3})\\d+"), "\\1");
        return QDateTime::fromString(s, Qt::ISODateWithMs).toUTC();
    };

    /* extract the timezone from the Avicenna datetime format */
    static inline auto parseAvicennaTZ = [](const QString &raw) -> QString {
        QRegularExpressionMatch m = QRegularExpression("([+-]\\d{2}:\\d{2})$").match(raw.trimmed());
        if (m.hasMatch())
            return m.captured(1);

        /* if there no explicit timezone offset — then derive from the local timezone at this datetime's instant (handles DST) */
        QString s = raw.trimmed();
        s.replace(' ', 'T');
        s.replace(QRegularExpression("(\\.\\d{3})\\d+"), "\\1");
        QDateTime localDT = QDateTime::fromString(s, Qt::ISODateWithMs);
        int offsetSecs = localDT.isValid() ? localDT.offsetFromUtc() : QDateTime::currentDateTime().offsetFromUtc();
        int h   = qAbs(offsetSecs) / 3600;
        int min = (qAbs(offsetSecs) % 3600) / 60;
        return QString("%1%2:%3").arg(offsetSecs >= 0 ? "+" : "-").arg(h, 2, 10, QChar('0')).arg(min, 2, 10, QChar('0'));
    };

    /* columns that carry session metadata rather than question responses — skip these during data import */
    static inline const QStringList avicennaNonQuestionCols = {
        "activity version",
        "date",                     /* (datasource) formatted as YYYY-MM or YYYY */
        "device app update date",
        "device app version",
        "device id",
        "device last used",
        "device manufacturer",
        "device model",
        "duration (seconds) from first response to completion time",
        "duration (seconds) from scheduled to completion time",
        "end time",                 /* when the subject clicks submit */
        "expiry time",
        "location",
        "participant end time",     /* subject is done with project */
        "participant id",           /* the subject ID */
        "participant label",
        "participant start time",   /* enrollment date */
        "participant status",
        "prompt time",              /* notification - 7:00am */
        "record time",              /* may be the same as the end time, unless they didn't finish it... then it will be the end of the survey open-window time */
        "session scheduled time",   /* survey opens - 6:00am */
        "start time",               /* survey start time */
        "status",
        "study_id",                 /* (datasource) the project ID */
        "timestamp",                /* (datasource) UUIDv1 timestamp */
        "triggering logic id",
        "triggering logic type",
        "unanswered status",
        "user_id",                  /* (datasource) the subject ID */
        "uuid",
    };

};

#endif // MODULEREMOTEIMPORT_H
