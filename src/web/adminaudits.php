<?
 // ------------------------------------------------------------------------------
 // NiDB adminaudits.php
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
		<title>NiDB - Audits</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$orderby = GetVariable("orderby");
	$problemtype = GetVariable("problemtype");
	
	/* determine action */
	switch ($action) {
		case 'displaylog':
			DisplayMenu($problemtype);
			DisplayAudit($orderby, $problemtype);
			break;
		default:
			DisplayMenu($problemtype);
			DisplayAudit($orderby, $problemtype);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu($p) {
		$urllist['Administration'] = "adminaudits.php";
		$urllist['Audits'] = "adminaudits.php";
		NavigationBar("Admin", $urllist);
		
		?><b>Filter by problem type:</b> <a href="adminaudits.php?problemtype=">All</a><?
		$sqlstring = "select distinct(problem) from audit_results where problem <> '' order by problem";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$problemtype = $row['problem'];
			if ($p == $problemtype) {
				?> | <a href="adminaudits.php?problemtype=<?=$problemtype?>" style="border: 1px solid orange; border-radius: 3px; padding: 3px"><?=$problemtype?></a><?
			}
			else {
				?> | <a href="adminaudits.php?problemtype=<?=$problemtype?>"><?=$problemtype?></a><?
			}
		}
		
		?>
		<br><br>
		<?
	}
		
	
	/* -------------------------------------------- */
	/* ------- DisplayAudit ----------------------- */
	/* -------------------------------------------- */
	function DisplayAudit($orderby, $problemtype) {
		if ($problemtype != "") {
			if ($orderby == "") { $sqlstring = "select * from audit_results where problem = '$problemtype' order by subject_uid"; }
			else { $sqlstring = "select * from audit_results where problem = '$problemtype' order by $orderby"; }
		}
		else {
			if ($orderby == "") { $sqlstring = "select * from audit_results order by subject_uid"; }
			else { $sqlstring = "select * from audit_results order by $orderby"; }
		}
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		PrintSQLTable($result,"adminaudits.php?action=displaylog",$orderby,8);
	}
?>

<? include("footer.php") ?>
