/* ------------------------------------------------------------------------------
  NIDB moduleBackup.cpp
  Copyright (C) 2004 - 2021
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
#include <QLocale>
#include "moduleBackup.h"
#include "analysis.h"


/* ---------------------------------------------------------- */
/* --------- moduleBackup ---------------------------------- */
/* ---------------------------------------------------------- */
moduleBackup::moduleBackup(nidb *a)
{
    n = a;
    backupTapeSize = n->cfg["backupsize"].toLong() * 1000000000; /* convert GB to bytes */
    backupDir = n->cfg["backupdir"];
    backupStagingDir = n->cfg["backupstagingdir"];
    backupDevice = n->cfg["backupdevice"];
    backupServer = n->cfg["backupserver"];
}


/* ---------------------------------------------------------- */
/* --------- ~moduleBackup --------------------------------- */
/* ---------------------------------------------------------- */
moduleBackup::~moduleBackup()
{

}


/* ---------------------------------------------------------- */
/* --------- Run -------------------------------------------- */
/* ---------------------------------------------------------- */
int moduleBackup::Run() {
    n->WriteLog("Entering the export module");

    n->ModuleRunningCheckIn();
    if (!n->ModuleCheckIfActive()) { n->WriteLog("Module is now inactive, stopping the module"); return 0; }

    /* don't attempt to run this module if the parameters are not valid */
    if ((backupStagingDir == "") || (backupStagingDir == ".") || (backupStagingDir == "..") || (backupStagingDir == "/") || (backupStagingDir.contains("//")) || (backupStagingDir == "/root") || (backupStagingDir == "/home")) {
        n->WriteLog(QString("backupstagingdir is not valid [%1]").arg(backupStagingDir));
        return 1;
    }
    if ((backupDir == "") || (backupDir == ".") || (backupDir == "..") || (backupDir == "/") || (backupDir.contains("//")) || (backupDir == "/root") || (backupDir == "/home")) {
        n->WriteLog(QString("backupdir is not valid [%1]").arg(backupStagingDir));
        return 1;
    }
    if (backupTapeSize < 1) {
        n->WriteLog(QString("backupTapeSize is not valid [%1]").arg(backupTapeSize));
        return 1;
    }

    QSqlQuery q;

    /* insert a row into backup table for the copying, to display a basic row of information the staging directory size, on the webpage */
    q.prepare("insert ignore into backups (backup_tapenumber, backup_tapestatus, backup_startdateA) values (0, 'staging', now()) on duplicate key update backup_tapestatus = 'idle', backup_startdateA = now(), backup_enddateA = null");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

    /* ----- step 1 ----- backup the database (Always done) */
    n->WriteLog(BackupDatabase());

    /* possible statuses: 'idle','waitingfortape','readytowrite','writing','filelisting','rewinding','staging','ejecting','complete' */

    /* check if there are any backups already occuring: ie, any status other than 'idle' or 'complete' */
    q.prepare("select * from backups where backup_tapestatus not in ('idle', 'complete')");
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() > 0) {
        while (q.next()) {
            //int backup_id = q.value("backup_id").toInt();
            int backup_tapenumber = q.value("backup_tapenumber").toInt();
            QString backup_tapestatus = q.value("backup_tapestatus").toString();
            QDateTime backup_startdateA = q.value("backup_startdateA").toDateTime();
            QDateTime backup_enddateA = q.value("backup_enddateA").toDateTime();
            qint64 backup_tapesizeA = q.value("backup_tapesizeA").toLongLong();
            //backup_tapecontentsA
            QDateTime backup_startdateB = q.value("backup_startdateB").toDateTime();
            QDateTime backup_enddateB = q.value("backup_enddateB").toDateTime();
            qint64 backup_tapesizeB = q.value("backup_tapesizeB").toLongLong();
            //backup_tapecontentsB
            QDateTime backup_startdateC = q.value("backup_startdateC").toDateTime();
            QDateTime backup_enddateC = q.value("backup_enddateC").toDateTime();
            qint64 backup_tapesizeC = q.value("backup_tapesizeC").toLongLong();
            //backup_tapecontentsC

            n->WriteLog(QString("Tape [%1] has status of [%2]. Startdate(s) [%3,%4,%5]  sizes [%6,%7,%8]  enddates [%9,%10,%11]").arg(backup_tapenumber).arg(backup_tapestatus).arg(backup_startdateA.toString()).arg(backup_startdateB.toString()).arg(backup_startdateC.toString()).arg(backup_tapesizeA).arg(backup_tapesizeB).arg(backup_tapesizeC).arg(backup_enddateA.toString()).arg(backup_enddateB.toString()).arg(backup_enddateC.toString()));
        }
    }

    /* ----- step 2 ----- copy to backup staging */
    qint64 backupStageSize = MoveToBackupStaging();
    n->WriteLog(QString("[%1] size is [%2] bytes").arg(backupStagingDir).arg(backupStageSize));

    /* ----- step 3 ----- check if we have enough data to write to a tape */
    if (backupStageSize > backupTapeSize) {
        /* possible statuses:
         * 'idle', 'waitingForTapeA', 'readyToWriteTapeA', 'writingTapeA', 'completeTapeA', 'waitingForTapeB', 'readyToWriteTapeB', 'writingTapeB', 'completeTapeB', 'waitingForTapeC', 'readyToWriteTapeC', 'writingTapeC', 'completeTapeC', 'complete' */

        /* check for any rows with status readyToWriteTapeX */
        q.prepare("select * from backups order by backup_tapenumber desc limit 1");
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        q.first();
        int backupid = q.value("backup_id").toInt();
        QString status = q.value("backup_tapestatus").toString();
        int tapeNum = q.value("backup_tapenumber").toInt();

        /* if there are, then write the tape */
        if (status == "readyToWriteTapeA") {
            WriteTape(tapeNum, 'A', backupid);
        }
        else if (status == "readyToWriteTapeB") {
            WriteTape(tapeNum, 'B', backupid);
        }
        else if (status == "readyToWriteTapeC") {
            WriteTape(tapeNum, 'C', backupid);
        }
        else if (status == "idle") {
            /* create new row with status of waitingForTapeA, and maxTapeNum+1. Also get the backup_id */
            q.prepare("insert into backups (backup_tapenumber, backup_tapestatus) values (:tapenum, 'waitingForTapeA')");
            q.bindValue(":tapenum", tapeNum+1);
            n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
            backupid = q.lastInsertId().toInt();
        }
        else if (status == "completeTapeA") {
            /* set status to 'waitingForTapeB' */
            SetBackupStatus(backupid, "waitingForTapeB");
        }
        else if (status == "completeTapeB") {
            /* set status to 'waitingForTapeC' */
            SetBackupStatus(backupid, "waitingForTapeC");
        }
        else if (status == "completeTapeC") {
            /* set status to 'complete' */
            SetBackupStatus(backupid, "complete");

            /* delete backupstaging contents  */
            QString systemstring = QString("rm -rf %1/*").arg(n->cfg["backupstagingdir"]);
            n->WriteLog(QString("Attempting to remove backupstagingdir using system command [%1]").arg(systemstring));

        }
    }
    else {
        q.prepare("update backups set backup_tapestatus = 'idle', backup_enddateA = now(), backup_tapesizeA = :stagesize where backup_tapenumber = 0");
        q.bindValue(":stagesize", backupStageSize);
        n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
    }

    return 1;
}


