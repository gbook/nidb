<?
 // ------------------------------------------------------------------------------
 // NiDB ajaxapi.php
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

	ob_start();
	session_start();
	require "functions.php";
	require "includes_php.php";

	//PrintVariable($_GET);
	
	$action = GetVariable("action");
	$nfspath = GetVariable("nfspath");
	$connectionid = GetVariable("connectionid");
	$transactionid = GetVariable("transactionid");
	$detail = GetVariable("detail");
	$total = GetVariable("total");
	$jobid = GetVariable("jobid");
	$uid = GetVariable("uid");
	$hostname = GetVariable("hostname");
	$username = GetVariable("username");
	$clustertype = GetVariable("clustertype");
	$submithostuser = GetVariable("submithostuser");
	$term = GetVariable("term");
	$instrumentname = GetVariable("instrumentname");
	$instrumentid = GetVariable("instrumentid");
	$itemname = GetVariable("itemname");
	$itemtype = GetVariable("itemtype");
	$itemnotes = GetVariable("itemnotes");
	$instrumentnotes = GetVariable("instrumentnotes");
	$originalname = GetVariable("originalname");
	$itemnamesJson = GetVariable("itemnames");
	$itemsJson     = GetVariable("items");
	$createmappings = GetVariable("createmappings");

	$projectid = GetVariable("projectid");
	$enrollmentid = GetVariable("enrollmentid");
	$observationid = GetVariable("observationid");
	$subjectid = GetVariable("subjectid");
	$studyid = GetVariable("studyid");
	$column = GetVariable("column");
	$value = GetVariable("value");
	$tz_offset = GetVariable("tz_offset");
	$surveyid = GetVariable("surveyid");
	$observationids = GetVariable("observationids");
	$mappingid  = GetVariable("mappingid");
	$flagname   = GetVariable("flagname");
	$source_type          = GetVariable("source_type");
	$avicenna_question    = GetVariable("avicenna_question");
	$avicenna_variable    = GetVariable("avicenna_variable");
	$avicenna_survey      = GetVariable("avicenna_survey");
	$avicenna_datasource  = GetVariable("avicenna_datasource");
	$avicenna_datatype    = GetVariable("avicenna_datatype");
	$redcap_choice_code   = GetVariable("redcap_choice_code");
	$redcap_validation    = GetVariable("redcap_validation");
	$redcap_event         = GetVariable("redcap_event");
	$redcap_form          = GetVariable("redcap_form");
	$redcap_field         = GetVariable("redcap_field");
	$redcap_datatype      = GetVariable("redcap_datatype");
	$redcap_datefield     = GetVariable("redcap_datefield");
	$nidb_instrument      = GetVariable("nidb_instrument");
	$nidb_variable        = GetVariable("nidb_variable");
	$flag_date_from_field = GetVariable("flag_date_from_field");
	$flag_can_repeat      = GetVariable("flag_can_repeat");
	$flag_import_meta        = GetVariable("flag_import_meta");
	$avicenna_variablecount  = GetVariable("avicenna_variablecount");
	$startdate = GetVariable("startdate");
	$enddate = GetVariable("enddate");
	$rater = GetVariable("rater");
	$notes = GetVariable("notes");
	$surveystatus = GetVariable("status");

	$s['pipelineid'] = GetVariable("pipelineid");
	$s['dependency'] = GetVariable("dependency");
	$s['deplevel'] = GetVariable("deplevel");
	$s['groupid'] = GetVariable("groupid");
	$s['projectid'] = GetVariable("projectid");
	$s['datasteps'] = GetVariable("datasteps");

	/* determine action */
	switch($action) {
		case 'pipelinetestsearch':
			PipelineTestSearch($s);
			break;
		case 'searchsubject':
			SearchSubject($uid);
			break;
		case 'searchicd10':
			SearchICD10($term);
			break;
		case 'searchobservationnames':
			SearchObservationNames($term, $instrumentname);
			break;
		case 'validatepath':
			ValidatePath($nfspath);
			break;
		case 'checkuser':
			CheckUsername($username);
			break;
		case 'remoteexportstatus':
			RemoteExportStatus($connectionid, $transactionid, $detail, $total);
			break;
		case 'checksgehost':
			CheckSGESubmitStatus($hostname, $clustertype, $submithostuser);
			break;
		case 'updatesubjectdetails':
			UpdateSubjectDetails($subjectid, $projectid, $column, $value);
			break;
		case 'updatestudydetails':
			UpdateStudyDetails($subjectid, $studyid, $column, $value);
			break;
		case 'updateobservationdetails':
			UpdateObservationDetails($observationid, $column, $value, $tz_offset);
			break;
		case 'updateseriesdetails':
			UpdateSeriesDetails(GetVariable("id"), GetVariable("modality"), GetVariable("element_id"), GetVariable("update_value"));
			break;
		case 'checkseriesobject':
			CheckSeriesObject(GetVariable("seriesid"), GetVariable("modality"), GetVariable("datatype"));
			break;
		case 'getseriesthumbnail':
			GetSeriesThumbnail(GetVariable("seriesid"), GetVariable("modality"));
			break;
		case 'downloadfile':
			DownloadFile(GetVariable("fileid"));
			break;
		case 'horizontalchart':
			HorizontalChart(GetVariable("w"), GetVariable("h"), GetVariable("v"), GetVariable("c"), GetVariable("b"));
			break;
		case 'stddevchart':
			StdDevChart(GetVariable("w"), GetVariable("h"), GetVariable("min"), GetVariable("max"), GetVariable("mean"), GetVariable("std"), GetVariable("i"), GetVariable("b"));
			break;
		case 'getobservationmeta':
			GetObservationMeta($observationid);
			break;
		case 'getobservationtimeseries':
			GetObservationTimeseries($observationid, GetVariable("tstart"), GetVariable("tend"), GetVariable("maxpoints"));
			break;
		case 'getchecklisttimeseries':
			GetChecklistTimeseries(GetVariable("enrollmentid"), GetVariable("instrumentitemid"), GetVariable("tstart"), GetVariable("tend"), GetVariable("maxpoints"));
			break;
		case 'setmissingreason':
			ChecklistSetMissingReason(GetVariable("enrollmentid"), GetVariable("projectchecklistid"), GetVariable("reason"));
			break;
		case 'deletemissingreason':
			ChecklistDeleteMissingReason(GetVariable("missingdataid"));
			break;
		case 'bulkupdateobservations':
			BulkUpdateObservations($observationids, $column, $value, $tz_offset);
			break;
		case 'bulkdeleteobservations':
			BulkDeleteObservations($observationids);
			break;
		case 'bulkconvertvaluetometa':
			BulkConvertValueToMeta($observationids);
			break;
		case 'bulkmovenewsurvey':
			BulkMoveToNewSurvey($observationids);
			break;
		case 'searchinstruments':
			SearchInstruments($term, $projectid);
			break;
		case 'searchinstrumentitems':
			SearchInstrumentItems($term, $instrumentid);
			break;
		case 'addinstrument':
			AddInstrumentAjax($instrumentname, $instrumentnotes, $projectid);
			break;
		case 'addinstrumentitem':
			AddInstrumentItemAjax($itemname, $itemtype, $itemnotes, $instrumentid);
			break;
		case 'formalizeinstrument':
			FormalizeInstrument($instrumentname, $originalname, $projectid, $itemnamesJson);
			break;
		case 'getsurveys':
			GetSurveys((int)$enrollmentid, (int)$instrumentid);
			break;
		case 'assigntosurvey':
			AssignToSurvey((int)$surveyid, $observationids);
			break;
		case 'createandassignsurvey':
			CreateAndAssignSurvey((int)$enrollmentid, (int)$instrumentid, $startdate, $enddate, $rater, $notes, $observationids);
			break;
		case 'updatesurvey':
			UpdateSurvey((int)$surveyid, $startdate, $enddate, $rater, $notes, $surveystatus);
			break;
		case 'getinstrumentitems':
			GetInstrumentItems((int)$instrumentid);
			break;
		case 'getinstrumentbyname':
			GetInstrumentByName($instrumentname, (int)$projectid);
			break;
		case 'createinstrumentitems':
			CreateInstrumentItems($instrumentname, $instrumentnotes, (int)$projectid, $itemsJson, $redcap_form, $redcap_event, (int)$createmappings);
			break;
		case 'updatemappingflag':
			UpdateMappingFlag((int)$mappingid, $flagname, (int)$value);
			break;
		case 'savemapping':
			SaveMapping((int)$mappingid, (int)$projectid, $source_type, (int)$avicenna_question, $avicenna_variable, $avicenna_variablecount, $avicenna_survey, $avicenna_datasource, $avicenna_datatype, $redcap_event, $redcap_form, $redcap_field, $redcap_choice_code, $redcap_datatype, $redcap_validation, $redcap_datefield, (int)$nidb_instrument, (int)$nidb_variable, (int)$flag_date_from_field, (int)$flag_can_repeat, (int)$flag_import_meta);
			break;
		case 'deletemapping':
			DeleteMapping((int)$mappingid);
			break;
		case 'bulkdeletemappings':
			BulkDeleteMappings(GetVariable('ids'));
			break;
		case 'bulkdeleteitems':
			BulkDeleteItems(GetVariable('ids'));
			break;
		case 'bulkupdateitemtype':
			BulkUpdateItemType(GetVariable('ids'), GetVariable('type'));
			break;
		case 'dicomreceivercounts':
			GetDicomReceiverCounts();
			break;
		case 'dicomarchivedsummary':
			GetDicomArchivedSummary();
			break;
		case 'fileiolist':
			GetFileIOList();
			break;
		case 'markanalysescomplete':
			MarkAnalysesComplete(GetVariable('analysisids'));
			break;
		case 'deleteanalyses':
			DeleteSelectedAnalyses(GetVariable('analysisids'));
			break;
		case 'setpipelinefavorite':
			SetPipelineFavorite(GetVariable('pipelineid'), GetVariable('favorite'));
			break;
		case 'checkbashsyntax':
			CheckBashSyntax(GetVariable('script'));
			break;
	}
	

	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- JsonHeader ------------------------- */
	/* -------------------------------------------- */
	/* Discard all buffered output (debug HTML, notices, etc.) and set JSON content-type.
	   Call at the top of every function that returns JSON. */
	function JsonHeader() {
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: application/json');
	}


	/* -------------------------------------------- */
	/* ------- CheckBashSyntax -------------------- */
	/* -------------------------------------------- */
	/* Check a pipeline script for bash problems and return Ace editor annotations as JSON. Uses shellcheck
	   if it is installed, otherwise falls back to 'bash -n' (syntax errors only). The script is only ever
	   parsed, never run */
	function CheckBashSyntax($script) {
		JsonHeader();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			http_response_code(405);
			echo json_encode(array('error' => 'POST required'));
			return;
		}

		/* every checker is an external command, so say so plainly instead of reporting a clean script */
		if (!function_exists('exec')) {
			http_response_code(501);
			echo json_encode(array('error' => 'This server does not allow PHP to run external commands (exec is disabled), so scripts cannot be checked'));
			return;
		}

		$script = (string)$script;
		if (strlen($script) > 1000000) {
			http_response_code(413);
			echo json_encode(array('error' => 'Script is too large to check'));
			return;
		}

		/* pipeline variables like {analysisrootdir} are replaced on the server before the script runs, so
		   they are not bash. Blank them out with a word of the same length, which keeps the line and column
		   numbers the checker reports lined up with what the user sees in the editor */
		$cleanscript = preg_replace_callback('/\{[A-Za-z_][A-Za-z0-9_]*\}/', function($m) { return str_repeat('x', strlen($m[0])); }, $script);

		/* the leading shebang line keeps shellcheck from complaining about a missing one, and is removed
		   from the reported line numbers below */
		$tmpfile = tempnam(sys_get_temp_dir(), 'nidbsyntax');
		if ($tmpfile === false) {
			http_response_code(500);
			echo json_encode(array('error' => 'Unable to create a temporary file to check the script'));
			return;
		}
		file_put_contents($tmpfile, "#!/bin/bash\n" . $cleanscript . "\n");

		/* a checker can report a problem at end-of-file, one line past the script. Ace cannot show an
		   annotation there, so clamp everything to the last line of the editor */
		$lastrow = max(0, substr_count(rtrim($script, "\r\n"), "\n"));

		/* 'timeout' guards against a runaway checker, but is not required */
		$timeout = "";
		exec("command -v timeout 2>/dev/null", $timeoutpath, $rc);
		if (($rc == 0) && (count($timeoutpath) > 0)) { $timeout = escapeshellarg(trim($timeoutpath[0])) . " 20 "; }

		$annotations = array();
		$error = "";
		$shellcheckpath = array();
		exec("command -v shellcheck 2>/dev/null", $shellcheckpath, $rc);
		$shellcheck = (($rc == 0) && (count($shellcheckpath) > 0)) ? trim($shellcheckpath[0]) : "";

		if ($shellcheck != "") {
			$checker = "shellcheck";
			$version = array();
			exec(escapeshellarg($shellcheck) . " --version 2>/dev/null | grep version:", $version);
			if (count($version) > 0) { $checker .= " " . trim(str_replace("version:", "", $version[0])); }

			/* -S info keeps the checks worth having (SC2086 unquoted variables, SC2071 [ a > b ]) while
			   dropping pure style nags. The excluded codes are ones that are expected in pipeline scripts:
			   SC1090/SC1091 can't follow sourced files, SC2154 variable referenced but not assigned (they
			   come from earlier steps or the environment), SC2012/SC2035 prefer find over ls. Edit this
			   list to make the check stricter or quieter */
			$output = array();
			exec($timeout . escapeshellarg($shellcheck) . " -s bash -S info -e SC1090,SC1091,SC2154,SC2012,SC2035 -f json " . escapeshellarg($tmpfile) . " 2>&1", $output, $rc);

			/* shellcheck exits 0 with no issues and 1 with issues. Anything else did not run properly */
			$found = json_decode(implode("\n", $output), true);
			if (!is_array($found)) {
				$error = "shellcheck did not return a result (exit code $rc)";
				if (count($output) > 0) { $error .= ": " . $output[0]; }
			}
			else {
				foreach ($found as $f) {
					$annotations[] = array(
						'row'    => min($lastrow, max(0, ((int)$f['line']) - 2)),
						'column' => max(0, ((int)$f['column']) - 1),
						'type'   => (($f['level'] == 'error') ? 'error' : (($f['level'] == 'warning') ? 'warning' : 'info')),
						'text'   => "SC" . $f['code'] . " (" . $f['level'] . "): " . $f['message'],
					);
				}
			}
		}
		else {
			/* syntax errors only. Output looks like: /tmp/nidbsyntaxXXX: line 4: syntax error near ... */
			$checker = "bash -n";
			$output = array();
			exec($timeout . "bash -n " . escapeshellarg($tmpfile) . " 2>&1", $output, $rc);
			if ($rc == 127) {
				$error = "Neither shellcheck nor bash could be run on this server";
			}
			foreach ($output as $line) {
				if (preg_match('/line (\d+):\s*(.*)$/', $line, $m)) {
					$annotations[] = array(
						'row'    => min($lastrow, max(0, ((int)$m[1]) - 2)),
						'column' => 0,
						'type'   => 'error',
						'text'   => trim($m[2]),
					);
				}
			}
			/* bash reported a problem in a format we did not recognize - show it rather than saying the script is clean */
			if (($rc != 0) && ($rc != 127) && (count($annotations) == 0)) {
				$error = "bash -n failed (exit code $rc)";
				if (count($output) > 0) { $error .= ": " . $output[0]; }
			}
		}

		unlink($tmpfile);

		if ($error != "") {
			http_response_code(500);
			echo json_encode(array('error' => $error, 'checker' => $checker));
			return;
		}

		echo json_encode(array('checker' => $checker, 'annotations' => $annotations));
	}


	/* -------------------------------------------- */
	/* ------- SetPipelineFavorite ---------------- */
	/* -------------------------------------------- */
	/* add (favorite=1) or remove (favorite=0) a pipeline from the current user's favorites. Returns JSON */
	function SetPipelineFavorite($pipelineid, $favorite) {
		JsonHeader();

		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			http_response_code(405);
			echo json_encode(array('error' => 'POST required'));
			return;
		}

		$pipelineid = (int)$pipelineid;
		$favorite = ($favorite == 1);

		/* only allow favoriting pipelines that exist */
		$sqlstring = "select pipeline_id from pipelines where pipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $pipelineid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$pipelineid]);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if (!$exists) {
			http_response_code(404);
			echo json_encode(array('error' => 'Pipeline not found'));
			return;
		}

		if (!SetUserFavorite('pipeline', $pipelineid, $favorite)) {
			http_response_code(403);
			echo json_encode(array('error' => 'Not logged in'));
			return;
		}

		echo json_encode(array('pipelineid' => $pipelineid, 'favorite' => ($favorite ? 1 : 0)));
	}


	/* -------------------------------------------- */
	/* ------- SearchICD10 ------------------------ */
	/* -------------------------------------------- */
	function SearchICD10($term) {
		JsonHeader();

		$term = trim($term);
		if ($term == '') {
			echo json_encode(array());
			return;
		}

		$search = '%' . $term . '%';
		$results = array();

		$stmt = mysqli_prepare($GLOBALS['linki'], "select icd10_id, icd10_code, icd10_longdesc from icd10 where icd10_code like ? or icd10_longdesc like ? order by icd10_code limit 50");
		mysqli_stmt_bind_param($stmt, 'ss', $search, $search);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$results[] = array(
				'label' => $row['icd10_code'] . ' - ' . $row['icd10_longdesc'],
				'value' => $row['icd10_code'] . ' - ' . $row['icd10_longdesc'],
				'icd10_id' => $row['icd10_id'],
				'code' => $row['icd10_code'],
				'longdesc' => $row['icd10_longdesc']
			);
		}
		mysqli_stmt_close($stmt);

		echo json_encode($results);
	}


	/* -------------------------------------------- */
	/* ------- SearchObservationNames ------------- */
	/* -------------------------------------------- */
	function SearchObservationNames($term, $instrumentname) {
		JsonHeader();

		$term = trim($term);
		if ($term == '') {
			echo json_encode([]);
			return;
		}

		$search = '%' . $term . '%';
		$results = array();

		$stmt = mysqli_prepare($GLOBALS['linki'], "select distinct observation_name, if(observation_instrument = ?, 0, 1) as priority from observations where observation_name like ? order by priority, observation_name limit 50");
		mysqli_stmt_bind_param($stmt, 'ss', $instrumentname, $search);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$results[] = [
				'label' => $row['observation_name'],
				'value' => $row['observation_name'],
			];
		}
		mysqli_stmt_close($stmt);

		echo json_encode($results);
	}


	/* -------------------------------------------- */
	/* ------- SearchSubject ---------------------- */
	/* -------------------------------------------- */
	function SearchSubject($searchuid) {
		$searchuid = trim($searchuid);

		$search = '%' . $searchuid . '%';
		$stmt = mysqli_prepare($GLOBALS['linki'], "select uid, subject_id, gender, year(birthdate) 'dobyear' from subjects where uid like ?");
		mysqli_stmt_bind_param($stmt, 's', $search);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$uid = $row['uid'];
			$id = $row['subject_id'];
			$gender = $row['gender'];
			$dobyear = $row['dobyear'];
			
			$age = date("Y") - $dobyear;
			
			$u['title'] = $uid;
			$u['url'] = "subjects.php?subjectid=$id";
			$u['description'] = "$gender - $age" . "yr";
			
			$a['results'][] = $u;
		}
		mysqli_stmt_close($stmt);

		echo json_encode($a, JSON_FORCE_OBJECT);
	}


	/* -------------------------------------------- */
	/* ------- CheckSGESubmitStatus --------------- */
	/* -------------------------------------------- */
	function CheckSGESubmitStatus($hostname, $clustertype, $submithostuser) {
		
		$hostname = trim($hostname);
		$hostname = preg_replace("/[^A-Za-z0-9 ]/", '', $hostname);
		$clustertype = trim($clustertype);
		$clustertype = preg_replace("/[^A-Za-z0-9 ]/", '', $clustertype);
		$submithostuser = trim($submithostuser);
		$submithostuser = preg_replace("/[^A-Za-z0-9 ]/", '', $submithostuser);

		if ($hostname == "") {
			echo "Hostname is blank";
			return false;
		}
		if ($clustertype == "") {
			echo "Cluster type is blank";
			return false;
		}
		if ($submithostuser == "") {
			echo "Submit host username is blank";
			return false;
		}
		
		if ($clustertype == "slurm") {
			exec("ssh $submithostuser@'$hostname' which sbatch", $output, $result);
			$clustercommand = "sbatch";
		}
		else {
			exec("ssh $submithostuser@'$hostname' which qsub", $output, $result);
			$clustercommand = "qsub";
		}
		
		if ($result == 0) {
			/* success */
			echo "1";
			print_r($output);
		}
		else {
			/* error */
			//echo "0";

			exec("ping -c 1 '$hostname'", $output, $result);
			
			if ($result != 0)
				echo "Host [$hostname] is not reachable";
			else
				echo "Cannot ssh, or cannot find $clustercommand";
		}
	}

	
	/* -------------------------------------------- */
	/* ------- ValidatePath ----------------------- */
	/* -------------------------------------------- */
	function ValidatePath($nfspath) {
		$p = trim($nfspath);

		$mdir = $GLOBALS['cfg']['mountdir'];

		$exists = 0;
		$writeable = 0;
		$msg = "";
		
		/* check for invalid paths before checking the drive to see if they exist */
		if ((strpos($p, "..") !== false) || (strpos($p, ".") !== false)) {
			$msg = "Contains relative directory (.. or .)";
		}
		else if (strpos($p, '\\') !== false) {
			$msg = "Contains backslash";
		}
		else if ($p == "/") {
			$msg = "Cannot be the root dir";
		}
		else if (substr($p,0,1) != "/") {
			$msg = "Must begin with slash";
		}
		else if ($p == "") {
			$msg = "Pathname is blank";
		}

		/* check if it exists and is writeable */
		else if (file_exists("$mdir/$p")) {
			$exists = 1;
			if (is_writable("$mdir/$p")) {
				$writeable = 1;
				$msg = "Path exists and is writeable";
			}
			else {
				$msg = "Path exists, but is not writeable";
			}
		}
		else {
			$msg = "Path does not exist";
		}
		if ($exists && $writeable) { $icon = "green check circle"; } else { $icon = "red exclamation circle"; }
		echo " <div class='ui left pointing label'><i class='$icon icon'></i> $msg</div>";
	}


	/* -------------------------------------------- */
	/* ------- CheckUsername ---------------------- */
	/* -------------------------------------------- */
	function CheckUsername($username) {
		$username = trim($username);

		$msg = "";

		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from users where username = ?");
		mysqli_stmt_bind_param($stmt, 's', $username);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (mysqli_num_rows($result) > 0) {
			echo " <div class='ui pointing label'><i class='red exclamation circle icon'></i> Username exists</div>";
		}
		else {
			echo " <div class='ui pointing label'><i class='green check circle icon'></i> Username available</div>";
		}
		
	}


	/* -------------------------------------------- */
	/* ------- RemoteExportStatus ----------------- */
	/* -------------------------------------------- */
	function RemoteExportStatus($connectionid, $transactionid, $detail=0, $total) {
		?><link rel="stylesheet" type="text/css" href="style.css"><?

		if (($connectionid == "") || (!IsInteger($connectionid))) { return; }
		if (($transactionid == "") || (!IsInteger($transactionid))) { return; }
		
		$sqlstring = "select * from remote_connections where remoteconn_id = $connectionid";
		$result = MySQLiQuery($sqlstring, __FILE__ , __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$remotenidbserver = $row['remote_server'];
		$remotenidbusername = $row['remote_username'];
		$remotenidbpassword = $row['remote_password'];
		$remoteinstanceid = $row['remote_instanceid'];
		$remoteprojectid = $row['remote_projectid'];
		$remotesiteid = $row['remote_siteid'];

		if ($detail) {
			$systemstring = "curl -gs -F 'action=getTransactionStatus' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$transactionid' -F 'instanceid=$remoteinstanceid' -F 'projectid=$remoteprojectid' -F 'siteid=$remotesiteid' $remotenidbserver/api.php";
			$report1 = json_decode(shell_exec($systemstring), true);

			?>
			<style>
				table, th, td {
					border: 1px solid #888;
					border-collapse: collapse;
				}
			</style>
			
			<div align="center">Block receipt status</div>
			<table class="ui very compact celled grey table">
				<thead>
					<th align="left">Block</th>
					<th align="left">Start</th>
					<th align="left">End</th>
					<th align="left">Status</th>
					<th align="left">Message</th>
				</thead>
			<?
			foreach ($report1 as $block => $info) {
				?>
				<tr>
					<td><?=$block?></td>
					<td><?=$info['import_startdate']?></td>
					<td><?=$info['import_enddate']?></td>
					<td><?=$info['import_status']?></td>
					<td><?=$info['import_message']?></td>
				</tr>
				<?
			}
			?>
			</table>
			<?

			$systemstring = "curl -gs -F 'action=getArchiveStatus' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$transactionid' $remotenidbserver/api.php";
			$report2 = json_decode(shell_exec($systemstring), true);
			?>
			<br>
			<div align="center">Archiving status</div>
			<table class="ui table" width="100%">
				<thead>
					<th align="left">Original ID</th>
					<th align="left">New UID/Study</th>
					<th align="left">Study datetime</th>
					<th align="left">Modality</th>
					<th align="left">Equipment</th>
					<th align="left">Protocol</th>
					<th align="left"># files</th>
				</thead>
			<?
			foreach ($report2 as $block => $info) {
				$status = $info['result'];
				$patientid_orig = $info['patientid_orig'];
				$studydatetime_orig = $info['studydatetime_orig'];
				$modality_orig = $info['modality_orig'];
				$stationname_orig = $info['stationname_orig'];
				$seriesdesc_orig = $info['seriesdesc_orig'];
				$subject_uid = $info['subject_uid'];
				$study_num = $info['study_num'];
				$numfiles = $info['numfiles'];
				?>
				<tr>
					<td><?=$patientid_orig?></td>
					<td><?="$subject_uid/$study_num"?></td>
					<td><?=$studydatetime_orig?></td>
					<td><?=$modality_orig?></td>
					<td><?=$stationname_orig?></td>
					<td><?=$seriesdesc_orig?></td>
					<td><?=$numfiles?></td>
				</tr>
				<?
			}
			?>
			</table>
			<?
		}
		else {
			$systemstring = "curl -gs -F 'action=getTransactionStatus' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$transactionid' $remotenidbserver/api.php";
			$report = json_decode(shell_exec($systemstring), true);

			$numtotal = 0;
			$numsuccess = 0;
			$numfail = 0;
			$numprocessing = 0;
			$archivesuccess = 0;
			$archiveerror = 0;
			foreach ($report as $block => $info) {
				$numtotal += $info['numfilestotal'];
				$numsuccess += $info['numfilessuccess'];
				$numfail += $info['numfilesfail'];
				$numprocessing += $numtotal - $numsuccess - $numfail;
				
				if ($info['import_status'] == 'archived')
					$archivesuccess++;
				elseif ($info['import_status'] == 'error')
					$archiveerror++;
			}
			$completecolor = "66AAFF";
			$processingcolor = "AAAAFF";
			$errorcolor = "FF6666";
			$othercolor = "EFEFFF";

			?>
			<span style="font-size: 11pt">
			<img src="ajaxapi.php?action=horizontalchart&b=yes&w=400&h=15&v=<?=$numsuccess?>,<?=$numprocessing?>,<?=$numfail?>,<?=($total-$numtotal)?>&c=<?=$completecolor?>,<?=$processingcolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($numsuccess/$total)*100,1)?>% received <span style="font-size:8pt;color:gray">(<?=number_format($numsuccess)?> of <?=number_format($total)?> blocks)</span>
			<br>
			<img src="ajaxapi.php?action=horizontalchart&b=yes&w=400&h=15&v=<?=$archivesuccess?>,<?=$archiveerror?>,<?=($total-$archivesuccess-$archiveerror)?>&c=<?=$completecolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($archivesuccess/$total)*100,1)?>% archived <span style="font-size:8pt;color:gray">(<?=number_format($archivesuccess)?> of <?=number_format($total)?> blocks)</span>
			</span>
			<?
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySGEJobStatus ---------------- */
	/* -------------------------------------------- */
	/*
	function DisplaySGEJobStatus($jobid) {
		if (($jobid == "") || (!IsInteger($jobid))) { return; }
		?><body style="margin: 0px: padding: 0px; overflow:hidden;"><?
		$systemstring = "ssh " . $GLOBALS['cfg']['clustersubmithost'] . " qstat -j $analysis_qsubid";
		$out = shell_exec($systemstring);
		if (trim($out) == "") {
			?><img src="images/alert.png" title="Analysis is marked as running, but the cluster job is not.<br><br>This most likely means the cluster job has failed and was not able to update the status on NiDB. Check log files for the error"><?
		}
		?></body><?
	}
	*/
	
	
	/* -------------------------------------------- */
	/* ------- PipelineTestSearch ----------------- */
	/* -------------------------------------------- */
	/* Estimates which studies a (level 1) pipeline would analyze, using the unsaved values from the
	   pipeline form. Mirrors modulePipeline::GetStudyToDoList() (steps A1-A4, B1, B2) and the
	   data-check phase of modulePipeline::GetData() in the C++ backend, including its quirks, so
	   keep them in sync. Data steps are posted as JSON in 'datasteps' */
	function PipelineTestSearch($s) {

		set_time_limit(30);
		$starttime = microtime(true);

		$pipelineid = (int)trim($s['pipelineid'] ?? '');
		$deplevel = trim($s['deplevel'] ?? '');
		$depids = TestSearchIntList($s['dependency'] ?? '');
		$groupids = TestSearchIntList($s['groupid'] ?? '');
		$projectids = TestSearchIntList($s['projectid'] ?? '');
		$steps = json_decode($s['datasteps'] ?? '', true);
		if (!is_array($steps))
			$steps = array();

		/* the backend only uses the first dependency */
		$pipelinedep = (count($depids) > 0) ? $depids[0] : -1;

		/* primary modality is the first step's modality, unless a step is marked primary */
		$modality = "";
		if (count($steps) > 0)
			$modality = trim($steps[0]['modality'] ?? '');
		foreach ($steps as $step) {
			if (!empty($step['primary'])) {
				$modality = trim($step['modality'] ?? '');
				break;
			}
		}

		?>
		<style>
			.underlined { text-decoration: underline; text-decoration-style: dashed; text-decoration-color: #888; }
		</style>
		<table class="ui very compact table">
			<thead>
				<tr>
					<th>Criteria<br><span class="tiny">Mouseover for description</span></th>
					<th>Value</th>
					<th>Matches</th>
					<th>Remaining</th>
				</tr>
			</thead>
			<tbody>
		<?

		if ($modality == "") {
			TestSearchRow("Primary modality", "The primary data step (or the first data step) must have a modality", "Blank", "-", "-", "error");
			TestSearchEnd($starttime);
			return;
		}

		/* ---------- existing analyses (informational) ---------- */
		$sqlstring = "select analysis_status, count(distinct study_id) 'count' from analysis where pipeline_id = ? group by analysis_status order by analysis_status";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $pipelineid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($pipelineid));
		$existing = array();
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$existing[$row['analysis_status']] = $row['count'];
		mysqli_stmt_close($stmt);
		TestSearchRow("Existing analyses", "Studies that already have an analysis for this pipeline (any status). These are not searched again", "", array_sum($existing), "-", "bold");
		foreach ($existing as $status => $count)
			TestSearchRow($status, "", "", $count, "-", "indent");

		/* ---------- A1 - valid studies of the primary modality without an existing analysis ---------- */
		$sqlstring = "select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id not in (select study_id from analysis where pipeline_id = ?) and (a.study_datetime < date_sub(now(), interval 6 hour)) and a.study_modality = ? and c.isactive = 1";
		$todo = TestSearchIDs($sqlstring, 'is', array($pipelineid, $modality), 'study_id');
		TestSearchRow("A1 - Valid $modality studies", "Studies of the primary modality, for active subjects, collected more than 6 hours ago, and without an existing analysis for this pipeline", "", count($todo), count($todo));

		/* ---------- A2 - parent dependency ---------- */
		if ($pipelinedep == -1) {
			TestSearchRow("A2 - Dependency", "Studies with a complete, not-bad analysis from the parent pipeline", "No dependency", "-", count($todo), "gray");
		}
		else {
			if ($deplevel == "subject") {
				/* all studies of subjects who have at least one completed parent analysis */
				$sqlstring = "select distinct s.study_id from studies s left join enrollment e on s.enrollment_id = e.enrollment_id where e.subject_id in (select c.subject_id from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.pipeline_id = ? and a.analysis_status = 'complete' and (a.analysis_isbad <> 1 or a.analysis_isbad is null))";
			}
			else {
				$sqlstring = "select distinct a.study_id from analysis a where a.pipeline_id = ? and a.analysis_status = 'complete' and (a.analysis_isbad <> 1 or a.analysis_isbad is null)";
			}
			$depstudies = TestSearchIDs($sqlstring, 'i', array($pipelinedep), 'study_id');
			$todo = TestSearchIntersect($todo, $depstudies);

			$depname = TestSearchValue("select pipeline_name from pipelines where pipeline_id = ?", 'i', array($pipelinedep), 'pipeline_name');
			$desc = ($deplevel == "subject") ? "All studies of subjects with at least one complete, not-bad analysis from the parent pipeline" : "Studies with a complete, not-bad analysis from the parent pipeline";
			if (count($depids) > 1)
				$desc .= ". Only the first dependency is used";
			TestSearchRow("A2 - Dependency ($deplevel level)", $desc, $depname, count($depstudies), count($todo));
		}

		/* ---------- A3 - groups ---------- */
		if (count($groupids) == 0) {
			TestSearchRow("A3 - Groups", "Studies in the selected group(s)", "No groups", "-", count($todo), "gray");
		}
		else {
			$place = implode(',', array_fill(0, count($groupids), '?'));
			$groupstudies = TestSearchIDs("select data_id from group_data where group_id in ($place)", str_repeat('i', count($groupids)), $groupids, 'data_id');
			$todo = TestSearchIntersect($todo, $groupstudies);
			$names = TestSearchColumn("select group_name from groups where group_id in ($place) order by group_name", str_repeat('i', count($groupids)), $groupids, 'group_name');
			TestSearchRow("A3 - Groups", "Studies in the selected group(s)", implode(", ", $names), count($groupstudies), count($todo));
		}

		/* ---------- A4 - projects ---------- */
		if (count($projectids) == 0) {
			TestSearchRow("A4 - Projects", "Studies of the primary modality in the selected project(s)", "No projects", "-", count($todo), "gray");
		}
		else {
			$place = implode(',', array_fill(0, count($projectids), '?'));
			$projectstudies = TestSearchIDs("select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join projects c on b.project_id = c.project_id where c.project_id in ($place) and a.study_modality = ?", str_repeat('i', count($projectids)) . 's', array_merge($projectids, array($modality)), 'study_id');
			$todo = TestSearchIntersect($todo, $projectstudies);
			$names = TestSearchColumn("select project_name from projects where project_id in ($place) order by project_name", str_repeat('i', count($projectids)), $projectids, 'project_name');
			TestSearchRow("A4 - Projects", "Studies of the primary modality in the selected project(s)", implode(", ", $names), count($projectstudies), count($todo));
		}

		TestSearchRow("Studies to check for data", "Studies remaining after steps A1-A4. Each is checked for the required data", "", count($todo), count($todo), "bold");

		/* ---------- data steps (GetData() check phase) ---------- */
		/* subject and study type of each remaining study */
		$studyinfo = array();
		foreach (array_chunk($todo, 1000) as $chunk) {
			$result = MySQLiQuery("select a.study_id, b.subject_id, a.study_type from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where a.study_id in (" . implode(',', array_map('intval', $chunk)) . ")", __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
				$studyinfo[(int)$row['study_id']] = array('subjectid' => (int)$row['subject_id'], 'studytype' => (string)$row['study_type']);
		}

		$passing = $todo;
		foreach ($steps as $i => $step) {
			$num = $step['num'] ?? ($i + 1);
			$protocol = trim($step['protocol'] ?? '');
			$stepmodality = strtolower(trim($step['modality'] ?? ''));
			$level = trim($step['datalevel'] ?? '');
			$assoctype = trim($step['studyassoc'] ?? '');
			$imagetype = trim($step['imagetype'] ?? '');
			$label = "Step $num - $protocol";

			if (empty($step['enabled'])) {
				TestSearchRow($label, "Step is not enabled", "Disabled - skipped", "-", count($passing), "gray");
				continue;
			}
			if (!empty($step['optional'])) {
				TestSearchRow($label, "Optional steps are not required to exist", "Optional - skipped", "-", count($passing), "gray");
				continue;
			}

			/* the modality's series table must exist. Otherwise the step is invalid and checking stops */
			$seriestable = GetSeriesTableName($stepmodality);
			$tableexists = false;
			if ($seriestable !== '')
				$tableexists = (TestSearchValue("select table_name from information_schema.tables where table_schema = database() and table_name = ?", 's', array($seriestable), 'table_name') !== null);
			if (!$tableexists) {
				$passing = array();
				TestSearchRow($label, "The modality's series table does not exist. The data check stops at this step", "Invalid modality [$stepmodality]", 0, 0, "error");
				break;
			}

			$seriesdescfield = ($stepmodality == "mr") ? "series_desc" : "series_protocol";

			/* protocols - a quoted list, or the whole string as one protocol */
			$protocols = array($protocol);
			if (strpos($protocol, '"') !== false) {
				try { $protocols = ShellWords($protocol); }
				catch (Exception $e) { $protocols = array($protocol); }
			}
			if (count($protocols) == 0)
				$protocols = array($protocol);
			$protplace = implode(',', array_fill(0, count($protocols), '?'));

			/* image types - comma separated list */
			if (strpos($imagetype, ',') !== false)
				$imagetypes = preg_split('/,\s*/', $imagetype);
			elseif ($imagetype != "")
				$imagetypes = array($imagetype);
			else
				$imagetypes = array();

			$matched = array();
			if ($level == "subject") {
				/* nearest-in-time and entire-subject only need a match anywhere in the subject; otherwise the study type must also match */
				$bytype = !in_array($assoctype, array("nearesttime", "nearestintime", "all", "entiresubject"));
				$subjectids = array();
				foreach ($passing as $studyid)
					$subjectids[$studyinfo[$studyid]['subjectid'] ?? 0] = true;
				$subjectids = array_keys($subjectids);

				$found = array();
				foreach (array_chunk($subjectids, 1000) as $chunk) {
					$sqlstring = "select distinct c.subject_id, d.study_type from enrollment a join projects b on a.project_id = b.project_id join subjects c on c.subject_id = a.subject_id join studies d on d.enrollment_id = a.enrollment_id join `$seriestable` e on e.study_id = d.study_id where c.isactive = 1 and d.study_modality = ? and c.subject_id in (" . implode(',', array_map('intval', $chunk)) . ") and trim(e.$seriesdescfield) in ($protplace)";
					$types = 's' . str_repeat('s', count($protocols));
					$params = array_merge(array($stepmodality), $protocols);
					if (count($imagetypes) > 0) {
						$sqlstring .= " and e.image_type in (" . implode(',', array_fill(0, count($imagetypes), '?')) . ")";
						$types .= str_repeat('s', count($imagetypes));
						$params = array_merge($params, $imagetypes);
					}
					foreach (TestSearchRows($sqlstring, $types, $params) as $row) {
						if ($bytype)
							$found[$row['subject_id'] . "|" . TestSearchNormalize($row['study_type'])] = true;
						else
							$found[$row['subject_id']] = true;
					}
				}
				foreach ($passing as $studyid) {
					$subjectid = $studyinfo[$studyid]['subjectid'] ?? 0;
					$key = $bytype ? $subjectid . "|" . TestSearchNormalize($studyinfo[$studyid]['studytype'] ?? '') : $subjectid;
					if (isset($found[$key]))
						$matched[] = $studyid;
				}
				$desc = $bytype ? "Subject has matching data in a study with the same study type" : "Subject has matching data in any study";
			}
			else {
				$comparison = TestSearchComparison($step['numboldreps'] ?? '');
				foreach (array_chunk($passing, 1000) as $chunk) {
					$sqlstring = "select distinct study_id from `$seriestable` where study_id in (" . implode(',', array_map('intval', $chunk)) . ") and trim($seriesdescfield) in ($protplace)";
					$types = str_repeat('s', count($protocols));
					$params = $protocols;
					if (count($imagetypes) > 0) {
						$sqlstring .= " and image_type in (" . implode(',', array_fill(0, count($imagetypes), '?')) . ")";
						$types .= str_repeat('s', count($imagetypes));
						$params = array_merge($params, $imagetypes);
					}
					if ($comparison !== null) {
						/* operator is whitelisted by TestSearchComparison() */
						$sqlstring .= " and ((numfiles " . $comparison[0] . " ?) or (dimT " . $comparison[0] . " ?))";
						$types .= 'ii';
						$params[] = $comparison[1];
						$params[] = $comparison[1];
					}
					foreach (TestSearchRows($sqlstring, $types, $params) as $row)
						$matched[] = (int)$row['study_id'];
				}
				$desc = "Study contains matching data" . (($comparison !== null) ? " with numfiles or dimT " . $comparison[0] . " " . $comparison[1] : "");
			}

			$passing = $matched;
			$value = ($level == "subject") ? "subject level" : "study level";
			TestSearchRow($label, "$desc. Studies without it are not checked further", "$stepmodality, $value", count($matched), count($passing));
		}

		/* dependency rules applied after the data check */
		if ($deplevel == "subject") {
			if (($pipelinedep != -1) && (count($passing) < count($todo)))
				TestSearchRow("Subject-level dependency", "With a subject-level dependency, studies missing required data are not analyzed", "", "-", count($passing), "indent");
		}
		elseif ($pipelinedep != -1) {
			if (count($passing) < count($todo))
				TestSearchRow("Dependency override", "When the pipeline has a (study-level) dependency, studies missing required data are still analyzed", "", "-", count($todo), "indent");
			$passing = $todo;
		}

		TestSearchRow("New analyses", "Studies that pass all checks and would get a new analysis", "", count($passing), count($passing), "bold");

		/* ---------- B1, B2 - reruns and supplements ---------- */
		$numrerun = (int)TestSearchValue("select count(*) 'count' from studies where study_id in (select study_id from analysis where pipeline_id = ? and analysis_rerunresults = 1 and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null))", 'i', array($pipelineid), 'count');
		$numsupplement = (int)TestSearchValue("select count(*) 'count' from studies where study_id in (select study_id from analysis where pipeline_id = ? and analysis_runsupplement = 1 and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null))", 'i', array($pipelineid), 'count');
		TestSearchRow("B1 - Rerun results", "Existing complete analyses flagged to have their results re-run (uses the saved pipeline)", "", $numrerun, "-", "indent");
		TestSearchRow("B2 - Run supplement", "Existing complete analyses flagged to run supplement commands (uses the saved pipeline)", "", $numsupplement, "-", "indent");

		$total = count($passing) + $numrerun + $numsupplement;
		TestSearchRow("Total studies to be analyzed", "New analyses + reruns + supplements", "", $total, $total, "bold");

		/* sample list of new analyses */
		if (count($passing) > 0) {
			$sample = array_slice($passing, 0, 50);
			$result = MySQLiQuery("select concat(c.uid, a.study_num) 'uidstudynum' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id in (" . implode(',', array_map('intval', $sample)) . ") order by c.uid, a.study_num", __FILE__, __LINE__);
			$uids = array();
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
				$uids[] = $row['uidstudynum'];
			?>
			<tr>
				<td colspan="4" class="tiny"><b><?=(count($passing) > 50) ? "First 50 new analyses" : "New analyses"?>:</b> <?=htmlspecialchars(implode(", ", $uids))?></td>
			</tr>
			<?
		}

		TestSearchEnd($starttime);
	}


	/* -------------------------------------------- */
	/* ------- TestSearchEnd ---------------------- */
	/* -------------------------------------------- */
	function TestSearchEnd($starttime) {
		?>
			</tbody>
		</table>
		<span class="tiny">Search took <?=number_format(microtime(true) - $starttime, 2)?> sec</span>
		<?
	}


	/* -------------------------------------------- */
	/* ------- TestSearchRow ---------------------- */
	/* -------------------------------------------- */
	/* style: "", bold, indent, gray, error */
	function TestSearchRow($criteria, $description, $value, $nummatch, $remaining, $style="") {
		if (is_numeric($nummatch)) $nummatch = number_format((float)$nummatch, 0);
		if (is_numeric($remaining)) $remaining = number_format((float)$remaining, 0);

		$rowstyle = ($style == "bold") ? "font-weight: bold;" : "";
		$cellstyle = "";
		if ($style == "indent") $cellstyle = "color: #777; font-size: 9pt;";
		if ($style == "gray") $cellstyle = "color: #999;";
		if ($style == "error") $cellstyle = "color: darkred;";
		$indent = ($style == "indent") ? "padding-left: 20px;" : "";
		$title = ($description != "") ? 'title="' . htmlspecialchars($description) . '" class="underlined"' : "";
		?>
		<tr style="<?=$rowstyle?>">
			<td style="<?=$indent?> <?=$cellstyle?>"><span <?=$title?>><?=htmlspecialchars($criteria)?></span></td>
			<td style="<?=$cellstyle?>"><?=htmlspecialchars($value)?></td>
			<td style="<?=$cellstyle?>"><?=htmlspecialchars($nummatch)?></td>
			<td style="<?=$cellstyle?>"><?=htmlspecialchars($remaining)?></td>
		</tr>
		<?
	}


	/* -------------------------------------------- */
	/* ------- TestSearchRows --------------------- */
	/* -------------------------------------------- */
	/* run a bound query and return all rows */
	function TestSearchRows($sqlstring, $types, $params) {
		$rows = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		if (($types != "") && ($stmt instanceof mysqli_stmt))
			mysqli_stmt_bind_param($stmt, $types, ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
			$rows[] = $row;
		if ($stmt instanceof mysqli_stmt)
			mysqli_stmt_close($stmt);
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- TestSearchColumn ------------------- */
	/* -------------------------------------------- */
	function TestSearchColumn($sqlstring, $types, $params, $column) {
		return array_map(function($row) use ($column) { return $row[$column]; }, TestSearchRows($sqlstring, $types, $params));
	}


	/* -------------------------------------------- */
	/* ------- TestSearchIDs ---------------------- */
	/* -------------------------------------------- */
	function TestSearchIDs($sqlstring, $types, $params, $column) {
		return array_values(array_unique(array_map('intval', TestSearchColumn($sqlstring, $types, $params, $column))));
	}


	/* -------------------------------------------- */
	/* ------- TestSearchValue -------------------- */
	/* -------------------------------------------- */
	/* first value of a column, or null if no rows */
	function TestSearchValue($sqlstring, $types, $params, $column) {
		$rows = TestSearchRows($sqlstring, $types, $params);
		return (count($rows) > 0) ? $rows[0][$column] : null;
	}


	/* -------------------------------------------- */
	/* ------- TestSearchIntersect ---------------- */
	/* -------------------------------------------- */
	function TestSearchIntersect($a, $b) {
		$lookup = array_flip($b);
		return array_values(array_filter($a, function($id) use ($lookup) { return isset($lookup[$id]); }));
	}


	/* -------------------------------------------- */
	/* ------- TestSearchIntList ------------------ */
	/* -------------------------------------------- */
	/* comma separated string (or array) to a list of positive ints, preserving order */
	function TestSearchIntList($list) {
		if (!is_array($list))
			$list = explode(',', (string)$list);
		return array_values(array_unique(array_filter(array_map('intval', $list), function($v) { return $v > 0; })));
	}


	/* -------------------------------------------- */
	/* ------- TestSearchNormalize ---------------- */
	/* -------------------------------------------- */
	/* approximates MySQL's case-insensitive, trailing-space-insensitive string comparison */
	function TestSearchNormalize($str) {
		return strtolower(rtrim((string)$str));
	}


	/* -------------------------------------------- */
	/* ------- TestSearchComparison --------------- */
	/* -------------------------------------------- */
	/* same rules as nidb::GetSQLComparison() in the backend. Returns [operator, number] or null if not valid */
	function TestSearchComparison($c) {
		$c = preg_replace('/\s+/', '', (string)$c);
		if (!preg_match('/^(<=|>=|<|>|~|=)?([+-]?\d+)$/', $c, $m))
			return null;
		$comp = ($m[1] == "") ? "=" : $m[1];
		if ($comp == "~")
			$comp = "<>";
		return array($comp, (int)$m[2]);
	}


	/* -------------------------------------------- */
	/* ------- UpdateSubjectDetails --------------- */
	/* -------------------------------------------- */
	function UpdateSubjectDetails($subjectid, $projectid, $column, $value) {

		$subjectid = (int)$subjectid;
		$projectid = (int)$projectid;
		$column = trim($column);          /* only ever matched against a whitelist below */
		$value = trim($value);            /* bound as a parameter below - do not pre-escape */

		if ($subjectid < 1) {
			echo "error, subjectID blank";
			return;
		}
		
		if ($column == "altuids") {
			StartSQLTransaction();
			/* get enrollmentid */
			$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectid and project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$enrollmentid = (int)$row['enrollment_id'];

			/* delete entries for this subject from the altuid table ... */
			$sqlstring = "delete from subject_altuid where subject_id = $subjectid and enrollment_id = $enrollmentid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			/* ... and insert the new rows into the altuids table */
			$altuidsublist = $value;
			//echo($altuidsublist);
			$altuids = explode(',',$altuidsublist);
			foreach ($altuids as $altuid) {
				$altuid = trim($altuid);
				if ($altuid != "") {
					$isprimary = 0;
					if (strpos($altuid, '*') !== FALSE) {
						$altuid = str_replace('*','',$altuid);
						$isprimary = 1;
					}
					$stmt = mysqli_prepare($GLOBALS['linki'], "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (?, ?, ?, ?)");
					mysqli_stmt_bind_param($stmt, 'isii', $subjectid, $altuid, $isprimary, $enrollmentid);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					mysqli_stmt_close($stmt);
				}
			}
			CommitSQLTransaction();
		}
		elseif ($column == "enrollgroup") {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update enrollment set enroll_subgroup = ? where project_id = ? and subject_id = ?");
			mysqli_stmt_bind_param($stmt, 'sii', $value, $projectid, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
		}
		elseif ($column == "enrollstatus") {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update enrollment set enroll_status = ? where project_id = ? and subject_id = ?");
			mysqli_stmt_bind_param($stmt, 'sii', $value, $projectid, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
		}
		else {
			$sqlstring = "update subjects set ";
			switch ($column) {
				case "uid": $sqlstring .= "uid"; break;
				case "guid": $sqlstring .= "guid"; break;
				case "sex": $sqlstring .= "subjects.sex"; break;
				case "gender": $sqlstring .= "gender"; break;
				case "dob": $sqlstring .= "birthdate"; break;
				case "ethnicity1": $sqlstring .= "ethnicity1"; break;
				case "ethnicity2": $sqlstring .= "ethnicity2"; break;
				case "handedness": $sqlstring .= "handedness"; break;
				case "education":
					switch ($value) {
						case "Unknown": $value = 0; break;
						case "Grade School": $value = 1; break;
						case "Middle School": $value = 2; break;
						case "High School/GED": $value = 3; break;
						case "Trade School": $value = 4; break;
						case "Associates Degree": $value = 5; break;
						case "Bachelors Degree": $value = 6; break;
						case "Masters Degree": $value = 7; break;
						case "Doctoral Degree": $value = 8; break;
						default: $value = "";
					}
					$sqlstring .= "education";
					break;
				case "marital": $sqlstring .= "marital_status"; break;
				case "smoking": $sqlstring .= "smoking_status"; break;
				case "enrollgroup": $sqlstring .= "enroll_subgroup"; break;
				default: echo "error - [$column] not recognized"; return;
			}
			$sqlstring .= " = ? where subject_id = ?";

			//echo "$sqlstring";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $value, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($value, $subjectid));
			mysqli_stmt_close($stmt);
		}

		echo "success";
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyDetails ----------------- */
	/* -------------------------------------------- */
	function UpdateStudyDetails($subjectid, $studyid, $column, $value) {

		$subjectid = (int)$subjectid;
		$studyid = (int)$studyid;
		$column = trim($column);          /* only ever matched against a whitelist below */
		$value = trim($value);            /* bound as a parameter below - do not pre-escape */

		if ($subjectid < 1) {
			echo "error, subjectid blank";
			return;
		}
		if ($studyid < 1) {
			echo "error, studyid blank";
			return;
		}
		
		if ($column == "altuids") {
			StartSQLTransaction();
			
			list($path2, $uid2, $studynum2, $studyid2, $subjectid2, $modality2, $type2, $studydatetime2, $enrollmentid, $projectname2, $projectid2) = GetStudyInfo($studyid);
			$enrollmentid = (int)$enrollmentid;

			/* delete entries for this subject from the altuid table ... */
			$sqlstring = "delete from subject_altuid where subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			/* ... and insert the new rows into the altuids table */
			$altuidsublist = $value;
			//echo($altuidsublist);
			$altuids = explode(',',$altuidsublist);
			foreach ($altuids as $altuid) {
				$altuid = trim($altuid);
				if ($altuid != "") {
					if ($enrollmentid == "") { $enrollmentid = 0; }
					//echo "enrollmentID [$enrollmentid] - altuid [$altuid]<br>";
					$isprimary = 0;
					if (strpos($altuid, '*') !== FALSE) {
						$altuid = str_replace('*','',$altuid);
						$isprimary = 1;
					}
					$stmt = mysqli_prepare($GLOBALS['linki'], "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (?, ?, ?, ?)");
					mysqli_stmt_bind_param($stmt, 'isii', $subjectid, $altuid, $isprimary, $enrollmentid);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__);
					mysqli_stmt_close($stmt);
				}
			}
			CommitSQLTransaction();
		}
		elseif ($column == "sex") {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update subjects set sex = ? where subject_id = ?");
			mysqli_stmt_bind_param($stmt, 'si', $value, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
		}
		elseif ($column == "gender") {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update subjects set gender = ? where subject_id = ?");
			mysqli_stmt_bind_param($stmt, 'si', $value, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
		}
		else {
			$sqlstring = "update studies set ";
			switch ($column) {
				case "visit": $sqlstring .= "study_type"; break;
				case "studydate": $sqlstring .= "study_datetime"; break;
				case "studyage": $sqlstring .= "study_ageatscan"; break;
				case "desc": $sqlstring .= "study_desc"; break;
				case "study_id": $sqlstring .= "study_alternateid"; break;
				case "site": $sqlstring .= "study_site"; break;
				default: echo "error - [$column] not recognized"; return;
			}
			$sqlstring .= " = ? where study_id = ?";

			//echo "$sqlstring";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'si', $value, $studyid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($value, $studyid));
			mysqli_stmt_close($stmt);
		}
		
		echo "success";
	}


	/* -------------------------------------------- */
	/* ------- UpdateObservationDetails ----------- */
	/* -------------------------------------------- */
	function UpdateObservationDetails($observationid, $column, $value, $tz_offset = '') {
		$observationid = (int)$observationid;
		if ($observationid < 1) { echo "error - invalid observation ID"; return; }

		$allowedColumns = [
			'name'          => 'observation_name',
			'value'         => 'observation_value',
			'rater'         => 'observation_rater',
			'startdate'     => 'observation_startdate',
			'enddate'       => 'observation_enddate',
			'duration'      => 'observation_duration',
			'obsInstrument' => 'observation_instrument',
		];

		if (!array_key_exists($column, $allowedColumns)) {
			echo "error - column [$column] not recognized";
			return;
		}

		$dbColumn = $allowedColumns[$column];
		$notNullColumns = ['name', 'value'];
		$intColumns = ['duration'];
		$dateColumns = ['startdate', 'enddate'];

		if (in_array($column, $intColumns)) {
			$castValue = (trim($value) === '') ? null : (int)$value;
			$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set $dbColumn = ? where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'ii', $castValue, $observationid);
		} elseif (in_array($column, $notNullColumns)) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set $dbColumn = ? where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'si', $value, $observationid);
		} elseif (in_array($column, $dateColumns)) {
			$nullableValue  = (trim($value) === '') ? null : $value;
			$nullableTzOff  = (trim($tz_offset) === '') ? null : $tz_offset;
			$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set $dbColumn = ?, observation_tz_offset = ? where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'ssi', $nullableValue, $nullableTzOff, $observationid);
		} else {
			$nullableValue = (trim($value) === '') ? null : $value;
			$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set $dbColumn = ? where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'si', $nullableValue, $observationid);
		}
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		echo "success";
	}


	/* -------------------------------------------- */
	/* ------- UpdateSeriesDetails ---------------- */
	/* -------------------------------------------- */
	/* inline edit of a single series field (folded in from the former series_inlineupdate.php) */
	function UpdateSeriesDetails($seriesid, $modality, $column, $value) {
		$seriesid = (int)$seriesid;
		if ($seriesid < 1) { echo "error - invalid series ID"; return; }

		/* validate modality -> table and primary-key identifiers (these cannot be bound as parameters) */
		$table = GetSeriesTableName($modality);
		if ($table === '') { echo "error - invalid modality"; return; }
		$pkColumn = strtolower($modality) . "series_id";

		/* only these columns may be edited inline */
		$allowedColumns = ['series_notes', 'series_protocol', 'series_datetime'];
		if (!in_array($column, $allowedColumns, true)) {
			echo "error - column [$column] not recognized";
			return;
		}

		$nullableValue = (trim($value) === '') ? null : $value;
		$stmt = mysqli_prepare($GLOBALS['linki'], "update $table set $column = ? where $pkColumn = ?");
		mysqli_stmt_bind_param($stmt, 'si', $nullableValue, $seriesid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		/* echo the value back so the in-place editor can display it */
		echo str_replace('\n', "<br>", ($nullableValue === null) ? ' ' : $value);
	}


	/* -------------------------------------------- */
	/* ------- CheckSeriesObject ------------------ */
	/* -------------------------------------------- */
	/* check whether a series' data files exist on disk (folded in from the former objectexists.php) */
	function CheckSeriesObject($seriesid, $modality, $datatype) {
		$seriesid = (int)$seriesid;
		if ($seriesid < 1) { return; }
		if (GetSeriesTableName($modality) === '') { return; }

		list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);

		if ($datatype == "dicom") {
			$files = glob("$path/*.dcm");
		}
		elseif ($datatype == "parrec") {
			$files = glob("$path/*.par");
		}
		else {
			return;
		}

		/* only report the problem case; when files are present the original emitted no visible output */
		if (empty($files) || !file_exists($files[0])) {
			echo '<i class="red exclamation circle icon" title="Files missing from disk"></i>';
		}
	}


	/* -------------------------------------------- */
	/* ------- GetSeriesThumbnail ----------------- */
	/* -------------------------------------------- */
	/* return the thumbnail preview link for a series (folded in from the former objectexists.php) */
	function GetSeriesThumbnail($seriesid, $modality) {
		$seriesid = (int)$seriesid;
		if ($seriesid < 1) { return; }
		if (GetSeriesTableName($modality) === '') { return; }

		list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
		$thumbpath = "$seriespath/thumb.png";
		if (file_exists($thumbpath)) {
			?><a href="preview.php?image=<?=$thumbpath?>" class="preview"><i class="photo video icon"></i></a><?
		}
	}


	/* -------------------------------------------- */
	/* ------- DownloadFile ----------------------- */
	/* -------------------------------------------- */
	function DownloadFile($fileid) {
		$fileid = (int)$fileid;
		if ($fileid < 1) { return; }

		list($filename, $filecontenttype, $fileblob, $filesize, $filedate) = GetFileFromSQL($fileid);
		if ($filename == "") { return; }

		/* discard any buffered output so the binary blob is not corrupted */
		while (ob_get_level() > 0) { ob_end_clean(); }

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: $filecontenttype");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: $filesize");

		echo $fileblob;
	}


	/* -------------------------------------------- */
	/* ------- HorizontalChart -------------------- */
	/* -------------------------------------------- */
	function HorizontalChart($w, $h, $v, $c, $b) {
		/* clamp dimensions to sane bounds to prevent memory-exhaustion DoS */
		$w = (int)$w;
		$h = (int)$h;
		if ($w < 1) { $w = 1; }
		if ($h < 1) { $h = 1; }
		if ($w > 2000) { $w = 2000; }
		if ($h > 2000) { $h = 2000; }

		/* create the canvas */
		$im = imagecreatetruecolor($w, $h);

		/* set background to white */
		$bg = imagecolorallocate($im, 255, 255, 255);
		imagefilledrectangle($im, 0, 0, $w, $h, $bg);

		/* get the pixel sizes of the blocks */
		$values = explode(',', $v);
		$colors = explode(',', $c);
		$sum = array_sum($values);

		if ($sum > 0) {
			$x1 = $x2 = 0;
			$y1 = 0;
			$y2 = $h;
			$i = 0;
			foreach ($values as $val) {
				list($red, $green, $blue) = HexToRGBArray($colors[$i]);
				$x1 = $x2;
				$x2 = $x1 + $w*($val/$sum);
				$color = imagecolorallocate($im, $red, $green, $blue);
				imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color);
				$i++;
			}
		}

		if ($b == "yes") {
			/* draw a gray border */
			$gray = imagecolorallocate($im, 120, 120, 120);
			imagepolygon($im, array(0,0, 0,$h-1, $w-1,$h-1, $w-1,0), 4, $gray);
		}

		/* discard any buffered output so the PNG is not corrupted */
		while (ob_get_level() > 0) { ob_end_clean(); }

		/* send the image to the browser */
		header('Content-type: image/png');
		imagepng($im);
		imagedestroy($im);
	}


	/* -------------------------------------------- */
	/* ------- HexToRGBArray ---------------------- */
	/* -------------------------------------------- */
	function HexToRGBArray($rgb) {
		/* convert 6 digit HEX string to 3 decimals */
		$rgb = str_replace("#", "", $rgb);
		return array(
			base_convert(substr($rgb, 0, 2), 16, 10),
			base_convert(substr($rgb, 2, 2), 16, 10),
			base_convert(substr($rgb, 4, 2), 16, 10),
		);
	}


	/* -------------------------------------------- */
	/* ------- StdDevChart ------------------------ */
	/* -------------------------------------------- */
	function StdDevChart($w, $h, $min, $max, $mean, $std, $ind, $b) {
		/* clamp dimensions to sane bounds to prevent memory-exhaustion DoS */
		$w = (int)$w;
		$h = (int)$h;
		if ($w < 1) { $w = 1; }
		if ($h < 1) { $h = 1; }
		if ($w > 2000) { $w = 2000; }
		if ($h > 2000) { $h = 2000; }

		/* create the canvas */
		$im = imagecreatetruecolor($w, $h);

		/* set background to white */
		$bg = imagecolorallocate($im, 255, 255, 255);
		imagefilledrectangle($im, 0, 0, $w, $h, $bg);

		/* draw the standard deviations */
		if (($max-$min) > 0 ) {
			$meanx = $w*(($mean-$min)/($max-$min)); /* calculate the mean line position, on which std devs are based */
		}
		else {
			$meanx = $w/2;
		}
		$x1 = $x2 = $meanx;
		$y1 = 0;
		$y2 = $h;
		foreach (array(4,3,2,1) as $i) {
			$x1 = $meanx - ($std*$i)/2;
			$x2 = $meanx + ($std*$i)/2;
			$color[$i] = imagecolorallocate($im, 255, 255-(255/$i), 255-(255/$i));
			imagefilledrectangle($im, $x1, $y1, $x2, $y2, $color[$i]);
		}
		if (($max-$min) > 0 ) {
			$meanx = $w*(($mean-$min)/($max-$min));
			imageline($im, $meanx, 0, $meanx, $h, $linecolor);
		}

		/* setup text color */
		$txtcolor = imagecolorallocate($im, 0, 0, 0);
		$linecolor = imagecolorallocate($im, 0, 0, 0);
		$txtheight = imagefontheight(1);

		/* draw a semi-transparent white box to put text into */
		$color = imagecolorallocatealpha($im, 255, 255, 255, 16);
		imagefilledrectangle($im, 0, $h-$txtheight-2, $w, $h, $color);

		/* draw min text */
		$str = number_format($min, 1);
		imagestring($im, 1, 1, $h-$txtheight-1, $str, $txtcolor);

		/* draw max text */
		$str = number_format($max, 1);
		$txtwidth = imagefontwidth(1)*strlen($str);
		imagestring($im, 1, $w-$txtwidth-1, $h-$txtheight-1, $str, $txtcolor);

		/* draw mean line and text */
		if (($max-$min) > 0 ) {
			$str = number_format($mean, 1);
			$txtwidth = imagefontwidth(1)*strlen($str);
			imagestring($im, 1, $meanx-$txtwidth/2, $h-$txtheight-1, $str, $txtcolor);
		}

		if ($ind != "") {
			if (($max-$min) > 0) {
				$indcolor = imagecolorallocate($im, 0, 0, 255);
				$indx = $w*(($ind-$min)/($max-$min));
				imageline($im, $indx, 0, $indx, $h, $indcolor);
			}
		}

		if ($b == "yes") {
			/* draw a gray border */
			$gray = imagecolorallocate($im, 120, 120, 120);
			imagepolygon($im, array(0,0, 0,$h-1, $w-1,$h-1, $w-1,0), 4, $gray);
		}

		/* discard any buffered output so the PNG is not corrupted */
		while (ob_get_level() > 0) { ob_end_clean(); }

		/* send the image to the browser */
		header('Content-type: image/png');
		imagepng($im);
		imagedestroy($im);
	}


	/* -------------------------------------------- */
	/* ------- GetObservationMeta ----------------- */
	/* -------------------------------------------- */
	function GetObservationMeta($observationid) {
		JsonHeader();
		$observationid = (int)$observationid;
		if ($observationid < 1) { echo json_encode(['error' => 'Invalid ID']); return; }
		$stmt = mysqli_prepare($GLOBALS['linki'], "select variable, value from observation_meta where observation_id = ? order by variable");
		mysqli_stmt_bind_param($stmt, 'i', $observationid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$rows = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = ['variable' => $row['variable'], 'value' => $row['value']];
		}
		mysqli_stmt_close($stmt);
		echo json_encode($rows);
	}


	/* -------------------------------------------- */
	/* ------- GetObservationTimeseries ----------- */
	/* -------------------------------------------- */
	/* Returns the timeseries points for one observation as [[epoch_ms, value], ...].
	   Supports zoom-to-load: pass tstart/tend (epoch milliseconds) to fetch just that
	   window at full resolution. Series with more than maxpoints rows are bucket-averaged
	   down to ~maxpoints so the browser never receives a 100k+ row dump. */
	function GetObservationTimeseries($observationid, $tstart, $tend, $maxpoints) {
		JsonHeader();
		$observationid = (int)$observationid;
		if ($observationid < 1) { echo json_encode(['error' => 'Invalid observation ID']); return; }

		$maxpoints = (int)$maxpoints;
		if ($maxpoints < 100)   $maxpoints = 2000;
		if ($maxpoints > 20000) $maxpoints = 20000;

		/* optional zoom-to-load window, passed as epoch milliseconds */
		$hasWindow   = (is_numeric($tstart) && is_numeric($tend) && (float)$tend > (float)$tstart);
		$winStartSec = $hasWindow ? (int)floor((float)$tstart / 1000) : 0;
		$winEndSec   = $hasWindow ? (int)ceil((float)$tend / 1000)    : 0;

		/* filter on the `time` column (not a wrapped expression) so partition pruning can kick in */
		$whereWindow = $hasWindow ? " and time between from_unixtime(?) and from_unixtime(?)" : "";

		/* summarize: point count, time range, and which typed column holds the values */
		$sql = "select count(*) n, min(unix_timestamp(time)) tmin, max(unix_timestamp(time)) tmax, sum(value_double is not null) nd, sum(value_int is not null) ni, sum(value_string is not null) ns from timeseries where observation_id = ?" . $whereWindow;
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		if ($hasWindow) mysqli_stmt_bind_param($stmt, 'iii', $observationid, $winStartSec, $winEndSec);
		else            mysqli_stmt_bind_param($stmt, 'i', $observationid);
		$res     = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$summary = mysqli_fetch_array($res, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		$n = (int)$summary['n'];
		if ($n < 1) { echo json_encode(['seriesType' => 'empty', 'count' => 0, 'points' => []]); return; }

		$tmin = (int)$summary['tmin'];
		$tmax = (int)$summary['tmax'];

		/* prefer double, then int, else a (non-graphable) string series */
		if      ((int)$summary['nd'] > 0) $valcol = 'value_double';
		elseif  ((int)$summary['ni'] > 0) $valcol = 'value_int';
		else                              $valcol = 'value_string';

		/* string timeseries can't be plotted; return a capped raw sample for a table fallback */
		if ($valcol === 'value_string') {
			$cap  = min($maxpoints, 1000);
			$sql  = "select unix_timestamp(time)*1000 t, value_string v from timeseries where observation_id = ?" . $whereWindow . " order by time limit " . (int)$cap;
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			if ($hasWindow) mysqli_stmt_bind_param($stmt, 'iii', $observationid, $winStartSec, $winEndSec);
			else            mysqli_stmt_bind_param($stmt, 'i', $observationid);
			$res    = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$points = [];
			while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) { $points[] = [(float)$row['t'], $row['v']]; }
			mysqli_stmt_close($stmt);
			echo json_encode(['seriesType' => 'string', 'count' => $n, 'downsampled' => ($n > $cap), 'tmin' => $tmin * 1000, 'tmax' => $tmax * 1000, 'points' => $points]);
			return;
		}

		/* numeric: raw points when small enough, otherwise bucket-average down to ~maxpoints */
		if ($n <= $maxpoints) {
			$sql         = "select unix_timestamp(time)*1000 t, $valcol v from timeseries where observation_id = ?" . $whereWindow . " order by time";
			$downsampled = false;
		} else {
			$winStart  = $hasWindow ? $winStartSec : $tmin;
			$winEnd    = $hasWindow ? $winEndSec   : $tmax;
			$bucketsec = (int)ceil(max(1, $winEnd - $winStart) / $maxpoints);
			if ($bucketsec < 1) $bucketsec = 1;
			$t0          = (int)$winStart;
			/* $t0 and $bucketsec are integers derived from timestamps; $valcol is an internal whitelist */
			$sql         = "select min(unix_timestamp(time))*1000 t, avg($valcol) v from timeseries where observation_id = ?" . $whereWindow . " group by floor((unix_timestamp(time) - $t0)/$bucketsec) order by t";
			$downsampled = true;
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		if ($hasWindow) mysqli_stmt_bind_param($stmt, 'iii', $observationid, $winStartSec, $winEndSec);
		else            mysqli_stmt_bind_param($stmt, 'i', $observationid);
		$res    = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$points = [];
		while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) { $points[] = [(float)$row['t'], ($row['v'] === null ? null : (float)$row['v'])]; }
		mysqli_stmt_close($stmt);

		echo json_encode([
			'seriesType'  => 'numeric',
			'valueColumn' => $valcol,
			'count'       => $n,
			'downsampled' => $downsampled,
			'tmin'        => $tmin * 1000,
			'tmax'        => $tmax * 1000,
			'points'      => $points,
		]);
	}


	/* -------------------------------------------- */
	/* ------- GetChecklistTimeseries ------------- */
	/* -------------------------------------------- */
	/* returns merged, windowed timeseries points for ALL of a subject's observations of a given
	   timeseries instrument item (scoped by enrollment). Used by the checklist timeseries view. */
	function GetChecklistTimeseries($enrollmentid, $instrumentitemid, $tstart, $tend, $maxpoints) {
		JsonHeader();
		$enrollmentid     = (int)$enrollmentid;
		$instrumentitemid = (int)$instrumentitemid;
		if ($enrollmentid < 1 || $instrumentitemid < 1) { echo json_encode(['error' => 'Invalid parameters']); return; }

		$maxpoints = (int)$maxpoints;
		if ($maxpoints < 100)   $maxpoints = 1000;
		if ($maxpoints > 20000) $maxpoints = 20000;

		/* gather this subject's observation ids for the selected timeseries item */
		$ids  = [];
		$stmt = mysqli_prepare($GLOBALS['linki'], "select observation_id from observations where enrollment_id = ? and instrumentitem_id = ?");
		mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $instrumentitemid);
		$res  = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) { $ids[] = (int)$row['observation_id']; }
		mysqli_stmt_close($stmt);
		if (count($ids) < 1) { echo json_encode(['seriesType' => 'empty', 'count' => 0, 'points' => []]); return; }
		$idlist = implode(',', $ids);   /* ints straight from the DB - safe to inline */

		/* optional 24hr window, passed as epoch milliseconds */
		$hasWindow   = (is_numeric($tstart) && is_numeric($tend) && (float)$tend > (float)$tstart);
		$winStartSec = $hasWindow ? (int)floor((float)$tstart / 1000) : 0;
		$winEndSec   = $hasWindow ? (int)ceil((float)$tend / 1000)    : 0;
		$whereWindow = $hasWindow ? " and time between from_unixtime(?) and from_unixtime(?)" : "";

		/* summarize: point count, time range, and which typed column holds the values */
		$sql  = "select count(*) n, min(unix_timestamp(time)) tmin, max(unix_timestamp(time)) tmax, sum(value_double is not null) nd, sum(value_int is not null) ni, sum(value_string is not null) ns from timeseries where observation_id in ($idlist)" . $whereWindow;
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		if ($hasWindow) mysqli_stmt_bind_param($stmt, 'ii', $winStartSec, $winEndSec);
		$res     = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$summary = mysqli_fetch_array($res, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		$n = (int)$summary['n'];
		if ($n < 1) { echo json_encode(['seriesType' => 'empty', 'count' => 0, 'points' => []]); return; }
		$tmin = (int)$summary['tmin'];
		$tmax = (int)$summary['tmax'];

		if      ((int)$summary['nd'] > 0) $valcol = 'value_double';
		elseif  ((int)$summary['ni'] > 0) $valcol = 'value_int';
		else                              $valcol = 'value_string';

		/* string timeseries can't be plotted */
		if ($valcol === 'value_string') {
			echo json_encode(['seriesType' => 'string', 'count' => $n, 'tmin' => $tmin * 1000, 'tmax' => $tmax * 1000, 'points' => []]);
			return;
		}

		/* numeric: raw points when small enough, otherwise bucket-average down to ~maxpoints */
		if ($n <= $maxpoints) {
			$sql         = "select unix_timestamp(time)*1000 t, $valcol v from timeseries where observation_id in ($idlist)" . $whereWindow . " order by time";
			$downsampled = false;
		} else {
			$winStart  = $hasWindow ? $winStartSec : $tmin;
			$winEnd    = $hasWindow ? $winEndSec   : $tmax;
			$bucketsec = (int)ceil(max(1, $winEnd - $winStart) / $maxpoints);
			if ($bucketsec < 1) $bucketsec = 1;
			$t0          = (int)$winStart;
			$sql         = "select min(unix_timestamp(time))*1000 t, avg($valcol) v from timeseries where observation_id in ($idlist)" . $whereWindow . " group by floor((unix_timestamp(time) - $t0)/$bucketsec) order by t";
			$downsampled = true;
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		if ($hasWindow) mysqli_stmt_bind_param($stmt, 'ii', $winStartSec, $winEndSec);
		$res    = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$points = [];
		while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) { $points[] = [(float)$row['t'], ($row['v'] === null ? null : (float)$row['v'])]; }
		mysqli_stmt_close($stmt);

		echo json_encode([
			'seriesType'  => 'numeric',
			'count'       => $n,
			'downsampled' => $downsampled,
			'tmin'        => $tmin * 1000,
			'tmax'        => $tmax * 1000,
			'points'      => $points,
		]);
	}


	/* -------------------------------------------- */
	/* ------- BulkUpdateObservations ------------- */
	/* -------------------------------------------- */
	function BulkUpdateObservations($observationidsJson, $column, $value, $tz_offset = '') {
		JsonHeader();
		$ids = json_decode($observationidsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['error' => 'No IDs provided']); return; }

		$allowedColumns = [
			'value'         => 'observation_value',
			'rater'         => 'observation_rater',
			'startdate'     => 'observation_startdate',
			'enddate'       => 'observation_enddate',
			'obsInstrument' => 'observation_instrument',
		];
		if (!array_key_exists($column, $allowedColumns)) {
			echo json_encode(['error' => "Column [$column] not recognized"]);
			return;
		}
		$dbColumn      = $allowedColumns[$column];
		$isDate        = ($column === 'startdate' || $column === 'enddate');
		$nullableValue = (trim($value) === '') ? null : $value;
		$nullableTzOff = (trim($tz_offset) === '') ? null : $tz_offset;
		$updated = 0;
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;
			if ($isDate) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set $dbColumn = ?, observation_tz_offset = ? where observation_id = ?");
				mysqli_stmt_bind_param($stmt, 'ssi', $nullableValue, $nullableTzOff, $id);
			} else {
				$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set $dbColumn = ? where observation_id = ?");
				mysqli_stmt_bind_param($stmt, 'si', $nullableValue, $id);
			}
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$updated++;
		}
		echo json_encode(['updated' => $updated]);
	}


	/* -------------------------------------------- */
	/* ------- BulkDeleteObservations ------------- */
	/* -------------------------------------------- */
	function BulkDeleteObservations($observationidsJson) {
		JsonHeader();
		$ids = json_decode($observationidsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['error' => 'No IDs provided']); return; }
		$deleted = 0;
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;
			$stmt = mysqli_prepare($GLOBALS['linki'], "delete from observations where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$deleted++;
		}
		echo json_encode(['deleted' => $deleted]);
	}


	/* -------------------------------------------- */
	/* ------- BulkMoveToNewSurvey ---------------- */
	/* -------------------------------------------- */
	/* Creates a new survey whose startdate is the oldest observation_startdate among the
	   selected observations, then assigns all selected observations to it. */
	function BulkMoveToNewSurvey($observationidsJson) {
		JsonHeader();
		$ids = json_decode($observationidsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['error' => 'No IDs provided']); return; }

		$intIds = array_values(array_filter(array_map('intval', $ids), function($id) { return $id > 0; }));
		if (empty($intIds)) { echo json_encode(['error' => 'No valid IDs']); return; }

		$idList = implode(',', $intIds);

		/* derive survey startdate from the earliest non-zero observation_startdate */
		$row = mysqli_fetch_array(MySQLiQuery(
			"select min(case when observation_startdate = '0000-01-01 00:00:00' or observation_startdate is null then null else observation_startdate end) as min_date from observations where observation_id in ($idList)",
			__FILE__, __LINE__
		), MYSQLI_ASSOC);
		$startdate_sql = (!empty($row['min_date'])) ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $row['min_date']) . "'" : "null";

		/* create the new survey (no instrument affiliation — observations may span instruments) */
		MySQLiQuery("insert into observation_surveys (instrument_id, survey_startdate, survey_entrydate) values (null, $startdate_sql, now())", __FILE__, __LINE__);
		$surveyid = (int)mysqli_insert_id($GLOBALS['linki']);

		/* reassign all selected observations to the new survey regardless of prior assignment */
		MySQLiQuery("update observations set observationsurvey_id = $surveyid where observation_id in ($idList)", __FILE__, __LINE__);

		echo json_encode(['survey_id' => $surveyid, 'moved' => count($intIds)]);
	}


	/* -------------------------------------------- */
	/* ------- FlattenJsonArray ------------------- */
	/* -------------------------------------------- */
	/* Flattens a nested array into dot-joined key paths using underscore as separator.
	   e.g. ['var_1' => ['subvar1' => 'x']] → ['var_1_subvar1' => 'x'] */
	function FlattenJsonArray($data, $prefix) {
		$result = [];
		foreach ($data as $key => $value) {
			$fullKey = $prefix !== '' ? $prefix . '_' . $key : (string)$key;
			if (is_array($value)) {
				$result = array_merge($result, FlattenJsonArray($value, $fullKey));
			} else {
				$result[$fullKey] = ($value === null) ? '' : (string)$value;
			}
		}
		return $result;
	}

	/* -------------------------------------------- */
	/* ------- BulkConvertValueToMeta ------------- */
	/* -------------------------------------------- */
	function BulkConvertValueToMeta($observationidsJson) {
		JsonHeader();
		$ids = json_decode($observationidsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['error' => 'No IDs provided']); return; }

		$converted = 0;
		$skipped   = 0;

		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;

			/* fetch current value */
			$stmt = mysqli_prepare($GLOBALS['linki'], "select observation_value from observations where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) continue;

			$jsonStr = trim($row['observation_value']);
			if ($jsonStr === '') { $skipped++; continue; }

			$decoded = json_decode($jsonStr, true);
			if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
				$skipped++;
				continue;
			}

			/* flatten and insert each key-value pair */
			$flat = FlattenJsonArray($decoded, '');
			foreach ($flat as $variable => $value) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "insert into observation_meta (observation_id, variable, value) values (?, ?, ?)");
				mysqli_stmt_bind_param($stmt, 'iss', $id, $variable, $value);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
			}

			/* clear the raw JSON value now that meta rows are written */
			$empty = '';
			$stmt = mysqli_prepare($GLOBALS['linki'], "update observations set observation_value = ? where observation_id = ?");
			mysqli_stmt_bind_param($stmt, 'si', $empty, $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);

			$converted++;
		}

		echo json_encode(['converted' => $converted, 'skipped' => $skipped]);
	}


	/* -------------------------------------------- */
	/* ------- SearchInstruments ------------------ */
	/* -------------------------------------------- */
	function SearchInstruments($term, $projectid) {
		JsonHeader();
		if ($projectid < 1) { echo json_encode([]); return; }
		$term = trim($term);
		$results = array();
		if ($term === '') {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id, instrument_name from instruments where project_id = ? order by instrument_name limit 100");
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
		} else {
			$search = '%' . $term . '%';
			$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id, instrument_name from instruments where project_id = ? and instrument_name like ? order by instrument_name limit 50");
			mysqli_stmt_bind_param($stmt, 'is', $projectid, $search);
		}
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$results[] = ['label' => $row['instrument_name'], 'value' => $row['instrument_name'], 'id' => (int)$row['instrument_id']];
		}
		mysqli_stmt_close($stmt);
		echo json_encode($results);
	}


	/* -------------------------------------------- */
	/* ------- SearchInstrumentItems -------------- */
	/* -------------------------------------------- */
	function SearchInstrumentItems($term, $instrumentid) {
		JsonHeader();
		if ($instrumentid < 1) { echo json_encode([]); return; }
		$term = trim($term);
		$results = array();
		if ($term === '') {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select instrumentitem_id, item_name from instrument_items where instrument_id = ? order by item_order, item_name limit 100");
			mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		} else {
			$search = '%' . $term . '%';
			$stmt = mysqli_prepare($GLOBALS['linki'], "select instrumentitem_id, item_name from instrument_items where instrument_id = ? and item_name like ? order by item_order, item_name limit 50");
			mysqli_stmt_bind_param($stmt, 'is', $instrumentid, $search);
		}
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$results[] = ['label' => $row['item_name'], 'value' => $row['item_name'], 'id' => (int)$row['instrumentitem_id']];
		}
		mysqli_stmt_close($stmt);
		echo json_encode($results);
	}


	/* -------------------------------------------- */
	/* ------- AddInstrumentAjax ------------------ */
	/* -------------------------------------------- */
	function AddInstrumentAjax($name, $notes, $projectid) {
		JsonHeader();
		$name = trim($name);
		if ($name == '' || $projectid < 1) { echo json_encode(['error' => 'Invalid name or project']); return; }
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into instruments (project_id, instrument_name, instrument_notes) values (?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'iss', $projectid, $name, $notes);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$newid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		echo json_encode(['instrument_id' => $newid, 'instrument_name' => $name]);
	}


	/* -------------------------------------------- */
	/* ------- AddInstrumentItemAjax -------------- */
	/* -------------------------------------------- */
	function AddInstrumentItemAjax($name, $type, $notes, $instrumentid) {
		JsonHeader();
		$name = trim($name);
		if ($name == '' || $instrumentid < 1) { echo json_encode(['error' => 'Invalid name or instrument']); return; }
		$validTypes = ['int', 'double', 'string', 'timeseries'];
		if (!in_array($type, $validTypes)) $type = 'string';
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into instrument_items (instrument_id, item_name, item_type, item_notes) values (?, ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'isss', $instrumentid, $name, $type, $notes);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$newid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		echo json_encode(['instrumentitem_id' => $newid, 'item_name' => $name]);
	}


	/* -------------------------------------------- */
	/* ------- FormalizeInstrument ---------------- */
	/* -------------------------------------------- */
	function FormalizeInstrument($instrumentname, $originalname, $projectid, $itemnamesJson) {
		JsonHeader();
		$instrumentname = trim($instrumentname);
		$originalname   = trim($originalname);
		if ($instrumentname === '' || $projectid < 1) { echo json_encode(['error' => 'Invalid instrument name or project']); return; }

		/* check for duplicate */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select instrument_id from instruments where project_id = ? and instrument_name = ? limit 1");
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $instrumentname);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) { echo json_encode(['error' => 'An instrument with this name already exists in the project']); return; }
		mysqli_stmt_close($stmt);

		$itemnames = json_decode($itemnamesJson, true);
		if (!is_array($itemnames)) $itemnames = array();

		/* create instrument */
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into instruments (project_id, instrument_name) values (?, ?)");
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $instrumentname);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$instrumentId = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);

		if (!$instrumentId) { echo json_encode(['error' => 'Failed to create instrument']); return; }

		/* create items and convert observations project-wide */
		$totalConverted = 0;
		foreach ($itemnames as $itemname) {
			$itemname = trim($itemname);
			if ($itemname === '') continue;

			$stmt = mysqli_prepare($GLOBALS['linki'], "insert into instrument_items (instrument_id, item_name, item_type) values (?, ?, 'string')");
			mysqli_stmt_bind_param($stmt, 'is', $instrumentId, $itemname);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$itemId = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);

			/* match on original legacy instrument name and observation name */
			$stmt = mysqli_prepare($GLOBALS['linki'], "update observations o join enrollment e on o.enrollment_id = e.enrollment_id set o.instrumentitem_id = ? where e.project_id = ? and o.observation_instrument = ? and o.observation_name = ? and o.instrumentitem_id is null");
			mysqli_stmt_bind_param($stmt, 'iiss', $itemId, $projectid, $originalname, $itemname);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$totalConverted += mysqli_affected_rows($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
		}

		echo json_encode(['instrument_id' => $instrumentId, 'converted' => $totalConverted]);
	}


	/* -------------------------------------------- */
	/* ------- GetSurveys ------------------------- */
	/* -------------------------------------------- */
	/* returns JSON array of surveys for a given enrollment + instrument, most recent first */
	function GetSurveys($enrollmentid, $instrumentid) {
		JsonHeader();
		if ($enrollmentid <= 0 || $instrumentid <= 0) {
			echo json_encode(array());
			return;
		}
		/* find surveys that have at least one observation from this enrollment */
		$sqlstring = "select s.survey_id, s.survey_startdate, s.survey_enddate, s.survey_rater, s.survey_notes, s.survey_visit from observation_surveys s join observations o on o.observationsurvey_id = s.survey_id where o.enrollment_id = $enrollmentid and s.instrument_id = $instrumentid group by s.survey_id order by s.survey_startdate desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$surveys = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$surveys[] = array(
				'survey_id' => (int)$row['survey_id'],
				'startdate' => $row['survey_startdate'],
				'enddate'   => $row['survey_enddate'],
				'rater'     => $row['survey_rater'],
				'notes'     => $row['survey_notes'],
				'visit'     => $row['survey_visit'],
			);
		}
		echo json_encode($surveys);
	}


	/* -------------------------------------------- */
	/* ------- AssignToSurvey --------------------- */
	/* -------------------------------------------- */
	/* assigns a list of observation IDs to an existing survey */
	function AssignToSurvey($surveyid, $observationidsJson) {
		JsonHeader();
		if ($surveyid <= 0) {
			echo json_encode(array('error' => 'invalid survey_id'));
			return;
		}
		$ids = json_decode($observationidsJson, true);
		if (!is_array($ids) || count($ids) === 0) {
			echo json_encode(array('error' => 'no observation IDs provided'));
			return;
		}
		$idList = implode(',', array_map('intval', $ids));
		$sqlstring = "update observations set observationsurvey_id = $surveyid where observation_id in ($idList)";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);
		echo json_encode(array('success' => true));
	}


	/* -------------------------------------------- */
	/* ------- CreateAndAssignSurvey -------------- */
	/* -------------------------------------------- */
	/* creates a new observation_surveys record and assigns a list of observations to it */
	function CreateAndAssignSurvey($enrollmentid, $instrumentid, $startdate, $enddate, $rater, $notes, $observationidsJson) {
		JsonHeader();
		if ($enrollmentid <= 0) {
			echo json_encode(array('error' => 'invalid enrollment_id'));
			return;
		}
		$ids = json_decode($observationidsJson, true);
		if (!is_array($ids) || count($ids) === 0) {
			echo json_encode(array('error' => 'no observation IDs provided'));
			return;
		}
		$startdateVal    = !empty(trim($startdate)) ? $startdate : null;
		$enddateVal      = !empty(trim($enddate))   ? $enddate   : null;
		$raterVal        = !empty(trim($rater))     ? $rater     : null;
		$notesVal        = !empty(trim($notes))     ? $notes     : null;
		$instrumentidVal = ($instrumentid > 0)      ? (int)$instrumentid : null;

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into observation_surveys (instrument_id, survey_startdate, survey_enddate, survey_rater, survey_notes, survey_entrydate) values (?, ?, ?, ?, ?, now())");
		mysqli_stmt_bind_param($stmt, 'issss', $instrumentidVal, $startdateVal, $enddateVal, $raterVal, $notesVal);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$surveyid = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);

		$idList = implode(',', array_map('intval', $ids));
		$sqlstring = "update observations set observationsurvey_id = $surveyid where observation_id in ($idList)";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);

		echo json_encode(array('success' => true, 'survey_id' => (int)$surveyid));
	}


	/* -------------------------------------------- */
	/* ------- UpdateSurvey ----------------------- */
	/* -------------------------------------------- */
	/* updates metadata fields on an existing observation_surveys record */
	function UpdateSurvey($surveyid, $startdate, $enddate, $rater, $notes, $status = "") {
		JsonHeader();
		if ($surveyid <= 0) {
			echo json_encode(array('error' => 'invalid survey_id'));
			return;
		}
		$startdateVal = !empty(trim($startdate)) ? $startdate : null;
		$enddateVal   = !empty(trim($enddate))   ? $enddate   : null;
		$raterVal     = !empty(trim($rater))     ? $rater     : null;
		$notesVal     = !empty(trim($notes))     ? $notes     : null;
		/* survey_status is a tinyint code (0-7); a blank selection clears it to NULL. 0 is a valid code, so test for numeric rather than truthiness. */
		$statusVal    = is_numeric(trim($status)) ? (int)$status : null;

		$stmt = mysqli_prepare($GLOBALS['linki'], "update observation_surveys set survey_startdate = ?, survey_enddate = ?, survey_rater = ?, survey_notes = ?, survey_status = ? where survey_id = ?");
		mysqli_stmt_bind_param($stmt, 'ssssii', $startdateVal, $enddateVal, $raterVal, $notesVal, $statusVal, $surveyid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		echo json_encode(array('success' => true));
	}


	/* -------------------------------------------- */
	/* ------- GetInstrumentItems ----------------- */
	/* -------------------------------------------- */
	function GetInstrumentItems($instrumentid) {
		JsonHeader();
		if ($instrumentid < 1) { echo json_encode([]); return; }
		$stmt = mysqli_prepare($GLOBALS['linki'], "SELECT instrumentitem_id, item_name FROM instrument_items WHERE instrument_id = ? ORDER BY item_order, item_name");
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$items = [];
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$items[] = ['id' => (int)$row['instrumentitem_id'], 'name' => $row['item_name']];
		}
		mysqli_stmt_close($stmt);
		echo json_encode($items);
	}


	/* -------------------------------------------- */
	/* ------- GetInstrumentByName ---------------- */
	/* -------------------------------------------- */
	/* Look up an instrument in a project by name and return it with its items,
	   including type and notes. Used by the "Create Instrument from Form" dialog
	   to compare a proposed instrument against one that already exists.

	   Returns instrument_id 0 when no instrument of that name exists. */
	function GetInstrumentByName($name, $projectid) {
		JsonHeader();
		$name = trim($name);
		if (($name == '') || ($projectid < 1)) {
			echo json_encode(['instrument_id' => 0, 'items' => []]);
			return;
		}

		$sql = "select instrument_id, instrument_name, instrument_notes from instruments where project_id = ? and instrument_name = ? limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $name);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($projectid, $name));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row) {
			echo json_encode(['instrument_id' => 0, 'items' => []]);
			return;
		}

		$instrumentid = (int)$row['instrument_id'];
		$out = [
			'instrument_id'    => $instrumentid,
			'instrument_name'  => (string)$row['instrument_name'],
			'instrument_notes' => (string)($row['instrument_notes'] ?? ''),
			'items'            => []
		];

		$sql = "select instrumentitem_id, item_name, item_type, item_notes, item_order from instrument_items where instrument_id = ? order by item_order, item_name";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($instrumentid));
		while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$out['items'][] = [
				'id'    => (int)$r['instrumentitem_id'],
				'name'  => (string)$r['item_name'],
				'type'  => (string)$r['item_type'],
				'notes' => (string)($r['item_notes'] ?? ''),
				'order' => (int)$r['item_order']
			];
		}
		mysqli_stmt_close($stmt);

		echo json_encode($out);
	}


	/* -------------------------------------------- */
	/* ------- CreateInstrumentItems -------------- */
	/* -------------------------------------------- */
	/* Create an instrument (if it does not already exist) and append items to it.

	   Only items whose names are not already present are inserted -- existing
	   items are never modified, because they may already be referenced by
	   observations. Item names are compared case-insensitively.

	   $itemsJson is a JSON array of {name, type, notes, field, choicecode,
	   fieldtype, validation}. The redcap_* members are only needed when
	   $createmappings is set, in which case a remoteimport_mapping row is also
	   created for each item, wiring the REDCap field to the new NiDB item. */
	function CreateInstrumentItems($name, $notes, $projectid, $itemsJson, $redcapForm = '', $redcapEvent = '', $createmappings = 0) {
		JsonHeader();
		$name = trim($name);
		if (($name == '') || ($projectid < 1)) {
			echo json_encode(['ok' => false, 'error' => 'An instrument name and project are required']);
			return;
		}

		$items = json_decode($itemsJson ?? '', true);
		if (!is_array($items) || empty($items)) {
			echo json_encode(['ok' => false, 'error' => 'No items were submitted']);
			return;
		}

		/* item_type must be a value the enum accepts; anything else is rejected
		   rather than silently coerced, so a mis-typed item is never created */
		$validTypes = ['enum', 'int', 'double', 'string', 'timeseries', 'image', 'csv', 'json', 'datetime'];

		/* find or create the instrument */
		$sql = "select instrument_id from instruments where project_id = ? and instrument_name = ? limit 1";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'is', $projectid, $name);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($projectid, $name));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		$created = false;
		if ($row)
			$instrumentid = (int)$row['instrument_id'];
		else {
			$sql = "insert into instruments (project_id, instrument_name, instrument_notes) values (?,?,?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'iss', $projectid, $name, $notes);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($projectid, $name, $notes));
			mysqli_stmt_close($stmt);
			$instrumentid = (int)mysqli_insert_id($GLOBALS['linki']);
			$created = true;
		}

		if ($instrumentid < 1) {
			echo json_encode(['ok' => false, 'error' => 'Could not create or find the instrument']);
			return;
		}

		/* existing item names (lowercased) and the current max order */
		$existing = [];
		$maxorder = 0;
		$sql = "select item_name, item_order from instrument_items where instrument_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
		mysqli_stmt_bind_param($stmt, 'i', $instrumentid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($instrumentid));
		while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$existing[strtolower(trim($r['item_name']))] = true;
			if ((int)$r['item_order'] > $maxorder)
				$maxorder = (int)$r['item_order'];
		}
		mysqli_stmt_close($stmt);

		$added = 0;
		$mapped = 0;
		$skipped = [];
		$rejected = [];

		/* redcap_datatype is an enum; anything outside it is stored as NULL rather
		   than rejected, since the datatype is informational for the mapping */
		$validRedcapTypes = ['text','notes','radio','dropdown','checkbox','calc','slider','descriptive','file','yesno','truefalse','sql'];

		$sql = "insert into instrument_items (instrument_id, item_name, item_type, item_notes, item_order) values (?,?,?,?,?)";
		foreach ($items as $it) {
			if (!is_array($it))
				continue;
			$iname  = trim($it['name'] ?? '');
			$itype  = trim($it['type'] ?? '');
			$inotes = (string)($it['notes'] ?? '');

			if ($iname == '')
				continue;

			if (isset($existing[strtolower($iname)])) {
				$skipped[] = $iname;
				continue;
			}
			if (!in_array($itype, $validTypes, true)) {
				$rejected[] = "$iname (invalid type '$itype')";
				continue;
			}

			$maxorder++;
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql);
			mysqli_stmt_bind_param($stmt, 'isssi', $instrumentid, $iname, $itype, $inotes, $maxorder);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql, array($instrumentid, $iname, $itype, $inotes, $maxorder));
			mysqli_stmt_close($stmt);
			$itemid = (int)mysqli_insert_id($GLOBALS['linki']);

			/* guard against duplicates within the submitted batch itself */
			$existing[strtolower($iname)] = true;
			$added++;

			/* optionally wire the REDCap field to the item just created */
			if ($createmappings && ($itemid > 0)) {
				$rcField  = trim($it['field'] ?? '');
				if ($rcField == '')
					continue;

				/* Key columns are stored as '' rather than NULL so the
				   uniq_redcap_mapping index actually constrains them -- MySQL
				   unique indexes do not compare NULLs. */
				$rcEvent  = (string)$redcapEvent;
				$rcForm   = (string)$redcapForm;
				$rcChoice = (string)($it['choicecode'] ?? '');
				$rcType   = trim($it['fieldtype'] ?? '');
				$rcTypeVal = in_array($rcType, $validRedcapTypes, true) ? $rcType : null;
				$rcValid  = trim($it['validation'] ?? '');
				$rcValidVal = ($rcValid !== '') ? $rcValid : null;

				/* skip if this field is already mapped (the unique index would
				   reject it anyway) */
				$q = "select remoteimportmapping_id from remoteimport_mapping where project_id = ? and source_type = 'redcap' and redcap_event = ? and redcap_form = ? and redcap_field = ? and redcap_choice_code = ? limit 1";
				$stmt = mysqli_prepare($GLOBALS['linki'], $q);
				mysqli_stmt_bind_param($stmt, 'issss', $projectid, $rcEvent, $rcForm, $rcField, $rcChoice);
				$res = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $q, array($projectid, $rcEvent, $rcForm, $rcField, $rcChoice));
				$dupe = mysqli_fetch_array($res, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
				if ($dupe)
					continue;

				$q = "insert into remoteimport_mapping (project_id, source_type, redcap_event, redcap_form, redcap_field, redcap_choice_code, redcap_datatype, redcap_validation, nidb_instrument, nidb_variable) values (?, 'redcap', ?,?,?,?,?,?,?,?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $q);
				mysqli_stmt_bind_param($stmt, 'issssssii', $projectid, $rcEvent, $rcForm, $rcField, $rcChoice, $rcTypeVal, $rcValidVal, $instrumentid, $itemid);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $q, array($projectid, $rcEvent, $rcForm, $rcField, $rcChoice, $rcTypeVal, $rcValidVal, $instrumentid, $itemid));
				mysqli_stmt_close($stmt);
				$mapped++;
			}
		}

		echo json_encode([
			'ok'              => true,
			'instrument_id'   => $instrumentid,
			'instrument_name' => $name,
			'created'         => $created,
			'added'           => $added,
			'mapped'          => $mapped,
			'skipped'         => $skipped,
			'rejected'        => $rejected
		]);
	}


	/* -------------------------------------------- */
	/* ------- UpdateMappingFlag ------------------ */
	/* -------------------------------------------- */
	function UpdateMappingFlag($mappingid, $flagname, $value) {
		JsonHeader();
		if ($mappingid < 1) { echo json_encode(['ok' => false, 'error' => 'invalid mappingid']); return; }
		$allowed = ['flag_date_from_field', 'flag_can_repeat', 'flag_import_meta'];
		if (!in_array($flagname, $allowed, true)) {
			echo json_encode(['ok' => false, 'error' => 'invalid flag name']);
			return;
		}
		$v = $value ? 1 : 0;
		$stmt = mysqli_prepare($GLOBALS['linki'], "UPDATE remoteimport_mapping SET $flagname = ? WHERE remoteimportmapping_id = ?");
		mysqli_stmt_bind_param($stmt, 'ii', $v, $mappingid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		echo json_encode(['ok' => true]);
	}


	/* -------------------------------------------- */
	/* ------- SaveMapping ------------------------ */
	/* -------------------------------------------- */
	function SaveMapping($mappingid, $projectid, $source_type, $avicenna_question, $avicenna_variable, $avicenna_variablecount, $avicenna_survey, $avicenna_datasource, $avicenna_datatype, $redcap_event, $redcap_form, $redcap_field, $redcap_choice_code, $redcap_datatype, $redcap_validation, $redcap_datefield, $nidb_instrument, $nidb_variable, $flag_date_from_field, $flag_can_repeat, $flag_import_meta) {
		JsonHeader();
		if ($projectid < 1) { echo json_encode(['ok' => false, 'error' => 'invalid projectid']); return; }
		$allowed_types = ['avicenna', 'redcap'];
		if (!in_array($source_type, $allowed_types, true)) {
			echo json_encode(['ok' => false, 'error' => 'invalid source_type']);
			return;
		}
		$nidb_instrument_val   = $nidb_instrument   > 0 ? $nidb_instrument   : null;
		$nidb_variable_val     = $nidb_variable     > 0 ? $nidb_variable     : null;
		$avicenna_question_val      = $avicenna_question      > 0  ? $avicenna_question      : null;
		$avicenna_variable_val      = $avicenna_variable     !== '' ? $avicenna_variable      : null;
		$avicenna_variablecount_val = $avicenna_variablecount !== '' ? $avicenna_variablecount : null;
		$avicenna_survey_val        = $avicenna_survey       !== '' ? $avicenna_survey        : null;
		$avicenna_datasource_val    = $avicenna_datasource   !== '' ? $avicenna_datasource    : null;
		$avicenna_datatype_val      = $avicenna_datatype     !== '' ? $avicenna_datatype      : null;

		// For an Avicenna mapping, exactly one of survey / datasource is required.
		if ($source_type === 'avicenna') {
			$hasSurvey     = $avicenna_survey_val     !== null;
			$hasDatasource = $avicenna_datasource_val !== null;
			if ($hasSurvey === $hasDatasource) {
				echo json_encode(['ok' => false, 'error' => $hasSurvey
					? 'Enter a survey OR a datasource, not both'
					: 'A survey or datasource is required']);
				return;
			}
		}
		/* The REDCap key columns are stored as '' rather than NULL, because MySQL
		   unique indexes do not compare NULLs -- uniq_redcap_mapping would not
		   constrain anything for a classic project (no event) or a non-checkbox
		   field (no choice code). Avicenna rows keep NULL in these columns, and
		   that is exactly what keeps the same index inert for them. */
		if ($source_type === 'redcap') {
			$redcap_event_val       = $redcap_event;
			$redcap_form_val        = $redcap_form;
			$redcap_field_val       = $redcap_field;
			$redcap_choice_code_val = $redcap_choice_code;
		}
		else {
			$redcap_event_val = $redcap_form_val = $redcap_field_val = $redcap_choice_code_val = null;
		}
		$allowed_dt = ['text','notes','radio','dropdown','checkbox','calc','slider','descriptive','file','yesno','truefalse','sql'];
		$redcap_datatype_val   = in_array($redcap_datatype, $allowed_dt, true) ? $redcap_datatype : null;
		$redcap_validation_val = $redcap_validation !== '' ? $redcap_validation : null;
		$redcap_datefield_val  = $redcap_datefield  !== '' ? $redcap_datefield  : null;
		$fdf = $flag_date_from_field ? 1 : 0;
		$fcr = $flag_can_repeat      ? 1 : 0;
		$fim = $flag_import_meta     ? 1 : 0;

		if ($mappingid > 0) {
			// Update existing
			$stmt = mysqli_prepare($GLOBALS['linki'],
				"UPDATE remoteimport_mapping SET avicenna_question=?, avicenna_variable=?, avicenna_variablecount=?, avicenna_survey=?, avicenna_datasource=?, avicenna_datatype=?, redcap_event=?, redcap_form=?, redcap_field=?, redcap_choice_code=?, redcap_datatype=?, redcap_validation=?, redcap_datefield=?, nidb_instrument=?, nidb_variable=?, flag_date_from_field=?, flag_can_repeat=?, flag_import_meta=? WHERE remoteimportmapping_id=? AND project_id=?");
			mysqli_stmt_bind_param($stmt, 'i' . 'ssssssssssss' . 'iiiiiii',
				$avicenna_question_val, $avicenna_variable_val, $avicenna_variablecount_val, $avicenna_survey_val, $avicenna_datasource_val, $avicenna_datatype_val, $redcap_event_val, $redcap_form_val, $redcap_field_val, $redcap_choice_code_val, $redcap_datatype_val, $redcap_validation_val, $redcap_datefield_val,
				$nidb_instrument_val, $nidb_variable_val, $fdf, $fcr, $fim, $mappingid, $projectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			echo json_encode(['ok' => true, 'mappingid' => $mappingid]);
		} else {
			// Insert new
			$stmt = mysqli_prepare($GLOBALS['linki'],
				"INSERT INTO remoteimport_mapping (project_id, source_type, avicenna_question, avicenna_variable, avicenna_variablecount, avicenna_survey, avicenna_datasource, avicenna_datatype, redcap_event, redcap_form, redcap_field, redcap_choice_code, redcap_datatype, redcap_validation, redcap_datefield, nidb_instrument, nidb_variable, flag_date_from_field, flag_can_repeat, flag_import_meta) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			mysqli_stmt_bind_param($stmt, 'isi' . 'ssssssssssss' . 'iiiii',
				$projectid, $source_type, $avicenna_question_val, $avicenna_variable_val, $avicenna_variablecount_val, $avicenna_survey_val, $avicenna_datasource_val, $avicenna_datatype_val, $redcap_event_val, $redcap_form_val, $redcap_field_val, $redcap_choice_code_val, $redcap_datatype_val, $redcap_validation_val, $redcap_datefield_val,
				$nidb_instrument_val, $nidb_variable_val, $fdf, $fcr, $fim);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$newid = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
			echo json_encode(['ok' => true, 'mappingid' => (int)$newid]);
		}
	}


	/* -------------------------------------------- */
	/* ------- DeleteMapping ---------------------- */
	/* -------------------------------------------- */
	function DeleteMapping($mappingid) {
		JsonHeader();
		if ($mappingid < 1) { echo json_encode(['ok' => false, 'error' => 'invalid mappingid']); return; }
		$stmt = mysqli_prepare($GLOBALS['linki'], "DELETE FROM remoteimport_mapping WHERE remoteimportmapping_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $mappingid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		echo json_encode(['ok' => true]);
	}


	/* -------------------------------------------- */
	/* ------- BulkDeleteMappings ----------------- */
	/* -------------------------------------------- */
	function BulkDeleteMappings($idsJson) {
		JsonHeader();
		$ids = json_decode($idsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['ok' => false, 'error' => 'No IDs provided']); return; }
		$deleted = 0;
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;
			$stmt = mysqli_prepare($GLOBALS['linki'], "DELETE FROM remoteimport_mapping WHERE remoteimportmapping_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$deleted++;
		}
		echo json_encode(['ok' => true, 'deleted' => $deleted]);
	}


	/* -------------------------------------------- */
	/* ------- BulkDeleteItems -------------------- */
	/* -------------------------------------------- */
	function BulkDeleteItems($idsJson) {
		JsonHeader();
		$ids = json_decode($idsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['ok' => false, 'error' => 'No IDs provided']); return; }
		$deleted = 0;
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;
			$stmt = mysqli_prepare($GLOBALS['linki'], "DELETE FROM instrument_items WHERE instrumentitem_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$deleted++;
		}
		echo json_encode(['ok' => true, 'deleted' => $deleted]);
	}


	/* -------------------------------------------- */
	/* ------- ChecklistSetMissingReason ---------- */
	/* -------------------------------------------- */
	/* insert/update a missing-data reason for the imaging checklist; returns the row so the
	   client can update the ag-grid cell in place. */
	function ChecklistSetMissingReason($enrollmentid, $projectchecklistid, $reason) {
		JsonHeader();
		$enrollmentid       = (int)$enrollmentid;
		$projectchecklistid = (int)$projectchecklistid;
		$reason             = trim($reason);
		if ($enrollmentid < 1 || $projectchecklistid < 1) { echo json_encode(['ok' => false, 'error' => 'Invalid parameters']); return; }

		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into enrollment_missingdata (enrollment_id, projectchecklist_id, missing_reason, missingreason_date) values (?, ?, ?, now()) on duplicate key update missing_reason = ?, missingreason_date = now()");
		mysqli_stmt_bind_param($stmt, 'iiss', $enrollmentid, $projectchecklistid, $reason, $reason);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		$stmt = mysqli_prepare($GLOBALS['linki'], "select missingdata_id, missing_reason, missingreason_date from enrollment_missingdata where enrollment_id = ? and projectchecklist_id = ?");
		mysqli_stmt_bind_param($stmt, 'ii', $enrollmentid, $projectchecklistid);
		$res = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($res, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		echo json_encode(['ok' => true, 'missingdataid' => (int)($row['missingdata_id'] ?? 0), 'reason' => $row['missing_reason'] ?? '', 'date' => $row['missingreason_date'] ?? '']);
	}


	/* -------------------------------------------- */
	/* ------- ChecklistDeleteMissingReason ------- */
	/* -------------------------------------------- */
	/* delete a missing-data reason (imaging checklist) */
	function ChecklistDeleteMissingReason($missingdataid) {
		JsonHeader();
		$missingdataid = (int)$missingdataid;
		if ($missingdataid < 1) { echo json_encode(['ok' => false, 'error' => 'Invalid ID']); return; }
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from enrollment_missingdata where missingdata_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $missingdataid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		echo json_encode(['ok' => true]);
	}


	/* -------------------------------------------- */
	/* ------- BulkUpdateItemType ----------------- */
	/* -------------------------------------------- */
	function BulkUpdateItemType($idsJson, $type) {
		JsonHeader();
		$allowed = ['string', 'int', 'double', 'enum', 'timeseries', 'image', 'csv'];
		if (!in_array($type, $allowed, true)) { echo json_encode(['ok' => false, 'error' => 'Invalid type']); return; }
		$ids = json_decode($idsJson, true);
		if (!is_array($ids) || count($ids) === 0) { echo json_encode(['ok' => false, 'error' => 'No IDs provided']); return; }
		$updated = 0;
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;
			$stmt = mysqli_prepare($GLOBALS['linki'], "UPDATE instrument_items SET item_type = ? WHERE instrumentitem_id = ?");
			mysqli_stmt_bind_param($stmt, 'si', $type, $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$updated++;
		}
		echo json_encode(['ok' => true, 'updated' => $updated]);
	}


	/* -------------------------------------------- */
	/* ------- GetDicomReceiverCounts ------------- */
	/* -------------------------------------------- */
	/* counts of files in the dicom_monitor table by status, for the DICOM receiver monitor page */
	function GetDicomReceiverCounts() {
		JsonHeader();
		$counts = array('Received' => 0, 'Parsed' => 0, 'Error' => 0);
		$sqlstring = "select file_status, count(*) 'count' from dicom_monitor where file_status in ('Received', 'Parsed', 'Error') group by file_status";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if (isset($counts[$row['file_status']]))
				$counts[$row['file_status']] = (int)$row['count'];
		}
		echo json_encode(array(
			'received' => $counts['Received'],
			'parsed'   => $counts['Parsed'],
			'error'    => $counts['Error'],
			'total'    => $counts['Received'] + $counts['Parsed'] + $counts['Error']
		));
	}


	/* -------------------------------------------- */
	/* ------- GetDicomArchivedSummary ------------ */
	/* -------------------------------------------- */
	/* returns the archived-files summary table (HTML) for the DICOM receiver monitor page, using
	   the same shared renderer as the initial page render */
	function GetDicomArchivedSummary() {
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: text/html; charset=utf-8');
		echo DicomArchivedSummaryHTML();
	}


	/* -------------------------------------------- */
	/* ------- GetFileIOList ---------------------- */
	/* -------------------------------------------- */
	/* returns the full fileio_requests list (JSON) for the filesio.php page to poll, so new
	   operations appear and existing ones update while the page is idle. Uses the shared builder,
	   which applies the same per-user access filtering as the initial page render. */
	function GetFileIOList() {
		JsonHeader();
		list($rows, $pendingCount) = GetFileIORequests();
		echo json_encode($rows);
	}


	/* -------------------------------------------- */
	/* ------- MarkAnalysesComplete --------------- */
	/* -------------------------------------------- */
	/* Marks the given analyses (from the cluster.php pipelines grid) as complete: Ids arrive as a JSON array; each is cast to int and bound. */
	function MarkAnalysesComplete($idsJson) {
		JsonHeader();
		$ids = json_decode($idsJson, true);
		if (!is_array($ids) || count($ids) === 0) {
			echo json_encode(array('ok' => false, 'error' => 'No analyses selected'));
			return;
		}
		$cleanIds = array();
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id > 0) $cleanIds[] = $id;
		}
		if (count($cleanIds) === 0) {
			echo json_encode(array('ok' => false, 'error' => 'No valid analysis IDs'));
			return;
		}
		$placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
		$sqlstring = "update analysis set analysis_status = 'complete', analysis_statusmessage = 'Marked complete by user', analysis_statusdatetime = now(), analysis_enddate = now(), analysis_iscomplete = 1 where analysis_id in ($placeholders)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$types = str_repeat('i', count($cleanIds));
		mysqli_stmt_bind_param($stmt, $types, ...$cleanIds);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $cleanIds);
		$affected = mysqli_stmt_affected_rows($stmt);
		mysqli_stmt_close($stmt);
		echo json_encode(array('ok' => true, 'cancelled' => (int)$affected));
	}


	/* -------------------------------------------- */
	/* ------- DeleteSelectedAnalyses ------------- */
	/* -------------------------------------------- */
	/* Queues the given analyses for deletion (from the cluster.php pipelines grid). This mirrors the
	   core of analysis.php DeleteAnalyses() - mark the analysis 'Queued for deletion' and insert a
	   'delete' fileio_requests row for the fileio module to process - but is standalone and does NOT
	   disable pipelines, because the cluster selection can span many pipelines. */
	function DeleteSelectedAnalyses($idsJson) {
		JsonHeader();
		$ids = json_decode($idsJson, true);
		if (!is_array($ids) || count($ids) === 0) {
			echo json_encode(array('ok' => false, 'error' => 'No analyses selected'));
			return;
		}
		$cleanIds = array();
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id > 0) $cleanIds[] = $id;
		}
		if (count($cleanIds) === 0) {
			echo json_encode(array('ok' => false, 'error' => 'No valid analysis IDs'));
			return;
		}

		/* one group id for the whole batch, matching analysis.php DeleteAnalyses() */
		$result = MySQLiQuery("select max(group_id) 'maxgroupid' from fileio_requests", __FILE__, __LINE__);
		$grow = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$groupid = (int)($grow['maxgroupid'] ?? 0) + 1;

		$deleted = 0;
		foreach ($cleanIds as $analysisid) {
			/* mark it so the analysis clearly shows it is going away */
			$sql1 = "update analysis set analysis_statusmessage = 'Queued for deletion' where analysis_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql1);
			mysqli_stmt_bind_param($stmt, 'i', $analysisid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql1, array($analysisid));
			mysqli_stmt_close($stmt);

			/* level 2 pipelines are group analyses; everything else is a study-level analysis */
			$sql2 = "select e.pipeline_level from analysis a left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql2);
			mysqli_stmt_bind_param($stmt, 'i', $analysisid);
			$lr = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql2, array($analysisid));
			$lrow = $lr ? mysqli_fetch_array($lr, MYSQLI_ASSOC) : null;
			mysqli_stmt_close($stmt);
			$analysislevel = ((int)($lrow['pipeline_level'] ?? 0) === 2) ? 'groupanalysis' : 'analysis';

			/* queue the deletion for the fileio module. Use the logged-in user from the session:
			   ajaxapi.php reassigns the global $username to GetVariable("username") near the top, so
			   $GLOBALS['username'] is not the current user here. */
			$sql3 = "insert into fileio_requests (fileio_operation, group_id, data_type, data_id, username, requestdate) values ('delete', ?, ?, ?, ?, now())";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sql3);
			$params = array($groupid, $analysislevel, $analysisid, ($_SESSION['username'] ?? ''));
			mysqli_stmt_bind_param($stmt, 'isis', ...$params);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sql3, $params);
			mysqli_stmt_close($stmt);
			$deleted++;
		}
		echo json_encode(array('ok' => true, 'deleted' => $deleted));
	}

?>
