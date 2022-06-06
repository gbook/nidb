/* ------------------------------------------------------------------------------
  Squirrel squirrel.cpp
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

#include "squirrel.h"
#include "../nidb/utils.h"

/* ------------------------------------------------------------ */
/* ----- squirrel --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::squirrel
 */
squirrel::squirrel()
{
    datetime = QDateTime::currentDateTime();
    description = "Package created by squirrelutils";
    name = "Squirrel package";
    version = QString("%1.%2").arg(SQUIRREL_VERSION_MAJ).arg(SQUIRREL_VERSION_MIN);
    format = "squirrel";
}


/* ------------------------------------------------------------ */
/* ----- read ------------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::read
 * @param filename
 * @return
 */
bool squirrel::read(QString filename) {

    return true;
}


/* ------------------------------------------------------------ */
/* ----- write ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::write
 * @param path
 * @return
 */
bool squirrel::write(QString path) {

	/* create JSON package info */
	//PrintPackage();
	/* create JSON object */
	QJsonObject root;

	QJsonObject pkgInfo;
	pkgInfo["name"] = name;
	pkgInfo["description"] = description;
	pkgInfo["datetime"] = CreateCurrentDateTime(2);
	pkgInfo["format"] = format;
	pkgInfo["version"] = version;

	root["_package"] = pkgInfo;

	QJsonArray JSONsubjects;

	/* iterate through subjects */
	for (int i=0; i < subjectList.size(); i++) {

		subject sub = subjectList[i];
		QJsonObject subjInfo = sub.ToJSON();

		/* Add list of studies to the current subject, then append the subject to the subject list */
		subjInfo["studies"] = JSONsubjects;
		JSONsubjects.append(subjInfo);
	}

    return true;
}


/* ------------------------------------------------------------ */
/* ----- validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::validate
 * @return
 */
bool squirrel::validate() {

    return true;
}


/* ------------------------------------------------------------ */
/* ----- print ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief print
 */
void squirrel::print() {

    /* print package info */
	PrintPackage();

    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

		subject sub = subjectList[i];
		sub.PrintSubject();

        /* iterate through studies */
		for (int j=0; j < sub.studyList.size(); j++) {

			study stud = sub.studyList[j];
			stud.PrintStudy();

            /* iterate through series */
			for (int k=0; k < stud.seriesList.size(); k++) {

				series ser = stud.seriesList[k];
				ser.PrintSeries();
			}
		}
    }
}


/* ------------------------------------------------------------ */
/* ----- addSubject ------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::addSubject
 * @param subj
 * @return true if added, false if not added
 */
bool squirrel::addSubject(subject subj) {
	//Print("Checkpoint 1");

    /* check size of the subject list before and after adding */
    qint64 size = subjectList.size();
	//Print("Checkpoint 2");

	subjectList.append(subj);
	//Print("Checkpoint 3");

    if (subjectList.size() > size)
        return true;
    else
        return false;
}


/* ------------------------------------------------------------ */
/* ----- removeSubject ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::removeSubject
 * @param ID
 * @return true if subject found and removed, false is subject not found
 */
bool squirrel::removeSubject(QString ID) {

    for(int i=0; i < subjectList.count(); ++i) {
		if (subjectList[i].ID == ID) {
            subjectList.remove(i);
            return true;
        }
    }
    return false;
}


/* ------------------------------------------------------------ */
/* ----- PrintPackage ----------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief PrintPackage
 */
void squirrel::PrintPackage() {

    Print("-- SQUIRREL PACKAGE ----------");
    Print(QString("   Date: %1").arg(datetime.toString()));
    Print(QString("   Description: %1").arg(description));
    Print(QString("   Name: %1").arg(name));
    Print(QString("   Version: %1").arg(version));
    Print(QString("   Format: %1").arg(format));

}
