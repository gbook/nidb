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

	require_once "Mail.php";
	require_once "Mail/mime.php";
	
	/* required for CAPTCHA */
	session_start();

	$nologin = true;
 	require "functions.php";
	
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
	$link = mysqli_connect($GLOBALS['cfg']['mysqlhost'], $GLOBALS['cfg']['mysqluser'], $GLOBALS['cfg']['mysqlpassword'], $GLOBALS['cfg']['mysqldatabase']) or die ("Could not connect. Error [" . mysql_error() . "]  File [" __FILE__ "] Line [ " . __LINE__ . "]");

	
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
		$email = mysql_real_escape_string($email);
		$firstname = mysql_real_escape_string($firstname);
		$midname = mysql_real_escape_string($midname);
		$lastname = mysql_real_escape_string($lastname);
		$institution = mysql_real_escape_string($institution);
		$country = mysql_real_escape_string($country);
		$password = mysql_real_escape_string($password);
		
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
		
		/* check if the username or email address is already in the users_pending table */
		$sqlstring = "select count(*) 'count' from users_pending where username = '$email' or user_email = '$email'";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$count = $row['count'];
		//echo "Count [$count]<br>";
		if ($count > 0) {
			return "Email address already registered, but not activated";
		}

		/* if no errors were found so far, insert the row, with the user disabled */
		/* insert a temp account into the DB */
		//$sqlstring = "insert into users (username, password, login_type, user_instanceid, user_fullname, user_institution, user_country, user_email, user_enabled) values ('$email',sha1('$password'),'Standard','$instance','$name','$institution','$country','$email',0)";
		$sqlstring = "insert into users_pending (username, password, user_firstname, user_midname, user_lastname, user_institution, user_country, user_email, emailkey, signupdate) values ('$email',sha1('$password'),'$firstname','$midname','$lastname','$institution','$country','$email',sha1(now()), now())";
		//echo "$sqlstring<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$rowid = mysql_insert_id();
		
		/* get the generated SHA1 hash */
		$sqlstring = "select emailkey from users_pending where user_id = $rowid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$emailkey = $row['emailkey'];
		
		$body = "<b>Thank for you signing up for NiDB</b><br><br>Click the link below to activate your account (or copy and paste into a browser)\n" . $GLOBALS['cfg']['siteurl'] . "/v.php?k=$emailkey";
		/* send the email */
		if (!SendGmail($email,'Acitvate your NiDB account',$body, 0)) {
			return "System error. Unable to send email!";
			$sqlstring = "delete from users_pending where user_id = $rowid";
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
				
			<form method="post" action="signup.php">
			<input type="hidden" name="action" value="create">
			
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
								Fill out the information below. You will receive an email with a link to activate your account
								<? } ?>
								&nbsp;<small style="color: red"><?=$message?></small>
								</td>
							</tr>
							<tr>
								<td class="label">Name</td>
								<td>
									<input type="text" name="firstname" maxlength="50" required autofocus="autofocus" value="<?=$name?>" placeholder="First">&nbsp;<input type="text" name="midname" maxlength="1" style="width:20px" value="<?=$name?>" placeholder="M">&nbsp;<input type="text" name="lastname" maxlength="50" required value="<?=$name?>" placeholder="Last">
								</td>
							</tr>
							<tr>
								<td class="label">Email</td>
								<td>
									<input type="email" name="email" maxlength="50" size="50" required value="<?=$email?>">
								</td>
							</tr>
							<tr>
								<td class="label">Institution</td>
								<td>
									<input type="text" name="institution" maxlength="50" size="50" required value="<?=$institution?>">
								</td>
							</tr>
							<tr>
								<td class="label">Country</td>
								<script>
									$(document).ready(function() {
										$("#country option[value='<?=$country?>']").attr("selected","selected");
									});
								</script>
								<td>
									<!--<input type="text" name="country" maxlength="50" required value="<?=$country?>">-->
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
									<input type="submit" value="Create Account">
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
						<input type="submit" value="Reset password">
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
		$email = mysql_real_escape_string($email);

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
