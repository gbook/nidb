<?
 // ------------------------------------------------------------------------------
 // NiDB users.php
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

	session_start();
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - User Options</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* setup variables */
	$action = GetSafeAction(array("saveoptions", "joininstance", "regenerateapikey")); /* mutating actions require POST + CSRF token */
	$password = GetVariable("password") ?? '';
	$c['firstname'] = GetVariable("firstname");
	$c['midname'] = GetVariable("midname");
	$c['lastname'] = GetVariable("lastname");
	$c['email1'] = GetVariable("email1");
	$c['email2'] = GetVariable("email2");
	$c['phone1'] = GetVariable("phone1");
	$c['phone2'] = GetVariable("phone2");
	$c['address1'] = GetVariable("address1");
	$c['address2'] = GetVariable("address2");
	$c['city'] = GetVariable("city");
	$c['state'] = GetVariable("state");
	$c['zip'] = GetVariable("zip");
	$c['country'] = GetVariable("country");
	$c['institution'] = GetVariable("institution");
	$c['dept'] = GetVariable("dept");
	$c['website'] = GetVariable("website");

	$sendmail_dailysummary = GetVariable("sendmail_dailysummary");
	$enablebeta = GetVariable("enablebeta");
	$instanceid = (int)GetVariable("instanceid");
	$notifications = GetVariable("notification");
	if (!is_array($notifications)) $notifications = array();
	$projectids = GetVariables("projectid");
	if (!is_array($projectids)) $projectids = array();

	switch ($action) {
		/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
		   then redirect to a GET so a refresh/Back doesn't re-submit the form */
		case 'saveoptions':
			ob_start();
			SaveOptions($username, $password, $c, $sendmail_dailysummary, $enablebeta, $notifications, $projectids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("users.php");
			break;
		case 'joininstance':
			ob_start();
			SendJoinInstanceRequest($username, $instanceid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("users.php");
			break;
		case 'regenerateapikey':
			ob_start();
			RegenerateApiKey($username);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("users.php");
			break;
		default:
			DisplayOptions($username);
	}


	/* ----------------------------------------------- */
	/* --------- GetUserRow -------------------------- */
	/* ----------------------------------------------- */
	/* returns the users row for $username, or null */
	function GetUserRow($username) {
		if (trim($username ?? '') == "") { return null; }

		$sqlstring = "select * from users where username = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $username);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) < 1) { return null; }

		return mysqli_fetch_array($result, MYSQLI_ASSOC);
	}


	/* ----------------------------------------------- */
	/* --------- SaveOptions ------------------------- */
	/* ----------------------------------------------- */
	function SaveOptions($username, $password, $c, $sendmail_dailysummary, $enablebeta, $notifications, $projectids) {
		if (IsGuest($username)) { return; }
		if (trim($username) == "") { return; }

		foreach ($c as $key => $val) {
			$c[$key] = trim($val ?? '');
		}

		$sendmail_dailysummary = ($sendmail_dailysummary == "1") ? 1 : 0;
		$enablebeta = ($enablebeta == "1") ? 1 : 0;

		$row = GetUserRow($username);
		if ($row === null) {
			Error("Unable to find your user account");
			return;
		}
		$userid = (int)$row['user_id'];
		if ($userid == 0) {
			Error("Unable to find your user account");
			return;
		}

		/* update the account information */
		$sqlstring = "update users set user_firstname = ?, user_midname = ?, user_lastname = ?, user_email = ?, user_email2 = ?, user_phone1 = ?, user_phone2 = ?, user_address1 = ?, user_address2 = ?, user_city = ?, user_state = ?, user_zip = ?, user_country = ?, user_institution = ?, user_dept = ?, user_website = ?, sendmail_dailysummary = ?, user_enablebeta = ? where user_id = ?";
		$params = [$c['firstname'], $c['midname'], $c['lastname'], $c['email1'], $c['email2'], $c['phone1'], $c['phone2'], $c['address1'], $c['address2'], $c['city'], $c['state'], $c['zip'], $c['country'], $c['institution'], $c['dept'], $c['website'], $sendmail_dailysummary, $enablebeta, $userid];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssssssssssssssiii', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		/* update the password, only for local (Standard) accounts. A NIS account
		   must not get a local password, which would keep working after the NIS account is removed */
		if ($password != "") {
			if ($row['login_type'] == "Standard") {
				$sqlstring = "update users set password = sha1(?) where user_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'si', $password, $userid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, ['********', $userid]);
				mysqli_stmt_close($stmt);
			}
			else {
				Error("Password not changed. This account logs in through the system (NIS/Unix) password, which can't be changed here.");
			}
		}

		/* delete all existing notification entries */
		$sqlstring = "delete from notification_user where user_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $userid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid]);
		mysqli_stmt_close($stmt);

		/* update the notifications */
		$sqlstring = "insert ignore into notification_user (user_id, project_id, notiftype_id) values (?, ?, ?)";
		foreach ($notifications as $notificationid) {
			$notificationid = (int)$notificationid;
			if ($notificationid <= 0) { continue; }

			$pids = $projectids[$notificationid] ?? array();
			if (!is_array($pids)) { $pids = array($pids); }

			/* no projects selected, or 'All Projects' selected -> project 0 (all) */
			if ((count($pids) == 0) || in_array('0', $pids, true) || in_array(0, $pids, true) || in_array('all', $pids, true)) {
				$pids = array(0);
			}

			foreach ($pids as $projectid) {
				$projectid = (int)$projectid;
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'iii', $userid, $projectid, $notificationid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid, $projectid, $notificationid]);
				mysqli_stmt_close($stmt);
			}
		}

		Notice("Account options saved");
	}


	/* ----------------------------------------------- */
	/* --------- SendJoinInstanceRequest ------------- */
	/* ----------------------------------------------- */
	function SendJoinInstanceRequest($username, $instanceid) {
		$row = GetUserRow($username);
		$userid = (int)($row['user_id'] ?? 0);
		if ($userid == 0) {
			Error("Unable to find your user account");
			return;
		}

		/* get the instance and its owner */
		$sqlstring = "select a.instance_name, b.user_email, b.user_fullname from instance a left join users b on a.instance_ownerid = b.user_id where a.instance_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $instanceid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instanceid]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) < 1) {
			Error("Instance not found");
			return;
		}
		$irow = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instancename = $irow['instance_name'];
		$oEmail = $irow['user_email'] ?? '';
		$oFullname = $irow['user_fullname'] ?? '';

		$sqlstring = "select instance_joinrequest from user_instance where user_id = ? and instance_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $userid, $instanceid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid, $instanceid]);
		mysqli_stmt_close($stmt);
		$sendemail = 0;
		if (mysqli_num_rows($result) < 1) {
			$sqlstring = "insert into user_instance (user_id, instance_id, instance_joinrequest) values (?, ?, 1)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $userid, $instanceid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid, $instanceid]);
			mysqli_stmt_close($stmt);
			$sendemail = 1;
		}
		else {
			$jrow = mysqli_fetch_array($result, MYSQLI_ASSOC);
			if ($jrow['instance_joinrequest']) {
				$sendemail = 1;
			}
		}

		if ($sendemail) {
			/* send an email to the owner of the instance */
			$uEmail = $row['user_email'];
			$uFullname = $row['user_fullname'];

			$body = htmlspecialchars($oFullname) . ",<br><br><b>" . htmlspecialchars($uFullname) . " (" . htmlspecialchars($uEmail) . ")</b> has requested to join your Neuroinformatics Database (NiDB) instance: <b>" . htmlspecialchars($instancename) . "</b><br><br>To accept or reject this request, login to NiDB and go to Admin->Instances and click Accept or Reject";

			if (($oEmail == '') || (!SendEmail($oEmail,'NiDB instance join request',$body,0))) {
				Error("Request recorded, but unable to email the instance owner");
			}
			else {
				Notice("Request sent");
			}
		}
	}


	/* ----------------------------------------------- */
	/* --------- RegenerateApiKey -------------------- */
	/* ----------------------------------------------- */
	function RegenerateApiKey($username) {
		$sqlstring = "select apiuser_id from api_users where username = ? limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $username);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) == 0) {
			Error("No API access configured for this account");
			return;
		}
		$apiKey = bin2hex(random_bytes(32));
		$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : (defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_BCRYPT);
		$hash = password_hash($apiKey, $algo);
		$sqlstring = "update api_users set credential = ? where username = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $hash, $username);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$hash, $username]);
		mysqli_stmt_close($stmt);
		Notice("New API key (save this — it will not be shown again): <tt>$apiKey</tt>");
	}


	/* ----------------------------------------------- */
	/* --------- DisplayOptions ---------------------- */
	/* ----------------------------------------------- */
	function DisplayOptions($username) {

		if (IsGuest($username)) { return; }
		if (trim($username) == '') {
			?>
			<span class="staticmessage">username is blank</span>
			<?
			return;
		}

		$row = GetUserRow($username);
		if ($row === null) {
			Error("Unable to find your user account");
			return;
		}
		$userid = (int)$row['user_id'];

		/* every value below is displayed in HTML, so escape it once here */
		$h = function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); };
		$firstname = $h($row['user_firstname']);
		$midname = $h($row['user_midname']);
		$lastname = $h($row['user_lastname']);
		$institution = $h($row['user_institution']);
		$country = $h($row['user_country']);
		$email1 = $h($row['user_email']);
		$email2 = $h($row['user_email2']);
		$address1 = $h($row['user_address1']);
		$address2 = $h($row['user_address2']);
		$city = $h($row['user_city']);
		$state = $h($row['user_state']);
		$zip = $h($row['user_zip']);
		$phone1 = $h($row['user_phone1']);
		$phone2 = $h($row['user_phone2']);
		$website = $h($row['user_website']);
		$dept = $h($row['user_dept']);

		$login_type = $row['login_type'];
		$dailycheck = ($row['sendmail_dailysummary'] == "1") ? "checked" : "";
		$enablebeta = ($row['user_enablebeta'] == "1") ? "checked" : "";

		$sqlstring = "select apiuser_id from api_users where username = ? limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $username);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
		mysqli_stmt_close($stmt);
		$hasApiAccess = (mysqli_num_rows($result) > 0);

		$sqlstring = "select count(*) 'count' from user_project where user_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $userid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid]);
		mysqli_stmt_close($stmt);
		$prow = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectcount = (int)$prow['count'];

		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
		?>
		<span class="tiny">I have permissions to <?=$projectcount?> projects!<br><br></span>
		<script type="text/javascript">
			$(document).ready(function() {
				/* check the matching passwords */
				$("#accountform").submit(function(){
					$(".error").hide();
					var hasError = false;
					var passwordVal = $("#password").val();
					var checkVal = $("#password-check").val();

					if (passwordVal != checkVal ) {
						$("#password-check").after('<span class="error">Passwords do not match.</span>');
						hasError = true;
					}
					if(hasError == true) {return false;}
				});
			});
		</script>


		<div class="ui text container">
			<div class="ui top attached segment">
				<div class="ui two column grid">
					<div class="column">
						<h2 class="ui header">My Account</h2>
					</div>
					<div class="right aligned column">
						<? if ($hasApiAccess) { ?>
						<form method="post" action="users.php" style="float:right; margin:0">
							<input type="hidden" name="action" value="regenerateapikey">
							<?=CSRFTokenField()?>
							<button type="submit" class="ui basic green button">Regenerate API Key</button>
						</form>
						<? } ?>
					</div>
				</div>
			</div>

			<form method="post" action="users.php" autocomplete="off" class="ui form attached fluid segment" id="accountform">
			<input type="hidden" name="action" value="saveoptions">
			<?=CSRFTokenField()?>

			<h3 class="ui header">Information</h3>
			
			<div class="three fields">
				<div class="required seven wide field">
					<label>First</label>
					<input type="text" name="firstname" id="firstname" maxlength="50" required autofocus="autofocus" value="<?=$firstname?>" placeholder="First">
				</div>
				<div class="two wide field">
					<label>Middle</label>
					<input type="text" name="midname" id="midname" maxlength="1" value="<?=$midname?>">
				</div>
				<div class="required seven wide field">
					<label>Last</label>
					<input type="text" name="lastname" id="lastname" maxlength="50" required value="<?=$lastname?>">
				</div>
			</div>

			<div class="two fields">
				<div class="required field">
					<label>Email</label>
					<input type="text" name="email1" value="<?=$email1?>" placeholder="primary email" class="required">
				</div>
				<div class="field">
					<label>Secondary email</label>
					<input type="text" name="email2" value="<?=$email2?>" placeholder="secondary email">
				</div>
			</div>
			
			<div class="two fields">
				<div class="field">
					<label>Phone</label>
					<div class="field">
						<input type="text" name="phone1" value="<?=$phone1?>">
					</div>
				</div>
				<div class="field">
					<label>Alternate phone</label>
					<div class="field">
						<input type="text" name="phone2" value="<?=$phone2?>">
					</div>
				</div>
			</div>

			<div class="field">
				<label>Address</label>
				<div class="field">
					<input type="text" name="address1" value="<?=$address1?>" placeholder="Address line 1"><br>
					<input type="text" name="address2" value="<?=$address2?>" placeholder="Address line 2">
				</div>
			</div>
			
			<div class="three fields">
				<div class="seven wide field">
					<label>City</label>
					<input type="text" name="city" value="<?=$city?>">
				</div>
				<script>
					$(document).ready(function() {
						$("#state").val(<?=json_encode($row['user_state'] ?? '', JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>);
					});
				</script>
				<div class="seven wide field">
					<label>State</label>
					<select name="state" id="state">
						<option value="">(Select state)</option>
						<option value="AL">Alabama</option>
						<option value="AK">Alaska</option>
						<option value="AZ">Arizona</option>
						<option value="AR">Arkansas</option>
						<option value="CA">California</option>
						<option value="CO">Colorado</option>
						<option value="CT">Connecticut</option>
						<option value="DE">Delaware</option>
						<option value="DC">District Of Columbia</option>
						<option value="FL">Florida</option>
						<option value="GA">Georgia</option>
						<option value="HI">Hawaii</option>
						<option value="ID">Idaho</option>
						<option value="IL">Illinois</option>
						<option value="IN">Indiana</option>
						<option value="IA">Iowa</option>
						<option value="KS">Kansas</option>
						<option value="KY">Kentucky</option>
						<option value="LA">Louisiana</option>
						<option value="ME">Maine</option>
						<option value="MD">Maryland</option>
						<option value="MA">Massachusetts</option>
						<option value="MI">Michigan</option>
						<option value="MN">Minnesota</option>
						<option value="MS">Mississippi</option>
						<option value="MO">Missouri</option>
						<option value="MT">Montana</option>
						<option value="NE">Nebraska</option>
						<option value="NV">Nevada</option>
						<option value="NH">New Hampshire</option>
						<option value="NJ">New Jersey</option>
						<option value="NM">New Mexico</option>
						<option value="NY">New York</option>
						<option value="NC">North Carolina</option>
						<option value="ND">North Dakota</option>
						<option value="OH">Ohio</option>
						<option value="OK">Oklahoma</option>
						<option value="OR">Oregon</option>
						<option value="PA">Pennsylvania</option>
						<option value="RI">Rhode Island</option>
						<option value="SC">South Carolina</option>
						<option value="SD">South Dakota</option>
						<option value="TN">Tennessee</option>
						<option value="TX">Texas</option>
						<option value="UT">Utah</option>
						<option value="VT">Vermont</option>
						<option value="VA">Virginia</option>
						<option value="WA">Washington</option>
						<option value="WV">West Virginia</option>
						<option value="WI">Wisconsin</option>
						<option value="WY">Wyoming</option>
						<option value="AS">American Samoa</option>
						<option value="GU">Guam</option>
						<option value="MP">Northern Mariana Islands</option>
						<option value="PR">Puerto Rico</option>
						<option value="UM">United States Minor Outlying Islands</option>
						<option value="VI">Virgin Islands</option>
					</select>
				</div>
				<div class="two wide field">
					<label>Zip</label>
					<input type="text" name="zip" value="<?=$zip?>">
				</div>
			</div>

			<script>
				$(document).ready(function() {
					$("#country").val(<?=json_encode($row['user_country'] ?? '', JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>);
				});
			</script>
			<div class="field">
				<label>Country</label>
				<div class="field">
					<select id="country" name="country">
						<option value="">(Select country)</option>
						<option value="AF">Afghanistan</option>
						<option value="AX">�land Islands</option>
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
				</div>
			</div>

			<div class="field">
				<label>Institution</label>
				<div class="field">
					<input type="text" name="institution" value="<?=$institution?>">
				</div>
			</div>
			
			<div class="field">
				<label>Department</label>
				<div class="field">
					<input type="text" name="dept" value="<?=$dept?>">
				</div>
			</div>
			
			<div class="field">
				<label>Website</label>
				<div class="field">
					<input type="text" name="website" value="<?=$website?>">
				</div>
			</div>
			
			<? if ($login_type == "Standard") { ?>
			<div class="ui grey segment">
				<h3 class="ui header">Password</h3>
				<span class="tiny">Only enter password if you want to change it</span>
				<div class="two fields">
					<div class="field">
						<label>Password</label>
						<div class="field">
							<input type="password" name="password" id="password" autocomplete="new-password">
						</div>
					</div>
					
					<div class="field">
						<label>Re-enter password</label>
						<div class="field">
							<input type="password" name="password-check" id="password-check" autocomplete="new-password">
						</div>
					</div>
				</div>
			</div>
			<? } ?>

			<div class="ui grey segment">
				<h3 class="ui header">Options</h3>
				<div class="field">
					<label>Project notifications</label>
					<div class="field">
						<table>
						<?
							$sqlstring  = "select * from notifications";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($nrow = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$notificationid = (int)$nrow['notiftype_id'];
								$notificationname = $nrow['notiftype_name'];
								$notificationdesc = $nrow['notiftype_desc'];
								$notificationneedproject = $nrow['notiftype_needproject'];
								
								/* reset for each notification, so one notification's projects don't carry over to the next */
								$projectids = array();
								$sqlstringA = "select project_id from notification_user where user_id = ? and notiftype_id = ?";
								$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
								mysqli_stmt_bind_param($stmtA, 'ii', $userid, $notificationid);
								$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$userid, $notificationid]);
								mysqli_stmt_close($stmtA);
								if (mysqli_num_rows($resultA) > 0) {
									$notifenabled = "checked";
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$projectids[] = $rowA['project_id'];
									}
								}
								else {
									$notifenabled = "";
								}
								?>
								<tr>
									<td valign="top">
										<div title="<?=$h($notificationdesc)?>"><input type="checkbox" <?=$notifenabled?> name="notification[]" value="<?=$notificationid?>"> <b><?=$h($notificationname)?></b></div>
									</td>
									<? if ($notificationneedproject == 1) { ?>
									<td valign="top"><? DisplayProjectSelectBox(0,"projectid-$notificationid"."[]",'','',1,$projectids); ?></td>
									<? } else {?>
									<td></td>
									<? } ?>
								</tr>
								<?
							}
						?>
						</table>
					</div>
				</div>
				<div class="field">
					<label>Daily MR summary</label>
					<div class="field">
						<input type="checkbox" name="sendmail_dailysummary" <?=$dailycheck?> value="1">
					</div>
				</div>
				
				<div class="field">
					<label>Enable beta features</label>
					<div class="field">
						<input type="checkbox" name="enablebeta" <?=$enablebeta?> value="1">
					</div>
				</div>
			</div>

			<div class="column" align="right">
				<button class="ui primary button" type="submit">Save</button>
			</div>
		</form>
		<br><br>
		
		<table class="ui celled selectable grey compact table" width="50%">
			<thead>
				<tr>
					<th colspan="2">Instances</th>
				</tr>
			</thead>
			<tbody>
			<?
				/* this user's membership/join-request status for each instance */
				$sqlstring = "select a.instance_id, a.instance_name, a.instance_ownerid, b.instance_joinrequest, b.user_id 'member' from instance a left join user_instance b on a.instance_id = b.instance_id and b.user_id = ? order by a.instance_name";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $userid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid]);
				mysqli_stmt_close($stmt);
				while ($irow = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$instanceid = (int)$irow['instance_id'];
					$name = $h($irow['instance_name']);
					$owner = $irow['instance_ownerid'];
					
					if ($owner == $userid) {
						?><tr><td><a href="instance.php?instanceid=<?=$instanceid?>"><?=$name?></a></td><?
					} else {
						?><tr><td><?=$name?></td><?
					}
					
					if ($irow['member'] !== null) {
						if ($irow['instance_joinrequest']) {
							?><td style="color: #888">Request Sent</td></tr><?
						}
						else {
							?><td style="color: #888">Joined</td></tr><?
						}
					}
					else {
						?>
						<td>
							<form method="post" action="users.php" style="margin:0">
								<input type="hidden" name="action" value="joininstance">
								<input type="hidden" name="instanceid" value="<?=$instanceid?>">
								<?=CSRFTokenField()?>
								<button type="submit" class="ui mini basic button">Request to Join</button>
							</form>
						</td></tr>
						<?
					}
				}
			?>
				<? if ($GLOBALS['cfg']['sitetype'] == "commercial") { ?>
				<tr><td colspan="2"><b>Create a new instance</b> - An instance can contain any number of projects, and you can control user access to the projects. When you create a new instance, you will need to specify a technical and billing contact and provide a copy of an IRB approval letter for your projects. You will be billed monthly for all usage until you cancel your account. To create a new instance contact the site administator.</td></tr>
				<? } ?>
			</tbody>
		</table>
		</div>
		<?
	}
	
	
	/* ----------------------------------------------- */
	/* --------- IsGuest ----------------------------- */
	/* ----------------------------------------------- */
	function IsGuest($username) {
		if (trim($username) == "") { return false; }
		
		$row = GetUserRow($username);
		$type = $row['login_type'] ?? '';
		
		if ($type == "Guest") {
			return true;
		}
		else {
			return false;
		}
	}

?>

<? include("footer.php") ?>
