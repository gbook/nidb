<?
 // ------------------------------------------------------------------------------
 // NiDB enrollment.php
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
		<title>NiDB - Enrollment</title>
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
	$id = GetVariable("id");
	$enrollmentid = GetVariable("enrollmentid");
	$completed = GetVariable("completed");
	$enrollgroup = GetVariable("enrollgroup");
	$enrollstatus = GetVariable("enrollstatus");
	$tags = GetVariable("tags");

	if (trim($enrollmentid) == "")
		$enrollmentid = $id;
	
	/* determine action */
	switch ($action) {
		case 'update':
			UpdateEnrollment($enrollmentid, $completed, $enrollgroup, $enrollstatus, $tags);
			DisplayEnrollment($enrollmentid);
			break;
		case 'displayenrollment':
			DisplayEnrollment($enrollmentid);
			break;
		default:
			DisplayEnrollment($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateEnrollment ------------------- */
	/* -------------------------------------------- */
	function UpdateEnrollment($id, $completed, $enrollgroup, $enrollstatus, $tags) {
		if (($id == '') || ($id == 0)) {
			Error("Enrollment ID blank");
			return;
		}
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		$enrollgroup = mysqli_real_escape_string($GLOBALS['linki'], $enrollgroup);
		$enrollstatus = mysqli_real_escape_string($GLOBALS['linki'], $enrollstatus);
		
		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* update the main enrollment items (group, status) */
		$sqlstring = "update enrollment set enroll_subgroup = '$enrollgroup', enroll_status = '$enrollstatus' where enrollment_id = '$id'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* delete all enrollment_checklist entries */
		$sqlstring = "delete from enrollment_checklist where enrollment_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* insert these enrollment_checklist entries */
		foreach ($completed as $itemid) {
			if (isInteger($itemid)) {
				$sqlstring = "insert into enrollment_checklist (enrollment_id, projectchecklist_id, date_completed, completedby) values ($id, $itemid, now(), '" . $_SESSION['username'] . "')";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
		}

		/* end the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* update the tags (outside of the above transaction) */
		$taglist = explode(',',$tags);
		SetTags('enrollment', 'dx', $id, $taglist);
		
		Notice("Enrollment updated");
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayEnrollment ------------------ */
	/* -------------------------------------------- */
	function DisplayEnrollment($id) {
		if (($id == '') || ($id == 0)) {
			Error("Enrollment ID blank");
			return;
		}
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
	
		/* get all the information about the enrollment */
		$sqlstring = "select * from enrollment a left join projects b on a.project_id = b.project_id left join subjects c on a.subject_id = c.subject_id where a.enrollment_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$projectnumber = $row['project_costcenter'];
		$projectid = $row['project_id'];
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$enrollmentid = $row['enrollment_id'];
		$enroll_startdate = $row['enroll_startdate'];
		$enroll_enddate = $row['enroll_enddate'];
		$enrollgroup = $row['enroll_subgroup'];
		$enrollstatus = $row['enroll_status'];
		
		$tags = GetTags('enrollment','dx',$id);

		//$urllist[$projectname] = "projects.php?id=$projectid";
		//$urllist[$uid] = "subjects.php?id=$subjectid";
		//NavigationBar("Enrollment for $uid in $projectname", $urllist);

		/* get alternate subject IDs */
		$altuids = GetAlternateUIDs($subjectid, $enrollmentid);

		/* get the main checklist items */
		$i = 0;
		$sqlstring = "select * from project_checklist where project_id = $projectid order by item_order asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$checklist[$i]['id'] = $row['projectchecklist_id'];
			$checklist[$i]['name'] = $row['item_name'];
			$checklist[$i]['desc'] = $row['item_desc'];
			$checklist[$i]['order'] = $row['item_order'];
			$checklist[$i]['modality'] = $row['modality'];
			$checklist[$i]['protocol'] = $row['protocol_name'];
			$checklist[$i]['count'] = $row['count'];
			$checklist[$i]['frequency'] = $row['frequency'];
			$checklist[$i]['frequencyunit'] = $row['frequency_unit'];
			$i++;
		}
		
		/* get studies associated with this enrollment */
		$studyids = array();
		$sqlstring = "select study_id from studies where enrollment_id = $enrollmentid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyids[] = "'" . $row['study_id'] . "'";
		}
		
		/* display the enrollment table */
		?>
		<datalist id="enrollsubgroup">
		<?
			$sqlstring = "select distinct(enroll_subgroup) from enrollment order by enroll_subgroup";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				?><option value="<?=$row['enroll_subgroup']?>"><?
			}
		?>
		</datalist>
		
		<div class="ui container">
			<form method="post" action="enrollment.php" class="ui form">
			<input type="hidden" name="action" value="update">
			<input type="hidden" name="id" value="<?=$enrollmentid?>">
		
			<div class="ui top attached inverted segment header">
				<div class="ui two column grid">
					<div class="column">
						<a class="ui inverted large button" href="projects.php?id=<?=$projectid?>"><i class="external alternate icon"></i> <?=$projectname?> (<?=$projectnumber?>)</a>
					</div>
					<div class="right aligned inverted column">
						<span style="font-size: 22pt; font-weight: bold;" class="tt"><?=$uid?></span><br><br>
						<span style="font-size: 12pt; font-weight: bold" class="tt"><?=implode2('<br>', $altuids)?></span>
					</div>
				</div>
			</div>

			<div class="ui attached segment">
				<table class="ui very basic celled table">
					<tr>
						<td class="right aligned"><b>Enrollment date</b></td>
						<td><?=$enroll_startdate?></b></td>
					</tr>
					<tr>
						<td class="right aligned"><b>Enrollment group</b></td>
						<td><input type="text" name="enrollgroup" list="enrollsubgroup" value="<?=$enrollgroup?>"></td>
					</tr>
					<tr>
						<td class="right aligned"><b>Enrollment status</b></td>
						<td>
							<select name="enrollstatus" class="ui dropdown">
								<option value="" <? if ($enrollstatus == "") { echo "selected"; } ?>>(Select status)</option>
								<option value="enrolled" <? if ($enrollstatus == "enrolled") { echo "selected"; } ?>>Enrolled</option>
								<option value="completed" <? if ($enrollstatus == "completed") { echo "selected"; } ?>>Completed</option>
								<option value="excluded" <? if ($enrollstatus == "excluded") { echo "selected"; } ?>>EXCLUDED</option>
							</select>
						</td>
					</tr>
					<tr>
						<td class="right aligned"><b>Tags</b></td>
						<td><input type="text" name="tags" value="<?=implode2(', ', $tags)?>"></td>
					</tr>
				<table>
				<table class="ui very compact celled selectable table">
					<thead>
						<tr>
							<th>Item</th>
							<th>Completed</th>
							<th>Date</th>
							<th>Experimenter</th>
						</tr>
					</thead>
					<?
					//PrintVariable($studyids);
					if ((count($studyids) > 0) && ($studyids != '')) {
						if (count($checklist) > 0) {
							foreach ($checklist as $i => $item) {
								$itemid = strtolower($item['id']);
								$name = $item['name'];
								$desc = $item['desc'];
								$modality = strtolower($item['modality']);
								$protocol = $item['protocol'];
								$count = $item['count'];
								$frequency = $item['frequency'];
								$frequencyunit = $item['frequencyunit'];

								$completedates = array();
								$completedate = "";
								$experimenter = "";
								
								//PrintVariable($protocol);
								$protocols = explode(',', $protocol);
								foreach ($protocols as $i => $p) {
									$protocols[$i] = "'" . trim($protocols[$i]) . "'";
								}
								$msg = "";
								/* check for valid modality */
								$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($modality) . "_series'";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								if (mysqli_num_rows($result) > 0) {
									$sqlstring = "select *, date(series_datetime) 'seriesdate' from $modality" . "_series where study_id in (" . implode(",", $studyids) . ") and series_desc in (" . implode(",", $protocols) . ")";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									if (mysqli_num_rows($result) > 0) {
										while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
											$completedates[] = $row['seriesdate'];
										}
										$completedate = implode2('<br>',array_unique($completedates));
										$checked = "checked";
									}
									else {
										$checked = "";
									}
									?>
									<tr>
										<td><?=$name?></td>
										<td><input type="checkbox" <?=$checked?> disabled></td>
										<td><?=$completedate?></td>
										<td><?=$experimenter?></td>
									</tr>
									<?
								}
								else {
									$sqlstring = "select *, date(date_completed) 'completedate' from enrollment_checklist where enrollment_id = $enrollmentid and projectchecklist_id = $itemid";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									if (mysqli_num_rows($result) > 0) {
										$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
										$completedate = $row['completedate'];
										$experimenter = $row['completedby'];
										$checked = "checked";
									}
									else {
										$checked = "";
									}
									?>
									<tr>
										<td><?=$name?></td>
										<td><input type="checkbox" name="completed[]" value="<?=$itemid?>" <?=$checked?>></td>
										<td><?=$completedate?></td>
										<td><?=$experimenter?></td>
									</tr>
									<?
								}
							}
						}
						else {
							?>
							<tr>
								<td colspan="4">No items completed for this enrollment</td>
							</tr>
							<?
						}
					}
					?>
				</table>
			</div>

			<div class="ui bottom attached right aligned segment">
				<a href="subjects.php?subjectid=<?=$subjectid?>" class="ui button">Back to <?=$uid?></a>
				<input class="ui primary button" type="submit" value="Save">
			</div>
			
			</form>
		</div>
		<?
	}
?>


<? include("footer.php") ?>
