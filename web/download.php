<?
 // ------------------------------------------------------------------------------
 // NiDB download.php
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

	define("LEGIT_REQUEST", true);
	
	session_start();

	require "functions.php";

	$seriesid = GetVariable("seriesid");
	$type = GetVariable("type");
	$modality = GetVariable("modality");
	$filename = GetVariable("filename");

	$modality = strtolower($modality);
	if ($type == "file") {
		if (substr($filename,0,5) != "/tmp/") {
			?><div class="staticmessage">You are attempting to download a file [<?=$filename?>] from an incorrect location</div><br>Go <a href="<?=$_SERVER["HTTP_REFERER"]?>">back</a> to referring page<?
		}
		else {
			if (!file_exists($filename)) {
				?><div class="staticmessage">The file you are attempting to download [<?=$filename?>] does not exist</div><br>Go <a href="<?=$_SERVER["HTTP_REFERER"]?>">back</a> to referring page<?
			}
			else {
				if (filesize($filename) == 0) {
					/* this may not correctly check files larger than 2GB in size... not sure if they'd ever return 0 size if they did exist though... */
					?><div class="staticmessage">The file you are attempting to download [<?=$filename?>] exists, but is empty</div><br>Go <a href="<?=$_SERVER["HTTP_REFERER"]?>">back</a> to referring page<?
				}
				else {
					header("Content-Description: File Transfer");
					header("Content-Disposition: attachment; filename=$filename");
					header("Content-Type: text/csv");
					header("Content-length: " . filesize($filename) . "\n\n");
					header("Content-Transfer-Encoding: binary");
					// output data to the browser
					readfile($filename);
					unlink($filename);
				}
			}
		}
	}
	else {
		/* get the path to the QA info */
		$sqlstring = "select a.*, b.study_num, d.uid from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id = $seriesid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
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
		$zipfilepath = $GLOBALS['cfg']['downloaddir'] . "/$zipfilename";
		/* create zip object */
		$systemstring = "zip -j $zipfilepath $datapath/*";
		$junk = exec($systemstring);
		
		if (!file_exists($datapath)) {
			?>
				<div class="staticmessage">The archive path [<?=$datapath?>] for this data does not exist</div><br>
				Go <a href="<?=$_SERVER["HTTP_REFERER"]?>">back</a> to referring page
			<?
		}
		else {
			if (!file_exists($zipfilepath)) {
				?><div class="staticmessage">The archive path [<?=$datapath?>] exists, but the server was unable to create a zip file [<?=$zipfilepath?>]</div><br>Go <a href="<?=$_SERVER["HTTP_REFERER"]?>">back</a> to referring page<?
			}
			else {
				if (filesize($zipfilepath) == 0) {
					?><div class="staticmessage">The archive path [<?=$datapath?>] exists, and the server was unable to create a zip file [<?=$zipfilepath?>], but the zip file is empty</div><br>Go <a href="<?=$_SERVER["HTTP_REFERER"]?>">back</a> to referring page<?
				}
				else {
					header("Content-Description: File Transfer");
					header("Content-Disposition: attachment; filename=$zipfilename");
					header("Content-Type: application/zip");
					header("Content-length: " . filesize($zipfilepath) . "\n\n");
					header("Content-Transfer-Encoding: binary");
					// output data to the browser
					readfile($zipfilepath);
					unlink($zipfilepath);
				}
			}
		}
	}
?>