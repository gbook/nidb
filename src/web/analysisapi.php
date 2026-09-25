<?
 // ------------------------------------------------------------------------------
 // NiDB analysisapi.php
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

 /* Analysis check-in API, called by pipeline job scripts running on a compute host.
	This is an HTTP alternative to the 'nidb cluster -u <submodule>' command.

	Authentication is per-analysis: when the pipeline module writes a job script it
	generates a random token, stores its SHA-256 hash in analysis.analysis_apitoken,
	and exports the raw token to the job as $NIDB_APITOKEN. A token can only modify
	the analysis it was issued for.

	Parameters are read from POST or GET. All responses are JSON:
		{"success": true|false, "message": "..."}

	Actions
		checkin        analysisid, token, status, [message], [step], [command], [hostname]
		resultinsert   analysisid, token, desc, one of [text|number|file|image], [unit]
		updateanalysis analysisid, token, numfiles, disksize (bytes)
		setcomplete    analysisid, token, iscomplete (0|1)

	Example
		curl -s "$NIDB_APIURL" -d action=checkin -d analysisid=$NIDB_ANALYSISID \
			-d token=$NIDB_APITOKEN -d status=processing --data-urlencode "message=processing step 2 of 5"
 */
	define("LEGIT_REQUEST", true);
	$nologin = true; /* authentication is handled here, not by includes_php.php */

	/* buffer output so stray output from the included files (or an SQL error) does not corrupt the JSON */
	ob_start();

	require "functions.php";
	require "includes_php.php";

	$action = AAPIParam("action");
	$analysisid = AAPIParam("analysisid");
	$token = AAPIParam("token");

	if (($analysisid == "") || ($token == ""))
		AAPIRespond(false, "Missing required parameters: analysisid, token.", 400);
	if (!ctype_digit($analysisid))
		AAPIRespond(false, "analysisid is not an integer.", 400);
	$analysisid = (int)$analysisid;

	if (!AAPIAuthenticate($analysisid, $token))
		AAPIRespond(false, "Authentication failed.", 401);

	switch ($action) {
		case 'checkin':        Checkin($analysisid); break;
		case 'resultinsert':   ResultInsert($analysisid); break;
		case 'updateanalysis': UpdateAnalysis($analysisid); break;
		case 'setcomplete':    SetComplete($analysisid); break;
		default:               AAPIRespond(false, "Unknown or missing action.", 400);
	}


	/* -------------------------------------------- */
	/* ------- Checkin ---------------------------- */
	/* -------------------------------------------- */
	/* Equivalent to 'nidb cluster -u pipelinecheckin' */
	function Checkin($analysisid) {
		$status = AAPIParam("status");
		$message = AAPIParam("message");
		$step = AAPIParam("step");
		$command = AAPIParam("command");
		$hostname = AAPIHostname();

		$validstatus = array('started', 'startedrerun', 'startedsupplement', 'complete', 'completererun', 'completesupplement', 'processing', 'error', 'notcompleted');
		if (!in_array($status, $validstatus, true))
			AAPIRespond(false, "Invalid status [$status]. Must be one of: " . implode(", ", $validstatus), 400);

		/* get the step number, or try to parse it from a message like 'processing step 2 of 5' */
		$stepnum = 0;
		if ($step != "") {
			$stepnum = (int)$step;
		}
		elseif (preg_match('/processing .*step (\d+) of /i', $message, $matches)) {
			$stepnum = (int)$matches[1];
		}

		switch ($status) {
			case 'started':
				$event = 'status_analysisStarted';
				$sqlstring = "update analysis set analysis_status = ?, analysis_statusmessage = ?, analysis_statusdatetime = now(), analysis_clusterstartdate = now(), analysis_hostname = ? where analysis_id = ?";
				$params = [$status, $message, $hostname, $analysisid];
				break;
			case 'startedrerun':
			case 'startedsupplement':
				$event = ($status == 'startedrerun') ? 'status_rerunStarted' : 'status_supplementStarted';
				$sqlstring = "update analysis set analysis_status = ?, analysis_statusmessage = ?, analysis_statusdatetime = now(), analysis_hostname = ? where analysis_id = ?";
				$params = [$status, $message, $hostname, $analysisid];
				break;
			case 'complete':
				$event = 'status_analysisComplete';
				$sqlstring = "update analysis set analysis_status = ?, analysis_statusmessage = ?, analysis_statusdatetime = now(), analysis_clusterenddate = now(), analysis_hostname = ? where analysis_id = ?";
				$params = [$status, $message, $hostname, $analysisid];
				break;
			case 'completererun':
				$event = 'status_rerunComplete';
				$sqlstring = "update analysis set analysis_status = 'complete', analysis_statusmessage = ?, analysis_rerunresults = 0 where analysis_id = ?";
				$params = [$message, $analysisid];
				break;
			case 'completesupplement':
				$event = 'status_supplementComplete';
				$sqlstring = "update analysis set analysis_status = 'complete', analysis_statusmessage = ?, analysis_rerunresults = 0, analysis_runsupplement = 0 where analysis_id = ?";
				$params = [$message, $analysisid];
				break;
			default:
				if ($stepnum > 0)
					$event = 'status_analysisStepCheckin';
				elseif (stripos($message, "processing result script") !== false)
					$event = 'status_resultScript';
				elseif (stripos($message, "updating analysis files") !== false)
					$event = 'status_updateFileList';
				elseif (stripos($message, "checking for completed files") !== false)
					$event = 'status_checkSuccessFiles';
				else
					$event = 'cluster_checkinStep';
				$sqlstring = "update analysis set analysis_status = ?, analysis_statusmessage = ?, analysis_rerunresults = 0, analysis_statusdatetime = now(), analysis_hostname = ? where analysis_id = ?";
				$params = [$status, $message, $hostname, $analysisid];
		}

		$types = (count($params) == 4) ? 'sssi' : 'si';
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		$logmessage = $message;
		if ($command != "")
			$logmessage .= " [$command]";
		LogAnalysisEvent($analysisid, $event, $stepnum, $logmessage, $hostname);

		AAPIRespond(true, "Checked in analysis [$analysisid] with status [$status]");
	}


	/* -------------------------------------------- */
	/* ------- ResultInsert ----------------------- */
	/* -------------------------------------------- */
	/* Equivalent to 'nidb cluster -u resultinsert' */
	function ResultInsert($analysisid) {
		$text = AAPIParam("text");
		$number = AAPIParam("number");
		$file = AAPIParam("file");
		$image = AAPIParam("image");
		$desc = AAPIParam("desc");
		$unit = AAPIParam("unit");

		/* exactly one type of result can be inserted per call */
		$numtypes = 0;
		foreach (array($text, $number, $file, $image) as $val)
			if ($val != "") $numtypes++;
		if ($numtypes == 0)
			AAPIRespond(false, "text, number, file, and image are all blank. There is nothing to insert.", 400);
		if ($numtypes > 1)
			AAPIRespond(false, "More than one of text, number, file, or image was specified. Only one type of result can be specified at a time.", 400);
		if ($desc == "")
			AAPIRespond(false, "Description of the result is blank. You must include a description/label (desc) of this result.", 400);
		if (($number != "") && !is_numeric($number))
			AAPIRespond(false, "number is not an integer or floating point value [$number]", 400);

		$resultnameid = GetOrInsertLookupID("analysis_resultnames", "resultname_id", "result_name", $desc);

		if ($text != "") {
			$sqlstring = "insert into analysis_results (analysis_id, result_type, result_nameid, result_text) values (?, 't', ?, ?) on duplicate key update result_count = result_count + 1";
			$params = [$analysisid, $resultnameid, $text];
			$types = 'iis';
		}
		elseif ($number != "") {
			$resultunitid = GetOrInsertLookupID("analysis_resultunit", "resultunit_id", "result_unit", $unit);
			$value = (float)$number;
			$sqlstring = "insert into analysis_results (analysis_id, result_type, result_nameid, result_unitid, result_value) values (?, 'v', ?, ?, ?) on duplicate key update result_count = result_count + 1";
			$params = [$analysisid, $resultnameid, $resultunitid, $value];
			$types = 'iiid';
		}
		else {
			/* file and image results both store a path */
			$resulttype = ($file != "") ? 'f' : 'i';
			$filename = ($file != "") ? $file : $image;
			$sqlstring = "insert into analysis_results (analysis_id, result_type, result_nameid, result_filename) values (?, ?, ?, ?) on duplicate key update result_count = result_count + 1";
			$params = [$analysisid, $resulttype, $resultnameid, $filename];
			$types = 'isis';
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		AAPIRespond(true, "Inserted result [$desc] for analysis [$analysisid]");
	}


	/* -------------------------------------------- */
	/* ------- UpdateAnalysis --------------------- */
	/* -------------------------------------------- */
	/* Equivalent to 'nidb cluster -u updateanalysis'. The file count and disk
	   size are calculated on the compute host and passed in. */
	function UpdateAnalysis($analysisid) {
		$numfiles = AAPIParam("numfiles");
		$disksize = AAPIParam("disksize");

		if (!ctype_digit($numfiles) || !ctype_digit($disksize))
			AAPIRespond(false, "numfiles and disksize (bytes) are required and must be non-negative integers.", 400);
		$numfiles = (int)$numfiles;
		$disksize = (float)$disksize; /* analysis_disksize is a double */

		$sqlstring = "update analysis set analysis_disksize = ?, analysis_numfiles = ? where analysis_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'dii', $disksize, $numfiles, $analysisid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$disksize, $numfiles, $analysisid]);
		mysqli_stmt_close($stmt);

		AAPIRespond(true, "Updated analysis [$analysisid] numfiles [$numfiles] disksize [$disksize]");
	}


	/* -------------------------------------------- */
	/* ------- SetComplete ------------------------ */
	/* -------------------------------------------- */
	/* Equivalent to 'nidb cluster -u checkcompleteanalysis'. The check for the
	   pipeline's completion files is done on the compute host and the result passed in. */
	function SetComplete($analysisid) {
		$iscomplete = AAPIParam("iscomplete");

		if (($iscomplete !== "0") && ($iscomplete !== "1"))
			AAPIRespond(false, "iscomplete is required and must be 0 or 1.", 400);
		$iscomplete = (int)$iscomplete;

		$sqlstring = "update analysis set analysis_iscomplete = ? where analysis_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $iscomplete, $analysisid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$iscomplete, $analysisid]);
		mysqli_stmt_close($stmt);

		AAPIRespond(true, "Set analysis [$analysisid] iscomplete [$iscomplete]");
	}


	/* ------------------------------------ helpers ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AAPIAuthenticate ------------------- */
	/* -------------------------------------------- */
	/* Check the token against the hash stored for this analysis. The same generic
	   failure is returned for an unknown analysis and a wrong token. */
	function AAPIAuthenticate($analysisid, $token) {
		$stmt = mysqli_prepare($GLOBALS['linki'], "select analysis_apitoken from analysis where analysis_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $analysisid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		$storedhash = $row['analysis_apitoken'] ?? '';
		if ($storedhash == '')
			return false;

		return hash_equals($storedhash, hash('sha256', $token));
	}


	/* -------------------------------------------- */
	/* ------- LogAnalysisEvent ------------------- */
	/* -------------------------------------------- */
	/* Same as nidb::LogAnalysisEvent() in the C++ backend */
	function LogAnalysisEvent($analysisid, $event, $stepnum, $message, $hostname) {
		$message = ($message == "") ? null : $message;
		$sqlstring = "insert into analysis_log (analysis_id, analysislog_event, analysislog_eventstatus, step_number, analysislog_message, analysislog_hostname) values (?, ?, 'success', ?, ?, ?)";
		$params = [$analysisid, $event, $stepnum, $message, $hostname];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isiss', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- GetOrInsertLookupID ---------------- */
	/* -------------------------------------------- */
	/* get the row ID of a name/unit lookup value, inserting it if it doesn't exist.
	   $table, $idcol, $valcol are hardcoded by the callers, never user input */
	function GetOrInsertLookupID($table, $idcol, $valcol, $value) {
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert ignore into `$table` (`$valcol`) values (?)");
		mysqli_stmt_bind_param($stmt, 's', $value);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		$stmt = mysqli_prepare($GLOBALS['linki'], "select `$idcol` from `$table` where `$valcol` = ?");
		mysqli_stmt_bind_param($stmt, 's', $value);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		if (!$row)
			AAPIRespond(false, "Unable to find or create [$value] in $table", 500);
		return (int)$row[$idcol];
	}


	/* -------------------------------------------- */
	/* ------- AAPIHostname ----------------------- */
	/* -------------------------------------------- */
	/* hostname of the compute host: passed in by the job, or the requesting IP */
	function AAPIHostname() {
		$hostname = AAPIParam("hostname");
		if ($hostname == "")
			$hostname = $_SERVER['REMOTE_ADDR'] ?? '';
		return substr($hostname, 0, 255);
	}


	/* -------------------------------------------- */
	/* ------- AAPIParam -------------------------- */
	/* -------------------------------------------- */
	/* read a parameter from POST, then GET */
	function AAPIParam($key) {
		if (isset($_POST[$key]))
			return trim((string)$_POST[$key]);
		if (isset($_GET[$key]))
			return trim((string)$_GET[$key]);
		return "";
	}


	/* -------------------------------------------- */
	/* ------- AAPIRespond ------------------------ */
	/* -------------------------------------------- */
	/* Emit a JSON response and terminate */
	function AAPIRespond($success, $message, $httpcode = 200) {
		/* discard buffered output so the body is exactly the JSON */
		while (ob_get_level() > 0) { ob_end_clean(); }
		http_response_code($httpcode);
		header("Content-Type: application/json; charset=UTF-8");
		header("Cache-Control: no-store");

		echo json_encode(array("success" => (bool)$success, "message" => $message), JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
		exit(0);
	}
?>
