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
    q.prepare("select * from export_nonimaging where export_dateend < NOW() - INTERVAL 30 DAY");
    if (q.size() > 0) {
        while (q.next()) {
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }

            int exportRowID = q.value("exportnonimaging_id").toInt();
            //int pipelineRowID = q.value("pipeline_id").toInt();
            //int projectRowID = q.value("project_id").toInt();
            //QString exportType = q.value("export_type").toString().trimmed(); /* analysisresults, observations, interventions */
            //QString destinationType = q.value("export_destinationtype").toString();
            //QString destinationNFSDir = q.value("export_destinationnfsdir").toString();
            //QString status = q.value("export_status").toString();
            //QString statusMessage = q.value("export_statusmessage").toString();
            //QString dateStart = q.value("export_startdate").toDateTime();
            //QString dateEnd = q.value("export_enddate").toDateTime();
            //QString dateDelete = q.value("export_deletedate").toDateTime();
            //qint64 fileSize = q.value("export_size").toLongLong();
            QString filePath = q.value("export_filepath").toString();

            /* delete the file */
            if (QFile::exists(filePath) && (!filePath.endsWith("/") && (filePath == ""))) {
                QFile::remove(filePath);
            }

            /* update the export_nonimaging row */
        }
    }

    /* get list of things to export */
    q.prepare("select * from export_nonimaging where (export_status = 'submitted' or export_status = 'pending') and (export_deletedate is null or export_deletedate > now())");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    if (q.size() > 0) {
        int i = 0;
        while (q.next()) {
            n->ModuleRunningCheckIn();
            if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 0; }
            bool found = false;
            i++;

            int exportRowID = q.value("exportnonimaging_id").toInt();
            int pipelineRowID = q.value("pipeline_id").toInt();
            int projectRowID = q.value("project_id").toInt();
            QString exportType = q.value("export_type").toString().trimmed(); /* analysisresults, observations, interventions */
            QString destinationType = q.value("export_destinationtype").toString();
            QString destinationNFSDir = q.value("export_destinationnfsdir").toString();
            QString status = q.value("export_status").toString();
            QString statusMessage = q.value("export_statusmessage").toString();
            QString dateStart = q.value("export_startdate").toDateTime();
            QString dateEnd = q.value("export_enddate").toDateTime();
            QString dateDelete = q.value("export_deletedate").toDateTime();
            qint64 fileSize = q.value("export_size").toLongLong();
            QString filePath = q.value("export_filepath").toString();

            /* remove a trailing slash if it exists */
            if (destinationNFSDir.right(1) == "/")
                destinationNFSDir.chop(1);

            /* get the current status of this fileio request, make sure no one else is processing it, and mark it as being processed if not */
            //QString status = GetExportStatus(exportid);
            if (status == "submitted") {
                /* set the status. if something is wrong, skip this request */
            //    if (!SetExportStatus(exportid, "processing")) {
            //        n->Log(QString("Unable to set export status to [%1]").arg(status));
            //        continue;
            //    }
            }
            else {
                /* skip this IO request... the status was changed outside of this instance of the program */
                n->Log(QString("The status for this export [%1] has been changed from [submitted] to [%2]. Skipping.").arg(exportRowID).arg(status));
                continue;
            }

            n->Log(QString(" ---------- Export operation (%1 of %2) ---------- ").arg(i).arg(q.size()));

            QString log;

            n->Log(QString("Found [%1] exports").arg(found));
        }
        n->Log("Finished performing exports");
    }
    else {
        n->Log("Nothing to do");
        return 0;
    }

    return 1;
}
