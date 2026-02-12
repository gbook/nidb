<?
 // ------------------------------------------------------------------------------
 // NiDB adminerrorlogs.php
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
		<title>NiDB - Manage Sites</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";
	
	/* check if they have permissions to this view page */
	if (!isSiteAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	
	/* determine action */
	if ($action == "editform") {
		DisplaySiteForm("edit", $id);
	}
	else {
		DisplayErrorLogs();
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayErrorLogs ------------------- */
	/* -------------------------------------------- */
	function DisplayErrorLogs($viewall=false) {
	?>
		<h2 class="ui header">
			Error Logs
			<div class="sub header">Displaying 30 most recent error messages</div>
		</h2>
		
		<table class="ui selectable celled grey table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Module</th>
					<th>Type</th>
					<th>Source</th>
					<th>Hostname</th>
					<th>Error</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from error_log order by error_date desc limit 30";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$hostname = $row['error_hostname'];
						$type = $row['error_type'];
						$source = $row['error_source'];
						$module = $row['error_module'];
						$date = $row['error_date'];
						$message = $row['error_message'];
				?>
				<tr>
					<td class="top aligned"><?=$date?></td>
					<td class="top aligned"><?=$module?></td>
					<td class="top aligned"><?=$type?></td>
					<td class="top aligned"><?=$source?></td>
					<td class="top aligned"><?=$hostname?></td>
					<td class="top aligned">
						<div class="ui styled fluid accordion">
							<div class="title">
								<i class="dropdown icon"></i>Error text...
							</div>
							<div class="content">
								<pre><?=$message?></pre>
							</div>
						</div>
					</td>
				</tr>
				<? 
					}
				?>
			</tbody>
		</table>
	<?
	}
?>


<? include("footer.php") ?>
