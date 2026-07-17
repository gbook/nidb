<?
 // ------------------------------------------------------------------------------
 // NiDB nda.php
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

	require "functions.php";
	require "includes_php.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = (int)GetVariable("projectid");
	$exportid = (int)GetVariable("exportid");
	$ndaprojectnumber = (int)GetVariable("ndaprojectnumber");
	$ndasubmissionid = (int)GetVariable("ndasubmissionid");
	$csvfile = GetVariable("csvfile");
	$nda_collectionid = (int)GetVariable("nda_collectionid");
	$expected_data = GetVariable("expected_data");
	$submission_dates = GetVariable("submission_dates");
	$modalities = GetVariable("modalities");
	$protocolnames = GetVariable("protocolname");
	$experimentids = GetVariable("experimentid");

	/* the csv download must be sent before any HTML is output */
	if ($action == "downloadcsv") {
		DownloadCSVFile($projectid, $exportid);
		exit;
	}

	/* the series detail is fetched by AJAX and returns just an HTML fragment (no menu/header/footer) */
	if ($action == "seriesdetail") {
		DisplaySeriesDetail($projectid, $exportid);
		exit;
	}

	/* in-place edit of the NDA project number / submission id - saves and returns a short status string */
	if ($action == "updatendainfoajax") {
		$ok = UpdateNDASubmission($projectid, $exportid, $ndaprojectnumber, $ndasubmissionid, $csvfile);
		echo $ok ? "success" : "error";
		exit;
	}
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - NDA</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "includes_html.php";
	require "menu.php";

	/* ----- route the action ----- */
	switch ($action) {
		case 'updatendainfo':
			UpdateNDASubmission($projectid, $exportid, $ndaprojectnumber, $ndasubmissionid, $csvfile);
			DisplayNDA($projectid);
			break;
		case 'updatendamapping':
			UpdateNDAMapping($projectid, $modalities, $protocolnames, $experimentids);
			EditNDAMapping($projectid);
			break;
		case 'editndamapping':
			EditNDAMapping($projectid);
			break;
		case 'updatendaproject':
			UpdateNDAProject($projectid, $nda_collectionid, $expected_data, $submission_dates);
			DisplayNDA($projectid);
			break;
		case 'editndaproject':
			EditNDAProjectForm($projectid);
			break;
		default:
			DisplayNDA($projectid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayNDA ------------------------- */
	/* -------------------------------------------- */
	function DisplayNDA($projectid) {
		$projectid = (int)$projectid;

		if ($projectid < 1) {
			Error("No project specified");
			return;
		}
		?>
		<div class="ui container" style="margin-top: 20px">
		<?
			DisplayNDAProjectInfo($projectid);
			?>
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">NDA Submissions</h2>
				</div>
				<div class="right aligned column">
					<a href="nda.php?action=editndamapping&projectid=<?=$projectid?>" class="ui button"><i class="tasks icon"></i> Edit experiment mapping</a>
				</div>
			</div>
			<br>
		<?

		/* find all NDA exports for this project */
		$exportids = GetNDAExportIDs($projectid);
		if (count($exportids) < 1) {
			echo "No previous NDA exports";
			?></div><?
			return;
		}

		$placeholders = implode(',', array_fill(0, count($exportids), '?'));
		$types = str_repeat('i', count($exportids));
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from exports where export_id in ($placeholders) order by submitdate desc");
		mysqli_stmt_bind_param($stmt, $types, ...$exportids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		?>
		<script type="text/javascript">
			/* auto-save the NDA collection / submission id for a row, and keep the Submitted indicator in sync */
			function ndaSaveInfo(indx, projectid, exportid, hascsv) {
				var proj = document.getElementById('ndaproj' + indx).value;
				var sub = document.getElementById('ndasub' + indx).value;
				var status = document.getElementById('ndastatus' + indx);
				if (status) status.innerHTML = '<i class="grey spinner loading icon"></i>';

				var body = new URLSearchParams();
				body.append('action', 'updatendainfoajax');
				body.append('projectid', projectid);
				body.append('exportid', exportid);
				body.append('ndaprojectnumber', proj);
				body.append('ndasubmissionid', sub);

				fetch('nda.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
					.then(function(r) { return r.text(); })
					.then(function(t) {
						var ok = (t.indexOf('success') !== -1);
						if (status) {
							status.innerHTML = ok ? '<i class="green check icon"></i> Saved' : '<i class="red exclamation icon"></i> Error';
							if (ok) setTimeout(function() { status.innerHTML = ''; }, 2000);
						}
						/* the Submitted indicator needs both a (non-zero) submission id and a CSV file */
						var cell = document.getElementById('ndasubmitted' + indx);
						if (cell) {
							var hasSub = (sub.trim() !== '' && sub.trim() !== '0');
							cell.innerHTML = (hasSub && hascsv) ? '<i class="green check circle icon" title="Submitted to NDA (has submission ID and CSV file)"></i>' : '';
						}
					})
					.catch(function() { if (status) status.innerHTML = '<i class="red exclamation icon"></i> Error'; });
			}
		</script>
		<table class="ui celled compact table">
			<thead>
				<tr>
					<th colspan="3" class="center aligned">Local</th>
					<th colspan="4" class="center aligned">NDA</th>
				</tr>
				<tr>
					<th></th>
					<th>Export date</th>
					<th>Username</th>
					<th>NDA Collection</th>
					<th>NDA Submission ID</th>
					<th>CSV File</th>
					<th>Submitted</th>
				</tr>
			</thead>
			<tbody>
		<?
		$indx = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$exportid = $row['export_id'];
			$submitdate = $row['submitdate'];
			$username = $row['username'];

			$indx++;
			$b_name = "showhide" . $indx;
			$t_name = "details" . $indx;

			/* get the NDA submission info previously saved for this export */
			$stmt2 = mysqli_prepare($GLOBALS['linki'], "select ndaprojectnum, ndasubmission_id, csv_file from project_nda_uploads where project_id = ? and export_id = ?");
			mysqli_stmt_bind_param($stmt2, 'ii', $projectid, $exportid);
			$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__);
			$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt2);
			$ndaprojectnumber = $row2['ndaprojectnum'] ?? "";
			$ndasubmissionid = $row2['ndasubmission_id'] ?? "";
			$hascsv = isset($row2['csv_file']);
			/* a submission is "complete" when it has both an NDA submission ID and a CSV file */
			$issubmitted = (!empty($ndasubmissionid) && $hascsv);
			?>
			<tr>
				<td class="collapsing">
					<button type="button" class="circular ui icon button" id="<?=$b_name?>" title="Show/hide series"><i class="angle down icon"></i></button>
				</td>
				<td class="collapsing">
					<form id="<?=$b_name?>form" class="ui form" action="nda.php" enctype="multipart/form-data" method="post">
						<input type="hidden" name="action" value="updatendainfo">
						<input type="hidden" name="projectid" value="<?=$projectid?>">
						<input type="hidden" name="exportid" value="<?=$exportid?>">
					</form>
					<?=date("D M j, Y h:ia", strtotime($submitdate))?>
				</td>
				<td class="collapsing"><?=$username?></td>
				<td>
					<div class="ui fluid input">
						<input form="<?=$b_name?>form" id="ndaproj<?=$indx?>" type="text" name="ndaprojectnumber" value="<?=htmlspecialchars($ndaprojectnumber)?>" onchange="ndaSaveInfo(<?=$indx?>, <?=$projectid?>, <?=$exportid?>, <?=$hascsv ? 'true' : 'false'?>)">
					</div>
				</td>
				<td>
					<div class="ui fluid input">
						<input form="<?=$b_name?>form" id="ndasub<?=$indx?>" type="text" name="ndasubmissionid" value="<?=htmlspecialchars($ndasubmissionid)?>" onchange="ndaSaveInfo(<?=$indx?>, <?=$projectid?>, <?=$exportid?>, <?=$hascsv ? 'true' : 'false'?>)">
					</div>
					<span id="ndastatus<?=$indx?>" class="ui text" style="font-size: 0.85em"></span>
				</td>
				<td>
					<input form="<?=$b_name?>form" type="file" name="csvfile" id="csvfile<?=$indx?>" accept=".csv" style="display: none" onchange="this.form.submit()">
					<label for="csvfile<?=$indx?>" class="ui small button"><i class="upload icon"></i> <?=$hascsv ? "Overwrite" : "Upload"?></label>
					<? if ($hascsv): ?>
						<a href="nda.php?action=downloadcsv&projectid=<?=$projectid?>&exportid=<?=$exportid?>" title="View existing CSV file"><i class="file alternate icon"></i> View csv</a>
					<? endif; ?>
				</td>
				<td class="center aligned" id="ndasubmitted<?=$indx?>">
					<? if ($issubmitted): ?>
						<i class="green check circle icon" title="Submitted to NDA (has submission ID and CSV file)"></i>
					<? endif; ?>
				</td>
			</tr>
			<tr id="<?=$t_name?>" hidden>
				<td colspan="7" style="padding: 0" id="<?=$t_name?>cell">
					<!-- series detail is loaded on demand when the row is first expanded -->
				</td>
			</tr>
			<script>
				/* toggle the per-export series detail row, loading its series on first expand */
				(function() {
					const btn = document.getElementById('<?=$b_name?>');
					const detail = document.getElementById('<?=$t_name?>');
					const cell = document.getElementById('<?=$t_name?>cell');
					let loaded = false;
					btn.addEventListener('click', function() {
						if (!loaded) {
							loaded = true;
							cell.innerHTML = '<div class="ui active inline loader" style="margin: 15px"></div>';
							fetch('nda.php?action=seriesdetail&projectid=<?=$projectid?>&exportid=<?=$exportid?>')
								.then(r => r.text())
								.then(html => { cell.innerHTML = html; })
								.catch(() => { cell.innerHTML = '<div style="margin: 15px; color: #900">Failed to load series</div>'; loaded = false; });
						}
						detail.hidden = !detail.hidden;
					});
				})();
			</script>
			<?
		}
		mysqli_stmt_close($stmt);
		?>
			</tbody>
		</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySeriesDetail ---------------- */
	/* -------------------------------------------- */
	/* Return the series table (as an HTML fragment) for a single export - called by AJAX when a row is expanded */
	function DisplaySeriesDetail($projectid, $exportid) {
		$projectid = (int)$projectid;
		$exportid = (int)$exportid;
		?>
		<table class="ui celled very compact table" style="margin: 0; border: none">
			<thead>
				<tr>
					<th align="left">Subject ID</th>
					<th align="left">Study ID</th>
					<th align="left">Series</th>
					<th align="left">Size</th>
					<th align="left">Status</th>
				</tr>
			</thead>
			<tbody>
		<?
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from exportseries where export_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modality = strtolower($row['modality']);
			$seriesid = (int)$row['series_id'];
			$status = $row['status'];

			$uid = $studynum = $seriesnum = $seriesdesc = "";
			$seriessize = 0;
			/* the modality determines the series table name, so it can't be bound - the scalar ids can */
			if (IsNiDBModality($modality)) {
				$sqlstring = "select a.*, b.*, d.project_name, e.uid, e.subject_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.$modality" . "series_id = ? and d.project_id = ? order by uid, study_num, series_num";
				$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt2, 'ii', $seriesid, $projectid);
				$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__);
				$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt2);
				/* the study/series may not resolve for this project (e.g. orphaned series) - guard the null row */
				if ($row2) {
					$seriesdesc = ($modality == "mr") ? ($row2['series_desc'] ?? "") : ($row2['series_protocol'] ?? "");
					$uid = $row2['uid'] ?? "";
					$studynum = $row2['study_num'] ?? "";
					$seriesnum = $row2['series_num'] ?? "";
					$seriessize = $row2['series_size'] ?? 0;
				}
			}

			switch ($status) {
				case 'processing': $class = "blue"; break;
				case 'complete': $class = "green"; break;
				case 'error': $class = "red"; break;
				default: $class = "";
			}
			?>
			<tr>
				<td><?=htmlspecialchars($uid)?></td>
				<td><?=htmlspecialchars("$uid$studynum")?></td>
				<td><?=htmlspecialchars($seriesnum)?> - <?=htmlspecialchars($seriesdesc)?></td>
				<td class="right aligned"><?=number_format((float)$seriessize)?></td>
				<td class="<?=$class?>"><?=ucfirst(htmlspecialchars($status))?></td>
			</tr>
			<?
		}
		mysqli_stmt_close($stmt);
		?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayNDAProjectInfo -------------- */
	/* -------------------------------------------- */
	/* Show the NDA project information (collection id, expected data, submission dates) with an edit button */
	function DisplayNDAProjectInfo($projectid) {
		$projectid = (int)$projectid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select nda_collectionid, expected_data, submission_dates from nda_project where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		?>
		<div class="ui segment">
			<div class="ui two column grid">
				<div class="column">
					<h3 class="ui header">NDA Project Information</h3>
				</div>
				<div class="right aligned column">
					<a href="nda.php?action=editndaproject&projectid=<?=$projectid?>" class="ui small button"><i class="edit icon"></i> Edit</a>
				</div>
			</div>
		<?
		if (!$row) {
			?>
			<p><i>No NDA project information has been entered for this project.</i></p>
			<?
		}
		else {
			?>
			<table class="ui very basic compact table" style="width: 100%; table-layout: fixed">
				<tbody>
					<tr>
						<td style="width: 200px"><b>NDA Collection ID</b></td>
						<td style="overflow-wrap: anywhere"><?=htmlspecialchars($row['nda_collectionid'] ?? "")?></td>
					</tr>
					<tr>
						<td><b>Expected data</b></td>
						<td style="overflow-wrap: anywhere"><?=nl2br(htmlspecialchars($row['expected_data'] ?? ""))?></td>
					</tr>
					<tr>
						<td><b>Submission dates</b></td>
						<td style="overflow-wrap: anywhere"><?=nl2br(htmlspecialchars($row['submission_dates'] ?? ""))?></td>
					</tr>
				</tbody>
			</table>
			<?
		}
		?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- EditNDAProjectForm ----------------- */
	/* -------------------------------------------- */
	function EditNDAProjectForm($projectid) {
		$projectid = (int)$projectid;

		if ($projectid < 1) {
			Error("No project specified");
			return;
		}

		$stmt = mysqli_prepare($GLOBALS['linki'], "select nda_collectionid, expected_data, submission_dates from nda_project where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		$nda_collectionid = $row['nda_collectionid'] ?? "";
		$expected_data = $row['expected_data'] ?? "";
		$submission_dates = $row['submission_dates'] ?? "";
		?>
		<div class="ui container" style="margin-top: 20px">
			<h2 class="ui header">Edit NDA Project Information</h2>
			<form class="ui form" action="nda.php" method="post">
				<input type="hidden" name="action" value="updatendaproject">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				<div class="field">
					<label>NDA Collection ID</label>
					<input type="number" name="nda_collectionid" value="<?=htmlspecialchars($nda_collectionid)?>">
				</div>
				<div class="field">
					<label>Expected data</label>
					<textarea name="expected_data" rows="4"><?=htmlspecialchars($expected_data)?></textarea>
				</div>
				<div class="field">
					<label>Submission dates</label>
					<textarea name="submission_dates" rows="4"><?=htmlspecialchars($submission_dates)?></textarea>
				</div>
				<button class="ui button" type="button" onclick="window.location.href='nda.php?projectid=<?=$projectid?>'">Cancel</button>
				<button class="ui primary button" type="submit">Update</button>
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateNDAProject ------------------- */
	/* -------------------------------------------- */
	function UpdateNDAProject($projectid, $nda_collectionid, $expected_data, $submission_dates) {
		$projectid = (int)$projectid;
		$nda_collectionid = (int)$nda_collectionid;

		if ($projectid < 1) {
			Error("No project specified");
			return;
		}

		/* one row per project - update if it already exists, otherwise insert */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select ndaproject_id from nda_project where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);

		if ($exists) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update nda_project set nda_collectionid = ?, expected_data = ?, submission_dates = ? where project_id = ?");
			mysqli_stmt_bind_param($stmt, 'issi', $nda_collectionid, $expected_data, $submission_dates, $projectid);
		}
		else {
			$stmt = mysqli_prepare($GLOBALS['linki'], "insert into nda_project (project_id, nda_collectionid, expected_data, submission_dates) values (?, ?, ?, ?)");
			mysqli_stmt_bind_param($stmt, 'iiss', $projectid, $nda_collectionid, $expected_data, $submission_dates);
		}
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- GetNDAExportIDs -------------------- */
	/* -------------------------------------------- */
	/* Return the list of export_ids for this project that were exported to NDA and contain at least one series */
	function GetNDAExportIDs($projectid) {
		$projectid = (int)$projectid;
		$exportids = [];

		if ($projectid < 1)
			return $exportids;

		/* enumerate the NiDB modalities that actually have a {modality}_series table. The modality
		   determines the series table name (which can't be bound), so validate it against the DB and
		   restrict it to alphanumerics before interpolating it into the query below. */
		$modalities = [];
		$result = MySQLiQuery("select mod_code from modalities order by mod_code", __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mod = strtolower(trim($row['mod_code']));
			if (($mod == "") || !ctype_alnum($mod))
				continue;
			/* NOTE: "SHOW TABLES LIKE ?" cannot be prepared on older MariaDB/MySQL versions
			   (fails in the PHP 7 production environment). Use information_schema instead, which
			   is preparable everywhere. Exact table_name match is fine since we know the name. */
			$chkStmt = mysqli_prepare($GLOBALS['linki'], "select table_name from information_schema.tables where table_schema = database() and table_name = ?");
			$tableName = $mod . "_series";
			mysqli_stmt_bind_param($chkStmt, 's', $tableName);
			$chk = MySQLiBoundQuery($chkStmt, __FILE__, __LINE__);
			if (mysqli_num_rows($chk) > 0)
				$modalities[] = $mod;
			mysqli_stmt_close($chkStmt);
		}

		if (count($modalities) < 1)
			return $exportids;

		/* build one UNION query that finds - directly in SQL - the ndar export_ids that contain at
		   least one series belonging to this project, joined per modality through its series table. */
		$selects = [];
		foreach ($modalities as $mod) {
			$selects[] = "select es.export_id"
				. " from exportseries es"
				. " inner join $mod" . "_series ms on es.series_id = ms.$mod" . "series_id"
				. " inner join studies st on ms.study_id = st.study_id"
				. " inner join enrollment en on st.enrollment_id = en.enrollment_id"
				. " inner join exports ex on es.export_id = ex.export_id"
				. " where es.modality = '$mod' and en.project_id = ? and ex.destinationtype = 'ndar'";
		}
		$sqlstring = "select distinct export_id from (" . implode(" union all ", $selects) . ") t order by export_id";

		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		/* one project_id placeholder per modality sub-select */
		$types = str_repeat('i', count($modalities));
		$params = array_fill(0, count($modalities), $projectid);
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$exportids[] = (int)$row['export_id'];
		}
		mysqli_stmt_close($stmt);

		return $exportids;
	}


	/* -------------------------------------------- */
	/* ------- UpdateNDASubmission ---------------- */
	/* -------------------------------------------- */
	function UpdateNDASubmission($projectid, $exportid, $ndaprojectnumber, $ndasubmissionid, $csvfile) {
		$projectid = (int)$projectid;
		$exportid = (int)$exportid;
		$ndaprojectnumber = (int)$ndaprojectnumber;
		$ndasubmissionid = (int)$ndasubmissionid;

		if ($projectid < 1) {
			Error("Invalid or blank Project ID [$projectid]");
			return false;
		}

		$hasupload = isset($_FILES['csvfile']) && ($_FILES['csvfile']['error'] === UPLOAD_ERR_OK);
		$filecontent = $hasupload ? base64_encode(file_get_contents($_FILES['csvfile']['tmp_name'])) : "";

		/* does a row already exist for this project/export? */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from project_nda_uploads where project_id = ? and export_id = ?");
		mysqli_stmt_bind_param($stmt, 'ii', $projectid, $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);

		if ($exists) {
			if ($hasupload) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "update project_nda_uploads set ndaprojectnum = ?, ndasubmission_id = ?, csv_file = NULLIF(?,'') where project_id = ? and export_id = ?");
				mysqli_stmt_bind_param($stmt, 'iisii', $ndaprojectnumber, $ndasubmissionid, $filecontent, $projectid, $exportid);
			}
			else {
				$stmt = mysqli_prepare($GLOBALS['linki'], "update project_nda_uploads set ndaprojectnum = ?, ndasubmission_id = ? where project_id = ? and export_id = ?");
				mysqli_stmt_bind_param($stmt, 'iiii', $ndaprojectnumber, $ndasubmissionid, $projectid, $exportid);
			}
		}
		else {
			if ($hasupload) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "insert into project_nda_uploads (project_id, export_id, csv_file, ndaprojectnum, ndasubmission_id) values (?, ?, NULLIF(?,''), ?, ?)");
				mysqli_stmt_bind_param($stmt, 'iisii', $projectid, $exportid, $filecontent, $ndaprojectnumber, $ndasubmissionid);
			}
			else {
				$stmt = mysqli_prepare($GLOBALS['linki'], "insert into project_nda_uploads (project_id, export_id, ndaprojectnum, ndasubmission_id) values (?, ?, ?, ?)");
				mysqli_stmt_bind_param($stmt, 'iiii', $projectid, $exportid, $ndaprojectnumber, $ndasubmissionid);
			}
		}
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		return true;
	}


	/* -------------------------------------------- */
	/* ------- DownloadCSVFile -------------------- */
	/* -------------------------------------------- */
	function DownloadCSVFile($projectid, $exportid) {
		$projectid = (int)$projectid;
		$exportid = (int)$exportid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select csv_file from project_nda_uploads where project_id = ? and export_id = ?");
		mysqli_stmt_bind_param($stmt, 'ii', $projectid, $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$numrows = mysqli_num_rows($result);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (($numrows == 0) || is_null($row['csv_file'])) {
			echo "CSV File does not exist";
			return;
		}

		$csvcontents = base64_decode($row['csv_file']);

		/* build a filename from the project name, requesting user, and submit date */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select project_name from projects where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$projectname = $row['project_name'];

		$stmt = mysqli_prepare($GLOBALS['linki'], "select username, submitdate from exports where export_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		$filename = preg_replace('/[^\w]/', '', $projectname . $row['username'] . $row['submitdate'] . $exportid);

		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$filename.csv");
		header("Content-Type: text/csv");
		header("Content-length: " . strlen($csvcontents));
		header("Content-Transfer-Encoding: text");
		echo $csvcontents;
	}


	/* -------------------------------------------- */
	/* ------- EditNDAMapping --------------------- */
	/* -------------------------------------------- */
	function EditNDAMapping($projectid) {
		$projectid = (int)$projectid;

		if ($projectid < 1) {
			echo "Project not specified";
			return;
		}

		/* get all studies, and all series, associated with this project */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = (int)$row['study_id'];
			$modality = strtolower($row['study_modality']);

			/* the modality determines the series table name, so it can't be bound - the scalar study id can */
			if (IsNiDBModality($modality) && ($studyid > 0)) {
				$stmtA = mysqli_prepare($GLOBALS['linki'], "select * from $modality" . "_series where study_id = ? order by series_desc");
				mysqli_stmt_bind_param($stmtA, 'i', $studyid);
				$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					if ($rowA['series_desc'] != "") {
						$seriesdesc = $rowA['series_desc'];
					}
					elseif ($rowA['series_protocol'] != "") {
						$seriesdesc = $rowA['series_protocol'];
					}
					if ($seriesdesc != "") {
						$seriesdescs[$modality][$seriesdesc]++;
					}
				}
				mysqli_stmt_close($stmtA);
			}
		}
		mysqli_stmt_close($stmt);

		/* get list of NDA experimentid mappings for this project */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from nda_mapping where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mapping[$row['modality']][$row['protocolname']] = $row['experiment_id'];
		}
		mysqli_stmt_close($stmt);
		?>
		<br><br>
		<div class="ui text container grid">
		<form action="nda.php" method="post">
		<input type="hidden" name="action" value="updatendamapping">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<b>NDA mapping</b>
		<br>
		This mapping is used in exporting of NDA format
		<br><br>
		<table class="ui small celled selectable grey compact table">
			<thead>
				<th>Modality</th>
				<th>Protocol name</th>
				<th>
					NDA experiment_id (integer)
				</th>
			</thead>
		<?
		//PrintVariable($seriesdescs);
		//PrintVariable($mapping);
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			array_multisort(array_keys($serieslist), SORT_NATURAL| SORT_FLAG_CASE, $serieslist);
			foreach ($serieslist as $series => $count) {

				$experiment_id = "";
				$experiment_id = $mapping[$modality][$series];
				?>
				<tr>
					<td><?=strtoupper($modality)?></td>
					<td><tt><?=$series?></tt></td>
					<td>
						<input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>"><input type="hidden" name="protocolname[<?=$i?>]" value="<?=$series?>">
						<div class="ui input">
							<input type="text" name="experimentid[<?=$i?>]" value="<?=$experiment_id?>">
						</div>
					</td>
				</tr>
				<?
				$i++;
			}
		}
		?>
			<tr>
				<td colspan="3">
				<div class="column" align="right">
					<button class="ui button" onClick="window.location.href='nda.php?projectid=<?=$projectid?>'; return false;">Cancel</button>
					<input class="ui primary button" type="submit" id="submit" value="Update">
				</div>
				</td>
			</tr>
		</table>

		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateNDAMapping ------------------- */
	/* -------------------------------------------- */
	function UpdateNDAMapping($projectid, $modalities, $protocolnames, $experimentids) {
		$projectid = trim(strtolower($projectid));

		if (isInteger($projectid) || $projectid == "" || $projectid == "null") { }
		else {
			Error("Invalid project ID [$projectid]");
			return;
		}

		/* an empty/"null" project id means this is the global mapping, stored as project_id NULL */
		$pid = ($projectid == "" || $projectid == "null") ? null : (int)$projectid;

		/* the form submits the project's complete mapping set, so clear the existing rows first;
		   nda_mapping has no unique key, so without this a re-save would append duplicate rows */
		if ($pid === null) {
			$del = mysqli_prepare($GLOBALS['linki'], "delete from nda_mapping where project_id is null");
		}
		else {
			$del = mysqli_prepare($GLOBALS['linki'], "delete from nda_mapping where project_id = ?");
			mysqli_stmt_bind_param($del, 'i', $pid);
		}
		MySQLiBoundQuery($del, __FILE__, __LINE__);
		mysqli_stmt_close($del);

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert ignore into nda_mapping (project_id, protocolname, modality, experiment_id) values (?, ?, ?, ?)");
		foreach ($modalities as $i => $modality) {
			$modality = strtolower($modality);
			$protocolname = $protocolnames[$i];
			$experimentid = $experimentids[$i];
			if (($modality != "") && ($protocolname != "") && ($experimentid != "") && (is_numeric($experimentid)) && ($experimentid > 0)) {
				$experimentid = (int)$experimentid;
				mysqli_stmt_bind_param($stmt, 'issi', $pid, $protocolname, $modality, $experimentid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			}
		}
		mysqli_stmt_close($stmt);
	}

?>

<? include("footer.php") ?>
