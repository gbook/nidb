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
	require "redcap_functions.php";

	$action    = GetVariable("action");
	$projectid = GetVariable("projectid");
	$importid  = GetVariable("importid");

	if ($projectid == "") {
		Error("No project specified");
		require "footer.php";
		exit;
	}

	switch ($action) {
		case 'bulkaddavicenna':
			BulkAddAvicenna((int)$projectid, GetVariable("csvtext"), GetVariable("createinstruments") != "", GetVariable("replaceexisting") != "");
			break;
		default:
			DisplayMappingList((int)$projectid, (int)$importid);
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
	function BulkAddAvicenna($projectid, $csvtext, $createInstruments = false, $replaceExisting = false) {
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

		// Normalize header: lowercase, strip spaces and underscores (so e.g. "avicenna_datasource"
		// and "Avicenna Datasource" both match the expected "avicennadatasource")
		$header = array_map(function($h) { return strtolower(str_replace([' ', '_'], '', trim($h))); }, str_getcsv($lines[0]));

		// Verify required columns are present. A survey OR a datasource is required per row
		// (validated below), so at least one of those two columns must be present.
		$required = ['avicennavariable', 'avicennadatatype', 'nidbinstrument', 'nidbvariable'];
		$missing  = array_diff($required, $header);
		if (!empty($missing)) {
			Error("Missing required columns: " . implode(', ', $missing));
			DisplayMappingList($projectid);
			return;
		}

		// A survey or a datasource column must be present (one of the two is required per row)
		if (!in_array('avicennasurvey', $header) && !in_array('avicennadatasource', $header)) {
			Error("Missing required column: avicennasurvey or avicennadatasource");
			DisplayMappingList($projectid);
			return;
		}

		$colIdx  = array_flip($header);
		$results = [];

		// If replacing, remove all existing Avicenna mappings for this project before importing.
		// This only clears the project's remoteimport_mapping rows; instruments and instrument_items
		// are intentionally left untouched, as they may be shared by other projects.
		$nDeleted = 0;
		if ($replaceExisting) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "DELETE FROM remoteimport_mapping WHERE project_id = ? AND source_type = 'avicenna'");
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$nDeleted = mysqli_stmt_affected_rows($stmt);
			mysqli_stmt_close($stmt);
		}

		foreach (array_slice($lines, 1) as $i => $line) {
			$values = str_getcsv($line);
			while (count($values) < count($header)) $values[] = '';

			$avicennaQuestion = trim($values[$colIdx['avicennaquestion']] ?? '');
			$avicennaVariable = trim($values[$colIdx['avicennavariable']] ?? '');
			$avicennaSurvey     = isset($colIdx['avicennasurvey'])     ? trim($values[$colIdx['avicennasurvey']]     ?? '') : '';
			$avicennaDatasource = isset($colIdx['avicennadatasource']) ? trim($values[$colIdx['avicennadatasource']] ?? '') : '';
			$avicennaDatatype = strtolower(trim($values[$colIdx['avicennadatatype']] ?? ''));
			$nidbInstrument   = trim($values[$colIdx['nidbinstrument']] ?? '');
			$nidbVariable     = trim($values[$colIdx['nidbvariable']] ?? '');
			$importMetaRaw    = isset($colIdx['importmeta'])            ? trim($values[$colIdx['importmeta']] ?? '') : '';
			$importMeta       = $importMetaRaw === '' ? 1 : (int)$importMetaRaw;

			$result = [
				'row'               => $i + 2,
				'avicenna_question' => $avicennaQuestion,
				'avicenna_variable' => $avicennaVariable,
				'nidb_instrument'   => $nidbInstrument,
				'nidb_variable'     => $nidbVariable,
				'status'            => '',
				'message'           => '',
			];

			// Validate: exactly one of survey/datasource; variable + datatype; at least one avicenna key; both nidb fields
			if (($avicennaSurvey === '') === ($avicennaDatasource === '')) {
				$result['status']  = 'error';
				$result['message'] = ($avicennaSurvey === '')
					? 'avicennasurvey or avicennadatasource is required'
					: 'provide avicennasurvey OR avicennadatasource, not both';
				$results[] = $result;
				continue;
			}
			if ($avicennaDatatype === '') {
				$result['status']  = 'error';
				$result['message'] = 'avicennadatatype is required';
				$results[] = $result;
				continue;
			}
			// avicennadatatype is validated case-insensitively (normalized to lowercase above)
			if (!in_array($avicennaDatatype, ['enum','int','double','string','timeseries','image','csv','json','datetime'], true)) {
				$result['status']  = 'error';
				$result['message'] = "avicennadatatype must be one of enum, int, double, string, timeseries, image, csv, json, datetime (got \"$avicennaDatatype\")";
				$results[] = $result;
				continue;
			}
			if ($avicennaQuestion === '' && $avicennaVariable === '') {
				$result['status']  = 'error';
				$result['message'] = 'avicennaquestion or avicennavariable must be provided';
				$results[] = $result;
				continue;
			}
			// A blank avicennaquestion is stored as NULL, but a non-blank value must be a positive integer
			if ($avicennaQuestion !== '' && (!ctype_digit($avicennaQuestion) || (int)$avicennaQuestion < 1)) {
				$result['status']  = 'error';
				$result['message'] = "avicennaquestion must be a positive integer or blank (got \"$avicennaQuestion\")";
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

			// item_type for created/updated instrument_items — shares the (already-validated) enum with avicenna_datatype
			$itemType = $avicennaDatatype;
			$notes    = array();

			// Look up instrument by name within this project
			$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrument_id FROM instruments WHERE project_id = ? AND instrument_name = ?");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $nidbInstrument);
			$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$instrRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$instrRow) {
				if ($createInstruments) {
					// Create the instrument (notes left blank)
					$stmt = mysqli_prepare($GLOBALS['linki'], "INSERT INTO instruments (project_id, instrument_name) VALUES (?, ?)");
					mysqli_stmt_bind_param($stmt, 'is', $projectid, $nidbInstrument);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					$instrumentId = mysqli_insert_id($GLOBALS['linki']);
					mysqli_stmt_close($stmt);
					$notes[] = "created instrument \"$nidbInstrument\"";
				} else {
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
			} else {
				$instrumentId = (int)$instrRow['instrument_id'];
			}

			// Look up variable by name within that instrument
			$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrumentitem_id FROM instrument_items WHERE instrument_id = ? AND item_name = ?");
			mysqli_stmt_bind_param($stmt, 'is', $instrumentId, $nidbVariable);
			$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$varRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (!$varRow) {
				if ($createInstruments) {
					// Create the instrument item, appending to the end of the item order (notes left blank)
					$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT COALESCE(MAX(item_order), -1) + 1 AS next_order FROM instrument_items WHERE instrument_id = ?");
					mysqli_stmt_bind_param($stmt, 'i', $instrumentId);
					$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					$ordRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
					mysqli_stmt_close($stmt);
					$nextOrder = (int)$ordRow['next_order'];

					$stmt = mysqli_prepare($GLOBALS['linki'], "INSERT INTO instrument_items (instrument_id, item_name, item_order, item_type) VALUES (?, ?, ?, ?)");
					mysqli_stmt_bind_param($stmt, 'isis', $instrumentId, $nidbVariable, $nextOrder, $itemType);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					$variableId = mysqli_insert_id($GLOBALS['linki']);
					mysqli_stmt_close($stmt);
					$notes[] = "created variable \"$nidbVariable\" ($itemType)";
				} else {
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
			} else {
				$variableId = (int)$varRow['instrumentitem_id'];
				if ($createInstruments) {
					// Update the existing item's datatype to match the mapping
					$stmt = mysqli_prepare($GLOBALS['linki'], "UPDATE instrument_items SET item_type = ? WHERE instrumentitem_id = ?");
					mysqli_stmt_bind_param($stmt, 'si', $itemType, $variableId);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					mysqli_stmt_close($stmt);
					$notes[] = "updated variable \"$nidbVariable\" ($itemType)";
				}
			}

			$avicennaQuestionVal      = $avicennaQuestion !== '' ? (int)$avicennaQuestion : null;
			$avicennaVariableVal      = $avicennaVariable !== '' ? $avicennaVariable : null;
			$avicennaSurveyVal        = $avicennaSurvey     !== '' ? $avicennaSurvey     : null;
			$avicennaDatasourceVal    = $avicennaDatasource !== '' ? $avicennaDatasource : null;
			$avicennaDataTypeVal      = $avicennaDatatype !== '' ? $avicennaDatatype : null;
			$fim = $importMeta ? 1 : 0;

			// Check for existing mapping (NULL-safe equals <=> handles null values)
			$stmt = mysqli_prepare($GLOBALS['linki'],
				"SELECT remoteimportmapping_id FROM remoteimport_mapping
				 WHERE project_id = ? AND source_type = 'avicenna'
				   AND avicenna_question <=> ? AND avicenna_variable <=> ? AND avicenna_survey <=> ? AND avicenna_datasource <=> ?");
			mysqli_stmt_bind_param($stmt, 'iisss', $projectid, $avicennaQuestionVal, $avicennaVariableVal, $avicennaSurveyVal, $avicennaDatasourceVal);
			$r = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$existingRow = mysqli_fetch_array($r, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if ($existingRow) {
				// Update the existing mapping
				$existingId = (int)$existingRow['remoteimportmapping_id'];
				$stmt = mysqli_prepare($GLOBALS['linki'],
					"UPDATE remoteimport_mapping SET nidb_instrument=?, nidb_variable=?, avicenna_survey=?, avicenna_datasource=?, avicenna_datatype=?, flag_import_meta=? WHERE remoteimportmapping_id=?");
				mysqli_stmt_bind_param($stmt, 'ii' . 'sss' . 'ii', $instrumentId, $variableId, $avicennaSurveyVal, $avicennaDatasourceVal, $avicennaDataTypeVal, $fim, $existingId);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
				$result['status'] = 'updated';
			} else {
				// Insert a new mapping
				$stmt = mysqli_prepare($GLOBALS['linki'],
					"INSERT INTO remoteimport_mapping (project_id, source_type, avicenna_question, avicenna_variable, avicenna_survey, avicenna_datasource, avicenna_datatype, nidb_instrument, nidb_variable, flag_import_meta) VALUES (?, 'avicenna', ?, ?, ?, ?, ?, ?, ?, ?)");
				mysqli_stmt_bind_param($stmt, 'ii' . 'sss' . 'ss' . 'ii', $projectid, $avicennaQuestionVal, $avicennaVariableVal, $avicennaSurveyVal, $avicennaDatasourceVal, $avicennaDataTypeVal, $instrumentId, $variableId, $fim);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
				$result['status'] = 'added';
			}

			if (!empty($notes)) $result['message'] = implode('; ', $notes);

			$results[] = $result;
		}

		// Count outcomes for summary label
		$nAdded   = count(array_filter($results, function($r) { return $r['status'] === 'added'; }));
		$nUpdated = count(array_filter($results, function($r) { return $r['status'] === 'updated'; }));
		$nErrors  = count(array_filter($results, function($r) { return $r['status'] === 'error'; }));
		?>
		<h3 class="ui header">Bulk import results
			<div class="sub header">
				<?php if ($replaceExisting) { ?><span class="ui tiny grey label"><?= $nDeleted ?> existing mapping<?= $nDeleted == 1 ? '' : 's' ?> cleared</span><?php } ?>
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
							<?php if ($res['message']) { ?> <span style="color:#666"><?= htmlspecialchars($res['message']) ?></span><?php } ?>
						<?php } elseif ($res['status'] === 'updated') { ?>
							<span class="ui tiny blue label"><i class="check icon"></i> updated</span>
							<?php if ($res['message']) { ?> <span style="color:#666"><?= htmlspecialchars($res['message']) ?></span><?php } ?>
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
				<?php if ($createInstruments) { ?><input type="hidden" name="createinstruments" value="1"><?php } ?>
				<?php if ($replaceExisting) { ?><input type="hidden" name="replaceexisting" value="1"><?php } ?>
				<textarea name="csvtext" rows="12"
				          style="font-family:monospace;font-size:0.85em;width:100%;margin-bottom:0.5em"><?= htmlspecialchars($csvtext) ?></textarea>
				<label style="display:block;margin-bottom:0.5em"><input type="checkbox" disabled <?= $createInstruments ? 'checked' : '' ?>> Create/update instruments <?= $createInstruments ? '(on)' : '(off)' ?></label>
				<?php if ($replaceExisting) { ?><label style="display:block;margin-bottom:0.5em;color:darkred"><input type="checkbox" disabled checked> Replace existing mapping (on)</label><?php } ?>
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
	function DisplayMappingList($projectid, $importid = 0) {

		$importid = (int)$importid;

		// Load all instruments for this project — used in JS for the instrument dropdown
		$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrument_id, instrument_name FROM instruments WHERE project_id = ? ORDER BY instrument_name");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$instruments = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$instruments[] = ['id' => (int)$row['instrument_id'], 'name' => $row['instrument_name']];
		}
		mysqli_stmt_close($stmt);

		//PrintVariable($instruments);
		// Load Avicenna mappings
		$stmt = mysqli_prepare($GLOBALS['linki'],
			"SELECT m.remoteimportmapping_id, m.avicenna_question, m.avicenna_variable,
			        m.avicenna_survey, m.avicenna_datasource, m.avicenna_datatype,
			        m.nidb_instrument, m.nidb_variable, m.flag_import_meta,
			        i.instrument_name, ii.item_name
			 FROM remoteimport_mapping m
			 LEFT JOIN instruments i ON i.instrument_id = m.nidb_instrument
			 LEFT JOIN instrument_items ii ON ii.instrumentitem_id = m.nidb_variable
			 WHERE m.project_id = ? AND m.source_type = 'avicenna'
			 ORDER BY m.avicenna_survey, m.avicenna_variable, m.avicenna_question");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$avicennaRows = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$avicennaRows[] = [
				'id'                     => (int)$row['remoteimportmapping_id'],
				'avicenna_survey'        => (string)$row['avicenna_survey'],
				'avicenna_datasource'    => (string)$row['avicenna_datasource'],
				'avicenna_variable'      => (string)$row['avicenna_variable'],
				'avicenna_question'      => (int)$row['avicenna_question'],
				'avicenna_datatype'      => (string)$row['avicenna_datatype'],
				'nidb_instrument_id'     => (int)$row['nidb_instrument'],
				'nidb_instrument'        => (string)$row['instrument_name'],
				'nidb_variable_id'       => (int)$row['nidb_variable'],
				'nidb_variable'          => (string)$row['item_name'],
				'flag_import_meta'       => (int)$row['flag_import_meta'],
			];
		}
		mysqli_stmt_close($stmt);
		//PrintVariable($avicennaRows);

		// Load REDCap mappings
		$stmt = mysqli_prepare($GLOBALS['linki'],
			"SELECT m.remoteimportmapping_id, m.redcap_event, m.redcap_form,
			        m.redcap_field, m.redcap_choice_code, m.redcap_datatype, m.redcap_validation, m.redcap_datefield,
			        m.nidb_instrument, m.nidb_variable, m.flag_date_from_field, m.flag_can_repeat,
			        i.instrument_name, ii.item_name
			 FROM remoteimport_mapping m
			 LEFT JOIN instruments i ON i.instrument_id = m.nidb_instrument
			 LEFT JOIN instrument_items ii ON ii.instrumentitem_id = m.nidb_variable
			 WHERE m.project_id = ? AND m.source_type = 'redcap'
			 ORDER BY m.redcap_event, m.redcap_form, m.redcap_field, m.redcap_choice_code");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$redcapRows = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$redcapRows[] = [
				'id'                   => (int)$row['remoteimportmapping_id'],
				'redcap_event'         => (string)$row['redcap_event'],
				'redcap_form'          => (string)$row['redcap_form'],
				'redcap_field'         => (string)$row['redcap_field'],
				'redcap_choice_code'   => (string)$row['redcap_choice_code'],
				'redcap_datatype'      => (string)$row['redcap_datatype'],
				'redcap_validation'    => (string)$row['redcap_validation'],
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

		/* Index the saved REDCap mappings by their field address so the structure
		   browser can show which REDCap fields are already mapped. Mappings are
		   global per project, so a field mapped for one REDCap import shows as
		   mapped when browsing another -- that is intentional, and the browser
		   labels it so the user can see they are reusing an existing mapping. */
		$mappedIndex = [];
		foreach ($redcapRows as $r) {
			$key = $r['redcap_event'] . '|' . $r['redcap_form'] . '|' . $r['redcap_field'] . '|' . $r['redcap_choice_code'];
			$mappedIndex[$key] = [
				'id'         => $r['id'],
				'instrument' => $r['nidb_instrument'],
				'variable'   => $r['nidb_variable'],
			];
		}

		/* The REDCap imports for this project. Mappings are project-global, but the
		   structure fetch needs a specific import's URL + token, so the user picks
		   which import to browse. */
		$stmt = mysqli_prepare($GLOBALS['linki'],
			"SELECT remoteimport_id, import_name FROM remote_imports
			 WHERE project_id = ? AND remote_type = 'redcap' ORDER BY import_name");
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$redcapImports = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$redcapImports[] = ['id' => (int)$row['remoteimport_id'], 'name' => (string)$row['import_name']];
		}
		mysqli_stmt_close($stmt);

		/* If an import was chosen, pull the live structure from REDCap */
		$structure = null;
		$structureError = '';
		$structureImportName = '';
		if ($importid > 0) {
			$cred = RedCapGetCredentials($importid, $projectid);
			if (!$cred['success'])
				$structureError = $cred['message'];
			else {
				$structureImportName = $cred['importname'];
				$s = RedCapGetStructure($cred['url'], $cred['token']);
				if (!$s['success'])
					$structureError = $s['message'];
				else
					$structure = $s;
			}
		}
		?>
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-grid.css">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-theme-balham.css">
		<style>
			.arrow-col-header { background: #444 !important; color: #fff; font-size: 2em; !important; }
			/* highlight for a NiDB instrument/variable that was prefilled by name match */
			.nidb-prefilled > .ui.dropdown,
			.nidb-prefilled > select { background: #fffbea !important; border-color: #e0b000 !important; box-shadow: 0 0 0 1px #e0b000 inset; }
			.nidb-prefilled > label:after { content: " (prefilled)"; color: #8a6d00; font-weight: normal; font-size: 0.9em; }
		</style>

		<div class="ui two column grid">
			<div class="ui column">
				<h2 class="ui header">Remote Import Mapping</h2>
			</div>
			<div class="ui right aligned column">
				<a class="ui primary button" href="importremote.php?projectid=<?=$projectid?>">Remort imports</a>
			</div>
		</div>

		<!-- Tab menu: Avicenna | REDCap -->
		<div class="ui top attached tabular menu">
			<a class="active item" data-tab="avicenna"><i class="mobile alternate icon"></i> Avicenna</a>
			<a class="item" data-tab="redcap"><i class="red redhat icon"></i> REDCap</a>
		</div>

		<!-- Avicenna tab -->
		<div class="ui bottom attached active tab segment" data-tab="avicenna">
			<div class="ui two column grid" style="padding-bottom: 10px">
				<div class="ui column">
					<span style="margin-left:auto;color:#666;font-size:0.9em"><?= count($avicennaRows) ?> mapping<?= count($avicennaRows) != 1 ? 's' : '' ?></span>
					<input type="text" id="avicennaFilter" placeholder="Search..." oninput="avicennaGridApi.setQuickFilter(this.value)" style="padding:5px 8px;width:250px;border:1px solid #ccc;border-radius:4px">
				</div>
				<div class="ui right aligned column">
					<button class="ui small button" onclick="$('#bulkAddModal').modal('show')">
						<i class="list icon"></i> Bulk add
					</button>
					<button class="ui small primary button" onclick="openModal('avicenna')">
						<i class="plus icon"></i> Add mapping
					</button>
				</div>
			</div>
			<div id="avicennaGrid" class="ag-theme-balham" style="height:500px;width:100%"></div>
			<div id="avicennaSelectionToolbar" style="display:none;margin-top:8px;display:none;align-items:center;gap:8px">
				<span id="avicennaSelectionLabel" style="color:#555;font-size:0.9em"></span>
				<button class="ui small red button" onclick="deleteSelected('avicenna')"><i class="trash icon"></i> Delete</button>
			</div>
		</div>

		<!-- REDCap tab -->
		<div class="ui bottom attached tab segment" data-tab="redcap">

			<!-- ── Browse the live REDCap project structure ────────────────── -->
			<div class="ui segment" style="background:#fafafa">
				<form method="get" action="remoteimportmapping.php" style="margin:0">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<div style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap">
						<div>
							<label style="display:block;font-weight:bold;font-size:0.9em;margin-bottom:3px">Browse REDCap structure</label>
							<select name="importid" style="padding:5px 8px;min-width:260px;border:1px solid #ccc;border-radius:4px">
								<option value="">-- select a REDCap import --</option>
								<? foreach ($redcapImports as $ri): ?>
									<option value="<?=$ri['id']?>" <?= ($ri['id'] == $importid) ? 'selected' : '' ?>><?=htmlspecialchars($ri['name'])?></option>
								<? endforeach; ?>
							</select>
						</div>
						<button type="submit" class="ui small button" onclick="this.innerHTML='<i class=\'notched circle loading icon\'></i> Loading...'">
							<i class="download icon"></i> Load structure
						</button>
						<? if (empty($redcapImports)): ?>
							<span style="color:#a00;font-size:0.9em">No REDCap imports defined for this project.
								<a href="importremote.php?action=addimportform&projectid=<?=$projectid?>">Add one</a>.</span>
						<? endif; ?>
					</div>
				</form>

				<? if ($structureError != ''): ?>
					<div class="ui negative message" style="margin-top:10px">
						<div class="header">Could not read the REDCap project</div>
						<p><?=htmlspecialchars($structureError)?></p>
					</div>
				<? elseif ($structure !== null): ?>
					<div style="margin-top:10px;padding-top:10px;border-top:1px solid #ddd">
						<div style="margin-bottom:8px">
							<b><?=htmlspecialchars($structure['projecttitle'])?></b>
							<span class="ui tiny label"><?= $structure['islongitudinal'] ? 'Longitudinal' : 'Classic' ?></span>
							<? if ($structure['hasrepeating']): ?><span class="ui tiny label">Has repeating</span><? endif; ?>
							<span style="color:#666;font-size:0.9em">via <?=htmlspecialchars($structureImportName)?></span>
						</div>

						<? foreach ($structure['warnings'] as $w): ?>
							<div class="ui warning message" style="padding:6px 10px;margin:4px 0"><?=htmlspecialchars($w)?></div>
						<? endforeach; ?>

						<div style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:8px">
							<? if ($structure['islongitudinal']): ?>
							<div>
								<label style="display:block;font-size:0.85em;color:#555">Event</label>
								<select id="rcEvent" onchange="rcEventChanged()" style="padding:5px 8px;min-width:220px;border:1px solid #ccc;border-radius:4px"></select>
							</div>
							<? endif; ?>
							<div>
								<label style="display:block;font-size:0.85em;color:#555">Form</label>
								<select id="rcForm" onchange="rcRenderFields()" style="padding:5px 8px;min-width:240px;border:1px solid #ccc;border-radius:4px"></select>
							</div>
							<div>
								<label style="display:block;font-size:0.85em;color:#555">Show</label>
								<select id="rcShow" onchange="rcRenderFields()" style="padding:5px 8px;border:1px solid #ccc;border-radius:4px">
									<option value="all">All fields</option>
									<option value="unmapped">Unmapped only</option>
									<option value="mapped">Mapped only</option>
								</select>
							</div>
							<div>
								<button class="ui small primary button" onclick="rcOpenInstrumentModal()" title="Create a NiDB instrument and items from this REDCap form's fields">
									<i class="magic icon"></i> Create Instrument from Form
								</button>
							</div>
							<span id="rcFieldCount" style="margin-left:auto;color:#666;font-size:0.9em"></span>
						</div>

						<div id="rcFieldTableWrap" style="max-height:420px;overflow:auto;border:1px solid #ddd;border-radius:4px">
							<table class="ui very compact small table" style="margin:0" id="rcFieldTable">
								<thead style="position:sticky;top:0;background:#f3f3f3;z-index:1">
									<tr>
										<th style="width:200px">REDCap field</th>
										<th>Label</th>
										<th style="width:90px">Type</th>
										<th style="width:80px">Suggests</th>
										<th style="width:230px">NiDB mapping</th>
										<th style="width:70px"></th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
					</div>
				<? endif; ?>
			</div>

			<div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
				<button class="ui small primary button" onclick="openModal('redcap')">
					<i class="plus icon"></i> Add mapping
				</button>
				<input type="text" id="redcapFilter" placeholder="Search..."
				       oninput="redcapGridApi.setQuickFilter(this.value)"
				       style="padding:5px 8px;width:250px;border:1px solid #ccc;border-radius:4px">
				<span style="margin-left:auto;color:#666;font-size:0.9em"><?= count($redcapRows) ?> mapping<?= count($redcapRows) != 1 ? 's' : '' ?></span>
			</div>
			<div id="redcapGrid" class="ag-theme-balham" style="height:500px;width:100%"></div>
			<div id="redcapSelectionToolbar" style="display:none;margin-top:8px;align-items:center;gap:8px">
				<span id="redcapSelectionLabel" style="color:#555;font-size:0.9em"></span>
				<button class="ui small red button" onclick="deleteSelected('redcap')"><i class="trash icon"></i> Delete</button>
			</div>
		</div>

		<!-- Create-instrument-from-REDCap-form modal -->
		<div class="ui large modal" id="instrumentModal">
			<div class="header">Create Instrument from REDCap Form</div>
			<div class="content">
				<div class="ui form">
					<div class="two fields">
						<div class="field">
							<label>NiDB instrument name</label>
							<input type="text" id="ci_name" oninput="rcInstrumentNameChanged()" placeholder="Instrument name">
						</div>
						<div class="field">
							<label>Instrument notes</label>
							<input type="text" id="ci_notes" placeholder="Notes (optional)">
						</div>
					</div>
				</div>

				<div id="ci_status" style="margin:10px 0"></div>

				<div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
					<button class="ui mini basic button" onclick="rcCiSelectAll(true)">Select all new</button>
					<button class="ui mini basic button" onclick="rcCiSelectAll(false)">Select none</button>
					<span id="ci_counts" style="margin-left:auto;color:#666;font-size:0.9em"></span>
				</div>

				<div style="max-height:420px;overflow:auto;border:1px solid #ddd;border-radius:4px">
					<table class="ui very compact small table" style="margin:0" id="ci_table">
						<thead style="position:sticky;top:0;background:#f3f3f3;z-index:1">
							<tr>
								<th style="width:34px"></th>
								<th style="width:190px">Proposed item (REDCap field)</th>
								<th>Label &rarr; item notes</th>
								<th style="width:110px">Type</th>
								<th style="width:190px">Existing item in NiDB</th>
								<th style="width:150px">Status</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<div style="margin-top:8px;color:#777;font-size:0.85em">
					<i class="info circle icon"></i>
					Existing items cannot be changed &mdash; they may already be referenced by observations.
					Only the checked new items will be added.
				</div>
			</div>
			<div class="actions">
				<div class="ui checkbox" style="float:left;margin:10px 0 0 4px">
					<input type="checkbox" id="ci_createmappings" checked>
					<label>Add REDCap &rarr; new instrument mappings</label>
				</div>
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary button" id="ci_submit" onclick="rcCreateInstrument()">Create Instrument</div>
			</div>
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
								<label>Survey</label>
								<input type="text" id="modal_avicenna_survey" placeholder="Survey name">
							</div>
							<div class="field">
								<label>Datasource</label>
								<input type="text" id="modal_avicenna_datasource" placeholder="Datasource name">
							</div>
						</div>
						<div style="background:#f8ffff;color:#276f86;border:1px solid #a9d5de;border-radius:4px;padding:6px 10px;margin-bottom:1em;font-size:0.9em">
							Enter a <b>Survey</b> or a <b>Datasource</b> &mdash; one is required, but not both.
						</div>
						<div class="two fields">
							<div class="field">
								<label>Variable</label>
								<input type="text" id="modal_avicenna_variable" placeholder="Variable name">
							</div>
							<div class="field">
								<label>Question #</label>
								<input type="number" id="modal_avicenna_question" placeholder="Question number" min="1">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Datatype</label>
								<input type="text" id="modal_avicenna_datatype" placeholder="Data type">
							</div>
							<div class="field"></div>
						</div>
					</div>

					<!-- REDCap-only fields (hidden when source_type is avicenna) -->
					<div id="redcap_fields">
						<div class="three fields">
							<div class="field">
								<label>Event</label>
								<input type="text" id="modal_redcap_event" placeholder="Unique event name (blank if classic)">
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
									<option value="yesno">yesno</option>
									<option value="truefalse">truefalse</option>
									<option value="sql">sql</option>
								</select>
							</div>
							<div class="field">
								<label>Choice code</label>
								<input type="text" id="modal_redcap_choice_code" placeholder="Checkbox choice code; blank if not a checkbox">
							</div>
						</div>
						<div class="three fields">
							<div class="field">
								<label>Date field</label>
								<input type="text" id="modal_redcap_datefield" placeholder="Field used for NiDB date">
							</div>
							<div class="field">
								<label>Validation</label>
								<input type="text" id="modal_redcap_validation" placeholder="REDCap validation type (eg date_ymd, integer)">
							</div>
							<div class="field"></div>
						</div>
					</div>

					<!-- NiDB instrument + variable (shared by both source types) -->
					<div class="two fields">
						<div class="field" id="field_nidb_instrument">
							<label>NiDB Instrument</label>
							<select id="modal_nidb_instrument" class="ui fluid dropdown"
							        onchange="loadInstrumentItems(this.value, null)">
								<option value="">-- select instrument --</option>
								<?php foreach ($instruments as $inst) { ?>
								<option value="<?= $inst['id'] ?>"><?= htmlspecialchars($inst['name']) ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="field" id="field_nidb_variable">
							<label>NiDB Variable</label>
							<select id="modal_nidb_variable" class="ui fluid dropdown">
								<option value="">-- select instrument first --</option>
							</select>
						</div>
					</div>
					<div id="nidb_prefill_hint" style="display:none;margin:-8px 0 10px 0;font-size:0.82em;color:#8a6d00"></div>

					<!-- Avicenna-only flags -->
					<div id="avicenna_flags" class="fields">
						<div class="field">
							<div class="ui checkbox">
								<input type="checkbox" id="modal_flag_import_meta">
								<label>Import metadata</label>
							</div>
						</div>
					</div>

					<!-- REDCap-only flags -->
					<div id="redcap_flags" class="fields">
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
					<input type="hidden" name="createinstruments" id="bulkCreateInstrumentsHidden" value="">
					<input type="hidden" name="replaceexisting" id="bulkReplaceExistingHidden" value="">
					<div class="ui form">
						<div class="field">
							<table class="ui compact celled table">
								<thead>
									<th>CSV column</th>
									<th>Required</th>
									<th>Description</th>
								</thead>
								<tr>
									<td><tt>avicennasurvey</tt></td>
									<td rowspan="2">survey <b>OR</b> datasource</td>
									<td>Survey number. Provide a survey <b>or</b> a datasource, not both.</td>
								</tr>
								<tr>
									<td><tt>avicennadatasource</tt></td>
									<!--<td>survey <i>or</i> datasource</td>-->
									<td>Datasource name. Provide a survey <b>or</b> a datasource, not both.</td>
								</tr>
								<tr>
									<td><tt>avicennavariable</tt></td>
									<td>yes</td>
									<td>Variable name</td>
								</tr>
								<tr>
									<td><tt>avicennadatatype</tt></td>
									<td>yes</td>
									<td>Possible values <code>enum, int, double, string, timeseries, image, csv, json, datetime</code></td>
								</tr>
								<tr>
									<td><tt>avicennaquestion</tt></td>
									<td>-</td>
									<td>Question number - value can be blank</td>
								</tr>
								<tr>
									<td><tt>nidbinstrument</tt></td>
									<td>yes</td>
									<td>Local NiDB instrument</td>
								</tr>
								<tr>
									<td><tt>nidbvariable</tt></td>
									<td>yes</td>
									<td>Local NiDB instrument-item</td>
								</tr>
								<tr>
									<td><tt>importmeta</tt></td>
									<td>-</td>
									<td>Import Avicenna <tt style="color: darkblue">metadata</tt> column if it exists. 1 to import, 0 to skip. Default is 1.</td>
								</tr>
							</table>
								
							<p>
								<ul>
								<li>First line must be a header row.
								<li>Columns may be in any order. Values may contain spaces.
								<li>During import, <tt style="color: darkblue">avicennavariable</tt> will be matched first. If it not found, then <tt style="color: darkblue">avicennaquestion</tt> will be matched.
								</ul>
							</p>
							<div class="ui top attached header" style="background-color:#DBDBEF;position:relative;z-index:1">Paste CSV below</div>
							<textarea name="csvtext" id="bulkCsvText" rows="12"
							          style="font-family:monospace;font-size:0.85em;width:100%;box-shadow:0 0 6px rgba(0,0,139,0.7);border-top-left-radius:0;border-top-right-radius:0;margin-top:0"
							          placeholder="avicennasurvey,avicennadatasource,avicennavariable,avicennadatatype,avicennaquestion,nidbinstrument,nidbvariable,importmeta"></textarea>
						</div>
						<div class="field">
							<div class="ui checkbox">
								<input type="checkbox" id="bulkCreateInstruments" value="1">
								<label>Create/update instruments</label>
							</div>
							<div style="color:#666;font-size:0.85em;margin-top:0.25em">
								When checked, missing instruments and instrument items are created (rather than reported as errors), and the item's datatype (<code>item_type</code>) is set/updated from <code>avicennadatatype</code>.
							</div>
						</div>
						<div class="field">
							<div class="ui checkbox">
								<input type="checkbox" id="bulkReplaceExisting" value="1">
								<label style="color:darkred;font-weight:bold">Replace existing mapping</label>
							</div>
							<div style="color:darkred;font-size:0.85em;margin-top:0.25em">
								<i class="exclamation triangle icon"></i> <b>Warning:</b> this will <b>erase all existing Avicenna mappings for this project</b> before importing, replacing them with the mappings from the CSV above. Instruments and instrument items are not affected (they may be shared by other projects). This cannot be undone.
							</div>
						</div>
					</div>
					<div id="bulkValidationErrors" style="display:none;margin-top:0.75em">
						<div class="ui error message" style="display:block;max-height:200px;overflow-y:auto">
							<div class="header">Please fix the following issues before importing</div>
							<ul id="bulkValidationList" class="list"></ul>
						</div>
					</div>
				</form>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<button class="ui primary button" onclick="submitBulkForm()">Add mappings</button>
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
			//import { themeBalham } from 'ag-grid-community';
		
			document.getElementById('avicennaGrid'),
			{
				columnDefs: [
					{ headerName: '', checkboxSelection: true, headerCheckboxSelection: true, width: 40, minWidth: 40, maxWidth: 40, sortable: false, filter: false, resizable: false },
					{ field: 'avicenna_survey',        headerName: 'Survey',           sortable: true, filter: true, flex: 1 },
					{ field: 'avicenna_datasource',    headerName: 'Datasource',       sortable: true, filter: true, flex: 1 },
					{ field: 'avicenna_variable',      headerName: 'Variable',         sortable: true, filter: true, flex: 1 },
					{ field: 'avicenna_question',      headerName: 'Question #',       sortable: true, filter: true, width: 130 },
					{ field: 'avicenna_datatype',      headerName: 'Datatype',         sortable: true, filter: true, width: 130 },
					arrowCol,
					{ field: 'nidb_instrument',   headerName: 'NiDB Instrument', sortable: true, filter: true, flex: 1,
						cellRenderer: params => {
							if (!params.data.nidb_instrument_id) return params.value || '';
							const a = document.createElement('a');
							a.href = 'instruments.php?projectid=<?= $projectid ?>&instrumentid=' + params.data.nidb_instrument_id;
							a.textContent = params.value;
							return a;
						}
					},
					{ field: 'nidb_variable',     headerName: 'NiDB Variable',   sortable: true, filter: true, flex: 1 },
					{
						field: 'flag_import_meta',
						headerName: 'Import metadata',
						width: 165,
						cellRenderer: flagRenderer('flag_import_meta'),
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
				rowData:           avicennaData,
				defaultColDef:     { resizable: true },
				getRowId:          params => String(params.data.id),
				rowSelection:       'multiple',
				onSelectionChanged: () => updateSelectionToolbar('avicenna'),
				//theme: themeBalham,				
			}
		);

		// ── REDCap grid ───────────────────────────────────────────────────
		redcapGridApi = agGrid.createGrid(
			document.getElementById('redcapGrid'),
			{
				columnDefs: [
					{ headerName: '', checkboxSelection: true, headerCheckboxSelection: true, width: 40, minWidth: 40, maxWidth: 40, sortable: false, filter: false, resizable: false },
					{ field: 'redcap_event',      headerName: 'Event',             sortable: true, filter: true, width: 140 },
					{ field: 'redcap_form',       headerName: 'Form',              sortable: true, filter: true, flex: 1 },
					{ field: 'redcap_field',      headerName: 'Field',             sortable: true, filter: true, flex: 1 },
					{ field: 'redcap_choice_code', headerName: 'Choice',           sortable: true, filter: true, width: 90 },
					{ field: 'redcap_datatype',   headerName: 'Datatype',          sortable: true, filter: true, width: 110 },
					{ field: 'redcap_datefield',  headerName: 'Date field',        sortable: true, filter: true, width: 120 },
					arrowCol,
					{ field: 'nidb_instrument',   headerName: 'NiDB Instrument',   sortable: true, filter: true, flex: 1,
						cellRenderer: params => {
							if (!params.data.nidb_instrument_id) return params.value || '';
							const a = document.createElement('a');
							a.href = 'instruments.php?projectid=<?= $projectid ?>&instrumentid=' + params.data.nidb_instrument_id;
							a.textContent = params.value;
							return a;
						}
					},
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
				rowData:            redcapData,
				defaultColDef:      { resizable: true },
				getRowId:           params => String(params.data.id),
				rowSelection:       'multiple',
				onSelectionChanged: () => updateSelectionToolbar('redcap'),
			}
		);

		/* ── REDCap structure browser ──────────────────────────────────────
		   rcStructure is the live project shape fetched from REDCap; rcMapped is
		   an index of already-saved mappings keyed "event|form|field|choicecode".
		   Both are null/empty when no import has been loaded. */
		const rcStructure = <?= json_encode($structure, JSON_UNESCAPED_SLASHES) ?>;
		const rcMapped    = <?= json_encode($mappedIndex ?: new stdClass(), JSON_UNESCAPED_SLASHES) ?>;

		function rcMapKey(ev, form, field, choice) {
			return (ev || '') + '|' + (form || '') + '|' + (field || '') + '|' + (choice || '');
		}

		/* The REDCap datatype field is a Semantic UI dropdown (initialised globally
		   by includes_html.php), so assigning .value on the underlying <select>
		   updates the element but not what Semantic displays. It has to be set
		   through the dropdown API. */
		function setRedcapDatatype(v) {
			if (v) $('#modal_redcap_datatype').dropdown('set selected', v);
			else   $('#modal_redcap_datatype').dropdown('clear');
		}

		/* Current event: '' for classic projects, which is what gets stored */
		function rcCurrentEvent() {
			const el = document.getElementById('rcEvent');
			return el ? el.value : '';
		}

		/* Forms available in the selected event. Longitudinal projects restrict
		   forms per event via the form/event mapping; classic projects expose all. */
		function rcFormsForEvent(ev) {
			if (!rcStructure) return [];
			if (rcStructure.islongitudinal && ev && rcStructure.formevents && rcStructure.formevents[ev])
				return rcStructure.formevents[ev];
			return Object.keys(rcStructure.instruments || {});
		}

		function rcPopulateEvents() {
			const el = document.getElementById('rcEvent');
			if (!el || !rcStructure) return;
			el.innerHTML = '';
			Object.keys(rcStructure.events || {}).forEach(function(uen) {
				const e = rcStructure.events[uen];
				const o = document.createElement('option');
				o.value = uen;
				o.textContent = e.label + ' (' + uen + ')';
				el.appendChild(o);
			});
			if (!el.options.length) {
				const o = document.createElement('option');
				o.value = ''; o.textContent = '(no events returned)';
				el.appendChild(o);
			}
		}

		function rcPopulateForms() {
			const el = document.getElementById('rcForm');
			if (!el || !rcStructure) return;
			const prev = el.value;
			const forms = rcFormsForEvent(rcCurrentEvent());
			el.innerHTML = '';
			forms.forEach(function(f) {
				const o = document.createElement('option');
				o.value = f;
				o.textContent = (rcStructure.instruments && rcStructure.instruments[f]) ? rcStructure.instruments[f] : f;
				el.appendChild(o);
			});
			if (forms.indexOf(prev) >= 0) el.value = prev;
		}

		function rcEventChanged() {
			rcPopulateForms();
			rcRenderFields();
		}

		function rcRenderFields() {
			if (!rcStructure) return;
			const tbody = document.querySelector('#rcFieldTable tbody');
			const form  = document.getElementById('rcForm').value;
			const ev    = rcCurrentEvent();
			const show  = document.getElementById('rcShow').value;
			const fields = (rcStructure.fields && rcStructure.fields[form]) ? rcStructure.fields[form] : [];

			tbody.innerHTML = '';
			let shown = 0, mappedCount = 0;

			fields.forEach(function(f) {
				const key = rcMapKey(ev, f.form, f.field, f.choicecode);
				const m   = rcMapped[key];
				if (m) mappedCount++;
				if ((show === 'unmapped' && m) || (show === 'mapped' && !m)) return;
				shown++;

				const tr = document.createElement('tr');
				if (!f.mappable) tr.style.opacity = '0.55';

				/* field name (the export column name) */
				const td1 = document.createElement('td');
				const code = document.createElement('code');
				code.textContent = f.exportname;
				td1.appendChild(code);
				if (f.isfile) {
					const b = document.createElement('span');
					b.className = 'ui tiny label';
					b.textContent = 'file';
					b.title = 'Importing this field needs a separate REDCap file download';
					td1.appendChild(document.createTextNode(' '));
					td1.appendChild(b);
				}
				tr.appendChild(td1);

				/* label */
				const td2 = document.createElement('td');
				td2.style.fontSize = '0.9em';
				td2.textContent = f.label || '';
				tr.appendChild(td2);

				/* REDCap type */
				const td3 = document.createElement('td');
				td3.style.fontSize = '0.85em';
				td3.textContent = f.fieldtype + (f.validation ? ' / ' + f.validation : '');
				tr.appendChild(td3);

				/* suggested NiDB type */
				const td4 = document.createElement('td');
				td4.style.fontSize = '0.85em';
				td4.textContent = f.suggestedtype || '—';
				tr.appendChild(td4);

				/* existing mapping */
				const td5 = document.createElement('td');
				td5.style.fontSize = '0.9em';
				if (m) {
					td5.innerHTML = '<i class="green check icon"></i>';
					td5.appendChild(document.createTextNode((m.instrument || '?') + ' › ' + (m.variable || '?')));
				}
				else if (!f.mappable) {
					td5.style.color = '#999';
					td5.textContent = f.fieldtype === 'descriptive' ? 'no data to map' : 'not mappable';
				}
				else {
					td5.style.color = '#999';
					td5.textContent = 'unmapped';
				}
				tr.appendChild(td5);

				/* action */
				const td6 = document.createElement('td');
				if (f.mappable) {
					const btn = document.createElement('button');
					btn.className = 'ui mini ' + (m ? 'basic ' : 'primary ') + 'button';
					btn.textContent = m ? 'Edit' : 'Map';
					btn.onclick = function() { rcOpenModalForField(ev, f, m); };
					td6.appendChild(btn);
				}
				tr.appendChild(td6);

				tbody.appendChild(tr);
			});

			document.getElementById('rcFieldCount').textContent =
				shown + ' of ' + fields.length + ' shown · ' + mappedCount + ' mapped';
		}

		/* Open the shared mapping modal pre-filled from a browsed REDCap field.
		   For an already-mapped field the saved row is loaded so this edits rather
		   than creating a duplicate (which the unique index would reject anyway). */
		function rcOpenModalForField(ev, f, existing) {
			if (existing) {
				let row = null;
				redcapGridApi.forEachNode(function(node) {
					if (node.data && node.data.id == existing.id) row = node.data;
				});
				if (row) { openModalForEdit('redcap', row); return; }
			}

			clearModal();
			document.getElementById('modal_source_type').value = 'redcap';
			document.getElementById('modalTitle').textContent   = 'Map REDCap field: ' + f.exportname;
			toggleSourceFields('redcap');
			document.getElementById('modal_redcap_event').value       = ev || '';
			document.getElementById('modal_redcap_form').value        = f.form || '';
			document.getElementById('modal_redcap_field').value       = f.field || '';
			document.getElementById('modal_redcap_choice_code').value = f.choicecode || '';
			setRedcapDatatype(f.fieldtype || '');
			document.getElementById('modal_redcap_validation').value  = f.validation || '';
			rcPrefillNidbTargets(f);
			$('#mappingModal').modal('show');
		}

		/* Clear any prefill highlight/message from a previous open */
		function rcClearNidbHint() {
			document.getElementById('field_nidb_instrument').classList.remove('nidb-prefilled');
			document.getElementById('field_nidb_variable').classList.remove('nidb-prefilled');
			const h = document.getElementById('nidb_prefill_hint');
			h.style.display = 'none';
			h.textContent = '';
		}

		/* Guess the NiDB instrument and item for a REDCap field being mapped.

		   The convention created by "Create Instrument from Form" is that the
		   instrument is named after the REDCap form label and each item after the
		   field's export name, so a name lookup finds them. The guess is only ever
		   a suggestion -- it is highlighted and labelled so the user can see the
		   values were not read from a saved mapping. */
		function rcPrefillNidbTargets(f) {
			if (!rcStructure) return;
			const form = document.getElementById('rcForm').value;
			const candidate = (rcStructure.instruments && rcStructure.instruments[form]) ? rcStructure.instruments[form] : form;
			if (!candidate) return;

			fetch('ajaxapi.php?action=getinstrumentbyname&projectid=<?= $projectid ?>&instrumentname=' + encodeURIComponent(candidate))
				.then(r => r.json())
				.then(function(resp) {
					if (!resp || !resp.instrument_id) return;

					const want = (f.exportname || '').toLowerCase();
					let match = null;
					(resp.items || []).forEach(function(i) {
						if (!match && i.name.toLowerCase() === want) match = i;
					});

					$('#modal_nidb_instrument').dropdown('set selected', String(resp.instrument_id));
					loadInstrumentItems(resp.instrument_id, match ? match.id : null);

					const hint = document.getElementById('nidb_prefill_hint');
					document.getElementById('field_nidb_instrument').classList.add('nidb-prefilled');
					if (match) {
						document.getElementById('field_nidb_variable').classList.add('nidb-prefilled');
						hint.innerHTML = '<i class="magic icon"></i> Prefilled by name match: instrument <b>'
							+ ciEsc(resp.instrument_name) + '</b>, item <b>' + ciEsc(match.name)
							+ '</b>. Verify before saving.';
					}
					else {
						hint.innerHTML = '<i class="magic icon"></i> Instrument <b>' + ciEsc(resp.instrument_name)
							+ '</b> prefilled by name match, but it has no item named <b>' + ciEsc(f.exportname)
							+ '</b> &mdash; choose one, or create it from the form first.';
					}
					hint.style.display = '';
				})
				.catch(function() { /* a failed guess is not an error; leave the fields blank */ });
		}

		/* ── Create Instrument from Form ───────────────────────────────────
		   Builds a proposed instrument from the selected REDCap form's fields and
		   compares it against an existing NiDB instrument of the same name.
		   Existing items are shown read-only; only new items can be added. */

		const NIDB_ITEM_TYPES = ['enum','int','double','string','timeseries','image','csv','json','datetime'];

		let ciProposed = [];   /* [{name,label,type,mappable,isfile}] */
		let ciExisting = null; /* {instrument_id, items:[{id,name,type,notes}]} or null */

		/* Levenshtein distance, used to flag a proposed item that looks like an
		   existing one (eg a renamed REDCap field) so the user does not create a
		   near-duplicate. */
		function ciLev(a, b) {
			a = (a||'').toLowerCase(); b = (b||'').toLowerCase();
			if (a === b) return 0;
			if (!a.length) return b.length;
			if (!b.length) return a.length;
			let prev = Array.from({length: b.length+1}, (_, i) => i);
			for (let i = 1; i <= a.length; i++) {
				const cur = [i];
				for (let j = 1; j <= b.length; j++) {
					cur[j] = Math.min(prev[j] + 1, cur[j-1] + 1,
					                  prev[j-1] + (a[i-1] === b[j-1] ? 0 : 1));
				}
				prev = cur;
			}
			return prev[b.length];
		}

		/* The REDCap field a checkbox column belongs to: "race___1" -> "race".
		   null for a plain field. */
		function ciBase(n) {
			const i = (n || '').indexOf('___');
			return (i >= 0) ? n.substring(0, i) : null;
		}

		/* Closest existing item to a proposed name, if it is close enough to be
		   worth warning about. Threshold scales with name length.

		   Checkbox choices of the same field are skipped: race___1 and race___2
		   differ by one character but are legitimately distinct items, so
		   comparing them would flag every checkbox sibling as a near-duplicate
		   and drown out the real warnings. */
		function ciClosest(name, existingItems) {
			const nbase = ciBase(name);
			let best = null, bestD = Infinity;
			existingItems.forEach(function(e) {
				const ebase = ciBase(e.name);
				if ((nbase !== null) && (ebase !== null) && (nbase === ebase)) return;
				const d = ciLev(name, e.name);
				if (d < bestD) { bestD = d; best = e; }
			});
			if (!best) return null;
			const limit = Math.max(2, Math.floor(name.length * 0.34));
			return (bestD > 0 && bestD <= limit) ? {item: best, dist: bestD} : null;
		}

		function rcOpenInstrumentModal() {
			if (!rcStructure) return;
			const form = document.getElementById('rcForm').value;
			if (!form) { alert('Select a form first.'); return; }

			/* proposed items = the form's mappable fields, one per checkbox choice */
			const fields = (rcStructure.fields && rcStructure.fields[form]) ? rcStructure.fields[form] : [];
			/* carry the REDCap coordinates too, so the mappings can be created
			   alongside the items without a second lookup */
			ciProposed = fields.filter(f => f.mappable).map(function(f) {
				return {
					name: f.exportname, label: f.label || '', type: f.suggestedtype || '', isfile: !!f.isfile,
					field: f.field || '', choicecode: f.choicecode || '',
					fieldtype: f.fieldtype || '', validation: f.validation || ''
				};
			});

			if (!ciProposed.length) { alert('This form has no mappable fields.'); return; }

			/* default the instrument name to the REDCap form's label, else its name */
			const label = (rcStructure.instruments && rcStructure.instruments[form]) ? rcStructure.instruments[form] : form;
			document.getElementById('ci_name').value  = label;
			document.getElementById('ci_notes').value = 'Created from REDCap form "' + form + '"';

			rcInstrumentNameChanged();
			$('#instrumentModal').modal('show');
		}

		/* Look up whether an instrument of this name already exists, then redraw */
		function rcInstrumentNameChanged() {
			const name = document.getElementById('ci_name').value.trim();
			const statusEl = document.getElementById('ci_status');

			if (!name) {
				ciExisting = null;
				statusEl.innerHTML = '<div class="ui warning message" style="padding:8px 12px;margin:0">An instrument name is required.</div>';
				rcCiRender();
				return;
			}

			statusEl.innerHTML = '<i class="notched circle loading icon"></i> Checking for an existing instrument&hellip;';
			fetch('ajaxapi.php?action=getinstrumentbyname&projectid=<?= $projectid ?>&instrumentname=' + encodeURIComponent(name))
				.then(r => r.json())
				.then(function(resp) {
					ciExisting = (resp && resp.instrument_id > 0) ? resp : null;
					if (ciExisting) {
						statusEl.innerHTML = '<div class="ui info message" style="padding:8px 12px;margin:0">'
							+ '<i class="info circle icon"></i> Instrument <b>' + ciEsc(name) + '</b> already exists ('
							+ ciExisting.items.length + ' item' + (ciExisting.items.length === 1 ? '' : 's')
							+ '). Existing items are shown for comparison and will not be modified.</div>';
					} else {
						statusEl.innerHTML = '<div class="ui positive message" style="padding:8px 12px;margin:0">'
							+ '<i class="plus circle icon"></i> A new instrument <b>' + ciEsc(name) + '</b> will be created.</div>';
					}
					rcCiRender();
				})
				.catch(function() {
					ciExisting = null;
					statusEl.innerHTML = '<div class="ui negative message" style="padding:8px 12px;margin:0">Could not check for an existing instrument.</div>';
					rcCiRender();
				});
		}

		function ciEsc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

		/* Render the side-by-side comparison */
		function rcCiRender() {
			const tbody = document.querySelector('#ci_table tbody');
			tbody.innerHTML = '';
			const existingItems = ciExisting ? ciExisting.items : [];
			const byName = {};
			existingItems.forEach(e => { byName[e.name.toLowerCase()] = e; });

			let nNew = 0, nExists = 0, nSimilar = 0;

			ciProposed.forEach(function(p, idx) {
				const match = byName[p.name.toLowerCase()] || null;
				const near  = match ? null : ciClosest(p.name, existingItems);
				const isNew = !match;
				if (match) nExists++; else nNew++;
				if (near) nSimilar++;

				const tr = document.createElement('tr');
				if (match) tr.style.background = '#f6f6f6';

				/* select checkbox: only for new items */
				const td0 = document.createElement('td');
				if (isNew) {
					const cb = document.createElement('input');
					cb.type = 'checkbox'; cb.className = 'ci-cb'; cb.dataset.idx = idx;
					/* a field with no suggested type needs an explicit choice, so
					   do not pre-select it */
					cb.checked = (p.type !== '');
					cb.onchange = rcCiUpdateCounts;
					td0.appendChild(cb);
				}
				tr.appendChild(td0);

				const td1 = document.createElement('td');
				const code = document.createElement('code'); code.textContent = p.name; td1.appendChild(code);
				tr.appendChild(td1);

				const td2 = document.createElement('td');
				td2.style.fontSize = '0.9em'; td2.textContent = p.label;
				tr.appendChild(td2);

				/* type: editable for new items, read-only for existing */
				const td3 = document.createElement('td');
				if (isNew) {
					const sel = document.createElement('select');
					sel.className = 'ci-type'; sel.dataset.idx = idx;
					sel.style.cssText = 'padding:2px 4px;font-size:0.85em;width:100%';
					const blank = document.createElement('option');
					blank.value = ''; blank.textContent = '-- type --';
					sel.appendChild(blank);
					NIDB_ITEM_TYPES.forEach(function(t) {
						const o = document.createElement('option');
						o.value = t; o.textContent = t;
						if (t === p.type) o.selected = true;
						sel.appendChild(o);
					});
					sel.onchange = function() { ciProposed[idx].type = this.value; rcCiUpdateCounts(); };
					td3.appendChild(sel);
				}
				else {
					td3.style.fontSize = '0.85em'; td3.style.color = '#666';
					td3.textContent = match.type;
				}
				tr.appendChild(td3);

				/* the existing counterpart, if any */
				const td4 = document.createElement('td');
				td4.style.fontSize = '0.9em';
				if (match) { const c = document.createElement('code'); c.textContent = match.name; td4.appendChild(c); }
				else if (near) {
					const c = document.createElement('code'); c.textContent = near.item.name; td4.appendChild(c);
					td4.appendChild(document.createTextNode(' (' + near.item.type + ')'));
				}
				else { td4.style.color = '#bbb'; td4.textContent = '—'; }
				tr.appendChild(td4);

				/* status */
				const td5 = document.createElement('td');
				td5.style.fontSize = '0.85em';
				if (match) {
					td5.innerHTML = '<span class="ui tiny label">already exists</span>';
				}
				else if (near) {
					td5.innerHTML = '<span class="ui tiny yellow label" title="Looks similar to an existing item — check you are not creating a duplicate">similar to existing</span>';
				}
				else if (p.type === '') {
					td5.innerHTML = '<span class="ui tiny orange label" title="' + (p.isfile ? 'REDCap file fields need an explicit type' : 'No type could be suggested') + '">choose a type</span>';
				}
				else {
					td5.innerHTML = '<span class="ui tiny green label">new</span>';
				}
				tr.appendChild(td5);

				tbody.appendChild(tr);
			});

			/* items that exist in NiDB but are not in this REDCap form */
			existingItems.forEach(function(e) {
				if (ciProposed.some(p => p.name.toLowerCase() === e.name.toLowerCase())) return;
				const tr = document.createElement('tr');
				tr.style.cssText = 'background:#fafafa;color:#999';
				tr.appendChild(document.createElement('td'));
				const t1 = document.createElement('td'); t1.style.color = '#bbb'; t1.textContent = '—'; tr.appendChild(t1);
				const t2 = document.createElement('td'); t2.style.fontSize = '0.85em'; t2.textContent = e.notes || ''; tr.appendChild(t2);
				const t3 = document.createElement('td'); t3.style.fontSize = '0.85em'; t3.textContent = e.type; tr.appendChild(t3);
				const t4 = document.createElement('td'); const c = document.createElement('code'); c.textContent = e.name; t4.appendChild(c); tr.appendChild(t4);
				const t5 = document.createElement('td'); t5.innerHTML = '<span class="ui tiny label">in NiDB only</span>'; tr.appendChild(t5);
				tbody.appendChild(tr);
			});

			document.getElementById('ci_counts').dataset.newCount     = nNew;
			document.getElementById('ci_counts').dataset.existsCount  = nExists;
			document.getElementById('ci_counts').dataset.similarCount = nSimilar;
			rcCiUpdateCounts();
		}

		function rcCiUpdateCounts() {
			const el = document.getElementById('ci_counts');
			const checked = document.querySelectorAll('.ci-cb:checked').length;
			el.textContent = checked + ' selected to add · ' + el.dataset.newCount + ' new · '
			               + el.dataset.existsCount + ' already exist · ' + el.dataset.similarCount + ' similar';
		}

		function rcCiSelectAll(on) {
			document.querySelectorAll('.ci-cb').forEach(function(cb) {
				/* never auto-select an item that still has no type */
				const p = ciProposed[parseInt(cb.dataset.idx)];
				cb.checked = on && (p.type !== '');
			});
			rcCiUpdateCounts();
		}

		function rcCreateInstrument() {
			const name = document.getElementById('ci_name').value.trim();
			if (!name) { alert('An instrument name is required.'); return; }

			const items = [];
			let missingType = 0;
			document.querySelectorAll('.ci-cb:checked').forEach(function(cb) {
				const p = ciProposed[parseInt(cb.dataset.idx)];
				if (!p) return;
				if (!p.type) { missingType++; return; }
				items.push({
					name: p.name, type: p.type, notes: p.label,
					field: p.field, choicecode: p.choicecode,
					fieldtype: p.fieldtype, validation: p.validation
				});
			});

			if (missingType > 0) {
				alert(missingType + ' selected item(s) have no type chosen. Choose a type or deselect them.');
				return;
			}
			if (!items.length) { alert('No new items are selected.'); return; }

			const btn = document.getElementById('ci_submit');
			btn.classList.add('loading', 'disabled');

			const makeMappings = document.getElementById('ci_createmappings').checked ? 1 : 0;
			const body = 'action=createinstrumentitems'
				+ '&projectid=<?= $projectid ?>'
				+ '&instrumentname=' + encodeURIComponent(name)
				+ '&instrumentnotes=' + encodeURIComponent(document.getElementById('ci_notes').value)
				+ '&redcap_form=' + encodeURIComponent(document.getElementById('rcForm').value)
				+ '&redcap_event=' + encodeURIComponent(rcCurrentEvent())
				+ '&createmappings=' + makeMappings
				+ '&items=' + encodeURIComponent(JSON.stringify(items));

			fetch('ajaxapi.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: body
			})
				.then(r => r.json())
				.then(function(resp) {
					btn.classList.remove('loading', 'disabled');
					if (!resp || !resp.ok) { alert('Error: ' + ((resp && resp.error) || 'unknown')); return; }
					let msg = (resp.created ? 'Created instrument "' : 'Updated instrument "') + resp.instrument_name
						+ '"\n' + resp.added + ' item(s) added.';
					if (makeMappings) msg += '\n' + (resp.mapped || 0) + ' mapping(s) created.';
					if (resp.skipped && resp.skipped.length) msg += '\n' + resp.skipped.length + ' skipped (already existed).';
					if (resp.rejected && resp.rejected.length) msg += '\n' + resp.rejected.length + ' rejected: ' + resp.rejected.join(', ');
					alert(msg);
					$('#instrumentModal').modal('hide');
					/* the instrument dropdown in the mapping modal is rendered
					   server-side, so reload to pick up the new instrument/items */
					location.reload();
				})
				.catch(function() {
					btn.classList.remove('loading', 'disabled');
					alert('Network error creating the instrument.');
				});
		}

		// ── Modal: open for a new mapping ─────────────────────────────────
		function openModal(sourceType) {
			clearModal();
			document.getElementById('modal_source_type').value = sourceType;
			document.getElementById('modalTitle').textContent  = 'Add ' + sourceType + ' mapping';
			toggleSourceFields(sourceType);
			if (sourceType === 'avicenna') {
				document.getElementById('modal_flag_import_meta').checked = true;
			}
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
				document.getElementById('modal_avicenna_survey').value        = data.avicenna_survey        || '';
				document.getElementById('modal_avicenna_datasource').value    = data.avicenna_datasource    || '';
				document.getElementById('modal_avicenna_variable').value      = data.avicenna_variable      || '';
				document.getElementById('modal_avicenna_datatype').value      = data.avicenna_datatype      || '';
				document.getElementById('modal_avicenna_question').value      = data.avicenna_question      || '';
				document.getElementById('modal_flag_import_meta').checked     = !!data.flag_import_meta;
			} else {
				document.getElementById('modal_redcap_event').value        = data.redcap_event     || '';
				document.getElementById('modal_redcap_form').value         = data.redcap_form      || '';
				document.getElementById('modal_redcap_field').value        = data.redcap_field     || '';
				document.getElementById('modal_redcap_choice_code').value  = data.redcap_choice_code || '';
				setRedcapDatatype(data.redcap_datatype || '');
				document.getElementById('modal_redcap_datefield').value    = data.redcap_datefield || '';
				document.getElementById('modal_redcap_validation').value   = data.redcap_validation || '';
				document.getElementById('modal_flag_date_from_field').checked = !!data.flag_date_from_field;
				document.getElementById('modal_flag_can_repeat').checked       = !!data.flag_can_repeat;
			}

			// Set instrument, then fetch its items and pre-select the saved variable
			$('#modal_nidb_instrument').dropdown('set selected', data.nidb_instrument_id || '');
			if (data.nidb_instrument_id) {
				loadInstrumentItems(data.nidb_instrument_id, data.nidb_variable_id);
			}

			$('#mappingModal').modal('show');
		}

		// ── Reset all form fields to empty ────────────────────────────────
		function clearModal() {
			['modal_mappingid','modal_avicenna_survey','modal_avicenna_datasource','modal_avicenna_variable','modal_avicenna_datatype',
			 'modal_avicenna_question',
			 'modal_redcap_event','modal_redcap_form','modal_redcap_choice_code',
			 'modal_redcap_field','modal_redcap_datefield',
			 'modal_redcap_validation'].forEach(id => {
				document.getElementById(id).value = '';
			});
			/* Semantic dropdowns must be cleared through their API, not by .value */
			setRedcapDatatype('');
			rcClearNidbHint();
			$('#modal_nidb_instrument').dropdown('clear');
			document.getElementById('modal_nidb_variable').innerHTML =
				'<option value="">-- select instrument first --</option>';
			document.getElementById('modal_flag_import_meta').checked     = false;
			document.getElementById('modal_flag_date_from_field').checked = false;
			document.getElementById('modal_flag_can_repeat').checked      = false;
		}

		// ── Show/hide source-type-specific field groups ───────────────────
		function toggleSourceFields(sourceType) {
			const isAvicenna = (sourceType === 'avicenna');
			document.getElementById('avicenna_fields').style.display = isAvicenna ? '' : 'none';
			document.getElementById('avicenna_flags').style.display  = isAvicenna ? '' : 'none';
			document.getElementById('redcap_fields').style.display   = isAvicenna ? 'none' : '';
			document.getElementById('redcap_flags').style.display    = isAvicenna ? 'none' : '';
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
					/* This select is wrapped by Semantic UI, so replacing its
					   options does not update the visible menu. Re-read it, then
					   re-apply the selection through the API. */
					$('#modal_nidb_variable').dropdown('refresh');
					if (preselectId)
						$('#modal_nidb_variable').dropdown('set selected', String(preselectId));
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
				action:          'savemapping',
				projectid:       projectId,
				mappingid:       mappingId,
				source_type:     sourceType,
				nidb_instrument: document.getElementById('modal_nidb_instrument').value,
				nidb_variable:   document.getElementById('modal_nidb_variable').value,
			};

			// Add source-specific fields and flags
			if (sourceType === 'avicenna') {
				const survey     = document.getElementById('modal_avicenna_survey').value.trim();
				const datasource = document.getElementById('modal_avicenna_datasource').value.trim();
				// A survey OR a datasource is required, but not both.
				if ((survey === '') === (datasource === '')) {
					alert(survey === ''
						? 'Please enter a Survey or a Datasource.'
						: 'Please enter a Survey OR a Datasource, not both.');
					return;
				}
				params.avicenna_survey        = document.getElementById('modal_avicenna_survey').value;
				params.avicenna_datasource    = document.getElementById('modal_avicenna_datasource').value;
				params.avicenna_variable      = document.getElementById('modal_avicenna_variable').value;
				params.avicenna_datatype      = document.getElementById('modal_avicenna_datatype').value;
				params.avicenna_question      = document.getElementById('modal_avicenna_question').value;
				params.flag_import_meta       = document.getElementById('modal_flag_import_meta').checked ? 1 : 0;
			} else {
				params.redcap_event          = document.getElementById('modal_redcap_event').value;
				params.redcap_form           = document.getElementById('modal_redcap_form').value;
				params.redcap_field          = document.getElementById('modal_redcap_field').value;
				params.redcap_choice_code    = document.getElementById('modal_redcap_choice_code').value;
				params.redcap_datatype       = document.getElementById('modal_redcap_datatype').value;
				params.redcap_datefield      = document.getElementById('modal_redcap_datefield').value;
				params.redcap_validation     = document.getElementById('modal_redcap_validation').value;
				params.flag_date_from_field  = document.getElementById('modal_flag_date_from_field').checked ? 1 : 0;
				params.flag_can_repeat       = document.getElementById('modal_flag_can_repeat').checked      ? 1 : 0;
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
					id:                 resp.mappingid,
					nidb_instrument_id: parseInt(params.nidb_instrument) || 0,
					nidb_instrument:    instrEl.options[instrEl.selectedIndex]?.text || '',
					nidb_variable_id:   parseInt(params.nidb_variable)   || 0,
					nidb_variable:      varEl.options[varEl.selectedIndex]?.text     || '',
				};

				if (sourceType === 'avicenna') {
					Object.assign(rowData, {
						avicenna_survey:        params.avicenna_survey,
						avicenna_variable:      params.avicenna_variable,
						avicenna_datatype:      params.avicenna_datatype,
						avicenna_question:      parseInt(params.avicenna_question)      || 0,
						flag_import_meta:       parseInt(params.flag_import_meta),
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
						redcap_event:     params.redcap_event,
						redcap_form:      params.redcap_form,
						redcap_field:     params.redcap_field,
						redcap_choice_code:   params.redcap_choice_code,
						redcap_datatype:      params.redcap_datatype,
						redcap_datefield:     params.redcap_datefield,
						redcap_validation:    params.redcap_validation,
						flag_date_from_field: parseInt(params.flag_date_from_field),
						flag_can_repeat:      parseInt(params.flag_can_repeat),
					});
					if (mappingId) {
						redcapGridApi.forEachNode(node => {
							if (node.data && node.data.id == mappingId) node.setData(rowData);
						});
					} else {
						redcapGridApi.applyTransaction({ add: [rowData] });
					}

					/* Keep the structure browser in step. It renders from rcMapped
					   rather than the grid, so without this the field just mapped
					   would still show as unmapped until the page reloaded. */
					if (rcStructure) {
						/* an edit may have moved the mapping to a different
						   event/form/field, so drop any previous entry for this id */
						Object.keys(rcMapped).forEach(function(k) {
							if (rcMapped[k] && rcMapped[k].id == resp.mappingid) delete rcMapped[k];
						});
						rcMapped[rcMapKey(params.redcap_event, params.redcap_form,
						                  params.redcap_field, params.redcap_choice_code)] = {
							id:         resp.mappingid,
							instrument: rowData.nidb_instrument,
							variable:   rowData.nidb_variable
						};
						rcRenderFields();
					}
				}

				$('#mappingModal').modal('hide');
			})
			.catch(() => alert('Network error saving mapping'));
		}

		// ── Bulk CSV pre-validation ───────────────────────────────────────
		function parseCSVLine(line) {
			const result = [];
			let cur = '', inQuote = false;
			for (let i = 0; i < line.length; i++) {
				const ch = line[i];
				if (ch === '"') {
					if (inQuote && line[i+1] === '"') { cur += '"'; i++; }
					else inQuote = !inQuote;
				} else if (ch === ',' && !inQuote) {
					result.push(cur); cur = '';
				} else {
					cur += ch;
				}
			}
			result.push(cur);
			return result;
		}

		function validateBulkCSV() {
			const VALID_DATATYPES = ['enum', 'int', 'double', 'string', 'timeseries', 'image', 'csv', 'json', 'datetime'];
			const REQUIRED_COLS   = ['avicennavariable', 'avicennadatatype', 'nidbinstrument', 'nidbvariable'];

			const raw    = document.getElementById('bulkCsvText').value.trim();
			const errors = [];

			if (!raw) {
				errors.push('CSV is empty.');
				return errors;
			}

			const lines = raw.split('\n').map(l => l.trim()).filter(l => l !== '');
			if (lines.length < 2) {
				errors.push('CSV must have a header row and at least one data row.');
				return errors;
			}

			// Normalise header
			const header    = parseCSVLine(lines[0]).map(h => h.trim().toLowerCase().replace(/[\s_]+/g, ''));
			const headerLen = header.length;

			// Check 1: required columns
			const missing = REQUIRED_COLS.filter(c => !header.includes(c));
			if (missing.length > 0) {
				errors.push('Missing required column' + (missing.length > 1 ? 's' : '') + ': ' + missing.join(', ') + '.');
			}

			// Check 1b: a survey OR a datasource column must be present
			if (!header.includes('avicennasurvey') && !header.includes('avicennadatasource')) {
				errors.push('Missing required column: avicennasurvey or avicennadatasource.');
			}

			const surveyIdx     = header.indexOf('avicennasurvey');
			const datasourceIdx = header.indexOf('avicennadatasource');
			const dtIdx   = header.indexOf('avicennadatatype');
			const instIdx = header.indexOf('nidbinstrument');
			const varIdx  = header.indexOf('nidbvariable');
			const qIdx    = header.indexOf('avicennaquestion');

			// Check data rows
			let blankRows = [], unevenRows = [], badDatatypes = [], blankInst = [], blankVar = [], badQuestions = [], badSurveyDatasource = [];

			lines.slice(1).forEach((line, i) => {
				const rowNum = i + 2;
				const cols   = parseCSVLine(line);

				// Skip entirely blank rows
				if (cols.every(c => c.trim() === '')) { blankRows.push(rowNum); return; }

				// Uneven column count
				if (cols.length !== headerLen) {
					unevenRows.push('row ' + rowNum + ' (' + cols.length + ' vs ' + headerLen + ' expected)');
				}

				// Valid avicennadatatype
				if (dtIdx >= 0 && cols[dtIdx] !== undefined) {
					const dt = cols[dtIdx].trim().toLowerCase();
					if (dt !== '' && !VALID_DATATYPES.includes(dt)) {
						badDatatypes.push('row ' + rowNum + ': "' + cols[dtIdx].trim() + '"');
					}
				}

				// Non-empty nidbinstrument
				if (instIdx >= 0 && (!cols[instIdx] || cols[instIdx].trim() === '')) {
					blankInst.push(rowNum);
				}

				// Non-empty nidbvariable
				if (varIdx >= 0 && (!cols[varIdx] || cols[varIdx].trim() === '')) {
					blankVar.push(rowNum);
				}

				// avicennaquestion, if provided, must be a positive integer (blank is allowed and stored as NULL)
				if (qIdx >= 0 && cols[qIdx] !== undefined) {
					const q = cols[qIdx].trim();
					if (q !== '' && (!/^\d+$/.test(q) || parseInt(q, 10) < 1)) {
						badQuestions.push('row ' + rowNum + ': "' + q + '"');
					}
				}

				// A survey OR a datasource is required per row (not both, not neither)
				const surveyVal     = surveyIdx     >= 0 && cols[surveyIdx]     !== undefined ? cols[surveyIdx].trim()     : '';
				const datasourceVal = datasourceIdx >= 0 && cols[datasourceIdx] !== undefined ? cols[datasourceIdx].trim() : '';
				if ((surveyVal === '') === (datasourceVal === '')) {
					badSurveyDatasource.push(rowNum);
				}
			});

			if (blankRows.length)    errors.push('Blank row' + (blankRows.length > 1 ? 's' : '') + ' found (will be skipped): ' + blankRows.join(', ') + '.');
			if (unevenRows.length)   errors.push('Column count mismatch in ' + unevenRows.join('; ') + '.');
			if (badDatatypes.length) errors.push('Invalid avicennadatatype (must be enum, int, double, string, timeseries, image, csv, json, or datetime) in ' + badDatatypes.join('; ') + '.');
			if (blankInst.length)    errors.push('Missing nidbinstrument in row' + (blankInst.length > 1 ? 's' : '') + ': ' + blankInst.join(', ') + '.');
			if (blankVar.length)     errors.push('Missing nidbvariable in row' + (blankVar.length > 1 ? 's' : '') + ': ' + blankVar.join(', ') + '.');
			if (badQuestions.length) errors.push('Invalid avicennaquestion (must be a positive integer or blank) in ' + badQuestions.join('; ') + '.');
			if (badSurveyDatasource.length) errors.push('Each row needs a survey OR a datasource (not both, not neither): row' + (badSurveyDatasource.length > 1 ? 's' : '') + ' ' + badSurveyDatasource.join(', ') + '.');

			return errors;
		}

		function submitBulkForm() {
			const errors  = validateBulkCSV();
			const errDiv  = document.getElementById('bulkValidationErrors');
			const errList = document.getElementById('bulkValidationList');

			// Blank rows are warnings not blockers — filter them out as hard errors
			const hardErrors = errors.filter(e => !e.startsWith('Blank row'));
			const warnings   = errors.filter(e =>  e.startsWith('Blank row'));

			errList.innerHTML = '';
			errors.forEach(e => {
				const li = document.createElement('li');
				li.textContent = e;
				errList.appendChild(li);
			});

			if (hardErrors.length > 0) {
				errDiv.style.display = 'block';
				return;
			}

			errDiv.style.display = errors.length ? 'block' : 'none';

			if (warnings.length === 0 || confirm(warnings.join('\n') + '\n\nContinue anyway?')) {
				// Sync the (UI-only) checkbox state into the hidden fields that actually get POSTed
				document.getElementById('bulkCreateInstrumentsHidden').value =
					document.getElementById('bulkCreateInstruments').checked ? '1' : '';
				const replaceExisting = document.getElementById('bulkReplaceExisting').checked;
				document.getElementById('bulkReplaceExistingHidden').value = replaceExisting ? '1' : '';

				// Replacing is destructive - require an explicit confirmation
				if (replaceExisting && !confirm('This will ERASE all existing Avicenna mappings for this project and replace them with the CSV above.\n\nInstruments and instrument items are not affected. This cannot be undone.\n\nContinue?')) {
					return;
				}
				document.getElementById('bulkForm').submit();
			}
		}

		// ── Selection toolbar ─────────────────────────────────────────────
		function updateSelectionToolbar(sourceType) {
			const api     = sourceType === 'avicenna' ? avicennaGridApi : redcapGridApi;
			const toolbar = document.getElementById(sourceType + 'SelectionToolbar');
			const label   = document.getElementById(sourceType + 'SelectionLabel');
			const count   = api.getSelectedRows().length;
			if (count > 0) {
				label.textContent = 'With selected ' + count + ' mapping' + (count !== 1 ? 's' : '') + '...';
				toolbar.style.display = 'flex';
			} else {
				toolbar.style.display = 'none';
			}
		}

		function deleteSelected(sourceType) {
			const api  = sourceType === 'avicenna' ? avicennaGridApi : redcapGridApi;
			const rows = api.getSelectedRows();
			if (rows.length === 0) return;
			if (!confirm('Are you sure you want to delete ' + rows.length + ' selected mapping' + (rows.length !== 1 ? 's' : '') + '? This cannot be undone.')) return;
			const ids = rows.map(r => r.id);
			fetch('ajaxapi.php?action=bulkdeletemappings&ids=' + encodeURIComponent(JSON.stringify(ids)))
				.then(r => r.json())
				.then(resp => {
					if (!resp.ok) { alert('Error deleting: ' + (resp.error || 'unknown')); return; }
					api.applyTransaction({ remove: rows });
					updateSelectionToolbar(sourceType);
				})
				.catch(() => alert('Network error deleting mappings'));
		}

		// ── Semantic UI initialization ─────────────────────────────────────
		$('.tabular.menu .item').tab();
		$('.ui.checkbox').checkbox();

		/* If a REDCap structure was loaded, switch to the REDCap tab and populate
		   the browser. Loading the structure is a full page GET, so without this
		   the user would land back on the Avicenna tab. */
		if (rcStructure) {
			$('.tabular.menu .item').tab('change tab', 'redcap');
			rcPopulateEvents();
			rcPopulateForms();
			rcRenderFields();
		}
		</script>
		<?php
	}

require "footer.php";
?>
