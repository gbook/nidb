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
	the analysis it was issued for. The token is cleared when the job checks in with a
	complete status (complete, completererun, completesupplement), so a token read from
	an old job script stops working once that job has finished.

	Parameters are read from POST or GET. All responses are JSON:
		{"success": true|false, "message": "..."}

	Actions
		checkin        analysisid, token, status, [message], [step], [command], [hostname]
		resultinsert   analysisid, token, desc, one of [text|number|file|image], [unit]
		updateanalysis analysisid, token, numfiles, disksize (bytes)
		setcomplete    analysisid, token, iscomplete (0|1)

	Container build actions, called by build-container.sh (modulePipelineContainer). These use a per-build token,
	stored as a SHA-256 hash in pipeline_containers.build_apitoken, and only work while the row is 'building'.
	containercomplete and containererror are the build's last call, and clear the token
		containercheckin   containerid, token, step, [message], [hostname]
		containercomplete  containerid, token, path, size (bytes), sha256, [message], [warnings], [buildsize], [buildfilecount], [hostname]
		containererror     containerid, token, message, [step], [warnings], [buildsize], [buildfilecount], [hostname]
		buildsize (bytes) and buildfilecount are what's left in the build directory after the job's cleanup

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
	$token = AAPIParam("token");

	/* container builds authenticate against pipeline_containers, not analysis */
	if (in_array($action, array('containercheckin', 'containercomplete', 'containererror'), true)) {
		$containerid = AAPIParam("containerid");
		if (($containerid == "") || ($token == ""))
			AAPIRespond(false, "Missing required parameters: containerid, token.", 400);
		if (!ctype_digit($containerid))
			AAPIRespond(false, "containerid is not an integer.", 400);
		$containerid = (int)$containerid;

		if (!AAPIAuthenticateContainer($containerid, $token))
			AAPIRespond(false, "Authentication failed.", 401);

		switch ($action) {
			case 'containercheckin':  ContainerCheckin($containerid); break;
			case 'containercomplete': ContainerComplete($containerid); break;
			case 'containererror':    ContainerError($containerid); break;
		}
	}

	$analysisid = AAPIParam("analysisid");
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

		/* the complete statuses are the job's last check-in, so they also clear the token */
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
				$sqlstring = "update analysis set analysis_status = ?, analysis_statusmessage = ?, analysis_statusdatetime = now(), analysis_clusterenddate = now(), analysis_hostname = ?, analysis_apitoken = null where analysis_id = ?";
				$params = [$status, $message, $hostname, $analysisid];
				break;
			case 'completererun':
				$event = 'status_rerunComplete';
				$sqlstring = "update analysis set analysis_status = 'complete', analysis_statusmessage = ?, analysis_rerunresults = 0, analysis_apitoken = null where analysis_id = ?";
				$params = [$message, $analysisid];
				break;
			case 'completesupplement':
				$event = 'status_supplementComplete';
				$sqlstring = "update analysis set analysis_status = 'complete', analysis_statusmessage = ?, analysis_rerunresults = 0, analysis_runsupplement = 0, analysis_apitoken = null where analysis_id = ?";
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


	/* -------------------------------------------- */
	/* ------- ContainerCheckin ------------------- */
	/* -------------------------------------------- */
	/* a major step of a container build. The first check-in is when the job started running, so it sets build_startdate */
	function ContainerCheckin($containerid) {
		$step = AAPIParam("step");
		$message = AAPIParam("message");
		$hostname = AAPIHostname();

		/* buildComplete is set by containercomplete */
		$validsteps = array('preReferenceAnalysisChecks', 'runningReferenceAnalysis', 'postReferenceAnalysisChecks', 'buildingContainer', 'runningValidationAnalysis');
		if (!in_array($step, $validsteps, true))
			AAPIRespond(false, "Invalid step [$step]. Must be one of: " . implode(", ", $validsteps), 400);

		$log = ContainerLogLine("[$hostname] $step" . (($message == "") ? "" : ": $message"));
		$sqlstring = "update pipeline_containers set build_step = ?, build_step_message = ?, build_hostname = ?, build_startdate = coalesce(build_startdate, now()), build_log = concat(coalesce(build_log, ''), ?) where pipelinecontainer_id = ?";
		$params = [$step, ContainerStepMessage($message), $hostname, $log, $containerid];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssi', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		AAPIRespond(true, "Checked in container [$containerid] at step [$step]");
	}


	/* -------------------------------------------- */
	/* ------- ContainerComplete ------------------ */
	/* -------------------------------------------- */
	/* the build succeeded. The .sif stays on the cluster; path is where it is, as the cluster sees it */
	function ContainerComplete($containerid) {
		$path = AAPIParam("path");
		$size = AAPIParam("size");
		$sha256 = strtolower(AAPIParam("sha256"));
		$message = AAPIParam("message");
		$hostname = AAPIHostname();

		if (($path == "") || (substr($path, 0, 1) != "/"))
			AAPIRespond(false, "path is required and must be an absolute path.", 400);
		if (!ctype_digit($size) || ((int)$size < 1))
			AAPIRespond(false, "size (bytes) is required and must be a positive integer.", 400);
		if (!preg_match('/^[0-9a-f]{64}$/', $sha256))
			AAPIRespond(false, "sha256 is required and must be 64 hex characters.", 400);
		$size = (int)$size;

		if ($message == "")
			$message = "Container built";
		$log = ContainerLogLine("[$hostname] buildComplete: $message. Container [$path] size [$size] sha256 [$sha256]");
		list($buildsize, $buildfilecount) = ContainerBuildDirStats();
		$sqlstring = "update pipeline_containers set build_status = 'complete', build_step = 'buildComplete', build_step_message = ?, build_hostname = ?, build_enddate = now(), build_apitoken = null, build_warnings = concat(coalesce(build_warnings, ''), ?), build_log = concat(coalesce(build_log, ''), ?), container_path = ?, container_size = ?, container_sha256 = ?, container_date = now(), build_size = coalesce(?, build_size), build_filecount = coalesce(?, build_filecount) where pipelinecontainer_id = ?";
		$params = [ContainerStepMessage($message), $hostname, ContainerWarnings(), $log, $path, $size, $sha256, $buildsize, $buildfilecount, $containerid];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssssisiii', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		AAPIRespond(true, "Container [$containerid] is complete");
	}


	/* -------------------------------------------- */
	/* ------- ContainerError --------------------- */
	/* -------------------------------------------- */
	/* the build failed. build_step is left at the last step checked in, so it shows where the build stopped */
	function ContainerError($containerid) {
		$message = AAPIParam("message");
		$step = AAPIParam("step");
		$hostname = AAPIHostname();

		if ($message == "")
			$message = "The build failed, with no message";
		$log = ContainerLogLine("[$hostname] ERROR" . (($step == "") ? "" : " during $step") . ": $message");
		list($buildsize, $buildfilecount) = ContainerBuildDirStats();
		$sqlstring = "update pipeline_containers set build_status = 'error', build_step_message = ?, build_hostname = ?, build_enddate = now(), build_apitoken = null, build_warnings = concat(coalesce(build_warnings, ''), ?), build_log = concat(coalesce(build_log, ''), ?), build_size = coalesce(?, build_size), build_filecount = coalesce(?, build_filecount) where pipelinecontainer_id = ?";
		$params = [ContainerStepMessage($message), $hostname, ContainerWarnings(), $log, $buildsize, $buildfilecount, $containerid];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssiii', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		AAPIRespond(true, "Container [$containerid] marked as error");
	}


	/* ------------------------------------ helpers ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AAPIAuthenticateContainer ---------- */
	/* -------------------------------------------- */
	/* Check the token against the hash stored for this container build. Only a build that's
	   running ('building') can check in */
	function AAPIAuthenticateContainer($containerid, $token) {
		$stmt = mysqli_prepare($GLOBALS['linki'], "select build_apitoken from pipeline_containers where pipelinecontainer_id = ? and build_status = 'building'");
		mysqli_stmt_bind_param($stmt, 'i', $containerid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		$storedhash = $row['build_apitoken'] ?? '';
		if ($storedhash == '')
			return false;

		return hash_equals($storedhash, hash('sha256', $token));
	}


	/* -------------------------------------------- */
	/* ------- ContainerLogLine ------------------- */
	/* -------------------------------------------- */
	/* a line for build_log, in the same format as modulePipelineContainer::AppendBuildLog() */
	function ContainerLogLine($text) {
		return "[" . date("Y-m-d H:i:s") . "] $text\n";
	}


	/* -------------------------------------------- */
	/* ------- ContainerBuildDirStats ------------- */
	/* -------------------------------------------- */
	/* the optional buildsize (bytes) and buildfilecount: what's left in the build directory. null if missing or not a
	   whole number, so the columns keep their value */
	function ContainerBuildDirStats() {
		$stats = array();
		foreach (array("buildsize", "buildfilecount") as $name) {
			$value = trim(AAPIParam($name));
			$stats[] = (($value != "") && ctype_digit($value)) ? (int)$value : null;
		}
		return $stats;
	}


	/* -------------------------------------------- */
	/* ------- ContainerStepMessage --------------- */
	/* -------------------------------------------- */
	/* build_step_message is a varchar(255). The full message is in build_log */
	function ContainerStepMessage($message) {
		return mb_substr($message, 0, 255);
	}


	/* -------------------------------------------- */
	/* ------- ContainerWarnings ------------------ */
	/* -------------------------------------------- */
	/* the build's warnings (build-warnings.txt, one per line), to append to build_warnings */
	function ContainerWarnings() {
		$warnings = AAPIParam("warnings");
		return ($warnings == "") ? "" : "$warnings\n";
	}



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
