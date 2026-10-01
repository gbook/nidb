<?
 // ------------------------------------------------------------------------------
 // NiDB projectusage.php
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
	require "functions.php";
	require "includes_php.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = (int)GetVariable("projectid");

	/* the timeseries counts are only loaded when the user asks for them (by AJAX), because
	   counting a project's timepoints in a large timeseries table can take minutes. This returns
	   JSON, so it runs before any HTML is output */
	if ($action == "gettimeseriesusage") {
		GetTimeseriesUsageJSON($projectid);
		exit(0);
	}
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Project usage</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "includes_html.php";
	require "menu.php";

	/* this page is read-only, so there are no mutating actions */
	DisplayProjectUsage($projectid);


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayProjectUsage ---------------- */
	/* -------------------------------------------- */
	/* Summary of the resources used by a project: disk size of the imaging data (all *_series
	   tables) and the number of non-imaging entries (observations, interventions, timeseries
	   timepoints). Requires View Data on the project */
	function DisplayProjectUsage($projectid) {
		$projectid = (int)$projectid;

		/* check the project exists */
		$sqlstring = "select project_name, project_costcenter from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("Invalid project ID");
			return;
		}
		$projectname = $row['project_name'];
		$costcenter = $row['project_costcenter'];

		/* usage is a summary of project data, so it requires View Data on the project */
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'viewdata', $projectid)) {
			Error("You do not have permissions to view data in this project");
			return;
		}

		list($imaging, $imagingtotals) = GetImagingUsage($projectid);
		$nonimaging = GetNonImagingUsage($projectid);
		$tablestats = GetTableStats();
		$timeseriesrows = FormatRowCount($tablestats['timeseries']['rows']);

		/* database usage. observations, interventions, and timeseries are the project's share of
		   each table's size. files is the actual size of the project's stored files. The timeseries
		   size is filled in when the user loads the timeseries counts */
		$dbusage = array(
			'observations' => array('label' => 'Observations', 'icon' => 'clipboard list', 'rows' => $nonimaging['observations'], 'bytes' => EstimateTableShare($nonimaging['observations'], $tablestats['observations'])),
			'interventions' => array('label' => 'Interventions', 'icon' => 'pills', 'rows' => $nonimaging['interventions'], 'bytes' => EstimateTableShare($nonimaging['interventions'], $tablestats['interventions'])),
			'files' => array('label' => 'Observation files', 'icon' => 'file', 'rows' => $nonimaging['files'], 'bytes' => $nonimaging['filebytes'])
		);
		$dbtotalbytes = 0;
		foreach ($dbusage as $u)
			$dbtotalbytes += $u['bytes'];

		?>
		<div class="ui container">
			<h1 class="ui header">
				<i class="chart pie icon"></i>
				<div class="content">
					Resource usage
					<div class="sub header"><a href="projects.php?id=<?=$projectid?>"><?=htmlspecialchars($projectname ?? '')?></a> (<?=htmlspecialchars($costcenter ?? '')?>)</div>
				</div>
			</h1>

			<!-- summary of the project's disk and database usage -->
			<br>
			<table class="ui large collapsing celled table" style="margin-left: auto; margin-right: auto; font-size: 1.3em">
				<thead>
					<tr>
						<th>Data type</th>
						<th class="right aligned">Size</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><i class="images icon"></i> Imaging data</td>
						<td class="right aligned"><?=HumanReadableFilesize($imagingtotals['size'])?></td>
					</tr>
					<tr>
						<td><i class="table icon"></i> Non-imaging data <span class="statnote" style="color: gray">(excluding timeseries)</span></td>
						<td class="right aligned" id="statnonimaging">~<?=HumanReadableFilesize($dbtotalbytes)?></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th style="background-color: #d4f7d4"><b>Total usage</b> <span class="statnote" style="color: gray; font-weight: normal">(excluding timeseries)</span></th>
						<th class="right aligned" style="background-color: #d4f7d4"><b id="stattotal" data-bytes="<?=number_format($imagingtotals['size'] + $dbtotalbytes, 0, '.', '')?>">~<?=HumanReadableFilesize($imagingtotals['size'] + $dbtotalbytes)?></b></th>
					</tr>
				</tfoot>
			</table>
			<br>

			<h3 class="ui dividing header">Imaging data</h3>
			<? if (count($imaging) == 0) { ?>
				<div class="ui message">This project contains no imaging data</div>
			<? } else { ?>
			<table class="ui very compact celled selectable table">
				<thead>
					<tr>
						<th>Modality</th>
						<th class="right aligned">Studies</th>
						<th class="right aligned">Series</th>
						<th class="right aligned">Files</th>
						<th class="right aligned">Size</th>
						<th class="right aligned">Bytes</th>
						<th>% of imaging</th>
					</tr>
				</thead>
				<tbody>
					<?
					foreach ($imaging as $m) {
						$pct = ($imagingtotals['size'] > 0) ? ($m['size'] / $imagingtotals['size']) * 100.0 : 0;
						?>
						<tr>
							<td><b><?=htmlspecialchars(strtoupper($m['modality']))?></b> <span class="tt" style="color: gray"><?=htmlspecialchars($m['desc'])?></span></td>
							<td class="right aligned"><?=number_format($m['studies'])?></td>
							<td class="right aligned"><?=number_format($m['series'])?></td>
							<td class="right aligned"><?=number_format($m['files'])?></td>
							<td class="right aligned"><?=HumanReadableFilesize($m['size'])?></td>
							<td class="right aligned tt" style="color: gray"><?=number_format($m['size'])?></td>
							<td>
								<div class="ui tiny blue progress" style="margin: 0" data-percent="<?=(int)round($pct)?>" title="<?=number_format($pct, 1)?>%">
									<div class="bar" style="width: <?=number_format($pct, 1, '.', '')?>%; min-width: 0"></div>
								</div>
							</td>
						</tr>
						<?
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th><b>Total</b></th>
						<th class="right aligned"><b><?=number_format($imagingtotals['studies'])?></b></th>
						<th class="right aligned"><b><?=number_format($imagingtotals['series'])?></b></th>
						<th class="right aligned"><b><?=number_format($imagingtotals['files'])?></b></th>
						<th class="right aligned"><b><?=HumanReadableFilesize($imagingtotals['size'])?></b></th>
						<th class="right aligned tt"><?=number_format($imagingtotals['size'])?></th>
						<th></th>
					</tr>
				</tfoot>
			</table>
			<div style="color: gray; font-size: smaller">Sizes are the archived series sizes recorded in the database. Studies are counted once per modality; a study with multiple modalities is counted once in the total.</div>
			<? } ?>

			<h3 class="ui dividing header">Non-imaging data</h3>
			<!-- object counts and database usage, side by side (they wrap on narrow screens) -->
			<div style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 2em">
				<div>
					<h4 class="ui header">Object counts</h4>
					<table class="ui very compact celled collapsing table">
						<thead>
							<tr>
								<th>Type</th>
								<th class="right aligned">Entries</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><i class="clipboard list icon"></i> Observations</td>
								<td class="right aligned"><?=number_format($nonimaging['observations'])?></td>
							</tr>
							<tr>
								<td><i class="pills icon"></i> Interventions</td>
								<td class="right aligned"><?=number_format($nonimaging['interventions'])?></td>
							</tr>
							<tr>
								<td><i class="chart area icon"></i> Timeseries timepoints <span style="color: gray; display: none" id="tsobservationsnote">(from <span id="tsobservations"></span> observations)</span></td>
								<td class="right aligned" id="tstimepoints"><button class="ui mini primary button" id="loadtimeseries" title="Timeseries counts are not loaded automatically. The timeseries table has approximately <?=$timeseriesrows?> rows, so counting this project's timepoints may take a while">Load counts</button></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div>
					<h4 class="ui header" style="display: flex; justify-content: space-between; align-items: center">Database usage <span title="Observation, intervention, and timeseries sizes are estimates: the project's share of the table's data and index size, by number of rows. The observation files size is the actual size of the project's files stored in the database." style="cursor: help"><i class="grey info circle icon" style="margin: 0"></i></span></h4>
					<table class="ui very compact celled collapsing table">
						<thead>
							<tr>
								<th>Table</th>
								<th class="right aligned">Project rows</th>
								<th class="right aligned">Table rows</th>
								<th class="right aligned">Size</th>
								<th class="right aligned">Bytes</th>
							</tr>
						</thead>
						<tbody>
							<? foreach (array('observations', 'interventions') as $table) { ?>
							<tr>
								<td><i class="<?=$dbusage[$table]['icon']?> icon"></i> <?=$dbusage[$table]['label']?> <span class="tt" style="color: gray"><?=$table?></span></td>
								<td class="right aligned"><?=number_format($dbusage[$table]['rows'])?></td>
								<td class="right aligned"><?=number_format($tablestats[$table]['rows'])?></td>
								<td class="right aligned" title="Estimated">~<?=HumanReadableFilesize($dbusage[$table]['bytes'])?></td>
								<td class="right aligned tt" style="color: gray"><?=number_format($dbusage[$table]['bytes'])?></td>
							</tr>
							<? } ?>
							<tr>
								<td><i class="chart line icon"></i> Timeseries <span class="tt" style="color: gray">timeseries</span></td>
								<td class="right aligned" id="tsdbrows"><span style="color: #bbb">&ndash;</span></td>
								<td class="right aligned"><?=number_format($tablestats['timeseries']['rows'])?></td>
								<td class="right aligned" id="tsdbsize" title="Estimated"><span style="color: #bbb">&ndash;</span></td>
								<td class="right aligned tt" style="color: gray" id="tsdbbytes"></td>
							</tr>
							<tr>
								<td><i class="<?=$dbusage['files']['icon']?> icon"></i> <?=$dbusage['files']['label']?> <span class="tt" style="color: gray">files</span></td>
								<td class="right aligned"><?=number_format($dbusage['files']['rows'])?></td>
								<td class="right aligned"><?=number_format($tablestats['files']['rows'])?></td>
								<td class="right aligned"><?=HumanReadableFilesize($dbusage['files']['bytes'])?></td>
								<td class="right aligned tt" style="color: gray"><?=number_format($dbusage['files']['bytes'])?></td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th><b>Total</b> <span style="color: gray; font-weight: normal" id="dbtotalnote">(excluding timeseries)</span></th>
								<th></th>
								<th></th>
								<th class="right aligned"><b id="dbtotalsize" data-bytes="<?=number_format($dbtotalbytes, 0, '.', '')?>">~<?=HumanReadableFilesize($dbtotalbytes)?></b></th>
								<th class="right aligned tt" id="dbtotalbytes"><?=number_format($dbtotalbytes)?></th>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
			<div class="ui negative message" id="tserror" style="display: none"></div>
		</div>

		<script type="text/javascript">
			/* same output as HumanReadableFilesize() in functions.php */
			function HumanReadableFilesize(size) {
				var units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
				var i = 0;
				for (i = 0; size > 1024; i++)
					size /= 1024;
				return size.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '\u00a0' + units[i];
			}

			$('#loadtimeseries').on('click', function() {
				var btn = $(this);
				btn.addClass('loading disabled');
				$('#tserror').hide();
				$.getJSON('projectusage.php', { action: 'gettimeseriesusage', projectid: <?=$projectid?> })
					.done(function(data) {
						if (data.error) {
							btn.removeClass('loading disabled');
							$('#tserror').text(data.error).show();
							return;
						}
						$('#tstimepoints').text(data.timepoints);
						$('#tsobservations').text(data.observations);
						$('#tsobservationsnote').show();
						$('#tsdbrows').text(data.timepoints);
						$('#tsdbsize').text('~' + HumanReadableFilesize(data.bytes));
						$('#tsdbbytes').text(data.bytes.toLocaleString('en-US'));
						var total = parseFloat($('#dbtotalsize').data('bytes')) + data.bytes;
						$('#dbtotalsize').text('~' + HumanReadableFilesize(total));
						$('#dbtotalbytes').text(total.toLocaleString('en-US'));
						$('#dbtotalnote').hide();
						$('#statnonimaging').text('~' + HumanReadableFilesize(total));
						$('#stattotal').text('~' + HumanReadableFilesize(parseFloat($('#stattotal').data('bytes')) + data.bytes));
						$('.statnote').hide();
					})
					.fail(function() {
						btn.removeClass('loading disabled');
						$('#tserror').text('Unable to load the timeseries counts. The request failed or timed out').show();
					});
			});
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- GetImagingUsage -------------------- */
	/* -------------------------------------------- */
	/* Returns [per-modality rows (sorted by size, largest first), totals] of the project's imaging
	   data across every modality that has a *_series table */
	function GetImagingUsage($projectid) {
		$projectid = (int)$projectid;
		$rows = array();
		$totals = array('studies' => 0, 'series' => 0, 'files' => 0, 'size' => 0);

		/* modality descriptions */
		$descs = array();
		$sqlstring = "select mod_code, mod_desc from modalities";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$descs[strtolower($row['mod_code'])] = $row['mod_desc'];
		}

		/* find the series tables that exist, and which file-count column each has. Most use
		   series_numfiles; mr_series uses numfiles */
		$tables = array();
		$sqlstring = "select table_name, column_name from information_schema.columns where table_schema = database() and table_name like '%\_series' and column_name in ('study_id', 'series_size', 'series_numfiles', 'numfiles')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$row = array_change_key_case($row, CASE_LOWER);
			$tables[$row['table_name']][$row['column_name']] = 1;
		}

		foreach ($descs as $modality => $desc) {
			$table = GetSeriesTableName($modality);
			if (($table == '') || !isset($tables[$table]['study_id']) || !isset($tables[$table]['series_size']))
				continue;

			if (isset($tables[$table]['series_numfiles']))
				$filecol = "a.series_numfiles";
			elseif (isset($tables[$table]['numfiles']))
				$filecol = "a.numfiles";
			else
				$filecol = "0";

			/* $table is validated against information_schema above, so it is safe to use in the query */
			$sqlstring = "select count(distinct a.study_id) 'studies', count(*) 'series', coalesce(sum($filecol),0) 'files', coalesce(sum(a.series_size),0) 'size' from `$table` a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);

			if (($row['series'] ?? 0) == 0)
				continue;

			$rows[] = array('modality' => $modality, 'desc' => $desc ?? '', 'studies' => (int)$row['studies'], 'series' => (int)$row['series'], 'files' => (float)$row['files'], 'size' => (float)$row['size']);
			$totals['series'] += (int)$row['series'];
			$totals['files'] += (float)$row['files'];
			$totals['size'] += (float)$row['size'];
		}

		usort($rows, function($a, $b) { return $b['size'] <=> $a['size']; });

		/* total distinct studies (a study can contain more than one modality) */
		$sqlstring = "select count(*) 'studies' from studies a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$totals['studies'] = (int)($row['studies'] ?? 0);

		return array($rows, $totals);
	}


	/* -------------------------------------------- */
	/* ------- GetNonImagingUsage ----------------- */
	/* -------------------------------------------- */
	/* Returns the number of observations and interventions in the project, and the number and total
	   size of the files stored in the database for its observations. Timeseries timepoints are
	   counted separately, on request, by GetTimeseriesUsage() */
	function GetNonImagingUsage($projectid) {
		$projectid = (int)$projectid;
		$usage = array('observations' => 0, 'interventions' => 0, 'files' => 0, 'filebytes' => 0);

		$queries = array(
			'observations' => "select count(*) 'num' from observations a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ?",
			'interventions' => "select count(*) 'num' from interventions a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ?"
		);

		foreach ($queries as $key => $sqlstring) {
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $projectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$usage[$key] = (int)($row['num'] ?? 0);
		}

		/* files (BLOBs) linked from the project's observations. file_size is the size of the blob */
		$sqlstring = "select count(*) 'num', coalesce(sum(file_size),0) 'bytes' from files where file_id in (select a.observation_fileid from observations a join enrollment b on a.enrollment_id = b.enrollment_id where b.project_id = ? and a.observation_fileid > 0)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$usage['files'] = (int)($row['num'] ?? 0);
		$usage['filebytes'] = (float)($row['bytes'] ?? 0);

		return $usage;
	}


	/* -------------------------------------------- */
	/* ------- GetTimeseriesUsage ----------------- */
	/* -------------------------------------------- */
	/* Returns the number of timeseries timepoints in the project, and the number of observations they
	   belong to. This can be slow on a large timeseries table, so it is only run on request */
	function GetTimeseriesUsage($projectid) {
		$projectid = (int)$projectid;
		$usage = array('timepoints' => 0, 'timeseriesobservations' => 0);

		/* timeseries is large, and joining it directly to observations makes MariaDB scan every
		   timepoint in the table. Instead, get the few distinct observation_ids that have timeseries
		   data (a loose index scan on observation_id_time), keep the ones in this project, then
		   count only their timepoints */
		$obsids = array();
		$sqlstring = "select b.observation_id from (select distinct observation_id from timeseries) a join observations b on a.observation_id = b.observation_id join enrollment c on b.enrollment_id = c.enrollment_id where c.project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$obsids[] = (int)$row['observation_id'];
		}
		mysqli_stmt_close($stmt);

		$usage['timeseriesobservations'] = count($obsids);
		if (count($obsids) > 0) {
			$placeholders = implode(',', array_fill(0, count($obsids), '?'));
			$sqlstring = "select count(*) 'num' from timeseries where observation_id in ($placeholders)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, str_repeat('i', count($obsids)), ...$obsids);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $obsids);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$usage['timepoints'] = (int)($row['num'] ?? 0);
		}

		return $usage;
	}


	/* -------------------------------------------- */
	/* ------- GetTimeseriesUsageJSON ------------- */
	/* -------------------------------------------- */
	/* AJAX handler: prints the project's timeseries counts as JSON. Requires View Data on the project */
	function GetTimeseriesUsageJSON($projectid) {
		$projectid = (int)$projectid;
		header('Content-Type: application/json');

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'viewdata', $projectid)) {
			echo json_encode(array('error' => 'You do not have permissions to view data in this project'));
			return;
		}

		/* release the session lock so the user's other pages aren't blocked while the count runs */
		session_write_close();
		set_time_limit(0);

		$usage = GetTimeseriesUsage($projectid);
		$tablestats = GetTableStats();
		$bytes = EstimateTableShare($usage['timepoints'], $tablestats['timeseries']);
		echo json_encode(array('timepoints' => number_format($usage['timepoints']), 'observations' => number_format($usage['timeseriesobservations']), 'bytes' => (int)$bytes));
	}


	/* -------------------------------------------- */
	/* ------- GetTableStats ---------------------- */
	/* -------------------------------------------- */
	/* Returns [table => [rows, bytes]] for the non-imaging tables, where bytes is the table's data
	   plus index size. These come from the table statistics, so they are instant. The tables are
	   Aria, which keeps an exact row count (InnoDB's table_rows is only an estimate) */
	function GetTableStats() {
		$stats = array();
		foreach (array('observations', 'interventions', 'timeseries', 'files') as $table)
			$stats[$table] = array('rows' => 0, 'bytes' => 0);

		$sqlstring = "select table_name, table_rows, data_length, index_length from information_schema.tables where table_schema = database() and table_name in ('observations', 'interventions', 'timeseries', 'files')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$row = array_change_key_case($row, CASE_LOWER);
			$stats[$row['table_name']] = array('rows' => (float)($row['table_rows'] ?? 0), 'bytes' => (float)($row['data_length'] ?? 0) + (float)($row['index_length'] ?? 0));
		}

		return $stats;
	}


	/* -------------------------------------------- */
	/* ------- EstimateTableShare ----------------- */
	/* -------------------------------------------- */
	/* Estimated bytes used by $rows rows of a table: the table's data and index size, divided
	   proportionally by number of rows. $stats is one table's entry from GetTableStats() */
	function EstimateTableShare($rows, $stats) {
		if ($stats['rows'] <= 0)
			return 0;

		return round(min($rows, $stats['rows']) / $stats['rows'] * $stats['bytes']);
	}


	/* -------------------------------------------- */
	/* ------- FormatRowCount --------------------- */
	/* -------------------------------------------- */
	/* Formats a large row count for display, e.g. "33.2 million" */
	function FormatRowCount($rows) {
		if ($rows >= 1000000000)
			return number_format($rows / 1000000000, 1) . " billion";
		elseif ($rows >= 1000000)
			return number_format($rows / 1000000, 1) . " million";
		else
			return number_format($rows);
	}
?>

<? include("footer.php") ?>
