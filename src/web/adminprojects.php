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
		$id = (int)GetVariable("id");
		$projectid = (int)GetVariable("projectid");
		$copyfromprojectid = (int)GetVariable("copyfromprojectid");
		$projectname = GetVariable("projectname");
		$admin = GetVariable("admin");
		$pi = GetVariable("pi");
		$instanceid = GetVariable("instanceid");
		$costcenter = GetVariable("costcenter");
		$startdate = GetVariable("startdate");
		$enddate = GetVariable("enddate");
		$usecustomid = GetVariable("usecustomid");
		$projectdesc = GetVariable("projectdesc");
		
		/* determine action */
		switch ($action) {
			case 'editform':
				DisplayProjectForm("edit", $id);
				break;
			case 'addform':
				DisplayProjectForm("add", 0);
				break;
			/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
			case 'update':
				ob_start();
				UpdateProject($id, $projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $costcenter, $startdate, $enddate);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojects.php");
				break;
			case 'add':
				ob_start();
				AddProject($projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $costcenter, $startdate, $enddate);
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
	function UpdateProject($id, $projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $costcenter, $startdate, $enddate) {
		/* perform data checks */
		$projectname = trim($projectname ?? '');
		$projectdesc = trim($projectdesc ?? '');
		$usecustomid = GetMySQLTinyInt($usecustomid);
		$costcenter = trim($costcenter ?? '');
		$startdate = NormalizeProjectDate($startdate);
		$enddate = NormalizeProjectDate($enddate);

		/* the project must be in one of this admin's instances, and so must the instance it's being moved to */
		if (!CanAccessProject($id)) {
			Error("Project not found or not accessible");
			return;
		}
		if (!CanUseInstance($instanceid)) {
			Error("Invalid instance");
			return;
		}

		/* update the project */
		$sqlstring = "update projects set project_name = ?, project_desc = ?, project_usecustomid = ?, project_admin = ?, project_pi = ?, instance_id = ?, project_costcenter = ?, project_startdate = ?, project_enddate = ? where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$params = [$projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $costcenter, $startdate, $enddate, $id];
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($projectname) . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddProject ------------------------- */
	/* -------------------------------------------- */
	function AddProject($projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $costcenter, $startdate, $enddate) {
		/* perform data checks */
		$projectname = trim($projectname ?? '');
		$projectdesc = trim($projectdesc ?? '');
		$usecustomid = GetMySQLTinyInt($usecustomid);
		$costcenter = trim($costcenter ?? '');
		$startdate = NormalizeProjectDate($startdate);
		$enddate = NormalizeProjectDate($enddate);

		if (!CanUseInstance($instanceid)) {
			Error("Invalid instance");
			return;
		}

		$projectuid = NIDB\CreateUID('P',4);

		/* insert the new project */
		$sqlstring = "insert into projects (project_uid, project_name, project_desc, project_usecustomid, project_admin, project_pi, instance_id, project_costcenter, project_startdate, project_enddate, project_status) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$params = [$projectuid, $projectname, $projectdesc, $usecustomid, $admin, $pi, $instanceid, $costcenter, $startdate, $enddate];
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($projectname) . " added");
	}


	/* -------------------------------------------- */
	/* ------- NormalizeProjectDate --------------- */
	/* -------------------------------------------- */
	/* blank or zero dates are stored as NULL (project_startdate/enddate are nullable) */
	function NormalizeProjectDate($date) {
		$date = trim($date ?? '');
		if (($date == "") || (substr($date, 0, 10) == "0000-00-00"))
			return null;
		return $date;
	}


	/* -------------------------------------------- */
	/* ------- HasProjectDate --------------------- */
	/* -------------------------------------------- */
	function HasProjectDate($date) {
		return (NormalizeProjectDate($date) !== null) && (strtotime($date) !== false);
	}


	/* -------------------------------------------- */
	/* ------- CanUseInstance --------------------- */
	/* -------------------------------------------- */
	/* site admins can use any instance, other admins only the instances they belong to */
	function CanUseInstance($instanceid) {
		$instanceid = (int)$instanceid;
		if ($instanceid <= 0)
			return false;

		if ($GLOBALS['issiteadmin']) {
			$sqlstring = "select instance_id from instance where instance_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $instanceid);
			$params = [$instanceid];
		}
		else {
			$sqlstring = "select a.instance_id from user_instance a join users b on a.user_id = b.user_id where a.instance_id = ? and b.username = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'is', $instanceid, $GLOBALS['username']);
			$params = [$instanceid, $GLOBALS['username']];
		}
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$found = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		return $found;
	}


	/* -------------------------------------------- */
	/* ------- CanAccessProject ------------------- */
	/* -------------------------------------------- */
	/* true if the project exists and is in an instance this admin can use */
	function CanAccessProject($projectid) {
		$sqlstring = "select instance_id from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row)
			return false;
		return CanUseInstance($row['instance_id']);
	}


	/* -------------------------------------------- */
	/* ------- GetProjectName --------------------- */
	/* -------------------------------------------- */
	/* returns the project name, or null if the project doesn't exist */
	function GetProjectName($projectid) {
		$sqlstring = "select project_name from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row)
			return null;
		return $row['project_name'];
	}


	/* -------------------------------------------- */
	/* ------- CopyProjectSettings ---------------- */
	/* -------------------------------------------- */
	function CopyProjectSettings($projectid, $copyfromprojectid) {
		$sourceprojectid = $copyfromprojectid;
		$destprojectid = $projectid;

		/* both projects must exist and be different, otherwise rows would be copied into a bogus project (or duplicated within one) */
		if (($sourceprojectid <= 0) || ($destprojectid <= 0)) {
			Error("Source and destination projects must both be selected");
			return;
		}
		if ($sourceprojectid == $destprojectid) {
			Error("Cannot copy settings from a project to itself");
			return;
		}
		if (!CanAccessProject($sourceprojectid) || !CanAccessProject($destprojectid)) {
			Error("Source or destination project not found or not accessible");
			return;
		}
		$sourceprojectname = GetProjectName($sourceprojectid);
		$destprojectname = GetProjectName($destprojectid);

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
						$numitems = 0;
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
							$numitems += mysqli_num_rows($resultA);
							while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
								$rowid2 = $rowA['studytemplateitem_id'];
								$newvals2['studytemplate_id'] = $newstudytemplateid;
								DuplicateSQLRow('study_templateitems', 'studytemplateitem_id', $rowid2, $newvals2);
							}
						}
						?>
						<div class="sub header">copied <?=$numrows?> templates (<?=$numitems?> template items)</div>
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
		$name = $admin = $pi = $instanceid = $costcenter = $desc = $startdate = $enddate = "";
		$usecustomid = 0;

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			if (!CanAccessProject($id)) {
				Error("Project not found or not accessible");
				return;
			}
			$sqlstring = "select * from projects where project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) {
				Error("Project not found");
				return;
			}
			$name = $row['project_name'];
			$admin = $row['project_admin'];
			$pi = $row['project_pi'];
			$instanceid = $row['instance_id'];
			$costcenter = $row['project_costcenter'];
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

			/* default new projects to the current instance */
			$instanceid = $_SESSION['instanceid'] ?? '';
		}

	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><?=htmlspecialchars($formtitle ?? '')?></div>
			</div>
			<form method="post" action="adminprojects.php" class="ui form <?=($type == "edit") ? "attached" : "bottom attached"?> fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=(int)$id?>">

			<div class="two fields">
				<div class="field">
					<label>Name</label>
					<div class="field">
						<input type="text" name="projectname" value="<?=htmlspecialchars($name ?? '')?>" maxlength="60" required>
					</div>
				</div>

				<div class="field">
					<label>Project number</label>
					<div class="field">
						<input type="text" name="costcenter" value="<?=htmlspecialchars($costcenter ?? '')?>" maxlength="45" required>
					</div>
				</div>
			</div>

			<div class="field">
				<label>Description</label>
				<textarea name="projectdesc" rows="3" placeholder="Optional project description"><?=htmlspecialchars($desc ?? '')?></textarea>
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
						/* same rule as CanUseInstance(): site admins see every instance, other admins only their own */
						if ($GLOBALS['issiteadmin']) {
							$sqlstring = "select * from instance order by instance_name";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						}
						else {
							$sqlstring = "select * from instance where instance_id in (select instance_id from user_instance where user_id = (select user_id from users where username = ?)) order by instance_name";
							$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
							mysqli_stmt_bind_param($stmt, 's', $GLOBALS['username']);
							$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$GLOBALS['username']]);
							mysqli_stmt_close($stmt);
						}
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$instance_id = $row['instance_id'];
							$instance_name = $row['instance_name'];
							if ($instanceid == $instance_id) { $selected = "selected"; } else { $selected = ''; }
							?><option value="<?=(int)$instance_id?>" <?=$selected?>><?=htmlspecialchars($instance_name ?? '')?></option><?
						}
					?>
					</select>
				</div>
			</div>

			<div class="field">
				<label>Principal Investigator</label>
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
								<option value="<?=(int)$userid?>" <?=$selected?>><?=htmlspecialchars($fullname ?? '')?> (<?=htmlspecialchars($username ?? '')?>)</option>
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
								<option value="<?=(int)$userid?>" <?=$selected?>><?=htmlspecialchars($fullname ?? '')?> (<?=htmlspecialchars($username ?? '')?>)</option>
								<?
							}
						?>
					</select>
				</div>
			</div>
			<div class="field">
				<label>Start Date</label>
				<div class="field">
					<input type="text" name="startdate" value="<?=HasProjectDate($startdate) ? htmlspecialchars($startdate) : ''?>" placeholder="YYYY-MM-DD">
				</div>
			</div>
			<div class="field">
				<label>End Date</label>
				<div class="field">
					<input type="text" name="enddate" value="<?=HasProjectDate($enddate) ? htmlspecialchars($enddate) : ''?>" placeholder="YYYY-MM-DD">
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
			<? if ($type == "edit") { /* copying settings needs an existing destination project */ ?>
			<div class="ui bottom attached grey segment">
				<form method="post" action="adminprojects.php" class="ui form">
					<input type="hidden" name="action" value="copysettings">
					<input type="hidden" name="projectid" value="<?=(int)$id?>">
					Copy settings from existing project <i class="question circle icon" title="Copies the study templates, BIDS mapping, and checklists from the selected project into this project"></i><br>
					<select name="copyfromprojectid" class="ui compact selection dropdown" required>
						<option value="">Select project...</option>
					<?
						/* only offer source projects this admin can access (see CanAccessProject) */
						if ($GLOBALS['issiteadmin']) {
							$sqlstringB = "select project_id, project_name, project_costcenter from projects where project_id <> ? order by project_name";
							$stmtB = mysqli_prepare($GLOBALS['linki'], $sqlstringB);
							mysqli_stmt_bind_param($stmtB, 'i', $id);
							$paramsB = [$id];
						}
						else {
							$sqlstringB = "select project_id, project_name, project_costcenter from projects where project_id <> ? and instance_id in (select a.instance_id from user_instance a join users b on a.user_id = b.user_id where b.username = ?) order by project_name";
							$stmtB = mysqli_prepare($GLOBALS['linki'], $sqlstringB);
							mysqli_stmt_bind_param($stmtB, 'is', $id, $GLOBALS['username']);
							$paramsB = [$id, $GLOBALS['username']];
						}
						$resultB = MySQLiBoundQuery($stmtB, __FILE__, __LINE__, $sqlstringB, $paramsB);
						mysqli_stmt_close($stmtB);
						while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
							$project_id = $rowB['project_id'];
							$project_name = $rowB['project_name'];
							$project_costcenter = $rowB['project_costcenter'];
							?>
							<option value="<?=(int)$project_id?>"><?=htmlspecialchars($project_name ?? '')?> (<?=htmlspecialchars($project_costcenter ?? '')?>)</option>
							<?
						}
					?>
					</select>
					<input type="submit" class="ui primary button" value="Copy Settings">
				</form>
			</div>
			<? } ?>
		</div>
		
		<br><br><br>
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
				/* site admins see projects from every instance (hence the Instance column); everyone else only sees the current instance */
				if ($GLOBALS['issiteadmin']) {
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname', d.instance_name from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id left join instance d on a.instance_id = d.instance_id where a.project_status = 'active' order by d.instance_name, a.project_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$sessioninstanceid = (int)($_SESSION['instanceid'] ?? 0);
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = ? order by a.project_name";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $sessioninstanceid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sessioninstanceid]);
					mysqli_stmt_close($stmt);
				}
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['project_id'];
					$projectuid = $row['project_uid'];
					$name = $row['project_name'];
					$adminfullname = $row['adminfullname'];
					$pifullname = $row['pifullname'];
					$instancename = $row['instance_name'] ?? '';
					$costcenter = $row['project_costcenter'];
					$startdate = HasProjectDate($row['project_startdate']) ? $row['project_startdate'] : '';
					$enddate = HasProjectDate($row['project_enddate']) ? $row['project_enddate'] : '';
					$status = $row['project_status'];

					/* grey out projects that have ended. Projects with no end date are ongoing */
					if (($enddate != '') && (strtotime($enddate) < strtotime("now"))) { $style="color: #666666"; } else { $style = ""; }
			?>
			<tr style="<?=$style?>">
				<td><a href="adminprojects.php?action=editform&id=<?=(int)$id?>"><?=htmlspecialchars($name ?? '')?></a></td>
				<? if ($GLOBALS['issiteadmin']) { ?><td class="tiny"><?=htmlspecialchars($instancename)?></td><? } ?>
				<td><?=htmlspecialchars($projectuid ?? '')?></td>
				<td><?=htmlspecialchars($costcenter ?? '')?></td>
				<td><?=htmlspecialchars($adminfullname ?? '')?></td>
				<td><?=htmlspecialchars($pifullname ?? '')?></td>
				<td><?=htmlspecialchars($startdate)?></td>
				<td><?=htmlspecialchars($enddate)?></td>
				<td><?=htmlspecialchars($status ?? '')?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
