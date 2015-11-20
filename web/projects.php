<?
 // ------------------------------------------------------------------------------
 // NiDB projects.php
 // Copyright (C) 2004 - 2015
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
		default:
			DisplayProjectList();
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- ChangeProject ---------------------- */
	/* -------------------------------------------- */
	function ChangeProject($projectRowID, $studyids) {
	
		foreach ($studyids as $studyRowID) {
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
	
		$sqlstring = "select * from projects where project_id = $id";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
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
		<!--<style type='text/css'>
		#table1 td {
		  cursor: default;
		  border: 1px dotted #BF8660;
		}
		</style>-->
		<script type='text/javascript' src='scripts/x/x.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xgetelementbyid.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xtableiterate.js'></script>
		<script type='text/javascript' src='scripts/x/lib/xpreventdefault.js'></script>
		<script type='text/javascript'>
		window.onload = function()
		{
		  //initCheckBoxes('table1');
		};
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
		//$sqlstring = "select a.*, c.*, d.uid, d.subject_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id and d.isactive = 1 order by a.study_site, a.study_desc";
		$sqlstring = "select a.*, c.*, d.uid, d.subject_id, d.isactive from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id left join subjects d on d.subject_id = b.subject_id where c.project_id = $id order by a.study_datetime";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$numstudies = mysql_num_rows($result);
		?>
		<?=$numstudies?> studies associated with this project
		<br><br>

		<? if ($GLOBALS['isadmin']) { ?>
		<form action="projects.php" method="post" name="theform">
		<input type="hidden" name="action" value="changeproject">
		<input type="hidden" name="id" value="<?=$id?>">
		<? } ?>
		
		<table class="smallgraydisplaytable" id='table1'>
			<tr>
				<th>Study ID</th>
				<th>Deleted?</th>
				<th>Subject ID</th>
				<th>Study Date</th>
				<th>Modality</th>
				<th>Study Desc</th>
				<th>Study ID</th>
				<? if ($GLOBALS['isadmin']) { ?>
				<th><input type="checkbox" id="checkall"></th>
				<? } ?>
				<th>Site</th>
				<th>Project</th>
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
				
				$sqlstringA = "select altuid from subject_altuid where subject_id = '$subjectid' order by isprimary desc";
				$resultA = MySQLQuery($sqlstringA, __FILE__, __LINE__);
				$rowA = mysql_fetch_array($resultA, MYSQL_ASSOC);
				$isprimary = $rowA['isprimary'];
				$altuid = $rowA['altuid'];
				
				if ($isprimary) {
					$altuid = "<b>$altuid</b>";
				}
				?>
				<tr>
					<td>
						<a href="studies.php?id=<?=$study_id?>"><span style="color: darkblue; text-decoration:underline"><?=$uid;?><?=$study_num;?></span></a>
					</td>
					<td><? if (!$isactive) { echo "Deleted"; } ?></td>
					<td><?=$altuid?></td>
					<td><?=$study_datetime?></td>
					<td><?=$modality?></td>
					<td><?=$study_desc?></td>
					<td><?=$study_altid?></td>
					<!--<td style="background-color: #FFFF99; border-left: 1px solid #4C4C1F; border-right: 1px solid #4C4C1F;"><input type='checkbox' name="studyids[]" value="<?=$study_id?>" onmouseover="if (!this.checked) { this.checked = true; } else { this.checked = false; }"></td>-->
					<? if ($GLOBALS['isadmin']) { ?>
					<td class="allcheck" style="background-color: #FFFF99; border-left: 1px solid #4C4C1F; border-right: 1px solid #4C4C1F;"><input type='checkbox' name="studyids[]" value="<?=$study_id?>"></td>
					<? } ?>
					<td><?=$study_site?></td>
					<td><?=$project_name?> (<?=$project_costcenter?>)</td>
				</tr>
				<?
			}
			?>
		</table>

		<? if ($GLOBALS['isadmin']) { ?>
		<div style="position: fixed; bottom:0px; background-color: #FFFF99; border-bottom: 2px solid #4C4C1F; border-top: 2px solid #4C4C1F; width:100%; padding:8px; margin-left: -7px; font-size: 10pt">
		<table width="98%">
			<tr>
				<td>
					<b>Powerful Tools:</b>
					<select name="newprojectid">
					<?
						$sqlstring = "select a.*, b.user_fullname from projects a left join users b on a.project_pi = b.user_id where a.project_status = 'active' order by a.project_name";
						//$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
						$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
							$project_id = $row['project_id'];
							$project_name = $row['project_name'];
							$project_costcenter = $row['project_costcenter'];
							$project_enddate = $row['project_enddate'];
							$user_fullname = $row['user_fullname'];
							
							if (strtotime($project_enddate) < strtotime("now")) { $style="color: gray"; } else { $style = ""; }
							//echo "[" . strtotime($project_enddate) . ":" . strtotime("now") . "]<br>";
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
	/* ------- DisplayUniqueSeries ---------------- */
	/* -------------------------------------------- */
	function DisplayUniqueSeries($id) {
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['Edit Group Protocols'] = "projects.php?action=viewuniqueseries&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id where a.project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
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
		//echo "<pre>";
		//print_r($seriesdescs);
		//echo "</pre>";
		
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
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality, uid, study_num from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = b.subject_id where a.project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		$numrowsaffected = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			
			foreach ($modalities as $i => $modality) {
				$oldname = $oldnames[$i];
				$newname = $newnames[$i];
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
		
		$sqlstring = "select * from projects where project_id = $id";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$name = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist[$name] = "projects.php?action=displayproject&id=$id";
		$urllist['Series Summary'] = "projects.php?action=viewuniqueseries&id=$id";
		NavigationBar("Projects", $urllist,0,'','','','');
		
		/* get all studies associated with this project */
		$sqlstring = "select study_id, study_modality, uid, study_num from projects a left join enrollment b on a.project_id = b.project_id left join studies c on b.enrollment_id = c.enrollment_id left join subjects d on d.subject_id = b.subject_id where a.project_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$uid = $row['uid'];
			$modality = strtolower($row['study_modality']);
			
			if (($modality != "") && ($studyid != "")) {
				$sqlstringA = "select * from $modality" . "_series where study_id = '$studyid' and ishidden <> 1";
				//PrintSQL($sqlstringA);
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
		//PrintVariable($seriesdescs, 'seriesdesc');
		//PrintVariable($uniqueseries, 'uniqueseries');
		
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
		//PrintVariable($seriesdescs, 'seriesdesc');
		//PrintVariable($uniqueseries, 'uniqueseries');
		
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
		
		/* delete all information about this SUBJECT from the database */
		foreach ($studyids as $id) {
			$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'study', $id,'" . $GLOBALS['username'] . "', now())";
			PrintSQL($sqlstring);
			$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		}
		//PrintVariable($ids,'ids');
		//PrintVariable($uids,'uids');
		//PrintVariable($uidstudynums,'uidstudynums');
		?>
		<div align="center" class="message">Studies [<?=implode2(', ',$studyids)?>] queued for obliteration</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- RearchiveStudies ------------------- */
	/* -------------------------------------------- */
	function RearchiveStudies($studyids, $matchidonly) {
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
		<table class="graydisplaytable" width="100%">
			<thead>
				<tr>
					<th>Name</th>
					<th>UID</th>
					<th>Cost Center</th>
					<th>Admin</th>
					<th>PI</th>
					<th>View report</th>
					<th>Group Protocols</th>
					<th>Studies</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname', c.username 'piusername', c.user_fullname 'pifullname' from projects a left join users b on a.project_admin = b.user_id left join users c on a.project_pi = c.user_id where a.project_status = 'active' and a.instance_id = " . $_SESSION['instanceid'] . " order by a.project_name";
					//PrintSQL($sqlstring);
					//exit(0);
					$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
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
						//PrintSQL($sqlstringA);
						$resultA = mysql_query($sqlstringA) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
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
								<td><a href="projectreport.php?action=viewprojectreport&projectid=<?=$id?>">Report</a></td>
								<td><a href="projects.php?action=viewuniqueseries&id=<?=$id?>">Edit</a> | <a href="projects.php?action=viewaltseriessummary&id=<?=$id?>">Summary</a></td>
								<td align="right">
									<table cellpadding="0" cellspacing="0" border="0">
								<?
								$sqlstring = "SELECT a.study_modality, b.project_id, count(b.project_id) 'count' FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 group by b.project_id,a.study_modality";
								//PrintSQL($sqlstring);
								$result2 = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
								while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
									$modality = $row2['study_modality'];
									$count = $row2['count'];
									
									$projectModalitySize = 0;
									if ($modality != "") {
										$sqlstring3 = "select sum(series_size) 'modalitysize' from " . strtolower($modality) ."_series where study_id in (SELECT a.study_id FROM `studies` a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where b.project_id = $id and c.isactive = 1 and a.study_modality = '$modality')";
										//$result3 = mysql_query($sqlstring3) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring3</i><br>");
										//$row3 = mysql_fetch_array($result3, MYSQL_ASSOC);
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
