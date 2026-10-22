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
		'seriesfiles' => 'Series QC files - compare the QC/QA file count and size of each MR series in the database against the files in the archive',
		'analysisfiles' => 'Analysis files - compare the file count and size of each analysis in the database against the files in the analysis directory'
	);

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

	require "includes_html.php";
	require "menu.php";

	if (!isSiteAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$auditid = GetVariable("auditid");
		$projectid = GetVariable("projectid");
		$audittype = GetVariable("audittype");
		$fixerrors = GetVariable("fixerrors");
		$issuetype = GetVariable("issuetype");
		$page = GetVariable("page");

		/* determine action */
		switch ($action) {
			/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
			   then redirect to a GET so a refresh/Back doesn't re-submit the form */
			case 'startaudit':
				ob_start();
				StartAudit($projectid, $audittype, $fixerrors);
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
	function StartAudit($projectid, $audittype, $fixerrors) {
		$projectid = (int)$projectid;
		$fixerrors = ($fixerrors == "1") ? 1 : 0;

		if (!array_key_exists($audittype ?? '', $GLOBALS['audittypes'])) {
			Error("Invalid audit type [" . htmlspecialchars($audittype ?? '') . "]");
			return;
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
		$sqlstring = "insert into audits (project_id, audit_type, audit_fixerrors, audit_status, audit_message, audit_objectprogress, audit_totalobjects, audit_startdate) values (?, ?, ?, 'started', ?, 0, 0, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isis', $projectid, $audittype, $fixerrors, $message);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $audittype, $fixerrors, $message]);
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

		return '<div class="ui small ' . $color . ' progress" style="margin: 0; min-width: 200px"><div class="bar" style="width: ' . $percent . '%; min-width: 2em"><div class="progress">' . $percent . '%</div></div><div class="label" style="font-weight: normal; font-size: 0.85em">' . number_format($progress) . ' of ' . number_format($total) . '</div></div>';
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
			<form class="ui form" method="post" action="adminaudits.php">
				<input type="hidden" name="action" value="startaudit">
				<div class="field">
					<label>Audit type</label>
					<select name="audittype" class="ui dropdown" required>
						<? foreach ($GLOBALS['audittypes'] as $type => $desc) { ?>
						<option value="<?=$type?>"><?=htmlspecialchars($desc)?></option>
						<? } ?>
					</select>
				</div>
				<div class="field">
					<label>Project</label>
					<select name="projectid" class="ui search dropdown" required>
						<option value="0">All projects (systemwide)</option>
						<? foreach ($projects as $id => $name) { ?>
						<option value="<?=$id?>"><?=htmlspecialchars($name)?></option>
						<? } ?>
					</select>
				</div>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="fixerrors" value="1" id="fixerrors">
						<label>Fix errors</label>
					</div>
					<div style="color: #888; font-size: 0.9em; margin-top: 5px">Update the database to match what is found on disk (file count, size). If unchecked, issues are only reported</div>
				</div>
				<button class="ui primary button" type="submit" onClick="if (document.getElementById('fixerrors').checked) { return confirm('Fix errors is checked. The audit will modify the database to match the files on disk. Continue?'); }">Create audit</button>
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
					<td><?=htmlspecialchars($row['audit_type'])?></td>
					<td><? if ($row['audit_fixerrors']) { ?><i class="orange wrench icon" title="Errors will be fixed"></i>Yes<? } else { ?>No<? } ?></td>
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
			});

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
		$perpage = 500;

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
			$sqlstring = "select * from audit_log where audit_id = ? order by auditlog_id limit ? offset ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iii', $auditid, $perpage, $offset);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$auditid, $perpage, $offset]);
		}
		else {
			$sqlstring = "select * from audit_log where audit_id = ? and issue_type = ? order by auditlog_id limit ? offset ?";
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
				<tr><td><b>Fix errors</b></td><td><?=($audit['audit_fixerrors'] ? "Yes" : "No")?></td></tr>
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

		<table class="ui celled selectable compact table">
			<thead>
				<tr>
					<th>Issue</th>
					<th>Object</th>
					<th>Description</th>
					<th>Fixed</th>
					<th>Fix description</th>
				</tr>
			</thead>
			<tbody>
			<?
			if (mysqli_num_rows($result) < 1) {
				?><tr><td colspan="5" style="text-align: center; color: #888">No issues logged</td></tr><?
			}
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$objectid = (int)$row['object_id'];
				switch ($row['object_type']) {
					case 'subject': $object = "<a href='subjects.php?id=$objectid'>subject $objectid</a>"; break;
					case 'study': $object = "<a href='studies.php?id=$objectid'>study $objectid</a>"; break;
					default: $object = htmlspecialchars($row['object_type']) . " $objectid";
				}
				?>
				<tr>
					<td><?=htmlspecialchars($row['issue_type'])?></td>
					<td style="white-space: nowrap"><?=$object?></td>
					<td><?=htmlspecialchars($row['issue_message'] ?? '')?></td>
					<td><? if ($row['issue_fixed'] === null) { ?>-<? } elseif ($row['issue_fixed']) { ?><i class="green check icon"></i>Yes<? } else { ?><i class="red times icon"></i>No<? } ?></td>
					<td><?=htmlspecialchars($row['issue_fixedmessage'] ?? '')?></td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>

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
