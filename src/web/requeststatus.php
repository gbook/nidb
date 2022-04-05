<?
 // ------------------------------------------------------------------------------
 // NiDB requeststatus.php
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
			ShowItemList($viewall);
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
	/* ------- ShowItemList ------------------------------ */
	/* --------------------------------------------------- */
	function ShowItemList($viewall) {
		?>
		<div class="ui container">
		<?
		if ($viewall) {
			?>
			<h3 class="ui header">Showing all exports</h3> <a class="ui basic button" href="requeststatus.php?viewall=0">Show only last 30</a>
			<?
		}
		else {
			?>
			<h3 class="ui header">Showing 30 most recent exports</h3> <a class="ui basic button" href="requeststatus.php?viewall=1">Show all</a>
			<?
		}
		?>
		<script>
			$(document).ready(function() {
				$('.ui .progress').progress();
			});
		</script>
		<div class="ui divided items">
		<?
		$completecolor = "66AAFF";
		$processingcolor = "AAAAFF";
		$errorcolor = "FF6666";
		$othercolor = "EFEFFF";
		
		if ($GLOBALS['issiteadmin']) {
			if ($viewall) {
				$sqlstring = "select * from exports order by submitdate desc";
			}
			else {
				$sqlstring = "select * from exports order by submitdate desc limit 30";
			}
		}
		else {
			if ($viewall) {
				$sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc";
			}
			else {
				$sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc limit 30";
			}
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
			
			switch ($destinationtype) {
				case "web": $deststr = "<i class='cloud download alternate icon'></i> Web"; break;
				case "publicdownload": $deststr = "<i class='people carry icon'></i> Public Download"; break;
				case "remotenidb": $deststr = "<em data-emoji=':chipmunk:'></em> Remote NiDB"; break;
				case "nfs": $deststr = "<i class='server icon'></i> Remote NiDB"; break;
				default: $deststr = ucfirst($destinationtype);
			}
			
			switch ($exportstatus) {
				case "submitted":
				case "pending":
					$statusstr = "";
					break;
				case "complete": $statusstr = "<i class='large green check icon'></i>Complete"; $iconcolor = "green"; break;
				case "error": $statusstr = "<i class='large red exclamation circle icon'></i>Complete w/Errors"; $iconcolor = "red"; break;
				case "processing": $statusstr = "<i class='large blue spinner loading icon'></i>Processing"; $iconcolor = "grey"; break;
				default: $statusstr = $exportstatus; $iconcolor = "";
			}
			
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
			
			if ($totals['error'] > 0) {
				$error = "error";
				$witherrors = "<br><span style='font-size: 8pt; color:red'>with " . $totals['error'] . " errors</span>";
			}
			else {
				$error = "";
				$witherrors = "";
			}
			
			if ($destinationtype == 'remotenidb') {
				$completelabel = 'sent';
			}
			else {
				$completelabel = 'complete';
			}
			
			/* get exports in queue ahead of this one */
			$numahead = 0;
			if (($status == 'submitted') || ($status == 'pending')) {
				$sqlstringA = "select count(*) 'count' from exports where status in ('processing','submitted') and submitdate < '$submitdate'";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
				$numahead = $rowA['count'];
			}
			
			?>
			<div class="ui item">
				<div class="image" style="text-align: left">
					<i class="big grey archive icon"></i>
					<?=$statusstr?> <?=$witherrors?>
				</div>
				<div class="content">
					<div class="header"><?=date("D M j, Y h:ia",strtotime($submitdate))?></div>
					<div class="meta">
						<?=$deststr?> &nbsp; &nbsp; <?=$numseries?> series &nbsp; &nbsp; <?=HumanReadableFilesize($totalbytes)?>
						<p>Requested by <?=$username?></p>
						<? if ($numahead > 0) {
							echo "<p>$numahead exports queued ahead of this export</p>";
						} ?>
					</div>
					<div class="description">
						<? if (($destinationtype == "remotenidb") && ($connectionid != "") && ($transactionid != "")) { ?>
						<br><iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=0&total=<?=$total?>" width="650px" height="50px" style="border: 0px">Checking with remote server...</iframe>
						<? }
						
						if ((($totals['complete']/$total)*100) < 100) {
						?>
						<div class="ui small progress <?=$error?>" data-percent="<?=($totals['complete']/$total)*100?>">
							<div class="bar">
								<div class="centered progress"></div>
							</div>
							<div class="label" style="font-size: smaller; font-weight: normal">Exporting series (<?=number_format($totals['complete'])?> of <?=number_format($total)?>)</div>
						</div>
						<?
						}
						else {
							echo $totals['complete'] . " series exported";
						}
						?>
					</div>
					<div class="extra">
						<div class="ui two column very compact grid">
							<div class="column">
								<script>
									$(document).ready(function() {
										$('#popupbutton<?=$exportid?>').popup({ popup : $('#popupmenu<?=$exportid?>'), on : 'click'	});
									});
								</script>
								<div class="ui small basic compact button" id="popupbutton<?=$exportid?>"><i class="cog icon"></i> Options</div>
								<div class="ui popup" id="popupmenu<?=$exportid?>" style="width: 400px">
									<a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>" title="View status" class="ui fluid primary button"><i class="binoculars icon"></i>View Export Details</a>
									<br>
									<? if ($exportstatus == "error") { ?>
									<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Retry failed series" class="ui fluid button"><i class="sync alternate icon"></i> Retry</a>
									<? } elseif (($exportstatus == "complete") || ($exportstatus == "cancelled")) { ?>
									<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Resend all series" class="ui fluid button"><i class="file import icon"></i> Resend</a>
									<? } elseif (($exportstatus == "submitted") || ($exportstatus == "processing")) { ?>
									<a href="requeststatus.php?action=cancelexport&exportid=<?=$exportid?>" title="Cancel the remaining series" class="ui fluid red button"><i class="times circle icon"></i> Cancel</a>
									<? } ?>
								</div>
							</div>
							<div class="right aligned column">
								<?
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
												?>
													<div class="ui labeled button">
														<a class="ui blue button" href="download/<?="NIDB-$exportid.zip"?>" title="Download zip file"><i class="download icon"></i> Download</a>
														<div class="ui basic label" style="font-weight: normal; font-size: smaller"><?=HumanReadableFilesize($filesize)?></div>
													</div>
												<?
											}
										}
										else {
											?>Preparing download...<?
										}
									}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?
			}
		?>
		</div>
		</div>
		<?
	}
	
	
	/* --------------------------------------------------- */
	/* ------- ShowList ---------------------------------- */
	/* --------------------------------------------------- */
	function ShowList($viewall) {
		
		if ($viewall) {
			?>
			<h3 class="ui header">Showing all exports</h3> <a class="ui basic button" href="requeststatus.php?viewall=0">Show only last 30</a>
			<?
		}
		else {
			?>
			<h3 class="ui header">Showing 30 most recent exports</h3> <a class="ui basic button" href="requeststatus.php?viewall=1">Show all</a>
			<?
		}
		?>
		<script>
			$(document).ready(function() {
				$('.ui .progress').progress();
			});
		</script>
		<table class="ui small celled selectable grey very compact table">
			<thead>
				<th align="left">Request date</th>
				<th align="left">Format</th>
				<th align="left">Username</th>
				<th align="right">Number of series</th>
				<th align="right">Size</th>
				<th align="left">Progress <i class="question circle outline grey icon" title="Progress from data sent to a remote site may not be visible if your login information has changed on the remote server, or if the remote server has deleted its log files"></i></th>
				<th align="left">Status</th>
				<th align="left">Actions</th>
				<th align="left">Messages/Download</th>
			</thead>
		<?
		$completecolor = "66AAFF";
		$processingcolor = "AAAAFF";
		$errorcolor = "FF6666";
		$othercolor = "EFEFFF";
		
		if ($GLOBALS['issiteadmin']) {
			if ($viewall) {
				$sqlstring = "select * from exports order by submitdate desc";
			}
			else {
				$sqlstring = "select * from exports order by submitdate desc limit 30";
			}
		}
		else {
			if ($viewall) {
				$sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc";
			}
			else {
				$sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc limit 30";
			}
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
			
			if ($totals['error'] > 0) {
				$error = "error";
				$witherrors = "<br><span style='font-size: 8pt; color:red'>with " . $totals['error'] . " errors</span>";
			}
			else {
				$error = "";
				$witherrors = "";
			}
			
			if ($destinationtype == 'remotenidb') {
				$completelabel = 'sent';
			}
			else {
				$completelabel = 'complete';
			}
			
			/* get exports in queue ahead of this one */
			$numahead = 0;
			if (($status == 'submitted') || ($status == 'pending')) {
				$sqlstringA = "select count(*) 'count' from exports where status in ('processing','submitted') and submitdate < '$submitdate'";
				$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
				$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
				$numahead = $rowA['count'];
			}
			
			?>
			<tr>
				<td style="white-space: nowrap;"><?=date("D M j, Y h:ia",strtotime($submitdate))?></td>
				<td><?=$destinationtype?></td>
				<td><?=$username?></td>
				<td class="right aligned"><?=$numseries?></td>
				<td class="right aligned"><?=number_format($totalbytes)?></td>
				<td style="white-space: nowrap; width: 20%">
					<!--<img src="horizontalchart.php?b=yes&w=400&h=15&v=<?=$totals['complete']?>,<?=$totals['processing']?>,<?=$totals['error']?>,<?=$leftovers?>&c=<?=$completecolor?>,<?=$processingcolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($totals['complete']/$total)*100,1)?>% <?=$completelabel?> <span style="font-size:8pt;color:gray">(<?=number_format($totals['complete'])?> of <?=number_format($total)?> series)</span>-->
					<? if (($destinationtype == "remotenidb") && ($connectionid != "") && ($transactionid != "")) { ?>
					<br><iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=0&total=<?=$total?>" width="650px" height="50px" style="border: 0px">Checking with remote server...</iframe>
					<? } ?>
					<div class="ui small progress <?=$error?>" data-percent="<?=($totals['complete']/$total)*100?>">
						<div class="bar">
							<div class="centered progress"></div>
						</div>
						<div class="label" style="font-size: 8pt; font-weight: normal"><?=number_format($totals['complete'])?> of <?=number_format($total)?> series</label>
					</div>
				</td>
				<td><a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>" title="View status"><?=ucfirst($exportstatus)?></a> <?=$witherrors?></td>
				<td>
					<? if ($exportstatus == "error") { ?>
					<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Retry failed series" class="ui compact orange button"><i class="sync alternate icon"></i> Retry</a>
					<? } elseif (($exportstatus == "complete") || ($exportstatus == "cancelled")) { ?>
					<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Resend all series" class="ui compact button"><i class="file import icon"></i> Resend</a>
					<? } elseif (($exportstatus == "submitted") || ($exportstatus == "processing")) { ?>
					<a href="requeststatus.php?action=cancelexport&exportid=<?=$exportid?>" title="Cancel the remaining series" class="ui compact red button"><i class="times circle icon"></i> Cancel</a>
					<? } ?>
				</td>
				<td><?
				if ($numahead > 0) {
					echo "[$numahead] exports queued ahead of this one<br>";
				}
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
							?>
								<div class="ui labeled button">
									<a class="ui compact blue button" href="download/<?="NIDB-$exportid.zip"?>" title="Download zip file"><i class="download icon"></i> Download</a>
									<div class="ui basic label"><?=HumanReadableFilesize($filesize)?></div>
								</div>
							<?
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
					<table class="ui very compact celled grey table">
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
