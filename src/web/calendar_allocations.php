<?
 // ------------------------------------------------------------------------------
 // NiDB calendar_allocations.php
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

<HTML><HEAD><TITLE>Calendar - Allocations</TITLE></HEAD>
<body style="font-family:arial, helvetica, sans-serif">
<? $system = "calendar"; ?>
<? $section = "calendar"; ?>
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
?>
<br><br>
<?
	mysqli_select_db("calendar") or die ("Could not select database<br>");
	
	/* get variables */
	if ($_POST["action"] == "") { $action = $_GET["action"]; } else { $action = $_POST["action"]; }
	if ($_POST["id"] == "") { $id = $_GET["id"]; } else { $id = $_POST["id"]; }
	if ($_POST["amount"] == "") { $amount = $_GET["amount"]; } else { $amount = $_POST["amount"]; }
	if ($_POST["timeperiod"] == "") { $timeperiod = $_GET["timeperiod"]; } else { $timeperiod = $_POST["timeperiod"]; }
	if ($_POST["calendar_id"] == "") { $calendar_id = $_GET["calendar_id"]; } else { $calendar_id = $_POST["calendar_id"]; }
	if ($_POST["project_id"] == "") { $project_id = $_GET["project_id"]; } else { $project_id = $_POST["project_id"]; }

	/* check the action */
	if ($action == "addform") {
		AddForm("",$amount, $timeperiod, $calendar_id, $project_id);
	}
	if ($action == "add") {
		Add($amount, $timeperiod, $calendar_id, $project_id);
	}
	elseif ($action == "editform") {
		EditForm("",$id);
	}
	elseif ($action == "edit") {
		Edit($id, $amount, $timeperiod, $calendar_id, $project_id);
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
		<style>
			.header { font-weight: bold; color: darkblue; border-top: 2px solid gray; border-bottom: 1px solid gray;}
		</style>
		<table><tr><td><img src="images/back16.png"></td><td><a href="index.php" class="link">Back</a> to Calendar</td></tr></table><br>
		<br>
		<table width="100%">
			<tr>
				<td>
					<img src="images/add16.png"> <a href="calendar_allocations.php?action=addform" class="link">Add Allocation</a>
				</td>
				<td align="right">
					<img src="images/refresh16.png"> <a href="calendar_allocations.php" class="link">Refresh Page</a>
				</td>
			</tr>
		</table>
		
		<br><br>
		
		<table width="100%" cellspacing="0" cellpadding="3">
		<tr>
			<td class="header">Project</td>
			<td class="header">Calendar</td>
			<td class="header">Allocation</td>
			<td class="header" align="center"><span style="font-weight: normal;">Delete</span></td>
		</tr>
		<?
		$sqlstring = "select a.*, b.*, c.* from allocations a left join calendars b on a.alloc_calendarid = b.calendar_id left join calendar_projects c on a.alloc_projectid = c.project_id";
		//$sqlstring = "select * from calendars where calendar_deletedate > now()";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['alloc_id'];
			$calendarname = $row['calendar_name'];
			$projectname = $row['project_name'];
			$timeperiod = $row['alloc_timeperiod'];
			$amount = $row['alloc_amount'];

			$timeperiod = str_replace("daily","day",$timeperiod);
			$timeperiod = str_replace("weekly","week",$timeperiod);
			$timeperiod = str_replace("monthly","month",$timeperiod);
			$timeperiod = str_replace("yearly","year",$timeperiod);
			
			?>
			<tr>
				<td><?=$projectname?></td>
				<td><?=$calendarname?></td>
				<td><?=$amount?> hours per <?=$timeperiod?> days</td>
				<td align="center"><a href="calendar_allocations.php?action=delete&id=<?=$id?>" style="color: red; text-decoration: underline">X</a></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}
	
	
	/* ----------------------------------------------- */
	/* --------- Add --------------------------------- */
	/* ----------------------------------------------- */
	function Add($amount, $timeperiod, $calendar_id, $project_id) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($amount == "") { AddForm("'Allocation Amount' is blank",$amount, $timeperiod, $calendar_id, $project_id); return; }
		
		/* if we get to this point, its safe to add to the database */
		$sqlstring = "insert into allocations (alloc_timeperiod, alloc_calendarid, alloc_projectid, alloc_amount) values ('$timeperiod','$calendar_id','$project_id','$amount')";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	

	
	/* ----------------------------------------------- */
	/* --------- Edit -------------------------------- */
	/* ----------------------------------------------- */
	function Edit($id, $amount, $timeperiod, $calendar_id, $project_id) {
		
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
		$sqlstring = "delete from allocations where alloc_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	
	
	
	/* ----------------------------------------------- */
	/* --------- AddForm ----------------------------- */
	/* ----------------------------------------------- */
	function AddForm($message, $amount, $timeperiod, $calendar_id, $project_id) {
	?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar_allocations.php" class="link">Back</a> to allocation list</td></tr></table><br>
		
		<form action="calendar_allocations.php" method="post" id="form1">
		<input type="hidden" name="action" value="add">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold">Add Allocation</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><?=$message?></td>
			</tr>
			<tr>
				<td class="label">Allocation per time period<br><span class="tiny">ex: 10 hours per month</span></td>
				<td class="rightvalue">
					<input type="text" name="amount" size="3" maxlength="3"> hours per 
					<select name="timeperiod">
						<option value="1">Day
						<option value="7">Week
						<option value="30" selected>Month
						<option value="365">Year
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Calendar</td>
				<td class="rightvalue">
					<select name="calendar_id">
					<?
						$sqlstring = "select calendar_id, calendar_name from calendars where calendar_deletedate > now()";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$id = $row['calendar_id'];
							$name = $row['calendar_name'];
					?>
						<option value="<?=$id?>"><?=$name?>
					<?
						}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Project</td>
				<td class="rightvalue">
					<select name="project_id">
					<?
						$sqlstring = "select project_id, project_name from calendar_projects where project_enddate > now()";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$id = $row['project_id'];
							$name = $row['project_name'];
					?>
						<option value="<?=$id?>"><?=$name?>
					<?
						}
					?>
					</select>
				</td>
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
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar_allocations.php" class="link">Back</a> to allocation list</td></tr></table><br>
		
		<form action="calendar_allocations.php" method="post" id="form1">
		<input type="hidden" name="action" value="edit">
		<input type="hidden" name="id" value="<?=$id?>">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold">Edit Calendar</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><?=$message?></td>
			</tr>
			<tr>
				<td class="label">Calendar Name<br><span class="tiny">short name</span></td>
				<td class="rightvalue"><input type="text" name="name" class="required" value="<?=$name?>"></td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td class="rightvalue"><input type="text" name="description" class="required" size="50" value="<?=$description?>"></td>
			</tr>
			<tr>
				<td class="label">Location</td>
				<td class="rightvalue"><input type="text" name="location" class="required" value="<?=$location?>"></td>
			</tr>
		</table>
		<p><input type="submit" value="Save" name="submit"></p>
		</form>
	<?
	}
	
?>
	
<? include("footer.php") ?>
