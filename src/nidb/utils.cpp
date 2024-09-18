/* ------------------------------------------------------------------------------
  NIDB utils.cpp
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

#include "utils.h"

/* ---------------------------------------------------------- */
/* --------- Print ------------------------------------------ */
/* ---------------------------------------------------------- */
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
/* this function does not work in Windows                     */
/* ---------------------------------------------------------- */
QString SystemCommand(QString s, bool detail, bool truncate, bool bufferOutput) {

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
/* this function does not work in Windows                     */
/* ---------------------------------------------------------- */
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
/* --------- MoveFile --------------------------------------- */
/* ---------------------------------------------------------- */
bool MoveFile(QString f, QString dir, QString &m) {

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
/* --------- RenameFile ------------------------------------- */
/* ---------------------------------------------------------- */
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
QStringList FindAllFiles(QString dir, QString pattern, bool recursive) {
    //if (cfg["debug"] == "1") WriteLog("Finding all files in ["+dir+"] with pattern ["+pattern+"]");

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
/* --------- FindFirstFile ---------------------------------- */
/* ---------------------------------------------------------- */
bool FindFirstFile(QString dir, QString pattern, QString &f, QString &msg, bool recursive) {

    QDir d = QDir(dir);
    if (!d.exists()) {
        msg = "Directory [" + dir + "] does not exist";
        return false;
    }

    f = "";

    if (recursive) {
        QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks, QDirIterator::Subdirectories);
        if (it.hasNext())
            f = it.next();
    }
    else {
        QDirIterator it(dir, QStringList() << pattern, QDir::Files | QDir::NoDotAndDotDot | QDir::NoSymLinks);
        if (it.hasNext())
            f = it.next();
    }

    if (f.size() == 0)
        return false;
    else
        return true;
}


/* ---------------------------------------------------------- */
/* --------- MoveAllFiles ----------------------------------- */
/* ---------------------------------------------------------- */
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
/* --------- UnzipDirectory --------------------------------- */
/* ---------------------------------------------------------- */
/* perform one pass through a directory and attempt to unzip
 * any zipped files in it */
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
QString RemoveNonAlphaNumericChars(QString s) {
    return s.remove(QRegularExpression("[^a-zA-Z0-9_-]"));
}


/* ---------------------------------------------------------- */
/* --------- SortQStringListNaturally ----------------------- */
/* ---------------------------------------------------------- */
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
/* --------- chmod ------------------------------------------ */
/* ---------------------------------------------------------- */
bool chmod(QString f, QString perm) {
    if (perm.size() != 3)
        return false;

    int owner = QString(perm[0]).toInt();
    int group = QString(perm[1]).toInt();
    int everyone = QString(perm[2]).toInt();

    switch (owner) {
        case 1: if (!QFile::setPermissions(f, QFileDevice::ExeOwner)) return false; break;
        case 2: if (!QFile::setPermissions(f, QFileDevice::WriteOwner)) return false; break;
        case 3: if (!QFile::setPermissions(f, QFileDevice::ExeOwner | QFileDevice::WriteOwner)) return false; break;
        case 4: if (!QFile::setPermissions(f, QFileDevice::ReadOwner)) return false; break;
        case 5: if (!QFile::setPermissions(f, QFileDevice::ExeOwner | QFileDevice::ReadOwner)) return false; break;
        case 6: if (!QFile::setPermissions(f, QFileDevice::ReadOwner | QFileDevice::WriteOwner)) return false; break;
        case 7: if (!QFile::setPermissions(f, QFileDevice::ExeOwner | QFileDevice::WriteOwner | QFileDevice::ReadOwner)) return false; break;
    }

    switch (group) {
        case 1: if (!QFile::setPermissions(f, QFileDevice::ExeGroup)) return false; break;
        case 2: if (!QFile::setPermissions(f, QFileDevice::WriteGroup)) return false; break;
        case 3: if (!QFile::setPermissions(f, QFileDevice::ExeGroup | QFileDevice::WriteGroup)) return false; break;
        case 4: if (!QFile::setPermissions(f, QFileDevice::ReadGroup)) return false; break;
        case 5: if (!QFile::setPermissions(f, QFileDevice::ExeGroup | QFileDevice::ReadGroup)) return false; break;
        case 6: if (!QFile::setPermissions(f, QFileDevice::ReadGroup | QFileDevice::WriteGroup)) return false; break;
        case 7: if (!QFile::setPermissions(f, QFileDevice::ExeGroup | QFileDevice::WriteGroup | QFileDevice::ReadGroup)) return false; break;
    }

    switch (everyone) {
        case 1: if (!QFile::setPermissions(f, QFileDevice::ExeOther)) return false; break;
        case 2: if (!QFile::setPermissions(f, QFileDevice::WriteOther)) return false; break;
        case 3: if (!QFile::setPermissions(f, QFileDevice::ExeOther | QFileDevice::WriteOther)) return false; break;
        case 4: if (!QFile::setPermissions(f, QFileDevice::ReadOther)) return false; break;
        case 5: if (!QFile::setPermissions(f, QFileDevice::ExeOther | QFileDevice::ReadOther)) return false; break;
        case 6: if (!QFile::setPermissions(f, QFileDevice::ReadOther | QFileDevice::WriteOther)) return false; break;
        case 7: if (!QFile::setPermissions(f, QFileDevice::ExeOther | QFileDevice::WriteOther | QFileDevice::ReadOther)) return false; break;
    }
    return true;
}


/* ---------------------------------------------------------- */
/* --------- JoinIntArray ----------------------------------- */
/* ---------------------------------------------------------- */
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
bool IsNumber(QString s) {
    if (IsInt(s) || IsDouble(s))
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- WrapText --------------------------------------- */
/* ---------------------------------------------------------- */
QString WrapText(QString s, int col) {
    for (int i = col; i <= s.size(); i+=col+1)
        s.insert(i, "\n");

    return s;
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
/* --------- Mean ------------------------------------------- */
/* ---------------------------------------------------------- */
/**
 * Calculates the mean value from a list of doubles
 * @param a array of doubles
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
double StdDev(QList<double> a) {
    if (a.isEmpty())
        return 0.0;

    return sqrt(Variance(a));
}


/* ---------------------------------------------------------- */
/* --------- BatchRenameFiles ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Batch rename a directory of files into the NiDB archive filename format
 * @param dir Directory containing the files
 * @param seriesnum The series number
 * @param studynum The study number
 * @param uid The subject UID
 * @param numfilesrenamed Number of files renamed
 * @param msg Any messages generated while renaiming
 * @return `true` if successful, `false` otherwise
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
    foreach (QString ext, exts) {
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
        foreach (QString fname, files) {
            f.setFileName(fname);
            QFileInfo fi(f);
            QString newName = fi.path() + "/" + QString("%1_%2_%3_%4%5").arg(uid).arg(studynum).arg(seriesnum).arg(i,5,10,QChar('0')).arg(ext.replace("*",""));
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
 * @brief Rename files into BIDS format. These files are likely already converted from DICOM to Nifti
 * @param dir Input directory
 * @param bidsSubject BIDS `sub-` label
 * @param bidsSession BIDS `ses-` label
 * @param mapping BIDS Mapping structure
 * @param numfilesrenamed Number of files renamed
 * @param msg Any messages about the renaming process
 * @return `true` if successful, `false` otherwise
 */
bool BatchRenameBIDSFiles(QString dir, QString bidsSubject, QString bidsSession, BIDSMapping mapping, int &numfilesrenamed, QString &msg) {

    QDir d;
    if (!d.exists(dir)) {
        msg = "directory [" + dir + "] does not exist";
        return false;
    }

    mapping.protocol.replace(QRegularExpression("[^a-zA-Z0-9]"), "");

    numfilesrenamed = 0;
    QStringList exts;
    exts << "*.img" << "*.hdr" << "*.nii" << "*.nii.gz" << "*.json" << "*.bvec" << "*.bval";
    /* loop through all the extensions we want to rename/renumber */
    foreach (QString ext, exts) {
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
        int r = mapping.run;
        foreach (QString fname, files) {

            f.setFileName(fname);
            QFileInfo fi(f);
            QString newName;
            QString bidsSuf = mapping.bidsSuffix;

            /* special case where one series becomes two BIDS files */
            if (mapping.bidsSuffix == "magnitude1and2") {
                msg += "Renaming a fieldmap magnitude 1 and 2 file. One series was collected but converted to Nifti as two .nii.gz files";
                /* look for files ending in e1 and e2 file */
                if (fi.baseName().endsWith("_e1"))
                    bidsSuf = "magnitude1";
                if (fi.baseName().endsWith("_e2"))
                    bidsSuf = "magnitude2";
            }

            QString fileBaseName = QString("%1_%2").arg(bidsSubject).arg(bidsSession);
            if (r > 0)
                fileBaseName += QString("_run-%1").arg(r);
            if (mapping.bidsTask != "")
                fileBaseName += QString("_task-%1").arg(mapping.bidsTask);
            if (mapping.protocol != "")
                fileBaseName += QString("_acq-%1").arg(mapping.protocol);

            newName = fi.path() + "/" + QString("%1_%2%3").arg(fileBaseName).arg(bidsSuf).arg(ext.replace("*",""));
            if (QFile::exists(newName)) {
                /* add run number if this file already exists */
                msg += "File " + newName + " already exists\n";
                r++;

                if (r > 0)
                    fileBaseName += QString("_run-%1").arg(r);
                if (mapping.bidsTask != "")
                    fileBaseName += QString("_task-%1").arg(mapping.bidsTask);
                if (mapping.protocol != "")
                    fileBaseName += QString("_acq-%1").arg(mapping.protocol);

                newName = fi.path() + "/" + QString("%1_%2%3").arg(fileBaseName).arg(bidsSuf).arg(ext.replace("*",""));
            }

            msg += QString(fname + " --> " + newName + "\n");
            if (f.rename(newName))
                numfilesrenamed++;
            else
                msg += QString("\nError renaming file [" + fname + "] to [" + newName + "]\n");
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetPatientAge ---------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Get patient age at the time of the imaging study, in years. From DICOM age string, or from StudyDate/PatientBirthdate calculation
 * @param PatientAgeStr DICOM patient age string
 * @param StudyDate Study date
 * @param PatientBirthDate Patient DOB, from DICOM
 * @return the age in years of the patient
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
    QFile d(dir);
    if (d.exists())
        return true;
    else
        return false;
}


/* ---------------------------------------------------------- */
/* --------- FileExists ------------------------------------- */
/* ---------------------------------------------------------- */
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
bool GetZipFileDetails(QString zippath, qint64 &unzipsize, qint64 &zipsize, QString &compression, qint64 &numfiles, QString &filelisting) {

    /* get the contents of the zip file */
    QString systemstring = "unzip -vl " + zippath;
    filelisting = SystemCommand(systemstring, false);

    /* get the zipped, unzipped sizes & numfiles from the filecontents listing */
    QStringList lines = filelisting.split("\n");
    QString lastline = lines.last().trimmed();
    //n->WriteLog(QString("Last line of [%1] %2").arg(systemstring).arg(lastline));
    QStringList parts = lastline.trimmed().split(QRegularExpression("\\s+"), Qt::SkipEmptyParts); /* split on whitespace */
    if (parts.size() >= 2) {
        unzipsize = parts[0].toLongLong();
        zipsize = parts[1].toLongLong();
        compression = parts[2];
        numfiles = parts[3].replace(" files","").toLongLong();
    }

    return true;
}
