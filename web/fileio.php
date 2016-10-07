<?
 // ------------------------------------------------------------------------------
 // NiDB fileio.php
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
		<title>NiDB - File IO</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* determine action */
	switch ($action) {
		case 'displayfileio':
			DisplayFileIO();
			break;
		default:
			DisplayFileIO();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayFileIO ---------------------- */
	/* -------------------------------------------- */
	function DisplayFileIO() {
		$sqlstring = "select * from fileio_requests where requestdate > date_sub(now(), interval 7 day) order by requestdate desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	
?>


<? include("footer.php") ?>
