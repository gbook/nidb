<?
 // ------------------------------------------------------------------------------
 // NiDB importexperiment.php
 // Copyright (C) 2004 - 2025
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
		<title>NiDB - Import assessments</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$data = GetVariable("data");

	
	/* determine action */
	switch ($action) {
		case 'import': Import($data); break;
		default: DisplayImportForm();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- Import ----------------------------- */
	/* -------------------------------------------- */
	function Import($data) {
		$lines = explode("\n", $data);
		$lastfilegroupid = 0;
		$groupid = 0;
		foreach($lines as $line) {
			?>
			<div style="color:darkred"><?=$line?></div>
			<?
			list($subjectid, $costcenter, $formid, $questionid, $filegroupid, $value, $rater, $datetime, $notes, $visit) = explode(',', $line);
			if ($subjectid == "") {
				echo "subjectid blank<br>";
				continue;
			}
			/* check if the subject exists, and get their SubjectRowID */
			$subjectids = explode('|',$subjectid);
			//print_r($subjectids);
			if (count($subjectids) > 1) { $subjid = implode("','",$subjectids); }
			else { $subjid = "'$subjectids[0]'"; }
			$sqlstring = "select subject_id, uid from subjects where uid in ($subjid) or altuid1 in ($subjid) or altuid2 in ($subjid) or altuid3 in ($subjid)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) == 0) {
				echo "Subject $subjid not found in database<br>";
				continue;
			}
			if (mysqli_num_rows($result) == 1) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$SubjectRowID = $row['subject_id'];
				$uid = $row['uid'];
				echo "$subjid : $uid ($SubjectRowID)<br>";
			}
			else {
				echo "Multiple subjects found for $subjid: [";
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$SubjectRowID = $row['subject_id'];
					$uid = $row['uid'];
					echo ", $uid ($SubjectRowID)";
				}
				echo "]<br>";
				continue;
			}
			
			/* check if the subject is enrolled in the project, return the  enrollmentRowID */
			$sqlstring = "select enrollment_id from enrollment a left join projects b on a.project_id = b.project_id where b.project_costcenter = '$costcenter' and a.subject_id = $SubjectRowID";
			//echo "[$sqlstring]<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$enrollmentRowID = $row['enrollment_id'];
				echo "subject project id: $enrollmentRowID<br>";
			}
			else {
				/* create a enrollmentid for the generic project */
			}
			
			/* check if we need to create a new groupid */
			if ($filegroupid != $lastfilegroupid) {
				$sqlstring = "select (max(exp_groupid) + 1) 'newgroupid' from assessments";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$groupid = $row['newgroupid'];
			}
			
			/* check if the experiment exists, and if the question is already filled out, if it is filled out skip it and create a new experiment */
			$sqlstring = "select * from assessments where enrollment_id = $enrollmentRowID and form_id = $formid";
			echo "[$sqlstring]<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				/* get the ExperimentRowID */
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$ExperimentRowID = $row['experiment_id'];
			}
			else {
				/* create the experiment, get the rowID */
				$sqlstring = "insert into assessments (enrollment_id, form_id, exp_admindate, experimentor, rater_username, label, notes, iscomplete) values ($enrollmentRowID, $formid, '$datetime', '$rater', '$rater', '$visit', '$notes', 1)";
				echo "[$sqlstring]<br>";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$ExperimentRowID = mysqli_insert_id($GLOBALS['linki']);
			}
			
			/* check if the question has already been filled in */
			/* if the question has already been filled, create a new experiment and get the ExperimentRowID */
			$sqlstring = "select * from assessment_data where experiment_id = $ExperimentRowID and formfield_id = $questionid";
			echo "[$sqlstring]<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				/* this question has already been filled out...
                   no need to overwrite imported data so create a new experiment to put the question into */
				/* create the experiment, get the rowID */
				$sqlstring = "insert into assessments (enrollment_id, form_id, exp_admindate, experimentor, rater_username, label, notes, iscomplete) values ($enrollmentRowID, $formid, '$datetime', '$rater', '$rater', '$visit', '$notes', 1)";
				echo "[$sqlstring]<br>";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$ExperimentRowID = mysqli_insert_id($GLOBALS['linki']);
			}
			/* insert the question value */
			$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_text, value_date, update_username) values ($questionid,$ExperimentRowID,'$value','$datetime','WebsiteImporter')";
			echo "[$sqlstring]<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			$lastfilegroupid = $filegroupid;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayImportForm ------------------ */
	/* -------------------------------------------- */
	function DisplayImportForm() {

		//$str1 = "$subjectids,999999,$formid,$dsmquestionid,$groupid,$code,$rater,$date,$notes,$visit";

		?>
		Paste CSV style data here using this format: <tt>subjectID(s), projectCostCenter, formID, questionID, groupID, value(s), rater, datetime, notes, visit</tt>
		<span class="tiny">
		<ul>
			<li>There can be multiple subjectIDs, which should be separated by the pipe | character. Only the first found subjectID will be used
			<li>There can be multiple values, which should be separated by a pipe |
			<li>Group ID only needs to be unique among the rows in this import, however groupids must be consecutive
		</ul>
		</span>
		<br>
		<form method="post" action="importexperiment.php">
		<input type="hidden" name="action" value="import">
		<textarea style="width:100%; height: 60%" name="data"></textarea>
		<input type="submit" value="Import!">
		</form>
		<?
	}
?>


<? include("footer.php") ?>
