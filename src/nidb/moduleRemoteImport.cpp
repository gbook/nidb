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
/**
 * @brief Constructor. Stores the nidb instance pointer.
 * @param a Pointer to the nidb application instance.
 */
moduleRemoteImport::moduleRemoteImport(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleRemoteImport ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Destructor.
 */
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
    n->Log("Checking for pending on-demand remote imports");
    QSqlQuery q;
    q.prepare("select * from remoteimport_batch a left join remote_imports b on a.remoteimport_id = b.remoteimport_id where a.status = 'pending' and a.next_state = 'run' and b.enabled = 1");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    n->Log(QString("Found [%1] pending on-demand remote imports").arg(q.size()));
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
            QString remoteUsername = q.value("remote_username").toString().trimmed();
            int remoteProjectID = q.value("remote_projectid").toInt();
            QString importSchedule = q.value("import_schedule").toString().trimmed();
            int importTime = q.value("import_time").toInt();
            int importDayOfMonth = q.value("import_dayofmonth").toInt();
            QStringList importDays = q.value("import_days").toStringList();
            QString csvType = q.value("csv_type").toString();

            n->Log(QString("Working on import [%1]  batch id [%2]").arg(importName).arg(remoteImportBatchRowID));
            n->Log(QString("  remoteimport_id [%1]  project_id [%2]  remote_type [%3]").arg(remoteImportRowID).arg(projectRowID).arg(remoteType));
            n->Log(QString("  remote_url [%1]  remote_username [%2]  remote_projectid [%3]").arg(remoteURL).arg(remoteUsername).arg(remoteProjectID));
            n->Log(QString("  import_schedule [%1]  import_time [%2]  import_dayofmonth [%3]  import_days [%4]").arg(importSchedule).arg(importTime).arg(importDayOfMonth).arg(importDays.join("|")));

            SetBatchStatus(remoteImportBatchRowID, "started");

            /* get the mapping */
            n->Log(QString("Getting import mapping for project_id [%1]").arg(projectRowID));
            QList <RemoteImportMapping> mapping = GetImportMapping(projectRowID);
            n->Log(QString("Found [%1] mapping rules").arg(mapping.size()));

            /* run the import */
            n->Log(QString("Running import for remote_type [%1]").arg(remoteType));
            if (remoteType == "avicenna") {
                ImportAvicenna(remoteImportBatchRowID, remoteURL, remoteToken, remoteUsername, remoteProjectID, mapping);
            }
            else if (remoteType == "redcap") {

            }
            else if (remoteType == "csv") {
                ImportCSV(remoteImportBatchRowID, csvType, mapping);
            }
            else {
                n->Log(QString("Unknown remote_type [%1], skipping").arg(remoteType));
            }
            n->Log(QString("Finished import for batch id [%1]").arg(remoteImportBatchRowID));

            SetBatchStatus(remoteImportBatchRowID, "complete");
        }
    }
    else {
        n->Log("No pending on-demand batches found");
    }

    /* get list of remote imports that are not on-demand */
    q.prepare("select * from remote_imports where import_schedule <> 'ondemand' and enabled = 1");
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
            QString importSchedule = q.value("import_schedule").toString().trimmed();
            int importHour = q.value("import_time").toInt();
            int importDayOfMonth = q.value("import_dayofmonth").toInt();
            QStringList importDays = q.value("import_days").toStringList();

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

            /* get the mapping */

            /* run the import */
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
/* --------- GetImportMapping ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Fetches the import mapping rules for a given project.
 * @param projectRowID Database row ID of the project.
 * @return List of RemoteImportMapping rules configured for the project.
 */
