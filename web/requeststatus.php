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
	$groupid = GetVariable("groupid");
	$requestid = GetVariable("requestid");
	$viewall = GetVariable("viewall");
	
	switch ($action) {
		case 'viewdetails':
			ViewDetails($requestid);
			break;
		case 'clearstatus':
			ClearStatus($requestid);
			ShowGroup($groupid, $page);
			break;
		case 'cancelgroup':
			CancelGroup($groupid);
			ShowList($viewall);
			break;
		case 'retryerrors':
			RetryErrors($groupid);
			ShowList($viewall);
			break;
		case 'showgroup':
			ShowGroup($groupid, $page);
			break;
		default:
			ShowList($viewall);
	}

	
	/* --------------------------------------------------- */
	/* ------- CancelGroup ------------------------------- */
	/* --------------------------------------------------- */
	function CancelGroup($groupid) {
		$sqlstring = "update data_requests set req_status = 'cancelled' where req_groupid = $groupid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><span class="staticmessage">Group download <?=$groupid?> cancelled</span><?
	}

	
	/* --------------------------------------------------- */
	/* ------- ClearStatus ------------------------------- */
	/* --------------------------------------------------- */
	function ClearStatus($requestid) {
		if ($requestid > 0) {
			$sqlstring = "update data_requests set req_status = '' where request_id = $requestid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			?><span class="staticmessage">Status for request [<?=$requestid?>] cleared</span><?
		}
		else {
			?>Invalid request ID<?
		}
	}

	
	/* --------------------------------------------------- */
	/* ------- RetryErrors ------------------------------- */
	/* --------------------------------------------------- */
	function RetryErrors($groupid) {
		$sqlstring = "select req_destinationtype from data_requests where req_groupid = $groupid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$desttype = $row['req_destinationtype'];
		
		/* the only download type that can resend single series is the remote NiDB. All others
		   may have consecutive series to handle and must be completely rerun */
		if ($desttype == "remotenidb") {
			$sqlstring = "update data_requests set req_status = '' where req_groupid = $groupid and req_status in ('problem', 'cancelled')";
		}
		else {
			$sqlstring = "update data_requests set req_status = '' where req_groupid = $groupid";
		}
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><span class="staticmessage">Group download <?=$groupid?> re-queued</span><?
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
		
		<div style="border: 1px solid #aaa; border-radius:4px; font-size:10pt; padding: 5px">
			<b>Notes</b>
			<ul>
				<li>Some older version of Linux cannot unzip files > 4GB. Upgrade unzip to v6.0 or try <code>jar xf thefile.zip</code>
			</ul>
		</div>
		<a href="requeststatus.php?viewall=1">View All Dates</a> (most recent 100 requests)
		<table width="100%" cellspacing="0" cellpadding="2">
			<tr>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Group</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Request Date</td>
				<? if (!$GLOBALS['ispublic']) { ?>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Username</td>
				<? } ?>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Total time</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Total size <span style="font-size:8pt; font-weight:normal; color: gray">(bytes)</span></td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Last update</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Status</td>
				<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Download</td>
				<!--<td style="font-weight:bold; border-bottom: solid 2pt black">&nbsp;Destination Path</td>-->
			</tr>
		<?
		$completecolor = "66AAFF";
		$processingcolor = "AAAAFF";
		$errorcolor = "FF6666";
		$othercolor = "EFEFFF";
		
		/* get the average processing time */
		$sqlstring = "SELECT avg(req_cputime) 'cpu' FROM `data_requests` where req_status = 'complete'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$avgcputime = $row['cpu'] + 0.5; /* add .5 sec just for yuks */

		/* get the number processing or still pending */
		$sqlstring = "SELECT count(*) 'count' FROM `data_requests` where req_status = 'pending' or req_status = 'processing' or req_status = '' or req_status is null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numpending = $row['count']; /* add .5 sec just for yuks */
		
		$waittime = ($avgcputime + 0.5) * $numpending; /* in seconds */
		//echo "[$waittime]";

		/* get the number processing or still pending */
		$sqlstring = "select date_add(now(), interval +$waittime second) 'waittime'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$completedate = $row['waittime'];
		
		/* get the groups that occur in the last 7 days */
		if ($GLOBALS['issiteadmin']) {
			if ($viewall) {
				$sqlstring = "SELECT distinct(req_groupid) 'groupid', req_modality FROM `data_requests` WHERE req_groupid > 0 order by req_groupid desc limit 100";
			}
			else {
				$sqlstring = "SELECT distinct(req_groupid) 'groupid', req_modality FROM `data_requests` WHERE req_date > date_add(now(), interval -7 day) and req_groupid > 0 order by req_groupid desc";
			}
		}
		else {
			if ($viewall) {
				$sqlstring = "SELECT distinct(req_groupid) 'groupid', req_modality FROM `data_requests` WHERE req_groupid > 0 and req_username = '" . $GLOBALS['username'] . "' order by req_groupid desc limit 100";
			}
			else {
				$sqlstring = "SELECT distinct(req_groupid) 'groupid', req_modality FROM `data_requests` WHERE req_date > date_add(now(), interval -7 day) and req_groupid > 0 and req_username = '" . $GLOBALS['username'] . "' order by req_groupid desc";
			}
		}
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$groupid = $row['groupid'];
			$modality = strtolower($row['req_modality']);
			
			$sqlstring = "select sum(b.series_size) 'totalbytes' from data_requests a left join $modality" . "_series b on a.req_seriesid = b.$modality" . "series_id where a.req_groupid = $groupid";
			$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
			$totalbytes = $row2['totalbytes'];
			
			$total = 0;
			unset($totals);
			$sqlstring = "SELECT req_status, sum(req_cputime) 'cpu', count(*) 'count', req_date, max(lastupdate) 'lastupdate', req_ip, req_username, req_destinationtype, req_nfsdir FROM `data_requests` where req_groupid = $groupid group by req_status";
			$result2 = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$requestdate = date("M j, Y G:i",strtotime($row2['req_date']));
				$requestingip = $row2['req_ip'];
				$username = $row2['req_username'];
				$lastupdate = $row2['lastupdate'];
				$destinationtype = $row2['req_destinationtype'];
				$destinationpath = $row2['req_nfsdir'];
				$cpu = $row2['cpu'];
				$totals[$row2['req_status']] = $row2['count'];
				$total += $row2['count'];
				
				$min = floor($cpu/60);
				$sec = round($cpu - ($min*60));
				$sec = str_pad($sec,2,'0',STR_PAD_LEFT);
				$minsec = "$min:$sec";
			}
			$leftovers = $total - $totals['complete'] - $totals['processing'] - $totals['problem'];
			?>
			<tr style="font-size:9pt">
				<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><?=$groupid?>&nbsp;</td>
				<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><a href="requeststatus.php?action=showgroup&groupid=<?=$groupid?>"><?=$requestdate?></a>&nbsp;
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
				&nbsp;</td>
				<!--<td style="border-bottom: solid 1pt gray; border-right: solid 1pt lightgray"><? if ($GLOBALS['username'] == $username) { echo $destinationpath; } ?>&nbsp;</td>-->
			</tr>
			<?
		}
		?>
		</table>
		<?
	}
	
	/* --------------------------------------------------- */
	/* ------- ShowGroup --------------------------------- */
	/* --------------------------------------------------- */
	function ShowGroup($groupid, $page) {
		$urllist['Search'] = "search.php";
		NavigationBar("Data export status", $urllist);
		
		if ($page == "") { $page = 1; }
		
		$sqlstring = "select distinct(req_modality) from data_requests where req_groupid = $groupid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$modality = $row['req_modality'];

		$sqlstring = "select a.*, b.*, d.project_name, d.project_costcenter, e.uid, f.* from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id left join data_requests f on f.req_seriesid = a.$modality" . "series_id where f.req_groupid = $groupid order by req_groupid, uid, study_num, series_num";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numrows = mysqli_num_rows($result);

		$numperpage = 500;
		$limit = $numperpage * ($page-1);
		
		$sqlstring = "select a.*, b.*, d.project_name, d.project_costcenter, e.uid, f.* from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id left join data_requests f on f.req_seriesid = a.$modality" . "series_id where f.req_groupid = $groupid order by req_groupid, uid, study_num, series_num";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	?>
		<b>Showing <?=$numrows?> requests from the last 7 days</b>
		<br>
		<?=$numperpage?>/page: 
		<?
			$remainder = $numrows;
			$pg = 1;
			while (($remainder+$numperpage) > $numperpage) {
				if ($pg == $page) {
				?><a href="requeststatus.php?page=<?=$pg?>" style="color: red"><?=$pg?></a> <span style="color:lightgray">|</span> <?
				}
				else {
				?><a href="requeststatus.php?page=<?=$pg?>" style="color: blue"><?=$pg?></a> <span style="color:lightgray">|</span> <?
				}
				
				$remainder -= $numperpage;
				$pg++;
			}
		?>
		<HR>
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
								?><a href="requeststatus.php?action=clearstatus&groupid=<?=$groupid?>&requestid=<?=$requestid?>" style="color: red" title="Clear status">x</a><?
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
