<?
 // ------------------------------------------------------------------------------
 // NiDB experiment.php
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

	require "functions.php";
	require "includes_php.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$expid = (int)GetVariable("expid");
	$projectid = (int)GetVariable("projectid");
	$experimentname = GetVariable("experimentname");
	$experimentdesc = GetVariable("experimentdesc");
	$filedeleteids = GetVariable("filedeleteids");
	if (!is_array($filedeleteids)) $filedeleteids = array();
	$fileid = (int)GetVariable("fileid");

	/* experiments belong to a project: View Data to list/view/download them, Edit Data to add/change/delete.
	   For an existing experiment (or one of its files) the project is looked up from the database, never
	   taken from the request */
	if ($action == "viewfile")
		$expid = GetExperimentIDForFile($fileid);
	if (in_array($action, array("editform", "update", "delete", "download", "viewfile")))
		$projectid = GetExperimentProjectID($expid);
	$canview = ExperimentProjectPerm($projectid, 'viewdata');
	$canedit = ExperimentProjectPerm($projectid, 'modifydata');

	/* determine action */
	if (($action == "download") && ($canview)) {
		DownloadExperiment($expid); /* sends the zip and exits, before any HTML */
	}
?>
<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Experiments</title>
	</head>
<body>
	<div id="wrapper">
