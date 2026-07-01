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

/* ---------------------------------------------------------- */
/* --------- Print ------------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Print a string to stdout.
 * @param s String to print.
 * @param n If true, append a newline.
 * @param pad If true, pad the output to 80 characters.
 */
void Print(QString s, bool n, bool pad) {
    if (n)
        if (pad)
            printf("%-80s\n", s.toStdString().c_str());
        else
            printf("%s\n", s.toStdString().c_str());
    else
        if (pad)
            printf("%-80s", s.toStdString().c_str());
        else
            printf("%s", s.toStdString().c_str());
}


/* ---------------------------------------------------------- */
/* --------- CreateCurrentDateTime -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Create a formatted string for the current date and time.
 * @param format Numeric format selector.
 * @return Current date and/or time formatted according to the selector.
 */
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
/**
 * @brief Create a compact timestamp string for log names or entries.
 * @return Current date and time formatted as yyyyMMddHHmmss.
 */
QString CreateLogDate() {
    QString date;

    QDateTime d = QDateTime::currentDateTime();
    date = d.toString("yyyyMMddHHmmss");

    return date;
}


/* ---------------------------------------------------------- */
/* --------- SystemCommand ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run a shell command and collect its output.
 *
 * This function does not work in Windows.
 *
 * @param s Command string to execute.
 * @param detail If true, include the command and elapsed time in the result.
 * @param truncate If true, truncate very large command output.
 * @return Command output, optionally wrapped with execution details.
 */
