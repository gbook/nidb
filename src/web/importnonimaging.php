<?
 // ------------------------------------------------------------------------------
 // NiDB importnonimaging.php
 // Copyright (C) 2004 - 2026
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
	ob_start(); /* buffer output so POST/Redirect/GET works despite the menu rendered first */
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

	/* ----- setup variables ----- */
	/* the preview and the import are POSTs carrying the CSRF token */
	$action = GetSafeAction(array("newimport", "submitnewimport"));

	$projectid = (int)GetVariable("projectid");
	$csv = GetVariable("csv");
	$skipblankvalue = GetVariable("skipblankvalue");
	$createmissingsubject = GetVariable("createmissingsubject");
	$displayall = GetVariable("displayall");
	
	/* determine action */
	switch ($action) {
		case 'newimportform':
			DisplayNewImportForm($instanceid);
			break;
		case 'newimport':
			/* review step: shows the parsed data and changes nothing, so no redirect.
			   Release the session lock so parsing a large CSV doesn't block the user's other requests */
			session_write_close();
			NewImportCheck($csv, $projectid, $skipblankvalue, $createmissingsubject);
			break;
		case 'submitnewimport':
			/* Post/Redirect/GET: run the import, keep its results as the flash message, then show the list.
			   The session lock is released during the (possibly long) import and re-opened for the flash */
			session_write_close();
			ob_start();
			SubmitNewImport($csv, $projectid, $skipblankvalue, $createmissingsubject);
			$flash = ob_get_clean();
			session_start();
			$_SESSION['flash'] = $flash;
			RedirectTo("importnonimaging.php");
			break;
		default:
			ShowFlashMessage();
			DisplayImportList($displayall);
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- CanImportToProject ----------------- */
	/* -------------------------------------------- */
	/* non-imaging import needs Edit Data on the destination project (admins have it everywhere) */
	function CanImportToProject($projectid) {
		$projectid = (int)$projectid;
		if ($projectid <= 0) return false;
		return (GetPerm(GetCurrentUserProjectPermissions(array($projectid)), 'modifydata', $projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- DisplayNewImportForm --------------- */
	/* -------------------------------------------- */
	function DisplayNewImportForm($instanceid) {
		/* destination projects: projects in the current instance where the user has Edit Data */
		$instanceid = (int)$instanceid;
		$projects = array();
		$sqlstring = "select project_id, project_name, project_costcenter from projects where instance_id = ? order by project_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $instanceid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instanceid]);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$projects[(int)$row['project_id']] = $row;
		}
		mysqli_stmt_close($stmt);
		$perms = GetCurrentUserProjectPermissions(array_keys($projects));
		foreach (array_keys($projects) as $pid) {
			if (GetPerm($perms, 'modifydata', $pid) != 1) unset($projects[$pid]);
		}
		?>
		<div class="ui container">
			<div class="ui top attached grey segment">
				<h2 class="ui header">New Import</h2>
			</div>
			<form method="post" action="importnonimaging.php" name="importform" enctype="multipart/form-data" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="newimport">
				<?=CSRFTokenField()?>
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
								foreach ($projects as $pid => $row) {
									?>
									<option value="<?=(int)$pid?>"><?=htmlspecialchars($row['project_name'] ?? '')?> (<?=htmlspecialchars($row['project_costcenter'] ?? '')?>)</option>
									<?
								}
							?>
						</select>
						<? if (count($projects) == 0) { ?><div class="ui small warning message">You don't have Edit Data permission on any project in this instance</div><? } ?>
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
		
		$projectid = (int)$projectid;
		if ($projectid == 0) {
			Error("Project was blank. Please go back and select a project");
			return;
		}
		if (!CanImportToProject($projectid)) {
			Error("You need Edit Data permission on the destination project to import into it");
			return;
		}
		
		$skipblankvalue = ($skipblankvalue == 1);
		$createmissingsubject = ($createmissingsubject == 1);
		
		$csvs = array();
		if ($csv != "") {
			$csvs['Pasted'] = $csv;
		}
		if (isset($_FILES['files']['name']) && is_array($_FILES['files']['name'])) {
			foreach ($_FILES['files']['name'] as $i => $name) {
				if (($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) != UPLOAD_ERR_OK) continue;
				$csvs[$name] = file_get_contents($_FILES['files']['tmp_name'][$i]);
			}
		}
		if (count($csvs) == 0) {
			Error("No CSV file or CSV text was provided");
			return;
		}
		
		$data = array();
		/* run through all of the CSV files and CSV pasted text */
		foreach ($csvs as $name => $csv) {
			
			$csvdata = ParseCSV(trim($csv));
			$rows = count($csvdata);
			$cols = ($rows > 0) ? count(array_keys($csvdata[0])) : 0;
			$cells = count($csvdata, COUNT_RECURSIVE);
			?>
			<div class="ui segment">
				<p>CSV [<?=htmlspecialchars($name)?>] contains <?=$rows?> rows x <?=$cols?> columns = <?=$cells?> cells</p>
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
						$data[$id][$type][$name][$startdate]['value'] = $value;
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
				$ids[$id]['uid'] = $uid;
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
						
						$rowdata[] = array('subjectrowid' => (string)$subjectRowID, 'id' => (string)$id, 'uid' => (string)$uid, 'type' => (string)$type, 'variable' => (string)$variable, 'startdate' => (string)$startdate, 'value' => (string)$value, 'instrument' => (string)$instrument, 'notes' => (string)$notes, 'description' => (string)$description, 'rater' => (string)$rater, 'enddate' => (string)$enddate, 'duration' => (string)$duration, 'entrydate' => (string)$entrydate, 'createdate' => (string)$createdate, 'modifydate' => (string)$modifydate);
					}
				}
			}
		}
		$totalrows = count($rowdata);
		/* the grid data is passed as JSON; JSON_HEX_* keep CSV values from breaking out of the <script> */
		$datatable = json_encode($rowdata, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
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

				rowData: <?=$datatable?>,
				
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
						<?=CSRFTokenField()?>
						<input type="hidden" name="projectid" value="<?=(int)$projectid?>">
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

		/* large imports can exceed the default 30s limit */
		set_time_limit(0);

		$startTime = microtime(true);
		
		$projectid = (int)$projectid;
		if (!CanImportToProject($projectid)) {
			Error("You need Edit Data permission on the destination project to import into it");
			return;
		}
		
		$skipblankvalue = ($skipblankvalue == 1);
		$createmissingsubject = ($createmissingsubject == 1);
		
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
		if ($rows == 0) {
			Error("The submitted data was empty");
			return;
		}
		$cols = count(array_keys($csvdata[0]));
		/* avoid COUNT_RECURSIVE which iterates every cell of a potentially huge array */
		$cells = $rows * $cols;
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

		/* build instrument and item lookup caches for this project */
		$instrumentIdCache   = array();
		$instrumentItemCache = array();
		$sqlstring = "select instrument_id, instrument_name from instruments where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$instrumentIdCache[strtolower(trim($row['instrument_name']))] = (int)$row['instrument_id'];
		}
		mysqli_stmt_close($stmt);
		if (count($instrumentIdCache) > 0) {
			$iids = implode(',', array_values($instrumentIdCache));
			/* store instrumentitem_id (not just true) so it can be used directly in the INSERT. $iids are integers from the database */
			$sqlstring = "select instrument_id, instrumentitem_id, item_name from instrument_items where instrument_id in ($iids)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$instrumentItemCache[(int)$row['instrument_id']][strtolower(trim($row['item_name']))] = (int)$row['instrumentitem_id'];
			}
		}

		/* pre-load all enrollment IDs for this project in one query;
		   eliminates a per-subject DB lookup for every row in the loop */
		$sqlstring = "select enrollment_id, subject_id from enrollment where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$enrollmentRowIDCache[(int)$row['subject_id']] = (int)$row['enrollment_id'];
		}
		mysqli_stmt_close($stmt);

		/* PASS 1: resolve all subjects/enrollments and collect formatted observation insert strings.
		   Keeping subject resolution separate from the insert phase allows PASS 2 to hold a
		   table-level write lock on observations without interfering with other tables. */
		$observationRows = array();

		foreach ($csvdata as $line) {
			/* reset all variables for this row. ParseCSV() drops empty cells, hence the ?? '' */
			$id = trim($line['ID'] ?? '');
			$variablename = $line['Variable'] ?? '';
			$value = $line['Value'] ?? '';
			$type = strtolower(trim($line['Type'] ?? ''));
			$instrument = $line['Instrument'] ?? '';
			$rater = $line['Rater'] ?? '';
			$startdate = $line['StartDate'] ?? '';
			$enddate = $line['EndDate'] ?? '';
			if ($type == "") $type = "observation";

			if ($id == "") continue;

			/* the subjectRowID/UID columns in the submitted grid come from the browser and are NOT trusted:
			   every ID is looked up again here, and only subjects enrolled in this project are found */
			if (isset($subjectRowIDCache[$id])) {
				$subjectRowID = $subjectRowIDCache[$id];
			}
			else {
				list ($subjectRowID, $uid) = GetSubjectRowIDByProject($id, $projectid);
				$subjectRowID = (int)$subjectRowID;
				$subjectRowIDCache[$id] = $subjectRowID;
			}

			if ($subjectRowID == 0) {
				if ($createmissingsubject) {
					/* create a subject (placeholder demographics; the name field gets the CSV ID) */
					list($subjectRowID, $uid) = AddSubject($id, '1900-01-01', 'U', $id);
					$subjectRowID = (int)$subjectRowID;
					$subjectRowIDCache[$id] = $subjectRowID;

					/* enroll the subject in this project */
					$enrollmentRowID = EnrollSubject($subjectRowID, $projectid);
					$enrollmentRowIDCache[$subjectRowID] = $enrollmentRowID;

					$subjectsCreated[$id] = 1;
				}
				else {
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

			/* get the enrollmentRowID. A found subject is always enrolled in this project (the lookup requires it) */
			if (isset($enrollmentRowIDCache[$subjectRowID])) {
				$enrollmentRowID = $enrollmentRowIDCache[$subjectRowID];
			}
			else {
				$enrollmentRowID = EnrollSubject($subjectRowID, $projectid);
				$enrollmentRowIDCache[$subjectRowID] = $enrollmentRowID;
			}

			if ($type == "observation") {
				/* fix any missing fields (null = SQL NULL; the values are bound in the insert below) */
				$rater = (trim($rater) == "") ? null : trim($rater);

				/* resolve instrumentitem_id if instrument name and variable name both match existing DB records */
				$resolvedItemId = null;
				$rawInstrument = trim($instrument);
				if ($rawInstrument != "") {
					$instrKey = strtolower($rawInstrument);
					if (isset($instrumentIdCache[$instrKey])) {
						$iid = $instrumentIdCache[$instrKey];
						$itemKey = strtolower(trim($variablename));
						if (isset($instrumentItemCache[$iid][$itemKey])) {
							$resolvedItemId = $instrumentItemCache[$iid][$itemKey];
						}
					}
				}
				$instrument = ($rawInstrument == "") ? null : $rawInstrument;

				/* dates come from the browser: keep only values that parse, normalized to a datetime */
				$startdate = (trim($startdate) == "" || strtotime(trim($startdate)) === false) ? null : date('Y-m-d H:i:s', strtotime(trim($startdate)));
				$enddate = (trim($enddate) == "" || strtotime(trim($enddate)) === false) ? null : date('Y-m-d H:i:s', strtotime(trim($enddate)));

				/* collect the row for the bulk insert pass */
				$observationRows[] = array((int)$enrollmentRowID, (string)$variablename, (string)$value, $rater, $instrument, $startdate, $enddate, $resolvedItemId);
				$numObservationsAdded++;
			}
			
			if ($type == "intervention") {
				$numInterventionsAdded++;
			}
			
			//$i++;
			//if ($i > 1000)
			//	break;
		}
		
		/* PASS 2: bulk insert all collected observation rows.
		   DISABLE KEYS defers non-unique index updates until ENABLE KEYS rebuilds them in
		   one pass at the end, avoiding per-row index maintenance across 300k+ rows.
		   LOCK TABLES holds the Aria table-level write lock for the entire insert phase
		   instead of acquiring and releasing it once per batch. */
		if (count($observationRows) > 0) {
			MySQLiQuery("ALTER TABLE observations DISABLE KEYS", __FILE__, __LINE__);
			MySQLiQuery("LOCK TABLES observations WRITE", __FILE__, __LINE__);

			/* batch size of 1000 reduces round trips by 10x vs the previous 100-row batches */
			foreach (array_chunk($observationRows, 1000) as $batch) {
				/* bound multi-row insert: 8 parameters per row (observation_entrydate is now()) */
				$sqlstring = "insert ignore into observations (enrollment_id, observation_entrydate, observation_name, observation_value, observation_rater, observation_instrument, observation_startdate, observation_enddate, instrumentitem_id) values " . implode(",", array_fill(0, count($batch), "(?, now(), ?, ?, ?, ?, ?, ?, ?)"));
				$params = array();
				foreach ($batch as $r) { foreach ($r as $v) { $params[] = $v; } }
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, str_repeat('issssssi', count($batch)), ...$params);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
				mysqli_stmt_close($stmt);
			}

			MySQLiQuery("UNLOCK TABLES", __FILE__, __LINE__);
			MySQLiQuery("ALTER TABLE observations ENABLE KEYS", __FILE__, __LINE__);
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

		$sqlstring = "insert into nonimagingimports (project_id, importDatetime, numObservationsImported, numObservationsSkipped, numInterventionsImported, numInterventionsSkipped, flagIgnoreEmptyCells, flagCreateMissingSubjects, numSubjectsCreated, numSubjectsNotFound, numUniqueObservationVariables, numUniqueInterventionVariables, importMessage) values (?, now(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$params = [$projectid, $numObservationsAdded, $numObservationsIgnored, $numInterventionsAdded, $numInterventionsIgnored, $flagIgnoreEmptyCells, $flagCreateMissingSubjects, $numSubjectsCreated, $numSubjectsNotFound, $numUniqueObservationVariables, $numUniqueInterventionVariables, $importMessage];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iiiiiiiiiiis', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		
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
		$name = (string)$name;
		$dob = (string)$dob;
		$sex = (string)$sex;
		$altuid = trim((string)$altuid);

		# create a new uid
		do {
			$uid = NIDB\CreateUID('S',3);
			$sqlstring = "select subject_id from subjects where uid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $uid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$uid]);
			$count = mysqli_num_rows($result);
			mysqli_stmt_close($stmt);
		} while ($count > 0);
		
		# create a new family uid
		do {
			$familyuid = NIDB\CreateUID('F');
			$sqlstring = "select family_id from families where family_uid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $familyuid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyuid]);
			$count = mysqli_num_rows($result);
			mysqli_stmt_close($stmt);
		} while ($count > 0);
		
		/* insert the new subject */
		$sqlstring = "insert into subjects (name, birthdate, sex, uid, uuid) values (?, ?, ?, ?, uuid())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssss', $name, $dob, $sex, $uid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, ['[name]', '[dob]', $sex, $uid]);
		$subjectRowID = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		# create familyRowID if it doesn't exist
		$familyname = "Proband-$uid";
		$sqlstring = "insert into families (family_uid, family_createdate, family_name) values (?, now(), ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $familyuid, $familyname);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyuid, $familyname]);
		$familyRowID = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
	
		$sqlstring = "insert into family_members (family_id, subject_id, fm_createdate) values (?, ?, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $familyRowID, $subjectRowID);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyRowID, $subjectRowID]);
		mysqli_stmt_close($stmt);
		
		$sqlstring = "insert ignore into subject_altuid (subject_id, altuid) values (?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'is', $subjectRowID, $altuid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectRowID, $altuid]);
		mysqli_stmt_close($stmt);
		
		return array($subjectRowID, $uid);
	}


	/* -------------------------------------------- */
	/* ------- EnrollSubject ---------------------- */
	/* -------------------------------------------- */
	function EnrollSubject($subjectRowID, $projectid) {
		if ($projectid == 0) {
			Error("Project not specified");
			return null;
		}

		$enrollmentRowID = null;
		$projectid = (int)$projectid;
		$subjectRowID = (int)$subjectRowID;
		
		$sqlstring = "select enrollment_id from enrollment where project_id = ? and subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $projectid, $subjectRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $subjectRowID]);
		$row = ($result) ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		mysqli_stmt_close($stmt);
		if (!$row) {
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values (?, ?, now())";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $projectid, $subjectRowID);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $subjectRowID]);
			$enrollmentRowID = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
		}
		else {
			$enrollmentRowID = (int)$row['enrollment_id'];
			
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
				
				/* reset per row (they used to carry over from the previous row) */
				$flagIgnoreEmptyCells = ($row['flagIgnoreEmptyCells']) ? "<i class='green large check icon'></i>" : "";
				$flagCreateMissingSubjects = ($row['flagCreateMissingSubjects']) ? "<i class='green large check icon'></i>" : "";

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
					<td><?=htmlspecialchars($importdate ?? '')?></td>
					<td><?=htmlspecialchars($projectname ?? '')?></td>
					
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
					
					<td><?=htmlspecialchars($importMessage ?? '')?></td>
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