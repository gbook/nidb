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

	/* buffer output so the POST handlers can redirect (Post/Redirect/GET) */
	ob_start();

	/* the session holds the CAPTCHA, the CSRF token, and flash messages */
	session_start();

	$nologin = true;
 	require "functions.php";
 	require "includes_php.php";

	/* more CAPTCHA code */
	include_once $_SERVER['DOCUMENT_ROOT'] . '/scripts/securimage/securimage.php';

	/* password reset links carry a token in the URL, so don't send it to other sites in a Referer header */
	header("Referrer-Policy: no-referrer");

	/* self-registration can be turned off with [allowsignup] in the config. A missing setting means on,
	   which was the behavior before the setting existed. New accounts still need admin approval (v.php) */
	$allowsignup = (($GLOBALS['cfg']['allowsignup'] ?? '1') == '1');
?>

<html>
	<head>
		<meta charset="utf-8">
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB</title>
	</head>

<body>
<link rel="stylesheet" type="text/css" href="style.css">
<br><br>
<?
	/* ----- setup variables ----- */
	/* the mutating actions must be a POST carrying this session's CSRF token. The "a" link parameter
	   only selects read-only views, so it can't be used to reach a mutating action */
	$action = GetSafeAction(array("create", "rp", "setpassword"));
	$a = GetVariable("a");
	if (($action == "") && in_array($a, array("r", "reset", "msg"), true)) { $action = $a; }

	/* edit variables */
	$email = GetVariable("email");
	$e = GetVariable("e");
	$k = GetVariable("k");
	$firstname = GetVariable("firstname");
	$midname = GetVariable("midname");
	$lastname = GetVariable("lastname");
	$institution = GetVariable("institution");
	$country = GetVariable("country");
	$password = GetVariable("password");
	$passwordcheck = GetVariable("password-check");

	/* ----- determine which action to take ----- */
	switch ($action) {
		case 'create':
			if (!$allowsignup) {
				DisplaySignupDisabled();
				break;
			}
			$msg = CreateAccount($email, $firstname, $midname, $lastname, $institution, $country, $password, $passwordcheck);
			if ($msg != "") {
				DisplayForm($msg, $email, $firstname, $midname, $lastname, $institution, $country);
			}
			else {
				$_SESSION['flash'] = "<b>Thank you for signing up</b><br><br>An email has been sent to &lt;" . htmlspecialchars($email ?? '') . "&gt; with a link to verify your email address.<br>After that, an administrator will review your account. You will be able to log in once it is approved.";
				RedirectTo("signup.php?a=msg");
			}
			break;
		case 'r':
			ResetPasswordForm("");
			break;
		case 'rp':
			$msg = RequestPasswordReset($e);
			if ($msg != "") {
				ResetPasswordForm($msg);
			}
			else {
				/* the same message whether or not the address has an account, so this can't be used to find accounts */
				$_SESSION['flash'] = "If an active account uses that address, an email has been sent to it with a link to choose a new password.<br>The link can be used once, and expires in 1 hour.";
				RedirectTo("signup.php?a=msg");
			}
			break;
		case 'reset':
			SetPasswordForm($k, "");
			break;
		case 'setpassword':
			$msg = SetPassword($k, $password, $passwordcheck);
			if ($msg != "") {
				SetPasswordForm($k, $msg);
			}
			else {
				$_SESSION['flash'] = "Your password has been changed.";
				RedirectTo("signup.php?a=msg");
			}
			break;
		case 'msg':
			DisplayMessage();
			break;
		default:
			if ($allowsignup) {
				DisplayForm("");
			}
			else {
				DisplaySignupDisabled();
			}
	}


	/* -------------------------------------------- */
	/* ------- DisplayMessage --------------------- */
	/* -------------------------------------------- */
	/* shows the flash message stashed by a POST handler before it redirected here */
	function DisplayMessage() {
		?>
		<div align="center">
		<br><br>
		<? ShowFlashMessage(); ?>
		<br><br>
		<a href="login.php">Go to the login page</a>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySignupDisabled -------------- */
	/* -------------------------------------------- */
	function DisplaySignupDisabled() {
		?>
		<div align="center">
		<br><br>
		<b>Self-registration is turned off on this server</b><br><br>
		Contact the site administrator to request an account.<br><br>
		<a href="login.php">Go to the login page</a>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- CreateAccount ---------------------- */
	/* -------------------------------------------- */
	/* adds a row to users_pending and emails a verification link. Returns "" on success, or an error
	   message to show with the form. v.php turns the pending row into a disabled account that an
	   admin must enable. */
	function CreateAccount($email, $firstname, $midname, $lastname, $institution, $country, $password, $passwordcheck) {
		$email = trim($email ?? '');
		$firstname = trim($firstname ?? '');
		$midname = trim($midname ?? '');
		$lastname = trim($lastname ?? '');
		$institution = trim($institution ?? '');
		$country = trim($country ?? '');
		$password = $password ?? '';
		$passwordcheck = $passwordcheck ?? '';

		$securimage = new Securimage();
		if ($securimage->check($_POST['captcha_code'] ?? '') == false) {
			return "CAPTCHA code entered was incorrect";
		}
		if ($email == "") {
			return "Email was blank";
		}
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			return "Email address is not valid";
		}
		/* the address becomes the username, which is varchar(45) */
		if (strlen($email) > 45) {
			return "Email address is too long (maximum 45 characters)";
		}
		if (($firstname == "") && ($lastname == "")) {
			return "Name was blank";
		}
		if ($institution == "") {
			return "Institution was blank";
		}
		if ($country == "") {
			return "Country was blank";
		}
		if (strlen($password) < 8) {
			return "Password must be at least 8 characters";
		}
		if ($password !== $passwordcheck) {
			return "Passwords do not match";
		}

		/* signups that were never verified expire after 7 days */
		MySQLiQuery("delete from users_pending where signupdate < date_sub(now(), interval 7 day)", __FILE__, __LINE__);

		/* check if the username or email address is already in the users table */
		$sqlstring = "select count(*) 'count' from users where username = ? or user_email = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $email, $email);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$email, $email]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if ($row['count'] > 0) {
			return "Email address already registered";
		}

		/* the verification key is random, and only its SHA-256 is stored, so it can't be guessed or
		   read back out of the table */
		$emailkey = bin2hex(random_bytes(32));
		$keyhash = hash('sha256', $emailkey);

		/* check if the username or email address is already in the users_pending table */
		$sqlstring = "select user_id from users_pending where username = ? or user_email = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $email, $email);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$email, $email]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		$isnew = false;
		if ($row) {
			/* already signed up but not verified: send a new link. Only the key changes. The details
			   (including the password) are kept from the first signup, so someone else can't replace
			   them and have the owner of the address verify an account with a password they chose */
			$userpendingid = (int)$row['user_id'];
			$sqlstring = "update users_pending set emailkey = ?, signupdate = now() where user_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $keyhash, $userpendingid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$keyhash, $userpendingid]);
			mysqli_stmt_close($stmt);
		}
		else {
			$sqlstring = "insert into users_pending (username, password, user_instanceid, user_fullname, user_firstname, user_midname, user_lastname, user_institution, user_country, user_email, emailkey, signupdate) values (?, sha1(?), 0, '', ?, ?, ?, ?, ?, ?, ?, now())";
			$params = [$email, $password, $firstname, $midname, $lastname, $institution, $country, $email, $keyhash];
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'sssssssss', ...$params);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, ['[email]', '[password]', $firstname, $midname, $lastname, $institution, $country, '[email]', '[key]']);
			$userpendingid = (int)mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
			$isnew = true;
		}

		$url = $GLOBALS['cfg']['siteurl'] . "/v.php?k=$emailkey";
		$body = "<b>Thank you for signing up for NiDB</b><br><br>Click the link below to verify your email address (or copy and paste it into a browser). The link expires in 7 days.<br><br><a href=\"$url\">$url</a><br><br>After you verify your address, an administrator will review your account. You will be able to log in once it is approved.";
		/* send the email */
		if (!SendEmail($email, 'Verify your NiDB email address', $body, 0)) {
			if ($isnew) {
				$sqlstring = "delete from users_pending where user_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $userpendingid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userpendingid]);
				mysqli_stmt_close($stmt);
			}
			return "System error. Unable to send email!";
		}

		return "";
	}


	/* -------------------------------------------- */
	/* ------- DisplayForm ------------------------ */
	/* -------------------------------------------- */
	function DisplayForm($message, $email="", $firstname="", $midname="", $lastname="", $institution="", $country="") {
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
				
			<form method="post" action="signup.php">
			<input type="hidden" name="action" value="create">
			<?=CSRFTokenField()?>
			
			<table width="100%" height="90%">
				<tr>
					<td align="center" valign="middle">
						<table cellpadding="5" class="editor" width="60%">
							<tr>
								<td colspan="2" align="center" style="background-color: #3B5998; color: white; font-weight: bold; border-radius:5px">
									Create an NiDB account
								</td>
							</tr>
							<tr>
								<td colspan="2" align="center" style="font-size:10pt; color: #444">
								<? if ($GLOBALS['cfg']['sitetype'] == "commercial") { ?>
								<?=$GLOBALS['cfg']['sitename']?> is transitioning to an automated hosting service. In the meantime, instances must still be created manually. If you want to create a new instance, and therefore a new billing account, contact the site administrator after you create an NiDB login.
								<? } else { ?>
								Fill out the information below. You will receive an email with a link to verify your address. An administrator then reviews the account before you can log in.
								<? } ?>
								&nbsp;<small style="color: red"><?=htmlspecialchars($message)?></small>
								</td>
							</tr>
							<tr>
								<td class="label">Name</td>
								<td>
									<input type="text" name="firstname" maxlength="50" required autofocus="autofocus" value="<?=htmlspecialchars($firstname ?? '')?>" placeholder="First">&nbsp;<input type="text" name="midname" maxlength="1" style="width:20px" value="<?=htmlspecialchars($midname ?? '')?>" placeholder="M">&nbsp;<input type="text" name="lastname" maxlength="50" required value="<?=htmlspecialchars($lastname ?? '')?>" placeholder="Last">
								</td>
							</tr>
							<tr>
								<td class="label">Email</td>
								<td>
									<input type="email" name="email" maxlength="50" size="50" required value="<?=htmlspecialchars($email ?? '')?>">
								</td>
							</tr>
							<tr>
								<td class="label">Institution</td>
								<td>
									<input type="text" name="institution" maxlength="50" size="50" required value="<?=htmlspecialchars($institution ?? '')?>">
								</td>
							</tr>
							<tr>
								<td class="label">Country</td>
								<script>
									/* reselect the country after a failed submit */
									document.addEventListener('DOMContentLoaded', function() {
										var c = <?=json_encode((string)($country ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
										if (c != '') document.getElementById('country').value = c;
									});
								</script>
								<td>
									<select id="country" name="country">
									<option value="AF">Afghanistan</option>
									<option value="AX">Åland Islands</option>
									<option value="AL">Albania</option>
									<option value="DZ">Algeria</option>
									<option value="AS">American Samoa</option>
									<option value="AD">Andorra</option>
									<option value="AO">Angola</option>
									<option value="AI">Anguilla</option>
									<option value="AQ">Antarctica</option>
									<option value="AG">Antigua and Barbuda</option>
									<option value="AR">Argentina</option>
									<option value="AM">Armenia</option>
									<option value="AW">Aruba</option>
									<option value="AU">Australia</option>
									<option value="AT">Austria</option>
									<option value="AZ">Azerbaijan</option>
									<option value="BS">Bahamas</option>
									<option value="BH">Bahrain</option>
									<option value="BD">Bangladesh</option>
									<option value="BB">Barbados</option>
									<option value="BY">Belarus</option>
									<option value="BE">Belgium</option>
									<option value="BZ">Belize</option>
									<option value="BJ">Benin</option>
									<option value="BM">Bermuda</option>
									<option value="BT">Bhutan</option>
									<option value="BO">Bolivia</option>
									<option value="BA">Bosnia and Herzegovina</option>
									<option value="BW">Botswana</option>
									<option value="BV">Bouvet Island</option>
									<option value="BR">Brazil</option>
									<option value="IO">British Indian Ocean Territory</option>
									<option value="BN">Brunei Darussalam</option>
									<option value="BG">Bulgaria</option>
									<option value="BF">Burkina Faso</option>
									<option value="BI">Burundi</option>
									<option value="KH">Cambodia</option>
									<option value="CM">Cameroon</option>
									<option value="CA">Canada</option>
									<option value="CV">Cape Verde</option>
									<option value="KY">Cayman Islands</option>
									<option value="CF">Central African Republic</option>
									<option value="TD">Chad</option>
									<option value="CL">Chile</option>
									<option value="CN">China</option>
									<option value="CX">Christmas Island</option>
									<option value="CC">Cocos (Keeling) Islands</option>
									<option value="CO">Colombia</option>
									<option value="KM">Comoros</option>
									<option value="CG">Congo</option>
									<option value="CD">Congo, The Democratic Republic of The</option>
									<option value="CK">Cook Islands</option>
									<option value="CR">Costa Rica</option>
									<option value="CI">Cote D'ivoire</option>
									<option value="HR">Croatia</option>
									<option value="CU">Cuba</option>
									<option value="CY">Cyprus</option>
									<option value="CZ">Czech Republic</option>
									<option value="DK">Denmark</option>
									<option value="DJ">Djibouti</option>
									<option value="DM">Dominica</option>
									<option value="DO">Dominican Republic</option>
									<option value="EC">Ecuador</option>
									<option value="EG">Egypt</option>
									<option value="SV">El Salvador</option>
									<option value="GQ">Equatorial Guinea</option>
									<option value="ER">Eritrea</option>
									<option value="EE">Estonia</option>
									<option value="ET">Ethiopia</option>
									<option value="FK">Falkland Islands (Malvinas)</option>
									<option value="FO">Faroe Islands</option>
									<option value="FJ">Fiji</option>
									<option value="FI">Finland</option>
									<option value="FR">France</option>
									<option value="GF">French Guiana</option>
									<option value="PF">French Polynesia</option>
									<option value="TF">French Southern Territories</option>
									<option value="GA">Gabon</option>
									<option value="GM">Gambia</option>
									<option value="GE">Georgia</option>
									<option value="DE">Germany</option>
									<option value="GH">Ghana</option>
									<option value="GI">Gibraltar</option>
									<option value="GR">Greece</option>
									<option value="GL">Greenland</option>
									<option value="GD">Grenada</option>
									<option value="GP">Guadeloupe</option>
									<option value="GU">Guam</option>
									<option value="GT">Guatemala</option>
									<option value="GG">Guernsey</option>
									<option value="GN">Guinea</option>
									<option value="GW">Guinea-bissau</option>
									<option value="GY">Guyana</option>
									<option value="HT">Haiti</option>
									<option value="HM">Heard Island and Mcdonald Islands</option>
									<option value="VA">Holy See (Vatican City State)</option>
									<option value="HN">Honduras</option>
									<option value="HK">Hong Kong</option>
									<option value="HU">Hungary</option>
									<option value="IS">Iceland</option>
									<option value="IN">India</option>
									<option value="ID">Indonesia</option>
									<option value="IR">Iran, Islamic Republic of</option>
									<option value="IQ">Iraq</option>
									<option value="IE">Ireland</option>
									<option value="IM">Isle of Man</option>
									<option value="IL">Israel</option>
									<option value="IT">Italy</option>
									<option value="JM">Jamaica</option>
									<option value="JP">Japan</option>
									<option value="JE">Jersey</option>
									<option value="JO">Jordan</option>
									<option value="KZ">Kazakhstan</option>
									<option value="KE">Kenya</option>
									<option value="KI">Kiribati</option>
									<option value="KP">Korea, Democratic People's Republic of</option>
									<option value="KR">Korea, Republic of</option>
									<option value="KW">Kuwait</option>
									<option value="KG">Kyrgyzstan</option>
									<option value="LA">Lao People's Democratic Republic</option>
									<option value="LV">Latvia</option>
									<option value="LB">Lebanon</option>
									<option value="LS">Lesotho</option>
									<option value="LR">Liberia</option>
									<option value="LY">Libyan Arab Jamahiriya</option>
									<option value="LI">Liechtenstein</option>
									<option value="LT">Lithuania</option>
									<option value="LU">Luxembourg</option>
									<option value="MO">Macao</option>
									<option value="MK">Macedonia, The Former Yugoslav Republic of</option>
									<option value="MG">Madagascar</option>
									<option value="MW">Malawi</option>
									<option value="MY">Malaysia</option>
									<option value="MV">Maldives</option>
									<option value="ML">Mali</option>
									<option value="MT">Malta</option>
									<option value="MH">Marshall Islands</option>
									<option value="MQ">Martinique</option>
									<option value="MR">Mauritania</option>
									<option value="MU">Mauritius</option>
									<option value="YT">Mayotte</option>
									<option value="MX">Mexico</option>
									<option value="FM">Micronesia, Federated States of</option>
									<option value="MD">Moldova, Republic of</option>
									<option value="MC">Monaco</option>
									<option value="MN">Mongolia</option>
									<option value="ME">Montenegro</option>
									<option value="MS">Montserrat</option>
									<option value="MA">Morocco</option>
									<option value="MZ">Mozambique</option>
									<option value="MM">Myanmar</option>
									<option value="NA">Namibia</option>
									<option value="NR">Nauru</option>
									<option value="NP">Nepal</option>
									<option value="NL">Netherlands</option>
									<option value="AN">Netherlands Antilles</option>
									<option value="NC">New Caledonia</option>
									<option value="NZ">New Zealand</option>
									<option value="NI">Nicaragua</option>
									<option value="NE">Niger</option>
									<option value="NG">Nigeria</option>
									<option value="NU">Niue</option>
									<option value="NF">Norfolk Island</option>
									<option value="MP">Northern Mariana Islands</option>
									<option value="NO">Norway</option>
									<option value="OM">Oman</option>
									<option value="PK">Pakistan</option>
									<option value="PW">Palau</option>
									<option value="PS">Palestinian Territory, Occupied</option>
									<option value="PA">Panama</option>
									<option value="PG">Papua New Guinea</option>
									<option value="PY">Paraguay</option>
									<option value="PE">Peru</option>
									<option value="PH">Philippines</option>
									<option value="PN">Pitcairn</option>
									<option value="PL">Poland</option>
									<option value="PT">Portugal</option>
									<option value="PR">Puerto Rico</option>
									<option value="QA">Qatar</option>
									<option value="RE">Reunion</option>
									<option value="RO">Romania</option>
									<option value="RU">Russian Federation</option>
									<option value="RW">Rwanda</option>
									<option value="SH">Saint Helena</option>
									<option value="KN">Saint Kitts and Nevis</option>
									<option value="LC">Saint Lucia</option>
									<option value="PM">Saint Pierre and Miquelon</option>
									<option value="VC">Saint Vincent and The Grenadines</option>
									<option value="WS">Samoa</option>
									<option value="SM">San Marino</option>
									<option value="ST">Sao Tome and Principe</option>
									<option value="SA">Saudi Arabia</option>
									<option value="SN">Senegal</option>
									<option value="RS">Serbia</option>
									<option value="SC">Seychelles</option>
									<option value="SL">Sierra Leone</option>
									<option value="SG">Singapore</option>
									<option value="SK">Slovakia</option>
									<option value="SI">Slovenia</option>
									<option value="SB">Solomon Islands</option>
									<option value="SO">Somalia</option>
									<option value="ZA">South Africa</option>
									<option value="GS">South Georgia and The South Sandwich Islands</option>
									<option value="ES">Spain</option>
									<option value="LK">Sri Lanka</option>
									<option value="SD">Sudan</option>
									<option value="SR">Suriname</option>
									<option value="SJ">Svalbard and Jan Mayen</option>
									<option value="SZ">Swaziland</option>
									<option value="SE">Sweden</option>
									<option value="CH">Switzerland</option>
									<option value="SY">Syrian Arab Republic</option>
									<option value="TW">Taiwan, Province of China</option>
									<option value="TJ">Tajikistan</option>
									<option value="TZ">Tanzania, United Republic of</option>
									<option value="TH">Thailand</option>
									<option value="TL">Timor-leste</option>
									<option value="TG">Togo</option>
									<option value="TK">Tokelau</option>
									<option value="TO">Tonga</option>
									<option value="TT">Trinidad and Tobago</option>
									<option value="TN">Tunisia</option>
									<option value="TR">Turkey</option>
									<option value="TM">Turkmenistan</option>
									<option value="TC">Turks and Caicos Islands</option>
									<option value="TV">Tuvalu</option>
									<option value="UG">Uganda</option>
									<option value="UA">Ukraine</option>
									<option value="AE">United Arab Emirates</option>
									<option value="GB">United Kingdom</option>
									<option value="US">United States</option>
									<option value="UM">United States Minor Outlying Islands</option>
									<option value="UY">Uruguay</option>
									<option value="UZ">Uzbekistan</option>
									<option value="VU">Vanuatu</option>
									<option value="VE">Venezuela</option>
									<option value="VN">Viet Nam</option>
									<option value="VG">Virgin Islands, British</option>
									<option value="VI">Virgin Islands, U.S.</option>
									<option value="WF">Wallis and Futuna</option>
									<option value="EH">Western Sahara</option>
									<option value="YE">Yemen</option>
									<option value="ZM">Zambia</option>
									<option value="ZW">Zimbabwe</option>
									</select>									
								</td>
							</tr>
							<tr>
								<td class="label">Password</td>
								<td>
									<input type="password" name="password" id="password" maxlength="50" pattern=".{8,50}" required>
									<span class="tiny">Minimum 8 characters</span>
								</td>
							</tr>
							<tr>
								<td class="label">Re-enter password</td>
								<td>
									<input type="password" name="password-check" id="password-check" maxlength="50" required>
								</td>
							</tr>
							<tr>
								<td colspan="2" style="font-size:10pt; color: #444">
									<br><br>
									<b>Terms of Service</b>
									<textarea style="width:100%; height:200px"><? readfile('license.txt')?></textarea><br>
									<input type="checkbox" name="licenseagree" value="1" required>I agree to terms and conditions listed above
								</td>
							</tr>
							<tr>
								<td colspan="2" align="center">
									<img id="captcha" src="scripts/securimage/securimage_show.php" alt="CAPTCHA Image" />
									<br>
									<span class="tiny">Enter the CAPTCHA code</span>
									<input type="text" name="captcha_code" size="10" maxlength="6" />
									<a href="#" style="font-size:9pt" onclick="document.getElementById('captcha').src = 'scripts/securimage/securimage_show.php?' + Math.random(); return false"><img src="images/refresh16.png" title="Get new image"></a>
								</td>
							</tr>
							<tr>
								<td colspan="2" align="center">
									<input type="submit" value="Create Account" class="ui primary button">
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			
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
			<?=CSRFTokenField()?>

			<div align="center">
			<table style="border: 1px solid #ccc; padding:5px">
				<tr>
					<td align="center" style="color: darkred"><?=htmlspecialchars($msg)?></td>
					<td align="center" style="color: #555; font-size: 10pt">
						<img id="captcha" src="scripts/securimage/securimage_show.php" alt="CAPTCHA Image" />
						<br>
						<span class="tiny">Enter the CAPTCHA code</span>
						<input type="text" name="captcha_code" size="10" maxlength="6" />
						<a href="#" style="font-size:9pt" onclick="document.getElementById('captcha').src = 'scripts/securimage/securimage_show.php?' + Math.random(); return false"><img src="images/refresh16.png" title="Get new image"></a>
						<br>
						Enter email <input type="email" name="e" required>
						&nbsp;
						<input type="submit" value="Email me a reset link" class="ui primary button">
					</td>
				</tr>
			</table>
			</div>
			</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- RequestPasswordReset --------------- */
	/* -------------------------------------------- */
	/* emails a single-use link to choose a new password. Nothing about the account changes until the
	   link is used, so a request made by someone else can't lock the owner out. Returns "" (also when
	   no account matches, so the response doesn't reveal which addresses have accounts), or an error
	   message to show with the form */
	function RequestPasswordReset($email) {
		$email = trim($email ?? '');

		$securimage = new Securimage();
		if ($securimage->check($_POST['captcha_code'] ?? '') == false) {
			return "CAPTCHA code entered was incorrect";
		}
		if ($email == "") {
			return "Email was blank";
		}

		/* only enabled accounts whose password is stored in NiDB (Standard) can be reset. 'recent' is
		   true if a link was sent in the last 5 minutes (links last 1 hour), so repeated requests don't
		   flood the inbox */
		$sqlstring = "select user_id, user_email, (user_resetexpires is not null and user_resetexpires > date_add(now(), interval 55 minute)) 'recent' from users where (username = ? or user_email = ?) and login_type = 'Standard' and user_enabled = 1 and (user_deleted is null or user_deleted = 0)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $email, $email);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$email, $email]);
		mysqli_stmt_close($stmt);

		$accounts = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$accounts[] = $row;
		}

		foreach ($accounts as $row) {
			if ($row['recent']) { continue; }

			/* send to the address on the account, not the one typed into the form */
			$to = trim($row['user_email'] ?? '');
			if ($to == "") { continue; }

			SendPasswordResetLink((int)$row['user_id'], $to);
		}

		return "";
	}


	/* -------------------------------------------- */
	/* ------- GetResetUserID --------------------- */
	/* -------------------------------------------- */
	/* returns the user_id that a password reset token belongs to, or 0 if the token is malformed,
	   unknown, used, or expired */
	function GetResetUserID($k) {
		if ((!is_string($k)) || (!preg_match('/^[0-9a-f]{64}$/', $k))) {
			return 0;
		}
		$tokenhash = hash('sha256', $k);

		$sqlstring = "select user_id from users where user_resettoken = ? and user_resetexpires > now() and login_type = 'Standard' and user_enabled = 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $tokenhash);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, ['[token]']);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return $row ? (int)$row['user_id'] : 0;
	}


	/* -------------------------------------------- */
	/* ------- SetPasswordForm -------------------- */
	/* -------------------------------------------- */
	function SetPasswordForm($k, $msg) {
		if (GetResetUserID($k) < 1) {
			?>
			<div align="center">
			<br><br>
			<b>This password reset link is not valid</b><br><br>
			It may have expired or already been used. <a href="signup.php?a=r">Request a new link</a>
			</div>
			<?
			return;
		}
		?>
			<form method="post" action="signup.php">
			<input type="hidden" name="action" value="setpassword">
			<input type="hidden" name="k" value="<?=htmlspecialchars($k)?>">
			<?=CSRFTokenField()?>

			<div align="center">
			<table style="border: 1px solid #ccc; padding:5px" cellpadding="5">
				<tr>
					<td colspan="2" align="center"><b>Choose a new password</b><br><span style="color: darkred"><?=htmlspecialchars($msg)?></span></td>
				</tr>
				<tr>
					<td>New password</td>
					<td><input type="password" name="password" maxlength="50" pattern=".{8,50}" required autofocus> <span class="tiny">Minimum 8 characters</span></td>
				</tr>
				<tr>
					<td>Re-enter password</td>
					<td><input type="password" name="password-check" maxlength="50" required></td>
				</tr>
				<tr>
					<td colspan="2" align="center"><input type="submit" value="Change password" class="ui primary button"></td>
				</tr>
			</table>
			</div>
			</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SetPassword ------------------------ */
	/* -------------------------------------------- */
	/* sets the new password and clears the token in one statement, so a token works only once.
	   Returns "" on success, or an error message to show with the form */
	function SetPassword($k, $password, $passwordcheck) {
		$password = $password ?? '';
		$passwordcheck = $passwordcheck ?? '';

		$userid = GetResetUserID($k);
		if ($userid < 1) {
			return "This password reset link is not valid";
		}
		if (strlen($password) < 8) {
			return "Password must be at least 8 characters";
		}
		if ($password !== $passwordcheck) {
			return "Passwords do not match";
		}

		$tokenhash = hash('sha256', $k);
		$sqlstring = "update users set password = sha1(?), user_resettoken = null, user_resetexpires = null where user_id = ? and user_resettoken = ? and user_resetexpires > now()";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sis', $password, $userid, $tokenhash);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, ['[password]', $userid, '[token]']);
		$changed = mysqli_stmt_affected_rows($stmt);
		mysqli_stmt_close($stmt);

		if ($changed < 1) {
			return "This password reset link is not valid";
		}

		return "";
	}


ob_end_flush();
	
?>
