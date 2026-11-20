/* ------------------------------------------------------------------------------
  Squirrel explore.h
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

#ifndef EXPLORE_H
#define EXPLORE_H

#include <QString>

/**
 * @brief The explore class
 *
 * Interactive REPL to browse the contents of an existing squirrel package.
 * The package's metadata is read once into memory, then the user issues
 * commands (ls, info, help, quit) to navigate the subject/study/series tree.
 */
class explore
{
public:
    /* Open the package and run the interactive loop. Returns a process exit
       code (0 on normal exit, non-zero if the package could not be read). */
    int Run(const QString &packagePath);
};

#endif // EXPLORE_H
