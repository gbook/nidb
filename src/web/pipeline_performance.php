<?
 // ------------------------------------------------------------------------------
 // NiDB pipelines.php
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
		<title>NiDB - Pipeline performance</title>
	</head>

<body>
	<div id="wrapper">
<?
	$timestart = microtime(true);

	require "functions.php";
	require "pipeline_functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST, "POST");
	//PrintVariable($_GET, "GET");
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$pipelineid = GetVariable("pipelineid");
	$id = GetVariable("id");
	if ($pipelineid == "")
		$pipelineid = $id;
		
	/* determine action */
	switch ($action) {
		case 'editpipeline':
			DisplayPipelineForm("edit", $id, $returntab);
			break;
		case 'viewusage':
			DisplayPipelineUsage();
			break;
		default:
			DisplayPipelinePerformance($pipelineid);
	}
	//PrintVariable($GLOBALS['t']);

	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayPipelinePerformance --------- */
	/* -------------------------------------------- */
	function DisplayPipelinePerformance($pipelineid) {
		?>
		
		<h1 class="ui header">Pipeline performance</h1>
		<br><br>
		
		<div class="ui text container pageloading">
			<div class="ui active inverted dimmer">
				<div class="ui text loader">Loading</div>
			</div>
		</div>
		
		<script type="text/javascript">
			$(document).ready(function() {
				$('.pageloading').hide();
			});
		</script>
		
		<?
			MarkTime("Info Tab - A");
			
			/* gather statistics about the analyses */
			$sqlstring = "select sum(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status = 'complete'";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$totaltime = $row['cluster_time'];
			$totaltime = number_format(($totaltime/60/60),2);

			MarkTime("Info Tab - B");
			
			$sqlstring = "select sum(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'cluster_timesuccess' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status = 'complete' and analysis_iscomplete = 1";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$totaltimesuccess = $row['cluster_timesuccess'];
			$totaltimesuccess = number_format(($totaltimesuccess/60/60),2);
			
			MarkTime("Info Tab - C");

			$sqlstring = "select count(*) 'numcomplete' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status = 'complete'";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$numcomplete = $row['numcomplete'];

			MarkTime("Info Tab - D");

			$sqlstring = "select count(*) 'numcompletesuccess' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status = 'complete' and analysis_iscomplete = 1";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$numcompletesuccess = $row['numcompletesuccess'];

			MarkTime("Info Tab - E");
			
			$sqlstring = "select count(*) 'numprocessing' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status = 'processing'";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$numprocessing = $row['numprocessing'];
			
			MarkTime("Info Tab - F");
			
			$sqlstring = "select count(*) 'numpending' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status = 'pending'";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$numpending = $row['numpending'];
			
			/* group by and count() */
			$sqlstring = "select analysis_status, count(*) 'count' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid group by analysis_status";
			$numTotal = 0;
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$status = $row['analysis_status'];
				$count = $row['count'];
				$numTotal += $count;
				if ($status == "complete") { $numComplete = $count; }
				if ($status == "pending") { $numPending = $count; }
				if ($status == "processing") { $numProcessing = $count; }
				if ($status == "error") { $numError = $count; }
				if ($status == "submitted") { $numSubmitted = $count; }
				if ($status == "notcompleted") { $numNotCompleted = $count; }
				if ($status == "NoMatchingStudies") { $numNoMatchingStudies = $count; }
				if ($status == "rerunresults") { $numRerunResults = $count; }
				if ($status == "NoMatchingStudyDependency") { $numNoMatchingStudyDependency = $count; }
				if ($status == "IncompleteDependency") { $numIncompleteDependency = $count; }
				if ($status == "BadDependency") { $numBadDependency = $count; }
				if ($status == "NoMatchingSeries") { $numNoMatchingSeries = $count; }
				if ($status == "OddDependencyStatus") { $numOddDependencyStatus = $count; }
				if ($status == "started") { $numStarted = $count; }
			}
			
			MarkTime("Info Tab - G");
			
			/* get mean processing times */
			$sqlstring = "select analysis_id, timestampdiff(second, analysis_startdate, analysis_enddate) 'analysis_time', timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate) 'cluster_time' from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.pipeline_id = $pipelineid and analysis_status <> ''";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				//$analysis_id = $row['analysis_id'];
				$analysistimes[] = $row['analysis_time'];
				$clustertimes[] = $row['cluster_time'];
			}
			if (count($clustertimes) == 0) {
				$clustertimes[] = 0;
			}
			if (count($analysistimes) == 0) {
				$analysistimes[] = 0;
			}

			MarkTime("Info Tab - H");
			
			?>
			<div class="ui cards">
				<div class="ui card">
					<div class="content">
						<div class="header">Analysis Statistics</div>
						<div class="meta">Total numbers of analyses for each status</div>
						<div class="description">
							<table class="ui table">
								<thead>
									<th>Status</th>
									<th>Count</th>
								</thead>
								<tr>
									<td>Completed</td>
									<td><?=$numcomplete?></td>
								</tr>
								<tr>
									<td class="left green marked">Completed successfully</td>
									<td class="right green marked"><?=$numcompletesuccess?></td>
								</tr>
								<tr>
									<td>Processing</td>
									<td><?=$numprocessing?></td>
								</tr>
								<tr>
									<td>Pending</td>
									<td><?=$numpending?></td>
								</tr>
								<tr>
									<td>Error</td>
									<td><?=$numerror?></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				
				<div class="ui card">
					<div class="content">
						<div class="header">Compute performance</div>
						<div class="meta">Analysis compute time per cluster node</div>
						<div class="description">
							<div class="ui big basic label">Total CPU time <?=$totaltime?> hrs</div>
							<br>
							<table class="ui very compact very small collapsing celled table">
								<thead>
									<th colspan="3">Computing performance<br><span class="tiny">Successful analyses only</span></th>
								</thead>
								<tr>
									<td><b>Hostname</b></td>
									<td><b>Avg CPU</b> <span class="tiny">(hours)</span></td>
									<td><b>Count</b></td>
								</tr>
							<?
								$sqlstring = "select avg(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'avgcpu', hostname, count(hostname) 'count' FROM (select analysis_clusterstartdate, analysis_clusterenddate, trim(Replace(Replace(Replace(analysis_hostname,'\t',''),'\n',''),'\r','')) 'hostname' from `analysis` WHERE pipeline_id = $pipelineid and (analysis_iscomplete = 1 or analysis_status = 'complete')) hostnames group by hostname order by hostname";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$cpuhrs = number_format(($row['avgcpu']/60/60),2);
									$count = $row['count'];
									$hostname = $row['hostname'];
									?>
									<tr>
										<td><?=$hostname?></td>
										<td><?=$cpuhrs?></td>
										<td class="right aligned"><?=$count?></td>
									</tr>
									<?
								}
							?>
							</table>
						</div>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Disk usage</div>
						<div class="meta">Total disk usage</div>
						<div class="description">
							<?
								$sqlstring = "select sum(analysis_disksize) 'disksize' from analysis where pipeline_id = $pipelineid";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
								$totaldiskusage = $row['disksize'];
							?>
							<div class="ui big basic label"><?=HumanReadableFilesize($totaldiskusage);?></div>
						</div>
					</div>
				</div>

				<div class="ui card">
					<div class="content">
						<div class="header">Timeline</div>
						<div class="meta">Analysis compute time by date</div>
						<div class="description">
							<table class="ui very compact very small collapsing celled table">
								<tr>
									<td><b>Date</b></td>
									<td><b>Avg CPU</b> <span class="tiny">(hours)</span></td>
									<td><b>Avg setup</b> <span class="tiny">(hours)</span></td>
									<td><b>Count</b></td>
								</tr>
							<?
								$datarows[] = "['Date', 'Setup time', 'Cluster time']";
								$sqlstring = "select avg(timestampdiff(second, analysis_clusterstartdate, analysis_clusterenddate)) 'avgcpu', avg(timestampdiff(second, analysis_startdate, analysis_enddate)) 'avgsetup', date_format(analysis_clusterstartdate, '%Y-%m-%d') 'analysisdate', count(*) 'count' FROM (select analysis_startdate, analysis_enddate, analysis_clusterstartdate, analysis_clusterenddate from `analysis` WHERE pipeline_id = $pipelineid and (analysis_iscomplete = 1 or analysis_status = 'complete')) hostnames group by date(analysis_startdate) order by date(analysis_startdate)";
								$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$cpuhrs = number_format(($row['avgcpu']/60/60),3,'.','');
									$setuphrs = number_format(($row['avgsetup']/60/60),3,'.','');
									#$cpuhrs = $row['avgcpu']/60;
									#$setuphrs = $row['avgsetup']/60;
									$count = $row['count'];
									$startdate = $row['analysisdate'];

									$datarows[] = "['$startdate', $setuphrs, $cpuhrs]";
								}
								
								$dataset = implode(",", $datarows);
							?>
							</table>
						</div>
					</div>
				</div>
				
			</div>

			<br><br>
			<script type="module">
				import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
			</script>
			<?
				$numAll = $numTotal + 0;
				$numCluster = $numError + $numProcessing + $numPending + $numSubmitted + $numStarted + 0;
				$numRunning = $numProcessing + $numPending + $numSubmitted + $numStarted + 0;
				$numComplete = $numcomplete + 0;
				$numSuccess = $numcompletesuccess + 0;
				$numNotSuccess = $numComplete - $numSuccess + 0;
				$numError = $numError + 0;
				$numNoMatch = $numNoMatchingSeries + $numNoMatchingStudies + 0;
				$numDepIssue = $numNoMatchingStudyDependency + $numIncompleteDependency + $numBadDependency + 0;
				
				/*$numComplete
				$numPending
				$numProcessing
				$numError
				$numSubmitted
				$numNotCompleted
				$numNoMatchingStudies
				$numRerunResults
				$numNoMatchingStudyDependency
				$numIncompleteDependency
				$numBadDependency
				$numNoMatchingSeries
				$numOddDependencyStatus
				$numStarted*/
			?>
			<pre class="mermaid" style="width: 100%">
				sankey-beta

				%% source,target,value
				All,Cluster,<?="$numCluster\n"?>
				Cluster,Complete,<?="$numComplete\n"?>
				Complete,Successful,<?="$numSuccess\n"?>
				Complete,Other status,<?="$numNotSuccess\n"?>
				Cluster,Running,<?="$numRunning\n"?>
				Cluster,Error,<?="$numError\n"?>
				All,No matching data found,<?="$numNoMatch\n"?>
				All,Dependency issue,<?="$numDepIssue\n"?>
			</pre>
			
			<br><br>
			<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
			<script type="text/javascript">
				google.charts.load('current', {'packages':['corechart']});
				google.charts.setOnLoadCallback(drawChart);

				function drawChart() {
					var data = google.visualization.arrayToDataTable([
						<?=$dataset?>
					]);

					var options = {
						isStacked: true,
						title: 'Total analysis time',
						hAxis: {title: 'Date',  titleTextStyle: {color: '#333'}},
						vAxis: {title: 'Hours', minValue: 0}
					};

					var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
					chart.draw(data, options);
				}
			</script>

			<div id="chart_div" style="width: 100%; height: 500px;"></div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- MarkTime --------------------------- */
	/* -------------------------------------------- */
	function MarkTime($msg) {
		$time = number_format((microtime(true) - $GLOBALS['timestart']), 3);
		$GLOBALS['t'][][$msg] = $time;
	}
	
?>

<? include("footer.php") ?>
