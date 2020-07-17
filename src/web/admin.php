<?
 // ------------------------------------------------------------------------------
 // NiDB admin.php
 // Copyright (C) 2004 - 2020
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
		<ul>
			<li><a href="adminusers.php">Users</a>
			<li><a href="adminprojects.php">Projects</a>
		</ul>
		
		<ul>
			<li><a href="reports.php">Reports</a>
			<li><a href="adminaudits.php">Audits</a>
			<li><a href="cleanup.php">Clean-up data</a>
			<li><a href="longqc.php">Longitudinal QC</a>
			<li><a href="stats.php">System Usage Statistics</a>
		</ul>
		
		<ul>
			<li><a href="adminmodules.php">Modules</a>
			<li><a href="adminmodalities.php">Modalities</a>
			<li><a href="adminsites.php">Sites</a>
			<li><a href="adminqc.php">QC Modules</a>
			<!--<li><a href="importlog.php">Import Logs</a>-->
			<li><a href="admininstances.php">Instances</a>
			<li><a href="status.php">System status</a>
			<li><a href="adminemail.php">Mass email</a>
			<li><a href="setup.php">Update</a>
			<li><a href="system.php">NiDB Settings...</a>
		</ul>
		<?
	}
?>

<? include("footer.php") ?>
