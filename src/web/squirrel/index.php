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

	/* Do the login... */
	$msg = "";
	if ($action == "login") {
		$msg = DoLogin($email);
	}

	/* ... then check if they should see the login screen */
	if ($_SESSION['valid'] != "true") {
		DisplayLogin("Session variable not valid");
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
	/* ------- DisplayPackage --------------------- */
	/* -------------------------------------------- */
	function DisplayPackage() {
		
		?>
			<div class="ui container">
				<div class="ui top attached tabular menu">
					<a class="item" data-tab="package">Package</a>
					<a class="active item" data-tab="subjects">Subjects</a>
					<a class="item" data-tab="experiments">Experiments</a>
					<a class="item" data-tab="pipelines">Pipelines</a>
				</div>
				<div class="ui bottom attached tab segment" data-tab="package">
					<? DisplayPackageInfo(); ?>
				</div>
				<div class="ui bottom attached active tab segment" data-tab="subjects">
					First
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
		$email = $_SESSION['email'];
		
		$sqlstring = "select * from users where email = '$email'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		if ($userid >= 0) {
			$sqlstring = "select * from packages where user_id = $userid";
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
			<?=$msg?>
			<div class="ui raised segment">
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
			$msg = "Email address $email already registerd. Welcome back!";
		}
		else {
			$sqlstring = "insert into users (ip_address, email, first_login) values ('$ip', '$email', now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$msg = "New email address registerd. Welcome!";
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
