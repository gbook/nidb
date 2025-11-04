/* ------------------------------------------------------------------------------
  NIDB moduleExportNonImaging.cpp
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

#include "moduleExportNonImaging.h"


/* ---------------------------------------------------------- */
/* --------- moduleExportNonImaging ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Constructor
 * @param a pointer to the nidb object
 */
moduleExportNonImaging::moduleExportNonImaging(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleExportNonImaging ------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Destructor
 */
moduleExportNonImaging::~moduleExportNonImaging()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Entry point for this module. This function will perform any exports if they are queued
 * @return The number of exports completed
 */
int moduleExportNonImaging::Run() {
    n->Log("Entering the exportnonimaging module");

    QSqlQuery q;

    /* delete any exports older than 30 days */
    q.prepare("select * from export_nonimaging where export_enddate < NOW() - INTERVAL 30 DAY");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

            int exportRowID = q.value("exportnonimaging_id").toInt();
            QString filePath = q.value("export_filepath").toString();

            /* delete the file */
            if (QFile::exists(filePath) && (!filePath.endsWith("/") && (filePath == ""))) {
                QFile::remove(filePath);
            }

            /* update the export_nonimaging row */
            q.prepare("update export_nonimaging set export_deletedate = now(), export_status = 'expired', export_statusmessage = 'Export expired after 30 days' where exportnonimaging_id = :exportid");
            q.bindValue(":exportid", exportRowID);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        }
    }

    /* get list of exports */
    q.prepare("select * from export_nonimaging where (export_status = 'submitted' or export_status = 'pending') and (export_deletedate is null or export_deletedate > now())");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() > 0) {
        int i = 0;
        while (q.next()) {
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }
            i++;

            int exportRowID = q.value("exportnonimaging_id").toInt();
            int pipelineRowID = q.value("pipeline_id").toInt();
            //int projectRowID = q.value("project_id").toInt();
            QString exportType = q.value("export_type").toString().trimmed(); /* analysisresults, observations, interventions */
            QString destinationType = q.value("export_destinationtype").toString();
            QString destinationNFSDir = q.value("export_destinationnfsdir").toString();
            QString status = q.value("export_status").toString();

            /* remove a trailing slash if it exists */
            if (destinationNFSDir.right(1) == "/")
                destinationNFSDir.chop(1);

            /* get the current status of this fileio request, make sure no one else is processing it, and mark it as being processed if not */
            if ((status == "submitted") || (status == "pending")) {

                /* process the request */
                bool success = true;
                QString statusMessage;

                /* create the export filepath */
                QString exportFilePath = n->cfg["exportdir"] + "/" + GenerateRandomString(20) + ".csv";

                QString csv;

                /* get result names (join is not efficient for 50 million rows for some reason) */
                QHash<int, QString> resultNameLookup;
                QSqlQuery q2;
                q2.prepare("select * from analysis_resultnames");
                n->SQLQuery(q2, __FUNCTION__, __FILE__, __LINE__);
                if (q2.size() > 0) {
                    while (q2.next()) {
                        resultNameLookup[q2.value("resultname_id").toInt()] = q2.value("result_name").toString();
                    }
                }
                else {
                    success = false;
                    statusMessage = "No analysis result names in database";
                }

                //qDebug() << resultNameLookup;
                //break;

                /* get results */
                QSqlQuery q3;
                QHash<QString, QHash<QString, QString>> dataTable; /* dataTable['S1234ABCx']['variable'] = value */
                QStringList resultNames;
                q3.prepare("select a.result_text, a.result_value, a.result_nameid, e.uid, c.study_num from analysis_results a left join analysis b on a.analysis_id = b.analysis_id left join studies c on b.study_id = c.study_id left join enrollment d on c.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where b.pipeline_id = :pipelineid");
                q3.bindValue(":pipelineid", pipelineRowID);
                n->SQLQuery(q3, __FUNCTION__, __FILE__, __LINE__);
                n->Log(QString("Found %1 result values").arg(q3.size()));
                if (q3.size() > 0) {
                    while (q3.next()) {
                        QString uidStudyNum = q3.value("uid").toString() + q3.value("study_num").toString();
                        QString value;
                        if (q3.value("result_text").isNull())
                            value = q3.value("result_value").toString();
                        else
                            value = "\"" + q3.value("result_text").toString() + "\"";

                        QString resultName = resultNameLookup[q3.value("result_nameid").toInt()];
                        if (!resultNames.contains(resultName))
                            resultNames.append(resultName);

                        dataTable[uidStudyNum][resultName] = value;
                    }
                }
                else {
                    success = false;
                    statusMessage = "No analysis results for this pipeline";
                }
                n->Log(QString("resultNames size is [%1]").arg(resultNames.size()));

                n->Log(QString("dataTable size is [%1]").arg(dataTable.size()));

                qint64 csvRows(0), csvCols(0);

                /* prepare the column names, and quote them */
                csvCols = resultNames.size() + 1;
                resultNames.sort();
                QStringList names;
                foreach (QString name, resultNames) {
                    names << "\"" + name + "\"";
                }
                csv = "\"Study\"," + names.join(",") + "\n";
                csvRows++;

                /* put results into csv format */
                /* iterate through the uidStudyNums */
                for(QHash<QString, QHash<QString, QString>>::iterator a = dataTable.begin(); a != dataTable.end(); ++a) {
                    QString uidStudyNum = a.key();

                    QStringList rowItems;
                    rowItems.append(uidStudyNum);

                    foreach (QString variable, resultNames) {
                        QString value = dataTable[uidStudyNum][variable];
                        rowItems.append(value);
                    }

                    QString csvLine = rowItems.join(",") + "\n";
                    csv += csvLine;
                    csvRows++;
                    if (csvRows%10000 == 0)
                        n->Log(QString("csv string has [%1] rows").arg(csvRows));
                }
                n->Log(QString("exportfilePath is [%1].  csv string is [%2] bytes").arg(exportFilePath).arg(csv.size()));
                n->Log(QString("csv contains %1 cols  x  %2 rows  =  %3 cells").arg(csvCols).arg(csvRows).arg(csvCols * csvRows));

                /* write csv to filePath */
                if (WriteTextFile(exportFilePath, csv, false)) {
                    statusMessage = "Successfuly wrote csv file";
                }
                else {
                    success = false;
                    statusMessage = "Error writing exported file";
                }

                /* update export status */
                QString status;
                if (success)
                    status = "complete";
                else
                    status = "error";

                QFileInfo fi(exportFilePath);
                qint64 fileSize = fi.size();

                QSqlQuery q4;
                q4.prepare("update export_nonimaging set export_enddate = now(), export_status = :status, export_statusmessage = :statusmessage, export_size = :filesize, export_filepath = :filepath where exportnonimaging_id = :exportid");
                q4.bindValue(":status", status);
                q4.bindValue(":statusmessage", statusMessage);
                q4.bindValue(":filesize", fileSize);
                q4.bindValue(":filepath", exportFilePath);
                q4.bindValue(":exportid", exportRowID);
                n->SQLQuery(q4, __FUNCTION__, __FILE__, __LINE__);
            }
            else {
                /* skip this IO request... the status was changed outside of this instance of the program */
                n->Log(QString("The status for this export [%1] has been changed from [submitted] to [%2]. Skipping.").arg(exportRowID).arg(status));
                continue;
            }

            n->Log(QString(" ---------- Export operation (%1 of %2) ---------- ").arg(i).arg(q.size()));

        }
        n->Log("Finished performing non-imaging exports");
    }
    else {
        n->Log("Nothing to do");
        return 0;
    }

    return 1;
}
