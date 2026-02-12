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

	//PrintVariable($_GET);
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$modality = GetVariable("modality");
	$studyids = GetVariable("studyids");
	$subjectids = GetVariable("subjectids");
	$enrollmentids = GetVariable("enrollmentids");
	
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
		case 'viewemptyseries':
			DisplayMenu();
			DisplayEmptySeries();
			break;
		case 'deactivatesubjects':
			DisplayMenu();
			DeactivateSubjects($subjectids);
			DisplayEmptySubjects();
			break;
		case 'obliteratesubjects':
			DisplayMenu();
			ObliterateSubjects($subjectids);
			DisplayEmptySubjects();
			break;
		case 'deleteenrollments':
			DisplayMenu();
			DeleteEnrollments($enrollmentids);
			DisplayEmptyEnrollments();
			break;
		case 'deletestudies':
			DisplayMenu();
			DeleteStudies($studyids);
			//DisplayEmptyEnrollments();
			break;
		default:
			DisplayMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
		
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
			
			$sqlstringA = "select a.subject_id, a.uid, a.isactive, c.study_id, c.study_datetime, c.study_modality from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.enrollment_id = $enrollmentid and c.study_num = $studynum";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				
				$subjectid = $rowA['subject_id'];
				$isactive = $rowA['isactive'];
				$uid = $rowA['uid'];
				$studyid = $rowA['study_id'];
				$studydate = $rowA['study_datetime'];
				$modality = $rowA['study_modality'];

				$sqlstringB = "select count(*) 'count' from " . strtolower($modality) . "_series where study_id = $studyid";
				$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
				$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
				$numdbseries = $rowB['count'];
				
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
					<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a> <?=$deleted?></td>
					<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a> <span class="tiny">(<?=$studyid?>)</span></td>
					<td><?=$studydate?></td>
					<td><tt><?=$archivepath?></tt></td>
					<td><?=$modality?></td>
					<td><?=$numdbseries?></td>
					<td><?=$numdiskseries?></td>
				</tr>
				<?
			}
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
			$enrollmentid = $row['enrollment_id'];
			$studydatetime = $row['study_datetime'];
			$studymodality = $row['study_modality'];
			
			$sqlstringA = "select a.subject_id, a.uid, a.isactive, c.study_id, c.study_num, c.study_datetime, c.study_modality from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_datetime = '$studydatetime' and c.study_modality = '$studymodality'";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				
				$subjectid = $rowA['subject_id'];
				$isactive = $rowA['isactive'];
				$uid = $rowA['uid'];
				$studyid = $rowA['study_id'];
				$studynum = $rowA['study_num'];
				$studydate = $rowA['study_datetime'];
				$modality = $rowA['study_modality'];

				if ($modality != "") {
					$sqlstringB = "select count(*) 'count' from " . strtolower($modality) . "_series where study_id = $studyid";
					$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
					$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
					$numdbseries = $rowB['count'];
				}
				
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
					<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a> <?=$deleted?></td>
					<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a> <span class="tiny">(<?=$studyid?>)</span></td>
					<td><?=$studydate?></td>
					<td><tt><?=$archivepath?></tt></td>
					<td><?=$modality?></td>
					<td><?=$numdbseries?></td>
					<td><?=$numdiskseries?></td>
				</tr>
				<?
			}
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
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a></td>
				<td><?=$name?></td>
				<td><?=$birthdate?></td>
				<td><?=$gender?></td>
				<td><?=$lastupdate?></td>
				<td><?=$isactive?></td>
				<td class="allcheck"><input type='checkbox' name="subjectids[]" value="<?=$subject_id?>"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="6" align="right"><input type="submit" value="Deactivate" onClick="document.theform.action.value='obliteratesubjects'; document.theform.submit()"> &nbsp; <input type="submit" value="Obliterate" onClick="document.theform.action.value='obliteratesubjects'; document.theform.submit()"></td>
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

		$sqlstring = "select a.*, b.uid, b.subject_id, b.isactive, c.project_name from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id not in (select enrollment_id from studies) and a.enrollment_id not in (select enrollment_id from assessments) and a.enrollment_id not in (select enrollment_id from observations) and a.enrollment_id not in (select enrollment_id from interventions) order by a.lastupdate";
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
				<td><?=$enrollment_id?></td>
				<td><?=$project_name?></td>
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a> <?=$deleted?></td>
				<td><?=$enroll_subgroup?></td>
				<td><?=$enroll_startdate?></td>
				<td><?=$enroll_enddate?></td>
				<td><?=$lastupdate?></td>
				<td class="allcheck"><input type='checkbox' name="enrollmentids[]" value="<?=$enrollment_id?>"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="8" align="right"><input type="submit" value="Delete Enrollments" class="ui red button"></td>
			</tr>
			</tbody>
		</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayEmptyStudies ---------------- */
	/* -------------------------------------------- */
	function DisplayEmptyStudies($modality) {
		$modality = mysqli_real_escape_string($GLOBALS['linki'], strtolower($modality));

		$sqlstring = "select * from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on b.project_id = d.project_id where study_modality = '$modality' and study_id not in (select study_id from $modality" . "_series) order by a.lastupdate";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysqli_num_rows($result)
		?>
		
		<div class="ui container">
		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deletestudies">
		<input type="hidden" name="modality" value="<?=$modality?>">
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
				<td><?=$enrollment_id?></td>
				<td><?=$project_name?></td>
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a></td>
				<td><?=$enroll_subgroup?></td>
				<td><?=$enroll_startdate?></td>
				<td><?=$enroll_enddate?></td>
				<td><?=$lastupdate?></td>
				<td class="allcheck"><input type='checkbox' name="studyids[]" value="<?=$study_id?>"></td>
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
				<td><?=$enrollment_id?></td>
				<td><?=$project_name?></td>
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a></td>
				<td><?=$enroll_subgroup?></td>
				<td><?=$enroll_startdate?></td>
				<td><?=$enroll_enddate?></td>
				<td><?=$lastupdate?></td>
				<td class="allcheck"><input type='checkbox' name="enrollmentids[]" value="<?=$enrollment_id?>"></td>
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
	/* ------- DeactiveateSubjects ---------------- */
	/* -------------------------------------------- */
	function DeactivateSubjects($subjectids) {
		$subjectids = mysqli_real_escape_array($GLOBALS['linki'], $subjectids);

		echo "<tt style='font-size:8pt'>";
		foreach ($subjectids as $id) {
			$sqlstring = "update subjects set isactive = 0 where subject_id = '$id'";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			echo "Deactivated $id: " . mysqli_affected_rows($GLOBALS['linki']) . " altered rows<br>";
		}
		echo "</tt>";
	}

	
	/* -------------------------------------------- */
	/* ------- ObliterateSubjects ----------------- */
	/* -------------------------------------------- */
	function ObliterateSubjects($subjectids) {
		$subjectids = mysqli_real_escape_array($GLOBALS['linki'], $subjectids);
		
		/* get list of subjects from the studyids */
		$sqlstring = "select subject_id, uid from subjects where subject_id in (" . implode(',',$subjectids) . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ids[] = $row['subject_id'];
			$uids[] = $row['uid'];
		}
		
		/* delete all information about this SUBJECT from the database */
		foreach ($ids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'subject', $id,'" . $GLOBALS['username'] . "', now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		Notice("Subjects [" . implode(', ',$uids) . "] queued for obliteration");
	}


	/* -------------------------------------------- */
	/* ------- DeleteEnrollments ------------------ */
	/* -------------------------------------------- */
	function DeleteEnrollments($enrollmentids) {

		echo "<tt style='font-size:8pt'>";
		foreach ($enrollmentids as $id) {
			$sqlstring = "delete from enrollment where enrollment_id = '$id'";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			echo "Deleted $id: " . mysqli_affected_rows($GLOBALS['linki']) . " altered rows<br>";
		}
		echo "</tt>";
	}


	/* -------------------------------------------- */
	/* ------- DeleteStudies ---------------------- */
	/* -------------------------------------------- */
	function DeleteStudies($studyids) {

		echo "<tt style='font-size:8pt'>";
		foreach ($studyids as $id) {
			list($path, $uid, $studynum, $studyid, $subjectid, $modality) = GetDataPathFromStudyID($id);
			echo "Study path [$path]<br>";
			if (file_exists($path)) {
				echo "Study path [$path] exists<br>";
				
				$datetime = time();
				rename($path, "$path-$datetime");
				echo "Moving $path to $path-$datetime<br>";
				
				$sqlstring = "delete from studies where study_id = '$id'";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				echo "Deleted $id: " . mysqli_affected_rows($GLOBALS['linki']) . " altered rows<br>";
			}
			else {
				echo "Study path [$path] does NOT exist<br>";
				$sqlstring = "delete from studies where study_id = '$id'";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				echo "Deleted $id: " . mysqli_affected_rows($GLOBALS['linki']) . " altered rows<br>";
			}
		}
		echo "</tt>";
	}
	
?>

<? include("footer.php") ?>
