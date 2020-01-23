<?
 // ------------------------------------------------------------------------------
 // NiDB analysisbuilder.php
 // Copyright (C) 2004 - 2019
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
	require "functions.php";
	require "includes_php.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$enrollmentid = GetVariable("enrollmentid");
	
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
    $a['reportformat'] = GetVariable("reportformat");
    $a['outputformat'] = GetVariable("outputformat");
	
	/* determine action */
	switch ($action) {
		default:
			if ($a['outputformat'] == "csv") {
				if ($a['reportformat'] == "long") {
					list($h, $t) = CreateLongReport($projectid, $a);
					PrintCSV($h,$t,'long');
				}
				else {
					list($h, $t) = CreateWideReport($projectid, $a);
					PrintCSV($h,$t,'wide');
				}
				exit(0);
			}
			?>

			<html>
				<head>
					<link rel="icon" type="image/png" href="images/squirrel.png">
					<title>NiDB - Analysis report builder</title>
				</head>

			<body>
				<div id="wrapper">
			<?
			/* requires don't work inside of functions */
			require "includes_html.php";
			require "menu.php";
			DisplayAnalysisSummaryBuilder($projectid, $a);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayAnalysisSummaryBuilder ------ */
	/* -------------------------------------------- */
	function DisplayAnalysisSummaryBuilder($projectid, $a) {
		
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
	
		?>
		<form method="post" action="analysisbuilder.php">
		<input type="hidden" name="action" value="viewanalysissummary">
		<b>Select Project: </b> <? DisplayProjectSelectBox(0,"projectid",'','',0,$projectid); ?> <input type="submit" value="Use selected project">
		</form>
		<?
		if (($projectid == '') || ($projectid == 0)) {
			return;
		}

		?>
		<span style="font-size: 16pt; font-weight: bold">Analysis Summary Builder</span>
		<br>
		<table style="width: 100%; height: 100%">
			<tr>
				<td width="15%" valign="top">
					<form method="post" action="analysisbuilder.php">
					<input type="hidden" name="action" value="viewanalysissummary">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<table width="100%">
						<tr>
							<td style="padding-left: 15px">
								<b>MR</b><br>
								<input type="checkbox" name="includeprotocolparms" <? if ($a['includeprotocolparms']) { echo "checked"; } ?> value="1">Include protocol parameters<br>
								<input type="checkbox" name="includemrqa" <? if ($a['includerqa']) { echo "checked"; } ?> value="1">Include QA
								<br>
								<select name="mr_protocols[]" multiple style="width: 400px" size="5">
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
								<select name="eeg_protocols[]" multiple style="width: 400px" size="5">
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
								<select name="et_protocols[]" multiple style="width: 400px" size="5">
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
								<br>
								<br>
								<input type="checkbox" name="includeallmeasures" value="1" <? if ($a['includeallmeasures']) echo "checked"; ?>>Include all measures
								<br>
								<input type="checkbox" name="includeallvitals" value="1" <? if ($a['includeallvitals']) echo "checked"; ?>>Include all vitals
								<br>
								<input type="checkbox" name="includealldrugs" value="1" <? if ($a['includealldrugs']) echo "checked"; ?>>Include all drugs/dosing
								<br>
								<br>
								<b>Options</b>
								<br>
								<input type="checkbox" name="includeemptysubjects" value="1" <? if ($a['includeemptysubjects']) echo "checked"; ?>>Include subjects without data<br>
								<br>
								<table>
									<tr>
										<td width="50%">
											Reporting format<br>
											<input type="radio" name="reportformat" value="long" <? if (($a['reportformat'] == "long") || ($a['reportformat'] == "")) echo "checked"; ?>>Long<br>
											<input type="radio" name="reportformat" value="wide" <? if ($a['reportformat'] == "wide") echo "checked"; ?>>Wide
										</td>
										<td style="padding-left: 20px">
											Output format<br>
											<input type="radio" name="outputformat" value="table" <? if (($a['outputformat'] == "table") || ($a['outputformat'] == "")) echo "checked"; ?>>Table (screen)<br>
											<input type="radio" name="outputformat" value="csv" <? if ($a['outputformat'] == "csv") echo "checked"; ?>>.csv
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					<div align="center">
						<input type="submit" value="Update Summary">
					</div>
					</form>
				</td>
				<td valign="top" height="100%">
					<div style="overflow: auto; height: 100%; width: 100%">
					<?
						if ($a['reportformat'] == "long") {
							list($h, $t) = CreateLongReport($projectid, $a);
						}
						elseif ($a['reportformat'] == "wide") {
							list($h, $t) = CreateWideReport($projectid, $a);
						}
						
						if ($a['outputformat'] == "table")
							PrintTable($h,$t,$a['reportformat']);
						elseif ($a['outputformat'] == "csv")
							PrintCSV($h,$t);
					?>
					</div>
				</td>
			</tr>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- CreateLongReport ------------------- */
	/* -------------------------------------------- */
	function CreateLongReport($projectid, $a) {
		
		/* create the table */
		$t;
		
		/* get all of the subject information */
		$sqlstring = "select a.*, b.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $projectid order by a.uid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$i = 0;
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjects[$i]['subjectid'] = $row['subject_id'];
			$subjects[$i]['uid'] = $row['uid'];
			$subjects[$i]['enrollmentid'] = $row['enrollment_id'];
			$subjects[$i]['dob'] = $row['birthdate'];
			$subjects[$i]['sex'] = $row['gender'];
			$subjects[$i]['height'] = $row['height'];
			$subjects[$i]['weight'] = $row['weight'];
			$subjects[$i]['enrollgroup'] = $row['enroll_subgroup'];
			
			$altuids = GetAlternateUIDs($subjects[$i]['subjectid'], $subjects[$i]['enrollmentid']);
			$subjects[$i]['altuids'] = implode2(" | ", $altuids);
			
			$i++;
		}
		
		/* loop through the subjects and add their info to the table */
		$row = 0;
		foreach ($subjects as $i => $subj) {
			$hasdata = false;
			/* get all of the protocol info */
			if (!empty($a['mr_protocols'])) {
				
				if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) {
					$sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = " . $subjects[$i]['enrollmentid'];
				}
				else {
					$mrprotocollist = MakeSQLListFromArray($a['mr_protocols']);
					$sqlstringA = "select a.*, b.*, count(a.series_desc) 'seriescount' from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = " . $subjects[$i]['enrollmentid'] . " and a.series_desc in ($mrprotocollist) group by a.series_desc";
				}
			
				/* add in the protocols */
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$seriesdesc = preg_replace('/\s+/', '', $rowA['series_desc']);
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
					
					if (($studyage == "") || ($studyage == "null") || ($studyage == 0))
						$age = strtotime($studydate) - strtotime($subj['dob']);
					else
						$age = $studyage;
					
					if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0))
						$height = $subj['height'];
					else
						$height = $studyheight;
					
					if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0))
						$weight = $subj['weight'];
					else
						$weight = $studyweight;
					
					/* need to add the demographic info to every row */
					$t[$row]['uid'] = $subj['uid'];
					$t[$row]['dob'] = $subj['dob'];
					$t[$row]['sex'] = $subj['sex'];
					$t[$row]['subjectheight'] = $height;
					$t[$row]['subjectweight'] = $weight;
					$t[$row]['enrollgroup'] = $subj['enrollgroup'];
					$t[$row]['altuids'] = $subj['altuids'];
					
					/* add the protocol info to the row */
					$t[$row]["$seriesdesc-SeriesNum"] = $seriesnum;
					$t[$row]["$seriesdesc-StudyDateTime"] = $studydatetime;
					$t[$row]["$seriesdesc-StudyNum"] = $studynum;
					$t[$row]["$seriesdesc-NumSeries"] = $numseries;
					$t[$row]["$seriesdesc-AgeAtScan"] = $age;
					$t[$row]["$seriesdesc-Height"] = $height;
					$t[$row]["$seriesdesc-Weight"] = $weight;
					$t[$row]["$seriesdesc-Notes"] = $studynotes;
					
					if ($a["includeprotocolparms"]) {
						$t[$row]["$seriesdesc-voxX"] = $pixdimX;
						$t[$row]["$seriesdesc-voxY"] = $pixdimY;
						$t[$row]["$seriesdesc-voxZ"] = $pixdimZ;
						$t[$row]["$seriesdesc-dimX"] = $dimX;
						$t[$row]["$seriesdesc-dimY"] = $dimY;
						$t[$row]["$seriesdesc-dimZ"] = $dimZ;
						$t[$row]["$seriesdesc-dimT"] = $dimT;
						$t[$row]["$seriesdesc-TR"] = $tr;
						$t[$row]["$seriesdesc-TE"] = $te;
						$t[$row]["$seriesdesc-TI"] = $ti;
						$t[$row]["$seriesdesc-flip"] = $flip;
					}
					
					if ($a['includemrqa']) {
						$sqlstringC = "select * from mr_qa where mrseries_id = $seriesid";
						$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
						$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
						
						$t[$row]["$seriesdesc-io_snr"] = $rowC['io_snr'];
						$t[$row]["$seriesdesc-pv_snr"] = $rowC['pv_snr'];
						$t[$row]["$seriesdesc-move_minx"] = $rowC['move_minx'];
						$t[$row]["$seriesdesc-move_miny"] = $rowC['move_miny'];
						$t[$row]["$seriesdesc-move_minz"] = $rowC['move_minz'];
						$t[$row]["$seriesdesc-move_maxx"] = $rowC['move_maxx'];
						$t[$row]["$seriesdesc-move_maxy"] = $rowC['move_maxy'];
						$t[$row]["$seriesdesc-move_maxz"] = $rowC['move_maxz'];
						$t[$row]["$seriesdesc-acc_minx"] = $rowC['acc_minx'];
						$t[$row]["$seriesdesc-acc_miny"] = $rowC['acc_miny'];
						$t[$row]["$seriesdesc-acc_minz"] = $rowC['acc_minz'];
						$t[$row]["$seriesdesc-acc_maxx"] = $rowC['acc_maxx'];
						$t[$row]["$seriesdesc-acc_maxy"] = $rowC['acc_maxy'];
						$t[$row]["$seriesdesc-acc_maxz"] = $rowC['acc_maxz'];
						$t[$row]["$seriesdesc-rot_minp"] = $rowC['rot_minp'];
						$t[$row]["$seriesdesc-rot_minr"] = $rowC['rot_minr'];
						$t[$row]["$seriesdesc-rot_miny"] = $rowC['rot_miny'];
						$t[$row]["$seriesdesc-rot_maxp"] = $rowC['rot_maxp'];
						$t[$row]["$seriesdesc-rot_maxr"] = $rowC['rot_maxr'];
						$t[$row]["$seriesdesc-rot_maxy"] = $rowC['rot_maxy'];
					}
					
					$row++;
					$hasdata = true;
				}
			}

			/* get all of the measures */
			if ($a['includeallmeasures']) {
				$sqlstringA = "select a.*, b.measure_name from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = " . $subj['enrollmentid'];
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					/* need to add the demographic info to every row */
					$t[$row]['uid'] = $subj['uid'];
					$t[$row]['dob'] = $subj['birthdate'];
					$t[$row]['sex'] = $subj['gender'];
					$t[$row]['subjectheight'] = $subj['height'];
					$t[$row]['subjectweight'] = $subj['weight'];
					$t[$row]['enrollgroup'] = $subj['enrollgroup'];
					$t[$row]['altuids'] = $subj['altuids'];

					/* add the measure info to this row */
					$measurename = $rowA['measure_name'];
					$t[$row][$measurename . '_startdatetime'] = $rowA['measure_startdate'];
					$t[$row][$measurename . '_duration'] = $rowA['measure_duration'];
					$t[$row][$measurename . '_enddatetime'] = $rowA['measure_enddate'];
					$t[$row][$measurename] = $rowA['measure_value'];

					$row++;
					$hasdata = true;
				}
			}
			
			/* get all of the vitals */
			if ($a['includeallvitals']) {
				$sqlstringA = "select a.*, b.vital_name from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where a.enrollment_id = " . $subj['enrollmentid'];
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					/* need to add the demographic info to every row */
					$t[$row]['uid'] = $subj['uid'];
					$t[$row]['dob'] = $subj['birthdate'];
					$t[$row]['sex'] = $subj['gender'];
					$t[$row]['subjectheight'] = $subj['height'];
					$t[$row]['subjectweight'] = $subj['weight'];
					$t[$row]['enrollgroup'] = $subj['enrollgroup'];
					$t[$row]['altuids'] = $subj['altuids'];

					/* add the measure info to this row */
					$vitalname = $rowA['vital_name'];
					$t[$row][$vitalname . '_startdatetime'] = $rowA['vital_startdate'];
					$t[$row][$vitalname . '_duration'] = $rowA['vital_duration'];
					$t[$row][$vitalname . '_enddatetime'] = $rowA['vital_enddate'];
					$t[$row][$vitalname] = $rowA['vital_value'];

					$row++;
					$hasdata = true;
				}
			}
			
			/* get all of the drugs/dosing */
			if ($a['includealldrugs']) {
				$sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = " . $subj['enrollmentid'];
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					/* need to add the demographic info to every row */
					$t[$row]['uid'] = $subj['uid'];
					$t[$row]['dob'] = $subj['birthdate'];
					$t[$row]['sex'] = $subj['gender'];
					$t[$row]['subjectheight'] = $subj['height'];
					$t[$row]['subjectweight'] = $subj['weight'];
					$t[$row]['enrollgroup'] = $subj['enrollgroup'];
					$t[$row]['altuids'] = $subj['altuids'];

					/* add the measure info to this row */
					$drugname = $rowA['drug_name'];
					$t[$row][$drugname . '_startdatetime'] = $rowA['drug_startdate'];
					$t[$row][$drugname . '_duration'] = $rowA['drug_duration'];
					$t[$row][$drugname . '_enddatetime'] = $rowA['drug_enddate'];
					$t[$row][$drugname] = $rowA['drug_value'];

					$row++;
					$hasdata = true;
				}
			}
			
			/* add a row if the subject had no data */
			if ((!$hasdata) && ($a['includeemptysubjects'] == 1)) {
				$t[$row]['uid'] = $subj['uid'];
				$t[$row]['dob'] = $subj['birthdate'];
				$t[$row]['sex'] = $subj['gender'];
				$t[$row]['subjectheight'] = $subj['height'];
				$t[$row]['subjectweight'] = $subj['weight'];
				$t[$row]['enrollgroup'] = $subj['enrollgroup'];
				$t[$row]['altuids'] = $subj['altuids'];
				$row++;
			}
		}
		
		/* create table header */
		$h2 = array();
		foreach ($t as $row => $subj) {
			foreach ($subj as $col => $vals) {
				$h2[$col] = "";
			}
		}
		$h = array_keys($h2);
		
		return array($h, $t);
	}
	
	
	/* -------------------------------------------- */
	/* ------- CreateWideReport ------------------- */
	/* -------------------------------------------- */
	function CreateWideReport($projectid, $a) {

		/* create the table */
		$t;
		
		/* get all of the subject information */
		$sqlstring = "select a.*, b.* from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = $projectid order by a.uid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$t[$uid]['uid'] = $uid;
			
			$subjectid 		= $t[$uid]['subjectid'] 	= $row['subject_id'];
			$enrollmentid 	= $t[$uid]['enrollmentid'] 	= $row['enrollment_id'];
			$dob 			= $t[$uid]['dob'] 			= $row['birthdate'];
			$sex 			= $t[$uid]['sex'] 			= $row['gender'];
			$subjectheight 	= $t[$uid]['subjectheight'] = $row['height'];
			$subjectweight 	= $t[$uid]['subjectweight'] = $row['weight'];
			$enrollgroup 	= $t[$uid]['enrollgroup'] 	= $row['enroll_subgroup'];
			
			$altuids = implode2(" | ", GetAlternateUIDs($subjectid, $enrollmentid));
			$t[$uid]['altuids'] = $altuids;
			
			/* get all of the protocol info */
			if (!empty($a['mr_protocols'])) {
				
				if (in_array("ALLPROTOCOLS", $a['mr_protocols'])) {
					$sqlstringA = "select a.*, b.* from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid";
				}
				else {
					$mrprotocollist = MakeSQLListFromArray($a['mr_protocols']);
					$sqlstringA = "select a.*, b.*, count(a.series_desc) 'seriescount' from mr_series a left join studies b on a.study_id = b.study_id where b.enrollment_id = $enrollmentid and a.series_desc in ($mrprotocollist) group by a.series_desc";
				}
			
				/* add in the protocols */
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				$i=1;
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$seriesdesc = preg_replace('/\s+/', '', $rowA['series_desc']);
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
					
					if (($studyage == "") || ($studyage == "null") || ($studyage == 0))
						$age = strtotime($studydate) - strtotime($dob);
					else
						$age = $studyage;
					
					if (($studyheight == "") || ($studyheight == "null") || ($studyheight == 0))
						$height = $subjectheight;
					else
						$height = $studyheight;
					
					if (($studyweight == "") || ($studyweight == "null") || ($studyweight == 0))
						$weight = $subjectweight;
					else
						$weight = $studyweight;
					
					/* add the protocol info to the row */
					$t[$uid]["$seriesdesc"."_SeriesNum_$i"] = $seriesnum;
					$t[$uid]["$seriesdesc"."_StudyDateTime_$i"] = $studydatetime;
					$t[$uid]["$seriesdesc"."_StudyNum_$i"] = $studynum;
					$t[$uid]["$seriesdesc"."_NumSeries_$i"] = $numseries;
					$t[$uid]["$seriesdesc"."_AgeAtScan_$i"] = $age;
					$t[$uid]["$seriesdesc"."_Height_$i"] = $height;
					$t[$uid]["$seriesdesc"."_Weight_$i"] = $weight;
					$t[$uid]["$seriesdesc"."_Notes_$i"] = $studynotes;
					
					if ($a["includeprotocolparms"]) {
						$t[$uid]["$seriesdesc"."_voxX_$i"] = $pixdimX;
						$t[$uid]["$seriesdesc"."_voxY_$i"] = $pixdimY;
						$t[$uid]["$seriesdesc"."_voxZ_$i"] = $pixdimZ;
						$t[$uid]["$seriesdesc"."_dimX_$i"] = $dimX;
						$t[$uid]["$seriesdesc"."_dimY_$i"] = $dimY;
						$t[$uid]["$seriesdesc"."_dimZ_$i"] = $dimZ;
						$t[$uid]["$seriesdesc"."_dimT_$i"] = $dimT;
						$t[$uid]["$seriesdesc"."_TR_$i"] = $tr;
						$t[$uid]["$seriesdesc"."_TE_$i"] = $te;
						$t[$uid]["$seriesdesc"."_TI_$i"] = $ti;
						$t[$uid]["$seriesdesc"."_flip_$i"] = $flip;
					}
					
					if ($a['includemrqa']) {
						$sqlstringC = "select * from mr_qa where mrseries_id = $seriesid";
						$resultC = MySQLiQuery($sqlstringC,__FILE__,__LINE__);
						$rowC = mysqli_fetch_array($resultC, MYSQLI_ASSOC);
						
						$t[$uid]["$seriesdesc"."_io_snr_$i"] = $rowC['io_snr'];
						$t[$uid]["$seriesdesc"."_pv_snr_$i"] = $rowC['pv_snr'];
						$t[$uid]["$seriesdesc"."_move_minx_$i"] = $rowC['move_minx'];
						$t[$uid]["$seriesdesc"."_move_miny_$i"] = $rowC['move_miny'];
						$t[$uid]["$seriesdesc"."_move_minz_$i"] = $rowC['move_minz'];
						$t[$uid]["$seriesdesc"."_move_maxx_$i"] = $rowC['move_maxx'];
						$t[$uid]["$seriesdesc"."_move_maxy_$i"] = $rowC['move_maxy'];
						$t[$uid]["$seriesdesc"."_move_maxz_$i"] = $rowC['move_maxz'];
						$t[$uid]["$seriesdesc"."_acc_minx_$i"] = $rowC['acc_minx'];
						$t[$uid]["$seriesdesc"."_acc_miny_$i"] = $rowC['acc_miny'];
						$t[$uid]["$seriesdesc"."_acc_minz_$i"] = $rowC['acc_minz'];
						$t[$uid]["$seriesdesc"."_acc_maxx_$i"] = $rowC['acc_maxx'];
						$t[$uid]["$seriesdesc"."_acc_maxy_$i"] = $rowC['acc_maxy'];
						$t[$uid]["$seriesdesc"."_acc_maxz_$i"] = $rowC['acc_maxz'];
						$t[$uid]["$seriesdesc"."_rot_minp_$i"] = $rowC['rot_minp'];
						$t[$uid]["$seriesdesc"."_rot_minr_$i"] = $rowC['rot_minr'];
						$t[$uid]["$seriesdesc"."_rot_miny_$i"] = $rowC['rot_miny'];
						$t[$uid]["$seriesdesc"."_rot_maxp_$i"] = $rowC['rot_maxp'];
						$t[$uid]["$seriesdesc"."_rot_maxr_$i"] = $rowC['rot_maxr'];
						$t[$uid]["$seriesdesc"."_rot_maxy_$i"] = $rowC['rot_maxy'];
					}
					$i++;
				}
			}

			/* get all of the measures */
			if ($a['includeallmeasures']) {
				$sqlstringA = "select a.*, b.measure_name from measures a left join measurenames b on a.measurename_id = b.measurename_id where enrollment_id = $enrollmentid";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				$i=1;
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					/* add the measure info to this row */
					$t[$uid]["measure_startdatetime_$i"] = $rowA['measure_startdate'];
					$t[$uid]["measure_duration_$i"] = $rowA['measure_duration'];
					$t[$uid]["measure_enddatetime_$i"] = $rowA['measure_enddate'];
					$measurename = $rowA['measure_name'];
					$t[$uid]["measure_$measurename"."_$i"] = $rowA['measure_value'];
					$i++;
				}
			}
			
			/* get all of the vitals */
			if ($a['includeallvitals']) {
				$sqlstringA = "select a.*, b.vital_name from vitals a left join vitalnames b on a.vitalname_id = b.vitalname_id where a.enrollment_id = $enrollmentid";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				$i=1;
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					/* add the measure info to this row */
					$t[$uid]["vital_startdatetime_$i"] = $rowA['vital_startdate'];
					$t[$uid]["vital_duration_$i"] = $rowA['vital_duration'];
					$t[$uid]["vital_enddatetime_$i"] = $rowA['vital_enddate'];
					$vitalname = $rowA['vital_name'];
					$t[$uid]["vital_$vitalname"."_$i"] = $rowA['vital_value'];
					$i++;
				}
			}
			
			/* get all of the drugs/dosing */
			if ($a['includealldrugs']) {
				$sqlstringA = "select a.*, b.drug_name from drugs a left join drugnames b on a.drugname_id = b.drugname_id where a.enrollment_id = $enrollmentid";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				$i=1;
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					/* add the measure info to this row */
					$t[$uid]["drug_startdatetime_$i"] = $rowA['drug_startdate'];
					$t[$uid]["drug_duration_$i"] = $rowA['drug_duration'];
					$t[$uid]["drug_enddatetime_$i"] = $rowA['drug_enddate'];
					$drugname = $rowA['drug_name'];
					$t[$uid]["drug_$drugname"."_$i"] = $rowA['drug_value'];
					$i++;
				}
			}
			
			/* add a row if the subject had no data */
			if ((!$hasdata) && ($a['includeemptysubjects'] == 1)) {
				$t[$uid]['uid'] = $uid;
				$t[$uid]['dob'] = $dob;
				$t[$uid]['sex'] = $sex;
				$t[$uid]['subjectheight'] = $subjectheight;
				$t[$uid]['subjectweight'] = $subjectweight;
				$t[$uid]['enrollgroup'] = $enrollgroup;
				$t[$uid]['altuids'] = $altuids;
			}
		}
		
		/* create table header */
		$h2 = array();
		foreach ($t as $row => $subj) {
			foreach ($subj as $col => $vals) {
				$h2[$col] = "";
			}
		}
		$h = array_keys($h2);
		
		return array($h, $t);
	}


	/* -------------------------------------------- */
	/* ------- PrintCSV --------------------------- */
	/* -------------------------------------------- */
	function PrintCSV($h, $t, $format) {
		
		$csv = "";
		$cols = array();
		
		$csv .= implode2(",", $h) . "\n";
		
		foreach ($t as $id => $subject) {
			$cols = array();
			foreach ($h as $col) {
				$cols[] = $t[$id][$col];
			}
			$csv .= implode2(",", $cols) . "\n";
		}

		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=NiDB-AnalysisBuilder-$format.csv");
		header("Content-Type: text/plain");
		header("Content-length: " . strlen($csv) . "\n\n");
		header("Content-Transfer-Encoding: text");
		// output csv to the browser
		echo $csv;
	}
	
	
	/* -------------------------------------------- */
	/* ------- PrintTable ------------------------- */
	/* -------------------------------------------- */
	function PrintTable($h, $t, $format) {
		
		$numcols = count($h);
		$numrows = count($t);
		
		if ($numcols == 0 && $numrows == 0) {
			?>
			No data to display
			<?
			return;
		}
		
		?>
		Displaying <b><?=$numcols?></b> cols by <b><?=$numrows?></b> rows in <b><?=$format?></b> format
		<br><br>
		<table class="summarytable">
			<thead>
				<tr>
				<?
				foreach ($h as $col) {
					?><th><?=$col?></th><?
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
						foreach ($h as $col) {
							if (is_numeric($t[$id][$col]) && (strpos($t[$id][$col], '.') !== false))
								$disp = number_format($t[$id][$col], 3);
							else
								$disp = $t[$id][$col];
							?><td><?=$disp?></td><?
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
