<?
 // ------------------------------------------------------------------------------
 // NiDB importremote.php
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
		<title>NiDB - Manage Remote Imports</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$importid = GetVariable("importid");
	$projectid = GetVariable("projectid");
	$batchid = GetVariable("batchid");
	$importname = GetVariable("importname");
	$remote_type = GetVariable("remote_type");
	$remote_url = GetVariable("remote_url");
	$remote_token = GetVariable("remote_token");
	$import_schedule = GetVariable("import_schedule");
	$import_time = GetVariable("import_time");
	$import_dayofmonth = GetVariable("import_dayofmonth");
	$import_days = GetVariable("import_days");

	/* determine action */
	switch ($action) {
		case 'viewbatchlog':
			DisplayBatchLog($batchid);
			break;
		case 'viewbatchimports':
			DisplayBatchImports($batchid);
			break;
		case 'viewimports':
			DisplayRemoteImportList($projectid);
			break;
		case 'viewbatchimportlist':
			DisplayRemoteImportBatchList($projectid);
			break;
		case 'runimport':
			RunRemoteImport($importid);
			DisplayRemoteImportList($projectid);
			break;
		case 'enable':
			SetRemoteImportEnabled($importid, 1);
			DisplayRemoteImportList($projectid);
			break;
		case 'disable':
			SetRemoteImportEnabled($importid, 0);
			DisplayRemoteImportList($projectid);
			break;
		case 'deleteimport':
			DeleteRemoteImport($importid);
			DisplayRemoteImportList($projectid);
			break;
		case 'editimportform':
			DisplayRemoteImportForm("edit", $importid);
			break;
		case 'addimportform':
			DisplayRemoteImportForm("add", "");
			break;
		case 'updateimport':
			UpdateRemoteImport($importid, $importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days);
			DisplayRemoteImportList($projectid);
			break;
		case 'addimport':
			AddRemoteImport($importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days);
			DisplayRemoteImportList($projectid);
			break;
		default:
			DisplayRemoteImportList($projectid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- RunRemoteImport -------------------- */
	/* -------------------------------------------- */
	function RunRemoteImport($importid) {
		$importid = (int)$importid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select import_name, import_schedule, remote_type from remote_imports where remoteimport_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $importid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row) {
			Error("Remote import not found");
			return;
		}

		if ($row['import_schedule'] != "ondemand") {
			Error("Only on-demand remote imports can be run manually");
			return;
		}

		$csvpath = null;
		if ($row['remote_type'] === 'csv') {
			if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
				Error("A CSV file is required to run this import");
				return;
			}
			$uploaddir = rtrim($GLOBALS['cfg']['uploaddir'], '/') . '/';
			$uniquename = uniqid('csvimport_', true) . '.csv';
			$csvpath = $uploaddir . $uniquename;
			if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $csvpath)) {
				Error("Failed to save uploaded CSV file");
				return;
			}
		}

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into remoteimport_batch (remoteimport_id, status, next_state, csv_path) values (?, 'pending', 'run', ?)");
		mysqli_stmt_bind_param($stmt, 'is', $importid, $csvpath);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Remote import " . $row['import_name'] . " queued");
	}


	/* -------------------------------------------- */
	/* ------- UpdateRemoteImport ----------------- */
	/* -------------------------------------------- */
	function UpdateRemoteImport($importid, $importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days) {
		$importid = (int)$importid;
		$projectid = (int)$projectid;
		$importname = trim($importname);
		$remote_type = trim($remote_type);
		$remote_url = trim($remote_url);
		$remote_token = trim($remote_token);
		$import_schedule = trim($import_schedule);
		$import_time = (int)$import_time;
		$import_dayofmonth = (int)$import_dayofmonth;
		$import_days = NormalizeImportDays($import_days);

		/* if the incoming token field is blank then we leave it alone, otherwise update it */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select remote_token from remote_imports where remoteimport_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $importid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$existingtoken = isset($row['remote_token']) ? $row['remote_token'] : "";
		mysqli_stmt_close($stmt);

		$remote_url_db = ($remote_url == "") ? null : $remote_url;
		if ($remote_token == "") {
			$remote_token_db = ($existingtoken === "") ? null : $existingtoken;
		}
		else {
			$remote_token_db = $remote_token;
		}

		$stmt = mysqli_prepare($GLOBALS['linki'], "update remote_imports set import_name = ?, project_id = ?, remote_type = ?, remote_url = ?, remote_token = ?, import_schedule = ?, import_time = ?, import_dayofmonth = ?, import_days = ? where remoteimport_id = ?");
		mysqli_stmt_bind_param($stmt, 'sissssissi', $importname, $projectid, $remote_type, $remote_url_db, $remote_token_db, $import_schedule, $import_time, $import_dayofmonth, $import_days, $importid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Remote import $importname updated");
	}


	/* -------------------------------------------- */
	/* ------- AddRemoteImport -------------------- */
	/* -------------------------------------------- */
	function AddRemoteImport($importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days) {
		$importname = trim($importname);
		$projectid = (int)$projectid;
		$remote_type = trim($remote_type);
		$remote_url = trim($remote_url);
		$remote_token = trim($remote_token);
		$import_schedule = trim($import_schedule);
		$import_time = (int)$import_time;
		$import_dayofmonth = (int)$import_dayofmonth;
		$import_days = NormalizeImportDays($import_days);

		$remote_url_db = ($remote_url == "") ? null : $remote_url;
		$remote_token_db = ($remote_token == "") ? null : $remote_token;

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into remote_imports (import_name, project_id, remote_type, remote_url, remote_token, import_schedule, import_time, import_dayofmonth, import_days) values (?, ?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'sissssiis', $importname, $projectid, $remote_type, $remote_url_db, $remote_token_db, $import_schedule, $import_time, $import_dayofmonth, $import_days);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Remote import $importname added");
	}


	/* -------------------------------------------- */
	/* ------- DisplayRemoteImportForm ------------ */
	/* -------------------------------------------- */
	function DisplayRemoteImportForm($type, $importid) {
		$projects = array();
		$sqlstring = "select project_id, project_name, project_costcenter from projects order by project_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projects[] = $row;
		}

		if ($type == "edit") {
			$importid = (int)$importid;
			$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, b.project_name from remote_imports a left join projects b on a.project_id = b.project_id where a.remoteimport_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $importid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) {
				Error("Remote import not found");
				return;
			}

			$importname = $row['import_name'];
			$projectid = $row['project_id'];
			$projectname = $row['project_name'];
			$remote_type = $row['remote_type'];
			$remote_url = $row['remote_url'];
			$remote_token = "";
			$import_schedule = $row['import_schedule'];
			$import_time = $row['import_time'];
			$import_dayofmonth = $row['import_dayofmonth'];
			$import_days = explode(",", $row['import_days']);
			$create_date = $row['create_date'];

			$formaction = "updateimport";
			$formtitle = "Updating remote import <b>$importname</b>";
			$submitbuttonlabel = "Update";
		}
		else {
			$importname = "";
			$projectid = GetVariable("projectid");
			$remote_type = "";
			$remote_url = "";
			$remote_token = "";
			$import_schedule = "";
			$import_time = 0;
			$import_dayofmonth = 1;
			$import_days = array("Sun");
			$create_date = "";

			$formaction = "addimport";
			$deletebutton = "";
			$formtitle = "Add new remote import";
			$submitbuttonlabel = "Add";
		}

		$remote_types = array(
			"" => "Select remote type...",
			"avicenna" => "Avicenna",
			"csv" => "CSV",
			"redcap" => "REDCap",
			"url" => "URL"
		);

		$import_schedules = array(
			"" => "Select schedule...",
			"ondemand" => "On demand",
			"hourly" => "Hourly",
			"daily" => "Daily",
			"weekly" => "Weekly",
			"monthly" => "Monthly"
		);

		$days = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
		$selectedschedule = strtolower(trim($import_schedule));
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>

			<form method="post" action="importremote.php" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<input type="hidden" name="importid" value="<?=$importid?>">
				<input type="hidden" name="projectid" value="<?=$projectid?>">

				<div class="field">
					<label>Import Name</label>
					<input type="text" name="importname" value="<?=$importname?>" maxlength="255" required autofocus="autofocus">
				</div>

				<div class="two fields">
					<div class="field">
						<label>Remote Source Type</label>
						<select name="remote_type" id="remote_type" required onchange="updateRemoteTypeFields()">
							<?
								foreach ($remote_types as $value => $label) {
									if ($remote_type == $value) { $selected = "selected"; } else { $selected = ""; }
									?>
									<option value="<?=$value?>" <?=$selected?>><?=$label?></option>
									<?
								}
							?>
						</select>
					</div>
					<div class="field" id="remote_url_group">
						<label>Remote URL</label>
						<input type="text" name="remote_url" id="remote_url" value="<?=$remote_url?>" placeholder="https://...">
					</div>
				</div>

				<div class="field" id="remote_token_group">
					<label>Remote Token</label>
					<input type="password" name="remote_token" value="<?=$remote_token?>" placeholder="Leave blank to keep current token">
				</div>

				<div class="three fields" id="import_schedule_group">
					<div class="field">
						<label>Import Schedule</label>
						<select name="import_schedule" id="import_schedule" required onchange="updateImportScheduleFields()">
							<?
								foreach ($import_schedules as $value => $label) {
									if ($import_schedule == $value) { $selected = "selected"; } else { $selected = ""; }
									?>
									<option value="<?=$value?>" <?=$selected?>><?=$label?></option>
									<?
								}
							?>
						</select>
					</div>
					<div class="field" id="import_time_group">
						<label>Import Time</label>
						<select name="import_time">
							<?
								for ($h = 0; $h < 24; $h++) {
									$label = sprintf("%02d:00", $h);
									if ((int)$import_time == $h) { $selected = "selected"; } else { $selected = ""; }
									?>
									<option value="<?=$h?>" <?=$selected?>><?=$label?></option>
									<?
								}
							?>
						</select>
					</div>
					<div class="field" id="import_dayofmonth_group">
						<label>Import Day of Month</label>
						<select name="import_dayofmonth">
							<?
								for ($d = 1; $d <= 31; $d++) {
									if ((int)$import_dayofmonth == $d) { $selected = "selected"; } else { $selected = ""; }
									?>
									<option value="<?=$d?>" <?=$selected?>><?=$d?></option>
									<?
								}
							?>
						</select>
					</div>
				</div>

				<div class="field" id="import_days_group">
					<label>Import Days</label>
					<div class="inline fields">
						<?
							foreach ($days as $day) {
								$checked = (is_array($import_days) && in_array($day, $import_days)) ? "checked" : "";
								?>
								<div class="field">
									<div class="ui checkbox">
										<input type="checkbox" name="import_days[]" value="<?=$day?>" <?=$checked?>>
										<label><?=$day?></label>
									</div>
								</div>
								<?
							}
						?>
					</div>
				</div>

				<div class="ui two column grid">
					<div class="column">
						<? if ($type == "edit") { ?>
						<a href="importremote.php?action=deleteimport&projectid=<?=$projectid?>&importid=<?=$importid?>" class="ui red button" onclick="return confirm('Are you sure you want to delete this remote import?')">Delete</a>
						<? } ?>
					</div>
					<div class="right aligned column">
						<button class="ui button" onClick="window.location.href='importremote.php'; return false;">Cancel</button>
						<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
					</div>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			function updateRemoteTypeFields() {
				var type = document.getElementById('remote_type').value;
				var isCSV = (type === 'csv');

				document.getElementById('remote_url_group').style.display      = isCSV ? 'none' : '';
				document.getElementById('remote_token_group').style.display     = isCSV ? 'none' : '';
				document.getElementById('import_schedule_group').style.display  = isCSV ? 'none' : '';
				document.getElementById('import_days_group').style.display      = isCSV ? 'none' : '';

				if (isCSV) {
					document.getElementById('import_schedule').value = 'ondemand';
				}
			}

			function updateImportScheduleFields() {
				var scheduleField = document.getElementById('import_schedule');
				if (!scheduleField) return;

				var schedule = scheduleField.value;
				var timeGroup = document.getElementById('import_time_group');
				var dayOfMonthGroup = document.getElementById('import_dayofmonth_group');
				var daysGroup = document.getElementById('import_days_group');

				if (timeGroup) timeGroup.style.display = (schedule === 'daily' || schedule === 'weekly' || schedule === 'monthly') ? '' : 'none';
				if (dayOfMonthGroup) dayOfMonthGroup.style.display = (schedule === 'monthly') ? '' : 'none';
				if (daysGroup) daysGroup.style.display = (schedule === 'weekly') ? '' : 'none';
			}

			document.addEventListener('DOMContentLoaded', function() {
				updateImportScheduleFields();
				updateRemoteTypeFields();
			});
		</script>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayRemoteImportList ------------ */
	/* -------------------------------------------- */
	function DisplayRemoteImportList($projectid) {
		$projectid = (int)$projectid;
	?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Remote Imports</h1>
				</div>
				<div class="right aligned column">
					<a href="importremote.php?action=viewbatchimportlist&projectid=<?=$projectid?>" class="ui button">View Batch Imports</a>
					<a href="importremote.php?action=addimportform&projectid=<?=$GLOBALS['projectid']?>" class="ui primary button"><i class="plus square icon"></i>New Import</a>
				</div>
			</div>
			<table class="ui very compact celled grey table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Type</th>
						<th>URL</th>
						<th>Schedule</th>
						<th>Enabled</th>
					</tr>
				</thead>
				<tbody>
					<?
						$sqlstring = "select * from remote_imports where project_id = $projectid order by import_name";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						$imports = [];
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$imports[] = $row;
						}
						foreach ($imports as $row) {
							$importid = $row['remoteimport_id'];
							$importname = $row['import_name'];
							$remote_type = $row['remote_type'];
							$remote_url = $row['remote_url'];
							$import_schedule = $row['import_schedule'];
							$import_time = $row['import_time'];
							$import_dayofmonth = $row['import_dayofmonth'];
							$import_days = $row['import_days'];
							$enabled = $row['enabled'];

							$scheduletext = FormatRemoteImportSchedule($import_schedule, $import_time, $import_dayofmonth, $import_days);
							if ($import_schedule == "ondemand") {
								if ($remote_type === 'csv') {
									$scheduletext .= " &nbsp; <a href=\"#\" class=\"ui tiny primary basic green button\" onclick=\"document.getElementById('csv_upload_$importid').click(); return false;\"><i class=\"upload icon\"></i>Upload &amp; Run</a>";
								} else {
									$scheduletext .= " &nbsp; <a href=\"importremote.php?action=runimport&projectid=$projectid&importid=$importid\" class=\"ui tiny primary basic green button\">Run now</a>";
								}
							}
							$remote_url_display = ($remote_url == "") ? "-" : $remote_url;
					?>
					<tr>
						<td><a href="importremote.php?action=editimportform&projectid=<?=$projectid?>&importid=<?=$importid?>"><?=$importname?></a></td>
						<td><?=ucfirst($remote_type)?></td>
						<td><?=$remote_url_display?></td>
						<td><?=$scheduletext?></td>
						<td>
							<?
								if ($enabled) {
									?><a href="importremote.php?action=disable&projectid=<?=$projectid?>&importid=<?=$importid?>" title="<b>Enabled.</b> Click to disable"><i class="big green toggle on icon"></i></a><?
								}
								else {
									?><a href="importremote.php?action=enable&projectid=<?=$projectid?>&importid=<?=$importid?>" title="<b>Disabled.</b> Click to enable"><i class="big grey horizontally flipped toggle on icon"></i></a><?
								}
							?>
						</td>
					</tr>
					<?
						}
					?>
				</tbody>
			</table>

			<?
				/* Hidden upload forms for CSV imports — must live outside the table */
				foreach ($imports as $row) {
					if ($row['remote_type'] === 'csv') {
						$importid = $row['remoteimport_id'];
						?>
						<form id="csvUploadForm_<?=$importid?>" method="post" action="importremote.php" enctype="multipart/form-data" style="display:none">
							<input type="hidden" name="action" value="runimport">
							<input type="hidden" name="importid" value="<?=$importid?>">
							<input type="hidden" name="projectid" value="<?=$projectid?>">
							<input type="file" name="csv_file" id="csv_upload_<?=$importid?>" accept=".csv"
								onchange="if (this.files.length) this.form.submit();">
						</form>
						<?
					}
				}
			?>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayRemoteImportBatchList ------- */
	/* -------------------------------------------- */
	function DisplayRemoteImportBatchList($projectid) {
		$projectid = (int)$projectid;
	?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Remote Import Batches</h1>
				</div>
				<div class="right aligned column">
					<a href="importremote.php?action=viewimports&projectid=<?=$projectid?>" class="ui button">View Remote Imports</a>
					<a href="importremote.php?action=addimportform&projectid=<?=$projectid?>" class="ui primary button"><i class="plus square icon"></i>New Import</a>
				</div>
			</div>
			<table class="ui very compact celled grey table">
				<thead>
					<tr>
						<th>Batch ID</th>
						<th>Import Name</th>
						<th>Source</th>
						<th>Start Date</th>
						<th>End Date</th>
						<th>Status</th>
						<th>Next State</th>
						<th>Logs</th>
						<th>Imports</th>
					</tr>
				</thead>
				<tbody>
					<?
						$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, b.import_name, b.remote_type, b.remote_url, (select count(*) from observations where remotebatch_id = a.remoteimportbatch_id) as obs_count from remoteimport_batch a left join remote_imports b on a.remoteimport_id = b.remoteimport_id where b.project_id = ? order by a.remoteimportbatch_id desc");
						mysqli_stmt_bind_param($stmt, 'i', $projectid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$batchRowID = $row['remoteimportbatch_id'];
							$parentImportRowID = $row['remoteimport_id'];
							$remoteType = $row['remote_type'];
							$remoteURL = $row['remote_url'];
							$importname = $row['import_name'];
							$startdate = $row['start_date'];
							$enddate = $row['end_date'];
							$status = $row['status'];
							$nextstate = $row['next_state'];
							$csvpath = $row['csv_path'];

							$obs_count = (int)$row['obs_count'];
							$importname_display = ($importname == "") ? "-" : $importname;
							$startdate_display = ($startdate == "") ? "-" : $startdate;
							$enddate_display = ($enddate == "") ? "-" : $enddate;
							$status_display = ($status == "") ? "-" : ucfirst($status);
							$nextstate_display = ($nextstate == "") ? "-" : ucfirst($nextstate);
							
							if ($remoteType == "csv") {
								if (file_exists($csvpath)) {
									/* get CSV details */
									$filesize = HumanReadableFilesize(filesize($csvpath));
									$source_display = "<tt>$csvpath</tt> <div class='ui small label'>$filesize</div>";
								}
								else {
									$source_display = "CSV file does not exist";
								}
							}
							else {
								$source_display = ucfirst($remoteType);
							}
					?>
					<tr>
						<td><?=$batchRowID?></td>
						<td><?=$importname_display?></td>
						<td><?=$source_display?></td>
						<td><?=$startdate_display?></td>
						<td><?=$enddate_display?></td>
						<td><?=$status_display?></td>
						<td><?=$nextstate_display?></td>
						<td><a href="importremote.php?action=viewbatchlog&batchid=<?=$batchRowID?>">View logs</a></td>
						<td>
							<? if ($obs_count > 0): ?>
								<a href="importremote.php?action=viewbatchimports&batchid=<?=$batchRowID?>&projectid=<?=$projectid?>"><?=$obs_count?> observation<?=$obs_count != 1 ? 's' : ''?></a>
							<? else: ?>
								-
							<? endif; ?>
						</td>
					</tr>
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
	/* ------- DisplayBatchLog ------------------- */
	/* -------------------------------------------- */
	function DisplayBatchLog($batchid) {
		$batchid = (int)$batchid;

		/* fetch batch + parent import info */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, b.import_name, b.remote_type, b.project_id from remoteimport_batch a left join remote_imports b on a.remoteimport_id = b.remoteimport_id where a.remoteimportbatch_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $batchid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$batch = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$batch) {
			Error("Batch not found");
			return;
		}

		$projectid = (int)$batch['project_id'];
		$importname  = $batch['import_name'] ?: '-';
		$remote_type = $batch['remote_type'];
		$status      = $batch['status'] ?: '-';
		$startdate   = $batch['start_date'] ?: '-';
		$enddate     = $batch['end_date'] ?: '-';
		$csvpath     = $batch['csv_path'];
		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Batch Log <span class="ui grey label">#<?=$batchid?></span></h1>
				</div>
				<div class="right aligned column">
					<a href="importremote.php?action=viewbatchimportlist&projectid=<?=$projectid?>" class="ui button"><i class="arrow left icon"></i>Back to Batches</a>
				</div>
			</div>

			<table class="ui very compact definition table" style="margin-bottom:1.5em">
				<tr><td>Import</td><td><?=$importname?></td></tr>
				<tr><td>Type</td><td><?=ucfirst($remote_type)?></td></tr>
				<tr><td>Status</td><td><?=ucfirst($status)?></td></tr>
				<tr><td>Start</td><td><?=$startdate?></td></tr>
				<tr><td>End</td><td><?=$enddate?></td></tr>
				<? if ($remote_type === 'csv'): ?>
				<tr>
					<td>CSV File</td>
					<td>
						<? if (!$csvpath): ?>
							<span class="ui grey text">No file associated</span>
						<? elseif (!file_exists($csvpath)): ?>
							<tt><?=$csvpath?></tt> &nbsp; <span class="ui red text"><i class="times circle icon"></i> File not found</span>
						<? else: ?>
							<tt><?=$csvpath?></tt> &nbsp; <span class="ui green text"><i class="check circle icon"></i> Exists</span> <div class="ui small label"><?=HumanReadableFilesize(filesize($csvpath))?></div>
						<? endif; ?>
					</td>
				</tr>
				<? endif; ?>
			</table>

			<?
			/* fetch log entries */
			$stmt = mysqli_prepare($GLOBALS['linki'], "select remoteimportlog_id, event, result, message, event_date from remoteimport_logs where remoteimportbatch_id = ? order by remoteimportlog_id asc");
			mysqli_stmt_bind_param($stmt, 'i', $batchid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$logs = [];
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$logs[] = $row;
			}
			mysqli_stmt_close($stmt);

			if (count($logs) === 0) {
				?>
				<div class="ui message">No log entries for this batch.</div>
				<?
			} else {
				$resultClasses = [
					'Success' => 'positive',
					'Error'   => 'negative',
					'Warning' => 'warning',
					'Neutral' => '',
				];
				?>
				<table class="ui very compact celled grey table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Event</th>
							<th>Result</th>
							<th>Message</th>
						</tr>
					</thead>
					<tbody>
						<? foreach ($logs as $log):
							$cls = isset($resultClasses[$log['result']]) ? $resultClasses[$log['result']] : '';
						?>
						<tr class="<?=$cls?>">
							<td><?=$log['event_date']?></td>
							<td><?=$log['event']?></td>
							<td><?=$log['result']?></td>
							<td><?=htmlspecialchars($log['message'])?></td>
						</tr>
						<? endforeach; ?>
					</tbody>
				</table>
				<div class="ui grey label"><?=count($logs)?> log entries</div>
				<?
			}
			?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayBatchImports --------------- */
	/* -------------------------------------------- */
	function DisplayBatchImports($batchid) {
		$batchid = (int)$batchid;

		/* fetch batch + parent import info */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select a.remoteimportbatch_id, a.status, a.start_date, a.end_date, b.import_name, b.remote_type, b.project_id from remoteimport_batch a left join remote_imports b on a.remoteimport_id = b.remoteimport_id where a.remoteimportbatch_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $batchid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$batch = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$batch) {
			Error("Batch not found");
			return;
		}

		$projectid  = (int)$batch['project_id'];
		$importname = $batch['import_name'] ?: '-';
		$status     = $batch['status'] ?: '-';
		$startdate  = $batch['start_date'] ?: '-';

		/* fetch observations */
		$stmt = mysqli_prepare($GLOBALS['linki'], "
			SELECT o.observation_id, s.uid AS subject_uid,
			       o.observation_name, o.observation_instrument, o.observation_value,
			       o.observation_startdate, o.observation_rater, o.observation_notes
			FROM observations o
			LEFT JOIN enrollment e ON o.enrollment_id = e.enrollment_id
			LEFT JOIN subjects s ON e.subject_id = s.subject_id
			WHERE o.remotebatch_id = ?
			ORDER BY o.observation_id ASC");
		mysqli_stmt_bind_param($stmt, 'i', $batchid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$rows = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = [
				'observation_id'         => (int)$row['observation_id'],
				'subject_uid'            => $row['subject_uid'],
				'observation_name'       => $row['observation_name'],
				'observation_instrument' => $row['observation_instrument'],
				'observation_value'      => $row['observation_value'],
				'observation_startdate'  => $row['observation_startdate'],
				'observation_rater'      => $row['observation_rater'],
				'observation_notes'      => $row['observation_notes'],
			];
		}
		mysqli_stmt_close($stmt);
		?>
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-grid.css">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-theme-alpine.css">

		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Imported Observations <span class="ui grey label"><?=count($rows)?></span></h1>
					<div class="ui sub header">Batch #<?=$batchid?> &mdash; <?=$importname?> &mdash; <?=ucfirst($status)?> &mdash; <?=$startdate?></div>
				</div>
				<div class="right aligned column" style="padding-top:1.5em">
					<a href="importremote.php?action=viewbatchimportlist&projectid=<?=$projectid?>" class="ui button"><i class="arrow left icon"></i>Back to Batches</a>
				</div>
			</div>

			<div style="margin-bottom:8px">
				<input type="text" id="obsFilterInput" placeholder="Search..." oninput="obsGridApi.setQuickFilter(this.value)" style="padding:5px 8px;width:250px;border:1px solid #ccc;border-radius:4px">
			</div>
			<div id="obsGrid" class="ag-theme-alpine" style="height:600px;width:100%"></div>
		</div>

		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@31/dist/ag-grid-community.min.js"></script>
		<script>
		const obsRowData = <?= json_encode($rows) ?>;

		const obsColumnDefs = [
			{ field: 'subject_uid',            headerName: 'Subject',    sortable: true, filter: true, width: 110 },
			{ field: 'observation_name',        headerName: 'Name',       sortable: true, filter: true, width: 180 },
			{ field: 'observation_instrument',  headerName: 'Instrument', sortable: true, filter: true, width: 160 },
			{ field: 'observation_value',       headerName: 'Value',      sortable: true, filter: true, width: 130 },
			{ field: 'observation_startdate',   headerName: 'Date',       sortable: true, filter: true, width: 160 },
			{ field: 'observation_rater',       headerName: 'Rater',      sortable: true, filter: true, width: 120 },
			{ field: 'observation_notes',       headerName: 'Notes',      sortable: true, filter: true, flex: 1 },
		];

		const obsGridApi = agGrid.createGrid(document.getElementById('obsGrid'), {
			columnDefs: obsColumnDefs,
			rowData: obsRowData,
			defaultColDef: { resizable: true },
			paginationPageSizeSelector: false,
		});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SetRemoteImportEnabled ------------- */
	/* -------------------------------------------- */
	function SetRemoteImportEnabled($importid, $enabled) {
		$importid = (int)$importid;
		$enabled = (int)$enabled;

		$stmt = mysqli_prepare($GLOBALS['linki'], "update remote_imports set enabled = ? where remoteimport_id = ?");
		mysqli_stmt_bind_param($stmt, 'ii', $enabled, $importid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- DeleteRemoteImport ----------------- */
	/* -------------------------------------------- */
	function DeleteRemoteImport($importid) {
		$importid = (int)$importid;

		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from remote_imports where remoteimport_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $importid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Remote import deleted");
	}


	/* -------------------------------------------- */
	/* ------- FormatRemoteImportSchedule --------- */
	/* -------------------------------------------- */
	function FormatRemoteImportSchedule($import_schedule, $import_time, $import_dayofmonth, $import_days) {
		$time = sprintf("%02d:00", (int)$import_time);
		$dayslabel = FormatRemoteImportDays($import_days);

		if ($import_schedule == "ondemand") {
			return "On demand";
		}
		elseif ($import_schedule == "hourly") {
			return "Hourly at $time";
		}
		elseif ($import_schedule == "daily") {
			return "Daily at $time";
		}
		elseif ($import_schedule == "weekly") {
			return "Weekly at $time on $dayslabel";
		}
		elseif ($import_schedule == "monthly") {
			return "Monthly at $time on day $import_dayofmonth";
		}
		else {
			return "Not scheduled";
		}
	}


	/* -------------------------------------------- */
	/* ------- NormalizeImportDays ---------------- */
	/* -------------------------------------------- */
	function NormalizeImportDays($import_days) {
		if (is_array($import_days)) {
			$days = array();
			foreach ($import_days as $day) {
				$day = trim($day);
				if ($day != "") {
					$days[] = $day;
				}
			}
			return implode(",", $days);
		}

		return trim($import_days);
	}


	/* -------------------------------------------- */
	/* ------- FormatRemoteImportDays ------------- */
	/* -------------------------------------------- */
	function FormatRemoteImportDays($import_days) {
		if (is_array($import_days)) {
			return implode(", ", $import_days);
		}

		$import_days = trim($import_days);
		if ($import_days == "") {
			return "None";
		}

		return str_replace(",", ", ", $import_days);
	}
?>


<? include("footer.php") ?>