QString SystemCommand(QString s, bool detail, bool truncate) {

    double starttime = double(QDateTime::currentMSecsSinceEpoch());
    QString ret;
    QString output;
    QString buffer;
    QProcess *process = new QProcess();

    /* in the off chance a null-terminator snuck in here */
    s.replace('\u0000', "");

    /* start QProcess and check if it started */
    process->start("sh", QStringList() << "-c" << s);
    if (!process->waitForStarted()) {
        output = "QProcess failed to start, with error [" + process->errorString() + "]";
    }
    /* collect the output */
    while(process->waitForReadyRead(-1)) {
        buffer = QString(process->readAll());
        output += buffer;
        //if (!bufferOutput)
        //	n->WriteLog(buffer,0,false);
    }
    /* check if it finished */
    process->waitForFinished();
    //if (!process->waitForFinished()) {
    //    output = "QProcess failed to finish, with error [" + process->errorString() + "]";
    //}

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
/* --------- SandboxedSystemCommand ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run a shell command inside a firejail sandbox.
 *
 * This function does not work in Windows.
 *
 * @param s Command string to execute from inside the sandbox directory.
 * @param dir Directory to expose as the private sandbox.
 * @param output Receives command output or an error message.
 * @param timeout Firejail timeout value.
 * @param detail If true, include command and elapsed-time details in output.
 * @param truncate If true, truncate very large command output.
 * @return true if the process completed without a QProcess error.
 */
bool SandboxedSystemCommand(QString s, QString dir, QString &output, QString timeout, bool detail, bool truncate) {

    double starttime = double(QDateTime::currentMSecsSinceEpoch());
    bool ret = true;
    QString outStr;
    QProcess process;
    double elapsedtime(0.0);

    /* check if the temp directory exists */
    QDir d(dir);
    if (!d.exists()) {
        output = "Error, sandbox dir [" + dir + "] does not exist";
        return false;
    }

    /* change to the home directory, which is where the jailed files will appear after running "firejail --private" */
    QDir::setCurrent("~");
    process.setProcessChannelMode(QProcess::MergedChannels);
    /* start the process */
    process.start("sh", QStringList() << "-c" << "firejail --timeout=" + timeout + " --quiet --private-cwd --private=" + dir + " ./" + s);
    QString command = "sh -cl 'firejail --timeout=" + timeout + " --quiet --private-cwd --private=" + dir + " ./" + s + "'";

    /* get the output, and wait for it to finish */
    if (process.waitForStarted(-1)) {
        while(process.waitForReadyRead(-1)) {
            outStr += process.readAll();
        }
    }
    process.waitForFinished();

    /* process should be done by now, check if there was an error */
    if ((process.errorString().trimmed() != "") && (process.errorString().trimmed() != "Unknown error")) {
        outStr += QString("Error [%1]. Exit status [%2]").arg(process.errorString()).arg(process.exitStatus());
        switch (process.error()) {
            case QProcess::FailedToStart: outStr += "Program failed to start. Executable not found?"; break;
            case QProcess::Crashed: outStr += "Program crashed"; break;
            case QProcess::Timedout: outStr += "Program timed out"; break;
            case QProcess::WriteError: outStr += "Program encountered a write error"; break;
            case QProcess::ReadError: outStr += "Program encountered a write error"; break;
            case QProcess::UnknownError: outStr += "Program encountered unknown error"; break;
        }
        ret = false;
    }
    else {
        elapsedtime = (double(QDateTime::currentMSecsSinceEpoch()) - starttime + 0.000001)/1000.0; /* add tiny decimal to avoid a divide by zero */

        outStr = outStr.trimmed();
        outStr.replace("’", "'");
        outStr.replace("‘", "'");

        /* truncate only if there was no error */
        if (truncate)
            if (outStr.size() > 10000)
                outStr = outStr.left(5000) + "\n\n     ...\n\n     OUTPUT TRUNCATED. Displaying only first and last 5,000 characters\n\n     ...\n\n" + outStr.right(5000);
    }

    /* format the final output */
    if (detail)
        output = QString("Executed command [%1], Output [%2], elapsed time [%3 sec]").arg(command).arg(outStr).arg(elapsedtime, 0, 'f', 3);
    else
        output = outStr;

    return ret;
}


/* ---------------------------------------------------------- */
/* --------- MakePath --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Create a directory path if it does not already exist.
 * @param p Path to create.
 * @param msg Receives a status or error message.
 * @param perm777 If true, chmod the path to 777 after creation.
 * @return true if the path exists or was created successfully.
 */
bool MakePath(QString p, QString &msg, bool perm777) {

    if ((p == "") || (p == ".") || (p == "..") || (p == "/") || (p.contains("//")) || (p == "/root") || (p == "/home")) {
        msg = "Path [" + p + "] is not valid";
        return false;
    }

    /* remove non printable unicode characters */
    p.replace('\u0000', "");

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
/**
 * @brief Recursively remove a directory after basic safety checks.
 * @param p Directory path to remove.
 * @param msg Receives an error message if removal fails.
 * @return true if the directory was removed successfully.
 */
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
/**
 * @brief Generate a random alphanumeric string.
 * @param n Number of characters to generate.
 * @return Random string containing letters and digits.
 */
QString GenerateRandomString(int n) {

   const QString chars("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789");
   QString randomString;
   for(int i=0; i<n; ++i)
   {
       QChar nextChar = chars.at(QRandomGenerator::global()->bounded(chars.length()));
       randomString.append(nextChar);
   }
   return randomString;
}


/* ---------------------------------------------------------- */
/* --------- NiDBMoveFile ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Move a file into a directory using the system mv command.
 * @param f File path to move.
 * @param dir Destination directory.
 * @param m Receives an error message on failure.
 * @return true if the file was moved successfully.
 */
bool NiDBMoveFile(QString f, QString dir, QString &m) {

    QDir d;
    if (d.exists(dir)) {
        QString systemstring;
        systemstring = QString("mv %1 %2/").arg(f).arg(dir);

        QString output = SystemCommand(systemstring, false).trimmed();
        if (output != "") {
            m = output;
            return false;
        }
    }
    else {
        m = QString("Directory [%1] does not exist").arg(dir);
        return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- NiDBCopyFile ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Copy a file into a directory using the system cp command.
 * @param f File path to copy.
 * @param dir Destination directory.
 * @param m Receives an error message on failure.
 * @return true if the file was copied successfully.
 */
bool NiDBCopyFile(QString f, QString dir, QString &m) {

    QDir d;
    if (d.exists(dir)) {
        QString systemstring;
        systemstring = QString("cp -u %1 %2/").arg(f).arg(dir);

        QString output = SystemCommand(systemstring, false).trimmed();
        if (output != "") {
            m = output;
            return false;
        }
    }
    else {
        m = QString("Directory [%1] does not exist").arg(dir);
        return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- RenameFile ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Rename or move a file using the system mv command.
 * @param filepathorig Original file path.
 * @param filepathnew New file path.
 * @param force If true, pass -f to mv.
 * @return true if the rename completed successfully.
 */
bool RenameFile(QString filepathorig, QString filepathnew, bool force) {

    if (filepathorig == filepathnew) {
        //WriteLog("RenameFile - old and new filename are the same");
        return true;
    }

    QString systemstring;
    if (force)
        systemstring = QString("mv -f %1 %2").arg(filepathorig).arg(filepathnew);
    else
        systemstring = QString("mv %1 %2").arg(filepathorig).arg(filepathnew);

    QString output = SystemCommand(systemstring, false).trimmed();
    /* check if there's an error message from mv */
    if (output == "")
        return true;
    else {
        //WriteLog("RenameFile() error. Running [" + systemstring + "] produced output [" + output + "]");
        return false;
    }
}


/* ---------------------------------------------------------- */
/* --------- FindAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Find files matching a pattern in a directory.
 * @param dir Directory to search.
 * @param pattern Filename pattern to match.
 * @param recursive If true, search subdirectories recursively.
 * @return List of matching file paths.
 */
QStringList FindAllFiles(QString dir, QString pattern, bool recursive) {
    //if (cfg["debug"] == "1") WriteLog("Finding all files in ["+dir+"] with pattern ["+pattern+"]");

    //QString systemstring;
    //if (recursive)
    //    systemstring = QString("find %1/ -name '%2' -type f").arg(dir).arg(pattern);
    //else
    //    systemstring = QString("find %1/ -maxdepth 1 -name '%2' -type f").arg(dir).arg(pattern);

    //Print("Checkpoint A1");
    //Print(systemstring);
    //QString output = SystemCommand(systemstring, false);
    //Print("Checkpoint A2");
    //Print(output);
    //Print("Checkpoint A3");
    //output.remove(QRegularExpression("[^\\x20-\\x7E]"));
    //Print("Checkpoint A4");
    QStringList files;
    //Print("Checkpoint A5");
    //files = output.split(QRegularExpression("\n|\r\n|\r"), Qt::SkipEmptyParts);
    //Print("Checkpoint A6");

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
/* --------- NiDBFindFirstFile ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Find the first file matching a pattern in a directory.
 * @param dir Directory to search.
 * @param pattern Filename pattern to match.
 * @param f Receives the first matching file path.
 * @param msg Receives an error message if the directory is invalid.
 * @param recursive If true, search subdirectories recursively.
 * @return true if a matching file was found.
 */
bool NiDBFindFirstFile(QString dir, QString pattern, QString &f, QString &msg, bool recursive) {

    QDir d = QDir(dir);
    if (!d.exists()) {
        msg = "Directory [" + dir + "] does not exist";
        return false;
    }

    f = "";

    QDirIterator::IteratorFlags flags = recursive ? QDirIterator::Subdirectories : QDirIterator::NoIteratorFlags;
    QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, flags);

    QStringList matches;
    while (it.hasNext())
        matches << it.next();

    if (matches.isEmpty())
        return false;

    matches.sort();
    f = matches.first();
    return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Move all matching files from one directory tree into another directory.
 * @param indir Directory tree to search.
 * @param pattern Filename pattern to match.
 * @param outdir Destination directory.
 * @param msg Receives any move error messages.
 * @return true if all matching files were moved successfully.
 */
bool MoveAllFiles(QString indir, QString pattern, QString outdir, QString &msg) {
    QStringList msgs;
    bool ret = true;
    QDirIterator it(indir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        QFile f(it.next());
        QString newfile = QString("%1/%2.dcm").arg(outdir).arg(GenerateRandomString(20));
        if (!f.rename(newfile)) {
            msgs << QString("Error moving [%1] to [%2]").arg(QFileInfo(f).filePath()).arg(newfile);
            ret = false;
        }
    }

    msg = msgs.join(" | ");
    return ret;
}

/* ---------------------------------------------------------- */
/* --------- FindAllDirs ------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Find directories matching a pattern.
 * @param dir Directory to search.
 * @param pattern Directory-name pattern to match, or "*" if blank.
 * @param recursive If true, search subdirectories recursively.
 * @param includepath If true, return full paths instead of directory names.
 * @return List of matching directories.
 */
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
/**
 * @brief Count files and total bytes in a directory.
 * @param dir Directory to inspect.
 * @param c Receives the number of files.
 * @param b Receives the total file size in bytes.
 * @param recurse If true, include files in subdirectories.
 */
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
/* --------- UnzipDirectory --------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Attempt to extract compressed files found in a directory.
 * @param dir Directory to process.
 * @param recurse If true, process compressed files in subdirectories.
 * @return Messages produced by extraction commands.
 */
QString UnzipDirectory(QString dir, bool recurse) {

    QStringList msgs;

    if (dir.trimmed() == "") {
        msgs << "Empty directory specified. Not attempting to unzip";
    }
    else {
        //msgs << "Directory before unzipping [" + dir + "] contains " + SystemCommand("ls " + dir, false);
        for (int i=0; i<3; i++) {
            QString prefix = QString("Unzipping pass [%1]: ").arg(i);
            QString maxdepth;
            if (recurse)
                maxdepth = "";
            else
                maxdepth = "-maxdepth 0";

            QStringList cmds;
            cmds << QString("cd %1; find . %2 -name '*.tar.gz' -exec tar -zxf {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.gz' -exec gunzip {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.z' -exec gunzip {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -iname '*.zip' -exec sh -c 'unzip -o -q -d \"${0%.*}\" \"$0\" && rm -v {}' '{}' ';'").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.tar.bz2' -exec tar -xjf {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.bz2' -exec bunzip {} \\;").arg(dir).arg(maxdepth);
            cmds << QString("cd %1; find . %2 -name '*.tar' -exec tar -xf {} \\;").arg(dir).arg(maxdepth);

            foreach (QString cmd, cmds) {
                QString output;
                output = SystemCommand(cmd,false);
                if (output != "")
                    msgs << prefix + output;
            }
        }
        //msgs << "Directory after unzipping [" + dir + "] contains " + SystemCommand("ls " + dir, false);
    }

    return msgs.join('\n');
}


/* ---------------------------------------------------------- */
/* --------- GetFileChecksum -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Calculate a cryptographic hash for a file.
 * @param fileName File path to hash.
 * @param hashAlgorithm Hash algorithm to use.
 * @return Hash bytes, or an empty QByteArray if the file cannot be read.
 */
QByteArray GetFileChecksum(const QString &fileName, QCryptographicHash::Algorithm hashAlgorithm) {
    QFile f(fileName);
    if (f.open(QFile::ReadOnly)) {
        QCryptographicHash hash(hashAlgorithm);
        if (hash.addData(&f)) {
            return hash.result();
        }
    }
    return QByteArray();
}


/* ---------------------------------------------------------- */
/* --------- RemoveNonAlphaNumericChars --------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Remove characters that are not letters, numbers, underscores, or dashes.
 * @param s Input string to sanitize.
 * @return Sanitized string.
 */
QString RemoveNonAlphaNumericChars(QString s) {
    return s.remove(QRegularExpression("[^a-zA-Z0-9_-]"));
}


/* ---------------------------------------------------------- */
/* --------- SortQStringListNaturally ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Sort a QStringList using natural numeric ordering.
 * @param s List to sort in place.
 */
void SortQStringListNaturally(QStringList &s) {

    if (s.size() < 2)
        return;

    QCollator coll;
    coll.setNumericMode(true);
    std::sort(s.begin(), s.end(), [&](const QString& s1, const QString& s2){ return coll.compare(s1, s2) < 0; });
}


/* ---------------------------------------------------------- */
/* --------- ParseDate -------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parse a date string into yyyy-MM-dd format.
 * @param s Date string to parse.
 * @return Parsed date, or 0000-01-01 if no supported format matches.
 */
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
/**
 * @brief Parse a time string into hh:mm:ss format.
 * @param s Time string to parse.
 * @return Parsed time, or 00:00:00 if no supported format matches.
 */
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
/* --------- chmod ------------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Set file permissions from a three-digit chmod-style string.
 * @param f File path to update.
 * @param perm Three-character permission string for owner, group, and others.
 * @return true if all requested permission changes succeeded.
 */
bool chmod(QString f, QString perm) {
    if (perm.size() != 3)
        return false;

    int owner    = QString(perm[0]).toInt();
    int group    = QString(perm[1]).toInt();
    int everyone = QString(perm[2]).toInt();

    QFileDevice::Permissions perms;

    switch (owner) {
        case 1: perms |= QFileDevice::ExeOwner; break;
        case 2: perms |= QFileDevice::WriteOwner; break;
        case 3: perms |= QFileDevice::ExeOwner | QFileDevice::WriteOwner; break;
        case 4: perms |= QFileDevice::ReadOwner; break;
        case 5: perms |= QFileDevice::ExeOwner | QFileDevice::ReadOwner; break;
        case 6: perms |= QFileDevice::ReadOwner | QFileDevice::WriteOwner; break;
        case 7: perms |= QFileDevice::ExeOwner | QFileDevice::WriteOwner | QFileDevice::ReadOwner; break;
    }

    switch (group) {
        case 1: perms |= QFileDevice::ExeGroup; break;
        case 2: perms |= QFileDevice::WriteGroup; break;
        case 3: perms |= QFileDevice::ExeGroup | QFileDevice::WriteGroup; break;
        case 4: perms |= QFileDevice::ReadGroup; break;
        case 5: perms |= QFileDevice::ExeGroup | QFileDevice::ReadGroup; break;
        case 6: perms |= QFileDevice::ReadGroup | QFileDevice::WriteGroup; break;
        case 7: perms |= QFileDevice::ExeGroup | QFileDevice::WriteGroup | QFileDevice::ReadGroup; break;
    }

    switch (everyone) {
        case 1: perms |= QFileDevice::ExeOther; break;
        case 2: perms |= QFileDevice::WriteOther; break;
        case 3: perms |= QFileDevice::ExeOther | QFileDevice::WriteOther; break;
        case 4: perms |= QFileDevice::ReadOther; break;
        case 5: perms |= QFileDevice::ExeOther | QFileDevice::ReadOther; break;
        case 6: perms |= QFileDevice::ReadOther | QFileDevice::WriteOther; break;
        case 7: perms |= QFileDevice::ExeOther | QFileDevice::WriteOther | QFileDevice::ReadOther; break;
    }

    return QFile::setPermissions(f, perms);
}


/* ---------------------------------------------------------- */
/* --------- JoinIntArray ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Join a list of integers into a string.
 * @param a Integer list to join.
 * @param glue Separator string.
 * @return Joined string.
 */
QString JoinIntArray(QList<int> a, QString glue) {
    if (a.size() == 0)
        return "";
    else if (a.size() == 1)
        return QString("%1").arg(a[0]);
    else {
        QStringList sa;
        for (int i=0; i<a.size();i++)
            sa << QString("%1").arg(a[i]);
        return sa.join(glue);
    }
}


/* ---------------------------------------------------------- */
/* --------- SplitStringArrayToInt -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Convert a list of strings to integers.
 * @param a String list to convert.
 * @return List of integer values.
 */
QList<int> SplitStringArrayToInt(QStringList a) {
    QList<int> i;

    if (a.size() > 0) {
        foreach (QString v, a) {
            i.append(v.trimmed().toInt());
        }
    }

    return i;
}


/* ---------------------------------------------------------- */
/* --------- SplitStringArrayToDouble ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Convert a list of strings to doubles.
 * @param a String list to convert.
 * @return List of double values.
 */
QList<double> SplitStringArrayToDouble(QStringList a) {
    QList<double> i;

    if (a.size() > 0) {
        foreach (QString v, a) {
            i.append(v.trimmed().toDouble());
        }
    }

    return i;
}


/* ---------------------------------------------------------- */
/* --------- SplitStringToIntArray -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Split a comma-separated string into integers.
 * @param a Comma-separated string.
 * @return List of integer values.
 */
QList<int> SplitStringToIntArray(QString a) {
    QList<int> i;

    if (a.size() > 0) {
        QStringList sl = a.split(',');
        i = SplitStringArrayToInt(sl);
    }

    return i;
}


/* ---------------------------------------------------------- */
/* --------- AppendCustomLog -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Append a timestamped message to a custom log file.
 * @param file Log file path.
 * @param msg Message to append.
 */
void AppendCustomLog(QString file, QString msg) {
    int pid = QCoreApplication::applicationPid();

    QFile f(file);
    if (f.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Append)) {
        QTextStream fs(&f);
        fs << QString("[%1][%2] %3\n").arg(CreateCurrentDateTime()).arg(pid).arg(msg);
        f.close();
    }
    else {
        //WriteLog("Error writing to file ["+file+"]");
    }
}


/* ---------------------------------------------------------- */
/* --------- ShellWords ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Extract double-quoted words from a shell-like command string.
 * @param s String to parse.
 * @return List of quoted values without quote characters.
 */
QStringList ShellWords(QString s) {

    QStringList words;
    QRegularExpression regex("\".*?\"", QRegularExpression::CaseInsensitiveOption);
    if (s.contains(regex)) {
        QRegularExpressionMatchIterator iterator = regex.globalMatch(s);
        while (iterator.hasNext()) {
            QRegularExpressionMatch match = iterator.next();
            QString matched = match.captured(0);
            matched.remove("\"");

            if (matched.length() > 0)
                words << matched;
        }
    }
    return words;
}


/* ---------------------------------------------------------- */
/* --------- IsInt ------------------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether a string can be converted to an integer.
 * @param s String to test.
 * @return true if the string is an integer.
 */
bool IsInt(QString s) {
    bool is = false;

    s.toInt(&is);

    if (is)
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- IsDouble --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether a string can be converted to a double.
 * @param s String to test.
 * @return true if the string is a double.
 */
bool IsDouble(QString s) {
    bool is = false;

    s.toDouble(&is);

    if (is)
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- IsNumber --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether a string can be converted to an integer or double.
 * @param s String to test.
 * @return true if the string is numeric.
 */
bool IsNumber(QString s) {
    if (IsInt(s) || IsDouble(s))
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- WrapText --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Insert newline characters into a string at fixed column intervals.
 * @param s String to wrap.
 * @param col Column interval for inserted newlines.
 * @return Wrapped string.
 */
QString WrapText(QString s, int col) {
    for (int i = col; i <= s.size(); i+=col+1)
        s.insert(i, "\n");

    return s;
}


/* ---------------------------------------------------------- */
/* --------- ParseCSV --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parse a CSV string with a required header row into an indexed hash.
 *
 * This handles most Excel-compatible CSV formats, but it does not handle nested
 * quotes and requires at least one header row and one data row.
 *
 * @note Performance: the indexedHash storage type (QHash<int, QHash<QString, QString>>)
 * hashes each column name string once per cell rather than once per column. For wide
 * files (500+ columns, 500+ rows) this results in hundreds of thousands of redundant
 * string hash operations. A future improvement would replace indexedHash with a
 * QVector<QVector<QString>> body plus a single QHash<QString,int> column-name-to-index
 * map built from the header row, reducing inner-loop access to a direct array index.
 *
 * @param csv CSV content to parse.
 * @param table Receives parsed row and column values.
 * @param columns Receives parsed lowercase column names.
 * @param msg Receives processing details and errors.
 * @return true if the CSV was parsed with the expected column count.
 */
bool ParseCSV(QString csv, indexedHash &table, QStringList &columns, QString &msg) {

    QStringList m;
    bool ret(true);

    /* get header row */
    QStringList lines = csv.trimmed().split(QRegularExpression("[\\n\\r]"));

    if (lines.size() > 1) {
        QString header = lines.takeFirst();
        QStringList rawCols = header.trimmed().toLower().split(QRegularExpression("\\s*,\\s*"));
        QStringList cols;
        for (const QString &c : rawCols)
            cols << QString(c).remove('"').trimmed();
        columns = cols;

        m << QString("Found [%1] columns [%2]").arg(cols.size()).arg(cols.join(","));
        /* remove the last column if it was blank, because the file contained an extra trailing comma */
        if (cols.last() == "") {
            cols.removeLast();
            m << QString("Last column was blank, removing").arg(cols.size());
        }

        qint64 numcols = cols.size();

        int row = 0;
        for (const QString &line : lines) {
            QString buffer;
            buffer.reserve(256);
            int col = 0;
            bool inQuotes = false;
            for (int i=0; i<line.size(); i++) {
                QChar c = line.at(i);

                if (c == '"') {
                    if (inQuotes) {
                        /* peek ahead: "" is an escaped quote, not a closing quote */
                        if ((i + 1 < line.size()) && (line.at(i + 1) == '"')) {
                            buffer += '"';
                            i++;
                        }
                        else {
                            inQuotes = false;
                        }
                    }
                    else {
                        inQuotes = true;
                    }
                }
                else if ((c == ',') && (!inQuotes)) {
                    QString val = buffer.trimmed();
                    if (!val.isEmpty())
                        table[row][cols[col]] = val;
                    buffer.clear();
                    col++;
                }
                else {
                    buffer += c;
                }
            }
            /* acquire the last column */
            QString lastVal = buffer.trimmed();
            if (!lastVal.isEmpty())
                table[row][cols[col]] = lastVal;
            buffer.clear();

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
/* --------- WriteTextFile ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Write text to a file.
 * @param filepath File path to write.
 * @param str Text to write.
 * @param append If true, append instead of overwriting.
 * @return true if the file was opened and written.
 */
bool WriteTextFile(QString filepath, QString str, bool append) {

    QFile f(filepath);
    if (append)
        f.open(QIODevice::WriteOnly | QIODevice::Text | QIODevice::Append);
    else
        f.open(QIODevice::WriteOnly | QIODevice::Text);

    if (f.isOpen()) {
        QTextStream fs(&f);
        fs << str;
        f.close();
        return true;
    }
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- ReadTextFileIntoArray -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read a text file into a list of trimmed lines.
 * @param filepath File path to read.
 * @param ignoreEmptyLines If true, omit empty lines.
 * @return List of lines read from the file.
 */
QStringList ReadTextFileIntoArray(QString filepath, bool ignoreEmptyLines) {
    QStringList a;

    QFile inputFile(filepath);
    inputFile.open(QIODevice::ReadOnly);
    if (inputFile.isOpen()) {
        QTextStream in(&inputFile);

        QString line;
        while (in.readLineInto(&line)) {
            line = line.trimmed();
            if (ignoreEmptyLines && (line.size() == 0)) {}
            else
                a.append(line);
        }
    }

    return a;
}


/* ---------------------------------------------------------- */
/* --------- ReadTextFileIntoString ------------------------- */
/* ---------------------------------------------------------- */
QString ReadTextFileIntoString(QString filepath) {
    QFile file(filepath);

    // Open the file in ReadOnly mode. Adding Text flag fixes line breaks automatically.
    if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
        qWarning() << "Failed to open file:" << file.errorString();
        return QString();
    }

    QTextStream in(&file);
    QString fileContent = in.readAll(); // Reads the entire file

    file.close();
    return fileContent;
}


/* ---------------------------------------------------------- */
/* --------- Mean ------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Calculate the mean value of a list of doubles.
 * @param a Values to average.
 * @return Mean value, or 0.0 for an empty list.
 */
double Mean(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    double sum = 0.0;
    foreach( double n, a )
        sum += n;

    return sum/double(a.size());
}


/* ---------------------------------------------------------- */
/* --------- Variance --------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Calculate the sample variance of a list of doubles.
 * @param a Values to evaluate.
 * @return Sample variance, or 0.0 for an empty list.
 */
double Variance(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    double mean = Mean(a);
    double temp = 0.0;

    foreach (double d, a)
        temp += (d-mean)*(d-mean);

    return temp/(double(a.size()-1));
}


/* ---------------------------------------------------------- */
/* --------- StdDev ----------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Calculate the sample standard deviation of a list of doubles.
 * @param a Values to evaluate.
 * @return Standard deviation, or 0.0 for an empty list.
 */
double StdDev(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    return sqrt(Variance(a));
}


/* ---------------------------------------------------------- */
/* --------- BatchRenameFiles ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Batch rename files into the NiDB archive filename format.
 * @param dir Directory containing files to rename.
 * @param seriesnum Series number to include in renamed files.
 * @param studynum Study number to include in renamed files.
 * @param uid Subject UID to include in renamed files.
 * @param numfilesrenamed Receives the number of files renamed.
 * @param msg Receives messages generated while renaming.
 * @return true if the directory was valid and processing completed.
 */
bool BatchRenameFiles(QString dir, QString seriesnum, QString studynum, QString uid, int &numfilesrenamed, QString &msg) {

    QDir d;
    if (!d.exists(dir)) {
        msg = "directory [" + dir + "] does not exist";
        return false;
    }

    numfilesrenamed = 0;
    QStringList exts;
    exts << "*.img" << "*.hdr" << "*.nii" << "*.nii.gz" << "*.json" << "*.bvec" << "*.bval";
    /* loop through all the extensions we want to rename/renumber */
    for (QString ext : exts) {
        int i = 1;
        QFile f;
        QDirIterator it(dir, QStringList() << ext, QDir::Files);

        /* get a list of files */
        QStringList files;
        while (it.hasNext()) {
            files.append(it.next());
        }
        /* sort the files */
        SortQStringListNaturally(files);

        /* rename the files */
        for (const QString &fname : files) {
            f.setFileName(fname);
            QFileInfo fi(f);
            QString newName = fi.path() + "/" + QString("%1_%2_%3_%4%5").arg(uid).arg(studynum).arg(seriesnum).arg(i,5,10,QChar('0')).arg(ext.replace("*", "", Qt::CaseInsensitive));
            msg += QString(fname + " --> " + newName);
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
/* --------- BatchRenameBIDSFiles --------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Rename converted imaging files into BIDS format.
 * @param dir Input directory containing files to rename.
 * @param bidsSubject BIDS sub label.
 * @param bidsSession BIDS ses label.
 * @param mapping BIDS mapping values used to build filenames and JSON fields.
 * @param numfilesrenamed Receives the number of files renamed.
 * @param msg Receives messages generated while renaming.
 * @return true if the directory was valid and processing completed.
 */
bool BatchRenameBIDSFiles(QString dir, QString bidsSubject, QString bidsSession, BIDSMapping mapping, int &numfilesrenamed, QString &msg) {

    //Print("Checkpoing BIDS A");
    QDir dd;
    if (!dd.exists(dir)) {
        msg = "directory [" + dir + "] does not exist";
        return false;
    }

    //Print("Checkpoing BIDS B");

    mapping.protocol.replace(QRegularExpression("[^a-zA-Z0-9]"), "");
    //Print("Checkpoing BIDS C");

    numfilesrenamed = 0;
    QStringList exts;
    //Print("Checkpoing BIDS D");

    exts << "*.img" << "*.hdr" << "*.nii" << "*.nii.gz" << "*.json" << "*.bvec" << "*.bval";
    //Print("Checkpoing BIDS E");

    /* loop through all the extensions we want to rename/renumber */
    foreach (QString ext, exts) {
        QFile f;
        QDirIterator it(dir, QStringList() << ext, QDir::Files);
        //Print("Checkpoing BIDS F");

        /* get a list of files */
        QStringList files;
        while (it.hasNext()) {
            files.append(it.next());
        }
        /* sort the files */
        SortQStringListNaturally(files);
        //Print("Checkpoing BIDS G");

        /* rename the files */
        int r = mapping.run;
        foreach (QString fname, files) {

            f.setFileName(fname);
            QFileInfo fi(f);
            QString newName;
            QString bidsSuf = mapping.bidsSuffix;

            /* special case where one series becomes two BIDS files */
            if (mapping.bidsSuffix == "magnitude1and2") {
                /* look for files ending in e1 and e2 file */
                if (fi.baseName().endsWith("_e1"))
                    bidsSuf = "magnitude1";
                if (fi.baseName().endsWith("_e2"))
                    bidsSuf = "magnitude2";
            }

            /* the order of the labels is important... task, acq, run, dir */
            QString fileBaseName = QString("%1_%2").arg(bidsSubject).arg(bidsSession);
            if (mapping.bidsTask != "")
                fileBaseName += QString("_task-%1").arg(mapping.bidsTask);
            if ((mapping.protocol != "") && (mapping.bidsIncludeAcquisition))
                fileBaseName += QString("_acq-%1").arg(mapping.protocol);
            if (r > 0)
                fileBaseName += QString("_run-%1").arg(r);
            if ((mapping.bidsEntity == "fmap") && (mapping.bidsSuffix == "epi")) /* PE direction required for fmap:epi */
                fileBaseName += QString("_dir-%1").arg(mapping.bidsPEDirection);

            newName = fi.path() + "/" + QString("%1_%2%3").arg(fileBaseName).arg(bidsSuf).arg(ext.replace("*",""));
            if (QFile::exists(newName)) {
                /* add run number if this file already exists */
                r++;

                fileBaseName = QString("%1_%2").arg(bidsSubject).arg(bidsSession);
                if (mapping.bidsTask != "")
                    fileBaseName += QString("_task-%1").arg(mapping.bidsTask);
                if ((mapping.protocol != "") && (mapping.bidsIncludeAcquisition))
                    fileBaseName += QString("_acq-%1").arg(mapping.protocol);
                if (r > 0)
                    fileBaseName += QString("_run-%1").arg(r);
                if ((mapping.bidsEntity == "fmap") && (mapping.bidsSuffix == "epi")) /* PE direction required for fmap:epi */
                    fileBaseName += QString("_dir-%1").arg(mapping.bidsPEDirection);

                newName = fi.path() + "/" + QString("%1_%2%3").arg(fileBaseName).arg(bidsSuf).arg(ext.replace("*",""));
            }

            //Print("Checkpoing BIDS H");

            msg += QString("\n" + fname + " --> " + newName);
            if (f.rename(newName))
                numfilesrenamed++;
            else
                msg += QString("\nError renaming file [" + fname + "] to [" + newName + "]\n");

            //Print("Checkpoing BIDS H 1");

            /* add IntendedFor entry to JSON file if needed */
            if (ext.endsWith(".json") && mapping.bidsIntendedForEntity != "") {
                //Print("Checkpoing BIDS H 2");
                QStringList intendedForEntityList = mapping.bidsIntendedForEntity.split(",");
                QStringList intendedForFileExtensionList = mapping.bidsIntendedForFileExtension.split(",");
                QStringList intendedForRunList = mapping.bidsIntendedForRun.split(",");
                QStringList intendedForSuffixList = mapping.bidsIntendedForSuffix.split(",");
                QStringList intendedForTaskList = mapping.bidsIntendedForTask.split(",");
                //Print("Checkpoing BIDS H 3");

                QJsonArray jsonIntendedFor;
                for (int i=0; i<intendedForEntityList.size(); i++) {
                    QString intendedForStr;
                    if (intendedForRunList.size() > 0)
                        intendedForStr = QString("bids::%1/%2/%3/%1_%2_task-%4_run-%5_%6.%7").arg(bidsSubject).arg(bidsSession).arg(intendedForEntityList[i]).arg(intendedForTaskList[i]).arg(intendedForRunList[i]).arg(intendedForSuffixList[i]).arg(intendedForFileExtensionList[i]);
                    else
                        intendedForStr = QString("bids::%1/%2/%3/%1_%2_task-%4_%5.%6").arg(bidsSubject).arg(bidsSession).arg(intendedForEntityList[i]).arg(intendedForTaskList[i]).arg(intendedForSuffixList[i]).arg(intendedForFileExtensionList[i]);
                    jsonIntendedFor.append(intendedForStr);
                }
                //Print("Checkpoing BIDS H 4");

                /* open existing JSON file */
                QFile jsonFile;
                jsonFile.setFileName(newName);
                if (!jsonFile.open(QIODevice::ReadOnly)) {
                    msg += "Error opening [" + newName + "]: " + jsonFile.errorString();
                    continue;
                }
                QByteArray jsonData = jsonFile.readAll();
                //Print("Checkpoing BIDS H 5");

                QJsonDocument d = QJsonDocument::fromJson(jsonData);
                QJsonObject root = d.object();
                //Print("Checkpoing BIDS H 6");

                /* add IntendedFor section */
                root["IntendedFor"] = jsonIntendedFor;

                /* save JSON file */
                QString j = QJsonDocument(root).toJson();
                if (!WriteTextFile(newName, j, false))
                    msg += "Error writing [" + newName + "]";

                //Print("Checkpoing BIDS H 7");
            }
            //Print("Checkpoing BIDS I");

            /* add a TaskName field to the JSON file if needed */
            if (ext.endsWith(".json") && ((mapping.bidsSuffix == "bold") || (mapping.bidsSuffix == "sbref"))) {
                QString task = mapping.bidsTask;

                /* open existing JSON file */
                QFile jsonFile;
                jsonFile.setFileName(newName);
                if (!jsonFile.open(QIODevice::ReadOnly)) {
                    msg += "Error opening [" + newName + "]: " + jsonFile.errorString();
                    continue;
                }
                QByteArray jsonData = jsonFile.readAll();

                QJsonDocument d = QJsonDocument::fromJson(jsonData);
                QJsonObject root = d.object();

                /* add IntendedFor section */
                root["TaskName"] = task;

                /* save JSON file */
                QString j = QJsonDocument(root).toJson();
                if (!WriteTextFile(newName, j, false))
                    msg += "Error writing [" + newName + "]";
            }
        }
    }
    //Print("Checkpoing BIDS J");

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetPatientAge ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get patient age at the time of an imaging study in years.
 * @param PatientAgeStr DICOM patient age string.
 * @param StudyDate Study date.
 * @param PatientBirthDate Patient birth date from DICOM.
 * @return Patient age in years, using the DICOM age string or date calculation.
 */
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
        studydate = QDate::fromString(StudyDate, "yyyy-MM-dd");
        dob = QDate::fromString(PatientBirthDate, "yyyy-MM-dd");

        PatientAge = double(dob.daysTo(studydate))/365.25;
    }

    return PatientAge;
}


/* ---------------------------------------------------------- */
/* --------- DirectoryExists -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether a directory path exists.
 * @param dir Directory path to check.
 * @return true if the path exists.
 */
bool DirectoryExists(QString dir) {
    QFile d(dir);
    if (d.exists())
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- FileExists ------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether a file path exists.
 * @param f File path to check.
 * @return true if the file exists.
 */
bool FileExists(QString f) {
    QFile file(f);
    if (file.exists())
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- FileDirectoryExists ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether the parent directory of a file path exists.
 * @param f File path whose parent directory should be checked.
 * @return true if the parent directory exists.
 */
bool FileDirectoryExists(QString f) {
    QFileInfo info(f);
    QDir d(info.absoluteDir());
    if (d.exists())
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- GetZipFileDetails ------------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Get summary details and listing text from a zip file.
 * @param zippath Zip file path.
 * @param unzipsize Receives total uncompressed size.
 * @param zipsize Receives total compressed size.
 * @param compression Receives compression summary text.
 * @param numfiles Receives number of files in the archive.
 * @param filelisting Receives the raw unzip listing.
 * @return true after attempting to read the zip listing.
 */
bool GetZipFileDetails(QString zippath, qint64 &unzipsize, qint64 &zipsize, QString &compression, qint64 &numfiles, QString &filelisting) {

    /* get the contents of the zip file */
    QString systemstring = "unzip -vl " + zippath;
    filelisting = SystemCommand(systemstring, false);

    /* get the zipped, unzipped sizes & numfiles from the filecontents listing */
    QStringList lines = filelisting.split("\n");
    QString lastline = lines.last().trimmed();
    //n->WriteLog(QString("Last line of [%1] %2").arg(systemstring).arg(lastline));
    QStringList parts = lastline.trimmed().split(QRegularExpression("\\s+"), Qt::SkipEmptyParts); /* split on whitespace */
    if (parts.size() > 2) {
        unzipsize = parts[0].toLongLong();
        zipsize = parts[1].toLongLong();
        compression = parts[2];
        numfiles = parts[3].replace(" files","").toLongLong();
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- isExecutableInstalled -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Check whether an executable can be found in the system path.
 * @param executableName Executable name to locate.
 * @return true if the executable was found.
 */
bool isExecutableInstalled(const QString &executableName) {
    QString path = QStandardPaths::findExecutable(executableName);
    return !path.isEmpty();
}


/* ---------------------------------------------------------- */
/* --------- extractBracketContent -------------------------- */
/* ---------------------------------------------------------- */
QString extractBracketContent(const QString &input) {
    int start = input.indexOf('[');
    int end = input.indexOf(']');
    if (start == -1 || end == -1 || end <= start)
        return {};
    return input.mid(start + 1, end - start - 1);
}


/* ---------------------------------------------------------- */
/* --------- extractAfterBracket ---------------------------- */
/* ---------------------------------------------------------- */
QString extractAfterBracket(const QString &input) {
    int end = input.indexOf(']');
    if (end == -1)
        return input;
    return input.mid(end + 1).trimmed();
}


/* ---------------------------------------------------------- */
/* --------- flattenJSON ------------------------------------ */
/* ---------------------------------------------------------- */
void flattenJSON(const QJsonObject &obj, QMap<QString, QString> &result, const QString &prefix)
{
    for (auto it = obj.constBegin(); it != obj.constEnd(); ++it) {
        QString key = prefix.isEmpty() ? it.key() : prefix + "_" + it.key();
        QJsonValue val = it.value();

        if (val.isObject()) {
            // Recurse into nested objects
            flattenJSON(val.toObject(), result, key);
        } else if (val.isArray()) {
            // Join array elements with ","
            QStringList items;
            for (const QJsonValue &item : val.toArray())
                items.append(item.toVariant().toString());
            result[key] = items.join(",");
        } else if (val.isNull()) {
            result[key] = "";
        } else {
            result[key] = val.toVariant().toString();
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- resizeImageFile -------------------------------- */
/* ---------------------------------------------------------- */
bool resizeImageFile(const QString &imagePath, int maxDimension)
{
    QImage image(imagePath);
    if (image.isNull())
        return false;

    if (image.width() <= maxDimension && image.height() <= maxDimension)
        return true;

    QImage resized = image.scaled(maxDimension, maxDimension, Qt::KeepAspectRatio, Qt::SmoothTransformation);

    return resized.save(imagePath);
}