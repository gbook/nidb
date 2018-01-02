<?
 // ------------------------------------------------------------------------------
 // NiDB calendar_users.php
 // Copyright (C) 2004 - 2018
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
	ob_start(); // for any page redirects
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Calendar</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["email"] == "") { $email = $_GET["email"]; } else { $email = $_POST["email"]; }
	if ($_POST["calendar_ids"] == "") { $calendar_ids = $_GET["calendar_ids"]; } else { $calendar_ids = $_POST["calendar_ids"]; }
	if ($_POST["project_ids"] == "") { $project_ids = $_GET["project_ids"]; } else { $project_ids = $_POST["project_ids"]; }
	
	if ($action == "saveoptions") {
		SaveOptions($username, $email, $calendar_ids, $project_ids);
		DisplayOptions($username);
	}
	elseif (($action == "") || ($action == "menu")) {
		DisplayOptions($username);
	}


	/* ----------------------------------------------- */
	/* --------- SaveOptions ------------------------- */
	/* ----------------------------------------------- */
	function SaveOptions($username, $email, $calendar_ids, $project_ids) {

		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		
		/* get the range of years that studies have occured */
		$sqlstring = "select * from users where username = '$username'";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			/* update */
			$sqlstring = "update users set user_email = '$email' where username = '$username'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			/* insert */
			$sqlstring = "insert into users (username, user_email) values ('$username', '$email')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		$sqlstring = "select * from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$user_id = $row['user_id'];

		$sqlstring = "delete from calendar_notifications where not_userid = $user_id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		foreach ($calendar_ids as $cal_id) {
			$sqlstring = "insert into calendar_notifications (not_userid, not_calendarid) values ($user_id, $cal_id)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}

		$sqlstring = "delete from project_notifications where not_userid = $user_id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		foreach ($project_ids as $prj_id) {
			$sqlstring = "insert into project_notifications (not_userid, not_projectid) values ($user_id, $prj_id)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayOptions ---------------------- */
	/* ----------------------------------------------- */
	function DisplayOptions($username) {

		/* get the range of years that studies have occured */
		$sqlstring = "select * from users where username = '$username'";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$email = $row['user_email'];
			$user_id = $row['user_id'];
		}
		else {
			$user_id = 0;
		}
		if ($row['sendmail_autocomplete'] == "1") { $autocompletecheck = "checked"; }

	?>
	<form name="userform" action="calendar_users.php" method="post">
	<input type="hidden" name="action" value="saveoptions">
	<table class="editor">
		<tr>
			<td colspan="2" align="center" style="font-size: 12pt; border-bottom: 1px solid gray">User options for <b><?=$username?></b></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td class="label">Email</td>
			<td class="value"><input type="text" name="email" value="<?=$email?>"></td>
		</tr>
		<tr>
			<td class="label">&nbsp;</td>
			<td class="value">Send email when appointments on these calendars are cancelled<br>
			<!--<br><span class="sublabel">This will send an email whenever your automatic processes are complete.</span>-->
			<?
				$sqlstring = "select * from calendar_notifications where not_userid = $user_id";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$cal_ids[] = $row['not_calendarid'];
				}
				//print_r($cal_ids);
			
				$sqlstring = "select * from calendars where calendar_createdate < now() and calendar_deletedate > now()";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$calendar_id = $row['calendar_id'];
					$calendar_name = $row['calendar_name'];
					$calendar_desc = $row['calendar_description'];
					$calendar_loc = $row['calendar_location'];
					$checked = "";
					if (isset($cal_ids)) {
						foreach ($cal_ids as $cal_id) {
							if ($cal_id == $calendar_id) {
								$checked = "checked";
								break;
							}
						}
					}
					?>
					<small><input type="checkbox" name="calendar_ids[]" value="<?=$calendar_id?>" <?=$checked?>> <b><?=$calendar_name?></b> - <?=$calendar_desc?> (<?=$calendar_loc?>)</small><br>
					<?
				}
			?>
			</td>
		</tr>
		<tr>
			<td class="label">&nbsp;</td>
			<td class="value"><br>Send email when appointments from these projects are cancelled<br>
			<!--<br><span class="sublabel">This will send an email whenever your automatic processes are complete.</span>-->
			<?
				$sqlstring = "select * from project_notifications where not_userid = $user_id";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$prj_ids[] = $row['not_projectid'];
				}
				//print_r($cal_ids);
			
				$sqlstring = "select * from calendar_projects where project_startdate < now() and project_enddate > now()";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$project_id = $row['project_id'];
					$project_name = $row['project_name'];
					$project_desc = $row['project_description'];
					$project_admin = $row['project_admin'];
					$checked = "";
					if (isset($prj_ids)) {
						foreach ($prj_ids as $prj_id) {
							if ($prj_id == $project_id) {
								$checked = "checked";
								break;
							}
						}
					}
					?>
					<small><input type="checkbox" name="project_ids[]" value="<?=$project_id?>" <?=$checked?>> <b><?=$project_name?></b> - <?=$project_desc?> (<?=$project_admin?>)</small><br>
					<?
				}
			?>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center"><br><input type="submit" value="Save"></td>
		</tr>
	</table>
	</form>
	<?
	}
?>

<? include("footer.php") ?>
