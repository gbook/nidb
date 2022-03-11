<?
 // ------------------------------------------------------------------------------
 // NiDB ajaxapi.php
 // Copyright (C) 2004 - 2021
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
		case 'validatepath':
			ValidatePath($nfspath);
			break;
		case 'sgejobstatus':
			DisplaySGEJobStatus($jobid);
			break;
		case 'remoteexportstatus':
			RemoteExportStatus($connectionid, $transactionid, $detail, $total);
			break;
		case 'checkhost':
			CheckHostStatus($hostname);
			break;
		case 'checksgehost':
			CheckSGESubmitStatus($hostname);
			break;
	}
	

	/* ------------------------------------ functions ------------------------------------ */


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
	function CheckHostStatus($hostname) {
		
		$hostname = trim($hostname);
		$hostname = preg_replace("/[^A-Za-z0-9 ]/", '', $hostname);

		exec("ping -c 1 '$hostname'", $output, $result);
		
		if ($result == 0)
			echo "1";
		else
			echo "0";
	}


	/* -------------------------------------------- */
	/* ------- CheckSGESubmitStatus --------------- */
	/* -------------------------------------------- */
	function CheckSGESubmitStatus($hostname) {
		
		$hostname = trim($hostname);
		$hostname = preg_replace("/[^A-Za-z0-9 ]/", '', $hostname);

		exec("ssh '$hostname' which qsub", $output, $result);
		
		if ($result == 0) {
			echo "1";
			print_r($output);
		}
		else {
			echo "0";
			print_r($output);
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
		if (strpos($p, "..") !== false) {
			$msg = "Contains relative directory (..)";
		}
		else if (strpos($p, '\\') !== false) {
			$msg = "Contains backslash";
		}
		else if ($p == "/") {
			$msg = "Path is the root dir (/)";
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
		if ($exists && $writeable) { $color = "green"; } else { $color = "red"; }
		echo " <span style='color: $color'>$msg</span>";
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
?>