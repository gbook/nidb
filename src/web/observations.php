<?
 // ------------------------------------------------------------------------------
 // NiDB observations.php
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

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$observationid = GetVariable("observationid");
	$enrollmentid = GetVariable("enrollmentid");
	$observationname = GetVariable('observation_name');
	$observationinstrument = GetVariable('observation_instrument');
	$instrumentitemid = GetVariable('instrumentitem_id');
	$observationvalue = GetVariable('observation_value');
	$observationdatecompleted = GetVariable('observation_datecompleted');
	$observationrater = GetVariable('observation_rater');

	/* determine action */
	switch ($action) {
		case 'addobservation':
			AddObservation($enrollmentid, $observationname, $observationvalue, $observationdatecompleted, $observationrater, $observationinstrument, $instrumentitemid);
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
	function AddObservation($enrollmentid, $observationname, $observationvalue, $observationdatecompleted, $observationrater, $observationinstrument, $instrumentitemid) {
		$observationvalue = mysqli_real_escape_string($GLOBALS['linki'], $observationvalue);
		$observationdatecompleted = mysqli_real_escape_string($GLOBALS['linki'], $observationdatecompleted);
		$observationrater = mysqli_real_escape_string($GLOBALS['linki'], $observationrater);

		/* if an instrument item is linked, derive observation_name and observation_instrument from the DB */
		if ((int)$instrumentitemid > 0) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select ii.item_name, ins.instrument_name from instrument_items ii left join instruments ins on ii.instrument_id = ins.instrument_id where ii.instrumentitem_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $instrumentitemid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$observationname = $row['item_name'];
				$observationinstrument = $row['instrument_name'];
			}
			mysqli_stmt_close($stmt);
		}

		$observationname = mysqli_real_escape_string($GLOBALS['linki'], $observationname);
		$observationinstrument = mysqli_real_escape_string($GLOBALS['linki'], $observationinstrument);

		if (trim($observationrater) == "") $observationrater = "null";
		else $observationrater = "'" . trim($observationrater) . "'";

		if (trim($observationinstrument) == "") $observationinstrument = "null";
		else $observationinstrument = "'" . trim($observationinstrument) . "'";

		if (trim($observationdatecompleted) == "") $observationdatecompleted = "null";
		else $observationdatecompleted = "'" . trim($observationdatecompleted) . "'";

		if ((int)$instrumentitemid > 0) $instrumentitemid_sql = (int)$instrumentitemid;
		else $instrumentitemid_sql = "null";

		$sqlstring = "insert ignore into observations (enrollment_id, observation_entrydate, observation_name, observation_value, observation_rater, observation_instrument, instrumentitem_id, observation_enddate) values ($enrollmentid, now(), '$observationname', '$observationvalue', $observationrater, $observationinstrument, $instrumentitemid_sql, $observationdatecompleted)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DeleteObservation ------------------ */
	/* -------------------------------------------- */
	function DeleteObservation($id) {
		$sqlstring = "delete from observations where observation_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		Notice("Observation deleted");
	}


	/* -------------------------------------------- */
	/* ------- DisplayObservationList ------------- */
	/* -------------------------------------------- */
	function DisplayObservationList($enrollmentid) {

		if ((trim($enrollmentid) == "") || ($enrollmentid < 0)) {
			?>Invalid or blank enrollment ID [<?=$enrollmentid?>]<?
			return;
		}

		/* get subject's info and project for the breadcrumb/form */
		$sqlstring = "select a.*, b.uid, b.subject_id, c.project_name, c.project_id from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $enrollmentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectid = $row['project_id'];

		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

		<div class="ui text container" id="pageloading">
			<div class="ui inverted segment" align="center">
				<h2 class="ui inverted header">
					<i class="spinner loading icon"></i> Loading...
				</h2>
			</div>
		</div>

		<!-- Add Instrument Modal -->
		<div class="ui small modal" id="addInstrumentModal">
			<div class="header">Add New Instrument</div>
			<div class="content">
				<div class="ui form" id="addInstrumentForm">
					<div class="field">
						<label>Instrument name <span class="ui red text">*</span></label>
						<input type="text" id="newInstrumentName" placeholder="Enter instrument name">
					</div>
					<div class="field">
						<label>Notes</label>
						<textarea id="newInstrumentNotes" rows="2" placeholder="Optional notes"></textarea>
					</div>
					<div id="addInstrumentError" class="ui error message" style="display:none"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button" id="addInstrumentSave">Save instrument</div>
			</div>
		</div>

		<!-- Add Instrument Item Modal -->
		<div class="ui small modal" id="addItemModal">
			<div class="header">Add New Item to <span id="addItemModalInstrumentName"></span></div>
			<div class="content">
				<div class="ui form" id="addItemForm">
					<div class="field">
						<label>Item name <span class="ui red text">*</span></label>
						<input type="text" id="newItemName" placeholder="Enter item name">
					</div>
					<div class="field">
						<label>Type</label>
						<select id="newItemType" class="ui dropdown">
							<option value="string">string</option>
							<option value="int">int</option>
							<option value="double">double</option>
							<option value="timeseries">timeseries</option>
						</select>
					</div>
					<div class="field">
						<label>Notes</label>
						<textarea id="newItemNotes" rows="2" placeholder="Optional notes"></textarea>
					</div>
					<div id="addItemError" class="ui error message" style="display:none"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button" id="addItemSave">Save item</div>
			</div>
		</div>

		<!-- 'Formalize Instrument' Modal - this displays an option to take a set of observations and make it into an instrument -->
		<div class="ui small modal" id="formalizeInstrumentModal">
			<div class="header" id="formalizeModalHeader">Create Instrument from Legacy Observations</div>
			<div class="content">
				<div class="ui info message">
					This will create a new instrument and convert <b><span id="formalizeConvertCount">0</span> observation(s)</b> project-wide from legacy text strings to linked instrument items.
				</div>
				<div class="ui form">
					<div class="field">
						<label>Instrument name</label>
						<input type="text" id="formalizeInstrumentName">
					</div>
					<div class="field">
						<label>Items to create (<span id="formalizeItemCount">0</span>) &mdash; all added as string type, editable later via Instruments</label>
						<div id="formalizeItemsList" style="max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:8px; border-radius:4px; background:#fafafa; font-family:monospace; font-size:0.9em"></div>
					</div>
					<div id="formalizeError" class="ui error message" style="display:none"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button" id="formalizeSaveButton">Create &amp; Convert</div>
			</div>
		</div>

		<div class="ui raised black segment">
			<form action="observations.php" method="post" class="ui form" id="addObsForm">
				<input type="hidden" name="action" value="addobservation">
				<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
				<input type="hidden" name="instrument_id" id="instrumentId">
				<input type="hidden" name="observation_instrument" id="instrumentNameHidden">
				<input type="hidden" name="instrumentitem_id" id="instrumentitemId">

				<div class="fields">
					<div class="four wide field">
						<label>Instrument &nbsp; <span class="ui basic tiny label" style="font-weight:normal">optional but encouraged</span></label>
						<input type="text" id="instrumentSearch" placeholder="Search instruments..." autocomplete="off">
						<small><a href="#" id="addInstrumentLink" style="color:#2185d0">+ Add new instrument</a></small>
					</div>
					<div class="four wide field">
						<label>Instrument item</label>
						<input type="text" id="itemSearch" placeholder="Select instrument first" autocomplete="off" disabled>
						<small><a href="#" id="addItemLink" style="color:#2185d0; display:none">+ Add new item</a></small>
					</div>
					<div class="four wide field">
						<label>Observation name</label>
						<input type="text" name="observation_name" id="observationName" placeholder="Enter name or select instrument/item" required>
					</div>
					<div class="two wide field">
						<label>Value</label>
						<input type="text" name="observation_value" size="10" required>
					</div>
				</div>
				<div class="fields">
					<div class="four wide field">
						<label>Start date</label>
						<input type="datetime-local" name="observation_startdate">
					</div>
					<div class="two wide field">
						<label>Duration</label>
						<input type="text" name="observation_duration" size="10">
					</div>
					<div class="four wide field">
						<label>End date</label>
						<input type="datetime-local" name="observation_enddate">
					</div>
					<div class="two wide field">
						<label>Rater</label>
						<input type="text" name="observation_rater" size="15" value="<?=$GLOBALS['username']?>">
					</div>
					<div class="two wide field">
						<label>&nbsp;</label>
						<input type="submit" class="ui primary button" value="Add Observation">
					</div>
				</div>

			</form>
		</div>

		<?
		$groups = array();
		$sqlstring = "select a.*, e.uid, ii.item_name, ins.instrument_name as linked_instrument_name from observations a left join enrollment d on a.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id left join instruments ins on ii.instrument_id = ins.instrument_id where a.enrollment_id = $enrollmentid order by a.observation_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$observationid = $row['observation_id'];
			$studyid = $row['study_id'];
			$seriesid = $row['series_id'];
			$uid = $row['uid'];
			$observation_name = $row['item_name'] != '' ? $row['item_name'] : $row['observation_name'];
			$instrument_name = $row['linked_instrument_name'] != '' ? $row['linked_instrument_name'] : $row['observation_instrument'];

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

			$entry = array(
				'observationid' => $observationid,
				'instrumentitemid' => (int)$row['instrumentitem_id'],
				'name' => $observation_name,
				'obsInstrument' => $row['observation_instrument'],
				'instrumentitem' => $row['item_name'],
				'value' => $row['observation_value'],
				'series' => $series,
				'rater' => $row['observation_rater'],
				'startdate' => $row['observation_startdate'],
				'duration' => $row['observation_duration'],
				'enddate' => $row['observation_enddate'],
				'dateshtml' => "<b>Entry</b> " . $row['observation_entrydate'] . "<br><b>Create</b> " . $row['observation_createdate'] . "<br><b>Modify</b> " . $row['observation_modifydate']
			);

			$groupKey = !empty($instrument_name) ? $instrument_name : '__none__';
			$groups[$groupKey][] = $entry;
		}

		/* sort instrument groups alphabetically; move __none__ to end */
		ksort($groups);
		if (isset($groups['__none__'])) {
			$noneRows = $groups['__none__'];
			unset($groups['__none__']);
			$groups['__none__'] = $noneRows;
		}

		$groupsJson = json_encode($groups, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

		/* build per-group metadata: whether a real instrument exists, project-wide legacy count, unique item names */
		$groupMeta = array();
		$totalObs = 0;
		$instrumentCount = 0;
		$unaffiliatedCount = isset($groups['__none__']) ? count($groups['__none__']) : 0;
		foreach ($groups as $key => $rows) {
			$totalObs += count($rows);
			if ($key === '__none__') continue;
			$instrumentCount++;

			$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id from instruments where project_id = ? and instrument_name = ? limit 1");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $key);
			$instrResult = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$hasInstrument = (mysqli_num_rows($instrResult) > 0);
			$instrRow = $hasInstrument ? mysqli_fetch_array($instrResult, MYSQLI_ASSOC) : false;
			mysqli_stmt_close($stmt);

			$stmt = mysqli_prepare($GLOBALS['linki'], "select count(*) as cnt from observations o join enrollment e on o.enrollment_id = e.enrollment_id where e.project_id = ? and o.observation_instrument = ? and o.instrumentitem_id is null");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $key);
			$cntResult = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$cntRow = mysqli_fetch_array($cntResult, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			$seen = array();
			$uniqueItems = array();
			foreach ($rows as $r) {
				if ($r['name'] !== '' && !isset($seen[$r['name']])) {
					$seen[$r['name']] = true;
					$uniqueItems[] = $r['name'];
				}
			}
			sort($uniqueItems);

			$groupMeta[$key] = [
				'hasInstrument' => $hasInstrument,
				'instrumentId'  => $hasInstrument ? (int)$instrRow['instrument_id'] : null,
				'legacyCount'   => (int)$cntRow['cnt'],
				'uniqueItems'   => $uniqueItems,
			];
		}
		$groupMetaJson = json_encode($groupMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		?>
		<p style="color:#666; margin-bottom:6px"><?=$totalObs?> observation<?=$totalObs != 1 ? 's' : ''?> from <?=$instrumentCount?> instrument<?=$instrumentCount != 1 ? 's' : ''?> (<?=$unaffiliatedCount?> unaffiliated observation<?=$unaffiliatedCount != 1 ? 's' : ''?>)</p>
		<div class="ui styled fluid accordion" id="observationAccordion"></div>

		<script>
			const groupedData = <?=$groupsJson?>;
			const groupMeta   = <?=$groupMetaJson?>;
			const PROJECT_ID  = <?=(int)$projectid?>;

			/* custom AG Grid header component using prototype API (required by community edition) */
			function ObservationDatesHeader() {}
			ObservationDatesHeader.prototype.init = function(params) {
				this.eGui = document.createElement('span');
				this.eGui.className = 'observation-html-tooltip';
				this.eGui.setAttribute('data-html', '<b>Entry</b> Date the value was entered into this database<br><b>Create</b> Date the record was created in this database<br><b>Modify</b> Date the record was modified in this database');
				this.eGui.innerHTML = 'Entry dates <i class="question circle icon"></i>';
			};
			ObservationDatesHeader.prototype.getGui = function() { return this.eGui; };

			/* visually distinguish editable cells (pointer cursor) from read-only cells (muted text);
			   must evaluate editable as a function when it's used as a callback on the column def */
			function editableCellStyle(params) {
				if (params.colDef.editable === true || (typeof params.colDef.editable === 'function' && params.colDef.editable(params))) {
					return { cursor: 'text' };
				}
				return { color: '#666' };
			}

			/* saves an in-place cell edit via AJAX; flashes the cell green on success or reverts the value on failure */
			function onCellValueChanged(params) {
				$.post('ajaxapi.php', {
					action: 'updateobservationdetails',
					observationid: params.data.observationid,
					column: params.column.colId,
					value: params.newValue ?? ''
				}, function(response) {
					if (response === 'success') {
						params.api.flashCells({ rowNodes: [params.node], columns: [params.column.colId] });
					} else {
						params.node.setDataValue(params.column.colId, params.oldValue);
					}
				}).fail(function() {
					params.node.setDataValue(params.column.colId, params.oldValue);
				});
			}

			/* shared column definitions referenced by both instrColDefs and noneColDefs */
			const deleteColDef = {
				headerName: 'Delete',
				field: 'observationid',
				width: 90, minWidth: 90, maxWidth: 90,
				filter: false, sortable: false, resizable: false,
				cellStyle: { 'text-align': 'center', 'display': 'flex', 'align-items': 'center' },
				cellRenderer: function(params) {
					const link = document.createElement('a');
					link.href = 'observations.php?action=deleteobservation&observationid=' + params.value + '&enrollmentid=<?=$enrollmentid?>';
					link.title = 'Delete this observation';
					link.onclick = function() { return confirm('Are you sure you want to delete this record?'); };
					link.innerHTML = '<i class="large red alternate outline trash icon"></i>';
					return link;
				}
			};

			/* entry dates column definition */
			const datesColDef = {
				headerName: 'Entry dates',
				field: 'dateshtml',
				width: 175, minWidth: 175, maxWidth: 175,
				filter: false, sortable: false, resizable: false,
				cellStyle: { 'text-align': 'center', 'display': 'flex', 'align-items': 'center' },
				headerComponent: ObservationDatesHeader,
				cellRenderer: function(params) {
					const icon = document.createElement('span');
					icon.className = 'observation-html-tooltip';
					icon.setAttribute('data-html', params.value);
					icon.innerHTML = '<i class="large calendar alternate outline icon"></i>';
					return icon;
				}
			};

			/* column defs for instrument groups. There is no Instrument column here, because it is shown in the accordion header */
			const instrColDefs = [
				{
					headerName: 'Variable', field: 'name', flex: 1.3, minWidth: 180,
					editable: function(params) { return !params.data.instrumentitemid; },
					cellStyle: editableCellStyle
				},
				{ headerName: 'Instrument Item', field: 'instrumentitem', flex: 1, minWidth: 150 },
				{ headerName: 'Value', field: 'value', flex: 1, minWidth: 130, editable: true, cellStyle: editableCellStyle },
				{ headerName: 'Series', field: 'series', flex: 1, minWidth: 160 },
				{ headerName: 'Rater', field: 'rater', minWidth: 120, editable: true, cellStyle: function() { return { 'font-size': '9pt', cursor: 'text' }; } },
				{ headerName: 'Start date', field: 'startdate', minWidth: 160, editable: true, cellStyle: editableCellStyle },
				{ headerName: 'Duration', field: 'duration', minWidth: 110, editable: true, cellStyle: editableCellStyle },
				{ headerName: 'End date', field: 'enddate', minWidth: 160, editable: true, cellStyle: editableCellStyle },
				deleteColDef,
				datesColDef
			];

			/* column defs for the no-instrument group: keep Variable (instrColDefs[0]), insert an editable
			   Instrument column, then append everything from instrColDefs except name and instrumentitem */
			const noneInstrumentColDef = { headerName: 'Instrument', field: 'obsInstrument', flex: 1, minWidth: 150, editable: true, cellStyle: editableCellStyle };
			const noneColDefs = [instrColDefs[0], noneInstrumentColDef, ...instrColDefs.filter(function(c) { return c.field !== 'instrumentitem' && c.field !== 'name'; })];

			/* sanitizes a string for safe insertion into innerHTML */
			function escHtml(s) {
				return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
			}

			/* original legacy instrument name used for DB matching during conversion; stored separately
			   so the user can rename the instrument in the modal without breaking the observation lookup */
			let formalizeOriginalName = '';

			/* populates and opens the formalize modal dialog for a given instrument group;
			   adapts the title, the items list, and convert count based on whether the instrument already exists */
			function openFormalizeModal(instrName) {
				/* snapshot the original legacy name so onApprove can match DB rows even if the user renames the instrument */
				formalizeOriginalName = instrName;
				const meta = groupMeta[instrName];
				if (!meta) return;

				/* title and name-field editability differ between "create" (Legacy) and "convert" (Partially linked) modes */
				$('#formalizeModalHeader').text(meta.hasInstrument ? 'Convert Remaining Legacy Observations' : 'Create Instrument from Legacy Observations');
				$('#formalizeInstrumentName').val(instrName).prop('readonly', meta.hasInstrument);

				/* show how many observations will be converted and how many unique items will be created */
				$('#formalizeConvertCount').text(meta.legacyCount);
				$('#formalizeItemCount').text(meta.uniqueItems.length);

				/* reset any error/spinner state left over from a previous open */
				$('#formalizeError').hide();
				$('#formalizeSaveButton').removeClass('loading disabled');

				/* populate the preview list of observation names that will become instrument items */
				const list = $('#formalizeItemsList');
				list.empty();
				if (meta.uniqueItems.length === 0) {
					list.append('<em style="color:#999">No items found</em>');
				} else {
					meta.uniqueItems.forEach(function(item) {
						list.append($('<div>').css('padding', '1px 0').text(item));
					});
				}

				$('#formalizeInstrumentModal').modal({
					closable: false,
					onApprove: function() {
						/* returning false keeps the modal open while the AJAX call completes */
						const newName = $('#formalizeInstrumentName').val().trim();
						if (newName === '') {
							$('#formalizeError').text('Instrument name is required.').show();
							return false;
						}
						$('#formalizeSaveButton').addClass('loading disabled');
						$.post('ajaxapi.php', {
							action:         'formalizeinstrument',
							instrumentname: newName,       /* potentially renamed instrument name to save */
							originalname:   formalizeOriginalName, /* original legacy string used for DB WHERE matching */
							projectid:      PROJECT_ID,
							itemnames:      JSON.stringify(groupMeta[formalizeOriginalName].uniqueItems)
						}, function(data) {
							if (data.error) {
								$('#formalizeError').text(data.error).show();
								$('#formalizeSaveButton').removeClass('loading disabled');
								return;
							}
							/* reload so accordions reflect the newly linked instrument */
							location.reload();
						}, 'json').fail(function() {
							$('#formalizeError').text('Server error — please try again.').show();
							$('#formalizeSaveButton').removeClass('loading disabled');
						});
						return false;
					}
				}).modal('show');
			}

			/* resets the instrument item autocomplete and unlocks the observation name field */
			function clearItemSelection() {
				$('#instrumentitemId').val('');
				$('#observationName').prop('readonly', false).css('background-color', '').val('');
			}

			/* resets the instrument autocomplete and cascades to clear the item selection */
			function clearInstrumentSelection() {
				$('#instrumentId').val('');
				$('#instrumentNameHidden').val('');
				$('#itemSearch').prop('disabled', true).val('').removeClass('error');
				$('#addItemLink').hide();
				clearItemSelection();
			}

			$(document).ready(function() {
				const accordion = document.getElementById('observationAccordion');

				/* build one accordion section + AG Grid per instrument group; __none__ receives special column defs */
				Object.entries(groupedData).forEach(function([instrName, rows]) {
					const isNone = instrName === '__none__';
					const displayName = isNone ? 'No instrument' : instrName;
					/* grid element IDs must be valid HTML identifiers, so strip non-alphanumeric characters */
					const gridId = 'grid_' + instrName.replace(/[^a-zA-Z0-9]/g, '_');
					/* clamp grid height: minimum 120px, maximum 600px, ~42px per row + 56px header */
					const height = Math.max(120, Math.min(rows.length * 42 + 56, 600));

					/* meta is null for the __none__ group because it has no associated instrument record */
					const meta = isNone ? null : (groupMeta[instrName] || null);
					let badge = '';
					if (meta) {
						if (meta.hasInstrument && meta.legacyCount === 0) {
							/* all observations are linked via instrumentitem_id */
							badge = '&nbsp;<span class="ui tiny green label" title="This instrument exists"><i class="check icon"></i> Linked</span>';
						} else if (meta.hasInstrument && meta.legacyCount > 0) {
							/* instrument exists in DB but some observations still use the legacy observation_instrument string */
							badge = '&nbsp;<span class="ui tiny yellow label" title="This instrument exists, but not all items exist"><i class="adjust icon"></i> Partially linked</span>'
								+ '&nbsp;<a class="formalize-link" href="#" data-instrument="' + escHtml(instrName) + '" style="font-size:0.85em">'
								+ '<i class="sync icon"></i>Convert remaining</a>';
						} else {
							/* no instrument record exists; only the free-text observation_instrument string is present */
							badge = '&nbsp;<span class="ui tiny orange label" title="This instrument does not exist"><i class="warning sign icon"></i> Legacy</span>'
								+ '&nbsp;<a class="formalize-link" href="#" data-instrument="' + escHtml(instrName) + '" style="font-size:0.85em">'
								+ '<i class="plus icon"></i>Create instrument</a>';
						}
					}

					/* accordion title: dropdown chevron, bold instrument name, row-count bubble, and badge */
					const title = document.createElement('div');
					title.className = 'title';
					title.innerHTML = '<i class="dropdown icon"></i><b>' + escHtml(displayName) + '</b>&nbsp;<div class="ui small circular label">' + rows.length + '</div>' + badge;
					accordion.appendChild(title);

					/* accordion content: a single sized div that AG Grid mounts into */
					const content = document.createElement('div');
					content.className = 'content';
					const gridDiv = document.createElement('div');
					gridDiv.id = gridId;
					gridDiv.style.cssText = 'height:' + height + 'px; width:100%';
					content.appendChild(gridDiv);
					accordion.appendChild(content);

					agGrid.createGrid(gridDiv, {
						theme: agGrid.themeQuartz,
						/* __none__ group gets an editable Instrument column; all others show the instrument in the accordion header */
						columnDefs: isNone ? noneColDefs : instrColDefs,
						rowData: rows,
						defaultColDef: { sortable: true, filter: true, resizable: true },
						animateRows: false,
						suppressMovableColumns: true,
						onCellValueChanged: onCellValueChanged
					});
				});

				/* exclusive:false allows multiple accordion sections to be open simultaneously */
				$('#observationAccordion').accordion({ exclusive: false });
				/* jQuery UI tooltip for observation entry-date cells that use data-html */
				$('#observationAccordion').tooltip({
					items: '.observation-html-tooltip',
					content: function() { return $(this).attr('data-html'); }
				});

				/* stopPropagation prevents the accordion title click handler from toggling the section */
				$(document).on('click', '.formalize-link', function(e) {
					e.stopPropagation();
					e.preventDefault();
					openFormalizeModal($(this).data('instrument'));
				});

				$('#pageloading').hide();

				/* ----- instrument autocomplete ----- */
				$('#instrumentSearch').autocomplete({
					source: function(request, response) {
						$.getJSON('ajaxapi.php', { action: 'searchinstruments', term: request.term, projectid: PROJECT_ID }, response);
					},
					minLength: 0,
					select: function(event, ui) {
						/* lock in the chosen instrument and unlock the item search */
						$('#instrumentId').val(ui.item.id);
						$('#instrumentNameHidden').val(ui.item.value);
						$(this).removeClass('error');
						$('#itemSearch').prop('disabled', false).val('').attr('placeholder', 'Search items...');
						$('#addItemLink').show();
						clearItemSelection();
					},
					change: function(event, ui) {
						/* if the user typed something but didn't pick a suggestion, flag the field as invalid */
						if (!ui.item) {
							if ($(this).val().trim() !== '') {
								$(this).addClass('error');
							}
							clearInstrumentSelection();
						}
					}
				});

				/* show full instrument list on focus without requiring any typed characters */
				$('#instrumentSearch').on('focus', function() {
					$(this).autocomplete('search', '');
				});

				/* clear instrument on manual erase */
				$('#instrumentSearch').on('input', function() {
					if ($(this).val().trim() === '') {
						$(this).removeClass('error');
						clearInstrumentSelection();
					}
				});

				/* ----- instrument item autocomplete ----- */
				$('#itemSearch').autocomplete({
					source: function(request, response) {
						/* scope item search to the currently selected instrument */
						$.getJSON('ajaxapi.php', { action: 'searchinstrumentitems', term: request.term, instrumentid: $('#instrumentId').val() }, response);
					},
					minLength: 0,
					select: function(event, ui) {
						/* lock in the chosen item and mirror its name into the read-only observation name field */
						$('#instrumentitemId').val(ui.item.id);
						$('#observationName').val(ui.item.value).prop('readonly', true).css('background-color', '#f8f8f8');
						$(this).removeClass('error');
					},
					change: function(event, ui) {
						/* if the user typed something but didn't pick a suggestion, flag the field as invalid */
						if (!ui.item) {
							if ($(this).val().trim() !== '') {
								$(this).addClass('error');
							}
							clearItemSelection();
						}
					}
				});

				/* show full item list for the selected instrument on focus */
				$('#itemSearch').on('focus', function() {
					$(this).autocomplete('search', '');
				});

				/* clear item on manual erase */
				$('#itemSearch').on('input', function() {
					if ($(this).val().trim() === '') {
						$(this).removeClass('error');
						clearItemSelection();
					}
				});

				/* ----- form validation ----- */
				$('#addObsForm').on('submit', function(e) {
					const instrText = $('#instrumentSearch').val().trim();
					const instrId   = $('#instrumentId').val();
					const itemText  = $('#itemSearch').val().trim();
					const itemId    = $('#instrumentitemId').val();

					/* block form submit if the instrument field has unresolved free text (no matching DB record selected) */
					if (instrText !== '' && instrId === '') {
						e.preventDefault();
						$('#instrumentSearch').addClass('error');
						alert('Please select an existing instrument from the dropdown, or use "+ Add new instrument" to create one.');
						return false;
					}
					/* block form submit if an instrument is chosen but no item has been selected */
					if (instrId !== '' && itemId === '') {
						e.preventDefault();
						$('#itemSearch').addClass('error');
						alert('An instrument is selected — please also select an observation item, or use "+ Add new item" to create one.');
						return false;
					}
				});

				/* ----- add instrument modal ----- */
				$('#addInstrumentLink').on('click', function(e) {
					e.preventDefault();
					/* pre-fill name with whatever the user already typed in the search field */
					$('#newInstrumentName').val($('#instrumentSearch').val());
					$('#newInstrumentNotes').val('');
					$('#addInstrumentError').hide();
					$('#addInstrumentModal').modal({
						closable: false,
						onApprove: function() {
							const name  = $('#newInstrumentName').val().trim();
							const notes = $('#newInstrumentNotes').val().trim();
							if (name === '') {
								$('#addInstrumentError').text('Instrument name is required.').show();
								return false;
							}
							$.post('ajaxapi.php', { action: 'addinstrument', instrumentname: name, instrumentnotes: notes, projectid: PROJECT_ID }, function(data) {
								if (data.error) {
									$('#addInstrumentError').text(data.error).show();
									return;
								}
								/* wire the new instrument into the form as if the user had picked it from autocomplete */
								$('#instrumentSearch').val(data.instrument_name).removeClass('error');
								$('#instrumentId').val(data.instrument_id);
								$('#instrumentNameHidden').val(data.instrument_name);
								$('#itemSearch').prop('disabled', false).val('').attr('placeholder', 'Search items...');
								$('#addItemLink').show();
								clearItemSelection();
								$('#addInstrumentModal').modal('hide');
							}, 'json').fail(function() {
								$('#addInstrumentError').text('Server error — please try again.').show();
							});
							return false;
						}
					}).modal('show');
				});

				/* ----- add item modal ----- */
				$('#addItemLink').on('click', function(e) {
					e.preventDefault();
					/* pre-fill name with whatever the user already typed in the item search field */
					$('#newItemName').val($('#itemSearch').val());
					$('#newItemType').val('string');
					$('#newItemNotes').val('');
					$('#addItemError').hide();
					/* show the parent instrument name in the modal heading for context */
					$('#addItemModalInstrumentName').text($('#instrumentSearch').val());
					$('#addItemModal').modal({
						closable: false,
						onApprove: function() {
							const name  = $('#newItemName').val().trim();
							const type  = $('#newItemType').val();
							const notes = $('#newItemNotes').val().trim();
							const instrId = $('#instrumentId').val();
							if (name === '') {
								$('#addItemError').text('Item name is required.').show();
								return false;
							}
							$.post('ajaxapi.php', { action: 'addinstrumentitem', itemname: name, itemtype: type, itemnotes: notes, instrumentid: instrId }, function(data) {
								if (data.error) {
									$('#addItemError').text(data.error).show();
									return;
								}
								/* wire the new item into the form and mirror its name into the observation name field */
								$('#itemSearch').val(data.item_name).removeClass('error');
								$('#instrumentitemId').val(data.instrumentitem_id);
								$('#observationName').val(data.item_name).prop('readonly', true).css('background-color', '#f8f8f8');
								$('#addItemModal').modal('hide');
							}, 'json').fail(function() {
								$('#addItemError').text('Server error — please try again.').show();
							});
							return false;
						}
					}).modal('show');
				});
			});
		</script>
		<?
	}
?>


<? include("footer.php") ?>
