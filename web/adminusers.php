<?
 // ------------------------------------------------------------------------------
 // NiDB adminusers.php
 // Copyright (C) 2004 - 2018
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
		<title>NiDB - Manage Users</title>
	</head>

<body onload="onload()">
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "nidbapi.php";
	require "menu.php";

	//PrintVariable($_POST,'post');
	
	/* ----- setup variables ----- */
	$vars['action'] = GetVariable("action");
	$vars['id'] = GetVariable("id");
	$vars['username'] = GetVariable("username");
	$vars['password'] = GetVariable("password");
	$vars['fullname'] = GetVariable("fullname");
	$vars['email'] = GetVariable("email");
	$vars['enabled'] = GetVariable("enabled");
	$vars['isadmin'] = GetVariable("isadmin");
	$vars['isguest'] = GetVariable("isguest");
	$vars['instanceid'] = GetVariable("instanceid");
	$vars['dataprojects'] = GetVariable("dataprojects");
	$vars['phiprojects'] = GetVariable("phiprojects");
	$vars['writedataprojects'] = GetVariable("writedataprojects");
	$vars['writephiprojects'] = GetVariable("writephiprojects");
	
	/* determine action */
	switch ($vars['action']) {
		case 'editform':
			DisplayUserForm("edit", $vars['id']);
			break;
		case 'addform':
			DisplayUserForm("add", "");
			break;
		case 'enable':
			EnableUser($vars['id']);
			DisplayUserList();
			break;
		case 'disable':
			DisableUser($vars['id']);
			DisplayUserList();
			break;
		case 'makeadmin':
			MakeAdminUser($vars['id']);
			DisplayUserList();
			break;
		case 'notadmin':
			MakeNotAdminUser($vars['id']);
			DisplayUserList();
			break;
		case 'update':
			UpdateUser($vars['id'], $vars['username'], $vars['password'], $vars['fullname'], $vars['email'], $vars['enabled'], $vars['isadmin'], $vars['isguest'], $vars['instanceid'], $vars['dataprojects'], $vars['phiprojects'], $vars['writedataprojects'], $vars['writephiprojects']);
			DisplayUserList();
			break;
		case 'add':
			AddUser($vars['username'], $vars['password'], $vars['fullname'], $vars['email'], $vars['enabled'], $vars['isadmin'], $vars['isguest'], $vars['instanceid'], $vars['dataprojects'], $vars['phiprojects'], $vars['writedataprojects'], $vars['writephiprojects']);
			DisplayUserList();
			break;
		case 'delete':
			DeleteUser($vars['id']);
			break;
		default:
			DisplayUserList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateUser ------------------------- */
	/* -------------------------------------------- */
	function UpdateUser($id, $username, $password, $fullname, $email, $enabled, $isadmin, $isguest, $instanceid, $dataprojects, $phiprojects, $writedataprojects, $writephiprojects) {
		/* perform data checks */
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$fullname = mysqli_real_escape_string($GLOBALS['linki'], $fullname);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);

		/* determine their current login type */
		$sqlstring = "select login_type from users where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$logintype = $row['login_type'];
		if ($logintype = "Standard") {
			if ($isguest) {
				$logintype = "Guest";
			}
			else {
				$logintype = "Standard";
			}
		}
		else {
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
			$sqlstring = "insert into user_instance (user_id, instance_id) values ($id, $instid)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* delete all previous rows from the user_project table for this user */
		$sqlstring = "delete from user_project where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* update/insert user_project view rows */
		if (count($dataprojects) > 0) {
			foreach ($dataprojects as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_data = 1, view_phi = 0 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, view_data, view_phi) values ($id, $projectid, 1, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert user_project edit rows */
		if (count($phiprojects) > 0) {
			foreach ($phiprojects as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_phi = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, view_data, view_phi) values ($id, $projectid, 0, 1)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}

		/* update/insert view rows */
		if (count($writedataprojects) > 0) {
			foreach ($writedataprojects as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set write_data = 1, write_phi = 0 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, write_data, write_phi) values ($id, $projectid, 1, 0)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert edit rows */
		if (count($writephiprojects) > 0) {
			foreach ($writephiprojects as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set write_phi = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, write_data, write_phi) values ($id, $projectid, 0, 1)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		?><div align="center"><span class="message"><?=$username?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddUser ---------------------------- */
	/* -------------------------------------------- */
	function AddUser($username, $password, $fullname, $email, $enabled, $isadmin, $isguest, $instanceid, $dataprojects, $phiprojects, $writedataprojects, $writephiprojects) {
		/* perform data checks */
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		$fullname = mysqli_real_escape_string($GLOBALS['linki'], $fullname);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$password = mysqli_real_escape_string($GLOBALS['linki'], $password);
		$enabled = mysqli_real_escape_string($GLOBALS['linki'], $enabled) + 0;
		$isadmin = mysqli_real_escape_string($GLOBALS['linki'], $isadmin) + 0;
		$isguest = mysqli_real_escape_string($GLOBALS['linki'], $isguest) + 0;
		
		/* determine their current login type */
		if ($isguest) {
			$logintype = "Guest";
		}
		else {
			$logintype = "Standard";
		}
		
		/* insert the new user */
		$sqlstring = "insert into users (username, password, login_type, user_instanceid, user_fullname, user_firstname, user_midname, user_lastname, user_institution, user_country, user_email, user_email2, user_address1, user_address2, user_city, user_state, user_zip, user_phone1, user_phone2, user_website, user_dept, user_lastlogin, user_logincount, user_enabled, user_isadmin, sendmail_dailysummary) values ('$username', sha1('$password'), '$logintype','" . $_SESSION['instanceid'] . "', '$fullname', '', '', '', '', '', '$email', '', '', '', '', '', '', '', '', '', '', now(), 0, 1, '$isadmin', 0)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$id = mysqli_insert_id($GLOBALS['linki']);
		
		/* and then insert the new user_instance rows */
		foreach ($instanceid as $instid) {
			$sqlstring = "insert into user_instance (user_id, instance_id) values ($id, $instid)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* update/insert view rows */
		if (is_array($dataprojects)) {
			foreach ($dataprojects as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_data = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, view_data) values ($id, $projectid, 1)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		/* update/insert edit rows */
		if (is_array($phiprojects)) {
			foreach ($phiprojects as $projectid) {
				$sqlstring = "select * from user_project where user_id = $id and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$sqlstring = "update user_project set view_phi = 1 where user_id = $id and project_id = $projectid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sqlstring = "insert into user_project (user_id, project_id, view_phi) values ($id, $projectid, 1)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		?><div align="center"><span class="message"><?=$username?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteUser ------------------------- */
	/* -------------------------------------------- */
	function DeleteUser($id) {
		$sqlstring = "delete from users where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	


	/* -------------------------------------------- */
	/* ------- EnableUser ------------------------- */
	/* -------------------------------------------- */
	function EnableUser($id) {
		$sqlstring = "update users set user_enabled = 1 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisableUser ------------------------ */
	/* -------------------------------------------- */
	function DisableUser($id) {
		$sqlstring = "update users set user_enabled = 0 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- MakeAdminUser ---------------------- */
	/* -------------------------------------------- */
	function MakeAdminUser($id) {
		$sqlstring = "update users set user_isadmin = 1 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- MakeNotAdminUser ------------------- */
	/* -------------------------------------------- */
	function MakeNotAdminUser($id) {
		$sqlstring = "update users set user_isadmin = 0 where user_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
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
			if ($login_type == "Guest") $isguestcheck = "checked";
		
			$formaction = "update";
			$formtitle = "Updating $username";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new user";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Administration'] = "admin.php";
		$urllist['User List'] = "adminusers.php";
		$urllist[$username] = "adminusers.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
	?>
		<style type="text/css">
			td label { 
			   display: block;
			}
			.checkcell {
				border-left: 1px solid #ccc;
			}
		</style>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="adminusers.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td class="heading" colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<? if ($type != 'edit') { ?>
			<tr>
				<td class="label">Username</td>
				<td><input type="text" name="username" value="<?=$username?>"></td>
			</tr>
			<? } else { ?>
			<input type="hidden" name="username" value="<?=$username?>">
			<? } ?>
			<tr>
				<td class="label">Full name</td>
				<td><input type="text" name="fullname" value="<?=$fullname?>" required></td>
			</tr>
			<? if (($login_type == "Guest") || ($login_type == "Standard") || ($type == "add")) { ?>
			<tr>
				<td class="label">Password</td>
				<td><input type="password" name="password" id="password"></td>
			</tr>
			<tr>
				<td class="label">Re-enter Password</td>
				<td><input type="password" name="password-check" id="password-check"></td>
			</tr>
			<? } ?>
			<tr>
				<td class="label">Email</td>
				<td><input type="text" name="email" value="<?=$email?>" required></td>
			</tr>
			<tr>
				<td class="label">Enabled?</td>
				<td><input type="checkbox" name="enabled" value="1" <?=$enabledcheck?>></td>
			</tr>
			<tr>
				<td class="label">Admin?</td>
				<td><input type="checkbox" name="isadmin" value="1" <?=$isadmincheck?>></td>
			</tr>
			<tr>
				<td class="label">Guest?</td>
				<td><input type="checkbox" name="isguest" value="1" <?=$isguestcheck?>></td>
			</tr>
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
	
					/* to disable the autofill thing in Chrome */
					if ($.browser.webkit) {
						$('input[name="username"]').attr('autocomplete', 'off');
						$('input[name="fullname"]').attr('autocomplete', 'off');
						$('input[name="password"]').attr('autocomplete', 'off');
					}
					$("#alldataprojects").click(function() {
						var checked_status = this.checked;
						$(".dataprojects").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#allphiprojects").click(function() {
						var checked_status = this.checked;
						$(".phiprojects").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#writealldataprojects").click(function() {
						var checked_status = this.checked;
						$(".writedataprojects").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					$("#writeallphiprojects").click(function() {
						var checked_status = this.checked;
						$(".writephiprojects").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
					/* show/hide projects for each instance */
					$(".instances").click(function() {
						console.log("I've been clicked!" + this.value);
						if (this.checked) {
							console.log("Now I'm checked");
							//$(".projects" + this.value).find("input[type='checkbox']").each(function() {
							//	this.attr("enabled",true);
							//});
							$(".chkInstance" + this.value).attr("disabled",false);
							$(".projects" + this.value).css("background-color","#fff");
							$(".projects" + this.value).css("color", 'darkblue');
						}
						else {
							console.log("Now I'm not checked");
							//$(".projects" + this.value).find("input[type='checkbox']").each(function() {
							//	this.attr("enabled",true);
							//});
							$(".chkInstance" + this.value).attr("disabled",true);
							$(".projects" + this.value).css("background-color", '#eee');
							$(".projects" + this.value).css("color", '#777');
						}
					});
				});
				</script>
			<tr>
				<td class="label" valign="top">Project access</td>
				<td>
					<table cellspacing="0" cellpadding="1" class="smallgraydisplaytable">
						<thead>
						<tr>
							<th></th>
							<th colspan="2" align="center">Data</th>
							<th colspan="2" align="center">PHI</th>
						</tr>
						<tr>
							<th></th>
							<th align="center">View &nbsp;</th>
							<th align="center">Change &nbsp;</th>
							<th align="center">View &nbsp;</th>
							<th align="center">Change &nbsp;</th>
						</tr>
						</thead>
						<tr style="color: darkblue; font-size:11pt; font-weight: bold">
							<td>Select/unselect all<br><br></td>
							<td valign="top" align="center" class="checkcell"><label><input type="checkbox" id="alldataprojects"></label></td>
							<td valign="top" align="center" class="checkcell"><label><input type="checkbox" id="writealldataprojects"></label></td>
							<td valign="top" align="center" class="checkcell"><label><input type="checkbox" id="allphiprojects"></label></td>
							<td valign="top" align="center" class="checkcell"><label><input type="checkbox" id="writeallphiprojects"></label></td>
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
							<td colspan="5"><label><input type="checkbox" value="<?=$instance_id?>" name="instanceid[]" class="instances" id="instance<?=$instance_id;?>" <?=$checked?>><b><?=$instance_name?></b></label></td>
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
										$view_data = $rowB['view_data'];
										$view_phi = $rowB['view_phi'];
										$write_data = $rowB['write_data'];
										$write_phi = $rowB['write_phi'];
									}
									else {
										$view_data = "";
										$view_phi = "";
										$write_data = "";
										$write_phi = "";
									}
								}

								?>
								<tr style="color: darkblue; font-size:11pt;" class="projects<?=$instance_id?>">
									<td><?=$project_name?> (<tt><?=$project_costcenter?></tt>)</td>
									<td align="center" class="dataprojects checkcell"><label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="dataprojects[]" value="<?=$project_id?>" <?if ($view_data) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?> title="View data for<br><b><?=$project_name?></b>"></label></td>
									<td align="center" class="writedataprojects checkcell"><label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="writedataprojects[]" value="<?=$project_id?>" <?if ($write_data) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?> title="Change/upload data for<br><b><?=$project_name?></b>"></label></td>
									<td align="center" class="phiprojects checkcell"><label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="phiprojects[]" value="<?=$project_id?>" <?if ($view_phi) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?> title="View PHI for<br><b><?=$project_name?></b>"></label></td>
									<td align="center" class="writephiprojects checkcell"><label><input type="checkbox" class="chkInstance<?=$instance_id?>" name="writephiprojects[]" value="<?=$project_id?>" <?if ($write_phi) echo "checked"; ?> <?if ($type == "add") echo "checked"; ?> title="Change PHI for<br><b><?=$project_name?></b>"></label></td>
								</tr>
								<?
								if ($bgcolor == "#EEFFEE") { $bgcolor = "#FFFFFF"; }
								elseif ($bgcolor == "#FFFFFF") { $bgcolor = "#EEFFEE"; }
							}
						}
					?>
					</table>
				</td>
			</tr>
			<? } ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" id="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		</div>
		<br><br><br>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayUserList -------------------- */
	/* -------------------------------------------- */
	function DisplayUserList() {
	
		$urllist['Administration'] = "admin.php";
		$urllist['User List'] = "adminusers.php";
		$urllist['Add User'] = "adminusers.php?action=addform";
		NavigationBar("Admin", $urllist);
		
	?>
	
	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Username</th>
				<th>Full name</th>
				<th>Email</th>
				<th>Login type</th>
				<!--<th>Instance</th>-->
				<th>Last Login</th>
				<th>Login Count</th>
				<th>Enabled</th>
				<th>Admin</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from users a left join user_instance b on a.user_id = b.user_id where b.instance_id = '" . $_SESSION['instanceid'] . "' order by username";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['user_id'];
					$username = $row['username'];
					$fullname = $row['user_fullname'];
					$email = $row['user_email'];
					$login_type = $row['login_type'];
					//$instancename = $row['instance_name'];
					$lastlogin = $row['user_lastlogin'];
					$logincount = $row['user_logincount'];
					$enabled = $row['user_enabled'];
					$isadmin = $row['user_isadmin'];
			?>
			<tr>
				<td><a href="adminusers.php?action=editform&id=<?=$id?>"><?=$username?></td>
				<td><?=$fullname?></td>
				<td><?=$email?></td>
				<td><?=$login_type?></td>
				<!--<td class="tiny"><?=$instancename?></td>-->
				<td><?=$lastlogin?></td>
				<td><?=$logincount?></td>
				<td>
					<?
						if ($enabled) {
							?><a href="adminusers.php?action=disable&id=<?=$id?>"><img src="images/checkedbox16.png"></a><?
						}
						else {
							?><a href="adminusers.php?action=enable&id=<?=$id?>"><img src="images/uncheckedbox16.png"></a><?
						}
					?>
				</td>
				<td>
					<?
						if ($isadmin) {
							?><a href="adminusers.php?action=notadmin&id=<?=$id?>"><img src="images/checkedbox16.png"></a><?
						}
						else {
							?><a href="adminusers.php?action=makeadmin&id=<?=$id?>"><img src="images/uncheckedbox16.png"></a><?
						}
					?>
				</td>
				<!--<td><?if ($enabled) echo "&#10004;";?></td>
				<td><?if ($isadmin) echo "&#10004;";?></td> -->
			</tr>
			<? } ?>
			<tr><td colspan="8" align="center">The Following users are unaffiliated with an instance</td></tr>
			<?
				$sqlstring = "select a.* from users a left join user_instance b on a.user_id = b.user_id where b.instance_id = '' or b.instance_id is null order by username";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['user_id'];
					$username = $row['username'];
					$fullname = $row['user_fullname'];
					$email = $row['user_email'];
					$login_type = $row['login_type'];
					$instancename = $row['instance_name'];
					$lastlogin = $row['user_lastlogin'];
					$logincount = $row['user_logincount'];
					$enabled = $row['user_enabled'];
					$isadmin = $row['user_isadmin'];
			?>
			<tr>
				<td><a href="adminusers.php?action=editform&id=<?=$id?>"><?=$username?></td>
				<td><?=$fullname?></td>
				<td><?=$email?></td>
				<td><?=$login_type?></td>
				<td class="tiny"><?=$instancename?></td>
				<td><?=$lastlogin?></td>
				<td><?=$logincount?></td>
				<td>
					<?
						if ($enabled) {
							?><a href="adminusers.php?action=disable&id=<?=$id?>"><img src="images/checkedbox16.png"></a><?
						}
						else {
							?><a href="adminusers.php?action=enable&id=<?=$id?>"><img src="images/uncheckedbox16.png"></a><?
						}
					?>
				</td>
				<td>
					<?
						if ($isadmin) {
							?><a href="adminusers.php?action=notadmin&id=<?=$id?>"><img src="images/checkedbox16.png"></a><?
						}
						else {
							?><a href="adminusers.php?action=makeadmin&id=<?=$id?>"><img src="images/uncheckedbox16.png"></a><?
						}
					?>
				</td>
				<!--<td><?if ($enabled) echo "&#10004;";?></td>
				<td><?if ($isadmin) echo "&#10004;";?></td> -->
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>