/* ------------------------------------------------------------------------------
  NIDB experiment.cpp
  Copyright (C) 2004 - 2023
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

#include "experiment.h"
#include <QSqlQuery>

experiment::experiment()
{

}

experiment::experiment(int id, nidb *a)
{
    n = a;
    experimentid = id;
    LoadExperimentInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadExperimentInfo ----------------------------- */
/* ---------------------------------------------------------- */
void experiment::LoadExperimentInfo() {

    if (experimentid < 1) {
        msg = "Invalid experiment ID";
        isValid = false;
        return;
    }

    /* load the experiment info */
    QSqlQuery q;
    q.prepare("select * from experiments where experiment_id = :experimentid");
    q.bindValue(":experimentid", experimentid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1) {
        msg = "Experiment query returned no results. Possibly invalid experiment ID or recently deleted?";
        isValid = false;
        return;
    }
    q.first();

    name = q.value("exp_name").toString().trimmed();
    createDate = q.value("exp_createdate").toDateTime();
    modifyDate = q.value("exp_modifydate").toDateTime();
    version = q.value("mp_version").toInt();
    desc = q.value("exp_desc").toString();
    creator = q.value("exp_creator").toString();

    /* load the files */
    q.prepare("select * from experiment_files where experiment_id = :experimentid");
    q.bindValue(":experimentid", experimentid);
    //q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
    if (q.size() > 0) {
        while (q.next()) {
            experimentFile f;
            f.id = q.value("experimentfile_id").toInt();
            f.filename = q.value("file_name").toString().trimmed();
            f.filesize = q.value("file_size").toInt();
            f.cDate = q.value("file_createdate").toDateTime();
            f.mDate = q.value("file_modifydate").toDateTime();
            f.file = q.value("file").toByteArray();
            files.append(f);
        }
    }

    isValid = true;
    msg = "Loaded mini-experiment details";
}


/* ---------------------------------------------------------- */
/* --------- WriteFiles ------------------------------------- */
/* ---------------------------------------------------------- */
bool experiment::WriteFiles(QString dir, QString &m) {

    QDir d(dir);
    if (!d.exists()) {
        m = "Directory [" + dir + "] does not exist";
        return false;
    }

    foreach (experimentFile s, files) {
        QString filename = dir + "/" + s.filename;

        QFile f(filename);
        f.open(QIODevice::WriteOnly);
        f.write(QByteArray::fromBase64(s.file));
        f.close();

        /* check if file actually exists */
        if (!QFile::exists(filename)) {
            m = "Created file [" + filename + "] does not exist";
            return false;
        }

        /* check the size of the file */
        QFileInfo fi(filename);
        if (fi.size() != s.filesize) {
            m = QString("Created file size [%1] does not match database file size [%2]").arg(fi.size()).arg(s.filesize);
            return false;
        }
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetJSONObject ---------------------------------- */
/* ---------------------------------------------------------- */
/* if path is specified, write the full JSON object to that
 * path and return a small JSON object */
QJsonObject experiment::GetJSONObject(QString path) {
    QJsonObject json, jsonSmall, jsonLarge;

    jsonSmall["name"] = name;

    jsonLarge["name"] = name;
    jsonLarge["desc"] = desc;
    jsonLarge["createDate"] = createDate.toString();
    jsonLarge["modifyDate"] = createDate.toString();
    jsonLarge["version"] = version;
    jsonLarge["creator"] = creator;

    if (path == "") {
        /* return full JSON object */
        return jsonLarge;
    }
    else {
        /* write all experiment info to path */
        QString m;
        QString experimentpath = QString("%1/%2").arg(path).arg(name);
        if (!MakePath(experimentpath, m))
            n->WriteLog("Error creating path [" + experimentpath + "] because of [" + m + "]");

        QByteArray j = QJsonDocument(jsonLarge).toJson();
        QFile fout(QString("%1/%2/experiment.json").arg(path).arg(name));
        if (fout.open(QIODevice::WriteOnly))
            fout.write(j);
        else
            n->WriteLog("Error writing file [" + QString("%1/%2/experiment.json").arg(path).arg(name) + "]");

        /* return small JSON object */
        return jsonSmall;
    }
}


/* ---------------------------------------------------------- */
/* --------- GetSquirrelObject ------------------------------ */
/* ---------------------------------------------------------- */
squirrelExperiment experiment::GetSquirrelObject() {
    squirrelExperiment s;

    s.experimentName = name;
    s.numFiles = files.size();

    return s;
}
