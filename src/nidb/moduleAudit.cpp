/* ------------------------------------------------------------------------------
  NIDB moduleAudit.cpp
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

#include <QSqlQuery>
#include "moduleAudit.h"


/* ---------------------------------------------------------- */
/* --------- moduleAudit ------------------------------------ */
/* ---------------------------------------------------------- */
moduleAudit::moduleAudit(nidb *a)
{
    n = a;
}


/* ---------------------------------------------------------- */
/* --------- ~moduleAudit ----------------------------------- */
/* ---------------------------------------------------------- */
moduleAudit::~moduleAudit()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run on-demand audits (files exist, PHI, orphaned objects, etc).
 * @return 1 if any audit ran (keep the log), 0 otherwise
 */
int moduleAudit::Run() {
    n->Log("Entering the audit module");

    /* TODO: get the list of requested audits. Until then, no audits are run */
    QStringList audits;

    bool ret = false;
    foreach (QString audit, audits) {
        n->ModuleRunningCheckIn();
        if (!n->ModuleCheckIfActive()) { n->Log("Module is now inactive, stopping the module"); return 1; }

        QString m;
        bool ran = false;
        if (audit == "filesexist")
            ran = AuditFilesExist(m);
        else if (audit == "phi")
            ran = AuditPHI(m);
        else if (audit == "orphanedobjects")
            ran = AuditOrphanedObjects(m);
        else
            m = "Unrecognized audit [" + audit + "]";

        n->Log(QString("Audit [%1] %2: %3").arg(audit).arg(ran ? "completed" : "did not run").arg(m));
        ret = true;
    }

    if (!ret)
        n->Log("No audits requested");

    n->Log("Leaving the audit module");
    return ret;
}


/* ---------------------------------------------------------- */
/* --------- AuditFilesExist -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check that the files referenced in the database exist on disk
 * @param m Summary message
 * @return true if the audit ran
 */
bool moduleAudit::AuditFilesExist(QString &m) {
    m = "Not implemented";
    return false;
}


/* ---------------------------------------------------------- */
/* --------- AuditPHI --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check stored data for PHI
 * @param m Summary message
 * @return true if the audit ran
 */
bool moduleAudit::AuditPHI(QString &m) {
    m = "Not implemented";
    return false;
}


/* ---------------------------------------------------------- */
/* --------- AuditOrphanedObjects --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find database objects whose parent objects no longer exist
 * @param m Summary message
 * @return true if the audit ran
 */
bool moduleAudit::AuditOrphanedObjects(QString &m) {
    m = "Not implemented";
    return false;
}
