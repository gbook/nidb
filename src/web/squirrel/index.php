<?
 // ------------------------------------------------------------------------------
 // squirrel index.php
 // Copyright (C) 2004 - 2023
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
	if (!session_start())
		echo "Error starting session. _SESSION variable not created";

	date_default_timezone_set("America/New_York");
	require "functions.php";
	require "config.php";

?>
<html>
	<head>
		<link rel="icon" type="image/png" href="../images/squirrel.png">
		<title>Squirrel package builder</title>
	</head>

	<body>
	<script type="text/javascript" src="../scripts/jquery-3.5.1.min.js"></script>
	<script type="text/javascript" src="../scripts/jquery-ui.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../scripts/jquery-ui.min.css">
	<link rel="stylesheet" type="text/css" href="../style.css">
	<link rel="stylesheet" type="text/css" href="../scripts/semantic/semantic.css">
	<script src="../scripts/semantic/semantic.min.js"></script>

	<noscript>Javascript is required to use NiDB</noscript>
	<div id="cookiemessage" class="ui orange message"></div>
	<script type="text/javascript">
		<!--
		function AreCookiesEnabled() {
			var cookieEnabled = (navigator.cookieEnabled) ? true : false;
			if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) { 
				document.cookie="testcookie";
				cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
			}
			var div = document.getElementById('cookiemessage');
			if (!cookieEnabled) {
				div.innerHTML = 'This site requires cookies to be enabled';
			}
			else {
				div.style.display = 'none';
				div.style.visibility = 'hidden';
			}
		};

		window.onload = AreCookiesEnabled;
		-->
	</script>
	
	<script>
		$(document).ready(function() {
			$('.ui.accordion').accordion();
			$('.ui.dropdown').dropdown();
			$('.menu .item').tab();
			$('.ui.calendar').calendar();
			$('#dob')
				.calendar({
					type: 'date',
					formatter: {
					  date: 'YYYY-MM-DD'
					}
				});
			$('#studydatetime')
				.calendar({
					type: 'datetime',
					formatter: {
					  datetime: 'YYYY-MM-DD HH:mm:ss'
					}
				});
			$('#seriesdatetime')
				.calendar({
					type: 'datetime',
					formatter: {
					  datetime: 'YYYY-MM-DD HH:mm:ss'
					}
				});
			$('.label')
				.popup({
					inline: true
				});				
		});
	</script>
	<?

	/* database connection */
	$linki = mysqli_connect($GLOBALS['mysqlhost'], $GLOBALS['mysqluser'], $GLOBALS['mysqlpass'], $GLOBALS['mysqldb']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$email = GetVariable("email");
	
	/* package variables */
	$packageid = GetVariable("packageid");
	$name = GetVariable("name");
	$desc = GetVariable("desc");
	$subjectdirformat = GetVariable("subjectdirformat");
	$studydirformat = GetVariable("studydirformat");
	$seriesdirformat = GetVariable("seriesdirformat");
	$dataformat = GetVariable("dataformat");
	$license = GetVariable("license");
	$readme = GetVariable("readme");
	$changes = GetVariable("changes");
	$notes = GetVariable("notes");

	/* subject variables */
	$subjectid = GetVariable("subjectid");
	$id = GetVariable("id");
	$altids = GetVariable("altids");
	$guid = GetVariable("guid");
	$dob = GetVariable("dob");
	$sex = GetVariable("sex");
	$gender = GetVariable("gender");
	$ethnicity1 = GetVariable("ethnicity1");
	$ethnicity2 = GetVariable("ethnicity2");

	/* study variables */
	$studyid = GetVariable("studyid");
	$study_number = GetVariable("study_number");
	$study_datetime = GetVariable("study_datetime");
	$study_age = GetVariable("study_age");
	$study_height = GetVariable("study_height");
	$study_weight = GetVariable("study_weight");
	$study_modality = GetVariable("study_modality");
	$study_description = GetVariable("study_description");
	$study_studyuid = GetVariable("study_studyuid");
	$study_visittype = GetVariable("study_visittype");
	$study_daynumber = GetVariable("study_daynumber");
	$study_timepoint = GetVariable("study_timepoint");
	$study_equipment = GetVariable("study_equipment");

	/* study variables */
	$seriesid = GetVariable("seriesid");
	$series_number = GetVariable("series_number");
	$series_datetime = GetVariable("series_datetime");
	$series_uid = GetVariable("series_uid");
	$series_description = GetVariable("series_description");
	$series_protocol = GetVariable("series_protocol");
	$series_experimentid = GetVariable("series_experimentid");

	/* series variables */
	$seriesid = GetVariable("seriesid");

	/* Do the login... */
	$msg = "";
	if ($action == "login") {
		$msg = DoLogin($email);
	}

	/* ... then check if they should see the login screen */
	if ($_SESSION['valid'] != "true") {
		DisplayLogin("Not logged in");
	}
	
	/* determine action */
	if (($action == "") || ($action == "login")) {
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "newpackage") {
		NewPackage();
	}
	elseif ($action == "downloadpackage") {
		DownloadPackage();
	}
	elseif ($action == "setpackageinfo") {
		$msg = SetPackageInfo($packageid, $name, $desc, $subjectdirformat, $studydirformat, $seriesdirformat, $dataformat, $license, $readme, $changes, $notes);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "setsubjectinfo") {
		$msg = SetSubjectInfo($subjectid, $id, $altids, $guid, $dob, $sex, $gender, $ethnicity1, $ethnicity2);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "setstudyinfo") {
		$msg = SetStudyInfo($subjectid, $studyid, $study_number, $study_datetime, $study_age, $study_height, $study_weight, $study_modality,$study_description, $study_studyuid, $study_visittype, $study_daynumber, $study_timepoint, $study_equipment);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "setseriesinfo") {
		$msg = SetSeriesInfo($subjectid, $studyid, $seriesid, $series_number, $series_datetime, $series_uid, $series_description, $series_protocol, $series_experimentid);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "subjectform") {
		DisplayHeader($msg);
		ShowSubjectForm($subjectid);
	}
	elseif ($action == "studyform") {
		DisplayHeader($msg);
		ShowStudyForm($subjectid, $studyid);
	}
	elseif ($action == "seriesform") {
		DisplayHeader($msg);
		ShowSeriesForm($subjectid, $studyid, $seriesid);
	}
	else {
		
	}


	/* -------------------------------------------- */
	/* ------- DisplayHeader ---------------------- */
	/* -------------------------------------------- */
	function DisplayHeader($msg) {
		?>
		<div class="ui container">
			<div class="ui horizontal segments">		
				<div class="ui brown segment">
					<a href="index.php"><img src="../images/squirrel.png" width="30px">
					<span class="ui big brown text"><b>Squirrel package creator</b></span></a>
				</div>
				<div class="ui brown segment">
					<?=$msg?>&nbsp;
				</div>
				<div class="ui right aligned brown segment">
					Logged in as <?=$_SESSION['email']?><br>
					<a href="https://github.com/gbook/squirrel">Squirrel</a> running on <a href="https://github.com/gbook/nidb">NiDB</a> &copy;2023
				</div>
			</div>
		</div>
		<br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SetPackageInfo --------------------- */
	/* -------------------------------------------- */
	function SetPackageInfo($packageid, $name, $desc, $subjectdirformat, $studydirformat, $seriesdirformat, $dataformat, $license, $readme, $changes, $notes) {

		$name = mysqli_real_escape_string($GLOBALS['linki'], trim($name));
		$desc = mysqli_real_escape_string($GLOBALS['linki'], trim($desc));
		$subjectdirformat = mysqli_real_escape_string($GLOBALS['linki'], trim($subjectdirformat));
		$studydirformat = mysqli_real_escape_string($GLOBALS['linki'], trim($studydirformat));
		$seriesdirformat = mysqli_real_escape_string($GLOBALS['linki'], trim($seriesdirformat));
		$dataformat = mysqli_real_escape_string($GLOBALS['linki'], trim($dataformat));
		$license = mysqli_real_escape_string($GLOBALS['linki'], trim($license));
		$readme = mysqli_real_escape_string($GLOBALS['linki'], trim($readme));
		$changes = mysqli_real_escape_string($GLOBALS['linki'], trim($changes));
		$notes = mysqli_real_escape_string($GLOBALS['linki'], trim($notes));
		
		/* get userid, they're only allowed to have one package */
		$email = $_SESSION['email'];
		
		$sqlstring = "select * from users where email = '$email'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		/* get the packageid. if it doesn't exist, create it */
		if ($userid >= 0) {
			$sqlstring = "select * from packages where user_id = $userid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$packageid = $row['package_id'];
				$sqlstring = "update packages set pkg_name = '$name', pkg_desc = '$desc', pkg_subjectdirformat = '$subjectdirformat', pkg_studydirformat = '$studydirformat', pkg_seriesdirformat = '$seriesdirformat', pkg_dataformat = '$dataformat', pkg_license = '$license', pkg_readme = '$readme', pkg_changes = '$changes', pkg_notes = '$notes'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			else {
				$sqlstring = "insert into packages (user_id, pkg_name, pkg_desc, pkg_date, pkg_subjectdirformat, pkg_studydirformat, pkg_seriesdirformat, pkg_dataformat, pkg_license, pkg_readme, pkg_changes, pkg_notes) values ($userid, '$name', '$desc', now(), '$subjectdirformat', '$studydirformat', '$seriesdirformat', '$dataformat', '$license', '$readme', '$changes', '$notes')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
		
	}
	

	/* -------------------------------------------- */
	/* ------- SetSubjectInfo --------------------- */
	/* -------------------------------------------- */
	function SetSubjectInfo($subjectid, $id, $altids, $guid, $dob, $sex, $gender, $ethnicity1, $ethnicity2) {

		$subjectid = mysqli_real_escape_string($GLOBALS['linki'], trim($subjectid));
		$id = mysqli_real_escape_string($GLOBALS['linki'], trim($id));
		$altids = mysqli_real_escape_string($GLOBALS['linki'], trim($altids));
		$guid = mysqli_real_escape_string($GLOBALS['linki'], trim($guid));
		$dob = mysqli_real_escape_string($GLOBALS['linki'], trim($dob));
		$sex = mysqli_real_escape_string($GLOBALS['linki'], trim($sex));
		$gender = mysqli_real_escape_string($GLOBALS['linki'], trim($gender));
		$ethnicity1 = mysqli_real_escape_string($GLOBALS['linki'], trim($ethnicity1));
		$ethnicity2 = mysqli_real_escape_string($GLOBALS['linki'], trim($ethnicity2));
		
		/* get the user_id and package_id */
		list($userid, $packageid) = GetUserAndPackageIDs();
		
		/* get the packageid. if it doesn't exist, create it */
		if (($userid >= 0) && ($packageid >= 0)) {
			if ($subjectid != "") {
				$sqlstring = "select * from subjects where subject_id = '$subjectid'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update subjects set id = '$id', altids = '$altids', guid = '$guid', dob = '$dob', sex = '$sex', gender = '$gender', ethnicity1 = '$ethnicity1', ethnicity2 = '$ethnicity2' where subject_id = $subjectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$msg = "Subject updated";
				}
				else {
					$msg = "Subject ID [$subjectid] not found";
				}
			}
			else {
				$sqlstring = "insert into subjects (package_id, id, altids, guid, dob, sex, gender, ethnicity1, ethnicity2) values ($packageid, '$id', '$altids', '$guid', '$dob', '$sex', '$gender', '$ethnicity1', '$ethnicity2')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$msg = "Subject added";
			}
		}
		
		return $msg;
	}


	/* -------------------------------------------- */
	/* ------- SetStudyInfo ----------------------- */
	/* -------------------------------------------- */
	function SetStudyInfo($subjectid, $studyid, $study_number, $study_datetime, $study_age, $study_height, $study_weight, $study_modality, $study_description, $study_studyuid, $study_visittype, $study_daynumber, $study_timepoint, $study_equipment) {

		$subjectid = mysqli_real_escape_string($GLOBALS['linki'], trim($subjectid));
		$studyid = mysqli_real_escape_string($GLOBALS['linki'], trim($studyid));
		$number = mysqli_real_escape_string($GLOBALS['linki'], trim($study_number));
		$datetime = mysqli_real_escape_string($GLOBALS['linki'], trim($study_datetime));
		$age = mysqli_real_escape_string($GLOBALS['linki'], trim($study_age));
		$height = mysqli_real_escape_string($GLOBALS['linki'], trim($study_height));
		$weight = mysqli_real_escape_string($GLOBALS['linki'], trim($study_weight));
		$modality = mysqli_real_escape_string($GLOBALS['linki'], trim($study_modality));
		$description = mysqli_real_escape_string($GLOBALS['linki'], trim($study_description));
		$studyuid = mysqli_real_escape_string($GLOBALS['linki'], trim($study_studyuid));
		$visittype = mysqli_real_escape_string($GLOBALS['linki'], trim($study_visittype));
		$daynumber = mysqli_real_escape_string($GLOBALS['linki'], trim($study_daynumber));
		$timepoint = mysqli_real_escape_string($GLOBALS['linki'], trim($study_timepoint));
		$equipment = mysqli_real_escape_string($GLOBALS['linki'], trim($study_equipment));
		
		/* get the user_id and package_id */
		list($userid, $packageid) = GetUserAndPackageIDs();
		
		/* get the packageid. if it doesn't exist, create it */
		if (($userid >= 0) && ($packageid >= 0)) {
			if ($studyid != "") {
				$sqlstring = "select * from studies where study_id = '$studyid'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update studies set number = '$number', datetime = '$datetime', age = '$age', height = '$height', weight = '$weight', modality = '$modality', description = '$description', studyuid = '$studyuid', visttype = '$visittype', daynumber = '$daynumber', timepoint = '$timepoint', equipment = '$equipment' where study_id = $studyid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$msg = "Study updated";
				}
				else {
					$msg = "Study ID [$studyid] not found";
				}
			}
			else {
				$sqlstring = "insert into studies (subject_id, number, datetime, age, height, weight, modality, description, studyuid, visittype, daynumber, timepoint, equipment) values ($subjectid, '$number', '$datetime', '$age', '$height', '$weight', '$modality', '$description', '$studyuid', '$visittype', '$daynumber', '$timepoint', '$equipment')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$msg = "Study added";
			}
		}
		
		return $msg;
	}


	/* -------------------------------------------- */
	/* ------- SetSeriesInfo ---------------------- */
	/* -------------------------------------------- */
	function SetSeriesInfo($subjectid, $studyid, $seriesid, $series_number, $series_datetime, $series_uid, $series_description, $series_protocol, $series_experimentid) {

		$subjectid = mysqli_real_escape_string($GLOBALS['linki'], trim($subjectid));
		$studyid = mysqli_real_escape_string($GLOBALS['linki'], trim($studyid));
		$seriesid = mysqli_real_escape_string($GLOBALS['linki'], trim($seriesid));
		$number = mysqli_real_escape_string($GLOBALS['linki'], trim($series_number));
		$datetime = mysqli_real_escape_string($GLOBALS['linki'], trim($series_datetime));
		$seriesuid = mysqli_real_escape_string($GLOBALS['linki'], trim($series_uid));
		$description = mysqli_real_escape_string($GLOBALS['linki'], trim($series_description));
		$protocol = mysqli_real_escape_string($GLOBALS['linki'], trim($series_protocol));
		$experimentid = mysqli_real_escape_string($GLOBALS['linki'], trim($series_experimentid));
		
		/* get the user_id and package_id */
		list($userid, $packageid) = GetUserAndPackageIDs();
		
		/* get the packageid. if it doesn't exist, create it */
		if (($userid >= 0) && ($packageid >= 0)) {
			if ($seriesid != "") {
				$sqlstring = "select * from series where series_id = '$seriesid'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update series set series_num = '$number', series_datetime = '$datetime', seriesuid = '$seriesuid', description = '$description', protocol = '$protocol', experiment_id = '$experimentid' where series_id = $seriesid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$msg = "Series updated";
				}
				else {
					$msg = "Series ID [$seriesid] not found";
				}
			}
			else {
				$sqlstring = "insert into series (study_id, series_num, series_datetime, seriesuid, description, protocol, experiment_id) values ($studyid, '$number', '$datetime', '$seriesuid', '$description', '$protocol', '$experimentid')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$msg = "Series added";
			}
		}
		
		return $msg;
	}


	/* -------------------------------------------- */
	/* ------- DisplayPackage --------------------- */
	/* -------------------------------------------- */
	function DisplayPackage() {
		
		/* get the user_id and package_id */
		list($userid, $packageid) = GetUserAndPackageIDs();

		$name = "Unnamed";
		$numsubjects = 0;
		if ($userid >= 0) {
			$sqlstring = "select * from packages where package_id = $packageid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$name = $row['pkg_name'];

			$sqlstring = "select * from subjects where package_id = $packageid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$numsubjects = mysqli_num_rows($result);
		}
		
		?>
			<div class="ui container">
				<div class="ui top attached huge tabular menu">
					<a class="item" data-tab="package">Package (<?=$name?>)</a>
					<a class="active item" data-tab="subjects">Data (<?=$numsubjects?> subjects)</a>
					<a class="item" data-tab="experiments">Experiments</a>
					<a class="item" data-tab="pipelines">Pipelines</a>
				</div>
				<div class="ui bottom attached tab segment" data-tab="package">
					<? DisplayPackageInfo(); ?>
				</div>
				<div class="ui bottom attached active tab segment" data-tab="subjects">
					<? DisplaySubjects(); ?>
				</div>
				<div class="ui bottom attached tab segment" data-tab="experiments">
					Second
				</div>
				<div class="ui bottom attached tab segment" data-tab="pipelines">
					Third
				</div>
				
				<br><br>
				<div align="center">
					<a href="index.php?action=downloadpackage" class="ui primary button"><i class="large cloud download alternate icon"></i> Download Package</a>
				</div>
				<br><br><br>
			</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayPackageInfo ----------------- */
	/* -------------------------------------------- */
	function DisplayPackageInfo() {

		/* get the user_id and package info */
		list($userid, $packageid) = GetUserAndPackageIDs();
		
		if ($userid >= 0) {
			$sqlstring = "select * from packages where package_id = $packageid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$name = $row['pkg_name'];
			$desc = $row['pkg_desc'];
			$subjectdirformat = $row['pkg_subjectdirformat'];
			$studydirformat = $row['pkg_studydirformat'];
			$seriesdirformat = $row['pkg_seriesdirformat'];
			$dataformat = $row['pkg_dataformat'];
			$license = $row['pkg_license'];
			$readme = $row['pkg_readme'];
			$changes = $row['pkg_changes'];
			$notes = $row['pkg_notes'];
		}
		
		if ($name == "") {
			$name = "(Unnamed)";
		}

		?>
			<form method="post" action="index.php" class="ui form">
				<input type="hidden" name="action" value="setpackageinfo">
						
				<div class="field">
					<label>Name</label>
					<div class="field">
						<input type="text" name="name" value="<?=$name?>">
					</div>
				</div>
				
				<div class="field">
					<label>Description</label>
					<div class="field">
						<textarea name="desc" rows="3" placeholder="Longer description..."><?=$desc?></textarea>
					</div>
				</div>
				
				<div class="four fields">
					<div class="field">
						<label>Subject directory format</label>
						<select class="ui selection dropdown" name="subjectdirformat">
							<option value="orig">Original</option>
							<option value="seq">Sequential</option>
						</select>										
					</div>
					<div class="field">
						<label>Study directory format</label>
						<select class="ui selection dropdown" name="studydirformat">
							<option value="orig">Original</option>
							<option value="seq">Sequential</option>
						</select>										
					</div>
					<div class="field">
						<label>Series directory format</label>
						<select class="ui selection dropdown" name="seriesdirformat">
							<option value="orig">Original</option>
							<option value="seq">Sequential</option>
						</select>										
					</div>
					<div class="field">
						<label>Package data format</label>
						<select class="ui selection dropdown" name="dataformat">
							<option value="orig">Original</option>
							<option value="anon">Anonymized (if DICOM)</option>
							<option value="anonfull">Full anonymization (if DICOM)</option>
							<option value="nifti3d">Nifti3D</option>
							<option value="nifti3dgz">Nifti3D.gz</option>
							<option value="nifti4d">Nifti4D</option>
							<option value="nifti4dgz">Nifti4D.gz</option>
						</select>										
					</div>
				</div>

				<div class="field">
					<label>License</label>
					<div class="field">
						<textarea name="license" rows="3" placeholder="LICENSE (often imported from BIDS)..."><?=$license?></textarea>
					</div>
				</div>

				<div class="field">
					<label>Readme</label>
					<div class="field">
						<textarea name="readme" rows="3" placeholder="README (often imported from BIDS)..."><?=$readme?></textarea>
					</div>
				</div>

				<div class="field">
					<label>Changes</label>
					<div class="field">
						<textarea name="changes" rows="3" placeholder="CHANGES (often imported from BIDS)..."><?=$changes?></textarea>
					</div>
				</div>

				<div class="field">
					<label>Notes</label>
					<div class="field">
						<textarea name="notes" rows="3" placeholder="NOTES (often imported from BIDS)..."><?=$notes?></textarea>
					</div>
				</div>

				<br>
				<div align="right">
					<input type="submit" class="ui primary button" value="Save Package Details">
				</div>

			</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubjects -------------------- */
	/* -------------------------------------------- */
	function DisplaySubjects() {
		
		list($userid, $packageid) = GetUserAndPackageIDs();
		
		?>
		<script>
			$('.button')
				.popup({
					inline: true
				});
		</script>
		
		<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
		<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
		<style>
			.dropzone {
				min-height: 40px;
				border: 3px dashed #888;
				border-radius: 6px;
				padding: 10px 10px;
				margin: 5px;
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
				margin: 1px !important;max-height: 70px;
			}
			.dz-file-preview {
				margin: 1px !important; max-height: 70px;
			}
			.dz-processing {
				margin: 1px !important; max-height: 70px;
			}
			.dz-success {
				margin: 1px !important; max-height: 70px;
			}
			.dz-complete {
				margin: 1px !important; max-height: 70px;
			}
			.dz-details {
				padding: 6px !important; margin: 1px !important; max-height: 70px;
			}
			.dz-image {
				padding: 6px !important; margin: 1px !important; max-height: 70px;
			}
		</style>
		
		<div class="ui fluid styled tree accordion" style="background-color: #ddd">
		<?		
		$sqlstring = "select * from subjects where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$id = $row['id'];
			$altids = $row['altids'];
			$guid = $row['guid'];
			$dob = $row['dob'];
			$sex = $row['sex'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			?>
			<div class="title" style="font-size: larger">
				<i class="dropdown icon"></i><tt><?=$id?></tt>
			</div>
			<div class="content">
				<a href="index.php?action=subjectform&subjectid=<?=$subjectid?>" class="ui primary compact button"><i class="edit icon"></i>Edit</a> <div class="ui large blue label" data-html="<?=$id?><br><?=$altids?>"><i class="info circle icon"></i> Details</div> &nbsp; &nbsp; <a href="index.php?action=studyform&subjectid=<?=$subjectid?>" class="ui primary compact button"><i class="plus icon"></i> Add study</a>
			<?
				$sqlstringA = "select * from studies where subject_id = $subjectid";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$studyid = $rowA['study_id'];
					$studynumber = $rowA['number'];
					$studydatetime = $rowA['datetime'];
					$age = $rowA['age'];
					$height = $rowA['height'];
					$weight = $rowA['weight'];
					$modality = $rowA['modality'];
					$studydescription = $rowA['description'];
					$studyuid = $rowA['studyuid'];
					$visittype = $rowA['visittype'];
					$daynumber = $rowA['daynumber'];
					$timepoint = $rowA['timepoint'];
					$equipment = $rowA['equipment'];
				
					?>
					<div class="styled accordion" style="margin-top: 15px; background-color: #eee; important!">
						<div class="title">
							<i class="dropdown icon"></i><tt><?=$studynumber?></tt>
						</div>
						<div class="content">
							<a href="index.php?action=studyform&studyid=<?=$studyid?>" class="ui primary compact button"><i class="edit icon"></i> Edit study</a> <div class="ui large blue label" data-html="<?=$id?><br><?=$altids?>"><i class="info circle icon"></i> Details</div> &nbsp; &nbsp;  <a href="index.php?action=seriesform&subjectid=<?=$subjectid?>&studyid=<?=$studyid?>" class="ui primary compact button"><i class="plus icon"></i> Add series</a>
							<?
								$sqlstringC = "select * from series where study_id = $studyid";
								$resultC = MySQLiQuery($sqlstringC, __FILE__, __LINE__);
								while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
									$seriesid = $rowC['series_id'];
									$seriesnumber = $rowC['series_num'];
									$seriesdatetime = $rowC['series_datetime'];
									$seriesuid = $rowC['seriesuid'];
									$seriesdescription = $rowC['description'];
									$protocol = $rowC['protocol'];
									$experiment_id = $rowC['experiment_id'];
									$size = $rowC['size'];
									$numfiles = $rowC['numfiles'];
									$behsize = $rowC['behsize'];
									$behnumfiles = $rowC['behnumfiles'];
								
									?>
									<div class="styled accordion" style="margin-top: 15px; important!">
										<div class="title">
											<i class="dropdown icon"></i><tt><?=$seriesnumber?></tt>
										</div>
										<div class="content">
											<a href="index.php?action=seriesform&seriesid=<?=$seriesid?>" class="ui primary compact button"><i class="edit icon"></i> Edit series</a> <div class="ui large blue label" data-html
											="<?=$id?><br><?=$altids?>"><i class="info circle icon"></i> Details</div>

											<form action="upload.php" class="dropzone" id="uploadseries<?=$seriesid?>">
												<input type="hidden" name="action" value="uploadseries">
												<input type="hidden" name="seriesid" value="<?=$seriesid?>">
											</form>
											<script>
												Dropzone.options.uploadseries<?=$seriesid?> = {
													createImageThumbnails: false,
													thumbnailHeight: 60,
													maxFilesize: 1000000,
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
											
										</div>
									</div>
									<?
								}
							?>
						</div>
					</div>
					<?
				}
			?>
			</div>
			<?
		}
		
		?>
		</div>
		<a href="index.php?action=subjectform" class="ui small compact button" style="margin-top: 15px"><i class="plus icon"></i> Add subject</a>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ShowSubjectForm -------------------- */
	/* -------------------------------------------- */
	function ShowSubjectForm($subjectid) {

		list($userid, $packageid) = GetUserAndPackageIDs();
		
		if ($userid >= 0) {
			if ($packageid == "") {
				/* create a package if it doesn't yet exist */
				SetPackageInfo("", "Default Package", "", "orig", "orig", "orig", "orig", "", "", "", "");
			}

			list($userid, $packageid) = GetUserAndPackageIDs();
			
			$sqlstring = "select * from subjects where subject_id = '$subjectid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$subjectid = $row['subject_id'];
			$id = $row['id'];
			$altids = $row['altids'];
			$guid = $row['guid'];
			$dob = $row['dob'];
			$sex = $row['sex'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
		}
		
		?>
		<div class="ui text container">
			<form method="post" action="index.php" class="ui form">
				<input type="hidden" name="action" value="setsubjectinfo">
				<input type="hidden" name="subjectid" value="<?=$subjectid?>">

				<h4 class="ui dividing header">IDs</h4>
				<div class="ui three fields">
					<div class="field">
						<label>Primary ID</label>
						<div class="field">
							<input type="text" name="id" value="<?=$id?>" required>
						</div>
					</div>
					
					<div class="field">
						<label>Alternate IDs</label>
						<div class="field">
							<input type="text" name="altids" value="<?=$altids?>">
						</div>
					</div>

					<div class="field">
						<label>GUID</label>
						<div class="field">
							<input type="text" name="guid" value="<?=$guid?>">
						</div>
					</div>
				</div>

				<div class="ui three fields">
					<div class="field">
						<label>DOB</label>
						<div class="field">
							<div class="ui calendar" id="dob">
								<div class="ui fluid input left icon">
									<i class="calendar icon"></i>
									<input type="text" name="dob" value="<?=$dob?>" placeholder="Date/Time">
								</div>
							</div>
						</div>
					</div>
					
					<div class="field">
						<label>Sex</label>
						<div class="field">
							<div class="ui selection dropdown">
								<input type="hidden" name="sex" value="<?=$sex?>">
									<i class="dropdown icon"></i>
								<div class="default text">Select Country</div>
								<div class="menu">
									<div class="item" data-value="F">F</div>
									<div class="item" data-value="M">M</div>
									<div class="item" data-value="O">O</div>
									<div class="item" data-value="U">U</div>
								</div>
							</div>
							
						</div>
					</div>

					<div class="field">
						<label>Gender</label>
						<div class="field">
							<div class="ui selection dropdown">
								<input type="hidden" name="gender" value="<?=$gender?>">
									<i class="dropdown icon"></i>
								<div class="default text">Select Country</div>
								<div class="menu">
									<div class="item" data-value="F">F</div>
									<div class="item" data-value="M">M</div>
									<div class="item" data-value="O">O</div>
									<div class="item" data-value="U">U</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="ui two fields">
					<div class="field">
						<label>Ethnicity1</label>
						<div class="field">
							<input type="text" name="ethnicity1" value="<?=$ethnicity1?>">
						</div>
					</div>
					
					<div class="field">
						<label>Ethnicity2</label>
						<div class="field">
							<input type="text" name="ethnicity1" value="<?=$ethnicity2?>">
						</div>
					</div>
				</div>

				<br>
				<div align="right">
					<a href="index.php" class="ui button">Cancel</a>
					<input type="submit" class="ui primary button" value="Save Subject Details">
				</div>

			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ShowStudyForm ---------------------- */
	/* -------------------------------------------- */
	function ShowStudyForm($subjectid, $studyid) {

		list($userid, $packageid) = GetUserAndPackageIDs();
		
		if ($userid >= 0) {
			$sqlstring = "select * from studies where study_id = '$studyid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			//$studyid = $row['study_id'];
			//$subjectid = $row['subject_id'];
			$number = $row['number'];
			$datetime = $row['datetime'];
			$age = $row['age'];
			$height = $row['height'];
			$weight = $row['weight'];
			$modality = $row['modality'];
			$description = $row['description'];
			$studyuid = $row['studyuid'];
			$visittype = $row['visittype'];
			$daynumber = $row['daynumber'];
			$timepoint = $row['timepoint'];
			$equipment = $row['equipment'];
		}
		
		?>
		<div class="ui text container">
			<form method="post" action="index.php" class="ui form">
				<input type="hidden" name="action" value="setstudyinfo">
				<input type="hidden" name="subjectid" value="<?=$subjectid?>">
				<input type="hidden" name="studyid" value="<?=$studyid?>">

				<div class="ui two fields">
					<div class="field">
						<label>Number</label>
						<div class="field">
							<input type="number" name="study_number" value="<?=$number?>">
						</div>
					</div>
					
					<div class="field">
						<label>Date/time</label>
						<div class="ui calendar" id="studydatetime">
							<div class="ui fluid input left icon">
								<i class="calendar icon"></i>
								<input type="text" name="study_datetime" value="<?=$dob?>" placeholder="Date/Time">
							</div>
						</div>
					</div>
				</div>
				
				<h4 class="ui dividing header">Subject info</h4>
				<div class="ui three fields">
					<div class="field">
						<label>Age</label>
						<div class="field">
							<div class="ui right labeled input">
								<input type="number" step="0.1" name="study_age" value="<?=$age?>">
								<div class="ui label">years</div>
							</div>
						</div>
					</div>
					
					<div class="field">
						<label>Height</label>
						<div class="field">
							<div class="ui right labeled input">
								<input type="number" step="0.1" name="study_height" value="<?=$height?>">
								<div class="ui label">cm</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>Weight</label>
						<div class="field">
							<div class="ui right labeled input">
								<input type="number" step="0.1" name="study_weight" value="<?=$weight?>">
								<div class="ui label">kg</div>
							</div>
						</div>
					</div>
				</div>

				<div class="field">
					<label>Modality</label>
					<div class="field">
						<input type="text" name="study_modality" value="<?=$modality?>">
					</div>
				</div>

				<div class="field">
					<label>Description</label>
					<div class="field">
						<input type="text" name="study_description" value="<?=$description?>">
					</div>
				</div>

				<div class="field">
					<label>Study UID</label>
					<div class="field">
						<input type="text" name="study_studyuid" value="<?=$studyuid?>">
					</div>
				</div>

				<div class="field">
					<label>Equipment</label>
					<div class="field">
						<input type="text" name="study_equipment" value="<?=$equipment?>">
					</div>
				</div>

				<h4 class="ui dividing header">Clinical trial info</h4>
				<div class="ui three fields">
					<div class="field">
						<label>Visit type</label>
						<div class="field">
							<input type="text" name="study_visittype" value="<?=$visittype?>">
						</div>
					</div>
					
					<div class="field">
						<label>Day number</label>
						<div class="field">
							<input type="number" name="study_daynumber" value="<?=$daynumber?>">
						</div>
					</div>

					<div class="field">
						<label>Time point</label>
						<div class="field">
							<input type="number" name="study_timepoint" value="<?=$timepoint?>">
						</div>
					</div>
				</div>

				<br>
				<div align="right">
					<a href="index.php" class="ui button">Cancel</a>
					<input type="submit" class="ui primary button" value="Save Study Details">
				</div>

			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ShowSeriesForm --------------------- */
	/* -------------------------------------------- */
	function ShowSeriesForm($subjectid, $studyid, $seriesid) {

		list($userid, $packageid) = GetUserAndPackageIDs();
		
		if ($userid >= 0) {
			$sqlstring = "select * from series where study_id = '$seriesid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$number = $row['series_num'];
			$datetime = $row['series_datetime'];
			$seriesuid = $row['seriesuid'];
			$description = $row['description'];
			$protocol = $row['protocol'];
			$experiment_id = $row['experiment_id'];
			$size = $row['size'];
			$numfiles = $row['numfiles'];
			$behsize = $row['behsize'];
			$behnumfiles = $row['behnumfiles'];
		}
		
		?>
		<div class="ui text container">
			<form method="post" action="index.php" class="ui form">
				<input type="hidden" name="action" value="setseriesinfo">
				<input type="hidden" name="subjectid" value="<?=$subjectid?>">
				<input type="hidden" name="studyid" value="<?=$studyid?>">
				<input type="hidden" name="seriesid" value="<?=$seriesid?>">

				<div class="ui two fields">
					<div class="field">
						<label>Number</label>
						<div class="field">
							<input type="number" name="series_number" value="<?=$number?>">
						</div>
					</div>
					
					<div class="field">
						<label>Date/time</label>
						<div class="ui calendar" id="seriesdatetime">
							<div class="ui fluid input left icon">
								<i class="calendar icon"></i>
								<input type="text" name="series_datetime" value="<?=$dob?>" placeholder="Date/Time">
							</div>
						</div>
					</div>
				</div>
				
				<div class="field">
					<label>Series UID</label> 
					<div class="field">
						<input type="text" name="series_uid" value="<?=$seriesuid?>">
					</div>
				</div>

				<div class="field">
					<label>Description</label> 
					<div class="field">
						<input type="text" name="series_description" value="<?=$description?>">
					</div>
				</div>

				<div class="field">
					<label>Protocol</label> 
					<div class="field">
						<input type="text" name="series_protocol" value="<?=$protocol?>">
					</div>
				</div>

				<div class="field">
					<label>Experiment</label> 
					<div class="field">
						<select class="ui selection dropdown" name="series_experimentid">
							<option value="1">Experiment 1</option>
						</select>										
						
					</div>
				</div>
					
				<br>
				<div align="right">
					<a href="index.php" class="ui button">Cancel</a>
					<input type="submit" class="ui primary button" value="Save Series Details">
				</div>

			</form>
		</div>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayExperiments ----------------- */
	/* -------------------------------------------- */
	function DisplayExperiments() {
	}


	/* -------------------------------------------- */
	/* ------- DisplayPipelines ------------------- */
	/* -------------------------------------------- */
	function DisplayPipelines() {
	}


	/* -------------------------------------------- */
	/* ------- DownloadPackage -------------------- */
	/* -------------------------------------------- */
	function DownloadPackage() {
		$json = GetJSON();
		
		PrintVariable($json);
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetJSON ---------------------------- */
	/* -------------------------------------------- */
	function GetJSON() {
		list($userid, $packageid) = GetUserAndPackageIDs();

		$json['package'] = PackageToArray($packageid);

		$sqlstring = "select * from subjects where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$subjects[] = SubjectToArray($subjectid);
		}
		$json['data']['subjects'] = $subjects;
		$json['data']['numsubjects'] = count($subjects);
		
		return json_encode($json, JSON_PRETTY_PRINT);
	}


	/* -------------------------------------------- */
	/* ------- PackageToArray --------------------- */
	/* -------------------------------------------- */
	function PackageToArray($packageid) {

		$sqlstring = "select * from packages where package_id = $packageid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$package['name'] = $row['pkg_name'];
		$package['desc'] = $row['pkg_desc'];
		$package['subjectdirformat'] = $row['pkg_subjectdirformat'];
		$package['studydirformat'] = $row['pkg_studydirformat'];
		$package['seriesdirformat'] = $row['pkg_seriesdirformat'];
		$package['dataformat'] = $row['pkg_dataformat'];
		$package['license'] = $row['pkg_license'];
		$package['readme'] = $row['pkg_readme'];
		$package['changes'] = $row['pkg_changes'];
		$package['notes'] = $row['pkg_notes'];
		
		return $package;
	}


	/* -------------------------------------------- */
	/* ------- SubjectToArray --------------------- */
	/* -------------------------------------------- */
	function SubjectToArray($subjectid) {
		
		$sqlstring = "select * from subjects where subject_id = '$subjectid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$subject['id'] = $row['id'];
		$subject['altids'] = $row['altids'];
		$subject['guid'] = $row['guid'];
		$subject['dob'] = $row['dob'];
		$subject['sex'] = $row['sex'];
		$subject['gender'] = $row['gender'];
		$subject['ethnicity1'] = $row['ethnicity1'];
		$subject['ethnicity2'] = $row['ethnicity2'];

		$sqlstring = "select * from studies where subject_id = $subjectid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$studies[] = StudyToArray($studyid);
		}
		$subject['studies'] = $studies;
		$subject['numstudies'] = count($studies);
		
		return $subject;
	}


	/* -------------------------------------------- */
	/* ------- StudyToArray ----------------------- */
	/* -------------------------------------------- */
	function StudyToArray($studyid) {
		$sqlstring = "select * from studies where study_id = '$studyid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

		$study['number'] = $row['number'];
		$study['datetime'] = $row['datetime'];
		$study['age'] = $row['age'];
		$study['height'] = $row['height'];
		$study['weight'] = $row['weight'];
		$study['modality'] = $row['modality'];
		$study['description'] = $row['description'];
		$study['studyuid'] = $row['studyuid'];
		$study['visittype'] = $row['visittype'];
		$study['daynumber'] = $row['daynumber'];
		$study['timepoint'] = $row['timepoint'];
		$study['equipment'] = $row['equipment'];

		$sqlstring = "select * from series where study_id = $studyid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriesid = $row['series_id'];
			$series[] = SeriesToArray($seriesid);
		}
		$study['series'] = $series;
		$study['numseries'] = count($series);
		
		return $study;
	}


	/* -------------------------------------------- */
	/* ------- SeriesToArray ---------------------- */
	/* -------------------------------------------- */
	function SeriesToArray($seriesid) {
		$sqlstring = "select * from series where study_id = '$seriesid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$series['number'] = $row['series_num'];
		$series['datetime'] = $row['series_datetime'];
		$series['seriesuid'] = $row['seriesuid'];
		$series['description'] = $row['description'];
		$series['protocol'] = $row['protocol'];
		$series['experiment'] = $row['experiment_id'];
		$series['size'] = $row['size'];
		$series['numfiles'] = $row['numfiles'];
		$series['behsize'] = $row['behsize'];
		$series['behnumfiles'] = $row['behnumfiles'];
		
		return $series;
	}


	/* -------------------------------------------- */
	/* ------- GetUserAndPackageIDs --------------- */
	/* -------------------------------------------- */
	function GetUserAndPackageIDs() {

		$userid = -1;
		$packageid = -1;

		/* get the user_id and package info */
		$email = $_SESSION['email'];
		
		$sqlstring = "select user_id from users where email = '$email'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$userid = $row['user_id'];
		}
		
		if ($userid >= 0) {
			$sqlstring = "select package_id from packages where user_id = $userid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$packageid = $row['package_id'];
		}
		
		return array($userid, $packageid);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayLogin ----------------------- */
	/* -------------------------------------------- */
	function DisplayLogin($msg) {
		?>
		<style>
			.center-screen {
				display: flex;
				justify-content: center;
				align-items: center;
				text-align: center;
				min-height: 100vh;
			}
		</style>
		
		<div class="center-screen">
			<div class="ui raised segment">
				<?=$msg?>
				<br>
				<h1 class="ui left aligned header">
					<em data-emoji=":chipmunk:"></em>
					<div class="content">
						Squirrel package builder
						<div class="sub header">Enter your email to login</div>
					</div>
				</h1>
				<br>
				<br>
				<form method="post" action="index.php" class="ui form">
					<input type="hidden" name="action" value="login">
					<div class="ui left icon input">
						<input type="text" name="email" placeholder="Email...">
						<i class="envelope icon"></i>
					</div>
					<input class="ui button" type="submit" value="Login">
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DoLogin ---------------------------- */
	/* -------------------------------------------- */
	function DoLogin($email) {

		$email = mysqli_real_escape_string($GLOBALS['linki'], trim($email));
		$ip = $_SERVER['REMOTE_ADDR'];
		
		/* check if they already have an entry */
		$sqlstring = "select * from users where email = '$email'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		
		if (mysqli_num_rows($result) > 0) {
			$sqlstring = "update users set ip_address = '$ip', last_login = now() where email = '$email'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$msg = "Welcome back $email!";
		}
		else {
			$sqlstring = "insert into users (ip_address, email, first_login) values ('$ip', '$email', now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$msg = "Welcome $email!";
		}
		
		$_SESSION['email'] = $email;
		$_SESSION['valid'] = "true";
		return $msg;
	}

	/* -------------------------------------------- */
	/* ------- DoLogout --------------------------- */
	/* -------------------------------------------- */
	function DoLogout() {
		session_destroy();
		
		DisplayLogin("You have been logged out");
	}
?>
	</body>
</html>
<? ob_end_flush(); ?>
