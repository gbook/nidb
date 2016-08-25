<?
 // ------------------------------------------------------------------------------
 // NiDB signup.php
 // Copyright (C) 2004 - 2016
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
	$nologin = true;
 	require "functions.php";
	
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>Verify email address</title>
		<META http-equiv="refresh" content="10;URL=login.php">
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<br><br>
<?
	/* ----- setup variables ----- */
	$k = GetVariable("k");

	/* database connection */
	$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

	/* validate the key and redirect as necessary */
	if (Validate($k)) {
		DisplaySuccess();
	}
	else {
		DisplayFail();
	}

	/* -------------------------------------------- */
	/* ------- DisplaySuccess --------------------- */
	/* -------------------------------------------- */
	function DisplaySuccess() {
		?>
		<div align="center">
		<br><br>
		<b>Thank you for activating your NiDB account</b><br>
		You may <a href="login.php">login</a>, or wait to be redirected to the login page in 10s
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayFail ------------------------ */
	/* -------------------------------------------- */
	function DisplayFail() {
		?>
		<div align="center">
		<br><br>
		<b>Invalid account activation</b>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- Validate --------------------------- */
	/* -------------------------------------------- */
	function Validate($k) {
		$k = mysqli_real_escape_string($GLOBALS['linki'], $k);

		if (trim($k) == "") {
			return 0;
		}
		
		/* check if the key exists in the users_pending table */
		$sqlstring = "select * from users_pending where emailkey = '$k'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$userpendingid = $row['user_id'];
			$username = $row['username'];
			$password = $row['password'];
			$fullname = $row['user_fullname'];
			$institution = $row['user_institution'];
			$country = $row['user_country'];
			$email = $row['user_email'];
		}
		else {
			return 0;
		}

		/* if no errors were found so far, insert the row, with the user disabled */
		$sqlstring = "insert into users (username, password, login_type, user_fullname, user_institution, user_country, user_email, user_enabled) values ('$username','$password','Standard','$fullname','$institution','$country','$email',1)";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$userid = mysqli_insert_id();
		
		$sqlstring = "delete from users_pending where user_id = $userpendingid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* insert a row into the instance permissions for the default instance */
		$sqlstring = "insert into user_instance (user_id, instance_id) values ($userid, (select instance_id from instance where instance_default = 1))";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$body = "<b>Your NiDB account on " . $GLOBALS['cfg']['siteurl'] . " account is active and you are joined to the main instance</b><br><br>Login now: " . $GLOBALS['cfg']['siteurl'] . "/login.php<br><br>Follow these steps to join other instances<ol><li>Login to NiDB: " . $GLOBALS['cfg']['siteurl'] . "/login.php<li>Click your username at the top of the page<li>Find the instance you want to join on the list of available instances<li>The owner of the instance will receive notification that you want to join<li>You will receive a notifiication of the owners response to your join request</ol><br><br>";
		/* send the email */
		SendGmail($email,'Your NiDB account has been acitvated',$body, 0);
		
		return 1;
	}
	
	
?>