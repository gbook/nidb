<?
 // ------------------------------------------------------------------------------
 // NiDB remoteimportmapping.php
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
		<title>NiDB - Remote Import Mapping</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	$action    = GetVariable("action");
	$projectid = GetVariable("projectid");

	if ($projectid == "") {
		Error("No project specified");
		require "footer.php";
		exit;
	}

	switch ($action) {
		case 'bulkaddavicenna':
			BulkAddAvicenna((int)$projectid, GetVariable("csvtext"));
			break;
		default:
			DisplayMappingList((int)$projectid);
	}


	/* --------------------------------------------------- */
	/* ------- ClosestMatch ------------------------------ */
	/* --------------------------------------------------- */
	function ClosestMatch($needle, $haystack) {
		if (empty($haystack)) return null;
		$best     = null;
		$bestDist = PHP_INT_MAX;
		$lower    = strtolower($needle);
		foreach ($haystack as $candidate) {
			$dist = levenshtein($lower, strtolower($candidate));
			if ($dist < $bestDist) {
				$bestDist = $dist;
				$best     = $candidate;
			}
		}
		return $best;
	}


	/* --------------------------------------------------- */
	/* ------- BulkAddAvicenna -------------------------- */
	/* --------------------------------------------------- */
	function BulkAddAvicenna($projectid, $csvtext) {
		$csvtext = trim($csvtext);
		if ($csvtext === '') {
			Error("No CSV data provided");
			DisplayMappingList($projectid);
			return;
		}

		// Split into non-empty lines
		$lines = array_values(array_filter(array_map('trim', explode("\n", $csvtext)), function($l) { return $l !== ''; }));

		if (count($lines) < 2) {
			Error("CSV must have a header row and at least one data row");
			DisplayMappingList($projectid);
			return;
		}

		// Normalize header: lowercase, strip spaces
		$header = array_map(function($h) { return strtolower(str_replace(' ', '', trim($h))); }, str_getcsv($lines[0]));

		// Verify required columns are present
		$required = ['avicennaquestion', 'avicennavariable', 'nidbinstrument', 'nidbvariable'];
		$missing  = array_diff($required, $header);
		if (!empty($missing)) {
			Error("Missing required columns: " . implode(', ', $missing));
			DisplayMappingList($projectid);
			return;
		}

		$colIdx  = array_flip($header);
		$results = [];

		foreach (array_slice($lines, 1) as $i => $line) {
			$values = str_getcsv($line);
			while (count($values) < count($header)) $values[] = '';

			$avicennaQuestion = trim($values[$colIdx['avicennaquestion']] ?? '');
			$avicennaVariable = trim($values[$colIdx['avicennavariable']] ?? '');
			$nidbInstrument   = trim($values[$colIdx['nidbinstrument']] ?? '');
			$nidbVariable     = trim($values[$colIdx['nidbvariable']] ?? '');
			$dateFromField    = isset($colIdx['datefromfield']) ? (int)trim($values[$colIdx['datefromfield']] ?? '0') : 0;
			$canRepeat        = isset($colIdx['canrepeat'])     ? (int)trim($values[$colIdx['canrepeat']] ?? '0')     : 0;

			$result = [
				'row'               => $i + 2,
				'avicenna_question' => $avicennaQuestion,
				'avicenna_variable' => $avicennaVariable,
				'nidb_instrument'   => $nidbInstrument,
				'nidb_variable'     => $nidbVariable,
				'status'            => '',
				'message'           => '',
			];

			// Validate: at least one avicenna key, plus both nidb fields
			if ($avicennaQuestion === '' && $avicennaVariable === '') {
				$result['status']  = 'error';
				$result['message'] = 'avicennaquestion or avicennavariable must be provided';
				$results[] = $result;
				continue;
			}
			if ($nidbInstrument === '') {
				$result['status']  = 'error';
				$result['message'] = 'nidbinstrument is required';
				$results[] = $result;
				continue;
			}
			if ($nidbVariable === '') {
				$result['status']  = 'error';
				$result['message'] = 'nidbvariable is required';
				$results[] = $result;
				continue;
			}

			// Look up instrument by name within this project
			$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrument_id FROM instruments WHERE project_id = ? AND instrument_name = ?");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $nidbInstrument);
			$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$instrRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$instrRow) {
				// Fetch all instrument names for this project to suggest the closest match
				$stmt2 = mysqli_prepare($GLOBALS['linki'], "SELECT instrument_name FROM instruments WHERE project_id = ?");
				mysqli_stmt_bind_param($stmt2, 'i', $projectid);
				$r2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__);
				$allInstruments = [];
				while ($row2 = mysqli_fetch_array($r2, MYSQLI_ASSOC)) $allInstruments[] = $row2['instrument_name'];
				mysqli_stmt_close($stmt2);
				$suggestion = ClosestMatch($nidbInstrument, $allInstruments);
				$result['status']  = 'error';
				$result['message'] = "Instrument not found: \"$nidbInstrument\""
				                   . ($suggestion !== null ? "; did you mean \"$suggestion\"?" : '');
				$results[] = $result;
				continue;
			}
			$instrumentId = (int)$instrRow['instrument_id'];

			// Look up variable by name within that instrument
			$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrumentitem_id FROM instrument_items WHERE instrument_id = ? AND item_name = ?");
			mysqli_stmt_bind_param($stmt, 'is', $instrumentId, $nidbVariable);
			$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$varRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$varRow) {
				// Fetch all variable names for this instrument to suggest the closest match
				$stmt2 = mysqli_prepare($GLOBALS['linki'], "SELECT item_name FROM instrument_items WHERE instrument_id = ?");
				mysqli_stmt_bind_param($stmt2, 'i', $instrumentId);
				$r2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__);
				$allVars = [];
				while ($row2 = mysqli_fetch_array($r2, MYSQLI_ASSOC)) $allVars[] = $row2['item_name'];
				mysqli_stmt_close($stmt2);
				$suggestion = ClosestMatch($nidbVariable, $allVars);
				$result['status']  = 'error';
				$result['message'] = "Variable not found: \"$nidbVariable\" in instrument \"$nidbInstrument\""
				                   . ($suggestion !== null ? "; did you mean \"$suggestion\"?" : '');
				$results[] = $result;
				continue;
			}
			$variableId = (int)$varRow['instrumentitem_id'];

			$avicennaQuestionVal = is_numeric($avicennaQuestion) ? (int)$avicennaQuestion : null;
			$avicennaVariableVal = $avicennaVariable !== '' ? $avicennaVariable : null;
			$fdf = $dateFromField ? 1 : 0;
			$fcr = $canRepeat     ? 1 : 0;

			// Check for existing mapping (NULL-safe equals <=> handles null values)
			$stmt = mysqli_prepare($GLOBALS['linki'],
				"SELECT remoteimportmapping_id FROM remoteimport_mapping
				 WHERE project_id = ? AND source_type = 'avicenna'
				   AND avicenna_question <=> ? AND avicenna_variable <=> ?");
			mysqli_stmt_bind_param($stmt, 'iis', $projectid, $avicennaQuestionVal, $avicennaVariableVal);
			$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$existingRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if ($existingRow) {
				// Update the existing mapping
				$existingId = (int)$existingRow['remoteimportmapping_id'];
				$stmt = mysqli_prepare($GLOBALS['linki'],
					"UPDATE remoteimport_mapping SET nidb_instrument=?, nidb_variable=?, flag_date_from_field=?, flag_can_repeat=? WHERE remoteimportmapping_id=?");
				mysqli_stmt_bind_param($stmt, 'iiiii', $instrumentId, $variableId, $fdf, $fcr, $existingId);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
				$result['status'] = 'updated';
			} else {
				// Insert a new mapping
				$stmt = mysqli_prepare($GLOBALS['linki'],
					"INSERT INTO remoteimport_mapping (project_id, source_type, avicenna_question, avicenna_variable, nidb_instrument, nidb_variable, flag_date_from_field, flag_can_repeat) VALUES (?, 'avicenna', ?, ?, ?, ?, ?, ?)");
				mysqli_stmt_bind_param($stmt, 'iisiiii', $projectid, $avicennaQuestionVal, $avicennaVariableVal, $instrumentId, $variableId, $fdf, $fcr);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
				$result['status'] = 'added';
			}

			$results[] = $result;
		}

		// Count outcomes for summary label
		$nAdded   = count(array_filter($results, function($r) { return $r['status'] === 'added'; }));
		$nUpdated = count(array_filter($results, function($r) { return $r['status'] === 'updated'; }));
		$nErrors  = count(array_filter($results, function($r) { return $r['status'] === 'error'; }));
		?>
		<h3 class="ui header">Bulk import results
			<div class="sub header">
				<?php if ($nAdded)   { ?><span class="ui tiny green  label"><?= $nAdded ?>   added</span><?php } ?>
				<?php if ($nUpdated) { ?><span class="ui tiny blue   label"><?= $nUpdated ?> updated</span><?php } ?>
				<?php if ($nErrors)  { ?><span class="ui tiny red    label"><?= $nErrors ?>  errors</span><?php } ?>
			</div>
		</h3>
		<table class="ui compact small table">
			<thead>
				<tr>
					<th>Row</th>
					<th>Avicenna Q#</th>
					<th>Avicenna Variable</th>
					<th>NiDB Instrument</th>
					<th>NiDB Variable</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($results as $res) { ?>
				<tr class="<?= $res['status'] === 'error' ? 'negative' : ($res['status'] === 'added' ? 'positive' : '') ?>">
					<td><?= $res['row'] ?></td>
					<td><?= htmlspecialchars($res['avicenna_question']) ?></td>
					<td><?= htmlspecialchars($res['avicenna_variable']) ?></td>
					<td><?= htmlspecialchars($res['nidb_instrument']) ?></td>
					<td><?= htmlspecialchars($res['nidb_variable']) ?></td>
					<td>
						<?php if ($res['status'] === 'added') { ?>
							<span class="ui tiny green label"><i class="plus icon"></i> added</span>
						<?php } elseif ($res['status'] === 'updated') { ?>
							<span class="ui tiny blue label"><i class="check icon"></i> updated</span>
						<?php } else { ?>
							<span class="ui tiny red label"><i class="exclamation icon"></i> error</span>
							<?php if ($res['message']) { ?> <?= htmlspecialchars($res['message']) ?><?php } ?>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php if ($nErrors > 0) { ?>
		<div style="margin-top:1em">
			<h4 class="ui header">Fix and resubmit</h4>
			<form method="POST" action="remoteimportmapping.php">
				<input type="hidden" name="action" value="bulkaddavicenna">
				<input type="hidden" name="projectid" value="<?= $projectid ?>">
				<textarea name="csvtext" rows="12"
				          style="font-family:monospace;font-size:0.85em;width:100%;margin-bottom:0.5em"><?= htmlspecialchars($csvtext) ?></textarea>
				<button type="submit" class="ui primary button"><i class="upload icon"></i> Import</button>
			</form>
		</div>
		<?php } ?>
		<?php

		DisplayMappingList($projectid);
	}


	/* --------------------------------------------------- */
	/* ------- DisplayMappingList ------------------------ */
	/* --------------------------------------------------- */
	function DisplayMappingList($projectid) {

		// Load all instruments for this project — used in JS for the instrument dropdown
		$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrument_id, instrument_name FROM instruments WHERE project_id = ? ORDER BY instrument_name");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$instruments = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$instruments[] = ['id' => (int)$row['instrument_id'], 'name' => $row['instrument_name']];
		}
		mysqli_stmt_close($stmt);

		// Load Avicenna mappings
		$stmt = mysqli_prepare($GLOBALS['linki'],
			"SELECT m.remoteimportmapping_id, m.avicenna_question, m.avicenna_variable,
			        m.nidb_instrument, m.nidb_variable, m.flag_date_from_field, m.flag_can_repeat,
			        i.instrument_name, ii.item_name
			 FROM remoteimport_mapping m
			 LEFT JOIN instruments i ON i.instrument_id = m.nidb_instrument
			 LEFT JOIN instrument_items ii ON ii.instrumentitem_id = m.nidb_variable
			 WHERE m.project_id = ? AND m.source_type = 'avicenna'
			 ORDER BY m.avicenna_question, m.avicenna_variable");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$avicennaRows = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$avicennaRows[] = [
				'id'                   => (int)$row['remoteimportmapping_id'],
				'avicenna_question'    => (int)$row['avicenna_question'],
				'avicenna_variable'    => (string)$row['avicenna_variable'],
				'nidb_instrument_id'   => (int)$row['nidb_instrument'],
				'nidb_instrument'      => (string)$row['instrument_name'],
				'nidb_variable_id'     => (int)$row['nidb_variable'],
				'nidb_variable'        => (string)$row['item_name'],
				'flag_date_from_field' => (int)$row['flag_date_from_field'],
				'flag_can_repeat'      => (int)$row['flag_can_repeat'],
			];
		}
		mysqli_stmt_close($stmt);

		// Load REDCap mappings
		$stmt = mysqli_prepare($GLOBALS['linki'],
			"SELECT m.remoteimportmapping_id, m.redcap_arm, m.redcap_event, m.redcap_form,
			        m.redcap_field, m.redcap_datatype, m.redcap_datefield,
			        m.nidb_instrument, m.nidb_variable, m.flag_date_from_field, m.flag_can_repeat,
			        i.instrument_name, ii.item_name
			 FROM remoteimport_mapping m
			 LEFT JOIN instruments i ON i.instrument_id = m.nidb_instrument
			 LEFT JOIN instrument_items ii ON ii.instrumentitem_id = m.nidb_variable
			 WHERE m.project_id = ? AND m.source_type = 'redcap'
			 ORDER BY m.redcap_arm, m.redcap_event, m.redcap_form, m.redcap_field");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$redcapRows = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$redcapRows[] = [
				'id'                   => (int)$row['remoteimportmapping_id'],
				'redcap_arm'           => (string)$row['redcap_arm'],
				'redcap_event'         => (string)$row['redcap_event'],
				'redcap_form'          => (string)$row['redcap_form'],
				'redcap_field'         => (string)$row['redcap_field'],
				'redcap_datatype'      => (string)$row['redcap_datatype'],
				'redcap_datefield'     => (string)$row['redcap_datefield'],
				'nidb_instrument_id'   => (int)$row['nidb_instrument'],
				'nidb_instrument'      => (string)$row['instrument_name'],
				'nidb_variable_id'     => (int)$row['nidb_variable'],
				'nidb_variable'        => (string)$row['item_name'],
				'flag_date_from_field' => (int)$row['flag_date_from_field'],
				'flag_can_repeat'      => (int)$row['flag_can_repeat'],
			];
		}
		mysqli_stmt_close($stmt);
		?>
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-grid.css">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-theme-alpine.css">
		<style>
			.arrow-col-header { background: #444 !important; color: #fff; font-size: 2em; !important; }
		</style>

		<h2 class="ui header">Remote Import Mapping</h2>

		<!-- Tab menu: Avicenna | REDCap -->
		<div class="ui top attached tabular menu">
			<a class="active item" data-tab="avicenna"><i class="mobile alternate icon"></i> Avicenna</a>
			<a class="item" data-tab="redcap"><i class="red redhat icon"></i> REDCap</a>
		</div>

		<!-- Avicenna tab -->
		<div class="ui bottom attached active tab segment" data-tab="avicenna">
			<div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
				<button class="ui small primary button" onclick="openModal('avicenna')">
					<i class="plus icon"></i> Add mapping
				</button>
				<button class="ui small button" onclick="$('#bulkAddModal').modal('show')">
					<i class="list icon"></i> Bulk add
				</button>
				<input type="text" id="avicennaFilter" placeholder="Search..."
				       oninput="avicennaGridApi.setQuickFilter(this.value)"
				       style="padding:5px 8px;width:250px;border:1px solid #ccc;border-radius:4px">
			</div>
			<div id="avicennaGrid" class="ag-theme-alpine" style="height:500px;width:100%"></div>
		</div>

		<!-- REDCap tab -->
		<div class="ui bottom attached tab segment" data-tab="redcap">
			<div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
				<button class="ui small primary button" onclick="openModal('redcap')">
					<i class="plus icon"></i> Add mapping
				</button>
				<input type="text" id="redcapFilter" placeholder="Search..."
				       oninput="redcapGridApi.setQuickFilter(this.value)"
				       style="padding:5px 8px;width:250px;border:1px solid #ccc;border-radius:4px">
			</div>
			<div id="redcapGrid" class="ag-theme-alpine" style="height:500px;width:100%"></div>
		</div>

		<!-- Add/Edit mapping modal -->
		<div class="ui modal" id="mappingModal">
			<div class="header" id="modalTitle">Add mapping</div>
			<div class="content">
				<form class="ui form" id="mappingForm">
					<input type="hidden" id="modal_mappingid"   value="">
					<input type="hidden" id="modal_source_type" value="">

					<!-- Avicenna-only fields (hidden when source_type is redcap) -->
					<div id="avicenna_fields">
						<div class="two fields">
							<div class="field">
								<label>Question #</label>
								<input type="number" id="modal_avicenna_question" placeholder="Question number" min="1">
							</div>
							<div class="field">
								<label>Variable</label>
								<input type="text" id="modal_avicenna_variable" placeholder="Variable name">
							</div>
						</div>
					</div>

					<!-- REDCap-only fields (hidden when source_type is avicenna) -->
					<div id="redcap_fields">
						<div class="three fields">
							<div class="field">
								<label>Arm</label>
								<input type="text" id="modal_redcap_arm" placeholder="Arm">
							</div>
							<div class="field">
								<label>Event</label>
								<input type="text" id="modal_redcap_event" placeholder="Event">
							</div>
							<div class="field">
								<label>Form</label>
								<input type="text" id="modal_redcap_form" placeholder="Form (instrument)">
							</div>
						</div>
						<div class="three fields">
							<div class="field">
								<label>Field</label>
								<input type="text" id="modal_redcap_field" placeholder="Field name">
							</div>
							<div class="field">
								<label>Datatype</label>
								<select id="modal_redcap_datatype" class="ui fluid dropdown">
									<option value="">-- select --</option>
									<option value="text">text</option>
									<option value="notes">notes</option>
									<option value="radio">radio</option>
									<option value="dropdown">dropdown</option>
									<option value="checkbox">checkbox</option>
									<option value="calc">calc</option>
									<option value="slider">slider</option>
									<option value="descriptive">descriptive</option>
									<option value="file">file</option>
								</select>
							</div>
							<div class="field">
								<label>Date field</label>
								<input type="text" id="modal_redcap_datefield" placeholder="Field used for NiDB date">
							</div>
						</div>
					</div>

					<!-- NiDB instrument + variable (shared by both source types) -->
					<div class="two fields">
						<div class="field">
							<label>NiDB Instrument</label>
							<select id="modal_nidb_instrument" class="ui fluid dropdown"
							        onchange="loadInstrumentItems(this.value, null)">
								<option value="">-- select instrument --</option>
								<?php foreach ($instruments as $inst) { ?>
								<option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['name']) ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="field">
							<label>NiDB Variable</label>
							<select id="modal_nidb_variable" class="ui fluid dropdown">
								<option value="">-- select instrument first --</option>
							</select>
						</div>
					</div>

					<!-- Flags as inline checkboxes -->
					<div class="fields">
						<div class="field">
							<div class="ui checkbox">
								<input type="checkbox" id="modal_flag_date_from_field">
								<label>Date from field</label>
							</div>
						</div>
						<div class="field">
							<div class="ui checkbox">
								<input type="checkbox" id="modal_flag_can_repeat">
								<label>Can repeat</label>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary button" onclick="saveMapping()">
					<i class="save icon"></i> Save
				</div>
			</div>
		</div>

		<!-- Bulk add Avicenna mappings modal -->
		<div class="ui modal" id="bulkAddModal">
			<div class="header"><i class="list icon"></i> Bulk add Avicenna mappings</div>
			<div class="content">
				<form id="bulkForm" method="POST" action="remoteimportmapping.php">
					<input type="hidden" name="action" value="bulkaddavicenna">
					<input type="hidden" name="projectid" value="<?= $projectid ?>">
					<div class="ui form">
						<div class="field">
							<label>CSV data</label>
							<p style="color:#666;font-size:0.9em">
								First line must be a header row. Required columns: <code>avicennaquestion</code>, <code>avicennavariable</code>, <code>nidbinstrument</code>, <code>nidbvariable</code>.
								Optional: <code>datefromfield</code>, <code>canrepeat</code>. Columns may be in any order. Values may contain spaces.
							</p>
							<textarea name="csvtext" rows="12"
							          style="font-family:monospace;font-size:0.85em;width:100%"
							          placeholder="avicennaquestion,avicennavariable,nidbinstrument,nidbvariable,datefromfield,canrepeat"></textarea>
						</div>
					</div>
				</form>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<button class="ui primary button" onclick="document.getElementById('bulkForm').submit()">
					<i class="upload icon"></i> Import
				</button>
			</div>
		</div>

		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@31/dist/ag-grid-community.min.js"></script>
		<script>
		// Data injected at page render time
		const projectId    = <?= $projectid ?>;
		const avicennaData = <?= json_encode($avicennaRows) ?>;
		const redcapData   = <?= json_encode($redcapRows) ?>;

		// Grid API references; assigned after createGrid() below
		let avicennaGridApi = null;
		let redcapGridApi   = null;

		// ── Flag checkbox renderer ────────────────────────────────────────
		// Returns a cellRenderer that saves a boolean flag via AJAX on change
		function flagRenderer(flagName) {
			return params => {
				const cb    = document.createElement('input');
				cb.type     = 'checkbox';
				cb.checked  = !!params.value;
				cb.style.cursor = 'pointer';
				cb.addEventListener('change', () => {
					fetch('ajaxapi.php?action=updatemappingflag'
					    + '&mappingid=' + params.data.id
					    + '&flagname='  + encodeURIComponent(flagName)
					    + '&value='     + (cb.checked ? 1 : 0))
						.then(r => r.json())
						.then(resp => {
							if (!resp.ok) {
								alert('Error saving flag: ' + (resp.error || 'unknown'));
								cb.checked = !cb.checked; // revert on error
							}
						})
						.catch(() => { alert('Network error saving flag'); cb.checked = !cb.checked; });
				});
				return cb;
			};
		}

		// ── Edit/Delete button renderer ───────────────────────────────────
		// getGridApi is a thunk () => gridApi so it can be called after the grid exists
		function actionRenderer(getGridApi, sourceType) {
			return params => {
				const div = document.createElement('div');
				div.style.display = 'flex';
				div.style.alignItems = 'center';
				div.style.justifyContent = 'center';

				// Edit opens the modal pre-filled with this row's data
				const editBtn     = document.createElement('button');
				editBtn.className = 'ui mini compact button';
				editBtn.innerHTML = '<i class="edit icon"></i>';
				editBtn.title     = 'Edit';
				editBtn.onclick   = () => openModalForEdit(sourceType, params.data);
				div.appendChild(editBtn);

				// Delete confirms, then removes the row via AJAX and from the grid
				const delBtn          = document.createElement('button');
				delBtn.className      = 'ui mini compact red button';
				delBtn.innerHTML      = '<i class="trash icon"></i>';
				delBtn.title          = 'Delete';
				delBtn.onclick        = () => {
					if (!confirm('Delete this mapping?')) return;
					fetch('ajaxapi.php?action=deletemapping&mappingid=' + params.data.id)
						.then(r => r.json())
						.then(resp => {
							if (resp.ok) {
								getGridApi().applyTransaction({ remove: [params.data] });
							} else {
								alert('Error deleting: ' + (resp.error || 'unknown'));
							}
						})
						.catch(() => alert('Network error'));
				};
				div.appendChild(delBtn);
				return div;
			};
		}

		// Arrow separator column shared by both grids
		const arrowCol = {
			headerName: '→', sortable: false, filter: false, width: 65,
			headerClass: 'arrow-col-header',
			cellStyle: { background: '#eee', 'justify-content': 'center', 'display': 'flex', 'align-items': 'center' },
			cellRenderer: () => {
				const span = document.createElement('span');
				span.textContent  = '→';
				span.style.fontSize = '2em';
				return span;
			}
		};

		// ── Avicenna grid ─────────────────────────────────────────────────
		avicennaGridApi = agGrid.createGrid(
			document.getElementById('avicennaGrid'),
			{
				columnDefs: [
					{ field: 'avicenna_question', headerName: 'Question #',     sortable: true, filter: true, width: 110 },
					{ field: 'avicenna_variable', headerName: 'Variable',        sortable: true, filter: true, flex: 1 },
					arrowCol,
					{ field: 'nidb_instrument',   headerName: 'NiDB Instrument', sortable: true, filter: true, flex: 1 },
					{ field: 'nidb_variable',     headerName: 'NiDB Variable',   sortable: true, filter: true, flex: 1 },
					{
						field: 'flag_date_from_field',
						headerName: 'Date from field',
						width: 140,
						cellRenderer: flagRenderer('flag_date_from_field'),
						cellStyle: { 'justify-content': 'center', 'display': 'flex', 'align-items': 'middle' }
					},
					{
						field: 'flag_can_repeat',
						headerName: 'Can repeat',
						width: 110,
						cellRenderer: flagRenderer('flag_can_repeat'),
						cellStyle: { 'justify-content': 'center', 'display': 'flex', 'align-items': 'middle' }
					},
					{
						headerName: '',
						sortable: false,
						filter: false,
						width: 100,
						cellRenderer: actionRenderer(() => avicennaGridApi, 'avicenna'),
						cellStyle: { 'justify-content': 'center', 'display': 'flex', 'align-items': 'middle' }
					},
				],
				rowData:       avicennaData,
				defaultColDef: { resizable: true },
				getRowId:      params => String(params.data.id),
			}
		);

		// ── REDCap grid ───────────────────────────────────────────────────
		redcapGridApi = agGrid.createGrid(
			document.getElementById('redcapGrid'),
			{
				columnDefs: [
					{ field: 'redcap_arm',        headerName: 'Arm',               sortable: true, filter: true, width: 110 },
					{ field: 'redcap_event',      headerName: 'Event',             sortable: true, filter: true, width: 140 },
					{ field: 'redcap_form',       headerName: 'Form',              sortable: true, filter: true, flex: 1 },
					{ field: 'redcap_field',      headerName: 'Field',             sortable: true, filter: true, flex: 1 },
					{ field: 'redcap_datatype',   headerName: 'Datatype',          sortable: true, filter: true, width: 110 },
					{ field: 'redcap_datefield',  headerName: 'Date field',        sortable: true, filter: true, width: 120 },
					arrowCol,
					{ field: 'nidb_instrument',   headerName: 'NiDB Instrument',   sortable: true, filter: true, flex: 1 },
					{ field: 'nidb_variable',     headerName: 'NiDB Variable',     sortable: true, filter: true, flex: 1 },
					{
						field: 'flag_date_from_field',
						headerName: 'Date from field',
						width: 140,
						cellRenderer: flagRenderer('flag_date_from_field'),
						cellStyle: { 'justify-content': 'center', 'display': 'flex', 'align-items': 'middle' }
					},
					{
						field: 'flag_can_repeat',
						headerName: 'Can repeat',
						width: 110,
						cellRenderer: flagRenderer('flag_can_repeat'),
						cellStyle: { 'justify-content': 'center', 'display': 'flex', 'align-items': 'middle' }
					},
					{
						headerName: '',
						sortable: false,
						filter: false,
						width: 120,
						cellRenderer: actionRenderer(() => redcapGridApi, 'redcap'),
						cellStyle: { 'justify-content': 'center', 'display': 'flex', 'align-items': 'middle' }
					},
				],
				rowData:       redcapData,
				defaultColDef: { resizable: true },
				getRowId:      params => String(params.data.id),
			}
		);

		// ── Modal: open for a new mapping ─────────────────────────────────
		function openModal(sourceType) {
			clearModal();
			document.getElementById('modal_source_type').value = sourceType;
			document.getElementById('modalTitle').textContent  = 'Add ' + sourceType + ' mapping';
			toggleSourceFields(sourceType);
			$('#mappingModal').modal('show');
		}

		// ── Modal: open pre-filled for editing an existing row ────────────
		function openModalForEdit(sourceType, data) {
			clearModal();
			document.getElementById('modal_mappingid').value   = data.id;
			document.getElementById('modal_source_type').value = sourceType;
			document.getElementById('modalTitle').textContent  = 'Edit ' + sourceType + ' mapping';
			toggleSourceFields(sourceType);

			if (sourceType === 'avicenna') {
				document.getElementById('modal_avicenna_question').value = data.avicenna_question || '';
				document.getElementById('modal_avicenna_variable').value = data.avicenna_variable || '';
			} else {
				document.getElementById('modal_redcap_arm').value       = data.redcap_arm       || '';
				document.getElementById('modal_redcap_event').value     = data.redcap_event     || '';
				document.getElementById('modal_redcap_form').value      = data.redcap_form      || '';
				document.getElementById('modal_redcap_field').value     = data.redcap_field     || '';
				document.getElementById('modal_redcap_datatype').value  = data.redcap_datatype  || '';
				document.getElementById('modal_redcap_datefield').value = data.redcap_datefield || '';
			}

			// Set instrument, then fetch its items and pre-select the saved variable
			document.getElementById('modal_nidb_instrument').value = data.nidb_instrument_id || '';
			if (data.nidb_instrument_id) {
				loadInstrumentItems(data.nidb_instrument_id, data.nidb_variable_id);
			}

			document.getElementById('modal_flag_date_from_field').checked = !!data.flag_date_from_field;
			document.getElementById('modal_flag_can_repeat').checked       = !!data.flag_can_repeat;

			$('#mappingModal').modal('show');
		}

		// ── Reset all form fields to empty ────────────────────────────────
		function clearModal() {
			['modal_mappingid','modal_avicenna_question','modal_avicenna_variable',
			 'modal_redcap_arm','modal_redcap_event','modal_redcap_form',
			 'modal_redcap_field','modal_redcap_datatype','modal_redcap_datefield',
			 'modal_nidb_instrument'].forEach(id => {
				document.getElementById(id).value = '';
			});
			document.getElementById('modal_nidb_variable').innerHTML =
				'<option value="">-- select instrument first --</option>';
			document.getElementById('modal_flag_date_from_field').checked = false;
			document.getElementById('modal_flag_can_repeat').checked      = false;
		}

		// ── Show/hide source-type-specific field groups ───────────────────
		function toggleSourceFields(sourceType) {
			document.getElementById('avicenna_fields').style.display =
				(sourceType === 'avicenna') ? '' : 'none';
			document.getElementById('redcap_fields').style.display =
				(sourceType === 'redcap') ? '' : 'none';
		}

		// ── Load instrument items via AJAX ────────────────────────────────
		// Called when instrument dropdown changes; preselectId pre-selects a variable
		function loadInstrumentItems(instrumentId, preselectId) {
			const varSelect = document.getElementById('modal_nidb_variable');
			varSelect.innerHTML = '<option value="">Loading...</option>';

			if (!instrumentId) {
				varSelect.innerHTML = '<option value="">-- select instrument first --</option>';
				return;
			}

			fetch('ajaxapi.php?action=getinstrumentitems&instrumentid=' + encodeURIComponent(instrumentId))
				.then(r => r.json())
				.then(items => {
					varSelect.innerHTML = '<option value="">-- select variable --</option>';
					items.forEach(item => {
						const opt       = document.createElement('option');
						opt.value       = item.id;
						opt.textContent = item.name;
						if (preselectId && item.id == preselectId) opt.selected = true;
						varSelect.appendChild(opt);
					});
				})
				.catch(() => {
					varSelect.innerHTML = '<option value="">Error loading items</option>';
				});
		}

		// ── Save mapping via AJAX (no page reload) ────────────────────────
		function saveMapping() {
			const sourceType = document.getElementById('modal_source_type').value;
			const mappingId  = document.getElementById('modal_mappingid').value;

			// Build the params object with shared fields
			const params = {
				action:               'savemapping',
				projectid:            projectId,
				mappingid:            mappingId,
				source_type:          sourceType,
				nidb_instrument:      document.getElementById('modal_nidb_instrument').value,
				nidb_variable:        document.getElementById('modal_nidb_variable').value,
				flag_date_from_field: document.getElementById('modal_flag_date_from_field').checked ? 1 : 0,
				flag_can_repeat:      document.getElementById('modal_flag_can_repeat').checked      ? 1 : 0,
			};

			// Add source-specific fields
			if (sourceType === 'avicenna') {
				params.avicenna_question = document.getElementById('modal_avicenna_question').value;
				params.avicenna_variable = document.getElementById('modal_avicenna_variable').value;
			} else {
				params.redcap_arm       = document.getElementById('modal_redcap_arm').value;
				params.redcap_event     = document.getElementById('modal_redcap_event').value;
				params.redcap_form      = document.getElementById('modal_redcap_form').value;
				params.redcap_field     = document.getElementById('modal_redcap_field').value;
				params.redcap_datatype  = document.getElementById('modal_redcap_datatype').value;
				params.redcap_datefield = document.getElementById('modal_redcap_datefield').value;
			}

			// POST as application/x-www-form-urlencoded
			const body = Object.keys(params)
				.map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
				.join('&');

			fetch('ajaxapi.php', {
				method:  'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			})
			.then(r => r.json())
			.then(resp => {
				if (!resp.ok) {
					alert('Error saving: ' + (resp.error || 'unknown'));
					return;
				}

				// Build the full row object for the grid from form values
				const instrEl = document.getElementById('modal_nidb_instrument');
				const varEl   = document.getElementById('modal_nidb_variable');
				const rowData = {
					id:                   resp.mappingid,
					nidb_instrument_id:   parseInt(params.nidb_instrument) || 0,
					nidb_instrument:      instrEl.options[instrEl.selectedIndex]?.text || '',
					nidb_variable_id:     parseInt(params.nidb_variable)   || 0,
					nidb_variable:        varEl.options[varEl.selectedIndex]?.text     || '',
					flag_date_from_field: parseInt(params.flag_date_from_field),
					flag_can_repeat:      parseInt(params.flag_can_repeat),
				};

				if (sourceType === 'avicenna') {
					Object.assign(rowData, {
						avicenna_question: parseInt(params.avicenna_question) || 0,
						avicenna_variable: params.avicenna_variable,
					});
					if (mappingId) {
						// Update the existing row in place
						avicennaGridApi.forEachNode(node => {
							if (node.data && node.data.id == mappingId) node.setData(rowData);
						});
					} else {
						avicennaGridApi.applyTransaction({ add: [rowData] });
					}
				} else {
					Object.assign(rowData, {
						redcap_arm:       params.redcap_arm,
						redcap_event:     params.redcap_event,
						redcap_form:      params.redcap_form,
						redcap_field:     params.redcap_field,
						redcap_datatype:  params.redcap_datatype,
						redcap_datefield: params.redcap_datefield,
					});
					if (mappingId) {
						redcapGridApi.forEachNode(node => {
							if (node.data && node.data.id == mappingId) node.setData(rowData);
						});
					} else {
						redcapGridApi.applyTransaction({ add: [rowData] });
					}
				}

				$('#mappingModal').modal('hide');
			})
			.catch(() => alert('Network error saving mapping'));
		}

		// ── Semantic UI initialization ─────────────────────────────────────
		$('.tabular.menu .item').tab();
		$('.ui.checkbox').checkbox();
		</script>
		<?php
	}

require "footer.php";
?>