/* ---------------------------------------------------------- */
/* --------- MoveToBackupStaging----------------------------- */
/* ---------------------------------------------------------- */
qint64 moduleBackup::MoveToBackupStaging() {

    qint64 backupStagingSize = 0;

    /* get size of backup staging directory */
    int c;
    n->GetDirSizeAndFileCount(backupStagingDir, c, backupStagingSize, true);

    /* loop through files in backup dir, older than 24 hrs, then
       move files one by one from backup to backupstaging dirs */
    qint64 bytesMoved = 0;
    int filesMoved = 0;
    QDirIterator it(backupDir, QStringList() << "*", QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        it.next();
        qint64 size = it.fileInfo().size();
        QString fname = it.fileName();
        QString fdir = it.fileInfo().absolutePath();
        QString fpath = it.filePath();

        QString relativeDir = fdir.replace(backupDir, ""); /* remove the base backupStagingDir path */

        /* if copying this file will put the backupStagingSize over the tape size limit, then don't copy it, and we're done checking files */
        if ((backupStagingSize + size) >= backupTapeSize) {
            n->WriteLog(QString("Moving file [%1] of size [%2] bytes would make backupStagingDir size of [%3] bytes which is greater than tape size of [%4]").arg(fname).arg(size).arg(backupStagingSize + size).arg(backupTapeSize));
            break;
        }
        //QString destFile = QString("%1%2/%3").arg(backupStagingDir).arg(relativeDir).arg(fname);
        QString destDir = QString("%1%2").arg(backupStagingDir).arg(relativeDir);
        n->WriteLog(QString("Moving file [%1] to directory [%2]").arg(fpath).arg(destDir));

        QString m;
        if (!n->MakePath(destDir,m))
            n->WriteLog(QString("Unable to create path [%1]. Error message [%2]").arg(destDir).arg(m));
        else {
            if (n->MoveFile(fpath,destDir)) {
                filesMoved++;
                bytesMoved += size;
            }
            else
                n->WriteLog(QString("Unable to copy file [%1] to directory [%2]").arg(fpath).arg(destDir));
        }
    }

    backupStagingSize += bytesMoved;

    QLocale locale = QLocale::system();
    n->WriteLog(QString("Moved [%1] files of size [%2] bytes [%3]").arg(filesMoved).arg(bytesMoved).arg(locale.formattedDataSize(bytesMoved)));

    return backupStagingSize;
}


