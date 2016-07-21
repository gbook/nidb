<?
 // ------------------------------------------------------------------------------
 // NiDB cleanup.php
 // Copyright (C) 2004 - 2016
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
	require "includes.php";
	require "nidbapi.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$subjectids = GetVariable("subjectids");
	$enrollmentids = GetVariable("enrollmentids");
	
	/* determine action */
	switch ($action) {
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
			DisplayEmptyStudies();
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
		default:
			DisplayMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
		$urllist['Administration'] = "cleanup.php";
		$urllist['Cleanup'] = "cleanup.php";
		NavigationBar("Admin", $urllist);
		
		?>
		View empty <a href="cleanup.php?action=viewemptysubjects">subjects</a><br>
		View empty <a href="cleanup.php?action=viewemptyenrollments">enrollments</a><br>
		<!--View empty <a href="cleanup.php?action=viewemptystudies">studies</a><br>-->
		<!--View empty <a href="cleanup.php?action=viewemptyseries">series</a><br>-->
		<br><br>
		<?
	}
		
	
	/* -------------------------------------------- */
	/* ------- DisplayEmptySubjects --------------- */
	/* -------------------------------------------- */
	function DisplayEmptySubjects() {

		$sqlstring = "select * from subjects where subject_id not in (select subject_id from enrollment) and isactive = 1";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysql_num_rows($result)
		?>
		
		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deactivatesubjects">
		Found <?=$numrows?> empty subjects
		<table class="smallgraydisplaytable">
			<thead>
				<tr>
					<th>UID</th>
					<th>Name</th>
					<th>Birthdate</th>
					<th>Gender</th>
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
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$subject_id = $row['subject_id'];
			$name = $row['name'];
			$uid = $row['uid'];
			$birthdate = $row['birthdate'];
			$gender = $row['gender'];
			$lastupdate = $row['lastupdate'];
			?>
			<tr>
				<td><a href="subjects.php?id=<?=$subject_id?>"><?=$uid?></a></td>
				<td><?=$name?></td>
				<td><?=$birthdate?></td>
				<td><?=$gender?></td>
				<td><?=$lastupdate?></td>
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
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayEmptyEnrollments ------------ */
	/* -------------------------------------------- */
	function DisplayEmptyEnrollments() {

		$sqlstring = "select a.*, b.uid, b.subject_id, c.project_name from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id not in (select enrollment_id from studies) and a.enrollment_id not in (select enrollment_id from assessments) and a.enrollment_id not in (select enrollment_id from measures) and a.enrollment_id not in (select enrollment_id from prescriptions) and b.isactive = 1 order by a.lastupdate";
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		$numrows = mysql_num_rows($result)
		?>
		
		<form action="cleanup.php" method="post" name="theform">
		<input type="hidden" name="action" value="deleteenrollments">
		Found <?=$numrows?> empty enrollments
		<table class="smallgraydisplaytable">
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
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
				<td colspan="8" align="right"><input type="submit" value="Delete Enrollments"></td>
			</tr>
			</tbody>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DeactiveateSubjects ---------------- */
	/* -------------------------------------------- */
	function DeactivateSubjects($subjectids) {
		$subjectids = mysql_real_escape_array($subjectids);

		echo "<tt style='font-size:8pt'>";
		foreach ($subjectids as $id) {
			$sqlstring = "update subjects set isactive = 0 where subject_id = '$id'";
			$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
			echo "Deactivated $id: " . mysql_affected_rows() . " altered rows<br>";
		}
		echo "</tt>";
	}

	
	/* -------------------------------------------- */
	/* ------- ObliterateSubjects ----------------- */
	/* -------------------------------------------- */
	function ObliterateSubjects($subjectids) {
		$subjectids = mysql_real_escape_array($subjectids);
		
		/* get list of subjects from the studyids */
		$sqlstring = "select subject_id, uid from subjects where subject_id in (" . implode(',',$subjectids) . ")";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$ids[] = $row['subject_id'];
			$uids[] = $row['uid'];
		}
		
		/* delete all information about this SUBJECT from the database */
		foreach ($ids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'subject', $id,'" . $GLOBALS['username'] . "', now())";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Subjects [<?=implode(', ',$uids)?>] queued for obliteration</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DeleteEnrollments ------------------ */
	/* -------------------------------------------- */
	function DeleteEnrollments($enrollmentids) {

		echo "<tt style='font-size:8pt'>";
		foreach ($enrollmentids as $id) {
			$sqlstring = "delete from enrollment where enrollment_id = '$id'";
			//PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
			echo "Deleted $id: " . mysql_affected_rows() . " altered rows<br>";
		}
		echo "</tt>";
	}
	
?>

<? include("footer.php") ?>
