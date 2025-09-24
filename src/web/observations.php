<?
 // ------------------------------------------------------------------------------
 // NiDB observations.php
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
		<title>NiDB - Phenotypic observations</title>
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
	$observationid = GetVariable("observationid");
	$enrollmentid = GetVariable("enrollmentid");
	$observationname = GetVariable('observation_name');
	$observationinstrument = GetVariable('observation_instrument');
	$observationvalue = GetVariable('observation_value');
	$observationdatecompleted = GetVariable('observation_datecompleted');
	$observationrater = GetVariable('observation_rater');
						
	/* determine action */
	switch ($action) {
		case 'addobservation':
			AddObservation($enrollmentid, $observationname, $observationvalue, $observationdatecompleted, $observationrater, $observationinstrument);
			DisplayObservationList($enrollmentid);
			break;
		case 'deleteobservation':
			DeleteObservation($observationid);
			DisplayObservationList($enrollmentid);
			break;
		default:
			DisplayObservationList($enrollmentid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddObservation --------------------- */
	/* -------------------------------------------- */
	function AddObservation($enrollmentid, $observationname, $observationvalue, $observationdatecompleted, $observationrater, $observationinstrument) {
		$observationname = mysqli_real_escape_string($GLOBALS['linki'], $observationname);
		$observationvalue = mysqli_real_escape_string($GLOBALS['linki'], $observationvalue);
		$observationdatecompleted = mysqli_real_escape_string($GLOBALS['linki'], $observationdatecompleted);
		$observationrater = mysqli_real_escape_string($GLOBALS['linki'], $observationrater);
		$observationinstrument = mysqli_real_escape_string($GLOBALS['linki'], $observationinstrument);

		//if (is_numeric($observationvalue)) {
		//	$observationtype = 'n';
		//	$valuestring = '';
		//	$valuenum = $observationvalue;
		//}
		//else {
		//	$observationtype = 's';
		//	$valuestring = $observationvalue;
		//	$valuenum = '';
		//}
		
		//$sqlstringA = "select observationname_id from observationnames where observation_name = '$observationname'";
		//$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		//if (mysqli_num_rows($resultA) > 0) {
		//	$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
		//	$observationnameid = $rowA['observationname_id'];
		//}
		//else {
		//	$sqlstringA = "insert into observationnames (observation_name) values ('$observationname')";
		//	$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
		//	$observationnameid = mysqli_insert_id($GLOBALS['linki']);
		//}
		if (trim($observationrater) == "") $observationrater = "null";
		else $observationrater = "'" . trim($observationrater) . "'";

		if (trim($observationinstrument) == "") $observationinstrument = "null";
		else $observationinstrument = "'" . trim($observationinstrument) . "'";
		
		if (trim($observationdatecompleted) == "") $observationdatecompleted = "null";
		else $observationdatecompleted = "'" . trim($observationdatecompleted) . "'";
		
		$sqlstring = "insert ignore into observations (enrollment_id, observation_entrydate, observation_name, observation_value, observation_rater, observation_instrument, observation_enddate) values ($enrollmentid, now(), $observationname, '$observationvalue', $observationrater, $observationinstrument, $observationdatecompleted)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- DeleteObservation ------------------ */
	/* -------------------------------------------- */
	function DeleteObservation($id) {
		$sqlstring = "delete from observations where observation_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Observation deleted");
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayObservationList ----------------- */
	/* -------------------------------------------- */
	function DisplayObservationList($enrollmentid) {
		
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
		//$urllist["$projectname Observations"] = "observations.php?enrollmentid=$enrollmentid";
		//NavigationBar("Phenotypic observations", $urllist);
		
		?>
		<script>
			$(document).ready(function(){
				$('#pageloading').hide();
				//$('#observationtable').tablesort();
			});
		</script>

		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/se/dt-1.10.24/datatables.min.css"/>
		<script type="text/javascript" src="https://cdn.datatables.net/v/se/dt-1.10.24/datatables.min.js"></script>
		<script>
			$(document).ready(function() {
				$('#observationtable').DataTable( {"pageLength": 25} );
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
			<form action="observations.php" method="post" class="ui form">
				<input type="hidden" name="action" value="addobservation">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				
				<div class="eight fields">
					<div class="field">
						<label>Observation name</label>
						<input type="text" name="observation_name" size="15" required>
					</div>
					<div class="field">
						<label>Instrument</label>
						<input type="text" name="observation_instrument" size="15">
					</div>
					<div class="field">
						<label>Value</label>
						<input type="text" name="observation_value" size="10" required>
					</div>
					<div class="field">
						<label>Rater</label>
						<input type="text" name="observation_rater" size="15" value="<?=$GLOBALS['username']?>">
					</div>
					<div class="field">
						<label>Start date</label>
						<input type="datetime-local" name="observation_startdate">
					</div>
					<div class="field">
						<label>Duration</label>
						<input type="text" name="observation_duration" size="10">
					</div>
					<div class="field">
						<label>End date</label>
						<input type="datetime-local" name="observation_enddate">
					</div>
					<div class="field">
						<label>.</label>
						<input type="submit" class="ui primary button" value="Add Observation">
					</div>
				</div>
				
			</form>
		</div>
		
		<!--<div class="ui top attached header">
			Table is sortable &nbsp; <span style="font-size: smaller; font-weight: normal">(Sorting may be slow with large table)</span>
		</div>-->
		<table class="ui celled very compact small grey sortable selectable bottom attached table" id="observationtable">
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
								var rows = document.querySelector("#observationtable tbody").rows;
								
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
					$sqlstring = "select a.*, e.uid from observations a left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.enrollment_id = $enrollmentid order by a.observation_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$observationid = $row['observation_id'];
						$studyid = $row['study_id'];
						$seriesid = $row['series_id'];
						$uid = $row['uid'];
						$observation_name = $row['observation_name'];
						$instrument_name = $row['instrument_name'];
						$observation_value = $row['observation_value'];
						//$observation_notes = $row['observation_notes'];
						$observation_rater = $row['observation_rater'];
						$observation_startdate = $row['observation_startdate'];
						$observation_enddate = $row['observation_enddate'];
						$observation_duration = $row['observation_duration'];
						$observation_entrydate = $row['observation_entrydate'];
						$observation_createdate = $row['observation_createdate'];
						$observation_modifydate = $row['observation_modifydate'];
						
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
								$(".edit_inline<? echo $observationid; ?>").editInPlace({
									url: "observation_inlineupdate.php",
									params: "action=editinplace&id=<? echo $series_id; ?>",
									default_text: "<i style='color:#AAAAAA'>Edit here...</i>",
									bg_over: "white",
									bg_out: "lightyellow",
								});
							});
						</script>
						<tr>
							<td><?=$observation_name?></td>
							<td><?=$instrument_name?></td>
							<td><?=$observation_value?></td>
							<!--<td><?=$observation_notes?></td>-->
							<td><?=$series?></td>
							<td style="font-size: 9pt"><?=$observation_rater?></td>
							<td><?=$observation_startdate?></td>
							<td><?=$observation_duration?></td>
							<td><?=$observation_enddate?></td>
							<td class="center aligned" style="border-right: 1px solid #666">
								<a href="observations.php?action=deleteobservation&observationid=<?=$observationid?>&enrollmentid=<?=$enrollmentid?>" title="Delete this variable" onClick="return confirm('Are you sure you want to delete this record?')"><i class="red alternate trash icon"></i></a>
							</td>
							<td title="<b>Entry</b> <?=$observation_entrydate?><br><b>Create</b> <?=$observation_createdate?><br><b>Modify</b> <?=$observation_modifydate?>">
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
