<?
 // ------------------------------------------------------------------------------
 // NiDB phpincludes.php
 // Copyright (C) 2004 - 2021
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");
	
	/* this file includes the database connection, cookies, and loads the configuration file */
	
	/* global variables */
	$username = "";
	$userid = "";
	$instanceid = "";
	$instancename = "";

	/* load the configuration info [[these two lines should be the only config variables specific to the website]] */
 	$cfg = LoadConfig();
	date_default_timezone_set("America/New_York");

	/* check if this server is supposed to be up or not */
	if ($cfg['offline'] == 1) {
		?>
		<table style="width: 100%; height: 100%;">
			<tr>
				<td style="height: 40%"></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td style="width: 30%"></td>
				<td style="text-align: center; vertical: align: middle; border: 4px solid orange; padding: 25px; font-family: arial, helvetica, sans serif; font-size: 14pt">
					<img align="right" src="images/squirrel.png" height="30%">NiDB is temporarily offline due to maintenance<br><br>Please contact the administrator with any questions.
				</td>
				<td style="width: 30%"></td>
			</tr>
			<tr>
				<td style="height: 40%"></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<?
		exit(0);
	}
	
	if (stristr($_SERVER['HTTP_HOST'],":8080") != false) { $isdevserver = true; }
	else { $isdevserver = false; }

 	/* this is the first include file loaded by all pages, so... we'll put the page load start time in here */
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$pagestart = $time;
	
	/* database connection */
	if ($isdevserver) {
		$linki = mysqli_connect($GLOBALS['cfg']['mysqldevhost'], $GLOBALS['cfg']['mysqldevuser'], $GLOBALS['cfg']['mysqldevpassword'], $GLOBALS['cfg']['mysqldevdatabase']);
		//or die (SendGmail($GLOBALS['cfg']['adminemail'], __FILE__ . " unable to connect to database","PHP script could not connect to database", 0));
		
		$sitename = $cfg['sitenamedev'];
	}
	else {
		$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']);
		//or die (SendGmail($GLOBALS['cfg']['adminemail'], __FILE__ . " unable to connect to database","PHP script could not connect to database", 0));
		
		$sitename = $cfg['sitename'];
	}

	/* check if DB connection was successful */
	if (mysqli_connect_errno()) {
		echo "Unable to connect to database, with error [" . mysqli_connect_error() . "]";
		exit();
	}

	/* disable the login checking, if its the signup page or if authentication is done in the page (such as api.php) */
	if (!$nologin) {
		/* cookie info */
		$username = $_SESSION['username'];
		if (($_SESSION['validlogin'] != "true") || ($_SESSION['userid'] == '') ) {
			header("Location: login.php");
		}
		if (trim($username) == "") {
			Error("username is blank. Contact NiDB administrator");
			exit(0);
		}
	}
	else {
		/* no login checking */
	}
	
	$instanceid = $_SESSION['instanceid'];
	
	/* get info if they are an admin (wouldn't want to store this in a cookie... if they're logged in for 3 months, they may no longer be an admin during that time */
	$sqlstring = "select user_isadmin, user_issiteadmin, login_type, user_enablebeta, user_id from users where username = '$username'";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$userid = $row['user_id'];
	$isadmin = $row['user_isadmin'];
	$issiteadmin = $row['user_issiteadmin'];
	$enablebeta = $row['user_enablebeta'];
	$_SESSION['enablebeta'] = $enablebeta;
	if (strtolower($row['login_type']) == "guest") {
		$isguest = 1;
	}
	else {
		$isguest = 0;
	}
	
	/* each user can only be associated with 1 instance, so display that instance name at the top of the page */
	$sqlstring = "select instance_name from instance where instance_id in (select instance_id from users where username = '$username')";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
	$instancename = $row['instance_name'];

?>
