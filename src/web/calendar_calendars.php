<?
 // ------------------------------------------------------------------------------
 // NiDB calendar_calendars.php
 // Copyright (C) 2004 - 2022
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
		<title>NiDB - Calendar List</title>
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
	
	/* get variables */
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["id"] == "") { $id = $_GET["id"]; } else { $id = $_POST["id"]; }
	if ($_POST["name"] == "") { $name = $_GET["name"]; } else { $name = $_POST["name"]; }
	if ($_POST["description"] == "") { $description = $_GET["description"]; } else { $description = $_POST["description"]; }
	if ($_POST["location"] == "") { $location = $_GET["location"]; } else { $location = $_POST["location"]; }

	/* check the action */
	if ($action == "addform") {
		AddForm("",$name, $description, $location);
	}
	if ($action == "add") {
		Add($name, $description, $location);
	}
	elseif ($action == "editform") {
		EditForm("",$id);
	}
	elseif ($action == "edit") {
		Edit($id, $name, $description, $location);
	}
	elseif ($action == "delete") {
		Delete($id);
	}
	elseif (($action == "") || ($action == "list")) {
		DisplayList();
	}
	
	
	/* ----------------------------------------------- */
	/* --------- DisplayList ------------------------- */
	/* ----------------------------------------------- */
	function DisplayList() {
		?>
		
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<a href="calendar.php" class="ui button"><i class="arrow alternate circle left icon"></i>Back</a>
				</div>
				<div class="right aligned column">
					<a href="calendar_calendars.php?action=addform" class="ui primary button"><i class="plus square icon"></i>Add Calendar</a>
				</div>
			</div>
		
		<table class="ui celled selectable grey table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Description</th>
					<th>Location</th>
					<th class="center aligned">Delete</th>
				</tr>
			</thead>
			<tbody>
				<?
				$sqlstring = "select * from calendars where calendar_deletedate > now()";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['calendar_id'];
					$name = $row['calendar_name'];
					$description = $row['calendar_description'];
					$location = $row['calendar_location'];
					?>
					<tr>
						<td><a href="calendar_calendars.php?action=editform&id=<? =$id?>" class="ui button"><i class="edit icon"></i><? =$name?></a></td>
						<td><? =$description?></td>
						<td><? =$location?></td>
						<td class="center aligned">
							<a href="calendar_calendars.php?action=delete&id=<? =$id?>" onclick="return confirm('Are you sure?')"><i class="ui red alternate trash icon"></i></a>
						</td>
					</tr>
					<?
				}
				?>
			</tbody>
		</table>
		</div>
		<?
	}
	
	
	/* ----------------------------------------------- */
	/* --------- Add --------------------------------- */
	/* ----------------------------------------------- */
	function Add($name, $description, $location) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($name == "") { AddForm("'Calendar Name' is blank",$name, $description, $location); return; }
		if ($description == "") { AddForm("'Description' is blank",$name, $description, $location); return; }
		if ($location == "") { AddForm("'Location' is blank",$name, $description, $location); return; }

		$name = mysqli_real_escape_string($GLOBALS['linki'], $name);
		$description = mysqli_real_escape_string($GLOBALS['linki'], $description);
		$location = mysqli_real_escape_string($GLOBALS['linki'], $location);
		
		/* if we get to this point, its safe to add to the database */
		$sqlstring = "insert into calendars (calendar_name, calendar_description, calendar_location, calendar_createdate, calendar_deletedate) values ('$name','$description','$location',now(),'3000-01-01 00:00:00')";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	

	
	/* ----------------------------------------------- */
	/* --------- Edit -------------------------------- */
	/* ----------------------------------------------- */
	function Edit($id, $name, $description, $location) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($name == "") { EditForm("'<b>Calendar Name</b>' was blank, original values now displayed",$id); return; }
		if ($description == "") { EditForm("'<b>Description</b>' was blank, original values now displayed",$id); return; }
		if ($location == "") { EditForm("'<b>Location</b>' was blank, original values now displayed",$id); return; }
		
		/* if we get to this point, its safe to add to the database */
		$sqlstring = "update calendars set calendar_name = '$name', calendar_description = '$description', calendar_location = '$location' where calendar_id = '$id'";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	

	
	/* ----------------------------------------------- */
	/* --------- Delete ------------------------------ */
	/* ----------------------------------------------- */
	function Delete($id) {
		$sqlstring = "update calendars set calendar_deletedate = now() where calendar_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	
	
	
	/* ----------------------------------------------- */
	/* --------- AddForm ----------------------------- */
	/* ----------------------------------------------- */
	function AddForm($message, $name, $description, $location) {
	?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar_calendars.php" class="ui button">Back</a> to calendar list</td></tr></table><br>
		
		<form action="calendar_calendars.php" method="post" id="form1">
		<input type="hidden" name="action" value="add">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold">Add Calendar</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><? =$message?></td>
			</tr>
			<tr>
				<td class="label">Calendar Name<br><span class="tiny">short name</span></td>
				<td class="rightvalue"><input type="text" name="name" class="required" value="<? =$name?>"></td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td class="rightvalue"><input type="text" name="description" class="required" size="50" value="<? =$description?>"></td>
			</tr>
			<tr>
				<td class="label">Location</td>
				<td class="rightvalue"><input type="text" name="location" class="required" value="<? =$location?>"></td>
			</tr>
		</table>
		<p><input type="submit" value="Add" name="submit"></p>
		</form>
	<?
	}

	
	/* ----------------------------------------------- */
	/* --------- EditForm ---------------------------- */
	/* ----------------------------------------------- */
	function EditForm($message, $id) {
	
		$sqlstring = "select * from calendars where calendar_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['calendar_name'];
		$description = $row['calendar_description'];
		$location = $row['calendar_location'];
	?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar_calendars.php" class="ui button">Back</a> to calendar list</td></tr></table><br>
		
		<form action="calendar_calendars.php" method="post" id="form1">
		<input type="hidden" name="action" value="edit">
		<input type="hidden" name="id" value="<? =$id?>">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold">Edit Calendar</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><? =$message?></td>
			</tr>
			<tr>
				<td class="label">Calendar Name<br><span class="tiny">short name</span></td>
				<td class="rightvalue"><input type="text" name="name" class="required" value="<? =$name?>"></td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td class="rightvalue"><input type="text" name="description" class="required" size="50" value="<? =$description?>"></td>
			</tr>
			<tr>
				<td class="label">Location</td>
				<td class="rightvalue"><input type="text" name="location" class="required" value="<? =$location?>"></td>
			</tr>
		</table>
		<p><input type="submit" value="Save" name="submit"></p>
		</form>
	<?
	}
	
?>
	
<? include("footer.php") ?>
