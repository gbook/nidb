
<?
 // ------------------------------------------------------------------------------
 // NiDB projects.php
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
		<title>NiDB - Projects</title>
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
	/* mutating actions require POST + CSRF token */
	$action = GetSafeAction(array("updatestudyage", "changeproject", "updateexperimentmapping", "applytags", "obliteratesubject", "obliteratestudy", "rearchivestudies", "rearchivesubjects", "resetqa", "resetmriqc", "setfavorite", "unsetfavorite", "batchupdatesubject", "batchaddsubjects", "batchupdatestudy"));
	$id = (int)GetVariable("id");
	$projectid = (int)GetVariable("projectid");
	if ($id == 0) { $id = $projectid; }
	$viewtype = GetVariable("viewtype");
	if (!in_array($viewtype, array("", "groupbypi", "groupbyadmin"), true)) $viewtype = "";
	$returnto = GetVariable("returnto");
	$newprojectid = (int)GetVariable("newprojectid");
	$studyids = GetVariable("studyids");
	if (!is_array($studyids)) $studyids = array();
	$matchidonly = GetVariable("matchidonly");
	$modalities = GetVariable("modalities");
	$protocolnames = GetVariable("protocolname");
	$experimentids = GetVariable("experimentid");
	$tags = GetVariable("tags");
	$csv = GetVariable("csv");

	/* mutating actions use POST/Redirect/GET: run the handler, stash its message, then redirect
	   to a GET so a refresh/Back doesn't re-submit. Each handler checks permissions itself */
	$flashthen = function($url) { $_SESSION['flash'] = ob_get_clean(); RedirectTo($url); };
	switch ($action) {
		case 'displaystudies':
			ShowFlashMessage();
			DisplayStudiesTable($id);
			break;
		case 'updatestudyage':
			ob_start(); UpdateStudyAge($id);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'changeproject':
			ob_start(); ChangeProject($id, $newprojectid, $studyids);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'updateexperimentmapping':
			ob_start(); UpdateExperimentMapping($id, $modalities, $protocolnames, $experimentids);
			$flashthen("projects.php?action=editexperimentmapping&id=$id");
			break;
		case 'editexperimentmapping':
			ShowFlashMessage();
			EditExperimentMapping($id);
			break;
		case 'editsubjects':
		case 'displaysubjects':
			ShowFlashMessage();
			DisplaySubjectsTable($id);
			break;
		case 'applytags':
			ob_start(); ApplyTags($id, $studyids, $tags);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'obliteratesubject':
			ob_start(); ObliterateSubject($id, $studyids);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'obliteratestudy':
			ob_start(); ObliterateStudy($id, $studyids);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'rearchivestudies':
			ob_start(); RearchiveStudies($id, $studyids, $matchidonly);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'rearchivesubjects':
			ob_start(); RearchiveSubjects($id, $studyids, $matchidonly);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'resetqa':
			ob_start(); ResetProjectQC($id, "basic");
			$flashthen("projects.php?id=$id");
			break;
		case 'resetmriqc':
			ob_start(); ResetProjectQC($id, "mriqc");
			$flashthen("projects.php?id=$id");
			break;
		case 'setfavorite':
		case 'unsetfavorite':
			ob_start(); SetProjectFavorite($id, ($action == 'setfavorite') ? 1 : 0);
			/* back to the project page, or to the project list it was clicked from */
			$flashthen(($returnto == "project") ? "projects.php?id=$id" : "projects.php?viewtype=" . urlencode($viewtype));
			break;
		case 'batchupdatesubject':
			ob_start(); BatchUpdateSubject($id, $csv);
			$flashthen("projects.php?action=editsubjects&id=$id");
			break;
		case 'batchaddsubjects':
			ob_start(); BatchAddSubjects($id, $csv);
			$flashthen("projects.php?action=editsubjects&id=$id");
			break;
		case 'batchupdatestudy':
			ob_start(); BatchUpdateStudy($id, $csv);
			$flashthen("projects.php?action=displaystudies&id=$id");
			break;
		case 'displaynonimaging':
			DisplayNonImagingTable($id);
			break;
		case 'displayprojectinfo':
		default:
			ShowFlashMessage();
			if ($id == 0) {
				DisplayProjects($viewtype);
			}
			else {
				DisplayProject($id);
			}
			break;
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetProjectRow ---------------------- */
	/* -------------------------------------------- */
	/* the project row, or null if it doesn't exist */
	function GetProjectRow($projectid) {
		$projectid = (int)$projectid;
		if ($projectid <= 0) return null;
		$sqlstring = "select * from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return $row ? $row : null;
	}


	/* -------------------------------------------- */
	/* ------- CheckProjectPerm ------------------- */
	/* -------------------------------------------- */
	/* returns the project row if the current user has any one of $perms (e.g. array('viewdata', 'viewphi'))
	   on the project, otherwise prints an error and returns null */
	function CheckProjectPerm($projectid, $perms) {
		$project = GetProjectRow($projectid);
		if ($project === null) {
			Error("Project not found");
			return null;
		}
		$p = GetCurrentUserProjectPermissions(array((int)$projectid));
		foreach ((array)$perms as $perm) {
			if (GetPerm($p, $perm, $projectid)) return $project;
		}
		$labels = array('viewdata' => 'View Data', 'viewphi' => 'View PHI', 'modifydata' => 'Edit Data', 'modifyphi' => 'Edit PHI', 'projectadmin' => 'project admin');
		Error("You need " . implode(" or ", array_map(function($x) use ($labels) { return $labels[$x] ?? $x; }, (array)$perms)) . " permission on this project");
		return null;
	}


	/* -------------------------------------------- */
	/* ------- GetProjectStudyIDs ----------------- */
	/* -------------------------------------------- */
	/* of the requested study ids, the ones that belong to this project (ints) */
	function GetProjectStudyIDs($projectid, $studyids) {
		$projectid = (int)$projectid;
		$ids = array_values(array_unique(array_filter(array_map('intval', (array)$studyids), function($v) { return $v > 0; })));
		if (($projectid <= 0) || (count($ids) == 0)) return array();

		$sqlstring = "select a.study_id from studies a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ? and a.study_id in (" . implode(',', array_fill(0, count($ids), '?')) . ")";
		$params = array_merge(array($projectid), $ids);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($params)), ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$valid = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$valid[] = (int)$row['study_id'];
		}
		mysqli_stmt_close($stmt);
		return $valid;
	}


	/* -------------------------------------------- */
	/* ------- CheckPowerToolAccess --------------- */
	/* -------------------------------------------- */
	/* the 'Powerful Tools' on the studies table (tags, move, re-archive, obliterate) are only shown to
	   site admins; enforce that here. Returns the selected study ids that belong to this project, or
	   null after printing an error */
	function CheckPowerToolAccess($projectid, $studyids) {
		if (!isSiteAdmin()) {
			Error("Only site administrators can perform this operation");
			return null;
		}
		if (GetProjectRow($projectid) === null) {
			Error("Project not found");
			return null;
		}
		$ids = GetProjectStudyIDs($projectid, $studyids);
		if (count($ids) == 0) {
			Error("No studies from this project were selected");
			return null;
		}
		return $ids;
	}


	/* -------------------------------------------- */
	/* ------- QueueFileIORequests ---------------- */
	/* -------------------------------------------- */
	/* queue a backend fileio operation ($operation, a literal) for each subject or study id */
	function QueueFileIORequests($operation, $datatype, $ids) {
		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values (?, ?, ?, ?, now())";
		$username = $GLOBALS['username'];
		foreach ($ids as $id) {
			$id = (int)$id;
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ssis', $operation, $datatype, $id, $username);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$operation, $datatype, $id, $username]);
			mysqli_stmt_close($stmt);
		}
	}


	/* -------------------------------------------- */
	/* ------- GetStudiesSubjects ----------------- */
	/* -------------------------------------------- */
	/* the subjects (subject_id => uid) of a list of study ids */
	function GetStudiesSubjects($studyids) {
		$subjects = array();
		if (count($studyids) == 0) return $subjects;
		$sqlstring = "select distinct c.subject_id, c.uid from studies a join enrollment b on a.enrollment_id = b.enrollment_id join subjects c on b.subject_id = c.subject_id where a.study_id in (" . implode(',', array_fill(0, count($studyids), '?')) . ")";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($studyids)), ...$studyids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $studyids);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjects[(int)$row['subject_id']] = $row['uid'];
		}
		mysqli_stmt_close($stmt);
		return $subjects;
	}


	/* -------------------------------------------- */
	/* ------- FavoriteToggle --------------------- */
	/* -------------------------------------------- */
	/* the favorite star for a project, as a small POST form. $returnto 'project' goes back to the project
	   page, otherwise to the project list ($viewtype) */
	function FavoriteToggle($projectid, $favorite, $returnto, $viewtype = "") {
		$projectid = (int)$projectid;
		?><form method="post" action="projects.php" style="display: inline; margin: 0"><input type="hidden" name="action" value="<?=($favorite ? "unsetfavorite" : "setfavorite")?>"><input type="hidden" name="id" value="<?=$projectid?>"><input type="hidden" name="returnto" value="<?=htmlspecialchars($returnto)?>"><input type="hidden" name="viewtype" value="<?=htmlspecialchars($viewtype)?>"><?=CSRFTokenField()?><button type="submit" style="background: none; border: none; padding: 0; cursor: pointer"><? if ($favorite) { ?><i class="yellow star icon" title="Click to remove this project from your favorites"></i><? } else { ?><i class="grey star outline icon" title="Click to add this project to your favorites"></i><? } ?></button></form><?
	}


	/* -------------------------------------------- */
	/* ------- JSONForScript ---------------------- */
	/* -------------------------------------------- */
	/* json_encode() a value for embedding in a <script> block (no </script> breakout) */
	function JSONForScript($v) {
		return json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PARTIAL_OUTPUT_ON_ERROR);
	}


	/* -------------------------------------------- */
	/* ------- ParseBatchCSV ---------------------- */
	/* -------------------------------------------- */
	/* parse pasted CSV into an array of rows keyed by the lowercased, space-free header names. Short
	   rows are padded with ''. Returns null (after a notice) if there is no header + data row */
	function ParseBatchCSV($csvstr) {
		$lines = preg_split('/\r\n|\r|\n/', trim($csvstr ?? ''));
		if (count($lines) < 2) { Notice("CSV must contain a header row and at least one data row."); return null; }

		$header = array_map(function($h) { return strtolower(str_replace(' ', '', trim($h))); }, str_getcsv($lines[0]));
		$rows = array();
		for ($i = 1; $i < count($lines); $i++) {
			if (trim($lines[$i]) === '') continue;
			$values = array_map('trim', str_getcsv($lines[$i]));
			$row = array();
			foreach ($header as $idx => $col) {
				$row[$col] = $values[$idx] ?? '';
			}
			$row['_line'] = $i;
			$rows[] = $row;
		}
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- BatchUpdateSubject ----------------- */
	/* -------------------------------------------- */
	/* update subjects enrolled in this project from pasted CSV. Permissions follow subjects.php:
	   enrollment columns (status, enrollgroup, altuids, icd10) need Edit Data on this project, PHI
	   columns need Edit PHI on the subject, and other subject columns need either */
	function BatchUpdateSubject($projectid, $csvstr) {
		$project = CheckProjectPerm($projectid, array('modifydata', 'modifyphi'));
		if ($project === null) return;
		$projectid = (int)$projectid;
		$pperms = GetCurrentUserProjectPermissions(array($projectid));
		$canmodifydata = GetPerm($pperms, 'modifydata', $projectid);

		$rows = ParseBatchCSV($csvstr);
		if ($rows === null) return;

		$enrollmentcols = array('status', 'enrollgroup', 'altuids', 'icd10');
		$phicols = array('birthdate', 'ethnicity1', 'ethnicity2', 'handedness', 'education', 'marital');
		$subjectcols = array('guid', 'sex', 'gender', 'smoking');

		$msgs = array();
		foreach ($rows as $line) {
			$uid = $line['uid'] ?? '';
			$h_uid = htmlspecialchars($uid);
			if ($uid == '') { $msgs[] = "Row " . $line['_line'] . ": skipped &mdash; uid is blank"; continue; }

			/* the subject must be enrolled in this project */
			$sqlstring = "select a.subject_id from subjects a join enrollment b on a.subject_id = b.subject_id where a.uid = ? and b.project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $uid, $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$uid, $projectid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) { $msgs[] = "$h_uid - not enrolled in this project, skipped"; continue; }
			$subjectid = (int)$row['subject_id'];
			$sp = GetSubjectPermissions($subjectid);

			foreach ($line as $column => $value) {
				if (in_array($column, $enrollmentcols)) $allowed = $canmodifydata;
				elseif (in_array($column, $phicols)) $allowed = $sp['modifyphi'];
				elseif (in_array($column, $subjectcols)) $allowed = $sp['canedit'];
				else continue;

				if (!$allowed) {
					$msgs[] = "$h_uid - " . htmlspecialchars($column) . " not changed: you don't have permission to edit it";
					continue;
				}
				$msgs[] = UpdateSubjectDetails($uid, $subjectid, $projectid, $column, trim($value));
			}

			/* firstname/lastname combine into subjects.name as 'lastname^firstname'. Handled together
			   (not as individual columns) so one does not overwrite the other. Only applied when at
			   least one is provided, so a row without them leaves name unchanged. Name is PHI */
			if (array_key_exists('firstname', $line) || array_key_exists('lastname', $line)) {
				$firstname = trim($line['firstname'] ?? '');
				$lastname = trim($line['lastname'] ?? '');
				if (($firstname !== '') || ($lastname !== '')) {
					if (!$sp['modifyphi']) {
						$msgs[] = "$h_uid - name not changed: you don't have Edit PHI permission";
					}
					else {
						$newname = $lastname . '^' . $firstname;
						$sqlstring = "update subjects set name = ? where subject_id = ?";
						$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
						mysqli_stmt_bind_param($stmt, 'si', $newname, $subjectid);
						MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newname, $subjectid]);
						mysqli_stmt_close($stmt);
						$msgs[] = "$h_uid - Updated name &rarr; <tt>" . htmlspecialchars($newname) . "</tt>";
					}
				}
			}
		}
		Notice(implode("<br>", array_filter($msgs)));
	}


	/* -------------------------------------------- */
	/* ------- BatchUpdateStudy ------------------- */
	/* -------------------------------------------- */
	function BatchUpdateStudy($projectid, $csvstr) {
		$project = CheckProjectPerm($projectid, array('modifydata'));
		if ($project === null) return;
		$projectid = (int)$projectid;

		$rows = ParseBatchCSV($csvstr);
		if ($rows === null) return;
		if (!array_key_exists('uid', $rows[0] ?? array()) || !array_key_exists('studynum', $rows[0] ?? array())) {
			Notice("CSV must contain the columns <b>uid</b> and <b>studynum</b>");
			return;
		}

		/* CSV column -> studies column (literals) */
		$colMap = array(
			'visit'         => 'study_type',
			'studydatetime' => 'study_datetime',
			'studyage'      => 'study_ageatscan',
			'description'   => 'study_desc',
			'studyid'       => 'study_alternateid',
			'site'          => 'study_site',
		);

		$msgs = array();
		foreach ($rows as $row) {
			$i = $row['_line'];
			$uid = $row['uid'];
			$h_uid = htmlspecialchars($uid);
			$studynum = (int)$row['studynum'];
			if ($uid === '') { $msgs[] = "Row $i: skipped &mdash; uid is blank"; continue; }
			if ($studynum <= 0) { $msgs[] = "Row $i ($h_uid): skipped &mdash; studynum is missing or invalid"; continue; }

			/* find the study for this uid + studynum within this project */
			$sqlstring = "select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects d on d.subject_id = b.subject_id where d.uid = ? and a.study_num = ? and b.project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'sii', $uid, $studynum, $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$uid, $studynum, $projectid]);
			$rowS = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$rowS) { $msgs[] = "Row $i ($h_uid $studynum): study not found in this project"; continue; }
			$studyid = (int)$rowS['study_id'];

			/* build the update from whichever optional columns are present in the CSV */
			$sets = array();
			$params = array();
			$updated = array();
			foreach ($colMap as $csvcol => $dbcol) {
				if (!array_key_exists($csvcol, $row)) continue;
				$raw = $row[$csvcol];
				/* skip blank values for numeric/datetime columns to avoid invalid writes */
				if (($raw === '') && in_array($dbcol, array('study_datetime', 'study_ageatscan'))) continue;
				$sets[] = "$dbcol = ?";
				$params[] = $raw;
				$updated[] = $csvcol;
			}
			if (count($sets) < 1) { $msgs[] = "Row $i ($h_uid $studynum): no updatable columns provided"; continue; }

			$sqlstring = "update studies set " . implode(", ", $sets) . " where study_id = ?";
			$params[] = $studyid;
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, str_repeat('s', count($params) - 1) . 'i', ...$params);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			mysqli_stmt_close($stmt);
			$msgs[] = "$h_uid $studynum &mdash; updated <tt>" . implode(", ", $updated) . "</tt>";
		}
		Notice(implode("<br>", $msgs));
	}


	/* -------------------------------------------- */
	/* ------- BatchAddSubjects ------------------- */
	/* -------------------------------------------- */
	/* create (or find, by alternate ID) subjects from pasted CSV and enroll them in this project.
	   Needs Edit Data on the project, plus Edit PHI if any PHI column (name, birthdate, race,
	   ethnicity, handedness, education, marital status) has values */
	function BatchAddSubjects($projectid, $csvstr) {
		$project = CheckProjectPerm($projectid, array('modifydata'));
		if ($project === null) return;
		$projectid = (int)$projectid;
		$pperms = GetCurrentUserProjectPermissions(array($projectid));

		$rows = ParseBatchCSV($csvstr);
		if ($rows === null) return;

		foreach (array('id', 'sex', 'gender', 'status', 'enrollgroup') as $req) {
			if (!array_key_exists($req, $rows[0])) {
				Notice("CSV is missing required column: <b>$req</b>");
				return;
			}
		}

		/* PHI columns need Edit PHI. Refuse the whole batch rather than silently dropping values */
		if (!GetPerm($pperms, 'modifyphi', $projectid)) {
			foreach ($rows as $row) {
				foreach (array('birthdate', 'ethnicity1', 'ethnicity2', 'handedness', 'education', 'maritalstatus', 'firstname', 'lastname') as $phicol) {
					if (($row[$phicol] ?? '') !== '') {
						Error("The CSV contains PHI (" . htmlspecialchars($phicol) . "), which needs Edit PHI permission on this project. No subjects were added");
						return;
					}
				}
			}
		}

		$validSex    = ['F','M','O','U',''];
		$validStatus = ['consented','excluded','completed',''];

		$msgs = [];
		foreach ($rows as $row) {
			$i = $row['_line'];
			$altid = $row['id'];
			$h_altid = htmlspecialchars($altid);
			$sex = strtoupper($row['sex']);
			$gender = strtoupper($row['gender']);
			$status = strtolower($row['status']);
			$enrollgroup = $row['enrollgroup'];

			if ($altid === '') { $msgs[] = "Row $i: skipped — ID is blank"; continue; }
			if (!in_array($sex, $validSex)) { $msgs[] = "Row $i ($h_altid): invalid sex '" . htmlspecialchars($sex) . "' — must be F, M, O, or U"; continue; }
			if (!in_array($gender, $validSex)) { $msgs[] = "Row $i ($h_altid): invalid gender '" . htmlspecialchars($gender) . "' — must be F, M, O, or U"; continue; }
			if (!in_array($status, $validStatus)) { $msgs[] = "Row $i ($h_altid): invalid status '" . htmlspecialchars($status) . "' — must be consented, excluded, completed, or blank"; continue; }

			/* optional columns */
			$guid       = $row['guid'] ?? '';
			$birthdate  = $row['birthdate'] ?? '';
			$ethnicity1 = $row['ethnicity1'] ?? '';
			$ethnicity2 = $row['ethnicity2'] ?? '';
			$handedness = $row['handedness'] ?? '';
			$education  = $row['education'] ?? '';
			$marital    = $row['maritalstatus'] ?? '';
			$smoking    = $row['smokingstatus'] ?? '';
			$firstname  = $row['firstname'] ?? '';
			$lastname   = $row['lastname'] ?? '';
			if ($birthdate === '') $birthdate = '0000-00-00';

			/* if a first and/or last name was provided, store it in subjects.name as
			   'lastname^firstname'; otherwise fall back to the CSV ID */
			$name = (($firstname !== '') || ($lastname !== '')) ? ($lastname . '^' . $firstname) : $altid;

			/* check if a subject with this altuid already exists */
			$sqlstring = "select subject_id from subject_altuid where altuid = ? limit 1";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $altid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$altid]);
			$row2 = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($row2) {
				$subjectRowID = (int)$row2['subject_id'];
				$msgs[] = "<b>$h_altid</b>: subject already exists (subject_id=$subjectRowID) — checking enrollment";
			}
			else {
				/* generate a unique NiDB UID, and a unique family UID */
				do {
					$uid = NIDB\CreateUID('S', 3);
				} while (BatchValueExists("select subject_id from subjects where uid = ?", $uid));
				do {
					$familyuid = NIDB\CreateUID('F');
				} while (BatchValueExists("select family_id from families where family_uid = ?", $familyuid));

				/* insert subject */
				$sqlstring = "insert into subjects (name, birthdate, sex, gender, ethnicity1, ethnicity2, handedness, education, marital_status, smoking_status, uid, uuid, guid) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, uuid(), ?)";
				$params = [$name, $birthdate, $sex, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $marital, $smoking, $uid, $guid];
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ssssssssssss', ...$params);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				$subjectRowID = (int)mysqli_insert_id($GLOBALS['linki']);
				mysqli_stmt_close($stmt);

				/* create family record */
				$familyname = "Proband-$uid";
				$sqlstring = "insert into families (family_uid, family_createdate, family_name) values (?, now(), ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ss', $familyuid, $familyname);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyuid, $familyname]);
				$familyRowID = (int)mysqli_insert_id($GLOBALS['linki']);
				mysqli_stmt_close($stmt);

				$sqlstring = "insert into family_members (family_id, subject_id, fm_createdate) values (?, ?, now())";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ii', $familyRowID, $subjectRowID);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyRowID, $subjectRowID]);
				mysqli_stmt_close($stmt);

				/* store the CSV ID as the subject's alt UID */
				$sqlstring = "insert ignore into subject_altuid (subject_id, altuid) values (?, ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'is', $subjectRowID, $altid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectRowID, $altid]);
				mysqli_stmt_close($stmt);

				$msgs[] = "<b>$h_altid</b>: created new subject <tt>" . htmlspecialchars($uid) . "</tt>";
			}

			/* enroll in project if not already enrolled */
			$sqlstring = "select enrollment_id from enrollment where project_id = ? and subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $projectid, $subjectRowID);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $subjectRowID]);
			$erow = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($erow) {
				/* update group and status on existing enrollment */
				$enrollmentRowID = (int)$erow['enrollment_id'];
				$sqlstring = "update enrollment set enroll_subgroup = ?, enroll_status = ? where enrollment_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ssi', $enrollgroup, $status, $enrollmentRowID);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollgroup, $status, $enrollmentRowID]);
				mysqli_stmt_close($stmt);
				$msgs[] = "&nbsp;&nbsp;&rarr; already enrolled — updated group/status";
			}
			else {
				$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate, enroll_subgroup, enroll_status) values (?, ?, now(), ?, ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'iiss', $projectid, $subjectRowID, $enrollgroup, $status);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $subjectRowID, $enrollgroup, $status]);
				mysqli_stmt_close($stmt);
				$msgs[] = "&nbsp;&nbsp;&rarr; enrolled in project (group: <i>" . htmlspecialchars($enrollgroup) . "</i>, status: <i>" . htmlspecialchars($status) . "</i>)";
			}
		}

		Notice(implode("<br>", $msgs));
	}


	/* -------------------------------------------- */
	/* ------- BatchValueExists ------------------- */
	/* -------------------------------------------- */
	/* true if a one-parameter (string) select returns any rows */
	function BatchValueExists($sqlstring, $value) {
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $value);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$value]);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		return $exists;
	}


	/* -------------------------------------------- */
	/* ------- UpdateSubjectDetails --------------- */
	/* -------------------------------------------- */
	/* set one column for a subject (batch update). The caller has checked permissions. Returns a
	   message (HTML) */
	function UpdateSubjectDetails($uid, $subjectid, $projectid, $column, $value) {
		$subjectid = (int)$subjectid;
		$projectid = (int)$projectid;
		$h = htmlspecialchars("$uid, $column");
		if ($subjectid <= 0) return "$h - subject ID blank";
		if ($value == "") return "$h - Batch will not update to a blank value";

		if ($column == "altuids") {
			$enrollmentid = (int)GetEnrollmentID($subjectid, $projectid);
			StartSQLTransaction();
			/* replace this enrollment's alternate UIDs */
			$sqlstring = "delete from subject_altuid where subject_id = ? and enrollment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $subjectid, $enrollmentid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid, $enrollmentid]);
			mysqli_stmt_close($stmt);
			/* comma or space separated; an asterisk marks the primary ID */
			$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (?, ?, ?, ?)";
			foreach (preg_split('/[\s,]+/', $value) as $altuid) {
				$altuid = trim($altuid);
				if ($altuid == "") continue;
				$isprimary = (strpos($altuid, '*') !== false) ? 1 : 0;
				$altuid = str_replace('*', '', $altuid);
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'isii', $subjectid, $altuid, $isprimary, $enrollmentid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid, $altuid, $isprimary, $enrollmentid]);
				mysqli_stmt_close($stmt);
			}
			CommitSQLTransaction();
			return htmlspecialchars($uid) . " - Updated alternate UIDs <tt>" . htmlspecialchars($value) . "</tt>";
		}
		elseif (($column == "status") || ($column == "enrollgroup")) {
			$dbcol = ($column == "status") ? "enroll_status" : "enroll_subgroup";
			$sqlstring = "update enrollment set $dbcol = ? where project_id = ? and subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'sii', $value, $projectid, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$value, $projectid, $subjectid]);
			mysqli_stmt_close($stmt);
			return htmlspecialchars($uid) . " - Updated " . htmlspecialchars($column) . " &rarr; <tt>" . htmlspecialchars($value) . "</tt>";
		}
		elseif ($column == "icd10") {
			$enrollmentRowID = (int)GetEnrollmentID($subjectid, $projectid);
			$sqlstring = "select icd10_id from icd10 where icd10_code = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $value);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$value]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) return "$h - ICD10 code <tt>" . htmlspecialchars($value) . "</tt> not found";
			$icd10RowID = (int)$row['icd10_id'];

			$sqlstring = "insert ignore into diagnosis (enrollment_id, icd10_id) values (?, ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $enrollmentRowID, $icd10RowID);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID, $icd10RowID]);
			mysqli_stmt_close($stmt);
			return htmlspecialchars($uid) . " - Added diagnosis <tt>" . htmlspecialchars($value) . "</tt>";
		}

		/* subject columns (literals) */
		$cols = array('guid' => 'guid', 'sex' => 'sex', 'gender' => 'gender', 'birthdate' => 'birthdate', 'ethnicity1' => 'ethnicity1', 'ethnicity2' => 'ethnicity2', 'handedness' => 'handedness', 'education' => 'education', 'marital' => 'marital_status', 'smoking' => 'smoking_status');
		if (!isset($cols[$column])) return "$h - column not recognized";
		if ($column == "education") {
			$levels = array("Unknown" => 0, "Grade School" => 1, "Middle School" => 2, "High School/GED" => 3, "Trade School" => 4, "Associates Degree" => 5, "Bachelors Degree" => 6, "Masters Degree" => 7, "Doctoral Degree" => 8);
			/* the batch help lists the numbers 0-8; the names are also accepted */
			if (isset($levels[$value])) $value = $levels[$value];
			elseif (!preg_match('/^[0-8]$/', $value)) return "$h - invalid education value";
		}
		$sqlstring = "update subjects set " . $cols[$column] . " = ? where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$value = (string)$value;
		mysqli_stmt_bind_param($stmt, 'si', $value, $subjectid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$value, $subjectid]);
		mysqli_stmt_close($stmt);
		return htmlspecialchars($uid) . " - Updated " . htmlspecialchars($column) . " &rarr; <tt>" . htmlspecialchars($value) . "</tt>";
	}


	/* -------------------------------------------- */
	/* ------- ApplyTags -------------------------- */
	/* -------------------------------------------- */
	/* tag the enrollments of the selected studies (site admin 'Powerful Tools') */
	function ApplyTags($projectid, $studyids, $tags) {
		$ids = CheckPowerToolAccess($projectid, $studyids);
		if ($ids === null) return;

		$taglist = array_values(array_filter(array_map('trim', explode(',', $tags ?? '')), 'strlen'));
		if (count($taglist) == 0) { Error("No tags entered"); return; }

		/* the enrollments of these studies */
		$sqlstring = "select distinct a.enrollment_id, c.uid from studies a join enrollment b on a.enrollment_id = b.enrollment_id join subjects c on b.subject_id = c.subject_id where a.study_id in (" . implode(',', array_fill(0, count($ids), '?')) . ")";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids)), ...$ids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $ids);
		$uids = array();
		$sqlstringA = "insert ignore into tags (enrollment_id, tag) values (?, ?)";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = (int)$row['enrollment_id'];
			$uids[] = $row['uid'];
			foreach ($taglist as $tag) {
				$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
				mysqli_stmt_bind_param($stmtA, 'is', $enrollmentid, $tag);
				MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$enrollmentid, $tag]);
				mysqli_stmt_close($stmtA);
			}
		}
		mysqli_stmt_close($stmt);

		Notice("Applied tags '" . htmlspecialchars(implode(", ", $taglist)) . "' to subjects " . htmlspecialchars(implode(", ", array_unique($uids))));
	}


	/* -------------------------------------------- */
	/* ------- SetProjectFavorite ----------------- */
	/* -------------------------------------------- */
	/* the user's own favorite flag for a project */
	function SetProjectFavorite($projectid, $favorite) {
		$projectid = (int)$projectid;
		$userid = (int)$_SESSION['userid'];
		$favorite = $favorite ? 1 : 0;
		$sqlstring = "update user_project set favorite = ? where user_id = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iii', $favorite, $userid, $projectid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$favorite, $userid, $projectid]);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- ResetProjectQC --------------------- */
	/* -------------------------------------------- */
	/* reset basic QC ($type 'basic') or mriqc ('mriqc') for every MR series in the project. Offered
	   to admins only (menu.php) */
	function ResetProjectQC($projectid, $type) {
		if (!IsAdminUser()) { Error("Only administrators can reset QC for a whole project"); return; }
		$projectid = (int)$projectid;
		if (GetProjectRow($projectid) === null) { Error("Project not found"); return; }

		$sqlstring = "select a.mrseries_id from mr_series a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$seriesids = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriesids[] = (int)$row['mrseries_id'];
		}
		mysqli_stmt_close($stmt);
		if (count($seriesids) == 0) { Notice("No MR series in this project"); return; }

		if ($type == "mriqc") {
			ResetMRIQC($seriesids);
		}
		else {
			ResetQA($seriesids);
			Notice("Basic QC reset for " . count($seriesids) . " MR series. It will be regenerated");
		}
	}


	/* -------------------------------------------- */
	/* ------- ObliterateSubject ------------------ */
	/* -------------------------------------------- */
	/* queue deletion of the subjects of the selected studies (site admin 'Powerful Tools') */
	function ObliterateSubject($projectid, $studyids) {
		$ids = CheckPowerToolAccess($projectid, $studyids);
		if ($ids === null) return;

		$subjects = GetStudiesSubjects($ids);
		QueueFileIORequests('delete', 'subject', array_keys($subjects));
		Notice("Subjects [" . htmlspecialchars(implode(', ', $subjects)) . "] queued for obliteration");
	}


	/* -------------------------------------------- */
	/* ------- ObliterateStudy -------------------- */
	/* -------------------------------------------- */
	function ObliterateStudy($projectid, $studyids) {
		$ids = CheckPowerToolAccess($projectid, $studyids);
		if ($ids === null) return;

		QueueFileIORequests('delete', 'study', $ids);
		Notice("Studies [" . implode(', ', $ids) . "] queued for obliteration");
	}


	/* -------------------------------------------- */
	/* ------- RearchiveStudies ------------------- */
	/* -------------------------------------------- */
	function RearchiveStudies($projectid, $studyids, $matchidonly) {
		$ids = CheckPowerToolAccess($projectid, $studyids);
		if ($ids === null) return;

		QueueFileIORequests($matchidonly ? 'rearchiveidonly' : 'rearchive', 'study', $ids);
		Notice("Studies [" . implode(', ', $ids) . "] queued for re-archiving");
	}


	/* -------------------------------------------- */
	/* ------- RearchiveSubjects ------------------ */
	/* -------------------------------------------- */
	function RearchiveSubjects($projectid, $studyids, $matchidonly) {
		$ids = CheckPowerToolAccess($projectid, $studyids);
		if ($ids === null) return;

		$subjects = GetStudiesSubjects($ids);
		QueueFileIORequests($matchidonly ? 'rearchiveidonly' : 'rearchive', 'subject', array_keys($subjects));
		Notice("Subjects [" . htmlspecialchars(implode(', ', $subjects)) . "] queued for re-archiving");
	}


	/* -------------------------------------------- */
	/* ------- ChangeProject ---------------------- */
	/* -------------------------------------------- */
	/* move the selected studies to the subject's enrollment in another project, enrolling the subject
	   there if needed (site admin 'Powerful Tools') */
	function ChangeProject($projectid, $newprojectid, $studyids) {
		$ids = CheckPowerToolAccess($projectid, $studyids);
		if ($ids === null) return;
		$newprojectid = (int)$newprojectid;
		$newproject = GetProjectRow($newprojectid);
		if ($newproject === null) { Error("Destination project not found"); return; }
		$newprojectname = htmlspecialchars($newproject['project_name']);

		$msgs = array();
		foreach ($ids as $studyid) {
			/* the study, its enrollment and subject */
			$sqlstring = "select a.study_num, a.enrollment_id, b.subject_id, c.uid from studies a join enrollment b on a.enrollment_id = b.enrollment_id join subjects c on b.subject_id = c.subject_id where a.study_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $studyid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
			$srow = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$srow) { $msgs[] = "<span class='ui red text'>Study [$studyid] is not part of an enrollment... <b>skipping</b></span>"; continue; }
			$subjectid = (int)$srow['subject_id'];
			$studyname = htmlspecialchars($srow['uid'] . $srow['study_num']);

			/* the subject's enrollment in the new project, creating it if needed */
			$sqlstring = "select enrollment_id from enrollment where project_id = ? and subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $newprojectid, $subjectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newprojectid, $subjectid]);
			$erow = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($erow) {
				$newenrollmentid = (int)$erow['enrollment_id'];
			}
			else {
				$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values (?, ?, now())";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ii', $newprojectid, $subjectid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newprojectid, $subjectid]);
				$newenrollmentid = (int)mysqli_insert_id($GLOBALS['linki']);
				mysqli_stmt_close($stmt);
				$msgs[] = "Enrolled subject <b>" . htmlspecialchars($srow['uid']) . "</b> in project <b>$newprojectname</b>";
			}

			if ((int)$srow['enrollment_id'] == $newenrollmentid) {
				$msgs[] = "Study $studyname is already in project $newprojectname";
				continue;
			}
			$sqlstring = "update studies set enrollment_id = ? where study_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $newenrollmentid, $studyid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newenrollmentid, $studyid]);
			mysqli_stmt_close($stmt);
			$msgs[] = "Moved study <b>$studyname</b> to project <b>$newprojectname</b>";
		}
		Notice("<ul><li>" . implode("<li>", $msgs) . "</ul>", "Moving studies to $newprojectname");
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyAge --------------------- */
	/* -------------------------------------------- */
	/* set each study's StudyAge to the age calculated from the subject's DOB and the study date */
	function UpdateStudyAge($projectid) {
		$project = CheckProjectPerm($projectid, array('modifydata'));
		if ($project === null) return;
		$projectid = (int)$projectid;

		$sqlstring = "select a.study_id, a.study_datetime, a.study_ageatscan, d.birthdate from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects d on d.subject_id = b.subject_id where b.project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		mysqli_stmt_close($stmt);

		$numupdated = 0;
		$sqlstringA = "update studies set study_ageatscan = ? where study_id = ?";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			list($studyAge, $calcStudyAge) = GetStudyAge($row['birthdate'], $row['study_ageatscan'], $row['study_datetime']);
			if ($calcStudyAge != null) {
				$studyid = (int)$row["study_id"];
				$calcStudyAge = (float)$calcStudyAge;
				$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
				mysqli_stmt_bind_param($stmtA, 'di', $calcStudyAge, $studyid);
				MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$calcStudyAge, $studyid]);
				mysqli_stmt_close($stmtA);
				$numupdated++;
			}
		}

		Notice("Updated StudyAge for $numupdated studies");
	}


	/* -------------------------------------------- */
	/* ------- GetSubjectGridRows ----------------- */
	/* -------------------------------------------- */
	/* the rows for the project's subject grids. PHI (DOB, race, ethnicity, handedness, education,
	   marital status) is blanked unless the user can view the subject's PHI (View PHI on any project
	   the subject is enrolled in), and each row says what the user may edit: PHI needs Edit PHI on
	   the subject, other subject fields need Edit Data or Edit PHI on any of the subject's projects.
	   $isactive null returns active and deleted subjects */
	function GetSubjectGridRows($projectid, $isactive = null) {
		$projectid = (int)$projectid;

		/* the projects of every subject in this project, to work out per-subject PHI permissions */
		$subjectprojects = array();
		$sqlstring = "select subject_id, project_id from enrollment where subject_id in (select subject_id from enrollment where project_id = ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$allprojects = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectprojects[(int)$row['subject_id']][] = (int)$row['project_id'];
			$allprojects[(int)$row['project_id']] = 1;
		}
		mysqli_stmt_close($stmt);
		$perms = GetCurrentUserProjectPermissions(array_keys($allprojects));
		$subjectperm = function($subjectid, $perm) use ($subjectprojects, $perms) {
			foreach ($subjectprojects[$subjectid] ?? array() as $pid) {
				if (GetPerm($perms, $perm, $pid)) return true;
			}
			return false;
		};

		/* alternate UIDs: all of the subject's, and those for this project's enrollment */
		$globalaltids = array();
		$projectaltids = array();
		$sqlstring = "select a.subject_id, a.altuid, a.isprimary, a.enrollment_id, b.project_id from subject_altuid a left join enrollment b on a.enrollment_id = b.enrollment_id where a.subject_id in (select subject_id from enrollment where project_id = ?) and a.altuid <> '' order by a.isprimary desc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$sid = (int)$row['subject_id'];
			$altid = ($row['isprimary'] ? "*" : "") . $row['altuid'];
			$globalaltids[$sid][$altid] = 1;
			if ((int)$row['project_id'] == $projectid) $projectaltids[$sid][$altid] = 1;
		}
		mysqli_stmt_close($stmt);

		$educations = array(0 => "Unknown", 1 => "Grade School", 2 => "Middle School", 3 => "High School/GED", 4 => "Trade School", 5 => "Associates Degree", 6 => "Bachelors Degree", 7 => "Masters Degree", 8 => "Doctoral Degree");

		$sqlstring = "select a.subject_id, a.uid, a.guid, a.sex, a.gender, a.birthdate, a.ethnicity1, a.ethnicity2, a.handedness, a.education, a.marital_status, a.smoking_status, a.isactive, b.enrollment_id, b.enroll_subgroup, b.enroll_status from subjects a join enrollment b on a.subject_id = b.subject_id where b.project_id = ?" . (($isactive === null) ? "" : " and a.isactive = ?") . " order by a.uid";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		if ($isactive === null) {
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$params = [$projectid];
		}
		else {
			$isactive = (int)$isactive;
			mysqli_stmt_bind_param($stmt, 'ii', $projectid, $isactive);
			$params = [$projectid, $isactive];
		}
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		$rows = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$sid = (int)$row['subject_id'];
			$canviewphi = $subjectperm($sid, 'viewphi');
			$education = $row['education'];
			if ($education === null || $education === '') $education = "-";
			else $education = $educations[(int)$education] ?? $education;

			$r = array(
				'id' => $sid,
				'enrollmentid' => (int)$row['enrollment_id'],
				'isactive' => (int)$row['isactive'],
				'uid' => $row['uid'] ?? '',
				'globalaltuids' => implode(", ", array_keys($globalaltids[$sid] ?? array())),
				'altuids' => implode(", ", array_keys($projectaltids[$sid] ?? array())),
				'guid' => $row['guid'] ?? '',
				'sex' => $row['sex'] ?? '',
				'gender' => $row['gender'] ?? '',
				'smoking' => $row['smoking_status'] ?? '',
				'enrollgroup' => $row['enroll_subgroup'] ?? '',
				'enrollstatus' => $row['enroll_status'] ?? '',
				'canviewphi' => $canviewphi,
				'canmodifyphi' => $subjectperm($sid, 'modifyphi'),
				'canedit' => ($subjectperm($sid, 'modifydata') || $subjectperm($sid, 'modifyphi')),
			);
			/* PHI only if the user may view it */
			$phi = array('dob' => $row['birthdate'] ?? '', 'ethnicity1' => $row['ethnicity1'] ?? '', 'ethnicity2' => $row['ethnicity2'] ?? '', 'handedness' => $row['handedness'] ?? '', 'education' => $education, 'marital' => $row['marital_status'] ?? '');
			foreach ($phi as $k => $v) {
				$r[$k] = $canviewphi ? $v : '';
			}
			$rows[] = $r;
		}
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- GridScript ------------------------- */
	/* -------------------------------------------- */
	/* shared JavaScript for the editable grids: HTML escaping for cell renderers, the gray
	   'no view permissions' box, and saving an edited cell to ajaxapi.php by POST with the CSRF token.
	   $saveaction is the ajaxapi action; $extra are fixed fields sent with every save */
	function GridScript($saveaction, $extra) {
		?>
		<script type="text/javascript">
			/* escape a value for insertion into HTML */
			function gridEsc(s) {
				return String((s === null) || (s === undefined) ? '' : s).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
			}
			/* renderer for PHI columns: the value, or the gray box if the user can't view it */
			function gridPHIRenderer(params) {
				if (!params.data.canviewphi) return <?=JSONForScript(NoViewPermission())?>;
				return gridEsc(params.value);
			}
			/* save an edited cell. On failure the cell is put back to its old value */
			function gridSaveCell(event, fields) {
				if (event.source === 'revert') return; /* our own revert after a failed save */
				const body = new FormData();
				body.append('action', <?=JSONForScript($saveaction)?>);
				body.append('csrftoken', <?=JSONForScript($_SESSION['csrftoken'] ?? '')?>);
				const extra = <?=JSONForScript($extra)?>;
				for (const k in extra) body.append(k, extra[k]);
				for (const k in fields) body.append(k, fields[k]);
				body.append('column', event.column.getColDef().field);
				body.append('value', (event.newValue === null) || (event.newValue === undefined) ? '' : event.newValue);
				const col = gridEsc(event.column.getColDef().headerName || event.column.getColDef().field);
				fetch('ajaxapi.php', { method: 'POST', body: body, credentials: 'same-origin' })
					.then(function(r) { return r.text(); })
					.then(function(text) {
						if (text.trim() == "success") {
							document.getElementById("updateresult").innerHTML = '<div class="ui success message">Success updating <b>' + col + '</b> to \'' + gridEsc(event.newValue) + '\'</div>';
						}
						else {
							document.getElementById("updateresult").innerHTML = '<div class="ui error message">Error updating <b>' + col + '</b>: ' + gridEsc(text) + '</div>';
							event.node.setDataValue(event.column.getColId(), event.oldValue, 'revert');
						}
					})
					.catch(function() {
						document.getElementById("updateresult").innerHTML = '<div class="ui error message">Error updating <b>' + col + '</b></div>';
					});
			}
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubjectsTable --------------- */
	/* -------------------------------------------- */
	function DisplaySubjectsTable($id, $isactive=1) {
		/* the subject list needs View Data or View PHI on the project; PHI columns are blanked per
		   subject without View PHI, and each column is editable only with the matching permission */
		$project = CheckProjectPerm($id, array('viewdata', 'viewphi'));
		if ($project === null) return;
		$id = (int)$id;
		$pperms = GetCurrentUserProjectPermissions(array($id));
		$canmodifydata = (bool)GetPerm($pperms, 'modifydata', $id);
		$cancsvedit = ($canmodifydata || GetPerm($pperms, 'modifyphi', $id));

		/* Get all subjects (active and deleted), and their enrollment info, associated with the
		   project. Everything is loaded so the table can be filtered client-side by status and by
		   deleted state; the grid defaults to showing active subjects only. */
		$rows = GetSubjectGridRows($id, null);
		$numsubjects = 0;
		foreach ($rows as $row) { if ($row['isactive'] == 1) $numsubjects++; }   /* initial view shows active subjects only */
		/* total enrolled in this project, across all statuses and active + deleted */
		$numtotalsubjects = count($rows);

		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<br>
		<div class="ui text container">
			<span id="updateresult"></span>
		</div>
	  
		<div class="ui top attached yellow segment">
			<div class="ui red right corner label" title="This table is editable. Changes are permanent after editing each cell."><i class="exclamation circle icon"></i></div>
			<div class="ui grid">
				<div class="eight wide column">
					<h2 class="ui header" style="margin-bottom: 6px">Subjects</h2>
					<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px">
						<span style="color: rgba(0,0,0,.55)">Displaying <span id="subjectcount"><?=$numsubjects?></span> of <?=$numtotalsubjects?> subjects</span>
						&nbsp;<b>View:</b>
						<div class="ui small basic buttons">
							<button type="button" class="ui button subjectfilter active" data-mode="active">All active</button>
							<button type="button" class="ui button subjectfilter" data-mode="consented" title="Active and consented">Consented</button>
							<button type="button" class="ui button subjectfilter" data-mode="completed">Completed</button>
							<button type="button" class="ui button subjectfilter" data-mode="excluded">Excluded</button>
						</div>
						&nbsp;
						<button type="button" class="ui small basic button subjectfilter" data-mode="deleted">Inactive/Deleted</button>
					</div>
				</div>
				<div class="right aligned seven wide column">
					<? if ($cancsvedit) { ?>
					<button class="ui small basic primary compact button" id="batchsubjectupdatebutton"> Batch update subjects...</button> &nbsp;
					<? } ?>
					<? if ($canmodifydata) { ?>
					<button class="ui small basic primary compact button" id="batchsubjectaddbutton"><i class="user plus icon"></i> Batch add subjects...</button> &nbsp;
					<? } ?>
					<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
				</div>
			</div>
		</div>
		<div class="ui modal" id="batchaddmodal">
			<div class="header">Batch Add Subjects</div>
			<div class="scrolling content">
				<form action="projects.php" method="post" class="ui form">
					<input type="hidden" name="action" value="batchaddsubjects">
					<input type="hidden" name="projectid" value="<?=$id?>">
					<?=CSRFTokenField()?>
					<div class="field">
						<label>Paste .csv formatted data</label>
						<textarea name="csv" style="font-family:monospace"></textarea>
					</div>
					<p><i class="blue question circle icon"></i> <b>Formatting Guide</b></p>
					<b>Sample .csv format</b>
					<div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
						ID, sex, gender, status, enrollgroup<br>
						SUBJ001, F, F, consented, control<br>
						SUBJ002, M, M, consented, patient
					</div>
					<br><br>
					<b>Required columns</b>
					<table class="ui very compact small celled table">
						<thead>
							<tr><th>Column</th><th>Description</th></tr>
						</thead>
						<tbody>
							<tr><td><code>ID</code></td><td>Subject identifier; stored as an alternate UID</td></tr>
							<tr><td><code>sex</code></td><td>Possible values: <tt>F, M, O, U</tt></td></tr>
							<tr><td><code>gender</code></td><td>Possible values: <tt>F, M, O, U</tt></td></tr>
							<tr><td><code>status</code></td><td>Enrollment status. Possible values: <tt>consented, excluded, completed,</tt> or blank</td></tr>
							<tr><td><code>enrollgroup</code></td><td>Enrollment group (e.g. <tt>control, patient</tt>)</td></tr>
						</tbody>
					</table>
					<b>Optional columns</b>
					<table class="ui very compact small celled table">
						<thead>
							<tr><th>Column</th><th>Description</th></tr>
						</thead>
						<tbody>
							<tr><td><code>birthdate</code></td><td>Format <tt>YYYY-MM-DD</tt> (defaults to <tt>0000-00-00</tt> if omitted)</td></tr>
							<tr><td><code>education</code></td><td>Possible values: 0 (Unknown) through 8 (Doctoral Degree)</td></tr>
							<tr><td><code>ethnicity1</code></td><td>Possible values: <tt>hispanic, nothispanic</tt></td></tr>
							<tr><td><code>ethnicity2</code></td><td>Possible values: <tt>unknown, asian, black, white, indian, islander, mixed, other</tt></td></tr>
							<tr><td><code>firstname</code></td><td>Stored in the subject name as <tt>lastname^firstname</tt></td></tr>
							<tr><td><code>guid</code></td><td>Globally unique ID for NDA submission</td></tr>
							<tr><td><code>handedness</code></td><td>Possible values: <tt>R, L, A, U</tt></td></tr>
							<tr><td><code>lastname</code></td><td>Stored in the subject name as <tt>lastname^firstname</tt></td></tr>
							<tr><td><code>maritalstatus</code></td><td>Possible values: <tt>unknown, married, single, divorced, separated, civilunion, cohabitating, widowed</tt></td></tr>
							<tr><td><code>smokingstatus</code></td><td>Possible values: <tt>unknown, never, current, past</tt></td></tr>
						</tbody>
					</table>
				</div>
			<div class="actions">
				<input type="submit" class="ui approve button" value="Add Subjects">
				<div class="ui cancel button">Cancel</div>
				</form>
			</div>
		</div>

		<div class="ui modal" id="batchmodal">
			<div class="header">Batch Update Subject Information</div>
			<div class="scrolling content">
				<form action="projects.php" method="post" class="ui form">
					<input type="hidden" name="action" value="batchupdatesubject">
					<input type="hidden" name="projectid" value="<?=$id?>">
					<?=CSRFTokenField()?>
					<div class="field">
						<label>Paste .csv formatted data</label>
						<textarea name="csv" style="font-family:monospace"></textarea>
					</div>
					<p><i class="blue question circle icon"></i> <b>Formatting Guide</b></p>
					
					<b>Sample .csv format</b>
					<div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
						uid, guid, sex, enrollgroup<br>
						S1234ABC, NDA23548239, F, control
					</div>
					<br>
					Available columns (.csv may contain any set of those columns, but <b>must</b> always contain <tt>uid</tt>)
					<table class="ui very compact small celled table">
						<thead>
							<tr><th>Column</th><th>Description</th></tr>
						</thead>
						<tbody>
							<tr style="color: darkred"><td><code>uid</code></td><td>The <b>UID</b> of the subject (required)</td></tr>
							<tr><td><code>altuids</code></td><td>Space delimited list of alternate UIDs with asterisk indicating primary alternate ID. Example <code>*1234 AB539 ID2054</code></td></tr>
							<tr><td><code>birthdate</code></td><td>Format <tt>YYYY-MM-DD</tt></td></tr>
							<tr><td><code>education</code></td><td>Possible values: 0 (Unknown), 1 (Grade School), 2 (Middle School), 3 (High School/GED), 4 (Trade School), 5 (Associates Degree), 6 (Bachelors Degree), 7 (Masters Degree), 8 (Doctoral Degree)</td></tr>
							<tr><td><code>enrollgroup</code></td><td>Enrollment group</td></tr>
							<tr><td><code>ethnicity1</code></td><td>Possible values: <tt>hispanic, nothispanic</tt></td></tr>
							<tr><td><code>ethnicity2</code></td><td>Possible values: <tt>unknown, asian, black, white, indian, islander, mixed, other</tt></td></tr>
							<tr><td><code>firstname</code></td><td>Stored in the subject name as <tt>lastname^firstname</tt></td></tr>
							<tr><td><code>gender</code></td><td>Possible values: <tt>F, M, O, U</tt></td></tr>
							<tr><td><code>guid</code></td><td>Globally unique ID for NDA submission</td></tr>
							<tr><td><code>handedness</code></td><td>Possible values: <tt>R, L, A, U</tt></td></tr>
							<tr><td><code>icd10</code></td><td>ICD10 code. See list of available <a href="icd10.php" target="newwindow">codes</a>.</td></tr>
							<tr><td><code>lastname</code></td><td>Stored in the subject name as <tt>lastname^firstname</tt></td></tr>
							<tr><td><code>marital</code></td><td>Possible values: <tt>unknown, married, single, divorced, separated, civilunion, cohabitating, widowed</tt></td></tr>
							<tr><td><code>sex</code></td><td>Possible values: <tt>F, M, O, U</tt></td></tr>
							<tr><td><code>smoking</code></td><td>Possible values: <tt>unknown, never, current, past</tt></td></tr>
							<tr><td><code>status</code></td><td>Enrollment status. Possible values: <tt>consented, enrolled, completed, excluded</tt></td></tr>
						</tbody>
					</table>
				</div>
			<div class="actions">
				<input type="submit" class="ui approve button" value="Update">
				<div class="ui cancel button">Cancel</div>
				</form>
			</div>
		</div>
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<style>
			.completed { background-color: #bfb; }
			.excluded { background-color: #eee; color: #444; }
		</style>
		<? GridScript('updatesubjectdetails', array('projectid' => $id)); ?>
		<script type="text/javascript">
			let gridApi;
			const canModifyData = <?=JSONForScript($canmodifydata)?>;

			/* the full subject dataset (active + deleted); the grid is given only the filtered subset */
			const allSubjectRows = <?=JSONForScript($rows)?>;

			/* subject table filter: single-select. one of
			     'active'    - all active subjects, any enrollment status
			     'consented' / 'completed' / 'excluded' - active subjects with that status
			     'deleted'   - all deleted subjects (isactive=0), any status */
			let subjectFilterMode = 'active';

			/* Filter the dataset and hand the grid only the matching rows. Replacing rowData (rather
			   than using an ag-grid external filter) guarantees the table never keeps stale rows: the
			   grid only ever contains what should be visible. getRowId lets ag-grid diff the change
			   and preserve sort/scroll. */
			function applySubjectFilter() {
				if (!gridApi) return;
				const mode = subjectFilterMode;
				const filtered = allSubjectRows.filter(function(r) {
					const deleted = (Number(r.isactive) === 0);
					if (mode === 'deleted') return deleted;   /* all deleted, regardless of status */
					if (deleted) return false;                /* every other mode is active-only */
					if (mode === 'active') return true;       /* all active */
					return r.enrollstatus === mode;           /* active with this enrollment status */
				});
				gridApi.setGridOption('rowData', filtered);
				document.getElementById('subjectcount').textContent = filtered.length;
			}

			$(document).ready(function(){
				$('#batchsubjectupdatebutton').click(function(){
					$('#batchmodal').modal('show');
				});
				$('#batchsubjectaddbutton').click(function(){
					$('#batchaddmodal').modal('show');
				});

				/* filter buttons: single-select. clicking one shows that group and highlights it */
				document.querySelectorAll('.subjectfilter').forEach(function(btn) {
					btn.addEventListener('click', function() {
						subjectFilterMode = this.getAttribute('data-mode');
						document.querySelectorAll('.subjectfilter').forEach(function(b) { b.classList.remove('active'); });
						this.classList.add('active');
						applySubjectFilter();
					});
				});
			});
			
			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridApi.exportDataAsCsv( {allColumns: false} );
			}			

			// Grid Options are properties passed to the grid
			const gridOptions = {
				theme: agGrid.themeBalham,

				// each entry here represents one column
				columnDefs: [
					{ field: 'id', hide: true },
					{ field: 'enrollmentid', hide: true },
					{
						headerName: "UID",
						field: "uid",
						pinned: 'left',
						width: 150,
						cellRenderer: function(params) {
							return '<a href="subjects.php?id=' + encodeURIComponent(params.data.id) + '">' + gridEsc(params.value) + '</a>'
						}
					},
					{
						headerName: "Status",
						field: "enrollstatus",
						editable: canModifyData,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'consented', 'excluded', 'completed'],
							valueListGap: 0
						}
					},
					{ 
						headerName: "Global Alt UIDs",
						field: "globalaltuids",
						editable: false,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + gridEsc(params.value) + '</tt>'
						},
						
					},
					{ 
						headerName: "Project Alt UIDs",
						field: "altuids",
						editable: canModifyData,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + gridEsc(params.value) + '</tt>'
						},
						
					},
					{ headerName: "GUID", field: "guid", editable: (p) => p.data.canedit },
					{ headerName: "Birthdate", field: "dob", editable: (p) => p.data.canmodifyphi, cellDataType: 'dateString', cellRenderer: gridPHIRenderer },
					{
						headerName: "Sex",
						field: "sex",
						editable: (p) => p.data.canedit,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Gender",
						field: "gender",
						editable: (p) => p.data.canedit,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Ethnicity1",
						field: "ethnicity1",
						editable: (p) => p.data.canmodifyphi,
						cellRenderer: gridPHIRenderer,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'hispanic', 'nothispanic'],
							valueListGap: 0
						}
					},
					{ 
						headerName: "Ethnicity2",
						field: "ethnicity2",
						editable: (p) => p.data.canmodifyphi,
						cellRenderer: gridPHIRenderer,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['unknown','','asian','black','white','indian','islander','mixed','other'],
							valueListGap: 0
						}
					},
					{
						headerName: "Handedness",
						field: "handedness",
						editable: (p) => p.data.canmodifyphi,
						cellRenderer: gridPHIRenderer,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['', 'R', 'L', 'U', 'A'], valueListGap: 0 }
					},
					{
						headerName: "Education",
						field: "education",
						editable: (p) => p.data.canmodifyphi,
						cellRenderer: gridPHIRenderer,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['Unknown', '', 'Grade School', 'Middle School', 'High School/GED', 'Trade School', 'Associates Degree', 'Bachelors Degree', 'Masters Degree', 'Doctoral Degree'], valueListGap: 0 }
					},
					{
						headerName: "Marital Status",
						field: "marital",
						editable: (p) => p.data.canmodifyphi,
						cellRenderer: gridPHIRenderer,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['unknown', '', 'married', 'single', 'divorced', 'separated', 'civilunion', 'cohabitating', 'widowed'], valueListGap: 0 }
					},
					{
						headerName: "Smoking Status",
						field: "smoking",
						editable: (p) => p.data.canedit,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['unknown', '', 'never', 'current', 'past'], valueListGap: 0 }
					},
					{ headerName: "Enroll Sub-group", field: "enrollgroup", editable: canModifyData },
				],

				/* rows are supplied by applySubjectFilter() after the grid is created */
				rowData: [],

				/* Give each row a stable identity so ag-grid can diff rowData changes (from the
				   Consented/Completed/Excluded/Deleted filters) and preserve sort and scroll. */
				getRowId: (params) => String(params.data.enrollmentid),

				rowClassRules: {
					"completed": "data.enrollstatus == 'completed'",
					"excluded": "data.enrollstatus == 'excluded'",
				},
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},

				rowSelection: { mode: 'multiRow' }, // allow rows to be selected
				animateRows: false,
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
				onCellValueChanged: (event) => {
					gridSaveCell(event, { subjectid: event.data.id, enrollmentid: event.data.enrollmentid });
				},				
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				// new grid instance, passing in the hosting DIV and Grid Options
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);

				/* load the default (active subjects) view into the grid */
				applySubjectFilter();

				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}

			function onSubmitForm() {
				const selectedRows = gridApi.getSelectedRows();
				
				/* create comma-separated list of objectIDs */
				let objectIDs = [];
				for (let i=0; i < selectedRows.length; i++) {
					objectIDs.push(selectedRows[i].enrollmentid);
				}
				/* reuse the field if it exists, so repeated submits don't stack duplicate inputs */
				let field = document.thesubjectform.querySelector("input[name='objectidstr']");
				if (!field) {
					field = document.createElement('input');
					field.type = 'hidden';
					field.name = 'objectidstr';
					document.thesubjectform.appendChild(field);
				}
				field.value = objectIDs.join(",");
				return true;
			}

			/* send the selected enrollments to packages.php. form.submit() does not fire the onsubmit
			   handler, so build the ID list explicitly first. setAttribute is used because the form
			   has an input named 'action', which shadows form.action */
			function AddSelectedToPackage() {
				if (gridApi.getSelectedRows().length < 1) {
					alert("Select one or more subjects to add to a package");
					return;
				}
				const form = document.thesubjectform;
				onSubmitForm();
				form.setAttribute('action', 'packages.php');
				form.querySelector("input[name='action']").value = 'addobject';
				form.submit();
			}

		</script>
		
		<br>
		<form method="post" action="projects.php" id="thesubjectform" name="thesubjectform" onSubmit="return onSubmitForm();">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="action" value="">
			<input type="hidden" name="objecttype" value="enrollment">
			
			<!-- leaving the button code in case we add more functionality to this form that uses the same list of selected enrollmentRowIDs -->
			<button type="button" class="ui basic brown button" title="Add the selected subjects to a squirrel package" onclick="AddSelectedToPackage()"><img src="images/squirrel-icon-64.png" height="15"></img> Add to squirrel package</button>

		</form>
		
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayCompactSubjectsTable -------- */
	/* -------------------------------------------- */
	function DisplayCompactSubjectsTable($id, $isactive=1) {
		/* shown on the project page. Needs View Data or View PHI on the project (no error otherwise,
		   since the rest of the project page is visible to everyone) */
		$id = (int)$id;
		$pperms = GetCurrentUserProjectPermissions(array($id));
		if (!GetPerm($pperms, 'viewdata', $id) && !GetPerm($pperms, 'viewphi', $id)) {
			?><div class="ui message">You don't have permission to view this project's subjects</div><?
			return;
		}
		$canmodifydata = (bool)GetPerm($pperms, 'modifydata', $id);

		$rows = GetSubjectGridRows($id, (int)$isactive);
		$numsubjects = count($rows);
		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<br>
		<div class="ui text container">
			<span id="updateresult"></span>
		</div>
	  
		<div class="ui top attached yellow segment">
			<div class="ui red right corner label" title="This table is editable. Changes are permanent after editing each cell."><i class="exclamation circle icon"></i></div>
			<div class="ui grid">
				<div class="eight wide column">
					<h2 class="ui header">
						Subjects
						<div class="sub header">Displaying <?=$numsubjects?> subjects</div>
					</h2>
				</div>
				<div class="right aligned seven wide column">
					<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
				</div>
			</div>
		</div>
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<style>
			.completed { background-color: #bfb; }
			.excluded { background-color: #eee; color: #444; }
		</style>
		<? GridScript('updatesubjectdetails', array('projectid' => $id)); ?>
		<script type="text/javascript">
			let gridApi;
			const canModifyData = <?=JSONForScript($canmodifydata)?>;

			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridApi.exportDataAsCsv();
			}			

			// Grid Options are properties passed to the grid
			const gridOptions = {

				// each entry here represents one column
				columnDefs: [
					{ field: 'id', hide: true },
					{
						headerName: "UID",
						field: "uid",
						pinned: 'left',
						width: 150,
						cellRenderer: function(params) {
							return '<a href="subjects.php?id=' + encodeURIComponent(params.data.id) + '">' + gridEsc(params.value) + '</a>'
						}
					},
					{
						headerName: "Status",
						field: "enrollstatus",
						editable: canModifyData,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'consented', 'excluded', 'completed'],
							valueListGap: 0
						}
					},
					{ 
						headerName: "Global Alt UIDs",
						field: "globalaltuids",
						editable: false,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + gridEsc(params.value) + '</tt>'
						},
						
					},
					{ 
						headerName: "Alt UIDs",
						field: "altuids",
						editable: canModifyData,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + gridEsc(params.value) + '</tt>'
						},
						
					},
					{
						headerName: "Sex",
						field: "sex",
						editable: (p) => p.data.canedit,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Gender",
						field: "gender",
						editable: (p) => p.data.canedit,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{ headerName: "Enroll Sub-group", field: "enrollgroup", editable: canModifyData },
				],

				rowData: <?=JSONForScript($rows)?>,
				
				rowClassRules: {
					"completed": "data.enrollstatus == 'completed'",
					"excluded": "data.enrollstatus == 'excluded'",
				},
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},

				rowSelection: { mode: 'multiRow' }, // allow rows to be selected
				animateRows: false, // have rows animate to new positions when sorted
				rowHeight: 28,
				headerHeight: 34,
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
				autoSizeStrategy: { type: 'fitCellContents' },
				onCellValueChanged: (event) => {
					gridSaveCell(event, { subjectid: event.data.id, enrollmentid: event.data.enrollmentid });
				},				
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				// new grid instance, passing in the hosting DIV and Grid Options
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}

		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudiesTable ---------------- */
	/* -------------------------------------------- */
	function DisplayStudiesTable($id, $isactive=1) {
		/* the study list needs View Data on the project; editing needs Edit Data */
		$project = CheckProjectPerm($id, array('viewdata'));
		if ($project === null) return;
		$id = (int)$id;
		$pperms = GetCurrentUserProjectPermissions(array($id));
		$canmodifydata = (bool)GetPerm($pperms, 'modifydata', $id);
		$issiteadmin = isSiteAdmin();
		?>

		<script type="text/javascript">
			$(window).on('load', function() {
				$('#pageloading').hide();
			});
		</script>
		<br>
		<div class="ui text container" id="pageloading">
			<div class="ui yellow message" align="center">
				<i class="spinner loading icon"></i> Loading study list...
			</div>
		</div>

		<?
			@ob_flush();
			@flush();

		/* this project's alternate UIDs for each subject */
		$altids = array();
		$sqlstring = "select a.subject_id, a.altuid, a.isprimary from subject_altuid a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ? and a.altuid <> '' order by a.isprimary desc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$altids[(int)$row['subject_id']][($row['isprimary'] ? "*" : "") . $row['altuid']] = 1;
		}
		mysqli_stmt_close($stmt);

		/* studies associated with this project. (DOB is only used to calculate the age, which isn't PHI) */
		$sqlstring = "select a.study_id, a.study_modality, a.study_datetime, a.study_site, a.study_num, a.study_desc, a.study_type, a.study_alternateid, a.study_ageatscan, d.sex, d.gender, d.birthdate, d.uid, d.subject_id from studies a join enrollment b on a.enrollment_id = b.enrollment_id join subjects d on d.subject_id = b.subject_id where b.project_id = ? order by d.uid asc, a.study_modality asc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);
		$numstudies = mysqli_num_rows($result);
		$rows = array();
		$lastuid = "";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'] ?? '';
			$subjectid = (int)$row['subject_id'];
			$studyageatscan = number_format((float)$row['study_ageatscan'], 1);

			list($studyAge, $calcStudyAge) = GetStudyAge($row['birthdate'], $studyageatscan, $row['study_datetime']);
			$calcStudyAge = ($calcStudyAge == null) ? "-" : number_format($calcStudyAge, 1);

			$rows[] = array(
				'subjectid' => $subjectid,
				'studyid' => (int)$row['study_id'],
				'rowhighlight' => ($uid != $lastuid) ? 1 : 0,
				'uid' => $uid,
				'sex' => $row['sex'] ?? '',
				'gender' => $row['gender'] ?? '',
				'altuids' => implode(", ", array_keys($altids[$subjectid] ?? array())),
				'uidstudynum' => $uid . $row['study_num'],
				'visit' => $row['study_type'] ?? '',
				'studydate' => $row['study_datetime'] ?? '',
				'studyage' => $studyageatscan,
				'calcstudyage' => $calcStudyAge,
				'modality' => $row['study_modality'] ?? '',
				'desc' => $row['study_desc'] ?? '',
				'study_id' => $row['study_alternateid'] ?? '',
				'site' => $row['study_site'] ?? '',
			);
			$lastuid = $uid;
		}
		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<br>
		<div class="ui text container">
			<span id="updateresult"></span>
		</div>
	  
		<div class="ui top attached yellow segment">
			<div class="ui red right corner label" title="This table is editable. Changes are permanent after editing each cell."><i class="exclamation circle icon"></i></div>
			<div class="ui grid">
				<div class="eight wide column">
					<h2 class="ui header">
						Studies
						<div class="sub header">Displaying <?=$numstudies?> studies</div>
					</h2>
				</div>
				<div class="right aligned seven wide column">
					<? if ($canmodifydata) { ?>
					<button type="button" class="ui small basic primary compact button" id="batchstudyupdatebutton" title="Paste a CSV to batch update studies">Batch Update...</button>
					<form method="post" action="projects.php" style="display: inline; margin: 0">
						<input type="hidden" name="action" value="updatestudyage">
						<input type="hidden" name="id" value="<?=$id?>">
						<?=CSRFTokenField()?>
						<button type="submit" class="ui small basic primary compact button" title="Set StudyAge to CalcStudyAge for all studies">Update StudyAge</button>
					</form>
					<? } ?>
					<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
				</div>
			</div>
		</div>

		<div class="ui modal" id="batchstudymodal">
			<div class="header">Batch Update Studies</div>
			<div class="scrolling content">
				<form action="projects.php" method="post" class="ui form">
					<input type="hidden" name="action" value="batchupdatestudy">
					<?=CSRFTokenField()?>
					<input type="hidden" name="projectid" value="<?=$id?>">
					<div class="field">
						<label>Paste .csv formatted data</label>
						<textarea name="csv" style="font-family:monospace"></textarea>
					</div>
					<p><i class="blue question circle icon"></i> <b>Formatting Guide</b></p>

					<b>Sample .csv format</b>
					<div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
						uid, studynum, visit, studyage<br>
						S1234ABC, 1, baseline, 24.5
					</div>
					<br>
					Available columns (.csv may contain any set of the optional columns, but <b>must</b> always contain <tt>uid</tt> and <tt>studynum</tt> to identify each study)
					<table class="ui very compact small celled table">
						<thead>
							<tr><th>Column</th><th>Description</th></tr>
						</thead>
						<tbody>
							<tr style="color: darkred"><td><code>uid</code></td><td>The subject <b>UID</b> (required)</td></tr>
							<tr style="color: darkred"><td><code>studynum</code></td><td>The study number for the subject (required)</td></tr>
							<tr><td><code>visit</code></td><td>Study visit / type</td></tr>
							<tr><td><code>studydatetime</code></td><td>Study date/time, format <tt>YYYY-MM-DD HH:MM:SS</tt></td></tr>
							<tr><td><code>studyage</code></td><td>Age at scan (numeric)</td></tr>
							<tr><td><code>description</code></td><td>Study description</td></tr>
							<tr><td><code>studyid</code></td><td>Alternate study ID</td></tr>
							<tr><td><code>site</code></td><td>Study site</td></tr>
						</tbody>
					</table>
				</div>
			<div class="actions">
				<input type="submit" class="ui approve button" value="Update Studies">
				<div class="ui cancel button">Cancel</div>
				</form>
			</div>
		</div>
		<script>
			$(document).ready(function(){
				$('#batchstudyupdatebutton').click(function(){
					$('#batchstudymodal').modal('show');
				});
			});
		</script>

		<form method="post" action="projects.php" id="theform" name="theform" onSubmit="return onSubmitForm();">
		<input type="hidden" name="id" value="<?=$id?>">
		<input type="hidden" name="action" value="">
		<?=CSRFTokenField()?>
		
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<style>
			.rowhighlight {
				border-top: 2px solid #888;
			}
		</style>
		<? GridScript('updatestudydetails', array('projectid' => $id)); ?>
		<script type="text/javascript">
			let gridApi;
			const canModifyData = <?=JSONForScript($canmodifydata)?>;

			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridApi.exportDataAsCsv();
			}			

			// Grid Options are properties passed to the grid
			const gridOptions = {

				// each entry here represents one column
				columnDefs: [
					{ field: 'subjectid', hide: true },
					{ field: 'studyid', hide: true },
					{ field: 'rowhighlight', hide: true },
					{
						headerName: "UID",
						field: "uid",
						pinned: 'left',
						width: 150,
						cellRenderer: function(params) {
							return '<a href="subjects.php?id=' + encodeURIComponent(params.data.subjectid) + '">' + gridEsc(params.value) + '</a>'
						}
					},
					{
						headerName: "Study",
						field: "uidstudynum",
						pinned: 'left',
						cellRenderer: function(params) {
							return '<a href="studies.php?id=' + encodeURIComponent(params.data.studyid) + '"><b>' + gridEsc(params.value) + '</b></a>'
						},
						<? if ($issiteadmin) { ?> checkboxSelection: true, cellStyle: { 'background-color': '#ff9' } <? } ?>
					},
					{
						headerName: "Sex",
						field: "sex",
						editable: canModifyData,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						},
					},
					{
						headerName: "Gender",
						field: "gender",
						editable: canModifyData,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{ 
						headerName: "Subject Alt UIDs",
						field: "altuids",
						editable: canModifyData,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + gridEsc(params.value) + '</tt>'
						},
						
					},
					{ headerName: "Visit", field: "visit", editable: canModifyData },
					{ headerName: "Study Datetime", field: "studydate", editable: false },
					{ headerName: "StudyAge", field: "studyage", editable: canModifyData },
					{ headerName: "Calculated StudyAge", field: "calcstudyage", editable: false },
					{ headerName: "Modality", field: "modality", editable: false },
					{ headerName: "Description", field: "desc", editable: false },
					{ headerName: "Study ID", field: "study_id", editable: false },
					{ headerName: "Site", field: "site", editable: false },
				],

				rowData: <?=JSONForScript($rows)?>,
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},
				rowClassRules: {
					// row style expression
					'rowhighlight': 'data.rowhighlight == 1',
				},

				<? if ($issiteadmin) { ?>
				rowSelection: {
					mode: 'multiRow',
					//headerCheckbox: true,
					checkboxes: true,
				}, // allow rows to be selected
				<? } ?>
				animateRows: false,
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
				autoSizeStrategy: { type: 'fitCellContents' },
				onCellValueChanged: (event) => {
					gridSaveCell(event, { subjectid: event.data.subjectid, studyid: event.data.studyid });
				},				
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				
				// new grid instance, passing in the hosting DIV and Grid Options
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}
			
			function onSubmitForm() {
				const selectedRows = gridApi.getSelectedRows();
				
				let studyIDs = [];
				for (let i=0; i < selectedRows.length; i++) {
					let inputStr = "<input type='hidden' name='studyids[]' value='" + selectedRows[i].studyid + "'>";
					document.theform.insertAdjacentHTML('beforeend', inputStr);
				}
			}
			
		</script>
		
		<? if ($issiteadmin) { ?>
		<div class="ui compact yellow segment" style="background-color: #FFFF99">

			<h2 class="ui header">
				<i class="tools icon"></i>
				<div class="content">
					Powerful Tools
					<div class="sub header">Perform operations on the selected studies</div>
				</div>
			</h2>

			<div class="ui segment">
				<h3 class="ui header">
					Apply enrollment tag(s)
					<div class="sub header">Comma separated list</div>
				</h3>
				<div class="ui action input">
					<input type="text" name="tags" id="tags" list="taglist" multiple>
					<datalist id="taglist">
					<?
						$sqlstring = "select distinct(tag) 'tag' from tags where enrollment_id is not null and enrollment_id <> 0 and enrollment_id <> ''";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$tag = htmlspecialchars($row['tag'] ?? '');
							?>
							<option value="<?=$tag?>">
							<?
						}
					?>
					</datalist>
					<button type="submit" class="ui button" title="Applies the tags to the selected studies" onclick="document.theform.elements['action'].value='applytags'">Apply tags</button>
				</div>
			</div>

			<div class="ui segment">
				<h3 class="ui header">
					Move studies to new project
				</h3>
				<div class="ui action input">
					<select name="newprojectid" id="newprojectid" class="ui dropdown">
					<?
						$sqlstring = "select a.*, b.user_fullname from projects a left join users b on a.project_pi = b.user_id where a.project_status = 'active' order by a.project_name";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$project_id = $row['project_id'];
							$project_name = $row['project_name'];
							$project_costcenter = $row['project_costcenter'];
							$project_enddate = $row['project_enddate'];
							$user_fullname = $row['user_fullname'];
							
							if (strtotime($project_enddate) < strtotime("now")) { $style="color: gray"; } else { $style = ""; }
							?>
							<option value="<?=(int)$project_id?>" style="<?=$style?>"><?=htmlspecialchars($project_name ?? '')?> (<?=htmlspecialchars($project_costcenter ?? '')?>)</option>
							<?
						}
					?>
					</select>
					<button type="submit" class="ui button" title="Moves the imaging studies from this project to the selected project" onclick="document.theform.elements['action'].value='changeproject'">Move studies</button>
				</div>
			</div>
			
			<div class="ui segment">
				<h3 class="ui header">
					Rearchive
				</h3>

				<div class="ui checkbox" title="When re-archiving, only match existing subjects by ID. Do not use the Patient ID, DOB, or Sex fields to match subjects">
					<input type="checkbox" name="matchidonly" value="1" checked>
					<label>Match by ID only</label>
				</div>
				&nbsp; &nbsp;
				<button class="ui orange button" title="Moves all DICOM files back into the incoming directory to be parsed again. Useful if there was an archiving error and too many subjects are in the wrong place." type="submit" onclick="document.theform.elements['action'].value='rearchivestudies'">Re-archive DICOM studies</button>
				&nbsp; &nbsp;
				<button class="ui orange button" title="Moves all DICOM files from this SUBJECT into the incoming directory, and deletes the subject" type="submit" onclick="document.theform.elements['action'].value='rearchivesubjects'">Re-archive Subjects</button>
			</div>
			
			<div class="ui segment">
				<h3 class="ui header">
					Obliterate
				</h3>
				<button class="ui red button" title="Delete the subject permanently" type="submit" onclick="document.theform.elements['action'].value='obliteratesubject'"><i class="bomb icon"></i> Obliterate Subjects</button> &nbsp; &nbsp;
				<button class="ui red button" title="Delete the studies permanently" type="submit" onclick="document.theform.elements['action'].value='obliteratestudy'"><i class="bomb icon"></i> Obliterate Studies</button>
			</div>
		</div>
		<? } ?>

		<!--<td align="right" valign="top">
			<div align="right"><input class="ui primary button" type="submit" value="Save Studies Table"></div>
		</td>-->
		</form>
		
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayNonImagingTable ------------- */
	/* -------------------------------------------- */
	function DisplayNonImagingTable($id, $isactive=1) {
		/* observations are data: View Data on the project */
		$project = CheckProjectPerm($id, array('viewdata'));
		if ($project === null) return;
		$id = (int)$id;
		$isactive = (int)$isactive;
		$name = $project['project_name'];
		
		$rowdata = array();
		
		$observationNames = array();
		$table = array();
		/* get all subjects, and their enrollment info, associated with the project */
		$sqlstring = "select a.observation_name, observation_value, observation_startdate, b.enrollment_id, c.subject_id, c.uid, c.sex, c.gender from observations a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = ? and c.isactive = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $id, $isactive);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $isactive]);
		mysqli_stmt_close($stmt);
		$numObservations = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectRowID = $row['subject_id'];
			$enrollmentRowID = $row['enrollment_id'];
			$uid = $row['uid'];
			$sex = $row['sex'];
			$gender = $row['gender'];
			$obsv_name = $row['observation_name'];
			$obsv_value = $row['observation_value'];
			$obsv_date = $row['observation_startdate'];

			/* skip 'reserved' words */
			if ( ($obsv_name == "uid") || ($obsv_name == "subjectRowID") || ($obsv_name == "enrollmentRowID")) { continue; }
			
			$table[$subjectRowID]['uid'] = $uid;
			$table[$subjectRowID]['enrollmentRowID'] = $enrollmentRowID;
			$table[$subjectRowID]['observations'][$obsv_name] = ($table[$subjectRowID]['observations'][$obsv_name] ?? 0) + 1;
			$observationNames[] = $obsv_name;
		}
		
		$observationNames = array_unique($observationNames);
		natcasesort($observationNames);
		
		$stats = array();
		
		foreach ($table as $subjectRowID => $data) {
			$uid = $data['uid'];
			$enrollmentRowID = $data['enrollmentRowID'];
			$row = "{ subjectRowID: $subjectRowID, enrollmentRowID: $enrollmentRowID, uid: " . JSONForScript((string)$uid) . " ";
			foreach ($observationNames as $name) {
				$obsvName = $name;
				$obsvCount = $data['observations'][$name] ?? 0;
				if ($obsvCount < 1) {
					$obsvCount = 0;
				}
				else {
					$stats[$obsvName] = ($stats[$obsvName] ?? 0) + 1;
				}

				$row .= ", " . JSONForScript((string)$obsvName) . ": $obsvCount";
			}
			$row .= "}\n";
			$rowdata[] = $row;
		}
		
		//PrintVariable($stats);
		
		$data = "";
		if (count($rowdata) > 0)
			$data = implode(",", $rowdata);
		
		$numObservationNames = count($observationNames);
		$numSubjects = count($rowdata);
		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<br>
		<div class="ui text container">
			<span id="updateresult"></span>
		</div>
	  
		<div class="ui top attached yellow segment">
			<div class="ui grid">
				<div class="eight wide column">
					<h2 class="ui header">
						Observation checklist
						<div class="sub header">Displaying <?=number_format($numObservations)?> observation values</div>
					</h2>
						<p>
						Observation names: <?=$numObservationNames?><br>
						Subjects: <?=$numSubjects?>
						</p>
				</div>
				<div class="right aligned seven wide column">
					<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
				</div>
			</div>
		</div>
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<script type="text/javascript">
			let gridApi;
			
			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridApi.exportDataAsCsv( {allColumns: false} );
			}			

			// Grid Options are properties passed to the grid
			const gridOptions = {
				theme: agGrid.themeBalham,

				// each entry here represents one column
				columnDefs: [
					{ field: 'subjectRowID', hide: true },
					{ field: 'enrollmentRowID', hide: true },
					{
						headerName: "UID",
						field: "uid",
						pinned: 'left',
						width: 150,
						cellDataType: 'text',
						cellRenderer: function(params) {
							return '<a href="subjects.php?id=' + encodeURIComponent(params.data.subjectRowID) + '">' + String(params.value).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }) + '</a>'
						},
						headerTooltip: "Click to go to the subject",
					},
					<? foreach ($observationNames as $name) {
						$percent = ($numSubjects > 0) ? number_format((($stats[$name] ?? 0)/$numSubjects) * 100.0, 0) : 0;
						?>
					{
						field: <?=JSONForScript((string)$name)?>,
						headerName: <?=JSONForScript("$name ($percent%)")?>,
						valueParser: numberParser,

						cellStyle: function(params) {
							if ((params.value == '') || (params.value == null) || (params.value < 1)) {
								return { backgroundColor: `rgb(255, 255, 255)` };
							}
							else if (params.value == 1) {
								return { backgroundColor: `rgb(200, 255, 200)` };
							}
							else if (params.value > 1) {
								return { backgroundColor: `rgb(128, 255, 128)` };
							}
						}
					},
					<? } ?>
				],

				rowData: [ <?=$data?> ],
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},

				rowSelection: { mode: 'multiRow' }, // allow rows to be selected
				animateRows: false, // have rows animate to new positions when sorted
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				// new grid instance, passing in the hosting DIV and Grid Options
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}
			
			function numberParser(params) {
				const newValue = params.newValue;
				let valueAsNumber;
				if (newValue === null || newValue === undefined || newValue === "") {
					valueAsNumber = null;
				} else {
					valueAsNumber = parseFloat(params.newValue);
				}
				return valueAsNumber;
			}			

		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- EditExperimentMapping -------------- */
	/* -------------------------------------------- */
	/* map the protocol names in this project's series to the project's experiments */
	function EditExperimentMapping($projectid) {
		$project = CheckProjectPerm($projectid, array('modifydata'));
		if ($project === null) return;
		$projectid = (int)$projectid;

		/* the distinct series descriptions (or protocol, if blank) for each modality in this project */
		$seriesdescs = array();
		$sqlstring = "select distinct a.study_modality from studies a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$modalitylist = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modalitylist[] = strtolower($row['study_modality'] ?? '');
		}
		mysqli_stmt_close($stmt);
		foreach (array_unique($modalitylist) as $modality) {
			/* the table name is built from the modality, so it must be a valid name with a series table */
			$table = GetSeriesTableName($modality);
			if (($table == "") || !TableExists($table)) continue;
			$sqlstringA = "select a.series_desc, a.series_protocol from `$table` a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = ?";
			$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
			mysqli_stmt_bind_param($stmtA, 'i', $projectid);
			$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$projectid]);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$seriesdesc = (($rowA['series_desc'] ?? '') != "") ? $rowA['series_desc'] : ($rowA['series_protocol'] ?? '');
				if ($seriesdesc != "") {
					$seriesdescs[$modality][$seriesdesc] = ($seriesdescs[$modality][$seriesdesc] ?? 0) + 1;
				}
			}
			mysqli_stmt_close($stmtA);
		}

		/* this project's current experiment mappings */
		$mapping = array();
		$sqlstring = "select modality, protocolname, experiment_id from experiment_mapping where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mapping[strtolower($row['modality'])][$row['protocolname']] = $row['experiment_id'];
		}
		mysqli_stmt_close($stmt);

		/* this project's experiments */
		$experiments = array();
		$sqlstring = "select experiment_id, exp_name from experiments where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$experiments[(int)$row['experiment_id']] = $row['exp_name'];
		}
		mysqli_stmt_close($stmt);
		?>
		<br><br>
		<div class="ui text container grid">
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="updateexperimentmapping">
		<input type="hidden" name="id" value="<?=$projectid?>">
		<?=CSRFTokenField()?>
		<b>Experiment mapping</b>
		<br>
		This mapping associates <a href="experiment.php?projectid=<?=$projectid?>">experiments</a> with protocol names.
		<br><br>
		<table class="ui small celled selectable grey compact table">
			<thead>
				<th>Modality</th>
				<th>Protocol name</th>
				<th>Experiment</th>
			</thead>
		<?
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			uksort($serieslist, 'strnatcasecmp');
			foreach ($serieslist as $series => $count) {
				$experiment_id = $mapping[$modality][$series] ?? "";
				?>
				<tr>
					<td><?=htmlspecialchars(strtoupper($modality))?></td>
					<td><tt><?=htmlspecialchars($series)?></tt></td>
					<td>
						<input type="hidden" name="modalities[<?=$i?>]" value="<?=htmlspecialchars($modality)?>"><input type="hidden" name="protocolname[<?=$i?>]" value="<?=htmlspecialchars($series)?>">
						<select class="ui selection dropdown" name="experimentid[<?=$i?>]">
							<option value="">Select experiment...</option>
						<?
							foreach ($experiments as $expid => $name) {
								$selected = ($expid == $experiment_id) ? "selected" : "";
								echo "<option value='$expid' $selected>" . htmlspecialchars($name ?? '') . "</option>";
							}
						?>
						</select>
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
					<a class="ui button" href="projects.php?id=<?=$projectid?>">Cancel</a>
					<input class="ui primary button" type="submit" id="submit" value="Update">
				</div>
				</td>
			</tr>
		</table>
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateExperimentMapping ------------ */
	/* -------------------------------------------- */
	/* replace each listed protocol's mapping: the selected experiment (which must belong to this
	   project), or no mapping if none is selected */
	function UpdateExperimentMapping($projectid, $modalities, $protocolnames, $experimentids) {
		$project = CheckProjectPerm($projectid, array('modifydata'));
		if ($project === null) return;
		$projectid = (int)$projectid;
		if (!is_array($modalities)) { Error("Nothing to update"); return; }

		/* this project's experiments */
		$experiments = array();
		$sqlstring = "select experiment_id from experiments where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$experiments[(int)$row['experiment_id']] = 1;
		}
		mysqli_stmt_close($stmt);

		$n = 0;
		StartSQLTransaction();
		foreach ($modalities as $i => $modality) {
			$modality = strtolower(trim($modality ?? ''));
			$protocolname = $protocolnames[$i] ?? '';
			$experimentid = (int)($experimentids[$i] ?? 0);
			if (($modality == "") || ($protocolname == "")) continue;

			/* (experiment_mapping has no unique key, so replace rather than insert) */
			$sqlstring = "delete from experiment_mapping where project_id = ? and modality = ? and protocolname = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iss', $projectid, $modality, $protocolname);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $modality, $protocolname]);
			mysqli_stmt_close($stmt);

			if (isset($experiments[$experimentid])) {
				$sqlstring = "insert into experiment_mapping (project_id, protocolname, modality, experiment_id) values (?, ?, ?, ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'issi', $projectid, $protocolname, $modality, $experimentid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $protocolname, $modality, $experimentid]);
				mysqli_stmt_close($stmt);
				$n++;
			}
		}
		CommitSQLTransaction();
		Notice("Experiment mapping saved ($n protocols mapped)");
	}


	/* -------------------------------------------- */
	/* ------- TableExists ------------------------ */
	/* -------------------------------------------- */
	function TableExists($table) {
		static $cache = array();
		if (isset($cache[$table])) return $cache[$table];
		$sqlstring = "select table_name from information_schema.tables where table_schema = database() and table_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $table);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$table]);
		$cache[$table] = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		return $cache[$table];
	}


	/* -------------------------------------------- */
	/* ------- DisplayProject --------------------- */
	/* -------------------------------------------- */
	function DisplayProject($id) {
		/* the project page (name, counts, links) is visible to any user, like the project list. The
		   subject list at the bottom checks View Data / View PHI itself, as do the linked pages */
		$id = (int)$id;
		$project = GetProjectRow($id);
		if ($project === null) {
			Error("Project not found");
			return;
		}

		/* update the mostrecent table */
		UpdateMostRecent('', '', $id);
		
		$name = htmlspecialchars($project['project_name'] ?? '');
		$projectdesc = htmlspecialchars($project['project_desc'] ?? '');

		/* the user's favorite flag */
		$userid = (int)$_SESSION['userid'];
		$sqlstring = "select favorite from user_project where user_id = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $userid, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid, $id]);
		$rowA = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$favorite = $rowA['favorite'] ?? 0;

		/* counts shown on the page */
		$counts = array(
			'numstudies' => "select count(*) 'n' from studies a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ?",
			'numsubjects' => "select count(*) 'n' from subjects a join enrollment b on a.subject_id = b.subject_id where b.project_id = ?",
			'numinstruments' => "select count(*) 'n' from instruments where project_id = ?",
			'numexperiments' => "select count(*) 'n' from experiments where project_id = ?",
		);
		foreach ($counts as $var => $sqlstring) {
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$$var = (int)($row['n'] ?? 0);
		}
		
		?>
		
		<div class="ui container">
			<div class="ui grid">
				<div class="sixteen wide column">
					<h1 class="ui header">
						<?=$name?>
						<? FavoriteToggle($id, $favorite, "project"); ?>
						<div class="sub header"><?=$numsubjects?> subjects &nbsp; &nbsp; <?=$numstudies?> studies</div>
					</h1>
					<p><?=$projectdesc?></p>
				</div>
			</div>

			<!-- quick-access tiles, grouped by category (max 4 per row, then wrap) -->

			<h3 class="ui dividing header">View</h3>
			<div class="ui four stackable cards" style="margin-bottom: 1.5em">
				<a class="ui green card" href="projects.php?action=editsubjects&id=<?=$id?>">
					<div class="content">
						<div class="ui grey circular label" style="float: right"><?=$numsubjects?></div>
						<div class="header"><i class="user friends icon"></i> Subjects</div>
						<div class="description">Manage subjects</div>
					</div>
				</a>
				<a class="ui green card" href="projects.php?action=displaystudies&id=<?=$id?>">
					<div class="content">
						<div class="ui grey circular label" style="float: right"><?=$numstudies?></div>
						<div class="header"><i class="project diagram icon"></i> Imaging studies</div>
						<div class="description">Manage MR, EEG, and other imaging studies</div>
					</div>
				</a>
				<a class="ui green card" href="projects.php?action=displaynonimaging&id=<?=$id?>">
					<div class="content">
						<div class="header"><i class="table icon"></i> Non-imaging data</div>
						<div class="description">Manage observations/interventions</div>
					</div>
				</a>
				<div class="ui green card">
					<div class="content">
						<div class="header"><i class="clipboard check icon"></i> QC</div>
						<div class="description">Quality control</div>
					</div>
					<div class="extra content">
						<a href="checklist.php?projectid=<?=$id?>" style="color: #4183c4">Checklists</a><br>
						<a href="projectchecklist.php?projectid=<?=$id?>" style="color: #4183c4">Imaging checklist</a><br>
						<a href="mrqcchecklist.php?action=viewqcparams&id=<?=$id?>" style="color: #4183c4">MR scan QC</a><br>
						<a href="mriqc.php?action=viewmriqc&projectid=<?=$id?>" style="color: #4183c4">Advanced QC</a>
					</div>
				</div>
			</div>

			<h3 class="ui dividing header">Import</h3>
			<div class="ui four stackable cards" style="margin-bottom: 1.5em">
				<div class="ui green card">
					<div class="content">
						<div class="header"><i class="cloud download alternate icon"></i> Remote import</div>
						<div class="description">Manage remote imports</div>
					</div>
					<div class="extra content">
						<a href="remoteimportmapping.php?projectid=<?=$id?>" style="color: #4183c4">Remote Import Mapping</a><br>
						<a href="importremote.php?projectid=<?=$id?>" style="color: #4183c4">Remote Imports</a><br>
						<a href="importremote.php?action=viewbatchimportlist&projectid=<?=$id?>" style="color: #4183c4">Batch Imports</a>
					</div>
				</div>
				<a class="ui green card" href="importimaging.php?action=newimportform&projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="file import icon"></i> Import imaging</div>
						<div class="description">Import imaging data</div>
					</div>
				</a>
				<a class="ui green card" href="importnonimaging.php?action=newimportform&projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="file import icon"></i> Import non-imaging</div>
						<div class="description">Import non-imaging data</div>
					</div>
				</a>
			</div>

			<h3 class="ui dividing header">Export</h3>
			<div class="ui four stackable cards" style="margin-bottom: 1.5em">
				<div class="ui green card">
					<div class="content">
						<div class="header"><i class="upload icon"></i> NDA submission</div>
						<div class="description">Manage NDA submissions</div>
					</div>
					<div class="extra content">
						<a href="nda.php?projectid=<?=$id?>" style="color: #4183c4">NDA submissions</a><br>
						<a href="nda.php?action=editndamapping&projectid=<?=$id?>" style="color: #4183c4">Edit experiment mapping</a>
					</div>
				</div>
				<a class="ui green card" href="analysisbuilder.php?action=viewanalysissummary&projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="list alternate outline icon"></i> Analysis builder</div>
						<div class="description">Build and export analyses</div>
					</div>
				</a>
				<a class="ui green card" href="nonimaging.php?projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="table icon"></i> Non-imaging data</div>
						<div class="description">Search and export observations/interventions</div>
					</div>
				</a>
			</div>

			<h3 class="ui dividing header">Manage</h3>
			<div class="ui four stackable cards" style="margin-bottom: 1.5em">
				<a class="ui green card" href="instruments.php?projectid=<?=$id?>">
					<div class="content">
						<div class="ui grey circular label" style="float: right"><?=$numinstruments?></div>
						<div class="header"><i class="flask icon"></i> Instruments</div>
						<div class="description">Manage instruments and expected observations</div>
					</div>
				</a>
				<a class="ui green card" href="datadictionary.php?projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="database icon"></i> Data dictionary</div>
						<div class="description">Manage the data dictionary</div>
					</div>
				</a>
				<a class="ui green card" href="templates.php?action=displaystudytemplatelist&projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="clone outline icon"></i> Study templates</div>
						<div class="description">Manage study templates</div>
					</div>
				</a>
				<a class="ui green card" href="experiment.php?projectid=<?=$id?>">
					<div class="content">
						<div class="ui grey circular label" style="float: right"><?=$numexperiments?></div>
						<div class="header"><i class="clipboard icon"></i> Experiments</div>
						<div class="description">Manage experiments</div>
					</div>
				</a>
				<div class="ui green card">
					<div class="content">
						<div class="header"><i class="tasks icon"></i> Mappings</div>
						<div class="description">Manage data mappings</div>
					</div>
					<div class="extra content">
						<a href="projects.php?action=editexperimentmapping&id=<?=$id?>" style="color: #4183c4">Experiment/protocol mapping</a><br>
						<a href="remoteimportmapping.php?projectid=<?=$id?>" style="color: #4183c4">Remote import mapping</a>
					</div>
				</div>
				<a class="ui green card" href="projectusage.php?projectid=<?=$id?>">
					<div class="content">
						<div class="header"><i class="chart pie icon"></i> Resource usage</div>
						<div class="description">Imaging disk usage and non-imaging data counts</div>
					</div>
				</a>
			</div>

			<?
			?>

			<script type="text/javascript">
				$(window).on('load', function() {
					$('#pageloading').hide();
				});
			</script>
			<div class="ui text container" id="pageloading">
				<div class="ui yellow message" align="center">
					<i class="spinner loading icon"></i> Loading subject list...
				</div>
			</div>

			<?
				@ob_flush();
				@flush();

				DisplayCompactSubjectsTable($id);
			?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjects -------------------- */
	/* -------------------------------------------- */
	function DisplayProjects($viewtype) {
		
		if ($_SESSION['instanceid'] == "") {
			Error("InstanceID is blank. Page may not display properly. Try selecting an NiDB instance from the top left corner of the page.");
		}
		
		?>
		
		<div class="ui container">
			<a href="projects.php?viewtype=groupbypi" class="ui labeled icon <? if ($viewtype == "groupbypi") echo "yellow"; ?> button"><i class="address card icon"></i>Group by PI</a>
			<a href="projects.php?viewtype=groupbyadmin" class="ui labeled icon <? if ($viewtype == "groupbyadmin") echo "yellow"; ?> button"><i class="users icon"></i>Group by Admin</a>
			<a href="projects.php" class="ui labeled icon <? if ($viewtype == "") echo "yellow"; ?> button"><i class="list icon"></i>View list</a>
			<br><br>
			
			<?
				if ($viewtype == "groupbypi") {
					DisplayProjectsGrouped('pi');
				}
				elseif ($viewtype == "groupbyadmin") {
					DisplayProjectsGrouped('admin');
				}
				else {
					DisplayProjectList();
				}
			?>
		</div>
		<?
		
	}


	/* -------------------------------------------- */
	/* ------- GetProjectListData ----------------- */
	/* -------------------------------------------- */
	/* the active projects in the current instance, with the user's access, favorite flag and study
	   counts (counts only for projects the user can view data in). Values are HTML-escaped for display.
	   $orderby is a literal from this file */
	function GetProjectListData($orderby = "a.project_name") {
		$instanceid = (int)$_SESSION['instanceid'];
		$userid = (int)$_SESSION['userid'];

		$sqlstring = "select a.project_id, a.project_name, a.project_uid, a.project_costcenter, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname', d.favorite from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id left join user_project d on d.project_id = a.project_id and d.user_id = ? where a.project_status = 'active' and a.instance_id = ? order by $orderby";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $userid, $instanceid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid, $instanceid]);
		$rows = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = $row;
		}
		mysqli_stmt_close($stmt);

		/* the user's permissions on these projects (admins have all) */
		$perms = GetCurrentUserProjectPermissions(array_map(function($r) { return (int)$r['project_id']; }, $rows));

		$h = function($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); };
		$projects = array();
		foreach ($rows as $row) {
			$id = (int)$row['project_id'];
			$viewdata = (bool)GetPerm($perms, 'viewdata', $id);
			$hasaccess = $viewdata || GetPerm($perms, 'viewphi', $id);
			$favorite = (int)($row['favorite'] ?? 0);

			/* study counts by modality */
			$totalstudies = 0;
			$studydetail = "";
			if ($viewdata) {
				$sqlstring = "select a.study_modality, count(*) 'count' from studies a join enrollment b on a.enrollment_id = b.enrollment_id join subjects c on b.subject_id = c.subject_id where b.project_id = ? and c.isactive = 1 group by a.study_modality";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $id);
				$result2 = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
				while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
					$modality = ($row2['study_modality'] == "") ? "(blank)" : $row2['study_modality'];
					$totalstudies += (int)$row2['count'];
					$studydetail .= "<li><b>" . $h($modality) . "</b> - " . (int)$row2['count'];
				}
				mysqli_stmt_close($stmt);
				$studydetail = "<ul>$studydetail</ul>";
			}

			/* count of active subjects (only needed for the favorites list) */
			$subjectcount = 0;
			if ($favorite && $hasaccess) {
				$sqlstring = "select count(distinct c.subject_id) 'numsubj' from enrollment b join subjects c on b.subject_id = c.subject_id where b.project_id = ? and c.isactive = 1";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $id);
				$resultF = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
				$rowF = mysqli_fetch_array($resultF, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
				$subjectcount = (int)($rowF['numsubj'] ?? 0);
			}

			/* searchable text: accessible rows expose all columns, no-access rows expose the name only (all that's shown) */
			if ($hasaccess)
				$search = strtolower(trim($row['project_name'] . " " . $row['project_uid'] . " " . $row['project_costcenter'] . " " . $row['adminfullname'] . " " . $row['pifullname']));
			else
				$search = strtolower(trim($row['project_name'] ?? ''));

			$projects[] = array(
				'id' => $id,
				'name' => $h($row['project_name']),
				'projectuid' => $h($row['project_uid']),
				'costcenter' => $h($row['project_costcenter']),
				'adminusername' => $h($row['adminusername']),
				'adminfullname' => $h($row['adminfullname']),
				'piusername' => $h($row['piusername']),
				'pifullname' => $h($row['pifullname']),
				'favorite' => $favorite,
				'hasaccess' => $hasaccess,
				'totalstudies' => $totalstudies,
				'studydetail' => $h($studydetail),
				'subjectcount' => $subjectcount,
				'search' => $h($search),
			);
		}
		return $projects;
	}


	/* -------------------------------------------- */
	/* ------- RenderProjectRow ------------------- */
	/* -------------------------------------------- */
	/* renders a single project's <tr> for the project list tables. $columns are the optional columns
	   to show ('uid', 'costcenter', 'admin', 'pi', 'studies') */
	function RenderProjectRow($p, $columns = array('uid', 'costcenter', 'admin', 'pi', 'studies'), $viewtype = "") {
		if ($p['hasaccess']) {
			?>
			<tr valign="top" data-search="<?=$p['search']?>">
				<td>
					<b><a href="projects.php?id=<?=$p['id']?>"><?=$p['name']?></a></b>
					<? FavoriteToggle($p['id'], $p['favorite'], "list", $viewtype); ?>
				</td>
				<? if (in_array('uid', $columns)) { ?><td><?=$p['projectuid']?></td><? } ?>
				<? if (in_array('costcenter', $columns)) { ?><td><?=$p['costcenter']?></td><? } ?>
				<? if (in_array('admin', $columns)) { ?><td><?=$p['adminfullname']?></td><? } ?>
				<? if (in_array('pi', $columns)) { ?><td><?=$p['pifullname']?></td><? } ?>
				<? if (in_array('studies', $columns)) { ?><td align="left" title="<?=$p['studydetail']?>"><?=$p['totalstudies']?></td><? } ?>
			</tr>
			<?
		}
		else {
			?>
			<tr data-search="<?=$p['search']?>">
				<td style="color: #999; padding-left: 20px" colspan="<?=count($columns) + 1?>">No access to <b><?=$p['name']?></b></td>
			</tr>
			<?
		}
	}

	/* -------------------------------------------- */
	/* ------- DisplayProjectList ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectList() {

		/* gather all active projects for this instance once, then render into two tabs */
		$projects = GetProjectListData();

		$countMine = 0;
		foreach ($projects as $p) { if ($p['hasaccess']) $countMine++; }
		$countAll = count($projects);
		?>

		<script>
			$(document).ready(function() {
				$('.menu .item').tab();
				$('.tabular.menu .item').tab();
			});

			function filterProjects() {
				var term = document.getElementById('projectSearch').value.toLowerCase().trim();
				var tabs = [
					{ tableId: 'table-mine', badgeId: 'badge-mine' },
					{ tableId: 'table-all',  badgeId: 'badge-all'  },
				];
				tabs.forEach(function(tab) {
					var table = document.getElementById(tab.tableId);
					var rows  = table.querySelectorAll('tbody tr');
					var count = 0;
					rows.forEach(function(row) {
						if (term === '') {
							row.style.display = '';
						} else {
							var s = (row.dataset.search || '');
							var match = s.indexOf(term) !== -1;
							row.style.display = match ? '' : 'none';
							if (match) count++;
						}
					});
					var badge = document.getElementById(tab.badgeId);
					if (term === '') {
						badge.style.display = 'none';
					} else {
						badge.className    = count > 0 ? 'ui red label' : 'ui grey label';
						badge.textContent  = count;
						badge.style.display = '';
					}
				});
			}
		</script>

		<?
			/* favorite projects list */
			$favorites = array();
			foreach ($projects as $p) { if ($p['favorite'] && $p['hasaccess']) $favorites[] = $p; }
			if (count($favorites) > 0) {
		?>
		<div class="ui segment" style="margin-bottom:16px">
			<h4 class="ui header" style="margin-bottom:10px"><i class="yellow star icon"></i>Favorite projects</h4>
			<table class="ui very basic compact collapsing table">
				<thead>
					<th>Project</th>
					<th class="right aligned">Subjects</th>
				</thead>
				<tbody>
					<? foreach ($favorites as $p) { ?>
					<tr>
						<td><a href="projects.php?id=<?=$p['id']?>"><?=$p['name']?></a></td>
						<td class="right aligned"><?=$p['subjectcount']?></td>
					</tr>
					<? } ?>
				</tbody>
			</table>
		</div>
		<? } ?>

		<div class="ui top attached tabular menu large">
			<a class="item active" data-tab="mine">My Projects &nbsp;<span class="ui label"><?=$countMine?></span> <span id="badge-mine" class="ui label" style="display:none"></span></a>
			<a class="item" data-tab="all">All Projects &nbsp;<span class="ui label"><?=$countAll?></span> <span id="badge-all" class="ui label" style="display:none"></span></a>
			<div class="right menu">
				<div class="item">
					<div class="ui icon input" style="width:360px">
						<input type="text" id="projectSearch" oninput="filterProjects()" placeholder="Search"/>
						<i class="search icon"></i>
					</div>
				</div>
			</div>
		</div>

		<div class="ui bottom attached tab segment active" data-tab="mine">
			<table id="table-mine" class="ui celled selectable grey table" data-sortable>
				<thead>
					<th data-sort="string-ins">Name</th>
					<th data-sort="string-ins">UID</th>
					<th data-sort="string-ins">Cost Center</th>
					<th data-sort="string-ins">Admin</th>
					<th data-sort="string-ins">PI</th>
					<th data-sort="int">Studies</th>
				</thead>
				<tbody>
					<?
						foreach ($projects as $p) {
							if ($p['hasaccess']) RenderProjectRow($p);
						}
						if ($countMine == 0) {
							?><tr><td colspan="6" style="color:#999; text-align:center; padding:20px">You do not have access to any projects in this instance.</td></tr><?
						}
					?>
				</tbody>
			</table>
		</div>

		<div class="ui bottom attached tab segment" data-tab="all">
			<table id="table-all" class="ui celled selectable grey table" data-sortable>
				<thead>
					<th data-sort="string-ins">Name</th>
					<th data-sort="string-ins">UID</th>
					<th data-sort="string-ins">Cost Center</th>
					<th data-sort="string-ins">Admin</th>
					<th data-sort="string-ins">PI</th>
					<th data-sort="int">Studies</th>
				</thead>
				<tbody>
					<?
						foreach ($projects as $p) {
							RenderProjectRow($p);
						}
					?>
				</tbody>
			</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectsGrouped ------------- */
	/* -------------------------------------------- */
	/* the project list grouped by PI ($by 'pi') or by admin ($by 'admin') */
	function DisplayProjectsGrouped($by) {
		$projects = GetProjectListData(($by == "pi") ? "a.project_pi, a.project_name" : "a.project_admin, a.project_name");
		$viewtype = ($by == "pi") ? "groupbypi" : "groupbyadmin";
		/* the other person is shown as a column */
		$columns = ($by == "pi") ? array('uid', 'costcenter', 'admin', 'studies') : array('uid', 'costcenter', 'pi', 'studies');

		$lastgroup = null;
		foreach ($projects as $p) {
			$groupuser = ($by == "pi") ? $p['piusername'] : $p['adminusername'];
			$groupname = ($by == "pi") ? $p['pifullname'] : $p['adminfullname'];
			if ($groupuser == "") $groupuser = "(blank)";

			/* start a new block for this person */
			if ($lastgroup !== $groupuser) {
				if ($lastgroup !== null) {
					?></table></div><?
				}
				?>
				<div class="ui styled segment">
					<h2 class="ui header">
						<i class="blue user icon"></i>
						<?=$groupname?>
						<div class="sub header"><?=$groupuser?></div>
					</h2>
					<table class="ui scrolling table">
						<thead>
							<th>Project</th>
							<th>UID</th>
							<th>Cost center</th>
							<th><?=($by == "pi") ? "Admin" : "PI"?></th>
							<th>Studies</th>
						</thead>
				<?
				$lastgroup = $groupuser;
			}
			RenderProjectRow($p, $columns, $viewtype);
		}
		if ($lastgroup !== null) {
			?></table></div><?
		}
	}

?>

<br><br><br><br>

<? include("footer.php") ?>
