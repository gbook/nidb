<?
 // ------------------------------------------------------------------------------
 // NiDB squirrel/index.php
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
	
	session_start();

	require "functions.php";


	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	
	/* determine action */
	if ($action == "changepassword") {
		
	}
	elseif ($action == "delete") {
		
	}
	else {
		
	}
	
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>Squirrel package builder</title>
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" type="text/css" href="scripts/semantic/semantic.css">
<script src="scripts/semantic/semantic.min.js"></script>

<noscript>Javascript is required to use NiDB</noscript>
<div id="cookiemessage" style="font-weight:bold; border: 2px solid orange; text-align: center; width: 98%"></div>
<script type="text/javascript">
<!--
function AreCookiesEnabled()
{
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;

    if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled)
    { 
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
<?

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* edit variables */
	$username = GetVariable("username");
	$password = GetVariable("password");

	/* database connection */
	$linki = mysqli_connect('localhost', 'nidb', 'password', 'squirrel') or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");
	
	/* ----- determine which action to take ----- */
	if ($action == "login") {
		if (!CheckLogin($username, $password)) {
			DisplayLogin("Incorrect login. Make sure Caps Lock is not on");
		}
		else {
			header("Location: index.php");
		}
	}
	elseif ($action =="logout") {
		DoLogout();
	}
	else {
		if ($GLOBALS['cfg']['enablecas']){
			$username = AuthenticateCASUser();
			if ($username == "") {
				DisplayLogin("Invalid CAS login");
			}
			else {
				echo "Created the client (session already exists)...<br>";
				phpCAS::setNoCasServerValidation();
				if (phpCAS::checkAuthentication()) {
					$username = phpCAS::getUser();
					echo "Username [$username]";
				}
				else {
					phpCAS::forceAuthentication();
				}
				DoLogin($username);
				header("Location: index.php");
			}
		}
		else {
			DisplayLogin("");
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CheckLogin ------------------------- */
	/* -------------------------------------------- */
	function CheckLogin($username, $password) {
		$validlogin = false;
		if ((AuthenticateUnixUser($username, $password)) && (!$GLOBALS['ispublic'])) {
			Debug(__FILE__, __LINE__,"This is a Unix user account");
			$validlogin = true;
		}
		else {
			Debug(__FILE__, __LINE__,"Not a unix user account");
			if (AuthenticateStandardUser($username, $password)) {
				$validlogin = true;
			}
			else {
				return false;
			}
		}
		
		if ($validlogin) {
			DoLogin($username);
			return true;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DoLogin ---------------------------- */
	/* -------------------------------------------- */
	function DoLogin($username) {
		/* check if they are an admin */
		$sqlstring = "select user_isadmin, user_id from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		if ($row['user_isadmin'] == '1')
			$isadmin = true;
		else
			$isadmin = false;
		
		if (mysqli_num_rows($result) > 0) {
			$sqlstring = "update users set user_lastlogin = now() where username = '$username'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

			$sqlstring = "update users set user_logincount = user_logincount + 1 where username = '$username'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			$sqlstring = "insert into users (username, login_type, user_lastlogin, user_logincount, user_enabled) values ('$username', 'NIS', now(), 1, 1)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
			
		$_SESSION['username'] = $username;
		$_SESSION['validlogin'] = "true";
		$_SESSION['userid'] = $userid;
		if ($isadmin) $_SESSION['isadmin'] = "true";
		else $_SESSION['isadmin'] = "false";
		
		$sqlstring = "select instance_id from user_instance where user_id = (select user_id from users where username = '$username')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instanceid = $row['instance_id'];
		if ($instanceid == '') {
			$sqlstring = "insert into user_instance (user_id, instance_id) values ((select user_id from users where username = '$username'),(select instance_id from instance where instance_default = 1))";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			$sqlstring = "select instance_id from instance where instance_default = 1";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$instanceid = $row['instance_id'];
		}
		
		$sqlstring = "select instance_name from instance where instance_id = $instanceid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instancename = $row['instance_name'];
		
		$_SESSION['instanceid'] = $instanceid;
		$_SESSION['instancename'] = $instancename;
	}

	/* -------------------------------------------- */
	/* ------- DoLogout --------------------------- */
	/* -------------------------------------------- */
	function DoLogout() {
		session_destroy();
		setcookie('MOD_AUTH_CAS', '', time()-1000, '/');
		
		if ($GLOBALS['cfg']['enablecas']) {
			phpCAS::logoutWithRedirectService($GLOBALS['cfg']['siteurl']);
			echo "You have been logged out of NiDB through CAS. <a href='login.php'>Login</a> again.";
		}
		else {
			DisplayLogin("You have been logged out");
		}
	}


	/* -------------------------------------------- */
	/* ------- AuthenticateStandardUser ----------- */
	/* -------------------------------------------- */
	function AuthenticateStandardUser($username, $password) {
		/* attempt to authenticate a standard user */
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		
		if ((trim($username) == "") || (trim($password) == ""))
			return false;
			
		$sqlstring = "select user_id from users where username = '$username' and password = sha1('$password') and user_enabled = 1";
		Debug(__FILE__, __LINE__,"In AuthenticateStandardUser(): [$sqlstring]");
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0)
			return true;
		else
			return false;
	}
	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayLogin ----------------------- */
	/* -------------------------------------------- */
	function DisplayLogin($message) {
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
			<form method="post" action="login.php" class="ui form">
				<input type="hidden" name="action" value="login">

				<div class="ui raised compact segment">
					<? if ($message != "") { ?>
					<div class="ui center aligned inverted tertiary red segment">
						<?=$message?>
					</div>
					<? } ?>
						<img class="ui medium centered image" src="images/NIDB_logo.png">
						<br><br>
						<? if ($GLOBALS['cfg']['enablecas']) { ?>
							<input class="ui primary button" type="submit" value="Login with CAS">
						<?
							}
							else {
						?>
						<table cellspacing="5" cellpadding="5">
							<tr>
								<td>
									<div class="ui header">
										Username
									</div>
								</td>
								<td>
									<input type="text" name="username" maxlength="50" autofocus="autofocus">
								</td>
							</tr>
							<tr>
								<td>
									<div class="ui header">
										Password
									</div>
								</td>
								<td>
									<input type="password" name="password" maxlength="50">
								</td>
							</tr>
							
							<tr>
								<td>
									<? if ($GLOBALS['cfg']['ispublic']) { ?>
									New user? <a href="signup.php">Sign up</a>.<br>
									Forgot password? <a href="signup.php?a=r">Reset it</a>.
									<? } ?>
								</td>
								<td align="right">
									<input class="ui primary button" type="submit" value="Login">
								</td>
							</tr>
						</table>
						<?
							}
						?>
				</div>
				
			</form>
		</div>
		
		<div style="position:absolute; bottom:5; width:95%; height: 30px; padding:10px">
			<table width="100%" cellspacing="0" cellpadding="6">
				<tr>
					<td align="left" style="font-size:8pt; color: #555">
						NiDB v<?=$GLOBALS['cfg']['version']?> on <?=$_SERVER['HTTP_HOST']?>
					</td>
				</tr>
			</table>
		</div>
		<?
	}
?>
</body>
<? ob_end_flush(); ?>
