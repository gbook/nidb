<?
 // ------------------------------------------------------------------------------
 // NiDB measures.php
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
		<title>NiDB - Phenotypic measures</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	//PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$measureid = GetVariable("measureid");
	$enrollmentid = GetVariable("enrollmentid");
	$measurename = GetVariable('measure_name');
	$measureinstrument = GetVariable('measure_instrument');
	$measurevalue = GetVariable('measure_value');
	$measuredatecompleted = GetVariable('measure_datecompleted');
	$measurerater = GetVariable('measure_rater');
						
	/* determine action */
	switch ($action) {
		case 'addmeasure':
			AddMeasure($enrollmentid, $measurename, $measurevalue, $measuredatecompleted, $measurerater, $measureinstrument);
			DisplayMeasureList($enrollmentid);
			break;
		case 'deletemeasure':
			DeleteMeasure($measureid);
			DisplayMeasureList($enrollmentid);
			break;
		default:
			DisplayMeasureList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddMeasure ------------------------- */
	/* -------------------------------------------- */
	function AddMeasure($enrollmentid, $measurename, $measurevalue, $measuredatecompleted, $measurerater, $measureinstrument) {
		$measurename = mysqli_real_escape_string($GLOBALS['linki'], $measurename);
		$measurevalue = mysqli_real_escape_string($GLOBALS['linki'], $measurevalue);
		$measuredatecompleted = mysqli_real_escape_string($GLOBALS['linki'], $measuredatecompleted);
		$measurerater = mysqli_real_escape_string($GLOBALS['linki'], $measurerater);
		$measureinstrument = mysqli_real_escape_string($GLOBALS['linki'], $measureinstrument);

		if (is_numeric($measurevalue)) {
			$measuretype = 'n';
			$valuestring = '';
			$valuenum = $measurevalue;
		}
		else {
			$measuretype = 's';
			$valuestring = $measurevalue;
			$valuenum = '';
		}
		
		$sqlstringA = "select measurename_id from measurenames where measure_name = '$measurename'";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		if (mysqli_num_rows($resultA) > 0) {
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$measurenameid = $rowA['measurename_id'];
		}
		else {
			$sqlstringA = "insert into measurenames (measure_name) values ('$measurename')";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$measurenameid = mysqli_insert_id($GLOBALS['linki']);
		}
		if (trim($measurerater) == "") $measurerater = "null";
		else $measurerater = "'" . trim($measurerater) . "'";

		if (trim($measureinstrument) == "") $measureinstrument = "null";
		else $measureinstrument = "'" . trim($measureinstrument) . "'";
		
		if (trim($measuredatecompleted) == "") $measuredatecompleted = "null";
		else $measuredatecompleted = "'" . trim($measuredatecompleted) . "'";
		
		$sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered, measurename_id, measure_type, measure_valuestring, measure_valuenum, measure_rater, measure_instrument, measure_datecomplete) values ($enrollmentid, now(), $measurenameid, '$measuretype', '$valuestring', '$valuenum', $measurerater, $measureinstrument, $measuredatecompleted)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- DeleteMeasure ---------------------- */
	/* -------------------------------------------- */
	function DeleteMeasure($id) {
		$sqlstring = "delete from measures where measure_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Measure deleted");
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayMeasureList ----------------- */
	/* -------------------------------------------- */
	function DisplayMeasureList($enrollmentid) {
		
		if ((trim($enrollmentid) == "") || ($enrollment_id < 0)) {
			?>Invalid or blank enrollment ID [<?=$enrollment_id?>]<?
			return;
		}
		
		/* get subject's info for the breadcrumb list */
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		
		//$urllist['Subject List'] = "subjects.php";
		//$urllist[$uid] = "subjects.php?action=display&id=$subjectid";
		//$urllist["$projectname Measures"] = "measures.php?enrollmentid=$enrollmentid";
		//NavigationBar("Phenotypic measures", $urllist);
		
		?>
		<script>
			$(document).ready(function(){
				$('#pageloading').hide();
				//$('#measuretable').tablesort();
			});
		</script>

		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/se/dt-1.10.24/datatables.min.css"/>
		<script type="text/javascript" src="https://cdn.datatables.net/v/se/dt-1.10.24/datatables.min.js"></script>
		<script>
			$(document).ready(function() {
				$('#measuretable').DataTable( {"pageLength": 25} );
			} );		
		</script>
		
		<div class="ui text container" id="pageloading">
			<div class="ui inverted segment" align="center">
				<h2 class="ui inverted header">
					<i class="spinner loading icon"></i> Loading...
				</h2>
			</div>
		</div>
		
		<div class="ui segment">		
			<form action="measures.php" method="post" class="ui form">
				<input type="hidden" name="action" value="addmeasure">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				
				<div class="eight fields">
					<div class="field">
						<label>Measure name</label>
						<input type="text" name="measure_name" size="15" required>
					</div>
					<div class="field">
						<label>Instrument</label>
						<input type="text" name="measure_instrument" size="15">
					</div>
					<div class="field">
						<label>Value</label>
						<input type="text" name="measure_value" size="10" required>
					</div>
					<div class="field">
						<label>Rater</label>
						<input type="text" name="measure_rater" size="15" value="<?=$GLOBALS['username']?>">
					</div>
					<div class="field">
						<label>Start date</label>
						<input type="datetime-local" name="measure_startdate">
					</div>
					<div class="field">
						<label>Duration</label>
						<input type="text" name="measure_duration" size="10">
					</div>
					<div class="field">
						<label>End date</label>
						<input type="datetime-local" name="measure_enddate">
					</div>
					<div class="field">
						<label>.</label>
						<input type="submit" class="ui primary button" value="Add Measure">
					</div>
				</div>
				
			</form>
		</div>
		
		<!--<div class="ui top attached header">
			Table is sortable &nbsp; <span style="font-size: smaller; font-weight: normal">(Sorting may be slow with large table)</span>
		</div>-->
		<table class="ui celled very compact small grey sortable selectable bottom attached table" id="measuretable">
			<thead>
				<tr>
					<th>Variable
					<!--
						<div class="ui icon input">
							<input id="variablenamefilter" class="ui small input" type="text" placeholder="Filter by variable"/>
							<i class="search icon"></i>
						</div>

						<script type="text/javascript">
							function filterTable(event) {
								var filter = event.target.value.toUpperCase();
								var rows = document.querySelector("#measuretable tbody").rows;
								
								for (var i = 0; i < rows.length; i++) {
									var firstCol = rows[i].cells[0].textContent.toUpperCase();
									if (firstCol.indexOf(filter) > -1) {
										rows[i].style.display = "";
									} else {
										rows[i].style.display = "none";
									}      
								}
							}

							document.querySelector('#variablenamefilter').addEventListener('keyup', filterTable, false);
						</script>-->
					</th>
					<th>Instrument</th>
					<th>Value</th>
					<!--<th>Notes</th>-->
					<th>Series</th>
					<th>Rater</th>
					<th>Start date</th>
					<th>Duration</th>
					<th>End date</th>
					<th style="border-right: 1px solid #666">Delete</th>
					<th title="<b>Entry</b> Date the value was entered into this database<br><b>Create</b> Date the record was created in this database<br><b>Modify</b> Date the record was modified in this database">Database record dates<i class="question circle icon"></i></th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select a.*, b.*, c.*, e.uid from measures a left join measurenames b on a.measurename_id = b.measurename_id left join measureinstruments c on a.instrumentname_id = c.measureinstrument_id left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.enrollment_id = $enrollmentid order by b.measure_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$measureid = $row['measure_id'];
						$studyid = $row['study_id'];
						$seriesid = $row['series_id'];
						$uid = $row['uid'];
						$measure_name = $row['measure_name'];
						$instrument_name = $row['instrument_name'];
						$measure_value = $row['measure_value'];
						//$measure_notes = $row['measure_notes'];
						$measure_rater = $row['measure_rater'];
						$measure_startdate = $row['measure_startdate'];
						$measure_enddate = $row['measure_enddate'];
						$measure_duration = $row['measure_duration'];
						$measure_entrydate = $row['measure_entrydate'];
						$measure_createdate = $row['measure_createdate'];
						$measure_modifydate = $row['measure_modifydate'];
						
						if ($studyid != "") {
							$sqlstringA = "select study_num, study_modality from studies where study_id = $studyid";
							$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
							$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
							$studynum = $rowA['study_num'];
							$modality = strtolower($rowA['study_modality']);

							$sqlstringB = "select series_num from $modality" . "_series where $modality" . "series_id = $seriesid";
							$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
							$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
							$seriesnum = $rowB['series_num'];
							
							$series = "$uid$studynum-$seriesnum ($modality)";
						}
						else {
							$series = "";
						}
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$(".edit_inline<? echo $measureid; ?>").editInPlace({
									url: "measure_inlineupdate.php",
									params: "action=editinplace&id=<? echo $series_id; ?>",
									default_text: "<i style='color:#AAAAAA'>Edit here...</i>",
									bg_over: "white",
									bg_out: "lightyellow",
								});
							});
						</script>
						<tr>
							<td><?=$measure_name?></td>
							<td><?=$instrument_name?></td>
							<td><?=$measure_value?></td>
							<!--<td><?=$measure_notes?></td>-->
							<td><?=$series?></td>
							<td style="font-size: 9pt"><?=$measure_rater?></td>
							<td><?=$measure_startdate?></td>
							<td><?=$measure_duration?></td>
							<td><?=$measure_enddate?></td>
							<td class="center aligned" style="border-right: 1px solid #666">
								<a href="measures.php?action=deletemeasure&measureid=<?=$measureid?>&enrollmentid=<?=$enrollmentid?>" title="Delete this variable" onClick="return confirm('Are you sure you want to delete this record?')"><i class="red alternate trash icon"></i></a>
							</td>
							<td title="<b>Entry</b> <?=$measure_entrydate?><br><b>Create</b> <?=$measure_createdate?><br><b>Modify</b> <?=$measure_modifydate?>">
							<i class="calendar alternate outline icon"></i>
							</td>
						</tr>
					<?
					}
					?>
			</tbody>
		</table>
		<?
	}
?>


<? include("footer.php") ?>
