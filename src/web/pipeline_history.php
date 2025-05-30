<?
 // ------------------------------------------------------------------------------
 // NiDB pipeline_history.php
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
		<title>NiDB - Pipeline history</title>
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
		case 'viewhistory':
			DisplayPipelineHistory($pipelineid);
			break;
		default:
			DisplayPipelineHistory($pipelineid);
	}
	//PrintVariable($GLOBALS['t']);

	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- DisplayPipelinePerformance --------- */
	/* -------------------------------------------- */
	function DisplayPipelineHistory($pipelineid) {
		?>
		
		<a href="pipelines.php?action=editpipeline&id=<?=$pipelineid?>" class="ui green button">Back</a>
		
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
			$sqlstring = "select distinct(run_num) 'run_num', event_datetime from pipeline_history where pipeline_id = $pipelineid and pipeline_event = 'pipelineStarted' order by event_datetime desc";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$run = $row['run_num'];
				$runDatetime = $row['event_datetime'];
				
				?>
				<div class="ui blue top attached segment">
					<?=$runDatetime?>
				</div>
				<table class="ui table bottom attached table">
					<thead>
						<th>Pipeline version</th>
						<th>analysis ID</th>
						<th>Event</th>
						<th>Datetime</th>
						<th>Message</th>
						<!--<th>Run number</th>-->
					</thead>
				<?

				/* get list of previous runs */
				$sqlstringA = "select * from pipeline_history where pipeline_id = $pipelineid and run_num = $run order by run_num desc, event_datetime asc";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$pipelineVersion = $rowA['pipeline_version'];
					$analysisRowID = $rowA['analysis_id'];
					$event = $rowA['pipeline_event'];
					$eventDatetime = $rowA['event_datetime'];
					$eventMessage = $rowA['event_message'];
					//$runNumber = $row['run_num'];
					?>
					<tr>
						<td><?=$pipelineVersion?></td>
						<td><?=$analysisRowID?></td>
						<td><?=$event?></td>
						<td><?=$eventDatetime?></td>
						<td><?=$eventMessage?></td>
						<!--<td><?=$runNumber?></td>-->
					</tr>
					<?
				}
				
				?>
				</table>
				<br><Br>
				<?
				
			}
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
