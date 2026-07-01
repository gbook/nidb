/* ------------------------------------------------------------------------------
  NIDB observation.cpp
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

#include "observation.h"
#include "study.h"
#include <QDateTime>
#include <QFile>
#include <QFileInfo>
#include <QMimeDatabase>
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- observation ------------------------------------ */
/* ---------------------------------------------------------- */
/** @brief Default constructor. Object is not valid until observationRowID and nidb pointer are set. */
observation::observation() {

}


/* ---------------------------------------------------------- */
/* --------- observation ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Constructs and loads an observation by ID.
 * @param id    Primary key of the observations row to load.
 * @param a     Pointer to the nidb instance (database/logging).
 * @param loadLinked  If true, also loads linked instrument, survey, metadata, and file metadata.
 */
observation::observation(qint64 id, nidb *a, bool loadLinked)
{
    n = a;
    observationRowID = id;
    loadLinkedData = loadLinked;
    LoadObservationInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadObservationInfo ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Loads all scalar fields for this observation from the database.
 *        When loadLinkedData is true, also populates linked instrument/item,
 *        survey, metadata key-value pairs, and file metadata (blob deferred to LoadFile()).
 */
void observation::LoadObservationInfo() {

    QStringList msgs;

    if (observationRowID < 1) {
        msgs << "Invalid observation ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        q.prepare("select * from observations a left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.observation_id = :observationid");
        q.bindValue(":observationid", observationRowID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid observation ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();

            dateObservationEnd      = q.value("observation_enddate").toDateTime();
            dateObservationStart    = q.value("observation_startdate").toDateTime();
            dateRecordCreate        = q.value("observation_createdate").toDateTime();
            dateRecordEntry         = q.value("observation_entrydate").toDateTime();
            dateRecordModify        = q.value("observation_modifydate").toDateTime();
            enrollmentRowID         = q.value("enrollment_id").toInt();
            instrumentItemRowID     = q.value("instrumentitem_id").toInt();
            observationDescription  = q.value("observation_desc").toString();
            observationDuration     = q.value("observation_duration").toInt();
            observationInstrument   = q.value("observation_instrument").toString();
            observationName         = q.value("observation_name").toString();
            observationNotes        = q.value("observation_notes").toString();
            observationRater        = q.value("observation_rater").toString();
            observationTZOffset     = q.value("observation_tz_offset").toString();
            observationValue        = q.value("observation_value").toString();
            projectRowID            = q.value("project_id").toInt();
            remoteBatchRowID        = q.value("remotebatch_id").toInt();
            subjectRowID            = q.value("subject_id").toInt();
            subjectUID              = q.value("UID").toString();
            surveyRowID             = q.value("observationsurvey_id").toInt();
            fileRowID               = q.value("observation_fileid").toInt();

            isValid = true;

            /* get data from linked tables */
            if (loadLinkedData) {

                /* load instrument/item data */
                if (instrumentItemRowID > 0) {
                    q.prepare("select * from instrument_items a left join instruments b on a.instrument_id = b.instrument_id where a.instrumentitem_id = :itemid");
                    q.bindValue(":itemid", instrumentItemRowID);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                    if (q.size() > 0) {
                        q.first();
                        hasLinkedInstrument = true;
                        hasLinkedInstrumentItem = true;
                        linkedInstrumentName = q.value("instrument_name").toString();
                        linkedInstrumentNotes = q.value("instrument_notes").toString();
                        linkedInstrumentItemName = q.value("item_name").toString();
                        linkedInstrumentItemOrder = q.value("item_order").toInt();
                        linkedInstrumentItemNotes = q.value("item_notes").toString();
                        linkedInstrumentItemType = q.value("item_type").toString();

                        /* get item value map */
                        if (linkedInstrumentItemType == "enum") {
                            QSqlQuery q2;
                            q2.prepare("select * from instrumentitem_map where instrumentitem_id = :itemid");
                            q2.bindValue(":itemid", instrumentItemRowID);
                            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
                            if (q2.size() > 0) {
                                while (q2.next()) {
                                    valueMap[q2.value("int_val").toInt()] = q2.value("string_val").toString();
                                }
                            }

                        }
                        /* get timeseries data */
                        if (linkedInstrumentItemType == "timeseries") {
                            /* load timeseries */
                            QSqlQuery q2;
                            q2.prepare("select * from timeseries where observation_id = :obsid");
                            q2.bindValue(":obsid", observationRowID);
                            n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
                            if (q2.size() > 0) {
                                while (q2.next()) {
                                    QDateTime time = q2.value("time").toDateTime();
                                    if (!q2.value("value_double").isNull())
                                        timeseriesDouble[time] = q2.value("value_double").toDouble();

                                    if (!q2.value("value_int").isNull())
                                        timeseriesInt[time] = q2.value("value_int").toInt();

                                    if (!q2.value("value_string").isNull())
                                        timeseriesString[time] = q2.value("value_string").toString();
                                }
                            }
                        }
                    }
                }

                /* load survey data */
                if (surveyRowID > 0) {
                    q.prepare("select * from observation_surveys where survey_id = :surveyid");
                    q.bindValue(":surveyid", surveyRowID);
                    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                    if (q.size() > 0) {
                        q.first();
                        hasSurvey = true;
                        linkedSurveyStartDate = q.value("survey_startdate").toDateTime();
                        linkedSurveyEndDate = q.value("survey_enddate").toDateTime();
                        linkedSurveyNotes = q.value("survey_notes").toString();
                        linkedSurveyVisit = q.value("survey_visit").toString();
                        linkedSurveyExperimenter = q.value("survey_experimenter").toString();
                        linkedSurveyRater = q.value("survey_rater").toString();
                        linkedSurveyEntryDate = q.value("survey_entrydate").toDateTime();
                    }
                }

                /* load meta data */
                q.prepare("select * from observation_meta where observation_id = :obsid");
                q.bindValue(":obsid", observationRowID);
                n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                if (q.size() > 0) {
                    hasMetadata = true;
                    while (q.next()) {
                        QString key = q.value("variable").toString();
                        QString value = q.value("value").toString();
                        metadata[key] = value;
                    }
                }

                /* load file metadata (blob fetched on demand via LoadFile()) */
                if (fileRowID > 0) {
                    QSqlQuery qf;
                    qf.prepare("select file_name, file_contenttype, file_size, file_date from files where file_id = :fileid");
                    qf.bindValue(":fileid", fileRowID);
                    n->SQLQuery(qf, __FUNCTION__, __FILE__, __LINE__);
                    if (qf.size() > 0) {
                        qf.first();
                        hasFile          = true;
                        fileName         = qf.value("file_name").toString();
                        fileContentType  = qf.value("file_contenttype").toString();
                        fileSize         = qf.value("file_size").toLongLong();
                        fileDate         = qf.value("file_date").toDateTime();
                    }
                }

            } /* end loading linked data */

        }
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PopulateLinkedInstrument ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Loads the instrument and instrument item linked to this observation via instrumentItemRowID.
 * @return true if a matching instrument item was found and populated, false otherwise.
 */
bool observation::PopulateLinkedInstrument() {
    /* load instrument/item data */
    if (instrumentItemRowID > 0) {
        QSqlQuery q;
        q.prepare("select * from instrument_items a left join instruments b on a.instrument_id = b.instrument_id where a.instrumentitem_id = :itemid");
        q.bindValue(":itemid", instrumentItemRowID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() > 0) {
            q.first();
            hasLinkedInstrument = true;
            hasLinkedInstrumentItem = true;
            linkedInstrumentName = q.value("instrument_name").toString();
            linkedInstrumentNotes = q.value("instrument_notes").toString();
            linkedInstrumentItemName = q.value("item_name").toString();
            linkedInstrumentItemOrder = q.value("item_order").toInt();
            linkedInstrumentItemNotes = q.value("item_notes").toString();
            linkedInstrumentItemType = q.value("item_type").toString();
            return true;
        }
    }

    return false;
}


/* ---------------------------------------------------------- */
/* --------- PrintObservationInfo --------------------------- */
/* ---------------------------------------------------------- */
/** @brief Writes all observation fields to the nidb log for debugging. */
void observation::PrintObservationInfo() {
    QString output = QString("***** Observation - [%1] *****\n").arg(observationRowID);

    output += QString("   dateObservationEnd: [%1]\n").arg(dateObservationEnd.toString());
    output += QString("   dateObservationStart: [%1]\n").arg(dateObservationStart.toString());
    output += QString("   dateRecordCreate: [%1]\n").arg(dateRecordCreate.toString());
    output += QString("   dateRecordEntry: [%1]\n").arg(dateRecordEntry.toString());
    output += QString("   dateRecordModify: [%1]\n").arg(dateRecordModify.toString());
    output += QString("   enrollmentRowID: [%1]\n").arg(enrollmentRowID);
    output += QString("   instrumentItemRowID: [%1]\n").arg(instrumentItemRowID);
    output += QString("   observationDescription: [%1]\n").arg(observationDescription);
    output += QString("   observationDuration: [%1]\n").arg(observationDuration);
    output += QString("   observationInstrument: [%1]\n").arg(observationInstrument);
    output += QString("   observationName: [%1]\n").arg(observationName);
    output += QString("   observationNotes: [%1]\n").arg(observationNotes);
    output += QString("   observationRater: [%1]\n").arg(observationRater);
    output += QString("   observationValue: [%1]\n").arg(observationValue);
    output += QString("   projectRowID: [%1]\n").arg(projectRowID);
    output += QString("   remoteBatchRowID: [%1]\n").arg(remoteBatchRowID);
    output += QString("   subjectRowID: [%1]\n").arg(subjectRowID);
    output += QString("   subjectUID: [%1]\n").arg(subjectUID);
    output += QString("   surveyRowID: [%1]\n").arg(surveyRowID);
    output += QString("   fileRowID: [%1]\n").arg(fileRowID);
    if (hasFile) {
        output += QString("   fileName: [%1]\n").arg(fileName);
        output += QString("   fileContentType: [%1]\n").arg(fileContentType);
        output += QString("   fileSize: [%1]\n").arg(fileSize);
        output += QString("   fileDate: [%1]\n").arg(fileDate.toString());
    }

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Converts this observation to a squirrelObservation for export.
 * @param databaseUUID  UUID of the database, passed through to the squirrel object.
 * @return Populated squirrelObservation instance.
 */
squirrelObservation observation::GetSquirrelObject(QString databaseUUID) {
    squirrelObservation sqrl(databaseUUID);

    sqrl.DateEnd         = dateObservationEnd;
    sqrl.DateRecordCreate = dateRecordCreate;
    sqrl.DateRecordEntry = dateRecordEntry;
    sqrl.DateRecordModify = dateRecordModify;
    sqrl.DateStart       = dateObservationStart;
    sqrl.Description     = observationDescription;
    sqrl.Duration        = observationDuration;
    sqrl.InstrumentName  = observationInstrument;
    sqrl.ObservationName = observationName;
    sqrl.Notes           = observationNotes;
    sqrl.Rater           = observationRater;
    sqrl.Value           = observationValue;

    return sqrl;
}


/* ---------------------------------------------------------- */
/* --------- AddToDatabase ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Persists this observation to the database (INSERT or UPDATE).
 *        Existence is checked by (enrollment_id, observation_name, observation_startdate).
 *        An UPDATE is performed when the existing value matches or is blank; otherwise a new
 *        row is inserted. Any metadata key-value pairs are written via INSERT IGNORE.
 *        NOTE: MariaDB 10.3 (RHEL8 minimum) does not support unique indexes on text columns,
 *        so duplicate detection is done manually rather than relying on ON DUPLICATE KEY.
 * @return true on success, false if enrollmentRowID is unset or the query failed.
 */
bool observation::AddToDatabase() {
    //n->Log(QString("AddToDatabase()  enrollmentRowID (%1)  name (%2)  value (%3)  startDate (%4 UTC)  metadata (%5)")
    //       .arg(enrollmentRowID).arg(observationName).arg(observationValue).arg(dateObservationStart.toUTC().toString("yyyy-MM-dd HH:mm:ss")).arg(metadata.size()));

    if (enrollmentRowID < 1) {
        msg = "enrollmentRowID must be set before calling AddToDatabase()";
        isValid = false;
        n->Log(QString("AddToDatabase() failed: %1").arg(msg));
        return false;
    }

    bool update(false); /* 'update' if true, 'insert' if false */
    QSqlQuery q;

    /* check if this observation exists by (enrollment_id, observation_name, observation_startdate) */
    q.prepare("select observation_id, observation_value from observations where enrollment_id = :enrollmentid and observation_name = :name and observation_startdate = :startdate");
    q.bindValue(":enrollmentid",    enrollmentRowID);
    q.bindValue(":name",            observationName);
    q.bindValue(":startdate",       dateObservationStart.toUTC().toString("yyyy-MM-dd HH:mm:ss"));
    //n->Log(QString("  existence check  startdate [%1]").arg(dateObservationStart.toUTC().toString("yyyy-MM-dd HH:mm:ss")));
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    //n->Log(QString("  existence check returned [%1] rows").arg(q.size()));
    if (q.size() > 0) {
        q.first();
        observationRowID = q.value("observation_id").toLongLong();
        QString val = q.value("observation_value").toString();
        //n->Log(QString("  existing row  observationRowID [%1]  existing value [%2]").arg(observationRowID).arg(val));
        /* update if this existing value is blank or it already equals what we're trying to insert */
        if ((observationValue == val) || (val == "")) {
            /* update the other columns */
            update = true;
        }
        //n->Log(QString("  update [%1]").arg(update));
    }

    if (update) {
        //n->Log(QString("  updating observationRowID [%1]").arg(observationRowID));
        q.prepare("update observations set instrumentitem_id = :instrumentitemid, observationsurvey_id = :surveyid, remotebatch_id = :remotebatchid, observation_fileid = :fileid, observation_notes = :notes, observation_instrument = :instrument, observation_desc = :desc, observation_rater = :rater, observation_value = :value, observation_enddate = :enddate, observation_tz_offset = :tzoffset, observation_duration = :duration where observation_id = :observationid");
        q.bindValue(":observationid",    observationRowID);
        q.bindValue(":instrumentitemid", instrumentItemRowID > 0 ? QVariant(instrumentItemRowID) : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":surveyid",        surveyRowID > 0         ? QVariant(surveyRowID)         : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":remotebatchid",   remoteBatchRowID > 0    ? QVariant(remoteBatchRowID)    : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":fileid",          fileRowID > 0           ? QVariant(fileRowID)           : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":notes",           observationNotes);
        q.bindValue(":instrument",      observationInstrument);
        q.bindValue(":desc",            observationDescription);
        q.bindValue(":rater",           observationRater);
        q.bindValue(":value",           observationValue);
        q.bindValue(":enddate",         dateObservationEnd.isValid() ? QVariant(dateObservationEnd.toUTC().toString("yyyy-MM-dd HH:mm:ss")) : QVariant(QMetaType::fromType<QString>()));
        q.bindValue(":tzoffset",        observationTZOffset.isEmpty() ? QVariant(QMetaType::fromType<QString>()) : QVariant(observationTZOffset));
        q.bindValue(":duration",        observationDuration > 0     ? QVariant(observationDuration) : QVariant(QMetaType::fromType<int>()));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        isValid = (observationRowID > 0);
    }
    else {
        //n->Log("  inserting new observation row");
        q.prepare("insert into observations (enrollment_id, instrumentitem_id, observationsurvey_id, remotebatch_id, observation_fileid, observation_name, observation_notes, observation_instrument, observation_desc, observation_rater, observation_value, observation_startdate, observation_enddate, observation_tz_offset, observation_duration, observation_entrydate, observation_createdate) values (:enrollmentid, :instrumentitemid, :surveyid, :remotebatchid, :fileid, :name, :notes, :instrument, :desc, :rater, :value, :startdate, :enddate, :tzoffset, :duration, :entrydate, :createdate)");
        q.bindValue(":enrollmentid",    enrollmentRowID);
        q.bindValue(":instrumentitemid", instrumentItemRowID > 0 ? QVariant(instrumentItemRowID) : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":surveyid",        surveyRowID > 0         ? QVariant(surveyRowID)         : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":remotebatchid",   remoteBatchRowID > 0    ? QVariant(remoteBatchRowID)    : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":fileid",          fileRowID > 0           ? QVariant(fileRowID)           : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":name",            observationName);
        q.bindValue(":notes",           observationNotes);
        q.bindValue(":instrument",      observationInstrument);
        q.bindValue(":desc",            observationDescription);
        q.bindValue(":rater",           observationRater);
        q.bindValue(":value",           observationValue);
        q.bindValue(":startdate",       dateObservationStart.toUTC().toString("yyyy-MM-dd HH:mm:ss"));
        q.bindValue(":enddate",         dateObservationEnd.isValid() ? QVariant(dateObservationEnd.toUTC().toString("yyyy-MM-dd HH:mm:ss")) : QVariant(QMetaType::fromType<QString>()));
        q.bindValue(":tzoffset",        observationTZOffset.isEmpty() ? QVariant(QMetaType::fromType<QString>()) : QVariant(observationTZOffset));
        q.bindValue(":duration",        observationDuration > 0     ? QVariant(observationDuration) : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":entrydate",       dateRecordEntry.isValid()   ? QVariant(dateRecordEntry)   : QVariant(QMetaType::fromType<QDateTime>()));
        q.bindValue(":createdate",      dateRecordCreate.isValid()  ? QVariant(dateRecordCreate)  : QVariant(QMetaType::fromType<QDateTime>()));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        observationRowID = q.lastInsertId().toLongLong();
        isValid = (observationRowID > 0);
        //n->Log(QString("  inserted observationRowID [%1]").arg(observationRowID));
    }

    /* add metadata if there is any */
    if (metadata.size() > 0) {
        //n->Log(QString("  inserting [%1] metadata key-value pairs for observationRowID [%2]").arg(metadata.size()).arg(observationRowID));
        q.prepare("insert ignore into observation_meta (observation_id, variable, value) values (?, ?, ?)");

        QVariantList ids;
        QVariantList variables;
        QVariantList values;
        for (auto it = metadata.keyValueBegin(); it != metadata.keyValueEnd(); ++it) {
            ids << observationRowID;
            variables << it->first;
            values << it->second;
            //n->Log(QString("metadata: %1, %2, %3").arg(observationRowID).arg(it->first).arg(it->second));
        }
        q.addBindValue(ids);
        q.addBindValue(variables);
        q.addBindValue(values);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, false, true);
    }

    //n->Log(QString("AddToDatabase() returning  isValid [%1]  observationRowID [%2]").arg(isValid).arg(observationRowID));
    return isValid;
}


/* ---------------------------------------------------------- */
/* --------- LoadFile --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Fetches the full file blob from the files table into fileBlob.
 *        File metadata (fileName, fileContentType, fileSize, fileDate) is also refreshed.
 *        Call this only when the blob content is actually needed; metadata is loaded
 *        automatically by LoadObservationInfo() when loadLinkedData is true.
 * @return true on success, false if fileRowID is unset or the row was not found.
 */
bool observation::LoadFile() {
    if (fileRowID < 1) {
        msg = "No file linked to this observation";
        return false;
    }

    QSqlQuery q;
    q.prepare("select file_name, file_contenttype, file_blob, file_size, file_date from files where file_id = :fileid");
    q.bindValue(":fileid", fileRowID);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1) {
        msg = QString("File row %1 not found").arg(fileRowID);
        return false;
    }

    q.first();
    fileName        = q.value("file_name").toString();
    fileContentType = q.value("file_contenttype").toString();
    fileBlob        = q.value("file_blob").toByteArray();
    fileSize        = q.value("file_size").toLongLong();
    fileDate        = q.value("file_date").toDateTime();
    hasFile         = true;
    return true;
}


/* ---------------------------------------------------------- */
/* --------- SaveFile --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Reads a file from disk and stores it as a blob in the files table, linked to this observation.
 *        The filename and MIME type are derived from filePath. If fileRowID is already set,
 *        the existing files row is updated in place; otherwise a new row is inserted and
 *        observation_fileid on the observations row is updated to point to it.
 * @param filePath  Absolute path to the file to read and store.
 * @return true on success, false if the file cannot be opened or the insert failed.
 */
bool observation::SaveFile(const QString &filePath) {
    QFile f(filePath);
    if (!f.open(QIODevice::ReadOnly)) {
        msg = QString("Cannot open file: %1").arg(filePath);
        return false;
    }
    QByteArray data = f.readAll();
    f.close();

    QFileInfo fi(filePath);
    QString name = fi.fileName();
    QString contentType = QMimeDatabase().mimeTypeForFile(fi).name();

    QSqlQuery q;

    if (fileRowID > 0) {
        q.prepare("update files set file_name = :name, file_contenttype = :contenttype, file_blob = :blob, file_size = :size, file_date = now() where file_id = :fileid");
        q.bindValue(":fileid",      fileRowID);
        q.bindValue(":name",        name);
        q.bindValue(":contenttype", contentType);
        q.bindValue(":blob",        data);
        q.bindValue(":size",        data.size());
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }
    else {
        q.prepare("insert into files (file_name, file_contenttype, file_blob, file_size, file_date) values (:name, :contenttype, :blob, :size, now())");
        q.bindValue(":name",        name);
        q.bindValue(":contenttype", contentType);
        q.bindValue(":blob",        data);
        q.bindValue(":size",        data.size());
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        fileRowID = q.lastInsertId().toInt();
        if (fileRowID < 1) {
            msg = "Failed to insert file row";
            return false;
        }

        /* link the new file row to this observation */
        QSqlQuery qu;
        qu.prepare("update observations set observation_fileid = :fileid where observation_id = :obsid");
        qu.bindValue(":fileid", fileRowID);
        qu.bindValue(":obsid",  observationRowID);
        n->SQLQuery(qu, __FUNCTION__, __FILE__, __LINE__);
    }

    fileName        = name;
    fileContentType = contentType;
    fileBlob        = data;
    fileSize        = data.size();
    fileDate        = QDateTime::currentDateTimeUtc();
    hasFile         = true;
    return true;
}
