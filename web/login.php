<?
 // ------------------------------------------------------------------------------
 // NiDB login.php
 // Copyright (C) 2004 - 2015
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

	session_start();
	
	$nologin = true;
 	require "functions.php";
	//ob_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>Login to NiDB</title>
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<br><br>
<?

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* edit variables */
	$username = GetVariable("username");
	$password = GetVariable("password");
	//$persistent = GetVariable("persistent");


	/* database connection */
	$link = mysql_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword']) or die ("Could not connect: " . mysql_error());
	mysql_select_db($GLOBALS['cfg']['mysqldatabase']) or die ("Could not select database<br>");

	
	/* ----- determine which action to take ----- */
	if ($action == "login") {
		if (!DoLogin($username, $password))
			DisplayLogin("Incorrect login. Make sure Caps Lock is not on");
		else {
			header("Location: index.php");
		}
	}
	elseif ($action =="logout") {
		DoLogout();
		DisplayLogin("You have been logged out");
	}
	else {
		DisplayLogin("");
	}

	
	/* -------------------------------------------- */
	/* ------- DoLogin ---------------------------- */
	/* -------------------------------------------- */
	function DoLogin($username, $password) {
		
		if ((AuthenticateUnixUser($username, $password)) && (!$GLOBALS['ispublic'])) {
		
			/* check if they are an admin */
			$sqlstring = "select user_isadmin from users where username = '$username'";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			if ($row['user_isadmin'] == '1')
				$isadmin = true;
			else
				$isadmin = false;
			
			if (mysql_num_rows($result) > 0) {
				$sqlstring = "update users set user_lastlogin = now() where username = '$username'";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);

				$sqlstring = "update users set user_logincount = user_logincount + 1 where username = '$username'";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			}
			else {
				$sqlstring = "insert into users (username, login_type, user_lastlogin, user_logincount, user_enabled) values ('$username', 'NIS', now(), 1, 1)";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			}

			$_SESSION['username'] = $username;
			$_SESSION['validlogin'] = "true";
			if ($isadmin) $_SESSION['isadmin'] = "true";
			else $_SESSION['isadmin'] = "false";
			
			$sqlstring = "select instance_id from user_instance where user_id = (select user_id from users where username = '$username')";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$instanceid = $row['instance_id'];
			//echo "[$sqlstring] - [$instanceid]<br>";
			if ($instanceid == '') {
				$sqlstring = "insert into user_instance (user_id, instance_id) values ((select user_id from users where username = '$username'),(select instance_id from instance where instance_default = 1))";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				
				$sqlstring = "select instance_id from instance where instance_default = 1";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$instanceid = $row['instance_id'];
			}
			
			$sqlstring = "select instance_name from instance where instance_id = $instanceid";
			$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$instancename = $row['instance_name'];
			//echo "[$sqlstring] - [$instancename]<br>";
			
			$_SESSION['instanceid'] = $instanceid;
			$_SESSION['instancename'] = $instancename;
			
			//exit(0);
			return true;
		}
		else {
			//echo "Not a UNIX account, trying standard account";
			if (AuthenticateStandardUser($username, $password)) {
			
				/* check if they are an admin */
				$sqlstring = "select user_isadmin from users where username = '$username'";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				if ($row['user_isadmin'] == '1')
					$isadmin = true;
				else
					$isadmin = false;

				$sqlstring = "update users set user_lastlogin = now() where username = '$username'";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				$sqlstring = "update users set user_logincount = user_logincount + 1 where username = '$username'";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);

				$_SESSION['username'] = $username;
				$_SESSION['validlogin'] = "true";
				if ($isadmin) $_SESSION['isadmin'] = "true";
				else $_SESSION['isadmin'] = "false";
				
				$sqlstring = "select instance_id from user_instance where user_id = (select user_id from users where username = '$username')";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$instanceid = $row['instance_id'];
				//echo "[$sqlstring] - [$instanceid]<br>";
				if ($instanceid == '') {
					$sqlstring = "insert into user_instance (user_id, instance_id) values ((select user_id from users where username = '$username'),(select instance_id from instance where instance_default = 1))";
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
					
					$sqlstring = "select instance_id from instance where instance_default = 1";
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
					$row = mysql_fetch_array($result, MYSQL_ASSOC);
					$instanceid = $row['instance_id'];
				}
				
				$sqlstring = "select instance_name from instance where instance_id = $instanceid";
				$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$instancename = $row['instance_name'];
				//echo "[$sqlstring] - [$instancename]<br>";
				
				$_SESSION['instanceid'] = $instanceid;
				$_SESSION['instancename'] = $instancename;
				
				return true;
			}
			else {
				return false;
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- DoLogout --------------------------- */
	/* -------------------------------------------- */
	function DoLogout() {
		//setcookie("username", "");
		//setcookie("validlogin", "false");
		session_destroy();
	}


	/* -------------------------------------------- */
	/* ------- AuthenticateStandardUser ----------- */
	/* -------------------------------------------- */
	function AuthenticateStandardUser($username, $password) {
		/* attempt to authenticate a standard user */
		$username = mysql_real_escape_string($username);
		$password = mysql_real_escape_string($password);
		
		if ((trim($username) == "") || (trim($password) == ""))
			return false;
			
		$sqlstring = "select user_id from users where username = '$username' and password = sha1('$password') and user_enabled = 1";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0)
			return true;
		else
			return false;
	}
	
	
	/* -------------------------------------------- */
	/* ------- AuthenticateUnixUser --------------- */
	/* -------------------------------------------- */
	function AuthenticateUnixUser($username, $password) {
		/* attempt to authenticate a unix user */
		$pwent = posix_getpwnam($username);
		$password_hash = $pwent["passwd"];
		//echo "User info for $username: {{$password_hash}}";
		//print_r($pwent);
		//if($pwent == false)
		//	return false;
			
		$autharray = split(":",`ypmatch $username passwd`);
		if ($autharray[0] != $username) {
			return false;
		}
			
		//echo "<pre>blahablah";
		//print_r($autharray);
		//echo "</pre>";
		
		$cryptpw = crypt($password, $autharray[1]);
		
		if($cryptpw == $autharray[1])
			return true;
		return false;
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayLogin ----------------------- */
	/* -------------------------------------------- */
	function DisplayLogin($message) {
		?>
			<form method="post" action="login.php">
			<input type="hidden" name="action" value="login">
			
			<table width="100%" height="90%">
				<tr>
					<td align="center" valign="middle">
						<img src="images/nidb_short_notext_small.png" height="40">
						<br><br>
						<table cellpadding="5" class="editor">
							<tr>
								<td colspan="2" align="center" style="background-color: #3B5998; color: white; font-weight: bold; border-radius:5px">
									Login to NiDB
								</td>
							</tr>
							<tr>
								<td colspan="2" align="center" style="color: red">
								&nbsp;<small><?=$message?></small>
								</td>
							</tr>
							<tr title="Username is your email address if you self-registered">
								<td class="label">Username<br><span class="tiny">or email address</span></td>
								<td>
									<input type="text" name="username" maxlength="50" autofocus="autofocus">
								</td>
							</tr>
							<tr>
								<td class="label">Password<br><span class="tiny">Case sensitive</span></td>
								<td>
									<input type="password" name="password" maxlength="50">
								</td>
							</tr>
							<tr>
								<td style="font-size:8pt; text-align: left">
									<? if ($GLOBALS['cfg']['ispublic']) { ?>
									New user? <a href="signup.php">Sign up</a>.<br>
									Forgot password? <a href="signup.php?a=r">Reset it</a>.
									<? } ?>
								</td>
								<td align="right">
									<input type="submit" value="Login">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<? if ($GLOBALS['cfg']['ispublic']) { ?>
				<tr>
					<td align="center">
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
										<!--<li><b>Use NiDB as a primary database</b> <span style="font-size:8pt">Commercial hosting provided by <a href="http://neuroinfodb.com">NeuroInfoDB.com LLC</a></span>-->
										<li><b>NiDB is open source</b> <span style="font-size:8pt">Download on <a href="https://github.com/gbook/nidb">github</a></span>
									</ul>
									</span>
								</td>
							</tr>
						</table>
					</td>
					<? } ?>						
				</tr>
			</table>
			
			</form>
			
		</div>
		
		<div style="position:absolute; bottom:0; width:95%; height: 30px; padding:10px">
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

<? ob_end_flush(); ?>
