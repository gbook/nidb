<?
// ------------------------------------------------------------------------------
// NiDB groups.php
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
	<title>NiDB - Manage Groups</title>
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
	$groupid = GetVariable("groupid");
	$subjectgroupid = GetVariable("subjectgroupid");
	$studygroupid = GetVariable("studygroupid");
	$seriesgroupid = GetVariable("seriesgroupid");
	$groupname = GetVariable("groupname");
	$grouptype = GetVariable("grouptype");
	$owner = GetVariable("owner");
	$uids = GetVariable("uids");
	$uidids = GetVariable("uidids");
	$seriesids = GetVariable("seriesid");
	$studyids = GetVariable("studyid");
	$modality = GetVariable("modality");
	$itemid = GetVariable("itemid");
	$measures = GetVariable("measures");
	$columns = GetVariable("columns");
	$groupmeasures = GetVariable("groupmeasures");
	$studylist = GetVariable("studylist");

	//if ($groupid == "")
		

	/* determine action */
	switch ($action) {
		case 'add':
			AddGroup($groupname, $grouptype, $GLOBALS['username']);
			DisplayGroupList();
			break;
		case 'delete': DeleteGroup($id); break;
		case 'addsubjectstogroup':
			AddSubjectsToGroup($subjectgroupid, $uids, $seriesids, $modality);
			ViewGroup($subjectgroupid, $measures, $columns, $groupmeasures);
			break;
		case 'addstudiestogroup':
			AddStudiesToGroup($studygroupid, $seriesids, $studyids, $modality);
			ViewGroup($studygroupid, $measures, $columns, $groupmeasures);
			break;
		case 'addseriestogroup':
			AddSeriesToGroup($seriesgroupid, $seriesids, $modality);
			ViewGroup($seriesgroupid, $measures, $columns, $groupmeasures);
			break;
		case 'viewimagingsummary':
			ViewImagingSummary($id);
			break;
		case 'removegroupitem':
			RemoveGroupItem($itemid);
			ViewGroup($id, $measures, $columns, $groupmeasures);
			break;
		case 'viewgroup':
			ViewGroup($id, $measures, $columns, $groupmeasures);
			break;
		case 'updatestudygroup':
			UpdateStudyGroup($id, $studylist);
			ViewGroup($id, $measures, $columns, $groupmeasures);
			break;
		default:
			DisplayGroupList();
			break;
	}


	/* ------------------------------------ functions ------------------------------------ */
	
	
	/* -------------------------------------------- */
	/* ------- AddGroup --------------------------- */
	/* -------------------------------------------- */
	function AddGroup($groupname, $grouptype, $owner) {
		/* perform data checks */
		/* get userid */
		$sqlstring = "select * from users where username = '$owner'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];

		/* insert the new group */
		$sqlstring = "insert ignore into groups (group_name, group_type, group_owner) values ('$groupname', '$grouptype', '$userid')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("$groupname added");
	}

	/* -------------------------------------------- */
	/* ------- AddSubjectsToGroup ----------------- */
	/* -------------------------------------------- */
	function AddSubjectsToGroup($groupid, $uids, $seriesids, $modality) {

		$numadded = 0;
		$numexisting = 0;
		/* if the request came from the subjects.php page */
		if (!empty($uids)) {
			foreach ($uids as $uid) {
				$sqlstring = "select subject_id from subjects where uid = '$uid'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$uidid = $row['subject_id'];

				/* check if its already in the db */
				$sqlstring  = "select * from group_data where group_id = $groupid and data_id = $uidid and modality = ''";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the uidids */
					$sqlstring = "insert into group_data (group_id, data_id) values ($groupid, $uidid)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$numadded++;
				}
			}
		}
		/* if the request came from the search.php page */
		if (!empty($seriesids)) {
			foreach ($seriesids as $seriesid) {
				/* get the study id for this seriesid/modality */
				$sqlstring = "select c.subject_id from ".$modality."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.".$modality."series_id = $seriesid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$uidid = $row['subject_id'];

				/* check if its already in the db */
				$sqlstring  = "select * from group_data where group_id = $groupid and data_id = $uidid and modality = ''";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the uidids */
					$sqlstring = "insert into group_data (group_id, data_id) values ($groupid, $uidid)";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$numadded++;
				}
			}
		}
		Notice("<b>$numadded</b> studies added<br><b>$numexisting</b> studies already in group");
	}

	
	/* -------------------------------------------- */
	/* ------- AddStudiesToGroup ------------------ */
	/* -------------------------------------------- */
	function AddStudiesToGroup($groupid, $seriesids, $studyids, $modality) {
		$modality = strtolower($modality);

		$numadded = 0;
		$numexisting = 0;
		if (is_array($seriesids)) {
			foreach ($seriesids as $seriesid) {
				/* get the study id for this seriesid/modality */
				$sqlstring = "select study_id from $modality" . "_series where $modality" . "series_id = $seriesid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$studyid = $row['study_id'];

				/* check if its already in the db */
				$sqlstring  = "select * from group_data where group_id = $groupid and data_id = $studyid and modality = '$modality'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the seriesids */
					$sqlstring = "insert into group_data (group_id, data_id, modality, date_added) values ($groupid, $studyid, '$modality', now())";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$numadded++;
				}
			}
		}

		if (is_array($studyids)) {
			foreach ($studyids as $studyid) {
				/* get the modality for this study */
				$sqlstring = "select study_modality from studies where study_id = $studyid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$modality = $row['modality'];

				/* check if its already in the db */
				$sqlstring  = "select * from group_data where group_id = $groupid and data_id = $studyid and modality = '$modality'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the studyids */
					$sqlstring = "insert into group_data (group_id, data_id, modality) values ($groupid, $studyid, '$modality')";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$numadded++;
				}
			}
		}
		Notice("<b>$numadded</b> studies added<br><b>$numexisting</b> studies already in group");
	}


	/* -------------------------------------------- */
	/* ------- AddSeriesToGroup ------------------- */
	/* -------------------------------------------- */
	function AddSeriesToGroup($groupid, $seriesids, $modality) {

		$numadded = 0;
		$numexisting = 0;
		foreach ($seriesids as $seriesid) {
			/* check if its already in the db */
			$sqlstring  = "select * from group_data where group_id = $groupid and data_id = $seriesid and modality = '$modality'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$numexisting++;
			}
			else {
				/* insert the seriesids */
				$sqlstring = "insert into group_data (group_id, data_id, modality) values ($groupid, $seriesid, '$modality')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$numadded++;
			}
		}
		Notice("<b>$numadded</b> series added<br><b>$numexisting</b> series already in group");
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateStudyGroup ------------------- */
	/* -------------------------------------------- */
	function UpdateStudyGroup($id, $studylist) {
		
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);

		if (trim($id) == "") {
			Error("ID blank");
			return;
		}

		/* start transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$studies = preg_split('/\s+/', $studylist);
		$studies = mysqli_real_escape_array($studies);
		$studies = array_unique($studies);

		/* delete all old group entries */
		$sqlstring = "delete from group_data where group_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		/* loop through all the studies and insert them */
		foreach ($studies as $study) {
			if (trim($study) == "") { continue; }
			
			$uid = substr($study,0,8);
			$studynum = substr($study,8);

			$sqlstring = "select b.study_id from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where d.uid = '$uid' AND b.study_num='$studynum'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$studyid = trim($row['study_id']);

				//echo "[$study] --> [$studyid]<br>";
			
				/* insert the studyids */
				$sqlstring = "insert into group_data (group_id, data_id) values ($id, $studyid)";
				//echo "$sqlstring<br>";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
			else {
				echo "Study [$study] not found. Possibly invaliud studynum?<br>";
			}
		}

		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
	}
	

	/* -------------------------------------------- */
	/* ------- RemoveGroupItem -------------------- */
	/* -------------------------------------------- */
	function RemoveGroupItem($itemid) {
		//PrintVariable($itemid,'ItemID');

		foreach ($itemid as $item) {
			$sqlstring = "delete from group_data where subjectgroup_id = $item";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			?><div align="center"><span class="message">Item <?=$item?> deleted</span></div><?
		}
		return;
	}


	/* -------------------------------------------- */
	/* ------- DeleteGroup ------------------------ */
	/* -------------------------------------------- */
	function DeleteGroup($id) {
		$sqlstring = "delete from groups where group_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- ViewGroup -------------------------- */
	/* -------------------------------------------- */
	function ViewGroup($id, $measures, $columns, $groupmeasures) {

		/* get the general group information */
		$sqlstring = "select * from groups where group_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupname = $row['group_name'];
		$grouptype = $row['group_type'];

		//PrintVariable($groupname);
		//PrintVariable($grouptype);
		
		if ($grouptype == 'series')
			ViewSeriesGroup($id, $groupname, $measures, $columns, $groupmeasures);
		if ($grouptype == 'study')
			ViewStudyGroup($id, $groupname, $measures, $columns, $groupmeasures);
		if ($grouptype == 'subject')
			ViewSubjectGroup($id, $groupname, $measures, $columns, $groupmeasures);
	}
	
	
	/* -------------------------------------------- */
	/* ------- ViewSeriesGroup -------------------- */
	/* -------------------------------------------- */
	function ViewSeriesGroup($id, $groupname, $measures, $columns, $groupmeasures) {

		/* get the general group information */
		$sqlstring = "select * from groups where group_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupname = $row['group_name'];
		$grouptype = $row['group_type'];

		?>
		<script>
			$(document).ready(function() { $("#studytable").tablesorter(); } );
		</script>

		<?
		/* (subject level) group statistics */
		$totalage = 0;
		$numage = 0;
		$totalweight = 0;
		$numweight = 0;
		$n = 0;

		/* get a distinct list of modalities... then get a list of series for each modality */
		$sqlstring = "select distinct(modality) from group_data where group_id = $id order by modality";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modalities[] = $row['modality'];
		}
		
		PrintVariable($modalities);
		foreach ($modalities as $modality) {
			$modality = strtolower($modality);
			/* get the demographics (series level) */
			$sqlstring = "select b.*, c.study_num, c.study_datetime, c.study_ageatscan, e.*, (datediff(b.series_datetime, e.birthdate)/365.25) 'age' from group_data a left join ".$modality."_series b on a.data_id = b.".$modality."series_id left join studies c on b.study_id = c.study_id left join enrollment d on c.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.group_id = 3 and a.modality = '".$modality."' and e.subject_id is not null";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			//PrintSQL($sqlstring);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyid = $row['study_id'];
				$studynum = $row['study_num'];
				$studydesc = $row['study_desc'];
				$studyalternateid = $row['study_alternateid'];
				$studymodality = $row['study_modality'];
				$studydatetime = $row['study_datetime'];
				$studyoperator = $row['study_operator'];
				$studyperformingphysician = $row['study_performingphysician'];
				$studysite = $row['study_site'];
				$studyinstitution = $row['study_institution'];
				$studynotes = $row['study_notes'];
				$subgroup = $row['enroll_subgroup'];
				$seriesnum = $row['series_num'];

				$subjectid = $row['subject_id'];
				$name = $row['name'];
				$birthdate = $row['birthdate'];
				$age = $row['age'];
				$gender = $row['gender'];
				$ethnicity1 = $row['ethnicity1'];
				$ethnicity2 = $row['ethnicity2'];
				$weight = $row['weight'];
				$handedness = $row['handedness'];
				$education = $row['education'];
				$uid = $row['uid'];

				$serieslist[] = "$uid$studynum" . "_$seriesnum";
				/* do some demographics calculations */
				$n++;
				if ($age > 0) {
					$totalage += $age;
					$numage++;
					$ages[] = $age;
				}
				if ($weight > 0) {
					$totalweight += $weight;
					$numweight++;
					$weights[] = $weight;
				}
				$genders{$gender}++;
				$educations{$education}++;
				$ethnicity1s{$ethnicity1}++;
				$ethnicity2s{$ethnicity2}++;
				$handednesses{$handedness}++;
			}
		}
		/* calculate some stats */
		if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
		if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
		if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
		if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }
			
		?>
		<div class="ui top attached grey segment">
			<div class="ui two column grid">
				<div class="ui column">
					<h2 class="ui header"><?=$groupname?></h2>
				</div>
				<div class="ui right aligned column">
					<button class="ui tiny red button">Delete Group</button>
				</div>
			</div>
			<div class="ui grid">
				<div class="ui four wide column">
					<h3 class="ui header">Summary</h3>
					<?
						DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight);
					?>
						<br>
					<div class="ui styled accordion">
						<div class="title">
							<i class="dropdown icon"></i>
							SQL
						</div>
						<div class="content">
							<tt><?=PrintSQL($sqlstring)?></tt>
						</div>
					</div>
				</div>
				<div class="ui four wide column">
					<h3 class="ui header">Options</h3>
					<p>No options available</p>
				</div>
				<div class="ui eight wide column">
					<h3 class="ui header">Group members</h3>
					
					<form class="ui form" action="groups.php" method="get">
						<textarea><?=$serieslist?></textarea>
						<br><br>
						<div align="right">
							<button class="ui primary button">Save</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="ui bottom attached segment">

		<table class="ui celled selectable grey very compact table">
			<thead>
				<th>Initials</th>
				<th>UID</th>
				<th>DOB</th>
				<th>Age<br><span class="tiny" style="font-weight: normal;">from DICOM header</span></th>
				<th>Age<br><span class="tiny" style="font-weight: normal;">calculated</span></th>
				<th>Sex</th>
				<th>Sub-group</th>
				<th>Weight</th>
				<th>Alt UIDs</th>
				<th>Study ID</th>
				<th>Description/Protocol</th>
				<th>Modality</th>
				<th>Date/time</th>
				<th>Series #</th>
				<th>Remove<br>from group</th>
			</thead>
			<?
			/* get a distinct list of modalities... then get a list of series for each modality */

			/* reset the result pointer to 0 to iterate through the results again */
			foreach ($modalities as $modality) {
				$modality = strtolower($modality);
				/* get the demographics (series level) */
				$sqlstring = "select b.*, c.study_num, c.study_datetime, c.study_ageatscan, e.*, (datediff(b.series_datetime, e.birthdate)/365.25) 'age' from group_data a left join ".$modality."_series b on a.data_id = b.".$modality."series_id left join studies c on b.study_id = c.study_id left join enrollment d on c.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.group_id = 3 and a.modality = '".$modality."' and e.subject_id is not null";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				mysqli_data_seek($result,0);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$seriesdesc = $row['series_desc'];
					$seriesprotocol = $row['series_protocol'];
					$seriesdatetime = $row['series_datetime'];
					$seriesnum = $row['series_num'];
					$studynum = $row['study_num'];
					$studydatetime = $row['study_datetime'];
					$studyage = $row['study_ageatscan'];
					$seriesmodality = strtoupper($modality);

					$itemid = $row['subjectgroup_id'];
					$subjectid = $row['subject_id'];
					$name = $row['name'];
					$birthdate = $row['birthdate'];
					//$age = $row['age'];
					$gender = $row['gender'];
					$ethnicity1 = $row['ethnicity1'];
					$ethnicity2 = $row['ethnicity2'];
					$weight = $row['weight'];
					$handedness = $row['handedness'];
					$education = $row['education'];
					$uid = $row['uid'];
					/* get list of alternate subject UIDs */
					$altuids = GetAlternateUIDs($subjectid,'');

					list($studyAge, $calcStudyAge) = GetStudyAge($birthdate, $studyage, $studydatetime);
					
					if ($studyAge == null)
						$studyAge = "<span class='tiny'>null</span>";
					else
						$studyAge = number_format($studyAge,1);

					if ($calcStudyAge == null)
						$calcStudyAge = "<span class='tiny'>null</span>";
					else
						$calcStudyAge = number_format($calcStudyAge,1);
					
					$parts = explode("^",$name);
					$name = substr($parts[1],0,1) . substr($parts[0],0,1);
					?>
					<tr>
						<td><?=$name?></td>
						<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
						<td><?=$birthdate?></td>
						<td><?=$studyAge?></td>
						<td><?=$calcStudyAge?></td>
						<? if (!in_array(strtoupper($gender),array('M','F','O'))) {$color = "red";} else {$color="black";} ?>
						<td style="color:<?=$color?>"><?=$gender?></td>
						<td style="font-size:8pt"><?=$subgroup?></td>
						<? if ($weight <= 0) {$color = "red";} else {$color="black";} ?>
						<td style="color:<?=$color?>"><?=number_format($weight,1)?>kg</td>
						<td style="font-size:8pt"><?=implode2(', ',$altuids)?></td>
						<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
						<td style="font-size:8pt"><?=$seriesdesc?> <?=$seriesprotocol?></td>
						<td><?=$seriesmodality?></td>
						<td style="font-size:8pt"><?=$seriesdatetime?></td>
						<td><?=$seriesnum?></td>
						<td align="center"><a href="groups.php?action=removegroupitem&itemid=<?=$itemid?>&id=<?=$id?>" style="color:red"><i class="trash icon"></i></a></td>
					</tr>
					<?
				}
			}
			?>
		</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ViewStudyGroup --------------------- */
	/* -------------------------------------------- */
	function ViewStudyGroup($id, $groupname, $measures, $columns, $groupmeasures) {

		/* get the general group information */
		$sqlstring = "select * from groups where group_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupname = $row['group_name'];
		$grouptype = $row['group_type'];

		?>
		<script>
			$(document).ready(function() { $("#studytable").tablesorter(); } );
		</script>

		<?
		/* (subject level) group statistics */
		$totalage = 0;
		$numage = 0;
		$totalweight = 0;
		$numweight = 0;
		$n = 0;
		
		$sqlstring = "select a.subjectgroup_id, b.*, (datediff(now(), birthdate)/365.25) 'age' from group_data a left join subjects b on a.data_id = b.subject_id where a.group_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$name = $row['name'];
			$birthdate = $row['birthdate'];
			$age = $row['age'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$weight = $row['weight'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$uid = $row['uid'];
			$studylist[] = $studyid;

			/* do some demographics calculations */
			$n++;
			if ($age > 0) {
				$totalage += $age;
				$numage++;
				$ages[] = $age;
			}
			if ($weight > 0) {
				$totalweight += $weight;
				$numweight++;
				$weights[] = $weight;
			}
			$genders{$gender}++;
			$educations{$education}++;
			$ethnicity1s{$ethnicity1}++;
			$ethnicity2s{$ethnicity2}++;
			$handednesses{$handedness}++;
		}
		if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
		if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
		if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
		if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }
		
		?>
		<div class="ui top attached grey segment">
			<div class="ui two column grid">
				<div class="ui column">
					<h2 class="ui header"><?=$groupname?></h2>
				</div>
				<div class="ui right aligned column">
					<button class="ui tiny red button">Delete Group</button>
				</div>
			</div>
			<div class="ui grid">
				<div class="ui five wide column">
					<h3 class="ui header">Summary</h3>
					<div class="ui styled accordion">
						<div class="active title">
							<i class="dropdown icon"></i>
							Group contains <b><?=$n?></b> studies
						</div>
						<div class="active content">
							<?DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight);?>
						</div>
						<div class="title">
							<i class="dropdown icon"></i>
							SQL
						</div>
						<div class="content">
							<tt><?=PrintSQL($sqlstring)?></tt>
						</div>
						<div class="title">
							<i class="dropdown icon"></i>
							MR summary
						</div>
						<div class="content">
						<?
							DisplayMRProtocolSummary($studylist);
						?>
						</div>
					</div>
				</div>
				<div class="ui five wide column">
					<h3 class="ui header">Options</h3>
					<a href="groups.php?action=viewimagingsummary&id=<?=$id?>">Imaging Summary</a><br>
					<br>
					<a href="groups.php?action=viewgroup&id=<?=$id?>&measures=all">Include measures</a><br>
					<a href="groups.php?action=viewgroup&id=<?=$id?>&measures=all&columns=min">Include measures and only UID</a><br>
					<a href="groups.php?action=viewgroup&id=<?=$id?>&measures=all&columns=min&groupmeasures=byvalue">Include measures and only UID and group measures by value</a>
				</div>
				<div class="ui six wide column">
					<h3 class="ui header">Edit group members</h3>
					<form action="groups.php" method="post" class="ui form">
					<input type="hidden" name="action" value="updatestudygroup">
					<input type="hidden" name="id" value="<?=$id?>">
					<?
						$studies = "";
						$sqlstring = "select a.subjectgroup_id, d.uid, b.study_num from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id order by d.uid,b.study_num";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$studynum = $row['study_num'];
							$uid = $row['uid'];
							$studies .=  $uid . $studynum . "\n";
						}
						?>
						<textarea name='studylist' style="font-family: monospace; font-size: larger;"><?=$studies?></textarea>
						<br><br>
						<div align="right">
							<button class="ui primary button" type="submit">Update</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="ui bottom attached segment">
		<?

		/* ------------------ study group type ------------------- */
		if ($grouptype == "study") {
		$csv = "";

		/* get the demographics (study level) */
		$sqlstring = "select c.enroll_subgroup, b.study_id, b.study_ageatscan,d.*, (datediff(b.study_datetime, d.birthdate)/365.25) 'age' from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id group by d.uid order by d.uid,b.study_num";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			$studynum = $row['study_num'];
			$studydesc = $row['study_desc'];
			$studyalternateid = $row['study_alternateid'];
			$studymodality = $row['study_modality'];
			$studyageatscan = $row['study_ageatscan'];
			$studydatetime = $row['study_datetime'];
			$studyoperator = $row['study_operator'];
			$studyperformingphysician = $row['study_performingphysician'];
			$studysite = $row['study_site'];
			$studyinstitution = $row['study_institution'];
			$studynotes = $row['study_notes'];
			$subgroup = $row['enroll_subgroup'];
			$studylist[] = $studyid;

			$subjectid = $row['subject_id'];
			$name = $row['name'];
			$birthdate = $row['birthdate'];
			$age = $row['age'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$weight = $row['weight'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$uid = $row['uid'];
			$subjectids[] = $subjectid;
			/* do some demographics calculations */
			$n++;

			if ($studyageatscan > 0) {
				$age = $studyageatscan;
			}

			if ($age > 0) {
				$totalage += $age;
				$numage++;
				$ages[] = $age;
			}
			if ($weight > 0) {
				$totalweight += $weight;
				$numweight++;
				$weights[] = $weight;
			}
			$genders{$gender}++;
			$educations{$education}++;
			$ethnicity1s{$ethnicity1}++;
			$ethnicity2s{$ethnicity2}++;
			$handednesses{$handedness}++;
		}
		if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
		if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
		if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
		if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }

		if ($measures == "all") {
			$sqlstringD = "select a.subject_id, b.enrollment_id, c.*, d.measure_name from measures c join measurenames d on c.measurename_id = d.measurename_id left join enrollment b on c.enrollment_id = b.enrollment_id join subjects a on a.subject_id = b.subject_id where a.subject_id in (" . implode2(",", $subjectids) . ")";
			$resultD = MySQLiQuery($sqlstringD,__FILE__,__LINE__);

			if ($groupmeasures == "byvalue") {
				$mnames = array('ANTDX','AVDDX','AX1Com1_Code','AX1Com2_Code','AX1Com3_Code','AX1Com4_Code','AX1Com5_Code','AX1Com6_Code','AX1Com7_Code','AX1Pri_Code','AXIIDX','BRDDX','DPNDX','DSM-Axis','DSM-Axis1','DSM-Axis2','DSM-Axis295.3','DSM-Axis304.3','DSM-AxisV71.09','DSM_IV_TR','DXGROUP_1','DX_GROUP','MiniDxn','MiniDxnFollowUp','NARDX','OBCDX','PARDX','ProbandGroup','Psychosis','relnm1','SAsubtype','SCZDX','status','SubjectType','SZTDX');
				while ($rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC)) {
					$subjectid = $rowD['subject_id'];
					$measurename = $rowD['measure_name'];
					if (in_array($measurename,$mnames)) {
						if ($rowD['measure_type'] == 's') {
							$value = strtolower(trim($rowD['measure_valuestring']));
						}
						else {
							$value = strtolower(trim($rowD['measure_valuenum']));
						}

						if (is_numeric(substr($value,0,6))) {
							$value = substr($value,0,6);
						}
						elseif (is_numeric(substr($value,0,5))) {
							$value = substr($value,0,5);
						}
						elseif (is_numeric(substr($value,0,4))) {
							$value = substr($value,0,4);
						}
						elseif (is_numeric(substr($value,0,3))) {
							$value = substr($value,0,3);
						}
						elseif (is_numeric(substr($value,1,5))) {
							$value = substr($value,1,5);
						}
						elseif (substr($value,0,3) == "xxx") {
							$value = "xxx";
						}

						$measuredata[$subjectid][$value] = 1;
						$measurenames[] = $value;
					}
				}
				$measurenames = array_unique($measurenames);
				natsort($measurenames);
			}
			else {
				while ($rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC)) {
					if ($rowD['measure_type'] == 's') {
						$measuredata[$rowD['subject_id']][$rowD['measure_name']]['value'][] = $rowD['measure_valuestring'];
					}
					else {
						$measuredata[$rowD['subject_id']][$rowD['measure_name']]['value'][] = $rowD['measure_valuenum'];
					}
					$measuredata[$rowD['subject_id']][$rowD['measure_name']]['notes'][] = $rowD['measure_notes'];
					$measurenames[] = $rowD['measure_name'];
				}
				$measurenames = array_unique($measurenames);
				natcasesort($measurenames);
			}
		}

		/* setup the CSV header */
		if ($columns == "min") {
			$csv = "UID";
		}
		else {
			$csv = "Initials,UID,StudyAge,CalcStudyAge,Sex,SubGroup,Weight,Handedness,Education,AltUIDs,StudyID,Description,AltStudyID,Modality,StudyDate,Operator,Physician,Site";
		}

		?>
			<table>
				<tr>
					<td valign="top" style="padding-right:20px">
					</td>
				</tr>
				<tr>
					<td valign="top">
						<span class="tiny">Click columns to sort. May be slow for large tables</span>

						<form action="groups.php" method="post">
							<input type="hidden" name="id" value="<?=$id?>">
							<input type="hidden" name="action" value="removegroupitem">

							<table id="studytable" class="ui small celled selectable grey very compact table">
								<thead>
								<tr>
									<? if ($columns != "min") { ?>
										<th>Initials</th>
									<? } ?>
									<th>UID</th>
									<? if ($columns != "min") { ?>
										<th>DOB</th>
										<th>StudyDate</th>
										<th>Age<br><span class="tiny">header</span></th>
										<th>Age<br><span class="tiny">computed</span></th>
										<th>Sex</th>
										<th>Ethnicities</th>
										<th>SubGroup</th>
										<th>VisitType</th>
										<th>Weight</th>
										<th>Handedness</th>
										<th>Education</th>
										<th>Alt UIDs</th>
										<th>Study ID</th>
										<th>Description</th>
										<th>Alternate Study ID</th>
										<th>Modality</th>
										<th>Operator</th>
										<th>Physician</th>
										<th>Site</th>
									<? } ?>
									<?
									if (count($measurenames) > 0) {
										foreach ($measurenames as $measurename) {
											echo "<th>$measurename</th>";
											$csv .= ",\"$measurename\"";
										}
									}
									?>
									<th>Remove</th>
								</tr>
								</thead>
								<tbody>
								<?
								/* reset the result pointer to 0 to iterate through the results again */
								$sqlstring = "select a.subjectgroup_id, c.enroll_subgroup, b.*, d.*, (datediff(b.study_datetime, d.birthdate)/365.25) 'age' from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id order by d.uid,b.study_num";
								$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								//mysqli_data_seek($result,0);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$studyid = $row['study_id'];
									$studynum = $row['study_num'];
									$studydesc = $row['study_desc'];
									$studyalternateid = $row['study_alternateid'];
									$studymodality = $row['study_modality'];
									$studyageatscan = $row['study_ageatscan'];
									$studydatetime = $row['study_datetime'];
									$studyoperator = $row['study_operator'];
									$studyperformingphysician = $row['study_performingphysician'];
									$studysite = $row['study_site'];
									$studyinstitution = $row['study_institution'];
									$studynotes = $row['study_notes'];
									$studyvisittype = $row['study_type'];
									$studyweight = $row['study_weight'];
									$subgroup = $row['enroll_subgroup'];

									$itemid = $row['subjectgroup_id'];
									$subjectid = $row['subject_id'];
									$name = $row['name'];
									$birthdate = $row['birthdate'];
									$age = $row['age'];
									$gender = $row['gender'];
									$ethnicity1 = $row['ethnicity1'];
									$ethnicity2 = $row['ethnicity2'];
									$weight = $row['weight'];
									$handedness = $row['handedness'];
									$education = $row['education'];
									$uid = $row['uid'];
									if ($age <= 0) {
										$age = $studyageatscan;
									}
									
									list($studyAge, $calcStudyAge) = GetStudyAge($birthdate, $studyageatscan, $studydatetime);
									
									if ($studyAge == null)
										$studyAge = "-";
									else
										$studyAge = number_format($studyAge,1);

									if ($calcStudyAge == null)
										$calcStudyAge = "-";
									else
										$calcStudyAge = number_format($calcStudyAge,1);
									
									/* get list of alternate subject UIDs */
									$altuids = GetAlternateUIDs($subjectid,'');

									$parts = explode("^",$name);
									$name = substr($parts[1],0,1) . substr($parts[0],0,1);

									if ($columns == "min") {
										$csv .= "\n\"$uid\"";
									}
									else {
										$csv .= "\n\"$name\",\"$uid\",\"$studyAge\",\"$calcStudyAge\",\"$gender\",\"$subgroup\",\"$weight\",\"$handedness\",\"$education\",\"" . implode2(', ',$altuids) . "\",\"$uid$studynum\",\"$studydesc\",\"$studyalternateid\",\"$studymodality\",\"$studydatetime\",\"$studyoperator\",\"$studyperformingphysician\",\"$studysite\"";
									}
									?>
									<tr>
										<? if ($columns != "min") { ?>
											<td><?=$name?></td>
										<? } ?>
										<td class="tt"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
										<? if ($columns != "min") { ?>
											<td><?=$birthdate?></td>
											<td><?=$studydatetime?></td>
											<td><?=$studyAge?></td>
											<td><?=$calcStudyAge?></td>
											<? if (!in_array(strtoupper($gender),array('M','F','O'))) {$color = "red";} else {$color="black";} ?>
											<td style="color:<?=$color?>"><?=$gender?></td>
											<td style="font-size:8pt"><?=$ethnicity1?> <?=$ethnicity2?></td>
											<td style="font-size:8pt"><?=$subgroup?></td>
											<td style="font-size:8pt"><?=$studyvisittype?></td>
											<? if ($studyweight <= 0) {$color = "red";} else {$color="black";} ?>
											<td style="color:<?=$color?>"><?=number_format($studyweight,1)?>kg</td>
											<td><?=$handedness?></td>
											<td><?=$education?></td>
											<td style="font-size:8pt"><?=implode2(', ',$altuids)?></td>
											<td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
											<td style="font-size:8pt"><?=$studydesc?></td>
											<td><?=$studyalternateid?></td>
											<td><?=$studymodality?></td>
											<td><?=$studyoperator?></td>
											<td><?=$studyperformingphysician?></td>
											<td style="font-size:8pt"><?=$studysite?></td>
										<? } ?>
										<?
										if (count($measurenames) > 0) {
											if ($groupmeasures == "byvalue") {
												foreach ($measurenames as $measurename) {
													$csv .= ",\"" . $measuredata[$subjectid][$measurename] . "\"";
													?>
													<td class="seriesrow">
														<?
														if (isset($measuredata[$subjectid][$measurename])) {
															echo $measuredata[$subjectid][$measurename];
														}
														?>
													</td>
													<?
												}
											}
											else {
												foreach ($measurenames as $measure) {
													$csv .= ",\"" . $measuredata[$subjectid][$measure]['value'] . "\"";
													?>
													<td class="seriesrow">
														<?
														if (isset($measuredata[$subjectid][$measure]['value'])) {
															foreach ($measuredata[$subjectid][$measure]['value'] as $value) {
																echo "$value<br>";
															}
														}
														?>
													</td>
													<?
												}
											}
										}
										?>
										<!--<td><a href="groups.php?action=removegroupitem&itemid=<?=$itemid?>&id=<?=$id?>" style="color:red"><i class="trash icon"></i></a></td>-->
										<td><input type="checkbox" name="itemid[]" value="<?=$itemid?>"></td>
									</tr>
									<?
								}
								?>
								<tr>
									<td colspan="100" align="right">
										<input type="submit" value="Remove">
						</form>
					</td>
				</tr>
				</tbody>
			</table>
			</td>
			</tr>
			</table>
			<?

			/* ---------- generate csv file ---------- */
			$filename = $groupname . "_" . GenerateRandomString(10) . ".csv";
			file_put_contents("/tmp/" . $filename, $csv);
			?>
			<div width="50%" align="center" style="background-color: #FAF8CC; padding: 5px;">
				Download .csv file <a href="download.php?type=file&filename=<?="/tmp/$filename";?>"><img src="images/download16.png"></a>
			</div>
		</div>
		<?
		}
	}


	/* -------------------------------------------- */
	/* ------- ViewSubjectGroup ------------------- */
	/* -------------------------------------------- */
	function ViewSubjectGroup($id, $groupname, $measures, $columns, $groupmeasures) {

		/* get the general group information */
		$sqlstring = "select * from groups where group_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupname = $row['group_name'];
		$grouptype = $row['group_type'];

		?>
		<script>
			$(document).ready(function() { $("#studytable").tablesorter(); } );
		</script>

		<?
		/* (subject level) group statistics */
		$totalage = 0;
		$numage = 0;
		$totalweight = 0;
		$numweight = 0;
		$n = 0;

		/* get the actual group data (subject level) */
		$sqlstring = "select a.subjectgroup_id, b.*, (datediff(now(), birthdate)/365.25) 'age' from group_data a left join subjects b on a.data_id = b.subject_id where a.group_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = $row['subject_id'];
			$name = $row['name'];
			$birthdate = $row['birthdate'];
			$age = $row['age'];
			$gender = $row['gender'];
			$ethnicity1 = $row['ethnicity1'];
			$ethnicity2 = $row['ethnicity2'];
			$weight = $row['weight'];
			$handedness = $row['handedness'];
			$education = $row['education'];
			$uid = $row['uid'];

			/* do some demographics calculations */
			$n++;
			if ($age > 0) {
				$totalage += $age;
				$numage++;
				$ages[] = $age;
			}
			if ($weight > 0) {
				$totalweight += $weight;
				$numweight++;
				$weights[] = $weight;
			}
			$genders{$gender}++;
			$educations{$education}++;
			$ethnicity1s{$ethnicity1}++;
			$ethnicity2s{$ethnicity2}++;
			$handednesses{$handedness}++;
		}
		if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
		if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
		if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
		if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }

		?>
		<div class="ui top attached grey segment">
			<div class="ui two column grid">
				<div class="ui column">
					<h2 class="ui header"><?=$groupname?></h2>
				</div>
				<div class="ui right aligned column">
					<button class="ui tiny red button">Delete Group</button>
				</div>
			</div>
			<div class="ui grid">
				<div class="ui four wide column">
					Summary
					<?
					DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight);
					?>
					<div class="ui styled accordion">
						<div class="title">
							<i class="dropdown icon"></i>
							SQL
						</div>
						<div class="content">
							<tt><?=PrintSQL($sqlstring)?></tt>
						</div>
					</div>
				</div>
				<div class="ui four wide column">
					Options
				</div>
				<div class="ui eight wide column">
					Group members
				</div>
			</div>
		</div>
		<div class="ui bottom attached segment">
			<form action="groups.php" method="post">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="action" value="removegroupitem">
				<table class="ui celled selectable grey very compact table">
					<thead>
						<th>Initials</th>
						<th>UID</th>
						<th>Age<br><span class="tiny">current</span></th>
						<th>Sex</th>
						<th>Ethnicity 1</th>
						<th>Ethnicity 2</th>
						<th>Weight</th>
						<th>Handedness</th>
						<th>Education</th>
						<th>Alt UIDs</th>
						<th>Remove<br>from group</th>
					</thead>
					<?
					/* reset the result pointer to 0 to iterate through the results again */
					mysqli_data_seek($result,0);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$itemid = $row['subjectgroup_id'];
						$subjectid = $row['subject_id'];
						$name = $row['name'];
						$birthdate = $row['birthdate'];
						$age = $row['age'];
						$gender = $row['gender'];
						$ethnicity1 = $row['ethnicity1'];
						$ethnicity2 = $row['ethnicity2'];
						$weight = $row['weight'];
						$handedness = $row['handedness'];
						$education = $row['education'];
						$uid = $row['uid'];

						/* get list of alternate subject UIDs */
						$altuids = GetAlternateUIDs($subjectid,'');

						$parts = explode("^",$name);
						$name = substr($parts[1],0,1) . substr($parts[0],0,1);
						?>
						<tr>
							<td><?=$name?></td>
							<td>
								<a href="subjects.php?id=<?=$subjectid?>" style="font-family: monospace; font-size: larger;"><?=$uid?></a>
							</td>
							<? if ($age <= 0) {$color = "red";} else {$color="black";} ?>
							<td style="color:<?=$color?>"><?=number_format($age,1)?>Y</td>
							<? if (!in_array(strtoupper($gender),array('M','F','O'))) {$color = "red";} else {$color="black";} ?>
							<td style="color:<?=$color?>"><?=$gender?></td>
							<td><?=$ethnicitiy1?></td>
							<td><?=$ethnicitiy1?></td>
							<td><?=number_format($weight,1)?>kg</td>
							<td><?=$handedness?></td>
							<td><?=$education?></td>
							<td><span style="font-family: monospace; font-size: larger;"><?=implode(', ',$altuids)?></span></td>
							<td><input type="checkbox" name="itemid[]" value="<?=$itemid?>"></td>
						</tr>
						<?
					}
					?>
					<tr>
						<td colspan="100" align="right">
							<input type="submit" value="Remove">
						</td>
					</tr>
				</table>
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRProtocolSummary ----------- */
	/* -------------------------------------------- */
	function DisplayMRProtocolSummary($studylist) {
		$studylist = array_filter($studylist);
		$studies = implode(",",$studylist);

		if (trim($studies) == "") {
			return;
		}
		$sqlstring = "SELECT series_altdesc, series_tr, series_te, series_flip, phaseencodedir, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, count(*) 'count' FROM `mr_series` where study_id in ($studies) and is_derived <> 1 group by series_altdesc, series_tr, series_te, series_flip, phaseencodedir, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?>
		<details>
			<summary>MR protocol summary</summary>
			<table class="ui very compact small celled grey table">
				<thead>
				<th>Desc</th>
				<th>TR</th>
				<th>TE</th>
				<th>Flip</th>
				<th>Phase dir</th>
				<th>Phase dir pos.</th>
				<th>Spacing X</th>
				<th>Spacing Y</th>
				<th>Spacing Z (center of slices)</th>
				<th>Rows</th>
				<th>Cols</th>
				<th>Slices</th>
				<th>Count</th>
				</thead>
				<tbody>
				<?
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$seriesdesc = $row['series_altdesc'];
					$series_tr = $row['series_tr'];
					$series_te = $row['series_te'];
					$series_flip = $row['series_flip'];
					$phaseencodedir = $row['phaseencodedir'];
					$PhaseEncodingDirectionPositive = $row['PhaseEncodingDirectionPositive'];
					$series_spacingx = $row['series_spacingx'];
					$series_spacingy = $row['series_spacingy'];
					$series_spacingz = $row['series_spacingz'];
					$img_rows = $row['img_rows'];
					$img_cols = $row['img_cols'];
					$img_slices = $row['img_slices'];
					$count = $row['count'];
					?>
					<tr>
						<td><?=$seriesdesc?></td>
						<td><?=$series_tr?></td>
						<td><?=$series_te?></td>
						<td><?=$series_flip?></td>
						<td><?=$phaseencodedir?></td>
						<td><?=$PhaseEncodingDirectionPositive?></td>
						<td><?=$series_spacingx?></td>
						<td><?=$series_spacingy?></td>
						<td><?=$series_spacingz?></td>
						<td><?=$img_rows?></td>
						<td><?=$img_cols?></td>
						<td><?=$img_slices?></td>
						<td><?=$count?></td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>
		</details>
		<?
	}
	/* -------------------------------------------- */
	/* ------- DisplayDemographicsTable ----------- */
	/* -------------------------------------------- */
	function DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight) {
		?>
		<table class="ui attached very basic very compact collapsing celled table">
			<tr>
				<td>N</td>
				<td><?=$n?></td>
			</tr>
			<tr>
				<td>Age<br><span class="tiny">computed from<br><?=$numage?> non-zero ages</span></td>
				<td><?=number_format($avgage,1)?>Y <span class="small">&plusmn;<?=number_format($varage,1)?>Y</span></td>
			</tr>
			<tr>
				<td>Sex</td>
				<td>
					<?
					foreach ($genders as $key => $value) {
						$pct = number_format(($value/$n)*100, 1);
						echo "$key: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
					}
					?>
				</td>
			</tr>
			<!--<tr>
				<td>Ethnicity 1</td>
				<td>
					<?
					//print_r($educations);
					foreach ($ethnicity1s as $key => $value) {
						$key = "$key";
						switch ($key) {
							case "": $ethnicity1 = "Not specified"; break;
							case "hispanic": $ethnicity1 = "Hispanic/Latino"; break;
							case "nothispanic": $ethnicity1 = "Not hispanic/Latino"; break;
						}
						$pct = number_format(($value/$n)*100, 1);
						echo "$ethnicity1: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
					}
					?>
				</td>
			</tr>
			<tr>
				<td>Ethnicity 2</td>
				<td>
					<?
					//print_r($educations);
					foreach ($ethnicity2s as $key => $value) {
						$key = "$key";
						switch ($key) {
							case "": $ethnicity2 = "Not specified"; break;
							case "indian": $ethnicity2 = "American Indian/Alaska Native"; break;
							case "asian": $ethnicity2 = "Asian"; break;
							case "black": $ethnicity2 = "Black/African American"; break;
							case "islander": $ethnicity2 = "Hawaiian/Pacific Islander"; break;
							case "white": $ethnicity2 = "White"; break;
							case "mixed": $ethnicity2 = "Mixed"; break;
						}
						$pct = number_format(($value/$n)*100, 1);
						echo "$ethnicity2: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
					}
					?>
				</td>
			</tr>
			<tr>
				<td>Education</td>
				<td>
					<?
					//print_r($educations);
					foreach ($educations as $key => $value) {
						$key = "$key";
						if (trim($key == "")) { $education = "Not specified"; }
						else {
							switch ($key) {
								case "0": $education = "Unknown"; break;
								case "1": $education = "Grade School"; break;
								case "2": $education = "Middle School"; break;
								case "3": $education = "High School/GED"; break;
								case "4": $education = "Trade School"; break;
								case "5": $education = "Associates Degree"; break;
								case "6": $education = "Bachelors Degree"; break;
								case "7": $education = "Masters Degree"; break;
								case "8": $education = "Doctoral Degree"; break;
							}
						}

						$pct = number_format(($value/$n)*100, 1);
						echo "$education: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
					}
					?>
				</td>
			</tr>
			<tr>
				<td>Handedness</td>
				<td>
					<?
					//print_r($educations);
					foreach ($handednesses as $key => $value) {
						$key = "$key";
						switch ($key) {
							case "": $handedness = "Not specified"; break;
							case "U": $handedness = "Unknown"; break;
							case "R": $handedness = "Right"; break;
							case "L": $handedness = "Left"; break;
							case "A": $handedness = "Ambidextrous"; break;
						}
						$pct = number_format(($value/$n)*100, 1);
						echo "$handedness: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
					}
					?>
				</td>
			</tr>
			<tr>
				<td>Weight<br><span class="tiny">computed from<br>non-zero weights</span></td>
				<td><?=number_format($avgweight,1)?>kg <span class="small">&plusmn;<?=number_format($varweight,1)?>kg</span></td>
			</tr>-->
		</table>
		<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayGroupList ------------------- */
	/* -------------------------------------------- */
	function DisplayGroupList() {

		?>
		<div class="ui container">

			<div class="ui grid">
				<div class="six wide column">
					<h1 class="ui header">Groups</h1>
				</div>
				<div class="ten wide column" align="right">
					<form action="groups.php" method="post" name="theform" id="theform">
					<input type="hidden" name="action" value="add">
					<div class="ui labeled action input">
						<input type="text" name="groupname" placeholder="Group name" required>
						<select name="grouptype" class="ui selection dropdown" required>
							<option value="">(select group type)
							<option value="subject">Subject
							<option value="study">Study
							<option value="series">Series
						</select>
						<button type="submit" class="ui button primary"><i class="plus square outline icon"></i> Create Group</button>
					</div>
					</form>
				</div>
			</div>
			
			<table class="ui small celled selectable grey very compact table">
				<thead>
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Owner</th>
					<th>Group size</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<?
				$sqlstring = "select a.*, b.username 'ownerusername', b.user_fullname 'ownerfullname' from groups a left join users b on a.group_owner = b.user_id order by a.group_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['group_id'];
					$name = $row['group_name'];
					$ownerusername = $row['ownerusername'];
					$grouptype = $row['group_type'];

					$sqlstring2 = "select count(*) 'count' from group_data where group_id = $id";
					$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
					$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
					$count = $row2['count'];
					?>
					<tr style="<?=$style?>">
						<td><a href="groups.php?action=viewgroup&id=<?=$id?>"><?=$name?></a></td>
						<td><?=$grouptype?></td>
						<td><?=$ownerusername?></td>
						<td><?=$count?></td>
						<td align="right">
							<? if ($ownerusername == $GLOBALS['username']) { ?>
								<a href="groups.php?action=delete&id=<?=$id?>" style="color:red"><i class="trash icon"></i></a>
							<? } ?>
						</td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- ViewImagingSummary ----------------- */
	/* -------------------------------------------- */
	function ViewImagingSummary($id) {
		
		$sqlstring = "select a.subjectgroup_id, d.uid, d.birthdate, d.gender, b.study_datetime, b.study_ageatscan, b.study_num, b.study_id from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyid = $row['study_id'];
			if (($studyid != "") && (strtolower($studyid) != "null")) {
				/* get list of series for this study */
				$studies[$studyid]['uid'] = $row['uid'];
				$studies[$studyid]['sex'] = $row['gender'];
				$studies[$studyid]['studynum'] = $row['study_num'];

				list($studyAge, $calcStudyAge) = GetStudyAge($row['birthdate'], $row['study_ageatscan'], $row['study_datetime']);
				$studies[$studyid]['studyage'] = $studyAge;
				$studies[$studyid]['calcstudyage'] = $calcStudyAge;
				

				$sqlstringA = "select b.* from mr_series a left join bids_mapping b on a.series_desc = b.protocolname where a.study_id = $studyid and a.series_desc <> ''";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$protocol = $rowA['shortname'];
					//$studies[$studyid]['protocols'][$protocol] = "&#10004;";
					$studies[$studyid]['protocols'][$protocol] = 1;
					$protocols[$protocol] = 1;
				}
			}
		}
		echo "Loaded data<br>";

		?>
		<table border=1>
			<thead>
				<th>UID</th>
				<th>Sex</th>
				<th>Age</th>
				<th>CalcAge</th>
				<?
				foreach ($protocols as $prot => $val) {
					?><th><?=$prot?></th><?
				}
				?>
			</thead>
		<?
		foreach ($studies as $studyid => $study) {
			?>
			<tr>
				<td><?=$study['uid']?></td>
				<td><?=$study['sex']?></td>
				<td><?=number_format($study['studyage'], 1)?></td>
				<td><?=number_format($study['calcstudyage'], 1)?></td>
				<?
				foreach ($protocols as $prot => $val) {
					?><td><?=$study['protocols'][$prot]?></td><?
				}
				?>
			</tr>
			<?
		}
		?></table><?
		
	}
	
?>
<? include("footer.php") ?>

