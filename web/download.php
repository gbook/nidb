<?
 // ------------------------------------------------------------------------------
 // NiDB download.php
 // Copyright (C) 2004 - 2015
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

	$seriesid = GetVariable("seriesid");
	$type = GetVariable("type");
	$modality = GetVariable("modality");
	$filename = GetVariable("filename");

	$modality = strtolower($modality);
	if ($type == "file") {
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Type: text/csv");
		header("Content-length: " . filesize($filename) . "\n\n");
		header("Content-Transfer-Encoding: binary");
		// output data to the browser
		readfile($filename);
		unlink($filename);
	}
	else {
		/* get the path to the QA info */
		$sqlstring = "select a.*, b.study_num, d.uid from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id = $seriesid";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$series_num = $row['series_num'];
		$study_num = $row['study_num'];
		$uid = $row['uid'];
		$datatype = $row['data_type'];

		if ($datatype == "") {
			$datatype = "$modality";
		}
		
		if ($type == "beh") {
			$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/beh";
			$zipfilename = "$uid-$study_num-$series_num-beh.zip";
		}
		else {
			$datapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";
			$zipfilename = "$uid-$study_num-$series_num-$datatype.zip";
		}
		
		/* create the zip file in the tmp directory .... */
		$zipfilepath = $GLOBALS['cfg']['downloadpath'] . "/$zipfilename";
		/* create zip object */
		$systemstring = "zip -j $zipfilepath $datapath/*";
		$junk = exec($systemstring);
		
		//echo "Created $zipfilepath and moved it to " . $GLOBALS['cfg']['archivedir'] . "/$uid-$study_num-$series_num.zip";
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$zipfilename");
		header("Content-Type: application/zip");
		header("Content-length: " . filesize($zipfilepath) . "\n\n");
		header("Content-Transfer-Encoding: binary");
		// output data to the browser
		readfile($zipfilepath);
		unlink($zipfilepath);
	}
?>