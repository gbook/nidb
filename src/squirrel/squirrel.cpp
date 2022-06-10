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
 * @brief squirrel::read Reads a squirrel package
 * @param filename Full filepath of the package to read
 * @return true if package was successfully read, false otherwise
 */
bool squirrel::read(QString filename) {

    return true;
}


/* ------------------------------------------------------------ */
/* ----- write ------------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::write Writes a squirrel package using stored information
 * @param outpath full path to the output squirrel .zip file
 * @param dataFormat if converting from DICOM, write the data in the specified format
 *                   - 'orig' - Perform no conversion of DICOM images (not recommended as it retains PHI)
 *                   - 'anon' - Anonymize the DICOM files (light anonymization: remove PHI, but not ID or dates)
 *                   - 'nifti4d' - Nifti 4D
 *                   - 'nifti4dgz' - Nifti 4D gzip [default]
 *                   - 'nidti3d' - Nifti 3D
 *                   - 'nifti3dgz' - Nifti 3D gzip
 * @param dirFormat directory structure of the subject data
 *                  - 'orig' - Use the subjectID for subject directory names, studyNum for study directories, and seriesNum for series directories
 *                  - 'seq' - Use sequentially generated numbers for subject, study, series directories. These will be arbitrary
 * @return true if package was successfully written, false otherwise
 */
bool squirrel::write(QString outpath, QString dataFormat, QString dirFormat) {

	if (dirFormat == "")
		dirFormat = "orig";
	if (dataFormat == "")
		dataFormat == "nifti4dgz";

	/* create temp directory */
	MakeTempDir();

	/* ----- 1) write data. And set the relative paths in the objects ----- */
	/* iterate through subjects */
	for (int i=0; i < subjectList.size(); i++) {

		subject sub = subjectList[i];

		QString subjDir;
		if (dirFormat == "orig")
			subjDir = sub.ID;
		else
			subjDir = QString("%1").arg(i);

		subjDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
		QString vPath = QString("data/%1").arg(subjDir);
		subjectList[i].virtualPath = vPath;

		/* iterate through studies */
		for (int j=0; j < sub.studyList.size(); j++) {

			study stud = sub.studyList[j];

			QString studyDir;
			if (dirFormat == "orig")
				studyDir = QString("%1").arg(stud.studyNum);
			else
				studyDir = QString("%1").arg(j);

			studyDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
			QString vPath = QString("data/%1/%2").arg(subjDir).arg(studyDir);
			subjectList[i].studyList[j].virtualPath = vPath;

			/* iterate through series */
			for (int k=0; k < stud.seriesList.size(); k++) {

				series ser = stud.seriesList[k];

				QString seriesDir;
				if (dirFormat == "orig")
					seriesDir = ser.seriesNum;
				else
					seriesDir = QString("%1").arg(k);

				seriesDir.replace(QRegularExpression("[^a-zA-Z0-9 _-]", QRegularExpression::CaseInsensitiveOption), "");
				QString vPath = QString("data/%1/%2/%3").arg(subjDir).arg(studyDir).arg(seriesDir);
				subjectList[i].studyList[j].seriesList[k].virtualPath = vPath;

				QString m;
				QString seriesPath = QString("%1/%2").arg(workingDir).arg(subjectList[i].studyList[j].seriesList[k].virtualPath);
				MakePath(seriesPath,m);

				/* copy all of the series files to the temp directory */
				foreach (QString f, ser.files) {
					QString systemstring = QString("cp -uv %1 %2/%3").arg(f).arg(workingDir).arg(subjectList[i].studyList[j].seriesList[k].virtualPath);
					Print(SystemCommand(systemstring));
				}

				/* write the series .json file, containing the dicom header params */
				QJsonObject params;
				params = ser.ParamsToJSON();
				QByteArray j = QJsonDocument(params).toJson();
				QFile fout(QString("%1/params.json").arg(seriesPath));
				fout.open(QIODevice::WriteOnly);
				fout.write(j);
			}
		}
	}

	/* ----- 2) write .json file ----- */
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

    /* add subjects */
    for (int i=0; i < subjectList.size(); i++) {
		JSONsubjects.append(subjectList[i].ToJSON(workingDir));
    }
	root["numSubjects"] = JSONsubjects.size();
	root["subjects"] = JSONsubjects;

    /* add pipelines */
    if (pipelineList.size() > 0) {
        QJsonArray JSONpipelines;
        for (int i=0; i < pipelineList.size(); i++) {
			JSONpipelines.append(pipelineList[i].ToJSON(workingDir));
        }
		root["numPipelines"] = JSONpipelines.size();
		root["pipelines"] = JSONpipelines;
    }

    /* add experiments */
    if (experimentList.size() > 0) {
        QJsonArray JSONexperiments;
        for (int i=0; i < experimentList.size(); i++) {
            JSONexperiments.append(experimentList[i].ToJSON());
        }
		root["numExperiments"] = JSONexperiments.size();
		root["experiments"] = JSONexperiments;
    }

	/* write the final .json file */
	QByteArray j = QJsonDocument(root).toJson();
	QFile fout(QString("%1/squirrel.json").arg(workingDir));
	fout.open(QIODevice::WriteOnly);
	fout.write(j);

	/* zip the temp directory into the output file */
	QString zipfile = outpath;
	if (!zipfile.endsWith(".zip"))
		zipfile += ".zip";

	QString systemstring = "cd " + workingDir + "; zip -1rv " + zipfile + " .";
	Print("Beginning zipping package...");
	Print(SystemCommand(systemstring, true));
	Print("Finished zipping package...");

	if (FileExists(zipfile)) {
		Print("Created .zip file [" + zipfile + "]");

		/* delete the tmp dir, if it exists */
		if (DirectoryExists(workingDir)) {
			Print("Temporary export dir [" + workingDir + "] exists and will be deleted");
			QString m;
			if (!RemoveDir(workingDir, m))
				Print("Error [" + m + "] removing directory [" + workingDir + "]");
		}
	}
	else {
		Print("Error creating zip file [" + zipfile + "]");
	}

    return true;
}


/* ------------------------------------------------------------ */
/* ----- validate --------------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::validate
 * @return true if valid squirrel file, false otherwise
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
 * @brief squirrel::PrintPackage
 */
void squirrel::PrintPackage() {

    Print("-- SQUIRREL PACKAGE ----------");
    Print(QString("   Date: %1").arg(datetime.toString()));
    Print(QString("   Description: %1").arg(description));
    Print(QString("   Name: %1").arg(name));
    Print(QString("   Version: %1").arg(version));
    Print(QString("   Format: %1").arg(format));

}


/* ------------------------------------------------------------ */
/* ----- MakeTempDir ------------------------------------------ */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::MakeTempDir
 * @return
 */
bool squirrel::MakeTempDir() {
	workingDir = QString("/tmp/%1").arg(GenerateRandomString(20));
	QString m;
	if (MakePath(workingDir, m))
		return true;
	else
		return false;
}


/* ------------------------------------------------------------ */
/* ----- DeleteTempDir ---------------------------------------- */
/* ------------------------------------------------------------ */
/**
 * @brief squirrel::DeleteTempDir
 * @return
 */
bool squirrel::DeleteTempDir() {
	QString m;
	if (RemoveDir(workingDir, m))
		return true;
	else
		return false;
}
