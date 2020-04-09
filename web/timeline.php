<?
 // ------------------------------------------------------------------------------
 // NiDB timeline.php
 // Copyright (C) 2004 - 2020
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

	declare(strict_types = 1);
	define("LEGIT_REQUEST", true);
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Timeline</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$enrollmentid = GetVariable("enrollmentid");
	$id = GetVariable("id");
	$startdatetime = GetVariable("startdatetime");
	$enddatetime = GetVariable("enddatetime");
	$resolution = GetVariable("resolution");
	$selectedprotocols = GetVariable("selectedprotocols");
	$allmeasures = GetVariable("allmeasures");

	if (is_null($enrollmentid) || trim($enrollmentid) == "")
		$enrollmentid = $id;
	
	if (!is_null($startdatetime))
		$startdatetime = str_replace("T", " ", $startdatetime);
		
	if (!is_null($enddatetime))
		$enddatetime = str_replace("T", " ", $enddatetime);
		
	/* determine action */
	switch ($action) {
		case 'displaytimeline':
			DisplayTimeline($enrollmentid, $startdatetime, $enddatetime, $resolution, $selectedprotocols, $allmeasures);
			break;
		default:
			DisplayTimeline($enrollmentid, $startdatetime, $enddatetime, $resolution, $selectedprotocols, $allmeasures);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */
	

	/* -------------------------------------------- */
	/* ------- DisplayTimeline -------------------- */
	/* -------------------------------------------- */
	function DisplayTimeline($enrollmentid, $startdatetime, $enddatetime, $resolution, $selectedprotocols, $allmeasures) {
		
		if ((is_null($enrollmentid)) || ($enrollmentid < 0) || ($enrollmentid == "")) {
			?><span class="error">Invalid enrollment ID</div><?
			return;
		}
		$enrollmentid = trim($enrollmentid);

		/* get all the information about the enrollment */
		$q = mysqli_stmt_init($GLOBALS['linki']);
		mysqli_stmt_prepare($q, "select * from enrollment a left join projects b on a.project_id = b.project_id left join subjects c on a.subject_id = c.subject_id where a.enrollment_id = ?");
		mysqli_stmt_bind_param($q, 'i', $enrollmentid);
		$result = MySQLiBoundQuery($q, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
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
		}
		else {
			return;
		}

		/* get privacy information */
		$userid = $_SESSION['userid'];
	
		$perms = GetCurrentUserProjectPermissions($projectids);
		$urllist['Subjects'] = "subjects.php";
		NavigationBar("$uid", $urllist, $perms);

		if (is_null($selectedprotocols)) {
			$all = true;
		}
		else {
			/* get associative array of selected protocols */
			foreach ($selectedprotocols as $sp) {
				list($a, $b) = explode(":",$sp,2);
				$selprot[$a][] = $b;
				
				$selectedModalities[] = $a;
			}
			$selectedModalities = array_unique($selectedModalities);
			$all = false;
		}

		/* get list of studies for this enrollment */
		$sqlstring = "select a.*, datediff(a.study_datetime, c.birthdate) 'ageatscan' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id = $enrollmentid order by a.study_num asc";
		$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result2) > 0) {
			$i = 0;
			
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$study_id = $row2['study_id'];
				$study_num = $row2['study_num'];
				$modality = $row2['study_modality'];
				$study_datetime = $row2['study_datetime'];
				$study_ageatscan = $row2['study_ageatscan'];
				$calcage = number_format($row2['ageatscan']/365.25,1);
				$study_operator = $row2['study_operator'];
				$study_performingphysician = $row2['study_performingphysician'];
				$study_site = $row2['study_site'];
				$study_type = $row2['study_type'];
				$study_status = $row2['study_status'];
				$study_doradread = $row2['study_doradread'];
				
				/* get the age at the time of the study */
				if ((!is_null($study_ageatscan)) && (trim($study_ageatscan) != 0)) {
					$age = (double)$study_ageatscan;
				}
				else {
					$age = (double)$calcage;
				}

				/* check if valid modality */
				if ($modality != "") {
					$sqlstring4 = "show tables like '" . strtolower($modality) . "_series'";
					$result4 = MySQLiQuery($sqlstring4, __FILE__, __LINE__);
					if (mysqli_num_rows($result4) > 0) {
						$sqlstring3 = "select * from " . strtolower($modality) . "_series where study_id = $study_id order by series_num asc";
						$result3 = MySQLiQuery($sqlstring3, __FILE__, __LINE__);
						while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
							if ($row3['series_desc'] == "") {
								$protocol = $row3['series_protocol'];
							}
							else {
								$protocol = $row3['series_desc'];
							}
							$seriesdatetime = $row3['series_datetime'];
							$seriesnum = $row3['series_num'];
							$seriestr = $row3['series_tr'];
							$boldreps = $row3['bold_reps'];
							$dimT = $row3['dimT'];
							$numfiles = $row3['numfiles'];
							$seriesduration = $row3['series_duration'];
							
							if (is_null($mindate))
								$mindate = $seriesdatetime;
							else {
								if ($seriesdatetime < $mindate)
									$mindate = $seriesdatetime;
							}
							
							if (is_null($maxdate))
								$maxdate = $seriesdatetime;
							else {
								if ($seriesdatetime > $maxdate)
									$maxdate = $seriesdatetime;
							}

							/* calculate scan/experiment duration */
							if ($seriesduration == "") {
								if (($boldreps > 1) || ($dimT > 1)) {
									$duration = ($seriestr * max($boldreps, $dimT))/1000.0;
								}
								else {
									$duration = ($seriestr * $numfiles)/1000.0;
								}
							}
							else {
								$duration = $seriesduration;
							}
							
							/* check if all modalities should be displayed, or only the selected modalities */
							if (($all) || (in_array($modality, $selectedModalities))) {
								/* check if all protocols for this modality should be displayed, or only the selected */
								if ($all || in_array($protocol, $selprot[$modality])) {
									/* check if the series datetime is within range */
									if ( (is_null($startdatetime) || is_null($enddatetime)) || (($seriesdatetime > $startdatetime) && ($seriesdatetime < $enddatetime)) ) {
										$series[$i]['studynum'] = $study_num;
										$series[$i]['seriesnum'] = $seriesnum;
										$series[$i]['modality'] = $modality;
										$series[$i]['studydate'] = $study_datetime;
										$series[$i]['seriesdate'] = $seriesdatetime;
										$series[$i]['protocol'] = $protocol;
										$series[$i]['age'] = $age;
										$series[$i]['studysite'] = $study_site;
										$series[$i]['studytype'] = $study_type;
										$series[$i]['duration'] = $duration;
									}
								}
							}
							
							if (is_null($protocols[$modality]))
								$protocols[$modality][] = $protocol;
							
							if (!in_array($protocol, $protocols[$modality]))
								$protocols[$modality][] = $protocol;
							
							$modalities[] =$modality;
							
							$i++;
						}
					}
					else {
						echo "<span style='color:red'>Invalid modality [$modality]</span><br>";
					}
				}
			}
			
			/* get measures */
			if ($allmeasures) {
				$sqlstringA = "select a.*, b.*, c.* from measures a left join measurenames b on a.measurename_id = b.measurename_id left join measureinstruments c on a.instrumentname_id = c.measureinstrument_id where a.enrollment_id = $enrollmentid order by b.measure_name";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$measure_name = $rowA['measure_name'];
					$instrument_name = $rowA['instrument_name'];
					$measure_value = $rowA['measure_value'];
					$measure_startdate = $rowA['measure_startdate'];
					$measure_enddate = $rowA['measure_enddate'];
					$measure_duration = $rowA['measure_duration'];
					
					$series[$i]['studynum'] = '';
					$series[$i]['seriesnum'] = '';
					$series[$i]['modality'] = 'Measure';
					$series[$i]['studydate'] = $measure_startdate;
					$series[$i]['seriesdate'] = $measure_startdate;
					$series[$i]['protocol'] = $measure_name;
					$series[$i]['age'] = 0;
					$series[$i]['studysite'] = '';
					$series[$i]['studytype'] = $instrument_name;
					$series[$i]['duration'] = $measure_duration;
					$i++;
				}
			}
			
			$modalities = array_unique($modalities);
			
			DisplayOptionsTable($protocols, $modalities, $enrollmentid, $selectedprotocols, $allmeasures, $startdatetime, $enddatetime, $mindate, $maxdate);
			
			$series = array_msort($series, array('seriesdate'=>SORT_ASC, 'seriesnum'=>SORT_ASC, 'modality'=>SORT_DESC));
			?>
			
			<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
			<script type="text/javascript">
				google.charts.load('current', {'packages':['timeline']});
				google.charts.setOnLoadCallback(drawChart);
				function drawChart() {
					var container = document.getElementById('timeline');
					var chart = new google.visualization.Timeline(container);
					var dataTable = new google.visualization.DataTable();

					dataTable.addColumn({ type: 'string', id: 'President' });
					dataTable.addColumn({ type: 'date', id: 'Start' });
					dataTable.addColumn({ type: 'date', id: 'End' });
					dataTable.addRows([<?
						foreach ($series as $key => $v) {
							$sdate = str_replace(" ", "T", $v['seriesdate']);
							//$edate = date_format(date_add(date_create($sdate), date_interval_create_from_date_string("$v['duration'] seconds")), "Y-m-d H:i:s");
							$edateinsec = (int)(strtotime($sdate) + $v['duration']);
							$edate = date('Y-m-d H:i:s', $edateinsec);
							$desc = $v['protocol'];
							$datarows[] = "[ '$desc', new Date('$sdate'), new Date('$edate') ]";
						}
						echo implode2("\n,", $datarows);
						?>]);
					var options = { avoidOverlappingGridLines: false, height: 800, timeline: { barLabelStyle: {fontName: "arial", fontSize: "8" }} };
					chart.draw(dataTable, options);
				}
			</script>

			<div id="timeline" style="height: 800px;"></div>
  
			<table class="smalldisplaytable">
				<thead>
					<th>Study</th>
					<th>Series</th>
					<th>Modality</th>
					<th>Study Date</th>
					<th>Series Date</th>
					<th>Protocol</th>
					<th>Age<span class="tiny">&nbsp;y</span></th>
					<th>Site</th>
					<th>Visit</th>
					<th>Duration</th>
				</thead>
				<tbody>
				<?
					foreach ($series as $key => $tr) {
						?>
						<tr>
							<td><?=$tr['studynum']?></td>
							<td><?=$tr['seriesnum']?></td>
							<td><?
							 if ($tr['modality'] == "") { ?><span style="color: white; background-color: red">&nbsp;blank&nbsp;</span><? }
							 else { echo $tr['modality']; }
							?></td>
							<td><?=$tr['studydate']?></td>
							<td><?=$tr['seriesdate']?></td>
							<td><?=$tr['protocol']?></td>
							<td><?=number_format($tr['age'],1)?></td>
							<td><?=$tr['studysite']?></td>
							<td><?=$tr['studytype']?></td>
							<td><?=$tr['duration']?></td>
						</tr>
						<?
					}
				?>
				</tbody>
			</table>
			<?
		}
		else {
			echo "No imaging studies";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayOptionsTable ---------------- */
	/* -------------------------------------------- */
	function DisplayOptionsTable($protocols, $modalities, $enrollmentid, $selectedprotocols, $allmeasures, $startdatetime, $enddatetime, $mindate, $maxdate) {
		
		natsort($modalities);
		
		foreach ($selectedprotocols as $sp) {
			list($a, $b) = explode(":",$sp,2);
			
			$selprot[$a][] = $b;
		}
		
		?>
		<div style="background-color: #eee; border: 2px solid #888; border-radius: 8px; padding: 10px;">
		
		<form method="post" id="form1" action="timeline.php">
		<input type="hidden" name="action" value="displaytimeline">
		<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
		<b>Protocols</b>
		<table style="font-size: smaller;">
			<tr>
			<?
			foreach ($modalities as $modality) {
				$p = $protocols[$modality];
				natsort($p);
				$parentCheckID = "parent-$modality";
				$childCheckClass = "child-$modality";
				?>
				<script>
					$(document).ready(function() {
						$("#<?=$parentCheckID?>").click(function() {
							$(".<?=$childCheckClass?>").prop("checked", this.checked);
						});

						$('.<?=$childCheckClass?>').click(function() {
						if ($('.<?=$childCheckClass?>:checked').length == $('.<?=$childCheckClass?>').length) {
							$('#<?=$parentCheckID?>').prop('checked', true);
							} else {
								$('#<?=$parentCheckID?>').prop('checked', false);
							}
						});
					});
				</script>
				
				<td valign="top" style="border-right: 1px solid #aaa; padding: 8px">
				<b style="font-size: larger;"><?=$modality?></b> &nbsp; <label style="color: #666;"><input type="checkbox" id="<?=$parentCheckID?>"> Select all</label>
				<br><br>
				<div style="column-count: 2; column-fill: auto;">
				<?
				foreach ($p as $protocol) {
					if ((isset($selprot[$modality])) && (in_array($protocol, $selprot[$modality]))) {
						$checked = "checked";
					}
					else {
						$checked = "";
					}
					?>
					<label><input type="checkbox" class="all child-<?=$modality?>" name="selectedprotocols[]" value="<?=$modality?>:<?=$protocol?>" <?=$checked?>> <?=$protocol?></label><br>
					<?
				}
				?>
				</div>
				</td>
				<?
			}
			?>
			</tr>
		</table>
		<hr>
		<b>Measures</b>
		<input type="checkbox" name="allmeasures" value="1" <? if ($allmeasures) { echo "checked"; } ?>> Include all measures
		<hr>
		<b>Series Date</b><br>
		Data range <?=$mindate?> to <?=$maxdate?><br>
		<input type="datetime-local" name="startdatetime" value="<?=str_replace(" ", "T", $startdatetime)?>"> to <input type="datetime-local" name="enddatetime" value="<?=str_replace(" ", "T", $enddatetime)?>">
		
		<br><br><br>
		<input type="submit" value="Update">
		
		</form>
		</div>
		<?
	}
	
?>


<? include("footer.php") ?>
