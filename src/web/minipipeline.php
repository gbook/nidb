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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Behavioral data analysis pipelines</title>
	</head>
<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetSafeAction(array("add", "update", "delete", "rerunjob")); /* mutating actions require POST + CSRF token */
	$mpid = (int)GetVariable("mpid");
	$mpjobid = (int)GetVariable("mpjobid");
	$projectid = (int)GetVariable("projectid");
	$minipipelinename = GetVariable("minipipelinename");
	$scriptexecutableids = GetVariable("scriptexecutableids");
	if (!is_array($scriptexecutableids)) $scriptexecutableids = array();
	$scriptentrypointid = (int)GetVariable("scriptentrypointid");
	$scriptdeleteids = GetVariable("scriptdeleteids");
	if (!is_array($scriptdeleteids)) $scriptdeleteids = array();
	$scriptparams = GetVariable("scriptparams");
	if (!is_array($scriptparams)) $scriptparams = array();
	$scriptid = (int)GetVariable("scriptid");

	/* mini-pipelines belong to a project and their scripts are run on the server, writing observations
	   into the project: View Data to see them, Edit Data to add/change/delete/rerun. For an existing
	   mini-pipeline (or one of its scripts) the project is looked up from the database, never taken
	   from the request */
	if ($action == "viewscript")
		$mpid = GetMiniPipelineIDForScript($scriptid);
	if (in_array($action, array("editform", "update", "delete", "viewjobs", "viewscript", "rerunjob")))
		$projectid = GetMiniPipelineProjectID($mpid);
	$canview = MiniPipelineProjectPerm($projectid, 'viewdata');
	$canedit = MiniPipelineProjectPerm($projectid, 'modifydata');
	$needsedit = in_array($action, array("addform", "add", "update", "delete", "rerunjob"));

	/* determine action */
	if ((!$canview) || ($needsedit && !$canedit)) {
		Error("You do not have permission to " . ($needsedit ? "change" : "view") . " mini-pipelines in this project");
		if ($canview) DisplayMiniPipelineList($projectid, $canedit);
	}
	elseif ($action == "editform") {
		DisplayMiniPipelineForm($projectid, "edit", $mpid, $canedit);
	}
	elseif ($action == "addform") {
		DisplayMiniPipelineForm($projectid, "add", "", $canedit);
	}
	/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit */
	elseif ($action == "update") {
		ob_start();
		UpdateMiniPipeline($mpid, $minipipelinename, $scriptexecutableids, $scriptentrypointid, $scriptdeleteids, $scriptparams);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("minipipeline.php?projectid=$projectid");
	}
	elseif ($action == "viewjobs") {
		DisplayMiniPipelineJobs($mpid, $projectid, $canedit);
	}
	elseif ($action == "add") {
		ob_start();
		AddMiniPipeline($projectid, $minipipelinename);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("minipipeline.php?projectid=$projectid");
	}
	elseif ($action == "delete") {
		ob_start();
		DeleteMiniPipeline($mpid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("minipipeline.php?projectid=$projectid");
	}
	elseif ($action == "viewscript") {
		ViewScript($projectid, $scriptid);
	}
	elseif ($action == "rerunjob") {
		ob_start();
		ReRunJob($mpjobid, $mpid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("minipipeline.php?action=viewjobs&mpid=$mpid");
	}
	else {
		DisplayMiniPipelineList($projectid, $canedit);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetMiniPipelineProjectID ----------- */
	/* -------------------------------------------- */
	/* the project a mini-pipeline belongs to, or 0 if it doesn't exist */
	function GetMiniPipelineProjectID($mpid) {
		$mpid = (int)$mpid;
		if ($mpid < 1) return 0;

		$sqlstring = "select project_id from minipipelines where minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $mpid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['project_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- GetMiniPipelineIDForScript --------- */
	/* -------------------------------------------- */
	/* the mini-pipeline a script belongs to, or 0 if the script doesn't exist */
	function GetMiniPipelineIDForScript($scriptid) {
		$scriptid = (int)$scriptid;
		if ($scriptid < 1) return 0;

		$sqlstring = "select minipipeline_id from minipipeline_scripts where minipipelinescript_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $scriptid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($scriptid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['minipipeline_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- MiniPipelineProjectPerm ------------ */
	/* -------------------------------------------- */
	/* true if the current user has $perm ('viewdata' or 'modifydata') on the project */
	function MiniPipelineProjectPerm($projectid, $perm) {
		$projectid = (int)$projectid;
		if ($projectid < 1) return false;

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return (GetPerm($perms, $perm, $projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- ReRunJob --------------------------- */
	/* -------------------------------------------- */
	function ReRunJob($mpjobid, $mpid) {
		$mpjobid = (int)$mpjobid;
		$mpid = (int)$mpid;
		$sqlstring = "update minipipeline_jobs set mp_status = 'pending', mp_queuedate = now() where minipipelinejob_id = ? and minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $mpjobid, $mpid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpjobid, $mpid));
		mysqli_stmt_close($stmt);
		
		Notice("Minipipeline job queued to be re-run");
	}


	/* -------------------------------------------- */
	/* ------- UpdateMiniPipeline ----------------- */
	/* -------------------------------------------- */
	function UpdateMiniPipeline($mpid, $minipipelinename, $scriptexecutableids, $scriptentrypointid, $scriptdeleteids, $scriptparams) {
		$mpid = (int)$mpid;
		if (!ValidID($mpid,'MiniPipeline ID')) { return; }
		
		if (is_null($scriptentrypointid) || ($scriptentrypointid == 0)) {
			Notice("No entry point set. An entry point script must be set.");
		}
			
		/* update the minipipeline */
		$sqlstring = "update minipipelines set mp_version = mp_version + 1, mp_name = ?, mp_modifydate = now() where minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $minipipelinename, $mpid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($minipipelinename, $mpid));
		mysqli_stmt_close($stmt);
		
		/* script-level updates below only touch scripts belonging to this minipipeline */
		
		/* update executable flags: clear all, then set the checked ones */
		$sqlstring = "update minipipeline_scripts set mp_executable = 0 where minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $mpid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
		mysqli_stmt_close($stmt);
		$sqlstring = "update minipipeline_scripts set mp_executable = 1 where minipipelinescript_id = ? and minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		foreach ($scriptexecutableids as $scriptexecutableid) {
			$scriptexecutableid = (int)$scriptexecutableid;
			mysqli_stmt_bind_param($stmt, 'ii', $scriptexecutableid, $mpid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($scriptexecutableid, $mpid));
		}
		mysqli_stmt_close($stmt);
		
		/* update entry point flag */
		if ((!is_null($scriptentrypointid)) && ($scriptentrypointid > -1)) {
			$scriptentrypointid = (int)$scriptentrypointid;

			/* remove previous flags */
			$sqlstring = "update minipipeline_scripts set mp_entrypoint = 0 where minipipeline_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $mpid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
			mysqli_stmt_close($stmt);

			/* set new flag */
			$sqlstring = "update minipipeline_scripts set mp_entrypoint = 1 where minipipelinescript_id = ? and minipipeline_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $scriptentrypointid, $mpid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($scriptentrypointid, $mpid));
			mysqli_stmt_close($stmt);
		}
		
		/* update parameter lists */
		$sqlstring = "update minipipeline_scripts set mp_parameterlist = ? where minipipelinescript_id = ? and minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		foreach ($scriptparams as $scriptid => $param) {
			$scriptid = (int)$scriptid;
			$param = (string)$param;
			mysqli_stmt_bind_param($stmt, 'sii', $param, $scriptid, $mpid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($param, $scriptid, $mpid));
		}
		mysqli_stmt_close($stmt);
		
		/* remove files to be removed last... in case the user updated an option and wanted to delete it as well */
		$sqlstring = "delete from minipipeline_scripts where minipipelinescript_id = ? and minipipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		foreach ($scriptdeleteids as $scriptdeleteid) {
			$scriptdeleteid = (int)$scriptdeleteid;
			mysqli_stmt_bind_param($stmt, 'ii', $scriptdeleteid, $mpid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($scriptdeleteid, $mpid));
		}
		mysqli_stmt_close($stmt);
		
		/* add new files */
		AddMiniPipelineScripts($mpid);
		
		Notice(htmlspecialchars($minipipelinename ?? '') . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddMiniPipelineScripts ------------- */
	/* -------------------------------------------- */
	/* store any scripts uploaded in the 'scripts[]' field with this minipipeline */
	function AddMiniPipelineScripts($mpid) {
		if (!isset($_FILES['scripts']['name']) || !is_array($_FILES['scripts']['name']))
			return;

		$sqlstring = "insert into minipipeline_scripts (minipipeline_id, mp_version, mp_executable, mp_scriptname, mp_script, mp_scriptsize, mp_scriptmodifydate, mp_scriptcreatedate) values (?, 1, 0, ?, ?, ?, now(), now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$total_files = count($_FILES['scripts']['name']);
		for ($key = 0; $key < $total_files; $key++) {
			/* check if file is selected */
			if (isset($_FILES['scripts']['name'][$key]) && $_FILES['scripts']['size'][$key] > 0) {
				/* the backend writes each script to <tmpdir>/<name> and runs the entry point by name, so the name
				   must be a plain filename that is also safe on a command line */
				$scriptFilename = basename($_FILES['scripts']['name'][$key]);
				if (!preg_match('/^[A-Za-z0-9._-]+$/', $scriptFilename) || ($scriptFilename == ".") || ($scriptFilename == "..")) {
					Error("Script <tt>" . htmlspecialchars($scriptFilename) . "</tt> was not added. Script names may only contain letters, numbers, and <tt>. _ -</tt>");
					continue;
				}
				$scriptData = base64_encode(file_get_contents($_FILES['scripts']['tmp_name'][$key]));
				$scriptSize = (int)$_FILES['scripts']['size'][$key];

				mysqli_stmt_bind_param($stmt, 'issi', $mpid, $scriptFilename, $scriptData, $scriptSize);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid, $scriptFilename, '(script)', $scriptSize));
			}
		}
		mysqli_stmt_close($stmt);
	}

	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineJobs ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineJobs($mpid, $projectid, $canedit) {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
		$mpid = (int)$mpid;
		$projectid = (int)$projectid;
		$sqlstring = "select a.*, b.mp_name from minipipeline_jobs a left join minipipelines b on a.minipipeline_id = b.minipipeline_id where a.minipipeline_id = ? order by a.mp_queuedate desc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $mpid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
		mysqli_stmt_close($stmt);
		?>
		<a class="ui primary button" href="minipipeline.php?projectid=<?=$projectid?>"><i class="arrow alternate circle left icon"></i> Back</a>
		<table class="ui celled selectable grey compact table">
			<thead>
				<th>Mini-pipeline</th>
				<th>Queue date</th>
				<th>Start date</th>
				<th>Complete date</th>
				<th>Status</th>
				<th>Logs</th>
				<th>Study</th>
				<th>Modality</th>
				<th>Rows inserted</th>
				<th></th>
			</thead>
			<tbody>
		<?
		$i = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mpjobid = $row['minipipelinejob_id'];
			$mpname = $row['mp_name'];
			$queuedate = $row['mp_queuedate'];
			$startdate = $row['mp_startdate'];
			$enddate = $row['mp_enddate'];
			$status = $row['mp_status'];
			$logs = $row['mp_log'];
			$modality = $row['mp_modality'];
			$seriesid = $row['mp_seriesid'];
			$numinserts = $row['mp_numinserts'];
			$logs = str_replace("<", "&lt;", $logs);
			$logs = str_replace(">", "&gt;", $logs);
			
			list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality)
			?>
			<div class="ui scrolling modal" id="modal<?=$i?>">
				<i class="close icon"></i>
				<div class="header">
					Viewing log file
				</div>
				<div class="scrolling content" style="overflow: auto">
					<pre class="tt" style="white-space: pre-wrap;"><?=$logs?></pre>
				</div>
				<div class="actions">
					<div class="ui approve button">Close</div>
				</div>
			</div>
			<tr class="<? if (($status == "error") or ($numinserts < 1)) { echo "negative"; } ?>">
				<td valign="top"><?=htmlspecialchars($mpname ?? '')?></td>
				<td valign="top"><?=htmlspecialchars($queuedate ?? '')?></td>
				<td valign="top"><?=htmlspecialchars($startdate ?? '')?></td>
				<td valign="top"><?=htmlspecialchars($enddate ?? '')?></td>
				<td valign="top"><?=htmlspecialchars($status ?? '')?></td>
				<td valign="top" class="scrolling content">
					<button class="ui compact basic button" id="viewlog<?=$i?>" onClick="$('#modal<?=$i?>').modal('show');"><i class="file alternate icon"></i> View Logs</button>
				</td>
				<td valign="top"><a href="studies.php?studyid=<?=(int)$studyid?>"><?=htmlspecialchars($uid . $studynum)?></a></td>
				<td valign="top"><?=htmlspecialchars($modality ?? '')?></td>
				<td valign="top"><?=(int)$numinserts?></td>
				<td valign="top">
					<? if (($status != "pending") && ($canedit)) { ?>
					<form method="post" action="minipipeline.php" style="display: inline">
						<input type="hidden" name="action" value="rerunjob">
						<input type="hidden" name="mpjobid" value="<?=$mpjobid?>">
						<input type="hidden" name="mpid" value="<?=$mpid?>">
						<input type="hidden" name="projectid" value="<?=$projectid?>">
						<?=CSRFTokenField()?>
						<button type="submit" class="ui very compact button">Rerun</button>
					</form>
					<? } ?>
				</td>
			</tr>
			<?
			$i++;
		}
		?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- AddMiniPipeline -------------------- */
	/* -------------------------------------------- */
	function AddMiniPipeline($projectid, $minipipelinename) {
		$projectid = (int)$projectid;
		if (!ValidID($projectid,'Project ID')) { return; }
		
		/* insert the new minipipeline */
		$sqlstring = "insert into minipipelines (project_id, mp_version, mp_name, mp_modifydate, mp_createdate) values (?, 1, ?, now(), now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $minipipelinename);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($projectid, $minipipelinename));
		$mpid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);

		AddMiniPipelineScripts($mpid);

		Notice(htmlspecialchars($minipipelinename ?? '') . " added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteMiniPipeline ----------------- */
	/* -------------------------------------------- */
	function DeleteMiniPipeline($mpid) {
		if (!ValidID($mpid,'MiniPipeline ID')) { return; }
		
		$mpid = (int)$mpid;
		foreach (array("delete from minipipelines where minipipeline_id = ?", "delete from minipipeline_scripts where minipipeline_id = ?") as $sqlstring) {
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $mpid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
			mysqli_stmt_close($stmt);
		}
		
		Notice("Mini-pipeline deleted");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineForm ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineForm($projectid, $type, $mpid, $canedit) {
		$projectid = (int)$projectid;
		$readonly = $canedit ? "" : "disabled"; /* users with View Data only see the mini-pipeline, without edit controls */
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			if (!ValidID($mpid,'MiniPipeline ID'))
				return;
			
			$mpid = (int)$mpid;
			$sqlstring = "select * from minipipelines where minipipeline_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $mpid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$minipipelineid = $row['minipipeline_id'];
			$name = $row['mp_name'];
		
			$formaction = "update";
			$formtitle = htmlspecialchars($name ?? '');
			$submitbuttonlabel = "Update";
		}
		else {
			$name = "";
			$formaction = "add";
			$formtitle = "New mini-pipeline";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>
			<form method="post" action="minipipeline.php" enctype="multipart/form-data" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="mpid" value="<?=$mpid?>">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<?=CSRFTokenField()?>

			<div class="field">
				<label>Name</label>
				<div class="field">
					<input type="text" name="minipipelinename" value="<?=htmlspecialchars($name ?? '')?>" maxlength="255" required <?=$readonly?>>
				</div>
			</div>
			
			<div class="field">
				<label>Script(s)</label>
				<div class="field">
					<? if ($type == "edit") { ?>
					<table class="ui celled selectable small very compact table">
						<thead>
							<th>Script</th>
							<th>Size</th>
							<th>Executable?</th>
							<th>Entry point?</th>
							<th>Create date</th>
							<th>Modify date</th>
							<? if ($canedit) { ?><th>Remove?</th><? } ?>
						</thead>
						<?
						$sqlstring = "select * from minipipeline_scripts where minipipeline_id = ? order by mp_scriptname asc";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $mpid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($mpid));
						mysqli_stmt_close($stmt);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$scriptid = $row['minipipelinescript_id'];
							$script = $row['mp_scriptname'];
							$executable = $row['mp_executable'];
							$entrypoint = $row['mp_entrypoint'];
							$scriptsize = $row['mp_scriptsize'];
							$createdate = date('M j, Y h:ia',strtotime($row['mp_scriptcreatedate']));
							$modifydate = date('M j, Y h:ia',strtotime($row['mp_scriptmodifydate']));
							?>
							<tr>
								<td><tt style="font-size: larger"><a href="minipipeline.php?action=viewscript&scriptid=<?=(int)$scriptid?>"><?=htmlspecialchars($script ?? '')?></a></tt></td>
								<td><?=(int)$scriptsize?></td>
								<td><input type="checkbox" name="scriptexecutableids[]" value="<?=(int)$scriptid?>" <? if ($executable) { echo "checked"; }?> <?=$readonly?>></td>
								<td><input type="radio" name="scriptentrypointid" value="<?=(int)$scriptid?>" <? if ($entrypoint) { echo "checked"; }?> <?=$readonly?>></td>
								<td style="font-size: smaller"><?=$createdate?></td>
								<td style="font-size: smaller"><?=$modifydate?></td>
								<? if ($canedit) { ?><td><input type="checkbox" name="scriptdeleteids[]" value="<?=(int)$scriptid?>"></td><? } ?>
							</tr>
							<?
						}
						?>
					</table>
					<br>
					<? } ?>
					<? if ($canedit) { ?>
					Add script(s) <input type="file" name="scripts[]" multiple>
					<span class="tiny">Max individual file size 4GB. Max filename length 255 characters</span>
					<? } ?>
				</div>
			</div>
			<div class="ui two column grid">
				<div class="column">
					<? if (($type == "edit") && ($canedit)) { ?>
					<!-- forms can't nest, so this submits the separate delete form below -->
					<button type="submit" form="deleteminipipelineform" class="ui red button"><i class="trash icon"></i>Delete</button>
					<? } ?>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="minipipeline.php?projectid=<?=$projectid?>"><?=$canedit ? "Cancel" : "Back"?></a>
					<? if ($canedit) { ?><input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>"><? } ?>
				</div>
			</div>
			</form>
			<? if (($type == "edit") && ($canedit)) { ?>
			<form method="post" action="minipipeline.php" id="deleteminipipelineform" onsubmit="return confirm('Are you sure you want to delete this mini-pipeline and all of its scripts? Any variables created by it will remain in the database.')">
				<input type="hidden" name="action" value="delete">
				<input type="hidden" name="mpid" value="<?=$mpid?>">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				<?=CSRFTokenField()?>
			</form>
			<? } ?>
			<div class="ui attached segment">
				<br>
				<div class="ui accordion">
					<div class="title">
						<i class="dropdown icon"></i>
						How to make a mini-pipeline
					</div>
					<div class="content">
						<p>A mini-pipeline is meant to be a small script that extracts values from behavioral data files. Because it not meant to process images, the execution time of the mini-pipeline is limited to 5 minutes. It also does not have access to the cluster or any shared network resources.</p>
						<p>One script is identified as the entry point into the mini-pipeline. This script must accept as input through the command line a list of all of the files in the data or beh directory. The filenames will be sorted alphabetically. This is the script that will be called, and it can call any of the other scripts/files that are part of the mini-pipeline.
						<p>All scripts and behavioral files are copied to a temporary directory for the mini-pipeline to run. The output of the mini-pipeline must be in the following format, written to a file named <code>output.csv</code></p>
						<div style="border: 1px dashed #777; padding-left: 15px">
						<tt><pre>Type, VariableName, StartDate, EndDate, Duration, Value, Units, Notes, Instrument
observation, EyeContact, 2012-10-22, , 3000, 34.9, , "Sneezed at minute 3", ADOS
vital, BloodPressure, 2019-11-06 09:23, , , "122/70", , ,
intervention, Ketamine, 2018-03-17 19:56, 2018-03-17 19:58, 120, 2.2, ml, "Fine", 
... </pre></tt>
						</div>
						<p>Notes about the output formats
						<ul>
							<li>Format must be in .csv, blank values still need a comma even if no values
							<li>Header must be on the first row
							<li>Possible types are <b>observation</b>, <b>vital</b>, <b>intervention</b>
							<li>Dates must be in <tt>YYYY-MM-DD</tt> format, with leading zeros (ex, <tt>03</tt> for March)
							<li>Dates can include times, but time must be 24hr format, with leading zeros (<tt>14:03</tt>), with or without seconds (<tt>12:45:11</tt>)
							<li>Duration is in seconds
							<li>Any strings with spaces must be enclosed in double quotes
							<li>If <i>VariableName</i> already exists for a study, it will be updated to the value from the latest run of the mini-pipeline
							<li><tt>Type</tt>, <tt>VariableName</tt>, <tt>Value</tt> are the only required columns
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineList ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineList($projectid, $canedit) {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
		$projectid = (int)$projectid;
		if (!ValidID($projectid,'Project ID')) { return; }
		
		?>
		<div class="ui container">
			<? if ($canedit) { ?>
			<button class="ui primary large button" onClick="window.location.href='minipipeline.php?projectid=<?=$projectid?>&action=addform'; return false;"><i class="plus square outline icon"></i> Create mini-pipeline</button>
			<? } ?>
			<br><br>
			<table class="ui celled selectable grey compact table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Jobs</th>
						<th>Version</th>
						<th>Create date</th>
						<th>Modify date</th>
						<th>Script(s)</th>
						<!--<th>Delete</th>-->
					</tr>
				</thead>
				<tbody>
					<?
						$sqlstring = "select * from minipipelines where project_id = ? order by mp_name asc";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $projectid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($projectid));
						mysqli_stmt_close($stmt);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$mpid = $row['minipipeline_id'];
							$name = $row['mp_name'];
							$version = $row['mp_version'];
							$mpcreatedate = date('M j, Y h:ia',strtotime($row['mp_createdate']));
							$mpmodifydate = date('M j, Y h:ia',strtotime($row['mp_modifydate']));
							
							?>
							<tr>
								<td valign="top"><a class="ui primary compact button" href="minipipeline.php?action=editform&mpid=<?=(int)$mpid?>"><i class="edit icon"></i> <?=htmlspecialchars($name ?? '')?></a></td>
								<td valign="top"><a class="ui compact basic button" href="minipipeline.php?action=viewjobs&mpid=<?=(int)$mpid?>"><i class="file alternate icon"></i> View Jobs</a></td>
								<td valign="top"><?=(int)$version?></td>
								<td valign="top"><?=$mpcreatedate?></td>
								<td valign="top"><?=$mpmodifydate?></td>
								<td valign="top" align="center">
								<?
									$mpid = (int)$mpid;
									$sqlstringA = "select * from minipipeline_scripts where minipipeline_id = $mpid order by mp_scriptname asc";
									$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__); /* $mpid is a database integer */
									if (mysqli_num_rows($resultA) > 0) {
								?>
									<table style="font-size: 9pt" class="ui very small very compact celled selectable table">
										<thead>
											<tr>
												<th>Script</th>
												<th>Size</th>
												<th>Executable</th>
												<th>Entry point</th>
											</tr>
										</thead>
									<?
									while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
										$script = $rowA['mp_scriptname'];
										$params = $rowA['mp_parameterlist'];
										$executable = $rowA['mp_executable'];
										$entrypoint = $rowA['mp_entrypoint'];
										$scriptsize = $rowA['mp_scriptsize'];
										$createdate = date('M j, Y h:ia',strtotime($rowA['mp_scriptcreatedate']));
										$modifydate = date('M j, Y h:ia',strtotime($rowA['mp_scriptmodifydate']));
										?>
										<tr>
											<td><tt><?=htmlspecialchars($script ?? '')?> <i><?=htmlspecialchars($params ?? '')?></i></tt></td>
											<td><?=(int)$scriptsize?></td>
											<td><? if ($executable) { echo "&#10004;"; }?></td>
											<td><? if ($entrypoint) { echo "&#10004;"; }?></td>
										</tr>
										<?
									}
									?>
									</table>
									<?
									}
									else {
										echo "None";
									}
									?>
								</td>
							</tr>
							<?
						}
					?>
				</tbody>
			</table>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ViewScript ------------------------- */
	/* -------------------------------------------- */
	function ViewScript($projectid, $scriptid) {

		$projectid = (int)$projectid;
		$scriptid = (int)$scriptid;
		if (!ValidID($projectid,'Project ID')) { return; }
		if (!ValidID($scriptid,'Script ID')) { return; }
		
		$sqlstring = "select * from minipipeline_scripts where minipipelinescript_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $scriptid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($scriptid));
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$mpid = $row['minipipeline_id'];
			$scriptid = $row['minipipelinescript_id'];
			$scriptname = $row['mp_scriptname'];
			$contents = base64_decode($row['mp_script']);
			$executable = $row['mp_executable'];
			$entrypoint = $row['mp_entrypoint'];
			$scriptsize = $row['mp_scriptsize'];
			$createdate = date('M j, Y h:ia',strtotime($row['mp_scriptcreatedate']));
			$modifydate = date('M j, Y h:ia',strtotime($row['mp_scriptmodifydate']));
			?>
			
			<div class="ui text container">
				<a href="minipipeline.php?action=editform&mpid=<?=(int)$mpid?>" class="ui primary button"><b><i class="arrow alternate circle left icon"></i> Back</b></a>
				<br>
				<table class="ui very simple very compact small celled table">
					<tr>
						<td>Script name</td>
						<td><h3 class="ui header"><?=htmlspecialchars($scriptname ?? '')?></h3></td>
					</tr>
					<tr>
						<td>Executable?</td>
						<td><? if ($executable) { echo "&#10004;"; } ?></td>
					</tr>
					<tr>
						<td>Entry point?</td>
						<td><? if ($entrypoint) { echo "&#10004;"; } ?></td>
					</tr>
					<tr>
						<td>Script size</td>
						<td><?=(int)$scriptsize?> <span class="tiny">bytes</span></td>
					</tr>
					<tr>
						<td>Create date</td>
						<td><?=$createdate?></td>
					</tr>
					<tr>
						<td>Modify date</td>
						<td><?=$modifydate?></td>
					</tr>
				</table>
			</div>
			<div style="padding: 20px">
				Displaying entire file
				<tt><pre style="text-align: left; padding: 15px; border: 1px solid gray"><?=htmlspecialchars($contents, ENT_QUOTES | ENT_SUBSTITUTE)?></pre></tt>
			</div>
			<?
		}
	}
	
?>


<? include("footer.php") ?>
