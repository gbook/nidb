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
		<style>.ui-autocomplete { z-index: 9999 !important; }</style>
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
	/*
	 * OBSERVATION STATE TABLE
	 * Each row in the observations table can be in one of 8 states based on the presence or
	 * absence of three fields: instrumentitem_id (FK to instrument_items), observation_instrument
	 * (free-text legacy hint), and observationsurvey_id (FK to observation_surveys).
	 *
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * | State   | instrumentitem_id | observation_instr  | observationsurvey_id  | Description / Handling                       |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  1      | set               | set                | set                   | Fully linked survey obs — ideal/target state |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  2      | set               | set                | null                  | Linked item + legacy hint, no survey         |
	 * |         |                   |                    |                       | Left blank intentionally (no auto-assign)    |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  3      | set               | null               | set                   | Linked item, no hint, in survey              |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  4      | set               | null               | null                  | Linked item, no hint, no survey              |
	 * |         |                   |                    |                       | Auto-fill: match obs_name → item_name via    |
	 * |         |                   |                    |                       | survey.instrument_id and update silently     |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  5      | null              | set                | set                   | Legacy name in a survey — formalize target   |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  6      | null              | set                | null                  | Legacy name, no survey — Formalize link      |
	 * |         |                   |                    |                       | shown; assign to most recent survey or new   |
	 * |         |                   |                    |                       | survey on observation_name collision         |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  7      | null              | null               | set                   | No instrument link, in survey (orphan obs)   |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 * |  8      | null              | null               | null                  | Fully unaffiliated → grouped under __none__  |
	 * +---------+-------------------+--------------------+-----------------------+----------------------------------------------+
	 *
	 * Grouping logic:
	 *   States 1-4  (instrumentitem_id set)  → grouped by instruments.instrument_name (via FK join)
	 *   States 5-6  (observation_instrument set, no item FK) → grouped by observation_instrument text
	 *   States 7-8  (neither field set)      → grouped under __none__ ("No instrument")
	 *
	 * Label logic per Instrument accordion section:
	 *   "Linked"           → all rows in the group are states 1-4 (legacyCount === 0 and instrument exists)
	 *   "Partially linked" → instrument exists but some rows are states 5-6 (legacyCount > 0)
	 *   "Legacy"           → no instrument record exists; rows are states 5-6 only
	 *   (no label)         → __none__ group (states 7-8)
	 */
	function DisplayObservationList($enrollmentid) {

		if ((trim($enrollmentid) == "") || ($enrollmentid < 0)) {
			?>Invalid or blank enrollment ID [<?=$enrollmentid?>]<?
			return;
		}

		/* get subject's info and project for the breadcrumb/form */
		list(,,,,$projectid) = GetEnrollmentInfo($enrollmentid);

		/* flush the loading indicator to the browser before running the heavy SQL queries */
		?>
		<div class="ui text container" id="pageloading">
			<div class="ui inverted segment" align="center">
				<h2 class="ui inverted header">
					<i class="spinner loading icon"></i> Loading...
				</h2>
			</div>
		</div>
		<?
		/* discard any output buffer accumulated by includes so the loading div reaches the browser now */
		while (ob_get_level()) ob_end_flush();
		flush();
		?>

		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

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

		<!-- Assign to Existing Survey Modal -->
		<div class="ui small modal" id="assignSurveyModal">
			<div class="header">Assign Observations to Survey</div>
			<div class="content">
				<div class="ui form">
					<div class="field">
						<label>Select survey</label>
						<select id="assignSurveySelect" class="ui dropdown" style="width:100%"></select>
					</div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button">Assign</div>
			</div>
		</div>

		<!-- Edit Survey Modal -->
		<div class="ui small modal" id="editSurveyModal">
			<div class="header">Edit Survey</div>
			<div class="content">
				<div class="ui form">
					<input type="hidden" id="editSurveyId">
					<div class="fields">
						<div class="eight wide field">
							<label>Start date</label>
							<input type="datetime-local" id="editSurveyStartdate">
						</div>
						<div class="eight wide field">
							<label>End date</label>
							<input type="datetime-local" id="editSurveyEnddate">
						</div>
					</div>
					<div class="field">
						<label>Rater</label>
						<input type="text" id="editSurveyRater" placeholder="Rater name">
					</div>
					<div class="field">
						<label>Notes</label>
						<textarea id="editSurveyNotes" rows="2"></textarea>
					</div>
					<div id="editSurveyError" class="ui error message" style="display:none"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button" id="editSurveySaveButton">Save</div>
			</div>
		</div>

		<!-- New Survey Modal -->
		<div class="ui small modal" id="newSurveyModal">
			<div class="header">Create New Survey</div>
			<div class="content">
				<div class="ui form">
					<input type="hidden" id="newSurveyInstrId">
					<div class="fields">
						<div class="eight wide field">
							<label>Start date</label>
							<input type="datetime-local" id="newSurveyStartdate">
						</div>
						<div class="eight wide field">
							<label>End date</label>
							<input type="datetime-local" id="newSurveyEnddate">
						</div>
					</div>
					<div class="field">
						<label>Rater</label>
						<input type="text" id="newSurveyRater" placeholder="Rater name">
					</div>
					<div class="field">
						<label>Notes</label>
						<textarea id="newSurveyNotes" rows="2"></textarea>
					</div>
					<div id="newSurveyError" class="ui error message" style="display:none"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button" id="newSurveySaveButton">Create &amp; Assign</div>
			</div>
		</div>

		<!-- Observation Metadata Modal -->
		<div class="ui small modal" id="obsMetaModal">
			<div class="header"><i class="database icon"></i> Metadata &mdash; <span id="obsMetaModalName"></span></div>
			<div class="content" id="obsMetaContent">
				<div class="ui active centered inline loader"></div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Close</div>
			</div>
		</div>

		<!-- Bulk Action Modal -->
		<div class="ui small modal" id="bulkActionModal">
			<div class="header">Bulk Action &mdash; <span id="bulkSelectedCount">0</span> observation(s) selected</div>
			<div class="content">
				<div class="ui form">
					<div class="field">
						<label>Action</label>
						<select id="bulkActionSelect" class="ui dropdown" onchange="updateBulkActionFields()">
							<option value="">&#8212; Select action &#8212;</option>
							<option value="obsInstrument">Set Instrument</option>
							<option value="rater">Set Rater</option>
							<option value="value">Set Value</option>
							<option value="startdate">Set Start Date</option>
							<option value="enddate">Set End Date</option>
							<option value="delete">Delete</option>
							<option value="convertmeta">Convert value to metadata</option>
							<option value="movenewsurvey">Move to new survey</option>
						</select>
					</div>
					<div id="bulk_field_movenewsurvey" class="ui info message" style="display:none">
						<i class="info circle icon"></i>Selected observations will be moved to a new survey. The survey start date will be set to the earliest observation start date among the selection. Any existing survey assignment on the selected observations will be replaced.
					</div>
					<div id="bulk_field_convertmeta" class="ui info message" style="display:none">
						<i class="info circle icon"></i>Each selected observation&rsquo;s <b>value</b> will be parsed as JSON, flattened into key&ndash;value pairs, and stored in the observation metadata table. Nested keys are joined with <code>_</code> (e.g. <code>var_1 &rarr; subvar1</code> becomes <code>var_1_subvar1</code>). The original value will be cleared. Observations whose value is empty or not valid JSON will be skipped.
					</div>
					<div id="bulk_field_obsInstrument" class="field" style="display:none">
						<label>Instrument name</label>
						<input type="text" id="bulkInstrumentValue" placeholder="Instrument name">
					</div>
					<div id="bulk_field_rater" class="field" style="display:none">
						<label>Rater</label>
						<input type="text" id="bulkRaterValue" placeholder="Rater name">
					</div>
					<div id="bulk_field_value" class="field" style="display:none">
						<label>Value</label>
						<input type="text" id="bulkValueValue" placeholder="Observation value">
					</div>
					<div id="bulk_field_startdate" class="field" style="display:none">
						<label>Start Date</label>
						<input type="datetime-local" id="bulkStartdateValue">
					</div>
					<div id="bulk_field_enddate" class="field" style="display:none">
						<label>End Date</label>
						<input type="datetime-local" id="bulkEnddateValue">
					</div>
					<div id="bulk_field_delete" class="ui warning message" style="display:none">
						<i class="warning sign icon"></i>This will permanently delete <b><span id="bulkDeleteCount">0</span> observation(s)</b>. This cannot be undone.
					</div>
					<div id="bulkActionError" class="ui red message" style="display:none"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary approve button" id="bulkApplyBtn">Apply</div>
			</div>
		</div>

		<!-- Add Observation modal -->
		<div class="ui modal" id="addObsModal">
			<div class="header"><i class="plus icon"></i> Add Observation</div>
			<div class="content">
				<form action="observations.php" method="post" class="ui form" id="addObsForm">
					<input type="hidden" name="action" value="addobservation">
					<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
					<input type="hidden" name="instrument_id" id="instrumentId">
					<input type="hidden" name="observation_instrument" id="instrumentNameHidden">
					<input type="hidden" name="instrumentitem_id" id="instrumentitemId">

					<div class="fields">
						<div class="eight wide field">
							<label style="display:flex;justify-content:space-between;align-items:center">
								<span>Instrument &nbsp; <span class="ui basic tiny label" style="font-weight:normal">optional but encouraged</span></span>
								<a href="#" id="addInstrumentLink" style="color:#2185d0;font-weight:normal;font-size:0.85em">+ Add new instrument</a>
							</label>
							<input type="text" id="instrumentSearch" placeholder="Search instruments..." autocomplete="off">
						</div>
						<div class="eight wide field">
							<label style="display:flex;justify-content:space-between;align-items:center">
								<span>Instrument item</span>
								<a href="#" id="addItemLink" style="color:#2185d0;font-weight:normal;font-size:0.85em;display:none">+ Add new item</a>
							</label>
							<input type="text" id="itemSearch" placeholder="Select instrument first" autocomplete="off" disabled>
						</div>
					</div>
					<div class="fields">
						<div class="eight wide field">
							<label>Observation name</label>
							<input type="text" name="observation_name" id="observationName" placeholder="Enter name or select instrument/item" required>
						</div>
						<div class="four wide field">
							<label>Value</label>
							<input type="text" name="observation_value" size="10" required>
						</div>
						<div class="four wide field">
							<label>Rater</label>
							<input type="text" name="observation_rater" size="15" value="<?=$GLOBALS['username']?>">
						</div>
					</div>
					<div class="fields">
						<div class="six wide field">
							<label>Start date</label>
							<input type="datetime-local" name="observation_startdate" id="obsStartdate">
						</div>
						<div class="three wide field">
							<label>Duration</label>
							<div class="ui right labeled input">
								<input type="text" name="observation_duration" size="10">
								<div class="ui label">sec</div>
							</div>
						</div>
						<div class="six wide field">
							<label>End date</label>
							<input type="datetime-local" name="observation_enddate">
						</div>
					</div>
				</form>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<div class="ui primary button" onclick="document.getElementById('addObsForm').submit()">
					<i class="plus icon"></i> Add
				</div>
			</div>
		</div>

		<?
		$groups     = array();
		$surveyMeta = array(); /* per-survey metadata keyed by 'survey_<id>' */

		/* joined to instrument_items and instruments to resolve states 1-4 (instrumentitem_id set);
		   joined to observation_surveys to pull survey dates/rater for states 1,3,5,7;
		   left joins fall through to NULLs for states 2,4,6,8 (no survey) */
		$sqlstring = "select a.*, ii.item_name, ins.instrument_name as linked_instrument_name, s.survey_startdate, s.survey_enddate, s.survey_rater, s.survey_notes, s.survey_visit, (select count(*) from observation_meta om where om.observation_id = a.observation_id) as meta_count from observations a left join instrument_items ii on a.instrumentitem_id = ii.instrumentitem_id left join instruments ins on ii.instrument_id = ins.instrument_id left join observation_surveys s on a.observationsurvey_id = s.survey_id where a.enrollment_id = $enrollmentid order by a.observation_name";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$observationid = $row['observation_id'];
			/* states 1-4: use the canonical item_name from the DB; states 5-8: fall back to the stored observation_name */
			$observation_name = $row['item_name'] != '' ? $row['item_name'] : $row['observation_name'];
			/* states 1-4: use the canonical instrument_name resolved via FK; states 5-6: use the legacy text hint */
			$instrument_name = $row['linked_instrument_name'] != '' ? $row['linked_instrument_name'] : $row['observation_instrument'];

			/* build the row information */
			$entry = array(
				'observationid' => $observationid,
				'instrumentitemid' => (int)$row['instrumentitem_id'],
				'observationName' => $observation_name,
				'obsInstrument' => $row['observation_instrument'],
				'instrumentitem' => $row['item_name'],
				'value' => $row['observation_value'],
				'rater' => $row['observation_rater'],
				'startdate' => $row['observation_startdate'],
				'duration' => $row['observation_duration'],
				'enddate' => $row['observation_enddate'],
				'dateshtml' => "<b>Entry</b> " . $row['observation_entrydate'] . "<br><b>Create</b> " . $row['observation_createdate'] . "<br><b>Modify</b> " . $row['observation_modifydate'],
				'metaCount' => (int)$row['meta_count'],
				'surveyid'  => !empty($row['observationsurvey_id']) ? (int)$row['observationsurvey_id'] : null
			);

			/* states 1-4 or 5-6: outer group by instrument name; states 7-8 → __none__ instrument group */
			$groupKey  = !empty($instrument_name) ? $instrument_name : '__none__';
			/* states 1,3,5,7 (have survey): inner group by survey_id; states 2,4,6,8 → '__none__' survey sub-group */
			$surveyKey = !empty($row['observationsurvey_id']) ? 'survey_' . (int)$row['observationsurvey_id'] : '__none__';
			$groups[$groupKey][$surveyKey][] = $entry;

			/* collect survey metadata the first time each unique survey_id is encountered */
			if (!empty($row['observationsurvey_id']) && !isset($surveyMeta[$surveyKey])) {
				$surveyMeta[$surveyKey] = array(
					'surveyId'  => (int)$row['observationsurvey_id'],
					'startdate' => $row['survey_startdate'],
					'enddate'   => $row['survey_enddate'],
					'rater'     => $row['survey_rater'],
					'notes'     => $row['survey_notes'],
					'visit'     => $row['survey_visit'],
				);
			}
		}

		/* sort instrument groups alphabetically; move __none__ to end */
		ksort($groups);
		if (isset($groups['__none__'])) {
			$noneGroups = $groups['__none__'];
			unset($groups['__none__']);
			$groups['__none__'] = $noneGroups;
		}

		$groupsJson = json_encode($groups, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

		/* build per-group metadata: instrument record existence, legacy count, unique item names, item count.
		 * hasInstrument drives the badge: true → states 1-4 possible (Linked or Partially linked);
		 * false → all rows are states 5-6 (Legacy badge).
		 * legacyCount: state-5/6 observations project-wide; 0 + hasInstrument = fully linked (states 1-4). */
		$groupMeta = array();
		$totalObs = 0;
		$instrumentCount = 0;
		/* __none__ group holds states 7-8; sum across survey sub-groups for the summary line */
		$unaffiliatedCount = 0;
		if (isset($groups['__none__'])) {
			foreach ($groups['__none__'] as $sRows) {
				$unaffiliatedCount += count($sRows);
			}
		}
		foreach ($groups as $key => $surveyGroups) {
			/* flatten survey sub-groups into a single row list for counting and uniqueItems */
			$rows = array_merge(...array_values($surveyGroups));
			$totalObs += count($rows);
			if ($key === '__none__') continue;
			$instrumentCount++;

			/* check whether a formal instrument record exists for this group name (distinguishes states 1-4 from 5-6) */
			$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id from instruments where project_id = ? and instrument_name = ? limit 1");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $key);
			$instrResult = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$hasInstrument = (mysqli_num_rows($instrResult) > 0);
			$instrRow = $hasInstrument ? mysqli_fetch_array($instrResult, MYSQLI_ASSOC) : false;
			mysqli_stmt_close($stmt);

			/* count legacy rows project-wide: observations that reference this instrument by free text (states 5-6)
			   but have no instrumentitem_id FK set — these are the observations the Formalize action will convert */
			$stmt = mysqli_prepare($GLOBALS['linki'], "select count(*) as cnt from observations o join enrollment e on o.enrollment_id = e.enrollment_id where e.project_id = ? and o.observation_instrument = ? and o.instrumentitem_id is null");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $key);
			$cntResult = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$cntRow = mysqli_fetch_array($cntResult, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			/* remove duplicate observation names in this group — used to populate the Formalize modal */
			$uniqueItems = array_values(array_unique(array_column($rows, 'observationName')));
			/* remove blank observation names */
			$uniqueItems = array_values(array_filter($uniqueItems, function($v) { return $v !== ''; }));
			sort($uniqueItems);

			/* count items in the instrument template — used for completeness display in survey sub-headers */
			$itemCount = 0;
			if ($hasInstrument && $instrRow) {
				$iid = (int)$instrRow['instrument_id'];
				$itemCountResult = MySQLiQuery("select count(*) as cnt from instrument_items where instrument_id = $iid", __FILE__, __LINE__);
				$itemCountRow = mysqli_fetch_array($itemCountResult, MYSQLI_ASSOC);
				$itemCount = (int)$itemCountRow['cnt'];
			}

			$groupMeta[$key] = array(
				'hasInstrument' => $hasInstrument,
				'instrumentId'  => $hasInstrument ? (int)$instrRow['instrument_id'] : null,
				'legacyCount'   => (int)$cntRow['cnt'],
				'uniqueItems'   => $uniqueItems,
				'itemCount'     => $itemCount,
			);
		}
		$groupMetaJson  = json_encode($groupMeta,  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$surveyMetaJson = json_encode($surveyMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		
		$topInformation = sprintf("%d observation%s from %d instrument%s (%d unaffiliated observation%s)", $totalObs, $totalObs != 1 ? 's' : '', $instrumentCount, $instrumentCount != 1 ? 's' : '', $unaffiliatedCount, $unaffiliatedCount != 1 ? 's' : '');
		
		?>
		<div style="margin: 8px 0">
			<button class="ui primary button" onclick="$('#addObsModal').modal('show')">
				<i class="plus icon"></i> Add observation
			</button>
			<button class="ui green button disabled" id="withSelectedBtn" disabled onclick="openBulkActionModal()">
				<i class="tasks icon"></i>With selected...
			</button>
		</div>
		
		<div class="ui top attached secondary inverted black segment" style="padding: 6px 14px"><?=$topInformation?></div>
		<div class="ui styled fluid accordion" id="observationAccordion" style="box-shadow: 0 4px 8px rgba(0,0,0,0.2)"></div>

		<script>
			const groupedData   = <?=$groupsJson?>;
			const groupMeta     = <?=$groupMetaJson?>;
			const surveyMeta    = <?=$surveyMetaJson?>;
			const PROJECT_ID    = <?=(int)$projectid?>;
			const ENROLLMENT_ID = <?=(int)$enrollmentid?>;

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

			/* variable column extracted so both instrColDefs and noneColDefs share the same object */
			const variableColDef = {
				headerName: 'Variable', field: 'observationName', flex: 1.3, minWidth: 180,
				editable: function(params) { return !params.data.instrumentitemid; },
				cellStyle: editableCellStyle
			};

			/* metadata indicator column: icon shown only when metaCount > 0; click lazy-loads the modal */
			const metaColDef = {
				headerName: 'Metadata',
				field: 'metaCount',
				width: 100, minWidth: 100, maxWidth: 100,
				filter: false, sortable: false, resizable: false,
				cellStyle: { 'text-align': 'center', 'display': 'flex', 'align-items': 'center', 'justify-content': 'center' },
				cellRenderer: function(params) {
					if (!params.value) return null;
					const btn = document.createElement('a');
					btn.href = '#';
					btn.title = params.value + ' metadata item' + (params.value !== 1 ? 's' : '');
					btn.innerHTML = '<i class="large database icon"></i>';
					btn.onclick = function(e) {
						e.preventDefault();
						openMetaModal(params.data.observationid, params.data.observationName);
					};
					return btn;
				}
			};

			/* column defs for instrument groups (states 1-6): no Instrument column because the instrument
			   name is already shown in the accordion header */
			const instrColDefs = [
				variableColDef,
				{ headerName: 'Instrument Item', field: 'instrumentitem', flex: 1, minWidth: 150 },
				{ headerName: 'Value', field: 'value', flex: 1, minWidth: 130, editable: true, cellStyle: editableCellStyle },
				{ headerName: 'Rater', field: 'rater', minWidth: 120, editable: true, cellStyle: function() { return { 'font-size': '9pt', cursor: 'text' }; } },
				{ headerName: 'Start date', field: 'startdate', minWidth: 160, editable: true, cellStyle: editableCellStyle },
				{ headerName: 'Duration', field: 'duration', minWidth: 110, editable: true, cellStyle: editableCellStyle },
				{ headerName: 'End date', field: 'enddate', minWidth: 160, editable: true, cellStyle: editableCellStyle },
				metaColDef,
				datesColDef,
				deleteColDef
			];

			/* column defs for the __none__ group (states 7-8): keep Variable, insert an editable Instrument
			   column so the user can assign a free-text hint (promoting toward state 6), then append the rest */
			const noneInstrumentColDef = { headerName: 'Instrument', field: 'obsInstrument', flex: 1, minWidth: 150, editable: true, cellStyle: editableCellStyle };
			const noneColDefs = [variableColDef, noneInstrumentColDef, ...instrColDefs.filter(function(c) { return c.field !== 'instrumentitem' && c.field !== 'observationName'; })];

			/* sanitizes a string for safe insertion into innerHTML */
			function escHtml(s) {
				return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
			}

			/* lazy-loads and displays observation_meta rows for the given observation */
			function openMetaModal(observationid, obsName) {
				document.getElementById('obsMetaModalName').textContent = obsName || ('Observation #' + observationid);
				document.getElementById('obsMetaContent').innerHTML = '<div class="ui active centered inline loader" style="margin:20px 0"></div>';
				$('#obsMetaModal').modal({ closable: true }).modal('show');
				$.getJSON('ajaxapi.php', { action: 'getobservationmeta', observationid: observationid }, function(data) {
					if (!data || data.error) {
						document.getElementById('obsMetaContent').innerHTML = '<p class="ui red text">Error loading metadata.</p>';
						return;
					}
					if (!data.length) {
						document.getElementById('obsMetaContent').innerHTML = '<p>No metadata found.</p>';
						return;
					}
					var html = '<table class="ui celled compact small table"><thead><tr><th>Variable</th><th>Value</th></tr></thead><tbody>';
					data.forEach(function(row) {
						html += '<tr><td>' + escHtml(row.variable) + '</td><td>' + escHtml(row.value) + '</td></tr>';
					});
					html += '</tbody></table>';
					document.getElementById('obsMetaContent').innerHTML = html;
				}).fail(function() {
					document.getElementById('obsMetaContent').innerHTML = '<p class="ui red text">Server error — please try again.</p>';
				});
			}

			/* tracks all grid API instances so onGridSelectionChanged can collect selected IDs across grids */
			const gridApis = [];
			const selectedObsIds = new Set();

			function onGridSelectionChanged() {
				selectedObsIds.clear();
				gridApis.forEach(function(api) {
					api.getSelectedNodes().forEach(function(node) {
						if (node.data) selectedObsIds.add(node.data.observationid);
					});
				});
				var count = selectedObsIds.size;
				var btn = document.getElementById('withSelectedBtn');
				if (count > 0) {
					btn.innerHTML = '<i class="tasks icon"></i>With ' + count + ' selected...';
					btn.disabled = false;
					btn.classList.remove('disabled');
				} else {
					btn.innerHTML = '<i class="tasks icon"></i>With selected...';
					btn.disabled = true;
					btn.classList.add('disabled');
				}
			}

			function updateBulkActionFields() {
				var action = document.getElementById('bulkActionSelect').value;
				['obsInstrument', 'rater', 'value', 'startdate', 'enddate', 'delete', 'convertmeta', 'movenewsurvey'].forEach(function(f) {
					document.getElementById('bulk_field_' + f).style.display = (action === f) ? 'block' : 'none';
				});
				var applyBtn = document.getElementById('bulkApplyBtn');
				if (action === 'delete') {
					applyBtn.className = 'ui red approve button';
					applyBtn.textContent = 'Delete';
					document.getElementById('bulkDeleteCount').textContent = selectedObsIds.size;
				} else if (action === 'convertmeta') {
					applyBtn.className = 'ui primary approve button';
					applyBtn.textContent = 'Convert';
				} else if (action === 'movenewsurvey') {
					applyBtn.className = 'ui primary approve button';
					applyBtn.textContent = 'Move';
				} else {
					applyBtn.className = 'ui primary approve button';
					applyBtn.textContent = 'Apply';
				}
				document.getElementById('bulkActionError').style.display = 'none';
			}

			function openBulkActionModal() {
				document.getElementById('bulkSelectedCount').textContent = selectedObsIds.size;
				document.getElementById('bulkActionSelect').value = '';
				['obsInstrument', 'rater', 'value', 'startdate', 'enddate', 'delete', 'convertmeta', 'movenewsurvey'].forEach(function(f) {
					document.getElementById('bulk_field_' + f).style.display = 'none';
				});
				document.getElementById('bulkActionError').style.display = 'none';
				var applyBtn = document.getElementById('bulkApplyBtn');
				applyBtn.className = 'ui primary approve button';
				applyBtn.textContent = 'Apply';
				applyBtn.classList.remove('loading', 'disabled');

				$('#bulkActionModal').modal({
					closable: false,
					onApprove: function() {
						var action = document.getElementById('bulkActionSelect').value;
						if (!action) {
							document.getElementById('bulkActionError').textContent = 'Please select an action.';
							document.getElementById('bulkActionError').style.display = '';
							return false;
						}
						var ids = Array.from(selectedObsIds);
						applyBtn.classList.add('loading', 'disabled');
						document.getElementById('bulkActionError').style.display = 'none';

						if (action === 'delete') {
							$.post('ajaxapi.php', {
								action: 'bulkdeleteobservations',
								observationids: JSON.stringify(ids)
							}, function(data) {
								if (data && data.error) {
									document.getElementById('bulkActionError').textContent = data.error;
									document.getElementById('bulkActionError').style.display = '';
									applyBtn.classList.remove('loading', 'disabled');
									return;
								}
								location.reload();
							}, 'json').fail(function() {
								document.getElementById('bulkActionError').textContent = 'Server error — please try again.';
								document.getElementById('bulkActionError').style.display = '';
								applyBtn.classList.remove('loading', 'disabled');
							});
						} else if (action === 'convertmeta') {
							$.post('ajaxapi.php', {
								action: 'bulkconvertvaluetometa',
								observationids: JSON.stringify(ids)
							}, function(data) {
								if (data && data.error) {
									document.getElementById('bulkActionError').textContent = data.error;
									document.getElementById('bulkActionError').style.display = '';
									applyBtn.classList.remove('loading', 'disabled');
									return;
								}
								location.reload();
							}, 'json').fail(function() {
								document.getElementById('bulkActionError').textContent = 'Server error — please try again.';
								document.getElementById('bulkActionError').style.display = '';
								applyBtn.classList.remove('loading', 'disabled');
							});
						} else if (action === 'movenewsurvey') {
							$.post('ajaxapi.php', {
								action: 'bulkmovenewsurvey',
								observationids: JSON.stringify(ids)
							}, function(data) {
								if (data && data.error) {
									document.getElementById('bulkActionError').textContent = data.error;
									document.getElementById('bulkActionError').style.display = '';
									applyBtn.classList.remove('loading', 'disabled');
									return;
								}
								location.reload();
							}, 'json').fail(function() {
								document.getElementById('bulkActionError').textContent = 'Server error — please try again.';
								document.getElementById('bulkActionError').style.display = '';
								applyBtn.classList.remove('loading', 'disabled');
							});
						} else {
							var inputMap = {
								'obsInstrument': 'bulkInstrumentValue',
								'rater':         'bulkRaterValue',
								'value':         'bulkValueValue',
								'startdate':     'bulkStartdateValue',
								'enddate':       'bulkEnddateValue',
							};
							var value = document.getElementById(inputMap[action]).value;
							$.post('ajaxapi.php', {
								action: 'bulkupdateobservations',
								observationids: JSON.stringify(ids),
								column: action,
								value: value
							}, function(data) {
								if (data && data.error) {
									document.getElementById('bulkActionError').textContent = data.error;
									document.getElementById('bulkActionError').style.display = '';
									applyBtn.classList.remove('loading', 'disabled');
									return;
								}
								location.reload();
							}, 'json').fail(function() {
								document.getElementById('bulkActionError').textContent = 'Server error — please try again.';
								document.getElementById('bulkActionError').style.display = '';
								applyBtn.classList.remove('loading', 'disabled');
							});
						}
						return false; /* keep modal open until reload */
					}
				}).modal('show');
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
				/* defer accordion build so the browser paints the loading message before JS runs */
				setTimeout(function() {

				const accordion = document.getElementById('observationAccordion');

				/* track inner accordion IDs so they can be initialized after DOM insertion */
				const innerAccordionIds = [];

				/* build two-level accordion: outer = instrument group, inner = survey sub-groups */
				Object.entries(groupedData).forEach(function([instrName, surveyGroups]) {
					const isNoneInstr = instrName === '__none__';
					const displayName = isNoneInstr ? 'No instrument' : instrName;
					const instrSafeId = instrName.replace(/[^a-zA-Z0-9]/g, '_');

					/* total observation count and formal survey count (excludes the __none__ sub-group) */
					let totalRows = 0;
					let surveyCount = 0;
					Object.entries(surveyGroups).forEach(function([sk, rows]) {
						totalRows += rows.length;
						if (sk !== '__none__') surveyCount++;
					});

					/* meta is null for the __none__ group (states 7-8) */
					const meta = isNoneInstr ? null : (groupMeta[instrName] || null);

					/* instrument-level badge: Linked (states 1-4 only), Partially linked (mix), Legacy (states 5-6 only) */
					let instrBadge = '';
					if (meta) {
						if (meta.hasInstrument && meta.legacyCount === 0) {
							instrBadge = '&nbsp;<span class="ui tiny green label" title="All observations linked via instrument item FK"><i class="check icon"></i> Linked</span>';
						} else if (meta.hasInstrument && meta.legacyCount > 0) {
							instrBadge = '&nbsp;<span class="ui tiny yellow label" title="Some observations still use a legacy text string"><i class="adjust icon"></i> Partially linked</span>'
								+ '&nbsp;<a class="formalize-link" href="#" data-instrument="' + escHtml(instrName) + '" style="font-size:0.85em"><i class="sync icon"></i>Convert remaining</a>';
						} else {
							instrBadge = '&nbsp;<span class="ui tiny orange label" title="No instrument record exists for this name"><i class="warning sign icon"></i> Legacy</span>'
								+ '&nbsp;<a class="formalize-link" href="#" data-instrument="' + escHtml(instrName) + '" style="font-size:0.85em"><i class="plus icon"></i>Create instrument</a>';
						}
					}

					/* outer accordion title */
					const outerTitle = document.createElement('div');
					outerTitle.className = 'title';
					const surveyLabel = surveyCount + ' survey' + (surveyCount !== 1 ? 's' : '');
					if (displayName == "No instrument")
						outerTitle.innerHTML = '<i class="dropdown icon"></i><b>' + escHtml(displayName) + '</b>&nbsp;<div class="ui small circular label">' + totalRows + ' observations</div>' + instrBadge;
					else
						outerTitle.innerHTML = '<i class="dropdown icon"></i><b>' + escHtml(displayName) + '</b>&nbsp;<div class="ui small circular label">' + totalRows + ' observations / ' + surveyLabel + '</div>' + instrBadge;
					accordion.appendChild(outerTitle);

					/* outer accordion content: holds the inner survey-level accordion */
					const outerContent = document.createElement('div');
					outerContent.className = 'content';

					const innerAccordion = document.createElement('div');
					innerAccordion.className = 'ui styled fluid accordion';
					const innerAccordionId = 'surveys_' + instrSafeId;
					innerAccordion.id = innerAccordionId;
					innerAccordionIds.push(innerAccordionId);

					/* sort survey keys: dated surveys descending by startdate, __none__ always last */
					const surveyKeys = Object.keys(surveyGroups).sort(function(a, b) {
						if (a === '__none__') return 1;
						if (b === '__none__') return -1;
						const dateA = (surveyMeta[a] && surveyMeta[a].startdate) ? surveyMeta[a].startdate : '';
						const dateB = (surveyMeta[b] && surveyMeta[b].startdate) ? surveyMeta[b].startdate : '';
						return dateB.localeCompare(dateA);
					});

					surveyKeys.forEach(function(surveyKey) {
						const rows        = surveyGroups[surveyKey];
						const isNoneSurvey = surveyKey === '__none__';
						const sm          = isNoneSurvey ? null : (surveyMeta[surveyKey] || null);

						/* completeness: count linked rows (have instrumentitem_id) vs total items in instrument template */
						const filledCount = rows.filter(function(r) { return r.instrumentitemid > 0; }).length;
						const totalItems  = meta ? (meta.itemCount || 0) : 0;

						/* survey sub-header: dated surveys show date + rater + completeness badge;
						   __none__ sub-group shows "Unaffiliated observations" + assign/new-survey buttons */
						let surveyTitleHtml;
						if (isNoneSurvey) {
							surveyTitleHtml = '<i class="dropdown icon"></i>'
								+ '<span style="color:#999"><i class="unlink icon"></i> Unaffiliated observations</span>'
								+ '&nbsp;<div class="ui small circular label">' + rows.length + '</div>';
						} else {
							const dateStr  = (sm && sm.startdate) ? sm.startdate.substring(0, 16) : 'No date';
							const raterStr = (sm && sm.rater) ? ' &mdash; ' + escHtml(sm.rater) : '';
							let complBadge = '';
							if (totalItems > 0) {
								const complColor = (filledCount === totalItems) ? 'green' : (filledCount > 0 ? 'yellow' : 'red');
								complBadge = '&nbsp;<span class="ui tiny ' + complColor + ' label">' + filledCount + ' / ' + totalItems + ' items</span>';
							}
							const editLink = '&nbsp;<a class="edit-survey-link" href="#" data-surveykey="' + escHtml(surveyKey) + '" style="font-size:0.85em"><i class="edit icon"></i>Edit survey</a>';
							surveyTitleHtml = '<i class="dropdown icon"></i><i class="calendar outline icon"></i> '
								+ escHtml(dateStr) + raterStr
								+ '&nbsp;<div class="ui small circular label">' + rows.length + '</div>'
								+ complBadge + editLink;
						}

						const surveyTitleDiv = document.createElement('div');
						surveyTitleDiv.className = 'title';
						surveyTitleDiv.innerHTML = surveyTitleHtml;
						innerAccordion.appendChild(surveyTitleDiv);

						const surveyContentDiv = document.createElement('div');
						surveyContentDiv.className = 'content';

						/* action bar: shown only for the unaffiliated sub-group of a named instrument */
						if (isNoneSurvey && !isNoneInstr) {
							const obsIds = rows.map(function(r) { return r.observationid; });
							const actionBar = document.createElement('div');
							actionBar.className = 'survey-action-bar';
							actionBar.style.cssText = 'margin-bottom:10px';
							actionBar.setAttribute('data-instrname', instrName);
							actionBar.setAttribute('data-instrid',   meta ? (meta.instrumentId || '') : '');
							actionBar.setAttribute('data-obsids',    JSON.stringify(obsIds));
							actionBar.innerHTML = '<button class="ui tiny primary button assign-survey-btn"><i class="linkify icon"></i> Assign to existing survey</button>'
								+ '&nbsp;<button class="ui tiny button new-survey-btn"><i class="calendar plus icon"></i> New survey</button>';
							surveyContentDiv.appendChild(actionBar);
						}

						/* AG Grid for this survey sub-section */
						const gridId  = 'grid_' + instrSafeId + '_' + surveyKey.replace(/[^a-zA-Z0-9]/g, '_');
						const height  = Math.max(120, Math.min(rows.length * 42 + 56, 600));
						const gridDiv = document.createElement('div');
						gridDiv.id    = gridId;
						gridDiv.style.cssText = 'height:' + height + 'px; width:100%';
						surveyContentDiv.appendChild(gridDiv);
						innerAccordion.appendChild(surveyContentDiv);

						const gridApi = agGrid.createGrid(gridDiv, {
							theme: agGrid.themeQuartz,
							/* __none__ instrument group (states 7-8): editable Instrument column included */
							columnDefs: isNoneInstr ? noneColDefs : instrColDefs,
							rowData: rows,
							defaultColDef: { sortable: true, filter: true, resizable: true },
							animateRows: false,
							suppressMovableColumns: true,
							rowSelection: { mode: 'multiRow' },
							onCellValueChanged: onCellValueChanged,
							onSelectionChanged: onGridSelectionChanged
						});
						gridApis.push(gridApi);
					});

					outerContent.appendChild(innerAccordion);
					accordion.appendChild(outerContent);
				});

				/* initialize outer accordion (exclusive:false allows multiple sections open simultaneously) */
				$('#observationAccordion').accordion({ exclusive: false, duration: 0 });
				/* initialize each inner survey-level accordion; stopPropagation on inner title clicks
				   prevents them from bubbling up to the outer accordion and collapsing the parent section */
				innerAccordionIds.forEach(function(id) {
					$('#' + id).accordion({ exclusive: false, duration: 0 });
					$('#' + id).on('click', '> .title', function(e) { e.stopPropagation(); });
					/* edit-survey-link is inside .title; intercept here (closer to target than .title) so
					   stopPropagation fires before Fomantic's accordion title handler toggles the panel */
					$('#' + id).on('click', 'a.edit-survey-link', function(e) {
						e.preventDefault();
						e.stopPropagation();
						$(this).trigger('edit-survey');
					});
				});

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

				/* ----- assign unaffiliated observations to an existing survey ----- */
				$(document).on('click', '.assign-survey-btn', function(e) {
					e.stopPropagation();
					const bar     = $(this).closest('.survey-action-bar');
					const instrId = bar.attr('data-instrid');
					const obsIds  = JSON.parse(bar.attr('data-obsids'));

					if (!instrId) {
						alert('No linked instrument. Use "Formalize" to create an instrument first.');
						return;
					}
					/* fetch existing surveys for this enrollment + instrument */
					$.getJSON('ajaxapi.php', { action: 'getsurveys', enrollmentid: ENROLLMENT_ID, instrumentid: instrId }, function(data) {
						if (!data || !data.length) {
							alert('No existing surveys found for this instrument. Use "New survey" to create one.');
							return;
						}
						const sel = $('#assignSurveySelect').empty();
						data.forEach(function(s) {
							const label = (s.startdate ? s.startdate.substring(0, 16) : 'No date') + (s.rater ? ' — ' + s.rater : '');
							sel.append($('<option>').val(s.survey_id).text(label));
						});
						$('#assignSurveyModal').modal({
							closable: false,
							onApprove: function() {
								$.post('ajaxapi.php', {
									action:         'assigntosurvey',
									surveyid:       $('#assignSurveySelect').val(),
									observationids: JSON.stringify(obsIds)
								}, function() { location.reload(); });
								return false;
							}
						}).modal('show');
					}).fail(function() { alert('Error fetching surveys. Please try again.'); });
				});

				/* ----- create new survey and assign unaffiliated observations to it ----- */
				$(document).on('click', '.new-survey-btn', function(e) {
					e.stopPropagation();
					const bar     = $(this).closest('.survey-action-bar');
					const instrId = bar.attr('data-instrid');
					const obsIds  = JSON.parse(bar.attr('data-obsids'));

					$('#newSurveyInstrId').val(instrId);
					$('#newSurveyStartdate').val(new Date(new Date() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16));
					$('#newSurveyEnddate').val('');
					$('#newSurveyRater').val('<?=$GLOBALS['username']?>');
					$('#newSurveyNotes').val('');
					$('#newSurveyError').hide();
					$('#newSurveySaveButton').removeClass('loading disabled');

					$('#newSurveyModal').modal({
						closable: false,
						onApprove: function() {
							$('#newSurveySaveButton').addClass('loading disabled');
							$.post('ajaxapi.php', {
								action:         'createandassignsurvey',
								enrollmentid:   ENROLLMENT_ID,
								instrumentid:   instrId,
								startdate:      $('#newSurveyStartdate').val(),
								enddate:        $('#newSurveyEnddate').val(),
								rater:          $('#newSurveyRater').val(),
								notes:          $('#newSurveyNotes').val(),
								observationids: JSON.stringify(obsIds)
							}, function(data) {
								if (data && data.error) {
									$('#newSurveyError').text(data.error).show();
									$('#newSurveySaveButton').removeClass('loading disabled');
									return;
								}
								location.reload();
							}, 'json').fail(function() {
								$('#newSurveyError').text('Server error — please try again.').show();
								$('#newSurveySaveButton').removeClass('loading disabled');
							});
							return false;
						}
					}).modal('show');
				});

				/* ----- edit an existing survey ----- */
				$(document).on('edit-survey', '.edit-survey-link', function(e) {
					const surveyKey = $(this).attr('data-surveykey');
					const sm = surveyMeta[surveyKey];
					$('#editSurveyId').val(sm ? sm.surveyId : '');
					$('#editSurveyStartdate').val(sm && sm.startdate ? sm.startdate.replace(' ', 'T').slice(0, 16) : '');
					$('#editSurveyEnddate').val(sm && sm.enddate   ? sm.enddate.replace(' ', 'T').slice(0, 16)   : '');
					$('#editSurveyRater').val(sm && sm.rater ? sm.rater : '');
					$('#editSurveyNotes').val(sm && sm.notes ? sm.notes : '');
					$('#editSurveyError').hide();
					$('#editSurveySaveButton').removeClass('loading disabled');

					$('#editSurveyModal').modal({
						closable: false,
						onApprove: function() {
							$('#editSurveySaveButton').addClass('loading disabled');
							$.post('ajaxapi.php', {
								action:    'updatesurvey',
								surveyid:  $('#editSurveyId').val(),
								startdate: $('#editSurveyStartdate').val(),
								enddate:   $('#editSurveyEnddate').val(),
								rater:     $('#editSurveyRater').val(),
								notes:     $('#editSurveyNotes').val()
							}, function(data) {
								if (data && data.error) {
									$('#editSurveyError').text(data.error).show();
									$('#editSurveySaveButton').removeClass('loading disabled');
									return;
								}
								location.reload();
							}, 'json').fail(function() {
								$('#editSurveyError').text('Server error — please try again.').show();
								$('#editSurveySaveButton').removeClass('loading disabled');
							});
							return false;
						}
					}).modal('show');
				});

				/* default the add-observation start date to now in local time */
				$('#obsStartdate').val(new Date(new Date() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16));

				$('#pageloading').hide();

				}, 0); /* end setTimeout — accordion build deferred to let browser paint loading message */

				/* ----- instrument autocomplete ----- */
				$('#instrumentSearch').autocomplete({
					source: function(request, response) {
						$.getJSON('ajaxapi.php', { action: 'searchinstruments', term: request.term, projectid: PROJECT_ID }, response);
					},
					appendTo: '#addObsModal',
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
					appendTo: '#addObsModal',
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

				/* ----- add instrument item modal ----- */
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
