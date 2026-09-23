<?
 // ------------------------------------------------------------------------------
 // NiDB stats.php
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
		<title>NiDB - Statistics</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* check if they have permissions to this view page */
	if (!isSiteAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}

	/* ----- setup variables ----- */
	$action = GetVariable("action");

	/* determine action */
	if ($action == "") {
		DisplayStats();
	}
	else {
		DisplayStats();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayStats ----------------------- */
	/* -------------------------------------------- */
	function DisplayStats() {

		$currentyear = date("Y");

		$sqlstring = "select count(*) count from subjects where isactive = 1";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numsubjects = number_format($row['count']);
		$numtotalsubjects = $row['count'];

		$sqlstring = "select count(*) count from studies";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numstudies = $row['count'];

		$totalseries = 0;
		$totalsize = 0;
		$seriescounts = array();
		$modalities = array(); /* modalities with a <modality>_series table, used by the total storage chart */
		$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '%\_series'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			//print_r($row);
			$tablename = $row['Tables_in_' . $GLOBALS['cfg']['mysqldatabase'] . ' (%\_series)'];
			//echo $tablename;
			$parts = explode("_", $tablename);
			$modality = $parts[0];

			/* ignore deprecated* tables (e.g. deprecated_mr_series). They are not present on all
			   instances, and $parts[0] would otherwise build a non-existent "deprecated_series". */
			if (strpos($tablename, 'deprecated') === 0)
				continue;

			if (($modality != 'audit') && ($modality != 'upload') && ($modality != 'package')) {
				$modalities[] = $modality;
				$sqlstring2 = "select count(*) 'count', sum(series_size) 'size' from $modality" . "_series";
				$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
				$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
				$totalseries += $row2['count'];
				$totalsize += $row2['size'];
				$seriescounts[$modality] = number_format($row2['count']);
				$seriessize[$modality] = HumanReadableFilesize($row2['size']);
			}
		}

		/* total series qa time */
		$sqlstring = "select sum(cputime) totalcpu from mr_qa";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$diff = $row['totalcpu'];
		$totalseriesqacpu = FormatCountdown($diff);

		/* total study qa time */
		$sqlstring = "select sum(cputime) totalcpu from mr_studyqa";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$diff = $row['totalcpu'];
		$totalstudyqacpu = FormatCountdown($diff);

		/* request processing time, number of completed requests, and mean request time in one pass */
		$sqlstring = "select sum(req_cputime) 'totalrequestcpu', sum(req_completedate > '0000-00-00 00:00:00') 'numcomplete', avg(case when req_completedate > '0000-00-00 00:00:00' then time_to_sec(timediff(req_completedate, req_date)) end) 'avgtime' from data_requests";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$totalrequestcpu = FormatCountdown($row['totalrequestcpu']);
		$avgrequesttime = FormatCountdown($row['avgtime']);
		$numcomplete = intval($row['numcomplete']);

		/* median request time (the middle row of the completed requests, ordered by duration) */
		$medianrequesttime = FormatCountdown(null);
		if ($numcomplete > 0) {
			$med = intval(floor($numcomplete/2));
			$sqlstring = "SELECT time_to_sec(timediff(req_completedate, req_date)) avgtime FROM `data_requests` where req_completedate > '0000-00-00 00:00:00' order by avgtime limit $med,1";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$medianrequesttime = FormatCountdown($row['avgtime'] ?? null);
		}

		$uptime = shell_exec('uptime');

		/* subject demographics, in one pass over subjects. 'not specified' keeps the original
		   not in () semantics, so NULL genders are not counted */
		$sqlstring = "select sum(gender = 'F') 'numfemales', sum(gender = 'M') 'nummales', sum(gender = 'O') 'numother', sum(gender = 'U') 'numunknown', sum(gender not in ('F','M','O','U')) 'numnotspec' from subjects";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numfemales = intval($row['numfemales']);
		$nummales = intval($row['nummales']);
		$numother = intval($row['numother']);
		$numunknown = intval($row['numunknown']);
		$numnotspec = intval($row['numnotspec']);
		$pctdivisor = ($numtotalsubjects > 0) ? $numtotalsubjects : 1;
		
		?>
		
		<div style="padding: 0 15px">
			<h3 class="ui dividing header">Summary</h3>
			<div class="ui four column stackable grid">
				<div class="column">
					<h4 class="ui top attached block header">Series Info</h4>
					<div class="ui bottom attached segment">
						<span style="font-size:10pt;">
						<b>Available Data:</b><br>
						<?=$numsubjects;?> subjects<br>
						<?=number_format($numstudies);?> studies<br>
						<br>
						<table class="ui very compact small celled table">
							<tr>
								<th><b>Series</b></th>
								<th align="right"><b>Count</b></th>
								<th align="right"><b>Size</b></th>
							</tr>
						<?
							foreach ($seriescounts as $modality => $count) {
								?>
								<tr>
									<td><?=strtoupper($modality)?></td>
									<td align="right"><?=$count?></td>
									<td align="right"><?=$seriessize[$modality]?></td>
								</tr>
								<?
							}
						?>
							<tr style="color: #5882FA; font-weight: bold">
								<td>Total</td>
								<td align="right"><?=number_format($totalseries)?></td>
								<td align="right"><?=HumanReadableFilesize($totalsize)?></td>
							</tr>
						</table>
						</span>
					</div>
				</div>
				<div class="column">
					<h4 class="ui top attached block header">System Info</h4>
					<div class="ui bottom attached segment">
						<div class="ui small header">Uptime</div>
						<?=$uptime;?>
						<div class="ui small header">Data Requests</div>
						<b>CPU time:</b> <?=$totalrequestcpu?><br>
						<b>Mean request time:</b> <?=$avgrequesttime?><br>
						<b>Median request time:</b> <?=$medianrequesttime?><br>
					</div>
				</div>
				<div class="column">
					<h4 class="ui top attached block header">Subject Demographics</h4>
					<div class="ui bottom attached segment">
						<table class="reviewtable">
							<tr>
								<td class="label"># females</td>
								<td class="value"><?=$numfemales?> (<?=number_format(($numfemales/$pctdivisor)*100,1)?>%)</td>
							</tr>
							<tr>
								<td class="label"># males</td>
								<td class="value"><?=$nummales?> (<?=number_format(($nummales/$pctdivisor)*100,1)?>%)</td>
							</tr>
							<tr>
								<td class="label"># other</td>
								<td class="value"><?=$numother?> (<?=number_format(($numother/$pctdivisor)*100,1)?>%)</td>
							</tr>
							<tr>
								<td class="label"># unknown</td>
								<td class="value"><?=$numunknown?> (<?=number_format(($numunknown/$pctdivisor)*100,1)?>%)</td>
							</tr>
							<tr>
								<td class="label"># not specified</td>
								<td class="value"><?=$numnotspec?> (<?=number_format(($numnotspec/$pctdivisor)*100,1)?>%)</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="column">
					<h4 class="ui top attached block header">MR</h4>
					<div class="ui bottom attached segment">
						<div class="ui small header">CPU time</div>
						<b>Total series QA CPU time:</b> <?=$totalseriesqacpu?><br>
						<b>Total study QA CPU time:</b> <?=$totalstudyqacpu?><br>
					</div>
				</div>
			</div>

			<h3 class="ui dividing header">Storage and Studies Over Time</h3>
			<h4 class="ui top attached block header">Total Cumulative Storage</h4>
			<div class="ui bottom attached segment">
				<?
					/* cumulative storage of all modalities across every site, including sites not listed in the by-site charts */
					$jsonstrings = array();
					$points = array(); /* [ms timestamp, cumulative GB], used for the projection */
					$cumtotal = 0;
					/* combine the series of every modality (table names come from show tables above, not user input).
					   Each table is summed per study first, so far fewer rows are combined and joined to studies */
					$unions = array();
					foreach ($modalities as $modality)
						$unions[] = "select study_id, sum(series_size) series_size from $modality" . "_series group by study_id";
					if (count($unions) > 0) {
						$sqlstring = "SELECT unix_timestamp(DATE(a.study_datetime)) Date, sum(b.series_size) 'totalsize' FROM studies a join (" . implode(" union all ", $unions) . ") b on a.study_id = b.study_id GROUP BY DATE(a.study_datetime) order by Date";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$date = $row['Date']*1000;
							if ($date > 0) {
								$cumtotal += $row['totalsize']/1000/1000/1000;
								$jsonstrings[] = "['$date', " . number_format($cumtotal,1,'.','') . "]";
								$points[] = array($date, $cumtotal);
							}
						}
					}

					/* ----- linear projection, 1 and 3 years past the latest data point -----
					   rate = least-squares slope of the cumulative total over the last 12 months.
					   The shaded range spans the slopes of the earlier and later 6 months of that
					   window, so its width shows how steady the growth rate was over the year. */
					$projection = null;
					$dayms = 86400*1000;
					$yearms = 365.25*$dayms;
					$windowstart = time()*1000 - $yearms;
					$halfway = time()*1000 - $yearms/2;
					$recent = array(); $firsthalf = array(); $secondhalf = array();
					foreach ($points as $p) {
						if ($p[0] < $windowstart) continue;
						$recent[] = $p;
						if ($p[0] < $halfway) $firsthalf[] = $p; else $secondhalf[] = $p;
					}
					$rate = StatsLinearSlope($recent); /* GB per day */
					/* require data spread over at least 3 months of the window for a meaningful fit */
					if (($rate !== null) && (count($recent) >= 3) && (($recent[count($recent)-1][0] - $recent[0][0]) >= 90*$dayms)) {
						$rates = array($rate);
						foreach (array(StatsLinearSlope($firsthalf), StatsLinearSlope($secondhalf)) as $r)
							if ($r !== null) $rates[] = $r;
						$last = $points[count($points)-1];
						$projection = array('t0' => intval($last[0]), 'v0' => $last[1], 'rate' => $rate, 'low' => max(0, min($rates)), 'high' => max($rates));
						foreach (array('rate', 'low', 'high') as $k) {
							foreach (array(1, 3) as $yrs)
								$projection[$k . $yrs] = $last[1] + $projection[$k] * 365.25 * $yrs;
						}
					}
				?>
				<script type="text/javascript" src="scripts/flot/jquery.flot.fillbetween.min.js"></script>
				<script>
					$(function() {
						var data5 = [
							{
							label: "All imaging data",
							data: [<?=implode2(',',$jsonstrings)?>]
							},
						<? if ($projection !== null) { ?>
							{ id: "projlow", data: <?=StatsProjectionSeries($projection, 'low')?>, color: "#cb4b4b", lines: { show: true, fill: false, lineWidth: 0 } },
							{ label: "Projection range", id: "projhigh", fillBetween: "projlow", data: <?=StatsProjectionSeries($projection, 'high')?>, color: "#cb4b4b", lines: { show: true, fill: 0.2, lineWidth: 0 } },
							{ label: "Projection (linear, last 12 months)", data: <?=StatsProjectionSeries($projection, 'rate')?>, color: "#cb4b4b", lines: { show: true, fill: false, lineWidth: 2 }, points: { show: true, radius: 3 } },
						<? } ?>
						];

						var options5 = {
							series: {
								lines: {
									show: true,
									fill: true
								},
								points: {
									show: false
								}
							},
							legend: {
								noColumns: 6
							},
							xaxis: {
								mode: "time",
								timeformat: "%Y-%m-%d"
							},
							yaxis: {
								min: 0,
								tickDecimals: 1
							},
							selection: {
								mode: "x"
							}
						};

						options5.legend.position = "nw"; /* the rising curve and projection fill the top-right */
						var plot5 = $.plot($("#placeholder5"), data5, options5);
					});
				</script>

				<div class='flot-y-axis'>
					<div class='flot-tick-label'>GB</div>
				</div>
				<div id="placeholder5" style="width:100%;height:300px;"></div>
				<? if ($projection !== null) { ?>
				<div style="margin-top: 8px">
					<b>Projected total</b> at <?=StatsFormatGB($projection['rate']*30.44)?>/month (linear fit of the last 12 months):
					<b>+1 year</b> <?=StatsFormatGB($projection['rate1'])?> (range <?=StatsFormatGB($projection['low1'])?> &ndash; <?=StatsFormatGB($projection['high1'])?>),
					<b>+3 years</b> <?=StatsFormatGB($projection['rate3'])?> (range <?=StatsFormatGB($projection['low3'])?> &ndash; <?=StatsFormatGB($projection['high3'])?>)
				</div>
				<? } else { ?>
				<div style="margin-top: 8px; color: #888">Not enough data from the last 12 months to project future storage.</div>
				<? } ?>
			</div>

			<h4 class="ui top attached block header">Cumulative Data Storage by Site</h4>
			<div class="ui bottom attached segment">
				<?
					/* daily MR storage and study counts per site, shared by the three by-site charts: one query for
					   all sites instead of one per site per chart. The sites listed are the ones that have studies */
					$sitedays = array(); /* $sitedays[site][] = [ms timestamp, GB, study count] */
					$sqlstring = "SELECT a.study_site, unix_timestamp(DATE(a.study_datetime)) Date, COUNT(DISTINCT a.study_datetime) totalCount, sum(b.series_size) 'totalsize' FROM studies a left join mr_series b on a.study_id = b.study_id where a.study_site in ('hhntMRC20107','AWP45351','WHSKYRA-01') GROUP BY a.study_site, DATE(a.study_datetime) order by a.study_site, Date";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$date = $row['Date']*1000;
						if ($date > 0)
							$sitedays[$row['study_site']][] = array($date, $row['totalsize']/1000/1000/1000, $row['totalCount']);
					}
					$sites = array_keys($sitedays);
					natcasesort($sites);
				?>
				<script>
					$(function() {
							var data2 = [
					<?
						foreach ($sites as $site) {
							$jsonstrings = array();
							$cumtotal = 0;
							?>
								{
								label: <?=json_encode($site)?>,
								data: [<?
							foreach ($sitedays[$site] as $day) {
								$cumtotal += $day[1];
								$jsonstrings[] = "['$day[0]', " . number_format($cumtotal,1,'.','') . "]";
							}
							?><?=implode2(',',$jsonstrings)?>]
								},
							<?
							
						}
						//PrintVariable($studysites,'StudySites');
					?>
						];
					
						var options2 = {
							series: {
								lines: {
									show: true,
									fill: true
								},
								points: {
									show: false
								}
							},
							legend: {
								noColumns: 6
							},
							xaxis: {
								mode: "time",
								timeformat: "%Y-%m-%d"
							},
							yaxis: {
								min: 0,
								tickDecimals: 1
							},
							selection: {
								mode: "x"
							}
						};

						var placeholder2 = $("#placeholder2");

						var plot2 = $.plot(placeholder2, data2, options2);
					});
				</script>
				
				<div class='flot-y-axis'>
					<div class='flot-tick-label'>GB</div>
				</div>
				<div id="placeholder2" style="width:100%;height:300px;"></div>
			</div>
			<h4 class="ui top attached block header">Cumulative Studies by Day</h4>
			<div class="ui bottom attached segment">
				<script>
					$(function() {
							var data3 = [
					<?
						//foreach ($sites as $site) {
							$jsonstrings = array();
							$cumtotal = 0;
							?>
								{
								label: "Studies",
								data: [<?
							$sqlstring = "SELECT unix_timestamp(DATE(a.study_datetime)) Date, COUNT(DISTINCT a.study_datetime) totalCount FROM studies a where (a.study_site in ('hhntMRC20107','AWP45351','WHSKYRA-01') or a.study_modality in ('eeg', 'et', 'task', 'video', 'gsr', 'assessment')) GROUP BY DATE(a.study_datetime) order by Date";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$date = $row['Date']*1000;
								if ($date > 0) {
									//$totalsize = $row['totalsize']/1000/1000/1000;
									$totalcount = $row['totalCount'];
									//$cumtotal += $totalsize;
									$cumtotal += $totalcount;
									//$studysites[$site][$date]['size'] = $totalsize;
									//$studysites[$site][$date]['count'] = $totalcount;
									
									//$jsonstrings[] .= "['$date', $totalsize]";
									$jsonstrings[] .= "['$date', " . $cumtotal . "]";
								}
							}
							?><?=implode2(',',$jsonstrings)?>]
								},
							<?
							
						//}
						//PrintVariable($studysites,'StudySites');
					?>
						];
					
						var options3 = {
							series: {
								lines: {
									show: true,
									fill: true
								},
								points: {
									show: false
								}
							},
							legend: {
								noColumns: 6
							},
							xaxis: {
								mode: "time",
								timeformat: "%Y-%m-%d"
							},
							yaxis: {
								min: 0,
								tickDecimals: 1
							},
							selection: {
								mode: "x"
							}
						};

						var placeholder3 = $("#placeholder3");

						var plot3 = $.plot(placeholder3, data3, options3);
					});
				</script>
				
				<div class='flot-y-axis'>
					<div class='flot-tick-label'>N</div>
				</div>
				<div id="placeholder3" style="width:100%;height:300px;"></div>
			</div>
			
			
			<h4 class="ui top attached block header">Data Storage by Day by Site</h4>
			<div class="ui bottom attached segment">
				<script>
					$(function() {
							var data = [
					<?
						foreach ($sites as $site) {
							$jsonstrings = array();
							?>
								{
								label: <?=json_encode($site)?>,
								data: [<?
							foreach ($sitedays[$site] as $day)
								$jsonstrings[] = "['$day[0]', " . number_format($day[1],1,'.','') . "]";
							?><?=implode2(',',$jsonstrings)?>]
								},
							<?
							
						}
						//PrintVariable($studysites,'StudySites');
					?>
						];
					
						var options = {
							series: {
								lines: {
									show: true,
									fill: true
								},
								points: {
									show: false
								}
							},
							legend: {
								noColumns: 6
							},
							xaxis: {
								mode: "time",
								timeformat: "%Y-%m-%d"
							},
							yaxis: {
								min: 0,
								tickDecimals: 1
							},
							selection: {
								mode: "x"
							}
						};

						var placeholder = $("#placeholder");

						var plot = $.plot(placeholder, data, options);									
					});
				</script>
				
				<div class='flot-y-axis'>
					<div class='flot-tick-label'>GB</div>
				</div>
				<div id="placeholder" style="width:100%;height:300px;"></div>
			</div>

			
			<h4 class="ui top attached block header">MRI Studies by Day by Site</h4>
			<div class="ui bottom attached segment">
				<script>
					$(function() {
							var data4 = [
					<?
						foreach ($sites as $site) {
							$jsonstrings = array();
							?>
								{
								label: <?=json_encode($site)?>,
								data: [<?
							foreach ($sitedays[$site] as $day)
								$jsonstrings[] = "['$day[0]', " . $day[2] . "]";
							?><?=implode2(',',$jsonstrings)?>]
								},
							<?
							
						}
						//PrintVariable($studysites,'StudySites');
					?>
						];
					
						var options4 = {
							series: {
								lines: {
									show: true,
									fill: true
								},
								points: {
									show: false
								}
							},
							legend: {
								noColumns: 6
							},
							xaxis: {
								mode: "time",
								timeformat: "%Y-%m-%d"
							},
							yaxis: {
								min: 0,
								tickDecimals: 1
							},
							selection: {
								mode: "x"
							}
						};

						var placeholder4 = $("#placeholder4");

						var plot4 = $.plot(placeholder4, data4, options4);
					});
				</script>
				
				<div class='flot-y-axis'>
					<div class='flot-tick-label'>Studies</div>
				</div>
				<div id="placeholder4" style="width:100%;height:300px;"></div>
			</div>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- StatsLinearSlope ------------------- */
	/* -------------------------------------------- */
	/* least-squares slope of [ms timestamp, value] points, in value per day. null if it can't be fit */
	function StatsLinearSlope($pts) {
		$n = count($pts);
		if ($n < 2)
			return null;

		$mx = 0; $my = 0;
		foreach ($pts as $p) {
			$mx += $p[0]/86400000;
			$my += $p[1];
		}
		$mx /= $n;
		$my /= $n;

		$sxx = 0; $sxy = 0;
		foreach ($pts as $p) {
			$dx = $p[0]/86400000 - $mx;
			$sxx += $dx*$dx;
			$sxy += $dx*($p[1] - $my);
		}
		if ($sxx == 0)
			return null;

		return $sxy/$sxx;
	}


	/* -------------------------------------------- */
	/* ------- StatsProjectionSeries -------------- */
	/* -------------------------------------------- */
	/* flot series [latest, +1yr, +3yr] (JSON) for one of the projection's rates ('rate', 'low', 'high') */
	function StatsProjectionSeries($proj, $k) {
		$t1 = intval($proj['t0'] + 365.25*86400*1000);
		$t3 = intval($proj['t0'] + 3*365.25*86400*1000);
		return json_encode(array(array($proj['t0'], round($proj['v0'],1)), array($t1, round($proj[$k.'1'],1)), array($t3, round($proj[$k.'3'],1))));
	}


	/* -------------------------------------------- */
	/* ------- StatsFormatGB ---------------------- */
	/* -------------------------------------------- */
	/* format GB (1000^3 bytes, the unit of the storage charts) as GB or TB */
	function StatsFormatGB($gb) {
		if ($gb >= 1000)
			return number_format($gb/1000, 1) . "&nbsp;TB";
		return number_format($gb, 1) . "&nbsp;GB";
	}


	/* -------------------------------------------- */
	/* ------- DrawScatterPlot -------------------- */
	/* -------------------------------------------- */
	function DrawScatterPlot($w,$h,$x,$y,$c) {

		$axisindent = 40;
		$numticks = 8;
		$ticklength = 8;
		
		/* create the canvas */
		$im = imagecreatetruecolor($w,$h);
		imageantialias($im, true);
		
		/* set background to white */
		$bg = imagecolorallocate($im, 255, 255, 255);
		imagefilledrectangle($im, 0,0,$w,$h,$bg);

		
		/* determine x and y scales based on data */
		$x = explode(",", $x);
		$y = explode(",", $y);
		$c = explode(",", $c);
		$xrange = max($x);
		$yrange = max($y);
		$draww = $w - $axisindent;
		$drawh = $h - $axisindent;
		$xscale = $draww/$xrange;
		$yscale = $drawh/$yrange;
		
		//echo "Scales: $xscale, $yscale";
		/* draw the dots */
		for ($i=0; $i<count($x); $i++) {
			$xp = $x[$i]*$xscale + $axisindent;
			$yp = $h-($y[$i]*$yscale + $axisindent);

			$color = imagecolorallocatealpha($im, hexdec(substr($c[$i],0,2)), hexdec(substr($c[$i],2,2)), hexdec(substr($c[$i],4,2)), 100);
			//echo "Plotting $i: ($xp,$yp) $color<br>\n";
			imagefilledellipse($im,$xp,$yp,6,6,$color);
		}
		
		/* setup text color */
		$fontsize = 2;
		$txtcolor = imagecolorallocate($im,0,0,0);
		$linecolor = imagecolorallocate($im,180,180,180);
		$txtheight = imagefontheight($fontsize);
		$txtwidth = imagefontwidth($fontsize);

		/* draw the axis lines */
		imageline($im,$axisindent,$h-$axisindent,$w,$h-$axisindent,$linecolor); // x
		imageline($im,$axisindent,$h-$axisindent,$axisindent,0,$linecolor); // y
		
		/* draw tick lines */
		$xtickspacing = ($w-$axisindent)/$numticks;
		$ytickspacing = ($h-$axisindent)/$numticks;
		for ($i=0;$i<=$numticks;$i++) {
			/* y ticks */
			$x1 = ($i*$xtickspacing)+$axisindent;
			$x2 = ($i*$xtickspacing)+$axisindent;
			$y1 = $h-$axisindent;
			$y2 = $h-$axisindent+$ticklength;
			imageline($im, $x1, $y1, $x2, $y2,$linecolor);
			
			/* x axis values */
			$str = number_format(($i*$xtickspacing)/$xscale, 1);
			$x1 = $axisindent+($i*$xtickspacing-($txtwidth*strlen($str))/2);
			$y1 = $h-$axisindent+$ticklength+2;
			imagestring($im, $fontsize, $x1, $y1,$str,$txtcolor);

			/* y ticks */
			$y1 = $h-(($i*$ytickspacing)+$axisindent);
			$y2 = $h-(($i*$ytickspacing)+$axisindent);
			$x1 = $axisindent-$ticklength;
			$x2 = $axisindent;
			imageline($im, $x1, $y1, $x2, $y2,$linecolor);
			
			/* y axis values */
			$str = number_format(($i*$ytickspacing)/$yscale, 1);
			$x1 = $axisindent-($txtwidth*strlen($str))-($ticklength+2);
			$y1 = ($numticks-$i)*$ytickspacing-$txtheight/2;
			imagestring($im, $fontsize, $x1, $y1, $str, $txtcolor);
		}

		/* draw axis labels */
		$fontsize = 5;
		$txtcolor = imagecolorallocate($im,0,0,0);
		$txtheight = imagefontheight($fontsize);
		$txtwidth = imagefontwidth($fontsize);
		
		$str = "Years";
		$x1 = $axisindent + ($w-$axisindent)/2 - (($txtwidth*strlen($str))/2);
		$y1 = ($h-$axisindent/2);
		imagestring($im, $fontsize, $x1, $y1, $str, $txtcolor);
		
		$str = "mm";
		$y1 = ($h-$axisindent)/2 - ($txtheight/2);
		$x1 = $axisindent/2 - ($txtwidth*strlen($str)/2);
		imagestring($im, $fontsize, $x1, $y1, $str, $txtcolor);
		
		$filename = "/tmp/" . GenerateRandomString(10) . ".png";
		imagepng($im, $filename);
		chmod($filename, 0777);
		imagedestroy($im);
		
		return $filename;
	}
	
?>


<? include("footer.php") ?>
