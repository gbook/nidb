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
 * @brief read
 * @param filename
 * @return
 */
bool read(QString filename) {

    return true;
}


/* ------------------------------------------------------------ */
/* ----- write ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief write
 * @param path
 * @return
 */
bool write(QString path) {
    return true;
}


/* ------------------------------------------------------------ */
/* ----- validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief validate
 * @return
 */
bool validate() {

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


    /* iterate through subjects */
    for (int i=0; i < subjectList.size(); i++) {

        /* iterate through studies */

            /* iterate through series */
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

    /* check size of the subject list before and after adding */
    qint64 size = subjectList.size();

    subjectList.append(subj);

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
