<?
 // ------------------------------------------------------------------------------
 // NiDB importnonimaging.php
 // Copyright (C) 2004 - 2025
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

	/* these operations could take a long time, so extend the execution timeout */
	set_time_limit(600);

	define("LEGIT_REQUEST", true);

	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Import Non-imaging Data</title>
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
	//PrintVariable($_FILES);
	
	$username = $_SESSION['username'];
	$instanceid = $_SESSION['instanceid'];
	session_write_close();
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$csv = GetVariable("csv");
	$skipblankvalue = GetVariable("skipblankvalue");
	$createmissingsubject = GetVariable("createmissingsubject");
	
	/* determine action */
	switch ($action) {
		case 'newimportform':
			DisplayNewImportForm($username, $instanceid);
			break;
		case 'newimport':
			NewImportCheck($csv, $projectid, $skipblankvalue, $createmissingsubject);
			break;
		case 'submitnewimport':
			SubmitNewImport($csv, $projectid, $skipblankvalue, $createmissingsubject);
			break;
		case 'displayimportlist':
			DisplayImportList($displayall);
			break;
		case 'displayimport':
			DisplayImport($uploadid);
			break;
		default:
			DisplayImportList($displayall);
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayNewImportForm --------------- */
	/* -------------------------------------------- */
	function DisplayNewImportForm($username, $instanceid) {
		?>
		<div class="ui container">
			<div class="ui top attached grey segment">
				<h2 class="ui header">New Import</h2>
			</div>
			<form method="post" action="importnonimaging.php" name="importform" enctype="multipart/form-data" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="newimport">
				<div class="ui grid">

					<div class="three wide column"><h3 class="ui grey right aligned header">CSV file(s)</h3></div>
					<div class="thirteen wide column">
						<div class="ui file input">
							<input type="file" name="files[]" multiple>
						</div>
					</div>
					<div class="three wide column"><h3 class="ui grey right aligned header">CSV formatted text</h3></div>
					<div class="thirteen wide column">
						<textarea name="csv"></textarea>
						<div class="ui accordion">
							<div class="title">
								<i class="dropdown icon"></i>
								Example CSV format
							</div>
							<div class="content">
								<p>CSV must have a header, with the following possible column names</p>
								<div class="ui raised segment">
									<tt><u>id</u>, type, name, instrument, value, notes, description, rater, startdate, enddate, duration, entrydate, createdate, modifydate</tt>
								</div>
								Notes
								<ul>
									<li>Columns can be in any order
									<li><code>id</code> is required for each line and must be unique to the specified project. NiDB <u>UID</u> or <u>AltUID</u> may be used.
									<li><code>type</code> is either <code>observation</code> or <code>intervention</code>. Default is <code>observation</code>.
									<li>Strings must be "quoted"
									<li>Dates must be in format <code>YYYY-MM-DD</code> or <code>MM/DD/YYYY</code>
									<li>Datetimes must be in format <code>YYYY-MM-DD HH:Mi:SS</code> or <code>MM/DD/YYYY HH:Mi:SS</code>
									<li>Columns that are <i>not</i> one of the column keywords are assumed to be a key/value pair: the column is the observation/intervention name and the cell is the value
								</ul>
							</div>
						</div>
					</div>
					
					<div class="three wide column"><h3 class="ui grey right aligned header">Destination Project</h3></div>
					<div class="thirteen wide column">
						<select name="projectid" required>
							<option value="">Select project...</option>
							<?
								$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '$username') and a.instance_id = '$instanceid' order by project_name";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$project_id = $row['project_id'];
									$project_name = $row['project_name'];
									$project_costcenter = $row['project_costcenter'];
									?>
									<option value="<?=$project_id?>"><?=$project_name?> (<?=$project_costcenter?>)</option>
									<?
								}
							?>
						</select>
					</div>
					
					<div class="three wide column"><h3 class="ui grey right aligned header">Options</h3></div>
					<div class="thirteen wide column">
						<div class="ui checkbox">
							<input type="checkbox" name="skipblankvalue" value="1">
							<label>Ignore blank values <i class="question circle icon" title="If checked, blank cells will be ignored and no observation/intervention added to NiDB. If un-checked, then a blank value will be inserted into NiDB for that observation/intervention"></i></label>
						</div>
						<br>
						<div class="ui checkbox">
							<input type="checkbox" name="createmissingsubject" value="1">
							<label>Create missing subjects <i class="question circle icon" title="If a subject ID or UID is not found within NiDB, then create the subject. Demographic data will listed as blank and will need to be edited after import"></i></label>
						</div>
					</div>
				</div>

				<br>
				<div style="text-align: right">
					<a href="importnonimaging.php" class="ui button">Cancel</a>
					<input type="submit" class="ui primary button" value="Import" onClick="this.disabled = true; this.value = 'Submitting...'; importform.submit();">
				</div>
			
			</form>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- NewImportCheck --------------------- */
	/* -------------------------------------------- */
	function NewImportCheck($csv, $projectid, $skipblankvalue, $createmissingsubject) {
		
		/* prepare fields for SQL */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$skipblankvalue = mysqli_real_escape_string($GLOBALS['linki'], $skipblankvalue);
		$createmissingsubject = mysqli_real_escape_string($GLOBALS['linki'], $createmissingsubject);

		if ($projectid == "") {
			Error("Project was blank. Please go back and select a project");
			return;
		}
		
		if ($skipblankvalue == 1) { $skipblankvalue = true; } else { $skipblankvalue = false; }
		if ($createmissingsubject == 1) { $createmissingsubject = true; } else { $createmissingsubject = false; }
		
		$csvs = array();
		if ($csv != "") {
			$csvs['Pasted'] = $csv;
		}
		foreach ($_FILES['files']['name'] as $i => $name) {
			$csvs[$name] = file_get_contents($_FILES['files']['tmp_name'][$i]);
		}
		
		/* run through all of the CSV files and CSV pasted text */
		foreach ($csvs as $name => $csv) {
			
			$csvdata = ParseCSV(trim($csv));
			$rows = count($csvdata);
			$cols = count(array_keys($csvdata[0]));
			$cells = count($csvdata, COUNT_RECURSIVE);
			?>
			<div class="ui segment">
				<p>CSV [<?=$name?>] contains <?=$rows?> rows x <?=$cols?> columns = <?=$cells?> cells</p>
			<?
			$colsAsVariables = false;
			
			foreach ($csvdata as $line) {
				/* reset all variables for this row */
				$keys = array();
				$id = "";
				$name = "";
				$value = "";
				$type = "";
				$instrument = "";
				$notes = "";
				$description = "";
				$rater = "";
				$startdate = "";
				$enddate = "";
				$duration = "";
				$entrydate = "";
				$createdate = "";
				$modifydate = "";
				foreach ($line as $key => $val) {
					if (strtolower($key) == "id") { $id = $val; }
					elseif (strtolower($key) == "name") { $name = $val; }
					elseif (strtolower($key) == "value") { $value = $val; }
					elseif (strtolower($key) == "type") { $type = $val; }
					elseif (strtolower($key) == "instrument") { $instrument = $val; }
					elseif (strtolower($key) == "notes") { $notes = $val; }
					elseif (strtolower($key) == "description") { $description = $val; }
					elseif (strtolower($key) == "rater") { $rater = $val; }
					elseif (strtolower($key) == "startdate") {
						$startdate = date('Y-m-d H:i:s', strtotime($val));
					}
					elseif (strtolower($key) == "enddate") {
						$enddate = date('Y-m-d H:i:s', strtotime($val));
					}
					elseif (strtolower($key) == "duration") { $duration = $val; }
					elseif (strtolower($key) == "entrydate") {
						$entrydate = date('Y-m-d H:i:s', strtotime($val));
					}
					elseif (strtolower($key) == "createdate") {
						$createdate = date('Y-m-d H:i:s', strtotime($val));
					}
					elseif (strtolower($key) == "modifydate") {
						$modifydate = date('Y-m-d H:i:s', strtotime($val));
					}
					else {
						/* not one of the keywords, so it must be a key/value pair */
						$keys[$key] = $val;
						$colsAsVariables = true;
					}
				}
				/* fix any unknown data */
				if ($type == "") { $type = "observation"; }
				if ($startdate == "") { $startdate = "1900-01-01"; }
				
				/* if there is a name/value in this row */
				if ($name != "") {
					if ($value == "") {
						if (!$skipblankvalue) {
							$data[$id][$type][$name][$startdate]['value'] = "";
							$data[$id][$type][$name][$startdate]['instrument'] = $instrument;
							$data[$id][$type][$name][$startdate]['notes'] = $notes;
							$data[$id][$type][$name][$startdate]['description'] = $description;
							$data[$id][$type][$name][$startdate]['rater'] = $rater;
							$data[$id][$type][$name][$startdate]['enddate'] = $enddate;
							$data[$id][$type][$name][$startdate]['duration'] = $duration;
							$data[$id][$type][$name][$startdate]['entrydate'] = $entrydate;
							$data[$id][$type][$name][$startdate]['createdate'] = $createdate;
							$data[$id][$type][$name][$startdate]['modifydate'] = $modifydate;
						}
					}
					else {
						$data[$id][$type][$name][$startdate]['value'] = $val;
						$data[$id][$type][$name][$startdate]['instrument'] = $instrument;
						$data[$id][$type][$name][$startdate]['notes'] = $notes;
						$data[$id][$type][$name][$startdate]['description'] = $description;
						$data[$id][$type][$name][$startdate]['rater'] = $rater;
						$data[$id][$type][$name][$startdate]['enddate'] = $enddate;
						$data[$id][$type][$name][$startdate]['duration'] = $duration;
						$data[$id][$type][$name][$startdate]['entrydate'] = $entrydate;
						$data[$id][$type][$name][$startdate]['createdate'] = $createdate;
						$data[$id][$type][$name][$startdate]['modifydate'] = $modifydate;
					}
				}
				
				/* if there are non-keyword columns */
				if (count($keys) > 0) {
					foreach ($keys as $key => $val) {
						$data[$id][$type][$key][$startdate]['value'] = $val;
						$data[$id][$type][$key][$startdate]['instrument'] = $instrument;
						$data[$id][$type][$key][$startdate]['notes'] = $notes;
						$data[$id][$type][$key][$startdate]['description'] = $description;
						$data[$id][$type][$key][$startdate]['rater'] = $rater;
						$data[$id][$type][$key][$startdate]['enddate'] = $enddate;
						$data[$id][$type][$key][$startdate]['duration'] = $duration;
						$data[$id][$type][$key][$startdate]['entrydate'] = $entrydate;
						$data[$id][$type][$key][$startdate]['createdate'] = $createdate;
						$data[$id][$type][$key][$startdate]['modifydate'] = $modifydate;
					}
				}
			}
			/* determine the type of csv we are parsing */
			if ($colsAsVariables) {
				$csvtype = "CSV format appears to be wide format (each row is a single subject, with multiple columns as variables)";
			}
			else {
				$csvtype = "CSV format appears to be long format (each row is a variable)";
			}
			
			?>
			<p><?=$csvtype?></p>
			</div>
			<?
		}

		?>
		
		<!-- Include the JS for AG Grid -->
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<?
		
		$ids = array();
		$rowdata = array();
		$colCount = 0;
		foreach ($data as $id => $data1) {
			$subjectRowID = null;
			$uid = "";
			/* lookup this ID to get a subjectRowID */
			if (isset($ids[$id])) {
				$subjectRowID = $ids[$id]['subjectRowID'];
				$uid = $ids[$id]['uid'];
			}
			else {
				/* try to find the subject by ID */
				list ($subjectRowID, $uid) = GetSubjectRowIDByProject($id, $projectid);
				$ids[$id]['subjectRowID'] = $subjectRowID;
				$ids[$id]['uid'] = $subjectRowID;
			}
			
			foreach ($data1 as $type => $data2) {
				foreach ($data2 as $variable => $data3) {
					foreach ($data3 as $startdate => $val) {
						$value = $val['value'];
						$value = str_replace("\"","",$value);
						$value = str_replace("'","",$value);

						$instrument = $val['instrument'];
						$notes = $val['notes'];
						$description = $val['description'];
						$rater = $val['rater'];
						$enddate = $val['enddate'];
						$duration = $val['duration'];
						$entrydate = $val['entrydate'];
						$createdate = $val['createdate'];
						$modifydate = $val['modifydate'];
						
						$rowdata[] = "{ subjectrowid: \"$subjectRowID\", id: \"$id\", uid: \"$uid\", type: \"$type\", variable: \"$variable\", startdate: \"$startdate\", value: \"$value\", instrument: \"$instrument\", notes: \"$notes\", description: \"$description\", rater: \"$rater\", enddate: \"$enddate\", duration: \"$duration\", entrydate: \"$entrydate\", createdate: \"$createdate\", modifydate: \"$modifydate\" }";
					}
				}
			}
		}
		$datatable = "";
		$totalrows = count($rowdata);
		if ($totalrows > 0) {
			$datatable = implode(",", $rowdata);
		}
		?>
		<p>Found <?=$totalrows?> <i>unique</i> data items to be imported.<br>Review data and click <b>Submit</b></p>
		
		<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
		<script type="text/javascript">
			let gridApi;

			$(document).ready(function(){
				$('#batchsubjectupdatebutton').click(function(){
					$('#batchmodal').modal('show');
				});
			});
			
			// Function to demonstrate calling grid's API
			function deselect(){
				gridOptions.api.deselectAll()
			}
			
			function onBtnExport() {
				gridApi.exportDataAsCsv( {allColumns: false} );
			}			

			// Grid Options are properties passed to the grid
			const gridOptions = {
				theme: agGrid.themeBalham,

				// each entry here represents one column
				columnDefs: [
					{ headerName: "subjectRowID", field: "subjectrowid" },
					{ headerName: "ID", field: "id" },
					{ headerName: "UID", field: "uid" },
					{ headerName: "Type", field: "type" },
					{ headerName: "Variable", field: "variable" },
					{ headerName: "StartDate", field: "startdate", cellDataType: 'dateTimeString' },
					{ headerName: "Value", field: "value" },
					{ headerName: "Instrument", field: "instrument" },
					{ headerName: "Notes", field: "notes" },
					{ headerName: "Description", field: "description" },
					{ headerName: "Rater", field: "rater" },
					{ headerName: "EndDate", field: "enddate", cellDataType: 'dateTimeString' },
					{ headerName: "Duration", field: "duration" },
					{ headerName: "EntryDate", field: "entrydate", cellDataType: 'dateTimeString' },
					{ headerName: "CreateDate", field: "createdate", cellDataType: 'dateTimeString' },
					{ headerName: "ModifyDate", field: "modifydate", cellDataType: 'dateTimeString' }
				],

				rowData: [ <?=$datatable?> ],
				
				// default col def properties get applied to all columns
				defaultColDef: {sortable: true, filter: true, resizable: true, editable: false},
				autoSizeStrategy: {
					type: "fitGridWidth",
				},
				animateRows: false, // have rows animate to new positions when sorted
				suppressMovableColumns: true,
			};

			$( document ).ready(function() {
				// get div to host the grid
				const eGridDiv = document.getElementById("myGrid");
				// new grid instance, passing in the hosting DIV and Grid Options
				gridApi = agGrid.createGrid(eGridDiv, gridOptions);
				
			});

		</script>
		
		<!-- submission section -->
		<div class="ui segment">
			<div class="ui grid">
				<div class="two wide column">
					<form method="post" action="importnonimaging.php" name="importform" enctype="multipart/form-data">
						<input type="hidden" name="action" value="submitnewimport">
						<input type="hidden" name="projectid" value="<?=$projectid?>">
						<input type="hidden" name="skipblankvalue" value="<?=(int)$skipblankvalue?>">
						<input type="hidden" name="createmissingsubject" value="<?=(int)$createmissingsubject?>">
						<input type="hidden" name="csv" value="">
						
						<input type="submit" class="ui primary button" onClick="this.disabled = true; this.value = 'Submitting...'; importform.csv.value = gridApi.getDataAsCsv(); importform.submit();";>
					</form>
				</div>
				<div class="fourteen wide column">
					<div class="ui warning message">
						<div class="header">
							After clicking Submit, you may see a <b>Gateway timeout error</b>
						</div>
						
						<p>This can happen when importing very large files (more than 100,000 variables).<br>The webpage may show an error, but the data is still importing in the background. Don't click the back button; instead type the NiDB address in the searchbar again</p>
					</div>
				</div>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- SubmitNewImport -------------------- */
	/* -------------------------------------------- */
	function SubmitNewImport($csv, $projectid, $skipblankvalue, $createmissingsubject) {

		$startTime = microtime(true);
		
		/* prepare fields for SQL */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$skipblankvalue = mysqli_real_escape_string($GLOBALS['linki'], $skipblankvalue);
		$createmissingsubject = mysqli_real_escape_string($GLOBALS['linki'], $createmissingsubject);
		
		if ($skipblankvalue == 1) { $skipblankvalue = true; } else { $skipblankvalue = false; }
		if ($createmissingsubject == 1) { $createmissingsubject = true; } else { $createmissingsubject = false; }
		
		?>
		<div class="ui segment">
			<h2 class="ui header">
				Import options
			</h2>
			<ul>
				<li>Import empty cells <? if ($skipblankvalue) { echo "<i class='gray large toggle off icon' title='Cells with empty values will not be imported'></i>"; } else { echo "<i class='green large toggle on icon' title='Empty cells will be imported as blank values'></i>"; } ?>
				<li>Create missing subjects <? if ($createmissingsubject) { echo "<i class='green large toggle on icon' title='Missing subjects will be created'></i>"; } else { echo "<i class='gray large toggle off icon' title='Missing subjects will not be created'></i>"; } ?>
			</ul>
		</div>
		<?
		$observationNameCache = array();
		$interventionNameCache = array();
		$enrollmentRowIDCache = array();
		$subjectRowIDCache = array();

		$numObservationsAdded = 0;
		$numInterventionsAdded = 0;
		$numObservationsIgnored = 0;
		$numInterventionsIgnored = 0;
		$subjectsNotFound = array();
		$subjectsCreated = array();
		
		//PrintVariable($csv);
		$csvdata = ParseCSV(trim($csv));
		//PrintVariable($csvdata);
		$rows = count($csvdata);
		$cols = count(array_keys($csvdata[0]));
		$cells = count($csvdata, COUNT_RECURSIVE);
		?>
		<div class="ui segment">
			<h2 class="ui header">
				Data characteristics
			</h2>
			<p>Submitted dataset contains <?=number_format($rows)?> rows x <?=number_format($cols)?> columns = <?=number_format($cells)?> cells</p>
			<p>Importing <?=number_format($rows)?> data items</p>
		</div>
		<!--<tt>
		<pre>-->
		<?
		//return;

		$i=0;
		$inserts = array();

		foreach ($csvdata as $line) {
			/* reset all variables for this row */
			$subjectRowID = $line['subjectRowID'];
			$uid = $line['UID'];
			$id = $line['ID'];
			$variablename = $line['Variable'];
			$value = $line['Value'];
			$type = $line['Type'];
			$instrument = $line['Instrument'];
			$notes = $line['Notes'];
			$description = $line['Description'];
			$rater = $line['Rater'];
			$startdate = $line['StartDate'];
			$enddate = $line['EndDate'];
			$duration = $line['Duration'];
			$entrydate = $line['EntryDate'];
			$createdate = $line['CreateDate'];
			$modifydate = $line['ModifyDate'];
			
			if ($subjectRowID == "") {
				//echo "A) subjectRowID was blank for [$id]\n";
				
				/* check the cache for a subjectRowID */
				if (isset($subjectRowIDCache[$id])) {
					$subjectRowID = $subjectRowIDCache[$id];
					//echo "B) subjectRowID exists in cache. ID [$id] = subjectRowID [$subjectRowID]\n";
				}
				else {
					//echo "C) subjectRowID for ID [$id] did not exist in cache\n";
					/* try to get the subjectRowID by searching for the ID */
					list ($subjectRowID, $uid) = GetSubjectRowIDByProject($id, $projectid);
					$subjectRowIDCache[$id] = $subjectRowID;
					//echo "D) subjectRowID now exists in cache. ID $id = subjectRowID $subjectRowID\n";
				}
				
				if ($createmissingsubject == 1) {
					if ($subjectRowID == "") {
						//echo "E) subjectRowID for [$id] is still blank, creating subject...\n";
						
						/* create a subject */
						list($subjectRowID, $uid) = AddSubject($id, '1900-01-01', 'U', $id);
						$subjectRowIDCache[$id] = $subjectRowID;

						//echo "F) Created subject [$subjectRowID] for ID [$id]\n";
						
						/* enroll the subject in this project */
						$enrollmentRowID = EnrollSubject($subjectRowID, $projectid);
						$enrollmentRowIDCache[$subjectRowID] = $enrollmentRowID;

						//echo "G) Created enrollment [$enrollmentRowID] for subject [$subjectRowID] in project [$projectid]\n";
						
						$subjectsCreated[$id] = 1;
					}
				}
				else {
					//echo "createmissingsubject [$createmissingsubject] , subjectRowID [$subjectRowID]\n";
					$subjectsNotFound[$id] = 1;
				
					if ($type == "observation") {
						$numObservationsIgnored++;
					}
					else {
						$numInterventionsIgnored++;
					}
					continue;
				}
			}
			else {
				//echo "H) subjectRowID [$subjectRowID] was already in the csv\n";
			}

			/* get the enrollmentRowID (check the cache first) */
			if (isset($enrollmentRowIDCache[$subjectRowID])) {
				$enrollmentRowID = $enrollmentRowIDCache[$subjectRowID];
			}
			else {
				$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					/* add the enrollmentRowID to the cache */
					$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
					$enrollmentRowID = $row['enrollment_id'];
					$enrollmentRowIDCache[$subjectRowID] = $enrollmentRowID;
				}
				else {
					/* enroll the subject in this project (and add the enrollmentRowID to the cache) */
					$enrollmentRowID = EnrollSubject($subjectRowID, $projectid);
					$enrollmentRowIDCache[$subjectRowID] = $enrollmentRowID;
					$subjectsCreated[$id] = 1;
				}
			}

			if ($type == "observation") {
				/* fix any missing fields */
				if (trim($rater) == "") $rater = "null";
				else $rater = "'" . trim($rater) . "'";

				if (trim($instrument) == "") $instrument = "null";
				else $instrument = "'" . trim($instrument) . "'";
				
				if (trim($enddate) == "") $enddate = "null";
				else $enddate = "'" . trim($enddate) . "'";

				if (trim($startdate) == "") $startdate = "null";
				else $startdate = "'" . trim($startdate) . "'";
				
				/* add to batch insert */
				$inserts[] = "($enrollmentRowID, now(), '$variablename', '$value', $rater, $instrument, $startdate, $enddate)";
				$numObservationsAdded++;
				
				if (count($inserts) >= 100) {
					$sqlstring = "insert ignore into observations (enrollment_id, observation_entrydate, observation_name, observation_value, observation_rater, observation_instrument, observation_startdate, observation_enddate) values " . implode(",", $inserts);
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$inserts = array();
				}
			}
			
			if ($type == "intervention") {
				$numInterventionsAdded++;
			}
			
			//$i++;
			//if ($i > 1000)
			//	break;
		}
		
		/* finish up the insert buffer */
		if (count($inserts) >= 100) {
			$sqlstring = "insert ignore into observations (enrollment_id, observation_entrydate, observation_name, observation_value, observation_rater, observation_instrument, observation_startdate, observation_enddate) values " . implode(",", $inserts);
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$inserts = array();
		}
		

		//PrintVariable($subjectRowIDCache, "subjectRowIDCache");
		//PrintVariable($enrollmentRowIDCache, "enrollmentRowIDCache");
		//PrintVariable($subjectsCreated, "subjectsCreated");
		//PrintVariable($subjectsIgnored, "subjectsIgnored");
		
		$endTime = microtime(true);
		$elapsedTime = $endTime - $startTime;
		
		$numSubjectsCreated = count($subjectsCreated);
		$numSubjectsNotFound = count($subjectsNotFound);
		$numUniqueObservationVariables = count($observationNameCache);
		$numUniqueInterventionVariables = count($interventionNameCache);
		$flagIgnoreEmptyCells = (int)$skipblankvalue;
		$flagCreateMissingSubjects = (int)$createmissingsubject;
		$importMessage = "Import complete";

		$sqlstring = "insert into nonimagingimports (project_id, importDatetime, numObservationsImported, numObservationsSkipped, numInterventionsImported, numInterventionsSkipped, flagIgnoreEmptyCells, flagCreateMissingSubjects, numSubjectsCreated, numSubjectsNotFound, numUniqueObservationVariables, numUniqueInterventionVariables, importMessage) values ($projectid, now(), $numObservationsAdded, $numObservationsIgnored, $numInterventionsAdded, $numInterventionsIgnored, $flagIgnoreEmptyCells, $flagCreateMissingSubjects, $numSubjectsCreated, $numSubjectsNotFound, $numUniqueObservationVariables, $numUniqueInterventionVariables, '$importMessage')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?>
		<!--</pre>
		</tt>-->
		<div class="ui segment">
			<h2 class="ui header">
				Import results
			</h2>
			<ul>
				<li><?=number_format($numObservationsAdded)?> observation values added (or already existing)
				<li><?=number_format($numObservationsIgnored)?> observation values skipped
				<li><?=count($observationNameCache)?> unique observation variables
				<br><br>
				<li><?=number_format($numInterventionsAdded)?> intervention values added (or already existing)
				<li><?=number_format($numInterventionsIgnored)?> intervention values skipped
				<li><?=count($interventionNameCache)?> unique intervention variables
				<br><br>
				<li><?=$numSubjectsCreated?> subjects added
				<li><?=$numSubjectsNotFound?> subjects not found
				<br><br>
				<li>Import took <?=number_format($elapsedTime,1)?> seconds
			</ul>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- AddSubject ------------------------- */
	/* -------------------------------------------- */
	function AddSubject($name, $dob, $sex, $altuid) {
	
		/* perform data checks */
		$name = mysqli_real_escape_string($GLOBALS['linki'], $name);
		$dob = mysqli_real_escape_string($GLOBALS['linki'], $dob);
		$sex = mysqli_real_escape_string($GLOBALS['linki'], $sex);
		$altuid = mysqli_real_escape_string($GLOBALS['linki'], $altuid);
		//$altuids = explode(',',$altuid);

		# create a new uid
		do {
			$uid = NIDB\CreateUID('S',3);
			$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$uid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$count = mysqli_num_rows($result);
		} while ($count > 0);
		
		# create a new family uid
		do {
			$familyuid = NIDB\CreateUID('F');
			$sqlstring = "SELECT * FROM `families` WHERE family_uid = '$familyuid'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$count = mysqli_num_rows($result);
		} while ($count > 0);
		
		/* insert the new subject */
		$sqlstring = "insert into subjects (name, birthdate, sex, uid, uuid) values ('$name', '$dob', '$sex', '$uid', uuid())";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$subjectRowID = mysqli_insert_id($GLOBALS['linki']);
		
		# create familyRowID if it doesn't exist
		$sqlstring2 = "insert into families (family_uid, family_createdate, family_name) values ('$familyuid', now(), 'Proband-$uid')";
		$result2 = MySQLiQuery($sqlstring2,__FILE__,__LINE__);
		$familyRowID = mysqli_insert_id($GLOBALS['linki']);
	
		$sqlstring3 = "insert into family_members (family_id, subject_id, fm_createdate) values ($familyRowID, $subjectRowID, now())";
		$result3 = MySQLiQuery($sqlstring3,__FILE__,__LINE__);
		
		$altuid = trim($altuid);
		$sqlstring = "insert ignore into subject_altuid (subject_id, altuid) values ($subjectRowID, '$altuid')";
		if ($GLOBALS['debug']) { PrintSQL($sqlstring); }
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		//echo "Added subject $uid\n";
		
		return array($subjectRowID, $uid);
	}


	/* -------------------------------------------- */
	/* ------- EnrollSubject ---------------------- */
	/* -------------------------------------------- */
	function EnrollSubject($subjectRowID, $projectid) {
		if ($projectid == "") {
			Error("Project not specified");
			return null;
		}

		$enrollmentRowID = null;
		
		$sqlstring = "select enrollment_id from enrollment where project_id = $projectid and subject_id = $subjectRowID";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) < 1) {
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectid, $subjectRowID, now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$enrollmentRowID = mysqli_insert_id($GLOBALS['linki']);
			
			//echo "Subject now enrolled in $projectname<br>";
		}
		else {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$enrollmentRowID = $row['enrollment_id'];
			
			//$sqlstring = "update enrollment set enroll_enddate = '0000-00-00 00:00:00' where enrollment_id = '$enrollmentRowID'";
			//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			
			//Notice("Subject re-enrolled in <b>$projectname</b>");
		}
		
		return $enrollmentRowID;
	}


	/* -------------------------------------------- */
	/* ------- DisplayImportList ------------------ */
	/* -------------------------------------------- */
	function DisplayImportList($displayall) {

		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h1 class="ui header">Non-imaging Import</h1>
					Displaying 10 most recent imports. <a href="importnonimaging.php?displayall=1">View all</a>
				</div>
				<div class="right aligned column">
					<a href="importnonimaging.php?action=newimportform" class="ui primary button"><i class="cloud upload icon"></i> New Import</a>
				</div>
			</div>
			
			<table class="ui celled structured table">
				<thead>
					<tr>
						<th colspan="2"></th>
						<th colspan="2">Options</th>
						<th colspan="3">Observations</th>
						<th colspan="3">Interventions</th>
						<th colspan="2">Subjects</th>
						<th></th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Project</th>

						<th>Ignore Empty Cells</th>
						<th>Create Missing Subjects</th>

						<th>Imported</th>
						<th>Skipped</th>
						<th>Unique Variables</th>
						
						<th>Imported</th>
						<th>Skipped</th>
						<th>Unique Variables</th>

						<th>Created</th>
						<th>Not Found</th>
						
						<th>Message</th>
					</tr>
				</thead>
		<?
		
		if ($displayall == "1") {
			$sqlstring = "select * from nonimagingimports a left join projects b on a.project_id = b.project_id order by importDatetime desc";
		}
		else {
			$sqlstring = "select * from nonimagingimports a left join projects b on a.project_id = b.project_id order by importDatetime desc limit 10";
		}
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$importid = $row['nonimagingimport_id'];
				$importdate = $row['importDatetime'];
				$projectid = $row['project_id'];
				$projectname = $row['project_name'];
				$projectnumber = $row['project_costcenter'];
				$numObservationsImported = $row['numObservationsImported'];
				$numObservationsSkipped = $row['numObservationsSkipped'];
				$numInterventionsImported = $row['numInterventionsImported'];
				$numInterventionsSkipped = $row['numInterventionsSkipped'];
				$numSubjectsCreated = $row['numSubjectsCreated'];
				$numSubjectsNotFound = $row['numSubjectsNotFound'];
				$numUniqueObservationVariables = $row['numUniqueObservationVariables'];
				$numUniqueInterventionVariables = $row['numUniqueInterventionVariables'];
				$importMessage = $row['importMessage'];
				
				if ($row['flagIgnoreEmptyCells']) { $flagIgnoreEmptyCells = "<i class='green large check icon'></i>"; }
				if ($row['flagCreateMissingSubjects']) { $flagCreateMissingSubjects = "<i class='green large check icon'></i>"; }

				if ($numObservationsImported == 0) { $numObservationsImported = "-"; } else { $numObservationsImported = number_format($numObservationsImported); }
				if ($numObservationsSkipped == 0) { $numObservationsSkipped = "-"; } else { $numObservationsSkipped = number_format($numObservationsSkipped); }
				if ($numUniqueObservationVariables == 0) { $numUniqueObservationVariables = "-"; } else { $numUniqueObservationVariables = number_format($numUniqueObservationVariables); }
				
				if ($numInterventionsImported == 0) { $numInterventionsImported = "-"; } else { $numInterventionsImported = number_format($numInterventionsImported); }
				if ($numInterventionsSkipped == 0) { $numInterventionsSkipped = "-"; } else { $numInterventionsSkipped = number_format($numInterventionsSkipped); }
				if ($numUniqueInterventionVariables == 0) { $numUniqueInterventionVariables = "-"; } else { $numUniqueInterventionVariables = number_format($numUniqueInterventionVariables); }

				if ($numSubjectsCreated == 0) { $numSubjectsCreated = "-"; } else { $numSubjectsCreated = number_format($numSubjectsCreated); }
				if ($numSubjectsNotFound == 0) { $numSubjectsNotFound = "-"; } else { $numSubjectsNotFound = number_format($numSubjectsNotFound); }
				
				?>
				<tr>
					<td><?=$importdate?></td>
					<td><?=$projectname?></td>
					
					<td class="ui center aligned text"><?=$flagIgnoreEmptyCells?></td>
					<td class="ui center aligned text"><?=$flagCreateMissingSubjects?></td>
					
					<td class="right aligned text"><?=$numObservationsImported?></td>
					<td class="right aligned text"><?=$numObservationsSkipped?></td>
					<td class="right aligned text"><?=$numUniqueObservationVariables?></td>
					
					<td class="right aligned text"><?=$numInterventionsImported?></td>
					<td class="right aligned text"><?=$numInterventionsSkipped?></td>
					<td class="right aligned text"><?=$numUniqueInterventionVariables?></td>

					<td class="right aligned text"><?=$numSubjectsCreated?></td>
					<td class="right aligned text"><?=$numSubjectsNotFound?></td>
					
					<td><?=$importMessage?></td>
				</tr>
				<?
			}
		}
		else {
			?>No current or recent non-imaging imports<?
		}
		?>
			</table>
		</div>
		<?
	}
?>
<? include("footer.php") ?>