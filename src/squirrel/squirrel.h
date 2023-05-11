/* ------------------------------------------------------------------------------
  Squirrel squirrel.h
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

#ifndef SQUIRREL_H
#define SQUIRREL_H

#include <QString>
#include <QDate>
#include <QDateTime>
#include <QDebug>
#include "squirrelSubject.h"
#include "squirrelExperiment.h"
#include "squirrelPipeline.h"
#include "squirrelMeasure.h"
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
    ~squirrel();

    bool read(QString filename, QString &m, bool validateOnly=false);
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
    QString format; /*!< package format. will always be 'squirrel' */
    QString subjectDirFormat; /*!< orig, seq */
    QString studyDirFormat; /*!< orig, seq */
    QString seriesDirFormat; /*!< orig, seq */
    QString dataFormat; /*!< orig, anon, anonfull, nift3d, nifti3dgz, nifti4d, nifti4dgz */
    QString filePath; /*!< full path to the zip file */
    qint64 GetUnzipSize();
    qint64 GetNumFiles();

    /* subject, pipeline, and experiment data */
    QList<squirrelSubject> subjectList; /*!< List of subjects within this package */
    QList<squirrelPipeline> pipelineList; /*!< List of pipelines within this package */
    QList<squirrelExperiment> experimentList; /*!< List of experiments within this package */

    /* searching/retrieval functions */
    bool GetSubject(QString ID, squirrelSubject &sqrlSubject);
    bool GetStudy(QString ID, int studyNum, squirrelStudy &sqrlStudy);
    bool GetSeries(QString ID, int studyNum, int seriesNum, squirrelSeries &sqrlSeries);
    bool GetSubjectList(QList<squirrelSubject> &subjects);
    bool GetStudyList(QString ID, QList<squirrelStudy> &studies);
    bool GetSeriesList(QString ID, int studyNum, QList<squirrelSeries> &series);
    bool GetDrugList(QString ID, QList<squirrelDrug> &drugs);
    bool GetMeasureList(QString ID, QList<squirrelMeasure> &measures);
    bool GetAnalysis(QString ID, int studyNum, QString pipelineName, squirrelAnalysis &sqrlAnalysis);
    bool GetPipeline(QString pipelineName, squirrelPipeline &sqrlPipeline);
    bool GetExperiment(QString experimentName, squirrelExperiment &sqrlExperiment);
    QString GetTempDir();
	bool valid() { return isValid; }
	bool okToDelete() { return isOkToDelete; }

private:
    void PrintPackage();
    bool MakeTempDir(QString &dir);
    QString Log(QString m, QString f);
    QString workingDir;
    QString logfile;
    QStringList msgs; /* squirrel messages, to be passed back upon writing (or reading) through the squirrel library */

	bool isValid;
	bool isOkToDelete;
};

#endif // SQUIRREL_H
