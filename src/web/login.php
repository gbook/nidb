<?
 // ------------------------------------------------------------------------------
 // NiDB login.php
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


	define("LEGIT_REQUEST", true);
	
	session_start();
	
	$nologin = true;
 	require "functions.php";
 	require "includes_php.php";
	require_once("phpCAS/CAS.php");
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>Login to NiDB</title>
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
	$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

	/* connect to CAS if enabled */
	if ($GLOBALS['cfg']['enablecas']){
		phpCAS::client(CAS_VERSION_2_0, $GLOBALS['cfg']['casserver'], intval($GLOBALS['cfg']['casport']), $GLOBALS['cfg']['cascontext']);
	}
	
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
	/* ------- AuthenticateUnixUser --------------- */
	/* -------------------------------------------- */
	function AuthenticateUnixUser($username, $password) {
		
		if (($username != "root") && ($username != "")) {
			
			/* attempt to authenticate a unix user */
			$pwent = posix_getpwnam($username);
			$password_hash = $pwent["passwd"];

			if (trim(shell_exec("command -v ypmatch")) != "") {
					
				$autharray = explode(":",`ypmatch $username passwd`);
				if ($autharray[0] != $username) {
					return false;
				}
				
				$cryptpw = crypt($password, $autharray[1]);
				
				if($cryptpw == $autharray[1])
					return true;
			}
		}
		
		return false;
	}
	
	
	/* -------------------------------------------- */
	/* ------- AuthenticateCASUser ---------------- */
	/* -------------------------------------------- */
	function AuthenticateCASUser() {
		//phpCAS::setDebug("/tmp/phpCAS.log");
		// Enable verbose error messages. Disable in production!
		//phpCAS::setVerbose(true);		
		//echo "I'm in the AuthenticateCASUser function<br>";
		if(isset($_SESSION)) {
			//phpCAS::client(CAS_VERSION_2_0, $GLOBALS['cfg']['casserver'], intval($GLOBALS['cfg']['casport']), $GLOBALS['cfg']['cascontext'], false);
			echo "Created the client (session already exists)...<br>";
			phpCAS::setNoCasServerValidation();
			if (phpCAS::checkAuthentication()) {
				$username = phpCAS::getUser();
				//echo "Username [$username]";
				return $username;
			}
			else {
				phpCAS::forceAuthentication();
			}
		}
		phpCAS::setNoCasServerValidation();
		//echo "Set the no server validation...<br>";
		//actually authenticate
		if (phpCAS::checkAuthentication()) {
			//echo "Already authenticated...<br>";
		}
		else {
			//echo "NOT already authenticated...<br>";
			phpCAS::forceAuthentication();
			# We'll never get back to this point! because CAS will redirect back to login.php with no POST variables passed in...
			echo "Did the authentication...<br>";
		}
		return '';
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayLogin ----------------------- */
	/* -------------------------------------------- */
	function DisplayLogin($message) {
		?>
		<form method="post" action="login.php" class="ui form">
			<input type="hidden" name="action" value="login">

			<br><br><br>
			<div class="ui grid">
				<div class="ui six wide column"></div>
				<div class="ui four wide column">
					<div class="ui top attached inverted center aligned segment">
						<h2 class="ui header">Login</h2>
					</div>
					<? if ($message != "") { ?>
					<div class="ui attached center aligned inverted tertiary red segment">
						<?=$message?>
					</div>
					<? } ?>
					<div class="ui bottom attached segment">
						<img class="ui medium centered image" src="images/NIDB_logo.png">
						<br><br>
						<? if ($GLOBALS['cfg']['enablecas']) { ?>
							<input class="ui primary button" type="submit" value="Login with CAS">
						<?
							}
							else {
						?>
						<div class="ui two column grid">
							<div class="right aligned column">
								<span style="font-size: larger">Username</span><br><span class="tiny"> or email address</span>
							</div>
							<div class="column">
								<input type="text" name="username" maxlength="50" autofocus="autofocus">
							</div>
							<div class="right aligned column">
								<span style="font-size: larger">Password</span><br><span class="tiny">Case sensitive</span>
							</div>
							<div class="column">
								<input type="password" name="password" maxlength="50">
							</div>
							
							<div class="column">
								<? if ($GLOBALS['cfg']['ispublic']) { ?>
								New user? <a href="signup.php">Sign up</a>.<br>
								Forgot password? <a href="signup.php?a=r">Reset it</a>.
								<? } ?>
							</div>
							<div class="column">
								<input class="ui primary button" type="submit" value="Login">
							</div>
						</div>
						<?
							}
						?>
							
					</div>
				</div>
				<div class="ui six wide column">
					<? if ($GLOBALS['cfg']['ispublic']) { ?>
						<table>
							<tr>
								<td align="center">
									View publicly available <a href="downloads.php">downloads</a>
									<br><br><br><br><br>
								</td>
							</tr>
							<tr>
								<td width="500px">
									<span style="font-size:10pt; color: #444">
									<b>Interested in NeuroInformatics Database?</b> <span style="font-size:8pt">This instance of NiDB is hosted by the <a href="http://www.nrc-iol.org/">Olin Neuropsychiatry Research Center</a> and <a href="http://www.harthosp.org">Hartford Hospital</a><br></span>
									<ul>
										<li><b>Want to share data?</b> <span style="font-size:8pt">Contact gregory.book@hhchealth.org</span>
										<li><b>NiDB is open source</b> <span style="font-size:8pt">Download on <a href="https://github.com/gbook/nidb">github</a></span>
									</ul>
									</span>
								</td>
							</tr>
						</table>
					<? } ?>
				</div>
			</div>
		</form>
		
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
