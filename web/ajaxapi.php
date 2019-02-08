<?
 // ------------------------------------------------------------------------------
 // NiDB ajaxapi.php
 // Copyright (C) 2004 - 2019
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
	session_start();
	require "functions.php";

	$action = GetVariable("action");
	$nfspath = GetVariable("nfspath");

	/* determine action */
	switch($action) {
		case 'validatepath':
			ValidatePath($nfspath);
			break;
	}
	

	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- ValidatePath ----------------------- */
	/* -------------------------------------------- */
	function ValidatePath($nfspath) {
		$p = trim($nfspath);

		$mdir = $GLOBALS['cfg']['mountdir'];

		$exists = 0;
		$writeable = 0;
		$msg = "";
		
		/* check for invalid paths before checking the drive to see if they exist */
		if (strpos($p, "..") !== false) {
			$msg = "Contains relative directory (..)";
		}
		else if (strpos($p, '\\') !== false) {
			$msg = "Contains backslash";
		}
		else if ($p == "/") {
			$msg = "Path is the root dir (/)";
		}
		else if ($p == "") {
			$msg = "Pathname is blank";
		}

		/* check if it exists and is writeable */
		else if (file_exists("$mdir/$p")) {
			$exists = 1;
			if (is_writable("$mdir/$p")) {
				$writeable = 1;
				$msg = "Path exists and is writeable";
			}
			else {
				$msg = "Path exists, but is not writeable";
			}
		}
		else {
			$msg = "Path does not exist";
		}
		if ($exists && $writeable) { $color = "green"; } else { $color = "red"; }
		echo " <span style='color: $color'>$msg</span>";
	}
	
?>