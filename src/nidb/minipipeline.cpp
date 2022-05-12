/* ------------------------------------------------------------------------------
  NIDB minipipeline.cpp
  Copyright (C) 2004 - 2022
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

#include "minipipeline.h"
#include <QSqlQuery>

minipipeline::minipipeline(int id, nidb *a)
{
    n = a;
    minipipelineid = id;
    LoadMiniPipelineInfo();
}


/* ---------------------------------------------------------- */
/* --------- LoadMiniPipelineInfo --------------------------- */
/* ---------------------------------------------------------- */
void minipipeline::LoadMiniPipelineInfo() {

    if (minipipelineid < 1) {
        msg = "Invalid minipipeline ID";
        isValid = false;
        return;
    }

    /* load the minipipeline info */
    QSqlQuery q;
    q.prepare("select * from minipipelines where minipipeline_id = :minipipelineid");
    q.bindValue(":minipipelineid", minipipelineid);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
    if (q.size() < 1) {
        msg = "Mini-pipeline query returned no results. Possibly invalid minipipeline ID or recently deleted?";
        isValid = false;
        return;
    }
    q.first();

    name = q.value("mp_name").toString().trimmed();
    createDate = q.value("mp_createdate").toDateTime();
    modifyDate = q.value("mp_modifydate").toDateTime();
    version = q.value("mp_version").toInt();

    /* load the scripts */
    q.prepare("select * from minipipeline_scripts where minipipeline_id = :minipipelineid");
    q.bindValue(":minipipelineid", minipipelineid);
    //q.bindValue(":version", version);
    n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__, true);
    if (q.size() > 0) {
        while (q.next()) {
            miniPipelineScript mps;
            mps.id = q.value("minipipelinescript_id").toInt();
            mps.version = q.value("mp_version").toInt();
            mps.isExec = q.value("mp_executable").toBool();
            mps.isEntryPoint = q.value("mp_entrypoint").toBool();
            mps.filename = q.value("mp_scriptname").toString().trimmed();
            mps.filesize = q.value("mp_scriptsize").toInt();
            mps.parameterList = q.value("mp_parameterlist").toString().trimmed();
            mps.cDate = q.value("mp_scriptcreatedate").toDateTime();
            mps.mDate = q.value("mp_scriptmodifydate").toDateTime();
            mps.file = q.value("mp_script").toByteArray();
            scripts.append(mps);

            n->WriteLog(QString("Entry point [%1] [%2]").arg(mps.filename).arg(mps.isEntryPoint));
            if (mps.isEntryPoint)
                entrypoint = mps.filename;
        }
    }

    isValid = true;
    msg = "Loaded mini-pipeline details";
}


/* ---------------------------------------------------------- */
/* --------- WriteScripts ----------------------------------- */
/* ---------------------------------------------------------- */
bool minipipeline::WriteScripts(QString dir, QString &m) {

    QDir d(dir);
    if (!d.exists()) {
        m = "Directory [" + dir + "] does not exist";
        return false;
    }

    foreach (miniPipelineScript s, scripts) {
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

        /* set the permissions */
        if (s.isExec)
            f.setPermissions(QFile::ExeGroup | QFile::ExeOther | QFile::ExeOther | QFile::ExeUser);
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- GetJSONObject ---------------------------------- */
/* ---------------------------------------------------------- */
/* if path is specified, write the full JSON object to that
 * path and return a small JSON object */
QJsonObject minipipeline::GetJSONObject(QString path) {
	QJsonObject json, jsonSmall, jsonLarge;

	jsonSmall["name"] = name;

	jsonLarge["name"] = name;
	jsonLarge["createDate"] = createDate.toString();
	jsonLarge["modifyDate"] = createDate.toString();
	jsonLarge["version"] = version;
	jsonLarge["entrypoint"] = entrypoint;
	//jsonLarge["creator"] = creator;

	if (path == "") {
		/* return full JSON object */
		return jsonLarge;
	}
	else {
		/* write all minipipeline info to path */
		QString m;
		QString minipipelinepath = QString("%1/%2").arg(path).arg(name);
		if (!n->MakePath(minipipelinepath, m))
			n->WriteLog("Error creating path [" + minipipelinepath + "] because of [" + m + "]");

		QByteArray j = QJsonDocument(jsonLarge).toJson();
		QFile fout(QString("%1/%2/minipipeline.json").arg(path).arg(name));
		if (fout.open(QIODevice::WriteOnly))
			fout.write(j);
		else
			n->WriteLog("Error writing file [" + QString("%1/%2/minipipeline.json").arg(path).arg(name) + "]");

		/* return small JSON object */
		return jsonSmall;
	}
}
