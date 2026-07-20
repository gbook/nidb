<?
 // ------------------------------------------------------------------------------
 // NiDB checklist.php
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
		<title>NiDB - Checklist</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	$action    = GetVariable("action");
	$projectid = (int)GetVariable("projectid");

	/* subject enrollment-status filter, applied across all views (default: all) */
	$enrollstatus = GetVariable("enrollstatus");
	if (!in_array($enrollstatus, ['all', 'enrolled', 'consented', 'completed', 'excluded'], true)) $enrollstatus = 'all';

	/* the data types displayed on this page. Each drives a summary segment and (later) a detail view */
	$checklistTypes = [
		'imaging'               => ['label' => 'Imaging',               'icon' => 'photo icon',          'color' => 'blue',   'hex' => '#2185d0', 'desc' => 'MR, EEG, ET, and other imaging studies'],
		'observations'          => ['label' => 'Observations',          'icon' => 'clipboard list icon', 'color' => 'teal',   'hex' => '#00b5ad', 'desc' => 'Assessment and instrument observations'],
		'interventions'         => ['label' => 'Interventions',         'icon' => 'pills icon',          'color' => 'violet', 'hex' => '#6435c9', 'desc' => 'Drugs, dosing, and other interventions'],
		'observationimages'     => ['label' => 'Observation images',    'icon' => 'image outline icon',  'color' => 'orange', 'hex' => '#f2711c', 'desc' => 'Observations with an associated image/file'],
		'observationtimeseries' => ['label' => 'Observation timeseries','icon' => 'chartline icon',     'color' => 'green',  'hex' => '#21ba45', 'desc' => 'Observations with continuous timeseries data'],
	];

	switch ($action) {
		case 'observationimages':
			DisplayObservationImages($projectid);
			break;
		case 'observationtimeseries':
			DisplayObservationTimeseries($projectid);
			break;
		case 'imaging':
			DisplayImaging($projectid);
			break;
		case 'editchecklist':
			DisplayEditImagingChecklist($projectid);
			break;
		case 'editchecklistupdate':
			UpdateImagingChecklist($projectid, GetVariable("itemid"), GetVariable("itemname"), GetVariable("itemtype"), GetVariable("modality"), GetVariable("mappedname"), GetVariable("expectedcount"), GetVariable("deleteitem"), GetVariable("instrumentid"));
			DisplayEditImagingChecklist($projectid);
			break;
		case 'observations':
			DisplayObservations($projectid);
			break;
		case 'interventions':
			DisplayDetail($projectid, $action);
			break;
		default:
			DisplaySummary($projectid);
	}


	/* -------------------------------------------- */
	/* ------- GetProjectName --------------------- */
	/* -------------------------------------------- */
	function GetProjectName($projectid) {
		$sqlstring = "select project_name from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return $row ? $row['project_name'] : '';
	}


	/* -------------------------------------------- */
	/* ------- EnrollMarkClass -------------------- */
	/* -------------------------------------------- */
	/* Fomantic "marked" cell class for an enrollment status: green for completed, grey for
	   excluded, nothing for all other statuses. Applied to the right edge of the UID cell. */
	function EnrollMarkClass($status) {
		if ($status === 'completed') return 'green marked right';
		if ($status === 'excluded')  return 'grey marked right';
		return '';
	}


	/* -------------------------------------------- */
	/* ------- Enrollment status filter ----------- */
	/* -------------------------------------------- */
	/* SQL fragment restricting to the selected enrollment status ('' = all subjects). The status is
	   validated against a whitelist by the caller, so the value is safe to inline. */
	function EnrollStatusSQL($status, $alias = 'b') {
		if (in_array($status, ['enrolled', 'consented', 'completed', 'excluded'], true))
			return " and $alias.enroll_status = '$status'";
		return "";
	}

	/* Fomantic color for the active status button (blank statuses have no color) */
	function EnrollStatusColor($status) {
		$c = ['enrolled' => 'blue', 'consented' => 'teal', 'completed' => 'green', 'excluded' => 'grey'];
		return $c[$status] ?? '';
	}

	/* renders the enrollment-status filter button group. Each link preserves the current view and
	   query parameters, changing only enrollstatus. The active button is colored per its status. */
	function DisplayEnrollFilter($enrollstatus) {
		$statuses = ['all' => 'All', 'enrolled' => 'Enrolled', 'consented' => 'Consented', 'completed' => 'Completed', 'excluded' => 'Excluded'];
		$base = $_GET;
		?><div class="ui compact buttons"><?
		foreach ($statuses as $key => $label) {
			$q = $base;
			$q['enrollstatus'] = $key;
			$url = 'checklist.php?' . htmlspecialchars(http_build_query($q));
			if ($key === $enrollstatus) {
				?><a href="<?=$url?>" class="ui active <?=EnrollStatusColor($key)?> button"><?=$label?></a><?
			} else {
				?><a href="<?=$url?>" class="ui basic button"><?=$label?></a><?
			}
		}
		?></div><?
	}


	/* -------------------------------------------- */
	/* ------- ChecklistCountPair ----------------- */
	/* -------------------------------------------- */
	/* runs a query (bound to $projectid) that returns 'subjects' and 'records' columns */
	function ChecklistCountPair($sqlstring, $projectid) {
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return ['subjects' => (int)($row['subjects'] ?? 0), 'records' => (int)($row['records'] ?? 0)];
	}


	/* -------------------------------------------- */
	/* ------- GetChecklistSummary ---------------- */
	/* -------------------------------------------- */
	/* returns per-type subject/record counts for the project. Definitions are kept here so they
	   can be refined alongside each detail view as it is built. */
	function GetChecklistSummary($projectid, $enrollFilter = "") {
		$s = [];

		/* Imaging: subjects with at least one study, and total studies */
		$s['imaging'] = ChecklistCountPair(
			"select count(distinct c.subject_id) subjects, count(a.study_id) records
			 from studies a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 where b.project_id = ? and c.isactive = 1 $enrollFilter", $projectid);

		/* Observations: regular (non-image, non-timeseries) instrument/assessment observations */
		$s['observations'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.observation_id) records
			 from observations a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			 where b.project_id = ? and c.isactive = 1 $enrollFilter
			   and (ii.item_type is null or ii.item_type not in ('image','timeseries'))", $projectid);

		/* Interventions: subjects with at least one intervention, and total interventions */
		$s['interventions'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.intervention_id) records
			 from interventions a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 where b.project_id = ? and c.isactive = 1 $enrollFilter", $projectid);

		/* Observation images: observations whose instrument item is of type 'image' */
		$s['observationimages'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.observation_id) records
			 from observations a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			 where b.project_id = ? and c.isactive = 1 $enrollFilter and ii.item_type = 'image' and a.observation_fileid > 0", $projectid);

		/* Observation timeseries: observations whose instrument item is of type 'timeseries' */
		$s['observationtimeseries'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.observation_id) records
			 from observations a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			 where b.project_id = ? and c.isactive = 1 $enrollFilter and ii.item_type = 'timeseries'", $projectid);

		return $s;
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectPicker --------------- */
	/* -------------------------------------------- */
	/* shown when no project is selected */
	function DisplayProjectPicker() {
		?>
		<div class="ui container">
			<div class="ui segment">
				<h2 class="ui header">
					<div class="content">Data Checklist<div class="sub header">Select a project to view its data checklists</div></div>
				</h2>
				<form method="get" action="checklist.php" class="ui form">
					<div class="ui fluid labeled input">
						<div class="ui label">Project</div>
						<select name="projectid" class="ui fluid search dropdown" required onchange="this.form.submit()">
							<option value="">Select Project...</option>
							<?
								$sqlstring = "select a.project_id, a.project_name, a.project_costcenter from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') and a.instance_id = '" . $_SESSION['instanceid'] . "' order by a.project_name";
								$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									?><option value="<?=$row['project_id']?>"><?=htmlspecialchars($row['project_name'])?> (<?=htmlspecialchars($row['project_costcenter'])?>)</option><?
								}
							?>
						</select>
					</div>
				</form>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayHeaderBar ------------------- */
	/* -------------------------------------------- */
	function DisplayHeaderBar($projectid, $projectname, $subtitle = "") {
		global $enrollstatus;

		$displayBackButton = true;
		if ($subtitle == "") {
			$subtitle = $projectname;
			$displayBackButton = false;
		}
		?>
		<div class="ui top attached blue segment">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">
						<div class="content"><?=htmlspecialchars($subtitle)?> checklist</div>
					</h2>
				</div>
				<div class="right aligned column">
					<? if ($displayBackButton) { ?>
					<a href="checklist.php?projectid=<?=$projectid?>&enrollstatus=<?=$enrollstatus?>" class="ui basic button"><i class="arrow left icon"></i> Back to checklist</a>
					<? } ?>
				</div>
			</div>
			<div style="margin-top:10px">
				<span style="color:#888; font-size:0.9em; margin-right:8px">Enrollment status</span>
				<? DisplayEnrollFilter($enrollstatus); ?>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySummary --------------------- */
	/* -------------------------------------------- */
	function DisplaySummary($projectid) {
		global $checklistTypes, $enrollstatus;

		if ($projectid < 1) { DisplayProjectPicker(); return; }

		$projectname = GetProjectName($projectid);
		$enrollFilter = EnrollStatusSQL($enrollstatus);

		/* total active subjects in the project (denominator for coverage) */
		$totalSubjects = ChecklistCountPair(
			"select count(distinct c.subject_id) subjects, 0 records
			 from enrollment b left join subjects c on b.subject_id = c.subject_id
			 where b.project_id = ? and c.isactive = 1 $enrollFilter", $projectid)['subjects'];

		$summary = GetChecklistSummary($projectid, $enrollFilter);
		?>
		<div class="ui container">
			<? DisplayHeaderBar($projectid, $projectname); ?>

			<div class="ui bottom attached segment">
				<p style="color:#666; margin-bottom:2px"><b><?=number_format($totalSubjects)?></b> active subjects</p>

				<div class="ui stackable three column grid" style="margin-top:8px">
					<?
						foreach ($checklistTypes as $key => $info) {
							$subjects = $summary[$key]['subjects'];
							$records  = $summary[$key]['records'];
							$pct = ($totalSubjects > 0) ? round(($subjects / $totalSubjects) * 100) : 0;
							?>
							<div class="column">
								<div class="ui fluid <?=$info['color']?> segment" style="height:100%">
									<h4 class="ui header">
										<i class="<?=$info['icon']?>"></i>
										<div class="content"><?=$info['label']?>
											<div class="sub header"><?=htmlspecialchars($info['desc'])?></div>
										</div>
									</h4>

									<table class="ui very basic compact table" style="margin:6px 0 4px 0; border:none">
										<tbody>
											<tr>
												<td style="color:#888; padding:2px 0; border:none">Subjects with data</td>
												<td style="text-align:right; padding:2px 0; border:none"><b><?=number_format($subjects)?></b> <span style="color:#aaa">of <?=number_format($totalSubjects)?> (<?=$pct?>%)</span></td>
											</tr>
											<tr>
												<td style="color:#888; padding:2px 0; border:none">Records</td>
												<td style="text-align:right; padding:2px 0; border:none"><b><?=number_format($records)?></b></td>
											</tr>
										</tbody>
									</table>

									<div style="background:#e8e8e8; border-radius:3px; height:6px; margin:0 0 12px 0; overflow:hidden" title="<?=$pct?>% of subjects have data">
										<div style="width:<?=$pct?>%; height:100%; background:<?=$info['hex']?>"></div>
									</div>

									<a href="checklist.php?action=<?=$key?>&projectid=<?=$projectid?>&enrollstatus=<?=$enrollstatus?>">View details <i class="arrow right icon"></i></a>
								</div>
							</div>
							<?
						}
					?>
				</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDetail ---------------------- */
	/* -------------------------------------------- */
	/* placeholder drill-down view. Each type's detailed checklist will be built out one by one. */
	function DisplayDetail($projectid, $type) {
		global $checklistTypes;

		if ($projectid < 1) { DisplayProjectPicker(); return; }
		if (!isset($checklistTypes[$type])) { DisplaySummary($projectid); return; }

		$projectname = GetProjectName($projectid);
		$info = $checklistTypes[$type];
		?>
		<div class="ui container">
			<? DisplayHeaderBar($projectid, $projectname, $info['label']); ?>

			<div class="ui bottom attached segment">
				<div class="ui <?=$info['color']?> segment" style="margin-top:12px">
					<h3 class="ui header">
						<i class="<?=$info['icon']?>"></i>
						<div class="content"><?=$info['label']?><div class="sub header"><?=htmlspecialchars($info['desc'])?></div></div>
					</h3>
					<div class="ui info message">
						<i class="hourglass half icon"></i> The detailed <b><?=htmlspecialchars($info['label'])?></b> checklist view is coming soon.
					</div>
				</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayObservationImages ----------- */
	/* -------------------------------------------- */
	/* day view: subjects (rows, alphabetical by UID) x time-of-day (columns) of image thumbnails */
	function DisplayObservationImages($projectid) {
		global $checklistTypes, $enrollstatus;

		if ($projectid < 1) { DisplayProjectPicker(); return; }

		$projectname = GetProjectName($projectid);
		$info = $checklistTypes['observationimages'];
		$enrollFilter = EnrollStatusSQL($enrollstatus);

		/* determine which date to show. Default to the most recent date that has image data. */
		$date = GetVariable("date");
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			$sqlstring = "select max(date(a.observation_startdate)) maxd
				from observations a
				left join enrollment b on a.enrollment_id = b.enrollment_id
				left join subjects c on b.subject_id = c.subject_id
				left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
				where b.project_id = ? and c.isactive = 1 $enrollFilter and a.observation_fileid > 0 and ii.item_type = 'image'";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$date = ($row && $row['maxd']) ? $row['maxd'] : date('Y-m-d');
		}

		$prevDate  = date('Y-m-d', strtotime($date . ' -1 day'));
		$nextDate  = date('Y-m-d', strtotime($date . ' +1 day'));
		$dateLabel = date('l, F j, Y', strtotime($date));

		/* all active subjects in the project - we show every subject (even with no images) so that
		   missing data is visible to the user */
		$allSubjects = [];
		$sqlstring = "select c.uid, c.subject_id, max(b.enroll_status) enroll_status
			from enrollment b
			join subjects c on b.subject_id = c.subject_id
			where b.project_id = ? and c.isactive = 1 $enrollFilter
			group by c.subject_id, c.uid
			order by c.uid";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $allSubjects[] = $row; }
		mysqli_stmt_close($stmt);

		/* image observations for this date, bucketed by subject UID and time of day */
		$sqlstring = "select c.uid, a.observation_id, a.observation_startdate, a.observation_name,
			a.observation_fileid, f.file_name, hour(a.observation_startdate) hr
			from observations a
			left join enrollment b on a.enrollment_id = b.enrollment_id
			left join subjects c on b.subject_id = c.subject_id
			left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			left join files f on a.observation_fileid = f.file_id
			where b.project_id = ? and c.isactive = 1 $enrollFilter and a.observation_fileid > 0
			  and ii.item_type = 'image' and date(a.observation_startdate) = ?
			order by c.uid, a.observation_startdate";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $date);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $date]);

		$imgByUid = [];   /* uid => ['morning'=>[], 'afternoon'=>[], 'evening'=>[]] */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			if (!isset($imgByUid[$uid])) $imgByUid[$uid] = ['morning' => [], 'afternoon' => [], 'evening' => []];
			$hr = (int)$row['hr'];
			if ($hr < 12)     $bucket = 'morning';
			elseif ($hr < 18) $bucket = 'afternoon';
			else              $bucket = 'evening';
			$imgByUid[$uid][$bucket][] = $row;
		}
		mysqli_stmt_close($stmt);
		?>
		<div class="ui container">
			<? DisplayHeaderBar($projectid, $projectname, $info['label']); ?>

			<div class="ui bottom attached segment">
				<!-- date navigator -->
				<div class="ui center aligned basic segment" style="margin-top:8px">
					<div class="ui buttons">
						<a href="checklist.php?action=observationimages&projectid=<?=$projectid?>&enrollstatus=<?=$enrollstatus?>&date=<?=$prevDate?>" class="ui labeled icon button"><i class="left arrow icon"></i> <?=date('M j', strtotime($prevDate))?></a>
						<div class="ui active button" style="min-width:16em"><i class="calendar outline icon"></i> <?=$dateLabel?></div>
						<a href="checklist.php?action=observationimages&projectid=<?=$projectid?>&enrollstatus=<?=$enrollstatus?>&date=<?=$nextDate?>" class="ui right labeled icon button"><?=date('M j', strtotime($nextDate))?> <i class="right arrow icon"></i></a>
					</div>
				</div>

				<? if (count($allSubjects) == 0) { ?>
					<div class="ui info message"><i class="info circle icon"></i> This project has no active subjects.</div>
				<? } else { ?>
					<table class="ui celled structured table">
						<thead>
							<tr>
								<th class="two wide">Subject</th>
								<th>Morning <span style="color:#999; font-weight:normal">(before 12pm)</span></th>
								<th>Afternoon <span style="color:#999; font-weight:normal">(12&ndash;6pm)</span></th>
								<th>Evening <span style="color:#999; font-weight:normal">(after 6pm)</span></th>
							</tr>
						</thead>
						<tbody>
							<? foreach ($allSubjects as $subj) {
								$uid = $subj['uid'];
								$buckets = $imgByUid[$uid] ?? ['morning' => [], 'afternoon' => [], 'evening' => []];
								?>
							<tr>
								<td class="<?=EnrollMarkClass($subj['enroll_status'])?>"<?=$subj['enroll_status'] != '' ? ' title="Enrollment: ' . htmlspecialchars(ucfirst($subj['enroll_status'])) . '"' : ''?>>
									<a href="subjects.php?subjectid=<?=(int)$subj['subject_id']?>"><b><?=htmlspecialchars($uid)?></b></a>
								</td>
								<? foreach (['morning', 'afternoon', 'evening'] as $bucket) { ?>
								<td>
									<? if (count($buckets[$bucket]) == 0) { ?>
										<span style="color:#ddd">&mdash;</span>
									<? } else foreach ($buckets[$bucket] as $img) {
										$fid = (int)$img['observation_fileid'];
										$cap = date('g:i a', strtotime($img['observation_startdate']));
										$caption = $img['observation_name'] . ' @ ' . $cap;
										?>
										<span onclick="showObsImage(<?=$fid?>, <?=htmlspecialchars(json_encode($caption), ENT_QUOTES)?>)" title="<?=htmlspecialchars($caption)?>" style="cursor:pointer; display:inline-block; text-align:center; margin:3px; vertical-align:top">
											<img src="getfile.php?fileid=<?=$fid?>" style="max-height:110px; max-width:150px; border:1px solid #ccc; border-radius:3px" loading="lazy">
											<div style="font-size:0.75em; color:#888"><?=$cap?></div>
										</span>
									<? } ?>
								</td>
								<? } ?>
							</tr>
							<? } ?>
						</tbody>
					</table>
				<? } ?>
			</div>
		</div>

		<!-- image preview modal -->
		<div id="obsImageModal" onclick="hideObsImage()" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); text-align:center">
			<span style="position:absolute; top:15px; right:30px; color:#fff; font-size:40px; cursor:pointer" title="Close">&times;</span>
			<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); max-width:92%; max-height:92%">
				<img id="obsImageModalImg" src="" style="max-width:100%; max-height:82vh; border:3px solid #fff; border-radius:4px; background:#fff">
				<div id="obsImageModalCap" style="color:#fff; margin-top:10px; font-size:1.1em"></div>
			</div>
		</div>
		<script>
			function showObsImage(fileid, caption) {
				document.getElementById('obsImageModalImg').src = 'getfile.php?fileid=' + fileid;
				document.getElementById('obsImageModalCap').textContent = caption || '';
				document.getElementById('obsImageModal').style.display = 'block';
			}
			function hideObsImage() {
				document.getElementById('obsImageModal').style.display = 'none';
				document.getElementById('obsImageModalImg').src = '';
			}
			document.addEventListener('keydown', function(e) { if (e.key === 'Escape') hideObsImage(); });
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayObservationTimeseries ------- */
	/* -------------------------------------------- */
	/* day/window view: subjects (rows, by UID) each with an inline timeseries chart for the
	   selected instrument item over the selected 24hr-aligned time window. */
	function DisplayObservationTimeseries($projectid) {
		global $checklistTypes, $enrollstatus;

		if ($projectid < 1) { DisplayProjectPicker(); return; }

		$projectname = GetProjectName($projectid);
		$info = $checklistTypes['observationtimeseries'];
		$enrollFilter = EnrollStatusSQL($enrollstatus);

		/* --- available timeseries instrument items for the dropdown --- */
		$items = [];
		$sqlstring = "select ii.instrumentitem_id, ii.item_name, ins.instrument_name
			from observations a
			join enrollment b on a.enrollment_id = b.enrollment_id
			join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			join instruments ins on ii.instrument_id = ins.instrument_id
			where b.project_id = ? and ii.item_type = 'timeseries'
			group by ii.instrumentitem_id, ii.item_name, ins.instrument_name
			order by ins.instrument_name, ii.item_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $items[] = $row; }
		mysqli_stmt_close($stmt);

		/* selected item (default to first) */
		$itemid = (int)GetVariable("instrumentitemid");
		$validItem = false;
		foreach ($items as $it) { if ((int)$it['instrumentitem_id'] == $itemid) { $validItem = true; break; } }
		if (!$validItem) $itemid = (count($items) > 0) ? (int)$items[0]['instrumentitem_id'] : 0;

		/* --- time window (default: last full day). Windows are aligned to calendar days (12a-1159p). --- */
		$timeframes = [
			'today'   => 'Today',
			'lastday' => 'Last day',
			'last7'   => 'Last 7 full days',
			'last30'  => 'Last 30 full days',
			'all'     => 'All',
		];
		$timeframe = GetVariable("timeframe");
		if (!isset($timeframes[$timeframe])) $timeframe = 'lastday';

		$todayStart = strtotime('today');
		switch ($timeframe) {
			case 'today':  $winStart = $todayStart;               $winEnd = $todayStart + 86400 - 1; break;
			case 'last7':  $winStart = $todayStart - 7 * 86400;   $winEnd = $todayStart - 1;         break;
			case 'last30': $winStart = $todayStart - 30 * 86400;  $winEnd = $todayStart - 1;         break;
			case 'all':    $winStart = null;                      $winEnd = null;                    break;
			case 'lastday':
			default:       $winStart = $todayStart - 86400;       $winEnd = $todayStart - 1;         break;
		}

		/* --- all active subjects in the project. We show every subject (even with no data in the
		   window) so that missing data is visible to the user; each chart reports its own emptiness. --- */
		$subjects = [];
		$sqlstring = "select c.uid, c.subject_id, min(b.enrollment_id) enrollment_id, max(b.enroll_status) enroll_status
			from enrollment b
			join subjects c on b.subject_id = c.subject_id
			where b.project_id = ? and c.isactive = 1 $enrollFilter
			group by c.subject_id, c.uid
			order by c.uid";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $subjects[] = $row; }
		mysqli_stmt_close($stmt);

		/* JS window values (epoch ms), or null for "all" */
		$tstartMs = ($winStart !== null) ? ($winStart * 1000) : 'null';
		$tendMs   = ($winEnd   !== null) ? ($winEnd   * 1000) : 'null';

		/* shared x-axis range (epoch seconds) so every subject's chart aligns to the same timeline:
		   for a bounded window it is the window itself; for "All" it is the item's full data extent. */
		if ($winStart !== null) {
			$xMinSec = $winStart;
			$xMaxSec = $winEnd;
		} else {
			$xMinSec = null;
			$xMaxSec = null;
			if ($itemid > 0) {
				$sqlstring = "select min(unix_timestamp(t.time)) mn, max(unix_timestamp(t.time)) mx
					from timeseries t
					join observations a on t.observation_id = a.observation_id
					join enrollment b on a.enrollment_id = b.enrollment_id
					where b.project_id = ? and a.instrumentitem_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ii', $projectid, $itemid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $itemid]);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
				if ($row && $row['mn'] !== null) { $xMinSec = (int)$row['mn']; $xMaxSec = (int)$row['mx']; }
			}
		}
		$xMinJs = ($xMinSec !== null) ? (int)$xMinSec : 'null';
		$xMaxJs = ($xMaxSec !== null) ? (int)$xMaxSec : 'null';
		?>
		<link rel="stylesheet" href="scripts/uplot/uPlot.min.css">
		<script src="scripts/uplot/uPlot.iife.min.js"></script>

		<div class="ui container">
			<? DisplayHeaderBar($projectid, $projectname, $info['label']); ?>

			<div class="ui bottom attached segment">
				<? if (count($items) == 0) { ?>
					<div class="ui info message" style="margin-top:12px"><i class="info circle icon"></i> This project has no timeseries observations.</div>
				<? } else { ?>
					<!-- item + timeframe selectors -->
					<form method="get" action="checklist.php" class="ui form" style="margin-top:12px">
						<input type="hidden" name="action" value="observationtimeseries">
						<input type="hidden" name="projectid" value="<?=$projectid?>">
						<input type="hidden" name="enrollstatus" value="<?=$enrollstatus?>">
						<div class="inline fields" style="margin-bottom:0">
							<div class="field">
								<label>Observation</label>
								<select name="instrumentitemid" onchange="this.form.submit()" style="padding:6px; border:1px solid #ccc; border-radius:3px">
									<? foreach ($items as $it) {
										$sel = ((int)$it['instrumentitem_id'] == $itemid) ? 'selected' : '';
										?><option value="<?=$it['instrumentitem_id']?>" <?=$sel?>><?=htmlspecialchars($it['instrument_name'] . ' / ' . $it['item_name'])?></option><?
									} ?>
								</select>
							</div>
							<div class="field">
								<label>Timeframe</label>
								<select name="timeframe" onchange="this.form.submit()" style="padding:6px; border:1px solid #ccc; border-radius:3px">
									<? foreach ($timeframes as $tfkey => $tflabel) {
										$sel = ($tfkey == $timeframe) ? 'selected' : '';
										?><option value="<?=$tfkey?>" <?=$sel?>><?=$tflabel?></option><?
									} ?>
								</select>
							</div>
						</div>
					</form>

					<? if (count($subjects) == 0) { ?>
						<div class="ui info message" style="margin-top:12px"><i class="info circle icon"></i> This project has no active subjects.</div>
					<? } else { ?>
						<table class="ui celled structured very compact table" style="margin-top:12px">
							<thead>
								<tr>
									<th class="two wide">Subject</th>
									<th>Timeseries</th>
								</tr>
							</thead>
							<tbody>
								<? $charts = [];
								   foreach ($subjects as $s) {
									$divid = 'tschart_' . (int)$s['enrollment_id'];
									$charts[] = ['id' => $divid, 'enrollmentid' => (int)$s['enrollment_id']];
									?>
								<tr>
									<td class="top aligned <?=EnrollMarkClass($s['enroll_status'])?>"<?=$s['enroll_status'] != '' ? ' title="Enrollment: ' . htmlspecialchars(ucfirst($s['enroll_status'])) . '"' : ''?>>
										<a href="subjects.php?subjectid=<?=(int)$s['subject_id']?>"><b><?=htmlspecialchars($s['uid'])?></b></a>
									</td>
									<td class="top aligned" style="padding:2px 4px">
										<div id="<?=$divid?>" style="width:100%"><div style="color:#bbb; font-size:0.8em; padding:2px 6px">Loading&hellip;</div></div>
									</td>
								</tr>
								<? } ?>
							</tbody>
						</table>

						<script>
							(function() {
								var ITEMID = <?=$itemid?>;
								var TSTART = <?=$tstartMs?>;
								var TEND   = <?=$tendMs?>;
								var XMIN   = <?=$xMinJs?>;   /* shared x-axis range (epoch seconds) */
								var XMAX   = <?=$xMaxJs?>;
								var COLOR  = '<?=$info['hex']?>';
								var charts = <?=json_encode($charts)?>;
								var GAP_MIN_SEC = 60 * 60;   /* never shade gaps shorter than 60 minutes */

								/* uPlot hook: shade a gray band over any span with no data for longer than the
								   adaptive threshold. The threshold adapts to the point spacing (which reflects the
								   server-side downsample bucket size) so wide windows don't over-shade:
								   threshold = max(60 min, 2 x median spacing between consecutive points). */
								function drawGaps(u) {
									var xs = u.data[0];
									if (!xs || xs.length < 2) return;

									/* deltas between consecutive points */
									var deltas = [];
									for (var i = 1; i < xs.length; i++) deltas.push(xs[i] - xs[i - 1]);
									var sorted = deltas.slice().sort(function(a, b) { return a - b; });
									var median = sorted[Math.floor(sorted.length / 2)] || 0;
									var threshold = Math.max(GAP_MIN_SEC, 2 * median);

									var ctx = u.ctx;
									var top = u.bbox.top, hgt = u.bbox.height;
									ctx.save();
									ctx.fillStyle = 'rgba(0,0,0,0.10)';
									for (var j = 0; j < deltas.length; j++) {
										if (deltas[j] > threshold) {
											var x1 = u.valToPos(xs[j], 'x', true);
											var x2 = u.valToPos(xs[j + 1], 'x', true);
											ctx.fillRect(x1, top, x2 - x1, hgt);
										}
									}
									ctx.restore();
								}

								function renderTS(divId, enrollmentid) {
									var el = document.getElementById(divId);
									if (!el) return;
									if (typeof uPlot === 'undefined') { el.innerHTML = '<div style="color:#c00; font-size:0.85em; padding:8px">Chart library failed to load</div>'; return; }
									var params = 'action=getchecklisttimeseries&enrollmentid=' + enrollmentid + '&instrumentitemid=' + ITEMID + '&maxpoints=800';
									if (TSTART !== null && TEND !== null) params += '&tstart=' + TSTART + '&tend=' + TEND;
									fetch('ajaxapi.php?' + params).then(function(r) { return r.json(); }).then(function(resp) {
										if (!resp || resp.error || resp.seriesType === 'empty' || !resp.points || !resp.points.length) {
											el.innerHTML = '<div style="color:#bbb; font-size:0.8em; padding:2px 6px">No data in this window</div>';
											return;
										}
										if (resp.seriesType === 'string') {
											el.innerHTML = '<div style="color:#999; font-size:0.8em; padding:2px 6px">Non-numeric timeseries (not graphable)</div>';
											return;
										}
										var pts = resp.points, xs = new Array(pts.length), ys = new Array(pts.length);
										for (var i = 0; i < pts.length; i++) { xs[i] = pts[i][0] / 1000; ys[i] = pts[i][1]; }
										el.innerHTML = '';
										var opts = {
											width:  el.clientWidth || 700,
											height: 100,
											legend: { show: false },
											scales: { x: (XMIN !== null && XMAX !== null) ? { time: true, range: [XMIN, XMAX] } : { time: true } },
											cursor: { drag: { x: false, y: false } },
											series: [ {}, { label: 'value', stroke: COLOR, width: 1, points: { show: false } } ],
											axes:   [ {}, { size: 44 } ],
											hooks:  { drawClear: [ drawGaps ] }
										};
										new uPlot(opts, [xs, ys], el);
									}).catch(function() {
										el.innerHTML = '<div style="color:#c00; font-size:0.8em; padding:2px 6px">Error loading chart</div>';
									});
								}

								/* lazy-load: only fetch/render a chart when its row scrolls near the viewport */
								if ('IntersectionObserver' in window) {
									var io = new IntersectionObserver(function(entries, obs) {
										entries.forEach(function(entry) {
											if (entry.isIntersecting) {
												obs.unobserve(entry.target);
												renderTS(entry.target.id, entry.target.getAttribute('data-enrollmentid'));
											}
										});
									}, { rootMargin: '300px 0px' });
									charts.forEach(function(c) {
										var el = document.getElementById(c.id);
										if (el) { el.setAttribute('data-enrollmentid', c.enrollmentid); io.observe(el); }
									});
								} else {
									/* older browsers: just render everything */
									charts.forEach(function(c) { renderTS(c.id, c.enrollmentid); });
								}
							})();
						</script>
					<? } ?>
				<? } ?>
			</div>
		</div>
		<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayImaging --------------------- */
	/* -------------------------------------------- */
	/* Imaging checklist matrix (subjects x checklist items), ported from projectchecklist.php.
	   Rendered full page width because of the number of columns. Honors the enrollment filter. */
	function DisplayImaging($projectid) {
		global $enrollstatus;

		if ($projectid < 1) { DisplayProjectPicker(); return; }
		$enrollFilter = EnrollStatusSQL($enrollstatus, 'a');

		$p = GetProjectInfo($projectid);
		$projectname = $p['projectName'];
		$usecustomid = $p['projectUseCustomID'];

		/* the main checklist items (each becomes a column) */
		$checklist = array();
		$i = 0;
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from project_checklist where project_id = ? order by item_order asc");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$checklist[$i]['id']       = $row['projectchecklist_id'];
			$checklist[$i]['name']     = $row['item_name'];
			$checklist[$i]['desc']     = $row['item_desc'];
			$checklist[$i]['order']    = $row['item_order'];
			$checklist[$i]['modality'] = $row['imaging_modality'];
			$checklist[$i]['protocol'] = $row['mapped_name'];
			$checklist[$i]['count']    = $row['expected_count'];
			$i++;
		}
		mysqli_stmt_close($stmt);

		/* the enrollments (rows), honoring the enrollment-status filter */
		$enrollment = array();
		$sqlstring = "select a.*, b.subject_id, b.uid, b.guid, b.isactive from enrollment a left join subjects b on a.subject_id = b.subject_id where a.project_id = ? and b.isactive = 1 $enrollFilter order by b.uid asc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$enrollment[$uid]['guid']            = $row['guid'];
			$enrollment[$uid]['enrollment_id']   = $row['enrollment_id'];
			$enrollment[$uid]['project_id']      = $row['project_id'];
			$enrollment[$uid]['subject_id']      = $row['subject_id'];
			$enrollment[$uid]['isactive']        = $row['isactive'];
			$enrollment[$uid]['enroll_startdate'] = $row['enroll_startdate'];
			$enrollment[$uid]['enroll_subgroup'] = $row['enroll_subgroup'];
		}
		mysqli_stmt_close($stmt);
		$numenrollments = count($enrollment);

		/* ag-grid columns: fixed leading columns, then one per checklist item, then completeness */
		$columnDefs = array(
			array('headerName' => 'Primary ID',  'field' => 'primaryid',  'minWidth' => 130, 'flex' => 1),
			array('headerName' => 'UID',         'field' => 'uid',        'minWidth' => 120, 'flex' => 1, 'pinned' => 'left'),
			array('headerName' => 'GUID',        'field' => 'guid',       'minWidth' => 170, 'flex' => 1.2),
			array('headerName' => 'Enroll date', 'field' => 'enrolldate', 'minWidth' => 130, 'flex' => 1),
			array('headerName' => '# studies',   'field' => 'numstudies', 'minWidth' => 110, 'maxWidth' => 120, 'flex' => 0.7),
			array('headerName' => 'Group',       'field' => 'group',      'minWidth' => 120, 'flex' => 1)
		);
		$totals = array(0, 0, 0, 0, 0);
		$ii = 5;
		foreach ($checklist as $i => $item) {
			$columnDefs[] = array(
				'headerName'    => $item['name'],
				'field'         => 'checklist_' . $item['id'],
				'minWidth'      => 120,
				'flex'          => 1,
				'headerTooltip' => "Modality: " . $item['modality'] . "\nProtocol: " . $item['protocol'] . "\nDescription: " . $item['desc']
			);
			$totals[$ii] = 0;
			$ii++;
		}
		$columnDefs[] = array('headerName' => 'Complete data?', 'field' => 'complete', 'minWidth' => 150, 'flex' => 1.2);

		/* one grid row per subject */
		$rowdata = array();
		if (is_array($enrollment)) foreach ($enrollment as $uid => $subject) {
			$guid           = $subject['guid'];
			$enrolldate     = $subject['enroll_startdate'];
			$enrollsubgroup = $subject['enroll_subgroup'];
			$enrollmentid   = $subject['enrollment_id'];
			$subjectid      = $subject['subject_id'];
			$isactive       = $subject['isactive'];
			$rowtotal       = 0;

			if ($enrollmentid == '') continue;

			$studyids = GetStudiesByEnrollment($enrollmentid);

			/* project-specific alt uid */
			$stmt = mysqli_prepare($GLOBALS['linki'], "select altuid from subject_altuid where subject_id = ? and enrollment_id = ? and isprimary = 1");
			mysqli_stmt_bind_param($stmt, 'ii', $subjectid, $enrollmentid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$altuid = $row['altuid'] ?? '';

			$deleted = (!$isactive) ? "Deleted" : "";

			if ($uid != "")            { $totals[0]++; }
			if ($guid != "")           { $totals[1]++; }
			if ($altuid != "")         { $totals[2]++; }
			if ($enrolldate != "")     { $totals[3]++; }
			if ($enrollsubgroup != "") { $totals[4]++; }

			if (($usecustomid == 1) && ($altuid == "")) {
				$customidstyle = "border: 1px solid red; background-color: orange";
				$customidtext  = "<i style='color: red'>missing ID</i>";
			} else {
				$customidstyle = "";
				$customidtext  = $altuid;
			}

			$gridrow = array(
				'enrollmentid'    => $enrollmentid,
				'primaryid'       => $customidtext,
				'primaryid_style' => $customidstyle,
				'uid'             => "<a href=\"subjects.php?id=$subjectid\">$uid</a> $deleted",
				'guid'            => $guid,
				'enrolldate'      => "<a href=\"enrollment.php?id=$enrollmentid\">$enrolldate</a>",
				'numstudies'      => count($studyids),
				'group'           => $enrollsubgroup
			);

			/* evaluate every checklist item for this subject (even with no studies), so that any
			   item can be marked as missing with a reason. */
			$ii = 5;
			foreach ($checklist as $i => $item) {
				$itemid   = strtolower($item['id']);
				$modality = strtolower($item['modality']);
				$protocol = $item['protocol'];

				$protocols = explode(',', $protocol);
				foreach ($protocols as $k => $pp) { $protocols[$k] = "'" . mysqli_real_escape_string($GLOBALS['linki'], trim($protocols[$k])) . "'"; }

				$msg = "";
				if (!preg_match('/^[a-z0-9]+$/', $modality)) $modality = "";
				$seriestable = GetSeriesTableName($modality);

				/* prod-safe existence check (SHOW ... LIKE can't be prepared on older MariaDB) */
				$tableexists = false;
				if ($seriestable != "") {
					$stmt = mysqli_prepare($GLOBALS['linki'], "select table_name from information_schema.tables where table_schema = database() and table_name = ?");
					mysqli_stmt_bind_param($stmt, 's', $seriestable);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					$tableexists = (mysqli_num_rows($result) > 0);
					mysqli_stmt_close($stmt);
				}

				if ($tableexists) {
					/* imaging item: look for a matching series (only possible if the subject has studies) */
					if (count($studyids) > 0) {
						$numfilesfield = (strtolower($modality) == "mr") ? "numfiles" : "series_numfiles";
						$sqlstring = "select study_id from $seriestable where study_id in (" . implode(',', array_map('intval', (array)$studyids)) . ") and (trim(series_desc) in (" . implode(',', $protocols) . ") or trim(series_protocol) in (" . implode(',', $protocols) . ")) and $numfilesfield > 0";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$studyid = $row['study_id'];
							$msg = "<a href='studies.php?id=$studyid'>&#10004;</a>";
							$totals[$ii]++;
							$rowtotal++;
						}
					}
				} else {
					/* manual item: look for a manual check-off in enrollment_checklist */
					$stmt = mysqli_prepare($GLOBALS['linki'], "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?");
					mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $itemid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					if (mysqli_num_rows($result) > 0) {
						$msg = "&#10004;";
						$totals[$ii]++;
						$rowtotal++;
					}
					mysqli_stmt_close($stmt);
				}

				if ($msg == "") {
					$itemAttr = htmlspecialchars($item['name'], ENT_QUOTES);
					$uidAttr  = htmlspecialchars($uid, ENT_QUOTES);
					$stmt = mysqli_prepare($GLOBALS['linki'], "select * from enrollment_missingdata where enrollment_id = ? and projectchecklist_id = ?");
					mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $itemid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					if (mysqli_num_rows($result) > 0) {
						$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
						$missingdataid = $row['missingdata_id'];
						$reason        = $row['missing_reason'];
						$date          = $row['missingreason_date'];
						$reasonAttr    = htmlspecialchars($reason, ENT_QUOTES);
						$gridrow["checklist_$itemid"]            = "<a href=\"#\" class=\"mark-missing\" onclick=\"openMissingReasonModal(this); return false;\" data-enrollmentid=\"$enrollmentid\" data-projectchecklistid=\"$itemid\" data-missingdataid=\"$missingdataid\" data-reason=\"$reasonAttr\" data-item=\"$itemAttr\" data-uid=\"$uidAttr\">&#10006;</a>";
						$gridrow["checklist_$itemid" . "_style"]   = "background-image: repeating-linear-gradient(-45deg, transparent, transparent 5px, #ddd 5px, #ddd 10px);";
						$gridrow["checklist_$itemid" . "_tooltip"] = "<b>" . htmlspecialchars($reason) . "</b> - $date";
					} else {
						$gridrow["checklist_$itemid"]            = "<a href=\"#\" class=\"mark-missing\" onclick=\"openMissingReasonModal(this); return false;\" data-enrollmentid=\"$enrollmentid\" data-projectchecklistid=\"$itemid\" data-missingdataid=\"\" data-reason=\"\" data-item=\"$itemAttr\" data-uid=\"$uidAttr\">?</a>";
						$gridrow["checklist_$itemid" . "_style"]   = "border-left: 1px solid #ffd699; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #ffe0b3 5px, #ffe0b3 10px);";
						$gridrow["checklist_$itemid" . "_tooltip"] = "Click to set reason for missing data";
					}
					mysqli_stmt_close($stmt);
				} else {
					$gridrow["checklist_$itemid"] = $msg;
				}
				$ii++;
			}

			if (count($checklist) > 0 && $rowtotal == count($checklist)) {
				$gridrow['complete'] = "&#10004;";
				$totals[$ii]++;
			} else {
				$gridrow['complete']       = "Only $rowtotal of " . count($checklist);
				$gridrow['complete_style'] = "text-align: center; font-size:8pt;";
			}
			$rowdata[] = $gridrow;
		}

		/* pinned totals row */
		$pinnedrow = array(
			'primaryid'  => 'Totals',
			'uid'        => $totals[0],
			'guid'       => $totals[1],
			'enrolldate' => $totals[3],
			'numstudies' => '',
			'group'      => $totals[4]
		);
		$ii = 5;
		foreach ($checklist as $i => $item) { $pinnedrow['checklist_' . $item['id']] = $totals[$ii]; $ii++; }
		$pinnedrow['complete'] = $totals[$ii];

		$columnData = json_encode($columnDefs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$rowData    = json_encode($rowdata,    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$pinnedData = json_encode(array($pinnedrow), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<style>
			#imagingchecklistgrid { width: 100%; height: 64vh; }
			.imagingchecklist-html-cell { width: 100%; height: 100%; display: flex; align-items: center; }
			.imagingchecklist-html-cell.centered { justify-content: center; }
		</style>

		<div style="padding: 0 14px">
			<? DisplayHeaderBar($projectid, $projectname, 'Imaging'); ?>

			<div class="ui bottom attached segment">
				<div class="ui two column grid" style="margin-bottom:2px">
					<div class="column">Displaying <b><?=$numenrollments?> enrollments</b></div>
					<div class="right aligned column">
						<a href="checklist.php?action=editchecklist&projectid=<?=$projectid?>&enrollstatus=<?=$enrollstatus?>" class="ui primary basic button"><i class="edit icon"></i> Edit imaging checklist</a>
					</div>
				</div>

				<div id="imagingchecklistgrid"></div>
				<div style="color:#888; font-size:0.85em; margin-top:4px">Displaying <span id="imagingchecklistrowcount">0</span> rows</div>
			</div>
		</div>

		<script>
			const imagingColumnDefs = <?=$columnData?>;
			const imagingRowData    = <?=$rowData?>;
			const imagingPinnedData = <?=$pinnedData?>;
			let imagingGridApi;

			function imagingHtmlRenderer(params) {
				const field = params.colDef.field;
				const wrapper = document.createElement('span');
				wrapper.className = 'imagingchecklist-html-cell';
				if (field == 'numstudies' || field == 'complete' || field.indexOf('checklist_') == 0) wrapper.className += ' centered';
				if (params.data && params.data[field + '_style'])   wrapper.setAttribute('style', params.data[field + '_style']);
				if (params.data && params.data[field + '_tooltip']) {
					wrapper.className += ' imagingchecklist-html-tooltip';
					wrapper.setAttribute('data-html', params.data[field + '_tooltip']);
				}
				wrapper.innerHTML = (params.value == null) ? '' : params.value;
				return wrapper;
			}
			function imagingCellStyle(params) {
				if (params.node.rowPinned) return { 'font-weight': 'bold', 'background-color': '#f7f7f7' };
				return null;
			}
			function updateImagingRowCount() {
				if (imagingGridApi) document.getElementById('imagingchecklistrowcount').textContent = imagingGridApi.getDisplayedRowCount();
			}
			imagingColumnDefs.forEach(function(columnDef) {
				columnDef.cellRenderer     = imagingHtmlRenderer;
				columnDef.cellStyle        = imagingCellStyle;
				columnDef.wrapHeaderText   = true;
				columnDef.autoHeaderHeight = true;
			});
			const imagingTheme = agGrid.themeBalham.withParams({
				headerTextColor:       'white',
				headerBackgroundColor: '#2185d0',
				headerFontSize:        '15px',
				columnBorder:          { style: 'solid', color: '#ddd' },
			});
			const imagingGridOptions = {
				theme:                  imagingTheme,
				columnDefs:             imagingColumnDefs,
				rowData:                imagingRowData,
				pinnedBottomRowData:    imagingPinnedData,
				getRowId:               function(params) { return String(params.data.enrollmentid); },
				defaultColDef:          { sortable: true, filter: true, resizable: true },
				animateRows:            false,
				suppressMovableColumns: true,
				onFirstDataRendered:    updateImagingRowCount,
				onFilterChanged:        updateImagingRowCount,
				onModelUpdated:         updateImagingRowCount
			};
			$(document).ready(function() {
				imagingGridApi = agGrid.createGrid(document.getElementById('imagingchecklistgrid'), imagingGridOptions);
				$('#imagingchecklistgrid').tooltip({ items: ".imagingchecklist-html-tooltip", content: function() { return $(this).attr("data-html"); } });
				updateImagingRowCount();
			});
		</script>

		<!-- missing-data reason modal -->
		<div class="ui small modal" id="missingReasonModal">
			<div class="header">Reason for missing data</div>
			<div class="content">
				<p style="color:#666"><b id="mrmSubject"></b> &mdash; <span id="mrmItem"></span></p>
				<div class="ui fluid input">
					<input type="text" id="mrmReason" placeholder="Reason for missing data">
				</div>
			</div>
			<div class="actions">
				<a class="ui red basic button" id="mrmDelete" style="float:left; display:none"><i class="trash icon"></i> Delete reason</a>
				<div class="ui cancel button">Cancel</div>
				<button class="ui primary button" id="mrmSave">Save</button>
			</div>
		</div>
		<script>
			var mrmCtx = {};
			var MRM_STYLE_HAS  = "background-image: repeating-linear-gradient(-45deg, transparent, transparent 5px, #ddd 5px, #ddd 10px);";
			var MRM_STYLE_NONE = "border-left: 1px solid #ffd699; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #ffe0b3 5px, #ffe0b3 10px);";

			function mrmEsc(s) {
				return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
			}

			/* rebuild the cell anchor (matches the server-side markup) so ag-grid can re-render it */
			function mrmBuildAnchor(missingdataid, reason, symbol) {
				return '<a href="#" class="mark-missing" onclick="openMissingReasonModal(this); return false;"'
					+ ' data-enrollmentid="' + mrmEsc(mrmCtx.enrollmentid) + '"'
					+ ' data-projectchecklistid="' + mrmEsc(mrmCtx.projectchecklistid) + '"'
					+ ' data-missingdataid="' + mrmEsc(missingdataid || '') + '"'
					+ ' data-reason="' + mrmEsc(reason || '') + '"'
					+ ' data-item="' + mrmEsc(mrmCtx.item) + '"'
					+ ' data-uid="' + mrmEsc(mrmCtx.uid) + '">' + symbol + '</a>';
			}

			/* update the single ag-grid cell for the current context without reloading */
			function mrmUpdateCell(value, style, tooltip) {
				var node = imagingGridApi.getRowNode(String(mrmCtx.enrollmentid));
				if (!node) return;
				var field = 'checklist_' + mrmCtx.projectchecklistid;
				node.data[field]              = value;
				node.data[field + '_style']   = style;
				node.data[field + '_tooltip'] = tooltip;
				imagingGridApi.refreshCells({ rowNodes: [node], columns: [field], force: true });
			}

			function openMissingReasonModal(el) {
				mrmCtx = {
					enrollmentid:       el.dataset.enrollmentid,
					projectchecklistid: el.dataset.projectchecklistid,
					missingdataid:      el.dataset.missingdataid || '',
					item:               el.dataset.item || '',
					uid:                el.dataset.uid || ''
				};
				document.getElementById('mrmReason').value        = el.dataset.reason || '';
				document.getElementById('mrmSubject').textContent = mrmCtx.uid;
				document.getElementById('mrmItem').textContent    = mrmCtx.item;
				document.getElementById('mrmDelete').style.display = mrmCtx.missingdataid ? '' : 'none';
				$('#missingReasonModal').modal({ closable: true }).modal('show');
				setTimeout(function() { document.getElementById('mrmReason').focus(); }, 120);
			}

			function mrmSaveReason() {
				var reason = document.getElementById('mrmReason').value;
				var btn = document.getElementById('mrmSave');
				btn.classList.add('loading', 'disabled');
				fetch('ajaxapi.php?action=setmissingreason'
						+ '&enrollmentid=' + encodeURIComponent(mrmCtx.enrollmentid)
						+ '&projectchecklistid=' + encodeURIComponent(mrmCtx.projectchecklistid)
						+ '&reason=' + encodeURIComponent(reason))
					.then(function(r) { return r.json(); })
					.then(function(resp) {
						btn.classList.remove('loading', 'disabled');
						if (!resp || !resp.ok) { alert('Save failed: ' + ((resp && resp.error) || 'unknown error')); return; }
						mrmCtx.missingdataid = resp.missingdataid;
						mrmUpdateCell(mrmBuildAnchor(resp.missingdataid, resp.reason, '✖'), MRM_STYLE_HAS, '<b>' + mrmEsc(resp.reason) + '</b> - ' + mrmEsc(resp.date));
						$('#missingReasonModal').modal('hide');
					})
					.catch(function() { btn.classList.remove('loading', 'disabled'); alert('Save failed: network error'); });
			}

			function mrmDeleteReason() {
				if (!mrmCtx.missingdataid) return;
				if (!confirm('Delete this missing-data reason?')) return;
				fetch('ajaxapi.php?action=deletemissingreason&missingdataid=' + encodeURIComponent(mrmCtx.missingdataid))
					.then(function(r) { return r.json(); })
					.then(function(resp) {
						if (!resp || !resp.ok) { alert('Delete failed: ' + ((resp && resp.error) || 'unknown error')); return; }
						mrmCtx.missingdataid = '';
						mrmUpdateCell(mrmBuildAnchor('', '', '?'), MRM_STYLE_NONE, 'Click to set reason for missing data');
						$('#missingReasonModal').modal('hide');
					})
					.catch(function() { alert('Delete failed: network error'); });
			}

			$(document).ready(function() {
				document.getElementById('mrmSave').addEventListener('click', mrmSaveReason);
				document.getElementById('mrmDelete').addEventListener('click', mrmDeleteReason);
				document.getElementById('mrmReason').addEventListener('keydown', function(e) {
					if (e.key === 'Enter') { e.preventDefault(); mrmSaveReason(); }
				});
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetStudiesByEnrollment ------------- */
	/* -------------------------------------------- */
	function GetStudiesByEnrollment($enrollmentid) {
		$studyids = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], "select study_id from studies where enrollment_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $studyids[] = (int)$row['study_id']; }
		mysqli_stmt_close($stmt);
		return $studyids;
	}


	/* -------------------------------------------- */
	/* ------- UpdateImagingChecklist ------------- */
	/* -------------------------------------------- */
	/* saves the checklist item rows (insert/update/delete), ported from projectchecklist.php */
	function UpdateImagingChecklist($projectid, $itemid, $itemname, $itemtype, $modality, $mappedname, $expectedcount, $deleteitem, $instrumentid) {
		$projectid = trim($projectid);
		if (!isInteger($projectid)) { Error("Invalid project ID [$projectid]"); return; }
		$projectid = (int)$projectid;

		$itemids        = is_array($itemid)        ? $itemid        : array();
		$itemnames      = is_array($itemname)      ? $itemname      : array();
		$itemtypes      = is_array($itemtype)      ? $itemtype      : array();
		$modalities     = is_array($modality)      ? $modality      : array();
		$mappednames    = is_array($mappedname)    ? $mappedname    : array();
		$expectedcounts = is_array($expectedcount) ? $expectedcount : array();
		$deleteitems    = is_array($deleteitem)    ? $deleteitem    : array();
		$instrumentids  = is_array($instrumentid)  ? $instrumentid  : array();

		$allindices = array_unique(array_merge(
			array_keys($itemids), array_keys($itemnames), array_keys($itemtypes), array_keys($modalities),
			array_keys($mappednames), array_keys($expectedcounts), array_keys($deleteitems), array_keys($instrumentids)
		));
		sort($allindices, SORT_NUMERIC);

		mysqli_begin_transaction($GLOBALS['linki']);

		$deleteRowID = 0;
		$deleteStmt = mysqli_prepare($GLOBALS['linki'], "delete from project_checklist where project_id = ? and projectchecklist_id = ?");
		mysqli_stmt_bind_param($deleteStmt, 'ii', $projectid, $deleteRowID);

		$insertItemOrder = 0; $insertName = ""; $insertType = ""; $insertModality = ""; $insertMappedName = ""; $insertExpectedCount = null; $insertInstrumentId = null;
		$insertStmt = mysqli_prepare($GLOBALS['linki'], "insert into project_checklist (project_id, item_order, item_name, item_type, imaging_modality, mapped_name, expected_count, instrument_id) values (?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($insertStmt, 'iissssii', $projectid, $insertItemOrder, $insertName, $insertType, $insertModality, $insertMappedName, $insertExpectedCount, $insertInstrumentId);

		$updateItemOrder = 0; $updateName = ""; $updateType = ""; $updateModality = ""; $updateMappedName = ""; $updateExpectedCount = null; $updateInstrumentId = null; $updateRowID = 0;
		$updateStmt = mysqli_prepare($GLOBALS['linki'], "update project_checklist set item_order = ?, item_name = ?, item_type = ?, imaging_modality = ?, mapped_name = ?, expected_count = ?, instrument_id = ? where project_id = ? and projectchecklist_id = ?");
		mysqli_stmt_bind_param($updateStmt, 'issssiiii', $updateItemOrder, $updateName, $updateType, $updateModality, $updateMappedName, $updateExpectedCount, $updateInstrumentId, $projectid, $updateRowID);

		$itemorder = 1;
		foreach ($allindices as $i) {
			$rowid             = isset($itemids[$i])        ? trim($itemids[$i])        : "";
			$name              = isset($itemnames[$i])      ? trim($itemnames[$i])      : "";
			$type              = isset($itemtypes[$i])      ? trim($itemtypes[$i])      : "Checkbox";
			$itemmodality      = isset($modalities[$i])     ? trim($modalities[$i])     : "";
			/* mapped name arrives as an array (multi-select); store it comma-separated */
			if (!isset($mappednames[$i])) {
				$itemmappedname = "";
			} elseif (is_array($mappednames[$i])) {
				$vals = array_filter(array_map('trim', $mappednames[$i]), function($v) { return $v !== ""; });
				$itemmappedname = implode(',', $vals);
			} else {
				$itemmappedname = trim($mappednames[$i]);
			}
			$itemexpectedcount = isset($expectedcounts[$i]) ? trim($expectedcounts[$i]) : "";
			$delete            = isset($deleteitems[$i])    ? trim($deleteitems[$i])    : "0";
			$iteminstrumentid  = isset($instrumentids[$i])  ? (int)$instrumentids[$i]   : null;
			if ($iteminstrumentid < 1) $iteminstrumentid = null;

			if (($rowid != "") && (!isInteger($rowid))) { continue; }
			if (!in_array($type, array("Checkbox", "Imaging", "Intervention", "Observation", "Diagnosis", "Instrument"))) { $type = "Checkbox"; }
			if (($itemexpectedcount == "") || (!isInteger($itemexpectedcount)) || ($itemexpectedcount < 1)) { $itemexpectedcount = "null"; }
			else { $itemexpectedcount = intval($itemexpectedcount); }

			if (($delete == "1") && ($rowid != "")) {
				$deleteRowID = (int)$rowid;
				MySQLiBoundQuery($deleteStmt, __FILE__, __LINE__);
			}
			else if ($name != "") {
				if ($rowid == "") {
					$insertItemOrder = $itemorder;
					$insertName = $name; $insertType = $type; $insertModality = $itemmodality; $insertMappedName = $itemmappedname;
					$insertExpectedCount = ($itemexpectedcount == "null") ? null : $itemexpectedcount;
					$insertInstrumentId = $iteminstrumentid;
					MySQLiBoundQuery($insertStmt, __FILE__, __LINE__);
				}
				else {
					$updateItemOrder = $itemorder;
					$updateName = $name; $updateType = $type; $updateModality = $itemmodality; $updateMappedName = $itemmappedname;
					$updateExpectedCount = ($itemexpectedcount == "null") ? null : $itemexpectedcount;
					$updateInstrumentId = $iteminstrumentid;
					$updateRowID = (int)$rowid;
					MySQLiBoundQuery($updateStmt, __FILE__, __LINE__);
				}
				$itemorder++;
			}
		}

		mysqli_stmt_close($deleteStmt);
		mysqli_stmt_close($insertStmt);
		mysqli_stmt_close($updateStmt);
		mysqli_commit($GLOBALS['linki']);

		Notice("Checklist updated");
	}


	/* -------------------------------------------- */
	/* ------- DisplayEditImagingChecklist -------- */
	/* -------------------------------------------- */
	/* the checklist item editor (Select2 rows), ported from projectchecklist.php */
	function DisplayEditImagingChecklist($projectid) {
		global $enrollstatus;

		if ($projectid < 1) { DisplayProjectPicker(); return; }
		$projectid   = (int)$projectid;
		$projectname = GetProjectName($projectid);

		/* modality options */
		$modalitycodes = [];
		$stmt = mysqli_prepare($GLOBALS['linki'], "select mod_code from modalities order by mod_code");
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $modalitycodes[] = $row['mod_code']; }
		mysqli_stmt_close($stmt);
		$modalityoptions = "<option value=\"\"></option>";
		foreach ($modalitycodes as $mc) { $esc = htmlspecialchars($mc, ENT_QUOTES); $modalityoptions .= "<option value=\"$esc\">$esc</option>"; }

		/* instrument options */
		$instruments = [];
		$instrumentoptions = "<option value=\"\">Select instrument...</option>";
		$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id, instrument_name from instruments where project_id = ? order by instrument_name");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$iid = (int)$row['instrument_id'];
			$instrumentoptions .= "<option value=\"$iid\">" . htmlspecialchars($row['instrument_name'], ENT_QUOTES) . "</option>";
			$instruments[$iid] = $row['instrument_name'];
		}
		mysqli_stmt_close($stmt);

		/* existing series_desc values per modality for this project (to suggest as mapped names) */
		$seriesByModality = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], "select distinct study_modality from studies st join enrollment e on st.enrollment_id = e.enrollment_id where e.project_id = ? and study_modality <> ''");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$projmods = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $projmods[] = $row['study_modality']; }
		mysqli_stmt_close($stmt);
		foreach ($projmods as $mod) {
			$seriestable = GetSeriesTableName($mod);   /* validates + returns "{modality}_series" */
			if ($seriestable == "") continue;
			$chk = mysqli_prepare($GLOBALS['linki'], "select table_name from information_schema.tables where table_schema = database() and table_name = ?");
			mysqli_stmt_bind_param($chk, 's', $seriestable);
			$chkres = MySQLiBoundQuery($chk, __FILE__, __LINE__);
			$exists = (mysqli_num_rows($chkres) > 0);
			mysqli_stmt_close($chk);
			if (!$exists) continue;
			/* $seriestable is validated alnum + "_series", safe to inline */
			$sqlstring = "select distinct series_desc from $seriestable s join studies st on s.study_id = st.study_id join enrollment e on st.enrollment_id = e.enrollment_id where e.project_id = ? and trim(series_desc) <> '' order by series_desc";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
			$key = strtoupper($mod);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $seriesByModality[$key][] = $row['series_desc']; }
			mysqli_stmt_close($stmt);
		}
		?>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
		<style>
			.checklist-select { width: 100%; }
			.select2-container { width: 100% !important; }
			.select2-container--default .select2-selection--single { border: 1px solid rgba(34,36,38,.15); border-radius: .28571429rem; height: 38px; }
			.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; padding-left: 14px; color: rgba(0,0,0,.87); }
			.select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
			.checklist-name-column { width: 32%; }
			.checklist-count-column { width: 100px; }
			.checklist-count-input { max-width: 80px; }
		</style>

		<div style="padding: 0 14px">
			<? DisplayHeaderBar($projectid, $projectname, 'Edit imaging'); ?>

			<div class="ui bottom attached segment">
				<div style="margin-bottom:10px">
					<a href="checklist.php?action=imaging&projectid=<?=$projectid?>&enrollstatus=<?=$enrollstatus?>" class="ui basic blue button"><i class="arrow left icon"></i> Back to imaging checklist</a>
					<?
						$lastenrollmentid = isset($_COOKIE['lastenrollmentid']) ? (int)$_COOKIE['lastenrollmentid'] : 0;
						if ($lastenrollmentid > 0) {
							list($returnuid, $returnsubjectid, $returnaltuid, $returnprojectname, $returnprojectid) = GetEnrollmentInfo($lastenrollmentid);
							if ($returnsubjectid > 0) {
								?><a href="enrollment.php?enrollmentid=<?=$lastenrollmentid?>" class="ui basic button"><i class="user icon"></i> Return to enrollment &mdash; <?=htmlspecialchars($returnuid)?></a><?
							}
						}
					?>
				</div>

				<form method="post" action="checklist.php" id="checklistform" onSubmit="RenumberChecklistRows()">
					<input type="hidden" name="action" value="editchecklistupdate">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<input type="hidden" name="enrollstatus" value="<?=$enrollstatus?>">

					<script type="text/javascript">
						/* existing series_desc values for the project, keyed by (upper-cased) modality */
						var SERIES_BY_MODALITY = <?=json_encode($seriesByModality, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)?>;

						/* (re)build a row's mapped-name multi-select options from its modality's series_desc list,
						   preserving current selections, then (re)initialize Select2 with tag support so users can
						   also type custom values. */
						function InitMappedName(row) {
							var sel = jQuery(row).find("select.checklist-mappedname");
							if (!sel.length) return;
							var current = sel.val() || [];
							var modalityEl = row.querySelector("select.checklist-modality");
							var modality = modalityEl ? (modalityEl.value || "").toUpperCase() : "";
							var suggestions = SERIES_BY_MODALITY[modality] || [];

							if (sel.hasClass("select2-hidden-accessible")) sel.select2("destroy");
							sel.empty();
							var seen = {};
							suggestions.forEach(function(s) {
								seen[s] = true;
								sel.append(new Option(s, s, false, current.indexOf(s) !== -1));
							});
							current.forEach(function(v) {
								if (v !== "" && !seen[v]) { seen[v] = true; sel.append(new Option(v, v, false, true)); }
							});
							sel.select2({ tags: true, tokenSeparators: [","], width: "style", placeholder: "Select or type series name(s)..." });
						}

						/* Assign indexed field names so PHP receives each row as an array entry. */
						function SetChecklistInputNames(row, rownum) {
							row.querySelector("input.checklist-itemid").name = "itemid[" + rownum + "]";
							row.querySelector("input.checklist-itemname").name = "itemname[" + rownum + "]";
							row.querySelector("select.checklist-itemtype").name = "itemtype[" + rownum + "]";
							row.querySelector("select.checklist-modality").name = "modality[" + rownum + "]";
							row.querySelector("select.checklist-mappedname").name = "mappedname[" + rownum + "][]";
							row.querySelector("input.checklist-expectedcount").name = "expectedcount[" + rownum + "]";
							row.querySelector("input.checklist-deleteitem").name = "deleteitem[" + rownum + "]";
							row.querySelector("select.checklist-instrument").name = "instrumentid[" + rownum + "]";
							row.querySelector(".checklist-itemorder-display").textContent = rownum;
						}

						/* Keep submitted row order contiguous after client-side add/delete actions. */
						function RenumberChecklistRows() {
							var rownum = 1;
							document.querySelectorAll("#checklistitems tr.checklist-row:not(.checklist-template):not(.checklist-deleted)").forEach(function(row) {
								SetChecklistInputNames(row, rownum);
								rownum++;
							});
							document.querySelectorAll("#checklistitems tr.checklist-row.checklist-deleted:not(.checklist-template)").forEach(function(row) {
								SetChecklistInputNames(row, rownum);
								rownum++;
							});
						}

						/* Clone the hidden template, make its required fields active, then initialize Select2. */
						function AddChecklistRow() {
							var template = document.getElementById("checklistrowtemplate");
							var row = template.cloneNode(true);
							row.removeAttribute("id");
							row.classList.remove("checklist-template");
							row.style.display = "";
							ResetChecklistDropdowns(row);
							row.querySelector(".checklist-itemname").required = true;
							row.querySelector(".checklist-itemtype").required = true;
							document.getElementById("checklistitems").appendChild(row);
							RenumberChecklistRows();
							InitializeChecklistDropdowns(row);
							InitMappedName(row);
							UpdateChecklistRowRequirements(row);
							row.querySelector(".checklist-itemname").focus();
						}

						/* New unsaved rows can be removed; existing DB rows are hidden and marked for deletion. */
						function DeleteChecklistRow(button) {
							var row = button.closest("tr");
							if (row.querySelector(".checklist-itemid").value == "") {
								row.remove();
							}
							else {
								row.classList.add("checklist-deleted");
								row.style.display = "none";
								row.querySelector(".checklist-deleteitem").value = "1";
							}
							RenumberChecklistRows();
						}

						/* Cloned rows can inherit Select2 metadata from the template; strip it before reinitializing. */
						function ResetChecklistDropdowns(context) {
							jQuery(context).find(".select2-container").remove();
							jQuery(context).find("select.checklist-select").add(jQuery(context).filter("select.checklist-select")).each(function() {
								jQuery(this)
									.removeClass("select2-hidden-accessible")
									.removeAttr("data-select2-id")
									.removeAttr("aria-hidden")
									.removeAttr("tabindex");
								jQuery(this).find("option").removeAttr("data-select2-id");
							});
						}

						/* Initialize Select2 only for real checklist rows, never the hidden template. */
						function InitializeChecklistDropdowns(context) {
							if (!window.jQuery || !jQuery.fn.select2) {
								return;
							}

							jQuery(context).find("tr.checklist-template select.checklist-select").add(jQuery(context).filter("tr.checklist-template select.checklist-select")).each(function() {
								if (jQuery(this).hasClass("select2-hidden-accessible")) {
									jQuery(this).select2("destroy");
								}
							});

							jQuery(context).find("select.checklist-select").add(jQuery(context).filter("select.checklist-select")).filter(function() {
								return jQuery(this).closest("tr.checklist-template").length == 0;
							}).each(function() {
								if (jQuery(this).hasClass("select2-hidden-accessible")) {
									return;
								}

								jQuery(this).select2({
									minimumResultsForSearch: Infinity,
									width: "style"
								});
							});
						}

						/* Show/hide modality, mappedname, and instrument cells based on the selected type. */
						function UpdateChecklistRowRequirements(row) {
							var typeVal          = row.querySelector("select.checklist-itemtype").value;
							var modalitySelect   = row.querySelector("select.checklist-modality");
							var instrumentSelect = row.querySelector("select.checklist-instrument");
							var modalityDiv      = row.querySelector(".checklist-modality-container");
							var instrumentDiv    = row.querySelector(".checklist-instrument-container");
							var isImaging        = (typeVal === "Imaging");
							var isInstrument     = (typeVal === "Instrument");
							modalitySelect.required   = isImaging;
							instrumentSelect.required = isInstrument;
							/* mapped name is a Select2-managed (hidden) multi-select, so HTML5 'required' is not enforced on it */
							modalityDiv.style.display   = isImaging    ? "" : "none";
							instrumentDiv.style.display = isInstrument ? "" : "none";
						}

						/* Existing rows are present on page load and need Select2 setup once. */
						jQuery(document).ready(function() {
							InitializeChecklistDropdowns(document);
							document.querySelectorAll("#checklistitems tr.checklist-row:not(.checklist-template)").forEach(function(row) {
								UpdateChecklistRowRequirements(row);
								InitMappedName(row);
							});
							jQuery("#checklistitems").on("change", "select.checklist-itemtype", function() {
								UpdateChecklistRowRequirements(this.closest("tr"));
							});
							/* repopulate mapped-name suggestions when a row's modality changes */
							jQuery("#checklistitems").on("change", "select.checklist-modality", function() {
								InitMappedName(this.closest("tr"));
							});
						});
					</script>

					<table class="ui small very compact selectable table">
						<thead>
							<tr>
								<th>Order</th>
								<th class="checklist-name-column">Name</th>
								<th>Type</th>
								<th>Modality</th>
								<th>Mapped name<br><span class="tiny">comma separated for multiple protocol or variable names</span></th>
								<th>Instrument</th>
								<th class="checklist-count-column">Expected count</th>
								<th></th>
							</tr>
						</thead>
						<tbody id="checklistitems">
							<tr id="checklistrowtemplate" class="checklist-row checklist-template" style="display: none">
								<td class="center aligned"><span class="checklist-itemorder-display"></span></td>
								<td>
									<input type="hidden" class="checklist-itemid" value="">
									<input type="hidden" class="checklist-deleteitem" value="0">
									<div class="ui fluid input">
										<input type="text" class="checklist-itemname" value="">
									</div>
								</td>
								<td>
									<select class="checklist-select checklist-itemtype">
										<option value="">Select item type...</option>
										<option value="Checkbox">Checkbox</option>
										<option value="Imaging">Imaging</option>
										<option value="Intervention">Intervention</option>
										<option value="Observation">Observation</option>
										<option value="Diagnosis">Diagnosis</option>
										<option value="Instrument">Instrument</option>
									</select>
								</td>
								<td>
									<div class="checklist-modality-container" style="display:none">
										<select class="checklist-select checklist-modality">
											<?=$modalityoptions?>
										</select>
									</div>
								</td>
								<td><select class="checklist-mappedname" multiple></select></td>
								<td>
									<div class="checklist-instrument-container" style="display:none">
										<select class="checklist-select checklist-instrument">
											<?=$instrumentoptions?>
										</select>
									</div>
								</td>
								<td><div class="ui input checklist-count-input"><input type="number" class="checklist-expectedcount" min="1" value=""></div></td>
								<td><button type="button" class="ui red icon button" onClick="DeleteChecklistRow(this)" title="Delete checklist item"><i class="trash alternate icon"></i></button></td>
							</tr>
							<?
								$itemorder = 1;
								$stmt = mysqli_prepare($GLOBALS['linki'], "select * from project_checklist where project_id = ? order by item_order asc, projectchecklist_id asc");
								mysqli_stmt_bind_param($stmt, 'i', $projectid);
								$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$checklistrowid  = $row['projectchecklist_id'];
									$itemname        = htmlspecialchars($row['item_name'], ENT_QUOTES);
									$itemtype        = $row['item_type'];
									$mappednamevals  = array_filter(array_map('trim', explode(',', $row['mapped_name'] ?? "")), function($v) { return $v !== ""; });
									$expectedcount   = htmlspecialchars($row['expected_count'] ?? "", ENT_QUOTES);
									$rowinstrumentid = (int)($row['instrument_id'] ?? 0);
									$rawmodality     = $row['imaging_modality'] ?? "";
								?>
									<tr class="checklist-row">
										<td class="center aligned"><span class="checklist-itemorder-display"><?=$itemorder?></span></td>
										<td>
											<input type="hidden" class="checklist-itemid" name="itemid[<?=$itemorder?>]" value="<?=$checklistrowid?>">
											<input type="hidden" class="checklist-deleteitem" name="deleteitem[<?=$itemorder?>]" value="0">
											<div class="ui fluid input">
												<input type="text" class="checklist-itemname" name="itemname[<?=$itemorder?>]" value="<?=$itemname?>" required>
											</div>
										</td>
										<td>
											<select name="itemtype[<?=$itemorder?>]" class="checklist-select checklist-itemtype" required>
												<option value="">Select item type...</option>
												<option value="Checkbox" <? if ($itemtype == "Checkbox") { echo "selected"; } ?>>Checkbox</option>
												<option value="Imaging" <? if ($itemtype == "Imaging") { echo "selected"; } ?>>Imaging</option>
												<option value="Intervention" <? if ($itemtype == "Intervention") { echo "selected"; } ?>>Intervention</option>
												<option value="Observation" <? if ($itemtype == "Observation") { echo "selected"; } ?>>Observation</option>
												<option value="Diagnosis" <? if ($itemtype == "Diagnosis") { echo "selected"; } ?>>Diagnosis</option>
												<option value="Instrument" <? if ($itemtype == "Instrument") { echo "selected"; } ?>>Instrument</option>
											</select>
										</td>
										<td>
											<div class="checklist-modality-container" <? if ($itemtype != "Imaging") { echo 'style="display:none"'; } ?>>
												<select name="modality[<?=$itemorder?>]" class="checklist-select checklist-modality">
													<option value=""></option>
													<? foreach ($modalitycodes as $mc) {
														$esc = htmlspecialchars($mc, ENT_QUOTES);
														$sel = (strcasecmp($mc, $rawmodality) === 0) ? " selected" : "";
														echo "<option value=\"$esc\"$sel>$esc</option>";
													} ?>
												</select>
											</div>
										</td>
										<td>
											<select class="checklist-mappedname" name="mappedname[<?=$itemorder?>][]" multiple>
												<? foreach ($mappednamevals as $mv) { $mvesc = htmlspecialchars($mv, ENT_QUOTES); ?><option value="<?=$mvesc?>" selected><?=$mvesc?></option><? } ?>
											</select>
										</td>
										<td>
											<div class="checklist-instrument-container" <? if ($itemtype != "Instrument") { echo 'style="display:none"'; } ?>>
												<select name="instrumentid[<?=$itemorder?>]" class="checklist-select checklist-instrument">
													<option value="">Select instrument...</option>
													<? foreach ($instruments as $iid => $iname) {
														$sel = ($iid === $rowinstrumentid && $rowinstrumentid > 0) ? " selected" : "";
														echo "<option value=\"$iid\"$sel>" . htmlspecialchars($iname, ENT_QUOTES) . "</option>";
													} ?>
												</select>
											</div>
										</td>
										<td><div class="ui input checklist-count-input"><input type="number" class="checklist-expectedcount" name="expectedcount[<?=$itemorder?>]" min="1" value="<?=$expectedcount?>"></div></td>
										<td><button type="button" class="ui red icon button" onClick="DeleteChecklistRow(this)" title="Delete checklist item"><i class="trash alternate icon"></i></button></td>
									</tr>
									<?
									$itemorder++;
								}
								mysqli_stmt_close($stmt);
							?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="8" align="right" style="padding-right: 20px">
									<button class="ui button" type="button" onClick="AddChecklistRow()"><i class="plus square outline icon"></i> Add checklist item</button>
									<input class="ui primary button" type="submit" value="Update">
								</td>
							</tr>
						</tfoot>
					</table>
				</form>
			</div>
		</div>
		<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayObservations ---------------- */
	/* -------------------------------------------- */
	/* Observation checklist matrix (subjects x observation names, counts), ported from
	   projects.php action=displaynonimaging. Scoped to regular (non-image, non-timeseries)
	   observations; shows all active subjects; honors the enrollment filter. Full page width. */
	function DisplayObservations($projectid) {
		global $enrollstatus;

		if ($projectid < 1) { DisplayProjectPicker(); return; }
		$enrollFilter = EnrollStatusSQL($enrollstatus, 'b');
		$projectname  = GetProjectName($projectid);

		/* all active subjects (rows) */
		$subjects = [];
		$sqlstring = "select c.uid, c.subject_id, min(b.enrollment_id) enrollment_id
			from enrollment b
			join subjects c on b.subject_id = c.subject_id
			where b.project_id = ? and c.isactive = 1 $enrollFilter
			group by c.subject_id, c.uid
			order by c.uid";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { $subjects[] = $row; }
		mysqli_stmt_close($stmt);

		/* per-subject counts of each regular observation name */
		$obsBySubject     = array();   /* subject_id => [name => count] */
		$observationNames = array();
		$numObservations  = 0;
		$sqlstring = "select c.subject_id, a.observation_name, count(*) cnt
			from observations a
			join enrollment b on a.enrollment_id = b.enrollment_id
			join subjects c on b.subject_id = c.subject_id
			left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			where b.project_id = ? and c.isactive = 1 $enrollFilter
			  and (ii.item_type is null or ii.item_type not in ('image','timeseries'))
			  and a.observation_name not in ('uid','subjectRowID','enrollmentRowID')
			group by c.subject_id, a.observation_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$sid  = (int)$row['subject_id'];
			$name = $row['observation_name'];
			$cnt  = (int)$row['cnt'];
			if (trim($name) === "") continue;
			$obsBySubject[$sid][$name] = $cnt;
			$observationNames[$name]   = true;
			$numObservations          += $cnt;
		}
		mysqli_stmt_close($stmt);

		$observationNames = array_keys($observationNames);
		natcasesort($observationNames);
		$observationNames = array_values($observationNames);

		$numSubjects         = count($subjects);
		$numObservationNames = count($observationNames);

		/* coverage: number of subjects that have >=1 of each observation, and build row data */
		$stats = array();
		foreach ($observationNames as $name) { $stats[$name] = 0; }
		$rowdata = array();
		foreach ($subjects as $s) {
			$sid = (int)$s['subject_id'];
			$r = array('uid' => $s['uid'], 'subjectRowID' => $sid, 'enrollmentRowID' => (int)$s['enrollment_id']);
			foreach ($observationNames as $name) {
				$c = $obsBySubject[$sid][$name] ?? 0;
				if ($c > 0) $stats[$name]++;
				$r[$name] = $c;
			}
			$rowdata[] = $r;
		}

		/* column metadata (name + coverage % header) for the JS to build columnDefs */
		$obscols = array();
		foreach ($observationNames as $name) {
			$percent = ($numSubjects > 0) ? number_format(($stats[$name] / $numSubjects) * 100.0, 0) : 0;
			$obscols[] = array('field' => (string)$name, 'header' => "$name ($percent%)");
		}

		$rowData    = json_encode($rowdata, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$colMeta    = json_encode($obscols, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<style>#observationsgrid { width: 100%; height: 64vh; }</style>

		<div style="padding: 0 14px">
			<? DisplayHeaderBar($projectid, $projectname, 'Observations'); ?>

			<div class="ui bottom attached segment">
				<div class="ui two column grid" style="margin-bottom:2px">
					<div class="column">
						<b><?=number_format($numObservations)?></b> observation values &nbsp;&middot;&nbsp;
						<b><?=number_format($numObservationNames)?></b> observation names &nbsp;&middot;&nbsp;
						<b><?=number_format($numSubjects)?></b> subjects
					</div>
					<div class="right aligned column">
						<button class="ui small basic primary compact button" onClick="observationsExportCsv()"><i class="file excel outline icon"></i> Export as .csv</button>
					</div>
				</div>

				<div id="observationsgrid"></div>
				<div style="color:#888; font-size:0.85em; margin-top:4px">Displaying <span id="observationsrowcount">0</span> rows</div>
			</div>
		</div>

		<script>
			const observationsRowData = <?=$rowData?>;
			const observationsColMeta = <?=$colMeta?>;
			let observationsGridApi;

			function observationsCellStyle(params) {
				const v = params.value;
				if (v == null || v === '' || v < 1) return { backgroundColor: '#ffffff' };
				if (v == 1) return { backgroundColor: 'rgb(200, 255, 200)' };
				return { backgroundColor: 'rgb(128, 255, 128)' };
			}
			function observationsExportCsv() { if (observationsGridApi) observationsGridApi.exportDataAsCsv({ allColumns: false }); }
			function updateObservationsRowCount() {
				if (observationsGridApi) document.getElementById('observationsrowcount').textContent = observationsGridApi.getDisplayedRowCount();
			}

			const observationsColumnDefs = [
				{ field: 'subjectRowID', hide: true },
				{ field: 'enrollmentRowID', hide: true },
				{
					headerName: 'UID', field: 'uid', pinned: 'left', width: 150,
					cellRenderer: function(params) { return '<a href="subjects.php?id=' + params.data.subjectRowID + '">' + (params.value == null ? '' : params.value) + '</a>'; },
					headerTooltip: 'Click to go to the subject'
				}
			];
			observationsColMeta.forEach(function(c) {
				/* use colId + valueGetter (not field) so observation names containing dots are read
				   as a flat key rather than an ag-grid nested path */
				observationsColumnDefs.push({
					colId:       c.field,
					headerName:  c.header,
					valueGetter: function(params) { return params.data ? params.data[c.field] : null; },
					cellStyle:   observationsCellStyle
				});
			});

			const observationsTheme = agGrid.themeBalham.withParams({
				headerTextColor:       'white',
				headerBackgroundColor: '#00b5ad',
				headerFontSize:        '14px',
				columnBorder:          { style: 'solid', color: '#ddd' },
			});
			const observationsGridOptions = {
				theme:                  observationsTheme,
				columnDefs:             observationsColumnDefs,
				rowData:                observationsRowData,
				defaultColDef:          { sortable: true, filter: true, resizable: true },
				rowSelection:           { mode: 'multiRow' },
				animateRows:            false,
				suppressMovableColumns: true,
				onFirstDataRendered:    updateObservationsRowCount,
				onFilterChanged:        updateObservationsRowCount,
				onModelUpdated:         updateObservationsRowCount
			};

			$(document).ready(function() {
				observationsGridApi = agGrid.createGrid(document.getElementById('observationsgrid'), observationsGridOptions);
				updateObservationsRowCount();
			});
		</script>
		<?
	}

require "footer.php";
