<?
 // ------------------------------------------------------------------------------
 // NiDB about.php
 // Copyright (C) 2004 - 2019
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
		DisplayAbout();
	}
	else {
		DisplayAbout();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayAbout ----------------------- */
	/* -------------------------------------------- */
	function DisplayAbout() {
	
		$urllist['Home'] = "index.php";
		$urllist['About'] = "about.php";
		NavigationBar("About", $urllist);
		
		?>
		<p><i>Neuroinformatics Database</i> (NiDB) was developed at the Olin Neuropsychiatry Research Center at Hartford Hospital. This system is open source, released under the <a href="http://www.gnu.org/copyleft/gpl.html">GPLv3</a> license.</p>
		
		<p>Visit the NiDB website at <a href="http://github.com/gbook/nidb">http://github.com/gbook/nidb</a></p>
		
		<p>Problems, compliments, or suggestions should be directed to <a href="mailto:gregory.book@hhchealth.org">gregory.book@hhchealth.org</a></p>
		<?
	}
?>


<? include("footer.php") ?>
