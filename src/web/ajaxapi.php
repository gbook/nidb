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
	$fileioIds = GetVariable("ids");
	$mappingid  = GetVariable("mappingid");
	$flagname   = GetVariable("flagname");
	$source_type          = GetVariable("source_type");
	$avicenna_question    = GetVariable("avicenna_question");
	$avicenna_variable    = GetVariable("avicenna_variable");
	$avicenna_survey      = GetVariable("avicenna_survey");
	$avicenna_datatype    = GetVariable("avicenna_datatype");
	$redcap_arm           = GetVariable("redcap_arm");
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
	
	$s['pipelineid'] = GetVariable("pipelineid");
	$s['dependency'] = GetVariable("dependency");
	$s['deplevel'] = GetVariable("deplevel");
	$s['groupid'] = GetVariable("groupid");
	$s['projectid'] = GetVariable("projectid");
	$s['dd_isprimary'] = GetVariable("dd_isprimary");
	$s['dd_enabled'] = GetVariable("dd_enabled");
	$s['dd_optional'] = GetVariable("dd_optional");
	$s['dd_order'] = GetVariable("dd_order");
	$s['dd_protocol'] = GetVariable("dd_protocol");
	$s['dd_modality'] = GetVariable("dd_modality");
	$s['dd_datalevel'] = GetVariable("dd_datalevel");
	$s['dd_studyassoc'] = GetVariable("dd_studyassoc");
	$s['dd_imagetype'] = GetVariable("dd_imagetype");
	$s['dd_seriescriteria'] = GetVariable("dd_seriescriteria");
	$s['dd_numboldreps'] = GetVariable("dd_numboldreps");

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
		//case 'sgejobstatus':
		//	DisplaySGEJobStatus($jobid);
		//	break;
		case 'remoteexportstatus':
			RemoteExportStatus($connectionid, $transactionid, $detail, $total);
			break;
		//case 'checkhost':
		//	CheckHostStatus($hostname);
		//	break;
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
		case 'getobservationmeta':
			GetObservationMeta($observationid);
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
			UpdateSurvey((int)$surveyid, $startdate, $enddate, $rater, $notes);
			break;
		case 'getfileiostatus':
			GetFileIOStatus($fileioIds);
			break;
		case 'getinstrumentitems':
			GetInstrumentItems((int)$instrumentid);
			break;
		case 'updatemappingflag':
			UpdateMappingFlag((int)$mappingid, $flagname, (int)$value);
			break;
		case 'savemapping':
			SaveMapping((int)$mappingid, (int)$projectid, $source_type, (int)$avicenna_question, $avicenna_variable, $avicenna_variablecount, $avicenna_survey, $avicenna_datatype, $redcap_arm, $redcap_event, $redcap_form, $redcap_field, $redcap_datatype, $redcap_datefield, (int)$nidb_instrument, (int)$nidb_variable, (int)$flag_date_from_field, (int)$flag_can_repeat, (int)$flag_import_meta);
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
	}
	

	/* ------------------------------------ functions ------------------------------------ */

	/* Discard all buffered output (debug HTML, notices, etc.) and set JSON content-type.
	   Call at the top of every function that returns JSON. */
	function JsonHeader() {
		while (ob_get_level() > 0) ob_end_clean();
		header('Content-Type: application/json');
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
	/* ------- SearchObservationNames ------------ */
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
		$searchuid = mysqli_real_escape_string($GLOBALS['linki'], trim($searchuid));
		
		$sqlstring = "select uid, subject_id, gender, year(birthdate) 'dobyear' from subjects where uid like '%$searchuid%'";
		$result = MySQLiQuery($sqlstring, __FILE__ , __LINE__);
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
		
		echo json_encode($a, JSON_FORCE_OBJECT);
	}


	/* -------------------------------------------- */
	/* ------- CheckHostStatus -------------------- */
	/* -------------------------------------------- */
	//function CheckHostStatus($hostname) {
	//	$hostname = trim($hostname);
	//	$hostname = preg_replace("/[^A-Za-z0-9 ]/", '', $hostname);
	//	exec("ping -c 1 '$hostname'", $output, $result);
	//	if ($result == 0)
	//		echo "1";
	//	else
	//		echo "0";
	//}


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
		$username = trim(mysqli_real_escape_string($GLOBALS['linki'], $username));

		$msg = "";

		$sqlstring = "select * from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__ , __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		
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
			<table class="graydisplaytable" width="100%">
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
			<img src="horizontalchart.php?b=yes&w=400&h=15&v=<?=$numsuccess?>,<?=$numprocessing?>,<?=$numfail?>,<?=($total-$numtotal)?>&c=<?=$completecolor?>,<?=$processingcolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($numsuccess/$total)*100,1)?>% received <span style="font-size:8pt;color:gray">(<?=number_format($numsuccess)?> of <?=number_format($total)?> blocks)</span>
			<br>
			<img src="horizontalchart.php?b=yes&w=400&h=15&v=<?=$archivesuccess?>,<?=$archiveerror?>,<?=($total-$archivesuccess-$archiveerror)?>&c=<?=$completecolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($archivesuccess/$total)*100,1)?>% archived <span style="font-size:8pt;color:gray">(<?=number_format($archivesuccess)?> of <?=number_format($total)?> blocks)</span>
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
	function PipelineTestSearch($s) {
		
		set_time_limit(30);
		
		/* setup the variables */
		$dd = array(); /* data definition */
		$pipelineid = trim($s['pipelineid']);
		$dependency = trim($s['dependency']);
		$deplevel = trim($s['deplevel']); // 'study' or 'subject'
		$groupids = trim($s['groupid']);
		$projectids = trim($s['projectid']);
		$dd['isprimary'] = trim($s['dd_isprimary']); // 'undefined' if not specified
		if ($dd['isprimary'] == 'undefined')
			$primaryindex = 0;
		else
			$primaryindex = $dd['isprimary'] - 1;
		$dd['enabled'] = explode(",", trim($s['dd_enabled']));
		$dd['optional'] = explode(",", trim($s['dd_optional']));
		$dd['order'] = explode(",", trim($s['dd_order']));
		$dd['protocol'] = explode(",", trim($s['dd_protocol']));
		$dd['modality'] = explode(",", trim($s['dd_modality']));
		$dd['datalevel'] = explode(",", trim($s['dd_datalevel']));
		$dd['studyassoc'] = explode(",", trim($s['dd_studyassoc']));
		$dd['imagetype'] = explode("|", trim($s['dd_imagetype']));
		$dd['seriescriteria'] = explode(",", trim($s['dd_seriescriteria']));
		$dd['numboldreps'] = explode(",", trim($s['dd_numboldreps']));
		
		/* reformat the datadef to be organized by step instead of by criteria */
		foreach ($dd['protocol'] as $key => $protocol) {
			$datadef[$key]['enabled'] = $dd['enabled'][$key];
			$datadef[$key]['optional'] = $dd['optional'][$key];
			$datadef[$key]['order'] = $dd['order'][$key];
			$datadef[$key]['protocol'] = $dd['protocol'][$key];
			$datadef[$key]['modality'] = $dd['modality'][$key];
			$datadef[$key]['datalevel'] = $dd['datalevel'][$key];
			$datadef[$key]['studyassoc'] = $dd['studyassoc'][$key];
			$datadef[$key]['imagetype'] = $dd['imagetype'][$key];
			$datadef[$key]['seriescriteria'] = $dd['seriescriteria'][$key];
			$datadef[$key]['numboldreps'] = $dd['numboldreps'][$key];
		}

		/* get names of projects, groups, and parent pipelines */
		if ($dependency == "") {
			$depname = "";
		}
		else {
			$sqlstring = "select pipeline_name from pipelines where pipeline_id = $dependency";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$depname = $row['pipeline_name'];
		}
		
		if ($groupids == "") {
			$groupname = "";
		}
		else {
			$sqlstring = "select group_name from groups where group_id in ($groupids)";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$groupnames[] = $row['group_name'];
			}
			$groupname = implode2(",", $groupnames);
		}
		
		if ($projectids == "") {
			$projectname = "";
		}
		else {
			$sqlstring = "select project_name from projects where project_id in ($projectids)";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$projectnames[] = $row['project_name'];
			}
			$projectname = implode2(",", $projectids);
		}
		
		$primarymodality = $dd['modality'][$primaryindex];
		
		/* start printing the search results */
		?>
		<style>
			.underlined { text-decoration: underline; text-decoration-style: dashed; text-decoration-color: #888; }
		</style>
		
		<table class="graydisplaytable">
			<thead>
				<tr>
					<th>Criteria<br><span class="tiny">Mouseover for description</span></th>
					<th>Value</th>
					<th>Matches</th>
					<th>Cumulative</th>
				</tr>
			</thead>
			<tbody>
			<?
			$studyids_existing = array();
			$studyids = array();
			$studyids_completedparent = array();
			$studyids_groups = array();
			$studyids_valid = array();
			$studyids_remaining = array();
			$studyids_step = array();
			
			/* ---------- LINE 1 - existing (already processed) studies for this pipeline ---------- */
			$sqlstring = "select study_id, analysis_status from analysis where pipeline_id = $pipelineid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$numExisting = 0;
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyids['existing'][$row['analysis_status']][] = $row['study_id'];
				$studyids_existing[] = $row['study_id'];
			}
			$studyids_existing = array_unique($studyids_existing);
			//PrintVariable($studyids_existing);
			PrintSearchRow("Existing (processed) studies", "Total number of studies that have already been processed for this pipeline... Not necessarily successful, but something has been done with them", "Total", count($studyids_existing), 0);
			/* individual status for existing studies */
			foreach ($studyids['existing'] as $status => $vals) {
				$studyids['existing'][$status] = array_unique($studyids['existing'][$status]);
				PrintSearchRow("$status", "Number of studies with a status of <b>$status</b>", "", count($studyids['existing'][$status],0), 0, false, true, true);
			}
			
			/* ---------- LINE 2 - completed dependencies ---------- */
			/* total completed studies from parent pipeline */
			if ($dependency == "") {
				PrintSearchRow("Completed dependent studies", "The total number of studies from the parent pipeline that are <b>complete</b> and are <b>not marked as bad</b>.", "No parent pipeline", "-", "-", false);
			}
			else {
				$sqlstringA = "select study_id from analysis where pipeline_id = $dependency and analysis_status = 'complete' and (analysis_isbad <> 1 or analysis_isbad is null)";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$studyids_completedparent[] = $rowA['study_id'];
				}
				$studyids_completedparent = array_unique($studyids_completedparent);
				$cumtotal = count(array_diff($studyids_completedparent, $studyids_existing));
				PrintSearchRow("Completed dependent studies", "The total number of studies from the parent pipeline that are <b>complete</b> and are <b>not marked as bad</b>.", "$depname", count($studyids_completedparent), $cumtotal, false);
			}
			
			/* ---------- LINE 3 - groups ---------- */
			if ($groupids == "") {
				PrintSearchRow("Group", "Total number of studies in the selected groups", "No groups", "-", "-");
			}
			else {
				$sqlstring = "select data_id from group_data where group_id in ($groupids)";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$studyids_groups[] = $row['data_id'];
				}
				$studyids_groups = array_unique($studyids_groups);
				if (count($studyids_completedparent) > 0) {
					$cumtotal = count(array_diff(array_intersect($studyids_completedparent, $studyids_groups), $studyids_existing));
				}
				else {
					$cumtotal = count(array_diff($studyids_groups, $studyids_existing));
				}
				PrintSearchRow("Group", "Total number of studies in the selected groups", $groupname, count($studyids_groups), $cumtotal);
			}
			
			/* ---------- LINE 3.5 - projects ---------- */
			//$sqlstring = "select study_id from group_data where group_id in ($projectids)";
			//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			//while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			//	$studyids['groups'][] = $row['data_id'];
			//}
			//$studyids['groups'] = array_unique($studyids['groups']);
			//$cumtotal = count(array_intersect($studyids['completedparent'], $studyids['groups']));
			//PrintSearchRow("Group", "Total number of studies in the selected groups", $groupname, $studyids['groups'], $cumtotal);

			/* ---------- LINE 4 - valid studies of primary modality ---------- */
			$sqlstring = "select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where (a.study_datetime < date_sub(now(), interval 6 hour)) and a.study_modality = '$primarymodality' and c.isactive = 1";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$studyids_valid[] = $row['study_id'];
			}
			$studyids_valid = array_unique($studyids_valid);

			if ((count($studyids_groups) > 0) && (count($studyids_completedparent) > 0)) {
				$studyids_remaining = array_diff(array_intersect($studyids_completedparent, $studyids_groups, $studyids_valid), $studyids_existing);
			}
			elseif (count($studyids_groups) > 0) {
				$studyids_remaining = array_diff(array_intersect($studyids_groups, $studyids_valid), $studyids_existing);
			}
			elseif (count($studyids_completedparent) > 0) {
				$studyids_remaining = array_diff(array_intersect($studyids_completedparent, $studyids_valid), $studyids_existing);
			}
			else {
				$studyids_remaining = array_diff($studyids_valid, $studyids_existing);
			}
			$cumtotal = count($studyids_remaining);
			PrintSearchRow("Valid $primarymodality studies", "Valid studies for non-deleted subjects, collected more than 6 hours ago", "", count($studyids_valid), $cumtotal);

			/* ---------- LINE 5 - remaining studies to be processed ---------- */
			PrintSearchRow("Remaining studies", "Remaining studies to be processed. These will be checked for valid data", "", $cumtotal, $cumtotal, true);
			
			/* ---------- LINE 6+ - check the data steps ---------- */
			foreach ($datadef as $step => $details) {
				$enabled = $details['enabled'];
				//$optional = $details['optional']; /* doesn't matter if optional */
				$order = $details['order'];
				$protocol = $details['protocol'];
				$modality = strtolower($details['modality']);
				$datalevel = $details['datalevel'];
				$studyassoc = $details['studyassoc'];
				$imagetype = $details['imagetype'];
				//$seriescriteria = $details['seriescriteria'];
				$numboldreps = $details['numboldreps'];
				
				/* check if enabled */
				if (!$enabled) {
					PrintSearchRow("Data step $step", str_replace('"', "'", "$protocol"), "Not enabled", "-", "-", false, true, true);
					continue;
				}
				if ($protocol == "")
					continue;

				/* check if the modality exists */
				$sqlstring = "show tables like '$modality"."_series'";
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				if (mysqli_num_rows($result) < 1)
					continue;
				
				/* get the correct search description field */
				$seriesdescfield = "series_protocol";
				if ($modality == "mr")
					$seriesdescfield = "series_desc";
				
				/* prepare the protocol name(s) for SQL. Seperate any protocols that have multiples */
				if (contains($protocol,'"')) {
					$prots = ShellWords($protocol);
					$protocols = "'" . implode2("','", $prots) . "'";
				}
				else
					$protocols = "'" . $protocol . "'";
				
				/* prepare image type(s) for SQL */
				//PrintVariable($imagetype);
				if (contains($imagetype, ",")) {
					$types = preg_split("/,[\s,]+/", $imagetype);
					$imagetypes = "'" . implode("','", $types) . "'";
				}
				else
					if (trim($imagetype) != "")
						$imagetypes = "'$imagetype'";
				
				/* prepare the numboldreps comparison */
				list($comp, $num) = GetSQLComparison($numboldreps);
				
				/* check each of the studies from the previous list */
				foreach ($studyids_remaining as $studyid) {
					list($path, $uid, $studynum, $studyid, $subjectid, $modality, $studytype, $studydatetime, $enrollmentid, $projectname, $projectid) = GetStudyInfo($studyid);
					$modality = strtolower($modality);
					
					if ($datalevel == "study") {
						/* if study level, check the study for criteria */
						$sqlstring = "select a.study_id from studies a left join $modality" . "_series b on a.study_id = b.study_id where b.$seriesdescfield in ($protocols)";
						if ($imagetypes != "")
							$sqlstring .= " and b.image_type in ($imagetypes)";
						if ($numboldreps != "")
							$sqlstring .= " and b.numfiles $comp $num";
					}
					else {
						/* if subject level, check the subject for the criteria */
						
						if (($studyassoc == "nearesttime") || ($studyassoc == "nearestintime")) {
							/* find the data from the same subject and modality that has the nearest (in time) matching scan */
							echo "Searching for data from the same SUBJECT and modality that has the nearest (in time) matching scan<br>";

							$sqlstring = "SELECT d.study_id FROM enrollment a JOIN projects b on a.project_id = b.project_id JOIN subjects c on c.subject_id = a.subject_id JOIN studies d on d.enrollment_id = a.enrollment_id JOIN $modality"."_series e on e.study_id = d.study_id WHERE c.isactive = 1 AND d.study_modality = '$modality' AND c.subject_id = $subjectid AND trim(e.$seriesdescfield) in ($protocols)";
							if (($imagetypes != "") && ($imagetypes != "''"))
								$sqlstring .= " and b.image_type in ($imagetypes)";
						}
						else if ($studyassoc == "all") {
							echo "Searching for ALL data from the same SUBJECT and modality<br>";
							$sqlstring = "SELECT d.study_id FROM enrollment a JOIN projects b on a.project_id = b.project_id JOIN subjects c on c.subject_id = a.subject_id JOIN studies d on d.enrollment_id = a.enrollment_id JOIN $modality"."_series e on e.study_id = d.study_id WHERE c.isactive = 1 AND d.study_modality = '$modality' AND c.subject_id = $subjectid AND trim(e.$seriesdescfield) in ($protocols)";
							if (($imagetypes != "") && ($imagetypes != "''"))
								$sqlstring .= " and b.image_type in ($imagetypes)";
						}
						else {
							/* find the data from the same subject and modality that has the same study_type */
							echo "Searching for data from the same SUBJECT, Modality, and StudyType<br>";

							$sqlstring = "SELECT d.study_id FROM enrollment a JOIN projects b on a.project_id = b.project_id JOIN subjects c on c.subject_id = a.subject_id JOIN studies d on d.enrollment_id = a.enrollment_id JOIN $modality"."_series e on e.study_id = d.study_id WHERE c.isactive = 1 AND d.study_modality = '$modality' AND c.subject_id = $subjectid AND trim(e.$seriesdescfield) in ($protocols)";

							if (($imagetypes != "") && ($imagetypes != "''"))
								$sqlstring .= " and b.image_type in ($imagetypes)";

							if ($studytype != "")
								$sqlstring += " and d.study_type = '$studytype'";
						}
					}
					//PrintSQL($sqlstring);
					//break;
					$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
					if (mysqli_num_rows($result) > 0) {
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$studyids_step[$step][] = $row['study_id'];
						}
					}
					$studyids_step[$step] = array_unique($studyids_step[$step]);
					$cumtotal = count(array_diff($studyids_step[$step], $studyids_existing));
				}
				PrintSearchRow("[$datalevel level] data step $step", $protocol, "-", count($studyids_step[$step]), $cumtotal);
				
			}
		// /* if it's a subject level dependency, but there is no data found, we don't want to copy any dependencies */
		// if ((stepIsInvalid) && (deplevel == "subject")) {
			// dlog << " ********** One of the required steps was invalid because no data was found based on the search criteria. (This was a subject-level dependency) No data will be downloaded. **********";
			// datalog = dlog.join("\n");
			// return false;
		// }

		// /* if there is a dependency, don't worry about the previous checks */
		// if (pipelinedep != -1)
			// stepIsInvalid = false;

		// /* any bad data items, then the data spec didn't work out for this subject */
		// if (stepIsInvalid) {
			// dlog << " ********** One of the required steps was invalid because no data was found for the search criteria. No data will be downloaded.";
			// datalog = dlog.join("\n");
			// return false;
		// }

		// /* ------ end checking the data steps --------------------------------------
			// if we get to here, the data spec is valid for this study
			// so we can assume all of the data exists, and start copying it
		   // ------------------------------------------------------------------------- */
		?>
			</tbody>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- PrintSearchRow --------------------- */
	/* -------------------------------------------- */
	function PrintSearchRow($criteria, $title, $value, $nummatch, $cummatch, $bold=false, $indent=false, $gray=false) {
		if ($title) { $title = "title='$title' class='underlined'"; } else { $title = ""; }
		if ($bold) { $bold = "font-weight: bold;"; } else { $bold = ""; }
		if ($indent) { $indent = "padding-left: 20px;"; } else { $indent = ""; }
		if ($gray) { $gray = "color: #999; font-size: 9pt;"; } else { $gray = ""; }
		
		if ($nummatch != "-")
			$nummatch = number_format($nummatch,0);
		
		if ($cummatch != "-")
			$cummatch = number_format($cummatch,0);
		
		?>
		<tr style="<?=$bold?>">
			<td style="<?=$indent?> <?=$gray?>" <?=$title?>><?=$criteria?></td>
			<td style="<?=$gray?>"><?=$value?></td>
			<td style="<?=$gray?>"><?=$nummatch?></td>
			<td style="<?=$gray?>"><?=$cummatch?></td>
		</tr>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- UpdateSubjectDetails --------------- */
	/* -------------------------------------------- */
	function UpdateSubjectDetails($subjectid, $projectid, $column, $value) {

		$subjectid = (int)$subjectid;
		$projectid = (int)$projectid;
		$column = trim(mysqli_real_escape_string($GLOBALS['linki'], $column));
		$value = trim(mysqli_real_escape_string($GLOBALS['linki'], $value));

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
					if (strpos($altuid, '*') !== FALSE) {
						$altuid = str_replace('*','',$altuid);
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',1, '$enrollmentid')";
					}
					else {
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',0, '$enrollmentid')";
					}
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
			CommitSQLTransaction();
		}
		elseif ($column == "enrollgroup") {
			$sqlstring = "update enrollment set enroll_subgroup = '$value' where project_id = $projectid and subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		}
		elseif ($column == "enrollstatus") {
			$sqlstring = "update enrollment set enroll_status = '$value' where project_id = $projectid and subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
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
			$sqlstring .= " = '$value' where subject_id = $subjectid";
			
			//echo "$sqlstring";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		}
		
		echo "success";
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyDetails ----------------- */
	/* -------------------------------------------- */
	function UpdateStudyDetails($subjectid, $studyid, $column, $value) {

		$subjectid = (int)$subjectid;
		$studyid = (int)$studyid;
		$column = trim(mysqli_real_escape_string($GLOBALS['linki'], $column));
		$value = trim(mysqli_real_escape_string($GLOBALS['linki'], $value));

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
					if (strpos($altuid, '*') !== FALSE) {
						$altuid = str_replace('*','',$altuid);
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',1, '$enrollmentid')";
					}
					else {
						$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ($subjectid, '$altuid',0, '$enrollmentid')";
					}
					//echo $sqlstring;
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
			CommitSQLTransaction();
		}
		elseif ($column == "sex") {
			$sqlstring = "update subjects set sex = '$value' where subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		}
		elseif ($column == "gender") {
			$sqlstring = "update subjects set gender = '$value' where subject_id = $subjectid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
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
			$sqlstring .= " = '$value' where study_id = $studyid";
			
			//echo "$sqlstring";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
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
	/* ------- GetObservationMeta ---------------- */
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
	/* ------- BulkUpdateObservations ------------ */
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
	/* ------- BulkDeleteObservations ------------ */
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
	/* ------- BulkMoveToNewSurvey --------------- */
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
	/* ------- BulkConvertValueToMeta ------------ */
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
		$startdate_sql    = !empty(trim($startdate))    ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $startdate) . "'" : "null";
		$enddate_sql      = !empty(trim($enddate))      ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $enddate) . "'"   : "null";
		$rater_sql        = !empty(trim($rater))        ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $rater) . "'"     : "null";
		$notes_sql        = !empty(trim($notes))        ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $notes) . "'"     : "null";
		$instrumentid_sql = ($instrumentid > 0)         ? $instrumentid : "null";

		$sqlstring = "insert into observation_surveys (instrument_id, survey_startdate, survey_enddate, survey_rater, survey_notes, survey_entrydate) values ($instrumentid_sql, $startdate_sql, $enddate_sql, $rater_sql, $notes_sql, now())";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$surveyid = mysqli_insert_id($GLOBALS['linki']);

		$idList = implode(',', array_map('intval', $ids));
		$sqlstring = "update observations set observationsurvey_id = $surveyid where observation_id in ($idList)";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);

		echo json_encode(array('success' => true, 'survey_id' => (int)$surveyid));
	}


	/* -------------------------------------------- */
	/* ------- UpdateSurvey ----------------------- */
	/* -------------------------------------------- */
	/* updates metadata fields on an existing observation_surveys record */
	function UpdateSurvey($surveyid, $startdate, $enddate, $rater, $notes) {
		JsonHeader();
		if ($surveyid <= 0) {
			echo json_encode(array('error' => 'invalid survey_id'));
			return;
		}
		$startdate_sql = !empty(trim($startdate)) ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $startdate) . "'" : "null";
		$enddate_sql   = !empty(trim($enddate))   ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $enddate) . "'"   : "null";
		$rater_sql     = !empty(trim($rater))     ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $rater) . "'"     : "null";
		$notes_sql     = !empty(trim($notes))     ? "'" . mysqli_real_escape_string($GLOBALS['linki'], $notes) . "'"     : "null";

		$sqlstring = "update observation_surveys set survey_startdate = $startdate_sql, survey_enddate = $enddate_sql, survey_rater = $rater_sql, survey_notes = $notes_sql where survey_id = $surveyid";
		MySQLiQuery($sqlstring, __FILE__, __LINE__);

		echo json_encode(array('success' => true));
	}


	/* -------------------------------------------- */
	/* ------- GetFileIOStatus -------------------- */
	/* -------------------------------------------- */
	/* -------------------------------------------- */
	/* ------- GetInstrumentItems ---------------- */
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
	/* ------- UpdateMappingFlag ----------------- */
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
	/* ------- SaveMapping ----------------------- */
	/* -------------------------------------------- */
	function SaveMapping($mappingid, $projectid, $source_type, $avicenna_question, $avicenna_variable, $avicenna_variablecount, $avicenna_survey, $avicenna_datatype, $redcap_arm, $redcap_event, $redcap_form, $redcap_field, $redcap_datatype, $redcap_datefield, $nidb_instrument, $nidb_variable, $flag_date_from_field, $flag_can_repeat, $flag_import_meta) {
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
		$avicenna_datatype_val      = $avicenna_datatype     !== '' ? $avicenna_datatype      : null;
		$redcap_arm_val        = $redcap_arm        !== '' ? $redcap_arm        : null;
		$redcap_event_val      = $redcap_event      !== '' ? $redcap_event      : null;
		$redcap_form_val       = $redcap_form       !== '' ? $redcap_form       : null;
		$redcap_field_val      = $redcap_field      !== '' ? $redcap_field      : null;
		$allowed_dt = ['text','notes','radio','dropdown','checkbox','calc','slider','descriptive','file'];
		$redcap_datatype_val   = in_array($redcap_datatype, $allowed_dt, true) ? $redcap_datatype : null;
		$redcap_datefield_val  = $redcap_datefield  !== '' ? $redcap_datefield  : null;
		$fdf = $flag_date_from_field ? 1 : 0;
		$fcr = $flag_can_repeat      ? 1 : 0;
		$fim = $flag_import_meta     ? 1 : 0;

		if ($mappingid > 0) {
			// Update existing
			$stmt = mysqli_prepare($GLOBALS['linki'],
				"UPDATE remoteimport_mapping SET avicenna_question=?, avicenna_variable=?, avicenna_variablecount=?, avicenna_survey=?, avicenna_datatype=?, redcap_arm=?, redcap_event=?, redcap_form=?, redcap_field=?, redcap_datatype=?, redcap_datefield=?, nidb_instrument=?, nidb_variable=?, flag_date_from_field=?, flag_can_repeat=?, flag_import_meta=? WHERE remoteimportmapping_id=? AND project_id=?");
			mysqli_stmt_bind_param($stmt, 'iss' . 'ss' . 'ssssss' . 'iiiiiii',
				$avicenna_question_val, $avicenna_variable_val, $avicenna_variablecount_val, $avicenna_survey_val, $avicenna_datatype_val, $redcap_arm_val, $redcap_event_val, $redcap_form_val, $redcap_field_val, $redcap_datatype_val, $redcap_datefield_val,
				$nidb_instrument_val, $nidb_variable_val, $fdf, $fcr, $fim, $mappingid, $projectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			echo json_encode(['ok' => true, 'mappingid' => $mappingid]);
		} else {
			// Insert new
			$stmt = mysqli_prepare($GLOBALS['linki'],
				"INSERT INTO remoteimport_mapping (project_id, source_type, avicenna_question, avicenna_variable, avicenna_variablecount, avicenna_survey, avicenna_datatype, redcap_arm, redcap_event, redcap_form, redcap_field, redcap_datatype, redcap_datefield, nidb_instrument, nidb_variable, flag_date_from_field, flag_can_repeat, flag_import_meta) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			mysqli_stmt_bind_param($stmt, 'isiss' . 'ss' . 'ssssss' . 'iiiii',
				$projectid, $source_type, $avicenna_question_val, $avicenna_variable_val, $avicenna_variablecount_val, $avicenna_survey_val, $avicenna_datatype_val, $redcap_arm_val, $redcap_event_val, $redcap_form_val, $redcap_field_val, $redcap_datatype_val, $redcap_datefield_val,
				$nidb_instrument_val, $nidb_variable_val, $fdf, $fcr, $fim);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$newid = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
			echo json_encode(['ok' => true, 'mappingid' => (int)$newid]);
		}
	}


	/* -------------------------------------------- */
	/* ------- DeleteMapping --------------------- */
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
	/* ------- BulkDeleteMappings ---------------- */
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


	/* ------- BulkDeleteItems ------------------- */
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


	function GetFileIOStatus($idsJson) {
		JsonHeader();
		$idArray = json_decode($idsJson, true);
		if (!is_array($idArray) || count($idArray) === 0) {
			echo json_encode([]);
			return;
		}
		$cleanIds = array_filter(array_map('intval', $idArray), function($id) { return $id > 0; });
		if (count($cleanIds) === 0) {
			echo json_encode([]);
			return;
		}
		$idList = implode(',', $cleanIds);
		$sqlstring = "SELECT fileiorequest_id, request_status, request_message, startdate, enddate FROM fileio_requests WHERE fileiorequest_id IN ($idList)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$rows = [];
		if ($result && !is_array($result)) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$duration = '';
				$validStart = ($row['startdate'] !== '0000-00-00 00:00:00' && $row['startdate'] !== '');
				$validEnd   = ($row['enddate']   !== '0000-00-00 00:00:00' && $row['enddate']   !== '');
				if ($validStart && $validEnd) {
					$diff = strtotime($row['enddate']) - strtotime($row['startdate']);
					if ($diff > 0) {
						$h = (int)floor($diff / 3600);
						$m = (int)floor(($diff % 3600) / 60);
						$s = (int)($diff % 60);
						if ($h > 0)     $duration = "{$h}h {$m}m {$s}s";
						elseif ($m > 0) $duration = "{$m}m {$s}s";
						else            $duration = "{$s}s";
					}
				}
				$rows[] = [
					'fileiorequest_id' => (int)$row['fileiorequest_id'],
					'request_status'   => $row['request_status'],
					'request_message'  => $row['request_message'],
					'enddate'          => $row['enddate'],
					'duration'         => $duration,
				];
			}
		}
		echo json_encode($rows);
	}

?>
