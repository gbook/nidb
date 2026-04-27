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


class moduleRemoteImport
{
public:
    moduleRemoteImport(nidb *n);
    ~moduleRemoteImport();

    bool Run();

    bool IsDateInSchedule(QDateTime date, QString scheduleType, int hourOfDay, int dayOfMonth, QStringList daysOfWeek);

private:
    bool ImportAvicenna(int remoteImportBatchRowID, QString remoteURL, QString remoteToken);
    bool ImportRedCap(int remoteImportBatchRowID, QString remoteURL, QString remoteToken);
    bool ImportURL(int remoteImportBatchRowID, QString remoteURL, QString remoteToken);

    QString RemoteImportLogEventToString(RemoteImportLogEvent event);
    QString EventResultToString(EventResult result);
    void RemoteImportLog(qint64 batchRowID, RemoteImportLogEvent event, QString message, EventResult result);

    nidb *n;
};

#endif // MODULEREMOTEIMPORT_H
