/* ------------------------------------------------------------------------------
  NIDB moduleAudit.h
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
#ifndef MODULEAUDIT_H
#define MODULEAUDIT_H
#include "nidb.h"

class moduleAudit
{
public:
    moduleAudit(nidb *n);
    ~moduleAudit();

    int Run();

    /* individual audits. Each returns true if the audit ran to completion, and sets m to a summary message */
    bool AuditSeriesFiles(qint64 auditid, int projectid, bool fixErrors, QString &m);
    bool AuditAnalysisFiles(qint64 auditid, int projectid, bool fixErrors, QString &m);

private:
    bool AuditCheckIn(qint64 auditid, qint64 progress, qint64 total, QString &stopReason);
    int CheckFileCountAndSize(qint64 auditid, bool fixErrors, QString objectType, qint64 objectid, QString objectDesc, QString path, QString table, QString idColumn, QString countColumn, QString sizeColumn, bool dbCountIsNull, qint64 dbCount, bool dbSizeIsNull, qint64 dbSize, qint64 diskCount, qint64 diskSize, qint64 &numFixed);
    QString GetAuditStatus(qint64 auditid);
    void SetAuditStatus(qint64 auditid, QString status, QString msg, bool ended=false);
    void SetAuditProgress(qint64 auditid, qint64 progress, qint64 total);
    void LogAuditIssue(qint64 auditid, QString issueType, QString objectType, qint64 objectid, QString msg, int fixed, QString fixedMsg);

    nidb *n;
};

#endif // MODULEAUDIT_H
