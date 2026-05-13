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

			<div class="ui black top attached segment">
				<h2 class="ui header">
					Enrollment Details
				</h2>
			</div>
			<form method="post" action="enrollment.php" class="ui form" enctype="multipart/form-data">
			<input type="hidden" name="action" value="update">
			<input type="hidden" name="id" value="<?=$enrollmentid?>">
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
						<div class="ui file input">
							<input type="file" name="irbconsent">
						</div>
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
		
		<br>
		
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
					$item['instrumentId'] = $row['instrument_id'];
					
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
						<th>Matched Data</th>
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
						case "Instrument":
							DisplayChecklistItemInstrument($item);
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
					$('.ui.modal').modal();
				});
			</script>
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
	/* ------- DisplayItemModal ------------------- */
	/* -------------------------------------------- */
	function DisplayItemModal($item) {
		?>
		<div class="ui small modal" id="item-modal-<?=$item['itemRowID']?>">
			<div class="header"><?=htmlspecialchars($item['itemName'] ?? '')?></div>
			<div class="content">
				<table class="ui celled very compact small table">
					<tbody>
						<tr><td><b>Type</b></td><td><?=htmlspecialchars($item['itemType'] ?? '')?></td></tr>
						<tr><td><b>Modality</b></td><td><?=htmlspecialchars($item['imagingModality'] ?? '')?></td></tr>
						<tr><td><b>Mapped name</b></td><td><?=htmlspecialchars($item['mappedName'] ?? '')?></td></tr>
						<tr><td><b>Expected count</b></td><td><?=htmlspecialchars($item['expectedCount'] ?? '')?></td></tr>
					</tbody>
				</table>
			</div>
			<div class="actions">
				<div class="ui cancel button">Close</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFoundImagingModal ----------- */
	/* -------------------------------------------- */
	function DisplayFoundImagingModal($data, $item) {
		?>
		<div class="ui small modal" id="found-data-modal-<?=$item['itemRowID']?>">
			<div class="header">Matched series</div>
			<div class="content">
				<table class="ui celled very compact small table">
					<thead>
						<tr>
							<th colspan="3">Study</th>
							<th colspan="3">Series</th>
						</tr>
						<tr>
							<th>No.</th>
							<th>Desc</th>
							<th>Date</th>
							<th>No.</th>
							<th>Desc</th>
							<th>Date</th>
						</tr>
					</thead>
					<tbody>
						<? foreach ($data as $d) { ?>
						<tr>
							<td><?=$d['StudyNumber']?></td>
							<td><a href="studies.php?studyid=<?=$d['StudyRowID']?>"><?=$d['StudyDescription']?></a></td>
							<td><?=$d['StudyDatetime']?></td>
							<td><?=$d['SeriesNumber']?></td>
							<td><?=$d['SeriesDescription']?></td>
							<td><?=$d['SeriesDatetime']?></td>
						</tr>
						<? } ?>
					</tbody>
				</table>
			</div>
			<div class="actions">
				<div class="ui cancel button">Close</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFoundNonImagingModal -------- */
	/* -------------------------------------------- */
	function DisplayFoundNonImagingModal($data, $item) {
		?>
		<div class="ui small modal" id="found-data-modal-<?=$item['itemRowID']?>">
			<div class="header">Matched data</div>
			<div class="content">
				<table class="ui celled very compact small table">
					<thead>
						<tr>
							<th>Name</th>
							<th>Value</th>
							<th>Date</th>
							<th>Duration</th>
							<th>Rater</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
						<? foreach ($data as $d) { ?>
						<tr>
							<td><?=htmlspecialchars($d['Name'] ?? '')?></td>
							<td><?=htmlspecialchars($d['Value'] ?? '')?></td>
							<td><?=htmlspecialchars($d['Startdate'] ?? '')?></td>
							<td><?=htmlspecialchars($d['Duration'] ?? '')?></td>
							<td><?=htmlspecialchars($d['Rater'] ?? '')?></td>
							<td><?=htmlspecialchars($d['Notes'] ?? '')?></td>
						</tr>
						<? } ?>
					</tbody>
				</table>
			</div>
			<div class="actions">
				<div class="ui cancel button">Close</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayFoundDiagnosisModal --------- */
	/* -------------------------------------------- */
	function DisplayFoundDiagnosisModal($data, $item) {
		?>
		<div class="ui small modal" id="found-data-modal-<?=$item['itemRowID']?>">
			<div class="header">Matched diagnoses</div>
			<div class="content">
				<table class="ui celled very compact small table">
					<thead>
						<tr>
							<th>ICD-10 Code</th>
							<th>Description</th>
							<th>Start Date</th>
							<th>End Date</th>
						</tr>
					</thead>
					<tbody>
						<? foreach ($data as $d) { ?>
						<tr>
							<td><?=htmlspecialchars($d['Code'] ?? '')?></td>
							<td><?=htmlspecialchars($d['Description'] ?? '')?></td>
							<td><?=htmlspecialchars($d['StartDate'] ?? '')?></td>
							<td><?=htmlspecialchars($d['EndDate'] ?? '')?></td>
						</tr>
						<? } ?>
					</tbody>
				</table>
			</div>
			<div class="actions">
				<div class="ui cancel button">Close</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayInstrumentProgressModal ----- */
	/* -------------------------------------------- */
	function DisplayInstrumentProgressModal($expectedItems, $collectedSet, $item) {
		?>
		<div class="ui small modal" id="instrument-progress-modal-<?=$item['itemRowID']?>">
			<div class="header">Instrument items — <?=htmlspecialchars($item['itemName'] ?? '')?></div>
			<div class="content">
				<table class="ui celled very compact small table">
					<thead>
						<tr>
							<th>Item</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<? foreach ($expectedItems as $ename) { ?>
						<? $done = isset($collectedSet[strtolower(trim($ename))]); ?>
						<tr>
							<td><?=htmlspecialchars($ename)?></td>
							<td>
								<? if ($done) { ?>
								<i class="green check circle icon"></i> Complete
								<? } else { ?>
								<i class="grey circle outline icon"></i> Incomplete
								<? } ?>
							</td>
						</tr>
						<? } ?>
					</tbody>
				</table>
			</div>
			<div class="actions">
				<div class="ui cancel button">Close</div>
			</div>
		</div>
		<?
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
				<td></td>
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
		
		/* get the mapped names */
		[$names, $logic] = GetMappedNameList($item['mappedName']);
		
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
					$sqlstring = "select * from $tableName a left join studies b on a.study_id = b.study_id where a.study_id in ($studyPlaceholders) and a.series_desc in ($protocolPlaceholders)";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					$types = str_repeat('i', count($studyids)) . str_repeat('s', count($protocols));
					$params = array_merge($studyids, $protocols);
				}
				else {
					$studyPlaceholders = implode(',', array_fill(0, count($studyids), '?'));
					foreach ($names as $name) {
						$descs[] = "a.series_desc = ?";
					}
					$protocolPlaceholders = implode(' AND ', $descs);
					$sqlstring = "select * from $tableName a left join studies b on a.study_id = b.study_id where a.study_id in ($studyPlaceholders) and ($protocolPlaceholders)";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					$types = str_repeat('i', count($studyids)) . str_repeat('s', count($names));
					$params = array_merge($studyids, $names);
				}
				//PrintVariable(DebugSQLBoundStatement($sqlstring, $params));
				
				mysqli_stmt_bind_param($stmt, $types, ...$params);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				mysqli_stmt_close($stmt);
				$data = array();
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$completedates[] = date('M j, Y', strtotime($row['series_datetime']));
						/* StudyNumber, SeriesDesc, SeriesDatetime */
						//$series_datetime = date('M j, Y g:ia',strtotime($row['series_datetime']));
						$d['StudyNumber'] = $row['study_num'];
						$d['StudyDescription'] = $row['study_desc'];
						$d['StudyDatetime'] = $row['study_datetime'];
						$d['SeriesDatetime'] = $row['series_datetime'];
						$d['SeriesNumber'] = $row['series_num'];
						$d['SeriesDescription'] = $row['series_desc'];
						$d['StudyRowID'] = $row['study_id'];
						
						$data[] = $d;
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
			<td>
				Imaging <i class="ui list icon right floated" style="cursor:pointer" onclick="$('#item-modal-<?=$itemRowID?>').modal('show')"></i>
			</td>
			<td><?=$completedDate?></td>
			<td><?=$completedBy?></td>
			<? if (count($data) > 0) { ?>
			<td style="cursor:pointer; text-decoration: underline dotted blue" onclick="$('#found-data-modal-<?=$itemRowID?>').modal('show')">
				<?=count($data)?> <?=$item['imagingModality']?> series
				<? DisplayFoundImagingModal($data, $item); ?>
			</td>
			<? } else { ?>
			<td></td>
			<? } ?>
			<td>
				<? if ($isComplete) { ?>
				<i class='green check circle icon'></i>
				<? } else { ?>
				<i class='grey circle outline icon'></i>
				<? } ?>
				<!-- modal dialog, needs to be within a <td> element -->
				<? DisplayItemModal($item); ?>
			</td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemObservation ---- */
	/* -------------------------------------------- */
	/**
		Items come from the observations table
		- The table will display a checkmark for complete, if the variables in the observations table match the mapped_names
	*/
	function DisplayChecklistItemObservation($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];

		/* get the mapped names */
		[$observations, $logic] = GetMappedNameList($item['mappedName']);
		
		if ($logic == "OR") {
			$obsvPlaceholders = implode(',', array_fill(0, count($observations), '?')); /* create list of ?,?,? bound parameter placeholders */
			$sqlstring = "select * from observations where enrollment_id = ? and observation_name in ($obsvPlaceholders)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			$types = 'i' . str_repeat('s', count($observations)); /* create the list of bound datatypes (iiisss, etc) */
			$params = array_merge([$enrollmentRowID], $observations); /* merge the values into a single array for SQL debugging later */
		}
		else {
			foreach ($observations as $obsv) {
				$descs[] = "observation_name = ?";
			}
			$obsvPlaceholders = implode(' AND ', $descs);
			$sqlstring = "select * from observations where enrollment_id = ? and ($obsvPlaceholders)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			$types = 'i' . str_repeat('s', count($observations));
			$params = array_merge([$enrollmentRowID], $observations);
		}
		
		//PrintVariable(DebugSQLBoundStatement($sqlstring, $params));
		
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		$data = array();
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				/* collect the found data */
				$d['Name'] = $row['observation_name'];
				$d['Notes'] = $row['observation_notes'];
				$d['Instrument'] = $row['observation_instrument'];
				$d['Description'] = $row['observation_desc'];
				$d['Value'] = $row['observation_value'];
				$d['Startdate'] = $row['observation_startdate'];
				$d['Duration'] = $row['observation_duration'];
				$d['Rater'] = $row['observation_rater'];
				$data[] = $d;
				
				/* quick displayed info */
				$completedates[] = date('M j, Y', strtotime($row['observation_startdate']));
				$raters[] = $row['observation_rater'];
			}
			$completedDate = implode2(',',array_unique($completedates));
			$completedBy = implode2(',',array_unique($raters));
			$isComplete = true;
		}
		else {
			$isComplete = false;
		}
		?>
		<tr>
			<td><?=$item['itemName']?></td>
			<td>
				Observation <i class="ui list icon right floated" style="cursor:pointer" onclick="$('#item-modal-<?=$itemRowID?>').modal('show')"></i>
			</td>
			<td><?=$completedDate?></td>
			<td><?=$completedBy?></td>
			<? if (count($data) > 0) { ?>
			<td style="cursor:pointer; text-decoration: underline dotted blue" onclick="$('#found-data-modal-<?=$itemRowID?>').modal('show')">
				<?=count($data)?> observations
				<? DisplayFoundNonImagingModal($data, $item); ?>
			</td>
			<? } else { ?>
			<td></td>
			<? } ?>
			<td>
				<? if ($isComplete) { ?>
				<i class='green check circle icon'></i>
				<? } else { ?>
				<i class='grey circle outline icon'></i>
				<? } ?>
				<!-- modal dialog, needs to be within a <td> element -->
				<? DisplayItemModal($item); ?>
			</td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemInstrument ----- */
	/* -------------------------------------------- */
	/**
		Items come from the observations table, matched by instrument_id
		- Displays a checkmark if any observations exist for this enrollment with the linked instrument
	*/
	function DisplayChecklistItemInstrument($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];
		$instrumentId = (int)($item['instrumentId'] ?? 0);

		$completedDate = '';
		$completedBy = '';
		$isComplete = false;
		$data = array();
		$expectedItems = array();
		$collectedSet = array();
		$totalItems = 0;
		$completedItems = 0;
		$pct = 0;

		/* check for manual override in enrollment_checklist */
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $enrollmentRowID, $itemRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID, $itemRowID]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = (bool)$row['iscomplete'];
			$completedDate = $row['completedate'];
			$completedBy = $row['completedby'];
		}
		else {
			if ($instrumentId > 0) {
				/* get the expected items for this instrument */
				$sqlstring = "select item_name from instrument_items where instrument_id = ? order by item_order asc";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $instrumentId);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instrumentId]);
				mysqli_stmt_close($stmt);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$expectedItems[] = $row['item_name'];
				}

				/* get collected observations for this enrollment+instrument */
				$sqlstring = "select * from observations where enrollment_id = ? and instrument_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'ii', $enrollmentRowID, $instrumentId);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID, $instrumentId]);
				mysqli_stmt_close($stmt);
				$completedates = array();
				$raters = array();
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$d['Name'] = $row['observation_name'];
						$d['Notes'] = $row['observation_notes'];
						$d['Instrument'] = $row['observation_instrument'];
						$d['Description'] = $row['observation_desc'];
						$d['Value'] = $row['observation_value'];
						$d['Startdate'] = $row['observation_startdate'];
						$d['Duration'] = $row['observation_duration'];
						$d['Rater'] = $row['observation_rater'];
						$data[] = $d;

						$collectedSet[strtolower(trim($row['observation_name']))] = true;
						$completedates[] = date('M j, Y', strtotime($row['observation_startdate']));
						$raters[] = $row['observation_rater'];
					}
					$completedDate = implode2(',', array_unique($completedates));
					$completedBy = implode2(',', array_unique($raters));
				}

				/* calculate per-item completion */
				$totalItems = count($expectedItems);
				foreach ($expectedItems as $ename) {
					if (isset($collectedSet[strtolower(trim($ename))])) {
						$completedItems++;
					}
				}
				$pct = $totalItems > 0 ? round($completedItems / $totalItems * 100) : 0;
				$isComplete = ($totalItems > 0) && ($completedItems === $totalItems);
			}
		}
		?>
		<tr>
			<td><?=$item['itemName']?></td>
			<td>
				Instrument <i class="ui list icon right floated" style="cursor:pointer" onclick="$('#item-modal-<?=$itemRowID?>').modal('show')"></i>
			</td>
			<td><?=$completedDate?></td>
			<td><?=$completedBy?></td>
			<? if ($totalItems > 0) { ?>
			<td style="cursor:pointer; text-decoration: underline dotted blue" onclick="$('#instrument-progress-modal-<?=$itemRowID?>').modal('show')">
				<?=$pct?>% (<?=$completedItems?>/<?=$totalItems?> items)
				<? DisplayInstrumentProgressModal($expectedItems, $collectedSet, $item); ?>
			</td>
			<? } else { ?>
			<td></td>
			<? } ?>
			<td>
				<? if ($isComplete) { ?>
				<i class='green check circle icon'></i>
				<? } else { ?>
				<i class='grey circle outline icon'></i>
				<? } ?>
				<!-- modal dialog, needs to be within a <td> element -->
				<? DisplayItemModal($item); ?>
			</td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayChecklistItemIntervention --- */
	/* -------------------------------------------- */
	/**
		Items come from the interventions table
		- The table will display a checkmark for complete, if the variables in the interventions table match the mapped_names
	*/
	function DisplayChecklistItemIntervention($item) {
		$enrollmentRowID = $item['enrollmentRowID'];
		$itemRowID = $item['itemRowID'];

		/* get the mapped names */
		[$interventions, $logic] = GetMappedNameList($item['mappedName']);
		
		if ($logic == "OR") {
			$intPlaceholders = implode(',', array_fill(0, count($interventions), '?')); /* create list of ?,?,? bound parameter placeholders */
			$sqlstring = "select * from interventions where enrollment_id = ? and intervention_desc in ($intPlaceholders)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			$types = 'i' . str_repeat('s', count($interventions)); /* create the list of bound datatypes (iiisss, etc) */
			$params = array_merge([$enrollmentRowID], $interventions); /* merge the values into a single array for SQL debugging later */
		}
		else {
			foreach ($interventions as $intv) {
				$descs[] = "intervention_desc = ?";
			}
			$intPlaceholders = implode(' AND ', $descs);
			$sqlstring = "select * from interventions where enrollment_id = ? and ($intPlaceholders)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			$types = 'i' . str_repeat('s', count($interventions));
			$params = array_merge([$enrollmentRowID], $interventions);
		}
		
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		$data = array();
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				/* collect the found data */
				$d['Name'] = $row['intervention_name'];
				$d['Notes'] = $row['intervention_notes'];
				$d['Instrument'] = $row['intervention_instrument'];
				$d['Description'] = $row['intervention_desc'];
				$d['Value'] = $row['intervention_value'];
				$d['Startdate'] = $row['intervention_startdate'];
				$d['Duration'] = $row['intervention_duration'];
				$d['Rater'] = $row['intervention_rater'];
				$data[] = $d;
				
				/* quick displayed info */
				$completedates[] = date('M j, Y', strtotime($row['intervention_startdate']));
				$raters[] = $row['intervention_rater'];
			}
			$completedDate = implode2(',',array_unique($completedates));
			$completedBy = implode2(',',array_unique($raters));
			$isComplete = true;
		}
		else {
			$isComplete = false;
		}
		?>
		<tr>
			<td><?=$item['itemName']?></td>
			<td>
				Intervention <i class="ui list icon right floated" style="cursor:pointer" onclick="$('#item-modal-<?=$itemRowID?>').modal('show')"></i>
			</td>
			<td><?=$completedDate?></td>
			<td><?=$completedBy?></td>
			<? if (count($data) > 0) { ?>
			<td style="cursor:pointer; text-decoration: underline dotted blue" onclick="$('#found-data-modal-<?=$itemRowID?>').modal('show')">
				<?=count($data)?> interventions
				<? DisplayFoundNonImagingModal($data, $item); ?>
			</td>
			<? } else { ?>
			<td></td>
			<? } ?>
			<td>
				<? if ($isComplete) { ?>
				<i class='green check circle icon'></i>
				<? } else { ?>
				<i class='grey circle outline icon'></i>
				<? } ?>
				<!-- modal dialog, needs to be within a <td> element -->
				<? DisplayItemModal($item); ?>
			</td>
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

		$completedDate = '';
		$completedBy = '';
		$isComplete = false;
		$data = array();

		/* check for manual override in enrollment_checklist */
		$sqlstring = "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $enrollmentRowID, $itemRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID, $itemRowID]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$isComplete = (bool)$row['iscomplete'];
			$completedDate = $row['completedate'];
			$completedBy = $row['completedby'];
		}
		else {
			/* check the diagnosis table */
			$sqlstring = "select a.diagnosis_id, b.icd10_code, b.icd10_longdesc, date_format(a.start_date, '%Y-%m-%d') start_date, date_format(a.end_date, '%Y-%m-%d') end_date from diagnosis a left join icd10 b on a.icd10_id = b.icd10_id where a.enrollment_id = ? order by b.icd10_code";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $enrollmentRowID);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentRowID]);
			mysqli_stmt_close($stmt);
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$d['Code'] = $row['icd10_code'];
					$d['Description'] = $row['icd10_longdesc'];
					$d['StartDate'] = $row['start_date'];
					$d['EndDate'] = $row['end_date'];
					$data[] = $d;
				}
				$isComplete = true;
			}
		}
		?>
		<tr>
			<td><?=$item['itemName']?></td>
			<td>
				Diagnosis <i class="ui list icon right floated" style="cursor:pointer" onclick="$('#item-modal-<?=$itemRowID?>').modal('show')"></i>
			</td>
			<td><?=$completedDate?></td>
			<td><?=$completedBy?></td>
			<? if (count($data) > 0) { ?>
			<td style="cursor:pointer; text-decoration: underline dotted blue" onclick="$('#found-data-modal-<?=$itemRowID?>').modal('show')">
				<?=count($data)?> diagnoses
				<? DisplayFoundDiagnosisModal($data, $item); ?>
			</td>
			<? } else { ?>
			<td></td>
			<? } ?>
			<td>
				<? if ($isComplete) { ?>
				<i class='green check circle icon'></i>
				<? } else { ?>
				<i class='grey circle outline icon'></i>
				<? } ?>
				<!-- modal dialog, needs to be within a <td> element -->
				<? DisplayItemModal($item); ?>
			</td>
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
