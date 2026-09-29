<?
 // ------------------------------------------------------------------------------
 // NiDB cleanup.php
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
		<title>NiDB - Cleanup</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	/* check if they have permissions to this view page */
	if (!isSiteAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}

	//PrintVariable($_GET);
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$modality = GetSeriesModality(GetVariable("modality"));
	$studyids = GetIDList(GetVariable("studyids"));
	$subjectids = GetIDList(GetVariable("subjectids"));
	$enrollmentids = GetIDList(GetVariable("enrollmentids"));

	/* mutating actions must come from the page's POST forms, not a GET link */
	$mutating = array('deactivatesubjects', 'obliteratesubjects', 'deleteenrollments', 'deletestudies');
	if (in_array($action, $mutating) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {
		$action = "";
	}

	/* determine action */
	switch ($action) {
		case 'viewallduplicatestudies':
			DisplayMenu();
			DisplayAllDuplicateStudies();
			break;
		case 'viewduplicatestudies':
			DisplayMenu();
			DisplayDuplicateStudies();
			break;
		case 'viewemptysubjects':
			DisplayMenu();
			DisplayEmptySubjects();
			break;
		case 'viewemptyenrollments':
			DisplayMenu();
			DisplayEmptyEnrollments();
			break;
		case 'viewemptystudies':
			DisplayMenu();
			DisplayEmptyStudies($modality);
			break;
		case 'vieworphanstudies':
			DisplayMenu();
			DisplayOrphanStudies();
			break;
		/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
		   then redirect to a GET so a refresh/Back doesn't re-submit the form */
		case 'deactivatesubjects':
			ob_start();
			DeactivateSubjects($subjectids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("cleanup.php?action=viewemptysubjects");
			break;
		case 'obliteratesubjects':
			ob_start();
			ObliterateSubjects($subjectids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("cleanup.php?action=viewemptysubjects");
			break;
		case 'deleteenrollments':
			ob_start();
			DeleteEnrollments($enrollmentids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("cleanup.php?action=viewemptyenrollments");
			break;
		case 'deletestudies':
			ob_start();
			DeleteStudies($studyids);
			$_SESSION['flash'] = ob_get_clean();
			if ($modality != "")
				RedirectTo("cleanup.php?action=viewemptystudies&modality=" . urlencode($modality));
			else
				RedirectTo("cleanup.php");
			break;
		default:
			DisplayMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- GetIDList -------------------------- */
	/* -------------------------------------------- */
	/* returns the positive integer IDs from a request array (non-arrays give an empty list) */
	function GetIDList($ids) {
		if (!is_array($ids))
			return array();

		return array_values(array_unique(array_filter(array_map('intval', $ids), function($id) { return $id > 0; })));
	}


	/* -------------------------------------------- */
	/* ------- GetSeriesModality ------------------ */
	/* -------------------------------------------- */
	/* returns the lowercase modality if a <modality>_series table exists, otherwise "". The result is
	   safe to use in a table name */
	function GetSeriesModality($modality) {
		static $cache = array();

		$tablename = GetSeriesTableName($modality ?? '');
		if ($tablename == '')
			return '';
		if (isset($cache[$tablename]))
			return $cache[$tablename];

		$sqlstring = "select table_name from information_schema.tables where table_schema = database() and table_name = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $tablename);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$tablename]);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);

		$cache[$tablename] = $exists ? strtolower($modality) : '';
		return $cache[$tablename];
	}


	/* -------------------------------------------- */
	/* ------- CountStudySeries ------------------- */
	/* -------------------------------------------- */
	/* number of series in the database for a study, or 0 if the modality has no series table */
	function CountStudySeries($modality, $studyid) {
		$modality = GetSeriesModality($modality);
		if ($modality == '')
			return 0;

		$studyid = (int)$studyid;
		$sqlstring = "select count(*) 'count' from $modality" . "_series where study_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $studyid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return $row['count'] ?? 0;
	}


	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {

		ShowFlashMessage();
		?>
		<div class="ui container">
			View empty <a href="cleanup.php?action=viewemptysubjects">subjects</a> <span class="tiny">Subjects without any enrollments</span><br>
			View empty <a href="cleanup.php?action=viewemptyenrollments">enrollments</a> <span class="tiny">Enrollments without any studies</span><br>
			View empty (<a href="cleanup.php?action=viewemptystudies&modality=mr">MR</a>, <a href="cleanup.php?action=viewemptystudies&modality=eeg">EEG</a>, <a href="cleanup.php?action=viewemptystudies&modality=et">ET</a>) studies <span class="tiny">Studies with no series</span><br>
			View orphan <a href="cleanup.php?action=vieworphanstudies">studies</a> <span class="tiny">Studies with invalid or missing enrollments</span><br>
			View duplicate <a href="cleanup.php?action=viewduplicatestudies">studies</a> <span class="tiny">Studies within the same subject and enrollment with duplicated <b>study numbers</b></span><br>
			View duplicate <a href="cleanup.php?action=viewallduplicatestudies">studies</a> <span class="tiny">Studies across the entire database</span>
		</div>
		<br><br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDuplicateStudies ------------ */
	/* -------------------------------------------- */
	function DisplayDuplicateStudies() {

		$sqlstring = "SELECT enrollment_id, study_num, count(*) as qty FROM studies GROUP BY enrollment_id, study_num HAVING qty > 1";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result)
		?>

		<div class="ui container">
		
		Found <?=$numrows?> duplicate study numbers
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>UID</th>
					<th>Study</th>
					<th>Date</th>
					<th>Path</th>
					<th>Modality</th>
					<th>Series in DB</th>
					<th>Series on disk</th>
				</tr>
			</thead>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = $row['enrollment_id'];
			$studynum = $row['study_num'];

			$sqlstringA = "select a.subject_id, a.uid, a.isactive, c.study_id, c.study_datetime, c.study_modality from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.enrollment_id = ? and c.study_num = ?";
			$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
			mysqli_stmt_bind_param($stmtA, 'ii', $enrollmentid, $studynum);
			$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$enrollmentid, $studynum]);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

				$subjectid = $rowA['subject_id'];
				$isactive = $rowA['isactive'];
				$uid = $rowA['uid'];
				$studyid = $rowA['study_id'];
				$studydate = $rowA['study_datetime'];
				$modality = $rowA['study_modality'];

				$numdbseries = CountStudySeries($modality, $studyid);

				$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";
				
				$numdiskseries = 0;
				$files = glob("$archivepath/*");
				if ($files){
					$numdiskseries = count($files);
				}
				
				if (!$isactive) { $deleted = "(deleted)"; }
				else { $deleted = ""; }
				?>
				<tr>
					<td><a href="subjects.php?id=<?=(int)$subjectid?>"><?=htmlspecialchars($uid ?? '')?></a> <?=$deleted?></td>
					<td><a href="studies.php?id=<?=(int)$studyid?>"><?=htmlspecialchars($uid ?? '')?><?=$studynum?></a> <span class="tiny">(<?=$studyid?>)</span></td>
					<td><?=$studydate?></td>
					<td><tt><?=htmlspecialchars($archivepath)?></tt></td>
					<td><?=htmlspecialchars($modality ?? '')?></td>
					<td><?=$numdbseries?></td>
					<td><?=$numdiskseries?></td>
				</tr>
				<?
			}
			mysqli_stmt_close($stmtA);
			?>
			<tr>
				<td colspan="7">&nbsp;</td>
			</tr>
			<?
		}
		?>
		</table>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayAllDuplicateStudies --------- */
	/* -------------------------------------------- */
	function DisplayAllDuplicateStudies() {

		$sqlstring = "SELECT study_datetime, study_modality, count(*) as qty FROM studies where year(study_datetime) <> 0 and study_modality <> '' and study_modality <> 'mrseries' GROUP BY study_datetime, study_modality HAVING qty > 1 order by study_datetime";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result)
		?>
		
		<div class="ui container">
		
		Found <?=$numrows?> duplicate study datetimes
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>UID</th>
					<th>Study</th>
					<th>Date</th>
					<th>Path</th>
					<th>Modality</th>
					<th>Series in DB</th>
					<th>Series on disk</th>
				</tr>
			</thead>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studydatetime = $row['study_datetime'];
			$studymodality = $row['study_modality'];

			$sqlstringA = "select a.subject_id, a.uid, a.isactive, c.study_id, c.study_num, c.study_datetime, c.study_modality from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_datetime = ? and c.study_modality = ?";
			$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
			mysqli_stmt_bind_param($stmtA, 'ss', $studydatetime, $studymodality);
			$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$studydatetime, $studymodality]);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {

				$subjectid = $rowA['subject_id'];
				$isactive = $rowA['isactive'];
				$uid = $rowA['uid'];
				$studyid = $rowA['study_id'];
				$studynum = $rowA['study_num'];
				$studydate = $rowA['study_datetime'];
				$modality = $rowA['study_modality'];

				$numdbseries = CountStudySeries($modality, $studyid);

				$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";
				
				$numdiskseries = 0;
				$files = glob("$archivepath/*");
				if ($files){
					$numdiskseries = count($files);
				}
				
				if (!$isactive) { $deleted = "(deleted)"; }
				else { $deleted = ""; }
				?>
				<tr>
					<td><a href="subjects.php?id=<?=(int)$subjectid?>"><?=htmlspecialchars($uid ?? '')?></a> <?=$deleted?></td>
					<td><a href="studies.php?id=<?=(int)$studyid?>"><?=htmlspecialchars($uid ?? '')?><?=$studynum?></a> <span class="tiny">(<?=$studyid?>)</span></td>
					<td><?=$studydate?></td>
					<td><tt><?=htmlspecialchars($archivepath)?></tt></td>
					<td><?=htmlspecialchars($modality ?? '')?></td>
					<td><?=$numdbseries?></td>
					<td><?=$numdiskseries?></td>
				</tr>
				<?
			}
			mysqli_stmt_close($stmtA);
			?>
			<tr>
				<td colspan="7">&nbsp;</td>
			</tr>
			<?
		}
		?>
		</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayEmptySubjects --------------- */
	/* -------------------------------------------- */
	function DisplayEmptySubjects() {

		$sqlstring = "select * from subjects where subject_id not in (select subject_id from enrollment) order by lastupdate asc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result)
		?>
		
		<div class="ui container">

		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deactivatesubjects">
		Found <?=$numrows?> empty subjects
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>UID</th>
					<th>Name</th>
					<th>Birthdate</th>
					<th>Gender</th>
					<th>Last update</th>
					<th>Deleted?</th>
					<th><input type="checkbox" id="checkall"></th>
				</tr>
			</thead>
			<tbody>
			<script type="text/javascript">
			$(document).ready(function() {
				$("#checkall").click(function() {
					var checked_status = this.checked;
					$(".allcheck").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
				});
			});
			</script>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subject_id = $row['subject_id'];
			$name = $row['name'];
			$uid = $row['uid'];
			$birthdate = $row['birthdate'];
			$gender = $row['gender'];
			$lastupdate = $row['lastupdate'];
			$isactive = $row['isactive'];
			
			if (!$isactive) { $isactive = "&#x2714;"; } else { $isactive = ""; }
			?>
			<tr>
				<td><a href="subjects.php?id=<?=(int)$subject_id?>"><?=htmlspecialchars($uid ?? '')?></a></td>
				<td><?=htmlspecialchars($name ?? '')?></td>
				<td><?=htmlspecialchars($birthdate ?? '')?></td>
				<td><?=htmlspecialchars($gender ?? '')?></td>
				<td><?=$lastupdate?></td>
				<td><?=$isactive?></td>
				<td class="allcheck"><input type='checkbox' name="subjectids[]" value="<?=(int)$subject_id?>"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="6" align="right"><input type="submit" value="Deactivate" onClick="document.theform.action.value='deactivatesubjects'; document.theform.submit()"> &nbsp; <input type="submit" value="Obliterate" onClick="document.theform.action.value='obliteratesubjects'; document.theform.submit()"></td>
			</tr>
			</tbody>
		</table>
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayEmptyEnrollments ------------ */
	/* -------------------------------------------- */
	function DisplayEmptyEnrollments() {

		//$sqlstring = "select a.*, b.uid, b.subject_id, b.isactive, c.project_name from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id not in (select enrollment_id from studies) and a.enrollment_id not in (select enrollment_id from assessments) and a.enrollment_id not in (select enrollment_id from observations) and a.enrollment_id not in (select enrollment_id from interventions) order by a.lastupdate";
		$sqlstring = "select a.*, b.uid, b.subject_id, b.isactive, c.project_name from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id not in (select enrollment_id from studies) and a.enrollment_id not in (select enrollment_id from observations) and a.enrollment_id not in (select enrollment_id from interventions) order by a.lastupdate";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result)
		?>
		<div class="ui container">
		
		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deleteenrollments">
		Found <?=$numrows?> empty enrollments
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>Enrollment ID</th>
					<th>Project</th>
					<th>Subject</th>
					<th>Enroll subgroup</th>
					<th>Enroll start date</th>
					<th>Enroll end date</th>
					<th>Last update</th>
					<th><input type="checkbox" id="checkall"></th>
				</tr>
			</thead>
			<tbody>
			<script type="text/javascript">
			$(document).ready(function() {
				$("#checkall").click(function() {
					var checked_status = this.checked;
					$(".allcheck").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
				});
			});
			</script>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollment_id = $row['enrollment_id'];
			$project_name = $row['project_name'];
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$enroll_subgroup = $row['enroll_subgroup'];
			$enroll_startdate = $row['enroll_startdate'];
			$enroll_enddate = $row['enroll_enddate'];
			$lastupdate = $row['lastupdate'];
			$isactive = $row['isactive'];
			if (!$isactive) { $deleted = "(deleted)"; } else { $deleted = ""; }
			?>
			<tr>
				<td><?=(int)$enrollment_id?></td>
				<td><?=htmlspecialchars($project_name ?? '')?></td>
				<td><a href="subjects.php?id=<?=(int)$subject_id?>"><?=htmlspecialchars($uid ?? '')?></a> <?=$deleted?></td>
				<td><?=htmlspecialchars($enroll_subgroup ?? '')?></td>
				<td><?=$enroll_startdate?></td>
				<td><?=$enroll_enddate?></td>
				<td><?=$lastupdate?></td>
				<td class="allcheck"><input type='checkbox' name="enrollmentids[]" value="<?=(int)$enrollment_id?>"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="8" align="right"><input type="submit" value="Delete Enrollments" class="ui red button"></td>
			</tr>
			</tbody>
		</table>
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayEmptyStudies ---------------- */
	/* -------------------------------------------- */
	function DisplayEmptyStudies($modality) {
		/* the modality is used in a table name, so it must have a <modality>_series table */
		$modality = GetSeriesModality($modality);
		if ($modality == "") {
			Warning("Invalid modality");
			return;
		}

		$sqlstring = "select * from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on b.project_id = d.project_id where study_modality = ? and study_id not in (select study_id from $modality" . "_series) order by a.lastupdate";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $modality);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$modality]);
		mysqli_stmt_close($stmt);
		$numrows = mysqli_num_rows($result)
		?>
		
		<div class="ui container">
		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deletestudies">
		<input type="hidden" name="modality" value="<?=htmlspecialchars($modality)?>">
		Found <?=$numrows?> empty studies
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>Enrollment ID</th>
					<th>Project</th>
					<th>Subject</th>
					<th>Enroll subgroup</th>
					<th>Enroll start date</th>
					<th>Enroll end date</th>
					<th>Last update</th>
					<th><input type="checkbox" id="checkall"></th>
				</tr>
			</thead>
			<tbody>
			<script type="text/javascript">
			$(document).ready(function() {
				$("#checkall").click(function() {
					var checked_status = this.checked;
					$(".allcheck").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
				});
			});
			</script>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$study_id = $row['study_id'];
			$enrollment_id = $row['enrollment_id'];
			$project_name = $row['project_name'];
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$enroll_subgroup = $row['enroll_subgroup'];
			$enroll_startdate = $row['enroll_startdate'];
			$enroll_enddate = $row['enroll_enddate'];
			$lastupdate = $row['lastupdate'];
			?>
			<tr>
				<td><?=(int)$enrollment_id?></td>
				<td><?=htmlspecialchars($project_name ?? '')?></td>
				<td><a href="subjects.php?id=<?=(int)$subject_id?>"><?=htmlspecialchars($uid ?? '')?></a></td>
				<td><?=htmlspecialchars($enroll_subgroup ?? '')?></td>
				<td><?=$enroll_startdate?></td>
				<td><?=$enroll_enddate?></td>
				<td><?=$lastupdate?></td>
				<td class="allcheck"><input type='checkbox' name="studyids[]" value="<?=(int)$study_id?>"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="8" align="right"><input type="submit" value="Delete Studies"></td>
			</tr>
			</tbody>
		</table>
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayOrphanStudies --------------- */
	/* -------------------------------------------- */
	function DisplayOrphanStudies() {

		$sqlstring = "select * from studies where enrollment_id not in (select enrollment_id from enrollment) order by lastupdate";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result)
		?>
		
		<div class="ui container">
		<!--
		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deletestudies">
		-->
		Found <?=$numrows?> orphaned studies
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>Enrollment ID</th>
					<th>Project</th>
					<th>Subject</th>
					<th>Enroll subgroup</th>
					<th>Enroll start date</th>
					<th>Enroll end date</th>
					<th>Last update</th>
					<th><input type="checkbox" id="checkall"></th>
				</tr>
			</thead>
			<tbody>
			<script type="text/javascript">
			$(document).ready(function() {
				$("#checkall").click(function() {
					var checked_status = this.checked;
					$(".allcheck").find("input[type='checkbox']").each(function() {
						this.checked = checked_status;
					});
				});
			});
			</script>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollment_id = $row['enrollment_id'];
			$project_name = $row['project_name'];
			$uid = $row['uid'];
			$subject_id = $row['subject_id'];
			$enroll_subgroup = $row['enroll_subgroup'];
			$enroll_startdate = $row['enroll_startdate'];
			$enroll_enddate = $row['enroll_enddate'];
			$lastupdate = $row['lastupdate'];
			?>
			<tr>
				<td><?=(int)$enrollment_id?></td>
				<td><?=htmlspecialchars($project_name ?? '')?></td>
				<td><a href="subjects.php?id=<?=(int)$subject_id?>"><?=htmlspecialchars($uid ?? '')?></a></td>
				<td><?=htmlspecialchars($enroll_subgroup ?? '')?></td>
				<td><?=$enroll_startdate?></td>
				<td><?=$enroll_enddate?></td>
				<td><?=$lastupdate?></td>
				<td class="allcheck"><input type='checkbox' name="enrollmentids[]" value="<?=(int)$enrollment_id?>"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="8" align="right"><input type="submit" disabled value="Delete Enrollments"></td>
			</tr>
			</tbody>
		</table>
		</form>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DeactivateSubjects ----------------- */
	/* -------------------------------------------- */
	function DeactivateSubjects($subjectids) {

		echo "<tt style='font-size:8pt'>";
		foreach ($subjectids as $id) {
			$id = (int)$id;
			$sqlstring = "update subjects set isactive = 0 where subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			echo "Deactivated $id: " . mysqli_stmt_affected_rows($stmt) . " altered rows<br>";
			mysqli_stmt_close($stmt);
		}
		echo "</tt>";
	}

	
	/* -------------------------------------------- */
	/* ------- ObliterateSubjects ----------------- */
	/* -------------------------------------------- */
	function ObliterateSubjects($subjectids) {
		if (count($subjectids) == 0) {
			Notice("No subjects selected");
			return;
		}

		/* get the list of subjects that exist */
		$ids = array();
		$uids = array();
		$placeholders = implode(',', array_fill(0, count($subjectids), '?'));
		$sqlstring = "select subject_id, uid from subjects where subject_id in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, str_repeat('i', count($subjectids)), ...$subjectids);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $subjectids);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ids[] = (int)$row['subject_id'];
			$uids[] = $row['uid'];
		}
		mysqli_stmt_close($stmt);
		
		/* delete all information about this SUBJECT from the database */
		foreach ($ids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'subject', ?, ?, now())";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'is', $id, $GLOBALS['username']);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $GLOBALS['username']]);
			mysqli_stmt_close($stmt);
		}
		Notice("Subjects [" . htmlspecialchars(implode(', ', $uids)) . "] queued for obliteration");
	}


	/* -------------------------------------------- */
	/* ------- DeleteEnrollments ------------------ */
	/* -------------------------------------------- */
	function DeleteEnrollments($enrollmentids) {

		echo "<tt style='font-size:8pt'>";
		foreach ($enrollmentids as $id) {
			$id = (int)$id;
			$sqlstring = "delete from enrollment where enrollment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			echo "Deleted $id: " . mysqli_stmt_affected_rows($stmt) . " altered rows<br>";
			mysqli_stmt_close($stmt);
		}
		echo "</tt>";
	}


	/* -------------------------------------------- */
	/* ------- DeleteStudies ---------------------- */
	/* -------------------------------------------- */
	function DeleteStudies($studyids) {

		echo "<tt style='font-size:8pt'>";
		foreach ($studyids as $id) {
			$id = (int)$id;

			$sqlstring = "select a.study_num, c.uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$row) {
				echo "Study $id does not exist<br>";
				continue;
			}

			/* only move the archive directory if the study has a full path. A blank UID or study
			   number would otherwise point at the archive root (or the subject's directory) */
			$uid = trim($row['uid'] ?? '');
			$studynum = trim($row['study_num'] ?? '');
			if (($uid != '') && ($studynum != '')) {
				$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";
				if (file_exists($path)) {
					echo "Study path [" . htmlspecialchars($path) . "] exists<br>";

					$datetime = time();
					rename($path, "$path-$datetime");
					echo "Moving " . htmlspecialchars($path) . " to " . htmlspecialchars("$path-$datetime") . "<br>";
				}
				else {
					echo "Study path [" . htmlspecialchars($path) . "] does NOT exist<br>";
				}
			}
			else {
				echo "Study $id has no archive path (blank UID or study number)<br>";
			}

			$sqlstring = "delete from studies where study_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			echo "Deleted $id: " . mysqli_stmt_affected_rows($stmt) . " altered rows<br>";
			mysqli_stmt_close($stmt);
		}
		echo "</tt>";
	}
	
?>

<? include("footer.php") ?>
