<?
 // ------------------------------------------------------------------------------
 // NiDB requeststatus.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Data request status</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* get variables */
	$action = GetVariable("action");
	$page = GetVariable("page");
	$exportid = GetVariable("exportid");
	$requestid = GetVariable("requestid");
	$viewall = GetVariable("viewall");
	
	switch ($action) {
		case 'viewdetails':
			ViewDetails($exportid);
			break;
		case 'resetexport':
			ResetExport($exportid);
			ViewExport($exportid, $page);
			break;
		case 'cancelexport':
			CancelExport($exportid);
			ShowList($viewall);
			break;
		case 'retryerrors':
			RetryErrors($exportid);
			ShowList($viewall);
			break;
		case 'viewexport':
			ViewExport($exportid, $page);
			break;
		default:
			ShowList($viewall);
	}

	
	/* --------------------------------------------------- */
	/* ------- CancelExport ------------------------------- */
	/* --------------------------------------------------- */
	function CancelExport($exportid) {
		$sqlstring = "update exports set status = 'cancelled' where export_id = $exportid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("Export [$exportid] cancelled");
	}

	
	/* --------------------------------------------------- */
	/* ------- ResetExport ------------------------------- */
	/* --------------------------------------------------- */
	function ResetExport($exportid) {
		if ($exportid > 0) {
			$sqlstring = "update exports set status = 'submitted', log = '' where export_id = $exportid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$sqlstring = "update exportseries set status = 'submitted' where export_id = $exportid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			Notice("Status reset for export [$exportid]");
		}
		else {
			Error("Invalid export ID [$exportid]");
		}
	}

	
	/* --------------------------------------------------- */
	/* ------- RetryErrors ------------------------------- */
	/* --------------------------------------------------- */
	function RetryErrors($exportid) {
		$sqlstring = "select destinationtype from data_requests where req_groupid = $exportid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$desttype = $row['req_destinationtype'];
		
		/* the only download type that can resend single series is the remote NiDB. All others
		   may have consecutive/renumbered series and must be completely rerun */
		if ($desttype == "remotenidb") {
			$sqlstring = "update exports set status = 'submitted' where export_id = $exportid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$sqlstring = "update exportseries set status = 'submitted' where export_id = $exportid and status in ('error', 'cancelled')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			$sqlstring = "update exports set status = 'submitted' where export_id = $exportid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$sqlstring = "update exportseries set status = 'submitted' where export_id = $exportid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		Notice("Export $exportid re-queued");
	}
	

	/* --------------------------------------------------- */
	/* ------- ViewDetails ------------------------------- */
	/* --------------------------------------------------- */
	function ViewDetails($requestid) {
		$urllist['Search'] = "search.php";
		$urllist['Data export status'] = "requeststatus.php";
		NavigationBar("Data export details", $urllist);
		
		$sqlstring = "select * from data_requests where request_id = $requestid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		$fields_num = mysqli_num_fields($result);
		for($i=0; $i<$fields_num; $i++)
		{
			$field = mysqli_fetch_field($result);
			$fields[] = $field->name;
		}
		?><div style="column-count 3; -moz-column-count:3; -webkit-column-count:3"><?
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		foreach ($fields as $f) {
			if (($f != 'req_results') && (stripos($f,'password') === false)) {
				echo "<b>$f</b> - " . $row[$f] . "<br>";
			}
		}
		echo "</div><pre><b>Request results</b><br>" . $row['req_results'] . "</pre>";
	}
	
	
	/* --------------------------------------------------- */
	/* ------- ShowList ---------------------------------- */
	/* --------------------------------------------------- */
	function ShowList($viewall) {
		
		if ($viewall) {
			?>
			<h3 class="ui header">Showing all exports</h3> <a href="requeststatus.php?viewall=0">(show only most recent 30)</a>
			<?
		}
		else {
			?>
			<h3 class="ui header">Showing 30 most recent exports</h3> <a href="requeststatus.php?viewall=1">(show all)</a>
			<?
		}
		?>
		<table class="ui small celled selectable grey compact table">
			<thead>
				<th align="left">Request date</th>
				<th align="left">Format</th>
				<th align="left">Username</th>
				<th align="right">Number of series</th>
				<th align="right">Size</th>
				<th align="left">Progress <i class="question circle outline grey icon" title="Progress from data sent to a remote site may not be visible if your login information has changed on the remote server, or if the remote server has deleted its log files"></i></th>
				<th align="left">Status</th>
				<th align="left">Actions</th>
				<th align="left">Download</th>
			</thead>
		<?
		$completecolor = "66AAFF";
		$processingcolor = "AAAAFF";
		$errorcolor = "FF6666";
		$othercolor = "EFEFFF";
		
		if ($GLOBALS['issiteadmin']) {
			if ($viewall) { $sqlstring = "select * from exports order by submitdate desc"; }
			else { $sqlstring = "select * from exports order by submitdate desc limit 30"; }
		}
		else {
			if ($viewall) { $sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc"; }
			else { $sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc limit 30"; }
		}
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$exportid = $row['export_id'];
			$submitdate = $row['submitdate'];
			$username = $row['username'];
			$destinationtype = $row['destinationtype'];
			$exportstatus = $row['status'];
			$connectionid = $row['remotenidb_connectionid'];
			$transactionid = $row['remotenidb_transactionid'];
			
			$total = 0;
			$totalbytes = 0;
			unset($totals);
			$totals['submitted'] = 0;
			$totals['processing'] = 0;
			$totals['complete'] = 0;
			$totals['error'] = 0;
			$sqlstringA = "select * from exportseries where export_id = $exportid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$numseries = mysqli_num_rows($resultA);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				//PrintVariable($rowA);
				$modality = strtolower($rowA['modality']);
				$seriesid = $rowA['series_id'];
				$status = $rowA['status'];
				$sqlstringB = "select * from $modality" . "_series where $modality" . "series_id = $seriesid";
				$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
				$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
				$totalbytes += $rowB['series_size'];
				
				$total++;
				switch ($status) {
					case 'submitted': $totals['submitted']++; break;
					case 'processing': $totals['processing']++; break;
					case 'complete': $totals['complete']++; break;
					case 'error': $totals['error']++; break;
				}
			}
				
			$leftovers = $total - $totals['complete'] - $totals['processing'] - $totals['error'];
				
				if ($destinationtype == 'remotenidb') {
					$completelabel = 'sent';
				}
				else {
					$completelabel = 'complete';
				}
				?>
				<tr>
					<td style="white-space: nowrap;"><?=date("D M j, Y h:ia",strtotime($submitdate))?></td>
					<td><?=$destinationtype?></td>
					<td><?=$username?></td>
					<td align="right"><?=$numseries?></td>
					<td align="right"><?=number_format($totalbytes)?></td>
					<td style="white-space: nowrap; width: 20%">
						<img src="horizontalchart.php?b=yes&w=400&h=15&v=<?=$totals['complete']?>,<?=$totals['processing']?>,<?=$totals['error']?>,<?=$leftovers?>&c=<?=$completecolor?>,<?=$processingcolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($totals['complete']/$total)*100,1)?>% <?=$completelabel?> <span style="font-size:8pt;color:gray">(<?=number_format($totals['complete'])?> of <?=number_format($total)?> series)</span>
						<? if (($destinationtype == "remotenidb") && ($connectionid != "") && ($transactionid != "")) { ?>
						<br><iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=0&total=<?=$total?>" width="650px" height="50px" style="border: 0px">Checking with remote server...</iframe>
						<? } ?>
					</td>
					<td><a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>" title="View status"><?=ucfirst($exportstatus)?></a></td>
					<td>
						<? if ($exportstatus == "error") { ?>
						<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Retry failed series">Retry</a>
						<? } elseif (($exportstatus == "complete") || ($exportstatus == "cancelled")) { ?>
						<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Resend all series">Resend</a>
						<? } elseif (($exportstatus == "submitted") || ($exportstatus == "processing")) { ?>
						<a href="requeststatus.php?action=cancelexport&exportid=<?=$exportid?>" title="Cancel the remaining series">Cancel</a>
						<? } ?>
					</td>
					<td><?
					if ($destinationtype == "web") {
						if ((round($totals['complete']/$total)*100 == 100) || (($totals['submitted'] == 0) && ($totals['processing'] == 0))) {
							$zipfile = $_SERVER['DOCUMENT_ROOT'] . "/download/NIDB-$exportid.zip";
							if (file_exists($zipfile)) {
								$output = shell_exec("du -sb $zipfile");
								list($filesize, $fname) = preg_split('/\s+/', $output);
							}
							else {
								$filesize = 0;
							}
							
							if ($filesize == 0) {
								echo "Zipping download...";
							}
							else {
								?><a href="download/<?="NIDB-$exportid.zip"?>" title="Download zip file">Download</a> <span class="tiny"><?=number_format($filesize,0)?> bytes</span><?
							}
						}
						else {
							echo "Preparing download...";
						}
					}
					?>
					</td>
				</tr>
				<?
			}
		?>
		</table>
		<?
	}
	
	/* --------------------------------------------------- */
	/* ------- ViewExport -------------------------------- */
	/* --------------------------------------------------- */
	function ViewExport($exportid) {
		$urllist['Search'] = "search.php";
		$urllist['Export status'] = "requeststatus.php";
		NavigationBar("Data export status", $urllist);

		$sqlstring = "select * from exports where export_id = $exportid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$log = $row['log'];
		$destinationtype = $row['destinationtype'];
		$connectionid = $row['remotenidb_connectionid'];
		$transactionid = $row['remotenidb_transactionid'];
		$status = ucfirst($row['status']);

		if ($status == "Complete") { $color = "#229320"; }
		elseif ($status == "Error") { $color = "#8E3023"; }
		else { $color = "#3B5998"; }
		?>
		<table>
			<tr>
				<td valign="top" style="padding: 7px; color: white; background-color: <?=$color?>; width:200px; font-weight: bold"><?=$status?></td>
				<td valign="top">
					<details>
					<summary>View Export Log</summary>
					<tt><pre><?=$log?></pre></tt>
					</details>
				</td>
			</tr>
		</table>
		
		<br><br>
		
		<table width="100%" style="border: 2px solid #444;" cellpadding="0" cellspacing="0">
			<tr>
				<td style="background-color: #444; color: #fff; padding: 8px"><b>LOCAL NiDB</b> sending status</td>
			</tr>
			<tr>
				<td>
					<table class="graydisplaytable" width="100%">
						<thead>
							<th align="left">Subject</th>
							<th align="left">Study</th>
							<th align="left">Series</th>
							<th align="right">Size</th>
							<th align="left">Status</th>
							<th align="left">Message</th>
						</thead>
					<?
					$sqlstring = "select * from exportseries where export_id = $exportid";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$modality = strtolower($row['modality']);
						$seriesid = $row['series_id'];
						$status = $row['status'];
						$statusmessage = $row['statusmessage'];
						
						$sqlstringB = "select a.*, b.*, d.project_name, e.uid, e.subject_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.$modality" . "series_id = $seriesid order by uid, study_num, series_num";
						$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
						$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
						$seriesdesc = $rowB['series_desc'];
						if ($modality != "mr") {
							$seriesdesc = $rowB['series_protocol'];
						}
						$subjectid = $rowB['subject_id'];
						$studyid = $rowB['study_id'];
						$uid = $rowB['uid'];
						$seriesnum = $rowB['series_num'];
						$studynum = $rowB['study_num'];
						$seriessize = $rowB['series_size'];
						$totalbytes += $rowB['series_size'];
						
						$total++;
						switch ($status) {
							case 'submitted': $totals['submitted']++; $bgcolor = "#fff"; $color="#444"; break;
							case 'processing': $totals['processing']++; $bgcolor = "#526FAA"; $color="#fff"; break;
							case 'complete': $totals['complete']++; $bgcolor = "#229320"; $color="#fff"; break;
							case 'error': $totals['error']++; $bgcolor = "#8E3023"; $color="#fff"; break;
						}
						?>
						<tr>
							<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
							<td><a href="studies.php?id=<?=$studyid?>"><?="$uid$studynum"?></a></td>
							<td><?=$seriesnum?> - <?=$seriesdesc?></td>
							<td align="right"><?=number_format($seriessize)?></td>
							<td style="background-color: <?=$bgcolor?>; color: <?=$color?>"> <?=ucfirst($status)?></td>
							<td><?=$statusmessage?></td>
						</tr>
						<?
					}
					?>
					</table>
				</td>
			</tr>
		</table>

		<? if ($destinationtype == 'remotenidb') { ?>
		<br><br>
		<table width="100%" style="border: 2px solid #444;" cellpadding="0" cellspacing="0">
			<tr>
				<td style="background-color: #444; color: #fff; padding: 8px"><b>REMOTE NiDB</b> archiving status</td>
			</tr>
			<tr>
				<td>
					<iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=1&total=<?=$total?>" width="100%" height="600px" style="border: 0px">No iframes available?</iframe>
				</td>
			</tr>
		</table>
		<? } ?>
		<?
	}
	?>
<? include("footer.php") ?>
