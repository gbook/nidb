<?
 // ------------------------------------------------------------------------------
 // NiDB register.php
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
	
	/* required for CAPTCHA */
	session_start();

	$nologin = true;
 	require "functions.php";
 	require "includes_php.php";
 	require "includes_html.php";
	
	/* more CAPTCHA code */
	include_once $_SERVER['DOCUMENT_ROOT'] . '/scripts/securimage/securimage.php';
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB</title>
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<br><br>
<?
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	if ($action == "") { $action = GetVariable("a"); }

	/* edit variables */
	$email = GetVariable("email");
	$e = GetVariable("e");
	$firstname = GetVariable("firstname");
	$midname = GetVariable("midname");
	$lastname = GetVariable("lastname");
	$institution = GetVariable("institution");
	$instance = GetVariable("instance");
	$country = GetVariable("country");
	$password = GetVariable("password");

	/* database connection */
	$linki = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysqli_error() . "]  File [" . __FILE__ . "] Line [ " . __LINE__ . "]");

	
	/* ----- determine which action to take ----- */
	switch ($action) {
		case 'create':
			$msg = CreateAccount($email, $firstname, $midname, $lastname, $institution, $instance, $country, $password);
			if ($msg != "") {
				DisplayForm($msg, $email, $firstname, $midname, $lastname, $institution, $instance, $country);
			}
			else {
				DisplaySuccessMessage($email, $firstname, $midname, $lastname, $institution, $instance, $country, $password);
			}
			break;
		case 'r':
			ResetPasswordForm("");
			break;
		case 'rp':
			ResetPassword($e);
			break;
		default:
			DisplayForm("");
	}


	/* -------------------------------------------- */
	/* ------- DisplaySuccessMessage -------------- */
	/* -------------------------------------------- */
	function DisplaySuccessMessage($email, $name, $institution, $country, $password) {
		?>
		<div align="center">
		<br><br>
		<b>Thank you for signing up</b><br><br>
		An email has been sent to &lt;<?=$email?>&gt; with a link to activate your account.
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- CreateAccount ---------------------- */
	/* -------------------------------------------- */
	function CreateAccount($email, $firstname, $midname, $lastname, $institution, $instance, $country, $password) {
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$firstname = mysqli_real_escape_string($GLOBALS['linki'], $firstname);
		$midname = mysqli_real_escape_string($GLOBALS['linki'], $midname);
		$lastname = mysqli_real_escape_string($GLOBALS['linki'], $lastname);
		$institution = mysqli_real_escape_string($GLOBALS['linki'], $institution);
		$country = mysqli_real_escape_string($GLOBALS['linki'], $country);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		
		$securimage = new Securimage();
		if ($securimage->check($_POST['captcha_code']) == false) {
			// or you can use the following code if there is no validation or you do not know how
			return "CAPTCHA code entered was incorrect";
		}
		if (trim($email) == "") {
			return "Email was blank";
		}
		if ((trim($firstname) == "") && (trim($lastname) == "")) {
			return "Name was blank";
		}
		if (trim($institution) == "") {
			return "Institution was blank";
		}
		if (trim($country) == "") {
			return "Country was blank";
		}
		if (trim($password) == "") {
			return "Password was blank";
		}
		
		/* check if the username or email address is already in the users table */
		$sqlstring = "select count(*) 'count' from users where username = '$email' or user_email = '$email'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$count = $row['count'];
		//echo "Count [$count]<br>";
		if ($count > 0) {
			return "Email address already registered";
		}
		
		$userpendingid = -1;
		
		/* check if the username or email address is already in the users_pending table */
		$sqlstring = "select user_id from users_pending where username = '$email' or user_email = '$email'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$userpendingid = $row['user_id'];
			echo "Email address already registered, but not activated. Sending verification email again.";
		}
		else {
			/* if no errors were found so far, insert the row, with the user disabled */
			/* insert a temp account into the DB */
			//$sqlstring = "insert into users (username, password, login_type, user_instanceid, user_fullname, user_institution, user_country, user_email, user_enabled) values ('$email',sha1('$password'),'Standard','$instance','$name','$institution','$country','$email',0)";
			$sqlstring = "insert into users_pending (username, password, user_firstname, user_midname, user_lastname, user_institution, user_country, user_email, emailkey, signupdate) values ('$email',sha1('$password'),'$firstname','$midname','$lastname','$institution','$country','$email',sha1(now()), now())";
			//echo "$sqlstring<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$userpendingid = mysqli_insert_id($GLOBALS['linki']);
		}

		/* get the generated SHA1 hash */
		$sqlstring = "select emailkey from users_pending where user_id = $userpendingid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$emailkey = $row['emailkey'];
		
		$body = "<b>Thank for you signing up for NiDB</b><br><br>Click the link below to activate your account (or copy and paste into a browser)\n" . $GLOBALS['cfg']['siteurl'] . "/v.php?k=$emailkey";
		/* send the email */
		if (!SendGmail($email,'Acitvate your NiDB account',$body, 0)) {
			return "System error. Unable to send email!";
			$sqlstring = "delete from users_pending where user_id = $userpendingid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			return "";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayForm ------------------------ */
	/* -------------------------------------------- */
	function DisplayForm($message, $email="", $name="", $institution="", $instance="", $country="") {
		?>
			<script type="text/javascript">
			$(document).ready(function() {
				/* check the matching passwords */
				$("#submit").click(function(){
					$(".error").hide();
					var hasError = false;
					var passwordVal = $("#password").val();
					var checkVal = $("#password-check").val();
					if (passwordVal == '') {
						$("#password").after('<span class="error">Please enter a password.</span>');
						hasError = true;
					}
					else if (checkVal == '') {
						$("#password-check").after('<span class="error">Please re-enter your password.</span>');
						hasError = true;
					}
					else if (passwordVal != checkVal ) {
						$("#password-check").after('<span class="error">Passwords do not match.</span>');
						hasError = true;
					}
					if(hasError == true) {return false;}
				});
				
				/* to disable the autofill thing in Chrome */
				if ($.browser.webkit) {
					$('input[name="username"]').attr('autocomplete', 'off');
					$('input[name="fullname"]').attr('autocomplete', 'off');
					$('input[name="password"]').attr('autocomplete', 'off');
				}
			}
			</script>
			<div class="ui text container">
				
				<div class="ui blue segment">
					<h1 class="header">Register to download BSNIP data</h1>
				</div>
				<form method="post" action="register.php" class="ui form">
				<input type="hidden" name="action" value="register">
					<div class="field">
						<label>Name</label>
						<input type="text" name="name" maxlength="100" required autofocus="autofocus" value="<?=$name?>" placeholder="Name">
					</div>
					<div class="field">
						<label>Email</label>
						<input type="email" name="email" maxlength="100" required value="<?=$email?>">
					</div>
					<div class="field">
						<label>Institution</label>
						<input type="text" name="institution" maxlength="100" required value="<?=$institution?>">
					</div>
					<div class="field">
						<label>Country</label>
						<input type="text" name="country" maxlength="100" required value="<?=$country?>">
					</div>
					<br><br>
					<b>Terms of Service</b>
					<textarea style="width:100%; height:200px"><? readfile('license.txt')?></textarea><br>
					<input type="checkbox" name="licenseagree" value="1" required>I agree to terms and conditions listed above
					
					<br><br>
					
					<img id="captcha" src="scripts/securimage/securimage_show.php" alt="CAPTCHA Image" />
					<br>
					<span class="tiny">Enter the CAPTCHA code</span>
					<input type="text" name="captcha_code" size="10" maxlength="6" />
					<a href="#" style="font-size:9pt" onclick="document.getElementById('captcha').src = 'scripts/securimage/securimage_show.php?' + Math.random(); return false"><img src="images/refresh16.png" title="Get new image"></a>

					<br><br>
					<input type="submit" value="Create Account" class="ui primary button">
				</form>
			</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ResetPasswordForm ------------------ */
	/* -------------------------------------------- */
	function ResetPasswordForm($msg) {
		?>
			<form method="post" action="signup.php">
			<input type="hidden" name="action" value="rp">
			
			<div align="center">
			<table style="border: 1px solid #ccc; padding:5px">
				<tr>
					<td align="center" style="color: darkred"><?=$msg?></td>
					<td align="center" style="color: #555; font-size: 10pt">
						<img id="captcha" src="scripts/securimage/securimage_show.php" alt="CAPTCHA Image" />
						<br>
						<span class="tiny">Enter the CAPTCHA code</span>
						<input type="text" name="captcha_code" size="10" maxlength="6" />
						<a href="#" style="font-size:9pt" onclick="document.getElementById('captcha').src = 'scripts/securimage/securimage_show.php?' + Math.random(); return false"><img src="images/refresh16.png" title="Get new image"></a>
						<br>
						Enter email <input type="email" name="e" required>
						&nbsp;
						<input type="submit" value="Reset password" class="ui primary button">
					</td>
				</tr>
			</table>
			</form>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- ResetPassword ---------------------- */
	/* -------------------------------------------- */
	function ResetPassword($email) {
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);

		$safetoemail = 0;
		
		$securimage = new Securimage();
		if ($securimage->check($_POST['captcha_code']) == false) {
			// or you can use the following code if there is no validation or you do not know how
			ResetPasswordForm("CAPTCHA code entered was incorrect");
		}
		if (trim($email) == "") {
			ResetPasswordForm("Email was blank");
		}
		
		/* check if the username or email address is already in the users table */
		$sqlstring = "select count(*) 'count' from users where username = '$email' or user_email = '$email'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$count = $row['count'];
		//echo "Count [$count]<br>";
		if ($count > 0) {
			$safetoemail = 1;
		}
		else {
			/* check if the username or email address is already in the users_pending table */
			$sqlstring = "select count(*) 'count' from users where username = '$email' or user_email = '$email'";
			//echo "$sqlstring<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$count = $row['count'];
			//echo "Count [$count]<br>";
			if ($count > 0) {
				?>This email address was used to sign up for an account, but has not been activated<?
			}
			else {
				?>This email address is not valid in this system<?
				return 0;
			}
		}
		
		
		$newpass = GenerateRandomString(10);
		
		/* send a password reset email */
		$body = "Your password has been temporarily reset to '$newpass'. Please login to " . $GLOBALS['cfg']['siteurl'] . " and change your password";
		/* send the email */
		if (!SendGmail($email,'NiDB password reset',$body, 0)) {
			echo "System error. Unable to send email!";
			//$sqlstring = "delete from users_pending where user_id = $rowid";
			//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			$sqlstring = "update users set password = sha1('$newpass') where user_email = '$email'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			echo "Email sent to '$email'. Check it and get back to me";
		}
		?><?
	}
	

ob_end_flush();
	
?>
