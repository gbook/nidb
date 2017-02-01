<?
 // ------------------------------------------------------------------------------
 // NiDB adminprojects.php
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
		<title>NiDB - Manage Projects</title>
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
	$id = GetVariable("id");
	$projectname = GetVariable("projectname");
	$admin = GetVariable("admin");
	$pi = GetVariable("pi");
	$instanceid = GetVariable("instanceid");
	$sharing = GetVariable("sharing");
	$costcenter = GetVariable("costcenter");
	$startdate = GetVariable("startdate");
	$enddate = GetVariable("enddate");
	$datausers = GetVariable("datausers");
	$phiusers = GetVariable("phiusers");
	
	
	/* determine action */
	switch ($action) {
		case 'editform':
			DisplayProjectForm("edit", $id);
			break;
		case 'addform':
			DisplayProjectForm("add", "");
			break;
		case 'update':
			UpdateProject($id, $projectname, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers);
			DisplayProjectList();
			break;
		case 'add':
			AddProject($projectname, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers);
			DisplayProjectList();
			break;
		case 'delete':
			DeleteProject($id);
			break;
		default:
			DisplayProjectList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateProject ---------------------- */
	/* -------------------------------------------- */
	function UpdateProject($id, $projectname, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers) {
		/* perform data checks */
		$projectname = mysqli_real_escape_string($GLOBALS['linki'], $projectname);
		$admin = mysqli_real_escape_string($GLOBALS['linki'], $admin);
		$pi = mysqli_real_escape_string($GLOBALS['linki'], $pi);
		$sharing = mysqli_real_escape_string($GLOBALS['linki'], $sharing);
		$costcenter = mysqli_real_escape_string($GLOBALS['linki'], $costcenter);
		$startdate = mysqli_real_escape_string($GLOBALS['linki'], $startdate);
		$enddate = mysqli_real_escape_string($GLOBALS['linki'], $enddate);
		
		/* update the project */
		$sqlstring = "update projects set project_name = '$projectname', project_admin = '$admin', project_pi = '$pi', instance_id = '$instanceid', project_sharing = '$sharing', project_costcenter = '$costcenter', project_startdate = '$startdate', project_enddate = '$enddate' where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		/* delete all previous rows from the db for this project */
		$sqlstring = "delete from user_project where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* update/insert view rows */
		if (is_array($datausers)) {
			foreach ($datausers as $userid) {
				$sqlstring = "select * from user_project where user_id = $userid and project_id = $id";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_data = 1, view_phi = 0 where user_id = $userid and project_id = $id";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, view_data, view_phi) values ($userid, $id, 1, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert edit rows */
		if (is_array($phiusers)) {
			foreach ($phiusers as $userid) {
				$sqlstring = "select * from user_project where user_id = $userid and project_id = $id";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_phi = 1 where user_id = $userid and project_id = $id";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, view_data, view_phi) values ($userid, $id, 0, 1)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		?><div align="center"><span class="message"><?=$projectname?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddProject ------------------------- */
	/* -------------------------------------------- */
	function AddProject($projectname, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers) {
		/* perform data checks */
		$projectname = mysqli_real_escape_string($GLOBALS['linki'], $projectname);
		$admin = mysqli_real_escape_string($GLOBALS['linki'], $admin);
		$pi = mysqli_real_escape_string($GLOBALS['linki'], $pi);
		$sharing = mysqli_real_escape_string($GLOBALS['linki'], $sharing);
		$costcenter = mysqli_real_escape_string($GLOBALS['linki'], $costcenter);
		$startdate = mysqli_real_escape_string($GLOBALS['linki'], $startdate);
		$enddate = mysqli_real_escape_string($GLOBALS['linki'], $enddate);
		
		$projectuid = NIDB\CreateUID('P',4);
		
		/* insert the new project */
		$sqlstring = "insert into projects (project_uid, project_name, project_admin, project_pi, instance_id, project_sharing, project_costcenter, project_startdate, project_enddate, project_status) values ('$projectuid', '$projectname', '$admin', '$pi', '$instanceid', '$sharing', '$costcenter', '$startdate', '$enddate', 'active')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$projectname?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteProject ---------------------- */
	/* -------------------------------------------- */
	function DeleteProject($id) {
		$sqlstring = "delete from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProjectForm ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectForm($type, $id) {

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from projects where project_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			//$id = $row['project_id'];
			$name = $row['project_name'];
			$admin = $row['project_admin'];
			$pi = $row['project_pi'];
			$instanceid = $row['instance_id'];
			$costcenter = $row['project_costcenter'];
			$sharing = $row['project_sharing'];
			$startdate = $row['project_startdate'];
			$enddate = $row['project_enddate'];
		
			$formaction = "update";
			$formtitle = "Updating $name";
			$submitbuttonlabel = "Update";
		}
		else {
                        echo "<br>admin is $admin and pi is $pi </br>";
                        echo "<br>userid is $userid </br>";

			$formaction = "add";
			$formtitle = "Add new project";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Administration'] = "admin.php";
		$urllist['Projects'] = "adminprojects.php";
		$urllist[$name] = "adminprojects.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="adminprojects.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td class="label">Name</td>
				<td><input type="text" name="projectname" value="<?=$name?>" size="60" maxlength="60"></td>
			</tr>
			<tr>
				<td class="label">Instance</td>
				<td>
					<select name="instanceid" required>
						<option value="">Select Instance...</option>
					<?
						$sqlstring = "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "')) order by instance_name";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$instance_id = $row['instance_id'];
							$instance_uid = $row['instance_uid'];
							$instance_name = $row['instance_name'];
							if ($instanceid == $instance_id) { $selected = "selected"; } else { $selected = ''; }
							?>
							<option value="<?=$instance_id?>" <?=$selected?>><?=$instance_name?></option>
							<?
						}
					?>
					</select>
				</td>
			</tr>
			<? if ($type == "edit") { ?>
			<tr>
				<td class="label">Administrator</td>
				<td>
					<select name="admin">
						<?
							$sqlstring = "select * from users order by user_fullname, username";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$userid = $row['user_id'];
								$username = $row['username'];
								$fullname = $row['user_fullname'];
								if ($userid == $admin) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$userid?>" <?=$selected?>><?=$fullname?> (<?=$username?>)</option>
								<?
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Principle Investigator</td>
				<td>
					<select name="pi">
						<?
							$sqlstring = "select * from users order by user_fullname, username";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$userid = $row['user_id'];
								$username = $row['username'];
								$fullname = $row['user_fullname'];
								if ($userid == $pi) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$userid?>" <?=$selected?>><?=$fullname?> (<?=$username?>)</option>
								<?
							}
						?>
					</select>
				</td>
			</tr>
			<? } ?>
			<tr>
				<td class="label">Project Number<br><span class="tiny">Cost Center</span></td>
				<td><input type="text" name="costcenter" value="<?=$costcenter?>"></td>
			</tr>
			<tr>
				<td class="label">Start Date</td>
				<td><input type="text" name="startdate" value="<?=$startdate?>"></td>
			</tr>
			<tr>
				<td class="label">End Date</td>
				<td><input type="text" name="enddate" value="<?=$enddate?>"></td>
			</tr>
				<script type="text/javascript">
				$(document).ready(function() {
					$("#alldatausers").click(function() {
						var checked_status = this.checked;
						$(".datausers").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allphiusers").click(function() {
						var checked_status = this.checked;
						$(".phiusers").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allnoneprojects").click(function() {
						var checked_status = this.checked;
						$(".noneprojects").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
				});
				</script>
			<? if ($type == "edit") { ?>
			<tr>
				<td class="label" valign="top">User access</td>
				<td>
					<details>
						<summary>View user access list</summary>
					<table class="smallgraydisplaytable">
						<thead>
							<tr>
								<th>Data &nbsp;</th>
								<th>PHI &nbsp;</th>
								<th></th>
							</tr>
						</thead>
						<tr>
							<td valign="top"><input type="checkbox" id="alldatausers"></td>
							<td valign="top"><input type="checkbox" id="allphiusers"></td>
							<td>Select/unselect all<br><br></td>
						</tr>
				<?
					$bgcolor = "#EEFFEE";
					$sqlstring = "select * from users where user_id in (select user_id from user_instance where instance_id = $instanceid) order by username";
					//echo "$sqlstring<br>";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$user_id = $row['user_id'];
						$username = $row['username'];
						$user_fullname = $row['user_fullname'];
						
						$sqlstringA = "select * from user_project where user_id = $user_id and project_id = '$id'";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						if (mysqli_num_rows($resultA) > 0) {
							$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
							$view_data = $rowA['view_data'];
							$view_phi = $rowA['view_phi'];
							$access_none = $rowA['access_none'];
						}
						else {
							$view_data = "";
							$view_phi = "";
							$access_none = "";
						}

						?>
						<tr style="color: darkblue; font-size:11pt; /*background-color: <?=$bgcolor?>*/">
							<td class="datausers"><input type="checkbox" name="datausers[]" value="<?=$user_id?>" <?if ($view_data) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></td>
							<td class="phiusers"><input type="checkbox" name="phiusers[]" value="<?=$user_id?>" <?if ($view_phi) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></td>
							<td><tt><?=$username?></tt> - <?=$user_fullname?></td>
						</tr>
						<?
						if ($bgcolor == "#EEFFEE") { $bgcolor = "#FFFFFF"; }
						elseif ($bgcolor == "#FFFFFF") { $bgcolor = "#EEFFEE"; }
					}
					?>
					</table>
					</details>
				</td>
			</tr>
			<? } ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		<br><br><br>
			<? if ($type == "edit") { ?>
				<div style="font-size:11pt; color: #333;">
				Required protocols<br><br>
				<iframe src="adminprojectprotocols.php?projectid=<?=$id?>" width="70%" height="400px" frameborder="0"></iframe>
				<?
					$sqlstring = "select * from project_protocol where project_id = $id";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$user_id = $row['user_id'];
					}
				?>
				</div>
			<? } ?>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayProjectList ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectList() {
	
		$urllist['Administration'] = "admin.php";
		$urllist['Projects'] = "adminprojects.php";
		$urllist['Add Project'] = "adminprojects.php?action=addform";
		NavigationBar("Admin", $urllist);
		
		//$instanceid = GetInstanceID();
		$instancename = GetInstanceName($instanceid);
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<? if ($GLOBALS['issiteadmin']) { ?><th>Instance</th><? } ?>
				<th>UID</th>
				<th>Cost Center</th>
				<th>Admin</th>
				<th>PI</th>
				<th>Start date</th>
				<th>End date</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
			<!--<tr>
				<td class="subheader" colspan="8">Projects within <?=$instancename?></td>
			</tr>-->
			<?
				if ($GLOBALS['issiteadmin']) {
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname', d.instance_name from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id left join instance d on a.instance_id = d.instance_id where a.project_status = 'active' and a.instance_id = " . $_SESSION['instanceid'] . " order by a.project_name";
				}
				else {
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = " . $_SESSION['instanceid'] . " order by a.project_name";
				}
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['project_id'];
					$projectuid = $row['project_uid'];
					$name = $row['project_name'];
					$adminusername = $row['adminusername'];
					$adminfullname = $row['adminfullname'];
					$piusername = $row['piusername'];
					$pifullname = $row['pifullname'];
					$instancename = $row['instance_name'];
					$costcenter = $row['project_costcenter'];
					$startdate = $row['project_startdate'];
					$enddate = $row['project_enddate'];
					$irbapprovaldate = $row['project_irbapprovaldate'];
					$status = $row['project_status'];
					
					if (strtotime($enddate) < strtotime("now")) { $style="color: #666666"; } else { $style = ""; }
			?>
			<tr style="<?=$style?>">
				<td><a href="adminprojects.php?action=editform&id=<?=$id?>"><?=$name?></td>
				<? if ($GLOBALS['issiteadmin']) { ?><td class="tiny"><?=$instancename?></td><? } ?>
				<td><?=$projectuid?></td>
				<td><?=$costcenter?></td>
				<td><?=$adminfullname?></td>
				<td><?=$pifullname?></td>
				<td><?=$startdate?></td>
				<td><?=$enddate?></td>
				<td><?=$status?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
