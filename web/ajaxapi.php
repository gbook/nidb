<?
 // ------------------------------------------------------------------------------
 // NiDB ajaxapi.php
 // Copyright (C) 2004 - 2020
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

	PrintVariable($_GET);
	
	$action = GetVariable("action");
	$nfspath = GetVariable("nfspath");
	$connectionid = GetVariable("connectionid");
	$transactionid = GetVariable("transactionid");
	$detail = GetVariable("detail");
	$total = GetVariable("total");
	$jobid = GetVariable("jobid");
	
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
		case 'validatepath':
			ValidatePath($nfspath);
			break;
		case 'sgejobstatus':
			DisplaySGEJobStatus($jobid);
			break;
		case 'remoteexportstatus':
			RemoteExportStatus($connectionid, $transactionid, $detail, $total);
			break;
	}
	

	/* ------------------------------------ functions ------------------------------------ */

	
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
			<table class="graydisplaytable" width="100%">
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
				$cumtotal = count(array_diff(array_intersect($studyids_completedparent, $studyids_groups, $studyids_valid), $studyids_existing));
			}
			elseif (count($studyids_groups) > 0) {
				$cumtotal = count(array_diff(array_intersect($studyids_groups, $studyids_valid), $studyids_existing));
			}
			elseif (count($studyids_completedparent) > 0) {
				$cumtotal = count(array_diff(array_intersect($studyids_completedparent, $studyids_valid), $studyids_existing));
			}
			else {
				$cumtotal = count(array_diff($studyids_valid, $studyids_existing));
			}
			PrintSearchRow("Valid $primarymodality studies", "Valid studies for non-deleted subjects, collected more than 6 hours ago", "", count($studyids_valid), $cumtotal);

			/* ---------- LINE 5 - remaining studies to be processed ---------- */
			PrintSearchRow("Remaining studies", "Remaining studies to be processed. These will be checked for valid data", "", $cumtotal, $cumtotal, true);
			
			/* now check the data steps */
			//PrintVariable($datadef);
		
			foreach ($datadef as $step => $details) {
				//PrintVariable($details);
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
				
				//PrintVariable($protocol);
				/* prepare the protocol name(s) for SQL. Seperate any protocols that have multiples */
				if (contains($protocol,'"')) {
					//echo "[$protocol] contains a \"";
					$prots = ShellWords($protocol);
					//PrintVariable($prots);
					$protocols = "'" . implode2("','", $prots) . "'";
				}
				else
					$protocols = "'" . $protocol . "'";
				//PrintVariable($protocols);
				
				/* prepare image type(s) for SQL */
				PrintVariable($imagetype);
				if (contains($imagetype, ",")) {
					$types = preg_split("/,[\s,]+/", $imagetype);
					//PrintVariable($types);
					//foreach ($types as $i => $type) {
					//	$types[$i] = str_replace("\\", "\\\\", $types[$i]);
					//}
					$imagetypes = "'" . implode("','", $types) . "'";
				}
				else
					if (trim($imagetype) != "")
						$imagetypes = "'$imagetype'";
				
				/* prepare the numboldreps comparison */
				list($comp, $num) = GetSQLComparison($numboldreps);
				
				/* if study level, check the study for criteria */
				$sqlstring = "select a.study_id from studies a left join $modality" . "_series b on a.study_id = b.study_id where b.$seriesdescfield in ($protocols)";
				if ($imagetypes != "")
					$sqlstring .= " and b.image_type in ($imagetypes)";
				if ($numboldreps != "")
					$sqlstring .= " and b.numfiles $comp $num";
				PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				if (mysqli_num_rows($result) > 0) {
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$studyids_step[$order][] = $row['study_id'];
					}
				}
				$studyids_step[$order] = array_unique($studyids_step[$order]);
				$cumtotal = count(array_diff($studyids_step[$order], $studyids_existing));
				
				PrintSearchRow("Data step $step", $protocol, "-", count($studyids_step[$order]), $cumtotal);
				/* if subject level, check the subject for the criteria */
			}
			//PrintVariable($studyids_step);
			
			
		// for (int i = 0; i < datadef.size(); i++) {
			// QString protocol = datadef[i].protocol;
			// QString modality = datadef[i].modality.toLower();
			// QString imagetype = datadef[i].imagetype;
			// bool enabled = datadef[i].enabled;
			// QString type = datadef[i].type;
			// QString level = datadef[i].level;
			// QString assoctype = datadef[i].assoctype;
			// bool optional = datadef[i].optional;
			// QString numboldreps = datadef[i].numboldreps;

			// dlog << QString("   Checking if the following data exist:   protocol [%2]  modality [%3]  imagetype [%4]  enabled [%5]  type [%6]  level [%7]  assoctype [%8]  optional [%9]  numboldreps [%10]").arg(i).arg(protocol).arg(modality).arg(imagetype).arg(enabled).arg(type).arg(level).arg(assoctype).arg(optional).arg(numboldreps);

			// /* expand the comparison into SQL */
			// QString comparison;
			// int num(0);
			// bool validComparisonStr = false;
			// if (n->GetSQLComparison(numboldreps, comparison, num))
				// validComparisonStr = true;

			// /* if its a subject level, check the subject for the protocol(s) */
			// int subjectid = s.subjectid;
			// QString studydate = s.studydatetime.toString("yyyy-MM-dd hh:mm:ss");
			// if (level == "subject") {
				// dlog << "   Note: this data step is subject level [" + protocol + "], association type [" + assoctype + "]";

				// QString sqlstring;
				// if ((assoctype == "nearesttime") || (assoctype == "nearestintime")) {
					// /* find the data from the same subject and modality that has the nearest (in time) matching scan */
					// dlog << QString("   Searching for data from the same SUBJECT and modality that has the nearest (in time) matching scan");

					// sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

					// if (imagetypes != "''")
						// sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

					// sqlstring += QString(" ORDER BY ABS( DATEDIFF( `%1_series`.series_datetime, '%2' ) ) LIMIT 1").arg(modality).arg(studydate);

					// q.prepare(sqlstring);
					// q.bindValue(":subjectid", subjectid);
				// }
				// else if (assoctype == "all") {
					// dlog << QString("   Searching for ALL data from the same SUBJECT and modality");

					// sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

					// if (imagetypes != "''")
						// sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

					// q.prepare(sqlstring);
					// q.bindValue(":subjectid", subjectid);
				// }
				// else {
					// /* find the data from the same subject and modality that has the same study_type */
					// dlog << QString("   Searching for data from the same SUBJECT, Modality, and StudyType");

					// sqlstring = QString("SELECT *, `%1_series`.%1series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `%1_series` on `%1_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '%1' AND `subjects`.subject_id = :subjectid AND trim(`%1_series`.%2) in (%3)").arg(modality).arg(seriesdescfield).arg(protocols);

					// if (imagetypes != "''")
						// sqlstring += QString(" and `%1_series`.image_type in (%2)").arg(modality).arg(imagetypes);

					// sqlstring += " and `studies`.study_type = :studytype";

					// q.prepare(sqlstring);
					// q.bindValue(":subjectid", subjectid);
					// q.bindValue(":studytype", studytype);
				// }

				// dlog << n->WriteLog("   SQL used for this search (for debugging) [" + n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__,true) + "]");
				// if (q.size() > 0) {
					// dlog << QString("   Data FOUND for step [%1] (subject level)").arg(i);
					// RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, -1, "", i, "Data found for this step (subject level)");
				// }
				// else {
					// dlog << QString("   Data NOT found for step [%1] (subject level)").arg(i);
					// RecordDataDownload(datadownloadid, analysisid, modality, 1, 0, -1, "", i, "Data NOT found for this step (subject level). Stopping search");
					// stepIsInvalid = true;
					// break;
				// }
			// }
			// /* otherwise, check the study for the protocol(s) */
			// else {
				// QString sqlstring;
				// dlog << QString("   Checking the study [%1] for the protocol (%2)").arg(studyid).arg(protocols);
				// /* get a list of series satisfying the search criteria, if it exists */
				// sqlstring = QString("select * from %1_series where study_id = :studyid and (trim(%2) in (%3))").arg(modality).arg(seriesdescfield).arg(protocols);
				// if (imagetypes != "''") {
					// sqlstring += " and image_type in (" + imagetypes + ")";
				// }
				// if (validComparisonStr)
					// sqlstring += QString(" and numfiles %1 %2").arg(comparison).arg(num);

				// q.prepare(sqlstring);
				// q.bindValue(":studyid", studyid);
				// n->SQLQuery(q, __FUNCTION__, __FILE__, __LINE__);
				// if (q.size() > 0) {
					// dlog << QString("   Data found for step [%1] - protocol [%2] (study level)").arg(i).arg(protocol);
					// RecordDataDownload(datadownloadid, analysisid, modality, 1, 1, -1, "", i, "Data found for this step (study level)");
				// }
				// else {
					// dlog << QString("   Data NOT found for step [%1] - protocol [%2] (study level). Stopping search for this step").arg(i).arg(protocol);
					// RecordDataDownload(datadownloadid, analysisid, modality, 1, 0, -1, "", i, "Data NOT found for this step (study level). Stopping search");
					// stepIsInvalid = true;
					// break;
				// }
			// }
		// }
		// dlog << "\n ********** Done checking data steps **********";

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