<?
 // ------------------------------------------------------------------------------
 // NiDB assessments.php
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
		<title>NiDB - Assessments</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";
	
	/* setup variables */
	$action = GetVariable("action");
	$enrollmentid = GetVariable("enrollmentid");
	$experimentid = GetVariable("experimentid");
	$formid = GetVariable("formid");
	$val_strings = GetVariables("string");
	$val_numbers = GetVariables("number");
	$val_texts = GetVariables("text");
	$val_dates = GetVariables("date");
	$val_files = GetVariables("file");
	$experimentor = GetVariable("experimentor");
	$experimentdate = GetVariable("experimentdate");
	$label = GetVariable("label");
	$notes = GetVariable("notes");

	//print_r($val_text);
	
	/* determine action */
	switch ($action) {
		case 'create':
			CreateForm($enrollmentid, $formid, $username);
			break;
		case 'completed':
			SetAsComplete($experimentid);
			ViewForm($experimentid, "view");
			break;
		case 'save':
			$experimentid = SaveForm($enrollmentid, $formid, $val_strings, $val_numbers, $val_texts, $val_dates, $val_files, $experimentor, $experimentdate, $username, $label, $notes);
			ViewForm($experimentid, "view");
			break;
		case 'update':
			UpdateForm($experimentid, $enrollmentid, $formid, $val_strings, $val_numbers, $val_texts, $val_dates, $val_files, $experimentor, $experimentdate, $username, $label, $notes);
			ViewForm($experimentid, "view");
			break;
		case 'view':
			ViewForm($experimentid, "print");
			break;
		case 'edit':
			ViewForm($experimentid, "edit");
			break;
		case 'print':
			PrintForm($experimentid);
			break;
		default:
			echo "No action specified";
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- SetAsComplete ---------------------- */
	/* -------------------------------------------- */
	function SetAsComplete($experimentid) {
		$sqlstring = "update assessments set iscomplete = 1 where experiment_id = $experimentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
	}

	
	/* -------------------------------------------- */
	/* ------- SaveForm --------------------------- */
	/* -------------------------------------------- */
	function SaveForm($enrollmentid, $formid, $val_strings, $val_numbers, $val_texts, $val_dates, $val_files, $experimentor, $experimentdate, $username, $label, $notes) {

		$experimentor = mysqli_real_escape_string($GLOBALS['linki'], $experimentor);
		$label = mysqli_real_escape_string($GLOBALS['linki'], $label);
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);
		
		$sqlstring = "insert into assessments (enrollment_id, form_id, exp_admindate, experimentor, rater_username, label, notes) values ($enrollmentid, $formid, '$experimentdate', '$experimentor', '$username', '$label', '$notes')";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$experimentid = mysql_insert_id();
		
		/* insert all the strings */
		if (isset($val_strings)) {
			foreach ($val_strings as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_string, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		/* insert all the numbers */
		if (isset($val_numbers)) {
			foreach ($val_numbers as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_number, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		/* insert all the texts */
		if (isset($val_texts)) {
			foreach ($val_texts as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_text, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		/* insert all the dates */
		//PrintVariable($val_dates,"val_dates");
		if (isset($val_dates)) {
			foreach ($val_dates as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_date, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		return $experimentid;
	}


	/* -------------------------------------------- */
	/* ------- UpdateForm ------------------------- */
	/* -------------------------------------------- */
	function UpdateForm($experimentid, $enrollmentid, $formid, $val_strings, $val_numbers, $val_texts, $val_dates, $val_files, $experimentor, $experimentdate, $username, $label, $notes) {
	
		$experimentor = mysqli_real_escape_string($GLOBALS['linki'], $experimentor);
		$label = mysqli_real_escape_string($GLOBALS['linki'], $label);
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);

		$sqlstring = "update assessments set exp_admindate = '$experimentdate', experimentor = '$experimentor', label = '$label', notes = '$notes' where experiment_id = $experimentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");

		/* delete all old assessment_data entries */
		$sqlstring = "delete from assessment_data where experiment_id = $experimentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		/* insert all the strings */
		if (isset($val_strings['string'])) {
			foreach ($val_strings['string'] as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_string, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		/* insert all the numbers */
		if (isset($val_numbers['number'])) {
			foreach ($val_numbers['number'] as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_number, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		/* insert all the texts */
		if (isset($val_texts)) {
			foreach ($val_texts as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_text, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		/* insert all the dates */
		if (isset($val_dates['date'])) {
			foreach ($val_dates['date'] as $formfieldid => $value) {
				if (is_array($value)) $value = implode(",", $value);
				$value = mysqli_real_escape_string($GLOBALS['linki'], trim($value));
				$sqlstring = "insert into assessment_data (formfield_id, experiment_id, value_date, update_username) values ($formfieldid, $experimentid, '$value', '$username')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
	}
	
	/* -------------------------------------------- */
	/* ------- CreateForm ------------------------- */
	/* -------------------------------------------- */
	function CreateForm($enrollmentid, $formid, $username) {
	
		$sqlstring = "select * from assessment_forms where form_id = $formid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$sqlstring = "select a.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$id = $row['subject_id'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$id";
		NavigationBar("Subjects", $urllist);
		
	?>
		<div align="center">
		<br><br>
		<form action="assessments.php" method="post">
		<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
		<input type="hidden" name="formid" value="<?=$formid?>">
		<input type="hidden" name="action" value="save">
		
		<table>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Experimentor</td>
				<td><input type="text" name="experimentor" value="<?=$username?>"></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Experiment date</td>
				<td><input type="text" name="experimentdate" value="<?=date("Y-m-d");?>"></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Label</td>
				<td><input type="text" name="label" value=""></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Notes</td>
				<td><textarea name="notes"></textarea></td>
			</tr>
		</table>
		<br><br>
		
		<!--
		<table class="formentrytable">
			<tr>
				<td class="title" colspan="3"><?=$title?></td>
			</tr>
			<tr>
				<td class="desc" colspan="3"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "select * from assessment_formfields where form_id = $formid order by formfield_order + 0";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$formfield_id = $row['formfield_id'];
					$formfield_desc = $row['formfield_desc'];
					$formfield_values = $row['formfield_values'];
					$formfield_datatype = $row['formfield_datatype'];
					$formfield_order = $row['formfield_order'];
					$formfield_scored = $row['formfield_scored'];
					$formfield_haslinebreak = $row['formfield_haslinebreak'];
					
					?>
					<tr>
						<? if ($formfield_datatype == "header") { ?>
							<td colspan="2" class="sectionheader"><?=$formfield_desc?></td>
						<? } else { ?>
							<td class="field"><?=$formfield_desc?></td>
							<td class="value">
							<?
								switch ($formfield_datatype) {
									case "binary": ?><input type="file" name="file-<?=$formfield_id?>[]"><? break;
									case "multichoice": ?>
										<select multiple name="text-<?=$formfield_id?>[]" style="height:150px">
											<?
												$values = explode(",", $formfield_values);
												natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<option value="<?=$value?>"><?=$value?></option>
												<?
												}
											?>
										</select>
									<? break;
									case "singlechoice": ?>
											<?
												$values = explode(",", $formfield_values);
												//natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<input type="radio"  name="text-<?=$formfield_id?>[]" value="<?=$value?>"><?=$value?>
												<?
													if ($formfield_haslinebreak) { echo "<br>"; } else { echo "&nbsp;"; }
												}
											?>
									<? break;
									case "date": ?><input type="date" name="date-<?=$formfield_id?>[]"><span class="tiny">date</span><? break;
									case "number": ?><input type="text" name="number-<?=$formfield_id?>[]"><span class="tiny">number</span><? break;
									case "string": ?><input type="text" name="string-<?=$formfield_id?>[]"><span class="tiny">string</span><? break;
									case "text": ?><textarea name="text-<?=$formfield_id?>[]"></textarea><? break;
								}
							?>
						<? } ?>
						</td>
						<? if ($formfield_scored) {?>
						<td><input type="text" size="2"></td>
						<? } ?>
						<td class="order"><?=$formfield_order?></td>
					</tr>
					<?
				}
			?>
			-->
			<input type="submit" value="Create">
		</form>
		<br><br>
		
		</div>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- ViewForm --------------------------- */
	/* -------------------------------------------- */
	function ViewForm($experimentid, $viewtype) {
	
		$sqlstring = "select * from assessments where experiment_id = $experimentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$enrollmentid = $row['enrollment_id'];
		$formid = $row['form_id'];
		$experimentor = $row['experimentor'];
		$exp_admindate = $row['exp_admindate'];
		$iscomplete = $row['iscomplete'];
		$lastupdate = date("M n, Y g:i a",strtotime($row['lastupdate']));
		$label = $row['label'];
		$notes = $row['notes'];

		$sqlstring = "select * from assessment_forms where form_id = $formid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$sqlstring = "select a.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$id = $row['subject_id'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$id";
		NavigationBar("Subjects", $urllist);
		
		if ($viewtype == "view") {
			$readonly = "readonly";
		}
		if ($viewtype == "print") $print = 1; else $print = 0;
		
		if ($iscomplete) {
			$formstatusclass = "completeform";
			$formstatus = "Complete";
		}
		else {
			$formstatusclass = "incompleteform";
			$formstatus = "Incomplete";
		}
		
	?>
		<div align="center">
		<br><br>
		<form action="assessments.php" method="post">
		<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
		<input type="hidden" name="formid" value="<?=$formid?>">
		<input type="hidden" name="experimentid" value="<?=$experimentid?>">
		<input type="hidden" name="action" value="update">
		
		<table>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Experimentor</td>
				<td><input type="text" name="experimentor" value="<?=$experimentor?>" <?=$readonly?>></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Experiment date</td>
				<td><input type="text" name="experimentdate" value="<?=$exp_admindate?>" <?=$readonly?>></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Label</td>
				<td><input type="text" name="label" value="<?=$label?>"></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Notes</td>
				<td><textarea name="notes"><?=$notes?></textarea></td>
			</tr>
		</table>
		<br><br>
		
		<table class="formentrytable">
			<tr>
				<td class="title" colspan="2"><?=$title?></td>
				<td rowspan="2"><div class="<?=$formstatusclass?>"><?=$formstatus?><br><span style="font-size:8pt; font-weight: normal"><?=$lastupdate?></span></div></td>
			</tr>
			<tr>
				<td class="desc" colspan="2"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "SELECT a.*, b.value_text, b.value_number, b.value_string, b.value_binary, b.value_date, b.update_username FROM assessment_formfields a left outer join assessment_data b on a.formfield_id = b.formfield_id where a.form_id = $formid and (b.experiment_id = $experimentid or b.experiment_id is NULL) order by a.formfield_order + 0";
				//echo $sqlstring;
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//print_r($row);
					$formfield_id = $row['formfield_id'];
					$formfield_desc = $row['formfield_desc'];
					$formfield_values = $row['formfield_values'];
					$formfield_datatype = $row['formfield_datatype'];
					$formfield_order = $row['formfield_order'];
					$formfield_scored = $row['formfield_scored'];
					$formfield_haslinebreak = $row['formfield_haslinebreak'];
					
					$value_text = $row['value_text'];
					$value_number = $row['value_number'];
					$value_string = $row['value_string'];
					$value_binary = $row['value_binary'];
					$value_date = $row['value_date'];
					$update_username = $row['update_username'];
					
					?>
					<tr>
						<? if ($formfield_datatype == "header") { ?>
							<td colspan="2" class="sectionheader"><?=$formfield_desc?></td>
						<? } else { ?>
							<td class="field">
								<?=$formfield_order?> <?=$formfield_desc?>
							</td>
							<td class="value">
							<?
								switch ($formfield_datatype) {
									case "binary": ?><input type="file" name="file-<?=$formfield_id?>[]" <?=$readonly?>><? break;
									case "multichoice":
										if ($viewtype == "print") {
											echo str_replace(",", "<br>", $value_text);
										}
										else {
										?>
											<!--<select multiple="multiple" name="text[<?=$formfield_id?>]" id="multiselect">-->
											<select multiple="multiple" name="text-<?=$formfield_id?>[]" <?=$readonly?> style="height:150px">
												<?
													$formvalues = explode(",", $formfield_values);
													$experimentvalues = explode(",", $value_text);
													natsort($formvalues);
													foreach ($formvalues as $value) {
														$selected = "";
														$value = trim($value);
														foreach ($experimentvalues as $expvalue) {
															if ($value == $expvalue) { $selected = "selected"; }
														}
													?>
														<option value="<?=$value?>" <?=$selected?>><?=$value?></option>
													<?
													}
												?>
											</select>
											<table>
												<tr style="font-size:8pt">
													<td valign="top"><b>Originally selected values</b></td>
													<td>
														<? echo str_replace(",", "<br>", $value_text); ?>
													</td>
												</tr>
											</table>
										<?
										}
										break;
									case "singlechoice":
										if ($viewtype == "print") {
											//echo "value_text [$value_text]";
											$values = explode(",", $formfield_values);
											foreach ($values as $value) {
												$value = trim($value);
												if ($value == $value_text) {
													?>&nbsp; <span style="background-color:lightyellow;padding:2px;border:1px solid orange;border-radius:5px"><?=$value?></span>
													<?
												} else {
													echo "&nbsp; <span style='color:#AAA'>$value</span>";
												}
												if ($formfield_haslinebreak) { echo "<br>"; } else { echo "&nbsp;"; }
											}
											//echo $value_text;
										}
										else {
											//echo "value_text [$value_text]";
											$values = explode(",", $formfield_values);
											foreach ($values as $value) {
												$value = trim($value);
												if ($value == $value_text) { $checked = "checked"; } else { $checked = "";}
												?>
												<input type="radio"  name="text-<?=$formfield_id?>[]" value="<?=$value?>" <?=$checked?> <?=$readonly?>><?=$value?>
												<?
												if ($formfield_haslinebreak) { echo "<br>"; } else { echo "&nbsp;"; }
											}
										}
										break;
									case "date":
										if ($viewtype == "print") { echo $value_date; }
										else {
											?><input type="date" name="date-<?=$formfield_id?>[]" value="<?=$value_date?>" <?=$readonly?>><span class="tiny">date</span><?
										}
										break;
									case "number":
										if ($viewtype == "print") { echo $value_number; }
										else {
											?><input type="text" placeholder="Enter a number" name="number-<?=$formfield_id?>[]" value="<?=$value_number?>" <?=$readonly?>><?
										}
										break;
									case "string":
										if ($viewtype == "print") { echo $value_string; }
										else {
											?><input type="text" placeholder="Enter a string" name="string-<?=$formfield_id?>[]" value="<?=$value_string?>" <?=$readonly?>><?
										}
										break;
									case "text":
										if ($viewtype == "print") { echo $value_text; }
										else {
											?><textarea name="text-<?=$formfield_id?>[]" <?=$readonly?>><?=$value_text?></textarea><?
										}
										break;
								}
							?>
						<? } ?>
						</td>
						<? if ($formfield_scored) {?>
						<td><input type="text" size="2"></td>
						<? } ?>
						<!--<td class="order"><?=$formfield_order?></td>-->
						<!--<td class="rater"><?=$update_username?></td>-->
					</tr>
					<?
				}
				
				if (!$iscomplete) {
			?>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" value="Update">
				</td>
			</tr>
			<? } ?>
		</table>
		</form>
		<br><br>
		
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- PrintForm -------------------------- */
	/* -------------------------------------------- */
	function PrintForm($experimentid) {
	
		$sqlstring = "select * from assessments where experiment_id = $experimentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$enrollmentid = $row['enrollment_id'];
		$formid = $row['form_id'];
		$experimentor = $row['experimentor'];
		$exp_admindate = $row['exp_admindate'];
		$iscomplete = $row['iscomplete'];
		$lastupdate = date("M n, Y g:i a",strtotime($row['lastupdate']));

		$sqlstring = "select * from assessment_forms where form_id = $formid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$sqlstring = "select a.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$id = $row['subject_id'];
		
		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$id";
		NavigationBar("Subjects", $urllist);
		
		if ($viewtype == "view") {
			$readonly = "readonly";
		}
		if ($viewtype == "print") $print = 1; else $print = 0;
		
		if ($iscomplete) {
			$formstatusclass = "completeform";
			$formstatus = "Complete";
		}
		else {
			$formstatusclass = "incompleteform";
			$formstatus = "Incomplete";
		}
		
	?>
		<div align="center">
		<br><br>
		<table>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Experimentor</td>
				<td><?=$experimentor?></td>
			</tr>
			<tr>
				<td align="right" style="font-weight: bold; font-size: 11pt; color: #444444">Experiment date</td>
				<td><?=$exp_admindate?></td>
			</tr>
		</table>
		<br><br>
		
		<table class="formentrytable">
			<tr>
				<td class="title" colspan="3"><?=$title?></td>
				<td rowspan="2"><div class="<?=$formstatusclass?>"><?=$formstatus?><br><span style="font-size:8pt; font-weight: normal"><?=$lastupdate?></span></div></td>
			</tr>
			<tr>
				<td class="desc" colspan="3"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "SELECT a.*, b.value_text, b.value_number, b.value_string, b.value_binary, b.value_date, b.update_username FROM assessment_formfields a left outer join assessment_data b on a.formfield_id = b.formfield_id where a.form_id = $formid and (b.experiment_id = $experimentid or b.experiment_id is NULL) order by a.formfield_order + 0";
				//echo $sqlstring;
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					//print_r($row);
					$formfield_id = $row['formfield_id'];
					$formfield_desc = $row['formfield_desc'];
					$formfield_values = $row['formfield_values'];
					$formfield_datatype = $row['formfield_datatype'];
					$formfield_order = $row['formfield_order'];
					$formfield_scored = $row['formfield_scored'];
					$formfield_haslinebreak = $row['formfield_haslinebreak'];
					
					$value_text = $row['value_text'];
					$value_number = $row['value_number'];
					$value_string = $row['value_string'];
					$value_binary = $row['value_binary'];
					$value_date = $row['value_date'];
					$update_username = $row['update_username'];
					
					?>
					<tr>
						<? if ($formfield_datatype == "header") { ?>
							<td colspan="2" class="sectionheader"><?=$formfield_desc?></td>
						<? } else { ?>
							<td class="field"><?=$formfield_desc?></td>
							<td class="value">
							<?
								switch ($formfield_datatype) {
									case "binary": ?><input type="file" name="file-<?=$formfield_id?>[]" <?=$readonly?>><? break;
									case "multichoice":
										echo str_replace(",", "<br>", $value_text);
										break;
									case "singlechoice":
										if ($viewtype == "print") {
											echo $value_string;
										}
										else {
											$values = explode(",", $formfield_values);
											foreach ($values as $value) {
												if ($value == $value_string) { $checked = "checked"; } else { $checked = "";}
												?>
												<input type="radio"  name="text-<?=$formfield_id?>[]" value="<?=$value?>" <?=$checked?> <?=$readonly?>><?=$value?> &nbsp;
												<?
											}
										}
										break;
									case "date":
										if ($viewtype == "print") {
											echo $value_date;
										}
										else {
										?>
											<input type="date" name="date-<?=$formfield_id?>[]" value="<?=$value_date?>" <?=$readonly?>><span class="tiny">date</span>
										<?
										}
										break;
									case "number":
										if ($viewtype == "print") {
											echo $value_number;
										}
										else {
										?>
											<input type="text" name="number-<?=$formfield_id?>[]" value="<?=$value_number?>" <?=$readonly?>><span class="tiny">number</span>
										<?
										}
										break;
									case "string":
										if ($viewtype == "print") {
											echo $value_string;
										}
										else {
										?>
											<input type="text" name="string-<?=$formfield_id?>[]" value="<?=$value_string?>" <?=$readonly?>><span class="tiny">string</span>
										<?
										}
										break;
									case "text":
										if ($viewtype == "print") {
											echo $value_text;
										}
										else {
										?>
											<textarea name="text-<?=$formfield_id?>[]" <?=$readonly?>><?=$value_text?></textarea>
										<?
										}
										break;
								}
							?>
						<? } ?>
						</td>
						<? if ($formfield_scored) {?>
						<td><input type="text" size="2"></td>
						<? } ?>
						<td class="order"><?=$formfield_order?></td>
						<td class="rater"><?=$update_username?></td>
					</tr>
					<?
				}
				
				if (!$iscomplete) {
			?>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" value="Update">
				</td>
			</tr>
			<? } ?>
		</table>
		</form>
		<br><br>
		
		</div>
	<?
	}
	
?>


<? include("footer.php") ?>
