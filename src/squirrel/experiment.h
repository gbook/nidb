/* ------------------------------------------------------------------------------
  Squirrel experiment.h
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

#ifndef EXPERIMENT_H
#define EXPERIMENT_H
#include <QString>
#include <QJsonObject>
#include <QJsonArray>

/**
 * @brief The experiment class
 */
class experiment
{
public:
    experiment();
    QJsonObject ToJSON();

    QString experimentName; /*!< experiment name (required) */
    qint64 numFiles; /*!< number of experiment files (required) */
    qint64 size; /*!< total size in bytes of the experiment files (required) */
    QString path; /*!< path to the experiment files, relative to the package root (required) */

};

#endif // EXPERIMENT_H
