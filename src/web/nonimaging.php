<?
 // ------------------------------------------------------------------------------
 // NiDB nonimaging.php
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

	/* Search/export of a project's non-imaging data. See doc/nonimaging-export.md for the design */

	define("LEGIT_REQUEST", true);

	session_start();
	require "functions.php";
	require "includes_php.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = (int)GetVariable("projectid");

	/* server-side performance metrics. Only returned to siteadmins */
	$GLOBALS['perf'] = array('start' => microtime(true), 'queries' => array());

	/* AJAX handlers. These return JSON, so they run before any HTML is output */
	if ($action == "getobservationlist") {
		GetObservationListJSON($projectid);
		exit(0);
	}
	if ($action == "getsubjectlist") {
		GetSubjectListJSON($projectid);
		exit(0);
	}
	if ($action == "preview") {
		PreviewExportJSON($projectid);
		exit(0);
	}
	if ($action == "download") {
		DownloadExport($projectid, (GetVariable("dryrun") == "1"));
		exit(0);
	}
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Search/export non-imaging data</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "includes_html.php";
	require "menu.php";

	/* this page is read-only (search and export), so there are no mutating actions */
	list($project, $error) = GetProject($projectid);
	if ($error != "")
		Error($error);
	else
		DisplayExportPage($projectid, $project);


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetProject ------------------------- */
	/* -------------------------------------------- */
	/* Returns array(project row, error message). The error is set if the project doesn't exist or
	   the user doesn't have View Data on the project. Viewing and exporting are the same permission */
	function GetProject($projectid) {
		$projectid = (int)$projectid;

		$sqlstring = "select project_name, project_costcenter from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row)
			return array(null, "Invalid project ID");

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'viewdata', $projectid))
			return array(null, "You do not have permissions to view data in this project");

		return array($row, "");
	}


	/* -------------------------------------------- */
	/* ------- TimedQuery ------------------------- */
	/* -------------------------------------------- */
	/* Runs a bound query and returns all rows. Records the query and fetch times in $GLOBALS['perf'] */
	function TimedQuery($label, $sqlstring, $types = "", $params = []) {
		$start = microtime(true);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		if (($stmt) && ($types != ""))
			mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$queried = microtime(true);

		$rows = array();
		if ($result) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
				$rows[] = $row;
		}
		if ($stmt)
			mysqli_stmt_close($stmt);
		$fetched = microtime(true);

		/* queries run in batches have the same label, and are combined into one entry */
		$queryms = ($queried - $start) * 1000;
		$fetchms = ($fetched - $queried) * 1000;
		$queries = &$GLOBALS['perf']['queries'];
		$last = count($queries) - 1;
		if (($last >= 0) && ($queries[$last]['label'] == $label)) {
			$queries[$last]['calls']++;
			$queries[$last]['queryms'] = round($queries[$last]['queryms'] + $queryms, 1);
			$queries[$last]['fetchms'] = round($queries[$last]['fetchms'] + $fetchms, 1);
			$queries[$last]['rows'] += count($rows);
		}
		else
			$queries[] = array('label' => $label, 'calls' => 1, 'queryms' => round($queryms, 1), 'fetchms' => round($fetchms, 1), 'rows' => count($rows));

		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- SendJSON --------------------------- */
	/* -------------------------------------------- */
	/* Prints $data as JSON. Adds the performance metrics for siteadmins */
	function SendJSON($data) {
		header('Content-Type: application/json');
		if ($GLOBALS['issiteadmin'] ?? false) {
			$data['perf'] = array(
				'totalms' => round((microtime(true) - $GLOBALS['perf']['start']) * 1000, 1),
				'peakmemory' => memory_get_peak_usage(true),
				'queries' => $GLOBALS['perf']['queries']
			);
		}
		echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
	}


	/* -------------------------------------------- */
	/* ------- ValidDate -------------------------- */
	/* -------------------------------------------- */
	/* Returns the datetime, or "" if it is null or a zero date */
	function ValidDate($datetime) {
		if (($datetime == null) || (substr($datetime, 0, 4) == "0000"))
			return "";
		return $datetime;
	}


	/* -------------------------------------------- */
	/* ------- GetObservationListJSON ------------- */
	/* -------------------------------------------- */
	/* AJAX handler: every observation in the project, as instrument items and unaffiliated
	   observation names, with counts and date ranges. Observations from deleted surveys
	   (survey_status 7) and deleted subjects are excluded. Observations whose instrument item no longer exists have no
	   type, so they're treated as unaffiliated (regular values, exported as CSV) */
	function GetObservationListJSON($projectid) {
		$projectid = (int)$projectid;

		list($project, $error) = GetProject($projectid);
		if ($error != "") {
			SendJSON(array('error' => $error));
			return;
		}

		/* release the session lock so the user's other pages aren't blocked while this runs */
		session_write_close();
		set_time_limit(0);

		/* one pass over the project's observations: grouped by instrument item, or by name for
		   unaffiliated observations (no instrument item, or the item no longer exists) */
		$sqlstring = "select coalesce(ii.instrumentitem_id, 0) 'itemid', if(ii.instrumentitem_id is null, o.observation_name, '') 'obsname', count(*) 'num', count(distinct e.subject_id) 'numsubjects', min(o.observation_startdate) 'firstdate', max(o.observation_startdate) 'lastdate' from observations o join enrollment e on o.enrollment_id = e.enrollment_id join subjects sj on e.subject_id = sj.subject_id left join instrument_items ii on o.instrumentitem_id = ii.instrumentitem_id left join observation_surveys s on o.observationsurvey_id = s.survey_id where e.project_id = ? and sj.isactive = 1 and (s.survey_status is null or s.survey_status <> 7) group by itemid, obsname";
		$counts = TimedQuery("Observation counts", $sqlstring, 'i', [$projectid]);

		/* the project's instrument items, including those with no observations yet */
		$sqlstring = "select ii.instrumentitem_id, ii.item_name, ii.item_type, ii.item_order, i.instrument_id, i.instrument_name from instrument_items ii join instruments i on ii.instrument_id = i.instrument_id where i.project_id = ?";
		$itemrows = TimedQuery("Project instrument items", $sqlstring, 'i', [$projectid]);

		$items = array();
		foreach ($itemrows as $row)
			$items[(int)$row['instrumentitem_id']] = $row;

		/* observations can point to an instrument item from another project's instrument. Get those items too */
		$missing = array();
		foreach ($counts as $row) {
			if (((int)$row['itemid'] > 0) && (!isset($items[(int)$row['itemid']])))
				$missing[] = (int)$row['itemid'];
		}
		if (count($missing) > 0) {
			$sqlstring = "select ii.instrumentitem_id, ii.item_name, ii.item_type, ii.item_order, i.instrument_id, i.instrument_name from instrument_items ii join instruments i on ii.instrument_id = i.instrument_id where ii.instrumentitem_id in (" . implode(",", array_fill(0, count($missing), "?")) . ")";
			$itemrows = TimedQuery("Other projects' instrument items", $sqlstring, str_repeat('i', count($missing)), $missing);
			foreach ($itemrows as $row)
				$items[(int)$row['instrumentitem_id']] = $row;
		}

		/* combine the counts with the instrument items */
		$itemcounts = array();
		$unaffiliated = array();
		foreach ($counts as $row) {
			$itemid = (int)$row['itemid'];
			$count = array('num' => (int)$row['num'], 'numsubjects' => (int)$row['numsubjects'], 'firstdate' => ValidDate($row['firstdate']), 'lastdate' => ValidDate($row['lastdate']));
			if ($itemid > 0)
				$itemcounts[$itemid] = $count;
			else
				$unaffiliated[] = array('name' => $row['obsname']) + $count;
		}

		$instrumentitems = array();
		foreach ($items as $itemid => $row) {
			$count = $itemcounts[$itemid] ?? array('num' => 0, 'numsubjects' => 0, 'firstdate' => '', 'lastdate' => '');
			$instrumentitems[] = array(
				'itemid' => $itemid,
				'name' => $row['item_name'],
				'type' => $row['item_type'] ?? '',
				'order' => (int)$row['item_order'],
				'instrumentid' => (int)$row['instrument_id'],
				'instrument' => $row['instrument_name']
			) + $count;
		}

		SendJSON(array('instrumentitems' => $instrumentitems, 'unaffiliated' => $unaffiliated));
	}


	/* -------------------------------------------- */
	/* ------- GetSubjectListJSON ----------------- */
	/* -------------------------------------------- */
	/* AJAX handler: the project's enrolled (active) subjects, with their number of observations */
	function GetSubjectListJSON($projectid) {
		$projectid = (int)$projectid;

		list($project, $error) = GetProject($projectid);
		if ($error != "") {
			SendJSON(array('error' => $error));
			return;
		}

		session_write_close();
		set_time_limit(0);

		$sqlstring = "select e.enrollment_id, s.uid, s.sex, e.enroll_subgroup, e.enroll_status, (select group_concat(a.altuid order by a.isprimary desc, a.altuid separator ', ') from subject_altuid a where a.subject_id = s.subject_id) 'altuids', (select count(*) from observations o where o.enrollment_id = e.enrollment_id) 'numobservations' from enrollment e join subjects s on e.subject_id = s.subject_id where e.project_id = ? and s.isactive = 1 order by s.uid";
		$rows = TimedQuery("Subjects", $sqlstring, 'i', [$projectid]);

		$subjects = array();
		foreach ($rows as $row) {
			$subjects[] = array(
				'enrollmentid' => (int)$row['enrollment_id'],
				'uid' => $row['uid'] ?? '',
				'altuids' => $row['altuids'] ?? '',
				'sex' => $row['sex'] ?? '',
				'group' => $row['enroll_subgroup'] ?? '',
				'status' => $row['enroll_status'] ?? '',
				'numobservations' => (int)$row['numobservations']
			);
		}

		SendJSON(array('subjects' => $subjects));
	}


	/* -------------------------------------------- */
	/* ------- Placeholders ----------------------- */
	/* -------------------------------------------- */
	/* "?,?,?" for an 'in (...)' list of $num values */
	function Placeholders($num) {
		return implode(",", array_fill(0, $num, "?"));
	}


	/* -------------------------------------------- */
	/* ------- ParseExportRequest ----------------- */
	/* -------------------------------------------- */
	/* The export request is POSTed as one JSON string (a selection can be thousands of names, more
	   than PHP's max_input_vars). Returns array(request, errors) */
	function ParseExportRequest() {
		$json = json_decode($_POST['request'] ?? '', true);
		if (!is_array($json))
			return array(null, array("Invalid export request"));

		$errors = array();
		$req = array(
			'format' => (string)($json['format'] ?? ''),
			'layout' => (string)($json['layout'] ?? ''),
			'itemids' => array(),
			'names' => array(),
			'subjectmode' => ((($json['subjectmode'] ?? '') == 'choose') ? 'choose' : 'all'),
			'enrollmentids' => array(),
			'startdate' => '',
			'enddate' => '',
			'repeats' => (string)($json['repeats'] ?? 'all'),
			'dates' => (bool)($json['dates'] ?? false)
		);

		if (!in_array($req['format'], array('csv', 'files')))
			$errors[] = "Only the CSV and Files formats can be exported";
		elseif ($req['format'] == 'csv') {
			if (!in_array($req['layout'], array('long', 'wide')))
				$errors[] = "Invalid CSV layout";
			if (($req['layout'] == 'wide') && (!in_array($req['repeats'], array('all', 'first', 'last'))))
				$errors[] = "Invalid repeated observations option";
		}

		/* instrument item IDs, and unaffiliated observation names (de-duplicated) */
		foreach ((array)($json['itemids'] ?? array()) as $id) {
			if ((int)$id > 0)
				$req['itemids'][(int)$id] = (int)$id;
		}
		$req['itemids'] = array_values($req['itemids']);
		foreach ((array)($json['names'] ?? array()) as $name) {
			if (is_string($name))
				$req['names'][$name] = $name;
		}
		$req['names'] = array_values($req['names']);
		if (($req['format'] != 'csv') && (count($req['names']) > 0))
			$errors[] = "Unaffiliated observations can only be exported as CSV";
		if ((count($req['itemids']) + count($req['names'])) == 0)
			$errors[] = "No observations are selected";
		if ((count($req['itemids']) + count($req['names'])) > 50000)
			$errors[] = "Too many observations are selected (more than 50,000)";

		if ($req['subjectmode'] == 'choose') {
			foreach ((array)($json['enrollmentids'] ?? array()) as $id) {
				if ((int)$id > 0)
					$req['enrollmentids'][(int)$id] = (int)$id;
			}
			if (count($req['enrollmentids']) == 0)
				$errors[] = "No subjects are selected";
		}

		/* dates are YYYY-MM-DD, in UTC */
		foreach (array('startdate', 'enddate') as $field) {
			$date = (string)($json[$field] ?? '');
			if ($date == '')
				continue;
			$dt = DateTime::createFromFormat('!Y-m-d', $date);
			if ((!$dt) || ($dt->format('Y-m-d') != $date))
				$errors[] = "Invalid date [" . htmlspecialchars($date) . "]";
			else
				$req[$field] = $date;
		}
		if (($req['startdate'] != '') && ($req['enddate'] != '') && ($req['startdate'] > $req['enddate']))
			$errors[] = "The start date is after the end date";

		return array($req, $errors);
	}


	/* -------------------------------------------- */
	/* ------- ValidateExportItems ---------------- */
	/* -------------------------------------------- */
	/* Checks the selected instrument items exist and can be exported in the request's format.
	   Returns array(itemid => item row (item_name, item_type, item_order, instrument_name), errors) */
	function ValidateExportItems($req) {
		$items = array();
		$errors = array();
		if (count($req['itemids']) == 0)
			return array($items, $errors);

		$sqlstring = "select ii.instrumentitem_id, ii.item_name, ii.item_type, ii.item_order, i.instrument_name from instrument_items ii join instruments i on ii.instrument_id = i.instrument_id where ii.instrumentitem_id in (" . Placeholders(count($req['itemids'])) . ")";
		$rows = TimedQuery("Validate instrument items", $sqlstring, str_repeat('i', count($req['itemids'])), $req['itemids']);

		$formattypes = array('csv' => array('enum', 'int', 'double', 'string', 'datetime', ''), 'files' => array('image', 'csv', 'json'));
		$formatlabels = array('csv' => 'CSV', 'files' => 'Files');
		foreach ($rows as $row) {
			$items[(int)$row['instrumentitem_id']] = $row;
			if (!in_array($row['item_type'] ?? '', $formattypes[$req['format']]))
				$errors[] = "[" . htmlspecialchars($row['item_name']) . "] is a " . htmlspecialchars($row['item_type'] == '' ? 'blank' : $row['item_type']) . " item, which can't be exported as " . $formatlabels[$req['format']];
		}
		$nummissing = count($req['itemids']) - count($rows);
		if ($nummissing > 0)
			$errors[] = "$nummissing of the selected instrument items no longer exist. Reload the page and select the observations again";

		return array($items, $errors);
	}


	/* -------------------------------------------- */
	/* ------- GetExportSubjects ------------------ */
	/* -------------------------------------------- */
	/* The project's active subjects to export, in UID order. Returns array(enrollmentid => array(uid,
	   altuid), warnings). The altuid is the subject's alternate UID for this enrollment (primary first) */
	function GetExportSubjects($projectid, $req) {
		$projectid = (int)$projectid;

		$sqlstring = "select e.enrollment_id, s.uid, (select a.altuid from subject_altuid a where a.subject_id = s.subject_id order by (a.enrollment_id = e.enrollment_id) desc, a.isprimary desc, a.altuid limit 1) 'altuid' from enrollment e join subjects s on e.subject_id = s.subject_id where e.project_id = ? and s.isactive = 1 order by s.uid, e.enrollment_id";
		$rows = TimedQuery("Subjects", $sqlstring, 'i', [$projectid]);

		$subjects = array();
		$warnings = array();
		foreach ($rows as $row) {
			$id = (int)$row['enrollment_id'];
			if (($req['subjectmode'] == 'choose') && (!isset($req['enrollmentids'][$id])))
				continue;
			$subjects[$id] = array('uid' => $row['uid'] ?? '', 'altuid' => $row['altuid'] ?? '');
		}

		if ($req['subjectmode'] == 'choose') {
			$nummissing = count($req['enrollmentids']) - count($subjects);
			if ($nummissing > 0)
				$warnings[] = "$nummissing of the selected subjects are no longer enrolled in this project, and were skipped";
		}

		return array($subjects, $warnings);
	}


	/* -------------------------------------------- */
	/* ------- ExportWhere ------------------------ */
	/* -------------------------------------------- */
	/* The 'from ... where ...' for the selected observations of a batch of enrollments. Unaffiliated
	   observations (no instrument item, or the item no longer exists) are selected by name. Observations
	   from deleted surveys are excluded. The Files format also joins the observations' files (f).
	   Returns array(sql, types, params) */
	function ExportWhere($req, $enrollmentids) {
		$filesjoin = (($req['format'] == 'files') ? " left join files f on o.observation_fileid = f.file_id" : "");
		$sqlstring = "from observations o left join instrument_items ii on o.instrumentitem_id = ii.instrumentitem_id left join instruments i on ii.instrument_id = i.instrument_id left join observation_surveys sv on o.observationsurvey_id = sv.survey_id" . $filesjoin . " where o.enrollment_id in (" . Placeholders(count($enrollmentids)) . ") and (sv.survey_status is null or sv.survey_status <> 7)";
		$types = str_repeat('i', count($enrollmentids));
		$params = array_values($enrollmentids);

		$selected = array();
		if (count($req['itemids']) > 0) {
			$selected[] = "ii.instrumentitem_id in (" . Placeholders(count($req['itemids'])) . ")";
			$types .= str_repeat('i', count($req['itemids']));
			$params = array_merge($params, $req['itemids']);
		}
		if (count($req['names']) > 0) {
			$selected[] = "(ii.instrumentitem_id is null and o.observation_name in (" . Placeholders(count($req['names'])) . "))";
			$types .= str_repeat('s', count($req['names']));
			$params = array_merge($params, $req['names']);
		}
		$sqlstring .= " and (" . implode(" or ", $selected) . ")";

		/* the date range includes the whole end date */
		if ($req['startdate'] != '') {
			$sqlstring .= " and o.observation_startdate >= ?";
			$types .= 's';
			$params[] = $req['startdate'] . " 00:00:00";
		}
		if ($req['enddate'] != '') {
			$sqlstring .= " and o.observation_startdate < ?";
			$types .= 's';
			$params[] = date('Y-m-d', strtotime($req['enddate'] . " +1 day")) . " 00:00:00";
		}

		return array($sqlstring, $types, $params);
	}


	/* -------------------------------------------- */
	/* ------- NameKey ---------------------------- */
	/* -------------------------------------------- */
	/* The key of an observation column: 'i<itemid>' for instrument items, 'u:<name>' for unaffiliated
	   names. Names are compared like the database does: not case sensitive, ignoring trailing spaces */
	function NameKey($itemid, $name) {
		if ((int)$itemid > 0)
			return 'i' . (int)$itemid;
		return 'u:' . mb_strtolower(rtrim($name));
	}


	/* -------------------------------------------- */
	/* ------- RowKeySQL -------------------------- */
	/* -------------------------------------------- */
	/* Wide layout with all repeats: the row an observation belongs to. Observations from a survey are
	   grouped by survey, others by date (UTC) */
	function RowKeySQL() {
		return "if(sv.survey_id is not null, concat('s', sv.survey_id), concat('d', date(o.observation_startdate)))";
	}


	/* -------------------------------------------- */
	/* ------- UniqueHeader ----------------------- */
	/* -------------------------------------------- */
	/* Returns $header, with ' (2)', ' (3)', ... added if it's already used (not case sensitive) */
	function UniqueHeader($header, &$used) {
		$unique = $header;
		$num = 2;
		while (isset($used[mb_strtolower($unique)]))
			$unique = $header . " (" . $num++ . ")";
		$used[mb_strtolower($unique)] = true;
		return $unique;
	}


	/* -------------------------------------------- */
	/* ------- ExportColumns ---------------------- */
	/* -------------------------------------------- */
	/* Returns array(header row, observation columns). The observation columns (wide layout only) are
	   key => array(header, dateheader), in order: instrument items by instrument and item order, then
	   unaffiliated names. Instrument item columns are named <instrument>.<item> because two
	   instruments can have items with the same name */
	function ExportColumns($req, $items) {
		if ($req['layout'] == 'long')
			return array(LongColumns(), array());

		$fixed = ($req['repeats'] == 'all') ? array('UID', 'AltUID', 'SurveyVisit', 'SurveyInstance', 'DateUTC') : array('UID', 'AltUID');
		$used = array();
		foreach ($fixed as $header)
			$used[mb_strtolower($header)] = true;

		uasort($items, function($a, $b) {
			$cmp = strcasecmp($a['instrument_name'], $b['instrument_name']);
			if ($cmp == 0)
				$cmp = (int)$a['item_order'] - (int)$b['item_order'];
			if ($cmp == 0)
				$cmp = strcasecmp($a['item_name'], $b['item_name']);
			return $cmp;
		});
		$names = $req['names'];
		usort($names, 'strcasecmp');

		$columns = array();
		foreach ($items as $itemid => $item)
			$columns[NameKey($itemid, '')] = array('header' => $item['instrument_name'] . "." . $item['item_name']);
		foreach ($names as $name)
			$columns[NameKey(0, $name)] = array('header' => $name);

		/* first/last can have a date column for each observation */
		$headers = $fixed;
		foreach ($columns as $key => $column) {
			$columns[$key]['header'] = UniqueHeader($column['header'], $used);
			$headers[] = $columns[$key]['header'];
			if (($req['repeats'] != 'all') && ($req['dates'])) {
				$columns[$key]['dateheader'] = UniqueHeader($column['header'] . ".DateUTC", $used);
				$headers[] = $columns[$key]['dateheader'];
			}
		}

		return array($headers, $columns);
	}


	/* -------------------------------------------- */
	/* ------- ExportRows ------------------------- */
	/* -------------------------------------------- */
	/* Generator for the export's rows, for the request's layout */
	function ExportRows($req, $subjects, $columns) {
		if ($req['layout'] == 'long')
			return LongRows($req, $subjects);
		return WideRows($req, $subjects, $columns);
	}


	/* -------------------------------------------- */
	/* ------- WideRows --------------------------- */
	/* -------------------------------------------- */
	/* Generator: the wide-layout rows, in subject UID order. Queries the subjects in batches.
	   - all: one row per subject per survey (or per date, for observations without a survey). If an
	     observation is repeated within the same survey/date, it goes in an extra row for that survey/date
	   - first/last: one row per subject, with each observation's first/last value by date */
	function WideRows($req, $subjects, $columns) {
		$colindex = array_flip(array_keys($columns));
		$numcols = count($columns);
		$all = ($req['repeats'] == 'all');

		$select = "select o.enrollment_id, coalesce(ii.instrumentitem_id, 0) 'itemid', o.observation_name, o.observation_value, o.observation_startdate, sv.survey_visit, sv.survey_instance, sv.survey_startdate, " . RowKeySQL() . " 'rowkey' ";
		if ($all)
			$order = " order by o.enrollment_id, coalesce(sv.survey_startdate, date(o.observation_startdate)), rowkey, o.observation_startdate, o.observation_id";
		else
			$order = " order by o.enrollment_id, o.observation_startdate, o.observation_id";

		foreach (array_chunk(array_keys($subjects), 100) as $batch) {
			list($sqlwhere, $types, $params) = ExportWhere($req, $batch);
			$rows = TimedQuery("Observation data (batches of 100 subjects)", $select . $sqlwhere . $order, $types, $params);

			/* all: $wide[enrollment][rowkey] = list of rows. first/last: $wide[enrollment] = one row */
			$wide = array();
			foreach ($rows as $row) {
				$col = $colindex[NameKey($row['itemid'], $row['observation_name'])] ?? null;
				if ($col === null)
					continue;
				$id = (int)$row['enrollment_id'];

				if ($all) {
					$rowkey = $row['rowkey'] ?? 'd';
					$placed = false;
					foreach ($wide[$id][$rowkey] ?? array() as $i => $widerow) {
						if (!array_key_exists($col, $widerow['values'])) {
							$wide[$id][$rowkey][$i]['values'][$col] = $row['observation_value'];
							$placed = true;
							break;
						}
					}
					if (!$placed) {
						$date = ($row['survey_startdate'] !== null) ? ValidDate($row['survey_startdate']) : substr(ValidDate($row['observation_startdate']), 0, 10);
						$wide[$id][$rowkey][] = array('visit' => $row['survey_visit'] ?? '', 'instance' => $row['survey_instance'] ?? '', 'date' => $date, 'values' => array($col => $row['observation_value']));
					}
				}
				else {
					/* the rows are in date order, so the first value is kept, or the last one overwrites */
					if (($req['repeats'] == 'first') && (isset($wide[$id]['values'])) && (array_key_exists($col, $wide[$id]['values'])))
						continue;
					$wide[$id]['values'][$col] = $row['observation_value'];
					$wide[$id]['dates'][$col] = ValidDate($row['observation_startdate']);
				}
			}
			unset($rows);

			foreach ($batch as $id) {
				if (!isset($wide[$id]))
					continue;
				$subject = array($subjects[$id]['uid'], $subjects[$id]['altuid']);

				if ($all) {
					foreach ($wide[$id] as $widerows) {
						foreach ($widerows as $widerow) {
							$out = array_merge($subject, array($widerow['visit'], $widerow['instance'], $widerow['date']));
							for ($col = 0; $col < $numcols; $col++)
								$out[] = $widerow['values'][$col] ?? '';
							yield $out;
						}
					}
				}
				else {
					$out = $subject;
					for ($col = 0; $col < $numcols; $col++) {
						$out[] = $wide[$id]['values'][$col] ?? '';
						if ($req['dates'])
							$out[] = $wide[$id]['dates'][$col] ?? '';
					}
					yield $out;
				}
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- LongColumns ------------------------ */
	/* -------------------------------------------- */
	function LongColumns() {
		return array('UID', 'AltUID', 'Instrument', 'Observation', 'Value', 'StartDateUTC', 'EndDateUTC', 'TimezoneOffset', 'DurationSeconds', 'Rater', 'Notes', 'SurveyVisit', 'SurveyInstance');
	}


	/* -------------------------------------------- */
	/* ------- LongRows --------------------------- */
	/* -------------------------------------------- */
	/* Generator: the long-layout rows (one per observation), in subject UID order. Queries the
	   subjects in batches, so a large export doesn't have to fit in memory */
	function LongRows($req, $subjects) {
		$select = "select o.enrollment_id, coalesce(i.instrument_name, '') 'instrumentname', coalesce(ii.item_name, o.observation_name) 'obsname', o.observation_value, o.observation_startdate, o.observation_enddate, o.observation_tz_offset, o.observation_duration, o.observation_rater, o.observation_notes, sv.survey_visit, sv.survey_instance ";

		foreach (array_chunk(array_keys($subjects), 100) as $batch) {
			list($sqlwhere, $types, $params) = ExportWhere($req, $batch);
			$sqlstring = $select . $sqlwhere . " order by o.enrollment_id, instrumentname, o.observation_startdate, obsname";
			$rows = TimedQuery("Observation data (batches of 100 subjects)", $sqlstring, $types, $params);

			/* the rows are ordered by enrollment. Output them in the batch's UID order */
			$byenrollment = array();
			foreach ($rows as $row)
				$byenrollment[(int)$row['enrollment_id']][] = $row;
			unset($rows);

			foreach ($batch as $enrollmentid) {
				foreach ($byenrollment[$enrollmentid] ?? array() as $row) {
					yield array(
						$subjects[$enrollmentid]['uid'],
						$subjects[$enrollmentid]['altuid'],
						$row['instrumentname'],
						$row['obsname'],
						$row['observation_value'],
						ValidDate($row['observation_startdate']),
						ValidDate($row['observation_enddate']),
						$row['observation_tz_offset'] ?? '',
						$row['observation_duration'] ?? '',
						$row['observation_rater'] ?? '',
						$row['observation_notes'] ?? '',
						$row['survey_visit'] ?? '',
						$row['survey_instance'] ?? ''
					);
				}
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- PathPart --------------------------- */
	/* -------------------------------------------- */
	/* A name that is safe as one part of a path in the zip (on Windows, macOS, and Linux) */
	function PathPart($name, $default = "_") {
		$pattern = '/[\x00-\x1F\x7F\/\\\\:*?"<>|]+/';
		$part = preg_replace($pattern . 'u', '_', (string)$name);
		if ($part === null) /* not valid UTF-8 */
			$part = preg_replace($pattern, '_', (string)$name);
		$part = trim($part, " .");
		if ($part == "")
			$part = $default;
		return mb_substr($part, 0, 100);
	}


	/* -------------------------------------------- */
	/* ------- ManifestColumns -------------------- */
	/* -------------------------------------------- */
	function ManifestColumns() {
		return array('Path', 'Status', 'UID', 'AltUID', 'Instrument', 'Observation', 'ItemType', 'StartDateUTC', 'EndDateUTC', 'TimezoneOffset', 'SurveyVisit', 'SurveyInstance', 'OriginalFilename', 'ContentType', 'SizeBytes', 'Value', 'Notes');
	}


	/* -------------------------------------------- */
	/* ------- FileEntries ------------------------ */
	/* -------------------------------------------- */
	/* Generator: the files of the selected observations, in subject UID order, each with its path in
	   the zip and its manifest row. Observations whose file is missing are included (status 'missing
	   file', no path). Paths are <UID>/<instrument>/<observation>/<YYYYMMDD_HHMMSS>_<filename>, made
	   unique (not case sensitive) by adding _2, _3, ... before the extension */
	function FileEntries($req, $subjects) {
		$select = "select o.observation_id, o.enrollment_id, coalesce(i.instrument_name, '') 'instrumentname', coalesce(ii.item_name, o.observation_name) 'obsname', ii.item_type, o.observation_value, o.observation_notes, o.observation_startdate, o.observation_enddate, o.observation_tz_offset, sv.survey_visit, sv.survey_instance, f.file_id, f.file_name, f.file_contenttype, f.file_size ";
		$used = array();

		foreach (array_chunk(array_keys($subjects), 100) as $batch) {
			list($sqlwhere, $types, $params) = ExportWhere($req, $batch);
			$sqlstring = $select . $sqlwhere . " order by o.enrollment_id, instrumentname, obsname, o.observation_startdate, o.observation_id";
			$rows = TimedQuery("File list (batches of 100 subjects)", $sqlstring, $types, $params);

			$byenrollment = array();
			foreach ($rows as $row)
				$byenrollment[(int)$row['enrollment_id']][] = $row;
			unset($rows);

			foreach ($batch as $id) {
				foreach ($byenrollment[$id] ?? array() as $row) {
					$startdate = ValidDate($row['observation_startdate']);
					$found = ($row['file_id'] !== null);

					$path = "";
					if ($found) {
						$prefix = ($startdate != "") ? str_replace(array('-', ':', ' '), array('', '', '_'), $startdate) : "nodate";
						$dir = PathPart($subjects[$id]['uid']) . "/" . PathPart($row['instrumentname']) . "/" . PathPart($row['obsname']);
						$filename = $prefix . "_" . PathPart($row['file_name'], "file");
						$path = "$dir/$filename";
						$num = 2;
						while (isset($used[mb_strtolower($path)])) {
							$ext = pathinfo($filename, PATHINFO_EXTENSION);
							$path = "$dir/" . (($ext != "") ? substr($filename, 0, -strlen($ext) - 1) . "_$num.$ext" : $filename . "_$num");
							$num++;
						}
						$used[mb_strtolower($path)] = true;
					}

					$timestamp = ($startdate != "") ? strtotime($startdate . " UTC") : time();
					yield array(
						'path' => $path,
						'fileid' => (int)$row['file_id'],
						'timestamp' => (($timestamp === false) ? time() : $timestamp),
						/* text files are compressed. Images are usually compressed already, so they're stored */
						'deflate' => (in_array($row['item_type'], array('csv', 'json')) || (substr((string)$row['file_contenttype'], 0, 5) == 'text/')),
						'manifest' => array(
							$path,
							($found ? 'ok' : 'missing file'),
							$subjects[$id]['uid'],
							$subjects[$id]['altuid'],
							$row['instrumentname'],
							$row['obsname'],
							$row['item_type'] ?? '',
							$startdate,
							ValidDate($row['observation_enddate']),
							$row['observation_tz_offset'] ?? '',
							$row['survey_visit'] ?? '',
							$row['survey_instance'] ?? '',
							$row['file_name'] ?? '',
							$row['file_contenttype'] ?? '',
							($found ? (int)$row['file_size'] : ''),
							$row['observation_value'] ?? '',
							$row['observation_notes'] ?? ''
						)
					);
				}
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- WriteZipExport --------------------- */
	/* -------------------------------------------- */
	/* Writes the Files export as a zip: the files, and manifest.csv. $write is called with each piece
	   of the zip. Each file is read from the database in 64 MB chunks. Returns the number of files */
	function WriteZipExport($req, $subjects, $write) {
		$zip = new ZipStreamWriter($write);
		$manifest = fopen('php://temp', 'w+');
		fputcsv($manifest, ManifestColumns(), ',', '"', "\\");

		$numfiles = 0;
		$chunksize = 64 * 1048576;
		foreach (FileEntries($req, $subjects) as $entry) {
			if ($entry['path'] != "") {
				$zip->BeginFile($entry['path'], $entry['timestamp'], $entry['deflate']);
				$pos = 1;
				do {
					$rows = TimedQuery("File data (64 MB chunks)", "select substring(file_blob, ?, ?) 'chunk', length(file_blob) 'length' from files where file_id = ?", 'iii', [$pos, $chunksize, $entry['fileid']]);
					if (count($rows) == 0)
						break;
					$length = (int)$rows[0]['length'];
					$chunk = (string)$rows[0]['chunk'];
					unset($rows);
					if ($chunk == "")
						break;
					$zip->WriteData($chunk);
					$pos += strlen($chunk);
					unset($chunk);
				} while ($pos <= $length);
				$zip->EndFile();
				$numfiles++;
			}
			fputcsv($manifest, $entry['manifest'], ',', '"', "\\");
		}

		$zip->BeginFile("manifest.csv", time(), true);
		rewind($manifest);
		while (!feof($manifest))
			$zip->WriteData((string)fread($manifest, 1048576));
		$zip->EndFile();
		fclose($manifest);

		$zip->Finish();
		return $numfiles;
	}


	/* -------------------------------------------- */
	/* ------- PreviewFiles ----------------------- */
	/* -------------------------------------------- */
	/* Preview of the Files export: totals, and the first files with their paths in the zip */
	function PreviewFiles($req, $items, $subjects, $errors, $warnings) {
		$keycounts = array();
		$total = 0;
		$nummissing = 0;
		$bytes = 0;
		$numsubjects = 0;
		foreach (array_chunk(array_keys($subjects), 1000) as $batch) {
			list($sqlwhere, $types, $params) = ExportWhere($req, $batch);
			$sqlstring = "select ii.instrumentitem_id 'itemid', count(*) 'num', sum(f.file_id is null) 'missing', coalesce(sum(f.file_size), 0) 'bytes' " . $sqlwhere . " group by itemid";
			foreach (TimedQuery("Counts (batches of 1000 subjects)", $sqlstring, $types, $params) as $row) {
				$keycounts[(int)$row['itemid']] = ($keycounts[(int)$row['itemid']] ?? 0) + (int)$row['num'];
				$total += (int)$row['num'];
				$nummissing += (int)$row['missing'];
				$bytes += (float)$row['bytes'];
			}

			$sqlstring = "select count(distinct o.enrollment_id) 'num' " . $sqlwhere;
			$rows = TimedQuery("Subjects with data (batches of 1000 subjects)", $sqlstring, $types, $params);
			$numsubjects += (int)($rows[0]['num'] ?? 0);
		}

		$empty = array();
		foreach ($req['itemids'] as $id) {
			if (!isset($keycounts[$id]))
				$empty[] = $items[$id]['item_name'] ?? "item $id";
		}
		if (count($empty) > 0)
			$warnings[] = count($empty) . " of the selected observations have no data for these subjects and dates: " . htmlspecialchars(implode(", ", array_slice($empty, 0, 20))) . ((count($empty) > 20) ? ", ..." : "");
		if ($nummissing > 0)
			$warnings[] = number_format($nummissing) . " observations have no file, or their file is missing. They're listed in manifest.csv with the status 'missing file'";
		if ($total == 0)
			$errors[] = "No observations match. Check the selected observations, subjects, and dates";

		/* the first files: path, status, and the main manifest columns */
		$previewrows = array();
		if ($total > 0) {
			foreach (FileEntries($req, $subjects) as $entry) {
				$m = $entry['manifest'];
				$previewrows[] = array($m[0], $m[1], $m[2], $m[4], $m[5], $m[7], $m[12], $m[13], $m[14]);
				if (count($previewrows) >= 500)
					break;
			}
		}

		$stats = array(
			array('label' => 'Files', 'value' => number_format($total - $nummissing)),
			array('label' => 'Total size (uncompressed)', 'value' => HumanSize($bytes)),
			array('label' => 'Missing files', 'value' => number_format($nummissing)),
			array('label' => 'Subjects with data', 'value' => number_format($numsubjects))
		);
		SendJSON(array('errors' => $errors, 'warnings' => $warnings, 'columns' => array('Path', 'Status', 'UID', 'Instrument', 'Observation', 'StartDateUTC', 'OriginalFilename', 'ContentType', 'SizeBytes'), 'rows' => $previewrows, 'numrows' => $total, 'stats' => $stats));
	}


	/* -------------------------------------------- */
	/* ------- HumanSize -------------------------- */
	/* -------------------------------------------- */
	function HumanSize($bytes) {
		$units = array('bytes', 'KB', 'MB', 'GB', 'TB');
		$i = 0;
		while (($bytes >= 1024) && ($i < count($units) - 1)) {
			$bytes /= 1024;
			$i++;
		}
		return (($i == 0) ? number_format($bytes) : number_format($bytes, 1)) . " " . $units[$i];
	}


	/* -------------------------------------------- */
	/* ------- ZipStreamWriter -------------------- */
	/* -------------------------------------------- */
	/* Writes a .zip file as a stream, so a large export doesn't have to fit in memory or on disk.
	   Each file is written with a data descriptor (its CRC and sizes come after the data), so the
	   data can be written in chunks. Files are stored, or deflated if $deflate is set. ZIP64 records
	   are added if the zip is over 4 GB or has more than 65,535 files. Each file must be under 4 GB.
	   $write is called with each piece of the zip */
	class ZipStreamWriter {
		private $write;
		private $offset = 0;
		private $entries = array();
		private $current = null;

		function __construct($write) {
			$this->write = $write;
		}

		private function Write($data) {
			call_user_func($this->write, $data);
			$this->offset += strlen($data);
		}

		/* DOS date and time of a unix timestamp (UTC) */
		private static function DosDateTime($timestamp) {
			$t = getdate($timestamp - (int)date('Z', $timestamp));
			if ($t['year'] < 1980)
				return array(0, (0 << 9) | (1 << 5) | 1);
			return array(($t['hours'] << 11) | ($t['minutes'] << 5) | ($t['seconds'] >> 1), (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday']);
		}

		function BeginFile($path, $timestamp, $deflate) {
			list($dostime, $dosdate) = self::DosDateTime($timestamp);
			$method = ($deflate ? 8 : 0);
			$this->current = array('path' => $path, 'offset' => $this->offset, 'method' => $method, 'time' => $dostime, 'date' => $dosdate, 'crc' => hash_init('crc32b'), 'size' => 0, 'csize' => 0, 'deflate' => ($deflate ? deflate_init(ZLIB_ENCODING_RAW, array('level' => 6)) : null));
			/* flags: bit 3 (data descriptor), bit 11 (UTF-8 file name) */
			$this->Write(pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0808, $method, $dostime, $dosdate, 0, 0, 0, strlen($path), 0) . $path);
		}

		function WriteData($data) {
			hash_update($this->current['crc'], $data);
			$this->current['size'] += strlen($data);
			if ($this->current['deflate'])
				$data = deflate_add($this->current['deflate'], $data, ZLIB_NO_FLUSH);
			$this->current['csize'] += strlen($data);
			if ($data != '')
				$this->Write($data);
		}

		function EndFile() {
			if ($this->current['deflate']) {
				$data = deflate_add($this->current['deflate'], '', ZLIB_FINISH);
				$this->current['csize'] += strlen($data);
				$this->Write($data);
			}
			$this->current['crc'] = hexdec(hash_final($this->current['crc']));
			$this->Write(pack('VVVV', 0x08074b50, $this->current['crc'], $this->current['csize'], $this->current['size']));
			unset($this->current['deflate']);
			$this->entries[] = $this->current;
			$this->current = null;
		}

		/* writes the central directory. Call once, after the last file */
		function Finish() {
			$cdstart = $this->offset;
			foreach ($this->entries as $e) {
				/* a file that starts after 4 GB has its offset in a ZIP64 extra field */
				$zip64 = ($e['offset'] > 0xFFFFFFFF);
				$extra = ($zip64 ? pack('vvP', 0x0001, 8, $e['offset']) : '');
				$version = ($zip64 ? 45 : 20);
				/* made by unix (3), so the external attributes are unix permissions (regular file, 0644) */
				$this->Write(pack('VvvvvvvVVVvvvvvVV', 0x02014b50, (3 << 8) | $version, $version, 0x0808, $e['method'], $e['time'], $e['date'], $e['crc'], $e['csize'], $e['size'], strlen($e['path']), strlen($extra), 0, 0, 0, 0x81A40000, ($zip64 ? 0xFFFFFFFF : $e['offset'])) . $e['path'] . $extra);
			}
			$cdsize = $this->offset - $cdstart;
			$num = count($this->entries);

			if (($num > 0xFFFF) || ($cdstart > 0xFFFFFFFF) || ($cdsize > 0xFFFFFFFF)) {
				/* ZIP64 end of central directory record and locator */
				$zip64eocd = $this->offset;
				$this->Write(pack('VPvvVVPPPP', 0x06064b50, 44, (3 << 8) | 45, 45, 0, 0, $num, $num, $cdsize, $cdstart));
				$this->Write(pack('VVPV', 0x07064b50, 0, $zip64eocd, 1));
			}
			$this->Write(pack('VvvvvVVv', 0x06054b50, 0, 0, min($num, 0xFFFF), min($num, 0xFFFF), min($cdsize, 0xFFFFFFFF), min($cdstart, 0xFFFFFFFF), 0));
		}
	}


	/* -------------------------------------------- */
	/* ------- PrepareExport ---------------------- */
	/* -------------------------------------------- */
	/* Checks the project and permissions, and parses and validates the POSTed export request.
	   Returns array(project, request, instrument items, subjects, errors, warnings) */
	function PrepareExport($projectid) {
		list($project, $error) = GetProject($projectid);
		if ($error != "")
			return array(null, null, array(), array(), array($error), array());

		/* release the session lock so the user's other pages aren't blocked while this runs */
		session_write_close();
		set_time_limit(0);

		list($req, $errors) = ParseExportRequest();
		if (count($errors) > 0)
			return array($project, $req, array(), array(), $errors, array());

		list($items, $errors) = ValidateExportItems($req);
		list($subjects, $warnings) = GetExportSubjects($projectid, $req);
		if ((count($errors) == 0) && (count($subjects) == 0))
			$errors[] = "There are no subjects to export";

		return array($project, $req, $items, $subjects, $errors, $warnings);
	}


	/* -------------------------------------------- */
	/* ------- PreviewExportJSON ------------------ */
	/* -------------------------------------------- */
	/* AJAX handler: validates the export request, and returns the totals and the first rows of the export */
	function PreviewExportJSON($projectid) {
		list($project, $req, $items, $subjects, $errors, $warnings) = PrepareExport($projectid);
		if (count($errors) > 0) {
			SendJSON(array('errors' => $errors, 'warnings' => $warnings));
			return;
		}
		if ($req['format'] == 'files') {
			PreviewFiles($req, $items, $subjects, $errors, $warnings);
			return;
		}
		list($headers, $columns) = ExportColumns($req, $items);

		/* totals, by selected observation. The batches partition the subjects, so the per-batch
		   distinct subject counts add up */
		$keycounts = array();
		$total = 0;
		$numsubjects = 0;
		$numwiderows = 0;
		foreach (array_chunk(array_keys($subjects), 1000) as $batch) {
			list($sqlwhere, $types, $params) = ExportWhere($req, $batch);
			$sqlstring = "select coalesce(ii.instrumentitem_id, 0) 'itemid', if(ii.instrumentitem_id is null, o.observation_name, '') 'obsname', count(*) 'num' " . $sqlwhere . " group by itemid, obsname";
			foreach (TimedQuery("Counts (batches of 1000 subjects)", $sqlstring, $types, $params) as $row) {
				$key = NameKey($row['itemid'], $row['obsname']);
				$keycounts[$key] = ($keycounts[$key] ?? 0) + (int)$row['num'];
				$total += (int)$row['num'];
			}

			$sqlstring = "select count(distinct o.enrollment_id) 'num' " . $sqlwhere;
			$rows = TimedQuery("Subjects with data (batches of 1000 subjects)", $sqlstring, $types, $params);
			$numsubjects += (int)($rows[0]['num'] ?? 0);

			/* wide layout, all repeats: the number of rows. Each subject's survey/date needs as many
			   rows as its most repeated observation */
			if (($req['layout'] == 'wide') && ($req['repeats'] == 'all')) {
				$sqlstring = "select coalesce(sum(maxrepeats), 0) 'num' from (select max(repeats) 'maxrepeats' from (select o.enrollment_id, " . RowKeySQL() . " 'rowkey', if(ii.instrumentitem_id is null, concat('u:', o.observation_name), concat('i', ii.instrumentitem_id)) 'colkey', count(*) 'repeats' " . $sqlwhere . " group by o.enrollment_id, rowkey, colkey) a group by enrollment_id, rowkey) b";
				$rows = TimedQuery("Wide rows (batches of 1000 subjects)", $sqlstring, $types, $params);
				$numwiderows += (int)($rows[0]['num'] ?? 0);
			}
		}

		/* selected observations with no data for these subjects and dates */
		$empty = array();
		foreach ($req['itemids'] as $id) {
			if (!isset($keycounts['i' . $id]))
				$empty[] = $items[$id]['item_name'] ?? "item $id";
		}
		foreach ($req['names'] as $name) {
			if (!isset($keycounts[NameKey(0, $name)]))
				$empty[] = $name;
		}
		if (count($empty) > 0)
			$warnings[] = count($empty) . " of the selected observations have no data for these subjects and dates: " . htmlspecialchars(implode(", ", array_slice($empty, 0, 20))) . ((count($empty) > 20) ? ", ..." : "");
		if ($total == 0)
			$errors[] = "No observations match. Check the selected observations, subjects, and dates";

		if (count($headers) > 16384)
			$warnings[] = "The export has " . number_format(count($headers)) . " columns. Excel can only open 16,384 columns";

		/* the number of rows in the export */
		if ($req['layout'] == 'long')
			$numrows = $total;
		elseif ($req['repeats'] == 'all')
			$numrows = $numwiderows;
		else
			$numrows = $numsubjects;

		/* the first rows of the export */
		$previewrows = array();
		if ($total > 0) {
			foreach (ExportRows($req, $subjects, $columns) as $row) {
				$previewrows[] = $row;
				if (count($previewrows) >= 500)
					break;
			}
		}

		$stats = array(
			array('label' => 'Rows', 'value' => number_format($numrows)),
			array('label' => 'Columns', 'value' => number_format(count($headers))),
			array('label' => 'Observations', 'value' => number_format($total)),
			array('label' => 'Subjects with data', 'value' => number_format($numsubjects)),
			array('label' => 'Observation names with data', 'value' => number_format(count($keycounts)))
		);
		SendJSON(array('errors' => $errors, 'warnings' => $warnings, 'columns' => $headers, 'rows' => $previewrows, 'numrows' => $numrows, 'stats' => $stats));
	}


	/* -------------------------------------------- */
	/* ------- DownloadExport --------------------- */
	/* -------------------------------------------- */
	/* Streams the export as a .csv file, or a .zip for the Files format. A dry run (siteadmins only)
	   builds the whole export without sending it, and returns the size and the performance metrics as JSON */
	function DownloadExport($projectid, $dryrun) {
		if (($dryrun) && (!($GLOBALS['issiteadmin'] ?? false))) {
			SendJSON(array('errors' => array("Dry runs are for siteadmins only")));
			return;
		}

		list($project, $req, $items, $subjects, $errors, $warnings) = PrepareExport($projectid);
		if (count($errors) > 0) {
			if ($dryrun)
				SendJSON(array('errors' => $errors));
			else {
				?>
				<html><body style="font-family: sans-serif">
					<h3>The export could not be created</h3>
					<ul><? foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
					<a href="nonimaging.php?projectid=<?=(int)$projectid?>">Back to search/export</a>
				</body></html>
				<?
			}
			return;
		}

		$projectname = preg_replace('/[^A-Za-z0-9_-]+/', '_', $project['project_name']);
		if (!$dryrun) {
			/* nothing should have been output yet. Discard any buffered output so the file streams */
			while (ob_get_level() > 0)
				ob_end_clean();
		}

		if ($req['format'] == 'files') {
			if (!$dryrun) {
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename="' . $projectname . "_observations_files_" . gmdate('Ymd_His') . '.zip"');
			}
			/* send the zip as it's written. A dry run only counts the bytes */
			$bytes = 0;
			$unflushed = 0;
			$write = function($data) use ($dryrun, &$bytes, &$unflushed) {
				$bytes += strlen($data);
				if ($dryrun)
					return;
				echo $data;
				$unflushed += strlen($data);
				if ($unflushed > 1048576) {
					flush();
					$unflushed = 0;
				}
			};
			$numfiles = WriteZipExport($req, $subjects, $write);
			if ($dryrun)
				SendJSON(array('rows' => $numfiles, 'bytes' => $bytes));
			return;
		}

		if ($dryrun)
			$out = fopen('php://memory', 'w');
		else {
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $projectname . "_observations_" . $req['layout'] . "_" . gmdate('Ymd_His') . '.csv"');
			$out = fopen('php://output', 'w');
		}

		list($headers, $columns) = ExportColumns($req, $items);
		$bytes = fputcsv($out, $headers, ',', '"', "\\");
		$numrows = 0;
		foreach (ExportRows($req, $subjects, $columns) as $row) {
			$bytes += fputcsv($out, $row, ',', '"', "\\");
			$numrows++;
			if (($numrows % 5000) == 0) {
				if ($dryrun) {
					/* a dry run only counts the bytes, so don't keep the output */
					rewind($out);
					ftruncate($out, 0);
				}
				else
					flush();
			}
		}
		fclose($out);

		if ($dryrun)
			SendJSON(array('rows' => $numrows, 'bytes' => $bytes));
	}


	/* -------------------------------------------- */
	/* ------- DisplayExportPage ------------------ */
	/* -------------------------------------------- */
	/* The search/export page. Steps: data type, format, observations, subjects and dates, preview
	   and download. The selections are kept in the browser */
	function DisplayExportPage($projectid, $project) {
		$projectid = (int)$projectid;
		$issiteadmin = (bool)($GLOBALS['issiteadmin'] ?? false);

		?>
		<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>
		<style>
			.nistep { display: none; }
			.nistep.active { display: block; }
			.ui.card.niselected { box-shadow: 0 0 0 2px #2185d0 !important; }
			.niinstrument { border-bottom: 1px solid #eee; }
			.niinstrumenthead { padding: 6px 4px; cursor: pointer; }
			.niinstrumenthead:hover { background-color: #f8f8f8; }
			.niitems { padding: 2px 0 8px 36px; }
			.niitem { padding: 2px 0; }
			.niitem.nicount0 { color: #999; }
			.nichip { margin-bottom: 4px !important; }
			.niperf td { font-family: 'JetBrains Mono', monospace; font-size: 0.9em; }
		</style>

		<div class="ui container">
			<h1 class="ui header">
				<i class="table icon"></i>
				<div class="content">
					Search/export non-imaging data
					<div class="sub header"><a href="projects.php?id=<?=$projectid?>"><?=htmlspecialchars($project['project_name'] ?? '')?></a> (<?=htmlspecialchars($project['project_costcenter'] ?? '')?>)</div>
				</div>
			</h1>

			<div class="ui five small ordered steps">
				<a class="active step" data-step="1"><div class="content"><div class="title">Data type</div><div class="description" id="stepdesc1">Observations</div></div></a>
				<a class="step" data-step="2"><div class="content"><div class="title">Format</div><div class="description" id="stepdesc2">&nbsp;</div></div></a>
				<a class="disabled step" data-step="3"><div class="content"><div class="title">Observations</div><div class="description" id="stepdesc3">&nbsp;</div></div></a>
				<a class="disabled step" data-step="4"><div class="content"><div class="title">Subjects &amp; dates</div><div class="description" id="stepdesc4">All subjects</div></div></a>
				<a class="disabled step" data-step="5"><div class="content"><div class="title">Preview &amp; download</div><div class="description">&nbsp;</div></div></a>
			</div>

			<!-- step 1: data type -->
			<div class="nistep active" id="step1">
				<div class="ui two stackable cards">
					<a class="ui card niselected" onClick="GoToStep(2)">
						<div class="content">
							<div class="header"><i class="clipboard list icon"></i> Observations</div>
							<div class="description">Measures, assessments, survey responses, and observation files</div>
						</div>
					</a>
					<div class="ui disabled card" style="opacity: 0.5">
						<div class="content">
							<div class="header"><i class="pills icon"></i> Interventions</div>
							<div class="description">Coming soon</div>
						</div>
					</div>
				</div>
				<br>
				<button class="ui primary right labeled icon button" onClick="GoToStep(2)">Next <i class="right arrow icon"></i></button>
			</div>

			<!-- step 2: format -->
			<div class="nistep" id="step2">
				<p>Choose the export format. Each observation can only be exported in one format, which comes from its instrument item type. Only one format can be exported at a time.</p>
				<div class="ui three stackable cards" id="formatcards"></div>

				<h4 class="ui header">Can't find an observation? Search all observations in this project</h4>
				<div class="ui fluid left icon input">
					<i class="search icon"></i>
					<input type="text" id="findall" placeholder="Observation or instrument name">
				</div>
				<div id="findresults"></div>
			</div>

			<!-- step 3: observations -->
			<div class="nistep" id="step3">
				<div class="ui grid">
					<div class="eleven wide column">
						<div class="ui form">
							<div class="inline fields" style="margin-bottom: 0">
								<div class="field" style="flex-grow: 1">
									<div class="ui fluid left icon input">
										<i class="filter icon"></i>
										<input type="text" id="obsfilter" placeholder="Filter observation and instrument names">
									</div>
								</div>
								<div class="field">
									<button class="ui basic button" onClick="$('#pastemodal').modal('show')"><i class="paste icon"></i> Paste a list</button>
								</div>
							</div>
						</div>
						<div id="crossformathint"></div>

						<h4 class="ui dividing header">Instrument observations</h4>
						<div id="instrumentlist"></div>

						<div id="unaffiliatedsection">
							<h4 class="ui dividing header">Unaffiliated observations <span style="font-weight: normal; font-size: smaller; color: gray">(not part of an instrument, or the instrument item was deleted; exported as CSV)</span></h4>
							<div id="unaffiliatedgrid" style="height: 400px"></div>
						</div>
					</div>
					<div class="five wide column">
						<div class="ui segment">
							<h4 class="ui header">Selected <span id="selectedcount"></span> <a href="#" style="float: right; font-size: smaller; font-weight: normal" onClick="ClearSelection(); return false;">Clear</a></h4>
							<div id="selectedtray"></div>
						</div>
						<button class="ui primary fluid right labeled icon button" onClick="GoToStep(4)">Next <i class="right arrow icon"></i></button>
					</div>
				</div>
			</div>

			<!-- step 4: subjects and dates -->
			<div class="nistep" id="step4">
				<div class="ui form">
					<div class="inline fields">
						<label>Subjects</label>
						<div class="field"><div class="ui radio checkbox"><input type="radio" name="subjectmode" value="all" checked><label>All subjects</label></div></div>
						<div class="field"><div class="ui radio checkbox"><input type="radio" name="subjectmode" value="choose"><label>Choose subjects</label></div></div>
					</div>
				</div>
				<div id="subjectchooser" style="display: none; margin-bottom: 1em">
					<div class="ui form">
						<div class="inline fields" style="margin-bottom: 0.5em">
							<div class="field" style="flex-grow: 1">
								<div class="ui fluid left icon input">
									<i class="filter icon"></i>
									<input type="text" id="subjectfilter" placeholder="Filter subjects">
								</div>
							</div>
							<div class="field">
								<button class="ui basic button" onClick="$('#pasteuidmodal').modal('show')"><i class="paste icon"></i> Paste UIDs</button>
							</div>
						</div>
					</div>
					<div id="subjectgrid" style="height: 350px"></div>
					<div id="subjectselectedcount" style="margin-top: 0.5em; color: gray"></div>
				</div>

				<div class="ui form">
					<div class="inline fields">
						<label>Observation date range</label>
						<div class="field"><input type="date" id="startdate"></div>
						<div class="field">to</div>
						<div class="field"><input type="date" id="enddate"></div>
						<div class="field" style="color: gray">Optional. Dates are UTC</div>
					</div>
				</div>
				<button class="ui primary right labeled icon button" onClick="GoToStep(5)">Next <i class="right arrow icon"></i></button>
			</div>

			<!-- step 5: preview and download -->
			<div class="nistep" id="step5">
				<div class="ui grid">
					<div class="eight wide column" id="exportsummary"></div>
					<div class="eight wide column">
						<div class="ui form" id="layoutoptions">
							<div class="grouped fields">
								<label>CSV layout</label>
								<div class="field"><div class="ui radio checkbox"><input type="radio" name="layout" value="long" checked><label>Long: one row per observation</label></div></div>
								<div class="field"><div class="ui radio checkbox"><input type="radio" name="layout" value="wide"><label>Wide: one column per observation</label></div></div>
							</div>
							<div class="grouped fields" id="wideoptions" style="display: none; margin-left: 2em">
								<label>Repeated observations</label>
								<div class="field"><div class="ui radio checkbox"><input type="radio" name="repeats" value="all" checked><label>All: one row per subject per survey (or per date, for observations without a survey)</label></div></div>
								<div class="field"><div class="ui radio checkbox"><input type="radio" name="repeats" value="first"><label>First: one row per subject, with each observation's earliest value</label></div></div>
								<div class="field"><div class="ui radio checkbox"><input type="radio" name="repeats" value="last"><label>Last: one row per subject, with each observation's latest value</label></div></div>
								<div class="field" id="dateoption" style="display: none"><div class="ui checkbox"><input type="checkbox" id="includedates"><label>Include a date column for each observation</label></div></div>
							</div>
						</div>
					</div>
				</div>
				<button class="ui primary button" id="previewbutton" onClick="Preview()"><i class="eye icon"></i> Preview</button>
				<button class="ui green disabled button" id="downloadbutton" onClick="Download()" title="Preview the export first"><i class="download icon"></i> Download .csv</button>
				<? if ($issiteadmin) { ?>
				<button class="ui basic button" id="dryrunbutton" onClick="DryRun()" title="Build the whole export on the server without downloading it, and show the timings in the Performance panel (siteadmin only)"><i class="stopwatch icon"></i> Time the download (dry run)</button>
				<? } ?>
				<div id="previewmessages" style="margin-top: 1em"></div>
				<div id="previewsummary" style="margin-top: 1em"></div>
				<div id="previewgrid" style="height: 450px; display: none"></div>
			</div>

			<!-- paste a list of observation names -->
			<div class="ui modal" id="pastemodal">
				<div class="header">Paste a list of observation names</div>
				<div class="content">
					<div class="ui form">
						<div class="field">
							<label>One name per line, or comma/tab separated. Matching is not case sensitive</label>
							<textarea id="pastenames" rows="10"></textarea>
						</div>
					</div>
					<div id="pasteresult"></div>
				</div>
				<div class="actions">
					<div class="ui cancel button">Close</div>
					<div class="ui primary button" onClick="ApplyPastedNames()">Select</div>
				</div>
			</div>

			<!-- paste a list of UIDs -->
			<div class="ui modal" id="pasteuidmodal">
				<div class="header">Paste a list of subject UIDs</div>
				<div class="content">
					<div class="ui form">
						<div class="field">
							<label>UIDs or alternate UIDs. One per line, or comma/tab separated</label>
							<textarea id="pasteuids" rows="10"></textarea>
						</div>
					</div>
					<div id="pasteuidresult"></div>
				</div>
				<div class="actions">
					<div class="ui cancel button">Close</div>
					<div class="ui primary button" onClick="ApplyPastedUIDs()">Select</div>
				</div>
			</div>

			<? if ($issiteadmin) { ?>
			<!-- performance metrics, siteadmin only -->
			<div class="ui styled fluid accordion" style="margin-top: 2em">
				<div class="title"><i class="dropdown icon"></i> Performance <span style="font-weight: normal; color: gray">(siteadmin only)</span></div>
				<div class="content">
					<table class="ui very compact small celled table niperf">
						<thead><tr><th>Request</th><th>Query/step</th><th>Calls</th><th>Rows</th><th>Query ms</th><th>Fetch ms</th><th>Server ms</th><th>Round trip ms</th><th>Render ms</th><th>Peak memory</th></tr></thead>
						<tbody id="perfbody"></tbody>
					</table>
				</div>
			</div>
			<? } ?>
		</div>

		<script>
			const projectid = <?=$projectid?>;
			const isSiteAdmin = <?=($issiteadmin ? 'true' : 'false')?>;

			/* export formats, and the instrument item types that use them. Unaffiliated observations are always CSV */
			const FORMATS = {
				csv: { label: 'CSV', icon: 'file excel outline', types: ['enum', 'int', 'double', 'string', 'datetime', ''], desc: 'Values in a .csv file (long or wide layout)' },
				timeseries: { label: 'Timeseries', icon: 'chart line', types: ['timeseries'], desc: 'Timeseries data points (format to be determined)' },
				files: { label: 'Files', icon: 'file archive outline', types: ['image', 'csv', 'json'], desc: 'A .zip of image, csv, and json files' }
			};

			/* all observations in the project. Each entry: { key, name, instrument, instrumentid, type, format, num, numsubjects, firstdate, lastdate }.
			   key is 'i<itemid>' for instrument items and 'u:<name>' for unaffiliated names */
			let observations = [];
			let observationsByKey = {};
			let observationsLoaded = false;

			let state = {
				format: null,
				selected: { csv: new Set(), timeseries: new Set(), files: new Set() }, /* keys, kept per format */
				expanded: new Set(), /* expanded instrument IDs */
				subjectMode: 'all',
				subjects: new Set() /* enrollment IDs */
			};

			let unaffiliatedGrid = null;
			let subjectGrid = null;
			let subjects = [];
			let previewGrid = null;
			let previewedRequest = null; /* the request (JSON) of the last preview without errors. Download is only enabled for that request */
			let syncingGrid = false; /* true while the selection is being pushed into a grid, so the grid's selection event is ignored */

			/* ----- helpers ----- */
			function Esc(s) {
				return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
			}
			function Num(n) { return Number(n).toLocaleString(); }
			function FormatOfType(type) {
				for (const f in FORMATS)
					if (FORMATS[f].types.includes(type || '')) return f;
				return 'csv';
			}
			function SplitList(text) {
				return text.split(/[\n,\t]+/).map(s => s.trim()).filter(s => s != '');
			}
			function Matches(o, filter) {
				return (filter == '') || o.name.toLowerCase().includes(filter) || o.instrument.toLowerCase().includes(filter);
			}

			/* ----- performance ----- */
			/* adds a request's server and client timings to the performance table */
			function RecordPerf(request, roundtripms, renderms, perf) {
				if (!isSiteAdmin || !perf) return;
				const mem = perf.peakmemory ? (perf.peakmemory / 1048576).toFixed(1) + ' MB' : '';
				let html = '<tr class="active"><td><b>' + Esc(request) + '</b></td><td></td><td></td><td></td><td></td><td></td><td>' + (perf.totalms || '') + '</td><td>' + (roundtripms ? roundtripms.toFixed(1) : '') + '</td><td>' + renderms.toFixed(1) + '</td><td>' + mem + '</td></tr>';
				perf.queries.forEach(function(q) {
					html += '<tr><td></td><td>' + Esc(q.label) + '</td><td>' + Esc(q.calls) + '</td><td>' + Num(q.rows) + '</td><td>' + q.queryms + '</td><td>' + q.fetchms + '</td><td></td><td></td><td></td><td></td></tr>';
				});
				$('#perfbody').append(html);
			}

			/* GET a JSON action from this page, then call done(data). Records the timings; the render time is the time spent in done() */
			function GetJSON(action, label, done) {
				const start = performance.now();
				$.getJSON('nonimaging.php', { action: action, projectid: projectid })
					.done(function(data) {
						const received = performance.now();
						if (data.error) {
							alert(data.error);
							return;
						}
						done(data);
						RecordPerf(label, received - start, performance.now() - received, data.perf);
					})
					.fail(function(xhr) { alert('Error loading ' + label + ': ' + xhr.status + ' ' + xhr.statusText); });
			}

			/* ----- steps ----- */
			function GoToStep(n) {
				if ((n >= 3) && (state.format == null)) n = 2;
				$('.nistep').removeClass('active');
				$('#step' + n).addClass('active');
				$('.ui.steps .step').each(function() {
					const s = parseInt($(this).attr('data-step'));
					$(this).toggleClass('active', s == n);
					$(this).toggleClass('disabled', (s >= 3) && (state.format == null));
				});
				if (n == 3) RenderObservations();
				if (n == 5) {
					RenderSummary();
					/* the CSV layout options are only for CSV */
					$('#layoutoptions').toggle(state.format == 'csv');
					$('#downloadbutton').html('<i class="download icon"></i> Download ' + (state.format == 'files' ? '.zip' : '.csv'));
					UpdateDownloadButton();
				}
			}

			/* ----- step 2: format ----- */
			function RenderFormatCards() {
				let html = '';
				for (const f in FORMATS) {
					const items = observations.filter(o => o.format == f && o.num > 0);
					const num = items.reduce((t, o) => t + o.num, 0);
					const counts = observationsLoaded ? (Num(items.length) + ' observation names, ' + Num(num) + ' observations') : '<i class="notched circle loading icon"></i> Loading...';
					html += '<a class="ui card' + (state.format == f ? ' niselected' : '') + '" onClick="SelectFormat(\'' + f + '\', true)">' +
						'<div class="content"><div class="header"><i class="' + FORMATS[f].icon + ' icon"></i> ' + FORMATS[f].label + '</div>' +
						'<div class="meta">' + counts + '</div>' +
						'<div class="description">' + FORMATS[f].desc + '</div></div></a>';
				}
				$('#formatcards').html(html);
			}

			function SelectFormat(f, advance) {
				state.format = f;
				$('#stepdesc2').text(FORMATS[f].label);
				RenderFormatCards();
				UpdateSelectedCount();
				if (advance) GoToStep(3);
			}

			/* search all observations, in every format */
			function RenderFindResults() {
				const filter = $('#findall').val().trim().toLowerCase();
				if (filter == '') {
					$('#findresults').html('');
					return;
				}
				const found = observations.filter(o => o.num > 0 && Matches(o, filter));
				let html = '<table class="ui very compact selectable small table"><thead><tr><th>Observation</th><th>Instrument</th><th>Format</th><th>Observations</th></tr></thead><tbody>';
				found.slice(0, 100).forEach(function(o) {
					html += '<tr style="cursor: pointer" data-key="' + Esc(o.key) + '"><td>' + Esc(o.name) + '</td><td>' + (o.instrument ? Esc(o.instrument) : '<span style="color: gray">(unaffiliated)</span>') + '</td><td><i class="' + FORMATS[o.format].icon + ' icon"></i>' + FORMATS[o.format].label + '</td><td>' + Num(o.num) + '</td></tr>';
				});
				html += '</tbody></table>';
				if (found.length == 0)
					html = '<div class="ui message">No observations match</div>';
				else if (found.length > 100)
					html += '<div style="color: gray">Showing 100 of ' + Num(found.length) + ' matches. Type more to narrow the search</div>';
				else
					html += '<div style="color: gray">Click an observation to select it and its format</div>';
				$('#findresults').html(html);
			}

			/* ----- step 3: observations ----- */
			function RenderObservations() {
				const f = state.format;
				const filter = $('#obsfilter').val().trim().toLowerCase();

				/* instrument items in this format, grouped by instrument */
				const instruments = {};
				observations.filter(o => o.instrumentid && o.format == f).forEach(function(o) {
					if (!instruments[o.instrumentid])
						instruments[o.instrumentid] = { id: o.instrumentid, name: o.instrument, items: [] };
					instruments[o.instrumentid].items.push(o);
				});
				const list = Object.values(instruments).sort((a, b) => a.name.localeCompare(b.name));

				let html = '';
				list.forEach(function(inst) {
					inst.items.sort((a, b) => a.order - b.order || a.name.localeCompare(b.name));
					const instMatches = (filter != '') && inst.name.toLowerCase().includes(filter);
					const items = (filter == '' || instMatches) ? inst.items : inst.items.filter(o => o.name.toLowerCase().includes(filter));
					if (items.length == 0) return;

					const selectable = inst.items.filter(o => o.num > 0);
					const numsel = selectable.filter(o => state.selected[f].has(o.key)).length;
					const total = inst.items.reduce((t, o) => t + o.num, 0);
					const expanded = state.expanded.has(inst.id) || (filter != '');
					const checked = (numsel > 0 && numsel == selectable.length) ? ' checked' : '';
					const indeterminate = (numsel > 0 && numsel < selectable.length);

					html += '<div class="niinstrument" data-instrumentid="' + inst.id + '">' +
						'<div class="niinstrumenthead"><input type="checkbox" class="niinstcheck"' + checked + (indeterminate ? ' data-indeterminate="1"' : '') + (selectable.length == 0 ? ' disabled' : '') + '> ' +
						'<i class="caret ' + (expanded ? 'down' : 'right') + ' icon"></i> <b>' + Esc(inst.name) + '</b> ' +
						'<span style="color: gray">' + Num(inst.items.length) + ' items, ' + Num(total) + ' observations' + (numsel > 0 ? ', <span style="color: #2185d0">' + Num(numsel) + ' selected</span>' : '') + '</span></div>';
					if (expanded) {
						html += '<div class="niitems">';
						items.forEach(function(o) {
							html += '<div class="niitem' + (o.num == 0 ? ' nicount0' : '') + '"><label><input type="checkbox" class="niitemcheck" data-key="' + Esc(o.key) + '"' + (state.selected[f].has(o.key) ? ' checked' : '') + (o.num == 0 ? ' disabled' : '') + '> ' +
								Esc(o.name) + '</label> <span style="color: gray; font-size: smaller">' + Esc(o.type || 'blank') + ' &middot; ' + Num(o.num) + ' observations, ' + Num(o.numsubjects) + ' subjects' + (o.firstdate ? ' &middot; ' + Esc(o.firstdate.substr(0, 10)) + ' to ' + Esc(o.lastdate.substr(0, 10)) : '') + '</span></div>';
						});
						html += '</div>';
					}
					html += '</div>';
				});
				if (html == '')
					html = '<div class="ui message">' + (list.length == 0 ? 'No instrument observations in ' + FORMATS[f].label + ' format' : 'No instrument observations match the filter') + '</div>';
				$('#instrumentlist').html(html);
				$('#instrumentlist input[data-indeterminate]').prop('indeterminate', true);

				/* unaffiliated observations are always CSV */
				if (f == 'csv') {
					$('#unaffiliatedsection').show();
					CreateUnaffiliatedGrid();
					unaffiliatedGrid.setGridOption('quickFilterText', filter);
					SyncUnaffiliatedGrid();
				}
				else
					$('#unaffiliatedsection').hide();

				RenderCrossFormatHint(filter);
				RenderSelectedTray();
			}

			/* when the filter matches observations in other formats, say so */
			function RenderCrossFormatHint(filter) {
				let hints = [];
				if (filter != '') {
					for (const f in FORMATS) {
						if (f == state.format) continue;
						const n = observations.filter(o => o.format == f && o.num > 0 && Matches(o, filter)).length;
						if (n > 0)
							hints.push('<a href="#" onClick="SelectFormat(\'' + f + '\', true); return false;">' + Num(n) + ' in ' + FORMATS[f].label + ' format</a>');
					}
				}
				$('#crossformathint').html(hints.length ? '<div class="ui small info message"><i class="info circle icon"></i> Also found: ' + hints.join(', ') + '</div>' : '');
			}

			function CreateUnaffiliatedGrid() {
				if (unaffiliatedGrid) return;
				const start = performance.now();
				const rows = observations.filter(o => !o.instrumentid);
				unaffiliatedGrid = agGrid.createGrid(document.getElementById('unaffiliatedgrid'), {
					theme: agGrid.themeBalham,
					rowData: rows,
					getRowId: params => params.data.key,
					rowSelection: { mode: 'multiRow', selectAll: 'filtered' },
					columnDefs: [
						{ field: 'name', headerName: 'Observation', flex: 2, filter: true },
						{ field: 'num', headerName: 'Observations', flex: 1, valueFormatter: p => Num(p.value) },
						{ field: 'numsubjects', headerName: 'Subjects', flex: 1, valueFormatter: p => Num(p.value) },
						{ field: 'firstdate', headerName: 'First date', flex: 1, valueFormatter: p => (p.value || '').substr(0, 10) },
						{ field: 'lastdate', headerName: 'Last date', flex: 1, valueFormatter: p => (p.value || '').substr(0, 10) }
					],
					onSelectionChanged: function() {
						if (syncingGrid) return;
						const sel = state.selected.csv;
						for (const key of Array.from(sel))
							if (key.startsWith('u:')) sel.delete(key);
						unaffiliatedGrid.getSelectedRows().forEach(o => sel.add(o.key));
						RenderSelectedTray();
					}
				});
				RecordPerf('Unaffiliated grid (' + Num(rows.length) + ' rows)', 0, performance.now() - start, { queries: [] });
			}

			/* push the selection into the unaffiliated grid */
			function SyncUnaffiliatedGrid() {
				if (!unaffiliatedGrid) return;
				syncingGrid = true;
				const toSelect = [], toDeselect = [];
				unaffiliatedGrid.forEachNode(function(node) {
					const want = state.selected.csv.has(node.data.key);
					if (want && !node.isSelected()) toSelect.push(node);
					if (!want && node.isSelected()) toDeselect.push(node);
				});
				unaffiliatedGrid.setNodesSelected({ nodes: toSelect, newValue: true });
				unaffiliatedGrid.setNodesSelected({ nodes: toDeselect, newValue: false });
				syncingGrid = false;
			}

			function UpdateSelectedCount() {
				if (state.format == null) return;
				$('#selectedcount').text('(' + Num(state.selected[state.format].size) + ')');
				$('#stepdesc3').text(Num(state.selected[state.format].size) + ' selected');
			}

			/* the selected observations, as removable chips. Long selections show the first 100 */
			function RenderSelectedTray() {
				const sel = Array.from(state.selected[state.format]);
				let html = '';
				sel.slice(0, 100).forEach(function(key) {
					const o = observationsByKey[key];
					if (!o) return;
					html += '<a class="ui small label nichip" data-key="' + Esc(key) + '" title="' + Esc(o.instrument ? o.instrument + ': ' + o.name : o.name) + '">' + Esc(o.name) + ' <i class="delete icon"></i></a> ';
				});
				if (sel.length > 100)
					html += '<div style="color: gray">and ' + Num(sel.length - 100) + ' more</div>';
				if (sel.length == 0)
					html = '<span style="color: gray">Nothing selected</span>';
				const num = sel.reduce((t, key) => t + (observationsByKey[key] ? observationsByKey[key].num : 0), 0);
				if (sel.length > 0)
					html += '<div style="margin-top: 0.5em; color: gray">' + Num(num) + ' observations (before filtering by subject and date)</div>';
				$('#selectedtray').html(html);
				UpdateSelectedCount();
			}

			function ClearSelection() {
				state.selected[state.format].clear();
				RenderObservations();
			}

			/* select the observations whose names are in the pasted list. Reports names that weren't found, or that are in another format */
			function ApplyPastedNames() {
				const names = SplitList($('#pastenames').val());
				const f = state.format;
				let numselected = 0;
				let notfound = [], otherformat = [];
				names.forEach(function(name) {
					const lname = name.toLowerCase();
					const matches = observations.filter(o => o.num > 0 && o.name.toLowerCase() == lname);
					const inFormat = matches.filter(o => o.format == f);
					inFormat.forEach(function(o) { state.selected[f].add(o.key); numselected++; });
					if (matches.length == 0) notfound.push(name);
					else if (inFormat.length == 0) otherformat.push(name + ' (' + matches.map(o => FORMATS[o.format].label).filter((v, i, a) => a.indexOf(v) == i).join(', ') + ')');
				});
				let html = '<div class="ui small message">Selected ' + Num(numselected) + ' observations from ' + Num(names.length) + ' names';
				if (otherformat.length) html += '<br><b>In another format, not selected:</b> ' + Esc(otherformat.join('; '));
				if (notfound.length) html += '<br><b>Not found:</b> ' + Esc(notfound.join(', '));
				html += '</div>';
				$('#pasteresult').html(html);
				RenderObservations();
			}

			/* ----- step 4: subjects ----- */
			function CreateSubjectGrid() {
				if (subjectGrid) return;
				subjectGrid = agGrid.createGrid(document.getElementById('subjectgrid'), {
					theme: agGrid.themeBalham,
					rowData: [],
					getRowId: params => String(params.data.enrollmentid),
					rowSelection: { mode: 'multiRow', selectAll: 'filtered' },
					columnDefs: [
						{ field: 'uid', headerName: 'UID', flex: 1, filter: true },
						{ field: 'altuids', headerName: 'Alternate UIDs', flex: 2, filter: true },
						{ field: 'sex', headerName: 'Sex', width: 70 },
						{ field: 'group', headerName: 'Group', flex: 1, filter: true },
						{ field: 'status', headerName: 'Status', flex: 1, filter: true },
						{ field: 'numobservations', headerName: 'Observations', flex: 1, valueFormatter: p => Num(p.value) }
					],
					onSelectionChanged: function() {
						if (syncingGrid) return;
						state.subjects = new Set(subjectGrid.getSelectedRows().map(s => s.enrollmentid));
						UpdateSubjectCount();
					}
				});
				GetJSON('getsubjectlist', 'Subject list', function(data) {
					subjects = data.subjects;
					subjectGrid.setGridOption('rowData', subjects);
					UpdateSubjectCount();
				});
			}

			function UpdateSubjectCount() {
				if (state.subjectMode == 'all') {
					$('#stepdesc4').text('All subjects');
					return;
				}
				$('#subjectselectedcount').text(Num(state.subjects.size) + ' of ' + Num(subjects.length) + ' subjects selected');
				$('#stepdesc4').text(Num(state.subjects.size) + ' subjects');
			}

			/* select subjects by UID or alternate UID */
			function ApplyPastedUIDs() {
				const uids = SplitList($('#pasteuids').val());
				let notfound = [];
				uids.forEach(function(uid) {
					const luid = uid.toLowerCase();
					const matches = subjects.filter(s => s.uid.toLowerCase() == luid || s.altuids.toLowerCase().split(', ').includes(luid));
					matches.forEach(s => state.subjects.add(s.enrollmentid));
					if (matches.length == 0) notfound.push(uid);
				});
				syncingGrid = true;
				const nodes = [];
				subjectGrid.forEachNode(function(node) { if (state.subjects.has(node.data.enrollmentid)) nodes.push(node); });
				subjectGrid.setNodesSelected({ nodes: nodes, newValue: true });
				syncingGrid = false;
				$('#pasteuidresult').html('<div class="ui small message">' + Num(uids.length - notfound.length) + ' of ' + Num(uids.length) + ' UIDs found' + (notfound.length ? '<br><b>Not found:</b> ' + Esc(notfound.join(', ')) : '') + '</div>');
				UpdateSubjectCount();
			}

			/* ----- step 5: summary ----- */
			function RenderSummary() {
				const f = state.format;
				const start = $('#startdate').val(), end = $('#enddate').val();
				let html = '<table class="ui very basic compact table">';
				html += '<tr><td class="collapsing"><b>Data type</b></td><td>Observations</td></tr>';
				html += '<tr><td><b>Format</b></td><td>' + FORMATS[f].label + '</td></tr>';
				html += '<tr><td><b>Observations</b></td><td>' + Num(state.selected[f].size) + ' selected</td></tr>';
				html += '<tr><td><b>Subjects</b></td><td>' + (state.subjectMode == 'all' ? 'All subjects' : Num(state.subjects.size) + ' subjects') + '</td></tr>';
				html += '<tr><td><b>Dates</b></td><td>' + ((start || end) ? Esc((start || 'any') + ' to ' + (end || 'any')) : 'Any date') + '</td></tr>';
				html += '</table>';
				$('#exportsummary').html(html);
			}

			/* the export request, sent to the server as one JSON string */
			function BuildRequest() {
				const itemids = [], names = [];
				state.selected[state.format].forEach(function(key) {
					const o = observationsByKey[key];
					if (!o) return;
					if (o.instrumentid) itemids.push(o.itemid); else names.push(o.name);
				});
				return JSON.stringify({
					format: state.format,
					layout: $('input[name=layout]:checked').val(),
					repeats: $('input[name=repeats]:checked').val(),
					dates: $('#includedates').prop('checked'),
					itemids: itemids,
					names: names,
					subjectmode: state.subjectMode,
					enrollmentids: (state.subjectMode == 'choose') ? Array.from(state.subjects) : [],
					startdate: $('#startdate').val(),
					enddate: $('#enddate').val()
				});
			}

			/* download is only enabled if the current selection was previewed without errors */
			function UpdateDownloadButton() {
				const ok = (previewedRequest != null) && (previewedRequest == BuildRequest());
				$('#downloadbutton').toggleClass('disabled', !ok).attr('title', ok ? '' : 'Preview the export first');
				if (!ok && (previewedRequest != null))
					$('#previewsummary').html('<div class="ui small warning message">The selection changed since the preview. Preview again before downloading</div>');
			}

			function RenderMessages(data) {
				let html = '';
				if (data.errors && data.errors.length)
					html += '<div class="ui error message"><ul class="list"><li>' + data.errors.join('</li><li>') + '</li></ul></div>';
				if (data.warnings && data.warnings.length)
					html += '<div class="ui warning message"><ul class="list"><li>' + data.warnings.join('</li><li>') + '</li></ul></div>';
				$('#previewmessages').html(html);
			}

			/* POST the request to an action. Records the timings */
			function PostRequest(action, label, extra, done) {
				const request = BuildRequest();
				const start = performance.now();
				$.post('nonimaging.php', Object.assign({ action: action, projectid: projectid, request: request }, extra), null, 'json')
					.done(function(data) {
						const received = performance.now();
						done(data, request);
						RecordPerf(label, received - start, performance.now() - received, data.perf);
					})
					.fail(function(xhr) { $('#previewmessages').html('<div class="ui error message">Error: ' + xhr.status + ' ' + Esc(xhr.statusText) + '</div>'); })
					.always(function() { $('#previewbutton, #dryrunbutton').removeClass('loading'); });
			}

			function Preview() {
				$('#previewbutton').addClass('loading');
				previewedRequest = null;
				UpdateDownloadButton();
				$('#previewsummary').html('');
				PostRequest('preview', 'Preview', {}, function(data, request) {
					RenderMessages(data);
					if (data.errors && data.errors.length) {
						$('#previewgrid').hide();
						return;
					}
					previewedRequest = request;
					UpdateDownloadButton();
					/* the totals depend on the format, so the server sends them as label/value pairs */
					const what = (state.format == 'files') ? 'files' : 'rows';
					$('#previewsummary').html('<div class="ui small statistics">' +
						data.stats.map(st => '<div class="statistic"><div class="value">' + Esc(st.value) + '</div><div class="label">' + Esc(st.label) + '</div></div>').join('') + '</div>' +
						'<p style="color: gray">' + (data.numrows > data.rows.length ? 'Showing the first ' + Num(data.rows.length) + ' ' + what : 'Showing all ' + what) + (state.format == 'files' ? '. The zip also has a manifest.csv listing every file and its observation' : '') + '</p>');

					$('#previewgrid').show();
					const columnDefs = data.columns.map((c, i) => ({ headerName: c, valueGetter: p => p.data[i], filter: true, resizable: true }));
					if (!previewGrid)
						previewGrid = agGrid.createGrid(document.getElementById('previewgrid'), { theme: agGrid.themeBalham, columnDefs: columnDefs, rowData: data.rows });
					else {
						previewGrid.setGridOption('columnDefs', columnDefs);
						previewGrid.setGridOption('rowData', data.rows);
					}
				});
			}

			/* the download is a normal form POST, so the browser streams the file to disk */
			function Download() {
				if ($('#downloadbutton').hasClass('disabled')) return;
				const form = $('<form method="post" action="nonimaging.php" style="display: none"></form>');
				form.append($('<input type="hidden" name="action">').val('download'));
				form.append($('<input type="hidden" name="projectid">').val(projectid));
				form.append($('<input type="hidden" name="request">').val(BuildRequest()));
				$('body').append(form);
				form.submit();
				form.remove();
			}

			/* siteadmin: build the whole export on the server without downloading it, and record the timings */
			function DryRun() {
				$('#dryrunbutton').addClass('loading');
				PostRequest('download', 'Download dry run', { dryrun: 1 }, function(data) {
					if (data.errors && data.errors.length) {
						RenderMessages(data);
						return;
					}
					if (data.perf)
						data.perf.queries.push({ label: 'Output: ' + Num(data.rows) + ' rows, ' + (data.bytes / 1048576).toFixed(1) + ' MB', calls: '', rows: data.rows, queryms: '', fetchms: '' });
				});
			}

			/* ----- setup ----- */
			$(document).ready(function() {
				$('.ui.accordion').accordion();
				$('.ui.checkbox').checkbox();

				$('.ui.steps .step').click(function() {
					if (!$(this).hasClass('disabled')) GoToStep(parseInt($(this).attr('data-step')));
				});

				let findTimer = null, filterTimer = null;
				$('#findall').on('input', function() { clearTimeout(findTimer); findTimer = setTimeout(RenderFindResults, 200); });
				$('#obsfilter').on('input', function() { clearTimeout(filterTimer); filterTimer = setTimeout(RenderObservations, 200); });
				$('#subjectfilter').on('input', function() { if (subjectGrid) subjectGrid.setGridOption('quickFilterText', $(this).val()); });

				/* find results: select the observation and its format */
				$('#findresults').on('click', 'tr[data-key]', function() {
					const o = observationsByKey[$(this).attr('data-key')];
					if (!o) return;
					state.selected[o.format].add(o.key);
					if (o.instrumentid) state.expanded.add(o.instrumentid);
					SelectFormat(o.format, true);
				});

				/* instrument list: expand/collapse an instrument, select an instrument, select an item */
				$('#instrumentlist').on('click', '.niinstrumenthead', function(e) {
					if ($(e.target).is('input')) return;
					const id = parseInt($(this).parent().attr('data-instrumentid'));
					if (state.expanded.has(id)) state.expanded.delete(id); else state.expanded.add(id);
					RenderObservations();
				});
				$('#instrumentlist').on('change', '.niinstcheck', function() {
					const id = parseInt($(this).closest('.niinstrument').attr('data-instrumentid'));
					const checked = $(this).prop('checked');
					observations.filter(o => o.instrumentid == id && o.format == state.format && o.num > 0).forEach(function(o) {
						if (checked) state.selected[state.format].add(o.key); else state.selected[state.format].delete(o.key);
					});
					RenderObservations();
				});
				$('#instrumentlist').on('change', '.niitemcheck', function() {
					const key = $(this).attr('data-key');
					if ($(this).prop('checked')) state.selected[state.format].add(key); else state.selected[state.format].delete(key);
					RenderObservations();
				});

				/* remove an observation from the selected tray */
				$('#selectedtray').on('click', '.nichip', function() {
					state.selected[state.format].delete($(this).attr('data-key'));
					RenderObservations();
				});

				/* layout options. The date column option is only for first/last */
				$('input[name=layout], input[name=repeats]').change(function() {
					const wide = ($('input[name=layout]:checked').val() == 'wide');
					$('#wideoptions').toggle(wide);
					$('#dateoption').toggle(wide && ($('input[name=repeats]:checked').val() != 'all'));
					UpdateDownloadButton();
				});
				$('#includedates').change(UpdateDownloadButton);

				/* subjects: all or chosen */
				$('input[name=subjectmode]').change(function() {
					state.subjectMode = $(this).val();
					$('#subjectchooser').toggle(state.subjectMode == 'choose');
					if (state.subjectMode == 'choose') CreateSubjectGrid();
					UpdateSubjectCount();
				});

				RenderFormatCards();
				GetJSON('getobservationlist', 'Observation list', function(data) {
					data.instrumentitems.forEach(function(o) {
						o.key = 'i' + o.itemid;
						o.format = FormatOfType(o.type);
					});
					data.unaffiliated.forEach(function(o) {
						o.key = 'u:' + o.name;
						o.format = 'csv';
						o.instrument = '';
						o.instrumentid = 0;
					});
					observations = data.instrumentitems.concat(data.unaffiliated);
					observations.forEach(o => observationsByKey[o.key] = o);
					observationsLoaded = true;
					RenderFormatCards();
				});
			});
		</script>
		<?
	}
?>

<? include("footer.php") ?>
