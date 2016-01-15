<?
 // ------------------------------------------------------------------------------
 // NiDB adminaudits.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Audits</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "nidbapi.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$orderby = GetVariable("orderby");
	
	/* determine action */
	switch ($action) {
		case 'displaylog':
			DisplayMenu();
			DisplayAudit($orderby);
			break;
		default:
			DisplayMenu();
			DisplayAudit($orderby);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
		$urllist['Administration'] = "adminaudits.php";
		$urllist['Audits'] = "adminaudits.php";
		NavigationBar("Admin", $urllist);
		
		?>
		<br><br>
		<?
	}
		
	
	/* -------------------------------------------- */
	/* ------- DisplayAudit ----------------------- */
	/* -------------------------------------------- */
	function DisplayAudit($orderby) {
		if ($orderby == "") {
			$sqlstring = "select * from audit_results order by subject_uid";
		}
		else {
			$sqlstring = "select * from audit_results order by $orderby";
		}
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		PrintSQLTable($result,"adminaudits.php?action=displaylog",$orderby,8);
	}
?>

<? include("footer.php") ?>
