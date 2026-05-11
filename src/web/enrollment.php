<?
 // ------------------------------------------------------------------------------
 // NiDB enrollment.php
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

	/* serve the IRB consent BLOB before any HTML output */
	if (isset($_GET['action']) && $_GET['action'] == 'viewirb') {
		require "functions.php";
		require "includes_php.php";
		$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
		if ($id > 0) {
			$sqlstring = "select irb_consent from enrollment where enrollment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			mysqli_stmt_close($stmt);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			if (!empty($row['irb_consent'])) {
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = $finfo->buffer($row['irb_consent']);
				header('Content-Type: ' . $mime);
				header('Content-Disposition: inline; filename="irb_consent_' . $id . '"');
				echo $row['irb_consent'];
				exit;
			}
		}
		header('HTTP/1.1 404 Not Found');
		exit;
	}
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
	$checklistitemid = GetVariable("checklistitemid");
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
		case 'setitemcomplete':
			SetItemComplete($enrollmentid, $checklistitemid);
			DisplayEnrollment($enrollmentid);
			break;
		case 'setitemincomplete':
			SetItemIncomplete($enrollmentid, $checklistitemid);
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
	/* ------- SetItemComplete -------------------- */
	/* -------------------------------------------- */
	function SetItemComplete($enrollmentRowID, $checklistItemID) {
		if (($enrollmentRowID == '') || ($enrollmentRowID == 0)) { Error("Enrollment ID blank"); return; }
		if (($checklistItemID == '') || ($checklistItemID == 0)) { Error("Checklist item ID blank"); return; }
		
		$enrollmentRowID = (int)$enrollmentRowID;
		$checklistItemID = (int)$checklistItemID;
		
		$sqlstring = "insert into enrollment_checklist (enrollment_id, projectchecklist_id, iscomplete, notes, date_completed, completedby) values (?, ?, 1, null, now(), ?) on duplicate key update iscomplete = 1, date_completed = now(), completedby = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iiss', $enrollmentRowID, $checklistItemID, $GLOBALS['username'], $GLOBALS['username']);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID, $checklistItemID, $GLOBALS['username'], $GLOBALS['username']]);
		mysqli_stmt_close($stmt);
		
		Notice("Item marked as complete");
	}


	/* -------------------------------------------- */
	/* ------- SetItemIncomplete ------------------ */
	/* -------------------------------------------- */
	function SetItemIncomplete($enrollmentRowID, $checklistItemID) {
		if (($enrollmentRowID == '') || ($enrollmentRowID == 0)) { Error("Enrollment ID blank"); return; }
		if (($checklistItemID == '') || ($checklistItemID == 0)) { Error("Checklist item ID blank"); return; }
		
		$enrollmentRowID = (int)$enrollmentRowID;
		$checklistItemID = (int)$checklistItemID;
		
		$sqlstring = "insert into enrollment_checklist (enrollment_id, projectchecklist_id, iscomplete, notes, date_completed, completedby) values (?, ?, 0, null, now(), ?) on duplicate key update iscomplete = 0, date_completed = null, completedby = null";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iis', $enrollmentRowID, $checklistItemID, $GLOBALS['username']);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID, $checklistItemID, $GLOBALS['username']]);
		mysqli_stmt_close($stmt);
		
		Notice("Item marked as incomplete");
	}


	/* -------------------------------------------- */
	/* ------- UpdateEnrollment ------------------- */
	/* -------------------------------------------- */
	function UpdateEnrollment($id, $completed, $enrollgroup, $enrollstatus, $tags) {
		if (($id == '') || ($id == 0)) {
			Error("Enrollment ID blank");
			return;
		}
		$id = (int)$id;

		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* update the main enrollment items (group, status) */
		$sqlstring = "update enrollment set enroll_subgroup = ?, enroll_status = ? where enrollment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssi', $enrollgroup, $enrollstatus, $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollgroup, $enrollstatus, $id]);
		mysqli_stmt_close($stmt);

		/* replace IRB consent BLOB if a new file was uploaded */
		if (isset($_FILES['irbconsent']) && $_FILES['irbconsent']['error'] == UPLOAD_ERR_OK) {
			$irbconsent = file_get_contents($_FILES['irbconsent']['tmp_name']);
			$sqlstring = "update enrollment set irb_consent = ? where enrollment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $irbconsent, $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$irbconsent, $id]);
			mysqli_stmt_close($stmt);
		}

		/* end the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		/* update the tags (outside of the above transaction) */
		$taglist = explode(',',$tags);
		SetTags('enrollment', $id, $taglist);

		Notice("Enrollment updated");
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetAdjacentEnrollmentInProject ----- */
	/* -------------------------------------------- */
	function GetAdjacentEnrollmentInProject($enrollmentRowID, $projectRowID, $direction) {
		$enrollmentRowID = (int)$enrollmentRowID;
		$projectRowID = (int)$projectRowID;
		$direction = strtolower(trim($direction));

		if (($enrollmentRowID < 1) || ($projectRowID < 1)) {
			return false;
		}

		$sqlstring = "select b.uid from enrollment a left join subjects b on a.subject_id = b.subject_id where a.enrollment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			return false;
		}

		$uid = $row['uid'];
		if ($direction == "previous") {
			$sqlstring = "select a.enrollment_id 'enrollmentRowID', b.subject_id 'subjectRowID', b.uid from enrollment a left join subjects b on a.subject_id = b.subject_id where a.project_id = ? and (b.uid < ? or (b.uid = ? and a.enrollment_id < ?)) order by b.uid desc, a.enrollment_id desc limit 1";
		}
		else {
			$sqlstring = "select a.enrollment_id 'enrollmentRowID', b.subject_id 'subjectRowID', b.uid from enrollment a left join subjects b on a.subject_id = b.subject_id where a.project_id = ? and (b.uid > ? or (b.uid = ? and a.enrollment_id > ?)) order by b.uid asc, a.enrollment_id asc limit 1";
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'issi', $projectRowID, $uid, $uid, $enrollmentRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectRowID, $uid, $uid, $enrollmentRowID]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return $row;
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayEnrollment ------------------ */
	/* -------------------------------------------- */
	function DisplayEnrollment($id) {
		if (($id == '') || ($id == 0)) {
			Error("Enrollment ID blank");
			return;
		}
		$id = (int)$id;

		/* get all the information about the enrollment */
		$sqlstring = "select * from enrollment a left join projects b on a.project_id = b.project_id left join subjects c on a.subject_id = c.subject_id where a.enrollment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);
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
		$has_irb_consent = !empty($row['irb_consent']);
		$previousenrollment = GetAdjacentEnrollmentInProject($enrollmentid, $projectid, "previous");
		$nextenrollment = GetAdjacentEnrollmentInProject($enrollmentid, $projectid, "next");

		$tags = GetTags('enrollment', $id);

		/* get alternate subject IDs */
		$altuids = GetAlternateUIDs($subjectid, $enrollmentid);

		/* display the enrollment table */
		?>
		<datalist id="enrollsubgroup">
		<?
			$sqlstring = "select distinct(enroll_subgroup) from enrollment where project_id = ? order by enroll_subgroup";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				?><option value="<?=$row['enroll_subgroup']?>"><?
			}
			mysqli_stmt_close($stmt);
		?>
		</datalist>
		
		<div class="ui text container">
			<div class="ui top attached segment header">
				<div class="ui grid">
					<div class="twelve wide column">
						<div class="ui aligned segment" style="background-color: #ffffcc">
							<div class="ui three column grid">
								<div class="left aligned column">
									<? if ($previousenrollment) { ?>
									<a class="ui compact basic yellow icon button" href="enrollment.php?enrollmentid=<?=$previousenrollment['enrollmentRowID']?>" title="Previous subject <?=$previousenrollment['uid']?> in <?=$projectname?>"><i class="chevron left icon"></i></a>
									<? } else { ?>
									<div class="ui compact basic yellow disabled icon button" title="No previous subject in <?=$projectname?>"><i class="chevron left icon"></i></div>
									<? } ?>
								</div>
								<div class="ui center aligned column">
									<a href="subjects.php?subjectid=<?=$subjectid?>"><span style="font-size: 22pt; font-weight: bold;" class="tt"><?=$uid?></span></a>
								</div>
								<div class="right aligned column">
									<? if ($nextenrollment) { ?>
									<a class="ui compact basic yellow icon button" href="enrollment.php?enrollmentid=<?=$nextenrollment['enrollmentRowID']?>" title="Next subject <?=$nextenrollment['uid']?> in <?=$projectname?>"><i class="chevron right icon"></i></a>
									<? } else { ?>
									<div class="ui compact basic yellow disabled icon button" title="No next subject in <?=$projectname?>"><i class="chevron right icon"></i></div>
									<? } ?>
								</div>
							</div>
							<? if (count($altuids) > 0) { ?>
							<br>
							<tt><?=implode2('<br>', $altuids)?></tt>
							<? } ?>
						</div>
					</div>
					<div class="four wide right aligned column">
						<a href="projects.php?id=<?=$projectid?>"><i class="external alternate icon"></i> <?=$projectname?> (<?=$projectnumber?>)</a>
					</div>
				</div>
			</div>

			<br>
			
			<form method="post" action="enrollment.php" class="ui form" enctype="multipart/form-data">
			<input type="hidden" name="action" value="update">
			<input type="hidden" name="id" value="<?=$enrollmentid?>">
			
			<div class="ui black top attached segment">
				<h2 class="ui header">
					Enrollment Details
				</h2>
			</div>
			<table class="ui basic celled attached table">
				<tr>
					<td class="right aligned"><b>Enrollment date</b></td>
					<td><?=$enroll_startdate?></td>
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
							<option value="consented" <? if ($enrollstatus == "consented") { echo "selected"; } ?>>Consented</option>
							<option value="enrolled" <? if ($enrollstatus == "enrolled") { echo "selected"; } ?>>Enrolled</option>
							<option value="completed" <? if ($enrollstatus == "completed") { echo "selected"; } ?>>Completed</option>
							<option value="excluded" <? if ($enrollstatus == "excluded") { echo "selected"; } ?>>EXCLUDED</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="right aligned"><b>IRB Consent</b></td>
					<td>
						<? if ($has_irb_consent) { ?><a href="enrollment.php?action=viewirb&id=<?=$enrollmentid?>" target="_blank">View current file</a><br><? } ?>
						<input type="file" name="irbconsent">
					</td>
				</tr>
				<tr>
					<td class="right aligned"><b>Tags</b></td>
					<td><input type="text" name="tags" value="<?=implode2(', ', $tags)?>"></td>
				</tr>
			</table>
			<div class="ui bottom attached right aligned segment">
				<input class="ui primary button" type="submit" value="Save">
			</div>
			</form>
		</div>
		
		<div class="ui container">
			<!-- *********** Checklist *********** -->
			<?
				/* get the main checklist items */
				$sqlstring = "select * from project_checklist where project_id = ? order by item_order asc";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $projectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
				mysqli_stmt_close($stmt);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					
					$item['enrollmentRowID'] = $enrollmentid;
					$item['itemRowID'] = $row['projectchecklist_id'];
					$item['itemOrder'] = $row['item_order'];
					$item['itemName'] = $row['item_name'];
					$item['itemDesc'] = $row['item_desc'];
					$item['itemType'] = $row['item_type'];
					$item['imagingModality'] = $row['imaging_modality'];
					$item['mappedName'] = $row['mapped_name'];
					$item['expectedCount'] = $row['expected_count'];
					
					$checklist[] = $item;
				}
			?>
			<div class="ui black top attached segment">
				<div class="ui two column grid">
					<div class="ui column">
						<h2 class="ui header">
							Enrollment Checklist
						</h2>
					</div>
					<div class="ui right aligned column">
						<a href="projectchecklist.php?action=editchecklist&projectid=<?=$projectid?>" class="ui basic green button">Edit checklist</a>
					</div>
				</div>
			</div>
			<table class="ui very compact celled selectable bottom attached table">
				<thead>
					<tr>
						<th>Item</th>
						<th>Type</th>
						<th>Date</th>
						<th>Experimenter</th>
						<th>Details</th>
						<th>Completed</th>
					</tr>
				</thead>
				<?
				foreach ($checklist as $item) {
					switch ($item['itemType']) {
						case "Checkbox":
							DisplayChecklistItemCheckbox($item);
							break;
						case "Imaging":
							DisplayChecklistItemImaging($item);
							break;
						case "Intervention":
							DisplayChecklistItemIntervention($item);
							break;
						case "Observation":
							DisplayChecklistItemObservation($item);
							break;
						case "Diagnosis":
							DisplayChecklistItemDiagnosis($item);
							break;
						default:
							DisplayChecklistItemDefault($item);
					}
				}
				?>
			</table>
			<script>
				$(function() {
					$('.checklist-html-tooltip').tooltip({
						items: '.checklist-html-tooltip',
						content: function() { return $(this).attr('data-html'); }
					});
				});
			</script>

			<div class="ui black top attached segment">
				<h2 class="ui header">
					Enrollment Checklist (original method)
				</h2>
			</div>
			<table class="ui very compact celled selectable bottom attached table">
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
							$name = $item['name'];
							$desc = $item['desc'];
							$modality = strtolower($item['modality']);
							$protocol = $item['protocol'];
							$count = $item['count'];

							$completedates = array();
							$completedate = "";
							$experimenter = "";
							
							//PrintVariable($protocol);
							$protocols = array_map('trim', explode(',', $protocol));
							/* check for valid modality using validated helper */
							$tableName = GetSeriesTableName($modality);
							if ($tableName !== '') {
								$studyPlaceholders = implode(',', array_fill(0, count($studyids), '?'));
								$protocolPlaceholders = implode(',', array_fill(0, count($protocols), '?'));
								$sqlstring = "select *, date(series_datetime) 'seriesdate' from $tableName where study_id in ($studyPlaceholders) and series_desc in ($protocolPlaceholders)";
								$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
								$types = str_repeat('i', count($studyids)) . str_repeat('s', count($protocols));
								$params = array_merge($studyids, $protocols);
								mysqli_stmt_bind_param($stmt, $types, ...$params);
								$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
								mysqli_stmt_close($stmt);
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
								$itemid = (int)$item['id'];
								$sqlstring = "select *, date(date_completed) 'completedate' from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
								$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
								mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $itemid);
								$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid, $itemid]);
								mysqli_stmt_close($stmt);
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
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetMappedNameList ------------------ */
	/* -------------------------------------------- */
	function GetMappedNameList($mappedNameStr) {
		
		$names = array();
		
		/* check if it contains an ampersand, which means an AND search */
		if (stripos($mappedNameStr, '&') !== false) {
			$names = explode("&", $mappedNameStr);
			$names = array_map('trim', $names);
			$logic = "AND";
		}
		else {
			$names = explode(",", $mappedNameStr);
			$names = array_map('trim', $names);
			$logic = "OR";
		}
		
		//PrintVariable($names);
		//PrintVariable($logic);
		
		return [$names, $logic];
	}
	
	
	/* -------------------------------------------- */
	/* ------- BuildItemTooltip ------------------- */
	/* -------------------------------------------- */
	function BuildItemTooltip($item) {
		$name     = htmlspecialchars($item['itemName'] ?? '');
		$type     = htmlspecialchars($item['itemType'] ?? '');
		$modality = htmlspecialchars($item['imagingModality'] ?? '');
		$mapped   = htmlspecialchars($item['mappedName'] ?? '');
		$count    = htmlspecialchars($item['expectedCount'] ?? '');

		$html = "<b>Name:</b> $name<br><b>Type:</b> $type<br><b>Modality:</b> $modality<br><b>Mapped name:</b> $mapped<br><b>Expected count:</b> $count";

		return htmlspecialchars($html, ENT_QUOTES);
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemCheckbox ------- */
	/* -------------------------------------------- */
	/**
		Checkbox items come from the enrollment_checklist table
		- This item is checked to indicate the item has been completed
	*/
	function DisplayChecklistItemCheckbox($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		//$item['itemOrder']
		//$item['itemName']
		//$item['itemDesc']
		//$item['itemType']
		//$item['imagingModality']
		//$item['mappedName']
		//$item['expectedCount']
		
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $item['enrollmentRowID'], $item['itemRowID']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$item['enrollmentRowID'], $item['itemRowID']]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = $row['iscomplete'];
			$notes = $row['notes'];
			$completedDate = $row['date_completed'];
			$completedBy = $row['completedby'];
		}
		?>
			<tr>
				<td><?=$item['itemName']?></td>
				<td>Checkbox <i class="checklist-html-tooltip ui list icon right floated" data-html="<?=BuildItemTooltip($item)?>"></i></td>
				<td><?=$completedDate?></td>
				<td><?=$completedBy?></td>
				<td>data found...</td>
				<td><? if ($isComplete) { echo "<a href='enrollment.php?action=setitemincomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='green check circle icon'></i></a>"; } else { echo "<a href='enrollment.php?action=setitemcomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='grey circle outline icon'></i></a>"; } ?></td>
			</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemImaging -------- */
	/* -------------------------------------------- */
	/**
		Items come from the *_series tables
		- This is checked if the series_desc exists at least once in one of the enrolled studies
	*/
	function DisplayChecklistItemImaging($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		//$item['itemOrder']
		//$item['itemName']
		//$item['itemDesc']
		//$item['itemType']
		//$item['imagingModality']
		//$item['mappedName']
		//$item['expectedCount']
		
		/* get the mapped names */
		[$names, $logic] = GetMappedNameList($item['mappedName']);
		//PrintVariable($names);
		
		/* first check if this item is marked in the enrollment_checklist table,
		   then check if the item exists in the imaging series table,
		   display both, but the checklist table supercedes the imaging table
		*/
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $item['enrollmentRowID'], $item['itemRowID']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$item['enrollmentRowID'], $item['itemRowID']]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = $row['iscomplete'];
			$notes = $row['notes'];
			$completedDate = $row['date_completed'];
			$completedBy = $row['completedby'];
		}
		
		/* get list of studies for this enrollment */
		$studyids = GetStudiesByEnrollmentID($enrollmentRowID);
		
		if (count($studyids) > 0) {
			//PrintVariable($protocol);
			$protocols = array_map('trim', explode(',', $item['mappedName']));
			/* check for valid modality using validated helper */
			$tableName = GetSeriesTableName($item['imagingModality']);
			if ($tableName !== '') {
				if ($logic == "OR") {
					$studyPlaceholders = implode(',', array_fill(0, count($studyids), '?'));
					$protocolPlaceholders = implode(',', array_fill(0, count($protocols), '?'));
					$sqlstring = "select *, date(series_datetime) 'seriesdate' from $tableName where study_id in ($studyPlaceholders) and series_desc in ($protocolPlaceholders)";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					$types = str_repeat('i', count($studyids)) . str_repeat('s', count($protocols));
					$params = array_merge($studyids, $protocols);
				}
				else {
					$studyPlaceholders = implode(',', array_fill(0, count($studyids), '?'));
					foreach ($names as $name) {
						$descs[] = "series_desc = ?";
					}
					$nameStr = implode(' AND ', $descs);
					//$protocolPlaceholders = implode(',', array_fill(0, count($protocols), '?'));
					$sqlstring = "select *, date(series_datetime) 'seriesdate' from $tableName where study_id in ($studyPlaceholders) and ($nameStr)";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					$types = str_repeat('i', count($studyids)) . str_repeat('s', count($names));
					$params = array_merge($studyids, $names);
				}
				//PrintVariable(DebugSQLBoundStatement($sqlstring, $params));
				
				mysqli_stmt_bind_param($stmt, $types, ...$params);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				mysqli_stmt_close($stmt);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$completedates[] = $row['seriesdate'];
					}
					$completedDate = implode2('<br>',array_unique($completedates));
					$isComplete = true;
				}
				else {
					$isComplete = false;
				}
			}
		}
		?>
		<tr>
			<td><?=$item['itemName']?></td>
			<td>Imaging <i class="checklist-html-tooltip ui list icon right floated" data-html="<?=BuildItemTooltip($item)?>"></i></td>
			<td><?=$completedDate?></td>
			<td><?=$completedBy?></td>
			<td>what data is present</td>
			<td><? if ($isComplete) { echo "<a href='enrollment.php?action=setitemincomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='green check circle icon'></i></a>"; } else { echo "<a href='enrollment.php?action=setitemcomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='grey circle outline icon'></i></a>"; } ?></td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemObservation ---- */
	/* -------------------------------------------- */
	/**
		Items come from the observations table
		- This is checked if the variables in the observations table match the mapped_names
	*/
	function DisplayChecklistItemObservation($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		//$item['itemOrder']
		//$item['itemName']
		//$item['itemDesc']
		//$item['itemType']
		//$item['imagingModality']
		//$item['mappedName']
		//$item['expectedCount']

		/* first check if this item is marked in the enrollment_checklist table,
		   then check if the item exists in the imaging series table,
		   display both, but the checklist table supercedes the imaging table
		*/
		
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $item['enrollmentRowID'], $item['itemRowID']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$item['enrollmentRowID'], $item['itemRowID']]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = $row['iscomplete'];
			$notes = $row['notes'];
			$completedDate = $row['date_completed'];
			$completedBy = $row['completedby'];
		}
		?>
			<tr>
				<td><?=$item['itemName']?></td>
				<td>Observation <i class="checklist-html-tooltip ui list icon right floated" data-html="<?=BuildItemTooltip($item)?>"></i></td>
				<td><?=$completedDate?></td>
				<td><?=$completedBy?></td>
				<td></td>
				<td><? if ($isComplete) { echo "<a href='enrollment.php?action=setitemincomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='green check circle icon'></i></a>"; } else { echo "<a href='enrollment.php?action=setitemcomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='grey circle outline icon'></i></a>"; } ?></td>
			</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemIntervention --- */
	/* -------------------------------------------- */
	/**
		Items come from the interventions table
	*/
	function DisplayChecklistItemIntervention($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		//$item['itemOrder']
		//$item['itemName']
		//$item['itemDesc']
		//$item['itemType']
		//$item['imagingModality']
		//$item['mappedName']
		//$item['expectedCount']

		/* first check if this item is marked in the enrollment_checklist table,
		   then check if the item exists in the imaging series table,
		   display both, but the checklist table supercedes the imaging table
		*/
		
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $item['enrollmentRowID'], $item['itemRowID']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$item['enrollmentRowID'], $item['itemRowID']]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = $row['iscomplete'];
			$notes = $row['notes'];
			$completedDate = $row['date_completed'];
			$completedBy = $row['completedby'];
		}
		?>
			<tr>
				<td><?=$item['itemName']?></td>
				<td>Intervention <i class="checklist-html-tooltip ui list icon right floated" data-html="<?=BuildItemTooltip($item)?>"></i></td>
				<td><?=$completedDate?></td>
				<td><?=$completedBy?></td>
				<td></td>
				<td><? if ($isComplete) { echo "<a href='enrollment.php?action=setitemincomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='green check circle icon'></i></a>"; } else { echo "<a href='enrollment.php?action=setitemcomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='grey circle outline icon'></i></a>"; } ?></td>
			</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemDiagnosis ------ */
	/* -------------------------------------------- */
	/**
		Items come from the diagnosis table
	*/
	function DisplayChecklistItemDiagnosis($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		//$item['itemOrder']
		//$item['itemName']
		//$item['itemDesc']
		//$item['itemType']
		//$item['imagingModality']
		//$item['mappedName']
		//$item['expectedCount']

		/* first check if this item is marked in the enrollment_checklist table,
		   then check if the item exists in the imaging series table,
		   display both, but the checklist table supercedes the imaging table
		*/
		
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $item['enrollmentRowID'], $item['itemRowID']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$item['enrollmentRowID'], $item['itemRowID']]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = $row['iscomplete'];
			$notes = $row['notes'];
			$completedDate = $row['date_completed'];
			$completedBy = $row['completedby'];
		}
		?>
			<tr>
				<td><?=$item['itemName']?></td>
				<td>Diagnosis <i class="checklist-html-tooltip ui list icon right floated" data-html="<?=BuildItemTooltip($item)?>"></i></td>
				<td><?=$completedDate?></td>
				<td><?=$completedBy?></td>
				<td></td>
				<td><? if ($isComplete) { echo "<a href='enrollment.php?action=setitemincomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='green check circle icon'></i></a>"; } else { echo "<a href='enrollment.php?action=setitemcomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='grey circle outline icon'></i></a>"; } ?></td>
			</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemDefault -------- */
	/* -------------------------------------------- */
	/**
		Items come from the interventions table
	*/
	function DisplayChecklistItemDefault($item) {
		/* unknown checklist item type */
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		//$item['itemOrder']
		//$item['itemName']
		//$item['itemDesc']
		//$item['itemType']
		//$item['imagingModality']
		//$item['mappedName']
		//$item['expectedCount']

		/* first check if this item is marked in the enrollment_checklist table,
		   then check if the item exists in the imaging series table,
		   display both, but the checklist table supercedes the imaging table
		*/
		
		?>
			<tr>
				<td><?=$item['itemName']?></td>
				<td><?=htmlspecialchars($item['itemType'])?> (unknown) <i class="checklist-html-tooltip ui list icon right floated" data-html="<?=BuildItemTooltip($item)?>"></i></td>
				<td><?=$completedDate?></td>
				<td><?=$completedBy?></td>
				<td></td>
				<td><? if ($isComplete) { echo "<a href='enrollment.php?action=setitemincomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='green check circle icon'></i></a>"; } else { echo "<a href='enrollment.php?action=setitemcomplete&enrollmentid=$enrollmentRowID&checklistitemid=$itemRowID'><i class='grey circle outline icon'></i></a>"; } ?></td>
			</tr>
		<?
	}
	
?>


<? include("footer.php") ?>
