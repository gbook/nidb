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
    };
    int instrumentRowID;
    int instrumentItemRowID;
};

class moduleRemoteImport
{
public:
    moduleRemoteImport(nidb *n);
    ~moduleRemoteImport();

    bool Run();

    bool IsDateInSchedule(QDateTime date, QString scheduleType, int hourOfDay, int dayOfMonth, QStringList daysOfWeek);

private:
    bool ImportAvicenna(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QString remoteUsername, int remoteProjectID, QList <RemoteImportMapping> mapping);
    bool ImportRedCap(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QList <RemoteImportMapping> mapping);
    bool ImportURL(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QList <RemoteImportMapping> mapping);
    bool ImportCSV(int remoteImportBatchRowID, QList <RemoteImportMapping> mapping);
    QList <RemoteImportMapping> GetImportMapping(int projectRowID);

    QString RemoteImportLogEventToString(RemoteImportLogEvent event);
    QString EventResultToString(EventResult result);
    void RemoteImportLog(qint64 batchRowID, RemoteImportLogEvent event, QString message, EventResult result);

    void SetBatchStatus(qint64 batchRowID, QString status, int remoteExportID = -1);

    int GetAvicennaExportID(QString jsonStr);
    QString GetAvicennaExportStatus(int exportID, QString remoteUsername, QString remoteToken, QString &exportURL);

    QList <RemoteImportMapping> mapping;

    nidb *n;
};

#endif // MODULEREMOTEIMPORT_H
