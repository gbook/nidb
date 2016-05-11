<?
 // ------------------------------------------------------------------------------
 // NiDB users.php
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
	session_start();
	
	require_once "Mail.php";
	require_once "Mail/mime.php";
	
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
	require "includes.php";
	require "menu.php";
	
	//PrintVariable($_POST,'post');
	//exit(0);
	
	/* setup variables */
	$action = GetVariable("action");
	$password = GetVariable("password");
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
	$id = GetVariable("id");
	$instanceid = GetVariable("instanceid");
	$notifications = GetVariable("notification");
	$projectids = GetVariables("projectid");
	//PrintVariable($projectids);
	//$protocol = GetVariable("protocol");
	//$variable = GetVariable("variable");
	//$criteria = GetVariable("criteria");
	//$value = GetVariable("value");

	/* ----- determine which action to take ----- */
	switch ($action) {
		case 'saveoptions':
			SaveOptions($username, $password, $c, $sendmail_dailysummary, $enablebeta, $notifications, $projectids);
			DisplayOptions($username);
			break;
		//case 'addnot':
		//	AddNotification($username, $protocol, $variable, $criteria, $value);
		//	DisplayOptions($username);
		//	break;
		case 'deletenot':
			DeleteNotification($id);
			DisplayOptions($username);
			break;
		case 'joininstance':
			SendJoinInstanceRequest($instanceid);
			DisplayOptions($username);
			break;
		case 'menu':
		default:
			DisplayOptions($username);
	}


	/* ----------------------------------------------- */
	/* --------- SaveOptions ------------------------- */
	/* ----------------------------------------------- */
	function SaveOptions($username, $password, $c, $sendmail_dailysummary, $enablebeta, $notifications, $projectids) {
		if (IsGuest($username)) { return; }
		
		foreach ($c as $key => $val) {
			$c[$key] = mysql_real_escape_string($val);
		}
		
		$sqlstring = "select * from users where username = '$username'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$userid = $row['user_id'];
			/* update */
			$sqlstring = "update users set";
			if ($password != "") { $sqlstring .= " password = sha1('$password'), "; }
			$sqlstring .= " user_firstname = '".$c['firstname']."', user_midname = '".$c['midname']."', user_lastname = '".$c['lastname']."', user_email = '".$c['email1']."', user_email2 = '".$c['email2']."', user_phone1 = '".$c['phone1']."', user_phone2 = '".$c['phone2']."', user_address1 = '".$c['address1']."', user_address2 = '".$c['address2']."', user_city = '".$c['city']."', user_state = '".$c['state']."', user_zip = '".$c['zip']."', user_country = '".$c['country']."', user_institution = '".$c['institution']."', user_dept = '".$c['dept']."', user_website = '".$c['website']."', sendmail_dailysummary = '$sendmail_dailysummary', user_enablebeta = '$enablebeta' where username = '$username'";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			
			/* delete all existing notification entries */
			$sqlstring = "delete from notification_user where user_id = $userid";
			PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			
			/* update the notifications */
			foreach ($notifications as $notificationid) {
				$pids = $projectids[$notificationid];
				foreach ($pids as $projectid) {
					if ($projectid == "all") { $projectid = 0; }
					
					$sqlstring = "insert into notification_user (user_id, project_id, notiftype_id) values ($userid, $projectid, $notificationid)";
					//PrintSQL($sqlstring);
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		else {
			?>
			<span class="message">How did you get this far? You should already have a row in the user table from when you first logged in.</span>
			<?
		}
	}


	/* ----------------------------------------------- */
	/* --------- AddNotification --------------------- */
	/* ----------------------------------------------- */
/* 	function AddNotification($username, $protocol, $variable, $criteria, $value) {
		if (IsGuest($username)) { return; }

		$sqlstring = "select user_id from users where username = '$username'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if (mysql_num_rows($result) > 0) {
			$id = $row['user_id'];
			
			$sqlstring = "insert into notifications (user_id, notif_type, notif_protocol, notif_snrvalue, notif_snrcriteria, notif_snrvariable) values ('$id', 'snr', '$protocol', '$value', '$criteria', '$variable')";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			?>
			<span class="message">Added notification. Notifications are sent daily IF the SNR meets your criteria.</span>
			<?
		}
		else {
			?>
			<span class="message">User "<?=$username?>" does not exist in the database. Please contact the NIDB administrator</span>
			<?
		}
	}
 */

	/* ----------------------------------------------- */
	/* --------- DeleteNotification ------------------ */
	/* ----------------------------------------------- */
	//function DeleteNotification($id) {
	//	if (IsGuest($username)) { return; }
	//	
	//	$sqlstring = "delete from notifications where id = $id";
	//	$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
	//	?><span class="message">Notification deleted</span><?
	//}


	/* ----------------------------------------------- */
	/* --------- SendJoinInstanceRequest ------------- */
	/* ----------------------------------------------- */
	function SendJoinInstanceRequest($instanceid) {
		$sqlstring = "select * from user_instance where user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "') and instance_id = $instanceid";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$sendemail = 0;
		if (mysql_num_rows($result) < 1) {
			$sqlstringA = "insert into user_instance (user_id, instance_id, instance_joinrequest) values ((select user_id from users where username = '" . $GLOBALS['username'] . "'), $instanceid, 1)";
			//PrintSQL($sqlstringA);
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			$sendemail = 1;
		}
		else {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$joinrequest = $row['instance_joinrequest'];
			if ($joinrequest) {
				$sendemail = 1;
			}
		}
		
		if ($sendemail) {
			/* send an email to the owner of the instance */
			$sqlstring = "select * from users where user_id = (select instance_ownerid from instance where instance_id = $instanceid)";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$oEmail = $row['user_email'];
			$oFullname = $row['user_fullname'];
			
			/* get the user in-question's information */
			$sqlstring = "select * from users where user_id = (select instance_ownerid from instance where instance_id = $instanceid)";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$uEmail = $row['user_email'];
			$uFullname = $row['user_fullname'];

			/* get the instance information */
			$sqlstring = "select * from instance where instance_id = $instanceid";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$instancename = $row['instance_name'];
			
			$body = "$oFullname,<br><br><b>$uFullname ($uEmail)</b> has requested to join your Neuroinformatics Database (NiDB) instance: <b>$instancename</b>\n\nTo accept or reject this request, login to NiDB and go to Admin->Instances and click Accept or Reject";
			
			if (!SendGmail($uEmail,'NiDB instance join request',$body,0)) {
				echo "System error. Unable to send email!";
			}
			else {
			}
			
			?><span class="message">Request sent</span><?
		}
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayOptions ---------------------- */
	/* ----------------------------------------------- */
	function DisplayOptions($username) {

		if (IsGuest($username)) { return; }
		
		/* get the range of years that studies have occured */
		$sqlstring = "select * from users where username = '$username'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$userid = $row['user_id'];
		$password = $row['password'];
		$email = $row['user_email'];
		$fullname = $row['user_fullname'];
		
		$firstname = $row['user_firstname'];
		$midname = $row['user_midname'];
		$lastname = $row['user_lastname'];
		$institution = $row['user_institution'];
		$country = $row['user_country'];
		$email1 = $row['user_email'];
		$email2 = $row['user_email2'];
		$address1 = $row['user_address1'];
		$address2 = $row['user_address2'];
		$city = $row['user_city'];
		$state = $row['user_state'];
		$zip = $row['user_zip'];
		$phone1 = $row['user_phone1'];
		$phone2 = $row['user_phone2'];
		$website = $row['user_website'];
		$dept = $row['user_dept'];
		
		$login_type = $row['login_type'];
		if ($row['sendmail_dailysummary'] == "1") { $dailycheck = "checked"; }
		if ($row['user_enablebeta'] == "1") { $enablebeta = "checked"; }

		//PrintVariable($row,'row');
		?>
		<div align="center">
		<script type="text/javascript">
			$(document).ready(function() {
				/* check the matching passwords */
				$("#submit").click(function(){
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

				/* to disable the autofill thing in Chrome */
				if ($.browser.webkit) {
					$('input[name="username"]').attr('autocomplete', 'off');
					$('input[name="fullname"]').attr('autocomplete', 'off');
					$('input[name="password"]').attr('autocomplete', 'off');
				}
			});
		</script>

		<form name="userform" action="users.php" method="post" autocomplete="off">
		<input type="hidden" name="action" value="saveoptions">
		<table class="bluedisplaytable" width="50%">
			<thead>
				<tr>
					<th>User options for <?=$username?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="main">
						<table class="editor">
							<tr>
								<td class="label">Name</td>
								<td class="value">
									<input type="text" name="firstname" id="firstname" maxlength="50" required autofocus="autofocus" value="<?=$firstname?>" placeholder="First" class="required">&nbsp;<input type="text" name="midname" id="midname" maxlength="1" style="width:20px" required value="<?=$midname?>" placeholder="M">&nbsp;<input type="text" name="lastname" id="lastname" maxlength="50" required value="<?=$lastname?>" placeholder="Last"class="required">
								</td>
							</tr>
							<tr>
								<td class="label">Email</td>
								<td class="value">
									<input type="text" name="email1" value="<?=$email1?>" size="50" placeholder="primary email" class="required"><br>
									<input type="text" name="email2" value="<?=$email2?>" size="50" placeholder="secondary email">
								</td>
							</tr>
							<tr>
								<td class="label">Phone</td>
								<td class="value">
									<input type="text" name="phone1" value="<?=$phone1?>" size="40"><br>
									<input type="text" name="phone2" value="<?=$phone2?>" size="40">
								</td>
							</tr>
							<tr>
								<td class="label">Address</td>
								<td class="value">
									<input type="text" name="address1" value="<?=$address1?>" size="50"><br>
									<input type="text" name="address2" value="<?=$address2?>" size="50">
								</td>
							</tr>
							<tr>
								<td class="label">City</td>
								<td class="value">
									<input type="text" name="city" value="<?=$city?>" size="50">
								</td>
							</tr>
							<tr>
								<td class="label">State</td>
								<script>
									$(document).ready(function() {
										$("#state option[value='<?=$state?>']").attr("selected","selected");
									});
								</script>
								<td class="value">
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
								</td>
							</tr>
							<tr>
								<td class="label">Zip</td>
								<td class="value">
									<input type="text" name="zip" value="<?=$zip?>" size="10">
								</td>
							</tr>
							<tr>
								<td class="label">Country</td>
								<script>
									$(document).ready(function() {
										$("#country option[value='<?=$country?>']").attr("selected","selected");
									});
								</script>
								<td class="value">
									<select id="country" name="country">
									<option value="">(Select country)</option>
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
								<td class="label">Institution</td>
								<td class="value">
									<input type="text" name="institution" value="<?=$institution?>" size="50">
								</td>
							</tr>
							<tr>
								<td class="label">Department</td>
								<td class="value">
									<input type="text" name="dept" value="<?=$dept?>" size="50">
								</td>
							</tr>
							<tr>
								<td class="label">Website</td>
								<td class="value">
									<input type="text" name="website" value="<?=$website?>" size="50">
								</td>
							</tr>
							<? if ($login_type == 'Standard') { ?>
							<tr>
								<td class="label"><br>Password</td>
								<td><br><input type="password" name="password" id="password" autocomplete="off" value=""><span class="tiny"> leave blank to not change password</span></td>
							</tr>
							<tr>
								<td class="label">Re-enter Password<br><br></td>
								<td><input type="password" name="password-check" id="password-check" autocomplete="off" value=""><br><br></td>
							</tr>
							<? } ?>
							<tr>
								<td class="label">Notifications</td>
								<td>
									<table>
									<?
										$sqlstring  = "select * from notifications";
										$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
										while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
											//$userid = $row['user_id'];
											$notificationid = $row['notiftype_id'];
											$notificationname = $row['notiftype_name'];
											$notificationdesc = $row['notiftype_desc'];
											$projectid = $row['project_id'];
											$frequency = $row['notiftype_frequency'];
											
											$sqlstringA = "select * from notification_user where user_id = $userid and notiftype_id = $notificationid";
											$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
											if (mysql_num_rows($resultA) > 0) {
												$notifenabled = "checked";
												unset($projectids);
												while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
													$projectids[] = $rowA['project_id'];
												}
												//PrintVariable($projectids);
											}
											else {
												$notifenabled = "";
											}
											?>
											<tr>
												<td valign="top">
													<div title="<?=$notificationdesc?>"><input type="checkbox" <?=$notifenabled?> name="notification[]" value="<?=$notificationid?>"> <b><?=$notificationname?></b></div>
												</td>
												<td valign="top"><? DisplayProjectSelectBox(0,"projectid-$notificationid"."[]",'',1,$projectids); ?></td>
											</tr>
											<?
										}
									?>
									</table>
								</td>
							</tr>
							<tr>
								<td class="label"><input type="checkbox" name="sendmail_dailysummary" <?=$dailycheck?> value="1"></td>
								<td class="value">Send daily summary email of NiDB activity
								<br><span class="sublabel">Includes MRI SNR and movement QA</span></td>
							</tr>
							<tr>
								<td class="label"><input type="checkbox" name="enablebeta" <?=$enablebeta?> value="1"></td>
								<td class="value">Enable Beta features</td>
							</tr>
							<tr>
								<td colspan="2" align="center"><br><input type="submit" value="Save"></td>
							</tr>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
		</form>
		<br><br>
		
		<table class="graydisplaytable" width="50%">
			<thead>
				<tr>
					<th colspan="2">Instances</th>
				</tr>
			</thead>
			<tbody>
			<?
				$sqlstring = "select * from instance order by instance_name";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$instanceid = $row['instance_id'];
					$uid = $row['instance_uid'];
					$name = $row['instance_name'];
					$owner = $row['instance_ownerid'];
					$default = $row['instance_default'];
					$requested = $row['instance_joinrequest'];
					$ownername = $row['username'];
					
					if ($owner == $userid) {
						?><tr><td><a href="instance.php?instanceid=<?=$instanceid?>"><?=$name?></a></td><?
					} else {
						?><tr><td><?=$name?></td><?
					}
					
					$sqlstringA = "select * from user_instance where user_id = (select user_id from users where username = '$username') and instance_id = $instanceid";
					$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
					if (mysql_num_rows($resultA) > 0) {
						$rowA = mysql_fetch_array($resultA, MYSQL_ASSOC);
						$joinrequest = $rowA['instance_joinrequest'];
						if ($joinrequest) {
							?><td style="color: #888">Request Sent</td></tr><?
						}
						else {
							?><td style="color: #888">Joined</td></tr><?
						}
					}
					else {
						?>
						<td><a href="users.php?action=joininstance&instanceid=<?=$instanceid?>">Request to Join</a></td></tr>
						<?
					}
					?>
					</tr>
					<?
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
		
		$sqlstring = "select login_type from users where username = '$username'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$type = $row['login_type'];
		
		if ($type == "Guest") {
			return true;
		}
		else {
			return false;
		}
	}

?>

<? include("footer.php") ?>
