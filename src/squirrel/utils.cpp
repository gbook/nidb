/* ------------------------------------------------------------------------------
  NIDB utils.cpp
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

#include "utils.h"
#include "squirrelVersion.h"
#include "squirrel.h"

namespace utils {

    /* ---------------------------------------------------------- */
    /* --------- Print ------------------------------------------ */
    /* ---------------------------------------------------------- */
    QString Print(QString s, bool n, bool pad) {
        QString str;
        if (n) {
            if (pad) {
                printf("%-80s\n", s.toStdString().c_str());
                str = s + "\n";
            }
            else {
                printf("%s\n", s.toStdString().c_str());
                str = s + "\n";
            }
        }
        else {
            if (pad) {
                printf("%-80s", s.toStdString().c_str());
                str = s;
            }
            else {
                printf("%s", s.toStdString().c_str());
                str = s;
            }
        }
        return str;
    }


    /* ---------------------------------------------------------- */
    /* --------- CreateCurrentDateTime -------------------------- */
    /* ---------------------------------------------------------- */
    QString CreateCurrentDateTime(int format) {
        QString date;

        QDateTime d = QDateTime::currentDateTime();
        switch (format) {
            case 1:
                date = d.toString("yyyy/MM/dd HH:mm:ss"); break;
            case 2:
                date = d.toString("yyyy-MM-dd HH:mm:ss"); break;
            case 3:
                date = d.toString("yyyy/MM/dd"); break;
            case 4:
                date = d.toString("yyyy-MM-dd"); break;
            case 5:
                date = d.toString("HH:mm:ss"); break;
            default:
                date = d.toString("yyyy/MM/dd HH:mm:ss");
        }

        return date;
    }


    /* ---------------------------------------------------------- */
    /* --------- CreateLogDate ---------------------------------- */
    /* ---------------------------------------------------------- */
    QString CreateLogDate() {
        QString date;

        QDateTime d = QDateTime::currentDateTime();
        date = d.toString("yyyyMMddHHmmss");

        return date;
    }


    /* ---------------------------------------------------------- */
    /* --------- SystemCommand ---------------------------------- */
    /* ---------------------------------------------------------- */
    QString SystemCommand(QString s, bool detail, bool truncate) {

        double starttime = double(QDateTime::currentMSecsSinceEpoch());
        QString ret;
        QString output;
        QString buffer;
        QProcess *process = new QProcess();

        /* start QProcess and check if it started */

        #ifdef Q_OS_WINDOWS
            /* Windows is so difficult... cmd.exe must be started, and only then can you pass the command to it, with a newline as if the Enter key was pressed */
            s = QString(s + "\n");
            process->start("cmd.exe");
            if (!process->waitForStarted()) {
                output = "QProcess failed to start, with error [" + process->errorString() + "]";
            }
            process->write(s.toLatin1());
            process->closeWriteChannel();

            /* collect the output */
            while(process->waitForReadyRead(-1)) {
                buffer = QString(process->readAll());
                output += buffer;
            }
            /* check if it finished */
            process->waitForFinished();
            output += QString(process->readAll());
        #else
            /* Linux is so much easier... */
            process->start("sh", QStringList() << "-c" << s);
            if (!process->waitForStarted()) {
                output = "QProcess failed to start, with error [" + process->errorString() + "]";
            }
            /* collect the output */
            while(process->waitForReadyRead(-1)) {
                buffer = QString(process->readAll());
                output += buffer;
            }
            /* check if it finished */
            process->waitForFinished();
            output += QString(process->readAll());
        #endif

        delete process;

        double elapsedtime = (double(QDateTime::currentMSecsSinceEpoch()) - starttime + 0.000001)/1000.0; /* add tiny decimal to avoid a divide by zero */

        output = output.trimmed();
        output.replace("’", "'");
        output.replace("‘", "'");

        if (truncate)
            if (output.size() > 20000)
                output = output.left(10000) + "\n\n     ...\n\n     OUTPUT TRUNCATED. Displaying only first and last 10,000 characters\n\n     ...\n\n" + output.right(10000);

        if (detail)
            ret = QString("Executed command [%1], Output [%2], elapsed time [%3 sec]").arg(s).arg(output).arg(elapsedtime, 0, 'f', 3);
        else
            ret = output;

        return ret;
    }


    /* ---------------------------------------------------------- */
    /* --------- MakePath --------------------------------------- */
    /* ---------------------------------------------------------- */
    bool MakePath(QString p, QString &msg, bool perm777) {

        if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p == "/root") || (p == "/home")) {
            msg = "Path [" + p + "] is not valid";
            return false;
        }

        QDir d(p);

        if(!d.exists() && !d.mkpath(p)) {
            msg = "MakePath() Error creating path [" + p + "]";
            return false;
        }
        else
            msg = "MakePath() Path already exists or was created successfuly [" + p + "]";

        if (perm777)
            SystemCommand("chmod 777 " + p);

        return true;
    }


    /* ---------------------------------------------------------- */
    /* --------- RemoveDir -------------------------------------- */
    /* ---------------------------------------------------------- */
    bool RemoveDir(QString p, QString &msg) {

        if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p.startsWith("/root")) || (p == "/home")) {
            msg = "Path is not valid [" + p + "]";
            return false;
        }

        QDir path(p);
        if (path.removeRecursively()) {
            return true;
        }
        else {
            msg = "Unable to delete directory";
            return false;
        }
    }


    /* ---------------------------------------------------------- */
    /* --------- GenerateRandomString --------------------------- */
    /* ---------------------------------------------------------- */
    QString GenerateRandomString(int n) {

       const QString chars("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789");
       QString randomString;
       for(int i=0; i<n; ++i)
       {
           QChar nextChar = chars.at(QRandomGenerator::global()->bounded(chars.length()-1));
           randomString.append(nextChar);
       }
       return randomString;
    }


    /* ---------------------------------------------------------- */
    /* --------- FindAllFiles ----------------------------------- */
    /* ---------------------------------------------------------- */
    QStringList FindAllFiles(QString dir, QString pattern, bool recursive) {
        QStringList files;
        if (recursive) {
            QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
            while (it.hasNext())
                files << it.next();
        }
        else {
            QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::NoIteratorFlags);
            while (it.hasNext())
                files << it.next();
        }

        return files;
    }


    /* ---------------------------------------------------------- */
    /* --------- FindAllDirs ------------------------------------ */
    /* ---------------------------------------------------------- */
    QStringList FindAllDirs(QString dir, QString pattern, bool recursive, bool includepath) {

        if (pattern.trimmed() == "")
            pattern = "*";

        QStringList dirs;

        if (recursive) {
            QDirIterator it(dir, QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
            while (it.hasNext()) {
                if (includepath)
                    dirs << it.next();
                else {
                    it.next();
                    dirs << it.fileName();
                }
            }
        }
        else {
            QDirIterator it(dir, QStringList() << pattern, QDir::Dirs | QDir::NoDotAndDotDot | QDir::NoSymLinks);
            while (it.hasNext()) {
                if (includepath)
                    dirs << it.next();
                else {
                    it.next();
                    dirs << it.fileName();
                }
            }
        }

        return dirs;
    }


    /* ---------------------------------------------------------- */
    /* --------- GetDirSizeAndFileCount ------------------------- */
    /* ---------------------------------------------------------- */
    void GetDirSizeAndFileCount(QString dir, qint64 &c, qint64 &b, bool recurse) {
        c = 0;
        b = 0;

        QDir d(dir);

        if (recurse) {
            QDirIterator it(dir, QStringList() << "*", QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
            while (it.hasNext()) {
                it.next();
                c++;
                b += it.fileInfo().size();
            }
        }
        else {
            QFileInfoList fl = d.entryInfoList(QDir::NoDotAndDotDot | QDir::Files);
            c = fl.size();
            for (int i=0; i < fl.size(); i++) {
                const QFileInfo finfo = fl.at(i);
                b += finfo.size();
            }
        }
    }


    /* ---------------------------------------------------------- */
    /* --------- ParseDate -------------------------------------- */
    /* ---------------------------------------------------------- */
    QString ParseDate(QString s) {
        QString d = "0000-01-01";
        QDate date;

        s.replace(":","-").replace(".","-").replace("/","-").replace("|","-").replace(",","-").replace("\\","-");

        date = QDate::fromString(s, "yyyy-MM-dd");
        if (date.isValid()) return date.toString("yyyy-MM-dd");

        date = QDate::fromString(s, "yyyy-M-d");
        if (date.isValid()) return date.toString("yyyy-MM-dd");

        date = QDate::fromString(s, "dd-MM-yy");
        if (date.isValid()) return date.toString("yyyy-MM-dd");

        date = QDate::fromString(s, "MM-yyyy");
        if (date.isValid()) return date.toString("yyyy-MM-dd");

        return d;
    }


    /* ---------------------------------------------------------- */
    /* --------- ParseTime -------------------------------------- */
    /* ---------------------------------------------------------- */
    QString ParseTime(QString s) {
        QString t = "00:00:00";
        QTime time;

        s.replace("-",":").replace("/",":").replace("|",":").replace(",",":").replace("\\",":");

        time = QTime::fromString(s, "hh:mm:ss");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "h:m:s");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:m:s");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:mm:s");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "h:mm:ss");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "h:m:ss");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:m:ss");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "h:mm:s");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:mm");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:m");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "h:mm");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "h:m");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:mm:ss.zzz");
        if (time.isValid()) return time.toString("hh:mm:ss");

        time = QTime::fromString(s, "hh:mm:ss.z");
        if (time.isValid()) return time.toString("hh:mm:ss");

        return t;
    }


    /* ---------------------------------------------------------- */
    /* --------- ParseCSV --------------------------------------- */
    /* ---------------------------------------------------------- */
    /* this function handles most Excel compatible .csv formats
     * but it does not handle nested quotes, and must have a header
     * row */
    bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg) {

        QStringList m;
        bool ret(true);

        /* get header row */
        QStringList lines = csv.trimmed().split(QRegularExpression("[\\n\\r]"));

        if (lines.size() > 1) {
            QString header = lines.takeFirst();
            QStringList cols = header.trimmed().toLower().split(QRegularExpression("\\s*,\\s*"));
            columns = cols;

            m << QString("Found [%1] columns [%2]").arg(cols.size()).arg(cols.join(","));
            /* remove the last column if it was blank, because the file contained an extra trailing comma */
            if (cols.last() == "") {
                cols.removeLast();
                m << "Last column was blank, removing";
            }

            qint64 numcols = cols.size();

            int row = 0;
            foreach (QString line, lines) {
                QString buffer = "";
                int col = 0;
                bool inQuotes = false;
                for (int i=0; i<line.size(); i++) {
                    QChar c = line.at(i);

                    /* determine if we're in quotes or not */
                    if (c == '"') {
                        if (inQuotes)
                            inQuotes = false;
                        else
                            inQuotes = true;
                    }

                    /* check if we've hit the next comma, and therefor should end the previous variable */
                    if ((c == ',') && (!inQuotes)) {
                        table[row][cols[col]] = buffer.trimmed();

                        buffer = "";
                        col++;
                    }
                    else {
                        buffer = QString("%1%2").arg(buffer).arg(c); /* make sure no null terminators end up in the string */
                    }
                }
                /* acquire the last column */
                table[row][cols[col]] = buffer.trimmed();
                buffer = "";

                if ((col+1) != numcols) {
                    m << QString("Error: row [%1] has [%2] columns, but expecting [%3] columns").arg(row+1).arg(col+1).arg(numcols);
                    ret = false;
                }

                row++;
            }
            m << QString("Processed [%1] data rows").arg(row);
        }
        else {
            ret = false;
            m << ".csv file contained only one row. The csv must contain at least one header row and one data row";
        }

        msg = m.join("  \n");

        return ret;
    }


    /* ---------------------------------------------------------- */
    /* --------- ParseTSV --------------------------------------- */
    /* ---------------------------------------------------------- */
    /* this function handles most .tsv formats but it does not
     *  handle nested quotes, and must have a header row */
    bool ParseTSV(QString tsv, indexedHash &table, QStringList &columns, QString &msg) {

        QStringList m;
        bool ret(true);

        /* get header row */
        QStringList lines = tsv.trimmed().split(QRegularExpression(R"(\n|\r\n|\r)"));

        m << QString("Found [%1] lines").arg(lines.size());
        if (lines.size() > 1) {
            QString header = lines.takeFirst();
            QStringList cols = header.trimmed().toLower().split(QRegularExpression("\\s*\t\\s*"));
            columns = cols;

            m << QString("Found [%1] columns [%2]").arg(cols.size()).arg(cols.join(","));
            /* remove the last column if it was blank, because the file contained an extra trailing comma */
            if (cols.last() == "") {
                cols.removeLast();
                m << QString("Last column was blank, removing").arg(cols.size());
            }

            qint64 numcols = cols.size();

            int row = 0;
            foreach (QString line, lines) {
                QString buffer = "";
                int col = 0;
                bool inQuotes = false;
                for (int i=0; i<line.size(); i++) {
                    QChar c = line.at(i);

                    /* determine if we're in quotes or not */
                    if (c == '"') {
                        if (inQuotes)
                            inQuotes = false;
                        else
                            inQuotes = true;
                    }

                    /* check if we've hit the next tab, and therefor should end the previous variable */
                    if ((c == '\t') && (!inQuotes)) {
                        table[row][cols[col]] = buffer.trimmed();

                        buffer = "";
                        col++;
                    }
                    else {
                        buffer = QString("%1%2").arg(buffer).arg(c); /* make sure no null terminators end up in the string */
                    }
                }
                /* acquire the last column */
                table[row][cols[col]] = buffer.trimmed();
                buffer = "";

                if ((col+1) != numcols) {
                    m << QString("Error: row [%1] has [%2] columns, but expecting [%3] columns").arg(row+1).arg(col+1).arg(numcols);
                    ret = false;
                }

                row++;
            }
            m << QString("Processed [%1] data rows").arg(row);
        }
        else {
            ret = false;
            m << ".tsv file contained only one row. The tsv must contain at least one header row and one data row";
        }

        msg = m.join("  \n");

        return ret;
    }


    /* ---------------------------------------------------------- */
    /* --------- CleanJSON -------------------------------------- */
    /* ---------------------------------------------------------- */
    QString CleanJSON(QString s) {
        s.replace("\"","");
        s.replace("\\\"","");
        s.replace("{","");
        s.replace("}","");
        s.replace(":", " : ");
        s.replace("\\n", "");
        s.replace("\\t", "");
        s.replace("\\\\", "");
        s.replace(QRegularExpression("\\\\n"), "");
        s.replace(QRegularExpression("\\n"), "");
        s.replace(QRegularExpression("\\\\t"), "");
        s.replace(QRegularExpression("\\t"), "");

        return s;
    }


    /* ---------------------------------------------------------- */
    /* --------- WriteTextFile ---------------------------------- */
    /* ---------------------------------------------------------- */
    bool WriteTextFile(QString filepath, QString str, bool append) {

        QFile f(filepath);
        if (append)
            f.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Append);
        else
            f.open(QIODevice::WriteOnly | QIODevice::Text);

        if (f.isOpen()) {
            QTextStream fs(&f);
            fs << str.replace("\r\n", "\n");
            f.close();
            return true;
        }
        else
            return false;
    }


    /* ---------------------------------------------------------- */
    /* --------- ReadTextFileToString --------------------------- */
    /* ---------------------------------------------------------- */
    QString ReadTextFileToString(QString filepath) {
        QString a;

        QFile inputFile(filepath);
        inputFile.open(QIODevice::ReadOnly);
        if (inputFile.isOpen()) {
            QTextStream in(&inputFile);

            QString line;
            while (in.readLineInto(&line)) {
                a.append(line + "\n");
            }
        }

        a = a.replace("\r\n", "\n");

        return a;
    }


    /* ---------------------------------------------------------- */
    /* --------- BatchRenameFiles ------------------------------- */
    /* ---------------------------------------------------------- */
    bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg) {

        QDir d;
        if (!d.exists(dir)) {
            msg = "directory [" + dir + "] does not exist";
            return false;
        }

        numfilesrenamed = 0;
        QStringList exts;
        exts << "*.img" << "*.hdr" << "*.nii" << "*.nii.gz" << "*.json" << "*.json.gz" << "*.bvec" << "*.bval";
        /* loop through all the extensions we want to rename/renumber */
        foreach (QString ext, exts) {
            int i = 1;
            QFile f;
            QDirIterator it(dir, QStringList() << ext, QDir::Files);
            while (it.hasNext()) {
                QString fname = it.next();
                f.setFileName(fname);
                QFileInfo fi(f);
                QString newName = fi.path() + "/" + QString("%1_%2_%3_%4%5").arg(uid).arg(studynum).arg(seriesnum).arg(i,5,10,QChar('0')).arg(ext.replace("*",""));
                if (f.rename(newName))
                    numfilesrenamed++;
                else
                    msg += QString("\nError renaming file [" + fname + "] to [" + newName + "]");
                i++;
            }
        }

        return true;
    }


    /* ---------------------------------------------------------- */
    /* --------- GetPatientAge ---------------------------------- */
    /* ---------------------------------------------------------- */
    double GetPatientAge(QString PatientAgeStr, QString StudyDate, QString PatientBirthDate) {
        double PatientAge(0.0);

        /* check if the patient age contains any characters */
        if (PatientAgeStr.contains('Y')) PatientAge = PatientAgeStr.replace("Y","").toDouble();
        if (PatientAgeStr.contains('M')) PatientAge = PatientAgeStr.replace("M","").toDouble()/12.0;
        if (PatientAgeStr.contains('W')) PatientAge = PatientAgeStr.replace("W","").toDouble()/52.0;
        if (PatientAgeStr.contains('D')) PatientAge = PatientAgeStr.replace("D","").toDouble()/365.25;

        /* fix patient age */
        if (PatientAge < 0.001) {
            QDate studydate;
            QDate dob;
            studydate.fromString(StudyDate);
            dob.fromString(PatientBirthDate);

            PatientAge = double(dob.daysTo(studydate))/365.25;
        }

        return PatientAge;
    }


    /* ---------------------------------------------------------- */
    /* --------- DirectoryExists -------------------------------- */
    /* ---------------------------------------------------------- */
    bool DirectoryExists(QString dir) {
        if (dir.trimmed() == "")
            return false;
        if (QFile::exists(dir))
            return true;
        else
            return false;
    }


    /* ---------------------------------------------------------- */
    /* --------- FileExists ------------------------------------- */
    /* ---------------------------------------------------------- */
    bool FileExists(QString f) {
        if (f.trimmed() == "")
            return false;
        else if (QFile::exists(f))
            return true;
        else
            return false;
    }


    /* ---------------------------------------------------------- */
    /* --------- CopyFileToDir ---------------------------------- */
    /* ---------------------------------------------------------- */
    bool CopyFileToDir(QString f, QString dir) {
        QFileInfo file(f);

        if (QFile::copy(f, QString("%1/%2").arg(dir).arg(file.fileName()))) {
            Print(QString("Copied file. Old name [%1] to new name [%2/%3]").arg(f).arg(dir).arg(file.fileName()));
            return true;
        }
        else {
            return false;
        }
    }




    /* ---------------------------------------------------------- */
    /* --------- CleanString ------------------------------------ */
    /* ---------------------------------------------------------- */
    QString CleanString(QString s) {
        s.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
        s.simplified().remove(' ');
        s.remove(" ");
        return s;
    }


    /* ---------------------------------------------------------- */
    /* --------- SQLQuery --------------------------------------- */
    /* ---------------------------------------------------------- */
    /* QSqlQuery object must already be prepared and bound before */
    /* being passed in to this function                           */
    bool SQLQuery(QSqlQuery &q, QString function, QString file, int line, bool d) {

        /* get the SQL string that will be run */
        QString sql = q.executedQuery();
        QVariantList list = q.boundValues();
        for (int i=0; i < list.size(); ++i) {
            sql += QString(" [" + list.at(i).toString() + "]");
        }

        if (d)
            Print(sql);

        /* run the query */
        if (q.exec())
            return true;
        else {
            /* if we get to this point, there is a SQL error */
            QString err = QString("SQL ERROR (Function: %1 File: %2 Line: %3)\n\nSQL (1) [%4]\n\nSQL (2) [%5]\n\nDatabase error [%6]\n\nDriver error [%7]").arg(function).arg(file).arg(line).arg(sql).arg(q.executedQuery()).arg(q.lastError().databaseText()).arg(q.lastError().driverText());
            qDebug() << err;
            qDebug() << q.lastError();

            return false;
        }
    }


    /* ---------------------------------------------------------- */
    /* --------- AnonymizeParams -------------------------------- */
    /* ---------------------------------------------------------- */
    QHash<QString, QString> AnonymizeParams(QHash<QString, QString> params) {
        QHash<QString, QString> p;
        QStringList anonFields;
        anonFields << "AcquisitionDate";
        anonFields << "AcquisitionTime";
        anonFields << "CommentsOnThePerformedProcedureSte";
        anonFields << "ContentDate";
        anonFields << "ContentTime";
        anonFields << "Filename";
        anonFields << "InstanceCreationDate";
        anonFields << "InstanceCreationTime";
        anonFields << "InstitutionAddress";
        anonFields << "InstitutionName";
        anonFields << "InstitutionalDepartmentName";
        anonFields << "OperatorsName";
        anonFields << "ParentDirectory";
        anonFields << "PatientBirthDate";
        anonFields << "PatientID";
        anonFields << "PatientName";
        anonFields << "PerformedProcedureStepDescription";
        anonFields << "PerformedProcedureStepID";
        anonFields << "PerformedProcedureStepStartDate";
        anonFields << "PerformedProcedureStepStartTime";
        anonFields << "PerformingPhysicianName";
        anonFields << "ReferringPhysicianName";
        anonFields << "RequestedProcedureDescription";
        anonFields << "RequestingPhysician";
        anonFields << "SeriesDate";
        anonFields << "SeriesDateTime";
        anonFields << "SeriesTime";
        anonFields << "StationName";
        anonFields << "StudyDate";
        anonFields << "StudyDateTime";
        anonFields << "StudyDescription";
        anonFields << "StudyTime";
        anonFields << "UniqueSeriesString";

        for(QHash<QString, QString>::iterator a = params.begin(); a != params.end(); ++a) {
            if (!anonFields.contains(a.key()))
                p[a.key()] = a.value();
        }

        return p;
    }

    /* ---------------------------------------------------------- */
    /* --------- PrintHeader ------------------------------------ */
    /* ---------------------------------------------------------- */
    void PrintHeader() {
        QString bindir = QDir::currentPath();

        Print("+----------------------------------------------------+");
        Print(QString("|  Squirrel utils version %1.%2\n|\n|  Build date [%3 %4]\n|  C++ [%5]\n|  Qt compiled [%6]\n|  Qt runtime [%7]\n|  Build system [%8]" ).arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN).arg(__DATE__).arg(__TIME__).arg(__cplusplus).arg(QT_VERSION_STR).arg(qVersion()).arg(QSysInfo::buildAbi()));
        Print(QString("|\n|  Current working directory is %1").arg(bindir));
        Print("+----------------------------------------------------+\n");
    }


    /* ---------------------------------------------------------- */
    /* --------- HumanReadableSize ------------------------------ */
    /* ---------------------------------------------------------- */
    QString HumanReadableSize(qint64 bytes)
    {
        QStringList units = {"B", "KB", "MB", "GB", "TB"};
        int i = 0;
        double sizeD = double(bytes);

        while (sizeD >= 1024 && i < units.size() - 1) {
            sizeD /= 1024;
            i++;
        }

        return QString::number(sizeD, 'f', 2) + " " + units[i];
    }


    /* ---------------------------------------------------------- */
    /* --------- PrintProgress ---------------------------------- */
    /* ---------------------------------------------------------- */
    void PrintProgress(double percentage) {
        int val = (int) (percentage * 100);
        int lpad = (int) (percentage * PBWIDTH);
        int rpad = PBWIDTH - lpad;
        printf("\r%3d%% [%.*s%*s]", val, lpad, PBSTR, rpad, "");
        fflush(stdout);
        if (val >= 100)
            printf("\n");
    }


    /* ---------------------------------------------------------- */
    /* --------- StringToDatetime ------------------------------- */
    /* ---------------------------------------------------------- */
    QDateTime StringToDatetime(QString datetime) {
        datetime = datetime.replace(' ', 'T') + "Z";

        QDateTime qdt = QDateTime::fromString(datetime, Qt::ISODate);
        qdt = qdt.toLocalTime();

        return qdt;
    }


    /* ---------------------------------------------------------- */
    /* --------- GetStagedFileList ------------------------------ */
    /* ---------------------------------------------------------- */
    QStringList GetStagedFileList(QString databaseUUID, qint64 objectID, ObjectType object) {
        QStringList paths;

        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select * from StagedFiles where ObjectRowID = :id and ObjectType = :type");
        q.bindValue(":id", objectID);
        q.bindValue(":type", squirrel::ObjectTypeToString(object));
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            paths.append(q.value("StagedPath").toString());
        }

        return paths;
    }


    /* ---------------------------------------------------------- */
    /* --------- StoreStagedFileList ---------------------------- */
    /* ---------------------------------------------------------- */
    void StoreStagedFileList(QString databaseUUID, qint64 objectID, ObjectType object, QStringList paths) {

        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        if (objectID >= 0) {
            /* delete previously staged files from the database */
            q.prepare("delete from StagedFiles where ObjectRowID = :id and ObjectType = :type");
            q.bindValue(":id", objectID);
            q.bindValue(":type", squirrel::ObjectTypeToString(object));
            utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

            foreach (QString path, paths) {
                q.prepare("insert into StagedFiles (ObjectRowID, ObjectType, StagedPath) values (:id, :type, :path)");
                q.bindValue(":id", objectID);
                q.bindValue(":type", squirrel::ObjectTypeToString(object));
                q.bindValue(":path", path);
                utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
            }
        }
    }


    /* ---------------------------------------------------------- */
    /* --------- RemoveStagedFileList --------------------------- */
    /* ---------------------------------------------------------- */
    void RemoveStagedFileList(QString databaseUUID, qint64 objectID, ObjectType object) {
        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("delete from StagedFiles where ObjectRowID = :id and ObjectType = :type");
        q.bindValue(":id", objectID);
        q.bindValue(":type", squirrel::ObjectTypeToString(object));
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    }


    /* ---------------------------------------------------------- */
    /* --------- GetParams -------------------------------------- */
    /* ---------------------------------------------------------- */
    QHash<QString, QString> GetParams(QString databaseUUID, qint64 seriesRowID) {
        QHash<QString, QString> params;

        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        q.prepare("select * from Params where SeriesRowID = :id");
        q.bindValue(":id", seriesRowID);
        utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
        while (q.next()) {
            QString key = q.value("ParamKey").toString();
            QString value = q.value("ParamValue").toString();
            params[key] = value;
        }

        return params;
    }


    /* ---------------------------------------------------------- */
    /* --------- StoreParams ------------------------------------ */
    /* ---------------------------------------------------------- */
    void StoreParams(QString databaseUUID, qint64 seriesRowID, QHash<QString, QString> params) {

        QSqlQuery q(QSqlDatabase::database(databaseUUID));
        if (seriesRowID >= 0) {
            /* delete previously staged files from the database */
            q.prepare("delete from Params where SeriesRowID = :id");
            q.bindValue(":id", seriesRowID);
            utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);

            for(QHash<QString, QString>::iterator a = params.begin(); a != params.end(); ++a) {
                QString key = a.key().trimmed();
                QString value = a.value().trimmed();

                if (key != "") {
                    q.prepare("insert into Params (SeriesRowID, ParamKey, ParamValue) values (:id, :key, :value)");
                    q.bindValue(":id", seriesRowID);
                    q.bindValue(":key", key);
                    q.bindValue(":value", value);
                    utils::SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
                }
            }
        }
    }


    /* ---------------------------------------------------------- */
    /* --------- PrintData -------------------------------------- */
    /* ---------------------------------------------------------- */
    QString PrintData(PrintFormat p, QStringList keys, QList <QStringHash> rows) {
        /* print the data - rows might have different keys, so always print by key */
        QStringList lines;
        if (p == CSV)
            lines += "\"" + keys.join("\",\"") + "\"";
        else
            lines += keys.join("\t");

        for(auto row : rows) {
            QStringList rowData;
            for(auto key : keys) {
                rowData.append(row[key]);
            }
            if (p == CSV)
                lines += "\"" + rowData.join("\",\"") + "\"";
            else
                lines += rowData.join("\t");
        }

        return utils::Print(lines.join("\n"));
    }


    /* ---------------------------------------------------------- */
    /* --------- MergeStringHash -------------------------------- */
    /* ---------------------------------------------------------- */
    QStringHash MergeStringHash(QStringHash hash1, QStringHash hash2) {
        for (auto it = hash2.constBegin(); it != hash2.constEnd(); ++it) {
            hash1.insert(it.key(), it.value());
        }
        return hash1;
    }

}
