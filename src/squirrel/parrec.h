/* ------------------------------------------------------------------------------
  Squirrel parrec.h
  Copyright (C) 2004 - 2026
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

#ifndef PARREC_H
#define PARREC_H

#include <QString>
#include <QHash>

/**
 * @brief Reader for Philips PAR/REC headers (.par files, V3 - V4.2)
 *
 * Kept free of other squirrel/NiDB headers so both libsquirrel
 * (squirrelImageIO) and the nidb binary (imageIO) can share it.
 */
namespace parrec {
    QString FindParFile(QString f);
    bool ReadParFileTags(QString f, QHash<QString, QString> &tags, QString &msg);
}

#endif // PARREC_H
