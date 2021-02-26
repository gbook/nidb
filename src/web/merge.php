<?
 // ------------------------------------------------------------------------------
 // NiDB merge.php
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
		<title>NiDB - Merge</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($_GET);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$subjectids = GetVariable("subjectids");
	$subjectuid = GetVariable("subjectuid");
	$selectedid = GetVariable("selectedid");
	$selectedstudyid = GetVariable("selectedstudyid");
	$studyids = GetVariable("studyids");
	$studyid = GetVariable("studyid");
	$mergemethod = GetVariable("mergemethod");
	$idtoremove = GetVariable("idtoremove");
	$name = GetVariable("name");
	$dob = GetVariable("dob");
	$gender = GetVariable("gender");
	$ethnicity1 = GetVariable("ethnicity1");
	$ethnicity2 = GetVariable("ethnicity2");
	$enrollgroup = GetVariable("enrollgroup");
	$altuids = GetVariable("altuids");
	$guid = GetVariable("guid");
	$mergeuids = GetVariable("uids");
	$returnpage = GetVariable("returnpage");

	/* determine action */
	switch ($action) {
		case 'merge':
			DisplayMergeSubjects($subjectids, $idtoremove, $subjectuid);
			break;
		case 'submitmerge':
			SubmitMerge($subjectids, $selectedid, $name, $dob, $gender, $ethnicity1, $ethnicity2, $altuids, $guid, $enrollgroup);
			break;
		case 'submitmergestudies':
			SubmitMergeStudies($studyids, $selectedstudyid, $mergemethod);
			break;
		case 'mergestudyform':
			DisplayMergeStudies($studyids, $studyid);
			break;
		default:
			DisplayMergeSubjects($subjectids, $idtoremove, $subjectuid);
	}
	

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- SubmitMerge ------------------------ */
	/* -------------------------------------------- */
	function SubmitMerge($subjectids, $selectedid, $name, $dob, $gender, $ethnicity1, $ethnicity2, $altuids, $guid, $enrollgroup) {

		//PrintVariable($subjectids);
		
		/* remove the 'selectedid' */
		foreach ($subjectids as $key => $id) {
			//echo "[$key] = $id<br>";
			if ($id == $selectedid) {
				//echo "About to unset subjectids[$key]<br>";
				unset($subjectids[$key]);
			}
		}
		$subjectids = array_values($subjectids);

		/* perform data checks */
		$name = mysqli_real_escape_string($GLOBALS['linki'], $name[$selectedid]);
		$dob = mysqli_real_escape_string($GLOBALS['linki'], $dob[$selectedid]);
		$gender = mysqli_real_escape_string($GLOBALS['linki'], $gender[$selectedid]);
		$ethnicity1 = mysqli_real_escape_string($GLOBALS['linki'], $ethnicity1[$selectedid]);
		$ethnicity2 = mysqli_real_escape_string($GLOBALS['linki'], $ethnicity2[$selectedid]);
		$altuid = mysqli_real_escape_string($GLOBALS['linki'], $altuid[$selectedid]);
		$enrollgroup = mysqli_real_escape_string($GLOBALS['linki'], $enrollgroup[$selectedid]);
		$guid = mysqli_real_escape_string($GLOBALS['linki'], $guid[$selectedid]);

		//PrintVariable($subjectids);
		$subjectids = mysqli_real_escape_array($subjectids);
		//PrintVariable($subjectids);
		
		$mergeids = implode2(",", $subjectids);
		//PrintVariable($mergeids);
		
		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, merge_ids, merge_name, merge_dob, merge_sex, merge_ethnicity1, merge_ethnicity2, merge_guid, merge_enrollgroup, merge_altuids, request_status, request_message, username, requestdate) values ('merge', 'subject', $selectedid, '$mergeids', '$name', '$dob', '$gender', '$ethnicity1', '$ethnicity2', '$guid', '$enrollgroup', '$altuid', 'pending', 'Request submitted', '" . $GLOBALS['username'] . "', now())";
		//PrintVariable($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		$sqlstring = "select uid from subjects where subject_id = $selectedid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$finaluid = $row['uid'];

		$sqlstring = "select uid from subjects where subject_id in (" . MakeSQLListFromArray($subjectids) . ")";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uids[] = $row['uid'];
		}
		
		?>
		<table cellpadding="5">
			<tr>
				<td>Merging UID(s) &nbsp;</td>
				<td style="border: 1px solid #aaa; border-radius: 5px"><?=implode2("<br>", $uids)?></td>
				<td>&nbsp; into &rarr; </td>
				<td><span style="border: 1px solid #aaa; padding: 5px; border-radius: 5px"><?=$finaluid?></span></td>
			</tr>
		</table>
		<br>
		<br>
		<b>Merge queued</b>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SubmitMergeStuidies ---------------- */
	/* -------------------------------------------- */
	function SubmitMergeStudies($studyids, $selectedstudyid, $mergemethod) {
		
		/* remove the 'selectedid' */
		foreach ($studyids as $key => $id) {
			if ($id == $selectedstudyid) {
				unset($studyids[$key]);
			}
		}
		$studyids = array_values($studyids);
		$studyidlist = implode2(',', $studyids);
		$studyidlist = mysqli_real_escape_string($GLOBALS['linki'], $studyidlist);

		$mergemethod = mysqli_real_escape_string($GLOBALS['linki'], $mergemethod);

		/* merge all other studyids into the selectedstudyid */
		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, merge_ids, merge_method, request_status, request_message, username, requestdate) values ('merge', 'study', $selectedstudyid, '$studyidlist', '$mergemethod', 'pending', 'Request submitted', '" . $GLOBALS['username'] . "', now())";
		PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		DisplayNotice("Merge queued");
	}


	/* -------------------------------------------- */
	/* ------- DisplayMergeStudies ---------------- */
	/* -------------------------------------------- */
	function DisplayMergeStudies($studyids, $studyid) {

		$studyid = mysqli_real_escape_string($GLOBALS['linki'], $studyid);
		$studyids = mysqli_real_escape_array($studyids);

		$sqlstring = "select c.uid, a.study_modality from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $studyid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$modality = $row['study_modality'];

		?>
		Select studies that you <i>want to merge</i> (only <?=$modality?> modality for this enrollment are displayed). Then <i>select the final study</i> they will be merged into
		<br><br>
		<form action="merge.php" method="post">
		<input type="hidden" name="action" value="submitmergestudies">
		<input type="hidden" name="returnpage" value="<?=$returnpage?>">
		<table class="graydisplaytable">
			<thead>
				<th></th>
				<th>Study</th>
				<th>Date</th>
				<th>Number of series</th>
				<th>Final Study</th>
			</thead>
		<?
		$sqlstring = "select * from studies where enrollment_id in (select enrollment_id from studies where study_id = $studyid) and study_modality in (select study_modality from studies where study_id = $studyid) order by study_datetime asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$checked = "checked";
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$sid = $row['study_id'];
			$studynum = $row['study_num'];
			$studydate = $row['study_datetime'];
			
			//if (in_array($studyid, $studyids)) { $checked = "checked"; }
			//if ($sid == $studyid) { $checked = "checked"; }
			
			/* get the number of series for this study */
			$sqlstringA = "select count(*) 'numseries' from " . strtolower($modality) . "_series where study_id = $studyid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$numseries = $rowA['numseries'];
			?>
			<tr>
				<td>
					<input type="checkbox" name="studyids[]" value="<?=$sid?>" checked>
				</td>
				<td>
					<?=$uid?><?=$studynum?>
				</td>
				<td>
					<?=$studydate?>
				</td>
				<td>
					<?=$numseries?>
				</td>
				<td>
					<input type="radio" name="selectedstudyid" value="<?=$studyid?>" <?=$checked?>>
				</td>
			</tr>
			<?
			$checked = "";
		}
		?>
		</table>
		<br>
		<b>Merge method</b><br>
		<input type="radio" name="mergemethod" value="sortbyseriesdate" checked>Renumber series, ordering by series datetime (ascending)<br>
		<input type="radio" name="mergemethod" value="concatbystudydateasc">Concatentate by study date (ascending <tt>2020-06-14 2020-06-22 ... 2020-08-01 2020-08-05</tt>)<br>
		<input type="radio" name="mergemethod" value="concatbystudydatedesc">Concatentate by study date (descending <tt>2020-08-05 2020-08-01 ... 2020-06-22 2020-06-14</tt>)<br>
		<input type="radio" name="mergemethod" value="concatbystudynumasc">Concatentate by study number (ascending <tt>1 2 ... 7 8</tt>)<br>
		<input type="radio" name="mergemethod" value="concatbystudynumdesc">Concatentate by study number (descending <tt>6 5 ... 2 1</tt>)<br>
		<br>
		<input type="submit" value="Merge">
		</form>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayMergeSubjects --------------- */
	/* -------------------------------------------- */
	function DisplayMergeSubjects($subjectids, $idtoremove, $subjectuid) {

		$subjectuid = mysqli_real_escape_string($GLOBALS['linki'], $subjectuid);
		
		$sqlstring = "select subject_id from subjects where uid = '$subjectuid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectids[] = $row['subject_id'];
		}
		$subjectids = mysqli_real_escape_array($subjectids);

		if (((count($subjectids) == 0) || ($subjectids == "")) && ($subjectuid == "")) {
			?>
			No subjects selected for merge. Add a UID below.
			<br><br>
			<form action="merge.php" method="post">
			<input type="hidden" name="action" value="merge">
			<input type="text" name="subjectuid" placeholder="UID"><br>
			<input type="submit" class="linkbutton" value="Add UID">
			</form>
			<?
			return;
		}

		/* remove the 'idtoremove' */
		if ($idtoremove != "") {
			if (($key = array_search($idtoremove, $subjectids)) !== false) {
				unset($subjectids[$key]);
			}
		}
		
		if (is_array($subjectids)) {
			$sqlstring = "select * from subjects where subject_id in (" . MakeSQLListFromArray($subjectids) . ")";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$numsubjects = mysqli_num_rows($result);
			$i=0;
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				/* gather info for this uid and put into an array */
				$subjects[$i]['id'] = $row['subject_id'];
				$subjects[$i]['name'] = $row['name'];
				$subjects[$i]['dob'] = $row['birthdate'];
				$subjects[$i]['gender'] = $row['gender'];
				$subjects[$i]['ethnicity1'] = $row['ethnicity1'];
				$subjects[$i]['ethnicity2'] = $row['ethnicity2'];
				$subjects[$i]['uid'] = $row['uid'];
				$subjects[$i]['guid'] = $row['guid'];
				
				/* get list of alternate subject UIDs */
				$altuids = GetAlternateUIDs($row['subject_id'],0);
				$subjects[$i]['altuid'] = implode2(', ',$altuids);
				
				$i++;
			}
		}

		/* display one column for each subject with a radio button to "merge all studies into this subject" */
		?>
		<style>
			.radio-toolbar2 input[type="radio"]:checked ~ * {
				background:yellow !important;
				padding: 5px;
			}
		</style>
		
		<table>
			<tr>
				<td colspan="2" style="padding: 15px;">
					<b>Merge subjects</b>
					<ul>
					<li>Select the UID you want to merge into and edit information in that column. <b>Leftmost UID is selected by default</b>
					<li>Only the information in the selected column will be saved, and all other projects will be merged into that UID. All other UIDs will be deleted.
					</ul>
				</td>
			</tr>
			<tr>
				<td>
				<form action="merge.php" method="post">
				<input type="hidden" name="action" value="submitmerge">
				<input type="hidden" name="returnpage" value="<?=$returnpage?>">
				<?
				for ($i=0;$i<count($subjects);$i++) {
					echo "<input type='hidden' name='subjectids[" . $i . "]' value='" . $subjects[$i]['id'] . "'>\n";
				}
				?>
				<table cellspacing="0" cellpadding="1" class="merge">
					<tr class="radio-toolbar2">
						<td class="label">UID</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								?>
									<td align="center" class="uid"><label><input type="radio" id="uid<?=$i?>" name="selectedid" value="<?=$subjects[$i]['id']?>" <? if ($i == 0) { echo "checked"; } ?> ><span><?=$subjects[$i]['uid']?></span></label> &nbsp; <input type="button" name="removeid" value="Remove UID" style="width: 100px; margin:4px" onclick="document.removeidform.idtoremove.value='<?=$subjects[$i]['id']?>';document.removeidform.submit();">
									</td>
								<?
							}
						?>
					</tr>
					<tr>
						<td class="label">Name</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['name'] != $subjects[0]['name']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?>
									<td class="<?=$class?>"><input type="text" name="name[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['name']?>"></td>
								<?
							}
						?>
					</tr>
					<tr>
						<td class="label">DOB</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['dob'] != $subjects[0]['dob']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?>
									<td class="<?=$class?>"><input type="text" name="dob[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['dob'];?>"></td>
								<?
							}
						?>
					</tr>
					<tr>
						<td class="label">Sex</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['gender'] != $subjects[0]['gender']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?>
									<td class="<?=$class?>"><input type="text" name="gender[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['gender'];?>"></td>
								<?
							}
						?>
					</tr>
					<tr>
						<td class="label">Ethnicity 1</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['ethnicity1'] != $subjects[0]['ethnicity1']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?><td class="<?=$class?>"><input type="text" name="ethnicity1[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['ethnicity1'];?>"></td><?
							}
						?>
					</tr>
					<tr>
						<td class="label">Ethnicity 2</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['ethnicity2'] != $subjects[0]['ethnicity2']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?><td class="<?=$class?>"><input type="text" name="ethnicity2[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['ethnicity2'];?>"></td><?
							}
						?>
					</tr>
					<tr>
						<td class="label">GUID</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['guid'] != $subjects[0]['guid']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?><td class="<?=$class?>"><input type="text" name="guid[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['guid'];?>"></td><?
							}
						?>
					</tr>
					<tr>
						<td class="label">Alternate subject IDs</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
								if ($subjects[$i]['altuid'] != $subjects[0]['altuid']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
								?><td class="<?=$class?>"><input type="text" name="altuids[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['altuid'];?>"></td><?
							}
						?>
					</tr>
					<tr>
						<td class="label">Studies (with enrollment group)</td>
						<?
							for ($i=0;$i<count($subjects);$i++) {
							?>
								<td valign="top" style="border-right: 1px solid gray">
									<table cellspacing="0" cellpadding="0">
										<?
											$sqlstring = "select a.*, b.*, date(enroll_startdate) 'enroll_startdate', date(enroll_enddate) 'enroll_enddate' from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = " . $subjects[$i]['id'];
											$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
											while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
												$enrollmentid = $row['enrollment_id'];
												$enrollgroup = $row['enroll_subgroup'];
												$project_name = $row['project_name'];
												$costcenter = $row['project_costcenter'];
												
												$altuids = GetAlternateUIDs($subjects[$i]['id'], $enrollmentid);
												$altuidlist = implode2(', ',$altuids);
												
										?>
										<tr>
											<td colspan="4" style="font-size:9pt; background-color:#eee; padding: 4px">
												<table cellpadding="0" cellspacing="0" width="100%">
													<tr>
														<td><b><?=$project_name?></b> (<?=$costcenter?>)
														<br>
														<input type="text" name="enrollgroup[<?=$enrollmentid?>]" value="<?=$enrollgroup?>" placeholder="Enrollment group">
														</td>
													</tr>
												</table>
											</td>
										</tr>
											<?
											$sqlstring = "select * from studies where enrollment_id = $enrollmentid";
											$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
											if (mysqli_num_rows($result2) > 0) {
												while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
													$study_id = $row2['study_id'];
													$study_num = $row2['study_num'];
													$study_modality = $row2['study_modality'];
													$study_datetime = $row2['study_datetime'];
													$study_operator = $row2['study_operator'];
													$study_performingphysician = $row2['study_performingphysician'];
													$study_site = $row2['study_site'];
													$study_status = $row2['study_status'];
													
													?>
													<tr style="font-size: 8pt">
														<td style="border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; padding: 1px 5px"><?=$study_num?></td>
														<td style="border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; padding: 1px 5px"><?=$study_modality?></td>
														<td style="border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; padding: 1px 5px"><?=$study_datetime?></td>
														<td style="border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; padding: 1px 5px"><?=$study_site?></td>
													</tr>
													<?
												}
											}
											else {
												?>
												<tr>
													<td align="center">
														None
													</td>
												</tr>
												<?
											}
										}
										?>
									</table>
								</td>
							<?
							}
						?>
					</tr>
					<tr>
						<td colspan="<?=count($subjects)+1?>" align="center" style="border-top: 2px solid gray; border-bottom: 2px solid gray">
							<br>
							<input type="submit" value="Merge">
							<br><br>
						</td>
					</tr>
				</table>
				</form>
			</td>
			<td align="center" valign="top" style="padding: 10px">
				<?
					if ($numsubjects < 4) {
				?>
				<form action="merge.php" method="post">
				<input type="hidden" name="action" value="merge">
				<?
				for ($i=0;$i<count($subjects);$i++) {
					echo "<input type='hidden' name='subjectids[" . $i . "]' value='" . $subjects[$i]['id'] . "'>\n";
				}
				?>
				<input type="text" name="subjectuid" placeholder="UID"><br>
				<input type="submit" class="linkbutton" value="Add UID">
				</form>
				<? } else { ?>
				Only 4 IDs allowed at a time
				<? } ?>
			</td>
		</tr>
		</table>
		
		<form action="merge.php" method="post" name="removeidform">
		<input type="hidden" name="action" value="merge">
		<?
		for ($j=0;$j<count($subjects);$j++) {
			echo "<input type='hidden' name='subjectids[" . $j . "]' value='" . $subjects[$j]['id'] . "'>\n";
		}
		?>
		<input type="hidden" name="idtoremove" value="">
		</form>
		
	<?
	}
?>

<? include("footer.php") ?>