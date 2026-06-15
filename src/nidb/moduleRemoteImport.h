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

/* enums for logging */
enum RemoteImportLogEvent {
    ConnectionEnd,
    ConnectionStart,
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
    bool ImportAvicenna(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QString remoteUsername, int remoteProjectID, int remoteSurveyID, const ImportMapping &mapping);
    bool ImportRedCap(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, const ImportMapping &mapping);
    bool ImportURL(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, const ImportMapping &mapping);
    bool ImportCSV(int remoteImportBatchRowID, int remoteSurveyID, QString csvFormat, const ImportMapping &mapping, bool importUnmapped);

    QString RemoteImportLogEventToString(RemoteImportLogEvent event);
    QString EventResultToString(EventResult result);
    void RemoteImportLog(qint64 batchRowID, RemoteImportLogEvent event, QString message, EventResult result);

    void SetBatchStatus(qint64 batchRowID, QString status, int remoteExportID = -1);

    int GetAvicennaExportID(QString jsonStr);
    QString GetAvicennaExportStatus(int remoteProjectID, int exportID, QString remoteUsername, QString remoteToken, QString &exportURL);
    bool DownloadAvicennaExport(int remoteProjectID, QString remoteUsername, QString remoteToken, QString url, QString path);
    QList<int> GetAvicennaSubjectsFromCSV(QString csv);
    qint64 ParseInsertAvicenna(qint64 remoteImportBatchRowID, int remoteSurveyID, const ImportMapping &mapping, bool importUnmapped, QString csvpath);

    //InsertObservation(int subjectRowID, int enrollmentRowID, int remoteBatchRowID, QString observation, QDateTime startDate, QDateTime endDate, int duration, QDateTime entryDate, QDateTime createDate, QDateTime modifyDate);

    nidb *n;
};

#endif // MODULEREMOTEIMPORT_H
