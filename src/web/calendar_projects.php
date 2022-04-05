<?
 // ------------------------------------------------------------------------------
 // NiDB calendar_projects.php
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
	session_start();
?>
<HTML><HEAD><TITLE>Calendar - Projects</TITLE></HEAD>
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
	if ($_POST["name"] == "") { $name = $_GET["name"]; } else { $name = $_POST["name"]; }
	if ($_POST["admin"] == "") { $admin = $_GET["admin"]; } else { $admin = $_POST["admin"]; }
	if ($_POST["description"] == "") { $description = $_GET["description"]; } else { $description = $_POST["description"]; }

	/* check the action */
	if ($action == "addform") {
		AddForm("",$name, $description, $admin);
	}
	if ($action == "add") {
		Add($name, $description, $admin);
	}
	elseif ($action == "editform") {
		EditForm("",$id);
	}
	elseif ($action == "edit") {
		Edit($id, $name, $description, $admin);
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
		<table><tr><td><img src="images/back16.png"></td><td><a href="index.php" class="ui button">Back</a> to Calendar</td></tr></table><br>
		<br>
		<table width="100%">
			<tr>
				<td>
					<img src="images/add16.png"> <a href="calendar_projects.php?action=addform" class="ui button">Add Project</a>
				</td>
				<td align="right">
					<img src="images/refresh16.png"> <a href="calendar_projects.php" class="ui button">Refresh Page</a>
				</td>
			</tr>
		</table>
		
		<br><br>
		
		<table width="100%" cellspacing="0" cellpadding="3">
		<tr>
			<td class="header">Name</td>
			<td class="header">Description</td>
			<td class="header">Admin</td>
			<td class="header" align="center"><span style="font-weight: normal;">Delete</span></td>
		</tr>
		<?
		$sqlstring = "select * from projects where project_enddate > now()";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['project_id'];
			$name = $row['project_name'];
			$admin = $row['project_admin'];
			$description = $row['project_description'];
			?>
			<tr>
				<td><a href="calendar_projects.php?action=editform&id=<?=$id?>" class="ui button"><?=$name?></a></td>
				<td><?=$description?></td>
				<td><?=$admin?></td>
				<td align="center"><a href="calendar_projects.php?action=delete&id=<?=$id?>" style="color: red; text-decoration: underline"><i class="trash icon"></i></a></td>
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
	function Add($name, $description, $admin) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($name == "") { AddForm("'Calendar Name' is blank",$name, $description, $admin); return; }
		if ($description == "") { AddForm("'Description' is blank",$name, $description, $admin); return; }
		if ($admin == "") { AddForm("'Admin Username' is blank",$name, $description, $admin); return; }

		$name = mysqli_real_escape_string($GLOBALS['linki'], $name);
		$description = mysqli_real_escape_string($GLOBALS['linki'], $description);
		$admin = mysqli_real_escape_string($GLOBALS['linki'], $admin);
		
		/* if we get to this point, its safe to add to the database */
		$sqlstring = "insert into projects (project_name, project_description, project_admin, project_startdate, project_enddate) values ('$name','$description','$admin',now(),'3000-01-01 00:00:00')";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	

	
	/* ----------------------------------------------- */
	/* --------- Edit -------------------------------- */
	/* ----------------------------------------------- */
	function Edit($id, $name, $description, $admin) {
		
		/* check if any form elements are bad, if so redisplay the addform */
		if ($name == "") { EditForm("'<b>Calendar Name</b>' was blank, original values now displayed",$id); return; }
		if ($description == "") { EditForm("'<b>Description</b>' was blank, original values now displayed",$id); return; }
		if ($admin == "") { EditForm("'<b>Admin Username</b>' was blank, original values now displayed",$id); return; }
		
		$name = mysqli_real_escape_string($GLOBALS['linki'], $name);
		$description = mysqli_real_escape_string($GLOBALS['linki'], $description);
		$admin = mysqli_real_escape_string($GLOBALS['linki'], $admin);
		
		/* if we get to this point, its safe to add to the database */
		$sqlstring = "update projects set project_name = '$name', project_description = '$description', project_admin = '$admin' where project_id = '$id'";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	


	/* ----------------------------------------------- */
	/* --------- Delete ------------------------------ */
	/* ----------------------------------------------- */
	function Delete($id) {
		$sqlstring = "update projects set project_enddate = now() where project_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		DisplayList();
	}	

	
	/* ----------------------------------------------- */
	/* --------- AddForm ----------------------------- */
	/* ----------------------------------------------- */
	function AddForm($message, $name, $description, $admin) {
	?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar_projects.php" class="ui button">Back</a> to project list</td></tr></table><br>
		
		<form action="calendar_projects.php" method="post" id="form1">
		<input type="hidden" name="action" value="add">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold">Add Project</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><?=$message?></td>
			</tr>
			<tr>
				<td class="label">Project Name<br><span class="tiny">short name</span></td>
				<td class="rightvalue"><input type="text" name="name" class="required" value="<?=$name?>"></td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td class="rightvalue"><input type="text" name="description" class="required" size="50" value="<?=$description?>"></td>
			</tr>
			<tr>
				<td class="label">Admin Username</td>
				<td class="rightvalue"><input type="text" name="admin" class="required" value="<?=$admin?>"></td>
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
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		$description = $row['project_description'];
		$admin = $row['project_admin'];
	?>
		<table><tr><td><img src="images/back16.png"></td><td><a href="calendar_projects.php" class="ui button">Back</a> to project list</td></tr></table><br>
		
		<form action="calendar_projects.php" method="post" id="form1">
		<input type="hidden" name="action" value="edit">
		<input type="hidden" name="id" value="<?=$id?>">
		
		<table cellspacing="0" cellpadding="5" class="editor">
			<tr>
				<td colspan="3" style="color: darkblue; font-size: 14pt; text-align:center; font-weight: bold">Edit Project</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="color:red"><?=$message?></td>
			</tr>
			<tr>
				<td class="label">Project Name<br><span class="tiny">short name</span></td>
				<td class="rightvalue"><input type="text" name="name" class="required" value="<?=$name?>"></td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td class="rightvalue"><input type="text" name="description" class="required" size="50" value="<?=$description?>"></td>
			</tr>
			<tr>
				<td class="label">Admin Username</td>
				<td class="rightvalue"><input type="text" name="admin" class="required" value="<?=$admin?>"></td>
			</tr>
		</table>
		<p><input type="submit" value="Save" name="submit"></p>
		</form>
	<?
	}
	
?>
	
<? include("footer.php") ?>
