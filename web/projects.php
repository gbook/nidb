<?
 // ------------------------------------------------------------------------------
 // NiDB projects.php
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
		<title>NiDB - Projects</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
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
	
	$param_rowid = GetVariable("param_rowid");
	$param_protocol = GetVariable("param_protocol");
	$param_sequence = GetVariable("param_sequence");
	$param_tr = GetVariable("param_tr");
	$param_te = GetVariable("param_te");
	$param_ti = GetVariable("param_ti");
	$param_flip = GetVariable("param_flip");
	$param_xdim = GetVariable("param_xdim");
	$param_ydim = GetVariable("param_ydim");
	$param_zdim = GetVariable("param_zdim");
	$param_tdim = GetVariable("param_tdim");
	$param_slicethickness = GetVariable("param_slicethickness");
	$param_slicespacing = GetVariable("param_slicespacing");
	$param_bandwidth = GetVariable("param_bandwidth");
	$existingstudy = GetVariable("existingstudy");
	$existingseries = GetVariable("existingseries");

	/* determine action */
	switch ($action) {
		case 'displayproject':
			DisplayProject($id);
			break;
		case 'changeproject':
			ChangeProject($newprojectid, $studyids);
			DisplayProject($id);
			break;
		case 'viewuniqueseries':
			DisplayUniqueSeries($id);
			break;
		case 'viewaltseriessummary':
			DisplayAltSeriesSummary($id);
			break;
		case 'editdemographics':
			DisplayDemographicsEditTable($id);
			break;
		case 'displaydemographics':
			DisplayDemographics($id);
			break;
		case 'updatedemographics':
			UpdateDemographics($id,$subjectids,$altuids,$guids,$birthdates,$genders,$ethnicity1s,$ethnicity2s,$educations,$maritalstatus,$smokingstatus,$enrollgroups);
			DisplayDemographics($id);
			break;
		case 'viewinstancesummary':
			DisplayInstanceSummary($id);
			break;
		case 'changealternatenames':
			ChangeSeriesAlternateNames($id, $modalities, $oldnames, $newnames);
			DisplayUniqueSeries($id);
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
		case 'editmrparams':
			EditMRScanParams($id);
			break;
		case 'updatemrparams':
			UpdateMRParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr, $param_te, $param_ti, $param_flip, $param_xdim, $param_ydim, $param_zdim, $param_tdim, $param_slicethickness, $param_slicespacing, $param_bandwidth);
			EditMRScanParams($id);
			break;
		case 'loadmrparams':
			LoadMRParams($id, $existingstudy, $existingseries);
			EditMRScanParams($id);
			break;
		case 'viewmrparams':
			ViewMRParams($id);
			break;
		case 'resetqa':
			ResetQA($id);
			DisplayProject($id);
			break;
		default:
			DisplayProjectList();
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- UpdateDemographics ----------------- */
	/* -------------------------------------------- */
	function UpdateDemographics($id,$subjectids,$altuids,$guids,$birthdates,$genders,$ethnicity1s,$ethnicity2s,$educations,$maritalstatus,$smokingstatus,$enrollgroups) {
		
		PrintVariable($subjectids);
		/* prepare the fields for SQL */
		$id = mysql_real_escape_string($id);
		$subjectids = mysql_real_escape_array($subjectids);
		$altuids = mysql_real_escape_array($altuids);
		$guids = mysql_real_escape_array($guids);
		$birthdates = mysql_real_escape_array($birthdates);
		$genders = mysql_real_escape_array($genders);
		$ethnicity1s = mysql_real_escape_array($ethnicity1s);
		$ethnicity2s = mysql_real_escape_array($ethnicity2s);
		$educations = mysql_real_escape_array($educations);
		$maritalstatus = mysql_real_escape_array($maritalstatus);
		$smokingstatus = mysql_real_escape_array($smokingstatus);
		$enrollgroups = mysql_real_escape_array($enrollgroups);
		PrintVariable($subjectids);
		
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
	
		echo "I'm here! [" . count($subjectids) . "]";
		
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
			
			echo "Hi! [$i]";
			/* only do updates if its a valid subjectid */
			if (isInteger($subjectid)) {
				$sqlstring = "update subjects set guid = '$guid', birthdate = '$birthdate', gender = '$gender', ethnicity1 = '$ethnicity1', ethnicity2 = '$ethnicity2', education = '$education', marital_status = '$marital', smoking_status = '$smoking' where subject_id = $subjectid";
				PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				
				$sqlstring = "update enrollment set enroll_subgroup = '$enrollgroup' where subject_id = $subjectid and project_id = $id";
				PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				
				$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectid and project_id = $id";
				PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				if (mysql_num_rows($result) > 0){
					$row = mysql_fetch_array($result, MYSQL_ASSOC);
					$enrollmentid = $row['enrollment_id'];
				}
				else {
					continue;
				}
				
				/* now update the alternate IDs */
				/* ... first delete entries for this subject from the altuid table ... */
				$sqlstring = "delete from subject_altuid where subject_id = $subjectid";
				PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
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
					PrintSQL($sqlstring);
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- ChangeProject ---------------------- */
	/* -------------------------------------------- */
	function ChangeProject($projectRowID, $studyids) {
		$projectRowID = mysql_real_escape_string($projectRowID);
	
		foreach ($studyids as $studyRowID) {
			$studyRowID = mysql_real_escape_string($studyRowID);
			
			/* get the subject ID */
			$sqlstring = "select a.subject_id, b.enrollment_id from enrollment a left join studies b on a.enrollment_id = b.enrollment_id where b.study_id = $studyRowID";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			if (mysql_num_rows($result) > 0){
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$subjectRowID = $row['subject_id'];
				$existingEnrollmentRowID = $row['enrollment_id'];
			}
			else {
				echo "This study is not part of an enrollment...<br>";
				continue;
			}
		
			/* check if the subject is enrolled in the project */
			$sqlstring = "select * from enrollment where project_id = $projectRowID and subject_id = $subjectRowID";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			if (mysql_num_rows($result) > 0){
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$enrollmentRowID = $row['enrollment_id'];
				?><span style="color:green">[<?=$subjectRowID?>] is already enrolled in [<?=$projectRowID?>] with enrollment [<?=$enrollmentRowID?>]</span><br><?
			}
			else {
				/* if they're not enrolled, create the enrollment, with the enrollment date of the 'scandate' */
				$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				echo "Creating enrollment [$sqlstring]<br>";
				$enrollmentRowID = mysql_insert_id();
			}
			
			/* check if the study is already associated with the enrollment, and if not, move the study to the enrollment */
			$sqlstring = "select * from studies where enrollment_id = $enrollmentRowID and study_id = $studyRowID";
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			if (mysql_num_rows($result) > 0){
				$row = mysql_fetch_array($result, MYSQL_ASSOC);
				$enrollmentRowID = $row['enrollment_id'];
				?><span style="color:green">Study [<?=$studyRowID?>] is already part of enrollment [<?=$enrollmentRowID?>]</span><br><?
			}
			else {
				/* if the study is not associated with the enrollment, associate it */
				$sqlstring = "update studies set enrollment_id = $enrollmentRowID where study_id = $studyRowID";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				echo "Moved study from enrollment $existingEnrollmentRowID to $enrollmentRowID<br>";
				//exit(0);
			}
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProject --------------------- */
	/* -------------------------------------------- */
	function DisplayProject($id) {
		$id = mysql_real_escape_string($id);
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		$admin = $row['project_admin'];
		$pi = $row['project_pi'];
		$costcenter = $row['project_costcenter'];
		$sharing = $row['project_sharing'];
		$startdate = $row['project_startdate'];
		$enddate = $row['project_enddate'];
	
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		?>
		<script type='text/javascript' src='scripts/x/x.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xgetelementbyid.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xtableiterate.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xpreventdefault.js'></script>
		<script type='text/javascript'>
		window.onload = function()

		/* Click-n-Drag Checkboxes */
		var gCheckedValue = null;
		function initCheckBoxes(sTblId)
		{
		  xTableIterate(sTblId,
			function(td, isRow) {
			  if (!isRow) {
				var cb = td.getElementsByTagName('input');
				if (cb && cb[0].type.toLowerCase() == 'checkbox') {
				  td.checkBoxObj = cb[0];
				  td.onmousedown = tdOnMouseDown;
				  td.onmouseover = tdOnMouseOver;
				  td.onclick = tdOnClick;
				}
			  }
			}
		  );
		}
		function tdOnMouseDown(ev)
		{
		  if (this.checkBoxObj) {
			gCheckedValue = this.checkBoxObj.checked = !this.checkBoxObj.checked;
			document.onmouseup = docOnMouseUp;
			document.onselectstart = docOnSelectStart; // for IE
			xPreventDefault(ev); // cancel text selection
		  }
		}
		function tdOnMouseOver(ev)
		{
		  if (gCheckedValue != null && this.checkBoxObj) {
			this.checkBoxObj.checked = gCheckedValue;
		  }
		}
		function docOnMouseUp()
		{
		  document.onmouseup = null;
		  document.onselectstart = null;
		  gCheckedValue = null;
		}
		function tdOnClick()
		{
		  // Cancel a click on the checkbox itself. Let it bubble up to the TD
		  return false;
		}
		function docOnSelectStart(ev)
		{
		  return false; // cancel text selection
		}
		</script>
<?		
		
		/* display studies associated with this project */
		$sqlstring = "select a.*, c.*, d.*,(datediff(a.study_datetime, d.birthdate)/365.25) 'age' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by d.uid asc, a.study_num asc";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$numstudies = mysql_num_rows($result);
		
		//PrintSQLTable($result);
		/* get some stats about the project */
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$uid = $row['uid'];
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
		//PrintVariable($genders);
		
		$lowdate = min($studydates);
		$highdate = max($studydates);
		?>
		<table>
			<tr>
				<td width="50%" valign="top">
					<fieldset style="border-radius: 5px; border: 1px solid #999">
						<legend><b>Project Info</b></legend>
						<table class="twocoltable">
							<thead>
								<tr>
									<th colspan="2"><?=$name?></th>
								</tr>
							</thead>
							<tr>
								<td class="left">Subjects</td>
								<td class="right"><?=count($uids)?></td>
							</tr>
							<tr>
								<td class="left">Age (years)</td>
								<td class="right">
									<table style="font-size: 9pt">
										<? list($n,$min,$max,$mean,$stdev) = arraystats($ages); ?>
										<tr><td align="right" style="padding-right: 10px"><b>All</b> (n=<?=$n?>)</td><td><?=number_format($mean,1)?> &plusmn;<?=number_format($stdev,1)?> (<?=number_format($min,1)?> - <?=number_format($max,1)?>)</td></tr>
										<?
											foreach ($genders as $sex => $a) {
												list($n,$min,$max,$mean,$stdev) = arraystats($a['ages']);
										?>
										<tr><td align="right" style="padding-right: 10px"><b><?=$sex?></b> (n=<?=$n?>)</td><td><?=number_format($mean,1)?> &plusmn;<?=number_format($stdev,1)?> (<?=number_format($min,1)?> - <?=number_format($max,1)?>)</td></tr>
										<?
											}
										?>
									</table>
								</td>
							</tr>
							<tr>
								<td class="left">Studies</td>
								<td class="right"><?=$numstudies?></td>
							</tr>
							<tr>
								<td class="left">Study date range</td>
								<td class="right"><?=$lowdate?> to <?=$highdate?></td>
							</tr>
						</table>
					</fieldset>
				</td>
				<td width="50%" valign="top">
					<fieldset style="border-radius: 5px; border: 1px solid #999">
						<legend><b>Available actions for project</b></legend>
						<a class="linkbutton" href="projectreport.php?action=viewprojectreport&id=<?=$id?>">View Project Report</a><br>
						<a class="linkbutton" href="projects.php?action=viewuniqueseries&id=<?=$id?>">Edit Alt Series Names</a> &nbsp; <a class="linkbutton" href="projects.php?action=viewaltseriessummary&id=<?=$id?>">View Alt Series Names</a><br>
						<a class="linkbutton" href="projects.php?action=editdemographics&id=<?=$id?>">Edit Demographics & IDs</a> &nbsp; <a class="linkbutton" href="projects.php?action=displaydemographics&id=<?=$id?>">View Demographics & IDs</a><br>
						<a class="linkbutton" href="projects.php?action=editmrparams&id=<?=$id?>">Edit MR scan params</a> &nbsp; <a class="linkbutton" href="projects.php?action=viewmrparams&id=<?=$id?>">View MR scan QC</a>
						<? if ($GLOBALS['isadmin']) { ?>
						<br><br>
						<b style="color:red">Danger area</b><br>
						<a class="redlinkbutton" href="projects.php?action=resetqa&id=<?=$id?>">Reset MRI QA</a>
						<? } ?>
					</fieldset>
				</td>
			</tr>
		</table>
		<br><br>

		<? if ($GLOBALS['issiteadmin']) { ?>
		<form action="projects.php" method="post" name="theform">
		<input type="hidden" name="action" value="changeproject">
		<input type="hidden" name="id" value="<?=$id?>">
		<? } ?>

		<table class="smallgraydisplaytable" id='table1'>
			<tr>
				<th>Subject</th>
				<th>Study</th>
				<th>Deleted?</th>
				<th>Alt Subject IDs</th>
				<th>Study Date</th>
				<th>Modality</th>
				<th>Study Desc</th>
				<th>Study ID</th>
				<? if ($GLOBALS['issiteadmin']) { ?>
				<th><input type="checkbox" id="checkall"></th>
				<? } ?>
				<th>Site</th>
			</tr>
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
			$uid = "";
			$bgcolor = "";
			mysql_data_seek($result,0);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$study_id = $row['study_id'];
				$modality = $row['study_modality'];
				$study_datetime = $row['study_datetime'];
				$study_site = $row['study_site'];
				$study_num = $row['study_num'];
				$study_desc = $row['study_desc'];
				$study_site = $row['study_site'];
				$study_altid = $row['study_alternateid'];
				$uid = $row['uid'];
				$subjectid = $row['subject_id'];
				$project_name = $row['project_name'];
				$project_costcenter = $row['project_costcenter'];
				$isactive = $row['isactive'];
				
				$sqlstringA = "select altuid from subject_altuid where subject_id = '$subjectid' and altuid <> '' order by isprimary desc";
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
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
				$altids = "";
				
				if ($lastuid != $uid) {
					if ($bgcolor == "") {
						$bgcolor = "background-color: #ddd;";
					}
					else {
						$bgcolor = "";
					}
					$rowstyle = "border-top: 1px solid #444; border-bottom: 0px; $bgcolor";
				}
				else {
					$rowstyle = "$bgcolor";
				}

				?>
				<tr>
					<td style="<?=$rowstyle?>">
						<a href="subjects.php?id=<?=$subjectid?>"><span style="color: darkblue; text-decoration:underline"><?=$uid;?></span></a>
					</td>
					<td style="<?=$rowstyle?>">
						<a href="studies.php?id=<?=$study_id?>"><span style="color: darkblue; text-decoration:underline"><?=$uid;?><?=$study_num;?></span></a>
					</td>
					<td style="<?=$rowstyle?>"><? if (!$isactive) { echo "Deleted"; } ?></td>
					<td style="<?=$rowstyle?>font-family: courier"><?=$altuidlist?></td>
					<td style="<?=$rowstyle?>"><?=$study_datetime?></td>
					<td style="<?=$rowstyle?>"><?=$modality?></td>
					<td style="<?=$rowstyle?>"><?=$study_desc?></td>
					<td style="<?=$rowstyle?>"><?=$study_altid?></td>
					<? if ($GLOBALS['issiteadmin']) { ?>
					<td class="allcheck" style="background-color: #FFFF99; border-left: 1px solid #4C4C1F; border-right: 1px solid #4C4C1F;" <?=$rowstyle?>><input type='checkbox' name="studyids[]" value="<?=$study_id?>"></td>
					<? } ?>
					<td style="<?=$rowstyle?>"><?=$study_site?></td>
				</tr>
				<?
				$lastuid = $uid;
			}
			?>
		</table>
		
		<br><br>

		<? if ($GLOBALS['issiteadmin']) { ?>
		<div style="position: fixed; bottom:0px; background-color: #FFFF99; border-bottom: 2px solid #4C4C1F; border-top: 2px solid #4C4C1F; width:100%; padding:8px; margin-left: -7px; font-size: 10pt">
		<table width="98%">
			<tr>
				<td>
					<b>Powerful Tools:</b>
					<select name="newprojectid">
					<?
						$sqlstring = "select a.*, b.user_fullname from projects a left join users b on a.project_pi = b.user_id where a.project_status = 'active' order by a.project_name";
						$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
					<input type="submit" value="Move Studies" title="Moves the imaging studies from this project to the selected project" onclick="document.theform.action='projects.php';document.theform.action.value='changeproject'" style="font-size:10pt">
					&nbsp;&nbsp;&nbsp;&nbsp;
					<span title="When re-archiving, only match existing subjects by ID. Do not use the Patient ID, DOB, or Sex fields to match subjects"><input type="checkbox" name="matchidonly" value="1" checked>Match ID only</span>
					&nbsp;&nbsp;
					<input type="submit" value="Re-archive DICOM studies" title="Moves all DICOM files back into the incoming directory to be parsed again. Useful if there was an archiving error and too many subjects are in the wrong place." onclick="document.theform.action='projects.php';document.theform.action.value='rearchivestudies'" style="color: red; font-size:10pt">
					&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" value="Re-archive Subjects" title="Moves all DICOM files from this SUBJECT into the incoming directory, and deletes the subject" onclick="document.theform.action='projects.php';document.theform.action.value='rearchivesubjects'" style="color: red; font-size:10pt">
				</td>
			</tr>
			<tr>
				<td align="right">
					<input type="submit" value="Obliterate Subjects &#128163;" title="Delete the subject permanently" onclick="document.theform.action='projects.php';document.theform.action.value='obliteratesubject'" style="color: red; font-size:10pt"> &nbsp; &nbsp;
					<input type="submit" value="Obliterate Studies &#128163;" title="Delete the studies permanently" onclick="document.theform.action='projects.php';document.theform.action.value='obliteratestudy'" style="color: red; font-size:10pt">
				</td>
			</tr>
		</table>
		</form>
		<? } ?>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDemographicsEditTable ------- */
	/* -------------------------------------------- */
	function DisplayDemographicsEditTable($id) {
		$id = mysql_real_escape_string($id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['Edit Demographics'] = "projects.php?action=editdemographics&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		?>
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="updatedemographics">
		<input type="hidden" name="id" value="<?=$id?>">
		<table class="smallgraydisplaytable">
			<thead>
				<th>UID</th>
				<th>Alt IDs<br><span class="tiny">Comma separated, * next to main ID</span></th>
				<th>GUID</th>
				<th>Birthdate<br><span class="tiny">YYY-MM-DD</span></th>
				<th>Sex<br><span class="tiny">M,F,U,O</span></th>
				<th>Race</th>
				<th>Ethnicity</th>
				<th>Handedness<br><span class="tiny">R,L,A,U</span></th>
				<th>Marital</th>
				<th>Smoking</th>
				<th>Enroll group</th>
			</thead>
		<?
		/* get all subjects, and their enrollment info, associated with the project */
		$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id order by a.uid";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
			
			$sqlstringA = "select altuid from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
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
			$altids = "";
			
			$sqlstringA = "select distinct(enroll_subgroup) from enrollment where enroll_subgroup <> '' order by enroll_subgroup";
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			?>
			<datalist id="enrollgroups">
			<?
				while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
					$enrollgroup = $rowA['enroll_subgroup'];
					?><option value="<?=$enrollgroup?>"><?
				}
			?>
			</datalist>
			<tr>
				<input type="hidden" name="subjectid[]" value="<?=$subjectid?>">
				<td style="font-weight: bold; font-size:12pt"><tt><?=$uid?></tt></td>
				<td><input type="text" name="altuids[]" size="30" value="<?=$altuidlist?>" style="font-family: monospace"></td>
				<td><input type="text" name="guids[]" size="10" value="<?=$guid?>"></td>
				<td><input type="text" name="birthdates[]" size="8" maxlength="10" value="<?=$birthdate?>"></td>
				<td><input type="text" name="genders[]" maxlength="1" style="width:35px" value="<?=$gender?>"></td>
				<td>
					<select name="ethnicity1[]" style="width:100px">
						<option value="" <? if ($ethnicity1 == "") echo "selected"; ?>></option>
						<option value="hispanic" <? if ($ethnicity1 == "hispanic") echo "selected"; ?>>Hispanic/Latino</option>
						<option value="nothispanic" <? if ($ethnicity1 == "nothispanic") echo "selected"; ?>>Not hispanic/latino</option>
					</select>
				</td>
				<td>
					<select name="ethnicity2[]" style="width:100px">
						<option value="" <? if ($ethnicity2 == "") echo "selected"; ?>></option>
						<option value="indian" <? if ($ethnicity2 == "indian") echo "selected"; ?>>American Indian/Alaska Native</option>
						<option value="asian" <? if ($ethnicity2 == "asian") echo "selected"; ?>>Asian</option>
						<option value="black" <? if ($ethnicity2 == "black") echo "selected"; ?>>Black/African American</option>
						<option value="islander" <? if ($ethnicity2 == "islander") echo "selected"; ?>>Hawaiian/Pacific Islander</option>
						<option value="white" <? if ($ethnicity2 == "white") echo "selected"; ?>>White</option>
					</select>
				</td>
				<td>
					<select name="education[]" style="width:100px">
						<option value="" <? if ($education == "") echo "selected"; ?>></option>
						<option value="0" <? if ($education == "0") echo "selected"; ?>>Unknown</option>
						<option value="1" <? if ($education == "1") echo "selected"; ?>>Grade School</option>
						<option value="2" <? if ($education == "2") echo "selected"; ?>>Middle School</option>
						<option value="3" <? if ($education == "3") echo "selected"; ?>>High School/GED</option>
						<option value="4" <? if ($education == "4") echo "selected"; ?>>Trade School</option>
						<option value="5" <? if ($education == "5") echo "selected"; ?>>Associates Degree</option>
						<option value="6" <? if ($education == "6") echo "selected"; ?>>Bachelors Degree</option>
						<option value="7" <? if ($education == "7") echo "selected"; ?>>Masters Degree</option>
						<option value="8" <? if ($education == "8") echo "selected"; ?>>Doctoral Degree</option>
					</select>
				</td>
				<td>
					<select name="maritalstatus[]">
						<option value="" <? if ($maritalstatus == "") echo "selected"; ?>></option>
						<option value="unknown" <? if ($maritalstatus == "unknown") echo "selected"; ?>>Unknown</option>
						<option value="single" <? if ($maritalstatus == "single") echo "selected"; ?>>Single</option>
						<option value="married" <? if ($maritalstatus == "married") echo "selected"; ?>>Married</option>
						<option value="divorced" <? if ($maritalstatus == "divorced") echo "selected"; ?>>Divorced</option>
						<option value="separated" <? if ($maritalstatus == "separated") echo "selected"; ?>>Separated</option>
						<option value="civilunion" <? if ($maritalstatus == "civilunion") echo "selected"; ?>>Civil Union</option>
						<option value="cohabitating" <? if ($maritalstatus == "cohabitating") echo "selected"; ?>>Cohabitating</option>
						<option value="widowed" <? if ($maritalstatus == "widowed") echo "selected"; ?>>Widowed</option>
					</select>
				</td>
				<td>
					<select name="smokingstatus[]">
						<option value="" <? if ($smokingstatus == "") echo "selected"; ?>></option>
						<option value="unknown" <? if ($smokingstatus == "unknown") echo "selected"; ?>>Unknown</option>
						<option value="never" <? if ($smokingstatus == "never") echo "selected"; ?>>Never</option>
						<option value="past" <? if ($smokingstatus == "past") echo "selected"; ?>>Past</option>
						<option value="current" <? if ($smokingstatus == "current") echo "selected"; ?>>Current</option>
					</select>
				</td>
				<td><input type="text" list="enrollgroups" name="enrollgroup[]" value="<?=$enrollsubgroup?>"></td>
			</tr>
			<?
		}
		?>
		</table>
		<input type="submit" value="Update">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDemographics ---------------- */
	/* -------------------------------------------- */
	function DisplayDemographics($id) {
		$id = mysql_real_escape_string($id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['View Demographics'] = "projects.php?action=displaydemographics&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		?>
		<table class="smallgraydisplaytable">
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
		$sqlstring = "select * from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $id order by a.uid";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
			
			$sqlstringA = "select altuid from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
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
			$altids = "";
			
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
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- ResetQA ---------------------------- */
	/* -------------------------------------------- */
	function ResetQA($id) {
		$id = mysql_real_escape_string($id);
		
		$sqlstring = "select mrseries_id from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = '$id'";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$seriesid = $row['mrseries_id'];
		
			/* delete from the mr_qa table */
			$sqlstringA = "delete from mr_qa where mrseries_id = $seriesid";
			//PrintSQL($sqlstringA);
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			
			/* delete from the qc* tables */
			$sqlstringA = "select qcmoduleseries_id from qc_moduleseries where series_id = $seriesid and modality = 'mr'";
			//PrintSQL($sqlstringA);
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysql_fetch_array($resultA, MYSQL_ASSOC);
			$qcmoduleseriesid = $rowA['qcmoduleseries_id'];

			if ($qcmoduleseriesid != "") {
				$sqlstringA = "delete from qc_results where qcmoduleseries_id = $qcmoduleseriesid";
				//PrintSQL($sqlstringA);
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				
				$sqlstringA = "delete from qc_moduleseries where qcmoduleseries_id = $qcmoduleseriesid";
				//PrintSQL($sqlstringA);
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				
				echo "QC deleted<br>";
			}
		}
	}

	

	/* -------------------------------------------- */
	/* ------- ViewMRParams ----------------------- */
	/* -------------------------------------------- */
	function ViewMRParams($id) {
		$id = mysql_real_escape_string($id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['View MR Scan Parameter QA'] = "projects.php?action=editmrparams&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');

		/* get all of the MR params for this project */
		$sqlstring = "select * from mr_scanparams where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		if (mysql_num_rows($result) < 1){
			?>No MR parameters specified for this project. Add them <a href="projects.php?action=editmrparams&id=<?=$id?>">here</a>.<?
			return;
		}
		else {
			$i=0;
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$parms['protocol'][$i] = $row['protocol_name'];
				$parms['sequence'][$i] = $row['sequence_name'];
				$parms['tr'][$i] = (double)$row['tr'];
				$parms['te'][$i] = (double)$row['te'];
				$parms['ti'][$i] = (double)$row['ti'];
				$parms['flip'][$i] = (double)$row['flip'];
				$parms['xdim'][$i] = (double)$row['xdim'];
				$parms['ydim'][$i] = (double)$row['ydim'];
				$parms['zdim'][$i] = (double)$row['zdim'];
				$parms['tdim'][$i] = (double)$row['tdim'];
				$parms['slicethickness'][$i] = (double)$row['slicethickness'];
				$parms['slicespacing'][$i] = (double)$row['slicespacing'];
				$parms['bandwidth'][$i] = (double)$row['bandwidth'];
				$i++;
			}
			$numparms = $i;
		}
		
		/* get list of studies associated with this project */
		$sqlstring = "select c.study_id, c.study_num, a.uid from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where b.project_id = '$id' and c.study_modality = 'MR'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0){
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$studyid = $row['study_id'];
				$uid = $row['uid'];
				$studynum = $row['study_num'];
				if ($studyid > 0) {
					/* get the mr_series rows */
					$sqlstringA = "select * from mr_series where study_id = $studyid";
					$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
					if (mysql_num_rows($resultA) > 0){
						?>
						<table width="100%">
							<tr>
								<td colspan="2" style="background-color: #444; color: white; padding: 3px 6px; border-radius:4px; margin-top: 10px; margin-bottom:5px"><b>Checking <?=$uid?><?=$studynum?>...</td>
							</tr>
						<?
						while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
							$seriesnum = $rowA['series_num'];
							$series_desc = $rowA['series_desc'];
							$series_protocol = $rowA['series_protocol'];
							$sequence = $rowA['series_sequencename'];
							$tr = (double)$rowA['series_tr'];
							$te = (double)$rowA['series_te'];
							$ti = (double)$rowA['series_ti'];
							$flip = (double)$rowA['series_flip'];
							$slicethickness = (double)$rowA['slicethickness'];
							$slicespacing = (double)$rowA['slicespacing'];
							$dimx = (double)$rowA['dimX'];
							$dimy = (double)$rowA['dimY'];
							$dimz = (double)$rowA['dimZ'];
							$dimt = (double)$rowA['dimT'];
							$bandwidth = (double)$rowA['bandwidth'];
							
							$protocol1 = $series_desc;
							$protocol2 = $series_protocol;
							
							$matched = false;
							$mismatch = "";
							/* check if the params in this study match with any of the rows in the QA params table */
							for ($i=0;$i<$numparms;$i++) {
								$rowmatch = true;
								$nummismatch[$i] = 0;
								if (($protocol1 != $parms['protocol'][$i]) && ($protocol2 != $parms['protocol'][$i])) { $rowmatch = false; $nummismatch[$i]++; }
								if ($sequence != $parms['sequence'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								if ($tr != $parms['tr'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								if ($te != $parms['te'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								if ($ti != $parms['ti'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								if ($flip != $parms['flip'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								//if ($dimx != $parms['dimx']) { $rowmatch = false; $nummismatch[$i]++; }
								//if ($dimy != $parms['dimy']) { $rowmatch = false; $nummismatch[$i]++; }
								//if ($dimz != $parms['dimz']) { $rowmatch = false; $nummismatch[$i]++; }
								//if ($dimt != $parms['dimt']) { $rowmatch = false; $nummismatch[$i]++; }
								if ($slicethickness != $parms['slicethickness'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								if ($slicespacing != $parms['slicespacing'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								if ($bandwidth != $parms['bandwidth'][$i]) { $rowmatch = false; $nummismatch[$i]++; }
								
								if ($rowmatch) { $matched = true; break; }
							}
							//PrintVariable($nummismatch);
							
							if ($matched) {
								?>
								<tr style="font-size: 9pt">
									<td style="width: 30px"></td>
									<td style="color: green">Series <?=$seriesnum?> [<?=$protocol1?>] <b>OK</b></td>
								</tr><?
							}
							else {
								?><tr>
									<td style="width: 30px"></td>
									<td style="padding-left: 30px"><span style="color: red; font-size:9pt">Series <?=$seriesnum?> [<?=$protocol1?>] did NOT match. Nearest matches:
								<?
								$min = min($nummismatch);
								$idx = array_keys($nummismatch, $min);
								?>
								<table class="smallsimpledisplaytable" width="100%">
									<thead>
										<tr>
										<th>Protocol</th>
										<th>Sequence</th>
										<th>TR</th>
										<th>TE</th>
										<th>TI</th>
										<th>Flip &ang;</th>
										<th>X dim</th>
										<th>Y dim</th>
										<th>Z dim</th>
										<th>T dim</th>
										<th>Slice thick</th>
										<th>Slice spacing</th>
										<th>Bandwidth</th>
										<th></th>
										</tr>
									</thead>
									<tr style="font-weight: bold">
										<td><?=$protocol1?></td>
										<td><?=$sequence?></td>
										<td><?=$tr?></td>
										<td><?=$te?></td>
										<td><?=$ti?></td>
										<td><?=$flip?></td>
										<td><?=$dimx?></td>
										<td><?=$dimy?></td>
										<td><?=$dimz?></td>
										<td><?=$dimt?></td>
										<td><?=$slicethickness?></td>
										<td><?=$slicespacing?></td>
										<td><?=$bandwidth?></td>
										<td><a href="projects.php?id=<?=$id?>&action=loadmrparams&existingstudy=<?="$uid$studynum"?>&existingseries=<?=$seriesnum?>">Add to QA list</a></td>
									</tr>
								<?
								/* loop through the possible matches */
								foreach ($idx as $i) {
									$parm_protocol = $parms['protocol'][$i];
									$parm_sequence = $parms['sequence'][$i];
									$parm_tr = $parms['tr'][$i];
									$parm_te = $parms['te'][$i];
									$parm_ti = $parms['ti'][$i];
									$parm_flip = $parms['flip'][$i];
									$parm_xdim = $parms['xdim'][$i];
									$parm_ydim = $parms['ydim'][$i];
									$parm_zdim = $parms['zdim'][$i];
									$parm_tdim = $parms['tdim'][$i];
									$parm_slicethickness = $parms['slicethickness'][$i];
									$parm_slicespacing = $parms['slicespacing'][$i];
									$parm_bandwidth = $parms['bandwidth'][$i];
									?>
									<tr>
										<td style="color: <? if (($parm_protocol == $protocol1) || ($parm_protocol == $protocol2)) { echo "green"; } else { echo "red"; }?>"><?=$parm_protocol?></td>
										<td style="color: <?=($parm_sequence == $sequence)?"green":"red";?>"><?=$parm_sequence?></td>
										<td style="color: <?=($parm_tr == $tr)?"green":"red";?>"><?=$parm_tr?></td>
										<td style="color: <?=($parm_te == $te)?"green":"red";?>"><?=$parm_te?></td>
										<td style="color: <?=($parm_ti == $ti)?"green":"red";?>"><?=$parm_ti?></td>
										<td style="color: <?=($parm_flip == $flip)?"green":"red";?>"><?=$parm_flip?></td>
										<td style="color: <?=($parm_xdim == $xdim)?"green":"red";?>"><?=$parm_xdim?></td>
										<td style="color: <?=($parm_ydim == $ydim)?"green":"red";?>"><?=$parm_ydim?></td>
										<td style="color: <?=($parm_zdim == $zdim)?"green":"red";?>"><?=$parm_zdim?></td>
										<td style="color: <?=($parm_tdim == $tdim)?"green":"red";?>"><?=$parm_tdim?></td>
										<td style="color: <?=($parm_slicethickness == $slicethickness)?"green":"red";?>"><?=$parm_slicethickness?></td>
										<td style="color: <?=($parm_slicespacing == $slicespacing)?"green":"red";?>"><?=$parm_slicespacing?></td>
										<td style="color: <?=($parm_bandwidth == $bandwidth)?"green":"red";?>"><?=$parm_bandwidth?></td>
										<td></td>
									</tr>
									<?
								}
								?></table>
								</td>
								</tr>
							<?
							}
						}
						?></table><?
					}
					else {
						echo "Found no MR series for this study [$uid$studynum]";
					}
				}
				else {
					echo "Invalid study ID [$study_id]<br>";
				}
			}
		}
		else {
			echo "Found no valid MR studies for this project<br>";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- LoadMRParams ----------------------- */
	/* -------------------------------------------- */
	function LoadMRParams($id, $study, $series) {
		$id = mysql_real_escape_string($id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		$study = mysql_real_escape_string(trim($study));
		$series = mysql_real_escape_string(trim($series));
		
		$uid = substr($study,0,8);
		$studynum = substr($study,8);
		
		/* check if its a valid subject, valid study num, and is MR modality */
		$sqlstring = "select c.study_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where a.uid = '$uid' and c.study_num = '$studynum' and c.study_modality = 'MR'";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0){
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$studyid = $row['study_id'];
			if ($studyid > 0) {
				/* get the mr_series rows */
				if ($series == "") {
					$sqlstring = "select * from mr_series where study_id = $studyid";
				}
				else {
					$sqlstring = "select * from mr_series where study_id = $studyid and series_num = '$series'";
				}
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
				if (mysql_num_rows($result) > 0){
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$series_desc = $row['series_desc'];
						$series_protocol = $row['series_protocol'];
						$sequence = $row['series_sequencename'];
						$tr = $row['series_tr'];
						$te = $row['series_te'];
						$ti = $row['series_ti'];
						$flip = $row['series_flip'];
						$slicethickness = $row['slicethickness'];
						$slicespacing = $row['slicespacing'];
						$dimx = $row['dimX'];
						$dimy = $row['dimY'];
						$dimz = $row['dimZ'];
						$dimt = $row['dimT'];
						$bandwidth = $row['bandwidth'];
						
						if (strlen($series_desc) != "") {
							$protocol = $series_desc;
						}
						else {
							$protocol = $series_protocol;
						}
						$param_rowid[] = "";
						$param_protocol[] = $protocol;
						$param_sequence[] = $sequence;
						$param_tr[] = (double)$tr;
						$param_te[] = (double)$te;
						$param_ti[] = (double)$ti;
						$param_flip[] = (double)$flip;
						$param_xdim[] = (double)$dimx;
						$param_ydim[] = (double)$dimy;
						$param_zdim[] = (double)$dimz;
						$param_tdim[] = (double)$dimt;
						$param_slicethickness[] = (double)$slicethickness;
						$param_slicespacing[] = (double)$slicespacing;
						$param_bandwidth[] = (double)$bandwidth;
						echo "Found row [$protocol]<br>";
					}
					
					/* we have all the params, now do the inserts into the scan params table */
					UpdateMRParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr, $param_te, $param_ti, $param_flip, $param_xdim, $param_ydim, $param_zdim, $param_tdim, $param_slicethickness, $param_slicespacing, $param_bandwidth);
				}
				else {
					?><span class="staticmessage">No MR series found for [<?=$study?>]</span><?
				}
			}
		}
		else {
			?><span class="staticmessage">Invalid study ID [<?=$study?>]. Incorrect UID, study number, or study does not contain MR series</span><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateMRParams --------------------- */
	/* -------------------------------------------- */
	function UpdateMRParams($id, $param_rowid, $param_protocol, $param_sequence, $param_tr, $param_te, $param_ti, $param_flip, $param_xdim, $param_ydim, $param_zdim, $param_tdim, $param_slicethickness, $param_slicespacing, $param_bandwidth) {
		
		$i=0;
		foreach ($param_rowid as $paramid) {
			$paramid = mysql_real_escape_string($paramid);
			
			$protocol = mysql_real_escape_string(trim($param_protocol[$i]));
			$sequence = mysql_real_escape_string(trim($param_sequence[$i]));
			$tr = mysql_real_escape_string(trim($param_tr[$i]));
			$te = mysql_real_escape_string(trim($param_te[$i]));
			$ti = mysql_real_escape_string(trim($param_ti[$i]));
			$flip = mysql_real_escape_string(trim($param_flip[$i]));
			$xdim = mysql_real_escape_string(trim($param_xdim[$i]));
			$ydim = mysql_real_escape_string(trim($param_ydim[$i]));
			$zdim = mysql_real_escape_string(trim($param_zdim[$i]));
			$tdim = mysql_real_escape_string(trim($param_tdim[$i]));
			$slicethickness = mysql_real_escape_string(trim($param_slicethickness[$i]));
			$slicespacing = mysql_real_escape_string(trim($param_slicespacing[$i]));
			$bandwidth = mysql_real_escape_string(trim($param_bandwidth[$i]));
			
			if ($protocol != "") {
				if ($paramid == "") {
					$sqlstring = "insert ignore into mr_scanparams (protocol_name, sequence_name, project_id, tr, te, ti, flip, xdim, ydim, zdim, tdim, slicethickness, slicespacing, bandwidth) values ('$protocol', '$sequence', '$id', '$tr', '$te', '$ti', '$flip', '$xdim', '$ydim', '$zdim', '$tdim', '$slicethickness', '$slicespacing', '$bandwidth')";
				}
				else {
					$sqlstring = "update ignore mr_scanparams set protocol_name = '$protocol', sequence_name = '$sequence', tr = '$tr', te = '$te', ti = '$ti', flip = '$flip', xdim = '$xdim', ydim = '$ydim', zdim = '$zdim', tdim = '$tdim', slicethickness = '$slicethickness', slicespacing = '$slicespacing', bandwidth = '$bandwidth' where mrscanparam_id = $paramid";
				}
				//PrintSQL($sqlstring);
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			}
			if (($protocol == "") && ($paramid != "")) {
				$sqlstring = "delete from mr_scanparams where mrscanparam_id = $paramid";
				$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
			}
			$i++;
		}
	}
	

	/* -------------------------------------------- */
	/* ------- EditMRScanParams ------------------- */
	/* -------------------------------------------- */
	function EditMRScanParams($id) {
		$id = mysql_real_escape_string($id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
	
		DisplayMRScanParamHeader($id);
		
		/* get all of the existing scan parameters */
		$sqlstring = "select * from mr_scanparams where project_id = '$id' order by protocol_name, sequence_name";
		//PrintSQL($sqlstring);
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$paramid = $row['mrscanparam_id'];
			$protocol = $row['protocol_name'];
			$sequence = $row['sequence_name'];
			$tr = $row['tr'];
			$te = $row['te'];
			$ti = $row['ti'];
			$flip = $row['flip'];
			$xdim = $row['xdim'];
			$ydim = $row['ydim'];
			$zdim = $row['zdim'];
			$tdim = $row['tdim'];
			$slicethickness = $row['slicethickness'];
			$slicespacing = $row['slicespacing'];
			$bandwidth = $row['bandwidth'];

			DisplayMRScanParamLine($paramid, $protocol, $sequence, $tr, $te, $ti, $flip, $xdim, $ydim, $zdim, $tdim, $slicethickness, $slicespacing, $bandwidth);
		}
		
		for ($i=0;$i<5;$i++) {
			DisplayMRScanParamLine();
		}
		?>
		</table>
		<input type="submit" value="Update">
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRScanParamHeader ----------- */
	/* -------------------------------------------- */
	function DisplayMRScanParamHeader($id) {
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['Edit MR Scan Parameters'] = "projects.php?action=editmrparams&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		?>
		<fieldset>
			<legend>Add scan parameters from existing study</legend>
			<form>
			<input type="hidden" name="action" value="loadmrparams">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="text" name="existingstudy"> &nbsp; <input type="submit" value="Load Parameters"><br>
			<span class="tiny">Enter study ID in the format <u>S1234ABC5</u></span>
			</form>
		</fieldset>
		<br><br>
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="updatemrparams">
		<input type="hidden" name="id" value="<?=$id?>">
		<table class="smallgraydisplaytable">
			<thead>
				<tr>
					<th>Protocol<br><span class="tiny">Leave blank to remove the row</span></th>
					<th>Sequence</th>
					<th>TR</th>
					<th>TE</th>
					<th>TI</th>
					<th>Flip &ang;</th>
					<th>X dim</th>
					<th>Y dim</th>
					<th>Z dim</th>
					<th>T dim</th>
					<th>Slice thickness</th>
					<th>Spacing between slice centers</th>
					<th>Bandwidth</th>
				</tr>
			</thead>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayMRScanParamLine ------------- */
	/* -------------------------------------------- */
	function DisplayMRScanParamLine($rowid="", $protocol="", $sequence="", $tr="", $te="", $ti="", $flip="", $xdim="", $ydim="", $zdim="", $tdim="", $slicethickness="", $slicespacing="", $bandwidth="") {
		?><tr>
			<input type="hidden" name="param_rowid[]" value="<?=$rowid?>">
			<td><input type="text" name="param_protocol[]" value="<?=$protocol?>"></td>
			<td><input type="text" name="param_sequence[]" value="<?=$sequence?>"></td>
			<td><input type="text" size="5" maxlength="8" name="param_tr[]" value="<?=$tr?>"></td>
			<td><input type="text" size="5" maxlength="8" name="param_te[]" value="<?=$te?>"></td>
			<td><input type="text" size="5" maxlength="8" name="param_ti[]" value="<?=$ti?>"></td>
			<td><input type="text" size="5" maxlength="8" name="param_flip[]" value="<?=$flip?>"></td>
			<td><input type="number" style="width: 65px" name="param_xdim[]" value="<?=$xdim?>"></td>
			<td><input type="number" style="width: 65px" name="param_ydim[]" value="<?=$ydim?>"></td>
			<td><input type="number" style="width: 65px" name="param_zdim[]" value="<?=$zdim?>"></td>
			<td><input type="number" style="width: 65px" name="param_tdim[]" value="<?=$tdim?>"></td>
			<td><input type="text" size="5" maxlength="8" name="param_slicethickness[]" value="<?=$slicethickness?>"></td>
			<td><input type="text" size="5" maxlength="8" name="param_slicespacing[]" value="<?=$slicespacing?>"></td>
			<td><input type="text" size="7" maxlength="8" name="param_bandwidth[]" value="<?=$bandwidth?>"></td>
		</tr><?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayUniqueSeries ---------------- */
	/* -------------------------------------------- */
	function DisplayUniqueSeries($id) {
		$id = mysql_real_escape_string($id);
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['Edit Group Protocols'] = "projects.php?action=viewuniqueseries&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$studyid = $row['study_id'];
			$modality = strtolower($row['study_modality']);
			
			if (($modality != "") && ($studyid != "")) {
				$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid'";
				//PrintSQL($sqlstringA);
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
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
				}
			}
		}
		
		?>
		<form action="projects.php" method="post">
		<input type="hidden" name="action" value="changealternatenames">
		<input type="hidden" name="id" value="<?=$id?>">
		<table class="graydisplaytable">
			<thead>
				<th>Modality</th>
				<th>Series Description</th>
				<th>Count</th>
				<th>New Description</th>
			</thead>
		<?
		$i=0;
		foreach ($seriesdescs as $modality => $serieslist) {
			natksort($serieslist);
			foreach ($serieslist as $series => $count) {
				?>
				<tr>
					<td><?=strtoupper($modality)?></td>
					<td><tt><?=$series?></tt></td>
					<td><?=$count?></td>
					<td><input type="hidden" name="modalities[<?=$i?>]" value="<?=strtolower($modality)?>"><input type="hidden" name="oldname[<?=$i?>]" value="<?=$series?>"><input type="text" name="newname[<?=$i?>]"></td>
				</tr>
				<?
				$i++;
			}
		}
		?>
			<tr>
				<td colspan="3" align="right"><input type="submit"></td>
			</tr>
		</table>

		<?
	}


	/* -------------------------------------------- */
	/* ------- ChangeSeriesAlternateNames --------- */
	/* -------------------------------------------- */
	function ChangeSeriesAlternateNames($id, $modalities, $oldnames, $newnames) {
		if (!isInteger($id)) { echo "Invalid project ID [$id]"; return; }
		$id = mysql_real_escape_string($id);
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality, uid, study_num from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = b.subject_id where a.project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$numrowsaffected = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			
			foreach ($modalities as $i => $modality) {
				$modality = mysql_real_escape_string($modality);
				$oldname = mysql_real_escape_string($oldnames[$i]);
				$newname = mysql_real_escape_string($newnames[$i]);
				if (($modality != "") && ($studyid != "") && ($oldname != "") && ($newname != "")) {
					$sqlstringA = "update $modality" . "_series set series_altdesc = '$newname' where (series_desc = '$oldname' or (series_protocol = '$oldname' and (series_desc = '' or series_desc is null))) and study_id = '$studyid'";
					$numupdates = 0;
					$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
					$numupdates = mysql_affected_rows();
					$numrowsaffected += $numupdates;
					if ($numupdates > 0) {
						//echo "[$sqlstringA]<br>";
						echo "<b>Added alternate series description for $uid$studynum. $oldname &rarr; $newname</b><br>";
					}
				}
			}
		}
		echo "Updated [$numrowsaffected] rows<br>";
	}


	/* -------------------------------------------- */
	/* ------- DisplayAltSeriesSummary ------------ */
	/* -------------------------------------------- */
	function DisplayAltSeriesSummary($id) {
		$id = mysql_real_escape_string($id);

		$sqlstring = "select * from projects where project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['Series Summary'] = "projects.php?action=viewuniqueseries&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality, uid, study_num from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = b.subject_id where a.project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			$modality = strtolower($row['study_modality']);
			
			if (($modality != "") && ($studyid != "")) {
				$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' and ishidden <> 1";
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
					$seriesaltdesc = $rowA['series_altdesc'];
					if ($seriesaltdesc != "") {
						$seriesdescs[$uid][$modality][$seriesaltdesc]++;
						$uniqueseries[$modality][$seriesaltdesc]++;
					}
				}
			}
		}
		
		?>
		<table class="graydisplaytable">
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
	/* ------- DisplayInstanceSummary ------------- */
	/* -------------------------------------------- */
	function DisplayInstanceSummary($id) {
		$id = mysql_real_escape_string($id);
		
		$urllist['Project List'] = "projects.php";
		NavigationBar("Projects for " . $_SESSION['instancename'], $urllist,0,'','','','');
		
		/* get all studies associated with this project */
		$sqlstring = "SELECT b.enrollment_id, c.study_id, c.study_modality, c.study_num, c.study_ageatscan, d.uid, d.subject_id, d.birthdate, e.altuid, a.project_name FROM projects a LEFT JOIN enrollment b on a.project_id = b.project_id LEFT JOIN studies c on b.enrollment_id = c.enrollment_id LEFT JOIN subjects d on d.subject_id = b.subject_id LEFT JOIN subject_altuid e on e.subject_id = d.subject_id WHERE a.instance_id = $id and d.isactive = 1 order by a.project_name, e.altuid";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQL($sqlstring);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			$subjectid = $row['subject_id'];
			$age = $row['study_ageatscan'];
			$dob = $row['birthdate'];
			$projectname = $row['project_name'];
			$modality = strtolower($row['study_modality']);
			$enrollmentid = $row['enrollment_id'];
			
			$sqlstringA = "select altuid from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
			$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysql_fetch_array($resultA, MYSQL_ASSOC);
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
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
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
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysql_fetch_array($resultA, MYSQL_ASSOC)) {
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
	/* ------- ObliterateSubject ------------------ */
	/* -------------------------------------------- */
	function ObliterateSubject($studyids) {
		$studyids = mysql_real_escape_array($studyids);
		
		/* get list of subjects from the studyids */
		$sqlstring = "select subject_id, uid from subjects where subject_id in (select subject_id from enrollment where enrollment_id in (select enrollment_id from studies where study_id in (" . implode(',',$studyids) . ") ))";
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
	/* ------- ObliterateStudy -------------------- */
	/* -------------------------------------------- */
	function ObliterateStudy($studyids) {
		$studyids = mysql_real_escape_array($studyids);
		
		/* delete all information about this SUBJECT from the database */
		foreach ($studyids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Studies [<?=implode2(', ',$studyids)?>] queued for obliteration</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- RearchiveStudies ------------------- */
	/* -------------------------------------------- */
	function RearchiveStudies($studyids, $matchidonly) {
		$studyids = mysql_real_escape_array($studyids);
		$matchidonly = mysql_real_escape_string($matchidonly);
		
		/* rearchive all the studies */
		foreach ($studyids as $id) {
			if ($matchidonly) {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rearchiveidonly', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			}
			else {
				$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('rearchive', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			}
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Studies [<?=implode(', ',$studyids)?>] queued for re-archiving</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- RearchiveSubjects ------------------ */
	/* -------------------------------------------- */
	function RearchiveSubjects($studyids, $matchidonly) {
		$studyids = mysql_real_escape_array($studyids);
		$matchidonly = mysql_real_escape_string($matchidonly);
		
		/* get list of subjects from the studyids */
		$sqlstring = "select subject_id, uid from subjects where subject_id in (select subject_id from enrollment where enrollment_id in (select enrollment_id from studies where study_id in (" . implode(',',$studyids) . ") ))";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		}
		?>
		<div align="center" class="message">Subjects [<?=implode(', ',$uids)?>] queued for re-archiving</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectList ----------------- */
	/* -------------------------------------------- */
	function DisplayProjectList() {
		
		$urllist['Project List'] = "projects.php";
		NavigationBar("Projects for " . $_SESSION['instancename'], $urllist,0,'','','','');
		
		?>
		View <a href="projects.php?action=viewinstancesummary&id=<?=$_SESSION['instanceid']?>">instance summary</a>
		<br><br>
		<p id="msg" style="color: #0A0; text-align: center;">&nbsp;</p>		
		<table class="sortable graydisplaytable" width="100%">
			<thead>
				<tr>
					<th data-sort="string-ins">Name</th>
					<th data-sort="string-ins">UID</th>
					<th data-sort="string-ins">Cost Center</th>
					<th data-sort="string-ins">Admin</th>
					<th data-sort="string-ins">PI</th>
					<th data-sort="int">Studies</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = " . $_SESSION['instanceid'] . " order by a.project_name";
					$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$id = $row['project_id'];
						$name = $row['project_name'];
						$adminusername = $row['adminusername'];
						$adminfullname = $row['adminfullname'];
						$piusername = $row['piusername'];
						$pifullname = $row['pifullname'];
						$projectuid = $row['project_uid'];
						$costcenter = $row['project_costcenter'];

						$sqlstringA = "select * from user_project where user_id in (select user_id from users where username = '" . $GLOBALS['username'] . "') and project_id = $id";
						$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
						$rowA = mysql_fetch_array($resultA, MYSQL_ASSOC);
						$view_data = $rowA['view_data'];
						$view_phi = $rowA['view_phi'];
						
						if ($view_data) {
							?>
							<tr valign="top">
								<td><a href="projects.php?action=displayproject&id=<?=$id?>"><?=$name?></td>
								<td><?=$projectuid?></td>
								<td><?=$costcenter?></td>
								<td><?=$adminfullname?></td>
								<td><?=$pifullname?></td>
								<td align="right">
									<table cellpadding="0" cellspacing="0" border="0">
								<?
								$sqlstring = "SELECT a.study_modality, b.project_id, count(b.project_id) 'count' FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 group by b.project_id,a.study_modality";
								//PrintSQL($sqlstring);
								$result2 = MySQLQuery($sqlstring, __FILE__, __LINE__);
								while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
									$modality = $row2['study_modality'];
									$count = $row2['count'];
									
									$projectModalitySize = 0;
									if ($modality != "") {
										$sqlstring3 = "select sum(series_size) 'modalitysize' from " . strtolower($modality) ."_series where study_id in (SELECT a.study_id FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 and a.study_modality = '$modality')";
										$projectModalitySize = $row3['modalitysize'];
									}
									
									if ($modality == "") { $modality = "(blank)"; }
									?>
									<tr>
									<td align="right" style="font-size:10pt; border: none; color: darkblue; padding: 0px 3px"><b><?=$modality?></b></td>
									<td style="font-size:10pt; border: none; padding: 0px 3px"><?=$count?> <!--<span class="tiny"><?=number_format(($projectModalitySize/1024/1024/1024),1)?> GB</span>--></td>
									</tr>
									<?
								}
							?>
									</table>
								</td>
							</tr>
							<?
						}
						else {
						?>
							<tr>
								<td colspan="5">
									No access to <?=$name?>
								</td>
							</tr>
						<?
						}
					}
				?>
			</tbody>
		</table>
		<?
	}
?>

<br><br><br><br>

<? include("footer.php") ?>
