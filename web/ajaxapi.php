<?
 // ------------------------------------------------------------------------------
 // NiDB ajaxapi.php
 // Copyright (C) 2004 - 2019
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
	session_start();
	require "functions.php";

	$action = GetVariable("action");
	$nfspath = GetVariable("nfspath");
	$connectionid = GetVariable("connectionid");
	$transactionid = GetVariable("transactionid");
	$detail = GetVariable("detail");
	$total = GetVariable("total");
	$jobid = GetVariable("jobid");

	/* determine action */
	switch($action) {
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
?>