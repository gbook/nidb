<?
 // ------------------------------------------------------------------------------
 // NiDB redcap_import.php
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");

	/* ------------------------------------------------------------------------------
	   REDCap import execution.

	   Runs synchronously in PHP (REDCap imports are browser-driven; the C++
	   moduleRemoteImport handles the Avicenna batches). Progress is still recorded
	   in remoteimport_batch / remoteimport_logs so the existing batch viewer works.

	   How REDCap record data maps onto NiDB:

	     A flat record export returns one row per (record, event, repeat instance).
	     A row is NOT per-form -- it carries the fields of every requested form at
	     once -- so which form a value belongs to comes from the mapping, not the
	     row. The exception is a repeating instrument, where redcap_repeat_instrument
	     names the single form that row belongs to.

	     Grouping key -> one observation_surveys row:
	       (record, event, form, repeat_instance)

	     Each mapped field -> one observations row under that survey.

	   Re-running an import updates matching observations in place rather than
	   inserting duplicates, so picking up edits made in REDCap is safe. Nothing is
	   ever deleted.
	   ------------------------------------------------------------------------------ */


	/* Marker for a datetime REDCap could not supply.

	   observation_surveys.survey_startdate is NOT NULL, and REDCap does not
	   timestamp records or repeat instances -- so a form with no mapped date field
	   and no survey timestamp has no collection date at all. This zero date is the
	   same marker NiDB already uses for an unknown observation date (it is the
	   DEFAULT on observations.observation_startdate), so "unknown" looks the same
	   across the schema.

	   Note the bookkeeping columns (entry/create/modify) are always real
	   timestamps: only the collection date is unknown. */
	define("REDCAP_UNKNOWN_DATETIME", "0000-00-00 00:00:00");


	/* -------------------------------------------- */
	/* ------- RedCapLogBatch --------------------- */
	/* -------------------------------------------- */
	function RedCapLogBatch($batchid, $event, $result, $message) {
		if ($batchid <= 0)
			return;
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into remoteimport_logs (remoteimportbatch_id, event, result, message) values (?,?,?,?)");
		mysqli_stmt_bind_param($stmt, 'isss', $batchid, $event, $result, $message);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- RedCapLoadMappings ----------------- */
	/* -------------------------------------------- */
	/* Load the project's REDCap mappings, indexed by "event|form|field|choicecode".
	   Only rows that actually point at a NiDB instrument item are usable. */
	function RedCapLoadMappings($projectid) {
		$sql = "select m.redcap_event, m.redcap_form, m.redcap_field, m.redcap_choice_code,
		               m.redcap_datatype, m.redcap_validation, m.redcap_datefield,
		               m.nidb_instrument, m.nidb_variable,
		               m.flag_date_from_field, m.flag_can_repeat, m.flag_import_meta,
		               i.instrument_name, ii.item_name, ii.item_type
		        from remoteimport_mapping m
		        join instruments i on i.instrument_id = m.nidb_instrument
		        join instrument_items ii on ii.instrumentitem_id = m.nidb_variable
		        where m.project_id = ? and m.source_type = 'redcap'";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($projectid));

		$maps = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ev     = (string)($row['redcap_event'] ?? '');
			$form   = (string)($row['redcap_form'] ?? '');
			$field  = (string)($row['redcap_field'] ?? '');
			$choice = (string)($row['redcap_choice_code'] ?? '');
			$key = "$ev|$form|$field|$choice";
			$maps[$key] = array(
				'event'         => $ev,
				'form'          => $form,
				'field'         => $field,
				'choicecode'    => $choice,
				'exportname'    => ($choice !== '') ? ($field . '___' . $choice) : $field,
				'datatype'      => (string)($row['redcap_datatype'] ?? ''),
				'validation'    => (string)($row['redcap_validation'] ?? ''),
				'datefield'     => (string)($row['redcap_datefield'] ?? ''),
				'instrumentid'  => (int)$row['nidb_instrument'],
				'itemid'        => (int)$row['nidb_variable'],
				'instrument'    => (string)$row['instrument_name'],
				'itemname'      => (string)$row['item_name'],
				'itemtype'      => (string)$row['item_type'],
				'datefromfield' => (int)($row['flag_date_from_field'] ?? 0),
				'canrepeat'     => (int)($row['flag_can_repeat'] ?? 0),
				'importmeta'    => (int)($row['flag_import_meta'] ?? 0)
			);
		}
		mysqli_stmt_close($stmt);
		return $maps;
	}


	/* -------------------------------------------- */
	/* ------- RedCapResolveEnrollment ------------ */
	/* -------------------------------------------- */
	/* Resolve a REDCap record identifier to a NiDB enrollment in this project.
	   Match order is fixed: subjects.uid first, then subject_altuid.altuid. Both
	   are scoped to the project, so a subject enrolled elsewhere is not matched. */
	function RedCapResolveEnrollment($subjectkey, $projectid, &$cache) {
		$subjectkey = trim($subjectkey ?? '');
		if ($subjectkey == '')
			return 0;

		if (isset($cache[$subjectkey]))
			return $cache[$subjectkey];

		/* by UID */
		$sql = "select e.enrollment_id from subjects s join enrollment e on e.subject_id = s.subject_id where s.uid = ? and e.project_id = ? limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'si', $subjectkey, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($subjectkey, $projectid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row) {
			/* by alternate UID */
			$sql = "select e.enrollment_id from subject_altuid a join enrollment e on e.subject_id = a.subject_id where a.altuid = ? and e.project_id = ? limit 1";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'si', $subjectkey, $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($subjectkey, $projectid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
		}

		$eid = $row ? (int)$row['enrollment_id'] : 0;
		$cache[$subjectkey] = $eid;
		return $eid;
	}


	/* -------------------------------------------- */
	/* ------- RedCapFindOrCreateSurvey ----------- */
	/* -------------------------------------------- */
	/* Find the observation_surveys row for this (enrollment, instrument, event,
	   instance, date) group, creating it if absent.

	   observation_surveys has no enrollment_id -- it is linked to a subject only
	   through observations -- so an existing survey is located by joining back
	   through observations for this enrollment. */
	/* $itemIds are the instrument items belonging to this group. They make the
	   lookup specific: filtering only on (enrollment, instrument, visit, instance,
	   date) can match a survey created for a *different* REDCap form that happens
	   to map to the same NiDB instrument, and 'limit 1' would then pick one
	   arbitrarily -- producing duplicate observations on the next run.

	   Returns the survey row ID, -1 in a dry run, or 0 if the insert failed. */
	function RedCapFindOrCreateSurvey($enrollmentid, $instrumentid, $visit, $instance, $startdate, $itemIds, $dryrun, &$created) {
		$instanceVal = ($instance === '') ? null : (int)$instance;
		/* survey_startdate is NOT NULL, so an unresolvable date becomes the
		   unknown-date marker. The lookup below uses the same value, so a re-run
		   still matches the survey it created. */
		$dateVal     = ($startdate === '') ? REDCAP_UNKNOWN_DATETIME : $startdate;

		$idlist = implode(",", array_map('intval', (array)$itemIds));
		if ($idlist === '')
			$idlist = '0';

		$sql = "select s.survey_id from observations o
		        join observation_surveys s on s.survey_id = o.observationsurvey_id
		        where o.enrollment_id = ? and o.instrumentitem_id in ($idlist) and s.instrument_id = ?
		          and (s.survey_visit <=> ?) and (s.survey_instance <=> ?) and (s.survey_startdate <=> ?)
		        limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'iisis', $enrollmentid, $instrumentid, $visit, $instanceVal, $dateVal);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($enrollmentid, $instrumentid, $visit, $instanceVal, $dateVal));
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		mysqli_stmt_close($stmt);

		if ($row)
			return (int)$row['survey_id'];

		if ($dryrun) {
			$created++;
			return -1; /* placeholder: nothing is written in a dry run */
		}

		$sql = "insert into observation_surveys (instrument_id, survey_startdate, survey_visit, survey_instance, survey_entrydate) values (?,?,?,?,now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'issi', $instrumentid, $dateVal, $visit, $instanceVal);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($instrumentid, $dateVal, $visit, $instanceVal));
		mysqli_stmt_close($stmt);

		/* MySQLiBoundQuery does not abort on a failed execute, so the insert ID
		   must be checked: a stale or zero value here would attach observations to
		   the wrong survey, or to none at all. */
		$newid = (int)mysqli_insert_id($GLOBALS['linki']);
		if ($newid < 1)
			return 0;

		$created++;
		return $newid;
	}


	/* -------------------------------------------- */
	/* ------- RedCapUpsertObservation ------------ */
	/* -------------------------------------------- */
	/* Insert, or update in place, the observation for one mapped value.
	   Returns 'inserted', 'updated', 'unchanged', or 'failed'. */
	function RedCapUpsertObservation($enrollmentid, $surveyid, $map, $value, $startdate, $batchid, $dryrun) {
		/* the collection date may be unknown, but the entry/create/modify stamps
		   below are always real timestamps */
		$dateVal = ($startdate === '') ? REDCAP_UNKNOWN_DATETIME : $startdate;

		$sql = "select observation_id, observation_value from observations where enrollment_id = ? and instrumentitem_id = ? and (observationsurvey_id <=> ?) limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'iii', $enrollmentid, $map['itemid'], $surveyid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($enrollmentid, $map['itemid'], $surveyid));
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		mysqli_stmt_close($stmt);

		if ($row) {
			if ((string)$row['observation_value'] === (string)$value)
				return 'unchanged';
			if ($dryrun)
				return 'updated';

			$obsid = (int)$row['observation_id'];
			$sql = "update observations set observation_value = ?, observation_startdate = ?, remotebatch_id = ?, observation_modifydate = now() where observation_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'ssii', $value, $dateVal, $batchid, $obsid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($value, $dateVal, $batchid, $obsid));
			mysqli_stmt_close($stmt);
			return 'updated';
		}

		if ($dryrun)
			return 'inserted';

		/* entry/create/modify are stamped with now() explicitly rather than relying
		   on the column defaults, so the row carries real timestamps even when the
		   collection date is unknown */
		$sql = "insert into observations (enrollment_id, instrumentitem_id, observationsurvey_id, remotebatch_id,
		            observation_name, observation_instrument, observation_value, observation_startdate,
		            observation_entrydate, observation_createdate, observation_modifydate)
		        values (?,?,?,?,?,?,?,?,now(),now(),now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'iiiissss',
			$enrollmentid, $map['itemid'], $surveyid, $batchid,
			$map['itemname'], $map['instrument'], $value, $dateVal);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($enrollmentid, $map['itemid'], $surveyid, $batchid, $map['itemname'], $map['instrument'], $value, $dateVal));
		mysqli_stmt_close($stmt);

		/* confirm the row was actually written (see RedCapFindOrCreateSurvey) */
		if ((int)mysqli_insert_id($GLOBALS['linki']) < 1)
			return 'failed';

		return 'inserted';
	}


	/* -------------------------------------------- */
	/* ------- RedCapCarryLabels ------------------ */
	/* -------------------------------------------- */
	/* Copy a REDCap field's choice labels into instrumentitem_map so the coded
	   values stored in observations can be displayed with their labels.

	   Only applies to enum items. Existing rows are left alone -- a label edited
	   in NiDB is not overwritten by REDCap. */
	function RedCapCarryLabels($itemid, $choices, $dryrun, &$added) {
		if (empty($choices))
			return;

		foreach ($choices as $code => $label) {
			$code  = (string)$code;
			$label = (string)$label;

			$sql = "select itemmap_id from instrumentitem_map where instrumentitem_id = ? and int_val = ? limit 1";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'is', $itemid, $code);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($itemid, $code));
			$exists = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if ($exists)
				continue;

			$added++;
			if ($dryrun)
				continue;

			$sql = "insert into instrumentitem_map (instrumentitem_id, int_val, string_val) values (?,?,?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'iss', $itemid, $code, $label);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($itemid, $code, $label));
			mysqli_stmt_close($stmt);
		}
	}


	/* -------------------------------------------- */
	/* ------- RedCapRunImport -------------------- */
	/* -------------------------------------------- */
	/* Execute a REDCap import. Returns a summary array; when $dryrun is true
	   nothing is written and the summary reports what would have happened. */
	function RedCapRunImport($importid, $projectid, $dryrun = false) {
		$importid  = (int)$importid;
		$projectid = (int)$projectid;

		$sum = array(
			'success'        => false,
			'message'        => '',
			'dryrun'         => (bool)$dryrun,
			'batchid'        => 0,
			'records'        => 0,
			'rows'           => 0,
			'surveys'        => 0,
			'inserted'       => 0,
			'updated'        => 0,
			'unchanged'      => 0,
			'labelsadded'    => 0,
			'failed'         => 0,
			'skippedblank'   => 0,
			'skippedcomplete'=> 0,
			'unmatched'      => array(),   /* record ids with no NiDB subject */
			'problems'       => array()    /* value coercion failures etc */
		);

		/* ----- import configuration + credentials ----- */
		$cred = RedCapGetCredentials($importid, $projectid);
		if (!$cred['success']) {
			$sum['message'] = $cred['message'];
			return $sum;
		}

		$sql = "select import_name, redcap_subjectid_field, redcap_raw_or_label, redcap_require_complete from remote_imports where remoteimport_id = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'ii', $importid, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($importid, $projectid));
		$cfg = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$cfg) {
			$sum['message'] = 'Remote import not found';
			return $sum;
		}

		$subjectField   = trim($cfg['redcap_subjectid_field'] ?? '');
		$rawOrLabel     = ($cfg['redcap_raw_or_label'] ?? 'raw');
		$requireComplete = (int)($cfg['redcap_require_complete'] ?? 0);

		if ($subjectField == '') {
			$sum['message'] = 'No REDCap subject ID field is configured for this import. Set it on the import before running.';
			return $sum;
		}

		/* ----- mappings ----- */
		$maps = RedCapLoadMappings($projectid);
		if (empty($maps)) {
			$sum['message'] = 'No REDCap field mappings are defined for this project.';
			return $sum;
		}

		/* forms and events to request; an empty event list means "all" */
		$forms = array();
		$events = array();
		foreach ($maps as $m) {
			if ($m['form'] !== '')  $forms[$m['form']] = true;
			if ($m['event'] !== '') $events[$m['event']] = true;
		}
		$forms = array_keys($forms);
		$events = array_keys($events);

		/* ----- field choices, for carrying labels forward ----- */
		$choicesByField = array();
		$meta = RedCapGetMetadata($cred['url'], $cred['token'], $forms);
		if ($meta['success'] && is_array($meta['data'])) {
			foreach ($meta['data'] as $f) {
				$fname = trim($f['field_name'] ?? '');
				if ($fname != '')
					$choicesByField[$fname] = RedCapFieldChoices($f);
			}
		}

		/* ----- pull the records -----
		   The subject ID field is requested explicitly: restricting the export to
		   the mapped forms would otherwise omit it whenever it lives on a form
		   that has no mappings, and every row would then look like it had a blank
		   subject. */
		$rec = RedCapExportRecords($cred['url'], $cred['token'], $forms, $events, $rawOrLabel, array($subjectField));
		if (!$rec['success']) {
			$sum['message'] = 'Record export failed: ' . $rec['message'];
			return $sum;
		}
		$rows = $rec['data'];
		$sum['rows'] = count($rows);

		/* Fail loudly if the subject ID column is absent: without it nothing can
		   be matched, and a silent "0 records" is far harder to diagnose. */
		if (!empty($rows) && is_array($rows[0]) && !array_key_exists($subjectField, $rows[0])) {
			$sum['message'] = "The configured subject ID field '" . $subjectField . "' was not returned by REDCap. Check that the field name is spelled correctly and that the API token has export rights to it.";
			return $sum;
		}

		/* ----- open a batch (skipped for a dry run) ----- */
		$batchid = 0;
		if (!$dryrun) {
			$sql = "insert into remoteimport_batch (remoteimport_id, status, next_state, start_date) values (?, 'running', '', now())";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'i', $importid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($importid));
			mysqli_stmt_close($stmt);
			$batchid = (int)mysqli_insert_id($GLOBALS['linki']);
			if ($batchid < 1) {
				/* without a batch there is nowhere to log and no provenance to
				   stamp on the observations, so do not import at all */
				$sum['message'] = 'Could not create an import batch; nothing was imported.';
				return $sum;
			}
			$sum['batchid'] = $batchid;
			RedCapLogBatch($batchid, 'ImportStart', 'Neutral', "REDCap import '" . $cfg['import_name'] . "' started: " . count($rows) . " row(s), " . count($maps) . " mapping(s)");
		}

		/* ----- walk the records ----- */
		$enrollCache = array();
		$seenRecords = array();
		$surveysCreated = 0;
		$labelWork = array();   /* instrumentitem_id => code => label */

		foreach ($rows as $row) {
			if (!is_array($row))
				continue;

			$recordKey = trim($row[$subjectField] ?? '');
			if ($recordKey == '')
				continue;
			$seenRecords[$recordKey] = true;

			$enrollmentid = RedCapResolveEnrollment($recordKey, $projectid, $enrollCache);
			if ($enrollmentid < 1) {
				$sum['unmatched'][$recordKey] = true;
				continue;
			}

			$rowEvent      = trim($row['redcap_event_name'] ?? '');
			$rowInstrument = trim($row['redcap_repeat_instrument'] ?? '');
			$rowInstance   = trim((string)($row['redcap_repeat_instance'] ?? ''));

			/* Which mappings apply to this row. A repeating-instrument row belongs
			   to exactly one form; a normal row carries every form's fields. */
			$applicable = array();
			foreach ($maps as $m) {
				if ($m['event'] !== $rowEvent)
					continue;
				if (($rowInstrument !== '') && ($m['form'] !== $rowInstrument))
					continue;
				/* Group by (form, NiDB instrument), not by form alone: one REDCap
				   form's fields may legitimately be mapped to more than one NiDB
				   instrument, and each instrument needs its own survey row. */
				$gk = $m['form'] . '|' . $m['instrumentid'];
				if (!isset($applicable[$gk]))
					$applicable[$gk] = array('form' => $m['form'], 'instrumentid' => $m['instrumentid'], 'maps' => array());
				$applicable[$gk]['maps'][] = $m;
			}

			foreach ($applicable as $group) {
				$form         = $group['form'];
				$formMaps     = $group['maps'];
				$instrumentid = $group['instrumentid'];

				/* form completion gate */
				if ($requireComplete) {
					$statusKey = $form . '_complete';
					if (isset($row[$statusKey]) && ((string)$row[$statusKey] !== '2')) {
						$sum['skippedcomplete']++;
						continue;
					}
				}

				/* survey date: the mapped date field wins, then a survey
				   timestamp, then nothing (REDCap does not date instances) */
				$startdate = '';
				foreach ($formMaps as $m) {
					if ($m['datefromfield'] && ($m['datefield'] !== '') && isset($row[$m['datefield']])) {
						$startdate = RedCapNormalizeDateTime($row[$m['datefield']]);
						if ($startdate !== '')
							break;
					}
				}
				if ($startdate === '') {
					$tsKey = $form . '_timestamp';
					if (isset($row[$tsKey]))
						$startdate = RedCapNormalizeDateTime($row[$tsKey]);
				}

				$visit = ($rowEvent !== '') ? $rowEvent : null;

				/* skip the group entirely if it has no values at all, so empty
				   forms do not create empty surveys */
				$hasValue = false;
				foreach ($formMaps as $m) {
					if (trim((string)($row[$m['exportname']] ?? '')) !== '') { $hasValue = true; break; }
				}
				if (!$hasValue)
					continue;

				/* the group's item IDs disambiguate the survey lookup */
				$groupItemIds = array();
				foreach ($formMaps as $gm)
					$groupItemIds[] = $gm['itemid'];

				$surveyid = RedCapFindOrCreateSurvey($enrollmentid, $instrumentid, $visit, $rowInstance, $startdate, $groupItemIds, $dryrun, $surveysCreated);

				/* Never attach observations to a survey that could not be created:
				   that would orphan them or bind them to the wrong survey. Skip the
				   whole group and report it. */
				if ($surveyid == 0) {
					$sum['failed']++;
					$sum['problems'][] = "$recordKey / $form: could not create the survey, so this form's values were skipped";
					continue;
				}

				foreach ($formMaps as $m) {
					$raw = $row[$m['exportname']] ?? null;
					if ($raw === null || trim((string)$raw) === '') {
						$sum['skippedblank']++;
						continue;
					}

					$c = RedCapCoerceValue($raw, $m['itemtype']);
					if (!$c['ok']) {
						$sum['problems'][] = "$recordKey / " . $m['exportname'] . ": " . $c['message'] . " (item '" . $m['itemname'] . "' is " . $m['itemtype'] . ")";
						continue;
					}

					$what = RedCapUpsertObservation($enrollmentid, $surveyid, $m, $c['value'], $startdate, $batchid, $dryrun);
					if ($what === 'inserted')       $sum['inserted']++;
					else if ($what === 'updated')   $sum['updated']++;
					else if ($what === 'unchanged') $sum['unchanged']++;
					else {
						$sum['failed']++;
						$sum['problems'][] = "$recordKey / " . $m['exportname'] . ": the observation could not be written";
					}

					/* Note which coded items need labels. The labels themselves are
					   written once after the walk: doing it here would repeat the
					   same lookups for every observation of every record, and in a
					   dry run (where nothing is inserted) would count the same
					   missing label once per row. */
					if ($m['itemtype'] === 'enum') {
						$ch = isset($choicesByField[$m['field']]) ? $choicesByField[$m['field']] : array();
						if ($m['choicecode'] !== '')
							$ch = array('1' => 'Checked', '0' => 'Unchecked');
						if (!empty($ch))
							$labelWork[$m['itemid']] = $ch;
					}
				}
			}
		}

		/* ----- carry value labels into instrumentitem_map (once per item) ----- */
		foreach ($labelWork as $itemid => $choices)
			RedCapCarryLabels($itemid, $choices, $dryrun, $sum['labelsadded']);

		$sum['records'] = count($seenRecords);
		$sum['surveys'] = $surveysCreated;
		$sum['unmatched'] = array_keys($sum['unmatched']);
		$sum['success'] = true;

		if (!$dryrun) {
			$msg = sprintf("Imported %d observation(s), updated %d, unchanged %d, across %d record(s). %d survey(s) created, %d label(s) added.",
				$sum['inserted'], $sum['updated'], $sum['unchanged'], $sum['records'], $sum['surveys'], $sum['labelsadded']);
			RedCapLogBatch($batchid, 'ImportObservation', 'Success', $msg);

			if (!empty($sum['unmatched']))
				RedCapLogBatch($batchid, 'ImportSubject', 'Warning', count($sum['unmatched']) . " REDCap record(s) had no matching NiDB subject: " . implode(', ', array_slice($sum['unmatched'], 0, 50)));
			foreach (array_slice($sum['problems'], 0, 50) as $p)
				RedCapLogBatch($batchid, 'ImportObservation', 'Warning', $p);

			/* a run that could not write everything is recorded as an error, so a
			   partial import is not silently reported as complete */
			$finalStatus = ($sum['failed'] > 0) ? 'error' : 'complete';
			RedCapLogBatch($batchid, 'ImportEnd', ($sum['failed'] > 0) ? 'Error' : 'Success',
				($sum['failed'] > 0)
					? ('REDCap import finished with ' . $sum['failed'] . ' write failure(s)')
					: 'REDCap import finished');

			$sql = "update remoteimport_batch set status = ?, end_date = now() where remoteimportbatch_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'si', $finalStatus, $batchid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($finalStatus, $batchid));
			mysqli_stmt_close($stmt);
		}

		return $sum;
	}
?>
