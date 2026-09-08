<?
 // ------------------------------------------------------------------------------
 // NiDB adminprojects.php
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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
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

	
	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = GetVariable("id");
		$projectid = GetVariable("projectid");
		$copyfromprojectid = GetVariable("copyfromprojectid");
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
		$projectdesc = GetVariable("projectdesc");
		
		/* determine action */
		switch ($action) {
			case 'editform':
				DisplayProjectForm("edit", $id);
				break;
			case 'addform':
				DisplayProjectForm("add", "$username");
				break;
			/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
			case 'update':
				ob_start();
				UpdateProject($id, $projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojects.php");
				break;
			case 'add':
				ob_start();
				AddProject($projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojects.php");
				break;
			case 'delete':
				ob_start();
				DeleteProject($id);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojects.php");
				break;
			case 'copysettings':
				ob_start();
				CopyProjectSettings($projectid, $copyfromprojectid);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojects.php?action=editform&id=" . urlencode($projectid));
				break;
			default:
				DisplayProjectList();
		}
	}	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateProject ---------------------- */
	/* -------------------------------------------- */
	function UpdateProject($id, $projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate) {
		$usecustomid = GetMySQLTinyInt($usecustomid);

		/* update the project */
		$sqlstring = "update projects set project_name = ?, project_desc = ?, project_usecustomid = ?, project_admin = ?, project_pi = ?, instance_id = ?, project_sharing = ?, project_costcenter = ?, project_startdate = ?, project_enddate = ? where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$params = [$projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $id];
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($projectname) . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddProject ------------------------- */
	/* -------------------------------------------- */
	function AddProject($projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate, $datausers, $phiusers) {
		/* perform data checks */
		$projectname = trim($projectname);
		$projectdesc = trim($projectdesc);
		$usecustomid = GetMySQLTinyInt($usecustomid);
		$admin = trim($admin);
		$pi = trim($pi);
		$sharing = trim($sharing);
		$costcenter = trim($costcenter);
		$startdate = trim($startdate);
		$enddate = trim($enddate);

		if ($startdate == "") { $startdate = "0000-00-00"; }
		if ($enddate == "") { $enddate = "0000-00-00"; }

		$projectuid = NIDB\CreateUID('P',4);

		/* insert the new project */
		$sqlstring = "insert into projects (project_uid, project_name, project_desc, project_usecustomid, project_admin, project_pi, instance_id, project_sharing, project_costcenter, project_startdate, project_enddate, project_status) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$params = [$projectuid, $projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $sharing, $costcenter, $startdate, $enddate];
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($projectname) . " added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteProject ---------------------- */
	/* -------------------------------------------- */
	function DeleteProject($id) {
		$sqlstring = "delete from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);
		Notice("Project deleted");
	}


	/* -------------------------------------------- */
	/* ------- CopyProjectSettings ---------------- */
	/* -------------------------------------------- */
	function CopyProjectSettings($projectid, $copyfromprojectid) {
		$sourceprojectid = $copyfromprojectid;
		$destprojectid = $projectid;

		$sqlstring = "select project_name from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $sourceprojectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sourceprojectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$sourceprojectname = $row['project_name'] ?? '';

		$sqlstring = "select project_name from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $destprojectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$destprojectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$destprojectname = $row['project_name'] ?? '';

		?>
		<div class="ui message">
			<h2 class="ui header">Copying settings from <div class="ui big blue label"><?=htmlspecialchars($sourceprojectname)?></div> to <div class="ui big blue label"><?=htmlspecialchars($destprojectname)?></div></h2>

			<ul>
				<li>
					<h3 class="ui header">Project checklists
						<?
						$numrows = 0;
						$newvals = array();
						$sqlstring = "select * from project_checklist where project_id = ?";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $sourceprojectid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sourceprojectid]);
						mysqli_stmt_close($stmt);
						$numrows = mysqli_num_rows($result);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$rowid = $row['projectchecklist_id'];
							$newvals['project_id'] = $destprojectid;
							DuplicateSQLRow('project_checklist', 'projectchecklist_id', $rowid, $newvals);
						}
						?>
						<div class="sub header">copied <?=$numrows?> rows</div>
					</h3>
				<li>
					<h3 class="ui header">BIDS mapping
						<?
						$numrows = 0;
						$newvals = array();
						$sqlstring = "select * from bids_mapping where project_id = ?";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $sourceprojectid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sourceprojectid]);
						mysqli_stmt_close($stmt);
						$numrows = mysqli_num_rows($result);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$rowid = $row['protocolmapping_id'];
							$newvals['project_id'] = $destprojectid;
							DuplicateSQLRow('bids_mapping', 'protocolmapping_id', $rowid, $newvals);
						}
						?>
						<div class="sub header">copied <?=$numrows?> rows</div>
					</h3>
				<li>
					<h3 class="ui header">Study templates
						<?
						$numrows = 0;
						$newvals = array();
						$sqlstring = "select * from study_template where project_id = ?";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $sourceprojectid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sourceprojectid]);
						mysqli_stmt_close($stmt);
						$numrows = mysqli_num_rows($result);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$rowid = $row['studytemplate_id'];
							$newvals['project_id'] = $destprojectid;
							$newstudytemplateid = DuplicateSQLRow('study_template', 'studytemplate_id', $rowid, $newvals);

							$newvals2 = array();
							$sqlstringA = "select * from study_templateitems where studytemplate_id = ?";
							$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
							mysqli_stmt_bind_param($stmtA, 'i', $rowid);
							$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$rowid]);
							mysqli_stmt_close($stmtA);
							$numrows += mysqli_num_rows($resultA);
							while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
								$rowid2 = $rowA['studytemplateitem_id'];
								$newvals2['studytemplate_id'] = $newstudytemplateid;
								$newrowid = DuplicateSQLRow('study_templateitems', 'studytemplateitem_id', $rowid2, $newvals2);
							}
						}
						?>
						<div class="sub header">copied <?=$numrows?> rows</div>
					</h3>
			</ul>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProjectForm ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectForm($type, $id) {

		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG, e.g. copysettings) */

		/* defaults so the "add" form (and any missing columns) don't reference undefined vars */
		$name = $admin = $pi = $instanceid = $costcenter = $sharing = $desc = $startdate = $enddate = "";
		$usecustomid = 0;

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from projects where project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			//$id = $row['project_id'];
			$name = $row['project_name'];
			$admin = $row['project_admin'];
			$pi = $row['project_pi'];
			$instanceid = $row['instance_id'];
			$costcenter = $row['project_costcenter'];
			$sharing = $row['project_sharing'];
			$desc = $row['project_desc'];
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
			$sqlstring = "select * from users where username = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $username);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$userid = $row['user_id'];
			}
			mysqli_stmt_close($stmt);
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
				<label>Description</label>
				<textarea name="projectdesc" rows="3" placeholder="Optional project description"><?=htmlspecialchars($desc)?></textarea>
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
						$sqlstring = "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = ?)) order by instance_name";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 's', $GLOBALS['username']);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$GLOBALS['username']]);
						mysqli_stmt_close($stmt);
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
			<div class="ui two column grid">
				<div class="column">
					
				</div>
				<div class="right aligned column">
					<button class="ui button" onClick="window.location.href='adminprojects.php'; return false;">Cancel</button>
					<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
				</div>
			</div>
			</form>
			<div class="ui bottom attached grey segment">
				<form method="post" action="adminprojects.php" class="ui form">
					<input type="hidden" name="action" value="copysettings">
					<input type="hidden" name="projectid" value="<?=$id?>">
					Copy settings from existing project <i class="question circle icon" title="Copy the following settings from an existing project<br><ul><li>Templates<li>Data dictionary<li>BIDS mapping<li>Checklists<li>Mini-pipelines<li>Redcap Settings</ul>"></i><br>
					<select name="copyfromprojectid" class="ui compact selection dropdown">
						<option value="">Select project...</option>
					<?
						$sqlstringB = "select * from projects";
						$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
						while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
							$project_id = $rowB['project_id'];
							$project_name = $rowB['project_name'];
							$project_costcenter = $rowB['project_costcenter'];
							?>
							<option value="<?=$project_id?>"><?=$project_name?> (<?=$project_costcenter?>)</option>
							<?
						}
					?>
					</select>
					<input type="submit" class="ui primary button" value="Copy Settings">
				</form>
			</div>
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
					$sqlstring = "select * from users where user_id in (select user_id from user_instance where instance_id = ?) order by username";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $instanceid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instanceid]);
					mysqli_stmt_close($stmt);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$user_id = $row['user_id'];
						$username = $row['username'];
						$user_fullname = $row['user_fullname'];

						$sqlstringA = "select * from user_project where user_id = ? and project_id = ?";
						$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
						mysqli_stmt_bind_param($stmtA, 'ii', $user_id, $id);
						$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$user_id, $id]);
						mysqli_stmt_close($stmtA);
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
			<div class="ui container">
				<div class="ui segment">
					Required protocols<br><br>
					<iframe src="adminprojectprotocols.php?projectid=<?=$id?>" width="100%" height="400px" frameborder="0"></iframe>
				</div>
			</div>
		<? } ?>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectList ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectList() {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
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
				$sessioninstanceid = $_SESSION['instanceid'];
				if ($GLOBALS['issiteadmin']) {
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname', d.instance_name from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id left join instance d on a.instance_id = d.instance_id where a.project_status = 'active' and a.instance_id = ? order by a.project_name";
				}
				else {
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = ? order by a.project_name";
				}
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $sessioninstanceid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sessioninstanceid]);
				mysqli_stmt_close($stmt);
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
