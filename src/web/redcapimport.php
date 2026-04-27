<?
 // ------------------------------------------------------------------------------
 // NiDB redcapimport.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - RedCap import</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$redcapurl = GetVariable("redcapurl");
	$redcaptoken = GetVariable("redcaptoken");
	$redcapidfield = GetVariable("redcapidfield");
	$redcapnidbidfield = GetVariable("redcapnidbidfield");
						
	/* determine action */
	switch ($action) {
		case 'updateconnection':
			UpdateConnection($projectid, $redcapurl, $redcaptoken, $redcapidfield, $redcapnidbidfield);
			DisplayRedCapSettings($projectid);
			break;
		case 'importsettings':
			DisplayRedCapSettings($projectid);
			break;
		default:
			DisplayRedCapSettings($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- UpdateConnection ------------------- */
	/* -------------------------------------------- */
	function UpdateConnection($projectid, $redcapurl, $redcaptoken, $redcapidfield, $redcapnidbidfield) {
		$projectid = trim($projectid);
		$redcapurl = trim($redcapurl);
		$redcaptoken = trim($redcaptoken);
		$redcapidfield = trim($redcapidfield);
		$redcapnidbidfield = trim($redcapnidbidfield);

		if (($projectid == "") || ($projectid < 0)) {
			Error("Invalid or blank project ID [$projectid]");
			return;
		}
		$projectid = (int)$projectid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "update projects set redcap_server = ?, redcap_token = ?, redcapid_field = ?, redcapnidbid_field = ? where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'ssssi', $redcapurl, $redcaptoken, $redcapidfield, $redcapnidbidfield, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("REDCap connection settings updated");
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayRedCapSettings -------------- */
	/* -------------------------------------------- */
	function DisplayRedCapSettings($projectid) {
		$projectid = trim($projectid);

		if (($projectid == "") || ($projectid < 0)) {
			Error("Invalid or blank project ID [$projectid]");
			return;
		}
		$projectid = (int)$projectid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select redcap_server, redcap_token, redcapid_field, redcapnidbid_field from projects where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row) {
			Error("Project not found");
			return;
		}

		$redcapurl = $row['redcap_server'];
		$redcaptoken = $row['redcap_token'];
		$redcapidfield = $row['redcapid_field'];
		$redcapnidbidfield = $row['redcapnidbid_field'];

		?>

		<div class="ui text container">
			<h2 class="ui top attached inverted header" align="center">Setup REDCap Connection</h2>
			<form action="redcapimport.php" method="post" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="updateconnection">
				<input type="hidden" name="projectid" value="<?=$projectid?>">

				<h4 class="ui dividing header">REDCap server</h4>
				<div class="field">
					<label>REDCap server</label>
					<input type="text" name="redcapurl" value="<?=$redcapurl?>" required>
				</div>
				<div class="field">
					<label>REDCap token</label>
					<input type="text" name="redcaptoken" value="<?=$redcaptoken?>" required>
				</div>

				<h4 class="ui dividing header">Subject identification fields</h4>
				<div class="field">
					<label>REDCap unique ID <i class="small blue question circle outline icon" title="Provide the name of the REDCap field containing the unique record ID"></i></label>
					<input type="text" name="redcapidfield" value="<?=$redcapidfield?>" required>
				</div>
				<div class="field">
					<label>REDCap-NiDB ID <i class="small blue question circle outline icon" title="Provide the name of the REDCap field containing the NiDB subject ID"></i></label>
					<input type="text" name="redcapnidbidfield" value="<?=$redcapnidbidfield?>" required>
				</div>

				<div align="right">
					<button class="ui primary button" type="submit"><i class="linkify icon"></i>Update Connection Settings</button>
				</div>
			</form>
		</div>

		<?
	}


	require "footer.php";
?>
