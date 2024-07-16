/* ------------------------------------------------------------------------------
  Squirrel squirrelGroupAnalysis.h
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


#ifndef SQUIRRELGROUPANALYSIS_H
#define SQUIRRELGROUPANALYSIS_H
#include <QtSql>
#include <QString>
#include <QDateTime>
#include <QJsonObject>

/**
 * @brief The squirrelGroupAnalysis class
 */
class squirrelGroupAnalysis
{
public:
    squirrelGroupAnalysis();
    QJsonObject ToJSON();
    QString PrintGroupAnalysis();
    bool Get();             /* gets the object data from the database */
    bool Store();           /* saves the object data from this object into the database */
    bool isValid() { return valid; }
    QString Error() { return err; }
    qint64 GetObjectID() { return objectID; }
    void SetObjectID(qint64 id) { objectID = id; }
    QString VirtualPath();
    QList<QPair<QString,QString>> GetStagedFileList();

    /* JSON elements */
    QString GroupAnalysisName;  /*!< name of the group analysis */
    QDateTime DateTime;         /*!< datetime of the group analysis */
    QString Description;        /*!< description of the group analysis */
    QString Notes;              /*!< notes about the group analysis */
    qint64 FileCount;            /*!< number of files in the analysis */
    qint64 Size;                /*!< disk size in bytes of the analysis */
    QString virtualPath;        /*!< path within the squirrel package, no leading slash */

    /* lib variables */
    QStringList stagedFiles;    /*!< staged file list: list of files in their own original paths which will be copied in before the package is zipped up */

private:
    bool valid = false;
    QString err;
    qint64 objectID = -1;
};

#endif // SQUIRRELGROUPANALYSIS_H
