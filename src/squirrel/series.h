/* ------------------------------------------------------------------------------
  Squirrel series.h
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

#ifndef SERIES_H
#define SERIES_H

#include <QString>
#include <QHash>
#include <QList>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The series class
 *
 * provides details of a series
 */
class series
{
public:
    series();
    void PrintSeries();
    QJsonObject ToJSON();
	QJsonObject ParamsToJSON();

    /* subject info */
	QString number; /*!< Series number. must be unique to the study */
	QDateTime dateTime; /*!< Series datetime */
	QString seriesUID; /*!< SeriesInstanceUID */
	QString description; /*!< Description of the series */
    QString protocol; /*!< Protocol (may differ from description) */
    qint64 numFiles; /*!< Number of files associated with the series */
    qint64 size; /*!< total size in bytes of the series */
    QHash<QString, QString> params; /*!< Hash containing experimental parameters. eg MR params */
    QStringList files; /*!< file list */

    QStringList experimentList; /*!< List of experiments attached to this series */

    QString virtualPath; /*!< path within the squirrel package, no leading slash */
};

#endif // SERIES_H
