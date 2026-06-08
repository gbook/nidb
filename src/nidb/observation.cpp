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
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- observation ------------------------------------ */
/* ---------------------------------------------------------- */
observation::observation() {

}


/* ---------------------------------------------------------- */
/* --------- observation ------------------------------------ */
/* ---------------------------------------------------------- */
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
            observationValue        = q.value("observation_value").toString();
            projectRowID            = q.value("project_id").toInt();
            remoteBatchRowID        = q.value("remotebatch_id").toInt();
            subjectRowID            = q.value("subject_id").toInt();
            subjectUID              = q.value("UID").toString();
            surveyRowID             = q.value("observationsurvey_id").toInt();

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

            } /* end loading linked data */

        }
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintObservationInfo --------------------------- */
/* ---------------------------------------------------------- */
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

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
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
/** @brief Inserts this observation into the database.
 *  @return true on success, false if the insert failed or required fields are missing */
bool observation::AddToDatabase() {
    if (enrollmentRowID < 1) {
        msg = "enrollmentRowID must be set before calling AddToDatabase()";
        isValid = false;
        return false;
    }

    QSqlQuery q;
    q.prepare("insert into observations (enrollment_id, instrumentitem_id, observationsurvey_id, remotebatch_id, observation_name, observation_notes, observation_instrument, observation_desc, observation_rater, observation_value, observation_startdate, observation_enddate, observation_duration, observation_entrydate, observation_createdate) values (:enrollmentid, :instrumentitemid, :surveyid, :remotebatchid, :name, :notes, :instrument, :desc, :rater, :value, :startdate, :enddate, :duration, :entrydate, :createdate)");
    q.bindValue(":enrollmentid",    enrollmentRowID);
    q.bindValue(":instrumentitemid", instrumentItemRowID > 0 ? QVariant(instrumentItemRowID) : QVariant(QMetaType::fromType<int>()));
    q.bindValue(":surveyid",        surveyRowID > 0         ? QVariant(surveyRowID)         : QVariant(QMetaType::fromType<int>()));
    q.bindValue(":remotebatchid",   remoteBatchRowID > 0    ? QVariant(remoteBatchRowID)    : QVariant(QMetaType::fromType<int>()));
    q.bindValue(":name",            observationName);
    q.bindValue(":notes",           observationNotes);
    q.bindValue(":instrument",      observationInstrument);
    q.bindValue(":desc",            observationDescription);
    q.bindValue(":rater",           observationRater);
    q.bindValue(":value",           observationValue);
    q.bindValue(":startdate",       dateObservationStart);
    q.bindValue(":enddate",         dateObservationEnd.isValid() ? QVariant(dateObservationEnd) : QVariant(QMetaType::fromType<QDateTime>()));
    q.bindValue(":duration",        observationDuration > 0     ? QVariant(observationDuration) : QVariant(QMetaType::fromType<int>()));
    q.bindValue(":entrydate",       dateRecordEntry.isValid()   ? QVariant(dateRecordEntry)   : QVariant(QMetaType::fromType<QDateTime>()));
    q.bindValue(":createdate",      dateRecordCreate.isValid()  ? QVariant(dateRecordCreate)  : QVariant(QMetaType::fromType<QDateTime>()));
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    observationRowID = q.lastInsertId().toLongLong();
    isValid = (observationRowID > 0);

    /* add metadata if there is any */
    if (metadata.size() > 0) {
        q.prepare("insert ignore into observation_meta (observation_id, variable, value) values (?, ?, ?)");

        QVariantList ids;
        QVariantList variables;
        QVariantList values;
        for (auto it = metadata.keyValueBegin(); it != metadata.keyValueEnd(); ++it) {
            ids << observationRowID;
            variables << it->first;
            values << it->second;
        }
        q.addBindValue(ids);
        q.addBindValue(variables);
        q.addBindValue(values);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }

    return isValid;
}
