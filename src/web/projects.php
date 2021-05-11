
<?
 // ------------------------------------------------------------------------------
 // NiDB projects.php
 // Copyright (C) 2004 - 2021
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
	
	$rdoc_label = GetVariable("rdoc_label");
	$itemprotocol = GetVariable("itemprotocol");

	/* determine action */
	switch ($action) {
		case 'displaystudies':
			DisplayStudiesTable($id);
			break;
		case 'auditstudies':
			AuditStudies($id);
			break;
		case 'changeproject':
			ChangeProject($newprojectid, $studyids);
			DisplayStudiesTable($id);
			break;
		case 'editbidsdatatypes':
			EditBIDSDatatypes($id);
			break;
		case 'viewbidsdatatypes':
			ViewBIDSDatatypes($id);
			break;
		//case 'saveprotocolmapping':
		//	SaveProtocolMapping($id);
		//	break;
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
		case 'displayprojectinfo':
			DisplayProjectInfo($id);
			break;
		case 'editsubjects':
			DisplayEditSubjectsTable($id);
			break;
		case 'displaysubjects':
			DisplaySubjects($id,1);
			break;
		case 'updatedemographics':
			UpdateDemographics($id,$subjectids,$altuids,$guids,$birthdates,$genders,$ethnicity1s,$ethnicity2s,$educations,$maritalstatus,$smokingstatus,$enrollgroups);
			DisplaySubjects($id,1);
			break;
		case 'displaydeletedsubjects':
			DisplaySubjects($id,0);
			break;
		case 'updatesubjecttable':
			UpdateSubjectTable($id,$subjecttable);
			DisplayEditSubjectsTable($id);
			break;
		case 'updatestudytable':
			UpdateStudyTable($id,$studytable);
			DisplayStudiesTable($id);
			break;
		case 'applytags':
			ApplyTags($id, $studyids, $tags);
			DisplayStudiesTable($id);
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
			DisplayProjectList();
			break;
		case 'obliteratestudy':
			ObliterateStudy($studyids);
			DisplayProjectList();
			break;
		case 'rearchivestudies':
			RearchiveStudies($studyids, $matchidonly);
			DisplayProjectList();
			break;
		case 'rearchivesubjects':
			RearchiveSubjects($studyids, $matchidonly);
			DisplayProjectList();
			break;
		case 'resetqa':
			ResetProjectQA($id);
			DisplayStudiesTable($id);
			break;
		case 'show_rdoc_list':
			DisplayRDoCList($rdoc_label);
			break;
		case 'assessmentinfo':
			DisplayFormList($id);
			break;
		default:
			if ($id == '') {
				DisplayProjectList();
			}
			else {
				DisplayProjectInfo($id);
			}
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- UpdateDemographics ----------------- */
	/* -------------------------------------------- */
	function UpdateDemographics($id,$subjectids,$altuids,$guids,$birthdates,$genders,$ethnicity1s,$ethnicity2s,$educations,$maritalstatus,$smokingstatus,$enrollgroups) {
		
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		$subjectids = mysqli_real_escape_array($subjectids);
		$altuids = mysqli_real_escape_array($altuids);
		$guids = mysqli_real_escape_array($guids);
		$birthdates = mysqli_real_escape_array($birthdates);
		$genders = mysqli_real_escape_array($genders);
		$ethnicity1s = mysqli_real_escape_array($ethnicity1s);
		$ethnicity2s = mysqli_real_escape_array($ethnicity2s);
		$educations = mysqli_real_escape_array($educations);
		$maritalstatus = mysqli_real_escape_array($maritalstatus);
		$smokingstatus = mysqli_real_escape_array($smokingstatus);
		$enrollgroups = mysqli_real_escape_array($enrollgroups);
		
		/* check to see if each array has the same number of elements */
		if (count($subjectids) != count($altuids)) { echo "Error in number of items received"; return; }
		if (count($altuids) != count($guids)) { echo "Error in number of items received"; return; }
		if (count($guids) != count($birthdates)) { echo "Error in number of items received"; return; }
		if (count($birthdates) != count($genders)) { echo "Error in number of items received"; return; }
		if (count($genders) != count($ethnicity1s)) { echo "Error in number of items received"; return; }
		if (count($ethnicity1s) != count($ethnicity2s)) { echo "Error in number of items received"; return; }
		if (count($ethnicity2s) != count($educations)) { echo "Error in number of items received"; return; }
		if (count($educations) != count($maritalstatus)) { echo "Error in number of items received"; return; }
		if (count($maritalstatus) != count($smokingstatus)) { echo "Error in number of items received"; return; }
		if (count($smokingstatus) != count($enrollgroups)) { echo "Error in number of items received"; return; }
	
		
		for ($i=0;$i<count($subjectids);$i++) {
			$subjectid = $subjectids[$i];
			$altuid = $altuids[$i];
			$guid = $guids[$i];
			$birthdate = $birthdates[$i];
			$gender = $genders[$i];
			$ethnicity1 = $ethnicity1s[$i];
			$ethnicity2 = $ethnicity2s[$i];
			$education = $educations[$i];
			$marital = $maritalstatus[$i];
			$smoking = $smokingstatus[$i];
			$enrollgroup = $enrollgroups[$i];
			
			/* only do updates if its a valid subjectid */
			if (isInteger($subjectid)) {
				$sqlstring = "update subjects set guid = '$guid', birthdate = '$birthdate', gender = '$gender', ethnicity1 = '$ethnicity1', ethnicity2 = '$ethnicity2', education = '$education', marital_status = '$marital', smoking_status = '$smoking' where subject_id = $subjectid";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				
				$sqlstring = "update enrollment set enroll_subgroup = '$enrollgroup' where subject_id = $subjectid and project_id = $id";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				
				$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectid and project_id = $id";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0){
					$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
					$enrollmentid = $row['enrollment_id'];
				}
				else {
					continue;
				}
				
				/* now update the alternate IDs */
				/* ... first delete entries for this subject from the altuid table ... */
				$sqlstring = "delete from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				/* ... and insert the new rows into the altuids table */
				$altuidlist = explode(',',$altuid);
				foreach ($altuidlist as $altid) {
					$altid = trim($altid);
					if (strpos($altid, '*') !== FALSE) {
						$altid = str_replace('*','',$altid);
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altid',1, '$enrollmentid')";
					}
					else {
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altid',0, '$enrollmentid')";
					}
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
	}

	
	/* -------------------------------------------- */
	/* ------- ApplyTags -------------------------- */
	/* -------------------------------------------- */
	function ApplyTags($id, $studyids, $tags) {
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		$studyids = mysqli_real_escape_array($studyids);
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
	/* ------- UpdateSubjectTable ----------------- */
	/* -------------------------------------------- */
	function UpdateSubjectTable($id,$subjecttable) {
		
		StartSQLTransaction();
		
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$numRowsUpdated = 0;
		//PrintVariable($studytable);
		$csv = explode("\n",$subjecttable);
		array_shift($csv); /* remove headers from csv */
		foreach ($csv as $line) {
			/* only accept valid lines with the correct # of columns */
			if (trim($line) != '') {
				$parts = str_getcsv($line);
				//PrintVariable($parts,'Parts');
				$numparts = count($parts);
				if ($numparts == 15) {
					//PrintVariable($parts, 'Parts');
					$subjectid = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[0]));
					$uid = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[1]));
					$primaryid = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[2]));
					$altuidlist = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[3]));
					$guid = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[4]));
					$dob = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[5]));
					$sex = mysqli_real_escape_string($GLOBALS['linki'],strtoupper(trim($parts[6])));
					$ethnicity1 = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[7]));
					$ethnicity2 = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[8]));
					$education = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[9]));
					$handedness = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[10]));
					$marital = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[11]));
					$smoking = mysqli_real_escape_string($GLOBALS['linki'],strtolower(trim($parts[12])));
					$enrollgroup = mysqli_real_escape_string($GLOBALS['linki'],trim($parts[13]));
					
					/* validate the IDs */
					if (!ctype_digit(strval($subjectid))) { echo "SubjectID [$subjectid] is not an integer<br>"; continue; }
					
					/* valdiate and build the SQL update statement */
					$sqlupdates = array();
					if ($guid != "") { $sqlupdates[] = "guid = '$guid'"; }
					if (($dob != "") && (strtotime($dob) != false)) { $sqlupdates[] = "birthdate = '$dob'"; }
					if (in_array($sex,array('F','M','O','U','T'))) { $sqlupdates[] = "gender = '$sex'"; }
					switch(strtolower($ethnicity1)) {
						case 'hispanic':
							$sqlupdates[] = "ethnicity1 = 'hispanic'"; break;
						case 'nothispanic':
						case 'not hispanic':
							$sqlupdates[] = "ethnicity1 = 'nothispanic'"; break;
					}
					switch(strtolower($ethnicity2)) {
						case 'indian':
						case 'american indian/alaskan native':
							$sqlupdates[] = "ethnicity2 = 'indian'"; break;
						case 'asian':
							$sqlupdates[] = "ethnicity2 = 'asian'"; break;
						case 'black':
						case 'black/african american':
							$sqlupdates[] = "ethnicity2 = 'black'"; break;
						case 'islander':
						case 'hawaiian/pacific islander':
							$sqlupdates[] = "ethnicity2 = 'asian'"; break;
						case 'white':
							$sqlupdates[] = "ethnicity2 = 'white'"; break;
					}
					switch(strtolower($education)) {
						case '0':
						case 'unknown':
							$sqlupdates[] = "education = '0'"; break;
						case '1':
						case 'grade school':
							$sqlupdates[] = "education = '1'"; break;
						case '2':
						case 'middle school':
							$sqlupdates[] = "education = '2'"; break;
						case '3':
						case 'high school/ged':
							$sqlupdates[] = "education = '3'"; break;
						case '4':
						case 'trade school':
							$sqlupdates[] = "education = '4'"; break;
						case '5':
						case 'associates degree':
							$sqlupdates[] = "education = '5'"; break;
						case '6':
						case 'bachelors degree':
							$sqlupdates[] = "education = '6'"; break;
						case '7':
						case 'masters degree':
							$sqlupdates[] = "education = '7'"; break;
						case '8':
						case 'doctoral degree':
							$sqlupdates[] = "education = '8'"; break;
					}
					switch(strtolower($handedness)) {
						case 'r':
						case 'right':
							$sqlupdates[] = "handedness = 'R'"; break;
						case 'l':
						case 'left':
							$sqlupdates[] = "handedness = 'L'"; break;
						case 'a':
						case 'ambidextrous':
							$sqlupdates[] = "handedness = 'A'"; break;
						case 'u':
						case 'unknown':
							$sqlupdates[] = "handedness = 'U'"; break;
					}
					switch(strtolower($marital)) {
						case 'unknown':
							$sqlupdates[] = "marital_status = 'unknown'"; break;
						case 'single':
							$sqlupdates[] = "marital_status = 'single'"; break;
						case 'married':
							$sqlupdates[] = "marital_status = 'married'"; break;
						case 'divorced':
							$sqlupdates[] = "marital_status = 'divorced'"; break;
						case 'separated':
							$sqlupdates[] = "marital_status = 'separated'"; break;
						case 'civilunion':
						case 'civil union':
							$sqlupdates[] = "marital_status = 'civilunion'"; break;
						case 'cohabitating':
							$sqlupdates[] = "marital_status = 'cohabitating'"; break;
						case 'widowed':
							$sqlupdates[] = "marital_status = 'widowed'"; break;
					}
					if ($smoking != "") {
						$sqlupdates[] = "smoking_status = '$smoking'";
					}
					
					$sqlstring = "update subjects set " . implode(", ",$sqlupdates) . " where subject_id = '$subjectid'";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					
					/* ----- update the alternate UIDs ----- */
					/* get the enrollmentid */
					$sqlstring = "select enrollment_id from enrollment where project_id = '$id' and subject_id = $subjectid";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
					$enrollmentid = $row['enrollment_id'];
					
					/* if there are no alternate IDs, skip this step */
					if (($altuidlist == '') || ($altuidlist == '*')) { continue; }
					
					/* delete entries for this subject from the altuid table ... */
					$sqlstring = "delete from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					/* ... and insert the new rows into the altuids table */
					$altuids = explode(',',$altuidlist);
					foreach ($altuids as $altuid) {
						$altuid = trim($altuid);
						if ($altuid == '') { continue; }
						//echo "enrollmentID [$enrollmentid] - altuid [$altuid]<br>";
						if (strpos($altuid, '*') !== FALSE) {
							$altuid = str_replace('*','',$altuid);
							$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',1, '$enrollmentid')";
							if ($altuid == '') { continue; }
						}
						else {
							$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',0, '$enrollmentid')";
						}
						//PrintSQL($sqlstring);
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						//echo "<span class='tiny'>" . mysqli_affected_rows() . " rows updated</span>";
						$numRowsUpdated += mysqli_affected_rows();
					}
					
					$sqlstring = "update enrollment set enroll_subgroup = '$enrollgroup' where enrollment_id = $enrollmentid";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		CommitSQLTransaction();
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyTable ------------------- */
	/* -------------------------------------------- */
	function UpdateStudyTable($id,$studytable) {
		
		StartSQLTransaction();
		
		/* prepare the fields for SQL */
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$numRowsUpdated = 0;
		
		$noticemsg = "";
		$errormsg = "";
		//PrintVariable($studytable);
		$csv = explode("\n",$studytable);
		array_shift($csv); /* remove headers from csv */
		foreach ($csv as $line) {
			/* only accept valid lines with the correct # of columns */
			if (trim($line) != '') {
				$parts = mysqli_real_escape_array(str_getcsv($line));
				//PrintVariable($parts,'Parts');
				$numparts = count($parts);
				if (($numparts == 17) || ($numparts == 16)) {
					//PrintVariable($parts, 'Parts');
					
					if ($numparts == 17) {
						$studyid = trim($parts[0]);
						$subjectid = trim($parts[1]);
						$uid = trim($parts[2]);
						$sex = strtoupper(trim($parts[3]));
						$altuidlist = trim($parts[4]);
						$visit = trim($parts[6]);
						$ageatscan = trim($parts[9]);
						$site = trim($parts[15]);
					}
					else {
						$studyid = trim($parts[0]);
						$subjectid = trim($parts[1]);
						$uid = trim($parts[2]);
						$sex = strtoupper(trim($parts[3]));
						$altuidlist = trim($parts[4]);
						$visit = trim($parts[6]);
						$ageatscan = trim($parts[9]);
						$site = trim($parts[14]);
					}
					
					/* validate each variable before trying the SQL */
					if (!ctype_digit(strval($studyid))) { $noticemsg .= "StudyID [$studyid] is not an integer<br>"; continue; }
					if (!ctype_digit(strval($subjectid))) { $noticemsg .= "SubjectID [$subjectid] is not an integer<br>"; continue; }
					
					if ($ageatscan != "") {
						if (is_numeric($ageatscan)) {
							$sqlstring = "update studies set study_ageatscan = '$ageatscan', study_type = '$visit', study_site = '$site' where study_id = '$studyid'";
							//PrintSQL($sqlstring);
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							$numRowsUpdated += mysqli_affected_rows();
						}
						else {
							$noticemsg .= "Age-at-scan [$ageatscan] is not a number<br>";
						}
					}
					
					/* update the sex, if its valid */
					if (($sex == 'F') || ($sex == 'M') || ($sex == 'O') || ($sex == 'U')) {
						$sqlstring = "update subjects set gender = '$sex' where subject_id = '$subjectid'";
						//PrintSQL($sqlstring);
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						$numRowsUpdated += mysqli_affected_rows();
					}
					
					/* update the alternate UIDs */
					/* get the enrollmentid */
					$sqlstring = "select enrollment_id from studies where study_id = '$studyid'";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
					$enrollmentid = $row['enrollment_id'];
					
					/* if there are no alternate IDs, skip this step */
					if (($altuidlist == '') || ($altuidlist == '*')) { continue; }
					
					/* delete entries for this subject from the altuid table ... */
					$sqlstring = "delete from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					/* ... and insert the new rows into the altuids table */
					$altuids = explode(',',$altuidlist);
					foreach ($altuids as $altuid) {
						$altuid = trim($altuid);
						if ($altuid == '') { continue; }
						//echo "enrollmentID [$enrollmentid] - altuid [$altuid]<br>";
						if (strpos($altuid, '*') !== FALSE) {
							$altuid = str_replace('*','',$altuid);
							$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',1, '$enrollmentid')";
							if ($altuid == '') { continue; }
						}
						else {
							$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',0, '$enrollmentid')";
						}
						//PrintSQL($sqlstring);
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						$numRowsUpdated += mysqli_affected_rows();
					}
				}
				else {
					$errormsg .= "Expecting 16 or 17 columns, but received $numcols. Ignoring this line.";
				}
			}
		}
		CommitSQLTransaction();
		
		if ($errormsg != "")
			Error($errormsg);

		if ($noticemsg != "")
			Notice($noticemsg);
		else
			Notice("Study details updated");
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
		$studyids = mysqli_real_escape_array($studyids);
		
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
		$studyids = mysqli_real_escape_array($studyids);
		
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
		$studyids = mysqli_real_escape_array($studyids);
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
		$studyids = mysqli_real_escape_array($studyids);
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
						$numupdates = mysqli_affected_rows();
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
	/* ------- DisplayStudiesTable ---------------- */
	/* -------------------------------------------- */
	function DisplayStudiesTable($id) {
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
		<script type='text/javascript' src='scripts/x/x.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xgetelementbyid.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xtableiterate.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xpreventdefault.js'></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid.js"></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_renderers.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_editors.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_validators.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_utils.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_charts.js" ></script>
		<link rel="stylesheet" href="scripts/editablegrid/editablegrid.css" type="text/css" media="screen">
		<style>
			table.testgrid { border-collapse: collapse; border: 1px solid #CCB; width: 100%; }
			table.testgrid td, table.testgrid th { padding: 5px; }
			table.testgrid th { background: #E5E5E5; text-align: left; }
			input.invalid { background: red; color: #FDFDFD; }
			td .editable { background-color: lightyellow; }
		</style>
		<?		
		/* display studies associated with this project */
		$sqlstring = "select a.*, c.*, d.* from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by d.uid asc, a.study_modality asc limit 5000";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		
		/* get some stats about the project */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			
			$uids[$uid]['sex'] = $row['gender']; /* create hash of UID and sex */
			$studydates[] = $row['study_datetime']; /* get list of study dates */
			$genders[$row['gender']]['count']++; /* get the count of each gender */
			
			list($studyAge, $calcStudyAge) = GetStudyAge($row['birthdate'], $row['study_ageatscan'], $row['study_datetime']);
			
			if ($studyAge != null) {
				$ages[] = $studyAge;
				$genders[$row['gender']]['ages'][] = $studyAge;
			}
			elseif ($calcStudyAge != null) {
				$ages[] = $calcStudyAge;
				$genders[$row['gender']]['ages'][] = $calcStudyAge;
			}
		}
		
		$lowdate = min($studydates);
		$highdate = max($studydates);
		?>
		
		<? if ($GLOBALS['issiteadmin']) { ?>
		<form action="projects.php" method="post" name="theform" id="theform">
		<input type="hidden" name="action" value="changeproject">
		<input type="hidden" name="id" value="<?=$id?>">
		<? } ?>

		<script>
			window.onload = function() {
				editableGrid = new EditableGrid("DemoGridAttach", { sortIconUp: "images/up.png", sortIconDown: "images/down.png", enableSort: false, caption: 'Double-click to edit table'}); 

				// we build and load the metadata in Javascript
				editableGrid.load({ metadata: [
					{ name: "studyid", datatype: "string", editable: false },
					{ name: "subjectid", datatype: "string", editable: false },
					{ name: "subject", datatype: "html", editable: false },
					{ name: "sex", datatype: "html", editable: true },
					{ name: "altuids", datatype: "html", editable: true },
					{ name: "study", datatype: "html", editable: false },
					{ name: "visit", datatype: "string", editable: true },
					{ name: "deleted", datatype: "html", editable: false },
					{ name: "studydate", datatype: "date", editable: false },
					{ name: "ageatscan", datatype: "double", editable: true, precision: 2, thousands_separator: '', decimal_point: '.' },
					{ name: "calcageatscan", datatype: "string", editable: false },
					{ name: "modality", datatype: "string", editable: false },
					{ name: "studydesc", datatype: "string", editable: false },
					{ name: "studyid", datatype: "string", editable: false },
					{ name: "delete", datatype: "html", editable: false },
					{ name: "site", datatype: "string", editable: true }
				]});

				// then we attach to the HTML table and render it
				editableGrid.attachToHTMLTable('table1');
				editableGrid.renderGrid();
			} 
		</script>
		
		<br><br>
		<div align="center">
		<b>This table is editable</b>. Edit the <span style="background-color: lightyellow; border: 1px solid skyblue; padding:5px">Highlighted</span> fields by single-clicking the cell. Use tab to navigate the table, and make sure to <b>hit enter when editing a cell before saving</b>. Click <b>Save</b> when done editing<br>
		<br>
		Displaying <?=$numstudies?> studies
		</div>
		<br>
		<script type="text/javascript">
			function CheckAll(checkbox) {
				var checked_status = checkbox.checked;
				$(".allcheck").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			}
		</script>
		<table class="testgrid ui celled small very compact selectable table" id='table1'>
			<thead>
			<tr>
				<th></th><!-- studyID -->
				<th></th><!-- subjectID -->
				<th>Subject</th>
				<th>Sex <span class="tiny" style="font-weight: normal">F, M, O, U</span></th>
				<th>Alt Subject IDs <span class="tiny">comma separated, * next to primary ID</span></th>
				<th>Study Num</th>
				<th>Visit</th>
				<th>Active?</th>
				<th>Study Date</th>
				<th>StudyAge</th>
				<th>CalcStudyAge</th>
				<th>Modality</th>
				<th>Study Desc</th>
				<th>Study ID</th>
				<? if ($GLOBALS['issiteadmin']) { ?>
				<th><input type="checkbox" id="checkall" onClick="CheckAll(this)"></th>
				<? } ?>
				<th>Site</th>
			</tr>
			</thead>
			<?
			$uid = "";
			$bgcolor = "";
			$i = 1;
			mysqli_data_seek($result,0);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$study_id = $row['study_id'];
				$modality = $row['study_modality'];
				$study_datetime = $row['study_datetime'];
				$study_site = $row['study_site'];
				$study_num = $row['study_num'];
				$study_desc = $row['study_desc'];
				$study_visit = $row['study_type'];
				$study_site = $row['study_site'];
				$study_altid = $row['study_alternateid'];
				$study_ageatscan = $row['study_ageatscan'];
				//$age = $row['age'];
				$sex = $row['gender'];
				$dob = $row['birthdate'];
				$uid = $row['uid'];
				$subjectid = $row['subject_id'];
				$enrollmentid = $row['enrollment_id'];
				$project_name = $row['project_name'];
				$project_costcenter = $row['project_costcenter'];
				$isactive = $row['isactive'];

				list($studyAge, $calcStudyAge) = GetStudyAge($dob, $study_ageatscan, $study_datetime);
				
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
				
				if ($lastuid != $uid) {
					$bgcolor = "";
					//if ($bgcolor == "") {
						//$bgcolor = "background-color: #efefef;";
					//}
					//else {
					//	$bgcolor = "";
					//}
					$rowstyle = "border-top: 2px solid #666; border-bottom: 0px; $bgcolor";
				}
				else {
					$rowstyle = "$bgcolor";
				}

				?>
				<tr id="R<?=$i?>">
					<? if ($lastuid != $uid) { ?>
						<td style="<?=$rowstyle?>; font-size: 6pt;"><?=$study_id?></td>
						<td style="<?=$rowstyle?>; font-size: 6pt;"><?=$subjectid?></td>
						<td style="<?=$rowstyle?>">
							<a class="ui large compact primary button" href="subjects.php?id=<?=$subjectid?>"><span class="tt"><?=$uid;?></span></a>
						</td>
						<td style="<?=$rowstyle?>" class="editable"><?=$sex?></td>
						<td style="<?=$rowstyle?>" class="editable tt"><?=$altuidlist?></td>
						<?
					}
					else {
						?>
						<td style="<?=$rowstyle?>; font-size: 6pt;"><?=$study_id?></td>
						<td style="<?=$rowstyle?>; font-size: 6pt;"><?=$subjectid?></td>
						<td style="<?=$rowstyle?>"></td>
						<td style="<?=$rowstyle?>"></td>
						<td style="<?=$rowstyle?>"></td>
						<?
					}
					?>
					<td style="<?=$rowstyle?>" class="tt">
						<a class="ui basic large compact primary button" href="studies.php?id=<?=$study_id?>"><span class="tt"><?=$uid;?><?=$study_num;?></span></a>
					</td>
					<td style="<?=$rowstyle?>" class="editable"><?=$study_visit?></td>
					<td style="<?=$rowstyle?>"><? if ($isactive) { echo "<i class='check green icon'></i>"; } ?></td>
					<td style="<?=$rowstyle?>"><?=$study_datetime?></td>
					<td style="<?=$rowstyle?>" class="editable"><?=$studyAge?></td>
					<td style="<?=$rowstyle?>"><?=$calcStudyAge?></td>
					<td style="<?=$rowstyle?>"><?=$modality?></td>
					<td style="<?=$rowstyle?>"><?=$study_desc?></td>
					<td style="<?=$rowstyle?>" class="tt"><?=$study_altid?></td>
					<? if ($GLOBALS['issiteadmin']) { ?>
					<td class="allcheck" style="background-color: #FFFF99; border-left: 1px solid #4C4C1F; border-right: 1px solid #4C4C1F;" <?=$rowstyle?>><input type='checkbox' name="studyids[]" value="<?=$study_id?>"></td>
					<? } ?>
					<td style="<?=$rowstyle?>" class="editable"><?=$study_site?></td>
				</tr>
				<?
				$lastuid = $uid;
				$i++;
			}
			?>
		</table>
		<script type="text/javascript" src="scripts/jquery.table2csv.js"></script>
		<script>
			//$(document).ready(function() {
				function ConvertToCSV() {
					$("#table1").table2csv( {
						callback: function (csv) {
							document.getElementById("studytable").value = csv;
							//alert('Hi');
							document.getElementById('savetableform').submit();
						}
					});
				}
			//});
		</script>
		
		<br>

		<table width="100%">
			<tr>
				<? if ($GLOBALS['issiteadmin']) { ?>
				<td style="background-color: #FFFF99; border: 1px solid #4C4C1F; border-radius:5px; padding:8px; font-size: 10pt" width="70%">
					<table cellpadding="5">
						<tr>
							<td colspan="2"><b style="font-size: 14pt">Powerful Tools</b><br>Perform the following operations on the selected studies...<br><br></td>
						</tr>
						<tr>
							<td style="text-align: right; color: #555; font-weight: bold">
								Apply enrollment tag(s)
							</td>
							<td>
								<input type="text" name="tags" id="tags" list="taglist" multiple> <span class="tiny">comma separated</span>
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
								<input type="submit" value="Apply tag(s)" title="Applies the tags to the selected studies" onclick="document.theform.action='projects.php'; document.theform.action.value='applytags'; document.theform.submit()" style="font-size:10pt">
							<td>
						</tr>
						<tr>
							<td style="text-align: right; color: #555; font-weight: bold">
								Move studies to new project
							</td>
							<td>
								<select name="newprojectid" id="newprojectid">
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
								<input type="submit" value="Move Studies" title="Moves the imaging studies from this project to the selected project" onclick="document.theform.action='projects.php'; document.theform.action.value='changeproject'; document.theform.submit()" style="font-size:10pt">
							<td>
						</tr>
						<tr>
							<td style="text-align: right; color: #555; font-weight: bold">
								Re-archive
							</td>
							<td>
								<span title="When re-archiving, only match existing subjects by ID. Do not use the Patient ID, DOB, or Sex fields to match subjects"><input type="checkbox" name="matchidonly" value="1" checked>Match ID only</span>
								&nbsp;&nbsp;
								<input type="submit" value="Re-archive DICOM studies" title="Moves all DICOM files back into the incoming directory to be parsed again. Useful if there was an archiving error and too many subjects are in the wrong place." onclick="document.theform.action='projects.php'; document.theform.action.value='rearchivestudies'; document.theform.submit()" style="color: red; font-size:10pt">
								&nbsp;&nbsp;&nbsp;&nbsp;
								<input type="submit" value="Re-archive Subjects" title="Moves all DICOM files from this SUBJECT into the incoming directory, and deletes the subject" onclick="document.theform.action='projects.php'; document.theform.action.value='rearchivesubjects'; document.theform.submit()" style="color: red; font-size:10pt">
							</td>
						</tr>
						<tr>
							<td style="text-align: right; color: #555; font-weight: bold">
								Obliterate
							</td>
							<td>
								<input type="submit" value="Obliterate Subjects &#128163;" title="Delete the subject permanently" onclick="document.theform.action='projects.php';document.theform.action.value='obliteratesubject'; document.theform.submit()" style="color: red; font-size:10pt"> &nbsp; &nbsp;
								<input type="submit" value="Obliterate Studies &#128163;" title="Delete the studies permanently" onclick="document.theform.action='projects.php';document.theform.action.value='obliteratestudy'; document.theform.submit()" style="color: red; font-size:10pt">
							</td>
						</tr>
					</table>
				</td>
				<? } ?>
			</form>

				<td align="right" valign="top">
					<!-- save the form -->
					<form method="post" action="projects.php" id="savetableform">
					<input type="hidden" name="id" value="<?=$id?>">
					<input type="hidden" name="action" value="updatestudytable">
					<input type="hidden" name="studytable" id="studytable">
					<div align="right"><input class="ui primary button" type="submit" value="Save Studies Table" onMouseDown="ConvertToCSV();" style="font-size: 14pt; font-weight: bold"></div>
					</form>
				</td>
			</tr>
		</table>
		<?
	}

	
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
	/* ------- DisplayEditSubjectsTable ----------- */
	/* -------------------------------------------- */
	function DisplayEditSubjectsTable($id) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		$usecustomid = $row['project_usecustomid'];
		
		# get the autocomplete list for the enrollgroup
		$sqlstringA = "select distinct(enroll_subgroup) from enrollment where enroll_subgroup <> '' order by enroll_subgroup";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
			$enrollgroupautocomplete[] = "'" . str_replace("'","",$rowA['enroll_subgroup']) . "'";
		}
		?>
		<script type='text/javascript' src='scripts/x/x.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xgetelementbyid.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xtableiterate.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xpreventdefault.js'></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid.js"></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_renderers.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_editors.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_validators.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_utils.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/editablegrid_charts.js" ></script>
		<script type='text/javascript' src="scripts/editablegrid/extensions/autocomplete/autocomplete.js" ></script>
		<link rel="stylesheet" href="scripts/editablegrid/editablegrid.css" type="text/css" media="screen">
		<link rel="stylesheet" href="scripts/editablegrid/extensions/autocomplete/autocomplete.css" type="text/css" media="screen">
		<style>
			table.testgrid { border-collapse: collapse; border: 1px solid #CCB; width: 100%; }
			table.testgrid td, table.testgrid th { padding: 5px; }
			table.testgrid th { background: #E5E5E5; text-align: left; }
			input.invalid { background: red; color: #FDFDFD; }
			.editable { font-family: monospace; background-color: lightyellow; border: 1px solid skyblue }
			.deleted { color: #aaa; }
		</style>
		
		<script>
			window.onload = function() {
				editableGrid = new EditableGrid("DemoGridAttach", { sortIconUp: "images/up.png", sortIconDown: "images/down.png", enableSort: true, caption: 'Double-click to edit table'}); 

				// we build and load the metadata in Javascript
				editableGrid.load({ metadata: [
					{ name: "subjectid", datatype: "string", editable: false },
					{ name: "uid", datatype: "string", editable: false },
					{ name: "primaryid", datatype: "string", editable: false },
					{ name: "altuids", datatype: "html", editable: true },
					{ name: "guid", datatype: "string", editable: true },
					{ name: "birthdate", datatype: "string", editable: true },
					{ name: "sex", datatype: "string", editable: true, values: { "": "", "F": "F", "M": "M", "T": "T", "O": "O", "U": "U"} },
					{ name: "race", datatype: "string", editable: true, values: { "": "", "hispanic": "Hispanic", "nothispanic": "Not hispanic" } },
					{ name: "ethnicity", datatype: "string", editable: true, values: {"": "", "indian": "American Indian/Alaskan native", "asian": "Asian", "black": "Black/African American", "islander": "Hawaiian/Pacific Islander", "white": "White" } },
					{ name: "education", datatype: "string", editable: true, values: { "": "", "0":"Unknown", "1":"Grade school", "2":"Middle school", "3":"High school/GED", "4":"Trade school", "5":"Associates degree", "6":"Bachelors degree", "7":"Masters degree", "8":"Doctoral degree" } },
					{ name: "handedness", datatype: "string", editable: true, values: { "": "", "R": "Right", "L": "Left", "A": "Ambidextrous", "U": "Unknown" } },
					{ name: "marital", datatype: "string", editable: true, values: {"":"", "unknown":"Unknown", "single":"Single", "married":"Married", "divorced":"Divorced", "separated":"Separated", "civilunion":"Civil union", "cohabitating":"Cohabitating", "widowed":"Widowed"} },
					{ name: "smoking", datatype: "string", editable: true, values: { "":"", "unknown":"unknown", "never":"never", "past":"past", "current":"current" } },
					{ name: "enrollgroup", datatype: "string", editable: true }
				]});

				// use autocomplete on enrollgroup
				editableGrid.setCellEditor("enrollgroup", new AutocompleteCellEditor({
					suggestions: [<?=implode(",",$enrollgroupautocomplete)?>]
				}));
		
				// then we attach to the HTML table and render it
				editableGrid.attachToHTMLTable('table1');
				editableGrid.renderGrid();
			} 
		</script>
		<b>Options:</b> <a href="projects.php?action=displaydemographics&id=<?=$id?>" style="font-weight: normal">View table</a>
		<?
			/* get all subjects, and their enrollment info, associated with the project */
			$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id and a.isactive = 1 order by a.uid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$i=0;
		?>
		<br><br>
		<div align="center">
		<b style="font-size:16pt">This table is editable &nbsp; &nbsp;</b> Edit the <span style="background-color: lightyellow; border: 1px solid skyblue; padding:5px">Highlighted</span> fields by single-clicking the cell. Use tab to navigate the table, and make sure to <b>hit enter when editing a cell before saving</b>. Click <b>Save</b> when done editing<br>
		<br>
		Displaying <?=mysqli_num_rows($result)?> enrollments
		</div>
		<br>
		<table class="testgrid ui small celled selectable grey very compact table" id='table1'>
			<thead>
				<th></th>
				<th>UID</th>
				<th>Primary ID</th>
				<th>Alt IDs<br><span class="tiny">Comma separated, * next to primary ID</span></th>
				<th>GUID</th>
				<th>Birthdate (YYYY-MM-DD)</th>
				<th>Sex</th>
				<th>Race</th>
				<th>Ethnicity</th>
				<th>Education</th>
				<th>Handedness</th>
				<th>Marital</th>
				<th>Smoking</th>
				<th>Enroll group</th>
			</thead>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];
			$guid = $row['guid'];
			$gender = $row['gender'];
			$active = $row['isactive'];
			$birthdate = $row['birthdate'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$maritalstatus = $row['marital_status'];
			$smokingstatus = $row['smoking_status'];
			$enrollsubgroup = $row['enroll_subgroup'];
			$enrollmentid = $row['enrollment_id'];
			
			$sqlstringA = "select altuid, isprimary from subject_altuid where subject_id = '$subjectid' and altuid <> '' and enrollment_id = '$enrollmentid' order by isprimary desc";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			//PrintSQLTable($resultA);
			$altids = array();
			$primaryaltuid = "";
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$isprimary = $rowA['isprimary'];
				$altid = $rowA['altuid'];
				//echo "[$altid]<br>";
				if ($altid != "") {
					if ($isprimary) {
						$altids[] = "*" . $altid;
					}
					else {
						$altids[] = $altid;
					}
				}
			}
			//echo "$altids<br>";
			$altuidlist = implode2(", ",$altids);
			$primaryaltuid = str_replace('*','',$altids[0]);
			
			if ($active) {
				$deleted = "";
			}
			else {
				$deleted = " (DEL)";
			}
			
			if (($usecustomid == 1) && ($altuidlist == "")) {
				$customidstyle = "border: 1px solid red; background-color: orange";
			}
			else {
				$customidstyle = "";
			}
			?>
			<tr id="R<?=$i?>">
				<td class="tiny"><?=$subjectid?></td>
				<td style="font-weight: bold; font-size:12pt;"><?=$uid?> <?=$deleted?></td>
				<td style="<?=$customidstyle?>"><?=$primaryaltuid?></td>
				<td class="editable"><?=$altuidlist?></td>
				<td class="editable"><?=$guid?></td>
				<td class="editable"><?=$birthdate?></td>
				<td class="editable"><?=$gender?></td>
				<td class="editable"><?=$ethnicity1?></td>
				<td class="editable"><?=$ethnicity2?></td>
				<td class="editable"><?=$education?></td>
				<td class="editable"><?=$handedness?></td>
				<td class="editable"><?=$maritalstatus?></td>
				<td class="editable"><?=$smokingstatus?></td>
				<td class="editable"><?=$enrollsubgroup?></td>
			</tr>
			<?
			$i++;
		}
		?>
		</table>
		<script type="text/javascript" src="scripts/jquery.table2csv.js"></script>
		<script>
			//$(document).ready(function() {
				function ConvertToCSV() {
					$("#table1").table2csv( {
						callback: function (csv) {
							document.getElementById("subjecttable").value = csv;
							//alert('Hi');
							//document.getElementById('savetableform').submit();
						}
					});
				}
			//});
		</script>
		<form method="post" action="projects.php" id="savetableform">
		<input type="hidden" name="id" value="<?=$id?>">
		<input type="hidden" name="action" value="updatesubjecttable">
		<input type="hidden" name="subjecttable" id="subjecttable">
		<div align="right"><input type="submit" value="Save" onMouseDown="ConvertToCSV();"></div>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubjects -------------------- */
	/* -------------------------------------------- */
	function DisplaySubjects($id, $isactive=1) {
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
		
		?>
		<b>Options:</b> <a href="projects.php?action=displaydemographics&id=<?=$id?>" style="font-weight: normal">View table</a>
		<div align="center">
		<br>
		<table class="ui very compact celled grey table">
			<thead>
				<th>UID</th>
				<th>Alt IDs<br><span class="tiny">Comma separated, * next to main ID</span></th>
				<th>GUID</th>
				<th>Birthdate<br><span class="tiny">YYY-MM-DD</span></th>
				<th>Sex<br><span class="tiny">M,F,U,O</span></th>
				<th>Race</th>
				<th>Ethnicity</th>
				<th>Handedness<br><span class="tiny">R,L,A,U</span></th>
				<th>Education</th>
				<th>Marital</th>
				<th>Smoking</th>
				<th>Enroll group</th>
			</thead>
		<?
		/* get all subjects, and their enrollment info, associated with the project */
		$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id and a.isactive = '$isactive' order by a.uid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$uid = $row['uid'];
			$guid = $row['guid'];
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
					$altids[] = "*" . $altid;
				}
				else {
					$altids[] = $altid;
				}
			}
			$altuidlist = implode2(", ",$altids);
			$altids = array();
			
			switch ($ethnicity1) {
				case "": $ethnicity1 = "-"; break;
				case "hispanic": $ethnicity1 = "Hispanic/Latino"; break;
				case "nothispanic": $ethnicity1 = "Not hispanic/Latino"; break;
			}

			switch ($ethnicity2) {
				case "": $ethnicity2 = "-"; break;
				case "indian": $ethnicity2 = "American Indian/Alaska Native"; break;
				case "asian": $ethnicity2 = "Asian"; break;
				case "black": $ethnicity2 = "Black/African American"; break;
				case "islander": $ethnicity2 = "Hawaiian/Pacific Islander"; break;
				case "white": $ethnicity2 = "White"; break;
			}
			
			switch ($handedness) {
				case "": $handedness = "-"; break;
				case "U": $handedness = "Unknown"; break;
				case "R": $handedness = "Right"; break;
				case "L": $handedness = "Left"; break;
				case "A": $handedness = "Ambidextrous"; break;
			}
			
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
			
			?>
			<tr>
				<td style="font-weight: bold; font-size:12pt"><tt><?=$uid?></tt></td>
				<td><?=$altuidlist?></td>
				<td><?=$guid?></td>
				<td><?=$birthdate?></td>
				<td><?=$gender?></td>
				<td><?=$ethnicity1?></td>
				<td><?=$ethnicity2?></td>
				<td><?=$handedness?></td>
				<td><?=$education?></td>
				<td><?=$maritalstatus?></td>
				<td><?=$smokingstatus?></td>
				<td><?=$enrollsubgroup?></td>
			</tr>
			<?
		}
		?>
		</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- EditBIDSDatatypes ---------------- */
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
					<td><input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>"><input type="hidden" name="oldname[<?=$i?>]" value="<?=$series?>"><input type="text" name="newname[<?=$i?>]" value="<?=$shortname?>"></td>
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
			Error("Error", "Invalid project ID [$projectid]");
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
						<input type="text" name="experimentid[<?=$i?>]" value="<?=$experiment_id?>">
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
			Error("Error", "Invalid project ID [$projectid]");
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
	/* ------- DisplayProjectInfo ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectInfo($id) {
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
	
		/* get studies associated with this project */
		$sqlstring = "select a.*, c.*, d.*,(datediff(a.study_datetime, d.birthdate)/365.25) 'age' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by d.uid asc, a.study_modality asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysqli_num_rows($result);
		
		/* get some stats about the project */
		$siteids = array();
		$ages = array();
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
		}
		
		$lowdate = min($studydates);
		$highdate = max($studydates);
		
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
				<div class="ui seven wide column">
					<h2 class="ui top attached inverted header"><?=$name?></h2>
					<table class="ui basic bottom attached compact collapsing celled table">
						<tbody>
							<tr>
								<td class="right aligned"><h4 class="ui header">Subjects</h4></td>
								<td><?=count($uids)?></td>
							</tr>
							<tr>
								<td class="right aligned"><h4 class="ui header">Studies</h4></td>
								<td><?=$numstudies?></td>
							</tr>
							<tr>
								<td class="right aligned top aligned"><h4 class="ui header">Age (years)</h4></td>
								<td>
									<table class="ui small very basic table">
										<? list($n,$min,$max,$mean,$stdev) = arraystats($ages); ?>
										<tr>
											<td align="right" style="padding-right: 10px"><b>All</b> (n=<?=$n?>)</td><td><?=number_format($mean,1)?><span class="tiny">yr</span> &plusmn;<?=number_format($stdev,1)?><span class="tiny">yr</span> (range <?=number_format($min,1)?><span class="tiny">yr</span> to <?=number_format($max,1)?><span class="tiny">yr</span>)</td>
										</tr>
										<?
											foreach ($genders as $sex => $a) {
												list($n,$min,$max,$mean,$stdev) = arraystats($a['ages']);
												?>
												<tr>
													<td align="right" style="padding-right: 10px"><b><?=$sex?></b> (n=<?=$n?>)</td><td><?=number_format($mean,1)?><span class="tiny">yr</span> &plusmn;<?=number_format($stdev,1)?><span class="tiny">yr</span> (range: <?=number_format($min,1)?><span class="tiny">yr</span> to <?=number_format($max,1)?><span class="tiny">yr</span>)</td>
												</tr>
												<?
											}
										?>
									</table>
								</td>
							</tr>
							<tr>
								<td class="right aligned"><h4 class="ui header">Study date range</h4></td>
								<td><?=$lowdate?> to <?=$highdate?></td>
							</tr>
							<tr>
								<td class="right aligned"><h4 class="ui header">Remote connection params</h4></td>
								<td>
									Project ID: <?=$id?><br>
									Instance ID: <?=$instanceid?><br>
									Site IDs: <?=implode2(",",$siteids)?><br>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="ui one wide column">
				</div>
				<div class="ui three wide column">
					<h3 class="ui header">Data Views</h3>
					<br>
					<a class="ui fluid vertical big primary button" href="projects.php?action=editsubjects&id=<?=$id?>"><i class="black users icon"></i> Subjects</a><br>
					<a class="ui fluid vertical big primary button" href="projects.php?action=displaystudies&id=<?=$id?>"><i class="black sitemap icon"></i> Studies</a><br>
					<a class="ui fluid vertical big primary button" href="projectchecklist.php?projectid=<?=$id?>"><i class="black clipboard list icon"></i> Checklist</a><br>
					<a class="ui fluid vertical big primary button" href="mrqcchecklist.php?action=viewqcparams&id=<?=$id?>"><i class="black clipboard list icon"></i> MR scan QC</a>
				</div>
				<div class="ui one wide column">
				</div>
				<div class="ui four wide column">
					<h3 class="ui header">Project options/tools</h3>
					<br>
					<i class="database icon"></i><a href="datadictionary.php?projectid=<?=$id?>"> Data Dictionary</a><br><br>
					<i class="list alternate outline icon"></i><a href="analysisbuilder.php?action=viewanalysissummary&projectid=<?=$id?>"> Analysis Builder</a><br><br>
					<i class="clone outline icon"></i><a href="templates.php?action=displaystudytemplatelist&projectid=<?=$id?>"> Study Templates</a><br><br>
					<!--<i class="clipboard check icon"></i><a href="mrqcchecklist.php?action=editmrparams&id=<?=$id?>"> Edit Scan Criteria</a><br><br>
					<i class="clipboard list icon"></i><a href="mrqcchecklist.php?action=editqcparams&id=<?=$id?>"> Edit QC Criteria</a><br><br>-->
					<i class="tasks icon"></i><a href="projects.php?action=editbidsmapping&id=<?=$id?>"> BIDS Protocol Mapping</a><br><br>
					<i class="tasks icon"></i><a href="projects.php?action=editndamapping&id=<?=$id?>"> NDA Mapping</a><br><br>
					<i class="list ol icon"></i><a href="minipipeline.php?projectid=<?=$id?>"> Behavioral mini-pipelines</a><br><br>
					<i class="cloud download icon"></i><a href="redcapimport.php?action=importsettings&projectid=<?=$id?>"> Redcap settings</a><br><br>
					<i class="cloud download icon"></i><a href="redcaptonidb.php?action=default&projectid=<?=$id?>"> Redcap <i class="right arrow icon"></i> NiDB Transfer</a><br><br>
					<? if ($GLOBALS['isadmin']) { ?>
					<br><i class="sync red icon"></i><a href="projects.php?action=resetqa&id=<?=$id?>" style="color: #FF552A; font-weight:normal">Reset MRI QA</a><br>
					<? } ?>
				</div>
			</div>
		</div>
		<?
	}



