/* ------------------------------------------------------------------------------
  NIDB moduleManager.h
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

#ifndef MODULEMANAGER_H
#define MODULEMANAGER_H
#include "nidb.h"

/**
 * @brief Maintenance helper that clears stale module process records.
 *
 * moduleManager scans module_procs for entries that have not checked in for
 * several hours, removes their lock files, and deletes their database rows.
 */
class moduleManager
{
public:
    /**
     * @brief Construct a module manager bound to the active NiDB instance.
     * @param a NiDB application context used for logging, SQL, and config access
     */
    moduleManager(nidb *a);

    /**
     * @brief Destroy the module manager.
     */
    ~moduleManager();

    /**
     * @brief Remove stale module lock files and database records.
     * @return 1 if stale modules were processed, 0 if none were found
     */
    int Run();
private:
    nidb *n;
};

#endif // MODULEMANAGER_H
