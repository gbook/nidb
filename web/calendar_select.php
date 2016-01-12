<?
 // ------------------------------------------------------------------------------
 // NiDB calendar.php
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
	ob_start(); // for any page redirects
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
	require "includes.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["currentcal"] == "") { $currentcal = $_GET["currentcal"]; } else { $currentcal = $_POST["currentcal"]; }

	/* check the action */
	if ($action == "set") {
		SetCalendar($currentcal);
	}
	elseif (($action == "") || ($action == "list")) {
		DisplayMenu();
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayMenu ------------------------- */
	/* ----------------------------------------------- */
	function DisplayMenu() {
		$sqlstring = "select * from calendars where calendar_deletedate > now()";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$id = $row['calendar_id'];
			$name = $row['calendar_name'];
			$description = $row['calendar_description'];
			$location = $row['calendar_location'];
			?>
			<a href="calendar_select.php?action=set&currentcal=<?=$id?>"><b><?=$name?></b></a> - <?=$location?><br>
			<?
		}
		?>
		<a href="calendar_select.php?action=set&currentcal=0"><b>All calendars</b></a><br>
		<?
	}


	/* ----------------------------------------------- */
	/* --------- SetCalendar ------------------------- */
	/* ----------------------------------------------- */
	function SetCalendar($calid) {
		$sqlstring = "select * from calendars where calendar_id = $calid";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$calname = $row['calendar_name'];
	
		if ($calid == 0) { $calname = "All Calendars"; }
		setcookie("currentcal", $calid);
		setcookie("currentcalname", $calname);
		header("Location: calendar.php");
	}
?>
	
<? include("footer.php") ?>
