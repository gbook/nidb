<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Study templates</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	/* mutating actions require POST + CSRF token (this replaces the old HTTP_REFERER self-call check) */
	$mutating = array("createstudytemplate", "createprojecttemplate", "updatestudytemplate", "updateprojecttemplate", "deletestudytemplate", "deleteprojecttemplate");
	$action = GetSafeAction($mutating);
	$templateid = (int)GetVariable("templateid");
	$projectid = (int)GetVariable("projectid");
	$ptid = (int)GetVariable("ptid");
	$newtemplatename = GetVariable("newtemplatename");
	$newtemplatemodality = GetVariable("newtemplatemodality");
	$visittype = GetVariable("visittype");
	$modality = GetVariable("modality");
	$itemprotocol = GetVariable("itemprotocol");
	$protocols = GetVariable("protocols");
	$studydesc = GetVariable("studydesc");
	$studyoperator = GetVariable("studyoperator");
	$studyphysician = GetVariable("studyphysician");
	$studysite = GetVariable("studysite");
	$studynotes = GetVariable("studynotes");

	/* mutating actions use POST/Redirect/GET: run the handler, stash its message, then redirect to a
	   GET (the template's edit page, or the list) so a refresh/Back doesn't re-submit the form */
	if (in_array($action, $mutating, true)) {
		$redirect = "templates.php?projectid=$projectid";
		ob_start();
		list($ismember, $canedit) = GetTemplatePerms($projectid);
		if (!$canedit) {
			Error("You do not have permission to edit this project's templates");
		}
		else {
			switch ($action) {
				case 'createstudytemplate':
					$newid = CreateStudyTemplate($projectid, $newtemplatename, $newtemplatemodality);
					if ($newid > 0) $redirect = "templates.php?action=editstudytemplate&projectid=$projectid&templateid=$newid";
					break;
				case 'createprojecttemplate':
					$newid = CreateProjectTemplate($projectid, $newtemplatename);
					if ($newid > 0) $redirect = "templates.php?action=editprojecttemplate&projectid=$projectid&ptid=$newid";
					break;
				case 'updatestudytemplate':
					UpdateStudyTemplate($projectid, $templateid, $itemprotocol);
					$redirect = "templates.php?action=editstudytemplate&projectid=$projectid&templateid=$templateid";
					break;
				case 'updateprojecttemplate':
					UpdateProjectTemplate($projectid, $ptid, $visittype, $modality, $protocols, $studydesc, $studyoperator, $studyphysician, $studysite, $studynotes);
					$redirect = "templates.php?action=editprojecttemplate&projectid=$projectid&ptid=$ptid";
					break;
				case 'deletestudytemplate': DeleteStudyTemplate($projectid, $templateid); break;
				case 'deleteprojecttemplate': DeleteProjectTemplate($projectid, $ptid); break;
			}
		}
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo($redirect);
	}

	ShowFlashMessage();

	list($ismember, $canedit) = GetTemplatePerms($projectid);
	if ($projectid < 1) {
		Error("Invalid project ID");
	}
	elseif (!$ismember) {
		Error("You do not have permission to view this project's templates");
	}
	else {
		switch ($action) {
			case 'editstudytemplate':
				StudyTemplateForm($projectid, $templateid, $canedit);
				break;
			case 'editprojecttemplate':
				ProjectTemplateForm($projectid, $ptid, $canedit);
				break;
			default:
				DisplayStudyTemplateList($projectid, $canedit);
				DisplayProjectStudyTemplateList($projectid, $canedit);
		}
	}

	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetTemplatePerms ------------------- */
	/* -------------------------------------------- */
	/* Study templates are project definitions: any member of the project (any permission on it) may view
	   them; Edit Data is needed to create, change or delete them. Returns [ismember, canedit] */
	function GetTemplatePerms($projectid) {
		$projectid = (int)$projectid;
		if ($projectid < 1)
			return array(false, false);
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return array(IsProjectMember($perms, $projectid), (GetPerm($perms, 'modifydata', $projectid) == 1));
	}


	/* -------------------------------------------- */
	/* ------- TemplateQuery ---------------------- */
	/* -------------------------------------------- */
	/* runs a bound query; $types/$params as for mysqli_stmt_bind_param. Returns all rows */
	function TemplateQuery($sqlstring, $types = '', $params = array()) {
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		if ($types != '')
			mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$rows = array();
		if ($result && ($result !== true)) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
				$rows[] = $row;
		}
		mysqli_stmt_close($stmt);
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- GetStudyTemplate ------------------- */
	/* -------------------------------------------- */
	/* the study template row if it belongs to $projectid, otherwise null */
	function GetStudyTemplate($projectid, $templateid) {
		$rows = TemplateQuery("select * from study_template where studytemplate_id = ? and project_id = ?", 'ii', [(int)$templateid, (int)$projectid]);
		return (count($rows) > 0) ? $rows[0] : null;
	}


	/* -------------------------------------------- */
	/* ------- GetProjectTemplate ----------------- */
	/* -------------------------------------------- */
	/* the project template row if it belongs to $projectid, otherwise null */
	function GetProjectTemplate($projectid, $ptid) {
		$rows = TemplateQuery("select * from project_template where projecttemplate_id = ? and project_id = ?", 'ii', [(int)$ptid, (int)$projectid]);
		return (count($rows) > 0) ? $rows[0] : null;
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudyTemplateList ----------- */
	/* -------------------------------------------- */
	function DisplayStudyTemplateList($projectid, $canedit) {
		$projectid = (int)$projectid;
		?>
		<div class="ui container">

			<div class="ui grid">
				<div class="six wide column">
					<h2 class="ui header">
						Study Templates
						<div class="sub header">Creates <b>single</b> studies</div>
					</h2>
				</div>
				<div class="ten wide column" align="right">
					<? if ($canedit) { ?>
					<form action="templates.php" method="post" name="theform" id="theform">
					<input type="hidden" name="action" value="createstudytemplate">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<?=CSRFTokenField()?>
					<div class="ui labeled action input">
						<input type="text" name="newtemplatename" placeholder="New template name" required>
						<select name="newtemplatemodality" class="ui selection dropdown" required>
							<option value="">Modality...</option>
						<?
							$modalities = GetModalityList();
							foreach ((array)$modalities as $modality) {
								?><option value="<?=htmlspecialchars($modality)?>"><?=htmlspecialchars($modality)?></option><?
							}
						?>
						</select>
						<button type="submit" class="ui button primary"><i class="plus square outline icon"></i> Create Study Template</button>
					</div>
					</form>
					<? } ?>
				</div>
			</div>

			<table class="ui celled selectable grey table">
				<thead>
					<th>Name</th>
					<th>Modality</th>
				</thead>
			<?
			foreach (TemplateQuery("select * from study_template where project_id = ?", 'i', [$projectid]) as $row) {
				$templateid = (int)$row['studytemplate_id'];
				$templatename = ($row['template_name'] == "") ? "(blank)" : $row['template_name'];
				$templatemodality = ($row['template_modality'] == "") ? "(blank)" : $row['template_modality'];
				?>
				<tr>
					<td><a href="templates.php?action=editstudytemplate&projectid=<?=$projectid?>&templateid=<?=$templateid?>"><?=htmlspecialchars($templatename)?></a></td>
					<td><?=htmlspecialchars($templatemodality)?></td>
				</tr>
				<?
			}
			?>
			</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectStudyTemplateList ---- */
	/* -------------------------------------------- */
	function DisplayProjectStudyTemplateList($projectid, $canedit) {
		$projectid = (int)$projectid;
		?>
		<br><br><br>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">
						Project Study Templates
						<div class="sub header">Creates <b>groups</b> of studies</div>
					</h2>
				</div>
				<div class="column" align="right">
					<? if ($canedit) { ?>
					<form action="templates.php" method="post" name="theform" id="theform">
					<input type="hidden" name="action" value="createprojecttemplate">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<?=CSRFTokenField()?>
					<div class="ui labeled action input">
						<input type="text" name="newtemplatename" placeholder="New template name" required>
						<button type="submit" value="Create Project Template" class="ui button primary"><i class="plus square outline icon"></i> Create Project Template</button>
					</div>
					</form>
					<? } ?>
				</div>
			</div>
			<table class="ui celled selectable grey table">
				<thead>
					<tr>
						<th></th>
						<th colspan="2" style="text-align: center">Number of...</th>
						<th colspan="3"></th>
					</tr>
					<tr>
						<th>Name</th>
						<th>Studies</th>
						<th>Series</th>
						<th>Create Date</th>
						<th>Modify Date</th>
					</tr>
				</thead>
			<?
			$sqlstring = "select a.projecttemplate_id, a.template_name, a.template_createdate, a.template_modifydate, (select count(*) from project_templatestudies s where s.pt_id = a.projecttemplate_id) numstudies, (select count(*) from project_templatestudyitems i join project_templatestudies s on i.pts_id = s.pts_id where s.pt_id = a.projecttemplate_id) numseries from project_template a where a.project_id = ?";
			foreach (TemplateQuery($sqlstring, 'i', [$projectid]) as $row) {
				$ptid = (int)$row['projecttemplate_id'];
				$name = ($row['template_name'] == "") ? "(blank)" : $row['template_name'];
				?>
				<tr>
					<td><a href="templates.php?action=editprojecttemplate&projectid=<?=$projectid?>&ptid=<?=$ptid?>"><?=htmlspecialchars($name)?></a></td>
					<td><?=(int)$row['numstudies']?></td>
					<td><?=(int)$row['numseries']?></td>
					<td><?=htmlspecialchars((string)$row['template_createdate'])?></td>
					<td><?=htmlspecialchars((string)$row['template_modifydate'])?></td>
				</tr>
				<?
			}
			?>
			</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- CreateStudyTemplate ---------------- */
	/* -------------------------------------------- */
	/* returns the id of the new template, or of the existing template with that name */
	function CreateStudyTemplate($projectid, $name, $modality) {
		$projectid = (int)$projectid;
		$name = trim((string)$name);
		$modality = trim((string)$modality);
		if ($name == "") { Error("Template name cannot be blank"); return 0; }
		if (!in_array($modality, (array)GetModalityList(), true)) { Error("Invalid modality"); return 0; }

		/* if a template with this name already exists, show that one */
		$rows = TemplateQuery("select studytemplate_id from study_template where template_name = ? and project_id = ? limit 1", 'si', [$name, $projectid]);
		if (count($rows) > 0) {
			Notice("Template named <b>" . htmlspecialchars($name) . "</b> already exists. Displaying that template");
			return (int)$rows[0]['studytemplate_id'];
		}

		TemplateQuery("insert into study_template (project_id, template_name, template_modality) values (?, ?, ?)", 'iss', [$projectid, $name, $modality]);
		return (int)mysqli_insert_id($GLOBALS['linki']);
	}


	/* -------------------------------------------- */
	/* ------- CreateProjectTemplate -------------- */
	/* -------------------------------------------- */
	/* returns the id of the new template */
	function CreateProjectTemplate($projectid, $name) {
		$projectid = (int)$projectid;
		$name = trim((string)$name);
		if ($name == "") { Error("Template name cannot be blank"); return 0; }
		TemplateQuery("insert into project_template (project_id, template_name, template_createdate, template_modifydate) values (?, ?, now(), now())", 'is', [$projectid, $name]);
		return (int)mysqli_insert_id($GLOBALS['linki']);
	}


	/* -------------------------------------------- */
	/* ------- StudyTemplateForm ------------------ */
	/* -------------------------------------------- */
	function StudyTemplateForm($projectid, $templateid, $canedit) {
		$projectid = (int)$projectid;
		$template = GetStudyTemplate($projectid, $templateid);
		if ($template == null) {
			Error("Study template not found");
			return;
		}
		$templateid = (int)$template['studytemplate_id'];
		$ro = $canedit ? "" : "readonly";
		?>

		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><b><?=htmlspecialchars($template['template_name'])?></b> - <?=htmlspecialchars($template['template_modality'])?></div>
			</div>
			<form action="templates.php" method="post" name="theform" id="theform" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="updatestudytemplate">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<input type="hidden" name="templateid" value="<?=$templateid?>">
			<?=CSRFTokenField()?>
			<? if ($canedit) { ?><span class="tiny">Leave protocol blank to delete</span><? } ?>
			<table class="ui selectable grey compact table">
				<thead>
					<th>Series</th>
					<th>Protocol</th>
				</thead>
			<?
			$n=1;
			foreach (TemplateQuery("select item_protocol from study_templateitems where studytemplate_id = ? order by item_order", 'i', [$templateid]) as $row) {
				?>
				<tr>
					<td><?=$n++?></td>
					<td><input type="text" name="itemprotocol[]" value="<?=htmlspecialchars((string)$row['item_protocol'])?>" size="50" <?=$ro?>></td>
				</tr>
				<?
			}
			/* add 5 blank rows */
			if ($canedit) {
				for ($i=0;$i<5;$i++) {
					?>
					<tr>
						<td><?=$n++?></td>
						<td><input type="text" name="itemprotocol[]" value="" size="50"></td>
					</tr>
					<?
				}
			}
			?>
			</table>

			<div class="ui two column grid">
				<div class="column">
					<? if ($canedit) { ?>
					<button type="submit" form="deletestudytemplateform" class="ui red button"><i class="trash icon"></i>Delete</button>
					<? } ?>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="templates.php?projectid=<?=$projectid?>">Back</a>
					<? if ($canedit) { ?><input type="submit" value="Save" class="ui button primary"><? } ?>
				</div>
			</div>
			</form>
			<? if ($canedit) { ?>
			<form method="post" action="templates.php" id="deletestudytemplateform" onsubmit="return confirm('Are you sure you want to delete this template?')">
				<input type="hidden" name="action" value="deletestudytemplate">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				<input type="hidden" name="templateid" value="<?=$templateid?>">
				<?=CSRFTokenField()?>
			</form>
			<? } ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ProjectTemplateForm ---------------- */
	/* -------------------------------------------- */
	function ProjectTemplateForm($projectid, $ptid, $canedit) {
		$projectid = (int)$projectid;
		$template = GetProjectTemplate($projectid, $ptid);
		if ($template == null) {
			Error("Project template not found");
			return;
		}
		$ptid = (int)$template['projecttemplate_id'];
		$ro = $canedit ? "" : "readonly";
		$modalities = (array)GetModalityList();
		?>

		<div class="ui container">
			<div class="ui top attached visible message">
			  <div class="header"><?=htmlspecialchars($template['template_name'])?></div>
			</div>

		<form action="templates.php" method="post" name="theform" id="theform" class="ui form attached fluid segment">
		<input type="hidden" name="action" value="updateprojecttemplate">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="ptid" value="<?=$ptid?>">
		<?=CSRFTokenField()?>
		<? if ($canedit) { ?><span class="tiny">Leave visit type blank to delete</span><? } ?>
		<table class="ui selectable grey very compact table">
			<thead>
				<th>Visit Type</th>
				<th>Modality</th>
				<th>Series<br><span class="tiny">Comma separated list of protocol names</span></th>
				<th>Description</th>
				<th>Operator</th>
				<th>Physician</th>
				<th>Site</th>
				<th>Notes</th>
			</thead>
		<?
		/* the studies in this project template, then 5 blank rows for new ones */
		$studies = TemplateQuery("select * from project_templatestudies where pt_id = ? order by pts_order asc", 'i', [$ptid]);
		if ($canedit) {
			for ($i=0;$i<5;$i++)
				$studies[] = array('pts_id' => 0);
		}
		foreach ($studies as $row) {
			$ptsid = (int)$row['pts_id'];
			$pts_modality = (string)($row['pts_modality'] ?? '');
			$str = "";
			if ($ptsid > 0) {
				$p = array();
				foreach (TemplateQuery("select ptsitem_protocol from project_templatestudyitems where pts_id = ? order by ptsitem_order asc", 'i', [$ptsid]) as $rowA)
					$p[] = $rowA['ptsitem_protocol'];
				$str = implode(", ", $p);
			}
			?>
			<tr>
				<td><input type="text" name="visittype[]" value="<?=htmlspecialchars((string)($row['pts_visittype'] ?? ''))?>" <?=$ro?>></td>
				<td>
					<select name="modality[]" <?=($canedit ? "" : "disabled")?>>
						<option value="">Select modality</option>
						<?
							foreach ($modalities as $modality) {
								$checked = ($modality == $pts_modality) ? "selected" : "";
								?><option value="<?=htmlspecialchars($modality)?>" <?=$checked?>><?=htmlspecialchars($modality)?></option><?
							}
						?>
					</select>
				</td>
				<td><input type="text" name="protocols[]" value="<?=htmlspecialchars($str)?>" size="60" <?=$ro?>></td>
				<td><input type="text" name="studydesc[]" value="<?=htmlspecialchars((string)($row['pts_desc'] ?? ''))?>" <?=$ro?>></td>
				<td><input type="text" name="studyoperator[]" value="<?=htmlspecialchars((string)($row['pts_operator'] ?? ''))?>" <?=$ro?>></td>
				<td><input type="text" name="studyphysician[]" value="<?=htmlspecialchars((string)($row['pts_physician'] ?? ''))?>" <?=$ro?>></td>
				<td><input type="text" name="studysite[]" value="<?=htmlspecialchars((string)($row['pts_site'] ?? ''))?>" <?=$ro?>></td>
				<td><input type="text" name="studynotes[]" value="<?=htmlspecialchars((string)($row['pts_notes'] ?? ''))?>" <?=$ro?>></td>
			</tr>
			<?
		}
		?>
		</table>
			<div class="ui two column grid">
				<div class="column">
					<? if ($canedit) { ?>
					<button type="submit" form="deleteprojecttemplateform" class="ui red button"><i class="trash icon"></i>Delete</button>
					<? } ?>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="templates.php?projectid=<?=$projectid?>">Back</a>
					<? if ($canedit) { ?><input type="submit" value="Save" class="ui button primary"><? } ?>
				</div>
			</div>
		</form>
		<? if ($canedit) { ?>
		<form method="post" action="templates.php" id="deleteprojecttemplateform" onsubmit="return confirm('Are you sure you want to delete this template?')">
			<input type="hidden" name="action" value="deleteprojecttemplate">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<input type="hidden" name="ptid" value="<?=$ptid?>">
			<?=CSRFTokenField()?>
		</form>
		<? } ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyTemplate ---------------- */
	/* -------------------------------------------- */
	function UpdateStudyTemplate($projectid, $templateid, $itemprotocol) {
		$template = GetStudyTemplate($projectid, $templateid);
		if ($template == null) { Error("Study template not found"); return; }
		$templateid = (int)$template['studytemplate_id'];
		$itemprotocol = is_array($itemprotocol) ? $itemprotocol : array();

		mysqli_begin_transaction($GLOBALS['linki']);

		/* replace the template's items */
		TemplateQuery("delete from study_templateitems where studytemplate_id = ?", 'i', [$templateid]);
		$i = 0;
		foreach ($itemprotocol as $protocol) {
			$protocol = trim((string)$protocol);
			if ($protocol != "") {
				TemplateQuery("insert into study_templateitems (studytemplate_id, item_order, item_protocol) values (?, ?, ?)", 'iis', [$templateid, $i, $protocol]);
				$i++;
			}
		}

		mysqli_commit($GLOBALS['linki']);
		Notice("Study template updated");
	}


	/* -------------------------------------------- */
	/* ------- UpdateProjectTemplate -------------- */
	/* -------------------------------------------- */
	function UpdateProjectTemplate($projectid, $ptid, $visittype, $modality, $protocols, $studydesc, $studyoperator, $studyphysician, $studysite, $studynotes) {
		$template = GetProjectTemplate($projectid, $ptid);
		if ($template == null) { Error("Project template not found"); return; }
		$ptid = (int)$template['projecttemplate_id'];

		foreach (array('visittype', 'modality', 'protocols', 'studydesc', 'studyoperator', 'studyphysician', 'studysite', 'studynotes') as $var) {
			if (!is_array($$var)) $$var = array();
		}
		$modalities = (array)GetModalityList();

		mysqli_begin_transaction($GLOBALS['linki']);

		/* replace the template's studies and their items */
		TemplateQuery("delete from project_templatestudyitems where pts_id in (select pts_id from project_templatestudies where pt_id = ?)", 'i', [$ptid]);
		TemplateQuery("delete from project_templatestudies where pt_id = ?", 'i', [$ptid]);

		$studyorder = 0;
		foreach ($visittype as $i => $vtype) {
			$v = trim((string)$vtype);
			if ($v == "") continue;
			$m = (string)($modality[$i] ?? '');
			if (!in_array($m, $modalities, true)) $m = '';

			$params = [$ptid, $studyorder, $v, $m, (string)($studydesc[$i] ?? ''), (string)($studyoperator[$i] ?? ''), (string)($studyphysician[$i] ?? ''), (string)($studysite[$i] ?? ''), (string)($studynotes[$i] ?? '')];
			TemplateQuery("insert into project_templatestudies (pt_id, pts_order, pts_visittype, pts_modality, pts_desc, pts_operator, pts_physician, pts_site, pts_notes) values (?, ?, ?, ?, ?, ?, ?, ?, ?)", 'iisssssss', $params);
			$ptsid = (int)mysqli_insert_id($GLOBALS['linki']);

			$ii = 0;
			foreach (explode(",", (string)($protocols[$i] ?? '')) as $item) {
				/* in case they used quotes */
				$item = trim(str_replace(array('"', '\\'), '', $item));
				if ($item == "") continue;
				TemplateQuery("insert into project_templatestudyitems (pts_id, ptsitem_order, ptsitem_protocol) values (?, ?, ?)", 'iis', [$ptsid, $ii, $item]);
				$ii++;
			}
			$studyorder++;
		}
		TemplateQuery("update project_template set template_modifydate = now() where projecttemplate_id = ?", 'i', [$ptid]);

		mysqli_commit($GLOBALS['linki']);
		Notice("Study project template updated");
	}


	/* -------------------------------------------- */
	/* ------- DeleteStudyTemplate ---------------- */
	/* -------------------------------------------- */
	function DeleteStudyTemplate($projectid, $templateid) {
		$template = GetStudyTemplate($projectid, $templateid);
		if ($template == null) { Error("Invalid study template ID"); return; }
		$templateid = (int)$template['studytemplate_id'];

		TemplateQuery("delete from study_templateitems where studytemplate_id = ?", 'i', [$templateid]);
		TemplateQuery("delete from study_template where studytemplate_id = ?", 'i', [$templateid]);
		Notice("Template deleted");
	}


	/* -------------------------------------------- */
	/* ------- DeleteProjectTemplate -------------- */
	/* -------------------------------------------- */
	function DeleteProjectTemplate($projectid, $ptid) {
		$template = GetProjectTemplate($projectid, $ptid);
		if ($template == null) { Error("Invalid project template ID"); return; }
		$ptid = (int)$template['projecttemplate_id'];

		/* the template's studies and their items go with it */
		TemplateQuery("delete from project_templatestudyitems where pts_id in (select pts_id from project_templatestudies where pt_id = ?)", 'i', [$ptid]);
		TemplateQuery("delete from project_templatestudies where pt_id = ?", 'i', [$ptid]);
		TemplateQuery("delete from project_template where projecttemplate_id = ?", 'i', [$ptid]);
		Notice("Project Template deleted");
	}

?>


<? include("footer.php") ?>
