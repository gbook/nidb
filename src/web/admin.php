<?
 // ------------------------------------------------------------------------------
 // NiDB admin.php
 // Copyright (C) 2004 - 2021
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
		<title>NiDB - Administration</title>
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

	/* determine action */
	if ($action == "") {
		DisplayAdminList();
	}
	else {
		DisplayAdminList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayAdminList ------------------- */
	/* -------------------------------------------- */
	function DisplayAdminList() {
	

		?>
		<div class="ui text container grid">
			<div class="ui eight wide column">
				<i class="users icon"></i><a href="adminusers.php">Users</a>
				<br><br>
				<i class="clipboard list icon"></i><a href="adminprojects.php">Projects</a>
				<br><br><br>
				<i class="clipboard icon"></i><a href="reports.php">Reports</a>
				<br><br>
				<i class="clipboard check icon"></i><a href="adminaudits.php">Audits</a>
				<br><br>
				<i class="eraser icon"></i><a href="cleanup.php">Clean-up data</a>
				<br><br>
				<i class="check circle icon"></i><a href="longqc.php">Longitudinal QC</a>
				<br><br>
				<i class="thermometer half icon"></i><a href="stats.php">System Usage Statistics</a>
			</div>
			<div class="ui eight wide column">
				<li><a href="adminmodules.php">Modules</a>
				<li><a href="adminmodalities.php">Modalities</a>
				<li><a href="adminsites.php">Sites</a>
				<li><a href="adminqc.php">QC Modules</a>
				<!--<li><a href="importlog.php">Import Logs</a>-->
				<li><a href="admininstances.php">Instances</a>
				<li><a href="status.php">System status</a>
				<li><a href="adminemail.php">Mass email</a>
				<br><br><br>
				<i class="cog icon large red"></i><a href="system.php"><b>System Settings</b></a>
			</div>
		</div>
		<?
	}
?>

<? include("footer.php") ?>
