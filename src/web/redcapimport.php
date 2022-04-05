<?
 // ------------------------------------------------------------------------------
 // NiDB redcapimport.php
 // Copyright (C) 2004 - 2022
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
	
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$redcapevent = GetVariable("redcapevent");
	$redcapurl = GetVariable("redcapurl");
	$redcaptoken = GetVariable("redcaptoken");
						
	/* determine action */
	switch ($action) {
		case 'updateconnection':
			UpdateConnection($projectid, $redcapurl, $redcaptoken);
			DisplayRedCapSettings($projectid);
			break;
		case	'importsettings':
			DisplayRedCapSettings($projectid);
			break;
		default:
			DisplayRedCapSettings($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- UpdateConnection ------------------- */
	/* -------------------------------------------- */
	function UpdateConnection($projectid, $redcapurl, $redcaptoken) {
		$redcapurl = mysqli_real_escape_string($GLOBALS['linki'], $redcapurl);
		$redcaptoken = mysqli_real_escape_string($GLOBALS['linki'], $redcaptoken);
		
		if ((trim($projectid) == "") || ($projectid < 0)) {
			?>Invalid or blank project ID [<?=$projectid?>]<?
			return;
		}
		
		$sqlstring = "update projects set redcap_server = '$redcapurl', redcap_token = '$redcaptoken' where project_id = '$projectid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayRedCapSettings -------------- */
	/* -------------------------------------------- */
	function DisplayRedCapSettings($projectid) {
		
		if ((trim($projectid) == "") || ($projectid < 0)) {
			?>Invalid or blank project ID [<?=$projectid?>]<?
			return;
		}
		
		$sqlstring = "select redcap_server, redcap_token from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$redcapurl = $row['redcap_server'];
		$redcaptoken = $row['redcap_token'];
		
		?>


	<div class="ui four column centered container">
		<form action="redcapimport.php" method="post">
		<input type="hidden" name="action" value="updateconnection">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		
		<br><br>
		<h2 class="ui top attached inverted header" align="center"> Setup Redcap Connection </h2>
		<br> 

			
		<div class="four row column">
                        <div class="ui labeled input">
                          <div class="ui  label">
                            *Redcap Server
                          </div>
                          <input type="text"  name="redcapurl" value="<?=$redcapurl?>"  size="50" required>
                        </div>

                        <br>
                         <div class="ui labeled input">
                          <div class="ui  label">
                            *Redcap Token  
                          </div>
                                <input type="text" name="redcaptoken" value="<?=$redcaptoken?>" size="50" required>
                        </div>

		</div>
			<br>

			<button class="ui primary right floated button" type="submit">
                          <i class="linkify icon"></i>
                          Update Connection Settings
		       </button>
		</form>
	</div>

		<?
	}


include("footer.php") ?>