QList <RemoteImportMapping> moduleRemoteImport::GetImportMapping(int projectRowID) {
    QList<RemoteImportMapping> mappings;

    QSqlQuery q;
    q.prepare("select * from remoteimport_mapping where project_id = :projectRowID");
    q.bindValue(":projectRowID", projectRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            RemoteImportMapping mapping;

            mapping.sourceType = q.value("source_type").toString();
            if (mapping.sourceType == "avicenna") {
                mapping.avicenna.survey = q.value("avicenna_survey").toInt();
                mapping.avicenna.question = q.value("avicenna_question").toInt();
                mapping.avicenna.variable = q.value("avicenna_variable").toString();
                mapping.avicenna.datatype = q.value("avicenna_datatype").toString();
                mapping.avicenna.variableCount = q.value("avicenna_variableCount").toString();
            }
            if (mapping.sourceType == "redcap") {
                mapping.redcap.arm = q.value("redcap_arm").toString();
                mapping.redcap.arm = q.value("redcap_arm").toString();
                mapping.redcap.arm = q.value("redcap_arm").toString();
                mapping.redcap.arm = q.value("redcap_arm").toString();
                mapping.redcap.arm = q.value("redcap_arm").toString();
                mapping.redcap.arm = q.value("redcap_arm").toString();
            }
            mapping.instrumentRowID = q.value("nidb_instrument").toInt();
            mapping.instrumentItemRowID = q.value("nidb_variable").toInt();
            mappings.append(mapping);
        }
    }
    return mappings;
}


/* ---------------------------------------------------------- */
/* --------- RemoteImportLogEventToString ------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Converts a RemoteImportLogEvent enum value to its string representation.
 * @param event The event enum value to convert.
 * @return String name of the event.
 */
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
/**
 * @brief Converts an EventResult enum value to its string representation.
 * @param result The result enum value to convert.
 * @return String name of the result, defaulting to "Neutral" for unrecognized values.
 */
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
/**
 * @brief Inserts a log entry for a remote import batch event into the database.
 * @param batchRowID Database row ID of the remoteimport_batch record.
 * @param event The type of import event being logged.
 * @param message Human-readable description of the event.
 * @param result Outcome of the event (Success, Error, Warning, or Neutral).
 */
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
/**
 * @brief Requests a participation export from Avicenna, polls until ready, downloads
 * the CSV, and extracts the list of subject IDs using the configured mapping rules.
 * @param remoteImportBatchRowID Database row ID of the remoteimport_batch record.
 * @param remoteURL Base URL of the Avicenna API endpoint.
 * @param remoteToken API token for authenticating with Avicenna.
 * @param remoteUsername API username for authenticating with Avicenna.
 * @param remoteProjectID Avicenna study ID to export from.
 * @param mapping List of import mapping rules for this project.
 * @return true on success, false otherwise.
 */
