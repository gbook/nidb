<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
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
	require "includes_php.php";

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpscriptname = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpscriptname))
		$selfcall = true;
	else
		$selfcall = false;
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	if (is_array(GetVariable("seriesid")))
		$seriesids = GetVariable("seriesid");
	else
		$seriesid = GetVariable("seriesid");
	$modality = GetVariable("modality");
	
	//PrintVariable($_POST);
	//PrintVariable($_GET);
	//PrintVariable($_FILES);

	
	/* determine action */
	if (($selfcall && $action == "upload")) {
		UploadFile($seriesid, $modality, $file);
	}
	else {
		?>
		<html>
			<head>
				<link rel="icon" type="image/png" href="images/squirrel.png">
				<title>NiDB - Behavioral data analysis pipelines</title>
			</head>
		<body>
			<div id="wrapper">
		<?
		require "includes_html.php";
		require "menu.php";
		
		DisplaySeriesList($seriesids, $modality);

		include("footer.php");
		
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplaySeriesList ------------------ */
	/* -------------------------------------------- */
	function DisplaySeriesList($seriesids, $modality) {
		$seriesids = mysqli_real_escape_array($seriesids);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);

		if (!IsNiDBModality($modality)) {
			echo "Invalid modality [$modality]<br>";
			return;
		}

		?>
		<link rel="stylesheet" href="scripts/dropzone.css">
		<script src="scripts/dropzone.js"></script>
		<style>
			table .batchupload { border: 2px solid #444; border-radius: 8px; border-spacing: 0px; width: 90%; }
			table .batchupload thead:last-of-type th { border-bottom: 2px solid #444; }
			table .batchupload th { padding: 5px; background-color: #444; color: #fff; }
			table .batchupload td { padding: 7px; border-top: 1px solid #ddd; border-right: 1px solid #ddd; vertical-align: middle; }
			table .batchupload tr:hover td { background-color: lightyellow; border-top: 1px solid gold; }
			table .batchupload tr.newuid td { border-top: 2px solid #444; }
		</style>
		<div align="center">
		<form action="batchupload.php" method="post">
			<input name="action" type="hidden" value="displayseries">
			<?
				foreach ($seriesids as $seriesid) {
				?>
				<input name="seriesid[]" type="hidden" value="<?=$seriesid?>">
				<?
				}
			?>
			<input name="modality" type="hidden" value="<?=$modality?>">
			<input type="submit" value="Refresh Page" title="Refresh to view uploaded files" style="font-size: 14pt">
		</form>
		<br>
		<? if ($modality == "mr") { echo "Data for MRI series will be uploaded as <u>behavioral</u> data<br><br>"; } ?>
		<table class="batchupload">
			<thead>
				<th style="text-align: center; border-right: 1px solid #ddd">&nbsp;</th>
				<th colspan="2" style="text-align: center; border-right: 1px solid #ddd">Subject</th>
				<th colspan="3" style="text-align: center; border-right: 1px solid #ddd">Study</th>
				<th colspan="4" style="text-align: center;">Series</th>
			</thead>
			<thead>
				<th style="border-right: 1px solid #ddd">Upload</th>
				<th>UID</th>
				<th style="border-right: 1px solid #ddd">Age</th>
				<th>Study</th>
				<th>Modality</th>
				<th style="border-right: 1px solid #ddd">Date</th>
				<th>Number</th>
				<th>Date</th>
				<th>Protocol</th>
				<th>Files</th>
			</thead>
			<tbody>
			<?
				$seriesidlist = implode2(",", $seriesids);
				$sqlstring = "select a.*, b.study_id, b.study_datetime, b.study_num, b.study_ageatscan, d.uid from $modality"."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality"."series_id in ($seriesidlist) order by d.uid, b.study_num, a.series_num";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				$lastuid = "";
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$uid = $row['uid'];
					$age = $row['study_ageatscan'];
					$studydate = $row['study_datetime'];
					$studydate = date('M j, Y g:ia',strtotime($row['study_datetime']));
					$studynum = $row['study_num'];
					$studyid = $row['study_id'];
					$seriesnum = $row['series_num'];
					$seriesdate = date('M j, Y g:ia',strtotime($row['series_datetime']));
					$seriesdesc = $row['series_desc'];
					$seriesid = $row[$modality."series_id"];
					if ($seriesdesc == "")
						$seriesdesc = $row['series_protocol'];
					
					if (($uid != $lastuid) && ($lastuid != "")) {
						$tdclass = "newuid";
					}
					else {
						$tdclass = "";
					}
					?>
					<tr class="<?=$tdclass?>">
						<td>
							<form action="batchupload.php" class="dropzone" id="dropzone<?=$seriesid?>" style="margin-block-end: 0px">
								<input name="action" type="hidden" value="upload">
								<input name="seriesid" type="hidden" value="<?=$seriesid?>">
								<input name="modality" type="hidden" value="<?=$modality?>">
								<div class="fallback">
									<input name="file" type="file" multiple />
								</div>
							</form>
						</td>
						<td><?=$uid?></td>
						<td><?=$age?></td>
						<td><a href="studies.php?studyid=<?=$studyid?>"><?="$uid$studynum"?></a></td>
						<td><?=strtoupper($modality)?></td>
						<td><?=$studydate?></td>
						<td><?=$seriesnum?></td>
						<td><?=$seriesdate?></td>
						<td><?=$seriesdesc?></td>
						<td>
							<span class="tiny">
							<?
								list($datapath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
								if (strtolower($modality) == "mr")
									$datapath = "$datapath/beh";
								$filelist = array_diff(scandir($datapath), array('..', '.'));

								$numfiles = count($filelist);
								$numremain = $numfiles;
								$i = 0;
								foreach ($filelist as $file) {
									echo "$file<br>";
									$i++;
									$numremain--;
									if ($i >= 5)
										break;
								}
								if ($numremain > 0)
									echo "<br><b>[$numremain] additonal files not listed</b>";
							?>
							</span>
						</td>
					</tr>
					<?
					$lastuid = $uid;
				}
			?>
			</tbody>
		</table>
		</div>
		<?
	}
	
	/* -------------------------------------------- */
	/* ------- UploadFile ------------------------- */
	/* -------------------------------------------- */
	function UploadFile($seriesid, $modality) {
		$seriesid = mysqli_real_escape_string($GLOBALS['linki'], $seriesid);
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);

		if (!IsNiDBModality($modality)) {
			echo "Invalid modality [$modality]<br>";
			return 0;
		}
		
		list($outpath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
		
		if ($modality == "mr")
			$outpath = "$outpath/beh";

		/* go through all the files and save them */
		mkdir($outpath, 0777, true);
		chmod($outpath, 0777);
		foreach ($_FILES['file']['name'] as $i => $name) {
			$filesize = 0;
			error_reporting(E_ALL);
			if (move_uploaded_file($_FILES['file']['tmp_name'][$i], "$outpath/$name")) {
				$filesize = filesize("$outpath/$name");
				chmod("$outpath/$name", 0777);
				$success = 1;
				echo "SUCCESS: File [$name] written to [$outpath]<br>";
			}
			else {
				echo "ERROR moving [" . $_FILES['file']['tmp_name'][$i] . "] to [$outpath/$name]<br>";
				$success = 0;
			}
		}
		
		$filecount = count(glob("$outpath/*"));
		$filesize = GetDirectorySize($outpath);
		
		/* update the database to reflect the number of size of the files */
		if ($modality == "mr")
			$sqlstring = "update mr_series set numfiles_beh = $filecount, beh_size = $filesize where mrseries_id = $seriesid";
		else
			$sqlstring = "update $modality"."_series set series_numfiles = $filecount, series_size = $filesize where $modality"."series_id = $seriesid";
		
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}
?>
