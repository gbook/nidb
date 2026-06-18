/* ------------------------------------------------------------------------------
  NIDB survey.cpp
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

#include "survey.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- survey ----------------------------------------- */
/* ---------------------------------------------------------- */
survey::survey()
{

}


/* ---------------------------------------------------------- */
/* --------- survey ----------------------------------------- */
/* ---------------------------------------------------------- */
survey::survey(qint64 id, nidb *a)
{
    n = a;
    surveyRowID = id;
    LoadSurveyInfo();
}


/* ---------------------------------------------------------- */
/* --------- ~survey ---------------------------------------- */
/* ---------------------------------------------------------- */
survey::~survey()
{

}


/* ---------------------------------------------------------- */
/* --------- LoadSurveyInfo --------------------------------- */
/* ---------------------------------------------------------- */
void survey::LoadSurveyInfo() {

    QStringList msgs;

    if (surveyRowID < 1) {
        msgs << "Invalid survey ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        q.prepare("select * from observation_surveys where survey_id = :surveyid");
        q.bindValue(":surveyid", surveyRowID);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid survey ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();

            dateEnd         = q.value("survey_enddate").toDateTime();
            dateEntry       = q.value("survey_entrydate").toDateTime();
            dateStart       = q.value("survey_startdate").toDateTime();
            experimenter    = q.value("survey_experimenter").toString();
            instrumentRowID = q.value("instrument_id").toInt();
            notes           = q.value("survey_notes").toString();
            rater           = q.value("survey_rater").toString();
            visit           = q.value("survey_visit").toString();

            isValid = true;
        }
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintSurveyInfo -------------------------------- */
/* ---------------------------------------------------------- */
void survey::PrintSurveyInfo() {
    QString output = QString("***** Survey - [%1] *****\n").arg(surveyRowID);

    output += QString("   dateEnd: [%1]\n").arg(dateEnd.toString());
    output += QString("   dateEntry: [%1]\n").arg(dateEntry.toString());
    output += QString("   dateStart: [%1]\n").arg(dateStart.toString());
    output += QString("   experimenter: [%1]\n").arg(experimenter);
    output += QString("   instrumentRowID: [%1]\n").arg(instrumentRowID);
    output += QString("   notes: [%1]\n").arg(notes);
    output += QString("   rater: [%1]\n").arg(rater);
    output += QString("   surveyRowID: [%1]\n").arg(surveyRowID);
    output += QString("   visit: [%1]\n").arg(visit);

    n->Log(output);
}


/* ---------------------------------------------------------- */
/* --------- AddToDatabase ---------------------------------- */
/* ---------------------------------------------------------- */
/** @brief Inserts or updates this survey in the database.
 *  @return true on success, false if the insert failed or required fields are missing */
bool survey::AddToDatabase() {
    n->Log(QString("survey::AddToDatabase()  surveyRowID (%1)  dateStart (%2)")
           .arg(surveyRowID).arg(dateStart.toUTC().toString("yyyy-MM-dd HH:mm:ss")));

    if (!dateStart.isValid() || dateStart.isNull())
        return false;

    QSqlQuery q;

    if (surveyRowID > 0) {
        n->Log(QString("  updating surveyRowID [%1]").arg(surveyRowID));
        q.prepare("update observation_surveys set instrument_id = :instrumentid, survey_startdate = :startdate, survey_enddate = :enddate, survey_notes = :notes, survey_visit = :visit, survey_experimenter = :experimenter, survey_rater = :rater where survey_id = :surveyid");
        q.bindValue(":surveyid",      surveyRowID);
        q.bindValue(":instrumentid",  instrumentRowID > 0 ? QVariant(instrumentRowID) : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":startdate",     dateStart.isValid()  ? QVariant(dateStart.toUTC().toString("yyyy-MM-dd HH:mm:ss"))  : QVariant(QMetaType::fromType<QString>()));
        q.bindValue(":enddate",       dateEnd.isValid()    ? QVariant(dateEnd.toUTC().toString("yyyy-MM-dd HH:mm:ss"))    : QVariant(QMetaType::fromType<QString>()));
        q.bindValue(":notes",         notes);
        q.bindValue(":visit",         visit);
        q.bindValue(":experimenter",  experimenter);
        q.bindValue(":rater",         rater);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        isValid = true;
    }
    else {
        //n->Log("  inserting new survey row");
        q.prepare("insert into observation_surveys (instrument_id, survey_startdate, survey_enddate, survey_notes, survey_visit, survey_experimenter, survey_rater, survey_entrydate) values (:instrumentid, :startdate, :enddate, :notes, :visit, :experimenter, :rater, :entrydate)");
        q.bindValue(":instrumentid",  instrumentRowID > 0 ? QVariant(instrumentRowID) : QVariant(QMetaType::fromType<int>()));
        q.bindValue(":startdate",     dateStart.isValid()  ? QVariant(dateStart.toUTC().toString("yyyy-MM-dd HH:mm:ss"))  : QVariant(QMetaType::fromType<QString>()));
        q.bindValue(":enddate",       dateEnd.isValid()    ? QVariant(dateEnd.toUTC().toString("yyyy-MM-dd HH:mm:ss"))    : QVariant(QMetaType::fromType<QString>()));
        q.bindValue(":notes",         notes);
        q.bindValue(":visit",         visit);
        q.bindValue(":experimenter",  experimenter);
        q.bindValue(":rater",         rater);
        q.bindValue(":entrydate",     dateEntry.isValid()  ? QVariant(dateEntry.toUTC().toString("yyyy-MM-dd HH:mm:ss"))  : QVariant(QMetaType::fromType<QString>()));
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        surveyRowID = q.lastInsertId().toLongLong();
        isValid = (surveyRowID > 0);
        n->Log(QString("  inserted surveyRowID [%1]").arg(surveyRowID));
    }

    return isValid;
}
