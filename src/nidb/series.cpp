/* ------------------------------------------------------------------------------
  NIDB series.cpp
  Copyright (C) 2004 - 2024
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

#include "series.h"
#include "study.h"
#include "imageio.h"
#include <QSqlQuery>


/* ---------------------------------------------------------- */
/* --------- series ----------------------------------------- */
/* ---------------------------------------------------------- */
series::series(qint64 id, QString m, nidb *a)
{
    n = a;
    seriesid = id;
    modality = m.toLower();
    LoadSeriesInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadSeriesInfo --------------------------------- */
/* ---------------------------------------------------------- */
void series::LoadSeriesInfo() {

    QStringList msgs;

    if (seriesid < 1) {
        msgs << "Invalid series ID";
        isValid = false;
    }
    else {
        QSqlQuery q;
        QString sqlstring = QString("select *, b.study_num, d.uid, d.subject_id, c.enrollment_id, b.study_id from %1_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.%1series_id = :seriesid").arg(modality);
        q.prepare(sqlstring);
        q.bindValue(":seriesid", seriesid);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        if (q.size() < 1) {
            msgs << "Query returned no results. Possibly invalid series ID or recently deleted?";
            isValid = false;
        }
        else {
            q.first();
            uid = q.value("uid").toString().trimmed().replace('\u0000', "");
            studynum = q.value("study_num").toInt();
            seriesnum = q.value("series_num").toInt();
            desc = q.value("series_desc").toString().trimmed();
            protocol = q.value("series_protocol").toString().trimmed();
            datetime = q.value("series_datetime").toDateTime();
            subjectid = q.value("subject_id").toInt();
            studyid = q.value("study_id").toInt();
            projectid = q.value("project_id").toInt();
            enrollmentid = q.value("enrollment_id").toInt();
            if (q.value("data_type").isValid())
                datatype = q.value("data_type").toString().trimmed();
            isderived = q.value("is_derived").toBool();

            /* check to see if anything isn't valid or is blank */
            if ((n->cfg["archivedir"] == "") || (n->cfg["archivedir"] == "/")) { msgs << "cfg->archivedir was invalid"; isValid = false; }
            if (uid == "") { msgs << "uid was blank"; isValid = false; }
            if (studynum < 1) { msgs << "studynum is not valid"; isValid = false; }
            if (seriesnum < 0) { msgs << "seriesnum is not valid"; isValid = false; }

            seriespath = QString("%1/%2/%3/%4").arg(n->cfg["archivedir"]).arg(uid).arg(studynum).arg(seriesnum);
            if (modality == "mr") {
                datapath = seriespath + "/" + datatype;
                behpath = seriespath + "/beh";
            }
            else {
                datapath = seriespath + "/" + modality;
            }

            QDir d(seriespath);
            if (!d.exists()) {
                msgs << QString("Invalid series path [%1]").arg(seriespath);
                isValid = false;
            }
        }
        isValid = true;
    }
    msg = msgs.join(" | ");
}


/* ---------------------------------------------------------- */
/* --------- PrintSeriesInfo -------------------------------- */
/* ---------------------------------------------------------- */
void series::PrintSeriesInfo() {
    QString	output = QString("***** Series - [%1] *****\n").arg(seriesid);

    output += QString("   datapath: [%1]\n").arg(datapath);
    output += QString("   datatype: [%1]\n").arg(datatype);
    output += QString("   enrollmentid: [%1]\n").arg(enrollmentid);
    output += QString("   isDerived: [%1]\n").arg(isderived);
    output += QString("   isValid: [%1]\n").arg(isValid);
    output += QString("   modality: [%1]\n").arg(modality);
    output += QString("   msg: [%1]\n").arg(msg);
    output += QString("   projectid: [%1]\n").arg(projectid);
    output += QString("   seriesid: [%1]\n").arg(seriesid);
    output += QString("   seriesnum: [%1]\n").arg(seriesnum);
    output += QString("   seriespath: [%1]\n").arg(seriespath);
    output += QString("   studyid: [%1]\n").arg(studyid);
    output += QString("   studynum: [%1]\n").arg(studynum);
    output += QString("   subjectid: [%1]\n").arg(subjectid);
    output += QString("   uid: [%1]\n").arg(uid);

    n->WriteLog(output);
}


/* ---------------------------------------------------------- */
/* --------- ChangeSeriesPath ------------------------------- */
/* ---------------------------------------------------------- */
bool series::ChangeSeriesPath(int studyid, int newSeriesNum) {
    study s(studyid, n);
    QString newSeriesPath = QString("%1/%2").arg(s.path()).arg(newSeriesNum);

    n->WriteLog("Changing series path from [" + seriespath + "] to [" + newSeriesPath + "]");

    if (RenameFile(seriespath, newSeriesPath))
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelSeries series::GetSquirrelObject() {
    squirrelSeries sqrl;

    sqrl.DateTime = datetime;
    sqrl.Description = desc;
    sqrl.SeriesNumber = seriesnum;
    sqrl.Protocol = protocol;

    sqrl.stagedFiles = FindAllFiles(datapath,"*");
    sqrl.stagedBehFiles = FindAllFiles(behpath,"*");
    QString file, m;
    if (FindFirstFile(datapath,"*",file,m)) {
        imageIO io;
        QHash<QString, QString> tags;
        io.GetImageFileTags(file, n->cfg["bindir"], true, tags, m);
        sqrl.params = tags;
    }

    return sqrl;
}
