<?
 // ------------------------------------------------------------------------------
 // NiDB requeststatus.php
 // Copyright (C) 2004 - 2018
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
	require "includes.php";
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
		?><span class="staticmessage">Export [<?=$exportid?>] cancelled</span><?
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
			?><span class="staticmessage">Status reset for export [<?=$exportid?>]</span><?
		}
		else {
			?>Invalid export ID [<?=$exportid?>]<?
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
		?><span class="staticmessage">Export <?=$exportid?> re-queued</span><?
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
		$urllist['Search'] = "search.php";
		NavigationBar("Data export status", $urllist);
		
		?>
		<a href="requeststatus.php?viewall=1">Show all data exports</a>
		<br><br>
		<table class="graydisplaytable" width="100%">
			<thead>
				<th align="left">Request date</th>
				<th align="left">Format</th>
				<th align="left">Username</th>
				<th align="right">Number of series</th>
				<th align="right">Size</th>
				<th align="left">Progress</th>
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
			if ($viewall) { $sqlstring = "select * from exports order by submitdate desc limit 100"; }
			else { $sqlstring = "select * from exports order by submitdate desc limit 100"; }
		}
		else {
			if ($viewall) { $sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc limit 100"; }
			else { $sqlstring = "select * from exports where username = '" . $GLOBALS['username'] . "' order by submitdate desc limit 100"; }
		}
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$exportid = $row['export_id'];
			$submitdate = $row['submitdate'];
			$username = $row['username'];
			$destinationtype = $row['destinationtype'];
			$exportstatus = $row['status'];
			
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

			if ($exportstatus == "error") {
				$leftovers = $totals['complete'] = $totals['processing'] = 0;
				$totals['error'] = $total;
			}
				
				?>
				<tr>
					<td><?=date("D M j, Y h:ia",strtotime($submitdate))?></td>
					<td><?=$destinationtype?></td>
					<td><?=$username?></td>
					<td align="right"><?=$numseries?></td>
					<td align="right"><?=number_format($totalbytes)?></td>
					<td>
						<img src="horizontalchart.php?b=yes&w=400&h=15&v=<?=$totals['complete']?>,<?=$totals['processing']?>,<?=$totals['error']?>,<?=$leftovers?>&c=<?=$completecolor?>,<?=$processingcolor?>,<?=$errorcolor?>,<?=$othercolor?>"> <?=number_format(($totals['complete']/$total)*100,1)?>% complete <span style="font-size:8pt;color:gray">(<?=number_format($totals['complete'])?> of <?=number_format($total)?> series)</span>
					</td>
					<td><a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>"><?=ucfirst($exportstatus)?></a></td>
					<td>
						<? if ($exportstatus == "error") { ?>
						<a href="requeststatus.php?action=resetexport&exportid=<?=$exportid?>">Retry</a>
						<? } elseif (($exportstatus == "submitted") || ($exportstatus == "processing")) { ?>
						<a href="requeststatus.php?action=cancelexport&exportid=<?=$exportid?>">Cancel</a>
						<? } ?>
					</td>
					<td><?
					if ($destinationtype == "web") {
						if (round($totals['complete']/$total)*100 == 100) {
							$zipfile = $_SERVER['DOCUMENT_ROOT'] . "/download/NIDB-$exportid.zip";
							//echo "$zipfile<br>";
							if (file_exists($zipfile)) {
								$output = shell_exec("du -sb $zipfile");
								//echo "[$output]<br>";
								list($filesize, $fname) = preg_split('/\s+/', $output);
							}
							else {
								$filesize = 0;
							}
							
							if ($filesize == 0) {
								echo "Zipping download...";
							}
							else {
								?><a href="download/<?="NIDB-$exportid.zip"?>">Download</a> <span class="tiny"><?=number_format($filesize,0)?> bytes</span><?
							}
						}
						else {
							echo "Preparing download...";
						}
					}
					?>
					</td>
				</tr>
				<!--
				<tr style="font-size:9pt">
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><a href="requeststatus.php?action=viewexport&exportid=<?=$exportid?>"><?=$requestdate?></a>&nbsp;
					<?
						if (($GLOBALS['username'] == $username) || ($GLOBALS['issiteadmin'])) {
							?>
							<a href="requeststatus.php?action=cancelgroup&groupid=<?=$groupid?>" style="color:darkred; font-weight:bold" title="Cancel download">X</a>
							<?
						}
					?>
					</td>
					<? if (!$GLOBALS['ispublic']) { ?>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><?=$username?>&nbsp;</td>
					<? } ?>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray" align="right"><?=$minsec?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray" align="right"><?=number_format($totalbytes)?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray" align="right"><?=$lastupdate?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray; font-size:10pt">
						<img src="horizontalchart.php?b=yes&w=400&h=15&v=<?=$totals['complete']?>,<?=$totals['processing']?>,<?=$totals['problem']?>,<?=$leftovers?>&c=<?=$completecolor?>,<?=$processingcolor?>,<?=$errorcolor?>,<?=$othercolor?>">
						<?=number_format(($totals['complete']/$total)*100,1)?>% complete <span style="font-size:8pt;color:gray">(<?=number_format($totals['complete'])?> of <?=number_format($total)?> series)</span>
						<?
							if ($totals['processing'] > 0) {
								?>
								<span style="color: darkblue; font-size:8pt"><?=$totals['processing']?> processing</span>
								<?
							}
							if ($totals['problem'] > 0) {
								?>
								<br><span style="color: red; font-size:8pt"><?=$totals['problem']?> errors</span> 
								<a href="requeststatus.php?action=retryerrors&groupid=<?=$groupid?>" style="color:darkred; font-size:12pt;" title="Restart failed or cancelled series">&#8634;</a>
								<?
							}
							if ($totals['cancelled'] > 0) {
								?>
								<br><span style="color: red; font-size:8pt"><?=$totals['cancelled']?> cancelled</span>
								<a href="requeststatus.php?action=retryerrors&groupid=<?=$groupid?>" style="color:darkred; font-size:12pt;" title="Restart failed or cancelled series">&#8634;</a>
								<?
							}
							if (($totals['pending'] > 0) || ($totals['processing'] > 0) || ($totals[''] > 0)) {
								?>
								<br><span style="font-size:8pt">Expected completion: <?=$completedate?></span>
								<?
							}
						?>
					</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray">
					<?
					if ($destinationtype == "web") {
						if (round($totals['complete']/$total)*100 == 100) {
							$zipfile = $_SERVER['DOCUMENT_ROOT'] . "/download/NIDB-$groupid.zip";
							//echo "$zipfile<br>";
							if (file_exists($zipfile)) {
								$output = shell_exec("du -sb $zipfile");
								//echo "[$output]<br>";
								list($filesize, $fname) = preg_split('/\s+/', $output);
							}
							else {
								$filesize = 0;
							}
							
							if ($filesize == 0) {
								echo "Zipping download...";
							}
							else {
								?><a href="download/<?="NIDB-$groupid.zip"?>">Download</a> <span class="tiny"><?=number_format($filesize,0)?> bytes</span><?
							}
						}
						else {
							echo "Preparing download...";
						}
					}
					else {
						echo $destinationtype;
					}
					?>
				-->
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
					<summary>View Log</summary>
					<tt><pre><?=$log?></pre></tt>
					</details>
				</td>
			</tr>
		</table>
		
		<br><br>
		
		<table class="graydisplaytable" width="100%">
			<thead>
				<th align="left">Subject</th>
				<th align="left">Study</th>
				<th align="left">Series</th>
				<th align="right">Size</th>
				<th align="left">Status</th>
			</thead>
		<?
		$sqlstring = "select * from exportseries where export_id = $exportid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modality = strtolower($row['modality']);
			$seriesid = $row['series_id'];
			$status = $row['status'];
			
			$sqlstringB = "select a.*, b.*, d.project_name, e.uid from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.$modality" . "series_id = $seriesid order by uid, study_num, series_num";
			$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
			$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
			$seriesdesc = $rowB['series_desc'];
			if ($modality != "mr") {
				$seriesdesc = $rowB['series_protocol'];
			}
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
				<td><?=$uid?></td>
				<td><?="$uid$studynum"?></td>
				<td><?=$seriesnum?> - <?=$seriesdesc?></td>
				<td align="right"><?=number_format($seriessize)?></td>
				<td style="background-color: <?=$bgcolor?>; color: <?=$color?>"> <?=ucfirst($status)?></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
		return;
		
		
		$sqlstring = "select a.*, b.*, d.project_name, d.project_costcenter, e.uid, f.* from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id left join data_requests f on f.req_seriesid = a.$modality" . "series_id where f.req_groupid = $groupid order by req_groupid, uid, study_num, series_num";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numrows = mysqli_num_rows($result);

		$sqlstring = "select a.*, b.*, d.project_name, d.project_costcenter, e.uid, f.* from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id left join data_requests f on f.req_seriesid = a.$modality" . "series_id where f.req_groupid = $groupid order by req_groupid, uid, study_num, series_num";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	?>
		<b>Showing <?=$numrows?> requests</b>
		<br><br>
		<table width="100%" cellspacing="0" cellpadding="2">
			<tr>
				<td style="font-weight:bold; border-bottom: solid 2pt black">UID</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">Study Date</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">Destination type</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">FTP</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">Study Num</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">Format</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">Status</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">Complete date</td>
			</tr>
		<?
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$requestid = $row['request_id'];
				$series_id = $row[$modality . 'series_id'];
				$series_desc = $row['series_desc'];
				$series_num = $row['series_num'];
				$study_id = $row['study_id'];
				$study_num = $row['study_num'];
				$study_datetime = $row['study_datetime'];
				$uid = $row['uid'];
				$series_size = $row['series_size'];
				$project_name = $row['project_name'];
				$project_costcenter = $row['project_costcenter'];
				$status = $row['req_status'];
				$completedate = $row['req_completedate'];
				$format = $row['req_filetype'];
				$cpu = $row['req_cputime'];
				$destinationtype = $row['req_destinationtype'];
				
				if ($status == "complete") { $color = "#009933"; }
				if ($status == "processing") { $color = "#0000FF"; }
				if ($status == "pending") { $color = "#0000FF"; }
				if ($status == "problem") { $color = "#FF0000"; }
				if ($status == "error") { $color = "#FF0000"; }
				if ($status == "cancelled") { $color = "#FF0000"; }
				
				?>
				<tr style="font-size:10pt">
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><?=$uid?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><?=$study_datetime?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><? echo $destinationtype; ?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><? echo "$remoteftpserver$destinationpath"; ?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><a href="studies.php?id=<? echo $study_id; ?>"><span style="color: darkblue; text-decoration:underline"><?=$uid?><?=$study_num?></span></a>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><? echo $format; ?>&nbsp;</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray">
						<a href="requeststatus.php?action=viewdetails&requestid=<? echo $requestid; ?>"><span style="color:<? echo $color; ?>"><u><? echo $status; ?></u></span></a>&nbsp;
						<?
							if (in_array($status,array('processing','pending','problem','error','cancelled')) && $GLOBALS['isadmin']) {
								?><a href="requeststatus.php?action=resetexport&groupid=<?=$groupid?>&requestid=<?=$requestid?>" style="color: red" title="Clear status">x</a><?
							}
						?>
					</td>
					<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><? echo $completedate; ?>&nbsp;</td>
				</tr>
				<?
			}
		?>
		</table>
	<?
	}
	?>
<? include("footer.php") ?>
