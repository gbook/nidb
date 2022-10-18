/* ------------------------------------------------------------------------------
  Squirrel squirrel.h
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

#ifndef SQUIRREL_H
#define SQUIRREL_H

#include <QString>
#include <QDate>
#include <QDateTime>
#include "squirrelSubject.h"
#include "squirrelExperiment.h"
#include "squirrelPipeline.h"
#include "squirrelMeasure.h"
#include "squirrelMiniPipeline.h"
#include "squirrelDrug.h"
#include "squirrelVersion.h"

/**
 * @brief The squirrel class
 *
 * provides a complete class to read, write, and validate squirrel files
 */
class squirrel
{
public:
    squirrel();

    bool read(QString filename, bool validateOnly=false);
    bool write(QString outpath, QString &filepath, QString &m, bool debug=false);
    bool validate();
    void print();

    bool addSubject(squirrelSubject subj);
    bool addPipeline(squirrelPipeline pipe);
    bool addExperiment(squirrelExperiment exp);
    bool removeSubject(QString ID);

    /* package data */
    QDateTime datetime; /*!< datetime the package was created */
    QString description; /*!< detailed description of the package */
    QString name; /*!< name of the package */
    QString NiDBversion; /*!< NiDB version that wrote this package */
    QString version; /*!< squirrel version */
    QString format; /*!< package format, most likely 'squirrel' */
    QString subjectDirFormat; /*!< orig, seq */
    QString studyDirFormat; /*!< orig, seq */
    QString seriesDirFormat; /*!< orig, seq */
    QString dataFormat; /*!< orig, anon, anonfull, nift3d, nifti3dgz, nifti4d, nifti4dgz */
    qint64 GetUnzipSize();
    qint64 GetNumFiles();

private:
    void PrintPackage();
    bool MakeTempDir(QString &dir);
    QString Log(QString m, QString f);
    QString workingDir;
    QString logfile;
    QStringList msgs; /* squirrel messages, to be passed back upon writing (or reading) through the squirrel library */

    QList<squirrelSubject> subjectList; /*!< List of subjects within this package */
    QList<squirrelPipeline> pipelineList; /*!< List of pipelines within this package */
    QList<squirrelExperiment> experimentList; /*!< List of experiments within this package */

};

#endif // SQUIRREL_H
