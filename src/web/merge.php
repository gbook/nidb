<?
 // ------------------------------------------------------------------------------
 // NiDB merge.php
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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Merge</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	/* the most subjects that can be merged in one request */
	define("MERGE_MAXSUBJECTS", 4);

	/* ----- setup variables ----- */
	$action = GetSafeAction(array("submitmerge", "submitmergestudies")); /* mutating actions require POST + CSRF token */
	$subjectids = MergeCleanIDs(GetVariable("subjectids"));
	$subjectuid = trim((string)GetVariable("subjectuid"));
	$selectedid = (int)GetVariable("selectedid");
	$selectedstudyid = (int)GetVariable("selectedstudyid");
	$studyids = MergeCleanIDs(GetVariable("studyids"));
	$studyid = (int)GetVariable("studyid");
	$mergemethod = GetVariable("mergemethod");
	$idtoremove = (int)GetVariable("idtoremove");
	$name = GetVariable("name");
	$dob = GetVariable("dob");
	$gender = GetVariable("gender");
	$ethnicity1 = GetVariable("ethnicity1");
	$ethnicity2 = GetVariable("ethnicity2");
	$altuids = GetVariable("altuids");
	$guid = GetVariable("guid");

	/* determine action. Mutating actions use POST/Redirect/GET so a refresh/Back can't re-submit them.
	   On success they redirect to the merged subject/study; on failure, back to the merge form */
	switch ($action) {
		case 'submitmerge':
			ob_start();
			$ok = SubmitMergeSubjects($subjectids, $selectedid, $name, $dob, $gender, $ethnicity1, $ethnicity2, $altuids, $guid);
			$_SESSION['flash'] = ob_get_clean();
			if ($ok)
				RedirectTo("subjects.php?id=$selectedid");
			else
				RedirectTo("merge.php?" . http_build_query(array("action" => "merge", "subjectids" => $subjectids)));
			break;
		case 'submitmergestudies':
			ob_start();
			$ok = SubmitMergeStudies($studyids, $selectedstudyid, $mergemethod);
			$_SESSION['flash'] = ob_get_clean();
			if ($ok)
				RedirectTo("studies.php?studyid=$selectedstudyid");
			else
				RedirectTo("merge.php?action=mergestudyform&studyid=$selectedstudyid");
			break;
		case 'mergestudyform':
			DisplayMergeStudies($studyid);
			break;
		case 'merge':
		case 'mergesubjectform':
		default:
			DisplayMergeSubjects($subjectids, $idtoremove, $subjectuid);
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- MergeCleanIDs ---------------------- */
	/* -------------------------------------------- */
	/* returns the unique, positive integer IDs from a request value, in their original order */
	function MergeCleanIDs($ids) {
		if (!is_array($ids)) return array();
		$clean = array();
		foreach ($ids as $id) {
			if (is_array($id)) continue;
			$id = (int)$id;
			if (($id > 0) && (!in_array($id, $clean))) $clean[] = $id;
		}
		return $clean;
	}


	/* -------------------------------------------- */
	/* ------- MergeSexOptions -------------------- */
	/* -------------------------------------------- */
	function MergeSexOptions() {
		return array("" => "(Select sex)", "U" => "Unknown", "F" => "Female", "M" => "Male", "O" => "Other");
	}


	/* -------------------------------------------- */
	/* ------- MergeEthnicityOptions -------------- */
	/* -------------------------------------------- */
	/* subjects.ethnicity1 (labeled "Ethnicity" in the UI) */
	function MergeEthnicityOptions() {
		return array("" => "(Select ethnicity)", "hispanic" => "Hispanic/Latino", "nothispanic" => "Not hispanic/Latino");
	}


	/* -------------------------------------------- */
	/* ------- MergeRaceOptions ------------------- */
	/* -------------------------------------------- */
	/* subjects.ethnicity2 (labeled "Race" in the UI). The column is a SET, so a value may be a comma separated list of these */
	function MergeRaceOptions() {
		return array("" => "(Select race)", "indian" => "American Indian/Alaska Native", "asian" => "Asian", "black" => "Black/African American", "islander" => "Hawaiian/Pacific Islander", "white" => "White", "mixed" => "Mixed", "other" => "Other", "unknown" => "Unknown");
	}


	/* -------------------------------------------- */
	/* ------- MergeLabel ------------------------- */
	/* -------------------------------------------- */
	/* display label for a coded value. A comma separated value (race) is labeled part by part */
	function MergeLabel($options, $value) {
		if ($value === "") return "";
		$labels = array();
		foreach (explode(",", $value) as $part)
			$labels[] = $options[$part] ?? $part;
		return implode(", ", $labels);
	}


	/* -------------------------------------------- */
	/* ------- MergeSeriesTables ------------------ */
	/* -------------------------------------------- */
	/* the <modality>_series tables that exist, keyed by table name */
	function MergeSeriesTables() {
		static $tables = null;
		if ($tables === null) {
			$tables = array();
			$sqlstring = "select table_name from information_schema.tables where table_schema = database() and table_name like '%\\_series'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_NUM))
				$tables[strtolower($row[0])] = true;
		}
		return $tables;
	}


	/* -------------------------------------------- */
	/* ------- GetMergeSubjects ------------------- */
	/* -------------------------------------------- */
	/* loads the subjects, in the order of $subjectids, with the current user's permissions for each.
	   canmerge = Edit PHI on the subject (its demographics are replaced or the subject is deactivated)
	   and Edit Data on every project it is enrolled in (its data is moved). Admins can merge any subject */
	function GetMergeSubjects($subjectids) {
		$subjects = array();
		if (count($subjectids) == 0) return $subjects;

		$placeholders = implode(',', array_fill(0, count($subjectids), '?'));
		$sqlstring = "select subject_id, uid, name, birthdate, gender, ethnicity1, ethnicity2, guid, isactive from subjects where subject_id in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($subjectids)), ...$subjectids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $subjectids);
		$rows = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$rows[(int)$row['subject_id']] = $row;
		mysqli_stmt_close($stmt);

		foreach ($subjectids as $id) {
			if (!isset($rows[$id])) continue;
			$row = $rows[$id];
			$sp = GetSubjectPermissions($id);

			$canmerge = ($sp['isadmin'] || ($sp['modifyphi'] && (count($sp['projectids']) > 0)));
			if (!$sp['isadmin']) {
				foreach ($sp['projectids'] as $projectid) {
					if (!GetPerm($sp['projects'], 'modifydata', $projectid)) $canmerge = false;
				}
			}

			$dob = (string)($row['birthdate'] ?? '');
			if ($dob == "0000-00-00") $dob = "";

			$subjects[] = array(
				'id' => $id,
				'uid' => $row['uid'],
				'name' => (string)($row['name'] ?? ''),
				'dob' => $dob,
				'gender' => (string)($row['gender'] ?? ''),
				'ethnicity1' => (string)($row['ethnicity1'] ?? ''),
				'ethnicity2' => (string)($row['ethnicity2'] ?? ''),
				'guid' => (string)($row['guid'] ?? ''),
				'altuids' => implode2(', ', GetAlternateUIDs($id, 0)),
				'isactive' => (bool)$row['isactive'],
				'sp' => $sp,
				'canmerge' => $canmerge
			);
		}

		return $subjects;
	}


	/* -------------------------------------------- */
	/* ------- PendingMergeExists ----------------- */
	/* -------------------------------------------- */
	/* true if a merge of this data type is already queued or running for the target ID */
	function PendingMergeExists($datatype, $dataid) {
		$sqlstring = "select fileiorequest_id from fileio_requests where fileio_operation = 'merge' and data_type = ? and data_id = ? and request_status in ('pending', 'processing')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $datatype, $dataid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$datatype, $dataid]);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		return $exists;
	}


	/* -------------------------------------------- */
	/* ------- SubmitMergeSubjects ---------------- */
	/* -------------------------------------------- */
	/* queues a merge of $subjectids into $selectedid. Returns true if the request was queued */
	function SubmitMergeSubjects($subjectids, $selectedid, $name, $dob, $gender, $ethnicity1, $ethnicity2, $altuids, $guid) {

		if (($selectedid < 1) || (!in_array($selectedid, $subjectids))) {
			Error("Select the subject to merge into");
			return false;
		}
		if (count($subjectids) < 2) {
			Error("At least two subjects are needed for a merge");
			return false;
		}
		if (count($subjectids) > MERGE_MAXSUBJECTS) {
			Error("Only " . MERGE_MAXSUBJECTS . " subjects can be merged at a time");
			return false;
		}

		/* every subject must exist, be active, and be mergeable by this user */
		$subjects = GetMergeSubjects($subjectids);
		if (count($subjects) != count($subjectids)) {
			Error("One or more of the subjects no longer exist. Nothing was queued");
			return false;
		}
		$finaluid = "";
		$sourceuids = array();
		foreach ($subjects as $s) {
			if (!$s['isactive']) {
				Error("Subject " . htmlspecialchars($s['uid']) . " is inactive (deleted or already merged). Nothing was queued");
				return false;
			}
			if (!$s['canmerge']) {
				Error("You do not have permission to merge subject " . htmlspecialchars($s['uid']) . ". Merging requires Edit PHI and Edit Data on every project the subject is enrolled in. Nothing was queued");
				return false;
			}
			if ($s['id'] == $selectedid) $finaluid = $s['uid'];
			else $sourceuids[] = $s['uid'];
		}

		/* the demographics for the merged subject. These are single values; an array means a stale or forged form */
		foreach (array($name, $dob, $gender, $ethnicity1, $ethnicity2, $altuids, $guid) as $v) {
			if (is_array($v)) {
				Error("The merge form was out of date. Please reload the page and try again. Nothing was queued");
				return false;
			}
		}
		$name = trim((string)$name);
		$dob = trim((string)$dob);
		$gender = trim((string)$gender);
		$ethnicity1 = trim((string)$ethnicity1);
		$ethnicity2 = trim((string)$ethnicity2);
		$altuids = trim((string)$altuids);
		$guid = trim((string)$guid);

		if ($dob != "") {
			$d = DateTime::createFromFormat('!Y-m-d', $dob);
			if (($d === false) || ($d->format('Y-m-d') !== $dob)) {
				Error("Invalid date of birth [" . htmlspecialchars($dob) . "]. Use YYYY-MM-DD, or leave it blank. Nothing was queued");
				return false;
			}
		}
		/* sex is char(1): a standard code, or one of the subjects' existing values */
		if ((!array_key_exists($gender, MergeSexOptions())) && (!in_array($gender, array_column($subjects, 'gender'), true))) {
			Error("Invalid sex [" . htmlspecialchars($gender) . "]. Nothing was queued");
			return false;
		}
		if (!array_key_exists($ethnicity1, MergeEthnicityOptions())) {
			Error("Invalid ethnicity [" . htmlspecialchars($ethnicity1) . "]. Nothing was queued");
			return false;
		}
		if ($ethnicity2 != "") {
			foreach (explode(",", $ethnicity2) as $part) {
				if (($part == "") || (!array_key_exists($part, MergeRaceOptions()))) {
					Error("Invalid race [" . htmlspecialchars($ethnicity2) . "]. Nothing was queued");
					return false;
				}
			}
		}
		if (strlen($name) > 255) { Error("Name is longer than 255 characters. Nothing was queued"); return false; }
		if (strlen($guid) > 50) { Error("GUID is longer than 50 characters. Nothing was queued"); return false; }

		if (PendingMergeExists('subject', $selectedid)) {
			Error("A merge into " . htmlspecialchars($finaluid) . " is already queued or running. Wait for it to finish before queuing another");
			return false;
		}

		/* all subjects except the target are merged into the target */
		$mergeids = implode(",", array_values(array_diff($subjectids, array($selectedid))));
		$dobvalue = ($dob == "") ? null : $dob;
		$username = $GLOBALS['username'];

		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, merge_ids, merge_name, merge_dob, merge_sex, merge_ethnicity1, merge_ethnicity2, merge_guid, merge_altuids, request_status, request_message, username, requestdate) values ('merge', 'subject', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'Request submitted', ?, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isssssssss', $selectedid, $mergeids, $name, $dobvalue, $gender, $ethnicity1, $ethnicity2, $guid, $altuids, $username);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$selectedid, $mergeids, '(name)', '(dob)', $gender, '(ethnicity1)', '(ethnicity2)', $guid, $altuids, $username]);
		mysqli_stmt_close($stmt);

		$h = function($s) { return htmlspecialchars((string)$s); };
		Notice("Merge of <b>" . $h(implode(", ", $sourceuids)) . "</b> into <b>" . $h($finaluid) . "</b> queued. It runs in the background, usually within a few minutes; the merged subjects are then deactivated. If it fails, nothing is changed and the error is shown with the file I/O requests", "Merge queued");
		return true;
	}


	/* -------------------------------------------- */
	/* ------- GetMergeStudy ---------------------- */
	/* -------------------------------------------- */
	/* returns the study's row with its subject and project, or null */
	function GetMergeStudy($studyid) {
		$studyid = (int)$studyid;
		$sqlstring = "select a.study_id, a.study_num, a.study_modality, a.study_datetime, a.study_site, a.enrollment_id, b.project_id, b.subject_id, c.uid, d.project_name from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on b.project_id = d.project_id where a.study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $studyid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return $row ? $row : null;
	}


	/* -------------------------------------------- */
	/* ------- MergeMethods ----------------------- */
	/* -------------------------------------------- */
	function MergeMethods() {
		return array(
			"sortbyseriesdate" => "Renumber series 1...N, ordered by series date/time",
			"sortbyseriesnum" => "Keep the existing series numbers. A duplicate number becomes 10000 + the number",
			"concatbystudydateasc" => "Concatenate by study date, <b>ascending</b> <tt>2020-06-14, 2020-06-22 ... 2020-08-05</tt>, renumbering series 1...N",
			"concatbystudydatedesc" => "Concatenate by study date, <b>descending</b> <tt>2020-08-05 ... 2020-06-22, 2020-06-14</tt>, renumbering series 1...N",
			"concatbystudynumasc" => "Concatenate by study number, <b>ascending</b> <tt>1, 2 ... 8</tt>, renumbering series 1...N",
			"concatbystudynumdesc" => "Concatenate by study number, <b>descending</b> <tt>8 ... 2, 1</tt>, renumbering series 1...N"
		);
	}


	/* -------------------------------------------- */
	/* ------- SubmitMergeStudies ----------------- */
	/* -------------------------------------------- */
	/* queues a merge of $studyids into $selectedstudyid. Returns true if the request was queued */
	function SubmitMergeStudies($studyids, $selectedstudyid, $mergemethod) {

		$target = GetMergeStudy($selectedstudyid);
		if ($target === null) {
			Error("Select the study to merge into");
			return false;
		}
		$targetname = htmlspecialchars($target['uid'] . $target['study_num']);

		$perms = GetCurrentUserProjectPermissions(array($target['project_id']));
		if (!GetPerm($perms, 'modifydata', $target['project_id'])) {
			Error("You do not have permission to merge studies in this project. Merging studies requires Edit Data");
			return false;
		}

		if (!is_string($mergemethod) || !array_key_exists($mergemethod, MergeMethods())) {
			Error("Select a merge method");
			return false;
		}

		/* the studies to merge into the target. All must be in the target's enrollment, with the same modality */
		$mergeids = array();
		foreach ($studyids as $id) {
			if ($id == $selectedstudyid) continue;
			$s = GetMergeStudy($id);
			if (($s === null) || ($s['enrollment_id'] != $target['enrollment_id']) || (strcasecmp($s['study_modality'], $target['study_modality']) != 0)) {
				Error("Study [$id] is not a " . htmlspecialchars($target['study_modality']) . " study in the same enrollment as $targetname. Nothing was queued");
				return false;
			}
			$mergeids[] = $id;
		}
		if (count($mergeids) == 0) {
			Error("Select at least one study, other than the final study, to merge into $targetname");
			return false;
		}

		if (PendingMergeExists('study', $selectedstudyid)) {
			Error("A merge into $targetname is already queued or running. Wait for it to finish before queuing another");
			return false;
		}

		$mergeidlist = implode(",", $mergeids);
		$username = $GLOBALS['username'];
		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, merge_ids, merge_method, request_status, request_message, username, requestdate) values ('merge', 'study', ?, ?, ?, 'pending', 'Request submitted', ?, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isss', $selectedstudyid, $mergeidlist, $mergemethod, $username);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$selectedstudyid, $mergeidlist, $mergemethod, $username]);
		mysqli_stmt_close($stmt);

		Notice("Merge of " . count($mergeids) . " studies into <b>$targetname</b> queued. It runs in the background, usually within a few minutes; the merged studies are then deleted. If it fails, nothing is changed and the error is shown with the file I/O requests", "Merge queued");
		return true;
	}


	/* -------------------------------------------- */
	/* ------- DisplayMergeStudies ---------------- */
	/* -------------------------------------------- */
	function DisplayMergeStudies($studyid) {

		?>
		<div class="ui container">
			<h2 class="ui header">
				<i class="random icon"></i>
				<div class="content">
					Merge Studies
					<div class="sub header">Merge the series of several studies into one study</div>
				</div>
			</h2>
		<?
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		$target = GetMergeStudy($studyid);
		if ($target === null) {
			Error("Study not found");
			echo "</div>";
			return;
		}

		$projectid = (int)$target['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'viewdata', $projectid)) {
			Error("You do not have permission to view studies in this project");
			echo "</div>";
			return;
		}
		$canedit = GetPerm($perms, 'modifydata', $projectid);

		$modality = (string)$target['study_modality'];
		$table = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $modality)) . "_series";
		if (!isset(MergeSeriesTables()[$table])) {
			Error("Studies with modality [" . htmlspecialchars($modality) . "] have no series and can't be merged");
			echo "</div>";
			return;
		}

		/* the other studies of this modality in the same enrollment */
		$sqlstring = "select study_id, study_num, study_datetime, study_site, study_desc from studies where enrollment_id = ? and study_modality = ? order by study_datetime asc, study_num asc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $target['enrollment_id'], $modality);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$target['enrollment_id'], $modality]);
		$studies = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$studies[] = $row;
		mysqli_stmt_close($stmt);

		/* number of series in each study. The table name was validated above */
		$sqlstring = "select count(*) 'numseries' from `$table` where study_id = ?";
		foreach ($studies as $i => $s) {
			$sid = (int)$s['study_id'];
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $sid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$studies[$i]['numseries'] = (int)($row['numseries'] ?? 0);
		}

		$h = function($s) { return htmlspecialchars((string)$s); };
		?>
		<div class="ui segment">
			<p>Subject <a href="subjects.php?id=<?=(int)$target['subject_id']?>"><b><?=$h($target['uid'])?></b></a>, project <b><?=$h($target['project_name'])?></b>. Only <b><?=$h($modality)?></b> studies in this enrollment are shown.</p>
			<p>Check the studies to merge, and select the <b>final study</b> they will be merged into. The series of the other checked studies are moved into the final study, and those studies are then deleted.</p>
		</div>
		<?
		if (count($studies) < 2) {
			Notice("There are no other " . $h($modality) . " studies in this enrollment to merge with " . $h($target['uid'] . $target['study_num']));
			echo "</div>";
			return;
		}
		if (!$canedit) {
			Warning("You can view these studies, but merging them requires Edit Data on this project");
		}
		?>
		<form action="merge.php" method="post" class="ui form" id="mergestudyform">
			<input type="hidden" name="action" value="submitmergestudies">
			<?=CSRFTokenField()?>
			<table class="ui very compact celled collapsing grey table">
				<thead>
					<tr>
						<th class="center aligned">Merge</th>
						<th>Study</th>
						<th>Date</th>
						<th>Site</th>
						<th>Description</th>
						<th class="right aligned">Series</th>
						<th class="center aligned">Final study</th>
					</tr>
				</thead>
				<tbody>
				<?
				foreach ($studies as $s) {
					$sid = (int)$s['study_id'];
					$istarget = ($sid == (int)$target['study_id']);
					?>
					<tr <? if ($istarget) echo 'class="active"'; ?>>
						<td class="center aligned">
							<div class="ui fitted checkbox">
								<input type="checkbox" name="studyids[]" value="<?=$sid?>" id="mergestudy<?=$sid?>" checked>
								<label></label>
							</div>
						</td>
						<td><a href="studies.php?studyid=<?=$sid?>"><?=$h($target['uid'] . $s['study_num'])?></a></td>
						<td><?=$h($s['study_datetime'])?></td>
						<td><?=$h($s['study_site'])?></td>
						<td><?=$h($s['study_desc'])?></td>
						<td class="right aligned"><?=$s['numseries']?></td>
						<td class="center aligned">
							<div class="ui fitted radio checkbox">
								<input type="radio" name="selectedstudyid" value="<?=$sid?>" <? if ($istarget) echo "checked"; ?> onChange="document.getElementById('mergestudy<?=$sid?>').checked = true;">
								<label></label>
							</div>
						</td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>

			<div class="grouped fields">
				<label>Merge method</label>
				<?
				$first = true;
				foreach (MergeMethods() as $value => $label) {
					?>
					<div class="field">
						<div class="ui radio checkbox">
							<input type="radio" name="mergemethod" value="<?=$value?>" <? if ($first) echo "checked"; ?>>
							<label><?=$label?></label>
						</div>
					</div>
					<?
					$first = false;
				}
				?>
			</div>

			<button type="submit" class="ui primary button" <? if (!$canedit) echo "disabled"; ?> onClick="return confirm('Merge the checked studies into the final study? The other studies are deleted after their series are moved');"><i class="random icon"></i> Merge studies</button>
			<a href="studies.php?studyid=<?=(int)$target['study_id']?>" class="ui button">Cancel</a>
		</form>
		</div>
		<script>
			$(document).ready(function() { $('#mergestudyform .ui.checkbox').checkbox(); });
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- MergeSelect ------------------------ */
	/* -------------------------------------------- */
	/* a <select> for a coded field. Values in $extra (existing subject values outside the standard list) are added as options */
	function MergeSelect($fieldname, $options, $selected, $extra = array()) {
		foreach ($extra as $value) {
			if (($value !== "") && (!array_key_exists($value, $options))) $options[$value] = MergeLabel($options, $value);
		}
		$html = "<select name='$fieldname' id='merge_$fieldname'>";
		foreach ($options as $value => $label) {
			$sel = ((string)$value === (string)$selected) ? "selected" : "";
			$html .= "<option value='" . htmlspecialchars($value, ENT_QUOTES) . "' $sel>" . htmlspecialchars($label) . "</option>";
		}
		return $html . "</select>";
	}


	/* -------------------------------------------- */
	/* ------- DisplayMergeSubjects --------------- */
	/* -------------------------------------------- */
	function DisplayMergeSubjects($subjectids, $idtoremove, $subjectuid) {

		$h = function($s) { return htmlspecialchars((string)$s); };
		?>
		<div class="ui container">
			<h2 class="ui header">
				<i class="copy icon"></i>
				<div class="content">
					Merge Subjects
					<div class="sub header">Merge all data from several subjects into one subject</div>
				</div>
			</h2>
		<?
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */

		/* remove a subject from the list */
		if ($idtoremove > 0) {
			$subjectids = array_values(array_diff($subjectids, array($idtoremove)));
		}

		/* add a subject by UID */
		if ($subjectuid != "") {
			$sqlstring = "select subject_id, isactive from subjects where uid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $subjectuid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectuid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$row) {
				Error("Subject " . $h($subjectuid) . " not found");
			}
			elseif (!$row['isactive']) {
				Error("Subject " . $h($subjectuid) . " is inactive (deleted or already merged) and can't be merged");
			}
			elseif (in_array((int)$row['subject_id'], $subjectids)) {
				Notice("Subject " . $h($subjectuid) . " is already in the list");
			}
			elseif (count($subjectids) >= MERGE_MAXSUBJECTS) {
				Error("Only " . MERGE_MAXSUBJECTS . " subjects can be merged at a time");
			}
			else {
				$subjectids[] = (int)$row['subject_id'];
			}
		}

		$subjects = GetMergeSubjects(array_slice($subjectids, 0, MERGE_MAXSUBJECTS));
		$numsubjects = count($subjects);
		$subjectids = array_column($subjects, 'id');

		/* the current list, for the add/remove links */
		$listquery = function($ids) { return http_build_query(array("action" => "merge", "subjectids" => array_values($ids))); };
		?>
		<div class="ui segment">
			<p>Add the UIDs of the subjects to merge, then select the subject to <b>merge into</b> (the leftmost is selected by default). The studies, observations, and interventions of the other subjects are moved into the selected subject, and the other subjects are <b>deactivated</b>.</p>
			<p>The selected subject's demographics and alternate UIDs are replaced with the values in the <b>Merged subject</b> column. Click a value in any subject's column to copy it there. Values that differ between subjects are highlighted. Studies collected in the past 24 hours can't be merged yet.</p>

			<? if ($numsubjects < MERGE_MAXSUBJECTS) { ?>
			<form action="merge.php" method="get" class="ui form">
				<input type="hidden" name="action" value="merge">
				<? foreach ($subjectids as $i => $id) { ?>
				<input type="hidden" name="subjectids[<?=$i?>]" value="<?=$id?>">
				<? } ?>
				<div class="ui action input">
					<input type="text" name="subjectuid" placeholder="UID" autofocus>
					<button type="submit" class="ui primary button"><i class="user plus icon"></i> Add UID</button>
				</div>
			</form>
			<? } else { ?>
			<div class="ui small message">Only <?=MERGE_MAXSUBJECTS?> subjects can be merged at a time</div>
			<? } ?>
		</div>
		<?

		if ($numsubjects == 0) {
			echo "</div>";
			return;
		}

		/* every subject must be mergeable, and there must be at least two */
		$notmergeable = array();
		foreach ($subjects as $s) {
			if (!$s['canmerge']) $notmergeable[] = $h($s['uid']);
		}
		if (count($notmergeable) > 0) {
			Warning("You do not have permission to merge " . implode(", ", $notmergeable) . ". Merging requires Edit PHI and Edit Data on every project the subject is enrolled in");
		}
		$cansubmit = (($numsubjects >= 2) && (count($notmergeable) == 0));

		/* the fields shown for each subject. PHI fields are only shown with View PHI on the subject */
		$fields = array(
			'name' => array('label' => 'Name', 'phi' => true),
			'dob' => array('label' => 'Date of birth', 'phi' => true),
			'gender' => array('label' => 'Sex', 'phi' => false, 'options' => MergeSexOptions()),
			'ethnicity1' => array('label' => 'Ethnicity', 'phi' => true, 'options' => MergeEthnicityOptions()),
			'ethnicity2' => array('label' => 'Race', 'phi' => true, 'options' => MergeRaceOptions()),
			'guid' => array('label' => 'GUID', 'phi' => false),
			'altuids' => array('label' => 'Alternate UIDs', 'phi' => false)
		);
		$canview = function($s, $field) use ($fields) {
			if (!$s['sp']['hasaccess']) return false;
			return ($fields[$field]['phi'] ? (bool)$s['sp']['viewphi'] : true);
		};

		/* the values the radio buttons copy into the Merged subject column. Only values the user can view */
		$jsdata = array();
		foreach ($subjects as $s) {
			$values = array();
			foreach ($fields as $field => $f) {
				if ($canview($s, $field)) $values[$field] = $s[$field];
			}
			$jsdata[$s['id']] = $values;
		}

		/* the Merged subject column starts with the first subject's values */
		$first = $subjects[0];
		$extra = array();
		foreach (array('gender', 'ethnicity1', 'ethnicity2') as $field)
			$extra[$field] = array_column($subjects, $field);
		?>
		<form action="merge.php" method="post" class="ui form" id="mergeform">
			<input type="hidden" name="action" value="submitmerge">
			<?=CSRFTokenField()?>
			<? foreach ($subjectids as $i => $id) { ?>
			<input type="hidden" name="subjectids[<?=$i?>]" value="<?=$id?>">
			<? } ?>

			<table class="ui celled definition compact table">
				<thead>
					<tr>
						<th></th>
						<? foreach ($subjects as $i => $s) { ?>
						<th class="mergecol<?=$s['id']?> <? if ($i == 0) echo "selectedcol"; ?>">
							<div class="ui radio checkbox">
								<input type="radio" name="selectedid" value="<?=$s['id']?>" <? if ($i == 0) echo "checked"; ?> onChange="SelectMergeSubject(<?=$s['id']?>);">
								<label><a href="subjects.php?id=<?=$s['id']?>" style="font-size: large; font-weight: bold"><?=$h($s['uid'])?></a></label>
							</div>
							<a href="merge.php?<?=$h($listquery($subjectids) . "&idtoremove=" . $s['id'])?>" class="ui mini basic right floated icon button" title="Remove <?=$h($s['uid'])?> from the merge"><i class="trash alternate icon"></i></a>
						</th>
						<? } ?>
						<th class="positive"><i class="arrow alternate circle right icon"></i> Merged subject</th>
					</tr>
				</thead>
				<tbody>
				<?
				foreach ($fields as $field => $f) {
					$values = array();
					foreach ($subjects as $s) {
						if ($canview($s, $field)) $values[] = $s[$field];
					}
					$differs = (count(array_unique($values)) > 1);
					?>
					<tr>
						<td class="right aligned"><?=$f['label']?></td>
						<?
						foreach ($subjects as $i => $s) {
							$class = "mergecol" . $s['id'] . (($i == 0) ? " selectedcol" : "");
							if (!$canview($s, $field)) {
								?><td class="<?=$class?>"><?=NoViewPermission()?></td><?
								continue;
							}
							$display = isset($f['options']) ? MergeLabel($f['options'], $s[$field]) : $s[$field];
							?>
							<td class="<?=$class?> <? if ($differs) echo "warning"; ?>" style="cursor: pointer" title="Click to use this value" onClick="CopyMergeValue('<?=$field?>', <?=$h(json_encode($s[$field], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))?>);"><?=$h($display)?></td>
							<?
						}
						?>
						<td class="positive">
						<?
						switch ($field) {
							case 'gender': echo MergeSelect('gender', MergeSexOptions(), $first['gender'], $extra['gender']); break;
							case 'ethnicity1': echo MergeSelect('ethnicity1', MergeEthnicityOptions(), $canview($first, $field) ? $first['ethnicity1'] : "", $extra['ethnicity1']); break;
							case 'ethnicity2': echo MergeSelect('ethnicity2', MergeRaceOptions(), $canview($first, $field) ? $first['ethnicity2'] : "", $extra['ethnicity2']); break;
							case 'dob': ?><input type="date" name="dob" id="merge_dob" value="<?=$h($canview($first, $field) ? $first['dob'] : "")?>"><? break;
							case 'altuids': ?><input type="text" name="altuids" id="merge_altuids" value="<?=$h($first['altuids'])?>" title="Comma separated. Put an asterisk before the primary ID, for example *PrimaryID1, otherID1"><? break;
							default: ?><input type="text" name="<?=$field?>" id="merge_<?=$field?>" value="<?=$h($canview($first, $field) ? $first[$field] : "")?>"><?
						}
						?>
						</td>
					</tr>
					<?
				}
				?>
					<tr>
						<td class="right aligned top aligned">Enrollments &amp; studies</td>
						<?
						foreach ($subjects as $i => $s) {
							?>
							<td class="top aligned mergecol<?=$s['id']?> <? if ($i == 0) echo "selectedcol"; ?>">
								<? DisplayMergeSubjectStudies($s); ?>
							</td>
							<?
						}
						?>
						<td class="positive top aligned">All enrollments and studies</td>
					</tr>
				</tbody>
			</table>

			<button type="submit" class="ui primary button" <? if (!$cansubmit) echo "disabled"; ?> onClick="return confirm('Merge these subjects into the selected subject? The other subjects will be deactivated');"><i class="copy icon"></i> Merge subjects</button>
			<? if ($numsubjects < 2) { ?><span class="ui grey text">&nbsp; Add at least one more UID to merge</span><? } ?>
		</form>
		</div>

		<style>
			#mergeform th.selectedcol, #mergeform td.selectedcol { background-color: #fffbe6 !important; }
			#mergeform th.selectedcol { border-top: 3px solid #fbbd08 !important; }
		</style>
		<script>
			var mergeData = <?=json_encode($jsdata, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)?>;

			/* copy one value into the Merged subject column */
			function CopyMergeValue(field, value) {
				var el = document.getElementById('merge_' + field);
				if (!el) return;
				el.value = value;
			}

			/* highlight the selected subject's column and copy its values into the Merged subject column */
			function SelectMergeSubject(id) {
				$('#mergeform .selectedcol').removeClass('selectedcol');
				$('#mergeform .mergecol' + id).addClass('selectedcol');
				var values = mergeData[id] || {};
				for (var field in values) {
					CopyMergeValue(field, values[field]);
				}
			}

			$(document).ready(function() {
				$('#mergeform .ui.checkbox').checkbox();
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMergeSubjectStudies --------- */
	/* -------------------------------------------- */
	/* a subject's enrollments, and the studies in each enrollment the user has View Data on */
	function DisplayMergeSubjectStudies($s) {
		$h = function($v) { return htmlspecialchars((string)$v); };

		if (!$s['sp']['hasaccess']) {
			echo NoViewPermission();
			return;
		}

		$subjectid = (int)$s['id'];
		$sqlstring = "select a.enrollment_id, a.project_id, a.enroll_subgroup, b.project_name, b.project_costcenter from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ? order by b.project_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $subjectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
		$enrollments = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$enrollments[] = $row;
		mysqli_stmt_close($stmt);

		if (count($enrollments) == 0) {
			echo "<span class='ui grey text'>Not enrolled in any project</span>";
			return;
		}

		foreach ($enrollments as $e) {
			$enrollmentid = (int)$e['enrollment_id'];
			?>
			<div style="margin-bottom: 8px">
				<b><?=$h($e['project_name'])?></b> <span class="ui grey text">(<?=$h($e['project_costcenter'])?>)</span>
				<? if (trim((string)$e['enroll_subgroup']) != "") { ?><div class="ui mini basic label" title="Enrollment group"><?=$h($e['enroll_subgroup'])?></div><? } ?>
				<?
				if (!GetPerm($s['sp']['projects'], 'viewdata', $e['project_id']) && !$s['sp']['isadmin']) {
					echo "<br>" . NoViewPermission();
					echo "</div>";
					continue;
				}

				$sqlstring = "select study_id, study_num, study_modality, study_datetime, study_site from studies where enrollment_id = ? order by study_datetime";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid]);
				if (mysqli_num_rows($result) == 0) {
					echo "<br><span class='ui grey text'>No studies</span>";
				}
				else {
					?>
					<table class="ui very compact small celled table" style="margin-top: 3px">
					<?
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						?>
						<tr>
							<td><a href="studies.php?studyid=<?=(int)$row['study_id']?>"><?=$h($row['study_num'])?></a></td>
							<td><?=$h($row['study_modality'])?></td>
							<td><?=$h($row['study_datetime'])?></td>
							<td><?=$h($row['study_site'])?></td>
						</tr>
						<?
					}
					?>
					</table>
					<?
				}
				mysqli_stmt_close($stmt);
				?>
			</div>
			<?
		}
	}
?>

<? include("footer.php") ?>
