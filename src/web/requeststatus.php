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
	ob_start(); /* buffer output so the redirect-after-action (PRG) works despite the HTML rendered below */
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
	$exportid = (int)GetVariable("exportid");
	$requestid = (int)GetVariable("requestid");
	$viewall = GetVariable("viewall");
	
	/* actions on a specific export are limited to the export's owner or a site admin */
	if (in_array($action, array('resetexport', 'cancelexport', 'retryerrors', 'viewexport')) && !CanAccessExport($exportid)) {
		Error("Export [$exportid] not found or you do not have access to it");
		$action = "";
	}

	/* the mutating actions are GET links; redirect afterwards so a refresh doesn't repeat the action */
	switch ($action) {
		case 'viewdetails':
			ViewDetails($requestid);
			break;
		case 'resetexport':
			ob_start();
			ResetExport($exportid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("requeststatus.php?action=viewexport&exportid=$exportid");
			break;
		case 'cancelexport':
			ob_start();
			CancelExport($exportid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("requeststatus.php");
			break;
		case 'retryerrors':
			ob_start();
			RetryErrors($exportid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("requeststatus.php");
			break;
		case 'viewexport':
			ViewExport($exportid);
			break;
		default:
			ShowItemList($viewall);
	}


	/* --------------------------------------------------- */
	/* ------- CanAccessExport --------------------------- */
	/* --------------------------------------------------- */
	/* true if the export exists and the current user is a site admin or requested it */
	function CanAccessExport($exportid) {
		if ($exportid < 1) return false;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select username from exports where export_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		if (!$result || (mysqli_num_rows($result) < 1)) return false;
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

		return ($GLOBALS['issiteadmin'] || ($row['username'] == $GLOBALS['username']));
	}


	/* --------------------------------------------------- */
	/* ------- GetExportSeriesTotals --------------------- */
	/* --------------------------------------------------- */
	/* returns array(total series, per-status counts, total bytes) for an export */
	function GetExportSeriesTotals($exportid) {
		$total = 0;
		$totalbytes = 0;
		$totals = array('submitted' => 0, 'processing' => 0, 'complete' => 0, 'error' => 0);

		$stmt = mysqli_prepare($GLOBALS['linki'], "select modality, series_id, status from exportseries where export_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$seriestable = GetSeriesTableName($row['modality'] ?? '');
			if ($seriestable != "") {
				$modality = strtolower($row['modality']);
				$stmtB = mysqli_prepare($GLOBALS['linki'], "select series_size from `$seriestable` where `$modality" . "series_id` = ?");
				if ($stmtB) {
					mysqli_stmt_bind_param($stmtB, "i", $row['series_id']);
					$resultB = MySQLiBoundQuery($stmtB, __FILE__, __LINE__);
					mysqli_stmt_close($stmtB);
					$rowB = $resultB ? mysqli_fetch_array($resultB, MYSQLI_ASSOC) : null;
					$totalbytes += (int)($rowB['series_size'] ?? 0);
				}
			}

			$total++;
			if (isset($totals[$row['status']])) $totals[$row['status']]++;
		}

		return array($total, $totals, $totalbytes);
	}


	/* --------------------------------------------------- */
	/* ------- GetNumExportsAhead ------------------------ */
	/* --------------------------------------------------- */
	/* number of queued/processing exports submitted before $submitdate */
	function GetNumExportsAhead($submitdate) {
		$stmt = mysqli_prepare($GLOBALS['linki'], "select count(*) 'count' from exports where status in ('processing','submitted') and submitdate < ?");
		mysqli_stmt_bind_param($stmt, "s", $submitdate);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;

		return (int)($row['count'] ?? 0);
	}

	
	/* --------------------------------------------------- */
	/* ------- CancelExport ------------------------------- */
	/* --------------------------------------------------- */
	function CancelExport($exportid) {
		$stmt = mysqli_prepare($GLOBALS['linki'], "update exports set status = 'cancelled' where export_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Export [$exportid] cancelled");
	}

	
	/* --------------------------------------------------- */
	/* ------- ResetExport ------------------------------- */
	/* --------------------------------------------------- */
	function ResetExport($exportid) {
		if ($exportid > 0) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update exports set status = 'submitted', log = '' where export_id = ?");
			mysqli_stmt_bind_param($stmt, "i", $exportid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);

			$stmt = mysqli_prepare($GLOBALS['linki'], "update exportseries set status = 'submitted' where export_id = ?");
			mysqli_stmt_bind_param($stmt, "i", $exportid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);

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
		$stmt = mysqli_prepare($GLOBALS['linki'], "select destinationtype from exports where export_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		$desttype = $row['destinationtype'] ?? '';

		$stmt = mysqli_prepare($GLOBALS['linki'], "update exports set status = 'submitted' where export_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		/* the only download type that can resend single series is the remote NiDB. All others
		   may have consecutive/renumbered series and must be completely rerun */
		if ($desttype == "remotenidb") {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update exportseries set status = 'submitted' where export_id = ? and status in ('error', 'cancelled')");
		}
		else {
			$stmt = mysqli_prepare($GLOBALS['linki'], "update exportseries set status = 'submitted' where export_id = ?");
		}
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		Notice("Export $exportid re-queued");
	}
	

	/* --------------------------------------------------- */
	/* ------- ViewDetails ------------------------------- */
	/* --------------------------------------------------- */
	function ViewDetails($requestid) {
		
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from data_requests where request_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $requestid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;

		/* only the requester or a site admin may view the request */
		if (!$row || (!$GLOBALS['issiteadmin'] && ($row['req_username'] != $GLOBALS['username']))) {
			Error("Request [$requestid] not found or you do not have access to it");
			return;
		}

		?><div style="column-count: 3; -moz-column-count:3; -webkit-column-count:3"><?
		foreach ($row as $f => $v) {
			if (($f != 'req_results') && (stripos($f,'password') === false)) {
				echo "<b>" . htmlspecialchars($f) . "</b> - " . htmlspecialchars($v ?? '') . "<br>";
			}
		}
		echo "</div><pre><b>Request results</b><br>" . htmlspecialchars($row['req_results'] ?? '') . "</pre>";
	}


	/* --------------------------------------------------- */
	/* ------- ShowItemList ------------------------------ */
	/* --------------------------------------------------- */
	function ShowItemList($viewall) {
		ShowFlashMessage();
		?>
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h3 class="ui header"><?=($viewall ? "All exports" : "30 most recent exports")?></h3>
				</div>
				<div class="right aligned column">
					<? if ($viewall) { ?>
					<a class="ui small basic button" href="requeststatus.php?viewall=0">Show last 30 exports</a>
					<? } else { ?>
					<a class="ui small basic button" href="requeststatus.php?viewall=1">Show all</a>
					<? } ?>
				</div>
			</div>
			<script>
				$(document).ready(function() {
					$('.ui .progress').progress();
				});
			</script>

			<table class="ui very compact celled selectable table">
				<thead>
					<tr>
						<th>Submitted</th>
						<? if ($GLOBALS['issiteadmin']) { ?><th>Requested by</th><? } ?>
						<th>Destination</th>
						<th class="right aligned">Objects</th>
						<th class="right aligned">Size</th>
						<th style="min-width: 180px">Status</th>
						<th>Output</th>
						<th class="center aligned">Actions</th>
					</tr>
				</thead>
				<tbody>
		<?
		$limit = $viewall ? "" : " limit 30";
		if ($GLOBALS['issiteadmin']) {
			$sqlstring = "select * from exports order by submitdate desc$limit";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			$sqlstring = "select * from exports where username = ? order by submitdate desc$limit";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, "s", $GLOBALS['username']);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($GLOBALS['username']));
			mysqli_stmt_close($stmt);
		}
		$numrows = 0;
		while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
			$numrows++;
			$exportid = $row['export_id'];
			$submitdate = $row['submitdate'];
			$username = $row['username'];
			$destinationtype = $row['destinationtype'];
			$ndaFlags = explode(",", $row['nda_flags'] ?? "");
			$exportstatus = $row['status'];
			$connectionid = $row['remotenidb_connectionid'];
			$transactionid = $row['remotenidb_transactionid'];

			switch ($destinationtype) {
				case "web": $deststr = "<i class='cloud download alternate icon'></i> Web"; break;
				case "publicdownload": $deststr = "<i class='people carry icon'></i> Public Download"; break;
				case "remotenidb": $deststr = "<em data-emoji=':chipmunk:'></em> Remote NiDB"; break;
				case "nfs": $deststr = "<i class='server icon'></i> NFS"; break;
				case "ndar": $deststr = "<i class='server icon'></i> NDA"; break;
				case "dicomae": $deststr = "<i class='paper plane outline icon'></i> DICOM AE (PACS)"; break;
				default: $deststr = htmlspecialchars(ucfirst($destinationtype ?? ''));
			}

			list($total, $totals, $totalbytes) = GetExportSeriesTotals($exportid);
			$pctcomplete = ($total > 0) ? ($totals['complete']/$total)*100 : 100;
			$complete = (($totals['complete'] >= $total) || (($totals['submitted'] == 0) && ($totals['processing'] == 0)));

			/* get exports in queue ahead of this one */
			$numahead = 0;
			if (($exportstatus == 'submitted') || ($exportstatus == 'pending')) {
				$numahead = GetNumExportsAhead($submitdate);
			}

			switch ($exportstatus) {
				case "submitted":
				case "pending": $statuslabel = "<div class='ui small grey label'><i class='clock outline icon'></i>Queued</div>"; $rowclass = ""; break;
				case "processing": $statuslabel = "<div class='ui small blue label'><i class='spinner loading icon'></i>Processing</div>"; $rowclass = ""; break;
				case "complete": $statuslabel = "<div class='ui small green label'><i class='check icon'></i>Complete</div>"; $rowclass = ""; break;
				case "error": $statuslabel = "<div class='ui small red label'><i class='exclamation circle icon'></i>Error</div>"; $rowclass = "negative"; break;
				case "cancelled": $statuslabel = "<div class='ui small label'><i class='times icon'></i>Cancelled</div>"; $rowclass = "disabled"; break;
				default: $statuslabel = "<div class='ui small label'>" . htmlspecialchars(ucfirst($exportstatus ?? '')) . "</div>"; $rowclass = "";
			}
			?>
			<tr class="<?=$rowclass?>">
				<td style="white-space: nowrap"><a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>"><?=date("M j, Y g:ia", strtotime($submitdate))?></a></td>
				<? if ($GLOBALS['issiteadmin']) { ?><td><?=htmlspecialchars($username ?? "")?></td><? } ?>
				<td style="white-space: nowrap"><?=$deststr?></td>
				<td class="right aligned"><?=number_format($total)?></td>
				<td class="right aligned" style="white-space: nowrap"><?=HumanReadableFilesize($totalbytes)?></td>
				<td>
					<?=$statuslabel?>
					<? if ($totals['error'] > 0) { ?>
						<span style="font-size: smaller; color: red"><?=number_format($totals['error'])?> errors</span>
					<? } ?>
					<? if ($pctcomplete < 100) { ?>
					<div class="ui tiny <?=($totals['error'] > 0 ? "error" : "blue")?> progress" data-percent="<?=$pctcomplete?>" style="margin: 0.5em 0 0 0" title="<?=number_format($totals['complete'])?> of <?=number_format($total)?> objects exported (<?=number_format($pctcomplete, 1)?>%)">
						<div class="bar"></div>
					</div>
					<span style="font-size: smaller; color: gray"><?=number_format($totals['complete'])?> of <?=number_format($total)?></span>
					<? } ?>
					<? if ($numahead > 0) { ?>
					<br><span style="font-size: smaller; color: gray"><?=$numahead?> queued ahead</span>
					<? } ?>
				</td>
				<td>
					<? DisplayExportOutput($row, $destinationtype, $ndaFlags, $complete); ?>
					<? if (($destinationtype == "remotenidb") && ($connectionid != "") && ($transactionid != "")) { ?>
					<iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=0&total=<?=$total?>" width="300px" height="50px" style="border: 0px">Checking with remote server...</iframe>
					<? } ?>
				</td>
				<td class="center aligned" style="white-space: nowrap">
					<div class="ui mini basic icon buttons">
						<? if ($exportstatus == "error") { ?>
						<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Retry failed series" class="ui button"><i class="sync alternate icon"></i></a>
						<? } elseif (($exportstatus == "complete") || ($exportstatus == "cancelled")) { ?>
						<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>" title="Resend all series" class="ui button"><i class="redo icon"></i></a>
						<? } elseif (($exportstatus == "submitted") || ($exportstatus == "processing")) { ?>
						<a href="requeststatus.php?action=cancelexport&exportid=<?=$exportid?>" title="Cancel the remaining series" class="ui button" onClick="return confirm('Cancel this export?')"><i class="red times circle icon"></i></a>
						<? } ?>
					</div>
				</td>
			</tr>
			<?
		}
		if ($numrows == 0) {
			?><tr><td colspan="<?=($GLOBALS['issiteadmin'] ? 8 : 7)?>" class="center aligned" style="color: gray">No exports found</td></tr><?
		}
		?>
				</tbody>
			</table>
		</div>
		<?
	}


	/* --------------------------------------------------- */
	/* ------- DisplayExportOutput ----------------------- */
	/* --------------------------------------------------- */
	/* the 'Output' cell of the export list: download link, NDA location, etc */
	function DisplayExportOutput($row, $destinationtype, $ndaFlags, $complete) {
		$exportid = $row['export_id'];

		/* web download: destinationtype is 'web', or 'ndar' with NDA_WEBDOWNLOAD */
		$webdownload = (($destinationtype == "web") || ((($destinationtype == "ndar") || ($destinationtype == "ndarcsv")) && in_array('NDA_WEBDOWNLOAD', $ndaFlags)));

		if ($webdownload) {
			if (!$complete) {
				?><span style="color: gray">Preparing download...</span><?
				return;
			}
			$zipFileName = "NIDB-$exportid.zip";
			$zipFilePath = $GLOBALS['cfg']['webdir'] . "/download/$zipFileName";
			$filesize = file_exists($zipFilePath) ? filesize($zipFilePath) : 0;

			if ($filesize == 0) {
				?><span style="color: gray" title="<?=htmlspecialchars($zipFilePath)?>"><i class="spinner loading icon"></i> Zipping...</span><?
			}
			else {
				?><a class="ui mini fluid blue button" href="download/<?=$zipFileName?>" title="Download zip file"><i class="download icon"></i> Download <span style="font-weight: normal"><?=HumanReadableFilesize($filesize)?></span></a><?
			}
		}
		elseif (($destinationtype == "ndar") || ($destinationtype == "ndarcsv")) {
			if (!$complete) {
				?><span style="color: gray">Preparing NDA export...</span><?
			}
			elseif (($row['exported_path'] ?? "") != "") {
				?><span style="font-family: monospace; font-size: smaller; user-select: all">scp://<?=htmlspecialchars(gethostname())?><?=htmlspecialchars($row['exported_path'])?></span><?
			}
			else {
				?><span style="color: gray; font-size: smaller" title="Export path was not recorded for this export">Look in <span style="font-family: monospace"><?=htmlspecialchars($GLOBALS['cfg']['exportdir'])?></span></span><?
			}
		}
		elseif (($destinationtype == "nfs") && (($row['nfsdir'] ?? "") != "")) {
			?><span style="font-family: monospace; font-size: smaller"><?=htmlspecialchars($row['nfsdir'])?></span><?
		}
	}
	
	
	/* --------------------------------------------------- */
	/* ------- ViewExport -------------------------------- */
	/* ---Contribution: Muhammad Asim Mubeen (Dec 2023)--- */
	/* --------------------------------------------------- */
	function ViewExport($exportid) {
		ShowFlashMessage();

		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from exports where export_id = ?");
		mysqli_stmt_bind_param($stmt, "i", $exportid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		$row = $result ? mysqli_fetch_array($result, MYSQLI_ASSOC) : null;
		if (!$row) {
			Error("Export [$exportid] not found");
			return;
		}
		$log = $row['log'];
		$submitdate = $row['submitdate'];
		$username = $row['username'];
		$destinationtype = $row['destinationtype'];
		$exportstatus = $row['status'];
		$connectionid = $row['remotenidb_connectionid'];
		$transactionid = $row['remotenidb_transactionid'];
		?>
		<div class="ui container">
			<?
			
			switch ($destinationtype) {
				case "web": $deststr = "<i class='cloud download alternate icon'></i> Web"; break;
				case "publicdownload": $deststr = "<i class='people carry icon'></i> Public Download"; break;
				case "remotenidb": $deststr = "<em data-emoji=':chipmunk:'></em> Remote NiDB"; break;
				case "nfs": $deststr = "<i class='server icon'></i> NFS"; break;
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
			
			list($total, $totals, $totalbytes) = GetExportSeriesTotals($exportid);
			$numseries = $total;
			$pctcomplete = ($total > 0) ? ($totals['complete']/$total)*100 : 100;
			$complete = (($totals['complete'] >= $total) || (($totals['submitted'] == 0) && ($totals['processing'] == 0)));

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
			if (($exportstatus == 'submitted') || ($exportstatus == 'pending')) {
				$numahead = GetNumExportsAhead($submitdate);
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
						<p>Requested by <?=htmlspecialchars($username ?? "")?></p>
						<? if ($numahead > 0) {
							echo "<p>$numahead exports queued ahead of this export</p>";
						} ?>
					</div>
					<div class="ui description">
						<? if (($destinationtype == "remotenidb") && ($connectionid != "") && ($transactionid != "")) { ?>
						<br><iframe src="ajaxapi.php?action=remoteexportstatus&connectionid=<?=$connectionid?>&transactionid=<?=$transactionid?>&detail=0&total=<?=$total?>" width="650px" height="50px" style="border: 0px">Checking with remote server...</iframe>
						<? }
						
						if ($pctcomplete < 100) {
						?>
						<div class="ui small progress <?=$error?>" data-percent="<?=$pctcomplete?>">
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
										if ($complete) {
											$filesize = 0;
											$zipfilename = "";
											foreach (array("NIDB-$exportid.zip", "NiDB-Squirrel-$exportid.zip") as $f) {
												$zipfile = $_SERVER['DOCUMENT_ROOT'] . "/download/$f";
												if (file_exists($zipfile)) {
													$filesize = filesize($zipfile);
													$zipfilename = $f;
													break;
												}
											}

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
						<tt><pre><?=htmlspecialchars($log ?? '')?></pre></tt>
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
			$stmt = mysqli_prepare($GLOBALS['linki'], "select * from exportseries where export_id = ?");
			mysqli_stmt_bind_param($stmt, "i", $exportid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
				$modality = strtolower($row['modality'] ?? '');
				$seriesid = (int)$row['series_id'];
				$status = $row['status'] ?? '';
				$statusmessage = $row['statusmessage'] ?? '';

				/* reset per row so a missing series doesn't show the previous row's values */
				$seriesdesc = $subjectid = $studyid = $uid = $seriesnum = $studynum = '';
				$seriessize = 0;

				$seriestable = GetSeriesTableName($modality);
				if ($seriestable != "") {
					$sqlstringB = "select a.*, b.*, d.project_name, e.uid, e.subject_id from `$seriestable` a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.`$modality" . "series_id` = ? order by uid, study_num, series_num";
					$stmtB = mysqli_prepare($GLOBALS['linki'], $sqlstringB);
					if ($stmtB) {
						mysqli_stmt_bind_param($stmtB, "i", $seriesid);
						$resultB = MySQLiBoundQuery($stmtB, __FILE__, __LINE__, $sqlstringB, array($seriesid));
						mysqli_stmt_close($stmtB);
						$rowB = $resultB ? mysqli_fetch_array($resultB, MYSQLI_ASSOC) : null;
						if ($rowB) {
							$seriesdesc = ($modality == "mr") ? $rowB['series_desc'] : $rowB['series_protocol'];
							$subjectid = $rowB['subject_id'];
							$studyid = $rowB['study_id'];
							$uid = $rowB['uid'];
							$seriesnum = $rowB['series_num'];
							$studynum = $rowB['study_num'];
							$seriessize = (int)($rowB['series_size'] ?? 0);
						}
					}
				}

				switch ($status) {
					case 'processing': $class="blue"; break;
					case 'complete': $class="green"; break;
					case 'error': $class="red"; break;
					default: $class="";
				}
				?>
				<tr>
					<td><a href="subjects.php?id=<?=$subjectid?>"><?=htmlspecialchars($uid ?? '')?></a></td>
					<td><a href="studies.php?id=<?=$studyid?>"><?=htmlspecialchars("$uid$studynum")?></a></td>
					<td><?=$seriesnum?> - <?=htmlspecialchars($seriesdesc ?? '')?></td>
					<td class="right aligned"><?=number_format($seriessize)?></td>
					<td class="<?=$class?>"> <?=ucfirst($status)?></td>
					<td><?=htmlspecialchars($statusmessage)?></td>
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
