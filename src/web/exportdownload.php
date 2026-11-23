<?
 // ------------------------------------------------------------------------------
 // NiDB exportdownload.php
 // Copyright (C) 2004 - 2026
 // Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
 // Olin Neuropsychiatry Research Center, Hartford Hospital
 // ------------------------------------------------------------------------------
 // GPLv3 License:

 // This program is free software: you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation, either version 3 of the License, or
 // (at your option) any later version.

 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.

 // You should have received a copy of the GNU General Public License
 // along with this program.  If not, see <http://www.gnu.org/licenses/>.
 // ------------------------------------------------------------------------------

/* Serves the .zip file of a web export (moduleExport writes these to [downloaddir], which is outside
   the docroot). Only the user who requested the export, or a site admin, may download it. Supports
   HTTP Range requests so large downloads can be resumed. */

define("LEGIT_REQUEST", true);

session_start();

require "functions.php";
require "includes_php.php"; /* redirects to login.php if not logged in */

$exportid = (int)GetVariable("exportid");

/* answer 404 for both "no such export" and "not yours", so export ids can't be probed */
if (!CanAccessExport($exportid)) {
	http_response_code(404);
	exit('Export not found, or you do not have permission to access it');
}

$zipfile = GetExportZipPath($exportid);
if ($zipfile == "") {
	http_response_code(404);
	exit('The export file does not exist. It may still be zipping, or it may have been removed');
}

StreamFile($zipfile, basename($zipfile), "application/zip");
?>
