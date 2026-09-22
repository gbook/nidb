<?
 // ------------------------------------------------------------------------------
 // NiDB pipeline_history.php
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
	$pipelineid = (int)GetVariable("pipelineid");
	$id = (int)GetVariable("id");
	if ($pipelineid == 0)
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
	/* ------- DisplayPipelineHistory ------------- */
	/* -------------------------------------------- */
	/* show the pipeline's events from the last few hours in an ag-grid. Events are grouped by run, and each
	   run starts with a full-width separator row */
	function DisplayPipelineHistory($pipelineid) {
		if (!ValidID($pipelineid,'Pipeline ID')) { return; }
		$pipelineid = (int)$pipelineid;
		$hours = 12;

		$sqlstring = "select pipeline_name from pipelines where pipeline_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $pipelineid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$pipelineid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("Pipeline [$pipelineid] not found");
			return;
		}
		$pipelinename = $row['pipeline_name'];

		/* all events in the window, grouped into runs (newest run first, events in order within a run) */
		$sqlstring = "select run_num, pipeline_version, analysis_id, pipeline_event, event_datetime, event_message from pipeline_history where pipeline_id = ? and event_datetime >= date_sub(now(), interval ? hour) order by run_num desc, event_datetime asc, pipelinehistory_id asc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $pipelineid, $hours);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$pipelineid, $hours]);
		$runs = array();
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$runs[(int)$row['run_num']][] = $row;
		}
		mysqli_stmt_close($stmt);

		/* flatten into grid rows: a separator row for each run, followed by its events */
		$rows = array();
		$numevents = 0;
		foreach ($runs as $runnum => $events) {
			$first = $events[0];
			$last = $events[count($events)-1];
			$numerrors = 0;
			foreach ($events as $e) {
				if (substr($e['pipeline_event'], 0, 5) == "error") { $numerrors++; }
			}
			$start = substr($first['event_datetime'], 0, 19);
			$end = substr($last['event_datetime'], 0, 19);
			$rows[] = array(
				'separator'   => true,
				'run'         => $runnum,
				'start'       => $start,
				'end'         => $end,
				'duration'    => max(0, strtotime($end) - strtotime($start)),
				'startedearlier' => ($first['pipeline_event'] != 'pipelineStarted'),
				'finished'    => ($last['pipeline_event'] == 'pipelineFinished'),
				'numevents'   => count($events),
				'numerrors'   => $numerrors,
			);
			foreach ($events as $e) {
				$rows[] = array(
					'separator' => false,
					'run'       => $runnum,
					'version'   => (int)$e['pipeline_version'],
					'analysisid'=> (int)$e['analysis_id'],
					'event'     => (string)$e['pipeline_event'],
					'datetime'  => substr($e['event_datetime'], 0, 19),
					'message'   => (string)($e['event_message'] ?? ''),
				);
				$numevents++;
			}
		}
		$rowsJson = json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE);
		?>
		<div class="ui container">
			<div class="ui two column middle aligned grid">
				<div class="column">
					<h1 class="ui header">
						Pipeline history
						<div class="sub header"><a href="pipelines.php?action=editpipeline&id=<?=$pipelineid?>"><?=htmlspecialchars($pipelinename)?></a></div>
					</h1>
				</div>
				<div class="right aligned column">
					<a href="pipelines.php?action=editpipeline&id=<?=$pipelineid?>" class="ui basic button"><i class="arrow left icon"></i> Back to pipeline</a>
				</div>
			</div>

			<div style="display:flex; align-items:center; gap:10px; margin: 15px 0 8px 0">
				<div class="ui small icon input" style="width:300px">
					<input type="text" id="historyFilter" placeholder="Search events and messages..." oninput="historySearch(this.value)">
					<i class="search icon"></i>
				</div>
				<span style="color:#888">Showing <b><?=$numevents?></b> events in <b><?=count($runs)?></b> runs from the last <?=$hours?> hours</span>
			</div>

			<? if (count($rows) == 0) { ?>
			<div class="ui message">No pipeline events in the last <?=$hours?> hours.</div>
			<? } else { ?>
			<div id="historyGrid" style="height:70vh; width:100%"></div>
			<? } ?>
		</div>

		<? if (count($rows) > 0) { ?>
		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@36/dist/ag-grid-community.min.js"></script>
		<script>
		let historyGridApi;
		let historyMatchingRuns = null;
		let historyTerm = '';
		(function() {
			const rowData = <?=$rowsJson?>;

			function formatDuration(s) {
				const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
				return (h > 0 ? h + 'h ' : '') + (h > 0 || m > 0 ? m + 'm ' : '') + sec + 's';
			}

			/* the full-width row that separates runs */
			function separatorRenderer(params) {
				const d = params.data;
				const div = document.createElement('div');
				div.style.cssText = 'height:100%; display:flex; align-items:center; gap:14px; padding:0 12px; background:#e8f0fb; border-top:2px solid #2185d0; font-weight:bold';
				let html = '<span><i class="blue sync alternate icon"></i>Run ' + d.run + '</span>';
				html += '<span style="font-weight:normal">' + (d.startedearlier ? 'Started before ' : 'Started ') + d.start + '</span>';
				html += '<span style="font-weight:normal">' + (d.finished ? 'Finished ' + d.end + ' (' + formatDuration(d.duration) + ')' : 'Last event ' + d.end) + '</span>';
				html += '<span class="ui tiny basic label">' + d.numevents + ' events</span>';
				if (d.numerrors > 0) html += '<span class="ui tiny red label">' + d.numerrors + ' error' + (d.numerrors == 1 ? '' : 's') + '</span>';
				div.innerHTML = html;
				return div;
			}

			const columnDefs = [
				{ field: 'datetime',   headerName: 'Datetime',   width: 170 },
				{ field: 'event',      headerName: 'Event',      width: 200, cellStyle: function(params) {
						return (params.value || '').indexOf('error') === 0 ? { color: '#db2828', fontWeight: 'bold' } : null;
					}
				},
				{ field: 'analysisid', headerName: 'Analysis ID', width: 120, valueFormatter: function(params) { return params.value > 0 ? params.value : ''; } },
				{ field: 'version',    headerName: 'Version',    width: 95 },
				{ field: 'message',    headerName: 'Message',    flex: 1, tooltipField: 'message' },
			];

			const gridOptions = {
				columnDefs: columnDefs,
				rowData: rowData,
				/* rows are already in run/time order, and the separators only make sense in that order, so no sorting */
				defaultColDef: { sortable: false, resizable: true },
				isFullWidthRow: function(params) { return params.rowNode.data && params.rowNode.data.separator; },
				fullWidthCellRenderer: separatorRenderer,
				getRowHeight: function(params) { return (params.data && params.data.separator) ? 36 : undefined; },
				getRowStyle: function(params) {
					return (params.data && !params.data.separator && params.data.event.indexOf('error') === 0) ? { background: '#fff6f6' } : null;
				},
				/* search: show matching events, and the separator of any run with a match */
				isExternalFilterPresent: function() { return historyTerm !== ''; },
				doesExternalFilterPass: function(node) {
					if (node.data.separator) return historyMatchingRuns.has(node.data.run);
					return historyRowMatches(node.data);
				},
				tooltipShowDelay: 500,
			};

			historyGridApi = agGrid.createGrid(document.getElementById('historyGrid'), gridOptions);

			window.historyRowMatches = function(d) {
				return (d.event + ' ' + d.message + ' ' + d.datetime + ' ' + (d.analysisid || '')).toLowerCase().indexOf(historyTerm) !== -1;
			};
			window.historySearch = function(value) {
				historyTerm = value.toLowerCase().trim();
				historyMatchingRuns = new Set();
				rowData.forEach(function(d) {
					if (!d.separator && historyTerm !== '' && historyRowMatches(d)) historyMatchingRuns.add(d.run);
				});
				historyGridApi.onFilterChanged();
			};
		})();
		</script>
		<? } ?>
		<br><br>
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
