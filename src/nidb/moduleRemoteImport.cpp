/* ------------------------------------------------------------------------------
  NIDB moduleRemoteImport.cpp
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

#include "moduleRemoteImport.h"

/* ---------------------------------------------------------- */
/* --------- moduleRemoteImport ----------------------------- */
/* ---------------------------------------------------------- */
moduleRemoteImport::moduleRemoteImport(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleRemoteImport ---------------------------- */
/* ---------------------------------------------------------- */
moduleRemoteImport::~moduleRemoteImport()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run the module.
 * @return true if anything was processed, false otherwise
 */
bool moduleRemoteImport::Run() {
    n->Log("Entering the remoteimport module");

    /* go through list of pending on-demand remote imports (which will already be in the remoteimport_batch table) */
    QSqlQuery q;
    q.prepare("select * from remoteimport_batch a left join remote_imports b on a.remoteimport_id = b.remoteimport_id where a.import_status = 'pending' and b.enabled == 1");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            n->ModuleRunningCheckIn();

            /* remoteimport_batch fields */
            qint64 remoteImportBatchRowID = q.value("remoteimportbatch_id").toLongLong();

            /* remote_import fields */
            int remoteImportRowID = q.value("remoteimport_id").toInt();
            QString importName = q.value("import_name").toString().trimmed();
            int projectRowID = q.value("project_id").toInt();
            QString remoteType = q.value("remote_type").toString().trimmed();
            QString remoteURL = q.value("remote_url").toString().trimmed();
            QString remoteToken = q.value("remote_token").toString().trimmed();
            QString importSchedule = q.value("remote_schedule").toString().trimmed();
            int importTime = q.value("remote_time").toInt();
            int importDayOfMonth = q.value("remote_dayofmonth").toInt();
            QStringList importDays = q.value("remote_days").toStringList();

        }
    }

    /* get list of remote imports that are not on-demand */
    q.prepare("select * from remote_imports where import_schedule <> 'ondemand' and enabled == 1");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            n->ModuleRunningCheckIn();

            int remoteImportRowID = q.value("remoteimport_id").toInt();
            QString importName = q.value("import_name").toString().trimmed();
            int projectRowID = q.value("project_id").toInt();
            QString remoteType = q.value("remote_type").toString().trimmed();
            QString remoteURL = q.value("remote_url").toString().trimmed();
            QString remoteToken = q.value("remote_token").toString().trimmed();
            QString importSchedule = q.value("remote_schedule").toString().trimmed();
            int importHour = q.value("remote_time").toInt();
            int importDayOfMonth = q.value("remote_dayofmonth").toInt();
            QStringList importDays = q.value("remote_days").toStringList();

            /* check if there is already an active batch for this remote import */
            QSqlQuery q2;
            q2.prepare("select * from remoteimport_batch where remoteimport_id = :remoteimportid and status <> 'complete'");
            q2.bindValue(":remoteimportid", remoteImportRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            if (q2.size() > 0) {
                n->Log("There is a currently running batch. Skipping creation of new batch");
                continue;
            }
            else {
                n->Log("No currently running batches");
            }

            /* check if we are currently within range of the expected start time */
            if (!IsDateInSchedule(QDateTime::currentDateTime(), importSchedule, importHour, importDayOfMonth, importDays)) {
                n->Log(QString("%1 is NOT within the schedule [%2, %3, %4, %5]").arg(QDateTime::currentDateTime().toString()).arg(importSchedule).arg(importHour).arg(importDayOfMonth).arg(importDays.join("|")));
                continue;
            }
            else {
                n->Log(QString("%1 is within the schedule [%2, %3, %4, %5]").arg(QDateTime::currentDateTime().toString()).arg(importSchedule).arg(importHour).arg(importDayOfMonth).arg(importDays.join("|")));
            }

            /* check if there was a batch that started within the current hour */
            q2.prepare("select * from remoteimport_batch where (hour(start_date) = hour(now()) and date(start_date) = curdate()) and remoteimport_id = :remoteimportid");
            q2.bindValue(":remoteimportid", remoteImportRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            if (q2.size() > 0) {
                n->Log("There are batches that were started within the hour");
                continue;
            }
            else {
                n->Log("No batches that were started within the hour");
            }

            /* now setup a new batch for this remote import */
            q2.prepare("insert into remoteimport_batch (remoteimport_id, start_date, status, next_state) values (:remoteimportid, now(), 'started', '')");
            q2.bindValue(":remoteimportid", remoteImportRowID);
            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
            qint64 batchRowID = q2.lastInsertId().toLongLong();

            /* a batch exists, now we can do the import */
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- IsDateInSchedule ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether a date falls within a previously configured schedule.
 *
 * The schedule start time is determined by hourOfDay. Hourly schedules match
 * when date is within 60 minutes after the scheduled start time, with weekly
 * and monthly schedules applying their additional day constraints.
 *
 * @param date Date and time to test against the schedule.
 * @param scheduleType Schedule frequency. Valid values are "hourly", "daily",
 * "weekly", and "monthly".
 * @param hourOfDay Scheduled start hour in 24-hour time, from 0 to 23.
 * @param dayOfMonth Day of the month required for monthly schedules.
 * @param daysOfWeek List of abbreviated weekday names required for weekly
 * schedules, such as "Sun", "Tue", or "Fri".
 * @return true if date is inside the schedule window and satisfies the schedule
 * constraints, false otherwise.
 */
bool moduleRemoteImport::IsDateInSchedule(QDateTime date, QString scheduleType, int hourOfDay, int dayOfMonth, QStringList daysOfWeek) {

    /* clean and validate inputs */
    scheduleType = scheduleType.trimmed().toLower();

    if (!date.isValid())
        return false;

    if ((hourOfDay < 0) || (hourOfDay > 23))
        return false;

    QStringList scheduleTypes = {"hourly", "daily", "weekly", "monthly"};
    if (!scheduleTypes.contains(scheduleType))
        return false;

    /* check if the date is within one hour of the 'schedule' */
    QDateTime scheduledStart = date;
    scheduledStart.setTime(QTime(hourOfDay, 0, 0));
    qint64 secondsAfterScheduledStart = scheduledStart.secsTo(date);
    bool isInScheduledHour = ((secondsAfterScheduledStart >= 0) && (secondsAfterScheduledStart < 3600));

    /* if we're not in the scheduled hour, then return false */
    if (!isInScheduledHour)
        return false;

    /* we are within the scheduled hour, so return true if the schedule type is hourly or daily */
    if ((scheduleType == "hourly") || (scheduleType == "daily"))
        return true;

    /* we are still within the selected hour, so return true if the date is within one of the specified days of the week */
    if (scheduleType == "weekly") {
        QString dayOfWeek = date.date().toString("ddd");

        for (QString scheduledDay : daysOfWeek) {
            if (scheduledDay.trimmed().compare(dayOfWeek, Qt::CaseInsensitive) == 0)
                return true;
        }

        return false;
    }

    /* we are still within the scheduled hour, so return true if the day-of-month matches */
    if (scheduleType == "monthly")
        return (date.date().day() == dayOfMonth);

    return false;

}


/* ---------------------------------------------------------- */
/* --------- RemoteImportLogEventToString ------------------- */
/* ---------------------------------------------------------- */
QString moduleRemoteImport::RemoteImportLogEventToString(RemoteImportLogEvent event) {
    switch (event) {
        case ConnectionEnd:
            return "ConnectionEnd";
        case ConnectionStart:
            return "ConnectionStart";
        case ImportAnalysis:
            return "ImportAnalysis";
        case ImportEnd:
            return "ImportEnd";
        case ImportStart:
            return "ImportStart";
        case ImportIntervention:
            return "ImportIntervention";
        case ImportObservation:
            return "ImportObservation";
        case ImportPipeline:
            return "ImportPipeline";
        case ImportSeries:
            return "ImportSeries";
        case ImportStudy:
            return "ImportStudy";
        case ImportSubject:
            return "ImportSubject";
    }

    return "";
}


/* ---------------------------------------------------------- */
/* --------- EventResultToString ---------------------------- */
/* ---------------------------------------------------------- */
QString moduleRemoteImport::EventResultToString(EventResult result) {
    switch (result) {
        case Success:
            return "Success";
        case Error:
            return "Error";
        case Warning:
            return "Warning";
        case Neutral:
            return "Neutral";
    }

    return "Neutral";
}


/* ---------------------------------------------------------- */
/* --------- RemoteImportLog -------------------------------- */
/* ---------------------------------------------------------- */
void moduleRemoteImport::RemoteImportLog(qint64 batchRowID, RemoteImportLogEvent event, QString message, EventResult result) {
    QSqlQuery q;
    q.prepare("insert into remoteimport_logs (remoteimportbatch_id, event, result, message) values (:batchid, :event, :result, :message)");
    q.bindValue(":batchid", batchRowID);
    q.bindValue(":event", RemoteImportLogEventToString(event));
    q.bindValue(":result", EventResultToString(result));
    q.bindValue(":message", message);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

}


/* ---------------------------------------------------------- */
/* --------- ImportAvicenna --------------------------------- */
/* ---------------------------------------------------------- */
bool moduleRemoteImport::ImportAvicenna(int remoteImportBatchRowID, QString remoteURL, QString remoteToken) {
    Q_UNUSED(remoteImportBatchRowID)
    Q_UNUSED(remoteURL)
    Q_UNUSED(remoteToken)

    return false;
}


/* ---------------------------------------------------------- */
/* --------- ImportRedCap ----------------------------------- */
/* ---------------------------------------------------------- */
bool moduleRemoteImport::ImportRedCap(int remoteImportBatchRowID, QString remoteURL, QString remoteToken) {
    Q_UNUSED(remoteImportBatchRowID)
    Q_UNUSED(remoteURL)
    Q_UNUSED(remoteToken)

    return false;
}


/* ---------------------------------------------------------- */
/* --------- ImportURL -------------------------------------- */
/* ---------------------------------------------------------- */
bool moduleRemoteImport::ImportURL(int remoteImportBatchRowID, QString remoteURL, QString remoteToken) {
    Q_UNUSED(remoteImportBatchRowID)
    Q_UNUSED(remoteURL)
    Q_UNUSED(remoteToken)

    return false;
}
