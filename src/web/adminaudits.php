<?
 // ------------------------------------------------------------------------------
 // NiDB adminaudits.php
 // Copyright (C) 2004 - 2026
 // Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
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

	/* Audits are requested here (a row in `audits` with status 'started') and carried out by the
	   nidb audit module (moduleAudit.cpp), which updates the audit's status/progress and writes
	   any issues it finds to `audit_log` */

	define("LEGIT_REQUEST", true);

	session_start();
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) and the JSON status action work despite the HTML rendered below */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Audits</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";

	/* audit types, and their descriptions. Keys must match the audits.audit_type enum */
	$audittypes = array(
		'seriesfiles' => 'Series files (MR only) - compare the data, beh, and QC/QA file counts and sizes of each MR series in the database against the files in the archive',
		'analysisfiles' => 'Analysis files - compare the file count and size of each analysis in the database against the files in the analysis directory',
		'qualitycontrol' => 'Quality control (single project only) - find duplicate, empty, incomplete, or inconsistent subjects, studies, and series. Issues are reported only, except for the optional missing demographics fixes'
	);

	/* quality control checks, and their descriptions. Keys must match the audits.audit_options set. The criteria are listed in doc/audits.md */
	$qcchecklist = array(
		'duplicatesubjectsproject' => 'Duplicate subjects - same or similar name, DOB, sex (including transposed first/last name), or an alternate ID shared within this project',
		'duplicatesubjects' => 'Duplicate subjects, comparing alternate IDs from all projects - the same as above, but also compares the alternate IDs from the subjects\' enrollments in other projects. Projects that number their subjects 1, 2, 3... will cause many false positives',
		'duplicatestudies' => 'Duplicate studies - the same study under two or more subjects (same StudyInstanceUID, or same modality and date/time)',
		'empty' => 'Empty subjects, enrollments, and studies - no series, studies, observations, or interventions',
		'missingdemographics' => 'Missing demographics - no name, sex, or DOB',
		'invaliddob' => 'Invalid DOB - placeholder dates (1900-01-01, 1776-07-04, ...), impossible dates, in the future, or after the first study. YYYY and YYYY-MM DOBs are valid',
		'dicommetadata' => 'DICOM metadata - every DICOM file\'s PatientID, StudyDate/StudyTime, and SeriesNumber match its subject, study, and series (slow, reads every file)'
	);

	/* fixes for the missing demographics check, shown under it. Keys must match the audits.audit_options set. Never selected by default */
	$qcfixlist = array(
		'fixsex' => 'Fix missing sex/gender - fill in a blank sex or gender with the other, if it is M, F, O, or U',
		'fixname' => 'Fix missing name - set the name to the primary alternate ID of the subject\'s enrollment in this project, otherwise the UID',
		'fixdob' => 'Fix missing DOB - estimate the year and month (YYYY-MM-00) from the age at scan and date of the subject\'s earliest study that has both. Left blank if there is no such study'
	);

	/* quality control checks that are not selected by default */
	$qcchecksoffbydefault = array('duplicatesubjects');

	/* statuses where the audit module has not finished with the audit */
	$activestatuses = array('started', 'auditing', 'finishing');

	$action = GetVariable("action");

	/* JSON status poll, used by the audit list to update progress. Handled before any page chrome is rendered */
	if ($action == "auditstatus") {
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: application/json');
		if (!isSiteAdmin()) {
			http_response_code(403);
			echo json_encode(array('error' => 'Not authorized'));
		}
		else {
			echo json_encode(GetAuditStatus());
		}
		exit;
	}

	/* details of an issue that involves more than one object (ie, a group of suspected duplicate
	   subjects), as an HTML fragment for the issue modal. Handled before any page chrome is rendered */
	if ($action == "issuedetails") {
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: text/html; charset=utf-8');
		if (!isSiteAdmin()) {
			http_response_code(403);
			echo "Not authorized";
		}
		else {
			DisplayIssueDetails((int)GetVariable("auditlogid"));
		}
		exit;
	}

	require "includes_html.php";
	require "menu.php";

	if (!isSiteAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$auditid = (int)GetVariable("auditid");
		$projectid = (int)GetVariable("projectid");
		$audittype = GetVariable("audittype");
		$fixerrors = GetVariable("fixerrors");
		$qcchecks = GetVariable("qcchecks");
		$qcfixes = GetVariable("qcfixes");
		$issuetype = GetVariable("issuetype");
		$page = GetVariable("page");

		/* determine action */
		switch ($action) {
			/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
			   then redirect to a GET so a refresh/Back doesn't re-submit the form */
			case 'startaudit':
				ob_start();
				StartAudit($projectid, $audittype, $fixerrors, $qcchecks, $qcfixes);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminaudits.php");
				break;
			case 'stopaudit':
				ob_start();
				StopAudit($auditid);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminaudits.php");
				break;
			case 'deleteaudit':
				ob_start();
				DeleteAudit($auditid);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminaudits.php");
				break;
			case 'viewaudit':
				DisplayAudit($auditid, $issuetype, $page);
				break;
			default:
				DisplayAuditList();
		}
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- StartAudit ------------------------- */
	/* -------------------------------------------- */
	function StartAudit($projectid, $audittype, $fixerrors, $qcchecks, $qcfixes) {
		$projectid = (int)$projectid;
		$fixerrors = ($fixerrors == "1") ? 1 : 0;
		$options = null;

		if (!array_key_exists($audittype ?? '', $GLOBALS['audittypes'])) {
			Error("Invalid audit type [" . htmlspecialchars($audittype ?? '') . "]");
			return;
		}

		/* quality control audits run on a single project and need at least one check. They only report
		   issues, except for the missing demographics fixes, which are stored in audit_options with the checks */
		if ($audittype == "qualitycontrol") {
			$fixerrors = 0;
			if ($projectid == 0) {
				Error("Quality control audits can only be run on a single project. Select a project");
				return;
			}
			if (!is_array($qcchecks))
				$qcchecks = array();
			$checks = array_values(array_intersect(array_keys($GLOBALS['qcchecklist']), $qcchecks));
			if (count($checks) < 1) {
				Error("Select at least one quality control check");
				return;
			}
			if (in_array('missingdemographics', $checks)) {
				if (!is_array($qcfixes))
					$qcfixes = array();
				$checks = array_merge($checks, array_values(array_intersect(array_keys($GLOBALS['qcfixlist']), $qcfixes)));
			}
			$options = implode(",", $checks);
		}

		/* project 0 is a systemwide audit, otherwise the project must exist */
		if ($projectid != 0) {
			$sqlstring = "select project_id from projects where project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
			if (mysqli_num_rows($result) < 1) {
				Error("Project [$projectid] not found");
				return;
			}
		}

		/* don't queue a duplicate of an audit that is already waiting or running */
		$sqlstring = "select audit_id from audits where project_id = ? and audit_type = ? and audit_status in ('started','auditing','finishing')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $audittype);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $audittype]);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			Warning("An identical audit (#" . (int)$row['audit_id'] . ") is already queued or running. A new audit was not started.");
			return;
		}

		$message = "Audit requested by " . $GLOBALS['username'];
		$sqlstring = "insert into audits (project_id, audit_type, audit_fixerrors, audit_options, audit_status, audit_message, audit_objectprogress, audit_totalobjects, audit_startdate) values (?, ?, ?, ?, 'started', ?, 0, 0, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isiss', $projectid, $audittype, $fixerrors, $options, $message);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $audittype, $fixerrors, $options, $message]);
		$newid = mysqli_insert_id($GLOBALS['linki']);

		Notice("Audit #$newid queued. It will begin the next time the audit module runs.", "Audit started");
	}


	/* -------------------------------------------- */
	/* ------- StopAudit -------------------------- */
	/* -------------------------------------------- */
	/* an audit that is waiting to start will never be picked up by the audit module. A running audit
	   is stopped by the module the next time it checks in */
	function StopAudit($auditid) {
		$auditid = (int)$auditid;

		$message = "Stopped by " . $GLOBALS['username'];
		$sqlstring = "update audits set audit_status = 'stopped', audit_message = ?, audit_enddate = now() where audit_id = ? and audit_status in ('started','auditing','finishing')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $message, $auditid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$message, $auditid]);

		if (mysqli_stmt_affected_rows($stmt) < 1) {
			Error("Audit #$auditid was not stopped, because it is not active");
			return;
		}

		Notice("Audit #$auditid stopped", "Audit stopped");
	}


	/* -------------------------------------------- */
	/* ------- DeleteAudit ------------------------ */
	/* -------------------------------------------- */
	function DeleteAudit($auditid) {
		$auditid = (int)$auditid;

		$sqlstring = "select audit_status from audits where audit_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid]);
		if (mysqli_num_rows($result) < 1) {
			Error("Audit [$auditid] not found");
			return;
		}
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

		/* the audit module is working on it, so pulling the rows out from under it isn't safe */
		if (in_array($row['audit_status'], array('auditing', 'finishing'))) {
			Error("Audit #$auditid is currently running and cannot be deleted");
			return;
		}

		MySQLiQuery("start transaction", __FILE__, __LINE__);

		$sqlstring = "delete o from audit_log_objects o inner join audit_log l on o.auditlog_id = l.auditlog_id where l.audit_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid]);

		$sqlstring = "delete from audit_log where audit_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid]);

		$sqlstring = "delete from audits where audit_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid]);

		MySQLiQuery("commit", __FILE__, __LINE__);

		Notice("Audit #$auditid and its log deleted", "Audit deleted");
	}


	/* -------------------------------------------- */
	/* ------- GetAuditStatus --------------------- */
	/* -------------------------------------------- */
	/* current status of every audit. Used by the JSON status poll */
	function GetAuditStatus() {
		$audits = array();
		$sqlstring = "select a.audit_id, a.audit_status, a.audit_message, a.audit_objectprogress, a.audit_totalobjects, a.audit_startdate, a.audit_enddate, a.audit_lastcheckin, (select count(*) from audit_log l where l.audit_id = a.audit_id) 'numissues' from audits a order by a.audit_id desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$audits[] = array(
				'id' => (int)$row['audit_id'],
				'status' => $row['audit_status'],
				'statushtml' => AuditStatusLabel($row['audit_status']),
				'message' => $row['audit_message'] ?? '',
				'progresshtml' => AuditProgressBar($row['audit_status'], $row['audit_objectprogress'], $row['audit_totalobjects']),
				'startdate' => $row['audit_startdate'] ?? '',
				'enddate' => $row['audit_enddate'] ?? '',
				'lastcheckin' => $row['audit_lastcheckin'] ?? '',
				'numissues' => (int)$row['numissues']
			);
		}
		return $audits;
	}


	/* -------------------------------------------- */
	/* ------- AuditStatusLabel ------------------- */
	/* -------------------------------------------- */
	function AuditStatusLabel($status) {
		switch ($status) {
			case 'started': return '<span class="ui grey label"><i class="clock outline icon"></i>Waiting to start</span>';
			case 'auditing': return '<span class="ui blue label"><i class="spinner loading icon"></i>Auditing</span>';
			case 'finishing': return '<span class="ui teal label"><i class="spinner loading icon"></i>Finishing</span>';
			case 'complete': return '<span class="ui green label"><i class="check icon"></i>Complete</span>';
			case 'error': return '<span class="ui red label"><i class="exclamation triangle icon"></i>Error</span>';
			case 'stopped': return '<span class="ui orange label"><i class="stop icon"></i>Stopped</span>';
			default: return '<span class="ui basic label">Unknown</span>';
		}
	}


	/* -------------------------------------------- */
	/* ------- AuditProgressBar ------------------- */
	/* -------------------------------------------- */
	function AuditProgressBar($status, $progress, $total) {
		$progress = (int)($progress ?? 0);
		$total = (int)($total ?? 0);

		/* total isn't known until the module has counted the objects to check */
		if ($total <= 0) {
			if ($status == 'complete')
				return '<span style="color: #888">No objects checked</span>';
			return '<span style="color: #888">-</span>';
		}

		$percent = min(100, (int)floor(($progress / $total) * 100));
		switch ($status) {
			case 'complete': $color = 'green'; break;
			case 'error': $color = 'red'; break;
			case 'stopped': $color = 'orange'; break;
			default: $color = 'blue';
		}

		/* data-percent is required: Fomantic makes the bar transparent without it. The count is rendered
		   below the bar in normal flow, because a .label is absolutely positioned and overflows the cell */
		return '<div style="min-width: 200px"><div class="ui ' . $color . ' progress" data-percent="' . $percent . '" style="margin: 0"><div class="bar" style="width: ' . $percent . '%; min-width: 2.5em"><div class="progress" style="color: #fff">' . $percent . '%</div></div></div><div style="font-size: 0.85em; color: #666; text-align: center; margin-top: 0.2em">' . number_format($progress) . ' of ' . number_format($total) . '</div></div>';
	}


	/* -------------------------------------------- */
	/* ------- DisplayIssueDetails ---------------- */
	/* -------------------------------------------- */
	/* the objects involved in one issue (audit_log_objects), as an HTML fragment for the issue modal.
	   Subjects are shown with their current metadata, so the user can decide which to keep and fix */
	function DisplayIssueDetails($auditlogid) {
		$auditlogid = (int)$auditlogid;

		$sqlstring = "select * from audit_log where auditlog_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditlogid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditlogid]);
		if (mysqli_num_rows($result) < 1) {
			?><div class="ui error message">Issue [<?=$auditlogid?>] not found</div><?
			return;
		}
		$issue = mysqli_fetch_array($result, MYSQLI_ASSOC);

		$members = array();
		$sqlstring = "select object_type, object_id, object_note from audit_log_objects where auditlog_id = ? order by auditlogobject_id";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditlogid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditlogid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$members[] = $row;
		?>
		<p><b><?=htmlspecialchars($issue['issue_type'])?></b> &nbsp; <?=htmlspecialchars($issue['issue_message'] ?? '')?></p>
		<?
		if (count($members) < 1) {
			?><div class="ui message">This issue has no objects listed</div><?
			return;
		}

		/* only subjects have a detailed view. Other objects are listed with their note */
		if ($members[0]['object_type'] != 'subject') {
			?>
			<table class="ui celled compact table">
				<thead><tr><th>Object</th><th>Note</th></tr></thead>
				<? foreach ($members as $m) { ?>
				<tr><td><?=htmlspecialchars($m['object_type'] . ' ' . $m['object_id'])?></td><td><?=nl2br(htmlspecialchars($m['object_note'] ?? ''))?></td></tr>
				<? } ?>
			</table>
			<?
			return;
		}
		?>
		<div style="overflow-x: auto">
		<table class="ui celled compact small table">
			<thead>
				<tr>
					<th>UID</th>
					<th>Name</th>
					<th>DOB</th>
					<th>Sex</th>
					<th>Alternate IDs (project)</th>
					<th>Enrollments</th>
					<th>Studies</th>
					<th>Matched</th>
				</tr>
			</thead>
			<tbody>
			<?
			foreach ($members as $m) {
				$subjectid = (int)$m['object_id'];

				$sqlstring = "select uid, name, birthdate, gender, isactive from subjects where subject_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $subjectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
				if (mysqli_num_rows($result) < 1) {
					?><tr><td colspan="7"><i>Subject [<?=$subjectid?>] no longer exists</i></td><td><?=nl2br(htmlspecialchars($m['object_note'] ?? ''))?></td></tr><?
					continue;
				}
				$subject = mysqli_fetch_array($result, MYSQLI_ASSOC);

				$altuids = array();
				$sqlstring = "select a.altuid, a.isprimary, p.project_name from subject_altuid a left join enrollment c on a.enrollment_id = c.enrollment_id left join projects p on c.project_id = p.project_id where a.subject_id = ? order by a.altuid";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $subjectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
					$altuids[] = $row;

				$enrollments = array();
				$sqlstring = "select c.enrollment_id, c.enroll_subgroup, c.enroll_status, p.project_name, p.project_costcenter, (select count(*) from studies b where b.enrollment_id = c.enrollment_id) 'numstudies' from enrollment c left join projects p on c.project_id = p.project_id where c.subject_id = ? order by p.project_name";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $subjectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
					$enrollments[] = $row;

				$sqlstring = "select count(*) 'numstudies', group_concat(distinct b.study_modality order by b.study_modality separator ', ') 'modalities', min(b.study_datetime) 'firststudy', max(b.study_datetime) 'laststudy' from studies b inner join enrollment c on b.enrollment_id = c.enrollment_id where c.subject_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $subjectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
				$studies = mysqli_fetch_array($result, MYSQLI_ASSOC);
				?>
				<tr>
					<td style="white-space: nowrap"><a href="subjects.php?id=<?=$subjectid?>" target="_blank"><?=htmlspecialchars($subject['uid'] ?? '')?></a><? if (!$subject['isactive']) { ?> <span class="ui mini red label">deleted</span><? } ?></td>
					<td><?=htmlspecialchars($subject['name'] ?? '')?></td>
					<td style="white-space: nowrap"><?=htmlspecialchars($subject['birthdate'] ?? '')?></td>
					<td><?=htmlspecialchars($subject['gender'] ?? '')?></td>
					<td>
						<? foreach ($altuids as $a) { ?>
						<div style="white-space: nowrap"><?=htmlspecialchars($a['altuid'])?><? if ($a['isprimary']) { ?>*<? } ?> <span style="color: #888">(<?=htmlspecialchars($a['project_name'] ?? 'no project')?>)</span></div>
						<? } ?>
					</td>
					<td>
						<? foreach ($enrollments as $e) { ?>
						<div style="white-space: nowrap"><a href="enrollment.php?id=<?=(int)$e['enrollment_id']?>" target="_blank"><?=htmlspecialchars($e['project_name'] ?? '')?></a><?=(($e['project_costcenter'] ?? '') != '') ? ' (' . htmlspecialchars($e['project_costcenter']) . ')' : ''?> <span style="color: #888"><?=htmlspecialchars(trim(($e['enroll_subgroup'] ?? '') . ' ' . ($e['enroll_status'] ?? '')))?>, <?=(int)$e['numstudies']?> studies</span></div>
						<? } ?>
					</td>
					<td style="white-space: nowrap">
						<?=(int)$studies['numstudies']?> <? if ($studies['numstudies'] > 0) { ?>(<?=htmlspecialchars($studies['modalities'] ?? '')?>)<br><span style="color: #888"><?=htmlspecialchars(substr($studies['firststudy'] ?? '', 0, 10))?> to <?=htmlspecialchars(substr($studies['laststudy'] ?? '', 0, 10))?></span><? } ?>
					</td>
					<td style="font-size: 0.9em"><?=nl2br(htmlspecialchars($m['object_note'] ?? ''))?></td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		</div>
		<div style="color: #888; font-size: 0.9em">* primary alternate ID. Values are current, not as of the audit. Duplicates must be merged or fixed manually</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- AuditTypeText ---------------------- */
	/* -------------------------------------------- */
	/* audit type for the audit list. Quality control audits also list their checks */
	function AuditTypeText($type, $options) {
		$html = htmlspecialchars($type ?? '');
		if ($type == 'qualitycontrol') {
			$checks = array_filter(explode(",", $options ?? ''));
			$html .= '<div style="font-size: 0.85em; color: #666">' . htmlspecialchars(implode(", ", $checks)) . '</div>';
		}
		return $html;
	}


	/* -------------------------------------------- */
	/* ------- GetProjectNames -------------------- */
	/* -------------------------------------------- */
	/* project_id => display name for every project */
	function GetProjectNames() {
		$projects = array();
		$sqlstring = "select project_id, project_name, project_costcenter from projects order by project_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$name = $row['project_name'];
			if (($row['project_costcenter'] ?? '') != '')
				$name .= " (" . $row['project_costcenter'] . ")";
			$projects[(int)$row['project_id']] = $name;
		}
		return $projects;
	}


	/* -------------------------------------------- */
	/* ------- AuditProjectName ------------------- */
	/* -------------------------------------------- */
	function AuditProjectName($projectid, $projects) {
		$projectid = (int)$projectid;
		if ($projectid == 0)
			return "<b>All projects</b>";
		if (array_key_exists($projectid, $projects))
			return htmlspecialchars($projects[$projectid]);
		return "<i>Deleted project [$projectid]</i>";
	}


	/* -------------------------------------------- */
	/* ------- DisplayAuditList ------------------- */
	/* -------------------------------------------- */
	function DisplayAuditList() {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		$projects = GetProjectNames();

		/* queued audits only run if the audit module is enabled */
		$sqlstring = "select module_isactive from modules where module_name = 'audit'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		if (($row['module_isactive'] ?? 0) != 1) {
			Warning("The <b>audit</b> module is not enabled, so queued audits will not run. Enable it on the <a href='adminmodules.php'>Modules</a> page.");
		}
		?>
		<div style="padding: 0px 50px">

		<div class="ui container">
		<div class="ui segment">
			<h3 class="ui header">
				<i class="clipboard check icon"></i>
				<div class="content">Start an audit
					<div class="sub header">Audits are run in the background by the audit module. Progress is shown below</div>
				</div>
			</h3>
			<form class="ui form" method="post" action="adminaudits.php" onSubmit="return ValidateAuditForm()">
				<input type="hidden" name="action" value="startaudit">
				<div class="field">
					<label>Audit type</label>
					<select name="audittype" id="audittype" class="ui dropdown" required>
						<? foreach ($GLOBALS['audittypes'] as $type => $desc) { ?>
						<option value="<?=$type?>"><?=htmlspecialchars($desc)?></option>
						<? } ?>
					</select>
				</div>
				<div class="field">
					<label>Project</label>
					<select name="projectid" id="projectid" class="ui search dropdown" required>
						<option value="0">All projects (systemwide)</option>
						<? foreach ($projects as $id => $name) { ?>
						<option value="<?=$id?>"><?=htmlspecialchars($name)?></option>
						<? } ?>
					</select>
				</div>
				<div class="field" id="qcchecksfield" style="display: none">
					<label>Quality control checks &nbsp; <a href="#" onClick="SetQCChecks(true); return false;" style="font-weight: normal">Select all</a> | <a href="#" onClick="SetQCChecks(false); return false;" style="font-weight: normal">Select none</a></label>
					<? foreach ($GLOBALS['qcchecklist'] as $check => $desc) { ?>
					<div class="field" style="margin-bottom: 4px">
						<div class="ui checkbox">
							<input type="checkbox" name="qcchecks[]" value="<?=$check?>" <? if (!in_array($check, $GLOBALS['qcchecksoffbydefault'])) echo "checked"; ?>>
							<label><?=htmlspecialchars($desc)?></label>
						</div>
					</div>
					<? if ($check == 'missingdemographics') { ?>
					<div id="qcfixesfield" style="margin-left: 28px">
						<? foreach ($GLOBALS['qcfixlist'] as $fix => $fixdesc) { ?>
						<div class="field" style="margin-bottom: 4px">
							<div class="ui checkbox">
								<input type="checkbox" name="qcfixes[]" value="<?=$fix?>">
								<label><i class="orange wrench icon"></i><?=htmlspecialchars($fixdesc)?></label>
							</div>
						</div>
						<? } ?>
					</div>
					<? } ?>
					<? } ?>
					<div style="color: #888; font-size: 0.9em; margin-top: 5px">Quality control audits run on a single project, and only report issues. Nothing is changed unless a missing demographics fix is selected: fix the other issues manually</div>
				</div>
				<div class="field" id="fixerrorsfield">
					<div class="ui checkbox">
						<input type="checkbox" name="fixerrors" value="1" id="fixerrors">
						<label>Fix errors</label>
					</div>
					<div style="color: #888; font-size: 0.9em; margin-top: 5px">Update the database to match what is found on disk (file count, size). If unchecked, issues are only reported</div>
				</div>
				<button class="ui primary button" type="submit">Create audit</button>
			</form>
		</div>
		</div>
		<br>

		<?
		$sqlstring = "select a.*, (select count(*) from audit_log l where l.audit_id = a.audit_id) 'numissues' from audits a order by a.audit_id desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$anyactive = false;
		?>
		<div style="display: flex; align-items: center; gap: 12px">
			<h3 class="ui header" style="margin: 0">Audits</h3>
			<span id="pollstatus" style="margin-left: auto; color: #888"></span>
		</div>
		<table class="ui celled selectable compact table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Project</th>
					<th>Type</th>
					<th>Fix errors</th>
					<th>Status</th>
					<th>Progress</th>
					<th>Message</th>
					<th>Started</th>
					<th>Ended</th>
					<th>Last check-in</th>
					<th>Issues</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?
			if (mysqli_num_rows($result) < 1) {
				?><tr><td colspan="12" style="text-align: center; color: #888">No audits have been run</td></tr><?
			}
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$id = (int)$row['audit_id'];
				$status = $row['audit_status'];
				if (in_array($status, $GLOBALS['activestatuses'])) $anyactive = true;
				?>
				<tr data-auditid="<?=$id?>">
					<td><a href="adminaudits.php?action=viewaudit&auditid=<?=$id?>"><?=$id?></a></td>
					<td><?=AuditProjectName($row['project_id'], $projects)?></td>
					<td><?=AuditTypeText($row['audit_type'], $row['audit_options'] ?? '')?></td>
					<td><? if ($row['audit_type'] == 'qualitycontrol') { if (count(array_intersect(explode(",", $row['audit_options'] ?? ''), array_keys($GLOBALS['qcfixlist']))) > 0) { ?><i class="orange wrench icon" title="Missing demographics will be fixed"></i>Demographics<? } else { ?>-<? } } elseif ($row['audit_fixerrors']) { ?><i class="orange wrench icon" title="Errors will be fixed"></i>Yes<? } else { ?>No<? } ?></td>
					<td class="auditstatus"><?=AuditStatusLabel($status)?></td>
					<td class="auditprogress"><?=AuditProgressBar($status, $row['audit_objectprogress'], $row['audit_totalobjects'])?></td>
					<td class="auditmessage"><?=htmlspecialchars($row['audit_message'] ?? '')?></td>
					<td class="auditstartdate" style="white-space: nowrap"><?=htmlspecialchars($row['audit_startdate'] ?? '')?></td>
					<td class="auditenddate" style="white-space: nowrap"><?=htmlspecialchars($row['audit_enddate'] ?? '')?></td>
					<td class="auditlastcheckin" style="white-space: nowrap"><?=htmlspecialchars($row['audit_lastcheckin'] ?? '')?></td>
					<td class="auditissues"><a href="adminaudits.php?action=viewaudit&auditid=<?=$id?>"><?=number_format((int)$row['numissues'])?></a></td>
					<td class="auditactions" style="white-space: nowrap">
						<form class="stopform" method="post" action="adminaudits.php" style="margin: 0; display: <?=(in_array($status, $GLOBALS['activestatuses']) ? 'inline-block' : 'none')?>">
							<input type="hidden" name="action" value="stopaudit">
							<input type="hidden" name="auditid" value="<?=$id?>">
							<button class="ui mini basic orange button" type="submit" title="Stop this audit" onClick="return confirm('Stop audit #<?=$id?>?')"><i class="stop icon"></i>Stop</button>
						</form>
						<form class="deleteform" method="post" action="adminaudits.php" style="margin: 0; display: <?=(in_array($status, array('auditing', 'finishing')) ? 'none' : 'inline-block')?>">
							<input type="hidden" name="action" value="deleteaudit">
							<input type="hidden" name="auditid" value="<?=$id?>">
							<button class="ui mini basic red icon button" type="submit" title="Delete audit and its log" onClick="return confirm('Delete audit #<?=$id?> and its log?')"><i class="trash alternate icon"></i></button>
						</form>
					</td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		</div>

		<script>
			$(document).ready(function() {
				$('.ui.dropdown').dropdown({ fullTextSearch: true });
				$('.ui.checkbox').checkbox();
				$('#audittype').on('change', UpdateAuditForm);
				$('input[name="qcchecks[]"][value="missingdemographics"]').on('change', UpdateQCFixes);
				UpdateAuditForm();
				UpdateQCFixes();
			});

			/* quality control audits choose their checks, and can't fix errors */
			function UpdateAuditForm() {
				const qc = ($('#audittype').val() == 'qualitycontrol');
				$('#qcchecksfield').toggle(qc);
				$('#fixerrorsfield').toggle(!qc);
				if (qc) $('#fixerrors').closest('.checkbox').checkbox('uncheck');
			}

			function SetQCChecks(checked) {
				$('input[name="qcchecks[]"]').closest('.checkbox').checkbox(checked ? 'check' : 'uncheck');
				UpdateQCFixes();
			}

			/* the missing demographics fixes are only available when that check is selected */
			function UpdateQCFixes() {
				const on = $('input[name="qcchecks[]"][value="missingdemographics"]').is(':checked');
				$('input[name="qcfixes[]"]').each(function() {
					const cb = $(this).closest('.checkbox');
					if (!on) cb.checkbox('uncheck');
					cb.checkbox(on ? 'enable' : 'disable');
				});
			}

			function ValidateAuditForm() {
				if ($('#audittype').val() == 'qualitycontrol') {
					if ($('#projectid').val() == '0') {
						alert('Quality control audits can only be run on a single project. Select a project');
						return false;
					}
					if ($('input[name="qcchecks[]"]:checked').length < 1) {
						alert('Select at least one quality control check');
						return false;
					}
					const fixes = $('input[name="qcfixes[]"]:checked').map(function() { return $(this).closest('.checkbox').find('label').text().split(' - ')[0]; }).get();
					if (fixes.length > 0)
						return confirm('The following fixes are selected, and will modify subjects in the database:\n\n' + fixes.join('\n') + '\n\nContinue?');
					return true;
				}
				if (document.getElementById('fixerrors').checked)
					return confirm('Fix errors is checked. The audit will modify the database to match the files on disk. Continue?');
				return true;
			}

			const activeStatuses = <?=json_encode($GLOBALS['activestatuses'])?>;

			function escapeHtml(s) {
				const d = document.createElement('div');
				d.textContent = (s === null || s === undefined) ? '' : String(s);
				return d.innerHTML;
			}

			/* poll the audit status while any audit is waiting or running. Polling stops once every
			   audit has finished, and the page reloads if audits were added or removed elsewhere */
			let pollInFlight = false;
			let pollTimer = null;
			function refreshAudits() {
				if (pollInFlight) return;
				pollInFlight = true;
				fetch('adminaudits.php?action=auditstatus', { cache: 'no-store' })
					.then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
					.then(data => {
						if (!Array.isArray(data)) return;

						/* the set of audits changed, so re-render the whole list */
						const ids = Array.from(document.querySelectorAll('tr[data-auditid]')).map(tr => tr.dataset.auditid).join(',');
						if (ids != data.map(a => a.id).join(',')) { window.location.reload(); return; }

						let anyActive = false;
						data.forEach(a => {
							const tr = document.querySelector('tr[data-auditid="' + a.id + '"]');
							if (activeStatuses.includes(a.status)) anyActive = true;

							tr.querySelector('.auditstatus').innerHTML = a.statushtml;
							tr.querySelector('.auditprogress').innerHTML = a.progresshtml;
							tr.querySelector('.auditmessage').innerHTML = escapeHtml(a.message);
							tr.querySelector('.auditstartdate').innerHTML = escapeHtml(a.startdate);
							tr.querySelector('.auditenddate').innerHTML = escapeHtml(a.enddate);
							tr.querySelector('.auditlastcheckin').innerHTML = escapeHtml(a.lastcheckin);
							tr.querySelector('.auditissues a').textContent = a.numissues.toLocaleString();
							tr.querySelector('.auditactions .stopform').style.display = activeStatuses.includes(a.status) ? 'inline-block' : 'none';
							tr.querySelector('.auditactions .deleteform').style.display = (a.status == 'auditing' || a.status == 'finishing') ? 'none' : 'inline-block';
						});

						document.getElementById('pollstatus').textContent = 'Last refreshed ' + new Date().toLocaleTimeString();
						if (!anyActive) StopPolling();
					})
					.catch(e => console.error('Audit poll error:', e))
					.finally(() => { pollInFlight = false; });
			}

			function StopPolling() {
				if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
			}

			<? if ($anyactive) { ?>
			document.getElementById('pollstatus').textContent = 'Last refreshed ' + new Date().toLocaleTimeString();
			pollTimer = setInterval(refreshAudits, 2000);
			<? } ?>
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAudit ----------------------- */
	/* -------------------------------------------- */
	/* one audit's details and the issues it logged */
	function DisplayAudit($auditid, $issuetype, $page) {
		$auditid = (int)$auditid;
		$page = max(1, (int)$page);
		$perpage = 5000;

		$sqlstring = "select * from audits where audit_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid]);
		if (mysqli_num_rows($result) < 1) {
			Error("Audit [$auditid] not found");
			return;
		}
		$audit = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projects = GetProjectNames();

		/* issue counts by type, also used as the filter menu */
		$issuecounts = array();
		$totalissues = 0;
		$numfixed = 0;
		$sqlstring = "select issue_type, count(*) 'count', sum(issue_fixed = 1) 'numfixed' from audit_log where audit_id = ? group by issue_type order by issue_type";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $auditid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$issuecounts[$row['issue_type']] = (int)$row['count'];
			$totalissues += (int)$row['count'];
			$numfixed += (int)$row['numfixed'];
		}

		/* only filter on a type this audit actually logged */
		if (!array_key_exists($issuetype ?? '', $issuecounts))
			$issuetype = "";
		$numshown = ($issuetype == "") ? $totalissues : $issuecounts[$issuetype];
		$numpages = max(1, (int)ceil($numshown / $perpage));
		$page = min($page, $numpages);
		$offset = ($page - 1) * $perpage;

		if ($issuetype == "") {
			$sqlstring = "select l.*, (select count(*) from audit_log_objects o where o.auditlog_id = l.auditlog_id) 'nummembers' from audit_log l where l.audit_id = ? order by l.auditlog_id limit ? offset ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iii', $auditid, $perpage, $offset);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid, $perpage, $offset]);
		}
		else {
			$sqlstring = "select l.*, (select count(*) from audit_log_objects o where o.auditlog_id = l.auditlog_id) 'nummembers' from audit_log l where l.audit_id = ? and l.issue_type = ? order by l.auditlog_id limit ? offset ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'isii', $auditid, $issuetype, $perpage, $offset);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid, $issuetype, $perpage, $offset]);
		}

		$baseurl = "adminaudits.php?action=viewaudit&auditid=$auditid";
		?>
		<div style="padding: 0px 50px">
		<a href="adminaudits.php"><i class="arrow left icon"></i>Back to audits</a>

		<div class="ui segment">
			<h3 class="ui header">Audit #<?=$auditid?> &nbsp; <?=AuditStatusLabel($audit['audit_status'])?></h3>
			<table class="ui very basic collapsing compact table">
				<tr><td><b>Project</b></td><td><?=AuditProjectName($audit['project_id'], $projects)?></td></tr>
				<tr><td><b>Type</b></td><td><?=htmlspecialchars($GLOBALS['audittypes'][$audit['audit_type']] ?? $audit['audit_type'])?></td></tr>
				<? if ($audit['audit_type'] == 'qualitycontrol') { ?>
				<tr><td><b>Checks</b></td><td>
					<?
					/* the fixes are listed under the missing demographics check */
					$options = array_filter(explode(",", $audit['audit_options'] ?? ''));
					$fixes = array_intersect($options, array_keys($GLOBALS['qcfixlist']));
					foreach (array_diff($options, $fixes) as $check) {
					?>
					<div><i class="check icon"></i><?=htmlspecialchars($GLOBALS['qcchecklist'][$check] ?? $check)?></div>
					<? if ($check == 'missingdemographics') { foreach ($fixes as $fix) { ?>
					<div style="margin-left: 20px"><i class="orange wrench icon"></i><?=htmlspecialchars($GLOBALS['qcfixlist'][$fix])?></div>
					<? } } ?>
					<? } ?>
				</td></tr>
				<? } else { ?>
				<tr><td><b>Fix errors</b></td><td><?=($audit['audit_fixerrors'] ? "Yes" : "No")?></td></tr>
				<? } ?>
				<tr><td><b>Progress</b></td><td><?=AuditProgressBar($audit['audit_status'], $audit['audit_objectprogress'], $audit['audit_totalobjects'])?></td></tr>
				<tr><td><b>Message</b></td><td><?=htmlspecialchars($audit['audit_message'] ?? '')?></td></tr>
				<tr><td><b>Started</b></td><td><?=htmlspecialchars($audit['audit_startdate'] ?? '')?></td></tr>
				<tr><td><b>Ended</b></td><td><?=htmlspecialchars($audit['audit_enddate'] ?? '')?></td></tr>
				<tr><td><b>Last check-in</b></td><td><?=htmlspecialchars($audit['audit_lastcheckin'] ?? '')?></td></tr>
				<tr><td><b>Issues</b></td><td><?=number_format($totalissues)?> found, <?=number_format($numfixed)?> fixed</td></tr>
			</table>
			<? if (in_array($audit['audit_status'], $GLOBALS['activestatuses'])) { ?>
			<a href="<?=$baseurl?>" class="ui small basic button"><i class="sync icon"></i>Refresh</a>
			<? } ?>
		</div>

		<? if ($totalissues > 0) { ?>
		<div class="ui secondary menu">
			<a class="<? if ($issuetype == "") echo "active"; ?> item" href="<?=$baseurl?>">All issues <span class="ui label"><?=number_format($totalissues)?></span></a>
			<? foreach ($issuecounts as $type => $count) { ?>
			<a class="<? if ($issuetype == $type) echo "active"; ?> item" href="<?=$baseurl?>&issuetype=<?=urlencode($type)?>"><?=htmlspecialchars($type)?> <span class="ui label"><?=number_format($count)?></span></a>
			<? } ?>
		</div>
		<? } ?>

		<?
		/* the grid sorts/filters/exports the current page client-side. Paging and the issue type
		   menu stay server-side because a large audit can log one row per object per issue type */
		$rows = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = array(
				'auditlogid' => (int)$row['auditlog_id'],
				'nummembers' => (int)$row['nummembers'],
				'issue' => $row['issue_type'],
				'objecttype' => $row['object_type'] ?? '',
				'objectid' => (int)$row['object_id'],
				'message' => $row['issue_message'] ?? '',
				'fixed' => ($row['issue_fixed'] === null) ? '-' : ($row['issue_fixed'] ? 'Yes' : 'No'),
				'fixmessage' => $row['issue_fixedmessage'] ?? ''
			);
		}
		$rowsJson = json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);
		?>
		<div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
			<input type="text" placeholder="Search..." oninput="auditGridApi.setGridOption('quickFilterText', this.value)" style="padding:5px 8px; width:250px; border:1px solid #ccc; border-radius:4px">
			<span style="color:#888">Displaying <span id="auditCount"><?=count($rows)?></span> issues</span>
			<button class="ui small basic button" style="margin-left:auto" onclick="auditGridApi.exportDataAsCsv({fileName: 'audit<?=$auditid?>.csv'})"><i class="download icon"></i>Export CSV</button>
		</div>
		<div id="auditGrid" style="height:65vh; width:100%"></div>

		<!-- ag-grid v36 uses the Theming API (styles injected by the JS); no CSS import or theme class needed -->
		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@36/dist/ag-grid-community.min.js"></script>
		<script>
		let auditGridApi;
		(function() {
			const rowData = <?=$rowsJson?>;
			const objectPages = { subject: 'subjects.php', enrollment: 'enrollment.php', study: 'studies.php' };

			const columnDefs = [
				{ field: 'issue', headerName: 'Issue', width: 180 },
				{ field: 'objecttype', headerName: 'Object', width: 150,
					valueGetter: function(params) { return params.data.objecttype + ' ' + params.data.objectid; },
					/* sort by type, then numerically by id (a plain string sort puts 'series 10' before 'series 9') */
					comparator: function(a, b, nodeA, nodeB) {
						return (nodeA.data.objecttype || '').localeCompare(nodeB.data.objecttype || '') || (nodeA.data.objectid - nodeB.data.objectid);
					},
					cellRenderer: function(params) {
						const page = objectPages[params.data.objecttype];
						if (!page) return document.createTextNode(params.value);
						const a = document.createElement('a');
						a.href = page + '?id=' + encodeURIComponent(params.data.objectid);
						a.textContent = params.value;
						return a;
					}
				},
				{ field: 'nummembers', headerName: 'Group', width: 120,
					cellRenderer: function(params) {
						if (!params.value) return document.createTextNode('');
						const a = document.createElement('a');
						a.href = '#';
						a.textContent = params.value + ' ' + (params.data.objecttype || 'object') + 's';
						a.onclick = function(e) { e.preventDefault(); ShowIssueDetails(params.data.auditlogid); };
						return a;
					}
				},
				{ field: 'message', headerName: 'Description', flex: 3, tooltipField: 'message' },
				{ field: 'fixed', headerName: 'Fixed', width: 100,
					cellRenderer: function(params) {
						if (params.value === '-') return document.createTextNode('-');
						const span = document.createElement('span');
						const icon = document.createElement('i');
						icon.className = (params.value === 'Yes') ? 'green check icon' : 'red times icon';
						span.appendChild(icon);
						span.appendChild(document.createTextNode(params.value));
						return span;
					}
				},
				{ field: 'fixmessage', headerName: 'Fix description', flex: 2, tooltipField: 'fixmessage' },
			];

			const gridOptions = {
				columnDefs: columnDefs,
				rowData: rowData,
				defaultColDef: { sortable: true, filter: true, resizable: true },
				/* an issue with more than one object (ie, a group of duplicate subjects) opens its details when clicked */
				getRowStyle: function(params) { return (params.data && params.data.nummembers > 0) ? { cursor: 'pointer' } : null; },
				onRowClicked: function(e) {
					if (!e.data || !e.data.nummembers) return;
					if (e.event && e.event.target && e.event.target.closest('a')) return;
					ShowIssueDetails(e.data.auditlogid);
				},
				overlayNoRowsTemplate: '<span style="color: #888">No issues logged</span>',
				/* keep the "Displaying X issues" count in sync with the quick filter / column filters */
				onModelUpdated: function(params) {
					const el = document.getElementById('auditCount');
					if (el) el.textContent = params.api.getDisplayedRowCount().toLocaleString();
				},
			};

			auditGridApi = agGrid.createGrid(document.getElementById('auditGrid'), gridOptions);
		})();

		function ShowIssueDetails(auditlogid) {
			$('#issuemodalcontent').html('<div class="ui active centered inline loader"></div>');
			$('#issuemodal').modal('show');
			fetch('adminaudits.php?action=issuedetails&auditlogid=' + encodeURIComponent(auditlogid), { cache: 'no-store' })
				.then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
				.then(html => { $('#issuemodalcontent').html(html); $('#issuemodal').modal('refresh'); })
				.catch(e => { $('#issuemodalcontent').text('Error loading the issue details: ' + e.message); });
		}
		</script>

		<div class="ui fullscreen modal" id="issuemodal">
			<i class="close icon"></i>
			<div class="header">Issue details</div>
			<div class="scrolling content" id="issuemodalcontent"></div>
			<div class="actions"><div class="ui cancel button">Close</div></div>
		</div>

		<? if ($numpages > 1) {
			$typeparam = ($issuetype == "") ? "" : "&issuetype=" . urlencode($issuetype);
			?>
			<div class="ui pagination menu">
				<? if ($page > 1) { ?><a class="item" href="<?=$baseurl . $typeparam?>&page=<?=$page-1?>"><i class="angle left icon"></i></a><? } ?>
				<div class="item">Page <?=$page?> of <?=$numpages?> &nbsp; (<?=number_format($offset + 1)?> - <?=number_format(min($offset + $perpage, $numshown))?> of <?=number_format($numshown)?>)</div>
				<? if ($page < $numpages) { ?><a class="item" href="<?=$baseurl . $typeparam?>&page=<?=$page+1?>"><i class="angle right icon"></i></a><? } ?>
			</div>
		<? } ?>
		</div>
		<?
	}
?>

<? include("footer.php") ?>
