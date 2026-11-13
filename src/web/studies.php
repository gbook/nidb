<?
 // ------------------------------------------------------------------------------
 // NiDB studies.php
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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
?>
<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Studies</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nanodicom.php";

	$username = $_SESSION['username'];
	$instanceid = $_SESSION['instanceid'];

	/* ----- setup variables ----- */
	/* mutating actions require POST + CSRF token */
	$action = GetSafeAction(array("update", "saveme", "submitminipipelines", "movestudytosubject", "movestudytoproject", "moveseriestonewstudy", "delete", "deleteseries", "updateseries", "addseries", "renameseries", "updateseriesnotes", "hideseries", "unhideseries", "resetqa", "resetmriqc", "updatebidsmapping", "addtogroup", "sendtopacs"));
	$studyid = GetVariable("studyid");
	if ($studyid == 0) { $studyid = GetVariable("id"); }
	$studyid = (int)$studyid;
	$newuid = trim(GetVariable("newuid") ?? '');
	$newprojectid = (int)GetVariable("newprojectid");
	$seriesid = GetVariable("seriesid"); /* an int from this page, but an array (seriesid[]) from search.php */
	$seriesids = GetVariable("seriesids");
	$minipipelineid = (int)GetVariable("minipipelineid");
	$minipipelineids = GetVariable("minipipelineids");
	$modality = GetVariable("modality");
	$series_num = GetVariable("series_num");
	$notes = GetVariable("notes");
	$protocol = GetVariable("protocol");
	$seriesdesc = GetVariable("seriesdesc");
	$imagetype = GetVariable("imagetype");
	$bidsentitysuffix = GetVariable("bidsentitysuffix");
	$bidsIntendedForEntity = GetVariable("bidsIntendedForEntity");
	$bidsIntendedForTask = GetVariable("bidsIntendedForTask");
	$bidsIntendedForRun = GetVariable("bidsIntendedForRun");
	$bidsIntendedForSuffix = GetVariable("bidsIntendedForSuffix");
	$bidsIntendedForFileExtension = GetVariable("bidsIntendedForFileExtension");
	$bidsrun = GetVariable("bidsrun");
	$bidsautonumberruns = GetVariable("bidsautonumberruns");
	$bidsincludeacquisition = GetVariable("bidsincludeacquisition");
	$bidstask = GetVariable("bidstask");
	$bidspedirection = GetVariable("bidspedirection");
	$series_datetime = GetVariable("series_datetime");
	$studydatetime = GetVariable("studydatetime");
	$studyageatscan = GetVariable("studyageatscan");
	$studyheight = GetVariable("studyheight");
	$studyweight = GetVariable("studyweight");
	$studytype = GetVariable("studytype");
	$studydaynum = GetVariable("studydaynum");
	$studytimepoint = GetVariable("studytimepoint");
	$studyoperator = GetVariable("studyoperator");
	$studyphysician = GetVariable("studyphysician");
	$studysite = GetVariable("studysite");
	$studynotes = GetVariable("studynotes");
	$studydoradread = GetVariable("studydoradread");
	$studyradreaddate = GetVariable("studyradreaddate");
	$studyradreadfindings = GetVariable("studyradreadfindings");
	$studyetsnellchart = GetVariable("studyetsnellchart");
	$studyetvergence = GetVariable("studyetvergence");
	$studyettracking = GetVariable("studyettracking");
	$studysnpchip = GetVariable("studysnpchip");
	$studyaltid = GetVariable("studyaltid");
	$studyexperimenter = GetVariable("studyexperimenter");

	$newseriesdesc = GetVariable("newseriesdesc");
	$newseriesprotocol = GetVariable("newseriesprotocol");
	$seriesnotes = GetVariable("seriesnotes");

	$Sdate = GetVariable("Sdate");
	$studygroupid = GetVariable("studygroupid");
	$dicomaeid = GetVariable("dicomaeid");

	/* series ids arrive as seriesids[] from this page, and as seriesid[] from search.php */
	$seriesids = is_array($seriesids) ? $seriesids : array();
	if (is_array($seriesid)) {
		$seriesids = array_merge($seriesids, $seriesid);
		$seriesid = 0;
	}
	$seriesid = (int)$seriesid;

	/* mutating actions use POST/Redirect/GET: run the handler, stash its message, then
	   redirect to a GET so a refresh/Back doesn't re-submit. Each handler checks permissions itself */
	$studyurl = "studies.php?studyid=$studyid";
	switch($action) {
		case 'editform':
			DisplayStudyForm($studyid);
			break;
		case 'minipipelineform':
			DisplayMiniPipelineForm($studyid, $seriesids);
			break;
		case 'submitminipipelines':
			ob_start();
			SubmitMiniPipelines($modality, $seriesids, $minipipelineids, $minipipelineid);
			$_SESSION['flash'] = ob_get_clean();
			/* from search.php there is no study to go back to, so show the mini-pipeline's jobs */
			if ($studyid > 0) RedirectTo($studyurl);
			elseif ($minipipelineid > 0) RedirectTo("minipipeline.php?action=viewjobs&mpid=$minipipelineid");
			else RedirectTo("search.php");
			break;
		case 'update':
			ob_start();
			UpdateStudy($studyid, $modality, $studydatetime, $studyageatscan, $studyheight, $studyweight, $studytype, $studydaynum, $studytimepoint, $studyoperator, $studyphysician, $studysite, $studynotes, $studydoradread, $studyradreaddate, $studyradreadfindings, $studyetsnellchart, $studyetvergence, $studyettracking, $studysnpchip, $studyaltid, $studyexperimenter);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'saveme':
			ob_start();
			SaveSt($studyid, $studytype, $studydaynum, $studytimepoint, $studydatetime, $Sdate);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'movestudytosubject':
			ob_start();
			QueueMoveStudyToSubject($studyid, $newuid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'movestudytoproject':
			ob_start();
			MoveStudyToProject($studyid, $newprojectid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'moveseriestonewstudy':
			ob_start();
			MoveSeriesToNewStudy($studyid, $seriesids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'deleteconfirm':
			DeleteConfirm($studyid);
			break;
		case 'delete':
			ob_start();
			$subjectid = Delete($studyid);
			$_SESSION['flash'] = ob_get_clean();
			/* the study is gone, so go back to its subject */
			if ($subjectid > 0) RedirectTo("subjects.php?id=$subjectid");
			else RedirectTo($studyurl);
			break;
		case 'deleteseries':
			ob_start();
			DeleteSeries($studyid, $seriesid, $seriesids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'editseries':
			EditGenericSeries($seriesid, $modality);
			break;
		case 'updateseries':
			ob_start();
			$seriesstudyid = UpdateGenericSeries($seriesid, $modality, $protocol, $series_datetime, $notes);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("studies.php?studyid=" . (int)$seriesstudyid);
			break;
		case 'addseries':
			ob_start();
			AddGenericSeries($studyid, $series_num, $protocol, $series_datetime, $notes);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'renameseriesform':
			RenameSeriesForm($studyid, $seriesids);
			break;
		case 'renameseries':
			ob_start();
			RenameSeries($studyid, $newseriesdesc, $newseriesprotocol);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'updateseriesnotesform':
			UpdateSeriesNotesForm($studyid, $seriesids);
			break;
		case 'updateseriesnotes':
			ob_start();
			UpdateSeriesNotes($studyid, $seriesnotes);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'hideseries':
			ob_start();
			SetSeriesHidden($studyid, $seriesids, 1);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'unhideseries':
			ob_start();
			SetSeriesHidden($studyid, $seriesids, 0);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'resetqa':
			ob_start();
			ResetStudyQC($studyid, $seriesids, "basic");
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'resetmriqc':
			ob_start();
			ResetStudyQC($studyid, $seriesids, "mriqc");
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'editbidsmapping':
			EditBIDSMapping($seriesid, $modality);
			break;
		case 'updatebidsmapping':
			ob_start();
			UpdateBIDSMapping($studyid, $seriesdesc, $imagetype, $bidsentitysuffix, $bidsIntendedForEntity, $bidsIntendedForTask, $bidsIntendedForRun, $bidsIntendedForSuffix, $bidsIntendedForFileExtension, $bidsrun, $bidsautonumberruns, $bidsincludeacquisition, $bidstask, $bidspedirection);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'displayfiles':
			DisplayStudy($studyid, true);
			break;
		case 'addtogroup':
			ob_start();
			AddStudyToGroup($studyid, $studygroupid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		case 'sendtopacs':
			ob_start();
			QueueDICOMTransfer($studyid, $seriesids, $dicomaeid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo($studyurl);
			break;
		default:
			ShowFlashMessage();
			session_write_close(); /* nothing below writes to the session; release the lock so other pages aren't blocked while this one renders */
			DisplayStudy($studyid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetStudyRow ------------------------ */
	/* -------------------------------------------- */
	/* the study, with its enrollment, subject, and project. Returns null if not found */
	function GetStudyRow($studyid) {
		$studyid = (int)$studyid;
		if ($studyid <= 0) return null;

		$sqlstring = "select a.*, b.subject_id, b.project_id, c.uid, d.project_name, d.project_costcenter from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on b.project_id = d.project_id where a.study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $studyid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return $row ? $row : null;
	}


	/* -------------------------------------------- */
	/* ------- CheckStudyPerm --------------------- */
	/* -------------------------------------------- */
	/* returns the study row if the current user has $perm ('viewdata' or 'modifydata') on the
	   study's project, otherwise prints an error and returns null. $adminonly also requires an
	   admin, for the study operations that are only offered to admins */
	function CheckStudyPerm($studyid, $perm, $adminonly = false) {
		$row = GetStudyRow($studyid);
		if ($row === null) {
			Error("Study not found");
			return null;
		}

		if ($adminonly && !IsAdminUser()) {
			Error("Only administrators can perform this operation");
			return null;
		}

		$projectid = (int)$row['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, $perm, $projectid)) {
			Error("You do not have " . (($perm == 'modifydata') ? "Edit Data" : "View Data") . " permission on this study's project");
			return null;
		}

		return $row;
	}


	/* -------------------------------------------- */
	/* ------- GetSeriesTable --------------------- */
	/* -------------------------------------------- */
	/* returns [table, id column] for a modality's series table, or null if the modality isn't
	   one with a series table. The names are built from the modality, so it must be validated */
	function GetSeriesTable($modality) {
		$modality = strtolower(trim($modality ?? ''));
		$table = GetSeriesTableName($modality);
		if ($table == "") return null;

		$sqlstring = "select table_name from information_schema.tables where table_schema = database() and table_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $table);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$table]);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if (!$exists) return null;

		return array($table, $modality . "series_id");
	}


	/* -------------------------------------------- */
	/* ------- GetStudySeriesIDs ------------------ */
	/* -------------------------------------------- */
	/* of the requested series ids, returns the ones that belong to this study (study row from
	   GetStudyRow). Series ids from the request are never used without this check */
	function GetStudySeriesIDs($study, $seriesids) {
		$t = GetSeriesTable($study['study_modality'] ?? '');
		if ($t === null) return array();
		list($table, $idcol) = $t;

		if (!is_array($seriesids)) $seriesids = array($seriesids);
		$ids = array_values(array_unique(array_filter(array_map('intval', $seriesids), function($v) { return $v > 0; })));
		if (count($ids) == 0) return array();

		$studyid = (int)$study['study_id'];
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$sqlstring = "select `$idcol` 'id' from `$table` where study_id = ? and `$idcol` in ($placeholders)";
		$params = array_merge(array($studyid), $ids);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($params)), ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$valid = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$valid[] = (int)$row['id'];
		}
		mysqli_stmt_close($stmt);

		return $valid;
	}


	/* -------------------------------------------- */
	/* ------- GetSeriesStudyID ------------------- */
	/* -------------------------------------------- */
	/* the study a series belongs to, or 0 */
	function GetSeriesStudyID($modality, $seriesid) {
		$t = GetSeriesTable($modality);
		if ($t === null) return 0;
		list($table, $idcol) = $t;

		$seriesid = (int)$seriesid;
		$sqlstring = "select study_id from `$table` where `$idcol` = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $seriesid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return (int)($row['study_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- NullIfBlank ------------------------ */
	/* -------------------------------------------- */
	/* for optional form fields stored as NULL when left blank */
	function NullIfBlank($v) {
		$v = trim($v ?? '');
		return ($v == "") ? null : $v;
	}


	/* -------------------------------------------- */
	/* ------- FormatStudyDateTime ---------------- */
	/* -------------------------------------------- */
	/* normalize a date/time from a form (including datetime-local's 'T') to 'Y-m-d H:i:s', or "" if invalid */
	function FormatStudyDateTime($dt) {
		$dt = trim(str_ireplace("T", " ", $dt ?? ''));
		if ($dt == "") return "";
		$ts = strtotime($dt);
		if ($ts === false) return "";
		return date("Y-m-d H:i:s", $ts);
	}


	/* -------------------------------------------- */
	/* ------- QueueDICOMTransfer ----------------- */
	/* -------------------------------------------- */
	/* queue the selected series to be sent to a remote DICOM AE (PACS). Only series with
	   data_type 'dicom' are queued; the export module does the actual DICOM send */
	function QueueDICOMTransfer($studyid, $seriesids, $dicomaeid) {
		/* sending data off-site is like downloading it: View Data on the study's project */
		$study = CheckStudyPerm($studyid, 'viewdata');
		if ($study === null) return;

		$dicomaeid = (int)$dicomaeid;

		/* the export module builds the table name from this modality (<modality>_series). It comes
		   from the study, never the request, and must have a series table */
		$modality = strtolower($study['study_modality'] ?? '');
		$t = GetSeriesTable($modality);
		if ($t === null) {
			Error("Invalid modality [" . htmlspecialchars($modality) . "]. DICOM transfer not queued");
			return;
		}
		list($seriestable, $idcol) = $t;

		/* get the destination AE */
		$sqlstring = "select ae_title, ae_hostname, ae_ip, ae_port from dicom_ae where dicomae_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $dicomaeid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$dicomaeid]);
		$aerow = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$aerow) { Error("DICOM AE end point not found. DICOM transfer not queued"); return; }
		$aehost = ($aerow['ae_hostname'] != "") ? $aerow['ae_hostname'] : $aerow['ae_ip'];

		/* keep only the series that belong to this study and are DICOM */
		$seriesids = GetStudySeriesIDs($study, $seriesids);
		if (count($seriesids) == 0) { Error("No series from this study were selected. DICOM transfer not queued"); return; }
		$dicomseriesids = array();
		$skipped = array();
		foreach ($seriesids as $seriesid) {
			$seriesid = (int)$seriesid;
			$sqlstring = "select series_num, data_type from `$seriestable` where `$idcol` = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $seriesid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row)
				$skipped[] = "series ID $seriesid (not found)";
			elseif (strtolower($row['data_type'] ?? '') != "dicom")
				$skipped[] = "series " . $row['series_num'] . " (" . ($row['data_type'] ?? 'unknown') . ")";
			else
				$dicomseriesids[] = $seriesid;
		}
		$dicomseriesids = array_values(array_unique($dicomseriesids));

		if (count($dicomseriesids) < 1) {
			Error("None of the selected series are DICOM. DICOM transfer not queued" . ((count($skipped) > 0) ? "<br>Skipped: " . htmlspecialchars(implode(", ", $skipped)) : ""));
			return;
		}

		/* create the export, then add the series to it */
		$username = $GLOBALS['username'];
		$ip = getenv('REMOTE_ADDR');
		$sqlstring = "insert into exports (username, ip, download_flags, destinationtype, filetype, dicomae_id, submitdate, status) values (?, ?, 'DOWNLOAD_IMAGING', 'dicomae', 'dicom', ?, now(), 'submitted')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssi', $username, $ip, $dicomaeid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$username, $ip, $dicomaeid]);
		$exportid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);

		foreach ($dicomseriesids as $seriesid) {
			$sqlstring = "insert into exportseries (export_id, series_id, modality, status) values (?, ?, ?, 'submitted')";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iis', $exportid, $seriesid, $modality);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$exportid, $seriesid, $modality]);
			mysqli_stmt_close($stmt);
		}

		$msg = count($dicomseriesids) . " series queued for transfer to <b>" . htmlspecialchars($aerow['ae_title']) . "</b> (" . htmlspecialchars($aehost) . ":" . (int)$aerow['ae_port'] . ")";
		if (count($skipped) > 0)
			$msg .= "<br>Skipped non-DICOM series: " . htmlspecialchars(implode(", ", $skipped));
		$msg .= "<br><br><a href='requeststatus.php' class='ui small primary button'><i class='external alternate icon'></i> View export status</a>";
		Notice($msg, "DICOM transfer queued");
	}


	/* -------------------------------------------- */
	/* ------- AddStudyToGroup -------------------- */
	/* -------------------------------------------- */
	function AddStudyToGroup($studyid, $groupid) {
		if (!ValidID($studyid,'Study ID')) { return; }
		if (!ValidID($groupid,'Group ID')) { return; }
		$studyid = (int)$studyid;
		$groupid = (int)$groupid;

		/* groups only hold references, but don't let a user collect studies they can't see */
		$study = CheckStudyPerm($studyid, 'viewdata');
		if ($study === null) return;

		/* make sure the group exists and is a study group */
		$sqlstring = "select group_name from groups where group_id = ? and group_type = 'study'";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $groupid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$groupid]);
		if (mysqli_num_rows($result) < 1) { mysqli_stmt_close($stmt); Error("Study group not found"); return; }
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupname = htmlspecialchars($row['group_name']);
		mysqli_stmt_close($stmt);

		$studyname = htmlspecialchars(($study['uid'] ?? '') . $study['study_num']);
		$modality = strtolower($study['study_modality'] ?? '');

		/* check if it's already in the group (same matching as groups.php AddStudiesToGroup) */
		$sqlstring = "select subjectgroup_id from group_data where group_id = ? and data_id = ? and modality = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iis', $groupid, $studyid, $modality);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$groupid, $studyid, $modality]);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if ($exists) {
			Notice("$studyname is already in group <b>$groupname</b>");
			return;
		}

		$sqlstring = "insert into group_data (group_id, data_id, modality, date_added) values (?, ?, ?, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iis', $groupid, $studyid, $modality);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$groupid, $studyid, $modality]);
		mysqli_stmt_close($stmt);

		Notice("Added $studyname to group <b>$groupname</b>");
	}


	/* -------------------------------------------- */
	/* ------- SaveSt ----------------------------- */
	/* -------------------------------------------- */
	/* the "Quick Update" box on the study page (non-MR studies). Optionally copies the
	   study date/time to all of the study's series */
	function SaveSt($studyid, $studytype, $studydaynum, $studytimepoint, $studydatetime, $Sdate) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		$studydatetime = FormatStudyDateTime($studydatetime);
		if ($studydatetime == "") {
			Error("Invalid study date/time. Study not updated");
			return;
		}
		$studytype = trim($studytype ?? '');
		$studydaynum = (int)$studydaynum;
		$studytimepoint = (int)$studytimepoint;

		$sqlstring = "update studies set study_datetime = ?, study_type = ?, study_daynum = ?, study_timepoint = ? where study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssiii', $studydatetime, $studytype, $studydaynum, $studytimepoint, $studyid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studydatetime, $studytype, $studydaynum, $studytimepoint, $studyid]);
		mysqli_stmt_close($stmt);

		/* the series table comes from the study's own modality, never from the request */
		if ($Sdate == 'on') {
			$t = GetSeriesTable($study['study_modality']);
			if ($t === null) {
				Error("Study updated, but its modality has no series table, so the series were not updated");
				return;
			}
			list($table, $idcol) = $t;
			$sqlstring = "update `$table` set series_datetime = ? where study_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $studydatetime, $studyid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studydatetime, $studyid]);
			mysqli_stmt_close($stmt);
			Notice("Study and series updated");
			return;
		}

		Notice("Study updated");
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudy ------------------------ */
	/* -------------------------------------------- */
	function UpdateStudy($studyid, $modality, $studydatetime, $studyageatscan, $studyheight, $studyweight, $studytype, $studydaynum, $studytimepoint, $studyoperator, $studyphysician, $studysite, $studynotes, $studydoradread, $studyradreaddate, $studyradreadfindings, $studyetsnellchart, $studyetvergence, $studyettracking, $studysnpchip, $studyaltid, $studyexperimenter) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		/* the modality must be one of the defined modalities */
		$modality = trim($modality ?? '');
		$sqlstring = "select mod_code from modalities where mod_code = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $modality);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modality]);
		$validmodality = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if (!$validmodality) {
			Error("Invalid modality [" . htmlspecialchars($modality) . "]. Study not updated");
			return;
		}

		$studydatetime = FormatStudyDateTime($studydatetime);
		if ($studydatetime == "") {
			Error("Invalid study date/time. Study not updated");
			return;
		}

		$studydoradread = ($studydoradread == "1") ? 1 : 0;
		$studyradreaddate = NullIfBlank($studyradreaddate);
		$studyetsnellchart = NullIfBlank($studyetsnellchart);
		$studytimepoint = NullIfBlank($studytimepoint);
		$studydaynum = NullIfBlank($studydaynum);
		$studyheight = NullIfBlank($studyheight);
		$studyweight = NullIfBlank($studyweight);
		$studyageatscan = NullIfBlank($studyageatscan);

		$sqlstring = "update studies set study_experimenter = ?, study_alternateid = ?, study_modality = ?, study_datetime = ?, study_ageatscan = ?, study_height = ?, study_weight = ?, study_type = ?, study_daynum = ?, study_timepoint = ?, study_operator = ?, study_performingphysician = ?, study_site = ?, study_notes = ?, study_doradread = ?, study_radreaddate = ?, study_radreadfindings = ?, study_etsnellenchart = ?, study_etvergence = ?, study_ettracking = ?, study_snpchip = ?, study_status = 'complete' where study_id = ?";
		$params = [trim($studyexperimenter ?? ''), trim($studyaltid ?? ''), $modality, $studydatetime, $studyageatscan, $studyheight, $studyweight, trim($studytype ?? ''), $studydaynum, $studytimepoint, trim($studyoperator ?? ''), trim($studyphysician ?? ''), trim($studysite ?? ''), $studynotes ?? '', $studydoradread, $studyradreaddate, trim($studyradreadfindings ?? ''), $studyetsnellchart, trim($studyetvergence ?? ''), trim($studyettracking ?? ''), trim($studysnpchip ?? ''), $studyid];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssssssssssssissssssi', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Study Updated");
	}


	/* -------------------------------------------- */
	/* ------- AddGenericSeries ------------------- */
	/* -------------------------------------------- */
	/* add an empty series to a non-MR study. The series table comes from the study's modality */
	function AddGenericSeries($studyid, $series_num, $protocol, $series_datetime, $notes) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		if (strtolower($study['study_modality']) == "mr") {
			Error("MR series can't be added by hand. They are created when DICOM data is imported");
			return;
		}
		$t = GetSeriesTable($study['study_modality']);
		if ($t === null) {
			Error("This study's modality [" . htmlspecialchars($study['study_modality']) . "] has no series table. Series not added");
			return;
		}
		list($table, $idcol) = $t;

		$series_num = (int)$series_num;
		$protocol = trim($protocol ?? '');
		$notes = trim($notes ?? '');
		$series_datetime = FormatStudyDateTime($series_datetime);
		if ($series_datetime == "") {
			Error("Invalid series date/time. Series not added");
			return;
		}
		$createdby = $GLOBALS['username'];

		$sqlstring = "insert into `$table` (study_id, series_num, series_datetime, series_protocol, series_notes, series_createdby) values (?, ?, ?, ?, ?, ?)";
		$params = [$studyid, $series_num, $series_datetime, $protocol, $notes, $createdby];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iissss', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Series added");
	}


	/* -------------------------------------------- */
	/* ------- MoveSeriesToNewStudy --------------- */
	/* -------------------------------------------- */
	/* copy this (MR) study to a new study for the same subject/enrollment, then move the selected
	   series into it, copying the series directories within the archive */
	function MoveSeriesToNewStudy($studyid, $seriesids) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		if (strtolower($study['study_modality']) != "mr") {
			Error("Only MR series can be moved to a new study");
			return;
		}

		/* only series that belong to this study */
		$sids = GetStudySeriesIDs($study, $seriesids);
		if (count($sids) == 0) {
			Error("No series from this study were selected");
			return;
		}
		$placeholders = implode(',', array_fill(0, count($sids), '?'));
		$sidtypes = str_repeat('i', count($sids));

		$uid = $study['uid'];
		$oldstudynum = (int)$study['study_num'];
		$subjectid = (int)$study['subject_id'];
		$projectid = (int)$study['project_id'];
		$enrollmentid = (int)$study['enrollment_id'];
		$archivedir = $GLOBALS['cfg']['archivedir'];

		$logmsg = "";
		echo "<ol>";

		/* start a transaction */
		MySQLiQuery("start transaction", __FILE__, __LINE__);
		echo "<li>Start transaction";
		$logmsg .= "start transaction\n";

		/* get lowest seriesdatetime, make that the new study time */
		$sqlstring = "select min(series_datetime) 'newstudydatetime' from mr_series where mrseries_id in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $sidtypes, ...$sids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $sids);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$newstudydatetime = $row['newstudydatetime'];
		echo "<li>New study datetime [" . htmlspecialchars($newstudydatetime ?? '') . "]";
		$logmsg .= "NewStudyDatetime [$newstudydatetime]\n";

		/* get largest study_num for this subject */
		$sqlstring = "select max(a.study_num) 'maxstudynum' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $subjectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$newstudynum = (int)$row['maxstudynum'] + 1;
		echo "<li>New study number [$newstudynum]";
		$logmsg .= "NewStudyNumber [$newstudynum]\n";

		/* copy the study, get the new study number */
		/* 1 - create temp table */
		$sqlstring = "create temporary table tmp_studies select * from studies where study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $studyid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
		mysqli_stmt_close($stmt);
		echo "<li>Copy study into temp table";
		$logmsg .= "create temporary table tmp_studies (study $studyid)\n";

		/* 2 - update the temp table with new study num, and datetime */
		$sqlstring = "update tmp_studies set study_id = 0, study_num = ?, study_datetime = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $newstudynum, $newstudydatetime);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newstudynum, $newstudydatetime]);
		mysqli_stmt_close($stmt);
		echo "<li>Update temp table";
		$logmsg .= "update tmp_studies\n";

		/* 3 - copy the new study to the studies table */
		MySQLiQuery("insert into studies select * from tmp_studies", __FILE__, __LINE__);
		$newstudyid = mysqli_insert_id($GLOBALS['linki']);
		echo "<li>Copy new study into studies table. New study ID [$newstudyid]";
		$logmsg .= "insert into studies. NewStudyID [$newstudyid]\n";

		/* 4 - drop the temp table */
		MySQLiQuery("drop temporary table if exists tmp_studies", __FILE__, __LINE__);
		echo "<li>Drop temporary table";
		$logmsg .= "drop temporary table\n";

		/* foreach series, copy it to the new study directory */
		$sqlstring = "select series_num from mr_series where mrseries_id in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $sidtypes, ...$sids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $sids);
		mysqli_stmt_close($stmt);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriesnum = (int)$row['series_num'];

			/* copy the data, don't move in case there is a problem */
			$oldpath = "$archivedir/$uid/$oldstudynum/$seriesnum";
			$newpath = "$archivedir/$uid/$newstudynum/$seriesnum";
			$oldpathrenamed = "$archivedir/$uid/$oldstudynum/$seriesnum-" . GenerateRandomString(10);

			if (!file_exists($oldpath)) {
				MySQLiQuery("rollback", __FILE__, __LINE__);
				?><li><b style="color: red">The original path [<?=htmlspecialchars($oldpath)?>] does not exist. Stopped, and the database changes were rolled back (series directories already copied above were not moved back)</b></ol><?
				return;
			}

			$systemstring = "mkdir -pv " . escapeshellarg($newpath) . " 2>&1";
			$copyresults = shell_exec($systemstring);
			echo "<li>Creating new directory. Command [ <span class='tt'>" . htmlspecialchars($systemstring) . "</span> ] Output [ <span class='tt'>" . htmlspecialchars($copyresults ?? '') . "</span> ]";
			$logmsg .= "Command [$systemstring] Output [$copyresults]\n";

			if (!file_exists($newpath)) {
				MySQLiQuery("rollback", __FILE__, __LINE__);
				?><li><b style="color: red">The new path [<?=htmlspecialchars($newpath)?>] could not be created. Stopped, and the database changes were rolled back (series directories already copied above were not moved back)</b></ol><?
				return;
			}

			/* the trailing '/.' copies the directory's contents, including dot files */
			$systemstring = "rsync -rtuv " . escapeshellarg("$oldpath/.") . " " . escapeshellarg("$newpath/") . " 2>&1";
			echo "<li>Moving series data within archive directory (may take a while). Command [<span class='tt'>" . htmlspecialchars($systemstring) . "</span>] Output:<br>";
			$copyresults = shell_exec($systemstring);
			echo "<pre style='background-color: #eee'><span class='tt'>" . htmlspecialchars($copyresults ?? '') . "</span></pre>";
			$logmsg .= "Command [$systemstring] Output [$copyresults]\n";

			$systemstring = "mv " . escapeshellarg($oldpath) . " " . escapeshellarg($oldpathrenamed) . " 2>&1";
			echo "<li>Renaming original series directory. Command [<tt>" . htmlspecialchars($systemstring) . "</tt>] Output:<br>";
			$copyresults = shell_exec($systemstring);
			echo "<pre style='background-color: #eee'><tt>" . htmlspecialchars($copyresults ?? '') . "</tt></pre>";
			$logmsg .= "Command [$systemstring] Output [$copyresults]\n";
		}

		/* 5 - change the studyid for the series */
		$sqlstring = "update mr_series set study_id = ? where mrseries_id in ($placeholders)";
		$params = array_merge(array($newstudyid), $sids);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i' . $sidtypes, ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		echo "<li>Update mr_series table with new study ID";
		$logmsg .= "update mr_series set study_id = $newstudyid\n";

		/* commit the transaction */
		MySQLiQuery("commit", __FILE__, __LINE__);
		echo "<li>Commit transaction";
		$logmsg .= "commit\n";

		/* insert a changelog */
		$instanceid = (int)$GLOBALS['instanceid'];
		$userid = (int)$_SESSION['userid'];
		$changedesc = "Moved series from study [$uid$oldstudynum] to [$uid$newstudynum]. Results [" . trim($logmsg) . "]";
		$sqlstring = "insert into changelog (performing_userid, affected_userid, affected_instanceid1, affected_instanceid2, affected_siteid1, affected_siteid2, affected_projectid1, affected_projectid2, affected_subjectid1, affected_subjectid2, affected_enrollmentid1, affected_enrollmentid2, affected_studyid1, affected_studyid2, affected_seriesid1, affected_seriesid2, change_datetime, change_event, change_desc) values (?, null, ?, null, null, null, ?, ?, ?, ?, ?, ?, ?, ?, null, null, now(), 'MoveSeriesToNewStudy', ?)";
		$params = [$userid, $instanceid, $projectid, $projectid, $subjectid, $subjectid, $enrollmentid, $enrollmentid, $studyid, $newstudyid, $changedesc];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iiiiiiiiiis', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		echo "<li>Insert changelog";

		echo "</ol>";
		Notice("Moved " . count($sids) . " series to new study <a href='studies.php?studyid=$newstudyid'>" . htmlspecialchars($uid) . "$newstudynum</a>");
	}


	/* -------------------------------------------- */
	/* ------- GetStudySeriesRows ----------------- */
	/* -------------------------------------------- */
	/* the rows of the selected series that belong to this study, by series number */
	function GetStudySeriesRows($study, $seriesids) {
		$rows = array();
		$sids = GetStudySeriesIDs($study, $seriesids);
		if (count($sids) == 0) return $rows;

		list($table, $idcol) = GetSeriesTable($study['study_modality']);
		$placeholders = implode(',', array_fill(0, count($sids), '?'));
		$sqlstring = "select * from `$table` where `$idcol` in ($placeholders) order by series_num";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($sids)), ...$sids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $sids);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$row['_id'] = (int)$row[$idcol];
			$rows[] = $row;
		}
		mysqli_stmt_close($stmt);

		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- RenameSeriesForm ------------------- */
	/* -------------------------------------------- */
	function RenameSeriesForm($studyid, $seriesids) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		$rows = GetStudySeriesRows($study, $seriesids);
		if (count($rows) == 0) {
			Error("No series from this study were selected");
			return;
		}

		?>
		<form method="post" action="studies.php">
		<input type="hidden" name="action" value="renameseries">
		<input type="hidden" name="studyid" value="<?=$studyid?>">
		<?=CSRFTokenField()?>
		<table class="ui very compact celled collapsing grey table">
			<thead>
				<tr>
					<th></th>
					<th colspan="2" style="text-align:center; border-right: 1px solid #aaa">Current</th>
					<th colspan="2" style="text-align:center;">New</th>
				</tr>
				<tr>
					<th>Series</th>
					<th>Description<br><span class="tiny">DICOM field <i>SeriesDescription</i></span></th>
					<th style="border-right: 1px solid #aaa">Protocol<br><span class="tiny">DICOM field <i>ProtocolName</i></span></th>
					<th>Description</th>
					<th>Protocol</th>
				</tr>
			</thead>
			<tbody>
			<?
			foreach ($rows as $row) {
				$seriesid = $row['_id'];
				$seriesnum = htmlspecialchars($row['series_num']);
				$seriesdesc = htmlspecialchars($row['series_desc'] ?? '');
				$seriesprotocol = htmlspecialchars($row['series_protocol'] ?? '');
				?>
				<tr>
					<td><?=$seriesnum?></td>
					<td class="tt"><?=$seriesdesc?></td>
					<td style="border-right: 1px solid #aaa" class="tt"><?=$seriesprotocol?></td>
					<td><input type="text" name="newseriesdesc[<?=$seriesid?>]" value="<?=$seriesdesc?>" style="font-family: monospace;"></td>
					<td><input type="text" name="newseriesprotocol[<?=$seriesid?>]" value="<?=$seriesprotocol?>" style="font-family: monospace;"></td>
				</tr>
				<?
			}
		?>
			<tr>
				<td colspan="5" align="right"><a href="studies.php?studyid=<?=$studyid?>" class="ui button">Cancel</a> <input type="submit" value="Rename" class="ui primary button"></td>
			</tr>
		</table>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateSeriesField ------------------ */
	/* -------------------------------------------- */
	/* set one text column ($column is a literal from this file, never from the request) for each
	   [seriesid => value] in $values, for the series that belong to this study. Returns the count */
	function UpdateSeriesField($study, $values, $column) {
		if (!is_array($values) || (count($values) == 0)) return 0;

		$sids = GetStudySeriesIDs($study, array_keys($values));
		list($table, $idcol) = GetSeriesTable($study['study_modality']);

		$sqlstring = "update `$table` set `$column` = ? where `$idcol` = ?";
		$n = 0;
		foreach ($sids as $seriesid) {
			$value = trim($values[$seriesid] ?? '');
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $value, $seriesid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$value, $seriesid]);
			mysqli_stmt_close($stmt);
			$n++;
		}

		return $n;
	}


	/* -------------------------------------------- */
	/* ------- RenameSeries ----------------------- */
	/* -------------------------------------------- */
	function RenameSeries($studyid, $newseriesdesc, $newseriesprotocol) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		if (GetSeriesTable($study['study_modality']) === null) { Error("This study's modality has no series table"); return; }

		UpdateSeriesField($study, $newseriesdesc, 'series_desc');
		$n = UpdateSeriesField($study, $newseriesprotocol, 'series_protocol');

		Notice("Series names updated ($n series)");
	}


	/* -------------------------------------------- */
	/* ------- UpdateSeriesNotesForm -------------- */
	/* -------------------------------------------- */
	function UpdateSeriesNotesForm($studyid, $seriesids) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		$rows = GetStudySeriesRows($study, $seriesids);
		if (count($rows) == 0) {
			Error("No series from this study were selected");
			return;
		}

		?>
		<div class="ui text container">
			<form method="post" action="studies.php" class="ui form">
			<input type="hidden" name="action" value="updateseriesnotes">
			<input type="hidden" name="studyid" value="<?=$studyid?>">
			<?=CSRFTokenField()?>
			<table class="ui celled top attached grey table">
				<thead>
					<tr>
						<th>Series</th>
						<th>Description</th>
						<th>Note</th>
					</tr>
				</thead>
				<tbody>
				<?
				foreach ($rows as $row) {
					$seriesid = $row['_id'];
					?>
					<tr>
						<td><?=htmlspecialchars($row['series_num'])?></td>
						<td class="tt"><?=htmlspecialchars($row['series_desc'] ?? '')?></td>
						<td><input type="text" name="seriesnotes[<?=$seriesid?>]" value="<?=htmlspecialchars($row['series_notes'] ?? '')?>" style="font-family: monospace;"></td>
					</tr>
					<?
				}
			?>
			</table>
				<div class="ui bottom attached segment">
					<a href="studies.php?id=<?=$studyid?>" class="ui button">Cancel</a>
					<input type="submit" value="Save notes" class="ui primary button">
				</div>
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateSeriesNotes ------------------ */
	/* -------------------------------------------- */
	function UpdateSeriesNotes($studyid, $seriesnotes) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		if (GetSeriesTable($study['study_modality']) === null) { Error("This study's modality has no series table"); return; }

		$n = UpdateSeriesField($study, $seriesnotes, 'series_notes');

		Notice("Series notes updated ($n series)");
	}


	/* -------------------------------------------- */
	/* ------- UpdateBIDSMapping ------------------ */
	/* -------------------------------------------- */
	/* BIDS mappings are per project (the study's project), keyed by protocol name + image type */
	function UpdateBIDSMapping($studyid, $seriesdesc, $imagetype, $bidsentitysuffix, $bidsIntendedForEntity, $bidsIntendedForTask, $bidsIntendedForRun, $bidsIntendedForSuffix, $bidsIntendedForFileExtension, $bidsrun, $bidsautonumberruns, $bidsincludeacquisition, $bidstask, $bidspedirection) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$projectid = (int)$study['project_id'];
		$modality = strtolower($study['study_modality']);

		$seriesdesc = trim($seriesdesc ?? '');
		$imagetype = trim($imagetype ?? '');
		$bidsentitysuffix = trim($bidsentitysuffix ?? '');
		$bidsIntendedForEntity = trim($bidsIntendedForEntity ?? '');
		$bidsIntendedForTask = trim($bidsIntendedForTask ?? '');
		$bidsIntendedForRun = trim($bidsIntendedForRun ?? '');
		$bidsIntendedForSuffix = trim($bidsIntendedForSuffix ?? '');
		$bidsIntendedForFileExtension = trim($bidsIntendedForFileExtension ?? '');
		$bidstask = trim($bidstask ?? '');
		$bidspedirection = trim($bidspedirection ?? '');
		$bidsrun = (int)$bidsrun;
		$bidsautonumberruns = ($bidsautonumberruns == "1") ? 1 : 0;
		$bidsincludeacquisition = ($bidsincludeacquisition == "1") ? 1 : 0;

		$parts = explode(":", $bidsentitysuffix, 2);
		$bidsentity = $parts[0];
		$bidssuffix = $parts[1] ?? '';

		$sqlstring = "insert ignore into bids_mapping (project_id, protocolname, imagetype, modality, bidsentity, bidssuffix, bidsrun, bidsAutoNumberRuns, bidsIncludeAcquisition, bidsIntendedForEntity, bidsIntendedForTask, bidsIntendedForRun, bidsIntendedForSuffix, bidsIntendedForFileExtension, bidstask, bidspedirection) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) on duplicate key update bidsentity = values(bidsentity), bidssuffix = values(bidssuffix), bidsrun = values(bidsrun), bidsAutoNumberRuns = values(bidsAutoNumberRuns), bidsIncludeAcquisition = values(bidsIncludeAcquisition), bidsIntendedForEntity = values(bidsIntendedForEntity), bidsIntendedForTask = values(bidsIntendedForTask), bidsIntendedForRun = values(bidsIntendedForRun), bidsIntendedForSuffix = values(bidsIntendedForSuffix), bidsIntendedForFileExtension = values(bidsIntendedForFileExtension), bidstask = values(bidstask), bidspedirection = values(bidspedirection)";
		$params = [$projectid, $seriesdesc, $imagetype, $modality, $bidsentity, $bidssuffix, $bidsrun, $bidsautonumberruns, $bidsincludeacquisition, $bidsIntendedForEntity, $bidsIntendedForTask, $bidsIntendedForRun, $bidsIntendedForSuffix, $bidsIntendedForFileExtension, $bidstask, $bidspedirection];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isssssiiisssssss', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("BIDS mapping updated for this project<br><tt>" . htmlspecialchars($seriesdesc) . " | " . htmlspecialchars($imagetype) . "</tt> mapped to <tt>" . htmlspecialchars($bidsentitysuffix) . "</tt>");
	}


	/* -------------------------------------------- */
	/* ------- EditBIDSMapping -------------------- */
	/* -------------------------------------------- */
	function EditBIDSMapping($seriesid, $modality) {
		/* the mapping is saved to the series' project, so it needs Edit Data there */
		$seriesid = (int)$seriesid;
		$studyid = GetSeriesStudyID($modality, $seriesid);
		if ($studyid == 0) {
			Error("Series not found");
			return;
		}
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		if (strtolower($study['study_modality']) != strtolower($modality)) {
			Error("Series modality does not match the study");
			return;
		}
		$modality = strtolower($modality);
		list($table, $idcol) = GetSeriesTable($modality);

		$bidsentities['anat'] = array('IRT1','MESE','MEGRE','MP2RAGE','MPM','MTS','MTR','T1map','T2map','T2starmap','R1map','R2map','R2starmap','PDmap','MTRmap','MTsat','UNIT1','T1rho','MWFmap','MTVmap','Chimap','S0map','M0map','T1w','T2w','PDw','T2starw','FLAIR','inplaneT1','inplaneT2','PDT2','angio','T2star','FLASH','PD','VFA','defacemask');
		$bidsentities['dwi'] = array('dwi','sbref','physio','stim');
		$bidsentities['fmap'] = array('TB1AFI','TB1TFL','TB1RFM','RB1COR','TB1DAM','TB1EPI','TB1SRGE','TB1map','RB1map','epi','m0scan','phasediff','phase1','phase2','magnitude1','magnitude2','magnitude','magnitude1and2','fieldmap');
		$bidsentities['func'] = array('bold','cbv','sbref','events','phase','physio','stim');
		$bidsentities['perf'] = array('asl','m0scan','aslcontext','asllabeling','physio','stim');
		$bidsentities['derived'] = array('derived');
		
		/* anat */
		$suffixDesc['Chimap'] = "Quantitative susceptibility map (QSM)";
		$suffixDesc['FLAIR'] = "Fluid attenuated inversion recovery image";
		$suffixDesc['FLASH'] = "Fast-Low-Angle-Shot image";
		$suffixDesc['IRT1'] = "Inversion recovery T1";
		$suffixDesc['M0map'] = "Equilibrium magnetization (M0) map";
		$suffixDesc['MEGRE'] = "Multi-echo Gradient Recalled Echo";
		$suffixDesc['MESE'] = "Multi-echo spin echo";
		$suffixDesc['MP2RAGE'] = "Magnetization Prepared Two Gradient Echoes";
		$suffixDesc['MPM'] = "Multi-parametric Mapping";
		$suffixDesc['MTR'] = "Magnetization Transfer Ratio";
		$suffixDesc['MTRmap'] = "Magnetization transfer ratio image";
		$suffixDesc['MTS'] = "Magnetization transfer saturation";
		$suffixDesc['MTVmap'] = "Macromolecular tissue volume (MTV) image";
		$suffixDesc['MTsat'] = "Magnetization transfer saturation image";
		$suffixDesc['MWFmap'] = "Myelin water fraction image";
		$suffixDesc['PD'] = "Proton density image";
		$suffixDesc['PDT2'] = "PD and T2 weighted image";
		$suffixDesc['PDmap'] = "Proton density image";
		$suffixDesc['PDw'] = "Proton density (PD) weighted image";
		$suffixDesc['R1map'] = "Longitudinal relaxation rate image";
		$suffixDesc['R2map'] = "True transverse relaxation rate image";
		$suffixDesc['R2starmap'] = "Observed transverse relaxation rate image";
		$suffixDesc['S0map'] = "Observed signal amplitude (S0) image";
		$suffixDesc['T1map'] = "Longitudinal relaxation time image";
		$suffixDesc['T1rho'] = "T1 in rotating frame (T1 rho) image";
		$suffixDesc['T1w'] = "T1-weighted image";
		$suffixDesc['T2map'] = "True transverse relaxation time image";
		$suffixDesc['T2star'] = "T2* image";
		$suffixDesc['T2starmap'] = "Observed transverse relaxation time image";
		$suffixDesc['T2starw'] = "T2star weighted image";
		$suffixDesc['T2w'] = "T2-weighted image";
		$suffixDesc['UNIT1'] = "Homogeneous (flat) T1-weighted MP2RAGE image";
		$suffixDesc['VFA'] = "Variable flip angle";
		$suffixDesc['angio'] = "Angiogram";
		$suffixDesc['defacemask'] = "Defacing Mask";
		$suffixDesc['inplaneT1'] = "Inplane T1";
		$suffixDesc['inplaneT2'] = "Inplane T2";

		/* dwi */
		$suffixDesc['dwi'] = "Diffusion-weighted image";
		$suffixDesc['sbref'] = "Single-band reference image";
		$suffixDesc['physio'] = "Physiological recording";
		$suffixDesc['stim'] = "Continuous recording";
		
		/* fmap */
		$suffixDesc['epi'] = "The phase-encoding polarity (PEpolar) technique combines two or more Spin Echo EPI scans with different phase encoding directions to estimate the underlying inhomogeneity/deformation map.";
		$suffixDesc['fieldmap'] = "Fieldmap";
		$suffixDesc['m0scan'] = "ASL M0 calibration image";
		$suffixDesc['magnitude'] = "Magnitude";
		$suffixDesc['magnitude1'] = "Magnitude map generated by GRE or similar schemes, associated with the first echo in the sequence.";
		$suffixDesc['magnitude1and2'] = "First and second magnitude images; some scanners (Siemens) generate a single series from which magnitude 1 and 2 are derived.";
		$suffixDesc['magnitude2'] = "Magnitude map generated by GRE or similar schemes, associated with the second echo in the sequence.";
		$suffixDesc['phase1'] = "Phase map generated by GRE or similar schemes, associated with the first echo in the sequence.";
		$suffixDesc['phase2'] = "Phase map generated by GRE or similar schemes, associated with the second echo in the sequence.";
		$suffixDesc['phasediff'] = "Phase-difference; some scanners subtract the phase1 from the phase2 map and generate a unique phasediff file.";
		$suffixDesc['RB1COR'] = "Low resolution images acquired by the body coil (in the gantry of the scanner) and the head coil using identical acquisition parameters to generate a combined sensitivity map as described in Papp et al. (2016).";
		$suffixDesc['RB1map'] = "RF receive sensitivity map";
		$suffixDesc['TB1AFI'] = "This method (Yarnykh 2007) calculates a B1+ map from two images acquired at interleaved (two) TRs with identical RF pulses using a steady-state sequence.";
		$suffixDesc['TB1DAM'] = "The double-angle B1+ method (Insko and Bolinger 1993) is based on the calculation of the actual angles from signal ratios, collected by two acquisitions at different nominal excitation flip angles. Common sequence types for this application include spin echo and echo planar imaging.";
		$suffixDesc['TB1EPI'] = "This B1+ mapping method (Jiru and Klose 2006) is based on two EPI readouts to acquire spin echo (SE) and stimulated echo (STE) images at multiple flip angles in one sequence, used in the calculation of deviations from the nominal flip angle.";
		$suffixDesc['TB1map'] = "RF transmit field image";
		$suffixDesc['TB1RFM'] = "The result of a Siemens rf_map product sequence. This sequence produces two images. The first image appears like an anatomical image and the second output is a scaled flip angle map.";
		$suffixDesc['TB1SRGE'] = "Saturation-prepared with 2 rapid gradient echoes (SA2RAGE) uses a ratio of two saturation recovery images with different time delays, and a simulated look-up table to estimate B1+ (Eggenschwiler et al. 2011). This sequence can also be used in conjunction with MP2RAGE T1 mapping to iteratively improve B1+ and T1 map estimation (Marques & Gruetter 2013).";
		$suffixDesc['TB1TFL'] = "The result of a Siemens tfl_b1_map product sequence. This sequence produces two images. The first image appears like an anatomical image and the second output is a scaled flip angle map.";
		
		/* func */
		$suffixDesc['bold'] = "Blood-Oxygen-Level Dependent image";
		$suffixDesc['cbv'] = "Cerebral blood volume image";
		$suffixDesc['sbref'] = "Single-band reference image";
		$suffixDesc['events'] = "Events";
		$suffixDesc['phase'] = "Phase image (deprecated)";
		$suffixDesc['physio'] = "Physiological recording";
		$suffixDesc['stim'] = "Continuous recording";
		
		/* perf */
		$suffixDesc['asl'] = "Arterial spin labeling image";
		$suffixDesc['m0scan'] = "ASL M0 calibration image";
		$suffixDesc['aslcontext'] = "Arterial Spin Labeling Context";
		$suffixDesc['asllabeling'] = "ASL Labeling Screenshot";
		$suffixDesc['physio'] = "Physiological recording";
		$suffixDesc['stim'] = "Continuous recording";

		$sqlstring = "select series_desc, image_type from `$table` where `$idcol` = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $seriesid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$seriesdesc = $row['series_desc'] ?? '';
		$imagetype = $row['image_type'] ?? '';
		$projectid = (int)$study['project_id'];
		$projectname = $study['project_name'];

		$sqlstring = "select * from bids_mapping where protocolname = ? and imagetype = ? and modality = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssi', $seriesdesc, $imagetype, $modality, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesdesc, $imagetype, $modality, $projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) $row = array();
		/* everything below is displayed in HTML */
		$row = array_map(function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }, $row);
		//PrintVariable($row);
		$bidsentity = $row['bidsEntity'] ?? '';
		$bidssuffix = $row['bidsSuffix'] ?? '';
		$bidsIntendedForEntity = $row['bidsIntendedForEntity'] ?? '';
		$bidsIntendedForTask = $row['bidsIntendedForTask'] ?? '';
		$bidsIntendedForRun = $row['bidsIntendedForRun'] ?? '';
		$bidsIntendedForSuffix = $row['bidsIntendedForSuffix'] ?? '';
		$bidsIntendedForFileExtension = $row['bidsIntendedForFileExtension'] ?? '';
		$bidsrun = $row['bidsRun'] ?? '';
		$bidsautonumberruns = $row['bidsAutoNumberRuns'] ?? '';
		$bidsincludeacquisition = $row['bidsIncludeAcquisition'] ?? '';
		$bidstask = $row['bidsTask'] ?? '';
		$bidspedirection = $row['bidsPEDirection'] ?? '';
		$seriesdesc = htmlspecialchars($seriesdesc, ENT_QUOTES);
		$imagetype = htmlspecialchars($imagetype, ENT_QUOTES);
		$projectname = htmlspecialchars($projectname ?? '', ENT_QUOTES);
		
		?>
		<div class="ui container" style="overflow:visible">
			
			<h1 class="ui header">Mapping NiDB series</h1>
				<table class="ui blue table">
					<tr>
						<td><b>Series description</b></td>
						<td><tt><?=$seriesdesc?></tt></td>
					</tr>
					<tr>
						<td><b>Image Type</b></td>
						<td><tt><?=$imagetype?></tt></td>
					</tr>
					<tr>
						<td><b>Project</b></td>
						<td><tt><?=$projectname?></tt></td>
					</tr>
				</table>
			
			<h1 class="ui header">to BIDS</h1>
			
			<div class="ui blue segment">
			
				<form method="post" action="studies.php" class="ui form" style="overflow:visible">
					<input type="hidden" name="action" value="updatebidsmapping">
					<?=CSRFTokenField()?>
					<input type="hidden" name="studyid" value="<?=$studyid?>">
					<input type="hidden" name="seriesdesc" value="<?=$seriesdesc?>">
					<input type="hidden" name="imagetype" value="<?=$imagetype?>">
				
					<div class="field">
						<label>BIDS entity : suffix</label>
						<div class="ui selection dropdown">
							<input type="hidden" name="bidsentitysuffix" value="<?=$bidsentity?>:<?=$bidssuffix?>">
							<i class="dropdown icon"></i>
							<div class="default text">Select Entity:Suffix</div>
							<div class="scrollhint menu">
								<?
									ksort($bidsentities);
									foreach ($bidsentities as $entity => $suff) {
										?><div class="divider"></div><?
										natcasesort($suff);
										foreach ($suff as $suffix) {
											?>
											<div class="item" data-value="<?=$entity?>:<?=$suffix?>"><tt style="font-weight: bold"><?=$entity?> : <?=$suffix?></tt> <span style="color: #888888">- <?=htmlspecialchars($suffixDesc[$suffix] ?? '')?></span></div>
											<?
										}
									}
								?>
							</div>
						</div>
					</div>

					<h4 class="ui dividing header">BIDS IntendedFor</h4>
					<div class="fields">
						<div class="field">
							<label>Entity</label>
							<input type="text" name="bidsIntendedForEntity" value="<?=$bidsIntendedForEntity?>" style="font-family:monospace">
						</div>
						<div class="field">
							<label>Task</label>
							<input type="text" name="bidsIntendedForTask" value="<?=$bidsIntendedForTask?>" style="font-family:monospace">
						</div>
						<div class="field">
							<label>Run</label>
							<input type="text" name="bidsIntendedForRun" value="<?=$bidsIntendedForRun?>" style="font-family:monospace">
						</div>
						<div class="field">
							<label>Suffix</label>
							<input type="text" name="bidsIntendedForSuffix" value="<?=$bidsIntendedForSuffix?>" style="font-family:monospace">
						</div>
						<div class="field">
							<label>FileExtension</label>
							<input type="text" name="bidsIntendedForFileExtension" value="<?=$bidsIntendedForFileExtension?>" style="font-family:monospace">
						</div>
					</div>
					<div class="ui segment">
						<p><b><tt>IntendedFor</tt> Example</b></p>
						<p>If you enter the following options</p>
						<p>
						<table>
							<tr>
								<td><div class="ui fluid label">Entity</div></td>
								<td><tt>func, func</tt></td>
							</tr>
							<tr>
								<td><div class="ui fluid label">Task</div></td>
								<td><tt>Stroop, Stroop</tt></td>
							</tr>
							<tr>
								<td><div class="ui fluid label">Run</div></td>
								<td><tt>1,2</tt></td>
							</tr>
							<tr>
								<td><div class="ui fluid label">Suffix</div></td>
								<td><tt>bold, bold</tt></td>
							</tr>
							<tr>
								<td><div class="ui fluid label">FileExtension</div></td>
								<td><tt>nii.gz, nii.gz</tt></td>
							</tr>
						</table>
						</p>
						<p>then NiDB will generate this entry in the series' .json file</p>
						<p><div class="code">
							"IntendedFor": [<br>
							&nbsp;&nbsp;&nbsp;&nbsp;bids::sub-001/ses-001/func/sub-001_ses-001_task-Stroop_run-1_bold.nii.gz,<br>
							&nbsp;&nbsp;&nbsp;&nbsp;bids::sub-001/ses-001/func/sub-001_ses-001_task-Stroop_run-2_bold.nii.gz<br>
							]
						</div></p>
						<p>subject and session will be filled in appropriately</p>
					</div>

					<div class="field">
						<label>BIDS run number <i class="question circle outline icon" title="This BIDS series will always be labeled 'run-#'"></i></label>
						<input type="number" name="bidsrun" value="<?=$bidsrun?>">
					</div>

					<div class="field">
						<div class="ui checkbox">
							<input type="checkbox" name="bidsautonumberruns" value="1" <? if ($bidsautonumberruns == 1) { echo "checked"; } ?>>
							<label data-html="<div class='header'>Hello</div>">Automatically number runs</label>
						</div>
					</div>

					<div class="field">
						<label>BIDS task <i class="question circle outline icon" title="BIDS 'task-' filename option"></i></label>
						<input type="text" name="bidstask" value="<?=$bidstask?>" style="font-family:monospace">
					</div>

					<div class="field">
						<label>Acquisition</label>
						<div class="ui checkbox">
							<input type="checkbox" name="bidsincludeacquisition" value="1" <? if ($bidsincludeacquisition == 1) { echo "checked"; } ?>>
							<label data-html="<div class='header'>Hello</div>">Include acquisition in filename</label>
						</div>
					</div>

					<div class="field">
						<label>BIDS phase encoding direction <i class="question circle outline icon" title="BIDS 'dir-' filename option. Can be: AP, PA, LR, RL"></i></label>
						<input type="text" name="bidspedirection" value="<?=$bidspedirection?>" style="font-family:monospace">
					</div>
				
					<input type="submit" value="Update" class="ui primary button">
				</form>
			</div>
		</div>
		<?
	}
	
	


	/* -------------------------------------------- */
	/* ------- SetSeriesHidden -------------------- */
	/* -------------------------------------------- */
	/* hide ($hidden = 1) or unhide (0) the selected series of this study */
	function SetSeriesHidden($studyid, $seriesids, $hidden) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$t = GetSeriesTable($study['study_modality']);
		if ($t === null) { Error("This study's modality has no series table"); return; }
		list($table, $idcol) = $t;
		$hidden = $hidden ? 1 : 0;

		$sids = GetStudySeriesIDs($study, $seriesids);
		if (count($sids) == 0) {
			Error("No series from this study were selected");
			return;
		}

		$sqlstring = "update `$table` set ishidden = ? where `$idcol` = ?";
		foreach ($sids as $seriesid) {
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $hidden, $seriesid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$hidden, $seriesid]);
			mysqli_stmt_close($stmt);
		}

		Notice(count($sids) . " series " . ($hidden ? "hidden" : "unhidden"));
	}


	/* -------------------------------------------- */
	/* ------- UpdateGenericSeries ---------------- */
	/* -------------------------------------------- */
	/* returns the series' study ID (for the redirect), or 0 */
	function UpdateGenericSeries($seriesid, $modality, $protocol, $series_datetime, $notes) {
		$seriesid = (int)$seriesid;
		$studyid = GetSeriesStudyID($modality, $seriesid);
		if ($studyid == 0) {
			Error("Series not found");
			return 0;
		}
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return $studyid;

		/* the series must be of the study's modality */
		if (strtolower($study['study_modality']) != strtolower($modality)) {
			Error("Series modality does not match the study");
			return $studyid;
		}
		list($table, $idcol) = GetSeriesTable($modality);

		$series_datetime = FormatStudyDateTime($series_datetime);
		if ($series_datetime == "") {
			Error("Invalid series date/time. Series not updated");
			return $studyid;
		}
		$protocol = trim($protocol ?? '');
		$notes = trim($notes ?? '');

		$sqlstring = "update `$table` set series_datetime = ?, series_protocol = ?, series_notes = ? where `$idcol` = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssi', $series_datetime, $protocol, $notes, $seriesid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$series_datetime, $protocol, $notes, $seriesid]);
		mysqli_stmt_close($stmt);

		Notice("Series updated");
		return $studyid;
	}


	/* -------------------------------------------- */
	/* ------- DeleteConfirm ---------------------- */
	/* -------------------------------------------- */
	function DeleteConfirm($studyid) {
		/* deleting a study is only offered to admins */
		$study = CheckStudyPerm($studyid, 'modifydata', true);
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		?>
		<div align="center" class="message">
		<b>Are you absolutely sure you want to delete this study?</b><img src="images/chili24.png">
		<br><br>
		<span><?=htmlspecialchars($study['uid'] . $study['study_num'])?></span> collected on <?=htmlspecialchars($study['study_datetime'] ?? '')?>
		<br><br>
		<table width="100%">
			<tr>
				<td align="center" width="50%"><a href="studies.php?studyid=<?=$studyid?>" class="ui button">Back</a></td>
				<td align="center">
					<form method="post" action="studies.php">
					<input type="hidden" name="action" value="delete">
					<input type="hidden" name="studyid" value="<?=$studyid?>">
					<?=CSRFTokenField()?>
					<input type="submit" value="Yes, delete it" class="ui red button">
					</form>
				</td>
			</tr>
		</table>
		</div>
		<br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- Delete ----------------------------- */
	/* -------------------------------------------- */
	/* returns the study's subject ID (for the redirect), or 0 */
	function Delete($studyid) {
		$study = CheckStudyPerm($studyid, 'modifydata', true);
		if ($study === null) return 0;
		$studyid = (int)$study['study_id'];
		$uid = $study['uid'];
		$study_num = (int)$study['study_num'];

		/* rename (not delete) the study's archive directory */
		if (($uid != "") && ($study_num > 0)) {
			$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num";
			if (is_dir($archivepath)) {
				rename($archivepath, $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num-" . time());
			}
		}

		$sqlstring = "delete from studies where study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $studyid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
		mysqli_stmt_close($stmt);

		Notice("Study " . htmlspecialchars($uid . $study_num) . " deleted");
		return (int)$study['subject_id'];
	}


	/* -------------------------------------------- */
	/* ------- QueueMoveStudyToSubject ------------ */
	/* -------------------------------------------- */
	/* queue a move of the study to another (existing) subject; the backend does the move */
	function QueueMoveStudyToSubject($studyid, $newuid) {
		/* only offered to admins */
		$study = CheckStudyPerm($studyid, 'modifydata', true);
		if ($study === null) return;
		$studyid = (int)$study['study_id'];

		if ($newuid == "") { Error("No UID entered"); return; }

		$sqlstring = "select subject_id, uid from subjects where uid = ? and isactive = 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $newuid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newuid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("Subject [" . htmlspecialchars($newuid) . "] not found");
			return;
		}
		if ((int)$row['subject_id'] == (int)$study['subject_id']) {
			Error("The study already belongs to subject " . htmlspecialchars($row['uid']));
			return;
		}
		$newuid = $row['uid'];

		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, data_destination, username, requestdate) values ('move', 'study', ?, ?, ?, now())";
		$params = [$studyid, $newuid, $GLOBALS['username']];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iss', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Study queued for move to " . htmlspecialchars($newuid));
	}


	/* -------------------------------------------- */
	/* ------- MoveStudyToProject ----------------- */
	/* -------------------------------------------- */
	/* move the study to the subject's enrollment in another project */
	function MoveStudyToProject($studyid, $newprojectid) {
		/* only offered to admins */
		$study = CheckStudyPerm($studyid, 'modifydata', true);
		if ($study === null) return;
		$studyid = (int)$study['study_id'];
		$subjectid = (int)$study['subject_id'];
		$newprojectid = (int)$newprojectid;

		/* the subject must already be enrolled in the new project */
		$sqlstring = "select a.enrollment_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.project_id = ? and a.subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $newprojectid, $subjectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newprojectid, $subjectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("The subject is not enrolled in that project. Enroll the subject first");
			return;
		}
		$enrollmentid = (int)$row['enrollment_id'];

		$sqlstring = "update studies set enrollment_id = ? where study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $studyid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid, $studyid]);
		mysqli_stmt_close($stmt);

		Notice("Study moved to project " . htmlspecialchars($row['project_name'] ?? ''));
	}


	/* -------------------------------------------- */
	/* ------- ResetStudyQC ----------------------- */
	/* -------------------------------------------- */
	/* reset basic QC ($type 'basic') or mriqc ('mriqc') for the selected MR series of this study */
	function ResetStudyQC($studyid, $seriesids, $type) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		if (strtolower($study['study_modality']) != "mr") {
			Error("QC can only be reset for MR series");
			return;
		}

		$sids = GetStudySeriesIDs($study, $seriesids);
		if (count($sids) == 0) {
			Error("No series from this study were selected");
			return;
		}

		if ($type == "mriqc") {
			ResetMRIQC($sids);
		}
		else {
			ResetQA($sids);
			Notice("Basic QC reset for " . count($sids) . " series. It will be regenerated");
		}
	}


	/* -------------------------------------------- */
	/* ------- SubmitMiniPipelines ---------------- */
	/* -------------------------------------------- */
	/* queue mini-pipeline jobs. From the study page each series has its own mini-pipeline
	   ($minipipelineids, same index as $seriesids); from search.php one mini-pipeline
	   ($minipipelineid) is run on every selected series. Mini-pipelines write observations into
	   their project, so each series needs Edit Data on its project, and the mini-pipeline must
	   belong to that same project */
	function SubmitMiniPipelines($modality, $seriesids, $minipipelineids, $minipipelineid) {
		$t = GetSeriesTable($modality);
		if ($t === null) {
			Error("Invalid modality [" . htmlspecialchars($modality ?? '') . "]. No mini-pipeline jobs submitted");
			return;
		}
		list($table, $idcol) = $t;
		$modality = strtolower(trim($modality));
		if (!is_array($minipipelineids)) $minipipelineids = array();
		$minipipelineid = (int)$minipipelineid;

		/* series => mini-pipeline */
		$jobs = array();
		foreach ($seriesids as $key => $sid) {
			$sid = (int)$sid;
			$mpid = ($minipipelineid > 0) ? $minipipelineid : (int)($minipipelineids[$key] ?? 0);
			if (($sid > 0) && ($mpid > 0))
				$jobs[] = array($sid, $mpid);
		}
		if (count($jobs) == 0) {
			Error("No series with a mini-pipeline selected. No jobs submitted");
			return;
		}

		/* the project of each series */
		$sids = array_values(array_unique(array_map(function($j) { return $j[0]; }, $jobs)));
		$placeholders = implode(',', array_fill(0, count($sids), '?'));
		$sqlstring = "select a.`$idcol` 'id', c.project_id from `$table` a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.`$idcol` in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($sids)), ...$sids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $sids);
		$seriesproject = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriesproject[(int)$row['id']] = (int)$row['project_id'];
		}
		mysqli_stmt_close($stmt);
		$perms = GetCurrentUserProjectPermissions(array_values($seriesproject));

		/* the project of each mini-pipeline */
		$mpids = array_values(array_unique(array_map(function($j) { return $j[1]; }, $jobs)));
		$placeholders = implode(',', array_fill(0, count($mpids), '?'));
		$sqlstring = "select minipipeline_id, project_id from minipipelines where minipipeline_id in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($mpids)), ...$mpids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $mpids);
		$mpproject = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mpproject[(int)$row['minipipeline_id']] = (int)$row['project_id'];
		}
		mysqli_stmt_close($stmt);

		$n = 0;
		$skipped = 0;
		$sqlstring = "insert into minipipeline_jobs (minipipeline_id, mp_modality, mp_seriesid, mp_status, mp_queuedate) values (?, ?, ?, 'pending', now())";
		foreach ($jobs as $job) {
			list($sid, $mpid) = $job;
			$projectid = $seriesproject[$sid] ?? 0;
			if (($projectid == 0) || (($mpproject[$mpid] ?? -1) != $projectid) || !GetPerm($perms, 'modifydata', $projectid)) {
				$skipped++;
				continue;
			}
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'isi', $mpid, $modality, $sid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$mpid, $modality, $sid]);
			mysqli_stmt_close($stmt);
			$n++;
		}

		if ($n > 0)
			Notice("$n mini-pipeline job(s) submitted");
		if ($skipped > 0)
			Error("$skipped series skipped: the series wasn't found, the mini-pipeline belongs to a different project, or you don't have Edit Data permission on the series' project");
	}


	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineForm ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineForm($studyid, $seriesids) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$studyid = (int)$study['study_id'];
		$modality = strtolower($study['study_modality']);
		$projectid = (int)$study['project_id'];

		$rows = GetStudySeriesRows($study, $seriesids);
		if (count($rows) == 0) {
			Error("No series from this study were selected");
			return;
		}

		$mpselectbox = "<select name='minipipelineids[]'><option value='0' selected>(none)</option>";
		$sqlstring = "select minipipeline_id, mp_version, mp_name from minipipelines where project_id = ? order by mp_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mpselectbox .= "<option value='" . (int)$row['minipipeline_id'] . "'>" . htmlspecialchars($row['mp_name']) . " (v" . htmlspecialchars($row['mp_version']) . ")</option>";
		}
		mysqli_stmt_close($stmt);
		$mpselectbox .= "</select>";
		?>
		<form method="post" action="studies.php">
		<input type="hidden" name="action" value="submitminipipelines">
		<input type="hidden" name="studyid" value="<?=$studyid?>">
		<input type="hidden" name="modality" value="<?=htmlspecialchars($modality)?>">
		<?=CSRFTokenField()?>
		<table class="ui very compact celled grey table">
			<thead>
				<th>Series</th>
				<th>Desc</th>
				<th>Mini-pipeline</th>
			</thead>
			<tbody>
			<?
			foreach ($rows as $row) {
				$seriesdesc = $row['series_desc'] ?? '';
				if ($seriesdesc == "")
					$seriesdesc = $row['series_protocol'] ?? '';
				?>
				<tr>
					<td><input type="hidden" name="seriesids[]" value="<?=$row['_id']?>"><?=htmlspecialchars($row['series_num'])?></td>
					<td><?=htmlspecialchars($seriesdesc)?></td>
					<td><?=$mpselectbox?></td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<a href="studies.php?studyid=<?=$studyid?>" class="ui button">Cancel</a>
		<input type="submit" value="Run mini-pipelines" class="ui primary button">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudyForm ------------------- */
	/* -------------------------------------------- */
	function DisplayStudyForm($studyid) {
		$row = CheckStudyPerm($studyid, 'modifydata');
		if ($row === null) return;
		$studyid = (int)$row['study_id'];
		$study_modality = $row['study_modality'];

		/* everything below is displayed in HTML */
		$row = array_map(function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }, $row);
		$enrollmentid = $row['enrollment_id'];
		$equipmentid = $row['equipment_id'];
		$study_num = $row['study_num'];
		$study_alternateid = $row['study_alternateid'];
		$study_datetime = $row['study_datetime'];
		$study_ageatscan = $row['study_ageatscan'];
		$study_height = $row['study_height'];
		$study_weight = $row['study_weight'];
		$study_type = $row['study_type'];
		$study_daynum = $row['study_daynum'];
		$study_timepoint = $row['study_timepoint'];
		$study_operator = $row['study_operator'];
		$study_physician = $row['study_performingphysician'];
		$study_site = $row['study_site'];
		$study_notes = $row['study_notes'];
		$study_doradread = $row['study_doradread'];
		$study_radreaddate = $row['study_radreaddate'];
		$study_radreadfindings = $row['study_radreadfindings'];
		$study_etsnellenchart = $row['study_etsnellenchart'];
		$study_etvergence = $row['study_etvergence'];
		$study_ettracking = $row['study_ettracking'];
		$study_snpchip = $row['study_snpchip'];
		$study_experimenter = $row['study_experimenter'];
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectid = $row['project_id'];
		$projectname = $row['project_name'];

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		
		$formaction = "update";
		$formtitle = "Updating study $study_num";
		$submitbuttonlabel = "Update";
		
		//if (($study_radreaddate == "") || ($study_radreaddate == "0000-00-00 00:00:00")) { $study_radreaddate = date('Y-m-d h:i:s'); }
		
	?>
		<div class="ui center aligned container">
			<div class="ui massive breadcrumb">
				<a href="projects.php?id=<?=$projectid?>" class="section"><?=$projectname?></a>
				<i class="right angle icon divider"></i>
				<a href="subjects.php?id=<?=$subjectid?>" class="section"><?=$uid?></a>
				<i class="right angle icon divider"></i>
				<a href="studies.php?id=<?=$studyid?>" class="active section">Study <?=$study_num?></a>
			</div>
			<? DisplayPermissions($perms); ?>
		</div>
		
		<br><br>
		<div class="ui text container">
			<div class="ui top attached secondary segment">
				<h3 class="ui header"><?=$formtitle?></h3>
			</div>
			<div class="ui bottom attached segment">
				<form method="post" action="studies.php" class="ui form">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<?=CSRFTokenField()?>
				<input type="hidden" name="studyid" value="<?=$studyid?>">
				<div class="two fields">
					<div class="field">
						<label>Modality</label>
						<select name="modality" class="ui dropdown">
						<?
							$sqlstring = "select * from modalities order by mod_desc";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$mod_code = htmlspecialchars($row['mod_code']);
								$mod_desc = htmlspecialchars($row['mod_desc'] ?? '');
								if ($row['mod_code'] == $study_modality) { $selected = "selected"; } else { $selected = ""; }
								?><option value="<?=$mod_code?>" <?=$selected?>><?=$mod_desc?></option><?
							}
						?>
						</select>					
					</div>
					<div class="field">
						<label>Date/time</label>
						<input type="text" name="studydatetime" value="<?=$study_datetime?>" required>
					</div>
				</div>

				<div class="three fields">
					<div class="field">
						<label>Age</label>
						<div class="ui right labeled input">
							<input type="text" name="studyageatscan" value="<?=$study_ageatscan?>">
						    <div class="ui label">years</div>
						</div>
					</div>
					<div class="field" title="Height in <b>Meters</b>">
						<label>Height</label>
						<div class="ui right labeled input">
							<input type="text" name="studyheight" value="<?=$study_height?>" size="4">
						    <div class="ui yellow label">m</div>
						</div>
					</div>
					<div class="field" title="Weight in <b>Kilograms</b>">
						<label>Weight</label>
						<div class="ui right labeled input">
							<input type="text" name="studyweight" value="<?=$study_weight?>" size="4">
						    <div class="ui yellow label">kg</div>
						</div>
					</div>
				</div>
				
				<div class="ui segment">
					<h3 class="ui header">
						Repeated studies
						<div class="sub header">For clinical trials and tracking multiple sessions</div>
					</h3>
					<div class="ui three fields">
						<div class="field">
							<label>Visit type</label>
							<input type="text" name="studytype" value="<?=$study_type?>">
						</div>
						<div class="field">
							<label>Day number</label>
							<input type="text" name="studydaynum" value="<?=$study_daynum?>">
						</div>
						<div class="field">
							<label>Time number</label>
							<input type="text" name="studytimepoint" value="<?=$study_timepoint?>">
						</div>
					</div>
				</div>
				
				<div class="ui three fields">
					<div class="field">
						<label>Operator</label>
						<input type="text" name="studyoperator" value="<?=$study_operator?>">
					</div>
					<div class="field">
						<label>Performing physician</label>
						<input type="text" name="studyphysician" value="<?=$study_physician?>">
					</div>
					<div class="field">
						<label>Site</label>
						<input type="text" name="studysite" value="<?=$study_site?>">
					</div>
				</div>

				<div class="field">
					<label>Notes</label>
					<textarea name="studynotes" cols="30" rows="5"><?=$study_notes?></textarea>
				</div>

				<? if (strtolower($study_modality) == "mr") { ?>
					<div class="ui three fields">
						<div class="field">
							<label>Radiological read done?</label>
							<input type="checkbox" class="ui checkbox" name="studydoradread" value="1" <? if ($study_doradread) {echo "checked";} ?>>
						</div>
						<div class="field">
							<label>Radiological read date</label>
							<input type="text" name="studyradreaddate" value="<?=$study_radreaddate?>">
						</div>
						<div class="field">
							<label>Radiological read findings</label>
							<input type="text" name="studyradreadfindings" value="<?=$study_radreadfindings?>">
						</div>
					</div>
				<? } elseif (strtolower($study_modality) == "et") { ?>
					<div class="ui three fields">
						<div class="field">
							<label>Snellen chart</label>
							<input type="text" size="8" name="studyetsnellchart" value="<?=$study_etsnellenchart?>">
						</div>
						<div class="field">
							<label>Vergence</label>
							<input type="text" name="studyetvergence" value="<?=$study_etvergence?>">
						</div>
						<div class="field">
							<label>Tracking</label>
							<input type="text" name="studyettracking" value="<?=$study_ettracking?>">
						</div>
					</div>
				<? } elseif (strtolower($study_modality) == "snp") { ?>
					<div class="field">
						<label>SNP chip</label>
						<input type="text" size="35" name="studysnpchip" value="<?=$study_snpchip?>">
					</div>
				<? } ?>

				<div class="two fields">
					<div class="field">
						<label>Alternate ID</label>
						<input type="text" name="studyaltid" value="<?=$study_alternateid?>">
					</div>
					<div class="field">
						<label>Experimenter</label>
						<input type="text" name="studyexperimenter" <? if ($study_experimenter == "") { echo "style='color:red'"; } ?> value="<? if ($study_experimenter != "") { echo $study_experimenter; } else { echo htmlspecialchars($GLOBALS['username']); } ?>">
					</div>
				</div>
				
				<div class="ui two column grid">
					<div class="column">
					</div>
					<div class="right aligned column">
						<a href="studies.php?studyid=<?=$studyid?>" class="ui button">Cancel</a>
						<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
					</div>
				</div>
				
				</form>
			</div>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudy ----------------------- */
	/* -------------------------------------------- */
	function DisplayStudy($studyid, $displayfiles=false) {
		if (!ValidID($studyid,'Study ID')) { return; }
		$studyid = (int)$studyid;
	
		/* View Data on the study's project is needed to see the study at all */
		$row = CheckStudyPerm($studyid, 'viewdata');
		if ($row !== null) {
			$rawmodality = $row['study_modality'] ?? '';
			$rawdatetime = $row['study_datetime'];
			$rawradreaddate = $row['study_radreaddate'];
			$row_uid = $row['uid'] ?? '';
			/* everything below is displayed in HTML */
			$row = array_map(function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }, $row);
			$study_id = $row['study_id'];
			$enrollmentid = $row['enrollment_id'];
			//$equipmentid = $row['equipment_id'];
			$study_num = $row['study_num'];
			$study_alternateid = $row['study_alternateid'];
			$study_modality = $row['study_modality'];
			$study_datetime = $row['study_datetime'];
			$study_ageatscan = (float)$row['study_ageatscan'];
			$study_height = (float)$row['study_height'];
			$study_weight = (float)$row['study_weight'];
			$study_type = $row['study_type'];
			$study_daynum = $row['study_daynum'];
			$study_timepoint = $row['study_timepoint'];
			$study_operator = $row['study_operator'];
			$study_physician = $row['study_performingphysician'];
			$study_site = $row['study_site'];
			$study_notes = $row['study_notes'];
			$study_doradread = $row['study_doradread'];
			$study_radreaddate = $row['study_radreaddate'];
			$study_radreadfindings = $row['study_radreadfindings'];
			$study_etsnellenchart = $row['study_etsnellenchart'];
			$study_etvergence = $row['study_etvergence'];
			$study_ettracking = $row['study_ettracking'];
			$study_snpchip = $row['study_snpchip'];
			$study_status = $row['study_status'];
			$study_alternateid = $row['study_alternateid'];
			$study_experimenter = $row['study_experimenter'];
			$study_desc = $row['study_desc'];
			$study_createdby = $row['study_createdby'];
			$study_createdate = $row['study_createdate'];
			$uid = $row['uid'];
			$subjectid = trim($row['subject_id']);
			$costcenter = $row['project_costcenter'];
			$projectid = $row['project_id'];
			$project_name = $row['project_name'];
			
			$ft1 = floor($study_height/0.3048);
			$ft2 = (($study_height/0.3048)-$ft1)*12;
			$in = number_format($ft2,1);
			
			if (($study_height == 0) || ($study_weight == 0)) {
				$bmi = 0;
			}
			else {
				$bmi = $study_weight / ( $study_height * $study_height);
			}
			
			$study_heightft = "$ft1' $in\"";
		}
		else {
			return;
		}
		
		if (($subjectid == 0) || ($subjectid == "")) {
			Error("Invalid subject ID. Unable to display this study because the subject could not be found");
			return;
		}

		if ($rawmodality == "") {
			$study_modality = "Missing modality"; $class="missing";
		}
		else {
			if (GetSeriesTable($rawmodality) !== null) {
				$class = "value";
			}
			else {
				$study_modality = "Invalid modality [$study_modality]"; $class="missing";
			}
		}

		$dbstudydatetime = date('Y-m-d\TH:i:s', strtotime($rawdatetime ?? ''));
		$study_datetime = date("F j, Y g:ia",strtotime($rawdatetime ?? ''));
		$study_radreaddate = (($rawradreaddate ?? '') == "") ? "" : date("F j, Y g:ia",strtotime($rawradreaddate));

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		$canmodify = GetPerm($perms, 'modifydata', $projectid);
		
		/* update the mostrecent table */
		UpdateMostRecent('', $studyid, '');

		?>
		
		<style>
		#preview{
			position:absolute;
			border:1px solid #ccc;
			background:gray;
			padding:0px;
			display:none;
			color:#fff;
			}
		</style>
		
		<div class="ui center aligned container">
			<div class="ui massive breadcrumb">
				<a href="projects.php?id=<?=$projectid?>" class="section"><?=$project_name?></a>
				<i class="right angle icon divider"></i>
				<a href="subjects.php?id=<?=$subjectid?>" class="section"><?=$uid?></a>
				<i class="right angle icon divider"></i>
				<a href="studies.php?id=<?=$studyid?>" class="active section">Study <?=$study_num?></a>
			</div>
			<? DisplayPermissions($perms); ?>
		</div>
		
		<br>
		
		<div class="ui grid">
			<div class="three wide column">
			
				<!-- left side study information box -->
				<div class="ui top attached segment inverted header">
					<h3 class="ui header">Study information</h3>
				</div>
				<div class="ui bottom attached segment">
					<table class="ui very basic very compact celled table" width="100%">
						<tr>
							<td class="right aligned"><b>Study number</td>
							<td><?=$study_num?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Study ID</td>
							<td class="tt"><?=$uid?><?=$study_num?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Alternate Study ID</td>
							<td class="tt"><?=$study_alternateid?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Modality</td>
							<td class="<?=$class?>"><?=$study_modality?></td>
						</tr>
					<? if ((strtolower($study_modality) == "mr") || !$canmodify) { ?>
						<tr>
							<td class="right aligned"><b>Date/time</td>
							<td><?=$study_datetime?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Visit type</td>
							<td><?=$study_type?></td>
						</tr>
					<? } else { ?>
						<tr>
							<td colspan="2">
								<div class="ui styled segment">
									<form id="Sform" action="studies.php" method="post" class="ui form">
									<input type="hidden" name="action" value="saveme">
									<input type="hidden" name="studyid" value="<?=$studyid?>">
									<?=CSRFTokenField()?>
									<div class="inline field">
										<label>Date/time</label>
										<input type="datetime-local" value="<?=$dbstudydatetime;?>" name="studydatetime" required>
									</div>
									<div class="inline field">
										<div class="ui checkbox">
											<input type="checkbox" name="Sdate">
											<label>Copy <b>Date/time</b> value to all series</label>
										</div>
									</div>
									<div class="inline field">
										<label>Visit type</label>
										<input type="text" class="ui input" name="studytype" value="<?=$study_type?>" size="30" placeholder="Visit type">
									</div>
									<div class="inline field">
										<label>Visit number</label>
										<input type="text" class="ui input" name="studydaynum" value="<?=$study_daynum?>" size="30" placeholder="Day number">
									</div>
									<div class="inline field">
										<label>Visit time point</label>
										<input type="text" class="ui input" name="studytimepoint" value="<?=$study_timepoint?>" size="30" placeholder="Time point">
									</div>
									<input type="submit" class="ui small basic blue button" value="Quick Update">
									</form>
								</div>
							</td>
						</tr>
					<? } ?>
						<tr>
							<td class="right aligned"><b>Day</td>
							<td class="right marked orange"><?=$study_daynum?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Timepoint</td>
							<td class="right marked orange"><?=$study_timepoint?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Age at scan</td>
							<td><?=number_format($study_ageatscan,1)?> y</td>
						</tr>
						<tr>
							<td class="right aligned"><b>Height</td>
							<td><?=number_format($study_height,2)?> m <span class="tiny">(<?=$study_heightft?>)</span></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Weight</td>
							<td><?=number_format($study_weight,1)?> kg <span class="tiny">(<?=number_format($study_weight*2.20462,1)?> lbs)</span></td>
						</tr>
						<tr>
							<td class="right aligned"><b>BMI</td>
							<td><?=number_format($bmi,1)?> <span class="tiny">kg/m<sup>2</sup></span></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Visit type</td>
							<td><?=$study_type?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Description</td>
							<td><?=$study_desc?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Operator</td>
							<td><?=$study_operator?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Performing physician</td>
							<td><?=$study_physician?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Site</td>
							<td><?=$study_site?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Notes</td>
							<td><?=$study_notes?></td>
						</tr>
						<? if (strtolower($study_modality) == "mr") { ?>
							<tr>
								<td class="right aligned"><b>Radiological read?</td>
								<td><? if ($study_doradread) { echo "Yes"; } else { echo "No"; } ?></td>
							</tr>
							<tr>
								<td class="right aligned"><b>Rad. read date</td>
								<td><?=$study_radreaddate?></td>
							</tr>
							<tr>
								<td class="right aligned"><b>Rad. read findings</td>
								<td><?=$study_radreadfindings?></td>
							</tr>
						<? } elseif (strtolower($study_modality) == "et") { ?>
							<tr>
								<td class="right aligned"><b>Snellen chart</td>
								<td><?=$study_etsnellenchart?></td>
							</tr>
							<tr>
								<td class="right aligned"><b>Vergence</td>
								<td><?=$study_etvergence?></td>
							</tr>
							<tr>
								<td class="right aligned"><b>Tracking</td>
								<td><?=$study_ettracking?></td>
							</tr>
						<? } elseif (strtolower($study_modality) == "snp") { ?>
							<tr>
								<td class="right aligned"><b>SNP chip</td>
								<td><?=$study_snpchip?></td>
							</tr>
						<? } ?>
						<tr>
							<td class="right aligned"><b>Status</b></td>
							<td><?=$study_status?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Created by</td>
							<td><?=$study_createdby?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Import/upload date</td>
							<td><?=$study_createdate?></td>
						</tr>
						<tr>
							<td class="right aligned"><b>Experimenter</td>
							<td><?=$study_experimenter?></td>
						</tr>
					</table>

					<? if ($canmodify) { ?>
					<a href="studies.php?action=editform&studyid=<?=$studyid?>" class="ui primary basic fluid button"><i class="edit icon"></i> Edit study</a>
					<br>
					<? } ?>
					<a href="packages.php?action=addobject&objecttype=study&objectids[]=<?=$studyid?>" class="ui primary basic brown fluid button"><em data-emoji=":chipmunk:"></em> Add to Package</a>
					<br>
					<div class="ui primary basic fluid button" onclick="$('#addToGroupModal').modal('show')"><i class="user friends icon"></i> Add to group</div>

					<div class="ui small modal" id="addToGroupModal">
						<div class="header">Add study to group</div>
						<div class="content">
							<?
								/* list study groups, flagging those this study already belongs to (same matching as AddStudyToGroup) */
								$groupmodality = strtolower($study_modality ?? '');
								$groupstudyid = (int)$studyid;
								$sqlstring = "select a.group_id, a.group_name, count(b.subjectgroup_id) 'ingroup' from groups a left join group_data b on b.group_id = a.group_id and b.data_id = ? and b.modality = ? where a.group_type = 'study' group by a.group_id, a.group_name order by a.group_name";
								$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
								mysqli_stmt_bind_param($stmt, 'is', $groupstudyid, $groupmodality);
								$groupresult = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$groupstudyid, $groupmodality]);
								$numstudygroups = mysqli_num_rows($groupresult);
							?>
							<form action="studies.php" method="post" id="addToGroupForm" class="ui form">
								<input type="hidden" name="action" value="addtogroup">
								<?=CSRFTokenField()?>
								<input type="hidden" name="studyid" value="<?=(int)$studyid?>">
								<div class="field">
									<label>Study</label>
									<b class="tt"><?=htmlspecialchars($uid . $study_num)?></b>
								</div>
								<div class="field">
									<label>Group</label>
									<? if ($numstudygroups > 0) { ?>
									<select name="studygroupid" class="ui fluid dropdown" required>
										<option value="">Select a study group...</option>
										<?
											while ($grouprow = mysqli_fetch_array($groupresult, MYSQLI_ASSOC)) {
												if ($grouprow['ingroup'] > 0) {
													?><option value="<?=(int)$grouprow['group_id']?>" disabled><?=htmlspecialchars($grouprow['group_name'])?> (already in group)</option><?
												}
												else {
													?><option value="<?=(int)$grouprow['group_id']?>"><?=htmlspecialchars($grouprow['group_name'])?></option><?
												}
											}
											mysqli_stmt_close($stmt);
										?>
									</select>
									<? } else { ?>
									No study groups exist. <a href="groups.php">Create a study group</a> first.
									<? } ?>
								</div>
							</form>
						</div>
						<div class="actions">
							<div class="ui cancel button">Cancel</div>
							<? if ($numstudygroups > 0) { ?>
							<button type="submit" form="addToGroupForm" class="ui primary button">Add to group</button>
							<? } ?>
						</div>
					</div>

					<? if (IsAdminUser()) { ?>
						<script>
							$(document).ready(function() {
								$('#popupbutton1').popup({
									popup : $('#popupmenu1'),
									on : 'click'
								});
							});
						</script>
						
						<br><br>
						<div class="ui fluid basic red button" id="popupbutton1"><i class="tools icon"></i> Operations...</div>
						
						<div class="ui wide popup" id="popupmenu1" style="width: 400px">
							<a href="merge.php?action=mergestudyform&studyid=<?=$studyid?>" class="ui fluid primary button"><i class="random icon"></i> Merge study with...</a>
							
							<br>
							
							<form action="studies.php" method="post">
								<input type="hidden" name="studyid" value="<?=$studyid?>">
								<input type="hidden" name="action" value="movestudytosubject">
								<?=CSRFTokenField()?>
								<b>Move study to existing UID...</b>
								<div class="ui fluid inline action input">
									<input type="text" size="10" name="newuid" id="newuid" placeholder="existing UID" required>
									<button class="ui attached primary button" onClick="this.submit();">Move</button>
								</div>
							</form>
							
							<form action="studies.php" method="post">
								<input type="hidden" name="studyid" value="<?=$studyid?>">
								<input type="hidden" name="action" value="movestudytoproject">
								<?=CSRFTokenField()?>
								<b>Move study to new project...</b>
								<div class="ui fluid labeled inline action input">
									<select name="newprojectid" class="ui fluid selection dropdown" required>
										<option value="">Select project...</option>
									<?
										$subjectidB = (int)$subjectid;
										$sqlstringB = "select a.project_id, b.project_name, b.project_costcenter from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ?";
										$stmtB = mysqli_prepare($GLOBALS['linki'], $sqlstringB);
										mysqli_stmt_bind_param($stmtB, 'i', $subjectidB);
										$resultB = MySQLiBoundQuery($stmtB, __FILE__, __LINE__, $sqlstringB, [$subjectidB]);
										mysqli_stmt_close($stmtB);
										while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
											$project_id = (int)$rowB['project_id'];
											$project_name = htmlspecialchars($rowB['project_name'] ?? '');
											$project_costcenter = htmlspecialchars($rowB['project_costcenter'] ?? '');
											?>
											<option value="<?=$project_id?>"><?=$project_name?> (<?=$project_costcenter?>)</option>
											<?
										}
									?>
									</select>
									<button class="ui attached primary button" onClick="this.submit();">Move</button>
								</div>
							</form>
							
							<br><br>
							
							<a href="studies.php?action=deleteconfirm&studyid=<?=$studyid?>" class="ui fluid red button" onclick="return confirm('Are you sure you want to delete this study?')"><i class="trash icon"></i> Delete</a>
						</div>
					<? } ?>
				</div> <!-- end bottom attached segment -->
			</div> <!-- end 3-wide column -->
			<div class="thirteen wide column">
				<?
				if ($displayfiles == true) {
					$studypath = $GLOBALS['cfg']['archivedir'] . "/" . $row_uid . "/" . (int)$study_num;
					DisplayFileSeries($studypath, $studyid);
				}
				else {
					$study_modality = strtolower($rawmodality);
					if (($study_modality == "mr") || ($study_modality == "pr")) {
						DisplayMRSeries($studyid, $study_num, $row_uid, $study_modality, $projectid, $subjectid, $canmodify);
					}
					elseif ($study_modality == "ct") {
						DisplayCTSeries($studyid, $study_num, $row_uid, $canmodify);
					}
					else {
						DisplayGenericSeries($studyid, $study_modality, $canmodify);
					}
				}
				?>
			</div> <!-- end 13-wide column -->
		</div> <!-- end ui grid -->
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRSeries -------------------- */
	/* -------------------------------------------- */
	/* called from DisplayStudy, which has already checked View Data. $canmodify (Edit Data) shows the editing controls */
	function DisplayMRSeries($studyid, $study_num, $uid, $modality, $projectid, $subjectid, $canmodify) {
		$studyid = (int)$studyid;
		$projectid = (int)$projectid;
		$subjectid = (int)$subjectid;
		if (!ValidID($studyid,'Study ID')) { return; }
		$t = GetSeriesTable($modality);
		if (($t === null) || !in_array($modality, array('mr', 'pr'))) { Error("Invalid modality"); return; }
		list($seriestable, $idcol) = $t;
		if (!ValidID($study_num,'Studynum')) { return; }
	
		$colors = GenerateColorGradient();

		//$bidsentities['anat'] = array('IRT1','MESE','MEGRE','MP2RAGE','MPM','MTS','MTR','T1map','T2map','T2starmap','R1map','R2map','R2starmap','PDmap','MTRmap','MTsat','UNIT1','T1rho','MWFmap','MTVmap','Chimap','S0map','M0map','T1w','T2w','PDw','T2starw','FLAIR','inplaneT1','inplaneT2','PDT2','angio','T2star','FLASH','PD','VFA','defacemask');
		//$bidsentities['dwi'] = array('dwi','sbref','physio','stim');
		//$bidsentities['fmap'] = array('TB1AFI','TB1TFL','TB1RFM','RB1COR','TB1DAM','TB1EPI','TB1SRGE','TB1map','RB1map','epi','m0scan','phasediff','phase1','phase2','magnitude1','magnitude2','magnitude','magnitude1and2','fieldmap');
		//$bidsentities['func'] = array('bold','cbv','sbref','events','phase','physio','stim');
		//$bidsentities['perf'] = array('asl','m0scan','aslcontext','asllabeling','physio','stim');
		//$bidsentities['derived'] = array('derived');
		//PrintVariable($bidsentities);
		
		$pstats = array();
		if ($modality == "mr") {
			/* get the movement & SNR stats by sequence name, compared within this study's project, and
			   only for the sequences in this study (the whole-database version was too slow) */
			$statsprojectid = $projectid;
			$statsstudyid = (int)$studyid;
			$sqlstring2 = "SELECT b.series_sequencename, max(a.move_maxx) 'maxx', min(a.move_minx) 'minx', max(a.move_maxy) 'maxy', min(a.move_miny) 'miny', max(a.move_maxz) 'maxz', min(a.move_minz) 'minz', avg(a.pv_snr) 'avgpvsnr', avg(a.io_snr) 'avgiosnr', std(a.pv_snr) 'stdpvsnr', std(a.io_snr) 'stdiosnr', min(a.pv_snr) 'minpvsnr', min(a.io_snr) 'miniosnr', max(a.pv_snr) 'maxpvsnr', max(a.io_snr) 'maxiosnr', min(a.motion_rsq) 'minmotion', max(a.motion_rsq) 'maxmotion', avg(a.motion_rsq) 'avgmotion', std(a.motion_rsq) 'stdmotion' FROM mr_qa a join mr_series b on a.mrseries_id = b.mrseries_id join studies c on b.study_id = c.study_id join enrollment d on c.enrollment_id = d.enrollment_id where a.io_snr > 0 and d.project_id = ? and b.series_sequencename in (select series_sequencename from mr_series where study_id = ?) group by b.series_sequencename";
			$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
			mysqli_stmt_bind_param($stmt2, 'ii', $statsprojectid, $statsstudyid);
			$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$statsprojectid, $statsstudyid]);
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$sequence = $row2['series_sequencename'];
				$pstats[$sequence]['rangex'] = abs($row2['minx']) + abs($row2['maxx']);
				$pstats[$sequence]['rangey'] = abs($row2['miny']) + abs($row2['maxy']);
				$pstats[$sequence]['rangez'] = abs($row2['minz']) + abs($row2['maxz']);
				$pstats[$sequence]['avgpvsnr'] = $row2['avgpvsnr'];
				$pstats[$sequence]['stdpvsnr'] = $row2['stdpvsnr'];
				$pstats[$sequence]['minpvsnr'] = $row2['minpvsnr'];
				$pstats[$sequence]['maxpvsnr'] = $row2['maxpvsnr'];
				
				$pstats[$sequence]['avgiosnr'] = $row2['avgiosnr'];
				$pstats[$sequence]['stdiosnr'] = $row2['stdiosnr'];
				$pstats[$sequence]['miniosnr'] = $row2['miniosnr'];
				$pstats[$sequence]['maxiosnr'] = $row2['maxiosnr'];

				$pstats[$sequence]['avgmotion'] = $row2['avgmotion'];
				$pstats[$sequence]['stdmotion'] = $row2['stdmotion'];
				$pstats[$sequence]['minmotion'] = $row2['minmotion'];
				$pstats[$sequence]['maxmotion'] = $row2['maxmotion'];
				
				if ($row2['stdiosnr'] != 0) {
					$pstats[$sequence]['maxstdiosnr'] = ($row2['avgiosnr'] - $row2['miniosnr'])/$row2['stdiosnr'];
				} else { $pstats[$sequence]['maxstdiosnr'] = 0; }
				if ($row2['stdpvsnr'] != 0) {
					$pstats[$sequence]['maxstdpvsnr'] = ($row2['avgpvsnr'] - $row2['minpvsnr'])/$row2['stdpvsnr'];
				} else { $pstats[$sequence]['maxstdpvsnr'] = 0; }
				if ($row2['stdmotion'] != 0) {
					$pstats[$sequence]['maxstdmotion'] = ($row2['avgmotion'] - $row2['minmotion'])/$row2['stdmotion'];
				} else { $pstats[$sequence]['maxstdmotion'] = 0; }
			}
			mysqli_stmt_close($stmt2);
		}
			?>
		
		<script type="text/javascript">
		$(function() {
			$("#seriesall").click(function() {
				var checked_status = this.checked;
				$(".allseries").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
		});
		</script>
		<form method="post" name="serieslist" id="serieslist" action="studies.php">
		<input type="hidden" name="action" value="none">
		<input type="hidden" name="studyid" value="<?=$studyid?>">
		<input type="hidden" name="modality" value="<?=$modality?>">
		<?=CSRFTokenField()?>
		<input type="hidden" name="objecttype" value="series">
		<table class="ui top attached very compact small celled selectable grey table" style="margin: 0px">
			<thead>
				<tr>
					<th>Series</th>
					<th>Upload Beh</th>
					<th>Protocol</th>
					<th title="Time of the start of the series acquisition">Time</th>
					<th>Notes</th>
					<th title="View movement graph and FFT">QA</th>
					<th title="Analyst ratings and notes">Ratings</th>
					<th title="Total displacement in X direction">X</th>
					<th title="Total displacement in Y direction">Y</th>
					<th title="Total displacement in Z direction">Z</th>
					<th title="Per Voxel SNR (timeseries) - Calculated from the fslstats command. Colored relative to the same sequence within this project">PV<br>SNR</th>
					<th title="Inside-Outside SNR - This calculates the brain signal (center of brain-extracted volume) compared to the average of the volume corners. Colored relative to the same sequence within this project">IO<br>SNR</th>
					<th>Sequence</th>
					<th>Length<br><span class="tiny">approx.</span></th>
					<th>TR<br><span class="tiny">ms</span></th>
					<th>Voxel size <br><span class="tiny">(x y z)</span></th>
					<th title="Image dimensions in voxels. If 4D image, <i>t</i> dimension will be the number of BOLD reps">Image dims <br><span class="tiny">(x y z t) in voxels</span></th>
					<th>Files</th>
					<th>Beh</th>
					<th class="center aligned" style="background-color: Lavender;"><span style="font-size: 8pt;">Select</span><br><input type="checkbox" id="seriesall"></th>
				</tr>
			</thead>
			<tbody>
				<?
					/* get the series */
					$sqlstring = "select * from `$seriestable` where study_id = ? order by series_num";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $studyid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
					mysqli_stmt_close($stmt);
					$mrseriesids = array();
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$mrseriesids[] = (int)$row[$idcol];
					}
				
					/* get the rating information */
					if (count($mrseriesids) < 1) {
						?>
						<tr>
							<td colspan="22" align="center">No series found for this study</td>
						</tr>
						<?
					}
					else {
						$sqlstring3 = "select * from ratings where rating_type = 'series' and data_modality = ? and data_id in (" . implode(',', array_fill(0, count($mrseriesids), '?')) . ")";
						$params3 = array_merge(array($modality), $mrseriesids);
						$stmt3 = mysqli_prepare($GLOBALS['linki'], $sqlstring3);
						mysqli_stmt_bind_param($stmt3, 's' . str_repeat('i', count($mrseriesids)), ...$params3);
						$result3 = MySQLiBoundQuery($stmt3, __FILE__, __LINE__, $sqlstring3, $params3);
						mysqli_stmt_close($stmt3);
						while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
							$ratingseriesid = $row3['data_id'];
							$ratings[$ratingseriesid][] = $row3['rating_value'];
						}

						/* go through the series info */
						mysqli_data_seek($result,0);
						$lastseriesnum = 0;
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$mrseries_id = (int)$row[$idcol];
							$series_datetime = date('g:ia',strtotime($row['series_datetime']));
							$protocol = $row['series_protocol'];
							$series_desc = $row['series_desc'];
							$sequence = $row['series_sequencename'];
							$series_num = $row['series_num'];
							$series_tr = $row['series_tr'];
							$series_te = $row['series_te'];
							$series_ti = $row['series_ti'];
							$series_flip = $row['series_flip'];
							$phasedir = $row['phaseencodedir'];
							$phaseangle = $row['phaseencodeangle'];
							$series_spacingx = $row['series_spacingx'];
							$series_spacingy = $row['series_spacingy'];
							$series_spacingz = $row['series_spacingz'];
							$series_fieldstrength = $row['series_fieldstrength'];
							$img_rows = $row['img_rows'];
							$img_cols = $row['img_cols'];
							$img_slices = $row['img_slices'];
							$bold_reps = $row['bold_reps'];
							$dimN = $row['dimN'];
							$dimX = $row['dimX'];
							$dimY = $row['dimY'];
							$dimZ = $row['dimZ'];
							$dimT = $row['dimT'];
							$numfiles = $row['numfiles'];
							$series_size = $row['series_size'];
							$series_status = $row['series_status'];
							$series_notes = $row['series_notes'];
							$beh_size = $row['beh_size'];
							$numfiles_beh = $row['numfiles_beh'];
							$data_type = $row['data_type'];
							$lastupdate = $row['lastupdate'];
							$imagetype = $row['image_type'];
							$image_comments = $row['image_comments'];
							$ishidden = $row['ishidden'];
							$isvalid = $row['is_valid'];
							$validmessage = $row['message'];
							
							if ($series_num - $lastseriesnum > 1) {
								$firstmissing = $lastseriesnum+1;
								$lastmissing = $series_num-1;
								if ($firstmissing == $lastmissing) {
									$missingmsg = $firstmissing;
								}
								else {
									$missingmsg = "$firstmissing - $lastmissing";
								}
								?>
								<tr>
									<td colspan="24" align="center" style="border-top: solid 3px #FF7F7F; border-bottom: solid 3px #FF7F7F; padding:5px">
									<h4 class="ui center aligned header">
										Non-consecutive series numbers. Missing series <?=$missingmsg?>
									</h4>
									</td>
								</tr>
								<?
							}
							
							if (($numfiles_beh == '') || ($numfiles_beh == 0)) {
								/* get the number and size of the beh files */
								$behs = glob($GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/beh/*");
								$numfiles_beh = count($behs);
								$totalsize = 0;
								$beh_size = 0;
								foreach ($behs as $behfile) {
									$beh_size += filesize($behfile);
								}
								if ($numfiles_beh > 0) {
									$sqlstring5 = "update `$seriestable` set beh_size = ?, numfiles_beh = ? where `$idcol` = ?";
									$stmt5 = mysqli_prepare($GLOBALS['linki'], $sqlstring5);
									mysqli_stmt_bind_param($stmt5, 'iii', $beh_size, $numfiles_beh, $mrseries_id);
									MySQLiBoundQuery($stmt5, __FILE__, __LINE__, $sqlstring5, [$beh_size, $numfiles_beh, $mrseries_id]);
									mysqli_stmt_close($stmt5);
								}
							}
							
							if ($phasedir == "COL") { /* A>>P or P>>A */
								if ($phaseangle == 0) {
									$phase = "A >> P";
								}
								elseif ((abs($phaseangle) > 3.1) && (abs($phaseangle) < 3.2)) {
									$phase = "P >> A";
								}
								else {
									$phase = "COL";
								}
							}
							else { /* R>>L or L>>R */
								if (($phaseangle > 1.5) && ($phaseangle < 1.6)) {
									$phase = "R >> L";
								}
								elseif (($phaseangle < -1.5) && ($phaseangle > -1.6)) {
									$phase = "L >> R";
								}
								else {
									$phase = "ROW";
								}
							}
							
							$behdir = "";
							if (trim($protocol) == "") {
								$protocol = "(blank)";
							}
							if (($bold_reps > 1) || ($dimT > 1)) {
								$scanlengthsec = ($series_tr * max($bold_reps, $dimT))/1000.0;
							}
							else {
								if ($series_ti > 0) {
									$scanlengthsec = ($series_ti*$numfiles)/1000.0;
								}
								else {
									$scanlengthsec = ($series_tr * $numfiles)/1000.0;
								}
							}
							if (floor($scanlengthsec/60.0) > 0) {
								$scanlength = floor($scanlengthsec/60.0) . "m " . sprintf("%02d",round(fmod($scanlengthsec,60.0))) . "s";
							}
							else {
								$scanlength = sprintf("%0.2f",fmod($scanlengthsec,60.0)) . "s";
							}
							
							if ( (($dimT > 1) || ($bold_reps > 1)) && ($numfiles_beh < 1)) { $behcolor = "#FFAA7F"; } else { $behcolor = ""; }
							if ($numfiles_beh < 1) { $numfiles_beh = "-"; }

							$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";
							$gifthumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.gif";
							$realignpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/MotionCorrection.txt";

							if ($modality == "mr") {
								$sqlstring2 = "select * from mr_qa where mrseries_id = ?";
								$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
								mysqli_stmt_bind_param($stmt2, 'i', $mrseries_id);
								$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$mrseries_id]);
								$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
								mysqli_stmt_close($stmt2);
								$iosnr = $row2['io_snr'];
								$pvsnr = $row2['pv_snr'];
								$move_minx = $row2['move_minx'];
								$move_miny = $row2['move_miny'];
								$move_minz = $row2['move_minz'];
								$move_maxx = $row2['move_maxx'];
								$move_maxy = $row2['move_maxy'];
								$move_maxz = $row2['move_maxz'];
								$acc_minx = $row2['acc_minx'];
								$acc_miny = $row2['acc_miny'];
								$acc_minz = $row2['acc_minz'];
								$acc_maxx = $row2['acc_maxx'];
								$acc_maxy = $row2['acc_maxy'];
								$acc_maxz = $row2['acc_maxz'];
								$motion_rsq = $row2['motion_rsq'];
								$rangex = abs($move_minx) + abs($move_maxx);
								$rangey = abs($move_miny) + abs($move_maxy);
								$rangez = abs($move_minz) + abs($move_maxz);
								$rangex2 = abs($acc_minx) + abs($acc_maxx);
								$rangey2 = abs($acc_miny) + abs($acc_maxy);
								$rangez2 = abs($acc_minz) + abs($acc_maxz);
								$stdsmotion = 0;

								/* calculate color based on voxel size... red (100) means more than 1 voxel displacement in that direction */
								if ($series_spacingx > 0) {
									$xindex = round(($rangex/$series_spacingx)*100); if ($xindex > 100) { $xindex = 100; }
									$xindex2 = round(($rangex2/$series_spacingx)*100); if ($xindex2 > 100) { $xindex2 = 100; }
								}
								if ($series_spacingy > 0) {
									$yindex = round(($rangey/$series_spacingy)*100); if ($yindex > 100) { $yindex = 100; }
									$yindex2 = round(($rangey2/$series_spacingy)*100); if ($yindex2 > 100) { $yindex2 = 100; }
								}
								if ($series_spacingz > 0) {
									$zindex = round(($rangez/$series_spacingz)*100); if ($zindex > 100) { $zindex = 100; }
									$zindex2 = round(($rangez2/$series_spacingz)*100); if ($zindex2 > 100) { $zindex2 = 100; }
								}
								
								/* get standard deviations from the mean for SNR */
								if ($pstats[$sequence]['stdiosnr'] != 0) {
									if ($iosnr > $pstats[$sequence]['avgiosnr']) {
										$stdsiosnr = 0;
									}
									else {
										$stdsiosnr = (($iosnr - $pstats[$sequence]['avgiosnr'])/$pstats[$sequence]['stdiosnr']);
									}
								}
								if ($pstats[$sequence]['stdpvsnr'] != 0) {
									if ($pvsnr > $pstats[$sequence]['avgpvsnr']) {
										$stdspvsnr = 0;
									}
									else {
										$stdspvsnr = (($pvsnr - $pstats[$sequence]['avgpvsnr'])/$pstats[$sequence]['stdpvsnr']);
									}
								}
								if ($pstats[$sequence]['stdmotion'] != 0) {
									if ($motion_rsq > $pstats[$sequence]['avgmotion']) {
										$stdmotion = 0;
									}
									else {
										$stdmotion = (($motion_rsq - $pstats[$sequence]['avgmotion'])/$pstats[$sequence]['stdmotion']);
									}
								}
								
								if ($pstats[$sequence]['maxstdpvsnr'] == 0) { $pvindex = 100; }
								else { $pvindex = round(($stdspvsnr/$pstats[$sequence]['maxstdpvsnr'])*100); }
								$pvindex = 100 + $pvindex;
								if ($pvindex > 100) { $pvindex = 100; }
								
								if ($pstats[$sequence]['maxstdiosnr'] == 0) { $ioindex = 100; }
								else { $ioindex = round(($stdsiosnr/$pstats[$sequence]['maxstdiosnr'])*100); }
								$ioindex = 100 + $ioindex;
								if ($ioindex > 100) { $ioindex = 100; }
								if ($ioindex < 0) { $ioindex = 0; }
								
								if ($pstats[$sequence]['maxstdmotion'] == 0) { $motionindex = 100; }
								else { $motionindex = round(($stdmotion/$pstats[$sequence]['maxstdmotion'])*100); }
								$motionindex = 100 + $motionindex;
								if ($motionindex > 100) { $motionindex = 100; }
								if ($motionindex < 0) { $motionindex = 0; }
								
								$maxpvsnrcolor = $colors[100-$pvindex];
								$maxiosnrcolor = $colors[100-$ioindex];
								$maxmotioncolor = $colors[100-$motionindex];
								if ($pvsnr <= 0.0001) { $pvsnr = "-"; $maxpvsnrcolor = ""; }
								else { $pvsnr = number_format($pvsnr,2); }
								if ($iosnr <= 0.0001) { $iosnr = "-"; $maxiosnrcolor = ""; }
								else { $iosnr = number_format($iosnr,2); }
								
								/* setup movement colors */
								$maxxcolor = $colors[$xindex];
								$maxycolor = $colors[$yindex];
								$maxzcolor = $colors[$zindex];
								if ($rangex <= 0.0001) { $rangex = "-"; $maxxcolor = ""; }
								else { $rangex = number_format($rangex,2); }
								if ($rangey <= 0.0001) { $rangey = "-"; $maxycolor = ""; }
								else { $rangey = number_format($rangey,2); }
								if ($rangez <= 0.0001) { $rangez = "-"; $maxzcolor = ""; }
								else { $rangez = number_format($rangez,2); }

								/* setup acceleration colors */
								$maxxcolor2 = $colors[$xindex2];
								$maxycolor2 = $colors[$yindex2];
								$maxzcolor2 = $colors[$zindex2];
								if ($rangex2 <= 0.0001) { $rangex2 = "-"; $maxxcolor2 = ""; }
								else { $rangex2 = number_format($rangex2,2); }
								if ($rangey2 <= 0.0001) { $rangey2 = "-"; $maxycolor2 = ""; }
								else { $rangey2 = number_format($rangey2,2); }
								if ($rangez2 <= 0.0001) { $rangez2 = "-"; $maxzcolor2 = ""; }
								else { $rangez2 = number_format($rangez2,2); }
								
								/* format the motion r^2 value */
								if ($motion_rsq == 0) {
									$motion_rsq = '-';
									 $maxmotioncolor = "";
								}
								else {
									$motion_rsq = number_format($motion_rsq,5);
								}
							}
							/* get manually entered QA info */
							$sqlstringC = "select avg(value) 'avgrating', count(value) 'count' from manual_qa where series_id = ? and modality = ?";
							$stmtC = mysqli_prepare($GLOBALS['linki'], $sqlstringC);
							mysqli_stmt_bind_param($stmtC, 'is', $mrseries_id, $modality);
							$resultC = MySQLiBoundQuery($stmtC, __FILE__, __LINE__, $sqlstringC, [$mrseries_id, $modality]);
							$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
							mysqli_stmt_close($stmtC);
							$avgrating = $rowC['avgrating'];
							$ratingcount = $rowC['count'];
							if ($avgrating < 0.5) { $manualqacolor = "black"; }
							if (($avgrating >= 0.5) && ($avgrating < 1.5)) { $manualqacolor = "#FF0000"; }
							if (($avgrating >= 1.5) && ($avgrating <= 3.0)) { $manualqacolor = "#00FF00"; }
							if ($ratingcount < 1) { $manualqacolor = "#EFEFEF"; }
							
							/* check if this is real data, or unusable data based on the ratings, and get rating counts */
							$isbadseries = false;
							$istestseries = false;
							$ratingcount2 = '';
							$hasratings = false;
							$rowcolor = '';

							$ratingavg = "";
							if (isset($ratings)) {
								foreach ($ratings as $key => $ratingarray) {
									if ($key == $mrseries_id) {
										$hasratings = true;
										if (in_array(5,$ratingarray)) {
											$isbadseries = true;
										}
										if (in_array(6,$ratingarray)) {
											$istestseries = true;
										}
										$ratingcount2 = count($ratingarray);
										$ratingavg = array_sum($ratingarray) / count($ratingarray);
										break;
									}
								}
							}
							if ($isbadseries) { $rowcolor = "red"; }
							if ($istestseries) { $rowcolor = "#AAA"; }
							if ($ishidden) { $rowcolor = "#AAA"; }

							/* get BIDS protocol name mapping */
							/* (series description and protocol come from the DICOM headers, so they must be bound) */
							$bidsprotocol = ($series_desc != "") ? $series_desc : $protocol;
							$imagetypeB = $imagetype ?? '';
							$sqlstring3 = "select * from bids_mapping where protocolname = ? and imagetype = ? and modality = ? and project_id = ?";
							$stmt3 = mysqli_prepare($GLOBALS['linki'], $sqlstring3);
							mysqli_stmt_bind_param($stmt3, 'sssi', $bidsprotocol, $imagetypeB, $modality, $projectid);
							$result3 = MySQLiBoundQuery($stmt3, __FILE__, __LINE__, $sqlstring3, [$bidsprotocol, $imagetypeB, $modality, $projectid]);
							$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
							mysqli_stmt_close($stmt3);
							if (!$row3) $row3 = array();
							/* the mapping values are only displayed */
							$row3 = array_map(function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }, $row3);
							$bidsentity = $row3['bidsEntity'] ?? '';
							$bidssuffix = $row3['bidsSuffix'] ?? '';
							$bidsintendedfor = $row3['bidsIntendedForEntity'] ?? '';
							$bidsrun = $row3['bidsRun'] ?? '';
							$bidsautonumberruns = $row3['bidsAutoNumberRuns'] ?? '';
							$bidstask = $row3['bidsTask'] ?? '';
							$bidspedirection = $row3['bidsPEDirection'] ?? '';
							
							$bidstitle = "BIDS&#10;&#10;Entity&nbsp;-&nbsp;$bidsentity&#10;BIDS&nbsp;suffix&nbsp;-&nbsp;$bidssuffix&#10;IntendedFor&nbsp;-&nbsp;$bidsintendedfor&#10;Run&nbsp;-&nbsp;$bidsrun&#10;Autonumber&nbsp;runs&nbsp;-&nbsp;$bidsautonumberruns&#10;Task&nbsp;-&nbsp;$bidstask&#10;PE&nbsp;direction&nbsp;-&nbsp;$bidspedirection";

							/* the text values below come from the DICOM headers or users, and are displayed in HTML */
							$series_desc = htmlspecialchars($series_desc ?? '');
							$protocol = htmlspecialchars($protocol ?? '');
							$sequence_h = htmlspecialchars($sequence ?? '');
							$imagetype = htmlspecialchars($imagetype ?? '');
							$image_comments = htmlspecialchars($image_comments ?? '');
							$series_notes = htmlspecialchars($series_notes ?? '');
							$validmessage = htmlspecialchars($validmessage ?? '', ENT_QUOTES);
							$data_type_h = htmlspecialchars($data_type ?? '', ENT_QUOTES);
							
							?>
							<script type="text/javascript">
							// Popup window code
							function newPopup(url) {
								popupWindow = window.open(url,'popUpWindow','height=700,width=800,left=10,top=10,resizable=yes,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no,status=yes')
							}
							
							$(document).ready(function() {
								const xhttp = new XMLHttpRequest();
								xhttp.onload = function() {
									document.getElementById("series<?=$series_num?>").innerHTML = this.responseText;
								}
								xhttp.open("GET", "ajaxapi.php?action=checkseriesobject&modality=mr&seriesid=<?=$mrseries_id?>&datatype=<?=urlencode($data_type ?? '')?>", true);
								xhttp.send();

								$('.series-info-tooltip').tooltip({ items: '.series-info-tooltip', content: function() { return $(this).attr('data-html'); } });

							//	const xhttp2 = new XMLHttpRequest();
							//	xhttp2.onload = function() {
							//		document.getElementById("thumbnail<?=$series_num?>").innerHTML = this.responseText;
							//	}
							//	xhttp2.open("GET", "ajaxapi.php?action=getseriesthumbnail&modality=mr&seriesid=<?=$mrseries_id?>&datatype=<?=urlencode($data_type ?? '')?>", true);
							//	xhttp2.send();
							});
							</script>
							<tr style="color: <?=$rowcolor?>">
								<td><?=$series_num?> <? if (!$isvalid) { ?> <i class='ui large red exclamation circle icon' title='<?=$validmessage?>'></i><? } ?> <span id="series<?=$series_num?>"></span></td>
								<td><span id="uploader<?=$mrseries_id?>"></span></td>
								<td>
									<a href="series.php?action=scanparams&seriesid=<?=$mrseries_id?>&modality=<?=$modality?>"><?=$series_desc?></a>&nbsp;
									<a href="dicom.php?seriesid=<?=$mrseries_id?>&modality=<?=$modality?>"><em data-emoji=":x_ray:" class="small"></em></a>
									<!--<span id="thumbnail<?=$series_num?>"></span>-->
									<?php $series_tooltip = "<b>Series Description</b> - $series_desc<br><b>Protocol</b> - $protocol<br><b>Sequence Description</b> - $sequence_h<br><b>TE</b> - {$series_te}ms<br><b>Magnet</b> - {$series_fieldstrength}T<br><b>Flip angle</b> - {$series_flip}&deg;<br><b>Image type</b> - $imagetype<br><b>Image comment</b> - $image_comments<br><b>Phase encoding</b> - $phase"; ?>
					<span class="series-info-tooltip" data-html="<?=htmlspecialchars($series_tooltip, ENT_QUOTES)?>"><i class="ui info circle icon"></i></span>
									
									<?
										if ($bidsentity == "") {
											$label = "BIDS...";
											$color = "";
										}
										else {
											$label = "$bidsentity : $bidssuffix";
											$color = "green";
										}
									?>
									<? if ($canmodify) { ?>
									<a class="ui <?=$color?> compact tiny basic button right floated" href="studies.php?action=editbidsmapping&modality=<?=$modality?>&seriesid=<?=$mrseries_id?>" data-html="<?=$bidstitle?>" data-inverted="inverted" data-variation="multiline" data-variation="very wide"><?=$label?></a>
									<? } else { ?>
									<span class="ui <?=$color?> compact tiny basic label right floated" data-html="<?=$bidstitle?>"><?=$label?></span>
									<? } ?>
								</td>
								<td style="font-size:8pt"><?=$series_datetime?></td>
								<td style="font-size:8pt"><?=$series_notes;?></td>
								<td class="seriesrow" style="padding: 0px 5px;">
									<a href="JavaScript:newPopup('mrseriesqa.php?id=<?=$mrseries_id?>');"><i class="chart bar icon" title="View QA results, including movement correction"></i></a>
								</td>
								<td class="seriesrow" style="padding: 0px 5px;">
									<span style="font-size:7pt"><?=$ratingavg;?></span>
									<div id="popup" style="display:none; min-width:800px; min-height:400px"></div>
									<? if ($hasratings) { $image = "red"; } else { $image = "grey"; } ?>
									<a href="JavaScript:newPopup('ratings.php?id=<?=$mrseries_id?>&type=series&modality=mr');"><i class="<?=$image?> comment dots icon" title="View ratings"></i></a>
								</td>
								<td class="seriesrow" align="right" style="padding:0px;margin:0px;">
									<table cellspacing="0" cellpadding="1" height="100%" width="100%" class="movementsubtable">
										<tr><td title="Total X displacement" class="mainval" style="background-color: <?=$maxxcolor?>;"><?=$rangex;?></td></tr>
										<tr><td title="Total X velocity" class="subval" style="background-color: <?=$maxxcolor2?>;"><?=$rangex2;?></td></tr>
									</table>
								</td>
								<td class="seriesrow" align="right" style="padding:0px;margin:0px;">
									<table cellspacing="0" cellpadding="1" height="100%" width="100%" class="movementsubtable">
										<tr><td title="Total Y displacement" class="mainval" style="background-color: <?=$maxycolor?>;"><?=$rangey;?></td></tr>
										<tr><td title="Total Y velocity" class="subval" style="background-color: <?=$maxycolor2?>;"><?=$rangey2;?></td></tr>
									</table>
								</td>
								<td class="seriesrow" align="right" style="padding:0px; margin:0px;">
									<table cellspacing="0" cellpadding="1" height="100%" width="100%" class="movementsubtable">
										<tr><td title="Total Z displacement" class="mainval" style="background-color: <?=$maxzcolor?>;"><?=$rangez;?></td></tr>
										<tr><td title="Total Z velocity" class="subval" style="background-color: <?=$maxzcolor2?>;"><?=$rangez2;?></td></tr>
									</table>
								</td>
								<td class="seriesrow" align="right" style="background-color: <?=$maxpvsnrcolor?>; font-size:8pt">
									<a href="ajaxapi.php?action=stddevchart&h=40&w=450&min=<?=$pstats[$sequence]['minpvsnr']?>&max=<?=$pstats[$sequence]['maxpvsnr']?>&mean=<?=$pstats[$sequence]['avgpvsnr']?>&std=<?=$pstats[$sequence]['stdpvsnr']?>&i=<?=$pvsnr?>&b=yes" class="preview" style="color: black; text-decoration: none"><?=$pvsnr;?></a> 
								</td>
								<td class="seriesrow" align="right" style="background-color: <?=$maxiosnrcolor?>; font-size:8pt">
									<a href="ajaxapi.php?action=stddevchart&h=40&w=450&min=<?=$pstats[$sequence]['miniosnr']?>&max=<?=$pstats[$sequence]['maxiosnr']?>&mean=<?=$pstats[$sequence]['avgiosnr']?>&std=<?=$pstats[$sequence]['stdiosnr']?>&i=<?=$iosnr?>&b=yes" class="preview" style="color: black; text-decoration: none"><?=$iosnr;?></a>
								</td>
								<td><?=$sequence_h?></td>
								<td style="font-size:8pt"><?=$scanlength?></td>
								<td align="right" style="font-size:8pt"><?=$series_tr?></td>
								<td style="font-size:8pt;white-space: nowrap;">(<?=number_format($series_spacingx,1)?>, <?=number_format($series_spacingy,1)?>, <?=number_format($series_spacingz,1)?>)</td>
								<td style="font-size:8pt;white-space: nowrap;">(<?=$dimX?>, <?=$dimY?>, <?=$dimZ?><? if ($dimT > 1) { echo ", <big><b>$dimT</b></big>"; } ?>)</td>
								<td nowrap style="font-size:8pt">
									<? if ($series_size > 0) { ?>
									<?=$numfiles?> (<?=HumanReadableFilesize($series_size)?>)
									<? if ($GLOBALS['cfg']['allowrawdicomexport']) { ?>
									<a href="download.php?modality=mr&type=dicom&seriesid=<?=$mrseries_id?>" border="0"><i class="download icon" title="Download <?=$data_type_h?> data"></i></a>
									<? 	}
									}

									/* DB reports no files or zero size; check whether any files actually exist on disk */
									if (($modality == "mr") && (((int)$numfiles == 0) || ((float)$series_size == 0))) {
										$diskdatatype = (($data_type ?? '') == '') ? $modality : $data_type;
										$diskfiles = glob($GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$diskdatatype/*");
										$diskfiles = is_array($diskfiles) ? array_filter($diskfiles, 'is_file') : [];
										if (count($diskfiles) == 0) {
											?><i class="large red exclamation circle icon" title="No files on disk for this series"></i><?
										}
										else {
											?><i class="large yellow exclamation triangle icon" title="Database/disk file count mismatch"></i><?
										}
									}
									?>
								</td>
								<td nowrap bgcolor="<?=$behcolor?>" align="center">
									<? if ($numfiles_beh != "-") { ?>
									<a href="managefiles.php?seriesid=<?=$mrseries_id?>&modality=mr&datatype=beh"><?=$numfiles_beh?></a>
									<? } else { ?>
									<?=$numfiles_beh?>
									<? } ?>
									<span class="tiny">
									<?
										if ($numfiles_beh > 0) {
											echo "(" . HumanReadableFilesize($beh_size) . ")";
											?>
											&nbsp;<a href="download.php?modality=mr&type=beh&seriesid=<?=$mrseries_id?>" border="0"><i class="download icon" title="Download behavioral data"></i></a>
											<?
										}
									?>
									</span>
								</td>
								<td class="allseries center aligned" align="center" style="background-color: Lavender"><input type="checkbox" name="seriesids[]" value="<?=$mrseries_id?>" data-seriesnum="<?=htmlspecialchars($series_num)?>" data-datatype="<?=htmlspecialchars($data_type ?? '')?>"></td>
							</tr>
							<?
							$lastseriesnum = $series_num;
						}
					?>
					<!-- uploader script for this series -->
					<script>
						function createUploaders(){
							/* window.onload can only be called once, so make 1 function to create all uploaders */
							<?
							/* uploading behavioral files needs Edit Data */
							mysqli_data_seek($result,0); /* reset the sql result, so we can loop through it again */
							while ($canmodify && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
								$mrseries_id = (int)$row[$idcol];
								?>
								var uploader<?=$mrseries_id?> = new qq.FileUploader({
									element: document.getElementById('uploader<?=$mrseries_id?>'),
									action: 'upload.php',
									params: {modality: 'MRBEH', studyid: '<?=$studyid?>', seriesid: <?=$mrseries_id?>},
									debug: true
								});
								<?
							}
							?>
						}
						// in your app create uploader as soon as the DOM is ready
						// don't wait for the window to load  
						window.onload = createUploaders;
					</script>
				<?
				}
				?>
			</tbody>
		</table>
		<script>
			/*$(document).ready(function() {
				$('#popupbutton2').popup({
					popup : $('#popupmenu2'),
					on : 'click'
				});
			});*/
		</script>
		<div class="ui bottom attached menu">
			<a class="item" href="studies.php?studyid=<?=$studyid?>&action=displayfiles"><i class="file alternate icon"></i> View file list</a>
			<div class="right menu" id="popupbutton2">
				<div class="right menu">
					<div class="ui icon bottom left pointing dropdown button" style="background-color: lavender; margin-right: 0px">
						<i class="dropdown icon"></i>
						<span class="text">With selected series...</span>
						<div class="ui vertical menu">
							
							<? if ($canmodify) { ?>
							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='renameseriesform';document.serieslist.submit();"><i class="corner i cursor icon"></i> Rename</div>
							
							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='updateseriesnotesform';document.serieslist.submit();"><i class="clipboard outline icon"></i> Edit notes</div>
							
							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='moveseriestonewstudy';document.serieslist.submit();"><i class="share icon"></i> Move to new study</div>
							
							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='hideseries';document.serieslist.submit();" title="Hide the series. The series will not show up in search results"><i class="eye slash icon"></i> Hide</div>
							
							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='unhideseries';document.serieslist.submit();" title="Unhide the selected series. The series will now show up in search results"><i class="eye icon"></i> Un-hide</div>
							
							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='resetqa';document.serieslist.submit();" title="Reset the QA results for this series. New QA results will be re-generated"><i class="redo alternate icon"></i> Reset basic QC</div>

							<div class="ui item" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='resetmriqc';document.serieslist.submit();" title="Reset the mriqc metrics for this series. New QC metrics will be re-generated"><i class="redo alternate icon"></i> Reset advanced mriqc</div>
							<? } ?>
							
							<div class="ui item" onclick="document.serieslist.action='packages.php';document.serieslist.action.value='addobject';document.serieslist.submit();"><em data-emoji=":chipmunk:"></em>&nbsp; Add to Package</div>

							<div class="ui item" onclick="ShowSendToPACSModal();" title="Send the selected DICOM series to a remote DICOM AE (PACS)"><i class="paper plane outline icon"></i> Send to PACS</div>

							<? if ($canmodify) { ?>
							<div class="ui item"></div>
							
							<div class="ui item" onclick="if (confirm('Are you sure you want to delete the selected series?')) { document.serieslist.action='studies.php';document.serieslist.action.value='deleteseries';document.serieslist.submit(); }" title="Delete the selected series. The series will be moved to the <span class='tt'><?=$GLOBALS['cfg']['deleteddir']?></span> directory and will not appear anywhere on the website"><i class="red trash alternate icon"></i>Delete series</div>
							<? } ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		</form>

		<?
			/* DICOM AE end points for the "Send to PACS" modal. The modal has its own form, outside of the serieslist form */
			$sqlstring = "select dicomae_id, ae_title, ae_hostname, ae_ip, ae_port, ae_tls from dicom_ae order by ae_title";
			$aeresult = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$numaes = mysqli_num_rows($aeresult);
		?>
		<div class="ui small modal" id="sendToPACSModal">
			<div class="header">Send series to PACS</div>
			<div class="content">
				<form action="studies.php" method="post" id="sendToPACSForm" class="ui form">
					<input type="hidden" name="action" value="sendtopacs">
					<input type="hidden" name="studyid" value="<?=(int)$studyid?>">
					<?=CSRFTokenField()?>
					<div id="sendToPACSSeriesIDs"></div>
					<div class="field">
						<label>Selected series</label>
						<div id="sendToPACSSummary"></div>
					</div>
					<div class="field">
						<label>Destination DICOM AE</label>
						<? if ($numaes > 0) { ?>
						<select name="dicomaeid" class="ui fluid dropdown" required>
							<option value="">Select a DICOM AE...</option>
							<?
								while ($aerow = mysqli_fetch_array($aeresult, MYSQLI_ASSOC)) {
									$aehost = ($aerow['ae_hostname'] != "") ? $aerow['ae_hostname'] : $aerow['ae_ip'];
									?><option value="<?=(int)$aerow['dicomae_id']?>"><?=htmlspecialchars($aerow['ae_title'])?> (<?=htmlspecialchars($aehost)?>:<?=(int)$aerow['ae_port']?><? if ($aerow['ae_tls']) { echo ", TLS"; } ?>)</option><?
								}
							?>
						</select>
						<? } else { ?>
						<div class="ui warning visible message">No DICOM AE end points are defined. <? if (isSiteAdmin()) { ?><a href="admindicomae.php">Add a DICOM AE</a><? } else { ?>Ask an administrator to add one.<? } ?></div>
						<? } ?>
					</div>
				</form>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<button type="submit" form="sendToPACSForm" class="ui primary button" id="sendToPACSButton"><i class="paper plane icon"></i> Queue DICOM transfer</button>
			</div>
		</div>
		<script>
			/* show the Send to PACS modal for the checked series. Only DICOM series are sent; others are listed as skipped */
			function ShowSendToPACSModal() {
				var hasAEs = <?=($numaes > 0) ? 'true' : 'false'?>;
				var dicom = [], nondicom = [];
				$("#serieslist input[name='seriesids[]']:checked").each(function() {
					var datatype = String($(this).data('datatype') || '');
					var seriesnum = String($(this).data('seriesnum'));
					if (datatype.toLowerCase() == 'dicom')
						dicom.push({id: parseInt(this.value, 10), num: seriesnum});
					else
						nondicom.push({id: parseInt(this.value, 10), num: seriesnum, type: (datatype == '' ? 'unknown' : datatype)});
				});

				if ((dicom.length + nondicom.length) == 0) {
					alert('No series selected');
					return;
				}

				/* only the DICOM series are submitted */
				var ids = $('#sendToPACSSeriesIDs').empty();
				$.each(dicom, function(i, s) { ids.append($('<input type="hidden" name="seriesids[]">').val(s.id)); });

				var summary = $('#sendToPACSSummary').empty();
				if (dicom.length > 0)
					summary.append($('<div class="ui positive visible message">').text(dicom.length + ' DICOM series will be sent: ' + $.map(dicom, function(s) { return s.num; }).join(', ')));
				else
					summary.append($('<div class="ui negative visible message">').text('None of the selected series are DICOM. Only DICOM series can be sent to a PACS'));
				if (nondicom.length > 0)
					summary.append($('<div class="ui warning visible message">').text(nondicom.length + ' non-DICOM series will be skipped: ' + $.map(nondicom, function(s) { return s.num + ' (' + s.type + ')'; }).join(', ')));

				var canSend = (dicom.length > 0) && hasAEs;
				$('#sendToPACSButton').toggleClass('disabled', !canSend).prop('disabled', !canSend);
				$('#sendToPACSModal').modal('show');
			}
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayCTSeries -------------------- */
	/* -------------------------------------------- */
	/* called from DisplayStudy, which has already checked View Data. $canmodify (Edit Data) allows inline note edits */
	function DisplayCTSeries($studyid, $study_num, $uid, $canmodify) {
		$studyid = (int)$studyid;
		if (!ValidID($studyid,'Study ID')) { return; }
		if (!ValidID($study_num,'Studynum')) { return; }
	
		?>
		<!--<a href="studies.php?studyid=<?=$studyid?>&action=addseries&modality=CT">Add Series</a>-->
		
		<span class="tiny"><b>Upload file(s) by clicking the button or drag-and-drop (Firefox and Chrome only)</b><br>
		DICOM files will only be associated with the study under which they were originally run... If you upload files from a different study, they won't show up here.</span>
		<br><br>
		<div id="file-uploader-demo1">		
			<noscript>			
				<p>Please enable JavaScript to use file uploader.</p>
				<!-- or put a simple form for upload here -->
			</noscript>         
		</div>
		<br>
		<table class="smalldisplaytable" width="100%">
			<thead>
				<tr>
					<th>Series</th>
					<th>Desc</th>
					<th>Protocol</th>
					<th>Time</th>
					<th>Notes</th>
					<th>Contrast</th>
					<th>Body part</th>
					<th>Options</th>
					<th>KVP</th>
					<th>Collection Dia</th>
					<th>Contrast Route</th>
					<th>Rotation Dir</th>
					<th>Exposure</th>
					<th>Tube current</th>
					<th>Filter type</th>
					<th>Power</th>
					<th>Kernel</th>
					<th>Spacing</th>
					<th>Image size</th>
					<th># files</th>
					<th>Size</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from ct_series where study_id = ? order by series_num";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $studyid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
					mysqli_stmt_close($stmt);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$ctseries_id = (int)$row['ctseries_id'];
						$series_datetime = date('g:ia',strtotime($row['series_datetime'] ?? ''));
						/* the text values come from the DICOM headers or users, and are displayed in HTML */
						$row = array_map(function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }, $row);
						$series_desc = $row['series_desc'];
						$protocol = $row['series_protocol'];
						$sequence = $row['series_sequencename'];
						$series_num = $row['series_num'];
						$series_contrastbolusagent = $row['series_contrastbolusagent'];
						$series_bodypartexamined = $row['series_bodypartexamined'];
						$series_scanoptions = $row['series_scanoptions'];
						$series_kvp = $row['series_kvp'];
						$series_datacollectiondiameter = $row['series_datacollectiondiameter'];
						$series_contrastbolusroute = $row['series_contrastbolusroute'];
						$series_rotationdirection = $row['series_rotationdirection'];
						$series_exposuretime = $row['series_exposuretime'];
						$series_xraytubecurrent = $row['series_xraytubecurrent'];
						$series_filtertype = $row['series_filtertype'];
						$series_generatorpower = $row['series_generatorpower'];
						$series_convolutionkernel = $row['series_convolutionkernel'];
						$series_spacingx = $row['series_spacingx'];
						$series_spacingy = $row['series_spacingy'];
						$series_spacingz = $row['series_spacingz'];
						$img_rows = $row['series_imgrows'];
						$img_cols = $row['series_imgcols'];
						$img_slices = $row['series_imgslices'];
						$numfiles = $row['numfiles'];
						$series_size = $row['series_size'];
						$series_status = $row['series_status'];
						$series_notes = $row['series_notes'];
						$data_type = $row['data_type'];
						$lastupdate = $row['lastupdate'];
						

						$thumbpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/thumb.png";
						$realignpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/MotionCorrection.txt";

						?>
						<? if ($canmodify) { ?>
						<script type="text/javascript">
							$(document).ready(function(){
								$(".edit_inline<? echo $ctseries_id; ?>").editInPlace({
									url: "ajaxapi.php",
									params: "action=updateseriesdetails&modality=CT&id=<?=(int)$ctseries_id?>&csrftoken=<?=urlencode($_SESSION['csrftoken'] ?? '')?>",
									default_text: "<i style='color:#AAAAAA'>Add notes...</i>",
									bg_over: "white",
									bg_out: "lightyellow",
								});
							});
						</script>
						<? } ?>
						<tr>
							<td><?=$series_num?></td>
							<td><?=$series_desc?></td>
							<td><?=$protocol?> <a href="preview.php?image=<?=urlencode($thumbpath)?>" class="preview"><i class="image icon"></i></a></td>
							<td><?=$series_datetime?></td>
							<td><span id="series_notes" class="edit_inline<? echo $ctseries_id; ?>" style="background-color: lightyellow; padding: 1px 3px; font-size: 8pt;"><? echo $series_notes; ?></span></td>
							<td><?=$series_contrastbolusagent?></td>
							<td><?=$series_bodypartexamined?></td>
							<td><?=$series_scanoptions?></td>
							<td><?=$series_kvp?><span class="tiny">V</span></td>
							<td><?=$series_datacollectiondiameter?><span class="tiny">mm</span></td>
							<td><?=$series_contrastbolusroute?></td>
							<td><?=$series_rotationdirection?></td>
							<td><?=$series_exposuretime?><span class="tiny">ms</span></td>
							<td><?=$series_xraytubecurrent?><span class="tiny">mA</span></td>
							<td><?=$series_filtertype?></td>
							<td><?=$series_generatorpower?><span class="tiny">V</span></td>
							<td><?=$series_convolutionkernel?></td>
							<td><?=number_format((float)$series_spacingx,1)?> &times; <?=number_format((float)$series_spacingy,1)?> &times; <?=number_format((float)$series_spacingz,1)?></td>
							<td><?=$img_cols?> &times; <?=$img_rows?> &times; <?=$img_slices?></td>
							<td><?=$numfiles?></td>
							<td nowrap><?=HumanReadableFilesize((float)$series_size)?> <a href="download.php?modality=ct&type=dicom&seriesid=<?=$ctseries_id?>" border="0"><img src="images/download16.png" title="Download <?=$data_type?> data"></a></td>
						</tr>
						<?
					}
				?>
			</tbody>
		</table>
		<?
	}
	
	


	/* -------------------------------------------- */
	/* ------- DeleteSeries ----------------------- */
	/* -------------------------------------------- */
	/* MR series are queued for deletion by the backend; other modalities are deleted here
	   (the series directory is renamed, not removed) */
	function DeleteSeries($studyid, $seriesid, $seriesids) {
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		$t = GetSeriesTable($study['study_modality']);
		if ($t === null) { Error("This study's modality has no series table"); return; }
		list($table, $idcol) = $t;
		$modality = strtolower($study['study_modality']);

		/* combine the two seriesid variables, keeping only this study's series */
		$seriesids[] = $seriesid;
		$sids = GetStudySeriesIDs($study, $seriesids);
		if (count($sids) == 0) {
			Error("No series from this study were selected");
			return;
		}

		$uid = $study['uid'];
		$study_num = (int)$study['study_num'];

		foreach ($sids as $sid) {
			if ($modality == "mr") {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, modality, username, requestdate) values ('delete', 'series', ?, ?, ?, now())";
				$params = [$sid, $modality, $GLOBALS['username']];
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'iss', ...$params);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				mysqli_stmt_close($stmt);
			}
			else {
				/* get the series number, to find the series path */
				$sqlstring = "select series_num from `$table` where `$idcol` = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $sid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sid]);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
				$series_num = (int)($row['series_num'] ?? 0);

				/* rename the series directory */
				if (($uid != "") && ($study_num > 0) && ($series_num > 0)) {
					$seriespath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num";
					if (is_dir($seriespath)) {
						rename($seriespath, $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num-" . time());
					}
				}

				$sqlstring = "delete from `$table` where `$idcol` = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $sid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sid]);
				mysqli_stmt_close($stmt);
			}
		}

		if ($modality == "mr")
			Notice(count($sids) . " series queued for deletion");
		else
			Notice(count($sids) . " series deleted");
	}


	/* -------------------------------------------- */
	/* ------- EditGenericSeries ------------------ */
	/* -------------------------------------------- */
	function EditGenericSeries($seriesid, $modality) {
		$seriesid = (int)$seriesid;
		$studyid = GetSeriesStudyID($modality, $seriesid);
		if ($studyid == 0) {
			Error("Series not found");
			return;
		}
		$study = CheckStudyPerm($studyid, 'modifydata');
		if ($study === null) return;
		if (strtolower($study['study_modality']) != strtolower($modality)) {
			Error("Series modality does not match the study");
			return;
		}
		$modality = strtolower($modality);
		list($table, $idcol) = GetSeriesTable($modality);

		$sqlstring = "select * from `$table` where `$idcol` = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $seriesid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$series_num = $row['series_num'];
		$series_datetime = $row['series_datetime'];
		$protocolA = trim($row['series_protocol'] ?? '');
		$notes = $row['series_notes'];

		/* protocols defined for this modality, plus any already used */
		$protocols = array();
		$sqlstring = "select protocol from modality_protocol where modality = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $modality);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modality]);
		while ($prow = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if (trim($prow['protocol'] ?? '') != "") {
				$protocols[] = trim($prow['protocol']);
			}
		}
		mysqli_stmt_close($stmt);
		$result = MySQLiQuery("select distinct(series_protocol) from `$table`", __FILE__, __LINE__);
		while ($prow = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if (trim($prow['series_protocol'] ?? '') != "") {
				$protocols[] = trim($prow['series_protocol']);
			}
		}
		/* keep the current protocol selectable even if it's no longer in either list */
		if ($protocolA != "") $protocols[] = $protocolA;
		$protocols = array_unique($protocols, SORT_STRING);
		sort($protocols);
		?>
		<div class="ui text container">
			<form method="post" action="studies.php" class="ui form">
			<input type="hidden" name="action" value="updateseries">
			<input type="hidden" name="seriesid" value="<?=$seriesid?>">
			<input type="hidden" name="modality" value="<?=htmlspecialchars($modality)?>">
			<?=CSRFTokenField()?>
			<h3 class="ui header">Series <?=htmlspecialchars($series_num)?></h3>
			<div class="field">
				<label>Protocol</label>
				<select name="protocol">
				<?
					foreach ($protocols as $protocolB) {
						?>
						<option value="<?=htmlspecialchars($protocolB)?>" <? if ($protocolA == $protocolB) { echo "selected"; } ?>><?=htmlspecialchars($protocolB)?></option>
						<?
					}
				?>
				</select>
			</div>
			<div class="field">
				<label>Date/time <span class="tiny">24 hour clock</span></label>
				<input type="text" name="series_datetime" value="<?=htmlspecialchars($series_datetime ?? '')?>">
			</div>
			<div class="field">
				<label>Notes</label>
				<input type="text" size="70" name="notes" value="<?=htmlspecialchars($notes ?? '')?>">
			</div>
			<a href="studies.php?studyid=<?=$studyid?>" class="ui button">Cancel</a>
			<input type="submit" value="Update" class="ui primary button">
			</form>
		</div>
		<br><br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayGenericSeries --------------- */
	/* -------------------------------------------- */
	/* called from DisplayStudy, which has already checked View Data. $canmodify (Edit Data) shows the editing controls */
	function DisplayGenericSeries($id, $modality, $canmodify) {
		
		/* check for valid inputs */
		$id = (int)$id;
		$modality = strtolower(trim($modality ?? ''));
		if (!ValidID($id,'Study ID')) { return; }
		if ((trim($modality) == "") || (strtolower($modality) == "missing modality")) {
			?><div align="center" color="red">Modality was blank, unable to display data</div><?
			Error("Modality was blank. Unable to display data");
			return;
		}
		
		?>
		
		<SCRIPT LANGUAGE="Javascript">
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		</SCRIPT>
		
		<script type="text/javascript">
		$(function() {
			$("#seriesall").click(function() {
				var checked_status = this.checked;
				$(".allseries").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
		});
		</script>
		
		<table class="ui very compact celled selectable grey table">
			<thead>
				<tr>
					<th>Series</th>
					<th>Protocol</th>
					<th>Date</th>
					<th>Notes</th>
					<th>Files</th>
					<th>Size</th>
					<th>Upload <?=htmlspecialchars(strtoupper($modality))?> files <span class="tiny" style="font-weight: normal">(Drag & Drop)</span></th>
					<th>Download</th>
					<th align="left">Operations <input type="checkbox" id="seriesall"><span class="tiny" style="font-weight: normal"> Select All</span></th>
				</tr>
			</thead>
			<form method="post" name="serieslist" id="serieslist" action="studies.php" class="ui form">
			<input type="hidden" name="action" value="" id="serieslistaction">
			<input type="hidden" name="studyid" value="<?=$id?>">
			<?=CSRFTokenField()?>
			<tbody>
				<?
					$firstdate = "";
					$max_seriesnum = 0;
					$result = null;
					$t = GetSeriesTable($modality);
					if ($t !== null) {
						list($seriestable, $idcol) = $t;
						$sqlstring = "select * from `$seriestable` where study_id = ? order by series_num";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'i', $id);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
						mysqli_stmt_close($stmt);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$series_id = (int)$row[$idcol];
							/* the text values are displayed in HTML */
							$row = array_map(function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }, $row);
							$series_num = $row['series_num'];
							if ($series_num > $max_seriesnum) { $max_seriesnum = $series_num; }
							//$series_datetime = date('M j, Y g:ia',strtotime($row['series_datetime']));
							$series_datetime = $row['series_datetime'];
							$protocol = $row['series_protocol'];
							$notes = $row['series_notes'];
							$numfiles = $row['series_numfiles'];
							$series_size = $row['series_size'];
							$lastupdate = $row['lastupdate'];

							$datecolor = "";
							$datemsg = "";
							if ($firstdate == "") {
								$firstdate = substr($series_datetime,0,10);
							}
							else {
								if ($firstdate != substr($series_datetime,0,10)) {
									$datecolor = "red";
									$datemsg = "<i class='bell icon' title='Date does not match series 1. Is this date correct?'></i> ";
								}
							}
								
							if ($numfiles < 1) { $numfiles = "0"; }
							if ($series_size > 1) { $series_size = HumanReadableFilesize((float)$series_size); } else { $series_size = "-"; }
							if ($canmodify) {
							?>
							<script type="text/javascript">
								$(document).ready(function(){
									$(".edit_inline<? echo $series_id; ?>").editInPlace({
										url: "ajaxapi.php",
										params: "action=updateseriesdetails&modality=<?=urlencode($modality)?>&id=<?=(int)$series_id?>&csrftoken=<?=urlencode($_SESSION['csrftoken'] ?? '')?>",
										default_text: "<i style='color:#AAAAAA'>Add notes...</i>",
										bg_over: "white",
										bg_out: "lightyellow",
									});
								});
							</script>
							<? } ?>
							<tr>
								<td style="text-align: center;"><? if ($canmodify) { ?><a href="studies.php?action=editseries&seriesid=<?=$series_id?>&modality=<?=$modality?>" style="font-weight: bold; font-size: larger;"><?=$series_num?></a><? } else { ?><b><?=$series_num?></b><? } ?></td>
								<td><span id="series_protocol" class="edit_inline<? echo $series_id; ?>" style="background-color: lightyellow; padding: 1px 3px; font-size: 11pt;"><? echo $protocol; ?></span></td>
								<td class="<?=$datecolor?>"><?=$datemsg;?><span id="series_datetime" class="edit_inline<? echo $series_id; ?>" style="background-color: lightyellow; padding: 1px 3px; font-size: 11pt;"><? echo $series_datetime; ?></span></td>
								<td><span id="series_notes" class="edit_inline<? echo $series_id; ?>" style="background-color: lightyellow; padding: 1px 3px; font-size: 11pt;"><? echo $notes; ?></span></td>
								<td>
									<a class="ui tiny basic blue button <? if ($numfiles < 1) echo "disabled"; ?>" href="managefiles.php?seriesid=<?=$series_id?>&modality=<?=$modality?>&datatype=<?=$modality?>"><i class="file outline icon"></i> Manage <?=$numfiles?> file(s)</a>
								</td>
								<td><?=$series_size?></td>
								<td>
								<span id="uploader<?=$series_id?>"></span>
								</td>
								<td nowrap>
									<? if ($series_size != "-") { ?>
										<a class="ui tiny basic blue button" href="download.php?modality=<?=$modality?>&seriesid=<?=$series_id?>"><i class="download icon" title="Download <?=$modality?> data"></i> Download (<?=$series_size?>)</a>
									<? } ?>
								</td>
								<td class="allseries" ><input type="checkbox" name="seriesids[]" value="<?=$series_id?>"></td>
							</tr>
						<?
						}
					}
					else {
						?>
						<tr>
							<td colspan="1">
								<span style="color: red">Invalid modality [<?=htmlspecialchars($modality)?>]</span>
							</td>
						</tr>
						<?
					}
					?>
					<!-- uploader script for this series -->
					<script>
						function createUploaders(){
							/* window.onload can only be called once, so make 1 function to create all uploaders */
							<?
							/* uploading files needs Edit Data */
							if ($canmodify && ($result !== null)) {
							mysqli_data_seek($result,0); /* reset the sql result, so we can loop through it again */
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$series_id = (int)$row[$idcol];
								?>
									var uploader<?=$series_id?> = new qq.FileUploader({
										element: document.getElementById('uploader<?=$series_id?>'),
										action: 'upload.php',
										params: {modality: '<?=strtoupper($modality)?>', studyid: '<?=$id?>', seriesid: <?=$series_id?>},
										debug: true
									});
								<?
							}
							}
							?>
						}
						// in your app create uploader as soon as the DOM is ready
						// don't wait for the window to load  
						window.onload = createUploaders;
					</script>
				<input type="hidden" name="modality" value="<?=htmlspecialchars(strtoupper($modality))?>">
				<? if ($canmodify) { ?>
				<tr>
					<td>
						<div class="ui input">
							<input type="text" name="series_num" size="3" maxlength="10" value="<?=($max_seriesnum + 1)?>" required>
						</div>
					</td>
					<td><div class="ui input">
						<input type="text" name="protocol" list="protocols" required>
						</div>
						<datalist id="protocols">
						<?
							$sqlstring = "select protocol from modality_protocol where modality = ?";
							$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
							mysqli_stmt_bind_param($stmt, 's', $modality);
							$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modality]);
							mysqli_stmt_close($stmt);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$protocol = htmlspecialchars($row['protocol'] ?? '');
								?>
									<option value="<?=$protocol?>"><?=$protocol?></option>
								<?
							}
						?>
						</datalist>
					</td>
					<td title="Time should be formatted as a 24-hour clock"><div class="ui input"><input type="text" name="series_datetime" value="<?=date('Y-m-d H:i:s')?>" required></td>
					<td><div class="ui input"><input type="text" name="notes"></div></td>
					<td colspan="5">
						<button type="submit" class="ui button" value="Create" onClick="document.serieslist.action.value='addseries';"><i class="arrow alternate circle left icon"></i> Create series</button>
					</td>
				</tr>
				<? } ?>
			</tbody>
		</table>
		<div class="ui two column grid">
			<div class="column">
				<a class="ui basic button" href="studies.php?studyid=<?=$id?>&action=displayfiles"><i class="file alternate icon"></i> View file list</a>
			</div>
			<div class="right aligned column">
				<? if ($canmodify) { ?>
				<b>With Selected</b> &nbsp; &nbsp; <br>
				<br>
				<button type="button" class="ui red button" style="width:200px" onclick="if (confirm('Are you absolutely sure you want to DELETE the selected series?')) { document.serieslist.action.value='deleteseries'; document.serieslist.submit(); }"><i class="trash icon"></i> Delete</button>
				<br><br>
				<button type="button" class="ui button" style="width: 200px" onclick="document.serieslist.action='studies.php';document.serieslist.action.value='minipipelineform'; document.serieslist.submit()"><i class="cogs icon"></i> Run mini-pipeline</button>
				<? } ?>
			</div>
		</form>

		<?
	}
	


	/* -------------------------------------------- */
	/* ------- DisplayFileSeries ------------------ */
	/* -------------------------------------------- */
	function DisplayFileSeries($path, $studyid) {
	
		if (file_exists($path)) {
			$files = find_all_files($path);
			if (!is_array($files)) $files = array();

			?>
			<a class="ui basic button" href="studies.php?studyid=<?=$studyid?>">Normal View</a> Showing (<?=count($files)?> files) from <code><?=htmlspecialchars($path)?></code>
			<table class="ui very compact small celled table">
				<thead>
					<tr>
						<th style="font-weight: bold; border-bottom:2px solid #999999">File</th>
						<th style="font-weight: bold; border-bottom:2px solid #999999">Timestamp</th>
						<th style="font-weight: bold; border-bottom:2px solid #999999">Permissions</th>
						<th style="font-weight: bold; border-bottom:2px solid #999999">Size <span class="tiny">bytes</span></th>
					</tr>
				</thead>
			<?
			foreach ($files as $line) {
				
				$timestamp2 = "N/A";
				$perm2 = 'N/A';
				$islink2 = '';
				$isdir2 = '';
				$size2 = 0;
				list($file,$timestamp1,$perm1,$isdir1,$islink1,$size1) = explode("\t",$line);
				
				if (is_link($file)) { $islink2 = 1; }
				if (is_dir($file)) { $isdir2 = 1; }
				if (file_exists($file)) {
					$timestamp2 = filemtime($file);
					$perm2 = substr(sprintf('%o', fileperms($file)), -4);
					$size2 = filesize($file);

					$filetype = "";
					if (stristr(strtolower($file),'.nii') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.nii.gz') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.inflated') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.smoothwm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.sphere') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.pial') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.fsm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.orig') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.png') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.ppm') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpeg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.gif') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.txt') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.log') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.sh') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.job') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".o") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".e") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".par") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".mat") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".bidsignore") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".json") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".tsv") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".html") !== FALSE) { $filetype = 'text'; }
					//echo "[$file $filetype]";
				}
				$filecolor = "black";
				if ($islink2) { $filecolor = "red"; } else { $filecolor = ''; }
				if ($isdir1) { $filecolor = "darkblue"; $fileweight = ''; } else { $filecolor = ''; $fileweight = ''; }
				
				$clusterpath = str_replace($GLOBALS['cfg']['mountdir'],'',$path);
				$displayfile = str_replace($clusterpath,'',$file);
				$lastslash = strrpos($displayfile,'/');
				/* file names can come from uploads, so escape them */
				$displayfile = htmlspecialchars(substr($displayfile,0,$lastslash)) . '<b>' . htmlspecialchars(substr($displayfile,$lastslash)) . '</b>';
				
				$displayperms = '';
				for ($i=1;$i<=3;$i++) {
					switch (substr($perm2,$i,1)) {
						case 0: $displayperms .= '---'; break;
						case 1: $displayperms .= '--x'; break;
						case 2: $displayperms .= '-w-'; break;
						case 3: $displayperms .= '-wx'; break;
						case 4: $displayperms .= 'r--'; break;
						case 5: $displayperms .= 'r-x'; break;
						case 6: $displayperms .= 'rw-'; break;
						case 7: $displayperms .= 'rwx'; break;
					}
				}
				?>
				<tr>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD; color:<?=$filecolor?>; font-weight: <?=$fileweight?>">
					<?
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?=urlencode($file)?>" target="_blank"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?=urlencode($file)?>" target="_blank"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="niiview.php?type=<?=$filetype?>&filename=<?=urlencode($file)?>" target="_blank"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							default:
					?>
					<?=$displayfile?>
					<? } ?>
					</td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=is_int($timestamp2) ? date("M j, Y H:i:s",$timestamp2) : $timestamp2?></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=$displayperms?></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=number_format((float)$size2)?></td>
				</tr>
				<?
			}
			?></table><?
		}
		else {
			Error("No data exists for this study");
		}
	}
	
	

?>


<? include("footer.php") ?>
