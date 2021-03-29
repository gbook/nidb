<?
 // ------------------------------------------------------------------------------
 // NiDB adminusers.php
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
		<title>NiDB - Manage Users</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	//require "nidbapi.php";
	require "menu.php";

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpscriptname = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpscriptname))
		$selfcall = true;
	else
		$selfcall = false;

	//PrintVariable($_POST,'post');
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$username = GetVariable("username");
	$password = GetVariable("password");
	$fullname = GetVariable("fullname");
	$email = GetVariable("email");
	$enabled = GetVariable("enabled");
	$isadmin = GetVariable("isadmin");
	$instanceid = GetVariable("instanceid");
	$projectadmin = GetVariable("projectadmin");
	$modifydata = GetVariable("modifydata");
	$viewdata = GetVariable("viewdata");
	$modifyphi = GetVariable("modifyphi");
	$viewphi = GetVariable("viewphi");
	
	/* determine action */
	switch ($action) {
		case 'editform':
			DisplayUserForm("edit", $id);
			break;
		case 'addform':
			DisplayUserForm("add", "");
			break;
		case 'enable':
			EnableUser($id);
			DisplayUserList();
			break;
		case 'disable':
			DisableUser($id);
			DisplayUserList();
			break;
		case 'update':
			UpdateUser($id, $username, $password, $fullname, $email, $enabled, $isadmin, $instanceid, $projectadmin, $modifydata, $viewdata, $modifyphi, $viewphi);
			DisplayUserList();
			break;
		case 'add':
			AddUser($username, $password, $fullname, $email, $enabled, $isadmin, $instanceid);
			DisplayUserList();
			break;
		case 'delete':
			DeleteUser($id);
			DisplayUserList();
			break;
		default:
			DisplayUserList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateUser ------------------------- */
	/* -------------------------------------------- */
	function UpdateUser($id, $username, $password, $fullname, $email, $enabled, $isadmin, $instanceid, $projectadmin, $modifydata, $viewdata, $modifyphi, $viewphi) {
		/* perform data checks */
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$fullname = mysqli_real_escape_string($GLOBALS['linki'], $fullname);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		$isadmin = mysqli_real_escape_string($GLOBALS['linki'], $isadmin) + 0;
		$enabled = mysqli_real_escape_string($GLOBALS['linki'], $enabled) + 0;

		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* determine their current login type */
		$sqlstring = "select login_type from users where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$logintype = $row['login_type'];
		if ($logintype != "Standard") {
			$logintype = "NIS";
		}
		
		/* update the user */
		$sqlstring = "update users set username = '$username'";
		if ($password != "") { $sqlstring .= ", password = sha1('$password')"; }
		$sqlstring .= ", user_fullname = '$fullname', user_email = '$email', user_enabled = '$enabled', user_isadmin = '$isadmin', login_type = '$logintype' where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* delete all previous rows from the user_instance table for this user */
		$sqlstring = "delete from user_instance where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		/* and then insert the new user_instance rows */
		foreach ($instanceid as $instid) {
			$sqlstring = "insert into user_instance (user_id, instance_id, isdefaultinstance, instance_joinrequest) values ($id, $instid, 0, 0)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* delete all previous rows from the user_project table for this user */
		$sqlstring = "delete from user_project where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* update/insert modify data rows */
		if (count($projectadmin) > 0) {
			foreach ($projectadmin as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set project_admin = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, project_admin, write_data, view_data, write_phi, view_phi) values ($id, $projectid, 1, 0, 0, 0, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert modify data rows */
		if (count($modifydata) > 0) {
			foreach ($modifydata as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set write_data = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, project_admin, write_data, view_data, write_phi, view_phi) values ($id, $projectid, 0, 1, 0, 0, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert view data rows */
		if (count($viewdata) > 0) {
			foreach ($viewdata as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_data = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, project_admin, write_data, view_data, write_phi, view_phi) values ($id, $projectid, 0, 0, 1, 0, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert modify phi rows */
		if (count($modifyphi) > 0) {
			foreach ($modifyphi as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set write_phi = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, project_admin, write_data, view_data, write_phi, view_phi) values ($id, $projectid, 0, 0, 0, 1, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert view phi rows */
		if (count($viewphi) > 0) {
			foreach ($viewphi as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_phi = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, project_admin, write_data, view_data, write_phi, view_phi) values ($id, $projectid, 0, 0, 0, 0, 1)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* commit transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		Notice("$username updated");
	}


	/* -------------------------------------------- */
	/* ------- AddUser ---------------------------- */
	/* -------------------------------------------- */
	function AddUser($username, $password, $fullname, $email, $enabled, $isadmin, $instanceid) {
		/* perform data checks */
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$fullname = mysqli_real_escape_string($GLOBALS['linki'], $fullname);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		$enabled = mysqli_real_escape_string($GLOBALS['linki'], $enabled) + 0;
		$isadmin = mysqli_real_escape_string($GLOBALS['linki'], $isadmin) + 0;
		
		/* determine their current login type */
		$logintype = "Standard";
		
		/* insert the new user */
		$sqlstring = "insert into users (username, password, login_type, user_instanceid, user_fullname, user_firstname, user_midname, user_lastname, user_institution, user_country, user_email, user_email2, user_address1, user_address2, user_city, user_state, user_zip, user_phone1, user_phone2, user_website, user_dept, user_lastlogin, user_logincount, user_enabled, user_isadmin, sendmail_dailysummary) values ('$username', sha1('$password'), '$logintype','" . $_SESSION['instanceid'] . "', '$fullname', '', '', '', '', '', '$email', '', '', '', '', '', '', '', '', '', '', now(), 0, 1, '$isadmin', 0)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$id = mysqli_insert_id($GLOBALS['linki']);
		
		/* and then insert the new user_instance rows */
		//foreach ($instanceid as $instid) {
			$sqlstring = "insert into user_instance (user_id, instance_id, isdefaultinstance, instance_joinrequest) values ($id, " . $_SESSION['instanceid'] . ", 0, 0)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//}
		
		/* don't assign any permissions to a new user by default, it must be done manually */

		Notice("$username added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteUser ------------------------- */
	/* -------------------------------------------- */
	function DeleteUser($id) {
		$sqlstring = "update users set user_deleted = 1, user_enabled = 0 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		$username = GetUsernameFromID($id);
		Notice("$username deleted");
	}


	/* -------------------------------------------- */
	/* ------- EnableUser ------------------------- */
	/* -------------------------------------------- */
	function EnableUser($id) {
		$sqlstring = "update users set user_enabled = 1 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		$username = GetUsernameFromID($id);
		Notice("$username enabled");
	}


	/* -------------------------------------------- */
	/* ------- DisableUser ------------------------ */
	/* -------------------------------------------- */
	function DisableUser($id) {
		$sqlstring = "update users set user_enabled = 0 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		$username = GetUsernameFromID($id);
		Notice("$username disabled");
	}


	/* -------------------------------------------- */
	/* ------- MakeAdminUser ---------------------- */
	/* -------------------------------------------- */
	function MakeAdminUser($id) {
		$sqlstring = "update users set user_isadmin = 1 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		$username = GetUsernameFromID($id);
		Notice("$username set as admin");
	}


	/* -------------------------------------------- */
	/* ------- MakeNotAdminUser ------------------- */
	/* -------------------------------------------- */
	function MakeNotAdminUser($id) {
		$sqlstring = "update users set user_isadmin = 0 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		$username = GetUsernameFromID($id);
		Notice("$username unset as admin");
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayUserForm -------------------- */
	/* -------------------------------------------- */
	function DisplayUserForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from users where user_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$username = $row['username'];
			$email = $row['user_email'];
			$fullname = $row['user_fullname'];
			$login_type = $row['login_type'];
			//$instanceid = $row['user_instanceid'];
			$enabled = $row['user_enabled'];
			$isadmin = $row['user_isadmin'];
			if ($enabled == 1) $enabledcheck = "checked";
			if ($isadmin == 1) $isadmincheck = "checked";
		
			$formaction = "update";
			$formtitle = "Updating $username";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new user to this instance";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<style type="text/css">
			td label { 
			   display: block;
			}
			.checkcell {
				border-left: 1px solid #ccc;
			}
		</style>
		<div class="ui text container">
			<div class="ui attached visible message">
			  <div class="header"><?=$formtitle?></div>
			</div>

			<form method="post" action="adminusers.php" autocomplete="off" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">

			<h3 class="ui header">User Information</h3>
			
			<? if ($type != 'edit') { ?>
			<div class="field">
				<label>Username</label>
				<div class="field">
					<input type="text" name="username" value="<?=$username?>">
				</div>
			</div>
			<? } ?>

			<div class="field">
				<label>Full name</label>
				<div class="field">
					<input type="text" name="fullname" value="<?=$fullname?>" required placeholder="Full name">
				</div>
			</div>
			<? if (($login_type == "Standard") || ($type == "add")) { ?>
			<div class="two fields">
				<div class="field">
					<label>Password</label>
					<div class="field">
						<input type="password" name="password" id="password" autocomplete="new-password">
					</div>
				</div>
				
				<div class="field">
					<label>Re-enter password</label>
					<div class="field">
						<input type="password" name="password-check" id="password-check" autocomplete="new-password">
					</div>
				</div>
			</div>
			<? } ?>
			<div class="field">
				<label>Email</label>
				<div class="field">
					<input type="text" name="email" value="<?=$email?>" required placeholder="Email">
				</div>
			</div>
			<div class="field">
				<label>Enabled</label>
				<div class="field">
					<input type="checkbox" name="enabled" value="1" <?=$enabledcheck?>>
				</div>
			</div>
			<div class="field">
				<label>NiDB admin</label>
				<div class="field">
					<input type="checkbox" name="isadmin" value="1" <?=$isadmincheck?>>
				</div>
			</div>

			<? if ($type == 'edit') { ?>
				<script type="text/javascript">
				$(document).ready(function() {
					/* check the matching passwords */
					$("#submit").click(function(){
						$(".error").hide();
						var hasError = false;
						var passwordVal = $("#password").val();
						var checkVal = $("#password-check").val();

						if (passwordVal != checkVal ) {
							$("#password-check").after('<span class="error">Passwords do not match.</span>');
							hasError = true;
						}
						if(hasError == true) {return false;}
					});
					$("#allprojectadmin").click(function() {
						var checked_status = this.checked;
						$(".projectadmin").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allmodifydata").click(function() {
						var checked_status = this.checked;
						$(".modifydata").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allviewdata").click(function() {
						var checked_status = this.checked;
						$(".viewdata").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allmodifyphi").click(function() {
						var checked_status = this.checked;
						$(".modifyphi").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allviewphi").click(function() {
						var checked_status = this.checked;
						$(".viewphi").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					/* show/hide projects for each instance */
					$(".instances").click(function() {
						if (this.checked) {
							$(".chkInstance" + this.value).attr("disabled",false);
							$(".projects" + this.value).css("background-color","#fff");
							$(".projects" + this.value).css("color", 'darkblue');
						}
						else {
							$(".chkInstance" + this.value).attr("disabled",true);
							$(".projects" + this.value).css("background-color", '#eee');
							$(".projects" + this.value).css("color", '#777');
						}
					});
				});
				</script>
				
			<h3 class="ui header">Project Permissions</h3>
			<table class="ui small celled selectable grey compact table">
				<thead>
					<th></th>
					<th></th>
					<th colspan="2" align="center">Data</th>
					<th colspan="2" align="center">PHI/PII</th>
				</thead>
				<tbody>
					<tr>
						<td></td>
						<td>
							Project admin <i class="question circle outline icon" title="<b>Project admin</b><br><br>User has the following permissions for the selected projects:<ul><li>Assign admin permissions to users<li>Modify all data<li>View all data<li>Modify PHI/PII<li>View PHI/PII"></i>
						</td>
						<td>
							Modify <i class="question circle outline icon" title="User has permissions to modify, upload/import data, delete subjects/studies/series. Excluding PHI/PII"></i>
						</td>
						<td title="User has permissions to view all data, excluding PHI/PII">View</td>
						<td title="User has permissions to modify PHI/PII">Modify</td>
						<td title="User has permissions to view, but not modify PHI/PII">View</td>
					</tr>
					<tr>
						<td>Select/unselect all</td>
						<td class="checkcell"><label><input type="checkbox" id="allprojectadmin"></label></td>
						<td class="checkcell"><label><input type="checkbox" id="allmodifydata"></label></td>
						<td class="checkcell"><label><input type="checkbox" id="allviewdata"></label></td>
						<td class="checkcell"><label><input type="checkbox" id="allmodifyphi"></label></td>
						<td class="checkcell"><label><input type="checkbox" id="allviewphi"></label></td>
					</tr>
				<?
					$sqlstring = "select * from user_instance where user_id = '$id'";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$instanceids[] = $row['instance_id'];
					}
					
					/* start listing all instances and projects */
					$sqlstring = "select * from instance order by instance_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$instance_id = $row['instance_id'];
						$instance_uid = $row['instance_uid'];
						$instance_name = $row['instance_name'];
						
						$checked = "";
						if (is_array($instanceids)) {
							if (in_array($instance_id,$instanceids)) {	
								$checked = "checked";
							}
						}
						else {
							if ($instanceids == $instance_id) {
								$checked = "checked";
							}
						}
						?>
						<tr>
							<td colspan="7"><label><input type="checkbox" value="<?=$instance_id?>" name="instanceid[]" class="instances" id="instance<?=$instance_id;?>" <?=$checked?>> <b><?=$instance_name?></b></label></td>
						</tr>
						<?
							$bgcolor = "#EEFFEE";
							$sqlstringA = "select * from projects where instance_id = $instance_id order by project_name";
							$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
							while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
								$project_id = $rowA['project_id'];
								$project_name = $rowA['project_name'];
								$project_costcenter = $rowA['project_costcenter'];
								
								if ($id != "") {
									$sqlstringB = "select * from user_project where user_id = $id and project_id = $project_id";
									$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
									if (mysqli_num_rows($resultB) > 0) {
										$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
										$project_admin = $rowB['project_admin'];
										$view_data = $rowB['view_data'];
										$view_phi = $rowB['view_phi'];
										$write_data = $rowB['write_data'];
										$write_phi = $rowB['write_phi'];
									}
									else {
										$project_admin = "";
										$view_data = "";
										$view_phi = "";
										$write_data = "";
										$write_phi = "";
									}
								}

								?>
								<tr style="color: darkblue; font-size:11pt;" class="projects<?=$instance_id?>">
									<td><?=$project_name?> (<tt><?=$project_costcenter?></tt>)</td>
									<td class="projectadmin checkcell">
										<label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="projectadmin[]" value="<?=$project_id?>" <?if ($project_admin) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></label>
									</td>
									<td class="modifydata checkcell">
										<label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="modifydata[]" value="<?=$project_id?>" <?if ($write_data) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></label>
									</td>
									<td class="viewdata checkcell">
										<label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="viewdata[]" value="<?=$project_id?>" <?if ($view_data) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></label>
									</td>
									<td class="modifyphi checkcell">
										<label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="modifyphi[]" value="<?=$project_id?>" <?if ($write_phi) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></label>
									</td>
									<td class="viewphi checkcell">
										<label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="viewphi[]" value="<?=$project_id?>" <?if ($view_phi) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?>></label>
									</td>
								</tr>
								<?
								if ($bgcolor == "#EEFFEE") { $bgcolor = "#FFFFFF"; }
								elseif ($bgcolor == "#FFFFFF") { $bgcolor = "#EEFFEE"; }
							}
						}
					?>
					</table>
			<? } ?>
			<br><br>
			<div class="ui two column grid">
				<div class="column">
					<? if ($type == 'edit') { ?>
						<input type="hidden" name="username" value="<?=$username?>">
						<button class="ui red button" onClick="window.location.href='adminusers.php?action=delete&id=<?=$id?>'; return false;"><i class="minus square outline icon"></i>Delete User</button>
					<? } ?>
				</div>
				<div class="column" align="right">
					<button class="ui button" onClick="window.location.href='adminusers.php'; return false;">Cancel</button>
					<input class="ui primary button" type="submit" id="submit" value="<?=$submitbuttonlabel?>">
				</div>
			</div>
			</form>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayUserList -------------------- */
	/* -------------------------------------------- */
	function DisplayUserList() {
	?>

	<div style="padding: 0px 50px">
	<button class="ui primary large button" onClick="window.location.href='adminusers.php?action=addform'; return false;"><i class="plus square outline icon"></i>Add User</button>
	<br><br>
	
	<div class="ui top attached tabular menu large">
		<a class="item active" data-tab="first">Users in the <?=$_SESSION['instancename']?> Instance</a>
		<a class="item" data-tab="second">All Other Users</a>
		<a class="item" data-tab="third">Deleted Users</a>
	</div>
	<div class="ui bottom attached tab segment active" data-tab="first">
		<table class="ui celled selectable compact table">
			<thead>
				<th align="left">Username</th>
				<th>Full name</th>
				<th>Email</th>
				<th>Login type</th>
				<th>Last Login</th>
				<th>Login Count</th>
				<th>Enabled</th>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from users a left join user_instance b on a.user_id = b.user_id where b.instance_id = '" . $_SESSION['instanceid'] . "' and (a.user_deleted is null or a.user_deleted <> 1) order by a.username";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['user_id'];
						$username = trim($row['username']);
						$fullname = trim($row['user_fullname']);
						$email = trim($row['user_email']);
						$login_type = $row['login_type'];
						$lastlogin = $row['user_lastlogin'];
						$logincount = $row['user_logincount'];
						$enabled = $row['user_enabled'];
						
						if ($username == "")
							$username = "(blank)";
				?>
				<tr>
					<td><a href="adminusers.php?action=editform&id=<?=$id?>"><?=$username?></td>
					<td><?=$fullname?></td>
					<td><?=$email?></td>
					<td><?=$login_type?></td>
					<td><?=$lastlogin?></td>
					<td><?=$logincount?></td>
					<td>
						<?
							if ($enabled) {
								?><a href="adminusers.php?action=disable&id=<?=$id?>"><img src="images/toggle-on.png" width="30px"></a><?
							}
							else {
								?><a href="adminusers.php?action=enable&id=<?=$id?>"><img src="images/toggle-off.png" width="30px"></a><?
							}
						?>
					</td>
				</tr>
				<? } ?>
			</tbody>
		</table>
	</div>
	<div class="ui bottom attached tab segment" data-tab="second">
		<table class="ui celled selectable compact table">
			<thead>
				<th align="left">Username</th>
				<th>Full name</th>
				<th>Email</th>
				<th>Login type</th>
				<th>Last Login</th>
				<th>Login Count</th>
				<th>Enabled</th>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.* from users a left join user_instance b on a.user_id = b.user_id where b.instance_id <> '" . $_SESSION['instanceid'] . "' group by username order by username";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['user_id'];
						$username = trim($row['username']);
						$fullname = trim($row['user_fullname']);
						$email = trim($row['user_email']);
						$login_type = $row['login_type'];
						$lastlogin = $row['user_lastlogin'];
						$logincount = $row['user_logincount'];
						$enabled = $row['user_enabled'];
						
						if ($username == "")
							$username = "(blank)";
				?>
				<tr>
					<td><a href="adminusers.php?action=editform&id=<?=$id?>"><?=$username?></td>
					<td><?=$fullname?></td>
					<td><?=$email?></td>
					<td><?=$login_type?></td>
					<td><?=$lastlogin?></td>
					<td><?=$logincount?></td>
					<td>
						<?
							if ($enabled) {
								?><a href="adminusers.php?action=disable&id=<?=$id?>"><img src="images/toggle-on.png" width="30px"></a><?
							}
							else {
								?><a href="adminusers.php?action=enable&id=<?=$id?>"><img src="images/toggle-off.png" width="30px"></a><?
							}
						?>
					</td>
				</tr>
				<? } ?>
			</tbody>
		</table>
	</div>
	<div class="ui bottom attached tab segment" data-tab="third">
		<table class="ui celled selectable compact table">
			<thead>
				<th align="left">Username</th>
				<th>Full name</th>
				<th>Email</th>
				<th>Login type</th>
				<th>Last Login</th>
				<th>Login Count</th>
				<th>Enabled</th>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.* from users a left join user_instance b on a.user_id = b.user_id where a.user_deleted = 1 group by a.username order by a.username";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['user_id'];
						$username = trim($row['username']);
						$fullname = trim($row['user_fullname']);
						$email = trim($row['user_email']);
						$login_type = $row['login_type'];
						$lastlogin = $row['user_lastlogin'];
						$logincount = $row['user_logincount'];
						$enabled = $row['user_enabled'];
						
						if ($username == "")
							$username = "(blank)";
				?>
				<tr>
					<td><a href="adminusers.php?action=editform&id=<?=$id?>"><?=$username?></td>
					<td><?=$fullname?></td>
					<td><?=$email?></td>
					<td><?=$login_type?></td>
					<td><?=$lastlogin?></td>
					<td><?=$logincount?></td>
					<td>
						<?
							if ($enabled) {
								?><a href="adminusers.php?action=disable&id=<?=$id?>"><img src="images/toggle-on.png" width="30px"></a><?
							}
							else {
								?><a href="adminusers.php?action=enable&id=<?=$id?>"><img src="images/toggle-off.png" width="30px"></a><?
							}
						?>
					</td>
				</tr>
				<? } ?>
			</tbody>
		</table>
	</div>
	<?
	}
?>