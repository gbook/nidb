<?
 // ------------------------------------------------------------------------------
 // NiDB calendar_select.php
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
	
	ob_start(); // for any page redirects
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Calendar</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($_GET);

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$currentcal = (int)GetVariable("currentcal"); /* 0 = all calendars */

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
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['calendar_id'];
			$name = $row['calendar_name'];
			$description = $row['calendar_description'];
			$location = $row['calendar_location'];
			?>
			<a href="calendar_select.php?action=set&currentcal=<?=(int)$id?>"><b><?=htmlspecialchars($name ?? '')?></b></a> - <?=htmlspecialchars($location ?? '')?><br>
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
		$calid = (int)$calid;
		if ($calid == 0) {
			$calname = "All Calendars";
		}
		else {
			$sqlstring = "select calendar_name from calendars where calendar_id = ? and calendar_deletedate > now()";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $calid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($calid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) { Error("Calendar not found"); DisplayMenu(); return; }
			$calname = $row['calendar_name'];
		}
		setcookie("currentcal", $calid);
		setcookie("currentcalname", $calname);
		RedirectTo("calendar.php");
	}
?>
	
<? include("footer.php") ?>
