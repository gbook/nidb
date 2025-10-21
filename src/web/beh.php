<?
 // ------------------------------------------------------------------------------
 // NiDB beh.php
 // Copyright (C) 2004 - 2025
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
		<title>NiDB - Behavioral data</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	/* setup variables */
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
	
		//$urllist['Statistics'] = "stats.php";
		//NavigationBar("Stats", $urllist);

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
		$sqlstring = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '%_series'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			//print_r($row);
			$tablename = $row['Tables_in_' . $GLOBALS['cfg']['mysqldatabase'] . ' (%_series)'];
			//echo $tablename;
			$parts = explode("_", $tablename);
			$modality = $parts[0];
			
			$sqlstring2 = "select count(*) 'count', sum(series_size) 'size' from $modality" . "_series";
			$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
			$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
			$totalseries += $row2['count'];
			$totalsize += $row2['size'];
			$seriescounts[$modality] = number_format($row2['count']);
			$seriessize[$modality] = HumanReadableFilesize($row2['size']);
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
		
		/* total request processing time */
		$sqlstring = "select sum(req_cputime) totalrequestcpu from data_requests";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$diff = $row['totalrequestcpu'];
		$totalrequestcpu = FormatCountdown($diff);
		
		/* mean request time */
		$sqlstring = "SELECT avg(time_to_sec(timediff(req_completedate, req_date))) avgtime FROM `data_requests` where req_completedate > '000-00-00 00:00:00'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$diff = $row['avgtime'];
		$avgrequesttime = FormatCountdown($diff);

		/* median request time */
		$sqlstring = "SELECT * FROM `data_requests` where req_completedate > '0000-00-00 00:00:00'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numrows = mysqli_num_rows($result);
		$med = round($numrows/2);
		
		$sqlstring = "SELECT time_to_sec(timediff(req_completedate, req_date)) avgtime FROM `data_requests` where req_completedate > '0000-00-00 00:00:00' order by avgtime limit $med,1";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$diff = $row['avgtime'];
		$medianrequesttime = FormatCountdown($diff);
		
		$uptime = shell_exec('uptime');
		
		/* subject demographics */
		$sqlstring = "select (select count(*) from subjects where gender = 'F') 'numfemales', (select count(*) from subjects where gender = 'M') 'nummales', (select count(*) from subjects where gender = 'O') 'numother', (select count(*) from subjects where gender = 'U') 'numunknown', (select count(*) from subjects where gender not in ('F','M','O','U')) 'numnotspec'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$numfemales = $row['numfemales'];
		$nummales = $row['nummales'];
		$numother = $row['numother'];
		$numunknown = $row['numunknown'];
		$numnotspec = $row['numnotspec'];
		
		?>
		
		<table width="100%">
			<tr>
				<td valign="top" width="50%">
					<table class="ui table">
						<tr>
							<td class="title">Series Info</td>
						</tr>
						<tr>
							<td class="body">
								<span style="font-size:10pt;">
								<b>Available Data:</b><br>
								<?=$numsubjects;?> subjects<br>
								<?=number_format($numstudies);?> studies<br>
								<br>
								<table class="smalldisplaytable">
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
							</td>
						</tr>
					</table>
					
					<br>
					
					<table class="ui table">
						<tr>
							<td class="title">System Info</td>
						</tr>
						<tr>
							<td class="body">
								<span class="header">Uptime</span><br>
								<?=$uptime;?>
								<br><bR>
								<span class="header">Data Requests</span><br>
								<b>CPU time:</b> <?=$totalrequestcpu?><br>
								<b>Mean request time:</b> <?=$avgrequesttime?><br>
								<b>Median request time:</b> <?=$medianrequesttime?><br>
							</td>
						</tr>
					</table>
					
					<br>
					
					<table class="ui table">
						<tr>
							<td class="title">Subject Demographics</td>
						</tr>
						<tr>
							<td class="body">
								<table class="reviewtable">
									<tr>
										<td class="label"># females</td>
										<td class="value"><?=$numfemales?> (<?=number_format(($numfemales/$numtotalsubjects)*100,1)?>%)</td>
									</tr>
									<tr>
										<td class="label"># males</td>
										<td class="value"><?=$nummales?> (<?=number_format(($nummales/$numtotalsubjects)*100,1)?>%)</td>
									</tr>
									<tr>
										<td class="label"># other</td>
										<td class="value"><?=$numother?> (<?=number_format(($numother/$numtotalsubjects)*100,1)?>%)</td>
									</tr>
									<tr>
										<td class="label"># unknown</td>
										<td class="value"><?=$numunknown?> (<?=number_format(($numunknown/$numtotalsubjects)*100,1)?>%)</td>
									</tr>
									<tr>
										<td class="label"># not specified</td>
										<td class="value"><?=$numnotspec?> (<?=number_format(($numnotspec/$numtotalsubjects)*100,1)?>%)</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>					
				</td>
				<td valign="top" width="50%">
					<table class="ui table">
						<tr>
							<td class="title">MR</td>
						</tr>
						<tr>
							<td class="body">
								<span class="header">CPU time</span><br>
								<b>Total series QA CPU time:</b> <?=$totalseriesqacpu?><br>
								<b>Total study QA CPU time:</b> <?=$totalstudyqacpu?><br>
							</td>
						</tr>
						<tr>
							<td class="body">
								<table cellspacing="0" cellpadding="2" style="font-size:10pt">
									<tr>
										<td colspan="24" align="center"><span class="header">Total # MR studies starting at time...</span></td>
									</tr>
									<tr>
										<td colspan="12" align="center" style="border-bottom: 1px solid gray; border-right: 1pt solid gray">AM</td>
										<td colspan="12" align="center" style="border-bottom: 1px solid gray">PM</td>
									</tr>
									<tr style="font-size:10pt">
										<?
											for ($hour=0;$hour<24;$hour++) {
												if ($hour == 0){
													?><td align="center" style="border-left: solid 1px #CCCCCC; border-right: solid 1px #CCCCCC;">12</td><?
												}
												else {
													if ($hour < 12) {
														?><td align="center" style="border-right: solid 1px #CCCCCC;"><?=$hour?></td><?
													}
													else {
														$hr = $hour;
														if ($hour != 12) {
															$hr = $hr - 12;
														}
														?><td align="center" style="border-right: solid 1px #CCCCCC;"><?=$hr?></td><?
													}
												}
											}
										?>
									</tr>
									<tr>
								<?
									for ($hour=0;$hour<24;$hour++) {
										$sqlstring = "select count(*) count from studies where hour(study_datetime) = $hour and study_modality = 'MR'";
										#echo "$sqlstring<br>";
										$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
										$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
										$count = $row['count'];
										$percent = round(($count/$numstudies)*100);
										$counts[$hour] = $count;
										//echo "[$count] [$numstudies] [$percent]";
										#echo "<td align=right>$percent%&nbsp;</td>";
										?>
										<td valign="bottom" align="center" style="border-right: solid 1px #CCCCCC;">
											<table height="<? echo $percent*2; ?>px" cellpadding=0 cellspacing=0 width="15px">
												<tr>
													<td bgcolor="red" style="font-size: 0px">&nbsp;</td>
												</tr>
											</table>
										</td>
										<?
									}
									?> </tr><tr style="font-size:8pt"> <?
									for ($hour=0;$hour<24;$hour++) {
										echo "<td align=center>$counts[$hour]</td>";
									}
								?>
									</tr>
								</table>
								<br><br>
								<table cellspacing="0" cellpadding="2" style="font-size:10pt">
									<tr>
										<td colspan="13" align="center" style="border-bottom: 1pt solid darkgray"><span class="header">Scan History (# sessions per month)</span></td>
									</tr>
									<tr>
										<td>Year</td>
										<td>Jan</td>
										<td>Feb</td>
										<td>Mar</td>
										<td>Apr</td>
										<td>May</td>
										<td>Jun</td>
										<td>Jul</td>
										<td>Aug</td>
										<td>Sep</td>
										<td>Oct</td>
										<td>Nov</td>
										<td>Dec</td>
									</tr>
								<?
									$sqlstring = "select year(min(study_datetime)) firstyear from studies where study_datetime > '0000-00-00 00:00:01' and study_modality = 'MR'";
									$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
									$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
									$firstyear = $row['firstyear'];
									
									$numyears = $currentyear - $firstyear;
									
									for ($year=$firstyear;$year<=$currentyear;$year++) {
										?>
										<tr>
											<td>
												<a href="reports.php?action=yearstudy&year=<?=$year?>&modality=MR"><b><?=$year?></b></a>
											</td>
										<?
										for ($month=1;$month<=12;$month++) {
											$sqlstring = "select count(*) count from studies where year(study_datetime) = $year and month(study_datetime) = $month and study_modality = 'MR'";
											#echo "$sqlstring<br>";
											$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
											$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
											$count = $row['count'];
											echo "<td align=right>$count &nbsp;</td>";
										}
										?>
										</tr>
										<?
									}
								?>
								</table>
								
							</td>
						</tr>
						<tr>
							<td class="body">
							<?
								$sqlstring = "SELECT (move_maxx-move_minx + move_maxy-move_miny + move_maxz-move_minz) 'totalmovement', datediff(c.study_datetime, e.birthdate) 'ageatscan', e.gender FROM mr_qa a left join mr_series b on a.mrseries_id = b.mrseries_id left join studies c on b.study_id = c.study_id left join enrollment d on c.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id";
								$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
								$i=0;
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									if (($row['totalmovement'] > 0) && ($row['totalmovement'] < 50)) {
										//print_r($row);
										$y[] = number_format($row['totalmovement'],2);
										$x[] = number_format($row['ageatscan']/365.25,2);
										if ($row['gender'] == 'F')
											$c[] = 'FF0000'; //$c[] = 'FFC0CB';
										elseif ($row['gender'] == 'M')
											$c[] = '0000FF'; //$c[] = '4169E1';
										else
											$c[] = '888888';
										$i++;
									}
									//if ($i>100) break;
								}
								//print_r($x);
								$chd = implode(",",$x) . "|" . implode(",",$y);
								$chco = implode("|",$c);
								
								$x = implode(",",$x);
								$y = implode(",",$y);
								$c = implode(",",$c);
								
								$chartfilename = DrawScatterPlot(600,400,$x,$y,$c);
							?>
							<b>Age vs movement</b><br>
							<img src="data:image/png;base64,<?=base64_encode(file_get_contents($chartfilename))?>">
							<?
								/* image data should've been sent to the browser, so delete the temp image */
								unlink($chartfilename);
							?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DrawScatterPlot -------------------- */
	/* -------------------------------------------- */
	function DrawScatterPlot($w,$h,$x,$y,$c) {

		$axisindent = 40;
		$numticks = 5;
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