# Asim Addtition .........................................................................
	function AssessmentsInfo($id) {
		//DisplayProjectsMenu('assessments', $id);
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
	/* ------- DisplayProjectList ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectList() {
		
		if ($_SESSION['instanceid'] == "") {
			Error("InstanceID is blank. Page may not display properly. Try selecting an NiDB instance from the top left corner of the page.");
		}
		
		?>
		<!--View <a href="projects.php?action=viewinstancesummary&id=<?=$_SESSION['instanceid']?>">instance summary</a>
		<br><br>-->
		<a href="projects.php?action=editbidsmapping&id=null">Edit Global BIDS Protocol Mapping</a><br>
		
		<p id="msg" style="color: #0A0; text-align: center;">&nbsp;</p>
		
		<table class="ui small celled selectable grey compact table" id="projecttable">
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
				<th data-sort="string-ins">RDoC Submission</th>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname', d.label 'label' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id LEFT JOIN rdoc_uploads d ON  d.project_id = a.project_id where a.project_status = 'active' and a.instance_id = '" . $_SESSION['instanceid'] . "' order by a.project_name";
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
						$rdoc_label = $row['label'];

						$sqlstringA = "select * from user_project where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
						$view_data = $rowA['view_data'];
						$view_phi = $rowA['view_phi'];
						
						if ($view_data) {
							?>
							<tr valign="top">
								<td><a href="projects.php?id=<?=$id?>"><?=$name?></td>
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
								<td><a href="projects.php?action=show_rdoc_list&rdoc_label=<?=$rdoc_label?>"><?=$rdoc_label?></td> 
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
