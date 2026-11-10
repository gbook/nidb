<?
 // ------------------------------------------------------------------------------
 // NiDB phpincludes.php
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
	
	/* CSRF token. One token per session: per-request tokens break the back button and multiple
	   tabs, and buy nothing against this threat. Pages that change state should emit it with
	   CSRFTokenField() and verify it with VerifyCSRFToken(). Guarded on session_status() because
	   a few endpoints (api.php, api2.php, bsnip.php, v.php) include this file without
	   ever starting a session. Minting the token does not by itself enforce anything. */
	if (session_status() === PHP_SESSION_ACTIVE) {
		if (empty($_SESSION['csrftoken']))
			$_SESSION['csrftoken'] = bin2hex(random_bytes(32));
	}

 	/* this is the first include file loaded by all pages, so... we'll put the page load start time in here */
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$pagestart = $time;
	
	/* database connection */
	$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']);
	$sitename = $cfg['sitename'];

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
			exit(0);
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
	/* bound: usernames are email addresses, which may legally contain quotes (N51) */
	$username = (string)($username ?? '');
	$sqlstring = "select user_isadmin, user_issiteadmin, login_type, user_enablebeta, user_id from users where username = ?";
	$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
	mysqli_stmt_bind_param($stmt, 's', $username);
	$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
	$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
	mysqli_stmt_close($stmt);
	$userid = $row['user_id'] ?? '';
	$isadmin = $row['user_isadmin'] ?? 0;
	$issiteadmin = $row['user_issiteadmin'] ?? 0;
	$enablebeta = $row['user_enablebeta'] ?? 0;
	$_SESSION['enablebeta'] = $enablebeta;
	if (strtolower($row['login_type'] ?? '') == "guest") {
		$isguest = 1;
	}
	else {
		$isguest = 0;
	}
	
	/* each user can only be associated with 1 instance, so display that instance name at the top of the page */
	$sqlstring = "select instance_name from instance where instance_id in (select instance_id from users where username = ?)";
	$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
	mysqli_stmt_bind_param($stmt, 's', $username);
	$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
	$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
	mysqli_stmt_close($stmt);
	$instancename = $row['instance_name'] ?? '';

?>
