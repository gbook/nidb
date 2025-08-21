<?
 // ------------------------------------------------------------------------------
 // NiDB importlog.php
 // Copyright (C) 2004 - 2022
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
		<title>NiDB - Import Log</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$useronly = GetVariable("useronly");
	$transactionid = GetVariable("transactionid");
	
	/* determine action */
	switch ($action) {
		case 'viewimported':
			DisplayMenu();
			DisplayAllImportLog();
			break;
		case 'viewtransactions':
			DisplayMenu();
			DisplayTransactions($useronly);
			break;
		case 'viewuploadblocks':
			DisplayMenu();
			DisplayUploadBlocks($transactionid);
			break;
		default:
			DisplayMenu();
			DisplayTransactions($useronly);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
		//$urllist['Administration'] = "importlog.php";
		//$urllist['Import Log'] = "importlog.php";
		//NavigationBar("Admin", $urllist);
		
		?>
		<a href="importlog.php?action=viewtransactions&useronly=1">My Uploads</a> | <a href="importlog.php?action=viewtransactions&useronly=0">All Uploads</a><br>
		<br><br>
		<?
	}
		
	
	/* -------------------------------------------- */
	/* ------- DisplayAllImportLog ---------------- */
	/* -------------------------------------------- */
	function DisplayAllImportLog() {

		$sqlstring = "select *, timediff(max(importstartdate), min(importstartdate)) 'importtime', date_format(max(importstartdate), '%b %e, %Y %T') 'maximportdatetime', date_format(studydatetime_orig, '%b %e, %Y %T') 'studydatetime', date_format(seriesdatetime_orig, '%b %e, %Y %T') 'seriesdatetime', count(*) 'numfiles' from importlogs group by stationname_orig, studydatetime_orig, seriesnumber_orig order by studydatetime_orig desc, seriesdatetime_orig";
		?>
		
<style>
.text {
   position:relative;
   width:20px;
   height:170px;
   /*border:1px solid rgba(0,0,0,0.5);*/
   /*border-radius:7px; */
   /*margin:20px auto; */
   /*background-color:rgb(255,255,255); */
   /*box-shadow:inset 0 0 10px rgba(0,0,0,0.6),
                    0 0 10px rgba(0,0,0,0.6);*/
  }
.text span {
   position:absolute;
   width:170px;
   line-height:20px;
   left:0;
   top:100%;
   transform:rotate(-90deg); 
   -webkit-transform:rotate(-90deg); 
   transform-origin:0 0;
   -webkit-transform-origin:0 0;
   text-align:left;
   vertical-align: middle;
  }
</style>		
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th valign="bottom">Initials</th>
					<th>Patient ID</th>
					<!--<th>Institution</th>-->
					<th>Equipment</th>
					<th>Modality</th>
					<th>Study date</th>
					<th>Series date</th>
					<th>Series Num</th>
					<th>Num files</th>
					<th> </th>
					<th>New UID</th>
					<th>Study Num</th>
					<th>Project</th>
					<th>Import time<br><span class="tiny">HH:MM:SS</span></th>
					<th>Import complete</th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created subject</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created family</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created enrollment</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created study</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created series</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Overwrote existing data</span></div></th>
				</tr>
			</thead>
			<tbody>
		<?
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$patientid = $row['patientid_orig'];
			$patientname = $row['patientname_orig'];
			$institution = $row['institution_orig'];
			$equipment = $row['stationname_orig'];
			$modality = $row['modality_orig'];
			$studydatetime = $row['studydatetime'];
			$seriesdatetime = $row['seriesdatetime'];
			$seriesnum = $row['seriesnumber_orig'];
			$numfiles = $row['numfiles'];
			$importtime = $row['importtime'];
			$minimportdatetime = $row['minimportdatetime'];
			$maximportdatetime = $row['maximportdatetime'];

			$subjectid = $row['subjectid'];
			$newuid = $row['subject_uid'];
			$newstudynum = $row['study_num'];
			$project = $row['project_number'];
			
			$subjectcreated = $row['subject_created'];
			$familycreated = $row['family_created'];
			$enrollmentcreated = $row['enrollment_created'];
			$studycreated = $row['study_created'];
			$seriescreated = $row['series_created'];
			$overwroteexisting = $row['overwrote_existing'];

			/* fix patient name, to only show initials */
			$parts = preg_split("/(\s\s+|\^)/", $patientname);
			//print_r($parts);
			$displayname = '';
			foreach ($parts as $part) {
				$displayname = substr($part,0,1) . $displayname;
			}
			if ($subjectcreated) { $subjectcreated = "&#x2713;"; } else { $subjectcreated = ""; }
			if ($familycreated) { $familycreated = "&#x2713;"; } else { $familycreated = ""; }
			if ($enrollmentcreated) { $enrollmentcreated = "&#x2713;"; } else { $enrollmentcreated = ""; }
			if ($studycreated) { $studycreated = "&#x2713;"; } else { $studycreated = ""; }
			if ($seriescreated) { $seriescreated = "&#x2713;"; } else { $seriescreated = ""; }
			if ($overwroteexisting) { $overwroteexisting = "&#x2713;"; } else { $overwroteexisting = ""; }
			?>
			<tr>
				<td><?=$displayname?></td>
				<td title="PatientID (from file)"><?=$patientid?></td>
				<!--<td style="font-size:8pt"><?=$institution?></td>-->
				<td style="font-size:8pt"><?=$equipment?></td>
				<td><?=$modality?></td>
				<td title="Study date/time" style="font-size:8pt; white-space: nowrap;"><?=$studydatetime?></td>
				<td title="Series date/time" style="font-size:8pt; white-space: nowrap;"><?=$seriesdatetime?></td>
				<td title="Series number"><?=$seriesnum?></td>
				<td title="Number of files"><?=$numfiles?></td>
				<td style="font-size:16pt; padding-left:20px; padding-right:20px; color:darkred; -webkit-transform:scale(1.5,1);">&#10137;</td>
				<td><a href="subjects.php?id=<?=$subjectid?>"><?=$newuid?></a></td>
				<td title="Study number"><?=$newstudynum?></td>
				<td title="Project number"><?=$project?></td>
				<td title="Import time" style="font-size:8pt;"><?=$importtime?></td>
				<td title="Import completed date/time" style="font-size:8pt;"><?=$maximportdatetime?></td>
				<td title="Subject created"><?=$subjectcreated?></td>
				<td title="Family created"><?=$familycreated?></td>
				<td title="Enrollment created"><?=$enrollmentcreated?></td>
				<td title="Study created"><?=$studycreated?></td>
				<td title="Series created"><?=$seriescreated?></td>
				<td title="Overwrote existing"><?=$overwroteexisting?></td>
			</tr>
			<?
		}
		?>
			</tbody>
		</table>
		<?
		//PrintSQLTable($result,"importlog.php?action=displaylog",$orderby,8);
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayTransactions ---------------- */
	/* -------------------------------------------- */
	function DisplayTransactions($useronly) {

		if ($useronly != 1)
			$useronly = 0;
		
		$numdeleted1 = 0;
		$numdeleted2 = 0;
		/* delete any transactions older than 21 days */
		$sqlstring = "delete from import_transactions where transaction_startdate < date_sub(now(), interval 21 day)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numdeleted1 = mysqli_affected_rows($GLOBALS['linki']);
		
		/* delete import_requests that are not in the transaction table */
		$sqlstring = "delete from import_requests where import_transactionid not in (select importtrans_id from import_transactions)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$numdeleted2 = mysqli_affected_rows($GLOBALS['linki']);
		
		if (($numdeleted1 > 0) || ($numdeleted2 > 0)) {
		?>
		<span class="tiny">Cleanup import logs older than 21 days - Removed [<?=$numdeleted1?>] entries from import_transactions and [<?=$numdeleted2?>] entries from import_requests</span>
		<?
		}
		?>
		<br>
		<?
		if ($useronly) {
			$sqlstring = "select * from import_transactions where transaction_username = '" . $GLOBALS['username'] . "' order by transaction_startdate desc";
		}
		else {
			$sqlstring = "select * from import_transactions order by transaction_startdate desc";
		}
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$transactionid = $row['importtrans_id'];
			$transaction_startdate = $row['transaction_startdate'];
			$transaction_enddate = $row['transaction_enddate'];
			$transaction_status = $row['transaction_status'];
			$transaction_source = $row['transaction_source'];
			$transaction_username = $row['transaction_username'];

			$numuploading = 0;
			$numpending = 0;
			$numreceiving = 0;
			$numreceived = 0;
			$numarchiving = 0;
			$numarchived = 0;
			$numblank = 0;
			$numerror = 0;
			$numnull = 0;
			unset($counts);
			
			$numblocks = 0;
			$sqlstringA = "select import_status, count(*) 'count' from import_requests where import_transactionid = '$transactionid' group by import_status";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$counts[$rowA['import_status']] = $rowA['count'];
				$numblocks += $rowA['count'];
			}

			$sqlstringA = "select sum(numfilesfail) 'numfail', sum(numfilestotal) 'numtotal' from import_requests where import_transactionid = '$transactionid'";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$numfilesfail = $rowA['numfail'];
			$numfilestotal = $rowA['numtotal'];

			if ($transaction_status == 'uploading') {
				$status = "Receiving";
				$status_message = "An upload transaction has started. Data is currently being received by NiDB";
			}
			elseif ($transaction_status == 'uploadcomplete') {
				$status = "Upload complete";
				$status_message = "An upload transaction has been completed. Data have been received by NiDB and are being checked, but may not yet be archived or available for download";
			}

			$numuploading = (int)$counts['uploading'];
			$numpending = (int)$counts['pending'];
			$numreceiving = (int)$counts['receiving'];
			$numreceived = (int)$counts['received'];
			$numarchiving = (int)$counts['archiving'];
			$numarchived = (int)$counts['archived'];
			$numblank = (int)$counts[''];
			$numerror = (int)$counts['error'];
			$numnull = (int)$counts['null'];

			if (($numerror > 0) || ($numfilesfail > 0)) {
				$statusicon = "&#9940;";
				$statuscolor = "#ba4927";
			}
			elseif (($numarchived == $numblocks) && ($transaction_enddate != '')) {
				$statusicon = "&#10004;";
				$statuscolor = "#4b8c41";
			}
			else {
				$statusicon = "&#10004;";
				$statuscolor = "#888";
			}
			
			if ($numblocks > 0) {
				?>
				<style>
					/*.darkheader { background-color: #666; color: #fff; vertical-align: top; }*/
					.darkheader { background-color: #ddd; color: #333; vertical-align: top; }
				</style>
				<div align="center">
				<table width="90%" cellspacing="0" cellpadding="4" style="border: solid 2px #333">
					<tr>
						<td align="center" style="background-color: <?=$statuscolor?>; color: #fff" width="5%"><?=$statusicon?></td>
						<td class="darkheader"><span style="font-size: 8pt" width="20%">User</span><br><b><?=$transaction_username?></b></td>
						<td class="darkheader"><span style="font-size: 8pt" width="20%">Transaction ID</span><br><?=$transactionid?></td>
						<td class="darkheader"><span style="font-size: 8pt">Upload started</span><br><b><?=date('M j, Y g:ia',strtotime($transaction_startdate))?></b></td>
						<td class="darkheader" align="left" width="30%">
							<span style="font-size: 8pt">Archived blocks</span><br>
							<img src="horizontalchart.php?b=yes&w=200&h=15&v=<?=$numarchived?>,<?=$numblocks-$numarchived?>&c=888888,EEEEEE"> <?=number_format(((double)($numarchived+$numblank)/$numblocks)*100.0,1)?>% (<?=$numarchived?> of <?=$numblocks?>)
						</td>
					</tr>
					<tr>
						<td colspan="5" valign="top">
							<details>
							<summary>Upload block details</summary>
							<?DisplayUploadBlocks($transactionid)?>
							</details>
							<br>
							<details>
							<summary>Subject/study details</summary>
							<?DisplaySingleTransaction($transactionid)?>
							</details>
						</td>
					</tr>
				</table>
				</div>
				<br>
				<?
			}
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayUploadBlocks ---------------- */
	/* -------------------------------------------- */
	function DisplayUploadBlocks($transactionid) {
		?>
		<style>
			.highlighted:hover { border: 1px solid orange; }
		</style>
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th> </th>
					<th>ID</th>
					<th>Data type</th>
					<th>Received Files<br><span class="tiny">total/success/fail</span></th>
					<th>Start Date</th>
					<th>Status</th>
					<th>Message</th>
					<th>End Date</th>
					<th>Site</th>
					<th>Project</th>
					<th>Instance</th>
					<th colspan="2">View reports</th>
				</tr>
			</thead>
		<?
		$sqlstring = "select a.*, b.project_name, c.site_name, d.instance_name from import_requests a left join projects b on a.import_projectid = b.project_id left join nidb_sites c on a.import_siteid = c.site_id left join instance d on a.import_instanceid = d.instance_id where a.import_transactionid = $transactionid order by import_datetime desc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$importrequestid = $row['importrequest_id'];
			$import_datatype = $row['import_datatype'];
			//$import_modality = $row['import_modality'];
			$import_datetime = $row['import_datetime'];
			$import_status = $row['import_status'];
			$import_message = $row['import_message'];
			$import_startdate = $row['import_startdate'];
			$import_enddate = $row['import_enddate'];
			$import_equipment = $row['import_equipment'];
			$import_siteid = $row['import_siteid'];
			$import_projectid = $row['import_projectid'];
			$import_instanceid = $row['import_instanceid'];
			$import_anonymize = $row['import_anonymize'];
			$import_permanent = $row['import_permanent'];
			$import_matchidonly = $row['import_matchidonly'];
			$import_filename = $row['import_filename'];
			$import_fileisseries = $row['import_fileisseries'];
			$numfilestotal = $row['numfilestotal'];
			$numfilessuccess = $row['numfilessuccess'];
			$numfilesfail = $row['numfilesfail'];
			$uploadreport = $row['uploadreport'];
			$archivereport = $row['archivereport'];
			$projectname = $row['project_name'];
			$sitename = $row['site_name'];
			$instancename = $row['instance_name'];
			
			if (strlen($sitename) > 30) {
				$sitename = "<span title='$sitename'>" . substr($sitename,0,30) . "...</span>";
			}
			
			switch ($import_status) {
				case 'uploading': # Uploading
					$step1color = "#000"; $step1weight = "bold";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#999"; $step7weight = "normal";
					break;
				case 'pending': # Uploaded
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#000"; $step2weight = "bold";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#999"; $step7weight = "normal";
					break;
				case 'receiving': # Checking
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#000"; $step3weight = "bold";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#999"; $step7weight = "normal";
					break;
				case 'received': # Checked
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#000"; $step4weight = "bold";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#999"; $step7weight = "normal";
					break;
				case 'archiving': # Archiving
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#000"; $step5weight = "bold";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#999"; $step7weight = "normal";
					break;
				case 'archived': # Archived
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#000"; $step6weight = "bold";
					$step7color = "#999"; $step7weight = "normal";
					break;
				case 'error': # Error
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#f22"; $step7weight = "bold";
					break;
				default:
					$step1color = "#999"; $step1weight = "normal";
					$step2color = "#999"; $step2weight = "normal";
					$step3color = "#999"; $step3weight = "normal";
					$step4color = "#999"; $step4weight = "normal";
					$step5color = "#999"; $step5weight = "normal";
					$step6color = "#999"; $step6weight = "normal";
					$step7color = "#999"; $step7weight = "normal";
					break;
			}
			
			if (($numfilestotal != $numfilessuccess) || ($import_status == 'error')) {
				$statusicon = "&#9940;";
				$statuscolor = "#ba4927";
			}
			elseif ($import_status == 'archived') {
				$statusicon = "&#10004;";
				$statuscolor = "#4b8c41";
			}
			//elseif () {
			//	$statusicon = "&#10004;";
			//	$statuscolor = "#888";
			//}
			else {
				$statusicon = "";
				$statuscolor = "";
			}
			
			if ($import_startdate == "0000-00-00 00:00:00") { $import_startdate = "-"; }
			else { $import_startdate = date("M j, Y g:ia",strtotime($import_startdate)); }
			if ($import_enddate == "0000-00-00 00:00:00") { $import_enddate = "-"; }
			else { $import_enddate = date("M j, Y g:ia",strtotime($import_enddate)); }
			
			if ($import_anonymize) { $import_anonymize = "&#x2713;"; } else { $import_anonymize = ""; }
			if ($import_permanent) { $import_permanent = "&#x2713;"; } else { $import_permanent = ""; }
			if ($import_matchidonly) { $import_matchidonly = "&#x2713;"; } else { $import_matchidonly = ""; }
			if ($import_fileisseries) { $import_fileisseries = "&#x2713;"; } else { $import_fileisseries = ""; }
			?>
			<tr>
				<td align="center" style="background-color: <?=$statuscolor?>; color: #fff"><?=$statusicon?></td>
				<td valign="top"><?=$importrequestid?></td>
				<td valign="top"><?=$import_datatype?></td>
				<td valign="top"><?=$numfilestotal?>/<?=$numfilessuccess?>/<? if ($numfilesfail > 0) { echo "<span style='color: red; font-weight: bold'>$numfilesfail</span>"; } else { echo $numfilesfail; }?></td>
				<td valign="top" style="font-size:8pt"><?=$import_startdate?></td>
				<td valign="top" style="font-size:8pt">
					<span title="uploading &rarr; pending<br>api.php" class="highlighted"><span style="color: <?=$step1color?>; font-weight: <?=$step1weight?>">Uploading</span>&nbsp;&rarr;&nbsp;<span style="color: <?=$step2color?>; font-weight: <?=$step2weight?>">Uploaded</span></span>&nbsp;&rarr;&nbsp;
					<span title="receiving &rarr; received<br>importuploaded.pl" class="highlighted"><span style="color: <?=$step3color?>; font-weight: <?=$step3weight?>">Checking</span>&nbsp;&rarr;&nbsp;<span style="color: <?=$step4color?>; font-weight: <?=$step4weight?>">Checked</span></span>&nbsp;&rarr;&nbsp;
					<span title="archiving &rarr; archived<br>parsedicom.pl" class="highlighted"><span style="color: <?=$step5color?>; font-weight: <?=$step5weight?>">Archiving</span>&nbsp;&rarr;&nbsp;<span style="color: <?=$step6color?>; font-weight: <?=$step6weight?>">Archived</span></span>&nbsp;&nbsp;&nbsp;
					<span style="color: <?=$step7color?>; font-weight: <?=$step7weight?>">Error</span>
				</td>
				<td valign="top"><?=$import_message?></td>
				<td valign="top" style="font-size:8pt"><?=$import_enddate?></td>
				<td valign="top" style="font-size:8pt"><?=$sitename?></td>
				<td valign="top" style="font-size:8pt"><?=$projectname?></td>
				<td valign="top" style="font-size:8pt"><?=$instancename?></td>
				<td valign="top" style="font-size:8pt">
					<details>
					<summary>Upload report</summary>
					<pre><?=$uploadreport?></pre>
					</details>
				</td>
				<td valign="top" style="font-size:8pt">
					<details>
					<summary>Archive report</summary>
					<pre><?=$archivereport?></pre>
					</details>
				</td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySingleTransaction ----------- */
	/* -------------------------------------------- */
	function DisplaySingleTransaction($transactionid) {
		$sqlstring = "select * from import_requests where import_transactionid = $transactionid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$groupids[] = $row['importrequest_id'];
		}
		$grouplist = implode2(',',$groupids);
		if ($grouplist == "") {
			$grouplist = 'null';
		}
		$sqlstring = "select *, timediff(max(importstartdate), min(importstartdate)) 'importtime', date_format(max(importstartdate), '%b %e, %Y %T') 'maximportdatetime', date_format(studydatetime_orig, '%b %e, %Y %T') 'studydatetime', date_format(seriesdatetime_orig, '%b %e, %Y %T') 'seriesdatetime', count(*) 'numfiles' from importlogs where importgroupid in ($grouplist) group by stationname_orig, studydatetime_orig, seriesnumber_orig order by studydatetime_orig desc, seriesdatetime_orig";
		//$sqlstring = "select * from importlogs where importgroupid in ($grouplist)";
		?>
		
<style>
.text {
   position:relative;
   width:20px;
   height:170px;
   /*border:1px solid rgba(0,0,0,0.5);*/
   /*border-radius:7px; */
   /*margin:20px auto; */
   /*background-color:rgb(255,255,255); */
   /*box-shadow:inset 0 0 10px rgba(0,0,0,0.6),
                    0 0 10px rgba(0,0,0,0.6);*/
  }
.text span {
   position:absolute;
   width:170px;
   line-height:20px;
   left:0;
   top:100%;
   transform:rotate(-90deg); 
   -webkit-transform:rotate(-90deg); 
   transform-origin:0 0;
   -webkit-transform-origin:0 0;
   text-align:left;
   vertical-align: middle;
  }
</style>		
		<table class="ui very small very compact celled selectable grey table">
			<thead>
				<tr>
					<th valign="bottom">Initials</th>
					<th>Patient ID</th>
					<!--<th>Institution</th>-->
					<th>Equipment</th>
					<th>Modality</th>
					<th>Study date</th>
					<th>Series date</th>
					<th>Series Num</th>
					<th>Num files</th>
					<th> </th>
					<th>New UID</th>
					<th>Study Num</th>
					<th>Project</th>
					<th>Import time<br><span class="tiny">HH:MM:SS</span></th>
					<th>Import complete</th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created subject</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created family</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created enrollment</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created study</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Created series</span></div></th>
					<th><div class="text"><span style="font-size:10pt; font-weight:normal;">Overwrote existing data</span></div></th>
				</tr>
			</thead>
			<tbody>
		<?
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//PrintSQLTable($result,"importlog.php?action=displaylog",$orderby,8);
		//PrintSQLTable($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$patientid = $row['patientid_orig'];
			$patientname = $row['patientname_orig'];
			$institution = $row['institution_orig'];
			$equipment = $row['stationname_orig'];
			$modality = $row['modality_orig'];
			//$studydatetime = date('M j g:ia',strtotime($row['studydatetime_orig']));
			$studydatetime = $row['studydatetime'];
			//$seriesdatetime = date('M j g:ia',strtotime($row['seriesdatetime_orig']));
			$seriesdatetime = $row['seriesdatetime'];
			$seriesnum = $row['seriesnumber_orig'];
			$numfiles = $row['numfiles'];
			$importtime = $row['importtime'];
			$minimportdatetime = $row['minimportdatetime'];
			$maximportdatetime = $row['maximportdatetime'];

			$subjectid = $row['subjectid'];
			$newuid = $row['subject_uid'];
			$newstudynum = $row['study_num'];
			$project = $row['project_number'];
			
			$subjectcreated = $row['subject_created'];
			$familycreated = $row['family_created'];
			$enrollmentcreated = $row['enrollment_created'];
			$studycreated = $row['study_created'];
			$seriescreated = $row['series_created'];
			$overwroteexisting = $row['overwrote_existing'];

			/* fix patient name, to only show initials */
			$parts = preg_split("/(\s\s+|\^)/", $patientname);
			//print_r($parts);
			$displayname = '';
			foreach ($parts as $part) {
				$displayname = substr($part,0,1) . $displayname;
			}
			if ($subjectcreated) { $subjectcreated = "&#x2713;"; } else { $subjectcreated = ""; }
			if ($familycreated) { $familycreated = "&#x2713;"; } else { $familycreated = ""; }
			if ($enrollmentcreated) { $enrollmentcreated = "&#x2713;"; } else { $enrollmentcreated = ""; }
			if ($studycreated) { $studycreated = "&#x2713;"; } else { $studycreated = ""; }
			if ($seriescreated) { $seriescreated = "&#x2713;"; } else { $seriescreated = ""; }
			if ($overwroteexisting) { $overwroteexisting = "&#x2713;"; } else { $overwroteexisting = ""; }
			?>
			<tr>
				<td><?=$displayname?></td>
				<td title="PatientID (from file)"><?=$patientid?></td>
				<!--<td style="font-size:8pt"><?=$institution?></td>-->
				<td style="font-size:8pt"><?=$equipment?></td>
				<td><?=$modality?></td>
				<td title="Study date/time" style="font-size:8pt; white-space: nowrap;"><?=$studydatetime?></td>
				<td title="Series date/time" style="font-size:8pt; white-space: nowrap;"><?=$seriesdatetime?></td>
				<td title="Series number"><?=$seriesnum?></td>
				<td title="Number of files"><?=$numfiles?></td>
				<td style="font-size:16pt; padding-left:20px; padding-right:20px; color:darkred; -webkit-transform:scale(1.5,1);">&#10137;</td>
				<td><a href="subjects.php?id=<?=$subjectid?>"><?=$newuid?></a></td>
				<td title="Study number"><?=$newstudynum?></td>
				<td title="Project number"><?=$project?></td>
				<td title="Import time" style="font-size:8pt;"><?=$importtime?></td>
				<td title="Import completed date/time" style="font-size:8pt;"><?=$maximportdatetime?></td>
				<td title="Subject created"><?=$subjectcreated?></td>
				<td title="Family created"><?=$familycreated?></td>
				<td title="Enrollment created"><?=$enrollmentcreated?></td>
				<td title="Study created"><?=$studycreated?></td>
				<td title="Series created"><?=$seriescreated?></td>
				<td title="Overwrote existing"><?=$overwroteexisting?></td>
			</tr>
			<?
		}
		?>
			</tbody>
		</table>
		<?
	}
	
?>

<? include("footer.php") ?>