/* ---------------------------------------------------------- */
/* --------- BackupDatabase --------------------------------- */
/* ---------------------------------------------------------- */
QString moduleBackup::BackupDatabase() {

    QDateTime d = QDateTime::currentDateTime();
    QString date = d.toString("yyyy-MM-dd");

    QString systemstring = QString("mysqldump --single-transaction --compact -u%1 -p%2 %3 > %4/NiDB-backup-%5.sql").arg(n->cfg["mysqluser"]).arg(n->cfg["mysqlpassword"]).arg(n->cfg["mysqldatabase"]).arg(n->cfg["backupdir"]).arg(date);
    QString output = n->SystemCommand(systemstring);

    return output;
}


/* ---------------------------------------------------------- */
/* --------- WriteTapeSet ----------------------------------- */
/* ---------------------------------------------------------- */
//bool moduleBackup::WriteTapeSet() {

//    /* get next tape number, and letter */
//    int tapeNum = 1;
//    QSqlQuery q;
//    q.prepare("select max(backup_tapenumber) 'maxtapenumber' from backup");
//    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
//    if (q.size() > 0) {
//        q.first();
//        tapeNum = q.value("maxtapenumber").toInt() + 1;
//    }

//    /* start writing the data from backup staging to tape, one tape at a time */
//    if (WriteTape(tapeNum, 'A')) { n->WriteLog("Wrote tape A%1").arg(tapeNum, 4, 10, QLatin1Char('0')); }
//    else { n->WriteLog("Error writing tape A%1").arg(tapeNum, 4, 10, QLatin1Char('0')); }

//    if (WriteTape(tapeNum, 'B')) { n->WriteLog("Wrote tape B%1").arg(tapeNum, 4, 10, QLatin1Char('0')); }
//    else { n->WriteLog("Error writing tape B%1").arg(tapeNum, 4, 10, QLatin1Char('0')); }

//    if (WriteTape(tapeNum, 'C')) { n->WriteLog("Wrote tape C%1").arg(tapeNum, 4, 10, QLatin1Char('0')); }
//    else { n->WriteLog("Error writing tape C%1").arg(tapeNum, 4, 10, QLatin1Char('0')); }

//    return true;
//}