bool moduleRemoteImport::ImportAvicenna(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QString remoteUsername, int remoteProjectID, QList <RemoteImportMapping> mapping) {

    // URL should be https://avicennaresearch.com/api/v1/filter/export/

    // JSON string within the curl command should be
    // '{
    //    "study_id": 4859,
    //    "site_ssid": null,
    //    "filter": "PARTICIPATION",
    //    "rule_set": {
    //      "query": {},
    //      "sort_by": [],
    //      "columns": ["participant_id", "start_time", "end_time", "status_id"]
    //    }
    //  }'

    n->Log(QString("ImportAvicenna() called  batchid [%1]  url [%2]  username [%3]  remote_projectid [%4]  mapping rules [%5]").arg(remoteImportBatchRowID).arg(remoteURL).arg(remoteUsername).arg(remoteProjectID).arg(mapping.size()));
    SetBatchStatus(remoteImportBatchRowID, "running");

    /* initialize the JSON object - this curl string and JSON are static, so this type of initialization works well here */
    QJsonObject root {
        {"study_id",    remoteProjectID},
        {"site_ssid",   QJsonValue()},
        {"filter",      "PARTICIPATION"},
        {"rule_set",    QJsonObject{
            {"query",       QJsonObject{} },
            {"sort_by",     QJsonArray{} },
            {"columns",     QJsonArray{"participant_id", "start_time", "end_time", "status_id"} }
        } }
    };

    /* convert JSON object to QString */
    QJsonDocument doc(root);
    QString json = QString::fromUtf8(doc.toJson(QJsonDocument::Compact));
    n->Log(QString("Request JSON: %1").arg(json));

    /* build curl string to get list of subjects */
    QString curlStr = QString("curl -X POST %1 -H 'Authorization: ApiKey %2:%3' --data-raw '%4'").arg(remoteURL).arg(remoteUsername).arg(remoteToken).arg(json);
    n->Log(curlStr);

    n->Log("Sending export request to Avicenna");
    QString result = SystemCommand(curlStr, false);
    n->Log(QString("Avicenna export request response: %1").arg(result));
    int avicennaExportID = GetAvicennaExportID(result);
    n->Log(QString("Avicenna export ID: [%1]").arg(avicennaExportID));

    if (avicennaExportID <= 0) {
        n->Log("Invalid export ID returned from Avicenna. Aborting.");
        SetBatchStatus(remoteImportBatchRowID, "error");
        return false;
    }

    /* Wait 20 seconds (3x) for Avicenna to do whatever it needs to do... then get the export status and the export URL */
    bool ready = false;
    int i = 0;
    QString url;
    while (!ready) {
        n->Log(QString("Polling Avicenna export status, attempt [%1 of 3]").arg(i + 1));
        SetBatchStatus(remoteImportBatchRowID, "waiting", avicennaExportID);
        QThread::sleep(20);
        QString status = GetAvicennaExportStatus(remoteProjectID, avicennaExportID, remoteUsername, remoteToken, url);
        n->Log(QString("Avicenna export status [%1]  url [%2]").arg(status).arg(url));

        /* only allow this to run 3 times before giving up */
        if ((status == "Success") || (i >= 3))
            ready = true;

        i++;
    }

    /* if we get a URL, then download it */
    QString path = QString("%1/%2.csv").arg(n->cfg["tmpdir"]).arg(GenerateRandomString(20));
    n->Log(QString("Export URL [%1]  download path [%2]").arg(url).arg(path));
    if (url == "") {
        n->Log("No export URL returned from Avicenna. Setting batch status to error.");
        SetBatchStatus(remoteImportBatchRowID, "error");
    }
    else {
        /* download avicenna file */
        n->Log("Downloading Avicenna export file");
        if (DownloadAvicennaExport(remoteProjectID, remoteUsername, remoteToken, url, path)) {
            n->Log("Download succeeded");
            SetBatchStatus(remoteImportBatchRowID, "complete");
        }
        else {
            n->Log("Download failed");
            SetBatchStatus(remoteImportBatchRowID, "error");
        }
    }

    /* we should now have the list of subjects in that csv, so let's parse the csv */
    n->Log(QString("Parsing CSV from [%1]").arg(path));
    QString csv = ReadTextFileIntoString(path);
    n->Log(QString("CSV length [%1] chars").arg(csv.length()));
    QList<int> subjectids = GetAvicennaSubjectsFromCSV(csv);
    n->Log(QString("Found [%1] subject IDs: [%2]").arg(subjectids.size()).arg(JoinIntArray(subjectids, ",")));

    if (QFile::exists(path))
        QFile::remove(path);

    n->Log("ImportAvicenna() complete");
    return false;
}


/* ---------------------------------------------------------- */
/* --------- GetAvicennaExportID ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parses the JSON response from an Avicenna export request and returns the export ID.
 * @param jsonStr Raw JSON string returned by the Avicenna export API.
 * @return The study-specific export ID, or 0 if parsing fails or the field is absent.
 */
