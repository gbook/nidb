
<?
 // ------------------------------------------------------------------------------
 // NiDB projects.php
 // Copyright (C) 2004 - 2022
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
		<title>NiDB - Projects</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$projectid = GetVariable("projectid");
	if ($id == "") { $id = $projectid; }
	$viewtype = GetVariable("viewtype");
	$newprojectid = GetVariable("newprojectid");
	$studyids = GetVariable("studyids");
	$matchidonly = GetVariable("matchidonly");
	$modalities = GetVariable("modalities");
	$oldnames = GetVariable("oldname");
	$newnames = GetVariable("newname");
	$protocolnames = GetVariable("protocolname");
	$experimentids = GetVariable("experimentid");
	$subjectids = GetVariable("subjectid");
	$altuids = GetVariable("altuids");
	$guids = GetVariable("guids");
	$birthdates = GetVariable("birthdates");
	$genders = GetVariable("genders");
	$ethnicity1s = GetVariable("ethnicity1");
	$ethnicity2s = GetVariable("ethnicity2");
	$educations = GetVariable("education");
	$maritalstatus = GetVariable("maritalstatus");
	$smokingstatus = GetVariable("smokingstatus");
	$enrollgroups = GetVariable("enrollgroup");
	$studytable = GetVariable("studytable");
	$subjecttable = GetVariable("subjecttable");
	$tags = GetVariable("tags");
	$serieslist1 = GetVariable("serieslist1");
	$serieslist2 = GetVariable("serieslist2");
	$csv = GetVariable("csv");
	
	$rdoc_label = GetVariable("rdoc_label");
	$itemprotocol = GetVariable("itemprotocol");
	$xnathost = GetVariable("xnathost");

	/* determine action */
	switch ($action) {
		case 'displaystudies':
			DisplayStudiesTable2($id);
			break;
		case 'updatestudyage':
			UpdateStudyAge($id);
			DisplayStudiesTable2($id);
			break;
		case 'auditstudies':
			AuditStudies($id);
			break;
		case 'changeproject':
			ChangeProject($newprojectid, $studyids);
			DisplayStudiesTable2($id);
			break;
		case 'editbidsdatatypes':
			EditBIDSDatatypes($id);
			break;
		case 'viewbidsdatatypes':
			ViewBIDSDatatypes($id);
			break;
		case 'editxnat':
			EditXNAT($id);
			break;
		case 'savexnat':
			SaveXNAT($id, $xnathost);
			DisplayProject($id);
			break;
		case 'dismissnewstudies':
			DismissNewStudies($id);
			DisplayProject($id);
			break;
		case 'updatebidsmapping':
			UpdateBIDSMapping($id, $modalities, $oldnames, $newnames);
			EditBIDSMapping($id);
			break;
		case 'editbidsmapping':
			EditBIDSMapping($id);
			break;
		case 'updatendamapping':
			UpdateNDAMapping($id, $modalities, $protocolnames, $experimentids);
			EditNDAMapping($id);
			break;
		case 'editndamapping':
			EditNDAMapping($id);
			break;
		case 'updateexperimentmapping':
			UpdateExperimentMapping($id, $modalities, $protocolnames, $experimentids);
			EditExperimentMapping($id);
			break;
		case 'editexperimentmapping':
			EditExperimentMapping($id);
			break;
		case 'displayprojectinfo':
			DisplayProject($id);
			break;
		case 'editsubjects':
			DisplaySubjectsTable($id);
			break;
		case 'displaysubjects':
			DisplaySubjectsTable($id,1);
			break;
		case 'applytags':
			ApplyTags($id, $studyids, $tags);
			DisplayStudiesTable2($id);
			break;
		case 'displaycompleteprojecttable':
			DisplayCompleteProjectTable($id);
			break;
		case 'compareserieslists':
			DisplayCompareSeriesLists($id, $serieslist1, $serieslist2);
			break;
		case 'viewinstancesummary':
			DisplayInstanceSummary($id);
			break;
		case 'changealternatenames':
			ChangeSeriesAlternateNames($id, $modalities, $oldnames, $newnames);
			EditBIDSDatatypes($id);
			break;
		case 'obliteratesubject':
			ObliterateSubject($studyids);
			DisplayProjects($viewtype);
			break;
		case 'obliteratestudy':
			ObliterateStudy($studyids);
			DisplayProjects($viewtype);
			break;
		case 'rearchivestudies':
			RearchiveStudies($studyids, $matchidonly);
			DisplayProjects($viewtype);
			break;
		case 'rearchivesubjects':
			RearchiveSubjects($studyids, $matchidonly);
			DisplayProjects($viewtype);
			break;
		case 'resetqa':
			ResetProjectQA($id);
			DisplayStudiesTable2($id);
			break;
		case 'show_rdoc_list':
			DisplayRDoCList($rdoc_label);
			break;
		case 'assessmentinfo':
			DisplayFormList($id);
			break;
		case 'setfavorite':
			SetFavorite($id);
			DisplayProject($id);
			break;
		case 'unsetfavorite':
			UnsetFavorite($id);
			DisplayProject($id);
			break;
		case 'batchupdatesubject':
			BatchUpdateSubject($id, $csv);
			DisplaySubjectsTable($id);
			break;
		default:
			if ($id == '') {
				DisplayProjects($viewtype);
			}
			else {
				DisplayProject($id);
			}
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- BatchUpdateSubject ----------------- */
	/* -------------------------------------------- */
	function BatchUpdateSubject($projectid, $csvstr) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		
		$csv = preg_split('/\r\n|\r|\n/', $csvstr);
		$csv[0] = str_replace(' ','', $csv[0]);
		
		$data = array_map('str_getcsv', $csv);
		array_walk($data, function(&$a) use ($data) {
			$a = array_combine($data[0], $a);
		});
		array_shift($data); # remove column header
		
		$msgs = array();
		foreach ($data as $line) {
			$uid = $line['uid'];
			$sqlstring = "select subject_id from subjects where uid = '$uid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$subjectid = $row['subject_id'];
				foreach ($line as $column => $value) {
					$column = mysqli_real_escape_string($GLOBALS['linki'], trim($column));
					$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
					if (in_array($column, array('altuids', 'guid', 'birthdate', 'sex', 'gender', 'ethnicity1', 'ethnicity2', 'handedness', 'education', 'marital', 'smoking', 'enrollgroup'))) {
						$msgs[] = UpdateSubjectDetails($uid, $subjectid, $projectid, $column, $value);
					}
				}
			}
		}
		Notice(implode2("<br>", $msgs));
	}


	/* -------------------------------------------- */
	/* ------- UpdateSubjectDetails --------------- */
	/* -------------------------------------------- */
	function UpdateSubjectDetails($uid, $subjectid, $projectid, $column, $value) {
		
		if ($subjectid == "") {
			echo "error, subjectID blank";
			return;
		}
		
		$msg = "";
		
		if ($column == "altuids") {
			StartSQLTransaction();
			/* get enrollmentid */
			$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectid and project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$enrollmentid = $row['enrollment_id'];
			if ($enrollmentid == "") { $enrollmentid = 0; }

			/* delete entries for this subject from the altuid table ... */
			$sqlstring = "delete from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			/* ... and insert the new rows into the altuids table */
			$altuidsublist = $value;
			$altuids = explode(',',$altuidsublist);
			foreach ($altuids as $altuid) {
				$altuid = trim($altuid);
				if ($altuid != "") {
					//$enrollmentid = $enrollmentids[$i];
					if ($enrollmentid == "") { $enrollmentid = 0; }
					if (strpos($altuid, '*') !== FALSE) {
						$altuid = str_replace('*','',$altuid);
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',1, '$enrollmentid')";
					}
					else {
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',0, '$enrollmentid')";
					}
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
			CommitSQLTransaction();
			$msg = "$uid - Updated $column alternate UIDs <tt>$value</tt>";
		}
		elseif ($column == "enrollgroup") {
			$sqlstring = "update enrollment set enroll_subgroup = '$value' where project_id = $projectid and subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$msg = "$uid - Updated enroll group &rarr; <tt>$value</tt>";
		}
		else {
			$sqlstring = "update subjects set ";
			switch ($column) {
				case "guid": $sqlstring .= "guid"; break;
				case "sex": $sqlstring .= "subjects.sex"; break;
				case "gender": $sqlstring .= "gender"; break;
				case "birthdate": $sqlstring .= "birthdate"; break;
				case "ethnicity1": $sqlstring .= "ethnicity1"; break;
				case "ethnicity2": $sqlstring .= "ethnicity2"; break;
				case "handedness": $sqlstring .= "handedness"; break;
				case "education":
					switch ($value) {
						case "Unknown": $value = 0; break;
						case "Grade School": $value = 1; break;
						case "Middle School": $value = 2; break;
						case "High School/GED": $value = 3; break;
						case "Trade School": $value = 4; break;
						case "Associates Degree": $value = 5; break;
						case "Bachelors Degree": $value = 6; break;
						case "Masters Degree": $value = 7; break;
						case "Doctoral Degree": $value = 8; break;
						default: $value = "";
					}
					$sqlstring .= "education";
					break;
				case "marital": $sqlstring .= "marital_status"; break;
				case "smoking": $sqlstring .= "smoking_status"; break;
				case "enrollgroup": $sqlstring .= "enroll_subgroup"; break;
				default: echo "error - [$column] not recognized"; return;
			}
			$sqlstring .= " = '$value' where subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$msg = "$uid - Updated $column &rarr; <tt>$value</tt>";
		}
		
		return $msg;
	}


	/* -------------------------------------------- */
	/* ------- ApplyTags -------------------------- */
	/* -------------------------------------------- */
	function ApplyTags($id, $studyids, $tags) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);
		$tags = mysqli_real_escape_string($GLOBALS['linki'], $tags);
		$taglist = explode(',', $tags);
		
		$studyids = implode2(",", $studyids);
		
		if (count($studyids) > 0) {
			/* get list of enrollments from these studies */
			$sqlstring = "select enrollment_id from studies where study_id in ($studyids)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0){
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$enrollmentid = $row['enrollment_id'];

					foreach ($taglist as $tag) {
						$sqlstringA = "insert ignore into tags (tagtype, enrollment_id, tag) values ('dx', $enrollmentid, '$tag')";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						?><div class="message">Applied tag [<?=$tag?>] to enrollmentid [<?=$enrollmentid?>]</div><?
					}
				}
			}
		}
		else {
			Notice("No studies selected");
		}
	}


	/* -------------------------------------------- */
	/* ------- DismissNewStudies ------------------ */
	/* -------------------------------------------- */
	function DismissNewStudies($id) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$sqlstring = "update user_project set lastview_cleardate = now() where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("New studies dismissed");
	}


	/* -------------------------------------------- */
	/* ------- EditXNAT --------------------------- */
	/* -------------------------------------------- */
	function EditXNAT($id) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$sqlstring = "select xnat_hostname from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$xnathost = $row['xnat_hostname'];
		
		?>
		<div class="ui text container">
			<form method="post" action="projects.php" class="ui form">
				<input type="hidden" name="action" value="savexnat">
				<input type="hidden" name="id" value="<?=$id?>">
				<div class="field">
					<label>XNAT hostname</label>
					<input type="text" name="xnathost" value="<?=$xnathost?>" placeholder="Full hostname, ex. http://hostname...">
				</div>
				<input type="submit" class="ui button" value="Save">
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SaveXNAT --------------------------- */
	/* -------------------------------------------- */
	function SaveXNAT($id, $xnathost) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		$xnathost = mysqli_real_escape_string($GLOBALS['linki'], $xnathost);
		
		$sqlstring = "update projects set xnat_hostname = '$xnathost' where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("XNAT hostname '$xnathost' saved");
	}


	/* -------------------------------------------- */
	/* ------- SetFavorite ------------------------ */
	/* -------------------------------------------- */
	function SetFavorite($id) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$sqlstring = "update user_project set favorite = 1 where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- UnsetFavorite ---------------------- */
	/* -------------------------------------------- */
	function UnsetFavorite($id) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$sqlstring = "update user_project set favorite = 0 where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- ResetProjectQA --------------------- */
	/* -------------------------------------------- */
	function ResetProjectQA($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if ($id == "") {
			Error("Invalid project ID");
		}
		
		/* get list of series associated with this project */
		$sqlstring = "select mrseries_id from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriesid = $row['mrseries_id'];
			echo "$seriesid<br>";
			ResetQA($seriesid);
		}
	}

	
	/* -------------------------------------------- */
	/* ------- ObliterateSubject ------------------ */
	/* -------------------------------------------- */
	function ObliterateSubject($studyids) {
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);
		
		/* get list of subjects from the studyids */
		$sqlstring = "select subject_id, uid from subjects where subject_id in (select subject_id from enrollment where enrollment_id in (select enrollment_id from studies where study_id in (" . implode(',',$studyids) . ") ))";
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
		?>
		<div align="center" class="message">Subjects [<?=implode(', ',$uids)?>] queued for obliteration</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ObliterateStudy -------------------- */
	/* -------------------------------------------- */
	function ObliterateStudy($studyids) {
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);
		
		/* delete all information about this SUBJECT from the database */
		foreach ($studyids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Studies [<?=implode2(', ',$studyids)?>] queued for obliteration</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- RearchiveStudies ------------------- */
	/* -------------------------------------------- */
	function RearchiveStudies($studyids, $matchidonly) {
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);
		$matchidonly = mysqli_real_escape_string($GLOBALS['linki'], $matchidonly);
		
		/* rearchive all the studies */
		foreach ($studyids as $id) {
			if ($matchidonly) {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rearchiveidonly', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			}
			else {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rearchive', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			}
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		Notice("Studies [" . implode(', ',$studyids) . "] queued for re-archiving");
	}

	
	/* -------------------------------------------- */
	/* ------- RearchiveSubjects ------------------ */
	/* -------------------------------------------- */
	function RearchiveSubjects($studyids, $matchidonly) {
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);
		$matchidonly = mysqli_real_escape_string($GLOBALS['linki'], $matchidonly);
		
		/* get list of subjects from the studyids */
		$sqlstring = "select subject_id, uid from subjects where subject_id in (select subject_id from enrollment where enrollment_id in (select enrollment_id from studies where study_id in (" . implode(',',$studyids) . ") ))";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ids[] = $row['subject_id'];
			$uids[] = $row['uid'];
		}
		
		/* delete all information about this subject from the database */
		foreach ($ids as $id) {
			if ($matchidonly) {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rearchiveidonly', 'subject', $id,'" . $GLOBALS['username'] . "', now())";
			}
			else {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rearchive', 'subject', $id,'" . $GLOBALS['username'] . "', now())";
			}
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Subjects [<?=implode(', ',$uids)?>] queued for re-archiving</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ChangeProject ---------------------- */
	/* -------------------------------------------- */
	function ChangeProject($projectRowID, $studyids) {
		$projectRowID = mysqli_real_escape_string($GLOBALS['linki'], $projectRowID);
	
		foreach ($studyids as $studyRowID) {
			$studyRowID = mysqli_real_escape_string($GLOBALS['linki'], $studyRowID);
			
			/* get the subject ID */
			$sqlstring = "select a.subject_id, b.enrollment_id from enrollment a left join studies b on a.enrollment_id = b.enrollment_id where b.study_id = $studyRowID";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$subjectRowID = $row['subject_id'];
				$existingEnrollmentRowID = $row['enrollment_id'];
			}
			else {
				echo "This study is not part of an enrollment...<br>";
				continue;
			}
		
			/* check if the subject is enrolled in the project */
			$sqlstring = "select * from enrollment where project_id = $projectRowID and subject_id = $subjectRowID";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$enrollmentRowID = $row['enrollment_id'];
				?><span style="color:green">[<?=$subjectRowID?>] is already enrolled in [<?=$projectRowID?>] with enrollment [<?=$enrollmentRowID?>]</span><br><?
			}
			else {
				/* if they're not enrolled, create the enrollment, with the enrollment date of the 'scandate' */
				$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				echo "Creating enrollment [$sqlstring]<br>";
				$enrollmentRowID = mysqli_insert_id($GLOBALS['linki']);
			}
			
			/* check if the study is already associated with the enrollment, and if not, move the study to the enrollment */
			$sqlstring = "select * from studies where enrollment_id = $enrollmentRowID and study_id = $studyRowID";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$enrollmentRowID = $row['enrollment_id'];
				?><span style="color:green">Study [<?=$studyRowID?>] is already part of enrollment [<?=$enrollmentRowID?>]</span><br><?
			}
			else {
				/* if the study is not associated with the enrollment, associate it */
				$sqlstring = "update studies set enrollment_id = $enrollmentRowID where study_id = $studyRowID";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				echo "Moved study from enrollment $existingEnrollmentRowID to $enrollmentRowID<br>";
				//exit(0);
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyAge --------------------- */
	/* -------------------------------------------- */
	function UpdateStudyAge($projectid) {
		$projectid = mysqli_real_escape_array($GLOBALS['linki'], $projectid);
		
		/* get list of studies for this project */
		$sqlstring = "select a.study_id, a.study_datetime, a.study_ageatscan, d.birthdate from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* for each study, calculate and update the StudyAge */
		$numupdated = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			list($studyAge, $calcStudyAge) = GetStudyAge($row['birthdate'], $row['study_ageatscan'], $row['study_datetime']);
			
			if ($calcStudyAge != null) {
				$studyid = $row["study_id"];
				$sqlstringA = "update studies set study_ageatscan = $calcStudyAge where study_id = $studyid";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				$numupdated++;
			}
		}
 
		Notice("Updated StudyAge for $numupdated studies");
	}


	/* -------------------------------------------- */
	/* ------- ChangeSeriesAlternateNames --------- */
	/* -------------------------------------------- */
	function ChangeSeriesAlternateNames($id, $modalities, $oldnames, $newnames) {
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality, uid, study_num from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = b.subject_id where a.project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numrowsaffected = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			
			foreach ($modalities as $i => $modality) {
				$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
				$oldname = mysqli_real_escape_string($GLOBALS['linki'], $oldnames[$i]);
				$newname = mysqli_real_escape_string($GLOBALS['linki'], $newnames[$i]);
				
				if (IsNiDBModality($modality)) {
					if (($modality != "") && ($studyid != "") && ($oldname != "") && ($newname != "")) {
						$sqlstringA = "update $modality" . "_series set series_altdesc = '$newname' where (series_desc = '$oldname' or (series_protocol = '$oldname' and (series_desc = '' or series_desc is null))) and study_id = '$studyid'";
						$numupdates = 0;
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						$numupdates = mysqli_affected_rows($GLOBALS['linki']);
						$numrowsaffected += $numupdates;
						if ($numupdates > 0) {
							//echo "[$sqlstringA]<br>";
							echo "<b>Added alternate series description for $uid$studynum. $oldname &rarr; $newname</b><br>";
						}
					}
				}
			}
		}
		echo "Updated [$numrowsaffected] rows<br>";
	}


	/* -------------------------------------------- */
	/* ------- AuditStudies ----------------------- */
	/* -------------------------------------------- */
	function AuditStudies($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		$admin = $row['project_admin'];
		$pi = $row['project_pi'];
		$costcenter = $row['project_costcenter'];
		$sharing = $row['project_sharing'];
		$startdate = $row['project_startdate'];
		$enddate = $row['project_enddate'];
		
		/* get list of all studies associated with this project */
		$sqlstring = "select a.*, c.*, d.*,(datediff(a.study_datetime, d.birthdate)/365.25) 'age' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by d.uid asc, a.study_modality asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		?>
		<table class="ui very compact celled grey table">
			<thead>
				<th>Study</th>
				<th>Date</th>
				<th># series</th>
				<th>Modality</th>
				<th>Ok?</th>
			</thead>
		<?
		/* for each study get a list of series and check if they exist on disk */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$modality = strtolower($row['study_modality']);
			$study_datetime = $row['study_datetime'];
			$study_site = $row['study_site'];
			$studynum = $row['study_num'];
			$study_desc = $row['study_desc'];
			$study_visit = $row['study_type'];
			$study_site = $row['study_site'];
			$study_altid = $row['study_alternateid'];
			$study_ageatscan = $row['study_ageatscan'];
			$age = $row['age'];
			$sex = $row['gender'];
			$uid = $row['uid'];
			$subjectid = $row['subject_id'];
			$project_name = $row['project_name'];
			$project_costcenter = $row['project_costcenter'];
			$isactive = $row['isactive'];
			
			$sqlstringA = "select * from " . $modality . "_series where study_id = $studyid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$numseries = mysqli_num_rows($resultA);
			/* check all the series on disk */
			$problem = 0;
			$problems = array();
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$seriesid = $rowA[$modality . "series_id"];
				$seriesnum = $rowA['series_num'];
				$seriesdesc = $rowA['series_desc'];
				$datatype = $rowA['data_type'];
				$numfiles = $rowA['numfiles'];

				if ($datatype == "") { $datatype = $modality; }
				
				if (($numfiles == 0) || ($numfiles == "")) {
					$numfiles = $rowA['series_numfiles'];
				}
				
				$archivepath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/$datatype";
				//echo "$archivepath ";
				
				//if (($numfiles == 0) || ($numfiles == "")) {
				//	/* the database says there are no files, so don't check the filesystem */
				//}
				//else {
					if (!file_exists($archivepath)) {
						/* only report a problem if the database says there should be files on the disk */
						//if ($numfiles > 0) {
							$problem = 1;
							$problems[] = "Directory [$archivepath] does not exist";
						//}
					}
					else {
						/* check if there are actually files in there */
						$filecount = 0;
						$files = glob("$archivepath/*");
						if ($files){
							$filecount = count($files);
						}
						
						if ($filecount == 0) {
							$problem = 1;
							$problems[] = "Directory [$archivepath] is empty";
						}
						elseif ($filecount != $numfiles) {
							$problem = 1;
							$problems[] = "Number of files in DB [$numfiles] different than on filesystem [$filecount] for [$archivepath]";
						}
					}
				//}
			}

			if (!$problem) {
				?>
				<tr>
					<td><a href="studies.php?id=<?=$studyid?>"><?="$uid$studynum"?></a></td>
					<td><?=$study_datetime?></td>
					<td><?=$numseries?></td>
					<td><?=$modality?></td>
					<td><span style="color: green">&#10004;</span></td>
				</tr>
				<?
			}
			else {
				?>
				<tr style="font-weight: bold">
					<td style="border-top: 1px solid red; background-color: #ffd1d1"><a href="studies.php?id=<?=$studyid?>"><?="$uid$studynum"?></a></td>
					<td style="border-top: 1px solid red; background-color: #ffd1d1"><?=$study_datetime?></td>
					<td style="border-top: 1px solid red; background-color: #ffd1d1"><?=$numseries?></td>
					<td style="border-top: 1px solid red; background-color: #ffd1d1"><?=$modality?></td>
					<td style="border-top: 1px solid red; background-color: #ffd1d1"><span style="color: red">&#10006;</span></td>
				</tr>
				<tr>
					<td colspan="5" style="padding-left: 20px; border-bottom: 1px solid red; background-color: #ffd1d1">
						<?
						foreach ($problems as $prob) {
							?><?=$prob?><br><?
						}
						?>

					</td>
				</tr>
				<?
			}
		}
		?>
		</table>
		<?
	}

