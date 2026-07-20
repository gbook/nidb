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

	/* the data types displayed on this page. Each drives a summary segment and (later) a detail view */
	$checklistTypes = [
		'imaging'               => ['label' => 'Imaging',               'icon' => 'photo icon',          'color' => 'blue',   'hex' => '#2185d0', 'desc' => 'MR, EEG, ET, and other imaging studies'],
		'observations'          => ['label' => 'Observations',          'icon' => 'clipboard list icon', 'color' => 'teal',   'hex' => '#00b5ad', 'desc' => 'Assessment and instrument observations'],
		'interventions'         => ['label' => 'Interventions',         'icon' => 'pills icon',          'color' => 'violet', 'hex' => '#6435c9', 'desc' => 'Drugs, dosing, and other interventions'],
		'observationimages'     => ['label' => 'Observation images',    'icon' => 'image outline icon',  'color' => 'orange', 'hex' => '#f2711c', 'desc' => 'Observations with an associated image/file'],
		'observationtimeseries' => ['label' => 'Observation timeseries','icon' => 'chart line icon',     'color' => 'green',  'hex' => '#21ba45', 'desc' => 'Observations with continuous timeseries data'],
	];

	switch ($action) {
		case 'observationimages':
			DisplayObservationImages($projectid);
			break;
		case 'observationtimeseries':
			DisplayObservationTimeseries($projectid);
			break;
		case 'imaging':
		case 'observations':
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
	function GetChecklistSummary($projectid) {
		$s = [];

		/* Imaging: subjects with at least one study, and total studies */
		$s['imaging'] = ChecklistCountPair(
			"select count(distinct c.subject_id) subjects, count(a.study_id) records
			 from studies a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 where b.project_id = ? and c.isactive = 1", $projectid);

		/* Observations: regular (non-image, non-timeseries) instrument/assessment observations */
		$s['observations'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.observation_id) records
			 from observations a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			 where b.project_id = ? and c.isactive = 1
			   and (ii.item_type is null or ii.item_type not in ('image','timeseries'))", $projectid);

		/* Interventions: subjects with at least one intervention, and total interventions */
		$s['interventions'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.intervention_id) records
			 from interventions a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 where b.project_id = ? and c.isactive = 1", $projectid);

		/* Observation images: observations whose instrument item is of type 'image' */
		$s['observationimages'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.observation_id) records
			 from observations a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			 where b.project_id = ? and c.isactive = 1 and ii.item_type = 'image' and a.observation_fileid > 0", $projectid);

		/* Observation timeseries: observations whose instrument item is of type 'timeseries' */
		$s['observationtimeseries'] = ChecklistCountPair(
			"select count(distinct b.subject_id) subjects, count(a.observation_id) records
			 from observations a
			 left join enrollment b on a.enrollment_id = b.enrollment_id
			 left join subjects c on b.subject_id = c.subject_id
			 left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
			 where b.project_id = ? and c.isactive = 1 and ii.item_type = 'timeseries'", $projectid);

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
				<h2 class="ui header"><i class="tasks icon"></i>
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
		?>
		<div class="ui top attached secondary inverted segment">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header" style="color:white">
						<i class="tasks icon"></i>
						<div class="content">Data Checklist &mdash; <?=htmlspecialchars($projectname)?>
							<? if ($subtitle != "") { ?><div class="sub header" style="color:#ccc"><?=htmlspecialchars($subtitle)?></div><? } ?>
						</div>
					</h2>
				</div>
				<div class="right aligned column">
					<a href="projects.php?action=displayprojectinfo&id=<?=$projectid?>" class="ui basic inverted button"><i class="arrow left icon"></i> Project</a>
				</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySummary --------------------- */
	/* -------------------------------------------- */
	function DisplaySummary($projectid) {
		global $checklistTypes;

		if ($projectid < 1) { DisplayProjectPicker(); return; }

		$projectname = GetProjectName($projectid);

		/* total active subjects in the project (denominator for coverage) */
		$totalSubjects = ChecklistCountPair(
			"select count(distinct c.subject_id) subjects, 0 records
			 from enrollment b left join subjects c on b.subject_id = c.subject_id
			 where b.project_id = ? and c.isactive = 1", $projectid)['subjects'];

		$summary = GetChecklistSummary($projectid);
		?>
		<div class="ui container">
			<? DisplayHeaderBar($projectid, $projectname); ?>

			<div class="ui bottom attached segment">
				<p style="color:#666; margin-bottom:2px"><b><?=number_format($totalSubjects)?></b> active subjects in this project</p>

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

									<a href="checklist.php?action=<?=$key?>&projectid=<?=$projectid?>">View details <i class="arrow right icon"></i></a>
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
				<a href="checklist.php?projectid=<?=$projectid?>" class="ui basic button"><i class="arrow left icon"></i> Back to checklist</a>

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
		global $checklistTypes;

		if ($projectid < 1) { DisplayProjectPicker(); return; }

		$projectname = GetProjectName($projectid);
		$info = $checklistTypes['observationimages'];

		/* determine which date to show. Default to the most recent date that has image data. */
		$date = GetVariable("date");
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			$sqlstring = "select max(date(a.observation_startdate)) maxd
				from observations a
				left join enrollment b on a.enrollment_id = b.enrollment_id
				left join subjects c on b.subject_id = c.subject_id
				left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id
				where b.project_id = ? and c.isactive = 1 and a.observation_fileid > 0 and ii.item_type = 'image'";
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
			where b.project_id = ? and c.isactive = 1
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
			where b.project_id = ? and c.isactive = 1 and a.observation_fileid > 0
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
				<a href="checklist.php?projectid=<?=$projectid?>" class="ui basic button"><i class="arrow left icon"></i> Back to checklist</a>

				<!-- date navigator -->
				<div class="ui center aligned basic segment" style="margin-top:8px">
					<div class="ui buttons">
						<a href="checklist.php?action=observationimages&projectid=<?=$projectid?>&date=<?=$prevDate?>" class="ui labeled icon button"><i class="left arrow icon"></i> <?=date('M j', strtotime($prevDate))?></a>
						<div class="ui active button" style="min-width:16em"><i class="calendar outline icon"></i> <?=$dateLabel?></div>
						<a href="checklist.php?action=observationimages&projectid=<?=$projectid?>&date=<?=$nextDate?>" class="ui right labeled icon button"><?=date('M j', strtotime($nextDate))?> <i class="right arrow icon"></i></a>
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
		global $checklistTypes;

		if ($projectid < 1) { DisplayProjectPicker(); return; }

		$projectname = GetProjectName($projectid);
		$info = $checklistTypes['observationtimeseries'];

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
			where b.project_id = ? and c.isactive = 1
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
				<a href="checklist.php?projectid=<?=$projectid?>" class="ui basic button"><i class="arrow left icon"></i> Back to checklist</a>

				<? if (count($items) == 0) { ?>
					<div class="ui info message" style="margin-top:12px"><i class="info circle icon"></i> This project has no timeseries observations.</div>
				<? } else { ?>
					<!-- item + timeframe selectors -->
					<form method="get" action="checklist.php" class="ui form" style="margin-top:12px">
						<input type="hidden" name="action" value="observationtimeseries">
						<input type="hidden" name="projectid" value="<?=$projectid?>">
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

require "footer.php";
?>
