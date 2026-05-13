<?
 // ------------------------------------------------------------------------------
 // NiDB projectchecklist.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Project Checklist</title>
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
	$projectid = GetVariable("projectid");
	$itemid = GetVariable("itemid");
	//$itemorder = GetVariable("itemorder");
	$itemname = GetVariable("itemname");
	$itemtype = GetVariable("itemtype");
	$modality = GetVariable("modality");
	$mappedname = GetVariable("mappedname");
	$expectedcount = GetVariable("expectedcount");
	$deleteitem = GetVariable("deleteitem");
	$instrumentid = GetVariable("instrumentid");
	$enrollmentid = GetVariable("enrollmentid");
	$projectchecklistid = GetVariable("projectchecklistid");
	$reason = GetVariable("reason");
	$missingdataid = GetVariable("missingdataid");
	
	$a['mr_protocols'] = GetVariable("mr_protocols");
    $a['eeg_protocols'] = GetVariable("eeg_protocols");
    $a['et_protocols'] = GetVariable("et_protocols");
    $a['pipelines'] = GetVariable("pipelines");
    $a['includeprotocolparms'] = GetVariable("includeprotocolparms");
    $a['includemrqa'] = GetVariable("includemrqa");
    $a['includeallobservations'] = GetVariable("includeallobservations");
    //$a['includeallvitals'] = GetVariable("includeallvitals");
    $a['includeallinterventions'] = GetVariable("includeallinterventions");
    $a['includeemptysubjects'] = GetVariable("includeemptysubjects");
    $a['grouprowsby'] = GetVariable("grouprowsby");
	
	/* determine action */
	switch ($action) {
		case 'updateprojectchecklist':
			UpdateProjectChecklist($projectid, $itemid, $itemname, $itemtype, $modality, $mappedname, $expectedcount, $deleteitem, $instrumentid);
			DisplayEditChecklist($projectid);
			break;
		case 'setmissingdatareasonform':
			SetMissingDataReasonForm($projectid, $missingdataid, $enrollmentid, $projectchecklistid, $reason);
			break;
		case 'setmissingdatareason':
			SetMissingDataReason($projectid, $enrollmentid, $projectchecklistid, $reason);
			DisplayProjectChecklist($projectid);
			break;
		case 'deletemissingdatareason':
			DeleteMissingDataReason($missingdataid);
			DisplayProjectChecklist($projectid);
			break;
		case 'editchecklist':
			DisplayEditChecklist($projectid);
			break;
		case 'viewanalysissummary':
			DisplayAnalysisSummaryBuilder($projectid, $a);
			break;
		default:
			DisplayProjectChecklist($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateProjectChecklist ------------- */
	/* -------------------------------------------- */
	function UpdateProjectChecklist($projectid, $itemid, $itemname, $itemtype, $modality, $mappedname, $expectedcount, $deleteitem, $instrumentid) {

		/* perform data checks */
		$projectid = trim($projectid);
		
		if (!isInteger($projectid)) {
			Error("Invalid project ID [$projectid]");
			return;
		}
		$projectid = (int)$projectid;
		
		$itemids = $itemid;
		$itemnames = $itemname;
		$itemtypes = $itemtype;
		$modalities = $modality;
		$mappednames = $mappedname;
		$expectedcounts = $expectedcount;
		$deleteitems = $deleteitem;
		
		if (!is_array($itemids)) { $itemids = array(); }
		if (!is_array($itemnames)) { $itemnames = array(); }
		if (!is_array($itemtypes)) { $itemtypes = array(); }
		if (!is_array($modalities)) { $modalities = array(); }
		if (!is_array($mappednames)) { $mappednames = array(); }
		if (!is_array($expectedcounts)) { $expectedcounts = array(); }
		if (!is_array($deleteitems)) { $deleteitems = array(); }
		$instrumentids = is_array($instrumentid) ? $instrumentid : array();

		$allindices = array_unique(array_merge(
			array_keys($itemids),
			array_keys($itemnames),
			array_keys($itemtypes),
			array_keys($modalities),
			array_keys($mappednames),
			array_keys($expectedcounts),
			array_keys($deleteitems),
			array_keys($instrumentids)
		));
		sort($allindices, SORT_NUMERIC);
		
		mysqli_begin_transaction($GLOBALS['linki']);
		
		$deleteRowID = 0;
		$deleteStmt = mysqli_prepare($GLOBALS['linki'], "delete from project_checklist where project_id = ? and projectchecklist_id = ?");
		mysqli_stmt_bind_param($deleteStmt, 'ii', $projectid, $deleteRowID);
		
		$insertItemOrder = 0;
		$insertName = "";
		$insertType = "";
		$insertModality = "";
		$insertMappedName = "";
		$insertExpectedCount = null;
		$insertInstrumentId = null;
		$insertStmt = mysqli_prepare($GLOBALS['linki'], "insert into project_checklist (project_id, item_order, item_name, item_type, imaging_modality, mapped_name, expected_count, instrument_id) values (?, ?, ?, ?, ?, ?, ?, ?)");
		mysqli_stmt_bind_param($insertStmt, 'iissssii', $projectid, $insertItemOrder, $insertName, $insertType, $insertModality, $insertMappedName, $insertExpectedCount, $insertInstrumentId);

		$updateItemOrder = 0;
		$updateName = "";
		$updateType = "";
		$updateModality = "";
		$updateMappedName = "";
		$updateExpectedCount = null;
		$updateInstrumentId = null;
		$updateRowID = 0;
		$updateStmt = mysqli_prepare($GLOBALS['linki'], "update project_checklist set item_order = ?, item_name = ?, item_type = ?, imaging_modality = ?, mapped_name = ?, expected_count = ?, instrument_id = ? where project_id = ? and projectchecklist_id = ?");
		mysqli_stmt_bind_param($updateStmt, 'issssiiii', $updateItemOrder, $updateName, $updateType, $updateModality, $updateMappedName, $updateExpectedCount, $updateInstrumentId, $projectid, $updateRowID);
		
		$itemorder = 1;
		foreach ($allindices as $i) {
			$rowid = isset($itemids[$i]) ? trim($itemids[$i]) : "";
			$name = isset($itemnames[$i]) ? trim($itemnames[$i]) : "";
			$type = isset($itemtypes[$i]) ? trim($itemtypes[$i]) : "Checkbox";
			$itemmodality = isset($modalities[$i]) ? trim($modalities[$i]) : "";
			$itemmappedname = isset($mappednames[$i]) ? trim($mappednames[$i]) : "";
			$itemexpectedcount = isset($expectedcounts[$i]) ? trim($expectedcounts[$i]) : "";
			$delete = isset($deleteitems[$i]) ? trim($deleteitems[$i]) : "0";
			$iteminstrumentid = isset($instrumentids[$i]) ? (int)$instrumentids[$i] : null;
			if ($iteminstrumentid < 1) $iteminstrumentid = null;

			if (($rowid != "") && (!isInteger($rowid))) { continue; }
			if (!in_array($type, array("Checkbox", "Imaging", "Intervention", "Observation", "Diagnosis", "Instrument"))) { $type = "Checkbox"; }
			if (($itemexpectedcount == "") || (!isInteger($itemexpectedcount)) || ($itemexpectedcount < 1)) { $itemexpectedcount = "null"; }
			else { $itemexpectedcount = intval($itemexpectedcount); }
			
			if (($delete == "1") && ($rowid != "")) {
				$deleteRowID = (int)$rowid;
				$result = MySQLiBoundQuery($deleteStmt, __FILE__, __LINE__);
			}
			else if ($name != "") {
				if ($rowid == "") {
					$insertItemOrder = $itemorder;
					$insertName = $name;
					$insertType = $type;
					$insertModality = $itemmodality;
					$insertMappedName = $itemmappedname;
					$insertExpectedCount = ($itemexpectedcount == "null") ? null : $itemexpectedcount;
					$insertInstrumentId = $iteminstrumentid;
					$result = MySQLiBoundQuery($insertStmt, __FILE__, __LINE__);
				}
				else {
					$updateItemOrder = $itemorder;
					$updateName = $name;
					$updateType = $type;
					$updateModality = $itemmodality;
					$updateMappedName = $itemmappedname;
					$updateExpectedCount = ($itemexpectedcount == "null") ? null : $itemexpectedcount;
					$updateInstrumentId = $iteminstrumentid;
					$updateRowID = (int)$rowid;
					$result = MySQLiBoundQuery($updateStmt, __FILE__, __LINE__);
				}
				$itemorder++;
			}
		}
		
		mysqli_stmt_close($deleteStmt);
		mysqli_stmt_close($insertStmt);
		mysqli_stmt_close($updateStmt);
		
		mysqli_commit($GLOBALS['linki']);
		
		Notice("Checklist updated");
	}

	/* -------------------------------------------- */
	/* ------- SetMissingDataReasonForm ----------- */
	/* -------------------------------------------- */
	function SetMissingDataReasonForm($projectid, $missingdataid, $enrollmentid, $projectchecklistid, $reason) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectid));
		$enrollmentid = mysqli_real_escape_string($GLOBALS['linki'], trim($enrollmentid));
		$projectchecklistid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectchecklistid));
		$reason = mysqli_real_escape_string($GLOBALS['linki'], trim($reason));
		
		?>
		<div align="center">
			<fieldset align="center" style="border: 1px solid #666; width:300px; border-radius: 5px">
			<legend><b>Enter reason for missing data</b></legend>
			<form>
				<input type="hidden" name="action" value="setmissingdatareason">
				<input type="hidden" name="missingdataid" value="<?=$missingdataid?>">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<input type="hidden" name="projectchecklistid" value="<?=$projectchecklistid?>">
				<input type="text" name="reason" style="border: 1px solid #888" value="<?=$reason?>">
				<input type="submit" value="Save">
			</form>
			<br><br><br>
			<a href="projectchecklist.php?action=deletemissingdatareason&projectid=<?=$projectid?>&enrollmentid=<?=$enrollmentid?>&missingdataid=<?=$missingdataid?>">Delete</a> this missing data reason
			</fieldset>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DeleteMissingDataReason ------------ */
	/* -------------------------------------------- */
	function DeleteMissingDataReason($missingdataid) {

		/* perform data checks */
		$missingdataid = mysqli_real_escape_string($GLOBALS['linki'], trim($missingdataid));
		
		if (($missingdataid == '') || ($missingdataid == 0)) {
			Error("data ID blank");
			return;
		}
		
		$sqlstring = "delete from enrollment_missingdata where missingdata_id = '$missingdataid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}
	
	
	/* -------------------------------------------- */
	/* ------- SetMissingDataReason -------------- */
	/* -------------------------------------------- */
	function SetMissingDataReason($projectid, $enrollmentid, $projectchecklistid, $reason) {

		/* perform data checks */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectid));
		$enrollmentid = mysqli_real_escape_string($GLOBALS['linki'], trim($enrollmentid));
		$projectchecklistid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectchecklistid));
		$reason = mysqli_real_escape_string($GLOBALS['linki'], trim($reason));
		
		if (($projectid == '') || ($projectid == 0)) {
			Error("Project ID blank");
			return;
		}
		
		$sqlstring = "insert into enrollment_missingdata (enrollment_id, projectchecklist_id, missing_reason, missingreason_date) values ('$enrollmentid','$projectchecklistid','$reason',now()) on duplicate key update missing_reason = '$reason', missingreason_date = now()";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayEditChecklist --------------- */
	/* -------------------------------------------- */
	function DisplayEditChecklist($projectid) {
	
		$projectid = trim($projectid);
	
		if (($projectid == '') || ($projectid == 0) || (!isInteger($projectid))) {
			Error("Project ID blank");
			return;
		}
		$projectid = (int)$projectid;
		
		$stmt = mysqli_prepare($GLOBALS['linki'], "select project_name from projects where project_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$projectname = htmlspecialchars($row['project_name'], ENT_QUOTES);
		
		$modalityoptions = "<option value=\"\"></option>";
		$modalitycodes = [];
		$stmt = mysqli_prepare($GLOBALS['linki'], "select mod_code from modalities order by mod_code");
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modcode = htmlspecialchars($row['mod_code'], ENT_QUOTES);
			$modalityoptions .= "<option value=\"$modcode\">$modcode</option>";
			$modalitycodes[] = $row['mod_code'];
		}
		mysqli_stmt_close($stmt);

		$instrumentoptions = "<option value=\"\">Select instrument...</option>";
		$instruments = [];
		$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id, instrument_name from instruments where project_id = ? order by instrument_name");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$iid  = (int)$row['instrument_id'];
			$iname = htmlspecialchars($row['instrument_name'], ENT_QUOTES);
			$instrumentoptions .= "<option value=\"$iid\">$iname</option>";
			$instruments[$iid] = $iname;
		}
		mysqli_stmt_close($stmt);
		
		?>
		<div class="ui container">
			<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
			<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
			<style>
				.checklist-select {
					width: 100%;
				}
				.select2-container {
					width: 100% !important;
				}
				.select2-container--default .select2-selection--single {
					border: 1px solid rgba(34,36,38,.15);
					border-radius: .28571429rem;
					height: 38px;
				}
				.select2-container--default .select2-selection--single .select2-selection__rendered {
					line-height: 38px;
					padding-left: 14px;
					color: rgba(0,0,0,.87);
				}
				.select2-container--default .select2-selection--single .select2-selection__arrow {
					height: 36px;
				}
				.checklist-name-column {
					width: 32%;
				}
				.checklist-count-column {
					width: 100px;
				}
				.checklist-count-input {
					max-width: 80px;
				}
			</style>
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header"><?=$projectname?> Checklist Items</h2>
				</div>
				<div class="right aligned column">
					<a href="projectchecklist.php?projectid=<?=$projectid?>" class="ui basic blue button">Back to checklist</a>
				</div>
			</div>
		</div>
		
			<form method="post" action="projectchecklist.php" id="checklistform" onSubmit="RenumberChecklistRows()">
				<input type="hidden" name="action" value="updateprojectchecklist">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				
				<script type="text/javascript">
					/* Assign indexed field names so PHP receives each row as an array entry. */
					function SetChecklistInputNames(row, rownum) {
						row.querySelector("input.checklist-itemid").name = "itemid[" + rownum + "]";
						row.querySelector("input.checklist-itemname").name = "itemname[" + rownum + "]";
						row.querySelector("select.checklist-itemtype").name = "itemtype[" + rownum + "]";
						row.querySelector("select.checklist-modality").name = "modality[" + rownum + "]";
						row.querySelector("input.checklist-mappedname").name = "mappedname[" + rownum + "]";
						row.querySelector("input.checklist-expectedcount").name = "expectedcount[" + rownum + "]";
						row.querySelector("input.checklist-deleteitem").name = "deleteitem[" + rownum + "]";
						row.querySelector("select.checklist-instrument").name = "instrumentid[" + rownum + "]";
						row.querySelector(".checklist-itemorder-display").textContent = rownum;
					}
					
					/* Keep submitted row order contiguous after client-side add/delete actions. */
					function RenumberChecklistRows() {
						var rownum = 1;
						document.querySelectorAll("#checklistitems tr.checklist-row:not(.checklist-template):not(.checklist-deleted)").forEach(function(row) {
							SetChecklistInputNames(row, rownum);
							rownum++;
						});
						document.querySelectorAll("#checklistitems tr.checklist-row.checklist-deleted:not(.checklist-template)").forEach(function(row) {
							SetChecklistInputNames(row, rownum);
							rownum++;
						});
					}
					
					/* Clone the hidden template, make its required fields active, then initialize Select2. */
					function AddChecklistRow() {
						var template = document.getElementById("checklistrowtemplate");
						var row = template.cloneNode(true);
						row.removeAttribute("id");
						row.classList.remove("checklist-template");
						row.style.display = "";
						ResetChecklistDropdowns(row);
						row.querySelector(".checklist-itemname").required = true;
						row.querySelector(".checklist-itemtype").required = true;
						document.getElementById("checklistitems").appendChild(row);
						RenumberChecklistRows();
						InitializeChecklistDropdowns(row);
						UpdateChecklistRowRequirements(row);
						row.querySelector(".checklist-itemname").focus();
					}
					
					/* New unsaved rows can be removed; existing DB rows are hidden and marked for deletion. */
					function DeleteChecklistRow(button) {
						var row = button.closest("tr");
						if (row.querySelector(".checklist-itemid").value == "") {
							row.remove();
						}
						else {
							row.classList.add("checklist-deleted");
							row.style.display = "none";
							row.querySelector(".checklist-deleteitem").value = "1";
						}
						RenumberChecklistRows();
					}
					
					/* Cloned rows can inherit Select2 metadata from the template; strip it before reinitializing. */
					function ResetChecklistDropdowns(context) {
						jQuery(context).find(".select2-container").remove();
						jQuery(context).find("select.checklist-select").add(jQuery(context).filter("select.checklist-select")).each(function() {
							jQuery(this)
								.removeClass("select2-hidden-accessible")
								.removeAttr("data-select2-id")
								.removeAttr("aria-hidden")
								.removeAttr("tabindex");
							jQuery(this).find("option").removeAttr("data-select2-id");
						});
					}
					
					/* Initialize Select2 only for real checklist rows, never the hidden template. */
					function InitializeChecklistDropdowns(context) {
						if (!window.jQuery || !jQuery.fn.select2) {
							return;
						}
						
						jQuery(context).find("tr.checklist-template select.checklist-select").add(jQuery(context).filter("tr.checklist-template select.checklist-select")).each(function() {
							if (jQuery(this).hasClass("select2-hidden-accessible")) {
								jQuery(this).select2("destroy");
							}
						});
						
						jQuery(context).find("select.checklist-select").add(jQuery(context).filter("select.checklist-select")).filter(function() {
							return jQuery(this).closest("tr.checklist-template").length == 0;
						}).each(function() {
							if (jQuery(this).hasClass("select2-hidden-accessible")) {
								return;
							}
								
							jQuery(this).select2({
								minimumResultsForSearch: Infinity,
								width: "style"
							});
						});
					}
					
					/* Show/hide modality, mappedname, and instrument cells based on the selected type. */
					function UpdateChecklistRowRequirements(row) {
						var typeVal          = row.querySelector("select.checklist-itemtype").value;
						var modalitySelect   = row.querySelector("select.checklist-modality");
						var mappedInput      = row.querySelector("input.checklist-mappedname");
						var instrumentSelect = row.querySelector("select.checklist-instrument");
						var modalityDiv      = row.querySelector(".checklist-modality-container");
						var instrumentDiv    = row.querySelector(".checklist-instrument-container");
						var isImaging        = (typeVal === "Imaging");
						var isInstrument     = (typeVal === "Instrument");
						modalitySelect.required   = isImaging;
						mappedInput.required      = (typeVal === "Imaging" || typeVal === "Intervention" || typeVal === "Observation");
						instrumentSelect.required = isInstrument;
						modalityDiv.style.display   = isImaging    ? "" : "none";
						instrumentDiv.style.display = isInstrument ? "" : "none";
					}

					/* Existing rows are present on page load and need Select2 setup once. */
					jQuery(document).ready(function() {
						InitializeChecklistDropdowns(document);
						/* Initialize required state for all existing rows. */
						document.querySelectorAll("#checklistitems tr.checklist-row:not(.checklist-template)").forEach(function(row) {
							UpdateChecklistRowRequirements(row);
						});
						/* React to type changes (works for both existing and newly-added rows). */
						jQuery("#checklistitems").on("change", "select.checklist-itemtype", function() {
							UpdateChecklistRowRequirements(this.closest("tr"));
						});
					});
				</script>
				
				<table class="ui small very compact selectable table">
					<thead>
						<tr>
							<th>Order</th>
							<th class="checklist-name-column">Name</th>
							<th>Type</th>
							<th>Modality</th>
							<th>Mapped name<br><span class="tiny">comma separated for multiple protocol or variable names</span></th>
							<th>Instrument</th>
							<th class="checklist-count-column">Expected count</th>
							<th></th>
						</tr>
					</thead>
					<tbody id="checklistitems">
						<tr id="checklistrowtemplate" class="checklist-row checklist-template" style="display: none">
							<td class="center aligned"><span class="checklist-itemorder-display"></span></td>
							<td>
								<input type="hidden" class="checklist-itemid" value="">
								<input type="hidden" class="checklist-deleteitem" value="0">
								<div class="ui fluid input">
									<input type="text" class="checklist-itemname" value="">
								</div>
							</td>
							<td>
								<select class="checklist-select checklist-itemtype">
									<option value="">Select item type...</option>
									<option value="Checkbox">Checkbox</option>
									<option value="Imaging">Imaging</option>
									<option value="Intervention">Intervention</option>
									<option value="Observation">Observation</option>
									<option value="Diagnosis">Diagnosis</option>
									<option value="Instrument">Instrument</option>
								</select>
							</td>
							<td>
								<div class="checklist-modality-container" style="display:none">
									<select class="checklist-select checklist-modality">
										<?=$modalityoptions?>
									</select>
								</div>
							</td>
							<td><div class="ui fluid input"><input type="text" class="checklist-mappedname" value=""></div></td>
							<td>
								<div class="checklist-instrument-container" style="display:none">
									<select class="checklist-select checklist-instrument">
										<?=$instrumentoptions?>
									</select>
								</div>
							</td>
							<td><div class="ui input checklist-count-input"><input type="number" class="checklist-expectedcount" min="1" value=""></div></td>
							<td><button type="button" class="ui red icon button" onClick="DeleteChecklistRow(this)" title="Delete checklist item"><i class="trash alternate icon"></i></button></td>
						</tr>
						<?
							$itemorder = 1;
							$stmt = mysqli_prepare($GLOBALS['linki'], "select * from project_checklist where project_id = ? order by item_order asc, projectchecklist_id asc");
							mysqli_stmt_bind_param($stmt, 'i', $projectid);
							$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$checklistrowid = $row['projectchecklist_id'];
								$itemname = htmlspecialchars($row['item_name'], ENT_QUOTES);
								$itemtype = $row['item_type'];
								$imagingmodality = htmlspecialchars($row['imaging_modality'] ?? "", ENT_QUOTES);
								$mappedname = htmlspecialchars($row['mapped_name'] ?? "", ENT_QUOTES);
								$expectedcount = htmlspecialchars($row['expected_count'] ?? "", ENT_QUOTES);
								$rowinstrumentid = (int)($row['instrument_id'] ?? 0);
								$rawmodality = $row['imaging_modality'] ?? "";
							?>
								<tr class="checklist-row">
									<td class="center aligned"><span class="checklist-itemorder-display"><?=$itemorder?></span></td>
									<td>
										<input type="hidden" class="checklist-itemid" name="itemid[<?=$itemorder?>]" value="<?=$checklistrowid?>">
										<input type="hidden" class="checklist-deleteitem" name="deleteitem[<?=$itemorder?>]" value="0">
										<div class="ui fluid input">
											<input type="text" class="checklist-itemname" name="itemname[<?=$itemorder?>]" value="<?=$itemname?>" required>
										</div>
									</td>
									<td>
										<select name="itemtype[<?=$itemorder?>]" class="checklist-select checklist-itemtype" required>
											<option value="">Select item type...</option>
											<option value="Checkbox" <? if ($itemtype == "Checkbox") { echo "selected"; } ?>>Checkbox</option>
											<option value="Imaging" <? if ($itemtype == "Imaging") { echo "selected"; } ?>>Imaging</option>
											<option value="Intervention" <? if ($itemtype == "Intervention") { echo "selected"; } ?>>Intervention</option>
											<option value="Observation" <? if ($itemtype == "Observation") { echo "selected"; } ?>>Observation</option>
											<option value="Diagnosis" <? if ($itemtype == "Diagnosis") { echo "selected"; } ?>>Diagnosis</option>
											<option value="Instrument" <? if ($itemtype == "Instrument") { echo "selected"; } ?>>Instrument</option>
										</select>
									</td>
									<td>
										<div class="checklist-modality-container" <? if ($itemtype != "Imaging") { echo 'style="display:none"'; } ?>>
											<select name="modality[<?=$itemorder?>]" class="checklist-select checklist-modality">
												<option value=""></option>
												<? foreach ($modalitycodes as $mc) {
													$esc = htmlspecialchars($mc, ENT_QUOTES);
													$sel = (strcasecmp($mc, $rawmodality) === 0) ? " selected" : "";
													echo "<option value=\"$esc\"$sel>$esc</option>";
												} ?>
											</select>
										</div>
									</td>
									<td><div class="ui fluid input"><input type="text" class="checklist-mappedname" name="mappedname[<?=$itemorder?>]" value="<?=$mappedname?>"></div></td>
									<td>
										<div class="checklist-instrument-container" <? if ($itemtype != "Instrument") { echo 'style="display:none"'; } ?>>
											<select name="instrumentid[<?=$itemorder?>]" class="checklist-select checklist-instrument">
												<option value="">Select instrument...</option>
												<? foreach ($instruments as $iid => $iname) {
													$sel = ($iid === $rowinstrumentid && $rowinstrumentid > 0) ? " selected" : "";
													echo "<option value=\"$iid\"$sel>" . htmlspecialchars($iname, ENT_QUOTES) . "</option>";
												} ?>
											</select>
										</div>
									</td>
									<td><div class="ui input checklist-count-input"><input type="number" class="checklist-expectedcount" name="expectedcount[<?=$itemorder?>]" min="1" value="<?=$expectedcount?>"></div></td>
									<td><button type="button" class="ui red icon button" onClick="DeleteChecklistRow(this)" title="Delete checklist item"><i class="trash alternate icon"></i></button></td>
								</tr>
								<?
								$itemorder++;
							}
							mysqli_stmt_close($stmt);
						?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="7" align="right" style="padding-right: 20px">
							<button class="ui button" type="button" onClick="AddChecklistRow()"><i class="plus square outline icon"></i> Add checklist item</button>
							<input class="ui primary button" type="submit" value="Update">
							</td>
						</tr>
					</tfoot>
				</table>
			</form>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProjectChecklist ------------ */
	/* -------------------------------------------- */
	function DisplayProjectChecklist($projectid) {
	
		$projectid = trim($projectid);
	
		if (($projectid == '') || ($projectid == 0) || (!isInteger($projectid))) {
			Error("Project ID blank");
			return;
		}
		$projectid = (int)$projectid;
		
		/* get project information */
		$p = GetProjectInfo($projectid);
		$projectname = $p['projectName'];
		$usecustomid = $p['projectUseCustomID'];
	
		/* get the main checklist items */
		$checklist = array();
		$i = 0;
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from project_checklist where project_id = ? order by item_order asc");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$checklist[$i]['id'] = $row['projectchecklist_id'];
			$checklist[$i]['name'] = $row['item_name'];
			$checklist[$i]['desc'] = $row['item_desc'];
			$checklist[$i]['order'] = $row['item_order'];
			$checklist[$i]['modality'] = $row['imaging_modality'];
			$checklist[$i]['protocol'] = $row['mapped_name'];
			$checklist[$i]['count'] = $row['expected_count'];
			$i++;
		}
		mysqli_stmt_close($stmt);
		
		/* get the project enrollment data */
		$enrollment = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], "select a.*, b.subject_id, b.uid, b.guid, b.isactive, c.study_id from enrollment a left join subjects b on a.subject_id = b.subject_id left join studies c on a.enrollment_id = c.enrollment_id where a.project_id = ? and b.isactive = 1 order by b.uid asc");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		//PrintSQL($sqlstring);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$studyid = $row['study_id'];
			$enrollment[$uid]['guid'] = $row['guid'];
			$enrollment[$uid]['enrollment_id'] = $row['enrollment_id'];
			$enrollment[$uid]['project_id'] = $row['project_id'];
			$enrollment[$uid]['subject_id'] = $row['subject_id'];
			$enrollment[$uid]['isactive'] = $row['isactive'];
			$enrollment[$uid]['enroll_startdate'] = $row['enroll_startdate'];
			$enrollment[$uid]['enroll_enddate'] = $row['enroll_enddate'];
			$enrollment[$uid]['enroll_subgroup'] = $row['enroll_subgroup'];

		}
		mysqli_stmt_close($stmt);
		$numenrollments = count($enrollment);

		/* start to create the ag-grid columns */
		$columnDefs = array(
			array('headerName' => 'Primary ID', 'field' => 'primaryid', 'minWidth' => 130, 'flex' => 1),
			array('headerName' => 'UID', 'field' => 'uid', 'minWidth' => 120, 'flex' => 1, 'pinned' => 'left'),
			array('headerName' => 'GUID', 'field' => 'guid', 'minWidth' => 170, 'flex' => 1.2),
			array('headerName' => 'Enroll date', 'field' => 'enrolldate', 'minWidth' => 130, 'flex' => 1),
			array('headerName' => '# studies', 'field' => 'numstudies', 'minWidth' => 110, 'maxWidth' => 120, 'flex' => 0.7),
			array('headerName' => 'Group', 'field' => 'group', 'minWidth' => 120, 'flex' => 1)
		);
		
		/* get the checklist items (each will appear as a column) and add them to the ag-grid columns */
		$totals = array(0,0,0,0,0);
		$ii = 5;
		foreach ($checklist as $i => $item) {
			$name = $item['name'];
			$desc = $item['desc'];
			$modality = $item['modality'];
			$protocol = $item['protocol'];
			$columnDefs[] = array(
				'headerName' => $name,
				'field' => 'checklist_' . $item['id'],
				'minWidth' => 120,
				'flex' => 1,
				'headerTooltip' => "Modality: $modality\nProtocol: $protocol\nDescription: $desc"
			);
			$totals[$ii] = 0;
			$ii++;
		}
		$columnDefs[] = array('headerName' => 'Complete data?', 'field' => 'complete', 'minWidth' => 150, 'flex' => 1.2);
		
		$rowdata = array();
		$c = 0;
		/* loop through the subjects - each subject will have its own row in the rendered table */
		if (is_array($enrollment)) foreach ($enrollment as $uid => $subject) {
			$guid = $subject['guid'];
			$enrolldate = $subject['enroll_startdate'];
			$enrollsubgroup = $subject['enroll_subgroup'];
			$enrollmentid = $subject['enrollment_id'];
			$subjectid = $subject['subject_id'];
			$isactive = $subject['isactive'];
			
			$rowtotal = 0;
			
			if ($enrollmentid == '') {
				echo "ENROLLMENT ID blank for [$uid]...<br>";
				continue;
			}
			
			$studyids = GetStudiesByEnrollment($projectid);
			
			/* get project specific altuid */
			$stmt = mysqli_prepare($GLOBALS['linki'], "select altuid from subject_altuid where subject_id = ? and enrollment_id = ? and isprimary = 1");
			mysqli_stmt_bind_param($stmt, 'ii', $subjectid, $enrollmentid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$altuid = $row['altuid'];
			
			if (!$isactive) { $deleted = "Deleted"; }
			else { $deleted = ""; }
			
			if ($uid != "") { $totals[0]++; }
			if ($guid != "") { $totals[1]++; }
			if ($altuid != "") { $totals[2]++; }
			if ($enrolldate != "") { $totals[3]++; }
			if ($enrollsubgroup != "") { $totals[4]++; }

			if (($usecustomid == 1) && ($altuid == "")) {
				$customidstyle = "border: 1px solid red; background-color: orange";
				$customidtext = "<i style='color: red'>missing ID</i>";
			}
			else {
				$customidstyle = "";
				$customidtext = $altuid;
			}
			
			/* start building the grid row */
			$gridrow = array(
				'primaryid' => $customidtext,
				'primaryid_style' => $customidstyle,
				'uid' => "<a href=\"subjects.php?id=$subjectid\">$uid</a> $deleted",
				'guid' => $guid,
				'enrolldate' => "<a href=\"enrollment.php?id=$enrollmentid\">$enrolldate</a>",
				'numstudies' => count($studyids),
				'group' => $enrollsubgroup
			);
			
			$ii = 5;
			/* check if they have any studies */
			if ((count($studyids) > 0) && ($studyids != '')) {
				foreach ($checklist as $i => $item) {
					$itemid = strtolower($item['id']);
					$modality = strtolower($item['modality']);
					$protocol = $item['protocol'];
					$count = $item['count'];
					
					$c++;
					
					$protocols = explode(',', $protocol);
					foreach ($protocols as $i => $p) { $protocols[$i] = "'" . mysqli_real_escape_string($GLOBALS['linki'], trim($protocols[$i])) . "'"; }
					
					$msg = "";
					/* check for valid modality */
					if (!preg_match('/^[a-z0-9]+$/', $modality)) {
						$modality = "";
					}
					$seriestable = GetSeriesTableName($modality);
					
					$tablelike = $seriestable;
					$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like ?";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 's', $tablelike);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					mysqli_stmt_close($stmt);
					if (($seriestable != "") && (mysqli_num_rows($result) > 0)) {
						
						if (strtolower($modality) == "mr") { $numfilesfield = "numfiles"; } else { $numfilesfield = "series_numfiles"; }
						/* valid modality */
						$sqlstring = "select study_id from $seriestable where study_id in (" . implode(',',$studyids) . ") and (trim(series_desc) in (" . implode(',',$protocols) . ") or trim(series_protocol) in (" . implode(',',$protocols) . ")) and $numfilesfield > 0";
						//PrintVariable($sqlstring);
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$studyid = $row['study_id'];
							$msg = "<a href='studies.php?id=$studyid'>&#10004;</a>";
							$totals[$ii]++;
							$rowtotal++;
						}
						else {
							$msg = "";
						}
					}
					else {
						/* invalid modality */
						$stmt = mysqli_prepare($GLOBALS['linki'], "select * from enrollment_checklist where enrollment_id = ? and projectchecklist_id = ?");
						mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $itemid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							$msg = "&#10004;";
							$totals[$ii]++;
							$rowtotal++;
						}
						else {
							$msg = "";
						}
						mysqli_stmt_close($stmt);
					}
					
					/* done checking, display if it was found or not */
					if ($msg == "") {
						$stmt = mysqli_prepare($GLOBALS['linki'], "select * from enrollment_missingdata where enrollment_id = ? and projectchecklist_id = ?");
						mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $itemid);
						$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
						if (mysqli_num_rows($result) > 0) {
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$missingdataid = $row['missingdata_id'];
							$reason = $row['missing_reason'];
							$date = $row['missingreason_date'];
							$gridrow["checklist_$itemid"] = "<a href=\"projectchecklist.php?action=setmissingdatareasonform&missingdataid=$missingdataid&projectid=$projectid&enrollmentid=$enrollmentid&projectchecklistid=$itemid&reason=$reason\">&#10006;</a>";
							$gridrow["checklist_$itemid" . "_style"] = "background-image: repeating-linear-gradient(-45deg, transparent, transparent 5px, #ddd 5px, #ddd 10px);";
							$gridrow["checklist_$itemid" . "_tooltip"] = "<b>$reason</b> - $date";
						}
						else {
							$gridrow["checklist_$itemid"] = "<a href=\"projectchecklist.php?action=setmissingdatareasonform&projectid=$projectid&enrollmentid=$enrollmentid&projectchecklistid=$itemid\">?</a>";
							$gridrow["checklist_$itemid" . "_style"] = "border-left: 1px solid #ffd699; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #ffe0b3 5px, #ffe0b3 10px);";
							$gridrow["checklist_$itemid" . "_tooltip"] = "Click to set reason for missing data";
						}
						mysqli_stmt_close($stmt);
					}
					else {
						$gridrow["checklist_$itemid"] = $msg;
					}
					$ii++;
				}
				
				if ($rowtotal == count($checklist)) {
					$gridrow['complete'] = "&#10004;";
					$totals[$ii]++;
				}
				else {
					$gridrow['complete'] = "Nope. Only $rowtotal of " . count($checklist);
					$gridrow['complete_style'] = "text-align: center; font-size:8pt;";
				}
			}
			else {
				foreach ($checklist as $i => $item) {
					$gridrow['checklist_' . $item['id']] = "";
				}
				$gridrow['complete'] = "No studies";
				$gridrow['complete_style'] = "text-align: center;";
			}
			$rowdata[] = $gridrow;
		}
		
		$pinnedrow = array(
			'primaryid' => 'Totals',
			'uid' => $totals[0],
			'guid' => $totals[1],
			'enrolldate' => $totals[3],
			'numstudies' => '',
			'group' => $totals[4]
		);
		$ii = 5;
		foreach ($checklist as $i => $item) {
			$pinnedrow['checklist_' . $item['id']] = $totals[$ii];
			$ii++;
		}
		$pinnedrow['complete'] = $totals[$ii];
		
		$columnData = json_encode($columnDefs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$rowData = json_encode($rowdata, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$pinnedData = json_encode(array($pinnedrow), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<style>
			#projectchecklistgrid {
				width: 100%;
				height: 64vh;
			}
			.projectchecklist-html-cell {
				width: 100%;
				height: 100%;
				display: flex;
				align-items: center;
			}
			.projectchecklist-html-cell.centered {
				justify-content: center;
			}
		</style>
		
		<div class="ui two column grid">
			<div class="column">
				<h2 class="ui header"><?=$projectname?> Checklist</h2>
				Displaying <b><?=$numenrollments?> enrollments</b>
			</div>
			<div class="right aligned column">
				<a href="projectchecklist.php?action=editchecklist&projectid=<?=$projectid?>" class="ui primary basic button">Edit checklist</a>
			</div>
		</div>
		<div id="projectchecklistgrid"></div>
		<div class="ui bottom attached secondary segment">Displaying <span id="projectchecklistrowcount">0</span> rows</div>
		
		<script>
			const projectChecklistColumnDefs = <?=$columnData?>;
			const projectChecklistRowData = <?=$rowData?>;
			const projectChecklistPinnedData = <?=$pinnedData?>;
			let projectChecklistGridApi;
			
			/* Render stored HTML links/checkmarks while applying per-cell styles and tooltips. */
			function projectChecklistHtmlRenderer(params) {
				const field = params.colDef.field;
				const wrapper = document.createElement('span');
				wrapper.className = 'projectchecklist-html-cell';
				
				if (field == 'numstudies' || field == 'complete' || field.indexOf('checklist_') == 0) {
					wrapper.className += ' centered';
				}
				
				if (params.data && params.data[field + '_style']) {
					wrapper.setAttribute('style', params.data[field + '_style']);
				}
				if (params.data && params.data[field + '_tooltip']) {
					wrapper.className += ' projectchecklist-html-tooltip';
					wrapper.setAttribute('data-html', params.data[field + '_tooltip']);
				}
				
				wrapper.innerHTML = (params.value == null) ? '' : params.value;
				return wrapper;
			}
			
			/* Keep the pinned totals row visually distinct from enrollment rows. */
			function projectChecklistCellStyle(params) {
				if (params.node.rowPinned) {
					return { 'font-weight': 'bold', 'background-color': '#f7f7f7' };
				}
				return null;
			}
			
			/* Reflect the current filtered row count below the grid. */
			function updateProjectChecklistRowCount() {
				if (projectChecklistGridApi) {
					document.getElementById('projectchecklistrowcount').textContent = projectChecklistGridApi.getDisplayedRowCount();
				}
			}
			
			/* Apply common rendering behavior to the PHP-generated column definitions. */
			projectChecklistColumnDefs.forEach(function(columnDef) {
				columnDef.cellRenderer = projectChecklistHtmlRenderer;
				columnDef.cellStyle = projectChecklistCellStyle;
				columnDef.wrapHeaderText = true;
				columnDef.autoHeaderHeight = true;
			});

			const myTheme = agGrid.themeBalham.withParams({
				headerTextColor: 'white',
				headerBackgroundColor: '#333',
				headerFontSize: '16px',
				columnBorder: { style: 'solid', color: '#ddd' },
			});
			
			const projectChecklistGridOptions = {
				theme: myTheme,
				columnDefs: projectChecklistColumnDefs,
				rowData: projectChecklistRowData,
				pinnedBottomRowData: projectChecklistPinnedData,
				defaultColDef: { sortable: true, filter: true, resizable: true },
				animateRows: false,
				suppressMovableColumns: true,
				onFirstDataRendered: updateProjectChecklistRowCount,
				onFilterChanged: updateProjectChecklistRowCount,
				onModelUpdated: updateProjectChecklistRowCount
			};
			
			/* Create the grid and attach jQuery UI HTML tooltips after the DOM is ready. */
			$(document).ready(function() {
				projectChecklistGridApi = agGrid.createGrid(document.getElementById('projectchecklistgrid'), projectChecklistGridOptions);
				$('#projectchecklistgrid').tooltip({
					items: ".projectchecklist-html-tooltip",
					content: function() {
						return $(this).attr("data-html");
					}
				});
				updateProjectChecklistRowCount();
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetStudiesByEnrollment ------------- */
	/* -------------------------------------------- */
	function GetStudiesByEnrollment($projectid) {
		/* get studies associated with this enrollment */
		$studyids = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], "select study_id from studies where enrollment_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$studyids[] = (int)$row['study_id'];
		}
		mysqli_stmt_close($stmt);
		
		return $studyids;
	}
	
	
?>

<? include("footer.php") ?>