# My Changes Asim 04/16/2018
	/* -------------------------------------------- */
	/* ------- DisplayForm ------------------------ */
	/* -------------------------------------------- */
	function DisplayForm($id) {
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
	
	?>
		<div align="center">

		<br><br>
		<table class="formentrytable">
			<tr>
				<td class="title" colspan="3"><?=$title?></td>
			</tr>
			<tr>
				<td class="desc" colspan="3"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
				<td style="font-size:8pt; color: darkblue">Question #</td>
				<td style="font-size:8pt; color: darkblue">Question ID</td>
			</tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "select * from assessment_formfields where form_id = $id order by formfield_order + 0";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$formfield_id = $row['formfield_id'];
					$formfield_desc = $row['formfield_desc'];
					$formfield_values = $row['formfield_values'];
					$formfield_datatype = $row['formfield_datatype'];
					$formfield_order = $row['formfield_order'];
					$formfield_scored = $row['formfield_scored'];
					$formfield_haslinebreak = $row['formfield_haslinebreak'];
					
					?>
					<tr>
						<? if ($formfield_datatype == "header") { ?>
							<td colspan="2" class="sectionheader"><?=$formfield_desc?></td>
						<? } else { ?>
							<td class="field"><?=$formfield_desc?></td>
							<td class="value">
							<?
								switch ($formfield_datatype) {
									case "binary": ?><input type="file" name="value[]"><? break;
									case "multichoice": ?>
										<select multiple name="<?=$formfield_id?>-multichoice" style="height: 150px">
											<?
												$values = explode(",", $formfield_values);
												natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<option value="<?=$value?>"><?=$value?></option>
												<?
												}
											?>
										</select>
										<br>
										<span class="tiny">Hold <b>Ctrl</b>+click to select multiple items</span>
									<? break;
									case "singlechoice": ?>
											<?
												$values = explode(",", $formfield_values);
												//natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<input type="radio"  name="<?=$formfield_id?>-singlechoice" value="<?=$value?>"><?=$value?>
												<?
													if ($formfield_haslinebreak) { echo "<br>"; } else { echo "&nbsp;"; }
												}
											?>
									<? break;
									case "date": ?><input type="date" name="<?=$formfield_id?>-date"><? break;
									case "number": ?><input type="number" name="<?=$formfield_id?>-number"><? break;
									case "string": ?><input type="text" name="<?=$formfield_id?>-string"><? break;
									case "text": ?><textarea name="<?=$formfield_id?>-text"></textarea><? break;
								}
							?>
						<? } ?>
						</td>
						<? if ($formfield_scored) {?>
						<td><input type="text" size="2"></td>
						<? } ?>
						<td class="order"><?=$formfield_order?></td>
						<td class="order"><?=$formfield_id?></td>
					</tr>
					<?
				}
			?>
		</table>
		<br><br>
		
		</div>
	<?

	

	}


	/* -------------------------------------------- */
	/* ------- DisplayFormList -------------------- */
	/* -------------------------------------------- */
	function DisplayFormList($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
	?>

	<table class="ui very compact celled grey table">
		<thead>
			<tr>
				<th>Title</th>
				<th>Description</th>
				<th>Creator</th>
				<th>Create Date</th>
				<th>Published</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'creatorusername', b.user_fullname 'creatorfullname' from assessment_forms a left join users b on a.form_creator = b.user_id order by a.form_title";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['form_id'];
					$title = $row['form_title'];
					$desc = $row['form_desc'];
					$creatorusername = $row['creatorusername'];
					$creatorfullname = $row['creatorfullname'];
					$createdate = $row['form_createdate'];
					$ispublished = $row['form_ispublished'];
			?>
			<tr>
				<td>
					<? if ($ispublished) { ?>
					<a href="adminassessmentforms.php?action=viewform&id=<?=$id?>"><?=$title?></a>
					<? } else { ?>
					<a href="adminassessmentforms.php?action=editform&id=<?=$id?>"><?=$title?></a>
					<? } ?>
				</td>
				<td><?=$desc?></td>
				<td><?=$creatorfullname?></td>
				<td><?=$createdate?></td>
				<td><? if ($ispublished) { echo "&#10004;"; } ?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}

