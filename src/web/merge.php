<?
 // ------------------------------------------------------------------------------
 // NiDB merge.php
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
		$subjectids = mysqli_real_escape_array($GLOBALS['linki'], $subjectids);
		//PrintVariable($subjectids);
		
		$mergeids = implode2(",", $subjectids);
		//PrintVariable($mergeids);
		if ($mergeids == "") {
			Error("No subject IDs selected for merge");
			return;
		}
		
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
		<div class="ui text container">
			<div class="ui grid">
				<div class="middle aligned right aligned three wide column">
					<h2 class="header">Merging</h2>
				</div>
				<div class="center aligned middle aligned five wide column">
					<div class="ui segment">
						<?
						foreach ($uids as $uid) {
							echo "<span style='font-size: x-large'>$uid</span><br>";
						}
						?>
					</div>
				</div>
				<div class="center aligned middle aligned one wide column">
					<i class="big arrow alternate circle right icon"></i>
				</div>
				<div class="left aligned middle aligned seven wide column">
					<div class="ui segment">
						<div class="ui item">
							<div class="content">
								<h2 class="header">
									<i class="user icon"></i> <?=$finaluid?>
								</h2>
								<div class="meta">
									<?=$name?><br><?=$dob?><br><?=$gender?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="ui message">Merge queued</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SubmitMergeStudies ----------------- */
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

		Notice("Merge queued");
	}


	/* -------------------------------------------- */
	/* ------- DisplayMergeStudies ---------------- */
	/* -------------------------------------------- */
	function DisplayMergeStudies($studyids, $studyid) {

		$studyid = mysqli_real_escape_string($GLOBALS['linki'], $studyid);
		$studyids = mysqli_real_escape_array($GLOBALS['linki'], $studyids);

		$sqlstring = "select c.uid, a.study_modality from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $studyid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$modality = $row['study_modality'];

		?>
		Select studies that you <i>want to merge</i> (only <?=$modality?> modality for this enrollment are displayed). Then <i>select the final study</i> they will be merged into
		<br><br>
		<form action="merge.php" method="post" class="ui form">
		<input type="hidden" name="action" value="submitmergestudies">
		<input type="hidden" name="returnpage" value="<?=$returnpage?>">
		<table class="ui very compact celled collapsing grey table">
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
		<input type="radio" name="mergemethod" value="sortbyseriesdate" checked>Renumber series, ordering by series datetime (<b>ascending</b>)<br>
		<input type="radio" name="mergemethod" value="sortbyseriesnum" checked>Reorder by existing series number (<b>ascending</b>)<br>
		<input type="radio" name="mergemethod" value="concatbystudydateasc">Concatentate by study date (<b>ascending</b> <tt>2020-06-14 2020-06-22 ... 2020-08-01 2020-08-05</tt>)<br>
		<input type="radio" name="mergemethod" value="concatbystudydatedesc">Concatentate by study date (<b>descending</b> <tt>2020-08-05 2020-08-01 ... 2020-06-22 2020-06-14</tt>)<br>
		<input type="radio" name="mergemethod" value="concatbystudynumasc">Concatentate by study number (<b>ascending</b> <tt>1 2 ... 7 8</tt>)<br>
		<input type="radio" name="mergemethod" value="concatbystudynumdesc">Concatentate by study number (<b>descending</b> <tt>6 5 ... 2 1</tt>)<br>
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
		$subjectids = mysqli_real_escape_array($GLOBALS['linki'], $subjectids);

		if (((count($subjectids) == 0) || ($subjectids == "")) && ($subjectuid == "")) {
			?>
			No subjects selected for merge. Add a UID below.
			<br><br>
			<form action="merge.php" method="post" class="ui form">
			<input type="hidden" name="action" value="merge">
			<input type="text" name="subjectuid" placeholder="UID"><br>
			<input type="submit" class="ui primary button" value="Add UID">
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

		$numsubjects = count($subjects);
		$numcols = $numsubjects + 2;
		switch ($numcols) {
			case 1: $numcolstr = "one"; break;
			case 2: $numcolstr = "two"; break;
			case 3: $numcolstr = "three"; break;
			case 4: $numcolstr = "four"; break;
			case 5: $numcolstr = "five"; break;
			case 6: $numcolstr = "six"; break;
			case 7: $numcolstr = "seven"; break;
			case 8: $numcolstr = "eight"; break;
			case 9: $numcolstr = "nine"; break;
			case 10: $numcolstr = "ten"; break;
			default: $numcolstr = "";
		}
		
		/* display one column for each subject with a radio button to "merge all studies into this subject" */
		?>
		<style>
			/* .radio-toolbar2 input[type="radio"]:checked ~ * {
				background:yellow !important;
				padding: 5px;
			} */
		</style>

		<script>
			function highlight(label) {
				var dirformat = $("[name='dirformat']:checked").val();
				var elements = document.getElementsByName('labeldiv');

				var elementList = Array.prototype.slice.call(elements);
				//alert(elementList.length);
				elementList.forEach(clearHighlight);

				document.getElementById(label).classList.remove('secondary');
				document.getElementById(label).classList.add('yellow');
			}

			function clearHighlight(element) {
				element.classList.remove('yellow');
				element.classList.add('secondary');
			}
		</script>
		
		<div class="ui container">
			<div class="ui segment">
				<h2 class="ui header">
					<i class="copy icon"></i>
					<div class="content">
						Merge Subjects
						<div class="sub header">Merge all data from subjects into selected subject</div>
					</div>
				</h2>
				<p>Select the UID you want to merge into and edit information in that column. <b>Leftmost UID is selected by default</b> Only the information in the selected column will be saved, and all other projects will be merged into that UID. All other UIDs will be deleted.</p>
			</div>
		</div>
		
		<div class="ui center aligned basic segment">
			<?
				if ($numsubjects < 4) {
			?>
			<form action="merge.php" method="post" class="ui form">
				<input type="hidden" name="action" value="merge">
				<?
				for ($i=0;$i<count($subjects);$i++) {
					echo "<input type='hidden' name='subjectids[" . $i . "]' value='" . $subjects[$i]['id'] . "'>\n";
				}
				?>
				<div class="ui action input">
					<input type="text" name="subjectuid" placeholder="UID">
					<button type="submit" class="ui primary button" value="Add UID">
					<i class="user plus icon"></i> Add UID</button>
				</div>
			</form>
			<? } else { ?>
			Only 4 IDs allowed at a time
			<? } ?>		
		</div>
		
		<form action="merge.php" method="post" class="ui form">
			<input type="hidden" name="action" value="submitmerge">
			<input type="hidden" name="returnpage" value="<?=$returnpage?>">
			<?
			for ($i=0;$i<count($subjects);$i++) {
				echo "<input type='hidden' name='subjectids[" . $i . "]' value='" . $subjects[$i]['id'] . "'>\n";
			}
		?>
		
		<div class="ui compact grid">
			<div class="radio-toolbar2 <?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">UID</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						?>
							<div class="ui column uid">
								<div class="ui fitted inverted <? if ($i == 0) { echo "yellow"; } else { echo "secondary"; } ?> segment" id="label<?=$i?>" name="labeldiv">
									<div class="ui two column compact grid">
										<div class="column">
											<div class="ui radio checkbox" style="padding: 10px;">
												<input type="radio" id="uid<?=$i?>" name="selectedid" value="<?=$subjects[$i]['id']?>" <? if ($i == 0) { echo "checked"; } ?> onChange="highlight('label<?=$i?>');">
												<label style="font-size: x-large; font-weight: bold"><?=$subjects[$i]['uid']?></label>
											</div>
										</div>
										<div class=" right aligned column">
											<a name="removeid" class="ui small inverted button" style="margin-right: 10px;" onclick="document.removeidform.idtoremove.value='<?=$subjects[$i]['id']?>';document.removeidform.submit();"><i class="trash alternate icon"></i> Remove UID</a>
										</div>
									</div>
								</div>
							</div>
						<?
					}
				?>
				<div class="column">
				</div>
			</div>
			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">Name</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['name'] != $subjects[0]['name']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?>
							<div class="column <?=$class?>">
								<input type="text" name="name[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['name']?>">
							</div>
						<?
					}
				?>
				<div class="column">
				</div>
			</div>

			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">DOB</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['dob'] != $subjects[0]['dob']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?>
							<div class="column <?=$class?>"><input type="text" name="dob[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['dob'];?>"></div>
						<?
					}
				?>
			</div>

			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">Sex</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['gender'] != $subjects[0]['gender']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?>
							<div class="column <?=$class?>"><input type="text" name="gender[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['gender'];?>"></div>
						<?
					}
				?>
			</div>
			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">Ethnicity 1</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['ethnicity1'] != $subjects[0]['ethnicity1']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?><div class="column <?=$class?>"><input type="text" name="ethnicity1[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['ethnicity1'];?>"></div><?
					}
				?>
			</div>
			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">Ethnicity 2</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['ethnicity2'] != $subjects[0]['ethnicity2']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?><div class="column <?=$class?>"><input type="text" name="ethnicity2[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['ethnicity2'];?>"></div><?
					}
				?>
			</div>
			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">GUID</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['guid'] != $subjects[0]['guid']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?><div class="column <?=$class?>"><input type="text" name="guid[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['guid'];?>"></div><?
					}
				?>
			</div>
			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">Alternate Subject IDs</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
						if ($subjects[$i]['altuid'] != $subjects[0]['altuid']) { $class = "bodyhighlighted"; } else { $class = "bodynormal"; }
						?><div class="column <?=$class?>"><input type="text" name="altuids[<?=$subjects[$i]['id']?>]" value="<?=$subjects[$i]['altuid'];?>"></div><?
					}
				?>
			</div>
			<div class="<?=$numcolstr?> column row">
				<div class="right aligned column">
					<h3 class="header">Studies (w/enrollment group)</h3>
				</div>
				<?
					for ($i=0;$i<count($subjects);$i++) {
					?>
						<div class="top aligned column">
							<table class="ui small celled very compact table">
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
											<tr>
												<td><?=$study_num?></td>
												<td><?=$study_modality?></td>
												<td><?=$study_datetime?></td>
												<td><?=$study_site?></td>
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
						</div>
					<?
					}
				?>
			</div>
		</div>
			<input type="submit" value="Merge">
		</form>

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