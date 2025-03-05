/* ------------------------------------------------------------------------------
  Squirrel extract.cpp
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

#include "extract.h"
#include "utils.h"

extract::extract() {}

/* ---------------------------------------------------------------------------- */
/* ----- DoExtract ------------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
bool extract::DoExtract(QString packagePath, QString outputPath, QString objectType, QString objectIdentifier, QString subjectID, int studyNum, QString &m) {

    utils::Print(QString("Extracting subject [%1] from package [%2] to directory [%3]...").arg(objectIdentifier).arg(packagePath).arg(outputPath));

    /* read squirrel package */
    squirrel *sqrl = new squirrel();
    sqrl->SetFileMode(FileMode::ExistingPackage);
    sqrl->SetPackagePath(packagePath);
    sqrl->SetQuickRead(true);
    sqrl->Read();

    ObjectType object = sqrl->ObjectTypeToEnum(objectType);
    if (object == Subject) {
        /* find the subjectRowID */
        qint64 subjectRowID = sqrl->FindSubject(objectIdentifier);
        if (subjectRowID < 0) {
            utils::Print("Subject [" + objectIdentifier + "] not found in this package");
            delete sqrl;
            return false;
        }

        /* create outputDir */
        QString m;
        if (utils::MakePath(outputPath, m)) {
            utils::Print("Created output path [" + outputPath + "]");
        }
        else {
            utils::Print("Error creating output path [" + outputPath + "]. Message [" + m + "]");
            delete sqrl;
            return false;
        }

        /* extract the subject */
        utils::Print("Extracting subject [" + objectIdentifier + "] to output path [" + outputPath + "]");
        sqrl->ExtractObject(Subject, subjectRowID, outputPath);
    }
    else {
        m = "Invalid oject type [" + sqrl->ObjectTypeToString(object) + "] specified";
        delete sqrl;
        return false;
    }

    delete sqrl;
    return true;
}
