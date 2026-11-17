<?
 // ------------------------------------------------------------------------------
 // NiDB importlog.php
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
		<title>NiDB - Import Log</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$useronly = GetVariable("useronly");

	/* Permissions (decision 2026-11-17): the page is read-only.
	   - Upload blocks: visible to the user who uploaded the transaction, or with View Data on the block's project
	   - Subject/study rows (importlogs): View Data on the project the data went into (the enrollment's project, or
	     the import project if there is no enrollment). Initials and patient ID also need View PHI on that project
	   - The full import log (viewimported) is admin-only
	   Admins and siteadmins see everything */

	/* determine action */
	switch ($action) {
		case 'viewimported':
			DisplayMenu();
			DisplayAllImportLog();
			break;
		case 'viewtransactions':
		default:
			DisplayMenu();
			DisplayTransactions($useronly);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
		
		?>
		<a href="importlog.php?action=viewtransactions&useronly=1">My Uploads</a> | <a href="importlog.php?action=viewtransactions&useronly=0">All Uploads</a><br>
		<br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetImportLogPerms ------------------ */
	/* -------------------------------------------- */
	/* the current user's permissions on every project, loaded once for the page */
	function GetImportLogPerms() {
		$projectids = array();
		$result = MySQLiQuery("select project_id from projects", __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projectids[] = (int)$row['project_id'];
		}
		return array('isadmin' => IsAdminUser(), 'projects' => GetCurrentUserProjectPermissions($projectids));
	}


	/* -------------------------------------------- */
	/* ------- ImportLogPerm ---------------------- */
	/* -------------------------------------------- */
	/* true if the user has $perm (viewdata, viewphi) on $projectid. Admins have every permission, including on
	   rows with no project */
	function ImportLogPerm($p, $perm, $projectid) {
		if ($p['isadmin'])
			return true;
		if ((int)$projectid < 1)
			return false;
		return (GetPerm($p['projects'], $perm, (int)$projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- GetImportLogRows ------------------- */
	/* -------------------------------------------- */
	/* subject/study rows from importlogs, grouped by series. $transactionid limits them to one transaction's
	   import requests; 0 returns all rows. rowprojectid is the project the data went into */
	function GetImportLogRows($transactionid = 0) {
		$transactionid = (int)$transactionid;
		$sqlstring = "select a.*, coalesce(e.project_id, a.importprojectid) 'rowprojectid', timediff(max(a.importstartdate), min(a.importstartdate)) 'importtime', date_format(max(a.importstartdate), '%b %e, %Y %T') 'maximportdatetime', date_format(a.studydatetime_orig, '%b %e, %Y %T') 'studydatetime', date_format(a.seriesdatetime_orig, '%b %e, %Y %T') 'seriesdatetime', count(*) 'numfiles' from importlogs a left join enrollment e on a.enrollmentid = e.enrollment_id";
		if ($transactionid > 0)
			$sqlstring .= " where a.importgroupid in (select importrequest_id from import_requests where import_transactionid = ?)";
		$sqlstring .= " group by a.stationname_orig, a.studydatetime_orig, a.seriesnumber_orig, rowprojectid order by a.studydatetime_orig desc, a.seriesdatetime_orig";

		if ($transactionid > 0) {
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $transactionid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$transactionid]);
			mysqli_stmt_close($stmt);
		}
		else {
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}

		$rows = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = $row;
		}
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- DisplayImportLogTable -------------- */
	/* -------------------------------------------- */
	/* the subject/study table. Rows without View Data are left out (and counted); initials and patient ID are
	   replaced with NoViewPermission() without View PHI */
	function DisplayImportLogTable($rows, $p) {
		$visible = array();
		$numhidden = 0;
		foreach ($rows as $row) {
			if (ImportLogPerm($p, 'viewdata', $row['rowprojectid']))
				$visible[] = $row;
			else
				$numhidden++;
		}

		if ($numhidden > 0) {
			?><div class="ui small message"><?=(int)$numhidden?> series not shown. You do not have View Data permissions on their project</div><?
		}
		if (count($visible) == 0)
			return;
		?>
		<style>
			.importlogtext { position:relative; width:20px; height:170px; }
			.importlogtext span { position:absolute; width:170px; line-height:20px; left:0; top:100%; transform:rotate(-90deg); -webkit-transform:rotate(-90deg); transform-origin:0 0; -webkit-transform-origin:0 0; text-align:left; vertical-align: middle; }
		</style>
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th valign="bottom">Initials</th>
					<th>Patient ID</th>
					<th>Equipment</th>
					<th>Modality</th>
					<th>Study date</th>
					<th>Series date</th>
					<th>Series Num</th>
					<th>Num files</th>
					<th> </th>
					<th>New UID</th>
					<th>Study Num</th>
					<th>Project</th>
					<th>Import time<br><span class="tiny">HH:MM:SS</span></th>
					<th>Import complete</th>
					<th><div class="importlogtext"><span style="font-size:10pt; font-weight:normal;">Created subject</span></div></th>
					<th><div class="importlogtext"><span style="font-size:10pt; font-weight:normal;">Created family</span></div></th>
					<th><div class="importlogtext"><span style="font-size:10pt; font-weight:normal;">Created enrollment</span></div></th>
					<th><div class="importlogtext"><span style="font-size:10pt; font-weight:normal;">Created study</span></div></th>
					<th><div class="importlogtext"><span style="font-size:10pt; font-weight:normal;">Created series</span></div></th>
					<th><div class="importlogtext"><span style="font-size:10pt; font-weight:normal;">Overwrote existing data</span></div></th>
				</tr>
			</thead>
			<tbody>
			<?
			$check = "&#x2713;";
			foreach ($visible as $row) {
				$viewphi = ImportLogPerm($p, 'viewphi', $row['rowprojectid']);

				/* show only the patient's initials */
				$parts = preg_split("/(\s\s+|\^)/", (string)($row['patientname_orig'] ?? ''));
				$initials = '';
				foreach ($parts as $part) {
					$initials = substr($part, 0, 1) . $initials;
				}
				?>
				<tr>
					<td><?=($viewphi ? htmlspecialchars($initials) : NoViewPermission())?></td>
					<td title="PatientID (from file)"><?=($viewphi ? htmlspecialchars($row['patientid_orig'] ?? '') : NoViewPermission())?></td>
					<td style="font-size:8pt"><?=htmlspecialchars($row['stationname_orig'] ?? '')?></td>
					<td><?=htmlspecialchars($row['modality_orig'] ?? '')?></td>
					<td title="Study date/time" style="font-size:8pt; white-space: nowrap;"><?=htmlspecialchars($row['studydatetime'] ?? '')?></td>
					<td title="Series date/time" style="font-size:8pt; white-space: nowrap;"><?=htmlspecialchars($row['seriesdatetime'] ?? '')?></td>
					<td title="Series number"><?=htmlspecialchars($row['seriesnumber_orig'] ?? '')?></td>
					<td title="Number of files"><?=(int)$row['numfiles']?></td>
					<td style="font-size:16pt; padding-left:20px; padding-right:20px; color:darkred; -webkit-transform:scale(1.5,1);">&#10137;</td>
					<td><a href="subjects.php?id=<?=(int)$row['subjectid']?>"><?=htmlspecialchars($row['subject_uid'] ?? '')?></a></td>
					<td title="Study number"><?=htmlspecialchars($row['study_num'] ?? '')?></td>
					<td title="Project number"><?=htmlspecialchars($row['project_number'] ?? '')?></td>
					<td title="Import time" style="font-size:8pt;"><?=htmlspecialchars($row['importtime'] ?? '')?></td>
					<td title="Import completed date/time" style="font-size:8pt;"><?=htmlspecialchars($row['maximportdatetime'] ?? '')?></td>
					<td title="Subject created"><?=($row['subject_created'] ? $check : '')?></td>
					<td title="Family created"><?=($row['family_created'] ? $check : '')?></td>
					<td title="Enrollment created"><?=($row['enrollment_created'] ? $check : '')?></td>
					<td title="Study created"><?=($row['study_created'] ? $check : '')?></td>
					<td title="Series created"><?=($row['series_created'] ? $check : '')?></td>
					<td title="Overwrote existing"><?=($row['overwrote_existing'] ? $check : '')?></td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAllImportLog ---------------- */
	/* -------------------------------------------- */
	/* every importlogs row. Admin-only */
	function DisplayAllImportLog() {
		if (!IsAdminUser()) {
			?><div class="ui error message">The full import log is only available to admins</div><?
			return;
		}
		DisplayImportLogTable(GetImportLogRows(0), GetImportLogPerms());
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayTransactions ---------------- */
	/* -------------------------------------------- */
	/* Read-only. Transactions older than 21 days are removed by the import module (moduleImport::ArchiveLocal) */
	function DisplayTransactions($useronly) {

		$useronly = ($useronly == 1) ? 1 : 0;
		$username = (string)($GLOBALS['username'] ?? '');
		$p = GetImportLogPerms();

		if ($useronly) {
			$sqlstring = "select * from import_transactions where transaction_username = ? order by transaction_startdate desc";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $username);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username]);
			mysqli_stmt_close($stmt);
		}
		else {
			$sqlstring = "select * from import_transactions order by transaction_startdate desc";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		}
		?>
		<style>
			.darkheader { background-color: #ddd; color: #333; vertical-align: top; }
			.highlighted:hover { border: 1px solid orange; }
		</style>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$transactionid = (int)$row['importtrans_id'];
			$transaction_startdate = $row['transaction_startdate'];
			$transaction_enddate = $row['transaction_enddate'];
			$transaction_username = (string)($row['transaction_username'] ?? '');
			$isowner = (($username != '') && ($transaction_username === $username));

			/* the upload blocks the user may see: all of their own, otherwise those in projects with View Data */
			$blocks = array();
			$sqlstringA = "select a.*, b.project_name, c.site_name, d.instance_name from import_requests a left join projects b on a.import_projectid = b.project_id left join nidb_sites c on a.import_siteid = c.site_id left join instance d on a.import_instanceid = d.instance_id where a.import_transactionid = ? order by import_datetime desc";
			$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
			mysqli_stmt_bind_param($stmtA, 'i', $transactionid);
			$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$transactionid]);
			mysqli_stmt_close($stmtA);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				if ($isowner || ImportLogPerm($p, 'viewdata', $rowA['import_projectid']))
					$blocks[] = $rowA;
			}

			/* the subject/study rows the user may see */
			$logrows = GetImportLogRows($transactionid);
			$numvisiblelogs = 0;
			foreach ($logrows as $logrow) {
				if (ImportLogPerm($p, 'viewdata', $logrow['rowprojectid']))
					$numvisiblelogs++;
			}

			/* skip transactions with nothing to show */
			if ((count($blocks) == 0) && ($numvisiblelogs == 0))
				continue;

			/* counts over the visible blocks */
			$numblocks = count($blocks);
			$numarchived = 0;
			$numblank = 0;
			$numerror = 0;
			$numfilesfail = 0;
			foreach ($blocks as $block) {
				$status = (string)($block['import_status'] ?? '');
				if ($status == 'archived') $numarchived++;
				if ($status == '') $numblank++;
				if ($status == 'error') $numerror++;
				$numfilesfail += (int)$block['numfilesfail'];
			}

			if (($numerror > 0) || ($numfilesfail > 0)) {
				$statusicon = "&#9940;";
				$statuscolor = "#ba4927";
			}
			elseif (($numblocks > 0) && ($numarchived == $numblocks) && ($transaction_enddate != '')) {
				$statusicon = "&#10004;";
				$statuscolor = "#4b8c41";
			}
			else {
				$statusicon = "&#10004;";
				$statuscolor = "#888";
			}
			$percent = ($numblocks > 0) ? number_format(((double)($numarchived+$numblank)/$numblocks)*100.0, 1) : "0.0";
			?>
			<div align="center">
			<table width="90%" cellspacing="0" cellpadding="4" style="border: solid 2px #333">
				<tr>
					<td align="center" style="background-color: <?=$statuscolor?>; color: #fff" width="5%"><?=$statusicon?></td>
					<td class="darkheader"><span style="font-size: 8pt" width="20%">User</span><br><b><?=htmlspecialchars($transaction_username)?></b></td>
					<td class="darkheader"><span style="font-size: 8pt" width="20%">Transaction ID</span><br><?=$transactionid?></td>
					<td class="darkheader"><span style="font-size: 8pt">Upload started</span><br><b><?=date('M j, Y g:ia',strtotime($transaction_startdate ?? ''))?></b></td>
					<td class="darkheader" align="left" width="30%">
						<span style="font-size: 8pt">Archived blocks</span><br>
						<img src="ajaxapi.php?action=horizontalchart&b=yes&w=200&h=15&v=<?=$numarchived?>,<?=$numblocks-$numarchived?>&c=888888,EEEEEE"> <?=$percent?>% (<?=$numarchived?> of <?=$numblocks?>)
					</td>
				</tr>
				<tr>
					<td colspan="5" valign="top">
						<details>
						<summary>Upload block details</summary>
						<? DisplayUploadBlocks($blocks); ?>
						</details>
						<br>
						<details>
						<summary>Subject/study details</summary>
						<? DisplayImportLogTable($logrows, $p); ?>
						</details>
					</td>
				</tr>
			</table>
			</div>
			<br>
			<?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayUploadBlocks ---------------- */
	/* -------------------------------------------- */
	/* $blocks are import_requests rows the caller has already checked the user may see */
	function DisplayUploadBlocks($blocks) {
		if (count($blocks) == 0) {
			?><div class="ui small message">No upload blocks you have permission to view</div><?
			return;
		}
		?>
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th> </th>
					<th>ID</th>
					<th>Data type</th>
					<th>Received Files<br><span class="tiny">total/success/fail</span></th>
					<th>Start Date</th>
					<th>Status</th>
					<th>Message</th>
					<th>End Date</th>
					<th>Site</th>
					<th>Project</th>
					<th>Instance</th>
					<th colspan="2">View reports</th>
				</tr>
			</thead>
		<?
		/* the status steps, highlighted up to the block's current step */
		$stepindex = array('uploading' => 1, 'pending' => 2, 'receiving' => 3, 'received' => 4, 'archiving' => 5, 'archived' => 6, 'error' => 7);
		foreach ($blocks as $row) {
			$import_status = (string)($row['import_status'] ?? '');
			$numfilestotal = (int)$row['numfilestotal'];
			$numfilessuccess = (int)$row['numfilessuccess'];
			$numfilesfail = (int)$row['numfilesfail'];
			$sitename = (string)($row['site_name'] ?? '');

			$current = $stepindex[$import_status] ?? 0;
			$stepcolor = array();
			$stepweight = array();
			for ($i = 1; $i <= 7; $i++) {
				$stepcolor[$i] = ($i == $current) ? (($i == 7) ? "#f22" : "#000") : "#999";
				$stepweight[$i] = ($i == $current) ? "bold" : "normal";
			}
			
			if (($numfilestotal != $numfilessuccess) || ($import_status == 'error')) {
				$statusicon = "&#9940;";
				$statuscolor = "#ba4927";
			}
			elseif ($import_status == 'archived') {
				$statusicon = "&#10004;";
				$statuscolor = "#4b8c41";
			}
			else {
				$statusicon = "";
				$statuscolor = "";
			}
			
			$import_startdate = $row['import_startdate'] ?? '';
			$import_enddate = $row['import_enddate'] ?? '';
			$import_startdate = (($import_startdate == "") || ($import_startdate == "0000-00-00 00:00:00")) ? "-" : date("M j, Y g:ia", strtotime($import_startdate));
			$import_enddate = (($import_enddate == "") || ($import_enddate == "0000-00-00 00:00:00")) ? "-" : date("M j, Y g:ia", strtotime($import_enddate));
			?>
			<tr>
				<td align="center" style="background-color: <?=$statuscolor?>; color: #fff"><?=$statusicon?></td>
				<td valign="top"><?=(int)$row['importrequest_id']?></td>
				<td valign="top"><?=htmlspecialchars($row['import_datatype'] ?? '')?></td>
				<td valign="top"><?=$numfilestotal?>/<?=$numfilessuccess?>/<? if ($numfilesfail > 0) { echo "<span style='color: red; font-weight: bold'>$numfilesfail</span>"; } else { echo $numfilesfail; }?></td>
				<td valign="top" style="font-size:8pt"><?=$import_startdate?></td>
				<td valign="top" style="font-size:8pt">
					<span title="uploading &rarr; pending<br>api.php" class="highlighted"><span style="color: <?=$stepcolor[1]?>; font-weight: <?=$stepweight[1]?>">Uploading</span>&nbsp;&rarr;&nbsp;<span style="color: <?=$stepcolor[2]?>; font-weight: <?=$stepweight[2]?>">Uploaded</span></span>&nbsp;&rarr;&nbsp;
					<span title="receiving &rarr; received<br>importuploaded.pl" class="highlighted"><span style="color: <?=$stepcolor[3]?>; font-weight: <?=$stepweight[3]?>">Checking</span>&nbsp;&rarr;&nbsp;<span style="color: <?=$stepcolor[4]?>; font-weight: <?=$stepweight[4]?>">Checked</span></span>&nbsp;&rarr;&nbsp;
					<span title="archiving &rarr; archived<br>parsedicom.pl" class="highlighted"><span style="color: <?=$stepcolor[5]?>; font-weight: <?=$stepweight[5]?>">Archiving</span>&nbsp;&rarr;&nbsp;<span style="color: <?=$stepcolor[6]?>; font-weight: <?=$stepweight[6]?>">Archived</span></span>&nbsp;&nbsp;&nbsp;
					<span style="color: <?=$stepcolor[7]?>; font-weight: <?=$stepweight[7]?>">Error</span>
				</td>
				<td valign="top"><?=htmlspecialchars($row['import_message'] ?? '')?></td>
				<td valign="top" style="font-size:8pt"><?=$import_enddate?></td>
				<td valign="top" style="font-size:8pt">
					<? if (strlen($sitename) > 30) { ?>
					<span title="<?=htmlspecialchars($sitename, ENT_QUOTES)?>"><?=htmlspecialchars(substr($sitename, 0, 30))?>...</span>
					<? } else { echo htmlspecialchars($sitename); } ?>
				</td>
				<td valign="top" style="font-size:8pt"><?=htmlspecialchars($row['project_name'] ?? '')?></td>
				<td valign="top" style="font-size:8pt"><?=htmlspecialchars($row['instance_name'] ?? '')?></td>
				<td valign="top" style="font-size:8pt">
					<details>
					<summary>Upload report</summary>
					<pre><?=htmlspecialchars($row['uploadreport'] ?? '')?></pre>
					</details>
				</td>
				<td valign="top" style="font-size:8pt">
					<details>
					<summary>Archive report</summary>
					<pre><?=htmlspecialchars($row['archivereport'] ?? '')?></pre>
					</details>
				</td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}
	
?>

<? include("footer.php") ?>
