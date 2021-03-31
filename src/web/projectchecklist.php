<?
 // ------------------------------------------------------------------------------
 // NiDB projectchecklist.php
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
	$itemorder = GetVariable("itemorder");
	$itemname = GetVariable("itemname");
	$modality = GetVariable("modality");
	$protocol = GetVariable("protocol");
	$itemcount = GetVariable("itemcount");
	$frequency = GetVariable("frequency");
	$frequencyunit = GetVariable("frequencyunit");
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
    $a['includeallmeasures'] = GetVariable("includeallmeasures");
    $a['includeallvitals'] = GetVariable("includeallvitals");
    $a['includealldrugs'] = GetVariable("includealldrugs");
    $a['includeemptysubjects'] = GetVariable("includeemptysubjects");
    $a['grouprowsby'] = GetVariable("grouprowsby");
	
	/* determine action */
	switch ($action) {
		case 'updateprojectchecklist':
			UpdateProjectChecklist($projectid, $itemid, $itemorder, $itemname, $modality, $protocol, $itemcount, $frequency, $frequencyunit);
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
	function UpdateProjectChecklist($projectid, $itemid, $itemorder, $itemname, $modality, $protocol, $itemcount, $frequency, $frequencyunit) {

		/* perform data checks */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], trim($projectid));
		
		$itemid = mysqli_real_escape_array($itemid);
		$itemorder = mysqli_real_escape_array($itemorder);
		$itemname = mysqli_real_escape_array($itemname);
		$modality = mysqli_real_escape_array($modality);
		$protocol = mysqli_real_escape_array($protocol);
		$itemcount = mysqli_real_escape_array($itemcount);
		$frequency = mysqli_real_escape_array($frequency);
		$frequencyunit = mysqli_real_escape_array($frequencyunit);
		
		if (!isInteger($projectid)) { echo "Invalid project ID [$projectid]"; return; }

		/* update the checklist */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$i=1;

		foreach ($itemorder as $order) {

			if ((trim($itemorder[$i]) != "") && (trim($itemname[$i]) != "")) {
				if (trim($itemid[$i] == "")) {
					$sqlstring = "insert into project_checklist (project_id, item_name, item_order, modality, protocol_name, count, frequency, frequency_unit) values ($projectid, '$itemname[$i]', '$itemorder[$i]', '$modality[$i]', '$protocol[$i]', '$itemcount[$i]', '$frequency[$i]', '$frequencyunit[$i]')";
				}
				else {
					$sqlstring = "update project_checklist set item_name = '$itemname[$i]', item_order = '$itemorder[$i]', modality = '$modality[$i]', protocol_name = '$protocol[$i]', count = '$itemcount[$i]', frequency = '$frequency[$i]', frequency_unit = '$frequencyunit[$i]' where projectchecklist_id = $itemid[$i]";
				}
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
			else if ((trim($itemorder[$i]) != "") && (trim($itemname[$i]) == "")) {
				//delete
				$sqlstring = "delete from project_checklist where item_order = '$itemorder[$i]'  AND projectchecklist_id = '$itemid[$i]'";

				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}

			$i++;
		}
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
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
	
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
	
		if (($projectid == '') || ($projectid == 0)) {
			Error("Project ID blank");
			return;
		}
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		
		$neworder = 1;

		DisplayProjectsMenu('checklist', $projectid);

	?>

	<datalist id="modalitylist">
		<?
			$sqlstring = "select * from modalities";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$modcode = $row['mod_code'];
				?><option value="<?=$modcode?>"><?
			}
		?>
	</datalist>
	
	<div align="center">
	<br><br>
	This table is a list of expected items for this project<br><br>
	<form method="POST" action="projectchecklist.php">
	<input type="hidden" name="action" value="updateprojectchecklist">
	<input type="hidden" name="projectid" value="<?=$projectid?>">
	<table class="graydisplaytable dropshadow">
		<thead>
			<tr>
				<th>Order</th>
				<th>Name</th>
				<th>Modality<br><span class="tiny">Leave blank to use a checkbox</span></th>
				<th>Protocol<br><span class="tiny">comma separated for multiple protocols</span></th>
				<th>Count</th>
				<!--<th colspan="2">Frequency</th>-->
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from project_checklist where project_id = $projectid order by item_order";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['projectchecklist_id'];
					$itemname = $row['item_name'];
					$itemorder = $row['item_order'];
					$modality = $row['modality'];
					$protocol = $row['protocol_name'];
					$itemcount = $row['count'];
					$frequency = $row['frequency'];
					$frequencyunit = $row['frequency_unit'];
					//frequency_unit	enum('hour', 'day', 'week', 'month', 'year')					
					
			?>
			<input type="hidden" name="itemid[<?=$neworder?>]" value="<?=$id?>">
			<tr>
				<td><input type="number" name="itemorder[<?=$neworder?>]" value="<?=$neworder?>" style="width:40px"></td>
				<td><input type="text" name="itemname[<?=$neworder?>]" value="<?=$itemname?>" size="50"></td>
				<td><input type="text" name="modality[<?=$neworder?>]" value="<?=$modality?>" list="modalitylist"></td>
				<td><input type="text" name="protocol[<?=$neworder?>]" value="<?=$protocol?>" size="50"></td>
				<td><input type="number" name="itemcount[<?=$neworder?>]" value="<?=$itemcount?>" style="width:40px"></td>
				<!--<td><input type="text" name="frequency[<?=$neworder?>]" value="<?=$frequency?>"></td>-->
				<!--<td><input type="text" name="frequencyunit[<?=$neworder?>]" value="<?=$frequencyunit?>"></td>-->
			</tr>
			<? 
					$neworder++;
				}
				for ($i=0;$i<5;$i++) {
			?>
			<input type="hidden" name="itemid[<?=$neworder?>]" value="">
			<tr>
				<td><input type="number" name="itemorder[<?=$neworder?>]" value="<?=$neworder?>" style="width:40px"></td>
				<td><input type="text" name="itemname[<?=$neworder?>]" size="50"></td>
				<td><input type="text" name="modality[<?=$neworder?>]" list="modalitylist"></td>
				<td><input type="text" name="protocol[<?=$neworder?>]" size="50"></td>
				<td><input type="number" name="itemcount[<?=$neworder?>]" style="width:40px" value="1"></td>
				<!--<td><input type="text" name="frequency[<?=$neworder?>]"></td>-->
				<!--<td><input type="text" name="frequencyunit[<?=$neworder?>]"></td>-->
			</tr>
			<?
					$neworder++;
				}
			?>
			<tr>
				<td colspan="5" align="right" style="padding-right: 20px"><input type="submit" value="Save/Update"></td>
			</tr>
		</tbody>
	</table>
	</div>
	<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProjectChecklist ------------ */
	/* -------------------------------------------- */
	function DisplayProjectChecklist($projectid) {
	
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
	
		if (($projectid == '') || ($projectid == 0)) {
			Error("Project ID blank");
			return;
		}
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$usecustomid = $row['project_usecustomid'];
	
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
		
		/* get the project enrollment data */
		$sqlstring = "select a.*, b.subject_id, b.uid, b.guid, b.isactive, c.study_id from enrollment a left join subjects b on a.subject_id = b.subject_id left join studies c on a.enrollment_id = c.enrollment_id where a.project_id = $projectid and b.isactive = 1 order by b.uid asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
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
		$numenrollments = count($enrollment);

		DisplayProjectsMenu('checklist', $projectid);
		
		?>
		<br>
		<div class="ui container">
		<b>Displaying <?=$numenrollments?> enrollments</b> <span class="tiny">Table is sortable. Click column headers to sort</span><br><br>
		<table class="ui celled very compact selectable black table">
			<thead>
			<tr>
				<!--<th>Merge</th>-->
				<th class="ui inverted attached header">Primary ID</th>
				<th class="ui inverted attached header">UID</th>
				<th class="ui inverted attached header">GUID</th>
				<th class="ui inverted attached header">Enroll date</th>
				<th class="ui inverted attached header"># studies</th>
				<th class="ui inverted attached header">Group</th>
		<?
		$totals = array(0,0,0,0,0);
		$ii = 5;
		foreach ($checklist as $i => $item) {
			$name = $item['name'];
			$desc = $item['desc'];
			$modality = $item['modality'];
			$protocol = $item['protocol'];
			?>
			<th data-sort="string-ins" title="<b>Modality</b> <?=$modality?><br><b>Protocol</b> <?=$protocol?><br><b>Description</b> <?=$desc?>"  style="background-color: #444; color: #fff"><?=$name?><br><span class="tiny" style="color: #fff"><?=$modality?></span></th>
			<?
			$totals[$ii] = 0;
			$ii++;
		}
		?>
				<th class="ui inverted attached header">Complete data?</th>
			</tr>
			</thead>
			<tbody>
		<?
		
		$c = 0;
		/* loop through the subjects */
		foreach ($enrollment as $uid => $subject) {
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
			/* get studies associated with this enrollment */
			$studyids = array();
			$sqlstring = "select study_id from studies where enrollment_id = $enrollmentid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyids[] = "'" . $row['study_id'] . "'";
			}
			
			/* get project specific altuid */
			$sqlstring = "select altuid from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid and isprimary = 1";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
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
			
			?>
			<tr>
				<!--<td><input type="checkbox" name="uids[]" value="<?=$uid?>"></td>-->
				<td style="<?=$customidstyle?>" class="tt"><?=$customidtext?></td>
				<td class="tt"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a> <?=$deleted?></td>
				<td><?=$guid?></td>
				<td><a href="enrollment.php?id=<?=$enrollmentid?>"><?=$enrolldate?></a></td>
				<td><?=count($studyids)?></td>
				<td><?=$enrollsubgroup?></td>
			<?
			$ii = 5;
			/* check if they have any studies */
			if ((count($studyids) > 0) && ($studyids != '')) {
				foreach ($checklist as $i => $item) {
					$itemid = strtolower($item['id']);
					$modality = strtolower($item['modality']);
					$protocol = $item['protocol'];
					$count = $item['count'];
					$frequency = $item['frequency'];
					$frequencyunit = $item['frequencyunit'];
					
					$c++;
					
					$protocols = explode(',', $protocol);
					foreach ($protocols as $i => $p) { $protocols[$i] = "'" . trim($protocols[$i]) . "'"; }
					
					$msg = "";
					/* check for valid modality */
					$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($modality) . "_series'";
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					if (mysqli_num_rows($result) > 0) {
						
						if (strtolower($modality) == "mr") { $numfilesfield = "numfiles"; } else { $numfilesfield = "series_numfiles"; }
						/* valid modality */
						$sqlstring = "select study_id from $modality" . "_series where study_id in (" . implode(',',$studyids) . ") and (trim(series_desc) in (" . implode(',',$protocols) . ") or trim(series_protocol) in (" . implode(',',$protocols) . ")) and $numfilesfield > 0";
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
						$sqlstring = "select * from enrollment_checklist where enrollment_id = $enrollmentid and projectchecklist_id = $itemid";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							$msg = "&#10004;";
							$totals[$ii]++;
							$rowtotal++;
						}
						else {
							$msg = "";
						}
					}
					
					/* done checking, display if it was found or not */
					if ($msg == "") {
						$sqlstring = "select * from enrollment_missingdata where enrollment_id = '$enrollmentid' and projectchecklist_id = '$itemid'";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$missingdataid = $row['missingdata_id'];
							$reason = $row['missing_reason'];
							$date = $row['missingreason_date'];
							?><td style="background-image: repeating-linear-gradient(-45deg, transparent, transparent 5px, #ddd 5px, #ddd 10px);" title="<b><?=$reason?></b> - <?=$date?>"><a href="projectchecklist.php?action=setmissingdatareasonform&missingdataid=<?=$missingdataid?>&projectid=<?=$projectid?>&enrollmentid=<?=$enrollmentid?>&projectchecklistid=<?=$itemid?>&reason=<?=$reason?>">&#10006;</a></td><?
						}
						else {
							?><td style="border-left: 1px solid #ffd699; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #ffe0b3 5px, #ffe0b3 10px);" title="Click to set reason for missing data"><a href="projectchecklist.php?action=setmissingdatareasonform&projectid=<?=$projectid?>&enrollmentid=<?=$enrollmentid?>&projectchecklistid=<?=$itemid?>">?</a></td><?
						}
					}
					else {
						?><td><?=$msg?></td><?
					}
					$ii++;
				}
				
				if ($rowtotal == count($checklist)) {
					?><td>&#10004;</td><?
					$totals[$ii]++;
				}
				else {
					?><td style="border-left: 1px solid #ccc; text-align: center; font-size:8pt">Nope. Only <?=$rowtotal?> of <?=count($checklist)?></td><?
				}
			}
			else {
				?><td colspan="<?=count($checklist)?>" align="center" style="border-left: 1px solid #ccc">No studies</td><?
			}
			?>
			</tr>
			<?
		}
		?>
			</tbody>
			<tfoot>
			<tr>
				<th>Totals </th>
				<?
				foreach ($totals as $i => $num) {
					?><th><?=$num?></th><?
				}
				?>
			</tr>
			</tfoot>
		</table>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAnalysisSummaryBuilder ------ */
	/* -------------------------------------------- */
	function DisplayAnalysisSummaryBuilder($projectid, $a) {
		
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
	
		if (($projectid == '') || ($projectid == 0)) {
			Error("Project ID blank");
			return;
		}

		DisplayProjectsMenu('checklist', $projectid);
		
		?>
		<br><br>
		<span style="font-size: 16pt; font-weight: bold">Analysis Summary Builder</span>
		
		<table style="width: 100%; height: 100%">
			<tr>
				<td width="15%" valign="top">
					<form method="post" action="projectchecklist.php">
					<input type="hidden" name="action" value="viewanalysissummary">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<table width="100%">
						<tr>
							<td style="background-color: #526FAA; font-weight: bold; color: #fff; padding: 5px" align="center">
								Protocols
							</td>
						</tr>
						<tr>
							<td style="padding-left: 15px">
								<b>MR</b><br>
								<input type="checkbox" name="includeprotocolparms" <? if ($a['includeprotocolparms']) { echo "checked"; } ?> value="1">Include protocol parameters<br>
								<input type="checkbox" name="includemrqa" <? if ($a['includerqa']) { echo "checked"; } ?> value="1">Include QA
								<br>
								<select name="mr_protocols[]" multiple style="width: 450px" size="6">
									<option value="NONE" <? if (in_array("NONE", $a['mr_protocols']) || ($a['mr_protocols'] == "")) echo "selected"; ?>>(None)
									<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) echo "selected"; ?>>(ALL protocols)
									<?
									/* get unique list of MR protocols from this project */
									$sqlstring = "select a.series_desc from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid and a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$seriesdesc = trim($row['series_desc']);
										
										if (in_array($seriesdesc, $a['mr_protocols']))
											$selected = "selected";
										else
											$selected = "";
										
										$seriesdesc = str_replace("<", "&lt;", $seriesdesc);
										$seriesdesc = str_replace(">", "&gt;", $seriesdesc);
										?><option value="<?=$seriesdesc?>" <?=$selected?>><?=$seriesdesc?><?
									}
									?>
								</select>
								<br><br>
								<b>EEG</b><br>
								<select name="eeg_protocols[]" multiple style="width: 450px" size="6">
									<option value="NONE" <? if (in_array("NONE", $a['eeg_protocols']) || ($a['eeg_protocols'] == "")) echo "selected"; ?>>(None)
									<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['eeg_protocols'])) echo "selected"; ?>>(ALL protocols)
									<?
									/* get unique list of EEG protocols from this project */
									$sqlstring = "select a.series_desc from eeg_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid and a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$seriesdesc = $row['series_desc'];
										
										if (in_array($seriesdesc, $a['eeg_protocols']))
											$selected = "selected";
										else
											$selected = "";
										
										$seriesdesc = str_replace("<", "&lt;", $seriesdesc);
										$seriesdesc = str_replace(">", "&gt;", $seriesdesc);
										?><option value="<?=$seriesdesc?>" <?=$selected?>><?=$seriesdesc?><?
									}
									?>
								</select>
								<b>ET</b><br>
								<select name="et_protocols[]" multiple style="width: 450px" size="6">
									<option value="NONE" <? if (in_array("NONE", $a['et_protocols']) || ($a['et_protocols'] == "")) echo "selected"; ?>>(None)
									<option value="ALLPROTOCOLS" <? if (in_array("ALLPROTOCOLS", $a['et_protocols'])) echo "selected"; ?>>(ALL protocols)
									<?
									/* get unique list of ET protocols from this project */
									$sqlstring = "select a.series_desc from et_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = $projectid and a.series_desc <> '' and a.series_desc is not null group by series_desc order by series_desc";
									$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
									while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
										$seriesdesc = $row['series_desc'];
										
										if (in_array($seriesdesc, $a['et_protocols']))
											$selected = "selected";
										else
											$selected = "";
										
										$seriesdesc = str_replace("<", "&lt;", $seriesdesc);
										$seriesdesc = str_replace(">", "&gt;", $seriesdesc);
										?><option value="<?=$seriesdesc?>" <?=$selected?>><?=$seriesdesc?><?
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style="background-color: #526FAA; font-weight: bold; color: #fff; padding: 5px" align="center">
								Measures (key/value pairs)
							</td>
						</tr>
						<tr>
							<td style="padding-left: 15px">
								<input type="checkbox" name="includeallmeasures" value="1" <? if ($a['includeallmeasures']) echo "checked"; ?>>Include all measures<br>
							</td>
						</tr>
						<tr>
							<td style="background-color: #526FAA; font-weight: bold; color: #fff; padding: 5px" align="center">
								Vitals
							</td>
						</tr>
						<tr>
							<td style="padding-left: 15px">
								<input type="checkbox" name="includeallvitals" value="1" <? if ($a['includeallvitals']) echo "checked"; ?>>Include all vitals<br>
							</td>
						</tr>
						<tr>
							<td style="background-color: #526FAA; font-weight: bold; color: #fff; padding: 5px" align="center">
								Drugs/dosing
							</td>
						</tr>
						<tr>
							<td style="padding-left: 15px">
								<input type="checkbox" name="includealldrugs" value="1" <? if ($a['includealldrugs']) echo "checked"; ?>>Include all drugs/dosing<br>
							</td>
						</tr>
						<tr>
							<td style="background-color: #526FAA; font-weight: bold; color: #fff; padding: 5px" align="center">
								Options
							</td>
						</tr>
						<tr>
							<td style="padding-left: 15px">
								<input type="checkbox" name="includeemptysubjects" value="1" <? if ($a['includeemptysubjects']) echo "checked"; ?>>Include subjects without data<br>
								<br>
								Group by<br>
								<input type="radio" name="grouprowsby" value="subject" <? if (($a['grouprowsby'] == "subject") || ($a['grouprowsby'] == "")) echo "checked"; ?>>Subject<br>
								<input type="radio" name="grouprowsby" value="study" <? if ($a['grouprowsby'] == "study") echo "checked"; ?>>Study<br>
							</td>
						</tr>
					</table>
					<div align="center">
						<input type="submit" value="Update Summary">
					</div>
					</form>
				</td>
				<td valign="top">
					<div style="overflow: auto; height: 100%; width: 100%">
					<?=DisplayAnalysisTable($projectid, $a)?>
					</div>
				</td>
			</tr>
		</table>
		<?
	}
	
	/* -------------------------------------------- */
	/* ------- DisplayAnalysisTable --------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisTable($projectid, $a) {
		
		/* create the table */
		$t;
		if ($a['grouprowsby'] == "study")
			$sqlstring = "select a.*, b.*, c.study_num, c.study_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where b.project_id = $projectid order by a.uid, c.study_num";
		else
			$sqlstring = "select a.*, b.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $projectid order by a.uid";

		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = $row['enrollment_id'];
			$uid = $row['uid'];
			$studynum = $row['study_num'];
			$subjectid = $row['subject_id'];
			
			if ($a['grouprowsby'] == "study") {
				$studyid = trim($row['study_id']);
				$id = "$uid$studynum";
				$t[$id]['IDs']['UIDStudyNum'] = "$uid$studynum";
			}
			else {
				$id = $uid;
				$t[$id]['IDs']['UID'] = $uid;
			}
			
			$t[$id]['Demographics']['DOB'] = $row['birthdate'];
			$t[$id]['Demographics']['Sex'] = $row['gender'];
			$subjectheight = $row['height'];
			$subjectweight = $row['weight'];
			$t[$id]['Demographics']['Group'] = $row['enroll_subgroup'];
			
			$altuids = GetAlternateUIDs($subjectid, $enrollmentid);
			$t[$id]['IDs']['AltUIDs'] = implode2(",", $altuids);
			
			/* add measures (key/value) if necessary */
			if ($a['includeallmeasures']) {
				$sqlstringA = "select a.*, b.measure_name from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = $enrollmentid";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$measurename = $rowA['measure_name'];
					if ($rowA['measure_type'] == "n")
						$value = $rowA['measure_valuenum'];
					else
						$value = $rowA['measure_valuestring'];
					
					$t[$id]['Measures'][$measurename] = $value;
				}
			}
			
			/* add vitals if necessary */
			if ($a['includeallmeasures']) {
				$sqlstringA = "select a.*, b.vital_name from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where a.enrollment_id = $enrollmentid";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$vitalname = $rowA['vital_name'];
					$value = $rowA['vital_value'];
					
					$t[$id]['Vitals'][$vitalname] = $value;
				}
			}
			
			if (($a['grouprowsby'] == "study") && ($studyid == "")) {
				continue;
			}
			
			/* include MR protocols */
			if (!empty($a['mr_protocols'])) {
				
				if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) {
					if ($a['grouprowsby'] == "study") {
						$sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.study_id = $studyid";
					}
					else {
						$sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid";
					}
				}
				else {
					$mrprotocollist = MakeSQLListFromArray($a['mr_protocols']);
					if ($a['grouprowsby'] == "study") {
						if ($studyid == "") {
							continue;
						}
						
						$sqlstringA = "select a.*, b.*, count(a.series_desc) 'seriescount' from mr_series a left join studies b on a.study_id = b.study_id where a.study_id = $studyid and a.series_desc in ($mrprotocollist) group by a.series_desc";
					}
					else {
						$sqlstringA = "select a.*, b.*, count(a.series_desc) 'seriescount' from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid and a.series_desc in ($mrprotocollist) group by a.series_desc";
					}
				}
			
				/* add in the protocols */
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$seriesdesc = $rowA['series_desc'];
					$seriesid = $rowA['mrseries_id'];
					
					$pixdimX = $rowA['series_spacingx'];
					$pixdimY = $rowA['series_spacingy'];
					$pixdimZ = $rowA['series_spacingz'];
					$dimX = $rowA['dimX'];
					$dimY = $rowA['dimY'];
					$dimZ = $rowA['dimZ'];
					$dimT = $rowA['dimT'];
					$tr = $rowA['series_tr'];
					$te = $rowA['series_te'];
					$ti = $rowA['series_ti'];
					$flip = $rowA['series_flip'];
					$seriesnum = $rowA['series_num'];
					$studynum = $rowA['study_num'];
					$numseries = $rowA['seriescount'];
					$studyheight = $rowA['study_height'];
					$studyweight = $rowA['study_weight'];
					$studydatetime = $rowA['study_datetime'];
					$studyage = $rowA['study_ageatscan'];
					$studynotes = $rowA['study_notes'];
					
					//if (($studyage == "") || ($studyage == "null") || ($studyage == 0))
					//	$age = strtotime($studydate) - strtotime($t[$id]['Demographics']['DOB']);
					//else
					//	$age = $studyage;
					
					list($studyAge, $calcStudyAge) = GetStudyAge($t[$id]['Demographics']['DOB'], $studyage, $studydate);
					
					if ($studyAge == null)
						$studyAge = "-";
					else
						$studyAge = number_format($studyAge,1);

					if ($calcStudyAge == null)
						$calcStudyAge = "-";
					else
						$calcStudyAge = number_format($calcStudyAge,1);
					
					
					if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0))
						$height = $subjectheight;
					else
						$height = $studyheight;
					
					if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0))
						$weight = $subjectweight;
					else
						$weight = $studyweight;
					
					$t[$id][$seriesdesc]['SeriesNum'] = $seriesnum;
					$t[$id][$seriesdesc]['StudyDateTime'] = $studydatetime;
					$t[$id][$seriesdesc]['StudyNum'] = $studynum;
					$t[$id][$seriesdesc]['NumSeries'] = $numseries;
					$t[$id][$seriesdesc]['AgeAtScan'] = $studyAge;
					$t[$id][$seriesdesc]['CalcAgeAtScan'] = $calcStudyAge;
					$t[$id][$seriesdesc]['Height'] = $height;
					$t[$id][$seriesdesc]['Weight'] = $weight;
					$t[$id][$seriesdesc]['Notes'] = $studynotes;
					
					if ($a['includeprotocolparms']) {
						$t[$id][$seriesdesc]['voxX'] = $pixdimX;
						$t[$id][$seriesdesc]['voxY'] = $pixdimY;
						$t[$id][$seriesdesc]['voxZ'] = $pixdimZ;
						$t[$id][$seriesdesc]['dimX'] = $dimX;
						$t[$id][$seriesdesc]['dimY'] = $dimY;
						$t[$id][$seriesdesc]['dimZ'] = $dimZ;
						$t[$id][$seriesdesc]['dimT'] = $dimT;
						$t[$id][$seriesdesc]['TR'] = $tr;
						$t[$id][$seriesdesc]['TE'] = $te;
						$t[$id][$seriesdesc]['TI'] = $ti;
						$t[$id][$seriesdesc]['flip'] = $flip;
					}
					
					if ($a['includemrqa']) {
						$sqlstringC = "select * from mr_qa where mrseries_id = $seriesid";
						$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
						$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
						
						$t[$id][$seriesdesc]['io_snr'] = $rowC['io_snr'];
						$t[$id][$seriesdesc]['pv_snr'] = $rowC['pv_snr'];
						$t[$id][$seriesdesc]['move_minx'] = $rowC['move_minx'];
						$t[$id][$seriesdesc]['move_miny'] = $rowC['move_miny'];
						$t[$id][$seriesdesc]['move_minz'] = $rowC['move_minz'];
						$t[$id][$seriesdesc]['move_maxx'] = $rowC['move_maxx'];
						$t[$id][$seriesdesc]['move_maxy'] = $rowC['move_maxy'];
						$t[$id][$seriesdesc]['move_maxz'] = $rowC['move_maxz'];
						$t[$id][$seriesdesc]['acc_minx'] = $rowC['acc_minx'];
						$t[$id][$seriesdesc]['acc_miny'] = $rowC['acc_miny'];
						$t[$id][$seriesdesc]['acc_minz'] = $rowC['acc_minz'];
						$t[$id][$seriesdesc]['acc_maxx'] = $rowC['acc_maxx'];
						$t[$id][$seriesdesc]['acc_maxy'] = $rowC['acc_maxy'];
						$t[$id][$seriesdesc]['acc_maxz'] = $rowC['acc_maxz'];
						$t[$id][$seriesdesc]['rot_minp'] = $rowC['rot_minp'];
						$t[$id][$seriesdesc]['rot_minr'] = $rowC['rot_minr'];
						$t[$id][$seriesdesc]['rot_miny'] = $rowC['rot_miny'];
						$t[$id][$seriesdesc]['rot_maxp'] = $rowC['rot_maxp'];
						$t[$id][$seriesdesc]['rot_maxr'] = $rowC['rot_maxr'];
						$t[$id][$seriesdesc]['rot_maxy'] = $rowC['rot_maxy'];
					}
				}
			}
		}

		/* create table header */
		foreach ($t as $id => $subject) {
			$h['IDs']['UID'] = "";
			$h['IDs']['AltUIDs'] = "";
			
			foreach ($subject as $header => $section) {
				foreach ($section as $col => $vals) {
					$h[$header][$col] = "";
				}
			}
		}
		?>
		<table class="summarytable">
			<thead>
				<tr>
				<?
				foreach ($h as $header => $section) {
					$ncols = count($section);
					?>
					<th colspan="<?=$ncols?>"><?=$header?></th>
					<?
				}
				?>
				</tr>
				<tr>
				<?
				foreach ($h as $header => $section) {
					foreach ($section as $col => $vals) {
						?><th><?=$col?></th><?
					}
				}
				?>
				</tr>
			</thead>
			<tbody>
				<?
				foreach ($t as $id => $subject) {
					?>
					<tr>
					<?
					foreach ($h as $header => $section) {
						foreach ($section as $col => $vals) {
							if (is_numeric($t[$id][$header][$col]) && (strpos($t[$id][$header][$col], '.') !== false))
								$disp = number_format($t[$id][$header][$col], 3);
							else
								$disp = $t[$id][$header][$col];
							?><td><?=$disp?></td><?
						}
					}
					?>
					</tr>
				<? } ?>
			</tbody>
		</table>
		<?
	}
	
?>


<? include("footer.php") ?>
