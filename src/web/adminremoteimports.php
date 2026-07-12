<?
 // ------------------------------------------------------------------------------
 // NiDB adminremoteimports.php
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

	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = GetVariable("id");
		$projectid = GetVariable("projectid");
		$importname = GetVariable("importname");
		$remote_type = GetVariable("remote_type");
		$remote_url = GetVariable("remote_url");
		$remote_token = GetVariable("remote_token");
		$import_schedule = GetVariable("import_schedule");
		$import_time = GetVariable("import_time");
		$import_dayofmonth = GetVariable("import_dayofmonth");
		$import_days = isset($_POST['import_days']) ? $_POST['import_days'] : GetVariable("import_days");

		/* determine action */
		switch ($action) {
			case 'editform':
				DisplayRemoteImportForm("edit", $id);
				break;
			case 'addform':
				DisplayRemoteImportForm("add", "");
				break;
			case 'update':
				UpdateRemoteImport($id, $importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days);
				DisplayRemoteImportList();
				break;
			case 'add':
				AddRemoteImport($importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days);
				DisplayRemoteImportList();
				break;
			default:
				DisplayRemoteImportList();
		}
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateRemoteImport ----------------- */
	/* -------------------------------------------- */
	function UpdateRemoteImport($id, $importname, $projectid, $remote_type, $remote_url, $remote_token, $import_schedule, $import_time, $import_dayofmonth, $import_days) {
		$id = (int)$id;
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
		$stmt = mysqli_prepare($GLOBALS['linki'], "select remote_token from remote_import_sources where remoteimportsetting_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
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

		$stmt = mysqli_prepare($GLOBALS['linki'], "update remote_import_sources set import_name = ?, project_id = ?, remote_type = ?, remote_url = ?, remote_token = ?, import_schedule = ?, import_time = ?, import_dayofmonth = ?, import_days = ? where remoteimportsetting_id = ?");
		mysqli_stmt_bind_param($stmt, 'sissssissi', $importname, $projectid, $remote_type, $remote_url_db, $remote_token_db, $import_schedule, $import_time, $import_dayofmonth, $import_days, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("title", "$importname updated");
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

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into remote_import_sources (import_name, project_id, remote_type, remote_url, remote_token, import_schedule, import_time, import_dayofmonth, import_days) values (?, ?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'sissssiis', $importname, $projectid, $remote_type, $remote_url_db, $remote_token_db, $import_schedule, $import_time, $import_dayofmonth, $import_days);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("title", "$importname added");
	}


	/* -------------------------------------------- */
	/* ------- DisplayRemoteImportForm ------------ */
	/* -------------------------------------------- */
	function DisplayRemoteImportForm($type, $id) {
		$projects = array();
		$sqlstring = "select project_id, project_name, project_costcenter from projects order by project_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projects[] = $row;
		}

		if ($type == "edit") {
			$id = (int)$id;
			$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, b.project_name from remote_import_sources a left join projects b on a.project_id = b.project_id where a.remoteimportsetting_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $id);
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

			$formaction = "update";
			$formtitle = "Updating $importname";
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

			$formaction = "add";
			$formtitle = "Add new remote import";
			$submitbuttonlabel = "Add";
		}

		$remote_types = array(
			"" => "Select remote type...",
			"avicenna" => "Avicenna",
			"redcap" => "REDCap",
			"url" => "URL"
		);

		$import_schedules = array(
			"" => "Select schedule...",
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

			<form method="post" action="adminremoteimports.php" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<input type="hidden" name="id" value="<?=$id?>">

				<div class="field">
					<label>Import Name</label>
					<input type="text" name="importname" value="<?=$importname?>" maxlength="255" required autofocus="autofocus">
				</div>

				<div class="field">
					<label>Project</label>
					<select name="projectid" required>
						<option value="">Select project...</option>
						<?
							foreach ($projects as $projectrow) {
								$pid = $projectrow['project_id'];
								$pname = $projectrow['project_name'];
								$pcost = $projectrow['project_costcenter'];
								if ($projectid == $pid) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$pid?>" <?=$selected?>><?=$pname?> (<?=$pcost?>)</option>
								<?
							}
						?>
					</select>
				</div>

				<div class="two fields">
					<div class="field">
						<label>Remote Source Type</label>
						<select name="remote_type" required>
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
					<div class="field">
						<label>Remote URL</label>
						<input type="text" name="remote_url" value="<?=$remote_url?>" placeholder="https://...">
					</div>
				</div>

				<div class="field">
					<label>Remote Token</label>
					<input type="password" name="remote_token" value="<?=$remote_token?>" placeholder="Leave blank to keep current token">
				</div>

				<div class="three fields">
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
					</div>
					<div class="right aligned column">
						<button class="ui button" onClick="window.location.href='adminremoteimports.php'; return false;">Cancel</button>
						<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
					</div>
				</div>
			</form>
		</div>
		<script type="text/javascript">
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
			});
		</script>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayRemoteImportList ------------ */
	/* -------------------------------------------- */
	function DisplayRemoteImportList() {
	?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Remote Import Sources</h1>
				</div>
				<div class="right aligned column">
					<a href="adminremoteimports.php?action=addform" class="ui primary button"><i class="plus square icon"></i>New Import Source</a>
				</div>
			</div>
			<table class="ui very compact celled grey table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Project</th>
						<th>Type</th>
						<th>Schedule</th>
						<th>URL</th>
						<th>Created</th>
					</tr>
				</thead>
				<tbody>
					<?
						$sqlstring = "select a.*, b.project_name from remote_import_sources a left join projects b on a.project_id = b.project_id order by a.import_name";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$id = $row['remoteimportsetting_id'];
							$importname = $row['import_name'];
							$projectname = $row['project_name'];
							$remote_type = $row['remote_type'];
							$remote_url = $row['remote_url'];
							$import_schedule = $row['import_schedule'];
							$import_time = $row['import_time'];
							$import_dayofmonth = $row['import_dayofmonth'];
							$import_days = $row['import_days'];
							$create_date = $row['create_date'];

							$scheduletext = FormatRemoteImportSchedule($import_schedule, $import_time, $import_dayofmonth, $import_days);
							$remote_url_display = ($remote_url == "") ? "-" : $remote_url;
					?>
					<tr>
						<td><a href="adminremoteimports.php?action=editform&id=<?=$id?>"><?=$importname?></a></td>
						<td><?=$projectname?></td>
						<td><?=ucfirst($remote_type)?></td>
						<td><?=$scheduletext?></td>
						<td><?=$remote_url_display?></td>
						<td class="tiny"><?=$create_date?></td>
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
	/* ------- FormatRemoteImportSchedule --------- */
	/* -------------------------------------------- */
	function FormatRemoteImportSchedule($import_schedule, $import_time, $import_dayofmonth, $import_days) {
		$time = sprintf("%02d:00", (int)$import_time);
		$dayslabel = FormatRemoteImportDays($import_days);

		if ($import_schedule == "hourly") {
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
