<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
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

	define("LEGIT_REQUEST", true);
	session_start();
	
	require "functions.php";
	require "includes_php.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$seriesids = array();
	$seriesid = 0;
	if (is_array(GetVariable("seriesid")))
		$seriesids = GetVariable("seriesid");
	else
		$seriesid = (int)GetVariable("seriesid");
	$modality = strtolower(trim((string)GetVariable("modality")));

	
	/* determine action */
	if ($action == "upload") {
		/* mutating: must be a POST carrying this session's CSRF token (Dropzone posts the form's hidden inputs,
		   including the token). Answer with an HTTP error, not the page, so Dropzone shows the upload as failed */
		if ((($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') || (!VerifyCSRFToken(false))) {
			http_response_code(403);
			echo "This upload could not be verified. Your session may have expired - please reload the page and try again.";
			exit;
		}
		UploadFile($seriesid, $modality);
	}
	else {
		?>
		<html>
			<head>
				<link rel="icon" type="image/png" href="images/squirrel.png">
				<title>NiDB - Batch data upload</title>
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
		if ((GetSeriesTableName($modality) == '') || (!IsNiDBModality($modality))) {
			echo "Invalid modality [" . htmlspecialchars($modality) . "]<br>";
			return;
		}

		?>
		<!-- self-hosted copy (5.5.0, also used by importremote.php) rather than an unpinned CDN -->
		<link rel="stylesheet" href="scripts/dropzone.css">
		<script src="scripts/dropzone.js"></script>
		<style>
			table .batchupload { border: 2px solid #444; border-radius: 8px; border-spacing: 0px; width: 90%; }
			table .batchupload thead:last-of-type th { border-bottom: 2px solid #444; }
			table .batchupload th { padding: 5px; background-color: #444; color: #fff; }
			table .batchupload td { padding: 7px; border-top: 1px solid #ddd; border-right: 1px solid #ddd; vertical-align: middle; }
			table .batchupload tr:hover td { background-color: lightyellow; border-top: 1px solid gold; }
			table .batchupload tr.newuid td { border-top: 2px solid #444; }
			
			.dropzone {
				min-height: 40px;
				border: 2px dashed #888;
				border-radius: 6px;
				padding: 10px 10px;
				margin: 1px;
			}
			.dropzone:hover {
				background-color: lavender;
			}
			.dz-default {
				margin: 1px !important;
			}
			.dz-message {
				margin: 1px !important;
			}
			.dz-preview {
				margin: 1px !important;
			}
			.dz-details {
				margin: 1px !important;
			}
		</style>
		<div class="ui container">
		<form action="batchupload.php" method="post">
			<input name="action" type="hidden" value="displayseries">
			<?
				foreach ($seriesids as $seriesid) {
					$seriesid = (int)$seriesid;
				?>
				<input name="seriesid[]" type="hidden" value="<?=$seriesid?>">
				<?
				}
			?>
			<input name="modality" type="hidden" value="<?=htmlspecialchars($modality)?>">
			<input type="submit" value="Refresh Page" title="Refresh to view uploaded files" style="font-size: 14pt" class="ui primary button">
		</form>
		<br>
		<? if ($modality == "mr") { echo "Data for MRI series will be uploaded as <u>behavioral</u> data<br><br>"; } ?>
		<table class="ui celled table">
			<thead>
				<th style="text-align: center; border-right: 1px solid #ddd">&nbsp;</th>
				<th colspan="3" style="text-align: center; border-right: 1px solid #ddd">Subject</th>
				<th colspan="3" style="text-align: center; border-right: 1px solid #ddd">Study</th>
				<th colspan="4" style="text-align: center;">Series</th>
			</thead>
			<thead>
				<th style="border-right: 1px solid #ddd">Upload</th>
				<th>UID</th>
				<th>Age</th>
				<th style="border-right: 1px solid #ddd">Calculated Age</th>
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
				$seriesidlist = implode(",", array_map('intval', (array)$seriesids));
				if ($seriesidlist != "") {
					$sqlstring = "select a.*, b.study_id, b.study_datetime, b.study_num, b.study_ageatscan, c.project_id, d.uid, d.birthdate from $modality"."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality"."series_id in ($seriesidlist) order by d.uid, b.study_num, a.series_num";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__); /* ids are intval'd, modality validated above */

					/* only list series the user has View Data on; the upload box needs Edit Data (also checked in UploadFile) */
					$rows = array();
					$projectids = array();
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$rows[] = $row;
						$projectids[] = (int)$row['project_id'];
					}
					$perms = GetCurrentUserProjectPermissions($projectids);
					$numhidden = 0;

					$lastuid = "";
					foreach ($rows as $row) {
						$rowprojectid = (int)$row['project_id'];
						if (GetPerm($perms, 'viewdata', $rowprojectid) != 1) {
							$numhidden++;
							continue;
						}
						$canupload = (GetPerm($perms, 'modifydata', $rowprojectid) == 1);
						$uid = $row['uid'];
						$dob = $row['birthdate'];
						$age = $row['study_ageatscan'];
						$studydate = $row['study_datetime'];
						$studydate = date('M j, Y g:ia',strtotime($row['study_datetime']));
						$studynum = $row['study_num'];
						$studyid = $row['study_id'];
						$seriesnum = $row['series_num'];
						$seriesdate = date('M j, Y g:ia',strtotime($row['series_datetime']));
						$seriesdesc = $row['series_desc'];
						$seriesid = (int)$row[$modality."series_id"];
						if ($seriesdesc == "")
							$seriesdesc = $row['series_protocol'];
						
						list($studyAge, $calcStudyAge) = GetStudyAge($dob, $age, $studydate);
						
						if ($studyAge == null)
							$studyAge = "-";
						else
							$studyAge = number_format($studyAge,1);

						if ($calcStudyAge == null)
							$calcStudyAge = "-";
						else
							$calcStudyAge = number_format($calcStudyAge,1);
						
						if (($uid != $lastuid) && ($lastuid != "")) {
							$tdclass = "newuid";
						}
						else {
							$tdclass = "";
						}
						?>
						<tr class="<?=$tdclass?>">
							<td>
								<? if ($canupload) { ?>
								<script>
								// Note that the name "myDropzone" is the camelized
								// id of the form.
								Dropzone.options.dropzone<?=$seriesid?> = {
									// Note: using "function()" here to bind `this` to
									// the Dropzone instance.
									parallelUploads:10,
									uploadMultiple:true,
									init: function() {
										this.on("addedfile", file => {
											console.log("A file has been added");
										});
										this.on("sendingmultiple", function() {
											console.log("sending multiple files");
										});
										this.on("successmultiple", function(files, response) {
											console.log("successmultiple [" + response + "]");
										});
										this.on("errormultiple", function(files, response) {
											console.log("errormultiple [" + response + "]");
										});										
										this.on("success", function(files, response) {
											console.log("success [" + response + "]");
										});
										this.on("error", function(files, response) {
											console.log("error [" + response + "]");
										});
									}
								};
								</script>
								<form action="batchupload.php" class="dropzone" id="dropzone<?=$seriesid?>">
									<input name="action" type="hidden" value="upload">
									<?=CSRFTokenField()?>
									<input name="seriesid" type="hidden" value="<?=$seriesid?>">
									<input name="modality" type="hidden" value="<?=htmlspecialchars($modality)?>">
									<div class="fallback">
										<input name="file[]" type="file" multiple />
									</div>
								</form>
								<? } else { ?>
								<span class="tiny" style="color: gray">No upload permission</span>
								<? } ?>
							</td>
							<td><?=htmlspecialchars($uid ?? '')?></td>
							<td><?=htmlspecialchars($studyAge)?></td>
							<td><?=htmlspecialchars($calcStudyAge)?></td>
							<td><a href="studies.php?studyid=<?=(int)$studyid?>"><?=htmlspecialchars($uid . $studynum)?></a></td>
							<td><?=htmlspecialchars(strtoupper($modality))?></td>
							<td><?=$studydate?></td>
							<td><?=htmlspecialchars($seriesnum ?? '')?></td>
							<td><?=$seriesdate?></td>
							<td><?=htmlspecialchars($seriesdesc ?? '')?></td>
							<td>
								<span class="tiny">
								<?
									list($datapath, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
									if (strtolower($modality) == "mr")
										$datapath = "$seriespath/beh";
									echo htmlspecialchars($datapath) . "<br>";
									/* scandir() returns false for a missing dir (e.g. no beh/ yet), which array_diff() can't take on PHP 8 */
									$filelist = is_dir($datapath) ? array_diff(scandir($datapath), array('..', '.')) : array();

									$numfiles = count($filelist);
									$numremain = $numfiles;
									$i = 0;
									foreach ($filelist as $file) {
										echo htmlspecialchars($file) . "<br>";
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
					if ($numhidden > 0) {
						?>
						<tr>
							<td colspan="11" style="color: gray">[<?=$numhidden?>] selected series not shown (no View Data permission on their project)</td>
						</tr>
						<?
					}
				}
				else {
					?>
					<tr>
						<td colspan="11">No series selected</td>
					</tr>
					<?
				}
			?>
			</tbody>
		</table>
		</div>
		<?
	}
	
	/* -------------------------------------------- */
	/* ------- UserCanModifySeries ---------------- */
	/* -------------------------------------------- */
	/* adding files to a series changes raw data, so it requires Edit Data on the series' project.
	   $modality must already be validated (it is used in a table name) */
	function UserCanModifySeries($seriesid, $modality) {
		$tablename = GetSeriesTableName($modality);
		if (($tablename == '') || ((int)$seriesid < 1))
			return false;

		$sqlstring = "select c.project_id from $tablename a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id where a.$modality" . "series_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$seriesid = (int)$seriesid;
		mysqli_stmt_bind_param($stmt, 'i', $seriesid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($seriesid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row)
			return false;

		$projectid = (int)$row['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return (GetPerm($perms, 'modifydata', $projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- UploadFile ------------------------- */
	/* -------------------------------------------- */
	function UploadFile($seriesid, $modality) {
		$seriesid = (int)$seriesid;
		$modality = strtolower(trim((string)$modality));

		$output = "";
		$success = false;

		//header("HTTP/1.0 400 Bad Request");

		if ((GetSeriesTableName($modality) == '') || (!IsNiDBModality($modality))) {
			echo "Invalid modality [" . htmlspecialchars($modality) . "]";
			$success = false;
		}
		elseif (!UserCanModifySeries($seriesid, $modality)) {
			echo "You do not have permission to add files to this series";
			$success = false;
		}
		else {
			list($datapath, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
			
			/* MRI series get uploads as behavioral data; other modalities store files in the series' data dir
			   (the same dirs DisplaySeriesList lists) */
			if ($modality == "mr")
				$outpath = "$seriespath/beh";
			else
				$outpath = $datapath;

			//echo $outpath;
			
			//print_r($_FILES);
			
			/* go through all the files and save them */
			mkdir($outpath, 0777, true);
			chmod($outpath, 0777);
			foreach ($_FILES['file']['name'] as $i => $name) {
				/* client-supplied filename: never allow directory components */
				$name = basename((string)$name);
				if (($name == '') || ($name == '.') || ($name == '..')) {
					echo "ERROR invalid filename\n";
					$success = false;
					continue;
				}
				echo "Working on file [" . htmlspecialchars($name) . "]\n";
				$filesize = 0;
				error_reporting(E_ALL);
				if (move_uploaded_file($_FILES['file']['tmp_name'][$i], "$outpath/$name")) {
					$filesize = filesize("$outpath/$name");
					chmod("$outpath/$name", 0777);
					$success = true;
					echo "SUCCESS: File [" . htmlspecialchars($name) . "] written to [" . htmlspecialchars($outpath) . "]";
				}
				else {
					echo "ERROR moving [" . htmlspecialchars($name) . "] to [" . htmlspecialchars($outpath) . "]";
					$success = false;
				}
			}
			
			$filecount = count(glob("$outpath/*"));
			$filesize = GetDirectorySize($outpath);
			
			/* update the database to reflect the number of size of the files */
			if ($modality == "mr")
				$sqlstring = "update mr_series set numfiles_beh = ?, beh_size = ? where mrseries_id = ?";
			else
				$sqlstring = "update $modality"."_series set series_numfiles = ?, series_size = ? where $modality"."series_id = ?";
			
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'idi', $filecount, $filesize, $seriesid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($filecount, $filesize, $seriesid));
			mysqli_stmt_close($stmt);
		}
		
		if ($success) {
			http_response_code (200);
		}
		else {
			http_response_code (400);
		}
		echo "Hello";
		//echo "Ups error message";
		//set Content-Type to JSON
		header( 'Content-Type: application/json; charset=utf-8' );
		
		//echo error message as JSON
		echo json_encode( $output );
		
		//echo $output;
	}
	
	/* -------------------------------------------- */
	/* ------- GetDirectorySize ------------------- */
	/* -------------------------------------------- */
	function GetDirectorySize($dirname) {
		// open the directory, if the script cannot open the directory then return folderSize = 0
		$dir_handle = opendir($dirname);
		if (!$dir_handle)
			return 0;

		$folderSize = 0;
		
		// traversal for every entry in the directory
		while ($file = readdir($dir_handle)){
			// ignore '.' and '..' directory
			if  ($file  !=  "."  &&  $file  !=  "..")  {
				/* if this is a directory then go recursive! */
				if (is_dir($dirname."/".$file)) {
					$folderSize += GetDirectorySize($dirname.'/'.$file);
				} else {
					$folderSize += filesize($dirname."/".$file);
				}
			}
		}
		// close the directory
		closedir($dir_handle);
		// return $dirname folder size
		return $folderSize ;
	}	
?>
