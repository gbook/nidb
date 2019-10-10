<?
 // ------------------------------------------------------------------------------
 // NiDB instance.php
 // Copyright (C) 2004 - 2019
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Instance</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	PrintVariable($_POST);
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("instanceid");
	$contactid = GetVariable("contactid");
	$contactfullname = GetVariable("contactfullname");
	$contacttitle = GetVariable("contacttitle");
	$contactaddress1 = GetVariable("contactaddress1");
	$contactaddress2 = GetVariable("contactaddress2");
	$contactaddress3 = GetVariable("contactaddress3");
	$contactcity = GetVariable("contactcity");
	$contactstate = GetVariable("contactstate");
	$contactcountry = GetVariable("contactcountry");
	$contactphone1 = GetVariable("contactphone1");
	$contactphone2 = GetVariable("contactphone2");
	$contactphone3 = GetVariable("contactphone3");
	$contactemail1 = GetVariable("contactemail1");
	$contactemail2 = GetVariable("contactemail2");
	$contactemail3 = GetVariable("contactemail3");
	$contactwebsite = GetVariable("contactwebsite");
	$contactcompany = GetVariable("contactcompany");
	$contactdepartment = GetVariable("contactdepartment");

	/* determine action */
	switch ($action) {
		case 'addcontactform':
			DisplayContactForm("add", $id);
			break;
		case 'editcontactform':
			DisplayContactForm("edit", $id, $contactid);
			break;
		case 'add':
			AddContact($id, $contactfullname, $contacttitle, $contactaddress1, $contactaddress2, $contactaddress3, $contactcity, $contactstate, $contactcountry, $contactphone1, $contactphone2, $contactphone3, $contactemail1, $contactemail2, $contactemail3, $contactwebsite, $contactcompany, $contactdepartment);
			break;
		default:
			DisplayInstanceControlPanel($id);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- UpdateContact ---------------------- */
	/* -------------------------------------------- */
	function UpdateContact($id, $contactid, $contactfullname, $contacttitle, $contactaddress1, $contactaddress2, $contactaddress3, $contactcity, $contactstate, $contactcountry, $contactphone1, $contactphone2, $contactphone3, $contactemail1, $contactemail2, $contactemail3, $contactwebsite, $contactcompany, $contactdepartment) {
		/* perform data checks */
		$contactfullname = mysqli_real_escape_string($GLOBALS['linki'], $contactfullname);
		$contacttitle = mysqli_real_escape_string($GLOBALS['linki'], $contacttitle);
		$contactaddress1 = mysqli_real_escape_string($GLOBALS['linki'], $contactaddress1);
		$contactaddress2 = mysqli_real_escape_string($GLOBALS['linki'], $contactaddress2);
		$contactaddress3 = mysqli_real_escape_string($GLOBALS['linki'], $contactaddress3);
		$contactcity = mysqli_real_escape_string($GLOBALS['linki'], $contactcity);
		$contactstate = mysqli_real_escape_string($GLOBALS['linki'], $contactstate);
		$contactcountry = mysqli_real_escape_string($GLOBALS['linki'], $contactcountry);
		$contactphone1 = mysqli_real_escape_string($GLOBALS['linki'], $contactphone1);
		$contactphone2 = mysqli_real_escape_string($GLOBALS['linki'], $contactphone2);
		$contactphone3 = mysqli_real_escape_string($GLOBALS['linki'], $contactphone3);
		$contactemail1 = mysqli_real_escape_string($GLOBALS['linki'], $contactemail1);
		$contactemail2 = mysqli_real_escape_string($GLOBALS['linki'], $contactemail2);
		$contactemail3 = mysqli_real_escape_string($GLOBALS['linki'], $contactemail3);
		$contactwebsite = mysqli_real_escape_string($GLOBALS['linki'], $contactwebsite);
		$contactcompany = mysqli_real_escape_string($GLOBALS['linki'], $contactcompany);
		$contactdepartment = mysqli_real_escape_string($GLOBALS['linki'], $contactdepartment);

		/* update the contact */
		$sqlstring = "update contacts set contact_fullname = '$contactfullname', contact_title = '$contacttitle', contact_address1 = '$contactaddress1', contact_address2 = '$contactaddress2', contact_address3 = '$contactaddress3', contact_city = '$contactcity', contact_state = '$contactstate', contact_country = '$contactcountry', contact_phone1 = '$contactphone1', contact_phone2 = '$contactphone1', contact_phone3 = '$contactphone3', contact_email1 = '$contactemail1', contact_email2 = '$contactemail2', contact_email3 = '$contactemail3', contact_website = '$contactwebsite', contact_company = '$contactcompany', contact_department = '$contactdepartment'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$username?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddContact ------------------------- */
	/* -------------------------------------------- */
	function AddContact($id, $contactfullname, $contacttitle, $contactaddress1, $contactaddress2, $contactaddress3, $contactcity, $contactstate, $contactcountry, $contactphone1, $contactphone2, $contactphone3, $contactemail1, $contactemail2, $contactemail3, $contactwebsite, $contactcompany, $contactdepartment) {
		/* perform data checks */
		$contactfullname = mysqli_real_escape_string($GLOBALS['linki'], $contactfullname);
		$contacttitle = mysqli_real_escape_string($GLOBALS['linki'], $contacttitle);
		$contactaddress1 = mysqli_real_escape_string($GLOBALS['linki'], $contactaddress1);
		$contactaddress2 = mysqli_real_escape_string($GLOBALS['linki'], $contactaddress2);
		$contactaddress3 = mysqli_real_escape_string($GLOBALS['linki'], $contactaddress3);
		$contactcity = mysqli_real_escape_string($GLOBALS['linki'], $contactcity);
		$contactstate = mysqli_real_escape_string($GLOBALS['linki'], $contactstate);
		$contactcountry = mysqli_real_escape_string($GLOBALS['linki'], $contactcountry);
		$contactphone1 = mysqli_real_escape_string($GLOBALS['linki'], $contactphone1);
		$contactphone2 = mysqli_real_escape_string($GLOBALS['linki'], $contactphone2);
		$contactphone3 = mysqli_real_escape_string($GLOBALS['linki'], $contactphone3);
		$contactemail1 = mysqli_real_escape_string($GLOBALS['linki'], $contactemail1);
		$contactemail2 = mysqli_real_escape_string($GLOBALS['linki'], $contactemail2);
		$contactemail3 = mysqli_real_escape_string($GLOBALS['linki'], $contactemail3);
		$contactwebsite = mysqli_real_escape_string($GLOBALS['linki'], $contactwebsite);
		$contactcompany = mysqli_real_escape_string($GLOBALS['linki'], $contactcompany);
		$contactdepartment = mysqli_real_escape_string($GLOBALS['linki'], $contactdepartment);
		
		/* insert the new user */
		$sqlstring = "insert into contacts (contact_fullname, contact_title, contact_address1, contact_address2, contact_address3, contact_city, contact_state, contact_country, contact_phone1, contact_phone2, contact_phone3, contact_email1, contact_email2, contact_email3, contact_website, contact_company, contact_department) values ('$contactfullname', '$contacttitle', '$contactaddress1', '$contactaddress2', '$contactaddress3', '$contactcity', '$contactstate', '$contactcountry', '$contactphone1', '$contactphone2', '$contactphone3', '$contactemail1', '$contactemail2', '$contactemail3', '$contactwebsite', '$contactcompany', '$contactdepartment')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$contactid = mysqli_insert_id($GLOBALS['linki']);
		
		/* associate the contact with the instace */
		$sqlstring = "insert into instance_contact (instance_id, contact_id) values ($id, $contactid)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$contactfullname?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteUser ------------------------- */
	/* -------------------------------------------- */
	function DeleteContact($contactid) {
		/* remove the contact */
		$sqlstring = "delete from contacts where contact_id = $contactid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* remove the associations in the instance_contact table */
		$sqlstring = "delete from instance_contact where contact_id = $contactid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayContactForm ----------------- */
	/* -------------------------------------------- */
	function DisplayContactForm($type, $id, $contactid='') {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from contacts where contact_id = $contactid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$contactfullname = $row['contact_fullname'];
			$contacttitle = $row['contact_title'];
			$contactaddress1 = $row['contact_address1'];
			$contactaddress2 = $row['contact_address2'];
			$contactaddress3 = $row['contact_address3'];
			$contactcity = $row['contact_city'];
			$contactstate = $row['contact_state'];
			$contactcountry = $row['contact_country'];
			$contactphone1 = $row['contact_phone1'];
			$contactphone2 = $row['contact_phone2'];
			$contactphone3 = $row['contact_phone3'];
			$contactemail1 = $row['contact_email1'];
			$contactemail2 = $row['contact_email2'];
			$contactemail3 = $row['contact_email3'];
			$contactwebsite = $row['contact_website'];
			$contactcompany = $row['contact_company'];
			$contactdepartment = $row['contact_department'];
		
			$formaction = "update";
			$formtitle = "Updating $contact_fullname";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new contact";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Administration'] = "admin.php";
		$urllist['Instance Control Panel'] = "instance.php";
		$urllist[$contact_fullname] = "instance.php?action=editcontactform&contactid=$contactid";
		NavigationBar("Admin", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="instance.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="contactid" value="<?=$contactid?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td class="heading" colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td class="label">Title</td>
				<td><input type="text" name="contacttitle" value="<?=$contacttitle?>" size="6"></td>
			</tr>
			<tr>
				<td class="label">Full name <span style="color:red">*</span></td>
				<td><input type="text" name="contactfullname" value="<?=$contactfullname?>" size="40" required></td>
			</tr>
			<tr>
				<td class="label">Address <span style="color:red">*</span></td>
				<td>
					<input type="text" name="contactaddress1" value="<?=$contactaddress1?>" size="40" required maxlength="255"><br>
					<input type="text" name="contactaddress2" value="<?=$contactaddress2?>" size="40" maxlength="255"><br>
					<input type="text" name="contactaddress3" value="<?=$contactaddress3?>" size="40" maxlength="255">
				</td>
			</tr>
			<tr>
				<td class="label">City <span style="color:red">*</span></td>
				<td><input type="text" name="contactcity" value="<?=$contactcity?>" size="40" required maxlength="255"></td>
			</tr>
			<tr>
				<td class="label">State</td>
				<td>
					<select name="contactstate" required>
						<option value="">Select state...</option>
						<optgroup label="US">
							<option value="AL">Alabama</option>
							<option value="AK">Alaska</option>
							<option value="AZ">Arizona</option>
							<option value="AR">Arkansas</option>
							<option value="CA">California</option>
							<option value="CO">Colorado</option>
							<option value="CT">Connecticut</option>
							<option value="DE">Delaware</option>
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
							<option value="DC">Washington DC</option>
						</optgroup>
						<optgroup label="US Territories">
							<option value="PR">Puerto Rico</option>
							<option value="VI">U.S. Virgin Islands</option>
							<option value="AS">American Samoa</option>
							<option value="GU">Guam</option>
							<option value="MP">Northern Mariana Islands</option>
						</optgroup>
						<optgroup label="Canada">
							<option value="AB">Alberta </option>
							<option value="BC">British Columbia </option>
							<option value="MB">Manitoba </option>
							<option value="NB">New Brunswick </option>
							<option value="NL">Newfoundland and Labrador </option>
							<option value="NS">Nova Scotia </option>
							<option value="ON">Ontario </option>
							<option value="PE">Prince Edward Island </option>
							<option value="QC">Quebec </option>
							<option value="SK">Saskatchewan </option>
							<option value="NT">Northwest Territories </option>
							<option value="NU">Nunavut </option>
							<option value="YT">Yukon Territory </option>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Country <span style="color:red">*</span></td>
				<td>
					<select name="contactcountry" required>
						<option value="">Select Country...</option>
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
						<option value="BQ">Caribbean Netherlands </option>
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
						<option value="CD">Congo, Democratic Republic of</option>
						<option value="CK">Cook Islands</option>
						<option value="CR">Costa Rica</option>
						<option value="CI">Côte d'Ivoire</option>
						<option value="HR">Croatia</option>
						<option value="CU">Cuba</option>
						<option value="CW">Curaçao</option>
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
						<option value="FK">Falkland Islands</option>
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
						<option value="GW">Guinea-Bissau</option>
						<option value="GY">Guyana</option>
						<option value="HT">Haiti</option>
						<option value="HM">Heard and McDonald Islands</option>
						<option value="HN">Honduras</option>
						<option value="HK">Hong Kong</option>
						<option value="HU">Hungary</option>
						<option value="IS">Iceland</option>
						<option value="IN">India</option>
						<option value="ID">Indonesia</option>
						<option value="IR">Iran</option>
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
						<option value="KW">Kuwait</option>
						<option value="KG">Kyrgyzstan</option>
						<option value="LA">Lao People's Democratic Republic</option>
						<option value="LV">Latvia</option>
						<option value="LB">Lebanon</option>
						<option value="LS">Lesotho</option>
						<option value="LR">Liberia</option>
						<option value="LY">Libya</option>
						<option value="LI">Liechtenstein</option>
						<option value="LT">Lithuania</option>
						<option value="LU">Luxembourg</option>
						<option value="MO">Macau</option>
						<option value="MK">Macedonia</option>
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
						<option value="MD">Moldova</option>
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
						<option value="NC">New Caledonia</option>
						<option value="NZ">New Zealand</option>
						<option value="NI">Nicaragua</option>
						<option value="NE">Niger</option>
						<option value="NG">Nigeria</option>
						<option value="NU">Niue</option>
						<option value="NF">Norfolk Island</option>
						<option value="KP">North Korea</option>
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
						<option value="BL">Saint Barthélemy</option>
						<option value="SH">Saint Helena</option>
						<option value="KN">Saint Kitts and Nevis</option>
						<option value="LC">Saint Lucia</option>
						<option value="VC">Saint Vincent and the Grenadines</option>
						<option value="MF">Saint-Martin (France)</option>
						<option value="SX">Saint-Martin (Pays-Bas)</option>
						<option value="WS">Samoa</option>
						<option value="SM">San Marino</option>
						<option value="ST">Sao Tome and Principe</option>
						<option value="SA">Saudi Arabia</option>
						<option value="SN">Senegal</option>
						<option value="RS">Serbia</option>
						<option value="SC">Seychelles</option>
						<option value="SL">Sierra Leone</option>
						<option value="SG">Singapore</option>
						<option value="SK">Slovakia (Slovak Republic)</option>
						<option value="SI">Slovenia</option>
						<option value="SB">Solomon Islands</option>
						<option value="SO">Somalia</option>
						<option value="ZA">South Africa</option>
						<option value="GS">South Georgia and the South Sandwich Islands</option>
						<option value="KR">South Korea</option>
						<option value="SS">South Sudan</option>
						<option value="ES">Spain</option>
						<option value="LK">Sri Lanka</option>
						<option value="PM">St. Pierre and Miquelon</option>
						<option value="SD">Sudan</option>
						<option value="SR">Suriname</option>
						<option value="SJ">Svalbard and Jan Mayen Islands</option>
						<option value="SZ">Swaziland</option>
						<option value="SE">Sweden</option>
						<option value="CH">Switzerland</option>
						<option value="SY">Syria</option>
						<option value="TW">Taiwan</option>
						<option value="TJ">Tajikistan</option>
						<option value="TZ">Tanzania</option>
						<option value="TH">Thailand</option>
						<option value="NL">The Netherlands</option>
						<option value="TL">Timor-Leste</option>
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
						<option value="VA">Vatican</option>
						<option value="VE">Venezuela</option>
						<option value="VN">Vietnam</option>
						<option value="VG">Virgin Islands (British)</option>
						<option value="VI">Virgin Islands (U.S.)</option>
						<option value="WF">Wallis and Futuna Islands</option>
						<option value="EH">Western Sahara</option>
						<option value="YE">Yemen</option>
						<option value="ZM">Zambia</option>
						<option value="ZW">Zimbabwe</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Phone <span style="color:red">*</span></td>
				<td>
					<input type="tel" name="contactphone1" value="<?=$contactphone1?>" size="40" required maxlength="255"><br>
					<input type="tel" name="contactphone2" value="<?=$contactphone2?>" size="40" maxlength="255"><br>
					<input type="tel" name="contactphone3" value="<?=$contactphone3?>" size="40" maxlength="255">
				</td>
			</tr>
			<tr>
				<td class="label">Email <span style="color:red">*</span></td>
				<td>
					<input type="email" name="contactemail1" value="<?=$contactemail1?>" size="40" required maxlength="255"><br>
					<input type="email" name="contactemail2" value="<?=$contactemail2?>" size="40" maxlength="255"><br>
					<input type="email" name="contactemail3" value="<?=$contactemail3?>" size="40" maxlength="255">
				</td>
			</tr>
			<tr>
				<td class="label">Website</td>
				<td><input type="text" name="contactwebsite" value="<?=$contactwebsite?>" size="40" maxlength="255"></td>
			</tr>
			<tr>
				<td class="label">Company</td>
				<td><input type="text" name="contactcompany" value="<?=$contactcompany?>" size="40" maxlength="255"></td>
			</tr>
			<tr>
				<td class="label">Department</td>
				<td><input type="text" name="contactdepartment" value="<?=$contactdepartment?>" size="40" maxlength="255"></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" id="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		</div>
		<br><br><br>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayInstanceControlPanel -------- */
	/* -------------------------------------------- */
	function DisplayInstanceControlPanel($id) {
	
		$urllist['Instance Control Panel'] = "instance.php";
		NavigationBar("Admin", $urllist);
		
		/* if the instance ID is blank, display a list of instances that they own */
		if ($id == "") {
		?>
		<table class="graydisplaytable" width="50%">
			<thead>
				<tr>
					<th colspan="2">Instances</th>
				</tr>
			</thead>
			<tbody>
			<?
				$sqlstring = "select * from instance where instance_ownerid = (select user_id from users where username = '" . $_SESSION['username'] . "') order by instance_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$instanceid = $row['instance_id'];
					$uid = $row['instance_uid'];
					$name = $row['instance_name'];
					$owner = $row['instance_ownerid'];
					$default = $row['instance_default'];
					$requested = $row['instance_joinrequest'];
					$ownername = $row['username'];
					
					?>
					<tr><td><a href="instance.php?instanceid=<?=$instanceid?>"><?=$name?></a></td>
					<?
				}
				?>
			</tbody>
		</table>
			<?
			return;
		}
		
	?>
	<details style="border: 1px solid #ddd">
		<summary style="width:100%">Manage Contacts</summary>
		<table class="smallgraydisplaytable" style="margin:15px">
			<thead>
				<tr>
					<th>Name</th>
					<th>Email</th>
				</tr>
			</thead>
			<?
				$sqlstring = "select * from contacts where contact_id in (select contact_id from instance_contact where instance_id = $id)";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$contactid = $row['contact_id'];
						$name = $row['contact_fullname'];
						$email = $row['contact_email1'];
						?>
						<tr>
							<td><a href="instance.php?action=editcontactform&contactid=<?=$contactid?>"><?=$name?></a></td>
							<td><?=$email?></td>
						</tr>
						<?
					}
				}
				else {
			?>
				<tr>
					<td colspan="2">No contacts</td>
				</tr>
			<?
				}
			?>
		</table>
		<a href="instance.php?action=addcontactform&instanceid=<?=$id?>">Add contact</a>
	</details>
	
	<br>
	
	<details style="border: 1px solid #ddd">
		<summary style="width:100%">Pricing</summary>
		<table class="smallgraydisplaytable" style="margin:15px">
			<thead>
				<tr>
					<th>Item</th>
					<th>Unit</th>
					<th>Notes</th>
					<th>Price</th>
					<th>Cost Calculator</th>
				</tr>
			</thead>
			<script>
				function CalcTotal() {
					var sum = 0;
					$(".txt").each(function() {
						//if(!isNaN(this.value) && this.value.length!=0) {
							sum += parseFloat(this.value);
						//}
					});
					document.getElementById('thetotal').value = '$' + sum.toFixed(2);
				}
			</script>
			<?
				$sqlstring = "select * from instance_pricing where (now() between pricing_startdate and pricing_enddate) and pricing_internal <> 1";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$priceid = $row['pricing_id'];
						$item = $row['pricing_itemname'];
						$notes = $row['pricing_comments'];
						$unit = $row['pricing_unit'];
						$price = $row['pricing_price'];
						?>
						<tr>
							<td valign="top"><?=$item?></td>
							<td valign="top"><?=$unit?></td>
							<td class="tiny" style="width:150px" valign="top"><?=$notes?></td>
							<td valign="top">$<?=$price?></td>
							<script>
								function Calc<?=$priceid?>() {
									var subtotal = <?=$price?> * document.getElementById('qty<?=$priceid?>').value;
									document.getElementById('total<?=$priceid?>').value = subtotal.toFixed(2);
									CalcTotal();
								}
							</script>
							<td valign="top">
								<input type="text" id="qty<?=$priceid?>" placeholder="quantity" size="10" onChange="Calc<?=$priceid?>()"> $<input type="text" class="txt" size="10" readonly id="total<?=$priceid?>" value="0.00">
							</td>
						</tr>
						<?
					}
					?>
					<tr>
						<td class="tiny" colspan="3" style="width:200px" valign="top"><b>Note:</b> Billing occurs monthly, for the highest quantity during that month. If you collect N subjects over the course of a several year study, your initial monthly cost will be small compared to the ending cost calculated here</td>
						<td align="right" valign="top">Total</td>
						<td align="right" valign="top"><input type="text" size="10" readonly value="$0.00" id="thetotal"></td>
					</tr>
					<?
				}
				else {
			?>
				<tr>
					<td colspan="2">No current pricing</td>
				</tr>
			<?
				}
			?>
		</table>
	</details>

	<br>
	
	<details style="border: 1px solid #ddd">
		<summary style="width:100%">Current usage</summary>
		<table class="smallgraydisplaytable" style="margin:15px">
			<thead>
				<tr>
					<th>Date</th>
					<th>Item</th>
					<th>Units</th>
					<th>Price</th>
					<th>Quantity</th>
					<th>Total</th>
				</tr>
			</thead>
			<?
				$sqlstring = "select * from instance_usage a left join instance_pricing b on a.pricing_id = b.pricing_id where a.instance_id = $id order by a.usage_date";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$usagedate = $row['usage_date'];
						$usageamount = $row['usage_amount'];
						$item = $row['pricing_itemname'];
						$unit = $row['pricing_unit'];
						$price = $row['pricing_price'];
						
						$total = $price*$usageamount;
						?>
						<tr>
							<td><?=$usagedate?></td>
							<td><?=$item?></td>
							<td><?=$unit?></td>
							<td align="right">$<?=number_format($price,2)?></td>
							<td align="right"><?=number_format($usageamount,3)?></td>
							<td align="right">$<?=number_format($total,2)?></td>
						</tr>
						<?
					}
				}
				else {
			?>
				<tr>
					<td colspan="2">No usage</td>
				</tr>
			<?
				}
			?>
		</table>
	</details>
	<?
	}
?>