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
		});
	</script>
	<?

	/* database connection */
	$linki = mysqli_connect('localhost', 'nidb', 'password', 'squirrel') or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

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
		$msg = SetStudyInfo($subjectid, $id, $altids, $guid, $dob, $sex, $gender, $ethnicity1, $ethnicity2);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "setseriesinfo") {
		$msg = SetSeriesInfo($subjectid, $id, $altids, $guid, $dob, $sex, $gender, $ethnicity1, $ethnicity2);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "subjectform") {
		DisplayHeader($msg);
		ShowSubjectForm($subjectid);
	}
	elseif ($action == "studyform") {
		ShowStudyForm($studyid);
		DisplayHeader($msg);
		DisplayPackage();
	}
	elseif ($action == "subjectform") {
		ShowSeriesForm($seriesid);
		DisplayHeader($msg);
		DisplayPackage();
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
					<img src="../images/squirrel.png" width="30px">
					<span class="ui big brown text"><b>Squirrel package creator</b></span>
				</div>
				<div class="ui brown segment">
					<?=$msg?>&nbsp;
				</div>
				<div class="ui right aligned brown segment">
					Running on NiDB &copy;2023
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
					$sqlstring = "update subjects set id = '$id', altids = '$altids', guid = '$guid', dob = '$dob', sex = '$sex', gender = '$gender', ethnicity1 = '$ethnicity1', ethnicity2 = '$ethnicity2'";
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
				<div class="ui top attached tabular menu">
					<a class="item" data-tab="package">Package (<?=$name?>)</a>
					<a class="active item" data-tab="subjects">Subjects (<?=$numsubjects?>)</a>
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
		<div class="ui styled tree accordion">
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
			<div class="title">
				<i class="dropdown icon"></i>Subject <?=$id?>
			</div>
			<div class="content">
			<?
				$sqlstringA = "select * from studies where subject_id = $subjectid";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$studyid = $rowA['study_id'];
				
					?>
					<div class="styled accordion">
						<div class="title">
							<i class="dropdown icon"></i>Study <?=$studynum?>
						</div>
						<div class="content">
							<?
								$sqlstringC = "select * from series where study_id = $studyid";
								$resultC = MySQLiQuery($sqlstringC, __FILE__, __LINE__);
								while ($rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC)) {
									$seriesid = $rowC['series_id'];
								
									?>
									<div class="styled accordion">
										<div class="title">
											<i class="dropdown icon"></i>Series <?=$seriesnum?>
										</div>
										<div class="content">
											Series details
										</div>
									</div>
									<?
								}
							?>
								<a href="index.php?action=seriesform" class="ui small compact button" style="padding: 5px"><i class="plus icon"></i> Add series</a>
						</div>
					</div>
					<?
				}
			?>
				<a href="index.php?action=studyform" class="ui small compact button" style="padding: 5px"><i class="plus icon"></i> Add study</a>
			</div>
			<?
		}
		
		?>
			<a href="index.php?action=subjectform" class="ui small compact button" style="margin: 5px"><i class="plus icon"></i> Add subject</a>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ShowSubjectForm -------------------- */
	/* -------------------------------------------- */
	function ShowSubjectForm($subjectid) {

		list($userid, $packageid) = GetUserAndPackageIDs();
		
		if ($userid >= 0) {
			$sqlstring = "select * from packages where user_id = $userid";
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

				<h4 class="ui dividing header">IDs</h4>
				<div class="ui three fields">
					<div class="field">
						<label>Primary ID</label>
						<div class="field">
							<input type="text" name="id" value="<?=$id?>">
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
