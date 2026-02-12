<?
 // ------------------------------------------------------------------------------
 // NiDB admininstances.php
 // Copyright (C) 2004 - 2026
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
		<title>NiDB - Manage Instances</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";
	
	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = GetVariable("id");
		$defaultinstanceid = GetVariable("defaultinstanceid");
		$instancename = GetVariable("instancename");
		$users = GetVariable("users");
		$userinstanceid = GetVariable("userinstanceid");
		
		//print_r($_POST);
		
		/* determine action */
		switch ($action) {
			case 'editform':
				DisplayInstanceForm("edit", $id);
				break;
			case 'addform':
				DisplayInstanceForm("add", "");
				break;
			case 'update':
				UpdateInstance($id, $instancename, $users);
				DisplayInstanceList();
				break;
			case 'add':
				AddInstance($instancename);
				DisplayInstanceList();
				break;
			case 'setdefaultinstance':
				SetDefaultInstance($defaultinstanceid);
				DisplayInstanceList();
				break;
			case 'acceptjoin':
				AcceptJoin($userinstanceid);
				DisplayInstanceList();
				break;
			case 'rejectjoin':
				RejectJoin($userinstanceid);
				DisplayInstanceList();
				break;
			case 'delete':
				DeleteInstance($id);
				break;
			default:
				DisplayInstanceList();
		}
	}	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateInstance --------------------- */
	/* -------------------------------------------- */
	function UpdateInstance($id, $instancename, $users) {
		/* perform data checks */
		$instancename = mysqli_real_escape_string($GLOBALS['linki'], $instancename);
		
		/* update the instance */
		$sqlstring = "update instance set instance_name = '$instancename' where instance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* add the users to the user_instance table */
		foreach ($users as $userid) {
			$sqlstring = "insert ignore into user_instance (instance_id, user_id) values ($id, $userid)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		?><div align="center"><span class="message"><?=$instancename?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddInstance ------------------------ */
	/* -------------------------------------------- */
	function AddInstance($instancename) {
		/* perform data checks */
		$instancename = mysqli_real_escape_string($GLOBALS['linki'], $instancename);
		
		# create a new instance uid
		do {
			$instanceuid = NIDB\CreateUID('I');
			$sqlstring = "SELECT * FROM `instance` WHERE instance_uid = '$instanceuid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$count = mysqli_num_rows($result);
		} while ($count > 0);
		
		$sqlstring = "select user_id from users where username = '" . $GLOBALS['username'] . "'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$ownerid = $row['user_id'];
		
		/* insert the new instance */
		$sqlstring = "insert into instance (instance_uid, instance_name, instance_ownerid) values ('$instanceuid', '$instancename', '$ownerid')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$instancename?> added</span></div><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteInstance --------------------- */
	/* -------------------------------------------- */
	function DeleteInstance($id) {
		$sqlstring = "delete from instance where instance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	


	/* -------------------------------------------- */
	/* ------- SetDefaultInstance ----------------- */
	/* -------------------------------------------- */
	function SetDefaultInstance($id) {
		$sqlstring = "update instance set instance_default = 1 where instance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$sqlstring = "update instance set instance_default = 0 where instance_id <> $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	

	
	/* -------------------------------------------- */
	/* ------- AcceptJoin ------------------------- */
	/* -------------------------------------------- */
	function AcceptJoin($id) {
		$sqlstring = "update user_instance set instance_joinrequest = 0 where userinstance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><div class="message">Request accepted</div><?
	}

	
	/* -------------------------------------------- */
	/* ------- RejectJoin ------------------------- */
	/* -------------------------------------------- */
	function RejectJoin($id) {
		$sqlstring = "delete from user_instance where userinstance_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><div class="message">Request accepted</div><?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayInstanceForm ---------------- */
	/* -------------------------------------------- */
	function DisplayInstanceForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from instance where instance_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$instanceid = $row['instance_id'];
			$uid = $row['instance_uid'];
			$name = $row['instance_name'];
		
			$formaction = "update";
			$formtitle = "Updating $instancename";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new instance";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="admininstances.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="instancename" maxlength="50" size="35" value="<?=$name?>"></td>
			</tr>
			<? if ($type == 'edit') { ?>
			<tr>
				<td>Instance UID</td>
				<td class="tiny"><?=strtoupper($uid)?></td>
			</tr>
			<tr>
				<script type="text/javascript">
				$(document).ready(function() {
					$("#allusers").click(function() {
						var checked_status = this.checked;
						$(".users").find("input[type='checkbox']").each(function() {
							this.checked = checked_status;
						});
					});
				});
				</script>
			
				<table class="ui small celled selectable grey very compact table">
					<thead>
						<tr>
							<th>Add to Instance &nbsp;</th>
							<th></th>
						</tr>
					</thead>
					<tr>
						<td valign="top"><input type="checkbox" id="allusers"></td>
						<td>Select/unselect all<br><br></td>
					</tr>
					<?
						$userids = array();
						$sqlstring = "select user_id from user_instance where instance_id = $id";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$userids[] = $row['user_id'];
						}
						
						$sqlstring = "select * from users order by username";
						//echo "$sqlstring<br>";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$user_id = $row['user_id'];
							$username = $row['username'];
							$user_fullname = $row['user_fullname'];
							
							$checked = "";
							if (is_array($userids)) {
								if (in_array($user_id, $userids)) {
									$checked = "checked";
								}
							}
							?>
							<tr style="color: darkblue; font-size:11pt; /*background-color: <?=$bgcolor?>*/">
								<td class="users"><input type="checkbox" name="users[]" value="<?=$user_id?>" <?=$checked?>>
								<td><tt><?=$username?></tt> - <?=$user_fullname?></td>
							</tr>
							<?
							if ($bgcolor == "#EEFFEE") { $bgcolor = "#FFFFFF"; }
							elseif ($bgcolor == "#FFFFFF") { $bgcolor = "#EEFFEE"; }
						}
					?>
				</table>
			
			</tr>
			<? } ?>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</td>
			</tr>
			</form>
		</table>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayInstanceList ---------------- */
	/* -------------------------------------------- */
	function DisplayInstanceList() {
	?>

	<table class="ui celled selectable grey compact table">
		<thead>
			<tr>
				<th>UID</th>
				<th>Name</th>
				<th>Owner</th>
				<th>Default</th>
			</tr>
		</thead>
		<form method="post" action="admininstances.php">
		<input type="hidden" name="action" value="setdefaultinstance">
		<tbody>
			<?
				$sqlstring = "select * from instance a left join users b on a.instance_ownerid = b.user_id order by a.instance_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['instance_id'];
					$uid = $row['instance_uid'];
					$name = $row['instance_name'];
					$owner = $row['instance_ownerid'];
					$default = $row['instance_default'];
					$ownername = $row['username'];
					
					if ($default) { $checked = "checked"; } else { $checked = ""; }
			?>
			<tr>
				<td><?=$uid?></td>
				<td><a href="admininstances.php?action=editform&id=<?=$id?>"><?=$name?></td>
				<td><a href="adminusers.php?action=editform&id=<?=$owner?>"><?=$ownername?></a></td>
				<td><input type="radio" name="defaultinstanceid" value="<?=$id?>" <?=$checked?>></td>
			</tr>
			<? 
				}
			?>
			<tr>
				<td colspan="4" align="right"><input type="submit" value="Set Default" class="ui primary button"></td>
			</tr>
		</tbody>
		</form>
	</table>
	
	<br><Br>
	
	<table class="ui celled selectable grey compact table">
		<thead>
			<tr>
				<th>Instance</th>
				<th>Requestor</th>
				<th></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from user_instance a left join users b on a.user_id = b.user_id left join instance c on a.instance_id = c.instance_id where instance_joinrequest = 1 order by c.instance_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['userinstance_id'];
					$uid = $row['instance_uid'];
					$name = $row['instance_name'];
					$useremail = $row['user_email'];
					$userfullname = $row['user_fullname'];
					$username = $row['username'];
					
					if ($default) { $checked = "checked"; } else { $checked = ""; }
			?>
			<tr>
				<td><?=$name?></td>
				<td><?=$userfullname?> &lt;<?=$useremail?>&gt;</td>
				<td><a href="admininstances.php?action=acceptjoin&userinstanceid=<?=$id?>">Accept</td>
				<td><a href="admininstances.php?action=rejectjoin&userinstanceid=<?=$id?>">Reject</td>
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
