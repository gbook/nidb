<?
 // ------------------------------------------------------------------------------
 // NiDB admin.php
 // Copyright (C) 2004 - 2015
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Administration</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";
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
	
		$urllist['Administration'] = "admin.php";
		NavigationBar("Admin", $urllist);
		
		?>
		<ul>
		<li><a href="adminusers.php">Users</a>
		<li><a href="adminprojects.php">Projects</a>
		<li><a href="adminassessmentforms.php">Assessment Forms</a>
		<li><a href="adminmodules.php">Modules</a>
		<li><a href="adminmodalities.php">Modalities</a>
		<li><a href="adminsites.php">Sites</a>
		<li><a href="reports.php">Reports</a>
		<li><a href="adminqc.php">QC</a>
		<li><a href="importlog.php">Import Logs</a>
		<li><a href="admininstances.php">Instances</a>
		<li><a href="adminaudits.php">Audits</a>
		<li><a href="cleanup.php">Clean-up</a>
		<li><a href="system.php">System info</a>
		<li><a href="stats.php">Usage stats</a>
		<li><a href="longqc.php">Longitudinal QC</a>
		</ul>
		<?
	}
?>


<? include("footer.php") ?>