<?
	require "includes_html.php";
	require "menu.php";

	$action = GetSafeAction(array("add", "update", "delete")); /* mutating actions require POST + CSRF token */
	$needsedit = in_array($action, array("addform", "add", "update", "delete"));

	if ((!$canview) || ($needsedit && !$canedit)) {
		Error("You do not have permission to " . ($needsedit ? "change" : "view") . " experiments in this project");
		if ($canview) DisplayExperimentList($projectid, $canedit);
	}
	elseif ($action == "editform") {
		DisplayExperimentForm($projectid, "edit", $expid, $canedit);
	}
	elseif ($action == "addform") {
		DisplayExperimentForm($projectid, "add", "", $canedit);
	}
	/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit */
	elseif ($action == "update") {
		ob_start();
		UpdateExperiment($expid, $experimentname, $experimentdesc, $filedeleteids);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("experiment.php?projectid=$projectid");
	}
	elseif ($action == "add") {
		ob_start();
		AddExperiment($projectid, $experimentname, $experimentdesc);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("experiment.php?projectid=$projectid");
	}
	elseif ($action == "delete") {
		ob_start();
		DeleteExperiment($expid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("experiment.php?projectid=$projectid");
	}
	elseif ($action == "viewfile") {
		ViewFile($projectid, $fileid);
	}
	else {
		DisplayExperimentList($projectid, $canedit);
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetExperimentProjectID ------------- */
	/* -------------------------------------------- */
	/* the project an experiment belongs to, or 0 if the experiment doesn't exist */
	function GetExperimentProjectID($expid) {
		$expid = (int)$expid;
		if ($expid < 1) return 0;

		$sqlstring = "select project_id from experiments where experiment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $expid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($expid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['project_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- GetExperimentIDForFile ------------- */
	/* -------------------------------------------- */
	/* the experiment an experiment file belongs to, or 0 if the file doesn't exist */
	function GetExperimentIDForFile($fileid) {
		$fileid = (int)$fileid;
		if ($fileid < 1) return 0;

		$sqlstring = "select experiment_id from experiment_files where experimentfile_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $fileid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($fileid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['experiment_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- ExperimentProjectPerm -------------- */
	/* -------------------------------------------- */
	/* true if the current user has $perm ('viewdata' or 'modifydata') on the project */
	function ExperimentProjectPerm($projectid, $perm) {
		$projectid = (int)$projectid;
		if ($projectid < 1) return false;

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return (GetPerm($perms, $perm, $projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- UpdateExperiment ------------------- */
	/* -------------------------------------------- */
	function UpdateExperiment($expid, $experimentname, $experimentdesc, $filedeleteids) {
		$expid = (int)$expid;
		if (!ValidID($expid,'Experiment ID')) { return; }
		
		/* update the experiment */
		$sqlstring = "update experiments set exp_version = exp_version + 1, exp_name = ?, exp_desc = ?, exp_modifydate = now() where experiment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssi', $experimentname, $experimentdesc, $expid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($experimentname, $experimentdesc, $expid));
		mysqli_stmt_close($stmt);
		
		/* remove files to be removed last... in case the user updated an option and wanted to delete it as well.
		   Only files belonging to this experiment can be removed */
		$sqlstring = "delete from experiment_files where experimentfile_id = ? and experiment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		foreach ($filedeleteids as $filedeleteid) {
			$filedeleteid = (int)$filedeleteid;
			mysqli_stmt_bind_param($stmt, 'ii', $filedeleteid, $expid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($filedeleteid, $expid));
		}
		mysqli_stmt_close($stmt);
		
		/* add new files */
		AddExperimentFiles($expid);
		
		Notice(htmlspecialchars($experimentname ?? '') . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddExperimentFiles ----------------- */
	/* -------------------------------------------- */
	/* store any files uploaded in the 'files[]' field with this experiment */
	function AddExperimentFiles($expid) {
		if (!isset($_FILES['files']['name']) || !is_array($_FILES['files']['name']))
			return;

		$sqlstring = "insert into experiment_files (experiment_id, file_name, file, file_size, file_createdate, file_modifydate) values (?, ?, ?, ?, now(), now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$total_files = count($_FILES['files']['name']);
		for ($key = 0; $key < $total_files; $key++) {
			/* check if file is selected */
			if (isset($_FILES['files']['name'][$key]) && $_FILES['files']['size'][$key] > 0) {
				/* basename: the name is later used as a path when the experiment is downloaded */
				$fileFilename = basename($_FILES['files']['name'][$key]);
				if (($fileFilename == "") || ($fileFilename == ".") || ($fileFilename == "..")) continue;
				$fileData = base64_encode(file_get_contents($_FILES['files']['tmp_name'][$key]));
				$fileSize = (int)$_FILES['files']['size'][$key];

				mysqli_stmt_bind_param($stmt, 'issi', $expid, $fileFilename, $fileData, $fileSize);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($expid, $fileFilename, '(file)', $fileSize));
			}
		}
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- AddExperiment ---------------------- */
	/* -------------------------------------------- */
	function AddExperiment($projectid, $experimentname, $experimentdesc) {
		$projectid = (int)$projectid;
		if (!ValidID($projectid,'Project ID')) { return; }

		/* insert the new experiment */
		$sqlstring = "insert into experiments (project_id, exp_version, exp_name, exp_desc, exp_modifydate, exp_createdate) values (?, 1, ?, ?, now(), now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iss', $projectid, $experimentname, $experimentdesc);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($projectid, $experimentname, $experimentdesc));
		$expid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);

		AddExperimentFiles($expid);

		Notice(htmlspecialchars($experimentname ?? '') . " added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteExperiment ------------------- */
	/* -------------------------------------------- */
	function DeleteExperiment($expid) {
		if (!ValidID($expid,'Experiment ID')) { return; }
		
		$expid = (int)$expid;
		foreach (array("delete from experiments where experiment_id = ?", "delete from experiment_files where experiment_id = ?") as $sqlstring) {
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $expid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($expid));
			mysqli_stmt_close($stmt);
		}
		
		Notice("Experiment deleted");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayExperimentForm -------------- */
	/* -------------------------------------------- */
	function DisplayExperimentForm($projectid, $type, $expid, $canedit) {
		$projectid = (int)$projectid;
		$readonly = $canedit ? "" : "disabled"; /* users with View Data only see the experiment, without edit controls */
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			if (!ValidID($expid,'Experiment ID'))
				return;
			
			$expid = (int)$expid;
			$sqlstring = "select * from experiments where experiment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $expid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($expid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$name = $row['exp_name'] ?? '';
			$desc = $row['exp_desc'] ?? '';
		
			$formaction = "update";
			$formtitle = htmlspecialchars($name);
			$submitbuttonlabel = "Update";
		}
		else {
			$name = $desc = "";
			$formaction = "add";
			$formtitle = "New Experiment";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>
			<form method="post" action="experiment.php" enctype="multipart/form-data" class="ui form attached fluid raised segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="expid" value="<?=$expid?>">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<?=CSRFTokenField()?>

			<div class="field">
				<label>Name</label>
				<div class="field">
					<input type="text" name="experimentname" value="<?=htmlspecialchars($name)?>" maxlength="255" required <?=$readonly?>>
				</div>
			</div>

			<div class="field">
				<label>Description (Task instructions, README, how to analyze, etc)</label>
				<div class="field">
					<textarea name="experimentdesc" <?=$readonly?>><?=htmlspecialchars($desc)?></textarea>
				</div>
			</div>
			
			<div class="field">
				<label>File(s)</label>
				<div class="field">
					<? if ($type == "edit") { ?>
					<table class="ui celled selectable small very compact table">
						<thead>
							<th>File</th>
							<th>Size</th>
							<th>Create date</th>
							<th>Modify date</th>
							<? if ($canedit) { ?><th>Remove?</th><? } ?>
						</thead>
						<?
						$sqlstring = "select * from experiment_files where experiment_id = ? order by file_name asc";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $expid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($expid));
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$fileid = $row['experimentfile_id'];
							$file = $row['file_name'];
							$filesize = $row['file_size'];
							$createdate = date('M j, Y h:ia',strtotime($row['file_createdate']));
							$modifydate = date('M j, Y h:ia',strtotime($row['file_modifydate']));
							?>
							<tr>
								<td><tt style="font-size: larger"><a href="experiment.php?action=viewfile&fileid=<?=(int)$fileid?>"><?=htmlspecialchars($file)?></a></tt></td>
								<td><?=(int)$filesize?></td>
								<td style="font-size: smaller"><?=$createdate?></td>
								<td style="font-size: smaller"><?=$modifydate?></td>
								<? if ($canedit) { ?><td><input type="checkbox" name="filedeleteids[]" value="<?=(int)$fileid?>"></td><? } ?>
							</tr>
							<?
						}
						mysqli_stmt_close($stmt);
						?>
					</table>
					<br>
					<? } ?>
					<? if ($canedit) { ?>
					Add file(s) <input type="file" name="files[]" multiple>
					<span class="tiny">Max individual file size 4GB. Max filename length 255 characters</span>
					<? } ?>
				</div>
			</div>
			<div class="ui two column grid">
				<div class="column">
					<? if (($type == "edit") && ($canedit)) { ?>
					<!-- forms can't nest, so this submits the separate delete form below -->
					<button type="submit" form="deleteexperimentform" class="ui red button"><i class="trash icon"></i>Delete</button>
					<? } ?>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="experiment.php?projectid=<?=$projectid?>"><?=$canedit ? "Cancel" : "Back"?></a>
					<? if ($canedit) { ?><input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>"><? } ?>
				</div>
			</div>
			</form>
			<? if (($type == "edit") && ($canedit)) { ?>
			<form method="post" action="experiment.php" id="deleteexperimentform" onsubmit="return confirm('Are you sure you want to delete this experiment and all of its files?')">
				<input type="hidden" name="action" value="delete">
				<input type="hidden" name="expid" value="<?=$expid?>">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				<?=CSRFTokenField()?>
			</form>
			<? } ?>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayExperimentList -------------- */
	/* -------------------------------------------- */
	function DisplayExperimentList($projectid, $canedit) {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
		$projectid = (int)$projectid;
		if (!ValidID($projectid,'Project ID')) { return; }
		
		?>
		<div class="ui container">
			<? if ($canedit) { ?>
			<button class="ui primary large button" onClick="window.location.href='experiment.php?projectid=<?=$projectid?>&action=addform'; return false;"><i class="plus square outline icon"></i> Create experiment</button>
			<br><br>
			<? } ?>
			<table class="ui celled selectable grey compact table">
				<thead>
					<tr>
						<th>Name</th>
						<th>File(s)</th>
						<th>Download</th>
						<th>Version</th>
						<th>Create date</th>
						<th>Modify date</th>
						<th>Add to package</th>
					</tr>
				</thead>
				<tbody>
					<?
						$sqlstring = "select * from experiments where project_id = ? order by exp_name asc";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $projectid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($projectid));
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$expid = $row['experiment_id'];
							$name = $row['exp_name'];
							$version = $row['exp_version'];
							$createdate = date('M j, Y h:ia',strtotime($row['exp_createdate']));
							$modifydate = date('M j, Y h:ia',strtotime($row['exp_modifydate']));
							
							?>
							<tr>
								<td valign="top">
									<a href="experiment.php?action=editform&expid=<?=(int)$expid?>"><b><?=htmlspecialchars($name ?? '')?></b></a>
								</td>
								<td valign="top" align="center">
								<?
									$expid = (int)$expid;
									$sqlstringA = "select sum(file_size) total_size, count(file_name) file_count from experiment_files where experiment_id = $expid";
									$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__); /* $expid is a database integer */
									if (mysqli_num_rows($resultA) > 0) {
										$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
										$size = HumanReadableFilesize($rowA['total_size']);
										$count = $rowA['file_count'];
										
										if ($count > 0)
											echo "$count files, $size\n";
										else
											echo "-";
									}
									?>
								</td>
								<td valign="top" class="ui center aligned"><a href="experiment.php?action=download&expid=<?=$expid?>" title="Download <?=htmlspecialchars($name ?? '')?> experiment"><i class="large download icon"></i></a></td>
								<td valign="top"><?=(int)$version?></td>
								<td valign="top"><?=$createdate?></td>
								<td valign="top"><?=$modifydate?></td>
								<td valign="top" class="ui center aligned"><a href="packages.php?action=addobject&objecttype=experiment&objectids[]=<?=$expid?>"><img src="images/squirrel-icon-64.png" height="24"></img></a></td>
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
	/* ------- ViewFile --------------------------- */
	/* -------------------------------------------- */
	function ViewFile($projectid, $fileid) {

		$projectid = (int)$projectid;
		$fileid = (int)$fileid;
		if (!ValidID($projectid,'Project ID')) { return; }
		if (!ValidID($fileid,'File ID')) { return; }
		
		$sqlstring = "select * from experiment_files where experimentfile_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $fileid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($fileid));
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$expid = $row['experiment_id'];
			$fileid = $row['experimentfile_id'];
			$filename = $row['file_name'];
			$contents = base64_decode($row['file']);
			$filesize = $row['file_size'];
			$createdate = date('M j, Y h:ia',strtotime($row['file_createdate']));
			$modifydate = date('M j, Y h:ia',strtotime($row['file_modifydate']));
			?>
			
			<div class="ui text container">
				<a href="experiment.php?action=editform&expid=<?=(int)$expid?>" class="ui primary button"><b><i class="arrow alternate circle left icon"></i> Back</b></a>
				<br>
				<table class="ui very simple very compact small celled table">
					<tr>
						<td>File name</td>
						<td><h3 class="ui header"><?=htmlspecialchars($filename ?? '')?></h3></td>
					</tr>
					<tr>
						<td>File size</td>
						<td><?=(int)$filesize?> <span class="tiny">bytes</span></td>
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


	/* -------------------------------------------- */
	/* ------- DownloadExperiment ----------------- */
	/* -------------------------------------------- */
	function DownloadExperiment($experimentid) {
		$experimentid = (int)$experimentid;

		if (!ValidID($experimentid,'Experiment ID')) { return; }
		
		/* get list of files, and get the files */
		$sqlstring = "select count(*) 'num' from experiment_files where experiment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $experimentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($experimentid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if ((int)($row['num'] ?? 0) > 0) {
			
			/* create a private temp dir to assemble the zip in; removed again below */
			$tmppath = $GLOBALS['cfg']['tmpdir'] . '/' . bin2hex(random_bytes(10));
			mkdir($tmppath, 0700, true);
			
			$sqlstring = "select exp_name from experiments where experiment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $experimentid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($experimentid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($row !== null) {
				$expname = $row['exp_name'];
				$expname = preg_replace("/[^A-Za-z0-9]/", '', $expname);
			}
			else {
				$expname = "experiment";
			}
			if ($expname == "") $expname = "experiment";
			
			$zipfilename = "$tmppath/$expname.zip";
			
			$sqlstringA = "select * from experiment_files where experiment_id = ?";
			$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
			mysqli_stmt_bind_param($stmtA, 'i', $experimentid);
			$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, array($experimentid));
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$fileid = $rowA['experimentfile_id'];
				$filename = basename($rowA['file_name']); /* never write outside the temp dir, whatever name was stored */
				$contents = base64_decode($rowA['file']);
				$filesize = $rowA['file_size'];
				$createdate = date('M j, Y h:ia',strtotime($rowA['file_createdate']));
				$modifydate = date('M j, Y h:ia',strtotime($rowA['file_modifydate']));
				
				/* write file to temp dir */
				//echo "Writing to $tmppath/$filename<br><br>$contents<br><br>";
				if (file_put_contents("$tmppath/$filename", $contents) === false){
					//echo "$tmppath/$filename file not written<br>";
				}
				
				if (file_exists("$tmppath/$filename")) {
					//echo "$tmppath/$filename exists. Size " . filesize("$tmppath/$filename") . "<br>";
				}
				else {
					//echo "$tmppath/$filename does not exist.<br>";
				}
			}
			
			/* zip the experiment's files (everything written to the dir) */
			$writtenfiles = array_values(array_diff(scandir($tmppath), array('.', '..')));
			$systemstring = "cd " . escapeshellarg($tmppath) . " && zip " . escapeshellarg($zipfilename) . " -- " . implode(" ", array_map('escapeshellarg', $writtenfiles));
			shell_exec($systemstring);
			
			/* discard any buffered page output so the zip isn't corrupted */
			while (ob_get_level() > 0) ob_end_clean();
			
			/* send the .zip file to the browser */
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=\"$expname.zip\"");
			header("Content-Type: application/zip");
			header("Content-length: " . filesize($zipfilename));
			header("Content-Transfer-Encoding: binary");
			readfile($zipfilename);
			
			/* remove everything that was written, so no copy of the experiment is left in tmpdir */
			foreach ($writtenfiles as $writtenfile)
				@unlink("$tmppath/$writtenfile");
			@unlink($zipfilename);
			@rmdir($tmppath);
			exit(0);
		}
	}
	
?>


<? include("footer.php") ?>