# End My Changes Asim 04/16/2018
	
	
	/* -------------------------------------------- */
	/* ------- DisplayCompleteProjectTable -------- */
	/* -------------------------------------------- */
	function DisplayCompleteProjectTable($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		$admin = $row['project_admin'];
		$pi = $row['project_pi'];
		$costcenter = $row['project_costcenter'];
		$sharing = $row['project_sharing'];
		$startdate = $row['project_startdate'];
		$enddate = $row['project_enddate'];

		?>
		<form method="post" action="projects.php">
		<input type="hidden" name="action" value="compareserieslists">
		<input type="hidden" name="id" value="<?=$id?>">
		Series list from this server<br>
		<textarea style="width: 100%; height: 300px" name="serieslist1" readonly><?
		/* get all series associated with this project (MR only for now) */
		$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id left join mr_series d on c.study_id = d.study_id where b.project_id = $id order by c.study_datetime, d.series_num";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];
			$guid = $row['guid'];
			$gender = $row['gender'];
			$birthdate = $row['birthdate'];
			$enrollsubgroup = $row['enroll_subgroup'];
			$enrollmentid = $row['enrollment_id'];
			$studydatetime = $row['study_datetime'];
			$studyaltid = $row['study_alternateid'];
			$seriesdatetime = $row['series_datetime'];
			$seriesdesc = $row['series_desc'];
			$seriesprotocol = $row['series_protocol'];
			$seriessequencename = $row['series_sequencename'];
			$seriesnum = $row['series_num'];
			$seriesnumfiles = $row['numfiles'];
			$seriesnumbeh = $row['numfiles_beh'];

			$altuids = implode(',', GetAlternateUIDs($subjectid, $enrollmentid));
			
			if ($studydatetime == "") { $studydatetime = "No Studies"; }
			
			echo "$studydatetime\t$seriesnum\t$seriesdatetime\t$seriesdesc\t$seriesprotocol\t$seriesnumfiles\t$seriesnumbeh\t$uid\t$altuids\n";
		}
		?>
		</textarea>
		<br><br>
		PASTE a series list from another NiDB server here<br>
		<textarea style="width: 100%; height: 300px" name="serieslist2"></textarea>
		<input type="submit" value="Compare">
		</form>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayCompareSeriesLists ---------- */
	/* -------------------------------------------- */
	function DisplayCompareSeriesLists($id, $locallist, $foreignlist) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		$admin = $row['project_admin'];
		$pi = $row['project_pi'];
		$costcenter = $row['project_costcenter'];
		$sharing = $row['project_sharing'];
		$startdate = $row['project_startdate'];
		$enddate = $row['project_enddate'];

		$localseries = explode("\n",$locallist);
		$foreignseries = explode("\n",$foreignlist);
		
		$i = 0;
		foreach ($localseries as $row) {
			if (trim($row) != "") {
				$p = explode("\t", $row);
				if ($p[0] != "No Studies") {
					$ids = $p[7] . " " . $p[8];
					$series = implode(",",array($p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6]));
					$local[$i]['ids'] = trim($ids);
					$local[$i]['series'] = trim($series);
					$i++;
				}
			}
		}
		//PrintVariable($local);
		
		$i = 0;
		foreach ($foreignseries as $row) {
			if (trim($row) != "") {
				$p = explode("\t", $row);
				if ($p[0] != "No Studies") {
					$ids = $p[7] . " " . $p[8];
					$series = implode(",",array($p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6]));
					$remote[$i]['ids'] = trim($ids);
					$remote[$i]['series'] = trim($series);
					$i++;
				}
			}
		}
		//PrintVariable($remote);
		
		?>
		The following LOCAL series were not found in the REMOTE database
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th>UID</th>
					<th>Study Date</th>
					<th>Series Num</th>
					<th>Series Date</th>
					<th>SeriesDesc</th>
					<th>Protocol</th>
					<th>Num files</th>
					<th>Num beh</th>
				</tr>
		<?
		/* find all local rows that are NOT in the remote rows */
		foreach ($local as $i => $series1) {
			$localseries = $series1['series'];
			
			$found = 0;
			foreach ($remote as $j => $series2) {
				$remoteseries = $series2['series'];
				if ($localseries == $remoteseries) {
					$found = 1;
					break;
				}
			}
			if (!$found) {
				$localids = $series1['ids'];
				$localuids = explode(" ", $localids);
				$localuid = $localuids[0];
				
				$sp = explode(",", $localseries);
				?>
				<tr>
					<td><?=$localuid?></td>
					<td><?=$sp[0]?></td>
					<td><?=$sp[1]?></td>
					<td><?=$sp[2]?></td>
					<td><?=$sp[3]?></td>
					<td><?=$sp[4]?></td>
					<td><?=$sp[5]?></td>
					<td><?=$sp[6]?></td>
				</tr>
				<?
			}
		}
		?>
			</tr>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubjectsTable --------------- */
	/* -------------------------------------------- */
	function DisplaySubjectsTable($id, $isactive=1) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		$rowdata = array();
		
		/* get all subjects, and their enrollment info, associated with the project */
		$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id and a.isactive = '$isactive' order by a.uid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numsubjects = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$enrollmentid = $row['enrollment_id'];
			$uid = $row['uid'];
			$guid = $row['guid'];
			$sex = $row['sex'];
			$gender = $row['gender'];
			$birthdate = $row['birthdate'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$maritalstatus = $row['marital_status'];
			$smokingstatus = $row['smoking_status'];
			$enrollsubgroup = $row['enroll_subgroup'];
			
			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$isprimary = $rowA['isprimary'];
				$altid = $rowA['altuid'];
				if ($isprimary) {
					$globalaltids[] = "*" . $altid;
				}
				else {
					$globalaltids[] = $altid;
				}
			}
			$globalaltids = array_unique($globalaltids);
			$globalaltuidlist = implode2(", ",$globalaltids);
			$globalaltids = array();
			
			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' and enrollment_id = $enrollmentid order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$isprimary = $rowA['isprimary'];
				$altid = $rowA['altuid'];
				if ($isprimary) {
					$projectaltids[] = "*" . $altid;
				}
				else {
					$projectaltids[] = $altid;
				}
			}
			$projectaltids = array_unique($projectaltids);
			$projectaltuidlist = implode2(", ",$projectaltids);
			//PrintVariable($projectaltids);
			$projectaltids = array();
			
			switch ($education) {
				case "": $education = "-"; break;
				case 0: $education = "Unknown"; break;
				case 1: $education = "Grade School"; break;
				case 2: $education = "Middle School"; break;
				case 3: $education = "High School/GED"; break;
				case 4: $education = "Trade School"; break;
				case 5: $education = "Associates Degree"; break;
				case 6: $education = "Bachelors Degree"; break;
				case 7: $education = "Masters Degree"; break;
				case 8: $education = "Doctoral Degree"; break;
			}

			$rowdata[] = "{ id: $subjectid, enrollmentid: $subjectid, uid: \"$uid\", globalaltuids: \"$globalaltuidlist\", altuids: \"$projectaltuidlist\", guid: \"$guid\", dob: \"$birthdate\", sex: \"$sex\", gender: \"$gender\", ethnicity1: \"$ethnicity1\", ethnicity2: \"$ethnicity2\", handedness: \"$handedness\", education: \"$education\", marital: \"$maritalstatus\", smoking: \"$smokingstatus\", enrollgroup: \"$enrollsubgroup\" }";
		}
		$data = "";
		if (count($rowdata) > 0)
			$data = implode(",", $rowdata);
		
		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<!-- Include the core CSS, this is needed by the grid -->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css"/>-->
		<!-- Include the theme CSS, only need to import the theme you are going to use -->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css"/>-->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-balham.css"/>-->

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
					<button class="ui small basic primary compact button" id="batchsubjectupdatebutton"> Batch update...</button> &nbsp;
					<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
				</div>
			</div>
		</div>
		<div class="ui modal" id="batchmodal">
			<div class="header">Batch Update Subject Information</div>
			<div class="content">
				<form action="projects.php" method="post" class="ui form">
					<input type="hidden" name="action" value="batchupdatesubject">
					<input type="hidden" name="projectid" value="<?=$id?>">
					<div class="field">
						<label>Paste .csv formatted data</label>
						<textarea name="csv" style="font-family:monospace"></textarea>
					</div>
					<i class="blue question circle icon"></i> <b>Formatting Guide</b><br>
					Available columns (csv must always contain at least <b>uid</b>)
					<ul>
						<li><tt>uid</tt> - The UID of the subject
						<li><tt>altuids</tt> - space delimited list of alternate UIDs with asterisk indicating primary alternate ID. Example <code>*1234 AB539 ID2054</code>
						<li><tt>guid</tt>
						<li><tt>birthdate</tt> - format <code>YYYY-MM-DD</code>
						<li><tt>sex</tt> - Possible values: F, M, O, U
						<li><tt>gender</tt> - Possible values: F, M, O, U
						<li><tt>ethnicity1</tt> - Possible values: hispanic, nothispanic
						<li><tt>ethnicity2</tt> - Possible values: unknown, asian, black, white, indian, islander, mixed, other
						<li><tt>handedness</tt> - Possible values: R, L, A, U
						<li><tt>education</tt> - Possible values:  0 (Unknown), 1 (Grade School), 2 (Middle School), 3 (High School/GED), 4 (Trade School), 5 (Associates Degree), 6 (Bachelors Degree), 7 (Masters Degree), 8 (Doctoral Degree)
						<li><tt>marital</tt> - Possible values: unknown, married, single, divorced, separated, civilunion, cohabitating, widowed
						<li><tt>smoking</tt> - Possible values: unknown, never, current, past
						<li><tt>enrollgroup</tt>
					</ul>
					<b>Sample .csv format</b>
					<div style="font-family:monospace; padding:8px; background-color: #eee; border: 1px dashed #aaa">
						uid, guid, sex, enrollgroup<br>
						S1234ABC, NDA23548239, F, control
					</div>
				</div>
			<div class="actions">
				<input type="submit" class="ui approve button" value="Update">
				<div class="ui cancel button">Cancel</div>
				</form>
			</div>
		</div>
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<script type="text/javascript">
			let gridApi;

			$(document).ready(function(){
				$('#batchsubjectupdatebutton').click(function(){
					$('#batchmodal').modal('show');
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
			//import { themeBalham } from 'ag-grid-community';
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
							return '<a href="subjects.php?id=' + params.data.id + '">' + params.value + '</a>'
						}
					},
					{ 
						headerName: "Global Alt UIDs",
						field: "globalaltuids",
						editable: false,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + params.value + '</tt>'
						},
						
					},
					{ 
						headerName: "Project Alt UIDs",
						field: "altuids",
						editable: true,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + params.value + '</tt>'
						},
						
					},
					{ headerName: "GUID", field: "guid", editable: true },
					{ headerName: "Birthdate", field: "dob", editable: true, cellDataType: 'dateString' },
					{
						headerName: "Sex",
						field: "sex",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Gender",
						field: "gender",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Ethnicity1",
						field: "ethnicity1",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'hispanic', 'nothispanic'],
							valueListGap: 0
						}
					},
					{ 
						headerName: "Ethnicity2",
						field: "ethnicity2",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['unknown','','asian','black','white','indian','islander','mixed','other'],
							valueListGap: 0
						}
					},
					{
						headerName: "Handedness",
						field: "handedness",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['', 'R', 'L', 'U', 'A'], valueListGap: 0 }
					},
					{
						headerName: "Education",
						field: "education",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['Unknown', '', 'Grade School', 'Middle School', 'High School/GED', 'Trade School', 'Associates Degree', 'Bachelors Degree', 'Masters Degree', 'Doctoral Degree'], valueListGap: 0 }
					},
					{
						headerName: "Marital Status",
						field: "marital",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['unknown', '', 'married', 'single', 'divorced', 'separated', 'civilunion', 'cohabitating', 'widowed'], valueListGap: 0 }
					},
					{
						headerName: "Smoking Status",
						field: "smoking",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {	values: ['unknown', '', 'never', 'current', 'past'], valueListGap: 0 }
					},
					{ headerName: "Enroll Sub-group", field: "enrollgroup", editable: true },
				],

				rowData: [ <?=$data?> ],
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},

				rowSelection: { mode: 'multiRow' }, // allow rows to be selected
				animateRows: false, // have rows animate to new positions when sorted
				//onFirstDataRendered: onFirstDataRendered,
				stopEditingWhenCellsLoseFocus: true,
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
				onCellEditingStopped: (event) => {

					url = "ajaxapi.php?action=updatesubjectdetails&projectid=<?=$id?>&subjectid=" + event.data.id + "&enrollmentid=" + event.data.enrollmentid + "&column=" + event.column.getColDef().field + "&value=" + event.value;
					//console.log(url);
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							console.log(this.responseText);
							if (this.responseText == "success") {
								document.getElementById("updateresult").innerHTML = '<div class="ui success message" style="transition: opacity 3s ease-in-out, opacity 1; !important">Success updating <b>' + event.column.getColDef().field + '</b> to \'' + event.value + '\'</div>';
							}
							else {
								document.getElementById("updateresult").innerHTML = '<div class="ui error message">Error updating ' + event.column.getColDef().field + ' to ' + event.value + '</div>';
							}
						}
					};
					xhttp.open("GET", url, true);
					xhttp.send();
					
				},				
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				// new grid instance, passing in the hosting DIV and Grid Options
				//new agGrid.Grid(eGridDiv, gridOptions);
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				//gridOptions.columnApi.getColumns().forEach((column) => {
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				//gridOptions.columnApi.autoSizeColumns(allColumnIds, skipHeader);
				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}

		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayCompactSubjectsTable -------- */
	/* -------------------------------------------- */
	function DisplayCompactSubjectsTable($id, $isactive=1) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		$rowdata = array();
		
		/* get all subjects, and their enrollment info, associated with the project */
		$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id and a.isactive = '$isactive' order by a.uid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numsubjects = mysqli_num_rows($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$enrollmentid = $row['enrollment_id'];
			$uid = $row['uid'];
			$guid = $row['guid'];
			$sex = $row['sex'];
			$gender = $row['gender'];
			$birthdate = $row['birthdate'];
			$enrollsubgroup = $row['enroll_subgroup'];
			
			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$isprimary = $rowA['isprimary'];
				$altid = $rowA['altuid'];
				if ($isprimary) {
					$globalaltids[] = "*" . $altid;
				}
				else {
					$globalaltids[] = $altid;
				}
			}
			$globalaltids = array_unique($globalaltids);
			$globalaltuidlist = implode2(", ",$globalaltids);
			$globalaltids = array();
			$altids = array();

			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' and enrollment_id = $enrollmentid order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$isprimary = $rowA['isprimary'];
				$altid = $rowA['altuid'];
				if ($isprimary) {
					$altids[] = "*" . $altid;
				}
				else {
					$altids[] = $altid;
				}
			}
			$altids = array_unique($altids);
			$altuidlist = implode2(", ",$altids);
			$altids = array();
			
			$rowdata[] = "{ id: $subjectid, uid: \"$uid\", globalaltuids: \"$globalaltuidlist\", altuids: \"$altuidlist\", guid: \"$guid\", dob: \"$birthdate\", sex: \"$sex\", gender: \"$gender\", enrollgroup: \"$enrollsubgroup\" }";
		}
		
		$data = "";
		if (count($rowdata) > 0)
			$data = implode(",", $rowdata);
		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<!-- Include the core CSS, this is needed by the grid -->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css"/>-->
		<!-- Include the theme CSS, only need to import the theme you are going to use -->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css"/>-->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-balham.css"/>-->

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
		<script type="text/javascript">
			let gridApi;

			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridOptions.api.exportDataAsCsv( {allColumns: false} );
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
							return '<a href="subjects.php?id=' + params.data.id + '">' + params.value + '</a>'
						}
					},
					{ 
						headerName: "Global Alt UIDs",
						field: "globalaltuids",
						editable: false,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + params.value + '</tt>'
						},
						
					},
					{ 
						headerName: "Alt UIDs",
						field: "altuids",
						editable: true,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + params.value + '</tt>'
						},
						
					},
					{ headerName: "GUID", field: "guid", editable: true },
					{ headerName: "Birthdate", field: "dob", editable: true, cellDataType: 'dateString' },
					{
						headerName: "Sex",
						field: "sex",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Gender",
						field: "gender",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{ headerName: "Enroll Sub-group", field: "enrollgroup", editable: true },
				],

				rowData: [ <?=$data?> ],
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},

				rowSelection: { mode: 'multiRow' }, // allow rows to be selected
				animateRows: false, // have rows animate to new positions when sorted
				//onFirstDataRendered: onFirstDataRendered,
				stopEditingWhenCellsLoseFocus: true,
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
				autoSizeStrategy: { type: 'fitCellContents' },
				onCellEditingStopped: (event) => {

					url = "ajaxapi.php?action=updatesubjectdetails&projectid=<?=$id?>&subjectid=" + event.data.id + "&column=" + event.column.getColDef().field + "&value=" + event.value;
					//console.log(url);
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							console.log(this.responseText);
							if (this.responseText == "success") {
								document.getElementById("updateresult").innerHTML = '<div class="ui success message" style="transition: opacity 3s ease-in-out, opacity 1; !important">Success updating <b>' + event.column.getColDef().field + '</b> to \'' + event.value + '\'</div>';
							}
							else {
								document.getElementById("updateresult").innerHTML = '<div class="ui error message">Error updating ' + event.column.getColDef().field + ' to ' + event.value + '</div>';
							}
						}
					};
					xhttp.open("GET", url, true);
					xhttp.send();
					
				},				
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				// new grid instance, passing in the hosting DIV and Grid Options
				//new agGrid.Grid(eGridDiv, gridOptions);
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				//gridOptions.columnApi.getColumns().forEach((column) => {
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				//gridOptions.columnApi.autoSizeColumns(allColumnIds, skipHeader);
				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}

		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudiesTable2 --------------- */
	/* -------------------------------------------- */
	function DisplayStudiesTable2($id, $isactive=1) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }

		$rowdata = array();
		
		/* get all subjects, and their enrollment info, associated with the project */
		/* display studies associated with this project */
		$sqlstring = "select a.*, c.*, d.* from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by d.uid asc, a.study_modality asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		$rowhighlight = 0;
		$lastuid = "";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$study_id = $row['study_id'];
			$modality = $row['study_modality'];
			$studydatetime = $row['study_datetime'];
			$studysite = $row['study_site'];
			$studynum = $row['study_num'];
			$studydesc = $row['study_desc'];
			$studyvisit = $row['study_type'];
			$studyaltid = $row['study_alternateid'];
			$studyageatscan = number_format($row['study_ageatscan'], 1);
			//$age = $row['age'];
			$sex = $row['sex'];
			$gender = $row['gender'];
			$dob = $row['birthdate'];
			$uid = $row['uid'];
			$subjectid = $row['subject_id'];
			$enrollmentid = $row['enrollment_id'];
			$project_name = $row['project_name'];
			$project_costcenter = $row['project_costcenter'];
			$isactive = $row['isactive'];

			list($studyAge, $calcStudyAge) = GetStudyAge($dob, $studyageatscan, $studydatetime);
			//echo "list($studyAge, $calcStudyAge) = GetStudyAge($dob, $studyageatscan, $studydatetime)";
			
			if ($studyAge == null)
				$studyAge = "";
			else
				$studyAge = number_format($studyAge,1);

			if ($calcStudyAge == null)
				$calcStudyAge = "-";
			else
				$calcStudyAge = number_format($calcStudyAge,1);
			
			$altids = array();
			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' and enrollment_id = '$enrollmentid' and altuid <> '' order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$isprimary = $rowA['isprimary'];
				$altid = $rowA['altuid'];
				if ($isprimary) {
					$altids[] = "*" . $altid;
				}
				else {
					$altids[] = $altid;
				}
			}
			$altids = array_unique($altids);
			$altuidlist = implode2(", ",$altids);
			
			if ($uid != $lastuid) { $rowhighlight = 1; }
			else { $rowhighlight = 0; }
			
			$rowdata[] = "{ subjectid: $subjectid, studyid: $study_id, rowhighlight: $rowhighlight, uid: \"$uid\", sex: \"$sex\", gender: \"$gender\", altuids: \"$altuidlist\", uidstudynum: \"$uid$studynum\", visit: \"$studyvisit\", studydate: \"$studydatetime\", studyage: \"$studyageatscan\", calcstudyage: \"$calcStudyAge\", modality: \"$modality\", desc: \"$studydesc\", study_id: \"$studyaltid\", site: \"$studysite\"}";

			$lastuid = $uid;
		}
		
		$data = "";
		if (count($rowdata) > 0)
			$data = implode(",", $rowdata);
		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<!-- Include the core CSS, this is needed by the grid -->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-grid.css"/>-->
		<!-- Include the theme CSS, only need to import the theme you are going to use -->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-alpine.css"/>-->
		<!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community/styles/ag-theme-balham.css"/>-->

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
					<a href="projects.php?action=updatestudyage&id=<?=$id?>" class="ui small basic primary compact button" title="Set StudyAge to CalcStudyAge for all studies">Update StudyAge</a>
					<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
				</div>
			</div>
		</div>
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<style>
			.rowhighlight {
				border-top: 2px solid #888;
			}
		</style>
		<script type="text/javascript">
			let gridApi;

			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridOptions.api.exportDataAsCsv( {allColumns: false} );
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
							return '<a href="subjects.php?id=' + params.data.subjectid + '">' + params.value + '</a>'
						}
					},
					{
						headerName: "Sex",
						field: "sex",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{
						headerName: "Gender",
						field: "gender",
						editable: true,
						cellEditor: 'agSelectCellEditor',
						cellEditorParams: {
							values: ['', 'F', 'M', 'O', 'U'],
							valueListGap: 0
						}
					},
					{ 
						headerName: "Subject Alt UIDs",
						field: "altuids",
						editable: true,
						cellEditor: 'agLargeTextCellEditor',
						cellRenderer: function(params) {
							return '<tt>' + params.value + '</tt>'
						},
						
					},
					{
						headerName: "Study",
						field: "uidstudynum",
						pinned: 'left',
						cellRenderer: function(params) {
							return '<a href="studies.php?id=' + params.data.studyid + '"><b>' + params.value + '</b></a>'
						},
						headerCheckboxSelection: true,
						checkboxSelection: true
					},
					{ headerName: "Visit", field: "visit", editable: true },
					{ headerName: "Study Datetime", field: "studydate", editable: false },
					{ headerName: "StudyAge", field: "studyage", editable: true },
					{ headerName: "Calculated StudyAge", field: "calcstudyage", editable: false },
					{ headerName: "Modality", field: "modality", editable: false },
					{ headerName: "Description", field: "desc", editable: false },
					{ headerName: "Study ID", field: "study_id", editable: false },
					{ headerName: "Site", field: "site", editable: false },
				],

				rowData: [ <?=$data?> ],
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true},
				rowClassRules: {
					// row style expression
					'rowhighlight': 'data.rowhighlight == 1',
				},

				rowSelection: { mode: 'multiRow' }, // allow rows to be selected
				rowMultiSelectWithClick: true,
				animateRows: false, // have rows animate to new positions when sorted
				//onFirstDataRendered: onFirstDataRendered,
				stopEditingWhenCellsLoseFocus: true,
				undoRedoCellEditing: true,
				suppressMovableColumns: true,
				autoSizeStrategy: { type: 'fitCellContents' },
				onCellEditingStopped: (event) => {

					url = "ajaxapi.php?action=updatestudydetails&subjectid=" + event.data.subjectid + "&studyid=" + event.data.studyid + "&column=" + event.column.getColDef().field + "&value=" + event.value;
					console.log(url);
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							console.log(this.responseText);
							if (this.responseText == "success") {
								document.getElementById("updateresult").innerHTML = '<div class="ui success message" style="transition: opacity 3s ease-in-out, opacity 1; !important">Success updating <b>' + event.column.getColDef().field + '</b> to \'' + event.value + '\'</div>';
							}
							else {
								document.getElementById("updateresult").innerHTML = '<div class="ui error message">Error updating ' + event.column.getColDef().field + ' to ' + event.value + '</div>';
							}
						}
					};
					xhttp.open("GET", url, true);
					xhttp.send();
					
				},				
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				
				// new grid instance, passing in the hosting DIV and Grid Options
				//new agGrid.Grid(eGridDiv, gridOptions);
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
				autoSizeAll(false);
			});
			
			function autoSizeAll(skipHeader) {
				const allColumnIds = [];
				//gridOptions.columnApi.getColumns().forEach((column) => {
				gridApi.getColumns().forEach((column) => {
					allColumnIds.push(column.getId());
				});

				//gridOptions.columnApi.autoSizeColumns(allColumnIds, skipHeader);
				gridApi.autoSizeColumns(allColumnIds, skipHeader);
			}
			
		</script>
		
		<table width="100%">
			<tr>
				<? if ($GLOBALS['issiteadmin']) { ?>
				<td style="background-color: #FFFF99; border: 1px solid #4C4C1F; border-radius:5px; padding:8px;" width="70%">

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
									$tag = $row['tag'];
									?>
									<option value="<?=$tag?>">
									<?
								}
							?>
							</datalist>
							<button class="ui button" title="Applies the tags to the selected studies" onclick="document.theform.action='projects.php'; document.theform.action.value='applytags'; document.theform.submit()">Apply tags</button>
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
									<option value="<?=$project_id?>" style="<?=$style?>"><?=$project_name?> (<?=$project_costcenter?>)</option>
									<?
								}
							?>
							</select>
							<button class="ui button" title="Moves the imaging studies from this project to the selected project" onclick="document.theform.action='projects.php'; document.theform.action.value='changeproject'; document.theform.submit()">Move studies</button>
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
						<button class="ui orange button" title="Moves all DICOM files back into the incoming directory to be parsed again. Useful if there was an archiving error and too many subjects are in the wrong place." onclick="document.theform.action='projects.php'; document.theform.action.value='rearchivestudies'; document.theform.submit()">Re-archive DICOM studies</button>
						&nbsp; &nbsp;
						<button class="ui orange button" title="Moves all DICOM files from this SUBJECT into the incoming directory, and deletes the subject" onclick="document.theform.action='projects.php'; document.theform.action.value='rearchivesubjects'; document.theform.submit()">Re-archive Subjects</button>
					</div>
					
					<div class="ui segment">
						<h3 class="ui header">
							Obliterate
						</h3>
						<button class="ui red button" title="Delete the subject permanently" onclick="document.theform.action='projects.php';document.theform.action.value='obliteratesubject'; document.theform.submit()"><i class="bomb icon"></i> Obliterate Subjects</button> &nbsp; &nbsp;
						<button class="ui red button" title="Delete the studies permanently" onclick="document.theform.action='projects.php';document.theform.action.value='obliteratestudy'; document.theform.submit()"><i class="bomb icon"></i> Obliterate Studies</button>
					</div>
				</td>
				<? } ?>

				<td align="right" valign="top">
					<!-- save the form -->
					<form method="post" action="projects.php" id="savetableform">
					<input type="hidden" name="id" value="<?=$id?>">
					<input type="hidden" name="action" value="updatestudytable">
					<input type="hidden" name="studytable" id="studytable">
					<div align="right"><input class="ui primary button" type="submit" value="Save Studies Table"></div>
					</form>
				</td>
			</tr>
		</table>
		
		<?
	}


	/* -------------------------------------------- */
	/* ------- EditBIDSDatatypes ------------------ */
	/* -------------------------------------------- */
	function EditBIDSDatatypes($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];

		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$modality = strtolower($row['study_modality']);
			
			if (IsNiDBModality($modality)) {
				if (($modality != "") && ($studyid != "")) {
					$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' order by series_desc";
					//PrintSQL($sqlstringA);
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						$seriesaltdesc = $rowA['series_altdesc'];
						if ($rowA['series_desc'] != "") {
							$seriesdesc = $rowA['series_desc'];
						}
						elseif ($rowA['series_protocol'] != "") {
							$seriesdesc = $rowA['series_protocol'];
						}
						if ($seriesdesc != "") {
							$seriesdescs[$modality][$seriesdesc]++;
						}
						
						$altdesc[$seriesdesc][$modality] = $seriesaltdesc;
					}
				}
			}
		}
		
		?>
		<br><br>
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="changealternatenames">
		<input type="hidden" name="id" value="<?=$id?>">
		Click <b>Update</b> below to apply these changes to all studies associated with this project
		<br><br>
		<table class="ui very compact celled grey table">
			<thead>
				<th>Modality</th>
				<th>Series Description</th>
				<th>Count</th>
				<th>BIDS datatype</th>
			</thead>
		<?
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			array_multisort(array_keys($serieslist), SORT_NATURAL| SORT_FLAG_CASE, $serieslist);
			foreach ($serieslist as $series => $count) {

				$currentaltdesc = "";
				$currentaltdesc = $altdesc[$series][$modality];
				?>
				<tr>
					<td><?=strtoupper($modality)?></td>
					<td><tt><?=$series?></tt></td>
					<td><?=$count?></td>
					<td><input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>"><input type="hidden" name="oldname[<?=$i?>]" value="<?=$series?>"><input type="text" name="newname[<?=$i?>]" value="<?=$currentaltdesc?>"></td>
				</tr>
				<?
				$i++;
			}
		}
		?>
			<tr>
				<td colspan="3" align="right"><input type="submit" value="Update"></td>
			</tr>
		</table>

		<?
	}


	/* -------------------------------------------- */
	/* ------- ViewBIDSDatatypes ------------------ */
	/* -------------------------------------------- */
	function ViewBIDSDatatypes($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);

		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality, uid, study_num from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = b.subject_id where a.project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyid = $row['study_id'];
				$studynum = $row['study_num'];
				$uid = $row['uid'];
				$modality = strtolower($row['study_modality']);
				
				if (IsNiDBModality($modality)) {
					if (($modality != "") && ($studyid != "")) {
						$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' and ishidden <> 1";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
							$seriesaltdesc = $rowA['series_altdesc'];
							if ($seriesaltdesc != "") {
								$seriesdescs[$uid][$modality][$seriesaltdesc]++;
								$uniqueseries[$modality][$seriesaltdesc]++;
							}
						}
					}
				}
			}
		}
		
		?>
		<br><br>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th></th>
					<?
						foreach ($uniqueseries as $modality => $series) {
							$count = count($series);
							echo "<th colspan='$count'>$modality</th>";
						}
					?>
				</tr>
				<tr>
					<th>UID</th>
					<?
						foreach ($uniqueseries as $modality => $series) {
							foreach ($series as $ser => $count) {
								echo "<th>$ser ($count)</th>";
							}
						}
					?>
				</tr>
			</thead>
		<?

		foreach ($seriesdescs as $uid => $modalities) {
			?>
			<tr>
				<td><?=$uid?></td>
				<?
				foreach ($uniqueseries as $modality => $series) {
					foreach ($series as $ser => $count) {
						$localcount = $seriesdescs[$uid][$modality][$ser];
						if ($localcount > 0) { $bgcolor = "#CAFFC4"; } else { $bgcolor = ""; $localcount = "-"; }
						?>
							<td style="background-color: <?=$bgcolor?>"><?=$localcount?></td>
						<?
					}
				}
				?>
			</tr><?
		}
		?>
		</table>

		<?
	}


	/* -------------------------------------------- */
	/* ------- EditBIDSMapping -------------------- */
	/* -------------------------------------------- */
	function EditBIDSMapping($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectid));
		
		if (($projectid == "null") || ($projectid == null) || ($projectid == "")) {
			$projectid = 'null';
		}
			
		/* get all studies, and all series, associated with this project */
		if ($projectid == "null")
			$sqlstring = "select study_id, study_modality from studies where study_modality = 'mr'";
		else
			$sqlstring = "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = $projectid";
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$modality = strtolower($row['study_modality']);
			
			if (IsNiDBModality($modality)) {
				if (($modality != "") && ($studyid != "")) {
					$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' order by series_desc";
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						if ($rowA['series_desc'] != "") {
							$seriesdesc = $rowA['series_desc'];
						}
						elseif ($rowA['series_protocol'] != "") {
							$seriesdesc = $rowA['series_protocol'];
						}
						if ($seriesdesc != "") {
							$seriesdescs[$modality][$seriesdesc]++;
						}
					}
				}
			}
		}

		/* get list of BIDS protocol mappings for this project */
		if ($projectid == "null")
			$sqlstring = "select * from bids_mapping where project_id is null";
		else
			$sqlstring = "select * from bids_mapping where project_id = $projectid";
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mapping[$row['modality']][$row['protocolname']] = $row['shortname'];
		}
		?>
		<div class="ui text container grid">
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="updatebidsmapping">
		<input type="hidden" name="id" value="<?=$projectid?>">
		<b>BIDS mapping</b>
		<br>
		This mapping is used in exporting of BIDS format. This can also be used to group protocol names together: for example, protocols named <tt>AXMPRAGE</tt> and <tt>T1w</tt> are both <tt>anat</tt>.
		<br><br>
		<table class="ui small celled selectable grey compact table">
			<thead>
				<th>Modality</th>
				<th>Protocol name</th>
				<th>
					BIDS name<br>
					<span class="tiny" style="font-weight: normal">Possible BIDS names are <tt><b>anat</b></tt>, <tt><b>func</b></tt>, <tt><b>dwi</b></tt>, <tt><b>fmap</b></tt></span>
				</th>
			</thead>
		<?
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			array_multisort(array_keys($serieslist), SORT_NATURAL| SORT_FLAG_CASE, $serieslist);
			foreach ($serieslist as $series => $count) {

				$shortname = "";
				$shortname = $mapping[$modality][$series];
				?>
				<tr>
					<td><?=strtoupper($modality)?></td>
					<td><tt><?=$series?></tt></td>
					<td>
						<input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>">
						<input type="hidden" name="oldname[<?=$i?>]" value="<?=$series?>">
						<div class="ui fluid input">
							<input type="text" name="newname[<?=$i?>]" value="<?=$shortname?>">
						</div>
					</td>
				</tr>
				<?
				$i++;
			}
		}
		?>
			<tr>
				<td colspan="3" align="right">
					<div class="column" align="right">
						<button class="ui button" onClick="window.location.href='projects.php?id=<?=$projectid?>'; return false;">Cancel</button>
						<input class="ui primary button" type="submit" id="submit" value="Update">
					</div>
				</td>
			</tr>
		</table>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateBIDSMapping ------------------ */
	/* -------------------------------------------- */
	function UpdateBIDSMapping($projectid, $modalities, $experimentids) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim(strtolower($projectid)));
		
		if (isInteger($projectid) || $projectid == "" || $projectid == "null") { }
		else {
			Error("Invalid project ID [$projectid]");
			return;
		}
		//PrintVariable($modalities);
		//PrintVariable($oldnames);
		//PrintVariable($newnames);
		
		foreach ($modalities as $i => $modality) {
			$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
			$experimentid = mysqli_real_escape_string($GLOBALS['linki'], $experimentids[$i]);
			if (($modality != "") && ($oldname != "") && ($newname != "")) {
				
				$sqlstring = "insert ignore into nda_mapping (project_id, protocolname, modality, experiment_id) values ($projectid, '$oldname', '$newname', '$modality')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- EditNDAMapping --------------------- */
	/* -------------------------------------------- */
	function EditNDAMapping($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectid));
		
		if (($projectid == "null") || ($projectid == null) || ($projectid == "")) {
			$projectid = 'null';
		}
			
		/* get all studies, and all series, associated with this project */
		if ($projectid == "null")
			$sqlstring = "select study_id, study_modality from studies where study_modality = 'mr'";
		else
			$sqlstring = "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = $projectid";
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$modality = strtolower($row['study_modality']);
			
			if (IsNiDBModality($modality)) {
				if (($modality != "") && ($studyid != "")) {
					$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' order by series_desc";
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						if ($rowA['series_desc'] != "") {
							$seriesdesc = $rowA['series_desc'];
						}
						elseif ($rowA['series_protocol'] != "") {
							$seriesdesc = $rowA['series_protocol'];
						}
						if ($seriesdesc != "") {
							$seriesdescs[$modality][$seriesdesc]++;
						}
					}
				}
			}
		}

		/* get list of NDA experimentid mappings for this project */
		if ($projectid == "null")
			$sqlstring = "select * from nda_mapping where project_id is null";
		else
			$sqlstring = "select * from nda_mapping where project_id = $projectid";

		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mapping[$row['modality']][$row['protocolname']] = $row['experiment_id'];
		}
		?>
		<br><br>
		<div class="ui text container grid">
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="updatendamapping">
		<input type="hidden" name="id" value="<?=$projectid?>">
		<b>NDA mapping</b>
		<br>
		This mapping is used in exporting of NDA format
		<br><br>
		<table class="ui small celled selectable grey compact table">
			<thead>
				<th>Modality</th>
				<th>Protocol name</th>
				<th>
					NDA experiment_id (integer)
				</th>
			</thead>
		<?
		//PrintVariable($seriesdescs);
		//PrintVariable($mapping);
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			array_multisort(array_keys($serieslist), SORT_NATURAL| SORT_FLAG_CASE, $serieslist);
			foreach ($serieslist as $series => $count) {

				$experiment_id = "";
				$experiment_id = $mapping[$modality][$series];
				?>
				<tr>
					<td><?=strtoupper($modality)?></td>
					<td><tt><?=$series?></tt></td>
					<td>
						<input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>"><input type="hidden" name="protocolname[<?=$i?>]" value="<?=$series?>">
						<div class="ui input">
							<input type="text" name="experimentid[<?=$i?>]" value="<?=$experiment_id?>">
						</div>
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
					<button class="ui button" onClick="window.location.href='projects.php?id=<?=$projectid?>'; return false;">Cancel</button>
					<input class="ui primary button" type="submit" id="submit" value="Update">
				</div>
				</td>
			</tr>
		</table>

		<?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateNDAMapping ------------------- */
	/* -------------------------------------------- */
	function UpdateNDAMapping($projectid, $modalities, $protocolnames, $experimentids) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim(strtolower($projectid)));
		
		if (isInteger($projectid) || $projectid == "" || $projectid == "null") { }
		else {
			Error("Invalid project ID [$projectid]");
			return;
		}
		
		foreach ($modalities as $i => $modality) {
			$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
			$protocolname = mysqli_real_escape_string($GLOBALS['linki'], $protocolnames[$i]);
			$experimentid = mysqli_real_escape_string($GLOBALS['linki'], $experimentids[$i]);
			if (($modality != "") && ($protocolname != "") && ($experimentid != "") && (is_numeric($experimentid)) && ($experimentid > 0)) {
				
				$sqlstring = "insert ignore into nda_mapping (project_id, protocolname, modality, experiment_id) values ($projectid, '$protocolname', '$modality', '$experimentid')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- EditExperimentMapping -------------- */
	/* -------------------------------------------- */
	function EditExperimentMapping($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectid));
		
		if (($projectid == "null") || ($projectid == null) || ($projectid == "")) {
			$projectid = 'null';
		}
			
		/* get all studies, and all series, associated with this project */
		if ($projectid == "null")
			$sqlstring = "select study_id, study_modality from studies where study_modality = 'mr'";
		else
			$sqlstring = "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = $projectid";
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$modality = strtolower($row['study_modality']);
			
			if (IsNiDBModality($modality)) {
				if (($modality != "") && ($studyid != "")) {
					$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' order by series_desc";
					$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						if ($rowA['series_desc'] != "") {
							$seriesdesc = $rowA['series_desc'];
						}
						elseif ($rowA['series_protocol'] != "") {
							$seriesdesc = $rowA['series_protocol'];
						}
						if ($seriesdesc != "") {
							$seriesdescs[$modality][$seriesdesc]++;
						}
					}
				}
			}
		}

		/* get list of NDA experimentid mappings for this project */
		if ($projectid == "null")
			$sqlstring = "select * from experiment_mapping where project_id is null";
		else
			$sqlstring = "select * from experiment_mapping where project_id = $projectid";

		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mapping[$row['modality']][$row['protocolname']] = $row['experiment_id'];
		}
		?>
		<br><br>
		<div class="ui text container grid">
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="updateexperimentmapping">
		<input type="hidden" name="id" value="<?=$projectid?>">
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
		
		/* get list of experiments for this project */
		$sqlstring = "select * from experiments where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$experiments[$row['experiment_id']] = $row['exp_name'];
		}
		
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			array_multisort(array_keys($serieslist), SORT_NATURAL| SORT_FLAG_CASE, $serieslist);
			foreach ($serieslist as $series => $count) {

				$experiment_id = "";
				$experiment_id = $mapping[$modality][$series];
		
				?>
				<tr>
					<td><?=strtoupper($modality)?></td>
					<td><tt><?=$series?></tt></td>
					<td>
						<input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>"><input type="hidden" name="protocolname[<?=$i?>]" value="<?=$series?>">
						<!--<input type="text" name="experimentid[<?=$i?>]" value="<?=$experiment_id?>">-->
						<select class="ui selection dropdown" name="experimentid[<?=$i?>]">
							<option value="">Select experiment...</option>
						<?
							foreach ($experiments as $expid => $name) {
								if ($expid == $experiment_id) { $selected = "selected"; } else { $selected = ""; }
								echo "<option value='$expid' $selected>$name</option>";
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
					<button class="ui button" onClick="window.location.href='projects.php?id=<?=$projectid?>'; return false;">Cancel</button>
					<input class="ui primary button" type="submit" id="submit" value="Update">
				</div>
				</td>
			</tr>
		</table>

		<?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateExperimentMapping ------------ */
	/* -------------------------------------------- */
	function UpdateExperimentMapping($projectid, $modalities, $protocolnames, $experimentids) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim(strtolower($projectid)));
		
		if (isInteger($projectid) || $projectid == "" || $projectid == "null") { }
		else {
			Error("Invalid project ID [$projectid]");
			return;
		}
		
		foreach ($modalities as $i => $modality) {
			$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);
			$protocolname = mysqli_real_escape_string($GLOBALS['linki'], $protocolnames[$i]);
			$experimentid = mysqli_real_escape_string($GLOBALS['linki'], $experimentids[$i]);
			if (($modality != "") && ($protocolname != "") && ($experimentid != "") && (is_numeric($experimentid)) && ($experimentid > 0)) {
				
				$sqlstring = "insert ignore into experiment_mapping (project_id, protocolname, modality, experiment_id) values ($projectid, '$protocolname', '$modality', '$experimentid')";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayProject --------------------- */
	/* -------------------------------------------- */
	function DisplayProject($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);

		/* update the mostrecent table */
		UpdateMostRecent('', '', $id);
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		$admin = $row['project_admin'];
		$pi = $row['project_pi'];
		$costcenter = $row['project_costcenter'];
		$sharing = $row['project_sharing'];
		$startdate = $row['project_startdate'];
		$enddate = $row['project_enddate'];

		/* get user_project info */
		$sqlstringA = "select * from user_project where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
		$viewdata = $rowA['view_data'];
		$viewphi = $rowA['view_phi'];
		$lastview = $rowA['lastview_cleardate'];
		if (($lastview == "") || ($lastview == "null"))
			$lastview = "0000-00-00 00:00:00";
		$favorite = $rowA['favorite'];
	
		/* get studies associated with this project */
		$sqlstring = "select a.*, c.*, d.*,(datediff(a.study_datetime, d.birthdate)/365.25) 'age' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by d.uid asc, a.study_modality asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		
		/* get some stats about the project */
		$siteids = array();
		$ages = array();
		$studydates = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$siteids[] = $row['study_nidbsite'];
			$uids[$uid]['sex'] = $row['gender']; /* create hash of UID and sex */
			$studydates[] = $row['study_datetime']; /* get list of study dates */
			$genders[$row['gender']]['count']++; /* get the count of each gender */
			if ($row['study_ageatscan'] > 0) {
				$ages[] = $row['study_ageatscan'];
				$genders[$row['gender']]['ages'][] = $row['study_ageatscan'];
			}
			else {
				$ages[] = $row['age'];
				$genders[$row['gender']]['ages'][] = $row['age'];
			}
			$subjects[$uid] = "1";
		}

		$sqlstring = "select count(*) 'numsubjects' from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numsubjects = $row['numsubjects'];
		
		if (count($studydates) > 0) {
			$lowdate = min($studydates);
			$highdate = max($studydates);
		}
		else {
			$lowdate = "";
			$highdate = "";
		}
		
		/* get instance ID */
		$sqlstring = "select instance_id from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instanceid = $row['instance_id'];

		/* get list of site IDs */
		$siteids = array_unique($siteids);
		
		?>
		
		<div class="ui container">
			<div class="ui grid">
				<div class="six wide column">
					<h1 class="ui header">
						<?=$name?>
						<? if ($favorite) { ?>
						<a href="projects.php?action=unsetfavorite&id=<?=$id?>"><i class="yellow star icon" title="Click to remove this project from your favorites"></i></a>
						<? } else { ?>
						<a href="projects.php?action=setfavorite&id=<?=$id?>"><i class="grey star outline icon" title="Click to add this project to your favorites"></i></a><br>
						<? } ?>
						<div class="sub header"><?=$numsubjects?> subjects &nbsp; &nbsp; <?=$numstudies?> studies</div>
					</h1>
				</div>
				<div class="ten wide column">
					<a class="ui green button" href="projects.php?action=editsubjects&id=<?=$id?>">
						<i class="user friends icon"></i> Subjects
					</a>
					<a class="ui green button" href="projects.php?action=displaystudies&id=<?=$id?>">
						<i class="project diagram icon"></i> Studies
					</a>
				</div>
			</div>
			
			<?
				/* get list of studies created since project.lastview */
				$sqlstring = "select *, year(c.birthdate) 'dobyear' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and a.lastupdate > '$lastview'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$numnewstudies = mysqli_num_rows($result);

				DisplayCompactSubjectsTable($id);
			?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayInstanceSummary ------------- */
	/* -------------------------------------------- */
	function DisplayInstanceSummary($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		/* get all studies associated with this project */
		$sqlstring = "SELECT b.enrollment_id, c.study_id, c.study_modality, c.study_num, c.study_ageatscan, d.uid, d.subject_id, d.birthdate, e.altuid, a.project_name FROM projects a LEFT JOIN enrollment b on a.project_id = b.project_id LEFT JOIN studies c on b.enrollment_id = c.enrollment_id LEFT JOIN subjects d on d.subject_id = b.subject_id LEFT JOIN subject_altuid e on e.subject_id = d.subject_id WHERE a.instance_id = $id and d.isactive = 1 order by a.project_name, e.altuid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQL($sqlstring);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			$subjectid = $row['subject_id'];
			$age = $row['study_ageatscan'];
			$dob = $row['birthdate'];
			$projectname = $row['project_name'];
			$modality = strtolower($row['study_modality']);
			$enrollmentid = $row['enrollment_id'];
			
			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$isprimary = $rowA['isprimary'];
			$altuid = $rowA['altuid'];
			
			if (($modality != "") && ($studyid != "")) {
				/* check for valid modality */
				$sqlstring2 = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($modality) . "_series'";
				$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
				if (mysqli_num_rows($result2) < 1) {
					continue;
				}

				/* get the series */
				$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' and ishidden <> 1";
				//PrintSQL($sqlstringA);
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$seriesaltdesc = $rowA['series_altdesc'];
					if ($seriesaltdesc != "") {
						$seriesdescs[$uid][$modality][$seriesaltdesc]++;
						$uniqueseries[$modality][$seriesaltdesc]++;
						$seriesdescs[$uid]['age'] = $age;
						$seriesdescs[$uid]['dob'] = $dob;
						$seriesdescs[$uid]['altuid'] = $altuid;
						$seriesdescs[$uid]['subjectid'] = $subjectid;
						$seriesdescs[$uid]['project'] = $projectname;
					}
				}
				
				/* get the measures */
				$sqlstringA = "select c.instrument_name, b.measure_name, a.* from measures a left join measurenames b on a.measurename_id = b.measurename_id left join measureinstruments c on a.instrumentname_id = c.measureinstrument_id where a.enrollment_id = '$enrollmentid'";
				//PrintSQL($sqlstringA);
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$measurename = "[" . $rowA['instrument_name'] . "] - " . $rowA['measure_name'];
					if ($rowA['measure_type'] == 's') {	$measurevalue = $rowA['measure_valuestring']; }
					else { $measurevalue = $rowA['measure_valuenum']; }
					if ($seriesaltdesc != "") {
						$seriesdescs[$uid]['measures'][$measurename] = $measurevalue;
						$uniqueseries['measures'][$measurename]++;
					}
				}
			}
		}
		
		?>
		<script>
			$(document).ready(function() 
				{ 
					$("#thetable").tablesorter(); 
				} 
			);		
		</script>
		<table id="thetable" class="tablesorter">
			<thead>
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<?
						foreach ($uniqueseries as $modality => $series) {
							$count = count($series);
							echo "<th colspan='$count'>$modality</th>";
						}
					?>
				</tr>
				<tr>
					<th>UID</th>
					<th>Subject ID</th>
					<th>Age</th>
					<th>DOB</th>
					<th>Project</th>
					<?
						foreach ($uniqueseries as $modality => $series) {
							foreach ($series as $ser => $count) {
								echo "<th>$ser ($count)</th>";
							}
						}
					?>
				</tr>
			</thead>
		<?

		/* sort the list by altuid */
		//function compareByName($a, $b) {
		//	return strcmp($a["altuid"], $b["altuid"]);
		//}
		//usort($seriesdescs, 'compareByName');

		foreach ($seriesdescs as $uid => $modalities) {
			$age = $seriesdescs[$uid]['age'];
			$dob = $seriesdescs[$uid]['dob'];
			$altuid = $seriesdescs[$uid]['altuid'];
			$subjectid = $seriesdescs[$uid]['subjectid'];
			$project = $seriesdescs[$uid]['project'];
			?>
			<tr>
				<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
				<td><?=$altuid?></td>
				<td><?=$age?></td>
				<td><?=$dob?></td>
				<td><?=$project?></td>
				<?
				foreach ($uniqueseries as $modality => $series) {
					foreach ($series as $ser => $count) {
						$localcount = $seriesdescs[$uid][$modality][$ser];
						if ($localcount > 0) { $bgcolor = "#CAFFC4"; } else { $bgcolor = ""; $localcount = "-"; }
						#if ($localcount > 0) { $bgcolor = "green"; } else { $bgcolor = "";}
						?>
							<td style="background-color: <?=$bgcolor?>"><?=$localcount?></td>
						<?
					}
				}
				?>
			</tr><?
		}
		?>
		</table>

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
					DisplayProjectsByPI();
				}
				elseif ($viewtype == "groupbyadmin") {
					DisplayProjectsByAdmin();
				}
				else {
					DisplayProjectList();
				}
			?>
		</div>
		<?
		
	}

	/* -------------------------------------------- */
	/* ------- DisplayProjectList ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectList() {
		
		?>
			
		<!--<p id="msg" style="color: #0A0; text-align: center;">&nbsp;</p>-->

		<table class="ui celled selectable grey table" id="projecttable">
			<thead>
				<th data-sort="string-ins">Name &nbsp; 
					<div class="ui icon input">
						<input id="projectnamefilter" type="text" placeholder="Filter by project name"/>
						<i class="search icon"></i>
					</div>

					<script type="text/javascript">
						function filterTable(event) {
							var filter = event.target.value.toUpperCase();
							var rows = document.querySelector("#projecttable tbody").rows;
							
							for (var i = 0; i < rows.length; i++) {
								var firstCol = rows[i].cells[0].textContent.toUpperCase();
								var secondCol = rows[i].cells[1].textContent.toUpperCase();
								if (firstCol.indexOf(filter) > -1 || secondCol.indexOf(filter) > -1) {
									rows[i].style.display = "";
								} else {
									rows[i].style.display = "none";
								}      
							}
						}

						document.querySelector('#projectnamefilter').addEventListener('keyup', filterTable, false);
					</script>
				</th>
				<th data-sort="string-ins">UID</th>
				<th data-sort="string-ins">Cost Center</th>
				<th data-sort="string-ins">Admin</th>
				<th data-sort="string-ins">PI</th>
				<th data-sort="int">Studies</th>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = '" . $_SESSION['instanceid'] . "' order by a.project_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['project_id'];
						$name = $row['project_name'];
						$adminusername = $row['adminusername'];
						$adminfullname = $row['adminfullname'];
						$piusername = $row['piusername'];
						$pifullname = $row['pifullname'];
						$projectuid = $row['project_uid'];
						$costcenter = $row['project_costcenter'];

						$sqlstringA = "select * from user_project where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
						$view_data = $rowA['view_data'];
						$view_phi = $rowA['view_phi'];
						$favorite = $rowA['favorite'];
						
						if ($view_data) {
							?>
							<tr valign="top">
								<td>
									<b><a href="projects.php?id=<?=$id?>"><?=$name?></b>
									<? if ($favorite) { ?>
									<a href="projects.php?action=unsetfavorite&id=<?=$id?>"><i class="yellow star icon" title="Click to remove this project from your favorites"></i></a>
									<? } else { ?>
									<a href="projects.php?action=setfavorite&id=<?=$id?>"><i class="grey star outline icon" title="Click to add this project to your favorites"></i></a><br>
									<? } ?>
									
								</td>
								<td><?=$projectuid?></td>
								<td><?=$costcenter?></td>
								<td><?=$adminfullname?></td>
								<td><?=$pifullname?></td>
								<?
								$totalstudies = 0;
								$totalsize = 0.0;
								$studydetail = "";
								$sqlstring = "SELECT a.study_modality, b.project_id, count(b.project_id) 'count' FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 group by b.project_id,a.study_modality";
								$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
									$modality = $row2['study_modality'];
									$count = $row2['count'];
									
									$projectModalitySize = 0;
									if (IsNiDBModality($modality)) {
										if ($modality != "") {
											$sqlstring3 = "select sum(series_size) 'modalitysize' from " . strtolower($modality) ."_series where study_id in (SELECT a.study_id FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 and a.study_modality = '$modality')";
											$projectModalitySize = $row3['modalitysize'];
										}
									}
									
									$totalstudies += $count;
									$totalsize += $projectModalitySize;
									
									if ($modality == "") { $modality = "(blank)"; }
									
									$studydetail .= "<li><b>$modality</b> - $count";
								}
								$studydetail = "<ul>$studydetail<ul>";
								?>
								<td align="left" title="<?=$studydetail?>">
									<?=$totalstudies?>
								</td>
							</tr>
							<?
						}
						else {
						?>
							<tr>
								<td style="color: #999; padding-left: 20px">No access to <b><?=$name?></b></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
						<?
						}
					}
				?>
			</tbody>
		</table>
		<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayProjectsByPI ---------------- */
	/* -------------------------------------------- */
	function DisplayProjectsByPI() {
		
		/* get project list, sort by pi */
		$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = '" . $_SESSION['instanceid'] . "' order by a.project_pi, a.project_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$lastprojectpi = "";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['project_id'];
			$name = $row['project_name'];
			$adminusername = $row['adminusername'];
			$adminfullname = $row['adminfullname'];
			$piusername = $row['piusername'];
			$pifullname = $row['pifullname'];
			$projectuid = $row['project_uid'];
			$costcenter = $row['project_costcenter'];
			
			if ($piusername == "")
				$piusername = "(blank)";
			
			/* start a new block for this PI */
			if ($lastprojectpi != $piusername) {
				
				/* terminate the previous block if there was one */
				if ($lastprojectpi != "") {
					?>
						</table>
					</div><?
				}
					
				?>
				<div class="ui styled segment">
					<h2 class="ui header">
						<i class="blue user icon"></i>
						<?=$pifullname?>
						<div class="sub header"><?=$piusername?></div>
					</h2>
					<table class="ui scrolling table">
						<thead>
							<th>Project</th>
							<th>UID</th>
							<th>Cost center</th>
							<th>Admin</th>
							<th>Studies</th>
						</thead>
				<?
			}
			$lastprojectpi = $piusername;

			/* display the project list for this PI */
			$sqlstringA = "select * from user_project where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$view_data = $rowA['view_data'];
			$view_phi = $rowA['view_phi'];
			$favorite = $rowA['favorite'];
			
			if ($view_data) {
				?>
				<tr valign="top">
					<td>
						<b><a href="projects.php?id=<?=$id?>"><?=$name?></b>
						<? if ($favorite) { ?>
						<a href="projects.php?action=unsetfavorite&id=<?=$id?>"><i class="yellow star icon" title="Click to remove this project from your favorites"></i></a>
						<? } else { ?>
						<a href="projects.php?action=setfavorite&id=<?=$id?>"><i class="grey star outline icon" title="Click to add this project to your favorites"></i></a><br>
						<? } ?>
					</td>
					<td><?=$projectuid?></td>
					<td><?=$costcenter?></td>
					<td><?=$adminfullname?></td>
					<?
					$totalstudies = 0;
					$totalsize = 0.0;
					$studydetail = "";
					$sqlstring = "SELECT a.study_modality, b.project_id, count(b.project_id) 'count' FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 group by b.project_id,a.study_modality";
					$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
						$modality = $row2['study_modality'];
						$count = $row2['count'];
						
						$projectModalitySize = 0;
						if (IsNiDBModality($modality)) {
							if ($modality != "") {
								$sqlstring3 = "select sum(series_size) 'modalitysize' from " . strtolower($modality) ."_series where study_id in (SELECT a.study_id FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 and a.study_modality = '$modality')";
								$projectModalitySize = $row3['modalitysize'];
							}
						}
						
						$totalstudies += $count;
						$totalsize += $projectModalitySize;
						
						if ($modality == "") { $modality = "(blank)"; }
						
						$studydetail .= "<li><b>$modality</b> - $count";
					}
					$studydetail = "<ul>$studydetail<ul>";
					?>
					<td align="left" title="<?=$studydetail?>">
						<?=$totalstudies?>
					</td>
				</tr>
				<?
			}
			else {
			?>
				<tr>
					<td colspan="5">No access to <b><?=$name?></b></td>
				</tr>
			<?
			}
		}
		if ($lastprojectpi != "") {
			?>
				</table>
			</div>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectsByAdmin ------------- */
	/* -------------------------------------------- */
	function DisplayProjectsByAdmin() {
		
		/* get project list, sort by pi */
		$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = '" . $_SESSION['instanceid'] . "' order by a.project_admin, a.project_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$lastprojectadmin = "";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$id = $row['project_id'];
			$name = $row['project_name'];
			$adminusername = $row['adminusername'];
			$adminfullname = $row['adminfullname'];
			$piusername = $row['piusername'];
			$pifullname = $row['pifullname'];
			$projectuid = $row['project_uid'];
			$costcenter = $row['project_costcenter'];

			if ($adminusername == "")
				$adminusername = "(blank)";
			
			/* start a new block for this PI */
			if ($lastprojectadmin != $adminusername) {
				
				/* terminate the previous block if there was one */
				if ($lastprojectpi != "") {
					?></table>
					</div><?
				}
					
				?>
				<div class="ui styled segment">
					<h2 class="ui header">
						<i class="blue user icon"></i>
						<?=$adminfullname?>
						<div class="sub header"><?=$adminusername?></div>
					</h2>
					<table class="ui scrolling table">
						<thead>
							<th>Project</th>
							<th>UID</th>
							<th>Cost center</th>
							<th>PI</th>
							<th>Studies</th>
						</thead>
				<?
			}
			$lastprojectadmin = $adminusername;

			/* display the project list for this PI */
			$sqlstringA = "select * from user_project where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$view_data = $rowA['view_data'];
			$view_phi = $rowA['view_phi'];
			$favorite = $rowA['favorite'];
			
			if ($view_data) {
				?>
				<tr valign="top">
					<td>
						<b><a href="projects.php?id=<?=$id?>"><?=$name?></b>
						<? if ($favorite) { ?>
						<a href="projects.php?action=unsetfavorite&id=<?=$id?>"><i class="yellow star icon" title="Click to remove this project from your favorites"></i></a>
						<? } else { ?>
						<a href="projects.php?action=setfavorite&id=<?=$id?>"><i class="grey star outline icon" title="Click to add this project to your favorites"></i></a><br>
						<? } ?>
					</td>
					<td><?=$projectuid?></td>
					<td><?=$costcenter?></td>
					<td><?=$pifullname?></td>
					<?
					$totalstudies = 0;
					$totalsize = 0.0;
					$studydetail = "";
					$sqlstring = "SELECT a.study_modality, b.project_id, count(b.project_id) 'count' FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 group by b.project_id,a.study_modality";
					$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
						$modality = $row2['study_modality'];
						$count = $row2['count'];
						
						$projectModalitySize = 0;
						if (IsNiDBModality($modality)) {
							if ($modality != "") {
								$sqlstring3 = "select sum(series_size) 'modalitysize' from " . strtolower($modality) ."_series where study_id in (SELECT a.study_id FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 and a.study_modality = '$modality')";
								$projectModalitySize = $row3['modalitysize'];
							}
						}
						
						$totalstudies += $count;
						$totalsize += $projectModalitySize;
						
						if ($modality == "") { $modality = "(blank)"; }
						
						$studydetail .= "<li><b>$modality</b> - $count";
					}
					$studydetail = "<ul>$studydetail<ul>";
					?>
					<td align="left" title="<?=$studydetail?>">
						<?=$totalstudies?>
					</td>
				</tr>
				<?
			}
			else {
			?>
				<tr>
					<td colspan="5">No access to <b><?=$name?></b></td>
				</tr>
			<?
			}
		}
		if ($lastprojectadmin != "") {
			?></table></div><?
		}
	}


	/* -------------------------------------------- */
    /* ------- DisplayRDoCList -------------------- */
    /* -------------------------------------------- */
    function DisplayRDoCList($rdoc_label) {
		$subject = "Subject";
		$series = "Series";
		?>	

		<table style="width:70%">
		  <tr>
			<th>Subject</th>
			<th>Series</th> 
		  </tr>
		<?
			$sqlstring = "SELECT label FROM `rdoc_uploads` WHERE label = '$rdoc_label'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
						$series = $row2['label'];
							$subject = $row2['label'];
		?>
				  <tr>	
					<td><?=$subject?></td>
					<td><?=$series?></td> 
				  </tr>
		<?
		}
		?>
		</table>
		<?
	}
	
?>

<br><br><br><br>

<? include("footer.php") ?>
