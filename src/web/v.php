<?
 // ------------------------------------------------------------------------------
 // NiDB signup.php
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
	$nologin = true;
	require "functions.php";
	require "includes_php.php";

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
		<b>Thank you for verifying your email address</b><br><br>
		An administrator will now review your account. You will be able to <a href="login.php">log in</a> once it is approved.
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
		<b>Invalid account activation</b><br><br>
		The link may have expired (links last 7 days) or already been used. You can <a href="signup.php">sign up again</a>.
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- Validate --------------------------- */
	/* -------------------------------------------- */
	function Validate($k) {
		if ((!is_string($k)) || (trim($k) == "")) {
			return 0;
		}
		
		/* signup.php emails a random 64-hex-digit key and stores only its SHA-256. Keys made before that
		   change (the SHA-1 of the signup time, which could be computed) never match */
		$k = trim($k);
		if (!preg_match('/^[0-9a-f]{64}$/', $k)) {
			return 0;
		}
		$keyhash = hash('sha256', $k);

		/* check if the key exists in the users_pending table, and the signup is less than 7 days old */
		$sqlstring = "select * from users_pending where emailkey = ? and signupdate >= date_sub(now(), interval 7 day)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $keyhash);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, ['[key]']);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$userpendingid = $row['user_id'];
			$username = $row['username'];
			$password = $row['password'];
			$fullname = trim($row['user_fullname']);
			if ($fullname == "") {
				$fullname = $row['user_firstname'] . " " . $row['user_midname'] . " " . $row['user_lastname'];
			}
			$institution = $row['user_institution'];
			$country = $row['user_country'];
			$email = $row['user_email'];
			
			$nameparts = explode(" ", $fullname);
			$fname = $mname = $lname = "";
			if (count($nameparts) == 1) {
				$fname = $nameparts[0];
			}
			elseif (count($nameparts) == 2) {
				$fname = $nameparts[0];
				$lname = $nameparts[1];
			}
			elseif (count($nameparts) == 3) {
				$fname = $nameparts[0];
				$mname = $nameparts[1];
				$lname = $nameparts[2];
			}
			elseif (count($nameparts) > 3) {
				$fname = $nameparts[0];
				$mname = $nameparts[1];
				$lname = implode(" ", array_slice($nameparts, 2));
			}
			else {
				$fname = "Name not";
				$lname = "Specified";
			}
		}
		else {
			return 0;
		}

		/* the address may have been registered another way since the signup (e.g. by an admin) */
		$sqlstring = "select count(*) 'count' from users where username = ? or user_email = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username, $email]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if ($row['count'] > 0) {
			return 0;
		}

		/* create the account disabled. An admin enables it in adminusers.php */
		$sqlstring = "insert into users (username, password, login_type, user_fullname, user_institution, user_country, user_email, user_enabled) values (?, ?, 'Standard', ?, ?, ?, ?, 0)";
		$params = [$username, $password, $fullname, $institution, $country, $email];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssss', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$userid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		$userpendingid = (int)$userpendingid;
		$sqlstring = "delete from users_pending where user_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $userpendingid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userpendingid]);
		mysqli_stmt_close($stmt);
		
		/* insert a row into the instance permissions for the default instance */
		$sqlstring = "insert into user_instance (user_id, instance_id) values (?, (select instance_id from instance where instance_default = 1))";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $userid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid]);
		mysqli_stmt_close($stmt);
		
		$body = "<b>Your email address is verified</b><br><br>Your account on " . $GLOBALS['cfg']['siteurl'] . " is waiting for an administrator to approve it. You will be able to log in once it is approved.<br><br>After you log in, follow these steps to join other instances<ol><li>Login to NiDB: " . $GLOBALS['cfg']['siteurl'] . "/login.php<li>Click your username at the top of the page<li>Find the instance you want to join on the list of available instances<li>The owner of the instance will receive notification that you want to join<li>You will receive a notification of the owner's response to your join request</ol><br><br>";
		/* send the email */
		SendEmail($email,'Your NiDB email address is verified',$body, 0);

		/* tell the admin there is an account to review */
		$adminemail = trim($GLOBALS['cfg']['adminemail'] ?? '');
		if ($adminemail != "") {
			$body = "<b>A new NiDB account is waiting for approval</b><br><br>Name: " . htmlspecialchars($fullname) . "<br>Email: " . htmlspecialchars($email) . "<br>Institution: " . htmlspecialchars($institution) . "<br>Country: " . htmlspecialchars($country) . "<br><br>The account was created disabled. To approve it, enable it on the users page: " . $GLOBALS['cfg']['siteurl'] . "/adminusers.php<br>If you don't recognize it, leave it disabled or delete it.";
			SendEmail($adminemail, 'NiDB account waiting for approval', $body, 0);
		}
		
		return 1;
	}
	
	
?>