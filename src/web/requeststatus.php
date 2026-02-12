<?
 // ------------------------------------------------------------------------------
 // NiDB requeststatus.php
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
			ShowItemList($viewall);
			break;
		case 'retryerrors':
			RetryErrors($exportid);
			ShowItemList($viewall);
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
				case "complete": $statusstr = "<i class='big green check icon'></i>Complete"; $iconcolor = "green"; break;
				case "error": $statusstr = "<i class='big red exclamation circle icon'></i>Complete"; $iconcolor = "red"; break;
				case "processing": $statusstr = "<i class='big blue spinner loading icon'></i>Processing"; $iconcolor = "grey"; break;
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
				if ($modality != "") {
					$sqlstringB = "select * from $modality" . "_series where $modality" . "series_id = $seriesid";
					$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
					$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
					$totalbytes += $rowB['series_size'];
				}
				
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
			<div class="ui styled black top attached segment">
				<div class="ui two column very compact grid">
					<div class="column">
						<div class="ui header"><?=date("D M j, Y h:ia",strtotime($submitdate))?></div>
						<div class="ui meta">
							<?=$deststr?> &nbsp; &nbsp; <?=$numseries?> objects &nbsp; &nbsp; <?=HumanReadableFilesize($totalbytes)?>
							<p>Requested by <?=$username?></p>
							<? if ($numahead > 0) {
								echo "<p>$numahead exports queued ahead of this export</p>";
							} ?>
						</div>
						<div class="ui description">
							<? if (($destinationtype == "remotenidb") && ($connectionid != "") && ($transactionid != "")) { ?>
							<br><iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=0&total=<?=$total?>" width="650px" height="50px" style="border: 0px">Checking with remote server...</iframe>
							<? }
							
							if ((($totals['complete']/$total)*100) < 100) {
							?>
							<div class="ui orange image label">
								Exporting object <?=number_format($totals['complete'])?> of <?=number_format($total)?>
								<div class="detail"><?=number_format(($totals['complete']/$total)*100, 1)?>%</div>
							</div>
							<?
							}
							else {
								echo $totals['complete'] . " objects exported";
							}
							?>
						</div>
					</div>
					<div class="right aligned column">
						<script>
							$(document).ready(function() {
								$('#popupbutton<?=$exportid?>').popup({ popup : $('#popupmenu<?=$exportid?>'), on : 'click'	});
							});
						</script>
						<div class="ui vertical labeled spaced buttons">
							<a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>" title="View status" class="ui basic compact button"><i class="list alternate outline icon"></i> View Details</a>
							<? if ($exportstatus == "error") { ?>
							<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Retry failed series" class="ui basic compact button"><i class="sync alternate icon"></i> Retry</a>
							<? } elseif (($exportstatus == "complete") || ($exportstatus == "cancelled")) { ?>
							<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Resend all series" class="ui basic compact button"><i class="file import icon"></i> Resend</a>
							<? } elseif (($exportstatus == "submitted") || ($exportstatus == "processing")) { ?>
							<a href="requeststatus.php?action=cancelexport&exportid=<?=$exportid?>" title="Cancel the remaining series" class="ui basic red compact button"><i class="times circle icon"></i> Cancel</a>
							<? } ?>
							<br>
						</div>
					</div>
				</div>
			</div>

			<div class="ui bottom attached compact segment">
				<div class="ui two column very compact grid">
					<div class="column">
						<b><?=$statusstr?></b> <?=$witherrors?>						
					</div>
					<div class="right aligned column">
						<?
							if (($destinationtype == "web") || ($destinationtype == "xnat") || ($destinationtype == "squirrel") || ($destinationtype == "ndar")) {
								if ((round($totals['complete']/$total)*100 == 100) || (($totals['submitted'] == 0) && ($totals['processing'] == 0))) {
									$zipfile = $GLOBALS['cfg']['webdir'] . "/download/NIDB-$exportid.zip";
									if (file_exists($zipfile)) {
										$output = shell_exec("du -sb $zipfile");
										list($filesize, $fname) = preg_split('/\s+/', $output);
										$zipfilename = "NIDB-$exportid.zip";
									}
									else {
										$zipfile = $GLOBALS['cfg']['webdir'] . "/download/NiDB-Squirrel-$exportid.zip";
										if (file_exists($zipfile)) {
											$output = shell_exec("du -sb $zipfile");
											list($filesize, $fname) = preg_split('/\s+/', $output);
											$zipfilename = "NiDB-Squirrel-$exportid.zip";
											//echo $zipfilename;
										}
										else {
											$filesize = 0;
										}
									}
									
									//echo "[$zipfilename] [$zipfile]";
									if ($filesize == 0) {
										echo "Zipping download... ($zipfile)";
									}
									else {
										?>
											<a class="ui blue button" href="download/<?=$zipfilename?>" title="Download zip file"><i class="download icon"></i> Download <span style="font-size: smaller"><?=HumanReadableFilesize($filesize)?></span></a>
											<br>
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
			<br>
			<?
			}
		?>
		</div>
		<?
	}
	
	
	/* --------------------------------------------------- */
	/* ------- ViewExport -------------------------------- */
	/* ---Contribution: Muhammad Asim Mubeen (Dec 2023)--- */
	/* --------------------------------------------------- */
	function ViewExport($exportid) {

		$sqlstring = "select * from exports where export_id = $exportid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$log = $row['log'];
		$status = ucfirst($row['status']);
		$submitdate = $row['submitdate'];
		$username = $row['username'];
		$destinationtype = $row['destinationtype'];
		$exportstatus = $row['status'];
		$connectionid = $row['remotenidb_connectionid'];
		$transactionid = $row['remotenidb_transactionid'];

		if ($status == "Complete") { $color = "green"; }
		elseif ($status == "Error") { $color = "red"; }
		else { $color = "blue"; }
		?>
		<div class="ui container">
			<?
			
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
				case "error": $statusstr = "<i class='large red exclamation circle icon'></i>Complete"; $iconcolor = "red"; break;
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
				if ($modality != "") {
					$sqlstringB = "select * from $modality" . "_series where $modality" . "series_id = $seriesid";
					$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
					$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
					$totalbytes += $rowB['series_size'];
				}
				
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
			<div class="ui top attached segment">
				<div class="image" style="text-align: left">
					<i class="big grey archive icon"></i>
					<?=$statusstr?> <?=$witherrors?>
				</div>
				<div class="ui content">
					<div class="ui header"><?=date("D M j, Y h:ia",strtotime($submitdate))?></div>
					<div class="ui meta">
						<?=$deststr?> &nbsp; &nbsp; <?=$numseries?> series &nbsp; &nbsp; <?=HumanReadableFilesize($totalbytes)?>
						<p>Requested by <?=$username?></p>
						<? if ($numahead > 0) {
							echo "<p>$numahead exports queued ahead of this export</p>";
						} ?>
					</div>
					<div class="ui description">
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
									if (($destinationtype == "web") || ($destinationtype == "xnat") || ($destinationtype == "squirrel")) {
										if ((round($totals['complete']/$total)*100 == 100) || (($totals['submitted'] == 0) && ($totals['processing'] == 0))) {
											$zipfile = $_SERVER['DOCUMENT_ROOT'] . "/download/NIDB-$exportid.zip";
											if (file_exists($zipfile)) {
												$output = shell_exec("du -sb $zipfile");
												list($filesize, $fname) = preg_split('/\s+/', $output);
												$zipfilename = "NIDB-$exportid.zip";
											}
											else {
												$zipfile = $_SERVER['DOCUMENT_ROOT'] . "/download/NiDB-Squirrel-$exportid.zip";
												if (file_exists($zipfile)) {
													$output = shell_exec("du -sb $zipfile");
													list($filesize, $fname) = preg_split('/\s+/', $output);
													$zipfilename = "NiDB-Squirrel-$exportid.zip";
													//echo $zipfilename;
												}
												else {
													$filesize = 0;
												}
											}
											
											//echo "[$zipfilename] [$zipfile]";
											if ($filesize == 0) {
												echo "Zipping download...";
											}
											else {
												?>
													<div class="ui labeled button">
														<a class="ui blue button" href="download/<?=$zipfilename?>" title="Download zip file"><i class="download icon"></i> Download</a>
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
		
			<div class="ui bottom attached segment">
				<div class="ui accordion">
					<div class="title">
						<i class="dropdown icon"></i>
						View Export Log
					</div>
					<div class="content">
						<tt><pre><?=$log?></pre></tt>
					</div>
				</div>
			</div>
			<br><br>
			
			<div class="ui top attached segment">
				<h2 class="ui header">
					<div class="content">
						<i class="file export icon"></i> Local export status
						<div class="sub header">
							Status of local NiDB export
						</div>
					</div>
				</h2>
			</div>
		
			<table class="ui very compact bottom attached celled grey table">
				<thead>
					<th align="left">Subject</th>
					<th align="left">Study</th>
					<th align="left">Series</th>
					<th class="right aligned">Size</th>
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
				
				if ($modality != "") {
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
				}
				
				$total++;
				switch ($status) {
					case 'submitted': $totals['submitted']++; $class=""; break;
					case 'processing': $totals['processing']++; $class="blue"; $bgcolor = "#526FAA"; $color="#fff"; break;
					case 'complete': $totals['complete']++; $class="green"; $bgcolor = "#229320"; $color="#fff"; break;
					case 'error': $totals['error']++; $class="red"; $bgcolor = "#8E3023"; $color="#fff"; break;
				}
				?>
				<tr>
					<td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
					<td><a href="studies.php?id=<?=$studyid?>"><?="$uid$studynum"?></a></td>
					<td><?=$seriesnum?> - <?=$seriesdesc?></td>
					<td class="right aligned"><?=number_format($seriessize)?></td>
					<td class="<?=$class?>"> <?=ucfirst($status)?></td>
					<td><?=$statusmessage?></td>
				</tr>
				<?
			}
			?>
			</table>

			<? if ($destinationtype == 'remotenidb') { ?>
			<div class="ui top attached segment">
				<h2 class="ui header">
					<div class="content">
						<i class="file import icon"></i> Remote import status
						<div class="sub header">
							Status of remote NiDB import
						</div>
					</div>
				</h2>
			</div>
			<div class="bottom attached segment">
				<iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=1&total=<?=$total?>" width="100%" height="600px" style="border: 0px">No iframes available?</iframe>
			</div>
			<? } ?>
		</div>
		<?
	}
	?>
<? include("footer.php") ?>
