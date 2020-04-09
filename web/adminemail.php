<?
 // ------------------------------------------------------------------------------
 // NiDB adminemail.php
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
		<title>NiDB - Mass email</title>
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
	$emailbody = GetVariable("emailbody");
	$emailsubject = GetVariable("emailsubject");
	$emailto = GetVariable("emailto");
	
	/* determine action */
	switch ($action) {
		case 'sendemail':
			SendEmail($emailbody, $emailsubject, $emailto);
			break;
		default:
			DisplayEmailForm();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayEmailForm ------------------- */
	/* -------------------------------------------- */
	function DisplayEmailForm() {
		$urllist['Administration'] = "admin.php";
		$urllist['Mass email'] = "admineamil.php";
		NavigationBar("Admin", $urllist);
		
		?>
		<form action="adminemail.php" method="post" name="theform">
		<input type="hidden" name="action" value="sendemail">
		<input type="text" name="emailsubject" size="60" placeholder="Subject">
		<br><br>
		<textarea name="emailbody" rows="10" cols="60" placeholder="Body"></textarea>
		<br>
		<input type="submit" value="Send email">
		</form>
		<?
	}
		
	
	/* -------------------------------------------- */
	/* ------- SendEmail -------------------------- */
	/* -------------------------------------------- */
	function SendEmail($emailbody, $emailsubject, $emailto) {

		$sqlstring = "select user_email from users where user_email <> ''";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$emailto = $row['user_email'];
			/* send the email */
			if (!SendGmail($emailto,$emailsubject,$emailbody, 1, 0)) {
				return "System error. Unable to send email!";
			}
			else {
				return "Message send successfully to $numrows recipients";
			}
		}
	}
	
?>

<? include("footer.php") ?>