/* ---------------------------------------------------------- */
/* --------- WriteTape -------------------------------------- */
/* ---------------------------------------------------------- */
bool moduleBackup::WriteTape(int tapeNum, char tapeLetter, int backupid) {
    n->WriteLog(QString("Writing tape %1%2").arg(tapeLetter).arg(tapeNum, 4, 10, QLatin1Char('0')));

    QSqlQuery q;

    /* set startdate (if A, B, or C) */
    switch (tapeLetter) {
        case 'A':
            q.prepare("update backups set backup_startdateA = now() where backup_id = :backupid");
            break;
        case 'B':
            q.prepare("update backups set backup_startdateB = now() where backup_id = :backupid");
            break;
        case 'C':
            q.prepare("update backups set backup_startdateC = now() where backup_id = :backupid");
            break;
    }
    q.bindValue(":backupid", backupid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);

    /* write the tape (load, write, read contents, rewind, eject */
    QString systemstring;
    /* load the tape */
    n->WriteLog("Loading tape");
    if (backupServer == "")
        systemstring = QString("mt -f %1 load").arg(backupDevice);
    else
        systemstring = QString("ssh %1 \"mt -f %2 load\"").arg(backupServer).arg(backupDevice);
    n->WriteLog(n->SystemCommand(systemstring));

    /* write the data from backupstaging */
    n->WriteLog("Writing data from backupstaging");
    if (backupServer == "")
        systemstring = QString("cd %1; tar -cW --totals -f %2 *").arg(backupStagingDir).arg(backupDevice);
    else
        systemstring = QString("ssh %1 \"cd %2; tar -cW --totals -f %3 *\"").arg(backupServer).arg(backupStagingDir).arg(backupDevice);
    n->WriteLog(n->SystemCommand(systemstring));

    /* get tape listing */
    n->WriteLog("Getting tape contents listing");
    if (backupServer == "")
        systemstring = QString("tar -tf %1").arg(backupDevice);
    else
        systemstring = QString("ssh %1 \"tar -tf %2\"").arg(backupServer).arg(backupDevice);
    QString tapeListing = n->SystemCommand(systemstring,false);

    /* rewind tape */
    n->WriteLog("Rewinding tape");
    if (backupServer == "")
        systemstring = QString("mt -f %1 rewind").arg(backupDevice);
    else
        systemstring = QString("ssh %1 \"mt -f %2 rewind\"").arg(backupServer).arg(backupDevice);
    n->WriteLog(n->SystemCommand(systemstring));

    /* eject tape */
    n->WriteLog("Ejecting tape");
    if (backupServer == "")
        systemstring = QString("mt -f %1 offline").arg(backupDevice);
    else
        systemstring = QString("ssh %1 \"mt -f %2 offline\"").arg(backupServer).arg(backupDevice);
    n->WriteLog(n->SystemCommand(systemstring));

    /* set enddate (A, B, C) and status to 'completeTapeX' */
    switch (tapeLetter) {
        case 'A':
            q.prepare("update backups set backup_enddateA = now(), backup_tapestatus = 'completeTapeA', backup_tapecontentsA = :contents where backup_id = :backupid");
            break;
        case 'B':
            q.prepare("update backups set backup_enddateB = now(), backup_tapestatus = 'completeTapeB', backup_tapecontentsB = :contents where backup_id = :backupid");
            break;
        case 'C':
            q.prepare("update backups set backup_enddateC = now(), backup_tapestatus = 'completeTapeC', backup_tapecontentsC = :contents where backup_id = :backupid");
            break;
    }
    q.bindValue(":backupid", backupid);
    q.bindValue(":contents", tapeListing);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);

    return true;
}


/* ---------------------------------------------------------- */
/* --------- SetBackupStatus -------------------------------- */
/* ---------------------------------------------------------- */
void moduleBackup::SetBackupStatus(int backupid, QString status) {
    n->WriteLog(QString("Setting status of [%1] for backupid [%2]").arg(status).arg(backupid));

    QSqlQuery q;
    q.prepare("update backups set backup_tapestatus = :status where backup_id = :backupid");
    q.bindValue(":status", status);
    q.bindValue(":backupid", backupid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
}