int moduleRemoteImport::GetAvicennaExportID(QString jsonStr) {

    int id = 0;
    QJsonParseError parseError;
    QJsonDocument doc = QJsonDocument::fromJson(jsonStr.toUtf8(), &parseError);

    if (parseError.error != QJsonParseError::NoError) {
        qWarning() << "JSON parse error:" << parseError.errorString();
        return 0;
    }

    id = doc.object().value("study_specific_id").toInt();

    return id;
}


/* ---------------------------------------------------------- */
/* --------- GetAvicennaExportStatus ------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Polls the Avicenna API for the status of a previously requested export.
 * @param remoteProjectID Avicenna study ID.
 * @param remoteExportID Export ID returned by the initial export request.
 * @param remoteUsername API username for authenticating with Avicenna.
 * @param remoteToken API token for authenticating with Avicenna.
 * @param exportURL Set to the download URL when status is "Success", otherwise cleared.
 * @return Status label string (e.g. "Success", "Processing"), or empty string if not found or on error.
 */
QString moduleRemoteImport::GetAvicennaExportStatus(int remoteProjectID, int remoteExportID, QString remoteUsername, QString remoteToken, QString &exportURL) {

    QString curlStr = QString("curl 'https://avicennaresearch.com/api/v1/filter/export/?study_id=%1' -H 'Authorization: ApiKey %2:%3'").arg(remoteProjectID).arg(remoteUsername).arg(remoteToken);
    n->Log(curlStr);
    QString result = SystemCommand(curlStr, false);

    exportURL.clear();

    QJsonParseError parseError;
    QJsonDocument doc = QJsonDocument::fromJson(result.toUtf8(), &parseError);
    if (parseError.error != QJsonParseError::NoError) {
        n->Log(QString("JSON parse error: %1").arg(parseError.errorString()));
        return "";
    }

    const QJsonArray exports = doc.array();
    for (const QJsonValue &val : exports) {
        const QJsonObject obj = val.toObject();
        if (obj.value("study_specific_id").toInt() != remoteExportID)
            continue;

        const QString statusLabel = obj.value("status").toObject().value("label").toString();
        exportURL = obj.value("content").toString(); // Empty if null or not Success
        return statusLabel;
    }

    return ""; // Not found
}


/* ---------------------------------------------------------- */
/* --------- DownloadAvicennaExport ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Downloads an Avicenna export file to a local path.
 * @param remoteProjectID Avicenna study ID (unused in the request but retained for context).
 * @param remoteUsername API username for authenticating with Avicenna.
 * @param remoteToken API token for authenticating with Avicenna.
 * @param url Download URL for the export file.
 * @param path Local filesystem path to write the downloaded file to.
 * @return true if the file exists after the download attempt, false otherwise.
 */
