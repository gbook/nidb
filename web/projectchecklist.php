<?
 // ------------------------------------------------------------------------------
 // NiDB projectchecklist.php
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
		<title>NiDB - Project Checklist</title>
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
		//$sqlstring = "delete from project_checklist where project_id = $projectid";
		//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		$i=1;

		foreach ($itemorder as $order) {

			if ((trim($itemorder[$i]) != "") && (trim($itemname[$i]) != "")) {
				if (trim($itemid[$i] == "")) {
					$sqlstring = "insert into project_checklist (project_id, item_name, item_order, modality, protocol_name, count, frequency, frequency_unit) values ($projectid, '$itemname[$i]', '$itemorder[$i]', '$modality[$i]', '$protocol[$i]', '$itemcount[$i]', '$frequency[$i]', '$frequencyunit[$i]')";
				//echo "<br> in insert: item_order = '$itemorder[$i]',  projectchecklist_id = '$itemid[$i]' itemname: $itemname[$i]</br> ";
				}
				else {
					$sqlstring = "update project_checklist set item_name = '$itemname[$i]', item_order = '$itemorder[$i]', modality = '$modality[$i]', protocol_name = '$protocol[$i]', count = '$itemcount[$i]', frequency = '$frequency[$i]', frequency_unit = '$frequencyunit[$i]' where projectchecklist_id = $itemid[$i]";
				 //echo "<br> in update: item_order = '$itemorder[$i]',  projectchecklist_id = '$itemid[$i]'  itemname: $itemname[$i] </br>";
				}
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}
			else if ((trim($itemorder[$i]) != "") && (trim($itemname[$i]) == "")) {

				//echo "<br> in delete: item_order = '$itemorder[$i]',  projectchecklist_id = '$itemid[$i]'  itemname: $itemname[$i]</br>";
				

				//delete
				$sqlstring = "delete from project_checklist where item_order = '$itemorder[$i]'  AND projectchecklist_id = '$itemid[$i]'";

				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			}

			$i++;
		}
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		?><div align="center"><span class="message">Checklist updated</span></div><br><br><?
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
			?><div class="staticmessage">Data ID blank</div><?
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
			?><div class="staticmessage">Project ID blank</div><?
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
			?><div class="staticmessage">Project ID blank</div><?
			return;
		}
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
	
		$urllist['Projects'] = "projects.php";
		$urllist[$projectname] = "projects.php?id=$projectid";
		NavigationBar("Edit $projectname Checklist", $urllist);
		
		$neworder = 1;

		DisplayMenu('checklist', $projectid);

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
			?><div class="staticmessage">Project ID blank</div><?
			return;
		}
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
	
		$urllist['Projects'] = "projects.php";
		$urllist[$projectname] = "projects.php?id=$projectid";
		NavigationBar("$projectname Checklist", $urllist);
		
		
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
		
		//PrintVariable($checklist);
		
		/* get the project enrollment data */
		$sqlstring = "select a.*, b.subject_id, b.uid, b.guid, b.isactive, c.study_id from enrollment a left join subjects b on a.subject_id = b.subject_id left join studies c on a.enrollment_id = c.enrollment_id where a.project_id = $projectid and (a.enroll_enddate > now() or a.enroll_enddate = '0000-00-00') and b.isactive = 1 order by b.uid asc";
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
		
		DisplayMenu('checklist', $projectid);
		
		?>
		<br>
		<div align="center">
		<form action="subjects.php" method="post">
		<input type="hidden" name="action" value="merge">
		<input type="hidden" name="returnpage" value="projectchecklist.php?projectid=<?=$projectid?>">
		<?=$numenrollments?> enrollments<br><br>
		<span class="tiny">Table is sortable. Click column headers to sort</span>
		<table class="sortable graydisplaytable dropshadow" style="border-collapse: collapse">
			<thead>
			<tr>
				<th>Merge</th>
				<th data-sort="string-ins">UID</th>
				<th data-sort="string-ins">GUID</th>
				<th data-sort="string-ins">Alt ID</th>
				<th data-sort="string-ins">Enroll date</th>
				<th data-sort="string-ins">Group</th>
		<?
		$totals = array(0,0,0,0,0);
		$ii = 5;
		foreach ($checklist as $i => $item) {
			$name = $item['name'];
			$desc = $item['desc'];
			$modality = $item['modality'];
			$protocol = $item['protocol'];
			?>
			<th data-sort="string-ins" title="<b>Modality</b> <?=$modality?><br><b>Protocol</b> <?=$protocol?><br><b>Description</b> <?=$desc?>"><?=$name?><br><span class="tiny"><?=$modality?></span></th>
			<?
			$totals[$ii] = 0;
			$ii++;
		}
		?>
				<th data-sort="string-ins">Complete data?</th>
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
			$studyids = '';
			$sqlstring = "select study_id from studies where enrollment_id = $enrollmentid";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyids[] = "'" . $row['study_id'] . "'";
			}
			
			/* get project specific altuid */
			$sqlstring = "select altuid from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid and isprimary = 1";
			//PrintSQL($sqlstring);
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
			
			?>
			<tr>
				<td><input type="checkbox" name="uids[]" value="<?=$uid?>"></td>
				<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a> <?=$deleted?></td>
				<td><?=$guid?></td>
				<td><?=$altuid?></td>
				<td><a href="enrollment.php?id=<?=$enrollmentid?>"><?=$enrolldate?></a></td>
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
						if (($studyids == '') || (count($studyids) == 0 )) {
							$msg = "<span style='color: #ccc; font-size:14pt'>&nbsp;</span>";
						}
						else {
							$sqlstring = "select study_id from $modality" . "_series where study_id in (" . implode(',',$studyids) . ") and (series_desc in (" . implode(',',$protocols) . ") or series_protocol in (" . implode(',',$protocols) . "))";
							//PrintSQL($sqlstring);
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							if (mysqli_num_rows($result) > 0) {
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$studyid = $row['study_id'];
								$msg = "1";
								$msg = "<a href='studies.php?id=$studyid'>&#10004;</a>";
								$totals[$ii]++;
								$rowtotal++;
							}
							else {
								$msg = "";
							}
						}
					}
					else {
						$sqlstring = "select * from enrollment_checklist where enrollment_id = $enrollmentid and projectchecklist_id = $itemid";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							//$msg = "1";
							$msg = "&#10004;";
							$totals[$ii]++;
							$rowtotal++;
						}
						else {
							$msg = "";
						}
					}
					if ($msg == "") {
						$sqlstring = "select * from enrollment_missingdata where enrollment_id = '$enrollmentid' and projectchecklist_id = '$itemid'";
						$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
						if (mysqli_num_rows($result) > 0) {
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$missingdataid = $row['missingdata_id'];
							$reason = $row['missing_reason'];
							$date = $row['missingreason_date'];
							?><td style="border: 1px solid #ffd699; background-image: repeating-linear-gradient(-45deg, transparent, transparent 5px, #ddd 5px, #ddd 10px);" title="<b><?=$reason?></b> - <?=$date?>"><a href="projectchecklist.php?action=setmissingdatareasonform&missingdataid=<?=$missingdataid?>&projectid=<?=$projectid?>&enrollmentid=<?=$enrollmentid?>&projectchecklistid=<?=$itemid?>&reason=<?=$reason?>">&#10006;</a></td><?
						}
						else {
							?><td style="border: 1px solid #ffd699; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #ffe0b3 5px, #ffe0b3 10px);" title="Click to set reason for missing data"><a href="projectchecklist.php?action=setmissingdatareasonform&projectid=<?=$projectid?>&enrollmentid=<?=$enrollmentid?>&projectchecklistid=<?=$itemid?>">?</a></td><?
						}
					}
					else {
						?><td style="border-left: 1px solid #ccc; text-align: center"><?=$msg?></td><?
					}
					$ii++;
				}
			}
			else {
				?><td colspan="<?=count($checklist)?>" align="center" style="border-left: 1px solid #ccc">No studies</td><?
			}
			
			if ($rowtotal == count($checklist)) {
				?><td style="border-left: 1px solid #ccc; text-align: center">&#10004;</td><?
				$totals[$ii]++;
			}
			else {
				?><td style="border-left: 1px solid #ccc; text-align: center; font-size:8pt">Nope. Only <?=$rowtotal?> of <?=count($checklist)?></td><?
			}
			?>
			</tr>
			<?
		}
		//PrintVariable($enrollment);
		echo "<tr><td></td>";
		foreach ($totals as $i => $num) {
			?><td style="border-top: 1px solid black; font-weight: bold"><?=$num?></td><?
		}
		?>
			</tr>
			</tbody>
		</table>
		<input type="submit" value="Merge subjects">
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu($menuitem, $id) {
		switch ($menuitem) {
			case "info":
				?>
				<div align="center">
				<table width="50%">
					<tr>
						<td class="menuheaderactive"><a href="projects.php?action=displayprojectinfo&id=<?=$id?>">Project Info</a></td>
						<td class="menuheader"><a href="projects.php?action=editsubjects&id=<?=$id?>">Subjects</a></td>
						<td class="menuheader"><a href="projects.php?id=<?=$id?>">Studies</a></td>
						<td class="menuheader"><a href="projectchecklist.php?projectid=<?=$id?>">Checklist</a></td>
						<td class="menuheader"><a href="projects.php?action=viewmrparams&id=<?=$id?>">MR Scan QC</a></td>
					</tr>
				</table>
				</div>
				<?
				break;
			case "subjects":
				?>
				<div align="center">
				<table width="50%">
					<tr>
						<td class="menuheader"><a href="projects.php?action=displayprojectinfo&id=<?=$id?>">Project Info</a></td>
						<td class="menuheaderactive">
							<a href="projects.php?action=editsubjects&id=<?=$id?>">Subjects</a><br>
							<a href="projects.php?action=displaydemographics&id=<?=$id?>" style="font-size:10pt; font-weight: normal">View table</a>
						</td>
						<td class="menuheader"><a href="projects.php?id=<?=$id?>">Studies</a></td>
						<td class="menuheader"><a href="projectchecklist.php?projectid=<?=$id?>">Checklist</a></td>
						<td class="menuheader"><a href="projects.php?action=viewmrparams&id=<?=$id?>">MR Scan QC</a></td>
					</tr>
				</table>
				</div>
				<?
				break;
			case "studies":
				?>
				<div align="center">
				<table width="50%">
					<tr>
						<td class="menuheader"><a href="projects.php?action=displayprojectinfo&id=<?=$id?>">Project Info</a></td>
						<td class="menuheader"><a href="projects.php?action=editsubjects&id=<?=$id?>">Subjects</a></td>
						<td class="menuheaderactive"><a href="projects.php?id=<?=$id?>">Studies</a></td>
						<td class="menuheader"><a href="projectchecklist.php?projectid=<?=$id?>">Checklist</a></td>
						<td class="menuheader"><a href="projects.php?action=viewmrparams&id=<?=$id?>">MR Scan QC</a></td>
					</tr>
				</table>
				</div>
				<?
				break;
			case "checklist":
				?>
				<div align="center">
				<table width="50%">
					<tr>
						<td class="menuheader"><a href="projects.php?action=displayprojectinfo&id=<?=$id?>">Project Info</a></td>
						<td class="menuheader"><a href="projects.php?action=editsubjects&id=<?=$id?>">Subjects</a></td>
						<td class="menuheader"><a href="projects.php?id=<?=$id?>">Studies</a></td>
						<td class="menuheaderactive">
							<a href="projectchecklist.php?projectid=<?=$id?>">Checklist</a><br>
							<a href="projectchecklist.php?action=editchecklist&projectid=<?=$id?>" style="font-size: 10pt; font-weight: normal">Edit checklist</a>
						</td>
						<td class="menuheader"><a href="projects.php?action=viewmrparams&id=<?=$id?>">MR Scan QC</a></td>
					</tr>
				</table>
				</div>
				<?
				break;
			case "mrqc":
				?>
				<div align="center">
				<table width="50%">
					<tr>
						<td class="menuheader"><a href="projects.php?action=displayprojectinfo&id=<?=$id?>">Project Info</a></td>
						<td class="menuheader"><a href="projects.php?action=editsubjects&id=<?=$id?>">Subjects</a></td>
						<td class="menuheader"><a href="projects.php?id=<?=$id?>">Studies</a></td>
						<td class="menuheader"><a href="projectchecklist.php?projectid=<?=$id?>">Checklist</a></td>
						<td class="menuheaderactive">
							<a href="projects.php?action=viewmrparams&id=<?=$id?>">MR Scan QC</a><br>
							<a href="projects.php?action=editmrparams&id=<?=$id?>" style="font-size:10pt; font-weight: normal">Edit MR params</a><br>
							<a href="projects.php?action=viewaltseriessummary&id=<?=$id?>" style="font-size:10pt; font-weight: normal">View alt series names</a><br>
							<a href="projects.php?action=viewuniqueseries&id=<?=$id?>" style="font-size:10pt; font-weight: normal">Edit alt series names</a>
							<? if ($GLOBALS['isadmin']) { ?>
								<br><a href="projects.php?action=resetqa&id=<?=$id?>" style="color: #FF552A; font-size:10pt; font-weight:normal">Reset MRI QA</a>
							<? } ?>
						</td>
					</tr>
				</table>
				</div>
				<?
				break;
		}
	}
	
?>


<? include("footer.php") ?>
