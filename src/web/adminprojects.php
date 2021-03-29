<?
 // ------------------------------------------------------------------------------
 // NiDB adminprojects.php
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
		<title>NiDB - Manage Projects</title>
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
	$usecustomid = GetVariable("usecustomid");
	
	/* determine action */
	switch ($action) {
		case 'editform':
			DisplayProjectForm("edit", $id);
			break;
		case 'addform':
			DisplayProjectForm("add", "$username");
			break;
		case 'update':
			UpdateProject($id, $projectname, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers);
			DisplayProjectList();
			break;
		case 'add':
			AddProject($projectname, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers);
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
	function UpdateProject($id, $projectname, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers) {
		/* perform data checks */
		$projectname = mysqli_real_escape_string($GLOBALS['linki'], $projectname);
		$usecustomid = intval(mysqli_real_escape_string($GLOBALS['linki'], $usecustomid));
		$admin = mysqli_real_escape_string($GLOBALS['linki'], $admin);
		$pi = mysqli_real_escape_string($GLOBALS['linki'], $pi);
		$sharing = mysqli_real_escape_string($GLOBALS['linki'], $sharing);
		$costcenter = mysqli_real_escape_string($GLOBALS['linki'], $costcenter);
		$startdate = mysqli_real_escape_string($GLOBALS['linki'], $startdate);
		$enddate = mysqli_real_escape_string($GLOBALS['linki'], $enddate);
	
		/* update the project */
		$sqlstring = "update projects set project_name = '$projectname', project_usecustomid = '$usecustomid', project_admin = '$admin', project_pi = '$pi', instance_id = '$instanceid', project_sharing = '$sharing', project_costcenter = '$costcenter', project_startdate = '$startdate', project_enddate = '$enddate' where project_id = $id";
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
					$sqlstring = "insert into user_project (user_id, project_id, view_data, view_phi, write_data, write_phi) values ($userid, $id, 1, 0, 0, 0)";
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
					$sqlstring = "insert into user_project (user_id, project_id, view_data, view_phi, write_data, write_phi) values ($userid, $id, 0, 1, 0, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		Notice("title", "$projectname updated");
	}


	/* -------------------------------------------- */
	/* ------- AddProject ------------------------- */
	/* -------------------------------------------- */
	function AddProject($projectname, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers) {
		/* perform data checks */
		$projectname = mysqli_real_escape_string($GLOBALS['linki'], trim($projectname));
		$usecustomid = intval(mysqli_real_escape_string($GLOBALS['linki'], trim($usecustomid)));
		$admin = mysqli_real_escape_string($GLOBALS['linki'], trim($admin));
		$pi = mysqli_real_escape_string($GLOBALS['linki'], trim($pi));
		$sharing = mysqli_real_escape_string($GLOBALS['linki'], trim($sharing));
		$costcenter = mysqli_real_escape_string($GLOBALS['linki'], trim($costcenter));
		$startdate = mysqli_real_escape_string($GLOBALS['linki'], trim($startdate));
		$enddate = mysqli_real_escape_string($GLOBALS['linki'], trim($enddate));
		
		if ($startdate == "") { $startdate = "0000-00-00"; }
		if ($enddate == "") { $enddate = "0000-00-00"; }
		
		$projectuid = NIDB\CreateUID('P',4);
	
		// echo "project_admin: $admin, PI $pi";	
		/* insert the new project */
		$sqlstring = "insert into projects (project_uid, project_name, project_usecustomid, project_admin, project_pi, instance_id, project_sharing, project_costcenter, project_startdate, project_enddate, project_status) values ('$projectuid', '$projectname', '$usecustomid', '$admin', '$pi', '$instanceid', '$sharing', '$costcenter', '$startdate', '$enddate', 'active')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("title", "$projectname added");
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
			$usecustomid = $row['project_usecustomid'];
		
			$formaction = "update";
			$formtitle = "$name";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new project";
			$submitbuttonlabel = "Add";

			// find userid, added Feb 1, 2017, OOO
			$username = $id; // username and id are different things but i used it just not to change the old code too much
			$sqlstring = "select * from users where username = '$username'";
                	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                	if (mysqli_num_rows($result) > 0) {
                        	$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                        	$userid = $row['user_id'];
			}
		}
		
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>
			<form method="post" action="adminprojects.php" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">

			<div class="two fields">
				<div class="field">
					<label>Name</label>
					<div class="field">
						<input type="text" name="projectname" value="<?=$name?>" maxlength="255" required>
					</div>
				</div>
				
				<div class="field">
					<label>Project number</label>
					<div class="field">
						<input type="text" name="costcenter" value="<?=$costcenter?>" maxlength="255" required placeholder="6 digit cost center">
					</div>
				</div>
			</div>
			
			<div class="field">
				<label>Use Custom IDs?</label>
				<div class="field">
					<input type="checkbox" name="usecustomid" value="1" <? if ($usecustomid) { echo "checked"; } ?>>
				</div>
			</div>

			<div class="field">
				<label>Instance</label>
				<div class="field">
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
							?><option value="<?=$instance_id?>" <?=$selected?>><?=$instance_name?></option><?
						}
					?>
					</select>
				</div>
			</div>

			<div class="field">
				<label>Principle Investigator</label>
				<div class="field">
					<select name="pi">
						<option value="">Select Principal Investigator...</option>
						<?
							$sqlstring = "select * from users WHERE username NOT LIKE '' order by user_fullname, username";
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
				</div>
			</div>
			<div class="field">
				<label>Administrator</label>
				<div class="field">
					<select name="admin">
						<option value="">Select Administrator...</option>
						<?
							$sqlstring = "select * from users WHERE username NOT LIKE '' order by user_fullname, username";
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
				</div>
			</div>
			<div class="field">
				<label>Start Date</label>
				<div class="field">
					<input type="text" name="startdate" value="<?=$startdate?>">
				</div>
			</div>
			<div class="field">
				<label>End Date</label>
				<div class="field">
					<input type="text" name="enddate" value="<?=$enddate?>">
				</div>
			</div>
			<div align="right">
				<button class="ui button" onClick="window.location.href='adminprojects.php'; return false;">Cancel</button>
				<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
			</div>
			</form>
		</div>

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
			<!--
			<tr>
				<td class="label" valign="top">User access</td>
				<td>
					<details>
						<summary>View user access list</summary>
					<table class="ui very small very compact celled selectable grey table">
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
			</tr>-->
			<? } ?>
			</form>
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
	?>

	<div style="padding: 0px 50px">
	<button class="ui primary large button" onClick="window.location.href='adminprojects.php?action=addform'; return false;"><i class="plus square outline icon"></i> Create Project</button>
	<br><br>
	
	<h3 class="ui header">Projects</h3>
	<table class="ui small celled selectable grey compact table">
		<thead>
			<th>Name</th>
			<? if ($GLOBALS['issiteadmin']) { ?><th>Instance</th><? } ?>
			<th>UID</th>
			<th>Cost Center</th>
			<th>Admin</th>
			<th>PI</th>
			<th>Start date</th>
			<th>End date</th>
			<th>Status</th>
		</thead>
		<tbody>
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