bool moduleRemoteImport::DownloadAvicennaExport(int remoteProjectID, QString remoteUsername, QString remoteToken, QString url, QString path) {

    QString cmd = QString("curl '%1' -H 'Authorization: ApiKey %2:%3' --output %4").arg(url).arg(remoteUsername).arg(remoteToken).arg(path);
    QString result = SystemCommand(cmd, false);

    if (QFile::exists(path))
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- GetAvicennaSubjectsFromCSV --------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parses a CSV string from an Avicenna export and extracts the list of participant IDs.
 * @param csv CSV content with a header row containing an "ID" column.
 * @return List of integer participant IDs found in the CSV.
 */
QList<int> moduleRemoteImport::GetAvicennaSubjectsFromCSV(QString csv) {
    QList<int> subjectids;

    indexedHash table;
    QStringList columns;
    QString m;
    if (ParseCSV(csv, table, columns, m)) {
        if (columns.contains("id")) {
            for (int i=0; i<table.size(); i++) {
                QString idStr = table[i]["id"];
                subjectids.append(idStr.toInt());
            }
        }
    }

    return subjectids;
}


/* ---------------------------------------------------------- */
/* --------- ImportRedCap ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Imports data from a REDCap remote source. Not yet implemented.
 * @param remoteImportBatchRowID Database row ID of the remoteimport_batch record.
 * @param remoteURL Base URL of the REDCap API endpoint.
 * @param remoteToken API token for authenticating with REDCap.
 * @param mapping List of import mapping rules for this project.
 * @return true on success, false otherwise.
 */
bool moduleRemoteImport::ImportRedCap(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QList <RemoteImportMapping> mapping) {
    Q_UNUSED(remoteImportBatchRowID)
    Q_UNUSED(remoteURL)
    Q_UNUSED(remoteToken)

    return false;
}


/* ---------------------------------------------------------- */
/* --------- ImportURL -------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Imports data from a remote URL source. Not yet implemented.
 * @param remoteImportBatchRowID Database row ID of the remoteimport_batch record.
 * @param remoteURL URL to import data from.
 * @param remoteToken Authentication token for the remote URL.
 * @param mapping List of import mapping rules for this project.
 * @return true on success, false otherwise.
 */
bool moduleRemoteImport::ImportURL(int remoteImportBatchRowID, QString remoteURL, QString remoteToken, QList <RemoteImportMapping> mapping) {
    Q_UNUSED(remoteImportBatchRowID)
    Q_UNUSED(remoteURL)
    Q_UNUSED(remoteToken)

    return false;
}


/* ---------------------------------------------------------- */
/* --------- ImportCSV -------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Imports data from a local CSV file. Not yet implemented.
 * @param remoteImportBatchRowID Database row ID of the remoteimport_batch record.
 * @param mapping List of import mapping rules for this project.
 * @return true on success, false otherwise.
 */
bool moduleRemoteImport::ImportCSV(int remoteImportBatchRowID, QString csvType, QList <RemoteImportMapping> mapping) {
    Q_UNUSED(remoteImportBatchRowID)

    return false;
}


/* ---------------------------------------------------------- */
/* --------- SetBatchStatus --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Updates the status of a remoteimport_batch record in the database.
 * @param batchRowID Database row ID of the remoteimport_batch record.
 * @param status New status value. Valid values are "started", "running", "waiting", and "complete".
 * @param remoteExportID Optional Avicenna export ID to store with the batch record. Only written when status is "started" and value is >= 0.
 */
void moduleRemoteImport::SetBatchStatus(qint64 batchRowID, QString status, int remoteExportID) {
    QSqlQuery q;
    if (status == "started") {
        if (remoteExportID >= 0) {
            q.prepare("update remoteimport_batch set start_date = now(), status = 'started', next_state = '', remote_exportid = :exportid where remoteimportbatch_id = :batchid");
            q.bindValue(":exportid", remoteExportID);
            q.bindValue(":batchid", batchRowID);
            n->Log(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
        }
        else {
            q.prepare("update remoteimport_batch set start_date = now(), status = 'started', next_state = '' where remoteimportbatch_id = :batchid");
            q.bindValue(":batchid", batchRowID);
            n->Log(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
        }
    }
    else if (status == "running") {
        q.prepare("update remoteimport_batch set status = 'running', next_state = '' where remoteimportbatch_id = :batchid");
        q.bindValue(":batchid", batchRowID);
        n->Log(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
    }
    else if (status == "waiting") {
        q.prepare("update remoteimport_batch set status = 'waiting', next_state = '' where remoteimportbatch_id = :batchid");
        q.bindValue(":batchid", batchRowID);
        n->Log(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
    }
    else if (status == "complete") {
        q.prepare("update remoteimport_batch set end_date = now(), status = 'complete', next_state = '' where remoteimportbatch_id = :batchid");
        q.bindValue(":batchid", batchRowID);
        n->Log(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
    }
    else if (status == "error") {
        q.prepare("update remoteimport_batch set end_date = now(), status = 'error', next_state = '' where remoteimportbatch_id = :batchid");
        q.bindValue(":batchid", batchRowID);
        n->Log(n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__));
    }
}
