<?
 // ------------------------------------------------------------------------------
 // NiDB upload.php
 // Copyright (C) 2004 - 2022
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

	define("LEGIT_REQUEST", true);
	session_start();

	require "functions.php";
	require "config.php";

	/* database connection */
	$linki = mysqli_connect($GLOBALS['mysqlhost'], $GLOBALS['mysqluser'], $GLOBALS['mysqlpass'], $GLOBALS['mysqldb']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

	$action = GetVariable("action");
	$seriesid = GetVariable("seriesid");


	if ($action == "uploadseries") {
		UploadSeries($seriesid);
	}
	
	/* -------------------------------------------- */
	/* ------- UploadSeries ----------------------- */
	/* -------------------------------------------- */
	function UploadSeries($seriesid) {
		$seriesid = mysqli_real_escape_string($GLOBALS['linki'], $seriesid);

		$output = array();
		$success = false;

		//echo "Hello";
		
		$outpath = GetDataPathFromSeriesID($seriesid);
		$output[] = $outpath;
		
		/* go through all the files and save them */
		mkdir($outpath, 0777, true);
		chmod($outpath, 0777);
		$output[] = print_r($_FILES);
		foreach ($_FILES as $i => $file) {
			$name = $file['name'];
			$tmpname = $file['tmp_name'];
			
			echo "Working on file [$name]\n";
			$filesize = 0;
			error_reporting(E_ALL);
			if (move_uploaded_file($tmpname, "$outpath/$name")) {
				$filesize = filesize("$outpath/$name");
				chmod("$outpath/$name", 0777);
				$success = true;
				$output[] = "SUCCESS: File [$name] written to [$outpath]";
			}
			else {
				$output[] = "ERROR moving [$tmpname] to [$outpath/$name]";
				$success = false;
			}
		}
		
		$filecount = count(glob("$outpath/*"));
		$filesize = GetDirectorySize($outpath);
		
		/* update the database to reflect the number of size of the files */
		$sqlstring = "update series set numfiles = $filecount, size = $filesize where series_id = $seriesid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		if ($success) {
			http_response_code (200);
		}
		else {
			http_response_code (400);
		}
		//echo "Hello";
		//echo "Ups error message";
		//set Content-Type to JSON
		header( 'Content-Type: application/json; charset=utf-8' );
		
		//echo error message as JSON
		echo json_encode( $output );
		
		//print_r($output);
	}

?>