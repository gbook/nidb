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
				<div class="ui vertical basic big buttons">
					<a href="adminusers.php" class="ui button"><i class="black users icon"></i> Users</a>
					<a href="adminprojects.php" class="ui button"><i class="black clipboard list icon"></i> Projects</a>
					<a href="reports.php" class="ui button"><i class="black clipboard icon"></i> Reports</a>
					<a href="adminaudits.php" class="ui button"><i class="black clipboard check icon"></i> Audits</a>
					<a href="cleanup.php" class="ui button"><i class="black eraser icon"></i> Clean-up data</a>
					<a href="longqc.php" class="ui button"><i class="black check circle icon"></i> Longitudinal QC</a>
					<a href="stats.php" class="ui button"><i class="black thermometer half icon"></i> System Usage Statistics</a>
				</div>
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
				<i class="large cog icon"></i><a href="system.php"><b>System Settings</b></a>
				<br><br>
				<i class="large wrench icon"></i><a href="setup.php"><b>Setup</b></a>
			</div>
		</div>
		<?
	}
?>

<? include("footer.php") ?>
